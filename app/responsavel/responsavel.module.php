<?php
declare(strict_types=1);

namespace App\Responsavel;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'responsavel',
    name: 'Responsável',
    route: '/responsavel',
    template: true,
    templateLabel: 'Página · Responsável',
)]
final class ResponsavelModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_responsavel',
            'title'    => 'Responsável',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:responsavel'],
            ]],
            'fields' => [
                // ── Hero (carrossel) ──────────────────────────────────────
                [
                    'key'          => 'field_responsavel_hero_slides',
                    'name'         => 'hero_slides',
                    'label'        => 'Hero — Slides do Carrossel',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar Slide',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_responsavel_hero_slide_imagem',
                            'name'          => 'imagem',
                            'label'         => 'Imagem',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_eyebrow',
                            'name'          => 'eyebrow',
                            'label'         => 'Etiqueta',
                            'type'          => 'text',
                            'default_value' => 'Responsável',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_titulo',
                            'name'          => 'titulo',
                            'label'         => 'Título',
                            'type'          => 'textarea',
                            'rows'          => 2,
                            'required'      => 1,
                            'instructions'  => 'Use uma quebra de linha para controlar onde o título deve quebrar.',
                            'default_value' => 'Cuidado, confiança e diagnóstico preciso para o seu pet',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_subtitulo',
                            'name'          => 'subtitulo',
                            'label'         => 'Subtítulo',
                            'type'          => 'textarea',
                            'rows'          => 2,
                            'default_value' => 'Suporte técnico, agilidade e condições especiais para médicos-veterinários que buscam excelência no cuidado.',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_cta_primario_texto',
                            'name'          => 'cta_primario_texto',
                            'label'         => 'CTA Primário — Texto',
                            'type'          => 'text',
                            'default_value' => 'Fale conosco',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_cta_primario_link',
                            'name'          => 'cta_primario_link',
                            'label'         => 'CTA Primário — Link',
                            'type'          => 'url',
                            'default_value' => '#',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_cta_secundario_texto',
                            'name'          => 'cta_secundario_texto',
                            'label'         => 'CTA Secundário — Texto',
                            'type'          => 'text',
                            'default_value' => 'Nossos serviços',
                        ],
                        [
                            'key'           => 'field_responsavel_hero_slide_cta_secundario_link',
                            'name'          => 'cta_secundario_link',
                            'label'         => 'CTA Secundário — Link',
                            'type'          => 'url',
                            'default_value' => '#',
                        ],
                    ],
                ],
                [
                    'key'           => 'field_responsavel_hero_autoplay',
                    'name'          => 'hero_autoplay',
                    'label'         => 'Hero — Autoplay do Carrossel',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 1,
                ],
                [
                    'key'               => 'field_responsavel_hero_intervalo',
                    'name'              => 'hero_intervalo',
                    'label'             => 'Hero — Intervalo do Autoplay (ms)',
                    'type'              => 'number',
                    'default_value'     => 6000,
                    'min'               => 2000,
                    'step'              => 500,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_responsavel_hero_autoplay',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
