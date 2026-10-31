<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Support\Media;

/**
 * Registro dos campos ACF do tema. Todos os campos ficam em
 * upsites/customizer-register/inc/acf_fields.php — um bloco por página, uma
 * Fields::section() por seção.
 */
final class Fields
{
    /** Slug dos componentes compartilhados: viram aba (clone) no grupo de cada página que os usa. */
    private const SHARED = 'shared';

    /** "Slug" das Opções do Tema (página de opções do ACF, não é módulo). */
    public const OPTIONS = 'opcoes';

    /** @var array<string, list<array{order: int|array, key: string, label: string, location: array, fields: array}>> seções por página */
    private static array $pending = [];

    /**
     * UMA seção da página = uma aba lateral no grupo de campos da página.
     *
     * Todas as seções de um slug com a mesma location viram um único grupo
     * (`group_{slug}`, título = nome da página) com abas à esquerda, na ordem de
     * `$order`. Cada aba começa com o toggle "Ocultar seção" (`{section}_ocultar`),
     * desmarcado por padrão; marcado, os demais campos da aba somem do editor (lógica
     * condicional do ACF; os valores salvos são mantidos). No controller, leia com
     * $this->section() (seção oculta → null).
     *
     * Componente compartilhado (`$page = 'shared'`, location com uma regra OR por
     * página que o usa): também vira aba no grupo de cada uma dessas páginas, via
     * campo clone do ACF — os campos são os mesmos (mesmos names), então o valor é
     * salvo por página como nos demais. `$order` pode ser um array por página
     * (`['home' => 7, 'verticais' => 5]`); página fora do array → no fim.
     *
     * @param string      $page     slug do módulo (pasta em template-pages/), ex: 'home'
     * @param string      $section  section-name em snake_case, ex: 'about_us'
     * @param string      $label    nome da aba no admin, ex: 'Sobre nós'
     * @param array       $fields   campos (keys field_{slug}_{section}_*, names {section}_*)
     * @param int|array   $order    posição da seção na página (ordena as abas)
     * @param array|null  $location location ACF; padrão: page_template == fw:{slug}
     *                              (home: também a página inicial)
     */
    public static function section(string $page, string $section, string $label, array $fields, int|array $order = 0, ?array $location = null): void
    {
        if (!function_exists('acf_add_local_field_group')) return;

        $key    = self::snake($page) . '_' . $section;
        $toggle = "field_{$key}_ocultar";

        // Campos só aparecem com a seção visível (toggle desmarcado). A regra entra
        // em AND em cada grupo OR que o campo já tiver; subcampos somem com o pai.
        $visible = ['field' => $toggle, 'operator' => '!=', 'value' => '1'];
        $fields  = array_map(static function (array $field) use ($visible): array {
            $field = self::defaultRows($field);
            $logic = $field['conditional_logic'] ?? [];
            $field['conditional_logic'] = $logic
                ? array_map(static fn(array $and): array => [...$and, $visible], $logic)
                : [[$visible]];
            return $field;
        }, $fields);

        self::$pending[$page][] = [
            'order'    => $order,
            'key'      => $key,
            'label'    => $label,
            'location' => $location ?? self::pageLocation($page),
            'fields'   => array_merge([[
                'key'           => $toggle,
                'label'         => __('Ocultar seção', 'upsites'),
                'name'          => "{$section}_ocultar",
                'type'          => 'true_false',
                'instructions'  => __('Marque para ocultar esta seção nesta página.', 'upsites'),
                'ui'            => 1,
                'ui_on_text'    => __('Sim', 'upsites'),
                'ui_off_text'   => __('Não', 'upsites'),
                'default_value' => 0,
            ]], $fields),
        ];

        // As abas de uma página só viram grupo depois que todas as seções foram declaradas.
        if (!did_action('acf/init') || doing_action('acf/init')) {
            if (!has_action('acf/init', [self::class, 'flush'])) add_action('acf/init', [self::class, 'flush'], 99);
        } else {
            self::flush();
        }
    }

