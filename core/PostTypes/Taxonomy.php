<?php
declare(strict_types=1);

namespace Core\PostTypes;

use Core\Framework\Attributes\Taxonomy as TaxonomyAttr;

class Taxonomy
{
    public static function register(TaxonomyAttr $attr): void
    {
        $plural = $attr->plural;

        register_taxonomy($attr->slug, (array) $attr->postType, [
            'labels' => [
                'name'          => $plural,
                'singular_name' => rtrim($plural, 's'),
                'search_items'  => "Buscar {$plural}",
                'all_items'     => "Todas as {$plural}",
                'edit_item'     => 'Editar',
                'update_item'   => 'Atualizar',
                'add_new_item'  => 'Adicionar',
            ],
            'hierarchical' => $attr->hierarchical,
            'show_in_rest' => $attr->showInRest,
            'rewrite'      => ['slug' => $attr->slug],
        ]);
    }
}
