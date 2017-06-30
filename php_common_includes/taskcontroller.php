<?php
include_once 'common.php';
include_once 'commomTaskUtil.php';

//$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
class Task
{
    var $id;
    //modify zhaoqiang
    var $tasktype;
    var $taskpagestyletype;
    var $task;
    var $tasklevel;
    var $local;
    var $remote;
    var $timeout;
    var $activatetime;
    var $conflictdelay;
    var $starttime;
    var $endtime;
    var $taskstatus;
    var $datastatus;
    var $machine;
    var $taskip;
    var $taskaccount;//帐号
    var $taskpwd;//密码
    var $sourcetype;//源类型
    var $modeltype;//内容模版类型
    var $tasksource;//来源 weibo qq 等
    var $taskparams;
    var $remarks;
    var $tenantid;
    var $userid;
    var $queuetime;//排队时间
    var $usetype;//排队时的等待的资源用途：并发、使用
    var $wait_resourcetype;//等待的资源类型
    var $wait_resource;//等待的具体资源
    var $wait_appkey;
//    var $column;  //爬虫字段  子栏目
//    var $column1;  //爬虫字段  父栏目

    function __construct($tp)
    {
        if (!empty($tp)) {
            $this->taskparams = json_decode($tp);
        }
    }
}

//资源
class Resource
{
    var $id;
    var $resourcetype;//类型
    var $resource;
    var $source;//源
    var $sourcetype;
    var $taskcount;//最大并发任务数
    var $usedcount;//最大使用数
    var $status;//状态
    var $changetime;//使用数下次更新时间
    var $appkey;//应用ID
}

/**
 *
 * 获取正在执行的某类任务的最大级别
 * @param $taskcode 任务编号,对应task表的task字段
 */
function getMaxLevel($taskcode)
{
    global $dsql;
    $sql1 = "select max(tasklevel) as maxlevel from task where taskstatus = 0 or taskstatus = 4 and task = {$taskcode}";
    $qr1 = $dsql->ExecQuery($sql1);
    if (!$qr1) {
        throw new Exception("taskcontroller.php - getMaxLevel() sql:{$sql1} - " . $dsql->GetError());
    } else {
        $rs1 = $dsql->GetArray($qr1);
        if (empty($rs1['maxlevel'])) {
            return 1;
        } else {
            return $rs1['maxlevel'];
        }
    }
}

/**
 *
 * 新增任务
 * @param $task
 */
function addTask($task, $canRepeat = false)
{
    global $dsql, $logger;
    $logger->info(SELF . " addTask, task  = addTask");
    $conflictdelay = empty($task->conflictdelay) ? "NULL" : $task->conflictdelay;
    $tenantid = empty($task->tenantid) ? "NULL" : $task->tenantid;
    $userid = empty($task->userid) ? "NULL" : $task->userid;
    $fieldname = array();
    $fieldvalue = array();
    if (!empty($task->taskpagestyletype)) {
        $fieldname[] = "taskpagestyletype";
        $fieldvalue[] = $task->taskpagestyletype;
    }
    $namestr = implode(", ", $fieldname);
    $valuestr = implode(", ", $fieldvalue);
    $sqlnamestr = "";
    $sqlvaluestr = "";
    if ($namestr != "") {
        $sqlnamestr = ", " . $namestr . "";
        $sqlvaluestr = ", " . $valuestr . "";
    }

//    $category = "select * from data_import_category where id =". $task->taskparams->root->taskPro ->submiturl;
//    $qc = $dsql->ExecQuery($category);
//    if (!$qc) {
//        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " qcerror:" . $qc . " - " . $dsql->GetError());
//    } else {
//        while ($rc = $dsql->GetArray($qc)) {
//            $industryid = $rc["industry_id"];
//            $interfacename = $rc["interface_name"];
//        }
//    }
//    $importin = "select * from data_import_industry where id =" . $industryid;
//    $qi = $dsql->ExecQuery($importin);
//    if (!$qi) {
//        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " qierror:" . $qi . " - " . $dsql->GetError());
//    } else {
//        while ($ri = $dsql->GetArray($qi)) {
//            $importserver = $ri["import_server"];
//            $port = $ri["port"];
//        }
//    }
//    $task->taskparams->root->taskPro ->contenturl = "http://".$importserver .":".$port.$interfacename;

    $isInsert = true;
    if (!$canRepeat) {
        //先查询是否存在--不能重复
        //sunjianhui  2017-03-14  相同任务
        //先查询是否存在
        if ($task->task == 20) {
            $sqlsel = "select id from task where task = 20" . " and ( taskstatus = 1 or taskstatus = 0) and taskparams = '" . jsonEncode4DB($task->taskparams) . "'";
        } else {
            $sqlsel = "select id from task where task = " . $task->task . " and taskparams = '" . jsonEncode4DB($task->taskparams) . "'";
        }

        $qrsel = $dsql->ExecQuery($sqlsel);
        if (!$qrsel) {
            $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sqlsel . " error:" . mysql_error());
            return false;
        } else {
            $hasids = array();
            while ($tmpresult = $dsql->GetArray($qrsel, MYSQL_ASSOC)) {
                $hasids[] = $tmpresult['id'];
            }
        }
        if (count($hasids) > 0) {
            //以及存在，不能重复的时候，不需要插入
            $isInsert = false;
        }
    }
    if ($isInsert) {
        $sql = "insert into task(tasktype,task,tasklevel,col1,col,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, tenantid, userid, spcfdmac, taskclassify " . $sqlnamestr . ")";
        $sql = $sql . " values(" . $task->tasktype . "," . $task->task . "," . $task->tasklevel . ",'" . $task->column1 . "','" . $task->column . "'," . $task->local . "," . $task->remote . "," . $task->activatetime . "," . $conflictdelay . ",0,'" . jsonEncode4DB($task->taskparams) . "','" . $task->remarks . "', " . $tenantid . ", " . $userid . ", " . (empty($task->spcfdmac) ? "null" : "'" . $task->spcfdmac . "'") . ", " . (empty($task->taskclassify) ? "null" : "'" . $task->taskclassify . "'") . " " . $sqlvaluestr . ")";
        $logger->debug(__FILE__ . __LINE__ . " 新增的任务sql" . $sql);
        $qr = $dsql->ExecQuery($sql);
        $logger->debug(__FILE__ . __LINE__ . " 新增的任务task" . var_export($task, true));
        if (!$qr) {
            throw new Exception("taskcontroller.php - addTask() sql: {$sql} - " . mysql_error());
        } else {
            $dsql->FreeResult($qr);
            return true;
        }
    } else {
        //爬虫定时任务生成相同任务    sunjianhui  2017-03-14
        if ($task->task == 20) {
            $sql = "insert into task(tasktype,task,tasklevel,col1,col,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, tenantid, userid, spcfdmac, taskclassify " . $sqlnamestr . ")";
            $sql = $sql . " values(" . $task->tasktype . "," . $task->task . "," . $task->tasklevel . ",'" . $task->column1 . "','" . $task->column . "'," . $task->local . "," . $task->remote . "," . time() . "," . $conflictdelay . ",9,'" . jsonEncode4DB($task->taskparams) . "','" . ("和任务id: " . implode(",", $hasids) . "重复  " . date('Y-m-d H:i:s', time()) . $task->remarks) . "', " . $tenantid . ", " . $userid . ", " . (empty($task->spcfdmac) ? "null" : "'" . $task->spcfdmac . "'") . ", " . (empty($task->taskclassify) ? "null" : "'" . $task->taskclassify . "'") . " " . $sqlvaluestr . ")";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                throw new Exception("taskcontroller.php - addTask() sql: {$sql} - " . mysql_error());
            } else {
                $dsql->FreeResult($qr);
            }
        }
        $logger->debug(__FILE__ . __LINE__ . " 新增的" . var_export($task->taskparams, true) . " 和任务id: " . implode(",", $hasids) . "重复");
        return true;
    }
}

/*
 *
 *  把爬虫子任务入库到定时任务中，以实现价格趋势持续抓取
 *
 *	@param $task     任务参数
 *	@param $crontime 定时任务生成时间
 *
 *  @return Boolean
 * */
function addCrawlerTaskSchedule($task, $canRepeat = false, $crontime)
{
    global $dsql, $logger;
    $logger->info(__FUNCTION__ . __LINE__ . "the begin id:" . var_export($task, true));
    $logger->info(__FUNCTION__ . __LINE__ . "the param is:" . var_export($task->taskparams, true));
    $conflictdelay = empty($task->conflictdelay) ? "NULL" : $task->conflictdelay;
    $tenantid = empty($task->tenantid) ? "NULL" : $task->tenantid;
    $userid = empty($task->userid) ? "NULL" : $task->userid;
    $fieldname = array();
    $fieldvalue = array();


    $logger->info(__FUNCTION__ . __LINE__ . "the crontabtime is:" . var_export($crontime, true));
    if (!empty($task->taskpagestyletype)) {
        $fieldname[] = "taskpagestyletype";
        $fieldvalue[] = $task->taskpagestyletype;
    }
    $namestr = implode(", ", $fieldname);
    $valuestr = implode(", ", $fieldvalue);
    $sqlnamestr = "";
    $sqlvaluestr = "";
    if ($namestr != "") {
        $sqlnamestr = ", " . $namestr . "";
        $sqlvaluestr = ", " . $valuestr . "";
    }
    $isInsert = true;
    $ischeck = true;
    $param['taskparams'] = $task->taskparams;
    $param['nodup'] = 0;
    $param['scene']['duplicate'] = 0;
    $param['scene']['taskadded'] = 1;
    $logger->info(__FUNCTION__ . __LINE__ . "the add param is:" . var_export($param, true));
    $logger->info(__FUNCTION__ . __LINE__ . "the add111 param is:" . var_export(jsonEncode4DB($param), true));
    if ($ischeck) {
        //先查询是否存在--不能重复
        //先查询是否存在
        $sqlsel = "select id from taskschedule where task = " . $task->task . " and params = '" . jsonEncode4DB($param) . "'";
        $logger->info(__FUNCTION__ . __FILE__ . __LINE__ . "the sqlsel111 is:" . var_export($sqlsel, true));
        $qrsel = $dsql->ExecQuery($sqlsel);
        if (!$qrsel) {
            $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sqlsel . " error:" . mysql_error());
            return false;
        } else {
            $hasids = array();
            while ($tmpresult = $dsql->GetArray($qrsel, MYSQL_ASSOC)) {
                $hasids[] = $tmpresult['id'];
            }
        }
        if (count($hasids) > 0) {
            $logger->info("已经存在了哦");
            //以及存在，不能重复的时候，不需要插入
            $isInsert = false;
        }
    }
    $params1111 = jsonEncode4DB($param);
    $logger->info(__FUNCTION__ . __LINE__ . "the jsondecode param is:" . var_export($params1111, true));
    $logger->info(__FUNCTION__ . __FILE__ . __LINE__ . "the crontime222 is:" . var_export($crontime, true));
    if ($isInsert) {
        $sql = "insert into taskschedule(tasktype,task,tasklevel,local,remote,conflictdelay,status,params,crontime,remarks, tenantid, userid, spcfdmac, taskclassify " . $sqlnamestr . ",crawler_status)";
        $sql = $sql . " values(" . $task->tasktype . "," . $task->task . "," . $task->tasklevel . "," . $task->local . "," . $task->remote . "," . $conflictdelay . ",1,'" . $params1111 . "','" . $crontime . "','" . $task->remarks . "', " . $tenantid . ", " . $userid . ", " . (empty($task->spcfdmac) ? "null" : "'" . $task->spcfdmac . "'") . ", " . (empty($task->taskclassify) ? "null" : "'" . $task->taskclassify . "'") . " " . $sqlvaluestr . ",1)";
        $logger->debug(__FILE__ . __LINE__ . " 新增的任务sql" . $sql);
        $qr = $dsql->ExecQuery($sql);
        $logger->debug(__FILE__ . __LINE__ . " 新增的任务task" . var_export($task, true));
        if (!$qr) {
            $logger->info("false");
            throw new Exception("taskcontroller.php - addTask() sql: {$sql} - " . mysql_error());
        } else {
            $logger->info("true");
            $dsql->FreeResult($qr);
            return true;
        }
    } else {
        $logger->debug(__FILE__ . __LINE__ . " 新增的" . var_export($task->taskparams, true) . " 和任务id: " . implode(",", $hasids) . "重复");
        return true;
    }

}

function getTaskById($id)
{
    global $dsql;
    $result = null;
    $sql = "select * from task  where id=" . $id;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getTaskById() - " . mysql_error());
    } else {
        $rs = $dsql->GetObject($qr);
        if (!empty($rs)) {
            $result = new Task($rs->taskparams);
            $result->id = $rs->id;
            $result->tasktype = $rs->tasktype;
            $result->taskpagestyletype = $rs->taskpagestyletype;
            $result->task = $rs->task;
            $result->tasklevel = $rs->tasklevel;
            $result->local = $rs->local;
            $result->remote = $rs->remote;
            $result->timeout = $rs->timeout;
            $result->activatetime = $rs->activatetime;
            $result->conflictdelay = $rs->conflictdelay;
            $result->taskstatus = $rs->taskstatus;
            $result->starttime = $rs->starttime;
            $result->endtime = $rs->endtime;
            $result->datastatus = $rs->datastatus;
            $result->remarks = $rs->remarks;
            $result->machine = $rs->machine;

            //add by wangcc
            $result->spcfdmac = $rs->spcfdmac;
            $result->taskclassify = $rs->taskclassify;
        }
        $dsql->FreeResult($qr);
    }
    return $result;
}

function getTaskStatus($id)
{
    global $dsql, $task;
    $sql = "select taskstatus from {$dsql->dbName}.task where id={$id}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getTaskStatus() - " . mysql_error());
    } else {
        $rs = $dsql->GetObject($qr);
        if (empty($rs)) {
            throw new Exception('task not exist,sql:[' . $sql . "] DBHost:[" . $dsql->dbHost . "] DBName:[" . $dsql->dbName . "] result:{$rs}");
        }
        $dsql->FreeResult($qr);
        if ($rs->taskstatus == -1) {//人工停止
            $task->taskparams->scene->manual_shutdowncount++;
        }
        return $rs->taskstatus;
    }
}

/*获取等待启动的（状态0）任务
 tasktype：任务类型
 task：具体任务
 */
function getWaitingTask($tasktype, $task)
{
    $result = null;
    global $logger;
    //tasktype=1表示分析任务，task=3 表示"重新导入"任务
    $sql = "select * from task  where tasktype = " . $tasktype . " and task = " . $task . " and taskstatus = 0 and local = 1 and activatetime <= " . time() . " order by tasklevel desc limit 0,1";
    $logger->debug(__FILE__ . __LINE__ . " mywait " . var_export($sql, true));
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getWaitingTask() - " . mysql_error());
    } else {
        if (mysql_num_rows($qr) > 0) {
            $rs = mysql_fetch_object($qr);
            $result = new Task($rs->taskparams);
            $result->id = $rs->id;
            $result->tasktype = $rs->tasktype;
            $result->taskpagestyletype = $rs->taskpagestyletype;
            $result->task = $rs->task;
            $result->tasklevel = $rs->tasklevel;
            $result->local = $rs->local;
            $result->remote = $rs->remote;
            $result->timeout = $rs->timeout;
            $result->activatetime = $rs->activatetime;
            $result->conflictdelay = $rs->conflictdelay;
            $result->taskstatus = $rs->taskstatus;
            $result->starttime = $rs->starttime;
            $result->endtime = $rs->endtime;
            //modify zhaoqiang
            //$result->taskparams = $rs->taskparams;
            $result->datastatus = $rs->datastatus;
            $result->remarks = $rs->remarks;
        }
    }
    closeMysql();
    return $result;
}

