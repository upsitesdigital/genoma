<?php
declare(strict_types=1);

namespace App\ResultadoPesquisa;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/**
 * Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:resultado-pesquisa.
 *
 * A busca do WP (/?s={termo}) renderiza este módulo com os campos da página de modelo
 * fw:resultado-pesquisa (template-parts/search.php, chamado pelo index.php), como o RouteResolver do
 * React. A paginação, que no React trocava de página sem mudar a URL, vira link: /?s={termo}&pagina=2.
 */
#[Mod(slug: 'resultado-pesquisa', name: 'ResultadoPesquisa', route: '/resultado-pesquisa', template: true, templateLabel: 'Página · ResultadoPesquisa')]
final class ResultadoPesquisaModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php

    /** A busca do site é só de posts: a consulta principal do WP também (feed da busca, plugins de SEO). */
    public function boot(): void
    {
        add_action('pre_get_posts', [self::class, 'onlyPosts']);
    }

    public static function onlyPosts(\WP_Query $query): void
    {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) $query->set('post_type', 'post');
    }

    /**
     * Página com o modelo fw:resultado-pesquisa (a primeira publicada) — de onde vêm os campos da busca
     * (React: RouteResolver::findModulePageId). Sem ela → null: os padrões dos campos.
     */
    public static function pageId(): ?int
    {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- uma consulta por busca
        $pages = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'fw:resultado-pesquisa', 'number' => 1]);

        return $pages ? (int) $pages[0]->ID : null;
    }

    /**
     * $params do ResultadoPesquisaController::page() na busca: o termo (o mesmo que o WP buscou) e a
     * página da lista (?pagina=, ≥ 1). Da 2 em diante, só até a última página dos resultados; além
     * dela → null (o search.php responde 404, como o blog — não uma lista vazia indexável).
     *
     * @return array{termo: string, pagina: int}|null
     */
    public static function params(): ?array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navegação pública (página da lista), só leitura
        $pagina = isset($_GET['pagina']) && is_string($_GET['pagina']) ? absint($_GET['pagina']) : 1;
        $pagina = max(1, $pagina);
        $termo  = (string) get_search_query(false);

        return $pagina > 1 && $pagina > self::lastPage($termo) ? null : ['termo' => $termo, 'pagina' => $pagina];
    }

    /**
     * Última página dos resultados (a mesma consulta do ResultadoPesquisaController::resultados, só
     * contando). Sem termo → 1. Só roda para ?pagina= ≥ 2.
     */
    private static function lastPage(string $termo): int
    {
        if ($termo === '') return 1;

        $total = (int) (new \WP_Query([
            's'                      => $termo,
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]))->found_posts;

        return max(1, (int) ceil($total / max(1, (int) get_option('posts_per_page'))));
    }
}
