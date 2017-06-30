<?php
include_once('includes.php');
include_once("datatableresult.php");
include_once("model_config.php");
include_once('userinfo.class.php');
include_once("authorization.class.php");
include_once("commonFun.php");
initLogger(LOGNAME_WEBAPI);
session_start();
//判断session是否存在
if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	$user = Authorization::checkUserSession();
	if($user == CHECKSESSION_NULL){
		$arrs["result"]=false;
		$arrs["msg"]="未登录或登陆超时!";
		$arrs['errorcode'] = WEBERROR_NOSESSION;
		$arrs['error'] = $arrs["msg"];
		echo json_encode($arrs);
		exit;
	}
	else if(empty($user)){
		$arrs["result"]=false;
		$arrs["msg"]="没有权限访问!";
		echo json_encode($arrs);
		exit;
	}
}
define('TYPE_PAGE','navigate');
define('CHILDS', "children");

//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');
//定义数据导航链接请求参数 bert
define('ARG_TYPE_GETMODELBYCHANNEL',1);//HTTP请求,根据频道ID获取模型,对应模型列表
define('ARG_TYPE_GETNAVBYCHANNEL',2);//HTTP请求,根据频道ID获取导航, 对应左侧导航
define('ARG_TYPE_GETMODELJS',3);//HTTP请求,获取js模型
define('TYPE_MODELNAVTYPE', 'modelnavtype');

//获取数据类型 (type)的具体内容
define('TYPE_GETRENDERS', 'getrenders');//根据navid获取所有render类型的元素，返回元素数组
define('TYPE_GETLEFTMENU', 'getleftmenu');//获取租户平台左侧导航
define('TYPE_GETPLATEFORM', 'getplatformmenu');//获取数据平台左侧导航
define('TYPE_GETMODEL', 'getmodel');//数据平台是调用此函数获取模型实例读取模型规则表
define('TYPE_GETALLMODELS', 'getallmodels');//数据平台是调用此函数获取所有模型
define('TYPE_GETTELATRMODEL', 'getrelatemodel');//获取联动模型数据
define('TYPE_GETMODELBYCHANNEL', 'getmodelbychannel');//根据channelid获取模型
define('TYPE_GETMODELBYTENANTID', 'getmodelbytenantid');//根据租户id获取模型

define('TYPE_GETELEMENTS', 'getelements');//根据channelid获取模型
define('TYPE_ADDNAVIGATE', 'addnavigate');    //添加导航信息
define('TYPE_ADDNAVTAB', 'addnavtab');    //添加导航信息
define('TYPE_CHECKHOMEPAGE', 'checkhomepage');    //判断是否已经存在首页
define('TYPE_CHECKDEFAULTTAB', 'checkdefaulttab');    //判断是否为默认选择的卡片
define('TYPE_DELELEMENT', 'delelement');    //根据实例ID，元素ID删除一条元素记录
define('TYPE_GETINSTANCE', 'getinstance');    //根据导航ID 获取实例
define('TYPE_UPDATEELEMENT', 'updateelement');    //修改元素信息
define('TYPE_UPDATEINSTANCE', 'updateinstance');  //修改实例信息
define('TYPE_UPDATEPINRELATE', 'updatepinrelate');  //修改pin信息
define('TYPE_DELETEPINRELATE', 'deletepinrelate');  //删除pin信息
define('TYPE_CHECKMODELRULE', 'checkmodelrule');  //获取模型信息
define('TYPE_ADDELEMENT', 'addelement');  //获取模型信息
define('TYPE_GETMODELSHOW', 'getmodelshow');  //获取模型所有显示部分
define('TYPE_GETTENANTMODELRULE','gettenantmodelrule');
define('TYPE_SAVEMULTIMODEL', 'savemultimodel');//保存多模型页面
define('TYPE_ADDINSTANCE','addinstance');
define('TYPE_GETNAVBYTIDALL', 'getnavbytidall'); //获取租户的导航信息
define('TYPE_GETNAVBYTID', 'getnavbytid');//获取某条导航的详细信息
define('TYPE_GETNAVBYID', 'getnavbyid');//获取某条导航的详细信息
define('TYPE_GETTABBYPARENTID', 'gettabbyparentid');//获取某条导航下的选项卡
define('TYPE_GETSNAPSHOTHISTORY', 'getsnapshothistory');//获取快照历史
define('TYPE_DELETESNAPSHOTHISTORY', 'deletesnapshothistory');//删除历史快照
define('TYPE_DELETEEVENTHISTORYHISTORY', 'deleteeventhistoryhistory');//删除历史快照
define('TYPE_GETSNAPSHOTHISTORYBYTIME', 'getsnapshothistorybytime');
define('TYPE_GETSNAPSHOTHISTORYBYINCID', 'getsnapshothistorybyincid'); //根据instanceid查询快照
define('TYPE_GETSNAPSHOTHISTORYLIST', 'getsnapshothistorylist'); //根据instanceid查询快照
define('TYPE_GETSNAPSHOTHISTORYLISTALL', 'getsnapshothistorylistall'); //根据instanceid查询快照
define('TYPE_UPDATESCHEDULE', 'updateschedule');
define('TYPE_GETLATESTSNAPSHOT', 'getlatestsnapshot');
define('TYPE_SETSNAPSHOTSHOWINFO', 'setsnapshotshowinfo');
define('TYPE_GETSNAPSHOTSHOWINFO', 'getsnapshotshowinfo');
define('TYPE_GETEVENTLIST', 'geteventlist');
define('TYPE_GETEVENTLISTALL', 'geteventlistall');
define('TYPE_GETEVENTITEM', 'geteventitem');
define('TYPE_GETEVENTITEMALL', 'geteventitemall');
define('TYPE_GETEVENTHISTORYLIST', 'geteventhistorylist');
define('TYPE_GETEVENTHISTORYLISTALL', 'geteventhistorylistall');
define('TYPE_DELETEEVENTALERT', 'deleteeventalert');
define('TYPE_CHECKEXIST', 'checkexist'); //检查导航名称是否存在
define('TYPE_CHECKTABEXIST', 'checktabexist'); //检查导航下选项卡名称是否存在
define('TYPE_DELETENAVIGATE', 'deletenavigate');//删除导航信息
define('TYPE_DELETENAVTAB', 'deletenavtab');//删除导航信息
define('TYPE_UPDATENAVIGATE', 'updatenavigate');//修改导航信息
define('TYPE_UPDATENAVTAB', 'updatenavtab');//修改导航卡片信息
define('TYPE_UPDATENAVORDER', 'updatenavorder');//修改导航排序
define('TYPE_ADDPINRELATE','addpinrelate');//添加pin
define('TYPE_DELINSTANCE','delinstance');//删除实例
define('TYPE_SAVEPAGE','savepage');//保存整个页面html
define('TYPE_SAVEINSTANCE','saveinstance');//保存整个页面实例
define('TYPE_UPDATEMERGEDATA', 'updatemergedata');
define('TYPE_GETMERGEDATA', 'getmergedata');
define('TYPE_GETMERGESCHED', 'getmergesched');
define('TYPE_ISCONTAINCHILD', 'iscontainchild'); //是否含有子级导航
define('TYPE_GETWEIBOIDBYMID','geweibotidbymid');//根据微博的MID获取 微博ID
define('TYPE_GETNAVHTML', 'getnavhtml');//获取导航的html，widget调用

$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
//存放返回结果
$arrs = array();
$arrsdata;
$arg_type;
$arg_id;
$arg_name;
$arg_tid;
$arg_channelid;
$arg_typeid;
$arg_modelid;
$arg_modeltype;
$arg_navid;
$arg_instanceid; //实例ID
$arg_elementid; //元素ID
$arg_navid;//导航ID

/**
 *
 * 根据导航ID获取类型为render的元素
 * @param  $navid  导航ID
 * @throws Exception
 */
function getrenders($navid)
{
    global $dsql,$arrs;

    $pageid=0;

    $sql  = "select c.instancetype,d.* from (select a.*,b.id,b.instancetype from ".DATABASE_CUSTOMER_NAVIGATE." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.navid=b.navid) as c inner join ".DATABASE_ELEMENT." as d inner join c.id = d.instanceid and d.type='render'";
    if(!$qr){
        throw new Exception(TYPE_PAGE."- getrenders()-".$sql."-".mysql_error());
    }
    else{
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
			$temp_arr = array();
            $temp_arr["instanceid"] = $result["instanceid"];
            $temp_arr["elementid"] = $result["elementid"];
            $temp_arr["datajson"] = json_decode($result["content"]);
            $temp_arr["type"] = $result["type"];
            $temp_arr["instancetype"] = $result["instancetype"];
            //$temp_arr["updatetime"] = $result["updatetime"];

            $arrs[] = $temp_arr;
        }
    }
}


//将列表数据转换为json输
/**
 *
 * Enter description here ...
 * @param  $id
 * @param  $channelid   频道ID   分两种  一种带分组
 * @param  $typeid
 * @param  $arg_modelnavtype
 */
function getleftmenu()
{
    global $dsql,$arrs,$arrmodel,$logger;
    $num=0;
    $num2=0;
    //判断是否是租户用户
    if(isset($_SESSION["user"]->tenantid) && $_SESSION["user"]->tenantid!=null && $_SESSION["user"]->tenantid!=""){
        $sql="select *  from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$_SESSION["user"]->tenantid." and parentid=0 and userid=".$_SESSION["user"]->getuserid();
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs['result'] = 0;
        }
        else{
            $num = $dsql->GetTotalRow($qr);
            $temp_arr = array();
            $temp_arr2 = array();
            if($num>0)
            {
                while ($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
                    $sqlchild = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$_SESSION["user"]->tenantid." and  parentid = ".$result["id"]." and userid =".$_SESSION["user"]->getuserid();
                    unset($temp_arr2);
                    $temp_arr2["id"] = $result["id"];
                    $temp_arr2["modelname"] = $result["label"];
                    $temp_arr2["modelid"] = $result["modelid"];
                    if($result["ishomepage"]==1)
                    {
                        $temp_arr2["isdefault"] = true;
                    }
                    else
                    {
                        $temp_arr2["isdefault"] = false;
                    }
                    $temp_arr2["isnav"] = 0;

                    $arrinstance = getinstanceid($result["id"]);
                    //创建一级菜单
                    $nav = new Nav($result["id"],$result["level"],$result["label"], $result["pagetype"],$temp_arr2["isdefault"],$arrinstance,$result["modelid"],$result["filepath"]);
					$nav->pagetitle = $result["pagetitle"];
					$nav->icon = $result["icon"];
                    $qr2 = $dsql->ExecQuery($sqlchild);
                    if (!$qr2)
                    {
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlchild} ".$dsql->GetError());
						$arrs['result'] = 0;
                    }
                    else
                    {
                        $num2 = $dsql->GetTotalRow($qr2);
                        $temp_arr = array();
                        if($num2>0)
                        {
                            while ($r = $dsql->GetArray($qr2, MYSQL_ASSOC))
                            {
                                unset($tmp_arr);
                                $tmp_arr['id'] = $r["id"];
                                $tmp_arr['modelname'] = $r["label"];
                                $tmp_arr['modelid'] = $r["modelid"];
                                if($r["ishomepage"]==1){
                                    $tmp_arr["isdefault"] = true;
                                }
                                else{
                                    $tmp_arr["isdefault"] = false;
                                }
                                //instanceid是数组形式
                                $arrinstance2 = getinstanceid($r["id"]);
                                //创建二级菜单
								$navlevel2 = new Nav($r["id"],$r["level"],$r["label"], $r["pagetype"],$tmp_arr["isdefault"],$arrinstance2,$r["modelid"],$r["filepath"]);
								$navlevel2->pagetitle = $r["pagetitle"];
                                $nav->addChild($navlevel2);
                            }
                        }
                    }
                    $arrs[]=$nav;
                }
            }
        }
    }
    else
    {
        $arrs["result"]=false;
        $arrs["msg"]="未登录或登陆超时!";
        $arrs['errorcode'] = WEBERROR_NOSESSION;
    	$arrs['error'] = $arrs["msg"];
    }
}

/**
 *
 * 获取数据平台的左侧菜单
 * @param unknown_type $channelid
 */
function getplatformmenu()
{
    $channelid = isset($_REQUEST["channelid"]) ? $_REQUEST["channelid"] : 0;
    $result=getDataPlatformNav($channelid);
    $user = $_SESSION['user'];

    foreach($result as $k=>$v){
        foreach($v->childNav as $key=>$value){
            $is_in = false;
            if(in_array($value->modelid,$user->tenantResource)){
    	         $is_in = true;
    	     }
    	     if(!$is_in){
                 unset($result[$k]->childNav[$key]);    	         
    	     }
        }
    }
    echo json_encode($result);
}


/***
 * 参数：$modelid 资源ID
 *       $navid   导航ID
 *       $modeltype 导航类型 1:单模型 2：多模型
 *       $instanceid 实例ID 当多模型时会根据实例ID选查询多条资源
 * 说明：当$navid=0时只查询$modelid相等的资源
 */
function getinstancebymodelid($modelid,$navid,$modeltype,$instanceid)
{

    global $dsql,$arrs;

    $sql;
    //首先通过session判断是否是租户
    if($_SESSION["user"]->tenantid!=0&&$_SESSION["user"]->tenantid!=null&&$_SESSION["user"]->tenantid!="")
    {
        if($modeltype==1)
        {
            $sql = "select *  from ".DATABASE_TENANT_TAGINSTANCT." where navid=".$navid." and tenantid=".$_SESSION["user"]->tenantid;
        }
        else
        {
            $sql ="select *  from ".DATABASE_TENANT_TAGINSTANCT." where modelid=".$modelid." and id in(".$instanceid.") and tenantid=".$_SESSION["user"]->tenantid;
        }

        $qr = $dsql->ExecQuery($sql);
        $num = $dsql->GetTotalRow($qr);
        if (!$qr)
        {
            set_error_msg("sql error:".mysql_error());
        }
        else
        {

            if($num>0)
            {
                $tmp_arr = array();
                if($num==1)
                {
                    $result = $dsql->GetArray($qr, MYSQL_ASSOC);
                    $arrs2="[".$result["content"]."]";
                }
                else
                {
                    $arrdata = "[";
                    while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
                    {
                        $arrdata.=$result["content"].",";
                    }
                    $arrdata=rtrim($arrdata,",");
                    //$arrdata = $arrdata."]";
                    $arrs2 = $arrdata;
                }
                	
            }
            echo $arrs2;
        }
    }
    else
    {
        $sql = "select * from ".DATABASE_BILLRULEMODEL." where resourceid=".$modelid." and resourcetype=".$_SESSION["user"]->usertype;

        $qr = $dsql->ExecQuery($sql);
        $num = $dsql->GetTotalRow($qr);
        if (!$qr)
        {
            set_error_msg("sql error:".mysql_error());
        }
        else
        {

            if($num>0)
            {
                $tmp_arr = array();
                if($num==1)
                {
                    $result = $dsql->GetArray($qr, MYSQL_ASSOC);
                    $arrs2="[".$result["content"]."]";
                }
                else
                {
                    $arrdata = "[";
                    while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
                    {
                        $arrdata.=$result["content"].",";
                    }
                    $arrdata=rtrim($arrdata,",");
                    $arrdata = $arrdata."]";
                }
                	
            }
            echo $arrs2;
        }
    }

}

/**
 * 设置租户的模型规则
 * 返回值:0:失败,1:成功
 */
function settenantrule()
{
    global $dsql,$arrs,$arrdata;

    $sql = "update ".accounting_rule." set ruldata='".json_encode($arrdata["content"])."'";

    $qr = $dsql->ExecQuery($sql);
    if(!$qr)
    {
        $arrs["flag"]=0;
    }
    else
    {
        $arrs["flag"]=1;
    }

}

/**
 *
 * 获取某个导航模块的模型实例
 * $id 导航ID
 * 返回值:实例数组
 */
function getinstanceid($id)
{
    global $dsql,$arrs;
    $sql  = "select * from ".DATABASE_TENANT_TAGINSTANCT." where navid = ".$id;
    $qr = $dsql->ExecQuery($sql);
    $tmp_arr = array();
    if(!$qr)
    {

    }
    else
    {
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
            $tmp_arr["instanceid"]=$result["id"];
        }
    }

    return $tmp_arr;
}

/**
 *
 * 根据导航Id获取所有实例
 * @param unknown_type $navid
 */
function getinstance()
{
    global $dsql,$arrs;
    $navid = isset($_REQUEST["navid"]) ? $_REQUEST["navid"] : 0;
    $sql = "select * from ".DATABASE_TENANT_TAGINSTANCT." where navid =".$navid." and tenantid=".$_SESSION["user"]->tenantid;
    $qr = $dsql->ExecQuery($sql);
    if(!$qr)
    {
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
			$temp_arr = array();
            $temp_arr["navid"]=$result["navid"];
            $temp_arr["instanceid"]=$result["id"];
            $temp_arr["instancetype"]=$result["instancetype"];
            $temp_arr["updatetime"]=$result["updatetime"];
            $arrs[]=$temp_arr;
        }
    }
}


/**
 *
 * 根据instanceid删除元素信息
 * @param  $instanceid
 * @param  $elementid
 * @throws Exception
 */
function delelementbyinstanceid($instanceid,$elementid)
{
    global $dsql,$arrs;

    $sql = "delete from ".DATABASE_ELEMENT." where  instanceid =".$instanceid." and elementid=".$elementid;


    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        throw new Exception(TYPE_PAGE."- deleteelements()-".$sql."-".mysql_error());
        $arrs["result"]=0;
    }
    else
    {
        $arrs["result"]=1;
    }
}

/**
 *
 * 根据instanceid删除元素信息
 * @param  $instanceid
 * @param  $elementid
 * @throws Exception
 */
