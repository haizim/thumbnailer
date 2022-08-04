<?php

try {
    $url = str_replace(" ", "%20", $_GET['u']);
    $width = $_GET['w'] ?? 500;
    $quality = $_GET['q'] ?? 5;
    
    if ($quality > 100) {
        errorImage('max. quality must 0-100', 30);
        die();
    }
    
    $data = file_get_contents($url);
    
    $im = imagecreatefromstring($data);
    if ($im !== false) {
        // header('Content-Type: image/png');
        header('Content-Type: image/jpeg');
        // imagepng($im, null, $quality, PNG_NO_FILTER);
        // imagejpeg($im, null, $quality);
        $im = imagescale($im, $width);
        imagejpeg($im);
        imagedestroy($im);
    }
    else {
        errorImage();
    }
} catch(Exception $e) {
    errorImage();
}

function errorImage($text = "failed to get image", $x = 50, $y = 125) {
    // https://www.php.net/manual/en/function.imagejpeg.php
    // Create a blank image and add some text
    $im = imagecreatetruecolor(258, 258);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 5, $x, $y, $text, $text_color);
    imagestring($im, 2, 85, 152, 'by: haizim.one', $text_color);

    // Set the content type header - in this case image/jpeg
    header('Content-Type: image/jpeg');

    // Output the image
    imagejpeg($im);

    // Free up memory
    imagedestroy($im);
}