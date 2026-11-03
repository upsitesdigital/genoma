<?php
/**
 * Portfólio de Exames — banner com imagem (React: PortfolioSection em app/home/home.view.tsx).
 * Dados: HomeController → 'portfolio' (null = "Ocultar seção" → nem chega aqui).
 * O id da <section> é o do React (âncora dos menus de produção), diferente da pasta.
 *
 * - CTA: texto null → o botão não aparece; link null → href "#" (como no React).
 * - Imagem: tela toda no celular/tablet (com véu roxo para o texto); do lg em diante, à direita
 *   (43% → 100%) com a borda esquerda fundida ao fundo.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   cta: array{texto: string|null, link: string|null},
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;
?>
<section id="portfolio-de-exames" class="relative isolate flex min-h-[420px] w-full items-center overflow-hidden bg-[#859CA9] py-16 sm:min-h-[480px] md:py-20 lg:min-h-[560px] xl:min-h-[625px] xl:py-0">
  <?php // Imagem de fundo + gradientes de legibilidade ?>
  <div class="absolute inset-0 lg:inset-y-0 lg:right-0 lg:left-[43%]">
    <?= fw_img($data['imagem'], [
      'class' => 'h-full w-full object-cover object-[70%_30%] lg:object-[center_25%]',
      // object-cover recorta pela altura: a foto aparece mais larga que a caixa
      'sizes' => '(min-width: 1024px) 78vw, 200vw',
    ], 'full') ?>

    <?php // Véu para a leitura do texto em telas menores (a imagem ocupa a largura toda) ?>
    <div class="absolute inset-0 bg-linear-to-r/srgb from-[#433292]/70 via-[#433292]/35 to-[#433292]/0 lg:hidden"></div>

    <?php // Funde a borda esquerda da imagem ao fundo (máscara do Figma) ?>
    <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-1/3 bg-linear-to-r/srgb from-[#859CA9] via-[#859CA9]/70 to-transparent lg:block"></div>
  </div>

  <div class="container relative z-10">
    <div class="flex max-w-xl flex-col gap-6 lg:max-w-[594px] lg:gap-8">
      <?php if ($data['eyebrow'] || $data['titulo']) : ?>
        <div class="flex flex-col gap-2">
          <?php if ($data['eyebrow']) : ?>
            <p data-reveal class="font-sans text-body text-white"><?= esc_html($data['eyebrow']) ?></p>
          <?php endif; ?>
          <?php if ($data['titulo']) : ?>
            <h2 data-reveal class="font-heading text-h3 text-white md:text-h2"><?= esc_html($data['titulo']) ?></h2>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($data['texto']) : ?>
        <p data-reveal class="max-w-[560px] font-sans text-body leading-relaxed text-white/80"><?= esc_html($data['texto']) ?></p>
      <?php endif; ?>

      <?php if ($data['cta']['texto']) : ?>
        <a <?= fw_attrs([
          'href'        => fw_url($data['cta']['link']) ?? '#',
          'data-reveal' => true,
          'class'       => 'inline-flex w-full items-center justify-center rounded-full bg-white px-7 py-5 font-heading text-base leading-none md:text-h4 text-primary transition-colors hover:bg-primary hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:w-fit',
        ]) ?>><?= esc_html($data['cta']['texto']) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
