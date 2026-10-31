<?php
/**
 * Hero — carrossel de slides (React: HeroCarousel em app/home/home.view.tsx; <section id="hero">).
 * Dados: HomeController → 'hero' (null = "Ocultar seção" → nem chega aqui).
 *
 * - slides vazio → a seção não aparece (como no React); setas e bolinhas só com 2+ slides.
 * - eyebrow é o <h1> no 1º slide e <p> nos demais; titulo é <h2>.
 * - imagemMobile null → usa imagemMobilePadrao (imagem do tema) com object-fill; com imagem do
 *   editor, object-cover object-bottom (como no React).
 * - CTA: texto null → o botão não aparece; link null → href "#" (como no React).
 * - intervalo: aplique o mínimo de 2000 ms no store (React: Math.max(intervalo, 2000)).
 * - Ondas decorativas (não são campo): assets/diagnostico-veterinario-em-sp-16.svg (desktop) e -17.svg (mobile).
 * - Store `upwork/home-hero` (hero.store.js): setas, bolinhas, swipe e autoplay (pausa com o mouse
 *   ou o foco no carrossel; desligado com prefers-reduced-motion). Todos os slides saem no HTML;
 *   o ativo é `context.index` e cada slide tem o seu `context.i` (state.isActive).
 *
 * @var array{
 *   slides: list<array{
 *     imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     imagemMobile: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     eyebrow: string|null,
 *     titulo: string|null,
 *     subtitulo: string|null,
 *     ctaPrimario: array{texto: string|null, link: string|null},
 *     ctaSecundario: array{texto: string|null, link: string|null},
 *   }>,
 *   autoplay: bool,
 *   intervalo: int,
 *   imagemMobilePadrao: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;

$slides = $data['slides'] ?? [];
if (!$slides) return;

$total  = count($slides);
$assets = get_template_directory_uri() . '/template-parts/includes/home/hero/assets/';

// slide ativo no HTML do servidor (data-wp-bind/class) — o mesmo getter do hero.store.js
wp_interactivity_state('upwork/home-hero', [
  'isActive' => static function (): bool {
    $ctx = wp_interactivity_get_context();
    return ($ctx['i'] ?? 0) === ($ctx['index'] ?? 0);
  },
]);

$ctaClass = [
  'ctaPrimario'   => 'inline-flex w-full items-center justify-center rounded-full bg-brand-purple-accent px-6 py-4 font-sans text-[15px] leading-[1.5] font-semibold text-white transition-colors hover:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:w-auto lg:bg-primary lg:px-7 lg:py-5 lg:font-heading lg:text-h5 lg:leading-none lg:font-normal lg:hover:bg-brand-purple-dark',
  'ctaSecundario' => 'inline-flex w-full items-center justify-center rounded-full bg-white px-6 py-4 font-sans text-[15px] leading-[1.5] font-semibold text-[#29139A] transition-colors hover:bg-primary hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:w-auto lg:px-7 lg:py-5 lg:font-heading lg:text-h5 lg:leading-none lg:font-normal lg:text-primary',
];
// ChevronLeft/ChevronRight (lucide) do React
$arrow = '<svg class="size-5 text-primary sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%s"/></svg>';
?>
<section
  id="hero"
  class="relative isolate flex min-h-[842px] w-full items-start overflow-hidden bg-[#AAC1CD] pt-[186px] lg:min-h-[720px] lg:items-center lg:overflow-visible lg:bg-transparent lg:py-24 xl:min-h-[797px]"
  aria-roledescription="carousel"
  aria-label="<?php esc_attr_e('Destaques', 'upsites'); ?>"
  data-wp-interactive="upwork/home-hero"
  <?= wp_interactivity_data_wp_context([
    'index'     => 0,
    'total'     => $total,
    'autoplay'  => (bool) $data['autoplay'],
    'intervalo' => (int) $data['intervalo'],
    'paused'    => false,
  ]) ?>
  data-wp-init="callbacks.swipe"
  data-wp-watch="callbacks.autoplay"
  data-wp-on--mouseenter="actions.pause"
  data-wp-on--mouseleave="actions.resume"
  data-wp-on--focusin="actions.pause"
  data-wp-on--focusout="actions.resumeOnFocusOut"
>
  <?php // Slides (imagem de fundo + overlay): todos no HTML, o ativo visível. Sem lazy (como no React):
        // a imagem do outro breakpoint é display:none e, lazy, nunca carregaria ?>
  <?php foreach ($slides as $i => $slide) :
    $mobile = $slide['imagemMobile'] ?? $data['imagemMobilePadrao'];
    $first  = $i === 0;
    ?>
    <div
      <?= wp_interactivity_data_wp_context(['i' => $i]) ?>
      class="<?= fw_cn('absolute inset-0 transition-opacity duration-700 ease-in-out motion-reduce:transition-none', $first ? 'opacity-100' : 'pointer-events-none opacity-0') ?>"
      data-wp-class--opacity-100="state.isActive"
      data-wp-class--opacity-0="!state.isActive"
      data-wp-class--pointer-events-none="!state.isActive"
      data-wp-bind--aria-hidden="!state.isActive"
    >
      <?= fw_img($slide['imagem'], [
        'class'         => 'relative z-[1] hidden h-full w-full object-cover lg:block',
        'sizes'         => '100vw',
        'loading'       => false,
        'fetchpriority' => $first ? 'high' : 'low',
      ], 'full') ?>
      <div class="absolute inset-0 hidden bg-[linear-gradient(295.78deg,#95ABB7_54.95%,#AEC6D3_100.42%)] lg:block"></div>

      <?php // Mobile/tablet: imagem recortada na base do slide (Figma 393×459) ?>
      <?= fw_img($mobile, [
        'alt'           => $slide['imagemMobile']['alt'] ?? '',
        'class'         => fw_cn('absolute inset-x-0 bottom-0 aspect-[393/459] w-full lg:hidden', $slide['imagemMobile'] ? 'object-cover object-bottom' : 'object-fill'),
        'sizes'         => '100vw',
        'loading'       => false,
        'fetchpriority' => $first ? null : 'low',
      ]) ?>
    </div>
  <?php endforeach; ?>

  <?php // Conteúdo: um bloco por slide, só o ativo visível ?>
  <div class="container relative z-10">
    <?php foreach ($slides as $i => $slide) :
      $first  = $i === 0;
      $tag    = $first ? 'h1' : 'p'; // eyebrow: h1 no 1º slide, p nos demais
      $reveal = $first ? true : null; // entrada só no 1º slide (o que aparece no carregamento)
      $ctas   = array_filter(
        ['ctaPrimario' => $slide['ctaPrimario'], 'ctaSecundario' => $slide['ctaSecundario']],
        static fn(array $cta): bool => (bool) $cta['texto']
      );
      ?>
      <div
        <?= wp_interactivity_data_wp_context(['i' => $i]) ?>
        class="text-left lg:max-w-2xl"
        role="group"
        aria-roledescription="slide"
        aria-label="<?= esc_attr(sprintf(/* translators: 1: número do slide, 2: total de slides */ __('%1$d de %2$d', 'upsites'), $i + 1, $total)) ?>"
        data-wp-bind--hidden="!state.isActive"
        <?= $first ? '' : 'hidden' ?>
      >
        <?php if ($slide['eyebrow']) : ?>
          <<?= tag_escape($tag) ?> <?= fw_attrs(['data-reveal' => $reveal, 'class' => 'font-sans text-body text-white']) ?>><?= esc_html($slide['eyebrow']) ?></<?= tag_escape($tag) ?>>
        <?php endif; ?>

        <?php if ($slide['titulo']) : ?>
          <h2 <?= fw_attrs(['data-reveal' => $reveal, 'class' => 'mt-4 font-heading text-[28px] leading-[1.25] font-semibold text-white lg:mt-6 lg:text-5xl lg:leading-tight lg:font-medium xl:text-h1 xl:leading-[1.15]']) ?>><?= esc_html($slide['titulo']) ?></h2>
        <?php endif; ?>

        <?php if ($slide['subtitulo']) : ?>
          <p <?= fw_attrs(['data-reveal' => $reveal, 'class' => 'mt-4 font-sans text-[15px] leading-[1.5] text-white lg:mt-6 lg:max-w-lg lg:font-heading lg:text-h4 lg:leading-[1.4] lg:font-medium lg:text-white/90']) ?>><?= esc_html($slide['subtitulo']) ?></p>
        <?php endif; ?>

        <?php if ($ctas) : ?>
          <div class="mt-6 flex flex-col items-stretch gap-3 lg:mt-10 lg:flex-row lg:flex-wrap lg:items-center lg:gap-4">
            <?php foreach ($ctas as $key => $cta) : ?>
              <a <?= fw_attrs(['href' => fw_url($cta['link']) ?? '#', 'data-reveal' => $reveal, 'class' => $ctaClass[$key]]) ?>><?= esc_html($cta['texto']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php // Setas + indicadores (só com mais de 1 slide) ?>
  <?php if ($total > 1) : ?>
    <button
      type="button"
      class="absolute top-1/2 left-10 z-20 hidden size-[58px] -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition hover:bg-white/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:flex"
      aria-label="<?php esc_attr_e('Slide anterior', 'upsites'); ?>"
      data-wp-on--click="actions.prev"
    ><?= sprintf($arrow, 'm15 18-6-6 6-6') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>
    <button
      type="button"
      class="absolute top-1/2 right-10 z-20 hidden size-[58px] -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition hover:bg-white/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:flex"
      aria-label="<?php esc_attr_e('Próximo slide', 'upsites'); ?>"
      data-wp-on--click="actions.next"
    ><?= sprintf($arrow, 'm9 18 6-6-6-6') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>

    <div class="absolute inset-x-0 bottom-14 z-20 flex justify-center gap-2 lg:bottom-6">
      <?php foreach ($slides as $i => $slide) : ?>
        <button
          type="button"
          <?= wp_interactivity_data_wp_context(['i' => $i]) ?>
          class="h-2 w-2 rounded-full bg-white/50 transition-all hover:bg-white/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white aria-[current=true]:w-6 aria-[current=true]:bg-white"
          aria-label="<?= esc_attr(sprintf(/* translators: %d: número do slide */ __('Ir para o slide %d', 'upsites'), $i + 1)) ?>"
          aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
          data-wp-bind--aria-current="state.isActive"
          data-wp-on--click="actions.goTo"
        ></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php // Onda decorativa inferior (mobile / desktop) ?>
  <?= fw_img(['src' => $assets . 'diagnostico-veterinario-em-sp-17.svg', 'width' => 414, 'height' => 34, 'alt' => ''], [
    'aria-hidden' => 'true',
    'loading'     => false,
    'class'       => 'pointer-events-none absolute -bottom-px left-[-2.54%] z-10 w-[105.26%] max-w-none lg:hidden',
  ]) ?>
  <?= fw_img(['src' => $assets . 'diagnostico-veterinario-em-sp-16.svg', 'width' => 1426, 'height' => 53, 'alt' => ''], [
    'aria-hidden' => 'true',
    'loading'     => false,
    'class'       => 'pointer-events-none absolute inset-x-0 bottom-[-20px] z-10 mx-auto hidden w-full max-w-[1426px] lg:block',
  ]) ?>
</section>
