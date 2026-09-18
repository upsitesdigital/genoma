<?php
declare(strict_types=1);

namespace App\ResultadoPesquisa;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class ResultadoPesquisaController extends Controller
{
    #[Get('/resultado-pesquisa')]
    #[Get('/resultado-pesquisa/:id')]
    #[Cache(ttl: 60)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: 0);
        $page   = max(1, (int) ($request->get_param('page') ?: 1));
        $termo  = (string) ($request->get_param('s') ?: '');

        return [
            'hero'      => $this->hero($pageId, $termo),
            'resultados' => $this->resultados($termo, $page),
        ];
    }

    /**
     * Endpoint dedicado para paginar sem recarregar o Hero — mesmo padrão de
     * /blog-posts.
     */
    #[Get('/resultado-pesquisa-lista')]
    #[Cache(ttl: 60)]
    public function lista(\WP_REST_Request $request): array
    {
        $page  = max(1, (int) ($request->get_param('page') ?: 1));
        $termo = (string) ($request->get_param('s') ?: '');

        return $this->resultados($termo, $page);
    }

    private function hero(int $pageId, string $termo): array
    {
        return [
            'eyebrow'             => (string) ($this->field($pageId, 'hero_eyebrow') ?: ''),
            'buscaPlaceholder'    => (string) ($this->field($pageId, 'hero_busca_placeholder') ?: ''),
            'buscaIcone'          => $this->defaultBuscaIcone(),
            'termo'               => $termo,
            'semResultadosTexto'  => (string) ($this->field($pageId, 'sem_resultados_texto') ?: ''),
        ];
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
     * Busca nativa do WordPress (WP_Query com `s`) em posts e páginas
     * publicados. Sem conteúdo ACF — reflete o que existir publicado.
     */
    private function resultados(string $termo, int $page): array
    {
        $paged = max(1, $page);

        $args = [
            's'                   => $termo,
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => (int) get_option('posts_per_page'),
            'paged'               => $paged,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => false,
        ];

        $query = $termo !== '' ? new \WP_Query($args) : null;

        return [
            'posts'        => $query ? array_map(fn (\WP_Post $post): array => $this->mapPost($post), $query->posts) : [],
            'paginaAtual'  => $paged,
            'totalPaginas' => $query ? max(1, (int) $query->max_num_pages) : 1,
            'totalPosts'   => $query ? (int) $query->found_posts : 0,
            'termo'        => $termo,
        ];
    }

    private function mapPost(\WP_Post $post): array
    {
        $categorias    = $post->post_type === 'post' ? get_the_category($post->ID) : [];
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
