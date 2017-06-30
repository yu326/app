<?php
/**
 * 
 * 处理转发的脚本
 * @author Todd
 * task->taskparams JSON对象说明：
 *     source 数据源
 *     each_count 每次抓取个数
 *     min_updatetime 更新转发数、评论数的时间.当数据库字段repost_trend_time 大于该值时不更新
 *     select_cursor 查询种子微博（原创）时使用的limit，记录处理到第几条
 *     ---种子微博条件----------------------------------
 *     andor  逻辑关系 and  or
 *     min_reposts_count 最少转发数
 *     oristatus 数组，指定原创id
 *     min_created_time  最小创建时间
 *     max_created_time  最大创建时间
 *     screen_name 数组 作者姓名（默认内部关系：or）
 *     ------------------------------------------------
 *     select_repost_cursor 查询种子微博的转发微博时，使用的limit, 记录处理到第几条
 * 
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
include_once('jobfun.php');
ini_set('include_path',get_include_path().'/lib');
require_once 'OpenSDK/Tencent/Weibo.php';

initLogger(LOGNAME_REPOST_TREND);//使用同步模块的日志配置
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
$updatereposttime = 0;//总更新转发信息时间

$api_counts_time = 0;//更新转发数评论数访问API花费时间
$api_counts_count = 0;//更新转发数评论数访问API次数
$solr_update_time = 0;//调用solr更新转发数花费时间
$solr_update_count = 0;//调用solr总次数
$sql_updatecounts_time = 0;//更新数据库转发数花费时间
$sel_reposts;//当前正在处理的所有转发数组
$curr_repostindex;//正在处理的转发数组索引

$currentseed_id;//当前种子微博ID
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
    $task = getWaitingTask(TASKTYPE_SPIDER, TASK_REPOST_TREND);//转发
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

    $task = getQueueTask($currentmachine, TASK_REPOST_TREND);
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
    $task->remote = 0;
    updateTaskFull($task);
    $r = execute();
    myReleaseResource($task,$res_machine,$res_ip,$res_acc);
    if($r){
        if($task->taskparams->select_cursor > 0 && empty($task->taskparams->iscommit)){
            $stime = microtime_float();
            $solr_r = handle_solr_data(array(), SOLR_URL_UPDATE."&commit=true");
            $etime = microtime_float();
            $logger->info(SELF." - 更新solr返回{$solr_r}花费".($etime - $stime));
        }
        completeTask($task);
        $logger->info(SELF." - 任务完成");
    }
    else{
    	  $task->taskparams->scene->hangrepost = $task->taskparams->scene->repost_trend_status > 2 ? 1 : 0;//已处理完原创
    	  if(empty($task->taskparams->iscommit)){
    	  		$solr_r = handle_solr_data(array(), SOLR_URL_UPDATE."&commit=true");
    	  		$logger->info(SELF." - 更新solr返回{$solr_r}");
    	  }
        if($needqueue){
            $task->taskparams->scene->queuecount ++;
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
function crawling_repost_timeline($orig_info,$page,$each_count,$since_id,$max_id){
    global $apicount,$apierrorcount,$task,$logger,$oAuthThird,$apitime,$res_machine,$res_ip,$res_acc,$needqueue,
    	$currentseed_id;

    checkAndApplyResource($task,$res_machine,$res_ip,$res_acc);
    if($task->taskparams->scene->state != SCENE_NORMAL){
        $logger->info(SELF." - ".getResourceErrorMsg($task->taskparams->scene->state));
        $needqueue = true;
        return false;
    }
    $needqueue = false;
    $since_id = empty($since_id) ? false : $since_id; 
    $max_id = empty($max_id) ? false : $max_id;
    //获取原创的所有转发，包括直接转发、间接转发
    if($orig_info['is_repost'] == 0){//orig_info为原创
        $apiname = 'repost_timeline';
        $start_time = microtime_float();
        $repost_statuses = $oAuthThird->repost_timeline($currentseed_id,
            $page, 
            $each_count,
            $since_id,
            $max_id);
		//$logger->debug("repost_statuses:".var_export($repost_statuses,true));
		foreach($repost_statuses as $k=>$repost){
			$repost_statuses[$k]['page_url'] = weibomid2Url($repost['user']['id'], $repost['mid'], 1);
			$repost_statuses[$k]['source_host'] = "weibo.com";				
			$repost_statuses[$k]['user']['page_url'] = userid2Url($repost['user']['id'],1);
			if(isset($repost['retweeted_status']) ){
				$repost_statuses[$k]['retweeted_status']['source_host'] = "weibo.com";
				if(isset($repost['retweeted_status']['user'])){
					$repost_statuses[$k]['retweeted_status']['page_url'] = weibomid2Url($repost['retweeted_status']['user']['id'], $repost_statuses[$k]['retweeted_status']['mid'], 1);
					$repost_statuses[$k]['retweeted_status']['user']['page_url'] = userid2Url($repost['retweeted_status']['user']['id'],1);
				}
			}
		}
        $end_time = microtime_float();
        $task->taskparams->scene->repost_timeline_count ++;
        $apitimediff = $end_time - $start_time;
        $task->taskparams->scene->repost_timeline_time += $apitimediff;
    }
    else{//orig_info为转发时，只获取id列表
        $apiname = 'repost_timeline_ids';
        $start_time = microtime_float();
        $repost_statuses = $oAuthThird->repost_timeline_ids($orig_info['id'],
            $page, 
            $each_count,
            $since_id,
            $max_id);
        $end_time = microtime_float();
        $task->taskparams->scene->repost_timeline_ids_count ++;
        $apitimediff = $end_time - $start_time;
        $task->taskparams->scene->repost_timeline_ids_time += $apitimediff;
    }
    $apicount++;
    $logger->info(SELF." - 调用{$apiname}(参数：id:{$orig_info['id']},page:{$page},count:{$each_count},since_id:{$since_id}, max_id:{$max_id})花费时间：".$apitimediff);
    $result = true;
    if ($repost_statuses === false || $repost_statuses === null)
    {
        $apierrorcount++;
        $task->taskparams->scene->apierrorcount ++;
        $logger->error("{$apiname}异常：page:{$page},count:{$each_count},origid:{$orig_info['id']},since_id:{$since_id},max_id:{$max_id}");
        $result = false;
    }
    else if (isset($repost_statuses['error_code']) && isset($repost_statuses['error']))
    {
        $apierrorcount++;
        $task->taskparams->scene->apierrorcount ++;
    	if(strpos($repost_statuses['error'], "source paramter(appkey) is missing") !== false){
			//当前帐号使用的appkey出现不可用，将当前帐号资源置成不可用切换下一个资源
			$logger->warn(__FUNCTION__." 禁用当前帐号资源，继续申请其他资源");
			$result = NULL;
		}
		else{
        	$result = checkAPIResult($repost_statuses);
		}
    }
    if($result === false || $result === NULL){
         unset($repost_statuses);
         return $result;
    }
    else{
        return $repost_statuses;
    }
}

//更新微博转发信息
function update_repost_info($weibo){
    global $logger,$dsql,$task, $updatereposttime;
    $updata['repost_sinceid'] = $weibo['repost_sinceid'];
    $updata['repost_maxid'] = $weibo['repost_maxid'];
    /*$updata['reach_count'] = $weibo['reach_count'];
    $updata['total_reach_count'] = $weibo['total_reach_count'];
    $updata['total_reposts_count'] = $weibo['total_reposts_count'];
    $updata['repost_trend_cursor'] = $weibo['repost_trend_cursor'];
    */
    $whdata['id'] = $weibo['id'];
    $whdata['sourceid'] = $task->taskparams->source;
    $sql = update_template(DATABASE_WEIBO,$updata,$whdata);
    $start_time = microtime_float();
    $qr = $dsql->ExecQuery($sql);
    $end_time = microtime_float();
    $timediff = $end_time - $start_time;
    $task->taskparams->scene->update_taskids_count ++;
    $task->taskparams->scene->update_taskids_time += $timediff;
    if(!$qr){
        $logger->error(SELF.' sql:'.$sql.' error:'.$dsql->GetError());
        return false;
    }
    else{
        return true;
    }
}


