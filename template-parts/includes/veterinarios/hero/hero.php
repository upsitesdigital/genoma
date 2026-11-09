<?php
/**
 * Hero — carrossel de slides (React: HeroCarousel + SlideImage + SlideContent em
 * app/veterinarios/veterinarios.view.tsx; <section id="hero">).
 * Dados: VeterinariosController → 'hero' (null = "Ocultar seção" → nem chega aqui).
 *
 * - slides vazio → a seção não aparece (como no React); setas e bolinhas só com 2+ slides.
 * - Slides empilhados como no React: cada um é `absolute inset-0` (foto + texto) e a seção tem só a
 *   altura mínima; o ativo fica opaco e os outros transparentes por cima (troca em fade). Os
 *   inativos também ficam `inert` (no React os links deles continuavam no Tab).
 * - Foto: no celular cobre a seção inteira, com um véu roxo à esquerda para o texto; do lg em
 *   diante ocupa dos 40% à direita, com a borda esquerda dissolvida no degradê (mask-image).
 * - eyebrow é o <h1> no 1º slide e <p> nos demais; titulo é <h2>, uma linha por quebra do textarea
 *   (React: titulo.split('\n') → <span class="block">; o texto vem com \r\n — preg_split('/\R/', …)).
 * - CTA: texto null → o botão não aparece; link null → href "#" (como no React).
 * - Onda decorativa (não é campo): assets/diagnostico-veterinario-em-sp-46.svg, só do lg em diante;
 *   vaza 20px sobre a seção seguinte (como no React).
 * - Store `upwork/veterinarios-hero` (hero.store.js): setas, bolinhas, swipe e autoplay (só com
 *   autoplay true e 2+ slides; pausa com o mouse ou o foco no carrossel; mínimo de 2000 ms, como no
 *   React; desligado com prefers-reduced-motion). O ativo é `context.index` e cada slide/bolinha
 *   tem o seu `context.i` (state.isActive).
 *
 * @var array{
 *   slides: list<array{
 *     imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     eyebrow: string|null,
 *     titulo: string|null,
 *     subtitulo: string|null,
 *     ctaPrimario: array{texto: string|null, link: string|null},
 *     ctaSecundario: array{texto: string|null, link: string|null},
 *   }>,
 *   autoplay: bool,
 *   intervalo: int,
 * } $data
 */

defined('ABSPATH') || exit;

$slides = $data['slides'] ?? [];
if (!$slides) return;

$total = count($slides);
$onda  = get_template_directory_uri() . '/template-parts/includes/veterinarios/hero/assets/diagnostico-veterinario-em-sp-46.svg';

// slide ativo no HTML do servidor (data-wp-bind/class) — o mesmo getter do hero.store.js
wp_interactivity_state('upwork/veterinarios-hero', [
  'isActive' => static function (): bool {
    $ctx = wp_interactivity_get_context();
    return ($ctx['i'] ?? 0) === ($ctx['index'] ?? 0);
  },
]);

