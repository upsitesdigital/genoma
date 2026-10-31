<?php
defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Autoload PSR-4 do framework: Core\Foo\Bar → upsites/core/Foo/Bar.php
 * (os módulos em template-pages/ são carregados pelo ModuleLoader)
 */
spl_autoload_register(function (string $class): void {
  if (strncmp($class, 'Core\\', 5) !== 0) return;

  $file = get_template_directory() . '/upsites/core/' . str_replace('\\', '/', substr($class, 5)) . '.php';
  if (file_exists($file)) require $file;
});

// Helpers de markup dos templates (fw_cn, fw_attrs, fw_img…)
require get_template_directory() . '/upsites/core/Support/helpers.php';

// Funções do tema + ACF PRO embarcado (carregado lá se o plugin não estiver ativo)
require get_template_directory() . '/upsites/functions.php';

Core\Framework\Bootstrap::init();
