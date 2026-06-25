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
            'site_name'     => sanitize_text_field($input['site_name'] ?? ''),
            'logo_url'      => esc_url_raw($input['logo_url'] ?? ''),
            'logo_id'       => absint($input['logo_id'] ?? 0),
            'primary_color' => sanitize_hex_color($input['primary_color'] ?? ''),
            'footer_text'   => wp_kses_post($input['footer_text'] ?? ''),
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
                </table>
                <?php submit_button('Salvar opções'); ?>
            </form>
        </div>
        <?php
    }
}