function delelementbyincid($incid)
{
    global $dsql,$logger;
    $sql = "delete from ".DATABASE_ELEMENT." where  instanceid =".$incid;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
        //$arrs["result"]=0;
        return false;
    }
    else
    {
        $delpinsql = "delete from ".DATABASE_PINRELATION." where instanceid ={$incid}";
        $qrdelpin = $dsql->ExecQuery($delpinsql);
        if(!$qrdelpin){
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$delpinsql} ".$dsql->GetError());
        }
        return true;
    }
}




/**
 *
 * 根据元素ID删除元素信息
 * @param unknown_type $id
 */
function delelement()
{
    global $dsql,$arrs,$logger;
    $id=$_REQUEST['elementid'];
    $chksql = "select a.tenantid from ".DATABASE_TENANT_TAGINSTANCT." as a inner join ".DATABASE_ELEMENT." as b on a.id = b.instanceid";
    $chksql .= " where a.tenantid = {$_SESSION['user']->tenantid} and b.elementid = {$id}";
    $qrchk = $dsql->ExecQuery($chksql);
    if(!$qrchk){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$chksql} ".$dsql->GetError());
        $arrs["result"]=0;
        return;   
    }
    else{
        if($dsql->GetTotalRow($qrchk) < 1){
            $logger->error(__FILE__." func:".__FUNCTION__." data is empty sql:{$chksql} ");
            $arrs["result"]=0;
            return;   
        }
        else{
            $sql = "delete from ".DATABASE_ELEMENT." where elementid = {$id}";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr)
            {
                $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
                $arrs["result"]=0;
            }
            else
            {
                if($dsql->GetAffectedRows() > 0){
                    $delpinsql = "delete from ".DATABASE_PINRELATION." where outelementid ={$id} or inelementid={$id}";
                    $qrdelpin = $dsql->ExecQuery($delpinsql);
                    if(!$qrdelpin){
                        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$delpinsql} ".$dsql->GetError());
                    }
                    $arrs["result"]=1;
                }
                else{
                    $arrs["result"]=0;
                }
            }
        }
    }
}


/**
 *
 * 根据instanceid删除实例信息
 * @param unknown_type $instanceid 实例ID
 * @throws Exception
 */
function delinstance($instanceids)
{
    global $dsql,$arrs, $logger;
    //$instanceid = $_REQUEST['instanceid'];
    $sql = "delete from ".DATABASE_TENANT_TAGINSTANCT." where id in ({$instanceids}) and tenantid={$_SESSION['user']->tenantid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
        //$arrs["result"]=0;
        return false;
    }
    else
    {
        if($dsql->GetAffectedRows() > 0){
            $delElesql = "delete from ".DATABASE_ELEMENT." where instanceid in ({$instanceids})";
            $qrdelele = $dsql->ExecQuery($delElesql);
            if(!$qrdelele){
                $logger->error(__FILE__." func:".__FUNCTION__." sql:{$delElesql} ".$dsql->GetError());
            }
            $delpinsql = "delete from ".DATABASE_PINRELATION." where instanceid in ({$instanceids})";
            $qrdelpin = $dsql->ExecQuery($delpinsql);
            if(!$qrdelpin){
                $logger->error(__FILE__." func:".__FUNCTION__." sql:{$delpinsql} ".$dsql->GetError());
            }
            //$arrs["result"]=1;
            return true;
        }
        else{
            //$arrs["result"]=0;
            return false;
        }
    }
}
function update($userarr)
{
    global $dsql,$arrs;
    $sql = "update ".DATABASE_CUSTOMER_NAVIGATE." set label=".$userarr["label"].", tenantid=".$userarr["tenantid"].",parentid='".$userarr["parentid"]."',filepath='".$userarr["path"]."',level=".$userarr["level"]." ,modeltype=".$userarr["modeltype"].",updatetime=".time()." where id =".$userarr["id"];

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
function checkExist()
{
    global $dsql,$arrs;
    $num;
	//$navid = $_REQUEST["navid"];
	$label = $_REQUEST["name"];
	$navlevel = $_REQUEST["navlevel"];
	if($navlevel == 1){
		$sql="select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where label='".$label."' and level=1 and userid=".$_SESSION["user"]->getuserid();;
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			set_error_msg("sql error:".$dsql->GetError());
		}

		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$num=$result["totalcount"];
		}
	}
	else{
		$num = 0;
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
function checktabexist(){
	global $dsql, $arrs;
	$parentid = $_REQUEST['parentid'];
	$tabname = $_REQUEST['tabname'];
	$navid = isset($_REQUEST['navid']) ? $_REQUEST['navid'] : "";
	$navidcond = "";
	if($navid != ""){
		$navidcond = " and id != ".$navid."";
	}
	$sql = "select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where label ='".$tabname."' and parentid=".$parentid."".$navidcond."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
	}
	else{
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		$num = $result["totalcount"];
		if($num > 0){
			$arrs["flag"] = 1;
		}
		else{
			$arrs["flag"] = 0;
		}
	}
}

//检查用户名称是否重复
function checkExistByUserID($username,$tid,$uid)
{
    global $dsql,$arrs;
    $num;

    $sql="select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where username='".$username."' and tenantid=".$tid." and usrid <>"+$uid;

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

//设置用户与模型的关系
function settenantmodel()
{
    global $dsql,$arrs,$arrsdata;
    $num=0;

    if(!checkmodelrule($arrsdata->modelid,31,$arrsdata->content))
    {
        $arrs["flag"]=0;
    }
    else
    {
        //检查当前导航是否已与模型创建了关联
        $checkmodel = "select count(0) as cnt from  ".DATABASE_CUSTOMER_NAVIGATE." where id = ".$arrsdata->navid." and modelid=".$arrsdata->modelid;
        $checkinstance="select count(0) as cnt from ".DATABASE_TENANT_TAGINSTANCT." where navid = ".$arrsdata->navid;
        $createinstance = "insert into ".DATABASE_TENANT_TAGINSTANCT." (modelid,tenantid,content,navid,updatetime) values (".$arrsdata->modelid.",".$_SESSION["user"]->tenantid.",'".json_encode($arrsdata->content)."',".$arrsdata->navid.",".time().")";
        $updateinstalce = "update ".DATABASE_TENANT_TAGINSTANCT." set content='".json_encode($arrsdata->content)."',updatetime=".time()." where id = ".$arrsdata->content->modelid;
        $updatenav = "update ".DATABASE_CUSTOMER_NAVIGATE." set modelid = ".$arrsdata->modelid.",filepath='".$arrsdata->filepath."',updatetime = ".time()." where id = ".$arrsdata->navid;
        $sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where id=".$id;
        $qr = $dsql->ExecQuery($checkmodel);
        //echo $sql;
        if($arrsdata->instanceid==null)
        {
            $arrsdata->instanceid=0;
        }
        if($arrsdata->instanceid==0)
        {
            //修改当前导航的信息信息更新modelid信息
            $qr2 = $dsql->ExecQuery($updatenav);
            //$arrs["sql_updatenav"] = $updatenav;
            //创建一条实例信息
            $qrinstance = $dsql->ExecQuery($createinstance);

            //$arrs["sql_qrinstance "] = $createinstance;
        }
        else
        {
            //判断是修改原有实例还是修改为新的模型实例
            if($arrsdata->midelid==$arrsdata->content->modelid)
            {
                $updateinstance = $dsql->ExecQuery($updateinstalce);
                if(!$updateinstance)
                {
                    $arrs["flag"]=0;
                }
                else
                {
                    $arrs["flag"]=1;
                }

            }
            else
            {//删除原有实例插入新的实例,分为散步操作：1.从model实例表删除原有实例模型2.修改当前导航的modelid字段3.增加一条新的实例记录
                //1.删除原有的实例信息
                try {
                    $deltag="delete from ".DATABASE_TENANT_TAGINSTANCT." where id = ".$arrsdata->instanceid;
                    $deletetag = $dsql->ExecQuery($updateinstalce);
                    $arrs["deltah"] = $deltag;
                    //2.修改当前导航的modelid
                    $updatenav = "update ".DATABASE_CUSTOMER_NAVIGATE." set modelid = ".$arrsdata->content->modelid." where id = ".$arrsdata->navid;
                    $udpatenavigate  = $dsql->ExecQuery($updatenav);
                    $arrs["updatenav"] = $updatenav;
                    //3.插入新的导航信息
                    $inserttag ="insert into ".DATABASE_TENANT_TAGINSTANCT." (modelid,tenantid,content,updatetime) values (".$arrsata->content->modelid.",".$_SESSION["user"]->tenantid.",'".json_encode($arrsdata->content)."',".time().")";
                    $arrs["inserttag"] = $inserttag;
                    $insinstance = $dsql->ExecQuery($inserttag);
                    $arrs["flag"]==1;
                }
                catch(Exception $e)
                {
                    $arrs["flag"]==0;
                }
            }
        }
    }//end rule check
}//end function


//简述数据是否存在的公共函数
function checkrepeat($sql)
{
    global $dsql;
    $num=0;
    $checksql = $sql;

    $qr = $dsql->ExecQuery($sql);
    //echo $sql;

    if (!$qr)
    {
        //throw new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        $temp_arr = array();
        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
                $arrs[CHILDS][] = $temp_arr;
            }
        }
    }

}


/**
 *
 * 根据导航ID获取实例元素信息
 * @param  $navid   导航ID
 */
function getrender($navid)
{
    global $dsql,$arrs;
    $num=0;

    $sql="select a.*,b. from ".DATABASE_TENANT_TAGINSTANCT." as a inner join ".DATABASE_ELEMENT." as b  on a.id = b.instanceid where a.navid=".$navid." and b.type=2";
    $qr = $dsql->ExecQuery($sql);
    //echo $sql;

    if (!$qr)
    {
        //throw new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        $arrs["totalcount"]=$num;

        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
				$temp_arr = array();
                $temp_arr["instanceid"] = $result["instanceid"];
                $temp_arr["title"] = $result["title"];
                $temp_arr["instancetype"] = $result["instancetype"];
                $temp_arr["elementid"] = $result["level"];
                $temp_arr["modelid"] = $result["modelid"];
                $temp_arr["type"] = $result["type"];
                $temp_arr["datajson"] = json_decode($result["content"]);

                $arrs[] = $temp_arr;
            }
        }
    }
}

/**
 *
 * 根据频道id获取model列表
 * @param  $channelid    频道ID
 */
function getmodelbychannel()
{
    $channelid = isset($_REQUEST["channelid"]) ? $_REQUEST["channelid"] : 0;
    if(empty($channelid)){
        $result['result'] = false;
        $result['msg'] = '参数错误';
    }
    else{
        $r = getModelsByChannel($channelid);
        if(empty($r)){
            $result['result'] = false;
            $result['msg'] = '未获取到数据';
        }
        else{
            $user = $_SESSION['user'];
            foreach($r as $key=>$value){
                $is_in = false;
                if(in_array($value->modelid,$user->tenantResource)){
                     $is_in = true;
                }
                if(!$is_in){
                    unset($r[$key]);                 
                }
            }
            $result = $r;
        }
    }

    echo json_encode($result);
}

/**
 *
 * 根据的模型ID获取模型信息
 * @param unknown_type $modelid
 */
function getmodel()
{
    global $dsql,$arrs;
    $modelid = isset($_REQUEST["modelid"]) ? $_REQUEST["modelid"] : null;
    $modeltype = isset($_REQUEST["modeltype"]) ? $_REQUEST["modeltype"] : null;
    $num=0;
    $resourcetable;

    if($modeltype==0)
    {

    }
    else
    {
        switch ($modeltype)
        {
            case 1:
                $resourcetable = DATABASE_SYSTEM_RESOURCE;
                break;
            case 2:
                $resourcetable = DATABASE_TENANT_MANAGE_RESOURCE;
                break;
            case 3:
                $resourcetable = DATABASE_TENANT_RESOURCE;
                break;
            default:
                break;
        }
    }
    $user = $_SESSION['user'];
    $roleids = implode(',',$user->roles);
    $sql = "select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." a inner join $resourcetable b on a.resourceid = b.resourceid where a.resourceid={$modelid} and a.tenantid={$user->tenantid} and a.roleid in (".$roleids.")";
    $qr = $dsql->ExecQuery($sql);

    if (!$qr)
    {
        //throw new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        //	$arrs["totalcount"]=$num;

        $temp_arr = array();
        if($num>0)
        {
            if($num>1)
            {
                while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
                {
                    unset($temp_arr);
                    $temp_arr["modelid"] = $result["resourceid"];
                    $temp_arr["rulemodel"] = json_decode($result["ruledata"]);
                    $temp_arr["modelname"] = $result["label"];
                    $arrs = $temp_arr;
                }
            }
            else
            {
                $result = $dsql->GetArray($qr, MYSQL_ASSOC);
                $temp_arr["modelid"] = $result["resourceid"];
                $temp_arr["rulemodel"] = json_decode($result["ruledata"]);
                $temp_arr["modelname"] = $result["label"];

                $arrs = $temp_arr;
            }
        }
        //echo $arrs;
    }

}

/**
 *
 * 获取某个租户的模型列表
 */
function getmodelbytenantid()
{
    global $dsql,$arrs,$logger;
    $num=0;
    $user = $_SESSION['user'];
    if(count($user->roles) == 0){
        return;
    }
    $roleids = implode(',',$user->roles);
    $sql="select count(distinct a.resourceid),a.resourceid,b.label from ".DATABASE_ACCOUNTING_RULE." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid=".$user->tenantid." and a.roleid in ({$roleids}) group by a.resourceid";
    $logger->debug(__FUNCTION__.__FILE__.__LINE__." sql:".var_export($sql, true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        //throw new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        //$arrs["totalcount"]=$num;

        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
				$temp_arr = array();
                $temp_arr["modelid"] = $result["resourceid"];
                $temp_arr["modelname"] = $result["label"];
                $arrs[] = $temp_arr;
            }
        }
    }
}


/**
 *
 * 获取某个租户的模型规则
 * @param  $modelid
 */
function gettenantmodelrule()
{
    global $dsql,$arrs,$logger;
    $num=0;
    $modelid = isset($_REQUEST["modelid"]) ? $_REQUEST["modelid"] : null;
    if(!isset($modelid)){
        $logger->error(__FILE__." func:".__FUNCTION__." error: params modelid is null");
        $arrs['result'] = 0;
        return;
    }
	//虚拟数据源时直接从model_config静态数组返回
	if($modelid == 6){
		$vdata  = getModelByID(6);
		$vdataArr = json_decode(json_encode($vdata), true);
		$newVdataArr = $vdataArr["datajson"];
		$arrs["modelid"] = 6;
		$arrs["rulemodel"] = $newVdataArr;
		$arrs['modelname'] = "";
		return ;
	}


    $user = $_SESSION['user'];
    $roleids = implode(',',$user->roles);
    $sql = "select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." a inner join ".DATABASE_TENANT_RESOURCE." b on a.resourceid = b.resourceid where a.resourceid={$modelid} and a.tenantid={$user->tenantid} and a.roleid in (".$roleids.")";
	$start_time = microtime_float();
    $qr = $dsql->ExecQuery($sql);
	$end_time = microtime_float();
	$timediff = $end_time - $start_time;
	$logger->debug("查询模型权限条，花费时间：{$timediff}");

    if(!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        $temp_arr = array();
        if($num>0)
        {
            //合并权限
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
                $arrs["modelid"] = $result["resourceid"];
                $authJson = json_decode($result["ruledata"], true);
				if(VERSION != $authJson["version"]){
					$newJson  = getModelByID($authJson["modelid"]);
					$jsonArr = json_decode(json_encode($newJson), true);
					$newJsonArr = $jsonArr["datajson"];
					//$arrs["rulemodel"] = getNewVersionJson($newJsonArr, $authJson); 
					$arrs["rulemodel"] = getCommonMergeJson(1, $newJsonArr, $authJson);
				}
				else{
					$arrs["rulemodel"] = json_decode($result["ruledata"], true);
				}
				//新增模型时, 获取权限json, 同时把权限添加到filtervalue中, 对应formconfig中init函数 allowcontroll = 0时赋值
				//$arrs["rulemodel"] = mergefiltervalue($arrs["rulemodel"], array());
                $arrs['modelname'] = $result['label'];
            }
        }
    }
}


/**
 *
 * 从静态数组获取所有模型
 * @throws Exception
 */
function getallmodels(){
    global $modeljs;
    echo json_encode($modeljs);
}

/**
 *
 * 修改某个租户的模型规则信息
 * @param unknown_type $ruleid 规则ID
 */
