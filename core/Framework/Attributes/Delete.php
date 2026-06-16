<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Delete
{
    public function __construct(public readonly string $path) {}
}
