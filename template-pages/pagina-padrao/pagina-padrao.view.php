<?php
/**
 * Página comum (sem modelo) — título e conteúdo do editor (React: app/pagina-padrao/pagina-padrao.view.tsx).
 * $data = retorno do PaginaPadraoController::page(). Formato da seção: @var no topo de
 * template-parts/includes/pagina-padrao/conteudo/conteudo.php. Sem comentários, como no React.
 *
 * @var array{conteudo: array{titulo: string, id: int}|null} $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('pagina-padrao/conteudo', $data['conteudo']); ?>
</main>
