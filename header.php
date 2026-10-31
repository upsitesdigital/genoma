<?php
/**
 * Abre a página: <head> (wp_head imprime o CSS) e o <body> com o header do site.
 *
 * O header é a parte template-parts/includes/layout/header/header.php (+ header.store.js,
 * menu mobile pela Interactivity API), renderizada no servidor em TODAS as páginas
 * (módulos e templates PHP). Não monte header aqui — edite o header.php da parte.
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php // acessibilidade: 1º item do Tab — leva o foco direto ao conteúdo, pulando o menu ?>
<a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-md focus:bg-background focus:px-4 focus:py-2 focus:font-semibold focus:text-foreground focus:shadow-lg focus:outline-2 focus:outline-offset-2 focus:outline-primary"><?php esc_html_e( 'Pular para o conteúdo', 'upsites' ); ?></a>

<div class="flex min-h-screen flex-col">
	<?php // sticky no wrapper (e não no <header>): ele é filho da coluna da página, então gruda ao rolar ?>
	<div id="fw-header" class="sticky top-0 z-40"><?php Part::render( 'layout/header' ); ?></div>
	<div id="conteudo" class="flex-1" tabindex="-1">
