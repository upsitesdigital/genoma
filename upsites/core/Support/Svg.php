<?php
declare(strict_types=1);

namespace Core\Support;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * SVG no tema: libera o upload de SVG na biblioteca de mídia (ícones enviados pelo
 * cliente) e sanitiza todo SVG — no upload e antes de ser embutido inline na página.
 *
 * Lista do que é PERMITIDO (não do que é proibido): só elementos SVG de desenho
 * (ALLOWED_TAGS, no namespace do SVG) — <script>, <foreignObject>, HTML dentro do SVG,
 * animações SMIL (<animate>/<set> trocam href para javascript:) e qualquer tag
 * desconhecida saem. Comentários e processing instructions saem; CDATA vira texto.
 * Atributos: on* e data-* saem; com namespace, só xlink:href (por namespace, não pelo
 * prefixo) e xml:space/lang; links só internos (#id), ou data:image/(png|jpeg|gif|webp) em
 * <image>; url(...) só #id; javascript:/vbscript:/outros data: em qualquer atributo saem;
 * CSS com position fixed/absolute sai. <title>/<desc>/<style> só com texto.
 * Uploads: formulário, sideload, wp_upload_bits (XML-RPC) e todo anexo SVG criado.
 *
 * <style> é mantido mas ESCOPADO: o SVG ganha a classe `fw-svg-{hash}` e cada regra é
 * prefixada com ela — embutido na página, o CSS de um ícone não vaza para o resto
 * (exports do Illustrator/Figma usam `.cls-1` em todo arquivo). At-rules são removidas.
 */
final class Svg
{
    private const NS_SVG   = 'http://www.w3.org/2000/svg';
    private const NS_XLINK = 'http://www.w3.org/1999/xlink';
    private const NS_XML   = 'http://www.w3.org/XML/1998/namespace';

    private const ALLOWED_TAGS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'style', 'switch', 'a',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'image',
        'text', 'tspan', 'textpath',
        'lineargradient', 'radialgradient', 'stop', 'pattern', 'clippath', 'mask', 'marker',
        'filter', 'feblend', 'fecolormatrix', 'fecomponenttransfer', 'fecomposite', 'feconvolvematrix',
        'fediffuselighting', 'fedisplacementmap', 'fedistantlight', 'fedropshadow', 'feflood',
        'fefunca', 'fefuncb', 'fefuncg', 'fefuncr', 'fegaussianblur', 'femerge', 'femergenode',
        'femorphology', 'feoffset', 'fepointlight', 'fespecularlighting', 'fespotlight', 'fetile', 'feturbulence',
    ];

    public static function register(): void
    {
        // tipo SVG e permissão de upload: com o Safe SVG ativo são DELE (libera o MIME só durante o
        // upload, para os papéis de "Upload roles", e respeita os filtros dele); sem ele, do tema.
        // A sanitização abaixo vale nos dois casos.
        if (!self::safeSvgActive()) {
            add_filter('upload_mimes', [self::class, 'allowMime']);
            add_filter('wp_check_filetype_and_ext', [self::class, 'fixFiletype'], 10, 4);
        }
        // upload pelo formulário (multipart) e por sideload — POST /wp/v2/media com o arquivo
        // no corpo, media_sideload_image(), importadores — usam filtros diferentes
        add_filter('wp_handle_upload_prefilter', [self::class, 'sanitizeUpload']);
        add_filter('wp_handle_sideload_prefilter', [self::class, 'sanitizeUpload']);
        // wp_upload_bits() (XML-RPC wp.uploadFile/metaWeblog.newMediaObject, importadores) não passa
        // pelos prefilters acima: SVG inválido é recusado aqui, e todo anexo SVG criado é
        // reescrito sanitizado (rede de segurança para qualquer caminho que crie o anexo)
        add_filter('wp_upload_bits', [self::class, 'checkUploadBits']);
        add_action('add_attachment', [self::class, 'sanitizeAttachment']);

        // Safe SVG (plugin, se instalado): também remove referências externas (<image href="https://…">,
        // url() de fora) — por padrão ele mantém, e elas rastreiam o visitante
        add_filter('safe_svg_remove_remote_references', '__return_true');
    }

    /** Plugin Safe SVG ativo (os plugins carregam antes do tema). */
    private static function safeSvgActive(): bool
    {
        return class_exists('SafeSvg\safe_svg');
    }

    /** Sem o Safe SVG: envia SVG quem já envia arquivos (autor, editor, admin). */
    private static function canUpload(): bool
    {
        return current_user_can('upload_files');
    }

    /**
     * wp_upload_bits: o filtro não deixa trocar o conteúdo, e o arquivo é gravado antes de existir
     * anexo — então só passa SVG que JÁ está sanitizado (igual à saída do sanitize). Qualquer outro
     * é recusado (string = erro para o WP): envie pela biblioteca de mídia, que sanitiza.
     */
    public static function checkUploadBits(mixed $args): mixed
    {
        if (!is_array($args) || strtolower(pathinfo((string) ($args['name'] ?? ''), PATHINFO_EXTENSION)) !== 'svg') return $args;
        $bits = trim((string) ($args['bits'] ?? ''));
        return $bits !== '' && self::sanitize($bits) === $bits
            ? $args
            : __('SVG recusado: envie pela biblioteca de mídia do painel, que valida o arquivo.', 'upsites');
    }

    /** Anexo SVG recém-criado: o arquivo em disco é reescrito sanitizado (ou apagado, se inválido). */
    public static function sanitizeAttachment(int $postId): void
    {
        $file = get_attached_file($postId);
        if (!$file || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg' || !is_file($file)) return;

        $raw   = (string) file_get_contents($file);
        $clean = self::sanitize($raw);
        if ($clean === null) {
            wp_delete_attachment($postId, true); // não fica SVG não sanitizado servido em uploads/
            return;
        }
        if ($clean !== $raw) file_put_contents($file, $clean);
    }

    /** SVG liberado só para quem pode enviar SVG (canUpload). */
    public static function allowMime(array $mimes): array
    {
        if (self::canUpload()) {
            $mimes['svg'] = 'image/svg+xml';
        }
        return $mimes;
    }

    /** O WP não reconhece o tipo real de .svg — confirma pelo conteúdo. */
    public static function fixFiletype(array $data, string $file, string $filename, mixed $mimes): array
    {
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'svg') return $data;
        if (!self::canUpload()) return $data;

        $head = (string) file_get_contents($file, false, null, 0, 2048);
        if (stripos($head, '<svg') === false) return $data;

        return ['ext' => 'svg', 'type' => 'image/svg+xml', 'proper_filename' => $data['proper_filename'] ?? false];
    }

    /** Reescreve o arquivo enviado já sanitizado; SVG inválido é recusado. */
    public static function sanitizeUpload(array $file): array
    {
        $isSvg = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) === 'svg'
            || stripos((string) ($file['type'] ?? ''), 'svg') !== false;
        if (!$isSvg || empty($file['tmp_name']) || !is_file($file['tmp_name'])) return $file;

        $clean = self::sanitize((string) file_get_contents($file['tmp_name']));
        if ($clean === null) {
            $file['error'] = __('Este SVG não pôde ser validado. Exporte novamente (ex.: no Figma, "Copiar como SVG") e tente de novo.', 'upsites');
            return $file;
        }

        file_put_contents($file['tmp_name'], $clean);
        return $file;
    }

    /** SVG sanitizado, ou null se o conteúdo não for um SVG válido. */
    public static function sanitize(string $svg): ?string
    {
        if (trim($svg) === '' || stripos($svg, '<svg') === false) return null;
        if (!class_exists(\DOMDocument::class)) return null;

        // sem DTD/entidades (XXE e "billion laughs")
        $svg = preg_replace('/<!DOCTYPE\s[^\[>]*(\[[\s\S]*?\])?\s*>/i', '', $svg) ?? '';
        $svg = preg_replace('/<!ENTITY[\s\S]*?>/i', '', $svg) ?? '';

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->documentElement;
        if (!$loaded || !$root || strtolower($root->localName) !== 'svg') return null;
        if ($root->namespaceURI !== self::NS_SVG && $root->namespaceURI !== null) return null;
        // raiz com prefixo (`<s:svg xmlns:s=…>`): embutido no HTML não abre o contexto SVG — o
        // navegador vê um elemento HTML desconhecido e os filhos viram HTML (<image> → <img>)
        if ($root->prefix !== '' || $root->localName !== 'svg') return null;

        self::clean($root);
        // raiz sem transform (atributo ou style=""): o ícone embutido não pode crescer por cima da página
        $root->removeAttribute('transform');
        if ($root->hasAttribute('style')) {
            $style = self::withoutTransform($root->getAttribute('style'));
            if ($style === '') $root->removeAttribute('style');
            else $root->setAttribute('style', $style);
        }
        self::unscopeIds($root);    // idempotente: tira o prefixo de id de uma passada anterior
        self::scopeStyles($root);
        self::filterClasses($root);
        self::scopeIds($root);

        return $dom->saveXML($root) ?: null;
    }

    /** Prefixo dos ids de uma passada anterior (`fwi{hash}-`). */
    private const ID_PREFIX_RE = '/fwi[a-f0-9]{8}-/';

    private static function unscopeIds(\DOMElement $root): void
    {
        foreach (self::elements($root) as $el) {
            foreach (iterator_to_array($el->attributes) as $attr) {
                $v = (string) $attr->nodeValue;
                if (preg_match(self::ID_PREFIX_RE, $v)) $attr->value = (string) preg_replace(self::ID_PREFIX_RE, '', $v);
            }
            if (strtolower($el->localName) === 'style') self::setText($el, (string) preg_replace(self::ID_PREFIX_RE, '', $el->textContent));
        }
    }

    /**
     * Todo `id` ganha o prefixo `fwi{hash}-` (e as referências `#id`, `url(#id)`, aria-*by e os
     * seletores `#id` do <style> acompanham). Embutido inline, um id do SVG não pode colidir com
     * um da página — ex.: `wp-script-module-data-@wordpress/interactivity`, que o runtime da
     * Interactivity API lê por getElementById (o SVG viria antes do script real e trocaria o
     * state/config de todos os stores). Id com caractere fora de [A-Za-z0-9_.-] sai.
     */
    private static function scopeIds(\DOMElement $root): void
    {
        // ids inválidos saem ANTES do hash (senão o prefixo mudaria a cada passada)
        foreach (self::elements($root) as $el) {
            if ($el->hasAttribute('id') && !preg_match('/^[A-Za-z_][\w.-]*$/', $el->getAttribute('id'))) $el->removeAttribute('id');
        }

        $prefix = 'fwi' . substr(md5((string) $root->ownerDocument->saveXML($root)), 0, 8) . '-';
        // url(#x), url('#x'), URL( #x ) — com ou sem aspas, qualquer caixa
        $refs   = static fn(string $v): string => (string) preg_replace('/url\(\s*([\'"]?)\s*#/i', 'url($1#' . $prefix, $v);

        foreach (self::elements($root) as $el) {
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = strtolower($attr->localName);
                $v    = (string) $attr->nodeValue;
                if ($name === 'id') {
                    $attr->value = $prefix . $v;
                } elseif ($name === 'class') {
                    // classes também ganham o prefixo (menos o escopo fw-svg-*): nunca batem com
                    // uma classe do tema (`fixed`, `hidden`…), mesmo que o SVG declare `.fixed{}`
                    $attr->value = implode(' ', array_map(
                        static fn($c) => preg_match('/^fw-svg-[a-f0-9]{8}$/', $c) ? $c : $prefix . $c,
                        preg_split('/\s+/', trim($v), -1, PREG_SPLIT_NO_EMPTY) ?: []
                    ));
                } elseif ($name === 'href' && str_starts_with(ltrim($v), '#')) {
                    $attr->value = '#' . $prefix . substr(ltrim($v), 1);
                } elseif (in_array($name, ['aria-labelledby', 'aria-describedby'], true)) {
                    $attr->value = implode(' ', array_map(static fn($t) => $prefix . $t, preg_split('/\s+/', trim($v), -1, PREG_SPLIT_NO_EMPTY) ?: []));
                } elseif (stripos($v, 'url(') !== false) {
                    $attr->value = $refs($v);
                }
            }
            if (strtolower($el->localName) === 'style') {
                // seletor `#id`/`.classe` → com prefixo (menos o escopo .fw-svg-*); no corpo só
                // url(#id) (cor `#fff` fica)
                $css = (string) preg_replace_callback('/([^{}]*)\{([^{}]*)\}/', static fn(array $m): string =>
                    (string) preg_replace('/([.#])(?!fw-svg-[a-f0-9]{8}\b)([A-Za-z_][\w-]*)/', '$1' . $prefix . '$2', $m[1]) . '{' . $refs($m[2]) . '}', $el->textContent);
                self::setText($el, $css);
            }
        }
    }

    /**
     * `class` só com as classes usadas pelo próprio <style> do SVG (exports do Illustrator/Figma:
     * `.cls-1`) + o escopo `fw-svg-*`. Classe do tema (`fixed inset-0 z-50`…) num SVG enviado
     * cobriria a página com o CSS do site.
     */
    private static function filterClasses(\DOMElement $root): void
    {
        $css = '';
        foreach ($root->getElementsByTagName('style') as $style) $css .= $style->textContent;
        preg_match_all('/\.([A-Za-z_][\w-]*)/', $css, $m);
        $allowed = array_flip($m[1]);

        foreach (self::elements($root) as $el) {
            if (!$el->hasAttribute('class')) continue;
            $keep = array_filter(preg_split('/\s+/', $el->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) ?: [], static fn($c) => isset($allowed[$c]));
            if ($keep) $el->setAttribute('class', implode(' ', $keep));
            else $el->removeAttribute('class');
        }
    }

    /** @return \DOMElement[] o elemento e todos os descendentes */
    private static function elements(\DOMElement $root): array
    {
        return array_merge([$root], iterator_to_array($root->getElementsByTagName('*')));
    }

    private static function setText(\DOMElement $el, string $text): void
    {
        while ($el->firstChild) $el->removeChild($el->firstChild);
        $el->appendChild($el->ownerDocument->createTextNode($text));
    }

    /** Prefixa o CSS dos <style> com uma classe própria do SVG (escopo), ou remove o <style> vazio. */
    private static function scopeStyles(\DOMElement $root): void
    {
        $styles = iterator_to_array($root->getElementsByTagName('style'));
        if (!$styles) return;

        // idempotente: o SVG é sanitizado no upload e de novo ao ser embutido — tira o escopo anterior
        foreach ($styles as $style) {
            $plain = preg_replace('/\.fw-svg-[a-f0-9]{8}(?=\s*[,{])/', 'svg', $style->textContent) ?? ''; // regra da raiz
            $plain = preg_replace('/\.fw-svg-[a-f0-9]{8}\s+/', '', $plain) ?? '';
            while ($style->firstChild) $style->removeChild($style->firstChild);
            $style->appendChild($style->ownerDocument->createTextNode($plain));
        }
        $root->setAttribute('class', trim((string) preg_replace('/\bfw-svg-[a-f0-9]{8}\b/', '', $root->getAttribute('class'))));
        if ($root->getAttribute('class') === '') $root->removeAttribute('class');

        // hash do CSS já normalizado (mesmo resultado em toda passada)
        $scope = 'fw-svg-' . substr(md5(implode('', array_map(static fn($s) => self::scopeCss($s->textContent, '_'), $styles))), 0, 8);
        foreach ($styles as $style) {
            $css = self::scopeCss($style->textContent, $scope);
            if (trim($css) === '') {
                $style->parentNode?->removeChild($style);
                continue;
            }
            while ($style->firstChild) $style->removeChild($style->firstChild);
            $style->appendChild($style->ownerDocument->createTextNode($css));
        }

        $classes = trim(($root->getAttribute('class') ?: '') . ' ' . $scope);
        $root->setAttribute('class', $classes);
    }

    /**
     * Propriedades CSS aceitas num ícone — só desenho/texto do próprio SVG. Nada de position,
     * contain, display de layout, content, filtros de página etc.
     */
    private const CSS_PROPS = [
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'opacity', 'color',
        'stop-color', 'stop-opacity', 'clip-rule', 'visibility', 'display', 'mix-blend-mode', 'isolation',
        'paint-order', 'vector-effect', 'shape-rendering', 'font-family', 'font-size', 'font-weight',
        'font-style', 'letter-spacing', 'text-anchor', 'dominant-baseline', 'transform', 'transform-origin',
        'transform-box', 'flood-color', 'flood-opacity', 'lighting-color',
    ];

    /**
     * Declarações `prop:valor;…` → só as válidas, remontadas (`prop:valor;prop:valor`), ou ''.
     * Valor: palavras, números, unidades, cores `#hex`, e só as funções url(#id), rgb/rgba/hsl/hsla
     * e transform (translate/rotate/scale/matrix/skew) com números — sem aspas, `\`, `/`, `:`,
     * chaves/colchetes, `!important` nem parêntese fora dessas funções. Como a saída é REMONTADA
     * só com o que passou, nada que o navegador leia de outro jeito sobra (`(}`, comentários…).
     */
    public static function cleanDeclarations(string $decls): string
    {
        $decls = (string) preg_replace('#/\*.*?\*/#s', '', $decls);
        $fn    = '(?:url\(\s*#[A-Za-z_][\w.-]*\s*\)|(?:rgba?|hsla?)\([\d.,%\s]+\)|(?:translate|translateX|translateY|rotate|scale|scaleX|scaleY|matrix|skewX|skewY)\((?:[-\d.,\s]|deg|rad|turn|px|%)*\))';
        $value = '/^(?:[#\w.%+\-\s,]|' . $fn . ')+$/i';

        $out = [];
        foreach (explode(';', $decls) as $decl) {
            if (trim($decl) === '') continue;
            $pos = strpos($decl, ':');
            if ($pos === false) continue;
            $prop = strtolower(trim(substr($decl, 0, $pos)));
            $val  = trim(substr($decl, $pos + 1));
            if (!in_array($prop, self::CSS_PROPS, true) || $val === '' || !preg_match($value, $val)) continue;
            $out[] = $prop . ':' . preg_replace('/\s+/', ' ', $val);
        }
        return implode(';', $out);
    }

    /**
     * `a, b { … }` → `.scope a, .scope b { … }`; `svg x` → `.scope x`. Gramática FECHADA: só
     * seletores de tipo/classe/id combinados por espaço ou `>` (sem `~`, `+`, pseudo, atributo,
     * at-rules) e declarações de cleanDeclarations(). Regra fora disso some; o CSS de saída é
     * montado do zero — o navegador não tem como ler diferente do que foi validado.
     */
    public static function scopeCss(string $css, string $scope): string
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        if (self::unsafeCss($css, true)) return '';

        $compound = '(?:(?:[A-Za-z][\w-]*|\*)(?:[.#][A-Za-z_][\w-]*)*|(?:[.#][A-Za-z_][\w-]*)+)';
        $selector = '/^' . $compound . '(?:\s*>\s*' . $compound . '|\s+' . $compound . ')*$/';

        $out = '';
        preg_match_all('/([^{}]*)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
        foreach ($rules as [, $sel, $body]) {
            $decls = self::cleanDeclarations($body);
            if ($decls === '') continue;

            $scoped = [];
            $onRoot = false;
            foreach (explode(',', $sel) as $s) {
                $s = trim((string) preg_replace('/\s+/', ' ', $s));
                $s = trim((string) preg_replace('/^(svg|:root|html|body)\b\s*/i', '', $s));
                if ($s === '') { $onRoot = true; continue; }
                if (str_starts_with($s, '>')) $s = trim(substr($s, 1)); // `svg > x` = filho da raiz
                if (!preg_match($selector, $s)) continue;
                $scoped[] = '.' . $scope . ' ' . $s;
            }
            if ($scoped) $out .= implode(',', array_unique($scoped)) . '{' . $decls . '}';
            // regra da própria raiz: sem transform — `svg{transform:scale(80)}` faria o ícone
            // embutido cobrir a página (o mesmo overlay falso do position fixed)
            $rootDecls = $onRoot ? self::withoutTransform($decls) : '';
            if ($rootDecls !== '') $out .= '.' . $scope . '{' . $rootDecls . '}';
        }

        return $out;
    }

    /** Declarações (já limpas por cleanDeclarations) sem transform/transform-origin. */
    private static function withoutTransform(string $decls): string
    {
        $kept = array_filter(explode(';', $decls), static fn($d) => trim($d) !== '' && !preg_match('/^\s*transform(-origin)?\s*:/i', $d));
        return implode(';', $kept);
    }

    /**
     * CSS que pode buscar algo fora do arquivo ou fugir do escopo: url() que não seja #id,
     * @import, image-set()/image()/cross-fade()/element(), expression(), escapes (\) e — no
     * <style>, onde há seletores — aspas.
     */
    private static function unsafeCss(string $css, bool $stylesheet): bool
    {
        if (str_contains($css, '\\')) return true;
        if ($stylesheet && (str_contains($css, '"') || str_contains($css, "'"))) return true;
        $flat = strtolower((string) preg_replace('/[\s\x00-\x1f]+/', '', $css));
        if (preg_match('/@import|image-set\(|image\(|cross-fade\(|element\(|expression\(|javascript:/', $flat)) return true;
        // embutido inline, um ícone com position fixed/absolute cobriria a página (overlay falso);
        // var() sai porque esconde o valor (`--p:fixed;position:var(--p)`)
        if (preg_match('/position:(fixed|absolute|sticky)|var\(/', $flat)) return true;
        return preg_match('/url\((?![\'"]?#)/', $flat) === 1;
    }

    private static function clean(\DOMElement $el): void
    {
        foreach (iterator_to_array($el->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                // só elementos SVG da lista (no namespace do SVG): HTML dentro do <svg> (<p>, <xmp>,
                // <img>…) quebraria o contexto SVG quando embutido inline (mXSS) — sai inteiro
                $tag = strtolower($child->localName);
                $ns  = $child->namespaceURI;
                // <style> só sem CSS que busque algo de fora ou fuja do escopo (unsafeCss)
                $externalCss = $tag === 'style' && self::unsafeCss($child->textContent, true);
                // namespace igual ao da raiz e sem prefixo: `<svg xmlns:s=…>` + `<s:path>` ou filho sem
                // namespace sob raiz SVG não seria o mesmo elemento no XML e no HTML
                $sameNs = $ns === $el->ownerDocument->documentElement->namespaceURI && $child->prefix === '';
                if (!in_array($tag, self::ALLOWED_TAGS, true) || !$sameNs || $externalCss) {
                    $el->removeChild($child);
                    continue;
                }
                // <title>/<desc> são pontos de integração HTML e <style> é texto: só texto dentro.
                // Vazios saem — o saveXML gera `<title/>`, que no HTML abre um <title> (RCDATA)
                // e engole o resto da página
                if (in_array($tag, ['title', 'desc', 'style'], true)) {
                    // `<STYLE>`/`<Title>`: o XML diferencia caixa (getElementsByTagName('style') não
                    // acharia → CSS sem escopo), o HTML não — vira o nome em minúsculas
                    if ($child->localName !== $tag) {
                        $renamed = $child->ownerDocument->createElementNS(self::NS_SVG, $tag);
                        while ($child->firstChild) $renamed->appendChild($child->firstChild);
                        $el->replaceChild($renamed, $child);
                        $child = $renamed;
                    }
                    foreach (iterator_to_array($child->childNodes) as $inner) {
                        if (!$inner instanceof \DOMText) $child->removeChild($inner);
                    }
                    if ($tag !== 'style' && trim($child->textContent) === '') {
                        $el->removeChild($child);
                        continue;
                    }
                }
                self::clean($child);
            } elseif ($child instanceof \DOMCdataSection) {
                // CDATA (CSS do Illustrator) vira texto comum — escapado na saída
                $el->replaceChild($el->ownerDocument->createTextNode($child->data), $child);
            } elseif (!$child instanceof \DOMText) {
                // comentários e processing instructions: sem uso num ícone e vetor de mXSS
                $el->removeChild($child);
            }
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $ns    = $attr->namespaceURI;
            $name  = strtolower($attr->localName);
            $value = preg_replace('/[\s\x00-\x1f]+/', '', strtolower($attr->nodeValue ?? '')) ?? '';

            // atributo com namespace: só xlink:href (qualquer prefixo) e xml:space/xml:lang
            // prefixo NÃO declarado (`xlink:href` sem xmlns:xlink): o libxml guarda sem namespace e com
            // ":" no nome — passaria como atributo comum, e o HTML o lê como xlink:href (link externo)
            $allowedNs = ($ns === null && !str_contains($attr->nodeName, ':'))
                || ($ns === self::NS_XLINK && $name === 'href')
                || ($ns === self::NS_XML && in_array($name, ['space', 'lang'], true));

            $isEvent     = str_starts_with($name, 'on');
            // data-*: embutido inline, o SVG passa pelo processamento da Interactivity API —
            // data-wp-* injetaria diretivas (disparar actions do tema)
            $isData      = str_starts_with($name, 'data-')
                // comportamento de HTML sem uso num ícone (foco, edição, popover, rastreio)
                || in_array($name, ['ping', 'autofocus', 'popover', 'popovertarget', 'contenteditable', 'tabindex', 'target', 'formaction', 'action'], true)
                // name/srcset/usemap/srcdoc/is: só valem em HTML (DOM clobbering, imagem externa)
                || in_array($name, ['name', 'srcset', 'usemap', 'srcdoc', 'is'], true);
            $isLink      = in_array($name, ['href', 'src', 'action', 'formaction'], true);
            // data: só imagem raster e só em <image>; <use>/<a> só referência interna (#id)
            $rasterData  = preg_match('#^data:image/(png|jpe?g|gif|webp)[;,]#', $value) === 1;
            $dataOk      = $rasterData && strtolower($el->localName) === 'image';
            $badScheme   = str_contains($value, 'javascript:') || str_contains($value, 'vbscript:')
                || (str_starts_with($value, 'data:') && !$dataOk);
            $externalRef = $isLink && $value !== '' && !str_starts_with($value, '#') && !$dataOk;
            // url(...) em fill/filter/mask/style: só referência interna (#id)
            $externalUrl = preg_match('/url\((?![\'"]?#)/', $value) === 1;
            // nome com caixa diferente em atributo que importa (`CLASS`, `Style`, `ID`, `HREF`): o
            // XML trata como outro atributo e o filtro não veria; o HTML trataria como o mesmo
            $badCase     = in_array($name, ['class', 'id', 'style', 'href', 'src'], true) && $attr->localName !== $name;

            if (!$allowedNs || $isEvent || $isData || $badCase || ($isLink && $externalRef) || $badScheme || $externalUrl) {
                $el->removeAttributeNode($attr);
                continue;
            }

            // style="…": só declarações válidas (mesma gramática do <style>), remontadas
            if ($name === 'style') {
                $decls = self::cleanDeclarations((string) $attr->nodeValue);
                if ($decls === '') $el->removeAttributeNode($attr);
                else $attr->value = $decls;
            }
        }
    }
}
