<?php
/**
 * Arquivo (categoria, tag, data, taxonomia, CPT com arquivo): título e descrição do
 * arquivo + lista de posts com paginação. Incluído pelo index.php (get_template_part).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<main class="container py-12 md:py-16 lg:py-20">
	<div class="mx-auto max-w-3xl">
		<header>
			<?php the_archive_title( '<h1 class="text-3xl font-bold tracking-tight md:text-4xl">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="mt-4 text-lg text-muted-foreground">', '</div>' ); ?>
		</header>

		<div class="mt-10">
			<?php
			Part::render(
				'shared/post-list',
				array(
					'empty'      => __( 'Nenhum post encontrado neste arquivo.', 'upsites' ),
					'searchForm' => true,
				)
			);
			?>
		</div>
	</div>
</main>
