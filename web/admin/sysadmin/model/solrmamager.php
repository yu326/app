<?php
define( "SELF", basename(__FILE__) );
include_once( 'includes.php' );
include_once( 'commonFun.php' );
include_once("authorization.class.php");
session_start();
initLogger(LOGNAME_WEBAPI);
$chkr = Authorization::checkUserSession();
if($chkr != CHECKSESSION_SUCCESS){
	setErrorMsg($chkr, "未登录或登陆超时!");
}
if(empty($_POST['opt'])){
	setErrorMsg(-1, "参数错误");
}
$opt = $_POST['opt'];
$urlparams="";
switch ($opt){
	case "updatecache":
		$urlparams = "&commit=true&syncupdate=true";
		break;
	case "clearcache":
		$urlparams = "&commit=true&clearcache=true";
		break;
	case "testnlp":
		testNLP();
		break;
	default:
		break;
}
if(empty($urlparams)){
	$logger->error(SELF." 错误的参数值:opt:".$opt);
	setErrorMsg(-1, "错误的参数值");
}
else{
	$solr_r = handle_solr_data(array(), SOLR_URL_UPDATE.$urlparams);
	if($solr_r !== NULL){
		$logger->error(SLEF." call handle_solr_data faild, return:".var_export($solr_r,true));
		setErrorMsg(-1, "调用solr失败");
	}
	else{
		echo json_encode(array("result"=>true));
	}
}

/**
 *
 * 测试NLP
 */
function testNLP(){
	global $logger;
	$result = array("result"=>true, "msg"=>"");
	if(empty($_POST['content'])){
		echo json_encode(array("result"=>false, "msg"=>"内容为空"));
	}
	else{
		$weibo['text'] = $_POST['content'];
		//$weibo['sourceid'] = 1;
		$weibo['content_type'] = (int)$_POST['content_type'];
		$weibo['analysis_status'] = ANALYSIS_STATUS_NORMAL;

		//获取分词方案
		$dictionary_plan= $_POST['dictionary_plan'];
		$fdata = formatAnalysisData($weibo,$dictionary_plan);

		$start_time = microtime_float();
		$solr_r = send_solr($fdata,SOLR_URL_ANALYSIS);
		$end_time = microtime_float();
		$logger->info(__FILE__.__LINE__." testNLP solr_url_analysis 花费时间:".($end_time-$start_time));
		if(isset($solr_r['error'])){//分析出错
			$result = false;
			$logger->error(__FUNCTION__." send_solr faild:{$solr_r['error']}");
		}
		else{
			//analysis 返回的不是response，是tokenresult
			if(isset($solr_r['tokenresult'])){
				//$logger->debug(var_export($solr_r['tokenresult'], true));
				$result['msg'] = $solr_r['tokenresult'];
			}
			else{
				$result['result'] = false;
				$result['msg'] = "analysis data property error：".var_export($solr_r,true);
				$logger->error(__FUNCTION__." ".$result['msg']);
			}
		}
		echo json_encode($result);
	}
	exit;
}
