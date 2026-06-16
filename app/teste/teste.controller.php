<?php
declare(strict_types=1);

namespace App\Teste;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class TesteController extends Controller
{
    #[Get('/teste')]
    #[Get('/teste/:id')]
    #[Cache(ttl: 60)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: get_option('page_on_front') ?: 0);

        $cards = [];
        if (have_rows('cards', $pageId)) {
            while (the_row()) {
                $cards[] = [
                    'icone'  => get_sub_field('icone'),
                    'titulo' => get_sub_field('titulo'),
                    'texto'  => get_sub_field('texto'),
                ];
            }
        }

        return [
            'titulo'        => $this->field($pageId, 'titulo'),
            'descricao'     => $this->field($pageId, 'descricao'),
            'numero'        => (int) $this->field($pageId, 'numero'),
            'imagem'        => $this->image($this->field($pageId, 'imagem')),
            'cor'           => $this->field($pageId, 'cor') ?: 'azul',
            'exibir_banner' => (bool) $this->field($pageId, 'exibir_banner'),
            'cta'           => $this->field($pageId, 'cta'),
            'cards'         => $cards,
        ];
    }
}
