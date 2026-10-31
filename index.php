<?php
/**
 * Fallback geral da hierarquia do WP: tudo que não tem template mais específico
 * (single.php, page.php, 404.php…) cai aqui. Página de posts / blog → lista de posts;
 * autor, arquivo (categoria, tag, data, CPT) e busca → template-parts/{author,archive,search}.php.
 *
 * Páginas com modelo fw:{slug} e a página inicial (estática) não passam por aqui:
 * vão para template-parts/app.php (Core\Framework\Bootstrap::catchAll).
 * Projeto com módulo de blog: troque o corpo por View::render('blog', null, [...]) —
 * ver skill wordpress, "Quem chama View::render".
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

if ( is_author() ) {
	get_template_part( 'template-parts/author' );
} elseif ( is_archive() ) {
	get_template_part( 'template-parts/archive' );
} elseif ( is_search() ) {
	get_template_part( 'template-parts/search' );
} elseif ( is_404() ) {
	get_template_part( 'template-parts/error' );
} else {
	$posts_page = (int) get_option( 'page_for_posts' );
	$heading    = is_home() && $posts_page ? get_the_title( $posts_page ) : __( 'Últimos posts', 'upsites' );
	?>
	<main class="container py-12 md:py-16 lg:py-20">
		<div class="mx-auto max-w-3xl">
			<h1 class="text-3xl font-bold tracking-tight md:text-4xl"><?php echo esc_html( $heading ); ?></h1>
			<div class="mt-10">
				<?php Part::render( 'shared/post-list' ); ?>
			</div>
		</div>
	</main>
	<?php
}

get_footer();
