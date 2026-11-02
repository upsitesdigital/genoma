<?php
/**
 * Contato — compõe as seções de template-parts/includes/contato/ na ordem da página.
 * $data = retorno do ContatoController::page(); seção null = oculta pelo editor (Part não renderiza).
 * Formato de cada seção: @var no topo de template-parts/includes/contato/{seção}/{seção}.php.
 * O banner de CTA do rodapé (aba Rodapé, footer_cta_*) é lido pelo layout/footer, não daqui.
 *
 * @var array{
 *   hero: array|null,
 *   lista: array|null,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('contato/hero', $data['hero']); ?>
  <?php Part::render('contato/lista', $data['lista']); ?>
</main>
