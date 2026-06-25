<?php
declare(strict_types=1);

namespace Core\Admin\FormBuilder;

class FormApi
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        $slug = '(?P<slug>[a-zA-Z0-9-_]+)';

        register_rest_route('framework/v1', "/forms/{$slug}", [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getSchema'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('framework/v1', "/forms/{$slug}/submit", [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handleSubmit'],
            'permission_callback' => [self::class, 'verifyNonce'],
        ]);
    }

    /** Verifica o nonce do WP REST (injetado em window.FW_BOOT.nonce). */
    public static function verifyNonce(): bool
    {
        $nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        return wp_verify_nonce($nonce, 'wp_rest') !== false;
    }

    /** GET /framework/v1/forms/{slug} — retorna o schema do formulário. */
    public static function getSchema(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $form = self::findBySlug($request->get_param('slug'));
        if (!$form) {
            return new \WP_Error('not_found', 'Formulário não encontrado.', ['status' => 404]);
        }

        $rawFields = get_field('form_fields', $form->ID) ?: [];

        $fields = array_values(array_map(static function (array $f): array {
            $options = array_values(array_filter(
                array_map('trim', explode("\n", $f['options'] ?? ''))
            ));

            return [
                'type'        => $f['type'],
                'name'        => $f['name'],
                'label'       => $f['label'],
                'placeholder' => $f['placeholder'] ?? '',
                'required'    => (bool) ($f['required'] ?? false),
                'options'     => $options,
            ];
        }, $rawFields));

        return rest_ensure_response([
            'slug'           => get_field('form_slug', $form->ID),
            'title'          => get_the_title($form->ID),
            'fields'         => $fields,
            'submitLabel'    => get_field('submit_label', $form->ID) ?: 'Enviar',
            'successMessage' => get_field('success_message', $form->ID) ?: 'Mensagem enviada!',
        ]);
    }

    /** POST /framework/v1/forms/{slug}/submit — processa a submissão. */
    public static function handleSubmit(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $form = self::findBySlug($request->get_param('slug'));
        if (!$form) {
            return new \WP_Error('not_found', 'Formulário não encontrado.', ['status' => 404]);
        }

        $body       = (array) ($request->get_json_params() ?? []);
        $formFields = get_field('form_fields', $form->ID) ?: [];

        // Validação server-side dos campos obrigatórios
        $errors = [];
        foreach ($formFields as $f) {
            if (!empty($f['required']) && empty($body[$f['name']])) {
                $errors[$f['name']] = "O campo \"{$f['label']}\" é obrigatório.";
            }
        }

        if (!empty($errors)) {
            return new \WP_Error('validation_error', 'Erros de validação.', [
                'status' => 422,
                'errors' => $errors,
            ]);
        }

        // Sanitiza por tipo de campo
        $fieldTypeMap = array_column($formFields, 'type', 'name');
        $data = [];
        foreach ($body as $key => $value) {
            $type = $fieldTypeMap[$key] ?? 'text';
            $data[$key] = match ($type) {
                'textarea' => sanitize_textarea_field((string) $value),
                'email'    => sanitize_email((string) $value),
                'url'      => esc_url_raw((string) $value),
                'number'   => is_numeric($value) ? (string) $value : '',
                default    => sanitize_text_field((string) $value),
            };
        }

        // Executa as ações configuradas
        $actions = get_field('form_actions', $form->ID) ?: [];
        FormHandler::run($actions, get_field('form_slug', $form->ID) ?? $form->post_name, $data);

        return rest_ensure_response([
            'success' => true,
            'message' => get_field('success_message', $form->ID) ?: 'Mensagem enviada!',
        ]);
    }

    private static function findBySlug(string $slug): ?\WP_Post
    {
        $posts = get_posts([
            'post_type'      => 'fw_form',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [[
                'key'   => 'form_slug',
                'value' => sanitize_key($slug),
            ]],
        ]);

        return $posts[0] ?? null;
    }
}
