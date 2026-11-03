<?php
/**
 * Footer global — porte do Footer.tsx do tema React (resources/components/layout/). Renderizado em
 * todas as páginas pelo footer.php da raiz, dentro de #fw-footer.
 *
 * - Banner de CTA no topo. Celular/tablet: faixa lilás com foto, texto e botões empilhados.
 *   Desktop: foto + card branco que avançam 100px sobre a seção anterior (lg:-mt-[100px] dentro
 *   do <footer> com lg:mt-[100px]). O `flow-root` do <footer> impede que as duas margens se anulem:
 *   no React o <footer> era item flex do Layout, que já isola as margens; aqui o item flex é o #fw-footer.
 * - Barra: logo, menu do rodapé e botão Contato (desktop); no celular, frase e contatos no lugar.
 * - Última linha: copyright, direitos (só celular), política de privacidade e créditos (só desktop).
 * - Sem animação de entrada: o reveal do v3 ignora #fw-footer (o React animava título, imagens e botões).
 *
 * Banner — o mesmo cálculo do React (useFooterCtaOverride + Opções do Tema):
 * - página com a aba "Rodapé" no modelo (home, o-genoma, veterinarios, responsavel — campos
 *   footer_cta_* da página): título, textos e links preenchidos ali valem só nela; vazios (ou página
 *   sem a aba) → Configuração do Tema → Banner CTA (footer_cta_title, footer_cta_primary_*…) → padrão;
 * - "Mostrar Botão Secundário" desligado na página → só o botão principal, no estilo preenchido
 *   (com dois, o principal é o contornado); nunca salvo → ligado, como no React;
 * - "Ocultar seção" da aba Rodapé marcado → o banner some nesta página (toggle novo do v3);
 * - texto acima do título e foto: só da Configuração do Tema. Sem foto → assets/…-02.png no desktop e
 *   o recorte mais alto …-01.webp no celular/tablet; com foto, ela vale em todas as larguras.
 * - Opção nunca salva → o padrão do React; apagada no painel → o elemento some (botão sem texto ou
 *   sem link também).
 *
 * Demais dados (Configuração do Tema): Geral `site_name` (alt do logo); Header `logo` (se o do rodapé
 * ficar vazio; sem os dois → assets/…-04.svg) e `cta_secondary_label`/`cta_secondary_url` (botão
 * Contato); Rodapé `footer_logo`, `footer_text` ({ano}/{site}), `footer_privacy_label`/`_url` (abre
 * em nova aba, como no React), `footer_credits_text`, `footer_tagline`, `footer_email`, `footer_phone`
 * e `footer_rights_text`. Menu: Aparência → Menus → localização "footer" (só o 1º nível, como no React).
 * Redes sociais (Rodapé `rodape_redes`, novo no v3 — o React não tinha): shared/social-links abaixo
 * do logo, em todas as larguras (também no menu mobile do header).
 *
 * @var array $data (não usado: o footer monta os próprios dados)
 */

use Core\Admin\MenuApi;
use Core\Admin\ThemeOptions;
use Core\Framework\Part;
use Core\Framework\RouteResolver;
use Core\Support\Media;

defined('ABSPATH') || exit;

$assets   = get_template_directory_uri() . '/template-parts/includes/layout/footer/assets/';
$items    = MenuApi::tree('footer');
$siteName = ThemeOptions::text('site_name', 'Genoma Diagnósticos') ?? get_bloginfo('name');
$logo     = Media::imageData(ThemeOptions::value('footer_logo')) ?? Media::imageData(ThemeOptions::value('logo')) ?? [
  'id'     => 0,
  'src'    => $assets . 'diagnostico-veterinario-em-sp-04.svg',
  'alt'    => '',
  'width'  => 166,
  'height' => 84,
  'sizes'  => [],
];

// como no React: só link para fora do site abre em nova aba (o target do item vale só para ele)
$site       = untrailingslashit(home_url());
$isExternal = static fn(string $url): bool => str_starts_with($url, 'http') && !str_starts_with($url, $site);
// âncora solta (#servicos) é seção da home: fora dela, o link leva para a home na seção (igual ao header)
$isHome = is_front_page();
$href   = static fn(string $url): string => (!$isHome && str_starts_with($url, '#')) ? home_url('/') . $url : $url;

