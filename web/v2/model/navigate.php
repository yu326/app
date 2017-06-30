<?php
include_once('includes.php');
include_once("datatableresult.php");
include_once("model_config.php");
session_start();

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
define('TYPE_GETAll', 'getallnav');    //获取所有用户tag
define('TYPE_ADDNAVIGATE', 'addnavigate');    //添加tag
define('TYPE_UPDATE', 'updatenav');    //修改模板
define('TYPE_DELETE', 'deletenav');    //删除模板
define('TYPE_SEARCH', 'searchnav');    //查询模板
define('TYPE_GETBYTID', 'getnavbytid');  //根据ID查询模板
define('TYPE_GETBYID', 'getnavbyid');
define('TYPE_CHECKBYID', 'chencknavbyid');    //
define('TYPE_CHECKEXIST', 'checktexist');
define('TYPE_GETBYTIDALL', 'getnavbytidall');     //
define('TYPE_GETLEFTMENU', 'getleftmenu');
define('TYPE_SETTENANTMODEL', 'settenantmodel');
define('TYPE_GETINSTANCE', 'getinstance');//根据实例Id获取标签实例
define('TYPE_GETRESOURCEBYMODELID', 'getresourcebymodelid');//根据实例Id获取标签实例
define('TYPE_GETINSTANCEBYMODELID', 'getinstancebymodelid');//根据模型id和导航id获取json


$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_name;
$arg_tid;
$arg_channelid;
$arg_typeid;
$arg_modelid;

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

//添加导航信息
function addnavigate()
{
	global $dsql,$arrs,$arrsdata;

	$sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (tenantid,label,pageid,modelid,level,orderid,parentid,modeltype,updatetime) values
	(".$arrsdata["tenantid"].",'".$arrsdata["label"]."',0,0,".$arrsdata["level"].",0,".$arrsdata["parentid"].",".$arrsdata["modeltype"].",".time().")";
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
//店家租户模型实例
function add()
{
	global $dsql,$arrs,$arrsdata;

	$sql = "insert into ".DATABASE_CUSTOMER_NAVIGATE." (tenantid,label,pageid,modelid,level,orderid,parentid,modeltype,updatetime) values
	(".$arrsdata["tenantid"].",'".$arrsdata["label"]."',0,0,".$arrsdata["level"].",0,".$arrsdata["parentid"].",".$arrsdata["modeltype"].",".time().")";
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


/*
 * 获取模板
 */
function gettemplate()
{
	global $dsql,$arrs;

	$pageid=0;
	$sql="select * from ".DATABASE_CUSTOMER_NAVIGATE." order by updatetime desc";

	if(!$qr){
		throw new Exception(TYPE_PAGE."- gettemplate()-".$sql."-".mysql_error());
	}
	else{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["id"] = $result["id"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["tenantid"] = $result["tenantid"];
			$temp_arr["pageid"] = $result["pageid"];
			$temp_arr["homepage"] = $result["homepage"];
			$temp_arr["updatetime"] = $result["updatetime"];

			$arrs[CHILDS][] = $temp_arr;
		}
	}
}
//根据租户ID获取模块信息
function getbytid($id)
{
	global $dsql,$arrs;
	$num=0;

	$sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where tenantid=".$id." order by parentid";
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
				$temp_arr["id"] = $result["id"];
				$temp_arr["label"] = $result["label"];
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["pageid"] = $result["pageid"];
				$temp_arr["level"] = $result["level"];
				$temp_arr["parentid"] = $result["parentid"];
				$temp_arr["modeltype"] = $result["modeltype"];
				$temp_arr["orderid"] = $result["orderid"];
				$temp_arr["modelid"] = $result["modelid"];
				$temp_arr["homepage"] = $result["homepage"];
				$temp_arr["updatetime"] = $result["updatetime"];

				$arrs[CHILDS][] = $temp_arr;
			}
		}
	}
}


