<?php
/**
 * Estrutura — etiqueta, título, descrição e galeria de 3 fotos (React: EstruturaSection em
 * app/home/home.view.tsx; <section id="estrutura">).
 * Dados: HomeController → 'estrutura' (null = "Ocultar seção" → nem chega aqui).
 *
 * - fotos: sempre 3 posições (foto apagada → null, não renderiza); sem nenhuma, a galeria some.
 * - No celular as fotos ficam empilhadas na proporção do arquivo (o flex-1 numa coluna sem altura
 *   ignora o h-64 — igual ao React); do sm em diante, lado a lado com altura fixa.
 * - O md:mt-[110px] do React (respiro para os cards que vazam dos Diferenciais) está no
 *   md:mb-[110px] da seção Diferenciais.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   descricao: string|null,
 *   fotos: array{
 *     0: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     1: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     2: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   },
 * } $data
 */

defined('ABSPATH') || exit;

$fotos = array_filter($data['fotos'] ?? []);
?>
<section id="estrutura" class="w-full py-12 md:py-20 lg:py-24">
  <div class="container flex flex-col gap-6 md:gap-8 lg:gap-16">
    <?php if ($data['eyebrow'] || $data['titulo'] || $data['descricao']) : ?>
      <div class="flex flex-col gap-2">
        <?php if ($data['eyebrow'] || $data['titulo']) : ?>
          <div class="flex flex-col gap-2">
            <?php if ($data['eyebrow']) : ?>
              <span data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></span>
            <?php endif; ?>
            <?php if ($data['titulo']) : ?>
              <h2 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h2>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($data['descricao']) : ?>
          <p data-reveal class="max-w-xl font-sans text-base text-brand-gray-text lg:max-w-2xl"><?= esc_html($data['descricao']) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($fotos) : ?>
      <div class="flex flex-col gap-4 sm:flex-row sm:items-stretch sm:gap-5">
        <?php foreach ($fotos as $foto) : ?>
          <?= fw_img($foto, [
            'data-reveal' => true,
            'class'       => 'h-64 w-full min-w-0 flex-1 rounded-2xl object-cover sm:h-72 md:h-80 lg:h-[374px]',
            'sizes'       => '(min-width: 1400px) 440px, (min-width: 640px) 32vw, calc(100vw - 40px)',
          ]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
