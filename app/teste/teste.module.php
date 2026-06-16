<?php
declare(strict_types=1);

namespace App\Teste;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'teste',
    name: 'Teste',
    route: '/teste',
    template: true,
    templateLabel: 'Página · Teste',
)]
final class TesteModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_teste',
            'title'    => 'Teste — Campos',
            'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'fw:teste']]],
            'fields'   => [
                // Texto simples
                [
                    'key'           => 'field_teste_titulo',
                    'label'         => 'Título Principal',
                    'name'          => 'titulo',
                    'type'          => 'text',
                    'placeholder'   => 'Ex: Bem-vindo ao framework',
                    'required'      => 1,
                ],
                // Textarea
                [
                    'key'           => 'field_teste_descricao',
                    'label'         => 'Descrição',
                    'name'          => 'descricao',
                    'type'          => 'textarea',
                    'rows'          => 4,
                    'placeholder'   => 'Uma descrição detalhada...',
                    'required'      => 0,
                ],
                // Número
                [
                    'key'           => 'field_teste_numero',
                    'label'         => 'Número de Destaque',
                    'name'          => 'numero',
                    'type'          => 'number',
                    'placeholder'   => '100',
                    'min'           => 0,
                    'max'           => 9999,
                ],
                // Imagem
                [
                    'key'           => 'field_teste_imagem',
                    'label'         => 'Imagem de Capa',
                    'name'          => 'imagem',
                    'type'          => 'image',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                ],
                // Seletor
                [
                    'key'     => 'field_teste_cor',
                    'label'   => 'Cor do Tema',
                    'name'    => 'cor',
                    'type'    => 'select',
                    'choices' => [
                        'azul'   => 'Azul',
                        'verde'  => 'Verde',
                        'roxo'   => 'Roxo',
                        'laranja'=> 'Laranja',
                    ],
                    'default_value' => 'azul',
                    'return_format' => 'value',
                ],
                // True/False
                [
                    'key'           => 'field_teste_ativo',
                    'label'         => 'Exibir Banner',
                    'name'          => 'exibir_banner',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 1,
                ],
                // Link
                [
                    'key'           => 'field_teste_cta',
                    'label'         => 'Botão CTA',
                    'name'          => 'cta',
                    'type'          => 'link',
                    'return_format' => 'array',
                ],
                // Repeater de cards
                [
                    'key'        => 'field_teste_cards',
                    'label'      => 'Cards',
                    'name'       => 'cards',
                    'type'       => 'repeater',
                    'min'        => 0,
                    'max'        => 6,
                    'layout'     => 'block',
                    'button_label' => 'Adicionar Card',
                    'sub_fields' => [
                        [
                            'key'         => 'field_teste_card_icone',
                            'label'       => 'Ícone (emoji ou texto)',
                            'name'        => 'icone',
                            'type'        => 'text',
                            'placeholder' => '🚀',
                        ],
                        [
                            'key'         => 'field_teste_card_titulo',
                            'label'       => 'Título do Card',
                            'name'        => 'titulo',
                            'type'        => 'text',
                            'placeholder' => 'Módulos',
                        ],
                        [
                            'key'         => 'field_teste_card_texto',
                            'label'       => 'Texto do Card',
                            'name'        => 'texto',
                            'type'        => 'textarea',
                            'rows'        => 2,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
