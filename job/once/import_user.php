<?php
include_once( 'includes.php' );
initLogger(LOGNAME_SYNC);
$logger;//记录日志的对象
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getTotalCount(){
	global $dsql,$logger;
	$totalCount = "select count(*) as totalcount from ".DATABASE_USER."";
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

function getUserInfo($startnum, $pagesize){
	global $dsql,$logger;
	$limit = "";
	$startnum = empty($startnum) ? 0 : $startnum;
	if(!empty($pagesize)){
		$limit = " limit ".$startnum.",".$pagesize."";
	}
	$sql = "select `id` as users_id,`screen_name` as users_screen_name,`name` as users_name,`province` as users_province,`city` as users_city,`location` as users_location,`description`,`url` as users_url,`profile_image_url` as users_profile_image_url,`domain` as users_domain,`gender` as users_gender,`followers_count` as users_followers_count,`friends_count` as users_friends_count,`statuses_count` as users_statuses_count,`favourites_count` as users_favourites_count,`created_at` as users_created_at,`allow_all_act_msg` as users_allow_all_act_msg,`geo_enabled` as users_geo_enabled,`verified` as users_verified,`taginfo` as users_taginfo,`trendinfo` as users_trendinfo,`user_updatetime` as users_user_updatetime,`is_celebrity_friend` as users_is_celebrity_friend,`is_celebrity_follower` as users_is_celebrity_follower,`is_bridge_user` as users_is_bridge_user,`bridge_count` as users_bridge_count,`country_code` as users_country_code,`province_code` as users_province_code,`city_code` as users_city_code,`district_code` as users_district_code,`get_type` as users_get_type,`sourceid`,`seeduser` as users_seeduser,`verified_reason`,`verified_type` as users_verified_type from ".DATABASE_USER." ".$limit."";
	$qr2 = $dsql->ExecQuery($sql);
	if(!$qr2){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
			$temp_arr = $result;
			//$temp_arr["guid"] = $result["sourceid"]."u_".$result["users_id"];
			$temp_arr["guid"] = getUserGuidOrMore($result);
			if($temp_arr["guid"] === false){
				$logger->error("查询用户guid失败");
				$arrs["flag"]=0;
			}
			//添加关注,根据用户ID查出对应的关注
			//$fstart_time = microtime_float();
			$friendssql = "select userID from user_followers where followerID = '".$result["users_id"]."'";
			$friendsqr = $dsql->ExecQuery($friendssql);
			//$fend_time = microtime_float();
			//$get_friends_time = $fend_time - $fstart_time;
			//$logger->info("查 followerID=".$result["users_id"]."关注,使用时间:".$get_friends_time);
			//echo "per getfriend ".$result["users_id"]." use time:".$get_friends_time."\n";

			if(!$friendsqr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$friendssql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else{
				$farr = array();
				while($fr = $dsql->GetArray($friendsqr, MYSQL_ASSOC)){
					$farr[] = $fr["userID"];
				}
				$temp_arr["users_friends_id"] = $farr;
			}
			$dsql->FreeResult($friendsqr);
			$arrs["datalist"][] = $temp_arr;
		}
		$dsql->FreeResult($qr2);
		$arrs["flag"]=1;
	}
	return $arrs;
}

//从数据库取得用户信息导入solr
//分词字段
$tokenfields = array("description","verified_reason");
$tmptotalcount = getTotalCount(); 
if(isset($tmptotalcount["totalcount"])){
	$totalcount = $tmptotalcount["totalcount"];
	$logger->info("用户表总条数:".$totalcount);
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
//循环读取users_new表, 每次取1000条
$s_time = microtime_float();
for($i=0;$i< $totalcount; $i+=$persize ){
	echo "i ".$i."\n";
	$getres = array();
	$gstart_time = microtime_float();
	$getres = getUserInfo($i, $persize);
	$gend_time = microtime_float();
	$getuser_time = $gend_time - $gstart_time;
	$logger->info("单次分词".$persize."条,使用时间:".$getuser_time);
	echo "per getuserinfo time:".$getuser_time."\n";

	$solr_users_info = array();
	$solr_users_info = $getres["datalist"];
	//$logger->info(__FUNCTION__." solr_analysis data:".var_export($solr_users_info,true));
	//分词
	$start_time = microtime_float();
	$ana_result = solr_analysis($solr_users_info,$tokenfields);//分析微博
	$end_time = microtime_float();
	$solr_analysistime = $end_time - $start_time;
	$logger->info("单次分词".$persize."条,使用时间:".$solr_analysistime);
	echo "per analysis time:".$solr_analysistime."\n";
	if(!$ana_result){ //分词失败
		$logger->error(__FUNCTION__." solr_analysis failed:".var_export($ana_result,true));
	}
	else{
		formatStoreData($solr_users_info, $ana_result,$tokenfields);//生成存储数据
		//$logger->info(__FUNCTION__." formatStoreData:".var_export($solr_users_info,true));
	}
	unset($ana_result);
	//插入solr
	//插入数据之前,更改 description as users_description, verified_reason as users_verified_reason, sourceid as  users_sourceid
	$solrU = array();
	foreach($solr_users_info as $si=>$sitem){
		$tmpArr = array();
		foreach($sitem as $ti=>$titem){
			if(isset($titem)){
				switch($ti){
				case "description":
					$tmpArr["users_description"] = $titem;
					break;
				case "verified_reason":
					$tmpArr["users_verified_reason"] = $titem;
					break;
				case "sourceid":
					$tmpArr["users_sourceid"] = $titem;
					break;
				default:
					$tmpArr[$ti] = $titem;
					break;
				}
			}
		}
		$solrU[] = $tmpArr;
	}
	$solr_users_info = $solrU;
	//$logger->info(__FUNCTION__." formatStoreData:".var_export($solr_users_info,true));
	$solrerrorcount = 0;
	$start_time = microtime_float();
	$url = SOLR_URL_INSERT;
	$url .= "&commit=true";
	$result = handle_solr_data($solr_users_info, $url);
	$end_time = microtime_float();
	$analysisdiff = $end_time-$start_time;
	//$analysistime += $analysisdiff;
	if($result === false){
		$logger->error("insert_users 调用solr返回false data is : ".json_encode($solr_users_info));
		$errorcount = count($solr_users_info);
		$solrerrorcount += $errorcount;
		$strresult = "返回false";        
	}
	else if($result === NULL){
		$result = true;
		$strresult = "成功";
	}
	else {
		$logger->error("insert_status 调用solr失败的".var_export($result,true));
		$errorcount = count($result);
		$strresult = "失败{$errorcount}条";
		$solrerrorcount += $errorcount;
	}
	unset($solr_users_info);
	$logger->info("单次插入".$persize."条,{$strresult},使用时间:".$analysisdiff);
	echo "per insert time:".$analysisdiff."\n";
}
$e_time = microtime_float();
$s_time = $e_time - $s_time;
echo "total time :".$s_time;
$logger->info("总时间:".$s_time);

