<?php
declare(strict_types=1);

namespace Core\Framework;

abstract class Module
{
    /** Registra campos ACF do módulo via acf_add_local_field_group(). */
    public function fields(): void {}

    /**
     * Quando o módulo tem mais de um CPT e os campos diferem por tipo,
     * sobrescreva este método em vez de fields().
     */
    public function fieldsFor(string $postType): void {}

    /** Hook chamado após o módulo ser carregado — use para lógica extra. */
    public function boot(): void {}
}
