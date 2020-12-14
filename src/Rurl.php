<?php
/******************************************************************************************************************
 ** curl 请求网络
******************************************************************************************************************/
namespace ToolPackage;

class Rurl
{
    /**
     * 创建目录文件错误
     */
    const ERROR_CREATE_FILE = 9001;

    public static $obj = null;

    /**
     * 错误编码
     */
    public $errorNum = 0;

    /**
     * 错误信息
     */
    public $errorMessage = '';

    /**
     * 当前正在使用的uri
     */
    public $currentUri = '';

    /**
     * 解析后 uri 信息
     */
    public $currentUriInfo = [];

    /**
     * 超时时间（秒）
     */
    public $timeout = 30;

    /**
     * 超时重新请求次数
     */
    public $maxRequest = 3;

    /**
     * cookie缓存目录
     */
    public $cookieDir = '';

    /**
     * curl句柄
     * @var handle
     */
    public $curl = null;

    /**
     * 响应 头信息
     */
    public $headers = [];

    /**
     * 请求成功后的结果
     * @var text
     */
    public $contents;

    /**
     * curl option
     * @var array
     */
    protected $curlOptions = [];

    /**
     * 请求头
     * @var array key-value格式
     * 例如: ['Accept-Encoding'=>'gzip, deflate','Accept-Language'=>'zh-CN,zh;q=0.9']
     */
    public $requestHeaders = [];

    /**
     * 请求完成后回调
     * @param $rurl Rurl实例对象
     */
    public $onFinished;

    /**
     * 请求失败后回调
     * @param $rurl Rurl实例对象
     */
    public $onError;

    public function __construct()
    {
        $this->curl = curl_init();
        $this->curlOptions[CURLOPT_TIMEOUT] = $this->timeout;
        $this->curlOptions[CURLOPT_RETURNTRANSFER] = true;
    }

    /**
     * 入口
     */
    public static function make()
    {
        static::$obj || static::$obj = new static();
        return static::$obj;
    }

    /**
     * 设置错误信息
     * @param string $msg 错误信息
     */
    public function setErrorMessage($msg)
    {
        $this->errorMessage = $msg;
    }

    /**
     * 设置错误编码
     * @param int $errno
     */
    public function setErrorNum($errno)
    {
        $this->errorNum = $errno;
    }

    /**
     * 设置错误信息
     * @param int $errno
     * @param string $msg
     */
    public function setErrorInfo($errno=0, $msg='')
    {
        $this->setErrorNum($errno);
        $this->setErrorMessage($msg);
    }

    /**
     * 设置curlOption（多个）
     * @param array $option
     */
    public function setOptionArray($options = [])
    {
        $this->curlOptions = array_merge($this->curlOptions, $options);
    }

    /**
     * 设置curlOption（单个）
     * @param curl-option-key
     * @param curl-option-value
     */
    public function setOption($curl_option, $value)
    {
        $this->curlOptions[$curl_option] = $value;
    }

