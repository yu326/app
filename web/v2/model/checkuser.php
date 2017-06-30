<?php
//解决跨域提取数据   by：wang    2016-10-14
header('Access-Control-Allow-Origin: http://intel.inter3i.com');
//解决跨域提取数据   by：wang    2016-10-14
define('TYPE_LOGIN', 'login');
define('TYPE_SYSLOGIN', 'syslogin');//系统管理后台登录
define('TYPE_LOGOUT', 'logout');
define('TYPE_EXISTSESSION', 'existsession');//验证session是否存在 js调用
define('TYPE_CHECKWEBURL','checkweburl');//验证二级域名是否正确
define('TYPE_CHECKDRILLDOWN','checkdrilldown');//验证是否有权限drilldown
define('TYPE_CHECKLINKAGE','checklinkage');//验证是否有权限联动
define('TYPE_GETTOKEN','gettoken');//获取token
define('TYPE_CHECKTOKEN', 'checktoken');//检查token是否有效
include_once('includes.php');
include_once('commonFun.php');
include_once('userinfo.class.php');
include_once('authorization.class.php');
initLogger(LOGNAME_WEBAPI);

session_start();
$arrs;
$arrsdata = $_REQUEST;
$arg_type = isset($arrsdata["type"]) ? $arrsdata["type"] : null;

switch ($arg_type)
{
	case TYPE_LOGIN:
		login();
		break;
	case TYPE_EXISTSESSION:
		existSession();
		break;
	case TYPE_LOGOUT:
		logout();
		break;
	case TYPE_SYSLOGIN:
		syslogin();
		break;
	case TYPE_CHECKWEBURL:
		checkweburl();
		break;
	case TYPE_CHECKDRILLDOWN:
		checkdrilldown();
		break;
	case TYPE_CHECKLINKAGE:
		checklinkage();
		break;
	case TYPE_GETTOKEN:
		gettoken();
		break;
	case TYPE_CHECKTOKEN:
		checktoken();
		break;
	default:
		$arrs['result'] = false;
		$arrs['msg'] = '参数错误';
}
echo json_encode($arrs);

/**
 *
 * 退出登录
 */
function logout(){
	global $arrs;
	if(isset($_SESSION) && isset($_SESSION['user'])){
		$_SESSION['user'] = null;
	}
	$arrs["result"]=true;
}

/**
 *
 * 验证二级域名是否合法
 * @return 返回值:{result:true/false, localtype:1/2}
 */
function checkweburl(){
	global $arrs,$arrsdata,$dsql,$logger;
	$usertype=getLocalType();//获取当前登录的平台类型
	if(empty($usertype)){
		$arrs['result'] = false;
		$arrs["errortype"] = "domainerror";
		$arrs['msg'] = "域名错误";
		return;
	}
	$tcode = getTenantCode();//获取二级域名
	if(empty($tcode)){
		$arrs['result'] = false;
		$arrs["errortype"] = "addrerror";
		$arrs['msg'] = "地址错误，请输入二级域名";
		return;
	}
	else{
		$sql = "select tenantid, webname, selfstyle from tenant where weburl = '{$tcode}' and localtype={$usertype}";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$arrs["result"]=false;
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		}
		else{
			$num = $dsql->GetTotalRow($qr);
			if($num>0){
				$result = $dsql->GetArray($qr, MYSQL_ASSOC);
				$arrs["webname"] = $result["webname"];
				$arrs["tenantid"] = $result["tenantid"];
				$arrs["selfstyle"] = $result["selfstyle"];
				//$arrs["description"] = $result["description"];
				//$arrs["logourl"] = $result["logourl"];
				$arrs["result"]= true;
				$arrs['localtype'] = $usertype;
			}
			else{
				$arrs["result"]= false;
				$arrs['localtype'] = $usertype;
			}
		}
	}
}

