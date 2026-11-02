<?php
/**
 * Responsável — compõe as seções de template-parts/includes/responsavel/ na ordem da página.
 * $data = retorno do ResponsavelController::page(); seção null = oculta pelo editor (Part não renderiza).
 * Formato de cada seção: @var no topo de template-parts/includes/responsavel/{seção}/{seção}.php.
 * O banner de CTA do rodapé (aba Rodapé, footer_cta_*) é lido pelo layout/footer, não daqui.
 *
 * @var array{
 *   hero: array|null,
 *   suporte: array|null,
 *   planos: array|null,
 *   exames: array|null,
 *   servicos: array|null,
 *   resultados: array|null,
 *   beneficios: array|null,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('responsavel/hero', $data['hero']); ?>
  <?php Part::render('responsavel/suporte', $data['suporte']); ?>
  <?php Part::render('responsavel/planos', $data['planos']); ?>
  <?php Part::render('responsavel/exames', $data['exames']); ?>
  <?php Part::render('responsavel/servicos', $data['servicos']); ?>
  <?php Part::render('responsavel/resultados', $data['resultados']); ?>
  <?php Part::render('responsavel/beneficios', $data['beneficios']); ?>
</main>
