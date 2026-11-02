<?php
/**
 * Página do autor: faixa no estilo do topo do blog com avatar, nome e biografia + os posts dele em
 * cards com paginação (shared/post-list no modo loop). Incluído pelo index.php (get_template_part).
 * (No tema React esta página dava 404.)
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$upsites_author = get_queried_object();
$upsites_author = $upsites_author instanceof WP_User ? $upsites_author : null;
?>
<main>
	<?php // faixa colorida: o header é fixo e transparente no desktop (texto branco), como no topo do blog ?>
	<section class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-16 md:pb-20 lg:pt-40 lg:pb-24">
		<div class="container">
			<div class="flex max-w-3xl flex-col gap-4">
				<div data-reveal class="flex items-center gap-4">
					<?php if ( $upsites_author ) : ?>
						<?php echo get_avatar( $upsites_author->ID, 64, '', '', array( 'class' => 'size-16 rounded-full' ) ); ?>
					<?php endif; ?>
					<div class="flex flex-col gap-1">
						<p class="font-sans text-body-lg text-white"><?php esc_html_e( 'Autor', 'upsites' ); ?></p>
						<h1 class="font-heading text-3xl leading-tight font-medium text-white md:text-h1 md:leading-[1.15]"><?php echo esc_html( $upsites_author ? $upsites_author->display_name : '' ); ?></h1>
					</div>
				</div>

				<?php if ( $upsites_author && $upsites_author->description ) : ?>
					<div data-reveal class="font-sans text-body-lg text-white/90"><?php echo wp_kses_post( wpautop( $upsites_author->description ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	Part::render(
		'shared/post-list',
		array(
			'empty'      => __( 'Este autor ainda não publicou posts.', 'upsites' ),
			'searchForm' => true,
		)
	);
	?>
</main>
