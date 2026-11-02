<?php
/**
 * Post: módulo single-post (template-pages/single-post/) — hero com data, categoria, compartilhar
 * e imagem destacada, conteúdo do editor e "Veja também", como no tema React (sem comentários: o
 * React não tinha). View::render imprime header e footer.
 *
 * Outro tipo de conteúdo que caia aqui (anexo sem página-mãe, CPT sem single-{cpt}.php): o tema
 * React não tinha módulo para ele e mostrava o 404 — aqui também, com status 404 de verdade.
 * (Anexo de uma página passa pelo page.php, como página comum, também igual ao React.)
 *
 * Post com senha ainda não digitada: só o título e o formulário de senha do WP (o conteúdo e os
 * dados do módulo não são buscados) — a seção pagina-padrao/conteudo, como no page.php (o
 * View::render tem um formulário próprio, mas sem o recuo do header fixo do Genoma).
 *
 * @package UpWork
 */

use Core\Framework\Part;
use Core\Framework\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! is_singular( 'post' ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	require get_404_template();
	return;
}

// contador de visualizações (desligado por padrão: filtro upwork/post_views)
US_set_post_views( get_the_ID() );

if ( post_password_required() ) {
	get_header();
	echo '<main>';
	Part::render(
		'pagina-padrao/conteudo',
		array(
			'titulo' => get_the_title(),
			'id'     => get_the_ID(),
		)
	);
	echo '</main>';
	get_footer();
	return;
}

View::render( 'single-post', get_the_ID() );
