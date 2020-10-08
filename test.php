<?php
include __DIR__ . '/src/Rurl.php';
$url = 'http://hello.xiaofuxing.top';
$url = 'https://www.baidu.com';
$curl = ToolPackage\Rurl::make();
$curl -> setSaveCookie(__DIR__ . '/cookie.txt');
$header_file = fopen(__DIR__ . '/header.txt', 'wb');
$curl -> setResponseHeaderFile($header_file);
$data = $curl -> get($url);
print_r($data);
fclose($header_file);
