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
                    'instructions' => 'Cada linha é um slide. Com 2 ou mais slides aparecem as setas e as bolinhas de navegação. Sem nenhum slide, o topo da página não aparece.',
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
                            'label'         => 'Etiqueta (H1)',
                            'type'          => 'text',
                            'instructions'  => 'Renderizado como H1 da página apenas no primeiro slide (o Título abaixo é H2).',
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
                            'instructions'  => 'Foto menor, à esquerda, abaixo do texto. Vertical.',
                            'name'          => 'imagem_1',
                            'label'         => 'Imagem 1 (menor)',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                        ],
                        [
                            'key'           => 'field_o-genoma_hero_slide_imagem_2',
                            'instructions'  => 'Foto maior, à direita, abaixo do texto. Horizontal.',
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
                    'instructions'  => 'Ligado: os slides passam sozinhos (pausam quando o mouse está sobre eles).',
                    'name'          => 'hero_autoplay',
                    'label'         => 'Hero — Autoplay do Carrossel',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 1,
                ],
                [
                    'key'               => 'field_o-genoma_hero_intervalo',
                    'instructions'      => 'Tempo de cada slide em milissegundos: 6000 = 6 segundos. Mínimo 2000.',
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

                // ── Sobre nós ─────────────────────────────────────────────
                [
                    'key'           => 'field_o-genoma_sobre_eyebrow',
                    'instructions'  => 'Texto curto exibido acima do título da seção (ex.: SERVIÇOS).',
                    'name'          => 'sobre_eyebrow',
                    'label'         => 'Sobre nós — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Sobre nós',
                ],
                [
                    'key'           => 'field_o-genoma_sobre_titulo',
                    'instructions'  => 'Título principal da seção. Aperte Enter para escolher onde o título quebra.',
                    'name'          => 'sobre_titulo',
                    'label'         => 'Sobre nós — Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'default_value' => 'Uma década de excelência em diagnóstico veterinário.',
                ],
                [
                    'key'           => 'field_o-genoma_sobre_texto',
                    'name'          => 'sobre_texto',
                    'label'         => 'Sobre nós — Texto',
                    'type'          => 'textarea',
                    'rows'          => 6,
                    'instructions'  => 'Use uma linha em branco para separar os parágrafos.',
                    'default_value' => "Em 2026, completamos 10 anos de atuação, marcando uma trajetória construída com base na ciência, na ética e no compromisso com a medicina veterinária. Ao longo dessa jornada, já realizamos mais de 150 mil atendimentos, contribuindo diariamente para diagnósticos seguros e para o cuidado com a saúde animal.\n\nAtuamos com foco em diagnóstico laboratorial de alta qualidade, utilizando metodologias modernas, equipamentos de ponta e rigorosos padrões de controle, sempre alinhados às boas práticas laboratoriais e às demandas da rotina clínica veterinária.",
                ],
                [
                    'key'           => 'field_o-genoma_sobre_destaque',
                    'name'          => 'sobre_destaque',
                    'label'         => 'Sobre nós — Texto em destaque',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'instructions'  => 'Texto exibido entre as duas linhas divisórias, ao lado do título. Use uma quebra de linha para controlar onde o texto deve quebrar.',
                    'default_value' => "Ciência, precisão e confiança em mais de\n150 mil atendimentos",
                ],

                // ── Compromisso ───────────────────────────────────────────
                [
                    'key'           => 'field_o-genoma_compromisso_eyebrow',
                    'instructions'  => 'Texto curto exibido acima do título da seção (ex.: SERVIÇOS).',
                    'name'          => 'compromisso_eyebrow',
                    'label'         => 'Compromisso — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Compromisso',
                ],
                [
                    'key'           => 'field_o-genoma_compromisso_titulo',
                    'name'          => 'compromisso_titulo',
                    'label'         => 'Compromisso — Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'instructions'  => 'Use uma quebra de linha para controlar onde o título deve quebrar.',
                    'default_value' => 'Nosso compromisso vai além da liberação de resultados.',
                ],
                [
                    'key'           => 'field_o-genoma_compromisso_texto',
                    'instructions'  => 'Use uma linha em branco para separar os parágrafos.',
                    'name'          => 'compromisso_texto',
                    'label'         => 'Compromisso — Texto',
                    'type'          => 'textarea',
                    'rows'          => 4,
                    'default_value' => 'Trabalhamos para oferecer informação diagnóstica confiável, suporte técnico qualificado e um atendimento próximo, transparente e eficiente, fortalecendo parcerias sólidas com clínicas e hospitais veterinários.',
                ],
                [
                    'key'           => 'field_o-genoma_compromisso_imagem',
                    'instructions'  => 'Foto de fundo do banner, horizontal, com pelo menos 1920 px de largura. O texto fica sobre ela.',
                    'name'          => 'compromisso_imagem',
                    'label'         => 'Compromisso — Imagem de fundo',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],

                // ── Diaginostico (posição 4, última seção) ────────────────
                [
                    'key'           => 'field_o-genoma_diagnostico_titulo',
                    'name'          => 'diagnostico_titulo',
                    'label'         => 'Diaginostico — Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'instructions'  => 'Use uma quebra de linha para controlar onde o título deve quebrar.',
                    'default_value' => 'Diagnóstico que gera confiança',
                ],
                [
                    'key'           => 'field_o-genoma_diagnostico_texto',
                    'name'          => 'diagnostico_texto',
                    'label'         => 'Diaginostico — Texto',
                    'type'          => 'textarea',
                    'rows'          => 6,
                    'instructions'  => 'Use uma linha em branco para separar os parágrafos.',
                    'default_value' => "Acreditamos que um diagnóstico bem-feito é essencial para a condução adequada dos casos clínicos, o bem-estar animal e a confiança entre profissionais.\n\nPor isso, investimos continuamente em tecnologia, capacitação da equipe e melhoria constante dos processos, garantindo resultados consistentes e dentro dos prazos esperados.",
                ],
                [
                    'key'           => 'field_o-genoma_diagnostico_destaque',
                    'name'          => 'diagnostico_destaque',
                    'label'         => 'Diaginostico — Texto em destaque',
                    'type'          => 'textarea',
                    'rows'          => 3,
                    'instructions'  => 'Texto exibido entre as duas linhas divisórias, ao lado do título.',
                    'default_value' => 'O Genoma Diagnóstico Veterinário é movido pela experiência, pela inovação e pela responsabilidade com cada resultado entregue.',
                ],

                // ── Rodapé (sobrescreve o CTA do banner só nesta página) ───
                [
                    'key'           => 'field_o-genoma_footer_cta_titulo',
                    'name'          => 'footer_cta_titulo',
                    'label'         => 'Rodapé — CTA Título',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'instructions'  => 'Sobrescreve o título do banner de CTA do rodapé só nesta página. Deixe em branco para usar o padrão de Opções do Tema.',
                    'default_value' => "Entre em contato com\na nossa equipe.",
                ],
                [
                    'key'           => 'field_o-genoma_footer_cta_primario_texto',
                    'name'          => 'footer_cta_primario_texto',
                    'label'         => 'Rodapé — CTA Primário (Texto)',
                    'type'          => 'text',
                    'instructions'  => 'Sobrescreve o texto do botão principal do banner de CTA do rodapé só nesta página. Deixe em branco para usar o padrão de Opções do Tema.',
                    'default_value' => 'Fale conosco pelo WhatsApp',
                ],
                [
                    'key'           => 'field_o-genoma_footer_cta_primario_link',
                    'instructions'  => 'Endereço do botão principal do banner. Em branco, usa o das Opções do Tema.',
                    'name'          => 'footer_cta_primario_link',
                    'label'         => 'Rodapé — CTA Primário (Link)',
                    'type'          => 'text',
                    'default_value' => '#',
                ],
                [
                    'key'           => 'field_o-genoma_footer_cta_mostrar_secundario',
                    'instructions'  => 'Desligado: o banner mostra só o botão principal (roxo).',
                    'name'          => 'footer_cta_mostrar_secundario',
                    'label'         => 'Rodapé — Mostrar Botão Secundário',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 0,
                ],
                [
                    'key'               => 'field_o-genoma_footer_cta_secundario_texto',
                    'instructions'      => 'Texto do segundo botão do banner. Em branco, usa o das Opções do Tema.',
                    'name'              => 'footer_cta_secundario_texto',
                    'label'             => 'Rodapé — CTA Secundário (Texto)',
                    'type'              => 'text',
                    'default_value'     => 'Fale Conosco',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_o-genoma_footer_cta_mostrar_secundario',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_o-genoma_footer_cta_secundario_link',
                    'instructions'      => 'Endereço do segundo botão do banner. Em branco, usa o das Opções do Tema.',
                    'name'              => 'footer_cta_secundario_link',
                    'label'             => 'Rodapé — CTA Secundário (Link)',
                    'type'              => 'text',
                    'default_value'     => '#',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_o-genoma_footer_cta_mostrar_secundario',
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
