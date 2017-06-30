<?php
include_once('includes.php');
include_once('model_config.php');
include_once ('checkpure.php');
include_once('userinfo.class.php');
include_once("authorization.class.php");
include_once('computeprice.php');
include_once('commonFun.php');

initLogger(LOGNAME_WEBAPI);

define('CHILDS', "children");
session_start();
if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
    $arrs["result"]=false;
    $arrs["msg"]="未登录或登陆超时!";
    echo json_encode($arrs);
    exit;
}
//变量获取
define('TYPE_PAGE','resource_model');
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');
define('ARG_NAME', 'label');


//获取数据类型 (type)的具体内容
define('TYPE_GETRESOURCE', 'getresource');    //获取所资源
define('TYPE_ADDRESOURCE', 'addresource');    //添加资源
define('TYPE_UPDATERESOURCE', 'updateresource');    //修改资源
define('TYPE_DELETERESOURCE', 'deleteresource');    //
define('TYPE_GETRESOURCEBYNAME', 'getresourcebyname');//根据名称查询组信息
define('TYPE_GETRESOURCEBYID', 'getresourcebyid');//根据名称查询组信息
define('TYPE_SEARCHRESOURCE', 'searchresource');    //查询函数
define('TYPE_ADDGROUP', 'addgroup');    //添加组信息
define('TYPE_UPDATEGROUP', 'updategroup');//修改组信息
define('TYPE_DELETEGROUP', 'deletegroup');//删除租信息
define('TYPE_GETGROUPBYID', 'getgroupbyid');//根据ID获取组信息
define('TYPE_GETALLGROUP', 'getallgroup');//获取所有组信息
define('TYPE_GETGROUPBYNAME', 'getgroupbyname');//根据组名称获取组信息
define('TYPE_GETGROUP', 'getgroup');//获取组信息
define('TYPE_GETRESOURCEBYTYPE', 'getresourcebytype');//根据组id获取资源信息
define('TYPE_CHECKRESOURCE', 'checkresource');//检查资源信息
define('TYPE_CHECKRESOURCEBYTYPE', 'checkresourcebytype');//根据类型查找资源是否重复
define('TYPE_GETTENANTRESOURCE', 'gettenantresource');//根据类型查找资源是否重复
define('TYPE_SETTENANTRESOURCE', 'settenantresource');//设置租户的角色和模型
define('TYPE_GETRESOURCEBYTENANT', 'getresourcebytenant');
define('TYPE_GETRESOURCEBYTENANTID', 'getresourcebytenantid');
define('TYPE_CHECKADMINTYPE', 'checkadmintype');
define('TYPE_GETRESOURCEBYGROUP', 'getresourcebygroup');
define('TYPE_GETGROUPNOPAGE', 'getgroupnopage');//获取组信息
define('TYPE_GETRULEBYTENANT', 'getrulebytenant');//根据租户ID和资源ID获取计费规则模型
define('TYPE_UPDATETENANTRULE', 'updatetenantrule');//根据租户ID和资源ID获取计费规则模型
define('TYPE_CHECKUSERRESOURCE', 'checkuserresource');
define('TYPE_GETRESOURCEBYPRODUCT', 'getresourcebyproduct');
define('TYPE_GETTENANTRES', 'gettenantres');
define('TYPE_ADDTENANTRESOURCE', 'addtenantresource');
define('TYPE_BINDRESOURCEINFO', 'bindresourceinfo');
define('TYPE_GETALLTENANTRESOURCE', 'getalltenantresource');
define('TYPE_GETRESOURCEMAXID', 'getresourcemaxid');
define('TYPE_GETRESINFOBYTENANT', 'getresinfobytenant');
define('TYPE_GETTENANTRESREL', 'gettenantresrel');   //获取租户资源
define('TYPE_CHECKRESEXIST', 'checkresexist');
define('TYPE_GETTENANTRESRELATION', 'gettenantresrelation');  //从tenant_tesoure_relation表获取租户的资源
define('TYPE_GETALLSYSTEMRESOURCE', 'getallsystemresource');
define('TYPE_BINDSYSRESOURCEINFO', 'bindsysresourceinfo');
define('TYPE_GETRESOURCEBYROLE', 'getresourcebyrole');
define('TYPE_DELTENANTRESOURCE', 'deletetenantresource');
define('TYPE_DELRESBYTENANTROLE', 'delresbytenantrole');
define('TYPE_GETRESOURCEBYUSER', 'getresourcebyuser');
define('TYPE_GETACCOUNTJSON', 'getaccountjson');//获取计费json
define('TYPE_UPDATEACCOUNTJSON', 'updateaccountjson');//更新计费json
define('TYPE_GETLATESTJSON', 'getlatestjson');//获取计费json

//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_uid;
$arg_rid;
$arg_pid;
$arg_search_page;
$arg_pagesize;
$arg_roletype;
$arg_scopetype;
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

//添加新资源
function addresource()
{
    global $dsql,$arrs,$arrsdata,$logger;

    $resourcedata;
    $resid;

    if(!Authorization::checkUserUseage(6,1,null))
    {
        $arrs["result"]=false;
        $arrs["msg"]="您没有权限使用此功能,请与管理员联系!";
        echo json_encode($arrs);
        exit;
    }
    else
    {
        $arrs["result"]=true;

        switch ($arrsdata->resourcetype) {
            case 1:
                $num=0;
                $sqlcheck="select * from ".DATABASE_SYSTEM_RESOURCE." where resourceid =".$arrsdata->resourceid;
                $qrcheck = $dsql->ExecQuery($sqlcheck);

                if(!$qrcheck)
                {
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlcheck} ".$dsql->GetError());

                    $arrs["flag"]=3;
                    $json_str = json_encode($arrs);
                    echo $json_str;
                    exit;
                }
                else
                {
                    $num =  $dsql->GetTotalRow($qrcheck);
                }
                if($num>0)
                {
                    $arrs["flag"]=2;
                    $json_str = json_encode($arrs);
                    echo $json_str;
                    exit;
                }
                else
                {
                    $sql = "insert into ".DATABASE_SYSTEM_RESOURCE." (resourceid,label,description,groupid,updatetime,haschild) values(".$arrsdata->resourceid.",'".$arrsdata->label."','".$arrsdata->description."',".$arrsdata->groupid.",".time().",".$arrsdata->haschild.")";
                    $q2 = $dsql->ExecQuery($sql);
                    if(!$q2)
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
            case 3:
                $num=0;
                $sqlcheck="select * from ".DATABASE_TENANT_RESOURCE." where resourceid =".$arrsdata->resourceid;
                $qrcheck = $dsql->ExecQuery($sqlcheck);
                if(!$qrcheck)
                {
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlcheck} ".$dsql->GetError());

                    $arrs["flag"]=3;
                    $json_str = json_encode($arrs);
                    echo $json_str;
                    exit;
                }
                else
                {
                    $num =  $dsql->GetTotalRow($qrcheck);
                }

                if($num>0)
                {
                    $arrs["flag"]=3;
                }
                else
                {
                    $ruledatastr = jsonEncode4DB($arrsdata->ruledata);
                    $sql = "insert into ".DATABASE_TENANT_RESOURCE." (resourceid,label,description,groupid,score,ruledata,updatetime,scope) values (".$arrsdata->resourceid.",'".$arrsdata->label."','".$arrsdata->description."',".$arrsdata->groupid.",".$arrsdata->score.",'".$ruledatastr."',".time().",".$arrsdata->scope.");";
                    $resourceid=0;
                    $q = $dsql->ExecQuery($sql);
                    if(!$q)
                    {
                        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());

                        $arrs["flag"]=0;
                    }
                    else {
                        if(($arrsdata->rid!=null)&&($arrsdata->rid!=""))
                        {
                            $resourcedata = explode(",",$arrsdata->rid);

                            $sql="";
                            foreach($resourcedata as $key => $value){

                                $sql = "insert into ".DATABASE_PRODUCT_RESOURCE." (productid,resourceid,updatetime) values(".$value.",".$arrsdata->resourceid.",".time().");";
                                $q2 = $dsql->ExecQuery($sql);

                            }
                        }
                        $arrs["flag"]=1;

                    }


                }
                break;
        }




    }//结束权限判断

}
/*
 * 设置租户资源关系
 */
