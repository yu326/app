<?php
/**
 * Created by PhpStorm.
 * User: koreyoshi
 * Date: 2016/11/13
 * Time: 16:30
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
global $logger;
initLogger(LOGNAME_UPDATEMYSQL);//使用同步模块的日志配置

$logger->info(__FUNCTION__.__FILE__.__LINE__." the update weibo_update is beginning");

$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);

$r = execute();
if ($r) {
    $logger->info(__FUNCTION__.__FILE__.__LINE__."正常结束了哦~");
}else{
    $logger->info(__FUNCTION__.__FILE__.__LINE__."任务有问题哦~");
}
function execute(){
    global $logger,$dsql;
    $result = true;

    $sql = "update weibo_update set status = 0 where status = 1";

    $logger->info(__FUNCTION__.__FILE__.__LINE__." the sql is:".var_export($sql,true));
    $qrsel = $dsql->ExecQuery($sql);
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the qrsel is:".var_export($qrsel,true));
    if($qrsel){
        $result = true;
    }else{
        $logger->info("{$sql} is wrong !!!");
        $result = false;
    }

    return $result;
}








?>