/** Link {title, url, target} para o shared/button; sem texto ou sem URL segura → null (o botão some). */
$button = static function (?string $label, ?string $url) use ($isExternal): ?array {
  $url = fw_url($url);
  return $label && $url ? ['title' => $label, 'url' => $url, 'target' => $isExternal($url) ? '_blank' : ''] : null;
};

// ── Banner de CTA ────────────────────────────────────────────────────────────
// Aba "Rodapé" da página atual: só nas páginas de módulo cujo modelo a tem (o Fields::section dela
// cria o toggle field_{módulo}_footer_cta_ocultar). Os campos são lidos pelo name, como no React.
$route    = RouteResolver::current();
$pageId   = (int) ($route['pageId'] ?? 0);
$toggle   = $route ? 'field_' . str_replace('-', '_', $route['module']) . '_footer_cta_ocultar' : '';
// página com senha ainda não digitada: nada dela no rodapé (o React mostrava os padrões — a REST dava 404)
$hasTab   = $pageId > 0 && !post_password_required($pageId) && function_exists('acf_get_field') && acf_get_field($toggle);
$fromPage = static fn(string $name): mixed => $hasTab ? get_field($name, $pageId) : null;
// texto da página: vazio ou nunca salvo → null (cai na Configuração do Tema)
$pageText = static function (string $name) use ($fromPage): ?string {
  $value = $fromPage($name);
  return is_string($value) && trim($value) !== '' ? $value : null;
};

$banner   = !($hasTab && $fromPage('footer_cta_ocultar'));
$ctaImage = Media::imageData(ThemeOptions::value('footer_cta_image'));
$overline = ThemeOptions::text('footer_cta_overline', 'Fale conosco');
$title    = $pageText('footer_cta_titulo') ?? ThemeOptions::text('footer_cta_title', 'Cuidado começa com diagnóstico preciso.');
// cada linha do título vira um bloco (React: title.split('\n'))
$lines   = $title ? preg_split('/\r\n|\r|\n/', $title) : [];
$primary = $button(
  $pageText('footer_cta_primario_texto') ?? ThemeOptions::text('footer_cta_primary_label', 'Agendar Exame'),
  $pageText('footer_cta_primario_link') ?? ThemeOptions::text('footer_cta_primary_url', '#')
);
$secondary = ($fromPage('footer_cta_mostrar_secundario') ?? true) ? $button(
  $pageText('footer_cta_secundario_texto') ?? ThemeOptions::text('footer_cta_secondary_label', 'Fale Conosco'),
  $pageText('footer_cta_secundario_link') ?? ThemeOptions::text('footer_cta_secondary_url', '#')
) : null;
$ctaButton = 'w-full rounded-full px-6 py-4 font-sans text-[15px] leading-[1.5] font-semibold whitespace-nowrap lg:w-auto lg:px-7 lg:py-5 lg:text-h5 lg:leading-none lg:font-medium';
$ctaStyle  = [
  'outline' => 'border border-primary text-primary hover:bg-primary hover:text-white',
  'filled'  => 'border border-white bg-primary text-white hover:bg-brand-purple-dark',
];

