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
define('TYPE_PAGE','templatecontroller.php');
define('CHILDS', "children");

//变量获取
define('ARG_TYPE', 'type');
define('ARG_SEARCH_CURRPAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE', 'pagesize');


//获取数据类型 (type)的具体内容
define('TYPE_GETAll', 'getall');    //获取所有模板信息
define('TYPE_GETPRODUCT', 'getproduct');
define('TYPE_ADDPRODUCT', 'addproduct');    //添加模板
define('TYPE_UPDATEPRODUCT', 'updateproduct');    //修改模板
define('TYPE_DELETEPRODUCT', 'deleteproduct');    //删除模板
define('TYPE_SEARCHPRODUCT', 'searchproduct');    //查询模板
define('TYPE_GETPRODUCTBYID', 'getproductbyid');  //根据ID查询模板
define('TYPE_CHECKPRODUCT', 'checkproduct');
define('TYPE_CHECKPRODUCTBYID', 'checkproductbyid');
define('TYPE_SETRESOURCE', 'setresource');
define('TYPE_GETTENANTRESOURCE', 'gettenantresource');
define('TYPE_GETPRODUCTBYTENANT', 'getproductbytenant');
define('TYPE_GETRESOURCEBYPID', 'getresourcebypid');
define('TYPE_GETPRODUCTBYRESOURCE', 'getproductbyresource');



$arg_search_page = isset($_GET[ARG_SEARCH_CURRPAGE]) ? $_GET[ARG_SEARCH_CURRPAGE] : 1;
$arg_pagesize = isset($_GET[ARG_SEARCH_PAGESIZE]) ? $_GET[ARG_SEARCH_PAGESIZE] : 10;
$arg_type = isset($_GET["type"]) ? $_GET["type"] : '';
//存放返回结果
$arrs;
$arrsdata;
$arg_type;
$arg_id;
$arg_name;
$arg_tid;
/*

//判断session是否存在
if(!checkusersession())
{
$arrs["result"]=false;
$arrs["msg"]="未登录或登陆超时!";
return;
}
*/
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

//添加产品信息
function addproduct()
{
	global $dsql,$arrs,$arrsdata,$logger;
	if(!Authorization::checkUserUseage(5,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$sql = "insert into ".DATABASE_PRODUCTS." (label,description,price,updatetime) values ('".$arrsdata["label"]."','".$arrsdata["description"]."',".$arrsdata["price"].",".time().")";
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


/**
 * 获取所有产品信息
 * Enter description here ...
 */
function getall()
{
	global $dsql,$arrs,$arg_pagesize,$arg_search_page,$logger;

	////计算limit的起始位置
	$limit_cursor = ($arg_search_page - 1) * $arg_pagesize;
	if(!Authorization::checkUserUseage(5,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$totalCount = "select count(*) as totalcount from ".DATABASE_PRODUCTS;
		$sql = "select * from ".DATABASE_PRODUCTS." order by updatetime desc limit ".$limit_cursor.",".$arg_pagesize;

		$qr2 = $dsql->ExecQuery($totalCount);


		if(!$qr2)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			while($result = $dsql->GetArray($qr2, MYSQL_ASSOC))
			{
				$arrs["totalcount"] = $result["totalcount"];

			}

			$q = $dsql->ExecQuery($sql);
			if(!$q)
			{
				$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
				$arrs["flag"]=0;
			}
			else
			{
				$temp_arr = array();
				while ($r = $dsql->GetArray($q, MYSQL_ASSOC))
				{
					$temp_arr["productid"] = $r["productid"];
					$temp_arr["label"] = $r["label"];
					$temp_arr["price"] = $r["price"];
					$temp_arr["description"] = $r["description"];
					$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$r["updatetime"]);
					$arrs[CHILDS][] = $temp_arr;
				}
			}
		}


	}
}


/*
 * 获取产品
 */
function getproduct()
{
	global $dsql,$arrs,$logger;

	$sql="select * from ".DATABASE_PRODUCTS." order by updatetime desc";
	$qr = $dsql->ExecQuery($sql);

	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["productid"] = $result["productid"];
			$temp_arr["label"] = $result["label"];
			//$temp_arr["description"] = $result["description"];
			//$temp_arr["price"] = $result["price"];
			//$temp_arr["updatetime"] = $result["updatetime"];

			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}
}

/*
 * 获取产品
 */
function gettenantresource()
{
	global $dsql,$arrs,$logger;

	$sql="select * from ".DATABASE_TENANT_RESOURCE." order by updatetime desc";
	$qr = $dsql->ExecQuery($sql);

	if(!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];
			$temp_arr["description"] = $result["description"];
			$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);;

			$arrs[CHILDS][] = $temp_arr;
		}
		$arrs["flag"]=1;
	}
}

