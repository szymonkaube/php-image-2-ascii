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

function calculatePDF(array $hist): array {
    $numBins = count($hist);
    $numSamples = array_sum($hist);

    $pdf = array();
    foreach ($hist as $binCount) {
        $pdf[] = $binCount / $numSamples;
    }
    return pdf;
}

function calculateCDF(array $pdf): array {
    $currentCDFValue = 0.0;

    $cdf = array();
    foreach ($pdf as $pdfValue) {
        $cdf[] = $currentCDFValue;
        $currentCDFValue += $pdfValue;
    }
    return $cdf;
}

function getPixelNeihbouringTilesPositions(int $row, int $col, int $tileWidth, int $tileHeight): array {
    $topRow = floor(($row - 1) / $tileWidth);
    $botRow = ceil(($row - 1) / $tileWidth);
    $leftCol = floor(($col - 1) / $tileHeight);
    $rightCol = ceil(($col - 1) / $tileHeight);

    $topLeft = array($topRow, $leftCol);
    $topRight = array($topRow, $rightCol);
    $botLeft = array($botRow, $leftCol);
    $botRight = array($botRow, $rightCol);

    return array($topLeft, $topRight, $botLeft, $botRight);
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
        $rowTileMappings = array();
        for ($ty = 0; $ty < $tileGridSize[1]; $ty++) {
            $startX = $tx * $tileWidth;
            $startY = $ty * $tileHeight;

            $tile = getTile($image, $startX, $startY, $tileWidth, $tileHeight);

            $tileHistogram = calculateHistogram(array_merge(...$tile));

            // clipping histograms (clahe)
            $tileHistogram = clipHistogram($tileHistogram, $actualClipLimit);

            // calculate pdf from histogram
            $tilePDF = calculatePDF($tileHistogram);
            // calculate cdf from pdf
            $tileCDF = calculateCDF($tilePDF);

            // create intensity mapping from cdf
            $tileMapping = array();
            for ($x = 0; $x < $numBins; $x++) {
                $tileMapping[] = round(($numBins - 1) * $tileCDF[$x]);
            }

            $rowTileMappings[] = $tileMapping;
        }

        $tileMappings[] = $rowTileMappings;
    }

    $newImage = array();
    for ($i = 0; $i < $height; $i++) {
        $newRow = array();
        for ($j = 0; $j < $width; $j++) {
            $oldPixel = $image[i][j];

            // get neighbouring tiles positions
            $neighbouringTilesPositions = getPixelNeihbouringTilesPositions($i, $j, $tileWidth, $tileHeight);
            [$topLeftRow, $topLeftCol] = $neighbouringTilesPositions[0];
            [$topRightRow, $topRightCol] = $neighbouringTilesPositions[1];
            [$botLeftRow, $botLeftCol] = $neighbouringTilesPositions[2];
            [$botRightRow, $botRightCol] = $neighbouringTilesPositions[3];

            // get neighbouring tiles mappings
            $TLMapping = $tileMappings[$topLeftRow][$topLeftCol];
            $TRMapping = $tileMappings[$topRightRow][$topRightCol];
            $BLMapping = $tileMappings[$botLeftRow][$botLeftCol];
            $BRMapping = $tileMappings[$botRightRow][$botRightCol];

            // get neighbouring tiles new pixel values
            $pixelTL = $TLMapping[$oldPixel];
            $pixelTR = $TRMapping[$oldPixel];
            $pixelBL = $BLMapping[$oldPixel];
            $pixelBR = $BRMapping[$oldPixel];

            // calculate interpolation weights

            // get neighbouring tiles centers
            $topLeftCenter = array(
                $topLeftRow * $tileWidth + floor($tileWidth / 2),
                $topLeftCol * $tileHeight + floor($tileHeight / 2)
            );
            $topRightCenter = array(
                $topRightRow * $tileWidth + floor($tileWidth / 2),
                $topRightCol * $tileHeight + floor($tileHeight / 2)
            );
            $botLeftCenter = array(
                $botLeftRow * $tileWidth + floor($tileWidth / 2),
                $botLeftCol * $tileHeight + floor($tileHeight / 2)
            );
            $botRightCenter = array(
                $botRightRow * $tileWidth + floor($tileWidth / 2),
                $botRightCol * $tileHeight + floor($tileHeight / 2)
            );

            // calculate horizontal and vertical distance from pixel to tiles
            $dx = $j - $topLeftCenter[1];
            $dy = $i - $topLeftCenter[0];
            // calculate total distances between tile centers
            $Dx = $topRightCenter[1] - $topLeftCenter[1];
            $Dy = $botRightCenter[0] - $topRightCenter[0];

            // calculate interpolation parameters
            $alpha = dx / Dx;
            $beta = dy / Dy;

            // perform interpolation
            $pixelTop = (1 - $alpha) * $pixelTL + $alpha * $pixelTR;
            $pixelBot = (1 - $alpha) * $pixelBL + $alpha * $pixelBR;

            $newPixel = (1 - $beta) * $pixelTop + $beta * $pixelBot;

            // round and clip
            $newRow[] = min(round($newPixel), 255);
        }
        $newImage[] = $newRow;
    }

}