function settenantresource()
{
    global $dsql,$arrs,$arrsdata, $logger;
    if(!Authorization::checkUserUseage(8,1,null))
    {
        $arrs["result"]=false;
        $arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
        echo json_encode($arrs);
        exit;
    }
    else
    {
        $arrs["result"]=true;
        $sqldel;
        $sql;
        $roledata;
        $resourcedata;

        $deletedata = $arrsdata->delresource;  //获取要删除的资源
        //修改删除资源
        if(!empty($deletedata))
        {
            foreach($deletedata as $key => $value)
            {
                $sqldel="delete from ".DATABASE_ACCOUNTING_RULE." where resourceid =".$value." and roleid=".$arrsdata->roleid." and tenantid=".$arrsdata->tenantid;
                $q = $dsql->ExecQuery($sqldel);
                if(!$q)
                {
                    $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqldel} ".$dsql->GetError());
                    $arrs["flag"]=0;
                    $json_str = json_encode($arrs);
                    echo $json_str;
                    exit;
                }
            }
        }


        if($arrsdata->roleid!="")
        {
			$rolevalue = $arrsdata->roleid;
        }

        if($arrsdata->resourceid!="")
        {
            $resourcedata = explode(",",$arrsdata->resourceid);
        }
		if($rolevalue != "")
		{
			if(isset($resourcedata) && count($resourcedata)>0)
			{
				foreach($resourcedata as $key => $value){
					//先判断对应租户下 对应角色资源是否已添加
					$sqlexist = "select count(*) as cn from ".DATABASE_ACCOUNTING_RULE." where resourceid =".$value." and roleid = ".$rolevalue." and tenantid =".$arrsdata->tenantid;
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
							$content;
							$sqlselectacc = "select content from ".DATABASE_TENANT_RESOURCE_RELATION." where resourceid={$value} and tenantid={$arrsdata->tenantid}";
							$qrselacc = $dsql->ExecQuery($sqlselectacc);
							if(!$qrselacc){
								$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlselectacc} ".$dsql->GetError());
								$arrs["flag"]=0;
								echo json_encode($arrs);
								exit;
							}
							else{
								$selaccrs = $dsql->GetArray($qrselacc);
								if(!$selaccrs){
									$logger->error(__FILE__." func:".__FUNCTION__." content is empty sql:{$sqlselectacc} ");
									$arrs["flag"]=0;
									echo json_encode($arrs);
									exit;
								}
								else{
									$content = $selaccrs['content'];
								}
							}
							$sql="insert into ".DATABASE_ACCOUNTING_RULE." (resourceid,tenantid,roleid,updatetime,ruledata) values(".$value.",".$arrsdata->tenantid.",".$rolevalue.",".time().",'".jsonEncode4DB(json_decode($content))."')";
							$q = $dsql->ExecQuery($sql);
							if(!$q)
							{
								$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
								$arrs["flag"]=0;
								$json_str = json_encode($arrs);
								echo $json_str;
								exit;
							}
						}
					}
				}
			}
			else
			{
				$sql="delete from ".DATABASE_ACCOUNTING_RULE." where tenantid = ".$arrsdata->tenantid." and roleid = ".$arrsdata->roleid."";
				$q = $dsql->ExecQuery($sql);
				if(!$q)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
			}
		}
		$arrs["flag"]=1;
	}//结束权限判断
}

/*
 * 获取所有资源
 */
function getresource($typeid)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	switch ($typeid) {
	case 1:
		$sql = "select * from ".DATABASE_SYSTEM_RESOURCE." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
		$totalCount ="select count(*) totalcount from ".DATABASE_SYSTEM_RESOURCE;
		break;
	case 2:
		$totalCount ="select count(*) totalcount from ".DATABASE_TENANT_MANAGE_RESOURCE;
		$sql = "select * from ".DATABASE_TENANT_MANAGE_RESOURCE." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
		break;
	case 3:
		$totalCount ="select count(*) totalcount from ".DATABASE_TENANT_RESOURCE;
		$sql = "select * from ".DATABASE_TENANT_RESOURCE." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
		break;
	}

	$qr2 = $dsql->ExecQuery($totalCount);
	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else {
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
				$temp_arr["resourceid"] = $r["resourceid"];
				$temp_arr["label"] = $r["label"];
				if($typeid==3)
				{
					$temp_arr["score"] = $r["score"];
				}
				$temp_arr["description"] = $r["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}



}

//删除资源信息
function deleteresource($id,$typeid)
{
	global $dsql,$arrs,$logger;

	if(!Authorization::checkUserUseage(6,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else{
		$arrs["result"]=true;
		switch ($typeid) {
		case 1:
			$sql = "delete  from ".DATABASE_SYSTEM_RESOURCE." where resourceid in (".$id.")";
			//删除角色资源关系
			$sqlrole = "delete from ".DATABASE_ROLE_RESOURCE_RELATION." where resourceid in (".$id.")";

			$q1 = $dsql->ExecQuery($sqlrole);
			if(!$q1)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlrole} ".$dsql->GetError());
				$arrs["flag"]=0;
				echo json_encode($arrs);
				exit;
			}
			else
			{
				$q4 = $dsql->ExecQuery($sql);
				if(!$q4)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}

			}
			break;
		case 3:
			$sql = "delete  from ".DATABASE_TENANT_RESOURCE." where resourceid in (".$id.")";
			//删除规则表数据
			$sqlrule = "delete from ".DATABASE_ACCOUNTING_RULE." where resourceid in (".$id.")";
			//删除资源与与租户的关系
			$sqltenantresource = "delete from ".DATABASE_TENANT_RESOURCE_RELATION." where resourceid in (".$id.")";
			//删除产品和资源的关系
			$sqlproductresource = "delete from ".DATABASE_PRODUCT_RESOURCE." where resourceid in (".$id.")";
			$q3 = $dsql->ExecQuery($sqlrule);
			if(!$q3)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlrule} ".$dsql->GetError());
				$arrs["flag"]=0;
				echo json_encode($arrs);
				exit;
			}
			else
			{
				$q4 = $dsql->ExecQuery($sql);
				if(!$q4)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
					echo json_encode($arrs);
					exit;
				}
				else
				{
					$q5 = $dsql->ExecQuery($sqltenantresource);
					if(!$q5)
					{
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqltenantresource} ".$dsql->GetError());
						$arrs["flag"]=0;
						echo json_encode($arrs);
						exit;
					}
					else
					{
						$arrs["flag"]=1;
					}
					$q6 = $dsql->ExecQuery($sqlproductresource);
					if(!$q6)
					{
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlproductresource} ".$dsql->GetError());

						$arrs["flag"]=0;
						echo json_encode($arrs);
						exit;
					}
					else
					{
						$arrs["flag"]=1;
					}
				}
			}
			break;
		}
		//$q2 = $dsql->ExecQuery($sqltemplate);
	}//结束权限判断

}//end function


/**
 * 删除某一租户的资源
 * @param $id
 *
 */
function deletetenantresource($id,$tid)
{
	global $dsql,$arrs,$logger;

	$sql = "delete  from ".DATABASE_TENANT_RESOURCE_RELATION." where resourceid in (".$id.") and tenantid=".$tid;

	//删除资源与租户关系
	$sqlrule = "delete from ".DATABASE_ACCOUNTING_RULE." where resourceid in (".$id.") and tenantid=".$tid;


	$q1 = $dsql->ExecQuery($sqlrule);
	if(!$q1)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());

		$arrs["flag"]=0;
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$q2 = $dsql->ExecQuery($sql);
		if(!$q2)
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



/**
 * 删除某一租户的资源
 * @param $id
 *
 */
