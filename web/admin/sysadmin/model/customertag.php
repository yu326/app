<?php
include_once('includes.php');
include_once('checkpure.php');
define('TYPE_PAGE','templatecontroller.php');
define('CHILDS', "children");

//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');


//获取数据类型 (type)的具体内容
define('TYPE_GETAllTEMPLATE', 'getalltemplate');    //获取所有模板信息
define('TYPE_ADDTEMPLATE', 'addtemplate');    //添加模板
define('TYPE_UPDATETEMPLATE', 'updatetemplate');    //修改模板
define('TYPE_DELETETEMPLATE', 'deletetemplate');    //删除模板
define('TYPE_SEARCHTEMPLATE', 'searchtemplate');    //查询模板
define('TYPE_GETTEMPLATEBYID', 'gettemplatebyid');  //根据ID查询模板

/***************************************租户表标签***************************************************/

define('TYPE_GETALLTENAMTTAG','getalltenanttag');
define('TYPE_ADDTENANTTAG','addtenanttag');
define('TYPE_UPDATETENANTTAG','updatetenanttag');
define('TYPE_DELETETENANTTAG','updatetenanttag');
define('TYPE_GETTENANTTAGBYID','gentenanttagbyid');
define('TYPE_GETTENANTTAGBYTID','gentenanttagbytid');

define('TYPE_GETALLTAGINSTACT','getalltaginstact');
define('TYPE_ADDTAGINSTACT','addtaginstact');
define('TYPE_UPDATETAGINSTACT','updatetaginstact');
define('TYPE_DELETETAGINSTACT','deletetaginstact');
define('TYPE_GETTAGINSTACTBYID','gettaginstacebyid');
define('TYPE_GETTAGINSTACTBYTID','gettaginstacebytid');

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
$arg_type = $_GET["type"];
//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_name;
$arg_tid;
//判断session是否存在
if(!checkusersession())
{
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	return;
}

//global $dsql;


/*
 * set error msg
 */
function set_error_msg($error_str)
{
	$error['error'] = $error_str;
	$msg = json_encode($error);
	echo $msg;
	exit;
}

//添加租户标签信息
function addtenanttag()
{
	global $dsql,$arrs,$arrsdata;

	$sql = "insert into ".DATABASE_TENANT_TAGINFO." (tagid,accounting_rule_id,resourceid,renantid,content,updatetime) values ('".$arrsdata["tagid"]."',".$arrsdata["accounting_rule_id"].",".$arrsdata["resourceid"].",".$arrsdata["tenantidid"].",'".$arrsdata["content"]."',".time().")";
	
	try
	{
		$qr = $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}




/*
 * 获取租标签信息
 */
function getalltananttag()
{
global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	$result = new DatatableResult();
	$sqlcount = "select count(0) as cnt from ".DATABASE_TENANT_TAGINFO;
	$qr = $dsql->ExecQuery($sqlcount);
	if(!$qr){echo "error";
	//$logger->error(TASKMANAGER." - getTaskHistory() sqlerror:".$sqlcount." - ".$dsql->GetError());
	}
	else{
		$rcnt = $dsql->GetArray($qr);
		$result->sEcho=$_GET['sEcho'];
		$result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
		$result->iTotalRecords = $rcnt['cnt'];
		$result->iTotalDisplayRecords = $rcnt['cnt'];
		if($rcnt['cnt'] > 0){
			$sql = "select * from ".DATABASE_TENANT_TAGINFO." limit {$iDisplayStart},{$iDisplayLength}";
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(TASKMANAGER." - getalltananttag() sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				while ($r = $dsql->GetObject($qr)){
					$result->aaData[]=$r;
				}
			}
		}
	}
	echo json_encode($result);

}





//获取某租户的所有标签信息
function gettenangtagbytid($id)
{
global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	$result = new DatatableResult();
	$sqlcount = "select count(0) as cnt from ".DATABASE_TENANT_TAGINFO." where tenantid=".$id;
	$qr = $dsql->ExecQuery($sqlcount);
	if(!$qr){echo "error";
	//$logger->error(TASKMANAGER." - getTaskHistory() sqlerror:".$sqlcount." - ".$dsql->GetError());
	}
	else{
		$rcnt = $dsql->GetArray($qr);
		$result->sEcho=$_GET['sEcho'];
		$result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
		$result->iTotalRecords = $rcnt['cnt'];
		$result->iTotalDisplayRecords = $rcnt['cnt'];
		if($rcnt['cnt'] > 0){
			$sql = "select * from ".DATABASE_TENANT_TAGINFO." limit {$iDisplayStart},{$iDisplayLength} where tenantid=".$id;
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(TASKMANAGER." - gettenangtagbytid() sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				while ($r = $dsql->GetObject($qr)){
					$result->aaData[]=$r;
				}
			}
		}
	}
	echo json_encode($result);

}

