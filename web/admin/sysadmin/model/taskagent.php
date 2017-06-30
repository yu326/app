<?php

define("SELF", basename(__FILE__));
define("GET_DATA", 3);    //通过该标识，获取配置信息和任务信息
define("CONFIG_TYPE", GET_DATA);    //需要在include common.php之前，定义CONFIG_TYPE

define("TYPE", "type");//根据参数类型调用不同的函数
define("JAVAGET", "javaget");
define("GET", "get");
define("DROP", "drop");
define("COMPLETE", "complete");
define("SUSPEND", "suspend");
define("CHECK", "check");

//任务执行异常 将任务的状态更改为异常
define("TERMINATED", "terminated");

include_once('includes.php');
include_once('commonFun.php');
include_once('taskcontroller.php');
include_once("authorization.class.php");
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
//include 通用任务处理类
include_once('commomTaskUtil.php');
ini_set('include_path', get_include_path() . '/lib');

session_start();
set_time_limit(0);
initLogger(LOGNAME_WEBAPI);//初始化日志配置
$chkr = Authorization::checkUserSession();

if ($chkr != CHECKSESSION_SUCCESS) {
    setErrorMsg($chkr, "未登录或登陆超时!");
}

$arg_type = isset($_POST[TYPE]) ? $_POST[TYPE] : $_GET[TYPE];
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);

