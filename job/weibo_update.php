<?php
/**
 * weibo_update  更新微博
 * User: yu
 * Date: 2016/9/5
 * Time: 11:19
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
if(isset($_SERVER['argc']) && $_SERVER['argc']>2){
    $logger->debug(SELF." - 参数2：".$argv[2]);
    $currentmachine = $argv[2];
}
else{
    $logger->error(SELF." - 未传递参数【machine】");
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
try {
    //从数据库 Task 表中获取 后台接口调用类型的 关键字 抓取任务
    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_STATUSES_COUNT);
    $logger->debug(__FILE__ .__LINE__. " 任务详情是 ".var_export($task,true));
    if (!empty($task)) {

        $logger->debug(SELF . " ----获取任务成功！CurentMachine:[".$currentmachine."].");

        $task->machine = $currentmachine;
        $task->wait_resourcetype = RESOURCE_TYPE_MACHINE;
        $task->usetype = USETYPE_CONCURRENT;

        //将获取到的任务，插入任务队列，并将任务执行的机器设置为当前机器;如果插入成功则将任务队列中的任务状态修改为4,
        //如果任务状态修改失败，则将该任务从任务队列中清除,下次任务执行将再次执行该任务!
        $queue_ret = queueTask($task);
        if ($queue_ret === false) {
            $logger->error(SELF . " - 将任务插入排队表失败");
            exit;
        }
        $logger->debug(SELF . " - 将任务插入排队表");
    } else {
        $logger->debug(SELF . " - 未找到待启动任务,查询排队任务");
    }

    //从任务队列中获取分配给当前机器执行的任务
    $task = getQueueTask($currentmachine, TASK_STATUSES_COUNT);

    if (empty($task)) {
        $logger->debug(SELF . " - 未获取到排队任务，退出");
        exit;
    }
    $logger->debug(SELF . " - 获取到排队任务，任务ID：" . $task->id);

    if (empty($task->taskparams->source)) {
        $task->taskparams->errorinfo = "未指定源，退出";
        stopTask($task);
        $logger->error(SELF . "- 未指定源，退出");
        exit;
    }
    $logger->debug(SELF . " - 获取到任务，任务source：" . $task->taskparams->source);


    $task->machine = $currentmachine;//获取指定的机器资源
    $logger->info("curMachine:[" . $currentmachine);
    $task->tasksource = $task->taskparams->source; //数据来源

    getAllConcurrentRes($task, $res_machine, $res_ip, $res_acc);//获取并发资源

    $logger->info($task);
    if ($task->taskparams->scene->state != SCENE_NORMAL) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        //执行getResourceErrorMsg:无机器并发资源,任务退出分支，
        $logger->info(SELF . " - " . getResourceErrorMsg($task->taskparams->scene->state) . ",任务退出");
        updateQueueTask($task);
        updateTask($task);
        exit;
    }

    unQueueTask($task->id);//获取到资源，解除排队
    startTask($task);//启动任务
    $logger->info(SELF . " - 任务启动");
    $rt = detectConflictTask($task);

    if (!$rt['result']) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        stopTask($task);
        $logger->error(SELF . "- 冲突检测失败 -" . $rt['msg']);
        exit;
    } else if (!$rt['continue']) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        $logger->info(SELF . " - 任务冲突，延迟启动");
        exit;
    }
    $r = execute();
    myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    if ($r) {
        completeTask($task);
        $logger->info(SELF . " - 任务完成");
    }
} catch (Exception $ex) {
    fatalTask($task);
    $logger->fatal(SELF . " - 任务异常" . $ex->getMessage());
    exit;
}
exit;


/*
 * 执行任务
 */
