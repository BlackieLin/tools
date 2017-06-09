<?php
//setPageCache();//设置6个小时的浏览器缓存
include './bin/app.php';
$mod=$_GET['mod'];
$data=array(
	'status'=>200,
	'msg'=>'ok',
	'result'=>array()
);
$cacheTime='86400';//24小时


if($mod=="getcity"){
	$result=array();
	$wd=$_GET["wd"];
	$jd=$_GET["jd"];
	$url='http://api.map.baidu.com/geocoder/v2/?location='.$wd.','.$jd.'&output=json&ak=EjZyppeeknuoyKvqPSzg3iBdDCHzekkE&v=2.0';
	$result=cache_m::load($url);
	if(!is_array($result) || empty($result)){
		if(Request::obj()->doGet($url)){
			$result=Request::obj()->getResp();
			cache_m::save($result,$url,$cacheTime);
			if($result['status']==0&&isset($result['result'])){
				$data['result']=$result['result'];
			}else{
				$data['status']=404;
				$data['msg']='抱歉，经纬度解析出错';	
			}
		}else{
			$data['status']=404;
			$data['msg']='抱歉，网络请求错误';
		}
	}else{
		$data['result']=$result['result'];//读取缓存	
	}
}else if($mod=="nearby"){
	$data['result']=DB::obj()->query("select * from t_seller where 1=1");
}else if($mod=="list"){
	$seller_id=intval($_GET["seller_id"]);
	if($seller_id){
		$data['result']=DB::obj()->query("select * from t_goods where seller_id=".$seller_id);
	}else{
		$data['result']=array();
	}
}else if($mod=="show"){
	$id=intval($_GET["id"]);
	if($id){
		$tempArr=array();
		$tempArr=DB::obj()->query("select * from t_goods where id=".$id);
		if(!empty($tempArr)){
			$data['result']=$tempArr[0];	
		}else{
			$data['result']=array();	
		}
	}else{
		$data['result']=array();
	}
}else if($mod=="cOrder"){
	$_POST=getRequestData();
	$result=array();
	$result['openid']=md5($_POST['openid']);
	$result['goods_id']=intval($_POST['goods_id']);
	$result['pay_money']=htmlspecialchars(trim($_POST['pay_money']));
	$result['name']=htmlspecialchars(trim($_POST['name']));
	$result['tel']=htmlspecialchars(trim($_POST['tel']));
	$result['address']=htmlspecialchars(trim($_POST['address']));
	$result['beizhu']=htmlspecialchars(trim($_POST['beizhu']));
	$result['status']=1;
	if($result['pay_money']&&$result['openid']){
		$datas=array(
			'openid'=>$result['openid'],
			'goods_id'=>$result['goods_id'],
			'pay_money'=>$result['pay_money'],
			'name'=>$result['name'],
			'tel'=>$result['tel'],
			'address'=>$result['address'],
			'beizhu'=>$result['beizhu'],
			'status'=>$result['status'],
		);
		$flag=DB::obj()->insert("t_order",$datas);
	}
}else if($mod=="order"){
	$open_id=md5($_GET["openid"]);
	$result=array();
	if($_GET["openid"]){
		$data['result']=DB::obj()->query("select * from t_order where openid='".$open_id."'");
	}else{
		$data['result']=array();
	}
}else{
	$data['result']=array(
		'id'=>1,
		'name'=>'this is test file'
	);
}

jsonReturn($data);

?>