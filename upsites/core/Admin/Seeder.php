<?php
declare(strict_types=1);

namespace Core\Admin;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * O conteúdo do Figma vai para o painel: nas páginas de modelo, todo campo que
 * ainda não foi salvo recebe o seu padrão (default_value, imagem do Fields::image/icon,
 * default_rows) como valor de verdade no banco. Assim o editor edita o que o site
 * mostra, e o que ele apagar continua apagado (campo salvo nunca é sobrescrito).
 *
 * Roda ao abrir a página no editor e depois de salvá-la. Para as páginas que já
 * existem: `npm run seed` (upsites/core/cli/seed.php).
 */
final class Seeder
{
    private const SKIP = ['tab', 'message', 'accordion', 'clone'];

    public static function register(): void
    {
        add_action('load-post.php', [self::class, 'onEdit']);
        add_action('acf/save_post', [self::class, 'onSave'], 20);
    }

    /** O load-post.php roda antes de o WP checar a permissão de edição — checa aqui antes de gravar. */
    public static function onEdit(): void
    {
        $id = absint(wp_unslash($_GET['post'] ?? 0)); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- só o ID da tela de edição (o WP já checou o acesso)
        if ($id && self::fromAdmin() && current_user_can('edit_post', $id) && get_post_type($id) === 'page' && ModelScreen::isModel($id)) self::seed($id);
    }

    /**
     * Gravação ao ABRIR uma tela (GET, sem nonce): só se a navegação veio do próprio site — link
     * de outro site (CSRF) não grava. Sec-Fetch-Site é posto pelo navegador e não pode ser forjado
     * por página: 'same-origin' (clique no painel) ou 'none' (URL digitada/favorito) gravam.
     * Navegador sem ele: só com Referer do host do admin (o de verdade — não o _wp_http_referer
     * da URL, que o wp_get_raw_referer aceitaria); sem nenhum dos dois, não grava.
     */
    public static function fromAdmin(): bool
    {
        if (isset($_SERVER['HTTP_SEC_FETCH_SITE'])) {
            return in_array(sanitize_key(wp_unslash($_SERVER['HTTP_SEC_FETCH_SITE'])), ['same-origin', 'none'], true);
        }
        $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        return $referer !== '' && wp_parse_url($referer, PHP_URL_HOST) === wp_parse_url(admin_url(), PHP_URL_HOST);
    }

    public static function onSave(mixed $postId): void
    {
        if (!is_numeric($postId) || !current_user_can('edit_post', (int) $postId)) return;
        if (get_post_type((int) $postId) === 'page' && ModelScreen::isModel((int) $postId)) self::seed((int) $postId);
    }

    /**
     * Grava o padrão nos campos nunca salvos do post (com $empty, também nos salvos
     * vazios — só para migrar projetos antigos). Devolve [name => descrição] do que
     * gravou (ou gravaria, com $dry).
     *
     * @return array<string, string>
     */
    public static function seed(int $postId, bool $empty = false, bool $dry = false): array
    {
        if (!function_exists('acf_get_field_groups')) return [];

        $done = [];
        foreach (self::fields($postId) as $field) {
            $name   = $field['name'];
            $exists = metadata_exists('post', $postId, $name);
            if ($exists && !($empty && self::isEmpty(get_post_meta($postId, $name, true)))) continue;

            $value = self::defaultFor($postId, $field, $exists);
            if (self::isEmpty($value)) continue;

            $done[$name] = is_array($value) ? count($value) . ' item(ns)' : (is_numeric($value) ? "anexo #{$value}" : mb_substr((string) $value, 0, 60));
            if (!$dry) acf_update_value($value, $postId, $field);
        }
        if ($done && !$dry && function_exists('acf_get_store')) acf_get_store('values')->reset();

        return $done;
    }

    /**
     * Campos de primeiro nível dos grupos do post. Clone seamless (componente
     * compartilhado) chega expandido com key prefixada — usa o campo original
     * (`__key`), que é onde ficam os filtros de padrão e a referência gravada.
     * Padrões que dependem da página (acf/load_field) → cache de campos limpo por post.
     */
    private static function fields(int $postId): array
    {
        acf_get_store('fields')->reset();

        $fields = [];
        foreach (acf_get_field_groups(['post_id' => $postId]) as $group) {
            foreach (acf_get_fields($group['key']) ?: [] as $field) {
                if (!empty($field['__key'])) $field = acf_get_field($field['__key']) ?: $field;
                if (in_array($field['type'], self::SKIP, true) || ($field['name'] ?? '') === '') continue;
                $fields[$field['key']] = $field;
            }
        }
        return array_values($fields);
    }

    /** O que o painel mostraria se o campo nunca tivesse sido salvo (default_value, filtros de default_rows…). */
    private static function defaultFor(int $postId, array $field, bool $exists): mixed
    {
        if (!$exists) {
            acf_get_store('values')->reset();
            return acf_get_value($postId, $field);
        }
        // salvo vazio (modo $empty): esconde o meta só para ler o padrão
        $raw = get_post_meta($postId, $field['name'], true);
        $ref = get_post_meta($postId, '_' . $field['name'], true);
        delete_post_meta($postId, $field['name']);
        delete_post_meta($postId, '_' . $field['name']);
        acf_get_store('values')->reset();
        $value = acf_get_value($postId, $field);
        update_post_meta($postId, $field['name'], $raw);
        if ($ref !== '') update_post_meta($postId, '_' . $field['name'], $ref);
        acf_get_store('values')->reset();

        return $value;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === false || $value === '' || $value === [] || $value === '0' || $value === 0;
    }
}
