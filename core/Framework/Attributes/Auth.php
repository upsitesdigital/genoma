<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Auth
{
    public function __construct(public readonly string $role = '') {}
}
