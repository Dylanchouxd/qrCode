( function ( $ ) {
    /**
     * 付款码三合一
     * 点击 合并 上传文件到后台
     * 
     */
    $( '#merge' ).click( function ( e ) {
        var data = new FormData( document.getElementById( 'payQrcodeForm' ) );
        $.ajax( {
            url: './qrcode/payQrcode.php',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $( '#merge' ).button( 'loading' );
            },
            success: function ( result ) {
                $( '#merge' ).button( 'reset' );
                var res = JSON.parse( result );

                var toastOptions = {
                    heading: '出错了',
                    text: res.result,
                    icon: 'error',
                    position: 'top-right',
                    loader: false,
                    showHideTransition: 'plain',
                };

                if ( res.status === '200' ) {
                    toastOptions.heading = '成功';
                    toastOptions.icon = 'success';
                    toastOptions.hideAfter = 10000;

                    $( '#qrCodeModal' ).modal( 'show' );
                    $( '#qrcodeUrl' ).attr( 'src', res.qrcodeUrl );
                    $( '#qrcodeDownload' ).attr( 'href', res.qrcodeUrl );
                }
                $.toast( toastOptions );
            },
            error: function ( e ) {
                $( '#merge' ).button( 'reset' );
                $.toast( {
                    heading: '出错了',
                    text: '抱歉, 服务器出现了错误, 请重新尝试!',
                    icon: 'error',
                    position: 'top-right',
                    loader: false,
                    showHideTransition: 'plain',
                } );
            }
        } );
    } );

    $( '[data-toggle="tooltip"]' ).tooltip();
    $( '.file' ).change( function ( e ) {
        $( 'label[for=' + $( e.target ).prop( 'id' ) + ']' ).attr( 'data-original-title', e.target.files[ 0 ].name );
    } );

    /**
     * 二维码解析
     * 解析上传的图片数据
     */
    $( '#parseImg' ).change( function ( e ) {
        var data = new FormData( document.getElementById( 'parseQrcodeForm' ) );
        $.ajax( {
            url: './qrcode/parseQrcode.php',
            type: 'POST',
            processData: false,
            contentType: false,
            data: data,
            success: function ( result ) {
                var res = JSON.parse( result );
                var toastOptions = {
                    heading: '出错了',
                    text: '(；′⌒`) 解析失败...',
                    icon: 'error',
                    position: 'top-right',
                    loader: false,
                    showHideTransition: 'plain',
                };

                if ( res.result !== false ) {
                    $( '#qrcodeText' ).html( res.result );
                } else {
                    $( '#qrcodeText' ).html( '抱歉, 解析失败, 请确定您这张图片上附带二维码.' );
                }

                if ( res.status === '200' && res.result !== false ) {
                    toastOptions.heading = '成功啦';
                    toastOptions.icon = 'success';
                    toastOptions.text = 'ヾ(◍°∇°◍)ﾉﾞ 解析成功~';
                }

                $.toast( toastOptions );
            }
        } );
    } );
} )( jQuery );
