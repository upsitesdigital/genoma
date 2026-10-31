<?php
/**
 * Página não encontrada, quando o index.php cai no is_404() — e o corpo do 404.php da raiz.
 * O WP já envia o status 404.
 * Markup: a parte shared/not-found (a mesma do View::render sem módulo) com a busca
 * (searchform.php) ligada, dentro do mesmo <main>.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// busca abaixo do aviso, dentro do <main> (get_search_form com aria_label "Buscar no site")
Part::render( 'shared/not-found', array( 'search' => true ) );
