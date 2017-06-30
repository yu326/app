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
//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');
define('ARG_NAME', 'label');


//获取数据类型 (type)的具体内容
define('TYPE_GETRULEBYID', 'getrulebyid');    //获取所资源
define('TYPE_ADDRULE', 'addrule');    //添加资源
define('TYPE_UPDATERULE', 'updaterule');    //修改资源
define('TYPE_DELETERULE', 'deleterule');    //
define('TYPE_GETRULEBYTENANT', 'getrulebytenant');//根据名称查询组信息
define('TYPE_GETRULEBYROLE', 'getrulebyrole');//
define('TYPE_GETALLRULE', 'getallrule');//根据名称查询组信息
define('TYPE_SEARCHRESOURCE', 'searchresource');    //查询函数
define('TYPE_GETMODELBYTENANT', 'getmodelbytenant');    //查询函数



//$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
//$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_tid;
$arg_modelid;

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

//添加新资源
function addrule($tid,$resourcedata)
{
	global $dsql,$arrs,$arrsdata;

	if($resourcedata=="")
	{
		$sql = "delete from accounting_rule where tenantid = ".$tid;
		$q2 = $dsql->ExecQuery($sql);
		if(!$q)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$arrs["flag"]=1;
		}
	}
	else
	{
		$delall = " delete from user_role_mapping where userid in(select * from (select b.roleid,b.userid from accounting_rule as a inner join user_role_mapping as b on
a.roleid = b.roleid where a.tenantid = ".$tid.") as c inner join users as d
on c.userid = d.userid where d.tenantid = ".$tid.")";
		$sql2 = "delete from accounting_rule where tenantid = ".$tid;
		$qdelall = $dsql->ExecQuery($delall);
		if(!$qdelall)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
			echo json_encode($arrs);
			exit;	
		}
		
		$q2 = $dsql->ExecQuery($sql2);

		if(!$q2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
			echo json_encode($arrs);
			exit;	
		}

		$resarr = explode(",",$resourcedata);

		if(count($resarr)>0)
		{
			//创建资源角色关系向角色资源关系表插入数据
			foreach($resarr as $key => $value){

				$arr_resource = getresourcebyrole($value);
				if(count($arr_resource)>0)
				{
					foreach($arr_resource as $keys => $values){

						$sql = "insert into ".DATABASE_ACCOUNTING_RULE."(resourceid,tenantid,roleid,updatetime)
						values(".$arr_resource[$keys]["resourceid"].",".$tid.",".$value.",'".date(('Y-m-d G:i:s'),time())."')";
						$q = $dsql->ExecQuery($sql);
						
						if(!$q2)
						{
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
							$arrs["flag"]=0;
							echo json_encode($arrs);
							exit;	
						}
					
					}//end froeach
					$arrs["flag"]=1;

				}
			}
		}
	}

}

/*
 * 获取所有资源
 */
function getallrule()
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$totalCount ="select count(*) totalcount from ".DATABASE_ACCOUNTING_RULE;
	$sql="select a.*,b.label as groupname from resource as a left join resource_group as b on a.groupid = b.groupid
	      order by a.updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		while($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
		{
			$arrs["totalcount"] = $result["totalcount"];

		}

		$q = $dsql->ExecQuery($sql);

		if (!$q)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}

		$temp_arr = array();
		while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
		{
		$temp_arr["resourceid"] = $r["resourceid"];
		$temp_arr["label"] = $r["label"];
		$temp_arr["groupid"] = $r["groupid"];
		$temp_arr["groupname"] = $r["groupname"];
		$temp_arr["description"] = $r["description"];
		$temp_arr["updatetime"] = $r["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;

	}
	
}

//删除资源信息
function deleteresource($id)
{
	global $dsql,$arrs;

	//删除角色资源关系
	$sqlrole = "delete from role_resource_relation where resourceid in (".$id.")";
	//删除资源默认规则
//	$sqltemplate="delete from  billrulemodel where resourceid in (".$id.")";
	//删除资源与租户关系
	$sqlrule = "delete from accounting_rule where resourceid in (".$id.")";
	//删除资源
	$sqlres = "delete from resource where resourceid in (".$id.")";

	try {
		$q1 = $dsql->ExecQuery($sqlrole);
	//	$q2 = $dsql->ExecQuery($sqltemplate);
		$q3 = $dsql->ExecQuery($sqlrule);
		$q4 = $dsql->ExecQuery($sqlres);

		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}




//根据名称查找资源
function getrulebyid($rid)
{
	global $dsql,$arrs;

	$sql = "select *  from ".DATABASE_ACCOUNTING_RULE." where id='".$rid;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
	
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
	}
	else
	{

		$tmp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arr["id"] = $resoult["id"];
			$tmp_arr["resourceid"] = $resoult["resourceid"];
			$tmp_arr["roleid"] = $resoult["roleid"];
			$tmp_arr["tenantid"] = $resoult["tenantid"];
			$arrs['children']=$tmp_arr;
		}
		$arrs["flag"]=1;

	}

}

