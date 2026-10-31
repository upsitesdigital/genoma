<?php
/**
 * Lista de posts da consulta principal do WP (o loop): um shared/post-item por post,
 * paginação (the_posts_pagination) e mensagem quando não há resultados.
 * Usado por index.php e template-parts/{archive,search,author}.php (arquivo e autor ligam
 * searchForm; a busca não, porque o search.php já mostra o formulário no topo).
 *
 *   Part::render('shared/post-list', ['empty' => __('Nenhum post encontrado.', 'upsites')]);
 *
 * @var array{empty?: string, searchForm?: bool} $data
 *      empty = texto sem resultados · searchForm = mostra get_search_form() quando não há resultados.
 */

defined('ABSPATH') || exit;
?>
<?php if (have_posts()) : ?>
  <ul class="grid gap-10" role="list">
    <?php while (have_posts()) : the_post(); ?>
      <li><?php Core\Framework\Part::render('shared/post-item'); ?></li>
    <?php endwhile; ?>
  </ul>

  <?php
  the_posts_pagination([
    'class'              => 'pagination mt-12 [&_.nav-links]:flex [&_.nav-links]:flex-wrap [&_.nav-links]:gap-2 [&_.page-numbers]:inline-flex [&_.page-numbers]:min-w-10 [&_.page-numbers]:justify-center [&_.page-numbers]:rounded-md [&_.page-numbers]:border [&_.page-numbers]:px-3 [&_.page-numbers]:py-2 [&_.page-numbers]:text-sm [&_.current]:bg-primary [&_.current]:text-primary-foreground',
    'aria_label'         => __('Paginação dos posts', 'upsites'),
    'screen_reader_text' => __('Navegação dos posts', 'upsites'),
    'prev_text'          => __('Anterior', 'upsites'),
    'next_text'          => __('Próxima', 'upsites'),
  ]);
  ?>
<?php else : ?>
  <div class="rounded-xl border border-border bg-card p-8 text-center">
    <p class="text-muted-foreground"><?= esc_html($data['empty'] ?? __('Nenhum post encontrado.', 'upsites')) ?></p>
    <?php if (!empty($data['searchForm'])) : ?>
      <div class="mx-auto mt-6 max-w-md"><?php get_search_form(['aria_label' => __('Buscar no site', 'upsites')]); ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>
