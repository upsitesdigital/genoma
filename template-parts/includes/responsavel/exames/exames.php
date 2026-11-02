<?php
/**
 * Exames — preparação para exames (jejum): banner com foto de fundo, lista com ícones e observação
 * (React: ExamesSection em app/responsavel/responsavel.view.tsx; <section id="exames">).
 * Dados: ResponsavelController → 'exames' (null = "Ocultar seção" → nem chega aqui).
 *
 * - A seção sempre aparece (como no React); sem imagem fica o fundo roxo escuro.
 * - Overlay: degradê vertical escuro no celular/tablet (legibilidade sobre a foto inteira) e
 *   horizontal na cor da foto no desktop (Figma).
 * - itens: texto normal + destaque em negrito, colados (o espaço antes do destaque vem do campo).
 * - titulo: uma linha (<span class="block">) por quebra de linha do textarea.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   itens: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     texto: string|null,
 *     destaque: string|null,
 *   }>,
 *   rodape: string|null,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;

$linhas = $data['titulo'] ? preg_split('/\R/', $data['titulo']) : [];
$itens  = $data['itens'] ?? [];
?>
<section id="exames" class="relative isolate overflow-hidden bg-brand-purple-dark py-16 md:py-20 lg:min-h-[560px] lg:py-24 xl:min-h-[667px] xl:py-28">
  <?php // Foto de fundo (atrás dos overlays) ?>
  <?= fw_img($data['imagem'], [
    'class' => 'absolute inset-0 -z-10 h-full w-full object-cover',
    'sizes' => '100vw',
  ], 'full') ?>
  <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_top,rgba(0,0,0,0.85),rgba(0,0,0,0.55),rgba(0,0,0,0.25))] lg:hidden" aria-hidden="true"></div>
  <div class="absolute inset-0 -z-10 hidden bg-[linear-gradient(90deg,rgba(134,156,170,1)_39%,rgba(158,180,193,0)_100%)] lg:block" aria-hidden="true"></div>

  <div class="container relative flex flex-col gap-8 lg:h-full lg:justify-center lg:gap-10 xl:gap-12">
    <?php if ($data['eyebrow'] || $linhas || $data['texto']) : ?>
      <div class="max-w-xl space-y-3 md:space-y-4 lg:max-w-[594px]">
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-body text-white"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if ($linhas) : ?>
          <h2 data-reveal class="font-heading text-h3 leading-tight font-medium text-white md:text-h2 md:leading-[1.3] md:font-normal">
            <?php foreach ($linhas as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h2>
        <?php endif; ?>

        <?php if ($data['texto']) : ?>
          <p data-reveal class="max-w-[520px] font-sans text-body leading-relaxed text-white/80"><?= esc_html($data['texto']) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($itens) : ?>
      <ul class="flex max-w-xl flex-col gap-5 lg:max-w-[525px] lg:gap-8">
        <?php foreach ($itens as $item) : ?>
          <li data-reveal class="flex items-center gap-4 lg:gap-6">
            <?= fw_img($item['icone'], ['alt' => '', 'aria-hidden' => 'true', 'class' => 'size-9 shrink-0 lg:size-[52px]']) ?>
            <p class="font-sans text-body leading-snug text-white"><?= esc_html((string) $item['texto']) ?><?php if ($item['destaque']) : ?><span class="font-semibold"><?= esc_html($item['destaque']) ?></span><?php endif; ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($data['rodape']) : ?>
      <p data-reveal class="max-w-xl font-sans text-body-sm leading-relaxed text-white/80 lg:max-w-[594px]"><?= esc_html($data['rodape']) ?></p>
    <?php endif; ?>
  </div>
</section>
