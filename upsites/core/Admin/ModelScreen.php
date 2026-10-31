<?php
declare(strict_types=1);

namespace Core\Admin;

use Core\Framework\RouteResolver;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Tela de edição das páginas de modelo (fw:{slug} e a página inicial): o conteúdo
 * vem só dos campos ACF, então o editor de conteúdo e a imagem destacada saem —
 * fica o título e o grupo de campos da página (uma aba por seção).
 */
final class ModelScreen
{
    private const SUPPORTS = ['editor', 'thumbnail'];

    public static function register(): void
    {
        add_action('load-post.php', [self::class, 'strip']);
        add_action('enqueue_block_editor_assets', [self::class, 'reloadOnModel']);
    }

    /** Remove editor e imagem destacada quando a página aberta é de modelo. */
    public static function strip(): void
    {
        $id = absint(wp_unslash($_GET['post'] ?? 0)); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- só o ID da tela de edição (o WP já checou o acesso)
        if (!$id || get_post_type($id) !== 'page' || !self::isModel($id)) return;

        foreach (self::SUPPORTS as $feature) remove_post_type_support('page', $feature);
    }

    /** Página de módulo (modelo fw:{slug} ou a página inicial com o modelo padrão) — mesma regra do render. */
    public static function isModel(int $pageId): bool
    {
        return RouteResolver::moduleForPage($pageId) !== null;
    }

    /**
     * Página nova no editor de blocos: ao salvar com um modelo "Página · …" escolhido,
     * recarrega para abrir a tela só com os campos.
     */
    public static function reloadOnModel(): void
    {
        if (get_current_screen()?->post_type !== 'page') return;

        wp_add_inline_script('wp-data', <<<'JS'
wp.domReady(() => {
  const { select, subscribe } = wp.data;
  let saving = false;
  subscribe(() => {
    const editor = select('core/editor');
    if (!editor) return;
    const busy = editor.isSavingPost() && !editor.isAutosavingPost();
    if (saving && !busy && editor.didPostSaveRequestSucceed()) {
      const template = editor.getCurrentPostAttribute('template') || '';
      if (template.startsWith('fw:')) window.location.reload();
    }
    saving = busy;
  });
});
JS);
    }
}
