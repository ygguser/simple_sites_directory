<?PHP

function DrawLines($image) {
    $linenum = rand(2, 3);//Number of lines
    for ($i=0; $i<$linenum; $i++) {
        $color = imagecolorallocate($image, rand(0, 150), rand(0, 100), rand(0, 150));
        imageline($image, rand(0, 35), rand(1, 25), rand(60, 70), rand(1, 25), $color);
    }
}

session_start();
$captcha_num = rand(10000, 99999);
$_SESSION['code'] = $captcha_num;

$image = imagecreatetruecolor(70, 25);
$lightgray = imagecolorallocate($image, 245, 245, 240);

//fonts from https://fonts-online.ru/fonts/victor-mono/download
$fonts = array();
$fonts[0]['fname'] = 'VictorMono-ExtraLightItalic.otf';
$fonts[1]['fname'] = 'VictorMono-Italic.otf';
$fonts[2]['fname'] = 'VictorMono-ExtraLight.otf';

imagefill($image, 0, 0, $lightgray); 
putenv('GDFONTPATH=' . realpath('./fonts'));

DrawLines($image);

//Text
$x = 0;
for($i = 0; $i < strlen($captcha_num); $i++) {
    $color = imagecolorallocate($image, rand(0, 200), 0, rand(0, 200));
    $x+=10;
    $letter = substr($captcha_num, $i, 1);
    $font_num = rand(0, sizeof($fonts)-1);
    imagettftext($image, 16, rand(2, 10), $x, rand(21, 24), $color, $fonts[$font_num]["fname"], $letter);
}

DrawLines($image);

header("Content-type: image/png");
imagepng($image);
ImageDestroy($image);

?>
