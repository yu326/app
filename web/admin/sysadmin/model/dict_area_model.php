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
function getDictAreaList($startnum, $pagesize, $dict_area_cond1=NULL, $dict_area_cond2=NULL, $dict_area_cond3=NULL, $dict_area_cond4=NULL, $area_code=NULL, $querytype='query'){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	$iwhere = array();
	if($dict_area_cond1!= NULL){
		$iwhere[] = $dict_area_cond1;
		if($querytype == 'checkexist'){
			$pos = strpos($dict_area_cond1, '*');
			if($pos === false){
				$where[] =  "cond1 = '".$dict_area_cond1."'";
			}
			else{
				$dict_area_cond1 = str_replace("*", "%", $dict_area_cond1);       
				$where[] =  "cond1 like '".$dict_area_cond1."'";
			}
		}
		else{
			$where[] =  "cond1 like '%".$dict_area_cond1."%'";
		}
	}
	if($dict_area_cond2!= NULL){
		$iwhere[] = $dict_area_cond2;
		if($querytype == 'checkexist'){
			$pos = strpos($dict_area_cond2, '*');
			if($pos === false){
				$where[] =  "cond2 = '".$dict_area_cond2."'";
			}
			else{
				$dict_area_cond2 = str_replace("*", "%", $dict_area_cond2);       
				$where[] =  "cond2 like '".$dict_area_cond2."'";
			}
		}
		else{
			$where[] =  "cond2 like '%".$dict_area_cond2."%'";
		}
	}
	if($dict_area_cond3!= NULL){
		$iwhere[] = $dict_area_cond3;
		if($querytype == 'checkexist'){
			$pos = strpos($dict_area_cond3, '*');
			if($pos === false){
				$where[] =  "cond3 = '".$dict_area_cond3."'";
			}
			else{
				$dict_area_cond3 = str_replace("*", "%", $dict_area_cond3);       
				$where[] =  "cond3 like '".$dict_area_cond3."'";
			}
		}
		else{
			$where[] =  "cond3 like '%".$dict_area_cond3."%'";
		}
	}
	if($dict_area_cond4!= NULL){
		$iwhere[] = $dict_area_cond4;
		if($querytype == 'checkexist'){
			$pos = strpos($dict_area_cond4, '*');
			if($pos === false){
				$where[] =  "cond4 = '".$dict_area_cond4."'";
			}
			else{
				$dict_area_cond4 = str_replace("*", "%", $dict_area_cond4);       
				$where[] =  "cond4 like '".$dict_area_cond4."'";
			}
		}
		else{
			$where[] =  "cond4 like '%".$dict_area_cond4."%'";
		}
	}
	if($area_code!= NULL){
		$where[] = "area_code = '".$area_code."'";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_DICTAREA." ".$wherestr."";
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
		$sql="select * from ".DATABASE_DICTAREA." ".$wherestr." order by id desc ".$limit."";
		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["id"];
				$temp_arr["area_code"] = $result["area_code"];
				$temp_arr["cond1"] = $result["cond1"]; 
				$temp_arr["cond2"] = $result["cond2"]; 
				$temp_arr["cond3"] = $result["cond3"];
				$temp_arr["cond4"] = $result["cond4"]; 
				$temp_arr["3rd_part"] = $result["3rd_part"]; 
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
		if(count($iwhere) > 0){
			$tmpareaCode = array();
			foreach($iwhere as $i=>$iname){
				$qpa = "";
				if($querytype == 'checkexist'){
					$pos = strpos($iname, '*');
					if($pos === false){
						$qpa = " = '".$iname."'";
					}
					else{
						$iname = str_replace("*", "%", $iname);       
						$qpa =  " like '".$iname."'";
					}
				}
				else{
					$qpa =  " like '%".$iname."%'";
				}
				$isql="select * from ".DATABASE_AREA." where name ".$qpa." or another_name ".$qpa."";
				$iqr = $dsql->ExecQuery($isql);
				if(!$iqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
				else{
					$temp_arr = array();
					while ($res= $dsql->GetArray($iqr, MYSQL_ASSOC)){
						$temp_arr["area_code"] = $res["area_code"];
						$temp_arr["country"] = $res["country"]; 
						$temp_arr["province"] = $res["province"]; 
						$temp_arr["city"] = $res["city"];
						$temp_arr["district"] = $res["district"]; 
						$temp_arr["name"] = $res["name"]; 
						$temp_arr["another_name"] = $res["another_name"]; 
						$temp_arr["short_name"] = $res["short_name"]; 
						if(!in_array($res['area_code'], $tmpareaCode)){
							$arrs["arealist"][] = $temp_arr;
							$tmpareaCode[] = $res['area_code'];
						}
					}
				}
			}
			$arrs["flag"]=1;
			if(isset($arrs['arealist'])){
				$arrs["areacount"] = count($arrs["arealist"]);
			}
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectdict_areainfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$dict_area_code = isset($_GET['dict_area_code']) ? $_GET['dict_area_code'] : NULL;
		$dict_area_cond1= isset($_GET['dict_area_cond1']) ? $_GET['dict_area_cond1'] : NULL;
		$dict_area_cond2 = isset($_GET['dict_area_cond2']) ? $_GET['dict_area_cond2'] : NULL;
		$dict_area_cond3 = isset($_GET['dict_area_cond3']) ? $_GET['dict_area_cond3'] : NULL;
		$dict_area_cond4 = isset($_GET['dict_area_cond4']) ? $_GET['dict_area_cond4'] : NULL;
		$r= getDictAreaList($startnum, $pagesize, $dict_area_cond1, $dict_area_cond2, $dict_area_cond3, $dict_area_cond4, $dict_area_code);
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
		$dict_area_cond1 = $arrsdata["dict_area_cond1"];
		$dict_area_cond2 = isset($arrsdata["dict_area_cond2"]) ? $arrsdata["dict_area_cond2"] : NULL;
		$dict_area_cond3 = isset($arrsdata["dict_area_cond3"]) ? $arrsdata["dict_area_cond3"] : NULL;
		$dict_area_cond4 = isset($arrsdata["dict_area_cond4"]) ? $arrsdata["dict_area_cond4"] : NULL;
		$stype = isset($arrsdata["stype"]) ? $arrsdata["stype"] : NULL;

		$fieldname = array();
		$fieldvalue = array();
		if($arrsdata["area_code"] != NULL){
			$fieldname[] = "area_code";
			$fieldvalue[] = "'".$arrsdata["area_code"]."'";
		}
		if($arrsdata["dict_area_cond1"] != NULL){
			$fieldname[] = "cond1";
			$fieldvalue[] = "'".$arrsdata["dict_area_cond1"]."'";
		}
		if(!empty($arrsdata["dict_area_cond2"])){
			$fieldname[] = "cond2";
			$fieldvalue[] = "'".$arrsdata["dict_area_cond2"]."'";
		}
		if(!empty($arrsdata["dict_area_cond3"])){
			$fieldname[] = "cond3";
			$fieldvalue[] = "'".$arrsdata["dict_area_cond3"]."'";
		}
		if(!empty($arrsdata["dict_area_cond4"])){
			$fieldname[] = "cond4";
			$fieldvalue[] = "'".$arrsdata["dict_area_cond4"]."'";
		}
		if(!empty($arrsdata["stype"])){
			$fieldname[] = "3rd_part";
			$fieldvalue[] = "'".$arrsdata["stype"]."'";
		}
		$fieldnamestr = implode(", ", $fieldname);
		$fieldvaluestr = implode(", ", $fieldvalue);
		$senddata = array();
		$sql = "insert into ".DATABASE_DICTAREA." (".$fieldnamestr.") values (".$fieldvaluestr.")";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $area_name;
		}
		if(count($failed) == 0){ //数据库添加成功
			$arr["flag"] = 1;
			$arr["msg"] = "数据库添加成功";
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
		$idstr = implode(", ", $area_arr);
		$sql = "delete from ".DATABASE_DICTAREA." where `id` in (".$idstr.")";
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
		$dict_area_code = isset($arrsdata['dict_area_code']) ? $arrsdata['dict_area_code'] : NULL;
		$dict_area_cond1= isset($arrsdata['dict_area_cond1']) ? $arrsdata['dict_area_cond1'] : NULL;
		$dict_area_cond2 = isset($arrsdata['dict_area_cond2']) ? $arrsdata['dict_area_cond2'] : NULL;
		$dict_area_cond3 = isset($arrsdata['dict_area_cond3']) ? $arrsdata['dict_area_cond3'] : NULL;
		$dict_area_cond4 = isset($arrsdata['dict_area_cond4']) ? $arrsdata['dict_area_cond4'] : NULL;
		$stype = isset($arrsdata['stype']) ? $arrsdata['stype'] : NULL;
		$result = getDictAreaList(NULL, NULL, $dict_area_cond1, $dict_area_cond2, $dict_area_cond3, $dict_area_cond4, $dict_area_code, "checkexist");
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}



