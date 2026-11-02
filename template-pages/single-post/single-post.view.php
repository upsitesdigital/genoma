<?php
/**
 * Post individual — compõe as seções de template-parts/includes/single-post/ na ordem da página
 * (React: app/single-post/single-post.view.tsx). $data = retorno do SinglePostController::page().
 * Formato de cada seção: @var no topo de template-parts/includes/single-post/{seção}/{seção}.php.
 * Sem comentários, como no React.
 *
 * @var array{
 *   hero: array|null,
 *   conteudo: array{id: int}|null,
 *   vejaTambem: list<array>,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('single-post/hero', $data['hero']); ?>
  <?php Part::render('single-post/conteudo', $data['conteudo']); ?>
  <?php Part::render('single-post/veja-tambem', $data['vejaTambem']); ?>
</main>
