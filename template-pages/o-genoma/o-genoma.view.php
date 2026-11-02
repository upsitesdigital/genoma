<?php
/**
 * O Genoma — compõe as seções de template-parts/includes/o-genoma/ na ordem da página.
 * $data = retorno do OGenomaController::page(); seção null = oculta pelo editor (Part não renderiza).
 * Formato de cada seção: @var no topo de template-parts/includes/o-genoma/{seção}/{seção}.php.
 * O banner de CTA do rodapé (aba Rodapé, footer_cta_*) é lido pelo layout/footer, não daqui.
 *
 * @var array{
 *   hero: array|null,
 *   sobre: array|null,
 *   compromisso: array|null,
 *   diagnostico: array|null,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('o-genoma/hero', $data['hero']); ?>
  <?php Part::render('o-genoma/sobre', $data['sobre']); ?>
  <?php Part::render('o-genoma/compromisso', $data['compromisso']); ?>
  <?php Part::render('o-genoma/diagnostico', $data['diagnostico']); ?>
</main>
