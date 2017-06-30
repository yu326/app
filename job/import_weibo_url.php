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

initLogger(LOGNAME_IMPORTWEIBOURL);
$res_machine;//机器资源
$res_ip;
$res_acc;
$needqueue;//是否需要排队
$succcallsolr = false;//是否成功访问solr

$apierrorcount = 0;//访问api失败数
$spidercount = 0;//抓取数
$insertweibotime = 0;
$analysistime = 0;
$apitime = 0;
$funtime = 0;
$newcount = 0;
$solrerrorcount = 0;
$spiderusercount = 0;
$updateusercount = 0;
$insertusertime = 0;
$apicount = 0;

$reposttask_weiboid = array();//本次需要添加转发任务的微博ID
$reposttask_weibomid = array();//……mid

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


	$task = getWaitingTask(TASKTYPE_SPIDER, TASK_IMPORTWEIBOURL);
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

	$task = getQueueTask($currentmachine, TASK_IMPORTWEIBOURL);
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
	if(!empty($task->taskparams->addreposttask) && !empty($reposttask_weiboid)){//添加转发任务
		$repost_r = addRepostTask($task->taskparams->source, $reposttask_weiboid, $reposttask_weibomid, $task->taskparams->reposttask->conflictdelay, $task->taskparams->reposttask->local, $task->taskparams->reposttask->remote, $task->taskparams->reposttask);
		if($repost_r['result'] == false){//增加任务失败
			$logger->error(SELF." 增加抓取转发任务失败:".$repost_r['msg']);
		}
		else{
			$logger->info(SELF." 增加抓取转发任务成功");
		}
	}
	myReleaseResource($task,$res_machine,$res_ip,$res_acc);
	if($r){
		if(($newcount - $solrerrorcount) > 0 && empty($task->taskparams->iscommit)){
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
			$logger->info(SELF." - 任务{$task->id}停止");
			stopTask($task);
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
	global $logger, $task,$dsql,$res_machine,$res_ip,$res_acc,$needqueue, $oAuthThird, $reposttask_weiboid,
	$apicount,$apierrorcount,$spidercount,$insertweibotime,$analysistime,$apitime,$funtime,$newcount,$solrerrorcount,
	$spiderusercount,$insertusertime,$updateusercount;
	$task->taskparams->scene->status_desp = "";
	if(!isset($task->taskparams->select_cursor)){
		$task->taskparams->select_cursor = 0;//上次处理的位置
	}
	if(!isset($task->taskparams->scene)){
		$task->taskparams->scene = (object)array();
	}
	if(!isset($task->taskparams->scene->spider_statuscount)){
		$task->taskparams->scene->spider_statuscount = 0;//总抓取条数
	}
	if(!isset($task->taskparams->scene->insertsql_statustime)){
		$task->taskparams->scene->insertsql_statustime = 0;//总入库时间
	}
	if(!isset($task->taskparams->scene->insertsql_statuscount)){
		$task->taskparams->scene->insertsql_statuscount = 0;//入库条数
	}
	if(!isset($task->taskparams->scene->spider_usercount)){
		$task->taskparams->scene->spider_usercount = 0;//新用户数
	}
	if(!isset($task->taskparams->scene->insertsql_usertime)){
		$task->taskparams->scene->insertsql_usertime = 0;//用户入库时间
	}
	if(!isset($task->taskparams->scene->apierrorcount)){
		$task->taskparams->scene->apierrorcount = 0;//访问API的错误数
	}
	if(!isset($task->taskparams->scene->exists_weibocount)){
		$task->taskparams->scene->exists_weibocount = 0;//已存在的
	}
	if(!isset($task->taskparams->scene->alltime)){
		$task->taskparams->scene->alltime = 0;
	}
	if(!isset($task->taskparams->scene->api_queryid_count)){
		$task->taskparams->scene->api_queryid_count = 0;
	}
	if(!isset($task->taskparams->scene->api_showstatus_count)){
		$task->taskparams->scene->api_showstatus_count = 0;
	}
	if(!isset($task->taskparams->scene->api_showuser_count)){
		$task->taskparams->scene->api_showuser_count = 0;
	}
	if(!isset($task->taskparams->scene->userexists_count)){//已存在的用户数
		$task->taskparams->scene->userexists_count = 0;
	}
	if(!isset($task->taskparams->scene->user_count)){//所有的用户数
		$task->taskparams->scene->user_count = 0;
	}
	if(!isset($task->taskparams->scene->update_user_count)){//更新的用户数
		$task->taskparams->scene->update_user_count = 0;
	}
	if(!isset($task->taskparams->scene->error_notext_datas)){
		$task->taskparams->scene->error_notext_datas = array();
	}
	if(!isset($task->taskparams->scene->error_other_datas)){
		$task->taskparams->scene->error_other_datas = array();
	}
	$r = true;
	$logger->debug("enter execute");
	$datas = empty($task->taskparams->data) ? $task->taskparams->urls : $task->taskparams->data;//兼容以前的数据格式(url)
	$logger->debug("select_curosr:".$task->taskparams->select_cursor);
	$allcount = count($datas);
	if(empty($datas) || $task->taskparams->select_cursor >= $allcount){
		$logger->warn(SELF." 未找到需要处理的数据");
		return true;
	}
	$logger->info(SELF." 共有{$allcount}条url，剩余".($allcount - $task->taskparams->select_cursor)."条");
	$sourceid = $task->taskparams->source;
	//$urltype = $task->taskparams->urltype;//“id” “url”“weibo”“comment”四种取值
	$urltype = empty($task->taskparams->datatype) ? $task->taskparams->urltype : $task->taskparams->datatype;
	$chkfieldname = '';//$urltype == 'id' ? 'id' : 'mid';
	$newweibo = array();//已抓取的微博
	$oldcursor = $task->taskparams->select_cursor;
	$isseed = empty($task->taskparams->isseed) ? false : true;
	if($urltype == "comment"){
		$_r = getcomment($sourceid,$datas);
		if($_r['result']){
			if(isset($_r['comments'])){
				$newweibo = $_r['comments'];
			}
			else if(isset($_r['existids'])){//数据库已存在
				$task->taskparams->scene->exists_weibocount = count($_r['existids']);
			}
			if(!empty($newweibo)){
				$solr_r = insert_comment($newweibo,'comments_show_batch',$sourceid);
				if($solr_r['result'] === false){
					$r = false;
					$task->taskparams->scene->status_desp = "入库失败";
					$logger->error(SELF." 评论入库失败");
				}
				else{
					$task->taskparams->select_cursor = $allcount;
					$task->datastatus = $allcount;
				}
			}
		}
		else{//未抓取到评论
			$r = false;
			if(!empty($_r['nores'])){
				$needqueue = true;
			}
			else{
				$task->taskparams->scene->status_desp = "抓取评论失败";
				$logger->error(SELF." ".$_r['msg']);
			}
		}
		return $r;
	}
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
		$id = "";
		$chkfieldname = "";
		$needqueryid = false;
		$currvalue='';//当前url或ID
		if($urltype == "url"){
			$currvalue = is_array($_data) ? $_data['url'] : $_data;
			if($currvalue == ''){
				$task->taskparams->select_cursor++;
				continue;
			}
			$mid = weiboUrl2mid($currvalue,$sourceid);
			$chkfieldname = " mid = '{$mid}'";
			$needqueryid = true;
		}
		else if($urltype == 'weibo'){
			if(!empty($_data['id'])){
				$id = $_data['id'];
				if(!empty($_data['mid'])){
						$chkfieldname = " (id = '{$id}' or mid = '{$_data['mid']}')";
				}
				else{
					$chkfieldname = " id = '{$id}'";
				}
			}
			else{
				$mid = $_data['mid'];
				$chkfieldname = " mid = '{$mid}'";
			}
		}
		else{
			$id = is_array($_data) ? $_data['id'] : $_data;
			$currvalue = $id;
			$chkfieldname = " id = '{$id}'";
		}
		$sqlsel = "select id,mid,userid, update_time, isseed from ".DATABASE_WEIBO." where sourceid = {$sourceid} and {$chkfieldname}";
		$qr = $dsql->ExecQuery($sqlsel);
		if(!$qr){
			$logger->error(SELF.' sql :'.$sqlsel.' error: '.$dsql->GetError());
			$task->taskparams->scene->status_desp = "查询微博是否存在失败";
			$r = false;
			break;
		}
		else{
			$q_r = $dsql->GetArray($qr);
			if(!empty($q_r)){
				//数据库存在，判断上次更新时间距离当前时间是否小于阈值，小则不需要更新
				if((time() - $q_r['update_time']) < TIMELIMIT_UPDATEWEIBO){
					$logger->debug(SELF." weibo:".json_encode($_data)."已存在，跳过");
					if(!empty($task->taskparams->addreposttask)){//添加转发任务
						if(!empty($q_r['id'])){
							$reposttask_weiboid[] = $q_r['id'];
						}
						else if(!empty($q_r['mid']) && !empty($q_r['userid'])){
							//转发任务，只处理id或url，所以将mid的转成URL
							$mid2url = weibomid2Url($q_r['userid'],$q_r['mid'],$sourceid);
							if(!empty($mid2url)){
								$reposttask_weibomid[] = $mid2url;
							} 
						}
					}
					$task->taskparams->scene->exists_weibocount++;
					$task->taskparams->select_cursor++;//指向下一条
					if($q_r['isseed'] == 0 && $isseed){//旧数据非种子，任务中指定为种子，修改旧数据
						setSeedWeibo($sourceid, $q_r['id'], $q_r['mid']);
					}
					continue;
				}
				else {//时间超出，需要更新
					if($urltype == "url" && !empty($q_r['id'])){
						$id = $q_r['id'];
						$needqueryid = false;
					}
				}
			}
			if($needqueryid){
				$qid_r = queryid($mid);
				if($qid_r['result'] == false){
					$logger->error(SELF." {$currvalue}:".$qid_r['msg']);
					if(isset($qid_r['error_code']) && $qid_r['error_code'] == ERROR_CONTENT_NOT_EXIST){
						$task->taskparams->scene->status_desp = '';
						$task->taskparams->scene->error_notext_datas[] = $currvalue;
					}
					else{
						$task->taskparams->scene->status_desp = $currvalue.":".$qid_r['msg'];
						if(empty($qid_r['nores'])){
							$task->taskparams->scene->error_other_datas[] = $currvalue;
						}
					}
					if($qid_r['nores']){
						$needqueue = true;
						$r = false;
						break;
					}
				}
				else if(isset($qid_r['weiboid'])){
					$task->taskparams->scene->status_desp = '';
					$id = $qid_r['weiboid'];
				}
				else{
					$r = false;
					break;
				}
			}
			
			//urltype为weibo时
			if($urltype == 'weibo'){
				$newweibo[] = $_data;
			}
			else if(!empty($id)){
				$show_r = show_status($id);
				if($show_r['result'] == false){
					$logger->error(SELF." {$currvalue}:".$show_r['msg']);
					//内容不存在
					if(isset($show_r['error_code']) && $show_r['error_code'] == ERROR_CONTENT_NOT_EXIST){
						$task->taskparams->scene->status_desp = '';//网页上呈现的错误信息
						$task->taskparams->scene->error_notext_datas[] = $currvalue;
					}
					else{
						$task->taskparams->scene->status_desp = $currvalue.":".$show_r['msg'];//网页上呈现的错误信息
						if(empty($show_r['nores'])){
							$task->taskparams->scene->error_other_datas[] = $currvalue;
						}
					}
					if($show_r['nores']){
						$needqueue = true;
						$r = false;
						break;
					}
				}
				else if(!empty($show_r['weibo'])){
					$task->taskparams->scene->status_desp = '';
					$newweibo[] = $show_r['weibo'];
				}
				else{
					$r = false;
					break;
				}
			}
			else{
				$logger->warn(SELF." id is empty, next");
			}
		}
		$task->taskparams->select_cursor ++;
	}
	//只要抓取了微博，无论成功失败，先入库
	if(!empty($newweibo)){
		if($urltype == "weibo"){//先补全信息
			$incomlplete_weibos = array();
			$readysenddata = array();
			$sw_r = supplyWeibo($sourceid, $newweibo);
			if($sw_r === true){
				$readysenddata = $newweibo;
			}
			else{
				$r = false;
			 	if($sw_r === NULL){
					$needqueue = true;//无资源需要排队
				}
				while($weibo = array_shift($newweibo)){
					if(!empty($weibo['retweeted_status'])){//转发
						if(!empty($weibo['user']) && !empty($weibo['retweeted_status']['user'])){
							$readysenddata[] = $weibo;
						}
						else{//转发，或者原创没有找到user
							$incomlplete_weibos[] = $weibo;
						}
					}
					else{//原创
						if(!empty($weibo['user'])){
							$readysenddata[] = $weibo;
						}
						else{
							$incomlplete_weibos[] = $weibo;
						}
					}
				}
			}
		}
		else{
			$spidercount += count($newweibo);
			$readysenddata = $newweibo;
		}
		//$logger->debug("readysenddata:".var_export($readysenddata,true));
		//为分词方案变量赋值  方案数据在衍生任务时被保存在数据库
		global $dictionaryPlan;
		$dictionaryPlan=$task->taskparams->dicionary_plan;
		$logger->debug("用户下载方案:".var_export($dictionaryPlan,true));
		//$s_r = insert_status2($readysenddata,'show_status',$sourceid,NULL,NULL, $isseed);
		$solr_r = addweibo($sourceid, $readysenddata,$isseed,'show_status',true);		
		if($s_r['result'] !== true){
			$logger->error(SELF." - 调用insert_status2失败，返回".var_export($s_r,true));
			$task->taskparams->scene->status_desp = "调用solr失败";
			$task->taskparams->select_cursor = $oldcursor;//入库失败，相当于本次微博都未处理，恢复旧索引
			if($urltype == 'weibo'){
				$incomlplete_weibos = array_merge($incomlplete_weibos, $readysenddata);//入库失败，记录所有微博退出
			} 
			$r = false;
		}
		else{
			if(!empty($task->taskparams->addreposttask)){//添加转发任务
				foreach($readysenddata as $k => $v){
					if(!isset($v['retweeted_status'])){//原创
						if(!empty($v['id'])){
							$reposttask_weiboid[] = $v['id'];
						}
						else if(!empty($v['mid']) && !empty($v['userid'])){
							//转发任务，只处理id或url，所以将mid的转成URL
							$mid2url = weibomid2Url($v['userid'],$v['mid'],$sourceid);
							if(!empty($mid2url)){
								$reposttask_weibomid[] = $mid2url;
							} 
						}
					}
				}
			}
			$succcallsolr = true;
		}
	}
	//将未完成的微博记录
	if($urltype == 'weibo' && !empty($incomlplete_weibos)){
		$task->taskparams->select_cursor = 0;
		$task->taskparams->data = $incomlplete_weibos;
		$r = false;
	}
	if($urltype == "url"){
		$taskinfostr = "植入微博URL";
	}
	else if($urltype == "id"){
		$taskinfostr = "植入微博ID";
	}
	else if($urltype == "weibo"){
		$taskinfostr = "植入微博内容";
	}
	$task->taskparams->scene->insertsql_statustime += $insertweibotime;
	$task->taskparams->scene->insertsql_usertime += $insertusertime;
	$task->taskparams->scene->update_user_count += $updateusercount;
	$task->taskparams->scene->insert_user_count += $spiderusercount;
	$logger->info(SELF." - {$taskinfostr} 统计条数：总访问API次数{$apicount},(查询ID{$task->taskparams->scene->api_queryid_count}次，获取微博{$task->taskparams->scene->api_showstatus_count}次，获取用户{$task->taskparams->scene->api_showuser_count}次)，".
	    "出错{$apierrorcount}次, 总抓取{$spidercount}条,入库{$newcount}条, 调用solr总失败{$solrerrorcount}条,数据库已存在微博{$task->taskparams->scene->exists_weibocount}条，更新微博{$task->taskparams->scene->update_weibocount}条，总新增用户{$spiderusercount}个，更新用户{$updateusercount}个，数据库已存在用户{$task->taskparams->scene->userexists_count}个");
	$logger->info(SELF." - 统计时间：访问API时间{$apitime},总处理时间{$funtime}:(插入微博时间{$insertweibotime},插入用户时间{$insertusertime},分析时间{$analysistime})");
	$logger->debug('exit execute');
	return $r;
}