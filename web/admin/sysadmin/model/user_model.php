<?php
include_once('includes.php');
include_once 'commonFun.php';
include_once 'userinfo.class.php';
include_once('model_config.php');
include_once ('checkpure.php');
include_once("authorization.class.php");

initLogger(LOGNAME_WEBAPI);
define( "CONFIG_TYPE", 3);
define('CHILDS', "children");
session_start();
//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');
define('ARG_USERNAME', 'username');
define('ARG_REALNAME', 'realname');
define('ARG_PWD', 'password');
define('ARG_EMAIL', 'email');
define('ARG_USERID', 'uid');
define('ARG_TENANTID', 'tid');

//获取数据类型 (type)的具体内容
define('TYPE_GETAll', 'getalluser');    //获取所有用户
define('TYPE_ADDUSER', 'adduser');    //添加用户
define('TYPE_GETUSERLIST', 'getuserlist'); //获取用户列表
define('TYPE_UPDATEUSER', 'updateuser');    //修改用户
define('TYPE_UPDATEPWD', 'updatepwd');    //修改密码
define('TYPE_DELETEUSER', 'deleteuser');    //
define('TYPE_SEARCHUSER', 'searchuser');    //查询函数
define('TYPE_CHECKUSER', 'checkuser');    //查询函数
define('TYPE_CHECKUSEREXIST', 'checkuserexist'); //检查用户是否已存在
define('TYPE_GETUSERBYID', 'getuserbyid');    //查询函数
define('TYPE_CHECKUSERBYID', 'checkuserbyid');    //查询函数
define('TYPE_CREATEVALIDATE', 'validate');
define('TYPE_LOGIN', 'login');
define('TYPE_EXISTSESSION', 'existsession');//验证session是否存在 js调用

$arg_search_page;// = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize; //= isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_name;
$arg_tid;


if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$arrs["result"]=false;
	$arrs["msg"]="未登录或登陆超时!";
	echo json_encode($arrs);
	exit;
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

