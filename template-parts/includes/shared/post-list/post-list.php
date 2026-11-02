<?php
/**
 * Lista de posts em cards (grade de 1, 2 e 3 colunas) com paginação em pílulas numeradas (01, 02…) —
 * a ListaPostSection do blog e a ListaResultadosSection da busca do tema React
 * (app/blog/blog.view.tsx, app/resultado-pesquisa/resultado-pesquisa.view.tsx). Um shared/post-item
 * por post. As pílulas são links (carregamento normal da página; no React eram botões com estado).
 *
 * Dois modos:
 *  - com `posts`: os dados de um controller (BlogController → listaPost, ResultadoPesquisaController
 *    → resultados), com os links prontos em `paginas`;
 *  - sem `posts`: o loop da consulta principal do WP (arquivo e autor — template-parts/*.php), com a
 *    paginação do WP (get_pagenum_link).
 *
 *   Part::render('shared/post-list', ['id' => 'posts', 'empty' => __('Nenhum post encontrado.', 'upsites')] + $data['listaPost']);
 *   Part::render('shared/post-list', ['empty' => __('Nenhum post encontrado.', 'upsites'), 'searchForm' => true]);
 *
 * @var array{
 *   id?: string,
 *   posts?: list<array{id: int, titulo: string, resumo: string, data: string, link: string, categoria: string, imagem: array|null}>,
 *   paginas?: list<array{numero: int, url: string, atual: bool}>,
 *   totalPosts?: int,
 *   contagem?: bool,
 *   categoria?: bool,
 *   empty?: string|null,
 *   searchForm?: bool,
 * } $data
 *   id = id da <section> (âncora: #posts no blog, #resultados na busca) · contagem = "N resultados
 *   encontrados" acima da grade · categoria = categoria no card · empty = texto sem resultados ·
 *   searchForm = formulário de busca (searchform.php) abaixo do texto sem resultados.
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;

$loop      = !isset($data['posts']);
$categoria = !empty($data['categoria']);
$paginas   = $data['paginas'] ?? [];
$total     = (int) ($data['totalPosts'] ?? 0);

if ($loop) {
  // consulta principal: total e paginação do WP
  global $wp_query;
  $total  = (int) $wp_query->found_posts;
  $ultima = (int) $wp_query->max_num_pages;
  $atual  = max(1, (int) get_query_var('paged'));
  $paginas = $ultima > 1 ? array_map(static fn(int $n): array => [
    'numero' => $n,
    'url'    => (string) get_pagenum_link($n, false),
    'atual'  => $n === $atual,
  ], range(1, $ultima)) : [];
}

$temPosts = $loop ? have_posts() : !empty($data['posts']);
$id       = isset($data['id']) && $data['id'] !== '' ? $data['id'] : null;
?>
<?php if (!$temPosts) : ?>
  <section <?= fw_attrs(['id' => $id, 'class' => 'container py-16 text-center font-sans text-body-sm text-brand-gray-text md:py-20']) ?>>
    <p><?= esc_html(($data['empty'] ?? '') ?: __('Nenhum post encontrado.', 'upsites')) ?></p>
    <?php if (!empty($data['searchForm'])) : ?>
      <div class="mx-auto mt-6 max-w-md text-left"><?php get_search_form(['aria_label' => __('Buscar no site', 'upsites')]); ?></div>
    <?php endif; ?>
  </section>
<?php else : ?>
  <section <?= fw_attrs(['id' => $id, 'class' => 'container py-12 md:py-20 lg:py-24']) ?>>
    <?php if (!empty($data['contagem'])) : ?>
      <p data-reveal class="mb-8 font-sans text-body text-brand-gray-text md:mb-10">
        <?php
        /* translators: %s: quantidade de resultados da busca. */
        echo esc_html(sprintf(_n('%s resultado encontrado', '%s resultados encontrados', $total, 'upsites'), number_format_i18n($total)));
        ?>
      </p>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
      <?php if ($loop) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php Part::render('shared/post-item', ['categoria' => $categoria]); ?>
        <?php endwhile; ?>
      <?php else : ?>
        <?php foreach ($data['posts'] as $item) : ?>
          <?php Part::render('shared/post-item', ['post' => $item, 'categoria' => $categoria]); ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if (count($paginas) > 1) : ?>
      <nav aria-label="<?php esc_attr_e('Paginação', 'upsites'); ?>" class="mt-10 flex flex-wrap items-center justify-center gap-3 md:mt-12 md:gap-4">
        <?php foreach ($paginas as $pagina) : ?>
          <a <?= fw_attrs([
            'href'         => fw_url($pagina['url']),
            'aria-current' => $pagina['atual'] ? 'page' : null,
            /* translators: %d: número da página da lista. */
            'aria-label'   => sprintf(__('Página %d', 'upsites'), $pagina['numero']),
            'data-reveal'  => true,
            'class'        => fw_cn(
              'flex h-11 w-11 items-center justify-center rounded-full border font-heading text-base font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple md:h-12 md:w-12 md:text-h4',
              $pagina['atual']
                ? 'border-brand-purple text-brand-purple'
                : 'border-brand-gray-text text-brand-gray-text hover:border-brand-purple hover:text-brand-purple'
            ),
          ]) ?>><?= esc_html(sprintf('%02d', $pagina['numero'])) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
  </section>
<?php endif; ?>