switch ($arg_type) {
    case JAVAGET:
        try {
            getAgentTaskByID();
        } catch (Exception $e) {
            $logger->error(SELF . " - get Task from java api 异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    case GET:
        try {
            getAgentTask();
        } catch (Exception $e) {
            $logger->error(SELF . " - getAgentTask 异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    case DROP:
        try {
            dropAgentTask();
        } catch (Exception $e) {
            $logger->error(SELF . " - dropAgentTask 异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    case COMPLETE:
        try {
            completeAgentTask();
        } catch (Exception $e) {
            $logger->error(SELF . " - 完成任务异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    case SUSPEND:
        try {
            suspendAgentTask();
        } catch (Exception $e) {
            $logger->error(SELF . " - suspendAgentTask 异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    case CHECK:
        try {
            checkAgentTask();
        } catch (Exception $e) {
            $logger->error(SELF . " - checkAgentTask 异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    case TERMINATED:
        try {
            terminateAgentTask();
        } catch (Exception $e) {
            $logger->error(SELF . " - terminateAgentTask 异常. errorMsg:[" . $e->getMessage() . "].");
        }
        break;
    default:
        $logger->error(SELF . "参数错误：" . $arg_type);
}

function getAgentTaskByID()
{
    global $logger, $dsql;
    $taskid = isset($_POST['taskid']) ? $_POST['taskid'] : $_GET['taskid'];
    $logger->debug("通过任务ID[".$taskid."]来读取任务");
    $result = array('result' => true, 'msg' => '');
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];
    $taskobj = NULL;
    $task = isset($_POST['task']) ? $_POST['task'] : $_GET['task'];
    if($taskid != ""){
        $taskobj = getTaskById($taskid);
    }else{
        $logger->debug("任务ID为空！");
    }

    if (empty($taskobj)) {
        $logger->warn(SELF . " - 未找到待启动任务，退出");
        echo json_encode($result);
        exit;
    }

    if ($task == TASK_COMMON) {
        $logger->info(SELF . " " . __FUNCTION__ . "从数据库 DBDataName:[" . DATABASE_WEIBOINFO . "]中获取任务成功,任务类型:[" . $task . "] 开始处理...");
        getAgentTaskCommon($taskobj, $gtStartTime);
        exit;
    } else {
        $startTime = time();//当前时间
        $rt = detectConflictTask($taskobj);
        if (!$rt['result']) {
            $result['result'] = false;
            $result['msg'] = '冲突检测失败';
            $logger->error(SELF . "- 冲突检测失败 -" . $rt['msg']);
            echo json_encode($result);
            $logger->error(SELF . " - 任务停止");
            stopTask($taskobj);
            exit;
        } else if (!$rt['continue']) {
            $logger->error(SELF . " - 任务冲突，延迟启动");
            //getAgentTask();
            exit;
        }
        $endTime = time();//当前时间
        $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " 冲突检测 用时:[" . ($endTime - $startTime) . "].");
    }

    if (!empty($taskobj->taskparams->followpost)) {
        $taskobj->taskparams->followpost = array();
    }

    if ($task == TASK_REPOST_TREND || $task == TASK_COMMENTS) {
        $taskobj->local = 0;
        if (empty($taskobj->starttime)) {
            $taskobj->starttime = time();
        }
        if ($task == TASK_REPOST_TREND) {
            if (!isset($taskobj->taskparams->phase)) {
                $taskobj->taskparams->phase = 1;
            }
        }
    } else {
        $taskobj->starttime = time();
        if (isset($taskobj->taskparams->scene)) {
            unset($taskobj->taskparams->scene);
        }
        $taskobj->datastatus = 0;
    }
    if (!isset($taskobj->taskparams->scene)) {
        $taskobj->taskparams->scene = (object)array();
    }
    $taskobj->taskparams->scene->isremote = 1;
    $taskobj->machine = $host;
    $taskobj->timeout = time() + $taskobj->taskparams->duration;
    if (!isset($taskobj->taskparams->scene->historystat)) {
        $taskobj->taskparams->scene->historystat = NULL;
    }
    if ($task != TASK_REPOST_TREND && $task != TASK_COMMENTS && $task != TASK_WEIBO) {
        updateTaskFull($taskobj);
    }
    $result['task'] = array();
    if ($task == TASK_KEYWORD || $task == TASK_FRIEND || $task == TASK_WEIBO || $task == TASK_REPOST_TREND || $task == TASK_COMMENTS || $task == TASK_WEBPAGE) {
        $accounts = array();
        if (!empty($taskobj->taskparams->accountid)) {
            foreach ($taskobj->taskparams->accountid as $acntid) {
                $sql = "select username, password from " . DATABASE_SPIDERACCOUNT . " where id={$acntid}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $result['result'] = false;
                    $result['msg'] = '查询账号失败';
                    $logger->error(SELF . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError());
                    echo json_encode($result);
                    exit;
                }
                if ($acnt = $dsql->GetArray($qr)) {
                    $accounts[] = $acnt;
                }
                $dsql->FreeResult($qr);
            }
        }
        if ($task == TASK_REPOST_TREND && $taskobj->taskparams->phase == 2) {
            $config = 5; //处理转发对应的模板
        } else if ($task == TASK_WEBPAGE) {
            switch ($taskobj->taskpagestyletype) {
                case TASK_PAGESTYLE_ARTICLELIST:
                    $config = $taskobj->taskparams->SEtemplate; //搜索引擎模板
                    break;
                case TASK_PAGESTYLE_ARTICLEDETAIL:
                    $config = $taskobj->taskparams->SStemplate;
                    break;
                case TASK_PAGESTYLE_USERDETAIL:
                    $config = $taskobj->taskparams->usertemplate; //搜索引擎模板
                    break;
                default:
                    break;
            }
        } else {
            $config = $taskobj->taskparams->config;
        }
        $sql = "select * from " . DATABASE_SPIDERCONFIG . " where id={$config}";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $result['result'] = false;
            $result['msg'] = '查询配置失败';
            $logger->error(SELF . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError());
            echo json_encode($result);
            exit;
        }
        if ($conf = $dsql->GetObject($qr)) {
            $eol = (strpos($conf->content, "\r\n") === false) ? "\n" : "\r\n";
            if (!empty($conf->urlregex)) {
                $urstr = $conf->urlregex;
                $urarr = preg_split("/[\r\n,\s]+/", $urstr);
                $result['task']['urlregex'] = $urarr;
            }
            if (!empty($conf->detailurlregex)) {
                $durstr = $conf->detailurlregex;
                $durarr = preg_split("/[\r\n,\s]+/", $durstr);
                $result['task']['detailurlregex'] = $durarr;
            }
            if ($task == TASK_KEYWORD) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed}}" . $eol;
                $result['task']['config'] .= $conf->content;
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $dup = empty($taskobj->taskparams->filterdup) ? "&nodup=1" : "";
                    $url = "http://s.weibo.com/weibo/"; //2015-5-24 wb->weibo
                    $sort = "&xsort=time";
                    if (empty($taskobj->taskparams->keywords)) {
                        $keyword = "*";
                        $enums = "";
                    } else {
                        $keyword = "\$<keyword \"%s\">";
                        $words = array();
                        foreach ($taskobj->taskparams->keywords as $word) {
                            $words[] = rawurlencode($word);
                        }
                        $enums = "keyword:Enum(" . implode(",", $words) . ") ";
                    }
                    $username = empty($taskobj->taskparams->username) ? "" : ("&userscope=custom:" . rawurlencode($taskobj->taskparams->username));
                    $step = empty($taskobj->taskparams->step) ? "" : $taskobj->taskparams->step;
                    if (strpos($step, 'h') !== false) {
                        $stepnum = intval($step);
                        $hour = intval(date("H", $taskobj->taskparams->starttime));
                        $shift = ($hour % $stepnum) * 3600;
                        $starttime = date("Y-m-d-H", $taskobj->taskparams->starttime - $shift);
                        $endtime = date("Y-m-d-H", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . $keyword . $sort . $username . "&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour + {$stepnum} - 1 \"%02d\">" . $dup . "{" . $enums . "time:Time({$starttime},{$endtime},{$stepnum}h)}";
                    } else if ($step == '1d') {
                        $starttime = date("Y-m-d", $taskobj->taskparams->starttime);
                        $endtime = date("Y-m-d", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . $keyword . $sort . $username . "&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">" . $dup . "{" . $enums . "time:Time({$starttime},{$endtime},1d)}";
                    } else {
                        $starttime = isset($taskobj->taskparams->starttime) ? date("Y-m-d-H", $taskobj->taskparams->starttime) : "";
                        $endtime = "";
                        if (isset($taskobj->taskparams->endtime)) {
                            //新浪微博2014-04-26 07时~ 2014-04-26 07时搜索的是7点的微博,所以设置的结束时间应该减少一个小时
                            $tmpendtime = $taskobj->taskparams->endtime;
                            if ($tmpendtime % 3600 == 0) {
                                $urlendtime = $tmpendtime - 3600;
                            } else {
                                $urlendtime = $tmpendtime;
                            }
                            $endtime = date("Y-m-d-H", $urlendtime);
                        }
                        if (!empty($enums)) {
                            $enums = "{" . $enums . "}";
                        }
                        $result['task']['url'] = $url . $keyword . $sort . $username . "&timescope=custom:{$starttime}:{$endtime}" . $dup . $enums;
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_FRIEND) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed}}" . $eol;
                $result['task']['config'] .= $conf->content;
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $url = "http://weibo.com/";
                    $result['task']['url'] = $url . "\$<uid \"%s\">/follow{uid:Enum(" . implode(",", $taskobj->taskparams->uids) . ")}";
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_WEIBO) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed},\"usertimeline\":1}" . $eol;
                $result['task']['config'] .= $conf->content;
                $res = getGlobalResource($taskobj);
                if ($res['result'] == false) {
                    $logger->error(SELF . " " . __FUNCTION__ . " " . $res['msg']);
                    $logger->info(SELF . " - 重新申请任务");
                    $taskobj->taskstatus = 0;
                    $taskobj->machine = NULL;
                    $taskobj->timeout = NULL;
                    updateTaskFull($taskobj);
                    // getAgentTask($taskobj->id);
                    exit;
                }
                $seedusers = getSeedUser($taskobj, true);
                releaseGlobalResource();
                updateTaskFull($taskobj);
                if (empty($seedusers)) {
                    global $needqueue;
                    if (!empty($needqueue)) {
                        $logger->info(SELF . " - 重新申请任务");
                        $taskobj->taskstatus = 0;
                        $taskobj->machine = NULL;
                        $taskobj->timeout = NULL;
                        updateTaskFull($taskobj);
                        //getAgentTask($taskobj->id);
                        exit;
                    }
                    $result['result'] = false;
                    $result['msg'] = '未获取到种子用户';
                    $logger->error(SELF . " " . __FUNCTION__ . " 未获取到种子用户");
                    echo json_encode($result);
                    $logger->info(SELF . " - 任务停止");
                    $taskobj->timeout = NULL;
                    stopTask($taskobj);
                    exit;
                }
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $url = "http://s.weibo.com/weibo/";
                    $sort = "&xsort=time";
                    $users = array();
                    foreach ($seedusers as $user) {
                        $users[] = rawurlencode($user['screen_name']);
                    }
                    $step = empty($taskobj->taskparams->step) ? "" : $taskobj->taskparams->step;
                    if (strpos($step, 'h') !== false) {
                        $stepnum = intval($step);
                        $hour = intval(date("H", $taskobj->taskparams->starttime));
                        $shift = ($hour % $stepnum) * 3600;
                        $starttime = date("Y-m-d-H", $taskobj->taskparams->starttime - $shift);
                        $endtime = date("Y-m-d-H", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . "*" . $sort . "&userscope=custom:\$<uname \"%s\">&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour + {$stepnum} - 1 \"%02d\">&nodup=1{uname:Enum(" . implode(",", $users) . ") time:Time({$starttime},{$endtime},{$stepnum}h)}";
                    } else if ($step == '1d') {
                        $starttime = date("Y-m-d", $taskobj->taskparams->starttime);
                        $endtime = date("Y-m-d", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . "*" . $sort . "&userscope=custom:\$<uname \"%s\">&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">&nodup=1{uname:Enum(" . implode(",", $users) . ") time:Time({$starttime},{$endtime},1d)}";
                    } else {
                        $starttime = isset($taskobj->taskparams->starttime) ? date("Y-m-d-H", $taskobj->taskparams->starttime) : "";
                        $endtime = "";
                        if (isset($taskobj->taskparams->endtime)) {
                            //新浪微博2014-04-26 07时~ 2014-04-26 07时搜索的是7点的微博,所以设置的结束时间应该减少一个小时
                            $tmpendtime = $taskobj->taskparams->endtime;
                            if ($tmpendtime % 3600 == 0) {
                                $urlendtime = $tmpendtime - 3600;
                            } else {
                                $urlendtime = $tmpendtime;
                            }
                            $endtime = date("Y-m-d-H", $urlendtime);
                        }
                        $result['task']['url'] = $url . "*" . $sort . "&userscope=custom:\$<uname \"%s\">&timescope=custom:{$starttime}:{$endtime}&nodup=1{uname:Enum(" . implode(",", $users) . ")}";
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_REPOST_TREND) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isrepostseed},\"repost\":1}" . $eol;
                $result['task']['config'] .= $conf->content;
                $res = getGlobalResource($taskobj);
                if ($res['result'] == false) {
                    $logger->error(SELF . " " . __FUNCTION__ . " " . $res['msg']);
                    $logger->info(SELF . " - 重新申请任务");
                    $taskobj->taskstatus = 0;
                    $taskobj->machine = NULL;
                    $taskobj->timeout = NULL;
                    updateTaskFull($taskobj);
                    //getAgentTask($taskobj->id);
                    exit;
                }
                $seedweibo = false;
                switch ($taskobj->taskparams->phase) {
                    case 1:
                        do {
                            $seedweibo = getRepostSeedWeibo($taskobj);
                            if (!empty($seedweibo)) {
                                if ($seedweibo['reposts_count'] == 0) {
                                    $taskobj->taskparams->select_cursor++;
                                    continue;
                                }
                                //根据直接转发数,如果没有direct_reposts_count或值为0,字段说明没有分析过,都需要重新分析,
                                if (!empty($seedweibo['direct_reposts_count'])) {
                                    if (!$taskobj->taskparams->forceupdate) {
                                        $exist_count = getExistsCount($seedweibo);
                                        if ($exist_count === false) {
                                            $logger->error(SELF . " " . __FUNCTION__ . " 查询已存在转发数失败");
                                            $seedweibo = false;
                                            break;
                                        }
                                        //存在数大于转发数说明是有删除的微博,这种情况不重新分析.
                                        //存在风险可能开始没有分析过,但转发都抓取下来入库了, 分析转发轨迹时由于新浪微博转发删除造成exist_count > reposts_count 将不能分析传播轨迹
                                        if ($exist_count >= $seedweibo['reposts_count']) {
                                            $taskobj->taskparams->select_cursor++;
                                            continue;
                                        }
                                    }
                                }
                                if (!isset($taskobj->taskparams->repost)) {
                                    $taskobj->taskparams->repost = array();
                                }
                                $size = count($taskobj->taskparams->repost);
                                if ($size == 0 || $taskobj->taskparams->repost[$size - 1]->orig != $seedweibo['id']) {
                                    $taskobj->taskparams->repost[] = array('orig' => $seedweibo['id'], 'idnum' => 0);
                                }
                                if (!empty($seedweibo['id2url'])) {
                                    $taskobj->taskparams->currorigurl = $seedweibo['id'] . "({$seedweibo['weibourl']})";
                                } else {
                                    $taskobj->taskparams->currorigurl = $seedweibo['weibourl'];
                                }
                            } else {
                                $taskobj->taskparams->currorigurl = "";
                            }
                            break;
                        } while (true);
                        break;
                    case 2:
                        do {
                            $id = getRepostId($taskobj->taskparams->repost[$taskobj->taskparams->origin_cursor]->orig, NULL, $taskobj->taskparams->repost_cursor, $taskobj->id);
                            $seedweibo = false;
                            if ($id !== false) {
                                if (empty($id)) {
                                    $seedweibo = NULL;
                                } else {
                                    $res = getWeiboById($taskobj->taskparams->source, $id, $taskobj->taskparams->isrepostseed);
                                    if ($res['result'] == false) {
                                        if ($res['notext'] == true) {
                                            $seedweibo = NULL;
                                        }
                                    } else {
                                        $seedweibo = $res['weibo'];
                                        if (empty($seedweibo) || $seedweibo['reposts_count'] == 0) {
                                            $seedweibo = NULL;
                                        }
                                    }
                                }
                            }
                            if ($seedweibo === NULL) {
                                $taskobj->taskparams->repost_cursor--;
                                if ($taskobj->taskparams->repost_cursor <= 0) {
                                    if ($taskobj->taskparams->iscalctrend) {
                                        updateRepostTrend($taskobj, $taskobj->taskparams->origin_cursor);
                                    }
                                    $taskobj->taskparams->origin_cursor++;
                                    for (; $taskobj->taskparams->origin_cursor < count($taskobj->taskparams->repost); $taskobj->taskparams->origin_cursor++) {
                                        $taskobj->taskparams->repost_cursor = $taskobj->taskparams->repost[$taskobj->taskparams->origin_cursor]->idnum - 1;
                                        if ($taskobj->taskparams->repost_cursor > 0) {
                                            break;
                                        }
                                    }
                                }
                                if ($taskobj->taskparams->origin_cursor >= count($taskobj->taskparams->repost)) {
                                    break;
                                }
                            } else {
                                break;
                            }
                        } while (true);
                        break;
                    default:
                        break;
                }
                if ($seedweibo === false) {
                    releaseGlobalResource();
                    $result['result'] = false;
                    $result['msg'] = '获取种子微博异常';
                    $logger->error(SELF . " " . __FUNCTION__ . " 获取种子微博异常");
                    echo json_encode($result);
                    $logger->info(SELF . " - 任务停止");
                    $taskobj->timeout = NULL;
                    stopTask($taskobj);
                    exit;
                } else if (empty($seedweibo)) {
                    if ($taskobj->taskparams->phase == 1 && !empty($taskobj->taskparams->repost)) {
                        $donephase1 = true;
                        $cntphase2 = 0;
                        $s_time = microtime_float();
                        foreach ($taskobj->taskparams->repost as $idx => $repost) {
                            if ($repost->idnum > 1) {
                                for ($seqno = 0; $seqno < $repost->idnum; $seqno++) {
                                    $repostid = getRepostId($repost->orig, NULL, $seqno, $taskobj->id);
                                    if (empty($repostid)) {
                                        continue;
                                    }
                                    $res = getWeiboById($taskobj->taskparams->source, $repostid, $taskobj->taskparams->isrepostseed);
                                    if ($res['result'] == false) {
                                        if ($res['notext'] == true) {
                                            delRepostInfo($taskobj->id, $repost->orig, $repostid);
                                            continue;
                                        } else {
                                            $donephase1 = false;
                                            break 2;
                                        }
                                    }
                                    if ($res['weibo']['reposts_count'] > 0) {
                                        $cntphase2++;
                                        if (!isset($origin_cursor)) {
                                            $origin_cursor = $idx;
                                        }
                                    }
                                }
                            }
                        }
                        $e_time = microtime_float();
                        $phasetimediff = $e_time - $s_time;
                        $logger->info(SELF . " - 转换任务阶段花费{$phasetimediff}");
                        releaseGlobalResource();
                        if (!$donephase1) {
                            $logger->info(SELF . " - 任务抓取一级转发阶段未完成");
                            $taskobj->taskstatus = 0;
                            $taskobj->machine = NULL;
                            $taskobj->timeout = NULL;
                            updateTaskFull($taskobj);
                            //getAgentTask($taskobj->id);
                            exit;
                        } else if ($cntphase2 > 0) {
                            $taskobj->taskparams->phase = 2;
                            $taskobj->taskparams->repostcount = $cntphase2;
                            $taskobj->taskparams->repostdone = 0;
                            $taskobj->taskparams->origin_cursor = $origin_cursor;
                            $taskobj->taskparams->repost_cursor = $taskobj->taskparams->repost[$origin_cursor]->idnum - 1;
                            $logger->info(SELF . " - 任务进入抓取二级转发阶段");
                            $taskobj->taskstatus = 0;
                            $taskobj->machine = NULL;
                            $taskobj->timeout = NULL;
                            updateTaskFull($taskobj);
                            //getAgentTask();
                            exit;
                        }
                    } else {
                        releaseGlobalResource();
                    }
                    $logger->info(SELF . " " . __FUNCTION__ . " 未获取到种子微博");
                    $logger->info(SELF . " - 任务结束");
                    completeTask($taskobj);
                    delRepostInfo($taskobj->id);
                    //getAgentTask();
                    exit;
                }
                releaseGlobalResource();
                updateTaskFull($taskobj);
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $weibourl = empty($seedweibo['weibourl']) ? weibomid2Url($seedweibo['userid'], $seedweibo['mid'], $taskobj->taskparams->source) : $seedweibo['weibourl'];
                    $result['task']['url'] = $weibourl . "?type=repost";
                    if (!empty($taskobj->taskparams->page_cursor)) {
                        $skippage = "#SKIP:{\"conds\":[{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.node-type\",\"val\":\"forward_detail\"}]},{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.class\",\"val\":\"feed_repeat\"}]}],\"tgt\":\"action-data\",\"reg\":\"page=\\\\d+\",\"val\":\"page={$taskobj->taskparams->page_cursor}\"}" . $eol;
                        $result['task']['config'] = $skippage . $result['task']['config'];
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_COMMENTS) {
                $result['task']['config'] = "#CONST:{\"isseed\":0,\"comment\":1}" . $eol;
                $result['task']['config'] .= $conf->content;
                $res = getGlobalResource($taskobj);
                if ($res['result'] == false) {
                    $logger->error(SELF . " " . __FUNCTION__ . " " . $res['msg']);
                    $logger->info(SELF . " - 重新申请任务");
                    $taskobj->taskstatus = 0;
                    $taskobj->machine = NULL;
                    $taskobj->timeout = NULL;
                    updateTaskFull($taskobj);
                    //getAgentTask($taskobj->id);
                    exit;
                }
                $seedweibo = false;
                do {
                    $seedweibo = getRepostSeedWeibo($taskobj, true);
                    if (!empty($seedweibo)) {
                        if ($seedweibo['comments_count'] == 0) {
                            $taskobj->taskparams->select_cursor++;
                            continue;
                        }
                        //comment_sinceid为空时说明没有分析过评论轨迹
                        if (!empty($seedweibo['comment_sinceid'])) {
                            if (!$taskobj->taskparams->forceupdate) {
                                $exist_count = getCommentsCount($seedweibo);
                                if ($exist_count === false) {
                                    $logger->error(SELF . " " . __FUNCTION__ . " 查询已存在评论数失败");
                                    $seedweibo = false;
                                    break;
                                }
                                if ($exist_count >= $seedweibo['comments_count']) {
                                    $taskobj->taskparams->select_cursor++;
                                    continue;
                                }
                            }
                        }
                        if (!isset($taskobj->taskparams->comment)) {
                            $taskobj->taskparams->comment = array();
                        }
                        $size = count($taskobj->taskparams->comment);
                        if ($size == 0 || $taskobj->taskparams->comment[$size - 1]->orig != $seedweibo['id']) {
                            $tmparr = array();
                            $tmparr['sourceid'] = isset($seedweibo['sourceid']) ? $seedweibo['sourceid'] : NULL;
                            $tmparr['source_host'] = isset($seedweibo['source_host']) ? $seedweibo['source_host'] : NULL;
                            $tmparr['original_url'] = isset($seedweibo['original_url']) ? $seedweibo['original_url'] : NULL;
                            $tmparr['floor'] = isset($seedweibo['floor']) ? $seedweibo['floor'] : NULL;
                            $tmparr['paragraphid'] = isset($seedweibo['paragraphid']) ? $seedweibo['paragraphid'] : NULL;
                            $tmparr['mid'] = isset($seedweibo['mid']) ? $seedweibo['mid'] : NULL;
                            if (isset($seedweibo['comments_count'])) {
                                $tmparr['comments_count'] = $seedweibo['comments_count'];
                            }
                            $tmparr['orig'] = $seedweibo['id'];
                            $tmparr['idnum'] = 0;
                            //$taskobj->taskparams->comment[] = array('orig' => $seedweibo['id'], 'idnum' => 0);
                            $taskobj->taskparams->comment[] = $tmparr;
                        }
                        if (!empty($seedweibo['id2url'])) {
                            $taskobj->taskparams->currorigurl = $seedweibo['id'] . "({$seedweibo['weibourl']})";
                        } else {
                            $taskobj->taskparams->currorigurl = $seedweibo['weibourl'];
                        }
                    } else {
                        $taskobj->taskparams->currorigurl = "";
                    }
                    break;
                } while (true);
                if ($seedweibo === false) {
                    releaseGlobalResource();
                    $result['result'] = false;
                    $result['msg'] = '获取种子微博异常';
                    $logger->error(SELF . " " . __FUNCTION__ . " 获取种子微博异常");
                    echo json_encode($result);
                    $logger->info(SELF . " - 任务停止");
                    $taskobj->timeout = NULL;
                    stopTask($taskobj);
                    exit;
                } else if (empty($seedweibo)) {
                    $depid = findDependTask($taskobj);
                    if ($depid === false) {
                        releaseGlobalResource();
                        $result['result'] = false;
                        $result['msg'] = '获取依赖任务异常';
                        $logger->error(SELF . " " . __FUNCTION__ . " 获取依赖任务异常");
                        echo json_encode($result);
                        $logger->info(SELF . " - 任务停止");
                        $taskobj->timeout = NULL;
                        stopTask($taskobj);
                        exit;
                    } else if ($depid !== 0) {
                        releaseGlobalResource();
                        $logger->info(SELF . " - 任务 数据入库未完成");
                        $taskobj->taskstatus = 0;
                        $taskobj->machine = NULL;
                        $taskobj->timeout = NULL;
                        updateTaskFull($taskobj);
                        //getAgentTask($taskobj->id);
                        exit;
                    }
                    if (!empty($taskobj->taskparams->comment)) {
                        for ($orig = 0; $orig < count($taskobj->taskparams->comment); $orig++) {
                            updateCommentTrend($taskobj, $orig);
                        }
                    }
                    releaseGlobalResource();
                    $logger->info(SELF . " " . __FUNCTION__ . " 未获取到种子微博");
                    $logger->info(SELF . " - 任务结束");
                    completeTask($taskobj);
                    //getAgentTask();
                    exit;
                }
                releaseGlobalResource();
                updateTaskFull($taskobj);
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $weibourl = empty($seedweibo['weibourl']) ? weibomid2Url($seedweibo['userid'], $seedweibo['mid'], $taskobj->taskparams->source) : $seedweibo['weibourl'];
                    $result['task']['url'] = $weibourl . "?type=comment";
                    if (!empty($taskobj->taskparams->page_cursor)) {
                        $skippage = "#SKIP:{\"conds\":[{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.node-type\",\"val\":\"comment_detail\"}]},{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.class\",\"val\":\"feed_repeat\"}]}],\"tgt\":\"action-data\",\"reg\":\"page=\\\\d+\",\"val\":\"page={$taskobj->taskparams->page_cursor}\"}" . $eol;
                        $result['task']['config'] = $skippage . $result['task']['config'];
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_WEBPAGE) {
                $texttpl = !empty($taskobj->taskparams->SStemplate) ? $taskobj->taskparams->SStemplate : -1;
                $usertpl = !empty($taskobj->taskparams->usertemplate) ? $taskobj->taskparams->usertemplate : -1;
                $filter = "";
                $createdtimestart = !empty($taskobj->taskparams->createdtimestart) ? $taskobj->taskparams->createdtimestart : -1;
                $createdtimeend = !empty($taskobj->taskparams->createdtimeend) ? $taskobj->taskparams->createdtimeend : -1;
                //#CONST:{"derivetexttpl":-1,"deriveusertpl":-1,"filter_start":1416067200,"filter_end":1416067201}
                if ($createdtimestart != -1 || $createdtimeend != -1) {
                    //$filter .= ',"filter_field":"created_at_ts","filter_field_type":"range"';
                    if ($createdtimestart != -1) {
                        $filter .= ',"filter_start":' . $createdtimestart . '';
                    }
                    if ($createdtimeend != -1) {
                        $filter .= ',"filter_end":' . $createdtimeend . '';
                    }
                } else {
                    $lastrplytimestart = !empty($taskobj->taskparams->lastrplytimestart) ? $taskobj->taskparams->lastrplytimestart : -1;
                    $lastrplytimeend = !empty($taskobj->taskparams->lastrplytimeend) ? $taskobj->taskparams->lastrplytimeend : -1;
                    if ($lastrplytimestart != -1 || $lastrplytimeend != -1) {
                        //$filter .= ',"filter_field":"created_at_ts","filter_field_type":"range"';
                        if ($lastrplytimestart != -1) {
                            $filter .= ',"filter_start":' . $lastrplytimestart . '';
                        }
                        if ($lastrplytimeend != -1) {
                            $filter .= ',"filter_end":' . $lastrplytimeend . '';
                        }
                    }
                }
                $result['task']['config'] = "#CONST:{\"derivetexttpl\":{$texttpl},\"deriveusertpl\":{$usertpl} {$filter}}" . $eol;
                $result['task']['config'] .= $conf->content;
                if (isset($taskobj->taskpagestyletype) && $taskobj->taskpagestyletype == TASK_PAGESTYLE_ARTICLELIST) {
                    $result['task']['url'] = $taskobj->taskparams->listurls;
                } else if (isset($taskobj->taskpagestyletype) && $taskobj->taskpagestyletype == TASK_PAGESTYLE_USERDETAIL) {
                    $urls = $taskobj->taskparams->userurls;
                    $result['task']['url'] = $urls;
                } else if (isset($taskobj->taskpagestyletype) && $taskobj->taskpagestyletype == TASK_PAGESTYLE_ARTICLEDETAIL) {
                    $urls = $taskobj->taskparams->texturls;
                    $result['task']['url'] = $urls;
                }
            }
            if (!empty($accounts)) {
                $result['task']['accounts'] = $accounts;
            } else if (!empty($taskobj->taskparams->globalaccount)) {                //使用全局帐号
                $result['task']['globalaccount'] = 1;
                //获取帐号并返回
                $accountres = getNextSpiderAccount($taskobj->taskparams->source);
                if ($accountres['result']) {
                    $result['gaccount'] = $accountres['gaccount'];
                }
            }
            if (isset($taskobj->taskparams->logoutfirst)) {
                $result['task']['logoutfirst'] = $taskobj->taskparams->logoutfirst;
            }
            if (!empty($taskobj->taskparams->isswitch)) {
                $result['task']['switchpage'] = $taskobj->taskparams->switchpage;
                $result['task']['switchtime'] = $taskobj->taskparams->switchtime;
            }
            if (!empty($taskobj->taskparams->crawlpage)) {
                $result['task']['crawlpage'] = $taskobj->taskparams->crawlpage;
            }
        } else {
            $result['result'] = false;
            $result['msg'] = '无效配置';
            $logger->error(SELF . " " . __FUNCTION__ . " 无效配置");
        }
        $dsql->FreeResult($qr);
        if (!$result['result']) {
            echo json_encode($result);
            exit;
        }
        $result['task']['importurl'] = getImportURL();
    }
    $result['task']['id'] = $taskobj->id;
    $logger->debug(SELF . " - 成功分配任务" . json_encode($result));
    echo json_encode($result);
    exit;
}


function unsetVerCodeImg4TaskParam(&$taskparams)
{
    if (!isset($taskparams->root->runTimeParam->scene)) {
        $taskparams->root->runTimeParam->scene = (object)array();
    }
    if (isset($taskparams->root->runTimeParam->scene->veriimage)) {
        unlink($taskparams->root->runTimeParam->scene->veriimage);
        unset($taskparams->root->runTimeParam->scene->veriimage);
    }
    if (isset($taskparams->root->runTimeParam->scene->vericode)) {
        unset($taskparams->root->runTimeParam->scene->vericode);
    }
}

function getAgentTaskCommon(&$taskobj = NULL, &$gtStartTime = 0)
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');

    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];
    $logger->debug(SELF . " " . __FUNCTION__ . " getAgentTaskCommon for:[" . $host . "] taskobjId:[" . $taskobj->id . "].");

    $result['task'] = array();

    //*********************************url不拆分********************************//
//    "taskurl$:\"$<url \"%s\">\"{url:Enum(http://club.autohome.com.cn/bbs/forum-c-3170-1.html,http://club.autohome.com.cn/bbs/forum-c-3170-3.html)}";

    //*********************************url拆分********************************//
//    "taskurl$:\"$<url \"%s\">\"{url:Enum(http://club.autohome.com.cn/bbs/forum-c-3170-1.html)}";
    //*********************************url参数形式********************************//
//    "taskurl$:"$<url "%s" > "{url:Enum(|user.id|)}",

//    \"taskurl$:\\"$<url \\"%s\\" > \\"{url:Enum(|user.id|)}\"
//    "taskurl$:\"$<url \"%s\" > \"{url:Enum(|url|)}";

//    http://club.autohome.com.cn/bbs/forum-c-3170-1.html
//    "taskurl$:\"$<url \"%s\">\"{url:Enum(http://club.autohome.com.cn/bbs/forum-c-3170-1.html)}",
//    "taskurl$:\"$<url \"%s\">\"{url:Enum(http://club.autohome.com.cn/bbs/forum-c-3170-2.html)}",
//    "taskurl$:\"$<url \"%s\">\"{url:Enum(http://club.autohome.com.cn/bbs/forum-c-3170-3.html)}"

    try {
        //taskcontroller.php里面的默认方法查询出来的param,使用的是json_decode("");方法，没有指定true选项
        //将taskparams转化为关联数组表示方式
        if (!empty($taskobj->taskparams)) {
            $taskobj->taskparams = converObjToRelArray($taskobj->taskparams);
        }

        //pathStructMap:中定义了参数 Id=>参数取值表达式结构体，例如在URL中使用参数{iduser:Enum(|user.id|) 其中iduser是参数的"id"，需要使用
        //该id在参数取值路径映射表($pathStructMap)中获取该参数的取值规则定义
        //**************************************************测试代码************************************//
        //$taskobj->taskparams = getTaskParam4Test($taskobj->id, $taskobj->taskparams);
        //**************************************************测试代码*********************************END//
//        $logger->debug(SELF . " " . __FUNCTION__ . " taskParam:[" . var_export($taskobj->taskparams,true). "].");
        $logger->debug(SELF . " " . __FUNCTION__ . " taskParam empt?:[" . empty($taskobj->taskparams) . "].");

        $allParams = $taskobj->taskparams;
        //获取跟节点的任务定义
        $currentTaskParam = $allParams["root"];

        //**************************************************测试代码*********************************END//
//        $pathStructMap = getpathStructMap();
        //$pathStructMap = getpathStructMap4_tm();
        //$currentTaskParam["pathStructMap"] = &$pathStructMap;
        //**************************************************测试代码*********************************END//


        $logger->debug(SELF . " " . __FUNCTION__ . " currentTaskParam:[" . var_export($currentTaskParam, true) . "].");

        //设置开始时间
        $taskobj->starttime = time();
        $taskobj->datastatus = 0;
        $taskobj->machine = $host;
        $taskobj->timeout = time() + $currentTaskParam["taskPro"]["duration"];// $taskobj->taskparams->duration;

        if (empty($currentTaskParam["runTimeParam"])) {
            $currentTaskParam["runTimeParam"] = json_decode("", true);
        }

        if (!empty($currentTaskParam["runTimeParam"]["followpost"])) {
            $currentTaskParam["runTimeParam"]["followpost"] = array();
        }

        if (isset($currentTaskParam["runTimeParam"]["scene"])) {
            unset($currentTaskParam["runTimeParam"]["scene"]);
        }
        if (!isset($currentTaskParam["runTimeParam"]["scene"])) {
            $currentTaskParam["runTimeParam"]["scene"] = array();
        }
        $currentTaskParam["runTimeParam"]["scene"]["isremote"] = 1;

        if (!isset($currentTaskParam["runTimeParam"]["scene"]["historystat"])) {
            $currentTaskParam["runTimeParam"]["scene"]["historystat"] = NULL;
        }

        //处理所有账号信息
        $accounts = array();
        $logger->debug(SELF . " load all accounts...");

        if (!empty($currentTaskParam["loginAccounts"]["accounts"])) {
            $logger->debug(SELF . " " . __FUNCTION__ . " load all accounts from config:[" . var_export($currentTaskParam["loginAccounts"]["accounts"], true) . "].");
            foreach ($currentTaskParam["loginAccounts"]["accounts"] as $account) {
                $sql = "select username, password from " . DATABASE_SPIDERACCOUNT . " where id={$account}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $result['result'] = false;
                    $result['msg'] = '查询账号失败';
                    $logger->error(SELF . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError() . " Sql:[" . $sql . "].");
                    echo json_encode($result);
                    exit;
                }
                if ($acnt = $dsql->GetArray($qr)) {
                    $accounts[] = $acnt;
                }
                $dsql->FreeResult($qr);
            }
        }

        $logger->debug(SELF . " " . __FUNCTION__ . " load all accounts success. size:[" . count($accounts) . "] 个帐号信息!");

        if (empty($currentTaskParam["taskPro"]["template"])) {
            $result['result'] = false;
            $result['msg'] = 'task templete id is null,task id:[' . $taskobj->id . "].";
            $logger->error(SELF . " " . __FUNCTION__ . " task templete id is null,task id:[:" . $taskobj->id . "].");
            echo json_encode($result);
            exit;
        }

        //搜索引擎模板
        $config = $currentTaskParam["taskPro"]["template"];
        $logger->debug(SELF . " " . __FUNCTION__ . " load grab templete from dababase by id:{$config}");

        //根据模版Id从数据库中加载模版
        $sql = "select * from " . DATABASE_SPIDERCONFIG . " where id={$config}";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $result['result'] = false;
            $result['msg'] = '查询配置失败';
            $logger->error(SELF . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError());
            echo json_encode($result);
            exit;
        }

        $logger->debug(SELF . " " . __FUNCTION__ . " load template success. templateId:[" . $config . "].");

        if ($conf = $dsql->GetObject($qr)) {
            $eol = (strpos($conf->content, "\r\n") === false) ? "\n" : "\r\n";

            //URL匹配模版
            if (!empty($conf->urlregex)) {
                $urstr = $conf->urlregex;
                $urarr = preg_split("/[\r\n,\s]+/", $urstr);
                $result['task']['urlregex'] = $urarr;
            }

            //正文匹配模版
            if (!empty($conf->detailurlregex)) {
                $durstr = $conf->detailurlregex;
                $durarr = preg_split("/[\r\n,\s]+/", $durstr);
                $result['task']['detailurlregex'] = $durarr;
            }

            //*****************将任务中定义的所有参数设置大Jsconfig抓取模版中********************

            if (!empty($currentTaskParam["constants"])) {
                $paramDefList = $currentTaskParam["constants"];
                $constantsString = "#CONST:{";
                foreach ($paramDefList as $paramDef => $paramValue) {
//                $paramName = $paramDef["paramName"];
//                $paramValue = $paramDef["value"];
                    $logger->debug(SELF . " " . __FUNCTION__ . " add constants param,paramName:[" . $paramDef . "] value:[" . $paramValue . "].");

                    $constantsString = $constantsString . "\"" . $paramDef . "\"" . ":";
                    if (gettype($paramValue) == "integer" || gettype($paramValue) == "double") {
                        $constantsString = $constantsString . $paramValue;
                    } else if (gettype($paramValue) == "string") {
                        $constantsString = $constantsString . "\"" . $paramValue . "\"";
                    } else {
                        $logger->error(SELF . " " . __FUNCTION__ . " Illegal dataType for constants:[" . $paramDef . "] dataType:[" . gettype($paramValue) . "].");
//                    throw new Exception("Illegal dataType for constants:[".$paramName."] dataType:[".$paramDef["dataType"]."].");
                    }
                    $constantsString = $constantsString . ",";
                }

                if (endWith($constantsString, ",")) {
                    $constantsString = substr($constantsString, 0, strlen($constantsString) - 1);
                }
                $constantsString = $constantsString . "}" . $eol;
                $logger->info(SELF . " " . __FUNCTION__ . " constants:[" . $constantsString . "].");

                //*****************将任务中定义的所有参数设置大Jsconfig抓取模版中********************
                //增加常量
                // $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed}}".$eol;
                $result['task']['config'] = $constantsString;
            } else {
                $result['task']['config'] = "";
            }


            //****************************************测试代码****************************************//
//			$result['task']['config'] .= "\n".json_encode($filters);

            //拼接模版
            $result['task']['config'] .= $conf->content;

            //考虑一个任务中配置了多个模版文件,设置任务中步骤和所使用的模版之间的映射关系
            $result['task']["templMap"] = json_encode($currentTaskParam["templMap"]);
            $result['task']["stepNumURLPatterns"] = json_encode($currentTaskParam["stepNumURLPatterns"]);

//            $logger->info(SELF . " " . __FUNCTION__ . " allTemp:[" . $result['task']['config'] . "].");

//            //*****************将任务中定义的所有参数设置大Jsconfig抓取模版中********************
//            //设置爬虫任务抓取的地址规则
//            //先判断有没有直接设置好值
//            $taskUrls = $currentTaskParam["taskUrls"];
//
            $urls = getUrlFromTaskParam($currentTaskParam);
            $result['task']['url'] = $urls;

            //设置运行时的参数 run_url,不能在这里设置，这里有可能为Enum(a,b,c)等变量，应该在爬虫提交数据时候,从page_url属性中获取
            //$currentTaskParam["runTimeParam"]["run_url"] = $urls;

            if (!empty($accounts)) {
                $logger->debug(SELF . " " . __FUNCTION__ . " 使用当前帐号:[" . var_export($accounts, true) . "].");

                if (0 == count($accounts)) {
                    $logger->error(SELF . " " . __FUNCTION__ . " 加载到的帐号为空，运行失败.");
                } else {
                    $result['task']['accounts'] = $accounts;
                }
            } else if (!empty($currentTaskParam["loginAccounts"]["globalaccount"])) {

                $logger->debug(SELF . " " . __FUNCTION__ . " 使用全局帐号:[" . var_export($currentTaskParam["loginAccounts"]["globalaccount"], true) . "].");

                //使用全局帐号
                $result['task']['globalaccount'] = 1;
                //获取帐号并返回
                $accountres = getNextSpiderAccount($currentTaskParam["loginAccounts"]["globalaccounts"]);
                if ($accountres['result']) {
                    $result['gaccount'] = $accountres['gaccount'];
                }

                //使用全局帐号的时候的帐号切换策略
                if (!empty($currentTaskParam["loginAccounts"]["isswitch"])) {
                    $result['task']['switchpage'] = $currentTaskParam["loginAccounts"]["switchpage"];
                    $result['task']['switchtime'] = $currentTaskParam["loginAccounts"]["switchtime"];
                }
            }

            //是否需要先推出登录
            if (isset($currentTaskParam["loginAccounts"]["logoutfirst"])) {
                $result['task']['logoutfirst'] = $currentTaskParam["loginAccounts"]["logoutfirst"];
            }

            //设置终止页，现在已经被filter所替代
//			if(!empty($taskobj->taskparams->crawlpage)){
//				$result['task']['crawlpage'] =  $taskobj->taskparams->crawlpage;
//			}

        } else {
            $result['result'] = false;
            $result['msg'] = '无效配置,加载模版失败,模版为空,[' . $config . "].";
            $logger->error(SELF . " " . __FUNCTION__ . " 无效配置,加载模版失败,模版为空,[" . $config . "].");
        }
        //
        $dsql->FreeResult($qr);

        if (!$result['result']) {
            echo json_encode($result);
            exit;
        }
//        $result['task']['contenturl'] = $currentTaskParam["taskPro"]["contenturl"];
        $result['task']['importurl'] = getImportURL();

        //设置传递的参数 -->运行时参数
        if (!empty($currentTaskParam["runTimeParam"])) {
//            $logger->debug(SELF . " " . __FUNCTION__ . " Set runtim paramsDef:[" . var_export($currentTaskParam["runTimeParam"], true) . "].");
            $result['task']["runTimeParam"] = $currentTaskParam["runTimeParam"];
        }
        //设置传递的参数 -->数据定义
        if (!empty($currentTaskParam["paramsDef"])) {
//            $logger->debug(SELF . " " . __FUNCTION__ . " Set paramsDef:[" . var_export($currentTaskParam["paramsDef"], true) . "].");
            $result['task']["paramsDef"] = $currentTaskParam["paramsDef"];
        }
        //设置传递的参数 -->常量
        //常量已经写到了jsconfig文件头中不需要再传递

        //设置传递的参数 -->来自父任务的参数
        if (!empty($currentTaskParam["parentParam"])) {
//            $logger->debug(SELF . " " . __FUNCTION__ . " Set parent param:[" . var_export($currentTaskParam["parentParam"], true) . "].");
            $result['task']["parentParam"] = $currentTaskParam["parentParam"];
        }

        //设置参数取值路径定义
        $result['task']["pathStructMap"] = $currentTaskParam["pathStructMap"];
        //最后更新参数
    } catch (Exception $e) {
        $logger->error(SELF . " " . __FUNCTION__ . " 运行异常:[" + $e->getMessage() . "].");
        exit;
    }
    $result['task']['id'] = $taskobj->id;
    $result['task']['version'] = "NEW-VERSION";

    //更新任务参数
    updateTaskFull($taskobj);
//    $logger->debug(SELF . " - updateTaskFull:[" . json_encode($taskobj)."]\n CUrrentParm:[".var_export($currentTaskParam,true));


    //************************************测试代码*******************************//
    //if ($taskobj->id == 2) {
    //  $result['task']['url'] = "http://club.autohome.com.cn/bbs/forum-c-412-1.html";
//        $result['task']['url'] = "http://club.autohome.com.cn/bbs/thread-o-200054-48292091-1.html";
    //}
//    $result['task']['importurl'] = "http://192.168.0.216:8081/sysadmin/";
    //************************************测试代码*******************************//
//    $logger->info(SELF . " - 成功分配任务" . "from Database:[" . DATABASE_WEIBOINFO . "].");
    $gtEndTime = time();
    $logger->info(SELF . " " . __FUNCTION__ . "从数据库 DBDataName:[" . DATABASE_WEIBOINFO . "]中获取通用任务成功,耗时:[" . ($gtEndTime - $gtStartTime) . "] s.");
    echo json_encode($result);
    exit;
}

/**
 * 爬虫获取远程任务
 * @param null $exclude
 * @throws Exception
 */
function getAgentTask($exclude = NULL)
{
    global $logger, $dsql;

    $gtStartTime = time();

    $result = array('result' => true, 'msg' => '');
    //GBConfig.txt #TASKTYPE
    $typestr = isset($_POST['tasktype']) ? $_POST['tasktype'] : $_GET['tasktype'];
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];

    //考虑爬虫优先获取指定类型的任务
    $specifiedType = isset($_POST['specifiedtype']) ? $_POST['specifiedtype'] : (isset($_GET['specifiedtype']) ? $_GET['specifiedtype'] : null);

    //考虑爬虫优先获取指定的mac地址 该地址应该由服务端获取
    $specifiedMac = isset($_POST['mac']) ? $_POST['mac'] : (isset($_GET['mac']) ? $_GET['mac'] : null);

    $logger->debug(SELF . " " . __FUNCTION__ . "  getAgentTask begain ... specifiedType:[" . $specifiedType . "] specifiedMac:[" . $specifiedMac . "].");

//    if (isset($specifiedMac)) {
//        @exec("arp -a", $array); //执行arp -a命令，结果放到数组$array中
//        foreach ($array as $value) {
//            //匹配结果放到数组$mac_array
//            if (strpos($value, $_SERVER["REMOTE_ADDR"]) && preg_match("/(:?[0-9A-F]{2}[:-]){5}[0-9A-F]{2}/i", $value, $mac_array)) {
//                $mac = $mac_array[0];
//                break;
//            }
//        }
//        $logger->debug(SELF . " " . __FUNCTION__ . " 服务端获取mac成功:[" . $mac . "] 客户端指定的mac:[" . $specifiedMac . "].");
//
//        if ($specifiedMac != $mac) {
//            $specifiedMac = null;
//            $logger->debug(SELF . " " . __FUNCTION__ . " 服务端获取mac:[" . $mac . "] 与客户端指定的mac:[" . $specifiedMac . "] 不相等,忽略该参数!");
//        }
//    }

    if (!isset($typestr) || !isset($host)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $logger->error(SELF . " " . __FUNCTION__ . " 参数错误");
        echo json_encode($result);
        exit;
    }

    $typearr = json_decode($typestr, true);
    $taskobj = NULL;
    $task = TASK_KEYWORD;
    foreach ($typearr as $onetype) {
        $tasktype = $onetype['tasktype'];
        $task = $onetype['task'];

        $logger->debug(SELF . " " . __FUNCTION__ . " get task for type:[" . $task . "]...");
        if (isset($specifiedType) && isset($specifiedMac)) {
            $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫主机mac与任务分类获取任务, specifiedMac:[" . $specifiedMac . "] specifiedType:[" . $specifiedType . "] DB:[". DATABASE_WEIBOINFO . "]...");

            $taskTypeStartTime = time();
            $taskobj = getRemoteTaskForSpecidTypeAndMac($tasktype, $task, $specifiedMac, $specifiedType, $exclude);
            $taskTypeEndTime = time();
            if (isset($taskobj)) {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫主机mac与任务分类获取任务成功:[" . $taskobj->id . "]. 耗时:[" . ($taskTypeEndTime - $taskTypeStartTime) . "]m.");
                break;
            } else {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫主机mac与任务分类获取任务失败，没有找到任务.");
            }
        }

        //如果客户端设置了mac地址 并且与 http 头信息的mac地址一致 则优先获取当前机子的任务
        if (isset($specifiedMac)) {
            //优先获取当前机器的任务
            $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫主机mac获取任务:[" . $specifiedMac . "] DB:[". DATABASE_WEIBOINFO . "]...");
            $taskTypeStartTime = time();
            $taskobj = getRemoteTaskForSpecidMac($tasktype, $task, $specifiedMac, $exclude);
            $taskTypeEndTime = time();

            if (isset($taskobj)) {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫主机获取任务成功:[" . $taskobj->id . "]. 耗时:[" . ($taskTypeEndTime - $taskTypeStartTime) . "]m.");
                break;
            } else {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫主机获取任务失败，没有找到为该主机指定的任务.");
            }
        }

        if (isset($specifiedType)) {
            //优先获取任务指定类型的任务
            $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫指定的任务分类获取任务:[" . $specifiedType . "] DB:[". DATABASE_WEIBOINFO . "]...");
            $taskTypeStartTime = time();
            $taskobj = getRemoteTaskForSpecidType($tasktype, $task, $specifiedType, $exclude);
            $taskTypeEndTime = time();

            if (isset($taskobj)) {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫指定的任务分类获取任务成功:[" . $taskobj->id . "]. 耗时:[" . ($taskTypeEndTime - $taskTypeStartTime) . "]m.");
                break;
            } else {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据当前爬虫指定的任务分类获取任务失败，没有找到该分类任务.");
            }
        }

        if (!isset($taskobj)) {
            //如果根据指定的类型 或者 mac没有获取到任务 则通过默认方式来获取任务
            $logger->debug(SELF . " " . __FUNCTION__ . " 根据默认策略获取任务,DB:[" . DATABASE_WEIBOINFO . "]...");
            $taskTypeStartTime = time();
            $taskobj = getRemoteTask($tasktype, $task, $exclude);
            $taskTypeEndTime = time();

            if (isset($taskobj)) {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据默认策略获取任务成功:[" . $taskobj->id . "]. 耗时:[" . ($taskTypeEndTime - $taskTypeStartTime) . "]m.");
            } else {
                $logger->debug(SELF . " " . __FUNCTION__ . " 根据默认策略获取任务失败!");
            }
        }

        if (!empty($taskobj)) {
            break;
        }
    }
    if (empty($taskobj)) {
        $logger->warn(SELF . " - 未找到待启动任务，退出");
        echo json_encode($result);
        exit;
    }
    if ($task == TASK_COMMON) {
        $logger->info(SELF . " " . __FUNCTION__ . "从数据库 DBDataName:[" . DATABASE_WEIBOINFO . "]中获取任务成功,任务类型:[" . $task . "] 开始处理...");
        getAgentTaskCommon($taskobj, $gtStartTime);
        exit;
    } else {
        $startTime = time();//当前时间
        $rt = detectConflictTask($taskobj);
        if (!$rt['result']) {
            $result['result'] = false;
            $result['msg'] = '冲突检测失败';
            $logger->error(SELF . "- 冲突检测失败 -" . $rt['msg']);
            echo json_encode($result);
            $logger->error(SELF . " - 任务停止");
            stopTask($taskobj);
            exit;
        } else if (!$rt['continue']) {
            $logger->error(SELF . " - 任务冲突，延迟启动");
            getAgentTask();
            exit;
        }
        $endTime = time();//当前时间
        $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " 冲突检测 用时:[" . ($endTime - $startTime) . "].");
    }

    if (!empty($taskobj->taskparams->followpost)) {
        $taskobj->taskparams->followpost = array();
    }

    if ($task == TASK_REPOST_TREND || $task == TASK_COMMENTS) {
        $taskobj->local = 0;
        if (empty($taskobj->starttime)) {
            $taskobj->starttime = time();
        }
        if ($task == TASK_REPOST_TREND) {
            if (!isset($taskobj->taskparams->phase)) {
                $taskobj->taskparams->phase = 1;
            }
        }
    } else {
        $taskobj->starttime = time();
        if (isset($taskobj->taskparams->scene)) {
            unset($taskobj->taskparams->scene);
        }
        $taskobj->datastatus = 0;
    }
    if (!isset($taskobj->taskparams->scene)) {
        $taskobj->taskparams->scene = (object)array();
    }
    $taskobj->taskparams->scene->isremote = 1;
    $taskobj->machine = $host;
    $taskobj->timeout = time() + $taskobj->taskparams->duration;
    if (!isset($taskobj->taskparams->scene->historystat)) {
        $taskobj->taskparams->scene->historystat = NULL;
    }
    if ($task != TASK_REPOST_TREND && $task != TASK_COMMENTS && $task != TASK_WEIBO) {
        updateTaskFull($taskobj);
    }
    $result['task'] = array();
    if ($task == TASK_KEYWORD || $task == TASK_FRIEND || $task == TASK_WEIBO || $task == TASK_REPOST_TREND || $task == TASK_COMMENTS || $task == TASK_WEBPAGE) {
        $accounts = array();
        if (!empty($taskobj->taskparams->accountid)) {
            foreach ($taskobj->taskparams->accountid as $acntid) {
                $sql = "select username, password from " . DATABASE_SPIDERACCOUNT . " where id={$acntid}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $result['result'] = false;
                    $result['msg'] = '查询账号失败';
                    $logger->error(SELF . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError());
                    echo json_encode($result);
                    exit;
                }
                if ($acnt = $dsql->GetArray($qr)) {
                    $accounts[] = $acnt;
                }
                $dsql->FreeResult($qr);
            }
        }
        if ($task == TASK_REPOST_TREND && $taskobj->taskparams->phase == 2) {
            $config = 5; //处理转发对应的模板
        } else if ($task == TASK_WEBPAGE) {
            switch ($taskobj->taskpagestyletype) {
                case TASK_PAGESTYLE_ARTICLELIST:
                    $config = $taskobj->taskparams->SEtemplate; //搜索引擎模板
                    break;
                case TASK_PAGESTYLE_ARTICLEDETAIL:
                    $config = $taskobj->taskparams->SStemplate;
                    break;
                case TASK_PAGESTYLE_USERDETAIL:
                    $config = $taskobj->taskparams->usertemplate; //搜索引擎模板
                    break;
                default:
                    break;
            }
        } else {
            $config = $taskobj->taskparams->config;
        }
        $sql = "select * from " . DATABASE_SPIDERCONFIG . " where id={$config}";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $result['result'] = false;
            $result['msg'] = '查询配置失败';
            $logger->error(SELF . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError());
            echo json_encode($result);
            exit;
        }
        if ($conf = $dsql->GetObject($qr)) {
            $eol = (strpos($conf->content, "\r\n") === false) ? "\n" : "\r\n";
            if (!empty($conf->urlregex)) {
                $urstr = $conf->urlregex;
                $urarr = preg_split("/[\r\n,\s]+/", $urstr);
                $result['task']['urlregex'] = $urarr;
            }
            if (!empty($conf->detailurlregex)) {
                $durstr = $conf->detailurlregex;
                $durarr = preg_split("/[\r\n,\s]+/", $durstr);
                $result['task']['detailurlregex'] = $durarr;
            }
            if ($task == TASK_KEYWORD) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed}}" . $eol;
                $result['task']['config'] .= $conf->content;
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $dup = empty($taskobj->taskparams->filterdup) ? "&nodup=1" : "";
                    $url = "http://s.weibo.com/weibo/"; //2015-5-24 wb->weibo
                    $sort = "&xsort=time";
                    if (empty($taskobj->taskparams->keywords)) {
                        $keyword = "*";
                        $enums = "";
                    } else {
                        $keyword = "\$<keyword \"%s\">";
                        $words = array();
                        foreach ($taskobj->taskparams->keywords as $word) {
                            $words[] = rawurlencode($word);
                        }
                        $enums = "keyword:Enum(" . implode(",", $words) . ") ";
                    }
                    $username = empty($taskobj->taskparams->username) ? "" : ("&userscope=custom:" . rawurlencode($taskobj->taskparams->username));
                    $step = empty($taskobj->taskparams->step) ? "" : $taskobj->taskparams->step;
                    if (strpos($step, 'h') !== false) {
                        $stepnum = intval($step);
                        $hour = intval(date("H", $taskobj->taskparams->starttime));
                        $shift = ($hour % $stepnum) * 3600;
                        $starttime = date("Y-m-d-H", $taskobj->taskparams->starttime - $shift);
                        $endtime = date("Y-m-d-H", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . $keyword . $sort . $username . "&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour + {$stepnum} - 1 \"%02d\">" . $dup . "{" . $enums . "time:Time({$starttime},{$endtime},{$stepnum}h)}";
                    } else if ($step == '1d') {
                        $starttime = date("Y-m-d", $taskobj->taskparams->starttime);
                        $endtime = date("Y-m-d", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . $keyword . $sort . $username . "&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">" . $dup . "{" . $enums . "time:Time({$starttime},{$endtime},1d)}";
                    } else {
                        $starttime = isset($taskobj->taskparams->starttime) ? date("Y-m-d-H", $taskobj->taskparams->starttime) : "";
                        $endtime = "";
                        if (isset($taskobj->taskparams->endtime)) {
                            //新浪微博2014-04-26 07时~ 2014-04-26 07时搜索的是7点的微博,所以设置的结束时间应该减少一个小时
                            $tmpendtime = $taskobj->taskparams->endtime;
                            if ($tmpendtime % 3600 == 0) {
                                $urlendtime = $tmpendtime - 3600;
                            } else {
                                $urlendtime = $tmpendtime;
                            }
                            $endtime = date("Y-m-d-H", $urlendtime);
                        }
                        if (!empty($enums)) {
                            $enums = "{" . $enums . "}";
                        }
                        $result['task']['url'] = $url . $keyword . $sort . $username . "&timescope=custom:{$starttime}:{$endtime}" . $dup . $enums;
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_FRIEND) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed}}" . $eol;
                $result['task']['config'] .= $conf->content;
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $url = "http://weibo.com/";
                    $result['task']['url'] = $url . "\$<uid \"%s\">/follow{uid:Enum(" . implode(",", $taskobj->taskparams->uids) . ")}";
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_WEIBO) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isseed},\"usertimeline\":1}" . $eol;
                $result['task']['config'] .= $conf->content;
                $res = getGlobalResource($taskobj);
                if ($res['result'] == false) {
                    $logger->error(SELF . " " . __FUNCTION__ . " " . $res['msg']);
                    $logger->info(SELF . " - 重新申请任务");
                    $taskobj->taskstatus = 0;
                    $taskobj->machine = NULL;
                    $taskobj->timeout = NULL;
                    updateTaskFull($taskobj);
                    getAgentTask($taskobj->id);
                    exit;
                }
                $seedusers = getSeedUser($taskobj, true);
                releaseGlobalResource();
                updateTaskFull($taskobj);
                if (empty($seedusers)) {
                    global $needqueue;
                    if (!empty($needqueue)) {
                        $logger->info(SELF . " - 重新申请任务");
                        $taskobj->taskstatus = 0;
                        $taskobj->machine = NULL;
                        $taskobj->timeout = NULL;
                        updateTaskFull($taskobj);
                        getAgentTask($taskobj->id);
                        exit;
                    }
                    $result['result'] = false;
                    $result['msg'] = '未获取到种子用户';
                    $logger->error(SELF . " " . __FUNCTION__ . " 未获取到种子用户");
                    echo json_encode($result);
                    $logger->info(SELF . " - 任务停止");
                    $taskobj->timeout = NULL;
                    stopTask($taskobj);
                    exit;
                }
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $url = "http://s.weibo.com/weibo/";
                    $sort = "&xsort=time";
                    $users = array();
                    foreach ($seedusers as $user) {
                        $users[] = rawurlencode($user['screen_name']);
                    }
                    $step = empty($taskobj->taskparams->step) ? "" : $taskobj->taskparams->step;
                    if (strpos($step, 'h') !== false) {
                        $stepnum = intval($step);
                        $hour = intval(date("H", $taskobj->taskparams->starttime));
                        $shift = ($hour % $stepnum) * 3600;
                        $starttime = date("Y-m-d-H", $taskobj->taskparams->starttime - $shift);
                        $endtime = date("Y-m-d-H", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . "*" . $sort . "&userscope=custom:\$<uname \"%s\">&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">-\$<time.hour + {$stepnum} - 1 \"%02d\">&nodup=1{uname:Enum(" . implode(",", $users) . ") time:Time({$starttime},{$endtime},{$stepnum}h)}";
                    } else if ($step == '1d') {
                        $starttime = date("Y-m-d", $taskobj->taskparams->starttime);
                        $endtime = date("Y-m-d", $taskobj->taskparams->endtime);
                        $result['task']['url'] = $url . "*" . $sort . "&userscope=custom:\$<uname \"%s\">&timescope=custom:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">:\$<time.year>-\$<time.month \"%02d\">-\$<time.day \"%02d\">&nodup=1{uname:Enum(" . implode(",", $users) . ") time:Time({$starttime},{$endtime},1d)}";
                    } else {
                        $starttime = isset($taskobj->taskparams->starttime) ? date("Y-m-d-H", $taskobj->taskparams->starttime) : "";
                        $endtime = "";
                        if (isset($taskobj->taskparams->endtime)) {
                            //新浪微博2014-04-26 07时~ 2014-04-26 07时搜索的是7点的微博,所以设置的结束时间应该减少一个小时
                            $tmpendtime = $taskobj->taskparams->endtime;
                            if ($tmpendtime % 3600 == 0) {
                                $urlendtime = $tmpendtime - 3600;
                            } else {
                                $urlendtime = $tmpendtime;
                            }
                            $endtime = date("Y-m-d-H", $urlendtime);
                        }
                        $result['task']['url'] = $url . "*" . $sort . "&userscope=custom:\$<uname \"%s\">&timescope=custom:{$starttime}:{$endtime}&nodup=1{uname:Enum(" . implode(",", $users) . ")}";
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_REPOST_TREND) {
                $result['task']['config'] = "#CONST:{\"isseed\":{$taskobj->taskparams->isrepostseed},\"repost\":1}" . $eol;
                $result['task']['config'] .= $conf->content;
                $res = getGlobalResource($taskobj);
                if ($res['result'] == false) {
                    $logger->error(SELF . " " . __FUNCTION__ . " " . $res['msg']);
                    $logger->info(SELF . " - 重新申请任务");
                    $taskobj->taskstatus = 0;
                    $taskobj->machine = NULL;
                    $taskobj->timeout = NULL;
                    updateTaskFull($taskobj);
                    getAgentTask($taskobj->id);
                    exit;
                }
                $seedweibo = false;
                switch ($taskobj->taskparams->phase) {
                    case 1:
                        do {
                            $seedweibo = getRepostSeedWeibo($taskobj);
                            if (!empty($seedweibo)) {
                                if ($seedweibo['reposts_count'] == 0) {
                                    $taskobj->taskparams->select_cursor++;
                                    continue;
                                }
                                //根据直接转发数,如果没有direct_reposts_count或值为0,字段说明没有分析过,都需要重新分析,
                                if (!empty($seedweibo['direct_reposts_count'])) {
                                    if (!$taskobj->taskparams->forceupdate) {
                                        $exist_count = getExistsCount($seedweibo);
                                        if ($exist_count === false) {
                                            $logger->error(SELF . " " . __FUNCTION__ . " 查询已存在转发数失败");
                                            $seedweibo = false;
                                            break;
                                        }
                                        //存在数大于转发数说明是有删除的微博,这种情况不重新分析.
                                        //存在风险可能开始没有分析过,但转发都抓取下来入库了, 分析转发轨迹时由于新浪微博转发删除造成exist_count > reposts_count 将不能分析传播轨迹
                                        if ($exist_count >= $seedweibo['reposts_count']) {
                                            $taskobj->taskparams->select_cursor++;
                                            continue;
                                        }
                                    }
                                }
                                if (!isset($taskobj->taskparams->repost)) {
                                    $taskobj->taskparams->repost = array();
                                }
                                $size = count($taskobj->taskparams->repost);
                                if ($size == 0 || $taskobj->taskparams->repost[$size - 1]->orig != $seedweibo['id']) {
                                    $taskobj->taskparams->repost[] = array('orig' => $seedweibo['id'], 'idnum' => 0);
                                }
                                if (!empty($seedweibo['id2url'])) {
                                    $taskobj->taskparams->currorigurl = $seedweibo['id'] . "({$seedweibo['weibourl']})";
                                } else {
                                    $taskobj->taskparams->currorigurl = $seedweibo['weibourl'];
                                }
                            } else {
                                $taskobj->taskparams->currorigurl = "";
                            }
                            break;
                        } while (true);
                        break;
                    case 2:
                        do {
                            $id = getRepostId($taskobj->taskparams->repost[$taskobj->taskparams->origin_cursor]->orig, NULL, $taskobj->taskparams->repost_cursor, $taskobj->id);
                            $seedweibo = false;
                            if ($id !== false) {
                                if (empty($id)) {
                                    $seedweibo = NULL;
                                } else {
                                    $res = getWeiboById($taskobj->taskparams->source, $id, $taskobj->taskparams->isrepostseed);
                                    if ($res['result'] == false) {
                                        if ($res['notext'] == true) {
                                            $seedweibo = NULL;
                                        }
                                    } else {
                                        $seedweibo = $res['weibo'];
                                        if (empty($seedweibo) || $seedweibo['reposts_count'] == 0) {
                                            $seedweibo = NULL;
                                        }
                                    }
                                }
                            }
                            if ($seedweibo === NULL) {
                                $taskobj->taskparams->repost_cursor--;
                                if ($taskobj->taskparams->repost_cursor <= 0) {
                                    if ($taskobj->taskparams->iscalctrend) {
                                        updateRepostTrend($taskobj, $taskobj->taskparams->origin_cursor);
                                    }
                                    $taskobj->taskparams->origin_cursor++;
                                    for (; $taskobj->taskparams->origin_cursor < count($taskobj->taskparams->repost); $taskobj->taskparams->origin_cursor++) {
                                        $taskobj->taskparams->repost_cursor = $taskobj->taskparams->repost[$taskobj->taskparams->origin_cursor]->idnum - 1;
                                        if ($taskobj->taskparams->repost_cursor > 0) {
                                            break;
                                        }
                                    }
                                }
                                if ($taskobj->taskparams->origin_cursor >= count($taskobj->taskparams->repost)) {
                                    break;
                                }
                            } else {
                                break;
                            }
                        } while (true);
                        break;
                    default:
                        break;
                }
                if ($seedweibo === false) {
                    releaseGlobalResource();
                    $result['result'] = false;
                    $result['msg'] = '获取种子微博异常';
                    $logger->error(SELF . " " . __FUNCTION__ . " 获取种子微博异常");
                    echo json_encode($result);
                    $logger->info(SELF . " - 任务停止");
                    $taskobj->timeout = NULL;
                    stopTask($taskobj);
                    exit;
                } else if (empty($seedweibo)) {
                    if ($taskobj->taskparams->phase == 1 && !empty($taskobj->taskparams->repost)) {
                        $donephase1 = true;
                        $cntphase2 = 0;
                        $s_time = microtime_float();
                        foreach ($taskobj->taskparams->repost as $idx => $repost) {
                            if ($repost->idnum > 1) {
                                for ($seqno = 0; $seqno < $repost->idnum; $seqno++) {
                                    $repostid = getRepostId($repost->orig, NULL, $seqno, $taskobj->id);
                                    if (empty($repostid)) {
                                        continue;
                                    }
                                    $res = getWeiboById($taskobj->taskparams->source, $repostid, $taskobj->taskparams->isrepostseed);
                                    if ($res['result'] == false) {
                                        if ($res['notext'] == true) {
                                            delRepostInfo($taskobj->id, $repost->orig, $repostid);
                                            continue;
                                        } else {
                                            $donephase1 = false;
                                            break 2;
                                        }
                                    }
                                    if ($res['weibo']['reposts_count'] > 0) {
                                        $cntphase2++;
                                        if (!isset($origin_cursor)) {
                                            $origin_cursor = $idx;
                                        }
                                    }
                                }
                            }
                        }
                        $e_time = microtime_float();
                        $phasetimediff = $e_time - $s_time;
                        $logger->info(SELF . " - 转换任务阶段花费{$phasetimediff}");
                        releaseGlobalResource();
                        if (!$donephase1) {
                            $logger->info(SELF . " - 任务抓取一级转发阶段未完成");
                            $taskobj->taskstatus = 0;
                            $taskobj->machine = NULL;
                            $taskobj->timeout = NULL;
                            updateTaskFull($taskobj);
                            getAgentTask($taskobj->id);
                            exit;
                        } else if ($cntphase2 > 0) {
                            $taskobj->taskparams->phase = 2;
                            $taskobj->taskparams->repostcount = $cntphase2;
                            $taskobj->taskparams->repostdone = 0;
                            $taskobj->taskparams->origin_cursor = $origin_cursor;
                            $taskobj->taskparams->repost_cursor = $taskobj->taskparams->repost[$origin_cursor]->idnum - 1;
                            $logger->info(SELF . " - 任务进入抓取二级转发阶段");
                            $taskobj->taskstatus = 0;
                            $taskobj->machine = NULL;
                            $taskobj->timeout = NULL;
                            updateTaskFull($taskobj);
                            getAgentTask();
                            exit;
                        }
                    } else {
                        releaseGlobalResource();
                    }
                    $logger->info(SELF . " " . __FUNCTION__ . " 未获取到种子微博");
                    $logger->info(SELF . " - 任务结束");
                    completeTask($taskobj);
                    delRepostInfo($taskobj->id);
                    getAgentTask();
                    exit;
                }
                releaseGlobalResource();
                updateTaskFull($taskobj);
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $weibourl = empty($seedweibo['weibourl']) ? weibomid2Url($seedweibo['userid'], $seedweibo['mid'], $taskobj->taskparams->source) : $seedweibo['weibourl'];
                    $result['task']['url'] = $weibourl . "?type=repost";
                    if (!empty($taskobj->taskparams->page_cursor)) {
                        $skippage = "#SKIP:{\"conds\":[{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.node-type\",\"val\":\"forward_detail\"}]},{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.class\",\"val\":\"feed_repeat\"}]}],\"tgt\":\"action-data\",\"reg\":\"page=\\\\d+\",\"val\":\"page={$taskobj->taskparams->page_cursor}\"}" . $eol;
                        $result['task']['config'] = $skippage . $result['task']['config'];
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_COMMENTS) {
                $result['task']['config'] = "#CONST:{\"isseed\":0,\"comment\":1}" . $eol;
                $result['task']['config'] .= $conf->content;
                $res = getGlobalResource($taskobj);
                if ($res['result'] == false) {
                    $logger->error(SELF . " " . __FUNCTION__ . " " . $res['msg']);
                    $logger->info(SELF . " - 重新申请任务");
                    $taskobj->taskstatus = 0;
                    $taskobj->machine = NULL;
                    $taskobj->timeout = NULL;
                    updateTaskFull($taskobj);
                    getAgentTask($taskobj->id);
                    exit;
                }
                $seedweibo = false;
                do {
                    $seedweibo = getRepostSeedWeibo($taskobj, true);
                    if (!empty($seedweibo)) {
                        if ($seedweibo['comments_count'] == 0) {
                            $taskobj->taskparams->select_cursor++;
                            continue;
                        }
                        //comment_sinceid为空时说明没有分析过评论轨迹
                        if (!empty($seedweibo['comment_sinceid'])) {
                            if (!$taskobj->taskparams->forceupdate) {
                                $exist_count = getCommentsCount($seedweibo);
                                if ($exist_count === false) {
                                    $logger->error(SELF . " " . __FUNCTION__ . " 查询已存在评论数失败");
                                    $seedweibo = false;
                                    break;
                                }
                                if ($exist_count >= $seedweibo['comments_count']) {
                                    $taskobj->taskparams->select_cursor++;
                                    continue;
                                }
                            }
                        }
                        if (!isset($taskobj->taskparams->comment)) {
                            $taskobj->taskparams->comment = array();
                        }
                        $size = count($taskobj->taskparams->comment);
                        if ($size == 0 || $taskobj->taskparams->comment[$size - 1]->orig != $seedweibo['id']) {
                            $tmparr = array();
                            $tmparr['sourceid'] = isset($seedweibo['sourceid']) ? $seedweibo['sourceid'] : NULL;
                            $tmparr['source_host'] = isset($seedweibo['source_host']) ? $seedweibo['source_host'] : NULL;
                            $tmparr['original_url'] = isset($seedweibo['original_url']) ? $seedweibo['original_url'] : NULL;
                            $tmparr['floor'] = isset($seedweibo['floor']) ? $seedweibo['floor'] : NULL;
                            $tmparr['paragraphid'] = isset($seedweibo['paragraphid']) ? $seedweibo['paragraphid'] : NULL;
                            $tmparr['mid'] = isset($seedweibo['mid']) ? $seedweibo['mid'] : NULL;
                            if (isset($seedweibo['comments_count'])) {
                                $tmparr['comments_count'] = $seedweibo['comments_count'];
                            }
                            $tmparr['orig'] = $seedweibo['id'];
                            $tmparr['idnum'] = 0;
                            //$taskobj->taskparams->comment[] = array('orig' => $seedweibo['id'], 'idnum' => 0);
                            $taskobj->taskparams->comment[] = $tmparr;
                        }
                        if (!empty($seedweibo['id2url'])) {
                            $taskobj->taskparams->currorigurl = $seedweibo['id'] . "({$seedweibo['weibourl']})";
                        } else {
                            $taskobj->taskparams->currorigurl = $seedweibo['weibourl'];
                        }
                    } else {
                        $taskobj->taskparams->currorigurl = "";
                    }
                    break;
                } while (true);
                if ($seedweibo === false) {
                    releaseGlobalResource();
                    $result['result'] = false;
                    $result['msg'] = '获取种子微博异常';
                    $logger->error(SELF . " " . __FUNCTION__ . " 获取种子微博异常");
                    echo json_encode($result);
                    $logger->info(SELF . " - 任务停止");
                    $taskobj->timeout = NULL;
                    stopTask($taskobj);
                    exit;
                } else if (empty($seedweibo)) {
                    $depid = findDependTask($taskobj);
                    if ($depid === false) {
                        releaseGlobalResource();
                        $result['result'] = false;
                        $result['msg'] = '获取依赖任务异常';
                        $logger->error(SELF . " " . __FUNCTION__ . " 获取依赖任务异常");
                        echo json_encode($result);
                        $logger->info(SELF . " - 任务停止");
                        $taskobj->timeout = NULL;
                        stopTask($taskobj);
                        exit;
                    } else if ($depid !== 0) {
                        releaseGlobalResource();
                        $logger->info(SELF . " - 任务 数据入库未完成");
                        $taskobj->taskstatus = 0;
                        $taskobj->machine = NULL;
                        $taskobj->timeout = NULL;
                        updateTaskFull($taskobj);
                        getAgentTask($taskobj->id);
                        exit;
                    }
                    if (!empty($taskobj->taskparams->comment)) {
                        for ($orig = 0; $orig < count($taskobj->taskparams->comment); $orig++) {
                            updateCommentTrend($taskobj, $orig);
                        }
                    }
                    releaseGlobalResource();
                    $logger->info(SELF . " " . __FUNCTION__ . " 未获取到种子微博");
                    $logger->info(SELF . " - 任务结束");
                    completeTask($taskobj);
                    getAgentTask();
                    exit;
                }
                releaseGlobalResource();
                updateTaskFull($taskobj);
                if ($taskobj->taskparams->source == WEIBO_SINA) {
                    $weibourl = empty($seedweibo['weibourl']) ? weibomid2Url($seedweibo['userid'], $seedweibo['mid'], $taskobj->taskparams->source) : $seedweibo['weibourl'];
                    $result['task']['url'] = $weibourl . "?type=comment";
                    if (!empty($taskobj->taskparams->page_cursor)) {
                        $skippage = "#SKIP:{\"conds\":[{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.node-type\",\"val\":\"comment_detail\"}]},{\"props\":[{\"nam\":\"nodeName\",\"val\":\"SPAN\"},{\"nam\":\"innerHTML\",\"val\":\"下一页\"},{\"nam\":\"action-type\",\"val\":\"feed_list_page\"},{\"nam\":\"parentNode.parentNode.parentNode.parentNode.class\",\"val\":\"feed_repeat\"}]}],\"tgt\":\"action-data\",\"reg\":\"page=\\\\d+\",\"val\":\"page={$taskobj->taskparams->page_cursor}\"}" . $eol;
                        $result['task']['config'] = $skippage . $result['task']['config'];
                    }
                } else {
                    $result['task']['url'] = "";
                }
            } else if ($task == TASK_WEBPAGE) {
                $texttpl = !empty($taskobj->taskparams->SStemplate) ? $taskobj->taskparams->SStemplate : -1;
                $usertpl = !empty($taskobj->taskparams->usertemplate) ? $taskobj->taskparams->usertemplate : -1;
                $filter = "";
                $createdtimestart = !empty($taskobj->taskparams->createdtimestart) ? $taskobj->taskparams->createdtimestart : -1;
                $createdtimeend = !empty($taskobj->taskparams->createdtimeend) ? $taskobj->taskparams->createdtimeend : -1;
                //#CONST:{"derivetexttpl":-1,"deriveusertpl":-1,"filter_start":1416067200,"filter_end":1416067201}
                if ($createdtimestart != -1 || $createdtimeend != -1) {
                    //$filter .= ',"filter_field":"created_at_ts","filter_field_type":"range"';
                    if ($createdtimestart != -1) {
                        $filter .= ',"filter_start":' . $createdtimestart . '';
                    }
                    if ($createdtimeend != -1) {
                        $filter .= ',"filter_end":' . $createdtimeend . '';
                    }
                } else {
                    $lastrplytimestart = !empty($taskobj->taskparams->lastrplytimestart) ? $taskobj->taskparams->lastrplytimestart : -1;
                    $lastrplytimeend = !empty($taskobj->taskparams->lastrplytimeend) ? $taskobj->taskparams->lastrplytimeend : -1;
                    if ($lastrplytimestart != -1 || $lastrplytimeend != -1) {
                        //$filter .= ',"filter_field":"created_at_ts","filter_field_type":"range"';
                        if ($lastrplytimestart != -1) {
                            $filter .= ',"filter_start":' . $lastrplytimestart . '';
                        }
                        if ($lastrplytimeend != -1) {
                            $filter .= ',"filter_end":' . $lastrplytimeend . '';
                        }
                    }
                }
                $result['task']['config'] = "#CONST:{\"derivetexttpl\":{$texttpl},\"deriveusertpl\":{$usertpl} {$filter}}" . $eol;
                $result['task']['config'] .= $conf->content;
                if (isset($taskobj->taskpagestyletype) && $taskobj->taskpagestyletype == TASK_PAGESTYLE_ARTICLELIST) {
                    $result['task']['url'] = $taskobj->taskparams->listurls;
                } else if (isset($taskobj->taskpagestyletype) && $taskobj->taskpagestyletype == TASK_PAGESTYLE_USERDETAIL) {
                    $urls = $taskobj->taskparams->userurls;
                    $result['task']['url'] = $urls;
                } else if (isset($taskobj->taskpagestyletype) && $taskobj->taskpagestyletype == TASK_PAGESTYLE_ARTICLEDETAIL) {
                    $urls = $taskobj->taskparams->texturls;
                    $result['task']['url'] = $urls;
                }
            }
            if (!empty($accounts)) {
                $result['task']['accounts'] = $accounts;
            } else if (!empty($taskobj->taskparams->globalaccount)) {                //使用全局帐号
                $result['task']['globalaccount'] = 1;
                //获取帐号并返回
                $accountres = getNextSpiderAccount($taskobj->taskparams->source);
                if ($accountres['result']) {
                    $result['gaccount'] = $accountres['gaccount'];
                }
            }
            if (isset($taskobj->taskparams->logoutfirst)) {
                $result['task']['logoutfirst'] = $taskobj->taskparams->logoutfirst;
            }
            if (!empty($taskobj->taskparams->isswitch)) {
                $result['task']['switchpage'] = $taskobj->taskparams->switchpage;
                $result['task']['switchtime'] = $taskobj->taskparams->switchtime;
            }
            if (!empty($taskobj->taskparams->crawlpage)) {
                $result['task']['crawlpage'] = $taskobj->taskparams->crawlpage;
            }
        } else {
            $result['result'] = false;
            $result['msg'] = '无效配置';
            $logger->error(SELF . " " . __FUNCTION__ . " 无效配置");
        }
        $dsql->FreeResult($qr);
        if (!$result['result']) {
            echo json_encode($result);
            exit;
        }
        $result['task']['importurl'] = getImportURL();
    }
    $result['task']['id'] = $taskobj->id;
    $logger->debug(SELF . " - 成功分配任务" . json_encode($result));
    echo json_encode($result);
    exit;
}

