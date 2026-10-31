<?php
/**
 * Home — compõe as seções de template-parts/includes/home/ na ordem da página.
 * $data = retorno do HomeController::page(); seção null = oculta pelo editor (Part não renderiza).
 * Formato de cada seção: @var no topo de template-parts/includes/home/{seção}/{seção}.php.
 * O banner de CTA do rodapé (aba Rodapé, footer_cta_*) é lido pelo layout/footer, não daqui.
 *
 * @var array{
 *   hero: array|null,
 *   servicos: array|null,
 *   portfolio: array|null,
 *   sobre: array|null,
 *   diferenciais: array|null,
 *   estrutura: array|null,
 *   depoimentos: array|null,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('home/hero', $data['hero']); ?>
  <?php Part::render('home/servicos', $data['servicos']); ?>
  <?php Part::render('home/portfolio', $data['portfolio']); ?>
  <?php Part::render('home/sobre', $data['sobre']); ?>
  <?php Part::render('home/diferenciais', $data['diferenciais']); ?>
  <?php Part::render('home/estrutura', $data['estrutura']); ?>
  <?php Part::render('home/depoimentos', $data['depoimentos']); ?>
</main>
