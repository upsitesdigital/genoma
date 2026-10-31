<?php
declare(strict_types=1);

namespace Core\PostTypes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Attributes\PostType as PostTypeAttr;

/**
 * Registro de CPTs do tema. Todos ficam em
 * upsites/customizer-register/inc/register_cpts.php (dentro de US_register_cpts, no init):
 *
 *   PostType::add('servico', 'Serviço', 'Serviços', icon: 'dashicons-portfolio', supports: ['title', 'thumbnail']);
 *
 * Os rótulos do admin ("Adicionar %s", "Buscar %s"…) passam pelo i18n do WP (text domain
 * upsites); singular e plural são o nome do conteúdo, usados como vierem.
 */
final class PostType
{
    /** `public: false` = só no admin: editável no painel, sem página/arquivo/URL no site. */
    private const ADMIN_ONLY = [
        // fora da REST pública: /wp/v2/{tipo} listaria os itens para qualquer visitante.
        // (sem REST o editor é o clássico; para o de blocos passe ['show_in_rest' => true] em $args)
        'show_in_rest'        => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => false,
        'query_var'           => false,
        'rewrite'             => false,
    ];

    /**
     * @param string $slug       slug do CPT (até 20 caracteres, sem maiúsculas)
     * @param string $singular   nome no singular (ex.: 'Serviço') — entra nos rótulos do admin
     * @param string $plural     nome no plural (ex.: 'Serviços') — nome do menu no admin
     * @param bool   $public     false = só no admin (sem single/URL)
     * @param array  $rewrite    padrão ['slug' => $slug] (ex.: ['slug' => 'cases', 'with_front' => false])
     * @param array  $args       argumentos extras do register_post_type (sobrescrevem os anteriores)
     */
    public static function add(
        string $slug,
        string $singular,
        string $plural,
        string $icon = 'dashicons-admin-post',
        bool $public = true,
        bool $showInRest = true,
        array $supports = ['title', 'editor', 'thumbnail'],
        array $rewrite = [],
        array $args = [],
    ): void {
        self::register(new PostTypeAttr($slug, $singular, $plural, $icon, $public, $showInRest, $supports, $rewrite), $args);
    }

    public static function register(PostTypeAttr $attr, array $args = []): void
    {
        $singular = $attr->singular;
        $plural   = $attr->plural;

        $base = [
            'labels' => [
                'name'               => $plural,
                'singular_name'      => $singular,
                /* translators: %s: nome singular do tipo de conteúdo (ex.: "Serviço"). */
                'add_new_item'       => sprintf(__('Adicionar %s', 'upsites'), $singular),
                /* translators: %s: nome singular do tipo de conteúdo (ex.: "Serviço"). */
                'edit_item'          => sprintf(__('Editar %s', 'upsites'), $singular),
                /* translators: %s: nome singular do tipo de conteúdo (ex.: "Serviço"). */
                'view_item'          => sprintf(_x('Ver %s', 'um item do tipo de conteúdo', 'upsites'), $singular),
                /* translators: %s: nome plural do tipo de conteúdo (ex.: "Serviços"). */
                'view_items'         => sprintf(_x('Ver %s', 'lista do tipo de conteúdo', 'upsites'), $plural),
                /* translators: %s: nome plural do tipo de conteúdo (ex.: "Serviços"). */
                'search_items'       => sprintf(__('Buscar %s', 'upsites'), $plural),
                /* translators: %s: nome plural do tipo de conteúdo (ex.: "Serviços"). */
                'not_found'          => sprintf(__('%s não encontrados.', 'upsites'), $plural),
                /* translators: %s: nome plural do tipo de conteúdo (ex.: "Serviços"). */
                'not_found_in_trash' => sprintf(__('%s não encontrados na lixeira.', 'upsites'), $plural),
            ],
            'public'       => $attr->public,
            'show_in_rest' => $attr->showInRest,
            'supports'     => $attr->supports,
            'menu_icon'    => $attr->icon,
            'rewrite'      => $attr->rewrite ?: ['slug' => $attr->slug],
            'has_archive'  => false,
        ];
        if (!$attr->public) $base = array_merge($base, self::ADMIN_ONLY);

        register_post_type($attr->slug, array_merge($base, $args));
    }
}
