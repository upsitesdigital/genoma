<?php
declare(strict_types=1);

namespace App\Blog;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class BlogController extends Controller
{
    #[Get('/blog')]
    #[Get('/blog/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId    = (int) ($request->get_param('id') ?: 0);
        $page      = max(1, (int) ($request->get_param('page') ?: 1));
        $categoria = (string) ($request->get_param('categoria') ?: '');

        return [
            'hero'      => $this->hero($pageId, $categoria),
            'listaPost' => $this->listaPost($page, $categoria),
        ];
    }

    /**
     * Endpoint dedicado para paginar/filtrar a Lista de post sem recarregar o Hero.
     * Usado pela SPA ao trocar de página ou categoria. Rota fora de /blog/:id de
     * propósito (evita colisão com o path param :id, que casaria com "posts").
     */
    #[Get('/blog-posts')]
    #[Cache(ttl: 60)]
    public function posts(\WP_REST_Request $request): array
    {
        $page      = max(1, (int) ($request->get_param('page') ?: 1));
        $categoria = (string) ($request->get_param('categoria') ?: '');

        return $this->listaPost($page, $categoria);
    }

    /**
     * Monta o Hero (etiqueta + título + campo de busca + pílulas de categoria).
     * Campos ACF vazios retornam vazio ("" ou []) — sem fallback de conteúdo.
     */
    private function hero(int $pageId, string $categoriaAtual): array
    {
        return [
            'eyebrow'          => (string) ($this->field($pageId, 'hero_eyebrow') ?: ''),
            'titulo'           => (string) ($this->field($pageId, 'hero_titulo') ?: ''),
            'buscaPlaceholder' => (string) ($this->field($pageId, 'hero_busca_placeholder') ?: ''),
            'buscaIcone'       => $this->defaultBuscaIcone(),
            'categorias'       => $this->categoriasReais($pageId, $categoriaAtual),
        ];
    }

    /**
     * Pílulas de categoria montadas a partir da taxonomia nativa `category`:
     * "Todos" + uma pílula por categoria com pelo menos um post publicado.
     * O pill ativo (`destaque`) é o que corresponde ao filtro atual (?categoria=).
     */
    private function categoriasReais(int $pageId, string $categoriaAtual): array
    {
        $baseUrl = $pageId ? (get_permalink($pageId) ?: home_url('/blog/')) : home_url('/blog/');

        $categorias = [
            [
                'titulo'   => 'Todos',
                'link'     => $baseUrl,
                'destaque' => $categoriaAtual === '',
            ],
        ];

        $termos = get_categories([
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        // "Em destaque" sempre logo após "Todos", independente da ordem alfabética.
        usort($termos, fn (\WP_Term $a, \WP_Term $b): int =>
            ($b->slug === 'em-destaque') <=> ($a->slug === 'em-destaque')
        );

        foreach ($termos as $termo) {
            $categorias[] = [
                'titulo'   => $termo->name,
                'link'     => add_query_arg('categoria', $termo->slug, $baseUrl),
                'destaque' => $categoriaAtual === $termo->slug,
            ];
        }

        return $categorias;
    }

    private function defaultBuscaIcone(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/blog/assets/hero-icon-busca.svg',
            'alt'    => '',
            'width'  => 24,
            'height' => 24,
            'sizes'  => [],
        ];
    }

    /**
     * Monta a "Lista de post" com posts REAIS do WordPress (post type nativo `post`),
     * paginados via WP_Query. Não é conteúdo ACF — reflete o que existir publicado.
     *
     * @param int    $page      Página atual (1-based).
     * @param string $categoria Slug da categoria (taxonomia `category`) para filtrar; vazio = todas.
     */
    private function listaPost(int $page, string $categoria): array
    {
        $paged = max(1, $page);

        $args = [
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => (int) get_option('posts_per_page'),
            'paged'               => $paged,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => false,
        ];

        if ($categoria !== '') {
            $args['category_name'] = $categoria;
        }

        $query = new \WP_Query($args);

        return [
            'posts'          => array_map(fn (\WP_Post $post): array => $this->mapPost($post), $query->posts),
            'paginaAtual'    => $paged,
            'totalPaginas'   => max(1, (int) $query->max_num_pages),
            'totalPosts'     => (int) $query->found_posts,
            'categoriaAtual' => $categoria,
        ];
    }

    private function mapPost(\WP_Post $post): array
    {
        $categorias    = get_the_category($post->ID);
        $categoriaNome = $categorias[0]->name ?? '';

        return [
            'id'        => $post->ID,
            'titulo'    => (string) get_the_title($post),
            'resumo'    => $this->postExcerpt($post),
            'data'      => (string) get_the_date('j \d\e F \d\e Y', $post),
            'link'      => (string) get_permalink($post),
            'categoria' => (string) $categoriaNome,
            'imagem'    => $this->postImagem($post),
        ];
    }

    private function postExcerpt(\WP_Post $post): string
    {
        if ($post->post_excerpt !== '') {
            return wp_strip_all_tags($post->post_excerpt);
        }

        return wp_trim_words(wp_strip_all_tags($post->post_content), 20, '…');
    }

    private function postImagem(\WP_Post $post): array
    {
        $thumbId = get_post_thumbnail_id($post);

        if ($thumbId) {
            $image = $this->image([
                'url'    => wp_get_attachment_image_url($thumbId, 'medium_large') ?: '',
                'alt'    => get_post_meta($thumbId, '_wp_attachment_image_alt', true) ?: '',
                'width'  => null,
                'height' => null,
                'sizes'  => [],
            ]);

            if ($image !== null) return $image;
        }

        return $this->defaultPostImagem();
    }

    private function defaultPostImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/blog/assets/lista-post-placeholder.png',
            'alt'    => '',
            'width'  => 630,
            'height' => 340,
            'sizes'  => [],
        ];
    }
}
