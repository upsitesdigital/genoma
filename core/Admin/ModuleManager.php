<?php
declare(strict_types=1);

namespace Core\Admin;

use Core\Framework\ModuleLoader;
use Core\Framework\ModuleRegistry;
use Core\Framework\Attributes\Required;

class ModuleManager
{
    public static function register(): void
    {
        add_action('admin_menu',                   [self::class, 'addMenuPages']);
        add_action('admin_post_upwork_toggle_module', [self::class, 'handleToggle']);
    }

    public static function addMenuPages(): void
    {
        add_menu_page(
            page_title: 'UpWork Framework',
            menu_title: 'UpWork',
            capability: 'manage_options',
            menu_slug:  'upwork',
            callback:   [self::class, 'renderModules'],
            icon_url:   'dashicons-superhero',
            position:   3,
        );

        add_submenu_page(
            parent_slug: 'upwork',
            page_title:  'Módulos',
            menu_title:  'Módulos',
            capability:  'manage_options',
            menu_slug:   'upwork',
            callback:    [self::class, 'renderModules'],
        );

    }

    public static function handleToggle(): void
    {
        if (!current_user_can('manage_options')) wp_die('Sem permissão.');

        check_admin_referer('upwork_toggle_module');

        $slug   = sanitize_key($_POST['slug'] ?? '');
        $enable = ($_POST['module_action'] ?? '') === 'activate';

        if ($slug) {
            ModuleRegistry::setActive($slug, $enable);
        }

        wp_safe_redirect(admin_url('admin.php?page=upwork&updated=1'));
        exit;
    }

    public static function renderModules(): void
    {
        $modules = ModuleLoader::all();
        $updated = isset($_GET['updated']);
        ?>
        <div class="wrap">
            <h1>Módulos UpWork</h1>

            <?php if ($updated): ?>
                <div class="notice notice-success is-dismissible"><p>Configuração salva.</p></div>
            <?php endif; ?>

            <p class="description" style="margin-bottom:16px">
                Ative ou desative módulos. Módulos marcados como <strong>Obrigatório</strong> não podem ser desativados.
            </p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:24px"></th>
                        <th>Módulo</th>
                        <th>Slug</th>
                        <th>Rota</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($modules)): ?>
                    <tr><td colspan="6">Nenhum módulo encontrado em <code>/app/</code>.</td></tr>
                <?php else: ?>
                    <?php foreach ($modules as $data):
                        $attr      = $data['attr'];
                        $required  = !empty($data['reflection']->getAttributes(Required::class));
                        $active    = $required || ModuleRegistry::isActive($attr->slug);
                        $actionUrl = admin_url('admin-post.php');
                    ?>
                    <tr>
                        <td>
                            <span class="dashicons <?= esc_attr($attr->icon) ?>"
                                  style="color:<?= $active ? '#00a32a' : '#72777c' ?>"></span>
                        </td>
                        <td>
                            <strong><?= esc_html($attr->name) ?></strong>
                            <?php if ($required): ?>
                                <span class="dashicons dashicons-lock"
                                      title="Obrigatório"
                                      style="color:#72777c;font-size:14px;vertical-align:middle"></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= esc_html($attr->slug) ?></code></td>
                        <td><code><?= esc_html($attr->route) ?></code></td>
                        <td>
                            <span style="color:<?= $active ? '#00a32a' : '#72777c' ?>;font-weight:600">
                                <?= $active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($required): ?>
                                <span style="color:#72777c">Obrigatório</span>
                            <?php else: ?>
                                <form method="post" action="<?= esc_url($actionUrl) ?>" style="display:inline">
                                    <?php wp_nonce_field('upwork_toggle_module') ?>
                                    <input type="hidden" name="action"        value="upwork_toggle_module">
                                    <input type="hidden" name="slug"          value="<?= esc_attr($attr->slug) ?>">
                                    <input type="hidden" name="module_action" value="<?= $active ? 'deactivate' : 'activate' ?>">
                                    <button type="submit" class="button <?= $active ? 'button-secondary' : 'button-primary' ?>">
                                        <?= $active ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