//根据id获取租户某条标签信息
function gettenanttagbyid($id)
{
	global $dsql,$arrs;
$num=0;

	$sql="select * from ".DATABASE_TENANT_TAGINFO." where id=".$id;
	$qr = $dsql->ExecQuery($sql);


	if (!$qr)
	{
		throw new Exception(".TYPE_PAGE."- gettemplatebyid()-".$sql.".mysql_error());
	}
	else
	{
	$num = $dsql->GetTotalRow($qr);
	$arrs["totalcount"]=$num; 

	$temp_arr = array();
	if($num>0)
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
		$temp_arr["id"] = $result["tagid"];
		$temp_arr["accounting_rule_id"] = $result["accounting_rule_id"];
		$temp_arr["resourceid"] = $result["resourceid"];
		$temp_arr["tenantid"] = $result["tenantid"];
		$temp_arr["content"] = $result["content"];
		$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);
				
			$arrs[CHILDS][] = $temp_arr;
		}
	}
	}
}


//删除用户标签信息
function deletetenanttag($ID)
{
	global $dsql,$arrs;

	$sql = "delete from ".DATABASE_TENANT_TAGINFO." where id in(".$ID.")";

	
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		throw new Exception(TYPE_PAGE."- deletetenanttag()-".$sql."-".mysql_error());
		$arrs["flag"]=0;
	}
	else
	{
		$arrs["flag"]=1;
	}
}

//修改用户标签信息
function updatetenanttag($userarr)
{
		
	global $dsql,$arrs;
	$sql = "update ".DATABASE_TENANT_TAGINFO." set tagid='".$userarr["tagid"]."',accounting_rule_id='".$userarr["accounting_rule_id"]."',resourceid='".$userarr["resourceid"].",tenantid = ".$userarr["tenantid"].",content='".$userarr["content"].", updatetime=".time()."' where id =".$userarr["id"];
	
	try
	{
		$qr = $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}




//检查用户名称是否重复
function checkUserExist($label,$tid)
{
	global $dsql,$arrs;
	$num;

	$sql="select count(*) as totalcount from ".DATABASE_TEMPLATE." where label='".$label."' and template=".$tid;

	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		set_error_msg("sql error:".$dsql->GetError());
	}

	while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
	{
		$num=$result["totalcount"];
	}

	if($num>0)
	{
		$arrs["flag"]=1;
	}
	else
	{
		$arrs["flag"]=0;
	}

}

//检查用户名称是否重复
function checkUserExistByUserID($username,$tid,$uid)
{
	global $dsql,$arrs;
	$num;

	$sql="select count(*) as totalcount from ".DATABASE_USERS." where username='".$username."' and tenantid=".$tid." and usrid <>"+$uid;

	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		set_error_msg("sql error:".$dsql->GetError());
	}

	while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
	{
		$num=$result["totalcount"];
	}

	if($num>0)
	{
		$arrs["flag"]=1;
	}
	else
	{
		$arrs["flag"]=0;
	}

}

/****************************标签实例操作函数********************************/
//添加标签实例信息
function addtaginstact()
{
	global $dsql,$arrs,$arrsdata;
	$num=0;
	
	//echo $sql;
	$sqlflag = "select * from tenant_taginstanct where parentid=".$arrsdata["parentid"]." and tenantid=".$arrsdata["tenantid"]." and pageid=".$arrsdata["pageid"];
	$qrflag =  $dsql->ExecQuery($sqlflag);
	if(!$qrflag)
	{
		
	}
	else {
		$num = $dsql->GetTotalRow($qr);
	}
	//echo $num;
	//判断是否已经插入到数据库中
	if($num==0)
	{
		$sql = "insert into ".DATABASE_TENANT_TAGINSTANCT." (parentid,tenantid,content,updatetime,pageid) values (".$arrsdata["parentid"].",".$arrsdata["tenantid"].",'".$arrsdata["content"]."',".time().",".$arrsdata["pageid"].")";
	}
	else
	{
		$sql = "update ".DATABASE_TENANT_TAGINSTANCT." set parentid=".$arrsdata["parentid"].",content='".$arrsdata["content"]."' where tenantid = ".$arrsdata["tenantid"]." and parentid = ".$arrsdata["parentid"]." and pageid=".$pageid;
	}
	//echo $sql;
	
	try
	{
		$qr = $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}

//获取租户标签实例
function getalltaginstact()
{
global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	$result = new DatatableResult();
	$sqlcount = "select count(0) as cnt from ".DATABASE_TENANT_TAGINSTANCT;
	$qr = $dsql->ExecQuery($sqlcount);
	if(!$qr){echo "error";
	//$logger->error(TASKMANAGER." - getTaskHistory() sqlerror:".$sqlcount." - ".$dsql->GetError());
	}
	else{
		$rcnt = $dsql->GetArray($qr);
		$result->sEcho=$_GET['sEcho'];
		$result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
		$result->iTotalRecords = $rcnt['cnt'];
		$result->iTotalDisplayRecords = $rcnt['cnt'];
		if($rcnt['cnt'] > 0){
			$sql = "select * from ".DATABASE_TENANT_TAGINSTANCT." limit {$iDisplayStart},{$iDisplayLength}";
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(TASKMANAGER." - getalltaginstact() sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				while ($r = $dsql->GetObject($qr)){
					$result->aaData[]=$r;
				}
			}
		}
	}
	echo json_encode($result);

}

