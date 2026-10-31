<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Dados de um CPT (usado por Core\PostTypes\PostType).
 * Como attribute em módulo é legado: registre em upsites/customizer-register/inc/register_cpts.php
 * com PostType::add() — o npm run review acusa #[PostType] no module.php.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class PostType
{
    public function __construct(
        public readonly string $slug,
        public readonly string $singular,
        public readonly string $plural,
        public readonly string $icon = 'dashicons-admin-post',
        public readonly bool $public = true,
        public readonly bool $showInRest = true,
        public readonly array $supports = ['title', 'editor', 'thumbnail'],
        public readonly array $rewrite = [],
    ) {}
}