function delresbytenantrole($id,$roleid,$tid)
{
	global $dsql,$arrs,$logger;


	//删除资源与租户关系
	$sqlrule = "delete from ".DATABASE_ACCOUNTING_RULE." where resourceid in (".$id.") and tenantid=".$tid." and roleid=".$roleid;

	try {
		$q1 = $dsql->ExecQuery($sqlrule);
		$q2 = $dsql->ExecQuery($sql);

		$arrs["flag"]=1;
	}
	catch(Exception $e)
	{
		$arrs["flag"]=0;
	}

}




//修改资源信息
function updateresource()
{
	global $dsql,$arrs,$arrsdata,$logger;
	if(!Authorization::checkUserUseage(6,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		switch ($arrsdata->resourcetype) {
		case 1:
			$sql="update ".DATABASE_SYSTEM_RESOURCE." set label='".$arrsdata->label."',description='".$arrsdata->description."',groupid=".$arrsdata->groupid.",updatetime=".time()." where resourceid=".$arrsdata->id;
			break;
		case 3:
			$ruledatastr = jsonEncode4DB($arrsdata->ruledata);
			$sql="update ".DATABASE_TENANT_RESOURCE." set label='".$arrsdata->label."',description='".$arrsdata->description."',score=".$arrsdata->score.",groupid=".$arrsdata->groupid.",updatetime=".time().",scope=".$arrsdata->scope.",ruledata='".$ruledatastr."' where resourceid=".$arrsdata->id;
			break;
		}
		$q = $dsql->ExecQuery($sql);
		if(!$q){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else{
			$arrs["flag"]=1;
		}
	}//结束权限判断
}



//根据名称查找资源
//
function getresourcebyname($name,$typeid)
{
	global $dsql,$arrs,$logger;
	switch ($typeid) {
	case 1:
		$sql =  "select count(*) as totalcount from ".DATABASE_SYSTEM_RESOURCE." where  label='".$name."'";
		break;
	case 3:
		$sql =  "select count(*) as totalcount from ".DATABASE_TENANT_RESOURCE." where  label='".$name."'";
		break;
	default:
		break;
	}
	//echo $sql;
	$qr = $dsql->ExecQuery($sql);


	if(!$qr)
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

//根据ID获取资源
function getresourcebyid($rid,$type){
	global $dsql,$arrs,$logger;
	switch($type){
	case 1:
		$sql =  "select * from ".DATABASE_SYSTEM_RESOURCE." where  resourceid =".$rid;
		break;
	case 3:
		$sql =  "select * from ".DATABASE_TENANT_RESOURCE." where  resourceid =".$rid;
		break;
	default:
		break;
	}
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["description"] =$result["description"];
			$temp_arr["groupid"] =$result["groupid"];
			if($type==3){
				$temp_arr["score"] =$result["score"];
				$rjson = json_decode($result["ruledata"], true);
				if($rjson['version'] != VERSION){
					$newModel  = getModelByID($rjson["modelid"]);
					$newJson = json_decode(json_encode($newModel->datajson), true);
					$rjson = getCommonMergeJson(1, $newJson, $rjson);
					//$rjson = getNewJson($rjson);
				}
				$temp_arr["ruledata"] = json_encode($rjson);
				$temp_arr["scope"] =$result["scope"];
			}
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}
}


//获取某一组资源
function getresourcebygroup($rid,$type)
{
	global $dsql,$arrs,$logger;

	switch ($type) {
	case 1:
		$sql =  "select * from ".DATABASE_SYSTEM_RESOURCE." where  groupid = ".$rid;
		break;
	case 3:
		$sql =  "select * from ".DATABASE_TENANT_RESOURCE." where groupid = ".$rid;
		break;
	}
	$qr = $dsql->ExecQuery($sql);
	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());

		$arrs["flag"]=0;

	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["description"] =$result["description"];
			$temp_arr["groupid"] =$result["groupid"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}

//根据组id获取资源信息
function getresourcebytype($typeid)
{
	global $dsql,$arrs,$logger;

	switch ($typeid) {
	case 1:
		//$sql = "select * from ".DATABASE_SYSTEM_RESOURCE." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
		$sql = "select * from ".DATABASE_SYSTEM_RESOURCE." order by resourceid";
		$totalCount ="select count(*) totalcount from ".DATABASE_SYSTEM_RESOURCE;
		break;
	case 2:
		$totalCount ="select count(*) totalcount from ".DATABASE_TENANT_MANAGE_RESOURCE;
		//$sql = "select * from ".DATABASE_TENANT_MANAGE_RESOURCE." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
		$sql = "select * from ".DATABASE_TENANT_MANAGE_RESOURCE." order by updatetime desc";
		break;
	}
	$qr2 = $dsql->ExecQuery($totalCount);
	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());

		$arrs["flag"]=0;
		echo json_encode($arrs);
		exit;

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

		}
		else
		{

			$temp_arr = array();
			while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
			{
				$temp_arr["resourceid"] = $r["resourceid"];
				$temp_arr["label"] = $r["label"];
				$temp_arr["description"] = $r["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}
}
//根据租户计费规则表信息获取资源数据
function getresourcebytenant($gid, $roleid)
{
	global $dsql,$arrs,$logger;
	$sql = "SELECT b.resourceid ,b.label FROM ".DATABASE_ACCOUNTING_RULE." AS a INNER JOIN ".DATABASE_TENANT_RESOURCE." AS b ON a.resourceid = b.resourceid WHERE a.tenantid = ".$gid." and a.roleid=".$roleid."";
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}



//根据租户计费规则表信息获取资源数据
function getresinfobytenant($gid, $arg_roleid)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	//计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	$totalCount ="select count(*) as totalcount from ".DATABASE_ACCOUNTING_RULE."  where tenantid =".$gid." and roleid=".$arg_roleid."";
	$sql = "select a.*,b.label as rolename from (select c.*,d.label as resourcename,d.description from (select * from ".DATABASE_ACCOUNTING_RULE."  where tenantid =".$gid." and roleid = ".$arg_roleid." limit ".$limit_cursor.",".$arg_pagesize.") as c inner join ".DATABASE_TENANT_RESOURCE." as d on c.resourceid = d.resourceid) as a inner join ".DATABASE_ROLE." as b on a.roleid = b.roleid where b.roletype=3";
	//echo $sql;
	$qrtotal = $dsql->ExecQuery($totalCount);
	if (!$qrtotal)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$result = $dsql->GetArray($qrtotal, MYSQL_ASSOC);
		$arrs["totalcount"]=$result["totalcount"];
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{

			$temp_arr = array();
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$temp_arr["id"] = $result["id"];
				$temp_arr["resourceid"] = $result["resourceid"];
				$temp_arr["resourcename"] = $result["resourcename"];
				$temp_arr["rolename"] = $result["rolename"];
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["description"] = $result["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}

}




//根据租户ID获取资源
function getresourcebytenantid($gid)
{
	global $arrs,$arg_pagesize,$arg_search_page,$dsql,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	$totalCount ="select count(*) as totalcount from ".DATABASE_PRODUCT_RESOURCE." where productid = (select productid from ".DATABASE_TENANT." where tenantid=".$gid.") order by updatetime desc";
	$sql="select * from ".DATABASE_TENANT_RESOURCE." as a inner join (select * from ".DATABASE_PRODUCT_RESOURCE." where productid = (select productid from ".DATABASE_TENANT." where tenantid=".$gid.") order by updatetime desc
		limit ".$limit_cursor.",".$arg_pagesize.") as b
		on a.resourceid=b.resourceid";

	$qr2 = $dsql->ExecQuery($totalCount);
	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
		{
			$arrs["totalcount"]=$result["totalcount"];
		}
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{

			$temp_arr = array();
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$temp_arr["resourceid"] = $result["resourceid"];
				$temp_arr["label"] = $result["label"];
				$temp_arr["description"] = $result["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}



}


/**
 * 根据租户ID获取租户所拥有的资源
 * @param unknown_type $gid
 */
function gettenantresrelation($gid)
{
	global $arrs,$arg_pagesize,$arg_search_page,$dsql,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	$totalCount ="select count(*) as totalcount  from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid=".$gid;
	$sql="select a.*,b.label,b.description,b.score from (select * from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid=".$gid." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize.") as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid";
	$qr2 = $dsql->ExecQuery($totalCount);

	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$totalCount} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
		{
			$arrs["totalcount"]=$result["totalcount"];
		}
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{

			$temp_arr = array();
			while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$temp_arr["resourceid"] = $result["resourceid"];
				$temp_arr["label"] = $result["label"];
				$temp_arr["description"] = $result["description"];
				$temp_arr["score"] = $result["score"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}

	}
}


//根据组id获取资源信息
function gettenantresource($tid,$group)
{
	global $dsql,$arrs,$logger;

	if($group==0)
	{
		$sql = "select * from tenant_resource as a inner join product_resource_relation as b on a.resourceid = b.resourceid where b.productid=(select productid from tenant where tenantid=".$tid.")";
	}
	else
	{
		$sql = "select * from tenant_resource as a inner join product_resource_relation as b on a.resourceid = b.resourceid where b.productid=(select productid from tenant where tenantid=".$tid.") and a.groupid=".$group;
	}
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["groupid"] = $result["groupid"];
			$temp_arr["description"] =$result["description"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}




//根据组id获取资源信息
/*
function gettenantresrel($tid,$group)
{
	global $dsql,$arrs,$logger;

	if($group==0)
	{
		$sql = "select a.*,b.groupid,b.label,b.description from ".DATABASE_TENANT_RESOURCE_RELATION." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid=".$tid;
	}
	else
	{
		$sql = "select a.*,b.groupid,b.label,b.description from ".DATABASE_TENANT_RESOURCE_RELATION." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid=".$tid." and b.groupid=".$group;
	}
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["groupid"] = $result["groupid"];
			$temp_arr["description"] =$result["description"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}
 */

function gettenantresrel($tid,$group)
{
	global $dsql,$arrs,$logger;

	if($group==0)
	{
		$sql = "select a.*,b.groupid,b.label,b.description from ".DATABASE_TENANT_RESOURCE_RELATION." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid=".$tid;
	}
	else
	{
		$sql = "select a.*,b.groupid,b.label,b.description from ".DATABASE_TENANT_RESOURCE_RELATION." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid=".$tid." and b.groupid=".$group;
	}
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{

		$temp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["groupid"] = $result["groupid"];
			$temp_arr["description"] =$result["description"];
			$temp_arr["updatetime"] = $result["updatetime"];
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}

}
//添加资源组
function addgroup()
{
	global $dsql,$arrs,$arrsdata,$logger;
	if(!Authorization::checkUserUseage(7,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$sql = "insert into ".DATABASE_RESOURCE_GROUP." (label,description,updatetime,grouptype)
			values('".$arrsdata->label."','".$arrsdata->description."',".time().",".$arrsdata->grouptype.")";

		$q = $dsql->ExecQuery($sql);
		if (!$q)
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
//修改资源组信息
function updategroup($userarr)
{
	global $dsql,$arrs,$logger;
	if(!Authorization::checkUserUseage(7,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$sql = "update ".DATABASE_RESOURCE_GROUP." set label='".$userarr->label."',description='".$userarr->description."'
			,updatetime=".time().",grouptype=".$userarr->grouptype."	        
			where groupid =".$userarr->id;

		$q = $dsql->ExecQuery($sql);
		if (!$q)
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

//获取组信息

function getallgroup()
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$totalCount ="select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP;
	$sql="select * from ".DATABASE_RESOURCE_GROUP." order by updatetime desc  limit ".$limit_cursor.",".$arg_pagesize;
	$qr2 = $dsql->ExecQuery($totalCount);
	if (!$qr2)
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
				$temp_arr["groupid"] = $r["groupid"];
				$temp_arr["label"] = $r["label"];
				$temp_arr["description"] = $r["description"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);;
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}



}

/*
 * 获取组信息
 */
function getgroup($type)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	$sql="select * from ".DATABASE_RESOURCE_GROUP." where grouptype = ".$type." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;
	$totalCount ="select count(*) totalcount from ".DATABASE_RESOURCE_GROUP." where grouptype=".$type;


	$qr2 = $dsql->ExecQuery($totalCount);
	if (!$qr2)
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
				$temp_arr["groupid"] = $r["groupid"];
				$temp_arr["label"] = $r["label"];
				$temp_arr["description"] = $r["description"];
				$temp_arr["grouptype"] = $r["grouptype"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}
	}


}


//绑定组列表
function getgroupnopage($type)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;

	$sql="select * from ".DATABASE_RESOURCE_GROUP." where grouptype = ".$type." order by updatetime desc";
	$totalCount ="select count(*) totalcount from ".DATABASE_RESOURCE_GROUP." where grouptype=".$type;


	$qr2 = $dsql->ExecQuery($totalCount);
	if (!$qr2)
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

		$temp_arr = array();
		while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
		{
			$temp_arr["groupid"] = $r["groupid"];
			$temp_arr["label"] = $r["label"];
			$temp_arr["description"] = $r["description"];
			$temp_arr["grouptype"] = $r["grouptype"];
			$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}
}

//根据名称查找资源
function getgroupbyname($gid,$name)
{
	global $dsql,$arrs,$logger;
	if($gid==0)
	{
		$sql = "select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP." where label='".$name."'";
	}
	else
	{
		//修改时执行此查询
		$sql = "select count(*) as totalcount from ".DATABASE_RESOURCE_GROUP." where label='".$name."' and groupid <>".$gid;
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
			$arrs["totalcount"] = $result["totalcount"];
		}

		if($arrs["totalcount"]>0)
		{
			$arrs["flag"]=1;
		}
		else
		{
			$arrs["flag"]=0;
		}
	}

}

//根据Id查找组信息
function getgroupbyid($gid)
{
	global $dsql,$arrs,$logger;

	$sql = "select * from ".DATABASE_RESOURCE_GROUP." where  groupid =".$gid;

	$qr = $dsql->ExecQuery($sql);

	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		//$tmp_arrs = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arrs["groupid"] = $result["groupid"];
			$tmp_arrs["label"] = $result["label"];
			$tmp_arrs["description"] = $result["description"];
			$tmp_arrs["updatetime"] = $result["updatetime"];
			$tmp_arrs["grouptype"] = $result["grouptype"];
			$arrs['children'][]=$tmp_arrs;
		}
		$arrs["flag"]=1;
	}

}



//删除资源信息
function deletegroup($id)
{
	global $dsql,$arrs,$logger;

	if(!Authorization::checkUserUseage(7,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		//删除组信息
		$delgroup = "delete from ".DATABASE_RESOURCE_GROUP." where groupid in (".$id.")";

		$q1 = $dsql->ExecQuery($delgroup);
		if(!$q1)
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
//检查资源是否在多个个产品中存在
/**
 * Enter description here ...
 * @param unknown_type $name
 * @param unknown_type $pid
 */
function checkresource($name,$pid)
{
	global $dsql,$arrs,$logger;
	//mb_convert_encoding($name,"utf-8","gb2312");
	$name = iconv("gb2312","utf-8",$name);
	$total=0;
	$sql = "select * from ".DATABASE_TENANT_RESOURCE." where label='".$name."'";
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else
	{
		$total = $dsql->GetTotalRow($qr);
		if($total>0)
		{
			$arrs["flag"]=1;
		}
		else
		{
			$arrs["flag"]=0;
		}
	}

}

//检查资源是否在多个产品中存在
function checkresourcebytype($name,$typeid)
{
	global $dsql,$arrs,$logger;

	if($typeid==1)
	{
		$sql ="select * from ".DATABASE_SYSTEM_RESOURCE." where label='".$name."'";
	}
	else
	{
		$sql ="select count(*) as cnt from ".DATABASE_TENANT_MANAGE_RESOURCE." where label ='".$name."'";
	}

	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else
	{
		$total=$dsql->GetTotalRow($qr);


		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$total = $result["cnt"];
		}

		if($total>0)
		{
			$arrs["flag"]=1;
		}
		else
		{
			$arrs["flag"]=0;
		}

	}
}



/**
 * 判断资源是否添加过
 * @param unknown_type $resid   资源ID
 * @param unknown_type $tid     租户ID
 */
function checkresexist($resid,$tid)
{
	global $dsql,$arrs,$logger;

	$sql = "select count(*) as cn from ".DATABASE_TENANT_RESOURCE_RELATION." where resourceid =".$resid." and tenantid =".$tid;
	$qr = $dsql->ExecQuery($sql);
	$num=0;
	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=2;
	}
	else
	{
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		$num=$result["cn"];

		if($num>0)
		{
			$arrs["result"]=1;
		}
		else
		{
			$arrs["result"]=0;
		}
	}



}

/***
 * 根据租户ID和模型ID获取计费规则信息
 * 参数：$modelid:模型ID,$tuserid:租户ID
 * 返回值：符合条件的数据集
 */
function getrulebytenant()
{
	global $dsql,$arrs,$arrsdata,$logger;
	$id = $_REQUEST['id'];
	$arrsrule = _getauthjson($id);
	echo json_encode($arrsrule);
	exit;
}

function _getauthjson($authid){
	global $dsql, $logger;
	$sql = "select *  from ".DATABASE_ACCOUNTING_RULE." where id={$authid}";
	$arrsrule;
	//echo $sql;
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{

		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		return false;
	}
	else
	{
		$num = $dsql->GetTotalRow($qr);
		if($num>0)
		{
			$result = $dsql->GetArray($qr, MYSQL_ASSOC);
			$rjson = json_decode($result["ruledata"],true);
			if($rjson['version'] != VERSION){
				$newModel  = getModelByID($rjson["modelid"]);
				$newJson = json_decode(json_encode($newModel->datajson), true);
				$rjson = getCommonMergeJson(1, $newJson, $rjson);
				//$rjson = getNewJson($rjson);
			}
			return $rjson;
		}
		else{
			return false;
		}
	}
}

/***
 *更新租户 的计费规则信息
 * 参数：$modelid:模型ID,$tuserid:租户ID
 * 返回值：更新成功返回:1,失败返回:0
 */
function updatetenantrule($modelid,$tuserid)
{
	global $dsql,$arrs,$logger;
	$postdata = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],true);
	$datajson = $postdata["jsondata"];
	$authid = $postdata["currauthid"];
	if(!Authorization::checkUserUseage(8,1,$tuserid))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此功能,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	$accountjson = _getaccountjson($tuserid,$modelid);
	if(empty($accountjson)){
		$arrs["result"]=false;
		$arrs["msg"]="未找到计费信息";
		echo json_encode($arrs);
		exit;
	}
	else{
		$oldauthjson = _getauthjson($authid);
		if($oldauthjson === false){
			$arrs["result"]=false;
			$arrs["msg"]="未找到权限信息";
			echo json_encode($arrs);
			exit;
		}
		foreach ($datajson["filter"] as $k=>$v){
			$chkres = checkLimit($accountjson["filter"][$k], $v["limit"], $oldauthjson["filter"][$k]["limit"]);
			if($chkres  == -1){
				$arrs["result"]=false;
				$arrs["msg"]=$v["label"]."不允许修改";
				echo json_encode($arrs);
				exit;
			}
			else if($chkres == 0){
				$arrs["result"]=false;
				$arrs["msg"]=$v["label"]."值超出范围";
				echo json_encode($arrs);
				exit;
			}
			else{
				//比较值
				//foreach($datajson["filter"] as $key=>$value){
				if($accountjson["filter"][$k]["limitcontrol"] == -1){
					continue;
				}
				$equal = false;
				if(count($v["limit"]) == 0 && count($oldauthjson["filter"][$k]["limit"]) == 0){
					$equal = true;
				}
				else if(count($v["limit"]) != count($oldauthjson["filter"][$k]["limit"])){
					$equal = false;
				}
				else{
					$eqcount = 0;
					foreach($v["limit"] as $key=>$value){
						$l_value = getLimitValue($accountjson["filter"][$k]["datatype"],$value);
						foreach ($oldauthjson["filter"][$k]["limit"] as $i=>$item){
							if($item["repeat"] == $value["repeat"]){
								$acc_value = getLimitValue($accountjson["filter"][$k]["datatype"],$item);
								if($l_value == $acc_value){
									$eqcount++;
									break;                    
								}
							}
						}
					}
					$equal = $eqcount == count($v["limit"]);
				}
				if($equal == true){
					if($oldauthjson["filter"][$k]["allowcontrol"] != $v["allowcontrol"]){
						$equal = false;
					}
				}
				if($equal == false){
					if($accountjson["filter"][$k]["limitcontrol"] >0){
						$accountjson["filter"][$k]["limitcontrol"] --;
					}
				}
				//}
			}
		}
		if(isset($accountjson['download_DataLimit_limitcontrol']) && 
			($accountjson['download_DataLimit_limitcontrol'] == -1 || $accountjson['download_DataLimit_limitcontrol'] > 0)){
				if($accountjson['download_DataLimit_limitcontrol'] > 0){
					$accountjson['download_DataLimit_limitcontrol'] --;
				}
			}
		else{
			$arrs["result"]=false;
			$arrs["msg"]="下载数据量不允许修改";
			echo json_encode($arrs);
			exit;
		}
		if(isset($accountjson['download_FieldLimit_limitcontrol']) && 
			($accountjson['download_FieldLimit_limitcontrol'] == -1 || $accountjson['download_FieldLimit_limitcontrol'] > 0)){
				if($accountjson['download_FieldLimit_limitcontrol'] > 0){
					$accountjson['download_FieldLimit_limitcontrol'] --;
				}
			}
		else{
			$arrs["result"]=false;
			$arrs["msg"]="下载字段不允许修改";
			echo json_encode($arrs);
			exit;
		}
		$sql = "update ".DATABASE_ACCOUNTING_RULE." set ruledata='".jsonEncode4DB($datajson)."' ,updatetime=".time()." where id=".$authid;
		$qr = $dsql->ExecQuery($sql);
		if (!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["result"]=false;
			$arrs["msg"]="操作失败";
			echo json_encode($arrs);
			exit;
		}
		else
		{
			$updatesql = "update ".DATABASE_TENANT_RESOURCE_RELATION." set content='".jsonEncode4DB($accountjson)."' where tenantid={$tuserid} and resourceid={$modelid}";
			$upateqr = $dsql->ExecQuery($updatesql);
			if (!$upateqr)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$updatesql} ".$dsql->GetError());
				$arrs["result"]=false;
				$arrs["msg"]="更新计费信息失败";
				echo json_encode($arrs);
				exit;
			}
			else{
				$arrs["result"]=true;
				$arrs["msg"]="设置成功";
				echo json_encode($arrs);
				exit;
			}
		}

	}

}