/*获取无资源需求的本地任务
 tasktype：任务类型
 task：具体任务
 */
function getLocalTask($tasktype, $task)
{
    global $dsql, $logger;
    $result = null;
    $excludestr = empty($exclude) ? "" : " and id != {$exclude}";
    $sqls = "select * from task  where tasktype = " . $tasktype . " and task = " . $task . " and local = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " order by id asc limit 0,1";
    $qrs = null;
    $qru = null;
    do {
        $qrs = $dsql->ExecQuery($sqls);
        if (!$qrs) {
            throw new Exception("taskcontroller.php - getLocalTask() - " . mysql_error());
            break;
        }
        if ($dsql->GetTotalRow($qrs) > 0) {
            $rs = $dsql->GetObject($qrs);
            $sqlu = "update task set taskstatus=1 where id={$rs->id} and taskstatus=0";
            $qru = $dsql->ExecQuery($sqlu);
            if (!$qru) {
                throw new Exception("taskcontroller.php - getLocalTask() - " . mysql_error());
                break;
            }
            if ($dsql->GetAffectedRows() > 0) {
                $sql = "select * from task where id={$rs->id}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    throw new Exception("taskcontroller.php - getRemoteTask() - " . mysql_error());
                    break;
                }
                if ($dsql->GetTotalRow($qr) > 0) {
                    $rs = $dsql->GetObject($qr);
                    $result = new Task($rs->taskparams);
                    $result->id = $rs->id;
                    $result->tasktype = $rs->tasktype;
                    $result->taskpagestyletype = $rs->taskpagestyletype;
                    $result->task = $rs->task;
                    $result->tasklevel = $rs->tasklevel;
                    $result->local = $rs->local;
                    $result->remote = $rs->remote;
                    $result->timeout = $rs->timeout;
                    $result->activatetime = $rs->activatetime;
                    $result->conflictdelay = $rs->conflictdelay;
                    $result->taskstatus = $rs->taskstatus;
                    $result->starttime = $rs->starttime;
                    $result->endtime = $rs->endtime;
                    $result->datastatus = $rs->datastatus;
                    $result->remarks = $rs->remarks;
                    $result->tenantid = $rs->tenantid;
                    $result->userid = $rs->userid;
                    $dsql->FreeResult($qr);
                    break;
                } else {
                    // should never happen, just return null
                    break;
                }
            } else {
                // taken by others, try again
                $dsql->FreeResult($qrs);
                continue;
            }
        }
        break;
    } while (true);
    if ($qrs) {
        $dsql->FreeResult($qrs);
    }
    return $result;
}

/*获取远程任务
 tasktype：任务类型
 task：具体任务
 exclude: 排除任务ID
 */
function getRemoteTask($tasktype, $task, $exclude = NULL)
{
    global $dsql, $logger;
    $result = null;
    $excludestr = empty($exclude) ? "" : " and id != {$exclude}";
    $sqls0 = "select * from task  where tasktype = " . $tasktype . " and taskclassify is null " . " and spcfdmac is null " . " and task = " . $task . " and remote = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " limit 0,1";
    $sqls1 = "select * from task  where tasktype = " . $tasktype . " and taskclassify is null " . " and spcfdmac is null " . " and task = " . $task . " and remote = 1 and taskstatus = 1 and timeout is NOT NULL and timeout < " . time() . $excludestr . " limit 0,1";
    $qrs = null;
    $qru = null;
    do {
        $qrs = $dsql->ExecQuery($sqls0);
        $logger->debug(SELF . " " . __FUNCTION__ . " 获取默认远程任务: execute sql:[" . $sqls0 . "]...");
        if (!$qrs) {
            throw new Exception("taskcontroller.php - getRemoteTask() - " . mysql_error());
            break;
        }
        if ($dsql->GetTotalRow($qrs) > 0) {
            $rs = $dsql->GetObject($qrs);
            $sqlu0 = "update task set taskstatus=1 where id={$rs->id} and taskstatus=0";
            $qru = $dsql->ExecQuery($sqlu0);
            if (!$qru) {
                throw new Exception("taskcontroller.php - getRemoteTask() - " . mysql_error());
                break;
            }
            if ($dsql->GetAffectedRows() > 0) {
                $sql = "select * from task where id={$rs->id}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    throw new Exception("taskcontroller.php - getRemoteTask() - " . mysql_error());
                    break;
                }
                if ($dsql->GetTotalRow($qr) > 0) {
                    $rs = $dsql->GetObject($qr);
                    $result = new Task($rs->taskparams);
                    $result->id = $rs->id;
                    $result->tasktype = $rs->tasktype;
                    $result->task = $rs->task;
                    $result->taskpagestyletype = $rs->taskpagestyletype;
                    $result->tasklevel = $rs->tasklevel;
                    $result->local = $rs->local;
                    $result->remote = $rs->remote;
                    $result->timeout = $rs->timeout;
                    $result->activatetime = $rs->activatetime;
                    $result->conflictdelay = $rs->conflictdelay;
                    $result->taskstatus = $rs->taskstatus;
                    $result->starttime = $rs->starttime;
                    $result->endtime = $rs->endtime;
                    $result->datastatus = $rs->datastatus;
                    $result->remarks = $rs->remarks;
                    $result->tenantid = $rs->tenantid;
                    $result->userid = $rs->userid;
                    $dsql->FreeResult($qr);
                    break;
                } else {
                    // should never happen, just return null
                    break;
                }
            } else {
                // taken by others, try again
                $dsql->FreeResult($qrs);
                continue;
            }
        } else {
            // try expired task
            $logger->debug(SELF . " " . __FUNCTION__ . " 获取默认远程任务数为:[0],重置超时任务! SQL:[" . $sqls1 . "].");
            $qrs = $dsql->ExecQuery($sqls1);
            if (!$qrs) {
                throw new Exception("taskcontroller.php - getRemoteTask() - " . mysql_error());
                break;
            }
            if ($dsql->GetTotalRow($qrs) > 0) {
                $rs = $dsql->GetObject($qrs);
                $sqlu1 = "update task set taskstatus=0,timeout=NULL where id={$rs->id} and taskstatus=1";
                $qru = $dsql->ExecQuery($sqlu1);
                if (!$qru) {
                    throw new Exception("taskcontroller.php - getRemoteTask() - " . mysql_error());
                    break;
                }
                // race for the freed task
                $dsql->FreeResult($qrs);
                continue;
            } else {
                // no expired task
                break;
            }
        }
        break;
    } while (true);
    if ($qrs) {
        $dsql->FreeResult($qrs);
    }
    return $result;
}

function getRemoteTaskForSpecidMac($tasktype, $task, $mac, $exclude = NULL)
{
    global $dsql, $logger;
    $result = null;
    $excludestr = empty($exclude) ? "" : " and id != {$exclude}";

//    if (isset($mac) && !isset($specifiedType)) {
    $sqls0 = "select * from task  where tasktype = " . $tasktype . " and taskclassify is null " . " and spcfdmac = '" . $mac . "' and task = " . $task . " and remote = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " limit 0,1";
    $sqls1 = "select * from task  where tasktype = " . $tasktype . " and taskclassify is null " . " and spcfdmac = '" . $mac . "' and task = " . $task . " and remote = 1 and taskstatus = 1 and timeout is NOT NULL and timeout < " . time() . $excludestr . " limit 0,1";
//    } else if (isset($specifiedType) && !isset($mac)) {
//        $sqls0 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac is null " . " and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " , id asc limit 0,1";
//        $sqls1 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac is null " . " and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 1 and timeout is NOT NULL and timeout < " . time() . $excludestr . " , id asc limit 0,1";
//    } else if (isset($specifiedType) && isset($mac)) {
//        $sqls0 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac = " . $mac . "' and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " , id asc limit 0,1";
//        $sqls1 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac = " . $mac . "' and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 1 and timeout is NOT NULL and timeout < " . time() . $excludestr . " , id asc limit 0,1";
//    }

    $qrs = null;
    $qru = null;
    do {
        $qrs = $dsql->ExecQuery($sqls0);
        $logger->debug(SELF . " " . __FUNCTION__ . " getRemoteTaskForSpecidMac execute sql:[" . $sqls0 . "]...");

        if (!$qrs) {
            throw new Exception("taskcontroller.php - getRemoteTaskForSpecidMac() - " . mysql_error());
            break;
        }
        if ($dsql->GetTotalRow($qrs) > 0) {
            $rs = $dsql->GetObject($qrs);
            $sqlu0 = "update task set taskstatus=1 where id={$rs->id} and taskstatus=0";
            $qru = $dsql->ExecQuery($sqlu0);
            if (!$qru) {
                throw new Exception("taskcontroller.php - getRemoteTaskForSpecidMac() - " . mysql_error());
                break;
            }
            if ($dsql->GetAffectedRows() > 0) {
                $sql = "select * from task where id={$rs->id}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    throw new Exception("taskcontroller.php - getRemoteTaskForSpecidMac() - " . mysql_error());
                    break;
                }
                if ($dsql->GetTotalRow($qr) > 0) {
                    $rs = $dsql->GetObject($qr);
                    $result = new Task($rs->taskparams);
                    $result->id = $rs->id;
                    $result->tasktype = $rs->tasktype;
                    $result->task = $rs->task;
                    $result->taskpagestyletype = $rs->taskpagestyletype;
                    $result->tasklevel = $rs->tasklevel;
                    $result->local = $rs->local;
                    $result->remote = $rs->remote;
                    $result->timeout = $rs->timeout;
                    $result->activatetime = $rs->activatetime;
                    $result->conflictdelay = $rs->conflictdelay;
                    $result->taskstatus = $rs->taskstatus;
                    $result->starttime = $rs->starttime;
                    $result->endtime = $rs->endtime;
                    $result->datastatus = $rs->datastatus;
                    $result->remarks = $rs->remarks;
                    $result->tenantid = $rs->tenantid;
                    $result->userid = $rs->userid;
                    $dsql->FreeResult($qr);
                    break;
                } else {
                    // should never happen, just return null
                    break;
                }
            } else {
                // taken by others, try again
                $dsql->FreeResult($qrs);
                continue;
            }
        } else {
            // try expired task
            $qrs = $dsql->ExecQuery($sqls1);
            if (!$qrs) {
                throw new Exception("taskcontroller.php - getRemoteTaskForSpecidMac() - " . mysql_error());
                break;
            }
            if ($dsql->GetTotalRow($qrs) > 0) {
                $rs = $dsql->GetObject($qrs);
                $sqlu1 = "update task set taskstatus=0,timeout=NULL where id={$rs->id} and taskstatus=1";
                $qru = $dsql->ExecQuery($sqlu1);
                if (!$qru) {
                    throw new Exception("taskcontroller.php - getRemoteTaskForSpecidMac() - " . mysql_error());
                    break;
                }
                // race for the freed task
                $dsql->FreeResult($qrs);
                continue;
            } else {
                // no expired task
                break;
            }
        }
        break;
    } while (true);
    if ($qrs) {
        $dsql->FreeResult($qrs);
    }
    return $result;
}


function getRemoteTaskForSpecidType($tasktype, $task, $specifiedType, $exclude = NULL)
{
    global $dsql, $logger;
    $result = null;
    $excludestr = empty($exclude) ? "" : " and id != {$exclude}";
    $sqls0 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac is null " . " and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " limit 0,1";
    $sqls1 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac is null " . " and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 1 and timeout is NOT NULL and timeout < " . time() . $excludestr . " limit 0,1";
    $qrs = null;
    $qru = null;
    do {
        //$logger->debug(SELF . " " . __FUNCTION__ . " getRemoteTaskForSpecidType execute sql:[" . $sqls0 . "]...");
        $qrs = $dsql->ExecQuery($sqls0);
        if (!$qrs) {
            throw new Exception("taskcontroller.php - getRemoteTaskForSpecidType() - " . mysql_error());
            break;
        }
        if ($dsql->GetTotalRow($qrs) > 0) {
            $rs = $dsql->GetObject($qrs);
            $sqlu0 = "update task set taskstatus=1 where id={$rs->id} and taskstatus=0";
            $qru = $dsql->ExecQuery($sqlu0);
            if (!$qru) {
                throw new Exception("taskcontroller.php - getRemoteTaskForSpecidType() - " . mysql_error());
                break;
            }
            if ($dsql->GetAffectedRows() > 0) {
                $sql = "select * from task where id={$rs->id}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    throw new Exception("taskcontroller.php - getRemoteTaskForSpecidType() - " . mysql_error());
                    break;
                }
                if ($dsql->GetTotalRow($qr) > 0) {
                    $rs = $dsql->GetObject($qr);
                    $result = new Task($rs->taskparams);
                    $result->id = $rs->id;
                    $result->tasktype = $rs->tasktype;
                    $result->task = $rs->task;
                    $result->taskpagestyletype = $rs->taskpagestyletype;
                    $result->tasklevel = $rs->tasklevel;
                    $result->local = $rs->local;
                    $result->remote = $rs->remote;
                    $result->timeout = $rs->timeout;
                    $result->activatetime = $rs->activatetime;
                    $result->conflictdelay = $rs->conflictdelay;
                    $result->taskstatus = $rs->taskstatus;
                    $result->starttime = $rs->starttime;
                    $result->endtime = $rs->endtime;
                    $result->datastatus = $rs->datastatus;
                    $result->remarks = $rs->remarks;
                    $result->tenantid = $rs->tenantid;
                    $result->userid = $rs->userid;
                    $dsql->FreeResult($qr);
                    break;
                } else {
                    // should never happen, just return null
                    break;
                }
            } else {
                // taken by others, try again
                $dsql->FreeResult($qrs);
                continue;
            }
        } else {
            // try expired task
            $qrs = $dsql->ExecQuery($sqls1);
            if (!$qrs) {
                throw new Exception("taskcontroller.php - getRemoteTaskForSpecidType() - " . mysql_error());
                break;
            }
            if ($dsql->GetTotalRow($qrs) > 0) {
                $rs = $dsql->GetObject($qrs);
                $sqlu1 = "update task set taskstatus=0,timeout=NULL where id={$rs->id} and taskstatus=1";
                $qru = $dsql->ExecQuery($sqlu1);
                if (!$qru) {
                    throw new Exception("taskcontroller.php - getRemoteTaskForSpecidType() - " . mysql_error());
                    break;
                }
                // race for the freed task
                $dsql->FreeResult($qrs);
                continue;
            } else {
                // no expired task
                break;
            }
        }
        break;
    } while (true);
    if ($qrs) {
        $dsql->FreeResult($qrs);
    }
    return $result;
}

