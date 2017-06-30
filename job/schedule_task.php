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
ini_set('include_path', get_include_path() . '/lib');

initLogger(LOGNAME_SCHEDULER);
$logger->info("定时任务启动... ");
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
try {
    scheduleTasks();
} catch (Exception $ex) {
    $logger->fatal(SELF . " " . $ex->getMessage());
    exit;
}
exit;

function scheduleTasks()
{
    global $dsql, $logger;
    $logger->info("scheduleTasks... ");

    $result = true;
    $now = time();
    $expire = array();
    $limitcursor = 0;
    $eachcount = 10;
    while (1) {
        $logger->info("查询... ");

        $sql = "select * from " . DATABASE_TASKSCHEDULE . " where status = 1 order by id limit {$limitcursor},{$eachcount}";
        $logger->info(SELF . " .".  DATABASE_TASKSCHEDULE);

        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
            $result = false;
            break;
        } else {
            $logger->info("查询ok! ");

            $r_count = $dsql->GetTotalRow($qr);
            if ($r_count == 0) {
                break;
            } else {
                $logger->info(SELF . " 查询到:[{$r_count}条记录!].");
            }
            while ($sched = $dsql->GetObject($qr)) {
                if (isset($sched->starttime) && $sched->starttime > $now) {
                    //没有到开始时间
                    $logger->info(SELF . " 没有到开始时间，退出!");
                    continue;
                }
                if (isset($sched->endtime) && $sched->endtime <= $now) {
                    //定时任务已经失效：运行完成
                    $logger->info(SELF . " 定时任务已经失效，退出!");
                    $expire[] = $sched;
                    continue;
                }

                $sched->crontime = json_decode($sched->crontime);
                $time = $now - $now % $sched->crontime->precision;
                if ($sched->updatetime == $time) {
                    $logger->info(SELF . " updatetime == time，退出!");
                    continue;
                }
                if (!matchCronMask($sched->crontime->cronmask, $now)) {
                    continue;
                }

                $sched->status = 2;
                if (updateTaskScheduleStatus($sched, "status = 1") <= 0) {
                    $logger->info(SELF . " 发生冲突，跳过定时任务{$sched->id}");
                    continue;
                }
                $sched->params = json_decode($sched->params);
                if ($sched->task == TASK_COMMON) {
                    $ret = spawnCommomTask($sched, $time);
                } else {
                    $ret = spawnTask($sched, $time);
                }
                if ($ret['result'] == false) {
                    $logger->error(__FUNCTION__ . " " . date("Y-m-d H:i:s", $time) . " id:{$sched->id} error:" . $ret['msg']);
                    $logger->info(SELF . " 创建任务出错，停止定时任务{$sched->id}");
                    $sched->status = 0;
                } else {
                    $sched->status = 1;
                }
                $sched->updatetime = $time;
                updateTaskScheduleInfo($sched, "status = 2");
            }
            $dsql->FreeResult($qr);
        }
        if ($r_count < $eachcount) {
            break;
        }
        $limitcursor += $eachcount;
    }
    if (!empty($expire)) {
        foreach ($expire as $sched) {
            $logger->info(SELF . " 删除过期定时任务{$sched->id}");
            completeTaskSchedule($sched);
        }
    }
    return $result;
}

function spawnTask(&$schedule, $now)
{
    global $dsql, $logger;
    $result = array('result' => true, 'msg' => '');
    try {
        $logger->info(SELF . " 开始创建自定时任务，定时任务ID：{$schedule->id}");
        $task = new Task(NULL);
        $task->taskparams = clone $schedule->params->taskparams;
        $task->tasktype = $schedule->tasktype;
        if (isset($schedule->taskpagestyletype)) {
            $task->taskpagestyletype = $schedule->taskpagestyletype;
        }
        $task->task = $schedule->task;
        $task->tasklevel = $schedule->tasklevel;
        $task->local = $schedule->local;
        $task->remote = $schedule->remote;
        $task->activatetime = 0;
        $task->conflictdelay = $schedule->conflictdelay;
        $task->taskclassify =  $schedule->taskclassify;
        $task->spcfdmac = $schedule->spcfdmac;
        $task->remarks = "#创建自定时任务#" . $schedule->id . "#" . $schedule->remarks;
        $task->tenantid = $schedule->tenantid;
        $task->userid = $schedule->userid;
		$logger->info(SELF . " 自定时任务schedule:[" . var_export($task, true) . "].");
        if (!empty($schedule->params->relativestart)) {
            $task->taskparams->starttime = strtotime($schedule->params->relativestart, $now);
        }
        if (!empty($schedule->params->relativeend)) {
            $task->taskparams->endtime = strtotime($schedule->params->relativeend, $now);
        }
        if (!empty($schedule->params->recordtime)) {
            $task->taskparams->spawntime = $now;
            $task->taskparams->scheduleid = $schedule->id;
        }
        if (!empty($schedule->params->nodup)) {
            $logger->info("here");
            if (addTask($task) == false) {
                $result['result'] = false;
                $result['msg'] = '添加任务失败';
            } else {
                $duplicate = 0;
                $taskadded = 1;
            }
        } else {
            $logger->info("there");
            $precision = empty($schedule->crontime->precision) ? 60 : $schedule->crontime->precision;
            $ret = checkDupTask($task, $precision);
            if (!$ret['result']) {
                $result['result'] = false;
                $result['msg'] = $ret['msg'];
            } else {
                $duplicate = $ret['dup'];
                $taskadded = 0;
                if (!empty($ret['tasks'])) {
                    foreach ($ret['tasks'] as $onetask) {
                        if (addTask($onetask) == false) {
                            $result['result'] = false;
                            $result['msg'] = '添加任务失败';
                            break;
                        }
                        $taskadded++;
                    }
                }
            }
        }
        if ($result['result']) {
            if (isset($schedule->params->scene)) {
                unset($schedule->params->scene);
            }
            $schedule->params->scene = (object)array();
            $schedule->params->scene->duplicate = $duplicate;
            $schedule->params->scene->taskadded = $taskadded;
        }
    } catch (Exception $ex) {
        $result['result'] = false;
        $result['msg'] = $ex->getMessage();
    }
    return $result;
}

