<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Put
{
    public function __construct(public readonly string $path) {}
}
