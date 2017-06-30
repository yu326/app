<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");

session_start();

if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	echo json_encode($arrs);
	exit;
}
//方便数据库查询中，将查询结果直接返回成childs结果中的key值
define('CHILDS', "children");

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
define('TYPE_ADDMAPPING', 'addmapping');
define('TYPE_GETMAPPINGBYROLEID', 'getmappingbyroleid');    //获取角色
define('TYPE_GETMAPPINGBYID', 'getmappingbyid');    //获取角色
define('TYPE_GETALLMAPPING', 'getallmapping');    //获取所有角色
define('TYPE_UPDATEMAPPING', 'updatemapping');    //修改角色
define('TYPE_DELETEMAPPING', 'deletemapping');    //删除角色
define('TYPE_SEARCHMAPPING', 'searchMAPPING');    //查询函数

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata = NULL;
$arg_type;
$arg_id;
$arg_rid;
$arg_name;
$arg_roletype;
/*
 //判断session是否存在
 if(!checkusersession())
 {
 $arrs["result"]=false;
 $arrs["msg"]="未登录或登陆超时!";
 return;
 }
 */

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

//添加新角色
function addmapping()
{
	global $arrs,$arrsdata,$dsql;
	$num=0;
	if(!Authorization::checkUserUseage(4,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$deletedata = isset($arrsdata["delresource"]) ? $arrsdata["delresource"] : '';
		$resourcedata = $arrsdata["resourceid"];
		//	$arrs["del"] = $arrsdata["delresource"];
		//	$arrs["res"] = $resourcedata;
		//删除资源
		if($deletedata!=""&&$deletedata!=null)
		{
			//deleterelation($deletedata,$arrsdata["roletype"]);
			//$arrdel = explode(",",$deletedata);
			foreach($deletedata as $key => $value)
			{
				$sqldel="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where resourceid =".$value." and roleid=".$arrsdata["roleid"];
				//$arrs["del"] = $deletedata;
				//$arrs["sqldel"] = $sqldel;
				$q = $dsql->ExecQuery($sqldel);
			}

		}
		if($resourcedata=="")
		{
			$sql="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$arrsdata["roleid"];
			//$delrule="delete from ".DATABASE_ACCOUNTING_RULE." where roleid=".$pid;
			try {
				$q = $dsql->ExecQuery($sql);
				//$q2 = $dsql->ExecQuery($delrule);
				$arrs["flag"]=1;
			}
			catch(Exception $e)
			{
				$arrs["flag"]=0;
			}
		}
		else
		{

			$resarr = explode(",",$resourcedata);
			if(count($resarr)>0)
			{
				try
				{
					//创建资源角色关系向角色资源关系表插入数据
					foreach($resarr as $key => $value){
						$num=0;
						$sqlcheck = "select count(0) as cnt from ".DATABASE_ROLE_RESOURCE_RELATION." where resourceid=".$value." and roleid=".$arrsdata["roleid"];
						//$arrs["sqlcheck"] = $sqlcheck;
						$qr3 = $dsql->ExecQuery($sqlcheck);
						if(!$qr3)
						{

						}
						else
						{
							while ($result = $dsql->GetArray($qr3, MYSQL_ASSOC))
							{
								$num=$result["cnt"];
							}
						}

						if($num==0)
						{
							$sql2 = "insert into ".DATABASE_ROLE_RESOURCE_RELATION." (resourceid,roleid,permission,updatetime)
	        values(".$value.",".$arrsdata["roleid"].",".$arrsdata["permission"].",".time().")";
							//$arrs["sql2"]=$sql2;
							$q = $dsql->ExecQuery($sql2);
						}
					}
					$arrs["flag"]=1;
				}
				catch(Exception $e)
				{
					$arrs["flag"]=0;
				}
			}

		}
	}//结束权限判断


}

/*
 * 获取所有用户信息
 */
function getallmapping()
{
	global $arrs,$arg_pagesize,$dsql,$arg_search_page;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$totalCount ="select count(*) as totalcount from ".DATABASE_ROLE_RESOURCE_RELATION;
	$sql="select * from ".DATABASE_ROLE_RESOURCE_RELATION."  limit ".$limit_cursor.",".$arg_pagesize;
	$qr = $dsql->ExecQuery($totalCount);
	$qr2 = $dsql->ExecQuery($sql);

	while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
	{
		$arrs["totalcount"]=$result["totalcount"];
	}
	if (!$qr2)
	{
		$sql_note = mysql_errno()." file is ".__FILE__." line is ".__LINE__;
		set_error_msg($sql_note);
	}

	$temp_arr = array();
	while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
	{
		$temp_arr["relationid"] = $result["relationid"];
		$temp_arr["resourceid"] = $result["resourceid"];
		$temp_arr["roleid"] = $result["roleid"];
		$temp_arr["pemission"] = $result["pemission"];
		$temp_arr["updatetime"] = $result["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
	}
}

// 获取租户信息添加用户时使用此函数
function getrole()
{
	global $dsql,$arrs;
	$sql = "select * from ".DATABASE_USER_ROLE_MAPPIMG;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		set_error_msg("sql error:".mysql_error());
	}
	else
	{

		$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["description"] = $result["description"];
			$arrs['children'][]=$tmp_arrs;
		}
	}
}


