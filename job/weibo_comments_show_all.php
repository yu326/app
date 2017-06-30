<?php
define("SELF", basename(__FILE__));
define("GET_WEIBO", 2);    //通过该标识，获取配置信息和任务信息
define("CONFIG_TYPE", GET_WEIBO);    //需要在include common.php之前，定义CONFIG_TYPE

if (isset($_SERVER['argc']) && $_SERVER['argc'] > 1) {
    $allParam = $_SERVER['argv'];
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

initLogger(LOGNAME_GETCOMMENT);//使用同步模块的日志配置
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
    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_COMMENTS);

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
    $task = getQueueTask($currentmachine, TASK_COMMENTS);

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
        if (($newcount - $solrerrorcount) > 0 && empty($task->taskparams->iscommit)) {
            $solr_r = handle_solr_data(array(), SOLR_URL_INSERT . "&commit=true");
            if ($solr_r !== NULL) {
                $logger->error(SELF . " - 提交solr返回{$solr_r}");
            }
        }
        completeTask($task);
        $logger->info(SELF . " - 任务完成");
    } else {
        if (empty($task->taskparams->iscommit)) {
            $solr_r = handle_solr_data(array(), SOLR_URL_UPDATE . "&commit=true");
            $logger->info(SELF . " - 提交solr返回{$solr_r}");
        }
        if ($needqueue) {
            queueTask($task);
            $logger->info(SELF . " - 任务排队，退出");
        } else if ($ishang) {
            $logger->info(SELF . " - 任务挂起");
            hangTask($task);
        } else {
            $logger->info(SELF . " - 任务停止");
            stopTask($task);
        }
    }
} catch (Exception $ex) {
    fatalTask($task);
    $logger->fatal(SELF . " - 任务异常" . $ex->getMessage());
    exit;
}
exit;
//抓取
function crawling_comments_show_all($id, $page = 1, $count = 50)
{
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
    $weibos_info = $oAuthThirdBiz->comments_show_all($id, $page, $count);
    $logger->debug(__FILE__.__LINE__." weibos_info ".var_export($weibos_info, true));
    $end_time = microtime_float();
    $apicount++;
    $apitimediff = $end_time - $start_time;
    $apitime += $apitimediff;
    $task->taskparams->scene->apicount_usertimeline++;
    $task->taskparams->scene->apitime_usertimeline += $apitimediff;
    $logger->info(SELF . " - 调用weibo_comments_show_all花费时间：" . $apitimediff);
    $result = true;
    if($weibos_info === false || $weibos_info === null) {
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

/*
 * 执行任务
 */
function execute()
{
    global $logger, $task, $dsql, $apicount, $apierrorcount, $insertweibotime, $analysistime, $funtime,
           $spidercount, $newcount, $solrerrorcount, $spiderusercount, $insertusertime, $apitime;
    $r = true;
    // $seedcount = 0;
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
    //起始页码
    $page = isset($task->taskparams->page) ? $task->taskparams->page: 1;;
    //每次抓取多少条
    $each_count = isset($task->taskparams->each_count) ? $task->taskparams->each_count : 50;
    //抓取指定抓取微博的id
    $idArr = array();
    if(isset($task->taskparams->oristatus)){
        $tmpArr = $task->taskparams->oristatus;
        foreach($tmpArr as $ti=>$titem){
            if (filter_var($titem, FILTER_VALIDATE_URL)) {//如果是url
                $seedmid = weiboUrl2mid($titem, $sourceid);
                if (empty($seedmid)) {
                    $logger->error(__FUNCTION__ . " url:{$titem} sourceid:{$sourceid}转MID失败{$seedmid}, 跳过");
                    continue;
                } else {
                    $idArr[] = $seedmid;
                }
            }
            else{
                $idArr[] = $titem;
            }
        } 
    }
    $logger->debug(__FILE__.__LINE__." idArr ".var_export($idArr, true));

    //接口每次查询关键词只支持一个
    foreach($idArr as $key=>$id){
        $page = 1;
        $success_num = 0;
        $fail_num = 0;
        $total_number = 0;
        while (1) {
            $s_time = microtime_float();
            $weibos_info = crawling_comments_show_all($id, $page, $each_count);
            if($page == 1 && empty($weibos_info)){
                continue;
            }
            if($total_number == "0"){
                $total_number = $weibos_info[$key]['total_number'];
            }
            //因为请求新浪接口，可能返回为空，引入失败成功判断机制，两种情况下，结束
            //①请求成功次数大于等于我们根据$total_number/$each_count得到的最大请求数
            //②请求失败次数大于我们设置的$total_fail_num,因为请求接口时，返回数据为空的情况，出现较为频繁，所以把失败总请求数设置为成功请求数的两倍
            //*****注意*****   由于对数据里要求准确，所以不引入失败次数，直至查询完为止。
            $total_success_num = ceil($total_number/$each_count);
//            $total_fail_num = ceil($total_success_num*2);
            if( $success_num >= $total_success_num){
                break;
            }
            if(empty($weibos_info)){
                continue;
            }

            $success_num++;

            $page = $page + 1;
            unset($weibos_info['total_number']);
            foreach($weibos_info as $k=>$value)
            {
                $weibos_info[$k]['page_url'] = weibomid2Url($value['user']['id'], $value['mid'], 1);
                $weibos_info[$k]['source_host'] = "weibo.com";				
                $weibos_info[$k]['user']['page_url'] = userid2Url($value['user']['id'],1);
                $weibos_info[$k]['user']['source_host'] = "weibo.com";
                if(isset($value['status']) ){
                    $weibos_info[$k]['status']['source_host'] = "weibo.com";
                    if(isset($weibos_info[$k]['status']['user'])){
                        $weibos_info[$k]['status']['page_url'] = weibomid2Url($value['status']['user']['id'], $value['status']['mid'], 1);
                        $weibos_info[$k]['status']['user']['page_url'] = userid2Url($value['status']['user']['id'], 1);
                        $weibos_info[$k]['status']['user']['source_host'] = "weibo.com";
                    }
                    if(isset($weibos_info[$k]['status']['retweeted_status']['user'])){
                        $weibos_info[$k]['status']['retweeted_status']['page_url'] = weibomid2Url($value['status']['retweeted_status']['user']['id'], $value['status']['mid'], 1);
                        $weibos_info[$k]['status']['retweeted_status']['user']['page_url'] = userid2Url($value['status']['retweeted_status']['user']['id'], 1);
                        $weibos_info[$k]['status']['retweeted_status']['user']['source_host'] = "weibo.com";
                    }

                }
                if(isset($value['reply_comment'])){
                    $weibos_info[$k]['reply_comment']['source_host'] = "weibo.com";
                    if(isset($weibos_info[$k]['reply_comment']['user'])){
                        $weibos_info[$k]['reply_comment']['page_url'] = weibomid2Url($value['reply_comment']['user']['id'], $value['status']['mid'], 1);
                        $weibos_info[$k]['reply_comment']['user']['page_url'] = userid2Url($value['reply_comment']['user']['id'], 1);
                        $weibos_info[$k]['reply_comment']['user']['source_host'] = "weibo.com";
                    }
                }
            }
            $solr_r = addweibo($sourceid, $weibos_info, $isseed, 'weibo_comments_show_all'); //允许数据不全
            if($solr_r['result'] === false){
                $logger->error(__FILE__.__LINE__." insert_status false".var_export($solr_r, true));
                unset($weibos_info);
                $r = false;
                break;
            }
            $e_time = microtime_float();
            $diff_time = $e_time - $s_time;
            $logger->info(SELF . " - 统计条数：总访问API次数{$apicount},出错{$apierrorcount}次, 总抓取{$spidercount}条,入库{$newcount}条, 调用solr总失败{$solrerrorcount}条,总新增用户{$spiderusercount}个");
            $logger->info(SELF . " - 统计时间：访问API时间{$apitime},总处理时间{$funtime}:(插入微博时间{$insertweibotime},插入用户时间{$insertusertime},分析时间{$analysistime})");
            $logger->debug('exit execute');
        }
    }
    return $r;
}
