<?php
/**
 * Header global — renderizado em todas as páginas pelo header.php da raiz, dentro de
 * #fw-header (que é o sticky top-0 z-40: o <header> sozinho teria a altura do wrapper e não grudaria).
 * Menu: Aparência → Menus → localização "Menu Principal" (primary). Logo e nome: Aparência → Personalizar.
 * Menu mobile (abrir/fechar, Esc) e submenus (1 nível, botão com aria-expanded; no desktop
 * também abre ao passar o mouse): header.store.js — store `upwork/layout-header`.
 *
 * @var array $data (não usado: o header monta os próprios dados)
 */

use Core\Admin\MenuApi;

defined('ABSPATH') || exit;

$items    = MenuApi::tree('primary');
$siteName = get_bloginfo('name');
$logoId   = (int) get_theme_mod('custom_logo');
// logo de Personalizar → Identidade do site: <img> do WP com width/height (sem CLS) e srcset
$logo     = $logoId ? wp_get_attachment_image($logoId, 'full', false, [
  'class'    => 'h-9 w-auto',
  'alt'      => $siteName,
  'loading'  => false, // acima da dobra
  'decoding' => 'async',
]) : '';

/** Um link do menu (item de 1º nível ou de submenu). */
$menuLink = static function (array $item, string $class = ''): void { ?>
  <a <?= fw_attrs([
    'href'         => fw_url($item['url']),
    'aria-current' => $item['current'] ? 'page' : null,
    'class'        => fw_cn('text-sm font-medium transition hover:text-primary', ($item['current'] || $item['ancestor']) ? 'text-primary' : '', $class),
  ] + fw_target($item['target'])) ?>><?= esc_html($item['title']) ?></a>
<?php };

/**
 * Lista de links do menu (desktop e mobile usam a mesma). Filhos (children do MenuApi::tree)
 * viram um nível de submenu: no desktop, dropdown aberto pelo botão (aria-expanded) ou pelo
 * mouse; no mobile, lista recuada sempre visível. Netos em diante não são exibidos.
 */
$menuList = static function (array $items, string $class, bool $mobile = false) use ($menuLink): void { ?>
  <ul class="<?= fw_cn($class) ?>">
    <?php foreach ($items as $item) : ?>
      <?php $children = $item['children'] ?? []; ?>
      <?php if (!$children) : ?>
        <li class="<?= fw_cn($item['classes']) ?>"><?php $menuLink($item); ?></li>
      <?php elseif ($mobile) : ?>
        <li class="<?= fw_cn($item['classes']) ?>">
          <?php $menuLink($item); ?>
          <ul class="mt-3 grid gap-3 border-l pl-4">
            <?php foreach ($children as $child) : ?>
              <li class="<?= fw_cn($child['classes']) ?>"><?php $menuLink($child); ?></li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php else : ?>
        <?php $subId = 'fw-submenu-' . (int) $item['id']; ?>
        <li
          class="<?= fw_cn('group relative flex items-center gap-1', $item['classes']) ?>"
          <?= wp_interactivity_data_wp_context(['sub' => false]) ?>
          data-wp-on-document--keydown="actions.closeSubOnEscape"
          data-wp-on-document--click="actions.closeSubOutside"
          data-wp-on--focusout="actions.closeSubOnFocusOut"
        >
          <?php $menuLink($item); ?>
          <button
            type="button"
            class="rounded-sm p-1 text-muted-foreground transition hover:text-primary focus-visible:outline-2 focus-visible:outline-primary"
            aria-controls="<?= esc_attr($subId) ?>"
            aria-expanded="false"
            data-wp-bind--aria-expanded="context.sub"
            data-wp-on--click="actions.toggleSub"
          >
            <span class="sr-only"><?php
              /* translators: %s: nome do item do menu. */
              echo esc_html(sprintf(__('Submenu de %s', 'upsites'), $item['title']));
            ?></span>
            <svg class="size-4 transition group-hover:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
          </button>
          <?php // pt-2 no wrapper (e não margem): o mouse atravessa o vão sem fechar o hover ?>
          <div id="<?= esc_attr($subId) ?>" class="absolute top-full left-0 z-50 hidden min-w-48 pt-2 group-hover:block" data-wp-class--hidden="!context.sub">
            <ul class="grid gap-1 rounded-md border bg-background p-2 shadow-lg">
              <?php foreach ($children as $child) : ?>
                <li class="<?= fw_cn($child['classes']) ?>"><?php $menuLink($child, 'block rounded-sm px-3 py-2 hover:bg-muted'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>
<?php };
?>
<header
  class="border-b border-border bg-background/90 backdrop-blur"
  data-wp-interactive="upwork/layout-header"
  <?= wp_interactivity_data_wp_context(['open' => false]) ?>
  data-wp-on-document--keydown="actions.closeOnEscape"
>
  <div class="container flex h-16 items-center justify-between gap-6">
    <a href="<?= esc_url(home_url('/')) ?>" class="flex items-center gap-2 font-bold" data-wp-on--click="actions.close">
      <?php if ($logo) : ?>
        <?= $logo // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- <img> do wp_get_attachment_image (atributos escapados pelo WP) ?>
      <?php else : ?>
        <?= esc_html($siteName) ?>
      <?php endif; ?>
    </a>

    <nav class="hidden md:block" aria-label="<?= esc_attr__('Menu principal', 'upsites') ?>">
      <?php $menuList($items, 'flex items-center gap-6'); ?>
    </nav>

    <button
      type="button"
      class="md:hidden"
      aria-label="<?= esc_attr__('Menu', 'upsites') ?>"
      aria-controls="fw-mobile-menu"
      aria-expanded="false"
      data-wp-bind--aria-expanded="context.open"
      data-wp-on--click="actions.toggle"
    >
      <span class="block h-0.5 w-6 bg-foreground"></span>
      <span class="mt-1.5 block h-0.5 w-6 bg-foreground"></span>
      <span class="mt-1.5 block h-0.5 w-6 bg-foreground"></span>
    </button>
  </div>

  <nav
    id="fw-mobile-menu"
    class="border-t border-border md:hidden"
    aria-label="<?= esc_attr__('Menu principal', 'upsites') ?>"
    hidden
    data-wp-bind--hidden="!context.open"
    data-wp-on--click="actions.close"
  >
    <?php $menuList($items, 'container grid gap-3 py-4', true); ?>
  </nav>
</header>
