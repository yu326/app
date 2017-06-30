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
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getSpideraccountList($startnum, $pagesize, $account_sourceid=NULL, $account_username=NULL, $account_password=NULL, $account_id=NULL, $get_type="add"){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($account_id != NULL){
		if($get_type == "update"){
			$where[] = "id != ".$account_id."";
		}
		else{
			$where[] = "id = ".$account_id."";
		}
	}
	if($account_sourceid!= NULL){
		$where[] = "sourceid = ".$account_sourceid."";
	}
	if($account_username != NULL){
		$where[] = "username = '".$account_username."'";
	}
	if($account_password != NULL){
		$where[] = "password = '".myEncrypt($account_password)."'";
	}

	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_SPIDERACCOUNT." ".$wherestr."";
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
		$sql="select * from ".DATABASE_SPIDERACCOUNT." ".$wherestr." ".$limit."";
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
				$temp_arr["sourceid"] = $result["sourceid"]; //帐号来源
				$temp_arr["username"] = $result["username"]; //用户名
				$temp_arr["inuse"] = $result["inuse"]; //用户名
				$temp_arr["password"] = myDecrypt($result["password"]); //密码
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectspideraccountinfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$account_sourceid = isset($_GET['account_sourceid']) ? $_GET['account_sourceid'] : NULL;
		$r= getSpideraccountList($startnum, $pagesize, $account_sourceid);
		echo json_encode($r);
	}
	else if('selectaccountbysourceid' == $_GET['type']){
		$account_sourceid = isset($_GET['account_sourceid']) ? $_GET['account_sourceid'] : NULL;
		$r= getSpideraccountList(NULL, NULL, $account_sourceid);
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
	if($arg_type == "addspideraccount"){
		$account_sourceid = $arrsdata["account_sourceid"];  
		$account_username = $arrsdata["account_username"];  
		$account_password = myEncrypt($arrsdata["account_password"]);  

		$fieldname = array();
		$fieldvalue = array();
		if($account_sourceid != ""){
			$fieldname[] = "sourceid";
			$fieldvalue[] = $account_sourceid;
		}
		if($account_username != ""){
			$fieldname[] = "username";
			$fieldvalue[] = "'".$account_username."'";
		}
		if($account_password != ""){
			$fieldname[] = "password";
			$fieldvalue[] = "'".addslashes($account_password)."'";
		}

		$namestr = implode(", ", $fieldname); 
		$valuestr = implode(", ", $fieldvalue); 
		$senddata = array();
		$sql = "insert into ".DATABASE_SPIDERACCOUNT." (".$namestr.") values (".$valuestr.")";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $account_username;
		}
		else{
			$sourceid = $dsql->GetLastID();
		}

		if(count($failed) == 0){ //数据库添加成功
			$arr["flag"] = 1;
			$arrs["msg"] = "数据更新成功!";
		}
		else{
			$arr["flag"] = 0;
			if(count($failed) > 0){
				$arr["msg"] = "数据 ". implode(", ", $failed) ." 数据库添加失败";
			}
		}
		echo json_encode($arr);
	}
	else if($arg_type == "updatespideraccount"){ //修改
		$account_id = $arrsdata["account_id"];
		$account_sourceid = $arrsdata["account_sourceid"];
		$account_username = $arrsdata["account_username"];  
		$account_password = myEncrypt($arrsdata["account_password"]);

		$sql = "update ".DATABASE_SPIDERACCOUNT." set sourceid = ".$account_sourceid.", username = '".$account_username."', password = '".addslashes($account_password)."' where id = ".$account_id."";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据失败!";
		}
		else{
			$arrs["flag"] = 1;
			$arrs["msg"] = "数据更新成功!";
		}
		echo json_encode($arrs);
	}
	else if($arg_type == "deletespideraccount"){
		$account_arr = $arrsdata["deldata"];
		foreach($account_arr as $key => $value){
			$account_id[] = $value["id"];
		}
		$idstr = implode(", ", $account_id);
		$sql = "delete from ".DATABASE_SPIDERACCOUNT." where `id` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
			$arrs["flag"] = 1;
			$arrs["msg"] = "数据删除成功!";
		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		$hasitem = false;
		$account_id = isset($arrsdata["account_id"]) ? $arrsdata["account_id"] : NULL;
		$account_sourceid = isset($arrsdata["account_sourceid"]) ? $arrsdata["account_sourceid"] : NULL;
		$get_type = $account_id == NULL ? "add" : "update";
		if(isset($arrsdata["account_username"])){
			$resultdb = getSpideraccountList(NULL, NULL, $account_sourceid, $arrsdata["account_username"], NULL , $account_id, $get_type);
			if(isset($resultdb["totalcount"]) && $resultdb["totalcount"] > 0){
				$hasitem = true;
				$r["datalist"]["username"] = $resultdb["datalist"];
			}
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}