    /**
     * 设置请求头（多个）
     * @param array headers
     */
    public function setRequestHeaderArray($headers = [])
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $headers);
    }
    /**
     * 设置请求头（单个）
     * @param string $header_name
     * @param string $value
     */
    public function setRequestHeader($header_name, $value)
    {
        $this->requestHeaders[$header_name] = $value;
    }

    /**
     * 将requestHeaders整合到curl
     */
    public function headersToCurl()
    {
        $headers = [];
        foreach($this->requestHeaders as $header_name=>$value)
        {
            $headers[] = $header_name . ': ' . $value;
        }
        $this->curlOptions[CURLOPT_HTTPHEADER] = $headers;
    }


    /**
     * 设置存储cookie的文件(下次请求会覆盖上次的内容)
     * @param string $filename cookie文件名
     */
    public function setCookieJar($filename)
    {
        if(!file_exists($filename))
        {
            $this->createFile($filename);
        }
        $this->curlOptions[CURLOPT_COOKIEJAR] = $filename;
    }

    /**
     * 设置包含 cookie 数据的文件
     * @param string $cookie_file
     */
    public function setCookieFile($cookie_file)
    {
        $this->curlOptions[CURLOPT_COOKIEFILE] = $cookie_file;
    }

    /**
     * 设置响应头的保存文件
     */
    public function setResponseHeaderFile($filename)
    {
        $this->curlOptions[CURLOPT_WRITEHEADER] = $filename;
    }

    /**
     * 设置cookie保存目录
     * @param string $path
     */
    public function setCookieDir($path)
    {
        $this->cookieDir = $path;
    }

    /**
     * 创建文件
     */
    public function createFile($filename)
    {
        $dirname = dirname($filename);
        if(!file_exists($dirname))
        {
            $succ = mkdir($dirname, 0777, true);
            if(!$succ)
            {
                $this->setErrorInfo(self::ERROR_CREATE_FILE, '尝试创建目录' . $dirname . '失败');
            }
            $succ = touch($filename);
            if(!$succ)
            {
                $this->setErrorInfo(self::ERROR_CREATE_FILE, '尝试创建文件' . $filename . '失败');
            }
        }
    }

    /**
     * 解析cookie头
     */
    public function parseCookieHeader($cookiestring)
    {
        // $cookiestring = substr($cookiestring, 11);
        $info_list = explode(';', trim($cookiestring, ';'));
        $kv = array_shift($info_list);
        list($key, $value) = explode('=', $kv);
        $cookie = [
            'key' => $key,
            'value' => $value,
            'kv' => $kv
        ];

        foreach($info_list as $item)
        {
            list($k, $v) = explode('=', trim($item));
            $cookie[$k] = $v;
        }

        if(empty($cookie['path']) || $cookie['path'][0] != '/')
        {
            $cookie['path'] = $this->getDirname();
        }
        return $cookie;
    }

    /**
     * 解析url中path的目录
     * 如果path最后不是以 / 结尾,则最后的名称当做文件名处理
     * @return string 目录路径
     */
    public function getDirname()
    {
        if(!empty($this->currentUriInfo['path']))
        {
            if(substr($this->currentUriInfo['path'],-1) == '/')
            {
                $dirname = substr($this->currentUriInfo['path'], 0, -1);
            }
            $dirname = str_replace('\\', '/', dirname($this->currentUriInfo['path']));
        }
        else
        {
            $dirname = '/';
        }
        return $dirname;
    }


    /**
     * 回调函数 headerfunction
     */
    public function headerFunction($cp, $header)
    {

        $pattern = '/(^\S+):\s*(\S+)/';
        preg_match($pattern, $header, $matcher);
        if($matcher)
        {
            if(strtolower($matcher[1]) == 'set-cookie')
            {
                isset($this->headers['set-cookie']) || $this->headers['set-cookie'] = [];
                $this->headers['set-cookie'][] = $matcher[2];
                $cookie = $this->parseCookieHeader($matcher[2]);
                $cookies = $this->getStoredCookie();
                $cookies[$cookie['key']] = $cookie;
                $this->storeCookie($cookies);
            }
            else
            {
                $this->headers[strtolower($matcher[1])] = $matcher[2];
            }
        }
        return strlen($header);
    }

    /**
     * 获取已存储的cookie
     */
    protected function getStoredCookie()
    {
        $cookie = [];
        $cookie_file = $this->getCacheCookieFile();
        if(file_exists($cookie_file))
        {
            $data = file_get_contents($cookie_file);
            $data and $cookie = json_decode($data, true);
        }
        return $cookie;
    }

    /**
     * 保存cookie
     */
    protected function storeCookie($cookies)
    {
        $cookie_file = $this->getCacheCookieFile();
        if(!file_exists($cookie_file))
        {
            $this->createFile($cookie_file);
        }
        file_put_contents($cookie_file, json_encode($cookies));
    }

    /**
     * 获取缓存cookie_file
     */
    protected function getCacheCookieFile()
    {
        $data = parse_url($this->currentUri);
        return rtrim($this->cookieDir, '\/') . '/' . md5($data['scheme'].$data['host']);
    }

    /**
     * 请求完成后的动作
     * @param callable $action
     */
    public function setOnFinished(callable $action)
    {
        $this->onFinished = $action;
    }

    /**
     * 请求失败后的动作
     * @param callable $action
     */
    public function setOnError(callable $action)
    {
        $this->onError = $action;
    }

    /**
     * 通过url获取数据
     * @param String $url
     * @param Array $options curl的option设置
     * @param Number $maxnum 尝试连接的次数
     */
    public function exec($url)
    {
        $this->curlOptions[CURLOPT_URL] = $url;

        $this->currentUri = $url;
        $this->currentUriInfo = parse_url($url);
        //设置request-header
        $this->headersToCurl();
        //是否自动发送cookie
        $this->autoSendCookie();
        //是否缓存cookie
        $this->cacheCookie();
        curl_setopt_array($this->curl, $this->curlOptions);

        if(!empty($this->currentUriInfo['scheme']) && $this->currentUriInfo['scheme'] == 'https'){
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $start_time = date('Y-m-d H:i:s');
        $contents = curl_exec($this->curl);
        $num = 1;
        while(curl_errno($this->curl) === 28){
            if($num > $this->maxRequest){
                break;
            }
            $num++;
            $start_time = date('Y-m-d H:i:s');
            $contents = curl_exec($this->curl);
        }
        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        $this->setErrorInfo($errno, $error);

        /* if(isset($options[CURLOPT_POSTFIELDS])){
         *     if(is_array($options[CURLOPT_POSTFIELDS])){
         *         $param_str = json_encode($options[CURLOPT_POSTFIELDS]);
         *     }else{
         *         $param_str = $options[CURLOPT_POSTFIELDS];
         *     }
         *     $param_str = '参数：' . $param_str;
         * }else{
         *     $param_str = '';
         * } */

        if($error)
        {
            if($this->onError)
            {
                ($this->onError)($this);
            }
        }
        else
        {
            if($this->headers['content-encoding'] == 'gzip')
            {
                $this->contents = gzdecode($contents);
            }
            else
            {
                $this->contents = $contents;
            }
            if($this->onFinished)
            {
                ($this->onFinished)($this);
            }
        }
    }

    /**
     * 缓存cookie
     */
    protected function cacheCookie()
    {
        if($this->cookieDir)
        {
            $this->curlOptions[CURLOPT_HEADERFUNCTION] = [$this, 'headerFunction'];
        }
    }

    /**
     * 自动发送cookie
     */
    protected function autoSendCookie()
    {
        $cookie_data = $this->getStoredCookie();
        if($cookie_data)
        {
            $cookie_list = [];
            foreach($cookie_data as $info)
            {
                if($this->cookieIsValid($info))
                {
                    $cookie_list[] = $info['kv'];
                }
            }
            if($cookie_list)
            {
                $this->curlOptions[CURLOPT_COOKIE] = implode('; ', $cookie_list);
            }
        }
    }

    /**
     * 检查cookie 是否有效
     * @param array $info 一条cookie数据信息
     */
    public function cookieIsValid(&$info)
    {
        if(isset($info['expires']))
        {
            $unix_time = intval(strtotime($info['expires']));
            $unix_time = $unix_time - 3600*8;

            if($unix_time <= time())
            {
                return false;
            }
        }
        if(!empty($info['path']) && $info['path'] != '/' && $info['path'][0] == '/')
        {
            $preg = preg_quote(rtrim($info['path'], '/'), '/');
            $match_num = preg_match('/^' . $preg . '/', $this->currentUriInfo['path']);
            if($match_num == 0)
            {
                return false;
            }

        }
        return true;
    }

    /**
     * 获取错误信息
     */
    public function getErrorInfo()
    {
        return [
            'error_code' => $this->errorNum,
            'error_message' => $this->errorMessage
        ];
    }

    /**
     * 获取错误编码
     */
    public function getErrorCode()
    {
        return $this->errorNum;
    }

    /**
     * 获取错误描述
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * 将请求参数放到url上
     * @param String $url
     * @param String or Array $param 参数
     */
    public function setUrlParam($url, $param = array())
    {
        $p = '';
        if(is_array($param)){
            foreach($param as $k=>$v){
                $p .= "{$k}=".urlencode($v)."&";
            }
            $p = trim($p, '&');
        }else if(is_string($param)){
            $p .= $param;
        }

        $uinfo = parse_url($url);
        $new_url = '';
        !empty($uinfo['scheme']) && $new_url .= $uinfo['scheme'].'://';
        !empty($uinfo['host']) && $new_url .= $uinfo['host'];
        !empty($uinfo['port']) && $new_url .= ':' . $uinfo['port'];
        !empty($uinfo['path']) && $new_url .= $uinfo['path'];

        $query = '';
        if(!empty($uinfo['query'])){
            $query .= '?'.$uinfo['query'];
        }
        if($p){
            if($query){
                $query .= '&'.$p;
            }else{
                $query .= '?'.$p;
            }
        }
        $new_url .= $query;
        !empty($uinfo['fragment']) && $new_url .= '#'.$uinfo['fragment'];
        return $new_url;
    }

    /**
     * 初始化错误
     */
    public function errorInit()
    {
        $this->errorNum = 0;
        $this->errorMessage = '';
        $this->headers = [];
    }

    /**
     * get方式 获取数据
     * @param String $url
     * @param $param 字符串 或 数组
     */
    public function get($url, $param = array())
    {
        $this->errorInit();
        $this->curlOptions[CURLOPT_HTTPGET] = true;
        $url = $this->setUrlParam($url, $param);
        //echo $url;exit;
        return $this->exec($url);
    }

    /**
     * post方式 获取数据
     * @param String $url
     * @param $param 数组
     */
    public function post($url, $param = array())
    {
        $this->errorInit();
        $this->curlOptions[CURLOPT_POST] = true;
        $this->curlOptions[CURLOPT_POSTFIELDS] = $param;
        return $this -> exec($url);
    }

    /**
     * 自定义 方式 获取数据
     * @param String $url
     * @param String $method 自定义的传输方式
     * @param $param 数组
     */
    public function methodInterface($url, $method, $param = array())
    {
        $this->errorInit();
        $this->curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        $this->curlOptions[CURLOPT_POSTFIELDS] = $param;
        return $this -> exec($url);
    }

    /**
     * 关闭curl
     */
    public function close()
    {
        curl_close($this->curl);
    }

}
