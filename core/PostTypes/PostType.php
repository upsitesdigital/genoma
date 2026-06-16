<?php
declare(strict_types=1);

namespace Core\PostTypes;

use Core\Framework\Attributes\PostType as PostTypeAttr;

class PostType
{
    public static function register(PostTypeAttr $attr): void
    {
        $singular = $attr->singular;
        $plural   = $attr->plural;

        register_post_type($attr->slug, [
            'labels' => [
                'name'               => $plural,
                'singular_name'      => $singular,
                'add_new_item'       => "Adicionar {$singular}",
                'edit_item'          => "Editar {$singular}",
                'view_item'          => "Ver {$singular}",
                'view_items'         => "Ver {$plural}",
                'search_items'       => "Buscar {$plural}",
                'not_found'          => "{$plural} não encontrados.",
                'not_found_in_trash' => "{$plural} não encontrados na lixeira.",
            ],
            'public'       => $attr->public,
            'show_in_rest' => $attr->showInRest,
            'supports'     => $attr->supports,
            'menu_icon'    => $attr->icon,
            'rewrite'      => $attr->rewrite ?: ['slug' => $attr->slug],
            'has_archive'  => false,
        ]);
    }
}
