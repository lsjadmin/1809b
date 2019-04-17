<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <script src="/js/jquery/jquery.min.js"></script>
    <script src="/js/weixin/qrcode.min.js"></script>
</head>
<body>
<div width="300px" height="300px" border="1">
    <div id="qrcode"></div>
</div>

</body>
<ml>
<script>
    var qrcode = new QRCode('qrcode',{
        text:'{{$code_url}}',
        width:256,
        height:256,
        colorDark : '#000000',
        colorLight : '#ffffff',
        correctLevel : QRCode.CorrectLevel.H
    });

    //qrcode.clear();
    //qrcode.makeCode('new $wpayurl');
</script>