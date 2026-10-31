<?php
declare(strict_types=1);

namespace Core\PostTypes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Attributes\Taxonomy as TaxonomyAttr;

/**
 * Registro de taxonomias do tema. Todas ficam em
 * upsites/customizer-register/inc/register_taxes.php (dentro de US_register_taxes, no init):
 *
 *   Taxonomy::add('servico_categoria', 'Categorias', 'servico', hierarchical: true);
 *   Taxonomy::add('servico_cor', 'Cores', 'servico', singular: 'Cor');
 *
 * Sem `singular:`, o singular é o plural sem o "s" final ("Categorias" → "Categoria"); passe-o
 * quando isso não servir ("Cores" → "Core"). Os rótulos do admin ("Adicionar %s"…) passam pelo
 * i18n do WP (text domain upsites).
 */
final class Taxonomy
{
    /** `public: false` = só no admin: termos editáveis no painel, sem página/URL no site. */
    private const ADMIN_ONLY = [
        // fora da REST pública: /wp/v2/{tipo} listaria os itens para qualquer visitante.
        // (sem REST o editor é o clássico; para o de blocos passe ['show_in_rest' => true] em $args)
        'show_in_rest'        => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_admin_column'  => true,
        'show_in_nav_menus'  => false,
        'show_tagcloud'      => false,
        'query_var'          => false,
        'rewrite'            => false,
    ];

    /**
     * @param string       $slug     slug da taxonomia (até 32 caracteres)
     * @param string       $plural   nome no plural (ex.: 'Categorias') — nome do menu no admin
     * @param string|array $postType CPT(s) a que pertence
     * @param bool         $public   false = só no admin (sem página de termo)
     * @param array        $args     argumentos extras do register_taxonomy (sobrescrevem os anteriores)
     * @param string|null  $singular nome no singular (padrão: o plural sem o "s" final)
     */
    public static function add(
        string $slug,
        string $plural,
        string|array $postType,
        bool $hierarchical = false,
        bool $showInRest = true,
        bool $public = true,
        array $args = [],
        ?string $singular = null,
    ): void {
        self::register(new TaxonomyAttr($slug, $plural, $postType, $hierarchical, $showInRest, $singular), $args + ['public' => $public]);
    }

    public static function register(TaxonomyAttr $attr, array $args = []): void
    {
        $plural   = $attr->plural;
        // só o último "s" (rtrim tiraria todos: "Status" → "Statu"); plural irregular → passe $singular
        $singular = $attr->singular ?? (string) preg_replace('/s$/u', '', $plural);

        $base = [
            'labels' => [
                'name'          => $plural,
                'singular_name' => $singular,
                /* translators: %s: nome plural da taxonomia (ex.: "Categorias"). */
                'search_items'  => sprintf(__('Buscar %s', 'upsites'), $plural),
                /* translators: %s: nome plural da taxonomia (ex.: "Categorias"). */
                'all_items'     => sprintf(__('Todas as %s', 'upsites'), $plural),
                /* translators: %s: nome singular da taxonomia (ex.: "Categoria"). */
                'edit_item'     => sprintf(__('Editar %s', 'upsites'), $singular),
                /* translators: %s: nome singular da taxonomia (ex.: "Categoria"). */
                'update_item'   => sprintf(__('Atualizar %s', 'upsites'), $singular),
                /* translators: %s: nome singular da taxonomia (ex.: "Categoria"). */
                'add_new_item'  => sprintf(__('Adicionar %s', 'upsites'), $singular),
            ],
            'hierarchical' => $attr->hierarchical,
            'show_in_rest' => $attr->showInRest,
            'rewrite'      => ['slug' => $attr->slug],
        ];
        if (($args['public'] ?? true) === false) $base = array_merge($base, self::ADMIN_ONLY);

        register_taxonomy($attr->slug, (array) $attr->postType, array_merge($base, $args));
    }
}
