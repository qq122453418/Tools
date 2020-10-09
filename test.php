<?php
include __DIR__ . '/src/Rurl.php';
$url = 'http://hello.xiaofuxing.top';
// $url = 'https://www.baidu.com';
$curl = ToolPackage\Rurl::make();
$curl -> setSaveCookie(__DIR__ . '/cookie.txt');
$header_file = fopen(__DIR__ . '/header.txt', 'wb');
$curl -> setResponseHeaderFile($header_file);
$curl->setOptions([
    CURLOPT_HEADERFUNCTION => function($cp, $header){
        static $cookies = [];
        if(substr($header, 0, 11) === 'Set-Cookie:'){
            $content = substr($header, 11); 
            $info_list = explode(';', trim($content));
            $kv = array_shift($info_list);
            list($key, $value) = explode('=', $kv);
            $cookies[$key] = [
                'key' => $key,
                'value' => $value,
                'kv' => $kv
            ];
            foreach($info_list as $item)
            {
                list($k, $v) = explode('=', trim($item));
                $cookies[$key][$k] = $v;
            }
            file_put_contents('cookie.json', json_encode($cookies));
        }
        file_put_contents('header2.txt', $header, FILE_APPEND);
        return strlen($header);
    }
]);
$data = $curl -> get($url);
print_r($data);
fclose($header_file);
