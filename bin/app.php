<?php
date_default_timezone_set('Asia/Shanghai');
define('APP_PATH', dirname(dirname(__FILE__)));
define('WWW_ROOT', dirname(dirname(dirname(__FILE__))));
define('T_AK', md5("www.test.com"));//注明来源API
define('BAI_AK', 'B0647dffff1956a28c234ce3081a35f0');//百度AK

/**
 * 获取Request的数据---接收数据
 */
function getRequestData()
{
    $content = file_get_contents('php://input');
    $data = json_decode($content, true);
    return $data;
}

/**
 * json形式响应---响应数据
 */
function jsonReturn($data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

/**
 * 判断通讯key
 */
$_DATA=array();
$_DATA=getRequestData();
if ($_DATA['ak'] != T_AK) {
	jsonReturn(array(
		'status' => 404,
		'msg' => 'ak验证失败'
	));
}

function setPageCache(){
	date_default_timezone_set('Asia/Shanghai');
	$interval = 60 * 60 * 5; // 6 hours 
	header ("Last-Modified: " . gmdate ('r', time())); 
	header ("Expires: " . gmdate ("r", (time() + $interval))); 
	header ("Cache-Control: max-age=$interval"); 
}

function arrToZimu($allCity){
	$menuArrs=array();
	$i=-1;
	sort($allCity);
	foreach($allCity as $keys=>$value){
		$first=substr($value['cityCode'],0,1);
		if($first!=$last){
			$i++;
			$j=0;
			$menuArrs[$i]['zimu']=strtoupper($first);
		}
		if($first==substr($value['cityCode'],0,1)){
			$menuArrs[$i]['list'][$j]=array('cityCode'=>$value['cityCode'],'cityName'=>$value['cityName']);
			$j++;
		}
		$last=substr($value['cityCode'],0,1);
	}	
	return $menuArrs;
}
//远程访问
class Request
{
	private static $obj;
	
    private $errmsg;

    private $resp;

	public static function obj(){
		if(class_exists("Request")&&!self::$obj){
			self::$obj = new Request();
		}
		return self::$obj;
	}
	
    /**
     * 获取错误原因
     *
     * @return string
     */
    public function getErrormsg()
    {
        return $this->errmsg;
    }

    /**
     * 获取响应结果数据
     *
     * @return array
     */
    public function getResp()
    {
        return $this->resp;
    }

    /**
     * 发送GET请求
     *
     * @param string $url
     *            请求的url
     * @return bool
     */
    public function doGet($url,$json=true)
    {
        $ch = curl_init();
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 执行HTTP请求
        curl_setopt($ch, CURLOPT_URL, $url);
        $res = curl_exec($ch);
		if($json){
			if ($res === false) {
				$this->errmsg = 'http请求出错';
				return false;
			}
			$resp = @json_decode($res, true);
			if (! is_array($resp)) {
				$this->errmsg = '接口异常';
				return false;
			}
			if ($resp['errNum'] == 0) {
				$this->resp = $resp;
				return true;
			} else {
				//$this->errmsg = $resp['errMsg'];
				$this->errmsg = '网络异常';
				return false;
			}
		}else{
			if($res){
				$this->resp = $res;
				return true;
			}else{
				$this->errmsg = '接口异常';
				return false;
			}	
		}
    }
}
require_once(APP_PATH."/bin/DB.class.php");
require_once(APP_PATH."/bin/FileCache.class.php");
class cache_m extends FileCache{}
function delcache($cacheName){
	//删除缓存
	if($_GET["clearcache"]=="yes"){
		cache_m::del($cacheName);
	}
}