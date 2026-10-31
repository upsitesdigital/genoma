<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Support\Asset;

/**
 * Preview isolado de UMA parte, sem header/footer — para o screenshot do `npm run fidelity`:
 *
 *   {site}/?fw_preview=home/hero         seção (dados = controller do módulo sem página → padrões do Figma)
 *   {site}/?fw_preview=layout/header     header/footer (montam os próprios dados)
 *   {site}/?fw_preview=shared/cta        componente compartilhado (dados = padrões do controller, se houver)
 *
 * Só no ambiente local (Asset::isLocalHost — não o FW_MINIFIED_ASSETS) — em homologação/produção o parâmetro é ignorado.
 * Ao terminar (fontes e imagens carregadas) marca <body data-ready="1">.
 */
final class Preview
{
    public static function register(): void
    {
        add_action('template_redirect', [self::class, 'maybeRender'], 0);
    }

    public static function maybeRender(): void
    {
        $part = isset($_GET['fw_preview']) ? sanitize_text_field(wp_unslash($_GET['fw_preview'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- leitura, só no localhost
        if ($part === '' || !Asset::isLocalHost()) return;
        if (!preg_match('#^([a-z0-9-]+)/([a-z0-9-]+)$#', $part, $m)) wp_die('fw_preview: use {grupo}/{nome}', 400);

        [, $group, $name] = $m;

        // &fw_preview_page={id}: conteúdo salvo dessa página (e ela como página atual, para o
        // header/footer) no lugar dos padrões — medir contra o site no ar, não contra o Figma
        $pageId = isset($_GET['fw_preview_page']) ? absint($_GET['fw_preview_page']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- leitura, só no localhost
        if ($pageId && get_post_type($pageId) === 'page') {
            $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new \WP_Query(['page_id' => $pageId]);
            $GLOBALS['post']     = get_post($pageId);
        }

        $data = [];
        if (!in_array($group, ['layout', 'shared'], true)) {
            $module = View::fetch($group, $pageId ?: null);
            $key    = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $name))));
            $data   = is_array($module) && is_array($module[$key] ?? null) ? $module[$key] : [];
        }

        show_admin_bar(false);
        status_header(200);
        nocache_headers();
        ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body class="fw-preview">
<div id="preview"><?php Part::render($part, $data); ?></div>
<?php wp_footer(); ?>
<script>
(async () => {
  await document.fonts.ready;
  await Promise.all([...document.images].filter((i) => !i.complete).map((i) => new Promise((done) => {
    i.addEventListener('load', done, { once: true });
    i.addEventListener('error', done, { once: true });
  })));
  await new Promise((done) => requestAnimationFrame(() => requestAnimationFrame(done)));
  if (!document.querySelector('#preview > *')) document.body.dataset.error = 'a parte não renderizou nada (veja o error_log)';
  document.body.dataset.ready = '1';
})();
</script>
</body>
</html>
        <?php
        exit;
    }
}
