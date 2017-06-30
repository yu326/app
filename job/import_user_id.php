<?php
//define( "SELF", basename(__FILE__) );
define( "SELF", "" );

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
include_once('jobfun.php');
ini_set('include_path',get_include_path().'/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_IMPORTUSERID);
$res_machine;//机器资源
$res_ip;
$res_acc;
$needqueue;//是否需要排队
$succcallsolr = false;//是否成功访问solr

$apicount = 0;
$apierrorcount = 0;
$solrnewcount = 0;
$apitime = 0;

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
	$task = getWaitingTask(TASKTYPE_SPIDER, TASK_IMPORTUSERID);
	if(!empty($task)){
		$task->machine=$currentmachine;
		$task->wait_resourcetype = RESOURCE_TYPE_MACHINE;
		$task->usetype = USETYPE_CONCURRENT;
		$queue_ret = queueTask($task);
		if ($queue_ret === false)
		{
			$logger->error(SELF." - 将任务{$task->id}插入排队表失败");
			exit;
		}
		$logger->debug(SELF." - 将任务{$task->id}插入排队表");
	}
	else{
		$logger->debug(SELF." - 未找到待启动任务,查询排队任务");
	}

	$task = getQueueTask($currentmachine, TASK_IMPORTUSERID);
	if(empty($task)){
		$logger->debug(SELF." - 未获取到排队任务，退出");
		exit;
	}
	$logger->debug(SELF." - 获取到排队任务{$task->id}");
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
	$logger->info(SELF." - 任务{$task->id}启动");
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
		if($solrnewcount > 0 && empty($task->taskparams->iscommit)){
			$solr_r = handle_solr_data(array(),SOLR_URL_INSERT."&commit=true");
			if($solr_r !== NULL){
				$logger->error(SELF." - 提交solr返回{$solr_r}");
			}
		}
		completeTask($task);
		$logger->info(SELF." - 任务{$task->id}完成");
	}
	else{
		if($succcallsolr && empty($task->taskparams->iscommit)){
			$solr_r = handle_solr_data(array(), SOLR_URL_UPDATE."&commit=true");
			$logger->info(SELF." - 提交solr返回{$solr_r}");
		}
		if($needqueue){
			queueTask($task);
			$logger->info(SELF." - 任务{$task->id}排队，退出");
		}
		else{
			stopTask($task);
			$logger->info(SELF." - 任务{$task->id}停止");
		}
	}
}
catch(Exception $ex){
	fatalTask($task);
	$logger->fatal(SELF." - 任务{$task->id}异常".$ex->getMessage());
	exit;
}
exit;

/*
 * 执行任务
 */
