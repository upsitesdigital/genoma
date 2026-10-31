<?php
defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer
/**
 * Contador de visualizações de post (meta US_post_views_count + coluna em Posts).
 * Desligado por padrão: grava no banco a cada visualização e, com cache de página,
 * conta errado. Para ligar no projeto: add_filter('upwork/post_views', '__return_true');
 */

function US_post_views_enabled(): bool
{
  return (bool) apply_filters('upwork/post_views', false);
}

function US_set_post_views($postID)
{
  $postID = (int) $postID;
  if (!US_post_views_enabled() || !$postID) return;
  // não conta quem edita o site, prévias nem robôs comuns (cada visita é uma escrita no banco)
  if (is_user_logged_in() || is_preview()) return;
  $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
  if ($agent === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor|headless|lighthouse/i', $agent)) return;

  global $wpdb;
  $count_key = 'US_post_views_count';
  // incremento atômico: ler-somar-gravar (get + update_post_meta) perde visitas simultâneas
  $updated = $wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->postmeta} SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
    $postID,
    $count_key
  ));
  if (!$updated) add_post_meta($postID, $count_key, 1, true);
  wp_cache_delete($postID, 'post_meta');
}

add_filter('manage_posts_columns', function ($column) {
  if (US_post_views_enabled()) $column['US_post_views_count'] = __('Visualizações', 'upsites');
  return $column;
});

add_action('manage_posts_custom_column', function ($name) {
  global $post;
  if ($name === 'US_post_views_count') echo esc_html((string) (int) get_post_meta($post->ID, 'US_post_views_count', true));
});