function getRemoteTaskForSpecidTypeAndMac($tasktype, $task, $mac, $specifiedType, $exclude = NULL)
{
    global $dsql, $logger;
    $result = null;
    $excludestr = empty($exclude) ? "" : " and id != {$exclude}";
    $sqls0 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac = '" . $mac . "' and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 0 and activatetime <= " . time() . $excludestr . " limit 0,1";
    $sqls1 = "select * from task  where tasktype = " . $tasktype . " and spcfdmac = '" . $mac . "' and taskclassify = '" . $specifiedType . "' and task = " . $task . " and remote = 1 and taskstatus = 1 and timeout is NOT NULL and timeout < " . time() . $excludestr . " limit 0,1";
    $qrs = null;
    $qru = null;
    do {
        $logger->debug(SELF . " " . __FUNCTION__ . " getRemoteTaskForSpecidTypeAndMac execute sql:[" . $sqls0 . "]...");
        $qrs = $dsql->ExecQuery($sqls0);
        if (!$qrs) {
            throw new Exception("taskcontroller.php - getRemoteTaskForSpecidTypeAndMac() - " . mysql_error());
            break;
        }
        if ($dsql->GetTotalRow($qrs) > 0) {
            $rs = $dsql->GetObject($qrs);
            $sqlu0 = "update task set taskstatus=1 where id={$rs->id} and taskstatus=0";
            $qru = $dsql->ExecQuery($sqlu0);
            if (!$qru) {
                throw new Exception("taskcontroller.php - getRemoteTaskForSpecidTypeAndMac() - " . mysql_error());
                break;
            }
            if ($dsql->GetAffectedRows() > 0) {
                $sql = "select * from task where id={$rs->id}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    throw new Exception("taskcontroller.php - getRemoteTaskForSpecidTypeAndMac() - " . mysql_error());
                    break;
                }
                if ($dsql->GetTotalRow($qr) > 0) {
                    $rs = $dsql->GetObject($qr);
                    $result = new Task($rs->taskparams);
                    $result->id = $rs->id;
                    $result->tasktype = $rs->tasktype;
                    $result->task = $rs->task;
                    $result->taskpagestyletype = $rs->taskpagestyletype;
                    $result->tasklevel = $rs->tasklevel;
                    $result->local = $rs->local;
                    $result->remote = $rs->remote;
                    $result->timeout = $rs->timeout;
                    $result->activatetime = $rs->activatetime;
                    $result->conflictdelay = $rs->conflictdelay;
                    $result->taskstatus = $rs->taskstatus;
                    $result->starttime = $rs->starttime;
                    $result->endtime = $rs->endtime;
                    $result->datastatus = $rs->datastatus;
                    $result->remarks = $rs->remarks;
                    $result->tenantid = $rs->tenantid;
                    $result->userid = $rs->userid;
                    $dsql->FreeResult($qr);
                    break;
                } else {
                    // should never happen, just return null
                    break;
                }
            } else {
                // taken by others, try again
                $dsql->FreeResult($qrs);
                continue;
            }
        } else {
            // try expired task
            $qrs = $dsql->ExecQuery($sqls1);
            if (!$qrs) {
                throw new Exception("taskcontroller.php - getRemoteTaskForSpecidTypeAndMac() - " . mysql_error());
                break;
            }
            if ($dsql->GetTotalRow($qrs) > 0) {
                $rs = $dsql->GetObject($qrs);
                $sqlu1 = "update task set taskstatus=0,timeout=NULL where id={$rs->id} and taskstatus=1";
                $qru = $dsql->ExecQuery($sqlu1);
                if (!$qru) {
                    throw new Exception("taskcontroller.php - getRemoteTaskForSpecidTypeAndMac() - " . mysql_error());
                    break;
                }
                // race for the freed task
                $dsql->FreeResult($qrs);
                continue;
            } else {
                // no expired task
                break;
            }
        }
        break;
    } while (true);
    if ($qrs) {
        $dsql->FreeResult($qrs);
    }
    return $result;
}

//更新资源状态
function updateResourceStatus()
{
    global $dsql;
    $t = time();//当前时间
    $ntstr = date('Y-m-d H:00:00', strtotime("+1 hours"));//下一个整点，API整点将访问数清零
    $sqls = "select a.*,b.sourcetype from resourcestatus a inner join source b on a.source=b.id where resourcetype!=1 and (changetime <= " . $t . " or changetime is null)";//查找所有需要更新使用数的资源
    $qr = $dsql->ExecQuery($sqls);
    if (!$qr) {
        throw new Exception("taskcontroller.php - updateResourceStatus() - " . $sqls . " - " . $dsql->GetError());
    } else {
        while ($r = $dsql->GetObject($qr)) {
            $policy = getResourceMaxCount($r);
            if (empty($policy)) {
                continue;
            }
            $sqlu = "update resourcestatus set usedcount ={$policy['maxcount']},changetime=" . strtotime($ntstr) . " where id = {$r->id}";
            $qru = $dsql->ExecQuery($sqlu);
            if (!$qru) {
                throw new Exception("taskcontroller.php - updateResourceStatus() - " . $sqlu . " - " . $dsql->GetError());
            }
        }
        $dsql->FreeResult($qr);
    }
}

//获取某个资源的限制
//返回数组array('maxcount'=>123,'maxtaskcount'=>123);
function getResourceMaxCount($res)
{
    global $dsql;
    if (empty($res)) {
        return false;
    }
    $maxcount = -1;
    $maxtaskcount = -1;

    $presql = "select pd.maxcount,pd.maxtaskcount from policygroup pg inner join policygroupmembers pgm on pg.id=pgm.groupid
          inner join policydetails pd on pg.id=pd.groupid where";
    //首先获取某应用对具体资源的限制,
    $sql = "{$presql} pg.policytype=" . POLICY_TYPE_APP . " and pgm.member = '{$res->appkey}' and
          pd.resource = '{$res->resource}'";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getResourceMaxCount() - " . $sql . " - " . $dsql->GetError());
    } else if ($dsql->GetTotalRow($qr) == 0) {
        //未查询到应用对具体资源的限制时
        //查询当前应用针对对$res->resourcetype（资源类型）的限制
        $sql = "{$presql} pg.policytype=" . POLICY_TYPE_APP . " and pgm.member = '{$res->appkey}' and
          pd.resourcetype = {$res->resourcetype} ";
        $dsql->FreeResult($qr);
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            throw new Exception("taskcontroller.php - getResourceMaxCount() - " . $sql . " - " . $dsql->GetError());
        } else if ($dsql->GetTotalRow($qr) == 0) {
            //查询源对具体资源的限制
            $sql = "{$presql} pg.policytype=" . POLICY_TYPE_SOURCE . " and pgm.member = '{$res->source}' and
                pd.resource = '{$res->resource}'";
            $dsql->FreeResult($qr);
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                throw new Exception("taskcontroller.php - getResourceMaxCount() - " . $sql . " - " . $dsql->GetError());
            } else if ($dsql->GetTotalRow($qr) == 0) {
                //查询源对某类型资源的限制
                $sql = "{$presql} pg.policytype=" . POLICY_TYPE_SOURCE . " and pgm.member = '{$res->source}' and
                    pd.resourcetype = {$res->resourcetype}";
                $dsql->FreeResult($qr);
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    throw new Exception("taskcontroller.php - getResourceMaxCount() - " . $sql . " - " . $dsql->GetError());
                } else if ($dsql->GetTotalRow($qr) == 0) {
                    //查询源类型对某资源的限制
                    $sql = "{$presql} pg.policytype=" . POLICY_TYPE_SOURCETYPE . " and pgm.member = '{$res->sourcetype}' and
                        pd.resource = '{$res->resource}'";
                    $dsql->FreeResult($qr);
                    $qr = $dsql->ExecQuery($sql);
                    if (!$qr) {
                        throw new Exception("taskcontroller.php - getResourceMaxCount() - " . $sql . " - " . $dsql->GetError());
                    } else if ($dsql->GetTotalRow($qr) == 0) {
                        //查询源类型对某资源类型的限制
                        $sql = "{$presql} pg.policytype=" . POLICY_TYPE_SOURCETYPE . " and pgm.member = '{$res->sourcetype}' and
                            pd.resourcetype = {$res->resourcetype}";
                        $dsql->FreeResult($qr);
                        $qr = $dsql->ExecQuery($sql);
                        if (!$qr) {
                            throw new Exception("taskcontroller.php - getResourceMaxCount() - " . $sql . " - " . $dsql->GetError());
                        }
                    }
                }
            }
        }
    }
    $rs = $dsql->GetObject($qr);
    $dsql->FreeResult($qr);
    if (!empty($rs)) {
        $maxcount = $rs->maxcount;
        $maxtaskcount = $rs->maxtaskcount;
    }
    $result = array("maxcount" => $maxcount, "maxtaskcount" => $maxtaskcount);
    return $result;
}

//
//$resourcetype:资源类型
//$usetype:
//
//返回值：resource对象
/**
 *
 * Enter 申请资源
 * @param $usetype 用途，两种：任务并发和抓取
 * @param $resourcetype
 * @param $source 申请抓取资源时，需要指定$source
 * @param $appkey
 * @param $dependence
 * @param $tasklevel
 * @param $queuetime
 * @param $queuemachine
 */
function applyResource($usetype, $resourcetype, $source = NULL, $appkey = NULL, $dependence = NULL, $tasklevel = 1, $queuetime = 0, $queuemachine = NULL)
{
    global $dsql, $logger;
    updateResourceStatus();
    $result = null;
    $sourcewh = "";
    //if($usetype == USETYPE_SPIDER){
    if (!empty($source)) {
        $sourcewh = " and source = {$source} ";
    }
    $machinewh = "";
    if (!empty($queuemachine)) {
        $machinewh = " and machine = '{$queuemachine}'";
    }
    $queuecount = 0;
    $sqlqueue = "select count(0) as cnt from taskqueue where (tasklevel > {$tasklevel} or (tasklevel = {$tasklevel} and queuetime <= {$queuetime})) and resourcetype={$resourcetype} {$sourcewh} and usetype={$usetype} {$machinewh}";
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select taskqueue sqlqueue:" . var_export($sqlqueue, true));
    $qr_queue = $dsql->ExecQuery($sqlqueue);
    if (!$qr_queue) {
        throw new Exception("taskcontroller.php - applyResource() - " . $sqlqueue . " - " . $dsql->GetError());
    } else {
        $r_q = $dsql->GetArray($qr_queue);
        if (!empty($r_q)) {
            $queuecount = $r_q['cnt'];
        }
    }
    $dsql->FreeResult($qr_queue);
    $appwh = empty($appkey) ? "" : " and appkey = '{$appkey}' ";//指定获取某个app的资源
    $dewh = empty($dependence) ? "" : " and dependence = '{$dependence}' ";//IP需要指定依赖机器
    $sql = "select * from resourcestatus where resourcetype={$resourcetype} {$sourcewh} {$appwh} {$dewh}";
    switch ($usetype) {
        case USETYPE_CONCURRENT:
            $sql .= " and taskcount > {$queuecount} and usedcount > 0";//申请并发资源保证其usedcount可用
            break;
        case USETYPE_SPIDER:
            if (empty($source)) {
                return null;
            }
            $sql .= " and usedcount > {$queuecount} and source={$source}";
            break;
        default:
            return null;
            break;
    }

    $sql .= "  and status =1 limit 0,1";
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select resourcestatus sql:" . var_export($sql, true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - applyResource() - " . $sql . " - " . $dsql->GetError());
    } else {
        $result = $dsql->GetObject($qr);
        $dsql->FreeResult($qr);
        if (!empty($result)) {
            $v = "";
            switch ($usetype) {
                case USETYPE_CONCURRENT:
                    $v = "taskcount";
                    $result->taskcount--;
                    break;
                case USETYPE_SPIDER:
                    $v = "usedcount";
                    $result->usedcount--;
                    break;
                default:
                    return null;
                    break;
            }

            $sqlu = "update resourcestatus set {$v} = {$v}-1 where {$v} > 0 and id = {$result->id}";
            $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update resourcestatus sqlu:" . var_export($sqlu, true));
            $qru = $dsql->ExecQuery($sqlu);
            if (!$qru) {
                throw new Exception("taskcontroller.php - applyResource() - " . $sqlu . " - " . $dsql->GetError());
            } else {
                $ar = $dsql->GetAffectedRows();
                $dsql->FreeResult($qru);
                if ($ar < 1) {
                    return null;
                }
            }
        } else {
            return null;
        }
    }
    return $result;
}

//申请指定的资源
//$usetype:用途，两种：任务并发和抓取
//$resourcetype:资源类型
//$source：源
//$appkey:应用ID
//如果可用，则返回id，否则返回false
function applySpecificResource($usetype, $resourcetype, $resource, $source, $appkey, $tasklevel, $queuetime, $dependence = NULL, $queuemachine = NULL)
{
    global $dsql, $logger;
    updateResourceStatus();
    $result = false;
    $v = "";
    $appendwh = "";
    switch ($usetype) {
        case USETYPE_CONCURRENT:
            $v = "taskcount";
            $appendwh = " and usedcount > 0";
            break;
        case USETYPE_SPIDER:
            $v = "usedcount";
            break;
        default:
            return false;
            break;
    }

    $queuecount = 0;
    $wh = "";
    if ($resourcetype == RESOURCE_TYPE_MACHINE) {
        $wh = " and resource = '{$resource}' ";
    } else {
        $wh = " and source = ($source) ";
    }
    $queuemwh = "";
    if (!empty($queuemachine)) {
        $queuemwh = " and machine = '{$queuemachine}'";
    }
    $sqlqueue = "select count(0) as cnt from taskqueue where (tasklevel > {$tasklevel} or (tasklevel = {$tasklevel} and queuetime <= {$queuetime})) and resourcetype={$resourcetype} {$wh} and usetype={$usetype} {$queuemwh}";
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select taskqueue sqlqueue:" . var_export($sqlqueue, true));
    $qr_queue = $dsql->ExecQuery($sqlqueue);
    if (!$qr_queue) {
        throw new Exception("taskcontroller.php - applySpecificResource() - " . $sqlqueue . " - " . $dsql->GetError());
    } else {
        $r_q = $dsql->GetArray($qr_queue);
        if (!empty($r_q)) {
            $queuecount = $r_q['cnt'];
        }
    }
    $dsql->FreeResult($qr_queue);
    $dewh = "";
    if (!empty($dependence)) {
        $dewh = " and dependence='{$dependence}'";
    }
    if ($resourcetype == RESOURCE_TYPE_MACHINE) {
        $sqls = "select id from resourcestatus where {$v} > {$queuecount} and status =1 and
                resourcetype={$resourcetype} and resource='{$resource}'";
    } else {
        $sqls = "select id from resourcestatus where {$v} > {$queuecount} {$appendwh} and status =1 and
                resourcetype={$resourcetype} and resource='{$resource}' and source = {$source} and
                appkey='{$appkey}' {$dewh}";
    }
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select resourcestatus sqls:" . var_export($sqls, true));
    $qrs = $dsql->ExecQuery($sqls);
    if (!$qrs) {
        return false;
    } else {
        $rsc = $dsql->GetObject($qrs);
        $dsql->FreeResult($qrs);
        if (!empty($rsc)) {
            $result = $rsc->id;
            $sql = "update resourcestatus set {$v}={$v}-1 where {$v} > 0 and id=" . $rsc->id;
            /*switch ($resourcetype){
                case RESOURCE_TYPE_MACHINE:
                    $sql = "update resourcestatus set {$v}={$v}-1 where {$v} > 0 and status =1 and
                        resourcetype={$resourcetype} and resource='{$resource}'";
                    break;
                default:
                    $sql = "update resourcestatus set {$v}={$v}-1 where {$v} > 0 and status =1 and
                        resourcetype={$resourcetype} and resource='{$resource}' and source = {$source} and
                        appkey='{$appkey}'";
                    break;
            }*/
            $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update resourcestatus sql:" . var_export($sql, true));
            $qru = $dsql->ExecQuery($sql);
            if (!$qru) {
                throw new Exception("taskcontroller.php - applySpecificResource() - " . $sql . " - " . $dsql->GetError());
            } else {
                $ar = $dsql->GetAffectedRows();
                $dsql->FreeResult($qru);
                if ($ar < 1) {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
    return $result;
}

//将资源的使用数+1，已申请资源，但未使用成功时，调用.
//场景：一个操作需要IP资源和帐号资源，先申请了IP，但帐号未申请到，则需要回滚IP的使用数
function rollbackSpecificResource($id)
{
    global $dsql;
    $sql = "update resourcestatus set usedcount = usedcount + 1 where id ={$id}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - rollbackSpecificResource() - " . $sql . " - " . $dsql->GetError());
    }
    $dsql->FreeResult($qr);
}

/**
 *
 * 检查指定资源是否可用
 * @param $usetype 使用类型, USETYPE_CONCURRENT: 并发类型, USETYPE_SPIDER:抓取
 * @param $resourcetype 资源类型：机器、IP、帐号
 * @param $resource 资源, 资源具体的值
 * @param $appkey appkey
 * @param $tasklevel 任务级别
 * @param $queuetime 排队时间
 * @param $isowned 是否拥有资源
 * @param $dependence 依赖
 * @throws Exception
 */
function checkSpecificResource($usetype, $resourcetype, $resource, $appkey, $tasklevel, $queuetime, $isowned = FALSE, $dependence = NULL)
{
    global $dsql, $logger;
    updateResourceStatus();//先更新资源状态
    $wh = "";
    if (!empty($appkey)) {
        $wh = " and appkey = '{$appkey}'";
    }
    $whd = "";
    if (!empty($dependence)) {
        $whd = " and dependence='{$dependence}'";
    }
    $sql = "select IFNULL(taskcount,0) as tc, IFNULL(usedcount,0) as uc from resourcestatus where resourcetype={$resourcetype} and resource='{$resource}' and status =1 {$wh} {$whd}";
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select resourcestatus sql:" . var_export($sql, true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - checkSpecificResource() - " . $sql . " - " . $dsql->GetError());
    } else {
        $r = $dsql->GetObject($qr);
        $dsql->FreeResult($qr);
        if (!empty($r)) {
            $sqlqueue = "select count(0) as cnt,usetype from taskqueue where (tasklevel > {$tasklevel} or (tasklevel = {$tasklevel} and queuetime < {$queuetime})) and resourcetype={$resourcetype} and resource='{$resource}' {$wh}  group by usetype";
            $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select taskqueue sqlqueue:" . var_export($sqlqueue, true));
            $qrquque = $dsql->ExecQuery($sqlqueue);
            if (!$qrquque) {
                throw new Exception("taskcontroller.php - checkSpecificResource() - " . $sqlqueue . " - " . $dsql->GetError());
            } else {
                $queues = array();
                while ($r_queue = $dsql->GetObject($qrquque)) {
                    $queues[$r_queue->usetype] = $r_queue->cnt;
                }
                $dsql->FreeResult($qrquque);
                $taskcount = $r->tc - (isset($queues[USETYPE_CONCURRENT]) ? $queues[USETYPE_CONCURRENT] : 0);
                $usedcount = $r->uc - (isset($queues[USETYPE_SPIDER]) ? $queues[USETYPE_SPIDER] : 0);
                if ($usetype == USETYPE_CONCURRENT) {//检查并发资源时
                    if ($isowned) {//已占用资源
                        if ($resourcetype == RESOURCE_TYPE_MACHINE) {
                            return $taskcount >= 0;
                        } else {
                            return ($taskcount >= 0 && $usedcount > 0);
                        }
                    } else {
                        if ($resourcetype == RESOURCE_TYPE_MACHINE) {
                            return $taskcount > 0;
                        } else {
                            return ($taskcount > 0 && $usedcount > 0);
                        }
                    }
                } else {//检查使用资源时
                    return $usedcount > 0;
                }
            }
        } else {
            return false;
        }
    }
}

