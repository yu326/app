<?php
define("SELF", basename(__FILE__));

if ($argc > 1) {
	$allParam = $_SERVER['argv'];
	//$_SERVER['hostName'] = $allParam[1];
	//设置全局变量 在config.php中统一
	$GLOBALS['hostName'] = $allParam[1];
}


include_once('includes.php');
include_once('taskcontroller.php');
include_once('jobfun.php');
define('_NOSESSION_', 1);
require_once('dataProcessClass.php');
//ini_set('include_path',get_include_path().'/lib');

initLogger(LOGNAME_UPDATESNAPSHOT);

try{
	while(true){
		connectDB(DATABASE_WEIBOINFO);
		//定时快照和事件预警为两个任务,但不同时存在,定时快照添加预警后修改为,事件预警任务号
		//查找任务时需要查询定时快照和事件预警任务
		$task = getLocalTask(TASKTYPE_UPDATE, TASK_SNAPSHOT);
		if(empty($task)){
			$task = getLocalTask(TASKTYPE_UPDATE, TASK_EVENTALERT);
			if(empty($task)){
				$logger->debug(SELF." - 未找到待启动任务,退出");
				exit;
			}
		}
		startTask($task);
		$logger->info(SELF." - 任务{$task->id}启动");
	    $rt = detectConflictTask($task);
	    if(!$rt['result']){
	        stopTask($task);
	        $logger->error(SELF."- 任务{$task->id}冲突检测失败 -".$rt['msg']);
	        continue;
	    }
	    else if(!$rt['continue']){
	        $logger->info(SELF." - 任务{$task->id}冲突，延迟启动");
	        continue;
	    }
		$r = execute();
		connectDB(DATABASE_WEIBOINFO);
		if($r){
			completeTask($task);
			$logger->info(SELF." - 任务{$task->id}完成");
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
	global $logger, $task;
	$logger->info(__FUNCTION__.__FILE__.__LINE__."任务开始执行了");
	if(!isset($task->taskparams->scene)){
		$task->taskparams->scene = (object)array();
	}
	$start_t = microtime_float();
	connectDB(DATABASE_NAME);
	$inc = getElementsByInstance($task->taskparams->instanceid);
	if(empty($inc)){
		$logger->error(SELF." - 不存在的instanceid {$task->taskparams->instanceid}");
		return false;
	}
	$user = fakeUser($inc['tenantid']);
	if(empty($user)){
		$logger->error(SELF." - 创建用户失败");
		return false;
	}
	$GLOBALS['effectuser'] = $user;
	$task->taskparams->scene->userid = $user->getuserid();
	$reqs = fakeRequest($inc);
	if(empty($reqs)){
		$logger->error(SELF." - 创建请求失败");
		return false;
	}
	$task->taskparams->scene->reqstat = array();
	$histsaved = false;
	foreach($reqs as $req){
		switch($inc['instancetype']){
		case 1:
			$processer = createDataProcesser(PROCESSER_TYPE_ELEMENT);
			break;
		case 2:
			$processer = createDataProcesser(PROCESSER_TYPE_LINKAGE);
			break;
		case 3:
			$processer = createDataProcesser(PROCESSER_TYPE_OVERLAY);
			break;
		default:
			$logger->error(SELF." - 不支持的instancetype {$inc['instancetype']}");
			return false;
		}
		$processer->setLogger($logger);
		$r = $processer->parseParams($req);//解析参数
		if(!$r){
			$logger->error(SELF." - 解析参数失败:".$processer->getError());
			return false;
		}
		else{
			$dpr = $processer->getDataParams();
			if(!$dpr){
				$logger->error(SELF." - 获取模型失败:".$processer->getError());
				return false;
			}
			else{
				$rqstart_t = microtime_float();
				$out = $processer->getData($dpr);
				$rqend_t = microtime_float();
				if($out === false){
					$logger->error(SELF." - 获取数据失败:".$processer->getError());
					return false;
				}
			}
		}
        //$logger->debug(__FUNCTION__.__FILE__.__LINE__." update_snapshot out :".var_export($out, true));
		$fmtout = $processer->formatData($out);
		$fmtout = sortSnapshotField($fmtout, $req["modelid"]);
		if(!$histsaved){
			if(saveSnapshotHistory($req, $fmtout, $task->taskparams->spawntime) == false){
				$logger->error(SELF." - 保存快照历史失败");
				return false;
			}
			$histsaved = true;
		}
		/*
		if(updateSnapshot($req, $fmtout, $task->taskparams->spawntime) == false){
			$logger->error(SELF." - 更新快照失败");
			return false;
		}
		 */
		if(!empty($task->taskparams->eventlist)){
			$schedid = isset($task->taskparams->scheduleid) ? $task->taskparams->scheduleid : 0;
			checkEvents($req['instanceid'], $req['elementid'], $task->taskparams->spawntime, $fmtout, $task->taskparams->eventlist, $schedid);
		}
		unset($processer);
		$stat = (object)array();
		$stat->elementid = $req['elementid'];
		$stat->reqtime = $rqend_t - $rqstart_t;
		$task->taskparams->scene->reqstat[] = $stat;
		unset($stat);
	}
	$end_t = microtime_float();
	$task->taskparams->scene->alltime = $end_t - $start_t;
	
	return true;
}

function getElementsByInstance($instanceid)
{
    global $dsql, $logger;
	if(!isset($instanceid)){
		return false;	
	}
	$inc = array();
    $num = 0;
    $selfield = "a.instanceid,a.modelid,a.elementid,a.content,a.type,a.title,a.updatetime,a.modelparams";
    $selfield .= ",b.instancetype,b.tenantid";
    $sql = "select {$selfield} from ".DATABASE_ELEMENT." as a inner join ".DATABASE_TENANT_TAGINSTANCT." as b on a.instanceid = b.id where a.instanceid=".$instanceid;
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
    }
    else{
        $num = $dsql->GetTotalRow($qr);
        if($num>0){
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC)){
            	if(!isset($inc["instanceid"])){
            		$inc["instanceid"] = $result["instanceid"];
            		$inc["instancetype"] = $result["instancetype"];
            		$inc["tenantid"] = $result["tenantid"];
            	}
				$temp_arr = array();
				$temp_arr["modelid"] = $result["modelid"];
                $temp_arr["elementid"] = $result["elementid"];
				$tmpJson = json_decode($result["content"], true); //elements json
                $temp_arr["updatetime"] = $result["updatetime"];
                $temp_arr["datajson"] = $tmpJson;

				//解决用户分析字段改名,兼容旧版本orderby 名称
				if($tmpJson["modelid"] == 1 && $tmpJson["version"] <= 1031){
					$ob = "";
					switch($temp_arr["datajson"]["output"]["orderby"]){
					case "followers_count":
						$ob =  "users_followers_count";
						break;
					case "friends_count":
						$ob =  "users_friends_count";
						break;
					case "statuses_count":
						$ob =  "users_statuses_count";
						break;
					default:
						break;
					}
					$temp_arr["datajson"]["output"]["orderby"] = $ob;
				}

                //解决存存数据库 jsonencode4db时，将0 变为 null的  bug
                if(!empty($temp_arr['datajson']['filterrelation'])){
                    array_walk_recursive($temp_arr['datajson']['filterrelation'], "null2zero");
                }
                if(!empty($temp_arr['datajson']['filtervalue'])){
                    foreach($temp_arr['datajson']['filtervalue'] as $b_k => $b_v){
                        if(!isset($b_v['fromlimit'])){
                           $temp_arr['datajson']['filtervalue'][$b_k]['fromlimit'] = 0; 
                        }
                        if($b_v['fieldvalue']['datatype'] == 'array'){
                            foreach($b_v['fieldvalue']['value'] as $_b_k => $_b_v){
                                if($_b_v['datatype'] == "int" && $_b_v['value'] === null){
                                    $temp_arr['datajson']['filtervalue'][$b_k]['fieldvalue']['value'][$_b_k]['value'] = 0;
                                }
                            }
                        }
                        else{
                            if($b_v['fieldvalue']['datatype'] == 'int' && $b_v['fieldvalue']['value'] === null){
                                $temp_arr['datajson']['filtervalue'][$b_k]['fieldvalue']['value'] = 0;       
                            }
                        }
                    }
                }
                if(!empty($temp_arr['datajson']['output']) && $temp_arr['datajson']['output']['data_limit'] === null){
                    $temp_arr['datajson']['output']['data_limit'] = 0;
                }
                $temp_arr["type"] = $result["type"];
                $temp_arr["title"] = $result["title"];
				if($inc["instancetype"] == 3){
					$mp = json_decode($result["modelparams"],true);
					if(isset($mp["modelname"])){
						$temp_arr["modelname"] = $mp["modelname"];
					}
					if(isset($mp["referencedata"])){
						$temp_arr["referencedata"] = $mp["referencedata"];
					}
					if(isset($mp["secondaryyaxis"])){
						$temp_arr["secondaryyaxis"] = $mp["secondaryyaxis"];
					}
					if(!empty($tmpJson["showid"])){
						$temp_arr["showid"] = $tmpJson["showid"][0];
					}
					if(!empty($tmpJson["linetype"])){
						$temp_arr["linetype"] = $tmpJson["linetype"];
					}
					if(isset($mp["referencedataratio"])){
						$temp_arr["referencedataratio"] = $mp["referencedataratio"];
					}
					if(isset($mp["xcombined"])){
						$temp_arr["xcombined"] = $mp["xcombined"];
					}
					if(isset($mp["columnstacking"])){
						$temp_arr["columnstacking"] = $mp["columnstacking"];
					}
					if(isset($mp["xzreverse"])){
						$temp_arr["xzreverse"] = $mp["xzreverse"];
					}
					if(isset($mp["subInstanceType"])){
						$temp_arr["subInstanceType"] = $mp["subInstanceType"];
					}
					if(isset($mp["overlayindex"])){
						$temp_arr["overlayindex"] = $mp["overlayindex"];
					}
				}
                $inc["elements"][] = $temp_arr;
            }
        }
    }
	return $inc;
}

