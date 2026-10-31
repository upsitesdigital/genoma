<?php
defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer
/**
 * Taxonomias do tema — único lugar de taxonomias.
 *
 * Um Taxonomy::add() por taxonomia, com o(s) CPT(s) a que pertence. `public: false` =
 * só no admin (termos editáveis no painel, sem página de termo); `args` acrescenta/
 * sobrescreve argumentos do register_taxonomy. Campos ACF dos termos:
 * Fields::taxonomy() em acf_fields.php.
 *
 *   Taxonomy::add('servico_categoria', 'Categorias', 'servico', hierarchical: true);
 *   Taxonomy::add('servico_cor', 'Cores', 'servico', singular: 'Cor'); // singular ≠ plural sem "s"
 */

use Core\PostTypes\Taxonomy;

function US_register_taxes(): void
{
    // ↑ novas taxonomias entram acima desta linha
}
add_action('init', 'US_register_taxes');
