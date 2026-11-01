<?php
declare(strict_types=1);

namespace Core\Framework;

use Core\Framework\Attributes;

class Rest
{
    const NAMESPACE = 'framework/v1';

    /** Lê todos os métodos públicos de um controller e registra as rotas via attributes. */
    public static function compileController(string $className): void
    {
        if (!class_exists($className)) return;

        $ref      = new \ReflectionClass($className);
        $instance = $ref->newInstance();

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

        register_rest_route(self::NAMESPACE, $wpPath, [
            'methods'             => $methods,
            'callback'            => self::buildCallback($instance, $method, $cacheInstance),
            'permission_callback' => self::buildPermission($authInstance),
        ]);
    }

    private static function buildCallback(
        object $instance,
        \ReflectionMethod $method,
        ?Attributes\Cache $cache,
    ): callable {
        return function (\WP_REST_Request $request) use ($instance, $method, $cache) {
            // No WP REST a query string tem precedência sobre os params da rota:
            // sem isso, /pagina-padrao/3?id=118 serviria o post 118.
            $request->set_query_params(array_diff_key($request->get_query_params(), $request->get_url_params()));

            $denied = self::guardPost($request);
            if ($denied) return $denied;

            // Logado não usa cache: a resposta pode conter o que só ele pode ver.
            $cacheKey = $cache && !is_user_logged_in() ? self::cacheKey($request, $cache) : null;

            if ($cacheKey) {
                $cached = get_transient($cacheKey);
                if ($cached !== false) return rest_ensure_response($cached);
            }

            try {
                $result = $method->invoke($instance, $request);
            } catch (\Throwable $e) {
                return new \WP_Error(
                    'fw_controller_error',
                    $e->getMessage(),
                    ['status' => 500]
                );
            }

            if ($cacheKey && $cache) {
                set_transient($cacheKey, $result, $cache->ttl);
            }

            return rest_ensure_response($result);
        };
    }

    /**
     * Por convenção, `id` nas rotas de módulo é o ID do post/página exibido.
     * Só passa o que o WP mostraria publicamente (ou o que o usuário logado pode
     * ler, ex: preview de rascunho) e nunca conteúdo protegido por senha.
     */
    private static function guardPost(\WP_REST_Request $request): ?\WP_Error
    {
        $id = $request->get_param('id');
        if ($id === null || $id === '') return null;

        $post    = is_scalar($id) ? get_post((int) $id) : null;
        $visible = $post instanceof \WP_Post
            && (is_post_publicly_viewable($post) || current_user_can('read_post', $post->ID))
            && !post_password_required($post);

        return $visible ? null : new \WP_Error('fw_not_found', 'Conteúdo não encontrado.', ['status' => 404]);
    }

    /**
     * Chave = rota (já inclui os params de URL) + só os params de query que a
     * rota declara em #[Cache(vary: [...])]. Query string desconhecida
     * (utm_*, fbclid…) não gera transient novo.
     */
    private static function cacheKey(\WP_REST_Request $request, Attributes\Cache $cache): string
    {
        $vary = [];
        foreach ($cache->vary as $param) {
            $value = $request->get_param($param);
            $vary[$param] = is_scalar($value) ? trim((string) $value) : '';
        }

        return 'fw_rest_' . md5($request->get_route() . serialize($vary));
    }

    private static function buildPermission(?Attributes\Auth $auth): callable
    {
        if ($auth === null) return '__return_true';

        return static function () use ($auth): bool {
            if (!is_user_logged_in()) return false;
            if ($auth->role !== '') return current_user_can($auth->role);
            return true;
        };
    }

    /** Converte :param e :param? para o formato regex do WP REST API. */
    private static function convertPath(string $path): string
    {
        // :param? → opcional (deve estar no final)
        $path = preg_replace('/:(\w+)\?/', '(?P<$1>[a-zA-Z0-9-_]*)', $path) ?? $path;
        // :param → obrigatório
        $path = preg_replace('/:(\w+)/', '(?P<$1>[a-zA-Z0-9-_]+)', $path) ?? $path;
        return $path;
    }
}
