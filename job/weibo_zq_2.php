<?php
define( "SELF", basename(__FILE__) );

if ($argc > 1) {
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
}

define( "GET_WEIBO" , 2 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", GET_WEIBO );    //需要在include common.php之前，定义CONFIG_TYPE

include_once( 'includes.php' );
include_once('weibo_config.php');
include_once( 'weibo_class.php' );
include_once( 'saetv2.ex.class.php' );
include_once('taskcontroller.php');
include_once('jobfun.php');
ini_set('include_path',get_include_path().'/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_CRAWLING);//使用同步模块的日志配置
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
$ishang = false; //是否挂起
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


    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_WEIBO);
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

    $task = getQueueTask($currentmachine, TASK_WEIBO);
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
  	    if(($newcount - $solrerrorcount) > 0 && empty($task->taskparams->iscommit)){
		    $solr_r = handle_solr_data(array(),SOLR_URL_INSERT."&commit=true");
			if($solr_r !== NULL){
				$logger->error(SELF." - 提交solr返回{$solr_r}");
			}
		}
        completeTask($task);
        $logger->info(SELF." - 任务完成");
    }
    else{
    	if(empty($task->taskparams->iscommit)){
    	  	$solr_r = handle_solr_data(array(), SOLR_URL_UPDATE."&commit=true");
			$logger->info(SELF." - 提交solr返回{$solr_r}");
    	}
        if($needqueue){
            queueTask($task);
            $logger->info(SELF." - 任务排队，退出");
        }
        else if($ishang){
            $logger->info(SELF." - 任务挂起");
            hangTask($task);
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
function crawling_user_timeline($user_info,$page,$each_count){
    global $apicount,$apierrorcount,$task,$logger,$oAuthThirdBiz,$apitime,$res_machine,$res_ip,$res_acc,$needqueue;
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
    $logger->info(SELF." - user_timeline:page:{$page},count:{$each_count},userid:{$user_info['id']}");
    //$weibos_info = $oAuthThird->user_timeline(1, 200, 1883350564 , null, null);
    $weibos_info = $oAuthThirdBiz->user_timeline($page, $each_count, $user_info['id']);
    $end_time = microtime_float();
    $apicount++;
    $apitimediff = $end_time - $start_time;
    $apitime += $apitimediff;
    $task->taskparams->scene->apicount_usertimeline++;
    $task->taskparams->scene->apitime_usertimeline += $apitimediff;
    $logger->info(SELF." - 调用user_timeline花费时间：".$apitimediff);
    $result = true;
    if ($weibos_info === false || $weibos_info === null)
    {
        $apierrorcount++;
        $task->taskparams->scene->apierrorcount_usertimeline++;
        $logger->error("user_timeline异常：page:{$page},count:{$each_count},userid:{$user_info['id']}");
        $result = false;
    }
    else if (isset($weibos_info['error_code']) && isset($weibos_info['error']))
    {
        $apierrorcount++;
        $task->taskparams->scene->apierrorcount_usertimeline++;
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
            $logger->error(SELF." - user_timeline 登录失败,源{$task->tasksource},username:{$res_acc->resource} ".$weibos_info['error_code']." - ".$weibos_info['error']);
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
    if($result === false || $result === NULL){
        unset($weibos_info);
        return $result;
    }
    else{
        return $weibos_info;
    }
}

function updateUserInfo($userinfo){
    global $dsql, $logger;
    $updata['weibo_since_id'] = $userinfo['weibo_since_id'];
    $updata['weibo_max_id'] = $userinfo['weibo_max_id'];
    $updata['weibo_new_id'] = $userinfo['weibo_new_id'];
    $whdata['id'] = $userinfo['id'];
    $whdata['sourceid'] = $userinfo['sourceid'];
    $sql = update_template(DATABASE_USER, $updata, $whdata);
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("update userinfo errorsql {$sql} error:".$dsql->GetError());
        return false;
    }
    else{
        return true;
    }
}

/*
 * 通过用户ID查询微博信息
 */
function get_weibo(&$user_info,$each_count,$maxcount)
{
    global $config, $logger, $task,$ishang,$oAuthThird;
    $logger->debug('enter get_weibo');
    $each_count = $each_count ? $each_count :  200;
    //获取用户断档
    $interrupts = getUserInterrupts($user_info['id'],$user_info['sourceid']);
    if($interrupts === false){
        return false;
    }
    if(isset($task->taskparams->scene->hangaction) && $task->taskparams->scene->hangaction != 0){
        $hangaction = $task->taskparams->scene->hangaction;
        $hangstate = $task->taskparams->scene->hangstate;

        $task->taskparams->scene->hangaction = 0;//清空状态
        $task->taskparams->scene->hangstate = 0;

    }
    $complete_interrupts = array();//已完成的断档
    $getold = empty($interrupts);//尚未进行过抓取
    $page = 1;
    $result = true;
    $newtime; //本次最新获取的微博
    $_lasttime = 0;//本次抓取的微博中最早的时间
    $_lastid=0;
    $secget = 0;
    do{
        //_interrupt为空，说明尚未处理断档，获取断档并计算页码
        //如果getold为true，说明没有断档
        //处理完此断档后，将_interrupt置空
        if(empty($_interrupt) && !$getold){
            $_interrupt = $interrupts[0];
            //计算页码
            if($_interrupt['type'] == 'newest'){
                $page = 1;//从最新的抓
            }
            else{
                $page = getUserInterruptPage($user_info['id'],$user_info['sourceid'],$_interrupt['righttime'], $each_count);
                if($page === false){
                    $result = false;
                    break;
                }
            }
        }
        while(1){
            unset($weibos_info);
            $weibos_info = crawling_user_timeline($user_info,$page,$each_count,NULL,NULL,NULL);//不使用sinceid和maxid
            if($weibos_info === false){
                $result =  false;
                break 2;
            }
            else if($weibos_info === NULL){
                continue;
            }
            $weibo_count = count($weibos_info);
            $logger->debug("本次新抓{$weibo_count}条");
            if($weibo_count == 0){
            	  //第一页未抓取到，取得total_number比较
            	  if($page == 1){
            	  	  $lastdata = $oAuthThird->getLastAPIData();
            	  	  if(!empty($lastdata) && isset($lastdata['total_number'])){
            	  	      $total_num = $lastdata['total_number'];
            	  	      if($total_num == 0){
            	  	         $logger->info("用户{$user_info['id']}没有可显示的微博");
            	  	         break 2;
            	  	      }
            	  	  }
            	  }

                if($secget == 0){
                    //判断微博是否全部抓取完毕，否则继续抓取旧微博
                    $exist_count = getExistsCountByUserID($user_info['id']);
                }
                if( ($page-1)*$each_count < $user_info['statuses_count'] && $exist_count < $user_info['statuses_count'] ){
                    if($secget < MAXREGET_COUNT){
                        $secget++;
                        $logger->debug("开始第{$secget}次重复抓取{$user_info['id']}的转发微博。页码：".$page);
                        continue;
                    }
                    else if($hangaction != 1){
                        $ishang = true;
                        $result = false;
                        if(empty($getold)){//抓取新微博时，挂起的
                            $task->taskparams->scene->hangstate = 1;
                            $task->taskparams->scene->hangreason = "抓取用户{$user_info['screen_name']}的第{$page}页新微博时，挂起";
                        }
                        else{
                            $task->taskparams->scene->hangstate = 2;
                            $task->taskparams->scene->hangreason = "抓取用户{$user_info['screen_name']}的第{$page}页旧微博时，挂起";
                        }
                        $logger->warn(SELF." 任务挂起：{$task->taskparams->scene->hangreason}");
                        break 2;
                    }
                }
                else{
                    $logger->info("种子用户{$user_info['screen_name']}的微博抓取完毕");
                }
                break 2;//继续下一个用户
            }
            else{
                $secget = 0;
                if($page == 1){
                    //取得真正的最新的微博，解决置顶微博的问题
                    if($weibo_count > 1){
                        foreach ($weibos_info as $key => $value){
                            if(isset($weibos_info[$key+1])){
                                //找到最大的时间。判断当前微博是否比下一条时间大，如果大说明找到了最新的
                                $curtime = strtotime($weibos_info[$key]['created_at']);
                                $nexttime = strtotime($weibos_info[$key+1]['created_at']);
                                if($curtime > $nexttime){
                                    $newtime = $curtime;
                                    break;
                                }
                                else{//当前微博小于下一条的时间，说明当前微博可能为置顶，继续找
                                    $newtime = $nexttime;
                                    continue;
                                }
                            }
                        }
                    }
                    else{
                        $newtime = strtotime($weibos_info[0]['created_at']);
                    }
                }
                //更新用户的微博数，被删除的微博没有user对象，所以用循环
                foreach ($weibos_info as $key => $value){
                    if(isset($value['user']) && isset($value['user']['statuses_count'])){
                        $user_info['statuses_count'] = $value['user']['statuses_count'];
                        break;
                    }
                }
                if(!empty($_lastid) && !empty($_lasttime)){
                    //比较两次抓取是否重复，去重
                    for($i = 0; $i < $weibo_count; $i++){
                        $curtime = strtotime($weibos_info[$i]['created_at']);
                        //本次抓取的微博中如果有时间比上次抓取的微博时间大的，判断id是否与上次最后一个ID相同
                        //找到相同的ID，说明该条记录之前的微博都是跟上次重复的
                        if($curtime >= $_lasttime){
                            if($weibos_info[$i]['id'] == $_lastid){
                                array_splice($weibos_info,0,$i+1);
                                break;
                            }
                        }
                        else{
                            break;
                        }
                    }
                }
                $_c = count($weibos_info);
                if($_c > 0){
                    $_lastid = $weibos_info[$_c-1]['id'];
                    $_lasttime = strtotime($weibos_info[$_c - 1]['created_at']);

                    //用户字段空，非正常状态
                    /*if(!isset($weibos_info[0]['user'])){
                        $logger->warn("1 取得的微博无user字段，跳过该用户");
                        unset($weibos_info);
                        break;
                    }
                    //判断微博中的用户id是否与当前用户相同，不同则说明用户已被删除
                    if($weibos_info[0]['user']['id'] != $user_info['id']){
                        $logger->warn("1 取得的ID与种子用户ID不同，跳过该用户");
                        unset($weibos_info);
                        break;
                    }*/
                    //$solr_r = insert_status2($weibos_info, 'user_timeline', $task->tasksource);
					//补充
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
							}
						}
					}
					$solr_r = addweibo($task->tasksource, $weibos_info,0,'user_timeline',true);
                    if($solr_r['result'] === false){
                        $logger->error(SELF." - insert_status2返回 false");
                        unset($weibos_info);
                        $result = false;
                        break 2;
                    }
                    else if (is_array($solr_r)){
                        $logger->error(SELF." - 中断获取此种子用户的微博");
                        break 2;
                    }

                    if(!empty($_lasttime) && !empty($_interrupt)){
                        //当前断档已被覆盖
                        if($_lasttime < $_interrupt['righttime']){
                            $_interrupt = NULL;
                        }
                        /*
                        //依次处理每个断档 
                        foreach($interrupts as $key=>$value){
                            if($_lasttime < $value['righttime']){
                                $logger->debug("本次抓取最小时间{$_lasttime},消除断档{$value['righttime']}");
                                $complete_interrupts[] = $value;
                                array_shift($interrupts);
                            }
                            else{
                                break;
                            }
                        }
                        */
                        $rinter = handleUserTimeline(NULL, array('created_at'=>$_lasttime), $user_info['sourceid'], $interrupts, false, true);
                        if(!$rinter['result']){
                        	$result = false;
                        	$logger->error(SELF." ".$rinter['msg']);
                            break 2;
                        }
                    }
                    //判断，尚未处理旧微博，并且没有断档了。
                    if(empty($getold) && empty($interrupts)){
                        //判断微博是否全部抓取完毕，否则继续抓取旧微博
                        if($page*$each_count < $user_info['statuses_count']){
		                        $exist_count = getExistsCountByUserID($user_info['id']);
		                        if($exist_count < $user_info['statuses_count']){
		                            //计算页码,抓取旧微博
		                            $page = calcPage($exist_count, $each_count);
		                            if($page*$each_count < $user_info['statuses_count']){
				                            $getold = true;
				                            $logger->info("断档处理完毕，继续抓取第{$page}页的旧微博");
				                            continue;
				                        }
				                        else{
				                        	  break 2;
				                        }
		                        }
		                        else{
		                            $logger->info("断档处理完毕，用户微博全部抓取完毕");
		                            break 2;
		                        }
		                    }
                    }
                    //尚未抓取旧微博，并且当前断档已消除
                    if(!$getold && $_interrupt == NULL){
                        break;//执行下一个断档
                    }
                }
                $page++;
            }
            try{
                $task->taskparams->scene->alltime = ($task->taskparams->scene->insertsql_statustime + $task->taskparams->scene->insertsql_usertime + $task->taskparams->scene->apicount_usertimeline);
                updateTaskInfo($task);
            }
            catch(Exception $ex){
                $logger->error($ex->getMessage());
            }
        }
    }while(count($interrupts) > 0);
    /*
    //找到最新的断档，如果没有，说明被消除
    if(!empty($interrupts)){
        foreach($interrupts as $key => $value){
            if($value['type'] == 'newest'){
                $newest_interrupt = $value;
                break;
            }
        }
    }
    //设置最新的断档时间
    if(!empty($newtime) && (empty($newest_interrupt) || $newest_interrupt['righttime'] < $newtime)){
        $rnewtime = setUserNewtime($user_info['id'],$user_info['sourceid'],$newtime);
        if($rnewtime === false){
            $result = false;
        }
    }
    //最左侧断档没有消除，说明又产生了新的断档，老的的newtime变为旧断档
    if(!empty($newest_interrupt) && !empty($_lastid)){
        $rinterrupt = setUserInterrupt($_lastid, $user_info['sourceid'],$newest_interrupt['righttime']);
        if($rinterrupt === false){
            $result = false;
        }
    }
    if(!empty($complete_interrupts)){
        foreach($complete_interrupts as $k=>$v){
            if(empty($v['id'])){
                continue;
            }
            $rinterrupt = setUserInterrupt($v['id'],$user_info['sourceid'],NULL);
            if($rinterrupt === false){
                $result = false;
                break;
            }
        }        
    }
    */
    if(!$result){
    	return $result;
    }
    $left = array('created_at'=>0);
    if(!empty($newtime)){
    	$left['userid'] = $user_info['id'];
    	$left['created_at'] = $newtime;
    }
    $right = array('created_at'=>0);
    if(!empty($_lastid)){
    	$right['id'] = $_lastid;
    	$right['created_at'] = time();
    }
    $rinter = handleUserTimeline($left, $right, $user_info['sourceid'], $interrupts, false, true);
    if(!$rinter['result']){
        $result = false;
        $logger->error(SELF." ".$rinter['msg']);
    }
    return $result;

}

