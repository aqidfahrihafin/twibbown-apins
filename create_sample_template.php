<?php
// Create a sample template image with a transparent circle
$width = 500;
$height = 500;

// Create a true color image
$image = imagecreatetruecolor($width, $height);

// Enable alpha blending
imagealphablending($image, true);
imagesavealpha($image, true);

// Allocate colors
$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
$white = imagecolorallocate($image, 255, 255, 255);
$blue = imagecolorallocate($image, 0, 122, 255);
$red = imagecolorallocate($image, 255, 0, 0);

// Fill with transparent background
imagefill($image, 0, 0, $transparent);

// Draw a border
imagerectangle($image, 0, 0, $width-1, $height-1, $blue);

// Draw a circle with border
imagearc($image, $width/2, $height/2, $width-20, $height-20, 0, 360, $red);
imagearc($image, $width/2, $height/2, $width-40, $height-40, 0, 360, $red);

// Draw text
$text = "TWIBBON TEMPLATE";
$font = 5;
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;
imagestring($image, $font, $x, $y, $text, $red);

// Save the image
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

imagepng($image, 'uploads/sample_template.png');
imagedestroy($image);

echo "Sample template created successfully!";
?>