function execute()
{
	global $logger, $task, $needqueue,
	$apicount, $apitime, $apierrorcount, $apishowusercount, $apifriendscount, $solrnewcount, $solrquerytime, $solrupdatetime;
	$task->taskparams->scene->status_desp = "";
	if(!isset($task->taskparams->select_cursor)){
		$task->taskparams->select_cursor = 0;//上次处理的位置
	}
	if(!isset($task->taskparams->scene)){
		$task->taskparams->scene = (object)array();
	}
	if(!isset($task->taskparams->scene->apierrorcount)){
		$task->taskparams->scene->apierrorcount = 0;//访问API的错误数
	}
	if(!isset($task->taskparams->scene->api_showuser_count)){
		$task->taskparams->scene->api_showuser_count = 0;
	}
	if(!isset($task->taskparams->scene->api_showuser_errorcount)){
		$task->taskparams->scene->api_showuser_errorcount = 0;
	}
	if(!isset($task->taskparams->scene->insert_user_count)){
		$task->taskparams->scene->insert_user_count = 0;
	}
	if(!isset($task->taskparams->scene->update_user_count)){
		$task->taskparams->scene->update_user_count = 0;
	}
	if(!isset($task->taskparams->scene->alltime)){
		$task->taskparams->scene->alltime = 0;
	}
	if(!isset($task->taskparams->scene->solr_query_time)){
		$task->taskparams->scene->solr_query_time = 0;
	}
	if(!isset($task->taskparams->scene->solr_update_time)){
		$task->taskparams->scene->solr_update_time = 0;
	}
	if(!isset($task->taskparams->scene->api_showuser_time)){
		$task->taskparams->scene->api_showuser_time = 0;
	}
	if(!isset($task->taskparams->scene->error_notext_datas)){
		$task->taskparams->scene->error_notext_datas = array();
	}
	if(!isset($task->taskparams->scene->error_other_datas)){
		$task->taskparams->scene->error_other_datas = array();
	}
	$r = true;
	$logger->debug("enter execute");
	$datas = $task->taskparams->data;
	$logger->debug("select_curosr:".$task->taskparams->select_cursor);
	$allcount = count($datas);
	if(empty($datas) || $task->taskparams->select_cursor >= $allcount){
		$logger->warn(SELF." 未找到需要处理的数据");
		return true;
	}
	$start_t = microtime_float();
	$logger->info(SELF." 共有{$allcount}条，剩余".($allcount - $task->taskparams->select_cursor)."条");
	$source = $task->taskparams->source;
	$inputtype = $task->taskparams->datatype;
	$seeduser = $task->taskparams->isseed;
	$getfriends = $task->taskparams->getfriends;
	$insertcount = 0;
	$updatecount = 0;
	while($allcount > $task->taskparams->select_cursor)
	{
		$st = getTaskStatus($task->id);
		if($st == -1){
			$logger->info(SELF." - 人工停止");
			$task->taskparams->scene->status_desp = "人工停止";
			$r = false;
			break;
		}
		$_data = object_array($datas[$task->taskparams->select_cursor]);
		$id = NULL;
		$name = NULL;
		if($inputtype == "id"){
			$id = $_data;
		}
		else{
			$name = $_data;
		}
		$friendsinfo = NULL;
		if(!empty($task->taskparams->friendsinfo)){
			$friendstr = json_encode($task->taskparams->friendsinfo);
			unset($task->taskparams->friendsinfo);
			$task->taskparams->friendsinfo = json_decode($friendstr, true);
			$friendsinfo = &$task->taskparams->friendsinfo;
		}
		//为分词方案变量赋值  方案数据在衍生任务时被保存在数据库
		global $dictionaryPlan;
		$dictionaryPlan=$task->taskparams->dicionary_plan;
		$t_r = update_userinfo($source, $id, $name, $seeduser, $getfriends, $friendsinfo, UPDATE_ACTION_FORCE);
		if($t_r['result']){
			$insertcount += $t_r['newcount'];
			$updatecount += $t_r['updatecount'];
		}
		else if(!empty($t_r['notext'])){
			$task->taskparams->scene->status_desp = '';
			$task->taskparams->scene->error_notext_datas[] = $_data;
		}
		else{
			$r = false;
			if(!empty($t_r['nores'])){
				$needqueue = true;
			}
			else{
				$task->taskparams->scene->status_desp = $_data.":".$t_r['msg'];
				$task->taskparams->scene->error_other_datas[] = $_data;
			}
			break;
		}
		$task->taskparams->select_cursor++;
	}
	$end_t = microtime_float();
	$fulltime = $end_t - $start_t;
	$task->datastatus += $insertcount + $updatecount;
	$task->taskparams->scene->alltime += $fulltime;
	$task->taskparams->scene->insert_user_count += $insertcount;
	$task->taskparams->scene->update_user_count += $updatecount;
	$task->taskparams->scene->solr_query_time += $solrquerytime;
	$task->taskparams->scene->solr_update_time += $solrupdatetime;
	$solrnewcount = $insertcount + $updatecount;
	if($inputtype == "id"){
		$taskinfostr = "植入用户ID";
	}
	else{
		$taskinfostr = "植入用户昵称";
	}
	$logger->info(SELF." - {$taskinfostr} 统计条数：总访问API次数{$apicount}:(获取用户{$apishowusercount}次,获取关注{$apifriendscount}次),".
	    "出错{$apierrorcount}次, 入库{$solrnewcount}条, 新增用户{$insertcount}个,更新用户{$updatecount}个");
	$logger->info(SELF." - 统计时间：总处理时间{$fulltime}:(访问API时间{$apitime},查询solr时间{$solrquerytime},更新solr时间{$solrupdatetime})");
	$logger->debug('exit execute');
	return $r;
}