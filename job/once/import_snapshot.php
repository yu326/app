<?php
include_once( 'includes.php' );
initLogger(LOGNAME_SYNC);
$logger;//记录日志的对象
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_NAME,FALSE);
function getTotalCount(){
	global $dsql,$logger;
	$totalCount = "select count(*) as totalcount from ".DATABASE_ELEMENT." where snapshot != ''";
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

function getSnapshotInfo($startnum, $pagesize){
	global $dsql,$logger;
	$limit = "";
	$startnum = empty($startnum) ? 0 : $startnum;
	if(!empty($pagesize)){
		$limit = " limit ".$startnum.",".$pagesize."";
	}
	$sql = "select  `instanceid`,`elementid`,`updatetime`,`content`,`snapshot` from ".DATABASE_ELEMENT." where snapshot != '' ".$limit."";
	$qr2 = $dsql->ExecQuery($sql);
	if(!$qr2){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
			//对快照数据进行处理facet结果添加上facet字段
			$con = json_decode($result['content'], true);
			$facetfield = array();
			if(isset($con["facet"]["field"]) && count($con["facet"]["field"]) > 0){
				$facetfield["name"] = $con["facet"]["field"][0]["name"];
			}
			else if(isset($con["facet"]["range"]) && count($con["facet"]["field"]) > 0){
				$facetfield["name"] = $con["facet"]["range"][0]["name"];
			}
			$snapshot = json_decode($result["snapshot"], true);
			if(!empty($snapshot)){
				if(!empty($facetfield)){
					foreach($snapshot as $si=>$sitem){
						$snapshot[$si]["facet"] = $facetfield;
					}
				}
			}
			$insql = "insert into ".DATABASE_SNAPSHOT_HISTORY." (`instanceid`,`elementid`,`updatetime`,`content`,`snapshot`) values (".$result["instanceid"].", ".$result["elementid"].", ".$result["updatetime"].", '".jsonEncode4DB(json_decode($result["content"]))."', '".jsonEncode4DB($snapshot)."') ";
            $qr = $dsql->ExecQuery($insql);
			if(!$qr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$insql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else{
				$getlastid = "select LAST_INSERT_ID() as id";
				$gqr = $dsql->ExecQuery($getlastid);
				if(!$gqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
				else{
					$lastid = $dsql->GetArray($gqr, MYSQL_ASSOC);
					$upsql = "update ".DATABASE_ELEMENT." set snapid = ".$lastid["id"]." where elementid = ".$result["elementid"]."";
					$upqr = $dsql->ExecQuery($upsql);
					if(!$upqr){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
						$arrs["flag"]=0;
					}
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
	$logger->info("设置为快照的elements有:".$totalcount);
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
	getSnapshotInfo($i, $persize);
	$gend_time = microtime_float();
	$getuser_time = $gend_time - $gstart_time;
	echo "per getSnapshotInfo time:".$getuser_time."\n";
}
$e_time = microtime_float();
$s_time = $e_time - $s_time;
echo "total time :".$s_time;
$logger->info("总时间:".$s_time);

