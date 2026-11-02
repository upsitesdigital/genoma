<?php
declare(strict_types=1);

namespace App\Blog;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\RouteResolver;
use Core\Framework\Attributes\Module as Mod;

/**
 * Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:blog.
 *
 * URL do visitante (a mesma do React): /blog/?categoria={slug}. A paginação, que no React trocava de
 * página sem mudar a URL, vira link: /blog/?categoria={slug}&pagina=2 ("page" não serve: num
 * permalink de página o WP o lê como página do conteúdo e redireciona para /blog/2/).
 */
#[Mod(slug: 'blog', name: 'Blog', route: '/blog', template: true, templateLabel: 'Página · Blog')]
final class BlogModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php

    public function boot(): void
    {
        // a página fw:blog sai pelo index.php (e não pelo app.php): é ele que passa a página e a categoria
        // da URL ao controller — o app.php chama o View::render sem $params
        add_filter('template_include', [self::class, 'template'], 20);
        // arquivo nativo /category/{slug}/ → blog filtrado (React: Bootstrap::redirectCategoryArchive)
        add_action('template_redirect', [self::class, 'redirectCategoryArchive']);
    }

    /** template_include: página com o modelo fw:blog → index.php, no lugar do app.php do Bootstrap::catchAll. */
    public static function template(string $template): string
    {
        // só troca o app.php: feed, embed e o que mais o catchAll deixou passar seguem como estão
        if ($template !== get_template_directory() . '/template-parts/app.php') return $template;
        if (!is_page() || RouteResolver::moduleForPage((int) get_queried_object_id()) !== 'blog') return $template;

        $index = get_template_directory() . '/index.php';
        return is_file($index) ? $index : $template;
    }

    /**
     * A requisição é do blog? → ['pageId' => id da página, 'params' => $params do page()]; senão null.
     * Blog = a página com o modelo fw:blog ou a página de posts do WP (Configurações → Leitura; o
     * RouteResolver do React fazia o mesmo). params null = ?pagina= além da última página (404).
     *
     * @return array{pageId: int, params: array{pagina: int, categoria: string|null}|null}|null
     */
    public static function request(): ?array
    {
        if (is_page() && RouteResolver::moduleForPage((int) get_queried_object_id()) === 'blog') {
            $pageId = (int) get_queried_object_id();
        } elseif (is_home() && !is_front_page()) {
            $pageId = (int) get_option('page_for_posts') ?: (int) self::pageId();
        } else {
            return null;
        }

        return ['pageId' => $pageId, 'params' => self::params()];
    }

    /**
     * $params do BlogController::page() a partir da URL, validados (a chave do #[Cache] é id + $params:
     * valor livre da URL não entra nela):
     * - categoria: slug de uma categoria que existe; '' = sem filtro; null = a da URL não existe
     *   (lista vazia e nenhuma pílula ativa, como no React);
     * - pagina: ≥ 1. Da 2 em diante, só até a última página da lista com esse filtro; além dela
     *   → null (o index.php responde 404, em vez de uma lista vazia indexável).
     *
     * @return array{pagina: int, categoria: string|null}|null
     */
    public static function params(): ?array
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- navegação pública (filtro e página), só leitura
        $slug   = isset($_GET['categoria']) && is_string($_GET['categoria']) ? sanitize_title(wp_unslash($_GET['categoria'])) : '';
        $pagina = isset($_GET['pagina']) && is_string($_GET['pagina']) ? absint($_GET['pagina']) : 1;
        // phpcs:enable

        $termo     = $slug !== '' ? get_term_by('slug', $slug, 'category') : null;
        $categoria = $slug === '' ? '' : ($termo instanceof \WP_Term ? $termo->slug : null);
        $pagina    = max(1, $pagina);

        return $pagina > 1 && $pagina > self::lastPage($categoria) ? null : ['pagina' => $pagina, 'categoria' => $categoria];
    }

    /**
     * Última página da lista do blog com o filtro (a mesma consulta do BlogController::listaPost, só
     * contando). Categoria inexistente (null) → 0. Só roda para ?pagina= ≥ 2.
     */
    private static function lastPage(?string $categoria): int
    {
        if ($categoria === null) return 0;

        $args = [
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        if ($categoria !== '') $args['category_name'] = $categoria;

        $total = (int) (new \WP_Query($args))->found_posts;

        return (int) ceil($total / max(1, (int) get_option('posts_per_page')));
    }

    /** Página com o modelo fw:blog (a primeira publicada), como o RouteResolver::findModulePageId do React. */
    public static function pageId(): ?int
    {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- uma consulta, só no redirecionamento de categoria e na página de posts
        $pages = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'fw:blog', 'number' => 1]);

        return $pages ? (int) $pages[0]->ID : null;
    }

    /**
     * URL do blog filtrado por categoria (/blog/?categoria={slug}) — usada no lugar do arquivo nativo
     * /category/{slug}/, que nenhum módulo renderiza (React: RouteResolver::blogCategoryUrl). Sem página
     * de blog → null.
     */
    public static function categoryUrl(string $slug, int $pagina = 1): ?string
    {
        $pageId = self::pageId() ?: ((int) get_option('page_for_posts') ?: null);
        if (!$pageId) return null;

        return add_query_arg(['categoria' => $slug, 'pagina' => $pagina > 1 ? $pagina : false], (string) get_permalink($pageId));
    }

    /**
     * template_redirect: /category/{slug}/ → 301 para /blog/?categoria={slug} (a página 2+ do arquivo vai
     * para a mesma página do blog). O feed da categoria (/category/{slug}/feed/) continua sendo o feed.
     */
    public static function redirectCategoryArchive(): void
    {
        if (!is_category() || is_feed()) return;

        $term = get_queried_object();
        if (!$term instanceof \WP_Term) return;

        $url = self::categoryUrl($term->slug, max(1, (int) get_query_var('paged')));
        if ($url && wp_safe_redirect($url, 301)) exit;
    }
}
