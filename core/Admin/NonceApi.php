<?php
declare(strict_types=1);

namespace Core\Admin;

class NonceApi
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route('framework/v1', '/nonce', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'refresh'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function refresh(): \WP_REST_Response
    {
        return rest_ensure_response(['nonce' => wp_create_nonce('wp_rest')]);
    }
}
