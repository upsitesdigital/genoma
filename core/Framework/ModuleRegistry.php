<?php
declare(strict_types=1);

namespace Core\Framework;

class ModuleRegistry
{
    const OPTION_KEY = 'upwork_active_modules';

    /** Retorna true se o módulo está ativo (padrão: ativo). */
    public static function isActive(string $slug): bool
    {
        $stored = get_option(self::OPTION_KEY, []);
        return isset($stored[$slug]) ? (bool) $stored[$slug] : true;
    }

    public static function setActive(string $slug, bool $active): void
    {
        $stored         = get_option(self::OPTION_KEY, []);
        $stored[$slug]  = $active;
        update_option(self::OPTION_KEY, $stored);
    }

    /** @return array<string, bool> */
    public static function all(): array
    {
        return (array) get_option(self::OPTION_KEY, []);
    }
}