/*
 * 执行任务
 */
function execute()
{
    global $logger, $task,$dsql,$apicount,$apierrorcount,$insertweibotime,$analysistime,$funtime,
    $spidercount,$newcount,$solrerrorcount, $spiderusercount,$insertusertime,$apitime,$needqueue;
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
    if(!isset($task->taskparams->scene->apicount_usertimeline)){
        $task->taskparams->scene->apicount_usertimeline = 0;//访问API的次数
    }
    if(!isset($task->taskparams->scene->apitime_usertimeline)){
        $task->taskparams->scene->apitime_usertimeline = 0;//访问API的时间
    }
    if(!isset($task->taskparams->scene->apierrorcount_usertimeline)){
        $task->taskparams->scene->apierrorcount_usertimeline = 0;//访问API的错误数
    }
    if(!isset($task->taskparams->scene->alltime)){
        $task->taskparams->scene->alltime = 0;
    }
    $r = true;
    $logger->debug("enter execute");
    /*
     * 从数据库中的任务表，得到任务参数，其中包括查询的起始点
     * 和一次查询条数
     * 查询后，需要更新任务表的查询位置
     */
    //每次抓取多少条
    $each_count = isset($task->taskparams->each_count) ? $task->taskparams->each_count : 200;
    $usermaxcount = isset($task->taskparams->usermaxcount) ? $task->taskparams->usermaxcount : NULL;
    while (1)
    {
        $seeduser = getSeedUser($task);//获取种子用户
        if(!empty($seeduser)){
            //获取到种子用户
            $logger->info(SELF." - 获取到种子用户:".$seeduser['screen_name']);
            $logger->debug('*** before get_weibo memory:'.memory_get_usage());
            $r = get_weibo($seeduser,$each_count,$usermaxcount);
            $logger->debug('*** after get_weibo memory:'.memory_get_usage());
            if($r == false){
                $logger->warn(SELF." - getweibo返回false");
                break;
            }
            $task->taskparams->select_user_cursor++;
            try{
                $task->taskparams->scene->alltime = ($task->taskparams->scene->insertsql_statustime + $task->taskparams->scene->insertsql_usertime + $task->taskparams->scene->apicount_usertimeline);
                updateTaskInfo($task);
            }
            catch(Exception $ex){
                $logger->error($ex->getMessage());
            }
            //            $upp = json_encode($task->taskparams);
            //            $uptsql = "update task set taskparams = '{$upp}' where id = {$task->id}";
            //            $qr = $dsql->ExecNoneQuery($uptsql);
            //            if(!$qr){
            //                $logger->warn(SELF." - execute 更新taskparams出错：".$dsql->GetError());
            //            }
            //$dsql->FreeResult($qr);
        }
        else{
        	if(!empty($needqueue) || ($task->taskparams->seedusercount > 0 && $task->taskparams->select_user_cursor < $task->taskparams->seedusercount)){
        		$logger->error("未获取到种子用户");
        		$r = false;
        	}
            break;
        }
    }
    //$task->datastatus += ($newcount - $solrerrorcount);
    $logger->info(SELF." - 统计条数：总访问API次数{$apicount},出错{$apierrorcount}次, 总抓取{$spidercount}条,入库{$newcount}条, 调用solr总失败{$solrerrorcount}条,总新增用户{$spiderusercount}个");
    $logger->info(SELF." - 统计时间：访问API时间{$apitime},总处理时间{$funtime}:(插入微博时间{$insertweibotime},插入用户时间{$insertusertime},分析时间{$analysistime})");
    $logger->debug('exit execute');
    return $r;
}