function dropAgentTask()
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    $id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];
    $logger->info(SELF . " - 接收到DropTask请求. Hots:[" . $id . "] Host:[" . $host . "].");
    //$logger->info(SELF."模拟 - 成功分配任务".json_encode($result));
    //add by wangcc 测试
    //echo json_encode($result);
    //exit;

    if (!isset($id) || !isset($host)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $logger->error(SELF . " " . __FUNCTION__ . " 参数错误");
        echo json_encode($result);
        exit;
    }
    $taskobj = getTaskById($id);
    if (empty($taskobj)) {
        $result['result'] = false;
        $result['msg'] = '任务不存在';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务不存在");
        echo json_encode($result);
        exit;
    }
    if ($taskobj->machine != $host ||
        ($taskobj->taskstatus != 1 && $taskobj->taskstatus != -1 && $taskobj->taskstatus != 6)
    ) {
        $result['result'] = false;
        $result['msg'] = '任务已失效';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已失效");
        echo json_encode($result);
        exit;
    }
//    $logger->debug(SELF . " - 放弃任务:" . json_encode($taskobj));

    $logger->debug(SELF . " - 放弃任务:" . $taskobj->id . "].");
    if ($taskobj->taskstatus == -1) {
        $taskobj->taskstatus = 2;
        updateTask($taskobj, "machine='{$host}'");
        $result['result'] = false;
        $result['msg'] = '任务已停止';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已停止");
        echo json_encode($result);
        exit;
    }
    $taskobj->taskstatus = 0;
    $taskobj->machine = NULL;
    $taskobj->timeout = NULL;
    $taskobj->endtime = time();

    if (isCommonTask($taskobj)) {
        if (!isset($taskobj->taskparams->root->runTimeParam->scene->dropcount)) {
            $taskobj->taskparams->root->runTimeParam->scene->dropcount = 0;
        }
        $taskobj->taskparams->root->runTimeParam->scene->dropcount++;

        if (isset($taskobj->taskparams->root->runTimeParam->scene->stat)) {
            $taskobj->taskparams->root->runTimeParam->scene->historystat = $taskobj->taskparams->root->runTimeParam->scene->stat;
        }
        unsetVerCodeImg4TaskParam($taskobj->taskparams);
    } else {
        if (!isset($taskobj->taskparams->scene)) {
            $taskobj->taskparams->scene = (object)array();
        }
        if (!isset($taskobj->taskparams->scene->dropcount)) {
            $taskobj->taskparams->scene->dropcount = 0;
        }
        $taskobj->taskparams->scene->dropcount++;
        if (isset($taskobj->taskparams->scene->stat)) {
            $taskobj->taskparams->scene->historystat = $taskobj->taskparams->scene->stat;
        }
        if (isset($taskobj->taskparams->scene->veriimage)) {
            unlink($taskobj->taskparams->scene->veriimage);
            unset($taskobj->taskparams->scene->veriimage);
        }
        if (isset($taskobj->taskparams->scene->vericode)) {
            unset($taskobj->taskparams->scene->vericode);
        }
    }
    updateTaskFull($taskobj, "machine='{$host}'");
    echo json_encode($result);
    exit;
}

