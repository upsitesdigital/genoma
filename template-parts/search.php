<?php
/**
 * Resultados da busca: termo buscado, formulário de busca e lista de resultados com
 * paginação. Incluído pelo index.php (get_template_part).
 * Obs.: a busca nativa não indexa campos ACF (use Relevanssi/SearchWP — ver README).
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
			<h1 class="text-3xl font-bold tracking-tight md:text-4xl">
				<?php
				printf(
					/* translators: %s: termo buscado. */
					esc_html__( 'Resultados para: %s', 'upsites' ),
					'<span class="text-primary">' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
			<div class="mt-6"><?php get_search_form( array( 'aria_label' => __( 'Buscar no site', 'upsites' ) ) ); ?></div>
		</header>

		<div class="mt-10">
			<?php Part::render( 'shared/post-list', array( 'empty' => __( 'Nada encontrado. Tente buscar com outras palavras.', 'upsites' ) ) ); ?>
		</div>
	</div>
</main>
