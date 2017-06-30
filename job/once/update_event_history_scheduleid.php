<?php
/*
 * 这个脚本执行一次
 *
 * */
include_once( 'includes.php' );
initLogger(LOGNAME_SYNC);
$logger;//记录日志的对象
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_NAME,FALSE);
function getTotalCount(){
	global $dsql,$logger;
	$totalCount = "select count(*) as totalcount from ".DATABASE_EVENT_HISTORY."";
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
function getSnapshotScheduleAll($instanceid, $triggertime=NULL)
{
	global $dsql, $logger;
	$result = null;
	$limitcursor = 0;
	$eachcount = 10;
	while(1){
		$where = array();
		$where[] = "tasktype = ".TASKTYPE_UPDATE."";
		$where[] = "task = ".TASK_SNAPSHOT."";
		$wherestr = "";
		if(count($where) > 0){
			$wherestr = " where ".implode(" and ", $where);
		}
		$sql = "select * from ".DATABASE_WEIBOINFO.".".DATABASE_TASKHISTORY." ".$wherestr." order by id limit {$limitcursor},{$eachcount}";
		$logger->debug(__FILE__.__LINE__." sql ".var_export($sql, true));

		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FUNCTION__." - sql:{$sql} - ".$dsql->GetError());
			$result = false;
			break;
		}
		else{
			$r_count = $dsql->GetTotalRow($qr);
			if($r_count == 0){
				break;
			}
            while($sched = $dsql->GetObject($qr)){
            	$sched->taskparams = json_decode($sched->taskparams);
				if($instanceid != null){
					if($sched->taskparams->spawntime == $triggertime){
						if(!isset($sched->taskparams->scheduleid)){
							$tmpremark = $sched->remarks;
							//#创建自定时任务#48#测试计划描述
							preg_match_all ("/#(.*)#(\d*)#(.*)$/", $tmpremark, $matches);
							$sched->taskparams->scheduleid = $matches[2][0];
						}
						$result = $sched;
						break 2;
					}
				}
				else{
					$result[] = $sched->taskparams;
				}
            }
            $dsql->FreeResult($qr);
		}
		if($r_count < $eachcount){
        	break;
		}
		$limitcursor += $eachcount;
	}
	return $result;
}
function getEventHistoryInfo($startnum, $pagesize){
	global $dsql,$logger;
	$limit = "";
	$startnum = empty($startnum) ? 0 : $startnum;
	if(!empty($pagesize)){
		$limit = " limit ".$startnum.",".$pagesize."";
	}
	$sql = "select `triggertime`, `instanceid`, elementid from ".DATABASE_EVENT_HISTORY." ".$limit."";
	$qr2 = $dsql->ExecQuery($sql);
	if(!$qr2){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
			$sched = getSnapshotScheduleAll($result["instanceid"], $result["triggertime"]);
			if($sched != null){
				$upsql = "update ".DATABASE_EVENT_HISTORY." set scheduleid = ".$sched->taskparams->scheduleid." where triggertime = ".$result["triggertime"]." and instanceid = ".$result["instanceid"]."";
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
	$logger->info("事件历史条数:".$totalcount);
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
	getEventHistoryInfo($i, $persize);
	$gend_time = microtime_float();
	$getuser_time = $gend_time - $gstart_time;
	echo "per getEventHistoryInfo time:".$getuser_time."\n";
}
$e_time = microtime_float();
$s_time = $e_time - $s_time;
echo "total time :".$s_time;
$logger->info("总时间:".$s_time);

