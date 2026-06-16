<?php
declare(strict_types=1);

namespace App\Home;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;
use Core\Framework\Attributes\Required;

#[Mod(
    slug: 'home',
    name: 'Home',
    route: '/',
    template: true,
    templateLabel: 'Página · Home',
)]
#[Required]
final class HomeModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_home',
            'title'    => 'Home',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:home'],
            ]],
            'fields' => [
                [
                    'key'      => 'field_home_hero_titulo',
                    'name'     => 'hero_titulo',
                    'label'    => 'Título do Hero',
                    'type'     => 'text',
                    'required' => 1,
                ],
                [
                    'key'   => 'field_home_hero_subtitulo',
                    'name'  => 'hero_subtitulo',
                    'label' => 'Subtítulo do Hero',
                    'type'  => 'textarea',
                    'rows'  => 3,
                ],
                [
                    'key'           => 'field_home_hero_imagem',
                    'name'          => 'hero_imagem',
                    'label'         => 'Imagem do Hero',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],
                [
                    'key'   => 'field_home_cta_texto',
                    'name'  => 'cta_texto',
                    'label' => 'Texto do Botão (CTA)',
                    'type'  => 'text',
                ],
                [
                    'key'   => 'field_home_cta_link',
                    'name'  => 'cta_link',
                    'label' => 'Link do Botão (CTA)',
                    'type'  => 'url',
                ],
            ],
        ]);
    }
}
