<?php
/**
 * Card de post — o PostCard do blog e o ResultadoCard da busca do tema React (app/blog/blog.view.tsx,
 * app/resultado-pesquisa/resultado-pesquisa.view.tsx): imagem, categoria (opcional), título, resumo
 * (3 linhas) e "Saiba mais". O card inteiro é o link do post. Usado pelo shared/post-list.
 *
 *   Part::render('shared/post-item', ['post' => $post, 'categoria' => true]);   // PostData de um controller
 *   Part::render('shared/post-item');                                            // dentro do loop, depois de the_post()
 *
 * - post: PostData (BlogController/ResultadoPesquisaController::post). Sem ele, o post atual do loop,
 *   montado com as mesmas regras (resumo do editor ou 20 palavras do conteúdo; sem imagem destacada →
 *   assets/diagnostico-veterinario-em-sp-74.png).
 * - categoria: mostra a 1ª categoria acima do título (busca); sem categoria (página) não aparece.
 * - Imagem sem texto alternativo → o título do post (como no React).
 *
 * @var array{
 *   post?: array{id: int, titulo: string, resumo: string, data: string, link: string, categoria: string, imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null},
 *   categoria?: bool,
 * } $data
 */

defined('ABSPATH') || exit;

$post = $data['post'] ?? null;

if (!$post) {
  // modo loop: o post atual, no mesmo formato dos controllers
  $atual = get_post();
  if (!$atual) return;

  $thumbId    = (int) get_post_thumbnail_id($atual);
  $src        = $thumbId ? wp_get_attachment_image_url($thumbId, 'medium_large') : false;
  $categorias = 'post' === $atual->post_type ? get_the_category($atual->ID) : [];
  $resumo     = '';
  if ($atual->post_password === '') {
    $resumo = $atual->post_excerpt !== '' ? wp_strip_all_tags($atual->post_excerpt) : wp_trim_words(wp_strip_all_tags($atual->post_content), 20, '…');
  }

  $post = [
    'titulo'    => (string) get_the_title($atual),
    'resumo'    => $resumo,
    'link'      => (string) get_permalink($atual),
    'categoria' => (string) ($categorias[0]->name ?? ''),
    'imagem'    => $src
      ? ['id' => $thumbId, 'src' => $src, 'alt' => (string) get_post_meta($thumbId, '_wp_attachment_image_alt', true), 'width' => null, 'height' => null, 'sizes' => []]
      : ['id' => 0, 'src' => get_template_directory_uri() . '/template-parts/includes/shared/post-item/assets/diagnostico-veterinario-em-sp-74.png', 'alt' => '', 'width' => 630, 'height' => 340, 'sizes' => []],
  ];
}

$alt = ($post['imagem']['alt'] ?? '') ?: wp_strip_all_tags($post['titulo']);
?>
<a <?= fw_attrs([
  'href'        => fw_url($post['link']),
  'data-reveal' => true,
  'class'       => 'flex flex-col gap-9 rounded-2xl bg-brand-purple-subtle p-6 transition-shadow hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple md:p-8',
]) ?>>
  <div class="flex flex-col gap-4">
    <?= fw_img($post['imagem'], ['alt' => $alt, 'class' => 'h-[170px] w-full rounded-lg object-cover'], 'medium_large') ?>
    <div class="flex flex-col gap-2">
      <?php if (!empty($data['categoria']) && $post['categoria'] !== '') : ?>
        <span class="font-sans text-body-sm text-brand-purple-accent"><?= esc_html($post['categoria']) ?></span>
      <?php endif; ?>
      <h3 class="font-heading text-h4 leading-snug font-medium text-brand-purple-dark"><?= esc_html($post['titulo']) ?></h3>
      <p class="line-clamp-3 font-sans text-body-sm text-brand-gray-text"><?= esc_html($post['resumo']) ?></p>
    </div>
  </div>
  <span class="inline-flex w-full shrink-0 items-center justify-center rounded-full bg-brand-purple px-4 py-3.5 font-sans text-body-sm font-semibold text-white transition-opacity hover:opacity-90 lg:w-fit"><?php esc_html_e('Saiba mais', 'upsites'); ?></span>
</a>
