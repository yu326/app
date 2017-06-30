<?php
/*
 * 第三方提交数据接口
 * {
 *     "type":"addarticle"/"adduser"/"calctrend"
 *     "ispartialdata":true/false //ispartialdata, 最终送的记录是否完整
 *     "isnested":true/false
 *     "trendtype":"repost_trend"/"comment_trend";
 *     "data":[]
 * }
 * 有依赖关系的数据需在一页,或在一批前提交, 例如:提交时需要保证父帖在子帖的前边
 * 三组例子,每组里有多条原创
 */
ini_set('include_path', realpath('../../php_config'));
require_once('config.php');
ini_set('include_path', realpath('../../php_common_includes'));
require_once('common.php');
require_once('database_config.php');
require_once('db_mysql.class.php');
initLogger(LOGNAME_WEBAPI);
$sdata = json_decode($HTTP_RAW_POST_DATA,true);
if(empty($sdata)){
	$logger->error(__FILE__.__LINE__." before json_decode ".$HTTP_RAW_POST_DATA);
	$logger->error(__FILE__.__LINE__." after json_decode ".var_export($sdata, true));
	$result['result'] = false; 
	$result['msg'] = "josn error"; 
	echo json_encode($result);
	exit;
}
if(isset($sdata['type'])){
	$type = isset($sdata['type']) ? $sdata['type'] : "addarticle";
	$ispartialdata = isset($sdata['ispartialdata']) ? $sdata['ispartialdata'] : false;
	$isnested = isset($sdata['isnested']) ? $sdata['isnested'] : false;
	$issegmented = isset($sdata['issegmented']) ? $sdata['issegmented'] : true;
	$trendtype = isset($sdata['trendtype']) ? $sdata['trendtype'] : 'comment_trend';
	$data = isset($sdata['data']) ? $sdata['data'] : "";
}
else{ //送来多条数据
	$type = isset($sdata[0]['type']) ? $sdata[0]['type'] : "addarticle";
	$ispartialdata = isset($sdata[0]['ispartialdata']) ? $sdata[0]['ispartialdata'] : false;
	$isnested = isset($sdata[0]['isnested']) ? $sdata[0]['isnested'] : false;
	$issegmented = isset($sdata[0]['issegmented']) ? $sdata[0]['issegmented'] : true;
	$trendtype = isset($sdata[0]['trendtype']) ? $sdata[0]['trendtype'] : 'comment_trend';
	$tmpdata = array();
	foreach($sdata as $si=>$sitem){
		$tmpdata[] = $sitem['data'][0];
	}
	$logger->debug(__FILE__.__LINE__." tmpdata ".var_export($tmpdata, true));
	$data = $tmpdata;
}
if(empty($data)){
	$result['result'] = false; 
	$result['msg'] = "data is empty";
	echo json_encode($result);
	exit;
}
$result = array();
if($type == "addarticle"){
	$dataobj = array();
	$dataobj['ispartialdata'] = $ispartialdata;
	$dataobj['data'] = $data;
	$logger->info('before insert article nested:'.$isnested.'');
	$s_time = microtime_float();
	if($isnested){
		$res = insert_nested_data($dataobj, $issegmented, '3rd');
	}
	else{
		$res = insert_data($dataobj, $issegmented, '3rd');
	}
	$e_time = microtime_float();
	$logger->info('after insert article this round cost '.($e_time - $s_time).'');
	if($res['result'] !== true){
		$result['result'] = $res['result']; 
		$result['msg'] = $res['msg']; 
	}
	else{
		$result['result'] = true;
		$result['msg'] = formatStatisticsInfo($statistics_info);
	}
}
else if($type == "adduser"){
	$logger->info('before insert user');
	$s_time = microtime_float();
	$res = insert_user($data, NULL, NULL, 0, true, NULL, true, $issegmented, '3rd');
	$e_time = microtime_float();
	$logger->info('after insert user this round cost '.($e_time - $s_time).'');
	if($res['result'] !== true){
		$result['result'] = $res['result']; 
		$result['msg'] = $res['msg']; 
	}
	else{
		$result['result'] = true;
		$result['msg'] = formatStatisticsInfo($statistics_info);
	}
}
else if($type == "calctrend"){
	$ndata = array();
	foreach($data as $di=>$ditem){
		$tmpdata = array();
		$tmpdata['source_host'] = get_host_from_url($ditem['url']);
		$mid = weiboUrl2mid($ditem['url']);
		if(!empty($mid)){
			$tmpdata['mid'] = $mid;
		}
		else{
			$tmpdata['original_url'] = $ditem['url'];
			$tmpdata['floor'] = 0;
			$tmpdata['paragraphid'] = 0;
		}
		$ndata[] = $tmpdata;
	}
	$logger->info('before calctrend');
	$s_time = microtime_float();
	$res = calcTrendPath($trendtype, $ndata, true);
	$e_time = microtime_float();
	$logger->info('after caltrend this round cost '.($e_time - $s_time).'');
	$result['result'] = $res['result']; 
	$result['msg'] = $res['msg']; 
}
echo json_encode($result);
exit;
