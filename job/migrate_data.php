<?php
define("SELF", basename(__FILE__));
define("MAX_HANDLE_RECORDS", 5000);
define("MAX_DEL_CHECK_RECORDS", 8000);

//在删除文章或者用户时候 是否进行 引用/依赖 关系检查 默认不检查
define("CHECK_DOC_USER_DEPEND_DEL", false);


if ($argc > 1) {
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
}

include_once('includes.php');
include_once('taskcontroller.php');
include_once('jobfun.php');
ini_set('include_path', get_include_path() . '/lib');

ini_set("memory_limit", "1024M");

initLogger(LOGNAME_MIGRATEDATA);

$delete_status_count = 0;
$delete_user_count = 0;
$solr_query_time = 0;
$solr_retrieve_time = 0;
$solr_delete_time = 0;
$db_query_time = 0;
$db_retrieve_time = 0;
$db_delete_time = 0;

$dst_insert_status_count = 0;
$dst_update_status_count = 0;
$dst_insert_user_count = 0;
$dst_update_user_count = 0;
$dst_solr_query_time = 0;
$dst_solr_insert_time = 0;
$dst_solr_update_time = 0;
$dst_db_query_time = 0;
$dst_db_insert_time = 0;
$dst_db_update_time = 0;

$srchost = (object)array();
$dsthosts = array();
$commitnum = "5000"; //commit条数限制。 五千条commit为true一次。
/**
 * 在删除数据时候，由于是分批删除，如:总共需要删除1万条数据，每次最大删除1000条数据
 * 在第一批需要删除的数据中，可能在这1000条数据之外有其他的文章引用这批文章中的某些文章，那么需要先把被别人引用的这些文章暂时保留,等待删除完成后再次检查这些文章
 */

/*
 *  2017/3/29
 *
 *  以前的实时入solr，commit为true，频繁提交，solr会挂掉，所以进行了修改
 *  思路为批量入solr，以前查一次就commit为true的提交一次，改为，指定条数之后，进行一次commit为true的提交，条数由变量$commitnum控制
 *  最后循环结束时，会进行一次flush，把全部commit为false的数据，刷新到磁盘。
 * */
$kepDocIds = array();
$kepUserIds = array();

$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
try {
    $logger->debug(SELF . " - get task from DBserver:[" . DATABASE_SERVER . "] DBName:[" . DATABASE_WEIBOINFO . "].");

    $task = getLocalTask(TASKTYPE_MIGRATE, TASK_MIGRATEDATA);

    if (empty($task)) {
        $logger->debug(SELF . " - 未找到待启动任务,退出");
        exit;
    }
    startTask($task);
    $logger->info(SELF . " - 任务{$task->id}启动");
    $rt = detectConflictTask($task);
    if (!$rt['result']) {
        stopTask($task);
        $logger->error(SELF . "- 任务{$task->id}冲突检测失败 -" . $rt['msg']);
        exit;
    } else if (!$rt['continue']) {
        $logger->info(SELF . " - 任务{$task->id}冲突，延迟启动");
        exit;
    }
    $r = execute();
    if ($r) {
        completeTask($task);
        $logger->info(SELF . " - 任务{$task->id}完成");
    } else {
        stopTask($task);
        $logger->info(SELF . " - 任务{$task->id}停止");
    }
} catch (Exception $ex) {
    fatalTask($task);
    $logger->fatal(SELF . " - 任务{$task->id}异常" . $ex->getMessage());
    exit;
}
exit;

/*
 * 执行任务
 */
function execute()
{
    global $logger, $task,$dsthosts;
    global $g_kepDocIds, $g_kepUserIds;

    if (!connectDBs()) {
        $logger->error(SELF . " - 连接数据库失败");
        return false;
    }

    $task->taskparams->scene->status_desp = "";
    if (!isset($task->taskparams->select_cursor)) {
        $task->taskparams->select_cursor = $task->taskparams->offset;
    }
    if (!isset($task->taskparams->scene)) {
        $task->taskparams->scene = (object)array();
    }
    if (!isset($task->taskparams->scene->delete_status_count)) {
        $task->taskparams->scene->delete_status_count = 0;
    }
    if (!isset($task->taskparams->scene->delete_user_count)) {
        $task->taskparams->scene->delete_user_count = 0;
    }
    if (!isset($task->taskparams->scene->alltime)) {
        $task->taskparams->scene->alltime = 0;
    }
    if (!isset($task->taskparams->scene->solr_time)) {
        $task->taskparams->scene->solr_time = 0;
    }
    if (!isset($task->taskparams->scene->solr_query_time)) {
        $task->taskparams->scene->solr_query_time = 0;
    }
    if (!isset($task->taskparams->scene->solr_retrieve_time)) {
        $task->taskparams->scene->solr_retrieve_time = 0;
    }
    if (!isset($task->taskparams->scene->solr_delete_time)) {
        $task->taskparams->scene->solr_delete_time = 0;
    }
    if (!isset($task->taskparams->scene->db_time)) {
        $task->taskparams->scene->db_time = 0;
    }
    if (!isset($task->taskparams->scene->db_query_time)) {
        $task->taskparams->scene->db_query_time = 0;
    }
    if (!isset($task->taskparams->scene->db_retrieve_time)) {
        $task->taskparams->scene->db_retrieve_time = 0;
    }
    if (!isset($task->taskparams->scene->db_delete_time)) {
        $task->taskparams->scene->db_delete_time = 0;
    }
    if (!isset($task->taskparams->scene->dst) && !empty($task->taskparams->dsthost)) {
        $task->taskparams->scene->dst = array();
        $dstn = count($task->taskparams->dsthost);
        for ($i = 0; $i < $dstn; $i++) {
            $dst = (object)array();
            $dst->insert_status_count = 0;
            $dst->update_status_count = 0;
            $dst->insert_user_count = 0;
            $dst->update_user_count = 0;
            $dst->solr_time = 0;
            $dst->solr_query_time = 0;
            $dst->solr_insert_time = 0;
            $dst->solr_update_time = 0;
            $dst->db_time = 0;
            $dst->db_query_time = 0;
            $dst->db_insert_time = 0;
            $dst->db_update_time = 0;
            $task->taskparams->scene->dst[] = $dst;
        }
    }

    //$logger->info(SELF . " - 迁移数据for task:[" . var_export($task, true) . "] ...");

    $r = true;
    $start_t = microtime_float();
    while (true) {
        if (isset($task->taskparams->maxcount)) {
            $rest = $task->taskparams->maxcount - $task->datastatus;
            $goal = $rest > $task->taskparams->eachcount ? $task->taskparams->eachcount : $rest;
        } else {
            $goal = $task->taskparams->eachcount;
        }
        $count = migrateData($goal);
        if ($count < 0) {
            $r = false;
            break;
        }
        if ($count == 0 || $count < $goal) {
            break;
        }
        $st = getTaskStatus($task->id);
        if ($st == -1) {
            $logger->info(SELF . " - 人工停止");
            $task->taskparams->scene->status_desp = "人工停止";
            $r = false;
            break;
        } else {
            $end_t = microtime_float();
            $task->taskparams->scene->alltime += $end_t - $start_t;
            $start_t = $end_t;
            updateTask($task);
        }
    }
    $res = flushData($dsthosts['0']->solr);
    $logger->info("the flush res is:".var_export($res,true));
    //做最后的数据关联依赖 清理


//    if ($r) {
//        //$r = checkPending();
//    }
//    global $g_kepDocIds, $g_kepUserIds;
//    $task->datastatus += $count;
//    $logger->info(SELF . " - 迁移数据for task 完成,需要保留文章:[" . count($g_kepDocIds) . "]条, 用户:[" . count($g_kepUserIds) . "]条!");
    $logger->info(SELF . " - 迁移数据for task 完成,需要保留文章:[" . count($g_kepDocIds) . "]条, 用户:[" . count($g_kepUserIds) . "]条,已经迁移数据:[" . $task->datastatus . "] 条!");
    $end_t = microtime_float();
    $task->taskparams->scene->alltime += $end_t - $start_t;
    return $r;
}

