<?php
defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer
/**
 * CPTs (custom post types) do tema — único lugar de CPTs.
 *
 * Um PostType::add() por CPT. `public: false` = só no admin (editável no painel, sem
 * página/URL no site); `args` acrescenta/sobrescreve argumentos do register_post_type.
 * Campos ACF do CPT: Fields::postType() em acf_fields.php. Listagem: controller do módulo.
 *
 *   PostType::add('servico', 'Serviço', 'Serviços', icon: 'dashicons-portfolio', supports: ['title', 'thumbnail']);
 *   PostType::add('depoimento', 'Depoimento', 'Depoimentos', icon: 'dashicons-format-quote', public: false, supports: ['title']);
 */

use Core\PostTypes\PostType;

function US_register_cpts(): void
{
    // ↑ novos CPTs entram acima desta linha
}
add_action('init', 'US_register_cpts');