//检查某种类型的资源在某个源上是否可用
//根据任务级别判断是否有用资源
//考虑到排队情况，如果存在比当前任务级别高且在排队同样资源的任务，给其预留资源
//$tasklevel:任务级别
//$queuetime：排队时间
//$appKeyList: 指定检查某些特定app的资源
//$isowned:是否已拥有该类型的资源
//返回可用的app数组
function checkResource($resourcetype, $source, $dependence = NULL, $tasklevel, $queuetime, $appkeyList = NULL, $isowned = FALSE)
{
    global $dsql;
    updateResourceStatus();//先更新资源状态
    $result = NULL;
    $wh = "";
    if (!empty($appkeyList)) {
        $as = "'" . implode("','", $appkeyList) . "'";
        $wh = " and appkey in ({$as})";
    }
    if (!empty($dependence)) {
        $wh .= " and dependence = '{$dependence}'";
    }
    $sql = "select sum(IFNULL(taskcount,0)) as tc, sum(IFNULL(usedcount,0)) as uc,appkey from resourcestatus where
        resourcetype={$resourcetype} and source={$source} and status =1 {$wh} group by appkey";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - checkResource() - " . $sql . " - " . $dsql->GetError());
    } else {
        $sqlqueue = "select count(0) as cnt, usetype from taskqueue where
            (tasklevel > {$tasklevel} or (tasklevel = {$tasklevel} and queuetime < {$queuetime}))
            and resourcetype={$resourcetype} and source={$source} group by usetype";
        $qrquque = $dsql->ExecQuery($sqlqueue);
        if (!$qrquque) {
            throw new Exception("taskcontroller.php - checkResource() - " . $sqlqueue . " - " . $dsql->GetError());
        } else {
            $queues = array();
            while ($r_queue = $dsql->GetObject($qrquque)) {
                $queues[$r_queue->usetype] = $r_queue->cnt;
            }
        }
        $dsql->FreeResult($qrquque);
        $vkeys = array();
        while ($r = $dsql->GetObject($qr)) {
            $taskcount = $r->tc - (isset($queues[USETYPE_CONCURRENT]) ? $queues[USETYPE_CONCURRENT] : 0);
            $usedcount = $r->uc - (isset($queues[USETYPE_SPIDER]) ? $queues[USETYPE_SPIDER] : 0);
            if ($isowned) {//当前任务已拥有该类型资源
                if ($taskcount >= 0 && $usedcount > 0) {
                    $vkeys[] = $r->appkey;//符合条件的appkey
                }
            } else {
                if ($taskcount > 0 && $usedcount > 0) {
                    $vkeys[] = $r->appkey;//符合条件的appkey
                }
            }
        }
        $dsql->FreeResult($qr);
        if (count($vkeys) > 0) {
            $result = $vkeys;//返回有资源的app
        }
    }
    return $result;
}

//禁用资源，API返回资源不可使用时，调用。只禁用资源的使用，不禁用并发
function disableResource($id)
{
    global $dsql;
    $sql = "update resourcestatus set usedcount = 0 where usedcount > 0 and changetime > " . time() . " and id={$id}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        return false;
    }
    $dsql->FreeResult($qr);
}

//释放资源，将任务数加1
//$usetype:资源用途
function releaseResource($id)
{
    if (empty($id)) {
        return false;
    }
    $sql = "update resourcestatus set taskcount=taskcount+1 where id={$id}";
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - releaseResource() - " . $sql . " - " . mysql_error());
    }
    //$dsql->FreeResult($qr);
}

function getResourceById($id)
{
    global $dsql;
    $sql = "select * from resourcestatus where id = {$id}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getResourceById() - " . $sql . " - " . mysql_error());
    } else {
        $r = $dsql->GetObject($qr);
        $dsql->FreeResult($qr);
        return $r;
    }
}

/**
 * @return Task
 * @throws Exception
 * @desc    这是获取推送任务参数的
 */
function getdatapush($task)
{
    global $link, $logger;
    $sql = "select * from task where  task = " . $task . "  order by id desc limit 0,1";
    dbconnect();
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select taskqueue sql:" . var_export($sql, true));
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getdatapush() - " . $sql . " - " . mysql_error());
    } else {
        $rs = mysql_fetch_object($qr);
        $result = new Task($rs->taskparams);
        $result->id = $rs->id;
        $result->tasktype = $rs->tasktype;
        $result->taskpagestyletype = $rs->taskpagestyletype;
        $result->task = $rs->task;
        $result->tasklevel = $rs->tasklevel;
        $result->local = $rs->local;
        $result->remote = $rs->remote;
        $result->activatetime = $rs->activatetime;
        $result->conflictdelay = $rs->conflictdelay;
        $result->taskstatus = $rs->taskstatus;
        $result->machine = $rs->machine;
        $result->starttime = $rs->starttime;
        $result->endtime = $rs->endtime;
        //modify zhaoqiang
        $result->taskparams = $rs->taskparams;
        $result->datastatus = $rs->datastatus;
        $result->usetype = $rs->usetype;
        $result->queuetime = $rs->queuetime; //为任务插入队列的时间
        $result->wait_resourcetype = $rs->resourcetype;
        $result->wait_resource = $rs->resource;
        $result->remarks = $rs->remarks;
        $result->tenantid = $rs->tenantid;
        $result->userid = $rs->userid;
    }
    return $result;
}


//获取某机器的排队任务
function getQueueTask($machine, $task = 0)
{
    global $link, $logger;
    $result = false;
    $taskcond = ($task == 0) ? '' : " and t.task={$task}";
    $sql = "select t.*,tq.machine,tq.queuetime,tq.takentime,tq.usetype,tq.resourcetype,tq.resource from taskqueue tq inner join task t on tq.taskid=t.id where t.taskstatus=4 and tq.machine='{$machine}'" . $taskcond . " and tq.takentime + 3600 < " . time() . " order by tq.taskid asc  limit 0,1";
    dbconnect();
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " select taskqueue sql:" . var_export($sql, true));
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - getQueueTask() - " . $sql . " - " . mysql_error());
    } else {
        if (mysql_num_rows($qr) > 0) {
            $rs = mysql_fetch_object($qr);
            if ($rs->machine != $machine || $task != 0 && $rs->task != $task) {//非同一台机器的排队任务，不用处理
                $result = false;
            } else {
                $sqlu = "update taskqueue set takentime=" . time() . " where taskid=" . $rs->id . " and takentime=" . $rs->takentime;
                $qru = mysql_query($sqlu);
                if (!$qru) {
                    throw new Exception("taskcontroller.php - getQueueTask() - " . $sqlu . " - " . mysql_error());
                } else if (mysql_affected_rows($link) == 0) {
                    $result = false;
                } else {
                    $result = new Task($rs->taskparams);
                    $result->id = $rs->id;
                    $result->tasktype = $rs->tasktype;
                    $result->taskpagestyletype = $rs->taskpagestyletype;
                    $result->task = $rs->task;
                    $result->tasklevel = $rs->tasklevel;
                    $result->local = $rs->local;
                    $result->remote = $rs->remote;
                    $result->activatetime = $rs->activatetime;
                    $result->conflictdelay = $rs->conflictdelay;
                    $result->taskstatus = $rs->taskstatus;
                    $result->machine = $rs->machine;
                    $result->starttime = $rs->starttime;
                    $result->endtime = $rs->endtime;
                    //modify zhaoqiang
                    //$result->taskparams = $rs->taskparams;
                    $result->datastatus = $rs->datastatus;
                    $result->usetype = $rs->usetype;
                    $result->queuetime = $rs->queuetime; //为任务插入队列的时间
                    $result->wait_resourcetype = $rs->resourcetype;
                    $result->wait_resource = $rs->resource;
                    $result->remarks = $rs->remarks;
                    $result->tenantid = $rs->tenantid;
                    $result->userid = $rs->userid;
                }
            }
        }
    }
    //closeMysql();
    return $result;
}


//解除排队
function unQueueTask($taskid)
{
    $sql = "delete from taskqueue where taskid=" . $taskid;
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - unQueueTask() - " . $sql . " - " . mysql_error());
    }
    //closeMysql();
}

//将任务排队
function queueTask(&$task)
{
    global $logger;
    try {
        //$sourcetypevalue = empty($task->sourcetype) ? 'null' : $task->sourcetype;
        $sourcevalue = empty($task->tasksource) ? 'null' : $task->tasksource;
        $resource = empty($task->wait_resource) ? '' : $task->wait_resource;
        $appkey = empty($task->wait_appkey) ? '' : $task->wait_appkey;
        $sql = "insert into taskqueue (taskid,tasklevel,resourcetype,resource,source,appkey,machine,queuetime,takentime,usetype) values(" . $task->id . "," . $task->tasklevel . "," . $task->wait_resourcetype . ",'" . $resource . "'," . $sourcevalue . ", '" . $appkey . "','" . $task->machine . "'," . time() . ",0," . $task->usetype . ")";
        $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " insert taskqueue sql:" . var_export($sql, true));
        dbconnect();
        $qr = mysql_query($sql);
        if (!$qr) {
            //test
            $test_err_str = mysql_error();
            $test_err_no = mysql_errno();
            $logger->error(__FUNCTION__ . " sqlerror:" . $test_err_no . " " . $test_err_str);
            if ($test_err_no == 1062) {
                return false;
            }
            throw new Exception("taskcontroller.php - queueTask() - " . $sql . " - " . mysql_error());
        } else {
            $cond = "taskstatus=" . $task->taskstatus;
            $task->taskstatus = 4;
            $task->endtime = time();//date("Y-m-d H:i:s");
            //$task->taskparams->scene->shutdowncount++;
            $rows = updateTask($task, $cond);
            if ($rows == 0) {
                unQueueTask($task->id);
                return false;
            }
        }
        //test
        return true;
    } catch (Exception $ex) {
        $logger->error(__FUNCTION__ . " Exception:" . $ex->getMessage());
        return false;
    }
}

//更新任务信息，只更新已处理条数、时间等信息
function updateTaskInfo($task)
{
    global $dsql, $logger;
    $upstarttime = empty($task->starttime) ? '' : " starttime=" . $task->starttime . ",";
    $upendtime = empty($task->endtime) ? '' : " endtime=" . $task->endtime . ",";
    $uptimeout = empty($task->timeout) ? '' : " timeout=" . $task->timeout . ",";
    //$logger->debug(__FUNCTION__."  ".var_export($task->taskparams,true));
    $tpstr = jsonEncode4DB($task->taskparams);
    $sql = "update task set " . $upstarttime . $upendtime . $uptimeout . " datastatus={$task->datastatus}, taskparams = '" . jsonEncode4DB($task->taskparams) . "' where id=" . $task->id;
//    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update task sql:" . var_export($sql, true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception(__FUNCTION__ . " - " . $sql . " - " . $dsql->GetError());
    }
    $dsql->FreeResult($qr);
}


function updateTask($task, $cond = '')
{
    global $link, $logger;
    $upstarttime = empty($task->starttime) ? '' : " starttime=" . $task->starttime . ",";
    $upendtime = empty($task->endtime) ? '' : " endtime=" . $task->endtime . ",";
    $updatastatus = empty($task->datastatus) ? '' : " datastatus=" . $task->datastatus . ",";
    $uptimeout = " timeout=" . (empty($task->timeout) ? "NULL" : $task->timeout) . ",";
    $cond = empty($cond) ? '' : " and " . $cond;
    $sql = "update task set " . $upstarttime . $upendtime . $updatastatus . $uptimeout . " taskstatus=" . $task->taskstatus . ", taskparams = '" . jsonEncode4DB($task->taskparams) . "', machine='" . $task->machine . "' where id=" . $task->id . $cond;
    dbconnect();
//    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update task sql:" . var_export($sql, true));
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - updateTask() - " . $sql . " - " . mysql_error());
    }
    //closeMysql();
    return mysql_affected_rows($link);
}