/*
 * 根据原创微博，获取转发微博
 * $orig_statuses:原创微博
 */
function get_repost($orig_statuses, $each_count, $forceupdate = 0)
{
    global $apicount, $task,$apierrorcount,$task,$logger,$oAuthThird,$apitime,
    $res_machine,$res_ip,$res_acc,$needqueue,$updatereposttime, $dsql, $sel_reposts, $curr_repostindex,$ishang;
    //$logger->debug("enter get_repost");
    $each_count = isset($each_count) ? $each_count : 200;
    $each_count = $each_count > 200 ? 200 : $each_count;

    $result = true;
    $_spcount = 0;//新抓条数
    //转发数小于1的不需要请求
	//当没有设置direct_reposts_count 说明没有分析过转发轨迹, 需要抓转发重新分析
    $whileopt = $orig_statuses['reposts_count'] > 0 && ($forceupdate || $orig_statuses['exists_count'] < $orig_statuses['reposts_count'] || empty($orig_statuses['direct_reposts_count']));
    if(!$whileopt){
        return 0;
    } 
    $newid;//本次抓取的最新ID
    $newtime;//本次抓取最新微博的时间
    $page = 1;
    if(!empty($orig_statuses['id'])){
        $idormid = $orig_statuses['id'];
        $ismid = false;
    }
    else{
        $idormid = $orig_statuses['mid'];
        $ismid = true;
    }
    if(isset($task->taskparams->scene->hangaction) && $task->taskparams->scene->hangaction != 0){
        $hangaction = $task->taskparams->scene->hangaction;
        $hangstate = $task->taskparams->scene->hangstate;
        
        $task->taskparams->scene->hangaction = 0;//清空状态
        $task->taskparams->scene->hangstate = 0;
        
    }
    if(empty($task->taskparams->scene->hanglasttime) || $task->taskparams->scent->hangparentid != $idormid){
    	$task->taskparams->scene->hanglasttime = 0;
    	$task->taskparams->scene->hanglastid = NULL;
    }
    
    //每次都先抓取最新的微博。
    $arg_sinceid = false;//empty($maxid) || empty($ignore_maxid) ? false : $maxid;
    $arg_maxid = false;//不指定maxid，因为repost_timeline接口有问题
    $secget = 0;//是否第二次请求同一段数据
    $_lasttime = $task->taskparams->scene->hanglasttime;//本次抓取的微博中最早的时间
    $_lastid=$task->taskparams->scene->hanglastid;
    
    $interrupts = getRepostInterrupts($idormid,$orig_statuses['is_repost'],$orig_statuses['sourceid'],$_lasttime,$ismid);
    if($interrupts === false){
        return false;
    }
    $getold = empty($interrupts);//尚未进行过抓取
    //$logger->debug("断档：".var_export($interrupts,true));
    
    $complete_interrupts = array();//已完成的断档
	$pagecontinue = false;
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
                $_page = getRepostInterruptPage($idormid,$orig_statuses['is_repost'],$orig_statuses['sourceid'],$_interrupt['created_at'], $each_count,$ismid);
                if($_page === false){
                    $result = false;
                    break;                    
                }
                $page = $_page < $page ? $page + 1 : $_page;
            }
        }
        while(1){
            unset($weibos_info);
            $st = getTaskStatus($task->id);
            if($st == -1){
                $logger->info(SELF." - 人工停止");
                return false;        
            }
            
            $weibos_info = crawling_repost_timeline($orig_statuses,$page,$each_count,
                $arg_sinceid,$arg_maxid);
            if($weibos_info === false){
                $result = false;
                break 2;
            }
            else if($weibos_info === NULL){//返回NULL时，表示可继续访问API
                continue;
            }
            $weibo_count = count($weibos_info);
            if ($weibo_count == 0)
            {
            	  //第一页未抓取到，取得total_number比较
            	  if($page == 1){
            	  	  $lastdata = $oAuthThird->getLastAPIData();
            	  	  if(!empty($lastdata) && isset($lastdata['total_number'])){
            	  	      $total_num = $lastdata['total_number'];
            	  	      if($total_num == 0){
            	  	         $logger->info("用户{$orig_statuses['userid']}的微博{$idormid}没有可显示的微博");	
            	  	         break 2;
            	  	      }
            	  	  }
            	  }
            	
                if($secget == 0){   
                    //判断微博是否全部抓取完毕，否则继续抓取旧微博
                    $exist_count = getExistsCount($orig_statuses);
                }
                if(($page-1)*$each_count < $orig_statuses['reposts_count'] &&  $exist_count < $orig_statuses['reposts_count'] ){
                    if($secget < MAXREGET_COUNT){
                        $secget++;
                        $logger->debug("开始第{$secget}次重复抓取{$idormid}的转发微博。页码：".$page);
                        continue;
                    }
                    else{ 
                    	//选择的继续，并且最后一次微博时间大于挂起的时间，则继续抓
                    	if($hangaction == 1 && $_lasttime >= $task->taskparams->scene->hanglasttime){
                    		$page++;
							$pagecontinue = true;
							$logger->debug("continue page++");
							continue;
                    	}
                        //if($hangaction != 1){//1表示上次挂起后，选择了“继续”，不再进行挂起
	                        //重试后，扔未抓取到，挂起
	                        $ishang = true;
	                        $result = false;
	                        if(empty($getold)){
	                            $task->taskparams->scene->hangstate = 1;//表示抓取新转发未成功
	                            if($orig_statuses['is_repost']){
	                                $task->taskparams->scene->hangreason = "分析父ID时，抓取用户{$orig_statuses['userid']}的微博{$idormid}的第{$page}页最新转发ID重试{$secget}次仍未抓取到";
	                            }
	                            else{
	                                $task->taskparams->scene->hangreason = "抓取用户{$orig_statuses['userid']}的原创{$idormid}的第{$page}页最新转发时，重试{$secget}次仍未抓取到";
	                            }
	                        }
	                        else{
	                            $task->taskparams->scene->hangstate = 2;//表示抓旧转发未成功
	                            if($orig_statuses['is_repost']){
	                                $task->taskparams->scene->hangreason = "分析父ID时，抓取用户{$orig_statuses['userid']}的微博{$idormid}的第{$page}页旧转发ID重试{$secget}次仍未抓取到";
	                            }
	                            else{
	                                $task->taskparams->scene->hangreason = "抓取用户{$orig_statuses['userid']}的原创{$idormid}的第{$page}页旧转发时，重试{$secget}次仍未抓取到";
	                            }
	                        }
	                        $task->taskparams->scene->hanglasttime = $_lasttime;//挂起前，抓取的最后一条微博时间
	                        $task->taskparams->scene->hanglastid = $_lastid;
	                        $task->taskparams->scent->hangparentid = $idormid;//处理哪个父微博时挂起
	                        $task->taskparams->scene->hangrepost = $orig_statuses['is_repost'];//是否在处理转发时挂起
	                        $logger->warn(SELF." 任务挂起：{$task->taskparams->scene->hangreason}");
	                        break 2;
	                    //}
                    }
                }
                else{
                    $logger->info("微博{$idormid}的转发抓取完毕");
                }
                break 2;
            }
            $secget = 0;
			
            if($orig_statuses['is_repost'] == 0){
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
                	if($pagecontinue){//抓取微博时，中断后继续，未抓取到微博并自增page的情况下，又抓取到数据后创建断档
                		//$logger->debug("pagecontinue true-- lasttime:{$_lasttime}, hanglasttime:{$task->taskparams->scene->hanglasttime}, lastid:{$_lastid}, hanglastid:{$task->taskparams->scene->hanglastid}");
						if($_lasttime <= $task->taskparams->scene->hanglasttime && $_lastid != $task->taskparams->scene->hanglastid){
							if(isset($weibos_info[0]['created_at'])){
								if(!empty($_lastid)){
									$rinterrupt = setRepostInterrupt($task->taskparams->scene->hanglastid, $orig_statuses['sourceid'],0,strtotime($weibos_info[0]['created_at']));
									if($rinterrupt === false){
										$logger->error("抓取旧微博，新增断档失败");
										return false;
									}
								}
							}
							$hangaction = 0;
						}
						$pagecontinue = false;
					}
                    $_spcount += $_c;
                    //抓取原创的转发，插入solr
                    $logger->debug("*****1 before insert_status memory:".memory_get_usage());
                    //$solr_r = insert_status2($weibos_info, 'repost_timeline', $task->taskparams->source);
					$solr_r = addweibo($task->taskparams->source, $weibos_info,0,'repost_timeline',true);
                    $logger->debug("*****1 after insert_status memory:".memory_get_usage());
                    updatetaskparams();
                    if($solr_r['result'] !== true){
                        $logger->error(__FUNCTION__." - solr未全部处理成功，退出");
                        $result = false;
                        break;
                    }
                    unset($solr_r);
                }
            }
            else{
                if(!empty($_lastid)){
                    for($i = 0; $i < $weibo_count; $i++){
                        //找到相同的ID，说明该条记录之前的微博都是跟上次重复的
                        if($weibos_info[$i]['id'] == $_lastid){
                            array_splice($weibos_info,0,$i+1);
                            break;
                        }
                    }
                }
                $_c = $weibo_count;//count($weibos_info); 
                if($_c > 0){
                    $_lastid = $weibos_info[$_c-1];
	                if(!empty($_lastid)){
						$_lasttime = getCreatedat($_lastid, $orig_statuses['sourceid']);
					}
                	if($pagecontinue){
						if($_lasttime <= $task->taskparams->scene->hanglasttime && $_lastid != $task->taskparams->scene->hanglastid){
							$rlt = getCreatedat($weibos_info[0], $orig_statuses['sourceid']);
							if(!empty($_lastid)){
								$rinterrupt = setRepostInterrupt($task->taskparams->scene->hanglastid, $orig_statuses['sourceid'],1,$rlt);
								if($rinterrupt === false){
									$logger->error("抓取二级转发，新增断档失败");
									return false;
								}
							}
							$hangaction = 0;
						}
						$pagecontinue = false;
					}
					
                    $_spcount += $_c;
                    //抓取转发的转发时，只抓取了id
                    if(empty($orig_statuses['total_reposts_count'])){
                        $orig_statuses['total_reposts_count'] = $orig_statuses['reposts_count'];
                    } 
                     
                    //更新总转发数、总到达数
                    $repost_ids = "'".implode("','", $weibos_info)."'";
                    //更新父ID
                    $childdepth = $orig_statuses['repost_trend_cursor'] + 1;//转发深度
                    $start_time = microtime_float();
                    $father_guid = $orig_statuses['guid'];
                    if($orig_statuses['exists_count'] > 0){
                        //数据库中已存在孩子，更新孩子的转发层级
                        $sql_ec = "update ".DATABASE_WEIBO." set repost_trend_cursor={$childdepth}"; 
                        $sql_ec .= " where father_guid='{$father_guid}' and repost_trend_cursor != {$childdepth}"; 
                        $sql_ec .= " and sourceid = {$task->taskparams->source}";
                        $qrec = $dsql->ExecQuery($sql_ec);
                        if(!$qrec){
                            $logger->error(SELF.' sql:'.$sql_ec.' error:'.$dsql->GetError());
                            $result = false;
                            break;
                        }
                    }
                    $sqlupfath = "update ".DATABASE_WEIBO." set father_guid = '{$father_guid}',repost_trend_cursor = {$childdepth}  where id in ({$repost_ids}) and sourceid = {$task->taskparams->source}";
                    $qrupfath = $dsql->ExecQuery($sqlupfath);
                    $end_time = microtime_float();
                    $upfathtimediff = $end_time - $start_time;
                    $task->taskparams->scene->update_fatherid_time += $upfathtimediff;
                    $logger->debug(SELF." updata fatherid time:{$upfathtimediff}");
                    if(!$qrupfath){
                        $logger->error(SELF.' sql:'.$sqlupfath.' error:'.$dsql->GetError());
                        $result = false;
                        break;
                    }
                    $start_time = microtime_float();
                    for($i=0;$i<$weibo_count;$i++){
                        if(!empty($sel_reposts[$weibos_info[$i]])){
                            $sel_reposts[$weibos_info[$i]]['repost_trend_cursor'] = $childdepth;                   
                        }
                    }
                    $end_time = microtime_float();
                    $update_memory_time_diff = $end_time - $start_time;
                    $task->taskparams->scene->update_memory_count += $weibo_count;
                    $task->taskparams->scene->update_memory_time += $update_memory_time_diff;
                    $dsql->FreeResult($qrupfath); 
                }
            }
            //$logger->debug("开始处理 断档信息:".count($weibos_info));
            if(count($weibos_info) > 0){
                if($page == 1){
                    if($orig_statuses['is_repost'] == 0){
                        $newid = $weibos_info[0]['id'];//记录本次抓取的最新微博ID
                        $newtime = strtotime($weibos_info[0]['created_at']);
                    }
                    else{
                        $newid = $weibos_info[0];
                    }
                }
				

              //有断档，处理断档
              //$logger->debug("lasttime:{$_lasttime},interrupt:".var_export($_interrupt,true));
                if(!empty($_interrupt) && !empty($_lasttime)){
                        //当前断档已被覆盖
                        if($_lasttime < $_interrupt['righttime']){
                        	//$logger->debug("当前断档被覆盖:righttime:{$_interrupt['righttime']}, lasttime:{$_lasttime}");
                            $_interrupt = NULL;
                        }
                        else{
                        	//普通断档，当最后抓取的微博时间，处于断档之间时，修改断档
                        	if($_interrupt['type'] == 'normal' && $_lasttime < $_interrupt['created_at']
                        	    && $_lasttime > $_interrupt['righttime']){
                        		//$logger->debug("修改普通断档");
								$complete_interrupts[] = array("id"=>$_interrupt['id'],"righttime"=>NULL,'type'=>$_interrupt['type'], "created_at"=>$_interrupt['created_at']);
								$_interrupt['id'] = $_lastid;
								$_interrupt['created_at'] = $_lasttime;
                        	}
                        	else if($_interrupt['type'] == 'newest'){
                        		//$logger->debug("修改newest断档");
                        		$_interrupt['id'] = $_lastid;
                        		$_interrupt['type'] = 'normal';
                        		$_interrupt['created_at'] = $_lasttime;
                        	}
                        }
                        //依次处理每个断档 
                        foreach($interrupts as $key=>$value){
                            if($_lasttime < $value['righttime']){
                                //$logger->debug("本次抓取最小时间{$_lasttime},消除断档{$value['righttime']}");
                                $complete_interrupts[] = $value;
                                array_shift($interrupts);
                            }
                        }
                }
                
                //判断，尚未处理旧微博，并且没有断档了。
                if(empty($getold) && empty($interrupts)){
                    //判断微博是否全部抓取完毕，否则继续抓取旧微博
                    if($page*$each_count < $orig_statuses['reposts_count']){
		                    $exist_count = getExistsCount($orig_statuses);
		                    if($exist_count < $orig_statuses['reposts_count']){
		                        //计算页码,抓取旧微博
		                        $_page = calcPage($exist_count, $each_count);
		                        $page = $_page < $page ? $page + 1 : $_page;
		                        if($page*$each_count < $orig_statuses['reposts_count']){
				                        $getold = true;
				                        $logger->info("断档处理完毕，继续抓取第{$page}页的旧微博");
				                        continue;
				                    }
				                    else{
				                    		break 2;	
				                    }
		                    }
		                    else{
		                        $logger->info("断档处理完毕，微博{$idormid}的转发全部抓取完毕");
		                        break 2;
		                    }
		                }
                }
                //抓取的总数大于等于转发数
                if($page*$each_count >= $orig_statuses['reposts_count']){
                	  break 2;
                }

                //尚未抓取旧微博，并且当前断档已消除
                if(!$getold && $_interrupt == NULL){
                    break;//执行下一个断档
                }
            }
            $page ++;
        }
    }while(count($interrupts) > 0);

    //找到最新的断档，如果没有，说明被消除
    if(!empty($interrupts)){
        foreach($interrupts as $key => $value){
            if($value['type'] == 'newest'){
                $newest_interrupt = $value;
                break;
            }
        }
    }
    if($orig_statuses['is_repost'] && !empty($newid)){
        $newtime = getNewtime($orig_statuses);
    }
    
    //设置最新的断档时间
    if(!empty($newtime) && (empty($newest_interrupt) || $newest_interrupt['righttime'] < $newtime)){
        $rnewtime = setRepostNewtime($idormid,$orig_statuses['sourceid'],$newtime,$ismid);
        if($rnewtime === false){
        	  $logger->error("设置最新断档时间失败");
            $result = false;
        }
    }
    //最左侧断档没有消除，说明又产生了新的断档，老的的newtime变为旧断档
    /*if(!empty($newest_interrupt) && !empty($_lastid)){
        $rinterrupt = setRepostInterrupt($_lastid, $orig_statuses['sourceid'],$orig_statuses['is_repost'],$newest_interrupt['righttime']);
        if($rinterrupt === false){
        	$logger->error("新增断档时失败");
            $result = false;
        }
    }*/
    if(!empty($interrupts)){
        foreach($interrupts as $key => $value){
        	if(empty($v['id'])){
                continue;
            }
        	$rinterrupt = setRepostInterrupt($value['id'],$orig_statuses['sourceid'],$orig_statuses['is_repost'],$value['righttime']);
            if($rinterrupt === false){
            	$logger->error("更新断档时失败");
                $result = false;
                break;
            }
        }
    }
    if(!empty($complete_interrupts)){
        foreach($complete_interrupts as $k=>$v){
            if(empty($v['id'])){
                continue;
            }
            $rinterrupt = setRepostInterrupt($v['id'],$orig_statuses['sourceid'],$orig_statuses['is_repost'],NULL);
            if($rinterrupt === false){
            	$logger->error("消除断档时失败");
                $result = false;
                break;
            }
        }        
    }
    //$r = update_repost_info($orig_statuses);
    //$logger->debug("exit get_repost");
    return $result;
}

