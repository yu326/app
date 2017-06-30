<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");
define('CHILDS', "children");


if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
    $arrs["result"]=false;
    $arrs["msg"]="未登录或登陆超时!";
    echo json_encode($arrs);
    exit;
} 
//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');
define('ARG_NAME', 'username');
define('ARG_REALNAME', 'realname');
define('ARG_PWD', 'password');
define('ARG_EMAIL', 'email');
define('ARG_TID', 'tenantid');

//获取数据类型 (type)的具体内容
define('TYPE_GETALL', 'getall');    //获取所有统计信息
define('TYPE_ADD', 'addinfo');    //添加统计信息
define('TYPE_UPDATEINFO', 'updateinfo');    //修改统计信息
define('TYPE_DELETEINFO', 'deleteinfo');    //删除统计信息
define('TYPE_SEARCHINFO', 'searchinfo');    //查询统计信息
//define('TYPE_GETINFO', 'searchinfo');    //查询统计信息

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_uid;
$arg_useage;

//判断session是否存在
if(!checkusersession())
{
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	return;
}


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

//
function addinfo($rid,$uid)
{
	global $dsql,$arrs,$arrsdata;
$isuse=0;
$isuse = getinfobyrid($uid,$rid);
	$sql = "insert into ".DATABASE_ACCOUNTING_STATISTICS." (resourceid,userid,useage,updatetime)
	        values(".$rid.",".$uid.",".$isuse.",'".date(('Y-m-d G:i:s'),time())."')";
	try
	{
		$q =  $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}

/*
 * 获取所有信息
 */
function getall()
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$totalCount ="select count(*) as totalcount from ".DATABASE_ACCOUNTING_STATISTICS;
	$sql="select c.*,d.username from (select a.*,b.label from (select * from ".DATABASE_ACCOUNTING_STATISTICS." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize.") as a
inner join resource as b on a.resourceid = b.resourceid) as c
inner join users as d
on c.userid = d.userid";
	$qr2 =  $dsql->ExecQuery($totalCount);
	$q =  $dsql->ExecQuery($sql);

	while($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
	{
		$arrs["totalcount"] = $result["totalcount"];

	}
	if (!$q)
	{
		$sql_note = mysql_errno()." file is ".__FILE__." line is ".__LINE__;
		set_error_msg($sql_note);
	}

	$temp_arr = array();
	while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
	{
		$temp_arr["accountid"] = $r["accountid"];
		$temp_arr["resourceid"] = $r["resourceid"];
		$temp_arr["userid"] = $r["userid"];
		$temp_arr["label"] = $r["label"];
		$temp_arr["username"] = $r["username"];
		$temp_arr["useage"] = $r["useage"];
		$temp_arr["updatetime"] = $r["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
	}
}


//根据id获取信息
function getinfobyrid($uid,$rid)
{
	global $dsql,$arrs,$arg_pagesize;


	$sql="select * from (select b.* from user_role_mapping as a inner join role_resource_relation as b
on a.roleid = b.roleid where a.userid=".$uid.") as c where resourceid = ".$rid;
	$q =  $dsql->ExecQuery($sql);


	$num = $dsql->GetTotalRow($qr);
	if (!$q)
	{
		$sql_note = mysql_errno()." file is ".__FILE__." line is ".__LINE__;
		set_error_msg($sql_note);
	}

	if($num>0)
	{
	$temp_arr = array();
	while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
	{
		$temp_arr["permission"] = $r["permission"];
	
		//$arrs[CHILDS][] = $temp_arr;
	}
	 return $temp_arr["permission"];
	}
	else
	{
		return 0;
	}
}



//删除统计记录
function deleteinfo($ID)
{
	global $dsql,$arrs;
	$sql = "delete from ".DATABASE_ACCOUNTING_STATISTICS." where accountid in (".$ID.")";

	try {
		$q1 =  $dsql->ExecQuery($sql);

		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}
//修改角色信息
function updaterole($userarr)
{
	global $dsql,$arrs;
	$sql = "update role set label='".$userarr["label"]."',description='".$userarr["delscription"]."'
	,updatetime=,".date(('Y-m-d G:i:s'),time())."	        
	where roleid =".$userarr["id"];

	try
	{
		$q =  $dsql->ExecQuery($sql);
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
		global $arrsdata,$arrs;
		$arrsdata = $_REQUEST;
		$arg_id = $arrsdata["tenantid"];
		$arg_type = $arrsdata["type"];

	}
	//set_error_msg("opt is null");
}
else
{
	$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
	$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
	$arg_id = isset($_GET["rid"]) ? $_GET["rid"] : 0;
	$arg_uid = isset($_GET["uid"]) ? $_GET["uid"] : 0;
	$arg_type = isset($_GET["type"]) ? $_GET["type"] : null;
	$arg_useage = isset($_GET["use"]) ? $_GET["use"] : null;

}
mysql_query("SET NAMES utf8");
$arrs = $arrsdata;
switch ($arg_type)
{
	case TYPE_GETALL:
		getall();
		break;
	case TYPE_ADD:
		addinfo($arg_id,$arg_uid);
		break;
	case TYPE_UPDATEINFO:
		updateinfo($arrs);
		break;
	case TYPE_DELETEINFO:
		deleteinfo($arg_id);
		break;
	case TYPE_SEARCHINFO:
		searchinfo();
		break;
	default:
		set_error_msg("arg type has a error");
}

if (!$arrs)
{
	echo "";
}
else
{
	$json_str = json_encode($arrs);
	echo $json_str;
}



