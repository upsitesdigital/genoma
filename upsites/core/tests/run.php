<?php
/**
 * Testes das funções puras do framework (sem WordPress): sanitização/escopo de SVG e
 * mapeamento papel → capability do #[Auth]. Rodar: npm run test:php
 * Para um teste novo: test('descrição', function () { check(<condição>, '<detalhe>'); });
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;

// os arquivos do tema saem cedo sem ABSPATH (guarda contra acesso direto pela web)
define('ABSPATH', __DIR__ . '/');

// funções do WP usadas pelas funções puras testadas (equivalentes mínimos, sem WordPress)
if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr')) {
    function esc_attr(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_url')) {
    function esc_url(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

require __DIR__ . '/../Support/Svg.php';
require __DIR__ . '/../Support/Html.php';
require __DIR__ . '/../Framework/Attributes/Auth.php';
require __DIR__ . '/../Framework/Attributes/Cache.php';
require __DIR__ . '/../Support/Asset.php';
require __DIR__ . '/../Framework/View.php';

use Core\Support\Svg;
use Core\Framework\Attributes\Auth;
use Core\Framework\Attributes\Cache;
use Core\Framework\View;

$failures = 0;
$total = 0;

function check(bool $ok, string $detail = ''): void
{
    if (!$ok) throw new RuntimeException($detail ?: 'condição falsa');
}

function test(string $name, callable $fn): void
{
    global $failures, $total;
    $total++;
    try {
        $fn();
        echo "  ok    {$name}\n";
    } catch (Throwable $e) {
        $failures++;
        echo "  FALHA {$name}: {$e->getMessage()}\n";
    }
}

echo "Svg\n";

test('remove <script>, on* e javascript:', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" onload="x()"><script>alert(1)</script><a href="javascript:alert(1)"><path d="M0 0"/></a></svg>');
    check($out !== null, 'retornou null');
    check(stripos($out, '<script') === false, 'script ficou');
    check(stripos($out, 'onload') === false, 'onload ficou');
    check(stripos($out, 'javascript:') === false, 'javascript: ficou');
    check(str_contains($out, '<path'), 'path sumiu');
});

test('remove animações SMIL (animate/set)', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><a><set attributeName="href" to="javascript:alert(1)"/><animate attributeName="href" values="javascript:alert(1)"/></a></svg>');
    check($out !== null && stripos($out, '<set') === false && stripos($out, '<animate') === false, (string) $out);
});

test('xlink:href com outro prefixo (xmlns:f) também é checado', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><a f:href="javascript:alert(1)"><rect width="9" height="9"/></a><use f:href="#ok"/></svg>');
    check($out !== null && stripos($out, 'javascript') === false, (string) $out);
    check(preg_match('/f:href="#fwi[a-f0-9]{8}-ok"/', $out) === 1, 'href interno sumiu: ' . $out);
});

test('HTML dentro do SVG e comentários saem (mXSS)', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><p/><xmp><!--</xmp><img src=x onerror=alert(1)>--></xmp><!-- <img src=x onerror=alert(2)> --><path d="M0 0"/></svg>');
    check($out !== null, 'retornou null');
    foreach (['<p/', '<xmp', '<img', '<!--', 'onerror'] as $bad) check(stripos($out, $bad) === false, "{$bad} ficou: {$out}");
    check(str_contains($out, '<path'), 'path sumiu');
});

test('CDATA vira texto escapado; tags desconhecidas e de outro namespace saem', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml" xmlns:i="http://x.test/ns"><style><![CDATA[.a{fill:red}]]></style><h:img src="x" onerror="alert(1)"/><i:data i:foo="1"/><blink/><path class="a" i:bar="1" d="M0 0"/></svg>');
    check($out !== null && stripos($out, 'CDATA') === false && str_contains($out, '{fill:red}'), (string) $out);
    foreach (['<h:img', 'onerror', '<i:data', 'i:bar', '<blink'] as $bad) check(stripos($out, $bad) === false, "{$bad} ficou: {$out}");
});

test('url(...) externo em atributo sai; url(#id) fica', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(https://x.test/a#g)" width="1"/><rect fill="url(#g)" width="2"/></svg>');
    check($out !== null && !str_contains($out, 'x.test') && preg_match('/url\(#fwi[a-f0-9]{8}-g\)/', $out) === 1, (string) $out);
});

test('ids ganham prefixo próprio (sem colidir com a página) e as referências acompanham', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><linearGradient id="g"/><path id="p" d="M0 0"/></defs><style>#p{fill:#fff} .c{fill:url(#g)}</style><use xlink:href="#p"/><rect class="c" fill="url(#g)" width="1"/></svg>');
    check($out !== null && preg_match('/id="(fwi[a-f0-9]{8}-)g"/', $out, $m) === 1, (string) $out);
    $p = $m[1];
    foreach (["id=\"{$p}p\"", "xlink:href=\"#{$p}p\"", "fill=\"url(#{$p}g)\"", "#{$p}p{fill:#fff}", "fill:url(#{$p}g)"] as $want) check(str_contains($out, $want), "falta {$want}: {$out}");
    check(Svg::sanitize($out) === $out, 'não idempotente');
});

test('id que imita o do runtime da Interactivity API (@, /) sai', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" id="wp-script-module-data-@wordpress/interactivity"><text>{"config":{}}</text></svg>');
    check($out !== null && !str_contains($out, 'id='), (string) $out);
});

test('<STYLE> em maiúsculas é escopado; ~/+ e var() saem; class só as do próprio <style>', function () {
    $up = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><STYLE>body{opacity:.1}</STYLE><path d="M0 0"/></svg>');
    check($up !== null && !str_contains($up, 'STYLE') && preg_match('/<style>\.fw-svg-[a-f0-9]{8}\{opacity:\.1\}<\/style>/', $up) === 1, (string) $up);
    $sib = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>svg ~ *{display:none} svg + p{x:y} .a{fill:red}</style><path class="a fixed inset-0" d="M0 0"/></svg>');
    check($sib !== null && !str_contains($sib, '~') && !str_contains($sib, '+') && preg_match('/class="fwi[a-f0-9]{8}-a"/', $sib) === 1, (string) $sib);
    $var = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" class="fixed inset-0" style="--p:fixed;position:var(--p)"><path d="M0 0"/></svg>');
    check($var !== null && !str_contains($var, 'var(') && !str_contains($var, 'fixed'), (string) $var);
});

test('CSS com string/escape não foge do escopo nem busca recurso externo', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:"}"} body{display:none}</style><style>.b{background:url(\68ttps://x.test/x)}</style><path style="background:image-set(&quot;https://x.test/y&quot; 1x)" d="M0 0"/><rect style="fill:red" width="1"/></svg>');
    check($out !== null, 'retornou null');
    foreach (['body{', 'display:none', '\68', 'image-set', 'x.test'] as $bad) check(stripos($out, $bad) === false, "{$bad} ficou: {$out}");
    check(str_contains($out, 'style="fill:red"'), 'style legítimo sumiu: ' . $out);
});

test('raiz em outro namespace é recusada', function () {
    check(Svg::sanitize('<svg xmlns="http://www.w3.org/1999/xhtml"><img src="x" onerror="alert(1)"/></svg>') === null);
});

test('remove DOCTYPE/entidades (XXE)', function () {
    $out = Svg::sanitize('<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>');
    check($out === null || !str_contains($out, 'root:'), (string) $out);
});

test('conteúdo que não é SVG retorna null', function () {
    check(Svg::sanitize('') === null && Svg::sanitize('<html></html>') === null && Svg::sanitize('<svg><unclosed') === null);
});

test('CSS só pela gramática fechada: parêntese/colchete abertos, comentário em style="", CLASS e classe do tema não escapam', function () {
    $paren = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:(}x)}body p{color:red}</style><path class="a" d="M0 0"/></svg>');
    check($paren !== null && !str_contains($paren, '(') && preg_match('/<style>(\.fw-svg-[a-f0-9]{8} [^{},]+\{[^{}]*\})+<\/style>/', $paren) === 1, (string) $paren);
    $brk = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:[}x]}body span{contain:none}</style><path d="M0 0"/></svg>');
    check($brk !== null && !str_contains($brk, 'contain') && !str_contains($brk, '['), (string) $brk);
    $cmt = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" style="position:/**/fixed;inset:0;fill:red"><path d="M0 0"/></svg>');
    check($cmt !== null && str_contains($cmt, 'style="fill:red"'), (string) $cmt);
    $up = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" CLASS="fixed inset-0" ID="x"><path d="M0 0"/></svg>');
    check($up !== null && stripos($up, 'class=') === false && stripos($up, 'id=') === false, (string) $up);
    $decl = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>.fixed{fill:red}</style><path class="fixed" d="M0 0"/></svg>');
    check($decl !== null && !preg_match('/class="fixed"/', $decl) && preg_match('/class="fwi[a-f0-9]{8}-fixed"/', $decl) === 1, (string) $decl);
    $url = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/></defs><rect fill="url(\'#g\')" stroke="URL(#g)" width="1"/></svg>');
    check($url !== null && preg_match_all('/url\(\'?#fwi[a-f0-9]{8}-g\)?/i', $url) === 2, (string) $url);
    $bad = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><path id="a@b" d="M0 0"/><path id="ok" d="M1 1"/></svg>');
    check($bad !== null && Svg::sanitize($bad) === $bad, 'id inválido quebra a idempotência: ' . $bad);
});

