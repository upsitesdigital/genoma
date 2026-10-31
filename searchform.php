<?php
/**
 * Formulário de busca (get_search_form()) — usado no search.php, na lista de posts sem
 * resultados de arquivo e autor (shared/post-list com searchForm) e no 404 (shared/not-found
 * com search). Rótulo visível só para leitor de tela; foco visível no campo e no botão.
 *
 * $args vem do get_search_form( $args ): 'aria_label' (WP 5.2+) vira o aria-label do
 * <form role="search"> — distingue o landmark quando há mais de uma busca na página.
 *
 *   get_search_form( array( 'aria_label' => __( 'Buscar no site', 'upsites' ) ) );
 *
 * @package UpWork
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// id único: o formulário pode aparecer mais de uma vez na mesma página
$upsites_search_id    = wp_unique_id( 'search-' );
$upsites_search_label = isset( $args['aria_label'] ) ? (string) $args['aria_label'] : '';
?>
<form role="search" method="get" class="search-form flex gap-2" action="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo '' !== $upsites_search_label ? ' aria-label="' . esc_attr( $upsites_search_label ) . '"' : ''; ?>>
	<label for="<?php echo esc_attr( $upsites_search_id ); ?>" class="sr-only"><?php esc_html_e( 'Buscar por:', 'upsites' ); ?></label>
	<input
		type="search"
		id="<?php echo esc_attr( $upsites_search_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php echo esc_attr__( 'Buscar…', 'upsites' ); ?>"
		class="min-w-0 flex-1 rounded-md border border-border bg-background px-4 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-2 focus:outline-offset-2 focus:outline-primary"
	>
	<button type="submit" class="shrink-0 rounded-md bg-primary px-5 py-2 font-semibold text-primary-foreground hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
		<?php esc_html_e( 'Buscar', 'upsites' ); ?>
	</button>
</form>
