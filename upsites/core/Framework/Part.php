<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Support\Asset;

/**
 * Renderiza as partes do tema (seções, header/footer, componentes compartilhados):
 *
 *   template-parts/includes/{grupo}/{nome}/{nome}.php        markup (PHP + Tailwind)
 *   template-parts/includes/{grupo}/{nome}/{nome}.store.js   estado/ações (Interactivity API) — opcional
 *
 *   Part::render('home/hero', $data['hero']);                      // seção: null = oculta pelo editor
 *   Part::render('shared/button', ['link' => $data['botao'], 'class' => '…']);
 *
 * Dentro do arquivo, os dados chegam em `$data` (array). O que cada parte faz:
 *  - `$data === null` → não renderiza nada (seção com "Ocultar seção" marcado);
 *  - tem `{nome}.store.js` → o script module é enfileirado (só nas páginas que usam a parte);
 *  - erro dentro da parte → só ela some (registra no error_log), o resto da página segue —
 *    o equivalente ao ErrorBoundary por seção;
 *  - as diretivas `data-wp-*` são processadas no servidor (wp_interactivity_process_directives)
 *    uma vez, no nível de fora: o HTML já sai com o estado inicial aplicado.
 */
final class Part
{
    /** Profundidade de Part::render/Part::capture aninhados (só o nível 0 processa diretivas). */
    private static int $depth = 0;

    public static function render(string $name, ?array $data = []): void
    {
        echo self::html($name, $data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup do template, escapado nele
    }

    /** O HTML da parte, sem imprimir. '' se oculta, inexistente ou com erro. */
    public static function html(string $name, ?array $data = []): string
    {
        if ($data === null) return '';

        $name = trim($name, '/');
        if (!preg_match('#^[a-z0-9-]+/[a-z0-9-]+$#', $name)) {
            error_log("[upwork] Part::render('{$name}'): nome inválido — use {grupo}/{nome} em kebab-case");
            return '';
        }

        $file = get_template_directory() . "/template-parts/includes/{$name}/" . basename($name) . '.php';
        if (!is_file($file)) {
            error_log("[upwork] Part::render('{$name}'): template-parts/includes/{$name}/" . basename($name) . '.php não existe');
            return '';
        }

        Asset::module($name);

        return self::capture(static function () use ($file, $data): void {
            (static function (string $__file, array $data): void {
                require $__file;
            })($file, $data);
        }, $name);
    }

    /**
     * Roda `$render` com saída em buffer. Erro → descarta a saída da parte e registra no log.
     * No nível de fora, processa as diretivas da Interactivity API no HTML resultante.
     */
    public static function capture(callable $render, string $label = 'parte'): string
    {
        self::$depth++;
        $level = ob_get_level();
        ob_start();
        try {
            $render();
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            // fecha também os buffers que o template (ou um shortcode dentro dele) abriu e não fechou
            while (ob_get_level() > $level) ob_end_clean();
            error_log("[upwork] erro ao renderizar \"{$label}\": {$e->getMessage()} em {$e->getFile()}:{$e->getLine()}");
            $html = '';
        } finally {
            self::$depth--;
        }

        if (self::$depth === 0 && $html !== '' && str_contains($html, 'data-wp-')) {
            $html = wp_interactivity_process_directives($html);
        }

        return $html;
    }
}
