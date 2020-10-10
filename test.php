<?php
echo substr('/aa/', 0, -1);
exit;
include __DIR__ . '/src/Rurl.php';
$url = 'http://hello.xiaofuxing.top/a/aa/';
// $url = 'https://www.baidu.com';
$curl = ToolPackage\Rurl::make();
//$curl->setCookieJar(__DIR__ . '/cookie.txt');
//
$curl->setCookieDir(__DIR__ . '/cookies');

$curl->setOnFinished(function($curl){
    print_r($curl->currentUriInfo);
    print_r($curl->contents);
});

$curl->setOnError(function($curl){
    echo '出错了' . "\n";
    print_r($curl->errorNum . "\n");
    print_r($curl->errorMessage);
});
$curl->get($url);