function updatetenantmodel($ruleid)
{
    global $dsql,$arrs;$arrsdata;

    $sql="update ".DATABASE_ACCOUNTING_RULE." set ruledata='".json_encode($arrsdata.content)."' where id=".$ruleid;

    try {
        $qr = $dsql->ExecQuery($sql);
        	
        if(!$qr)
        {
            $arrs["flag"]=0;
        }
        else
        {
            $arrs["flag"]=1;

        }
    }
    catch (Exception $e)
    {
        $arrs["flag"]=0;
    }
}
//添加页内卡片
function addnavtab(){
    global $dsql,$arrs,$arrsdata, $logger;
	if($_SESSION["user"]->usertype == 2){
		$logger->error(__FILE__." func:".__FUNCTION__."只读用户不能添加卡片导航");
		$arrs["flag"]=0;
		return;
	}
	if($arrsdata->defaulttab == 1){
		$sql="update ".DATABASE_CUSTOMER_NAVIGATE." set ishomepage = 0 where parentid=".$arrsdata->parentid;
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
	}
   
	$sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (userid,tenantid,parentid,updatetime,label,ishomepage,level,pagetype) values (".$_SESSION["user"]->getuserid().",".$_SESSION["user"]->tenantid.",".$arrsdata->parentid.",".time().", '".$arrsdata->tabname."', ".$arrsdata->defaulttab.", ".$arrsdata->level.", ".$arrsdata->pagetype.")";
	$qr = $dsql->ExecQuery($sql);
	if($qr){
		$arrs["flag"] = 1;
	}
	else{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"] = 0;
	}
	//添加卡片成功
	if($arrs['flag'] == 1){
		//查询导航下卡片的个数,如果只有一个卡片时, 此卡片为默认卡片, 卡片的路径为父级路径
		$sqlp = "select count(*) as cnt from ".DATABASE_CUSTOMER_NAVIGATE." where parentid = ".$arrsdata->parentid."";
		$qrp = $dsql->ExecQuery($sqlp);
		if(!$qrp){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlp} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$tabres = $dsql->GetArray($qrp);
		}

		$getlastid = "select LAST_INSERT_ID() as id";
		$qr2 = $dsql->ExecQuery($getlastid);
		if(!$qr2){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$idrs = $dsql->GetArray($qr2);
			$navid = $idrs['id'];
			if(savemultimodel($navid,'') === false){
				$arrs["flag"]=0;
				return ;
			}
			else{
				$filepath = "tenant/{$_SESSION['user']->tenantid}/{$navid}.html";
				$upfilesql = "update ".DATABASE_CUSTOMER_NAVIGATE." set filepath='{$filepath}' where id={$navid}";
				$qrupf = $dsql->ExecQuery($upfilesql);
				if(!$qrupf){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upfilesql} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
				else{
					$arrs["flag"]=1;
				}
			}
			$sltinstance = "select * from ".DATABASE_TENANT_TAGINSTANCT." where navid = ".$arrsdata->parentid."";
			$qrinstance = $dsql->ExecQuery($sltinstance);
			if(!$qrinstance){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltab} ".$dsql->GetError());
				$arrs["flag"] = 0;
			}
			else{
				$num = $dsql->GetTotalRow($qrinstance);
			}
			//当为第一个卡片,并且导航中有模型时
			if($tabres['cnt'] == 1 && $num > 0){
				$sqltab = "select filepath,mergedata from ".DATABASE_CUSTOMER_NAVIGATE." where id = ".$arrsdata->parentid."";
				$qrtab = $dsql->ExecQuery($sqltab);
				if(!$qrtab){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltab} ".$dsql->GetError());
					$arrs["flag"] = 0;
				}
				else{
					$result = $dsql->GetArray($qrtab, MYSQL_ASSOC);
				}
				$upsql = "update ".DATABASE_CUSTOMER_NAVIGATE." set mergedata='".$result['mergedata']."'  where id={$navid}";
				$qrup = $dsql->ExecQuery($upsql);
				if(!$qrup){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
				else{
					$arrs["flag"]=1;
				}
				//copy父文件存到第一个卡片中
				$sRealPath = realpath('../');
				$file = $sRealPath."/".$result['filepath'];
				$newfile = $sRealPath."/".$filepath; 
				if (!copy($file, $newfile)) {
					$logger->error(__FILE__." func:".__FUNCTION__." failed to copy ".$file."");
				}
				/*
				//把父导航包含的实例复制一份以新卡片导航id存入实例导航表,同时把实例id置为负值, 卡片完全删除时再恢复
				while($ins = $dsql->GetArray($qrinstance, MYSQL_ASSOC)){
				}
				 */
				//更新实例所属的导航
				$upinstance = "update ".DATABASE_TENANT_TAGINSTANCT." set navid = ".$navid." where navid = ".$arrsdata->parentid."";
				$qri = $dsql->ExecQuery($upinstance);
				if(!$qri){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upinstance} ".$dsql->GetError());
					$arrs["flag"]=0;
					return ;
				}
				else{
					$arrs["flag"]=1;
				}
			}
		}
	}
}




/**
 *
 * 添加导航信息
 * 参数:通过异步POST方式将数据提交到本函数存放在$arrsdata中
 */
function addnavigate()
{
    global $dsql,$arrs,$arrsdata, $logger;
	if($_SESSION["user"]->usertype == 2){
		$logger->error(__FILE__." func:".__FUNCTION__."只读用户不能添加导航");
		$arrs["flag"]=0;
		return;
	}
    if($arrsdata->modifyhompage==0) //不修改主页
    {
        $sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (userid,tenantid,ishomepage,label,modelid,level,orderid,parentid, pagetitle,pagetype,updatetime, icon) values (".$_SESSION["user"]->getuserid().",".$_SESSION["user"]->tenantid.",".$arrsdata->ishomepage.",'".$arrsdata->label."',0,".$arrsdata->level.",0,".$arrsdata->parentid.",'".$arrsdata->pagetitle."',".$arrsdata->pagetype.",".time().",'".$arrsdata->icon."')";
		$qr = $dsql->ExecQuery($sql);
		if($qr){
			$arrs["flag"]=1;
		}
		else{
			set_error_msg("insert error".mysql_error());
			$arrs["flag"]=0;
		}
    }
    else //修改主页
    {
		//先把所有同级别的主页字段设置为0
        $sql="update ".DATABASE_CUSTOMER_NAVIGATE." set ishomepage=0 where userid=".$_SESSION["user"]->getuserid()." and tenantid=".$_SESSION["user"]->tenantid." and level=".$arrsdata->level;
        try
        {
            $qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$arrs["flag"]=0;
				$logger->error(__FILE__." ".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				set_error_msg("update  set ishomepage = 0 error".$dsql->GetError());
			}
			//添加导航的主页为1
            $sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (userid,tenantid,ishomepage,label,modelid,level,orderid,parentid,pagetitle,pagetype,updatetime, icon) values (".$_SESSION["user"]->getuserid().",".$_SESSION["user"]->tenantid.",".$arrsdata->ishomepage.",'".$arrsdata->label."',0,".$arrsdata->level.",0,".$arrsdata->parentid.",'".$arrsdata->pagetitle."',".$arrsdata->pagetype.",".time().",'".$arrsdata->icon."')";
            $qr = $dsql->ExecQuery($sql);
			if($qr){
				$arrs["flag"]=1;
			}
			else{
			    $logger->error(__FILE__." ".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
        }
        catch(Exception $e)
        {
            $logger->error(__FILE__." ".__FUNCTION__." exception".$e->getMessage());
            $arrs["flag"]=0;
        }
    }
    if($arrs['flag'] == 1){
        $getlastid = "select LAST_INSERT_ID() as id";
        $qr2 = $dsql->ExecQuery($getlastid);
        if(!$qr2){
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
            set_error_msg("insert error".mysql_error());
            $arrs["flag"]=0;
        }
        else{
            $idrs = $dsql->GetArray($qr2);
            $navid = $idrs['id'];
            if(savemultimodel($navid,'') === false){
                $arrs["flag"]=0;
                return ;
            }
            else{
                $filepath = "tenant/{$_SESSION['user']->tenantid}/{$navid}.html";
                $upfilesql = "update ".DATABASE_CUSTOMER_NAVIGATE." set filepath='{$filepath}' where id={$navid}";
                $qrupf = $dsql->ExecQuery($upfilesql);
                if(!$qrupf){
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$upfilesql} ".$dsql->GetError());
                    $arrs["flag"]=0;
                }
                else{
                    $arrs["flag"]=1;
                }
            }
        }
    }

}
function getlatestsnapshot(){
    global $dsql,$arrs,$arrsdata,$logger;
	//where
	$where = array();
	if(isset($_GET["updatetime"])){
		$where[] =  "a.updatetime = ".$_GET["updatetime"]."";
	}
	if(isset($_GET["instanceid"])){
		$where[] = "a.instanceid = ".$_GET["instanceid"]."";
	}
	if(isset($_GET["navid"])){
		$where[] = "b.navid = ".$_GET["navid"]."";
	}
	$where[] = "a.snapid != ''";
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$sql = "select a.instanceid, a.elementid, a.updatetime, a.snapid from ".DATABASE_ELEMENT." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.instanceid = b.id ".$wherestr."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
	}
	else{
		if($dsql->GetTotalRow($qr) > 0){
			$result = $dsql->GetArray($qr, MYSQL_ASSOC);
			$snapshot = getSnapShotByID($result["snapid"]);
			$arrs[CHILDS][] = $snapshot;
			$arrs["result"] = 1;
		}
	}
}
function getsnapshothistorybase($wherearr, $page=NULL, $pagesize=NULL){
    global $dsql,$logger;
	$limitcond = "";
	if(!empty($page) || !empty($pagesize)){
		$spage = !empty($page) ? $page : 1;
		$pagesize = !empty($pagesize) ? $pagesize : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	//where
	$where = array();
	if(isset($wherearr["updatetime"])){
		$where[] =  "a.updatetime = ".$wherearr["updatetime"]."";
	}
	if(isset($wherearr["instanceid"])){
		$where[] = "a.instanceid = ".$wherearr["instanceid"]."";
	}
	if(isset($wherearr["elementid"])){
		$where[] = "a.elementid = ".$wherearr["elementid"]."";
	}
	if(isset($wherearr["navid"])){
		$where[] = "b.navid = ".$wherearr["navid"]."";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$sqltotal = "select count(*) as totalcount from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.instanceid= b.id ".$wherestr."";
	$sql = "select a.updatetime, a.snapshot, a.elementid from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.instanceid= b.id ".$wherestr." ".$limitcond."";
	$qrtotal = $dsql->ExecQuery($sqltotal);
	if (!$qrtotal){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltotal} ".$dsql->GetError());
		$res["result"] = 0;
	}
	else
	{
		while ($result = $dsql->GetArray($qrtotal, MYSQL_ASSOC))
		{
			$res["totalcount"]=$result["totalcount"];
		}
	}
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$res["result"] = 0;
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr = array();
			$temp_arr["updatetime"] = $result["updatetime"];
			$temp_arr["elementid"] = $result["elementid"];
			$temp_arr['snapshot'] = json_decode($result["snapshot"],true);
			$res[CHILDS][] =  $temp_arr;
		}
		$res["result"] = 1;
	}
	return $res;
}
function getsnapshothistorybyincid(){
	global $arrs;
	$incid = isset($_GET['incid']) ? $_GET['incid'] : 0;
	$navid = isset($_GET['navid']) ? $_GET['navid'] : 0;
	$where["instanceid"] = $incid;
	$where["navid"] = $navid;
	$arrs = getsnapshothistorybase($where);
}
function replaceFuzzy2Preg($label){
	$pos = strpos($label, '*');
	if($pos !== false){
		$label = str_replace("*", ".*", $label);       
	}
	$reg = '/^'.$label.'$/';
	return $reg;
}
function geteventhistorylistall(){
    global $dsql,$arrs,$logger;
	/*
	$limitcond = "";
	if(isset($_GET['page']) || isset($_GET['pagesize'])){
		$spage = isset($_GET['page']) ? $_GET['page'] : 1;
		$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	$orderby = isset($_GET["orderby"]) ? $_GET["orderby"] : "a.triggertime desc";
	$where = array();
	if(isset($_GET["instanceid"])){
		$where[] = "a.instanceid = ".$_GET["instanceid"]."";
	}
	 */
	//登录的用户
	$userid = $_SESSION["user"]->getuserid();
	/*
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	 */
	/*

	$sqltotal = "select count(*) as totalcount from ".DATABASE_EVENT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d left join ".DATABASE_SNAPSHOT_HISTORY." as c on c.snapid=d.snapid inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr."";
	$qrtotal = $dsql->ExecQuery($sqltotal);
	if(!$qrtotal){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltotal} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		while ($result = $dsql->GetArray($qrtotal, MYSQL_ASSOC))
		{
			$arrs["totalcount"]=$result["totalcount"];
		}
	}
	 */
	$sql = "select a.instanceid,a.elementid,a.triggertime, a.sevtext,a.trigtext,a.action,a.status,b.navid,b.instancetype,c.snapid, c.snapshot, d.content, d.modelparams, navres.level1id, navres.level1,navres.level2id, navres.level2,navres.l2filepath, navres.level3id, navres.level3,navres.l3filepath from ".DATABASE_EVENT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d left join ".DATABASE_SNAPSHOT_HISTORY." as c on c.snapid = d.snapid inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and (navres.level2id = b.navid OR navres.level3id = b.navid)";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr = array();
			$temp_arr["navid"] = $result["navid"];
			$temp_arr["instanceid"] = $result["instanceid"];
			$temp_arr["instancetype"] = $result["instancetype"];
			$temp_arr["elementid"] = $result["elementid"];
			$temp_arr["triggertime"] = $result["triggertime"];
			$temp_arr["sevtext"] = $result["sevtext"];
			$temp_arr["trigtext"] = $result["trigtext"];
			$temp_arr["action"] = $result["action"];
			$temp_arr["status"] = $result["status"];
			$temp_arr["snapid"] = $result["snapid"];
			//$temp_arr['snapshot'] = json_decode($result["snapshot"],true);
			//$temp_arr['content'] = json_decode($result["content"],true);
			//$temp_arr['modelparams'] = json_decode($result["modelparams"],true);
			if(isset($temp_arr['content']["showtitle"])){
				$temp_arr["showtitle"] = $temp_arr['content']["showtitle"];
			}
			$temp_arr["level1"] = $result["level1"];
			$temp_arr["level1id"] = $result["level1id"];

			$temp_arr["level2"] = $result["level2"];
			$temp_arr["level2id"] = $result["level2id"];
			if(!empty($result["level2"])){
				$temp_arr["filepath"] = $result["l2filepath"];
			}

			$temp_arr["level3"] = $result["level3"];
			$temp_arr["level3id"] = $result["level3id"];
			if(!empty($result["level3"])){
				$temp_arr["filepath"] = $result["l3filepath"];
			}
			$arrs[CHILDS][] =  $temp_arr;
		}
		$arrs["result"] = 1;
	}
}
function geteventhistorylist(){
    global $dsql,$arrs,$logger;
	$limitcond = "";
	if(isset($_GET['page']) || isset($_GET['pagesize'])){
		$spage = isset($_GET['page']) ? $_GET['page'] : 1;
		$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	$orderby = isset($_GET["orderby"]) ? $_GET["orderby"] : "a.triggertime desc";
	$where = array();
	if(isset($_GET["level1id"])){
		$where[] = "navres.level1id=".$_GET["level1id"]."";
	}
	if(isset($_GET["level2id"])){
		$where[] = "navres.level2id=".$_GET["level2id"]."";
	}
	if(isset($_GET["level3id"])){
		$where[] = "navres.level3id=".$_GET["level3id"]."";
	}
	if(isset($_GET["scheduleid"])){
		$where[] = "a.scheduleid=".$_GET["scheduleid"]."";
	}
	if(isset($_GET["trigtext"])){
		$trigtext = $_GET["trigtext"]; 
		$pos = strpos($trigtext, '*');
		if($pos === false){
			$where[] =  "a.trigtext = '".$trigtext."'";
		}
		else{
			$trigtext= str_replace("*", "%", $trigtext);       
			$where[] =  "a.trigtext like '".$trigtext."'";
		}
	}
	//更新时间
	if(isset($_GET["triggertimestart"]) && isset($_GET["triggertimeend"])){
		$utstart = $_GET["triggertimestart"];
		$utend = $_GET["triggertimeend"];
		$where[] = "a.triggertime > ".$utstart." AND a.triggertime  < ".$utend."";
	}
	else if(isset($_GET["triggertimestart"])){
		$etstart = $_GET["triggertimestart"];
		$where[] = "a.triggertime > ".$etstart."";
	}
	else if(isset($_GET["triggertimeend"])){
		$etend = $_GET["triggertimeend"];
		$where[] = "a.triggertime < ".$etend."";
	}

	if(isset($_GET["instanceid"])){
		$where[] = "a.instanceid = ".$_GET["instanceid"]."";
	}
	//登录的用户
	$userid = $_SESSION["user"]->getuserid();
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	$sqltotal = "select count(*) as totalcount from ".DATABASE_EVENT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d left join ".DATABASE_SNAPSHOT_HISTORY." as c on c.snapid=d.snapid inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr."";
	$qrtotal = $dsql->ExecQuery($sqltotal);
	if(!$qrtotal){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltotal} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		while ($result = $dsql->GetArray($qrtotal, MYSQL_ASSOC))
		{
			$arrs["totalcount"]=$result["totalcount"];
		}
	}
	$sql = "select a.instanceid,a.elementid,a.triggertime,a.scheduleid, a.sevtext,a.trigtext,a.action,a.status,b.navid,b.instancetype,c.snapid, c.snapshot, d.content, d.modelparams, navres.level1id, navres.level1,navres.level2id, navres.level2,navres.l2filepath, navres.level3id, navres.level3,navres.l3filepath from ".DATABASE_EVENT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d left join ".DATABASE_SNAPSHOT_HISTORY." as c on c.snapid = d.snapid inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr." order by ".$orderby." ".$limitcond."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		$schedarr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr = array();
			$temp_arr["navid"] = $result["navid"];
			$temp_arr["scheduleid"] = $result["scheduleid"];
			$temp_arr["instanceid"] = $result["instanceid"];
			$temp_arr["instancetype"] = $result["instancetype"];
			$temp_arr["elementid"] = $result["elementid"];
			$temp_arr["triggertime"] = $result["triggertime"];
			$temp_arr["sevtext"] = $result["sevtext"];
			$temp_arr["trigtext"] = $result["trigtext"];
			$temp_arr["action"] = $result["action"];
			$temp_arr["status"] = $result["status"];
			$temp_arr["snapid"] = $result["snapid"];
			$temp_arr['snapshot'] = json_decode($result["snapshot"],true);
			$temp_arr['content'] = json_decode($result["content"],true);
			$temp_arr['modelparams'] = json_decode($result["modelparams"],true);
			if(isset($temp_arr['content']["showtitle"])){
				$temp_arr["showtitle"] = $temp_arr['content']["showtitle"];
			}
			$temp_arr["level1"] = $result["level1"];
			$temp_arr["level1id"] = $result["level1id"];

			$temp_arr["level2"] = $result["level2"];
			$temp_arr["level2id"] = $result["level2id"];
			if(!empty($result["level2"])){
				$temp_arr["filepath"] = $result["l2filepath"];
			}

			$temp_arr["level3"] = $result["level3"];
			$temp_arr["level3id"] = $result["level3id"];
			if(!empty($result["level3"])){
				$temp_arr["filepath"] = $result["l3filepath"];
			}
			//查询事件历史时的优化,由于相同定时任务id的 不重复查询 2014-08-02
			//相同定时任务不需要重复查询时不对的,因为同一个定时任务可能修改预警条件, 所以可以认为事件历史中的预警条件都是不同的,
			//因为不能确定是否修改,所以不重复查询的优化不可用
			if(!isset($schedarr[$result["scheduleid"]."_".$result["triggertime"]])){
				$tmpres = getSnapshotScheduleAll($result["instanceid"], $result["triggertime"], $result["scheduleid"]);
				if($tmpres != null){
					$temp_arr["sched"] = $tmpres;
				}
				$schedarr[$result["scheduleid"]."_".$result["triggertime"]] = $tmpres;
			}
			else{
				$temp_arr["sched"] = $schedarr[$result["scheduleid"]."_".$result["triggertime"]];
			}
			$arrs[CHILDS][] =  $temp_arr;
		}
		$arrs["result"] = 1;
	}
}
function deleteeventhistoryhistory(){
    global $dsql,$arrs,$logger;
	$deletestr = isset($_GET['deletestr']) ? $_GET['deletestr'] : -1;
	$deleteArr = explode(",", $deletestr);
	$error = array();
	foreach($deleteArr as $di=>$ditem){
		$tmp = explode("-", $ditem);
		$sql = "delete from ".DATABASE_EVENT_HISTORY." where elementid = ".$tmp[0]." and triggertime = ".$tmp[1]."";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$error[] = $tmp;
		}
	}
	$dsql->FreeResult($qr);
	if(count($error) > 0){
		$arrs["result"] = 0;
		$arrs["error"] = $error;
	}
	else{
		$arrs["result"] = 1;
	}
}
function geteventitem(){
	global $logger, $arrs, $dsql;
	$sched = array();
	$arrs["sched"] = null;
	if(isset($_GET["scheduleid"])){
		$scheduleid = $_GET["scheduleid"]; 
	}
	$schedstatus = NULL; //定时任务是否过期标识
	if(isset($_GET["schedstatus"])){
		$schedstatus = $_GET["schedstatus"];
	}
	$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULE;
	if($schedstatus != NULL){
		if($schedstatus == 0){
			$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULEHISTORY;
		}
	}
	$taskwhere = "";
	if(isset($_GET["task"])){
		$taskwhere = "task = ".$_GET["task"].""; 
	}
	else{
		$taskwhere = "(task = ".TASK_SNAPSHOT." or task = ".TASK_EVENTALERT.")"; 
	}
	$sql = "select * from ".$dbtable." where tasktype = ".TASKTYPE_UPDATE." and ".$taskwhere." and id = ".$scheduleid."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FUNCTION__." - sql:{$sql} - ".$dsql->GetError());
		$arrs["result"] = 0;
		break;
	}
	else{
		while($sched = $dsql->GetObject($qr)){
			$sched->params = json_decode($sched->params);
			$arrs["result"] = 1;
			$arrs["sched"] = $sched;
		}
		$dsql->FreeResult($qr);
	}
}
/*
function geteventitem(){
	global $logger, $arrs, $dsql;
	$sched = array();
	if(isset($_GET['instanceid'])){
		$schedstatus = NULL;
		if(isset($_GET["schedstatus"])){
			$schedstatus = $_GET["schedstatus"];
		}
		$sched = getSnapshotSchedule($_GET['instanceid'], $schedstatus);
		$arrs["result"] = 1;
	}
	else{
		$arrs["result"] = 0;
	}
	$arrs["sched"] = $sched;
}
 */