// ── Barra e última linha ─────────────────────────────────────────────────────
$contato = $button(ThemeOptions::text('cta_secondary_label', 'Contato'), ThemeOptions::text('cta_secondary_url', '#'));
$tagline = ThemeOptions::text('footer_tagline', 'Diagnóstico Veterinário com Precisão, Agilidade e Confiança.');
$email   = sanitize_email((string) ThemeOptions::text('footer_email'));
$phone   = ThemeOptions::text('footer_phone');
$tel     = $phone ? preg_replace('/\D/', '', $phone) : '';
// Opções do Tema (Fields::option) — apagado no painel → null, a linha some; aceita HTML de post (link, negrito).
// {ano}/{site} são trocados aqui, na hora (o texto salvo no banco não congela o ano)
/* translators: {ano} e {site} ficam como estão: viram o ano atual e o nome do site. */
$copyright = ThemeOptions::text('footer_text', __('© Copyright {ano}', 'upsites'));
if ($copyright) $copyright = strtr($copyright, ['{ano}' => wp_date('Y'), '{site}' => esc_html(get_bloginfo('name'))]);
$rights  = ThemeOptions::text('footer_rights_text', 'Todos os direitos reservados.');
$privacy = fw_url(ThemeOptions::text('footer_privacy_url', '#'));
$privacyLabel = ThemeOptions::text('footer_privacy_label', 'Política de privacidade');
$credits = ThemeOptions::text('footer_credits_text', 'Desenvolvido por Upsites');
?>
<footer class="<?= fw_cn('relative flow-root bg-brand-purple-dark lg:bg-primary', ['lg:mt-[100px]' => $banner]) ?>">
  <?php if ($banner) : ?>
    <div class="bg-brand-light-purple py-12 lg:bg-transparent lg:py-0">
      <div class="container flex flex-col gap-6 lg:-mt-[100px] lg:block">
        <div class="relative overflow-hidden rounded-[5px] lg:mb-1 lg:rounded-2xl">
          <?php if ($ctaImage) : ?>
            <?= fw_img($ctaImage, [
              'alt'   => '',
              'class' => 'h-[215px] w-full object-cover lg:h-[345px]',
              'sizes' => '(min-width: 1400px) 1360px, calc(100vw - 40px)',
            ], 'full') ?>
          <?php else : ?>
            <?php // celular/tablet: recorte mais alto da mesma foto (Figma 353×215) ?>
            <picture>
              <source media="(min-width: 1024px)" srcset="<?= esc_url($assets . 'diagnostico-veterinario-em-sp-02.png') ?>" width="1222" height="345">
              <?= fw_img(['src' => $assets . 'diagnostico-veterinario-em-sp-01.webp', 'width' => 1060, 'height' => 454, 'alt' => ''], [
                'class' => 'h-[215px] w-full object-cover lg:h-[345px]',
              ]) ?>
            </picture>
          <?php endif; ?>
          <?php // marca d'água "GENOMA" sobre a foto ?>
          <?= fw_img(['src' => $assets . 'diagnostico-veterinario-em-sp-03.svg', 'width' => 485, 'height' => 89, 'alt' => ''], [
            'aria-hidden' => 'true',
            'class'       => 'pointer-events-none absolute bottom-[5px] left-[14px] w-[207px] lg:bottom-0 lg:left-10 lg:w-[420px] lg:opacity-70',
          ]) ?>
        </div>

        <div class="flex flex-col gap-6 lg:relative lg:z-10 lg:flex-row lg:items-end lg:justify-between lg:gap-6 lg:rounded-2xl lg:bg-white lg:p-10 lg:shadow-lg">
          <?php if ($overline || $lines) : ?>
            <div class="flex max-w-xl flex-col gap-3 lg:gap-2">
              <?php if ($overline) : ?>
                <span class="text-eyebrow tracking-[1.5px] text-brand-purple-accent uppercase lg:text-body lg:tracking-normal lg:text-accent lg:normal-case"><?= esc_html($overline) ?></span>
              <?php endif; ?>
              <?php if ($lines) : ?>
                <?php // lg:font-normal: no Tailwind v3 o lg:text-h2 trazia o peso 400 junto ?>
                <h2 class="font-heading text-[24px] leading-[1.3] font-medium text-brand-purple-dark lg:text-h2 lg:font-normal">
                  <?php foreach ($lines as $line) : ?>
                    <span class="block"><?= esc_html($line) ?></span>
                  <?php endforeach; ?>
                </h2>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($primary || $secondary) : ?>
            <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center lg:gap-4">
              <?php
              // com um botão só, o Figma usa o estilo preenchido — o contornado só diferencia do preenchido quando há dois
              Part::render('shared/button', ['link' => $primary, 'class' => fw_cn($ctaButton, $ctaStyle[$secondary ? 'outline' : 'filled'])]);
              Part::render('shared/button', ['link' => $secondary, 'class' => fw_cn($ctaButton, $ctaStyle['filled'])]);
              ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php // Barra: logo, menu e botão Contato (desktop); no celular/tablet, frase e contatos ?>
  <div class="container flex flex-col items-center gap-6 pt-12 pb-8 lg:flex-row lg:items-center lg:justify-between lg:gap-8 lg:py-12">
    <div class="flex shrink-0 flex-col items-center gap-4 lg:items-start">
      <a href="<?= esc_url(home_url('/')) ?>" class="rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
        <?= fw_img($logo, [
          'alt'   => $siteName,
          'class' => 'h-[84px] w-[166px] object-contain',
        ], 'medium') ?>
      </a>

      <?php // abaixo do logo; no desktop, alinhado à esquerda com ele (sem rede preenchida, não aparece) ?>
      <?php Part::render('shared/social-links', ['class' => 'justify-center lg:justify-start']); ?>
    </div>

    <?php if ($tagline) : ?>
      <p class="text-center font-sans text-[13px] leading-[1.5] whitespace-pre-line text-brand-gray-border lg:hidden"><?= esc_html($tagline) ?></p>
    <?php endif; ?>

    <?php if ($email || $tel) : ?>
      <div class="flex flex-col items-center gap-3 font-sans text-xs leading-[1.5] text-white lg:hidden">
        <?php if ($email) : ?>
          <a href="<?= esc_url('mailto:' . $email) ?>" class="rounded-sm hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"><?= esc_html($email) ?></a>
        <?php endif; ?>
        <?php if ($tel) : ?>
          <a href="<?= esc_url('tel:' . $tel) ?>" class="rounded-sm hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"><?= esc_html($phone) ?></a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($items) : ?>
      <nav class="hidden lg:block" aria-label="<?= esc_attr__('Menu do rodapé', 'upsites') ?>">
        <ul class="flex flex-wrap items-center justify-center gap-10">
          <?php foreach ($items as $item) : ?>
            <li class="<?= fw_cn($item['classes']) ?>">
              <a <?= fw_attrs([
                'href'         => fw_url($href($item['url'])),
                'aria-current' => $item['current'] && !str_contains($item['url'], '#') ? 'page' : null,
                'class'        => 'rounded-sm text-sm font-semibold whitespace-nowrap text-white/85 transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white md:text-base',
              ] + fw_target($isExternal($item['url']) ? $item['target'] : '')) ?>><?= esc_html($item['title']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <?php if ($contato) : ?>
      <div class="hidden lg:block">
        <?php Part::render('shared/button', ['link' => $contato, 'class' => 'shrink-0 rounded-full border border-white/60 px-6 py-4 text-body whitespace-nowrap text-white hover:bg-white/10']); ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="container">
    <div class="h-px w-full bg-white/20"></div>
  </div>

  <?php // Última linha: copyright, direitos (celular), política de privacidade e créditos (desktop) ?>
  <div class="container flex flex-col items-center gap-2 pt-8 pb-6 text-center font-sans text-xs leading-[1.5] text-brand-gray-border lg:flex-row lg:justify-between lg:gap-3 lg:py-6 lg:text-left lg:text-body-sm lg:text-white/85">
    <?php if ($copyright) : ?>
      <span><?= wp_kses_post($copyright) ?></span>
    <?php endif; ?>
    <?php if ($rights) : ?>
      <span class="lg:hidden"><?= esc_html($rights) ?></span>
    <?php endif; ?>
    <?php if ($privacy && $privacyLabel) : ?>
      <a <?= fw_attrs([
        'href'  => $privacy,
        'class' => 'rounded-sm transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
      ] + fw_target('_blank')) ?>><?= esc_html($privacyLabel) ?></a>
    <?php endif; ?>
    <?php if ($credits) : ?>
      <a <?= fw_attrs([
        'href'  => 'https://upsites.digital/',
        'class' => 'hidden rounded-sm text-white/70 transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:inline',
      ] + fw_target('_blank')) ?>><?= esc_html($credits) ?></a>
    <?php endif; ?>
  </div>
</footer>
