<?php
/**
 * Item de lista de posts (post atual do loop): imagem destacada, data, título com link e resumo.
 * Usado pelo shared/post-list (index.php, arquivo, busca, autor) — chame dentro do loop,
 * depois de the_post():
 *
 *   Part::render('shared/post-item');
 *   Part::render('shared/post-item', ['heading' => 'h3']);
 *
 * @var array{heading?: 'h2'|'h3'} $data  heading = nível do título (padrão h2).
 */

defined('ABSPATH') || exit;

$tag = ($data['heading'] ?? 'h2') === 'h3' ? 'h3' : 'h2';
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('flex flex-col gap-4 md:flex-row md:gap-6'); ?>>
  <?php if (has_post_thumbnail()) : ?>
    <a href="<?php the_permalink(); ?>" class="shrink-0 md:w-64" tabindex="-1" aria-hidden="true">
      <?php the_post_thumbnail('medium_large', ['class' => 'aspect-[16/10] w-full rounded-lg object-cover', 'alt' => '']); ?>
    </a>
  <?php endif; ?>

  <div class="flex-1">
    <?php if ('post' === get_post_type()) : ?>
      <p class="text-sm text-muted-foreground">
        <time datetime="<?= esc_attr(get_the_date('c')) ?>"><?= esc_html(get_the_date()) ?></time>
      </p>
    <?php endif; ?>

    <<?= $tag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- h2|h3 fixo acima ?> class="mt-1 text-xl font-bold tracking-tight md:text-2xl">
      <a href="<?php the_permalink(); ?>" class="hover:text-primary"><?php the_title(); ?></a>
    </<?= $tag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- h2|h3 fixo acima ?>>

    <div class="mt-2 text-muted-foreground"><?php the_excerpt(); ?></div>

    <a href="<?php the_permalink(); ?>" class="mt-3 inline-block text-sm font-semibold text-primary hover:underline">
      <?php esc_html_e('Ler mais', 'upsites'); ?><span class="sr-only">: <?php the_title(); ?></span>
    </a>
  </div>
</article>
