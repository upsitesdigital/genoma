<?php
declare(strict_types=1);

namespace Core\Framework;

class RouteResolver
{
    /**
     * Resolve o módulo responsável pela requisição atual. Chamado a partir dos
     * templates PHP (front-page.php, page.php, single.php, index.php).
     *
     * @return array{module: string, pageId: int|null, url: string, title: string}|null
     */
    public static function current(): ?array
    {
        // 0. Busca nativa do WordPress (?s=) — sempre resolve para o módulo de
        // resultado de pesquisa, mesmo sem uma Page com esse template criada
        // ainda (nesse caso pageId fica null e os campos ACF ficam vazios).
        if (is_search()) {
            $pageId = self::findModulePageId('resultado-pesquisa');
            return [
                'module' => 'resultado-pesquisa',
                'pageId' => $pageId,
                'url'    => home_url('/?s=' . urlencode(get_search_query())),
                'title'  => 'Resultado da pesquisa',
            ];
        }

        // 1. Page com template fw:* atribuído
        if (is_page()) {
            $page     = get_queried_object();
            $template = get_post_meta($page->ID, '_wp_page_template', true);

            if (is_string($template) && str_starts_with($template, 'fw:')) {
                $slug = substr($template, 3);
                return [
                    'module' => $slug,
                    'pageId' => $page->ID,
                    'url'    => (string) get_permalink($page->ID),
                    'title'  => get_the_title($page->ID),
                ];
            }

            // Página com "Modelo por omissão" (sem template fw:* atribuído) — usa
            // o módulo genérico pagina-padrao (título + conteúdo nativo do editor).
            // A home (is_front_page) segue seu próprio caminho no passo 2 abaixo.
            if (!is_front_page()) {
                return [
                    'module' => 'pagina-padrao',
                    'pageId' => $page->ID,
                    'url'    => (string) get_permalink($page->ID),
                    'title'  => get_the_title($page->ID),
                ];
            }
        }

        // 2. Front page (home)
        if (is_front_page()) {
            $pageId = (int) get_option('page_on_front') ?: null;
            return [
                'module' => 'home',
                'pageId' => $pageId,
                'url'    => home_url('/'),
                'title'  => get_bloginfo('name'),
            ];
        }

        // 3. Post individual (single.php)
        if (is_singular('post')) {
            $postId = get_the_ID();
            return [
                'module' => 'single-post',
                'pageId' => $postId ?: null,
                'url'    => (string) get_permalink($postId),
                'title'  => get_the_title($postId),
            ];
        }

        // 4. Nenhum módulo mapeado — 404 real (ver 404.php)
        return null;
    }

    /** Encontra o ID da Page que usa o template `fw:{$slug}`, se existir. */
    private static function findModulePageId(string $slug): ?int
    {
        $pages = get_pages([
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'fw:' . $slug,
            'number'     => 1,
        ]);

        return $pages[0]->ID ?? null;
    }
}
