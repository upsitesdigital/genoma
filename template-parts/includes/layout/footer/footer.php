<?php
/**
 * Footer global — renderizado em todas as páginas pelo footer.php da raiz.
 * Menu: localização "Menu Rodapé" (footer); texto: Configuração do Tema.
 *
 * @var array $data (não usado: o footer monta os próprios dados)
 */

use Core\Admin\MenuApi;
use Core\Admin\ThemeOptions;

defined('ABSPATH') || exit;

$items = MenuApi::tree('footer');
// Opções do Tema (Fields::option) — apagado no painel → null, a linha some; aceita HTML de post (link, negrito).
// {ano}/{site} são trocados aqui, na hora (o texto salvo no banco não congela o ano)
/* translators: {ano} e {site} ficam como estão: viram o ano atual e o nome do site. */
$text = ThemeOptions::text('footer_text', __('© Copyright {ano}', 'upsites'));
if ($text) $text = strtr($text, ['{ano}' => wp_date('Y'), '{site}' => esc_html(get_bloginfo('name'))]);
?>
<footer class="border-t border-border bg-card">
  <div class="container flex flex-col gap-6 py-10 md:flex-row md:items-center md:justify-between">
    <?php if ($text) : ?>
      <div class="text-sm text-muted-foreground"><?= wp_kses_post($text) ?></div>
    <?php endif; ?>

    <?php if ($items) : ?>
      <nav aria-label="<?= esc_attr__('Menu do rodapé', 'upsites') ?>">
        <ul class="flex flex-wrap gap-x-6 gap-y-2">
          <?php foreach ($items as $item) : ?>
            <li>
              <a <?= fw_attrs(['href' => fw_url($item['url']), 'class' => 'text-sm text-muted-foreground hover:text-primary'] + fw_target($item['target'])) ?>><?= esc_html($item['title']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</footer>
