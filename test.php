<?php
include __DIR__ . '/src/Rurl.php';
// $url = 'https://fb.xiaofuxing.top/test.php';
$url = 'https://www.baidu.com';
$curl = new ToolPackage\Rurl;
$curl->setCookieDir(__DIR__ . '/cookies');

$curl->setRequestHeaderArray([
    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    'Accept-Encoding' => 'gzip, deflate, br',
    'Accept-Language' => 'zh-CN,zh;q=0.9',
    'Cache-Control' => 'no-cache',
    'Connection' => 'keep-alive',
    'Pragma' => 'no-cache',
    'Sec-Fetch-Dest' => 'document',
    'Sec-Fetch-Mode' => 'navigate',
    'Sec-Fetch-Site' => 'none',
    'Sec-Fetch-User' => 'cache',
    'Upgrade-Insecure-Requests' => '1',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36'

]);
$curl->setOnFinished(function($curl){
    echo $curl->contents;
});

$curl->get($url);
