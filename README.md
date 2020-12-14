Rurl
=============

对curl的操作进行了封装，可通过对象的方式进行操作。

Methods List
-------------

```
//设置选项(通过数组方式), 同curl的options
$rurl->setOptionArray(array $options);

//设置选项, 同curl的options
$rurl->setOption($option_key, $value);

//设置请求头
$rurl->setRequestHeaderArray(array $headers)

//设置请求头
$rurl->setRequestHeader($header_name, $value)

//设置存储cookie的文件(下次请求会覆盖上次的内容)
$rurl->setCookieJar($filename)

//设置响应头的保存文件
$rurl->setResponseHeaderFile($filename)

//设置包含 cookie 数据的文件（用与发送cookie）
$rurl->setCookieFile($cookie_file);

//设置响应cookie的缓存目录（如果进行了设置，则会自动发送缓存的cookie）
$rurl->setCookieDir($dirname)

//请求成功后的操作, 接收一个参数：rurl对象
$rurl->setOnFinished(callable $action)

//请求失败后的操作，回调函数接收一个参数：rurl对象
$rurl->setOnError(callable $action)

//获取错误信息包含错误编码和文本信息
$rurl->getErrorInfo()

//获取错误编码
$rurl->getErrorCode()

//获取错误文本
$rurl->getErrorMessage()

//get请求
$rurl->get($url, $param=[])

//post请求
$rurl->post($url, $param=[])

//自定义方式请求
$rurl->methodInterface($url, $method, $param=[])

//关闭curl
$rurl->close()
```