function completeAgentTask()
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    $id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];
    $stat = isset($_POST['stat']) ? $_POST['stat'] : $_GET['stat'];

    if (!isset($id) || !isset($host) || !isset($stat)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $logger->error(SELF . " " . __FUNCTION__ . " 参数错误");
        echo json_encode($result);
        exit;
    }
    $taskobj = getTaskById($id);
    $logger->debug(__FILE__ . __LINE__ . " completeAgentTask for taskId:[ " . $id . "].");

    if (empty($taskobj)) {
        $result['result'] = false;
        $result['msg'] = '任务不存在';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务不存在");
        echo json_encode($result);
        exit;
    }
    if ($taskobj->machine != $host ||
        ($taskobj->taskstatus != 1 && $taskobj->taskstatus != -1 && $taskobj->taskstatus != 6)
    ) {
        $result['result'] = false;
        $result['msg'] = '任务已失效';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已失效");
        echo json_encode($result);
        exit;
    }
    if (!isCommonTask($taskobj)) {

        if (!isset($taskobj->taskparams->scene)) {
            $taskobj->taskparams->scene = (object)array();
        }
        if (isset($taskobj->taskparams->scene->veriimage)) {
            unlink($taskobj->taskparams->scene->veriimage);
            unset($taskobj->taskparams->scene->veriimage);
        }
        if (isset($taskobj->taskparams->scene->vericode)) {
            unset($taskobj->taskparams->scene->vericode);
        }

        if (empty($taskobj->taskparams->scene->historystat)) {
            $taskobj->taskparams->scene->historystat = json_decode($stat);
        } else {
            $taskobj->taskparams->scene->historystat = addSpiderStat($taskobj->taskparams->scene->historystat, json_decode($stat));
        }
        $taskobj->taskparams->scene->stat = $taskobj->taskparams->scene->historystat;
    } else {
        unsetVerCodeImg4TaskParam($taskobj->taskparams);

        if (empty($taskobj->taskparams->root->runTimeParam->scene->historystat)) {
            $taskobj->taskparams->root->runTimeParam->scene->historystat = json_decode($stat);
        } else {
            $taskobj->taskparams->root->runTimeParam->scene->historystat = addSpiderStat($taskobj->taskparams->root->runTimeParam->scene->historystat, json_decode($stat));
        }
        $taskobj->taskparams->root->runTimeParam->scene->stat = $taskobj->taskparams->root->runTimeParam->scene->historystat;
    }

    if ($taskobj->taskstatus == -1) {
        $taskobj->taskstatus = 2;
        updateTask($taskobj, "machine='{$host}'");
        $result['result'] = false;
        $result['msg'] = '任务已停止';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已停止");
        echo json_encode($result);
        exit;
    }

