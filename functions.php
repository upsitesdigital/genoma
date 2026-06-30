<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

define('UPWORK_VERSION', '1.0.0');
define('UPWORK_DIR', get_template_directory());
define('UPWORK_URI', get_template_directory_uri());

define( 'MY_ACF_PATH', get_stylesheet_directory() . '/core/acf/' );
define( 'MY_ACF_URL', get_stylesheet_directory_uri() . '/core/acf/' );

// Include the ACF plugin.
include_once( MY_ACF_PATH . 'acf.php' );

// Customize the URL setting to fix incorrect asset URLs.
add_filter('acf/settings/url', 'my_acf_settings_url');
function my_acf_settings_url( $url ) {
  return MY_ACF_URL;
}

// Check if ACF is installed
if ( !is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) and !is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
    // Hide the ACF admin menu item.
    add_filter( 'acf/settings/show_admin', '__return_false' );
    // Hide the ACF Updates menu
    add_filter( 'acf/settings/show_updates', '__return_false', 100 );
}

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
