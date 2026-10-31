<?php
/**
 * Post (e CPT sem single-{cpt}.php próprio) em PHP puro: título, data/autor, imagem
 * destacada, conteúdo do editor, navegação entre posts e comentários.
 *
 * Projeto com módulo de post (template-pages/single-post/, `template: false`): troque
 * TODO o corpo abaixo por
 *
 *   US_set_post_views( get_the_ID() );
 *   Core\Framework\View::render( 'single-post', get_the_ID() );
 *
 * (View::render já imprime header e footer). CPT com layout próprio: single-{cpt}.php.
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

		// contador de visualizações (desligado por padrão: filtro upwork/post_views)
		US_set_post_views( get_the_ID() );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'mx-auto max-w-3xl' ); ?>>
			<header>
				<?php the_title( '<h1 class="text-3xl font-bold tracking-tight md:text-4xl lg:text-5xl">', '</h1>' ); ?>

				<?php if ( 'post' === get_post_type() ) : ?>
					<p class="mt-4 text-sm text-muted-foreground">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span aria-hidden="true">·</span>
						<?php
						printf(
							/* translators: %s: nome do autor do post, com link para a página do autor. */
							esc_html__( 'por %s', 'upsites' ),
							'<a class="hover:text-primary" href="' . esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>'
						);
						?>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="mt-8">
					<?php the_post_thumbnail( 'large', array( 'class' => 'w-full rounded-xl object-cover', 'fetchpriority' => 'high', 'loading' => false ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="entry-content mt-8">
				<?php the_content(); ?>
			</div>

			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="mt-8 flex flex-wrap gap-2 text-sm" aria-label="' . esc_attr__( 'Páginas do post', 'upsites' ) . '"><span class="font-semibold">' . esc_html__( 'Páginas:', 'upsites' ) . '</span>',
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
			?>

			<?php
			the_post_navigation(
				array(
					'class'              => 'mt-12 border-t pt-8 [&_.nav-links]:grid [&_.nav-links]:gap-4 md:[&_.nav-links]:grid-cols-2 md:[&_.nav-next]:text-right [&_a]:font-semibold [&_a:hover]:text-primary',
					'aria_label'         => __( 'Navegação entre posts', 'upsites' ),
					'screen_reader_text' => __( 'Navegação entre posts', 'upsites' ),
					'prev_text'          => '<span class="block text-sm font-normal text-muted-foreground">' . esc_html__( 'Anterior', 'upsites' ) . '</span>%title',
					'next_text'          => '<span class="block text-sm font-normal text-muted-foreground">' . esc_html__( 'Próximo', 'upsites' ) . '</span>%title',
				)
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