//根据ID获取资源
function getrulebytenant($tid)
{
	global $dsql,$arrs;

	$sql = "select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resouceid = b.resourceid where  tenantid =".$tid;


	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["id"] = $result["id"];
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["roleid"] = $result["roleid"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["ruledata"] =$result["ruledata"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}


//根据租户ID获取资源模型
function getmodelbytenant()
{
	global $dsql,$arrs;

	$sql = "select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where  a.tenantid =".$_SESSION["tenantid"];

	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["id"] = $result["id"];
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["roleid"] = $result["roleid"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["ruledata"] =$result["ruledata"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
	
		}
		$arrs["flag"]=1;
	}

}

//根据组id获取资源信息
function getrulebyrole($rid)
{
	global $dsql,$arrs;

	$sql = "select * from ".DATABASE_ACCOUNTING_RULE." where roleid =".$rid;


	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["id"] = $result["id"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["roleid"] =$result["roleid"];
			$temp_arr["ruledata"] = $result["ruledata"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}


//根据roleid获取资源
function getresourcebyrole($rid)
{
	global $dsql,$arrs;

	$sql = "select * from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid =".$rid;


	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["roleid"] =$result["roleid"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

	return $arrs[CHILDS];

}

//添加资源组
function addgroup()
{
	global $dsql,$arrs,$arrsdata;

$sql = "insert into ".DATABASE_RESOURCE_GROUP." (label,description,updatetime)
	        values('".$arrsdata["label"]."','".$arrsdata["description"]."','".date(('Y-m-d G:i:s'),time())."')";

	$q = $dsql->ExecQuery($sql);
	if (!$qr)
	{
	
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
$arrs["flag"]=1;

	}



}
//修改资源组信息
function updategroup($userarr)
{
	global $dsql,$arrs;
	$sql = "update ".DATABASE_RESOURCE_GROUP." set label='".$userarr["label"]."',description='".$userarr["description"]."'
	,updatetime='".date(('Y-m-d G:i:s'),time())."'	        
	where groupid =".$userarr["id"];


	$q = $dsql->ExecQuery($sql);
	if(!$q)
	{
	
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
	$arrs["flag"]=1;

	}
}

//获取组信息

function getallgroup()
{
	global $dsql,$arrs,$arg_pagesize;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize2;

	$totalCount ="select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP;
	$sql="select * from ".DATABASE_RESOURCE_GROUP." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	if (!$qr2)
	{
	
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		while($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
		{
			$arrs["totalcount"] = $result["totalcount"];
		}

		$q = $dsql->ExecQuery($sql);
		if (!$q)
		{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
		}
		else
		{

			$temp_arr = array();
			while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
			{
				$temp_arr["groupid"] = $r["groupid"];
				$temp_arr["label"] = $r["label"];
				$temp_arr["description"] = $r["description"];
				$temp_arr["updatetime"] = $r["updatetime"];
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}

	}


}

/*
 * 获取组信息
 */
function getgroup()
{
	global $dsql,$arrs;

	$sql="select * from ".DATABASE_RESOURCE_GROUP." order by updatetime desc";
	$q = $dsql->ExecQuery($sql);

	if (!$q)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$temp_arr = array();
		while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
		{
			$temp_arr["groupid"] = $r["groupid"];
			$temp_arr["label"] = $r["label"];
			$temp_arr["description"] = $r["description"];
			$temp_arr["updatetime"] = $r["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}
}

//根据名称查找资源
function getgroupbyname($gid,$name)
{
	global $dsql,$arrs;
	if($gid==0)
	{
		$sql = "select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP." where label='".$name."'";
	}
	else
	{
		//修改时执行此查询
		$sql = "select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP." where label='".$name."' and groupid <>".$gid;
	}
	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
	$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{

		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$arrs["totalcount"] = $result["totalcount"];
		}

		if($arrs["totalcount"]>0)
		{
			$arrs["flag"]=1;
		}
		else
		{
			$arrs["flag"]=0;
		}
	}

}

//根据Id查找组信息
function getgroupbyid($gid)
{
	global $dsql,$arrs;

	$sql = "select * from ".DATABASE_RESOURCE_GROUP." where  groupid =".$gid;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
	$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		//$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["groupid"] = $result["groupid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["description"] = $result["description"];
			$tmp_arrs["updatetime"] = $result["updatetime"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}

}



//删除资源信息
function deletegroup($id)
{
	global $dsql,$arrs;

	//删除组信息
	$delgroup = "delete from ".DATABASE_RESOURCE_GROUP." where groupid in (".$id.")";
	
	$q1 = $dsql->ExecQuery($delgroup);
	if (!$q1)
	{
	$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$arrs["flag"]=1;
	}
		/*
		 $q2 = $dsql->ExecQuery($sqltemplate);
		 $q3 = $dsql->ExecQuery($sqlrule);
		 $q4 = $dsql->ExecQuery($sqlres);
		 */
	


}


if (empty($_GET))
{
	if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
	{
		global $arrsdata,$arrs;
		$arrsdata = $_REQUEST;
		$arg_id = $arrsdata["tenantid"];
		$arg_tid = $arrsdata["tenantid"];
		$arg_type = $arrsdata["type"];

	}
	//set_error_msg("opt is null");
}
else
{
	$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
	$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
	$arg_id = isset($_GET["gid"]) ? $_GET["gid"] : 0;
	$arg_modelid = isset($_GET["modelid"]) ? $_GET["modelid"] : 0;
	$arg_tid = isset($_GET["tid"]) ? $_GET["tid"] : 0;
	$arg_name = isset($_GET["name"]) ? $_GET["name"] : null;
	$arg_type = isset($_GET["type"]) ? $_GET["type"] : null;

}


switch ($arg_type)
{
	case TYPE_ADDRULE:
		addrule($arg_id,$arrsdata["roleid"]);
		break;
	case TYPE_GETALLRULE:
		getallrule();
		break;
	case TYPE_GETRULEBYID:
		getrulebyid($arg_id);
		break;
	case TYPE_GETRULEBYTENANT:
		getrulebytenant($arg_tid);
		break;
	case TYPE_GETRULEBYROLE:
		getrulebyrole();
		break;
	case TYPE_GETMODELBYTENANT:
		getmodelbytenant();
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



