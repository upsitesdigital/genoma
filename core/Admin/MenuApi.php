<?php
declare(strict_types=1);

namespace Core\Admin;

class MenuApi
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route('framework/v1', '/menus/(?P<location>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getMenu'],
            'permission_callback' => '__return_true',
            'args'                => [
                'location' => ['required' => true, 'sanitize_callback' => 'sanitize_key'],
            ],
        ]);
    }

    public static function getMenu(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $location = $request->get_param('location');
        $locations = get_nav_menu_locations();

        if (empty($locations[$location])) {
            return new \WP_Error('no_menu', 'Menu não encontrado para esta localização.', ['status' => 404]);
        }

        $items = wp_get_nav_menu_items($locations[$location]);
        if (!$items) {
            return rest_ensure_response([]);
        }

        return rest_ensure_response(self::buildTree($items));
    }

    /** @param \WP_Post[] $items */
    private static function buildTree(array $items, int $parent = 0): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ((int) $item->menu_item_parent !== $parent) continue;

            $tree[] = [
                'id'       => (int) $item->ID,
                'title'    => $item->title,
                'url'      => $item->url,
                'target'   => $item->target ?: '_self',
                'classes'  => implode(' ', array_filter((array) $item->classes)),
                'children' => self::buildTree($items, (int) $item->ID),
            ];
        }

        return $tree;
    }
}
