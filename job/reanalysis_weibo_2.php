<?php
/**
 * 重新分析微博
 */
define("SELF", basename(__FILE__));

//决定重新分析时候 是否将标题添加到内容后面
//define("APP_TITLE_TO_TEXT", false);

if (isset($_SERVER['argc']) && $_SERVER['argc'] > 2) {
    $currentmachine = $argv[2];
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
} else {
    //$logger->error(SELF . " - 未传递参数【machine】");
    echo "参数错误";
    exit;
}

include_once('includes.php');
include_once('taskcontroller.php');
include_once('jobfun.php');
ini_set('include_path', get_include_path() . '/lib');
ini_set("memory_limit", "1024M");

initLogger(LOGNAME_SYNC);//使用同步模块的日志配置

$res_machine;//机器资源
$res_ip;
$res_acc;
//声明保存时间的变量，insert_status需要用
$analysistime = 0;//solr分词时间
$storetime = 0;//存储时间
$sqlquerytime = 0;//查询数据时间
$sqltime = 0;//更新数据库时间
$solrerrorcount = 0;//错误数
$currentmachine;//当前机器名称
$old_datastatus;//上次执行的条数
//分词方案 
$dictionaryPlan = "";
//任务id用来判断 任务id不同就从数据库取新方案
$taskID = -1;
//   执行次数，用来决定commit是否为true
$execcount = 0;
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
try {
    $task = getWaitingTask(TASKTYPE_ANALYSIS, TASK_SYNC);
    if (!empty($task)) {
        $task->machine = $currentmachine;
        $task->wait_resourcetype = RESOURCE_TYPE_MACHINE;
        $task->usetype = USETYPE_CONCURRENT;
        $queue_ret = queueTask($task);
        if ($queue_ret === false) {
            $logger->error(SELF . " - 将任务插入排队表失败");
            exit;
        }
        $logger->debug(SELF . " - 将任务插入排队表");
    } else {
        $logger->debug(SELF . " - 未找到待启动任务,查询排队任务");
    }

    $task = getQueueTask($currentmachine, TASK_SYNC);
    if (empty($task)) {
        $logger->debug(SELF . " - 未获取到排队任务，退出");
        exit;
    }

    $logger->debug(SELF . " - 获取到排队任务，任务ID：" . $task->id);
    $task->machine = $currentmachine;//获取指定的机器资源
    if (!isset($task->taskparams->scene->state)) {
        $task->taskparams->scene->state = SCENE_NORMAL;
    }
    $m_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_MACHINE, $task->machine, null, $task->tasklevel, $task->queuetime, true);
    if (!$m_r) {//不可用
        $task->taskparams->scene->state = SCENE_WAITCONCURRENT_MACHINE;
    }
    //getAllConcurrentRes($task,$res_machine,$res_ip,$res_acc);//获取并发资源
    if ($task->taskparams->scene->state != SCENE_NORMAL) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        $logger->info(SELF . " - " . getResourceErrorMsg($task->taskparams->scene->state) . ",任务退出");
        updateQueueTask($task);
        updateTask($task);
        exit;
    }

    unQueueTask($task->id);//获取到资源，解除排队
    startTask($task);//启动任务
    $logger->info(SELF . " - 任务启动");
    $rt = detectConflictTask($task);
    if (!$rt['result']) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        stopTask($task);
        $logger->error(SELF . "- 冲突检测失败 -" . $rt['msg']);
        exit;
    } else if (!$rt['continue']) {
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
        $logger->info(SELF . " - 任务冲突，延迟启动");
        exit;
    }
    if (empty($task->datastatus)) {
        $task->datastatus = 0;
    }
    $old_datastatus = $task->datastatus;//上次执行的条数
    $r = execute();
    myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    if ($r) {
        completeTask($task);
        $logger->info(SELF . " - 任务完成");
    } else {
        $logger->info(SELF . " - 任务停止");
        stopTask($task);
    }
} catch (Exception $ex) {
    fatalTask($task);
    $logger->fatal(SELF . " - 任务异常" . $ex->getMessage());
    exit;
}
exit;