/**
 *
 * 根据产品ID获取资源
 * @param unknown_type $pid
 */
function getresourcebyproduct($pid,$gid,$type)
{
	global $dsql,$arrs,$arrsdata,$logger;

	switch($type)
	{
	case 0:
		if($gid==0)
		{
			$sql = "select a.* from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b on a.resourceid = b.resourceid where b.productid =".$pid." and a.scope = 0";
		}
		else
		{
			$sql = "select a.* from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b on a.resourceid = b.resourceid where b.productid =".$pid." and a.groupid=".$gid." and a.scope = 0";
		}
		break;
	case 1:
		if($gid==0)
		{
			$sql = "select a.* from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b on a.resourceid = b.resourceid where b.productid =".$pid." and (a.scope = 0 or a.scope=1)";
		}
		else
		{
			$sql = "select a.* from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b on a.resourceid = b.resourceid where b.productid =".$pid." and a.groupid=".$gid." and (a.scope = 0 or a.scope=1)";
		}
		break;
	case 2:
		if($gid==0)
		{
			$sql = "select a.* from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b on a.resourceid = b.resourceid where b.productid =".$pid." and a.scope = 0 or a.scope=2";
		}
		else
		{
			$sql = "select a.* from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b on a.resourceid = b.resourceid where b.productid =".$pid." and a.groupid=".$gid." and a.scope = 0 or a.scope=2";
		}
		break;
	default:
	}
	$qr = $dsql->ExecQuery($sql);

	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$tmp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arr["resourceid"] = $result["resourceid"];
			$tmp_arr["label"] = $result["label"];
			$tmp_arr["score"] = $result["score"];
			$tmp_arr["scope"] = $result["scope"];
			$tmp_arr["groupid"] = $result["groupid"];
			$rjson = json_decode($result["ruledata"],true);
			if($rjson['version'] != VERSION){
				$newModel  = getModelByID($rjson["modelid"]);
				$newJson = json_decode(json_encode($newModel->datajson), true);
				$rjson = getCommonMergeJson(1, $newJson, $rjson);
				//$rjson = getNewJson($rjson);
			}
			$tmp_arr["ruledata"] = $rjson;
			$arrs[CHILDS][] = $tmp_arr;
		}
		$arrs["flag"]=1;
	}

}//end function

