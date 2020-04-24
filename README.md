## 项目依赖

使用了 [Endroid QR Code](https://github.com/endroid/qr-code) 的库实现本地合成二维码和解析二维码  

使用了 [khanamiryan php-qrcode-detector-decoder](https://github.com/khanamiryan/php-qrcode-detector-decoder) 的库实现本地解析二维码

## 安装

```linux
composer install

# 如果网络错误下载不了 推荐使用阿里云的Composer源
https://developer.aliyun.com/composer
执行 composer config repo.packagist composer https://mirrors.aliyun.com/composer/
```

### 项目说明

项目运行中存在自动创建文件夹和保存文件的操作，请确定有相应权限去操作。

### 目录说明

qrCode：项目逻辑文件

qrCodeLib：自动生成，用来存放合成后二维码。（外链访问于此）

upload：自动生成，用来存放用户上传的图片

resouce：HTML页面