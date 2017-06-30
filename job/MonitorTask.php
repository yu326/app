<?php
/**
 * Created by PhpStorm.
 * User: koreyoshi
 * Date: 2017/3/13
 * Time: 10:07
 */

define("SELF", basename(__FILE__));
define("GET_WEIBO", 2);    //通过该标识，获取配置信息和任务信息
define("CONFIG_TYPE", GET_WEIBO);    //需要在include common.php之前，定义CONFIG_TYPE

if (isset($_SERVER['argc']) && $_SERVER['argc'] > 1) {
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
} else {
    $logger->error(SELF . " - 未传递参数【machine】");
    exit;
}

include_once('includes.php');
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
include_once('taskcontroller.php');
include_once('jobfun.php');
ini_set('include_path', get_include_path() . '/lib');
require_once 'OpenSDK/Tencent/Weibo.php';
initLogger(LOGNAME_MONITORTASK);//使用同步模块的日志配置


if(isset($_SERVER['argc']) && $_SERVER['argc']>2){
    $logger->debug(SELF." - 参数2：".$argv[2]);
    $currentmachine = $argv[2];
}
else{
    $logger->error(SELF." - 未传递参数【machine】");
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
try{
    $TaskNum = array();
    $toStr = 'yaobowen@inter3i.com;caimeijiang@inter3i.com';  //接收监控邮件的预警地址
    $errorNum = "10";   //定义异常任务的个数
    $res = execute();
    $logger->info("this is ending~~~");
} catch (Exception $ex) {

    $logger->fatal(SELF . " - 任务异常" . $ex->getMessage());
    exit;
}
exit;

/*
 *    查询mysql中，正常状态的任务个数
 *
 *     @param  null
 *     @return  boolean
 */
function execute(){
    global $dsql,$logger,$errorNum,$TaskNum;
	//总任务数
    $sql = "select count(*) from task";
    $qr =  $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is " . $sql . " mysql error is " . $dsql->GetError());
    }
    $totalNum = $dsql->GetFetchRow($qr);
    $TaskNum['totalnum'] = $totalNum['0'];
	//正常任务个数
    $sql = "select count(*) from task where taskstatus = 1";
    $qr =  $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is " . $sql . " mysql error is " . $dsql->GetError());
    }
    $totalNum = $dsql->GetFetchRow($qr);
    $TaskNum['normalnum'] = $totalNum['0'];
	//等待启动任务个数
    $sql = "select count(*) from task where taskstatus = 0";
    $qr =  $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is " . $sql . " mysql error is " . $dsql->GetError());
    }
    $totalNum = $dsql->GetFetchRow($qr);
    $TaskNum['startnum'] = $totalNum['0'];
	//崩溃任务个数
    $sql = "select count(*) from task where taskstatus = 5";
    $qr =  $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is " . $sql . " mysql error is " . $dsql->GetError());
    }
    $totalNum = $dsql->GetFetchRow($qr);
    $TaskNum['collapsenum'] = $totalNum['0'];
	//停止任务个数
	$sql = "select count(*) from task where taskstatus = 2";
    $qr =  $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is " . $sql . " mysql error is " . $dsql->GetError());
    }
    $totalNum = $dsql->GetFetchRow($qr);
    $TaskNum['stopnum'] = $totalNum['0'];
    $logger->info(__FILE__.__LINE__."the taskNum is:".var_export($TaskNum,true));
    $res = sendWarnEmail($TaskNum);
    $res = json_decode($res);
    $res = (array)$res;
    $logger->info(__FILE__.__LINE__."the res is:".var_export($res,true));


}
/*
 *    发送预警邮件
 *
 *     @param  null
 *     @return  boolean
 */
function sendWarnEmail($TaskNum){
    global $toStr,$logger;
    $url = 'http://sendcloud.sohu.com/webapi/mail.send.json';
    $param = array(

        'api_user' => API_USER,

        'api_key' => API_KEY,

        'from' => ALARM_MAIL_FROM,

        'fromname' => ALARM_MAIL_FROMNAME,

        'to' => $toStr,

        'cc' => '',

        'bcc' => '',

        'subject' => '这是监控任务的邮件',

        'html' => '  总的任务个数:  '.$TaskNum['totalnum'].'  <br/>等待任务个数: '.$TaskNum['startnum'].' <br/>正常任务个数: '.$TaskNum['normalnum'].'(大于20个，需要关注) <br/>崩溃任务个数: '.$TaskNum['collapsenum'].'（大于0个，需要关注） <br/>停止任务个数: '.$TaskNum['stopnum'].'（大于0个，需要关注）',

        'resp_email_id' => 'true');

    $data = http_build_query($param);
    $options = array(

        'http' => array(

            'method'  => 'POST',

            'header' => 'Content-Type: application/x-www-form-urlencoded',

            'content' => $data

        ));
    $logger->info(__FILE__.__LINE__."the html is:".var_export($param['html'],true));

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
}




?>