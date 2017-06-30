<?php
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

initLogger(LOGNAME_CRAWLING);//使用同步模块的日志配置
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
$OriginalIdArray = array(); //存放原创的id   by  yu 2017/3/23
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
    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_KEYWORD);
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
    $task = getQueueTask($currentmachine, TASK_KEYWORD);

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
    $task->tasksource = $task->taskparams->source; //数据来源
    $logger->info(__FILE__.__LINE__." 从排队表中获取任务 task:".var_export($task, true));
    getAllConcurrentRes($task, $res_machine, $res_ip, $res_acc);//获取并发资源
    $logger->info(__FILE__.__LINE__." 获取资源后 task ".var_export($task, true));
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
        //因为solr提交方式修改，由现在的commit为true，变成false，最后flush，所以把这部分注释掉   by   yu
//        if (($newcount - $solrerrorcount) > 0 && empty($task->taskparams->iscommit)) {
//            $solr_r = handle_solr_data(array(), SOLR_URL_INSERT . "&commit=true");
//            if ($solr_r !== NULL) {
//                $logger->error(SELF . " - 提交solr返回{$solr_r}");
//            }
//        }
        completeTask($task);
        $logger->info(SELF . " - 任务完成");
    } else {
        //因为solr提交方式修改，由现在的commit为true，变成false，最后flush，所以把这部分注释掉   by   yu
//        if (empty($task->taskparams->iscommit)) {
//            $solr_r = handle_solr_data(array(), SOLR_URL_UPDATE . "&commit=true");
//            $logger->info(SELF . " - 提交solr返回{$solr_r}");
//        }
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
function crawling_limited($q, $page = 1, $count = 50, $ids = NULL, $sort = NULL, $starttime = 0, $endtime = 0, $dup = 1, $onlynum = 0)
{
    global $apicount, $apierrorcount, $task, $logger, $oAuthThirdBiz, $apitime, $res_machine, $res_ip, $res_acc, $needqueue;

    $dup = 0;         //是否排重（不显示相似数据），0：否、1：是，默认为1。
    $antispam = 0;    //是否反垃圾（不显示低质量数据），0：否、1：是，默认为1。
    $onlynum = 0;     //是否只返回总数，0：否、1：是，默认为0。


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
    $logger->debug(__FUNCTION__.__FILE__.__LINE__." keyword :".var_export($q, true));
    $weibos_info = $oAuthThirdBiz->weibo_limited($q, $page, $count, $ids, $sort, $starttime, $endtime, $dup, $onlynum, $antispam);
    $logger->debug(__FILE__.__LINE__." weibos_info ".var_export($weibos_info, true));
    $end_time = microtime_float();
    $apicount++;
    $apitimediff = $end_time - $start_time;
    $apitime += $apitimediff;
    $task->taskparams->scene->apicount_usertimeline++;
    $task->taskparams->scene->apitime_usertimeline += $apitimediff;
    $logger->info(__FUNCTION__.__FILE__.__LINE__."  - 调用weibo_limited花费时间:".$apitimediff);
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

/*
 * 执行任务
 */
function execute()
{
    global $logger, $task, $dsql, $apicount, $apierrorcount, $insertweibotime, $analysistime, $funtime,
           $spidercount, $newcount, $solrerrorcount, $spiderusercount, $insertusertime, $apitime,$OriginalIdArray;
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
    //需抓取的关键词
    $keywordArr = array();
    if(isset($task->taskparams->keywords)){
        $keywordArr = $task->taskparams->keywords;
    }
    //起始页码
    $page = isset($task->taskparams->page) ? $task->taskparams->page: 1;;
    $crawlpage = isset($task->taskparams->crawlpage) ? $task->taskparams->crawlpage : 1;
    //每次抓取多少条
    $each_count = isset($task->taskparams->each_count) ? $task->taskparams->each_count : 50;
    //抓取指定uids的用户
    $ids = NULL;
    if(isset($task->taskparams->userids)){
        $idArr = $task->taskparams->userids;
        $ids = implode("~", $idArr);
    }
    //排序
    $sort = NULL;
    if(isset($task->taskparams->sort)){
        $sort =  $task->taskparams->sort;
    }
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
    //去除重复
    $filterdup = NULL;
    if(isset($task->taskparams->filterdup)){
        $filterdup =  $task->taskparams->filterdup;
    }
    //是否只返回总条数
    $onlynum = NULL;
    if(isset($task->taskparams->onlynum)){
        $onlynum =  $task->taskparams->onlynum;
    }
	//是否抓取关键字相关微博
	$is_grab_repost = NULL;
	if(isset($task->taskparams->is_grab_repost)){
        $is_grab_repost =  $task->taskparams->is_grab_repost;
    }
    //接口每次查询关键词只支持一个
    foreach($keywordArr as $key=>$q){
        $page = 1;
        $OriginalIdArray = array();
        $logger->info(__FILE__.__LINE__."the original array is:".var_export($OriginalIdArray,true));
        while (1) {
            $s_time = microtime_float();
            $weibos_info = crawling_limited($q, $page, $each_count, $ids, $sort, $starttime, $endtime, $filterdup, $onlynum);
            if(empty($weibos_info)){
                break;
            }
            foreach($weibos_info as $k=>$value)
            {
                $weibos_info[$k]['page_url'] = weibomid2Url($value['user']['id'], $value['mid'], 1);
                $weibos_info[$k]['source_host'] = "weibo.com";				
                $weibos_info[$k]['user']['page_url'] = userid2Url($value['user']['id'],1);
                if(isset($value['retweeted_status']) ){
                    $weibos_info[$k]['retweeted_status']['source_host'] = "weibo.com";
                    if(isset($weibos_info[$k]['retweeted_status']['user'])){
                        $weibos_info[$k]['retweeted_status']['page_url'] = weibomid2Url($value['retweeted_status']['user']['id'], $value['retweeted_status']['mid'], 1);
                        $weibos_info[$k]['retweeted_status']['user']['page_url'] = userid2Url($value['retweeted_status']['user']['id'],1);
                        $weibos_info[$k]['retweeted_status']['user']['source_host'] = "weibo.com";
                    }
                }
				//由于业务需求，需要抓取所有包含关键字的微博的全部转发
				if($is_grab_repost == '1'){
                    for_repost($weibos_info[$k],$q);
				}
            }
            $solr_r = commonHandleData($sourceid, $weibos_info, $isseed, 'weibo_limited');
            //$solr_r = addweibo($sourceid, $weibos_info, $isseed, 'weibo_limited'); //允许数据不全
            unset($weibos_info);
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
            $page = $page + 1;
            if($crawlpage != -1 && $page > $crawlpage){
                break;
            }
            $logger->debug('exit execute');
        }
        //flush操作，把内存commit同步到磁盘
        if($r){
            $res = flushData();
            $logger->info("after flush result is:".var_export($res,true));
            if($res){
                $r = true;
            }else{
                $r = false;
                $logger->info("the flush weibo data is failed");
                break;
            }
        }
    }
    return $r;
}
/**
 *  对微博内容进行关键词匹配，
 *  匹配成功且转发数>0,创建转发任务
 **/
function for_repost($weibos_info,$q){
    global $logger, $task;
    if(isset($weibos_info['retweeted_status'])){    //判断原创是不是存在
        $data = $weibos_info['retweeted_status']['text'];

        $result = GetMatch($data,$q);
        if($result){     //判断原创是否包含关键词
            //转发数大于0，则创建抓取转发的任务
            if($weibos_info['retweeted_status']['reposts_count'] > 0) {
                $res = created_repost($weibos_info['retweeted_status']['page_url'],$task);
                $logger->debug(__FILE__ . __LINE__ . " 判断创建转发任务结果是 " . var_export($res, true));
                if (!$res) {
                    $logger->debug(__FILE__ . __LINE__ . " 抓取关键词时创建转发任务失败 ");
                }
            }
            //入库到mysql前数据的处理
            $insert_result = Handle_data($weibos_info['retweeted_status'],$q,"0");
            $logger->debug(__FILE__ . __LINE__ . " 更新表入库结果是 " . var_export($insert_result, true));
                if(!$insert_result){
                    $logger->debug(__FILE__.__LINE__." 入到更新表失败 ");
                }

        }
        unset($data);
            //转发数大于0，则创建抓取转发的任务
            if($weibos_info['reposts_count'] > 0) {
                $res = created_repost($weibos_info['page_url'],$task);
                $logger->debug(__FILE__ . __LINE__ . " 判断创建转发任务结果1是 " . var_export($res, true));
                if (!$res) {
                    $logger->debug(__FILE__ . __LINE__ . " 抓取关键词时创建转发任务失败 ");
                }
            }
                $insert_result = Handle_data($weibos_info,$q,"1");
                $logger->debug(__FILE__ . __LINE__ . " 更新表入库1结果是 " . var_export($insert_result, true));
                if(!$insert_result){
                    $logger->debug(__FILE__.__LINE__." 入到更新表失败 ");
                }
    }else{   //没有原创信息，则说明这条微博是原创
        //转发数大于0，则创建抓取转发的任务
        if($weibos_info['reposts_count'] > 0){
            $res = created_repost($weibos_info['page_url'],$task);
            $logger->debug(__FILE__ . __LINE__ . " 判断创建转发任务2结果是 " . var_export($res, true));
            if(!$res){
                $logger->debug(__FILE__.__LINE__." 抓取关键词时创建转发任务失败 ");
            }
        }
        $insert_result = Handle_data($weibos_info,$q,"0");
        $logger->debug(__FILE__ . __LINE__ . " 更新表入库2结果是 " . var_export($insert_result, true));
        if(!$insert_result){
            $logger->debug(__FILE__.__LINE__." 入到更新表失败 ");
        }
    }
}