    /**
     * UMA aba da página Configuração do Tema (configurações globais: rodapé, idiomas,
     * redes sociais…). Mesmo padrão das páginas — grupo `group_opcoes` com abas à
     * esquerda, campos com default_value/Fields::image/default_rows, "apagou, some" —
     * mas sem o toggle "Ocultar seção". Keys `field_opcoes_{section}_{nome}`, names
     * `{section}_{nome}`. Leitura: ThemeOptions::text('{name}', padrão) / ThemeOptions::value('{name}').
     */
    public static function option(string $section, string $label, array $fields, int $order = 0): void
    {
        if (!function_exists('acf_add_local_field_group')) return;

        self::$pending[self::OPTIONS][] = [
            'order'    => $order,
            'key'      => self::OPTIONS . "_{$section}",
            'label'    => $label,
            'location' => self::pageLocation(self::OPTIONS),
            'fields'   => array_map([self::class, 'defaultRows'], $fields),
        ];

        if (!did_action('acf/init') || doing_action('acf/init')) {
            if (!has_action('acf/init', [self::class, 'flush'])) add_action('acf/init', [self::class, 'flush'], 99);
        } else {
            self::flush();
        }
    }

    /**
     * Registra um grupo por página (seções com a mesma location), cada seção numa aba
     * lateral, e encaixa os componentes compartilhados como abas nas páginas que os usam.
     */
    public static function flush(): void
    {
        $shared = self::$pending[self::SHARED] ?? [];
        unset(self::$pending[self::SHARED]);

        // 1. grupos das páginas: [key => page, title, location, rules (json), items]
        $groups = [];
        foreach (self::$pending as $page => $sections) {
            $default = wp_json_encode(self::pageLocation($page));
            foreach ($sections as $i => $s) {
                $loc = wp_json_encode($s['location']);
                $gk  = $loc === $default ? 'group_' . self::snake($page) : "group_{$page}@{$loc}";
                $groups[$gk] ??= [
                    'page'     => $page,
                    'location' => $s['location'],
                    'rules'    => array_map('wp_json_encode', $s['location']),
                    'items'    => [],
                    'first'    => $s['key'],
                ];
                $groups[$gk]['items'][] = ['order' => (int) (is_array($s['order']) ? min($s['order'] ?: [0]) : $s['order']), 'seq' => $i, 'tab' => $s, 'fields' => $s['fields']];
            }
        }

        // 2. compartilhados: aba + clone em cada página cuja regra de location bate
        foreach ($shared as $i => $s) {
            $source    = "group_{$s['key']}";
            $unmatched = [];
            foreach ($s['location'] as $rule) {
                $json = wp_json_encode($rule);
                $host = null;
                foreach ($groups as $gk => $g) {
                    if (in_array($json, $g['rules'], true)) { $host = $gk; break; }
                }
                if ($host === null) { $unmatched[] = $rule; continue; }

                $page  = $groups[$host]['page'];
                $order = is_array($s['order']) ? ($s['order'][$page] ?? PHP_INT_MAX) : $s['order'];
                $base  = 'field_' . self::snake($page) . "_{$s['key']}";
                // a mesma página pode casar com mais de uma regra — uma aba só
                if (in_array($base, array_column($groups[$host]['items'], 'clone'), true)) continue;
                $groups[$host]['items'][] = [
                    'order'  => $order,
                    'seq'    => 1000 + $i,
                    'tab'    => ['key' => "{$base}", 'label' => $s['label']],
                    'clone'  => $base,
                    'fields' => [[
                        'key'          => "{$base}_clone",
                        'label'        => $s['label'],
                        'name'         => self::snake($page) . "_{$s['key']}",
                        'type'         => 'clone',
                        'clone'        => [$source],
                        'display'      => 'seamless',
                        'layout'       => 'block',
                        'prefix_label' => 0,
                        'prefix_name'  => 0,
                    ]],
                ];
            }

            // grupo de origem: só aparece sozinho onde nenhuma página o recebeu como aba
            acf_add_local_field_group([
                'key'        => $source,
                'title'      => self::pageName(self::SHARED) . " — {$s['label']}",
                'menu_order' => is_array($s['order']) ? 99 : $s['order'],
                'location'   => $unmatched ?: [[['param' => 'post_type', 'operator' => '==', 'value' => 'fw_shared_source']]],
                'fields'     => $s['fields'],
            ]);
        }

        // 3. registra os grupos das páginas, abas na ordem
        foreach ($groups as $gk => $g) {
            usort($g['items'], static fn(array $a, array $b): int => [$a['order'], $a['seq']] <=> [$b['order'], $b['seq']]);

            $fields = [];
            foreach ($g['items'] as $item) {
                $fields[] = [
                    'key'       => "{$item['tab']['key']}_tab",
                    'label'     => $item['tab']['label'],
                    'name'      => '',
                    'type'      => 'tab',
                    'placement' => 'left',
                    'endpoint'  => 0,
                ];
                array_push($fields, ...$item['fields']);
            }

            acf_add_local_field_group([
                'key'        => str_contains($gk, '@') ? "group_{$g['first']}" : $gk,
                'title'      => self::pageName($g['page']),
                'menu_order' => min(array_column($g['items'], 'order')),
                'location'   => $g['location'],
                'fields'     => $fields,
            ]);
        }
        self::$pending = [];
    }

