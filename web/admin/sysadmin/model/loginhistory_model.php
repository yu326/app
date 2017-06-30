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
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_NAME,FALSE);
function getLoginhistoryList($startnum, $pagesize, $login_tenantname=NULL, $login_username=NULL, $login_usertype=NULL, $login_remoteip=NULL, $login_remoteos=NULL, $login_loginresult=NULL, $login_logintimestart=NULL, $login_logintimeend=NULL, $login_userbrowser=NULL){
	global $arrs,$dsql,$logger;
	$arrs["result"]=true;
	//where
	$where = array();
	if($login_tenantname != NULL){
		$pos = strpos($login_tenantname, '*');
		if($pos === false){
			$where[] =  "tenant.tenantname = '".$login_tenantname."'";
		}
		else{
			$login_tenantname = str_replace("*", "%", $login_tenantname);       
			$where[] =  "tenant.tenantname like '".$login_tenantname."'";
		}
	}
	if($login_username!= NULL){
		$pos = strpos($login_username, '*');
		if($pos === false){
			$where[] =  "(users.username = '".$login_username."' or lh.errorusername = '".$login_username."')";
		}
		else{
			$login_username = str_replace("*", "%", $login_username);       
			$where[] =  "(users.username like '".$login_username."' or lh.errorusername like '".$login_username."')";
		}
	}
	if($login_usertype != NULL){
		$where[] = "(users.usertype = '".$login_usertype."' or lh.errorusertype = '".$login_usertype."')";
	}
	if($login_remoteip!= NULL){
		$pos = strpos($login_remoteip, '*');
		if($pos === false){
			$where[] =  "lh.remoteip = '".$login_remoteip."'";
		}
		else{
			$login_remoteip = str_replace("*", "%", $login_remoteip);       
			$where[] =  "lh.remoteip like '".$login_remoteip."'";
		}
	}
	if($login_remoteos!= NULL){
		$pos = strpos($login_remoteos, '*');
		if($pos === false){
			$where[] =  "lh.remoteos = '".$login_remoteos."'";
		}
		else{
			$login_remoteos = str_replace("*", "%", $login_remoteos);       
			$where[] =  "lh.remoteos like '".$login_remoteos."'";
		}
	}
	if($login_loginresult != NULL){
		$where[] = "lh.loginresult = '".$login_loginresult."'";
	}
	//logintime
	if($login_logintimestart != NULL && $login_logintimeend != NULL){
		$where[] = "lh.logintime > ".$login_logintimestart." AND lh.logintime < ".$login_logintimeend."";
	}
	else if($login_logintimestart != NULL){
		$where[] = "lh.logintime > ".$login_logintimestart."";
	}
	else if($login_logintimeend != NULL){
		$where[] = "lh.logintime < ".$login_logintimeend."";
	}
	//userbrowser
	if($login_userbrowser!= NULL){
		$pos = strpos($login_userbrowser, '*');
		if($pos === false){
			$where[] =  "lh.userbrowser = '".$login_userbrowser."'";
		}
		else{
			$login_userbrowser = str_replace("*", "%", $login_userbrowser);       
			$where[] =  "lh.userbrowser like '".$login_userbrowser."'";
		}
	}

	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$totalCount = "select count(*) as totalcount from ".DATABASE_LOGINHISTORY." as lh 
		left join users on lh.userid = users.userid
		left join tenant on (users.tenantid = tenant.tenantid or lh.errortenantid = tenant.tenantid)
		".$wherestr."";
	$qr = $dsql->ExecQuery($totalCount);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$arrs["totalcount"]=$result["totalcount"];
		}
		$limit = "";
		$startnum = empty($startnum) ? 0 : $startnum;
		if(!empty($pagesize)){
			$limit = " limit ".$startnum.",".$pagesize."";
		}
		$sql = "select lh.*, users.username, users.usertype, users.tenantid, tenant.tenantname from ".DATABASE_LOGINHISTORY." as lh 
			left join ".DATABASE_USERS." on lh.userid = users.userid
			left join ".DATABASE_TENANT." on (users.tenantid = tenant.tenantid or lh.errortenantid = tenant.tenantid) ".$wherestr." order by logintime desc ".$limit."";
		//$logger->debug(__FILE__." func:".__FUNCTION__." sql:{$sql} ");
		$qr2 = $dsql->ExecQuery($sql);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$temp_arr = array();
			$arrs["datalist"] = array();
			while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$temp_arr["id"] = $result["id"];
				$temp_arr["tenantname"] = $result["tenantname"]; //租户名称
				$temp_arr["usertype"] = $result["usertype"]; //用户类型
				$temp_arr["remoteip"] = $result["remoteip"]; //远程IP
				$temp_arr["remoteos"] = $result["remoteos"]; //远程操作系统
				$temp_arr["username"] = $result["username"]; //用户名
				$temp_arr["loginresult"] = $result["loginresult"]; //登录结果
				if($result["loginresult"] == LOGIN_ERROR_NOUSER){
					$temp_arr["username"] = $result["errorusername"]; //用户名
					$temp_arr["usertype"] = $result["errorusertype"]; //帐号类型
				}
				$temp_arr["logintime"] = $result["logintime"]; //登录时间
				$temp_arr["userbrowser"] = $result["userbrowser"]; //浏览器类型
				$arrs["datalist"][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
	return $arrs;
}
$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectlogininfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = $_GET['pagesize'];
		$startnum = $page * $pagesize;
		$login_tenantname = isset($_GET['login_tenantname']) ? $_GET['login_tenantname'] : NULL;
		$login_username = isset($_GET['login_username']) ? $_GET['login_username'] : NULL;
		$login_usertype = isset($_GET['login_usertype']) ? $_GET['login_usertype'] : NULL;
		$login_remoteip = isset($_GET['login_remoteip']) ? $_GET['login_remoteip'] : NULL;
		$login_remoteos = isset($_GET['login_remoteos']) ? $_GET['login_remoteos'] : NULL;
		$login_loginresult = isset($_GET['login_loginresult']) ? $_GET['login_loginresult'] : NULL;
		$login_logintimestart = isset($_GET['login_logintimestart']) ? $_GET['login_logintimestart'] : NULL;
		$login_logintimeend = isset($_GET['login_logintimeend']) ? $_GET['login_logintimeend'] : NULL;
		$login_userbrowser = isset($_GET['login_userbrowser']) ? $_GET['login_userbrowser'] : NULL;
		$r = getLoginhistoryList($startnum, $pagesize, $login_tenantname, $login_username, $login_usertype, $login_remoteip, $login_remoteos, $login_loginresult, $login_logintimestart, $login_logintimeend, $login_userbrowser);
		echo json_encode($r);
	}
}
