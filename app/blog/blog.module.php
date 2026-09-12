<?php
declare(strict_types=1);

namespace App\Blog;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'blog',
    name: 'Blog',
    route: '/blog',
    template: true,
    templateLabel: 'Página · Blog',
)]
final class BlogModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_blog',
            'title'    => 'Blog',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:blog'],
            ]],
            'fields' => [
                // ── Hero ──────────────────────────────────────────────────
                [
                    'key'           => 'field_blog_hero_eyebrow',
                    'name'          => 'hero_eyebrow',
                    'label'         => 'Hero — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Blog',
                ],
                [
                    'key'           => 'field_blog_hero_titulo',
                    'name'          => 'hero_titulo',
                    'label'         => 'Hero — Título',
                    'type'          => 'text',
                    'required'      => 1,
                    'default_value' => 'Simply dummy text of the  industry.',
                ],
                [
                    'key'           => 'field_blog_hero_busca_placeholder',
                    'name'          => 'hero_busca_placeholder',
                    'label'         => 'Hero — Busca (placeholder do campo)',
                    'type'          => 'text',
                    'default_value' => 'Busca',
                ],
                [
                    'key'          => 'field_blog_hero_categorias',
                    'name'         => 'hero_categorias',
                    'label'        => 'Hero — Categorias (pílulas de filtro)',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar Categoria',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_blog_hero_categoria_titulo',
                            'name'          => 'titulo',
                            'label'         => 'Título',
                            'type'          => 'text',
                            'required'      => 1,
                            'default_value' => '',
                        ],
                        [
                            'key'           => 'field_blog_hero_categoria_link',
                            'name'          => 'link',
                            'label'         => 'Link',
                            'type'          => 'url',
                            'default_value' => '#',
                        ],
                        [
                            'key'           => 'field_blog_hero_categoria_destaque',
                            'name'          => 'destaque',
                            'label'         => 'Destacar (estilo "Em destaque")',
                            'type'          => 'true_false',
                            'ui'            => 1,
                            'default_value' => 0,
                        ],
                    ],
                ],

                // ── Lista de post ─────────────────────────────────────────
                // Seção 100% dinâmica: lista posts reais do WordPress (post type
                // nativo `post`) via WP_Query no controller, com paginação e
                // filtro por categoria (?categoria=&page=). Não há campos ACF
                // aqui de propósito — o Figma não mostra título/subtítulo
                // estático para a seção, e o conteúdo dos cards (imagem,
                // título, resumo, data, categoria) vem sempre do post nativo,
                // nunca de um repeater.
            ],
        ]);
    }
}
