<?php
/**
 * Resultados dos exames — texto em parágrafos + foto (React: ResultadosSection em
 * app/responsavel/responsavel.view.tsx; <section id="resultados">).
 * Dados: ResponsavelController → 'resultados' (null = "Ocultar seção" → nem chega aqui).
 *
 * - Sem etiqueta, título e texto → a seção não aparece (como no React).
 * - titulo: uma linha (<span class="block">) por quebra de linha do textarea.
 * - paragrafos: o textarea já separado por linha em branco (um <p> por item).
 * - Filete na base (border-b) separa da seção Benefícios.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   paragrafos: list<string>,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;

$linhas = $data['titulo'] ? preg_split('/\R/', $data['titulo']) : [];
if (!$data['eyebrow'] && !$linhas && !$data['paragrafos']) return;
?>
<section id="resultados" class="border-b border-[rgba(41,19,154,0.2)] bg-background py-12 md:py-20 lg:py-24 xl:py-28">
  <div class="container">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_508px] lg:gap-16 xl:gap-24">
      <div>
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if ($linhas) : ?>
          <h2 data-reveal class="mt-2 max-w-[620px] font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
            <?php foreach ($linhas as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h2>
        <?php endif; ?>

        <?php if ($data['paragrafos']) : ?>
          <div class="mt-6 max-w-[577px] space-y-4 font-sans text-body leading-relaxed text-brand-gray-text md:mt-8">
            <?php foreach ($data['paragrafos'] as $paragrafo) : ?>
              <p data-reveal><?= esc_html($paragrafo) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?= fw_img($data['imagem'], [
        'data-reveal' => 'right',
        'class'       => 'h-64 w-full rounded-2xl object-cover sm:aspect-square sm:h-auto lg:h-full',
        'sizes'       => '(min-width: 1024px) 508px, 100vw',
      ]) ?>
    </div>
  </div>
</section>
