<?php
defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * ACF PRO embarcado (upsites/core/acf/) — o tema funciona com ACF mesmo sem o
 * plugin instalado. Se um ACF (PRO ou free) já estiver ativo como plugin, ele tem
 * prioridade e esta cópia não é carregada (os plugins carregam antes do tema).
 * Obs.: com o ACF free ativo, repeater/flexible_content/gallery não existem.
 */
if (!class_exists('ACF')) {
  define('US_ACF_PATH', get_template_directory() . '/upsites/core/acf/');
  define('US_ACF_URL', get_template_directory_uri() . '/upsites/core/acf/');

  include_once US_ACF_PATH . 'acf.php';

  // URLs dos assets (CSS/JS) do ACF apontando para a cópia do tema
  add_filter('acf/settings/url', fn() => US_ACF_URL);

  // Campos são registrados por código (customizer-register/inc/acf_fields.php) — esconde o menu do
  // ACF e o aviso de atualizações, que não se aplicam à cópia embarcada
  add_filter('acf/settings/show_admin', '__return_false');
  add_filter('acf/settings/show_updates', '__return_false', 100);
}

/** 
 * After theme setup hook actions
 */
add_action('after_setup_theme', function () {

  // traduções do tema (text domain "upsites", arquivos em languages/upsites-{locale}.mo)
  load_theme_textdomain('upsites', get_template_directory() . '/languages');

  // editor de blocos (posts) com a fonte e as cores do site — gerado do main.scss pelo build
  // (upsites/core/cli/theme-json.mjs); a paleta vem do theme.json
  add_theme_support('editor-styles');
  add_editor_style('assets/css/editor.css');

  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('automatic-feed-links');
  add_theme_support('responsive-embeds');
  // "Largura ampla"/"Largura total" nos blocos (CSS de .alignwide/.alignfull no main.scss)
  add_theme_support('align-wide');
  add_theme_support('html5', array(
    'search-form',
    'comment-form',
    'comment-list',
    'gallery',
    'caption',
    'style',
    'script',
  ));

  // WooCommerce: só com o plugin ativo (after_setup_theme roda depois dos plugins carregarem)
  if (class_exists('WooCommerce')) {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
  }

  // This theme uses wp_nav_menu() in two locations.
  // Nomes do tema React anterior (genoma em produção): mantidos para os menus continuarem atribuídos.
  register_nav_menus(
    array(
      'primary' => __('Menu Principal', 'upsites'),
      'footer' => __('Menu Rodapé', 'upsites'),
    )
  );
  add_theme_support(
    'custom-logo',
    array(
      'width'       => 197,
      'height'      => 50,
      'flex-height' => true,
      'flex-width'  => true,
    )
  );
});

/**
 * jQuery no rodapé em vez de bloquear a renderização no <head> (o tema não usa jQuery; só
 * plugins o carregam). Mantém o registro do core — 'jquery' = jquery-core + jquery-migrate,
 * com versão para o cache — e só muda o grupo dos três para o footer. Script de plugin que
 * dependa dele e saia no <head> puxa o jQuery para o <head> junto (o WP resolve as dependências).
 */
add_action('wp_enqueue_scripts', function () {
  foreach (array('jquery', 'jquery-core', 'jquery-migrate') as $handle) {
    wp_script_add_data($handle, 'group', 1);
  }
}, 1);

/**
 * Critical CSS (primeira dobra) da página de módulo: assets/css/{módulo}-above-the-fold.css
 * (localhost) / .min.css (homolog/prod), gerado pelo gulp de src/sass/{módulo}-above-the-fold.scss
 * — ex.: home-above-the-fold.scss para a home. Um arquivo só para celular e desktop: o Tailwind
 * já põe os breakpoints (md:, lg:) dentro dele. Sem o arquivo, nada muda na página.
 *
 * @return string|null caminho do arquivo, ou null (página sem critical CSS)
 */
function US_critical_css_file() {
  static $file = null;
  if (null !== $file) return $file ?: null;

  $file    = '';
  $current = Core\Framework\View::current();
  $module  = is_array($current) ? (string) ($current['module'] ?? '') : '';
  if (preg_match('/^[a-z0-9-]+$/', $module)) {
    $name = $module . '-above-the-fold' . (Core\Support\Asset::isLocal() ? '' : '.min') . '.css';
    $path = get_template_directory() . '/assets/css/' . $name;
    if (is_file($path)) $file = $path;
  }
  return $file ?: null;
}

