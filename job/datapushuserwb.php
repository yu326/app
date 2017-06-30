<?php
/*
 *  订阅用户的数据
 *  2016/11/18
 *  by yaobowen
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
set_time_limit(0);
initLogger(LOGNAME_DATAPUSH);//使用同步模块的日志配置
global $logger;
$logger->info(__FILE__.__LINE__." ~~~~~the beginning ~");


try {
    $taskid = "19";
    $task = getdatapush($taskid);
    $logger->info(__FILE__.__LINE__." task is ".var_export($task,true));

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
    global $logger, $task;


    $ch = curl_init();
    $url = "http://c.api.weibo.com/datapush/status?subid=10718";
    if (isset($last_id)) {
        $url = $url . "&since_id=" . $last_id;
    }
    $logger->info(__FILE__ . __LINE__ . "the url is:" . var_export($url, true));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
    curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response = curl_exec($ch);
    curl_close($ch);
    $logger->info(__FILE__ . __LINE__ . "response  is " . var_export($response, true));

    $arr = explode("\r\n", $response);
    $logger->info(__FILE__ . __LINE__ . " arr data is " . var_export($arr, true));




    foreach ($arr as $k => $v) {
        if (empty($v)) {
            unset($v);
            break;
        }
        $logger->info(__FILE__ . __LINE__ . "v  is " . var_export($v, true));
        $v = json_decode($v, true);
        $logger->info(__FILE__ . __LINE__ . "v  is " . var_export($v, true));
        $data[$k]['id'] = $v['text']['status']['id'];
        $data[$k]['text'] = $v['text']['status']['text'];
        $data[$k]['source'] = $v['text']['status']['source'];
        $data[$k]['created_at'] = $v['text']['status']['created_at'];
        $data[$k]['sourceid'] = 1;
        $data[$k]['favorited'] = $v['text']['status']['favorited'];
        $data[$k]['truncated'] = $v['text']['status']['truncated'];
        $data[$k]['in_reply_to_status_id'] = $v['text']['status']['in_reply_to_status_id'];
        $data[$k]['in_reply_to_user_id'] = $v['text']['status']['in_reply_to_user_id'];
        $data[$k]['in_reply_to_screen_name'] = $v['text']['status']['in_reply_to_screen_name'];
        $data[$k]['pic_ids'] = $v['text']['status']['pic_ids'];
        $data[$k]['pic_urls'] = $v['text']['status']['pic_urls'];
        $data[$k]['geo'] = $v['text']['status']['geo'];
        $data[$k]['user'] = $v['text']['status']['user'];
        $data[$k]['page_url'] = weibomid2Url($v['text']['status']['user']['id'], $v['text']['status']['id'], 1);
        $data[$k]['source_host'] = "weibo.com";
        $data[$k]['user']['page_url'] = userid2Url($v['text']['status']['user']['id'], 1);
        if (isset($v['text']['status']['retweeted_status'])) {
            $data[$k]['retweeted_status'] = $v['text']['status']['retweeted_status'];
            $data[$k]['retweeted_status']['sourcetype'] = 1;
            $is_repost = 1;
            $data[$k]['retweeted_status']['source_host'] = "weibo.com";

            if (isset($data[$k]['retweeted_status']['user'])) {
                $data[$k]['retweeted_status']['page_url'] = weibomid2Url($v['text']['status']['retweeted_status']['user']['id'], $v['text']['status']['retweetedstatus']['id'], 1);
                $data[$k]['retweeted_status']['user']['page_url'] = userid2Url($v['text']['status']['retweeted_status']['user']['id'], 1);
                $data[$k]['retweeted_status']['user']['source_host'] = "weibo.com";

            }
        } else {
            $is_repost = 0;
        }
        /*
         *            * 需要添加上，区分是否是原创
         */
        $data[$k]['is_repost'] = $is_repost;
        $data[$k]['reposts_count'] = $v['text']['status']['reposts_count'];
        $data[$k]['comments_count'] = $v['text']['status']['comments_count'];


        if (true) {
            $logger->info(__FILE__ . __LINE__ . " the repost_num is:" . var_export($data[$k]['reposts_count'], true));
            $logger->info(__FILE__ . __LINE__ . " the page_url is:" . var_export($data[$k]['page_url'], true));
            if ($data[$k]['reposts_count'] > 0) {
                $res = created_repost($data[$k]['page_url'], $task);
                $logger->info(__FILE__ . __LINE__ . " 判断创建转发任务结果是 " . var_export($res, true));
                if (!$res) {
                    $logger->debug(__FILE__ . __LINE__ . " 抓取关键词时创建转发任务失败 ");
                }
            }
        }
    }
    unset($arr);
    $solr_r = addweibo(1, $data, 1, 'datapushuserwb');
    unset($data);
    $logger->info(__FILE__.__LINE__." solr_r is ".var_export($solr_r,true));
    if ($solr_r) {
        $logger->info(__FILE__.__LINE__." the res is ".var_export($solr_r, true));
    }
    return $solr_r;

}