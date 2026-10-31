<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Support\Media;

abstract class Controller
{
    /** Lê um campo ACF de um post. */
    protected function field(int $postId, string $key): mixed
    {
        return function_exists('get_field') ? get_field($key, $postId) : null;
    }

    /** Lê todos os campos ACF de um post como array. */
    protected function fields(int $postId): array
    {
        return function_exists('get_fields') ? (get_fields($postId) ?: []) : [];
    }

    /**
     * Dados de uma seção, respeitando o toggle "Ocultar seção" do grupo ACF dela
     * (campo `{section}_ocultar`). Oculta → null (o view não renderiza).
     *
     *   'aboutUs' => $this->section($id, 'about_us', fn() => [...]),
     */
    protected function section(int $postId, string $section, callable $data): ?array
    {
        if ($this->field($postId, "{$section}_ocultar")) return null;

        return $data();
    }

    /**
     * Formulário do Contact Form 7 (campo ACF post_object do tipo wpcf7_contact_form,
     * return_format id) como referência `fw-cf7:{id}` — NÃO o HTML: o retorno do controller vai
     * para o cache, e o HTML do CF7 depende da requisição (valores e erros de um envio sem JS,
     * URL do `action`, campos `default:get`, scripts que ele enfileira). A parte shared/cf7-form
     * renderiza o shortcode a cada página. null se o CF7 não estiver ativo ou o formulário não existir.
     */
    protected function cf7(mixed $formId): ?string
    {
        // id (numérico) ou WP_Post; qualquer outra coisa (array de um campo múltiplo) → nenhum —
        // (int) de um array daria 1 e mostraria o formulário de ID 1
        $formId = $formId instanceof \WP_Post ? $formId->ID : (is_numeric($formId) ? (int) $formId : 0);
        if (!$formId || !function_exists('wpcf7_contact_form') || !wpcf7_contact_form($formId)) return null;

        return 'fw-cf7:' . $formId;
    }

    /**
     * Só em método REST (#[Get('/{slug}/…/:id')], o que um store busca): ID do post da rota —
     * lido SÓ do caminho. `get_param('id')` também leria `?id=` da query string e do corpo; o
     * Rest já confere a visibilidade desse id, mas o controller não deve aceitar outra origem.
     * Sem `:id` → 0. O page() recebe o id pronto do WordPress (não use isto lá).
     *
     *   $pageId = $this->pageId($request);
     */
    protected function pageId(\WP_REST_Request $request): int
    {
        $id = $request->get_url_params()['id'] ?? '';
        return is_string($id) && ctype_digit($id) ? (int) $id : 0;
    }

    /**
     * Só em método REST chamado SEM `:id` que precise da página inicial (Configurações →
     * Leitura) — só se for pública (publicada, de tipo público, sem senha). Privada, rascunho
     * ou com senha → 0: a rota sem id é tratada como pública pelo Rest e vai para o cache.
     * O page() da home já recebe o id da página inicial do WordPress.
     *
     *   $pageId = $this->pageId($request) ?: $this->frontPageId();
     */
    protected function frontPageId(): int
    {
        $post = get_post((int) get_option('page_on_front'));
        return $post && is_post_publicly_viewable($post) && $post->post_password === '' ? $post->ID : 0;
    }

    /**
     * O campo já foi salvo neste post? Distingue "nunca salvo" (o painel mostra o
     * conteúdo do Figma preenchido → o site mostra o mesmo) de "apagado pelo editor"
     * (salvo vazio → o elemento some do site, sem fallback).
     */
    protected function saved(int $postId, string $name): bool
    {
        return $postId > 0 && metadata_exists('post', $postId, $name);
    }

    /**
     * Texto (text, textarea, wysiwyg):
     * - nunca salvo → `$default` (o texto do Figma, o mesmo do default_value do campo);
     * - apagado pelo editor (salvo vazio) → null: o view não renderiza o elemento;
     * - preenchido → o texto do editor.
     *
     *   'titulo' => $this->text($id, 'hero_titulo', 'Texto do Figma'),
     */
    protected function text(int $postId, string $name, ?string $default = null): ?string
    {
        if (!$this->saved($postId, $name)) return $default;

        // get_field precisa da referência `_{name}` do ACF; sem ela (meta gravado por import/código), lê o valor cru
        $value = $this->field($postId, $name);
        if ($value === null || $value === false) $value = get_post_meta($postId, $name, true);

        return $this->textFrom(['v' => $value], 'v');
    }

