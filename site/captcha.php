<?PHP

session_start();
$captcha_num = rand(1000, 9999);
$_SESSION['code'] = $captcha_num;

$image = imagecreatetruecolor(70, 25);
$black = imagecolorallocate($image, 0, 0, 0);
$color = imagecolorallocate($image, 200, 100, 90); // red
//$white = imagecolorallocate($image, 255, 255, 255);
$lightgray = imagecolorallocate($image, 245, 245, 240);
 
imagefilledrectangle($image, 0, 0, 76, 41, $lightgray);
putenv('GDFONTPATH=' . realpath('.'));
imagettftext ($image, 18, 0, 8, 20, $color, "Arial-ItalicMT.ttf", $captcha_num);
 
header("Content-type: image/png");
imagepng($image);

?>
