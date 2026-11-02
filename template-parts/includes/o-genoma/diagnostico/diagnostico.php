<?php
/**
 * Diagnóstico — título, texto e destaque entre linhas; última seção da página (React:
 * DiagnosticoSection em app/o-genoma/o-genoma.view.tsx; <section id="diagnostico">).
 * Dados: OGenomaController → 'diagnostico' (null = "Ocultar seção" → nem chega aqui).
 *
 * - titulo, texto e destaque todos null → a seção não aparece. Não tem etiqueta (eyebrow).
 * - titulo (<h2>): uma linha por quebra do textarea (React: titulo.split('\n') → <span class="block">);
 *   o texto vem com \r\n — quebrado com preg_split('/\R/', …).
 * - texto: um <p> por parágrafo, separados por linha em branco (React: texto.split(/\n\s*\n/));
 *   preg_split('/\R\s*\R/', …) + trim (como o paragrafos() da Home).
 * - destaque: um <p> só, sem quebra de linha; null → o bloco inteiro (hr + texto + hr) não aparece.
 *
 * @var array{
 *   titulo: string|null,
 *   texto: string|null,
 *   destaque: string|null,
 * } $data
 */

defined('ABSPATH') || exit;

if (!$data['titulo'] && !$data['texto'] && !$data['destaque']) return;

$paragrafos = $data['texto'] ? array_filter(array_map('trim', preg_split('/\R\s*\R/', $data['texto']) ?: [])) : [];
?>
<section id="diagnostico" class="w-full bg-background pt-12 pb-16 sm:pt-20 sm:pb-24 md:pt-24 md:pb-32 xl:pt-28 xl:pb-40">
  <div class="container">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
      <div>
        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="max-w-[560px] font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
            <?php foreach (preg_split('/\R/', $data['titulo']) ?: [] as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h2>
        <?php endif; ?>

        <?php if ($paragrafos) : ?>
          <div class="mt-6 max-w-[620px] space-y-4 font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8">
            <?php foreach ($paragrafos as $paragrafo) : ?>
              <p data-reveal><?= esc_html($paragrafo) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($data['destaque']) : ?>
        <div class="flex flex-col gap-6 lg:gap-10">
          <hr data-reveal class="border-t border-brand-purple">
          <p data-reveal class="font-sans text-body-lg leading-relaxed text-brand-purple-dark"><?= esc_html($data['destaque']) ?></p>
          <hr data-reveal class="border-t border-brand-purple">
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