//    $logger->debug(__FILE__ . __LINE__ . " taskobj " . var_export($taskobj, true));
    if ($taskobj->task == TASK_REPOST_TREND) {
        $done = false;
        switch ($taskobj->taskparams->phase) {
            case 1:
                $taskobj->taskparams->select_cursor++;
                if ($taskobj->taskparams->select_cursor >= count($taskobj->taskparams->oristatus)) {
                    $done = true;
                    if (!empty($taskobj->taskparams->repost)) {
                        foreach ($taskobj->taskparams->repost as $repost) {
                            if ($repost->idnum > 1) {
                                $done = false;
                                break;
                            }
                        }
                    }
                }
                break;
            case 2:
                $taskobj->taskparams->repostdone++;
                $taskobj->taskparams->repost_cursor--;
                if ($taskobj->taskparams->repostdone >= $taskobj->taskparams->repostcount || $taskobj->taskparams->repost_cursor <= 0) {
                    if ($taskobj->taskparams->iscalctrend) {
                        updateRepostTrend($taskobj, $taskobj->taskparams->origin_cursor);
                    }
                    $taskobj->taskparams->origin_cursor++;
                    for (; $taskobj->taskparams->origin_cursor < count($taskobj->taskparams->repost); $taskobj->taskparams->origin_cursor++) {
                        $taskobj->taskparams->repost_cursor = $taskobj->taskparams->repost[$taskobj->taskparams->origin_cursor]->idnum - 1;
                        if ($taskobj->taskparams->repost_cursor > 0) {
                            break;
                        }
                    }
                }
                if ($taskobj->taskparams->origin_cursor >= count($taskobj->taskparams->repost)) {
                    $done = true;
                }
                break;
            default:
                break;
        }
        unset($taskobj->taskparams->page_cursor);
        if ($done) {
            completeTask($taskobj, "machine='{$host}'");
            $logger->debug(SELF . " - 完成任务" . json_encode($taskobj));
            delRepostInfo($taskobj->id);
        } else {
            $taskobj->taskstatus = 0;
            updateTaskFull($taskobj, "machine='{$host}'");
            $logger->debug(SELF . " - 更新任务" . json_encode($taskobj));
        }
    } else if ($taskobj->task == TASK_COMMENTS) {
        $done = false;
        $taskobj->taskparams->select_cursor++;
        if ($taskobj->taskparams->select_cursor >= count($taskobj->taskparams->oristatus)) {
            $depid = findDependTask($taskobj);
            if ($depid === 0) {
                $done = true;
            }
        }
        unset($taskobj->taskparams->page_cursor);
        if ($done) {
            if ($taskobj->taskparams->iscalctrend) {
                calcTrendPath('comment_trend', $taskobj->taskparams->comment, true, $taskobj);
                /*
                for($orig = 0; $orig < count($taskobj->taskparams->comment); $orig++){
                    updateCommentTrend($taskobj, $orig);
                }
                 */
            }
            completeTask($taskobj, "machine='{$host}'");
            $logger->debug(SELF . " - 完成任务" . json_encode($taskobj));
        } else {
            $taskobj->taskstatus = 0;
            updateTaskFull($taskobj, "machine='{$host}'");
            $logger->debug(SELF . " - 更新任务" . json_encode($taskobj));
        }
    } else if ($taskobj->task == TASK_WEBPAGE) {
        if (!empty($taskobj->taskparams->followpost) && $taskobj->taskparams->iscalctrend) {
            calcTrendPath('comment_trend', $taskobj->taskparams->followpost, false, $taskobj);
            //updateFollowPostTrend($taskobj, $taskobj->taskparams->followpost);
        }
        completeTask($taskobj, "machine='{$host}'");
        $logger->debug(SELF . " - 完成任务" . json_encode($taskobj));
    } else if ($taskobj->task = TASK_COMMON) {
        try {
            $logger->debug(__FILE__ . __LINE__ . " completeAgentTask for common task.task->task:[ " . $taskobj->task . "].");
            //通用任务
            if (!empty($taskobj->taskparams->root->runTimeParam->followpost) && $taskobj->taskparams->root->taskPro->iscalctrend) {
                calcTrendPath('comment_trend', $taskobj->taskparams->root->runTimeParam->followpost, false, $taskobj);
            }
            completeTask($taskobj, "machine='{$host}'");
            $logger->debug(SELF . " - 完成通用抓取任务.id:[" . $taskobj->id . "].");
        } catch (Exception $e) {
            $logger->debug(SELF . " - 完成通用抓取任务异常.id:[" . $taskobj->id . "] errorMsg:[" . $e->getMessage() . "].");
            $r['result'] = false;
            $r['msg'] .= " " . $e->getMessage();
        }
        //********************************测试代码****************************//
        //$taskobj->taskstatus = 3;
        //updateTaskFull($taskobj, "machine='{$host}'");
        //********************************测试代码****************************//
//        $logger->debug(SELF . " - 完成通用抓取任务" . json_encode($taskobj));
    } else {
        completeTask($taskobj, "machine='{$host}'");
        $logger->debug(SELF . " - 完成任务" . json_encode($taskobj));
    }
    echo json_encode($result);
    exit;
}

