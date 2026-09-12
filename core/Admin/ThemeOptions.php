<?php
declare(strict_types=1);

namespace Core\Admin;

class ThemeOptions
{
    const OPTION_KEY = 'upwork_theme_options';

    public static function register(): void
    {
        add_action('admin_menu',  [self::class, 'addMenu']);
        add_action('admin_init',  [self::class, 'registerSettings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueMedia']);
    }

    public static function addMenu(): void
    {
        add_submenu_page(
            parent_slug: 'upwork',
            page_title:  'Opções do Tema',
            menu_title:  'Opções do Tema',
            capability:  'manage_options',
            menu_slug:   'upwork-theme-options',
            callback:    [self::class, 'render'],
        );
    }

    public static function registerSettings(): void
    {
        register_setting('upwork_theme_options_group', self::OPTION_KEY, [
            'sanitize_callback' => [self::class, 'sanitize'],
        ]);
    }

    public static function enqueueMedia(string $hook): void
    {
        if ($hook !== 'upwork_page_upwork-theme-options') return;
        wp_enqueue_media();
    }

    public static function sanitize(mixed $input): array
    {
        if (!is_array($input)) return [];

        return [
            'site_name'           => sanitize_text_field($input['site_name'] ?? ''),
            'logo_url'            => esc_url_raw($input['logo_url'] ?? ''),
            'logo_id'             => absint($input['logo_id'] ?? 0),
            'primary_color'       => sanitize_hex_color($input['primary_color'] ?? ''),
            'footer_text'         => wp_kses_post($input['footer_text'] ?? ''),
            'cta_primary_label'   => sanitize_text_field($input['cta_primary_label'] ?? ''),
            'cta_primary_url'     => esc_url_raw($input['cta_primary_url'] ?? ''),
            'cta_secondary_label' => sanitize_text_field($input['cta_secondary_label'] ?? ''),
            'cta_secondary_url'   => esc_url_raw($input['cta_secondary_url'] ?? ''),

            'footer_cta_overline'        => sanitize_text_field($input['footer_cta_overline'] ?? ''),
            'footer_cta_title'           => sanitize_text_field($input['footer_cta_title'] ?? ''),
            'footer_cta_image_url'       => esc_url_raw($input['footer_cta_image_url'] ?? ''),
            'footer_cta_image_id'        => absint($input['footer_cta_image_id'] ?? 0),
            'footer_cta_primary_label'   => sanitize_text_field($input['footer_cta_primary_label'] ?? ''),
            'footer_cta_primary_url'     => esc_url_raw($input['footer_cta_primary_url'] ?? ''),
            'footer_cta_secondary_label' => sanitize_text_field($input['footer_cta_secondary_label'] ?? ''),
            'footer_cta_secondary_url'   => esc_url_raw($input['footer_cta_secondary_url'] ?? ''),
            'footer_privacy_label'       => sanitize_text_field($input['footer_privacy_label'] ?? ''),
            'footer_privacy_url'         => esc_url_raw($input['footer_privacy_url'] ?? ''),
            'footer_credits_text'        => sanitize_text_field($input['footer_credits_text'] ?? ''),
        ];
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) return;

        $opts = get_option(self::OPTION_KEY, []);
        $siteName    = $opts['site_name']     ?? '';
        $logoUrl     = $opts['logo_url']      ?? '';
        $logoId      = $opts['logo_id']       ?? 0;
        $primaryColor = $opts['primary_color'] ?? '#000000';
        $footerText  = $opts['footer_text']   ?? '';
        $ctaPrimaryLabel   = $opts['cta_primary_label']   ?? 'Resultados';
        $ctaPrimaryUrl     = $opts['cta_primary_url']     ?? '';
        $ctaSecondaryLabel = $opts['cta_secondary_label'] ?? 'Contato';
        $ctaSecondaryUrl   = $opts['cta_secondary_url']   ?? '';