function checkdrilldown(){
	global $logger,$arrs,$dsql;

	$arrs["allowdrilldown"] = false;
	$arrs["allowvirtualdata"] = false;
	$arrs["allowaccessdata"] = false;
	$arrs["allowdownload"] = false;
	$arrs["allowupdatesnapshot"] = false;
	$arrs["alloweventalert"] = false;
	$arrs["allowoverlay"] = false;
	$arrs["allowlinkage"] = false;
	$arrs["allowwidget"] = false;
	$arrs["selfstyle"] = false;
	$arrs["accessdatalimit"] = 0;

	$user = Authorization::checkUserSession();
	if(!empty($user)){
		$user = isset($_SESSION['user']) ? $_SESSION['user'] : Authorization::getUserFromToken();
		$tid = $user->tenantid;
		//查询租户通用选项
		$tsql = "select allowdrilldown, allowdownload, allowupdatesnapshot, alloweventalert, allowwidget, allowaccessdata, accessdatalimit, selfstyle, allowvirtualdata, allowoverlay, allowlinkage from ".DATABASE_TENANT." where tenantid={$tid}";
		$tqr = $dsql->ExecQuery($tsql);
		if(!$tqr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$tsql} ".$dsql->GetError());
		}
		else{
			$tres = $dsql->GetArray($tqr, MYSQL_ASSOC);
		}
		//查询角色通用选项
		$rolearr = array();
		if(!empty($user->roles)){
			$rolearr = $user->roles;
			$rolestr = implode(",", $rolearr);
			$sql = "select allowdrilldown, allowdownload, allowupdatesnapshot, alloweventalert, allowwidget, allowaccessdata, accessdatalimit, allowvirtualdata, allowoverlay, allowlinkage from ".DATABASE_TENANT_ROLE_MAPPING." where tenantid={$tid} and roleid in (".$rolestr.")";
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			}
			else{
				$rolesOpt = array();
				while($rs = $dsql->GetArray($qr)){
					$rolesOpt[] = $rs;
				}
			}
			$userOptArr = getGeneralOpt($tres, $rolesOpt);
			$user->allowdrilldown = $userOptArr["allowdrilldown"];
			$user->allowvirtualdata = $userOptArr["allowvirtualdata"];
			$user->allowdownload = $userOptArr["allowdownload"];
			$user->allowupdatesnapshot = $userOptArr["allowupdatesnapshot"];
			$user->alloweventalert= $userOptArr["alloweventalert"];
			$user->allowoverlay = $userOptArr["allowoverlay"];
			$user->allowlinkage = $userOptArr["allowlinkage"];
			$user->allowwidget = $userOptArr["allowwidget"];
			$user->allowaccessdata = $userOptArr["allowaccessdata"];
			$user->selfstyle = $userOptArr["selfstyle"];
			$user->accessdatalimit = $userOptArr["accessdatalimit"];

			$arrs["allowdrilldown"] = isset($user->allowdrilldown) && $user->allowdrilldown;
			$arrs["allowvirtualdata"] = isset($user->allowvirtualdata) && $user->allowvirtualdata;
			$arrs["allowdownload"] = isset($user->allowdownload) && $user->allowdownload;
			$arrs["allowupdatesnapshot"] = isset($user->allowupdatesnapshot) && $user->allowupdatesnapshot;
			$arrs["alloweventalert"] = isset($user->alloweventalert) && $user->alloweventalert;
			$arrs["allowoverlay"] = isset($user->allowoverlay) && $user->allowoverlay;
			$arrs["allowlinkage"] = isset($user->allowlinkage) && $user->allowlinkage;
			$arrs["allowwidget"] = isset($user->allowwidget) && $user->allowwidget;
			$arrs["allowaccessdata"] = isset($user->allowaccessdata) && $user->allowaccessdata;
			$arrs["selfstyle"] = isset($user->selfstyle) && $user->selfstyle;
			$arrs["accessdatalimit"] = $user->accessdatalimit;
		}
		else{
			$logger->error(__FILE__." func: ".__FUNCTION__." user roles is empty ".var_export($user, true));
		}
	}
	return $arrs;
}
/*
function checklinkage(){
	global $arrs, $dsql;
	$user = Authorization::checkUserSession();
	if(!empty($user)){
		$user = isset($_SESSION['user']) ? $_SESSION['user'] : Authorization::getUserFromToken();
		$tid = $user->tenantid;
		$roleid = 0;
		if(!empty($user->roles)){
			$roleid = $user->roles[0];
		}
		else{
			$logger->error(__FILE__." func: ".__FUNCTION__." user roles is empty ".var_export($user, true));
		}
		//查询租户联动设置
		$sql = "select allowlinkage from tenant where tenantid={$tid}";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		}
		else{
			$rs = $dsql->GetArray($qr);
			//$user->allowlinkage = !empty($rs['allowlinkage']);
		}
		//查询角色联动设置
		$rsql = "select allowlinkage from tenant_role_mapping where tenantid={$tid} and roleid=".$roleid."";
		$rqr = $dsql->ExecQuery($rsql);
		if(!$rqr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$rsql} ".$dsql->GetError());
		}
		else{
			$rrs = $dsql->GetArray($rqr);
			//allowlinkage
			if(!empty($rs['allowlinkage'])){
				$user->allowlinkage = !empty($rrs['allowlinkage']);
			}
			else{
				$user->allowlinkage = false;
			}
		}
		$arrs["result"] = isset($user->allowlinkage) && $user->allowlinkage;
	}
	else{
		$arrs["result"] = false;
	}
	return $arrs["result"];
}
 */
