<?php
declare(strict_types=1);

namespace App\Veterinarios;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'veterinarios',
    name: 'Veterinários',
    route: '/veterinarios',
    template: true,
    templateLabel: 'Página · Veterinários',
)]
final class VeterinariosModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_veterinarios',
            'title'    => 'Veterinários',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:veterinarios'],
            ]],
            'fields' => [
                // ── Hero (carrossel) ──────────────────────────────────────
                [
                    'key'          => 'field_veterinarios_hero_slides',
                    'name'         => 'hero_slides',
                    'label'        => 'Hero — Slides do Carrossel',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar Slide',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_veterinarios_hero_slide_imagem',
                            'name'          => 'imagem',
                            'label'         => 'Imagem',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_eyebrow',
                            'name'          => 'eyebrow',
                            'label'         => 'Etiqueta (H1)',
                            'type'          => 'text',
                            'instructions'  => 'Renderizado como H1 da página apenas no primeiro slide (o Título abaixo é H2).',
                            'default_value' => 'Veterinários',
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_titulo',
                            'name'          => 'titulo',
                            'label'         => 'Título',
                            'type'          => 'textarea',
                            'rows'          => 2,
                            'required'      => 1,
                            'instructions'  => 'Use uma quebra de linha para controlar onde o título deve quebrar.',
                            'default_value' => "Parceria que fortalece\no seu diagnóstico",
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_subtitulo',
                            'name'          => 'subtitulo',
                            'label'         => 'Subtítulo',
                            'type'          => 'textarea',
                            'rows'          => 2,
                            'default_value' => 'Suporte técnico, agilidade e condições especiais para médicos-veterinários que buscam excelência no cuidado.',
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_cta_primario_texto',
                            'name'          => 'cta_primario_texto',
                            'label'         => 'CTA Primário — Texto',
                            'type'          => 'text',
                            'default_value' => 'Quero ser parceiro',
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_cta_primario_link',
                            'name'          => 'cta_primario_link',
                            'label'         => 'CTA Primário — Link',
                            'type'          => 'text',
                            'default_value' => '#',
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_cta_secundario_texto',
                            'name'          => 'cta_secundario_texto',
                            'label'         => 'CTA Secundário — Texto',
                            'type'          => 'text',
                            'default_value' => 'Nossos serviços',
                        ],
                        [
                            'key'           => 'field_veterinarios_hero_slide_cta_secundario_link',
                            'name'          => 'cta_secundario_link',
                            'label'         => 'CTA Secundário — Link',
                            'type'          => 'text',
                            'default_value' => '#',
                        ],
                    ],
                ],
                [
                    'key'           => 'field_veterinarios_hero_autoplay',
                    'name'          => 'hero_autoplay',
                    'label'         => 'Hero — Autoplay do Carrossel',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 1,
                ],
                [
                    'key'               => 'field_veterinarios_hero_intervalo',
                    'name'              => 'hero_intervalo',
                    'label'             => 'Hero — Intervalo do Autoplay (ms)',
                    'type'              => 'number',
                    'default_value'     => 6000,
                    'min'               => 2000,
                    'step'              => 500,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_veterinarios_hero_autoplay',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],

                // ── Suporte ───────────────────────────────────────────────
                [
                    'key'           => 'field_veterinarios_suporte_eyebrow',
                    'name'          => 'suporte_eyebrow',
                    'label'         => 'Suporte — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Suporte técnico',
                ],
                [
                    'key'           => 'field_veterinarios_suporte_titulo',
                    'name'          => 'suporte_titulo',
                    'label'         => 'Suporte — Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'instructions'  => 'Use uma quebra de linha para controlar onde o título deve quebrar.',
                    'default_value' => "10 anos de atuação e mais de\n150 mil exames realizados.",
                ],
                [
                    'key'           => 'field_veterinarios_suporte_texto',
                    'name'          => 'suporte_texto',
                    'label'         => 'Suporte — Texto',
                    'type'          => 'textarea',
                    'rows'          => 4,
                    'default_value' => 'No Genoma Diagnóstico Veterinário, atuamos lado a lado com o médico-veterinário, oferecendo suporte técnico, agilidade operacional e condições especiais para quem busca excelência no cuidado com seus pacientes.',
                ],
                [
                    'key'           => 'field_veterinarios_suporte_quote',
                    'name'          => 'suporte_quote',
                    'label'         => 'Suporte — Texto em destaque',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'instructions'  => 'Texto exibido entre as duas linhas divisórias, ao lado do título.',
                    'default_value' => 'Somos um laboratório preparado para atender clínicas e hospitais veterinários com eficiência, confiança e proximidade.',
                ],
                [
                    'key'           => 'field_veterinarios_suporte_imagem_1',
                    'name'          => 'suporte_imagem_1',
                    'label'         => 'Suporte — Imagem 1 (menor)',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],
                [
                    'key'           => 'field_veterinarios_suporte_imagem_2',
                    'name'          => 'suporte_imagem_2',
                    'label'         => 'Suporte — Imagem 2 (maior)',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],

                // ── Praticidade ───────────────────────────────────────────
                [
                    'key'           => 'field_veterinarios_praticidade_eyebrow',
                    'name'          => 'praticidade_eyebrow',
                    'label'         => 'Praticidade — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Praticidade',
                ],
                [
                    'key'           => 'field_veterinarios_praticidade_titulo',
                    'name'          => 'praticidade_titulo',
                    'label'         => 'Praticidade — Título',
                    'type'          => 'text',
                    'default_value' => 'Coleta de Amostras',
                ],
                [
                    'key'           => 'field_veterinarios_praticidade_texto',
                    'name'          => 'praticidade_texto',
                    'label'         => 'Praticidade — Texto',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'default_value' => "Para garantir praticidade e segurança no envio das amostras, contamos com serviço de coleta por motoboy\nnos seguintes horários:",
                ],
                [
                    'key'           => 'field_veterinarios_praticidade_icone',
                    'name'          => 'praticidade_icone',
                    'label'         => 'Praticidade — Ícone dos Horários',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],
                [
                    'key'          => 'field_veterinarios_praticidade_horarios',
                    'name'         => 'praticidade_horarios',
                    'label'        => 'Praticidade — Horários',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar horário',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_veterinarios_praticidade_horario_texto',
                            'name'          => 'texto',
                            'label'         => 'Texto',
                            'type'          => 'text',
                            'default_value' => 'Seg a sex: das 9h às 17h',
                        ],
                    ],
                ],
                [
                    'key'           => 'field_veterinarios_praticidade_imagem',
                    'name'          => 'praticidade_imagem',
                    'label'         => 'Praticidade — Imagem',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],

                // ── Exames ────────────────────────────────────────────────
                [
                    'key'           => 'field_veterinarios_exames_eyebrow',
                    'name'          => 'exames_eyebrow',
                    'label'         => 'Exames — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Exames',
                ],
                [
                    'key'           => 'field_veterinarios_exames_titulo',
                    'name'          => 'exames_titulo',
                    'label'         => 'Exames — Título',
                    'type'          => 'text',
                    'default_value' => 'Portfólio de Exames',
                ],
                [
                    'key'           => 'field_veterinarios_exames_texto',
                    'name'          => 'exames_texto',
                    'label'         => 'Exames — Texto',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'default_value' => 'Disponibilizamos um portfólio completo de exames laboratoriais para suporte ao diagnóstico veterinário, contemplando exames de rotina e análises especializadas.',
                ],
                [
                    'key'           => 'field_veterinarios_exames_cta_texto',
                    'name'          => 'exames_cta_texto',
                    'label'         => 'Exames — CTA Texto',
                    'type'          => 'text',
                    'default_value' => 'Lista de exames',
                ],
                [
                    'key'           => 'field_veterinarios_exames_cta_link',
                    'name'          => 'exames_cta_link',
                    'label'         => 'Exames — CTA Link',
                    'type'          => 'text',
                    'default_value' => '#',
                ],
                [
                    'key'           => 'field_veterinarios_exames_imagem',
                    'name'          => 'exames_imagem',
                    'label'         => 'Exames — Imagem',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],

                // ── Estrutura ─────────────────────────────────────────────
                [
                    'key'           => 'field_veterinarios_estrutura_eyebrow',
                    'name'          => 'estrutura_eyebrow',
                    'label'         => 'Estrutura — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Estrutura / Tecnologia',
                ],
                [
                    'key'           => 'field_veterinarios_estrutura_titulo',
                    'name'          => 'estrutura_titulo',
                    'label'         => 'Estrutura — Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'default_value' => 'Suporte Técnico ao Veterinário',
                ],
                [
                    'key'           => 'field_veterinarios_estrutura_texto',
                    'name'          => 'estrutura_texto',
                    'label'         => 'Estrutura — Texto',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'default_value' => 'O Genoma oferece suporte direto com corpo veterinário, auxiliando na discussão de casos clínicos, interpretação de resultados e esclarecimento de dúvidas.',
                ],
                [
                    'key'           => 'field_veterinarios_estrutura_destaque',
                    'name'          => 'estrutura_destaque',
                    'label'         => 'Estrutura — Texto em destaque',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'instructions'  => 'Texto exibido alinhado à direita, ao lado do título.',
                    'default_value' => 'Nosso objetivo é ser um apoio real no seu raciocínio clínico.',
                ],
                [
                    'key'           => 'field_veterinarios_estrutura_imagem_1',
                    'name'          => 'estrutura_imagem_1',
                    'label'         => 'Estrutura — Imagem 1',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],
                [
                    'key'           => 'field_veterinarios_estrutura_imagem_2',
                    'name'          => 'estrutura_imagem_2',
                    'label'         => 'Estrutura — Imagem 2',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],
                [
                    'key'          => 'field_veterinarios_estrutura_contatos',
                    'name'         => 'estrutura_contatos',
                    'label'        => 'Estrutura — Card de Contato',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar item de contato',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_veterinarios_estrutura_contato_icone',
                            'name'          => 'icone',
                            'label'         => 'Ícone',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_veterinarios_estrutura_contato_texto',
                            'name'          => 'texto',
                            'label'         => 'Texto',
                            'type'          => 'textarea',
                            'rows'          => 2,
                            'instructions'  => 'Use uma quebra de linha para controlar onde o texto deve quebrar.',
                            'default_value' => 'Segunda a sexta-feira: das 9h às 19h',
                        ],
                    ],
                ],

                // ── Benefícios ────────────────────────────────────────────
                [
                    'key'           => 'field_veterinarios_beneficios_eyebrow',
                    'name'          => 'beneficios_eyebrow',
                    'label'         => 'Benefícios — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Benefícios',
                ],
                [
                    'key'           => 'field_veterinarios_beneficios_titulo',
                    'name'          => 'beneficios_titulo',
                    'label'         => 'Benefícios — Título',
                    'type'          => 'text',
                    'default_value' => 'Benefícios para Veterinários Conveniados',
                ],
                [
                    'key'           => 'field_veterinarios_beneficios_texto',
                    'name'          => 'beneficios_texto',
                    'label'         => 'Benefícios — Texto',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'default_value' => 'Ao se tornar um veterinário conveniado ao Genoma, você tem acesso a vantagens exclusivas:',
                ],
                [
                    'key'          => 'field_veterinarios_beneficios_itens',
                    'name'         => 'beneficios_itens',
                    'label'        => 'Benefícios — Itens',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar benefício',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_veterinarios_beneficios_item_icone',
                            'name'          => 'icone',
                            'label'         => 'Ícone',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_veterinarios_beneficios_item_texto',
                            'name'          => 'texto',
                            'label'         => 'Texto',
                            'type'          => 'text',
                            'default_value' => 'Estrutura própria e moderna',
                        ],
                    ],
                ],

                // ── Rodapé (sobrescreve o CTA do banner só nesta página) ───
                [
                    'key'           => 'field_veterinarios_footer_cta_titulo',
                    'name'          => 'footer_cta_titulo',
                    'label'         => 'Rodapé — CTA Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'instructions'  => 'Sobrescreve o título do banner de CTA do rodapé só nesta página. Deixe em branco para usar o padrão de Opções do Tema.',
                    'default_value' => 'Cuidado começa com diagnóstico preciso.',
                ],
                [
                    'key'           => 'field_veterinarios_footer_cta_primario_texto',
                    'name'          => 'footer_cta_primario_texto',
                    'label'         => 'Rodapé — CTA Primário (Texto)',
                    'type'          => 'text',
                    'instructions'  => 'Sobrescreve o texto do botão principal do banner de CTA do rodapé só nesta página. Deixe em branco para usar o padrão de Opções do Tema.',
                    'default_value' => 'Quero ser parceiro',
                ],
                [
                    'key'           => 'field_veterinarios_footer_cta_primario_link',
                    'name'          => 'footer_cta_primario_link',
                    'label'         => 'Rodapé — CTA Primário (Link)',
                    'type'          => 'text',
                    'default_value' => '#',
                ],
                [
                    'key'           => 'field_veterinarios_footer_cta_mostrar_secundario',
                    'name'          => 'footer_cta_mostrar_secundario',
                    'label'         => 'Rodapé — Mostrar Botão Secundário',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 1,
                ],
                [
                    'key'               => 'field_veterinarios_footer_cta_secundario_texto',
                    'name'              => 'footer_cta_secundario_texto',
                    'label'             => 'Rodapé — CTA Secundário (Texto)',
                    'type'              => 'text',
                    'default_value'     => 'Fale Conosco',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_veterinarios_footer_cta_mostrar_secundario',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_veterinarios_footer_cta_secundario_link',
                    'name'              => 'footer_cta_secundario_link',
                    'label'             => 'Rodapé — CTA Secundário (Link)',
                    'type'              => 'text',
                    'default_value'     => '#',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_veterinarios_footer_cta_mostrar_secundario',
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
