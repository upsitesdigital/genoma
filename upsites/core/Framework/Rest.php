<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Attributes;

final class Rest
{
    public const NAMESPACE = 'framework/v1';

    /**
     * rest_pre_dispatch: GET/HEAD das rotas do framework não têm corpo, mas o WP ainda leria um
     * JSON/form enviado junto (get_param prioriza o corpo) — descartado ANTES da validação dos
     * `args`, para a resposta (e a chave do cache) depender só do caminho e da query.
     */
    public static function stripGetBody(mixed $result, \WP_REST_Server $server, \WP_REST_Request $request): mixed
    {
        if (in_array($request->get_method(), ['GET', 'HEAD'], true) && str_starts_with(strtolower(ltrim($request->get_route(), '/')), self::NAMESPACE . '/')) {
            $request->set_body('');
            $request->set_body_params([]);
        }
        return $result;
    }

    /** Lê todos os métodos públicos de um controller e compila as rotas dos attributes. */
    public static function compileController(object|string $controller): void
    {
        if (is_string($controller) && !class_exists($controller)) return;

        $ref      = new \ReflectionClass($controller);
        $instance = is_object($controller) ? $controller : $ref->newInstance();

        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor()) continue;
            self::compileMethod($instance, $method);
        }
    }

    private static function compileMethod(object $instance, \ReflectionMethod $method): void
    {
        $map = [
            Attributes\Get::class    => 'GET',
            Attributes\Post::class   => 'POST',
            Attributes\Put::class    => 'PUT',
            Attributes\Patch::class  => 'PATCH',
            Attributes\Delete::class => 'DELETE',
        ];

        foreach ($map as $attrClass => $httpMethod) {
            foreach ($method->getAttributes($attrClass) as $attr) {
                self::register($httpMethod, $attr->newInstance()->path, $instance, $method);
            }
        }

        foreach ($method->getAttributes(Attributes\Route::class) as $attr) {
            $routeAttr = $attr->newInstance();
            self::register($routeAttr->methods, $routeAttr->path, $instance, $method);
        }
    }

    /**
     * Rotas compiladas dos controllers (métodos com #[Get]/#[Post]…), registradas no WP pelo
     * registerRoutes — o endpoint que os stores do JS chamam (paginação AJAX, filtros).
     *
     * @var list<array{methods: list<string>, path: string, callback: callable, permission_callback: callable|string, args: array}>
     */
    private static array $routes = [];

    private static function register(
        string|array $methods,
        string $path,
        object $instance,
        \ReflectionMethod $method,
    ): void {
        $wpPath = self::convertPath($path);
        $auth   = $method->getAttributes(Attributes\Auth::class)[0] ?? null;
        $cache  = $method->getAttributes(Attributes\Cache::class)[0] ?? null;

        $authInstance  = $auth?->newInstance();
        $cacheInstance = $cache?->newInstance();
        $list          = preg_split('/[\s,]+/', strtoupper(implode(',', (array) $methods)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $writes        = array_diff($list, ['GET', 'HEAD']);
        if ($cacheInstance && $writes) {
            error_log(sprintf('[upwork] #[Cache] em %s::%s vale só para GET — %s não é cacheado', $method->class, $method->name, implode(',', $writes)));
        }

        self::$routes[] = [
            'methods'             => $list,
            'path'                => $wpPath,
            'callback'            => self::buildCallback($instance, $method, $cacheInstance, $authInstance !== null),
            'permission_callback' => self::buildPermission($authInstance, $list, $path),
            'args'                => self::pathArgs($wpPath, $cacheInstance),
        ];
    }

    /** rest_api_init: registra no WP as rotas compiladas (ModuleLoader::registerRestRoutes). */
    public static function registerRoutes(): void
    {
        foreach (self::$routes as $route) {
            register_rest_route(self::NAMESPACE, $route['path'], [
                'methods'             => $route['methods'],
                'callback'            => $route['callback'],
                'permission_callback' => $route['permission_callback'],
                'args'                => $route['args'],
            ]);
        }
    }

    /**
     * `args` da rota (REST Handbook): um por parâmetro do caminho (`:id`, `:slug`…) e por
     * parâmetro de `vary` do #[Cache] — com tipo, descrição (aparece no OPTIONS/schema),
     * validação e sanitização. O regex do caminho já restringe os caracteres; aqui entra o
     * limite de tamanho e o `sanitize_text_field`. O `id` não é sanitizado aqui de propósito:
     * a forma canônica (sem zero à esquerda) é conferida em effectiveId (404, não 400).
     */
    private static function pathArgs(string $wpPath, ?Attributes\Cache $cache): array
    {
        $short = static fn($v): bool => is_scalar($v) && strlen((string) $v) <= 200;
        $args  = [];

        preg_match_all('/\(\?P<(\w+)>/', $wpPath, $m);
        foreach ($m[1] as $name) {
            $args[$name] = $name === 'id'
                ? ['type' => 'string', 'description' => 'ID do post do módulo.', 'validate_callback' => static fn($v): bool => $v === '' || (is_string($v) && ctype_digit($v) && strlen($v) <= 20)]
                : ['type' => 'string', 'description' => "Parâmetro {$name} do caminho.", 'validate_callback' => $short, 'sanitize_callback' => 'sanitize_text_field'];
        }

        foreach ($cache?->vary ?? [] as $name) {
            if (isset($args[$name])) continue;
            $args[$name] = ['type' => 'string', 'description' => "Parâmetro {$name} da query (entra na chave do cache).", 'required' => false, 'validate_callback' => $short, 'sanitize_callback' => 'sanitize_text_field'];
        }

        return $args;
    }

    /** Option com a versão do cache (páginas e REST) — entra na chave; trocar a versão invalida tudo. */
    public const CACHE_VERSION = 'upwork_cache_version';

    /** Prefixos dos transients do framework (o de agora e os de antes da troca para upwork_). */
    private const TRANSIENT_PREFIXES = ['upwork_page_', 'upwork_rest_', 'fw_page_', 'fw_rest_'];

    /**
     * Invalida todo o cache (páginas e REST) trocando a versão da chave — funciona com
     * transients no banco e com Redis/Memcached. Sem object cache, as entradas antigas (que
     * nunca mais serão lidas) saem do wp_options na hora, em vez de esperar o cron diário;
     * com object cache, o TTL cuida delas.
     */
    public static function flushCache(): void
    {
        update_option(self::CACHE_VERSION, (string) microtime(true), true);
        if (wp_using_ext_object_cache()) return;

        global $wpdb;
        $like = [];
        foreach (self::TRANSIENT_PREFIXES as $prefix) {
            $like[] = $wpdb->esc_like('_transient_' . $prefix) . '%';
            $like[] = $wpdb->esc_like('_transient_timeout_' . $prefix) . '%';
        }
        $where = implode(' OR ', array_fill(0, count($like), 'option_name LIKE %s'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- limpeza em lote dos transients do próprio tema; $where só tem placeholders
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE {$where}", $like));
        delete_option('fw_rest_cache_version'); // nome antigo da versão
    }

    private static bool $flushQueued = false;

    /**
     * flushCache() uma vez, no fim da requisição: um salvamento dispara vários hooks
     * (set_object_terms, save_post, acf/save_post) — invalida uma vez só e depois de tudo
     * gravado (uma visita no meio do salvamento não deixa no cache a página com metade dos campos).
     */
    public static function flushCacheLater(): void
    {
        if (self::$flushQueued) return;
        self::$flushQueued = true;
        add_action('shutdown', [self::class, 'flushCache'], 0, 0);
    }

    /**
     * Chave do cache: rota + parâmetros do caminho + só os parâmetros listados em
     * `vary` (o resto da query string é ignorado: `?x=1`, `?x=2` não criam entradas)
     * + usuário em rota autenticada (a resposta de um usuário nunca vai para outro)
     * + versão (flushCache()).
     */
    private static function cacheKey(\WP_REST_Request $request, Attributes\Cache $cache, bool $private, string $endpoint): string
    {
        // valores COMO o controller os lê (get_param: corpo JSON → query → caminho), não a query/o
        // caminho crus — senão `?slug=b` em `/x/a` (ou um corpo JSON) gravaria a resposta de b na
        // entrada de a e a serviria a todos
        $query = [];
        foreach ($cache->vary as $name) {
            $value = $request->get_param($name);
            if ($value !== null) $query[$name] = is_scalar($value) ? (string) $value : wp_json_encode($value);
        }
        ksort($query);
        // endpoint = Classe::método (não o caminho cru: `/HOME/1` e `/home/1` são a mesma rota no
        // WP) + parâmetros do caminho COMO o controller os recebe (sem mudar caixa — `/x/Foo` e
        // `/x/foo` podem ser respostas diferentes) + id canônico (effectiveId: `01` já deu 404).
        // Cada valor de `:param` cria uma entrada: o controller deve recusar valor inexistente.
        $params = [];
        foreach (array_keys($request->get_url_params()) as $name) {
            if ($name !== 'id') $params[$name] = $request->get_param($name);
        }
        ksort($params);
        $parts = [
            get_option(self::CACHE_VERSION, '1'),
            $request->get_method(),
            $endpoint,
            array_map(static fn($v) => is_scalar($v) ? (string) $v : '', $params),
            self::effectiveId($request),
            $query,
            $private ? get_current_user_id() : 0,
        ];

        return 'upwork_rest_' . md5((string) wp_json_encode($parts));
    }

    private static function buildCallback(
        object $instance,
        \ReflectionMethod $method,
        ?Attributes\Cache $cache,
        bool $private,
    ): callable {
        return function (\WP_REST_Request $request) use ($instance, $method, $cache, $private) {
            $visibility = self::postVisibility($request);
            if ($visibility === null) {
                return new \WP_Error('fw_not_found', __('Conteúdo não encontrado.', 'upsites'), ['status' => 404]);
            }

            // só conteúdo público vai para o cache (rascunho/privado/com senha: sempre na hora), e só
            // leitura: POST/PUT/DELETE com #[Cache] responderiam do cache sem rodar a gravação
            $endpoint = $method->class . '::' . $method->name;
            $readOnly = in_array($request->get_method(), ['GET', 'HEAD'], true);
            $cacheKey = $cache && $readOnly && ($private || $visibility === 'public') ? self::cacheKey($request, $cache, $private, $endpoint) : null;

            if ($cacheKey) {
                $cached = get_transient($cacheKey);
                if ($cached !== false) return rest_ensure_response($cached);
            }

            try {
                $result = $method->invoke($instance, $request);
            } catch (\Throwable $e) {
                // detalhe só no log; para quem chamou, mensagem genérica (com WP_DEBUG, a real)
                error_log(sprintf(
                    '[upwork] %s %s → %s: %s em %s:%d',
                    $request->get_method(), $request->get_route(), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()
                ));
                return new \WP_Error(
                    'fw_controller_error',
                    defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : __('Erro interno ao carregar o conteúdo.', 'upsites'),
                    ['status' => 500]
                );
            }

            // rota pública: só grava o que foi gerado para visitante — gerado com um usuário logado,
            // a resposta pode levar dados dele (ex.: e-mail pré-preenchido no formulário do CF7)
            // erro devolvido pelo controller (WP_Error/resposta 4xx-5xx) não vai para o cache
            $failed = is_wp_error($result) || ($result instanceof \WP_REST_Response && $result->get_status() >= 400);
            if ($cacheKey && $cache && !$failed && ($private || !is_user_logged_in())) {
                set_transient($cacheKey, $result, $cache->ttl);
            }

            return rest_ensure_response($result);
        };
    }

    /**
     * Rota com `:id` (post do módulo): o post precisa existir e ser visível para quem pede.
     *  'public'  → publicado, de tipo público e sem senha (pode ir para o cache);
     *  'private' → visível só para este usuário: quem pode editar (pré-visualização de
     *              rascunho), post privado com permissão de leitura, ou senha já digitada;
     *  null      → não existe ou não pode ser lido (404 — não revela se existe).
     * Rota sem `:id` → 'public'.
     */
    private static function postVisibility(\WP_REST_Request $request): ?string
    {
        // o id conferido em todas as origens (ver effectiveId)
        $id = self::effectiveId($request);
        if ($id === null) return 'public';
        if (!ctype_digit($id)) return null;

        $post = get_post((int) $id);
        if (!$post) return null;

        $viewable    = is_post_publicly_viewable($post);
        $hasPassword = $post->post_password !== '';
        if ($viewable && !$hasPassword) return 'public';
        if (current_user_can('edit_post', $post->ID)) return 'private';
        if ($hasPassword) return $viewable && !post_password_required($post) ? 'private' : null;

        return $post->post_status === 'private' && current_user_can('read_post', $post->ID) ? 'private' : null;
    }

    /**
     * `id` da requisição, conferido em TODAS as origens (caminho, query string, corpo/JSON):
     * o controller lê o do caminho (Controller::pageId) e o get_param() do WP dá prioridade à
     * query — checar uma origem só deixaria `/about/{rascunho}?id={publicado}` passar.
     * Mais de um valor diferente, valor não numérico ou não canônico (`01`) → 'x' (404).
     * null = sem id.
     */
    private static function effectiveId(\WP_REST_Request $request): ?string
    {
        // força o parse do corpo: em PUT/PATCH/DELETE o WP só lê o form-urlencoded sob demanda —
        // sem isto get_body_params() viria vazio aqui e o controller leria o id do corpo depois
        $request->get_params();

        $hasPathId = array_key_exists('id', $request->get_url_params());
        $sources = [
            $request->get_url_params(),
            $request->get_query_params(),
            $request->get_body_params(),
            $request->get_json_params() ?? [],
        ];

        $ids = [];
        foreach ($sources as $params) {
            if (!is_array($params) || !array_key_exists('id', $params)) continue;
            $id = $params['id'];
            if ($id === null || $id === '') continue;
            if (!is_scalar($id)) return 'x'; // array/objeto → inválido
            $ids[(string) $id] = true;
        }

        if (!$ids) return null;
        if (count($ids) > 1) return 'x';
        // rota sem `:id` não lê id de outra origem (pageId() só lê o caminho): `?id=` aí só
        // serviria para criar uma entrada de cache por post público
        if (!$hasPathId) return 'x';

        $id = (string) array_key_first($ids);
        // canônico: só dígitos, sem zero à esquerda — `01`, `001` não criam entradas de cache novas
        return ctype_digit($id) && $id === (string) (int) $id ? $id : 'x';
    }

    /**
     * Sem #[Auth]: leitura (GET/HEAD) é pública; escrita (POST/PUT/PATCH/DELETE) é NEGADA —
     * esquecer o #[Auth] num endpoint que grava não pode virar escrita anônima. Escrita
     * pública de verdade (raro; formulários são do CF7) exige `#[Auth]` + validação própria.
     */
    private static function buildPermission(?Attributes\Auth $auth, array $methods, string $path): callable
    {
        if ($auth === null) {
            $writes = array_diff($methods, ['GET', 'HEAD']);
            if (!$writes) return '__return_true';

            error_log(sprintf('[upwork] rota %s %s sem #[Auth]: negada (escrita exige #[Auth])', implode(',', $writes), $path));
            return '__return_false';
        }

        return static function () use ($auth): bool {
            if (!is_user_logged_in()) return false;
            $cap = $auth->capability();
            return $cap === '' || current_user_can($cap);
        };
    }

    /** Converte :param e :param? para o formato regex do WP REST API (`:id` só aceita dígitos). */
    private static function convertPath(string $path): string
    {
        $path = preg_replace('/:id\?(?!\w)/', '(?P<id>\d*)', $path) ?? $path;
        $path = preg_replace('/:id(?!\w)/', '(?P<id>\d+)', $path) ?? $path;
        // :param? → opcional (deve estar no final)
        $path = preg_replace('/:(\w+)\?/', '(?P<$1>[a-zA-Z0-9-_]*)', $path) ?? $path;
        // :param → obrigatório
        $path = preg_replace('/:(\w+)/', '(?P<$1>[a-zA-Z0-9-_]+)', $path) ?? $path;
        return $path;
    }
}
