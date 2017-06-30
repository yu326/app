<?php
include_once('includes.php');
include_once("datatableresult.php");
include_once('checkpure.php');
define('TYPE_PAGE','pagecontroller.php');
define('CHILDS', "children");

//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');


//获取数据类型 (type)的具体内容
define('TYPE_GETAll', 'getallpage');    //获取所有模板信息
define('TYPE_ADDPAGE', 'addpage');    //添加模板
define('TYPE_UPDATEPAGE', 'updatetepage');    //修改模板
define('TYPE_DELETEPAGE', 'deletetepage');    //删除模板
define('TYPE_SEARCHPAGE', 'searchpage');    //查询模板
define('TYPE_GETPAGEBYID', 'getpagebyid');  //根据ID查询模板
define('TYPE_CHECKPAGEBYID', 'checkpagebyid');    //
define('TYPE_CHECKPAGE', 'checkpage');
define('TYPE_SAVEPAGE', 'savepage');
define('TYPE_GETPAGEBYTENANT', 'getpagebytenant');



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
$filepath;
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

//添加模板信息
function addpage()
{
	global $dsql,$arrs,$arrsdata;

	$pageid=0;
	$sql = "insert into ".DATABASE_PAGE_DESCRIPTION." (title,filepath,tenantid,updatetime) values ('".$arrsdata["title"]."','".$arrsdata["filepath"]."',".$arrsdata["tenantid"].",'".time()."')";
	$sqlgetid = "select LAST_INSERT_ID() as pid";
	//echo $sql."-";
	//echo $sqlgetid;
	//echo "-";

	try
	{
		$qr = $dsql->ExecQuery($sql);
		if($qr)
		{
			$qr2 = $dsql->ExecQuery($sqlgetid);
			if(!$qr2)
			{
				throw new Exception(TYPE_PAGE."- addpage()-".$sql."-".mysql_error());
			}
			else
			{
				while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
				{
					$pageid = $result["pid"];
				}
					
			}

		}


		$sqlinsert = "insert into ".DATABASE_CUSTOMER_NAVIGATE."(tenantid,label,pageid,updatetime,homepage) values (".$arrsdata["tenantid"].",'".$arrsdata["title"]."',".$pageid.",".time().",".$arrsdata["homepage"].")";
		//echo $sqlinsert;
		
		$sqlgetid = "select LAST_INSERT_ID() as modelid";
		$qr2=$dsql->ExecQuery($sqlinsert);
		$qrmodelid = $dsql->ExecQuery($sqlgetid);
		if(!$qrmodelid)
			{
				throw new Exception(TYPE_PAGE."- addpage()-".$sql."-".mysql_error());
			}
			else
			{
				while ($result = $dsql->GetArray($qrmodelid, MYSQL_ASSOC))
				{
					$arrs["modelid"] = $result["modelid"];
				}
					
			}

		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}

/*
 * 获取模板
 */
function getallpage()
{
	global $dsql,$arrs;

	$sql="select * from ".DATABASE_PAGE_DESCRIPTION." order by updatetime desc";
	$qr = $dsql->ExecQuery($sql);

	if(!$qr){
		throw new Exception(TYPE_PAGE."- gettemplate()-".$sql."-".mysql_error());
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["id"] = $result["id"];
			$temp_arr["title"] = $result["title"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["filepath"] = $result["filepath"];
			$temp_arr["updatetime"] = $result["updatetime"];

			$arrs[CHILDS][] = $temp_arr;
		}
	}
}

function getpagebyid($id)
{
	global $dsql,$arrs;
	$num=0;

	$sql="select * from ".DATABASE_PAGE_DESCRIPTION." where id=".$id;
	$qr = $dsql->ExecQuery($sql);


	if (!$qr)
	{
		throw new Exception(TYPE_PAGE."- getpagebyid()-".$sql."-".mysql_error());
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
				$temp_arr["id"] = $result["id"];
				$temp_arr["title"] = $result["title"];
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["filepath"] = $result["filepath"];
				$temp_arr["updatetime"] = $result["updatetime"];

				$arrs[CHILDS][] = $temp_arr;
			}
		}
	}
}

