<?php
/**
 * Header global — porte do Header.tsx do tema React. Renderizado em todas as páginas pelo
 * header.php da raiz, dentro de #fw-header, que no Genoma é FIXO (main.scss): fica fora do
 * fluxo e as seções do topo de cada página já têm o recuo dele.
 *
 * - Desktop: transparente no topo da página (desce 44px) e com fundo #ABC3CF depois de rolar
 *   (`context.scrolled` → data-scrolled). Páginas com o modelo padrão (body_class
 *   page-template-default) têm o fundo sempre pintado, como no React.
 * - Celular/tablet: barra branca + menu em tela cheia (trava a rolagem da página) com os CTAs na
 *   base; submenus em acordeão. Desktop: submenu em dropdown (mouse ou botão).
 * - Store `upwork/layout-header` (header.store.js): menu, submenus, Esc, foco preso, rolagem.
 * - `data-fw-header-bar` = a barra (93px / 84px): é a altura que o src/js/_header-offset.js usa
 *   no recuo das âncoras (sem os 44px do topo nem o menu mobile aberto).
 *
 * Dados (o header monta os próprios): Configuração do Tema → Geral `site_name`; Header `logo`
 * (sem logo → assets/diagnostico-veterinario-em-sp-04.svg), `cta_primary_label`/`cta_primary_url`
 * e `cta_secondary_label`/`cta_secondary_url` (nunca salvos → padrão; apagados → o botão some).
 * Menu: Aparência → Menus → localização "primary".
 *
 * @var array $data (não usado)
 */

use Core\Admin\MenuApi;
use Core\Admin\ThemeOptions;
use Core\Framework\Part;
use Core\Support\Media;

defined('ABSPATH') || exit;

$items    = MenuApi::tree('primary');
$siteName = ThemeOptions::text('site_name', 'Genoma Diagnósticos') ?? get_bloginfo('name');
$logo     = Media::imageData(ThemeOptions::value('logo')) ?? [
  'id'     => 0,
  'src'    => get_template_directory_uri() . '/template-parts/includes/layout/header/assets/diagnostico-veterinario-em-sp-04.svg',
  'alt'    => '',
  'width'  => 166,
  'height' => 84,
  'sizes'  => [],
];

// como no React: só link para fora do site abre em nova aba (o target do item vale só para ele)
$site       = untrailingslashit(home_url());
$isExternal = static fn(string $url): bool => str_starts_with($url, 'http') && !str_starts_with($url, $site);
// âncora solta (#servicos) é seção da home: fora dela, o link leva para a home na seção
$isHome = is_front_page();
$href   = static fn(string $url): string => (!$isHome && str_starts_with($url, '#')) ? home_url('/') . $url : $url;
// item ativo = a própria página (o React comparava a URL exata): link com âncora nunca é ativo
$isActive = static fn(array $item): bool => $item['current'] && !str_contains($item['url'], '#');

// CTAs: texto e link das Opções do Tema; sem um dos dois, o botão não aparece
$ctas = [];
foreach (['primary' => ['Resultados', '#'], 'secondary' => ['Contato', '#']] as $key => [$label, $url]) {
  $label = ThemeOptions::text("cta_{$key}_label", $label);
  $url   = ThemeOptions::text("cta_{$key}_url", $url);
  if ($label && $url) $ctas[$key] = ['title' => $label, 'url' => $url, 'target' => $isExternal($url) ? '_blank' : ''];
}
$ctaClass = [
  'primary'   => 'rounded-full bg-white px-6 py-4 text-base leading-none whitespace-nowrap text-primary hover:bg-brand-light-purple',
  'secondary' => 'rounded-full border border-white/25 bg-primary px-6 py-4 text-base leading-none whitespace-nowrap text-white hover:bg-brand-purple-dark active:bg-brand-purple-dark',
];

// fundo sempre pintado nas páginas com o modelo padrão (globals.css do React: .page-template-default header)
$painted = in_array('page-template-default', get_body_class(), true);

// chevron do submenu: gira com o submenu aberto (e, no desktop, com o mouse sobre o item)
$chevron = '<svg class="size-3.5 shrink-0 transition-transform group-hover:rotate-180" data-wp-class--rotate-180="context.sub" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>';