/**
 * 
 * 获取某个微博的孩子中的最新时间
 * @param $father
 */
function getNewtime($father){
    global $dsql,$logger;
	if(empty($father['guid']) || (empty($father['id']) && empty($father['mid']))){
    	return false;
    }
    if($father['is_repost']){
    	$whfield = "father_guid='{$father['guid']}'";
    }
    else{
    	$whfield = !empty($father['id']) ? "retweeted_status='{$father['id']}'" : "retweeted_mid='{$father['mid']}'";
    }
    $sql = "select created_at from ".DATABASE_WEIBO." where sourceid={$father['sourceid']} 
        and {$whfield} order by created_at desc limit 0,1";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql:{$sql} has error:".$dsql->GetError());
        return false;
    }
    else{
        $rs = $dsql->GetArray($qr);
        if(!empty($rs)){
            return $rs['created_at'];            
        }
        else{
            return NULL;
        }
    }
}

function getCreatedat($id,$sourceid){
    global $dsql,$logger,$sel_reposts;
    $result = false;
    if(!empty($sel_reposts) && !empty($sel_reposts[$id])){
        return $sel_reposts[$id]['created_at'];
    }
    $sql = "select created_at from ".DATABASE_WEIBO." where id='{$id}' and sourceid={$sourceid}";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql:{$sql} has error:".$dsql->GetError());
        return false;
    }
    else{
        $rs = $dsql->GetArray($qr);
        if(!empty($rs)){
            return $rs['created_at'];            
        }
    }
    return  false;
}



