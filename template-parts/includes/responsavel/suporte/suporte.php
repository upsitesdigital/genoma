<?php
/**
 * Suporte — "Como funciona o atendimento": texto, frase de destaque entre linhas e 2 fotos
 * (React: SuporteSection em app/responsavel/responsavel.view.tsx; <section id="suporte">).
 * Dados: ResponsavelController → 'suporte' (null = "Ocultar seção" → nem chega aqui).
 *
 * - A seção sempre aparece (como no React); cada elemento some quando o campo está vazio.
 * - titulo: uma linha (<span class="block">) por quebra de linha do textarea; texto mantém as
 *   quebras de linha (whitespace-pre-line).
 * - imagem1 (retrato) e imagem2 (paisagem) lado a lado a partir do sm, nas proporções do Figma.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   quote: string|null,
 *   imagem1: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   imagem2: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;

$linhas = $data['titulo'] ? preg_split('/\R/', $data['titulo']) : [];
?>
<section id="suporte" class="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
  <div class="container">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
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

        <?php if ($data['texto']) : ?>
          <p data-reveal class="mt-6 max-w-[646px] font-sans text-body-lg leading-relaxed whitespace-pre-line text-brand-gray-text md:mt-8"><?= esc_html($data['texto']) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($data['quote']) : ?>
        <div data-reveal class="flex flex-col gap-8 lg:mt-[52px] lg:gap-[49px]">
          <hr class="border-t border-brand-purple">
          <p class="font-sans text-body-lg text-brand-purple-dark"><?= esc_html($data['quote']) ?></p>
          <hr class="border-t border-brand-purple">
        </div>
      <?php endif; ?>
    </div>

    <?php if ($data['imagem1'] || $data['imagem2']) : ?>
      <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-[314fr_873fr] sm:gap-7 md:mt-14">
        <?= fw_img($data['imagem1'], [
          'data-reveal' => true,
          'class'       => 'h-64 w-full rounded-2xl object-cover sm:aspect-[314/390] sm:h-auto',
          'sizes'       => '(min-width: 1400px) 353px, (min-width: 640px) 25vw, 100vw',
        ]) ?>
        <?= fw_img($data['imagem2'], [
          'data-reveal' => true,
          'class'       => 'h-64 w-full rounded-2xl object-cover sm:aspect-[873/389] sm:h-auto',
          'sizes'       => '(min-width: 1400px) 980px, (min-width: 640px) 70vw, 100vw',
        ]) ?>
      </div>
    <?php endif; ?>
  </div>
</section>
