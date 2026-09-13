<?php
declare(strict_types=1);

namespace App\SinglePost;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class SinglePostController extends Controller
{
    #[Get('/single-post')]
    #[Get('/single-post/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: 0);

        return [
            'hero' => $this->hero($pageId),
        ];
    }

    /**
     * Monta o Hero a partir do post REAL sendo visualizado (não é conteúdo ACF —
     * reflete o que existir publicado no post type nativo `post`).
     */
    private function hero(int $pageId): array
    {
        $post = get_post($pageId);

        if (!$post instanceof \WP_Post) {
            return [
                'titulo'        => '',
                'data'          => '',
                'categoria'     => '',
                'categoriaLink' => '',
                'imagem'        => $this->defaultImagem(),
                'url'           => '',
            ];
        }

        $categorias    = get_the_category($post->ID);
        $categoriaNome = $categorias[0]->name ?? '';
        $categoriaLink = $categorias[0] ?? null
            ? (string) get_category_link($categorias[0]->term_id)
            : '';

        return [
            'titulo'        => (string) get_the_title($post),
            'data'          => (string) get_the_date('j \d\e F \d\e Y', $post),
            'categoria'     => (string) $categoriaNome,
            'categoriaLink' => $categoriaLink,
            'imagem'        => $this->imagem($post),
            'url'           => (string) get_permalink($post),
        ];
    }

    private function imagem(\WP_Post $post): array
    {
        $thumbId = get_post_thumbnail_id($post);

        if ($thumbId) {
            $image = $this->image([
                'url'    => wp_get_attachment_image_url($thumbId, 'large') ?: '',
                'alt'    => get_post_meta($thumbId, '_wp_attachment_image_alt', true) ?: '',
                'width'  => null,
                'height' => null,
                'sizes'  => [],
            ]);

            if ($image !== null) return $image;
        }

        return $this->defaultImagem();
    }

    private function defaultImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/single-post/assets/hero-placeholder.png',
            'alt'    => '',
            'width'  => 800,
            'height' => 432,
            'sizes'  => [],
        ];
    }
}