function geteventlist(){
	global $logger, $arrs, $dsql;
	$totalcount = geteventlistall();
	$arrs = array();
	$arrs["totalcount"] = $totalcount;
	$limitcond = "";
	if(isset($_GET['page']) || isset($_GET['pagesize'])){
		$spage = isset($_GET['page']) ? $_GET['page'] : 1;
		$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		//$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	$where = array();
	if(isset($_GET["level1id"])){
		$where[] = "navres.level1id=".$_GET["level1id"]."";
	}
	if(isset($_GET["level2id"])){
		$where[] = "navres.level2id=".$_GET["level2id"]."";
	}
	if(isset($_GET["level3id"])){
		$where[] = "navres.level3id=".$_GET["level3id"]."";
	}

	//登录的用户
	$userid = $_SESSION["user"]->getuserid();
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$rescount = 0;
	$result = null;
	$limitcursor = 0;
	$eachcount = 10;
	while(1){
		$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULE;
		if(isset($_GET["schedstatus"])){
			if($_GET["schedstatus"] == 0){
				$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULEHISTORY;
			}
		}
		$taskwhere = "";
		if(isset($_GET["task"])){
			$taskwhere = "task = ".$_GET["task"].""; 
		}
		else{
			$taskwhere = "(task = ".TASK_SNAPSHOT." or task = ".TASK_EVENTALERT.")"; 
		}
		$sqltask = "select * from ".$dbtable." where tasktype = ".TASKTYPE_UPDATE." and ".$taskwhere." order by id desc limit {$limitcursor},{$eachcount}";
		$qr = $dsql->ExecQuery($sqltask);
		if(!$qr){
			$logger->error(__FUNCTION__." - sql:{$sqltask} - ".$dsql->GetError());
			$result = false;
			break;
		}
		else{
			$r_count = $dsql->GetTotalRow($qr);
			if($r_count == 0){
				break;
			}
			while($sched = $dsql->GetObject($qr)){
				$sched->params = json_decode($sched->params);
				$instanceid = $sched->params->taskparams->instanceid;
				//查看这个instance是否有历史事件,返回事件历史个数
				$eventcount = 0;
				$esql = "select count(*) as eventcount from ".DATABASE_EVENT_HISTORY." where instanceid = ".$instanceid." and scheduleid = ".$sched->id."";
				$eqr = $dsql->ExecQuery($esql);
				if(!$eqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				}
				else{
					while($res = $dsql->GetArray($eqr, MYSQL_ASSOC)){
						$eventcount = $res["eventcount"];
					}
				}
				$dsql->FreeResult($eqr);
				//查看这个instance是否有快照
				$nsql = "select a.snapid, a.snapshot, a.manualupdate,a.updatetime, d.elementid ,d.content,d.modelparams from ".DATABASE_SNAPSHOT_HISTORY." as a right join ".DATABASE_ELEMENT." as d on a.elementid = d.elementid  where d.instanceid = ".$instanceid." order by a.updatetime desc limit 1";

				$nqr = $dsql->ExecQuery($nsql);
				if(!$nqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				}
				else{
					$snap = array();
					while($nres = $dsql->GetArray($nqr, MYSQL_ASSOC)){
						$snap["elementid"] = $nres["elementid"];
						$snap['content'] = json_decode($nres["content"],true);
						if(!empty($nres['snapshot'])){
							$snap['snapshot'] = json_decode($nres['snapshot'], true);
						}
						if(!empty($nres["snapid"])){
							$snap["snapid"] = $nres["snapid"];
						}
						if(!empty($nres["modelparams"])){
							$snap["modelparams"] = json_decode($nres["modelparams"], true); 
						}
					}
				}
				$sql = "SELECT b.navid,b.instancetype, navres.level1id, navres.level1,navres.level2id, navres.level2,navres.l2filepath, navres.level3id, navres.level3,navres.l3filepath FROM ".DATABASE_TENANT_TAGINSTANCT." AS b INNER JOIN ( SELECT l3.id AS level3id, l3.label AS level3,l3.filepath AS l3filepath, level2id, level2, l2filepath, level1id, level1 FROM ".DATABASE_CUSTOMER_NAVIGATE." AS l3 RIGHT JOIN ( SELECT l2.id AS level2id, l2.label AS level2, l2.filepath AS l2filepath, level1id, level1 FROM ".DATABASE_CUSTOMER_NAVIGATE." AS l2 INNER JOIN ( SELECT l1.id AS level1id, l1.label AS level1 FROM ".DATABASE_CUSTOMER_NAVIGATE." AS l1 WHERE l1.parentid = 0 AND l1.userid = ".$userid.") AS l1res ON level1id = l2.parentid) AS l2res ON l3.parentid = level2id) AS navres ON  b.id = ".$instanceid." AND (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr."";
				$qrnav = $dsql->ExecQuery($sql);
				if(!$qrnav){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["result"] = 0;
				}
				else{
					while($res = $dsql->GetArray($qrnav)){
						$rescount ++;
						$temp_arr = array();
						$temp_arr["instanceid"] = $instanceid;
						$temp_arr["instancetype"] = $res["instancetype"];
						$temp_arr["navid"] = $res["navid"];
						$temp_arr["level1"] = $res["level1"];
						$temp_arr["level1id"] = $res["level1id"];
						$temp_arr["level2"] = $res["level2"];
						$temp_arr["level2id"] = $res["level2id"];
						if(!empty($res["level2"])){
							$temp_arr["filepath"] = $res["l2filepath"];
						}
						$temp_arr["level3"] = $res["level3"];
						$temp_arr["level3id"] = $res["level3id"];
						if(!empty($res["level3"])){
							$temp_arr["filepath"] = $res["l3filepath"];
						}
						if(isset($snap['elementid'])){
							$temp_arr['elementid'] = $snap['elementid'];
						}
						if(isset($snap['snapshot'])){
							$temp_arr['snapshot'] = $snap['snapshot'];
						}
						if($snap['content']){
							$temp_arr['content'] = $snap['content'];
						}
						if(isset($snap['content']["showtitle"])){
							$temp_arr["showtitle"] = $temp_arr['content']["showtitle"];
						}
						if(isset($sched->id)){
							$temp_arr["scheduleid"] = $sched->id;
						}
						if(isset($sched->remarks)){
							$temp_arr["remarks"] = $sched->remarks;
						}
						$temp_arr["haseventalert"] = false;
						if(isset($sched->params->taskparams->eventlist)){
							//有事件组合条件的情况,才有事件预警
							if(isset($sched->params->taskparams->eventlist->alarms) && count($sched->params->taskparams->eventlist->alarms) > 0){
								$temp_arr["haseventalert"] = true;
								if(isset($sched->params->taskparams->eventlist->name)){
									$temp_arr["eventname"] = $sched->params->taskparams->eventlist->name;
								}
								if(isset($sched->params->taskparams->eventlist->remarks)){
									$temp_arr["eventremarks"] = $sched->params->taskparams->eventlist->remarks;
								}
							}
						}
						//事件历史
						$temp_arr["eventalarms"] = $eventcount;
						if($rescount > $limit_cursor){
							$arrs[CHILDS][] = $temp_arr;
							if(count($arrs[CHILDS]) == $pagesize){
								break 3;
							}
						}
					}
				}
			}
			$dsql->FreeResult($qr);
		}
		if($r_count < $eachcount){
			break;
		}
		$limitcursor += $eachcount;
	}
}
function geteventlistall(){
	global $logger, $arrs, $dsql;
	$arrs = array();
	//登录的用户
	$userid = $_SESSION["user"]->getuserid();
	$rescount = 0;
	$result = null;
	$limitcursor = 0;
	$eachcount = 10;
	$needall = true; //是否需要查询全部的导航,初始化时需要,后续过滤带有活动和过期,只是为了返回总条数,在geteventlist中调用的
	$searchedhistorytable = false;
	$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULE;
	if(isset($_GET["schedstatus"])){
		$needall = false;
		if($_GET["schedstatus"] == 0){
			$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULEHISTORY;
		}
	}
	$where = array();
	if(isset($_GET["level1id"])){
		$where[] = "navres.level1id=".$_GET["level1id"]."";
	}
	if(isset($_GET["level2id"])){
		$where[] = "navres.level2id=".$_GET["level2id"]."";
	}
	if(isset($_GET["level3id"])){
		$where[] = "navres.level3id=".$_GET["level3id"]."";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	while(1){
		$taskwhere = "";
		if(isset($_GET["task"])){
			$taskwhere = "task = ".$_GET["task"].""; 
		}
		else{
			$taskwhere = "(task = ".TASK_SNAPSHOT." or task = ".TASK_EVENTALERT.")"; 
		}
		$sqltask = "select * from ".$dbtable." where tasktype = ".TASKTYPE_UPDATE." and ".$taskwhere." limit {$limitcursor},{$eachcount}";
		$qr = $dsql->ExecQuery($sqltask);
		if(!$qr){
			$logger->error(__FUNCTION__." - sql:{$sqltask} - ".$dsql->GetError());
			$result = false;
			break;
		}
		else{
			$r_count = $dsql->GetTotalRow($qr);
			if($r_count == 0){
				if($needall){
					if($searchedhistorytable){
						break;
					}
					else{
						$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULEHISTORY;
						$searchedhistorytable = true;
						$limitcursor = 0;
						continue;
					}
				}
				else{
					break;
				}
			}
			while($sched = $dsql->GetObject($qr)){
				$sched->params = json_decode($sched->params);
				$instanceid = $sched->params->taskparams->instanceid;
				$sql = "SELECT b.navid,b.instancetype, navres.level1id, navres.level1,navres.level2id, navres.level2,navres.l2filepath, navres.level3id, navres.level3,navres.l3filepath FROM ".DATABASE_TENANT_TAGINSTANCT." AS b INNER JOIN ( SELECT l3.id AS level3id, l3.label AS level3,l3.filepath AS l3filepath, level2id, level2, l2filepath, level1id, level1 FROM ".DATABASE_CUSTOMER_NAVIGATE." AS l3 RIGHT JOIN ( SELECT l2.id AS level2id, l2.label AS level2, l2.filepath AS l2filepath, level1id, level1 FROM ".DATABASE_CUSTOMER_NAVIGATE." AS l2 INNER JOIN ( SELECT l1.id AS level1id, l1.label AS level1 FROM ".DATABASE_CUSTOMER_NAVIGATE." AS l1 WHERE l1.parentid = 0 AND l1.userid = ".$userid.") AS l1res ON level1id = l2.parentid) AS l2res ON l3.parentid = level2id) AS navres ON  b.id = ".$instanceid." AND (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr."";
				$qrnav = $dsql->ExecQuery($sql);
				if(!$qrnav){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["result"] = 0;
				}
				else{
					while($res = $dsql->GetArray($qrnav)){
						$rescount ++;
						$temp_arr = array();
						$temp_arr["instanceid"] = $instanceid;
						$temp_arr["instancetype"] = $res["instancetype"];
						$temp_arr["navid"] = $res["navid"];
						$temp_arr["level1"] = $res["level1"];
						$temp_arr["level1id"] = $res["level1id"];
						$temp_arr["level2"] = $res["level2"];
						$temp_arr["level2id"] = $res["level2id"];
						if(!empty($res["level2"])){
							$temp_arr["filepath"] = $res["l2filepath"];
						}
						$temp_arr["level3"] = $res["level3"];
						$temp_arr["level3id"] = $res["level3id"];
						if(!empty($res["level3"])){
							$temp_arr["filepath"] = $res["l3filepath"];
						}
						$arrs[CHILDS][] = $temp_arr;
					}
				}
			}
			$dsql->FreeResult($qr);
		}
		if($r_count < $eachcount){
			if($needall){
				if($searchedhistorytable){
					break;
				}
				else{
					$dbtable = DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULEHISTORY;
					$searchedhistorytable = true;
					$limitcursor = 0;
					continue;
				}
			}
			else{
				break;
			}
		}
		$limitcursor += $eachcount;
	}
	$res = !empty($arrs[CHILDS]) ? count($arrs[CHILDS]) : 0;
	return $res;
}
function getsnapshothistorylistall(){
    global $dsql,$arrs,$logger;
	//登录的用户
	$userid = $_SESSION["user"]->getuserid();
	$sql = "select a.snapid,a.instanceid,a.elementid,a.updatetime,a.snapshot,a.content,b.navid,b.instancetype, d.modelparams, navres.level1id, navres.level1,navres.level2id, navres.level2,navres.l2filepath, navres.level3id, navres.level3,navres.l3filepath from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and d.modelid != 6 and (navres.level2id = b.navid OR navres.level3id = b.navid)";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		$arrs["navarr"] = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr = array();
			$temp_arr["level1"] = $result["level1"];
			$temp_arr["level1id"] = $result["level1id"];
			$temp_arr["level2"] = $result["level2"];
			$temp_arr["level2id"] = $result["level2id"];
			if(!empty($result["level2"])){
				$temp_arr["filepath"] = $result["l2filepath"];
			}

			$temp_arr["level3"] = $result["level3"];
			$temp_arr["level3id"] = $result["level3id"];
			if(!empty($result["level3"])){
				$temp_arr["filepath"] = $result["l3filepath"];
			}
			$arrs[CHILDS][] =  $temp_arr;
		}
		$arrs["result"] = 1;
	}
}
//新版
function getsnapshothistorylist(){
    global $dsql,$arrs,$logger;
	$limitcond = "";
	if(isset($_GET['page']) || isset($_GET['pagesize'])){
		$spage = isset($_GET['page']) ? $_GET['page'] : 1;
		$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	$orderby = isset($_GET["orderby"]) ? $_GET["orderby"] : "a.updatetime desc";
	$where = array();

	//更新时间
	if(isset($_GET["updatetimestart"]) && isset($_GET["updatetimeend"])){
		$utstart = $_GET["updatetimestart"];
		$utend = $_GET["updatetimeend"];
		$where[] = "a.updatetime > ".$utstart." AND a.updatetime  < ".$utend."";
	}
	else if(isset($_GET["updatetimestart"])){
		$etstart = $_GET["updatetimestart"];
		$where[] = "a.updatetime > ".$etstart."";
	}
	else if(isset($_GET["updatetimeend"])){
		$etend = $_GET["updatetimeend"];
		$where[] = "a.updatetime < ".$etend."";
	}
	if(isset($_GET["level1id"])){
		$where[] = "navres.level1id=".$_GET["level1id"]."";
	}
	if(isset($_GET["level2id"])){
		$where[] = "navres.level2id=".$_GET["level2id"]."";
	}
	if(isset($_GET["level3id"])){
		$where[] = "navres.level3id=".$_GET["level3id"]."";
	}
	if(isset($_GET["instanceid"])){
		$where[] = "a.instanceid=".$_GET["instanceid"]."";
	}
	//登录的用户
	$userid = $_SESSION["user"]->getuserid();
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	$sqltotal = "select count(*) as totalcount from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and d.modelid != 6 and (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr."";
	$qrtotal = $dsql->ExecQuery($sqltotal);
	if(!$qrtotal){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltotal} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		while ($result = $dsql->GetArray($qrtotal, MYSQL_ASSOC))
		{
			$arrs["totalcount"]=$result["totalcount"];
		}
	}
	$sql = "select a.snapid,a.instanceid,a.elementid,a.updatetime,a.snapshot,a.content,b.navid,b.instancetype, d.modelparams, navres.level1id, navres.level1,navres.level2id, navres.level2,navres.l2filepath, navres.level3id, navres.level3,navres.l3filepath from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b inner join ".DATABASE_ELEMENT." as d inner join (select l3.id as level3id, l3.label as level3,l3.filepath as l3filepath, level2id, level2, l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l3 right join ( select l2.id as level2id, l2.label as level2, l2.filepath as l2filepath, level1id, level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l2 inner join ( select l1.id as level1id , l1.label as level1 from ".DATABASE_CUSTOMER_NAVIGATE." as l1 where l1.parentid = 0 and l1.userid = ".$userid." ) as l1res on level1id = l2.parentid) as l2res on l3.parentid = level2id) as navres on a.instanceid = b.id  and a.elementid = d.elementid and d.modelid != 6 and (navres.level2id = b.navid OR navres.level3id = b.navid) ".$wherestr." order by ".$orderby." ".$limitcond."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		$arrs["navarr"] = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr = array();
			$temp_arr["snapid"] = $result["snapid"];
			$temp_arr["navid"] = $result["navid"];
			$temp_arr["instanceid"] = $result["instanceid"];
			$temp_arr["instancetype"] = $result["instancetype"];
			$temp_arr["elementid"] = $result["elementid"];
			$temp_arr["updatetime"] = $result["updatetime"];
			//$temp_arr['snapshot'] = json_decode($result["snapshot"],true);
			$temp_arr['content'] = json_decode($result["content"],true);
			$temp_arr['modelparams'] = json_decode($result["modelparams"],true);
			if(isset($temp_arr['content']["showtitle"])){
				$temp_arr["showtitle"] = $temp_arr['content']["showtitle"];
			}
			$temp_arr["level1"] = $result["level1"];
			$temp_arr["level1id"] = $result["level1id"];

			$temp_arr["level2"] = $result["level2"];
			$temp_arr["level2id"] = $result["level2id"];
			if(!empty($result["level2"])){
				$temp_arr["filepath"] = $result["l2filepath"];
			}

			$temp_arr["level3"] = $result["level3"];
			$temp_arr["level3id"] = $result["level3id"];
			if(!empty($result["level3"])){
				$temp_arr["filepath"] = $result["l3filepath"];
			}
			$arrs[CHILDS][] =  $temp_arr;
		}
		$arrs["result"] = 1;
	}
}
function getsnapshothistorybytime(){
	global $arrs;
	$snaptime = isset($_GET['snaptime']) ? $_GET['snaptime'] : 0;
	$elementid = isset($_GET['eleid']) ? $_GET['eleid'] : -1;
	$where["updatetime"] = $snaptime;
	$where["elementid"] = $elementid;
	$arrs = getsnapshothistorybase($where, 1, 1);
}
function deleteeventalert(){
    global $dsql,$arrs,$logger;
	$deletestr = isset($_GET['deletestr']) ? $_GET['deletestr'] : -1;
	$schedstatus = isset($_GET['schedstatus']) ? $_GET['schedstatus'] : NULL;
	$incarr = explode(",", $deletestr);
	foreach($incarr as $i=>$incid){
		$tmp = explode("-", $incid);
		$scheduleid = !empty($tmp[1]) ? $tmp[1] : NULL;
		$res = deleteSnapshotSchedule($tmp[0], $schedstatus, $scheduleid);
		if(!$res['result']){
			$arrs['result'] = 0;
			$arrs['msg'] = $res['msg'];
			return;
		}
		else{
			$arrs["result"] = 1;
		}
	}
}
function deletesnapshothistory(){
    global $dsql,$arrs,$logger;
	$deletestr = isset($_GET['deletestr']) ? $_GET['deletestr'] : -1;
	$sql = "delete from ".DATABASE_SNAPSHOT_HISTORY." where snapid in (".$deletestr.")";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		$arrs["result"] = 1;
	}
	$dsql->FreeResult($qr);
	//更新element表对应的snapid
	$sqls = "select elementid, instanceid from ".DATABASE_ELEMENT." where snapid in (".$deletestr.")";
	$qrs = $dsql->ExecQuery($sqls);
	if(!$qrs){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqls} ".$dsql->GetError());
		$arrs["result"] = 0;
	}
	else{
		while($result = $dsql->GetArray($qrs, MYSQL_ASSOC)){
			$sqle = "select snapid, updatetime from ".DATABASE_SNAPSHOT_HISTORY." where elementid = ".$result["elementid"]." order by updatetime desc limit 1";
			$qre = $dsql->ExecQuery($sqle);
			if(!$qre){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqle} ".$dsql->GetError());
				$arrs["result"] = 0;
			}
			else{
				$res = $dsql->GetArray($qre, MYSQL_ASSOC); 
				$snapid = 'NULL';
				if(!empty($res["snapid"])){
					$snapid = $res["snapid"];
				}
				else{
					//当快照全部删除时,更新,定时更新计划状态为不可用
					$ressched = getSnapshotSchedule($result["instanceid"]);
					if(!empty($ressched["id"])){
						$schedsql = "update ".DATABASE_WEIBOINFO.".".DATABASE_TASKSCHEDULE." set status = 0 where id= ".$ressched["id"]."";
						$schedqr = $dsql->ExecQuery($schedsql);
						if(!$schedqr){
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$schedsql} ".$dsql->GetError());
							$arrs["result"] = 0;
						}
					}
				}
				$usql = "update ".DATABASE_ELEMENT." set snapid = ".$snapid." where elementid=".$result["elementid"]."";
				$uqr = $dsql->ExecQuery($usql);
				if(!$uqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$usql} ".$dsql->GetError());
					$arrs["result"] = 0;
				}
			}
		}
	}
}