function execute()
{
    global $logger, $dsql, $analysistime, $storetime, $solrerrorcount, $sqltime, $sqlquerytime, $task, $orig_infos_cache, $solrtimediff, $sqltimediff;
    global $taskID, $dictionaryPlan;
    global $execcount;//执行次数
//    $execcount = 0;
    $result = true;
    //获取方案
    $taskID = $task->id;
    $dictionaryPlan = queryDictionaryPlan($taskID);
    if (empty($task->taskparams->tokenize_fields) && empty($task->taskparams->other_fields)) {
        $logger->error(SELF . " 任务参数错误，not found tokenize_fields , other_fields");
        return false;
    }
    if (!isset($task->taskparams->scene->solr_count)) {
        $task->taskparams->scene->solr_count = 0;
    }
    if (!isset($task->taskparams->scene->execcount)) {
        $task->taskparams->scene->execcount = 0;
    }
    if (!isset($task->taskparams->scene->sqlquerytime)) {
        $task->taskparams->scene->sqlquerytime = 0;
    }
    if (!isset($task->taskparams->scene->analysistime)) {
        $task->taskparams->scene->analysistime = 0;
    }
    if (!isset($task->taskparams->scene->sqlupdatetime)) {
        $task->taskparams->scene->sqlupdatetime = 0;
    }
    if (!isset($task->taskparams->scene->solrerrorcount)) {
        $task->taskparams->scene->solrerrorcount = 0;
    }
    if (!isset($task->taskparams->scene->alltime)) {
        $task->taskparams->scene->alltime = 0;
    }
    if (!isset($task->taskparams->scene->select_origguid_count)) {//查询原创guID的次数
        $task->taskparams->scene->select_origguid_count = 0;
    }
    $sqlarr = array();
    $preexetime = 0;
    $prestarttime = microtime_float();

    if (!isset($task->taskparams->maxanalysistime)) {
        $task->taskparams->maxanalysistime = time();//如果未设置最大分析时间，默认为当前时间
    }
    $maxanalysistime = $task->taskparams->maxanalysistime;
    $minanalysistime = $task->taskparams->minanalysistime;
//    $whArr = array();
//    if(!empty($maxanalysistime)){
//        $whArr[] = "analysis_time < ".$maxanalysistime."";
//    }
//    if(!empty($minanalysistime)){
//        $whArr[] = "analysis_time > ".$minanalysistime."";
//    }
    $analysiscount = 0;
//    $whstr = "";
//    if(count($whArr) > 0){
//        $whstr = implode(" and ", $whArr);
//        $sqlall = "select count(0) as cnt from weibo_new where ".$whstr."";
//        $qrall = $dsql->ExecQuery($sqlall);
//        if(!$qrall){
//            $logger->error(SELF." 查询总数据数失败:{$sqldatabasecount} error:".$dsql->GetError());
//            return false;
//        }
//        else{
//            $rsall = $dsql->GetArray($qrall, MYSQL_ASSOC);
//            //使用analysis_time条数
//            $analysiscount = (int)$rsall['cnt'];
//        }
//    }


    if (empty($task->taskparams->scene)) {//第一次执行任务
        $task->taskparams->scene = (object)array();
    }
//    $sqldatabasecount = "select count(0) as cnt from weibo_new ";
//    $qrdatabasecount = $dsql->ExecQuery($sqldatabasecount);
//    if(!$qrdatabasecount){
//        $logger->error(SELF." 查询总数据数失败:{$sqldatabasecount} error:".$dsql->GetError());
//        return false;
//    }
//    $rsdbcount = $dsql->GetArray($qrdatabasecount);
//    $task->taskparams->scene->databasecount = $rsdbcount['cnt'];//数据库数据数


    //根据配置的参数生成查询条件
    $qarr = array();
    //来源
    if (isset($task->taskparams->source_host) && count($task->taskparams->source_host) > 0) {
        $source_hostArr = $task->taskparams->source_host;
        $sp = array();
        foreach ($source_hostArr as $si => $sitem) {
            $sourceid = get_source_id($sitem);
            if (!empty($sourceid)) {
                $sp[] = "sourceid:" . $sourceid . "";
            } else {
                $sp[] = "source_host:" . $sitem . "";
            }
        }
        $qarr[] = implode("+OR+", $sp);
    }
    //链接
    if (isset($task->taskparams->urls) && count($task->taskparams->urls) > 0) {
        $tmparr = array();
        foreach ($task->taskparams->urls as $ki => $url) {
            $sourceid = get_sourceid_from_url($url);
            $mid = weiboUrl2mid($url, $sourceid);
            if ($mid != "" && !empty($sourceid)) {
                $tmparr[] = "(sourceid:{$sourceid}+AND+mid:" . $mid . ")";
            } else {
                $url = solrEsc($url);
                if (!empty($sourceid)) {
                    $tmparr[] = "(sourceid:{$sourceid}+AND+original_url:" . $url . ")";
                } else {
                    $tmparr[] = "original_url:" . $url . "";
                }
            }
        }
        $tmpstr = implode("+OR+", $tmparr);
        $qarr[] = $tmpstr;
    }
    $starttime = !empty($task->taskparams->minanalysistime) ? $task->taskparams->minanalysistime : null;
    $endtime = !empty($task->taskparams->maxanalysistime) ? $task->taskparams->maxanalysistime : null;
//    $logger->debug(SELF." 获取分析时间 startTime:[".($starttime)."] endTime:[".($endtime)."]!");

    if ($starttime != null || $endtime != null) {
        $fieldname = "created_at";
        if ($starttime === null && $endtime !== null) {
            $starttime = "*";
            $tmpwhere = "" . $fieldname . ":[" . $starttime . "+TO+" . $endtime . "]";
        } else if ($starttime !== null && $endtime === null) {
            $endtime = "*";
            $tmpwhere = "" . $fieldname . ":[" . $starttime . "+TO+" . $endtime . "]";
        } else if ($starttime !== null && $endtime !== null) {
            $tmpwhere = "" . $fieldname . ":[" . $starttime . "+TO+" . $endtime . "]";
        }
        $qarr[] = $tmpwhere;
    }

    $fl = array();
    $qparam = implode('+AND+', $qarr);
    //solr查询条件下的条数
    $solrres = 0;
    $solrres = solr_select_conds($fl, $qparam, 0, 0);
    $preendtime = microtime_float();
    $preexetime = $preendtime - $prestarttime;
    $task->taskparams->scene->alltime += $preexetime;

    //开始计算, analysis_time没有在solr中存,导致不能同时作为条件查询, 需要做两次循环,此处先计算个数少的,以少的作为循环
    $logger->info(__FILE__ . __LINE__ . " before analysis analysiscount:" . var_export($analysiscount, true) . " solrres:" . var_export($solrres, true));
    if ($analysiscount > 0 || $solrres > 0) {
//        if($analysiscount > $solrres){
        $task->taskparams->scene->totalanalysiscount = $solrres;
        do {
            $onceerrcount = 0;
            $st = getTaskStatus($task->id);
            if ($st == -1) {
                $logger->info(SELF . " - 人工停止");
                return false;
            }
            $each_count = !empty($task->taskparams->each_count) ? $task->taskparams->each_count : 500;
            if ($each_count < 500) {
                $each_count = 500;
            }
            $start_time = microtime_float();
            $curBegainIdx = $each_count * $execcount;
            $qr = solr_select_conds($fl, $qparam, $curBegainIdx, $each_count);
            $logger->debug(SELF . " 本次准备从solr中获取第:[" . ($curBegainIdx) . "] 条到第:[" . ($curBegainIdx + $each_count) . "] 条数据!");

            $end_time = microtime_float();
            $sqlquerytimediff = $end_time - $start_time;
            $sqlquerytime += $sqlquerytimediff;
            $execcount++;
            if ($qr === false) {
                $logger->error(__FILE__ . __LINE__ . " 获取文章异常 " . $qparam);
                $result = false;
                break;
            } else {
                $rsnum = count($qr);
                $logger->debug(SELF . " 本次从solr中实际获取到:[" . $rsnum . "] 条数据，准备重新分析这批文章...");
                //  经过考虑，感觉这部分代码不符合现在业务，所以注释    by  yu  2017/3/31
//                foreach ($qr as $qpi => $qpitem) {
//                    if (isset($qpitem['guid'])) {
//                        $sqlg = "select analysis_time from weibo_new where guid = '" . $qpitem['guid'] . "'";
//                        $gqr = $dsql->ExecQuery($sqlg);
//                        if (!$gqr) {
//                            $logger->error(SELF . " 数据库中未查到:{$sqlg} error:" . $dsql->GetError());
//                        } else {
//                            $gres = $dsql->GetArray($gqr);
//                            $maxanalysistime = $task->taskparams->maxanalysistime;
//                            if ($gres['analysis_time'] > $maxanalysistime) {
//                                unset($qr[$qpi]);
//                            }
//                            if (isset($task->taskparams->minanalysistime)) {
//                                $minanalysistime = $task->taskparams->minanalysistime;
//                                if ($gres['analysis_time'] < $minanalysistime) {
//                                    unset($qr[$qpi]);
//                                }
//                            }
//                        }
//                    }
//                }
                //----  end
                if ($rsnum === 0) {
                    //$logger->debug(SELF." 未查询到数据:{$qparam} start:".$each_count*$execcount." end:".$each_count."");
                    $logger->debug(SELF . " 未查询到数据:{$qparam} start:" . $each_count * $execcount . " end:" . $each_count . "");
                    break;
                }
                if (!executeData($qr, $rsnum, $each_count, $sqlquerytimediff)) {
                    $result = false;
                    break;
                }
            }
            unset($qr);
            $task->taskparams->scene->execcount++;//总执行次数
            $task->taskparams->scene->sqlquerytime += $sqlquerytimediff;//查询总花费

            $task->taskparams->scene->storetime += $solrtimediff;//存储花费时间
            $task->taskparams->scene->sqlupdatetime += $sqltimediff;//更新数据库总花费
            $task->taskparams->scene->solrerrorcount += $onceerrcount;//总失败条数
            $task->taskparams->scene->alltime += $sqlquerytimediff + $solrtimediff + $sqltimediff;
            try {
                updateTaskInfo($task);
            } catch (Exception $ex) {
                $logger->error($ex->getMessage());
            }
            $logger->info(SELF . " 总处理{$task->datastatus}条数据，失败{$solrerrorcount}条，执行{$execcount}次，查询总花费{$sqlquerytime}，solr分词总花费{$analysistime}，solr存储总花费{$storetime}，更新数据库总花费{$sqltime}");
        } while (1);
        //新增flush，结束时把所有数据刷新到磁盘上    by  yu   2017/3/31
        $flush_res = flushData();
        $logger->info(__FUNCTION__.__FILE__.__LINE__."the reanalysis flush data res is:".var_export($flush_res,true));
        if(!$flush_res){
            $result = false;
        }
        //---   end
//        }
//        else if($analysiscount < $solrres){
//            $task->taskparams->scene->totalanalysiscount = $analysiscount;
//            do{
//                $onceerrcount = 0;
//                $st = getTaskStatus($task->id);
//                if($st == -1){
//                    $logger->info(SELF." - 人工停止");
//                    return false;
//                }
//                $each_count = !empty($task->taskparams->each_count) ? $task->taskparams->each_count : 500;
//                $start_time = microtime_float();
//                $sqlonce = "select * from weibo_new where ".$whstr." limit ".$each_count*$execcount.", $each_count";
//                $start_time = microtime_float();
//                $qronce = $dsql->ExecQuery($sqlonce);
//                $end_time = microtime_float();
//                $sqlquerytimediff = $end_time - $start_time;
//                $sqlquerytime += $sqlquerytimediff;
//                $execcount++;
//                if(!$qronce){
//                    $logger->error(SELF." 查询总数据数失败:{$sqlonce} error:".$dsql->GetError());
//                    return false;
//                }
//                else{
//                    $rowonce = $dsql->GetTotalRow($qronce);
//                    if(count($rowonce) == 0){
//                        $logger->debug(SELF." 未查询到数据:{$sqlonce} start:".$each_count*$execcount." end:".$each_count."");
//                        break;
//                    }
//                    $guidArr = array();
//                    while($tmpresult = $dsql->GetArray($qronce, MYSQL_ASSOC)){
//                        if(!empty($tmpresult['guid'])){
//                            $guidArr[]='guid:'.$tmpresult['guid'];
//                        }
//                    }
//                    $guidstr = implode('+OR+', $guidArr);
//                    $rows = count($guidArr);
//                    if(!empty($qparam)){
//                        $guidstr = $guidstr."+AND+".$qparam;
//                    }
//                    $qr = solr_select_conds($fl, $guidstr, 0, $rows);
//                    if($qr === false){
//                        $logger->error(__FILE__.__LINE__." 获取文章异常 ".$guidstr);
//                        $result = false;
//                        break;
//                    }
//                    else{
//                        $rsnum = count($qr);
//                        if($rsnum === 0 || $rows === 0){
//                            //$logger->debug(SELF." 未查询到数据:{$qparam} start:".$each_count*$execcount." end:".$each_count."");
//                            $logger->debug(SELF." 未查询到数据:{$qparam} start:".$each_count*$execcount." end:".$each_count."");
//                            break;
//                        }
//                        if(!executeData($qr, $rsnum, $each_count, $sqlquerytimediff)){
//                            $result = false;
//                            break;
//                        }
//                    }
//                    $task->taskparams->scene->execcount ++;//总执行次数
//                    $task->taskparams->scene->sqlquerytime += $sqlquerytimediff;//查询总花费
//
//                    $task->taskparams->scene->storetime += $solrtimediff;//存储花费时间
//                    $task->taskparams->scene->sqlupdatetime += $sqltimediff;//更新数据库总花费
//                    $task->taskparams->scene->solrerrorcount += $onceerrcount;//总失败条数
//                    $task->taskparams->scene->alltime += $sqlquerytimediff + $solrtimediff + $sqltimediff;
//                    try{
//                        updateTaskInfo($task);
//                    }
//                    catch(Exception $ex){
//                        $logger->error($ex->getMessage());
//                    }
//                    $logger->info(SELF." 总处理{$task->datastatus}条数据，失败{$solrerrorcount}条，执行{$execcount}次，查询总花费{$sqlquerytime}，solr分词总花费{$analysistime}，solr存储总花费{$storetime}，更新数据库总花费{$sqltime}");
//                }
//            }while(1);
//        }
    }
    if (!$result) {
        $logger->info(SELF . " 总处理{$task->datastatus}条数据，失败{$solrerrorcount}条，执行{$execcount}次，查询总花费{$sqlquerytime}，solr分词总花费{$analysistime}，solr存储总花费{$storetime}，更新数据库总花费{$sqltime}");
    }
    return $result;
}

