<?php
include_once('includes.php');
include_once('checkpure.php');
define('CHILDS', "children");

//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');
define('ARG_NAME', 'label');


//获取数据类型 (type)的具体内容
define('TYPE_GETTENANTTAG', 'getall');    //获取所有租户标签
define('TYPE_GETTENANTTAGBYID', 'gettenanttagbyid');    //获取某一租户标签
define('TYPE_UPDATETENANTTAG', 'updatetenanttag');    //修改租户实例表中的内容
define('TYPE_GETRULEBYTENANTID', 'getrulebytid');    //修改租户实例表中的内容
define('TYPE_DELETERULE', 'deleterule');    //
define('TYPE_GETRULEBYTENANT', 'getrulebytenant');//根据名称查询组信息
define('TYPE_GETRULEBYTENANT2', 'getrulebytenant2');//根据名称查询组信息
define('TYPE_GETRULEBYROLE', 'getrulebyrole');//
define('TYPE_GETALLRULE', 'getallrule');//根据名称查询组信息
define('TYPE_SEARCHRESOURCE', 'searchresource');    //查询函数

/*************************标签实例信息*****************************/
define('TYPE_ADDTAGINSTANCE', 'addtaginstance');
define('TYPE_UPDATETAGINSTANCE', 'updatetaginstance');
define('TYPE_GETTAGINSTANCEBYID', 'gettaginstancebyid');


//$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
//$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_tid;

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
function addrule($tid,$resourcedata)
{
	global $dsql,$arrs,$arrsdata;

	if($resourcedata=="")
	{
		$sql = "delete from accounting_rule where tenantid = ".$tid;
		$q2 = $dsql->ExecQuery($sql);
	}
	else
	{
		$delall = " delete from user_role_mapping where userid in(select * from (select b.roleid,b.userid from accounting_rule as a inner join user_role_mapping as b on
a.roleid = b.roleid where a.tenantid = ".$tid.") as c inner join users as d
on c.userid = d.userid where d.tenantid = ".$tid.")";
		$sql2 = "delete from accounting_rule where tenantid = ".$tid;
		$qdelall = $dsql->ExecQuery($delall);
		$q2 = $dsql->ExecQuery($sql2);

		$resarr = explode(",",$resourcedata);
		$arrs["sql"]=$resarr;
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

				}
			}
		}
	}
	/*
	 $sql = "insert into ".DATABASE_ACCOUNTING_RULE." (resourceid,tenantid,roleid,updatetime)
	 values(".$arrsdata["resourceid"].",'".$arrsdata["tenantid"]."',".$arrsdata["roleid"].",'".date(('Y-m-d G:i:s'),time())."')";
	 try
	 {
		$q = $dsql->ExecQuery($sql);
		$arrs["flag"]=1;
		}
		catch(Exception $e)
		{
		$arrs["flag"]=0;
		}
		*/
}

/*
 * 获取所有租户的标签
 */
function getalltenanttag()
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$totalCount ="select count(*) totalcount from ".DATABASE_TENANT_TAGINFO;
	$sql="select * from ".DATABASE_TENANT_TAGINFO." updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	$q = $dsql->ExecQuery($sql);
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
		$temp_arr["id"] = $r["id"];
		$temp_arr["tagid"] = $r["tagid"];
		$temp_arr["accounting_id"] = $r["accounting_id"];
		$temp_arr["resourceid"] = $r["resourceid"];
		$temp_arr["tenantid"] = $r["tenantid"];
		$temp_arr["content"] = $r["content"];
		$temp_arr["updatetime"] = $r["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
	}
}


/*
 *根据租户获取标签的json数据
 */
function getrulebytid()
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$totalCount ="select count(*) totalcount from ".DATABASE_TENANT_TAGINFO;
	$sql="select * from ".DATABASE_TENANT_TAGINFO." updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	$q = $dsql->ExecQuery($sql);
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
		$temp_arr["id"] = $r["id"];
		$temp_arr["tagid"] = $r["tagid"];
		$temp_arr["accounting_id"] = $r["accounting_id"];
		$temp_arr["resourceid"] = $r["resourceid"];
		$temp_arr["tenantid"] = $r["tenantid"];
		$temp_arr["content"] = $r["content"];
		$temp_arr["updatetime"] = $r["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
	}
}


