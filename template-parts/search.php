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

View::render( 'resultado-pesquisa', ResultadoPesquisaModule::pageId(), ResultadoPesquisaModule::params() );
