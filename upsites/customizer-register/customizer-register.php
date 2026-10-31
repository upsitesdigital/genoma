<?php
defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * US_register_cpts
 */
require __DIR__ . '/inc/register_cpts.php';

/**
 * US_register_taxes
 */
require __DIR__ . '/inc/register_taxes.php';

/**
 * acf_add_local_field_group
 */
require __DIR__ . '/inc/acf_fields.php';
