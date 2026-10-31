<?php
/**
 * Template das páginas com modelo "Página · {Nome}" (fw:{slug}) e da página inicial,
 * escolhido por Core\Framework\Bootstrap::catchAll(). Renderiza no servidor o módulo
 * template-pages/{slug}/ ({slug}.view.php) com os dados do controller.
 *
 * Outros templates (single.php, archive.php…) fazem o mesmo chamando View::render().
 *
 * @package WordPress
 */

use Core\Framework\RouteResolver;
use Core\Framework\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$route = RouteResolver::current();

// loop do WP (the_post): plugins que dependem de in_the_loop()/loop_start (compartilhamento,
// relacionados, contadores) funcionam na página de módulo como numa página comum
if ( have_posts() ) {
	the_post();
}

View::render( $route['module'], $route['pageId'] );

// fecha o loop: no fim, have_posts() dispara loop_end e volta ao início
have_posts();
