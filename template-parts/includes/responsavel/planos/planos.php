<?php
/**
 * Planos — logos dos planos de saúde pet atendidos (React: PlanosSection em
 * app/responsavel/responsavel.view.tsx; <section id="planos">).
 * Dados: ResponsavelController → 'planos' (null = "Ocultar seção" → nem chega aqui).
 *
 * - Sem etiqueta, título e logos → a seção não aparece (como no React).
 * - titulo: uma linha (<span class="block">) por quebra de linha do textarea.
 * - Logo sem imagem não aparece; o alt é o da imagem ou, vazio, o nome do plano.
 * - Sem padding no topo: fica logo abaixo do Suporte, que já tem o respiro.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   logos: list<array{
 *     imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     nome: string|null,
 *   }>,
 * } $data
 */

defined('ABSPATH') || exit;

$logos  = array_filter($data['logos'] ?? [], static fn(array $logo): bool => (bool) $logo['imagem']);
$linhas = $data['titulo'] ? preg_split('/\R/', $data['titulo']) : [];
if (!$data['eyebrow'] && !$linhas && !$logos) return;
?>
<section id="planos" class="bg-background pb-12 md:pb-20 lg:pb-24 xl:pb-28">
  <div class="container">
    <?php if ($data['eyebrow'] || $linhas) : ?>
      <div class="max-w-[572px]">
        <?php if ($data['eyebrow']) : ?>
          <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
        <?php endif; ?>
        <?php if ($linhas) : ?>
          <h2 data-reveal class="mt-3 font-heading text-h2-mobile text-brand-purple-dark md:mt-4 md:text-h2">
            <?php foreach ($linhas as $linha) : ?>
              <span class="block"><?= esc_html($linha) ?></span>
            <?php endforeach; ?>
          </h2>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($logos) : ?>
      <?php // as imagens são os itens do flex (como no React): sem <li>, que mudaria o encaixe na linha ?>
      <div class="mt-10 flex flex-wrap items-center justify-center gap-x-10 gap-y-8 md:mt-12 lg:flex-nowrap lg:justify-between lg:gap-x-8">
        <?php foreach ($logos as $logo) : ?>
          <?= fw_img($logo['imagem'], [
            'alt'         => $logo['imagem']['alt'] ?: (string) $logo['nome'],
            'data-reveal' => 'quick',
            'class'       => 'h-10 w-auto max-w-[160px] object-contain sm:h-12 md:h-14 lg:h-16',
            'sizes'       => '160px',
          ], 'medium') ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