//更新异常任务 add by wang.
function updateTask4Terminate($task)
{
    global $link, $logger;
    $upendtime = empty($task->endtime) ? '' : " endtime=" . $task->endtime . ",";
    $updatastatus = empty($task->taskstatus) ? '' : " taskstatus=" . $task->taskstatus . ",";

    $updatasErrorCode = empty($task->error_code) ? '' : " error_code=" . $task->error_code . ",";
    $updatasErrorMsg = empty($task->error_msg) ? '' : "error_msg=" . "'" . "\"" . $task->error_msg . "\"" . "'" . ",";

    $sql = "update task set " . $upendtime . $updatastatus . $updatasErrorMsg . $updatasErrorCode . " machine= " . "'" . $task->machine . "'" . " where id=" . $task->id;
    dbconnect();
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update task sql:" . var_export($sql, true));
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - updateTask() - " . $sql . " - " . mysql_error());
    }
    //closeMysql();
    return mysql_affected_rows($link);
}

// 更新全部字段
function updateTaskFull($task, $cond = '')
{
    global $link;
    $timeout = empty($task->timeout) ? "NULL" : "{$task->timeout}";
    $endtime = empty($task->endtime) ? "NULL" : "{$task->endtime}";
    $datastatus = empty($task->datastatus) ? 0 : $task->datastatus;
    $upmachine = empty($task->machine) ? "machine=NULL" : "machine='{$task->machine}'";
    $cond = empty($cond) ? '' : " and " . $cond;
    $sql = "update task set local=" . $task->local . ", remote=" . $task->remote .
        ", starttime=" . $task->starttime . ", endtime=" . $endtime . ", timeout=" . $timeout .
        ",activatetime=" . $task->activatetime . ", datastatus=" . $datastatus . ", taskstatus=" . $task->taskstatus .
        ", taskparams = '" . jsonEncode4DB($task->taskparams) . "', " . $upmachine . " where id=" . $task->id . $cond;
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - updateTaskFull() - " . $sql . " - " . mysql_error());
    }
    //closeMysql();
    return mysql_affected_rows($link);
}

function updateQueueTask($task)
{
    global $dsql, $logger;
    $resource = empty($task->wait_resource) ? '' : ", resource = '" . $task->wait_resource . "'";
    $appkey = empty($task->wait_appkey) ? '' : ", appkey = '" . $task->wait_appkey . "'";
    $source = empty($task->tasksource) ? '' : ", source = {$task->tasksource}";
    $sql = "update taskqueue set queuetime= " . time() . ", takentime=0, resourcetype={$task->wait_resourcetype} {$resource} {$appkey} {$source} where taskid={$task->id}";
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update taskqueue sql:" . var_export($sql, true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - updateQueueTask() - " . $sql . " - " . $dsql->GetError());
    }
    $dsql->FreeResult($qr);
}

//挂起任务
function hangTask(&$task)
{
    $task->taskstatus = 7;//任务状态修改为挂起状态
    $task->endtime = time();//date("Y-m-d H:i:s");
    $task->taskip = '';
    $task->taskaccount = '';
    $task->taskpwd = '';
    $task->taskparams->scene->hangcount++;//挂起次数
    $task->taskparams->scene->shutdowncount++;//停止次数
    unQueueTask($task->id);
    updateTask($task);
}

//人工停止任务
function stopTask(&$task)
{
    $task->taskstatus = 2;
    $task->endtime = time();//date("Y-m-d H:i:s");
    $task->taskip = '';
    $task->taskaccount = '';
    $task->taskpwd = '';
    $task->taskparams->scene->shutdowncount++;//停止次数
    unQueueTask($task->id);
    updateTask($task);
}

//任务执行完毕
function completeTask(&$task, $cond = '')
{
    global $dsql, $logger;
    $task->taskstatus = 3;
    $task->endtime = time();
    $task->taskip = '';
    $task->taskaccount = '';
    $task->taskpwd = '';
    $conflictdelay = empty($task->conflictdelay) ? "NULL" : $task->conflictdelay;
    $cond = empty($cond) ? '' : " and " . $cond;
    $tenantid = empty($task->tenantid) ? "NULL" : $task->tenantid;
    $userid = empty($task->userid) ? "NULL" : $task->userid;
    $taskpagestyletype = empty($task->taskpagestyletype) ? "NULL" : $task->taskpagestyletype;
    $sql = "insert into taskhistory values(" . $task->id . "," . $task->tasktype . "," . $taskpagestyletype . "," . $task->task . ", " . $task->tasklevel . "," . $task->local . "," . $task->remote . "," . $task->activatetime . "," . $conflictdelay . "," . $task->starttime . "," . $task->endtime . "," . $task->datastatus . ", '" . jsonEncode4DB($task->taskparams) . "','" . $task->remarks . "', '" . $task->machine . "', " . $tenantid . ", " . $userid . ")";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - completeTask() - " . $sql . " - " . $dsql->GetError());
    } else {
        $sqldel = "delete from task where id = " . $task->id . $cond;
        $qr = $dsql->ExecQuery($sqldel);
        if (!$qr) {
            throw new Exception("taskcontroller.php - completeTask() - " . $sqldel . " - " . $dsql->GetError());
        }
        if ($dsql->GetAffectedRows() == 0) {
            $sqldel = "delete from taskhistory where id = " . $task->id . " limit 1";
            $qr = $dsql->ExecQuery($sqldel);
            if (!$qr) {
                throw new Exception("taskcontroller.php - completeTask() - " . $sqldel . " - " . $dsql->GetError());
            }
        }
    }
}

function startTask(&$task)
{
    $task->taskstatus = 1;//启动任务，状态置为1（正常）
    if (empty($task->starttime)) {
        $task->starttime = time();//date("Y-m-d H:i:s");
    }
    if (!empty($task->endtime)) {
        $nowt = time();
        $task->taskparams->scene->waittime += $nowt - $task->endtime;
    }
    updateTask($task);
}

//任务崩溃
function fatalTask(&$task)
{
    try {
        $task->taskstatus = 5;
        $task->endtime = time();//date("Y-m-d H:i:s");
        $task->taskparams->scene->fatalcount++;
        unQueueTask($task->id);
        updateTask($task);
    } catch (Exception $ex) {

    }
}


function updateDataAnalysisTime($weiboids)
{
    if (empty($weiboids)) {
        return false;
    }
    $ntime = time();
    dbconnect();
    $sql = "update weibo_new set analysis_time=" . $ntime . ",
        analysis_status = IF(analysis_status =" . ANALYSIS_STATUS_NEEDORG . "," . ANALYSIS_STATUS_NORMAL . ",analysis_status)
        where id in(" . $weiboids . ")";
    $qr = mysql_query($sql);
    if (!$qr) {
        return false;
    }
    return true;
}

//根据原创id更新转发微博的状态
//原创被删除或原创被屏蔽时，将所有相关转发的“原创ID”字段置为空，并修改analysis_status
//$id 原创id
//$status 状态
function updateRepostAnalysisStatus($orgid, $status)
{
    if (empty($orgid)) {
        return false;
    }
    $v = '';
    switch ($status) {
        case ANALYSIS_STATUS_ORGNOTEXIST:
        case ANALYSIS_STATUS_ORGPRIVATE:
            $v = "retweeted_status = '',";
            break;
        default:
            break;
    }
    $sql = "update weibo_new set {$v} analysis_status={$status} where is_repost = 1 and retweeted_status = '{$orgid}'";
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        return false;
    }
    return true;
}

//更新数据的分析状态
//$status 0 正常，1 未取到原创
function updateDataAnalysisStatus($ids, $status, $sourceid, $mids = false, &$timeStatisticObj = null)
{
    if (!empty($ids) && !empty($mids)) {
        $wh = " (id in ({$ids}) or mid in ({$mids}))";
    } else if (!empty($ids)) {
        $wh = " id in ({$ids})";
    } else if (!empty($mids)) {
        $wh = " mid in ({$mids})";
    }
    $v = '';
    switch ($status) {
        case ANALYSIS_STATUS_ORGNOTEXIST:
        case ANALYSIS_STATUS_ORGPRIVATE:
            $v = "retweeted_status = '',";
            break;
        default:
            break;
    }

    $sqlstart_time = microtime_float();
    $sql = "update weibo_new set {$v} analysis_status = {$status},analysis_time=" . time() . " where {$wh} and sourceid = {$sourceid}";
    dbconnect();
    $qr = mysql_query($sql);
    $sqlend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj, DB_UPDATE_TIME_KEY, $sqlend_time - $sqlstart_time);

    if (!$qr) {
        unset($qr);
        return false;
    }
    unset($qr);
    return true;
}

/**
 *
 * 新增抓取转发任务
 * @param $sourceid 数据源
 * @param $id 原创ID，支持数组或单值
 * @param $mid 原创MID，支持数组或单值
 * @param $delay 冲突延迟秒数
 * @param $local 本地任务
 * @param $remote 远程任务
 * @param $taskparams 任务参数
 * @param $comment 抓取评论任务
 *
 */
function addRepostTask($sourceid, $id, $mid = false, $delay = 60, $local = 1, $remote = 0, $taskparams = null, $remarks = '', $comment = false)
{
    $result = array();
    $ids = array();
    if (!empty($id)) {
        $ids = is_array($id) ? $id : array($id);
    }
    $mids = array();
    if (!empty($mid)) {
        $mids = is_array($mid) ? $mid : array($mid);
    }
    //foreach($ids as $key => $value){
    $sptask = new Task(null);
    $sptask->tasktype = TASKTYPE_SPIDER;//抓取
    $sptask->task = $comment ? TASK_COMMENTS : TASK_REPOST_TREND;//转发或评论
    $sptask->local = $local;
    $sptask->remote = $remote;
    $sptask->activatetime = 0;
    $sptask->conflictdelay = $delay;
    $sptask->remarks = $remarks;
    $sptask->tasksource = $sourceid;
    $sptask->tasklevel = getMaxLevel($sptask->task);
    $sptask->taskparams = array("source" => $sourceid, "each_count" => 200, "iscommit" => true);
    $sptask->taskparams["oristatus"] = array_merge($ids, $mids);
    if (!empty($taskparams)) {
        $sptask->taskparams['config'] = $taskparams->config;
        $sptask->taskparams['duration'] = $taskparams->duration;
        $sptask->taskparams['forceupdate'] = $taskparams->forceupdate;
        if (!$comment) {
            $sptask->taskparams['isrepostseed'] = empty($taskparams->isrepostseed) ? 0 : 1;
        }
    }
    if (addTask($sptask)) {
        $result['result'] = true;
        $result['msg'] = '';
    } else {
        $result['result'] = false;
        $result['msg'] = '添加任务失败';
    }
    //}
    return $result;
}

function detectConflictTask(&$task)
{
    $result = detectCrossType($task);
    if (!$result['result'] || !$result['continue']) {
        return $result;
    }
    $result = detectSameType($task);
    if (!$result['result'] || !$result['continue']) {
        return $result;
    }
    return $result;
}

function detectCrossType(&$task)
{
    global $dsql;
    $result = array('result' => true, 'continue' => true, 'msg' => '');
    if (empty($task->conflictdelay)) {
        return $result;
    }
    if ($task->task == TASK_MIGRATEDATA) {
        $conflicttype = array(TASKTYPE_ANALYSIS, TASKTYPE_SPIDER);
        $conflicttask = array(TASK_SYNC, TASK_WEIBO, TASK_REPOST_TREND, TASK_STATUSES_COUNT, TASK_COMMENTS, TASK_IMPORTWEIBOURL, TASK_IMPORTUSERID, TASK_KEYWORD, TASK_FRIEND);
    } else {
        $conflicttype = array(TASKTYPE_MIGRATE);
        $conflicttask = array(TASK_MIGRATEDATA);
    }
    $sqls = "select * from task  where tasktype in (" . implode(",", $conflicttype) . ") and task in (" . implode(",", $conflicttask) . ") and taskstatus = 1 and id != " . $task->id;
    $qr = $dsql->ExecQuery($sqls);
    if (!$qr) {
        throw new Exception("taskcontroller.php - detectCrossType() - " . $sqls . " - " . $dsql->GetError());
    } else {
        $conflict = false;
        while ($r = $dsql->GetObject($qr)) {
            if ($task->task == TASK_MIGRATEDATA) {
                $migrate = $task;
            } else if ($r->task == TASK_MIGRATEDATA) {
                $r->taskparams = json_decode($r->taskparams);
                $migrate = $r;
            }
            if (!empty($migrate)) {
                if (empty($migrate->taskparams->srchost) ||
                    (!empty($migrate->taskparams->dsthost) && array_search(0, $migrate->taskparams->dsthost) !== false)
                ) {
                    $conflict = true;
                }
            }
            if ($conflict || $task->task == TASK_MIGRATEDATA) {
                break;
            }
        }
        $dsql->FreeResult($qr);
        if ($conflict) {
            $task->activatetime = $task->conflictdelay + time();
            $task->taskstatus = 0;
            updateTaskFull($task);
            $result['continue'] = false;
        }
    }
    return $result;
}