$ctaClass = [
  'ctaPrimario'   => 'inline-flex w-full items-center justify-center rounded-full bg-primary px-7 py-5 font-heading text-h5 leading-none text-white transition-colors hover:bg-brand-purple-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:w-auto',
  'ctaSecundario' => 'inline-flex w-full items-center justify-center rounded-full bg-white px-7 py-5 font-heading text-h5 leading-none text-primary transition-colors hover:bg-primary hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:w-auto',
];
// ChevronLeft/ChevronRight (lucide) do React
$arrow = '<svg class="size-5 text-primary sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%s"/></svg>';
?>
<section
  id="hero"
  class="relative isolate flex min-h-[620px] w-full items-center bg-[linear-gradient(to_bottom_right,#95ABB7,#AEC6D3)] py-20 sm:min-h-[680px] md:py-24 lg:min-h-[720px] xl:min-h-[797px] xl:py-0"
  aria-roledescription="carousel"
  aria-label="<?php esc_attr_e('Destaques', 'upsites'); ?>"
  data-wp-interactive="upwork/veterinarios-hero"
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
  <?php foreach ($slides as $i => $slide) :
    $first  = $i === 0;
    $tag    = $first ? 'h1' : 'p'; // eyebrow: h1 no 1º slide, p nos demais
    $entrada = 0; // entrada só no 1º slide (o que aparece no carregamento): posição na cascata (data-hero-reveal)
    $ctas   = array_filter(
      ['ctaPrimario' => $slide['ctaPrimario'], 'ctaSecundario' => $slide['ctaSecundario']],
      static fn(array $cta): bool => (bool) $cta['texto']
    );
    ?>
    <div
      <?= wp_interactivity_data_wp_context(['i' => $i]) ?>
      class="<?= fw_cn('absolute inset-0 transition-opacity duration-700 ease-in-out motion-reduce:transition-none', $first ? 'opacity-100' : 'pointer-events-none opacity-0') ?>"
      role="group"
      aria-roledescription="slide"
      aria-label="<?= esc_attr(sprintf(/* translators: 1: número do slide, 2: total de slides */ __('%1$d de %2$d', 'upsites'), $i + 1, $total)) ?>"
      aria-hidden="<?= $first ? 'false' : 'true' ?>"
      <?= $first ? '' : 'inert' ?>
      data-wp-class--opacity-100="state.isActive"
      data-wp-class--opacity-0="!state.isActive"
      data-wp-class--pointer-events-none="!state.isActive"
      data-wp-bind--aria-hidden="!state.isActive"
      data-wp-bind--inert="!state.isActive"
    >
      <?php // Foto: tela toda no celular (com véu para o texto); dos 40% à direita no lg, borda esquerda dissolvida ?>
      <div class="absolute inset-0 lg:inset-y-0 lg:right-0 lg:left-[40%]">
        <?php // sem lazy (o slide inativo é transparente, não display:none). sizes = largura que o
              // object-cover pede (~830px no celular, ~1060px no desktop), não a da caixa ?>
        <?= fw_img($slide['imagem'], [
          'class'         => 'h-full w-full object-cover object-[72%_20%] lg:object-[center_18%] lg:[mask-image:linear-gradient(to_right,transparent,black_40%)]',
          'sizes'         => '(min-width: 1024px) 1060px, 830px',
          'loading'       => false,
          'fetchpriority' => $first ? 'high' : 'low',
        ], 'full') ?>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(45_37_89/0.8),rgb(45_37_89/0.45),rgb(45_37_89/0.1))] lg:hidden"></div>
      </div>

      <div class="<?= fw_cn('container relative z-10 flex h-full items-start pt-[186px] lg:items-center lg:pt-0', ['lg:pl-hero-arrows' => $total > 1]) ?>">
        <div class="max-w-xl text-left lg:max-w-[598px]">
          <?php if ($slide['eyebrow']) : ?>
            <<?= tag_escape($tag) ?> <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'font-sans text-body text-white']) ?>><?= esc_html($slide['eyebrow']) ?></<?= tag_escape($tag) ?>>
          <?php endif; ?>

          <?php if ($slide['titulo']) : ?>
            <?php // no Tailwind v3 do React o md:text-4xl e o lg:text-h1 trocavam a altura de linha do leading-tight ?>
            <h2 <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'mt-3 font-heading text-3xl leading-tight font-medium text-white md:text-4xl md:leading-10 lg:mt-6 lg:text-h1 lg:leading-[1.15]']) ?>>
              <?php foreach (preg_split('/\R/', $slide['titulo']) ?: [] as $linha) : ?>
                <span class="block"><?= esc_html($linha) ?></span>
              <?php endforeach; ?>
            </h2>
          <?php endif; ?>

          <?php if ($slide['subtitulo']) : ?>
            <?php // idem: o lg:text-h4 trocava a altura de linha do leading-snug ?>
            <p <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'mt-4 max-w-md font-heading text-base leading-snug font-medium text-white md:mt-6 lg:max-w-[542px] lg:text-h4 lg:leading-[1.4]']) ?>><?= esc_html($slide['subtitulo']) ?></p>
          <?php endif; ?>

          <?php if ($ctas) : ?>
            <div class="mt-8 flex flex-col items-stretch gap-3 md:mt-10 lg:flex-row lg:flex-wrap lg:items-center lg:gap-4">
              <?php foreach ($ctas as $key => $cta) : ?>
                <a <?= fw_attrs(['href' => fw_url($cta['link']) ?? '#', 'data-hero-reveal' => $first ? $entrada++ : null, 'class' => $ctaClass[$key]]) ?>><?= esc_html($cta['texto']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

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

    <div class="absolute inset-x-0 bottom-4 z-20 flex justify-center gap-2 md:bottom-6">
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

  <?php // Onda decorativa inferior (só do lg em diante) ?>
  <?= fw_img(['src' => $onda, 'width' => 1426, 'height' => 53, 'alt' => ''], [
    'aria-hidden' => 'true',
    'loading'     => false,
    'class'       => 'pointer-events-none absolute inset-x-0 bottom-[-20px] z-10 mx-auto hidden w-full max-w-[1426px] lg:block',
  ]) ?>
</section>
