<?php
/**
 * Hero — carrossel de slides (React: HeroCarousel + SlideContent em app/o-genoma/o-genoma.view.tsx;
 * <section id="hero">). Dados: OGenomaController → 'hero' (null = "Ocultar seção" → nem chega aqui).
 *
 * - slides vazio → a seção não aparece; setas e bolinhas só com 2+ slides.
 * - eyebrow é o <h1> no 1º slide e <p> nos demais; null → não aparece (1º slide sem eyebrow: a
 *   página fica sem <h1> visível e o View::ensureH1 põe um sr-only com o título da página).
 * - titulo (<h2>): uma linha por quebra do textarea (React: titulo.split('\n') → <span class="block">);
 *   o texto vem com \r\n — quebrado com preg_split('/\R/', …).
 * - destaque: sem quebra de linha (um <p>); null → a coluna inteira (hr + texto + hr) não aparece.
 * - imagem1 (menor, 314/390) e imagem2 (maior, 873/390): cada uma só se tiver valor; sem as duas,
 *   a grade de fotos não aparece. Sem padrão (como no React): nunca salva = null.
 * - Faixa branca na base (before:, só nesta página — commit 0bfb118 do tema React): as fotos
 *   (relative z-10) ficam metade sobre o degradê, metade sobre o branco.
 * - Slides empilhados como no React: o ativo é `relative` (dá a altura) e os outros ficam
 *   `absolute inset-0` transparentes por cima — a troca é um fade. O ativo é `context.index` e cada
 *   slide/bolinha tem o seu `context.i` (state.isActive).
 * - Store `upwork/o-genoma-hero` (hero.store.js): setas, bolinhas, swipe e autoplay (só com autoplay
 *   true e 2+ slides; pausa com o mouse ou o foco no carrossel; mínimo de 2000 ms, como no React;
 *   desligado com prefers-reduced-motion).
 *
 * @var array{
 *   slides: list<array{
 *     eyebrow: string|null,
 *     titulo: string|null,
 *     imagem1: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     imagem2: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     destaque: string|null,
 *   }>,
 *   autoplay: bool,
 *   intervalo: int,
 * } $data
 */

defined('ABSPATH') || exit;

$slides = $data['slides'] ?? [];
if (!$slides) return;

$total = count($slides);

// slide ativo no HTML do servidor (data-wp-bind/class) — o mesmo getter do hero.store.js
wp_interactivity_state('upwork/o-genoma-hero', [
  'isActive' => static function (): bool {
    $ctx = wp_interactivity_get_context();
    return ($ctx['i'] ?? 0) === ($ctx['index'] ?? 0);
  },
]);