/**
 * 设置转发微博的直接父亲
 * @param $orig 原创
 */
function set_repost_directfather($orig){
    global $logger, $dsql, $task, $sel_reposts, $curr_repostindex;
    $logger->debug("enter set_repost_directfather");
    $wh = "";
    if(!empty($orig['id'])){
    	$wh = "retweeted_status = '{$orig['id']}'";
    }
    else if(!empty($orig['mid'])){
    	$wh = "retweeted_mid = '{$orig['mid']}'";
    }
    else{
    	$logger->error(__FUNCTION__." id and mid is empty");
    	return false;
    }
    if(empty($task->taskparams->scene->all_secrepost_count) || empty($task->taskparams->select_repost_cursor)){
        $selallcountsql = "select count(0) as cnt from weibo_new where {$wh} and sourceid = {$task->taskparams->source} and reposts_count > 0";
        $qrselallcount = $dsql->ExecQuery($selallcountsql);
        if(!$qrselallcount){
            $logger->error(__FUNCTION__." sql:{$selallcountsql} error:".$dsql->GetError());
        }
        else{
            $rsselallcount = $dsql->GetArray($qrselallcount);
            if($rsselallcount){
                $task->taskparams->scene->all_secrepost_count += $rsselallcount['cnt'];//所有转发数大于0的转发微博个数
            }
        }
    }
    $eachcount = 10000; 
    $limitcursor = 0;
    if(isset($task->taskparams->select_repost_cursor)){//处理到第几条转发
        $limitcursor = $task->taskparams->select_repost_cursor;
    }
    else{
        $task->taskparams->select_repost_cursor = 0;
    }
    $result = true;
    $each_count = isset($task->taskparams->each_count) ? $task->taskparams->each_count : 200;
    while(1){
        //按时间正序取，先处理早期的微博
        $sql = "select userid,id,mid,guid, reposts_count, total_reposts_count,repost_trend_cursor,reach_count,total_reach_count,
            is_repost, repost_maxid,repost_sinceid,father_guid,sourceid,created_at
            from ".DATABASE_WEIBO." where {$wh} and reposts_count > 0 
            and sourceid = {$task->taskparams->source} order by created_at limit {$limitcursor},{$eachcount}";
        $stime = microtime_float();
        $qr = $dsql->ExecQuery($sql);
        $etime = microtime_float();
        $sel_reposttime = $etime - $stime;
        $task->taskparams->scene->select_repost_sqlcount ++;//查询数据库次数
        $task->taskparams->scene->select_repost_time += $sel_reposttime;//查询转发时间
        $logger->debug(__FUNCTION__." 从第{$limitcursor}条开始查询{$eachcount}条转发，花费{$sel_reposttime}");
        if(!$qr){
            $logger->error(__FUNCTION__." - 获取转发异常sql:{$sql} - ".$dsql->GetError());
            $result = false;
            break;
        }
        else{
            $r_count = $dsql->GetTotalRow($qr);
            $task->taskparams->scene->select_repost_count += $r_count;//已读出的转发
            if($r_count == 0){
                $logger->debug(__FUNCTION__." - 未获取到{$wh}的转发：limit {$limitcursor},{$eachcount}");
                break;
            }
            //unset($sel_reposts);//全局变量 $sel_reposts
            $sel_reposts = array();
            while($repost_weibo = $dsql->GetArray($qr)){
                $sqlchk = "select count(0) as cnt from ".DATABASE_WEIBO." where father_guid = '{$repost_weibo['guid']}'";
                $stime = microtime_float();
                $qrchk = $dsql->ExecQuery($sqlchk);
                $etime = microtime_float();
                $task->taskparams->scene->check_repost_count++;
                $task->taskparams->scene->check_repost_time += $etime - $stime;
                if($qrchk){
                    $rschk = $dsql->GetArray($qrchk);
                    if($rschk){
                        if($repost_weibo['reposts_count'] <= $rschk['cnt']){
                            $dsql->FreeResult($qrchk);
                            continue;
                        }
                    }
                    $dsql->FreeResult($qrchk);
                }
                if(!empty($rschk) && !empty($rschk['cnt'])){
                    $repost_weibo['exists_count'] = $rschk['cnt'];
                }
                else{
                    $repost_weibo['exists_count'] = 0;
                }
                //获取转发的转发id
                if(!isset($repost_weibo['repost_trend_cursor'])){
                    $repost_weibo['repost_trend_cursor'] = 1;//转发所处层级，默认设置1，获取其转发时，加上father的层级
                }
                $sel_reposts[$repost_weibo['id'].''] = $repost_weibo;
            }
            foreach($sel_reposts as $key=>$value){
                $r = get_repost($value, $each_count, true);
                if($r === false){
                    $logger->warn(__FUNCTION__." - 抓取转发微博的孩子ID出错");
                    $result = false;
                    break 2;
                }
                $task->taskparams->select_repost_cursor++;//在数据查询原创的转发时使用的 limit
                $task->taskparams->complete_repost_count++;//已处理的转发微博个数
                updatetaskparams();
            }
            /*
            for($i=0; $i<count($sel_reposts); $i++){
                $curr_repostindex = $i;//全局变量$curr_repostindex 记录当前处理到第几条
                $r = get_repost($sel_reposts[$i], $each_count);
                if($r === false){
                    $logger->warn(__FUNCTION__." - 抓取转发微博的孩子ID出错");
                    $result = false;
                    break 2;
                }
                $task->taskparams->select_repost_cursor++;//在数据查询原创的转发时使用的 limit
                $task->taskparams->complete_repost_count++;//已处理的转发微博个数
                updatetaskparams();
            }*/
            if($r_count < $eachcount){
                break;
            }
            $limitcursor += $eachcount;
        }
        $dsql->FreeResult($qr);
    }
    $logger->debug("exit set_repost_directfather");
    return $result;
}


