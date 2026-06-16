<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Cache
{
    public function __construct(public readonly int $ttl = 300) {}
}