//将列表数据转换为json输出
function getleftmenu($id,$channelid,$typeid, $arg_modelnavtype)
{
	global $dsql,$arrs,$arrmodel;
	$num=0;
	$num2=0;
	//var_dump($arrmodel);
	//判断是否是租户用户
	//$tmp  = array_search($arrmodel);
	switch($typeid){
		case 1:
			//-------处理POST/GET请求--------------
			if(!empty($arg_modelnavtype )){
				$result = array('result'=>true,'msg'=>'','data'=>null);
				switch($arg_modelnavtype ){
					case ARG_TYPE_GETMODELBYCHANNEL:
						$cid = $channelid;
						if(empty($cid)){
							$result['result'] = false;
							$result['msg'] = '参数错误';
						}
						else{
							$r = getModelsByChannel($cid);
							if(empty($r)){
								$result['result'] = false;
								$result['msg'] = '未获取到数据';
							}
							else{
								$result['data'] = $r;
							}
						}

						echo json_encode($result);
						break;
					case ARG_TYPE_GETNAVBYCHANNEL:
						$cid = $channelid;
						if(empty($cid)){
							$result['result'] = false;
							$result['msg'] = '参数错误';
						}
						else{
							$r = getDataPlatformNav($cid);
							if(empty($r)){
								$result['result'] = false;
								$result['msg'] = '未获取到数据';
							}
							else{
								$result['data'] = $r;
							}
						}
						echo json_encode($result);
						break;
					case ARG_TYPE_GETMODELJS:
						getModelJS();
						break;
					default:
						$result['result'] = false;
						$result['msg'] = 'arg type is error';
						echo json_encode($result);
						break;
				}
			}
			break;
		case 2:
			if(isset($_SESSION["tenantid"]) && $_SESSION["tenantid"]!=null && $_SESSION["tenantid"]!="")
			{
				//$sql="select a.*,b.id as instanceid from ".DATABASE_CUSTOMER_NAVIGATE." as a left join ".DATABASE_TENANT_TAGINSTANCT." as b on a.id = b.navid where a.tenantid=".$_SESSION["tenantid"]." and parentid=0 order by orderid desc";
				$sql="select *  from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$_SESSION["tenantid"]." and parentid=0";
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

							//$arrs[CHILDS][] = $temp_arr;
							//$sqlchild="select a.*,b.id as instanceid from ".DATABASE_CUSTOMER_NAVIGATE." as a left join ".DATABASE_TENANT_TAGINSTANCT." as b on a.id = b.navid  where a.tenantid=".$_SESSION["tenantid"]." and  parentid = ".$result["id"]." order by orderid desc";

							$sqlchild = "select * from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$_SESSION["tenantid"]." and  parentid = ".$result["id"];
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
							$nav = new Nav($result["id"],$result["level"],$result["label"],$result["modeltype"],$temp_arr2["isdefault"], $arrinstance,$result["modelid"]);
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
										//$tmp_arr = new array();
										$tmp_arr['id'] = $r["id"];
										$tmp_arr['modelname'] = $r["label"];
										$tmp_arr['modelid'] = $r["modelid"];

										//$temp_arr2["isdefault"] = 0;
										//$temp_arr2["isnav"] = 0;

										if($r["ishomepage"]==1)
										{
											$tmp_arr["isdefault"] = true;
										}
										else
										{
											$tmp_arr["isdefault"] = false;
										}
										$arrinstance2 = getinstanceid($r["id"]);
										//创建二级菜单
										$nav->addChild(new Nav($r["id"],$r["level"],$r["label"],$r["modeltype"],$tmp_arr["isdefault"], $arrinstance2,$r["modelid"]));
									}

								}
							}

							$arrs['data'][]=$nav;

						}
					}
				}
			}
			break;
		default:
			break;
	}
}



//根据导航ID或modelID获取模型实例
/*************************************
 * 参数：$modelid 资源ID
 *       $navid   导航ID
 * 说明：当$navid=0时只查询$modelid相等的资源
 */