/**
 *
 * 根据租户ID获取资源以及资源积分
 * @param  $tid
 */
function gettenantres($tid)
{
	global $dsql,$arrs,$logger;
	$totalscore=0;
	$sql = "select a.*,b.score,b.label from ".DATABASE_TENANT_RESOURCE_RELATION." as a inner join ".DATABASE_TENANT_RESOURCE." as b on a.resourceid = b.resourceid where a.tenantid  = ".$tid;
	//$sql = "select * from ".DATABASE_TENANT_RESOURCE." where resourceid not in (select resourceid from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid = {$tid})";
	$qr = $dsql->ExecQuery($sql);

	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$num=$dsql->GetTotalRow($qr);
		if($num>0)
		{
			$tmp_arr = array();
			$tmp_arr_score = array();
			if($num>1)
			{
				while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
				{
					$tmp_arr["resourceid"] = $result["resourceid"];
					$tmp_arr["label"] = $result["label"];
					$tmp_arr["score"] = $result["score"];
					$tmp_arr_score["resourceid"] = $result["resourceid"];
					$tmp_arr_score["score"] = $result["score"];
					$totalscore+=$result["score"];
					$arrs[CHILDS][] = $tmp_arr;

				}
			}
			else
			{
				$result = $dsql->GetArray($qr, MYSQL_ASSOC);
				$tmp_arr["resourceid"] = $result["resourceid"];
				$tmp_arr["label"] = $result["label"];
				$tmp_arr["score"] = $result["score"];
				$tmp_arr_score["resourceid"] = $result["resourceid"];
				$tmp_arr_score["score"] = $result["score"];
				$totalscore+=$result["score"];
				$arrs[CHILDS][] = $tmp_arr;

			}

		}
		$arrs["totalscore"] = $totalscore;
	}



}//end function