function execute()
{
    global  $logger, $task, $dsql, $apicount, $apierrorcount, $insertweibotime, $analysistime, $funtime,
           $spidercount, $newcount, $solrerrorcount, $spiderusercount, $insertusertime, $apitime;
    $r = true;

    if (!isset($task->taskparams->scene)) {
        $task->taskparams->scene = (object)array();
    }
    if (!isset($task->taskparams->scene->spider_statuscount)) {
        $task->taskparams->scene->spider_statuscount = 0;//总抓取条数
    }
    if (!isset($task->taskparams->scene->insertsql_statustime)) {
        $task->taskparams->scene->insertsql_statustime = 0;//总入库时间
    }
    if (!isset($task->taskparams->scene->insertsql_statuscount)) {
        $task->taskparams->scene->insertsql_statuscount = 0;//入库条数
    }
    if (!isset($task->taskparams->scene->insertsql_usertime)) {
        $task->taskparams->scene->insertsql_usertime = 0;//用户入库时间
    }
    if (!isset($task->taskparams->scene->apicount_usertimeline)) {
        $task->taskparams->scene->apicount_usertimeline = 0;//访问API的次数
    }
    if (!isset($task->taskparams->scene->apitime_usertimeline)) {
        $task->taskparams->scene->apitime_usertimeline = 0;//访问API的时间
    }
    if (!isset($task->taskparams->scene->apierrorcount_usertimeline)) {
        $task->taskparams->scene->apierrorcount_usertimeline = 0;//访问API的错误数
    }
    if (!isset($task->taskparams->scene->alltime)) {
        $task->taskparams->scene->alltime = 0;
    }
    $r = true;
    /*
     * 从数据库中的任务表，得到任务参数，其中包括查询的起始点
     * 和一次查询条数
     * 查询后，需要更新任务表的查询位置
     */
    $sourceid = $task->taskparams->source;
    $isseed = $task->taskparams->isseed;
    //需更新的关键词
    $keywordArr = array();
    if(isset($task->taskparams->keywords)){
        $keywordArr = $task->taskparams->keywords;
    }
    //起始页码
    $page = isset($task->taskparams->page) ? $task->taskparams->page: 1;
    //每次查取条数
    $each_count = isset($task->taskparams->each_count) ? 100 : 100;
    //要抓取微博的起始时间
    $starttime = NULL;
    if(isset($task->taskparams->starttime)){
        $starttime =  $task->taskparams->starttime;
    }
    //要抓取微博的结束时间
    $endtime = NULL;
    if(isset($task->taskparams->endtime)){
        $endtime =  $task->taskparams->endtime;
    }
    //接口每次查询关键词只支持一个
    foreach($keywordArr as $key=>$q){
        $q = trim($q);
		$q = strtolower($q);
        $page = 1;
        $mysql_data = array();     //每次查询mysql返回的数据
        $weibos_info = array();     //请求接口返回的数据
        while (1) {
            //查询待更新微博
            $logger->debug(__FILE__.__LINE__." page为 ".var_export($page,true));
            $mysql_data = selectUpdateWeibo($q, $starttime, $endtime, $page, $each_count);
            $logger->debug(__FILE__.__LINE__." 数据为 ".var_export($mysql_data,true));
            if(empty($mysql_data)){
                break;
            }
            foreach($mysql_data as $key=>$wb_id){
                $idArr[$key] = $wb_id['d_id'];
            }
            $logger->debug(__FILE__.__LINE__." idarr数据为 ".var_export($idArr,true));
            $ids = implode(",",$idArr);
            $logger->debug(__FILE__.__LINE__." ids数据为 ".var_export($ids,true));

            //调用接口
            $weibos_info = getNewRepost($ids);
            $logger->debug(__FILE__.__LINE__." 请求接口返回的数据为 ".var_export($weibos_info,true));
            foreach($mysql_data as $key=>$before_data){

                $later_data = $weibos_info[$key]['reposts'];
                if($before_data['repost_num'] < $later_data){
                    $update_num = $later_data - $before_data['repost_num'];
                    $logger->debug(__FILE__.__LINE__."要更新的微博id为".var_export($before_data,true));
					$status = 1;
                    $update_result = updateRepost($before_data['d_id'],$later_data,$status);
                    if(!$update_result){
                        $logger->debug(__FILE__.__LINE__." 更新转发数失败 ");
                    }
                    $insert_result = created_repost($before_data['d_id'],$task,$update_num);
                    if(!$insert_result){
                        $logger->debug(__FILE__.__LINE__." 创建转发任务失败 ");
                    }
                }else{
					if($later_data == 0){
						$status = 2;
					}else{
						$status = 1;
					}
					
                    $update_result = updateRepost($before_data['d_id'],$later_data,$status);
                    if(!$update_result){
                        $logger->debug(__FILE__.__LINE__." 更新转发数失败 ");
                    }
                }

            }
            unset($before_data);
            unset($later_data);
            unset($weibos_info);
            unset($mysql_data);
            unset($idArr);
            unset($update_result);
            unset($insert_result);

            $logger->info(__FUNCTION__.__FILE__.__LINE__."the total is".var_export($page*$each_count,true));
            if($page*$each_count >5000){
                break;
            }
            $page++;

        }
    }
    return true;
}


