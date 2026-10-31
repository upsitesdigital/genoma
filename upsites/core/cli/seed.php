<?php
/**
 * Passa o conteúdo padrão (Figma) para o painel nas páginas de modelo que já existem.
 *
 *   npm run seed                 → prévia (não grava nada)
 *   npm run seed -- --apply      → grava nos campos nunca salvos
 *   npm run seed -- --apply --vazios → também nos salvos vazios (migração de projeto antigo)
 *   npm run seed -- --page=12    → só uma página
 *
 * Campo com conteúdo do editor nunca é tocado. Mesma regra do Core\Admin\Seeder
 * (que já roda sozinho ao abrir/salvar a página no editor).
 */
declare(strict_types=1);

// só linha de comando: pela web (o arquivo fica dentro do tema) não faz nada
if (PHP_SAPI !== 'cli') exit(1);

// argumentos numa constante: o wp-load.php roda em escopo global (plugins podem sobrescrever variáveis)
define('UPWORK_SEED_ARGS', array_slice($argv, 1));

// sobe a partir do tema até achar o WordPress
$dir = __DIR__;
while ($dir !== dirname($dir) && !is_file("{$dir}/wp-load.php")) $dir = dirname($dir);
if (!is_file("{$dir}/wp-load.php")) { fwrite(STDERR, "wp-load.php não encontrado acima de " . __DIR__ . "\n"); exit(1); }

define('WP_ADMIN', true); // imagens padrão são importadas para a biblioteca só no admin
$_SERVER['HTTP_HOST'] ??= 'localhost';
require "{$dir}/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/admin.php';

(static function (array $args): void {
    $apply = in_array('--apply', $args, true);
    $empty = in_array('--vazios', $args, true);
    $page  = array_values(preg_grep('/^--page=\d+$/', $args) ?: []);
    $only  = $page ? (int) substr($page[0], 7) : 0;

    $admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    if ($admin) wp_set_current_user((int) $admin[0]);

    $pages = get_posts(['post_type' => 'page', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids']);
    $total = 0;
    foreach ($pages as $id) {
        if (($only && $id !== $only) || !Core\Admin\ModelScreen::isModel($id)) continue;
        $_GET['post'] = $id; // filtros de padrão por modelo (ex.: componentes compartilhados) leem a página em edição
        $done = Core\Admin\Seeder::seed($id, $empty, !$apply);
        foreach ($done as $name => $desc) printf("%-24s %-48s → %s\n", get_the_title($id), $name, $desc);
        $total += count($done);
    }

    echo "\n{$total} campo(s) " . ($apply ? 'gravados no painel.' : 'seriam gravados — rode com --apply.') . "\n";
    if ($apply && $total) Core\Framework\Rest::flushCache();
})(UPWORK_SEED_ARGS);
