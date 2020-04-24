<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('content-type: text/html; charset=utf-8');
define('folderUrl', $_SERVER["DOCUMENT_ROOT"] . '/upload/image/'); // 上传图片保存位置
define('resourceUrl', $_SERVER["DOCUMENT_ROOT"] . '/resource/'); // 资源文件位置
define('qrCodeLibUrl', $_SERVER["DOCUMENT_ROOT"] . '/qrCodeLib/'); // 用户二维码保存位置
define('toolDir', 'http://' . $_SERVER['HTTP_HOST'] . '/qrCodeLib/'); // 外网访问用户二维码路径

require $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php';

// endroid/qr-code
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

/**
 * filterData - 过滤表单数据
 * 1. 删除无用空格/换行字符
 * 2. 删除“\”反斜杠字符
 * 3. 将 分号/双引号/小于号/大于号 等特殊符号转义 防止XSS攻击
 */
function filterData($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * scaleImg - 等比例缩放图片
 * @param Object $image 目标图片
 * @param Number $maxWidth 缩放最大宽度
 * @param Number $maxHeight 缩放最大高度
 * @param String $type 标识符
 *
 */
function scaleImg($image, $maxWidth = 400, $maxHeight = 400, $type)
{
    $width = imagesx($image);
    $height = imagesy($image);

    if ($type === 'tencent') { // 因为QQ付款码的小，图片太小有可能检测不出来内容。
        $maxWidth = 400;
        $maxHeight = 400;
    } else {
        $maxWidth = $maxWidth;
        $maxHeight = $maxHeight;
    }

    if ($width > $height) {
        $imgPrecent = $width / $maxWidth;
    } else {
        $imgPrecent = $height / $maxHeight;
    }

    $newWidth = $width / $imgPrecent;
    $newHeight = $height / $imgPrecent;

    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled(
        $newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height
    );

    imagepng($newImage, $GLOBALS['imgFiles']['name']);
    $GLOBALS['imgFiles']['tmp_name'] = $_SERVER["DOCUMENT_ROOT"] . '/qrcode/' . $GLOBALS['imgFiles']['name'];
    imagedestroy($image);
    imagedestroy($newImage);
}

function jsonResult($status, $result, $qrcodeUrl = '')
{
    $returnVal = array(
        'status' => $status,
        'result' => $result,
        'qrcodeUrl' => $qrcodeUrl,
    );
    echo json_encode($returnVal);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileVar = array('weChat', 'aliPay', 'tencent');
    $allowDot = array('jpg', 'png', 'jpeg', 'gif');
    $qrCodeContent = array();

    if (!is_dir(folderUrl)) {
        mkdir(iconv('UTF-8', 'GBK', folderUrl), 0777, true);
    }

    for ($i = 0; $i < count($fileVar); $i++) {
        $$fileVar[$i] = '';
        $imgFiles = $_FILES[$fileVar[$i]];
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
        
        /**
         * 将图片等比例缩放
         * 为了提高识别速度
         *
         */
        $imageTmp;
        if ($imgFiles['type'] === 'image/jpg' || $imgFiles['type'] === 'image/jpeg') {
            $imageTmp = imagecreatefromjpeg($imgFiles['tmp_name']);
        } else if ($imgFiles['type'] === 'image/png') {
            $imageTmp = imagecreatefrompng($imgFiles['tmp_name']);
        } else {
            $imageTmp = imagecreatefromgif($imgFiles['tmp_name']);
        }

        scaleImg($imageTmp, 200, 200, $fileVar[$i]);

        /**
         * 移动图片到目标文件夹
         * 如果有重复名称的话, 在名字后面加 时间戳 防止覆盖
         */
        if (file_exists(folderUrl . $imgFiles['name'])) {
            $imgName = explode('.', $imgFiles['name'])[0] . '_' . time() . '.' . explode('.', $imgFiles['name'])[1];
            $imgFiles['name'] = $imgName;
        }
        if ($i === 0) {
            $weChatName = $imgFiles['name'];
        }

        copy($imgFiles['tmp_name'], folderUrl . $imgFiles['name']);
        unlink($imgFiles['tmp_name']);
        /**
         * Import Lib: khanamiryan/qrcode-detector-decoder
         * 识别二维码内容
         */
        $qrCode = new Zxing\QrReader(folderUrl . $imgFiles['name']);
        $text = $qrCode->text();
        array_push($qrCodeContent, $text);
    }

    /**
     * 将用户的文件合并在一个文件夹里面
     *
     */
    $userFolder = 'user_' . time();
    $userFolderUrl = qrCodeLibUrl . $userFolder;
    if (!is_dir($userFolderUrl . '/weChat/')) {
        mkdir(iconv('UTF-8', 'GBK', $userFolderUrl . '/weChat/'), 0777, true);
    }

    move_uploaded_file($_FILES['weChat']['tmp_name'], $userFolderUrl . '/weChat/' . $weChatName); // 因为需要weChat的二维码, 所以需要保存起来
    copy(resourceUrl . 'php/pay.php', $userFolderUrl . '/pay.php');

    /**
     * 编辑 pay.php 页面
     *
     */
    $str = '';
    $qrCodeContent[0] = toolDir . $userFolder . '/weChat/' . $weChatName;
    $file_pointer = fopen($userFolderUrl . '/pay.php', 'r');
    $replaceStr = array(
        '$weChatToWrite',
        '$aliPayToWrite',
        '$tencentToWrite',
    );
    while (!feof($file_pointer)) {
        $rep = fgets($file_pointer);
        $rep = str_replace($replaceStr, $qrCodeContent, $rep);
        $str .= $rep;
    }
    $file_pointer2 = fopen($userFolderUrl . '/pay.php', 'w');
    fwrite($file_pointer2, $str);
    fclose($file_pointer);
    fclose($file_pointer2);

    /**
     * Import Lib: endroid/qr-code
     * 生成三合一二维码
     *
     */
    $qrcode2 = new QrCode(toolDir . $userFolder . '/pay.php');
    $qrcode2->setSize(300);

    $qrcode2->setWriterByName('png');
    $qrcode2->setMargin(10);
    $qrcode2->setEncoding('UTF-8');
    $qrcode2->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
    $qrcode2->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
    $qrcode2->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
    $qrcode2->setValidateResult(false);

    $qrcode2->writeFile($userFolderUrl . '/pay.png');

    // 返回数据到前端
    jsonResult('200', '生成二维码成功, 请务必记得保存图片', toolDir . $userFolder . '/pay.png');
}
