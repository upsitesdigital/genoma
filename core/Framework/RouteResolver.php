<?php
declare(strict_types=1);

namespace Core\Framework;

class RouteResolver
{
    /**
     * Resolve o módulo responsável pela URL atual (usado no carregamento inicial da página).
     *
     * @return array{module: string, pageId: int|null, url: string, title: string}|null
     */
    public static function current(): ?array
    {
        // 1. Page com template fw:* atribuído
        if (is_page()) {
            $page     = get_queried_object();
            $template = get_post_meta($page->ID, '_wp_page_template', true);

            if (is_string($template) && str_starts_with($template, 'fw:')) {
                $slug = substr($template, 3);
                return [
                    'module' => $slug,
                    'pageId' => $page->ID,
                    'url'    => (string) get_permalink($page->ID),
                    'title'  => get_the_title($page->ID),
                ];
            }
        }

        // 2. Front page (home)
        if (is_front_page()) {
            $pageId = (int) get_option('page_on_front') ?: null;
            return [
                'module' => 'home',
                'pageId' => $pageId,
                'url'    => home_url('/'),
                'title'  => get_bloginfo('name'),
            ];
        }

        // 3. Nenhum módulo mapeado — React Router trata o 404
        return null;
    }

    /**
     * Resolve o módulo por path — usado pela SPA na navegação client-side.
     * Não depende de tags condicionais do WP (is_page etc.), opera com queries diretas.
     *
     * @return array{module: string, pageId: int|null, url: string, title: string}|null
     */
    public static function byPath(string $path): ?array
    {
        $clean = '/' . ltrim((string) parse_url($path, PHP_URL_PATH), '/');

        if ($clean === '/') {
            $pageId = (int) get_option('page_on_front') ?: null;
            return [
                'module' => 'home',
                'pageId' => $pageId,
                'url'    => home_url('/'),
                'title'  => get_bloginfo('name'),
            ];
        }

        $page = get_page_by_path(trim($clean, '/'));
        if (!$page) return null;

        $template = get_post_meta($page->ID, '_wp_page_template', true);
        if (!is_string($template) || !str_starts_with($template, 'fw:')) return null;

        $slug = substr($template, 3);
        return [
            'module' => $slug,
            'pageId' => $page->ID,
            'url'    => (string) get_permalink($page->ID),
            'title'  => get_the_title($page->ID),
        ];
    }

    public static function restHandler(\WP_REST_Request $request): \WP_REST_Response
    {
        return rest_ensure_response(self::byPath($request->get_param('path') ?? '/'));
    }
}
