<?php
/**
 * Suporte — título + texto, frase entre duas linhas e duas fotos (React: SuporteSection em
 * app/veterinarios/veterinarios.view.tsx; <section id="suporte">).
 * Dados: VeterinariosController → 'suporte' (null = "Ocultar seção" → nem chega aqui).
 *
 * - titulo (<h2>): uma linha por quebra do textarea (React: titulo.split('\n') → <span class="block">;
 *   o texto vem com \r\n — preg_split('/\R/', …)).
 * - quote null → a coluna da direita inteira (hr + texto + hr) não aparece.
 * - imagem1 (menor, 314/390) e imagem2 (maior, 873/389): cada uma só se tiver valor; sem as duas,
 *   a grade de fotos não aparece. No celular, empilhadas com 256px de altura.
 * - A onda do hero (lg+) vaza 20px sobre o topo desta seção, como no React.
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
?>
<section id="suporte" class="bg-background pt-12 pb-6 md:pt-20 md:pb-10 lg:pt-24 lg:pb-12 xl:pt-28 xl:pb-14">
  <div class="container">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
      <div>
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="mt-2 max-w-[620px] font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
            <?php foreach (preg_split('/\R/', $data['titulo']) ?: [] as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h2>
        <?php endif; ?>

        <?php if ($data['texto']) : ?>
          <p data-reveal class="mt-6 max-w-[646px] font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8"><?= esc_html($data['texto']) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($data['quote']) : ?>
        <div class="flex flex-col gap-8 lg:mt-[52px] lg:gap-[49px]">
          <hr data-reveal="line" class="border-t border-brand-purple">
          <p data-reveal class="font-sans text-body-lg text-brand-purple-dark"><?= esc_html($data['quote']) ?></p>
          <hr data-reveal="line" class="border-t border-brand-purple">
        </div>
      <?php endif; ?>
    </div>

    <?php if ($data['imagem1'] || $data['imagem2']) : ?>
      <?php // sizes ≈ colunas 314fr/873fr do container (no celular, a 2ª é recortada pela altura: ~580px) ?>
      <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-[314fr_873fr] sm:gap-7 md:mt-14">
        <?= fw_img($data['imagem1'], [
          'data-reveal' => true,
          'class'       => 'h-64 w-full rounded-2xl object-cover sm:aspect-[314/390] sm:h-auto',
          'sizes'       => '(min-width: 1400px) 360px, (min-width: 640px) 27vw, 100vw',
        ]) ?>
        <?= fw_img($data['imagem2'], [
          'data-reveal' => true,
          'class'       => 'h-64 w-full rounded-2xl object-cover sm:aspect-[873/389] sm:h-auto',
          'sizes'       => '(min-width: 1400px) 980px, (min-width: 640px) 70vw, 580px',
        ], 'full') ?>
      </div>
    <?php endif; ?>
  </div>
</section>