function getsnapshothistory(){
    global $dsql,$arrs,$logger;
	$limitcond = "";
	if(isset($_GET['page']) || isset($_GET['pagesize'])){
		$spage = isset($_GET['page']) ? $_GET['page'] : 1;
		$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	//where
	$where = array();
	if(isset($_GET['elementid'])){
		$where[] = "a.elementid = ".$_GET["elementid"]."";
	}
	if(isset($_GET['navid'])){
		$where[] = "b.navid = ".$_GET["navid"]."";
	}
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}
	$timestr = isset($_GET['timestr']) ? $_GET['timestr'] : -1;
	$tmpstr = explode(" ", $timestr);
	$f = explode("-", $tmpstr[0]);
	$s = explode(":", $tmpstr[1]);
	$sqlmaxmin = "select max(a.updatetime) as maxt, min(a.updatetime) as mint from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.instanceid = b.id ".$wherestr."";
	$qrmaxmin = $dsql->ExecQuery($sqlmaxmin);
	if(!$qrmaxmin){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlcount} ".$dsql->GetError());
		$arrs["result"]=0;
	}
	else{
		$mres = $dsql->GetArray($qrmaxmin, MYSQL_ASSOC);
		$maxyear = date("Y", $mres["maxt"]);
		$minyear = date("Y", $mres["mint"]);
	}
	$timeArr = array();
	if($f[0] != "~"){
		$maxyear = $f[0];
		$minyear = $f[0];
	}
	if($f[1] != "~"){
		$maxmonth = $f[1];
		$minmonth = $f[1];
	}
	else{
		$maxmonth = 12;
		$minmonth = 1;
	}
	if($f[2] != "~"){
		$maxday = $f[2];
		$minday = $f[2];
	}
	else{
		$maxday = 31;
		$minday = 1;
	}
	if($s[0] != "~"){
		$maxhour = $s[0];
		$minhour = $s[0];
	}
	else{
		$maxhour = 23;
		$minhour = 0;
	}
	if($s[1] != "~"){
		$maxminute = $s[1];
		$minminute = $s[1];
	}
	else{
		$maxminute = 59;
		$minminute = 0;
	}
	if($s[2] != "~"){
		$maxsecond = $s[2];
		$minsecond = $s[2];
	}
	else{
		$maxsecond = 59;
		$minsecond = 0;
	}
	/*
	for($i=$minyear;$i<=$maxyear;$i++){
		$str = "";
		$str .= $i."-";
		for($j=$minmonth;$j<=$maxmonth;$j++){
			$str .= $j."-";
			for($k=$minday;$k<=$maxday;$k++){
				$str .= $k." ";
				for($l=$minhour;$l<=$maxhour;$l++){
					$str .= $l.":";
					for($m=$minminute;$m<=$maxminute;$m++){
						$str .= $m.":";
						for($n=$minsecond;$n<$maxsecond;$n++){
							$str .= $n;
							$timeArr[] = strtotime($str);
						}
					}
				}
			}
		}
	}
	 */
	$sql = "select a.updatetime, a.snapid from ".DATABASE_SNAPSHOT_HISTORY." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.instanceid = b.id ".$wherestr."";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr = array();
			$year = date("Y",$result["updatetime"]);
			$month = date("n",$result["updatetime"]);
			$day = date("j",$result["updatetime"]);
			$hour = date("G",$result["updatetime"]);
			$minute = date("i",$result["updatetime"]);
			$second = date("s",$result["updatetime"]);
			if($year<=$maxyear && $year>=$minyear){
				if($month<=$maxmonth && $month>=$minmonth){
					if($day<=$maxday && $day >=$minday){
						if($hour<=$maxhour && $hour>=$minhour){
							if($minute<=$maxminute && $minute>=$minminute){
								if($second<=$maxsecond && $second>=$minsecond){
									//$temp_arr["updatetime"] = date('Y-m-d G:i:s',$result["updatetime"]);
									$temp_arr["updatetime"] = $result["updatetime"];
									$temp_arr["snapid"] = $result["snapid"];
									$arrs[CHILDS][] =  $temp_arr;
								}
							}
						}
					}
				}
			}
		}
		$arrs['totalcount'] = !empty($arrs[CHILDS]) ? count($arrs[CHILDS]) : 0;
		$arrs["result"]=1;
	}
}
function updateSnapshotHistory($incid, $eleid, $updatetime, $content, $snapshot, $manualupdate=NULL){
    global $dsql,$logger;
	$needinsert = true;
	if(!empty($manualupdate)){
		$sql = "select snapid from ".DATABASE_NAME.".".DATABASE_SNAPSHOT_HISTORY." where instanceid = ".$incid." and manualupdate != ''";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			return false;
		}
		else{
			if($dsql->GetTotalRow($qr) > 0){
				$res = $dsql->GetArray($qr, MYSQL_ASSOC);
				$sqlupdate = "update ".DATABASE_NAME.".".DATABASE_SNAPSHOT_HISTORY." set instanceid=".$incid.", elementid=".$eleid.",updatetime=".$updatetime.", manualupdate=1, content='".jsonEncode4DB($content)."', snapshot='".$snapshot."' where snapid=".$res["snapid"]." ";
				$qrupdate = $dsql->ExecQuery($sqlupdate);
				if(!$qrupdate){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlupdate} ".$dsql->GetError());
					return false;
				}
				else{
					$needinsert = false;
					$upsql = "update ".DATABASE_ELEMENT." set snapid = ".$res["snapid"]." where elementid = ".$eleid."";
					$upqr = $dsql->ExecQuery($upsql);
					if(!$upqr){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
						return false;
					}
				}
			}
		}
	}
	//在历史表添加上最新的
	if($needinsert){
		$sqllatest = "insert into ".DATABASE_NAME.".".DATABASE_SNAPSHOT_HISTORY." (instanceid,elementid,updatetime,content,snapshot, manualupdate) values ({$incid},{$eleid},{$updatetime},'".jsonEncode4DB($content)."','".$snapshot."', 1)";
		$qrlatest = $dsql->ExecQuery($sqllatest);
		if(!$qrlatest){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqllatest} ".$dsql->GetError());
			return false;
		}
		else{
			$getlastid = "select LAST_INSERT_ID() as id";
			$gqr = $dsql->ExecQuery($getlastid);
			if(!$gqr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
				return false;
			}
			else{
				$lastid = $dsql->GetArray($gqr, MYSQL_ASSOC);
				$upsql = "update ".DATABASE_ELEMENT." set snapid = ".$lastid["id"]." where elementid = ".$eleid."";
				$upqr = $dsql->ExecQuery($upsql);
				if(!$upqr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
					return false;
				}
			}
		}
	}
}
function setSnapshotShowInfo(){
	global $arrsdata,$arrs;
	$snapinfo = array();
	if(isset($arrsdata->snapid)){
		$snapinfo["snapid"] = $arrsdata->snapid;
	}
	$snapinfo["level1id"] = $arrsdata->level1id;
	$snapinfo["level2id"] = $arrsdata->level2id;
	if(isset($arrsdata->level3id)){
		$snapinfo["level3id"] = $arrsdata->level3id;
	}
	$snapinfo["instanceid"] = $arrsdata->incid;
	$snapinfo["elementid"] = $arrsdata->eleid;
	if(isset($_SESSION['user'])){
		$_SESSION['user']->setSnapshotInfo("snapshotkey", $snapinfo);
		$arrs["result"] = 1;
	}
	else{
		$arrs["result"] = 0;
	}
}
function getSnapshotShowInfo(){
	global $arrs, $logger;
	$res = $_SESSION['user']->getSnapshotInfo("snapshotkey");
	if(!empty($res)){
		$arrs["result"] = 1;
		$arrs["snapshotinfo"] = $res;
	}
	else{
		$arrs["result"] = 0;
	}
}
/**
 *
 * 添加元素信息
 * 参数:
 */
function addelement($eledata)
{
    global $dsql,$arrs,$arrsdata,$logger;
    try{
    	removeNeedlessProperty($eledata->content);//移出不需要存储到element的属性
        $jsonstr = jsonEncode4DB($eledata->content);
        $snapshot = empty($eledata->snapshot) ? NULL : jsonEncode4DB($eledata->snapshot);
        $issavesnapshot = empty($eledata->issavesnapshot) ? false : $eledata->issavesnapshot;//add 是否保存快照加个字段 by zuo:2016-9-9
        if($issavesnapshot){
            $issavesnapshot = 1;
        }else{
            $issavesnapshot = 0;
        }
        $modelparams = empty($eledata->modelparams) ? NULL : jsonEncode4DB($eledata->modelparams);
		$curtime = time();
        $sql = "insert into ".DATABASE_ELEMENT." (instanceid,modelid,type,content,title,updatetime, modelparams,issavesnapshot) values
    	(".$eledata->instanceid.",'".$eledata->modelid."',".$eledata->type.",'{$jsonstr}','{$eledata->title}',".$curtime.",'{$modelparams}',$issavesnapshot)";
        $logger->debug(__FILE__.__LINE__."sql is ".var_export($sql,true));
        $qr = $dsql->ExecQuery($sql);
        $logger->info(__FILE__.__LINE__."********addEle-succuss*********".var_export($qr,true));
        if(!$qr){
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
            //$arrs["result"]=0;
            return false;
        }
        else{
            $getlastid = "select LAST_INSERT_ID() as id";
            $qr2 = $dsql->ExecQuery($getlastid);
            //begin:修改时记录下模板id，filterrelation by zuo:2016-9-18
            $logger->info(__FILE__.__LINE__."--addEle-is-save-succuss-true--time---".var_export($curtime,true)."--date-".date("Y/m/d h:i:sa"));
            $logger->info(__FILE__.__LINE__."--addEle-eledata-instanceid--".var_export($eledata->instanceid,true));
            $logger->info(__FILE__.__LINE__."--addEle-eledata-filterrelation--".var_export($eledata->content->filterrelation,true));
            $logger->info(__FILE__.__LINE__."--addEle-eledata-filtervalue--".var_export($eledata->content->filtervalue,true));
            //end:修改时记录下模板id，filterrelation by zuo:2016-9-18
            if(!$qr2){
                $logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
                //$arrs["result"]=0;
                return false;
            }
            else{
                $idrs = $dsql->GetArray($qr2);
                $logger->info(__FILE__.__LINE__."--addEle-elementid---".var_export($idrs,true));
				//更新快照历史
				if($snapshot != NULL){
					updateSnapshotHistory($eledata->instanceid, $idrs['id'], $curtime, $eledata->content, $snapshot, 1);
				}
                return $idrs['id'];
            }
        }
    }
    catch (Exception $e)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." exception: ".$e->GetMessage());
        //$arrs["msg"] = $e->getMessage();
        //$arrs["result"]=0;
        return false;
    }
}
/**
 *
 * 修改元素信息
 */