test('<style> é escopado na classe do SVG', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>.cls-1{fill:red}@media (min-width:1px){.cls-1{fill:blue}}</style><path class="cls-1" d="M0 0"/></svg>');
    check($out !== null && preg_match('/class="(fw-svg-[a-f0-9]+)"/', $out, $m) === 1, 'sem classe fw-svg-*');
    check(preg_match('/\.' . $m[1] . ' \.fwi[a-f0-9]{8}-cls-1\{/', $out) === 1, 'regra não prefixada: ' . $out);
    check(stripos($out, '@media') === false, 'at-rule ficou');
});

test('<style> que busca algo de fora (@import, url(http…)) é removido', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(x.css);.a{fill:red}</style><style>.b{background:url(https://x.test/a.png)}</style><path d="M0 0"/></svg>');
    check($out !== null && stripos($out, '<style') === false, (string) $out);
});

test('sanitizar duas vezes dá o mesmo resultado (idempotente)', function () {
    $once = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" class="icon"><style>svg{color:red} .a,.b{fill:blue}</style><path class="a" d="M0 0"/></svg>');
    check($once !== null && Svg::sanitize($once) === $once, "1ª: {$once}\n2ª: " . Svg::sanitize((string) $once));
});

test('scopeCss: svg/:root viram o próprio escopo', function () {
    $css = Svg::scopeCss('svg{fill:red} :root{color:blue} .x,.y{opacity:.5}', '.s');
    check(str_contains($css, '.s{') || str_contains($css, '.s {'), $css);
    check(str_contains($css, '.s .x') && str_contains($css, '.s .y'), $css);
});

test('<title>/<desc> só com texto; vazios saem (sem <title/> que abre RCDATA no HTML)', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><desc><title/></desc><title>Ícone<g/></title><path d="M0 0"/></svg>');
    check($out !== null, 'retornou null');
    check(!str_contains($out, '<title/>') && !str_contains($out, '<desc'), $out);
    check(str_contains($out, '<title>Ícone</title>'), 'título com texto sumiu: ' . $out);
});

