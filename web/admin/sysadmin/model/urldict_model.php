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
function formatterSendData($senddata, $action, $url){
	$tmpkey = $sourceid;
	if(isset($senddata[$tmpkey])){
		$senddata[$tmpkey]["words"][] = $url;
	}
	else{
		$senddata[$tmpkey]["action"] = $action; //"update action, value in ('add','remove'), 'add' for add new or update existing"
		$senddata[$tmpkey]["type"] = 32;//对应source表
		$senddata[$tmpkey]["words"][] = $url;
	}
	return $senddata;
}
//向solr发送请求
function ud_send_to_solr($senddata){
	global $logger;
	$error = array();
	foreach($senddata as $key => $value){
		$logger->debug(__FUNCTION__." send_solr_value :".var_export($value,true)."");
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
function getUrlDictList($startnum, $pagesize, $ud_url=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($ud_url != NULL){ //类型(论坛,微博,SNS)
		$where[] =  "url = ".$ud_url."";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_URL_DICT." ".$wherestr."";
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
		$sql="select * from ".DATABASE_URL_DICT." ".$wherestr." ".$limit."";
		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["id"];
				$temp_arr["url"] = $result["url"];
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selecturldictinfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$r= getUrlDictList($startnum, $pagesize);
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
	if($arg_type == "addurldict"){
		$ud_url = $arrsdata["ud_url"];  
		$senddata = array();
		$sql = "insert into ".DATABASE_URL_DICT." ( url ) values ('".$ud_url."')";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $ud_url;
		}
		//向solr发送的数据
		$senddata = formatterSendData($senddata, 'add', $ud_url);

		if(count($failed) == 0){ //数据库添加成功
			$solr_r = ud_send_to_solr($senddata); //向solr发送
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
		$ud_arr = $arrsdata["deldata"];
		foreach($ud_arr as $key => $value){
			$ud_id[] = $value["id"];
			$senddata = formatterSendData($senddata, 'remove', $value["url"]);
		}
		$idstr = implode(", ", $ud_id);
		$sql = "delete from ".DATABASE_URL_DICT." where `id` in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据删除失败!";
		}
		else{
			$solr_r = ud_send_to_solr($senddata); //向solr发送
			$arrs["flag"] = 1;
			if(count($solr_r) > 0){
				$arrs["msg"] = "数据删除成功, solr删除失败!";
			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		$result = getUrlDictList(NULL, NULL, "'".$arrsdata["ud_url"]."'");
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}



