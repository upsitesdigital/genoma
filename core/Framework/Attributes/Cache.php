<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Cache
{
    /**
     * @param string[] $vary Params de query que mudam a resposta (ex: page, s).
     *                       Os demais não entram na chave do cache.
     */
    public function __construct(
        public readonly int $ttl = 300,
        public readonly array $vary = [],
    ) {}
}