/*
 * 验证用户并创建session，适用于前台登录
 */
function login()
{
	global $arrs,$arrsdata;
	$usertype=getLocalType();//获取当前登录的平台类型
	if(empty($usertype)){
		$arrs['result'] = false;
		$arrs["errortype"] = "domainerror";
		$arrs['msg'] = "域名错误";
		return;
	}
	$tcode = getTenantCode();//获取二级域名
	if(empty($tcode)){
		$arrs['result'] = false;
		$arrs["errortype"] = "addrerror";
		$arrs['msg'] = "地址错误，请输入二级域名";
		return;
	}
	if ($arrsdata['mark'] != "null") {
		if(isset($_SESSION["VCODE"]) && $arrsdata["mark"]==$_SESSION["VCODE"])
		{
			$r = checkuser($arrsdata['username'],$arrsdata['password'],$usertype,$tcode);
			if($r !== true){
				$arrs["result"]=false;
				$arrs["errortype"] = $r['errorcode'];
				$arrs['msg'] = $r['error'];
			}
			else{
				$arrs["result"]=true;
			}
		}
		else
		{
			$arrs["result"]=false;
			$arrs["errortype"] = "markerror";
			$arrs['msg'] = "验证码错误";
		}
	}else{
		if($arrsdata['username'] == "zibo"){
			$r = checkuser($arrsdata['username'],$arrsdata['password'],$usertype,$tcode);
			if($r !== true){
				$arrs["result"]=false;
				$arrs["errortype"] = $r['errorcode'];
				$arrs['msg'] = $r['error'];
			}
			else{
				$arrs["result"]=true;
			}
		}else{
			$arrs["result"]=false;
			$arrs["errortype"] = "markerror";
			$arrs['msg'] = "操作异常";
		}

	}	
}

/**
 * 验证用户创建session
 * @param $username 用户名
 * @param $password 密码
 * @param $localtype 租户类型，系统用户登录时，为null
 * @param $securl 二级域名 ，系统用户登录时为null
 */
