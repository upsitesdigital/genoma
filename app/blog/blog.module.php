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
                    'label'         => 'Hero — Etiqueta (H1)',
                    'type'          => 'text',
                    'instructions'  => 'Renderizado como H1 da página (o Título abaixo é H2).',
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
                // As pílulas de categoria (Hero) não são um campo ACF — são
                // montadas no controller a partir das categorias reais do
                // WordPress (taxonomia nativa `category`), com um pill "Todos"
                // + um pill por categoria com posts publicados.

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
