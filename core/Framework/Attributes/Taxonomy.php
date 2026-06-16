<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Taxonomy
{
    public function __construct(
        public readonly string $slug,
        public readonly string $plural,
        public readonly string|array $postType,
        public readonly bool $hierarchical = false,
        public readonly bool $showInRest = true,
    ) {}
}
