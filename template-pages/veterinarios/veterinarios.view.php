<?php
/**
 * Veterinários — compõe as seções de template-parts/includes/veterinarios/ na ordem da página.
 * $data = retorno do VeterinariosController::page(); seção null = oculta pelo editor (Part não renderiza).
 * Formato de cada seção: @var no topo de template-parts/includes/veterinarios/{seção}/{seção}.php.
 * O banner de CTA do rodapé (aba Rodapé, footer_cta_*) é lido pelo layout/footer, não daqui.
 *
 * @var array{
 *   hero: array|null,
 *   suporte: array|null,
 *   praticidade: array|null,
 *   tiposExames: array|null,
 *   estrutura: array|null,
 *   beneficios: array|null,
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('veterinarios/hero', $data['hero']); ?>
  <?php Part::render('veterinarios/suporte', $data['suporte']); ?>
  <?php Part::render('veterinarios/praticidade', $data['praticidade']); ?>
  <?php Part::render('veterinarios/tipos-exames', $data['tiposExames']); ?>
  <?php Part::render('veterinarios/estrutura', $data['estrutura']); ?>
  <?php Part::render('veterinarios/beneficios', $data['beneficios']); ?>
</main>