    /**
     * Campos de um CPT (registrado em register_cpts.php): `group_{slug}_{cpt}`,
     * location post_type == {cpt}. Campos field_{slug}_{cpt}_{nome} / name {cpt}_{nome}.
     * $group = opções extras do grupo ACF (ex.: ['position' => 'side']).
     */
    public static function postType(string $page, string $postType, string $label, array $fields, int $order = 0, array $group = []): void
    {
        self::group($page, $postType, $label, $fields, $order, 'post_type', $group);
    }

    /** Campos dos termos de uma taxonomia (register_taxes.php) — mesmo formato do postType(). */
    public static function taxonomy(string $page, string $taxonomy, string $label, array $fields, int $order = 0, array $group = []): void
    {
        self::group($page, $taxonomy, $label, $fields, $order, 'taxonomy', $group);
    }

    /**
     * BOTÃO = toggle "Ocultar botão" + campo `link` (URL, texto e nova aba). Espalhe
     * no array de campos (também em sub_fields de repeater):
     *
     *   ...Fields::button(['key' => 'field_home_hero_botao', 'label' => 'Botão', 'name' => 'hero_botao',
     *       'instructions' => 'Botão de ação abaixo do texto. Vazio, exibe o padrão "Falar com um expert".']),
     *
     * Gera `{name}_ocultar` (true_false, desmarcado) logo antes do link; marcado, o
     * campo do link some do editor e o botão não aparece no site (nem o padrão).
     * No controller: $this->link($id, '{name}', [padrão]) / $this->linkFrom($row, '{name}', [padrão]).
     *
     * @return array{0: array, 1: array} [toggle, link]
     */
    public static function button(array $link): array
    {
        $toggle  = $link['key'] . '_ocultar';
        $visible = ['field' => $toggle, 'operator' => '!=', 'value' => '1'];
        $logic   = $link['conditional_logic'] ?? []; // o toggle herda a condição original do botão
        $link['conditional_logic'] = $logic
            ? array_map(static fn(array $and): array => [...$and, $visible], $logic)
            : [[$visible]];

        return [
            [
                'key'           => $toggle,
                // mbstring é recomendada, não exigida pelo WP (que não tem polyfill do mb_strtolower)
                /* translators: %s: nome do botão em minúsculas (ex.: "botão") */
                'label'         => sprintf(__('Ocultar %s', 'upsites'), (function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower')($link['label'] ?? __('botão', 'upsites'))),
                'name'          => $link['name'] . '_ocultar',
                'type'          => 'true_false',
                'instructions'  => __('Marque para não exibir este botão.', 'upsites'),
                'ui'            => 1,
                'ui_on_text'    => __('Sim', 'upsites'),
                'ui_off_text'   => __('Não', 'upsites'),
                'default_value' => 0,
                'wrapper'       => $link['wrapper'] ?? [],
            ] + ($logic ? ['conditional_logic' => $logic] : []),
            $link + ['type' => 'link', 'return_format' => 'array'],
        ];
    }

    /**
     * Configuração de um campo de ÍCONE (imagem opcional, SVG de preferência).
     * Some ao array do campo:
     *   ['key' => …, 'label' => 'Ícone', 'name' => …, 'instructions' => …] + Fields::icon($asset)
     *
     * $default = caminho, relativo ao tema, do ícone do Figma
     * (ex.: 'template-parts/includes/home/services/assets/icon-globe.svg'). Ele é
     * importado para a biblioteca de mídia (no admin) e vira o valor padrão: o editor
     * vê o ícone no campo e pode trocar ou remover — removido, a página fica sem ícone.
     * No controller: $this->icon($id, '{name}', $default) ou $this->iconFrom($row['…']).
     */
    public static function icon(?string $default = null, ?string $alt = null): array
    {
        return self::media($default, 'svg,png,webp', $alt) + ['preview_size' => 'thumbnail'];
    }

    /**
     * Configuração de um campo de IMAGEM (foto, ilustração), com a imagem do Figma
     * como valor padrão — mesma regra do icon(). No controller:
     * $this->picture($id, '{name}', $default).
     */
    public static function image(?string $default = null, ?string $alt = null): array
    {
        return self::media($default, 'jpg,jpeg,png,webp,avif,gif', $alt) + ['preview_size' => 'medium'];
    }

    private static function group(string $page, string $object, string $label, array $fields, int $order, string $param, array $group): void
    {
        if (!function_exists('acf_add_local_field_group')) return;

        acf_add_local_field_group([
            'key'        => 'group_' . self::snake($page) . '_' . self::snake($object),
            'title'      => $label,
            'menu_order' => $order,
            'location'   => [[['param' => $param, 'operator' => '==', 'value' => $object]]],
            'fields'     => $fields,
        ] + $group);
    }

    /**
     * Repeater com `'default_rows' => [['titulo' => '…', 'imagem' => 'template-parts/…/assets/x.webp'], …]`
     * (subcampos pelo name): em post que nunca salvou o repeater, o painel já abre com
     * essas linhas (os itens do Figma) — o ACF não tem default_value para repeater.
     * Imagem/ícone na linha = caminho do asset no tema (importado para a biblioteca).
     * Depois de salvo vale o que o editor deixou, inclusive lista vazia.
     */
    private static function defaultRows(array $field): array
    {
        if (($field['type'] ?? '') !== 'repeater' || empty($field['default_rows'])) return $field;

        $rows = $field['default_rows'];
        unset($field['default_rows']);

        add_filter("acf/load_value/key={$field['key']}", static function ($value, $postId, array $repeater) use ($rows) {
            if (!empty($value)) return $value;
            // Opções do Tema (post_id 'options'): nunca salvo = option inexistente.
            if (in_array($postId, ['options', 'option'], true)) {
                if (get_option("options_{$repeater['name']}", null) !== null) return $value;
            } elseif (!is_numeric($postId) || metadata_exists('post', (int) $postId, $repeater['name'])) {
                return $value;
            }

            $byName = array_column($repeater['sub_fields'] ?? [], null, 'name');
            return array_map(static function (array $row) use ($byName): array {
                $out = [];
                foreach ($row as $name => $val) {
                    $sub = $byName[$name] ?? null;
                    if (!$sub) continue;
                    if (in_array($sub['type'], ['image', 'file'], true) && is_string($val)) $val = Media::seed($val);
                    $out[$sub['key']] = $val;
                }
                return $out;
            }, $rows);
        }, 10, 3);

        return $field;
    }

    /** $alt = texto alternativo do Figma, gravado no anexo importado (o editor pode trocar na Mídia). */
    private static function media(?string $default, string $mimes, ?string $alt = null): array
    {
        $field = ['type' => 'image', 'return_format' => 'array', 'library' => 'all', 'mime_types' => $mimes];
        $id    = $default ? Media::seed($default, $alt) : null;
        if ($id) $field['default_value'] = $id;
        return $field;
    }

    /** Página com o modelo do módulo; a home vale também para a página inicial (RouteResolver). */
    private static function pageLocation(string $page): array
    {
        if ($page === self::OPTIONS) return [[['param' => 'options_page', 'operator' => '==', 'value' => \Core\Admin\ThemeOptions::SLUG]]];

        $location = [[['param' => 'page_template', 'operator' => '==', 'value' => "fw:{$page}"]]];
        if ($page === 'home') $location[] = [['param' => 'page_type', 'operator' => '==', 'value' => 'front_page']];
        return $location;
    }

    private static function pageName(string $page): string
    {
        if ($page === self::OPTIONS) return __('Opções do Tema', 'upsites');

        return ModuleLoader::all()[$page]['attr']->name ?? ucwords(str_replace('-', ' ', $page));
    }

    private static function snake(string $s): string
    {
        return str_replace('-', '_', $s);
    }
}
