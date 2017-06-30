<?php
/**
 * 分析评论轨迹任务
 * task->taskparams JSON对象说明：
 *     ---条件----------------------------------
 *       oriurls 源url数组
 *     ------------------------------------------------
 */
define("SELF", basename(__FILE__));
if ($argc > 1) {
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
}

include_once('includes.php');
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
include_once('taskcontroller.php');
ini_set('include_path', get_include_path() . '/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_REPOST_TREND);//使用同步模块的日志配置
$res_machine;//机器资源
$res_ip;
$res_acc;
//声明保存时间的变量，insert_status需要用
$funtime = 0;//任务处理时间
$currentmachine;//当前机器名称
$needqueue = false;
if (isset($_SERVER['argc']) && $_SERVER['argc'] > 2) {
    $logger->debug(SELF . " - 参数2：" . $argv[2]);
    $currentmachine = $argv[2];
} else {
    $logger->error(SELF . " - 未传递参数【machine】");
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
try {
    $task = getWaitingTask(TASKTYPE_ANALYSIS, TASK_REPOSTPATH);
    if (!empty($task)) {
        $task->machine = $currentmachine;
        $task->wait_resourcetype = RESOURCE_TYPE_MACHINE;
        $task->usetype = USETYPE_CONCURRENT;
        $queue_ret = queueTask($task);
        if ($queue_ret === false) {
            $logger->error(SELF . " - 将任务插入排队表失败");
            exit;
        }
        $logger->debug(SELF . " - 将任务插入排队表");
    } else {
        $logger->debug(SELF . " - 未找到待启动任务,查询排队任务");
    }

    $task = getQueueTask($currentmachine, TASK_REPOSTPATH);
    if (empty($task)) {
        $logger->debug(SELF . " - 未获取到排队任务，退出");
        exit;
    }

    $logger->debug(SELF . " - 获取到排队任务，任务ID：" . $task->id);
    /*if(empty($task->taskparams->source)){
     $task->taskparams->errorinfo = "未指定源，退出";
     stopTask($task);
     $logger->error(SELF."- 未指定源，退出");
     exit;
    }*/
    $task->machine = $currentmachine;//获取指定的机器资源
    //$task->tasksource = $task->taskparams->source;
    if (!isset($task->taskparams->scene->state)) {
        $task->taskparams->scene->state = SCENE_NORMAL;
    }
    $m_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_MACHINE, $task->machine, null, $task->tasklevel, $task->queuetime, true);
    if (!$m_r) {//不可用
        $task->taskparams->scene->state = SCENE_WAITCONCURRENT_MACHINE;
    }
    //getAllConcurrentRes($task,$res_machine,$res_ip,$res_acc);//获取并发资源
    if ($task->taskparams->scene->state != SCENE_NORMAL) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
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
    if (empty($task->datastatus)) {
        $task->datastatus = 0;
    }
    $old_datastatus = $task->datastatus;//上次执行的条数
    $r = execute();
    myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    if ($r) {
        completeTask($task);
        $logger->info(SELF . " - 任务完成");
    } else {
        $logger->info(SELF . " - 任务停止");
        stopTask($task);
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
    global $logger, $task, $dsql, $funtime;
    $r = true;
    $logger->debug("enter execute");
    /*
     * 从数据库中的任务表，得到任务参数，其中包括查询的起始点
     * 和一次查询条数
     * 查询后，需要更新任务表的查询位置
     */
    //每次抓取多少条
    $oriurls = $task->taskparams->oriurls;
    $task->taskparams->errorurl = array();
    $calccount = 0;
    while (1) {
        $s_time = microtime_float();
        $tmpdata = array();
        $url = $oriurls[$calccount];
        $tmpdata['source_host'] = get_host_from_url($url);
        $mid = weiboUrl2mid($url);
        if (!empty($mid)) {
            $tmpdata['mid'] = $mid;
        } else {
            $tmpdata['original_url'] = $url;
            $tmpdata['floor'] = 0;
            $tmpdata['paragraphid'] = 0;
        }
        //$ndata[] = $tmpdata;
        $articleguid = getArticleGuidOrMore($tmpdata);
        //$seedweibo = getRepostSeedWeibo($task,true);//查询源是否存在
        $e_time = microtime_float();
        $funtime += ($e_time - $s_time);
        if (!empty($articleguid)) {
            $s_time = microtime_float();
            $logger->info(SELF . " - 获取到源guid:" . $articleguid);
            $logger->debug('*** before calc_reposttrend memory:' . memory_get_usage());
            $r = calcTrendPath('repost_trend', array($tmpdata), true);
            $logger->debug('*** after calc_reposttrend memory:' . memory_get_usage());
            if ($r['result'] == false) {
                $err = array();
                $err['url'] = $url;
                $err['msg'] = $r['msg'];
                $task->taskparams->errorurl[] = $err;
                $logger->error(SELF . " - calc_reposttrend返回false " . var_export($err, true));
            }
            $e_time = microtime_float();
            $diff_time = $e_time - $s_time;
            $logger->info(SELF . " - 源URL:" . $url . "处理完毕,耗费时间：{$diff_time}");
            updateTaskInfo($task);
            $funtime += $diff_time;
        } else {
            $err = array();
            $err['url'] = $url;
            $err['msg'] = '源不存在';
            $task->taskparams->errorurl[] = $err;
            $logger->debug(SELF . " - 要计算轨迹的源" . $url . "不存在!");
        }
        $task->taskparams->select_cursor = $calccount;
        $calccount++;
        if ($calccount >= count($oriurls)) {
            break;
        }
    }
    $logger->info(SELF . " - 总处理时间{$funtime}");
    $logger->debug('exit execute');
    return $r;
}