        $footerCtaOverline      = $opts['footer_cta_overline']        ?? 'Fale conosco';
        $footerCtaTitle         = $opts['footer_cta_title']           ?? 'Cuidado começa com diagnóstico preciso.';
        $footerCtaImageUrl      = $opts['footer_cta_image_url']       ?? '';
        $footerCtaImageId       = $opts['footer_cta_image_id']        ?? 0;
        $footerCtaPrimaryLabel  = $opts['footer_cta_primary_label']   ?? 'Agendar Exame';
        $footerCtaPrimaryUrl    = $opts['footer_cta_primary_url']     ?? '';
        $footerCtaSecondaryLabel = $opts['footer_cta_secondary_label'] ?? 'Fale Conosco';
        $footerCtaSecondaryUrl   = $opts['footer_cta_secondary_url']   ?? '';
        $footerPrivacyLabel     = $opts['footer_privacy_label']       ?? 'Política de privacidade';
        $footerPrivacyUrl       = $opts['footer_privacy_url']         ?? '';
        $footerCreditsText      = $opts['footer_credits_text']        ?? 'Desenvolvido por Upsites';
        ?>
        <div class="wrap">
            <h1>Opções do Tema</h1>
            <form method="post" action="options.php">
                <?php settings_fields('upwork_theme_options_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="site_name">Nome do Site</label></th>
                        <td>
                            <input
                                type="text"
                                id="site_name"
                                name="<?= self::OPTION_KEY ?>[site_name]"
                                value="<?= esc_attr($siteName) ?>"
                                class="regular-text"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Logo</label></th>
                        <td>
                            <div id="logo-preview" style="margin-bottom:8px">
                                <?php if ($logoUrl): ?>
                                    <img src="<?= esc_url($logoUrl) ?>" style="max-height:80px;display:block">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" id="logo_url" name="<?= self::OPTION_KEY ?>[logo_url]" value="<?= esc_attr($logoUrl) ?>">
                            <input type="hidden" id="logo_id"  name="<?= self::OPTION_KEY ?>[logo_id]"  value="<?= esc_attr((string)$logoId) ?>">
                            <button type="button" class="button" id="btn-logo-select">Selecionar imagem</button>
                            <?php if ($logoUrl): ?>
                                <button type="button" class="button" id="btn-logo-remove">Remover</button>
                            <?php endif; ?>
                            <script>
                            (function(){
                                var frame;
                                document.getElementById('btn-logo-select').addEventListener('click', function(){
                                    if (frame) { frame.open(); return; }
                                    frame = wp.media({ title: 'Selecionar Logo', button: { text: 'Usar como logo' }, multiple: false });
                                    frame.on('select', function(){
                                        var att = frame.state().get('selection').first().toJSON();
                                        document.getElementById('logo_url').value = att.url;
                                        document.getElementById('logo_id').value  = att.id;
                                        var preview = document.getElementById('logo-preview');
                                        preview.innerHTML = '<img src="' + att.url + '" style="max-height:80px;display:block">';
                                    });
                                    frame.open();
                                });
                                var btnRemove = document.getElementById('btn-logo-remove');
                                if (btnRemove) btnRemove.addEventListener('click', function(){
                                    document.getElementById('logo_url').value = '';
                                    document.getElementById('logo_id').value  = '0';
                                    document.getElementById('logo-preview').innerHTML = '';
                                });
                            })();
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="primary_color">Cor Primária</label></th>
                        <td>
                            <input
                                type="color"
                                id="primary_color"
                                name="<?= self::OPTION_KEY ?>[primary_color]"
                                value="<?= esc_attr($primaryColor) ?>"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_text">Texto do Rodapé</label></th>
                        <td>
                            <textarea
                                id="footer_text"
                                name="<?= self::OPTION_KEY ?>[footer_text]"
                                class="large-text"
                                rows="3"
                            ><?= esc_textarea($footerText) ?></textarea>
                            <p class="description">Aceita HTML básico. Ex: &copy; 2025 Empresa. Todos os direitos reservados.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cta_primary_label">Botão do Header — Primário</label></th>
                        <td>
                            <input
                                type="text"
                                id="cta_primary_label"
                                name="<?= self::OPTION_KEY ?>[cta_primary_label]"
                                value="<?= esc_attr($ctaPrimaryLabel) ?>"
                                class="regular-text"
                                placeholder="Resultados"
                            >
                            <input
                                type="text"
                                id="cta_primary_url"
                                name="<?= self::OPTION_KEY ?>[cta_primary_url]"
                                value="<?= esc_attr($ctaPrimaryUrl) ?>"
                                class="regular-text"
                                placeholder="https://... ou /resultados"
                            >
                            <p class="description">Texto e link do botão claro (pill branco) no header. Ex: "Resultados".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cta_secondary_label">Botão do Header — Secundário</label></th>
                        <td>
                            <input
                                type="text"
                                id="cta_secondary_label"
                                name="<?= self::OPTION_KEY ?>[cta_secondary_label]"
                                value="<?= esc_attr($ctaSecondaryLabel) ?>"
                                class="regular-text"
                                placeholder="Contato"
                            >
                            <input
                                type="text"
                                id="cta_secondary_url"
                                name="<?= self::OPTION_KEY ?>[cta_secondary_url]"
                                value="<?= esc_attr($ctaSecondaryUrl) ?>"
                                class="regular-text"
                                placeholder="https://... ou /contato"
                            >
                            <p class="description">Texto e link do botão contornado (pill roxo) no header. Ex: "Contato".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_cta_overline">Rodapé — Banner CTA (texto)</label></th>
                        <td>
                            <input
                                type="text"
                                id="footer_cta_overline"
                                name="<?= self::OPTION_KEY ?>[footer_cta_overline]"
                                value="<?= esc_attr($footerCtaOverline) ?>"
                                class="regular-text"
                                placeholder="Fale conosco"
                            >
                            <p class="description">Texto pequeno acima do título do banner de CTA do rodapé.</p>
                            <textarea
                                id="footer_cta_title"
                                name="<?= self::OPTION_KEY ?>[footer_cta_title]"
                                class="large-text"
                                rows="2"
                            ><?= esc_textarea($footerCtaTitle) ?></textarea>
                            <p class="description">Título (H2) do banner de CTA do rodapé.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Rodapé — Imagem do Banner CTA</label></th>
                        <td>
                            <div id="footer-cta-image-preview" style="margin-bottom:8px">
                                <?php if ($footerCtaImageUrl): ?>
                                    <img src="<?= esc_url($footerCtaImageUrl) ?>" style="max-height:120px;display:block">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" id="footer_cta_image_url" name="<?= self::OPTION_KEY ?>[footer_cta_image_url]" value="<?= esc_attr($footerCtaImageUrl) ?>">
                            <input type="hidden" id="footer_cta_image_id"  name="<?= self::OPTION_KEY ?>[footer_cta_image_id]"  value="<?= esc_attr((string)$footerCtaImageId) ?>">
                            <button type="button" class="button" id="btn-footer-cta-image-select">Selecionar imagem</button>
                            <?php if ($footerCtaImageUrl): ?>
                                <button type="button" class="button" id="btn-footer-cta-image-remove">Remover</button>
                            <?php endif; ?>
                            <script>
                            (function(){
                                var frame;
                                document.getElementById('btn-footer-cta-image-select').addEventListener('click', function(){
                                    if (frame) { frame.open(); return; }
                                    frame = wp.media({ title: 'Selecionar imagem do banner', button: { text: 'Usar imagem' }, multiple: false });
                                    frame.on('select', function(){
                                        var att = frame.state().get('selection').first().toJSON();
                                        document.getElementById('footer_cta_image_url').value = att.url;
                                        document.getElementById('footer_cta_image_id').value  = att.id;
                                        var preview = document.getElementById('footer-cta-image-preview');
                                        preview.innerHTML = '<img src="' + att.url + '" style="max-height:120px;display:block">';
                                    });
                                    frame.open();
                                });
                                var btnRemove = document.getElementById('btn-footer-cta-image-remove');
                                if (btnRemove) btnRemove.addEventListener('click', function(){
                                    document.getElementById('footer_cta_image_url').value = '';
                                    document.getElementById('footer_cta_image_id').value  = '0';
                                    document.getElementById('footer-cta-image-preview').innerHTML = '';
                                });
                            })();
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_cta_primary_label">Rodapé — Botão CTA Primário</label></th>
                        <td>
                            <input
                                type="text"
                                id="footer_cta_primary_label"
                                name="<?= self::OPTION_KEY ?>[footer_cta_primary_label]"
                                value="<?= esc_attr($footerCtaPrimaryLabel) ?>"
                                class="regular-text"
                                placeholder="Agendar Exame"
                            >
                            <input
                                type="text"
                                id="footer_cta_primary_url"
                                name="<?= self::OPTION_KEY ?>[footer_cta_primary_url]"
                                value="<?= esc_attr($footerCtaPrimaryUrl) ?>"
                                class="regular-text"
                                placeholder="https://... ou /agendar"
                            >
                            <p class="description">Botão contornado do banner de CTA do rodapé. Ex: "Agendar Exame".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_cta_secondary_label">Rodapé — Botão CTA Secundário</label></th>
                        <td>
                            <input
                                type="text"
                                id="footer_cta_secondary_label"
                                name="<?= self::OPTION_KEY ?>[footer_cta_secondary_label]"
                                value="<?= esc_attr($footerCtaSecondaryLabel) ?>"
                                class="regular-text"
                                placeholder="Fale Conosco"
                            >
                            <input
                                type="text"
                                id="footer_cta_secondary_url"
                                name="<?= self::OPTION_KEY ?>[footer_cta_secondary_url]"
                                value="<?= esc_attr($footerCtaSecondaryUrl) ?>"
                                class="regular-text"
                                placeholder="https://... ou /contato"
                            >
                            <p class="description">Botão preenchido do banner de CTA do rodapé. Ex: "Fale Conosco".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_privacy_label">Rodapé — Link de Política de Privacidade</label></th>
                        <td>
                            <input
                                type="text"
                                id="footer_privacy_label"
                                name="<?= self::OPTION_KEY ?>[footer_privacy_label]"
                                value="<?= esc_attr($footerPrivacyLabel) ?>"
                                class="regular-text"
                                placeholder="Política de privacidade"
                            >
                            <input
                                type="text"
                                id="footer_privacy_url"
                                name="<?= self::OPTION_KEY ?>[footer_privacy_url]"
                                value="<?= esc_attr($footerPrivacyUrl) ?>"
                                class="regular-text"
                                placeholder="https://... ou /politica-de-privacidade"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_credits_text">Rodapé — Texto de Créditos</label></th>
                        <td>
                            <input
                                type="text"
                                id="footer_credits_text"
                                name="<?= self::OPTION_KEY ?>[footer_credits_text]"
                                value="<?= esc_attr($footerCreditsText) ?>"
                                class="regular-text"
                                placeholder="Desenvolvido por Upsites"
                            >
                        </td>
                    </tr>
                </table>
                <?php submit_button('Salvar opções'); ?>
            </form>
        </div>
        <?php
    }
}