/**
 *
 * 设置租户资源关系
 * 参数:post 数据
 */
function addtenantresource()
{
	global $dsql,$arrs,$arrsdata,$logger;


	$sqldel;
	$sql;
	$arrsdata = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],true);
	if(!Authorization::checkUserUseage(2,1,$arrsdata['tenantid']))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$lastprice = computeJsonPrice($arrsdata['ruledata'],$arrsdata['tenantid']);
		if(!$lastprice){
			$logger->error(__FILE__." func:".__FUNCTION__." lastprice:{$lastprice} ");
			$arrs["result"]=false;
			$arrs["msg"]="计算价格失败!";
			return false;
		}
		else{
			$surprice = getTenantBalance($arrsdata['tenantid']);
			if(!$surprice){
				$logger->error(__FILE__." func:".__FUNCTION__." 获取剩余积分失败:{$surprice} ");
				$arrs["result"]=false;
				$arrs["msg"]="获取剩余积分失败!";
				return false;
			}
			else{
				if($lastprice > $surprice){
					$logger->error(__FILE__." func:".__FUNCTION__." 剩余积分不足:{$surprice} ");
					$arrs["result"]=false;
					$arrs["msg"]="剩余积分不足!";
					return false;
				}
			}
		}
		$sql="insert into ".DATABASE_TENANT_RESOURCE_RELATION." (resourceid,tenantid,updatetime,content,lastprice) values(".$arrsdata['resourceid'].",".$arrsdata['tenantid'].",".time().",'".jsonEncode4DB($arrsdata['ruledata'])."',{$lastprice})";


		$qrinsert = $dsql->ExecQuery($sql);
		if(!$qrinsert)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["result"]=false;
			$arrs["msg"]="添加失败!";
		}
		else
		{
			$arrs["result"]=true;
			$arrs["msg"]="添加成功，是否继续添加!";

		}
	}

}// end function


/**
 *
 * 绑定租户资源静态数组
 *
 */
function bindresourceinfo()
{
	global $dsql,$arrs,$arrsdata,$arrmodel,$logger;
	//去除虚拟数据源,不作为资源添加
	$available = array();
	foreach($arrmodel as $ai=>$aitem){
		if($aitem->modelid != 6){
			$available[] = $aitem;
		}
	}
	$json_str2 = json_encode($available);
	echo $json_str2;
}// end function


/**
 *
 * 绑定系统资源静态数组
 *
 */
function bindsysresourceinfo()
{
	global $dsql,$arrs,$arrsdata,$arrsysmodel,$logger;
	$json_str2 = json_encode($arrsysmodel);
	echo $json_str2;


}// end function

//获取资源的最大ID
function getresourcemaxid($type)
{
	global $dsql,$arrs,$arrsdata,$arrmodel,$logger;

	if($type==1)
	{
		$sql = "select max(resourceid) as modelid from ".DATABASE_SYSTEM_RESOURCE;
	}
	if($type==2)
	{
		$sql = "select max(resourceid) as modelid from ".DATABASE_TENANT_MANAGE_RESOURCE;
	}
	$qr = $dsql->ExecQuery($sql);
	//$num=$dsql->GetTotalRow($qr);

	if(!qr)
	{
		$arrs["result"]=0;
	}
	else
	{
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		$arrs["maxid"] = $result["modelid"];
	}

}// end function


function getalltenantresource()
{
	global $dsql,$arrs,$logger;

	$sql = "select * from ".DATABASE_TENANT_RESOURCE;

	$qr = $dsql->ExecQuery($sql);

	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$tmp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arr["modelid"] = $result["resourceid"];
			$arrs[CHILDS][] = $tmp_arr;
		}
		$arrs["flag"]=1;
	}
}



function getallsystemresource()
{
	global $dsql,$arrs,$logger;

	$sql = "select * from ".DATABASE_SYSTEM_RESOURCE;

	$qr = $dsql->ExecQuery($sql);

	if(!$qr)
	{

		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$tmp_arr = array();
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tmp_arr["modelid"] = $result["resourceid"];
			$arrs[CHILDS][] = $tmp_arr;
		}
		$arrs["flag"]=1;
	}
}//end function


/**
 * 根据角色获取资源
 *
 * 参数:$roleid角色ID,$roletype 角色类型,$userid 用户ID
 *
 */
