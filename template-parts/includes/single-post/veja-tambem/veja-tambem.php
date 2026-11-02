<?php
/**
 * Post · Veja também — até 3 posts relacionados em cards (React: VejaTambemSection em
 * app/single-post/single-post.view.tsx; <section id="veja-tambem">).
 * Dados: SinglePostController → 'vejaTambem' (mesma categoria; faltando, os mais recentes).
 *
 * - Lista vazia → a seção não aparece (como no React).
 * - Card inteiro é o link do post; "Saiba mais" é só visual (span), como no React.
 * - imagem: a destacada ou a padrão do tema; sem alt → o título do post.
 *
 * @var list<array{
 *   id: int,
 *   titulo: string,
 *   resumo: string,
 *   data: string,
 *   link: string,
 *   categoria: string,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * }> $data
 */

defined('ABSPATH') || exit;

if (!$data) return;
?>
<section id="veja-tambem" class="w-full">
  <div class="container flex flex-col gap-9 pb-16 md:pb-20 lg:pb-24">
    <h2 data-reveal class="font-heading text-h2 font-normal text-brand-purple-dark"><?php esc_html_e('Veja também', 'upsites'); ?></h2>

    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($data as $item) : ?>
        <a <?= fw_attrs([
          'href'        => fw_url($item['link']),
          'data-reveal' => true,
          'class'       => 'flex flex-col gap-9 rounded-2xl bg-brand-purple-subtle p-8 transition-shadow hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple',
        ]) ?>>
          <div class="flex flex-col gap-9">
            <?= fw_img($item['imagem'], [
              'alt'   => ($item['imagem']['alt'] ?? '') ?: $item['titulo'],
              'class' => 'h-[170px] w-full rounded-lg object-cover',
              'sizes' => '(min-width: 1400px) 368px, (min-width: 1024px) 27vw, (min-width: 768px) 42vw, calc(100vw - 104px)',
            ], 'medium_large') ?>
            <div class="flex flex-col gap-4">
              <h3 class="font-heading text-h4 leading-snug font-medium text-brand-purple-dark"><?= esc_html($item['titulo']) ?></h3>
              <p class="line-clamp-3 font-sans text-body-sm text-brand-gray-text"><?= esc_html($item['resumo']) ?></p>
            </div>
          </div>
          <span class="inline-flex w-full shrink-0 items-center justify-center rounded-full bg-brand-purple px-4 py-3.5 font-sans text-body-sm font-semibold text-white transition-opacity hover:opacity-90 lg:w-fit"><?php esc_html_e('Saiba mais', 'upsites'); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
