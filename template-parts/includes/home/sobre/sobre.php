<?php
/**
 * Sobre o Genoma — fotos, texto institucional, destaque e cards (React: SobreSection em
 * app/home/home.view.tsx; <section id="sobre">).
 * Dados: HomeController → 'sobre' (null = "Ocultar seção" → nem chega aqui).
 *
 * - fotos: sempre 3 posições (foto apagada → null); a 3ª é a mais larga (sm:flex-[1.8]).
 * - paragrafos: o textarea já separado por linha em branco (um <p> por item).
 * - Selo decorativo sobre as fotos (não é campo): assets/diagnostico-veterinario-em-sp-25.svg.
 * - icone: IconData — svg (markup sanitizado) quando o arquivo é SVG; o React mostrava como imagem pelo src (ou use shared/icon).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   fotos: array{
 *     0: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     1: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     2: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   },
 *   paragrafos: list<string>,
 *   destaque: string|null,
 *   diferenciais: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array, svg: string|null}|null,
 *     titulo: string|null,
 *     descricao: string|null,
 *   }>,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;

$fotos = $data['fotos'] ?? [];
// a 3ª foto é a mais larga (sm:flex-[1.8]); sizes ≈ largura que o object-cover pede
$fotoClass = [
  0 => ['class' => 'h-64 w-full min-w-0 rounded-2xl object-cover sm:h-72 sm:flex-1 md:h-80 lg:h-[389px]', 'sizes' => '(min-width: 640px) 33vw, 100vw'],
  1 => ['class' => 'h-64 w-full min-w-0 rounded-2xl object-cover sm:h-72 sm:flex-1 md:h-80 lg:h-[389px]', 'sizes' => '(min-width: 640px) 33vw, 100vw'],
  2 => ['class' => 'h-64 w-full min-w-0 rounded-2xl object-cover sm:h-72 sm:flex-[1.8] md:h-80 lg:h-[389px]', 'sizes' => '(min-width: 640px) 50vw, 100vw'],
];
$selo = [
  'src'    => get_template_directory_uri() . '/template-parts/includes/home/sobre/assets/diagnostico-veterinario-em-sp-25.svg',
  'width'  => 100,
  'height' => 323,
  'alt'    => '',
];
?>
<section id="sobre" class="w-full py-12 md:py-20 lg:py-24">
  <div class="container flex flex-col gap-12 md:gap-14 lg:gap-[73px]">
    <?php // Cabeçalho + fotos ?>
    <?php if ($data['eyebrow'] || $data['titulo'] || array_filter($fotos)) : ?>
      <div class="flex flex-col gap-8 md:gap-10 lg:gap-[49px]">
        <?php if ($data['eyebrow'] || $data['titulo']) : ?>
          <div class="flex max-w-xl flex-col gap-2 lg:max-w-[476px]">
            <?php if ($data['eyebrow']) : ?>
              <span data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></span>
            <?php endif; ?>
            <?php if ($data['titulo']) : ?>
              <h2 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h2>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (array_filter($fotos)) : ?>
          <div class="relative flex flex-col gap-4 sm:flex-row sm:items-stretch sm:gap-5">
            <?php foreach ($fotos as $i => $foto) : ?>
              <?= fw_img($foto, ['data-reveal' => true] + ($fotoClass[$i] ?? $fotoClass[0])) ?>
            <?php endforeach; ?>
            <?php // sem lazy: no celular fica display:none e o preview do fidelity esperaria para sempre (SVG de 8 KB) ?>
            <?= fw_img($selo, [
              'aria-hidden' => 'true',
              'loading'     => false,
              'class'       => 'pointer-events-none absolute -top-[73px] right-6 hidden w-16 sm:block md:-top-[92px] md:right-8 md:w-20 lg:-top-[115px] lg:right-[46px] lg:w-[100px]',
            ]) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php // Texto institucional + frase de destaque ?>
    <?php if ($data['paragrafos'] || $data['destaque']) : ?>
      <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between lg:gap-16">
        <?php if ($data['paragrafos']) : ?>
          <div class="flex flex-col gap-4 font-sans text-base text-brand-gray-text lg:max-w-xl lg:flex-1">
            <?php foreach ($data['paragrafos'] as $paragrafo) : ?>
              <p data-reveal><?= esc_html($paragrafo) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($data['destaque']) : ?>
          <div data-reveal class="flex flex-col gap-6 border-y border-brand-purple py-6 lg:w-[351px] lg:shrink-0">
            <p class="font-sans text-base text-brand-purple-dark"><?= esc_html($data['destaque']) ?></p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php // Cards de diferenciais ?>
    <?php if ($data['diferenciais']) : ?>
      <ul class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
        <?php foreach ($data['diferenciais'] as $item) : ?>
          <li data-reveal class="flex flex-col gap-8 rounded-2xl bg-brand-light-purple p-6 md:gap-9 md:p-9">
            <?php Part::render('shared/icon', ['icon' => $item['icone'], 'class' => 'size-[52px]']); ?>
            <?php if ($item['titulo'] || $item['descricao']) : ?>
              <div class="flex flex-col gap-4">
                <?php if ($item['titulo']) : ?>
                  <h3 class="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4"><?= esc_html($item['titulo']) ?></h3>
                <?php endif; ?>
                <?php if ($item['descricao']) : ?>
                  <p class="font-sans text-sm text-brand-gray-text md:text-base"><?= esc_html($item['descricao']) ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