function getresourcebyrole($roleid,$roletype)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	if(!Authorization::checkUserUseage(4,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;

		//如果roleid为0则显示当用户所有可用资源，反之只列出某一角色下资源
		if($roleid==0)
		{
			$totalCount ="select count(*) as totalcount from (select a.*,b.resourceid from ".DATABASE_ROLE." as a inner join ".DATABASE_ROLE_RESOURCE_RELATION." as b on a.roleid = b.roleid ) as c";
			$sql = "select c.*,d.haschild,d.label as modelname,d.description from (select a.roleid,a.label as rolename,b.resourceid,b.childid from (select * from ".DATABASE_ROLE." limit ".$limit_cursor.",".$arg_pagesize.") as a inner join ".DATABASE_ROLE_RESOURCE_RELATION." as b on a.roleid = b.roleid group by resourceid) as c inner join ".DATABASE_SYSTEM_RESOURCE." as d on c.resourceid = d.resourceid";
		}
		else
		{
			$totalCount ="select count(*) as totalcount from (select DISTINCT(resourceid) from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid.") as c";
			$sql="select * from (select c.*,d.label as rolename from (select a.*,b.label as modelname,b.description,b.haschild from  (select DISTINCT(resourceid),roleid,updatetime from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid." limit ".$limit_cursor.",".$arg_pagesize.") as a inner join ".DATABASE_SYSTEM_RESOURCE." as b on a.resourceid = b.resourceid) as c inner join ".DATABASE_ROLE." as d on c.roleid = d.roleid where d.roletype=1) as e group by resourceid";
		}
		$qr2 = $dsql->ExecQuery($totalCount);
		//echo $totalCount."<br/>.";
		//echo $sql;

		if(!$qr2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$result = $dsql->GetArray($qr2, MYSQL_ASSOC);
			$arrs["totalcount"] = $result["totalcount"];
			$qr = $dsql->ExecQuery($sql);

			if (!$qr)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else
			{

				$temp_arr = array();
				while ($r = $dsql->GetArray($qr, MYSQL_ASSOC))
				{
					$temp_arr["resourceid"] = $r["resourceid"];
					$temp_arr["modelname"] = $r["modelname"];
					$temp_arr["description"] = $r["description"];
					//$temp_arr["label"] = $r["label"];
					$temp_arr["haschild"] = $r["haschild"];
					$temp_arr["rolename"] = $r["rolename"];
					$temp_arr["roleid"] = $r["roleid"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["flag"]=1;
			}
		}


	}

}//end function






/**
 * 根据角色获取资源
 *
 * 参数:$userid 用户ID
 *
 */
function getresourcebyuser($userid,$tid)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;

	if(!Authorization::checkUserUseage(4,1,null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模块,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;




		//如果roleid为0则显示当用户所有可用资源，反之只列出某一角色下资源
		if($tid==-1)
		{
			$totalCount ="select count(*) as totalcount from(select a.* from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join ".DATABASE_USER_ROLE_MAPPIMG." as b on a.roleid = b.roleid where b.userid =".$userid.") as c";
			$sql="select  e.*,f.label as modelname,f.description from (select  c.*,d.label as rolename from (select a.* from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join ".DATABASE_USER_ROLE_MAPPIMG." as b on a.roleid = b.roleid where b.userid =".$userid." LIMIT ".$limit_cursor.",".$arg_pagesize."
			) as c inner join ".DATABASE_ROLE." as d on d.roleid  = c.roleid where d.roletype=1) as e inner join ".DATABASE_SYSTEM_RESOURCE." as f on e.resourceid = f.resourceid";

		}
		else
		{

			$totalCount = "select count(*) as totalcount from(select * from ".DATABASE_ACCOUNTING_RULE." as a inner join ".DATABASE_USER_ROLE_MAPPIMG." as b on a.roleid =b.roleid where b.userid=".$userid." and a.tenantid=".$tid.") as c";
			$sql = "select e.*,f.label as rolename from (select c.*,d.label as modelname,d.description from (select * from ".DATABASE_ACCOUNTING_RULE." as a inner join  ".DATABASE_USER_ROLE_MAPPIMG." as b on a.roleid = b.roleid where b.userid = ".$userid." AND a.tenantid=".$tid." limit ".$limit_cursor.",".$arg_pagesize.") as c inner join ".DATABASE_TENANT_RESOURCE." as d  on c.resourceid = d.resourceid) as e inner join ".DATABASE_ROLE." as f on e.roleid = f.roleid where f.roletype=3";

		}
		$qr2 = $dsql->ExecQuery($totalCount);
		//echo $totalCount."<br/>.";
		//echo $sql;

		if(!$qr2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$result = $dsql->GetArray($qr2, MYSQL_ASSOC);
			$arrs["totalcount"] = $result["totalcount"];

			$qr = $dsql->ExecQuery($sql);
			if (!$qr)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else
			{

				$temp_arr = array();
				while ($r = $dsql->GetArray($qr, MYSQL_ASSOC))
				{
					$temp_arr["resourceid"] = $r["resourceid"];
					$temp_arr["modelname"] = $r["modelname"];
					$temp_arr["description"] = $r["description"];
					//$temp_arr["label"] = $r["label"];
					$temp_arr["rolename"] = $r["rolename"];
					$temp_arr["roleid"] = $r["roleid"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
				$arrs["flag"]=1;
			}
		}


	}

}//end function



/**
 * 根据角色获取资源
 *
 * 参数:$roleid角色ID,$roletype 角色类型,$userid 用户ID
 *
 */
function getresourcebyuserrole($roleid,$roletype,$userid)
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;



	//如果roleid为0则显示当用户所有可用资源，反之只列出某一角色下资源
	if($roleid==0)
	{
		$totalCount ="select count(*) as totalcount  from (select a.* from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join ".DATABASE_USER_ROLE_MAPPIMG." as b on a.roleid = b.roleid where b.userid=".$userid." and b.roletype=1) as c";

		$sql="select e.*,f.haschild,f.label as modelname,f.description from (select c.*,d.label as rolename from (select a.* from ".DATABASE_ROLE_RESOURCE_RELATION." as a inner join ".DATABASE_USER_ROLE_MAPPIMG." as b on a.roleid = b.roleid where b.userid=".$userid." and b.roletype=1 limit ".$limit_cursor.",".$arg_pagesize.") as c inner join ".DATABASE_ROLE." as d on c.roleid = d.roleid where d.roletype=1) as e inner join ".DATABASE_SYSTEM_RESOURCE." as f on e.resourceid = f.resourceid";
	}
	else
	{

		$totalCount ="select count(*) as totalcount from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid;
		$sql="select c.*,d.label as rolename from (select a.*,b.label as modelname,b.haschild,b.description from (select * from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid=".$roleid." limit ".$limit_cursor.",".$arg_pagesize.") as a inner join  ".DATABASE_SYSTEM_RESOURCE." as b on a.resourceid = b.resourceid) as c inner join ".DATABASE_ROLE." as d on c.roleid = d.roleid where d.roletype=".$roletype;
	}
	$qr2 = $dsql->ExecQuery($totalCount);
	//	echo $totalCount;
	//	echo $sql;

	if(!$qr2)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$result = $dsql->GetArray($qr2, MYSQL_ASSOC);
		$arrs["totalcount"] = $result["totalcount"];

		$qr = $dsql->ExecQuery($sql);

		if(!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{

			$temp_arr = array();
			while ($r = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				$temp_arr["resourceid"] = $r["resourceid"];
				$temp_arr["modelname"] = $r["modelname"];
				$temp_arr["description"] = $r["description"];
				$temp_arr["label"] = $r["label"];
				$temp_arr["haschild"] = $r["haschild"];
				$temp_arr["rolename"] = $r["rolename"];
				$temp_arr["roleid"] = $r["roleid"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
				$arrs[CHILDS][] = $temp_arr;
			}
			$arrs["flag"]=1;
		}

	}
}

