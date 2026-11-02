<?php
declare(strict_types=1);

namespace App\SinglePost;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Post individual — porte do app/single-post/single-post.controller.php do tema React, com as
 * chaves do single-post.schema.ts (hero, conteudo, vejaTambem). Sem campos ACF: tudo vem do post
 * real (título, data, 1ª categoria, imagem destacada, conteúdo) e de posts relacionados reais.
 *
 * Diferenças em relação ao React (intencionais):
 * - sem $this->section(): o módulo não tem campos, então não há "Ocultar seção";
 * - 'conteudo' é a referência do post ({id}), não o HTML: o the_content roda na seção a cada
 *   página (template-parts/includes/single-post/conteudo/). O retorno do page() vai para o cache,
 *   e o HTML do editor depende da requisição — o WP enfileira o CSS dos blocos usados enquanto
 *   renderiza, e shortcodes (formulário, embed) não podem sair congelados do cache. Mesmo
 *   princípio do Controller::cf7(). Post sem conteúdo → null (a seção some, como no React);
 * - imagens como ImageData com o id do anexo (o fw_img gera width/height e srcset).
 */
final class SinglePostController extends Controller
{
    /** Imagem do hero quando o post não tem imagem destacada (a mesma do React). */
    private const IMAGEM_PADRAO = 'template-parts/includes/single-post/hero/assets/diagnostico-veterinario-em-sp-75.png';

    /** Imagem dos cards de "Veja também" sem imagem destacada (a mesma do React). */
    private const RELACIONADO_IMAGEM_PADRAO = 'template-parts/includes/single-post/veja-tambem/assets/diagnostico-veterinario-em-sp-76.png';

    /** Quantos posts relacionados (grade de 3 colunas). */
    private const LIMITE_RELACIONADOS = 3;

    /** Formato da data do hero e dos cards ("17 de Setembro de 2026"). */
    private const FORMATO_DATA = 'j \d\e F \d\e Y';

    /**
     * Dados do post — chamado direto em PHP pelo View::render (sem REST). Post com senha não
     * chega aqui: o View::render mostra só o formulário de senha do WP.
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        $post = get_post($pageId);
        if (!$post instanceof \WP_Post) {
            return ['hero' => null, 'conteudo' => null, 'vejaTambem' => []];
        }

        return [
            'hero'       => $this->hero($post),
            'conteudo'   => trim($post->post_content) !== '' ? ['id' => $post->ID] : null,
            'vejaTambem' => $this->vejaTambem($post),
        ];
    }

    /** Hero: data, 1ª categoria (link para o blog filtrado), título, compartilhar e imagem. */
    private function hero(\WP_Post $post): array
    {
        $categoria = get_the_category($post->ID)[0] ?? null;

        return [
            'titulo'        => (string) get_the_title($post),
            'data'          => (string) get_the_date(self::FORMATO_DATA, $post),
            'categoria'     => $categoria ? $categoria->name : '',
            'categoriaLink' => $categoria ? $this->categoriaLink($categoria) : '',
            'imagem'        => $this->destacada($post, 'large') ?? $this->padrao(self::IMAGEM_PADRAO),
            'url'           => (string) get_permalink($post),
        ];
    }

    /**
     * "Veja também" — mesmos critérios do React: até 3 posts da(s) mesma(s) categoria(s), do mais
     * recente para o mais antigo, sem o próprio post; faltando, completa com os mais recentes de
     * qualquer categoria (sem repetir). Lista vazia → a seção some.
     */
    private function vejaTambem(\WP_Post $post): array
    {
        $base = [
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'no_found_rows'       => true,
        ];

        $categoriaIds = wp_list_pluck(get_the_category($post->ID), 'term_id');
        $relacionados = $categoriaIds ? get_posts($base + [
            'posts_per_page' => self::LIMITE_RELACIONADOS,
            'post__not_in'   => [$post->ID], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- só o post atual (e os já escolhidos), como no React
            'category__in'   => $categoriaIds,
        ]) : [];

        $faltam = self::LIMITE_RELACIONADOS - count($relacionados);
        if ($faltam > 0) {
            $relacionados = array_merge($relacionados, get_posts($base + [
                'posts_per_page' => $faltam,
                'post__not_in'   => array_merge([$post->ID], wp_list_pluck($relacionados, 'ID')), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- idem
            ]));
        }

        return array_map(fn(\WP_Post $relacionado): array => [
            'id'        => $relacionado->ID,
            'titulo'    => (string) get_the_title($relacionado),
            'resumo'    => $this->resumo($relacionado),
            'data'      => (string) get_the_date(self::FORMATO_DATA, $relacionado),
            'link'      => (string) get_permalink($relacionado),
            'categoria' => get_the_category($relacionado->ID)[0]->name ?? '',
            'imagem'    => $this->destacada($relacionado, 'medium_large') ?? $this->padrao(self::RELACIONADO_IMAGEM_PADRAO),
        ], $relacionados);
    }

    /**
     * Resumo do card: o excerpt ou as 20 primeiras palavras do conteúdo; post com senha → '' sempre
     * (não post_password_required(), que depende do cookie de quem já digitou a senha: o retorno do
     * page() vai para o cache e seria servido a todos — mesma regra do blog e da busca).
     */
    private function resumo(\WP_Post $post): string
    {
        if ($post->post_password !== '') return '';
        if ($post->post_excerpt !== '') return wp_strip_all_tags($post->post_excerpt);

        return wp_trim_words(wp_strip_all_tags($post->post_content), 20, '…');
    }

    /**
     * Link da categoria: o blog filtrado (/blog/?categoria={slug}, a página com o modelo "Página ·
     * Blog" ou a página de posts) — como o RouteResolver::blogCategoryUrl do React; sem página de
     * blog, o arquivo nativo da categoria.
     */
    private function categoriaLink(\WP_Term $categoria): string
    {
        $blog   = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'fw:blog', 'number' => 1]); // phpcs:ignore WordPress.DB.SlowDBQuery -- uma página, resultado no cache do page()
        $blogId = $blog ? $blog[0]->ID : (int) get_option('page_for_posts');
        $link   = $blogId ? add_query_arg('categoria', $categoria->slug, (string) get_permalink($blogId)) : get_category_link($categoria->term_id);

        return esc_url_raw((string) $link);
    }

    /** Imagem destacada no tamanho pedido (ImageData com o id do anexo); sem imagem → null. */
    private function destacada(\WP_Post $post, string $size): ?array
    {
        $thumbId = (int) get_post_thumbnail_id($post);
        $src     = $thumbId ? wp_get_attachment_image_src($thumbId, $size) : false;
        if (!$src) return null;

        return [
            'id'     => $thumbId,
            'src'    => (string) $src[0],
            'alt'    => (string) get_post_meta($thumbId, '_wp_attachment_image_alt', true),
            'width'  => $src[1] ? (int) $src[1] : null,
            'height' => $src[2] ? (int) $src[2] : null,
            'sizes'  => [],
        ];
    }

    /** Imagem padrão do tema (asset da seção) como ImageData. */
    private function padrao(string $path): ?array
    {
        $imagem = $this->asset($path);
        if ($imagem) unset($imagem['svg']);

        return $imagem;
    }
}
