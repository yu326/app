<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");
global $hostDBNameDataMapping;
$dataBase4Dic = $hostDBNameDataMapping["8071"];
//分词字典添加删除
initLogger(LOGNAME_WEBAPI);

session_start();

if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	echo json_encode($arrs);
	exit;
}

function formatterSendData($senddata, $action, $word, $language, $pos=NULL,$category_id=NULL){
	$tmpkey = $language;
	if(isset($senddata[$tmpkey])){
		$senddata[$tmpkey]["words"][] = $word;
		$senddata[$tmpkey]["category_id"][] =$category_id;
	}
	else{
		$senddata[$tmpkey]["action"] = $action; //"update action, value in ('add','remove'), 'add' for add new or update existing"
		$senddata[$tmpkey]["type"] = 4;//表示传入的是分词词典
		$senddata[$tmpkey]["words"][] = $word;
		$senddata[$tmpkey]["category_id"][] =$category_id;
		$senddata[$tmpkey]["language"] = $language;

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
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,$dataBase4Dic,FALSE);
function getDictionaryList($startnum, $pagesize, $category_id=NULL, $dic_pos=NULL, $dic_language=NULL, $dic_value=NULL,$parent_id=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($category_id != NULL){
		$where[] =  "category_id = ".$category_id."";
	}
	if($dic_pos != NULL){
		$where[] = "pos = '".$dic_pos."'";
	}
	if($dic_language != NULL){
		$where[] = "language = '".$dic_language."'";
	}
	if($parent_id != NULL){
		$where[] = "parent_id = '".$parent_id."'";
	}
	if($dic_value != NULL){
		$arr1=split(",",$dic_value);
		$arr2=array();
		foreach ($arr1 as $a1)
		{
			$arr2[]="'".$a1."'";
		}
		$str=join(",",$arr2);
		$where[] = "value in (".$str.")";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	//$totalCount = "select count(*) as totalcount from ".DATABASE_DICTIONARY." ".$wherestr."";
	$totalCount="select count(*) as totalcount from ".DATABASE_DICTIONARY." as t1 left join ".DATABASE_DICTIONARY_CATEGORY ." as t2  on t2.id=t1.category_id ".$wherestr." ";
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
		$sql="SELECT  t1.`id`,  `value`,  `language`,  `category_id` ,category_name FROM ".DATABASE_DICTIONARY." as t1 left join ".DATABASE_DICTIONARY_CATEGORY ." as t2  on t2.id=t1.category_id ".$wherestr." ".$limit."";
		
		$qr2 = $dsql->ExecQuery($sql);
		//$logger->debug(__FILE__." func:".__FUNCTION__." sql:{$sql} ");
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["id"];
				$temp_arr["value"] = $result["value"]; //字典内容
				$temp_arr["category_id"] = $result["category_id"]; //字典类型
				//$temp_arr["pos"] = $result["pos"]; //词性，NR专有名词，NN一般名次，VV动词，
				$temp_arr["language"] = $result["language"]; //区分语言，英文时，value字段的大小写不敏感
				$temp_arr["category_name"]=$result["category_name"];
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	//var_dump($arrs);
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectdictionaryinfo' == $_GET['type']){
		
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : NULL;
		$parent_id = isset($_GET['parent_id']) ? $_GET['parent_id'] : NULL;
		
		$dic_language = isset($_GET['dic_language']) ? $_GET['dic_language'] : NULL;
		$dic_value = isset($_GET['dic_value']) ? $_GET['dic_value'] : NULL;
		$r = getDictionaryList($startnum, $pagesize, $category_id, NULL, $dic_language, $dic_value, $parent_id);
	
		echo json_encode($r);
	}
}
else if(isset($HTTP_RAW_POST_DATA))
{
    global $arrsdata;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
 
	if($arg_type == "adddictionary")
	{
		$category_id = $arrsdata["category_id"];
		//$dic_pos = isset($arrsdata['dic_pos']) ? $arrsdata['dic_pos'] : NULL;
		$dic_language =isset($arrsdata["dic_language"]) ? $arrsdata["dic_language"] : NULL; 
		$senddata = array();
		foreach($arrsdata["dic_value"] as $key => $value){
			if($value != ""){

				$sql = "insert into ".DATABASE_DICTIONARY." (category_id, language, value) values ( ".$category_id.", '".$dic_language."', '".$value."')";
				$qr = $dsql->ExecQuery($sql);
				//$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$failed = array();
				if(!$qr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$failed[]= $value;
				}
				//向solr发送的数据
				$senddata = formatterSendData($senddata, 'add', $value, $dic_language, NULL, $category_id);
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
	else if($arg_type == "deletevalueword"){
		$senddata = array();
		$dic_arr = $arrsdata["deldata"];
		foreach($dic_arr as $key => $value){
			$dic_id[] = $value["id"];
			$senddata = formatterSendData($senddata, 'remove', $value["value"], $value["language"], NULL,$value["category_id"]);
		}
		$idstr = implode(", ", $dic_id);
		$sql = "delete from ".DATABASE_DICTIONARY." where `id` in (".$idstr.")";
		$logger->debug(__FILE__.__LINE__." sql ".var_export($sql, true));
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
		// 删除类别表 如果此类别不再被引用
//		foreach($dic_arr as $key => $value){
//			//$dic_id[] = $value["id"];
//			$sql = "select count(*) as totalcount from ".DATABASE_DICTIONARY."where category_id=".$value["category_id"]."";
//		
//			$qr = $dsql->ExecQuery($sql);
//			if(!$qr){
//				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
//				$arrs["flag"]=0;
//			}
//			else{
//				$result = $dsql->GetArray($qr, MYSQL_ASSOC);
//				if($result["totalcount"]==0){
//					//删除类别
//					DelDictionaryCategory($category_id);
//				}
//			}
//		}
		echo json_encode($arrs);
	}
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		$category_id = $arrsdata["category_id"];
		$dic_language=isset($arrsdata["dic_language"])?$arrsdata["dic_language"]:NULL;
		
		foreach($arrsdata["dic_value"] as $key => $value){
			if($value != ""){
				$valueArr[] = "".$value."";  
			}
		}
		$valuestr = implode(", ", $valueArr);
	
		$result = getDictionaryList(NULL, NULL,$category_id, NULL, $dic_language, $valuestr);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}