/** Imprime o critical CSS inline no <head> (antes de qualquer CSS enfileirado). */
function US_critical_css() {
  $file = US_critical_css_file();
  if (!$file) return;

  // CSS do build do tema; `</style` dentro dele não pode fechar a tag antes da hora. O sourcemap
  // embutido da versão aberta (localhost) não serve inline: sai
  $css = (string) preg_replace('#/\*\# sourceMappingURL=[^*]*\*/#', '', (string) file_get_contents($file));
  // url() relativo (ex.: @font-face '../fonts/…') é relativo ao arquivo em assets/css/ — inline,
  // seria relativo à URL da página (404): vira absoluto
  $base = get_template_directory_uri() . '/assets/css/';
  $css  = (string) preg_replace_callback(
    '/url\(\s*([\'"]?)(?![a-z][a-z0-9+.-]*:|\/|#)([^\'")]+)\1\s*\)/i',
    function ($m) use ($base) { return 'url(' . $m[1] . $base . $m[2] . $m[1] . ')'; },
    $css
  );
  $css = str_ireplace('</style', '<\/style', $css);
  echo '<style id="fw-critical-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS gerado pelo build do tema (assets/css)
}
add_action('wp_head', 'US_critical_css', 1);

/**
 * Página com critical CSS: o main.css não bloqueia a renderização — carrega com media="print"
 * e vira "all" ao terminar (padrão do loadCSS); sem JS, o <noscript> carrega do jeito normal.
 * Sem critical CSS, o main.css continua como sempre (bloqueante, para não piscar sem estilo).
 */
add_filter('style_loader_tag', function ($tag, $handle) {
  if ('fw-main' !== $handle || !US_critical_css_file()) return $tag;

  $async = preg_replace('/\smedia=([\'"])[^\'"]*\1/', ' media=\'print\' onload="this.media=\'all\'"', $tag, 1);
  return $async . '<noscript>' . trim($tag) . "</noscript>\n";
}, 10, 2);


/** 
 * Load theme assets
 */
add_action('wp_enqueue_scripts', function () {
  /*
   * JS manual (src/js/mainJS.js, compilado pelo gulp) — JavaScript puro, sem jQuery.
   * Asset::script() escolhe o arquivo pelo ambiente: localhost → mainJS.js,
   * homolog/prod → mainJS.min.js. O main.css (Tailwind + Sass) entra em todas as
   * páginas por Core\Support\Asset::enqueueStyles().
   */
  $asset = Core\Support\Asset::class;
  // dado do PHP para o mainJS: wp_localize_script('mainJS', …) ou wp_add_inline_script() aqui.
  // Ação de admin-ajax nova cria o próprio nonce (wp_create_nonce('acao-especifica')) e confere
  // com check_ajax_referer().
  $asset::script('mainJS', 'mainJS');

  // resposta aninhada de comentário (move o formulário para baixo do comentário respondido)
  if (is_singular() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }
}, 999, 1);

/**
 * Preload das fontes locais (assets/fonts/, copiadas de src/fonts/ pelo build) — só a do texto,
 * que aparece acima da dobra. Ex.: add_filter('upwork/preload_fonts', fn() => ['inter-latin-400.woff2']);
 * Aceita .woff2 e .woff (o `type` do <link> segue a extensão); outros formatos são ignorados.
 */
add_action('wp_head', function () {
  $types = array('woff2' => 'font/woff2', 'woff' => 'font/woff');
  foreach ((array) apply_filters('upwork/preload_fonts', array()) as $font) {
    $file = 'assets/fonts/' . basename((string) $font);
    $type = $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
    if (!$type || !is_file(get_template_directory() . '/' . $file)) continue;
    printf(
      '<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . "\n",
      esc_url(get_template_directory_uri() . '/' . $file),
      esc_attr($type)
    );
  }
}, 2, 0);

// Genoma: Manrope (texto) na primeira dobra; animações de entrada ao rolar, como no tema React
add_filter('upwork/preload_fonts', fn() => array('manrope-latin-variable.woff2'));
add_filter('upwork/animation', '__return_true');


/* US_SET_POST_VIEWS — contador de visualizações (coluna "Views" em Posts).
 * Grava no banco a cada visualização de post: desligado por padrão.
 * Para ligar: add_filter('upwork/post_views', '__return_true'); */
define('US_SET_POST_VIEWS', '1.1.0');
require __DIR__ . '/post-views/post-views.php';

/* US_CUSTOMIZER_REGISTER — campos ACF (acf_fields.php), CPTs e taxonomias */
define('US_CUSTOMIZER_REGISTER', '1.1.0');
require __DIR__ . '/customizer-register/customizer-register.php';

/* meta author da UpSites no <head> — desligada por padrão (metadados de SEO são do Yoast).
 * Para ligar: add_filter('upwork/author_meta', '__return_true'); */
add_action('wp_head', function () {
  if (!apply_filters('upwork/author_meta', false)) return;
  echo '<meta name="author" content="' . esc_attr__('Criação de Site por UpSites', 'upsites') . '">' . "\n";
}, 1, 0);