function updateelement($ele)
{
    global $dsql,$arrs,$arrsdata,$logger;
	try{
		removeNeedlessProperty($ele->content);//移出不需要存储到element的属性
    	$contentStr = jsonEncode4DB($ele->content);
    	$snapshot = empty($ele->snapshot) ? NULL : jsonEncode4DB($ele->snapshot);
        $issavesnapshot = empty($ele->issavesnapshot) ? false : $ele->issavesnapshot;//add 是否保存快照加个字段 by zuo:2016-9-9
        if($issavesnapshot){
            $issavesnapshot = 1;
        }else{
            $issavesnapshot = 0;
        }
        //快照模型转为非快照模型时,页面查看时仍然为快照模型, 因为$snapshot == NULL时不做快照的保存处理
        //此处处理当保存的数据为空时快照id置为NULL, 页面的处理逻辑是根据有没有快照显示为实时处理.
        $snapid2null = "";
        if($snapshot === NULL){
            $snapid2null = ", snapid = NULL";
        }
        $modelparams = empty($ele->modelparams) ? NULL : jsonEncode4DB($ele->modelparams);
		$curtime = time();
		$sql = "update ".DATABASE_NAME.".".DATABASE_ELEMENT." set modelid =".$ele->modelid.", content='".$contentStr."',
		    title='".$ele->title."',issavesnapshot=".$issavesnapshot.",type=".$ele->type.", modelparams='{$modelparams}', updatetime=".$curtime.".".$snapid2null."
		    where instanceid =".$ele->instanceid." and elementid = ".$ele->elementid;
        $logger->debug(__FILE__.__LINE__."sql is ".var_export($sql,true));
        $qr = $dsql->ExecQuery($sql);
        $logger->info(__FILE__.__LINE__."*******updateeEle-is-ture********".var_export($qr,true));
		if($qr){
            //begin:添加时记录下模板id，filterrelation by zuo:2016-9-18
            $logger->info(__FILE__.__LINE__."--updateEle-is-save-succuss-true----updatetime---".var_export($curtime,true)."--data-".date("Y/m/d h:i:sa")."----instanceid-----".var_export($ele->instanceid,true)."-----elementid----".var_export($ele->elementid,true));
            $logger->info(__FILE__.__LINE__."--updateeEle-content-filterrelation--".var_export($ele->content->filterrelation,true));
            $logger->info(__FILE__.__LINE__."--updateeEle-content-filtervalue--".var_export($ele->content->filtervalue,true));
            //end:添加时记录下模板id，filterrelation by zuo:2016-9-18
			if($snapshot != NULL){
				updateSnapshotHistory($ele->instanceid, $ele->elementid, $curtime, $ele->content, $snapshot, 1);
			}
			return true;
		}
		else{
		    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			return false;
		}
    }
    catch(Exception $e){
        $logger->error(__FILE__." func:".__FUNCTION__." exception: ".$e->GetMessage());
        return false;
    }
}


/**
 *
 * 修改实例信息
 */
function updateinstance()
{
    global $dsql,$arrs,$arrsdata;

    $sql = "update ".DATABASE_TENANT_TAGINSTANCT." set instancetype =".$arrsdata->instancetype.",title='"+$arrsdata->title+"' where id =".$arrsdata->instanceid." and tenantid=".$_SESSION["user"]->tenantid;

    try{
        $qr = $dsql->ExecQuery($sql);
        $arrs["result"]=1;
    }
    catch (Exception $e)
    {
        $arrs["result"]=0;
    }
}




/**
 *
 * 更新pin信息
 */
function updatepinrelate()
{
    global $dsql,$arrs,$arrsdata;

    $sql = "update ".DATABASE_PINRELATION." set outelementid = ".$arrsdata->outelementid.",outpinid=".$arrsdata->outpinid.", inelementid=".$arrsdata->inelementid.", inpinid = ".$arrsdata->inpinid.",updatetime = ".time()." where instanceid=".$arrsdata->instanceid;
    try
    {
        $qr = $dsql->ExecQuery($sql);
        $arrs["result"]=1;
    }
    catch(Exception $e)
    {
        $arrs["result"]=0;
    }
}


/**
 *
 * 删除pin信息
 */
function deletepinrelate()
{
    global $dsql,$arrs,$arrsdata,$logger;
	$instanceid = $_REQUEST["instanceid"];
    $sql = "delete from  ".DATABASE_PINRELATION." where instanceid = ".$instanceid;
    $qr = $dsql->ExecQuery($sql);
	if(!$qr){ 
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
    }
    else{
        $arrs["result"]=1;
    }
}
function deleterepeatpinrelate($instanceid){
    global $dsql, $logger;
	//删除重复的
	//$delsql = "delete from ".DATABASE_PINRELATION." where instanceid = {$pinrelate->instanceid} and outelementid = {$pinrelate->outelementid} and inelementid = {$pinrelate->inelementid}";
	$delsql = "delete from ".DATABASE_PINRELATION." where instanceid = {$instanceid}";
	$delqr = $dsql->ExecQuery($delsql);
	if(!$delqr){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
        return false;
	}
}

/**
 *
 * 添加元素信息
 * 参数:通过异步POST方式将数据提交到本函数存放在$arrsdata中
 * outputdata格式： {datatype:"static或dynamic",value:[{screen_name:"张三",id:123},{screen_name:"李四",id:234}]}
 * inputdata格式：{value:"screen_name",text:"查昵称"}
 */
function addpinrelate($pinrelate)
{
    global $dsql,$arrs,$arrsdata,$logger;
    if($pinrelate->outputdata->datatype == 'static'){
	    foreach($pinrelate->outputdata->value as $k=>$v){
	        $pinrelate->outputdata->value[$k]->text = rawurlencode($pinrelate->outputdata->value[$k]->text);
	        $pinrelate->outputdata->value[$k]->value = rawurlencode($pinrelate->outputdata->value[$k]->value);
	    }
    }
    $pinrelate->outputdata->text = rawurlencode($pinrelate->outputdata->text);
    $pinrelate->inputdata->text = rawurlencode($pinrelate->inputdata->text);
    
    $outputdatastr = rawurldecode(json_encode($pinrelate->outputdata));
    $inputdatastr = rawurldecode(json_encode($pinrelate->inputdata));
    $sql = "insert into ".DATABASE_PINRELATION." (instanceid,outelementid,outpinid,inelementid,inpinid,updatatime,outputdata,inputdata) values
	(".$pinrelate->instanceid.",'".$pinrelate->outelementid."',".$pinrelate->outpinid.",".$pinrelate->inelementid.",".$pinrelate->inpinid.",".time().",'{$outputdatastr}','{$inputdatastr}')";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
        //$arrs["result"]=0;
        return false;
    }
    else{
        //$arrs["result"]=1;
        return true;
    }
}

/**
 *
 * 添加一条实例信息
 * 参数:post 数据信息
 *
 */
function addinstance($inc)
{
    global $dsql,$arrs,$arrsdata,$logger;
    //$sqladdinstance  = "insert into ".DATABASE_TENANT_TAGINSTANCT." (tenantid,instancetype,navid,title,updatetime) values (".$_SESSION["user"]->tenantid.",".$arrsdata->instancetype.",".$arrsdata->navid.",'".$arrsdata->title."',".time().")";
    $sqladdinstance  = "insert into ".DATABASE_TENANT_TAGINSTANCT." (tenantid,instancetype,navid,title,updatetime) values (".$_SESSION["user"]->tenantid.",".$inc->instancetype.",".$inc->navid.",'".$inc->title."',".time().")";
    try {
        $qr = $dsql->ExecQuery($sqladdinstance);
        if(!$qr){
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqladdinstance} ".$dsql->GetError());
            return false;
        }
        else{
            if($dsql->GetAffectedRows() > 0){
                $getlastid = "select LAST_INSERT_ID() as id";
                $qr2 = $dsql->ExecQuery($getlastid);
                if(!$qr2){
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
                    return false;
                }
                else{
                    $result = $dsql->GetArray($qr2, MYSQL_ASSOC);
                    return $result["id"];
                }
            }
            else{
                return false;
            }
        }
    }
    catch (Exception $e)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." Exception： ".$e->getMessage());
        return false;
    }
}

function saveinstance(){
    global $dsql,$arrs,$arrsdata,$logger;
	$user = isset($_SESSION['user']) ? $_SESSION['user'] : Authorization::getUserFromToken();
	if($user->usertype == 2){
		$logger->error(__FILE__." func:".__FUNCTION__." 只读用户不能保存");
		$arrs['result'] = 0;
		return;
	}
    $logger->debug(__FILE__.__LINE__."dada is  ".var_export($arrsdata,true));
    if(count($arrsdata->delincids) > 0){
        $delids = implode(',',$arrsdata->delincids);
		//根据instanceid, 从element表中 查出对应的elementid在mergedata中删除
		$instancesql = "select instanceid, modelid, elementid from ".DATABASE_ELEMENT." where instanceid in ({$delids})";
		$qr = $dsql->ExecQuery($instancesql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$instancesql} ".$instancesql->GetError());
			//$arrs["result"]=0;
			return false;
		}
		else{
			$eleid = array(); //要删除的 eleid
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
				$eleid[] = $result["elementid"]; 
			}
			//删掉合并字段的 已删除的 elements id
			if(isset($arrsdata->mergeinfo) && !empty($arrsdata->mergeinfo->mergedata) > 0){
				foreach($arrsdata->mergeinfo->mergedata as $mitem){ 
					foreach($mitem as $ti=>$titem){
						foreach($titem as $ki=>$keleid){
							if(in_array($keleid, $eleid)){
								unset($ki);
							}
						}
						$mitem->$ti = $titem;
					}
				}
			}
		}
        $delr = delinstance($delids);
        if($delr === false){
            $logger->error(__FILE__." func:".__FUNCTION__." delinstance return false");
            $arrs['result'] = 0;
            return;
        }
        //comment out until snapshot update feature is ready
        foreach($arrsdata->delincids as $incid){
        	$res = deleteSnapshotSchedule($incid);
        	if(!$res['result']){
        		$arrs['result'] = 0;
        		$arrs['msg'] = $res['msg'];
        		return;
        	}
        	$res = disableEventHistory($incid);
        	if(!$res['result']){
        		$arrs['result'] = 0;
        		$arrs['msg'] = $res['msg'];
        		return;
        	}
        }
    }
    $dsql->safeCheck = false;//不检查sql，sql的内容是由jsonencode出来的
    //新增的
    if(count($arrsdata->newinc) > 0){
        foreach($arrsdata->newinc as $k => $v){
            unset($temp_inc);
            if($v->instancetype == 2 || $v->instancetype == 3){//联动实例 || 叠加分析
                $temp_inc = array("oldinstanceid"=> $v->instanceid,"newinstanceid"=>'', "elements"=>array());
                if($v->instanceid > 0){//修改，将旧数据删除
                    /*if(delelementbyincid($v->instanceid) === false){
                        $arrs['result'] = 0;
                        return;
                    }*/
                	$elementids = array();
                	foreach ($v->elements as $ei => $ele){
                		$elementids[] = $ele->elementid;
                	}
                	if(!empty($elementids)){
                		//删除不存在的
                		$sqlids = implode(",", $elementids);
                		$sqldelele = "delete from ".DATABASE_ELEMENT." where instanceid = {$v->instanceid} and elementid not in ({$sqlids})";
                		$deleleqr = $dsql->ExecQuery($sqldelele);
                		if(!$deleleqr){
                			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqldelele} error:".$dsql->GetError());
                			$arrs['result'] = 0;
                        	return;
                		}
                	}
                }
                else{//x新增实例
                    $incid = addinstance($v);
                    if($incid === false){
                        $arrs['result'] = 0;
                        return;
                    }
                    else{
                        $v->instanceid = $incid;
                    }
                }
                $temp_inc['newinstanceid'] = $v->instanceid;
                //addelement
                foreach ($v->elements as $ei => $ele){
                    $temp_ele = array("oldid"=>$ele->elementid,'newid'=>'');
					$modelparams = array();
					if(isset($ele->modelname)){
						$modelparams["modelname"] = $ele->modelname;
					}
					if(isset($ele->referencedata)){
						$modelparams["referencedata"] = $ele->referencedata;
					}
					if(isset($ele->secondaryyaxis)){
						$modelparams["secondaryyaxis"] = $ele->secondaryyaxis;
					}
					if(isset($ele->referencedataratio)){
						$modelparams["referencedataratio"] = $ele->referencedataratio;
					}
					if(isset($v->xcombined)){
						$modelparams["xcombined"] = $v->xcombined;
					}
					if(isset($v->columnstacking)){
						$modelparams["columnstacking"] = $v->columnstacking;
					}
					if(isset($v->xzreverse)){
						$modelparams["xzreverse"] = $v->xzreverse;
					}
					if(isset($ele->subInstanceType)){
						$modelparams["subInstanceType"] = $ele->subInstanceType;
					}
					if(isset($ele->overlayindex)){
						$modelparams["overlayindex"] = $ele->overlayindex;
					}

					$ele->modelparams = $modelparams;
                    $ele->instanceid = $v->instanceid;
                    if($ele->elementid > 0){//已存在的
	                    if(updateelement($ele) === false){
	                        $arrs['result'] = 0;
	                        return;
	                    }
	                    $eleid = $ele->elementid;
                    }
                    else{//新增
	                    $eleid = addelement($ele);
	                    if($eleid === false){
	                        $arrs['result'] = 0;
	                        return;
	                    }
	                    else{
	                        $ele->elementid = $eleid;
	                    }
                    }
                    $temp_ele['newid'] = $ele->elementid;
                    $temp_inc['elements'][] = $temp_ele;
                    //更新filtervalue中保存的outelementid
                    foreach ($v->elements as $upei => $upele){
		                if($upele->elementid != $ele->elementid && !empty($upele->content->filtervalue)){
		                	$needupdate = false;
		                	for($nei=0; $nei<count($upele->content->filtervalue); $nei++){
		                		if(isset($upele->content->filtervalue[$nei]->fieldvalue->outelementid)
		                		    && $temp_ele['oldid'] == $upele->content->filtervalue[$nei]->fieldvalue->outelementid){
		                			$upele->content->filtervalue[$nei]->fieldvalue->outelementid = $temp_ele['newid'];
		                			$needupdate = true;
		                		}
		                	}
		                	if($needupdate){
		                		updateelement($upele);
		                	}
		                }
                    }
					if(isset($v->pinrelation) && count($v->pinrelation) > 0){
						foreach ($v->pinrelation as $pi => $pin){
							$v->pinrelation[$pi]->instanceid = $v->instanceid;
							if($v->pinrelation[$pi]->outelementid == $temp_ele['oldid']){
								$v->pinrelation[$pi]->outelementid = $eleid;
							}
							if($v->pinrelation[$pi]->inelementid == $temp_ele['oldid']){
								$v->pinrelation[$pi]->inelementid = $eleid;
							}
						}
					}
                }
				//添加前删除对应instance inelements outelements的relation, 解决页面删除realtion,保存刷新后又显示出来bug
				if(isset($v->pinrelation) && count($v->pinrelation) > 0){
					deleterepeatpinrelate($v->pinrelation[0]->instanceid);
					foreach ($v->pinrelation as $pi => $pin){
						if(addpinrelate($pin) === false){
							$arrs['result'] = 0;
							return;
						}
					}
				}
            }
            else{//普通实例
                if($v->instanceid > 0){
                    // 普通模型修改走这里
                    if(updateelement($v->elements[0]) === false){
                        $arrs['result'] = 0;
                        return;
                    }
                    $temp_inc = array("oldinstanceid"=>$v->instanceid,"newinstanceid"=>$v->instanceid, "elements"=>array());
                    $temp_ele = array("oldid"=>$v->elements[0]->elementid,'newid'=>$v->elements[0]->elementid);
                    $temp_inc['elements'][] = $temp_ele;
                }
                else{
                    $temp_inc = array("oldinstanceid"=>$v->instanceid, "elements"=>array());
                    $incid = addinstance($v);
                    if($incid === false){
                        $arrs['result'] = 0;
                        return;
                    }
                    else{
                        $v->instanceid = $incid;
                        $temp_inc['newinstanceid'] = $v->instanceid;
                        $v->elements[0]->instanceid = $incid;
                        $eleid = addelement($v->elements[0]);  //需要更新mergefield信息
                        if($eleid === false){
                            $arrs['result'] = 0;
                            return;
                        }
						//更新合并字段的 elements id
						if(isset($arrsdata->mergeinfo) && !empty($arrsdata->mergeinfo->mergedata)){
							foreach($arrsdata->mergeinfo->mergedata as &$mitem){ //引用
								foreach($mitem as $ti=>$titem){
									foreach($titem as &$keleid){
										if($keleid == $v->elements[0]->elementid){
											$keleid = $eleid;
										}
									}
									$mitem->$ti = $titem;
								}
							}
						}
                        $temp_ele = array("oldid"=>$v->elements[0]->elementid,'newid'=>$eleid);
                        $temp_inc['elements'][] = $temp_ele;
                   }
                }
            }
            //comment out until snapshot update feature is ready
            if(!empty($v->schedparams)){
            	$v->schedparams->instanceid = $v->instanceid;
            	$res = updateSnapshotSchedule($v->schedparams);
            	if(!$res['result']){
            		$arrs['result'] = 0;
            		$arrs['msg'] = $res['msg'];
            		return;
            	}
            }
            $arrs['data'][] = $temp_inc;
        }
    }
	if(isset($arrsdata->mergeinfo)){
		$arrsdata->mergedata = $arrsdata->mergeinfo->mergedata;
		$arrsdata->navid = $arrsdata->mergeinfo->navid;
		updatemergedata();

		$arrsdata->mergesched = $arrsdata->mergeinfo->mergesched;
		updatemergesched();
		$arrs['mergesched'] = $arrsdata->mergesched;
		$arrs['mergedata'] = $arrsdata->mergeinfo->mergedata;
	}
    $arrs['result'] = 1;
}
function updateschedule(){
    global $dsql,$arrs,$arrsdata,$logger;
	$res = updateSnapshotSchedule($arrsdata->schedparams);
	if(!$res['result']){
		$arrs['result'] = 0;
		$arrs['msg'] = $res['msg'];
	}
	else{
		$arrs['result'] = 1;
	}
}

/**
 * 
 * 保存页面html
 */
