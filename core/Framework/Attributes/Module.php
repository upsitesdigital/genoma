<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Module
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $route = '/',
        public readonly string $icon = 'admin-generic',
        public readonly int $menuPosition = 30,
        public readonly bool $template = false,
        public readonly string $templateLabel = '',
    ) {}
}
