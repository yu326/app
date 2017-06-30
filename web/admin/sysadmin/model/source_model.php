<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");

initLogger(LOGNAME_WEBAPI);

session_start();

if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	echo json_encode($arrs);
	exit;
}
//function formatterSendData($senddata, $action, $sourceid, $businessid){
//	$tmpkey = $sourceid;
//	if(isset($senddata[$tmpkey])){
//		$senddata[$tmpkey]["words"][] = array("sourceid"=>$sourceid, "businessid"=>$businessid);
//	}
//	else{
//		$senddata[$tmpkey]["action"] = $action; //"update action, value in ('add','remove'), 'add' for add new or update existing"
//		$senddata[$tmpkey]["type"] = 16;//对应source表
//		$senddata[$tmpkey]["words"][] = array("sourceid"=>$sourceid, "businessid"=>$businessid);
//	}
//	return $senddata;
//}
////向solr发送请求
//function sou_send_to_solr($senddata){
//	global $logger;
//	$error = array();
//	foreach($senddata as $key => $value){
//		$logger->debug(__FUNCTION__." send_solr_value :".var_export($value,true)."");
//		$solr_r = send_solr($value,SOLR_URL_DICTIONARY);
//		if($solr_r === false){
//			$logger->error(__FUNCTION__." 调用 send_solr 返回false");
//			$error[] = "send solr error";
//			break;
//		}
//		else if(!empty($solr_r)){
//			if(isset($solr_r['error'])){ //字典添加出错
//				$error[] = $value;
//				$logger->error(__FUNCTION__." send_solr faild:{$solr_r['error']}");
//			}
//			else{
//				$logger->debug(__FUNCTION__." send_solr :".var_export($solr_r,true)."");
//			}
//		}
//		else{
//			$logger->error(__FUNCTION__." 调用send_solr 返回:{$solr_r}");
//			$error[] = "send solr error";
//		}
//	}
//	return $error;
//}
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getSourceList($startnum, $pagesize, $sou_sourcetype=NULL, $sou_business=NULL, $sou_name=NULL, $sou_id=NULL, $get_type="add"){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($sou_sourcetype!= NULL){ //类型(论坛,微博,SNS)
		$where[] =  "sourcetype = ".$sou_sourcetype."";
	}
	if($sou_business != NULL){
		$where[] = "business = ".$sou_business."";
	}
	if($sou_name!= NULL){
		$where[] = "name = '".$sou_name."'";
	}
	if($sou_id != NULL){
		if($get_type == "update"){ //修改时去重判断, 和其他的进行比较
			$where[] = "id != ".$sou_id."";
		}
		else{
			$where[] = "id = ".$sou_id."";
		}
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_SOURCE." ".$wherestr."";
	$qr = $dsql->ExecQuery($totalCount);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{

		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$arrs["totalcount"]=$result["totalcount"];
		}
		$limit = "";
		$startnum = empty($startnum) ? 0 : $startnum;
		if(!empty($pagesize)){
			$limit = " limit ".$startnum.",".$pagesize."";
		}
		$sql="select * from ".DATABASE_SOURCE." ".$wherestr." ".$limit."";
		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			$arrs["datalist"] = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["id"];
				//$temp_arr["source"] = $result["source"]; //来源主机域名
				$temp_arr["sourcetype"] = $result["sourcetype"]; //来源类型 类型(论坛,微博,SNS)
				$temp_arr["name"] = $result["name"]; //来源名称
				$temp_arr["business"] = $result["business"]; //所属行业
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
function getSourceHostList($startnum, $pagesize, $sou_id=NULL, $sou_source=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($sou_id!= NULL){
		$where[] = "sourceid = ".$sou_id."";
	}
	if($sou_source!= NULL){
		$where[] = "source = ".$sou_source."";
	}

	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_SOURCEURL." ".$wherestr."";
	$qr = $dsql->ExecQuery($totalCount);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$arrs["totalcount"]=$result["totalcount"];
		}
		$limit = "";
		$startnum = empty($startnum) ? 0 : $startnum;
		if(!empty($pagesize)){
			$limit = " limit ".$startnum.",".$pagesize."";
		}
		$sql="select * from ".DATABASE_SOURCEURL." ".$wherestr." ".$limit."";
		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["sourceid"];
				$temp_arr["source"] = $result["source"]; //来源主机域名
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectsourceinfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$sou_sourcetype = isset($_GET['sou_sourcetype']) ? $_GET['sou_sourcetype'] : NULL;
		$sou_business = isset($_GET['sou_business']) ? $_GET['sou_business'] : NULL;
		$r= getSourceList($startnum, $pagesize, $sou_sourcetype, $sou_business);
		echo json_encode($r);
	}
	else if('selectsourcehost' == $_GET["type"]){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$sou_id = $_GET['sourceid'];
		$r= getSourceHostList($startnum, $pagesize, $sou_id);
		echo json_encode($r);
	}
	else if('getstyletype' == $_GET['type']){
		$r = styletypelist();
		echo json_encode($r);
	}
}
else if(isset($HTTP_RAW_POST_DATA)){
    global $arrsdata, $logger;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
	//新增
	if($arg_type == "addsource"){
		$sou_id = $arrsdata["sou_id"];  
		$sou_sourcetype = $arrsdata["sou_sourcetype"];  
		$sou_name = $arrsdata["sou_name"];  
		$sou_business = $arrsdata["sou_business"];  

		$fieldname = array();
		$fieldvalue = array();
		if($sou_id != ""){
			$fieldname[] = "id";
			$fieldvalue[] = "'".$sou_id."'";
		}
		if($sou_sourcetype != -1){
			$fieldname[] = "sourcetype";
			$fieldvalue[] = $sou_sourcetype;
		}
		if($sou_name != ""){
			$fieldname[] = "name";
			$fieldvalue[] = "'".$sou_name."'";
		}
		if($sou_business != -1){
			$fieldname[] = "business";
			$fieldvalue[] = $sou_business;
		}
		$namestr = implode(", ", $fieldname); 
		$valuestr = implode(", ", $fieldvalue); 
		$senddata = array();
		$sql = "insert into ".DATABASE_SOURCE." (".$namestr.") values (".$valuestr.")";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $sou_name;
		}
		else{
			$sourceid = $dsql->GetLastID();
		}
		//向solr发送的数据
//		if($sou_business != -1){ //行业不能为空
//			$senddata = formatterSendData($senddata, 'add', $sourceid, $sou_business);
//		}

		if(count($failed) == 0){ //数据库添加成功
			$arr["flag"] = 1;
//			if(count($senddata) > 0){
//				$solr_r = sou_send_to_solr($senddata); //向solr发送
//				if(count($solr_r) > 0){
//					$arr["msg"] = "数据库添加成功,solr添加失败";
//				}
//			}
		}
		else{
			$arr["flag"] = 0;
			if(count($failed) > 0){
				$arr["msg"] = "数据 ". implode(", ", $failed) ." 数据库添加失败";
			}
		}
		echo json_encode($arr);
	}
	else if($arg_type == "updatesourceword"){ //修改
		$sou_oldid = $arrsdata["sou_oldid"];
		$sou_id = $arrsdata["sou_id"];
		$sou_sourcetype= $arrsdata["sou_sourcetype"] == -1 ? 'NULL' : $arrsdata["sou_sourcetype"];  
		$sou_name = $arrsdata["sou_name"];  
		$sou_business = $arrsdata["sou_business"] == -1 ? 'NULL' : $arrsdata["sou_business"];  

		$sql = "update ".DATABASE_SOURCE." set id = ".$sou_id.", sourcetype = '".$sou_sourcetype."', name = '".$sou_name."', business = ".$sou_business." where id = ".$sou_oldid."";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据失败!";
		}
		else{
			$arrs["flag"] = 1;
//			if($sou_business != 'NULL'){
//				$senddata = array();
//				$senddata = formatterSendData($senddata, 'add', $sou_id, $sou_business);
//				$solr_r = sou_send_to_solr($senddata); //向solr发送
//				if(count($solr_r) > 0){
//					$arrs["msg"] = "数据更新成功, solr更新失败!";
//				}
//			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == "deletevalueword"){
	//	$senddata = array();
		$sou_arr = $arrsdata["deldata"];
		foreach($sou_arr as $key => $value){
			$sou_id[] = $value["id"];
			$sou_business = empty($value["business"]) ? -1 : $value["business"];
	//		$senddata = formatterSendData($senddata, 'remove', $value["id"], $sou_business);
		}
		$idstr = implode(", ", $sou_id);
		$sql = "delete from ".DATABASE_SOURCE." where `id` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
		//	$solr_r = sou_send_to_solr($senddata); //向solr发送
			$arrs["flag"] = 1;
//			if(count($solr_r) > 0){
//				$arrs["msg"] = "数据删除成功, solr删除失败!";
//			}
			//同时删除sourceurl表对应 souceid 的域名
			$sqlurl = "delete from ".DATABASE_SOURCEURL." where `sourceid` in (".$idstr.")";
			$qrurl = $dsql->ExecQuery($sqlurl);
			if(!$qrurl){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$qrurl} ".$dsql->GetError());
				$arrs["flag"] = 0;
				$arrs["msg"] = "souceurl 表 删除失败!";
			}
			else{
				$arrs["flag"] = 1;
			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		$hasitem = false;
		$sou_oldid = isset($arrsdata["sou_oldid"]) ? $arrsdata["sou_oldid"] : NULL;
		$sou_id = isset($arrsdata["sou_id"]) ? $arrsdata["sou_id"] : NULL;
		$sid = NULL;
		if($sou_oldid != NULL && $sou_oldid == $sou_id){
			$sid = $sou_id;
			$get_type = "update";
		}
		else{
			$sid = $sou_id;
			$get_type = "add";

			$result = getSourceList(NULL, NULL, NULL, NULL, NULL, $sid, $get_type);
			$hasitem = false;
			if(isset($result["totalcount"]) && $result["totalcount"] > 0){
				$hasitem = true;
				$r["datalist"]["id"] = $result["datalist"];
			}
		}

		if($sou_oldid != NULL){
			$sid = $sou_oldid;
			$get_type = "update";
		}
		$resultname = getSourceList(NULL, NULL, NULL, NULL, $arrsdata["sou_name"], $sid, $get_type);
		if(isset($resultname["totalcount"]) && $resultname["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"]["name"] = $resultname["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
	else if($arg_type == "addsourcehost"){
		$sou_source = $arrsdata["sou_source"];  
		$sou_id = $arrsdata["sou_id"];  
		$fieldname = array();
		$fieldvalue = array();
		if($sou_id != ""){
			$fieldname[] = "sourceid";
			$fieldvalue[] = "'".$sou_id."'";
		}
		if($sou_source != ""){
			$fieldname[] = "source";
			$fieldvalue[] = "'".$sou_source."'";
		}
		$namestr = implode(", ", $fieldname); 
		$valuestr = implode(", ", $fieldvalue); 
		$senddata = array();
		$sql = "insert into ".DATABASE_SOURCEURL." (".$namestr.") values (".$valuestr.")";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $sou_source;
		}

		if(count($failed) == 0){ //数据库添加成功
			$arr["flag"] = 1;
		}
		else{
			$arr["flag"] = 0;
			if(count($failed) > 0){
				$arr["msg"] = "数据 ". implode(", ", $failed) ." 数据库添加失败";
			}
		}
		echo json_encode($arr);
	}
	else if($arg_type == "deletesourcehost"){
		$senddata = array();
		$sou_arr = $arrsdata["deldata"];
		foreach($sou_arr as $key => $value){
			$sou_id[] = "'".$value["source"]."'";
		}
		$idstr = implode(", ", $sou_id);
		$sql = "delete from ".DATABASE_SOURCEURL." where `source` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
			$arrs["flag"] = 1;
		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checksourcehostexist'){ //新增前检查 对应项是否存在
		$result = getSourceHostList(NULL, NULL, NULL, "'".$arrsdata["sou_source"]."'");
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}