//添加新用户
function addnewuser()
{
	global $dsql,$arrs,$arrsdata,$logger;

	if(!Authorization::checkUserUseage(1,1,$childid=null)&&!Authorization::checkUserUseage(3,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		if(isset($arrsdata["binduserid"]) && $arrsdata["binduserid"]!=""){
			$binduserid = $arrsdata["binduserid"];
		}
		else{
			$binduserid = 'null';
		}
		$pwd = $arrsdata["password"];
		if($pwd == ""){
			$arrs["result"]=false;
			$arrs["msg"]="密码不能为空!";
			echo json_encode($arrs);
			exit;
		}
		$sql = "insert into ".DATABASE_USERS." (username,realname,password,email,expiretime,tenantid,updatetime, binduserid, usertype,alloweditinfo) values ('".$arrsdata["username"]."','".$arrsdata["realname"]."','".$pwd."','".$arrsdata["email"]."','".$arrsdata["expiretime"]."',".$arrsdata["tid"].",".time().", ".$binduserid.", ".$arrsdata["usertype"].", ".$arrsdata["alloweditinfo"].")";
		$qr = $dsql->ExecQuery($sql);

		if(!$qr)
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
function getuserlist(){
	global $dsql, $arrs, $arrsdata, $logger,$arg_pagesize,$arg_search_page;
	if(!Authorization::checkUserUseage(3,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else{
		$arrs["result"]=true;
	//where
	$where = array();
		//用户名
	if(isset($arrsdata["username"])){
		$username = $arrsdata["username"]; 
		$pos = strpos($username, '*');
		if($pos === false){
			$where[] =  "users.username = '".$username."'";
		}
		else{
			$username= str_replace("*", "%", $username);       
			$where[] =  "users.username like '".$username."'";
		}
	}
		//真实姓名
	if(isset($arrsdata["realname"])){
		$realname = $arrsdata["realname"]; 
		$pos = strpos($realname, '*');
		if($pos === false){
			$where[] =  "users.realname = '".$realname."'";
		}
		else{
			$realname= str_replace("*", "%", $realname);       
			$where[] =  "users.realname like '".$realname."'";
		}
	}
	//所属租户
	if(isset($arrsdata["tenantname"])){
		$tenantname = $arrsdata["tenantname"]; 
		$pos = strpos($tenantname, '*');
		if($pos === false){
			$where[] =  "b.tenantname = '".$tenantname."'";
		}
		else{
			$tenantname= str_replace("*", "%", $tenantname);       
			$where[] =  "b.tenantname like '".$tenantname."'";
		}
	}
	//电子邮件
	if(isset($arrsdata["email"])){
		$email = $arrsdata["email"]; 
		$pos = strpos($email, '*');
		if($pos === false){
			$where[] =  "users.email = '".$email."'";
		}
		else{
			$email = str_replace("*", "%", $email);       
			$where[] =  "users.email like '".$email."'";
		}
	}

	//usertype
	if(isset($arrsdata["usertype"])){
		$usertype = isset($arrsdata["usertype"]) ? $arrsdata["usertype"] : 0;
		$where[] = "users.usertype = ".$usertype."";
	}
	//binduserid
	if(isset($arrsdata["binduserid"])){
		$where[] = "users.binduserid = ".$arrsdata["binduserid"]."";
		//$binduserid = " and binduserid = ".$arrsdata["binduserid"];
	}
	//允许修改信息
	if(isset($arrsdata["alloweditinfo"])){
		$alloweditinfo = $arrsdata["alloweditinfo"];
		$where[] = "users.alloweditinfo = ".$alloweditinfo."";
	}

	//失效时间
	if(isset($arrsdata["expiretimestart"]) && isset($arrsdata["expiretimeend"])){
		$etstart = $arrsdata["expiretimestart"];
		$etend = $arrsdata["expiretimeend"];
		$where[] = "users.expiretime > ".$etstart." AND users.expiretime  < ".$etend."";
	}
	else if(isset($arrsdata["expiretimestart"])){
		$etstart = $arrsdata["expiretimestart"];
		$where[] = "users.expiretime > ".$etstart."";
	}
	else if(isset($arrsdata["expiretimeend"])){
		$etend = $arrsdata["expiretimeend"];
		$where[] = "users.expiretime < ".$etend."";
	}

	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
		$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
		$sqltotal = "SELECT count(*) as totalcount from ".DATABASE_USERS." LEFT JOIN ".DATABASE_TENANT." AS b ON users.tenantid = b.tenantid ".$wherestr."";
		$sql = "SELECT users.*,b.tenantname from ".DATABASE_USERS." LEFT JOIN ".DATABASE_TENANT." AS b ON users.tenantid = b.tenantid ".$wherestr." ORDER BY users.updatetime DESC limit ".$limit_cursor.",".$arg_pagesize."";
		$qrtotal = $dsql->ExecQuery($sqltotal);
		$qr = $dsql->ExecQuery($sql);
		if (!$qrtotal){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltotal} ".$dsql->GetError());
		}
		else
		{
			while ($result = $dsql->GetArray($qrtotal, MYSQL_ASSOC))
			{
				$arrs["totalcount"]=$result["totalcount"];
			}
		}

		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$num = $dsql->GetTotalRow($qr);
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				if(isset($result["binduserid"])){
					$sqlbindusername = "select username as bindusername from ".DATABASE_USERS." where  binduserid=".$result["binduserid"];
					$qrbindusername = $dsql->ExecQuery($sqlbindusername);
					if(!$qrbindusername){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlbindusername} ".$dsql->GetError());
					}
					else{
						while ($bu = $dsql->GetArray($qrbindusername, MYSQL_ASSOC)){
							$temp_arr["bindusername"]=$bu["bindusername"];
						}
					}
				}
				$temp_arr["userid"] = $result["userid"];
				$temp_arr["username"] = $result["username"];
				$temp_arr["realname"] = $result["realname"];
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["tenantname"] = $result["tenantname"];
				$temp_arr["usertype"] = $result["usertype"];
				$temp_arr["email"] = $result["email"];
				$temp_arr["expiretime"] = $result["expiretime"];
				$temp_arr["isonline"] = $result["isonline"];
				$temp_arr["onlinetime"] = $result["onlinetime"];
				$temp_arr["alloweditinfo"] = $result["alloweditinfo"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);

				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
}
/*
 * 获取所有用户信息
 */
function getalluser($utype,$id)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger, $arrsdata;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

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
		$totalCount ="select count(*) as totalcount from ".DATABASE_USERS." where  tenantid=".$id;
		if($utype==3)
		{

			if(isset($arrsdata["usertype"])){
				$usertype = $arrsdata["usertype"];
			}
			else{
				$usertype = 1;
			}
			$binduserid = "";
			if(isset($arrsdata["binduserid"])){
				$binduserid = " and binduserid = ".$arrsdata["binduserid"];
			}
			$totalCount ="select count(*) as totalcount from ".DATABASE_USERS." where tenantid <>-1".$binduserid;

			$sql="select a.*,b.tenantname from (select * from ".DATABASE_USERS." where  tenantid<>-1 and usertype=".$usertype." ".$binduserid." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize.") as a left join ".DATABASE_TENANT." as b on a.tenantid = b.tenantid";
		}
		else
		{
			$totalCount ="select count(*) as totalcount from ".DATABASE_USERS." where  tenantid=-1";
			$sql="select * from ".DATABASE_USERS." where tenantid=-1 order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
		}
		$qr = $dsql->ExecQuery($sql);
		$qr2 = $dsql->ExecQuery($totalCount);

		if (!$qr2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		}
		else
		{
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
			{
				$arrs["totalcount"]=$result["totalcount"];
			}
		}

		if (!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$num = $dsql->GetTotalRow($qr);
			$temp_arr = array();
			while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$temp_arr["userid"] = $result["userid"];
				$temp_arr["username"] = $result["username"];
				$temp_arr["realname"] = $result["realname"];
				$temp_arr["tenantid"] = $result["tenantid"];
				if($utype==3)
				{
					$temp_arr["tenantname"] = $result["tenantname"];
				}
				//$temp_arr["usertype"] = $result["usertype"];
				$temp_arr["email"] = $result["email"];
				$temp_arr["expiretime"] = $result["expiretime"];
				$temp_arr["isonline"] = $result["isonline"];
				$temp_arr["onlinetime"] = $result["onlinetime"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);

				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}




	}//结束权限判断
}

function getuserbyid($userid)
{
	global $dsql,$arrs,$logger;
	$num=0;
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
		$sql="select * from ".DATABASE_USERS." where userid=".$userid;
		$qr = $dsql->ExecQuery($sql);
		if (!$qr) {
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
					$temp_arr["userid"] = $result["userid"];
					$temp_arr["username"] = $result["username"];
					$temp_arr["realname"] = $result["realname"];
					$temp_arr["tenantid"] = $result["tenantid"];
					$temp_arr["email"] = $result["email"];
					$temp_arr["expiretime"] = $result["expiretime"];
					$temp_arr["isonline"] = $result["isonline"];
					$temp_arr["onlinetime"] = $result["onlinetime"];
					$temp_arr["updatetime"] = $result["updatetime"];

					$arrs[CHILDS][] = $temp_arr;
				}
			}
			$arrs["flag"]=1;
		}

	}
}

