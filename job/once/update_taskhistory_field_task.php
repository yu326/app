<?php
include_once( 'includes.php' );
initLogger(LOGNAME_SYNC);
$logger;//记录日志的对象
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getTotalCount(){
	global $dsql,$logger;
	$totalCount = "select count(*) as totalcount from ".DATABASE_TASKHISTORY." where tasktype = ".TASKTYPE_UPDATE." and task = ".TASK_SNAPSHOT."";
	$qr = $dsql->ExecQuery($totalCount);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$arrs["totalcount"]=$result["totalcount"];
		}
	}
	return $arrs;
}
function getTaskHistoryInfo($startnum, $pagesize){
	global $dsql,$logger;
	$limit = "";
	$startnum = empty($startnum) ? 0 : $startnum;
	if(!empty($pagesize)){
		$limit = " limit ".$startnum.",".$pagesize."";
	}
	$sql = "select id, task, taskparams from ".DATABASE_TASKHISTORY." where tasktype = ".TASKTYPE_UPDATE." and task = ".TASK_SNAPSHOT." ".$limit."";
	$qr2 = $dsql->ExecQuery($sql);
	if(!$qr2){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
			$taskparams = json_decode($result["taskparams"], true);
			if(isset($taskparams["eventlist"])){
				$upsql = "update ".DATABASE_TASKHISTORY." set task = ".TASK_EVENTALERT." where id= ".$result["id"]."";
				$upqr = $dsql->ExecQuery($upsql);
				if(!$upqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
			}
		}
		$dsql->FreeResult($qr2);
		$arrs["flag"]=1;
	}
	return $arrs;
}
echo "start";
$tmptotalcount = getTotalCount(); 
if(isset($tmptotalcount["totalcount"])){
	$totalcount = $tmptotalcount["totalcount"];
	$logger->info("历史任务条数:".$totalcount);
	echo "total num ".$totalcount."\n";
}
else{
	echo "获取总数失败!";
	exit;
}
//每次取值条数
$persize = 1000;
//test
//$totalcount = 4;
//$persize = 2;
//test end
//循环读取element表, 每次取1000条
$s_time = microtime_float();
for($i=0;$i< $totalcount; $i+=$persize ){
	echo "i ".$i."\n";
	$gstart_time = microtime_float();
	getTaskHistoryInfo($i, $persize);
	$gend_time = microtime_float();
	$getuser_time = $gend_time - $gstart_time;
	echo "per getTaskHistoryInfo time:".$getuser_time."\n";
}
$e_time = microtime_float();
$s_time = $e_time - $s_time;
echo "total time :".$s_time;
$logger->info("总时间:".$s_time);

