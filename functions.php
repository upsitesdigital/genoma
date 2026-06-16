<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

define('UPWORK_VERSION', '1.0.0');
define('UPWORK_DIR', get_template_directory());
define('UPWORK_URI', get_template_directory_uri());

require_once UPWORK_DIR . '/vendor/autoload.php';

\Core\Framework\Bootstrap::init();

add_action('after_setup_theme', function (): void {
    load_theme_textdomain('upwork', UPWORK_DIR . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    register_nav_menus([
        'primary' => __('Menu Principal', 'upwork'),
        'footer'  => __('Menu Rodapé', 'upwork'),
    ]);
});

// Remove assets desnecessários do WP no front
add_action('wp_enqueue_scripts', function (): void {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('global-styles');
}, 100);
