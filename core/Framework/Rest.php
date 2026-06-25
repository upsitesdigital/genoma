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
            $cacheKey = $cache ? 'fw_rest_' . md5($request->get_route() . serialize($request->get_params())) : null;

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