/*
 * 执行任务
 */
function execute()
{
    global $logger, $task,$dsql,$apicount,$apierrorcount,$insertweibotime,$analysistime,$funtime,
        $spidercount,$newcount,$solrerrorcount, $spiderusercount,$insertusertime,$apitime,$api_counts_time,
        $api_counts_count,$solr_update_time,$solr_update_count,$sql_updatecounts_time;
    $task->taskparams->scene->status_desp = ''; 
    $r = true;
    $seedcount = 0;
    $logger->debug("enter execute");
    /*
     * 从数据库中的任务表，得到任务参数，其中包括查询的起始点
     * 和一次查询条数
     * 查询后，需要更新任务表的查询位置
     */
    //每次抓取多少条
    $each_count = isset($task->taskparams->each_count) ? $task->taskparams->each_count : 200;
    if(!isset($task->taskparams->scene->update_taskids_time)){
    	$task->taskparams->scene->update_taskids_time = 0;
    }
    if(!isset($task->taskparams->scene->api_queryid_count)){
    	$task->taskparams->scene->api_queryid_count = 0;
    }
    if(!isset($task->taskparams->scene->apierrorcount)){
    	$task->taskparams->scene->apierrorcount = 0;
    }
    if(!isset($task->taskparams->scene->deleted_weibocount)){
    	$task->taskparams->scene->deleted_weibocount = 0;
    }
    if(!isset($task->taskparams->scene->calc_solrerrorcount)){
    	$task->taskparams->scene->calc_solrerrorcount = 0;
    }
    if(!isset($task->taskparams->scene->shutdowncount)){
    	$task->taskparams->scene->shutdowncount = 0;
    }
    if(!isset($task->taskparams->scene->manual_shutdowncount)){
    	$task->taskparams->scene->manual_shutdowncount = 0;
    }
    if(!isset($task->taskparams->scene->get_origrepost_time)){
    	$task->taskparams->scene->get_origrepost_time = 0;
    }
    if(!isset($task->taskparams->scene->set_repost_father_time)){
    	$task->taskparams->scene->set_repost_father_time = 0;
    }
    if(!isset($task->taskparams->scene->set_total_counts_time)){
    	$task->taskparams->scene->set_total_counts_time = 0;
    }
    if(!isset($task->taskparams->scene->repost_timeline_time)){
    	$task->taskparams->scene->repost_timeline_time = 0;
    }
    if(!isset($task->taskparams->scene->repost_timeline_count)){
    	$task->taskparams->scene->repost_timeline_count = 0;
    }
    if(!isset($task->taskparams->scene->repost_timeline_ids_time)){
    	$task->taskparams->scene->repost_timeline_ids_time = 0;
    }
    if(!isset($task->taskparams->scene->repost_timeline_ids_count)){
    	$task->taskparams->scene->repost_timeline_ids_count = 0;
    }
    if(!isset($task->taskparams->scene->statuses_count_count)){
    	$task->taskparams->scene->statuses_count_count = 0;
    }
    if(!isset($task->taskparams->scene->spider_statuscount)){
    	$task->taskparams->scene->spider_statuscount = 0;
    }
    if(!isset($task->taskparams->scene->insertsql_statuscount)){
    	$task->taskparams->scene->insertsql_statuscount = 0;
    }
    if(!isset($task->taskparams->scene->solrerrorcount)){
    	$task->taskparams->scene->solrerrorcount = 0;
    }
    if(!isset($task->taskparams->scene->spider_usercount)){
    	$task->taskparams->scene->spider_usercount = 0;
    }
    if(!isset($task->taskparams->scene->analysistime)){
    	$task->taskparams->scene->analysistime = 0;
    }
    if(!isset($task->taskparams->scene->storetime)){
    	$task->taskparams->scene->storetime = 0;
    }
    if(!isset($task->taskparams->scene->insertsql_statustime)){
    	$task->taskparams->scene->insertsql_statustime = 0;
    }
    if(!isset($task->taskparams->scene->insertsql_usertime)){
    	$task->taskparams->scene->insertsql_usertime = 0;
    }
    if(!isset($task->taskparams->scene->select_repost_sqlcount)){
    	$task->taskparams->scene->select_repost_sqlcount = 0;
    }
    if(!isset($task->taskparams->scene->select_repost_time)){
    	$task->taskparams->scene->select_repost_time = 0;
    }
    if(!isset($task->taskparams->scene->update_fatherid_time)){
    	$task->taskparams->scene->update_fatherid_time = 0;
    }
    if(!isset($task->taskparams->scene->calc_select_repost_count)){
    	$task->taskparams->scene->calc_select_repost_count = 0;
    }
    if(!isset($task->taskparams->scene->calc_select_repost_time)){
    	$task->taskparams->scene->calc_select_repost_time = 0;
    }
    if(!isset($task->taskparams->scene->calc_counts_time)){
    	$task->taskparams->scene->calc_counts_time = 0;
    }
    if(!isset($task->taskparams->scene->calc_updatecounts_time)){
    	$task->taskparams->scene->calc_updatecounts_time = 0;
    }
    if(!isset($task->taskparams->scene->calc_updatesolr_time)){
    	$task->taskparams->scene->calc_updatesolr_time = 0;
    }
    if(!isset($task->taskparams->complete_repost_count)){
    	$task->taskparams->complete_repost_count = 0;
    }
    if(!isset($task->taskparams->select_repost_cursor)){
    	$task->taskparams->select_repost_cursor = 0;
    }
    $remoteseed = array();
    while (1)
    {
        $s_time = microtime_float();
        //$as_time = s_time;
        $seedweibo = getRepostSeedWeibo($task);//获取种子微博
        $e_time = microtime_float();
        $seedtimediff = $e_time - $s_time;
        $task->taskparams->scene->get_seedweibo_time += $seedtimediff;
        if(!empty($seedweibo)){
        	if($seedweibo['reposts_count'] > 1900){
        		$remoteseed[] = $seedweibo['id'];
        		$task->taskparams->select_cursor++;
        		$task->taskparams->select_repost_cursor = 0;//获取下一个种子微博前，将已处理的转发位置置零
        		$task->taskparams->scene->hangrepost = 0;//恢复默认状态
        		updatetaskparams();
        		continue;
        	}
        	if(!empty($seedweibo['id2url'])){//将ID 的增加URL
        		$task->taskparams->currorigurl = $seedweibo['id']."({$seedweibo['weibourl']})";
        	}
        	else{
        		$task->taskparams->currorigurl = $seedweibo['weibourl'];
        	}
            //hangrepost为空或0时，进入抓取原创的步骤，否则表示上次任务中断时，原创已处理完毕，本次不需要再处理原创
            if(empty($task->taskparams->scene->hangrepost)){
                //获取到种子
                $task->taskparams->scene->repost_trend_status = 2;//正在抓取原创的转发
                updatetaskparams();
                
                $logger->debug('*** before get_repost memory:'.memory_get_usage());
                $seedweibo['exists_count'] = getExistsCount($seedweibo);
                if($seedweibo['exists_count'] === false){
                	  $logger->error("getExistsCount return false");
                	  $r = false;
                	  break;
                }

                $s_time = microtime_float();
                $r = get_repost($seedweibo,$each_count,$task->taskparams->forceupdate);
                $e_time = microtime_float();
                $getorigreposttime = $e_time - $s_time;
                $task->taskparams->scene->get_origrepost_time += $getorigreposttime;//抓取转发花费时间
                $logger->debug('*** after get_repost memory:'.memory_get_usage());
                if($r === false){
                    $logger->error(SELF." - get_repost返回false");
                    break;
                }
                else if($r === 0){
                	$r = true;
                	$task->taskparams->select_cursor++;
                	$task->taskparams->select_repost_cursor = 0;//获取下一个种子微博前，将已处理的转发位置置零
                	$task->taskparams->scene->hangrepost = 0;//恢复默认状态
                	updatetaskparams();
                	$logger->info(SELF." - 跳过种子微博:".(!empty($seedweibo['id2url']) ?  $seedweibo['id'] : $seedweibo['weibourl']));
                	continue;
                }
                $logger->info(SELF." - 种子微博:".(!empty($seedweibo['id2url']) ?  $seedweibo['id'] : $seedweibo['weibourl'])."的转发抓取完毕,花费{$getorigreposttime}");
            }
            $task->taskparams->scene->repost_trend_status = 3;//正在处理原创的转发
            updatetaskparams();
            $s_time = microtime_float();
            $r = set_repost_directfather($seedweibo);
            $e_time = microtime_float();
            $repost_timediff = $e_time - $s_time;
            $task->taskparams->scene->set_repost_father_time += $repost_timediff;//处理原创的转发花费时间
            if($r == false){
                $logger->error(SELF." - set_repost_directfather返回false");
                break;
            }
            $logger->info("所有转发处理完毕，花费{$repost_timediff}");
            $logger->info("开始计算总转发数、总到达数，并更新到solr");
            $task->taskparams->scene->repost_trend_status = 4;//正在计算总转发
            updatetaskparams();
            $s_time = microtime_float();
            $r = setTotalCounts($task, $seedweibo);
            $e_time = microtime_float();
            $setcountstimediff = $e_time - $s_time;
            $task->taskparams->scene->set_total_counts_time += $setcountstimediff;
            if($r == false){
                $logger->error(SELF." - setTotalCounts返回false");
                break;
            }
            $logger->info("计算总转发数、总到达数完毕，花费{$setcountstimediff}");
            //$ae_time = $e_time;
            //$all_time = $ae_time - $as_time;
            //$task->taskparams->scene->all_time += $all_time;
            $logger->info(SELF." - 种子微博:".(!empty($seedweibo['id2url']) ?  $seedweibo['id'] : $seedweibo['weibourl'])."转发数：{$seedweibo['reposts_count']},处理完毕");
            if(!empty($seedweibo['id2url'])){//将ID 的增加URL
            	$task->taskparams->oristatus[$task->taskparams->select_cursor] = $task->taskparams->oristatus[$task->taskparams->select_cursor]."({$seedweibo['weibourl']})";
            }
            $task->taskparams->select_cursor++;
            $task->taskparams->select_repost_cursor = 0;//获取下一个种子微博前，将已处理的转发位置置零
            $task->taskparams->scene->hangrepost = 0;//恢复默认状态
            updatetaskparams();
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
        $rs = addRepostTask($task->tasksource, $remoteseed, false, $task->conflictdelay, 0, 1, $task->taskparams, $task->remarks);
        if(!$rs['result']){
            $logger->error(SELF." - 添加远程任务失败");
            $r = false;
        }
    }
    //$task->datastatus += $task->taskparams->scene->spider_statuscount;//($newcount - $solrerrorcount);
    //$funtime += $solr_update_time +$sql_updatecounts_time;
    $alltime = $task->taskparams->scene->get_seedweibo_time + $task->taskparams->scene->get_origrepost_time + $task->taskparams->scene->set_repost_father_time + $task->taskparams->scene->update_taskids_time + $task->taskparams->scene->set_total_counts_time;
    $apicount = $task->taskparams->scene->repost_timeline_count + $task->taskparams->scene->repost_timeline_ids_count + $task->taskparams->scene->statuses_count_count;
    //$allapitime = $task->taskparams->scene->repost_timeline_time + $task->taskparams->scene->repost_timeline_ids_time;
    $logger->info(SELF." - 统计条数：总访问API次数".$apicount."，包括：repost_timeline:{$task->taskparams->scene->repost_timeline_count}, repost_timeline_ids:{$task->taskparams->scene->repost_timeline_ids_count}，statuses_count:{$task->taskparams->scene->statuses_count_count}，queryid:{$task->taskparams->scene->api_queryid_count}，
                共出错{$task->taskparams->scene->apierrorcount}次。 总抓取{$task->taskparams->scene->spider_statuscount}条，被删除{$task->taskparams->scene->deleted_weibocount}条，入库{$task->taskparams->scene->insertsql_statuscount}条, 新增到solr失败{$task->taskparams->scene->solrerrorcount}条,总新增用户{$task->taskparams->scene->spider_usercount}个，更新solr总转发、总到达出错{$task->taskparams->scene->calc_solrerrorcount}次");
    $logger->info("获取种子微博总花费{$task->taskparams->scene->get_seedweibo_time}");
    $logger->info("抓取原创的转发总花费{$task->taskparams->scene->get_origrepost_time}。包括：访问API {$task->taskparams->scene->repost_timeline_count} 次花费{$task->taskparams->scene->repost_timeline_time}，分词花费{$task->taskparams->scene->analysistime}，存储到solr花费{$task->taskparams->scene->storetime}，插入数据库花费{$task->taskparams->scene->insertsql_statustime}，插入用户花费{$task->taskparams->scene->insertsql_usertime}");
    $logger->info("分析父ID总花费{$task->taskparams->scene->set_repost_father_time}。包括：访问API {$task->taskparams->scene->repost_timeline_ids_count} 次花费{$task->taskparams->scene->repost_timeline_ids_time}，查询转发{$task->taskparams->scene->select_repost_sqlcount}次花费{$task->taskparams->scene->select_repost_time}，更新fatherid、转发层级花费{$task->taskparams->scene->update_fatherid_time}");
    $logger->info("更新抓取进度状态花费{$task->taskparams->scene->update_taskids_time}");
    $logger->info("计算总转发数、总到达数并更新到solr总花费{$task->taskparams->scene->set_total_counts_time}。包括：查询转发{$task->taskparams->scene->calc_select_repost_count}次花费{$task->taskparams->scene->calc_select_repost_time}，计算转发数花费{$task->taskparams->scene->calc_counts_time}，更新数据库花费{$task->taskparams->scene->calc_updatecounts_time}，更新到solr{$task->taskparams->scene->calc_select_repost_count}次花费{$task->taskparams->scene->calc_updatesolr_time}");
    //$logger->info(SELF." - 统计时间：总访问API时间".$allapitime.", 包括repost_timeline:{$apitime}, statuses_count:{$api_counts_time} ,总处理时间{$funtime}:(插入微博时间{$insertweibotime},插入用户时间{$insertusertime},分析时间{$analysistime},更新solr转发数时间{$solr_update_time}, 更新数据库转发数时间：{$sql_updatecounts_time})");
    $logger->info(SELF." - 总计：总处理原创{$seedcount}条，总处理转发{$task->taskparams->complete_repost_count}条，当前正在处理的转发位置{$task->taskparams->select_repost_cursor}，总花费{$alltime}。共停止{$task->taskparams->scene->shutdowncount}次，其中人工停止{$task->taskparams->scene->manual_shutdowncount}， 等待时间{$task->taskparams->scene->waittime}");
    $logger->debug('exit execute');
    return $r;
}

function updatetaskparams(){
    global $task,$logger,$dsql;
    $upp = json_encode($task->taskparams);
    $datastatus = empty($task->taskparams->scene->spider_statuscount) ? 0 : $task->taskparams->scene->spider_statuscount;
    $task->datastatus = $datastatus; 
    $uptsql = "update task set taskparams = '{$upp}',datastatus={$datastatus} where id = {$task->id}";
    $qr = $dsql->ExecNoneQuery($uptsql);
    if(!$qr){
        $logger->warn(SELF." - execute 更新taskparams出错：sql:{$uptsql} error:".$dsql->GetError());
    }
    $dsql->FreeResult($qr);
}
