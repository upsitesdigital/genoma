<?php
/**
 * Página não encontrada (404). O WordPress já responde com status 404; o markup é a
 * parte shared/not-found (a mesma usada pelo View::render quando o módulo não existe) + a busca
 * (searchform.php, com aria-label "Buscar no site") dentro do mesmo <main>: template-parts/error.php.
 *
 * @package UpWork
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
get_template_part( 'template-parts/error' );
get_footer();
