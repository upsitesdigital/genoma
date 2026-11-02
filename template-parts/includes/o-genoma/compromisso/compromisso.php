<?php
/**
 * Compromisso — banner com imagem de fundo e degradê (React: CompromissoSection em
 * app/o-genoma/o-genoma.view.tsx; <section id="compromisso">).
 * Dados: OGenomaController → 'compromisso' (null = "Ocultar seção" → nem chega aqui).
 *
 * - eyebrow, titulo e texto todos null → a seção não aparece, mesmo com imagem.
 * - imagem: fundo da seção (object-cover); null → sem foto, os degradês continuam. Sem padrão
 *   (como no React): nunca salva = null.
 * - Degradês: no celular/tablet escurece de cima para baixo (legibilidade); do md para cima,
 *   horizontal na cor do Figma (o React usava style inline — aqui, valor arbitrário).
 * - eyebrow: <p>; null → não aparece.
 * - titulo (<h2>): uma linha por quebra do textarea (React: titulo.split('\n') → <span class="block">);
 *   o texto vem com \r\n — quebrado com preg_split('/\R/', …).
 * - texto: um <p> só, sem quebrar parágrafos (o React não separa, embora a instrução do campo fale
 *   em linha em branco). O valor atual não tem quebra.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;

if (!$data['eyebrow'] && !$data['titulo'] && !$data['texto']) return;
?>
<section id="compromisso" class="relative isolate w-full overflow-hidden py-16 sm:py-20 md:py-24 xl:py-28">
  <?= fw_img($data['imagem'], [
    'class' => 'absolute inset-0 -z-20 h-full w-full object-cover',
    'sizes' => '100vw',
  ], 'full') ?>
  <?php // degradês (Tailwind v3 do React: interpolação em sRGB — por isso o linear-gradient explícito) ?>
  <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_bottom,rgb(0_0_0/0.7),rgb(0_0_0/0.4),rgb(0_0_0/0.1))] md:hidden"></div>
  <div class="absolute inset-0 -z-10 hidden bg-[linear-gradient(90deg,rgba(133,156,169,1)_50%,rgba(158,180,193,0)_89%)] md:block"></div>

  <div class="container">
    <div class="flex max-w-[594px] flex-col gap-6 md:gap-10">
      <?php // como no React, o bloco existe mesmo vazio (o gap acima do texto se mantém) ?>
      <div class="flex flex-col gap-2">
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-body text-white"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="max-w-[581px] font-heading text-h3 text-white md:text-h2">
            <?php foreach (preg_split('/\R/', $data['titulo']) ?: [] as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h2>
        <?php endif; ?>
      </div>

      <?php if ($data['texto']) : ?>
        <p data-reveal class="font-sans text-body-lg leading-relaxed text-white/80"><?= esc_html($data['texto']) ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
