<?php
declare(strict_types=1);

namespace Core\Admin\FormBuilder;

class FormHandler
{
    /** Executa todas as ações configuradas no formulário. */
    public static function run(array $actions, string $formSlug, array $data): void
    {
        foreach ($actions as $action) {
            match ($action['type'] ?? '') {
                'store'   => self::store($formSlug, $data),
                'email'   => self::email($action, $data),
                'webhook' => self::webhook($action, $data),
                default   => null,
            };
        }
    }

    private static function store(string $formSlug, array $data): void
    {
        $postId = wp_insert_post([
            'post_type'   => 'fw_submission',
            'post_title'  => $formSlug . ' — ' . current_time('d/m/Y H:i'),
            'post_status' => 'publish',
        ]);

        if (is_wp_error($postId)) return;

        update_post_meta($postId, 'submission_form_slug', sanitize_key($formSlug));
        update_post_meta($postId, 'submission_data', wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        update_post_meta($postId, 'submission_ip', sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    private static function email(array $action, array $data): void
    {
        $to = sanitize_email($action['email_to'] ?? '');
        if (!$to) return;

        $subject = $action['email_subject'] ?? 'Nova mensagem do site';

        // Substitui {{campo}} pelos valores enviados
        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
        }

        $body = '';
        foreach ($data as $key => $value) {
            $body .= ucfirst(str_replace('_', ' ', $key)) . ': ' . $value . "\n";
        }

        wp_mail($to, $subject, $body);
    }

    private static function webhook(array $action, array $data): void
    {
        $url = esc_url_raw($action['webhook_url'] ?? '');
        if (!$url) return;

        wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'    => wp_json_encode($data),
            'timeout' => 10,
            'blocking' => false,
        ]);
    }
}
