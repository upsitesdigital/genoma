<?php
/**
 * Resultado da pesquisa — compõe as seções de template-parts/includes/resultado-pesquisa/ na ordem da
 * página (React: ResultadoPesquisaView em app/resultado-pesquisa/resultado-pesquisa.view.tsx).
 * Na busca (/?s=) vem do template-parts/search.php; na página de modelo, do app.php (sem termo).
 * $data = retorno do ResultadoPesquisaController::page(); seção null = oculta pelo editor (Part não renderiza).
 * A lista de resultados não é seção ACF: é a parte compartilhada shared/post-list (cards + paginação),
 * a mesma do blog, aqui com a contagem e a categoria nos cards.
 *
 * @var array{
 *   hero: array|null,
 *   resultados: array{posts: list<array>, paginaAtual: int, totalPaginas: int, totalPosts: int, termo: string, paginas: list<array>, semResultadosTexto: string|null},
 * } $data
 */

use Core\Framework\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('resultado-pesquisa/hero', $data['hero']); ?>
  <?php
  Part::render('shared/post-list', [
    'id'        => 'resultados',
    'contagem'  => true,
    'categoria' => true,
    'empty'     => $data['resultados']['semResultadosTexto'] ?? __('Nenhum resultado encontrado.', 'upsites'),
  ] + $data['resultados']);
  ?>
</main>
