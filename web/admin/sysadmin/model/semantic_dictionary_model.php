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
function getDicCategory($dic_type){
	switch($dic_type){
	case 1:
		$category = "people";
		break;
	case 2:
		$category = "organization";
		break;
	default:
		break;
	}
	return $category;
}
function formatterSendData($senddata, $action, $word, $pos=NULL, $dic_type=NULL ){
	$language='cn';
	$tmpkey = $language;
	if(isset($senddata[$tmpkey])){
		$senddata[$tmpkey]["words"][] = $word;
		if($language == "cn"){
			$senddata[$tmpkey]["pos"][] = "NR"; //词性
		}

	}
	else{
		$senddata[$tmpkey]["action"] = $action; //"update action, value in ('add','remove'), 'add' for add new or update existing"
		$senddata[$tmpkey]["type"] = 1;//表示传入都是语义词典
		$senddata[$tmpkey]["words"][] = $word;
	//	$senddata[$tmpkey]["language"] = $language;
		if($language == "cn"){
			$senddata[$tmpkey]["pos"][] = "NR"; //词性
			$senddata[$tmpkey]["category"] = getDicCategory($dic_type);
			
			
		}
	}
	return $senddata;
}
//向solr发送请求
function dic_send_to_solr($senddata){
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

function getDictionaryList($startnum, $pagesize, $dic_type=NULL, $dic_pos=NULL, $dic_language=NULL, $dic_value=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($dic_type != NULL){
		$where[] =  "type = ".$dic_type."";
	}
	if($dic_pos != NULL){
		$where[] = "pos = '".$dic_pos."'";
	}

	if($dic_value != NULL){
		$where[] = "value in (".$dic_value.")";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	$totalCount = "select count(*) as totalcount from ".DATABASE_SEMANTIC_DICTIONARY." ".$wherestr."";
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
		$sql="select * from ".DATABASE_SEMANTIC_DICTIONARY." ".$wherestr." ".$limit."";
		$qr2 = $dsql->ExecQuery($sql);
		//$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["id"];
				$temp_arr["value"] = $result["value"]; //字典内容
				$temp_arr["type"] = $result["type"]; //字典类型
				//$temp_arr["pos"] = $result["pos"]; //词性，NR专有名词，NN一般名次，VV动词，
				//$temp_arr["language"] = $result["language"]; //区分语言，英文时，value字段的大小写不敏感
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectdictionaryinfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$dic_type = isset($_GET['dic_type']) ? $_GET['dic_type'] : NULL;
		//$dic_pos = isset($_GET['dic_pos']) ? $_GET['dic_pos'] : NULL;
		//$dic_language = isset($_GET['dic_language']) ? $_GET['dic_language'] : NULL;
		$r= getDictionaryList($startnum, $pagesize, $dic_type,  NULL,NULL);
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
	if($arg_type == "adddictionary"){
		$dic_type = $arrsdata["dic_type"];  
		//$dic_pos = $arrsdata["dic_pos"];  
		
		//$dic_language = $arrsdata["dic_language"];  
		$senddata = array();
		foreach($arrsdata["dic_value"] as $key => $value){
			if($value != ""){
				$sql = "insert into ".DATABASE_SEMANTIC_DICTIONARY." (type,  value) values (".$dic_type.",'".$value."')";
				$qr = $dsql->ExecQuery($sql);
				$failed = array();
				if(!$qr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$failed[]= $value;
				}
				//向solr发送的数据
				$senddata = formatterSendData($senddata, 'add', $value, NULL, $dic_type);
			}
		}

		if(count($failed) == 0){ //数据库添加成功
			$solr_r = dic_send_to_solr($senddata); //向solr发送
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
	else if($arg_type == "updatedictionaryword"){ //修改
		$dic_id = $arrsdata["dic_id"];
		$dic_type = $arrsdata["dic_type"];  
		//$dic_subtype = $arrsdata["dic_subtype"];  
		$dic_pos = $arrsdata["dic_pos"];  
		$dic_language = $arrsdata["dic_language"];  
		$dic_value = $arrsdata["dic_value"][0];

		$sql = "update ".DATABASE_SEMANTIC_DICTIONARY." set value = '".$dic_value."', language='".$dic_language."', type = ".$dic_type.", pos='".$dic_pos."'  where id = ".$dic_id."";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据失败!";
		}
		else{
			$senddata = array();
			$senddata = formatterSendData($senddata, 'add', $dic_value, $dic_language, $dic_pos, $dic_type);
			$solr_r = dic_send_to_solr($senddata); //向solr发送
			$arrs["flag"] = 1;
			if(count($solr_r) > 0){
				$arrs["msg"] = "数据更新成功, solr更新失败!";
			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == "deletevalueword"){
		$senddata = array();
		$dic_arr = $arrsdata["deldata"];
		foreach($dic_arr as $key => $value){
			$dic_id[] = $value["id"];
			$senddata = formatterSendData($senddata, 'remove', $value["value"], $value["language"], $value["pos"], $value["type"]);
		}
		$idstr = implode(", ", $dic_id);
		$sql = "delete from ".DATABASE_SEMANTIC_DICTIONARY." where `id` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
			$solr_r = dic_send_to_solr($senddata); //向solr发送
			$arrs["flag"] = 1;
			if(count($solr_r) > 0){
				$arrs["msg"] = "数据删除成功, solr删除失败!";
			}
		}
		
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		foreach($arrsdata["dic_value"] as $key => $value){
			if($value != ""){
				$valueArr[] = "'".$value."'";  
			}
		}
		$valuestr = implode(", ", $valueArr);

		$result = getDictionaryList(NULL, NULL, $arrsdata["dic_type"], NULL, NULL, $valuestr);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}



