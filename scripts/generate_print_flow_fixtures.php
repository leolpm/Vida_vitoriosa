<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! extension_loaded('gd')) {
    throw new RuntimeException('Execute este gerador via CLI com a extensão GD habilitada.');
}

$target = dirname(__DIR__).'/database/seeders/assets/print-flow';

if (! is_dir($target) && ! mkdir($target, 0777, true) && ! is_dir($target)) {
    throw new RuntimeException("Não foi possível criar {$target}.");
}

$fixtures = [
    ['portrait.jpg', 720, 960, 'jpeg', [13, 71, 161], [80, 184, 224]],
    ['landscape.jpg', 1200, 700, 'jpeg', [157, 91, 26], [242, 183, 74]],
    ['square.png', 800, 800, 'png', [28, 109, 76], [134, 204, 151]],
    ['panorama.webp', 1200, 600, 'webp', [92, 44, 145], [217, 137, 210]],
];

foreach ($fixtures as [$name, $width, $height, $format, $start, $end]) {
    $image = imagecreatetruecolor($width, $height);

    for ($y = 0; $y < $height; $y++) {
        $ratio = $height === 1 ? 0 : $y / ($height - 1);
        $color = imagecolorallocate(
            $image,
            (int) round($start[0] + (($end[0] - $start[0]) * $ratio)),
            (int) round($start[1] + (($end[1] - $start[1]) * $ratio)),
            (int) round($start[2] + (($end[2] - $start[2]) * $ratio)),
        );
        imageline($image, 0, $y, $width, $y, $color);
    }

    $white = imagecolorallocatealpha($image, 255, 255, 255, 25);
    $dark = imagecolorallocatealpha($image, 7, 20, 38, 35);
    imagefilledellipse($image, (int) ($width * .24), (int) ($height * .26), (int) ($width * .42), (int) ($height * .32), $white);
    imagefilledellipse($image, (int) ($width * .78), (int) ($height * .72), (int) ($width * .5), (int) ($height * .4), $dark);
    imagestring($image, 5, 24, 24, 'IMAGEM SINTETICA DE TESTE', imagecolorallocate($image, 255, 255, 255));
    imagestring($image, 4, 24, 48, strtoupper(pathinfo($name, PATHINFO_FILENAME))." {$width}x{$height}", imagecolorallocate($image, 244, 247, 250));

    $path = $target.'/'.$name;
    $saved = match ($format) {
        'jpeg' => imagejpeg($image, $path, 86),
        'png' => imagepng($image, $path, 6),
        'webp' => function_exists('imagewebp') && imagewebp($image, $path, 84),
        default => false,
    };
    imagedestroy($image);

    if (! $saved) {
        throw new RuntimeException("Não foi possível gerar {$path}.");
    }
}

echo "Fixtures do fluxo de impressão geradas em {$target}.".PHP_EOL;
