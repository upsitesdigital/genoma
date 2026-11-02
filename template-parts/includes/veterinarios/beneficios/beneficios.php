<?php
/**
 * Benefícios — título + texto e cards com ícone (React: BeneficiosSection em
 * app/veterinarios/veterinarios.view.tsx; <section id="beneficios">).
 * Dados: VeterinariosController → 'beneficios' (null = "Ocultar seção" → nem chega aqui).
 *
 * - itens vazio → só o cabeçalho aparece (como no React); 4 cards por linha no lg, 2 no sm.
 * - Card: ícone (null → não aparece) + texto (null → não aparece).
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

    <?php if ($data['texto']) : ?>
      <p data-reveal class="mt-6 max-w-2xl font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8"><?= esc_html($data['texto']) ?></p>
    <?php endif; ?>

    <?php if ($data['itens']) : ?>
      <ul class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 md:mt-14 lg:grid-cols-4">
        <?php foreach ($data['itens'] as $item) : ?>
          <li data-reveal class="flex flex-col gap-8 rounded-xl border border-border bg-white p-7 sm:gap-10 sm:p-8 lg:gap-12 lg:p-9">
            <?= fw_img($item['icone'], ['class' => 'h-10 w-10 shrink-0 sm:h-12 sm:w-12 lg:h-[52px] lg:w-[52px]']) ?>
            <?php if ($item['texto']) : ?>
              <p class="font-sans text-body text-brand-purple-dark"><?= esc_html($item['texto']) ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
