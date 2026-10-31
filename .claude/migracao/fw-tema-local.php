<?php
/**
 * Plugin Name: Troca de tema por requisição (só local)
 * Description: Migração do genoma (React → v3). Só em localhost/CLI: com o cookie fw_tema (ou a variável de ambiente FW_TEMA no CLI) = pasta de um tema instalado, a requisição usa esse tema; sem ele, nada muda. ?fw_tema=upwork-migracao grava o cookie; ?fw_tema=0 apaga. Não vai para produção.
 */

defined('ABSPATH') || exit;

$fw_local = PHP_SAPI === 'cli' || in_array(strtok($_SERVER['HTTP_HOST'] ?? '', ':'), ['localhost', '127.0.0.1'], true);
if (!$fw_local) return;

$fw_tema = PHP_SAPI === 'cli' ? (string) getenv('FW_TEMA') : sanitize_key(wp_unslash($_COOKIE['fw_tema'] ?? ''));
if (isset($_GET['fw_tema'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ferramenta local, só troca o tema da própria visita
    $fw_tema = sanitize_key(wp_unslash($_GET['fw_tema'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    setcookie('fw_tema', $fw_tema === '0' ? '' : $fw_tema, $fw_tema === '0' ? 1 : 0, '/');
}
if (!preg_match('/^[a-z0-9-]+$/', $fw_tema) || !is_dir(WP_CONTENT_DIR . "/themes/{$fw_tema}")) return;

add_filter('pre_option_stylesheet', fn() => $fw_tema);
add_filter('pre_option_template', fn() => $fw_tema);

// em produção o tema novo ocupa a pasta "upwork": aqui começa com as mesmas theme_mods (menus, logo)
if (get_option("theme_mods_{$fw_tema}", null) === null && is_array($fw_mods = get_option('theme_mods_upwork'))) {
    update_option("theme_mods_{$fw_tema}", $fw_mods);
}
