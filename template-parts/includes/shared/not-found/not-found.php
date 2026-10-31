<?php
/**
 * Página não encontrada (módulo sem view, View::render). Também é o corpo do 404.php
 * (template-parts/error.php), que liga a busca: Part::render('shared/not-found', ['search' => true]).
 *
 * @var array{search?: bool} $data
 *      search = mostra get_search_form() dentro do <main>, abaixo do link para a home (padrão: não).
 */

defined('ABSPATH') || exit;
?>
<main class="container py-24 text-center">
  <p class="text-sm font-semibold text-primary">404</p>
  <h1 class="mt-2 text-3xl font-bold"><?php esc_html_e('Página não encontrada', 'upsites'); ?></h1>
  <p class="mt-4 text-muted-foreground"><?php esc_html_e('O conteúdo que você procura não existe ou foi movido.', 'upsites'); ?></p>
  <a href="<?= esc_url(home_url('/')) ?>" class="mt-8 inline-block font-semibold text-primary hover:underline"><?php esc_html_e('Voltar para a home', 'upsites'); ?></a>
  <?php if (!empty($data['search'])) : ?>
    <div class="mx-auto mt-12 max-w-md">
      <p class="mb-3 text-muted-foreground"><?php esc_html_e('Ou faça uma busca:', 'upsites'); ?></p>
      <?php get_search_form(['aria_label' => __('Buscar no site', 'upsites')]); ?>
    </div>
  <?php endif; ?>
</main>