test('data: só imagem raster em <image>; <use>/<a> com data: saem', function () {
    $png = 'data:image/png;base64,iVBORw0KGgo=';
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use href="data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=#x"/><a href="data:image/png;base64,AA=="><rect width="1" height="1"/></a><image href="' . $png . '"/><image href="data:image/svg+xml;base64,AA=="/></svg>');
    check($out !== null, 'retornou null');
    check(substr_count($out, 'data:') === 1 && str_contains($out, $png), $out);
});

test('data-* (diretivas da Interactivity API) e position fixed/absolute saem', function () {
    $out = Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" data-wp-interactive="upwork/layout-header" data-wp-on--click="actions.toggle" style="position:fixed;inset:0;z-index:9999"><style>path{position:absolute}</style><path d="M0 0" style="fill:red"/></svg>');
    check($out !== null, 'retornou null');
    check(!str_contains($out, 'data-wp'), 'data-wp ficou: ' . $out);
    check(stripos($out, 'position') === false, 'position ficou: ' . $out);
    check(str_contains($out, 'fill:red'), 'style legítimo sumiu: ' . $out);
});

test('raiz com prefixo (<s:svg>) é recusada; filho sem namespace/prefixado e name/srcset saem', function () {
    check(Svg::sanitize('<s:svg xmlns:s="http://www.w3.org/2000/svg"><!--<svg--><image name="getElementById" srcset="https://evil.test/p.png 1x"/></s:svg>') === null, 'raiz prefixada passou');
    check(Svg::sanitize('<SVG xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></SVG>') === null, 'raiz <SVG>');
    $out = (string) Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:path d="M1 1"/><image xmlns="" width="1"/><path name="x" srcset="a 1x" usemap="#m" d="M0 0"/></svg>');
    foreach (['s:path', '<image', 'name=', 'srcset', 'usemap'] as $bad) check(!str_contains($out, $bad), "{$bad} ficou: {$out}");
    check(str_contains($out, '<path d="M0 0"/>'), $out);
    check(Svg::sanitize('<svg><path d="M0 0"/></svg>') === '<svg><path d="M0 0"/></svg>', 'SVG sem xmlns deixou de funcionar');
});