/** Link de um item do menu. */
$link = static function (array $item, string $class) use ($href, $isExternal, $isActive): void { ?>
  <a <?= fw_attrs([
    'href'         => fw_url($href($item['url'])),
    'aria-current' => $isActive($item) ? 'page' : null,
    'class'        => $class,
  ] + fw_target($isExternal($item['url']) ? $item['target'] : '')) ?>><?= esc_html($item['title']) ?></a>
<?php };

/** Botões de CTA (desktop: lado a lado; menu mobile: empilhados na base). */
$ctaLinks = static function () use ($ctas, $ctaClass): void {
  foreach ($ctas as $key => $cta) Part::render('shared/button', ['link' => $cta, 'class' => $ctaClass[$key]]);
};
?>
<header
  class="<?= fw_cn(
    'transition-[background-color,padding] duration-300 lg:pt-11 lg:data-[scrolled=true]:pt-0',
    $painted ? 'bg-[#ABC3CF]' : 'bg-white lg:bg-transparent lg:data-[scrolled=true]:bg-[#ABC3CF]'
  ) ?>"
  data-wp-interactive="upwork/layout-header"
  <?= wp_interactivity_data_wp_context(['open' => false, 'scrolled' => false]) ?>
  data-wp-bind--data-scrolled="context.scrolled"
  data-wp-init="callbacks.syncScroll"
  data-wp-watch="callbacks.lockScroll"
  data-wp-on-async-window--scroll="callbacks.syncScroll"
  data-wp-on-async-window--resize="callbacks.closeOnDesktop"
  data-wp-on-document--keydown="actions.onKeydown"
