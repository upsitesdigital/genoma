<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Rota só para usuário logado. `$can` = capability do WP (`edit_posts`,
 * `manage_options`…); nomes de papel (`editor`, `administrator`…) são aceitos e
 * convertidos na capability mínima do papel — então um papel acima também passa
 * (administrador passa em `#[Auth('editor')]`). Vazio = qualquer usuário logado.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Auth
{
    /** Papel → capability mínima dele (papéis acima têm a mesma capability). */
    private const ROLES = [
        'administrator' => 'manage_options',
        'editor'        => 'edit_others_posts',
        'author'        => 'publish_posts',
        'contributor'   => 'edit_posts',
        'subscriber'    => 'read',
    ];

    public function __construct(public readonly string $can = '') {}

    public function capability(): string
    {
        return self::ROLES[$this->can] ?? $this->can;
    }
}
