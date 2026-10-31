<?php
/**
 * Depoimentos — carrossel (React: DepoimentosSection em app/home/home.view.tsx; <section id="depoimentos">).
 * Dados: HomeController → 'depoimentos' (null = "Ocultar seção" → nem chega aqui).
 *
 * - itens vazio → a seção não aparece (como no React); setas só com 2+ itens (desktop).
 * - icone: IconData (no banco é o SVG de aspas; o React mostrava como imagem redonda pelo src):
 *   SVG inline pelo shared/icon; foto (PNG/JPG/WebP) recortada no círculo (object-cover).
 * - intervalo: mínimo de 2000 ms no store (React: Math.max(intervalo, 2000)).
 * - Como no React: no celular/tablet os cards ficam empilhados; do lg em diante, trilho com rolagem
 *   horizontal (scroll-snap, 2 cards visíveis) e setas. Sem bolinhas nem swipe próprio (o React não
 *   tinha — no touch o trilho rola nativo).
 * - Store `upwork/home-depoimentos` (depoimentos.store.js): setas e autoplay rolam o trilho um card
 *   (nas pontas, volta ao outro extremo); autoplay pausa com o mouse ou o foco na seção e fica
 *   desligado com prefers-reduced-motion. Cards sem animação de entrada (carrossel — como no React).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   itens: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array, svg: string|null}|null,
 *     texto: string|null,
 *     nome: string|null,
 *   }>,
 *   autoplay: bool,
 *   intervalo: int,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;

$itens = $data['itens'] ?? [];
if (!$itens) return;

$carrossel = count($itens) > 1;
$trilho    = 'depoimentos-trilho';

$avatarClass = 'size-10 shrink-0 rounded-full lg:order-1 lg:size-[60px]';
$arrowClass  = 'hidden size-10 shrink-0 items-center justify-center rounded-full bg-brand-purple-dark text-white transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple-dark sm:size-[54px] lg:flex';
// ChevronLeft/ChevronRight (lucide) do React
$arrow = '<svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%s"/></svg>';
?>
<section
  id="depoimentos"
  class="w-full py-12 md:py-20 lg:py-24"
  <?php if ($carrossel) : ?>
    data-wp-interactive="upwork/home-depoimentos"
    <?= wp_interactivity_data_wp_context([
      'trilho'    => $trilho,
      'autoplay'  => (bool) $data['autoplay'],
      'intervalo' => (int) $data['intervalo'],
      'paused'    => false,
    ]) ?>
    data-wp-watch="callbacks.autoplay"
    data-wp-on--mouseenter="actions.pause"
    data-wp-on--mouseleave="actions.resume"
    data-wp-on--focusin="actions.pause"
    data-wp-on--focusout="actions.resumeOnFocusOut"
  <?php endif; ?>
>
  <div class="container flex flex-col gap-8 md:gap-10 lg:gap-12">
    <?php if ($data['eyebrow'] || $data['titulo']) : ?>
      <div class="flex max-w-xl flex-col gap-2">
        <?php if ($data['eyebrow']) : ?>
          <span data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></span>
        <?php endif; ?>
        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h2>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="relative flex w-full items-center gap-3 sm:gap-5 lg:gap-8">
      <?php if ($carrossel) : ?>
        <button
          type="button"
          data-reveal
          class="<?= esc_attr($arrowClass) ?>"
          aria-controls="<?= esc_attr($trilho) ?>"
          aria-label="<?php esc_attr_e('Depoimento anterior', 'upsites'); ?>"
          data-wp-on--click="actions.prev"
        ><?= sprintf($arrow, 'm15 18-6-6 6-6') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>
      <?php endif; ?>

      <ul
        id="<?= esc_attr($trilho) ?>"
        class="flex w-full flex-col gap-4 lg:snap-x lg:snap-mandatory lg:flex-row lg:gap-8 lg:overflow-x-auto lg:scroll-smooth lg:[scrollbar-width:none] lg:[&::-webkit-scrollbar]:hidden"
      >
        <?php foreach ($itens as $item) : ?>
          <li class="flex w-full shrink-0 snap-start flex-col gap-5 rounded-2xl bg-brand-light-purple p-6 lg:w-[calc(50%-16px)] lg:gap-8 lg:p-16">
            <?php if ($item['texto']) : ?>
              <blockquote class="font-sans text-[15px] leading-[1.5] text-brand-purple-dark lg:order-2 lg:font-heading lg:text-h4 lg:leading-[1.4] lg:font-medium"><?= esc_html($item['texto']) ?></blockquote>
            <?php endif; ?>

            <?php // celular: avatar ao lado do nome; desktop: avatar no topo, nome no fim (lg:contents + order) ?>
            <?php if ($item['icone'] || $item['nome']) : ?>
              <div class="flex items-center gap-3 lg:contents">
                <?php if (!empty($item['icone']['svg'])) : ?>
                  <?php Part::render('shared/icon', ['icon' => $item['icone'], 'class' => $avatarClass]); ?>
                <?php else : ?>
                  <?= fw_img($item['icone'], [
                    'class' => 'object-cover ' . $avatarClass,
                    'sizes' => '60px',
                  ], 'medium') ?>
                <?php endif; ?>
                <?php if ($item['nome']) : ?>
                  <span class="font-sans text-[15px] leading-[1.5] font-semibold text-brand-purple-accent lg:order-3 lg:font-heading lg:text-h5 lg:leading-[1.4] lg:font-medium"><?= esc_html($item['nome']) ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($carrossel) : ?>
        <button
          type="button"
          data-reveal
          class="<?= esc_attr($arrowClass) ?>"
          aria-controls="<?= esc_attr($trilho) ?>"
          aria-label="<?php esc_attr_e('Próximo depoimento', 'upsites'); ?>"
          data-wp-on--click="actions.next"
        ><?= sprintf($arrow, 'm9 18 6-6-6-6') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>
      <?php endif; ?>
    </div>
  </div>
</section>
