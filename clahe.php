<?php

function getTile(array $array, int $x, int $y, int $width, int $height): array {
    $tile = [];

    for ($i = 0; $i < $width; $i++) {
        if (!isset($array[$i + $x])) break;

        for ($j = 0; $j < $height; $j++) {
            if (!isset($array[$i + $x][$j + $y])) break;

            $tile[$i][$j] = $array[$i + $x][$j + $y];
        }
    }
    return $tile;
}

function calculateHistogram(array $array, int $numBins = 256): array {
    $histogram = array_fill(0, $numBins, 0);
    $arraySize = count($array);
    for ($i = 0; $i < $arraySize; $i++) {
        $binIndex = $array[i];
        $histogram[$binIndex] += 1;
    }
    return $histogram;
}

function clipHistogram(array $hist, int $clipLimit): array {
    $numBins = count($hist);
    $excess = 0;
    // clip the values
    for ($i = 0; $i < $numBins; $i++) {
        if ($hist[$i] > $clipLimit) {
            $excess += $hist[i] - $clipLimit;
            $hist[i] = $clipLimit;
        }
    }

    // redistribute excess
    $redistributeAmount = floor($excess / $numBins);
    for ($i = 0; $i < $numBins; $i++) {
        $hist[i] += $redistributeAmount;
    }

    return $hist;
}

function clahe(array $image, array $tileGridSize = [8, 8], int $clipLimit = 40): array {
    $numBins = 256;

    $width = count($image[0]);
    $height = count($image);

    $tileWidth = round($width / $tileGridSize[0]);
    $tileHeight = round($height / $tileGridSize[1]);

    $numPixelsPerTile = $tileWidth * $tileHeight;
    $actualClipLimit = max(1, floor($clipLimit * $numPixelsPerTile / $numBins));

    $tileMappings = array();
    for ($tx = 0; $tx < $tileGridSize[0]; $tx++) {
        for ($ty = 0; $ty < $tileGridSize[1]; $ty++) {
            $startX = $tx * $tileWidth;
            $startY = $ty * $tileHeight;

            $tile = getTile($image, $startX, $startY, $tileWidth, $tileHeight);

            $tileHistogram = calculateHistogram(array_merge(...$tile));


        }
    }
}