//删除用户,删除用户的同时删除角色用户对应关系
function deleteuser($ID)
{
	global $dsql,$arrs,$logger;

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
		$sql = "delete from ".DATABASE_USERS." where userid in(".$ID.")";
		$sql2 = "delete from ".DATABASE_USER_ROLE_MAPPIMG." where userid in(".$ID.")";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$qr2 = $dsql->ExecQuery($sql2);
			if(!$qr2)
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
function updatepwd(){
	global $dsql,$arrs,$logger, $arrsdata;
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
		$sql = "update ".DATABASE_USERS." set password='".$arrsdata["password"]."' where userid =".$arrsdata["userid"];
		$qr = $dsql->ExecQuery($sql);
		if(!$qr)
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
function updateuser($userarr)
{
	global $dsql,$arrs,$logger, $arrsdata;
	if(isset($arrsdata["userid"])){
		$userarr = $arrsdata;
	}
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
		/*
		if($userarr["pwd"]!=null&&$userarr["pwd"]!="")
		{
			//$sql = "update users set username='".$userarr["username"]."',realname='".$userarr["realname"]."',password='".$userarr["pwd"]."' where userid =".$userarr["id"];
			$sql = "update ".DATABASE_USERS." password='".$userarr["pwd"]."' where userid =".$userarr["userid"];
		}
		else
		{
		 */
			$sql = "update ".DATABASE_USERS." set username='".$userarr["username"]."',realname='".$userarr["realname"]."',tenantid=".$userarr["tid"].",email ='".$userarr["email"]."',expiretime ='".$userarr["expiretime"]."',updatetime='".time()."', alloweditinfo='".$userarr["alloweditinfo"]."' where userid =".$userarr["userid"];
		/*
		}
		 */



		$qr = $dsql->ExecQuery($sql);
		if(!$qr)
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

//检查用户名称是否重复
function checkUserExist($username,$tid)
{
	global $dsql,$arrs,$logger, $arrsdata;
	$num;
	if(isset($arrsdata["username"])){
		$username = $arrsdata["username"];
	}
	if(isset($arrsdata["tid"])){
		$tid = $arrsdata["tid"];
	}
	$sql="select count(*) as totalcount from ".DATABASE_USERS." where username='".$username."' and tenantid=".$tid;
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
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
}

//检查用户名称是否重复
function checkUserExistByUserID($username,$tid,$uid)
{
	global $dsql,$arrs,$logger;
	$num;

	$sql="select count(*) as totalcount from ".DATABASE_USERS." where username='".$username."' and tenantid=".$tid." and userid <>".$uid;

	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
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


}

//生成默认密码
/*
function createpass()
{
	$strpass="inter3i";
	$strpass2 = md5($strpass);
	return $strpass2;
}
 */


//登录验证
function login()
{

	global $dsql,$arrs,$arrsdata,$logger;
	$usertype=getLocalType();//获取当前登录的平台类型
	$tcode = getTenantCode();//获取二级域名
	if(empty($tcode)){
		$arrs['result'] = false;
		$arrs['msg'] = "地址错误，请输入二级域名";
		return;
	}
	if($arrsdata["mark"]==$_SESSION["VCODE"])
	{
		checkuser($arrsdata,$usertype,$tcode);
	}
	else
	{
		$arrs["result"]=false;
		$arrs['msg'] = "验证码错误";
	}
}

$arrsdata = $_REQUEST;
$arg_search_page = isset($arrsdata[ARG_SEARCH_CURRPAGE]) ? $arrsdata[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($arrsdata[ARG_SEARCH_PAGESIZE]) ? $arrsdata[ARG_SEARCH_PAGESIZE] : 10;
$arg_id = isset($arrsdata[ARG_USERID]) ? $arrsdata[ARG_USERID] : 0;
$arg_name = isset($arrsdata[ARG_USERNAME]) ? $arrsdata[ARG_USERNAME] : null;
$arg_tid = isset($arrsdata[ARG_TENANTID]) ? $arrsdata[ARG_TENANTID] : 0;
$arg_type = isset($arrsdata["type"]) ? $arrsdata["type"] : null;


/*
 * 验证session是否存在，js调用
 */
function existSession(){
	global $arrs,$logger;
	if(!checkusersession())
	{
		$arrs["result"]=false;
		$arrs["msg"]="未登录或登陆超时!";
	}
	else{
		$arrs["result"]=true;
		$arrs["msg"]="";
	}
}

if($arg_type != TYPE_EXISTSESSION && $arg_type != TYPE_LOGIN){
	//判断session是否存在
	/*
	 if(!checkusersession())
	 {
	 $arrs["result"]=false;
	 $arrs["msg"]="未登录或登陆超时!";
	 echo json_encode($arrs);
	 exit;
	 }
	 */
}
if(isset($HTTP_RAW_POST_DATA))
{
    //if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
    //{
    global $arrsdata;
    //$arrsdata = $GLOBALS['HTTP_RAW_POST_DATA'];
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
    //$arg_elementid = $arrsdata->elementid;
    //$arg_instanceid = $arrsdata->instanceid;

    //}
}

switch ($arg_type)
{
	case TYPE_GETAll:
		getalluser($arg_tid,$arg_id);
		break;
	case TYPE_ADDUSER:
		addnewuser();
		break;
	case TYPE_GETUSERLIST:
		getuserlist();
		break;
	case TYPE_UPDATEUSER:
		updateuser($arrsdata);
		break;
	case TYPE_UPDATEPWD:
		updatepwd();
		break;
	case TYPE_DELETEUSER:
		deleteuser($arg_id);
		break;
	case TYPE_SEARCHUSER:
		searchUser(FALSE);
		break;
	case TYPE_CHECKUSER:
		checkUserExist($arg_name,$arg_tid);
		break;
	case TYPE_GETUSERBYID:
		getuserbyid($arg_id);
		break;
	case TYPE_CHECKUSERBYID:
		checkUserExistByUserID($arg_name,$arg_tid,$arg_id);
		break;
	case TYPE_CREATEVALIDATE:
		validate();
		break;
	case TYPE_LOGIN:
		login();
		break;
	case TYPE_EXISTSESSION:
		existSession();
		break;
	case TYPE_CHECKUSEREXIST:
		checkUserExist($arg_name,$arg_tid);
		break;
	default:
		set_error_msg("arg type has a error");
}


//closeMysql();
if (!isset($arrs))
{
	echo "";
}
else
{
	$json_str = json_encode($arrs);
	echo $json_str;
}