test('prefixo não declarado (xlink:href sem xmlns:xlink) sai; transform só fora da raiz', function () {
    $out = (string) Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><image xlink:href="https://evil.test/t.png" width="1"/><a xlink:href="https://evil.test/"><text>x</text></a></svg>');
    check(!str_contains($out, 'evil.test') && !str_contains($out, 'xlink:'), $out);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" transform="scale(50)" style="transform:scale(50);display:block"><style>svg{transform:scale(80);fill:red} g{transform:rotate(45deg)}</style><g><path d="M0 0"/></g></svg>';
    $out = (string) Svg::sanitize($svg);
    check(!preg_match('/<svg[^>]*transform/', $out), "atributo/style transform na raiz: {$out}");
    check(!preg_match('/\.fw-svg-[a-f0-9]{8}\{[^}]*transform/', $out), "regra da raiz com transform: {$out}");
    check(str_contains($out, 'fill:red') && str_contains($out, 'rotate(45deg)') && str_contains($out, 'display:block'), "tirou demais: {$out}");
    check(Svg::sanitize($out) === $out, 'não idempotente');
});

echo "View (LCP)\n";

test('lcpImage: maior imagem da 1ª seção, ignorando SVG, ícone pequeno e seção oculta', function () {
    $data = [
        'hero' => null, // seção oculta
        'banner' => [
            'logo'   => ['src' => 'https://x.test/logo.svg', 'width' => 2000, 'height' => 800],
            'selo'   => ['src' => 'https://x.test/selo.png', 'width' => 120, 'height' => 120],
            'fundo'  => ['src' => 'https://x.test/fundo.webp', 'width' => 1920, 'height' => 1080],
            'cards'  => [['img' => ['src' => 'https://x.test/card.webp', 'width' => 800, 'height' => 600]]],
        ],
        'depois' => ['img' => ['src' => 'https://x.test/maior-mas-abaixo.webp', 'width' => 4000, 'height' => 4000]],
    ];
    check(View::lcpImage($data) === 'https://x.test/fundo.webp', (string) View::lcpImage($data));
    check(View::lcpImage(['hero' => ['titulo' => 'só texto']]) === null, 'seção sem imagem deveria dar null');
    check(View::lcpImage(null) === null);
});