function connectDBs()
{
    global $logger, $task, $dsql, $srchost, $dsthosts;
    $logger->debug("enter " . __FUNCTION__);
    if (!empty($task->taskparams->srchost)) {
        $host = getHostById($task->taskparams->srchost);
        if (empty($host)) {
            $logger->error(__FUNCTION__ . "数据主机" . $task->taskparams->srchost . "不存在");
            $logger->debug("exit " . __FUNCTION__);
            return false;
        }

        if (isset($host['dbname']) && !empty($host['dbname'])) {
            $dbName = $host['dbname'];
        } else {
            $dbName = DATABASE_WEIBOINFO;
        }
        $srchost->dsql = new DB_MYSQL($host['dbserver'], $host['username'], $host['password'], $dbName, FALSE, FALSE, FALSE);
        $srchost->solr = trim($host['solrstore']);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-连接源主机--+-源主机Dbserver:[" . $host['dbserver'] . " DBName:[" . $dbName . "] solrHost:[" . $srchost->solr . "].");
    } else {
        $srchost->dsql = $dsql;
        $srchost->solr = trim(SOLR_STORE);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-连接源主机--+-源主机没有配置，将当前机器作为源主机,Dbserver:[" . DATABASE_SERVER . " DBName:[" . DATABASE_WEIBOINFO . "] solrHost:[" . $srchost->solr . "].");
    }
    if ($srchost->solr[strlen($srchost->solr) - 1] != '/') {
        $srchost->solr .= '/';
    }
    if (!empty($task->taskparams->dsthost)) {
        foreach ($task->taskparams->dsthost as $hostid) {
            if ($hostid != 0) {
                $host = getHostById($hostid);
                if (empty($host)) {
                    $logger->error(__FUNCTION__ . "数据主机" . $hostid . "不存在");
                    $logger->debug("exit " . __FUNCTION__);
                    return false;
                }
                $dsthost = (object)array();

                if (isset($host['dbname']) && !empty($host['dbname'])) {
                    $dbName = $host['dbname'];
                } else {
                    $dbName = DATABASE_WEIBOINFO;
                }
                $dsthost->dsql = new DB_MYSQL($host['dbserver'], $host['username'], $host['password'], $dbName, FALSE, FALSE, FALSE);
                $dsthost->solr = trim($host['solrstore']);
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-连接目标主机--+-目标主机Dbserver:[" . $host['dbserver'] . " DBName:[" . $dbName . "] solrHost:[" . $dsthost->solr . "].");
            } else {
                $dsthost = (object)array();
                $dsthost->dsql = $dsql;
                $dsthost->solr = trim(SOLR_STORE);
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-连接目标主机--+-目标主机没有配置，将当前机器作为目标主机,Dbserver:[" . DATABASE_SERVER . " DBName:[" . DATABASE_WEIBOINFO . "] solrHost:[" . $dsthost->solr . "].");
            }
            if ($dsthost->solr[strlen($dsthost->solr) - 1] != '/') {
                $dsthost->solr .= '/';
            }
            $dsthosts[] = $dsthost;
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    return true;
}

function migrateData($goal)
{
    global $logger, $task, $srchost, $dsthosts,
           $delete_status_count, $delete_user_count,
           $solr_query_time, $solr_retrieve_time, $solr_delete_time, $db_retrieve_time, $db_delete_time,
           $dst_insert_status_count, $dst_update_status_count, $dst_insert_user_count, $dst_update_user_count,
           $dst_solr_query_time, $dst_solr_insert_time, $dst_solr_update_time, $dst_db_query_time, $dst_db_insert_time, $dst_db_update_time,$commitnum;

    $logger->debug("enter " . __FUNCTION__);
    if ($goal <= 0) {
        $logger->debug("exit " . __FUNCTION__);
        return 0;
    }
    $delete_status_count = 0;
    $delete_user_count = 0;
    $solr_query_time = 0;
    $solr_retrieve_time = 0;
    $solr_delete_time = 0;
    $db_retrieve_time = 0;
    $db_delete_time = 0;
    $res = &exportData($srchost, $goal);
    if (!$res['result']) {
        $logger->debug("exit " . __FUNCTION__);
        return -1;
    }
    $count = $res['count'];
    if ($count > 0) {
        foreach ($dsthosts as $idx => $dst) {
            $dst_insert_status_count = 0;
            $dst_update_status_count = 0;
            $dst_insert_user_count = 0;
            $dst_update_user_count = 0;
            $dst_solr_query_time = 0;
            $dst_solr_insert_time = 0;
            $dst_solr_update_time = 0;
            $dst_db_query_time = 0;
            $dst_db_insert_time = 0;
            $dst_db_update_time = 0;
            //修改默认入solr方式，commit默认为false,5000整数倍时，commit为true，加入$commit参数。
            if($task->taskparams->select_cursor%$commitnum == 0 & $task->taskparams->select_cursor != 0){
                $commit = true;
            }else{
                $commit = false;
            }
            $logger->info("the  commit is:".var_export($commit,true));
            $ret = importData($dst, $res['data'],$commit);
            if (!$ret['result']) {
                $logger->error(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-迁移失败--+-导入数据异常,All message:[" . var_export($ret, true) . "].");
                return -1;
            }
            //----end
            $task->taskparams->scene->dst[$idx]->insert_status_count += $dst_insert_status_count;
            $task->taskparams->scene->dst[$idx]->update_status_count += $dst_update_status_count;
            $task->taskparams->scene->dst[$idx]->insert_user_count += $dst_insert_user_count;
            $task->taskparams->scene->dst[$idx]->update_user_count += $dst_update_user_count;
            $task->taskparams->scene->dst[$idx]->solr_time += $dst_solr_query_time + $dst_solr_insert_time + $dst_solr_update_time;
            $task->taskparams->scene->dst[$idx]->solr_query_time += $dst_solr_query_time;
            $task->taskparams->scene->dst[$idx]->solr_insert_time += $dst_solr_insert_time;
            $task->taskparams->scene->dst[$idx]->solr_update_time += $dst_solr_update_time;
            $task->taskparams->scene->dst[$idx]->db_time += $dst_db_query_time + $dst_db_insert_time + $dst_db_update_time;
            $task->taskparams->scene->dst[$idx]->db_query_time += $dst_db_query_time;
            $task->taskparams->scene->dst[$idx]->db_insert_time += $dst_db_insert_time;
            $task->taskparams->scene->dst[$idx]->db_update_time += $dst_db_update_time;
        }
        if ($task->taskparams->keepsrc) {
//            $logger->error(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-迁移成功--+-保留原始数据!");
            $task->taskparams->select_cursor += $count;

//            echo("test exit!");
//            exit;

        } else {
//            $logger->error(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-迁移成功--+-删除原始数据!");
            //*************测试数据*****=== 断点 ===********//
//            echo("test exit!");
//            exit;
            //*************测试数据*****=== 断点 ===********//

            $ret = deleteData($srchost, $res['data']);
            if (!$ret['result']) {
                $logger->debug("exit " . __FUNCTION__);
                return -1;
            }
        }
    }

    //******************** 释放所有临时变量**********//
    unset($res['data']['keydata']['ids']);
    unset($res['data']['keydata']['cids']);
    unset($res['data']['keydata']['seedids']);
    unset($res['data']['keydata']['uids']);
    unset($res['data']['keydata']['seeduids']);
    unset($res['data']['keydata']['allids']);
    unset($res['data']['keydata']['alluids']);
    unset($res['data']['keydata']);

    unset($res['data']['solrdata']['weibos']);
    unset($res['data']['solrdata']['users']);
    unset($res['data']['solrdata']);

    unset($res['data']['dbdata']['weibos']);
    unset($res['data']['dbdata']['comments']);
    unset($res['data']['dbdata']['users']);
    unset($res['data']['dbdata']);
    unset($res['data']);

    unset($res);

    $task->datastatus += $count;
    $task->taskparams->scene->delete_status_count += $delete_status_count;
    $task->taskparams->scene->delete_user_count += $delete_user_count;
    $task->taskparams->scene->solr_time += $solr_query_time + $solr_retrieve_time + $solr_delete_time;
    $task->taskparams->scene->solr_query_time += $solr_query_time;
    $task->taskparams->scene->solr_retrieve_time += $solr_retrieve_time;
    $task->taskparams->scene->solr_delete_time += $solr_delete_time;
    $task->taskparams->scene->db_time += $db_retrieve_time + $db_delete_time;
    $task->taskparams->scene->db_retrieve_time += $db_retrieve_time;
    $task->taskparams->scene->db_delete_time += $db_delete_time;
    $logger->debug("exit " . __FUNCTION__);
    return $count;
}


function &getDocByLimit($q, $startIdx, $docNum, $order, $url)
{
    global $logger;

    //将数据量大的任务拆分开
    if ($docNum > MAX_HANDLE_RECORDS) {
        $resultWeibos = array();

        $hasGetNum = 0; // 已经获取到的文档数
        $curStarIdx = $startIdx; //本次获取的其实索引
        $docNumPer = MAX_HANDLE_RECORDS;

        while ($hasGetNum < $docNum) {

            $curTatNum = $docNum - $hasGetNum;
            if ($curTatNum > $docNumPer) {
                $curTatNum = $docNumPer;
            }

            $weibos = solr_retrieve($q, $curStarIdx, $curTatNum, $order, $url);

            if ($weibos == false) {
                throw new Exception("solr_retrieve faild!");
            }

            //cur get num
            $curGetNum = count($weibos);

            $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-getDocByLimit--+-分批获取数据" . $curGetNum . "条!");

            if ($curGetNum > 0) {
//                foreach ($weibos as $weibo) {
//                    $resultWeibos[] = $weibo;
//                }
//                $resultWeibos = $resultWeibos + $weibos;
                $resultWeibos = array_merge($resultWeibos, $weibos);
            }

            unset($weibos);
//            $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-getDocByLimit--+-分批获取数据" . $curGetNum . "条 Data:[" . var_export($resultWeibos, true));

            $hasGetNum = $hasGetNum + $curGetNum;
            $curStarIdx = $curStarIdx + $curGetNum;
            if ($curGetNum < $curTatNum) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-getDocByLimit--+-本地获取到的文档数:{$curGetNum} 小于目标数:{$curTatNum} 推出,循环获取!");
                break;
            }
        }
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-getDocByLimit--+-分批获取数据完成，总共:" . count($resultWeibos) . "条!");
        return $resultWeibos;
    } else {
        $weibos = solr_retrieve($q, $startIdx, $docNum, $order, $url);
        return $weibos;
    }
}

function &exportData(&$src, $goal)
{
    global $logger, $task,
           $solr_retrieve_time, $db_retrieve_time;
    $logger->debug("enter " . __FUNCTION__);
    $result = array('result' => true, 'count' => 0, 'data' => array('keydata' => array(), 'dbdata' => array(), 'solrdata' => array()), 'msg' => '');

    $limitMem = ini_get('memory_limit');

    // modified by wangcc
    if ($task->taskparams->cond_in_customquery) {
        //使用用户自定义 的查询条件
        $q = $task->taskparams->cond_in_customquery;
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-用户自定义的查询条件:[" . $q . "] limitMem:{$limitMem}.");
    } else {
        $q = getQuery4Migrate($task->taskparams);
        //$q = getQuery4Migrate($task->taskparams);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-生成查询条件:[" . $q . "].");
    }

    $order = "";
//    if (isset($task->taskparams->orderby)) {
//        if ($task->taskparams->orderby == "created") {
//            $order = "created_at " . $task->taskparams->order;
//        }
//    }
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-用户自定义的查询条件--+-排序条件:{$order}" . " TaskParam order:[" . $task->taskparams->orderby . "].");


    //根据查询条件 从solr中获取 需要迁移的数据
    $url = $src->solr . SOLR_PARAM_RETRIEVE;
    $start_t = microtime_float();

//    $weibos = solr_retrieve($q, $task->taskparams->select_cursor, $goal, $order, $url);
    $weibos = &getDocByLimit($q, $task->taskparams->select_cursor, $goal, $order, $url);

    $end_t = microtime_float();
    $solr_retrieve_time += $end_t - $start_t;
    if ($weibos === false) {
        $result['result'] = false;
        $result['msg'] = "solr提取微博数据失败";
        $logger->error(__FUNCTION__ . " " . $result['msg']);
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    if (empty($weibos)) {
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    $result['count'] = count($weibos);

    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件从solr中迁移数据:[" . $result['count'] . "]条!");

    $ids = array(); //solr-原创 的 guid
    $cids = array();//solr-评论 的 guid
    $origids = array(); //solr-原贴 的 guid
    $uids = array();
    foreach ($weibos as $weibo) {
        if (!isset($weibo['guid'])) {
            $logger->error("the pro:[guid] for weibo is null,weiboData:[" . var_export($weibo, true) . "]." . __FUNCTION__);
            throw new Exception("property:[guid] is miss!");
        }
        if (isset($weibo['content_type'])) {
            if ($weibo['content_type'] == "2") {
                $cids[] = $weibo['guid'];
            } else {
                $ids[] = $weibo['guid'];
            }
            if ($weibo['content_type'] == "1" || $weibo['content_type'] == "2") {
                if (isset($weibo['retweeted_guid']) && array_search($weibo['retweeted_guid'], $origids) === false) {
                    $origids[] = $weibo['retweeted_guid'];
                }
            }
        } else {
            //单独迁移用户时候
            $ids[] = $weibo['guid'];
            $logger->error("迁移用户:[" . $weibo['guid'] . "]." . __FUNCTION__);
        }
        //用户guid
        if (isset($weibo['userid']) && array_search($weibo['userid'], $uids) === false) {
            if (empty($weibo['userid']) && $weibo['userid'] !== 0) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-从solr中文章中提取userid异常，weibo['userid'] is null:{$weibo['userid']}");
                throw new Exception("weibo.userId is null!");
            }
            $uids[] = $weibo['userid'];
        }
    }

    if (!empty($task->taskparams->dsthost)) {
        // do not need original for merely dropping data
        if (!empty($origids)) { //从 去除已经包含在 ids 中的文章
            $origids = array_merge(array_diff($origids, $ids));
        }
        if (!empty($origids)) {
            $start_t = microtime_float();
            $seleConds = array('guid' => $origids);
            if (isset($task->taskparams->source_host) && !empty($task->taskparams->source_host)) {
                $seleConds['source_host'] = $task->taskparams->source_host;
            }
            $origweibos = solr_retrieve($seleConds, 0, -1, "", $url);
            $end_t = microtime_float();
            $solr_retrieve_time += $end_t - $start_t;
            if ($origweibos === false) {
                $result['result'] = false;
                $result['msg'] = "solr提取额外的原创数据失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
            // original weibo for comment could be repost
            if (!empty($origweibos)) {
                $oids = array();
                foreach ($origweibos as $weibo) {
                    if ($weibo['content_type'] == "1") {
                        if (isset($weibo['retweeted_guid']) && array_search($weibo['retweeted_guid'], $oids) === false) {
                            $oids[] = $weibo['retweeted_guid'];
                        }
                    }
                }
                if (!empty($oids)) {
                    $oids = array_merge(array_diff($oids, $ids, $origids));
                }
                if (!empty($oids)) {
                    $start_t = microtime_float();

                    $seleConds = array('guid' => $oids);
                    if (isset($task->taskparams->source_host) && !empty($task->taskparams->source_host)) {
                        $seleConds['source_host'] = $task->taskparams->source_host;
                    }
//                    array('guid' => $oids, 'source_host' => $task->taskparams->source_host)
                    $oweibos = solr_retrieve($seleConds, 0, -1, "", $url);
                    $end_t = microtime_float();
                    $solr_retrieve_time += $end_t - $start_t;
                    if ($oweibos === false) {
                        $result['result'] = false;
                        $result['msg'] = "solr提取额外的额外的原创数据失败";
                        $logger->error(__FUNCTION__ . " " . $result['msg']);
                        $logger->debug("exit " . __FUNCTION__);
                        return $result;
                    }
                    $origweibos = array_merge($origweibos, $oweibos);
                }
            }
        }
    }
    $allids = $ids;
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-补充原文前文章个数:[" . count($ids) . "]条!");

    $alluids = $uids;
    if (!empty($origweibos)) {
        $weibos = array_merge($weibos, $origweibos);
        foreach ($origweibos as $weibo) {
            $allids[] = $weibo['guid']; //所有的都根据guid为唯一的主键 所有的文章都有guid
            if (isset($weibo['userid']) && array_search($weibo['userid'], $alluids) === false) {
                $alluids[] = $weibo['userid'];
            }
        }
    }

    $users = array();
    if (!empty($alluids)) {
        $start_t = microtime_float();

        $seleConds = array('users_id' => $alluids);
        if (isset($task->taskparams->users_source_host) && !empty($task->taskparams->users_source_host)) {
            $seleConds['users_source_host'] = $task->taskparams->users_source_host;
        }
//        array('users_id' => $alluids, 'users_source_host' => $task->taskparams->users_source_host)

        $users = solr_retrieve($seleConds, 0, -1, "", $url);
        $end_t = microtime_float();
        $solr_retrieve_time += $end_t - $start_t;
        if ($users === false) {
            $result['result'] = false;
            $result['msg'] = "solr提取用户数据失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
    }

    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件需要从solr中共获取文章Id数据:[" . count($allids) . "]条!");
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件需要从solr中共获取用户Id数据:[" . count($alluids) . "]条!");
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件需要从solr中共获取评论Id数据:[" . count($cids) . "]条!");
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件从solr中共获取用户数据:[" . count($users) . "]条 id:[" . count($alluids) . "]个!");

    $start_t = microtime_float();
    $dbweibos = db_retrieve($src->dsql, DATABASE_WEIBO, $allids);
    $end_t = microtime_float();
    $db_retrieve_time += $end_t - $start_t;
    if ($dbweibos === false) {
        $result['result'] = false;
        $result['msg'] = "数据库提取微博失败";
        $logger->error(__FUNCTION__ . " " . $result['msg']);
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件从DB中共获取文章数据:[" . count($dbweibos) . "]条 id:[" . count($allids) . "]个!");

    $start_t = microtime_float();
    $dbcomments = db_retrieve($src->dsql, DATABASE_COMMENT, $cids);
    $end_t = microtime_float();
    $db_retrieve_time += $end_t - $start_t;
    if ($dbcomments === false) {
        $result['result'] = false;
        $result['msg'] = "数据库提取评论失败";
        $logger->error(__FUNCTION__ . " " . $result['msg']);
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件从DB中共获取评论数据:[" . count($dbcomments) . "]条" . "id:[" . count($cids) . "]个!");

    $start_t = microtime_float();
    $dbusers = db_retrieve($src->dsql, DATABASE_USER, $alluids, "id");
    $end_t = microtime_float();
    $db_retrieve_time += $end_t - $start_t;
    if ($dbusers === false) {
        $result['result'] = false;
        $result['msg'] = "数据库提取用户失败";
        $logger->error(__FUNCTION__ . " " . $result['msg']);
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-exportData--+-本次根据迁移条件从DB中共获取用户数据:[" . count($dbusers) . "]条" . "id:[" . count($alluids) . "]个!");

    $seedids = array();
    foreach ($dbweibos as $weibo) {
        if (!empty($weibo['isseed']) && array_search($weibo['id'], $ids) !== false) {
            $seedids[] = $weibo['id'];
        }
    }
    $seeduids = array();
    foreach ($users as $user) {
        if (!empty($user['users_seeduser']) && array_search($user['users_id'], $uids) !== false) {
            $seeduids[] = $user['users_id'];
        }
    }

    $result['data']['keydata']['ids'] = &$ids; // matched weibo ids (to delete)
    $result['data']['keydata']['cids'] = &$cids; // comment ids
    $result['data']['keydata']['seedids'] = &$seedids;
    $result['data']['keydata']['uids'] = &$uids;
    $result['data']['keydata']['seeduids'] = &$seeduids;
    $result['data']['keydata']['allids'] = &$allids; // all weibo ids including dependent (to migrate)
    $result['data']['keydata']['alluids'] = &$alluids;
    $result['data']['solrdata']['weibos'] = &$weibos; // weibos and comments
    $result['data']['solrdata']['users'] = &$users;
    $result['data']['dbdata']['weibos'] = &$dbweibos;
    $result['data']['dbdata']['comments'] = &$dbcomments;
    $result['data']['dbdata']['users'] = &$dbusers;
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function handleSolrDataRetry($reqData, $url, $maxRetryTime = 2)
{
    global $logger;
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-handleSolrData...maxRetryTime:{$maxRetryTime}");
    $ret = handle_solr_data($reqData, $url);

    if ($ret !== NULL) {
        //调用solr 失败
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-handleSolrData--+-调用solr失败将要重试,应答数据为:" . var_export($ret, true));
        $hasRetryTime = 0;
        for (; $hasRetryTime < $maxRetryTime; $hasRetryTime++) {
            $ret = handle_solr_data($reqData, $url);
            if ($ret == NULL) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-handleSolrData--+-重试第:" . ($hasRetryTime + 1) . "次成功!");
                break;
            }
        }
    }

    if ($ret !== NULL) {
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-handleSolrData--+-请求solr失败，并且重试失败!");
    }
    return $ret;
}

function importData($dst, &$data,$commit)
{
    global $logger, $task,
           $dst_insert_status_count, $dst_update_status_count, $dst_insert_user_count, $dst_update_user_count,
           $dst_solr_query_time, $dst_solr_insert_time, $dst_solr_update_time, $dst_db_query_time, $dst_db_insert_time, $dst_db_update_time;
    //$logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-本次导入数据:[" . var_export($data, true) . "].");

    $result = array('result' => true, 'msg' => '');

    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-导入数据solr路径:[" . $dst->solr . SOLR_PARAM_SELECT . "].");

    // filter existing users from solr
    $exusers = array();
    if (!empty($data['keydata']['alluids'])) {
        $url = $dst->solr . SOLR_PARAM_SELECT;
        $facet = "";
        $start_t = microtime_float();

        $conds = array('users_id' => $data['keydata']['alluids']);
        if (isset($task->taskparams->users_source_host) && !empty($task->taskparams->users_source_host)) {
            //array('users_id' => $data['keydata']['alluids'], 'users_source_host' => $task->taskparams->users_source_host)
            $conds['users_source_host'] = $task->taskparams->users_source_host;
        }

        $qr = solr_select_conds(array('users_id', 'users_user_updatetime'), $conds, 0, -1, '', '', $facet, $url);
        $end_t = microtime_float();
        $dst_solr_query_time += $end_t - $start_t;
        if ($qr === false) {
            $result['result'] = false;
            $result['msg'] = "solr查询用户失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        } else {
            $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-查询目标solr中已经存在的用户--+-已经存在用户:[" . count($qr) . "]条!");
        }
        foreach ($qr as $rec) {
            $exusers[$rec['users_id']] = isset($rec['users_user_updatetime']) ? (int)$rec['users_user_updatetime'] : 0;
        }
    }

    $users_in = array();
    $users_up = array();
    $dbusers_in = array();
    $dbusers_up = array();
    if (empty($exusers)) {//目标库中 不存在 任何一个 需要导入的用户(数据)
        $users_in = $data['solrdata']['users'];
        $dbusers_in = $data['dbdata']['users'];
    } else {  ///目标库中 存在 部分 需要导入的用户(数据)

        //在源solr中存在 同时在目标solr中的 并且 不需要 插入的数据
        $nousers = array();

        foreach ($data['solrdata']['users'] as $user) {
            if (isset($exusers[$user['users_id']])) {//目标库中存在当前用户
                if (!isset($user['users_user_updatetime']) || $exusers[$user['users_id']] >= (int)$user['users_user_updatetime']) {
                    $nousers[$user['users_id']] = true;
                } else {
                    $users_up[] = $user;//需要更新的用户 在目标库中存在 但是不是最新的 需要更新
                }
            } else {
                $users_in[] = $user;//目标中不存在
            }
        }

        foreach ($data['dbdata']['users'] as $user) {
            if (isset($nousers[$user['id']])) {
                continue;
            }
            if (isset($exusers[$user['id']])) {
                $dbusers_up[] = $user;
            } else {
                $dbusers_in[] = $user;
            }
        }
    }
    // filter existing weibos from db
    $exweibos = array();
    $sql = "select guid, update_time from " . DATABASE_WEIBO . " where guid in ('" . implode("','", $data['keydata']['allids']) . "')";
    $start_t = microtime_float();
    $qr = $dst->dsql->ExecQuery($sql);
    $end_t = microtime_float();
    $dst_db_query_time += $end_t - $start_t;
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dst->dsql->GetError());
        $result['result'] = false;
        $result['msg'] = "数据库查询微博失败";
        $logger->error(__FUNCTION__ . " " . $result['msg']);
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    } else {
        while ($rec = $dst->dsql->GetArray($qr)) {
//            $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-查询数据库中存在的文章--+-该文章已经存在:[" . var_export($rec, true));
            if (!empty($rec)) {
                $exweibos[$rec['guid']] = isset($rec['update_time']) ? (int)$rec['update_time'] : 0;
            }
        }
        $dst->dsql->FreeResult($qr);
    }
    // filter existing comments from db
    $excomments = array();
    if (!empty($data['keydata']['cids'])) {
        $sql = "select guid from " . DATABASE_COMMENT . " where guid in ('" . implode("','", $data['keydata']['cids']) . "')";
        $start_t = microtime_float();
        $qr = $dst->dsql->ExecQuery($sql);
        $end_t = microtime_float();
        $dst_db_query_time += $end_t - $start_t;
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dst->dsql->GetError());
            $result['result'] = false;
            $result['msg'] = "数据库查询平评论失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        } else {
            while ($rec = $dst->dsql->GetArray($qr)) {
                if (!empty($rec)) {
                    $excomments[$rec['guid']] = 1;
                }
            }
            $dst->dsql->FreeResult($qr);
        }
    }
    $weibos_in = array();
    $weibos_up = array();
    $dbweibos_in = array();
    $dbweibos_up = array();
    $dbcomments_in = array();
    //$dbcomments_up = array();// comments in db need not update
    if (empty($exweibos) && empty($excomments)) {
        $weibos_in = $data['solrdata']['weibos'];
        $dbweibos_in = $data['dbdata']['weibos'];
        $dbcomments_in = $data['dbdata']['comments'];
    } else {
        // weibos
        $noweibos = array();
        foreach ($data['dbdata']['weibos'] as $weibo) {
            if (isset($exweibos[$weibo['guid']])) {
                if (!isset($weibo['update_time']) || $exweibos[$weibo['guid']] >= (int)$weibo['update_time']) {
                    $noweibos[$weibo['guid']] = true;
                } else {
                    $dbweibos_up[] = $weibo;
                }
            } else {
                $dbweibos_in[] = $weibo;
            }
        }
//        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-查询所有已经存在的文章--+-existDoc:[" . var_export($exweibos, true));
//        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-查询所有已经存在的文章--+-Noweibos:[" . var_export($noweibos, true));

        // comments
        foreach ($data['dbdata']['comments'] as $comment) {
            if (!isset($excomments[$comment['guid']])) {
                $dbcomments_in[] = $comment;
            }
        }
        // weibos and comments
        foreach ($data['solrdata']['weibos'] as $weibo) {
            if (isset($noweibos[$weibo['guid']])) {
                continue;
            }
            //$logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " noweibo 中不包含 当前Id:[" . var_export($weibo['guid'], true));
            if ((isset($exweibos[$weibo['guid']]) && $weibo['content_type'] != "2") ||
                (isset($excomments[$weibo['guid']]) && $weibo['content_type'] == "2")
            ) {
                $weibos_up[] = $weibo;
            } else {
                $weibos_in[] = $weibo;
            }
        }
    }
    // import data
    if (!empty($users_in)) {
        $dst_insert_user_count += count($users_in);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标solr中插入用户:[" . count($users_in) . "]条...");
        //根据commit的值来决定commit是否true   by yu
        if($commit){
            $url = $dst->solr . SOLR_PARAM_INSERT . "&commit=true";
        }else{
            $url = $dst->solr . SOLR_PARAM_INSERT . "&commit=false";
        }
        //------   end
//        $url = $dst->solr . SOLR_PARAM_INSERT . "&commit=true";
        $start_t = microtime_float();

        if (count($users_in) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($users_in, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $userInData) {
//                $ret = handle_solr_data($userInData, $url);
                $ret = handleSolrDataRetry($userInData, $url);

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标solr中插入用户:[" . count($users_in) . "]条--+-分批插入...");

                if ($ret !== NULL) {
                    $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                    $result['result'] = false;
                    $result['msg'] = "分批solr插入用户失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标solr中插入用户:[" . count($users_in) . "]条--+-分批插入成功!");
            }
            unset($eachTaskdatas);

            $end_t = microtime_float();
            $dst_solr_insert_time += $end_t - $start_t;
        } else {
//            $ret = handle_solr_data($users_in, $url);
            $ret = handleSolrDataRetry($users_in, $url);

            $end_t = microtime_float();
            $dst_solr_insert_time += $end_t - $start_t;
            if ($ret !== NULL) {
                $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                $result['result'] = false;
                $result['msg'] = "solr插入用户失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($users_in);

    if (!empty($users_up)) {
        $dst_update_user_count += count($users_up);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-更新目标solr中的用户:[" . count($users_up) . "]条...");
        //根据commit的值来决定commit是否true   by yu
        if($commit){
            $url = $dst->solr . SOLR_PARAM_UPDATE . "&commit=true";
        }else{
            $url = $dst->solr . SOLR_PARAM_UPDATE . "&commit=false";
        }
        //------   end
//        $url = $dst->solr . SOLR_PARAM_UPDATE . "&commit=true";
        $start_t = microtime_float();

        if (count($users_up) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($users_up, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $userUpData) {
//                $ret = handle_solr_data($userUpData, $url);
                $ret = handleSolrDataRetry($userUpData, $url);

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批更新目标solr中的用户:[" . count($userUpData) . "]条...");

                if ($ret !== NULL) {
                    $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                    $result['result'] = false;
                    $result['msg'] = "solr分批更新用户失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批更新目标solr中的用户:[" . count($userUpData) . "]条完成!");
            }
            unset($eachTaskdatas);

            $end_t = microtime_float();
            $dst_solr_update_time += $end_t - $start_t;
        } else {
//            $ret = handle_solr_data($users_up, $url);
            $ret = handleSolrDataRetry($users_up, $url);

            $end_t = microtime_float();
            $dst_solr_update_time += $end_t - $start_t;
            if ($ret !== NULL) {
                $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                $result['result'] = false;
                $result['msg'] = "solr更新用户失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($users_up);

    if (!empty($dbusers_in)) {
        $start_t = microtime_float();
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标DB中插入用户:[" . count($dbusers_in) . "]条...");

        if (count($dbusers_in) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($dbusers_in, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $dbUserInData) {
                $ret = db_insert($dst->dsql, DATABASE_USER, $dbUserInData);
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批插入目标DB中的用户:[" . count($dbUserInData) . "]条...");

                if ($ret === false) {
                    $result['result'] = false;
                    $result['msg'] = "分批数据库插入用户失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批插入目标DB中的用户:[" . count($dbUserInData) . "]条成功!");
            }
            unset($eachTaskdatas);
            $end_t = microtime_float();
            $dst_db_insert_time += $end_t - $start_t;

        } else {
            $ret = db_insert($dst->dsql, DATABASE_USER, $dbusers_in);
            $end_t = microtime_float();
            $dst_db_insert_time += $end_t - $start_t;
            if ($ret === false) {
                $result['result'] = false;
                $result['msg'] = "数据库插入用户失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($dbusers_in);

    if (!empty($dbusers_up)) {
        $start_t = microtime_float();
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-更新目标DB中的用户:[" . count($dbusers_up) . "]条...");

        if (count($dbusers_up) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($dbusers_up, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $dbUserUpData) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批更新目标DB中的用户:[" . count($dbUserUpData) . "]条...");
                $ret = db_update($dst->dsql, DATABASE_USER, $dbUserUpData);
                if ($ret === false) {
                    $result['result'] = false;
                    $result['msg'] = "分批数据库更新用户失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批更新目标DB中的用户:[" . count($dbUserUpData) . "]条成功!");
            }
            $end_t = microtime_float();
            $dst_db_update_time += $end_t - $start_t;
            unset($eachTaskdatas);
        } else {
            $ret = db_update($dst->dsql, DATABASE_USER, $dbusers_up);
            $end_t = microtime_float();
            $dst_db_update_time += $end_t - $start_t;
            if ($ret === false) {
                $result['result'] = false;
                $result['msg'] = "数据库更新用户失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($dbusers_up);


    if (!empty($weibos_in)) {
        $dst_insert_status_count += count($weibos_in);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标solr中插入文章:[" . count($weibos_in) . "]条...");
        $start_t = microtime_float();
        //根据commit的值来决定commit是否true   by yu
        if($commit){
            $url = $dst->solr . SOLR_PARAM_INSERT . "&commit=true";
        }else{
            $url = $dst->solr . SOLR_PARAM_INSERT . "&commit=false";
        }
        //------   end
//        $url = $dst->solr . SOLR_PARAM_INSERT . "&commit=true";

        if (count($weibos_in) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($weibos_in, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $weibosInper) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标solr中插入文章:[" . count($weibosInper) . "]条...");

//                $ret = handle_solr_data($weibosInper, $url);
                $ret = handleSolrDataRetry($weibosInper, $url);
//                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标solr中插入文章:[" . var_export($weibosInper, true) . "]");

                if ($ret !== NULL) {
                    $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                    $result['result'] = false;
                    $result['msg'] = "分批solr插入微博失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标solr中插入文章:[" . count($weibosInper) . "]条成功!");
            }
            unset($eachTaskdatas);
            $end_t = microtime_float();
            $dst_solr_insert_time += $end_t - $start_t;
        } else {
//            $ret = handle_solr_data($weibos_in, $url);
            $ret = handleSolrDataRetry($weibos_in, $url);


            $end_t = microtime_float();
            $dst_solr_insert_time += $end_t - $start_t;
            if ($ret !== NULL) {
                $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                $result['result'] = false;
                $result['msg'] = "solr插入微博失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($weibos_in);

    if (!empty($weibos_up)) {
        $dst_update_status_count += count($weibos_up);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-更新目标solr中文章:[" . count($weibos_up) . "]条...");
        //根据commit的值来决定commit是否true   by yu
        if($commit){
            $url = $dst->solr . SOLR_PARAM_UPDATE . "&commit=true";
        }else{
            $url = $dst->solr . SOLR_PARAM_UPDATE . "&commit=false";
        }
        //------   end
//        $url = $dst->solr . SOLR_PARAM_UPDATE . "&commit=true";
        $start_t = microtime_float();

        if (count($weibos_up) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($weibos_up, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $weibosUpPer) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 更新目标solr中文章:[" . count($weibosUpPer) . "]条...");

//                $ret = handle_solr_data($weibosUpPer, $url);
                $ret = handleSolrDataRetry($weibosUpPer, $url);


                if ($ret !== NULL) {
                    $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                    $result['result'] = false;
                    $result['msg'] = "分批 solr更新微博失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 更新目标solr中文章:[" . count($weibosUpPer) . "]条成功!");
            }
            unset($eachTaskdatas);
            $end_t = microtime_float();
            $dst_solr_update_time += $end_t - $start_t;
        } else {
//            $ret = handle_solr_data($weibos_up, $url);
            $ret = handleSolrDataRetry($weibos_up, $url);

            $end_t = microtime_float();
            $dst_solr_update_time += $end_t - $start_t;
            if ($ret !== NULL) {
                $logger->error(__FUNCTION__ . " solr return " . var_export($ret, true));
                $result['result'] = false;
                $result['msg'] = "solr更新微博失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($weibos_up);

    if (!empty($dbweibos_in)) {
        $start_t = microtime_float();
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标DB中插入文章:[" . count($dbweibos_in) . "]条...");

        if (count($dbweibos_in) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($dbweibos_in, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $dbWeibosInPer) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标DB中插入文章:[" . count($dbWeibosInPer) . "]条...");

                $ret = db_insert($dst->dsql, DATABASE_WEIBO, $dbWeibosInPer);
                if ($ret === false) {
                    $result['result'] = false;
                    $result['msg'] = "分批 数据库插入微博失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标DB中插入文章:[" . count($dbWeibosInPer) . "]条成功!");
            }
            unset($eachTaskdatas);
            $end_t = microtime_float();
            $dst_db_insert_time += $end_t - $start_t;
        } else {
            $ret = db_insert($dst->dsql, DATABASE_WEIBO, $dbweibos_in);
            $end_t = microtime_float();
            $dst_db_insert_time += $end_t - $start_t;
            if ($ret === false) {
                $result['result'] = false;
                $result['msg'] = "数据库插入微博失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($dbweibos_in);

    if (!empty($dbweibos_up)) {
        $start_t = microtime_float();
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-更新目标DB中的文章:[" . count($dbweibos_up) . "]条...");

        if (count($dbweibos_up) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($dbweibos_up, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $dbWeibosUpPer) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 更新目标DB中的文章:[" . count($dbWeibosUpPer) . "]条...");

                $ret = db_update($dst->dsql, DATABASE_WEIBO, $dbWeibosUpPer);
                if ($ret === false) {
                    $result['result'] = false;
                    $result['msg'] = "分批 数据库更新微博失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 更新目标DB中的文章:[" . count($dbWeibosUpPer) . "]条成功!");
            }
            unset($eachTaskdatas);
            $end_t = microtime_float();
            $dst_db_update_time += $end_t - $start_t;
        } else {
            $ret = db_update($dst->dsql, DATABASE_WEIBO, $dbweibos_up);
            $end_t = microtime_float();
            $dst_db_update_time += $end_t - $start_t;
            if ($ret === false) {
                $result['result'] = false;
                $result['msg'] = "数据库更新微博失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($dbweibos_up);


    if (!empty($dbcomments_in)) {
        $start_t = microtime_float();
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-向目标DB中插入评论:[" . count($dbcomments_in) . "]条...");

        if (count($dbcomments_in) > MAX_HANDLE_RECORDS) {
            $eachTaskdatas = array_chunk($dbcomments_in, MAX_HANDLE_RECORDS);
            foreach ($eachTaskdatas as $idx => $dbCommentsInPer) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标DB中插入评论:[" . count($dbCommentsInPer) . "]条...");

                $ret = db_insert($dst->dsql, DATABASE_COMMENT, $dbCommentsInPer);
                if ($ret === false) {
                    $result['result'] = false;
                    $result['msg'] = "分批 数据库插入评论失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-分批 向目标DB中插入评论:[" . count($dbCommentsInPer) . "]条成功!");
            }
            unset($eachTaskdatas);
            $end_t = microtime_float();
            $dst_db_insert_time += $end_t - $start_t;
        } else {
            $ret = db_insert($dst->dsql, DATABASE_COMMENT, $dbcomments_in);
            $end_t = microtime_float();
            $dst_db_insert_time += $end_t - $start_t;
            if ($ret === false) {
                $result['result'] = false;
                $result['msg'] = "数据库插入评论失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }
        }
    }
    unset($dbcomments_in);

    unset($users_in);
    unset($users_up);
    unset($dbusers_in);
    unset($dbusers_up);
    unset($weibos_in);
    unset($weibos_up);
    unset($dbweibos_in);
    unset($dbweibos_up);
    unset($dbcomments_in);
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-importData--+-导入数据完成!");
    return $result;
}

function deleteData($src, &$data)
{
    global $logger, $task;
    global $kepDocIds, $kepUserIds;
    $logger->debug("enter " . __FUNCTION__);
    $result = array('result' => true, 'msg' => '');
    unset($data['dbdata']);
    unset($data['solrdata']);
    if (empty($task->taskparams->delseedweibo)) {
        //删除所有的 并不是值删除 根据条件查询出来的
        // 而是 需要删除 根据条件查询出来的100条 + 原创  而这些原创有可能被本次迁移之外的数据所引用 所有需要在下文代码中进行检查 ，如果
        //      没有被其他代码引用则需要删除，如果被其他文章引用则暂时不要删除 在下一次删除时候继续判断
//        $ids = array_merge(array_diff($data['keydata']['ids'], $data['keydata']['seedids']));
        $ids = array_merge(array_diff($data['keydata']['allids'], $data['keydata']['seedids']));
    } else {
        //$ids = &$data['keydata']['ids'];
        $ids = &$data['keydata']['allids'];
    }

    if (!empty($kepDocIds)) {
        $allIds = array_merge($ids, $kepDocIds);
        $allIds = array_unique($allIds);
    } else {
        $allIds = $ids;
    }


    //$rw = checkDeleteWeibos($src, $ids, $data['keydata']['cids']);
    $rw = &checkDeleteWeibos($src, $allIds, $data['keydata']['cids']);

    if (!$rw['result']) {
        $result['result'] = false;
        $result['msg'] = $rw['msg'];
        $logger->error(__FUNCTION__ . " 删除微博失败");
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    $task->taskparams->select_cursor += count($data['keydata']['ids']) - count($rw['delids']);
    if ((!empty($rw['delids']) || !empty($data['keydata']['cids'])) && !empty($task->taskparams->deluser)) {
        if (empty($task->taskparams->delseeduser)) {
            //$uids = array_merge(array_diff($data['keydata']['uids'], $data['keydata']['seeduids']));
            $uids = array_merge(array_diff($data['keydata']['alluids'], $data['keydata']['seeduids']));
        } else {
//            $uids = &$data['keydata']['uids'];
            $uids = &$data['keydata']['alluids'];
        }

        if (!empty($kepUserIds)) {
            $allUIds = array_merge($uids, $kepUserIds);
            $allUIds = array_unique($allUIds);
        } else {
            $allUIds = $uids;
        }
        unset($uids);

        $ru = &checkDeleteUsers($src, $allUIds, $allIds);
        if (!$ru['result']) {
            $result['result'] = false;
            $result['msg'] = $ru['msg'];
            $logger->error(__FUNCTION__ . " 删除用户失败");
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
    }

    unset($allIds);
    unset($allUIds);
    unset($rw);
    unset($ru);
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function checkPending()
{
    global $logger, $task, $srchost,
           $delete_status_count, $delete_user_count,
           $solr_query_time, $solr_delete_time, $db_query_time, $db_delete_time;
    $logger->debug("enter " . __FUNCTION__);
    if ($task->taskparams->keepsrc) {
        $logger->debug("exit " . __FUNCTION__);
        return true;
    }
    $delete_status_count = 0;
    $delete_user_count = 0;
    $solr_query_time = 0;
    $solr_delete_time = 0;
    $db_query_time = 0;
    $db_delete_time = 0;

    if ($task->taskparams->cond_in_customquery) {
        //使用用户自定义 的查询条件
        $q = $task->taskparams->cond_in_customquery;
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-checkPending--+-用户自定义的查询条件:[" . $q . "].");
    } else {
        $q = getQuery4Migrate($task->taskparams);
        //$q = getQuery4Migrate($task->taskparams);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-checkPending--+-生成查询条件:[" . $q . "].");
    }

    $order = "";
    if (isset($task->taskparams->orderby)) {
        if ($task->taskparams->orderby == "created") {
            $order = "created_at " . $task->taskparams->order;
        }
    }
    $facet = "";
    $url = $srchost->solr . SOLR_PARAM_SELECT;
    $start = $task->taskparams->offset;
    while ($start < $task->taskparams->select_cursor) {
        $limit = $task->taskparams->select_cursor - $start;
        if ($limit > $task->taskparams->eachcount) {
            $limit = $task->taskparams->eachcount;
        }
        $ids = array();
        $cids = array();
        $id2user = array();// doc.guid ==> userid
        $start_t = microtime_float();
        $qr = solr_select_conds(array('guid', 'userid', 'content_type'), $q, $start, $limit, $order, '', $facet, $url);
        $end_t = microtime_float();
        $solr_query_time += $end_t - $start_t;
        if ($qr === false) {
            $logger->error(__FUNCTION__ . " solr查询待删微博出错");
            $logger->debug("exit " . __FUNCTION__);
            return false;
        }
        foreach ($qr as $rec) {
            if (isset($rec['content_type']) && $rec['content_type'] == "2") {
                $cids[] = $rec['guid'];
            } else {
                $ids[] = $rec['guid'];
            }
            if (isset($rec['userid'])) {
                $id2user[$rec['guid']] = $rec['userid'];
            }
        }
        unset($qr);
        if (empty($task->taskparams->delseedweibo)) {
            $ckids = array();
            if (!empty($ids)) {
                $sql = "select guid from " . DATABASE_WEIBO . " where guid in ('" . implode("','", $ids) . "') and isseed != 1";
                $start_t = microtime_float();
                $qr = $srchost->dsql->ExecQuery($sql);
                $end_t = microtime_float();
                $db_query_time += $end_t - $start_t;
                if (!$qr) {
                    $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $srchost->dsql->GetError());
                    $logger->debug("exit " . __FUNCTION__);
                    return false;
                } else {
                    while ($rec = $srchost->dsql->GetArray($qr)) {
                        if (!empty($rec)) {
                            $ckids[] = $rec['guid'];
                        }
                    }
                    $srchost->dsql->FreeResult($qr);
                }
            }
        } else {
            $ckids = &$ids;
        }
        if (!empty($cids)) {
            // comment cannot be left since it cannot be seed or depended
            $logger->error(__FUNCTION__ . " 数据一致性被破坏");
            $logger->debug("exit " . __FUNCTION__);
            return false;
        }
        $rw = checkDeleteWeibos($srchost, $ckids, $cids);
        if (!$rw['result']) {
            $logger->error(__FUNCTION__ . " 删除微博失败");
            $logger->debug("exit " . __FUNCTION__);
            return false;
        }
        $delcount = count($rw['delids']);
        if (!empty($task->taskparams->deluser)) {
            $uids = array();
            foreach ($rw['delids'] as $id) {
                $uids[] = $id2user[$id];
            }
            if (!empty($uids)) {
                if (empty($task->taskparams->delseeduser)) {
                    $ckuids = array();
                    $url = $srchost->solr . SOLR_PARAM_SELECT;
                    $facet = "";
                    $start_t = microtime_float();

                    $delUserCds = array('users_id' => $uids, 'users_seeduser' => 0);
                    if (isset($task->taskparams->users_source_host) && !empty($task->taskparams->users_source_host)) {
//                    array('users_id' => $uids, 'users_seeduser' => 0, 'users_source_host' => $task->taskparams->users_source_host)
                        $delUserCds['users_source_host'] = $task->taskparams->users_source_host;
                    }

                    $qr = solr_select_conds(array('users_id'), $delUserCds, 0, -1, '', '', $facet, $url);
                    $end_t = microtime_float();
                    $solr_query_time += $end_t - $start_t;
                    if ($qr === false) {
                        $logger->error(__FUNCTION__ . " solr查询待删用户出错");
                        $logger->debug("exit " . __FUNCTION__);
                        return false;
                    }
                    foreach ($qr as $rec) {
                        $ckuids[] = $rec['users_id'];
                    }
                } else {
                    $ckuids = &$uids;
                }
                $ru = checkDeleteUsers($srchost, $ckuids);
                if (!$ru['result']) {
                    $logger->error(__FUNCTION__ . " 删除用户失败");
                    $logger->debug("exit " . __FUNCTION__);
                    return false;
                }
            }
        }
        $start += $limit - $delcount;
        $task->taskparams->select_cursor -= $delcount;
        unset($rw);
        unset($ru);
        unset($ids);
        unset($cids);
        unset($id2user);
        unset($uids);
    }
    $task->taskparams->scene->delete_status_count += $delete_status_count;
    $task->taskparams->scene->delete_user_count += $delete_user_count;
    $task->taskparams->scene->solr_time += $solr_query_time + $solr_delete_time;
    $task->taskparams->scene->solr_query_time += $solr_query_time;
    $task->taskparams->scene->solr_delete_time += $solr_delete_time;
    $task->taskparams->scene->db_time += $db_query_time + $db_delete_time;
    $task->taskparams->scene->db_query_time += $db_query_time;
    $task->taskparams->scene->db_delete_time += $db_delete_time;
    $logger->debug("exit " . __FUNCTION__);
    return true;
}

function db_retrieve($dsql, $table, $ids, $idFiledName = "guid")
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    if (empty($ids)) {
        $logger->debug("exit " . __FUNCTION__);
        return array();
    }
    $nofields = array();
//    $idfield = 'id';
    $idfield = $idFiledName;
    switch ($table) {
        case DATABASE_WEIBO:
            $nofields[] = 'count_id';
            break;
        case DATABASE_USER:
            $nofields[] = 'no_id';
            break;
        default:
            break;
    }
    $result = array();
    $sql = "select * from {$table} where {$idfield} in ('" . implode("','", $ids) . "')";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        $result = false;
    } else {
        while ($rec = $dsql->GetArray($qr)) {
            if (!empty($rec)) {
                if (!empty($nofields)) {
                    foreach ($nofields as $nofield) {
                        unset($rec[$nofield]);
                    }
                }
                $result[] = $rec;
            }
        }
        $dsql->FreeResult($qr);
    }
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function db_insert($dsql, $table, $data)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    if (empty($data)) {
        $logger->debug("exit " . __FUNCTION__);
        return true;
    }
    $result = true;
    foreach ($data as $datum) {
        $sql = insert_template($table, $datum);
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            if ($dsql->GetErrorNo() == 1062) {
                // ignore duplicate
                $logger->warn(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
                $dsql->FreeResult($qr);
            } else {
                $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
                $result = false;
                break;
            }
        } else {
            $dsql->FreeResult($qr);
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function db_update($dsql, $table, $data)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    if (empty($data)) {
        $logger->debug("exit " . __FUNCTION__);
        return true;
    }
    $idfield = 'id';
    $result = true;
    foreach ($data as $datum) {
        $wheredata = array($idfield => $datum[$idfield]);
        unset($datum[$idfield]);
        $sql = update_template($table, $datum, $wheredata);
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
            $result = false;
            break;
        } else {
            $dsql->FreeResult($qr);
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function db_delete($dsql, $table, $ids, $filedName = "guid")
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    if (empty($ids)) {
        $logger->debug("exit " . __FUNCTION__);
        return true;
    }
    $idfield = $filedName;
    $result = true;
    $sql = "delete from {$table} where {$idfield} in ('" . implode("','", $ids) . "')";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        $result = false;
    } else {
        $dsql->FreeResult($qr);
    }
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function &checkDeleteWeibos($host, &$ids, &$cids)
{
    global $logger, $task,
           $delete_status_count, $solr_query_time, $solr_delete_time, $db_delete_time;
    global $kepDocIds;

    $logger->debug("enter " . __FUNCTION__);
    $result = array('result' => true, 'delids' => array(), 'msg' => '');
    if (empty($ids) && empty($cids)) {
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }
    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " need to del doc:[" . count($ids) . "] comments:[" . count($cids) . "].");

    if (CHECK_DOC_USER_DEPEND_DEL) {
        // 保留原创 因为这些原创还有其他文章在引用 如果被其他文章引用 并且这些文章不再本次需要删除的文章列表中 则需要暂时保留
        $keepids = array();
        if (!empty($ids)) {
            $url = $host->solr . SOLR_PARAM_SELECT;
            $facet = "";
            $deletConds = array('retweeted_guid' => $ids);
            if (isset($task->taskparams->source_host) && !empty($task->taskparams->source_host)) {
                $deletConds['source_host'] = $task->taskparams->source_host;
            }

            // 需要把引用该文章的所有文章都
            //$qr = solr_select_conds(array('retweeted_guid', 'guid'), $deletConds, 0, -1, '', 'retweeted_guid', $facet, $url);

            $selectDocIdx = 0;
            while (true) {
                $start_t = microtime_float();
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除原创数据--+-分批查询其他文章引用本次需要删除的文章--+-本次坐标:start:[{$selectDocIdx}]" . "] limit:[{" . MAX_DEL_CHECK_RECORDS . "}]");
                $qr = solr_select_conds(array('retweeted_guid', 'guid'), $deletConds, $selectDocIdx, MAX_DEL_CHECK_RECORDS, '', '', $facet, $url);
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除原创数据--+-分批查询其他文章引用本次需要删除的文章--+-本次查询结果数:[" . count($qr) . "] 本次需要删除的文章个数:[" . count($ids) . "].");

                $end_t = microtime_float();
                $solr_query_time += $end_t - $start_t;
                if ($qr === false) {
                    $result['result'] = false;
                    $result['msg'] = "solr查询原创失败";
                    $logger->error(__FUNCTION__ . " " . $result['msg']);
                    $logger->debug("exit " . __FUNCTION__);
                    return $result;
                }

                foreach ($qr as $rec) {
                    if (!isset($rec['guid']) || empty($rec['guid'])) {
                        $logger->error(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . "####################### currentData no guid:[" . var_export($rec, true) . "].");
                        throw new Exception("no guid!");
                    }
                    //该批次删除的文章本身引用另外一部分需要删除的文章 那么这批被引用的文章将不被保留 自己引用自己
                    if (array_search($rec['guid'], $ids) === true) {
                        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " ************************************************");
                        continue;
                    }

                    if (array_search($rec['retweeted_guid'], $keepids) === false) {
                        $keepids[] = $rec['retweeted_guid'];
                    } else {
                        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . "#############################################");
                    }
                }


                if (count($qr) < MAX_DEL_CHECK_RECORDS) {
                    $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除原创数据--+-分批查询其他文章引用本次需要删除的文章--+-分批查询结束!");
                    unset($qr);
                    break;
                } else {
                    $selectDocIdx = $selectDocIdx + MAX_DEL_CHECK_RECORDS;
                }
                unset($qr);
            }

        }
        unset($deletConds);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除原创数据--+-其他文章引用本次需要删除的文章--+-该批文章保留:[" . count($keepids) . "] 条, allDocId:[" . var_export($keepids, true));
        $delids = array_merge(array_diff($ids, $keepids));
        $kepDocIds = $keepids;
        unset($keepids);
        unset($qr);
    } else {
        $delids = $ids;
    }

    if (!empty($delids)) {
        $delete_status_count += count($delids);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除原创数据--+-根据id删除文章数据:[" . count($delids) . "] 条!");

        $start_t = microtime_float();
        $ret = db_delete($host->dsql, DATABASE_WEIBO, $delids, "guid");
        $end_t = microtime_float();
        $db_delete_time += $end_t - $start_t;
        if ($ret === false) {
            $result['result'] = false;
            $result['msg'] = "数据库删除微博失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
        //$q = "guid:" . "(" . implode(" OR ", $delids) . ") AND content_type:(0 OR 1)";
        $q = "guid:" . "(" . implode(" OR ", $delids) . ")";
        $url = $host->solr . SOLR_PARAM_DELETE;
        $start_t = microtime_float();
        $dr = delete_solrdata($q, $url);
        unset($q);

        $end_t = microtime_float();
        $solr_delete_time += $end_t - $start_t;
        if ($dr === false) {
            $result['result'] = false;
            $result['msg'] = "solr删除微博失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
    }
    if (!empty($cids)) {
        $delete_status_count += count($cids);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除评论数据--+-根据id删除评论数据:[" . count($cids) . "] 条!");
        $start_t = microtime_float();
        $ret = db_delete($host->dsql, DATABASE_COMMENT, $cids);
        $end_t = microtime_float();
        $db_delete_time += $end_t - $start_t;
        if ($ret === false) {
            $result['result'] = false;
            $result['msg'] = "数据库删除评论失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
        $q = "guid:" . "(" . implode(" OR ", $cids) . ") AND content_type:2";
        $url = $host->solr . SOLR_PARAM_DELETE;
        $start_t = microtime_float();
        $dr = delete_solrdata($q, $url);

        unset($q);

        $end_t = microtime_float();
        $solr_delete_time += $end_t - $start_t;
        if ($dr === false) {
            $result['result'] = false;
            $result['msg'] = "solr删除评论失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
    }
    $result['delids'] = $delids;
    unset($delids);
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function &checkDeleteUsers($host, &$uids, &$allDocIds)
{
    global $logger, $task,
           $delete_user_count, $solr_query_time, $solr_delete_time, $db_delete_time;
    global $kepUserIds;

    $logger->debug("enter " . __FUNCTION__);
    $result = array('result' => true, 'deluids' => array(), 'msg' => '');
    if (empty($uids)) {
        $logger->debug("exit " . __FUNCTION__);
        return $result;
    }

    if (CHECK_DOC_USER_DEPEND_DEL) {
        $keepuids = array();
        $url = $host->solr . SOLR_PARAM_SELECT;
        $facet = "";
        $delUseConds = array('userid' => $uids);
        if (isset($task->taskparams->source_host) && !empty($task->taskparams->source_host)) {
//        array('userid' => $uids, 'source_host' => $task->taskparams->source_host)
            $delUseConds['source_host'] = $task->taskparams->source_host;
        }

        $startUserIdx = 0;
        //需要分批查询引用该用户的文章 有可能引用该批用户的文章非常之多 所以不能一次性查出来
        while (true) {
            // 查询引用该用户的文章
            //
            $start_t = microtime_float();
            $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除用户数据--+-分批查询其他文章引用本次需要删除的用户--+-本次坐标:start:[{$startUserIdx}]" . "] limit:[{" . MAX_DEL_CHECK_RECORDS . "}]");
            $qr = solr_select_conds(array('userid', 'guid'), $delUseConds, $startUserIdx, MAX_DEL_CHECK_RECORDS, '', '', $facet, $url);
            $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除用户数据--+-分批查询其他文章引用本次需要删除的用户--+-本次查询结果数:[" . count($qr) . "] 本次需要删除的用户个数:[" . count($uids) . "].");

            $end_t = microtime_float();
            $solr_query_time += $end_t - $start_t;
            if ($qr === false) {
                $result['result'] = false;
                $result['msg'] = "solr查询用户失败";
                $logger->error(__FUNCTION__ . " " . $result['msg']);
                $logger->debug("exit " . __FUNCTION__);
                return $result;
            }

            foreach ($qr as $rec) {
                if (array_search($rec['userid'], $keepuids) === false && array_search($rec['guid'], $allDocIds) === false) {
                    $keepuids[] = $rec['userid'];
                }
            }

            if (count($qr) < MAX_DEL_CHECK_RECORDS) {
                $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除用户数据--+-分批查询其他文章引用本次需要删除的用户--+-分批查询结束!");
                unset($qr);
                break;
            } else {
                $startUserIdx = $startUserIdx + MAX_DEL_CHECK_RECORDS;
            }
            unset($qr);
        }
        unset($delUseConds);
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除用户数据--+-其他文章引用本次需要删除的用户--+-该批用户保留:[" . count($keepuids) . "] 条, AllUserIds:[" . var_export($keepuids, true));
        $kepUserIds = $keepuids;
        $deluids = array_merge(array_diff($uids, $keepuids));
        unset($keepuids);
    } else {
        $deluids = $uids;
    }

    if (!empty($deluids)) {
        $logger->debug(" " . __FILE__ . " " . __FUNCTION__ . " " . __LINE__ . " 数据迁移--+-删除用户数据--+-根据id删除用户数据:[" . count($deluids) . "] 条!");

        $delete_user_count += count($deluids);
        $start_t = microtime_float();
        $ret = db_delete($host->dsql, DATABASE_USER, $deluids, "id");
        $end_t = microtime_float();
        $db_delete_time += $end_t - $start_t;
        if ($ret === false) {
            $result['result'] = false;
            $result['msg'] = "数据库删除用户失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
        $q = "users_id:" . "(" . implode(" OR ", $deluids) . ")";
        $url = $host->solr . SOLR_PARAM_DELETE;
        $start_t = microtime_float();
        $dr = delete_solrdata($q, $url);

        unset($q);
        $end_t = microtime_float();
        $solr_delete_time += $end_t - $start_t;
        if ($dr === false) {
            $result['result'] = false;
            $result['msg'] = "solr删除用户失败";
            $logger->error(__FUNCTION__ . " " . $result['msg']);
            $logger->debug("exit " . __FUNCTION__);
            return $result;
        }
    }
    $result['deluids'] = $deluids;
    unset($qr);
    unset($deluids);
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}