//调用接口，查询指定ids的微博转评喜欢数
function  getNewRepost($ids){
    global $apicount, $apierrorcount, $task, $logger, $oAuthThirdBiz, $apitime, $res_machine, $res_ip, $res_acc, $needqueue;
    $st = getTaskStatus($task->id);
    if ($st == -1) {
        $logger->info(SELF . " - 人工停止");
        return false;
    }
    //申请资源时调用，初始化init_weiboclient
    checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
    if($task->taskparams->scene->state != SCENE_NORMAL){
        $logger->info(SELF." - ".getResourceErrorMsg($task->taskparams->scene->state));
        $needqueue = true;
        return false;
    }
    $needqueue = false;
    $start_time = microtime_float();

    $weibos_info = $oAuthThirdBiz->getNewRepost($ids);
//    $logger->debug(__FILE__.__LINE__." weibos_info ".var_export($weibos_info, true));
    $end_time = microtime_float();
    $apicount++;
    $apitimediff = $end_time - $start_time;
    $apitime += $apitimediff;
    $task->taskparams->scene->apicount_usertimeline++;
    $task->taskparams->scene->apitime_usertimeline += $apitimediff;
    $logger->info(__FUNCTION__.__FILE__.__LINE__."  - 调用get_new_repost花费时间:".$apitimediff);
    $result = true;
    if($weibos_info === false || $weibos_info === null ) {
        $apierrorcount++;
        $task->taskparams->scene->apierrorcount_usertimeline++;
        $result = false;
    } else if (isset($weibos_info['error_code']) && isset($weibos_info['error'])) {
        $apierrorcount++;
        $task->taskparams->scene->apierrorcount_usertimeline++;
        if ($weibos_info['error_code'] == ERROR_IP_OUT_LIMIT) {
            //IP使用超出
            disableResource($res_ip->id);
            $logger->info(SELF . " - 资源{$res_ip->id} IP:{$res_ip->resource}使用超出限制");
            $result = NULL;
        } else if ($weibos_info['error_code'] == ERROR_USER_OUT_LIMIT) {
            //帐号使用超出
            disableResource($res_acc->id);
            $logger->info(SELF . " - 资源{$res_acc->id} 帐号:{$res_acc->resource}使用超出限制");
            $result = NULL;
        } else if ($weibos_info['error_code'] == ERROR_LOGIN) {
            $logger->error(SELF . " - user_timeline 登录失败,源{$task->tasksource},username:{$res_acc->resource} " . $weibos_info['error_code'] . " - " . $weibos_info['error']);
            disableResource($res_acc->id);
            $result = NULL;
        } else if ($weibos_info['error_code'] == ERROR_TOKEN) {
            $logger->warn(SELF . " - token 失效 ,源{$task->tasksource},username:{$res_acc->resource} " . $weibos_info['error_code'] . " - " . $weibos_info['error']);
            $result = NULL;
        } else {
            $logger->error(SELF . " - 访问API失败：" . $weibos_info['error_code'] . " - " . $weibos_info['error']);
            $result = false;
        }
    }
    if ($result === false || $result === NULL) {
        unset($weibos_info);
        return $result;
    } else {
        return $weibos_info;
    }
}