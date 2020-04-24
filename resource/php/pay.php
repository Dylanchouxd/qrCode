<?php
    header("Content-type: text/html; charset=utf-8");
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match("/.+MicroMessenger.+/", $ua)) {
        $url = '$weChatToWrite';
        header('location: ' . $url);
    } elseif (preg_match("/.+AlipayClient.+/", $ua)) {
        $url = '$aliPayToWrite';
        header('location: ' . $url);
    } elseif (preg_match("/.+QQ.+/", $ua)) {
        $url = '$tencentToWrite';
        header('location: ' . $url);
    } else {
        echo "请使用支付宝或微信或QQ客户端扫码付款";
    }
?>