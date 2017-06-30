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
    $arrs["result"]=false;
    $arrs["msg"]="未登录或登陆超时!";
    echo json_encode($arrs);
    exit;
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
define('TYPE_CHECKHOMEPAGE', 'checkhomepage');    //判断是否已经存在首页
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
define('TYPE_CHECKEXIST', 'checkexist'); //检查导航名称是否存在
define('TYPE_DELETENAVIGATE', 'deletenavigate');//删除导航信息
define('TYPE_UPDATENAVIGATE', 'updatenavigate');//修改导航信息
define('TYPE_UPDATENAVORDER', 'updatenavorder');//修改导航排序
define('TYPE_ADDPINRELATE','addpinrelate');//添加pin
define('TYPE_DELINSTANCE','delinstance');//删除实例
define('TYPE_SAVEPAGE','savepage');//保存整个页面html
define('TYPE_SAVEINSTANCE','saveinstance');//保存整个页面实例
define('TYPE_ISCONTAINLEVEL2', 'iscontainlevel2'); //是否含有二级导航

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
        $temp_arr = array();
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
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
    global $dsql,$arrs,$arrmodel;
    $num=0;
    $num2=0;
    //判断是否是租户用户

    if(isset($_SESSION["user"]->tenantid) && $_SESSION["user"]->tenantid!=null && $_SESSION["user"]->tenantid!="")
    {
        $sql="select *  from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$_SESSION["user"]->tenantid." and parentid=0 and userid=".$_SESSION["user"]->userid;
        $qr = $dsql->ExecQuery($sql);

        if (!$qr)
        {
            //thriow new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
        }
        else
        {

            $num = $dsql->GetTotalRow($qr);
            //$arrs["totalcount"]=$num;

            $temp_arr = array();
            $temp_arr2 = array();
            if($num>0)
            {
                while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
                {
                    $sqlchild = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$_SESSION["user"]->tenantid." and  parentid = ".$result["id"]." and userid =".$_SESSION["user"]->userid;
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
                    $qr2 = $dsql->ExecQuery($sqlchild);
                    if (!$qr2)
                    {
                        //thriow new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
                    }
                    else
                    {
                        $num2 = $dsql->GetTotalRow($qr2);
                        //$arrs["totalcount"]=$num;

                        $temp_arr = array();
                        if($num2>0)
                        {
                            while ($r = $dsql->GetArray($qr2, MYSQL_ASSOC))
                            {
                                unset($tmp_arr);
                                $tmp_arr['id'] = $r["id"];
                                $tmp_arr['modelname'] = $r["label"];
                                $tmp_arr['modelid'] = $r["modelid"];

                                if($r["ishomepage"]==1)
                                {
                                    $tmp_arr["isdefault"] = true;
                                }
                                else
                                {
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

    }
    else
    {
        $temp_arr = array();
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
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
        //$arrs["result"]=1;
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
    $sql = "update ".DATABASE_CUSTOMER_NAVIGATE." set label=".$userarr["label"].", tenantid=".$userarr["tenantid"].",parentid='".$userarr["parentid"].",'filepath='".$userarr["path"]."',level=".$userarr["level"]."
	,modeltype=".$userarr["modeltype"].",updatetime=".time()." where id =".$userarr["id"];

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
	$navid = $_REQUEST["navid"];
	$label = $_REQUEST["name"];
	if($navid!=0){
		$sql="select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where label='".$label."' and id!=".$navid." and userid=".$_SESSION["user"]->userid;
	}
	else{
		$sql="select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where label='".$label."' and userid=".$_SESSION["user"]->userid;;
	}
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

        $temp_arr = array();
        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
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

    $sql="select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid=".$_SESSION["user"]->tenantid;
    $qr = $dsql->ExecQuery($sql);
    //echo $sql;

    if (!$qr)
    {
        //throw new Exception(".TYPE_PAGE."- getbyid($id)-".$sql.".mysql_error());
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        //$arrs["totalcount"]=$num;

        $temp_arr = array();
        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
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
    $user = $_SESSION['user'];
    $roleids = implode(',',$user->roles);
    $sql = "select a.*,b.label from ".DATABASE_ACCOUNTING_RULE." a inner join ".DATABASE_TENANT_RESOURCE." b on a.resourceid = b.resourceid where a.resourceid={$modelid} and a.tenantid={$user->tenantid} and a.roleid in (".$roleids.")";
    $qr = $dsql->ExecQuery($sql);

    if (!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        $num = $dsql->GetTotalRow($qr);
        $temp_arr = array();
        if($num>0)
        {
            //TODO 合并权限
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
                $arrs["modelid"] = $result["resourceid"];
                $arrs["rulemodel"] = json_decode($result["ruledata"]);
                $arrs['modelname'] = $result['label'];
            }
        }
    }
}

/**
 *
 * 根据实例ID获取元素信息
 * @param  $instanceid
 */
function getelements()
{
    global $dsql,$arrs, $logger;
	$instanceid = $_REQUEST["instanceid"];
	$elementtype= $_REQUEST["elementtype"];
    $num=0;
    $sql="";
    if($elementtype==0)
    {
        $sql="select a.*,b.instancetype from ".DATABASE_ELEMENT." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b  on a.instanceid = b.id where a.instanceid=".$instanceid;
    }
    else
    {
        $sql="select a.*,b.instancetype from ".DATABASE_ELEMENT." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b  on a.instanceid = b.id where a.instanceid=".$instanceid." and a.type=".$elementtype;
    }
    $qr = $dsql->ExecQuery($sql);

    if(!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else
    {
        $inctype;
        $num = $dsql->GetTotalRow($qr);
        $temp_arr = array();
        if($num>0)
        {
            //$r = $dsql->GetArray($qr, MYSQL_ASSOC);
			//var_dump($r);
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
                $inctype = $result["instancetype"];
                $temp_arr["instanceid"] = $result["instanceid"];
                $temp_arr["instancetype"] = $result["instancetype"];
				$temp_arr["modelid"] = $result["modelid"];
                $temp_arr["elementid"] = $result["elementid"];
                $temp_arr["datajson"] = json_decode($result["content"]);
				//$logger->error($temp_arr["datajson"]);
                $temp_arr["type"] = $result["type"];
                $temp_arr["title"] = $result["title"];
                	
                //$sqlgetinputpin = "select * from ".DATABASE_PINRELATION." where instanceid=".$result["instanceid"]." and inelementid = ".$result["elementid"];
                //$sqlgetoutputpin = "select * from ".DATABASE_PINRELATION." where instanceid=".$result["instanceid"]." and outelementid = ".$result["elementid"];
                $arrs['elements'][] = $temp_arr;
                /*
                $qrout = $dsql->ExecQuery($sqlgetoutputpin);

                if(!$qrout)
                {

                }
                else
                {
                    while ($result = $dsql->GetArray($qrout, MYSQL_ASSOC))
                    {
                        unset($temp_element);
                        $temp_element["outpinid"][] = $result["outpinid"];
                        $temp_element["outelementid"] = $result["outelementid"];
                        $temp_element["inputdata"] = json_decode($result["inputdata"]);
                        $temp_element["outputdata"] = json_decode($result["outputdata"]);
                        $temp_arr["outputpinid"][] = $temp_element;
                    }

                }
                */
            }
            if($inctype == 2 && $elementtype==0){//联动时,并且获取全部elements时，去除pinrelation
                $sqlgetrelation = "select * from ".DATABASE_PINRELATION." where instanceid={$instanceid}";
                $qrin = $dsql->ExecQuery($sqlgetrelation);
                if(!$qrin)
                {
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlgetrelation} ".$dsql->GetError());
                }
                else
                {
                    while ($result = $dsql->GetArray($qrin, MYSQL_ASSOC))
                    {
                        unset($temp_element);
                        $temp_element["instanceid"] = $result["instanceid"];
                        $temp_element["outelementid"] = $result["outelementid"];
                        $temp_element["inelementid"] = $result["inelementid"];
                        $temp_element["inpinid"] = $result["inpinid"];
                        $temp_element["outpinid"] = $result["outpinid"];
                        $temp_element["inputdata"] = json_decode($result["inputdata"]);
                        $temp_element["outputdata"] = json_decode($result["outputdata"]);
                        for($i = 0; $i < count($arrs['elements']); $i++){
                            if($arrs['elements'][$i]['elementid'] == $result['outelementid'] 
                                && !in_array($temp_element,$arrs['elements'][$i]['outputpin']) ){
                                $arrs['elements'][$i]['outputpin'][] = $temp_element;
                            }
                            if($arrs['elements'][$i]['elementid'] == $result['inelementid']
                                && !in_array($temp_element,$arrs['elements'][$i]['inputpin']) ){
                                $arrs['elements'][$i]['inputpin'][] = $temp_element;
                            }
                        }
                        $arrs["pinrelation"][] = $temp_element;
                    }
    
                }
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




/**
 *
 * 添加导航信息
 * 参数:通过异步POST方式将数据提交到本函数存放在$arrsdata中
 */
function addnavigate()
{
    global $dsql,$arrs,$arrsdata;
    
    if($arrsdata->modifyhompage==0) //不修改主页
    {

        $sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (userid,tenantid,ishomepage,label,modelid,level,orderid,parentid, pagetitle,pagetype,updatetime) values (".$_SESSION["user"]->userid.",".$_SESSION["user"]->tenantid.",".$arrsdata->ishomepage.",'".$arrsdata->label."',0,".$arrsdata->level.",0,".$arrsdata->parentid.",'".$arrsdata->pagetitle."',".$arrsdata->pagetype.",".time().")";
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
        $sql="update ".DATABASE_CUSTOMER_NAVIGATE." set ishomepage=0 where userid=".$_SESSION["user"]->userid." and tenantid=".$_SESSION["user"]->tenantid." and level=".$arrsdata->level;
        try
        {
            $qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$arrs["flag"]=0;
				set_error_msg("update  set ishomepage = 0 error".$dsql->GetError());
			}
			//添加导航的主页为1
            $sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (userid,tenantid,ishomepage,label,modelid,level,orderid,parentid,pagetitle,pagetype,updatetime) values (".$_SESSION["user"]->userid.",".$_SESSION["user"]->tenantid.",".$arrsdata->ishomepage.",'".$arrsdata->label."',0,".$arrsdata->level.",0,".$arrsdata->parentid.",'".$arrsdata->pagetitle."',".$arrsdata->pagetype.",".time().")";
            $qr = $dsql->ExecQuery($sql);
			if($qr){
				$arrs["flag"]=1;
			}
			else{
				$arrs["flag"]=0;
			}
        }
        catch(Exception $e)
        {
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
/**
 *
 * 添加元素信息
 * 参数:
 */
function addelement($eledata)
{
    global $dsql,$arrs,$arrsdata,$logger;
    try{
        $jsonstr = jsonEncode4DB($eledata->content);
        $sql = "insert into ".DATABASE_ELEMENT." (instanceid,modelid,type,content,title,updatetime) values
    	(".$eledata->instanceid.",'".$eledata->modelid."',".$eledata->type.",'{$jsonstr}','{$eledata->title}',".time().")";
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
            //$arrs["result"]=0;
            return false;
        }
        else{
            $getlastid = "select LAST_INSERT_ID() as id";
            $qr2 = $dsql->ExecQuery($getlastid);
            if(!$qr2){
                $logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
                //$arrs["result"]=0;
                return false;
            }
            else{
                $idrs = $dsql->GetArray($qr2);
                //$arrs["elementid"] = $idrs['id'];
                //$arrs["result"]=1;
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
    	$contentStr = jsonEncode4DB($ele->content);
		$sql = "update ".DATABASE_ELEMENT." set modelid =".$ele->modelid.", content='".$contentStr."',title='".$ele->title."',type=".$ele->type." where instanceid =".$ele->instanceid." and elementid = ".$ele->elementid;
        $qr = $dsql->ExecQuery($sql);
		if($qr){
			//$arrs["result"]=1;
			//$arrs['elementid'] = $ele->elementid;
			return true;
		}
		else{
		    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			//$arrs["result"]=0;
			return false;
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
    foreach($pinrelate->outputdata->value as $k=>$v){
        foreach($v as $prop=>$item){
            if(is_object($item)){
                $v->$prop->text = rawurlencode($item->text);//如果    
            }
            else{
                $v->$prop = rawurlencode($item);
            }
        }
    }
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
            //$arrs["result"]=0;
            return false;
        }
        else{
            if($dsql->GetAffectedRows() > 0){
                $getlastid = "select LAST_INSERT_ID() as id";
                $qr2 = $dsql->ExecQuery($getlastid);
                if(!$qr2){
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
                    //$arrs["result"]=0;
                    return false;
                }
                else{
                    $result = $dsql->GetArray($qr2, MYSQL_ASSOC);
                    //$arrs["instanceid"] = $result["id"];
                    //$arrs["result"]=1;
                    return $result["id"];
                }
            }
            else{
                //$arrs["result"]=0;
                return false;
            }
        }
    }
    catch (Exception $e)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." Exception： ".$e->getMessage());
        //$arrs["result"]=0;
        return false;

    }

    /*
     $sql = "insert into ".DATABASE_ELEMENT." (instanceid,outelementid,outpinid,inelementid,inpinid,updatetime) values
     (".$arrsdata->instanceid.",'".$arrsdata->modelid."',".$arrsdata->elementid.",".$arrsdata->type.",".json_encode($arrsdata->content).",".time().")";
     try
     {
     $qr = $dsql->ExecQuery($sql);
     $arrs["flag"]=1;
     }
     catch(Exception $e)
     {
     $arrs["flag"]=0;
     }
     */
}

function saveinstance(){
    global $dsql,$arrs,$arrsdata,$logger;
    if(count($arrsdata->delincids) > 0){
        $delids = implode(',',$arrsdata->delincids);
        $delr = delinstance($delids);
        if($delr === false){
            $logger->error(__FILE__." func:".__FUNCTION__." delinstance return false");
            $arrs['result'] = 0;
            return;
        }
    }
   
    //新增的
    if(count($arrsdata->newinc) > 0){
        foreach($arrsdata->newinc as $k => $v){
            unset($temp_inc);
            if($v->instancetype == 2){//联动实例
                $temp_inc = array("oldinstanceid"=> $v->instanceid,"newinstanceid"=>'', "elements"=>array());
                if($v->instanceid > 0){//修改，将旧数据删除
                    if(delelementbyincid($v->instanceid) === false){
                        $arrs['result'] = 0;
                        return;
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
                    $ele->instanceid = $v->instanceid;
                    $eleid = addelement($ele);
                    if($eleid === false){
                        $arrs['result'] = 0;
                        return;
                    }
                    else{
                        $ele->elementid = $eleid;
                    }
                    $temp_ele['newid'] = $ele->elementid;
                    $temp_inc['elements'][] = $temp_ele;
                    foreach ($v->pinrelation as $pi => $pin){
                        $pin->instanceid = $v->instanceid;
                        if($pin->outelementid == $eleid){
                            $pin->outelementid = $eleid;
                        }
                        if($pin->inelementid == $eleid){
                            $pin->inelementid = $eleid;
                        }
                    }
                }
                foreach ($v->pinrelation as $pi => $pin){
                    if(addpinrelate($pin) === false){
                        $arrs['result'] = 0;
                        return;
                    }
                }
                
            }
            else{//普通实例
                if($v->instanceid > 0){
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
                        $eleid = addelement($v->elements[0]);
                        if($eleid === false){
                            $arrs['result'] = 0;
                            return;
                        }
                        $temp_ele = array("oldid"=>$v->elements[0]->elementid,'newid'=>$eleid);
                        $temp_inc['elements'][] = $temp_ele;
                   }
                }
            }
            $arrs['data'][] = $temp_inc;
        }
    }
    $arrs['result'] = 1;
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

	if($arrsdata->ishomepage==0)
	{

		$sql = "update ".DATABASE_CUSTOMER_NAVIGATE." set label='".$arrsdata->label."',pagetitle='".$arrsdata->pagetitle."',pagetype=".$arrsdata->pagetype.",level=".$arrsdata->level.",parentid=".$arrsdata->parentid.",updatetime=".time().",ishomepage=".$arrsdata->ishomepage." where id=".$arrsdata->id ;
		//echo $sql;
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
	else
	{
		$sql="update ".DATABASE_CUSTOMER_NAVIGATE." set ishomepage=0 where userid=".$_SESSION["user"]->userid." and level= ".$arrsdata->level;
		$sql2 = "update ".DATABASE_CUSTOMER_NAVIGATE." set label='".$arrsdata->label."',pagetitle='".$arrsdata->pagetitle."',pagetype=".$arrsdata->pagetype.",level=".$arrsdata->level.",parentid=".$arrsdata->parentid.",updatetime=".time().",ishomepage=".$arrsdata->ishomepage. " where id=".$arrsdata->id;
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
function iscontainlevel2(){
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
        $filename=$dir."/".$navid.".html";
        $fp = fopen($filename, 'w');
        fwrite($fp, $htmldata);
        fclose($fp);
        //$arrs['result'] = 1;
        return true;
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
 * @param unknown_type $ishomepae
 */
function checkhomepage()
{
    global $dsql,$arrs;
	$navlevel = $_GET["navlevel"];

    $sql = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where userid=".$_SESSION["user"]->userid." and tenantid =".$_SESSION["user"]->tenantid." and level = ".$navlevel." and ishomepage=1";
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

function setlinkagemodel()
{
    global $dsql,$arrs;

    //判断是否第一次创建
    if(instanceid==0)
    {

    }
    else
    {

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
 *
 * 获取实例的json
 * @param unknown_type $instaneid
 */
function getelementjson($instaneid)
{
    $arrinstance = array();
    $sql="select a.instanceid,a.instancetype,b.* from ".DATABASE_TENANT_TAGINSTANCT." as a inner join ".DATABASE_ELEMENT." as b on a.id = b.instanceid where a.id =".$instaneid;
    $qr = $dsql->ExecQuery($sql);

    if(!$qr)
    {

    }
    else
    {
        $tmparr = array();
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {unset($tmparr);
        $tmparr["instanceid"]  = $result["instanceid"];
        $tmparr["instancetype"]  = $result["instancetype"];
        $tmparr["content"]  = $result["content"];

        $arrinstance[] = $tmparr;
        }
    }

    return $arrinstance;
}//end function

/**
 *  返回合并后的json
 *  输入参数:资源ID
 * 没有使用此函数
 */
function meragejson($modelid)
{
    global $dsql,$arrs;
    $totaljson;
    $srcdata;
    $tmpdata;
    if($_SESSION["user"]->usertype==3)
    {
        $sqlresource = "select * from ".DATABASE_USER_ROLE_MAPPIMG." as a inner join ".DATABASE_ACCOUNTING_RULE." as b on a.id=b.roleid where a.roletype=".$_SESSION["user"]->usertype." and a.id in(".$_SESSION["user"]->roleid.")and b.resourceid = ".$modelid." and b.tenantid = ".$_SESSION["user"]->tenantid;
    }
    else
    {
        $sqlresource = "select c.*,d.rulemodel as roledata from (select a.roleid,b.resourceid from ".DATABASE_USER_ROLE_MAPPIMG." as a inner join
".DATABASE_ROLE_RESOURCE_RELATION." as b on a.roleid=b.roleid where a.roletype=".$_SESSION["user"]->usertype." and a.roleid in(".$_SESSION["user"]->roleid.")and b.resourceid = ".$moleid." ) as c 
inner join billrulemodel as d on c.resourceid = d.resourceid"; 

    }
    $qr = $dsql->ExecQuery($sqlresource);

    if($dsql->GetTotalRow($qr)>0)
    {
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
            unset($tmparr);
            $tmparr["resourceid"]  = $result["resourceid"];
            $tmparr["ruledata"]  = json_decode($result["ruledata"],true);
            $arrinstance[] = $tmparr;
        }
    }
    //为数组中的第条数据赋值
    $srcdata =  $arrinstance[0]["ruledata"];
    if(count($arrinstance)>1)
    {
        for($i=0;$i<count($arrinstance);$i++)
        {
            if($i>1)
            {
                //为需要循环的数据赋值
                $tmpdata = $arrinstance[$i]["ruledata"];
                	
                foreach($tmpdata["filter"] as $k=>$v)
                {
                    //对比allowcontrol和isshow ,将权限比较大的值赋给数组元素0
                    if($srcdata["filter"][$k]["allowcontrol"]<$tmpdata["filter"][$k]["allowcontrol"])
                    {
                        $srcdata[$k]["allowcontrol"] = $tmpdata["filter"][$k]["allowcontrol"];
                    }

                    //对比isshow的值如果isshow为true
                    if($arrinstance[i]["content"]["filter"][$k]["isshow"]=="true")
                    {
                        $srcdata["filter"][$k]["isshow"] = $tmpdata["filter"][$k]["isshow"];
                    }

                    switch($k)
                    {
                        case "source":
                        case "emotion":
                        case "username":
                            //不对两组值是否相等
                            $diffvalue=array_diff($tmpdata["filter"][$k]["limit"],$srcdata["filter"][$k]["limit"]);

                            //如果不相等则将不相等的值与原数据进行判断，如果不存在则追加数据
                            if(count($diffvalue)>0)
                            {
                                foreach($diffvalue as $key=>$value)
                                {
                                    if(checkvalueisexist($value,$srcdata["filter"][$k]["limit"]))
                                    {
                                        continue;
                                    }
                                    else
                                    {
                                        $srcdata["filter"][$k]["limit"][] = $value;
                                    }
                                }
                            }
                            break;
                        case "business":
                        case "area":
                            $diffvalue=array_diff($tmpdata["filter"][$k]["limit"]["value"],$srcdata["filter"][$k]["limit"]["value"]);

                            //如果不相等则将不相等的值与原数据进行判断，如果不存在则追加数据
                            if(count($diffvalue)>0)
                            {
                                foreach($diffvalue as $key=>$value)
                                {
                                    if(checkvalueisexist($value,$srcdata["filter"][$k]["limit"]["value"]))
                                    {
                                        continue;
                                    }
                                    else
                                    {
                                        $srcdata["filter"][$k]["limit"]["value"][] = $value;
                                    }
                                }
                            }

                            $difftext=array_diff($srcdata["filter"][$k]["limit"]["text"],$tmpdata["filter"][$k]["limit"]["text"]);
                            //如果不相等则将不相等的值与原数据进行判断，如果不存在则追加数据
                            if(count($difftext)>0)
                            {
                                foreach($difftext as $key=>$value)
                                {
                                    if(checkvalueisexist($value,$srcdata["filter"][$k]["limit"]["text"]))
                                    {
                                        continue;
                                    }
                                    else
                                    {
                                        $srcdata["filter"][$k]["limit"]["text"][] = $value;
                                    }
                                }
                            }
                            break;
                        case "sex":
                        case "verified":
                            $diffvalue=array_diff($tmpdata["filter"][$k]["limit"],$srcdata["filter"][$k]["limit"]);

                            //如果不相等则将不相等的值与原数据进行判断，如果不存在则追加数据
                            if(count($diffvalue)>0)
                            {
                                foreach($diffvalue as $key=>$value)
                                {
                                    if(checkvalueisexist($value,$srcdata["filter"][$k]["limit"]))
                                    {
                                        continue;
                                    }
                                    else
                                    {
                                        $srcdata["filter"][$k]["limit"][] = $value;
                                    }
                                }
                            }
                            break;
                        default:
                            //判断默认值是否在指定的范围内
                            if($srcdata["filter"][$k]["minvalue"]>$tmpdata["filter"][$k]["minvalue"])
                            {
                                $srcdata["filter"][$k]["minvalue"] = $tmpdata["filter"][$k]["minvalue"];
                            }
                            if($srcdata["filter"][$k]["maxvalue"]>$tmpdata["filter"][$k]["maxvalue"])
                            {
                                $srcdata["filter"][$k]["maxvalue"] = $tmpdata["filter"][$k]["maxvalue"];
                            }
                    }
                }
            }

        }
    }
    return $srcdata;
}//end function
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

    ////计算limit的起始位置
    $limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
    $num=0;
/*
    if($id==0)
    {
        $sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE;
        $sql="select b.*,a.id as instanceid from ".DATABASE_TENANT_TAGINSTANCT." as a right join (select * from ".DATABASE_CUSTOMER_NAVIGATE." limit ".$limit_cursor.",".$arg_pagesize.") as b on a.navid = b.id";
    }
    else
    {
        $sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$id;
        $sql="select b.*,a.id as instanceid from ".DATABASE_TENANT_TAGINSTANCT." as a right join (select * from ".DATABASE_CUSTOMER_NAVIGATE."  where tenantid=".$id." limit ".$limit_cursor.",".$arg_pagesize.") as b on a.navid = b.id";
    }
    */
    
    $sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE." where userid=".$_SESSION["user"]->userid;
    //$sql="select b.*,a.id as instanceid from ".DATABASE_TENANT_TAGINSTANCT." as a right join (select * from ".DATABASE_CUSTOMER_NAVIGATE." where userid=".$_SESSION["user"]->userid." order by orderid desc limit ".$limit_cursor.",".$arg_pagesize.") as b on a.navid = b.id";
    $sql= "select * from ".DATABASE_CUSTOMER_NAVIGATE." where userid=".$_SESSION["user"]->userid." order by orderid desc limit ".$limit_cursor.",".$arg_pagesize;
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

    $sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where userid=".$_SESSION["user"]->userid." and parentid=0 order by parentid";
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

        $temp_arr = array();
        if($num>0)
        {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
            {
                $temp_arr["id"] = $result["id"];
                $temp_arr["label"] = $result["label"];
                $temp_arr["tenantid"] = $result["tenantid"];
                //$temp_arr["pageid"] = $result["pageid"];
                $temp_arr["pagetitle"] = $result["pagetitle"];
                $temp_arr["level"] = $result["level"];
                $temp_arr["parentid"] = $result["parentid"];
                //$temp_arr["modeltype"] = $result["modeltype"];
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

//根据Id获取模块信息
function getnavbyid($id)
{
    global $dsql,$arrs;
    $num=0;

    $sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where id=".$id;
    $qr = $dsql->ExecQuery($sql);

    if (!$qr)
    {
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
                $arrs["result"]=0;
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
                $temp_arr["label"] = $result["label"];
                $temp_arr["level"] = $result["level"];
                $temp_arr["tenantid"] = $result["tenantid"];
                //$temp_arr["pageid"] = $result["pageid"];
                $temp_arr["pagetitle"] = $result["pagetitle"];
                $temp_arr["pagetype"] = $result["pagetype"];
                $temp_arr["parentid"] = $result["parentid"];
                $temp_arr["ishomepage"] = $result["ishomepage"];
                $temp_arr["updatetime"] = $result["updatetime"];

                $arrs[CHILDS][] = $temp_arr;
            }
            $arrs["result"]=1;
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
    case TYPE_GETLEFTMENU:
        getleftmenu();
        break;
    case TYPE_GETELEMENTS:
        getelements();
        break;
    case TYPE_CHECKHOMEPAGE:
        checkhomepage();
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
	case TYPE_CHECKEXIST:
   		checkExist();		
		break;
	case TYPE_DELETENAVIGATE:
		deletenavigate($arg_id);
		break;
	case TYPE_ISCONTAINLEVEL2:
		iscontainlevel2();
		break;
	case TYPE_UPDATENAVIGATE:
		updatenavigate();
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
