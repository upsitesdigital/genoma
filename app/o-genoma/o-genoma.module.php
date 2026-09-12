<?php
declare(strict_types=1);

namespace App\OGenoma;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'o-genoma',
    name: 'O Genoma',
    route: '/o-genoma',
    template: true,
    templateLabel: 'Página · O Genoma',
)]
final class OGenomaModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_o-genoma',
            'title'    => 'O Genoma',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:o-genoma'],
            ]],
            'fields' => [
                // ── Hero (carrossel) ──────────────────────────────────────
                [
                    'key'          => 'field_o-genoma_hero_slides',
                    'name'         => 'hero_slides',
                    'label'        => 'Hero — Slides do Carrossel',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar Slide',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_o-genoma_hero_slide_eyebrow',
                            'name'          => 'eyebrow',
                            'label'         => 'Etiqueta',
                            'type'          => 'text',
                            'default_value' => 'O Genoma',
                        ],
                        [
                            'key'           => 'field_o-genoma_hero_slide_titulo',
                            'name'          => 'titulo',
                            'label'         => 'Título',
                            'type'          => 'textarea',
                            'rows'          => 2,
                            'required'      => 1,
                            'instructions'  => 'Use uma quebra de linha para controlar onde o título deve quebrar.',
                            'default_value' => 'Laboratório especializado em análises laboratoriais veterinárias',
                        ],
                        [
                            'key'           => 'field_o-genoma_hero_slide_imagem_1',
                            'name'          => 'imagem_1',
                            'label'         => 'Imagem 1 (menor)',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_o-genoma_hero_slide_imagem_2',
                            'name'          => 'imagem_2',
                            'label'         => 'Imagem 2 (maior)',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_o-genoma_hero_slide_destaque',
                            'name'          => 'destaque',
                            'label'         => 'Texto em destaque',
                            'type'          => 'textarea',
                            'rows'          => 3,
                            'instructions'  => 'Texto exibido entre as duas linhas divisórias, ao lado do título.',
                            'default_value' => 'Criado para apoiar médicos-veterinários na tomada de decisões clínicas com precisão, agilidade e confiabilidade.',
                        ],
                    ],
                ],
                [
                    'key'           => 'field_o-genoma_hero_autoplay',
                    'name'          => 'hero_autoplay',
                    'label'         => 'Hero — Autoplay do Carrossel',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 1,
                ],
                [
                    'key'               => 'field_o-genoma_hero_intervalo',
                    'name'              => 'hero_intervalo',
                    'label'             => 'Hero — Intervalo do Autoplay (ms)',
                    'type'              => 'number',
                    'default_value'     => 6000,
                    'min'               => 2000,
                    'step'              => 500,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_o-genoma_hero_autoplay',
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