    /** Texto de um subcampo de repeater ($row): vazio → null (a linha já foi salva pelo editor). */
    protected function textFrom(?array $row, string $name): ?string
    {
        $value = $row[$name] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * Linhas de um repeater: nunca salvo → null (use os itens do Figma: `?? self::X_DEFAULTS`);
     * salvo → as linhas do editor (array vazio se ele apagou todas — a lista some).
     *
     *   $rows  = $this->rows($id, 'services_itens');
     *   'itens' => $rows === null ? self::SERVICES_DEFAULTS : array_map(fn($r) => [...], $rows),
     */
    protected function rows(int $postId, string $name): ?array
    {
        if (!$this->saved($postId, $name)) return null;

        $value = $this->field($postId, $name);
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * Botão criado com Fields::button(): toggle `{name}_ocultar` + campo `link`.
     * - "Ocultar botão" marcado → null (o view não renderiza, nem o padrão);
     * - link vazio → o botão padrão do Figma (`$default` = ['title' => 'Falar com um expert',
     *   'url' => '/contato']); sem padrão → null;
     * - só a URL preenchida → texto do padrão.
     * Retorna LinkData { title, url, target }.
     *
     *   'botao' => $this->link($id, 'hero_botao', ['title' => 'Falar com um expert', 'url' => '/contato']),
     */
    protected function link(int $postId, string $name, ?array $default = null): ?array
    {
        if ($this->field($postId, "{$name}_ocultar")) return null;

        return $this->resolveLink($this->field($postId, $name), $default);
    }

    /** Mesmo que link(), para um subcampo de repeater: $this->linkFrom($row, 'botao', [padrão]). */
    protected function linkFrom(?array $row, string $name, ?array $default = null): ?array
    {
        if (!empty($row["{$name}_ocultar"])) return null;

        return $this->resolveLink($row[$name] ?? null, $default);
    }

    private function resolveLink(mixed $acfLink, ?array $default): ?array
    {
        $url   = is_array($acfLink) ? trim((string) ($acfLink['url'] ?? '')) : '';
        $title = is_array($acfLink) ? trim((string) ($acfLink['title'] ?? '')) : '';

        if ($url === '') {
            if (!$default || empty($default['url'])) return null;
            $url    = (string) $default['url'];
            $title  = $title ?: (string) ($default['title'] ?? '');
            $target = (string) ($default['target'] ?? '');
        } else {
            $title  = $title ?: (string) ($default['title'] ?? $url);
            $target = (string) ($acfLink['target'] ?? '');
        }
        // caminho relativo do Figma ("/contato") vira URL do site
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) $url = home_url($url);

        // só protocolos seguros (http/https/mailto/tel…): javascript:/data: viram '' → sem botão.
        // O campo link do ACF não filtra a URL ao salvar
        $url = esc_url_raw($url);
        if ($url === '') return null;

        return ['title' => $title, 'url' => $url, 'target' => $target === '_blank' ? '_blank' : '_self'];
    }

    /** Retorna dados de uma imagem ACF (campo image com return_format: array) como array padronizado. */
    protected function image(mixed $acfImage): ?array
    {
        return Media::imageData($acfImage);
    }

    /**
     * Ícone de um campo criado com Fields::icon(). Regra:
     * - campo ainda não salvo neste post → ícone do Figma (o $default do iconField);
     * - removido pelo editor (salvo vazio) → null: a página fica sem ícone;
     * - com arquivo → o enviado pelo editor.
     * Retorna IconData: ImageData + `svg` (markup sanitizado, para embutir inline e
     * herdar a cor do texto) quando o arquivo é SVG; senão `svg` = null.
     */
    protected function icon(int $postId, string $name, ?string $default = null): ?array
    {
        return $this->media($postId, $name, $default, true);
    }

    /** Ícone a partir do valor bruto do campo — use em subcampos de repeater: $this->iconFrom($row['icone'] ?? null). Vazio → null. */
    protected function iconFrom(mixed $acfImage): ?array
    {
        return Media::iconData($acfImage);
    }

    /** Imagem de um campo criado com Fields::image() — mesma regra do icon(), retorna ImageData. */
    protected function picture(int $postId, string $name, ?string $default = null): ?array
    {
        return $this->media($postId, $name, $default, false);
    }

    /**
     * Arquivo do tema (asset do Figma) no formato ImageData/IconData — o fallback
     * enquanto o asset não foi importado para a biblioteca (Core\Support\Media).
     */
    protected function asset(string $path): ?array
    {
        $path = ltrim($path, '/');
        $file = get_template_directory() . '/' . $path;
        if (!is_file($file)) return null;

        $isSvg = str_ends_with(strtolower($path), '.svg');
        $size  = $isSvg ? false : wp_getimagesize($file);

        return [
            'id'     => 0,
            'src'    => get_template_directory_uri() . '/' . $path,
            'alt'    => '',
            'width'  => $size ? $size[0] : null,
            'height' => $size ? $size[1] : null,
            'sizes'  => [],
            'svg'    => $isSvg ? Media::inlineSvg($file) : null,
        ];
    }

    private function media(int $postId, string $name, ?string $default, bool $icon): ?array
    {
        $value = $this->field($postId, $name);

        // nunca salvo neste post → o padrão do Figma: o anexo importado para a biblioteca
        // (o mesmo que o editor vê no campo) ou, antes da importação, o arquivo do tema
        if (($value === null || $value === false) && $default && (!$postId || !metadata_exists('post', $postId, $name))) {
            $seeded = Media::find($default);
            if ($seeded && function_exists('acf_get_attachment')) {
                $attachment = acf_get_attachment($seeded);
                return $icon ? $this->iconFrom($attachment) : $this->image($attachment);
            }
            $asset = $this->asset($default);
            if ($asset && !$icon) unset($asset['svg']);
            return $asset;
        }

        if ($icon) return $this->iconFrom($value);
        return $this->image($value);
    }
}
