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
//数据库字典表 字典类型对应solr的英文名称
function getExpCategory($exp_type){
	switch($exp_type){
	case 1:
		$category = "special_emotion";
		break;
	case 2:
		$category = "content_emotion";
		break;
	default:
		break;
	}
	return $category;
}
function formatterSendData($senddata, $action, $word, $value, $exp_type, $exp_source=NULL){
	if($exp_source == NULL){
		$exp_source = 0;
	}
	$tmpkey = $exp_type."_".$exp_source;
	if(isset($senddata[$tmpkey])){
		$senddata[$tmpkey]["words"][] = array("word"=>$word, "value"=>$value);
	}
	else{
		$senddata[$tmpkey]["action"] = $action; //"update action, value in ('add','remove'), 'add' for add new or update existing"
		$senddata[$tmpkey]["type"] = 8;//对应dictionary表
		$senddata[$tmpkey]["words"][] = array("word"=>$word, "value"=>$value);
		$senddata[$tmpkey]["category"] = getExpCategory($exp_type);
		if($exp_type == 1){//图标表情 需要区分来源(新浪,腾讯)
			$senddata[$tmpkey]["source"] = $exp_source;
		}
	}
	return $senddata;
}
//向solr发送请求
function exp_send_to_solr($senddata){
	global $logger;
	$error = array();
	foreach($senddata as $key => $value){
		$solr_r = send_solr($value,SOLR_URL_DICTIONARY);
		if($solr_r === false){
			$logger->error(__FUNCTION__." 调用 send_solr 返回false");
			$error[] = "send solr error";
			break;
		}
		else if(!empty($solr_r)){
			if(isset($solr_r['error'])){ //字典添加出错
				$error[] = $value;
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
	}
	return $error;
}
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getExpressionList($startnum, $pagesize, $exp_expression=NULL, $exp_value=NULL, $exp_type=NULL, $exp_source=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($exp_type != NULL){
		$where[] =  "type = ".$exp_type."";
	}
	if($exp_expression != NULL){
		$where[] =  "expression in (".$exp_expression.")";
	}
	if($exp_value != NULL){
		$where[] = "value = ".$exp_value."";
	}
	if($exp_source != NULL){
		$where[] = "source = ".$exp_source."";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	$totalCount = "select count(*) as totalcount from ".DATABASE_EXPRESSION." ".$wherestr."";
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
		$sql="select * from ".DATABASE_EXPRESSION." ".$wherestr." ".$limit."";
		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["expression"] = $result["expression"]; //表情名称
				$temp_arr["type"] = $result["type"]; //1表示图标表情，2表示文字表情
				$temp_arr["value"] = $result["value"]; //表情对应的情感值
				$temp_arr["source"] = $result["source"]; //表情对应的情感值
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
function getExpValueMaxMin(){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	$sql="select max(value) as expvalue_max, min(value) as expvalue_min from ".DATABASE_EXPRESSION;
	$qr2 = $dsql->ExecQuery($sql);
	if(!$qr2){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
			$temp_arr["expvalue_max"] = $result["expvalue_max"];
			$temp_arr["expvalue_min"] = $result["expvalue_min"];
			$arrs["datalist"][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectexpressioninfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$exp_type = isset($_GET['exp_type']) ? $_GET['exp_type'] : NULL;
		$exp_expression= isset($_GET['exp_expression']) ? $_GET['exp_expression'] : NULL;
		$exp_value= isset($_GET['exp_value']) ? $_GET['exp_value'] : NULL;
		$exp_source= isset($_GET['exp_source']) ? $_GET['exp_source'] : NULL;
		$r= getExpressionList($startnum, $pagesize, $exp_expression, $exp_value, $exp_type, $exp_source);
		echo json_encode($r);
	}
	else if('getexpvaluemaxmin' == $_GET['type']){
		$r = getExpValueMaxMin();
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
	if($arg_type == "addexpression"){
		$exp_type = $arrsdata["exp_type"];  
		$exp_source= $arrsdata["exp_source"];  
		$exp_value= $arrsdata["exp_value"];  
		$senddata = array();
		foreach($arrsdata["exp_expression"] as $key => $value){
			if($value != ""){
				$sql = "insert into ".DATABASE_EXPRESSION." (expression, value, source, type) values ('".$value."', ".$exp_value.", '".$exp_source."', '".$exp_type."')";
				$qr = $dsql->ExecQuery($sql);
				$failed = array();
				if(!$qr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$failed[]= $value;
				}
				//向solr发送的数据
				$senddata = formatterSendData($senddata, 'add', $value, $exp_value, $exp_type, $exp_source);
			}
		}

		if(count($failed) == 0){ //数据库添加成功
			$solr_r = exp_send_to_solr($senddata); //向solr发送
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
		$senddata = array();
		$exp_arr = $arrsdata["deldata"];
		foreach($exp_arr as $key => $value){
			$exp_id[] = "'".$value["expression"]."'";
			$senddata = formatterSendData($senddata, 'remove', $value["expression"], $value["value"], $value["type"], $value["source"]);
		}
		$idstr = implode(", ", $exp_id);
		$sql = "delete from ".DATABASE_EXPRESSION." where `expression` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
			$solr_r = exp_send_to_solr($senddata); //向solr发送
			$arrs["flag"] = 1;
			if(count($solr_r) > 0){
				$arrs["msg"] = "数据删除成功, solr删除失败!";
			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		foreach($arrsdata["exp_expression"] as $key => $value){
			if($value != ""){
				$valueArr[] = "'".$value."'";  
			}
		}
		$valuestr = implode(", ", $valueArr);
		$result = getExpressionList(NULL, NULL, $valuestr);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}