function detectSameType(&$task)
{
    global $dsql;
    $result = array('result' => true, 'continue' => true, 'msg' => '');
    if (empty($task->conflictdelay)) {
        return $result;
    }
    $sqls = "select * from task  where tasktype = " . $task->tasktype . " and task = " . $task->task . " and taskstatus = 1 and id != " . $task->id;
    $qr = $dsql->ExecQuery($sqls);
    if (!$qr) {
        throw new Exception("taskcontroller.php - detectConflictTask() - " . $sqls . " - " . $dsql->GetError());
    } else {
        $conflicts = array();
        $remains = NULL;
        while ($r = $dsql->GetObject($qr)) {
            $params = json_decode($r->taskparams);
            switch ($task->task) {
                case TASK_REPOST_TREND:
                case TASK_COMMENTS:
                    if (!isset($remains)) {
                        $cursor = isset($task->taskparams->select_cursor) ? $task->taskparams->select_cursor : 0;
                        $count = count($task->taskparams->oristatus);
                        if ($cursor >= 0 && $count > 0 && $cursor < $count) {
                            $remains = array_slice($task->taskparams->oristatus, $cursor);
                        } else {
                            $remains = array();
                        }
                    }
                    if (empty($remains)) {
                        break 2;
                    }
                    $tcursor = isset($params->select_cursor) ? $params->select_cursor : 0;
                    $tcount = count($params->oristatus);
                    if ($tcursor >= 0 && $tcount > 0 && $tcursor < $tcount) {
                        for ($i = $tcursor; $i < $tcount; $i++) {
                            $index = array_search($params->oristatus[$i], $remains);
                            if ($index !== false && $index !== NULL) {
                                array_splice($remains, $index, 1);
                                $conflicts[] = $params->oristatus[$i];
                            }
                        }
                    }
                    break;
                case TASK_MIGRATEDATA:
                    if ($task->taskparams->srchost == $params->srchost ||
                        !empty($params->dsthost) && array_search($task->taskparams->srchost, $params->dsthost) !== false ||
                        !empty($task->taskparams->dsthost) && array_search($params->srchost, $task->taskparams->dsthost) !== false
                    ) {
                        $conflicts[] = $r->id;
                        break 2;
                    }
                    if (!empty($params->dsthost) && !empty($task->taskparams->dsthost)) {
                        $inter = array_intersect($params->dsthost, $task->taskparams->dsthost);
                        if (!empty($inter)) {
                            $conflicts[] = $r->id;
                            break 2;
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        $dsql->FreeResult($qr);
        if (!empty($conflicts)) {
            if (empty($remains)) {
                $task->activatetime = $task->conflictdelay + time();
                $task->taskstatus = 0;
                updateTaskFull($task);
                $result['continue'] = false;
            } else {
                switch ($task->task) {
                    case TASK_REPOST_TREND:
                    case TASK_COMMENTS:
                        $rs = addRepostTask($task->tasksource, $conflicts, false, $task->conflictdelay, $task->local, $task->remote, $task->taskparams, $task->remarks, $task->task == TASK_COMMENTS);
                        if (!$rs['result']) {
                            $result['result'] = false;
                            $result['msg'] = $rs['msg'];
                            break;
                        }
                        $cursor = isset($task->taskparams->select_cursor) ? $task->taskparams->select_cursor : 0;
                        array_splice($task->taskparams->oristatus, $cursor, count($task->taskparams->oristatus), $remains);
                        updateTaskFull($task);
                        break;
                    default:
                        break;
                }
            }
        }
    }
    return $result;
}

function getTaskparam($taskid)
{
    global $logger, $dsql;
    $result = "";
    $sqlsel = "select taskparams,tasklevel from " . DATABASE_TASK . " where id ={$taskid}";
    $qr = $dsql->ExecQuery($sqlsel);
    if (!$qr) {
        $logger->error(SELF . ' sql :' . $sqlsel . ' error: ' . $dsql->GetError());
    } else {
        $q_r = $dsql->GetArray($qr);
        if (!empty($q_r)) {
            $result = json_decode($q_r['taskparams'], true);
            $result['tasklevel'] = $q_r['tasklevel'];
        }
    }
    return $result;
}

//根据任务编号从数据库获取分词方案  如果字典taskparams没有dictionaryPlan 返回默认
function queryDictionaryPlan($taskid)
{
    global $logger, $dsql;
    $result = "";
    $plan = "[[]]";
    $sqlsel = "select taskparams from " . DATABASE_TASK . " where id ={$taskid}";
    $qr = $dsql->ExecQuery($sqlsel);

    if (!$qr) {
        $logger->error(SELF . ' sql :' . $sqlsel . ' error: ' . $dsql->GetError());
    } else {

        $q_r = $dsql->GetArray($qr);
        if (!empty($q_r)) {
            $result = json_decode($q_r['taskparams']);
        }
    }
    if (!empty($result->dictionaryPlan)) {
        if (strlen($result->dictionaryPlan) > 4) {
            $plan = formatDictionaryPlan($result->dictionaryPlan);
        }
        $logger->debug("enter querydictionaryPlan" . $result->dictionaryPlan);
    }
    return $plan;
}

function updateAgentTask4CommonTask($id, $host, $stat, &$data = NULL, $page = NULL, $flag = 0, &$taskobj)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . " function " . __FUNCTION__ . " ### id:[{$id}] host:[{$host}] stat:[{$stat}] page:[{$page}] flag:[{$flag}]");
    //****************************************测试代码**************************************//
//    $taskobj->taskparams = getTaskParam4Test($taskobj->id, $taskobj->taskparams);
    //****************************************测试代码************************************END//

//    $taskobj->taskparams = converObjToRelArray($taskobj->taskparams);
//    $taskParams = $taskobj->taskparams["root"];
    if (is_array($taskobj->taskparams)) {
        $taskParams = &$taskobj->taskparams["root"];
    } else if (is_object($taskobj->taskparams)) {
//        $taskobj->taskparams =
        $paramObj = $taskobj->taskparams;
        $paramObjStr = json_encode($paramObj);
        $paramObj = json_decode($paramObjStr, true);
        $taskobj->taskparams = &$paramObj;
        $taskParams = &$taskobj->taskparams->root;
    }

    if ($taskobj->taskstatus != 1) {
        if ($taskobj->taskstatus == -1) {
            $taskobj->taskstatus = 2;
            $result['result'] = false;
            $result['msg'] = '任务已停止';
            $result['error'] = -2;
        } else if ($taskobj->taskstatus == 6) {
            $taskobj->taskstatus = 1;
            if (isset($taskParams["runTimeParam"]["scene"]["veriimage"])) {
                unlink($taskParams["runTimeParam"]["scene"]["veriimage"]);
                unset($taskParams["runTimeParam"]["scene"]["veriimage"]);
            }
            if (isset($taskParams["runTimeParam"]["scene"]["vericode"])) {
                unset($taskParams["runTimeParam"]["scene"]["vericode"]);
            }
        } else {
            $result['result'] = false;
            $result['msg'] = '任务已失效';
            $result['error'] = -2;
            return $result;
        }
    }

    $dataSize = count($data);
    $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### dataSize:[" . $dataSize . "].");
    $taskobj->datastatus += $dataSize;

    if (!isset($taskParams["runTimeParam"]["scene"])) {
        $taskParams["runTimeParam"]["scene"] = array();
    }

    if (!empty($stat) || (!empty($taskParams["runTimeParam"]["scene"]["historystat"]))) {
        if (!empty($taskParams["runTimeParam"]["scene"]["historystat"])) {
            $historyStat = $taskParams["runTimeParam"]["scene"]["historystat"];
            $historyStat = json_decode(json_encode($historyStat));
            $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### historystat存在! ---[" . var_export($historyStat, true) . "].");
        } else {
            //没有历史状态
            $historyStat = NULL;
        }
        if (!empty($stat)) {
            $stat = json_decode(json_encode($stat));
            $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### stat存在! ---[" . var_export($stat, true) . "].");
        }
        $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### begain add SpiderStat... historyStat:[" . var_export($historyStat, true) . "] curStat:[" . var_export($stat, true) . "] ParmStat:[" . var_export($taskParams["runTimeParam"]["scene"], true) . "].");
        $historyStat = addSpiderStat($historyStat, json_decode($stat));
        $taskParams["runTimeParam"]["scene"]["stat"] = json_decode(json_encode($historyStat), true);
        $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### success add SpiderStat. stat:[" . var_export($taskParams["runTimeParam"]["scene"]["stat"], true) . "].");
    } else {
        $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### historystat不存在!");
    }

//    followpost 用来计算轨迹

    if (!empty($data)) {
        if (!isset($taskParams["followpost"])) {
            $taskParams["runTimeParam"]["followpost"] = array();
        }
        $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### followpost:[" . var_export($taskParams["runTimeParam"]["followpost"], true) . "].");

        foreach ($data as $di => $ditem) {
            //$logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### begain hanle runTimeParam for one grab data:[" . var_export($ditem, true) . "].");
            //判断 主楼
            if ((gettype($ditem) == "array") && (isset($ditem['text'])) && (isset($ditem['floor'])) && $ditem['floor'] == 0) {
                $tmpobj = array();
                $tmpobj['sourceid'] = isset($ditem['sourceid']) ? $ditem['sourceid'] : NULL;
                //检查是否有host
                if (!isset($ditem['source_host'])) {
                    if (!empty($ditem['page_url'])) { //page_url 和 original_url 是否都需要设置
                        $tmpobj['source_host'] = get_host_from_url($ditem['page_url']);
                    }
                }
                $tmpobj['original_url'] = isset($ditem['original_url']) ? $ditem['original_url'] : NULL;
                $tmpobj['floor'] = isset($ditem['floor']) ? $ditem['floor'] : NULL;
                $tmpobj['paragraphid'] = isset($ditem['paragraphid']) ? $ditem['paragraphid'] : NULL;
                $tmpobj['mid'] = isset($ditem['mid']) ? $ditem['mid'] : NULL;
                if (isset($ditem['comments_count'])) {
                    $tmpobj['comments_count'] = $ditem['comments_count'];
                }
                if (!empty($taskParams["runTimeParam"]["followpost"])) {
                    $hasflag = false;
                    foreach ($taskParams["runTimeParam"]["followpost"] as $pi => $pitem) {
                        if (isset($ditem['mid']) && isset($pitem["mid"]) && $pitem["mid"] == $ditem['mid']) {
                            $hasflag = true;
                            break;
                        } else if (isset($ditem['original_url']) && isset($ditem['floor']) && isset($ditem['paragraphid'])
                            && isset($pitem["original_url"]) && isset($pitem["floor"]) && isset($pitem["paragraphid"])
                            && $pitem["original_url"] == $ditem['original_url'] && $pitem["floor"] == $ditem['floor'] && $pitem["paragraphid"] == $ditem['paragraphid']
                        ) {
                            $hasflag = true;
                            break;
                        }
                    }
                    if (!$hasflag) {
                        $taskParams["runTimeParam"]["followpost"][] = $tmpobj;
                    }
                } else {
                    $taskParams["runTimeParam"]["followpost"][] = $tmpobj;
                }
                $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### handle runTimeParam success for taskParam:[" . var_export($taskParams["runTimeParam"]["followpost"], true) . "].");
            } else {
                $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### 当前数据不是主楼不进行轨迹计算!");
            }
        }
    } else {
        $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### no need to handle followpost param! followpost datas null!");
    }
    updateTask($taskobj, "machine='{$host}'");
}

function updateAgentTask($id, $host, $stat, &$data = NULL, $page = NULL, $flag = 0, $hostcheck = 1)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . " function " . __FUNCTION__ . " updateAgentTask ### id:[{$id}] host:[{$host}] stat:[{$stat}] page:[{$page}] flag:[{$flag}]");
    //$logger->debug(__FILE__ . __LINE__ . " function " . __FUNCTION__ . "updateAgentTask for grabData: " . var_export($data, true));


    $result = array('result' => true, 'msg' => '', 'error' => 0, 'done' => false);
    if (!isset($id) || !isset($host) || !isset($stat)) {
        $result['result'] = false;
        $result['msg'] = '参数错误';
        $result['error'] = -1;
        return $result;
    }
    $taskobj = getTaskById($id);
    if (empty($taskobj)) {
        $result['result'] = false;
        $result['msg'] = '任务不存在';
        $result['error'] = -2;
        return $result;
    }
    $logger->debug(__FILE__ . __LINE__ . "------是否获取到---hostcheck-----" . var_export($hostcheck, true));
    if ($taskobj->machine != $host && $hostcheck != 0) {//update:$hostcheck!=0
        $logger->debug(__FILE__ . __LINE__ . "-------任务失效---------" . var_export($taskobj->machine, true));
        $result['result'] = false;
        $result['msg'] = '任务已失效 Task.machine[' . $taskobj->machine . "] host:[{$host}]";
        $result['error'] = -2;
        return $result;
    }
    $logger->debug(__FILE__ . __LINE__ . " function " . __FUNCTION__ . " ### taskstatus:[" . $taskobj->taskstatus . "].");

    if ($taskobj->task == TASK_COMMON) {
        updateAgentTask4CommonTask($id, $host, $stat, $data, $page, $flag, $taskobj);
        return $result;
    }

    if ($taskobj->taskstatus != 1) {
        if ($taskobj->taskstatus == -1) {
            $taskobj->taskstatus = 2;
            $result['result'] = false;
            $result['msg'] = '任务已停止';
            $result['error'] = -2;
        } else if ($taskobj->taskstatus == 6) {
            $taskobj->taskstatus = 1;
            if (isset($taskobj->taskparams->scene->veriimage)) {
                unlink($taskobj->taskparams->scene->veriimage);
                unset($taskobj->taskparams->scene->veriimage);
            }
            if (isset($taskobj->taskparams->scene->vericode)) {
                unset($taskobj->taskparams->scene->vericode);
            }
        } else {
            $result['result'] = false;
            $result['msg'] = '任务已失效';
            $result['error'] = -2;
            return $result;
        }
    }
    $dataSize = getDataCount($taskobj, $data);
    $logger->debug(__FILE__ . __LINE__ . " function: " . __FUNCTION__ . " ### dataSize:[" . $dataSize . "].");
    $taskobj->datastatus += $dataSize;

    if (!isset($taskobj->taskparams->scene)) {
        $taskobj->taskparams->scene = (object)array();
    }

    $taskobj->taskparams->scene->stat = addSpiderStat($taskobj->taskparams->scene->historystat, json_decode($stat));

    if (!empty($data)) {
        switch ($taskobj->task) {
            case TASK_REPOST_TREND:
                $res = updateRepostTask($taskobj, $data, $page, $flag);
                if ($res['result'] == false) {
                    $result['result'] = false;
                    $result['msg'] = $res['msg'];
                    $result['error'] = -1;
                    return $result;
                } else if ($res['done'] == true) {
                    $result['done'] = true;
                }
                break;
            case TASK_COMMENTS:
                $res = updateCommentTask($taskobj, $data, $page, $flag);
                if ($res['result'] == false) {
                    $result['result'] = false;
                    $result['msg'] = $res['msg'];
                    $result['error'] = -1;
                    return $result;
                } else if ($res['done'] == true) {
                    $result['done'] = true;
                }
                break;
            case TASK_WEBPAGE:
                if (!isset($taskobj->taskparams->followpost)) {
                    $taskobj->taskparams->followpost = array();
                }
                foreach ($data as $di => $ditem) {
                    if (isset($ditem['text']) && isset($ditem['floor']) && $ditem['floor'] == 0) {
                        $tmpobj = array();
                        $tmpobj['sourceid'] = isset($ditem['sourceid']) ? $ditem['sourceid'] : NULL;
                        //检查是否有host
                        if (!isset($ditem['source_host'])) {
                            if (!empty($ditem['page_url'])) {
                                $tmpobj['source_host'] = get_host_from_url($ditem['page_url']);
                            }
                        }
                        $tmpobj['original_url'] = isset($ditem['original_url']) ? $ditem['original_url'] : NULL;
                        $tmpobj['floor'] = isset($ditem['floor']) ? $ditem['floor'] : NULL;
                        $tmpobj['paragraphid'] = isset($ditem['paragraphid']) ? $ditem['paragraphid'] : NULL;
                        $tmpobj['mid'] = isset($ditem['mid']) ? $ditem['mid'] : NULL;
                        if (isset($ditem['comments_count'])) {
                            $tmpobj['comments_count'] = $ditem['comments_count'];
                        }
                        if (!empty($taskobj->taskparams->followpost)) {
                            $hasflag = false;
                            foreach ($taskobj->taskparams->followpost as $pi => $pitem) {
                                if (isset($ditem['mid']) && isset($pitem->mid) && $pitem->mid == $ditem['mid']) {
                                    $hasflag = true;
                                    break;
                                } else if (isset($ditem['original_url']) && isset($ditem['floor']) && isset($ditem['paragraphid'])
                                    && isset($pitem->original_url) && isset($pitem->floor) && isset($pitem->paragraphid)
                                    && $pitem->original_url == $ditem['original_url'] && $pitem->floor == $ditem['floor'] && $pitem->paragraphid == $ditem['paragraphid']
                                ) {
                                    $hasflag = true;
                                    break;
                                }
                            }
                            if (!$hasflag) {
                                $taskobj->taskparams->followpost[] = $tmpobj;
                            }
                        } else {
                            $taskobj->taskparams->followpost[] = $tmpobj;
                        }
                    }
                }
                break;
            default:
                break;
        }
    }
    updateTask($taskobj, "machine='{$host}'");
    return $result;
}

function getDataCount(&$taskobj, &$data)
{
    $count = 0;
    if (!empty($data)) {
        switch ($taskobj->task) {
            case TASK_KEYWORD:
            case TASK_WEIBO:
            case TASK_REPOST_TREND:
            case TASK_COMMENTS:
            case TASK_WEBPAGE:
                $count = count($data);
                break;
            case TASK_FRIEND:
                $udata = $data[0];
                if (isset($udata['ids'])) {
                    $count = count($udata['ids']);
                }
                break;
            default:
                break;
        }
    }
    return $count;
}

