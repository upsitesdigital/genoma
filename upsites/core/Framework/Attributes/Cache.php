<?php
declare(strict_types=1);

namespace Core\Framework\Attributes;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Cache da resposta da rota (transient). A chave usa só os parâmetros do caminho
 * (`:id`) e os da query string listados em `vary` — qualquer outro parâmetro é
 * ignorado (não cria entrada nova). Liste em `vary` só parâmetros de poucos valores
 * que mudam a resposta (ex.: `vary: ['pagina', 'categoria']`), validados no controller.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Cache
{
    /** @param list<string> $vary parâmetros da query string que mudam a resposta */
    public function __construct(
        public readonly int $ttl = 300,
        public readonly array $vary = [],
    ) {}
}