function terminateAgentTask()
{
    //{"type":"terminated","id":1993,"host":"wangcc:F4-06-69-67-E8-FE:5996","stat":"","ErrorMsg":"xxx","ErrorCode":"","terminateType":"Error|Exception|Faild"}

    global $logger, $dsql;
    $logger->info(__FUNCTION__.__FILE__.__LINE__." here is arrive~~");
    $result = array('result' => true, 'msg' => '');

    //任务Id
    $id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];

    if (empty($id)) {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,taskId can not null!';
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,taskId can not null!");

    }
    if (empty($host)) {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,host can not null!';
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,host can not null!");

    }
    //终止类型 :
    //  Error:当前任务出错：例如404 500 等页面错误，或者通过配置页面上的必须项匹配元素 匹配不到必须元素时候 当前任务错误 人工处理:直接删除
    //        链接失效 连接拒绝 网页打不开
    //  Exception:例如超时异常，对方服务器反应慢 或者 抓取页面时候在规定时间内没有完成或者 没有触发ondocumentComplete时间 导致超时
    //            重试达到做大次数
    //  Faild:关键字段缺失 或者 由于模版配置错误而导致数据校验失败 这种一般是属于业务失败 字段类型、长度校验失败 依赖字段缺失 必须字段缺失等 根据不同网页可能字段不同、字段
    //        数据类型不同
    $termType = isset($_POST['host']) ? $_POST['terminateType'] : $_GET['terminateType'];

    if (!isset($termType)) {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,terminateType can not null!';
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,terminateType can not null!");

    }
    // taskStatus:
    // -1: 等待停止
    // 0:  等待启动
    // 1:  正常
    // 2:  停止
    // 3:
    // 4:  排队中
    // 5:  崩溃
    // 6:  等待验证
    // 7:  挂起

    // 异常状态
    // 8:  Error.
    // 9:  Exception
    // 10: Faild

    $taskstaus = 1;
    if ($termType == 'Error') {
        $taskstaus = 8;
    } else if ($termType == 'Exception') {
        $taskstaus = 9;
    } else if ($termType == 'Faild') {
        $taskstaus = 10;
    } else {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,Invalid terminate type:[{$termType}]!';
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,Invalid terminate type:[{$termType}]!");

    }
    $logger->debug(__FILE__ . __LINE__ . " terminate Task for:[" . $termType . "].");

    // 获取任务ErrorCode
    $errorCode = isset($_POST['ErrorCode']) ? $_POST['ErrorCode'] : $_GET['ErrorCode'];
    if (!isset($errorCode)) {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,ErrorCode can not null!';
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,ErrorCode can not null!");

    }

    $taskobj = getTaskById($id);
    if (empty($taskobj)) {
        $result['result'] = false;
        $result['msg'] = '任务不存在';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务不存在");

    }

    if ($taskobj->machine != $host) {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,任务已失效:host changed!';
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,任务已失效:host changed!");

    }

    if ($taskobj->taskstatus != 1) {
        $result['result'] = false;
        $result['msg'] = 'terminate Task faild,任务状态非法:[' . $taskobj->taskstatus . "].";
        $logger->error(SELF . " " . __FUNCTION__ . " terminate Task faild,任务状态非法:" . $taskobj->taskstatus . "].");

    }
    if (!isset($id) || !isset($host)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $logger->error(SELF . "" . __FUNCTION__ . "参数错误");

    }
    if ($taskobj->taskstatus == -1) {
        $taskobj->taskstatus = 2;
        updateTask($taskobj, "machine='{$host}'");
        $result['result'] = false;
        $result['msg'] = '停止任务成功,任务已停止!';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已停止");

    }

    $errorMsg = isset($_POST['ErrorMsg']) ? $_POST['ErrorMsg'] : $_GET['ErrorMsg'];
    if (!isset($errorMsg)) {
        $errorMsg = "ErrorMsg is null";
    }

    $logger->info(SELF . " " . __FUNCTION__ . " terminate aget task, taskId:[" . $id . "]  ErrorCode" . $errorCode . "] ErrorMsg:[" . $errorMsg . "] terminateType:" . $termType . "].");


    $taskobj->error_code = $errorCode;
    $taskobj->error_msg = $errorMsg;
    $taskobj->taskstatus = $taskstaus;
    $taskobj->endtime = time();
    updateTask4Terminate($taskobj);
    $logger->info(SELF . " " . __FUNCTION__ . " 任务终止成功!");
    echo json_encode($result);
    exit;

    // $dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    // DB_MYSQL("set names 'utf8'");//数据库输出编码
    // DB_MYSQL("$mysql_database");
    // $conn= DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    // DB_MYSQL("set names'utf8'");
    // DB_MYSQL("$mysql_database");//打开数据库
    // $sql = "insert into messageboard (Topic,Content,Enabled,Date) values ('$Topic','$Content','1','2011-01-12')";
    // DB_MYSQL($sql);
    // mysql_close(); //关闭MySQL连接
}

