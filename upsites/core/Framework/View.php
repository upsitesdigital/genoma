<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Renderiza um módulo (template-pages/{slug}/) dentro de um template PHP.
 *
 * O WordPress resolve a rota e escolhe o template (hierarquia normal); o template
 * chama View::render(), o conteúdo do módulo vem do `page($id, $params)` do controller,
 * chamado direto em PHP (fetch) — e passa para template-pages/{slug}/{slug}.view.php,
 * que compõe as seções com Part::render(). REST só para o que o JS busca depois
 * (métodos com #[Get]). O HTML sai pronto do servidor; a interatividade é da
 * Interactivity API do WP.
 *
 *   // single.php
 *   Core\Framework\View::render('single-post', get_the_ID());
 *
 *   // archive.php — parâmetros extras chegam no segundo argumento do page()
 *   Core\Framework\View::render('blog', null, ['page' => max(1, get_query_var('paged'))]);
 */
final class View
{
    /** @var array{module: string, pageId: int|null, url: string, title: string}|null */
    private static ?array $current = null;

    private static mixed $data = null;

    /** Status HTTP da chamada interna ao controller (200, 404, 500…). */
    private static int $status = 200;

    /** Monta a página inteira: header.php, a view do módulo com os dados e footer.php. */
    public static function render(string $module, ?int $id = null, array $params = []): void
    {
        $id = $id ?: null;

        // página com senha ainda não digitada: só o formulário do WP — os campos não são buscados
        // (a REST do módulo também responde 404 nesse caso)
        if ($id && post_password_required($id)) {
            self::$current = ['module' => $module, 'pageId' => $id, 'url' => (string) get_permalink($id), 'title' => get_the_title($id)];
            get_header();
            echo '<main class="mx-auto max-w-xl px-4 py-24">' . get_the_password_form($id) . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- formulário do próprio WP (filtro the_password_form)
            get_footer();
            return;
        }

        self::$current = [
            'module' => $module,
            'pageId' => $id,
            'url'    => $id ? (string) get_permalink($id) : self::requestUrl(),
            'title'  => $id ? get_the_title($id) : wp_get_document_title(),
        ];

        $view = self::viewFile($module);
        self::$data = $view ? self::fetch($module, $id, $params) : null;

        // status HTTP de verdade (sem "soft 404" indexado): view inexistente → 404; controller falhou → 404/500
        if (!$view) {
            status_header(404);
            error_log("[upwork] View::render('{$module}'): template-pages/{$module}/{$module}.view.php não existe");
        } elseif (self::$status >= 400) {
            status_header(self::$status === 404 ? 404 : 500);
            nocache_headers();
        }

        get_header();

        if (!$view) {
            Part::render('shared/not-found');
        } elseif (!is_array(self::$data)) {
            printf('<main class="container py-16 text-center text-muted-foreground">%s</main>', esc_html__('Erro ao carregar.', 'upsites'));
        } else {
            $data = self::$data;
            $html = Part::capture(static function () use ($view, $data): void {
                (static function (string $__file, array $data): void {
                    require $__file;
                })($view, $data);
            }, $module);
            $html = self::ensureH1($html, (string) (self::$current['title'] ?? ''));
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup das seções, escapado nelas
        }

        get_footer();
    }

    /**
     * Página sem <h1> (hero oculto pelo editor ou sem título) ganha um `<h1 class="sr-only">`
     * com o título da página logo no início do <main> — a página continua com um título para
     * leitor de tela e buscadores. Com <h1> nas seções, nada muda.
     */
    public static function ensureH1(string $html, string $title): string
    {
        if ($title === '' || preg_match('/<h1[\s>]/i', $html)) return $html;

        $h1 = '<h1 class="sr-only">' . esc_html($title) . '</h1>';
        // callback (não string de substituição): `$0`/`\1` no título ("R$0,99") seriam lidos como
        // referência ao trecho casado e duplicariam o <main>
        $withMain = preg_replace_callback('/<main\b[^>]*>/i', static fn($m) => $m[0] . $h1, $html, 1, $count);
        return $count ? (string) $withMain : $h1 . $html;
    }

    /** template-pages/{slug}/{slug}.view.php, ou null se o slug for inválido/o arquivo não existir. */
    private static function viewFile(string $module): ?string
    {
        if (!preg_match('/^[a-z0-9-]+$/', $module)) return null;
        $file = get_template_directory() . "/template-pages/{$module}/{$module}.view.php";
        return is_file($file) ? $file : null;
    }

    /**
     * Maior imagem (ImageData: `src` + `width`) da primeira seção visível dos dados do módulo —
     * usada como og:image (Core\Support\Seo). Ignora SVG (ícone/logo) e imagem com largura
     * conhecida < 300px.
     */
    public static function lcpImage(mixed $data): ?string
    {
        if (!is_array($data)) return null;
        $first = null;
        foreach ($data as $section) {
            if (is_array($section)) { $first = $section; break; }
        }
        if ($first === null) return null;

        $best = null;
        $bestArea = -1;
        $walk = function (array $node) use (&$walk, &$best, &$bestArea): void {
            if (isset($node['src']) && is_string($node['src']) && array_key_exists('width', $node)) {
                $isSvg = !empty($node['svg']) || str_ends_with(strtolower((string) wp_parse_url($node['src'], PHP_URL_PATH)), '.svg');
                $w = (int) ($node['width'] ?? 0);
                $h = (int) ($node['height'] ?? 0);
                if (!$isSvg && ($w === 0 || $w >= 300) && $w * $h > $bestArea) {
                    $best = $node['src'];
                    $bestArea = $w * $h;
                }
                return;
            }
            foreach ($node as $child) if (is_array($child)) $walk($child);
        };
        $walk($first);

        return $best;
    }

    /** Dados do módulo da requisição atual (o retorno do controller). */
    public static function data(): mixed
    {
        return self::$data;
    }

    /** Módulo sendo renderizado na requisição atual (null = template PHP puro). */
    public static function current(): ?array
    {
        return self::$current;
    }

    public static function isActive(): bool
    {
        return self::$current !== null;
    }

    /** URL da requisição atual, sem query string. */
    private static function requestUrl(): string
    {
        // esquema e host do site (home_url), nunca do cabeçalho Host da requisição (controlado pelo cliente)
        $uri  = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/')); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- esc_url_raw sanitiza
        $path = (string) (wp_parse_url($uri, PHP_URL_PATH) ?: '/');
        $home = wp_parse_url(home_url());
        $port = isset($home['port']) ? ':' . $home['port'] : '';
        return esc_url_raw(($home['scheme'] ?? 'http') . '://' . ($home['host'] ?? '') . $port . $path);
    }

    /**
     * Dados do módulo: `page(int $id, array $params)` do controller, chamado direto em PHP (sem
     * REST). O id vem do WordPress (o template já passou pela main query) e os $params do
     * template — nada vem da URL. Retorna null se falhar (self::$status = 404/500).
     */
    public static function fetch(string $module, ?int $id, array $params = []): mixed
    {
        self::$status = 200;
        $controller = ModuleLoader::controller($module);

        if (!$controller || !method_exists($controller, 'page')) {
            error_log("[upwork] módulo '{$module}' sem controller com page(int \$pageId): array — a página não tem dados (npm run make:module gera o formato)");
            return self::fail(404);
        }

        $method = new \ReflectionMethod($controller, 'page');
        $auth   = ($method->getAttributes(Attributes\Auth::class)[0] ?? null)?->newInstance();
        $cache  = ($method->getAttributes(Attributes\Cache::class)[0] ?? null)?->newInstance();

        // segunda barreira (o WP já filtrou na main query): template que passe um id qualquer
        // não expõe rascunho/privado a quem não pode lê-lo
        // (mesma regra do Rest::postVisibility; o que não é público nunca vai para o cache)
        $public = true;
        if ($id) {
            $post = get_post($id);
            if (!$post) return self::fail(404);
            $viewable = is_post_publicly_viewable($post);
            $public   = $viewable && $post->post_password === '';
            $allowed  = $public
                || current_user_can('edit_post', $post->ID)                                          // pré-visualização
                || ($post->post_password !== '' && $viewable && !post_password_required($post))      // senha já digitada
                || ($post->post_status === 'private' && current_user_can('read_post', $post->ID));
            if (!$allowed) return self::fail(404);
        }
        // #[Auth] numa página: sem login/capability → 404 (não revela que existe)
        if ($auth && (!is_user_logged_in() || ($auth->capability() !== '' && !current_user_can($auth->capability())))) {
            return self::fail(404);
        }

        // cache só do que é igual para todo visitante: conteúdo público, sem usuário logado,
        // fora da pré-visualização (rascunho/campos não salvos nunca vão para o cache) e só em
        // GET/HEAD sem corpo — a resposta a um POST (envio de formulário sem JS, com os dados
        // digitados) não pode ser gravada nem servida a outro visitante
        $verb = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- só comparado com GET/HEAD
        $key = $cache && $public && !$auth && !is_user_logged_in() && !is_preview()
            && in_array($verb, ['GET', 'HEAD'], true) && empty($_POST) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- só checa se há corpo
            // 'p2' = formato dos dados (CF7 por referência); data do arquivo do controller = deploy
            // que muda o page() não serve o retorno antigo (os views novos leriam chaves que não existem)
            ? 'upwork_page_' . md5((string) wp_json_encode(['p2', get_option(Rest::CACHE_VERSION, '1'), get_class($controller), (int) filemtime((string) $method->getFileName()), (int) $id, $params]))
            : null;
        if ($key) {
            $cached = get_transient($key);
            if (is_array($cached)) return $cached;
        }

        try {
            $data = $controller->page((int) $id, $params);
        } catch (\Throwable $e) {
            error_log(sprintf('[upwork] %s::page(%d) → %s: %s em %s:%d', get_class($controller), (int) $id, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
            return self::fail(500);
        }
        if (!is_array($data)) return self::fail(404);

        if ($key) set_transient($key, $data, $cache->ttl);
        return $data;
    }

    private static function fail(int $status): ?array
    {
        self::$status = $status;
        return null;
    }
}
