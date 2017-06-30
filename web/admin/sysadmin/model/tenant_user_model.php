<?php
include_once('includes.php');
include_once('model_config.php');
include_once('userinfo.class.php');
include_once('authorization.class.php');

initLogger(LOGNAME_WEBAPI);
session_start();
$chksession =Authorization::checkUserSession();
if( $chksession!= CHECKSESSION_SUCCESS){
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
define('ARG_NAME', 'username');
define('ARG_REALNAME', 'realname');
define('ARG_PWD', 'password');
define('ARG_EMAIL', 'email');
define('ARG_TID', 'tenantid');

//获取数据类型 (type)的具体内容
define('TYPE_GETTENANT', 'gettenant');    //获取所有租户
define('TYPE_GETALLTENANT', 'getalltenant');//获取所有租户
define('TYPE_GETTENANTBYNAME', 'gettenantbyname');//根据名称获取租户
define('TYPE_GETTENANTBYID', 'gettenantbyid');
define('TYPE_GETTENANTBYDOMAIN', 'gettenantbydomain');//根据租户二级域名获取租户ID
define('TYPE_ADDTENANT', 'addtenant');    //添加租户
define('TYPE_UPDATETENANT', 'updatetenant');    //修改租户
define('TYPE_DELETETENANT', 'deletetenant');    //删除租户信息
define('TYPE_SEARCHTENANT', 'searchtenant');    //查询
define('TYPE_GETTENANTBYROLE', 'gettenantbyrole');  //根据当前用户的角色权限列出当期那用户能够访问的租户
define('TYPE_GETALLTENANT2', 'getalltenant2'); //设置租户资源时使用的租户列表

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata;
$arg_type;//存储操作类型
$arg_id;//存储租户id
$arg_name;//存储租户名称
$arg_web;//存储租户二级域名

//判断session是否存在
/*
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

//添加新租用户
function addtenant()
{
	global $dsql,$arrs,$arrsdata,$logger;
	//$checktypesql = "select * "
	//先检查当前操作用户是否是系统管理类用户
	//if(checkadmintype($arrsdata["usertype"]))
	//{
	if(!Authorization::checkUserUseage(1,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$accessdatalimit = 0;
		if($arrsdata["allowaccessdata"]){//只有$arrsdata["allowaccessdata"] 为1时，赋值
			$accessdatalimit = $arrsdata['accessdatalimit'];
		}
		$sql = "insert into ".DATABASE_TENANT." (tenantname,tel,email,contact,address,weburl,description,prepayment,
		  productid,updatetime,webname,localtype, timelimit, allowdrilldown, allowlinkage,allowoverlay, allowdownload,allowupdatesnapshot, alloweventalert, allowwidget,allowaccessdata, accessdatalimit, selfstyle, allowvirtualdata) 
		  values('".$arrsdata["tenantname"]."','".$arrsdata["tel"]."','".$arrsdata["email"]."', '".$arrsdata["contact"]."','"
		  .$arrsdata["address"]."', '".$arrsdata["weburl"]."','".$arrsdata["description"]."',".$arrsdata["prepayment"].","
		  .$arrsdata["productid"].",".time().",'".$arrsdata["webname"]."',".$arrsdata["localtype"].", "
		  .$arrsdata["timelimit"].",".$arrsdata["allowdrilldown"].",".$arrsdata["allowlinkage"].",".$arrsdata["allowoverlay"].", {$arrsdata["allowdownload"]},".$arrsdata["allowupdatesnapshot"].",".$arrsdata["alloweventalert"].",".$arrsdata["allowwidget"].",".$arrsdata["allowaccessdata"].",".$accessdatalimit.", ".$arrsdata["selfstyle"].", ".$arrsdata["allowvirtualdata"].")";
		$q = $dsql->ExecQuery($sql);
		if(!$q)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$arrs["flag"]=1;
			$newtenantid = $dsql->GetLastID();
			//当用选择使用自有样式时,创建应租户的文件夹
			if($arrsdata["selfstyle"]){
				$sRealPath = realpath('../../../v2');
				//$filepath = substr( $sRealPath, 0, strrpos( $sRealPath,'\\'));
				$dir = $sRealPath."/tenant/".$newtenantid;
				if(!is_dir($sRealPath."/tenant")){
					mkdir($sRealPath."/tenant", 0777);
				}
				if(!is_dir($dir)){
					mkdir($dir, 0777);
				}
			}
		}

	}

}

/*
 * 获取所有用户信息
 */
function getalltenant()
{
	global  $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	if(!Authorization::checkUserUseage(1,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$totalCount ="select count(*) as totalcount from ".DATABASE_TENANT;
		$sql="select * from ".DATABASE_TENANT." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
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
			else
			{
				$temp_arr = array();
				while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
				{
					$temp_arr["tenantid"] = $r["tenantid"];
					$temp_arr["tenantname"] = $r["tenantname"];
					$temp_arr["tel"] = $r["tel"];
					$temp_arr["email"] = $r["email"];
					$temp_arr["address"] = $r["address"];
					$temp_arr["contact"] = $r["contact"];
					//$temp_arr["logourl"] = $r["logourl"];
					$temp_arr["weburl"] = $r["weburl"];
					$temp_arr["prepayment"] = $r["prepayment"];
					$temp_arr["productid"] = $r["productid"];
					$temp_arr["description"] = $r["description"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["flag"]=1;
			}

		}

	}
}


/*
 * 获取所有用户信息
 */
function getalltenant2()
{
	global  $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	if(!Authorization::checkUserUseage(8,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$totalCount ="select count(*) as totalcount from ".DATABASE_TENANT;
		$sql="select * from ".DATABASE_TENANT." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
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
			else
			{
				$temp_arr = array();
				while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
				{
					$temp_arr["tenantid"] = $r["tenantid"];
					$temp_arr["tenantname"] = $r["tenantname"];
					$temp_arr["tel"] = $r["tel"];
					$temp_arr["email"] = $r["email"];
					$temp_arr["address"] = $r["address"];
					$temp_arr["contact"] = $r["contact"];
					//$temp_arr["logourl"] = $r["logourl"];
					$temp_arr["weburl"] = $r["weburl"];
					$temp_arr["prepayment"] = $r["prepayment"];
					$temp_arr["productid"] = $r["productid"];
					$temp_arr["description"] = $r["description"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["flag"]=1;
			}

		}

	}
}


/*
 * 根据当前登陆用户的权限显示可用租户列表
 */
function gettenantbyrole()
{
	global  $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	if(!Authorization::checkUserUseage(2,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
	$arrs["result"]=true;
	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	$totalCount = "select count(*) as totalcount from ".DATABASE_TENANT." as c inner join (select DISTINCT(b.childid) from ".DATABASE_USER_ROLE_MAPPIMG." as a inner join ".DATABASE_ROLE_RESOURCE_RELATION." as b on a.roleid = b.roleid where a.userid=".$_SESSION["user"]->userid." and b.resourceid=2) as d on c.tenantid = d.childid";
	$sql ="select * from ".DATABASE_TENANT." as c inner join (select DISTINCT(b.childid) from ".DATABASE_USER_ROLE_MAPPIMG." as a inner join ".DATABASE_ROLE_RESOURCE_RELATION." as b on a.roleid = b.roleid where a.userid=".$_SESSION["user"]->userid." and b.resourceid=2) as d on c.tenantid = d.childid limit ".$limit_cursor.",".$arg_pagesize;
	//$sql="select * from ".DATABASE_TENANT." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
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
				$temp_arr["tenantid"] = $r["tenantid"];
				$temp_arr["tenantname"] = $r["tenantname"];
				$temp_arr["tel"] = $r["tel"];
				$temp_arr["email"] = $r["email"];
				$temp_arr["address"] = $r["address"];
				$temp_arr["contact"] = $r["contact"];
				//$temp_arr["logourl"] = $r["logourl"];
				$temp_arr["weburl"] = $r["weburl"];
				$temp_arr["prepayment"] = $r["prepayment"];
				$temp_arr["productid"] = $r["productid"];
				//$temp_arr["description"] = $r["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}

	}//权限判断结束
}

// 获取租户信息添加用户时使用此函数
function gettenant()
{
	global $dsql,$arrs,$logger;
	$sql = "select tenantid,tenantname from ".DATABASE_TENANT;
	$qr =  $dsql->ExecQuery($sql);

	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{

		$tmp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arr["tenantid"] = $result["tenantid"];
			$tmp_arr["tenantname"] = $result["tenantname"];
			$arrs['children'][]=$tmp_arr;
		}
		$arrs["flag"]=1;
	}
}


//查找租户名称
function gettenantbyname($tid,$name,$web)
{
	global $dsql,$arrs,$logger;
	$total=0;
	$webtotal=0;
	$sql;
	$websql;
	if($tid==0)
	{
		$sql = "select count(*) as totalcount from ".DATABASE_TENANT." where tenantname='".$name."'";

	}
	else
	{
		//修改时执行此查询
		$sql = "select count(*) as totalcount from ".DATABASE_TENANT." where tenantname='".$name."' and tenantid <>".$tid;
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
			$total=$result["totalcount"];
		}
	}

	if($total>0)
	{
		$arrs["flag"]=1;
	}
	else
	{
		//查询二级域名是否存在
		if($tid=0)
		{
			$websql = "select count(*) totalcount from ".DATABASE_TENANT." where weburl='".$web."'";
		}
		else
		{
			$websql = "select count(*) totalcount from ".DATABASE_TENANT." where weburl='".$web."' and tenantid <> ".$tid;
		}
		$qr2 = $dsql->ExecQuery($websql);
		if (!$qr2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$websql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{

			while($r = $dsql->GetArray($qr2, MYSQL_ASSOC))
			{
				$webtotal=$r["totalcount"];
			}
			//如果二级域名不存在则创建目录
			if($webtotal>0)
			{
				$arrs["flag"]=2;
			}
			else
			{
				/*
				$sRealPath = realpath('./');
				$sSelfPath = $_SERVER['PHP_SELF'] ;
				//$filepath = substr( $sRealPath, 0, strrpos( $sRealPath,'\\'));
				$filepath = realpath(dirname(__FILE__).'../../../');//获取根目录
				$dirname = $filepath."\\tenant\\".$web."/";
				var_dump($dirname);
				//创建文件夹
				if(!file_exists($dirname))
				{
					mkdir($dirname);
				}

				$arrs["flag"]=0;
				 */
				$arrs["flag"]=0;
			}
		}
	}


}

function gettenantbyid($id)
{

	global $dsql,$arrs,$logger;
	$num=0;

	$sql="select a.*,b.label as productname from ".DATABASE_TENANT." as a inner join ".DATABASE_PRODUCTS." as b on a.productid = b.productid where tenantid=".$id;
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
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
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["tenantname"] = $result["tenantname"];
				$temp_arr["productname"] = $result["productname"];
				$temp_arr["contact"] = $result["contact"];
				$temp_arr["tel"] = $result["tel"];
				$temp_arr["address"] = $result["address"];
				$temp_arr["localtype"] = $result["localtype"];
				$temp_arr["webname"] = $result["webname"];
				$temp_arr["email"] = $result["email"];
				$temp_arr["weburl"] = $result["weburl"];
				$temp_arr["prepayment"] = $result["prepayment"];
				$temp_arr["timelimit"] = $result["timelimit"];
				$temp_arr["description"] = $result["description"];
				$temp_arr["allowdrilldown"] = $result["allowdrilldown"];
				$temp_arr["allowlinkage"] = $result["allowlinkage"];
				$temp_arr["allowoverlay"] = $result["allowoverlay"];
				$temp_arr["allowdownload"] = $result["allowdownload"];
				$temp_arr["allowupdatesnapshot"] = $result["allowupdatesnapshot"];
				$temp_arr["alloweventalert"] = $result["alloweventalert"];
				$temp_arr["allowwidget"] = $result["allowwidget"];
				$temp_arr["allowaccessdata"] = $result["allowaccessdata"];
				$temp_arr["accessdatalimit"] = $result["accessdatalimit"];
				$temp_arr["selfstyle"] = $result["selfstyle"];
				$temp_arr["allowvirtualdata"] = $result["allowvirtualdata"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);

				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}

}

/**
 * 根据租户的二级域名获取租户ID
 * 参数：$id租户Id
 * @param unknown_type $id
 */
function gettenantbydomain($id)
{

	global $dsql,$arrs,$logger;
	$num=0;

	$sql="select * from ".DATABASE_TENANT." where weburl='".$id."'";
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
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
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["tenantname"] = $result["tenantname"];
				$temp_arr["contact"] = $result["contact"];
				$temp_arr["tel"] = $result["tel"];
				$temp_arr["address"] = $result["address"];
				$temp_arr["email"] = $result["email"];
				$temp_arr["weburl"] = $result["weburl"];
				//$temp_arr["logourl"] = $result["logourl"];
				$temp_arr["prepayment"] = $result["prepayment"];
				$temp_arr["description"] = $result["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);

				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}

}


//删除用户,删除用户的同时删除角色用户对应关系
function deletetenant($ID)
{
	global $dsql,$arrs,$logger;

	if(!Authorization::checkUserUseage(1,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		//删除租户角色关系
		$delTRole = "delete from ".DATABASE_ACCOUNTING_RULE." where tenantid in(".$ID.")";
		//删除用户角色关系
		//$deluserrole = "delete from ".DATABASE_USER_ROLE_MAPPING." where userid in(select userid from users where tenantid in (".$ID."))";
		//删除制定租户的用户
		$delusers = "delete from ".DATABASE_USERS." where tenantid in(".$ID.")";
		//删除租户信息
		$deltenant = "delete from ".DATABASE_TENANT." where tenantid in (".$ID.")";
		//删除租户与资源的关系
		$deltenantresource ="delete from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid in (".$ID.")";


		$delurole = $dsql->ExecQuery($delTRole);
		if (!$delurole)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$delTRole} ".$dsql->GetError());
			$arrs["result"]=false;
			$arrs["msg"] = "删除失败";
		}
		else
		{
			$deltenantrole = $dsql->ExecQuery($deltenant);
			if (!$deltenantrole)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$deltenant} ".$dsql->GetError());
				$arrs["result"]=false;
				$arrs["msg"] = "删除失败";
			}
			else
			{
				$deltusers = $dsql->ExecQuery($delusers);
				if (!$deltusers)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$delusers} ".$dsql->GetError());
					$arrs["result"]=false;
					$arrs["msg"] = "删除失败";
				}
				else {
					$delres = $dsql->ExecQuery($deltenantresource);
					if (!$delres)
					{
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$deltenantresource} ".$dsql->GetError());
						$arrs["result"]=false;
						$arrs["msg"] = "删除失败";
					}
					else {
							
						$arrs["result"]=true;
						$arrs["msg"] = "删除成功";
							
					}
				}
			}
		}

	}
}
	//修改租户信息
	function updatetenant($userarr)
	{
		global $dsql,$arrs,$logger;
		if(!Authorization::checkUserUseage(1,1,$childid=null))
		{
			$arrs["result"]=false;
			$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
			echo json_encode($arrs);
			exit;
		}
		else
		{
			$accessdatalimit = 0;
			if($userarr["allowaccessdata"]){//只有$arrsdata["allowaccessdata"] 为1时，赋值
				$accessdatalimit = $userarr['accessdatalimit'];
			}
			$sql = "update ".DATABASE_TENANT." set tenantname='".$userarr["tenantname"]."',tel='".$userarr["tel"]."',email='".$userarr["email"].
	       "',address='".$userarr["address"]."',contact='".$userarr["contact"]."',
	       weburl='".$userarr["weburl"]."',timelimit='".$userarr["timelimit"]."',
	       description='".$userarr["description"]."',productid=".$userarr["productid"].",prepayment=".$userarr["prepayment"].",updatetime=".time().",webname='".$userarr["webname"]."',localtype=".$userarr["localtype"].",
	       allowdrilldown={$userarr['allowdrilldown']},allowlinkage={$userarr['allowlinkage']},allowoverlay={$userarr['allowoverlay']},allowdownload={$userarr['allowdownload']},allowupdatesnapshot={$userarr['allowupdatesnapshot']},alloweventalert={$userarr['alloweventalert']},
	       allowwidget={$userarr['allowwidget']}, allowaccessdata={$userarr['allowaccessdata']}, accessdatalimit={$accessdatalimit}, selfstyle=".$userarr["selfstyle"].", allowvirtualdata=".$userarr["allowvirtualdata"]."
	       where tenantid =".$userarr["id"];
			$q = $dsql->ExecQuery($sql);
			if (!$q)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["result"]=true;
				$arrs["msg"]="添加失败!";
			}
			else
			{
				$arrs["result"]=true;
				$arrs["msg"]="修改成功!";
				//当用选择使用自有样式时,创建应租户的文件夹
				if($userarr["selfstyle"]){
					$sRealPath = realpath('../../../v2');
					//$filepath = substr( $sRealPath, 0, strrpos( $sRealPath,'\\'));
					$dir = $sRealPath."/tenant/".$userarr["id"];
					if(!is_dir($sRealPath."/tenant")){
						mkdir($sRealPath."/tenant", 0777);
					}
					if(!is_dir($dir)){
						mkdir($dir, 0777);
					}
				}
			}
		}
	}

	//判断管理员用户类型
	function checkadmintype($id)
	{
		global $dsql,$arrs,$logger;
		$usertype=1;
		$sql = "select * from users where userid=".$id;
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			set_error_msg("sql error:".mysql_error());
		}
		else
		{

			$tmp_arr = array();
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$usertype = $result["usertype"];
			}
		}

		if($usertype==1)
		{
			return true;
		}
		else
		{
			return	false;
		}
	}
	//判断是否GET访问
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
		$arg_id = isset($_GET['tid']) ? $_GET['tid'] : 0;
		$arg_name = isset($_GET['tenantname']) ? $_GET['tenantname'] :null;
		$arg_web = isset($_GET['weburl']) ? $_GET['weburl'] :null;
	}

	switch ($arg_type)
	{
		case TYPE_GETTENANT:
			gettenant();
			break;
		case TYPE_GETALLTENANT:
			getalltenant();
			break;
		case TYPE_ADDTENANT:
			addtenant();
			break;
		case TYPE_UPDATETENANT:
			updatetenant($arrsdata);
			break;
		case TYPE_DELETETENANT:
			deletetenant($arg_id);
			break;
		case TYPE_SEARCHTENANT:
			searchtenant(FALSE);
			break;
		case TYPE_GETTENANTBYNAME:
			gettenantbyname($arg_id,$arg_name,$arg_web);
			break;
		case TYPE_GETTENANTBYID:
			gettenantbyid($arg_id);
			break;
		case TYPE_GETTENANTBYDOMAIN:
			gettenantbydomain($arg_id);
			break;
		case TYPE_GETTENANTBYROLE:
			gettenantbyrole();
			break;
		case TYPE_GETALLTENANT2:
			getalltenant2();
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



