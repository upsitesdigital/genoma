<?php
/**
 * Hero da busca — etiqueta, "Resultados para "termo"" e o campo de busca já com o termo (React:
 * HeroSection em app/resultado-pesquisa/resultado-pesquisa.view.tsx; <section id="hero">).
 * Dados: ResultadoPesquisaController → 'hero' (null = "Ocultar seção" → nem chega aqui).
 *
 * - eyebrow é o <h1> da página (null → não aparece); o <h2> é montado do termo (não é campo):
 *   sem termo (a própria página de modelo, /resultado-da-busca/) → "Buscar no site".
 * - Busca: formulário GET comum para /?s={termo} (o React montava a mesma URL no JS).
 *
 * @var array{
 *   eyebrow: string|null,
 *   buscaPlaceholder: string|null,
 *   buscaIcone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   termo: string,
 * } $data
 */

defined('ABSPATH') || exit;

$busca = $data['buscaPlaceholder'] ?? null;
// rótulo do campo e do botão para leitor de tela: o placeholder do painel (React: aria-label={buscaPlaceholder})
$rotulo = $busca ?: __('Buscar', 'upsites');
?>
<section id="hero" class="relative isolate w-full">
  <?php // lg:pt-40 (como o hero do blog): o header fixo vai até ~128px — com o lg:pt-24 do React, a etiqueta e o título ficavam por baixo dele ?>
  <div class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-16 md:pb-20 lg:pt-40 lg:pb-24">
    <div class="container flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
      <div class="flex max-w-xl flex-col gap-3 md:gap-4">
        <?php if ($data['eyebrow']) : ?>
          <h1 data-reveal class="font-sans text-body-lg text-white"><?= esc_html($data['eyebrow']) ?></h1>
        <?php endif; ?>
        <?php // md:leading-[1.15]: no React (Tailwind v3) o md:text-h1 trocava a altura de linha do leading-tight ?>
        <h2 data-reveal class="font-heading text-3xl leading-tight font-medium text-white sm:max-w-md md:text-h1 md:leading-[1.15] lg:max-w-lg">
          <?php if ($data['termo'] !== '') : ?>
            <?php esc_html_e('Resultados para', 'upsites'); ?> <span class="italic">"<?= esc_html($data['termo']) ?>"</span>
          <?php else : ?>
            <?php esc_html_e('Buscar no site', 'upsites'); ?>
          <?php endif; ?>
        </h2>
      </div>

      <form
        role="search"
        method="get"
        action="<?= esc_url(home_url('/')) ?>"
        data-reveal
        class="flex w-full items-center justify-between gap-3 rounded-full border border-white/70 px-6 py-4 focus-within:border-white lg:w-[423px] lg:shrink-0"
      >
        <input
          type="search"
          name="s"
          value="<?= esc_attr($data['termo']) ?>"
          <?= fw_attrs(['placeholder' => $busca, 'aria-label' => $rotulo]) ?>
          class="w-full bg-transparent font-sans text-body-sm text-white placeholder:text-white/90 focus:outline-hidden"
        >
        <button type="submit" aria-label="<?= esc_attr($rotulo) ?>" class="shrink-0 rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
          <?= fw_img($data['buscaIcone'], ['alt' => '', 'loading' => false, 'class' => 'h-6 w-6']) ?>
        </button>
      </form>
    </div>
  </div>
</section>
