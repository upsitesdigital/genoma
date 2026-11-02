<?php
// Dump do tema ativo (FW_TEMA, ver fw-tema-local.php): definições dos campos ACF, referências do banco
// e valores de cada página. Uso: FW_TEMA=<pasta do tema> php verificar-campos.php <raiz do WP> <saida.json>
if (PHP_SAPI !== 'cli') exit(1); // fica dentro do web root: aberto via HTTP, não roda
if (count($argv) < 3) exit("uso: FW_TEMA=<pasta do tema> php verificar-campos.php <raiz do WP> <saida.json>\n");
require rtrim($argv[1], '/\\') . '/wp-load.php';
global $wpdb;

$out = ['tema' => get_stylesheet(), 'fields' => [], 'refs' => ['ok' => 0, 'faltando' => []], 'values' => []];

// 1. definições (sem abas, sem toggles novos), achatadas por key
$walk = function (array $f, string $parent) use (&$walk, &$out) {
    if ($f['type'] === 'tab') return;
    $def = $f;
    unset($def['sub_fields'], $def['layouts'], $def['ID'], $def['parent'], $def['prefix'], $def['_name'], $def['_valid'], $def['id'], $def['class'], $def['menu_order'], $def['value']);
    $out['fields'][$f['key']] = ['parent' => $parent] + $def;
    foreach ($f['sub_fields'] ?? [] as $s) $walk($s, $f['key']);
    foreach ($f['layouts'] ?? [] as $l) foreach ($l['sub_fields'] ?? [] as $s) $walk($s, $f['key'] . '>' . $l['name']);
};
foreach (acf_get_field_groups() as $g) {
    if (in_array($g['key'], ['group_fw_form', 'group_fw_submission', 'group_teste', 'group_opcoes'], true)) continue;
    foreach (acf_get_fields($g) as $f) $walk($f, '');
}

// páginas com modelo fw:
$pages = $wpdb->get_results("SELECT p.ID, m.meta_value tpl FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_wp_page_template' WHERE p.post_type='page' AND m.meta_value LIKE 'fw:%' AND m.meta_value <> 'fw:teste'");

// 2. referências _campo -> field_x gravadas no banco
foreach ($pages as $p) {
    foreach ($wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key LIKE %s AND meta_value LIKE %s", $p->ID, '\\_%', 'field\\_%')) as $r) {
        if (acf_get_field($r->meta_value)) $out['refs']['ok']++;
        else $out['refs']['faltando'][] = "{$p->ID} {$r->meta_key} -> {$r->meta_value}";
    }
}

// 3. valores formatados de todos os campos de primeiro nível, por página
foreach ($pages as $p) {
    foreach (acf_get_field_groups(['post_id' => (int) $p->ID]) as $g) {
        foreach (acf_get_fields($g) as $f) {
            if ($f['type'] === 'tab' || str_ends_with($f['name'], '_ocultar') && !isset($out['fields'][$f['key']])) continue;
            if (in_array($g['key'], ['group_fw_form', 'group_fw_submission'], true)) continue;
            $out['values'][$p->ID][$f['name']] = get_field($f['name'], (int) $p->ID);
        }
    }
    ksort($out['values'][$p->ID]);
}
file_put_contents($argv[2], json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "{$out['tema']}: " . count($out['fields']) . " campos, " . count($pages) . " páginas, refs ok={$out['refs']['ok']} faltando=" . count($out['refs']['faltando']) . "\n";
