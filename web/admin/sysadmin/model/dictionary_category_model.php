<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");

global $hostDBNameDataMapping;
$dataBase4Dic = $hostDBNameDataMapping["8071"];

//字典类别表添加删除
initLogger(LOGNAME_WEBAPI);

session_start();

if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	echo json_encode($arrs);
	exit;
}

$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,$dataBase4Dic,FALSE);
function formatterSendData($senddata, $action, $id, $state=NULL ){
	
	$tmpkey ="category_dict";
	$senddata[$tmpkey]["words"][] = $id;
	$senddata[$tmpkey]["state"][] = $state; //词性
	$senddata[$tmpkey]["action"] = $action;
	$senddata[$tmpkey]["type"] = 0x10;//分类

	return $senddata;
}
//向solr发送请求
function dic_send_to_solr($senddata){
	global $logger;
	$error = array();
	foreach($senddata as $key => $value){
		//$logger->debug(__FILE__.var_export($value,true));
		
		$solr_r = send_solr($value,SOLR_URL_DICTIONARY);
		if($solr_r === false){
			$logger->error(__FUNCTION__." 调用 send_solr 返回false");
			$error[] = "send solr error";
			break;
		}
		else if(!empty($solr_r)){
			if(isset($solr_r['error'])){
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
function getDictionaryList2($startnum, $pagesize, $category_id=NULL, $category_name=NULL, $parent_id=NULL, $parent_name=NULL,$state=NULL,$allChild,$disable=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	$where = array();
	if($category_id != NULL){
		$where[] =  "id =".$category_id."";
	}
	//var_export($category_id);
	//$logger->error(__FILE__." func:".__FUNCTION__." sql:{$category_id} ".$dsql->GetError());
	//var_dump($allChild);
	if($parent_id != NULL){
		if($allChild&&$parent_id==-1){
			$where[] =  "parent_id != ".$parent_id."";
		}else{
			$where[] =  "parent_id = ".$parent_id."";
		}
	}
	if($parent_name != NULL){
		$where[] = "parent_name = '".$parent_name."'";
	}
	if($category_name != NULL){
		$where[] = "category_name = '".$category_name."'";
	}
	if($disable!=NULL){
		$where[] = "state!=2";
	}else if($state != NULL){
		$where[] = "state = '".$state."'";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	$totalCount = "select count(*) as totalcount from 
		".DATABASE_DICTIONARY_CATEGORY." ".$wherestr;
	
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

		$sql="select id,parent_id ,category_name, parent_name ,state from 
		".DATABASE_DICTIONARY_CATEGORY." ".$wherestr."  order by parent_id ".$limit."";
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
				$temp_arr["parent_id"] = $result["parent_id"]; 
				$temp_arr["parent_name"] = $result["parent_name"]; 
				$temp_arr["category_name"]=$result["category_name"];
				$temp_arr["state"]=$result["state"];
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	 
	return $arrs;
}
//查询所有选择的子类，如果有父类换成子类
function getSelectedChild($collection)
{
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	$sql="SELECT  `id`,  `parent_id`,  `category_name`,  `parent_name`,  `state` FROM"
 	.DATABASE_DICTIONARY_CATEGORY." where parent_id !=-1 and id in(".$collection.") or parent_id in(".$collection.")";
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
			$temp_arr["parent_id"] = $result["parent_id"]; 
			$temp_arr["parent_name"] = $result["parent_name"]; 
			$temp_arr["category_name"]=$result["category_name"];
			$temp_arr["state"]=$result["state"];
			$arrs["datalist"][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

	return $arrs;

}
$kwblurUrl = "";
if(isset($_GET['type'])){
	$logger->debug(__FILE__." func:".__FUNCTION__." 获取词典信息,使用固定数据库:".$dataBase4Dic);
	if('select_dictionary_category' == $_GET['type']){
		//查询所有父类或所有子类
		$parent_id= isset($_GET['parent_id']) ? $_GET['parent_id'] : NULL;
		$disable_state= isset($_GET['disable_state']) ? $_GET['disable_state'] : NULL; //不返回禁用的词典
		$r= getDictionaryList2(NULL, NULL,NULL, NULL, $parent_id, NULL,NULL,false,$disable_state);
		//$r= getDictionaryList($id);
		echo json_encode($r);
	}
	else if('select_dictionary_category_Child' == $_GET['type']){
		//传入父类和子类 返回子类
		$collection = $_GET['collection'];
		$r=getSelectedChild($collection);
		echo json_encode($r);
	}
	else if('select_dictionary_category_all' == $_GET['type']){
		//返回所有子类 
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$parent_id= isset($_GET['parent_id']) ? $_GET['parent_id'] : NULL;
		$parent_name= isset($_GET['parent_name']) ? $_GET['parent_name'] : NULL;
		$category_name= isset($_GET['category_name']) ? $_GET['category_name'] : NULL;
		$id= isset($_GET['id']) ? $_GET['id'] : NULL;
		$state= isset($_GET['state']) ? $_GET['state'] : NULL;
		$r= getDictionaryList2($startnum, $pagesize,$id, $category_name, $parent_id, $parent_name,$state,true);
		echo json_encode($r);
	}
}
else if(isset($HTTP_RAW_POST_DATA)){
		
    global $arrsdata;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
   //添加字典类别
   if($arg_type=="add_dictionary_category"){
	   if(isset($arrsdata["category_name"])){
		   $category_name = $arrsdata["category_name"];
	   }
		if(isset($arrsdata["parent_name"])){
			$parent_name = $arrsdata["parent_name"];
		}
		$parent_id = $arrsdata["parent_id"]; 
		$state=	$arrsdata["state"]; 
		if($parent_id!=-1){
			$sql = "insert into ".DATABASE_DICTIONARY_CATEGORY." (category_name,parent_id,state,parent_name) values ( '".$category_name."', ".$parent_id.",".$state.", '".$parent_name."')";
		}else{
			$sql = "insert into ".DATABASE_DICTIONARY_CATEGORY." (parent_name,parent_id,state) values ( '".$parent_name."', ".$parent_id.",".$state.")";
		}
		$qr = $dsql->ExecQuery($sql);
		//$logger->info(__FILE__."添加字典类别:".__FUNCTION__." {$sql} ".$dsql->GetError());
			
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql1:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "添加类别数据失败!";
		}
		else{
		
			$arrs["flag"] = 1;
			$arrs["msg"] = "数据更新成功!";
			$arrs["id"]=mysql_insert_id();
			if($parent_id!=-1){
				$senddata = array();
				//向solr发送的数据
				$senddata = formatterSendData($senddata, 'add',$arrs["id"],$state );
				$solr_r = dic_send_to_solr($senddata); //向solr发送
				if(count($solr_r) > 0){
					$arrs["msg"] = "数据库添加成功,solr添加失败";
				}
			}
		}
		echo json_encode($arrs);
	
	}
	else if($arg_type =="updatetype"){	
		$state=	$arrsdata["state"];
		$id=$arrsdata["id"];
		$sql = "update ".DATABASE_DICTIONARY_CATEGORY." set  state = ".$state."  where id = ".$id."";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"] = 0;
			$arrs["msg"] = "数据失败!";
		}
		else{
			$arrs["flag"] = 1;
			$arrs["msg"] = "数据更新成功!";
			//提交到solr
			$senddata1 = array();
			//向solr发送的数据
			$senddata1 = formatterSendData($senddata1, 'remove',$id,$state );
			$solr_r = dic_send_to_solr($senddata1); //向solr发送
			if(count($solr_r) > 0){
				$arrs["msg"] = "数据库更新成功,solr更新失败";
			}else{
				$senddata = array();
				//向solr发送的数据
				$senddata = formatterSendData($senddata, 'add',$id,$state );
				$solr_r = dic_send_to_solr($senddata); //向solr发送
				if(count($solr_r) > 0){
					$arrs["msg"] = "数据库更新成功,solr更新失败";
				}
			}

		}
		echo json_encode($arrs);
	}
	else if($arg_type == "deletevalueword"){
		$idstr = implode(", ", $arrsdata['id']);
		$sql="delete  from ".DATABASE_DICTIONARY_CATEGORY." where id in (".$idstr.")";
		$qr = $dsql->ExecQuery($sql);
		$logger->info(__FILE__."删除字典类别:".__FUNCTION__." ID:{$idstr} ".$dsql->GetError());
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
			$arrs["msg"]="删除失败。";
		}
		else {
			$arrs["flag"]=1;
			$arrs["msg"]="删除成功。";
			//自动删除父类 先检查是否没有引用
			deleteParentNode();
			//提交到solr
			$arr1=split(",",$idstr);
			
			$senddata = array();
			foreach ($arr1 as $a1)
			{
			  $logger->debug(__FILE__." delete".$idstr);
			//向solr发送的数据
				$senddata = formatterSendData($senddata, 'remove',$a1,1 );
			//	$arr2[]=".$a1.";
			
			}
			$solr_r = dic_send_to_solr($senddata); //向solr发送
			if(count($solr_r) > 0){
				$arrs["msg"] = "数据库删除成功,solr删除失败";
			}
		}
		
		echo json_encode($arrs);
	}
	//新增前检查 对应项是否存在
	else if($arg_type == 'checkvalueexist'){ 
		$category_name = $arrsdata["category_name"];
		$parent_id = isset($arrsdata["parent_id"])?$arrsdata["parent_id"]:NULL; //查询父类是否存在时不用传此参数
		$sql="select  count(*) as totalcount from 
			".DATABASE_DICTIONARY_CATEGORY." where category_name='".$category_name."'";
		if($parent_id==NULL){
			$sql="select  count(*) as totalcount from 
			".DATABASE_DICTIONARY_CATEGORY." where  parent_name='".$category_name."'";
		}
		$qr = $dsql->ExecQuery($sql);
		//$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		}
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
		
	}
	//删除前检查类别是否被引用
	else if($arg_type == 'checkCategoryExist'){ 
		$id = $arrsdata["id"];
		$parent_id = isset($arrsdata["parent_id"])?$arrsdata["parent_id"]:NULL; 
		//如果删除父类 ,检查子类引用，如果删除子类，检查分词引用
		$sql="select  count(*) as totalcount from 
			".DATABASE_DICTIONARY." where category_id=".$id;
		if($parent_id==-1){
			$sql="select  count(*) as totalcount from 
			".DATABASE_DICTIONARY_CATEGORY." where  parent_id=".$id;
		}
		$qr = $dsql->ExecQuery($sql);
		//$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		}
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
		
	}
}
//自动删除父类 先检查是否没有引用
function deleteParentNode(){
	global $arrs,$dsql,$logger;
	$sql1="(select id  from dictionary_category  where parent_id=-1 and id not in (select parent_id  from dictionary_category ))";
	$sql2="delete from ".DATABASE_DICTIONARY_CATEGORY." where parent_id=-1 and id in 
	";
	$qr = $dsql->ExecQuery($sql1);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql1} ".$dsql->GetError());
	}
	else{
		$temp_arr = array();
		while ($result=$dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr[] = $result["id"];
		}
		if(count($temp_arr)<1)
			return;
		$str=join(",",$temp_arr);
		$sql2=$sql2."(".$str.")";
		//$logger->info(__FILE__."删除父类".__FUNCTION__." ID:{$str} ".$dsql->GetError());
		$qr = $dsql->ExecQuery($sql2);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql2} ".$dsql->GetError());
		}
	}
}



