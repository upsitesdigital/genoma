<?php
declare(strict_types=1);

namespace App\PaginaPadrao;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class PaginaPadraoController extends Controller
{
    #[Get('/pagina-padrao')]
    #[Get('/pagina-padrao/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: 0);
        $post   = get_post($pageId);

        if (!$post instanceof \WP_Post) {
            return ['titulo' => '', 'conteudo' => ''];
        }

        return [
            'titulo'   => (string) get_the_title($post),
            'conteudo' => $this->conteudo($post),
        ];
    }

    /**
     * Conteúdo real da página (editor padrão do WP), processado por
     * `the_content` (shortcodes, oEmbed, wpautop, blocos etc.) — não é ACF.
     * O React recebe HTML pronto e renderiza via dangerouslySetInnerHTML
     * dentro do wrapper `.post-content`.
     */
    private function conteudo(\WP_Post $targetPost): string
    {
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
}