function savepage(){
    global $dsql,$arrs,$arrsdata,$logger;
    $navid = $_POST['navid'];
    $htmldata = $_POST['htmldata'];
    $shr = savemultimodel($navid,$htmldata);
    if($shr === false){
        $arrs['result'] = 0;
        return;
    }
    $arrs['result'] = 1;
}
//更新对应导航的mergedata字段
function updatemergedata(){
	global $dsql,$arrs,$arrsdata,$logger;
	$sql="update ".DATABASE_NAME.".".DATABASE_CUSTOMER_NAVIGATE." set mergedata='".json_encode($arrsdata->mergedata)."' where id=".$arrsdata->navid;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
	}
	else{
		$arrs["result"]=1;
	}
}
//获取对应导航的mergedata字段
function getmergedata(){
	global $dsql,$arrs,$arrsdata,$logger;
	$sql="select mergedata from ".DATABASE_CUSTOMER_NAVIGATE." where id=".$arrsdata->navid;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
	}
	else{
		$num = $dsql->GetTotalRow($qr);
		if($num > 0){
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
				$arrs = json_decode($result["mergedata"]);
			}
		}
	}
}
//更新对应导航的mergedata字段
function updatemergesched(){
	global $dsql,$arrs,$arrsdata,$logger;
	$sql="update ".DATABASE_NAME.".".DATABASE_CUSTOMER_NAVIGATE." set mergesched='".json_encode($arrsdata->mergesched)."' where id=".$arrsdata->navid;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
	}
	else{
		$arrs["result"]=1;
	}
}
function getmergesched(){
	global $dsql,$arrs,$arrsdata,$logger;
	$sql="select mergesched from ".DATABASE_CUSTOMER_NAVIGATE." where id=".$arrsdata->navid;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
	}
	else{
		$num = $dsql->GetTotalRow($qr);
		if($num > 0){
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
				$arrs = json_decode($result["mergesched"]);
			}
		}
	}
}
/**
 *
 * 设置单模型的的元素信息
 * 参数：通过post方式进行传递,
 * modelid:当前被修改的模型,
 * instanceid:实例ID,
 * elementid:元素ID,
 * content:元素条件json格式
 */
function setsinglemodel()
{
    global $dsql,$arrs,$arrsdata;

    //判断是否创建过实例信息
    if($arrsdata->instanceid==0)
    {
        $sql = "insert into ".DATABASE_TENANT_TAGINSTANCT." (tenantid,instancetype,navid,updatetime,title) values (".$_SESSION["user"]->tenantid.",1,".$arrsdata->navid.",".time().",'".$arrsdata->title."')";

        try {
            $qr = $dsql->ExecQuery($deloldemodel);
            $getlastid = "select LAST_INSERT_ID() as id";
            $qr = $dsql->ExecQuery($getlastid);
            $result = $dsql->GetArray($qrout, MYSQL_ASSOC);
            $insertelement = "insert into ".DATABASE_ELEMENT." (instanceid,modelid,content,type,title,updatetime) values(".$result["id"].",".$arrsdata->modelid.", content = '".json_encode($arrsdata->jsonata)."','".$arrsdata->title."',".time().")";

            $qrinsertelement = $dsql->ExecQuery($insertelement);

            $arrs["flag"]=1;
        }
        catch (Exception $e)
        {
            $arrs["flag"]=0;
        }
    }
    else
    {
        //判断是否更换了model,与新的json中的modelid进行对比
        if($arrsdata->content->modelid!=$arrsdata->modelid)
        {
            $deloldemodel ="delete from ".DATABASE_ELEMENT." elementid=".$arrsdata->elementid." and modelid = ".$arrsdata->modelid;
            $qr = $dsql->ExecQuery($deloldemodel);

            if(!$qr)
            {
                	
            }
            else
            {
                //删除旧的元素增加一条新的model的元素记录
                $addnewmodel="insert into ".DATABASE_ELEMENT." (instanceid,modelid,content,updatetime) values(".$arrsdata->instanceid.",".$arrsdata->modelid.",".json_encode($arrsdata->content).",".time().")";
                $qr = $dsql->ExecQuery($addnewmodel);
                try {
                    $arrs["flag"]=1;
                }
                catch (Exception $e)
                {
                    $arrs["flag"]=0;
                }
            }
        }
        else
        {
            $sql = "update ".DATABASE_ELEMENT." set content ='".json_encode($arrsdata->content)."' where elementid = ".$arrsdata->elementid." and instanceid = ".$arrsdata->instanceid;
            try{
                $qr = $dsql->ExecQuery($sql);
            }
            catch (Exception $e)
            {
                $arrs["flag"]=0;
            }
        }
    }


}

/**
 *
 * 更新导航信息
 * 参数:通过异步POST方式将数据提交到本函数存放在$arrsdata中
 */
function updatenavigate()
{
	global $dsql,$arrs,$arrsdata,$logger;
	if($arrsdata->ishomepage==0){
		$sql = "update ".DATABASE_CUSTOMER_NAVIGATE." set label='".$arrsdata->label."',pagetitle='".$arrsdata->pagetitle."',pagetype=".$arrsdata->pagetype.",parentid=".$arrsdata->parentid.",updatetime=".time().",ishomepage=".$arrsdata->ishomepage.", icon='".$arrsdata->icon."' where id=".$arrsdata->id ;
		//echo $sql;
		$qr = $dsql->ExecQuery($sql);
		if (!$qr){
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
		$sql="update ".DATABASE_CUSTOMER_NAVIGATE." set ishomepage=0 where level=".$arrsdata->level." and userid=".$_SESSION["user"]->getuserid();

		$sql2 = "update ".DATABASE_CUSTOMER_NAVIGATE." set label='".$arrsdata->label."',pagetitle='".$arrsdata->pagetitle."',pagetype=".$arrsdata->pagetype.",parentid=".$arrsdata->parentid.",updatetime=".time().",ishomepage=".$arrsdata->ishomepage. ", icon='".$arrsdata->icon."' where id=".$arrsdata->id;
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$qr2 = $dsql->ExecQuery($sql2);
			if (!$qr2)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql2} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else
			{
				$arrs["flag"]=1;
			}

		}
	}

}
function updatenavtab(){
	global $dsql,$arrs,$arrsdata,$logger;
	if($arrsdata->defaulttab == 1){
		$sql="update ".DATABASE_CUSTOMER_NAVIGATE." set ishomepage =0 where parentid=".$arrsdata->parentid;
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
	}

	$sql = "update ".DATABASE_CUSTOMER_NAVIGATE." set label ='".$arrsdata->tabname."',pagetype=".$arrsdata->pagetype.",parentid=".$arrsdata->parentid.",updatetime=".time().",ishomepage =".$arrsdata->defaulttab." where id=".$arrsdata->id ;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$arrs["flag"]=1;
	}
}
function iscontainchild(){
	global $dsql,$arrs;
	$delid = $_GET["id"];
	$arrid = explode(",", $delid);
	foreach($arrid as $k=>$v){
		$sql = "select * from `customer_navigate` where `parentid` = ".$v;
		$resqr = $dsql->ExecQuery($sql); 
		if($dsql->GetTotalRow($resqr) > 0){
			$sql2 = "select label from customer_navigate where id = ".$v;
			$resqr2 = $dsql->ExecQuery($sql2);
			$reslabel = $dsql->GetArray($resqr2, MYSQL_ASSOC);
			$arrs[] = $reslabel["label"];
		}
	}
}
//删除页内卡片
function deletenavtab($ID){
	global $dsql, $arrs, $logger;
	$arrids = explode(",", $ID);
	if($arrids > 0){
		foreach($arrids as $ai=>$aitem){
			$sqltab = "select filepath,parentid from ".DATABASE_CUSTOMER_NAVIGATE." where id = ".$aitem."";
			$qrtab = $dsql->ExecQuery($sqltab);
			if(!$qrtab){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltab} ".$dsql->GetError());
				$arrs["flag"] = 0;
			}
			else{
				$result = $dsql->GetArray($qrtab, MYSQL_ASSOC);
				$sqltabcount = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where parentid = ".$result['parentid']."";
				$qrtabcount = $dsql->ExecQuery($sqltabcount);
				$tabcount = $dsql->GetTotalRow($qrtabcount);
				if($tabcount == 1){
					$sqlp = "select filepath from ".DATABASE_CUSTOMER_NAVIGATE." where id = ".$result['parentid']."";
					$qrp = $dsql->ExecQuery($sqlp);
					if(!$qrp){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlp} ".$dsql->GetError());
						$arrs["flag"] = 0;
					}
					else{
						$resp = $dsql->GetArray($qrp, MYSQL_ASSOC);
						//最后一个卡片的页面结构复制给父导航
						$sRealPath = realpath('../');
						$lastfile = $sRealPath."/".$result['filepath'];
						$pfile = $sRealPath."/".$resp['filepath'];
						if(!copy($lastfile, $pfile)){
							$logger->error(__FILE__." func:".__FUNCTION__." failed to copy lastfile ".$lastfile." pfile ".$pfile."");
						}
						//更新实例所属的导航
						$upinstance = "update ".DATABASE_TENANT_TAGINSTANCT." set navid = ".$result['parentid']." where navid = ".$aitem."";
						$qri = $dsql->ExecQuery($upinstance);
						if(!$qri){
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upinstance} ".$dsql->GetError());
							$arrs["flag"]=0;
						}
						else{
							$arrs["flag"]=1;
						}

						//删除导航表最后卡片记录
						$sql = "delete from ".DATABASE_CUSTOMER_NAVIGATE." where id = ".$aitem."";
						$dlqr = $dsql->ExecQuery($sql);
						if(!$dlqr){
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$dlqr} ".$dsql->GetError());
							$arrs["flag"]=0;
						}
						else{
							$arrs["flag"]=1;
						}
						//删除最后一个卡片的页面结构
						if(file_exists($sRealPath."/".$result['filepath'])){
							unlink($sRealPath."/".$result['filepath']);
						}
					}
				}
				else{
					deletenavigate($aitem);
				}
			}
		}
	}
}
/**
 *
 * 删除导航信息,删除导航信息的同时删除与导航相关联的实例和元素信息
 * @param  $ID 导航ID,格式为(1,2,3,4,5,6)批量删除时会有多个导航ID
 * @throws Exception
 */
function deletenavigate($ID)
{
    global $dsql,$arrs;

    //删除导航表中的记录
    $sql = "delete from ".DATABASE_CUSTOMER_NAVIGATE." where id in(".$ID.")";
    $arrid = explode(",", $ID);

    if(count($arrid)>0)
    {
        foreach($arrid as $k=>$v)
        {
            //获取某导航的实例信息
            $sqlgetinstance = "select * from  ".DATABASE_TENANT_TAGINSTANCT." where navid = ".$arrid[$k];
            //$sqldelinstance ="delete from ".DATABASE_TENANT_TAGINSTANCT." where navid = ".$arrid[$k];
            $objinstance = $dsql->ExecQuery($sqlgetinstance);
            //判断是否存在实例
            if($dsql->GetTotalRow($objinstance)>0)
            {
                while ($result = $dsql->GetArray($objinstance, MYSQL_ASSOC))
                {
                    //删除元素信息
                    $sqldelelm = "delete from ".DATABASE_ELEMENT." where instanceid = ".$result["id"];
                    try{
                        $delelem = $dsql->ExecQuery($sqldelelm);
                    }
                    catch(Expection $e)
                    {
                        $arrs["flag"]=0;
                    }
                }
            }
        }
		//获取要删除导航的filepath 删除文件
		$sqlgetfilepath = "select filepath from ".DATABASE_CUSTOMER_NAVIGATE." where id in (".$ID.")";
		$qrfilepath = $dsql->ExecQuery($sqlgetfilepath);
		if($dsql->GetTotalRow($qrfilepath)>0){
			$filepathArr = array(); //存储要删除的文件路径
			while($res = $dsql->GetArray($qrfilepath, MYSQL_ASSOC)){
				$filepathArr[] = $res["filepath"];
			}
		}
        $sRealPath = realpath('../');
		foreach($filepathArr as $key=>$value){
			if(file_exists($sRealPath."/".$value)){
				unlink($sRealPath."/".$value);
			}
		}
    }
    //删除实例表中的数据
    $sqldelinstance ="delete from ".DATABASE_TENANT_TAGINSTANCT." where navid in (".$ID.")";
    try{
        $dsql->ExecQuery($sqldelinstance);    //删除所有实例信息
        $dsql->ExecQuery($sql);               //删除所有导航信息
        $arrs["flag"]=1;
    }
    catch(Exception $e)
    {
        throw new Exception(TYPE_PAGE."- deletenavigate()-".$sql."-".mysql_error());
        $arrs["flag"]=0;
    }
}//end function deletenavigate

/**
 *
 * 当导航是多模型是存储多模型的div存储位置;当前模型的homepageflag的值为1是存储为index.shtml
 * 成功这保存为制定的shtml文件
 */
function savemultimodel($navid,$htmldata)
{
    global $dsql,$arrs,$logger;
    try{
        // = "delete from ".DATABASE_CUSTOMER_NAVIGATE." where id in(".$ID.")";
        //$navid = $_REQUEST['navid'];
        //$htmldata = $_REQUEST['htmldata'];
        if(empty($navid)){
            $logger->error(__FILE__." func:".__FUNCTION__." params is null： navid:{$navid},htmldata:{$htmldata}");
            //$arrs['result'] = 0;
            return false;
        }
        $htmldata = empty($htmldata) ? "" : $htmldata;
        $htmldata = stripslashes($htmldata);//去除反斜杠
        $sRealPath = realpath('../');
        //$filepath = substr( $sRealPath, 0, strrpos( $sRealPath,'\\'));
        $dir = $sRealPath."/tenant/".$_SESSION["user"]->tenantid;
        if(!is_dir($sRealPath."/tenant")){
            mkdir($sRealPath."/tenant", 0777);
        }
        if(!is_dir($dir)){
            mkdir($dir, 0777);
        }
        if(disk_free_space($dir) > 1024){
            $filename=$dir."/".$navid.".html";
            $fp = fopen($filename, 'w');//写入方式打开,如果文件不存在则尝试创建之。 
            fwrite($fp, $htmldata);
            fclose($fp);
            return true;
        }
        else{
            $logger->error(__FILE__." func:".__FUNCTION__." error: disk space is not enough! ");
            return false;
        }
    }
    catch (Exception $ex){
        $logger->error(__FILE__." func:".__FUNCTION__." error： ".$ex->getMessage());
        //$arrs['result'] = 0;
        return false;
    }
    //file_put_contents("a.shtml",);
}//end function savemulitmodel()

/**
 *
 * 判断是否存在实例信息
 * @param unknown_type $nid  导航id
 */
function checkinstance($nid)
{
    global $dsql,$arrs;

    $sql = "select * from ".DATABASE_TENANT_TAGINSTANCT." where navid=".$nid;

    $qr = $dsql->ExecQuery($sql);

    if(!$qr)
    {
    }
    else
    {
        if($dsql->GetTotalRow($qr)>0)
        {
            return true;
        }
        else
        {
            return false;
        }

    }

    return true;
}// end function checkinstance()


/**
 *
 * 检查用户是否已经设置了默认页面
 * @param unknown_type $ishomepage
 */
function checkhomepage()
{
    global $dsql,$arrs;
	$navlevel = $_GET["navlevel"];

    $sql = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where userid=".$_SESSION["user"]->getuserid()." and tenantid =".$_SESSION["user"]->tenantid." and level = ".$navlevel." and ishomepage=1";
    $qr = $dsql->ExecQuery($sql);
    if($dsql->GetTotalRow($qr)>0)
    {
        $arrs["flag"]=1;
    }
    else
    {
        $arrs["flag"]=0;
    }

}//end function checkhomepage
function checkdefaulttab(){
	global $dsql, $arrs;
	$pid = $_GET["parentid"];
	$navid = isset($_REQUEST['navid']) ? $_REQUEST['navid'] : "";
	$navidcond = "";
	if($navid != ""){
		$navidcond = " and id != ".$navid."";
	}
	$sql = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where parentid = ".$pid." and ishomepage=1 ".$navidcond."";
	$qr = $dsql->ExecQuery($sql);
	if($dsql->GetTotalRow($qr) > 0){
		$arrs['flag'] = 1;
	}
	else{
		$arrs['flag'] = 0;
	}
}
/**
 *
 * 删除文件
 * @param  $navid 导航ID
 * @throws Exception
 */
function deltenantfile($navid)
{
    $sRealPath = realpath('./');
    $sSelfPath = $_SERVER['PHP_SELF'] ;
    $filepath = substr( $sRealPath, 0, strrpos( $sRealPath,'\\'));

    $filename=$filepath."/tenant/".$navid."/";

    if(is_dir($filename))
    {

    }

}

/**
 *判断某个值在数组中是否存在
 *
 * 存在返回：true
 * 不存在返回:false
 */
function checkvalueisexist($data,$arrdata)
{
    for($m=0;$m<count($arrdata);$m++)
    {
        if($data==$arrdata[$m])
        {
            return true;
        }
    }

    return false;
}
/**
 * 
 * 获取模型所有可用的show
 */
function getmodelshow(){
    global $allshow,$arrs,$logger;
    $modelid = $_REQUEST["modelid"];
    if(!isset($modelid)){
        $logger->error(__FILE__." func:".__FUNCTION__." params modelid is null： ");
        $arr['result'] = 0;
    }
    else{
        foreach($allshow as $key => $value){
            if($key == $modelid){
                $arrs = array_merge($arrs,$value);
            }
        }
    }
}
function updatenavorder($srcid)
{
	global $dsql,$arrs,$arrsdata,$logger;
	
	$arrsrcid =explode(",", $srcid);//存储旧的ID	
	//$arrnewid = explode("-", $newid);//存储新的ID
	//
	if($arrsrcid!=""&&count($arrsrcid)>0&&$arrsrcid!=null)
	{
		foreach($arrsrcid as $k=>$v)
		{
			$arrnav = explode("_",$v);
			if($arrnav!=""&&count($arrnav)>0&&$arrnav!=null)
			{
				$sql = "update  ".DATABASE_CUSTOMER_NAVIGATE." set orderid = ".$arrnav[2].",parentid=".$arrnav[1]." where id=".$arrnav[0];
				$qr = $dsql->ExecQuery($sql);
				if (!$qr)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["result"]=0;
				}
			}
			
		}
		$arrs["result"]=1;
	}
}
/**
 * 获取某一租户的导航信息
 * @param $id
 */
