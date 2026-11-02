<?php
declare(strict_types=1);

namespace App\PaginaPadrao;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Página comum (sem modelo) — porte do app/pagina-padrao/pagina-padrao.controller.php do tema
 * React. Sem campos ACF: título e conteúdo do editor.
 *
 * Diferenças em relação ao React (intencionais):
 * - o pagina-padrao.schema.ts era plano ({titulo, conteudo}) e o view tinha uma só seção
 *   (<section id="conteudo">) com os dois; aqui a seção é 'conteudo' => {titulo, id}, no formato
 *   uma chave por seção do v3;
 * - o conteúdo vai como referência da página (id), não como HTML: o the_content roda na seção a
 *   cada página (template-parts/includes/pagina-padrao/conteudo/) — o retorno do page() vai para
 *   o cache, e o HTML do editor depende da requisição (CSS dos blocos enfileirado ao renderizar,
 *   shortcodes). Mesmo princípio do Controller::cf7();
 * - sem $this->section(): o módulo não tem campos, então não há "Ocultar seção".
 */
final class PaginaPadraoController extends Controller
{
    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST). Página com senha
     * não chega aqui: o View::render mostra só o formulário de senha do WP.
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        $page = get_post($pageId);

        return [
            'conteudo' => $page instanceof \WP_Post
                ? ['titulo' => (string) get_the_title($page), 'id' => $page->ID]
                : null,
        ];
    }
}
