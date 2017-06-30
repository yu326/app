<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");

initLogger(LOGNAME_WEBAPI);
session_start();
/*
if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	echo json_encode($arrs);
	exit;
}
 */
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
function getSpiderConfigList($startnum, $pagesize, $spi_name=NULL, $spi_taskpagestyletype=-1){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($spi_name!= NULL){
		$pos = strpos($spi_name, '*');
		if($pos === false){
			$where[] =  "name = '".$spi_name."'";
		}
		else{
			$spi_name= str_replace("*", "%", $spi_name);       
			$where[] =  "name like '".$spi_name."'";
		}
	}
	if($spi_taskpagestyletype != -1){
		$where[] = "templatetype = ".$spi_taskpagestyletype."";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_SPIDERCONFIG." ".$wherestr."";
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
		$sql="select * from ".DATABASE_SPIDERCONFIG." ".$wherestr." ".$limit."";
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
				$temp_arr["name"] = $result["name"]; //配置名称
				$temp_arr["content"] = $result["content"]; //配置内容
				$temp_arr["templatetype"] = $result["templatetype"]; //模板类型
				$temp_arr["urlregex"] = $result["urlregex"]; //分析抓取url正则表达式
				$temp_arr["detailurlregex"] = $result["detailurlregex"]; //详情url正则表达式
				$temp_arr["urlconfigrule"] = $result["urlconfigrule"]; //生成url的配置
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;

}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectspiderconfiginfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$spi_name = isset($_GET['spi_name']) ? $_GET['spi_name'] : NULL;
		$spi_taskpagestyletype = isset($_GET['spi_taskpagestyletype']) ? $_GET['spi_taskpagestyletype'] : -1;
		$r= getSpiderConfigList($startnum, $pagesize, $spi_name, $spi_taskpagestyletype);
		echo json_encode($r);
	}
	else if('selecttploutfield' == $_GET['type']){
		$tplout = array(
			array('name'=>'用户id','code'=>'user.id'),
			array('name'=>'列表链接','code'=>'url'),
			array('name'=>'页面URL','code'=>'page_url')
		);
		echo json_encode($tplout);
		exit;
	}
}
else if(isset($HTTP_RAW_POST_DATA)){
    global $arrsdata;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
	if($arg_type == "addspiderconfig"){
		$spi_name = $arrsdata["spi_name"];  
		$spi_content = $arrsdata["spi_content"];  
		$spi_templatetype = $arrsdata["spi_templatetype"];  
		$spi_urlregex = $arrsdata["spi_urlregex"];  
		$spi_detailurlregex = $arrsdata["spi_detailurlregex"];  
		$spi_urlconfigrule = $arrsdata["spi_urlconfigrule"];  

		$fieldname = array();
		$fieldvalue = array();
		if($spi_templatetype != -1){
			$fieldname[] = "templatetype";
			$fieldvalue[] = $spi_templatetype;
		}
		if($spi_content != ""){
			$fieldname[] = "content";
			$fieldvalue[] = "'".mysql_escape_string($spi_content)."'";
		}
		if($spi_urlregex != ""){
			$fieldname[] = "urlregex";
			$fieldvalue[] = "'".mysql_escape_string($spi_urlregex)."'";
		}
		if($spi_detailurlregex != ""){
			$fieldname[] = "detailurlregex";
			$fieldvalue[] = "'".mysql_escape_string($spi_detailurlregex)."'";
		}
		if($spi_urlconfigrule != ""){
			$fieldname[] = "urlconfigrule";
			$fieldvalue[] = "'".mysql_escape_string($spi_urlconfigrule)."'";
		}
		if($spi_name != ""){
			$fieldname[] = "name";
			$fieldvalue[] = "'".$spi_name."'";
		}
		$namestr = implode(", ", $fieldname); 
		$valuestr = implode(", ", $fieldvalue); 
		$senddata = array();
		$sql = "insert into ".DATABASE_SPIDERCONFIG." (".$namestr.") values (".$valuestr.")";
		$qr = $dsql->ExecQuery($sql);
		$failed = array();
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$failed[]= $spi_name;
		}
		else{
			$sid = $dsql->GetLastID();
		}
		if(count($failed) == 0){ //数据库添加成功
			$arrs["flag"] = 1;
		}
		else{
			$arrs["flag"] = 0;
			if(count($failed) > 0){
				$arrs["msg"] = "数据 ". implode(", ", $failed) ." 数据库添加失败";
			}
		}
		echo json_encode($arrs);
		exit;
	}
	else if($arg_type == "updatespiderconfig"){ //修改
		$spi_id = $arrsdata["spi_id"];
		$spi_name = $arrsdata["spi_name"];  
		//where
		$where = array();
		if($spi_id != NULL){
			$where[] = "id = '".$spi_id."'";
		}
		$wherestr = "";
		if(count($where) > 0){
			$wherestr = " where ".implode(" and ", $where);
		}
		$sql="select * from ".DATABASE_SPIDERCONFIG." ".$wherestr."";
		$qr2 = $dsql->ExecQuery($sql);
		$has = false;
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$has = true;
				$spi_id = $result["id"];
				//$spi_name = $result["name"]; //配置名称
			}
			$arrs["flag"]=1;
		}

		if($has){ //有对应记录
			//特殊字符的处理
			$spi_content= mysql_escape_string($arrsdata["spi_content"]);  
			$spi_templatetype = $arrsdata["spi_templatetype"];  
			$spi_urlregex = mysql_escape_string($arrsdata["spi_urlregex"]);
			$spi_detailurlregex = mysql_escape_string($arrsdata["spi_detailurlregex"]);
			$spi_urlconfigrule = mysql_escape_string($arrsdata["spi_urlconfigrule"]);

			$sql = "update ".DATABASE_SPIDERCONFIG." set content = '".$spi_content."', templatetype = '".$spi_templatetype."', urlregex = '".$spi_urlregex."', detailurlregex = '".$spi_detailurlregex."', urlconfigrule = '".$spi_urlconfigrule."', name = '".$spi_name."' where id = ".$spi_id."";
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"] = 0;
				$arrs["msg"] = "数据失败!";
			}
			else{
				$arrs["flag"] = 1;
			}
		}
		else{ //新增
			$spi_id = $arrsdata["spi_id"];  
			$spi_name = $arrsdata["spi_name"];  
			$spi_content = $arrsdata["spi_content"];  
			$spi_templatetype = $arrsdata["spi_templatetype"];  
			$spi_urlregex = $arrsdata["spi_urlregex"];  
			$spi_detailurlregex = $arrsdata["spi_detailurlregex"];  
			$spi_urlconfigrule = $arrsdata["spi_urlconfigrule"];  

			$fieldname = array();
			$fieldvalue = array();
			if($spi_id != -1){
				$fieldname[] = "id";
				$fieldvalue[] = $spi_id;
			}
			if($spi_templatetype != -1){
				$fieldname[] = "templatetype";
				$fieldvalue[] = $spi_templatetype;
			}
			if($spi_content != ""){
				$fieldname[] = "content";
				$fieldvalue[] = "'".mysql_escape_string($spi_content)."'";
			}
			if($spi_urlregex != ""){
				$fieldname[] = "urlregex";
				$fieldvalue[] = "'".mysql_escape_string($spi_urlregex)."'";
			}
			if($spi_detailurlregex != ""){
				$fieldname[] = "detailurlregex";
				$fieldvalue[] = "'".mysql_escape_string($spi_detailurlregex)."'";
			}
			if($spi_urlconfigrule != ""){
				$fieldname[] = "urlconfigrule";
				$fieldvalue[] = "'".mysql_escape_string($spi_urlconfigrule)."'";
			}
			if($spi_name != ""){
				$fieldname[] = "name";
				$fieldvalue[] = "'".$spi_name."'";
			}
			$namestr = implode(", ", $fieldname); 
			$valuestr = implode(", ", $fieldvalue); 
			$senddata = array();
			$sql = "insert into ".DATABASE_SPIDERCONFIG." (".$namestr.") values (".$valuestr.")";
			$qr = $dsql->ExecQuery($sql);
			$failed = array();
			if(!$qr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$failed[]= $spi_name;
			}
			else{
				$sid = $dsql->GetLastID();
			}
			if(count($failed) == 0){ //数据库添加成功
				$arrs["flag"] = 1;
			}
			else{
				$arrs["flag"] = 0;
				if(count($failed) > 0){
					$arrs["msg"] = "数据 ". implode(", ", $failed) ." 数据库添加失败";
				}
			}
		}
		echo json_encode($arrs);
	}
	else if($arg_type == "deletespiderconfig"){
		$senddata = array();
		$spi_idarr = $arrsdata["deldata"];
		$idstr = implode(", ", $spi_idarr);
		$sql = "delete from ".DATABASE_SPIDERCONFIG." where `id` in (".$idstr.")";
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
	else if($arg_type == 'checkvalueexist'){ //新增前检查 对应项是否存在
		$hasitem = false;
		$result = getSpiderConfigList(NULL, NULL, $arrsdata["spi_name"]);
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"]["name"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
}
