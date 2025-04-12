<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ASCII-2-Image</title>
    <style>
        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 5px;
        }
    </style>
</head>

<body>

    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
        <input name="imgFile" type="file"></br>

        </br><input type="submit" value="Submit file">
    </form>

    <?php
        require "functions.php";

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $uploadsDir = "uploads/";

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["imgFile"])) {
            $imgFile = $_FILES["imgFile"];

            $imgFilename = $imgFile["name"];
            $targetPath = $uploadsDir . $imgFilename;

            if (move_uploaded_file($imgFile["tmp_name"], $targetPath)) {
                $imgPath = $targetPath;
                $imageInfo = getimagesize($imgPath);
                $mimeType = $imageInfo['mime'];

                switch ($mimeType) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($imgPath);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($imgPath);
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($imgPath);
                        break;
                    default:
                        throw new Exception("Unsupported image format");
                }

                $width = imagesx($image);
                $height = imagesy($image);

                $outputCharWidth = 500;
                $aspectRatio = $height / $width;
                $charAspectRatioCorrection = 0.5;

                $newWidth = $outputCharWidth;
                $newHeight = (int)round($newWidth * $aspectRatio * $charAspectRatioCorrection);
                $newHeight = max(1, $newHeight);

                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                $pixels = gdImageToPixels($newImage);

                # $avgPixels = getAveragePixels($pixels, 10);

                $asciiGrid = pixelsToAscii($pixels);

                foreach ($asciiGrid as $row) {
                    $asciiRow = implode("", $row);
                    echo "$asciiRow<br>";
                }
            }

        }
    ?>

</body>

</html>
