<?php
/**
 * 抓取评论任务
 * task->taskparams JSON对象说明：
 *     source 数据源
 *     each_count 每次抓取评论的条数
 *     select_cursor 查询微博时使用的limit，记录处理到第几条
 *     ---微博条件----------------------------------
 *     andor  逻辑关系 and  or
 *     min_updatetime 最后更新评论的时间.当数据库字段comment_updatetime 大于该值时不更新
 *     min_reposts_count 最少转发数
 *     ids 数组，指定微博id
 *     min_created_time  微博的最小创建时间
 *     max_created_time  微博的最大创建时间
 *     screen_name 数组 微博作者姓名（默认内部关系：or）
 *     ------------------------------------------------
 */
define( "SELF", basename(__FILE__) );
if ($argc > 1) {
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
}

include_once( 'includes.php' );
include_once('weibo_config.php');
include_once( 'weibo_class.php' );
include_once( 'saetv2.ex.class.php' );
include_once('taskcontroller.php');
ini_set('include_path',get_include_path().'/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_GETCOMMENT);//使用同步模块的日志配置
$res_machine;//机器资源
$res_ip;
$res_acc;
//声明保存时间的变量，insert_status需要用
$apitime = 0;//调用API花费总时间
$insertweibotime=0;//新数据入库时间
$analysistime=0;//solr时间
$funtime=0;//只包含抓取后的数据处理时间
$apicount = 0;//访问API次数
$apierrorcount = 0;//访问API错误次数
$spiderusercount = 0;//新入库的用户数
$insertusertime = 0;//插入用户花费时间
$spidercount = 0;//总抓取条数
$newcount = 0;//总入库条数
$solrerrorcount = 0;//错误数
$currentmachine;//当前机器名称
$needqueue = false;
if(isset($_SERVER['argc']) && $_SERVER['argc']>2){
    $logger->debug(SELF." - 参数2：".$argv[2]);
    $currentmachine = $argv[2];
}
else{
    $logger->error(SELF." - 未传递参数【machine】");
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
try{
    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_COMMENTS);
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

    $task = getQueueTask($currentmachine, TASK_COMMENTS);
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

//抓取
function crawling_comments($weibo_info,&$page,$each_count,$since_id,$max_id){
    global $apicount,$apierrorcount,$task,$logger,$oAuthThird,$apitime,$res_machine,$res_ip,$res_acc,$needqueue;
    $st = getTaskStatus($task->id);
    if($st == -1){
        $logger->info(SELF." - 人工停止");
        return false;        
    }

    checkAndApplyResource($task,$res_machine,$res_ip,$res_acc);
    if($task->taskparams->scene->state != SCENE_NORMAL){
        $logger->info(SELF." - ".getResourceErrorMsg($task->taskparams->scene->state));
        $needqueue = true;
        return false;
    }
    $needqueue = false;
    $start_time = microtime_float();
    //$weibos_info = $oAuthThird->user_timeline(1, 200, 1883350564 , null, null);
    $weibos_info = $oAuthThird->get_comments_by_sid($weibo_info['id'],$page, $each_count, $since_id, $max_id);

    $end_time = microtime_float();
    $apicount++;
    $apitimediff = $end_time - $start_time;
    $apitime += $apitimediff;
    $logger->info(SELF." - 调用user_timeline花费时间：".$apitimediff);
    $result = true;
    if ($weibos_info === false || $weibos_info === null)
    {
        $apierrorcount++;
        $logger->error("comment_show异常：page:{$page},count:{$each_count},weiboid:{$weibo_info['id']},since_id:{$since_id},max_id:{$max_id}");
        $result = false;
    }
    else if (isset($weibos_info['error_code']) && isset($weibos_info['error']))
    {
        $apierrorcount++;
        if($weibos_info['error_code'] == ERROR_IP_OUT_LIMIT){
            //IP使用超出
            disableResource($res_ip->id);
            $logger->info(SELF." - 资源{$res_ip->id} IP:{$res_ip->resource}使用超出限制");
            $result =  NULL;
        }
        else if($weibos_info['error_code'] == ERROR_USER_OUT_LIMIT){
            //帐号使用超出
            disableResource($res_acc->id);
            $logger->info(SELF." - 资源{$res_acc->id} 帐号:{$res_acc->resource}使用超出限制");
            $result =  NULL;
        }
        else if ($weibos_info['error_code'] == ERROR_LOGIN){
            $logger->error(SELF." - comment_show 登录失败,源{$task->tasksource},username:{$res_acc->resource} ".$weibos_info['error_code']." - ".$weibos_info['error']);
            disableResource($res_acc->id);
            $result =  NULL;
        }
        else if($weibos_info['error_code'] == ERROR_TOKEN){
            $logger->warn(SELF." - token 失效 ,源{$task->tasksource},username:{$res_acc->resource} ".$weibos_info['error_code']." - ".$weibos_info['error']);
            $result =  NULL;
        }
        else{
            $logger->error(SELF." - 访问API失败：".$weibos_info['error_code']." - ".$weibos_info['error']);
            $result =  false;
        }
    }

	//补充
	foreach($weibos_info as $k=>$value)
	{
		$weibos_info[$k]['page_url'] = weibomid2Url($value['user']['id'], $value['mid'], 1);
		$weibos_info[$k]['source_host'] = "weibo.com";				
		$weibos_info[$k]['user']['page_url'] = userid2Url($value['user']['id'],1);
		if(isset($value['status']) ){
			$weibos_info[$k]['status']['source_host'] = "weibo.com";
			if(isset($weibos_info[$k]['status']['user'])){
				$weibos_info[$k]['status']['page_url'] = weibomid2Url($value['status']['user']['id'], $value['status']['mid'], 1);
				$weibos_info[$k]['status']['user']['page_url'] = userid2Url($value['status']['user']['id'],1);
			}
		}
	}
	//$logger->debug("补充后的评论:".var_export($weibos_info,true));

    if($result === false || $result === NULL){
         unset($weibos_info);
         return $result;
    }
    else{
        return $weibos_info;
    }
}

/*
 * 通过用户ID查询微博信息
 */
function get_comments($weibo_info,$each_count,$forceupdate=0)
{
    global $apicount, $task,$apierrorcount,$task,$logger,$oAuthThird,$apitime,
    $res_machine,$res_ip,$res_acc,$needqueue,$updatereposttime;
    $logger->debug('enter get_comments');
	//当comment_sinceid为空时 说明没有分析过评论轨迹, 需要重新分析
	if(!empty($weibo_info['comment_sinceid'])){
		if($forceupdate){
			$weibo_info['comment_sinceid'] = NULL;
			$weibo_info['comment_maxid'] = NULL;
		}	
	}
    $sinceid = empty($weibo_info['comment_sinceid']) ? 0 : $weibo_info['comment_sinceid'];
    $maxid = empty($weibo_info['comment_maxid']) ? 0 : $weibo_info['comment_maxid'];
    $newid = NULL;
    $page = 1;
    $result = true;
    while(1){
    	$s_time = microtime_float();
        $comments_info = crawling_comments($weibo_info,$page,$each_count,$sinceid,$maxid);
        $e_time = microtime_float();
        $task->taskparams->scene->get_comments_time += $e_time - $s_time;
        if($comments_info === false){
            $result = false;
            break;
        }
        else if($comments_info === NULL){
            continue;
        }
        $comments_count = count($comments_info);
        if($comments_count == 0){
            $weibo_info['comment_maxid'] = NULL;
            $weibo_info['comment_sinceid'] = $newid;//将since_id赋最大值，以便下次获取最新的
            break;
        }
        else{
            if(empty($weibo_info['comment_maxid'])){
                //未设置maxid说明抓取的是用户最新的微博，第一条为用户最新的微博ID，记录到weibo_new_id
                $newid = $comments_info[0]['id'];
            }
            //更新maxid为当前记录的最后一个（即最小ID），则下次调用API时，只获取比此微博ID小的
            $weibo_info['comment_maxid'] = $comments_info[$comments_count - 1]['id'];
        }
        $page++;
        $s_time = microtime_float();
        //$solr_r = insert_comment($comments_info, 'comments_show', $task->taskparams->source);
		//$logger->debug("before addweibo:".var_export($comments_info,true));
		$solr_r = addweibo($task->taskparams->source, $comments_info,0,'comments_show', true, true);
        $e_time = microtime_float();
        $task->taskparams->scene->insert_comments_time += $e_time - $s_time;
        if($solr_r['result'] === false){
            //$logger->error(SELF." - solr返回false，退出");
            unset($comments_info);
            $result = false;
            break;
        }
        $task->datastatus += $comments_count;
        updateTaskInfo($task);
        unset($solr_r);
    }
    $s_time = microtime_float();
    //$r = set_comment_trend($task, $weibo_info);
	$tmp = array($weibo_info);
	$r = calcTrendPath('comment_trend', $tmp, true, $task);
    $e_time = microtime_float();
    $task->taskparams->scene->calc_comments_time += $e_time - $s_time;
    if($r == false){
    	$result = false;
    }
    update_comment_info($weibo_info);
    $logger->debug("exit get_comments");
    return $result;
}

//更新微博评论信息
function update_comment_info($weibo){
    global $logger,$dsql,$task, $updatereposttime;
    $updata['comment_sinceid'] = $weibo['comment_sinceid'];
    $updata['comment_maxid'] = $weibo['comment_maxid'];
    $updata['comment_updatetime'] = time();
    $whdata['id'] = $weibo['id'];
    $whdata['sourceid'] = $task->taskparams->source;
    $sql = update_template(DATABASE_WEIBO,$updata,$whdata);
    $start_time = microtime_float();
    $qr = $dsql->ExecQuery($sql);
    $end_time = microtime_float();
    $timediff = $end_time - $start_time;
    $updatereposttime += $timediff;
    if(!$qr){
        $logger->error(SELF.' sql:'.$sql.' error:'.$dsql->GetError());
    }
}

/*
 * 执行任务
 */
function execute()
{
    global $logger, $task,$dsql,$apicount,$apierrorcount,$insertweibotime,$analysistime,$funtime,
        $spidercount,$newcount,$solrerrorcount, $spiderusercount,$insertusertime,$apitime,$api_counts_time,
        $api_counts_count,$solr_update_time,$solr_update_count,$sql_updatecounts_time;
    $r = true;
    $seedcount = 0;
    $logger->debug("enter execute");
    if(!isset($task->taskparams->scene)){
    	$task->taskparams->scene = (object)array();
    }
    $task->taskparams->scene->status_desp = ''; 
    if(!isset($task->taskparams->scene->spider_statuscount)){
    	$task->taskparams->scene->spider_statuscount = 0;
    }
    if(!isset($task->taskparams->scene->new_comments_count)){
    	$task->taskparams->scene->new_comments_count = 0;
    }
    if(!isset($task->taskparams->scene->new_user_count)){
    	$task->taskparams->scene->new_user_count = 0;
    }
    if(!isset($task->taskparams->scene->apierrorcount)){
    	$task->taskparams->scene->apierrorcount = 0;
    }
    if(!isset($task->taskparams->scene->solrerrorcount)){
    	$task->taskparams->scene->solrerrorcount = 0;
    }
    if(!isset($task->taskparams->scene->calc_solrerrorcount)){
    	$task->taskparams->scene->calc_solrerrorcount = 0;
    }
    if(!isset($task->taskparams->scene->get_seedweibo_time)){
    	$task->taskparams->scene->get_seedweibo_time = 0;
    }
    if(!isset($task->taskparams->scene->get_comments_time)){
    	$task->taskparams->scene->get_comments_time = 0;
    }
    if(!isset($task->taskparams->scene->insert_comments_time)){
    	$task->taskparams->scene->insert_comments_time = 0;
    }
    if(!isset($task->taskparams->scene->calc_comments_time)){
    	$task->taskparams->scene->calc_comments_time = 0;
    }
    /*
     * 从数据库中的任务表，得到任务参数，其中包括查询的起始点
     * 和一次查询条数
     * 查询后，需要更新任务表的查询位置
     */
    //每次抓取多少条
    $each_count = isset($task->taskparams->each_count) ? $task->taskparams->each_count : 200;
    $remoteseed = array();
    while (1)
    {
    	$s_time = microtime_float();
        $seedweibo = getRepostSeedWeibo($task,true);//获取种子微博
        $e_time = microtime_float();
        $task->taskparams->scene->get_seedweibo_time += $e_time - $s_time;
        if(!empty($seedweibo)){
        	if($seedweibo['comments_count'] > 1900){
        		$remoteseed[] = $seedweibo['id'];
        		$task->taskparams->select_cursor++;
        		updateTaskInfo($task);
        		continue;
        	}
        	if(!empty($seedweibo['id2url'])){
				$task->taskparams->currorigurl = $seedweibo['id']."({$seedweibo['weibourl']})";
			}
			else{
				$task->taskparams->currorigurl = $seedweibo['weibourl'];
			}
			updateTaskInfo($task);
            $s_time = microtime_float();
            //获取到种子
            $logger->info(SELF." - 获取到种子微博:".$seedweibo['id']);
            $logger->debug('*** before get_comment memory:'.memory_get_usage());
            $r = get_comments($seedweibo,$each_count,$task->taskparams->forceupdate);
            $logger->debug('*** after get_comment memory:'.memory_get_usage());
            if($r == false){
                $logger->error(SELF." - get_comments返回false");
                break;
            }
            $e_time = microtime_float();
            $all_time = $e_time - $s_time;
            $logger->info(SELF." - 种子微博:".$seedweibo['id']."处理完毕,总耗费时间：{$all_time}");
            $task->taskparams->select_cursor++;
            updateTaskInfo($task);
            $seedcount++;
        }
        else if($seedweibo === false){
        	$r = false;
        	break;
        }
        else{
        	$task->taskparams->currorigurl = "";
            //未获取到种子微博，退出任务
            $logger->debug(SELF." - 未获取到种子微博");
            break;
        }
    }
    if(!empty($remoteseed)){
        $rs = addRepostTask($task->tasksource, $remoteseed, false, $task->conflictdelay, 0, 1, $task->taskparams, $task->remarks, true);
        if(!$rs['result']){
            $logger->error(SELF." - 添加远程任务失败");
            $r = false;
        }
    }
    $funtime += $insertweibotime + $insertusertime +$analysistime;
    $logger->info(SELF." - 统计条数：总访问API次数{$apicount},出错{$apierrorcount}次, 总抓取{$spidercount}条,入库{$newcount}条, 调用solr总失败{$solrerrorcount}条,总新增用户{$spiderusercount}个");
    $logger->info(SELF." - 统计时间：访问API时间{$apitime},总处理时间{$funtime}:(插入评论时间{$insertweibotime},插入用户时间{$insertusertime},分析时间{$analysistime})");
        $logger->debug('exit execute');
    return $r;
}