function suspendAgentTask()
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    $id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];

    $suspendtype = "";
    if (isset($_POST['suspendtype'])) {
        $suspendtype = $_POST['suspendtype'];
    } else if (isset($_GET['suspendtype'])) {
        $suspendtype = $_GET['suspendtype'];
    }
    $logger->debug(__FILE__ . __LINE__ . " suspendtype " . var_export($suspendtype, true));
    if (isset($suspendtype) && $suspendtype == "verify") {
        $vimg = isset($_POST['vimg']) ? $_POST['vimg'] : $_GET['vimg'];
    }
    if (!isset($id) || !isset($host)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $logger->error(SELF . " " . __FUNCTION__ . " 参数错误");
        echo json_encode($result);
        exit;
    }
    $taskobj = getTaskById($id);
    if (empty($taskobj)) {
        $result['result'] = false;
        $result['msg'] = '任务不存在';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务不存在");
        echo json_encode($result);
        exit;
    }
    if ($taskobj->machine != $host ||
        ($taskobj->taskstatus != 1 && $taskobj->taskstatus != -1 && $taskobj->taskstatus != 6)
    ) {
        $result['result'] = false;
        $result['msg'] = '任务已失效';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已失效");
        echo json_encode($result);
        exit;
    }
    if ($taskobj->taskstatus == -1) {
        $taskobj->taskstatus = 2;
        updateTask($taskobj, "machine='{$host}'");
        $result['result'] = false;
        $result['msg'] = '任务已停止';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已停止");
        echo json_encode($result);
        exit;
    }
    if (isset($suspendtype) && $suspendtype == "account") {
        $logger->debug(SELF . " - 等待帐号" . json_encode($taskobj));
        //$taskobj->taskstatus = 8; //设置为等待验证状态
        //获取帐号并返回
        $res = getNextSpiderAccount($taskobj->taskparams->source);
        if ($res['result']) {
            $result['gaccount'] = $res['gaccount'];
        }
    } else if (isset($suspendtype) && $suspendtype == "verify") {
        $logger->debug(SELF . " - 等待验证任务" . json_encode($taskobj));
        $taskobj->taskstatus = 6; //设置为等待验证状态

        if (!isCommonTask($taskobj)) {

            if (!isset($taskobj->taskparams->scene)) {
                $taskobj->taskparams->scene = (object)array();
            }
            if (isset($taskobj->taskparams->scene->veriimage)) {
                unlink($taskobj->taskparams->scene->veriimage);
                unset($taskobj->taskparams->scene->veriimage);
            }
            if (isset($taskobj->taskparams->scene->vericode)) {
                unset($taskobj->taskparams->scene->vericode);
            }

            if (isset($vimg)) {
                //把验证码图片放到/verify文件夹下
                $taskobj->taskparams->scene->veriimage = transVeriImage($vimg, $id);
            }

        } else {
            unsetVerCodeImg4TaskParam($taskobj->taskparams);
            if (isset($vimg)) {
                //把验证码图片放到/verify文件夹下
                $taskobj->taskparams->root->runTimeParam->scene->veriimage = transVeriImage($vimg, $id);
            }
        }
    }
    updateTaskFull($taskobj, "machine='{$host}'");
    echo json_encode($result);
    exit;
}

