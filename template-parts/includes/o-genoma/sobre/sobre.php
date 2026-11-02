<?php
/**
 * Sobre nós — texto institucional + destaque entre linhas (React: SobreSection em
 * app/o-genoma/o-genoma.view.tsx; <section id="sobre">).
 * Dados: OGenomaController → 'sobre' (null = "Ocultar seção" → nem chega aqui).
 *
 * - eyebrow, titulo, texto e destaque todos null → a seção não aparece.
 * - eyebrow: <p> em caixa alta; null → não aparece.
 * - titulo (<h2>): o React NÃO quebra em linhas (mostra {titulo} cru — uma quebra do textarea vira
 *   espaço), embora a instrução do campo diga que o Enter quebra. O valor atual não tem quebra.
 * - texto: um <p> por parágrafo, separados por linha em branco (React: texto.split(/\n\s*\n/));
 *   o texto vem com \r\n — preg_split('/\R\s*\R/', …) + trim (como o paragrafos() da Home).
 * - destaque (<h3>): uma linha por quebra do textarea (React: destaque.split('\n') → <span class="block">);
 *   null → o bloco inteiro (hr + texto + hr) não aparece.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   destaque: string|null,
 * } $data
 */

defined('ABSPATH') || exit;

if (!$data['eyebrow'] && !$data['titulo'] && !$data['texto'] && !$data['destaque']) return;

$paragrafos = $data['texto'] ? array_filter(array_map('trim', preg_split('/\R\s*\R/', $data['texto']) ?: [])) : [];
?>
<section id="sobre" class="w-full bg-background pt-12 pb-16 sm:pt-20 sm:pb-24 md:pt-24 md:pb-32 xl:pt-28 xl:pb-48">
  <div class="container">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
      <div>
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="mt-2 max-w-[560px] font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h2>
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
        <div class="flex flex-col gap-6 lg:mt-[19px] lg:gap-10">
          <hr data-reveal class="border-t border-brand-purple">
          <h3 data-reveal class="font-heading text-h4 text-brand-purple-dark">
            <?php foreach (preg_split('/\R/', $data['destaque']) ?: [] as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h3>
          <hr data-reveal class="border-t border-brand-purple">
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
