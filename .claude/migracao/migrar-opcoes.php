<?php
/**
 * Migração das Opções do Tema pelo terminal — o mesmo ThemeOptions::migrateLegacy() que roda
 * ao abrir o wp-admin, executado como o primeiro administrador. Faça backup de wp_options antes.
 *
 *   FW_TEMA=upwork-migracao php .claude/migracao/migrar-opcoes.php <raiz do WP>
 *
 * Em homologação/produção, com o tema novo já ativo, o FW_TEMA não é necessário.
 * Roda uma vez só (flag upwork_theme_options_migrado); imprime as opções gravadas.
 */
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require rtrim($argv[1], '/\\') . '/wp-load.php';

echo 'tema: ', get_stylesheet(), PHP_EOL;
$admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) $admin[0]);

\Core\Admin\ThemeOptions::migrateLegacy();

echo 'migrado: ', var_export(get_option('upwork_theme_options_migrado'), true), PHP_EOL;
global $wpdb;
foreach ($wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'options\\_%' AND option_name NOT LIKE 'options\\_\\_%' ORDER BY option_name") as $row) {
    echo str_pad($row->option_name, 40), mb_substr($row->option_value, 0, 70), PHP_EOL;
}