function updateRepostTask(&$taskobj, &$data, $page, $flag = 0)
{
    $result = array('result' => true, 'msg' => '', 'done' => false);
    switch ($taskobj->taskparams->phase) {
        case 1:
            $repost = array_pop($taskobj->taskparams->repost);
            if ($repost !== NULL) {
                if ($flag == 1) {
                    $orig = getWeiboById($taskobj->taskparams->source, $repost->orig, $taskobj->taskparams->isseed);
                    if ($orig['result'] == false) {
                        $result['result'] = false;
                        $result['msg'] = '获取原创失败';
                        break;
                    }
                }
                $ids = array();
                foreach ($data as $k => $v) {
                    if (!empty($v['id'])) {
                        if (array_search($v['id'], $ids) === false) {
                            $ids[] = $v['id'];
                        }
                    }
                    if ($flag == 1) {
                        $data[$k]['retweeted_status'] = $orig['weibo'];
                    }
                }
                if (!empty($ids)) {
                    $res = addRepostInfo($repost->orig, $ids, $repost->idnum, $taskobj->id);
                    if ($res === false) {
                        $result['result'] = false;
                        $result['msg'] = '添加转发失败';
                        break;
                    }
                }
                $taskobj->taskparams->repost[] = $repost;
            }
            break;
        case 2:
            $result['done'] = true;
            $repost = $taskobj->taskparams->repost[$taskobj->taskparams->origin_cursor];
            if (empty($repost)) {
                $result['result'] = false;
                $result['msg'] = '一级转发空';
                break;
            }
            $fatherid = getRepostId($repost->orig, NULL, $taskobj->taskparams->repost_cursor, $taskobj->id);
            if (empty($fatherid)) {
                $result['result'] = false;
                $result['msg'] = '上层转发空';
                break;
            }
            $father = getWeiboById($taskobj->taskparams->source, $fatherid, $taskobj->taskparams->isrepostseed);
            if ($father['result'] == false) {
                $result['result'] = false;
                $result['msg'] = '获取上层转发失败';
                break;
            }
            $depth = $father['weibo']['repost_trend_cursor'] + 1;
            $ids = array();
            foreach ($data as $k => $v) {
                if (empty($v['id'])) {
                    continue;
                }
                $repostid = getRepostId($repost->orig, $v['id'], -1, $taskobj->id);
                if (empty($repostid)) {
                    continue;
                }
                $ids[] = $v['id'];
            }
            if (!empty($ids)) {
                $res = updateFatherId($taskobj->taskparams->source, $ids, $fatherid, $depth);
                if ($res['result'] == false) {
                    $result['result'] = false;
                    $result['msg'] = $res['msg'];
                }
            }
            break;
        default:
            break;
    }
    if (!empty($page)) {
        $taskobj->taskparams->page_cursor = $page;
    } else {
        unset($taskobj->taskparams->page_cursor);
    }
    return $result;
}

function updateCommentTask(&$taskobj, &$data, $page, $flag = 0)
{
    $result = array('result' => true, 'msg' => '', 'done' => false);
    $comment = array_pop($taskobj->taskparams->comment);
    if ($comment !== NULL) {
        if ($flag == 1) {
            //这个函数是从数据库查询的数据库没有source_host, 对于没有设置来源对应sourceid的不可用
            $orig = getWeiboById($taskobj->taskparams->source, $comment->orig, $taskobj->taskparams->isseed);
            if ($orig['result'] == false) {
                $result['result'] = false;
                $result['msg'] = '获取原微博失败';
                return $result;
            }
            foreach ($data as $k => $v) {
                $data[$k]['status'] = $orig['weibo'];
            }
            $comment->sourceid = isset($orig['weibo']['sourceid']) ? $orig['weibo']['sourceid'] : NULL;
            $comment->source_host = isset($orig['weibo']['source_host']) ? $orig['weibo']['source_host'] : NULL;
            $comment->original_url = isset($orig['weibo']['original_url']) ? $orig['weibo']['original_url'] : NULL;
            $comment->floor = isset($orig['weibo']['floor']) ? $orig['weibo']['floor'] : NULL;
            $comment->paragraphid = isset($orig['weibo']['paragraphid']) ? $orig['weibo']['paragraphid'] : NULL;
            $comment->mid = isset($orig['weibo']['mid']) ? $orig['weibo']['mid'] : NULL;
            if (isset($orig['weibo']['comments_count'])) {
                $comment->comments_count = $orig['weibo']['comments_count'];
            }
        }
        if (!empty($data)) {
            $comment->idnum += count($data);
        }
        $taskobj->taskparams->comment[] = $comment;
    }
    if (!empty($page)) {
        $taskobj->taskparams->page_cursor = $page;
    } else {
        unset($taskobj->taskparams->page_cursor);
    }
    return $result;
}

function addSpiderStat($history, $delta)
{
    if (empty($history)) {
        return $delta;
    }
    if (empty($delta)) {
        return $history;
    }
    $stat = (object)array();
    $stat->count = $history->count + $delta->count;
    $stat->child = $history->child + $delta->child;
    $stat->time = $history->time + $delta->time;
    if ($stat->count == 0) {
        $stat->avg_time = 0;
    } else {
        $stat->avg_time = floor($stat->time / $stat->count);
    }
    if ($delta->min_time < $history->min_time) {
        $stat->min_time = $delta->min_time;
    } else {
        $stat->min_time = $history->min_time;
    }
    if ($delta->max_time > $history->max_time) {
        $stat->max_time = $delta->max_time;
    } else {
        $stat->max_time = $history->max_time;
    }
    $stat->svr_time = $history->svr_time + $delta->svr_time;
    if ($stat->count == 0) {
        $stat->avg_svr_time = 0;
    } else {
        $stat->avg_svr_time = floor($stat->svr_time / $stat->count);
    }
    if ($delta->min_svr_time < $history->min_svr_time) {
        $stat->min_svr_time = $delta->min_svr_time;
    } else {
        $stat->min_svr_time = $history->min_svr_time;
    }
    if ($delta->max_svr_time > $history->max_svr_time) {
        $stat->max_svr_time = $delta->max_svr_time;
    } else {
        $stat->max_svr_time = $history->max_svr_time;
    }
    return $stat;
}

function addTaskSchedule($schedule)
{
    global $dsql, $logger;
    $tenantid = "NULL";
    $userid = "NULL";
    $userinfo = isset($_SESSION['user']) ? $_SESSION['user'] : NULL;
    if ($userinfo != NULL) {
        $tenantid = $userinfo->tenantid;
        $userid = $userinfo->getuserid();
    }
    $conflictdelay = empty($schedule->conflictdelay) ? "NULL" : $schedule->conflictdelay;
    $starttime = empty($schedule->starttime) ? "NULL" : $schedule->starttime;
    $endtime = empty($schedule->endtime) ? "NULL" : $schedule->endtime;
    $updatetime = empty($schedule->updatetime) ? "NULL" : $schedule->updatetime;
    $status = isset($schedule->status) ? $schedule->status : 1;
    $taskpagestyletype = empty($schedule->taskpagestyletype) ? "NULL" : $schedule->taskpagestyletype;

    $taskclassify = empty($schedule->taskclassify) ? "NULL" : "'" . $schedule->taskclassify . "'";
    $spcfdmac = empty($schedule->spcfdmac) ? "NULL" : "'" . $schedule->spcfdmac . "'";
    $sql = "insert into " . DATABASE_WEIBOINFO . "." . DATABASE_TASKSCHEDULE . " (tasktype,taskpagestyletype,task,tasklevel,local,remote,conflictdelay,params,starttime,endtime,crontime,remarks,status,updatetime, tenantid, userid,taskclassify,spcfdmac )";
//    $sql .= " values(" . $schedule->tasktype . "," . $taskpagestyletype . "," . $schedule->task . "," . $schedule->tasklevel . "," . $schedule->local . "," . $schedule->remote . "," . $conflictdelay . ",'" . jsonEncode4DB($schedule->params) . "'," . $starttime . "," . $endtime . ",'" . jsonEncode4DB($schedule->crontime) . "','" . $schedule->remarks . "'," . $status . "," . $updatetime . ", " . $tenantid . ", ". $userid . ", ".((isset($taskclassify) && !empty($taskclassify) && $taskclassify!="undefined" )? "'".$schedule->taskclassify."'" : "NULL"). "," . ((isset($spcfdmac) && !empty($spcfdmac) && $spcfdmac!="undefined")? "'".$schedule->spcfdmac."'" :"NULL"). ")";
    $sql .= " values(" . $schedule->tasktype . "," . $taskpagestyletype . "," . $schedule->task . "," . $schedule->tasklevel . "," . $schedule->local . "," . $schedule->remote . "," . $conflictdelay . ",'" . jsonEncode4DB($schedule->params) . "'," . $starttime . "," . $endtime . ",'" . jsonEncode4DB($schedule->crontime) . "','" . $schedule->remarks . "'," . $status . "," . $updatetime . ", " . $tenantid . ", " . $userid . ", " . $taskclassify . "," . $spcfdmac . ")";
    $logger->debug(" - addTaskSchedule() sql:[" . $sql . "].");
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " sql: {$sql} - " . $dsql->GetError());
    } else {
        $dsql->FreeResult($qr);
        return true;
    }
}

function deleteTaskScheduleHistory($scheduleid)
{
    global $dsql;
    $rows = 0;
    $sqldel = "delete from " . DATABASE_TASKSCHEDULEHISTORY . " where id = " . $scheduleid;
    $qr = $dsql->ExecQuery($sqldel);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sqldel . " - " . $dsql->GetError());
    }
    $rows = $dsql->GetAffectedRows();
    return $rows;
}

function deleteTaskSchedule($schedule, $cond = '')
{
    global $dsql;
    $rows = 0;
    $cond = empty($cond) ? '' : " and " . $cond;
    $sqldel = "delete from " . DATABASE_TASKSCHEDULE . " where id = " . $schedule->id . $cond;
    $qr = $dsql->ExecQuery($sqldel);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sqldel . " - " . $dsql->GetError());
    }
    $rows = $dsql->GetAffectedRows();
    return $rows;
}

function completeTaskSchedule($schedule, $cond = '')
{
    global $dsql;
    $rows = 0;
    $cond = empty($cond) ? '' : " and " . $cond;
    $conflictdelay = empty($schedule->conflictdelay) ? "NULL" : $schedule->conflictdelay;
    $starttime = empty($schedule->starttime) ? "NULL" : $schedule->starttime;
    $endtime = empty($schedule->endtime) ? "NULL" : $schedule->endtime;
    $crontime = is_object($schedule->crontime) ? jsonEncode4DB($schedule->crontime) : $dsql->Esc($schedule->crontime);
    $params = is_object($schedule->params) ? jsonEncode4DB($schedule->params) : $dsql->Esc($schedule->params);
    $tenantid = empty($schedule->tenantid) ? "NULL" : $schedule->tenantid;
    $userid = empty($schedule->userid) ? "NULL" : $schedule->userid;
    $taskpagestyletype = empty($schedule->taskpagestyletype) ? "NULL" : $schedule->taskpagestyletype;
    $sql = "insert into " . DATABASE_TASKSCHEDULEHISTORY . " (id,tasktype,taskpagestyletype,task,tasklevel,local,remote,conflictdelay,params,starttime,endtime,crontime,remarks, tenantid, userid)";
    $sql .= " values(" . $schedule->id . "," . $schedule->tasktype . "," . $taskpagestyletype . "," . $schedule->task . "," . $schedule->tasklevel . "," . $schedule->local . "," . $schedule->remote . "," . $conflictdelay . ",'" . $params . "'," . $starttime . "," . $endtime . ",'" . $crontime . "','" . $schedule->remarks . "', " . $tenantid . ", " . $userid . ")";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sql . " - " . $dsql->GetError());
    } else {
        $sqldel = "delete from " . DATABASE_TASKSCHEDULE . " where id = " . $schedule->id . $cond;
        $qr = $dsql->ExecQuery($sqldel);
        if (!$qr) {
            throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sqldel . " - " . $dsql->GetError());
        }
        $rows = $dsql->GetAffectedRows();
        if ($rows == 0) {
            $sqldel = "delete from " . DATABASE_TASKSCHEDULEHISTORY . " where id = " . $schedule->id . " limit 1";
            $qr = $dsql->ExecQuery($sqldel);
            if (!$qr) {
                throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sqldel . " - " . $dsql->GetError());
            }
        }
    }
    return $rows;
}

function updateTaskScheduleFull($schedule, $cond = '')
{
    global $link, $logger;
    $tenantid = "NULL";
    $userid = "NULL";
    $userinfo = isset($_SESSION['user']) ? $_SESSION['user'] : NULL;
    if ($userinfo != NULL) {
        $tenantid = $userinfo->tenantid;
        $userid = $userinfo->getuserid();
    }
    $cond = empty($cond) ? '' : " and " . $cond;
    $conflictdelay = empty($schedule->conflictdelay) ? "NULL" : $schedule->conflictdelay;
    $taskpagestyletype = empty($schedule->taskpagestyletype) ? "NULL" : $schedule->taskpagestyletype;
    $starttime = empty($schedule->starttime) ? "NULL" : $schedule->starttime;
    $endtime = empty($schedule->endtime) ? "NULL" : $schedule->endtime;
    $updatetime = empty($schedule->updatetime) ? "NULL" : $schedule->updatetime;
    $crontime = empty($schedule->crontime) ? "NULL" : $schedule->crontime;
    $sql = "update " . DATABASE_TASKSCHEDULE .
        " set tasktype = " . $schedule->tasktype . ", taskpagestyletype = " . $taskpagestyletype . ", task = " . $schedule->task . ", tasklevel = " . $schedule->tasklevel .
        ", local = " . $schedule->local . ", remote = " . $schedule->remote . ", conflictdelay = " . $conflictdelay .
        ", params = '" . jsonEncode4DB($schedule->params) . "', starttime = " . $starttime . ", endtime = " . $endtime .
        ", crontime = '" . jsonEncode4DB($crontime) . "', remarks = '" . $schedule->remarks . "', status = " . $schedule->status .
        ", updatetime = " . $updatetime . " , tenantid = " . $tenantid . ", userid = " . $userid . " where id = " . $schedule->id . $cond;
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sql . " - " . mysql_error());
    }
    return mysql_affected_rows($link);
}

function updateTaskScheduleStatus($schedule, $cond = '')
{
    global $link;
    $cond = empty($cond) ? '' : " and " . $cond;
    $sql = "update " . DATABASE_TASKSCHEDULE . " set status = " . $schedule->status . " where id = " . $schedule->id . $cond;
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sql . " - " . mysql_error());
    }
    return mysql_affected_rows($link);
}

function updateTaskScheduleInfo($schedule, $cond = '')
{
    global $link;
    $cond = empty($cond) ? '' : " and " . $cond;
    $updatetime = empty($schedule->updatetime) ? "" : ", updatetime = " . $schedule->updatetime;
    $sql = "update " . DATABASE_TASKSCHEDULE . " set status = " . $schedule->status . $updatetime .
        ", params = '" . jsonEncode4DB($schedule->params) . "' where id = " . $schedule->id . $cond;
    dbconnect();
    $qr = mysql_query($sql);
    if (!$qr) {
        throw new Exception("taskcontroller.php - " . __FUNCTION__ . " - " . $sql . " - " . mysql_error());
    }
    return mysql_affected_rows($link);
}

