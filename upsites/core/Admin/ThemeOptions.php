<?php
declare(strict_types=1);

namespace Core\Admin;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Configuração do Tema: página de opções do ACF com as configurações globais do
 * site (rodapé, idiomas, redes sociais…). Os campos ficam em
 * upsites/customizer-register/inc/acf_fields.php (Fields::option()), no mesmo padrão
 * das páginas: grupo com abas à esquerda, conteúdo do Figma já no painel e
 * "apagou, some". Nome do site e logo continuam sendo os do WordPress (Personalizar).
 */
final class ThemeOptions
{
    public const SLUG = 'upwork';

    /** Option da versão antiga (Settings API) — migrada uma vez para os campos do ACF. */
    public const LEGACY = 'upwork_theme_options';

    /**
     * Option antiga → name do campo novo. Projetos com mais campos acrescentam aqui.
     * Genoma (tema React em produção): mesmos names; os pares *_id/*_url viram o campo de
     * imagem. primary_color não migra (o tema React não a usava no site).
     */
    private const LEGACY_MAP = [
        'site_name'                  => 'site_name',
        'logo_id'                    => 'logo',
        'cta_primary_label'          => 'cta_primary_label',
        'cta_primary_url'            => 'cta_primary_url',
        'cta_secondary_label'        => 'cta_secondary_label',
        'cta_secondary_url'          => 'cta_secondary_url',
        'footer_logo_id'             => 'footer_logo',
        'footer_text'                => 'footer_text',
        'footer_privacy_label'       => 'footer_privacy_label',
        'footer_privacy_url'         => 'footer_privacy_url',
        'footer_credits_text'        => 'footer_credits_text',
        'footer_tagline'             => 'footer_tagline',
        'footer_email'               => 'footer_email',
        'footer_phone'               => 'footer_phone',
        'footer_rights_text'         => 'footer_rights_text',
        'footer_cta_overline'        => 'footer_cta_overline',
        'footer_cta_title'           => 'footer_cta_title',
        'footer_cta_image_id'        => 'footer_cta_image',
        'footer_cta_primary_label'   => 'footer_cta_primary_label',
        'footer_cta_primary_url'     => 'footer_cta_primary_url',
        'footer_cta_secondary_label' => 'footer_cta_secondary_label',
        'footer_cta_secondary_url'   => 'footer_cta_secondary_url',
    ];

    public static function register(): void
    {
        add_action('acf/init', [self::class, 'addPage'], 5);
        add_action('load-toplevel_page_' . self::SLUG, [self::class, 'seed']);
        add_action('admin_init', [self::class, 'migrateLegacy']);
    }

    public static function addPage(): void
    {
        if (!function_exists('acf_add_options_page')) return;

        acf_add_options_page([
            'page_title'      => __('Opções do Tema', 'upsites'),
            'menu_title'      => __('Configuração do Tema', 'upsites'),
            'menu_slug'       => self::SLUG,
            'capability'      => 'manage_options',
            'icon_url'        => 'dashicons-superhero',
            'position'        => 3,
            'redirect'        => false,
            'update_button'   => __('Salvar opções', 'upsites'),
            'updated_message' => __('Opções salvas.', 'upsites'),
        ]);
    }

    /** O campo já foi salvo nas opções? (nunca salvo → padrão do Figma; salvo vazio → some) */
    public static function saved(string $name): bool
    {
        return get_option("options_{$name}", null) !== null;
    }

    /** Valor cru do campo (repeater, imagem, link…) — null se o ACF não estiver ativo. */
    public static function value(string $name): mixed
    {
        return function_exists('get_field') ? get_field($name, 'option') : null;
    }

    /** Texto: nunca salvo → $default; apagado pelo editor → null; preenchido → o do editor. */
    public static function text(string $name, ?string $default = null): ?string
    {
        if (!self::saved($name)) return $default;

        $value = self::value($name) ?? get_option("options_{$name}");
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /** Linhas de um repeater: nunca salvo → null (use o padrão); apagou tudo → [] (a lista some). */
    public static function rows(string $name): ?array
    {
        if (!self::saved($name)) return null;

        $rows = self::value($name);
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    /**
     * Imagem do ACF (array, ex.: subcampo Fields::icon() de uma linha) → IconData
     * { id, src, alt, width, height, sizes, svg } — SVG vai inline (herda a cor do texto); o mesmo
     * do Controller::iconFrom(). Sem imagem → null.
     */
    public static function icon(mixed $image): ?array
    {
        return \Core\Support\Media::iconData($image);
    }

    /** Ao abrir a página no admin: o conteúdo padrão dos campos nunca salvos vai para o painel. */
    public static function seed(): void
    {
        if (!function_exists('acf_get_fields') || !current_user_can('manage_options') || !Seeder::fromAdmin()) return;

        foreach (acf_get_fields('group_opcoes') ?: [] as $field) {
            if (in_array($field['type'], ['tab', 'message', 'accordion'], true) || ($field['name'] ?? '') === '') continue;
            if (self::saved($field['name'])) continue;

            acf_get_store('values')->reset();
            $value = acf_get_value('options', $field);
            if ($value !== null && $value !== '' && $value !== [] && $value !== false) acf_update_value($value, 'options', $field);
        }
    }

    /** Uma vez, por um administrador no painel: copia os valores da página antiga (Settings API) para os campos do ACF. */
    public static function migrateLegacy(): void
    {
        // admin_init também roda no admin-ajax.php, até para visitante
        if (wp_doing_ajax() || !current_user_can('manage_options')) return;

        $old = get_option(self::LEGACY);
        if (!is_array($old) || get_option(self::LEGACY . '_migrado') || !function_exists('acf_get_field')) return;

        foreach (self::LEGACY_MAP as $from => $to) {
            $value = $old[$from] ?? null;
            // imagem: a versão antiga guardava *_id e *_url — sem id, procura o anexo pela URL
            if (str_ends_with($from, '_id') && !$value) $value = attachment_url_to_postid((string) ($old[substr($from, 0, -3) . '_url'] ?? '')) ?: null;
            // vazio não migra: na versão antiga, opção vazia mostrava o padrão — nunca salvo aqui faz o mesmo
            if ($value === null || $value === '' || $value === 0 || self::saved($to)) continue;
            $field = self::fieldByName($to);
            if ($field) acf_update_value($value, 'options', $field);
        }
        update_option(self::LEGACY . '_migrado', 1, false);
    }

    private static function fieldByName(string $name): ?array
    {
        foreach (acf_get_fields('group_opcoes') ?: [] as $field) {
            if (($field['name'] ?? '') === $name) return $field;
        }
        return null;
    }
}
