<?php
declare(strict_types=1);

namespace App\Home;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class HomeController extends Controller
{
    #[Get('/home')]
    #[Get('/home/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: get_option('page_on_front') ?: 0);

        return [
            'hero' => [
                'titulo'    => (string) ($this->field($pageId, 'hero_titulo') ?? ''),
                'subtitulo' => (string) ($this->field($pageId, 'hero_subtitulo') ?? ''),
                'imagem'    => $this->image($this->field($pageId, 'hero_imagem')),
            ],
            'cta' => [
                'texto' => (string) ($this->field($pageId, 'cta_texto') ?? ''),
                'link'  => (string) ($this->field($pageId, 'cta_link') ?? ''),
            ],
        ];
    }
}
