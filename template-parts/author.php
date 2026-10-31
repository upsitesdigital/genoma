<?php
/**
 * Página do autor: nome, avatar e biografia + lista dos posts dele com paginação.
 * Incluído pelo index.php (get_template_part).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$author = get_queried_object();
$author = $author instanceof WP_User ? $author : null;
?>
<main class="container py-12 md:py-16 lg:py-20">
	<div class="mx-auto max-w-3xl">
		<header class="flex items-center gap-4">
			<?php if ( $author ) : ?>
				<?php echo get_avatar( $author->ID, 64, '', '', array( 'class' => 'size-16 rounded-full' ) ); ?>
			<?php endif; ?>
			<div>
				<p class="text-sm font-semibold text-primary"><?php esc_html_e( 'Autor', 'upsites' ); ?></p>
				<h1 class="text-3xl font-bold tracking-tight md:text-4xl"><?php echo esc_html( $author ? $author->display_name : '' ); ?></h1>
			</div>
		</header>

		<?php if ( $author && $author->description ) : ?>
			<div class="mt-4 text-lg text-muted-foreground"><?php echo wp_kses_post( wpautop( $author->description ) ); ?></div>
		<?php endif; ?>

		<div class="mt-10">
			<?php
			Part::render(
				'shared/post-list',
				array(
					'empty'      => __( 'Este autor ainda não publicou posts.', 'upsites' ),
					'searchForm' => true,
				)
			);
			?>
		</div>
	</div>
</main>
