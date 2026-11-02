<?php
/**
 * Post · Hero — data, categoria, título, compartilhar e a imagem destacada sobre o degradê
 * (React: HeroSection em app/single-post/single-post.view.tsx; <section id="hero">).
 * Dados: SinglePostController → 'hero'.
 *
 * - categoria '' → só a data; categoriaLink '' → a categoria sem link (como no React).
 * - Compartilhar: e-mail, LinkedIn, WhatsApp e Facebook com a URL e o título do post (redes em nova aba).
 * - imagem: a destacada ou a padrão do tema (o controller resolve); sem alt → o título (como no React).
 *
 * @var array{
 *   titulo: string,
 *   data: string,
 *   categoria: string,
 *   categoriaLink: string,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   url: string,
 * } $data
 */

defined('ABSPATH') || exit;

// buildShareLinks do React (encodeURIComponent ≈ rawurlencode)
$url    = rawurlencode($data['url']);
$titulo = rawurlencode($data['titulo']);
$shares = [
  [
    'href'  => "mailto:?subject={$titulo}&body={$url}",
    'label' => __('Compartilhar por e-mail', 'upsites'),
    'novo'  => false,
    // lucide Mail
    'icone' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
  ],
  [
    'href'  => "https://www.linkedin.com/sharing/share-offsite/?url={$url}",
    'label' => __('Compartilhar no LinkedIn', 'upsites'),
    'novo'  => true,
    // lucide Linkedin
    'icone' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>',
  ],
  [
    'href'  => "https://wa.me/?text={$titulo}%20{$url}",
    'label' => __('Compartilhar no WhatsApp', 'upsites'),
    'novo'  => true,
    // lucide MessageCircle
    'icone' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
  ],
  [
    'href'  => "https://www.facebook.com/sharer/sharer.php?u={$url}",
    'label' => __('Compartilhar no Facebook', 'upsites'),
    'novo'  => true,
    // lucide Facebook
    'icone' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
  ],
];
?>
<section id="hero" class="relative isolate w-full">
  <div class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-40 md:pb-52 lg:pt-40 lg:pb-64">
    <div class="container">
      <div class="mx-auto flex max-w-3xl flex-col gap-4 md:gap-6">
        <?php // data    •    categoria: os espaços viram um só, como no React ?>
        <p data-reveal class="font-sans text-body text-white"><?= esc_html($data['data']) ?><?php if ($data['categoria']) : ?>    •    <?php if ($data['categoriaLink']) : ?><a href="<?= esc_url($data['categoriaLink']) ?>" class="hover:underline focus-visible:underline focus-visible:outline-none"><?= esc_html($data['categoria']) ?></a><?php else : ?><?= esc_html($data['categoria']) ?><?php endif; ?><?php endif; ?></p>

        <?php // v4: o leading-tight venceria o line-height do text-h1 (no React, a partir do md vale o do text-h1) ?>
        <h1 data-reveal class="font-heading text-3xl leading-tight font-medium text-white md:text-h1 md:leading-[1.15]"><?= esc_html($data['titulo']) ?></h1>

        <div class="flex items-center gap-6">
          <span class="font-sans text-body text-white"><?php esc_html_e('Compartilhe', 'upsites'); ?></span>
          <div class="flex items-center gap-3">
            <?php foreach ($shares as $share) : ?>
              <a <?= fw_attrs([
                'href'       => fw_url($share['href']),
                'aria-label' => $share['label'],
                'class'      => 'text-white transition-opacity hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
              ] + ($share['novo'] ? fw_target('_blank') : [])) ?>>
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $share['icone'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- formas fixas dos ícones (lucide), escritas acima ?></svg>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="relative z-10 container -mt-32 pb-10 md:-mt-40 md:pb-14 lg:-mt-56 lg:pb-16">
    <div class="mx-auto max-w-3xl">
      <?= fw_img($data['imagem'], [
        'alt'           => ($data['imagem']['alt'] ?? '') ?: $data['titulo'],
        'class'         => 'aspect-[800/432] w-full rounded-2xl object-cover shadow-xl',
        'sizes'         => '(min-width: 808px) 768px, calc(100vw - 40px)',
        'data-reveal'   => true,
        // LCP da página: sem lazy, com prioridade
        'fetchpriority' => 'high',
        'loading'       => false,
      ], 'large') ?>
    </div>
  </div>
</section>