function getproductbyid($id)
{
	global $dsql,$arrs,$logger;
	$num=0;

	$sql="select * from ".DATABASE_PRODUCTS." where productid=".$id;
	$qr = $dsql->ExecQuery($sql);


	if(!$qr)
	{
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
				$temp_arr["productid"] = $result["productid"];
				$temp_arr["label"] = $result["label"];
				$temp_arr["description"] = $result["description"];
				$temp_arr["price"] = $result["price"];
				$temp_arr["updatetime"] = date(('Y-m-d G:i:s'),$result["updatetime"]);;
				$arrs[CHILDS][] = $temp_arr;
			}
		}
		$arrs["flag"]=1;
	}
}

//根据租户获取产品
function getproductbytenant($id)
{
	global $dsql,$arrs,$logger;
	$num=0;

	$sql="select * from ".DATABASE_PRODUCTS." as a inner join ".DATABASE_TENANT." as b on a.productid=b.productid where b.tenantid=".$id;
	$qr = $dsql->ExecQuery($sql);


	if(!$qr)
	{
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
				$temp_arr["productid"] = $result["productid"];
				$temp_arr["label"] = $result["label"];

				$arrs[CHILDS][] = $temp_arr;
			}
		}
		$arrs["flag"]=1;
	}
}

//根据资源获取产品
function getproductbyresource($id)
{
	global $dsql,$arrs,$logger;
	$num=0;

	$sql="select c.productid,c.label from ".DATABASE_PRODUCTS." as c inner join (select b.productid from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b
on a.resourceid = b.resourceid where a.resourceid=".$id.") as d
on c.productid = d.productid";
	//echo $sql;
	$qr = $dsql->ExecQuery($sql);


	if(!$qr)
	{
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
				$temp_arr["productid"] = $result["productid"];
				$temp_arr["label"] = $result["label"];

				$arrs[CHILDS][] = $temp_arr;
			}
		}
		$arrs["flag"]=1;
	}
}

