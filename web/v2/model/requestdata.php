<?php
//解决跨域提取数据   by：wang    2016-10-14
header('Access-Control-Allow-Origin: http://intel.inter3i.com'); 
//解决跨域提取数据   by：wang    2016-10-14
define( "SELF", basename(__FILE__) );
include_once('includes.php');
include_once('userinfo.class.php');
include_once("commonFun.php");
include_once("authorization.class.php");
include_once("getdata.php");
include_once("solragent.php");
include_once("dataProcessClass.php");
if(!isset($_SESSION)){
	session_start();
}
initLogger(LOGNAME_WEBAPI);
$arg_instanceid = isset($_GET["instanceid"]) ? $_GET["instanceid"] : 0;
$arg_elementid = isset($_GET["elementid"]) ? $_GET["elementid"] : 0;
$arg_hasformjson = isset($_GET["hasformjson"]) ? $_GET["hasformjson"] : 0;
$arg_returnoriginal = isset($_GET["returnoriginal"]) ? $_GET["returnoriginal"] : 0;
$arg_islinkage = isset($_GET["islinkage"]) ? $_GET["islinkage"] : 0;
$arg_isoverlay = isset($_GET["isoverlay"]) ? $_GET["isoverlay"] : 0;
$arg_download = isset($_REQUEST['downloadinfo']) ? 1 : 0;//是否是下载excel的请求
$arg_download_id = isset($_GET['downloadid']) ? $_GET['downloadid'] : "";//
$isdownload = $arg_download || !empty($arg_download_id);
//处理下载excel的请求
if($isdownload){
	$processer = createDataProcesser(PROCESSER_TYPE_DOWNLOAD);
	//由于有的浏览器，重复发起请求，导致参数错误
	//先用ajaxpost请求参数downloadinfo，存储在session中，并将key返回给客户端，客户端再次发起GET请求获取excel
	if($arg_download){
		if(empty($processer)){
			setErrorMsg("", "操作异常");
		}
		$r = $processer->checkAuth();
		if(!$r){
			setErrorMsg(WEBERROR_NOSESSION, $processer->getError());
		}
		$value = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],true);//$_REQUEST['downloadinfo'];
		if(!empty($value)){
			$key = md5(json_encode($value));
			if(!empty($key)){
				$_SESSION['user']->setDownloadInfo($key, $value);
				echo json_encode(array("key"=>$key));
				exit;
			}
			else{
				$logger->error(SELF." download 生成key失败");
				setErrorMsg("", "操作失败");
			}
		}
		else{
			$logger->error(SELF." downloadinfo 参数为空");
			setErrorMsg("", "参数错误");
		}
	}
	else{
		//第二次请求，根据downloadid取参数
		if(empty($processer)){
			$logger->error(SELF." processer is empty");
			$out = "操作异常";
		}
		else{
			$r = $processer->checkAuth();
			if(!$r){
				$out = $processer->getError();
			}
			else if(!empty($arg_download_id)){
				$params = $_SESSION['user']->getDownloadInfo($arg_download_id);
				set_time_limit(0);
				$global_timeoutsec = 0;//设置curl不超时
				$processer->setLogger($logger);
				if(empty($params)){
					$out = "参数错误";
				}
				else{
					$r = $processer->parseParams($params);//解析参数
					if(!$r){
						$logger->error($processer->getError());
						$out = "解析参数失败";
					}
					else{
						//$logger->debug(SELF." begin getData");
						$r = $processer->getData();
						//$logger->debug(SELF." end gettData");
						if($r === false){
							$logger->error($processer->getError());
							$out = "获取数据失败";
						}
						else if(empty($r)){
							$out = "查询的数据不存在";
						}
						else{
							//$logger->debug(SELF." begin formatData");
							$fmtout = $processer->formatData($r);
							//$logger->debug(SELF." end formatData");
							if(!$fmtout){
								$error = $processer->getError();
								$logger->error($error);
								if($error == "memory out of limit"){
									$out = "您请求的数据过多，请减少数据条数";
								}
								else{
									$out ="格式化数据失败";
								}
							}
							else{
								$outr = $processer->output();
								if(!$outr){
									$logger->error($processer->getError());
									$out = "输出失败";
								}
							}
						}
					}
				}
			}
			else{
				$logger->error(SELF." downloadid 参数为空");
				$out = "参数错误";
			}
		}
	}
	header("location:/error.html?error=".rawurlencode($out));
	exit;
}
else{
	if($arg_islinkage){
		$processer = createDataProcesser(PROCESSER_TYPE_LINKAGE);
	}
	else if($arg_isoverlay){
		$processer = createDataProcesser(PROCESSER_TYPE_OVERLAY);
	}
	else{
		$processer = createDataProcesser(PROCESSER_TYPE_ELEMENT);
	}
	if(empty($processer)){
		$logger->error("create data processer faild");
		setErrorMsg("", "操作异常");
	}
	else{
		$processer->setLogger($logger);
		$r = $processer->checkAuth();
		if(!$r){
			$out = getErrorOutput(WEBERROR_NOSESSION, $processer->getError());
		}
		else{
			$r = $processer->parseParams($_REQUEST);//解析参数
			if(!$r){
				$logger->error($processer->getError());
				$out = getErrorOutput("", "解析参数失败");
			}
			else{
				$dpr = $processer->getDataParams();
				if(!$dpr){
					$logger->error($processer->getError());
					$out = getErrorOutput("", "获取模型失败");
				}
				else{
					$r = $processer->checkLimit();//验证数据范围，如果部成功，则返回错误信息，直接输出
					if($r !== true){
						$out = $r;
					}
					else{
						$r = $processer->getData($dpr);
						if($r === false){
							$logger->error($processer->getError());
							$out = getErrorOutput("", "获取数据失败");
						}
						else{
							$out = $r;
						}
					}
				}
			}
		}
		$fmtout = $processer->formatData($out);
		$processer->output($fmtout);
	}
}

?>
