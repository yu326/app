<?php

/**
 *
 * 重新发送未发送的邮件预警邮件
 * User: koreyoshi
 * Date: 2016/10/22
 * Time: 20:45
 */

define("SELF", basename(__FILE__));
define("GET_WEIBO", 2);    //通过该标识，获取配置信息和任务信息
define("CONFIG_TYPE", GET_WEIBO);    //需要在include common.php之前，定义CONFIG_TYPE
define("SEND_TODAY_EMAIL",true);     //开关，控制是否只发送当天的失败邮件

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
initLogger(LOGNAME_HISTORYEMAIL);//使用同步模块的日志配置
try {

    $logger->info(__FILE__.__LINE__."send fail email begin~~~");
    $r = execute();
    if ($r) {
        $logger->info(SELF . " - 任务完成");
    }else{
        $logger->info(SELF . " - 任务异常");
    }

}catch (Exception $ex) {
    $logger->fatal(SELF . " - 任务异常" . $ex->getMessage());
    exit;
}
$logger->info(__FILE__.__LINE__." send fail emai end~~~");
exit;
function execute() {
    global $logger;
    $limit = 5;
    if(SEND_TODAY_EMAIL){
        $time = $_SERVER['REQUEST_TIME'];
        $logger->info("the one time is:".var_export($time,true));
        $time1 = date("Y-m-d",$time);
        $time2 = strtotime($time1);
    }
    $email_data = get_failemail($limit,$time2);
    $result = true;
    foreach ($email_data as $k=>$one_email_data) {
        $logger->info(__FILE__.__LINE__." the send fail email begin {$k}");
        $e_id = $one_email_data['e_id'];
        $logger->info(__FILE__.__LINE__." the one eamil  data is :".var_export($one_email_data,true));
        $alarm = json_decode(urldecode($one_email_data['e_event']));
        $content = json_decode(urldecode($one_email_data['e_content']),true);
        $logger->info(__FILE__.__LINE__." alarm data is :".var_export($alarm,true));
        $logger->info(__FILE__.__LINE__." content data is :".var_export($content,true));
        foreach ($content as $key=>$warn_content) {
            //针对不同的应用源可能某列值为空，则显示 “一”  如：百度贴吧没有转帖，则转发数为空
            $repost_count1 = isset($warn_content['reposts_count']) ? $warn_content['reposts_count'] : "一";
            $comments_count1 = isset($warn_content['comments_count']) ? $warn_content['comments_count'] : "一";
            $praises_count1 =  isset($warn_content['praises_count']) ? $warn_content['praises_count'] : "一";
            $followers_count1 =  isset($warn_content['followers_count']) ? $warn_content['followers_count'] : "一";

        $header = "<table border='1' style='border-collapse: collapse' align='left' width='90%'><tr><td width='8%' align='center'>用户名</td><td width='30%' align='center'>内容</td><td width='8%' align='center'>时间</td><td width='8%'align='center'>转发数</td><td width='8%' align='center'>评论数</td><td width='8%' align='center'>点赞数</td><td width='8%' align='center'>粉丝数</td><td width='8%' align='center'>原因</td><td width='8%' align='center'>应用来源</td></tr>";
        if(!isset($warn_data) || empty($warn_data)){
            $warn_data = "<tr><td align='center'>".$warn_content['screen_name']."</td><td align='center'><a href='".$warn_content['page_url']."'>".$warn_content['text']['0']."</a></td><td align='center'>".date('Y-m-d H:i:s',$warn_content['created_at'])."</td><td align='center'>".$repost_count1."</td><td align='center'>".$comments_count1."</td><td align='center'>".$praises_count1."</td><td align='center'>".$followers_count1."</td><td align='center'>".$warn_content['reason']."</td><td align='center'>".$warn_content['source_hostname']."</td></tr>";
        }else{
            $warn_data .= "<tr><td align='center'>".$warn_content['screen_name']."</td><td align='center'><a href='".$warn_content['page_url']."'>".$warn_content['text']['0']."</a></td><td align='center'>".date('Y-m-d H:i:s',$warn_content['created_at'])."</td><td align='center'>".$repost_count1."</td><td align='center'>".$comments_count1."</td><td align='center'>".$praises_count1."</td><td align='center'>".$followers_count1."</td><td align='center'>".$warn_content['reason']."</td><td align='center'>".$warn_content['source_hostname']."</td></tr>";
        }
        $footer = "</table>";



    }

    $warn_data = $header.$warn_data.$footer;
    $status = doEventAction($alarm,$warn_data) ? 1 : 0;
    $logger->info(__FILE__.__LINE__." the fail email send status is:".var_export($status,true));
    unset($alarm);
    unset($content);
    unset($warn_data);
    if ($status) {
        $sendtime = time();
        $res = update_fail_email_status($e_id,$status,$sendtime);
        $logger->info(__FILE__.__LINE__." the email_history update status is:".var_export($res,true));
        if (!$res) {
            $result = false;
            $logger->error(__FUNCTION__.__FILE__.__LINE__."the email_history update failed!");
        }
        }else{
            $result = false;
            $logger->error(__FUNCTION__.__FILE__.__LINE__."the email send failed!");
        }

    $logger->info(__FILE__.__LINE__." the send fail email end {$k}");

    }
    return $result;
}

//$logger->info(__FILE__.__LINE__." the fail email data is :".var_export($email_data,true));