//删除模板信息
function deleteproduct($ID)
{
	global $dsql,$arrs,$logger;
	if(!Authorization::checkUserUseage(5,1,$childid=null))
	{
		$arrs["result"]=false;
		$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
		echo json_encode($arrs);
		exit;
	}
	else
	{
		$arrs["result"]=true;
		$sql = "delete from ".DATABASE_PRODUCTS." where productid in(".$ID.")";
		$delresrelation = "delete from ".DATABASE_PRODUCT_RESOURCE." where productid in (".$ID.")";

		$qr = $dsql->ExecQuery($sql);
		if(!$qr)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			//删除产品与资源的关系
			$qr2 = $dsql->ExecQuery($delresrelation);
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

	//删除产品资源关系
	function deleteresource($ID,$pid)
	{
		global $dsql,$arrs,$logger;
		if(!Authorization::checkUserUseage(5,1,$childid=null))
		{
			$arrs["result"]=false;
			$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
			echo json_encode($arrs);
			exit;
		}
		else
		{
			$sql = "delete from ".DATABASE_PRODUCT_RESOURCE." where productid=".$pid." resouceid in(".$ID.")";


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

	function updateproduct($userarr)
	{
		global $dsql,$arrs,$logger;
		if(!Authorization::checkUserUseage(5,1,$childid=null))
		{
			$arrs["result"]=false;
			$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
			echo json_encode($arrs);
			exit;
		}
		else
		{
			$sql = "update ".DATABASE_PRODUCTS." set label='".$userarr["label"]."',description='".$userarr["description"]."',price=".$userarr["price"].",updatetime=".time()." where productid =".$userarr["productid"];

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
	function checkProductExist($label)
	{
		global $dsql,$arrs,$logger;
		$num;
		//$label = iconv("gb2312","utf-8",$label);
		$sql="select count(*) as cnt  from ".DATABASE_PRODUCTS." where label='".$label."'";

		$q = $dsql->ExecQuery($sql);
		if(!$q)
		{
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			$arrs["flag"]=0;
		}
		else
		{
			$result = $dsql->GetArray($q, MYSQL_ASSOC);
			$num = $result["cnt"];
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

	/**
	 * 查找添加的产品是否已经添加过
	 * @param $prdname  产品名称
	 * @param $pid  产品ID，此参数为0时，则表示新添加产品信息，不为0则表示修改产品名称，此时会查询当前修改的产品信息之外的产品名称重复
	 */
	function checkProductExistByPID($prdname,$pid)
	{
		global $dsql,$arrs,$logger;
		$num;
		//var_dump($prdname);
		//$prdname = iconv("gb2312","utf-8",$prdname);
		if($pid==0)
		{
			$sql = "select count(*) as totalcount from ".DATABASE_PRODUCTS." where label='".$prdname."'";
		}
		else
		{
			$sql = "select count(*) as totalcount from ".DATABASE_PRODUCTS." where label='".$prdname."' and productid<>".$pid;
		}

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





	//设置产品资源
	function setresource()
	{
		global $dsql,$arrs,$arrsdata,$logger;
		if(!Authorization::checkUserUseage(5,1,$childid=null))
		{
			$arrs["result"]=false;
			$arrs["msg"]="您没有权限使用此模,请与管理员联系!";
			echo json_encode($arrs);
			exit;
		}
		else
		{
			$arrs["result"]=true;
			$deletedata = isset($arrsdata["delresource"]) ? $arrsdata["delresource"] : 0;
			$resourcedata = $arrsdata["resourceid"];
			$pid = $arrsdata["productid"];
			//$arrs["deletedata"] = $deletedata;
			//删除资源
			if($deletedata!=""&&$deletedata!=null)
			{
				//deleteresource($deletedata);
				foreach($deletedata as $key => $value)
				{
					$sqldel = "delete from ".DATABASE_PRODUCT_RESOURCE." where productid=".$pid." and resourceid = ".$value;
					//$arrs["del"] = $deletedata;
					//$arrs["sqldel"] = $sqldel;

					$q = $dsql->ExecQuery($sqldel);
					if(!$q)
					{
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
						$arrs["flag"]=0;
						exit;
					}

				}
			}
			if($resourcedata=="")
			{
				$sql="delete from ".DATABASE_PRODUCT_RESOURCE." where productid=".$pid;

				$q = $dsql->ExecQuery($sql);

				if(!$q)
				{
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					$arrs["flag"]=0;
				}
				else {
					$arrs["flag"]=1;
				}

			}
			else
			{

				$resarr = explode(",",$resourcedata);
				if(count($resarr)>0)
				{
					//创建资源角色关系向角色资源关系表插入数据
					foreach($resarr as $key => $value){
						$num=0;
						$sqlcheck = "select count(0) as cnt from ".DATABASE_PRODUCT_RESOURCE." where resourceid=".$value." and productid=".$pid;
						//$arrs["sqlcheck"] = $sqlcheck;
						$qr3 = $dsql->ExecQuery($sqlcheck);
						if(!$qr3)
						{
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
							$arrs["flag"]=0;
						}
						else
						{
							while ($result = $dsql->GetArray($qr3, MYSQL_ASSOC))
							{
								$num=$result["cnt"];
							}
						}
						if($num==0)
						{
							$sql2 = "insert into ".DATABASE_PRODUCT_RESOURCE." (resourceid,productid,updatetime)
		         values(".$value.",".$pid.",".time().")";
							$q = $dsql->ExecQuery($sql2);
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
					$arrs["flag"]=1;
				}

			}
		}//结束权限判断

	}

	//
	function getresourceinfo($id,$pid)
	{global $dsql,$arrs,$arrsdata,$logger;
	$num = 0;
	$sql = "select count(0) as cnt from".DATABASE_PRODUCT_RESOURCE." where resourceid=".$id." and productid=".$pid;

	$qr = $dsql->ExecQuery($sql);

	if(!$q)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		
	}
	else
	{
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$num=$result["cnt"];
		}
	}
	return $num;

	}

	//获取产品拥有资源
function getresourcebypid($pid)
{
		global $dsql,$arrs,$arrsdata,$logger;
	$num = 0;

	$sql = "select a.label,a.resourceid from ".DATABASE_TENANT_RESOURCE." as a inner join ".DATABASE_PRODUCT_RESOURCE." as b
on a.resourceid = b.resourceid where b.productid=".$pid;
	$qr = $dsql->ExecQuery($sql);
	if (!$qr)
	{
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		$arrs["flag"]=0;
	}
	else
	{
		$temp_arr = array();
		while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
		{
			$temp_arr["resourceid"] = $result["resourceid"];
			$temp_arr["label"] = $result["label"];

			$arrs[CHILDS][] = $temp_arr;
		}
		if($arrs != null){
			$arrs["flag"]=1;
		}
		else{
			$arrs["flag"]=0;
		}
	}
}

	if (empty($_GET))
	{
		if($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest")
		{
			global $arrsdata;
			$arrsdata = $_REQUEST;
			$arg_id = isset($arrsdata["id"]) ? $arrsdata["id"] : 0;
			$arg_type = isset($arrsdata["type"]) ? $arrsdata["type"] : '';
			$arg_name = isset($arrsdata["label"]) ? $arrsdata['label'] : '';
			if($arg_name == ''){
				$arg_name = isset($arrsdata["name"]) ? $arrsdata['name'] : '';
			}
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

	}

	switch ($arg_type)
	{
		case TYPE_GETAll:
			getall();
			break;
		case TYPE_GETPRODUCT:
			getproduct();
			break;
		case TYPE_ADDPRODUCT:
			addproduct();
			break;
		case TYPE_UPDATEPRODUCT:
			updateproduct($arrsdata);
			break;
		case TYPE_DELETEPRODUCT:
			deleteproduct($arg_id);
			break;
		case TYPE_SEARCHPRODUCT:
			searchproduct(FALSE);
			break;
		case TYPE_GETPRODUCTBYID:
			getproductbyid($arg_id);
			break;
		case TYPE_CHECKPRODUCT:
			checkProductExist($arg_name);
			break;
		case TYPE_CHECKPRODUCTBYID:
			checkProductExistByPID($arg_name,$arg_id);
			break;
		case TYPE_GETTENANTRESOURCE:
			gettenantresource();
			break;
		case TYPE_GETPRODUCTBYTENANT:
			getproductbytenant($arg_id);
			break;
		case TYPE_GETRESOURCEBYPID:
			getresourcebypid($arg_id);
			break;
		case TYPE_SETRESOURCE:
			setresource($arg_id);
			break;
		case TYPE_GETPRODUCTBYRESOURCE:
			getproductbyresource($arg_id);
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



