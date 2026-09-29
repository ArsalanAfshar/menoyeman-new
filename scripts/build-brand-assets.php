<?php

/**
 * Build MenoyeMan brand assets from resources/brand/logo.svg.
 *
 * The logo SVG embeds a raster PNG (data URI) — this script extracts it and
 * generates the favicon set, PWA icons, Apple touch icon and the Open Graph
 * social image. The logo itself is never redrawn, recolored or distorted:
 * assets only scale it (aspect preserved) and place it on backgrounds.
 *
 * Usage: php scripts/build-brand-assets.php
 * Requires: ext-gd with PNG support.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$logoSvgPath = $root . '/resources/brand/logo.svg';
$outBrand = $root . '/resources/brand';
$outPublic = $root . '/public';

if (! extension_loaded('gd')) {
    fwrite(STDERR, "ext-gd is required.\n");
    exit(1);
}

// ---- 1. Extract the embedded PNG from the (sanitized) logo SVG -------------
$svg = file_get_contents($logoSvgPath);
if (! preg_match('/base64,([A-Za-z0-9+\/=]+)/', $svg, $m)) {
    fwrite(STDERR, "No embedded image found in logo.svg\n");
    exit(1);
}
$png = base64_decode($m[1], true);
if ($png === false) {
    fwrite(STDERR, "Embedded image is not valid base64.\n");
    exit(1);
}
$src = imagecreatefromstring($png);
if ($src === false) {
    fwrite(STDERR, "Embedded image is not a valid image.\n");
    exit(1);
}
$sw = imagesx($src);
$sh = imagesy($src);
imagealphablending($src, true);

// Trim fully transparent margins so icons use the full canvas.
$minX = $sw; $minY = $sh; $maxX = 0; $maxY = 0;
for ($y = 0; $y < $sh; $y++) {
    for ($x = 0; $x < $sw; $x++) {
        $alpha = (imagecolorat($src, $x, $y) >> 24) & 0x7F;
        if ($alpha < 126) { // opaque-ish pixels only
            if ($x < $minX) { $minX = $x; }
            if ($y < $minY) { $minY = $y; }
            if ($x > $maxX) { $maxX = $x; }
            if ($y > $maxY) { $maxY = $y; }
        }
    }
}
// Guard against degenerate trim
if ($maxX <= $minX || $maxY <= $minY) {
    $minX = 0; $minY = 0; $maxX = $sw - 1; $maxY = $sh - 1;
}
$cw = $maxX - $minX + 1;
$ch = $maxY - $minY + 1;

/** Scale the trimmed logo into a size box (aspect preserved). */
function logoOn(GdImage $src, int $minX, int $minY, int $cw, int $ch, int $size, int $pad, array $bg): GdImage
{
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, true);
    if ($bg === []) {
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size - 1, $size - 1, $transparent);
    } else {
        $color = imagecolorallocate($canvas, $bg[0], $bg[1], $bg[2]);
        imagefilledrectangle($canvas, 0, 0, $size - 1, $size - 1, $color);
    }
    $box = $size - 2 * $pad;
    $scale = min($box / $cw, $box / $ch);
    $dw = (int) round($cw * $scale);
    $dh = (int) round($ch * $scale);
    $dx = (int) round(($size - $dw) / 2);
    $dy = (int) round(($size - $dh) / 2);
    imagecopyresampled($canvas, $src, $dx, $dy, $minX, $minY, $dw, $dh, $cw, $ch);
    return $canvas;
}

function savePng(GdImage $img, string $path): void
{
    imagesavealpha($img, true);
    imagepng($img, $path, 9);
    echo "  wrote {$path}\n";
}

/**
 * Write a multi-size .ico containing PNG entries (Vista+ format).
 *
 * @param array<int,string> $pngPaths keyed by pixel size
 */
function writeIco(array $pngPaths, string $path): void
{
    ksort($pngPaths);
    $count = count($pngPaths);
    $header = pack('vvv', 0, 1, $count); // reserved, type=icon, count
    $entries = '';
    $blobs = '';
    $offset = 6 + 16 * $count;
    foreach ($pngPaths as $size => $pngPath) {
        $blob = file_get_contents($pngPath);
        $dim = $size >= 256 ? 0 : $size;
        $entries .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, strlen($blob), $offset);
        $blobs .= $blob;
        $offset += strlen($blob);
    }
    file_put_contents($path, $header . $entries . $blobs);
    echo "  wrote {$path}\n";
}

echo "Building brand assets from logo ({$sw}x{$sh}, trimmed {$cw}x{$ch})...\n";

// ---- 2. Icons ---------------------------------------------------------------
$favSizes = [16, 32, 48];
$icoSources = [];
foreach ($favSizes as $s) {
    $img = logoOn($src, $minX, $minY, $cw, $ch, $s, max(1, (int) round($s * 0.06)), []);
    $tmp = $outBrand . "/favicon-{$s}.png";
    savePng($img, $tmp);
    $icoSources[$s] = $tmp;
}
writeIco($icoSources, $outPublic . '/favicon.ico');

// Browser-native SVG favicon (logo.svg is already an SVG wrapper)
copy($logoSvgPath, $outPublic . '/favicon.svg');
echo "  wrote {$outPublic}/favicon.svg\n";

// Apple touch icon (opaque, rounded corners applied by iOS itself)
savePng(logoOn($src, $minX, $minY, $cw, $ch, 180, 14, [255, 247, 237]), $outPublic . '/apple-touch-icon.png');

// PWA icons (regular: transparent, maskable: safe-zone padding on warm white)
savePng(logoOn($src, $minX, $minY, $cw, $ch, 192, 16, []), $outPublic . '/icons/icon-192.png');
savePng(logoOn($src, $minX, $minY, $cw, $ch, 512, 40, []), $outPublic . '/icons/icon-512.png');
savePng(logoOn($src, $minX, $minY, $cw, $ch, 512, 96, [255, 247, 237]), $outPublic . '/icons/icon-512-maskable.png');

// ---- 3. Open Graph image 1200x630 ------------------------------------------
$og = imagecreatetruecolor(1200, 630);
imagealphablending($og, true);
// Warm off-white background with a soft orange glow (bottom-right)
$bg1 = imagecolorallocate($og, 255, 247, 237); // #FFF7ED
imagefilledrectangle($og, 0, 0, 1200, 630, $bg1);
for ($i = 0; $i < 60; $i++) {
    $t = $i / 59;
    $r = (int) (255 - 10 * $t);
    $g = (int) (247 - 60 * $t);
    $b = (int) (237 - 100 * $t);
    imagefilledellipse($og, 1050, 700, 700 + $i * 12, 500 + $i * 10, imagecolorallocate($og, $r, $g, $b));
}
// Logo scaled to ~62% of height, centered
$pad = 0;
$box = 380;
$scale = min($box / $cw, $box / $ch);
$dw = (int) round($cw * $scale);
$dh = (int) round($ch * $scale);
imagecopyresampled($og, $src, (int) ((1200 - $dw) / 2), (int) ((630 - $dh) / 2) - 30, $minX, $minY, $dw, $dh, $cw, $ch);
// Wordmark-free: keep the OG image clean (logo only).
savePng($og, $outBrand . '/og-image.png');
copy($outBrand . '/og-image.png', $outPublic . '/og-image.png');

echo "Done.\n";
