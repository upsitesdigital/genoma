<?php
declare(strict_types=1);

namespace Core\Admin\FormBuilder;

class FormCpt
{
    public static function register(): void
    {
        add_action('init',     [self::class, 'registerCpts']);
        add_action('acf/init', [self::class, 'registerFields']);
    }

    public static function registerCpts(): void
    {
        register_post_type('fw_form', [
            'labels'        => [
                'name'          => 'Formulários',
                'singular_name' => 'Formulário',
                'add_new_item'  => 'Adicionar Formulário',
                'edit_item'     => 'Editar Formulário',
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'upwork',
            'supports'      => ['title'],
            'show_in_rest'  => false,
            'menu_icon'     => 'dashicons-feedback',
        ]);

        register_post_type('fw_submission', [
            'labels'       => [
                'name'          => 'Submissões',
                'singular_name' => 'Submissão',
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'upwork',
            'supports'     => ['title'],
            'show_in_rest' => false,
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
        ]);
    }

    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) return;

        // Campos de configuração do fw_form
        acf_add_local_field_group([
            'key'      => 'group_fw_form',
            'title'    => 'Configuração do Formulário',
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'fw_form']]],
            'fields'   => [
                [
                    'key'          => 'field_fw_form_slug',
                    'name'         => 'form_slug',
                    'label'        => 'Slug',
                    'type'         => 'text',
                    'required'     => 1,
                    'instructions' => 'Identificador único. Use no componente: <code>&lt;DynamicForm slug="..."&gt;</code>',
                ],
                [
                    'key'          => 'field_fw_form_fields',
                    'name'         => 'form_fields',
                    'label'        => 'Campos',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => '+ Adicionar campo',
                    'sub_fields'   => [
                        [
                            'key'     => 'field_ff_type',
                            'name'    => 'type',
                            'label'   => 'Tipo',
                            'type'    => 'select',
                            'choices' => [
                                'text'     => 'Texto',
                                'email'    => 'E-mail',
                                'phone'    => 'Telefone',
                                'number'   => 'Número',
                                'textarea' => 'Texto longo',
                                'select'   => 'Seleção (dropdown)',
                                'radio'    => 'Radio',
                                'checkbox' => 'Checkbox',
                            ],
                            'required' => 1,
                        ],
                        ['key' => 'field_ff_name',        'name' => 'name',        'label' => 'Nome (slug)',   'type' => 'text', 'required' => 1],
                        ['key' => 'field_ff_label',       'name' => 'label',       'label' => 'Label',        'type' => 'text', 'required' => 1],
                        ['key' => 'field_ff_placeholder', 'name' => 'placeholder', 'label' => 'Placeholder',  'type' => 'text'],
                        ['key' => 'field_ff_required',    'name' => 'required',    'label' => 'Obrigatório',  'type' => 'true_false', 'ui' => 1],
                        [
                            'key'          => 'field_ff_options',
                            'name'         => 'options',
                            'label'        => 'Opções',
                            'type'         => 'textarea',
                            'rows'         => 3,
                            'instructions' => 'Uma opção por linha (para Select, Radio, Checkbox).',
                        ],
                    ],
                ],
                [
                    'key'          => 'field_fw_form_actions',
                    'name'         => 'form_actions',
                    'label'        => 'Ações após envio',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => '+ Adicionar ação',
                    'sub_fields'   => [
                        [
                            'key'     => 'field_fa_type',
                            'name'    => 'type',
                            'label'   => 'Tipo',
                            'type'    => 'select',
                            'choices' => [
                                'store'   => 'Salvar no banco',
                                'email'   => 'Enviar e-mail',
                                'webhook' => 'Webhook (POST JSON)',
                            ],
                            'required' => 1,
                        ],
                        ['key' => 'field_fa_email_to',      'name' => 'email_to',      'label' => 'Enviar para (e-mail)',  'type' => 'text'],
                        ['key' => 'field_fa_email_subject', 'name' => 'email_subject', 'label' => 'Assunto',              'type' => 'text',
                         'instructions' => 'Use {{nome_do_campo}} para substituir valores.'],
                        ['key' => 'field_fa_webhook_url',   'name' => 'webhook_url',   'label' => 'URL do Webhook',       'type' => 'url'],
                    ],
                ],
                [
                    'key'           => 'field_fw_form_submit_label',
                    'name'          => 'submit_label',
                    'label'         => 'Texto do Botão',
                    'type'          => 'text',
                    'default_value' => 'Enviar',
                ],
                [
                    'key'           => 'field_fw_form_success_msg',
                    'name'          => 'success_message',
                    'label'         => 'Mensagem de Sucesso',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'default_value' => 'Obrigado! Recebemos sua mensagem.',
                ],
            ],
        ]);

        // Campos de visualização da fw_submission
        acf_add_local_field_group([
            'key'      => 'group_fw_submission',
            'title'    => 'Dados da Submissão',
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'fw_submission']]],
            'fields'   => [
                ['key' => 'field_fsub_form_slug', 'name' => 'submission_form_slug', 'label' => 'Formulário', 'type' => 'text', 'readonly' => 1],
                ['key' => 'field_fsub_data',      'name' => 'submission_data',      'label' => 'Dados',       'type' => 'textarea', 'readonly' => 1, 'rows' => 8],
                ['key' => 'field_fsub_ip',        'name' => 'submission_ip',        'label' => 'IP',          'type' => 'text', 'readonly' => 1],
            ],
        ]);
    }
}
