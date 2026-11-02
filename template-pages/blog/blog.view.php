<?php
/**
 * Blog — compõe as seções de template-parts/includes/blog/ na ordem da página (React: BlogView em
 * app/blog/blog.view.tsx). Renderizado pelo index.php (BlogModule::request), não pelo app.php.
 * $data = retorno do BlogController::page(); seção null = oculta pelo editor (Part não renderiza).
 * A lista de posts não é seção ACF: é a parte compartilhada shared/post-list (cards + paginação),
 * a mesma da busca.
 *
 * @var array{
 *   hero: array|null,
 *   listaPost: array{posts: list<array>, paginaAtual: int, totalPaginas: int, totalPosts: int, categoriaAtual: string, paginas: list<array>},
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('blog/hero', $data['hero']); ?>
  <?php Part::render('shared/post-list', ['id' => 'posts', 'empty' => __('Nenhum post encontrado.', 'upsites')] + $data['listaPost']); ?>
</main>