//查找租户名称
function getmappingbyid($rid)
{
	global $dsql,$arrs;

	$sql = "select a.*,b.label from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join resource as b on a.resourceid=b.resourceid where a.roleid =".$rid;
	//echo $sql;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		set_error_msg("sql error:".mysql_error());
	}
	else
	{
		//$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["relationid"] = $result["relationid"];
			$tmp_arrs["resourceid"] = $result["resourceid"];
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["roletype"] = $result["roletype"];
			$tmp_arrs["pemission"] = $result["pemission"];
			$arrs['children'][]=$tmp_arrs;
		}
	}


}



function getmappingbyroleid($rid,$typeid)
{
	global $dsql,$arrs;
	switch ($typeid) {
		case 1:
			$sql = "select a.*,b.label from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join ".DATABASE_SYSTEM_RESOURCE." as b on a.resourceid=b.resourceid where a.roleid =".$rid." group by a.resourceid order by resourceid";
			break;
		case 2:
			$sql = "select a.*,b.label from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join ".DATABASE_TENANT_MANAGE_RESOURCE." as b on a.resourceid=b.resourceid where a.roleid =".$rid;
			break;
	}
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		set_error_msg("sql error:".mysql_error());
	}
	else
	{
		//$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["relationid"] = $result["relationid"];
			$tmp_arrs["resourceid"] = $result["resourceid"];
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["roletype"] = $typeid;
			$tmp_arrs["permission"] = $result["permission"];
			$arrs['children'][]=$tmp_arrs;
		}
	}


}



//删除角色,删除角色租户关系,删除角色资源关系
function deletemapping($ID,$type)
{
	global $dsql,$arrs;


	$sql="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where relationid in (".$ID.")";


	try {
		$q1 = $dsql->ExecQuery($sql);


		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}

//删除角色,删除角色租户关系,删除角色资源关系
function deleterelation($ID,$type)
{
	global $dsql,$arrs;


	$sql="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where resourceid in (".$ID.") ";


	try {
		$q1 = $dsql->ExecQuery($sql);
	}
	catch(Exception $e)
	{

	}

}
//修改角色信息
function updatemapping($userarr)
{
	global $dsql,$arrs;
	$sql = "update ".DATABASE_ROLE_RESOURCE_RELATION." set resourceid=".$userarr["resourceid"].",roleid=".$userarr["roleid"]."
	,pemission=".$userarr["pemission"].",updatetime=".time()."	        
	where relationid =".$userarr["relationid"];
	try
	{
		$q = $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}

//检查资源角色资源关系是否已存在
function getresourceinfo($id,$roleid,$type)
{
	global $dsql;
	$num = 0;
	$sql = "select count(0) as cnt from ".DATABASE_ROLE_RESOURCE_RELATION." where resourceid=".$id." and roleid=".$roleid;
	$arrs["sql33"] = $sql;
	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		throw new Exception(TYPE_PAGE."- getresourceinfo()-".$sql."-".mysql_error());
	}
	else
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$num=$result["cnt"];
		}
	}
	return $num;

}



if (empty($_GET))
{
	if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
	{
		$arrsdata = $_REQUEST;
		$arg_type = $arrsdata["type"];

	}
	//set_error_msg("opt is null");
}
else
{
	$arg_type = isset($_GET['type']) ? $_GET['type'] : null;
	$arg_id = isset($_GET['roleid']) ? $_GET['roleid'] : 0;
	$arg_roletype = isset($_GET['roletype']) ? $_GET['roletype'] : 0;
	$arg_name = isset($_GET['name']) ? $_GET['name'] : null;

}
/*
 if(empty($_POST))
 {
 $arrdata = $_REQUEST;
 $arrdata =  json_decode($arrdata);
 trace($arrdata);
 //var_dump($arrdata);
 echo  json_encode($arrdata);
 $username =  isset($_POST['fanscount']) ? $_POST['fanscount'] : null;
 }
 */
/*
 if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
 {
 $arrsdata = $_REQUEST;
 $arg_type = "gettenant";

 }
 */
$arrs = $arrsdata;
switch ($arg_type)
{
	case TYPE_ADDMAPPING:
		addmapping();
		break;
	case TYPE_GETALLMAPPING:
		getallmapping();
		break;
	case TYPE_GETMAPPINGBYID:
		getmappingbyid();
		break;
	case TYPE_UPDATEMAPPING:
		updatemapping($arrsdata);
		break;
	case TYPE_DELETEMAPPING:
		deletemapping($arg_id);
		break;
	case TYPE_SEARCHMAPPING:
		searchmapping();
		break;
	case TYPE_GETMAPPINGBYROLEID:
		getmappingbyroleid($arg_id,$arg_roletype);
		break;
	default:
		set_error_msg("arg type has a error");
		break;
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



