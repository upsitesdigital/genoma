<?php
/**
 * Comentários de post/página: lista (wp_list_comments), paginação, aviso de comentários
 * fechados e formulário (comment_form). Chamado por comments_template() em single.php
 * e page.php só quando os comentários estão abertos ou já existem.
 *
 * @package UpWork
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// post com senha ainda não digitada: não mostra os comentários
if ( post_password_required() ) {
	return;
}

$fw_comments_count = (int) get_comments_number();
?>
<section id="comments" class="mt-16 border-t pt-10" aria-labelledby="comments-title">
	<?php if ( have_comments() ) : ?>
		<h2 id="comments-title" class="text-2xl font-bold tracking-tight">
			<?php
			printf(
				/* translators: 1: número de comentários, 2: título do post. */
				esc_html( _n( '%1$s comentário em “%2$s”', '%1$s comentários em “%2$s”', $fw_comments_count, 'upsites' ) ),
				esc_html( number_format_i18n( $fw_comments_count ) ),
				esc_html( get_the_title() )
			);
			?>
		</h2>

		<ol class="mt-8 grid gap-6 [&_.children]:mt-6 [&_.children]:grid [&_.children]:gap-6 [&_.children]:border-l [&_.children]:pl-4 md:[&_.children]:pl-8 [&_.comment-body]:rounded-lg [&_.comment-body]:border [&_.comment-body]:p-4 [&_.comment-meta]:text-sm [&_.comment-meta]:text-muted-foreground [&_.comment-author]:flex [&_.comment-author]:items-center [&_.comment-author]:gap-3 [&_.comment-author]:font-semibold [&_.comment-author]:text-foreground [&_.avatar]:rounded-full [&_.comment-content]:mt-3 [&_.reply]:mt-3 [&_.reply]:text-sm [&_.reply_a]:font-semibold [&_.reply_a]:text-primary">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'class'              => 'mt-8 [&_.nav-links]:flex [&_.nav-links]:flex-wrap [&_.nav-links]:gap-2 [&_.page-numbers]:rounded-md [&_.page-numbers]:border [&_.page-numbers]:px-3 [&_.page-numbers]:py-2 [&_.page-numbers]:text-sm [&_.current]:bg-primary [&_.current]:text-primary-foreground',
				'aria_label'         => __( 'Paginação dos comentários', 'upsites' ),
				'screen_reader_text' => __( 'Navegação dos comentários', 'upsites' ),
				'prev_text'          => __( 'Anteriores', 'upsites' ),
				'next_text'          => __( 'Mais recentes', 'upsites' ),
			)
		);
		?>
	<?php else : ?>
		<h2 id="comments-title" class="sr-only"><?php esc_html_e( 'Comentários', 'upsites' ); ?></h2>
	<?php endif; ?>

	<?php if ( ! comments_open() ) : ?>
		<p class="mt-8 text-muted-foreground"><?php esc_html_e( 'Os comentários estão fechados.', 'upsites' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_container'    => 'mt-10',
			'class_form'         => 'mt-4 grid gap-4 [&_label]:block [&_label]:text-sm [&_label]:font-medium [&_input:not([type=checkbox])]:mt-1 [&_input:not([type=checkbox])]:w-full [&_input:not([type=checkbox])]:rounded-md [&_input:not([type=checkbox])]:border [&_input:not([type=checkbox])]:px-3 [&_input:not([type=checkbox])]:py-2 [&_textarea]:mt-1 [&_textarea]:w-full [&_textarea]:rounded-md [&_textarea]:border [&_textarea]:px-3 [&_textarea]:py-2 [&_.comment-form-cookies-consent]:flex [&_.comment-form-cookies-consent]:items-start [&_.comment-form-cookies-consent]:gap-2 [&_.required]:text-primary',
			'title_reply'        => __( 'Deixe um comentário', 'upsites' ),
			'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title text-xl font-bold">',
			'title_reply_after'  => '</h2>',
			'class_submit'       => 'submit cursor-pointer rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
			'label_submit'       => __( 'Enviar comentário', 'upsites' ),
		)
	);
	?>
</section>