function _getaccountjson($tenantid,$resourceid){
	global $logger,$dsql;
	$sql = "select content from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid={$tenantid} and resourceid = {$resourceid}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} error:".$dsql->GetError());
		return null;
	}
	else{
		$rs = $dsql->GetArray($qr);
		if($rs){
			$rjson = json_decode($rs['content'],true);
			if($rjson['version'] != VERSION){
				$newModel  = getModelByID($rjson["modelid"]);
				$newJson = json_decode(json_encode($newModel->datajson), true);
				$rjson = getCommonMergeJson(1, $newJson, $rjson);
				//$rjson = getNewJson($rjson);
			}
			return $rjson;
		}
		else{
			return null;
		}
	}
}
function getlatestjson(){
	global $dsql,$arrs,$logger;
	$modelid = $_REQUEST["modelid"]; //模型ID
	$newModel  = getModelByID($modelid);

	$sql =  "select * from ".DATABASE_TENANT_RESOURCE." where  resourceid =".$modelid;
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else{
		while($result=$dsql->GetArray($qr, MYSQL_ASSOC)){
			$rjson = json_decode($result["ruledata"], true);
			$newJson = json_decode(json_encode($newModel->datajson), true);
			$rjson = getCommonMergeJson(0, $newJson, $rjson); //获取最新的json,同时把价格信息赋值给最新json
		}
	}
	$result['data']["datajson"] = $rjson;
	echo json_encode($result);
	exit;
}
function getaccountjson(){
	global $logger,$dsql;
	$tid = $_REQUEST['tid'];//租户ID
	$resid = $_REQUEST['resourceid'];//资源ID
	$result;
	if(!isset($tid) || !isset($resid)){
		$result['result'] = false;
		$result['msg'] = '参数错误';
		$logger->error(__FILE__." func:".__FUNCTION__." params is empty: tid:{$tid},resid:{$resid}");
		echo json_encode($result);
		exit;
	}
	$rs = _getaccountjson($tid,$resid);
	if(empty($rs)){
		$result['result'] = false;
		$result['msg'] = '获取失败';
		echo json_encode($result);
		exit;
	}
	else{
		$result['result'] = true;
		$result['data'] = $rs;
		echo json_encode($result);
		exit;
	}
}

function updateaccountjson(){
	global $logger,$dsql;
	$postdata = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],true);
	$datajson = $postdata["jsondata"];
	$tenantid = $postdata['tenantid'];
	if(!Authorization::checkUserUseage(2,1,$tenantid))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此功能,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	$resourceid =  $postdata['modelid'];
	$sql = "update ".DATABASE_TENANT_RESOURCE_RELATION." set content='".jsonEncode4DB($datajson)."' where tenantid={$tenantid} and resourceid = {$resourceid}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} error:".$dsql->GetError());
		$result['result'] = false;
	}
	else{
		$result['result'] = true;
	}
	echo json_encode($result);
	exit;
}


if (empty($_GET))
{
	if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
	{

		global $arrsdata;
		$arrsdata = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);

		//$arrsdata->ruledata=json_decode($arrsdata->ruledata);
		$arg_type = isset($arrsdata->type) ? $arrsdata->type : '';
		$arg_rid = isset($arrsdata->modelid) ? $arrsdata->modelid : '';
		if($arg_rid == ''){
			$arg_rid = isset($arrsdata->resourceid) ? $arrsdata->resourceid: '';
		}
		$arg_id = isset($arrsdata->tenantid) ? $arrsdata->tenantid : '';
	}
}
else
{
	$arg_search_page = isset($_GET["currpage"]) ? $_GET["currpage"] : 1;
	$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
	$arg_id = isset($_GET["gid"]) ? $_GET["gid"] : 0;
	$arg_uid = isset($_GET["userid"]) ? $_GET["userid"] : 0;//用户ID
	$arg_rid = isset($_GET["rid"]) ? $_GET["rid"] : 0; //资源ID
	$arg_roleid = isset($_GET["roleid"]) ? $_GET["roleid"] : 0; //角色ID
	$arg_name = isset($_GET["name"]) ? $_GET["name"] : null;
	$arg_type = isset($_GET["type"]) ? $_GET["type"] : null;
	$arg_scopetype = isset($_GET["scopetype"]) ? $_GET["scopetype"] : 0;
	$arg_pid = isset($_GET["pid"]) ? $_GET["pid"] : 0;
	$arg_roletype = isset($_GET["roletype"]) ? $_GET["roletype"] : 0;
	$arg_groupid = isset($_GET["groupid"]) ? $_GET["groupid"] : 0;

}

switch ($arg_type)
{
case TYPE_ADDRESOURCE:
	addresource();
	break;
case TYPE_ADDGROUP:
	addgroup();
	break;
case TYPE_UPDATERESOURCE:
	updateresource();
	break;
case TYPE_UPDATEGROUP:
	updategroup($arrsdata);
	break;
case TYPE_GETRESOURCE:
	getresource($arg_id);
	break;
case TYPE_GETRESOURCEBYNAME:
	getresourcebyname($arg_name, $arg_scopetype);
	break;
case TYPE_GETGROUPBYNAME:
	getgroupbyname($arg_id,$arg_name);
	break;
case TYPE_GETGROUP:
	getgroup($arg_id);
	break;
case TYPE_GETGROUPNOPAGE:
	getgroupnopage($arg_id);
	break;
case TYPE_GETALLGROUP:
	getallgroup();
	break;
case TYPE_DELETEGROUP:
	deletegroup($arg_id);
	break;
case TYPE_DELETERESOURCE:
	deleteresource($arg_rid,$arg_id);
	break;
case TYPE_GETRESOURCEBYID:
	getresourcebyid($arg_id,$arg_scopetype);
	//getresourcebyid($arg_id,$arg_scopetype);
	break;
case TYPE_GETGROUPBYID:
	getgroupbyid($arg_id);
	break;
case TYPE_GETRESOURCEBYTYPE:
	getresourcebytype($arg_id);
	break;
case TYPE_CHECKUSERRESOURCE:
	checkuserresource($arg_rid,$arg_id);
	break;
case TYPE_CHECKRESOURCE:
	checkresource($arg_name,$arg_id);
	break;
case TYPE_CHECKRESOURCEBYTYPE:
	checkresourcebytype($arg_name,$arg_id);
	break;
case TYPE_GETTENANTRESOURCE:
	gettenantresource($arg_id,$arg_uid);
	break;
case TYPE_SETTENANTRESOURCE:
	settenantresource();
	break;
case TYPE_GETRESOURCEBYTENANT:
	getresourcebytenant($arg_id, $arg_roleid);
	break;
case TYPE_GETRESOURCEBYTENANTID:
	getresourcebytenantid($arg_id);
	break;
case TYPE_CHECKADMINTYPE:
	checkadmintype($arg_id, $arg_rid);
	break;
case TYPE_GETRESOURCEBYGROUP:
	getresourcebygroup($arg_id, $arg_rid);
	break;
case TYPE_GETRULEBYTENANT:
	getrulebytenant();
	break;
case TYPE_UPDATETENANTRULE:
	updatetenantrule($arg_rid,$arg_id);
	break;
case TYPE_GETRESOURCEBYPRODUCT:
	getresourcebyproduct($arg_pid,$arg_groupid,$arg_scopetype);
	break;
case TYPE_GETTENANTRES:
	gettenantres($arg_id);
	break;
case TYPE_ADDTENANTRESOURCE:
	addtenantresource();
	break;
case TYPE_BINDRESOURCEINFO:
	bindresourceinfo();
	break;
case TYPE_BINDSYSRESOURCEINFO:
	bindsysresourceinfo();
	break;
case TYPE_GETALLTENANTRESOURCE:
	getalltenantresource();
	break;
case TYPE_GETRESOURCEMAXID:
	getresourcemaxid($arg_id);
	break;
case TYPE_GETRESINFOBYTENANT:
	getresinfobytenant($arg_id, $arg_roleid);
	break;
case TYPE_GETTENANTRESREL:
	gettenantresrel($arg_id,$arg_uid);
	break;
case TYPE_CHECKRESEXIST:
	checkresexist($arg_id,$arg_uid);
	break;
case TYPE_GETTENANTRESRELATION:
	gettenantresrelation($arg_id);
	break;
case TYPE_GETALLSYSTEMRESOURCE:
	getallsystemresource();
	break;
case TYPE_GETRESOURCEBYROLE:
	getresourcebyrole($arg_rid,$arg_roletype);
	break;
case TYPE_DELTENANTRESOURCE:
	deletetenantresource($arg_rid,$arg_id);
	break;
case TYPE_DELRESBYTENANTROLE:
	delresbytenantrole($arg_rid,$arg_id);
	break;
case TYPE_GETRESOURCEBYUSER:
	getresourcebyuser($arg_uid,$arg_id);
	break;
case TYPE_GETACCOUNTJSON:
	getaccountjson();
	break;
case TYPE_GETLATESTJSON:
	getlatestjson();
	break;
case TYPE_UPDATEACCOUNTJSON:
	updateaccountjson();
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



