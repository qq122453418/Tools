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
    protected $errorNum = 0;

    /**
     * 错误信息
     */
    protected $errorMessage = '';

    /**
     * curl句柄
     * @var handle
     */
    protected $curl = null;

    /**
     * curl option
     * @var array
     */
    protected $curlOptions = [];


    /**
     * 请求结束后回调(主要是为了调试)
     * @param $url 
     * @param $param 请求的参数
     * @param $errno 错误码
     * @param $error 错误描述
     * @param $contents 请求到的内容
     */
    protected $_after;

    protected function __construct($url = null)
    {
        $this->curl = curl_init($url);
    }

    /**
     * 入口
     */
    public static function make($url = null)
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
        $this->errorNum = $error;
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
     * 设置curlOption
     * @param array $option 
     */
    public function setOption($option = [])
    {
        $this->curlOptions = $option;
    }

    /**
     * 设置请求头
     * @param array headers
     */
    public function setHeaders($headers = [])
    {
        $this->curlOptions[CURLOPT_HTTPHEADER] = $headers;
    }

    /**
     * 设置存储cookie的文件
     * @param string $filename cookie文件名
     */
    public function setSaveCookie($filename)
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
     * 请求完成后的动作
     * @param callable $after
     */
    public function onFinish(callable $after)
    {
        $this->_after = $after;
    }

    /**
     * 通过url获取数据
     * @param String $url 
     * @param Array $options curl的option设置
     * @param Number $maxnum 尝试连接的次数
     */
    public function exec($url, $options=array(), $maxnum=2)
    {
        //set_time_limit(60);
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
        );
        curl_setopt_array($this->curl, $this->curlOptions);
        curl_setopt_array($this->curl,$opt);
        if($options){
            curl_setopt_array($this->curl,$options); 
        }
        
        $_arr = parse_url($url);
        if(!empty($_arr['scheme']) && $_arr['scheme'] == 'https'){
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $start_time = date('Y-m-d H:i:s');
        $contents = curl_exec($this->curl);
        $num = 1;
        while(curl_errno($this->curl) === 28){
            if($num >= $maxnum){
                break;
            }
            $num++;
            $start_time = date('Y-m-d H:i:s');
            $contents = curl_exec($this->curl);
        }
        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        curl_close($this->curl);
        
        if(isset($options[CURLOPT_POSTFIELDS])){
            if(is_array($options[CURLOPT_POSTFIELDS])){
                $param_str = json_encode($options[CURLOPT_POSTFIELDS]);
            }else{
                $param_str = $options[CURLOPT_POSTFIELDS];
            }
            $param_str = '参数：' . $param_str;
        }else{
            $param_str = '';
        }

        if($error){
            $contents=null;
        }

        if($this->_after)
        {
            $this->_after($url, $options[CURLOPT_POSTFIELDS], $errno, $error, $contents);
        }
        return [
            'errno' => $errno,
            'error' => $error,
            'contents' => $contents,
        ];
    }

    /**
     * 获取错误信息
     */
    public function getErrorInfo()
    {
        return $this -> errorInfo;
    }

    /**
     * 将请求参数放到url上
     * @param String $url 
     * @param String or Array $param 参数
     */
    public function setUrlParam($url, $param = array()){
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
     * get方式 获取数据
     * @param String $url
     * @param $param 字符串 或 数组
     */
    public function get($url, $param = array(),$opt=array()){
        $opt[CURLOPT_HTTPGET] = true;
        $url = $this -> setUrlParam($url, $param);
        //echo $url;exit;
        return $this -> exec($url,$opt);
    }

    /**
     * post方式 获取数据
     * @param String $url
     * @param $param 数组
     */
    public function post($url, $param = array(), $opt=array()){
        $opt[CURLOPT_POST] = true;
        $opt[CURLOPT_POSTFIELDS] = $param;
        return $this -> exec($url, $opt);
    }

    /**
     * 自定义 方式 获取数据
     * @param String $url
     * @param String $method 自定义的传输方式
     * @param $param 数组
     */
    public function methodInterface($url, $method, $param = array(), $opt = array()){
        $opt[CURLOPT_CUSTOMREQUEST] = $method;
        $opt[CURLOPT_POSTFIELDS] = $param;
        return $this -> exec($url, $opt);
    }


}