function checkAgentTask()
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    $id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
    $host = isset($_POST['host']) ? $_POST['host'] : $_GET['host'];
    if (!isset($id) || !isset($host)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $logger->error(SELF . " " . __FUNCTION__ . " 参数错误");
        echo json_encode($result);
        exit;
    }
    $taskobj = getTaskById($id);
    if (empty($taskobj)) {
        $result['result'] = false;
        $result['msg'] = '任务不存在';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务不存在");
        echo json_encode($result);
        exit;
    }
    if ($taskobj->machine != $host ||
        ($taskobj->taskstatus != 1 && $taskobj->taskstatus != -1 && $taskobj->taskstatus != 6)
    ) {
        $result['result'] = false;
        $result['msg'] = '任务已失效';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已失效");
        echo json_encode($result);
        exit;
    }
    $logger->debug(SELF . " - 检查待验证任务" . json_encode($taskobj));
    if ($taskobj->taskstatus == -1) {
        $taskobj->taskstatus = 2;
        updateTask($taskobj, "machine='{$host}'");
        $result['result'] = false;
        $result['msg'] = '任务已停止';
        $logger->error(SELF . " " . __FUNCTION__ . " 任务已停止");
        echo json_encode($result);
        exit;
    }
    $result['taskstatus'] = $taskobj->taskstatus;
    if (isset($taskobj->taskparams->scene->vericode)) {
        $result['vericode'] = $taskobj->taskparams->scene->vericode;
    }
    echo json_encode($result);
    exit;
}

function getImportURL()
{
    ////define('SPIDER_IMPORT_URL','http://wangcc:8081/sysadmin/');//爬虫提交数据地址
    $hname = strtolower($_SERVER['HTTP_HOST']);
    $serverPort = getCurrrentSrvPort();
    $importURL = "http://" . $hname . "/sysadmin/";
//    $importURL = $taskobj->taskparams->importurl;
    return $importURL;
    //return SPIDER_IMPORT_URL;
}

function getGlobalResource($taskobj)
{
    global $task, $res_machine, $res_ip, $res_acc;
    $result = array('result' => true, 'msg' => '');
    if (isset($task)) {
        $result['result'] = false;
        $result['msg'] = '内部调用错';
        return $result;
    }
    $task = new Task(null);
    $task->machine = SERVER_MACHINE;
    $task->tasklevel = 2;
    $task->queuetime = time();
    $task->id = $taskobj->id;
    $task->taskparams = $taskobj->taskparams;
    $task->tasksource = $taskobj->taskparams->source;
    getAllConcurrentRes($task, $res_machine, $res_ip, $res_acc);
    if ($task->taskparams->scene->state != SCENE_NORMAL) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        $result['result'] = false;
        $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
    }
    return $result;
}

function releaseGlobalResource()
{
    global $task, $res_machine, $res_ip, $res_acc;
    myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    unset($GLOBALS['task']);
    unset($GLOBALS['res_machine']);
    unset($GLOBALS['res_ip']);
    unset($GLOBALS['res_acc']);
}

function transVeriImage(&$vimg, $id)
{
    global $logger;
    if (empty($vimg)) {
        return NULL;
    }
    $bytes = str_split($vimg, 2);
    if (empty($bytes)) {
        return NULL;
    }
    $sRealPath = realpath('../');
    $dir = $sRealPath . "/verify";
    if (!is_dir($dir)) {
        mkdir($dir, 0777);
    }
    $filename = $dir . "/" . $id . "_" . time() . ".bmp";
    $fp = fopen($filename, 'wb');
    $code = NULL;
    foreach ($bytes as $byte) {
        sscanf($byte, "%02x", $code);
        fprintf($fp, "%c", $code);
    }
    fclose($fp);
    return $filename;
}

function getNextSpiderAccount($source)
{
    global $logger, $dsql;
    $r = array('result' => true, 'msg' => '');
    $sql = "select id,username,password,inuse,sourceid from spideraccount where inuse = (select min(inuse) from spideraccount where sourceid =" . $source . " ) and sourceid = " . $source . " limit 1";
    //$logger->debug(__FUNCTION__ .$sql);
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $r['result'] = false;
        $r['msg'] = '查询账户失败';
        $logger->error(TASKMANAGER . " - getNextSpiderAccount() sqlerror:" . $dsql->GetError());
    } else {
        $res = $dsql->GetArray($qr);
        if (!$res) {
            $r['result'] = false;
            $r['msg'] = '未找到可用帐号';
        } else {
            //需要判断任务状态,
            //考虑把用户放到taskparam中
            $MAX_INT_UNSIGNED = 4294967290; //mysql中 int(10) 无符号的最大值 4294967295
            if ($res['inuse'] + 1 >= $MAX_INT_UNSIGNED) {
                $upsql = "update spideraccount set inuse = 0 where sourceid = " . $res['sourceid'] . "";
                $upqr = $dsql->ExecQuery($upsql);
                if (!$upqr) {
                    $r['result'] = false;
                    $r['msg'] = 'inuse 清0失败!';
                    $logger->debug(__FILE__ . __LINE__ . " sqlerror " . $dsql->GetError());
                }
            }
            $upsqli = "update spideraccount set inuse = inuse+1 where id = " . $res['id'] . "";
            $upqri = $dsql->ExecQuery($upsqli);
            if (!$upqri) {
                $r['result'] = false;
                $r['msg'] = 'inuse 清0失败!';
                $logger->debug(__FILE__ . __LINE__ . " sqlerror " . $dsql->GetError());
            } else {
                $r['gaccount'] = array('username' => $res['username'], 'password' => $res['password']);
            }

        }
    }
    $logger->debug(__FILE__ . __LINE__ . " getnextaccount " . var_export($r, true) . " inuse " . $res['inuse']);
    return $r;
}
