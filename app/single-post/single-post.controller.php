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
            'hero'       => $this->hero($pageId),
            'conteudo'   => $this->conteudo($pageId),
            'vejaTambem' => $this->vejaTambem($pageId),
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
        // Blog filtrado pela categoria; sem Page de blog, cai no arquivo nativo
        // (que o tema redireciona — ver Bootstrap::redirectCategoryArchive).
        $categoriaLink = $categorias[0] ?? null
            ? (\Core\Framework\RouteResolver::blogCategoryUrl($categorias[0]->slug)
                ?? (string) get_category_link($categorias[0]->term_id))
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

    /**
     * Conteúdo real do post (editor padrão do WP), processado por
     * `the_content` (shortcodes, oEmbed, wpautop, blocos etc.) — não é ACF,
     * vem nativamente do post type `post`. O React recebe HTML pronto e
     * renderiza via dangerouslySetInnerHTML dentro do wrapper `.post-content`.
     */
    private function conteudo(int $pageId): string
    {
        $targetPost = get_post($pageId);

        if (!$targetPost instanceof \WP_Post) {
            return '';
        }

        // Alguns filtros de `the_content` (oEmbed, blocos, [gallery], etc.)
        // dependem do global $post / loop atual — garantimos o contexto certo
        // e restauramos o estado original do global ao final.
        global $post;
        $previousPost = $post;

        $post = $targetPost; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        setup_postdata($post);

        $html = apply_filters('the_content', $targetPost->post_content);

        if ($previousPost instanceof \WP_Post) {
            $post = $previousPost; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
            setup_postdata($post);
        } else {
            wp_reset_postdata();
        }

        return (string) $html;
    }

    /**
     * "Veja também" — 3 posts relacionados reais (grid 3 colunas no Figma).
     * Critério: mesma(s) categoria(s) do post atual (`category__in`), sempre
     * excluindo o próprio post (`post__not_in`). Se a categoria não tiver posts
     * suficientes para completar o grid, o restante é preenchido com posts
     * recentes de qualquer categoria (fallback), ainda excluindo o post atual
     * e os já selecionados.
     */
    private function vejaTambem(int $pageId): array
    {
        $limite = 3;

        $post = get_post($pageId);
        if (!$post instanceof \WP_Post) {
            return [];
        }

        $categoriaIds = wp_list_pluck(get_the_category($post->ID), 'term_id');

        $relacionados = [];

        if (!empty($categoriaIds)) {
            $relacionados = get_posts([
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => $limite,
                'post__not_in'        => [$pageId],
                'category__in'        => $categoriaIds,
                'ignore_sticky_posts' => true,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'no_found_rows'       => true,
            ]);
        }

        $faltam = $limite - count($relacionados);

        // Fallback: completa com posts recentes de qualquer categoria quando a
        // mesma categoria do post atual não tem itens suficientes para o grid.
        if ($faltam > 0) {
            $excluidos = array_merge([$pageId], wp_list_pluck($relacionados, 'ID'));

            $recentes = get_posts([
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => $faltam,
                'post__not_in'        => $excluidos,
                'ignore_sticky_posts' => true,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'no_found_rows'       => true,
            ]);

            $relacionados = array_merge($relacionados, $recentes);
        }

        return array_map(fn (\WP_Post $relacionado): array => $this->mapRelacionado($relacionado), $relacionados);
    }

    private function mapRelacionado(\WP_Post $post): array
    {
        $categorias    = get_the_category($post->ID);
        $categoriaNome = $categorias[0]->name ?? '';

        return [
            'id'        => $post->ID,
            'titulo'    => (string) get_the_title($post),
            'resumo'    => $this->relacionadoExcerpt($post),
            'data'      => (string) get_the_date('j \d\e F \d\e Y', $post),
            'link'      => (string) get_permalink($post),
            'categoria' => (string) $categoriaNome,
            'imagem'    => $this->relacionadoImagem($post),
        ];
    }

    private function relacionadoExcerpt(\WP_Post $post): string
    {
        if ($post->post_excerpt !== '') {
            return wp_strip_all_tags($post->post_excerpt);
        }

        return wp_trim_words(wp_strip_all_tags($post->post_content), 20, '…');
    }

    private function relacionadoImagem(\WP_Post $post): array
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

        return [
            'src'    => get_template_directory_uri() . '/app/single-post/assets/veja-tambem-placeholder.png',
            'alt'    => '',
            'width'  => 315,
            'height' => 170,
            'sizes'  => [],
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
