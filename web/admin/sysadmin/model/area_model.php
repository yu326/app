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
function formatterSendData($senddata, $action, $area_name, $area_country, $area_province="", $area_city="", $area_district=""){
	$senddata["action"] = $action; //"update action, value in ('add','remove'), 'add' for add new or update existing"
	$senddata["type"] = 2;//对应area表
	$senddata["words"][] = array("area"=>$area_name, "country"=>$area_country, "province"=>$area_province, "city"=>$area_city, "district"=>$area_district);
	$senddata["pos"] = "NR"; //词性
	return $senddata;
}
//向solr发送请求
function area_send_to_solr($senddata){
	global $logger;
	$error = array();
	$solr_r = send_solr($senddata,SOLR_URL_DICTIONARY);
	if($solr_r === false){
		$logger->error(__FUNCTION__." 调用 send_solr 返回false");
		$error[] = "send solr error";
	}
	else if(!empty($solr_r)){
		if(isset($solr_r['error'])){ //字典添加出错
			$error[] = $senddata;
			$logger->error(__FUNCTION__." send_solr faild:{$solr_r['error']}");
		}
		else{
			$logger->debug(__FUNCTION__." send_solr :".var_export($solr_r,true)."");
		}
	}
	else{
		$logger->error(__FUNCTION__." 调用send_solr 返回:{$solr_r}");
		$error[] = "send solr error";
	}
	return $error;
}
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getAreaList($startnum, $pagesize, $area_country=NULL, $area_province=NULL, $area_city=NULL, $area_district=NULL, $area_code=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($area_country!= NULL){
		$where[] =  "country = '".$area_country."'";
	}
	if($area_province!= NULL){
		$where[] = "province = '".$area_province."'";
	}
	if($area_city!= NULL){
		$where[] = "city = '".$area_city."'";
	}
	if($area_district!= NULL){
		$where[] = "district = '".$area_district."'";
	}
	if($area_code!= NULL){
		$where[] = "area_code = '".$area_code."'";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	$totalCount = "select count(*) as totalcount from ".DATABASE_AREA." ".$wherestr."";
	$qr = $dsql->ExecQuery($totalCount);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$totalCount->GetError());
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
		$sql="select * from ".DATABASE_AREA." ".$wherestr." ".$limit."";

		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["area_code"] = $result["area_code"];
				$temp_arr["country"] = $result["country"]; 
				$temp_arr["province"] = $result["province"]; 
				$temp_arr["city"] = $result["city"];
				$temp_arr["district"] = $result["district"]; 
				$temp_arr["name"] = $result["name"]; 
				$temp_arr["another_name"] = $result["another_name"]; 
				$temp_arr["short_name"] = $result["short_name"]; 
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectareainfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$area_country= isset($_GET['area_country']) ? $_GET['area_country'] : NULL;
		$area_province = isset($_GET['area_province']) ? $_GET['area_province'] : NULL;
		$area_city = isset($_GET['area_city']) ? $_GET['area_city'] : NULL;
		$r= getAreaList($startnum, $pagesize, $area_country, $area_province, $area_city);
		echo json_encode($r);
	}
}
else if(isset($HTTP_RAW_POST_DATA)){
    global $arrsdata;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
	//新增
	if($arg_type == "addarea"){
		$area_code = $arrsdata["area_code"];  
		$area_country = $arrsdata["area_country"];
		$area_province = isset($arrsdata["area_province"])?$arrsdata["area_province"]:"";
		$area_city = isset($arrsdata["area_city"])?$arrsdata["area_city"]:"";
		$area_district = isset($arrsdata["area_district"])?$arrsdata["area_district"]:"";
		$area_name = $arrsdata["area_name"];
		$area_another_name = $arrsdata["area_another_name"];
		$area_short_name = $arrsdata["area_short_name"];

		$fieldname = array();
		$fieldvalue = array();
		if($arrsdata["area_code"] != NULL){
			$fieldname[] = "area_code";
			$fieldvalue[] = "'".$arrsdata["area_code"]."'";
		}
		if($arrsdata["area_country"] != NULL){
			$fieldname[] = "country";
			$fieldvalue[] = "'".$arrsdata["area_country"]."'";
		}
		if(!empty($arrsdata["area_province"])){
			$fieldname[] = "province";
			$fieldvalue[] = "'".$arrsdata["area_province"]."'";
		}
		if(!empty($arrsdata["area_city"])){
			$fieldname[] = "city";
			$fieldvalue[] = "'".$arrsdata["area_city"]."'";
		}
		if(!empty($arrsdata["area_district"])){
			$fieldname[] = "district";
			$fieldvalue[] = "'".$arrsdata["area_district"]."'";
		}
		if($arrsdata["area_name"] != NULL){
			$fieldname[] = "name";
			$fieldvalue[] = "'".$arrsdata["area_name"]."'";
		}
		if($arrsdata["area_another_name"] != NULL){
			$fieldname[] = "another_name";
			$fieldvalue[] = "'".$arrsdata["area_another_name"]."'";
		}
		if($arrsdata["area_short_name"] != NULL){
			$fieldname[] = "short_name";
			$fieldvalue[] = "'".$arrsdata["area_short_name"]."'";
		}
		$fieldnamestr = implode(", ", $fieldname);
		$fieldvaluestr = implode(", ", $fieldvalue);
		$senddata = array();
		$sql = "insert into ".DATABASE_AREA." (".$fieldnamestr.") values (".$fieldvaluestr.")";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $area_name;
		}
		//向solr发送的数据
		$senddata = formatterSendData($senddata, 'add', $area_name, $area_country, $area_province, $area_city, $area_district);
		if(!empty($area_another_name)){
			$senddata = formatterSendData($senddata, 'add', $area_another_name, $area_country, $area_province, $area_city, $area_district);
		}
		if(!empty($area_short_name)){
			$senddata = formatterSendData($senddata, 'add', $area_short_name, $area_country, $area_province, $area_city, $area_district);
		}

		if(count($failed) == 0){ //数据库添加成功
			$solr_r = area_send_to_solr($senddata); //向solr发送
			$arr["flag"] = 1;
			if(count($solr_r) > 0){
				$arr["msg"] = "数据库添加成功,solr添加失败";
			}
		}
		else{
			$arr["flag"] = 0;
			if(count($failed) > 0){
				$arr["msg"] = "数据 ". implode(", ", $failed) ." 数据库添加失败";
			}
		}
		echo json_encode($arr);
	}
	else if($arg_type == "deletevalueword"){
		$send_arr = array();
		$senddata = array();
		$area_arr = $arrsdata["deldata"];
		foreach($area_arr as $key => $value){
			$area_code[] = "'".$value["area_code"]."'";
			$province = empty($value["province"])?"":$value["province"];
			$city = empty($value["city"])?"":$value["city"];
			$district = empty($value["district"])?"":$value["district"];
			$senddata = formatterSendData($senddata, 'remove', $value["name"], $value["country"], $province, $city, $district);
			if($value["another_name"] != NULL){
				$senddata = formatterSendData($senddata, 'remove', $value["another_name"], $value["country"], $province, $city, $district);
			}
			if($value["short_name"] != NULL){
				$senddata = formatterSendData($senddata, 'remove', $value["short_name"], $value["country"], $province, $city, $district);
			}
			$send_arr[] = $senddata;
			unset($senddata);
			$senddata = array();
		}
		$idstr = implode(", ", $area_code);
		$sql = "delete from ".DATABASE_AREA." where `area_code` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
			foreach($send_arr as $senddata){
				$solr_r = area_send_to_solr($senddata); //向solr发送
				$arrs["flag"] = 1;
				if(count($solr_r) > 0){
					$arrs["msg"] = "数据删除成功, solr删除失败!";
				}
			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		$result = getAreaList(NULL, NULL, NULL, NULL, NULL, NULL, $arrsdata["area_code"]);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}