function checkuser($username,$password,$localtype,$securl)
{
	global $dsql,$arrs,$logger;
	$checksql;
	if(empty($localtype)){
		$checksql = "select a.userid, a.expiretime, a.usertype, a.alloweditinfo, a.tenantid from users as a where a.username = '{$username}' and a.password='{$password}' and a.tenantid=-1";
	}
	else{
		$checksql = "select a.userid, a.expiretime, a.binduserid, a.usertype, a.alloweditinfo, a.tenantid, b.localtype,b.weburl, c.allowlinkage, c.allowdrilldown,c.allowdownload,c.allowupdatesnapshot,c.alloweventalert,c.allowoverlay, c.allowwidget, c.allowaccessdata,c.allowvirtualdata, b.accessdatalimit from users a inner join tenant b on a.tenantid=b.tenantid inner join tenant_role_mapping c on c.tenantid = b.tenantid where a.username = '{$username}' and a.password='{$password}' and b.weburl='{$securl}' and b.localtype={$localtype}";
	}

	$qr = $dsql->ExecQuery($checksql);
	if(!$qr){
		//$arrs["result"]=false;
		//$arrs["msg"]="登录失败，操作异常！";
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$checksql} ".$dsql->GetError());
		return getErrorOutput(LOGIN_ERROR_EXCEPTION, "操作异常");
	}
	else{
		$num = $dsql->GetTotalRow($qr);
		$logininfo = array();
		if($num>0)
		{
			$result = $dsql->GetArray($qr, MYSQL_ASSOC);
			$logininfo["userid"] = $result["userid"];
			//失效时间
			$expiretime = $result["expiretime"];
			//判断失效时间
			$now = time();
			if(!empty($expiretime) && $now > $expiretime){
				$logger->warn("login faild: expire time user");
				$errtype = LOGIN_ERROR_EXPIRE;
				$errmsg = "该账号已经过期";
				$logininfo["loginresult"] = LOGIN_ERROR_EXPIRE; 
				//return getErrorOutput($errtype, $errmsg);
			}
			else{
				$user = NULL;
				if(!empty($result)){
					$user = Authorization::createUserSession($result);
				}
				$_SESSION["user"] = $user;
				$logininfo["loginresult"] = LOGIN_ERROR_SUCCESS;
			}
			//return true;
		}
		else
		{
			//判断是用户名错误还是密码错误
			if(empty($localtype)){
				$checkusersql = "select a.userid, a.expiretime, a.usertype, a.alloweditinfo, a.tenantid from users as a where a.username = '{$username}' and a.tenantid=-1";
			}
			else{
				$checkusersql = "select a.userid, a.expiretime, a.binduserid, a.usertype, a.alloweditinfo, a.tenantid, b.localtype,b.weburl, c.allowlinkage, c.allowdrilldown,c.allowdownload,c.allowupdatesnapshot,c.alloweventalert,c.allowoverlay, c.allowwidget, c.allowaccessdata,c.allowvirtualdata, b.accessdatalimit from users a inner join tenant b on a.tenantid=b.tenantid inner join tenant_role_mapping c on c.tenantid = b.tenantid where a.username = '{$username}' and b.weburl='{$securl}' and b.localtype={$localtype}";
			}
			$usqr = $dsql->ExecQuery($checkusersql);
			if(!$usqr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$checkusersql} ".$dsql->GetError());
				return getErrorOutput(LOGIN_ERROR_EXCEPTION, "操作异常");
			}
			else{
				$unum = $dsql->GetTotalRow($usqr);
				$result = $dsql->GetArray($usqr, MYSQL_ASSOC);
				if($unum > 0){ //查询有对应的用户名
					$errtype = LOGIN_ERROR_NOPWD;
					$errmsg = "密码错误";
					$logininfo["loginresult"] = LOGIN_ERROR_NOPWD;
					$logininfo["userid"] = $result["userid"];
				}
				else{
					$errtype = LOGIN_ERROR_NOUSER;
					$errmsg = "用户名错误";
					$logininfo["loginresult"] = LOGIN_ERROR_NOUSER;
					$logininfo["errorusername"] = $username; 
					if($securl != NULL){
						$logininfo["errorusertype"] = 1;
						//根据weburl查询对应的租户id
						$seltenantid = "select tenantid from tenant where weburl = '".$securl."'";
						$seltenantidqr = $dsql->ExecQuery($seltenantid);
						$tidnum = $dsql->GetTotalRow($seltenantidqr);
						if($tidnum > 0){
							$tidresult = $dsql->GetArray($seltenantidqr, MYSQL_ASSOC);
							$logininfo["errortenantid"] = $tidresult["tenantid"]; 
						}
					}
					else{
						$logininfo["errorusertype"] = -1;
					}
				}
				$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
				$remote_addr = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT'];
				$logger->warn("login faild: URL:{$url} REMOTE_ADDR:{$remote_addr} USERNAME:{$username}");
				//return getErrorOutput($errtype, $errmsg);
			}
		}
		$fields = array();
		$values = array();
		//remoteip;
		$remoteip = getIP();
		if($remoteip){
			$fields[] = "remoteip";
			$values[] = "'".$remoteip."'";
		}
		//OS
		$remoteos = getOS();
		if($remoteos){
			$fields[] = "remoteos";
			$values[] = "'".$remoteos."'";
		}

		//logintime
		$logintime = time();
		$fields[] = "logintime";
		$values[] = $logintime;
		//userbrowser
		$userbrowser = getBrowse();
		if($userbrowser){
			$fields[] = "userbrowser";
			$values[] = "'".$userbrowser."'";
		}
		//userid
		$userid = NULL;
		if(isset($logininfo["userid"])){
			$userid = $logininfo["userid"];
			$fields[] = "userid";
			$values[] = $userid;
		}
		//errorusername
		if(isset($logininfo["errorusername"])){
			$errusername = $logininfo["errorusername"];
			$fields[] = "errorusername";
			$values[] = "'".$errusername."'";
		}
		//errortenantid
		if(isset($logininfo["errortenantid"])){
			$errtenantid = $logininfo["errortenantid"];
			$fields[] = "errortenantid";
			$values[] = "'".$errtenantid."'";
		}
		//errorusertype
		if(isset($logininfo["errorusertype"])){
			$errusertype = $logininfo["errorusertype"];
			$fields[] = "errorusertype";
			$values[] = "'".$errusertype."'";
		}

		//loginresult
		$lr = $logininfo["loginresult"];
		$fields[] = "loginresult";
		$values[] = $lr;
		$fieldstr = implode(",", $fields);
		$valuestr = implode(",", $values);
		$lsql = "INSERT INTO `loginhistory` (".$fieldstr.") VALUES (".$valuestr.")";
		$lqr = $dsql->ExecQuery($lsql);
		if(!$lqr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$lsql} ".$dsql->GetError());
			return getErrorOutput(LOGIN_ERROR_EXCEPTION, "操作异常");
		}
		if($logininfo["loginresult"] == LOGIN_ERROR_SUCCESS){
			return true;
		}
		else{
			return getErrorOutput($errtype, $errmsg);
		}
	}
}

