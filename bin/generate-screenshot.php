<?php
/**
 * Gera o screenshot.png do tema (880x660px).
 * Acesse via browser: /wp-content/themes/UpWork/bin/generate-screenshot.php?key=upwork-setup-2026
 */
if (($_GET['key'] ?? '') !== 'upwork-setup-2026') {
    http_response_code(403);
    exit('Forbidden');
}

if (!extension_loaded('gd')) {
    exit('Extensão GD não disponível. Instale php-gd.');
}

$width  = 880;
$height = 660;
$out    = __DIR__ . '/../screenshot.png';

$img = imagecreatetruecolor($width, $height);

$bg      = imagecolorallocate($img, 15,  23, 42);
$accent  = imagecolorallocate($img, 99, 102, 241);
$white   = imagecolorallocate($img, 255, 255, 255);
$gray    = imagecolorallocate($img, 148, 163, 184);
$surface = imagecolorallocate($img, 30,  41,  59);

// Fundo
imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $bg);

// Header bar
imagefilledrectangle($img, 0, 0, $width - 1, 60, $surface);

// Logo placeholder
imagefilledrectangle($img, 32, 18, 140, 42, $accent);

// Nav items
foreach ([600, 660, 720, 780] as $x) {
    imagefilledrectangle($img, $x, 24, $x + 50, 36, $gray);
}

// Hero
imagefilledrectangle($img, 40, 100, $width - 40, 320, $surface);
imagefilledrectangle($img, 80, 130, 480, 160, $accent);
imagefilledrectangle($img, 80, 175, 600, 195, $gray);
imagefilledrectangle($img, 80, 205, 540, 220, $gray);
imagefilledrectangle($img, 80, 255, 160, 295, $accent);

// Cards
$cardW = 230;
$gap   = 20;
$startX = 40;
$cardY = 350;

for ($i = 0; $i < 3; $i++) {
    $x1 = $startX + $i * ($cardW + $gap);
    $x2 = $x1 + $cardW;
    imagefilledrectangle($img, $x1, $cardY, $x2, $cardY + 220, $surface);
    imagefilledrectangle($img, $x1 + 16, $cardY + 16, $x1 + 48, $cardY + 48, $accent);
    imagefilledrectangle($img, $x1 + 16, $cardY + 68, $x2 - 16, $cardY + 82, $white);
    imagefilledrectangle($img, $x1 + 16, $cardY + 96, $x2 - 16, $cardY + 108, $gray);
    imagefilledrectangle($img, $x1 + 16, $cardY + 116, $x2 - 40, $cardY + 126, $gray);
}

// Watermark text (sem extensão TTF, usa fonte built-in)
$label = 'UpWork Framework';
imagestring($img, 5, $width - 190, $height - 24, $label, $gray);

imagepng($img, $out);
imagedestroy($img);

echo 'screenshot.png gerado em: ' . realpath($out);
