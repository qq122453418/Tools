<?php
include __DIR__ . '/src/Rurl.php';
$url = 'https://fb.xiaofuxing.top/test.php';
$curl = new ToolPackage\Rurl;
$curl->setOnFinished(function($curl){
    echo $curl->contents;
});
$curl->get($url);
