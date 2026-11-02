<?php
/**
 * Página não encontrada, quando o index.php cai no is_404() — e o corpo do 404.php da raiz.
 * O WP já envia o status 404.
 * Markup: a parte shared/not-found (a mesma do View::render sem módulo) — o 404 do tema React,
 * só o "404" e "Página não encontrada." (sem busca).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

Part::render( 'shared/not-found' );