function fakeRequest($instance)
{
	if(empty($instance)){
		return false;
	}
	$reqarr = array();
	if($instance['instancetype'] == 1){// element
		$req = array();
		$req['instancetype'] = $instance['instancetype'];
		// fake request parameters
		$req['instanceid'] = $instance['instanceid'];
		$req['elementid'] = $instance['elements'][0]['elementid'];
		$req['modelid'] = $instance['elements'][0]['modelid'];
		$req['returnoriginal'] = ($instance['elements'][0]['datajson']['output']['outputtype'] == 1);
		$req['hasformjson'] = false;
		$req['isdrilldown'] = false;
		$reqarr[] = $req;
	}
	else if($instance['instancetype'] == 2){// linkage
		foreach($instance['elements'] as $element){
			if($element['type'] != 2){
				continue;
			}
			$req = array();
			$req['instancetype'] = $instance['instancetype'];
			// fake request parameters
			$req['instanceid'] = $instance['instanceid'];
			$req['elementid'] = $element['elementid'];
			$req['modelid'] = $element['modelid'];
			$req['returnoriginal'] = ($element['datajson']['output']['outputtype'] == 1);
			$req['hasformjson'] = false;
			$req['isdrilldown'] = false;
			$reqarr[] = $req;
		}
	}
	else if($instance['instancetype'] == 3){// overlay
		$req = array();
		$req['instancetype'] = $instance['instancetype'];
		$tgtidx = 0;
		for($idx = 1; $idx < count($instance['elements']); $idx++){
			if($instance['elements'][$idx]['type'] != 2){
				continue;
			}
			if($instance['elements'][$idx]['overlayindex'] > $instance['elements'][$tgtidx]['overlayindex']){
				$tgtidx = $idx;
			}
		}
		$req['elementid'] = $instance['elements'][$tgtidx]['elementid'];
		$req['modelid'] = $instance['elements'][$tgtidx]['modelid'];
		// fake request parameters
		$req['instanceid'] = $instance['instanceid'];
		$req['returnoriginal'] = ($instance['elements'][0]['datajson']['output']['outputtype'] == 1);
		if(isset($instance['elements'][0]['xcombined'])){
			$req['overlayxcombined'] = $instance['elements'][0]['xcombined'];
		}
		if(isset($instance['elements'][0]['columnstacking'])){
			$req['overlaycolumnstacking'] = $instance['elements'][0]['columnstacking'];
		}
		if(isset($instance['elements'][0]['xzreverse'])){
			$req['overlayxzreverse'] = $instance['elements'][0]['xzreverse'];
		}
		$req['hasformjson'] = false;
		$req['isdrilldown'] = false;
		$reqarr[] = $req;
	}
	else{
		return false;
	}
	return $reqarr;
}

