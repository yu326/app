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
define('CHILDS', "children");
define('TYPE_PAGE', 'user_role_mapping.php');
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
define('TYPE_GETMAPPING', 'getmapping');
define('TYPE_GETMAPPINGBYUSERID', 'getmappingbyuserid');
define('TYPE_GETMAPPINGBYUSER', 'getmappingbyuser');    //获取角色
define('TYPE_GETMAPPINGBYROLE', 'getmappingbyrole');    //获取所有角色
define('TYPE_UPDATEROLE', 'updaterole');    //修改角色
define('TYPE_DELETEMAPPING', 'deletemapping');    //删除角色
define('TYPE_GETROLEBYUSER', 'getrolebyuser');

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_name;
$arg_tid;
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
function addmapping($uid,$resourcedata)
{
	global $arrs,$arrsdata,$dsql,$logger;
	$deletedata = isset($arrsdata["delrole"]) ? $arrsdata["delrole"] : '';
	$resourcedata = isset($arrsdata["roleid"]) ? $arrsdata["roleid"] : '';
	$arrs["res"] = $resourcedata;
	if(!Authorization::checkUserUseage(3,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		//删除资源
		if($deletedata!=""&&$deletedata!=null)
		{
			foreach($deletedata as $key => $value)
			{
				$sqldel="delete from ".DATABASE_USER_ROLE_MAPPIMG." where roleid =".$value." and userid=".$arrsdata["userid"]." and roletype=".$arrsdata["roletype"];
				//		echo $sqldel;
				$q = $dsql->ExecQuery($sqldel);
			}
		}
		if($resourcedata=="")
		{
			$sql = "delete from ".DATABASE_USER_ROLE_MAPPIMG." where userid=".$arrsdata["userid"];
			//	echo $sql;
			try {
				$q = $dsql->ExecQuery($sql);
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
					//$q = $dsql->ExecQuery($sql);
					//$sql = "delete from user_role_mapping where userid=".$uid;
					//$q = $dsql->ExecQuery($sql);

					//创建资源角色关系向角色资源关系表插入数据
					foreach($resarr as $key => $value){

						$num=0;
						$sqlcheck = "select count(0) as cnt from ".DATABASE_USER_ROLE_MAPPIMG." where roleid=".$value." and userid=".$arrsdata["userid"]." and roletype=".$arrsdata["roletype"];
						$arrs["sqlcheck"] = $sqlcheck;
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
							$sql2 = "insert into ".DATABASE_USER_ROLE_MAPPIMG." (userid,roleid,roletype,updatetime)
		         values(".$uid.",".$value.",".$arrsdata["roletype"].",".time().")";
							$arrs["sql"]=$sql2;
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

//查找租户名称
function getrolebyname($rid,$name)
{
	global $dsql,$arrs,$logger;
	if($rid==0)
	{
		$sql = "select count(*) as totalcount from ".DATABASE_ROLE." where label='".$name."'";
	}
	else
	{
		//修改时执行此查询
		$sql = "select count(*) as totalcount from ".DATABASE_ROLE." where label='".$name."' and roleid <>".$rid;
	}
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
			$arrs['totalcount']=$result["totalcount"];
		}
	}

	if($arrs['totalcount']>0)
	{
		$arrs["flag"]=1;
	}
	else
	{
		$arrs["flag"]=0;
	}
}


//根据用户id查找角色
function getmappingbyuserid($uid)
{
	global $dsql,$arrs,$logger;


	$sql = "select a.*,b.label from user_role_mapping as a inner join role as b on a.roleid = b.roleid where a.userid = ".$uid; 

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
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["updatetime"] = $result["updatetime"];
			$arrs['children'][]=$tmp_arrs;
		}
	}

}



//删除角色,删除角色租户关系,删除角色资源关系
function deleterole($ID)
{
	global $dsql,$arrs,$logger;

	$deltrole="delete from accounting_rule where roleid in (select roleid from role where roleid in(".$ID."))";
	$delroleres="delete from role_resource_relation where roleid in (".$ID.")";
	$delrole = "delete from role where roleid in (".$ID.")";


	try {
		$q1 = $dsql->ExecQuery($deltrole);
		$q2 = $dsql->ExecQuery($delroleres);
		$q3 = $dsql->ExecQuery($delrole);

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
	global $dsql,$arrs,$logger;
	$sql = "update role set label='".$userarr["label"]."',description='".$userarr["description"]."'
	,updatetime='".date(('Y-m-d G:i:s'),time())."'	        
	where roleid =".$userarr["id"];
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

//创建角色资源关系
function addroleresource($roleid,$resourcedata)
{
	global $dsql,$arrs,$logger;

	$resarr = explode(",",$resourcedata);
	if(count($resarr)>0)
	{
		try
		{
			//创建资源角色关系向角色资源关系表插入数据
			foreach($resarr as $key => $value){
				$sql2 = "insert into role_resource_relation (resourceid,roleid,permission,updatetime)
		         values(".$value.",".$roleid.",1,'".date(('Y-m-d G:i:s'),time())."')";
				$q = $dsql->ExecQuery($sql2);
				$arrs["sql"] = $sql2;
			}
			$arrs["flag"]=1;
		}
		catch(Exception $e)
		{
			$arrs["flag"]=0;
		}
	}
}

//修改角色资源关系 //没有调用
function updateroleresource($roleid,$resourcedata)
{
	global $arrs,$logger;
	$sql = "delete from role_resource_relation where roleid=".$roleid;
	$resarr = explode(",",$resourcedata);
	if(count($resarr)>0)
	{
		//创建资源角色关系向角色资源关系表插入数据
		foreach($resarr as $key => $value){


			$sql2 = "insert into role_resource_relation (resourceid,roleid,permission,updatetime)
		         values(".$value.",".$roleid.",1,'".date(('Y-m-d G:i:s'),time())."')";
			$q = $dsql->ExecQuery($sql2);
			if (!$q)
			{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
					echo json_encode($arrs);
		exit;
			}
		}
		$arrs["flag"]=1;
	}

	$q = $dsql->ExecQuery($sql);

	if (!$q)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$arrs["flag"]=1;
	}
}




/**
 * 根据用户ID获取角色信息
 * @param $userid
 * @param $roletype
 */
function getrolebyuser($userid,$roletype)
{
	global $dsql,$arrs,$logger;

	$sql = "select b.roleid,b.label from ".DATABASE_USER_ROLE_MAPPIMG." as a inner join ".DATABASE_ROLE." as b on a.roleid = b.roleid where a.userid=".$userid." and b.roletype=".$roletype;


		$qr = $dsql->ExecQuery($sql);

		if (!$qr)
		{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$tmp_arrs = array();
			while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$tmp_arrs["roleid"] = $result["roleid"];
				$tmp_arrs["label"] = $result["label"];
				$arrs['children'][]=$tmp_arrs;
			}
			$arrs["flag"]=1;
		}
}//end function

if (empty($_GET))
{
	if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
	{
		$arrsdata = $_REQUEST;
		$arg_type = $arrsdata["type"];
		$arg_id = $arrsdata["userid"];

	}
	//set_error_msg("opt is null");
}
else
{
	$arg_type = isset($_GET['type']) ? $_GET['type'] : null;
	$arg_roletype = isset($_GET['roletype']) ? $_GET['roletype'] : null;
	$arg_id = isset($_GET['userid']) ? $_GET['userid'] : 0;
	$arg_tid = isset($_GET['tid']) ? $_GET['tid'] : 0;
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

switch ($arg_type)
{
	case TYPE_GETMAPPING:
		getmapping();
		break;
	case TYPE_GETMAPPINGBYUSER:
		getmappingbyuser();
		break;
	case TYPE_GETMAPPINGBYROLE:
		getmappingbyrole();
		break;
	case TYPE_DELETEMAPPING:
		DELETEMAPPING($arg_id);
		break;
	case TYPE_ADDMAPPING:
		addmapping($arg_id,$arrsdata["roleid"]);
		break;
	case TYPE_GETMAPPINGBYUSERID:
		getmappingbyuserid($arg_id);
		break;
	case TYPE_GETROLEBYUSER:
		getrolebyuser($arg_id,$arg_roletype);
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