function getinstancebymodelid($modelid,$navid)
{

	global $dsql,$arrs;

	$sql;
	//首先通过session判断是否是租户
	if($_SESSION["tenantid"]!=0&&$_SESSION["tenantid"]!=null&&$_SESSION["tenantid"]!="")
	{
		if($navid==0)
		{
			$sql = "select *  from ".DATABASE_TENANT_TAGINSTANCT." where modelid=".$modelid." and tenantid=".$_SESSION["tenantid"];
		}
		else
		{
			$sql = "select *  from ".DATABASE_TENANT_TAGINSTANCT." where modelid=".$modelid." and navid=".$navid." and tenantid=".$_SESSION["tenantid"];
		}
		$qr = $dsql->ExecQuery($sql);

		if (!$qr)
		{
			set_error_msg("sql error:".mysql_error());
		}
		else
		{

			$tmp_arr = array();
			while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				//$tmp_arr["id"] = $result["id"];
				//$tmp_arr["modelid"] = $result["modelid"];
				$tmp_arr["content"] = $result["content"];
				//$tmp_arr["tenantid"] = $result["tenantid"];
				//$tmp_arr["navid"] = $result["navid"];
				//$tmp_arr["updatetime"] = $result["updatetime"];
				//$arrs['children'][]=$tmp_arr;
				//echo $tmp_arr["content"];exit;
				//$arrcontent  = json_decode($tmp_arr["content"]);

				//$arrstmp=  $result["content"];
				//$arrs[]= json_decode($arrstmp);
				$arrs2=$result["content"];
			}
				echo $arrs2;			
		}
	}
	else
	{
		$sql = "select * from ".DATABASE_BILLRULEMODEL." where resourceid=".$modelid." and resourcetype=".$_SESSION["usertype"];

		$qr = $dsql->ExecQuery($sql);

		if (!$qr)
		{
			set_error_msg("sql error:".mysql_error());
		}
		else
		{

			$tmp_arr = array();
			while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
			{
				//$tmp_arr["id"] = $result["id"];
				//$tmp_arr["modelid"] = $result["modelid"];
				//$tmp_arr["content"] = $result["rulemodel"];
				//$tmp_arr["updatetime"] = $result["updatetime"];
				//$arrs['children'][]=$tmp_arr;
				$arrs2 = $result["content"];
			}
			echo $arrs2;
		}
	}

}

/**
 *
 * 获取某个导航模块的模型实例
 * $id 导航ID
 * 返回值:实例标示数组
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
			$tmp_arr[]=$result["id"];
		}
	}

	return $tmp_arr;
}

//获取某一租户的页面
function getbytid2($id)
{
	global  $dsql,$arrs,$arg_pagesize,$arg_search_page;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	$num=0;

	//$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	//$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	//$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	//$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	//$result = new DatatableResult();
	if($id==0)
	{
		$sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE;
		//$sql="select b.*,a.id as instanceid from ".DATABASE_TENANT_TAGINSTANCT." as a right join (select * from ".DATABASE_CUSTOMER_NAVIGATE." limit {$iDisplayStart},{$iDisplayLength}) as b on a.navid = b.id";
		$sql="select b.*,a.id as instanceid from ".DATABASE_TENANT_TAGINSTANCT." as a right join (select * from ".DATABASE_CUSTOMER_NAVIGATE." limit ".$limit_cursor.",".$arg_pagesize.") as b on a.navid = b.id";
	}
	else
	{
		$sqlcount = "select count(0) as cnt from ".DATABASE_CUSTOMER_NAVIGATE." where tenantid=".$id;
		$sql="select b.*,a.id as instanceid from ".DATABASE_TENANT_TAGINSTANCT." as a right join (select * from ".DATABASE_CUSTOMER_NAVIGATE."  where tenantid=".$id." limit ".$limit_cursor.",".$arg_pagesize.") as b on a.navid = b.id";
	}
	$qr = $dsql->ExecQuery($sqlcount);
	//$qr = $dsql->ExecQuery($sql);

	if(!$qr){
		echo "error";
		//$logger->error(TASKMANAGER." - getTaskHistory() sqlerror:".$sqlcount." - ".$dsql->GetError());
	}
	else{
		while($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$arrs["totalcount"] = $result["cnt"];

		}
		/*
		 $rcnt = $dsql->GetArray($qr);
		 $result->sEcho=$_GET['sEcho'];
		 $result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
		 $result->iTotalRecords = $rcnt['cnt'];
		 $result->iTotalDisplayRecords = $rcnt['cnt'];
		 */
		if($arrs["totalcount"]> 0){
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(TASKMANAGER." - getbytid2() sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				$temp_arr = array();
				while ($r = $dsql->GetArray($qr, MYSQL_ASSOC)){
					//echo date(('Y-m-d G:i:s'),$r["updatetime"]);
					$tmp_arr['id'] = $r["id"];
					$tmp_arr['level'] = $r["level"];
					$tmp_arr['parentid'] = $r["parentid"];
					$tmp_arr['instanceid'] = $r["instanceid"];
					$tmp_arr['modelid'] = $r["modelid"];
					$tmp_arr['text'] = $r["label"];
					$tmp_arr['updatetime'] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $tmp_arr;
				}
			}
		}
	}
	//echo json_encode($result);
}
//根据Id获取模块信息
function getbyid($id)
{
	global $dsql,$arrs;
	$num=0;

	$sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where id=".$id;
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
				$temp_arr["id"] = $result["id"];
				$temp_arr["label"] = $result["label"];
				$temp_arr["tenantid"] = $result["tenantid"];
				$temp_arr["pageid"] = $result["pageid"];
				$temp_arr["homepage"] = $result["homepage"];
				$temp_arr["updatetime"] = $result["updatetime"];

				$arrs[CHILDS][] = $temp_arr;
			}
		}
	}
}


