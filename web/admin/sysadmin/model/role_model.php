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
define('TYPE_GETALLROLE', 'getallrole');
define('TYPE_GETROLEBYID', 'getrolebyid');    //获取角色
define('TYPE_GETROLE', 'getrole');    //获取所有角色
define('TYPE_GETROLEBYNAME', 'getrolebyname');
define('TYPE_GETROLEBYTYPE', 'getrolebytype');
define('TYPE_GETROLEBYTENANT', 'getrolebytenant');
define('TYPE_ADDROLE', 'addrole');    //添加角色
define('TYPE_UPDATEROLE', 'updaterole');    //修改角色
define('TYPE_DELETEROLE', 'deleterole');    //删除角色
define('TYPE_SEARCHROLE', 'searchrole');    //查询函数
define('TYPE_ADDROLERESOURCE', 'addroleresource');    //查询函数
define('TYPE_GETROLESCOPE', 'getrolescope');    //查询函数
define('TYPE_ADDSCOPEINFO', 'addscopeinfo');    //添加范围信息
define('TYPE_GETSCOPEINFO', 'getscopeinfo');    //获取所有租户


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
$arg_resid;

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

function getscopeinfo()
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

//添加新角色
function addrole()
{
	global $arrs,$arrsdata,$dsql,$logger;
	if(!Authorization::checkUserUseage(4,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{	$arrs["result"]=true;
	$sql = "insert into ".DATABASE_ROLE." (label,roletype,description,updatetime)
	        values('".$arrsdata->label."',".$arrsdata->roletype.",'".$arrsdata->description."',".time().")";


	$qr = $dsql->ExecQuery($sql);
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

}
/*
 * 获取所有用户信息
 */
function getallrole($type)
{
	global $arrs,$arg_pagesize,$arg_search_page,$dsql,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	if(!Authorization::checkUserUseage(4,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$totalCount ="select count(*) as totalcount from ".DATABASE_ROLE." where roletype=".$type;
		$sql="select * from ".DATABASE_ROLE." where roletype=".$type." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
		$qr = $dsql->ExecQuery($totalCount);
		if(!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$arrs["totalcount"]=$result["totalcount"];
			}

			$qr2 = $dsql->ExecQuery($sql);


			if (!$qr2)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else
			{
				$temp_arr = array();
				while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
				{
					$temp_arr["roleid"] = $result["roleid"];
					$temp_arr["label"] = $result["label"];
					$temp_arr["roletype"] = $result["roletype"];
					$temp_arr["description"] = $result["description"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["flag"]=1;
			}

		}

	}
}

// 获取租户信息添加用户时使用此函数
function getrole($roletype)
{
	global $dsql,$arrs,$logger,$logger;
	$sql = "select * from ".DATABASE_ROLE." where roletype=".$roletype;
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
			$tmp_arrs["roletype"] = $result["roletype"];
			$tmp_arrs["description"] = $result["description"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}
}



// 获取租户信息添加用户时使用此函数
function getrolescope($roleid,$resid)
{
	global $dsql,$arrs,$logger;

	$sql = "select * from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid." and resourceid =".$resid;

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
			$tmp_arrs["resourceid"] = $result["resourceid"];
			$tmp_arrs["childid"] = $result["childid"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}
}

//根据类型获取角色信息
function getrolebytype($typeid)
{
	global $dsql,$arrs,$logger;
	$sql = "select * from ".DATABASE_ROLE." where roletype =".$typeid;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["roletype"] = $result["roletype"];
			$tmp_arrs["description"] = $result["description"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}
}

//查找角色名称是否重复
function getrolebyname($rid,$name,$type)
{
	global $dsql,$arrs,$logger;
	if($rid==0)
	{
		$sql = "select count(*) as totalcount from ".DATABASE_ROLE." where label='".$name."' and roletype=".$type;
	}
	else
	{
		//修改时执行此查询
		$sql = "select count(*) as totalcount from ".DATABASE_ROLE." where label='".$name."' and roleid <> ".$rid." and roletype=".$type;
	}

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$arrs['totalcount']=$result["totalcount"];
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


}


//根据ID获取角色ixnxi
function getrolebyid($rid)
{
	global $dsql,$arrs,$logger;

	$sql = "select *  from ".DATABASE_ROLE." where roleid=".$rid;

	//echo $sql;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["roletype"] = $result["roletype"];
			$tmp_arrs["description"] = $result["description"];
			$tmp_arrs["updatetime"] = $result["updatetime"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}

}

//获取某一租户的角色信息
function getrolebytenant($tid)
{
	global $dsql,$arrs,$logger;

	$sql = "select a.*,b.label from ".DATABASE_TENANT_ROLE_MAPPING." as a inner join ".DATABASE_ROLE." as b on a.roleid =b.roleid where a.tenantid=".$tid." group by roleid";
	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
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
		if(!empty($tmp_arrs)){
			$arrs["flag"]=1;
		}
	}

}


//获取某一租户的角色信息
function gettenantrole($tid)
{
	global $dsql,$arrs,$logger;

	$sql = "select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." as a inner join ".DATABASE_ROLE." as b on a.roleid =b.roleid where a.tenantid=".$tid." group by roleid";

	//echo $sql;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}

}



//删除角色,删除角色租户关系,删除角色资源关系
function deleterole($ID)
{
	global $dsql,$arrs,$logger;
	if(!Authorization::checkUserUseage(4,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$deltrole="delete from ".DATABASE_ACCOUNTING_RULE." where roleid in (select roleid from role where roleid in(".$ID."))";
		$delroleres="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid in (".$ID.")";
		$delrole = "delete from ".DATABASE_ROLE." where roleid in (".$ID.")";
		$q1 = $dsql->ExecQuery($deltrole);
		if (!$q1)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$q2 = $dsql->ExecQuery($delroleres);
			if(!$q2)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;

			}
			else
			{
				$q3 = $dsql->ExecQuery($delrole);
				if(!$q3)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;

				}
				else
				{
					$arrs["flag"]=1;
				}

			}

		}
	}

}
//修改角色信息
function updaterole()
{
	global $dsql,$arrs,$arrsdata,$logger;
	if(!Authorization::checkUserUseage(4,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$sql = "update ".DATABASE_ROLE." set label='".$arrsdata->label."',description='".$arrsdata->description."' ,updatetime=".time()." where roleid =".$arrsdata->id;
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

}


//创建角色资源关系, settentantresource.html调用, settentantresource.html页面没有调用
function addroleresource($roleid,$resourcedata,$roletype)
{
	global $dsql,$arrs,$logger;

	if($resourcedata=="")
	{
		$sql="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid;
		$delrule="delete from ".DATABASE_ACCOUNTING_RULE." where roleid=".$roleid;

		$q = $dsql->ExecQuery($sql);

		if(!$q)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
			echo json_encode($arrs);
			exit;
		}
		$q2 = $dsql->ExecQuery($delrule);
		if(!$q2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
			echo json_encode($arrs);
			exit;
		}

	}
	else
	{
		if($roletype==0)
		{
			$resarr = explode(",",$resourcedata);
			if(count($resarr)>0)
			{

				//$q = $dsql->ExecQuery($sql);
				$sqldel = "delete from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid;
				$qdel = $dsql->ExecQuery($sqldel);
					
				if(!$qdel)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}
				//创建资源角色关系向角色资源关系表插入数据
				foreach($resarr as $key => $value){


					$sql2 = "insert into ".DATABASE_ROLE_RESOURCE_RELATION." (resourceid,roleid,permission,updatetime)
		         values(".$value.",".$roleid.",1,'".date(('Y-m-d G:i:s'),time())."')";
					$q = $dsql->ExecQuery($sql2);
					if(!$q)
					{
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
						$arrs["flag"]=0;
						echo json_encode($arrs);
						exit;
					}
				}//end foreach
				$arrs["flag"]=1;
			}
		}
		else
		{
			if(count($resarr)>0)
			{

				$sqldel = "delete from ".DATABASE_TENANT_ROLE_RESOURCE." where roleid=".$roleid;
				$qdel = $dsql->ExecQuery($sqldel);
				if(!$qdel)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}
				//创建资源角色关系向角色资源关系表插入数据
				foreach($resarr as $key => $value){


					$sql2 = "insert into ".DATABASE_TENANT_ROLE_RESOURCE." (resourceid,troleid,permission,updatetime)
		         values(".$value.",".$roleid.",1,".time().")";
					$q = $dsql->ExecQuery($sql2);
					if(!$q)
					{
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
						$arrs["flag"]=0;
						echo json_encode($arrs);
						exit;
					}
				}//end foreach
				$arrs["flag"]=1;
			}
		}

	}
}

	//修改角色资源关系
	function updateroleresource($roleid,$resourcedata)
	{
		global $arrs,$logger;
		$sql = "delete from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid;
		$resarr = explode(",",$resourcedata);
		if(count($resarr)>0)
		{
			//创建资源角色关系向角色资源关系表插入数据
			foreach($resarr as $key => $value){


				$sql2 = "insert into ".DATABASE_ROLE_RESOURCE_RELATION." (resourceid,roleid,permission,updatetime)
		         values(".$value.",".$roleid.",1,'".date(('Y-m-d G:i:s'),time())."')";
				$q = $dsql->ExecQuery($sql2);
				if(!$q)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}
			}
		}


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




	//根据类型获取角色信息
	function addscopeinfo()
	{
		global $dsql,$arrs,$arrsdata,$logger;
		$resdata = $arrsdata->scopeid;


		switch($arrsdata->resid)
		{
			case 2:
				//删除旧的关系
				$delsql="delete from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$arrsdata->roleid." and resourceid=".$arrsdata->resid;
				$q = $dsql->ExecQuery($delsql);
				if(!$q)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}
				//增加新的关系

				if(($resdata!=null)&&($resdata!=""))
				{
					$arrdres = explode(",",$resdata);

					foreach($arrdres as $key => $value)
					{
						$sql = "insert into ".DATABASE_ROLE_RESOURCE_RELATION." (roleid,resourceid,childid,updatetime) values(".$arrsdata->roleid.",".$arrsdata->resid.",".$value.",".time().")";
						$q = $dsql->ExecQuery($sql);
						if(!$q)
						{
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
							$arrs["flag"]=0;
							//echo json_encode($arrs);
							exit;
						}
					}
					$arrs["flag"]=1;

				}
				else
				{
					$sql = "insert into ".DATABASE_ROLE_RESOURCE_RELATION." (roleid,resourceid,updatetime) values(".$arrsdata->roleid.",".$arrsdata->resid.",".time().")";
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
				break;
			default:
				$arrs["flag"]=0;
				break;
		}
	}




	if (empty($_GET))
	{
		if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
		{
			global $arrsdata;
			$arrsdata = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
			//$arrsdata = $_REQUEST;
			$arg_type = $arrsdata->type;
			if(isset($arrsdata->id)){ //修改用户
				$arg_id = $arrsdata->id;
			}
			if(isset($arrsdata->roleid)){ //设置资源
				$arg_id = $arrsdata->roleid;
			}
		}
		//set_error_msg("opt is null");
	}
	else
	{
		$arg_type = isset($_GET['type']) ? $_GET['type'] : null;
		$arg_id = isset($_GET['roleid']) ? $_GET['roleid'] : 0;
		$arg_roletype = isset($_GET['roletype']) ? $_GET['roletype'] : 0;
		$arg_tid = isset($_GET['tid']) ? $_GET['tid'] : 0;
		$arg_resid = isset($_GET['modelid']) ? $_GET['modelid'] : 0;
		$arg_name = isset($_GET['name']) ? $_GET['name'] : null;

	}

	switch ($arg_type)
	{
		case TYPE_GETROLE:
			getrole($arg_roletype);
			break;
		case TYPE_GETALLROLE:
			getallrole($arg_tid);
			break;
		case TYPE_ADDROLE:
			addrole();
			break;
		case TYPE_UPDATEROLE:
			updaterole();
			break;
		case TYPE_DELETEROLE:
			deleterole($arg_id);
			break;
		case TYPE_SEARCHROLE:
			searchrole(FALSE);
			break;
		case TYPE_GETROLEBYNAME:
			getrolebyname($arg_id,$arg_name,$arg_tid);
			break;
		case TYPE_GETROLEBYID:
			getrolebyid($arg_id);
			break;
		case TYPE_GETROLEBYTENANT:
			getrolebytenant($arg_tid);
			break;
		case TYPE_ADDROLERESOURCE:
			addroleresource($arg_id,$arrsdata["resourceid"]);
			break;
		case TYPE_GETROLEBYTYPE:
			getrolebytype($arg_tid);
			break;
		case TYPE_GETROLESCOPE:
			getrolescope($arg_tid,$arg_resid);
			break;
		case TYPE_ADDSCOPEINFO:
			addscopeinfo();
			break;
		case TYPE_GETSCOPEINFO:
            getscopeinfo();
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