function getnavbytidall($id)
{
    global  $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	$where = array();
	//导航名称
	if(isset($_GET["label"])){
		$slabel = $_GET["label"]; 
		$pos = strpos($slabel, '*');
		if($pos === false){
			$where[] =  "label = '".$slabel."'";
		}
		else{
			$slabel = str_replace("*", "%", $slabel);       
			$where[] =  "label like '".$slabel."'";
		}
	}
	//级别
	if(isset($_GET["level"])){
		$slevel = isset($_GET["level"]) ? $_GET["level"] : 0;
		$where[] = "level = ".$slevel."";
	}
	//一级导航名称
	if(isset($_GET["level1name"])){
		$slevel1name = $_GET["level1name"]; 
		$pos = strpos($slevel1name, '*');
		$innerwhere = "";
		if($pos === false){
			$innerwhere = "label = '".$slevel1name."'";
		}
		else{
			$slevel1name = str_replace("*", "%", $slevel1name);       
			$innerwhere =  "label like '".$slevel1name."'";
		}
		$psql = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where ".$innerwhere." and level=1";
		$pqr = $dsql->ExecQuery($psql);
		if(!$pqr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["result"]=0;
		}
		else{
			if($dsql->GetTotalRow($pqr) > 0){
				$idarr = array();
				while($res = $dsql->GetArray($pqr, MYSQL_ASSOC)){
					$idarr[] = $res["id"];
				}
				$idstr = implode(",", $idarr);
				$where[] = "parentid in (".$idstr.")";
			}
		}
	}
	//更新时间
	if(isset($_GET["updatetimestart"]) && isset($_GET["updatetimeend"])){
		$utstart = $_GET["updatetimestart"];
		$utend = $_GET["updatetimeend"];
		$where[] = "updatetime > ".$utstart." AND updatetime  < ".$utend."";
	}
	else if(isset($_GET["updatetimestart"])){
		$etstart = $_GET["updatetimestart"];
		$where[] = "updatetime > ".$etstart."";
	}
	else if(isset($_GET["updatetimeend"])){
		$etend = $_GET["updatetimeend"];
		$where[] = "updatetime < ".$etend."";
	}
	$where[] = "userid=".$_SESSION["user"]->getuserid()."";
	$where[] = "level!=3";
	$wherestr = "";
	if(count($where) > 0){
		$wherestr = " where ".implode(" and ", $where);
	}

	   //计算limit的起始位置
    $limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
    $num=0;
    $sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE." ".$wherestr."";
    $sql= "select * from ".DATABASE_CUSTOMER_NAVIGATE." ".$wherestr." order by orderid desc, updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
    $qr = $dsql->ExecQuery($sqlcount);

    if(!$qr){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
    }
    else{
        while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
            $arrs["totalcount"] = $result["cnt"];
        }

        if($arrs["totalcount"]> 0){
            $qr = $dsql->ExecQuery($sql);
            if(!$qr){
                $logger->error(TASKMANAGER." - getbytid2() sqlerror:".$sql." - ".$dsql->GetError());
            }
            else{
                $temp_arr = array();
                while ($r = $dsql->GetArray($qr, MYSQL_ASSOC)){
                    $tmp_arr['id'] = $r["id"];
                    $tmp_arr['level'] = $r["level"];
                    $tmp_arr['parentid'] = $r["parentid"];
                    $tmp_arr['modelid'] = $r["modelid"];
                    $tmp_arr['pagetype'] = $r["pagetype"];
                    $tmp_arr['orderid'] = $r["orderid"];
                    $tmp_arr['pagetitle'] = $r["pagetitle"];
                    $tmp_arr['ishomepage'] = $r["ishomepage"];
                    $tmp_arr['label'] = $r["label"];
                    $tmp_arr['updatetime'] = date(('Y-m-d G:i:s'),$r["updatetime"]);
                    $tmp_arr['icon'] = $r["icon"];
                    $arrs[CHILDS][] = $tmp_arr;
                }
            }
        }
    }
    //echo json_encode($result);
}//end function

/*
 * 根据导航ID获取导航详细信息
 *
 */
function getnavbytid()
{
    global $dsql,$arrs,$logger;
    $num=0;

    $sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where userid=".$_SESSION["user"]->getuserid()." and parentid=0 order by parentid";
    $qr = $dsql->ExecQuery($sql);

    if(!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
                $arrs["result"]=0;
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        $arrs["totalcount"]=$num;

        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
				$temp_arr = array();
                $temp_arr["id"] = $result["id"];
                $temp_arr["label"] = $result["label"];
                $temp_arr["tenantid"] = $result["tenantid"];
                $temp_arr["pagetitle"] = $result["pagetitle"];
                $temp_arr["level"] = $result["level"];
                $temp_arr["parentid"] = $result["parentid"];
                $temp_arr["pagetype"] = $result["pagetype"];
                $temp_arr["orderid"] = $result["orderid"];
                $temp_arr["modelid"] = $result["modelid"];
                $temp_arr["homepage"] = $result["ishomepage"];
                $temp_arr["updatetime"] = $result["updatetime"];

                $arrs[CHILDS][] = $temp_arr;
            }
        }
    }
}//end function

//根据mid 获取 微博ID
function geweibotidbymid(){
    global $arrs,$logger;
    $mid = $_REQUEST['mid'];
    $sqlconn = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
    $sql="select id from ".DATABASE_WEIBO."  where mid='{$mid}'";
    $qr = $sqlconn->ExecQuery($sql);
    if(!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$sqlconn->GetError());
        $arrs["result"]=0;
    }
    else{
        $rs = $sqlconn->GetArray($qr);
        if(!empty($rs) && !empty($rs['id'])){
            $arrs["result"]=1;
            $arrs["id"]=$rs['id'];    
        }
        else{
            $arrs["result"]=0;
        }
    }
}
function getnavinfobyid($id){
    global $dsql, $logger;
	$result = array();
	$userid = $_SESSION["user"]->getuserid();
    $sql="select a.id, a.label, a.level, a.parentid, a.filepath from ".DATABASE_CUSTOMER_NAVIGATE." as a where id=".$id." and userid=".$userid."";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else{
        $num = $dsql->GetTotalRow($qr);
        if($num>0){
			$result = $dsql->GetArray($qr, MYSQL_ASSOC);
        }
    }
	return $result;
}
//根据Id获取模块信息
function getnavbyid($id)
{
    global $dsql,$arrs, $logger;
    $num=0;
    $sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where id=".$id;
    $qr = $dsql->ExecQuery($sql);

    if(!$qr){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["result"]=0;
    }
    else{
        $num = $dsql->GetTotalRow($qr);
        $arrs["totalcount"]=$num;

        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
				$temp_arr = array();
                $temp_arr["id"] = $result["id"];
                $temp_arr["label"] = $result["label"];
                $temp_arr["level"] = $result["level"];
                $temp_arr["tenantid"] = $result["tenantid"];
                $temp_arr["pagetitle"] = $result["pagetitle"];
                $temp_arr["pagetype"] = $result["pagetype"];
                $temp_arr["parentid"] = $result["parentid"];
                $temp_arr["ishomepage"] = $result["ishomepage"];
                $temp_arr["defaulttab"] = $result["ishomepage"];
                $temp_arr["tabname"] = $result["label"];
                $temp_arr["updatetime"] = $result["updatetime"];

                $arrs[CHILDS][] = $temp_arr;
            }
            $arrs["result"]=1;
        }
    }
}
/*
 * 根据导航id获取对应导航下的tab*/
function gettabbyparentid(){
    global $dsql,$arrs,$arrsdata,$logger;
	$pid = isset($_GET['navid']) ? $_GET['navid'] : -1;
	$limitcond = "";
	if(isset($_GET['page']) || isset($_GET['pagesize'])){
		$spage = isset($_GET['page']) ? $_GET['page'] : 1;
		$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : 10;
		$limit_cursor = ($spage - 1) * $pagesize;
		$limitcond = " limit ".$limit_cursor.",".$pagesize."";
	}
	$sqlcount = "select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where parentid = ".$pid."";
	$sql = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where parentid = ".$pid." ".$limitcond."";
	$qrcount = $dsql->ExecQuery($sqlcount);
	if(!$qrcount){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlcount} ".$dsql->GetError());
		$arrs["result"]=0;
	}
	else{
		$totalres = $dsql->GetArray($qrcount, MYSQL_ASSOC);
		if($totalres['totalcount'] > 0){
			$arrs['totalcount'] = $totalres['totalcount'];
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["result"]=0;
			}
			else{
				while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
					$temp_arr = array();
					$temp_arr["id"] = $result["id"];
					$temp_arr["level"] = $result["level"];
					$temp_arr["tenantid"] = $result["tenantid"];
					$temp_arr["pagetype"] = $result["pagetype"];
					$temp_arr["parentid"] = $result["parentid"];
					$temp_arr["tabname"] = $result["label"];
					$temp_arr["filepath"] = $result["filepath"];
					$temp_arr["defaulttab"] = $result["ishomepage"];
					$temp_arr["updatetime"] = date('Y-m-d G:i:s',$result["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["result"]=1;
			}
		}
	}
}

function getnavhtml(){
	global $dsql,$arrs, $logger;
	$navid = $_GET['navid'];
	//$tenantid = $_SESSION['user']->tenantid;
	$sql = "select filepath from ".DATABASE_CUSTOMER_NAVIGATE." where id={$navid}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." ".__FUNCTION__." sql:{$sql} error:".$dsql->GetError());
		echo '';
		exit;
	}	
	else{
		$rs = $dsql->GetArray($qr);
		$filepath = $rs['filepath'];
		if(!empty($filepath)){
			$realfp = realpath("../".$filepath);
			$htm = file_get_contents($realfp);
			if(!empty($htm)){
				echo $htm;
			}
			else{
				echo '';
			}
			exit;
		}
		else{
			echo '';
			exit;
		}
	}
}

    $arg_search_page = isset($_REQUEST[ARG_SEARCH_CURRPAGE]) ? $_REQUEST[ARG_SEARCH_CURRPAGE] : 1;
    $arg_pagesize = isset($_REQUEST[ARG_SEARCH_PAGESIZE]) ? $_REQUEST[ARG_SEARCH_PAGESIZE] : 10;
    $arg_id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : 0;
    $arg_navid = isset($_REQUEST["navid"]) ? $_REQUEST["navid"] : 0;
    $arg_channelid = isset($_REQUEST["channelid"]) ? $_REQUEST["channelid"] : 0;
    $arg_name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : null;
    $arg_type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : null;
    $arg_typeid = isset($_REQUEST["typeid"]) ? $_REQUEST["typeid"] : null;
    $arg_modelnavtype = isset($_REQUEST[TYPE_MODELNAVTYPE]) ? $_REQUEST[TYPE_MODELNAVTYPE] : '';
    $arg_modelid = isset($_REQUEST["modelid"]) ? $_REQUEST["modelid"] : null;
    $arg_modeltype = isset($_REQUEST["modeltype"]) ? $_REQUEST["modeltype"] : null;
    $arg_instanceid = isset($_REQUEST["instanceid"]) ? $_REQUEST["instanceid"] : null;
    $arg_elementid = isset($_REQUEST["elementid"]) ? $_REQUEST["elementid"] : null;
    $arg_elementtype = isset($_REQUEST["elementtype"]) ? $_REQUEST["elementtype"] : null;

if (isset($HTTP_RAW_POST_DATA))
{
    //if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
    //{
    global $arrsdata;
    //$arrsdata = $GLOBALS['HTTP_RAW_POST_DATA'];
    $arrsdata = json_decode($HTTP_RAW_POST_DATA);
    if(!isset($arg_type)){
        $arg_type = $arrsdata->type;
    }
    //$arg_elementid = $arrsdata->elementid;
    //$arg_instanceid = $arrsdata->instanceid;

    //}
}
switch ($arg_type)
{
    case TYPE_ADDNAVIGATE:
        addnavigate();
        break;
	case TYPE_ADDNAVTAB:
		addnavtab();
		break;
    case TYPE_GETLEFTMENU:
        getleftmenu();
        break;
    case TYPE_GETELEMENTS:
    	$instanceid = $_REQUEST["instanceid"];
		$elementtype= $_REQUEST["elementtype"];
		$snapid = 0;
		if(isset($_REQUEST["snapid"])){
			$snapid = $_REQUEST["snapid"];
		}
        $arrs = getelements($instanceid, $elementtype, true, $snapid);//在commonFun.php中
        break;
    case TYPE_CHECKHOMEPAGE:
        checkhomepage();
        break;
	case TYPE_CHECKDEFAULTTAB:
		checkdefaulttab();
		break;
    case TYPE_DELELEMENT:
        delelement();
        break;
    case TYPE_GETINSTANCE:
        getinstance();
        break;
    case TYPE_UPDATEELEMENT:
        updateelement();
        break;
	case TYPE_ADDELEMENT:
		addelement();
		break;
    case TYPE_UPDATEINSTANCE:
        updateinstance();
        break;
    case TYPE_UPDATEPINRELATE:
        updatepinrelate();
        break;
    case TYPE_DELETEPINRELATE:
        deletepinrelate();
        break;
    case TYPE_GETPLATEFORM:
        getplatformmenu();
        break;
    case TYPE_GETMODELBYCHANNEL:
        getmodelbychannel();
        break;
    case TYPE_GETMODELBYTENANTID:
        getmodelbytenantid();
        break;
    case TYPE_GETMODEL:
        getmodel();
        break;
    case TYPE_GETALLMODELS:
        getallmodels();
        break;
    case TYPE_GETMODELSHOW:
        getmodelshow();
        break;
    case TYPE_GETTENANTMODELRULE:
        gettenantmodelrule();
        break;
    case TYPE_SAVEMULTIMODEL:
        savemultimodel();
        break;
    case TYPE_ADDINSTANCE:
        addinstance();
        break;
    case TYPE_GETNAVBYTIDALL:
        getnavbytidall($arg_id);
        break;
    case TYPE_GETNAVBYTID:
        getnavbytid($arg_id);
        break;
    case TYPE_GETNAVBYID:
        getnavbyid($arg_navid);
        break;
	case TYPE_GETTABBYPARENTID:
		gettabbyparentid();
		break;
	case TYPE_GETSNAPSHOTHISTORY:
		getsnapshothistory();
		break;
	case TYPE_DELETESNAPSHOTHISTORY:
		deletesnapshothistory();
		break;
	case TYPE_DELETEEVENTHISTORYHISTORY:
		deleteeventhistoryhistory();
		break;
	case TYPE_GETSNAPSHOTHISTORYBYTIME:
		getsnapshothistorybytime();
		break;
	case TYPE_GETEVENTLIST:
		geteventlist();
		break;
	case TYPE_GETEVENTLISTALL:
		geteventlistall();
		break;
	case TYPE_GETEVENTITEM:
		geteventitem();
		break;
	case TYPE_GETEVENTITEMALL:
		geteventitemall();
		break;
	case TYPE_GETEVENTHISTORYLIST:
		geteventhistorylist();
		break;
	case TYPE_GETEVENTHISTORYLISTALL:
		geteventhistorylistall();
		break;
	case TYPE_DELETEEVENTALERT:
		deleteeventalert();
		break;
	case TYPE_GETSNAPSHOTHISTORYLIST:
		getsnapshothistorylist();
		break;
	case TYPE_GETSNAPSHOTHISTORYLISTALL:
		getsnapshothistorylistall();
		break;
	case TYPE_UPDATESCHEDULE:
		updateschedule();
		break;
	case TYPE_GETSNAPSHOTHISTORYBYINCID:
		getsnapshothistorybyincid();
		break;
	case TYPE_GETLATESTSNAPSHOT:
		getlatestsnapshot();
		break;
	case TYPE_GETSNAPSHOTSHOWINFO:
		getSnapshotShowInfo();
		break;
	case TYPE_SETSNAPSHOTSHOWINFO:
		setSnapshotShowInfo();
		break;
	case TYPE_CHECKEXIST:
   		checkExist();		
		break;
	case TYPE_CHECKTABEXIST:
		checktabexist();
		break;
	case TYPE_DELETENAVIGATE:
		deletenavigate($arg_id);
		break;
	case TYPE_DELETENAVTAB:
		deletenavtab($arg_id);
		break;
	case TYPE_ISCONTAINCHILD:
		iscontainchild();
		break;
	case TYPE_UPDATENAVIGATE:
		updatenavigate();
		break;
	case TYPE_UPDATENAVTAB:
		updatenavtab();
		break;
	case TYPE_UPDATENAVORDER:
		updatenavorder($arg_id);
		break;
	case TYPE_ADDPINRELATE:
	    addpinrelate();
	    break;
	case TYPE_DELINSTANCE:
	    deleteinstance();
	    break;
	case TYPE_SAVEPAGE:
	    savepage();
	    break;    
	case TYPE_SAVEINSTANCE:
	    saveinstance();
	    break;
	case TYPE_UPDATEMERGEDATA:
		updatemergedata();
		break;
	case TYPE_GETMERGEDATA:
		getmergedata();
		break;
	case TYPE_GETMERGESCHED:
		getmergesched();
		break;
	case TYPE_GETWEIBOIDBYMID:
	    geweibotidbymid();
	    break;
	case TYPE_GETNAVHTML:
		getnavhtml();
		break;
    default:
        set_error_msg("arg type has a error");
        break;
}
if (empty($arrs))
{
    echo "";
}
else
{
    $json_str = json_encode($arrs);
	//$logger->error($json_str);
    echo $json_str;
}