>
  <div class="container flex min-h-[93px] items-center justify-between gap-4 lg:h-[84px] lg:min-h-0" data-fw-header-bar>
    <a href="<?= esc_url(home_url('/')) ?>" class="flex shrink-0 items-center gap-2 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" data-wp-on--click="actions.close">
      <?= fw_img($logo, [
        'alt'     => $siteName,
        'class'   => 'h-auto w-[122px] object-contain lg:h-[84px] lg:w-[166px]',
        'loading' => false, // acima da dobra
      ], 'medium') ?>
    </a>

    <?php if ($items) : ?>
      <nav class="hidden lg:block" aria-label="<?= esc_attr__('Menu principal', 'upsites') ?>">
        <ul class="flex items-center gap-6 xl:gap-10 2xl:gap-14">
          <?php foreach ($items as $item) : ?>
            <?php $navClass = fw_cn('rounded-sm text-base font-semibold transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white', $isActive($item) ? 'text-white' : 'text-white/80'); ?>
            <?php if (!$item['children']) : ?>
              <li class="<?= fw_cn($item['classes']) ?>"><?php $link($item, $navClass); ?></li>
            <?php else : ?>
              <?php $subId = 'fw-submenu-' . (int) $item['id']; ?>
              <li
                class="<?= fw_cn('group relative', $item['classes']) ?>"
                <?= wp_interactivity_data_wp_context(['sub' => false]) ?>
                data-wp-on-document--keydown="actions.closeSubOnEscape"
                data-wp-on-document--click="actions.closeSubOutside"
                data-wp-on--focusout="actions.closeSubOnFocusOut"
              >
                <button
                  type="button"
                  class="<?= fw_cn('flex items-center gap-1', $navClass) ?>"
                  aria-controls="<?= esc_attr($subId) ?>"
                  aria-expanded="false"
                  data-wp-bind--aria-expanded="context.sub"
                  data-wp-on--click="actions.toggleSub"
                ><?= esc_html($item['title']) ?><?= $chevron // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>
                <?php // pt-2 no wrapper (e não margem, como no React): o mouse atravessa o vão sem fechar ?>
                <div id="<?= esc_attr($subId) ?>" class="absolute top-full left-0 z-50 hidden pt-2 group-hover:block" data-wp-class--hidden="!context.sub">
                  <ul class="min-w-[180px] rounded-md border border-border bg-background py-1 shadow-md">
                    <?php foreach ($item['children'] as $child) : ?>
                      <li class="<?= fw_cn($child['classes']) ?>"><?php $link($child, 'block px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:bg-muted focus-visible:text-foreground focus-visible:outline-hidden'); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <?php if ($ctas) : ?>
      <div class="hidden shrink-0 items-center gap-2 lg:flex"><?php $ctaLinks(); ?></div>
    <?php endif; ?>

    <button
      type="button"
      class="group flex size-10 flex-col items-center justify-center gap-[5px] rounded-full bg-brand-light-purple focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary lg:hidden"
      aria-controls="fw-mobile-menu"
      aria-expanded="false"
      data-wp-bind--aria-expanded="context.open"
      data-wp-on--click="actions.toggle"
    >
      <span class="sr-only" data-wp-bind--hidden="context.open"><?php esc_html_e('Abrir menu', 'upsites'); ?></span>
      <span class="sr-only" hidden data-wp-bind--hidden="!context.open"><?php esc_html_e('Fechar menu', 'upsites'); ?></span>
      <span class="block h-0.5 w-[18px] rounded-full bg-brand-purple-dark transition-transform group-aria-expanded:translate-y-[7px] group-aria-expanded:rotate-45" aria-hidden="true"></span>
      <span class="block h-0.5 w-[18px] rounded-full bg-brand-purple-dark transition-opacity group-aria-expanded:opacity-0" aria-hidden="true"></span>
      <span class="block h-0.5 w-[18px] rounded-full bg-brand-purple-dark transition-transform group-aria-expanded:-translate-y-[7px] group-aria-expanded:-rotate-45" aria-hidden="true"></span>
    </button>
  </div>

  <?php // menu mobile/tablet: ocupa o resto da tela abaixo da barra (93px); dvh desconta a barra do navegador ?>
  <div
    id="fw-mobile-menu"
    class="h-[calc(100vh-93px)] overflow-y-auto border-t border-white/15 bg-primary supports-[height:100dvh]:h-[calc(100dvh-93px)] lg:hidden"
    hidden
    data-wp-bind--hidden="!context.open"
  >
    <nav class="container flex min-h-full flex-col gap-1 py-4" aria-label="<?= esc_attr__('Menu principal', 'upsites') ?>" data-wp-on--click="actions.closeOnLink">
      <?php if ($items) : ?>
        <ul class="flex flex-col gap-1">
          <?php foreach ($items as $item) : ?>
            <?php if (!$item['children']) : ?>
              <li class="<?= fw_cn($item['classes']) ?>"><?php $link($item, 'block py-2 text-base font-semibold text-white/90 hover:text-white focus-visible:outline-2 focus-visible:outline-white'); ?></li>
            <?php else : ?>
              <?php $subId = 'fw-submenu-m-' . (int) $item['id']; ?>
              <li class="<?= fw_cn($item['classes']) ?>" <?= wp_interactivity_data_wp_context(['sub' => false]) ?>>
                <button
                  type="button"
                  class="flex w-full items-center justify-between py-2 text-base font-semibold text-white/90 focus-visible:outline-2 focus-visible:outline-white"
                  aria-controls="<?= esc_attr($subId) ?>"
                  aria-expanded="false"
                  data-wp-bind--aria-expanded="context.sub"
                  data-wp-on--click="actions.toggleSub"
                ><?= esc_html($item['title']) ?><?= $chevron // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>
                <ul id="<?= esc_attr($subId) ?>" class="flex flex-col gap-1 pl-4" hidden data-wp-bind--hidden="!context.sub">
                  <?php foreach ($item['children'] as $child) : ?>
                    <li class="<?= fw_cn($child['classes']) ?>"><?php $link($child, 'block py-2 text-sm text-white/70 hover:text-white focus-visible:outline-2 focus-visible:outline-white'); ?></li>
                  <?php endforeach; ?>
                </ul>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($ctas) : ?>
        <?php // fixados na base do menu (mt-auto) ?>
        <div class="mt-auto flex flex-col gap-2 pt-8 pb-4"><?php $ctaLinks(); ?></div>
      <?php endif; ?>
    </nav>
  </div>
</header>
