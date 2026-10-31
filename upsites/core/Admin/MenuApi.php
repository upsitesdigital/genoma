<?php
declare(strict_types=1);

namespace Core\Admin;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/** Menus das localizações (register_nav_menus) em árvore, para o header/footer montarem em PHP. */
final class MenuApi
{
    /**
     * Árvore do menu de uma localização ([] se não houver menu atribuído).
     *
     * Marca o item da página atual (current) e seus pais/ancestrais (ancestor) com o mesmo
     * cálculo do wp_nav_menu() — _wp_menu_item_classes_by_context() (usa a main query).
     */
    public static function tree(string $location): array
    {
        $locations = get_nav_menu_locations();
        if (empty($locations[$location])) return [];

        $items = wp_get_nav_menu_items($locations[$location]);
        if (!$items) return [];

        // item que aponta para página/post não publicado (rascunho, privado, lixeira) não vai
        // para o site — o link daria 404 para o visitante. Aparece sozinho quando publicar.
        $items = array_values(array_filter($items, static fn($item): bool =>
            $item->type !== 'post_type' || get_post_status((int) $item->object_id) === 'publish'
        ));
        if (!$items) return [];

        // classes digitadas no painel (Aparência → Menus → Classes CSS), antes de o WP somar as dele
        foreach ($items as $item) {
            $item->fw_classes = implode(' ', array_filter((array) $item->classes));
        }

        if (function_exists('_wp_menu_item_classes_by_context')) {
            _wp_menu_item_classes_by_context($items);
        }

        return self::buildTree($items);
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
                // o WP já filtra ao salvar o menu; aqui de novo (filtro/import pode trazer javascript:)
                'url'      => esc_url_raw((string) $item->url),
                'target'   => $item->target === '_blank' ? '_blank' : '_self',
                'classes'  => $item->fw_classes ?? '',
                // item da página atual (current-menu-item)
                'current'  => !empty($item->current),
                // pai/ancestral do item atual (current-menu-parent / current-menu-ancestor)
                'ancestor' => !empty($item->current_item_ancestor) || !empty($item->current_item_parent),
                'children' => self::buildTree($items, (int) $item->ID),
            ];
        }

        return $tree;
    }
}
