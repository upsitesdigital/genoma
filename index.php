<?php
/**
 * Fallback geral da hierarquia do WP: tudo que não tem template mais específico
 * (single.php, page.php, 404.php…) cai aqui.
 *
 * - Busca (?s=) → template-parts/search.php: módulo resultado-pesquisa (página inteira).
 * - Blog → módulo blog com ?categoria= e ?pagina= da URL (BlogModule::request): a página com o
 *   modelo fw:blog (o BlogModule a desvia do app.php para cá — o app.php não passa $params) e a
 *   página de posts do WP (Configurações → Leitura), como no tema React.
 * - Autor e arquivo (tag, data, taxonomia; categoria é redirecionada para o blog pelo BlogModule) →
 *   template-parts/{author,archive}.php; 404 → template-parts/error.php.
 *
 * As demais páginas com modelo fw:{slug} e a página inicial (estática) não passam por aqui:
 * vão para template-parts/app.php (Core\Framework\Bootstrap::catchAll).
 *
 * @package UpWork
 */

use App\Blog\BlogModule;
use Core\Framework\Part;
use Core\Framework\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// busca: o search.php chama o View::render, que já imprime o header e o footer
if ( is_search() ) {
	get_template_part( 'template-parts/search' );
	return;
}

$upsites_blog = class_exists( BlogModule::class ) ? BlogModule::request() : null;
if ( $upsites_blog ) {
	if ( null === $upsites_blog['params'] ) {
		// ?pagina= além da última página do blog: 404 de verdade (não uma lista vazia indexada)
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		$upsites_404 = get_query_template( '404' );
		if ( $upsites_404 ) {
			require $upsites_404;
			return;
		}
	} else {
		// página fw:blog: o loop do WP (the_post), como no app.php — plugins que dependem de
		// in_the_loop()/loop_start funcionam como numa página comum
		$upsites_blog_pagina = is_page();
		if ( $upsites_blog_pagina && have_posts() ) {
			the_post();
		}

		View::render( 'blog', $upsites_blog['pageId'], $upsites_blog['params'] );

		if ( $upsites_blog_pagina ) {
			have_posts(); // fecha o loop (loop_end)
		}
		return;
	}
}

get_header();

if ( is_author() ) {
	get_template_part( 'template-parts/author' );
} elseif ( is_archive() ) {
	get_template_part( 'template-parts/archive' );
} elseif ( is_404() ) {
	get_template_part( 'template-parts/error' );
} else {
	// sem módulo de blog (ex.: "Últimos posts" na página inicial): lista da consulta principal
	$upsites_posts_page = (int) get_option( 'page_for_posts' );
	$upsites_heading    = is_home() && $upsites_posts_page ? get_the_title( $upsites_posts_page ) : __( 'Últimos posts', 'upsites' );
	?>
	<main>
		<section class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-16 md:pb-20 lg:pt-40 lg:pb-24">
			<div class="container">
				<h1 class="max-w-3xl font-heading text-3xl leading-tight font-medium text-white md:text-h1 md:leading-[1.15]"><?php echo esc_html( $upsites_heading ); ?></h1>
			</div>
		</section>
		<?php Part::render( 'shared/post-list' ); ?>
	</main>
	<?php
}

get_footer();
