<?php
declare(strict_types=1);

namespace App\Blog;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Blog — porte do app/blog/blog.controller.php do tema React: mesmos campos (acf_fields.php, bloco
 * blog) e mesmo formato do blog.schema.ts (hero + listaPost, chaves iguais), para o markup ser portado 1:1.
 *
 * A lista é de posts reais do WP (post nativo, taxonomia `category`), sem campos ACF. No React ela
 * paginava e filtrava pela REST (/blog-posts) sem recarregar; aqui toda navegação é carregamento de
 * página: o index.php (a página fw:blog é desviada para ele pelo BlogModule) lê ?categoria= e ?pagina=
 * da URL, valida (BlogModule::params) e passa em $params — a chave do #[Cache] é id + $params.
 *
 * Diferenças em relação ao React (intencionais):
 * - texto salvo vazio → null (o React devolvia ''; nos dois o elemento não aparece);
 * - listaPost.paginas: os links das pílulas de paginação (no React eram botões com estado no JS);
 * - imagem do post: ImageData com o id do anexo (fw_img: src medium_large, como no React, + srcset);
 * - post com senha: resumo sempre vazio (o React mostrava o resumo a quem já tinha digitado a senha;
 *   aqui a resposta vai para o cache e seria servida a todos).
 */
final class BlogController extends Controller
{
    /** Lupa do campo de busca (React: defaultBuscaIcone — não é campo). */
    private const BUSCA_ICONE = 'template-parts/includes/blog/hero/assets/diagnostico-veterinario-em-sp-73.svg';

    /** Imagem do card de post sem imagem destacada (React: defaultPostImagem). */
    private const POST_IMAGEM = 'template-parts/includes/shared/post-item/assets/diagnostico-veterinario-em-sp-74.png';

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST), via index.php.
     *
     * @param array{pagina?: int, categoria?: string|null} $params pagina ≥ 1 (padrão 1); categoria = slug
     *        de uma categoria que existe, '' = todas (padrão), null = a da URL não existe (lista vazia).
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId, array $params = []): array
    {
        $pagina    = max(1, (int) ($params['pagina'] ?? 1));
        $categoria = array_key_exists('categoria', $params) && $params['categoria'] === null ? null : (string) ($params['categoria'] ?? '');
        // sem página de blog (pageId 0): /blog/, como no React
        $base      = $pageId ? (string) get_permalink($pageId) : home_url('/blog/');

        return [
            'hero'      => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId, $base, $categoria)),
            // lista de posts: não é seção ACF (não tem campos nem "Ocultar seção"), sai sempre — shared/post-list
            'listaPost' => $this->listaPost($base, $pagina, $categoria),
        ];
    }

    /** Etiqueta (h1) + título (h2) + campo de busca + pílulas de categoria. */
    private function hero(int $pageId, string $base, ?string $categoria): array
    {
        $icone = $this->asset(self::BUSCA_ICONE);
        if ($icone) unset($icone['svg']); // sai como <img>, como no React

        return [
            'eyebrow'          => $this->text($pageId, 'hero_eyebrow', 'Blog'),
            'titulo'           => $this->text($pageId, 'hero_titulo', 'Simply dummy text of the  industry.'),
            'buscaPlaceholder' => $this->text($pageId, 'hero_busca_placeholder', 'Busca'),
            'buscaIcone'       => $icone,
            'categorias'       => $this->categorias($base, $categoria),
        ];
    }

    /**
     * Pílulas de categoria (não são campo ACF): "Todos" + uma por categoria com post publicado, em
     * ordem alfabética, com "Em destaque" logo depois de "Todos". `destaque` = a do filtro atual.
     */
    private function categorias(string $base, ?string $atual): array
    {
        $categorias = [['titulo' => __('Todos', 'upsites'), 'link' => $base, 'destaque' => $atual === '']];

        $termos = get_categories(['hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC']);
        usort($termos, static fn(\WP_Term $a, \WP_Term $b): int => ($b->slug === 'em-destaque') <=> ($a->slug === 'em-destaque'));

        foreach ($termos as $termo) {
            $categorias[] = [
                'titulo'   => $termo->name,
                'link'     => add_query_arg('categoria', $termo->slug, $base),
                'destaque' => $atual === $termo->slug,
            ];
        }

        return $categorias;
    }

    /**
     * Posts publicados (12 por página = "Posts por página" de Configurações → Leitura), filtrados pela
     * categoria. Categoria inexistente (null) → lista vazia, sem consulta.
     */
    private function listaPost(string $base, int $pagina, ?string $categoria): array
    {
        $query = null;
        if ($categoria !== null) {
            $args = [
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => max(1, (int) get_option('posts_per_page')),
                'paged'               => $pagina,
                'ignore_sticky_posts' => true,
            ];
            if ($categoria !== '') $args['category_name'] = $categoria;
            $query = new \WP_Query($args);
            // imagens destacadas dos cards (anexo + metadados) numa consulta só, sem uma por post
            update_post_thumbnail_cache($query);
        }
        $total = $query ? max(1, (int) $query->max_num_pages) : 1;

        return [
            'posts'          => $query ? array_map(fn(\WP_Post $post): array => $this->post($post), $query->posts) : [],
            'paginaAtual'    => $pagina,
            'totalPaginas'   => $total,
            'totalPosts'     => $query ? (int) $query->found_posts : 0,
            'categoriaAtual' => (string) $categoria,
            // uma pílula por página (como no React: todas, sem reticências); a 1ª sem ?pagina=
            'paginas'        => $total > 1 ? array_map(fn(int $n): array => [
                'numero' => $n,
                'url'    => add_query_arg(['categoria' => $categoria ?: false, 'pagina' => $n > 1 ? $n : false], $base),
                'atual'  => $n === $pagina,
            ], range(1, $total)) : [],
        ];
    }

    /** Card do post (PostData do shared/post-item). */
    private function post(\WP_Post $post): array
    {
        $categorias = get_the_category($post->ID);

        return [
            'id'        => $post->ID,
            'titulo'    => (string) get_the_title($post),
            'resumo'    => $this->resumo($post),
            'data'      => (string) get_the_date('j \d\e F \d\e Y', $post),
            'link'      => (string) get_permalink($post),
            'categoria' => (string) ($categorias[0]->name ?? ''),
            'imagem'    => $this->imagem($post),
        ];
    }

    /** Resumo do editor; vazio → as 20 primeiras palavras do conteúdo (como no React). */
    private function resumo(\WP_Post $post): string
    {
        if ($post->post_password !== '') return '';
        if ($post->post_excerpt !== '') return wp_strip_all_tags($post->post_excerpt);

        return wp_trim_words(wp_strip_all_tags($post->post_content), 20, '…');
    }

    /** Imagem destacada (medium_large) ou, sem ela, a imagem padrão do tema. */
    private function imagem(\WP_Post $post): ?array
    {
        $thumbId = (int) get_post_thumbnail_id($post);
        $src     = $thumbId ? wp_get_attachment_image_url($thumbId, 'medium_large') : false;
        if ($src) {
            return [
                'id'     => $thumbId,
                'src'    => $src,
                'alt'    => (string) get_post_meta($thumbId, '_wp_attachment_image_alt', true),
                'width'  => null,
                'height' => null,
                'sizes'  => [],
            ];
        }

        $padrao = $this->asset(self::POST_IMAGEM);
        if ($padrao) unset($padrao['svg']);

        return $padrao;
    }
}
