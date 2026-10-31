<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Dados de uma taxonomia (usado por Core\PostTypes\Taxonomy).
 * Como attribute em módulo é legado: registre em upsites/customizer-register/inc/register_taxes.php
 * com Taxonomy::add() — o npm run review acusa #[Taxonomy] no module.php.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Taxonomy
{
    public function __construct(
        public readonly string $slug,
        public readonly string $plural,
        public readonly string|array $postType,
        public readonly bool $hierarchical = false,
        public readonly bool $showInRest = true,
        public readonly ?string $singular = null, // null = o plural sem o "s" final
    ) {}
}
