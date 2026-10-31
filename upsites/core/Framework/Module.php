<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Módulo = uma página do tema (template-pages/{slug}/): o attribute #[Module] define
 * slug, rota e modelo "Página · …". CPTs e taxonomias NÃO ficam aqui: estão em
 * upsites/customizer-register/inc/register_cpts.php e register_taxes.php.
 * Os campos ACF NÃO ficam aqui: todos estão em
 * upsites/customizer-register/inc/acf_fields.php (Fields::section()).
 */
abstract class Module
{
    /** Hook chamado após o módulo ser carregado — use para lógica extra. */
    public function boot(): void {}

    /** @deprecated Legado: campos vão em upsites/customizer-register/inc/acf_fields.php. */
    public function fields(): void {}

    /** @deprecated Use Fields::section('{slug}', …) em upsites/customizer-register/inc/acf_fields.php. */
    protected function addSection(string $section, string $label, array $fields, int $order = 0, ?array $location = null): void
    {
        Fields::section($this->meta()->slug, $section, $label, $fields, $order, $location);
    }

    /** @deprecated Use Fields::icon() em acf_fields.php. */
    protected function iconField(?string $default = null): array
    {
        return Fields::icon($default);
    }

    /** @deprecated Use Fields::image() em acf_fields.php. */
    protected function imageField(?string $default = null): array
    {
        return Fields::image($default);
    }

    private function meta(): Attributes\Module
    {
        return (new \ReflectionClass($this))->getAttributes(Attributes\Module::class)[0]->newInstance();
    }
}