/**
 * 
 * 检查token是否有效，返回对象 {"result":true}
 */
function checktoken(){
	global $arrs, $arrsdata;
	$callback = isset($arrsdata['callback']) ? $arrsdata['callback'] : "";
	$accesstoken = getTokenParam();
	if(empty($accesstoken)){
		$arrs['result'] = false;
	}
	else{
		$detoken = authcode($accesstoken, 'DECODE', TOKENKEY);
		$arrs['result'] = !empty($detoken);
	}
	if(!empty($callback)){
		echo $callback."(".json_encode($arrs).")";
	}
	else{
		echo json_encode($arrs);
	}
	exit;
}

/**
 * 
 * 返回json对象{"token":"xxxx"}
 */
function gettoken(){
	global $arrs,$arrsdata;
	$callback = isset($arrsdata['callback']) ? $arrsdata['callback'] : "";
	$usertype=getLocalType();//获取当前登录的平台类型
	do{
		if(empty($usertype)){
			$arrs["errorcode"] = "domainerror";
			$arrs['error'] = "域名错误";
			break;
		}
		$tcode = getTenantCode();//获取二级域名
		if(empty($tcode)){
			$arrs["errorcode"] = "addrerror";
			$arrs['error'] = "地址错误，请输入二级域名";
			break;
		}
		if(empty($arrsdata['username']) || empty($arrsdata['password']))
		{
			$arrs['errorcode'] = LOGIN_ERROR_PARAM;
			$arrs['error'] = "缺少参数";
			break;
		}
		else{
			$r = checkuser($arrsdata['username'],$arrsdata['password'],$usertype,$tcode);
			if($r !== true){
				$arrs = $r;
			}
			else{
				$token = authcode($arrsdata['username'], 'ENCODE', TOKENKEY, TOKENEXPIRY);
				if(empty($token)){
					$arrs['errorcode'] = LOGIN_ERROR_TOKEN;
					$arrs['error'] = "获取token失败";
				}
				else{
					$arrs['token'] = $token;
				}
			}
		}
		break;
	}while(true);
	if(!empty($callback)){
		echo $callback."(".json_encode($arrs).")";
	}
	else{
		echo json_encode($arrs);
	}
	exit;
}

/*
 * 验证session是否存在，js调用
 */
function existSession(){
	global $arrs;
	$r = Authorization::checkUserSession();
	switch ($r){
		case CHECKSESSION_NULL:
			$arrs["result"]=false;
			$arrs["msg"]="未登录或登陆超时!";
			$_SESSION['user'] = null;
			break;
		case CHECKSESSION_ADDRERROR:
			$arrs["result"]=false;
			$arrs["msg"]="地址错误";
			$_SESSION['user'] = null;
			break;
		case CHECKSESSION_USEREXPIRETIME:
			$arrs["result"]=false;
			$arrs["errortype"] = 4;
			$arrs["msg"]="该账号已经过期";
			$_SESSION['user'] = null;
			break;
		default:
			$arrs["result"]=true;
			$arrs["usertype"] = $_SESSION["user"]->usertype;
			$arrs["msg"]="";
			break;
	}
}