//获取某一租户的页面
function getpagebytenant($id)
{
	global $dsql,$arrs;
	$num=0;
	
	$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	$result = new DatatableResult();
$sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE;
	$sql="select a.*,b.filepath from(select * from ".DATABASE_CUSTOMER_NAVIGATE." limit {$iDisplayStart},{$iDisplayLength}) as a inner join ".DATABASE_PAGE_DESCRIPTION." as b 
on a.pageid = b.id where a.tenantid=".$id;
	
	$qr = $dsql->ExecQuery($sqlcount);
	//$qr = $dsql->ExecQuery($sql);


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
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(TASKMANAGER." - getpagebytenant() sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				while ($r = $dsql->GetObject($qr)){
					//echo date(('Y-m-d G:i:s'),$r["updatetime"]);
					//var_dump($r);
					$result->aaData[]=$r;
				}
			}
		}
	}
	echo json_encode($result);
}

//删除模板信息
function deletetemplate($ID)
{
	global $dsql,$arrs;

	$sql = "delete from ".DATABASE_PAGE_DESCRIPTION." where id in(".$ID.")";


	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		throw new Exception(TYPE_PAGE."- deletetemplate()-".$sql."-".mysql_error());
		$arrs["flag"]=0;
	}
	else
	{
		$arrs["flag"]=1;
	}
}

function updatepage($userarr)
{global $dsql,$arrs;
$sql = "update ".DATABASE_PAGE_DESCRIPTION." set title='".$userarr["title"]."',filepath='".$userarr["filepath"]." where id =".$userarr["id"];

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
function checkPageExist($label,$tid)
{
	global $dsql,$arrs;
	$num;

	$sql="select count(*) as totalcount from ".DATABASE_PAGE_DESCRIPTION." where title='".$label."' and tenantid=".$tid;

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


//保存页面文件
function savepage()
{global $dsql,$arrs,$arrsdata;
	try {
		//echo dirname(dirname(__FILE__));
		//$strurl=dirname(dirname(__FILE__))."/sysadmin/".$filepath;
		//echo $strurl;

		//获取文件内容
		//$content=file_get_contents($strurl);
		//检查是否存在旧文件，有则删除
		//if(file_exists($filename)) unlink($filename);
		//设置静态文件路径及文件名
		$sRealPath = realpath('./');
		$sSelfPath = $_SERVER['PHP_SELF'] ;
		$filepath = substr( $sRealPath, 0, strrpos( $sRealPath,'\\'));
		if($arrsdata["homepageflag"]==1)
		{
			$filename=$filepath."/tenant/".$arrsdata["path"]."/index.shtml";
		}
		else
		{
			$filename=$filepath."/tenant/".$arrsdata["path"]."/".$arrsdata["modelid"].".shtml";
		}
echo $filename;
$head_str="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">";
$str = str_replace('\"','"',$arrsdata["content"]);
$strpage = $head_str.$str."</html>";

		$fp = fopen($filename, 'w');
		fwrite($fp, $strpage);
		//file_put_contents("a.shtml",);
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
	$filepath = isset($_GET["filepath"]) ? $_GET["filepath"] : "";

}

mysql_query("SET NAMES utf8");

switch ($arg_type)
{
	case TYPE_GETAll:
		getallpage();
		break;
	case TYPE_ADDPAGE:
		addpage();
		break;
	case TYPE_UPDATEPAGE:
		updatepage($arrsdata);
		break;
	case TYPE_DELETETEMPAGE:
		deletepage($arg_id);
		break;
	case TYPE_SEARCHPAGE:
		searchpage(FALSE);
		break;
	case TYPE_CHECKPAGE:
		checkPageExist($arg_name,$arg_id);
		break;
	case TYPE_GETPAGEEBYID:
		getpagebyid($arg_id);
		break;
	case TYPE_CHECKPAGEBYID:
		getoagebyid($arg_name,$arg_tid,$arg_id);
		break;
	case TYPE_SAVEPAGE:
		savepage();
		break;
	case TYPE_GETPAGEBYTENANT:
		getpagebytenant($arg_id);
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



