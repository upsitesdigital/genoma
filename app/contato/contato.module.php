<?php
declare(strict_types=1);

namespace App\Contato;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'contato',
    name: 'Contato',
    route: '/contato',
    template: true,
    templateLabel: 'Página · Contato',
)]
final class ContatoModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_contato',
            'title'    => 'Contato',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:contato'],
            ]],
            'fields' => [
                // ── Hero ──────────────────────────────────────────────────
                [
                    'key'           => 'field_contato_hero_eyebrow',
                    'instructions'  => 'Texto curto exibido acima do título (ex.: Contato).',
                    'name'          => 'hero_eyebrow',
                    'label'         => 'Hero — Etiqueta',
                    'type'          => 'text',
                    'default_value' => 'Contato',
                ],
                [
                    'key'           => 'field_contato_hero_titulo',
                    'instructions'  => 'Título grande do topo da página. É o título principal da página para o Google.',
                    'name'          => 'hero_titulo',
                    'label'         => 'Hero — Título',
                    'type'          => 'text',
                    'required'      => 1,
                    'default_value' => 'Fale com o Genoma Diagnóstico Veterinário',
                ],
                [
                    'key'           => 'field_contato_hero_descricao',
                    'instructions'  => 'Frase de apoio abaixo do título.',
                    'name'          => 'hero_descricao',
                    'label'         => 'Hero — Descrição',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'default_value' => 'Estamos prontos para atender você e esclarecer dúvidas sobre exames, resultados, convênios e parcerias.',
                ],
                [
                    'key'          => 'field_contato_hero_canais',
                    'instructions' => 'Cada linha é um card de contato (WhatsApp, e-mail...). Fica melhor com 3 cards.',
                    'name'         => 'hero_canais',
                    'label'        => 'Hero — Canais de Contato (cards)',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'min'          => 0,
                    'max'          => 0,
                    'button_label' => 'Adicionar Canal',
                    'sub_fields'   => [
                        [
                            'key'           => 'field_contato_hero_canal_icone',
                            'instructions'  => 'Ícone em SVG ou PNG com fundo transparente.',
                            'name'          => 'icone',
                            'label'         => 'Ícone',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'thumbnail',
                            'mime_types'    => 'svg,png',
                        ],
                        [
                            'key'           => 'field_contato_hero_canal_titulo',
                            'instructions'  => 'Nome do canal, exibido em destaque no card.',
                            'name'          => 'titulo',
                            'label'         => 'Título (ex: Whatsapp clientes)',
                            'type'          => 'text',
                            'required'      => 1,
                            'default_value' => '',
                        ],
                        [
                            'key'           => 'field_contato_hero_canal_valor',
                            'instructions'  => 'Telefone ou e-mail exibido no card, como o visitante deve ler (ex.: (11) 99999-9999).',
                            'name'          => 'valor',
                            'label'         => 'Valor (telefone ou e-mail)',
                            'type'          => 'text',
                            'required'      => 1,
                            'default_value' => '',
                        ],
                        [
                            'key'           => 'field_contato_hero_canal_link',
                            'instructions'  => 'Opcional. Preenchido, o card fica clicável. WhatsApp: https://wa.me/5511999999999 (só números, com 55 e DDD). E-mail: mailto:contato@dominio.com.br',
                            'name'          => 'link',
                            'label'         => 'Link (ex: https://wa.me/... ou mailto:...)',
                            'type'          => 'text',
                            'default_value' => '',
                        ],
                    ],
                ],

                // ── Lista de contatos ─────────────────────────────────────
                [
                    'key'          => 'field_contato_lista_contatos',
                    'name'         => 'lista_contatos',
                    'label'        => 'Lista de Contatos — Cards',
                    'type'         => 'flexible_content',
                    'button_label' => 'Adicionar Card',
                    'instructions' => 'Cards exibidos abaixo do hero. Sem nenhum card cadastrado, a seção não aparece.',
                    'layouts'      => [
                        'layout_contato_lista_card_info' => [
                            'key'        => 'layout_contato_lista_card_info',
                            'name'       => 'card_info',
                            'label'      => 'Card — Título + Itens (ícone + texto)',
                            'display'    => 'block',
                            'sub_fields' => [
                                [
                                    'key'           => 'field_contato_lista_card_info_titulo',
                                    'instructions'  => 'Título do card (ex.: Atendimento presencial).',
                                    'name'          => 'titulo',
                                    'label'         => 'Título',
                                    'type'          => 'text',
                                    'required'      => 1,
                                    'default_value' => '',
                                ],
                                [
                                    'key'           => 'field_contato_lista_card_info_descricao',
                                    'instructions'  => 'Texto curto abaixo do título.',
                                    'name'          => 'descricao',
                                    'label'         => 'Descrição',
                                    'type'          => 'textarea',
                                    'rows'          => 2,
                                    'default_value' => '',
                                ],
                                [
                                    'key'          => 'field_contato_lista_card_info_itens',
                                    'instructions' => 'Cada linha é um item com ícone e texto (ex.: endereço, horário, telefone).',
                                    'name'         => 'itens',
                                    'label'        => 'Itens (ícone + texto)',
                                    'type'         => 'repeater',
                                    'layout'       => 'table',
                                    'min'          => 0,
                                    'max'          => 0,
                                    'button_label' => 'Adicionar Item',
                                    'sub_fields'   => [
                                        [
                                            'key'           => 'field_contato_lista_item_icone',
                                            'instructions'  => 'Ícone em SVG ou PNG com fundo transparente.',
                                            'name'          => 'icone',
                                            'label'         => 'Ícone',
                                            'type'          => 'image',
                                            'return_format' => 'array',
                                            'preview_size'  => 'thumbnail',
                                            'mime_types'    => 'svg,png',
                                        ],
                                        [
                                            'key'           => 'field_contato_lista_item_texto',
                                            'name'          => 'texto',
                                            'label'         => 'Texto',
                                            'type'          => 'textarea',
                                            'rows'          => 2,
                                            'instructions'  => 'Use uma quebra de linha para controlar onde o texto deve quebrar.',
                                            'default_value' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'layout_contato_lista_card_texto' => [
                            'key'        => 'layout_contato_lista_card_texto',
                            'name'       => 'card_texto',
                            'label'      => 'Card — Título + Texto (com botão opcional)',
                            'display'    => 'block',
                            'sub_fields' => [
                                [
                                    'key'           => 'field_contato_lista_card_texto_titulo',
                                    'instructions'  => 'Título do card.',
                                    'name'          => 'titulo',
                                    'label'         => 'Título',
                                    'type'          => 'text',
                                    'required'      => 1,
                                    'default_value' => '',
                                ],
                                [
                                    'key'           => 'field_contato_lista_card_texto_texto',
                                    'instructions'  => 'Use uma linha em branco para separar os parágrafos.',
                                    'name'          => 'texto',
                                    'label'         => 'Texto',
                                    'type'          => 'textarea',
                                    'rows'          => 3,
                                    'default_value' => '',
                                ],
                                [
                                    'key'           => 'field_contato_lista_card_texto_mostrar_botao',
                                    'instructions'  => 'Ligado: mostra um botão roxo abaixo do texto.',
                                    'name'          => 'mostrar_botao',
                                    'label'         => 'Mostrar Botão',
                                    'type'          => 'true_false',
                                    'ui'            => 1,
                                    'default_value' => 0,
                                ],
                                [
                                    'key'               => 'field_contato_lista_card_texto_botao_texto',
                                    'instructions'      => 'Texto do botão.',
                                    'name'              => 'botao_texto',
                                    'label'             => 'Botão — Texto',
                                    'type'              => 'text',
                                    'default_value'     => '',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field'    => 'field_contato_lista_card_texto_mostrar_botao',
                                                'operator' => '==',
                                                'value'    => '1',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'key'               => 'field_contato_lista_card_texto_botao_link',
                                    'instructions'      => 'Endereço completo do botão, começando com https:// (ou o endereço de uma página do site).',
                                    'name'              => 'botao_link',
                                    'label'             => 'Botão — Link',
                                    'type'              => 'text',
                                    'default_value'     => '#',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field'    => 'field_contato_lista_card_texto_mostrar_botao',
                                                'operator' => '==',
                                                'value'    => '1',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