echo "View (h1) / Html (img)\n";

test('ensureH1: página sem <h1> ganha um sr-only no início do <main>; com <h1>, nada muda', function () {
    $out = View::ensureH1('<main class="x"><section><h2>A</h2></section></main>', 'Sobre & nós');
    check(str_starts_with($out, '<main class="x"><h1 class="sr-only">Sobre &amp; nós</h1><section>'), $out);
    $same = '<main><h1 class="t">Hero</h1></main>';
    check(View::ensureH1($same, 'X') === $same, 'mexeu numa página que já tinha h1');
    check(View::ensureH1('<section></section>', 'T') === '<h1 class="sr-only">T</h1><section></section>', 'sem <main>');
    $cifrao = View::ensureH1('<main class="x"><p>a</p></main>', 'A partir de R$0,99 \\1');
    check($cifrao === '<main class="x"><h1 class="sr-only">A partir de R$0,99 \\1</h1><p>a</p></main>', $cifrao);
});

test('img: anexo vai pelo wp_get_attachment_image (tamanho + attrs); asset/SVG → <img> simples', function () {
    $GLOBALS['fw_wp_img'] = null;
    if (!function_exists('wp_attachment_is_image')) {
        function wp_attachment_is_image(int $id): bool { return $id === 7; }
        function wp_get_attachment_image(int $id, string $size, bool $icon, array $attr): string { $GLOBALS['fw_wp_img'] = [$id, $size, $attr]; return '<img wp>'; }
    }
    $img = ['id' => 7, 'src' => 'https://x.test/a.jpg', 'alt' => 'Foto', 'width' => 1600, 'height' => 900];
    check(\Core\Support\Html::img($img, ['class' => 'w-full', 'loading' => false, 'title' => null, 'data-reveal' => true, 'on"x' => 'y'], 'full') === '<img wp>');
    [$id, $size, $attr] = $GLOBALS['fw_wp_img'];
    check($id === 7 && $size === 'full', "{$id} {$size}");
    check($attr === ['alt' => 'Foto', 'loading' => false, 'decoding' => 'async', 'class' => 'w-full', 'data-reveal' => '', 'onx' => 'y'], json_encode($attr));
    check(\Core\Support\Html::img(['id' => 0, 'src' => 'https://x.test/i.png', 'width' => 24, 'height' => 24]) === '<img src="https://x.test/i.png" width="24" height="24" alt="" loading="lazy" decoding="async">', 'asset do tema');
    check(str_starts_with(\Core\Support\Html::img(['id' => 7, 'src' => 'https://x.test/i.svg']), '<img src='), 'SVG não vai pelo WP');
});

echo "Auth / Cache\n";

test('papel vira a capability mínima', function () {
    check((new Auth('administrator'))->capability() === 'manage_options');
    check((new Auth('editor'))->capability() === 'edit_others_posts');
    check((new Auth('subscriber'))->capability() === 'read');
});

test('capability é mantida; vazio = qualquer logado', function () {
    check((new Auth('edit_posts'))->capability() === 'edit_posts');
    check((new Auth())->capability() === '');
});

test('Cache: ttl e vary padrão', function () {
    $c = new Cache();
    check($c->ttl === 300 && $c->vary === []);
});

echo "\n" . ($total - $failures) . "/{$total} ok\n";
exit($failures > 0 ? 1 : 0);