//获取某租户的所有标签实例信息
function gettaginstactbytenatid($id)
{
global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	$result = new DatatableResult();
	$sqlcount = "select count(0) as cnt from ".DATABASE_TENANT_TAGINSTANCT." where tenantid=".$id;
	$qr = $dsql->ExecQuery($sqlcount);
	if(!$qr){echo "error";
	//$logger->error(TASKMANAGER." - getTaskHistory() sqlerror:".$sqlcount." - ".$dsql->GetError());
	}
	else{
		$rcnt = $dsql->GetArray($qr);
		$result->sEcho=$_GET['sEcho'];
		$result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
		$result->iTotalRecords = $rcnt['cnt'];
		$result->iTotalDisplayRecords = $rcnt['cnt'];
		if($rcnt['cnt'] > 0){
			$sql = "select * from ".DATABASE_TENANT_TAGINSTANCT." limit {$iDisplayStart},{$iDisplayLength} where tenantid=".$id;
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(TASKMANAGER." - gettaginstactbytenatid() sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				while ($r = $dsql->GetObject($qr)){
					$result->aaData[]=$r;
				}
			}
		}
	}
	echo json_encode($result);

}



//根据id获取租户某条标签实例信息
function gettaginstactbyid($id)
{
	global $dsql,$arrs;
$num=0;

	$sql="select * from ".DATABASE_TENANT_TAGINSTANCT." where id=".$id;
	$qr = $dsql->ExecQuery($sql);


	if (!$qr)
	{
		throw new Exception(".TYPE_PAGE."- gettaginstactbyid()-".$sql.".mysql_error());
	}
	else
	{
	$num = $dsql->GetTotalRow($qr);
	$arrs["totalcount"]=$num; 

	$temp_arr = array();
	if($num>0)
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
		$temp_arr["id"] = $result["tagid"];
		$temp_arr["parentid"] = $result["parentid"];
		$temp_arr["tenantid"] = $result["tenantid"];
		$temp_arr["content"] = $result["content"];
		$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);
				
			$arrs[CHILDS][] = $temp_arr;
		}
	}
	}
}


//删除租户标签实例
function delettaginstact($ID)
{
	global $dsql,$arrs;

	$sql = "delete from ".DATABASE_TENANT_TAGINSTANCT." where id in(".$ID.")";

	
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		throw new Exception(TYPE_PAGE."- delettaginstact()-".$sql."-".mysql_error());
		$arrs["flag"]=0;
	}
	else
	{
		$arrs["flag"]=1;
	}
}



//修改租户标签实例信息
function updatetaginstact($userarr)
{
		
	global $dsql,$arrs;
	$sql = "update ".DATABASE_TENANT_TAGINSTANCT." set parentid='".$userarr["parentid"]."',tenantid = ".$userarr["tenantid"].",content='".$userarr["content"].", updatetime=".time()."' where id =".$userarr["id"];
	
	try
	{
		$qr = $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}



if (empty($_GET))
{
	if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
	{
		global $arrsdata;
		$arrsdata = $_REQUEST;
		$arg_type = $arrsdata["type"];

	}

	//set_error_msg("opt is null");
}
else
{
	$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
	$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
	$arg_id = isset($_GET["id"]) ? $_GET["id"] : 0;
	$arg_name = isset($_GET["name"]) ? $_GET["name"] : null;
	$arg_type = isset($_GET["type"]) ? $_GET["type"] : null;

}

mysql_query("SET NAMES utf8");

switch ($arg_type)
{
	case TYPE_GETAll:
		gettemplate();
		break;
	case TYPE_ADDTEMPLATE:
		addteplate();
		break;
	case TYPE_UPDATETEMPLATE:
		updatetemplate($arrsdata);
		break;
	case TYPE_DELETETEMPLATE:
		deleteuser($arg_id);
		break;
	case TYPE_SEARCHUSER:
		searchUser(FALSE);
		break;
	case TYPE_CHECKUSER:
		checkUserExist($arg_name,$arg_tid);
		break;
	case TYPE_GETTEMPLATEBYID:
		gettemplatebyid($arg_id);
		break;
	case TYPE_CHECKUSERBYID:
		getuserbyid($arg_name,$arg_tid,$arg_id);
		break;
	case TYPE_ADDTAGINSTACT:
		addtaginstact();
		break;
		default:
		set_error_msg("arg type has a error");
}


//closeMysql();
if (!$arrs)
{
	echo "";
}
else
{
	$json_str = json_encode($arrs);
	echo $json_str;
}



