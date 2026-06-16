<?php
declare(strict_types=1);

use Core\Support\Asset;
use Core\Framework\RouteResolver;

// ── SEO ──────────────────────────────────────────────────────────────────────
$seoTitle       = wp_get_document_title();
$seoDescription = get_bloginfo('description');
$seoImage       = '';
$queriedId      = get_queried_object_id();

if ($queriedId) {
    $excerpt = get_the_excerpt($queriedId);
    if ($excerpt) $seoDescription = wp_strip_all_tags($excerpt);

    if (has_post_thumbnail($queriedId)) {
        $seoImage = (string) get_the_post_thumbnail_url($queriedId, 'large');
    }
}

// ── Boot data para o React ───────────────────────────────────────────────────
$bootData = [
    'apiBase'      => rest_url('framework/v1'),
    'wpApiBase'    => rest_url('wp/v2'),
    'siteUrl'      => home_url(),
    'themeUrl'     => get_template_directory_uri(),
    'nonce'        => wp_create_nonce('wp_rest'),
    'themeOptions' => get_option('upwork_theme_options', []),
    'currentPath'  => $_SERVER['REQUEST_URI'] ?? '/',
    'currentRoute' => RouteResolver::current(),
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= esc_html($seoTitle) ?></title>
    <meta name="description" content="<?= esc_attr($seoDescription) ?>">

    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= esc_attr($seoTitle) ?>">
    <meta property="og:description" content="<?= esc_attr($seoDescription) ?>">
    <meta property="og:url"         content="<?= esc_url(home_url(add_query_arg([]))) ?>">
    <?php if ($seoImage): ?>
    <meta property="og:image"       content="<?= esc_url($seoImage) ?>">
    <meta name="twitter:card"       content="summary_large_image">
    <meta name="twitter:image"      content="<?= esc_url($seoImage) ?>">
    <?php endif; ?>

    <?php wp_head(); ?>
    <?= Asset::style('resources/app.tsx') ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div id="app-root"></div>
    <script>window.FW_BOOT = <?= wp_json_encode($bootData) ?>;</script>
    <?= Asset::script('resources/app.tsx') ?>
    <?php wp_footer(); ?>
</body>
</html>
