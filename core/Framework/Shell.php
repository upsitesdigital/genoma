<?php
declare(strict_types=1);

namespace Core\Framework;

/**
 * Renderiza o shell HTML da SPA (FW_BOOT + bundle React) a partir de uma rota
 * já resolvida (ou null, quando nenhum módulo bate — ex: 404.php).
 */
class Shell
{
    /**
     * @param array{module: string, pageId: int|null, url: string, title: string}|null $route
     */
    public static function render(?array $route): void
    {
        $seoTitle       = wp_get_document_title();
        $seoDescription = get_bloginfo('description');
        $seoImage       = '';
        $queriedId      = get_queried_object_id();

        if ($queriedId) {
            $excerpt = get_the_excerpt($queriedId);
            if ($excerpt) $seoDescription = wp_strip_all_tags($excerpt);

            if (has_post_thumbnail($queriedId)) {
                $seoImage = (string) get_the_post_thumbnail_url($queriedId, 'large');
            }
        }

        $bootData = [
            'apiBase'      => rest_url('framework/v1'),
            'wpApiBase'    => rest_url('wp/v2'),
            'siteUrl'      => home_url(),
            'themeUrl'     => get_template_directory_uri(),
            'nonce'        => wp_create_nonce('wp_rest'),
            'themeOptions' => get_option('upwork_theme_options', []),
            'currentPath'  => $_SERVER['REQUEST_URI'] ?? '/',
            'currentRoute' => $route,
            // Dados já resolvidos no servidor: o front semeia o cache do React
            // Query com eles e renderiza o conteúdo de primeira, sem skeleton
            // nem fetch extra (ver resources/lib/preload.ts).
            'preload'      => [
                'module' => $route ? self::preloadModule($route) : null,
                'menus'  => [
                    'primary' => self::internalGet('/menus/primary'),
                    'footer'  => self::internalGet('/menus/footer'),
                ],
            ],
        ];
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= esc_html($seoTitle) ?></title>
    <meta name="description" content="<?= esc_attr($seoDescription) ?>">

    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= esc_attr($seoTitle) ?>">
    <meta property="og:description" content="<?= esc_attr($seoDescription) ?>">
    <meta property="og:url"         content="<?= esc_url(home_url(add_query_arg([]))) ?>">
    <?php if ($seoImage): ?>
    <meta property="og:image"       content="<?= esc_url($seoImage) ?>">
    <meta name="twitter:card"       content="summary_large_image">
    <meta name="twitter:image"      content="<?= esc_url($seoImage) ?>">
    <?php endif; ?>

    <?php wp_head(); ?>
    <?php if ($route) \Core\Support\Asset::preload("app/{$route['module']}/{$route['module']}.view.tsx"); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div id="app-root"></div>
    <script>window.FW_BOOT = <?= wp_json_encode(\Core\Support\Webp::rewrite($bootData)) ?>;</script>
    <?php wp_footer(); ?>
</body>
</html>
        <?php
    }

    /**
     * Mesma requisição que useModule(slug) faria no front: GET /{slug}/{pageId}
     * com a query string atual (ex: ?s= na busca, paginação do blog).
     *
     * @param array{module: string, pageId: int|null} $route
     */
    private static function preloadModule(array $route): mixed
    {
        $path = '/' . $route['module'] . ($route['pageId'] ? '/' . $route['pageId'] : '');
        return self::internalGet($path, wp_unslash($_GET));
    }

    /**
     * Executa uma rota do framework internamente (sem HTTP) e devolve o JSON
     * que ela responderia, ou null em caso de erro — o front então busca via
     * REST normalmente.
     */
    private static function internalGet(string $path, array $query = []): mixed
    {
        try {
            $request = new \WP_REST_Request('GET', '/' . Rest::NAMESPACE . $path);
            $request->set_query_params($query);

            $response = rest_do_request($request);
            if ($response->is_error() || $response->get_status() >= 400) return null;

            return rest_get_server()->response_to_data($response, false);
        } catch (\Throwable) {
            return null;
        }
    }
}