function isScheduleIdentical($schedule1, $schedule2)
{
    if ($schedule1->tasktype != $schedule2->tasktype ||
        $schedule1->task != $schedule2->task ||
        $schedule1->tasklevel != $schedule2->tasklevel ||
        $schedule1->local != $schedule2->local ||
        $schedule1->remote != $schedule2->remote ||
        $schedule1->status != $schedule2->status ||
        $schedule1->remarks != $schedule2->remarks
    ) {
        return false;
    }
    if (isset($schedule1->taskpagestyletype) || isset($schedule2->taskpagestyletype)) {
        if ($schedule1->taskpagestyletype != $schedule2->taskpagestyletype) {
            return false;
        }
    }
    $conflictdelay1 = empty($schedule1->conflictdelay) ? 0 : $schedule1->conflictdelay;
    $conflictdelay2 = empty($schedule2->conflictdelay) ? 0 : $schedule2->conflictdelay;
    if ($conflictdelay1 != $conflictdelay2) {
        return false;
    }
    $starttime1 = empty($schedule1->starttime) ? 0 : $schedule1->starttime;
    $starttime2 = empty($schedule2->starttime) ? 0 : $schedule2->starttime;
    if ($starttime1 != $starttime2) {
        return false;
    }
    $endtime1 = empty($schedule1->endtime) ? 0 : $schedule1->endtime;
    $endtime2 = empty($schedule2->endtime) ? 0 : $schedule2->endtime;
    if ($endtime1 != $endtime2) {
        return false;
    }
    $updatetime1 = empty($schedule1->updatetime) ? 0 : $schedule1->updatetime;
    $updatetime2 = empty($schedule2->updatetime) ? 0 : $schedule2->updatetime;
    if ($updatetime1 != $updatetime2) {
        return false;
    }
    $params1 = is_string($schedule1->params) ? $schedule1->params : json_encode($schedule1->params);
    $params2 = is_string($schedule2->params) ? $schedule2->params : json_encode($schedule2->params);
    if ($params1 != $params2) {
        return false;
    }
    if (!empty($schedule1->crontime) && !empty($schedule2->crontime)) {
        $crontime1 = is_string($schedule1->crontime) ? $schedule1->crontime : json_encode($schedule1->crontime);
        $crontime2 = is_string($schedule2->crontime) ? $schedule2->crontime : json_encode($schedule2->crontime);
        if ($crontime1 != $crontime2) {
            return false;
        }
    } else {
        return false;
    }
    if (isset($schedule1->eventlist)) {
        $eventlist1 = is_string($schedule1->eventlist) ? $schedule1->eventlist : json_encode($schedule1->eventlist);
        $eventlist2 = is_string($schedule2->eventlist) ? $schedule2->eventlist : json_encode($schedule2->eventlist);
        if ($eventlist1 != $eventlist2) {
            return false;
        }
    }
    return true;
}

function checkDupTask(&$task, $precision = 60)
{
    global $dsql, $logger;
    $result = array('result' => true, 'dup' => 0, 'msg' => '', 'tasks' => null);
    $outdate = time() - 86400; // ignore history task started 1 day ago
    $eachcount = 10;
    $tasks = array($task);
    for ($table = 0; $table < 2; $table++) {
        switch ($table) {
            case 0:
                $sqls = "select taskparams,taskstatus,activatetime,starttime from task where tasktype = " . $task->tasktype . " and task = " . $task->task . " and taskstatus in (0,1)";
                break;
            case 1:
                $sqls = "select taskparams,starttime,endtime from taskhistory where tasktype = " . $task->tasktype . " and task = " . $task->task . " and starttime is not NULL and endtime is not NULL and starttime > {$outdate}";
                break;
            default:
                break;
        }
        $limitcursor = 0;
        while (1) {
            $sql = $sqls . " order by id limit {$limitcursor},{$eachcount}";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
                $result['result'] = false;
                $result['msg'] = '查询数据库出错';
                break 2;
            } else {
                $r_count = $dsql->GetTotalRow($qr);
                if ($r_count == 0) {
                    break;
                }
                while ($t = $dsql->GetObject($qr)) {
                    if ($table == 0) {
                        if ($t->taskstatus == 0 && !empty($t->activatetime) && $t->activatetime > time()) {
                            // future task does not count
                            continue;
                        }
                    }
                    $taskparams = json_decode($t->taskparams);
                    switch ($task->task) {
                        case TASK_KEYWORD:
                        case TASK_WEIBO:
                            // NOTE: $taskparams->endtime default NULL, $t->starttime default 0
                            if (!isset($taskparams->endtime) || (!empty($t->starttime) && $t->starttime < $taskparams->endtime)) {
                                $taskparams->endtime = empty($t->starttime) ? NULL : ($t->starttime - $t->starttime % $precision);
                            }

                            if ($task->task == TASK_KEYWORD) {
                                $ret = sliceKeywordTask($tasks, $taskparams);
                            } else {
                                $ret = sliceWeiboTask($tasks, $taskparams);
                            }
                            $result['dup'] += $ret;
                            break;
                        case TASK_REPOST_TREND:
                        case TASK_COMMENTS:
                            if ($table == 0) {
                                $ret = sliceRepostTask($tasks, $taskparams);
                                $result['dup'] += $ret;
                            }
                            break;
                        case TASK_FRIEND:
                        case TASK_STATUSES_COUNT:
                        case TASK_SYNC:
                        default:
                            break;
                    }
                }
                $dsql->FreeResult($qr);
            }
            if ($r_count < $eachcount) {
                break;
            }
            $limitcursor += $eachcount;
        }
    }
    if ($result['result']) {
        $result['tasks'] = $tasks;
    }
    return $result;
}

function sliceKeywordTask(&$tasks, $taskparams)
{
    $dup = 0;
    for ($idx = 0; $idx < count($tasks); $idx++) {
        $task = $tasks[$idx];
        if (empty($task->taskparams->username) != empty($taskparams->username) ||
            (!empty($task->taskparams->username) && $task->taskparams->username != $taskparams->username)
        ) {
            continue;
        }
        if ((isset($taskparams->starttime) && isset($task->taskparams->endtime) && $taskparams->starttime >= $task->taskparams->endtime) ||
            (isset($taskparams->endtime) && isset($task->taskparams->starttime) && $taskparams->endtime <= $task->taskparams->starttime)
        ) {
            continue;
        }
        $kwinter = array_merge(array_intersect($task->taskparams->keywords, $taskparams->keywords));
        if (empty($kwinter)) {
            continue;
        }
        $dup = 1;
        $kwdiff = array_merge(array_diff($task->taskparams->keywords, $taskparams->keywords));
        if ((!isset($taskparams->starttime) || (isset($task->taskparams->starttime) && $task->taskparams->starttime >= $taskparams->starttime)) &&
            (!isset($taskparams->endtime) || (isset($task->taskparams->endtime) && $task->taskparams->endtime <= $taskparams->endtime))
        ) {
            // all time covered, alter keywords
            if (!empty($kwdiff)) {
                $task->taskparams->keywords = $kwdiff;
                $tasks[$idx] = $task;
            } else {
                array_splice($tasks, $idx, 1);
                $idx--;
            }
        } else {
            if (!empty($kwdiff)) {
                // slice task into diff keywords and intersect keywords
                $kwtask = deepClone($task);
                $kwtask->taskparams->keywords = $kwdiff;
                array_push($tasks, $kwtask);
                $task->taskparams->keywords = $kwinter;
            }
            if (isset($taskparams->starttime) && (!isset($task->taskparams->starttime) || $taskparams->starttime > $task->taskparams->starttime) &&
                isset($taskparams->endtime) && (!isset($task->taskparams->endtime) || $taskparams->endtime < $task->taskparams->endtime)
            ) {
                // slice task into two ranges
                $tmtask = deepClone($task);
                $tmtask->taskparams->starttime = $taskparams->endtime;
                array_push($tasks, $tmtask);
                $task->taskparams->endtime = $taskparams->starttime;
            } else {
                if (!isset($taskparams->starttime) || (isset($task->taskparams->starttime) && $task->taskparams->starttime >= $taskparams->starttime)) {
                    $task->taskparams->starttime = $taskparams->endtime;
                }
                if (!isset($taskparams->endtime) || (isset($task->taskparams->endtime) && $task->taskparams->endtime <= $taskparams->endtime)) {
                    $task->taskparams->endtime = $taskparams->starttime;
                }
            }
            $tasks[$idx] = $task;
        }
    }
    return $dup;
}

function sliceWeiboTask(&$tasks, $taskparams)
{
    global $logger;
    $dup = 0;
    for ($idx = 0; $idx < count($tasks); $idx++) {
        $task = $tasks[$idx];
        if ($task->taskparams->source != $taskparams->source) {
            continue;
        }
        if (empty($task->taskparams->users) || empty($taskparams->users)) {
            continue;
        }
        if ($task->taskparams->inputtype != $taskparams->inputtype) {
            continue;
        }
        if ((isset($taskparams->starttime) && isset($task->taskparams->endtime) && $taskparams->starttime >= $task->taskparams->endtime) ||
            (isset($taskparams->endtime) && isset($task->taskparams->starttime) && $taskparams->endtime <= $task->taskparams->starttime)
        ) {
            continue;
        }
        $ttuser_id = array();
        if ($task->taskparams->inputtype == "screen_name") {
            if (!empty($task->taskparams->userids)) {
                foreach ($task->taskparams->users as $ti => $titem) {
                    $id = $task->taskparams->userids[$ti];
                    $ttuser_id[] = array("screen_name" => $titem, "id" => $id);
                }
            }
        }
        $tuser_id = array();
        if ($taskparams->inputtype == "screen_name") {
            if (!empty($task->userids)) {
                foreach ($task->users as $ti => $titem) {
                    $id = $task->userids[$ti];
                    $tuser_id[] = array("screen_name" => $titem, "id" => $id);
                }
            }
        }
        //users 中可能为用户名数组 或是 用户id数组
        $userinter = array_merge(array_intersect($task->taskparams->users, $taskparams->users));
        if ($task->taskparams->inputtype == "id") {
            if (empty($userinter)) {
                continue;
            }
        }
        //usersid 为id数组 当users为用户名数组时 usersid存对应的id
        if (!empty($task->taskparams->userids) && !empty($taskparams->userids)) {
            $useridsinter = array_merge(array_intersect($task->taskparams->userids, $taskparams->userids));
        }
        if (empty($useridsinter)) {
            continue;
        } else {
            $userinter = array();
            foreach ($useridsinter as $ui => $uitem) {
                if (!empty($ttuser_id)) {
                    foreach ($ttuser_id as $ti => $titem) {
                        if ($titem["id"] == $uitem) {
                            if (!in_array($titem["screen_name"], $userinter)) {
                                $userinter[] = $titem["screen_name"];
                            }
                        }
                    }
                }
            }
        }
        $dup = 1;
        $userdiff = array_merge(array_diff($task->taskparams->users, $taskparams->users));
        if (!empty($task->taskparams->userids) && !empty($taskparams->userids)) { //users数组为用户名时存在id数组, 以id数组为准
            $useridsdiff = array_merge(array_diff($task->taskparams->userids, $taskparams->userids));
            $userdiff = array();
            foreach ($useridsdiff as $ui => $uitem) {
                foreach ($ttuser_id as $ti => $titem) {
                    if ($titem["id"] == $uitem) {
                        if (!in_array($titem["screen_name"], $userdiff)) {
                            $userdiff[] = $titem["screen_name"];
                        }
                    }
                }
                foreach ($tuser_id as $ti => $titem) {
                    if ($titem["id"] == $uitem) {
                        if (!in_array($titem["screen_name"], $userdiff)) {
                            $userdiff[] = $titem["screen_name"];
                        }
                    }
                }
            }
        } else { //当存在用户名数组不存在用户id数组时, 不拆分,创建新任务
            continue;
        }
        if ((!isset($taskparams->starttime) || (isset($task->taskparams->starttime) && $task->taskparams->starttime >= $taskparams->starttime)) &&
            (!isset($taskparams->endtime) || (isset($task->taskparams->endtime) && $task->taskparams->endtime <= $taskparams->endtime))
        ) {
            // all time covered, alter keywords
            if (!empty($userdiff)) {
                $task->taskparams->users = $userdiff;
                if (!empty($useridsdiff)) {
                    $task->taskparams->userids = $useridsdiff;
                }
                $tasks[$idx] = $task;
            } else {
                array_splice($tasks, $idx, 1);
                $idx--;
            }
        } else {
            if (!empty($userdiff)) {
                // slice task into diff keywords and intersect keywords
                $usertask = deepClone($task);
                $usertask->taskparams->users = $userdiff;
                if ($useridsdiff) {
                    $usertask->taskparams->userids = $useridsdiff;
                }
                array_push($tasks, $usertask);
                $task->taskparams->users = $userinter;
                if (!empty($useridsinter)) {
                    $task->taskparams->userids = $useridsinter;
                }
            }
            if (isset($taskparams->starttime) && (!isset($task->taskparams->starttime) || $taskparams->starttime > $task->taskparams->starttime) &&
                isset($taskparams->endtime) && (!isset($task->taskparams->endtime) || $taskparams->endtime < $task->taskparams->endtime)
            ) {
                // slice task into two ranges
                $tmtask = deepClone($task);
                $tmtask->taskparams->starttime = $taskparams->endtime;
                array_push($tasks, $tmtask);
                $task->taskparams->endtime = $taskparams->starttime;
            } else {
                if (!isset($taskparams->starttime) || (isset($task->taskparams->starttime) && $task->taskparams->starttime >= $taskparams->starttime)) {
                    $task->taskparams->starttime = $taskparams->endtime;
                }
                if (!isset($taskparams->endtime) || (isset($task->taskparams->endtime) && $task->taskparams->endtime <= $taskparams->endtime)) {
                    $task->taskparams->endtime = $taskparams->starttime;
                }
            }
            $tasks[$idx] = $task;
        }
    }
    return $dup;
}

function sliceRepostTask(&$tasks, $taskparams)
{
    $dup = 0;
    for ($idx = 0; $idx < count($tasks); $idx++) {
        $task = $tasks[$idx];
        if ($task->taskparams->source != $taskparams->source) {
            continue;
        }
        if (empty($task->taskparams->oristatus) || empty($taskparams->oristatus)) {
            continue;
        }
        $statusinter = array_merge(array_intersect($task->taskparams->oristatus, $taskparams->oristatus));
        if (empty($statusinter)) {
            continue;
        }
        $dup = 1;
        $statusdiff = array_merge(array_diff($task->taskparams->oristatus, $taskparams->oristatus));
        if (!empty($statusdiff)) {
            $task->taskparams->oristatus = $statusdiff;
            $tasks[$idx] = $task;
        } else {
            array_splice($tasks, $idx, 1);
            $idx--;
        }
    }
    return $dup;
}

function findDependTask(&$taskobj)
{
    global $dsql, $logger;
    $depid = 0;
    $deptasks = array();
    switch ($taskobj->task) {
        case TASK_COMMENTS:
            $deptasks[] = TASK_IMPORTWEIBOURL;
            break;
        default:
            break;
    }
    if (empty($deptasks)) {
        return $depid;
    }
    $limitcursor = 0;
    $eachcount = 10;
    while (1) {
        $sql = "select * from task where task in (" . implode(",", $deptasks) . ") order by id limit {$limitcursor},{$eachcount}";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
            $depid = false;
            break;
        } else {
            $r_count = $dsql->GetTotalRow($qr);
            if ($r_count == 0) {
                break;
            }
            while ($t = $dsql->GetObject($qr)) {
                $t->taskparams = json_decode($t->taskparams);
                switch ($taskobj->task) {
                    case TASK_COMMENTS:
                        if ($t->task == TASK_IMPORTWEIBOURL) {
                            if (isset($t->taskparams->depend) && $t->taskparams->depend == $taskobj->id) {
                                $depid = $t->id;
                                $dsql->FreeResult($qr);
                                break 3;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
            $dsql->FreeResult($qr);
        }
        if ($r_count < $eachcount) {
            break;
        }
        $limitcursor += $eachcount;
    }
    return $depid;
}
