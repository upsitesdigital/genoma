<?php
/**
 * Hero do blog — etiqueta, título, busca e pílulas de categoria (React: HeroSection + CategoriaPill
 * em app/blog/blog.view.tsx; <section id="hero">).
 * Dados: BlogController → 'hero' (null = "Ocultar seção" → nem chega aqui).
 *
 * - eyebrow é o <h1> da página e titulo o <h2> (como no React); null → o elemento não aparece.
 * - Busca: formulário GET comum para /?s={termo} (o React montava a mesma URL no JS) → a busca do
 *   WP, que renderiza o módulo resultado-pesquisa.
 * - Pílulas: links /blog/?categoria={slug} (carregamento normal da página); a ativa (`destaque`) leva
 *   aria-current. No celular/tablet a faixa rola na horizontal (sem barra), do lg em diante distribui.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   buscaPlaceholder: string|null,
 *   buscaIcone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   categorias: list<array{titulo: string, link: string, destaque: bool}>,
 * } $data
 */

defined('ABSPATH') || exit;

$busca = $data['buscaPlaceholder'] ?? null;
// rótulo do campo e do botão para leitor de tela: o placeholder do painel (React: aria-label={buscaPlaceholder})
$rotulo = $busca ?: __('Buscar', 'upsites');
?>
<section id="hero" class="relative isolate w-full">
  <div class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-16 md:pb-20 lg:pt-40 lg:pb-24">
    <div class="container flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
      <?php if ($data['eyebrow'] || $data['titulo']) : ?>
        <div class="flex max-w-xl flex-col gap-3 md:gap-4">
          <?php if ($data['eyebrow']) : ?>
            <h1 data-reveal class="font-sans text-body-lg text-white"><?= esc_html($data['eyebrow']) ?></h1>
          <?php endif; ?>
          <?php if ($data['titulo']) : ?>
            <?php // md:leading-[1.15]: no React (Tailwind v3) o md:text-h1 trocava a altura de linha do leading-tight ?>
            <h2 data-reveal class="font-heading text-3xl leading-tight font-medium text-white sm:max-w-md md:text-h1 md:leading-[1.15] lg:max-w-md"><?= esc_html($data['titulo']) ?></h2>
          <?php endif; ?>
        </div>
      <?php endif; ?>

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
          <?= fw_attrs(['placeholder' => $busca, 'aria-label' => $rotulo]) ?>
          class="w-full bg-transparent font-sans text-body-sm text-white placeholder:text-white/90 focus:outline-hidden"
        >
        <button type="submit" aria-label="<?= esc_attr($rotulo) ?>" class="shrink-0 rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
          <?= fw_img($data['buscaIcone'], ['alt' => '', 'loading' => false, 'class' => 'h-6 w-6']) ?>
        </button>
      </form>
    </div>
  </div>

  <?php if ($data['categorias']) : ?>
    <div class="relative z-10 container -mt-8 pb-10 md:-mt-10 lg:-mt-9">
      <nav aria-label="<?php esc_attr_e('Categorias do blog', 'upsites'); ?>" class="flex items-center gap-1 overflow-x-auto rounded-full bg-white p-3 shadow-lg [scrollbar-width:none] md:gap-2 lg:justify-between lg:overflow-visible [&::-webkit-scrollbar]:hidden">
        <?php foreach ($data['categorias'] as $categoria) : ?>
          <a <?= fw_attrs([
            'href'         => fw_url($categoria['link']),
            'aria-current' => $categoria['destaque'] ? 'page' : null,
            // sem text-body-sm: no React o cn() (tailwind-merge) o descartava (lia como cor, em conflito
            // com text-brand-*), e as pílulas ficavam com a fonte herdada (16px/24px)
            'class'        => fw_cn(
              'shrink-0 rounded-full px-4 py-3 font-sans whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-purple md:px-6',
              $categoria['destaque']
                ? 'border border-brand-purple-accent bg-[rgba(41,19,154,0.1)] text-brand-purple'
                : 'text-brand-gray-text hover:text-brand-purple'
            ),
          ]) ?>><?= esc_html($categoria['titulo']) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>
</section>
