<?php
//检查404
function check404($url){
	set_time_limit(0);
	header("Expires: Mon, 26 Jul 1970 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	$handle = curl_init($url);
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($handle,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($handle, CURLOPT_TIMEOUT,10); 
	$response = curl_exec($handle);
	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	curl_close($handle);
	if($httpCode==200){
		//ok
	}else{
		//error
	}
}
?>
<script>
function check404(){
	var countc=0;
    var counts=0;//成功个数
	var counte=0;//错误个数
    $("#checkBtn").html("检查中...");
	$(".checkClass").each(function(n){
		var id=$(this).attr('id');
		$.ajax({
			url: './test.php?ac=ajax',
			type:'GET',
			dataType : 'jsonp',
			data : {id:id},
			success : function(data){   
			   if(data.status=='ok'){
				  $("#"+data.id).html("<font color='green'>无错链</font>");
				  counts=counts+1;
				}else if(data.status=='error'){
				  	$("#"+id).html("<font color='red'>有错链</font>");
					counte=counte+1;
				}else{
					$("#"+id).html("<font color='red'>未知错误</font>");
					counte=counte+1;
				}
				   countc=countc+1;
				   var bfc=Math.floor(countc/$(".checkClass").size()*100);
				   var bfs=Math.floor(counts/$(".checkClass").size()*100);
				   var bfe=Math.floor(counte/$(".checkClass").size()*100);
				  $("#checkBtn").html("查询进度："+bfc+"%,错链率："+bfe+"%");
			}
		}); 
	})
}
</script>