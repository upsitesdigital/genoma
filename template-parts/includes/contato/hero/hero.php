<?php
/**
 * Hero — faixa em degradê com etiqueta, título e descrição + cards de canais de contato que sobem
 * sobre o fim da faixa (React: HeroSection em app/contato/contato.view.tsx; <section id="hero">).
 * Dados: ContatoController → 'hero' (null = "Ocultar seção" → nem chega aqui).
 *
 * - eyebrow/titulo/descricao null → o elemento não aparece; titulo é o <h1> da página.
 * - canais vazio → só a faixa (sem a grade de cards), como no React.
 * - Card com link → <a> em nova aba (como no React, inclusive mailto:); link null → <div> sem clique.
 *   O href sai igual ao do campo (https://wa.me/…, mailto:…, tel:…) — fw_url bloqueia o resto.
 * - icone: ImageData pelo fw_img — imagem com o alt do arquivo, como no React; sem lazy (os cards
 *   estão na primeira tela).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   descricao: string|null,
 *   canais: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     titulo: string|null,
 *     valor: string|null,
 *     link: string|null,
 *   }>,
 * } $data
 */

defined('ABSPATH') || exit;

$canais = $data['canais'] ?? [];

// card do canal (React: CanalCard) — mesmo visual com e sem link
$cardClass = 'flex h-full items-center gap-6 rounded-xl border border-brand-gray-border bg-white p-6 shadow-xs transition-shadow hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple sm:p-8 md:gap-8 md:p-12';
$entrada = 0; // entrada do hero só em CSS (data-hero-reveal): posição de cada elemento na cascata
?>
<section id="hero" class="relative isolate w-full">
  <div class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-40 md:pb-48 lg:pt-40 lg:pb-56">
    <div class="container flex flex-col items-center gap-6 text-center md:gap-9">
      <?php if ($data['eyebrow']) : ?>
        <span class="font-sans text-body-lg text-white"><?= esc_html($data['eyebrow']) ?></span>
      <?php endif; ?>
      <?php if ($data['titulo']) : ?>
        <?php // text-3xl/tight: entrelinha 1.25 só no celular — no md o text-h1 volta a ter a dele (1.15), como no React ?>
        <h1 <?= fw_attrs(['data-hero-reveal' => $entrada++]) ?> class="max-w-[280px] font-heading text-3xl/tight font-medium text-white sm:max-w-md md:max-w-xl md:text-h1 lg:max-w-2xl"><?= esc_html($data['titulo']) ?></h1>
      <?php endif; ?>
      <?php if ($data['descricao']) : ?>
        <p <?= fw_attrs(['data-hero-reveal' => $entrada++]) ?> class="max-w-2xl font-sans text-body-lg text-white"><?= esc_html($data['descricao']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($canais) : ?>
    <div class="relative z-10 container -mt-28 pb-8 sm:-mt-24 md:-mt-28 md:pb-10 lg:-mt-24">
      <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($canais as $canal) :
          $href = fw_url($canal['link']);
          ?>
          <li <?= fw_attrs(['data-hero-reveal' => $entrada++]) ?>>
            <?php if ($href) : ?>
              <a <?= fw_attrs(['href' => $href, 'class' => $cardClass] + fw_target('_blank')) ?>>
            <?php else : ?>
              <div class="<?= esc_attr($cardClass) ?>">
            <?php endif; ?>
              <?= fw_img($canal['icone'], ['class' => 'size-[52px] shrink-0', 'loading' => false]) ?>
              <?php if ($canal['titulo'] || $canal['valor']) : ?>
                <span class="flex flex-col gap-2">
                  <?php if ($canal['titulo']) : ?>
                    <span class="font-sans text-body-sm text-brand-purple-dark/70"><?= esc_html($canal['titulo']) ?></span>
                  <?php endif; ?>
                  <?php if ($canal['valor']) : ?>
                    <span class="font-sans text-body-lg text-brand-purple-dark"><?= esc_html($canal['valor']) ?></span>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            <?php if ($href) : ?>
              </a>
            <?php else : ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>
