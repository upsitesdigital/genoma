<?php
declare(strict_types=1);

namespace Core\Admin;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Renovação do nonce da REST (X-WP-Nonce). O nonce da config da Interactivity API (getConfig("upwork").nonce) envelhece
 * (12–24h, ou mais com cache de página); quando a REST responde
 * `rest_cookie_invalid_nonce`, o módulo upwork/api (upsites/core/js/api.js) pede um novo aqui e repete.
 *
 * É admin-ajax e não REST de propósito: a REST sem nonce trata a requisição como
 * visitante (usuário 0) e devolveria o nonce errado; o admin-ajax reconhece o
 * usuário pelo cookie.
 */
final class NonceApi
{
    public const ACTION = 'upwork_rest_nonce';

    public static function register(): void
    {
        add_action('wp_ajax_' . self::ACTION, [self::class, 'refresh']);
        add_action('wp_ajax_nopriv_' . self::ACTION, [self::class, 'refresh']);
    }

    public static function refresh(): void
    {
        nocache_headers();
        wp_send_json(['nonce' => wp_create_nonce('wp_rest')]);
    }
}
