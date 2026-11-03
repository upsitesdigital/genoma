<?php
/**
 * Benefícios — cards com ícone e texto + frase de destaque (React: BeneficiosSection em
 * app/responsavel/responsavel.view.tsx; <section id="beneficios">).
 * Dados: ResponsavelController → 'beneficios' (null = "Ocultar seção" → nem chega aqui).
 *
 * - Sem etiqueta, título e itens → a seção não aparece (como no React).
 * - Cards: 1 coluna no celular, 2 no sm, 4 no desktop; ícone como imagem (SVG ou PNG), como no React.
 * - texto: frase em destaque abaixo dos cards (o emoji do campo vira imagem pelo wp-emoji do WordPress).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   itens: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     texto: string|null,
 *   }>,
 * } $data
 */

defined('ABSPATH') || exit;

$itens = $data['itens'] ?? [];
if (!$data['eyebrow'] && !$data['titulo'] && !$itens) return;
?>
<section id="beneficios" class="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
  <div class="container">
    <?php if ($data['eyebrow'] || $data['titulo']) : ?>
      <div class="flex flex-col gap-2">
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>
        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="max-w-2xl font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h2>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($itens) : ?>
      <ul class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 md:mt-14 lg:grid-cols-4">
        <?php foreach ($itens as $item) : ?>
          <li data-reveal class="flex flex-col gap-8 rounded-xl border border-border bg-white p-7 sm:gap-10 sm:p-8 lg:gap-12 lg:p-9">
            <?= fw_img($item['icone'], ['class' => 'size-10 shrink-0 sm:size-12 lg:size-[52px]']) ?>
            <?php if ($item['texto']) : ?>
              <?php // textarea: quebra de linha do painel mantida (como nos Diferenciais da home) ?>
              <p class="font-sans text-body whitespace-pre-line text-brand-purple-dark"><?= esc_html($item['texto']) ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($data['texto']) : ?>
      <p data-reveal class="mt-10 max-w-2xl font-heading text-h4 text-brand-purple-dark md:mt-14"><?= esc_html($data['texto']) ?></p>
    <?php endif; ?>
  </div>
</section>
