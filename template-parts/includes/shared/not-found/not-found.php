<?php
/**
 * Página não encontrada — corpo do 404.php (template-parts/error.php) e do View::render quando o
 * módulo não tem view. O status 404 é do WordPress (404.php) ou do View::render.
 *
 * Layout: card claro centralizado com a etiqueta "Erro 404", título, texto e botão para a home
 * (cores da marca: brand-light-purple no card, primary na etiqueta e no botão). Sem campos ACF.
 * mb do card: deixa o card do CTA do rodapé (que sobe sobre a faixa roxa) fora do conteúdo.
 *
 * @var array $data (não usado)
 */

defined('ABSPATH') || exit;
?>
<main>
  <div class="container pt-12 pb-16 lg:pt-24 lg:pb-24">
    <div class="flex flex-col items-center rounded-[20px] bg-brand-light-purple px-5 py-20 text-center md:px-10 lg:rounded-[30px] lg:py-32">
      <p data-reveal class="rounded-lg bg-primary px-4 py-2 font-heading text-2xl leading-none font-semibold text-white lg:px-5 lg:py-3 lg:text-[2rem]">
        <?php esc_html_e('Erro 404', 'upsites'); ?>
      </p>

      <h1 data-reveal class="mt-6 font-heading text-[2rem] leading-[1.15] font-bold text-brand-purple-dark md:text-5xl lg:mt-8 lg:text-[3.5rem]">
        <?php esc_html_e('Página não encontrada!', 'upsites'); ?>
      </h1>

      <p data-reveal class="mt-4 max-w-xl font-sans text-body text-brand-gray-text lg:mt-6 lg:text-body-lg">
        <?php esc_html_e('Infelizmente não encontramos a página que você está procurando.', 'upsites'); ?>
      </p>

      <a
        data-reveal
        href="<?= esc_url(home_url('/')) ?>"
        class="mt-8 inline-flex items-center justify-center rounded-full bg-primary px-7 py-4 font-heading text-base leading-none font-medium text-white transition-colors hover:bg-brand-purple-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary lg:mt-10 lg:py-5 lg:text-h5 lg:leading-none"
      ><?php esc_html_e('Voltar à página principal', 'upsites'); ?></a>
    </div>
  </div>
</main>
