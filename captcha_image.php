<?php
session_start();
header('Content-Type: image/png');

$captcha_text = rand(1000, 9999); // Simple numeric captcha
$_SESSION['captcha_text'] = $captcha_text;

$image = imagecreatetruecolor(100, 38);
$bg_color = imagecolorallocate($image, 255, 255, 255);
$fg_color = imagecolorallocate($image, 0, 0, 0);

imagefill($image, 0, 0, $bg_color);
imagettftext($image, 20, 0, 10, 30, $fg_color, __DIR__ . '/arial.ttf', $captcha_text); // Use a TTF font here
imagepng($image);
imagedestroy($image);
?>