function fakeUser($tenantid)
{
	global $dsql, $logger;
	$sql = "select a.*, b.localtype,b.weburl,b.allowlinkage,b.allowoverlay, b.allowdrilldown,b.allowdownload,b.allowupdatesnapshot,b.alloweventalert, b.allowwidget, 
		b.allowaccessdata, b.accessdatalimit,b.allowvirtualdata from users a inner join tenant b on a.tenantid=b.tenantid
        where b.tenantid={$tenantid}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		return false;
	}
	else{
		$result = $dsql->GetArray($qr, MYSQL_ASSOC);
		if(!empty($result)){
			return Authorization::createUserSession($result);
		}
		else{
			return false;
		}
	}
}
function updateSnapshot(&$request, &$snapshot, $updatetime)
{
	global $dsql, $logger;
	$sql = "update ".DATABASE_ELEMENT." set snapshot = '".jsonEncode4DB($snapshot)."', updatetime = {$updatetime} 
		where elementid = {$request['elementid']}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		return false;
	}
	$dsql->FreeResult($qr);
	if($request['instancetype'] != 1){//非标准分析模型,更新其他elements的updatetime
		$sql = "update ".DATABASE_ELEMENT." set updatetime = {$updatetime} 
			where instanceid = {$request['instanceid']} and elementid != {$request['elementid']}";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			return false;
		}
		$dsql->FreeResult($qr);
	}
	return true;
}
function saveSnapshotHistory(&$request,&$snapshot, $updatetime)
{
	global $dsql, $logger, $task;
	connectDB(DATABASE_NAME);
	$sql = "select instanceid, elementid, content, snapid from ".DATABASE_ELEMENT." where instanceid = {$request['instanceid']} and snapid != ''";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
		return false;
	}

	$elementids = array();
	if($dsql->GetTotalRow($qr) > 0){
		while($hist = $dsql->GetArray($qr)){
			if(!empty($hist) && !empty($hist['snapid'])){
				$elementids[] = $hist['elementid'];
				$content = json_decode($hist['content']);
				//unset($content->filter); //需要filter, 用来显示查询字段关系
				if(empty($task->taskparams->history_enable)){ //不保存快照历史,更新对应快照的数据
					$sqlup = "update ".DATABASE_SNAPSHOT_HISTORY." set updatetime = ".$updatetime.", snapshot = '".jsonEncode4DB($snapshot)."' where snapid = ".$hist['snapid']."";
					$qrup = $dsql->ExecQuery($sqlup);
					if(!$qrup){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqlup} ".$dsql->GetError());
						$arrs["flag"]=0;
					}
				}
				else{
					//在历史表添加上最新的快照
					$sqllatest = "insert into ".DATABASE_SNAPSHOT_HISTORY." (instanceid,elementid,updatetime,content,snapshot) values ({$hist['instanceid']},{$hist['elementid']},{$updatetime},'".jsonEncode4DB($content)."','".jsonEncode4DB($snapshot)."')";
					$qrlatest = $dsql->ExecQuery($sqllatest);
					if(!$qrlatest){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sqllatest} ".$dsql->GetError());
						return false;
					}
					else{
						$getlastid = "select LAST_INSERT_ID() as id";
						$gqr = $dsql->ExecQuery($getlastid);
						if(!$gqr){
							$logger->error(__FILE__." func:".__FUNCTION__." sql:{$getlastid} ".$dsql->GetError());
							$arrs["flag"]=0;
						}
						else{
							$lastid = $dsql->GetArray($gqr, MYSQL_ASSOC);
							$upsql = "update ".DATABASE_ELEMENT." set snapid = ".$lastid["id"]." where elementid = ".$hist["elementid"]."";
							$upqr = $dsql->ExecQuery($upsql);
							if(!$upqr){
								$logger->error(__FILE__." func:".__FUNCTION__." sql:{$upsql} ".$dsql->GetError());
								$arrs["flag"]=0;
							}
						}
					}
					$dsql->FreeResult($qrlatest);
				}
			}
		}
	}
	$dsql->FreeResult($qr);
	if(!empty($task->taskparams->history_count)){
		if(!empty($elementids)){
			foreach($elementids as $elementid){
				$sql = "select count(0) as cnt from ".DATABASE_SNAPSHOT_HISTORY." where elementid = {$elementid} and instanceid = {$request['instanceid']}";
				$qr = $dsql->ExecQuery($sql);
				if(!$qr){
					$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
					return false;
				}
				$rcnt = $dsql->GetArray($qr);
				$dsql->FreeResult($qr);
				$delcnt = $rcnt['cnt'] - $task->taskparams->history_count;
				if($delcnt > 0){
					$sql = "delete from ".DATABASE_SNAPSHOT_HISTORY." where elementid = {$elementid} and instanceid = {$request['instanceid']} order by updatetime asc limit {$delcnt}";
					$qr = $dsql->ExecQuery($sql);
					if(!$qr){
						$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
						return false;
					}
					$dsql->FreeResult($qr);
				}
			}
		}
	}
	else if(!empty($task->taskparams->history_duration)){
	  	$deltime = $updatetime - $task->taskparams->history_duration;
	  	$sql = "delete from ".DATABASE_SNAPSHOT_HISTORY." where instanceid = {$request['instanceid']} and updatetime < {$deltime}";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			return false;
		}
		$dsql->FreeResult($qr);
	}
	return true;
}
