<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
require dirname(__DIR__) . '/vendor/autoload.php';
// endroid/qr-code
use Endroid\QrCode\Response\QrCodeResponse;

function jsonResult($status, $result)
{
    $returnVal = array(
        'status' => $status,
        'result' => $result,
    );
    echo json_encode($returnVal);
}

$imgFiles = $_FILES['parseImg'];
$allowDot = array('jpg', 'png', 'jpeg', 'gif');
if (explode('/', $imgFiles['type'])[0] !== 'image') {
    jsonResult('400', '您上传的不是图片, 请检测后再重新上传');
    return false;
} else if (in_array(explode('/', $imgFiles['type'])[1], $allowDot) === -1) {
    jsonResult('400', '上传的图片格式只允许 png/jpg/jpeg/gif, 请检查后重新上传');
    return false;
} else if ($imgFiles['size'] <= 0) {
    jsonResult('400', '抱歉, 你上传的图片异常, 请检查后重新上传');
    return false;
}

if ($imgFiles['type'] === 'image/jpg' || $imgFiles['type'] === 'image/jpeg') {
    $imgTmp = imagecreatefromjpeg($imgFiles['tmp_name']);
} elseif ($imgFiles['type'] === 'image/png') {
    $imgTmp = imagecreatefrompng($imgFiles['tmp_name']);
} else {
    $imgTmp = imagecreatefromgif($imgFiles['tmp_name']);
}

$width = imagesx($imgTmp);
$height = imagesy($imgTmp);
$imgTmpName = time() . '.png';

$maxWidth = 400;
$maxHeight = 400;

$imgPrecent;
$width > $height ? $imgPrecent = $width / $maxWidth : $imgPrecent = $height / $maxHeight;

$newWidth = $width / $imgPrecent;
$newHeight = $height / $imgPrecent;

$newImage = imagecreatetruecolor($newWidth, $newHeight); // 创建一个空白的画布
imagecopyresampled(
    $newImage, $imgTmp, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height
);

$imgTmpName = time() . '.png';
if (!file_exists(dirname(__DIR__) . '/upload/image/')) {
    mkdir(dirname(__DIR__) . '/upload/image/', 0777, true);
}

imagepng($newImage, dirname(__DIR__) . '/upload/image/' . $imgTmpName); // 保存图片
imagedestroy($imgTmp);
imagedestroy($newImage);

$qrCode = new Zxing\QrReader(dirname(__DIR__) . '/upload/image/' . $imgTmpName);
$text = $qrCode->text();
exit(jsonResult('200', $text));