//删除资源信息
function deleteresource($id)
{
	global $dsql,$arrs;

	//删除角色资源关系
	$sqlrole = "delete from role_resource_relation where resourceid in (".$id.")";
	//删除资源默认规则
	$sqltemplate="delete from  billrulemodel where resourceid in (".$id.")";
	//删除资源与租户关系
	$sqlrule = "delete from accounting_rule where resourceid in (".$id.")";
	//删除资源
	$sqlres = "delete from resource where resourceid in (".$id.")";

	try {
		$q1 = $dsql->ExecQuery($sqlrole);
		$q2 = $dsql->ExecQuery($sqltemplate);
		$q3 = $dsql->ExecQuery($sqlrule);
		$q4 = $dsql->ExecQuery($sqlres);

		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}

//修改租户标签实例信息
function updatetaginstance($userarr)
{
	global $dsql,$arrs;
	$sql = "update ".DATABASE_TENANT_TAGINSTANCT." set content='".$arrs["content"]."' where id=".$userarr["id"];

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


//修改租户标签实例信息
function addtaginstance()
{
	global $dsql,$arrs;
	//$sql = "update ".DATABASE_TENANT_TAGINSTANCT." set content='".$userarr["content"]."' where id=".$userarr["id"];
	$sql="insert into ".DATABASE_TENANT_TAGINSTANCT." (parentid,tenantid,content,updatetime) values (".$arrs["parentid"].",".$arrs["tenantid"].",".$arrs["content"].",".time().")";
	echo $sql;
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


//根据ID获取标签实例信息
function gettaninstancebyid($id)
{
	global $dsql,$arrs;

	$sql = "select *  from ".DATABASE_TENANT_TAGINSTANCT." where id='".$id;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		set_error_msg("sql error:".mysql_error());
	}
	else
	{

		$tmp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arr["id"] = $resoult["id"];
			$tmp_arr["parentid"] = $resoult["parentid"];
			$tmp_arr["content"] = $resoult["content"];
			$tmp_arr["tenantid"] = $resoult["tenantid"];
			$tmp_arr["updatetime"] = $resoult["updatetime"];
			$arrs['children']=$tmp_arr;
		}

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
		set_error_msg("sql error:".mysql_error());
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

	}

}

//根据ID获取资源
function getrulebytenant($tid)
{
	global $dsql,$arrs;

	$sql = "select * from ".DATABASE_ACCOUNTING_RULE." where  tenantid =".$tid;


	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		set_error_msg("sql error:".mysql_error());
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["id"] = $result["id"];
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["roleid"] = $result["roleid"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["ruledata"] =$result["ruledata"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
	}

}

//根据ID获取某租户某个标签的资源
function getrulebytenant2($tid,$rid)
{
	global $dsql,$arrs;

	//	$sql = "select * from ".DATABASE_ACCOUNTING_RULE." where  tenantid =".$tid;
	$sql="select b.*,a.tagid from tenant_taginfo as a inner join accounting_rule as b
on a.resourceid = b.resourceid where a.tenantid =".$tid." and b.resourceid = ".$rid;

	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		set_error_msg("sql error:".mysql_error());
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["id"] = $result["id"];
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["roleid"] = $result["roleid"];
			$temp_arr["tagid"] = $result["tagid"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["ruledata"] =$result["ruledata"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
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
		set_error_msg("sql error:".mysql_error());
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
		set_error_msg("sql error:".mysql_error());
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
	}

	return $arrs[CHILDS];

}

//添加资源组
function addgroup()
{global $dsql,$arrs,$arrsdata;

$sql = "insert into ".DATABASE_RESOURCE_GROUP." (label,description,updatetime)
	        values('".$arrsdata["label"]."','".$arrsdata["description"]."','".date(('Y-m-d G:i:s'),time())."')";
$arrs["rr"] = $sql;
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
//修改资源组信息
function updategroup($userarr)
{
	global $dsql,$arrs;
	$sql = "update ".DATABASE_RESOURCE_GROUP." set label='".$userarr["label"]."',description='".$userarr["description"]."'
	,updatetime='".date(('Y-m-d G:i:s'),time())."'	        
	where groupid =".$userarr["id"];
	$arrs["sql"] = $sql;
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

//获取组信息

function getallgroup()
{
	global $dsql,$arrs,$arg_pagesize;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize2;

	$totalCount ="select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP;
	$sql="select * from ".DATABASE_RESOURCE_GROUP." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	$q = $dsql->ExecQuery($sql);
	//echo $sql;
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
		$temp_arr["groupid"] = $r["groupid"];
		$temp_arr["label"] = $r["label"];
		$temp_arr["description"] = $r["description"];
		$temp_arr["updatetime"] = $r["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
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


	$temp_arr = array();
	while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
	{
		$temp_arr["groupid"] = $r["groupid"];
		$temp_arr["label"] = $r["label"];
		$temp_arr["description"] = $r["description"];
		$temp_arr["updatetime"] = $r["updatetime"];
		$arrs[CHILDS][] = $temp_arr;
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
		set_error_msg("sql error:".mysql_error());
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
		set_error_msg("sql error:".mysql_error());
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
	}

}



//删除资源信息
function deletegroup($id)
{
	global $dsql,$arrs;

	//删除组信息
	$delgroup = "delete from ".DATABASE_RESOURCE_GROUP." where groupid in (".$id.")";
	try {
		$q1 = $dsql->ExecQuery($delgroup);
		/*
		 $q2 = $dsql->ExecQuery($sqltemplate);
		 $q3 = $dsql->ExecQuery($sqlrule);
		 $q4 = $dsql->ExecQuery($sqlres);
		 */
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
		//$arg_tid = $arrsdata["tenantid"];
		$arg_type = $arrsdata["type"];
	}
	//set_error_msg("opt is null");
}
else
{
	$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
	$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
	$arg_id = isset($_GET["gid"]) ? $_GET["gid"] : 0;
	$arg_tid = isset($_GET["tid"]) ? $_GET["tid"] : 0;
	$arg_name = isset($_GET["name"]) ? $_GET["name"] : null;
	$arg_type = isset($_GET["type"]) ? $_GET["type"] : null;

}

mysql_query("SET NAMES utf8");

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
	case TYPE_GETRULEBYTENANT2:
		getrulebytenant2($arg_tid,$arg_id);
		break;
	case TYPE_GETRULEBYROLE:
		getrulebyrole();
		break;
	case TYPE_ADDTAGINSTANCE:
		addtaginstance();
		break;
	case TYPE_UPDATETAGINSTANCE:
		updatetaginstance();
		break;
	case TYPE_GETTAGINSTANCEBYID:
		gettaginstancebyid($arg_id);
		break;
	case TYPE_ADDTAGINSTANCE:
		addtaginstance();
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



