<?php
/**
 * Created by PhpStorm.
 * User: koreyoshi
 * Date: 2016/10/10
 * Time: 15:11
 */

include_once('includes.php');
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
include_once('taskcontroller.php');
include_once('jobfun.php');
ini_set('include_path', get_include_path() . '/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_GETUPDATE);//使用同步模块的日志配置
$res_machine;//机器资源
$res_ip;
$res_acc;
//声明保存时间的变量，insert_status需要用
$apitime = 0;//调用API花费总时间
$insertweibotime = 0;//新数据入库时间
$analysistime = 0;//solr时间
$funtime = 0;//只包含抓取后的数据处理时间
$apicount = 0;//访问API次数
$apierrorcount = 0;//访问API错误次数
$spiderusercount = 0;//新入库的用户数
$insertusertime = 0;//插入用户花费时间
$spidercount = 0;//总抓取条数
$newcount = 0;//总入库条数
$solrerrorcount = 0;//错误数
$currentmachine;//当前机器名称
$needqueue = false;
$ishang = false; //是否挂起
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);

$r = execute();
if($r){
    $logger->info(__FILE__.__LINE__." 任务正常执行完成 ");
}else{
    $logger->info(__FILE__.__LINE__." 任务未正常完成~ ");
}
/*
 * 执行任务
 */
function execute()
{
    global $logger, $oAuthThird;
    $logger->info(__FILE__.__LINE__." 添加订阅关键词任务开始了 ");
    $weibos_info = array();

    $subid = "10719";
    $add_keywords = "西门子,intel,英特尔，因特尔,siemens";
    $weibos_info = $oAuthThird->add_keywords($subid,$add_keywords,null,null,null);
    $logger->info(__FILE__.__LINE__." 添加关键词返回的结果是 ".var_export($weibos_info,true));
    $logger->info(__FILE__.__LINE__." 添加订阅关键词任务结束了 ");
    return true;
}


?>