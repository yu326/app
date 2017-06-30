<?php
/**
 * 更新转发数评论数任务
 * task->taskparams JSON对象说明：
 *     source 数据源
 *     select_cursor 查询微博时使用的limit，记录处理到第几条
 *     ---微博条件----------------------------------
 *     andor  逻辑关系 and  or
 *     max_updatetime 更新转发数、评论数的时间.当数据库字段repost_trend_time 小于该值时更新
 *     min_reposts_count 最少转发数
 *     ids 数组，指定id
 *     min_created_time  最小创建时间
 *     max_created_time  最大创建时间
 *     screen_name 数组 作者姓名（默认内部关系：or）
 *     ------------------------------------------------
 */
define( "SELF", basename(__FILE__) );

include_once( 'includes.php' );
include_once('weibo_config.php');
include_once( 'weibo_class.php' );
include_once( 'saetv2.ex.class.php' );
include_once('taskcontroller.php');
ini_set('include_path',get_include_path().'/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_STATUSESCOUNT);
$res_machine;//机器资源
$res_ip;
$res_acc;
//声明保存时间的变量，insert_status需要用
$api_counts_time = 0;//更新转发数评论数访问API花费时间
$api_counts_count = 0;//更新转发数评论数访问API次数
$solr_update_time = 0;//调用solr更新转发数花费时间
$solr_update_count = 0;//调用solr总次数
$sql_updatecounts_time = 0;//更新数据库转发数花费时间
$apierrorcount=0;
$needqueue = false;
if(isset($_SERVER['argc']) && $_SERVER['argc']>1){
    $logger->debug(SELF." - 参数1：".$argv[1]);
    $currentmachine = $argv[1];
}
else{
    $logger->error(SELF." - 未传递参数【machine】");
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
try{
    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_STATUSES_COUNT);//更新转发数、评论数
    if(!empty($task)){
        $task->machine=$currentmachine;
        $task->wait_resourcetype = RESOURCE_TYPE_MACHINE;
        $task->usetype = USETYPE_CONCURRENT;
        $queue_ret = queueTask($task);
        if ($queue_ret === false)
        {
            $logger->error(SELF." - 将任务插入排队表失败");
            exit;
        }
        $logger->debug(SELF." - 将任务插入排队表");
    }
    else{
        $logger->debug(SELF." - 未找到待启动任务,查询排队任务");
    }

    $task = getQueueTask($currentmachine, TASK_STATUSES_COUNT);
    if(empty($task)){
        $logger->debug(SELF." - 未获取到排队任务，退出");
        exit;
    }
    
    $logger->debug(SELF." - 获取到排队任务，任务ID：".$task->id);
    if(empty($task->taskparams->source)){
        $task->taskparams->errorinfo = "未指定源，退出";
        stopTask($task);
        $logger->error(SELF."- 未指定源，退出");
        exit;
    }
    $task->machine = $currentmachine;//获取指定的机器资源
    $task->tasksource = $task->taskparams->source;

    getAllConcurrentRes($task,$res_machine,$res_ip,$res_acc);//获取并发资源
    if($task->taskparams->scene->state != SCENE_NORMAL){
        myReleaseResource($task,$res_machine,$res_ip,$res_acc);
        $logger->info(SELF." - ".getResourceErrorMsg($task->taskparams->scene->state).",任务退出");
        updateQueueTask($task);
        updateTask($task);
        exit;
    }

    unQueueTask($task->id);//获取到资源，解除排队
    startTask($task);//启动任务
    $logger->info(SELF." - 任务启动");
    $rt = detectConflictTask($task);
    if(!$rt['result']){
    	myReleaseResource($task,$res_machine,$res_ip,$res_acc);
        stopTask($task);
        $logger->error(SELF."- 冲突检测失败 -".$rt['msg']);
        exit;
    }
    else if(!$rt['continue']){
    	myReleaseResource($task,$res_machine,$res_ip,$res_acc);
        $logger->info(SELF." - 任务冲突，延迟启动");
        exit;
    }
    $r = execute();
    myReleaseResource($task,$res_machine,$res_ip,$res_acc);
    if($r){
        completeTask($task);
        $logger->info(SELF." - 任务完成");
    }
    else{
        if($needqueue){
            queueTask($task);
            $logger->info(SELF." - 任务排队，退出");
        }
        else{
            $logger->info(SELF." - 任务停止");
            stopTask($task);
        }
    }
}
catch(Exception $ex){
    fatalTask($task);
    $logger->fatal(SELF." - 任务异常".$ex->getMessage());
    exit;
}
exit;

/*
 * 执行任务
 */
function execute()
{
    global $logger, $dsql, $task,$api_counts_time,$api_counts_count,$solr_update_time,
        $solr_update_count,$sql_updatecounts_time,$apierrorcount;
    $eachidcount = 100;//每次查询多少个id
    $limitcursor = 0;
    $result = true;
    $sqlarr = array();
    //根据配置的参数生成查询条件
    //指定了微博ID
    if(isset($task->taskparams->ids) && count($task->taskparams->ids) > 0){
        $us = "'".implode("','", $task->taskparams->ids)."'";
        $sqlarr[] = "id in (".$us.")";
    }
    if(isset($task->taskparams->min_reposts_count)){
        //限制了种子微博转发数
        $sqlarr[] = "reposts_count >= {$task->taskparams->min_reposts_count}";
    }
    //最后一次更新时间
    if(isset($task->taskparams->max_updatetime)){
        $sqlarr[] = "repost_trend_update < {$task->taskparams->max_updatetime}";
    }
    if(isset($task->taskparams->min_created_time)){
        $sqlarr[] = "created_at >= {$task->taskparams->min_created_time}";
    }
    if(isset($task->taskparams->max_created_time)){
        $sqlarr[] = "created_at <= {$task->taskparams->max_created_time}";
    }
    $andor = isset($task->taskparams->andor) ? $task->taskparams->andor : "and";
    if(count($sqlarr) == 0){
        return null;
    }
    $wh = implode(" {$andor} ", $sqlarr);
    $sql = "select * from ".DATABASE_WEIBO." where sourceid = {$task->taskparams->source}";//数据条件
    $sql .= " and ({$wh})";    
    do{
        
        $_sql = $sql." limit 0,{$eachidcount}";
        $qr = $dsql->ExecQuery($_sql);
        if(!$qr){
            $logger->error(SELF." select weibo:{$_sql} error:".$dsql->GetError());
            $result = false;
            break;
        }       
        else{
            $rsnum = $dsql->GetTotalRow($qr);
            if($rsnum === 0){
                break;
            }
            $ids = '';
            while($rs = $dsql->GetArray($qr)){
                $ids .= $rs['id'].',';
            }
            $ids = substr($ids,0,-1);
            if(empty($ids)){
                break;   
            }
            else{
                //请求API获取最新的转发数和评论数
                $counts_info = crawling_count_info_by_ids($ids);
                if($counts_info === false){
                    $result = false;
                    break;
                }
                else if($counts_info === NULL){
                    continue;
                }
                if(count($counts_info) == 0){
                    $logger->warn("未获取到转发数和评论数，ids:{$ids}");
                    continue;
                }
                $r = update_status_counts($counts_info,$task->taskparams->source);
                if($r === false){
                    unset($counts_info);
                    $result = false;
                    break;
                }
                unset($counts_info);
            }
            if($rsnum < $eachidcount){
                break;
            }
        }
        $dsql->FreeResult($qr);
        $task->datastatus += $rsnum;
    }while (true);
    $logger->info(SELF." 访问API次数{$api_counts_count},出错{$apierrorcount}次,花费时间{$api_counts_time},访问solr次数{$solr_update_count},访问SOLR花费时间{$solr_update_time},更新数据库花费时间{$sql_updatecounts_time}");
}
