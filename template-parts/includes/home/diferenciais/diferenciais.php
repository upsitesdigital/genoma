<?php
/**
 * Diferenciais — banner com imagem de fundo + cards (React: DiferenciaisSection em
 * app/home/home.view.tsx; <section id="diferenciais">).
 * Dados: HomeController → 'diferenciais' (null = "Ocultar seção" → nem chega aqui).
 *
 * - itens vazio → a seção não aparece (como no React).
 * - imagem: foto no topo no mobile; fundo do banner (atrás do degradê) no tablet/desktop.
 * - titulo dos itens vem de textarea: quebras de linha mantidas (whitespace-pre-line).
 * - icone: IconData — shared/icon (SVG inline; PNG/WebP como tag img).
 *
 * Um markup só para os dois layouts do React (lá eram duas árvores, uma escondida por breakpoint):
 * a mesma imagem (fw_img) é a foto do topo no celular e vira o fundo da seção a partir do md (absolute — o
 * container não é posicionado); os cards passam de linha (ícone + texto) para coluna.
 * Os cards vazam 210px para baixo do banner (React: mb-[-210px]). O respiro de 110px que o React
 * punha no topo da Estrutura (md:mt-[110px]) fica aqui, no md:mb-[110px]: mesmo resultado com as
 * duas seções e sem buraco quando uma delas é ocultada.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   itens: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array, svg: string|null}|null,
 *     titulo: string|null,
 *   }>,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;

$itens = $data['itens'] ?? [];
if (!$itens) return;
?>
<section id="diferenciais" class="relative isolate w-full bg-brand-light-purple py-12 md:mb-[110px] md:bg-[#9EB4C1] md:pt-88 md:pb-20 lg:pb-24">
  <?php // degradê sobre a foto de fundo (tablet/desktop) ?>
  <div class="pointer-events-none absolute inset-0 -z-10 hidden bg-[linear-gradient(36deg,rgba(158,180,193,1)_15%,rgba(158,180,193,0)_100%)] md:block" aria-hidden="true"></div>

  <div class="container flex flex-col gap-8 md:-mb-[210px] md:gap-20">
    <?= fw_img($data['imagem'], [
      'class' => 'h-40 w-full rounded-2xl object-cover md:absolute md:inset-0 md:-z-20 md:h-full md:rounded-none',
      'sizes' => '(min-width: 768px) 100vw, calc(100vw - 40px)',
    ], 'full') ?>

    <?php if ($data['eyebrow'] || $data['titulo']) : ?>
      <div class="flex flex-col gap-2 md:max-w-xl lg:max-w-2xl">
        <?php if ($data['eyebrow']) : ?>
          <span data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase md:text-base md:tracking-normal md:text-white md:normal-case"><?= esc_html($data['eyebrow']) ?></span>
        <?php endif; ?>
        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2 md:font-normal md:text-white"><?= esc_html($data['titulo']) ?></h2>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <ul class="flex flex-col gap-3 md:flex-row md:flex-wrap md:gap-[38px]">
      <?php foreach ($itens as $item) : ?>
        <li data-reveal class="flex items-center gap-4 rounded-xl border border-brand-gray-border bg-white p-5 md:flex-col md:items-stretch md:gap-12 md:p-9 lg:w-[281px] lg:shrink-0">
          <?php if ($item['icone']) : ?>
            <?php // celular: ícone de 18px num círculo lilás; md+: ícone de 52px solto ?>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-purple-subtle md:size-auto md:justify-start md:rounded-none md:bg-transparent">
              <?php Part::render('shared/icon', ['icon' => $item['icone'], 'class' => 'size-[18px] md:size-[52px]']); ?>
            </span>
          <?php endif; ?>
          <?php if ($item['titulo']) : ?>
            <p class="font-heading text-base leading-snug font-medium whitespace-pre-line text-brand-purple-dark md:max-w-[198px] md:font-sans md:text-body-lg md:leading-normal md:font-normal"><?= esc_html($item['titulo']) ?></p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
