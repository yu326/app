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
define('TYPE_GETTENANTROLELIST', 'gettenantrolelist');//获取租户角色列表
define('TYPE_GETROLEBYTENANT', 'getrolebytenant');//获取租户角色列表
define('TYPE_SETTENANTROLE', 'settenantrole');
define('TYPE_GETGENERALOPTIONS', 'getgeneraloptions');
define('TYPE_SETGENERALOPTIONS', 'setgeneraloptions');

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;

//存放返回结果
$arrs;
$arrsdata;
$arg_type;//存储操作类型
$arg_id;//存储租户id
$arg_name;//存储租户名称
$arg_web;//存储租户二级域名
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

function gettenantrolelist(){
	global  $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	//计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	if(!Authorization::checkUserUseage(1,1,$childid=null)){
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else{
		//where
		$where = array();
		//用户名
		$arg_tenantname = isset($_GET['tenantname']) ? $_GET['tenantname'] : NULL;
		if($arg_tenantname!= NULL){
			$pos = strpos($arg_tenantname, '*');
			if($pos === false){
				$where[] =  "b.tenantname = '".$arg_tenantname."'";
			}
			else{
				$arg_tenantname = str_replace("*", "%", $arg_tenantname);       
				$where[] =  "b.tenantname like '".$arg_tenantname."'";
			}
		}
		//角色名称
		$arg_roleid = isset($_GET['roleid']) ? $_GET['roleid'] : NULL;
		if($arg_roleid){
			$where[] = "a.roleid = ".$arg_roleid."";
		}

		$wherestr = "";
		if(count($where) > 0){
			$wherestr = " where ".implode(" and ", $where);
		}

		$arrs["result"]=true;
		$totalCount = "select count(*) as totalcount from ".DATABASE_TENANT_ROLE_MAPPING." as a inner join ".DATABASE_TENANT." as b inner join ".DATABASE_ROLE." as c on a.tenantid = b.tenantid and a.roleid = c.roleid ".$wherestr."";
		$sql="select a.*,b.tenantname,c.label from ".DATABASE_TENANT_ROLE_MAPPING." as a inner join ".DATABASE_TENANT." as b inner join ".DATABASE_ROLE." as c on a.tenantid = b.tenantid and a.roleid = c.roleid ".$wherestr." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
		$qr2 = $dsql->ExecQuery($totalCount);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			while($result = $dsql->GetArray($qr2, MYSQL_ASSOC)){
				$arrs["totalcount"] = $result["totalcount"];
			}
			$q = $dsql->ExecQuery($sql);
			if(!$q){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else{
				$temp_arr = array();
				while($r = $dsql->GetArray($q, MYSQL_ASSOC)){
					$temp_arr["id"] = $r["id"];
					$temp_arr["tenantname"] = $r["tenantname"];
					$temp_arr["tenantid"] = $r["tenantid"];
					$temp_arr["roleid"] = $r["roleid"];
					$temp_arr["label"] = $r["label"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["flag"]=1;
			}
		}
	}
}
//获取租户和角色对应的通用选项
function getgeneraloptions(){
	global $logger, $dsql, $arrsdata, $arrs;
	$sql = "select * from ".DATABASE_TENANT_ROLE_MAPPING." where tenantid = ".$arrsdata->tenantid." and roleid = ".$arrsdata->roleid."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"] = 0;
	}
	else{
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		$arrs['children'][] = $result;
		$arrs['flag'] = 1;
	}
}
//更新租户和角色的通用选项
function setgeneraloptions(){
	global $logger, $dsql, $arrsdata, $arrs;
	$accessdatalimit = 0;
	if($arrsdata->allowaccessdata){//只有$arrsdata->allowaccessdata 为1时，赋值
		$accessdatalimit = $arrsdata->accessdatalimit;
		//检查角色数据量限制是否超出租户限制, 当绕过js检查 发送数据时
		$tenantoptsql="select * from ".DATABASE_TENANT." where tenantid=".$arrsdata->tenantid;
		$optqr = $dsql->ExecQuery($tenantoptsql);
		if(!$optqr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$tenantoptsql} ".$dsql->GetError());
			$arrs["flag"]=0;
			$json_str = json_encode($arrs);
			echo $json_str;
			exit;
		}
		else{
			$result = $dsql->GetArray($optqr, MYSQL_ASSOC);
			$tenantlimit = $result["accessdatalimit"];
			if($accessdatalimit > $tenantlimit){
				$logger->error(__FILE__." func:".__FUNCTION__." 接口数据量限制超出租户数据限制 ");
				$arrs["flag"]=0;
				$json_str = json_encode($arrs);
				echo $json_str;
				exit;
			}
		}
	}
	$upsql = "update ".DATABASE_TENANT_ROLE_MAPPING." set allowdrilldown = ".$arrsdata->allowdrilldown.", allowlinkage = ".$arrsdata->allowlinkage.", allowoverlay = ".$arrsdata->allowoverlay.", allowdownload = ".$arrsdata->allowdownload.", allowupdatesnapshot = ".$arrsdata->allowupdatesnapshot.", alloweventalert = ".$arrsdata->alloweventalert.", allowwidget = ".$arrsdata->allowwidget.", allowaccessdata = ".$arrsdata->allowaccessdata.", accessdatalimit= ".$accessdatalimit.", allowvirtualdata = ".$arrsdata->allowvirtualdata.", updatetime = ".time()." where tenantid = ".$arrsdata->tenantid." and roleid = ".$arrsdata->roleid."";
	$qr = $dsql->ExecQuery($upsql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
		$arrs["flag"]=0;
		$json_str = json_encode($arrs);
		echo $json_str;
		exit;
	}
	else{
		$arrs["flag"]=1;
	}
}
//获取某一租户的角色信息
function getrolebytenant($tid)
{
	global $dsql,$arrs,$logger;
	$sql = "select a.*,b.label from ".DATABASE_TENANT_ROLE_MAPPING." as a inner join ".DATABASE_ROLE." as b on a.roleid =b.roleid where a.tenantid=".$tid." group by roleid";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else{
		$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$tmp_arrs["roleid"] = $result["roleid"];
			$tmp_arrs["label"] = $result["label"];
			$arrs['children'][]=$tmp_arrs;
		}
		if(!empty($tmp_arrs)){
			$arrs["flag"]=1;
		}
	}
}
function settenantrole(){
    global $dsql,$arrs,$arrsdata;
    if(!Authorization::checkUserUseage(8,1,null)){
        $arrs["result"]=false;
        $arrs["msg"]="您没有权限使用此模,请与管理员联系!";
        echo json_encode($arrs);
        exit;
    }
    else{
        $arrs["result"]=true;
        $sqldel;
        $sql;
        $roledata;
		if(isset($arrsdata->delrole)){
			$deleterole = $arrsdata->delrole;   //获取要要删除的角色
		}

        //修改删除角色
        if($deleterole!=""&&$deleterole!=null)
        {
            foreach($deleterole as $key => $value)
            {
                $sqldel="delete from ".DATABASE_ACCOUNTING_RULE." where roleid =".$value." and tenantid=".$arrsdata->tenantid;
                $sqldel2="delete from ".DATABASE_TENANT_ROLE_MAPPING." where roleid =".$value." and tenantid=".$arrsdata->tenantid;
                $q = $dsql->ExecQuery($sqldel);
                $q2 = $dsql->ExecQuery($sqldel2);
                if(!$q || !$q2){
					if(!$q){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqldel} ".$dsql->GetError());
					}
					if(!$q2){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqldel2} ".$dsql->GetError());
					}
                    $arrs["flag"]=0;
                    $json_str = json_encode($arrs);
                    echo $json_str;
                    exit;
                }
            }
        }
        if($arrsdata->roleid!=""){
            $roledata = explode(",",$arrsdata->roleid);
        }
        //判断是否分配了角色
        if(count($roledata)>0){
			foreach($roledata as $rolekey => $rolevalue){
				//先判断对应租户下 对应角色是否已添加
				$sqlexist = "select count(*) as cn from ".DATABASE_TENANT_ROLE_MAPPING." where roleid = ".$rolevalue." and tenantid =".$arrsdata->tenantid;
				$qrexist = $dsql->ExecQuery($sqlexist);
				if(!$qrexist){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlexist} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}
				else{
					$resexist = $dsql->GetArray($qrexist, MYSQL_ASSOC);
					if($resexist["cn"] == 0){ //不存在时添加
						$content = "";
						$sql="insert into ".DATABASE_TENANT_ROLE_MAPPING." (tenantid,roleid,updatetime) values(".$arrsdata->tenantid.",".$rolevalue.",".time().")";
						$q = $dsql->ExecQuery($sql);
						if(!$q){
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
							$arrs["flag"]=0;
							$json_str = json_encode($arrs);
							echo $json_str;
							exit;
						}
					}
				}
			}
            $arrs["flag"]=1;
        }
        else
        {
            $sql="delete from ".DATABASE_ACCOUNTING_RULE." where tenantid = ".$arrsdata->tenantid;
            $sql2="delete from ".DATABASE_TENANT_ROLE_MAPPING." where tenantid = ".$arrsdata->tenantid;
            $q = $dsql->ExecQuery($sql);
            $q2 = $dsql->ExecQuery($sql2);
            if(!$q || !$q2){
				$s = $sql;
				if(!$q2){
					$s = $sql2;
				}
                $logger->error(__FILE__." func:".__FUNCTION__." sql:{$s} ".$dsql->GetError());
                $arrs["flag"]=0;
            }
            else {
                $arrs["flag"]=1;
            }
        }
    }//结束权限判断
}
	//判断是否GET访问
	if(empty($_GET)){
		if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest"){
			global $arrsdata;
			$arrsdata = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
			$arg_type = isset($arrsdata->type) ? $arrsdata->type : '';
			$arg_rid = isset($arrsdata->modelid) ? $arrsdata->modelid : '';
			if($arg_rid == ''){
				$arg_rid = isset($arrsdata->resourceid) ? $arrsdata->resourceid: '';
			}
			$arg_id = isset($arrsdata->tenantid) ? $arrsdata->tenantid : '';
		}
	}
	else{
		$arg_type = isset($_GET['type']) ? $_GET['type'] : null;
		$arg_id = isset($_GET['tid']) ? $_GET['tid'] : 0;
		$arg_name = isset($_GET['tenantname']) ? $_GET['tenantname'] :null;
		$arg_web = isset($_GET['weburl']) ? $_GET['weburl'] :null;
	}

	switch($arg_type){
		case TYPE_GETTENANTROLELIST:
			gettenantrolelist();
			break;
		case TYPE_GETGENERALOPTIONS:
			getgeneraloptions();
			break;
		case TYPE_SETGENERALOPTIONS:
			setgeneraloptions();
			break;
		case TYPE_GETROLEBYTENANT:
			getrolebytenant($arg_id);
			break;
		case TYPE_SETTENANTROLE:
			settenantrole();
			break;
		default:
			set_error_msg("arg type has a error");
	}
	if(!$arrs){
		echo "";
	}
	else{
		$json_str = json_encode($arrs);
		echo $json_str;
	}