function executeData(&$qr, $rsnum, $each_count, $sqlquerytimediff)
{
    global $logger, $dsql, $analysistime, $storetime, $solrerrorcount, $sqltime, $sqlquerytime, $task, $orig_infos_cache, $solrtimediff, $sqltimediff;
    global $taskID, $dictionaryPlan,$execcount;
    $result = true;
    $senddata = array();
    $origdata = array();
    $ids = array();
    $retweeted_mids = array();
    $add_title_after_text = $task->taskparams->add_title_after_text;//是否将标题添加到内容后面
    $logger->info(__LINE__.__LINE__." the add_title_after_text is:".var_export($add_title_after_text,true));

    if (count($qr) > 0) {
        $logger->debug(__FILE__ . __LINE__ . " 使用分词计划: " . var_export($dictionaryPlan, true)." 对该批文章进行重新分析...");
        foreach ($qr as $qi => $rs) {
            $logger->debug(__FILE__ . __LINE__ . " rs data " . var_export($rs, true));
            if (isset($rs['guid'])) {
                $guid = $rs['guid'];
            } else {
                $guid = getArticleGuidOrMore($rs);
            }
            if ($guid === false) {
                $logger->error(__FILE__ . __LINE__ . " 获取文章guid失败 " . var_export($rs, true));
                $result = false;
                break;
            }
            $ids[] = $guid;
            if (isset($rs['analysis_status']) && $rs['analysis_status'] == ANALYSIS_STATUS_OTHERERROR) {
                //微博在solr不存在
                $logger->error(__FILE__ . __LINE__ . " 当前文章以前分析错误，guid:[" . $guid . "].");
                $task->taskparams->scene->analysis_status_othererrorcount++;
                continue;
            }
            $rwguid = '';
            if (isset($rs['analysis_status']) && $rs['analysis_status'] == ANALYSIS_STATUS_NORMAL) {
                //两个字段都有值时，不确定原创的guid，去solr查询
                if (!empty($rs['retweeted_guid'])) {
                    $rwguid = $rs['retweeted_guid'];
                } else if (!empty($rs['retweeted_status']) && !empty($rs['retweeted_mid'])) {
                    $qparam = "(id:{$rs['retweeted_status']}+OR+mid:{$rs['retweeted_mid']})+AND+sourceid:{$rs['sourceid']}";
                    $origguidurl = SOLR_URL_SELECT . "?q={$qparam}&facet=off&fl=guid&start=0&rows=1";
                    $result = getSolrData($origguidurl);
                    if (isset($result['errorcode'])) {
                        $logger->error(SELF . " call getSolrData({$origguidurl}) error:" . $result['errormsg']);
                        $result = false;
                        break;
                    }
                    $task->taskparams->scene->select_origguid_count++;
                    if (!empty($result['query']['docs'])) {
                        $rwguid = $result['query']['docs'][0]['guid'];
                    } else {
                        $rs['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST;//原创不存在
                        $rwguid = '';
                    }
                } else if (!empty($rs['retweeted_status']) || !empty($rs['retweeted_mid'])) {
                    $rwguid = getOriginalGuidFromSolr($rs);
                    if ($rwguid === false) {
                        $logger->error(__FILE__ . __LINE__ . " 获取原创guid失败 " . var_export($rs, true));
                        $result = false;
                        break;
                    }
                }
            }

            //$senddata[] = array('guid'=>$guid,'retweeted_guid'=>$rwguid,'analysis_status'=>$rs['analysis_status']);
            $senddataitem = array();
            //旧记录没有retweeted_mid，solr需要此字段，根据retweeted_status查询原创的MID
            //$retweeted_mids以原创ID 为key，存储mid，避免重复查询
            if (!empty($rs['rewteeted_status']) && empty($rs['retweeted_mid'])) {
                if (!isset($retweeted_mids[$rs['rewteeted_status']])) {
                    $sql_rmid = "select mid from weibo_new where id = {$rs['rewteeted_status']}";
                    $qr_rmid = $dsql->ExecQuery($sql_rmid);
                    if (!$qr_rmid) {
                        $logger->error(SELF . " select weibo:{$sql_rmid} error:" . $dsql->GetError());
                        $result = false;
                        break;
                    } else {
                        $rs_rmid = $dsql->GetArray($qr_rmid);
                        $dsql->FreeResult($qr_rmid);
                        $retweeted_mids[$rs['rewteeted_status']] = empty($rs_rmid['mid']) ? '' : $rs_rmid['mid'];
                    }
                }
                $senddataitem['retweeted_mid'] = $retweeted_mids[$rs['rewteeted_status']];
            } else if (!empty($rs['retweeted_mid'])) {
                $senddataitem['retweeted_mid'] = $rs['retweeted_mid'];
            }
            $senddataitem['guid'] = $guid;//必填字段
            if (!empty($rwguid)) {
                $senddataitem['retweeted_guid'] = $rwguid;//必填字段
            }
            if (isset($rs['docguid'])) {
                $senddataitem['docguid'] = $rs['docguid'];//必填字段
            }
            if (isset($rs['paragraphid'])) {
                $senddataitem['paragraphid'] = $rs['paragraphid'];//必填字段
            }
            if (isset($rs['id'])) {
                $senddataitem['id'] = $rs['id'];//必填字段
            }
            if (isset($rs['sourceid'])) {
                $senddataitem['sourceid'] = $rs['sourceid'];//必填字段
            }
            if (isset($rs['mid'])) {
                $senddataitem['mid'] = $rs['mid'];
            }
            if (isset($rs['analysis_status'])) {
                $senddataitem['analysis_status'] = $rs['analysis_status'];
            }
            if (isset($rs['content_type'])) {
                $senddataitem['content_type'] = $rs['content_type'];
            } else {
                $logger->error(__FILE__ . __LINE__ . " content_type field not find  " . var_export($rs, true));
            }

            $logger->debug(__FILE__ . __LINE__ . " 重新分析字段:" . var_export($task->taskparams->tokenize_fields, true));
            $datas_desc;//数据位置

            // 分句界面上配置测需要分词的字段
            if (!empty($task->taskparams->tokenize_fields)) {
                foreach ($task->taskparams->tokenize_fields as $field_key => $field_name) {
                    if ($field_name == 'post_title') {    //对标题进行分析
                        if (isset($rs['post_title'])) {
                            $senddataitem['post_title'] = transTokenFieldToObj($rs['post_title']);
                            $senddataitem['post_title']['content'] = transferToRN($senddataitem['post_title']['content']);
                        }
                    } else if ($field_name == 'verified_reason') {    //对认证原因进行分析
                        if (isset($rs['verified_reason'])) {
                            $senddataitem['verified_reason'] = transTokenFieldToObj($rs['verified_reason']);
                            $senddataitem['verified_reason']['content'] = transferToRN($senddataitem['verified_reason']['content']);
                        }
                    } else if ($field_name == 'description') {    //对用户简介进行分析
                        if (isset($rs['description'])) {
                            $senddataitem['description'] = transTokenFieldToObj($rs['description']);
                            $senddataitem['description']['content'] = transferToRN($senddataitem['description']['content']);
                        }
                    } else if ($field_name == 'text') {
                        if (isset($rs['text'])) {
                            //需要把查出的字段text 去掉<BR/>
                            $senddataitem['text'] = transTokenFieldToObj($rs['text']);
                            $senddataitem['text']['content'] = transferToRN($senddataitem['text']['content']);
                            //三福项目特殊需求，把标题内容也加入到内容里面进行分词，然后用换行符把内容和标题分开，内容在上面，标题在下面
                            //    由于正常情况下，不需要把标题加到内容后面所以引入一个常量来控制   （这只是临时解决方案）
                            //**        解决方案  在前台加上一个单选框，把单选框的值入到数据库，然后这里在根据那个值来决定是否追加下面的逻辑      **//
                            if ($add_title_after_text) {
                                if (isset($rs['post_title']) && !empty($rs['post_title'])) {
                                    foreach ($rs['post_title'] as $key => $value) {
                                        $res = strpos($rs['text']['0'],$value);
                                        if($res !== false){
                                            break;
                                        }
//                                        $title_length = strlen($value);
//                                        $text_content = substr($rs['text']['0'], (-$title_length));
//                                        if ($text_content == $value) {
//                                            break;
//                                        }
                                        if ($key <= 0) {
                                            $senddataitem['text']['content'] = $senddataitem['text']['content'] . "\n" . $value;
                                        } else {
                                            $senddataitem['text']['content'] = $senddataitem['text']['content'] . "\t" . $value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($rs['content_type']) {
                    $datas_desc = &$senddata;
                } else {
                    $datas_desc = &$origdata;
                }
            } else {
                $datas_desc = &$senddata;
            }

            if (!empty($task->taskparams->other_fields)) {
                foreach ($task->taskparams->other_fields as $field_key => $field_name) {
                    if ($field_name == 'area') {
                        $senddataitem['country_code'] = empty($rs['country_code']) ? '' : $rs['country_code'];
                        $senddataitem['province_code'] = empty($rs['province_code']) ? '' : $rs['province_code'];
                        $senddataitem['city_code'] = empty($rs['city_code']) ? '' : $rs['city_code'];
                        $senddataitem['district_code'] = empty($rs['district_code']) ? '' : $rs['district_code'];
                    } else if ($field_name == 'time') {
                        $senddataitem['created_year'] = isset($rs['created_year']) ? $rs['created_year'] : '';
                        $senddataitem['created_month'] = isset($rs['created_month']) ? $rs['created_month'] : '';
                        $senddataitem['created_day'] = isset($rs['created_day']) ? $rs['created_day'] : '';
                        $senddataitem['created_hour'] = isset($rs['created_hour']) ? $rs['created_hour'] : '';
                        $senddataitem['created_weekday'] = isset($rs['created_at']) ? date('N', $rs['created_at']) : '';
                    } else {
                        if (!isset($rs[$field_name]) || $rs[$field_name] == NULL) {
                            $logger->error(SELF . " other_field:{$field_name} not found");
                            $result = false;
                            break 2;
                        }
                        $senddataitem[$field_name] = empty($rs[$field_name]) ? '' : $rs[$field_name];
                    }
                }
            }
            $datas_desc[] = $senddataitem;
            $logger->debug(__FILE__ . __LINE__ . " senddataitem " . var_export($senddataitem, true));
        }
    }
    //需要重新分析, 此处送给solr_analysis的origdata或senddata未包含全部字段,会在solr_analysis函数中从新查询
    $solr_analysistime = 0;
    if (!empty($task->taskparams->tokenize_fields)) {
        $logger->debug(__FUNCTION__ . " begin analysis weibo");
        $orig_count = count($origdata);
        $logger->debug(__FILE__ . __LINE__ . " 本次分析中原创文章:[{$orig_count}]个!");
        //先处理原创
        if ($orig_count) {
            $logger->debug(__FILE__ . __LINE__ . " before analysis origdata " . var_export($origdata, true));
            $start_time = microtime_float();
            $ana_result = solr_analysis($origdata, $task->taskparams->tokenize_fields, $dictionaryPlan);//分析微博
            $end_time = microtime_float();
            $solr_analysistime = $end_time - $start_time;
            $analysistime += $solr_analysistime;
            $task->taskparams->scene->analysistime += $solr_analysistime;//总分析时间
            if (!$ana_result) {
                $logger->error(__FUNCTION__ . " solr_analysis orig return empty " . var_export($origdata, true));
                $result = false;
                return false;
            }
            $logger->debug(__FILE__ . __LINE__ . " after analysis ana_result " . var_export($ana_result, true));
            formatStoreData($origdata, $ana_result, $task->taskparams->tokenize_fields);//生成存储数据
//            $orig_infos_cache = $origdata;//全局缓存
            unset($ana_result);
        } else {
            $logger->debug(__FILE__ . __LINE__ . " 本次分析中原创文章0条，没有原创文章!");
        }
        if (!empty($senddata)) {
            $start_time = microtime_float();
            $logger->debug(__FILE__ . __LINE__ . " 本次分词[非原创文章]个数为:[" . count($senddata) . "]条.");

            $logger->debug(__FILE__ . __LINE__ . " before analysis senddata[非原创文章]: " . var_export($senddata, true));
            $ana_result = solr_analysis($senddata, $task->taskparams->tokenize_fields, $dictionaryPlan);//分析微博
            $end_time = microtime_float();
            $solr_analysistime = $end_time - $start_time;
            $analysistime += $solr_analysistime;
            $task->taskparams->scene->analysistime += $solr_analysistime;//总分析时间
            if (!$ana_result) {
                $logger->error(__FUNCTION__ . " solr_analysis return empty[非原创文章]: " . var_export($ana_result, true));
                $result = false;
                return false;
            }
            $logger->debug(__FILE__ . __LINE__ . " after analysis ana_result[非原创文章]: " . var_export($ana_result, true));
            formatStoreData($senddata, $ana_result, $task->taskparams->tokenize_fields);//生成存储数据
            unset($ana_result);
        } else {
            $logger->debug(__FILE__ . __LINE__ . " 本次分词[非原创文章]个数为0条! ");
        }
        $senddata = array_merge($origdata, $senddata);
    }
    $iscommit = !empty($task->taskparams->iscommit) ? "true" : "false";//是否立即提交
    if (!empty($senddata)) {
        $url = SOLR_URL_UPDATE;
        //用执行次数来决定commit为true还是false。  by  yu  2017/3/31
        if($execcount%10 == 0)
        {
            $url .= "&commit=true";
        } else {
            $url .= "&commit=false";
        }
        //------ end
//        if ($rsnum < $each_count) {//最后一次
//            $url .= "&commit=true";
//        } else {
//            $url .= "&commit=" . $iscommit;
//        }
        $start_time = microtime_float();
        $solr_r = handle_solr_data($senddata, $url);
        $task->taskparams->scene->solr_count++;
        $end_time = microtime_float();
        $solrtimediff = $end_time - $start_time;
        $storetime += $solrtimediff;
        unset($senddata);
        if ($solr_r === false) {
            $result = false;
            $logger->error(SELF . " 调用solr失败");
        } else if ($solr_r !== NULL && is_array($solr_r)) {
            $ids = array_diff($ids, $solr_r);  //注意，键名保持不变
            $logger->error(SELF . " SOLR 未找到的:" . var_export($solr_r, true));
            $onceerrcount = count($solr_r);
            $solrerrorcount += $onceerrcount;
            $splitguid = splitGuid($solr_r);
            foreach ($splitguid['source_ids'] as $k => $v) {
                $noorgids = "'" . implode("','", $v) . "'";
                updateDataAnalysisStatus($noorgids, ANALYSIS_STATUS_OTHERERROR, $k);
            }
            foreach ($splitguid['source_mids'] as $k => $v) {
                $noorgmids = "'" . implode("','", $v) . "'";
                updateDataAnalysisStatus(null, ANALYSIS_STATUS_OTHERERROR, $k, $noorgmids);
            }
        }
        unset($solr_r);
    } else {
        $logger->warn("没有找到需要分析数据");
    }

    $task->datastatus += count($ids);
    $start_time = microtime_float();
    $time_als = time();
    $temp_idstr = "'" . implode("','", $ids) . "'";
    $upsql = "update " . DATABASE_WEIBO . " set analysis_time={$time_als} where guid in({$temp_idstr})";
    $logger->debug($upsql);
    $upqr = $dsql->ExecQuery($upsql);
    unset($ids);
    if (!$upqr) {
        $logger->error(SELF . " 更新analysistime失败：sql：{$upsql} error:" . $dsql->GetError());
        $result = false;
        return false;
    }
    $dsql->FreeResult($upqr);
    $end_time = microtime_float();
    $sqltimediff = $end_time - $start_time;
    $sqltime += $sqltimediff;

    $logger->info(SELF . " 本次处理{$rsnum}条数据，查询花费{$sqlquerytimediff}，solr分词花费{$solr_analysistime}, solr存储花费{$solrtimediff}，更新数据库花费{$sqltimediff}");
    return $result;
}

function splitGuid($ids)
{
    $allsource_noorg = array();//二维数组整合每个source的id
    $allsource_noorg_mid = array();//二维数组整合每个source的mid
    for ($i = 0; $i < count($ids); $i++) {
        $noorgtempid_arr = split("_", $ids[$i]);
        //guid中带m的，后面跟的是mid
        if (stripos($noorgtempid_arr[0], 'm') !== false) {
            $sid = str_ireplace('m', '', $noorgtempid_arr[0]);
            $allsource_noorg_mid['' . $sid][] = $noorgtempid_arr[1];
        } else {
            $allsource_noorg["{$noorgtempid_arr[0]}"][] = $noorgtempid_arr[1];
        }
    }
    return array("source_ids" => $allsource_noorg, "source_mids" => $allsource_noorg_mid);
}
