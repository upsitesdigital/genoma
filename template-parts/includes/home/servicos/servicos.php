<?php
/**
 * Serviços — grade de cards (React: ServicosSection em app/home/home.view.tsx; <section id="servicos">).
 * Dados: HomeController → 'servicos' (null = "Ocultar seção" → nem chega aqui).
 *
 * - itens vazio → a seção não aparece (como no React).
 * - Mostra 3 cards; com mais de 3, o botão "Ver mais"/"Ver menos" expande (VerMaisButton) e o cta
 *   da seção não aparece; com 3 ou menos, aparece o cta (se tiver texto).
 * - CTA: texto null → o botão não aparece; link null → href "#" (como no React).
 * - Store `upwork/home-servicos` (servicos.store.js): `context.expanded` mostra os cards além do 3º
 *   (todos já vêm no HTML, os extras com `hidden`).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   itens: list<array{
 *     imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     titulo: string|null,
 *     descricao: string|null,
 *     cta: array{texto: string|null, link: string|null},
 *   }>,
 *   cta: array{texto: string|null, link: string|null},
 * } $data
 */

defined('ABSPATH') || exit;

$itens = $data['itens'] ?? [];
if (!$itens) return;

$visiveis = 3; // VISIVEIS_INICIAL do VerMaisButton
$hasMore  = count($itens) > $visiveis;

// VerMaisButton do React: mesmo visual para o "Ver mais" e para o link do CTA
$verMaisClass = 'inline-flex w-full items-center justify-center gap-2 rounded-full border border-brand-purple px-6 py-4 font-heading text-base font-medium text-brand-purple transition-colors hover:bg-brand-purple hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple md:px-7 md:py-5 md:text-h5 lg:w-auto';
?>
<section
  id="servicos"
  class="w-full py-12 md:py-20 lg:py-24"
  <?php if ($hasMore) : ?>
    data-wp-interactive="upwork/home-servicos"
    <?= wp_interactivity_data_wp_context(['expanded' => false]) ?>
  <?php endif; ?>
>
  <div class="container">
    <?php if ($data['eyebrow'] || $data['titulo']) : ?>
      <div class="flex max-w-2xl flex-col gap-2">
        <?php if ($data['eyebrow']) : ?>
          <span data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></span>
        <?php endif; ?>
        <?php if ($data['titulo']) : ?>
          <h2 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h2>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <ul id="servicos-lista" class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 md:mt-10 lg:grid-cols-3 lg:gap-8">
      <?php foreach ($itens as $i => $item) :
        $extra = $i >= $visiveis;
        ?>
        <li
          data-reveal
          class="flex flex-col gap-9 rounded-2xl bg-brand-light-purple p-6 md:p-8"
          <?php if ($extra) : ?>
            hidden
            data-wp-bind--hidden="!context.expanded"
          <?php endif; ?>
        >
          <?php if ($item['imagem'] || $item['titulo'] || $item['descricao']) : ?>
            <div class="flex flex-col gap-9">
              <?= fw_img($item['imagem'], [
                'class'         => 'h-[170px] w-full rounded-lg object-cover',
                'sizes'         => '(min-width: 1400px) 368px, (min-width: 1024px) 27vw, (min-width: 640px) 44vw, 90vw',
                // lazy também nos cards escondidos (display:none): a imagem só baixa quando o
                // "Ver mais" abre o card — antes, 3 imagens invisíveis vinham com a página
              ]) ?>
              <?php if ($item['titulo'] || $item['descricao']) : ?>
                <div class="flex flex-col gap-4">
                  <?php if ($item['titulo']) : ?>
                    <h3 class="font-heading text-h5 font-medium text-brand-purple-dark"><?= esc_html($item['titulo']) ?></h3>
                  <?php endif; ?>
                  <?php if ($item['descricao']) : ?>
                    <p class="font-sans text-body-sm text-brand-gray-text"><?= esc_html($item['descricao']) ?></p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($item['cta']['texto']) : ?>
            <a <?= fw_attrs([
              'href'  => fw_url($item['cta']['link']) ?? '#',
              'class' => 'inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand-purple px-4 py-3.5 font-sans text-base font-semibold text-white transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple lg:w-fit',
            ]) ?>><?= esc_html($item['cta']['texto']) ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($hasMore) : ?>
      <div class="mt-10 flex justify-center md:mt-12">
        <button
          type="button"
          data-reveal
          class="<?= fw_cn('group', $verMaisClass) ?>"
          aria-controls="servicos-lista"
          aria-expanded="false"
          data-wp-bind--aria-expanded="context.expanded"
          data-wp-on--click="actions.toggle"
        >
          <span data-wp-bind--hidden="context.expanded"><?php esc_html_e('Ver mais', 'upsites'); ?></span>
          <span hidden data-wp-bind--hidden="!context.expanded"><?php esc_html_e('Ver menos', 'upsites'); ?></span>
          <?php // ChevronDown (lucide): vira para cima com a lista aberta ?>
          <svg class="size-4 transition-transform duration-300 group-aria-expanded:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>
        </button>
      </div>
    <?php elseif ($data['cta']['texto']) : ?>
      <div class="mt-10 flex justify-center md:mt-12">
        <a <?= fw_attrs(['href' => fw_url($data['cta']['link']) ?? '#', 'data-reveal' => true, 'class' => $verMaisClass]) ?>><?= esc_html($data['cta']['texto']) ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
