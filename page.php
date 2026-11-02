<?php
/**
 * Página comum (sem modelo "Página · {Nome}", ex.: Política de privacidade): módulo pagina-padrao
 * (template-pages/pagina-padrao/) — título e conteúdo do editor, como no tema React (sem
 * comentários: o React não tinha). View::render imprime header e footer.
 *
 * Páginas com modelo fw:{slug} e a página inicial NÃO passam por aqui: o
 * Core\Framework\Bootstrap::catchAll() as manda para template-parts/app.php (módulo).
 *
 * Página com senha ainda não digitada: só o título e o formulário de senha do WP (o conteúdo não
 * é buscado) — a própria seção pagina-padrao/conteudo, chamada direto (o View::render tem um
 * formulário próprio, mas sem o recuo do header fixo do Genoma).
 *
 * @package UpWork
 */

use Core\Framework\Part;
use Core\Framework\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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

View::render( 'pagina-padrao', get_the_ID() );
