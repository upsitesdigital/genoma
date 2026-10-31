<?php
/**
 * Página comum (sem modelo "Página · {Nome}"): título e conteúdo do editor de blocos.
 *
 * Páginas com modelo fw:{slug} e a página inicial NÃO passam por aqui: o
 * Core\Framework\Bootstrap::catchAll() as manda para template-parts/app.php (módulo).
 *
 * @package UpWork
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>
<main class="container py-12 md:py-16 lg:py-20">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'mx-auto max-w-3xl' ); ?>>
			<?php the_title( '<h1 class="text-3xl font-bold tracking-tight md:text-4xl lg:text-5xl">', '</h1>' ); ?>

			<div class="entry-content mt-8">
				<?php the_content(); ?>
			</div>

			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="mt-8 flex flex-wrap gap-2 text-sm" aria-label="' . esc_attr__( 'Páginas', 'upsites' ) . '"><span class="font-semibold">' . esc_html__( 'Páginas:', 'upsites' ) . '</span>',
					'after'  => '</nav>',
				)
			);

			edit_post_link(
				esc_html__( 'Editar', 'upsites' ),
				'<p class="mt-8 text-sm">',
				'</p>',
				null,
				'font-semibold text-primary hover:underline'
			);

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
