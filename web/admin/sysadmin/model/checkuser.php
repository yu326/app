<?php
define('TYPE_LOGIN', 'login');
define('TYPE_SYSLOGIN', 'syslogin');//系统管理后台登录
define('TYPE_SYSTITLE', 'systitle');//系统管理后台登录
define('TYPE_LOGOUT', 'logout');
define('TYPE_EXISTSESSION', 'existsession');//验证session是否存在 js调用
define('TYPE_CHECKWEBURL','checkweburl');//验证二级域名是否正确
define('TYPE_GETTOKEN','gettoken');//获取token

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
    case TYPE_SYSTITLE:
        systitle();
        break;
    case TYPE_CHECKWEBURL:
        checkweburl();
        break;
    case TYPE_GETTOKEN:
    	gettoken();
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
        $sql = "select tenantid from tenant where weburl = '{$tcode}' and localtype={$usertype}";
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $arrs["result"]=false;
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
        }
        else{
            $num = $dsql->GetTotalRow($qr);
            $logger->error($sql."    ".$num);
            $arrs["result"]= $num > 0;
            $arrs['localtype'] = $usertype;
        }
    }
}

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
    if($arrsdata["mark"]==$_SESSION["VCODE"])
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
}


function gettoken(){
    global $arrs,$arrsdata;
    $callback = isset($arrsdata['callback']) ? $arrsdata['callback'] : "";
    if(empty($arrsdata['username']) || empty($arrsdata['password']))
    {
    	$arrs['errorcode'] = LOGIN_ERROR_PARAM;
    	$arrs['error'] = "缺少参数";
    	echo $callback."(".json_encode($arrs).")";
    	exit;
    }
    else{
        $r = checkuser($arrsdata['username'],$arrsdata['password'],NULL,NULL);
        if($r !== true){
	    	echo $callback."(".json_encode($r).")";
	    	exit;
        }
        else{
        	$token = authcode($arrsdata['username'], 'ENCODE', TOKENKEY, TOKENEXPIRY);
        	if(empty($token)){
		    	$arrs['errorcode'] = LOGIN_ERROR_TOKEN;
		    	$arrs['error'] = "获取token失败";
		    	echo $callback."(".json_encode($arrs).")";
		    	exit;
        	}
        	else{
        		$arrs['token'] = $token;
        		echo $callback."(".json_encode($arrs).")";
        		exit;
        	}
        }
    }
}
function systitle(){
	global $arrs, $arrsdata;
	$arrs["result"] = true;
	if(defined("SYSTEM_TITLE")){
		$arrs["systitle"] = SYSTEM_TITLE;
	}else{
		$arrs["systitle"] = "博晓通";
    }
}
/**
 * 
 * 系统后台登录
 */
function syslogin(){
    global $arrs,$arrsdata,$logger;
    if(isset($_SESSION["VCODE"]) && $arrsdata["mark"] == $_SESSION["VCODE"])
    {
        $r = checkuser($arrsdata['username'],$arrsdata['password'],NULL,NULL);
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
    global $dsql,$logger;
    $checksql;
    if(empty($localtype)){
        $checksql = "select * from users where username = '{$username}' and password='{$password}' and tenantid=-1";
    }
    else{
        $checksql = "select * from users a inner join tenant b on a.tenantid=b.tenantid
          where a.username = '{$username}' and a.password='{$password}'
          and b.weburl='{$securl}' and b.localtype={$localtype}";
    }
    $qr = $dsql->ExecQuery($checksql);
    if(!$qr){
        //$arrs["result"]=false;
        //$arrs["msg"]="登录失败，操作异常！";
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$checksql} ".$dsql->GetError());
        return array("errorcode"=>LOGIN_ERROR_EXCEPTION, "error"=>"操作异常");
    }
    else{
        $num = $dsql->GetTotalRow($qr);
		$logininfo = array();
        if($num>0)
        {
            $result = $dsql->GetArray($qr, MYSQL_ASSOC);
            if(!empty($result)){
                $userid=$result["userid"];
                $tenantid=$result["tenantid"];
				$logininfo["userid"] = $result["userid"];
				//失效时间
				$expiretime = $result["expiretime"];
				$now = time();
				if(!empty($expiretime) && $now > $expiretime){
					//判断失效时间
						$logger->warn("login faild: expire time user");
						$errtype = LOGIN_ERROR_EXPIRE;
						$errmsg = "该账号已经过期";
						$logininfo["loginresult"] = LOGIN_ERROR_EXPIRE;
						//return array("errorcode"=>LOGIN_ERROR_EXPIRE, "error"=>"该账号已经过期");
				}
				else{
					$user = new UserInfo($userid,$tenantid,$localtype,$securl);
					$user->userexpiretime = $expiretime; //session中存过期时间
					Authorization::setUserRole($user);
					$_SESSION["user"] = $user;
					$logininfo["loginresult"] = LOGIN_ERROR_SUCCESS;
				}
            }
            //return true;
        }
        else
        {
			//判断是用户名错误还是密码错误
			if(empty($localtype)){
				$checkusersql = "select * from users where username = '{$username}' and tenantid=-1";
			}
			else{
				$checkusersql = "select a.*, b.localtype,b.weburl, b.allowlinkage, b.allowdrilldown,b.allowdownload, 
					b.allowwidget, b.allowaccessdata, b.accessdatalimit from users a inner join tenant b on a.tenantid=b.tenantid
					where a.username = '{$username}' and b.weburl='{$securl}' and b.localtype={$localtype}";
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

			/*
            $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $remote_addr = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT'];
            $logger->warn("login faild: URL:{$url} REMOTE_ADDR:{$remote_addr} USERNAME:{$username}");
            return array("errorcode"=>LOGIN_ERROR_NOUSER, "error"=>"用户名或密码错误");
			 */
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
			$arrs["msg"]="该账号已经过期";
			$_SESSION['user'] = null;
			break;
        default:
            $arrs["result"]=true;
            $arrs["msg"]="";
            break;
    }
}