// ChevronLeft/ChevronRight (lucide) do React
$arrow = '<svg class="size-5 text-primary sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%s"/></svg>';
?>
<section
  id="hero"
  class="relative isolate z-10 w-full bg-[linear-gradient(to_bottom_right,#95ABB7,#AEC6D3)] pt-[186px] before:absolute before:bottom-0 before:h-[145px] before:w-full before:bg-white before:content-[''] lg:pt-40 xl:pt-[203px]"
  aria-roledescription="carousel"
  aria-label="<?php esc_attr_e('Destaques', 'upsites'); ?>"
  data-wp-interactive="upwork/o-genoma-hero"
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
  <?php // Slides: todos no HTML; o ativo dá a altura, os outros ficam transparentes por cima ?>
  <div class="relative">
    <?php foreach ($slides as $i => $slide) :
      $first  = $i === 0;
      $tag    = $first ? 'h1' : 'p'; // eyebrow: h1 no 1º slide, p nos demais
      $entrada = 0; // entrada só no 1º slide (o que aparece no carregamento): posição na cascata (data-hero-reveal)
      ?>
      <div
        <?= wp_interactivity_data_wp_context(['i' => $i]) ?>
        class="<?= fw_cn('transition-opacity duration-700 ease-in-out motion-reduce:transition-none', $first ? 'relative opacity-100' : 'pointer-events-none absolute inset-0 opacity-0') ?>"
        role="group"
        aria-roledescription="slide"
        aria-label="<?= esc_attr(sprintf(/* translators: 1: número do slide, 2: total de slides */ __('%1$d de %2$d', 'upsites'), $i + 1, $total)) ?>"
        aria-hidden="<?= $first ? 'false' : 'true' ?>"
        data-wp-class--relative="state.isActive"
        data-wp-class--opacity-100="state.isActive"
        data-wp-class--absolute="!state.isActive"
        data-wp-class--inset-0="!state.isActive"
        data-wp-class--pointer-events-none="!state.isActive"
        data-wp-class--opacity-0="!state.isActive"
        data-wp-bind--aria-hidden="!state.isActive"
      >
        <div class="container">
          <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
            <div class="max-w-xl lg:max-w-[636px]">
              <?php if ($slide['eyebrow']) : ?>
                <<?= tag_escape($tag) ?> <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'font-sans text-body text-white']) ?>><?= esc_html($slide['eyebrow']) ?></<?= tag_escape($tag) ?>>
              <?php endif; ?>

              <?php if ($slide['titulo']) : ?>
                <?php // no Tailwind v3 do React o md:text-4xl e o lg:text-h1 trocavam a altura de linha do leading-tight ?>
                <h2 <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'mt-4 font-heading text-3xl leading-tight font-medium text-white sm:mt-6 md:text-4xl md:leading-10 lg:text-h1 lg:leading-[1.15]']) ?>>
                  <?php foreach (preg_split('/\R/', $slide['titulo']) ?: [] as $linha) : ?>
                    <span class="block"><?= esc_html($linha) ?></span>
                  <?php endforeach; ?>
                </h2>
              <?php endif; ?>
            </div>

            <?php if ($slide['destaque']) : ?>
              <div class="flex flex-col gap-8 lg:mt-[52px] lg:gap-[49px]">
                <hr <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'border-t border-white']) ?>>
                <p <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'font-sans text-body-lg text-white']) ?>><?= esc_html($slide['destaque']) ?></p>
                <hr <?= fw_attrs(['data-hero-reveal' => $first ? $entrada++ : null, 'class' => 'border-t border-white']) ?>>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($slide['imagem1'] || $slide['imagem2']) : ?>
            <?php // fotos do 1º slide = LCP: sem lazy e com prioridade; sizes ≈ colunas 314fr/873fr do container ?>
            <div class="relative z-10 mt-10 grid grid-cols-1 gap-4 sm:grid-cols-[314fr_873fr] sm:gap-7 md:mt-16 lg:mt-24 xl:mt-[147px]">
              <?= fw_img($slide['imagem1'], [
                'data-hero-reveal' => $first ? $entrada++ : null,
                'class'            => 'h-64 w-full rounded-2xl object-cover sm:aspect-[314/390] sm:h-auto',
                'sizes'            => '(min-width: 1400px) 360px, (min-width: 640px) 27vw, 100vw',
                'loading'          => $first ? false : 'lazy',
                'fetchpriority'    => $first ? 'high' : null,
              ]) ?>
              <?= fw_img($slide['imagem2'], [
                'data-hero-reveal' => $first ? $entrada++ : null,
                'class'            => 'h-64 w-full rounded-2xl object-cover sm:aspect-[873/390] sm:h-auto',
                'sizes'            => '(min-width: 1400px) 980px, (min-width: 640px) 70vw, 100vw',
                'loading'          => $first ? false : 'lazy',
                'fetchpriority'    => $first ? 'high' : null,
              ], 'full') ?>
            </div>
          <?php endif; ?>
        </div>
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

    <?php // como no React: bolinhas no fluxo, abaixo das fotos (ficam sob a faixa branca do before:) ?>
    <div class="mt-8 flex justify-center gap-2 sm:mt-10">
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
</section>