function spawnCommomTask(&$schedule, $now)
{
    global $logger;
//    $logger->info(SELF . " 生成通用类型的抓取任务...scheduleId:[{$schedule->id}].");
    $logger->info(SELF . " 开始创建通用抓取任务，定时任务ID：{$schedule->id}");
//	$logger->info(__FILE__.__LINE__ . " schedule21:[" . var_export($schedule, true) . "].");
    $result = array('result' => true, 'msg' => '');
    try {
        $task = new Task(NULL);
		$task->taskparams = $schedule->params->taskparams;

//		$scheduleTaskParam = $schedule->params->taskparams->root;
		//$curTaskParam = $task->taskparams->root;

        //****************************测试代码*****************//
        //$taskParam4Test = getTaskParam4Test("2");
        //$task->taskparams = $taskParam4Test;
        $scheduleTaskParam = $schedule->params->taskparams;
        $logger->info("the scheduleparam is:".var_export($scheduleTaskParam,true));


        //addParam4Test($scheduleTaskParam);
        $curTaskParam = $task->taskparams->root;




        //  add column column1
        if(isset($curTaskParam->paramsDef->column) && isset($curTaskParam->paramsDef->column1)){
            $column = $curTaskParam->paramsDef->column;
            $column1 = $curTaskParam->paramsDef->column1;
            $logger->info("the column is:".var_export($column,true));
            $logger->info("the column1 is:".var_export($column1,true));
            $task->column = $column;
            $task->column1 = $column1;
        }

        //end



        $task->tasktype = $schedule->tasktype;
		// if (isset($schedule->taskpagestyletype)) {
        //    $task->taskpagestyletype = $schedule->taskpagestyletype;
        //}
        $task->task = $schedule->task;
        $task->tasklevel = $schedule->tasklevel;
        //$task->tasklevel = $scheduleTaskParam->taskpro->tasklevel;
        $task->local = $schedule->local;
        //$task->local =$scheduleTaskParam->taskpro->local;
        $task->remote = $schedule->remote;
        //$task->remote = $scheduleTaskParam->taskpro->remote;
        $task->activatetime = 0;
        $task->conflictdelay = $schedule->conflictdelay;
        //$task->conflictdelay = $scheduleTaskParam->taskpro->conflictdelay;
        $task->taskclassify =  $schedule->taskclassify;
        $task->spcfdmac = $schedule->spcfdmac;

        $task->remarks = "[通用抓取任务]###创建自定时任务[#" . $schedule->id . "#" . $schedule->remarks . "]";
        $task->tenantid = $schedule->tenantid;
        $task->userid = $schedule->userid;

        if (!empty($schedule->params->recordtime)) {
            if (empty($curTaskParam->runTimeParam)) {
                $curTaskParam->runTimeParam = (object)array();
            }
            $curTaskParam->runTimeParam->spawntime = $now;
            $curTaskParam->runTimeParam->scheduleid = $schedule->id;
        }
        $logger->info("the task is:".var_export($task,true));
        //通用任务目前不做去重处理，只是在addTask里面根据任务类型(common)和taskParam判断有没有重复额任务正在执行；来判重
//        $logger->info(SELF . " task:[" . var_export($task, true) . "].");
		if (addTask($task) == false) {
			$result['result'] = false;
			$result['msg'] = '添加任务失败';
		} else {
			$duplicate = 0;
			$taskadded = 1;
		}
		 

        if ($result['result']) {
            //添加成功
            if (isset($schedule->params->scene)) {
                unset($schedule->params->scene);
            }
            $schedule->params->scene = (object)array();
            $schedule->params->scene->duplicate = $duplicate;
            $schedule->params->scene->taskadded = $taskadded;
        }
    } catch (Exception $ex) {
        $result['result'] = false;
        $result['msg'] = $ex->getMessage();
    }
    $logger->info(SELF . " 通用类型的任务!...result:[" . var_export($result, true) . "].");

    $logger->info(SELF . " 生成通用类型的抓取任务成功!...TaskInfo:[" . var_export($task, true) . "] scheduleParams:[" . var_export($schedule->params, true) . "].");
    return $result;
}

function addParam4Test(&$scheduleTaskParam)
{
    global $logger;

    $scheduleTaskParam->taskpro->tasklevel = 1;
    $scheduleTaskParam->taskpro->local = 0;
    $scheduleTaskParam->taskpro->remote = 1;
    $scheduleTaskParam->taskpro->conflictdelay = 60;

    //$logger->info(SELF . " addParam4Test success.scheduleTaskParam:[" . var_export($scheduleTaskParam, true) . "].");
		//	$task->tasklevel =$scheduleTaskParam->taskpro->tasklevel;
		//$task->local = $scheduleTaskParam->taskpro->local;
		//$task->remote = $scheduleTaskParam->taskpro->remote;
		//$task->activatetime = 0;
		//$task->conflictdelay = $scheduleTaskParam->taskpro->conflictdelay;
}


