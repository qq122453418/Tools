<?php
/******************************************************************************************************************
** curl 请求网络
******************************************************************************************************************/
namespace Tools\Rurl;

class Rurl
{
	/**
	 * 错误记录
	 */
	protected $errorInfo = null;

	/**
	 * 通过url获取数据
	 * @param String $url 
	 * @param Number $maxnum 尝试连接的次数
	 * @param Array $options curl的option设置
	 */
	public function exec($url, $options = array(), $maxnum = 2){
		//set_time_limit(60);
		$cp = curl_init();
		$opt = array(
			CURLOPT_URL => $url,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => true,
		);
		
		curl_setopt_array($cp,$opt);
		if($options){
			curl_setopt_array($cp,$options); 
		}
		
		$_arr = parse_url($url);
		if(!empty($_arr['scheme']) && $_arr['scheme'] == 'https'){
			curl_setopt($cp, CURLOPT_SSL_VERIFYPEER, false);
		}
		$start_time = date('Y-m-d H:i:s');
		$contents = curl_exec($cp);
		$num = 1;
		while(curl_errno($cp) === 28){
			if($num > $maxnum){
				break;
			}
			$num++;
			$start_time = date('Y-m-d H:i:s');
			$contents = curl_exec($cp);
		}
		$errno = curl_errno($cp);
		$error = curl_error($cp);
		curl_close($cp);
		
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

		$this -> errorInfo = $start_time . '--' . date('Y-m-d H:i:s  ') . $url . PHP_EOL . $param_str . PHP_EOL . json_encode(['error' => $error,'errno' => $errno]) . PHP_EOL . PHP_EOL;
		return $contents;
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
