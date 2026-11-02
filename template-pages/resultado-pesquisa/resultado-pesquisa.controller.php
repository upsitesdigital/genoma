<?php
declare(strict_types=1);

namespace App\ResultadoPesquisa;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;

/**
 * Resultado da pesquisa — porte do app/resultado-pesquisa/resultado-pesquisa.controller.php do tema
 * React: mesmos campos (acf_fields.php, bloco resultado-pesquisa) e formato do
 * resultado-pesquisa.schema.ts (hero + resultados), para o markup ser portado 1:1.
 *
 * Os resultados são a busca nativa do WP (WP_Query com `s`) em posts e páginas publicados, sem a
 * própria página de resultados. No React a lista paginava pela REST (/resultado-pesquisa-lista) sem
 * recarregar; aqui toda navegação é carregamento de página: o template-parts/search.php passa o termo
 * e a ?pagina= em $params (ResultadoPesquisaModule::params).
 *
 * Sem cache de propósito (o `npm run review` avisa — é esperado): a chave do cache do page() leva os
 * $params, e o termo e a página vêm do visitante — cada URL de busca gravaria um transient novo no
 * wp_options, sem limite. A busca é uma WP_Query só; o hero lê poucos campos.
 *
 * Diferenças em relação ao React (intencionais):
 * - texto salvo vazio → null (o React devolvia ''; nos dois o elemento não aparece);
 * - semResultadosTexto saiu do hero para `resultados` (é a mensagem da lista: continua valendo com o
 *   hero oculto pelo editor);
 * - resultados.paginas: os links das pílulas de paginação (no React eram botões com estado no JS);
 * - imagem do post: ImageData com o id do anexo (fw_img: src medium_large, como no React, + srcset);
 * - post com senha: resumo sempre vazio (como no blog, cuja resposta vai para o cache e seria servida a todos).
 */
final class ResultadoPesquisaController extends Controller
{
    /** Lupa do campo de busca (React: defaultBuscaIcone — não é campo). */
    private const BUSCA_ICONE = 'template-parts/includes/resultado-pesquisa/hero/assets/diagnostico-veterinario-em-sp-73.svg';

    /** Imagem do card sem imagem destacada (React: defaultPostImagem). */
    private const POST_IMAGEM = 'template-parts/includes/shared/post-item/assets/diagnostico-veterinario-em-sp-74.png';

    private const SEM_RESULTADOS = 'Não encontramos nenhum conteúdo para essa busca. Tente outro termo.';

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST): na busca (/?s=) pelo
     * template-parts/search.php, com $params; na própria página de modelo pelo app.php, sem termo.
     *
     * @param array{termo?: string, pagina?: int} $params termo buscado ('' = sem busca) e página da lista (≥ 1).
     */
    public function page(int $pageId, array $params = []): array
    {
        $termo  = (string) ($params['termo'] ?? '');
        $pagina = max(1, (int) ($params['pagina'] ?? 1));

        return [
            'hero'       => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId, $termo)),
            // lista de resultados: não é seção ACF (não tem "Ocultar seção"), sai sempre — shared/post-list
            'resultados' => $this->resultados($pageId, $termo, $pagina),
        ];
    }

    /** Etiqueta (h1) + título montado do termo + campo de busca já com o termo. */
    private function hero(int $pageId, string $termo): array
    {
        $icone = $this->asset(self::BUSCA_ICONE);
        if ($icone) unset($icone['svg']); // sai como <img>, como no React

        return [
            'eyebrow'          => $this->text($pageId, 'hero_eyebrow', 'Busca'),
            'buscaPlaceholder' => $this->text($pageId, 'hero_busca_placeholder', 'Busca'),
            'buscaIcone'       => $icone,
            'termo'            => $termo,
        ];
    }

    /**
     * Busca nativa do WP em posts e páginas publicados (12 por página = "Posts por página" de
     * Configurações → Leitura). Sem termo → nenhuma consulta e a mensagem de "sem resultados".
     */
    private function resultados(int $pageId, string $termo, int $pagina): array
    {
        $query = null;
        if ($termo !== '') {
            $args = [
                's'                   => $termo,
                'post_type'           => ['post', 'page'],
                'post_status'         => 'publish',
                'posts_per_page'      => max(1, (int) get_option('posts_per_page')),
                'paged'               => $pagina,
                'ignore_sticky_posts' => true,
            ];
            // a própria página de resultados não aparece como resultado
            if ($pageId) $args['post__not_in'] = [$pageId];
            $query = new \WP_Query($args);
            // imagens destacadas dos cards (anexo + metadados) numa consulta só, sem uma por post
            update_post_thumbnail_cache($query);
        }
        $total = $query ? max(1, (int) $query->max_num_pages) : 1;

        return [
            'posts'              => $query ? array_map(fn(\WP_Post $post): array => $this->post($post), $query->posts) : [],
            'paginaAtual'        => $pagina,
            'totalPaginas'       => $total,
            'totalPosts'         => $query ? (int) $query->found_posts : 0,
            'termo'              => $termo,
            // uma pílula por página (como no React: todas, sem reticências); a 1ª sem ?pagina=
            'paginas'            => $total > 1 ? array_map(fn(int $n): array => [
                'numero' => $n,
                'url'    => add_query_arg(['s' => rawurlencode($termo), 'pagina' => $n > 1 ? $n : false], home_url('/')),
                'atual'  => $n === $pagina,
            ], range(1, $total)) : [],
            // vazio no painel → o view usa "Nenhum resultado encontrado." (como no React)
            'semResultadosTexto' => $this->text($pageId, 'sem_resultados_texto', self::SEM_RESULTADOS),
        ];
    }

    /** Card do resultado (PostData do shared/post-item); categoria só de post. */
    private function post(\WP_Post $post): array
    {
        $categorias = $post->post_type === 'post' ? get_the_category($post->ID) : [];

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
