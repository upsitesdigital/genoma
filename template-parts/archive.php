<?php
/**
 * Arquivo (tag, data, taxonomia, CPT com arquivo): faixa no estilo do topo do blog com o título e a
 * descrição do arquivo + os posts em cards com paginação (shared/post-list no modo loop).
 * Incluído pelo index.php (get_template_part). Categoria não chega aqui: o BlogModule redireciona
 * /category/{slug}/ para /blog/?categoria={slug}. (No tema React estes arquivos davam 404.)
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
<main>
	<?php // faixa colorida: o header é fixo e transparente no desktop (texto branco), como no topo do blog ?>
	<section class="w-full bg-[linear-gradient(-20deg,#95ABB7_47%,#AEC6D3_100%)] pt-[186px] pb-16 md:pb-20 lg:pt-40 lg:pb-24">
		<div class="container">
			<div class="flex max-w-3xl flex-col gap-3 md:gap-4">
				<?php the_archive_title( '<h1 data-reveal class="font-heading text-3xl leading-tight font-medium text-white md:text-h1 md:leading-[1.15]">', '</h1>' ); ?>
				<?php the_archive_description( '<div data-reveal class="font-sans text-body-lg text-white/90">', '</div>' ); ?>
			</div>
		</div>
	</section>

	<?php
	Part::render(
		'shared/post-list',
		array(
			'empty'      => __( 'Nenhum post encontrado neste arquivo.', 'upsites' ),
			'searchForm' => true,
		)
	);
	?>
</main>