//删除模板信息
function delete($ID)
{
	global $dsql,$arrs;

	$sql = "delete from ".DATABASE_CUSTOMER_NAVIGATE." where id in(".$ID.")";


	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		throw new Exception(TYPE_PAGE."- delete()-".$sql."-".mysql_error());
		$arrs["flag"]=0;
	}
	else
	{
		$arrs["flag"]=1;
	}
}

function update($userarr)
{global $dsql,$arrs;
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
function checkExist($label,$tid)
{
	global $dsql,$arrs;
	$num;

	$sql="select count(*) as totalcount from ".DATABASE_CUSTOMER_NAVIGATE." where label='".$label."' and id=".$tid;

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

	if(!checkmodelrule(32,31,$arrsdata["content"]))
	{
		$arrs["flag"]=0;
	}
	else
	{
		//检查当前导航是否已与模型创建了关联
		$checkmodel = "select count(0) as cnt from  ".DATABASE_CUSTOMER_NAVIGATE." where id = ".$arrsdata["navid"]." and modelid=".$arrsdata["modelid"];
		$checkinstance="select count(0) as cnt from ".DATABASE_TENANT_TAGINSTANCT." where navid = ".$arrsdata["navid"];
		$createinstance = "insert into ".DATABASE_TENANT_TAGINSTANCT." (modelid,tenantid,content,navid,updatetime) values (".$arrsdata["modelid"].",".$_SESSION["tenantid"].",'".$arrsdata["content"]."',".$arrsdata["navid"].",".time().")";
		$updateinstalce = "update ".DATABASE_TENANT_TAGINSTANCT." set content='".$arrsdata["content"]."',updatetime=".time()." where id = ".$arrs["currinstanceid"];
		$updatenav = "update ".DATABASE_CUSTOMER_NAVIGATE." set modelid = ".$arrsdata["modelid"].",filepath='".$arrsdata["filepath"]."',updatetime = ".time()." where id = ".$arrsdata["navid"];
		$sql="select * from ".DATABASE_CUSTOMER_NAVIGATE."  where id=".$id;
		$qr = $dsql->ExecQuery($checkmodel);
		//echo $sql;
		if($arrsdata["instanceid"]==null)
		{
			$arrsdata["instanceid"]=0;
		}
		if($arrsdata["instanceid"]==0)
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
			if($arrsdata["modelid"]==$arrsdata["currentmodelid"])
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
					$deltag="delete from ".DATABASE_TENANT_TAGINSTANCT." where id = ".$arrsdata["instanceid"];
					$deletetag = $dsql->ExecQuery($updateinstalce);
					$arrs["deltah"] = $deltag;
					//2.修改当前导航的modelid
					$updatenav = "update ".DATABASE_CUSTOMER_NAVIGATE." set modelid = ".$arrsdata["currentmodelid"]." where id = ".$arrsdata["navid"];
					$udpatenavigate  = $dsql->ExecQuery($updatenav);
					$arrs["updatenav"] = $updatenav;
					//3.插入新的导航信息
					$inserttag ="insert into ".DATABASE_TENANT_TAGINSTANCT." (modelid,tenantid,content,updatetime) values (".$arrsata["currentmodelid"].",".$_SESSION["tenantid"].",'".$arrsdata["content"]."',".time().")";
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


/**
 * 检查json数据的合法性
 * 参数:$modelid:模型ID
 * $tid:租户ID
 * $content:json内容
 */
function checkmodelrule($modelid,$tid,$content)
{

	global $dsql,$arrs;

	$tenantmodel;
	$current_model;
	$tmp = str_replace("\\\"", "\"",$content);
	$sql = "select * from ".DATABASE_ACCOUNTING_RULE." where resourceid = ".$modelid." and tenantid = ".$tid;

	$qr = $dsql->ExecQuery($sql);
	if($dsql->GetTotalRow($qr)>0)
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$tenantmodel= $result["ruledata"];
		}

		$json_model = json_decode($tenantmodel,true);
		$currentmodel = json_decode($tmp,true);



		foreach($json_model["filter"] as $k=>$v)
		{
			foreach($currentmodel["filter"] as $k2=>$v2)
			{
				if($k==$k2)
				{
					if($k["value"]!=$k2["value"])
					{
						if($k["allowcontrol"]=="false")
						{
							return false;
						}
						else
						{
							switch ($k)
							{
								case "sex":
								case "area":
								case "weibotype":
								case "source":
								case "emotion":
								case "verified":
								case "business":
									if(!in_array($k["value"],$k["limit"]))
									{
										return false;
									}
									break;
								default:
									//判断默认值是否在指定范围内
									if($k2["value"]<$k2["minvalue"]||$k2["value"]>$k2["maxvalue"])
									{
										return false;
									}
									break;
							}


						}
					}
				}
			}
		}
	
		return true;
	}
	else
	{
		return false;
	}


}


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


if (empty($_GET))
{
	if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
	{
		global $arrsdata;
		$arrsdata = $_REQUEST;
		$arg_type = $arrsdata["type"];

	}
}
else
{
	$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
	$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
	$arg_id = isset($_GET["id"]) ? $_GET["id"] : 0;
	$arg_channelid = isset($_GET["channelid"]) ? $_GET["channelid"] : 0;
	$arg_name = isset($_GET["name"]) ? $_GET["name"] : null;
	$arg_type = isset($_GET["type"]) ? $_GET["type"] : null;
	$arg_typeid = isset($_GET["typeid"]) ? $_GET["typeid"] : null;
	$arg_modelnavtype = isset($_GET[TYPE_MODELNAVTYPE]) ? $_GET[TYPE_MODELNAVTYPE] : '';
	$arg_modelid = isset($_GET["modelid"]) ? $_GET["modelid"] : null;

}

switch ($arg_type)
{
	case TYPE_GETAll:
		getall();
		break;
	case TYPE_ADDNAVIGATE:
		addnavigate();
		break;
	case TYPE_UPDATE:
		update($arrsdata);
		break;
	case TYPE_DELETE:
		delete($arg_id);
		break;
	case TYPE_SEARCH:
		searchuser(FALSE);
		break;
	case TYPE_GETBYTID:
		getbytid($arg_id);
		break;
	case TYPE_GETBYTIDALL:
		getbytid2($arg_id);
		break;
	case TYPE_GETBYID:
		getbyid($arg_id);
		break;
	case TYPE_CHECKBYID:
		checkbyid($arg_name,$arg_tid,$arg_id);
		break;
	case TYPE_CHECKEXIST:
		checkExist($arg_name,$arg_id);
		break;
	case TYPE_GETLEFTMENU:
		getleftmenu($arg_id,$arg_channelid,$arg_typeid, $arg_modelnavtype);
		break;
	case TYPE_GETRESOURCEBYMODELID:
		getresourcebymodelid($arg_id,$arg_modelid);
		break;
	case TYPE_GETINSTANCEBYMODELID:
		getinstancebymodelid($arg_modelid,$arg_id);
		break;
	case TYPE_GETINSTANCE:
		getinstancebyid($arg_id);
		break;
	case TYPE_SETTENANTMODEL:
		settenantmodel();
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



