<?php
function gdImageToPixels(GdImage $image): array {
    $width = imagesx($image);
    $height = imagesy($image);

    $pixels = [];
    $ascii = [];

    for ($y = 0; $y < $height; $y++) {
        $row = [];

        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $grayPixelValue = 0.2989 * $r + 0.5870 * $g + 0.1140 * $b;
            $grayPixelValue = (int) $grayPixelValue;

            $row[] = $grayPixelValue;
        }
        $pixels[] = $row;
    }

    return $pixels;
}
function sumRadius(array $array, int $i, int $j, int $radius): int {
    $sum = 0;
    $rows = count($array);
    $cols = count($array[0]);

    for ($x = $i - $radius; $x <= $i + $radius; $x++) {
        for ($y = $j - $radius; $y <= $j + $radius; $y++) {
            if ($x >= 0 && $x < $rows && $y >= 0 && $y < $cols) {
                $sum += $array[$x][$y];
            }
        }
    }
    return $sum;
}

function getAveragePixels(array $pixels, int $radius): array {
    $width = count($pixels[0]);
    $height = count($pixels);

    $avgPixels = array();
    for ($i = $radius; $i < $height - $radius; $i = $i + 2*$radius - 1) {
        $avgRow = array();

        for ($j = $radius; $j < $width - $radius; $j = $j + 2*$radius - 1) {
            $numPixels = pow(2 * $radius + 1, 2);
            $avgPixel = round(sumRadius($pixels, $i, $j, $radius) / $numPixels);

            $avgRow[] = $avgPixel;
        }
        $avgPixels[] = $avgRow;
    }

    return $avgPixels;
}

function pixelToAscii(int $pixelValue): string {
    # $asciiSymbols = " .:-=+*#%@";
    $asciiSymbols = "@%#*+=-:. ";
    # $asciiSymbols = ' `.-_:\'/=+\\*^~!#&%$@WQMB';
    # $asciiSymbols = strrev($asciiSymbols);

    $index = (int) floor(($pixelValue / 256) * (strlen($asciiSymbols) - 1));
    return $asciiSymbols[$index];
}

function pixelsToAscii(array $pixels): array {
    $width = count($pixels[0]);
    $height = count($pixels);

    $asciiSymbols = array();
    for ($i = 0; $i < $height; $i++) {
        $asciiRow = array();

        for ($j = 0; $j < $width; $j++) {
            $asciiRow[] = pixelToAscii($pixels[$i][$j]);
        }
        $asciiSymbols[] = $asciiRow;
    }
    return $asciiSymbols;
}
?>
