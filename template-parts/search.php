<?php
/**
 * Busca do WP (/?s={termo}), incluída pelo index.php: a página inteira é o módulo resultado-pesquisa
 * (hero com o termo + resultados em cards), com os campos da página de modelo fw:resultado-pesquisa
 * — como o RouteResolver do tema React. Sem essa página, os padrões dos campos.
 * Termo e ?pagina= vão em $params (ResultadoPesquisaModule::params); o View::render imprime o
 * header e o footer.
 * Obs.: a busca nativa não indexa campos ACF (use Relevanssi/SearchWP — ver README).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package UpWork
 */

use App\ResultadoPesquisa\ResultadoPesquisaModule;
use Core\Framework\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$upsites_busca = ResultadoPesquisaModule::params();
if ( null === $upsites_busca ) {
	// ?pagina= além da última página dos resultados: 404 de verdade, como no blog (index.php)
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	$upsites_404 = get_query_template( '404' );
	if ( $upsites_404 ) {
		require $upsites_404;
	}
	return;
}

View::render( 'resultado-pesquisa', ResultadoPesquisaModule::pageId(), $upsites_busca );
