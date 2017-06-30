<?php
header("content-type:text/html;charset=utf-8");
define("SELF", basename(__FILE__));


//定义请求任务类型:为通用任务,以后可以去掉默认就是通用类型的任务
define("TASKTYPE_COMMON", "remotecommtask");
define("TASKTYPE_COMMON_CACHE", "remotecommtask_cache");

define("IMPORT_DATA_TARGET_H2", "H2_CACHE");
define("IMPORT_DATA_TARGET_MONGO", "MONGO_CACHE");

include_once('includes.php');
include_once('commonFun.php');
include_once('taskcontroller.php');
include_once("authorization.class.php");
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
include_once('PHPExcel/IOFactory.php');
ini_set('include_path', get_include_path() . '/lib');
include_once('OpenSDK/Tencent/Weibo.php');
session_start();
set_time_limit(0);//植入微博时，可能会超时
initLogger(LOGNAME_WEBAPI);
$chkr = Authorization::checkUserSession();
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
$res_machine;
$res_ip;
$res_acc;
$updateusercount = 0;
$spiderusercount = 0;
$apicount = 0;
$spidercount = 0;
$newcount = 0;
$task = new Task(null);
$task->machine = SERVER_MACHINE;
$task->taskparams->scene->state = SCENE_NORMAL;
$task->tasklevel = 2;
$task->taskparams->iscommit = true;
$task->queuetime = time();
$taskadd = isset($_POST['taskadd']) ? $_POST['taskadd'] : 0;
$reposttask = (object)array();
if ($taskadd) {
    $reposttask->local = empty($_POST['local']) ? 0 : 1;
    $reposttask->remote = empty($_POST['remote']) ? 0 : 1;
    $reposttask->conflictdelay = empty($_POST['conflictdelay']) ? 60 : (int)$_POST['conflictdelay'];
    $reposttask->config = (int)$_POST['config'];
    $reposttask->duration = (int)$_POST['duration'];
    $reposttask->forceupdate = empty($_POST['forceupdate']) ? 0 : 1;
    $reposttask->isrepostseed = empty($_POST['isrepostseed']) ? 0 : 1;
}
$result = array("result" => true, "msg" => '操作成功');
//$logger->info("处理类型为:[" . $_GET['type'] . "]的任务...");

//远程植入,tasktype为importurl，包含新浪微博搜索id，转发id，评论id
if (!empty($_GET['type']) && $_GET['type'] == "remote") {
    if ($chkr != CHECKSESSION_SUCCESS) {
        setErrorMsg($chkr, "未登录或登陆超时!");
    }
    if (empty($HTTP_RAW_POST_DATA)) {
        setErrorMsg(1, "未提交数据");
    }
    $weibos = array();
    $existsids = array();
    $errorurls = array();
    $importobj = json_decode($HTTP_RAW_POST_DATA, true);
    //set sourceid
    /*
    if(isset($importobj['sourceid']))
        $source = $importobj['sourceid'];
    else if(isset($importobj['page_url']))
    {
     */
    $source = get_source_id('weibo.com');
    if ($source != NULL) {
        $importobj['sourceid'] = $source;
    }
    //}
    //sourcehost和page_url在调用api的函数里设，这里不设
    $urlorids = isset($importobj['data']) ? $importobj['data'] : array();
    $allcount = count($urlorids);
    $logger->info(SELF . " recived import remote data (url count is " . $allcount . "): " . $HTTP_RAW_POST_DATA);
    $isseed = empty($importobj['isseed']) ? false : true;//是否种子微博
    $iscomment = empty($importobj['comment']) ? false : true;
    $timeline = $iscomment ? 'comments_show_batch' : (empty($importobj['repost']) ? 'show_status' : 'repost_timeline');
    $usertimeline = !empty($importobj['usertimeline']) && $allcount > 0 && isset($urlorids[0]['id']);
    $leftid = $usertimeline ? $urlorids[0]['id'] : '';
    $rightid = $usertimeline ? $urlorids[$allcount - 1]['id'] : '';
    $page = isset($importobj['page']) ? $importobj['page'] : null;
    //爬虫任务信息
    $taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
    $depend = 0;
    if (!empty($taskinfo)) {
        $rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $urlorids, $page);
        if ($rt['result'] == false) {
            $logger->error(SELF . " " . $rt['msg']);
            setErrorMsg($rt['error'], $rt['msg']);
        } else if ($rt['done'] == true) {
            echo json_encode($result);
            exit;
        }
        if ($iscomment) {
            $depend = $taskinfo->id;
        }
    }
    //$logger->error("taskinfo".var_export($taskinfo,true));
    //需要加入的分词方案 根据当前任务id获取 当衍生新任务时不是保存父任务的id而是直接复制了父任务的方案
    //是因为获取时有时间延时，任务可能已经进入历史表
    $dictionary_plan_new = "";
    if ($taskinfo->id != NULL) {
        //从数据库获取任务方案
        $dictionary_plan_new = queryDictionaryPlan($taskinfo->id);
    }
    global $taskID;
    $taskID = $taskinfo->id;
    $logger->debug(__FILE__ . __LINE__ . " taskID " . $taskID);
    $taskurls = array();//需要加入任务的微博
    if ($iscomment) {
        $weiboidtype = "comment";
        $cids = array();
        foreach ($urlorids as $comment) {
            $cids[] = $comment['id'];
        }
        $_r = getcomment($source, $cids);
        if ($_r['result']) {
            if (isset($_r['comments'])) {
                $weibos = $_r['comments'];
            } else if (isset($_r['existids'])) {//数据库已存在
                $existsids = $_r['existids'];
            }
        } else {//未抓取到评论
            $result['result'] = false;
            if (!empty($_r['nores'])) {
                $result['nores'] = true;
                $taskurls = $cids;//得到未处理的评论
            }
            if (isset($_r['error_code']) && $_r['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                $result['retry'] = true;
            }
            if (!empty($_r['apiempty'])) {
                $result['retry'] = true;
            }
            $result['msg'] = "批量抓取评论时失败：" . $_r['msg'] . "。";
            $logger->error(SELF . " " . $result['msg']);
        }
    } else {
        foreach ($urlorids as $key => $value) {
            $weiboidtype = isset($value['id']) ? 'id' : 'url';
            $v = isset($value['id']) ? $value['id'] : $value['url'];
            if (empty($v)) {
                continue;
            }
            $_r = getweibo($source, $weiboidtype, $v, $isseed, $timeline);
            if ($_r['result']) {
                if (isset($_r['weibo'])) {
                    $weibos[] = $_r['weibo'];
                } else if (isset($_r['weiboid'])) {//数据库已存在
                    $existsids[] = $_r['weiboid'];
                }
            } else {//未抓取到微博
                $result['result'] = false;
                if (!empty($_r['nores'])) {
                    $result['nores'] = true;
                    $taskurls = array_splice($urlorids, $key);//得到未处理的微博url
                }
                if (isset($_r['error_code']) && $_r['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                    $result['result'] = true;
                    $logger->error(SELF . " 抓取微博{$v}时失败：" . $_r['msg'] . "。");
                    continue;
                }
                if (!empty($_r['apiempty'])) {
                    $result['retry'] = true;
                }
                $result['msg'] = "抓取微博{$v}时失败：" . $_r['msg'] . "。";
                $logger->error(SELF . " " . $result['msg']);
                break;
            }
        }
    }
    $logger->info(SELF . " 抓取完毕，成功" . count($weibos) . "条, 数据库已存在" . count($existsids) . "条");
    $reposttask_ids = array();
    //处理已经抓取的微博
    if (!empty($weibos)) {
        if ($iscomment) {
            $_r = addweibo($source, $weibos, $isseed, $timeline, true, true);//入库
        } else {
            $_r = addweibo($source, $weibos, $isseed, $timeline);//入库
        }

        if ($_r['result'] == false) {
            $logger->error(SELF . " 入库失败" . var_export($_r, true));
            //入库失败且之前抓取微博时也有错误发生， 将msg拼接
            if ($result['result'] == false) {
                $result['msg'] .= "新增微博时失败：" . $_r['msg'];
            } else {
                $result = $_r;
            }
        } else {
            $logger->info(SELF . " 入库成功");
            //入库成功，且需要增加任务
            if ($taskadd) {
                foreach ($weibos as $k => $v) {
                    if (!isset($v['retweeted_status'])) {
                        $reposttask_ids[] = $v['id'];
                    }
                }
            }
        }
    }

    if (!empty($weiboids) && $taskadd) {
        foreach ($weiboids as $k => $v) {
            $reposttask_ids[] = $v;
        }
    }
    if (!empty($reposttask_ids)) {
        $_r = addRepostTask($source, $reposttask_ids, false, $reposttask->conflictdelay, $reposttask->local, $reposttask->remote, $reposttask);
        if ($_r['result'] == false) {//增加转发任务失败
            if ($result['result'] == false) {
                $result['msg'] .= $_r['msg'];
                $logger->error(SELF . " 新增转发任务时失败：" . $_r['msg']);
            } else {
                $result['result'] = $_r['result'];
                $result['msg'] = "添加微博成功，" . $_r['msg'];
            }
        }
    }
    if (!empty($taskurls)) {//增加植入任务
        $remarks = "来自crawler的请求，共{$allcount}条请求，已抓取" . count($weibos);
        $remarks .= "条，数据库已存在" . count($existsids) . "条。剩余" . count($taskurls) . "条。";
        //增加分词方案
        $t_r = addImportTask($source, $taskurls, $weiboidtype, $remarks, $isseed, $depend, $dictionary_plan_new);
        if ($result['result'] == false) {
            $result['msg'] .= $t_r['msg'];
        } else {
            $result['msg'] = $t_r['msg'];
        }
        if (!$t_r['result']) {
            $logger->error(SELF . " " . $t_r['msg']);
        } else {
            $logger->info(SELF . " " . $t_r['msg']);
        }
        $result['result'] = $t_r['result'];//新增任务成功后则继续
    }

    if ($usertimeline) {
        $left = getUserTimelineInfo($leftid, $source);
        $right = getUserTimelineInfo($rightid, $source);
        if (!empty($left) && !empty($right)) {
            $utl_r = handleUserTimeline($left, $right, $source);
            if ($utl_r['result'] == false) {
                $result['result'] = false;
                $result['msg'] = $utl_r['msg'];
                $logger->error(SELF . " " . $result['msg']);
            } else if ($utl_r['stop']) {
                $result['ctrl'] = array('stop' => true);
            }
        }
    }
    $result['info'] = formatResultInfo();
    if ($result['result'] == false) {
        if (!empty($result['retry'])) {
            $result['errorcode'] = -3;
            unset($result['retry']);
        } else {
            $result['errorcode'] = -1;
        }
        $result['error'] = $result['msg'];
        $logger->error($result['msg']);
        unset($result['result']);
        unset($result['msg']);
    }

    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == "addimporttask") {//增加任务时，排队任务
    if ($chkr != CHECKSESSION_SUCCESS) {
        $arrs["result"] = false;
        $arrs["msg"] = "未登录或登陆超时!";
        echo json_encode($arrs);
        exit;
    }
    $isseed = empty($_POST['isseed']) ? false : true;//是否种子微博
    if (isset($_POST['sourceid']))
        $source = $_POST['sourceid'];
    else if (isset($_POST['page_url']))
        $source = get_sourceid_from_url($_POST['page_url']);
    if (!empty($_POST['urls']) && !empty($source)) {
        $remarks = "来自系统管理的请求，共{$_POST['allcount']}条请求，已抓取" . ($_POST['allcount'] - count($_POST['urls']));
        $remarks .= "条。剩余" . count($_POST['urls']) . "条。";
        $result = addImportTask($source, $_POST['urls'], $_POST['weibotype'], $remarks, $isseed);
        if ($result['result'] == false) {
            $logger->error(SELF . " addImportTask return false :" . $result['msg']);
        }
    } else {
        $logger->error(SELF . " 参数错误，未传递urls或sourceid");
        $result['result'] = false;
        $result['msg'] = "参数错误,未传递urls或sourceid";
    }
    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == "remoteweibo") { //远程导入微博, tasktype为importweibo，包含新浪微博搜索全部，转发全部，评论全部
    try {
        if ($chkr != CHECKSESSION_SUCCESS) {
            setErrorMsg($chkr, "未登录或登陆超时!");
        }
        if (empty($HTTP_RAW_POST_DATA)) {
            setErrorMsg(1, "未提交数据");
        }
        $taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
        $logger->debug(SELF . " recived import remoteweibo data : " . $HTTP_RAW_POST_DATA);
        $postdata = json_decode($HTTP_RAW_POST_DATA, true);
        if (empty($postdata) || empty($postdata['data'])) {
            setErrorMsg(1, "数据为空");
        }
        $page = isset($postdata['page']) ? $postdata['page'] : null;
        if (!empty($taskinfo)) {
            $rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $postdata['data'], $page, 1);
            if ($rt['result'] == false) {
                $logger->error(SELF . " " . $rt['msg']);
                setErrorMsg($rt['error'], $rt['msg']);
            } else if ($rt['done'] == true) {
                echo json_encode($result);
                exit;
            }
        }
        if (isset($postdata['sourceid'])) {
            $sourceid = $postdata['sourceid'];
        } else if (isset($postdata['page_url'])) {
            $sourceid = get_sourceid_from_url($postdata['page_url']);
            $logger->debug(__FILE__ . __LINE__ . " sourceid " . var_export($sourceid, true));
            if ($sourceid != NULL) {
                $postdata['sourceid'] = $sourceid;
            }
        } else {
            $sourceid = get_source_id('weibo.com');
            if ($sourceid != NULL) {
                $postdata['sourceid'] = $sourceid;
            }
        }
        $logger->debug(__FILE__ . __LINE__ . " sourceid " . var_export($sourceid, true));
        //设置source_host
        $postdata['source_host'] = isset($postdata['page_url']) ? get_host_from_url($postdata['page_url']) : NULL;
        $task->tasksource = $sourceid;
        $task->taskparams->source = $sourceid;
        $isseed = empty($postdata['isseed']) ? false : true;//是否种子微博
        $allcount = count($postdata['data']);
        $usertimeline = !empty($postdata['usertimeline']) && $allcount > 0;
        //每条设置source_host
        if (isset($postdata['source_host'])) {
            for ($k = 0; $k < $allcount; $k++) {
                $postdata['data'][$k]['source_host'] = $postdata['source_host'];
            }
        }

        $iscomment = empty($postdata['comment']) ? false : true;
        $timeline = $iscomment ? 'comments_spider' : (empty($postdata['repost']) ? 'show_status' : 'repost_timeline');
        if ($iscomment == true) {
            $sw_r = supplyComment($postdata['sourceid'], $postdata['data']);
        } else {
            $sw_r = supplyWeibo($postdata['sourceid'], $postdata['data']);
        }
        $r_info = array();//统计信息
        if ($sw_r == true) {//全部成功
            if ($iscomment) {
                $r = addweibo($postdata['sourceid'], $postdata['data'], $isseed, $timeline, true, true);
            } else {
                $r = addweibo($postdata['sourceid'], $postdata['data'], $isseed, $timeline);
            }

            $incomplete_count = 0;
            if ($r['result'] && $usertimeline) {
                $left = array();
                $right = array();
                for ($i = 0; $i < $allcount; $i++) {
                    if (!empty($left) && !empty($right)) {
                        break;
                    }
                    if (empty($left) && empty($postdata['data'][$i]['deleted']) && !empty($postdata['data'][$i]['user'])) {
                        $left['id'] = $postdata['data'][$i]['id'];
                        $left['created_at'] = $postdata['data'][$i]['created_at_ts'];
                        $left['userid'] = $postdata['data'][$i]['user']['id'];
                        if (isset($postdata['data'][$i]['user']['statuses_count'])) {
                            $left['statuses_count'] = $postdata['data'][$i]['user']['statuses_count'];
                        }
                    }
                    if (empty($right) && empty($postdata['data'][$allcount - $i - 1]['deleted']) && !empty($postdata['data'][$allcount - $i - 1]['user'])) {
                        $right['id'] = $postdata['data'][$allcount - $i - 1]['id'];
                        $right['created_at'] = $postdata['data'][$allcount - $i - 1]['created_at_ts'];
                        $right['userid'] = $postdata['data'][$allcount - $i - 1]['user']['id'];
                        if (isset($postdata['data'][$allcount - $i - 1]['user']['statuses_count'])) {
                            $right['statuses_count'] = $postdata['data'][$allcount - $i - 1]['user']['statuses_count'];
                        }
                    }
                }
                if (!empty($left) && !empty($right)) {
                    $utl_r = handleUserTimeline($left, $right, $postdata['sourceid']);
                    if ($utl_r['result'] == false) {
                        $r['result'] = false;
                        $r['msg'] = $utl_r['msg'];
                        $logger->error(SELF . " " . $r['msg']);
                    } else if ($utl_r['stop']) {
                        $r['ctrl'] = array('stop' => true);
                    }
                }
            }
        } else {//未全部成功
            $incomlplete_weibos = array();
            $readysenddata = array();
            //将已成功的入库，未成功的加入任务
            while ($weibo = array_shift($postdata['data'])) {
                if (!empty($weibo['retweeted_status'])) {//转发
                    if (!empty($weibo['user']) && !empty($weibo['retweeted_status']['user'])) {
                        $readysenddata[] = $weibo;
                    } else {//转发，或者原创没有找到user
                        $incomlplete_weibos[] = $weibo;
                    }
                } else {//原创
                    if (!empty($weibo['user'])) {
                        $readysenddata[] = $weibo;
                    } else {
                        $incomlplete_weibos[] = $weibo;
                    }
                }
            }
            $sub_remarks = "";
            //发送给solr
            if (!empty($readysenddata)) {
                if ($iscomment) {
                    $r = addweibo($postdata['sourceid'], $readysenddata, $isseed, $timeline, true, true);
                } else {
                    $r = addweibo($postdata['sourceid'], $readysenddata, $isseed, $timeline);
                }

                if ($r['result'] == false) {
                    $sub_remarks = "已处理" . count($readysenddata) . "条，但入库失败。";
                    $incomlplete_weibos = array_merge($incomlplete_weibos, $readysenddata);
                } else {
                    $sub_remarks = "已处理" . count($readysenddata) . "条。";
                    if ($taskadd) {
                        $rt_ids = array();
                        $rt_urls = array();
                        foreach ($readysenddata as $rtk => $rtv) {
                            if (isset($rtv['retweeted_status'])) {
                                continue;//跳过转发
                            }
                            if (isset($rtv['id'])) {
                                if (!in_array($rtv['id'], $rt_ids)) {
                                    $rt_ids[] = $rtv['id'];
                                }
                            } else if (isset($rtv['user']['id']) && isset($rtv['mid'])) {
                                $rt_url = weibomid2Url($rtv['user']['id'], $rtv['mid'], $postdata['sourceid']);
                                if (!empty($rt_url) && !in_array($rt_url, $rt_urls)) {
                                    $rt_urls[] = $rt_url;
                                }
                            }
                        }
                        if (!empty($rt_ids) || !empty($rt_urls)) {
                            $_r = addRepostTask($source, $rt_ids, $rt_urls, $reposttask->conflictdelay, $reposttask->local, $reposttask->remote, $reposttask);
                            if ($_r['result'] == false) {//增加转发任务失败
                                $r['result'] = $_r['result'];
                                $r['msg'] = "增加转发任务失败";
                            }
                        }
                    }
                }
            }
            $incomplete_count = count($incomlplete_weibos);
            if ($incomplete_count > 0) {
                $sub_remarks .= "本次需要处理{$incomplete_count}条。";
                $remarks = "来自spider的请求，共" . $allcount . "条微博。{$sub_remarks}";
                $r = addImportTask($postdata['sourceid'], $incomlplete_weibos, 'weibo', $remarks, $isseed);
            }
        }
        $task->taskparams->scene->incomplete_count = $incomplete_count;
        $task->taskparams->scene->update_user_count += $updateusercount;
        $task->taskparams->scene->insert_user_count += $spiderusercount;
        $r['info'] = formatResultInfo();
    } catch (Exception $e) {
        $r['result'] = false;
        $r['msg'] .= " " . $e->getMessage();
    }
    if ($r['result'] == false) {
        $r['errorcode'] = -1;
        $r['error'] = $r['msg'];
        $logger->error($r['msg']);
        unset($r['result']);
        unset($r['msg']);
    }
    echo json_encode($r);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == "remotearticlelist") {
    if ($chkr != CHECKSESSION_SUCCESS) {
        setErrorMsg($chkr, "未登录或登陆超时!");
    }
    if (empty($HTTP_RAW_POST_DATA)) {
        setErrorMsg(1, "未提交数据");
    }
    $weibos = array();
    $existsids = array();
    $errorurls = array();
    $importobj = json_decode($HTTP_RAW_POST_DATA, true);
    $source = "";
    if (isset($importobj['sourceid']))
        $source = $importobj['sourceid'];
    else if (isset($importobj['page_url'])) {
        $source = get_sourceid_from_url($importobj['page_url']);
        if ($source != NULL) {
            $importobj['sourceid'] = $source;
        }
    }
    $urlorids = isset($importobj['data']) ? $importobj['data'] : array();
    $allcount = count($urlorids);
    $logger->info(SELF . " recived import data (url count is " . $allcount . "): " . $HTTP_RAW_POST_DATA);
    $derivetexttpl = empty($importobj['derivetexttpl']) ? -1 : $importobj['derivetexttpl'];//派生抓取内容任务模板
    $deriveusertpl = empty($importobj['deriveusertpl']) ? -1 : $importobj['deriveusertpl'];//派生抓取用户任务模板
    $page = isset($importobj['page']) ? $importobj['page'] : null;
    $taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
    if (!empty($taskinfo)) {
        $rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $urlorids, $page);
        if ($rt['result'] == false) {
            $logger->error(SELF . " " . $rt['msg']);
            setErrorMsg($rt['error'], $rt['msg']);
        } else if ($rt['done'] == true) {
            echo json_encode($result);
            exit;
        }
    }
    if ($derivetexttpl != -1) {//增加植入任务
        $remarks = "来自crawler, taskid:" . $taskinfo->id . "的请求，共{$allcount}条数据";
        $t_r = chunkDeriveTask($importobj, $taskinfo->id, $remarks);
        if ($result['result'] == false) {
            $result['msg'] .= $t_r['msg'];
        } else {
            $result['msg'] = $t_r['msg'];
        }
        if (!$t_r['result']) {
            $logger->error(SELF . " " . $t_r['msg']);
        } else {
            $logger->info(SELF . " " . $t_r['msg']);
        }
        $result['result'] = $t_r['result'];//新增任务成功后则继续
    }
    $result['info'] = formatResultInfo();
    if ($result['result'] == false) {
        if (!empty($result['retry'])) {
            $result['errorcode'] = -3;
            unset($result['retry']);
        } else {
            $result['errorcode'] = -1;
        }
        $result['error'] = $result['msg'];
        $logger->error($result['msg']);
        unset($result['result']);
        unset($result['msg']);
    }

    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == "remotearticledetail") { //远程导入文章详情
    try {
        if ($chkr != CHECKSESSION_SUCCESS) {
            setErrorMsg($chkr, "未登录或登陆超时!");
        }
        if (empty($HTTP_RAW_POST_DATA)) {
            setErrorMsg(1, "未提交数据");
        }
        $taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
        $dictionary_plan_new = "";
        if ($taskinfo->id != NULL) {
            //从数据库获取任务方案
            $dictionary_plan_new = queryDictionaryPlan($taskinfo->id);
        }

        $logger->debug(SELF . " recived import remotearticledetail data : " . $HTTP_RAW_POST_DATA);
        $HTTP_RAW_POST_DATA = my_iconv("UTF-8", "GBK//TRANSLIT", $HTTP_RAW_POST_DATA);//忽略非法字符
        $HTTP_RAW_POST_DATA = my_iconv("GBK", "UTF-8", $HTTP_RAW_POST_DATA);//转回utf8
        $beforepostdata = json_decode($HTTP_RAW_POST_DATA, true);
        //打印必要的info,作为调试
        $loginfoarr = array();
        if (isset($beforepostdata['data']) && count($beforepostdata['data']) > 0) {
            $firstdata = $beforepostdata['data'][0];
            if (isset($firstdata['post_title'])) {
                $loginfoarr['post_title'] = $firstdata['post_title'];
            }
            if (isset($firstdata['column'])) {
                $loginfoarr['column'] = $firstdata['column'];
            }
            if (isset($firstdata['column1'])) {
                $loginfoarr['column1'] = $firstdata['column1'];
            }
            if (isset($firstdata['original_url'])) {
                $loginfoarr['original_url'] = $firstdata['original_url'];
            }
            if (isset($firstdata['page_url'])) {
                $loginfoarr['page_url'] = $firstdata['page_url'];
            }
            if (isset($firstdata['sourceid'])) {
                $loginfoarr['sourceid'] = $firstdata['sourceid'];
            }
        }
        $logger->info(__FILE__ . __LINE__ . " recived import remotearticledetail taskid:" . $taskinfo->id . " summary data : " . var_export($loginfoarr, true));
        /*
        //对sourceid 进行处理爬虫返回的是域名, 根据域名查找对应的sourceid, 查找到返回sourceid,否则直接使用域名
        if(isset($beforepostdata['sourceid']))
            $sourceid = $beforepostdata['sourceid'];
        else if(isset($beforepostdata['page_url']))
        {
            $sourceid = get_sourceid_from_url($beforepostdata['page_url']);
            if($sourceid != NULL){
                $beforepostdata['sourceid'] = $sourceid;
            }
        }
        $beforepostdata['source_host'] = isset($beforepostdata['page_url'])? get_host_from_url($beforepostdata['page_url']): NULL;
         */
        $postdata = formatPostdata($beforepostdata);
        $logger->debug(__FILE__ . __LINE__ . " postdata " . var_export($postdata, true));
        if (empty($postdata) || empty($postdata['data'])) {
            setErrorMsg(1, "数据为空");
        }
        $page = isset($postdata['page']) ? $postdata['page'] : null;
        $logger->debug(__FILE__ . __LINE__ . " taskinfo " . var_export($taskinfo, true));
        if (!empty($taskinfo)) {
            $rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $postdata['data'], $page, 1);
            if ($rt['result'] == false) {
                $logger->error(SELF . " " . $rt['msg']);
                setErrorMsg($rt['error'], $rt['msg']);
            } else if ($rt['done'] == true) {
                echo json_encode($result);
                exit;
            }
        }
        if (isset($postdata['sourceid'])) {
            $task->tasksource = $postdata['sourceid'];
            $task->taskparams->source = $postdata['sourceid'];
        }
        //$isseed = empty($postdata['isseed']) ? false : true;//是否种子微博
        $allcount = count($postdata['data']);
        $deriveusertpl = empty($postdata['deriveusertpl']) ? -1 : $postdata['deriveusertpl'];//派生抓取用户任务模板
        if ($deriveusertpl != -1) {//派生抓取用户任务
            $remarks = "来自crawler,taskid:" . $taskinfo->id . "的请求，共" . $allcount . "条数据,";
            $r = chunkDeriveTask($postdata, $taskinfo->id, $remarks);
            if (!$r['result']) {
                $logger->error(SELF . " " . $r['msg']);
            } else {
                $logger->info(SELF . " " . $r['msg']);
            }
        }

        $r_info = array();//统计信息
        $usertimeline = !empty($postdata['usertimeline']) && $allcount > 0;
        $sw_r = true;
        $sourceid = isset($postdata['sourceid']) ? $postdata['sourceid'] : NULL;
        $r = addweibo($sourceid, $postdata['data'], 0, 'show_status', false, true); //允许数据不全
        $incomplete_count = 0;
        if ($r['result'] && $usertimeline) {
            $left = array();
            $right = array();
            for ($i = 0; $i < $allcount; $i++) {
                if (!empty($left) && !empty($right)) {
                    break;
                }
                if (empty($left) && empty($postdata['data'][$i]['deleted']) && !empty($postdata['data'][$i]['user'])) {
                    $left['id'] = $postdata['data'][$i]['id'];
                    $left['created_at'] = $postdata['data'][$i]['created_at_ts'];
                    $left['userid'] = $postdata['data'][$i]['user']['id'];
                    if (isset($postdata['data'][$i]['user']['statuses_count'])) {
                        $left['statuses_count'] = $postdata['data'][$i]['user']['statuses_count'];
                    }
                }
                if (empty($right) && empty($postdata['data'][$allcount - $i - 1]['deleted']) && !empty($postdata['data'][$allcount - $i - 1]['user'])) {
                    $right['id'] = $postdata['data'][$allcount - $i - 1]['id'];
                    $right['created_at'] = $postdata['data'][$allcount - $i - 1]['created_at_ts'];
                    $right['userid'] = $postdata['data'][$allcount - $i - 1]['user']['id'];
                    if (isset($postdata['data'][$allcount - $i - 1]['user']['statuses_count'])) {
                        $right['statuses_count'] = $postdata['data'][$allcount - $i - 1]['user']['statuses_count'];
                    }
                }
            }
            if (!empty($left) && !empty($right)) {
                $utl_r = handleUserTimeline($left, $right, $postdata['sourceid']);
                if ($utl_r['result'] == false) {
                    $r['result'] = false;
                    $r['msg'] = $utl_r['msg'];
                    $logger->error(SELF . " " . $r['msg']);
                } else if ($utl_r['stop']) {
                    $r['ctrl'] = array('stop' => true);
                }
            }
        }

        $task->taskparams->scene->incomplete_count = $incomplete_count;
        $task->taskparams->scene->update_user_count += $updateusercount;
        $task->taskparams->scene->insert_user_count += $spiderusercount;
        $r['info'] = formatResultInfo();
    } catch (Exception $e) {
        $r['result'] = false;
        $r['msg'] .= " " . $e->getMessage();
    }
    if ($r['result'] == false) {
        $r['errorcode'] = -1;
        $r['error'] = $r['msg'];
        $logger->error($r['msg']);
        unset($r['result']);
        unset($r['msg']);
    }
    echo json_encode($r);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == TASKTYPE_COMMON) {
    //远程导入文章详情 updateTaskFull($taskobj);
    try {
        //********************测试代码，不用用户登录************************
        //$chkr = CHECKSESSION_SUCCESS;
        //********************测试代码，不用用户登录************************

        //任务代理获得的任务类型是通用任务类型，这种类型的任务是新增任务，以后只有这一种任务
        //$logger->info($result['msg']);

        $logger->info("处理通用类型的任务，type：[" . TASKTYPE_COMMON . "].");
        $requsePostData = $HTTP_RAW_POST_DATA;
        //$result = array("result" => true, "msg" => "");

        //*****************************测试代码-不进行添加文章,直接返回*************************//
        //    $logger->info("测试代码直接返回:[" . var_export($r,true) . "]!");
        //    echo json_encode($r);
        //    exit;
        //*****************************测试代码-不进行添加文章,直接返回*************************//

        if ($chkr != CHECKSESSION_SUCCESS) {
            $logger->error("checksession failed,未登录或登陆超时!");
            setErrorMsg($chkr, "未登录或登陆超时!");
        }

        if (empty($requsePostData)) {
            $logger->error("提交的数据为空, 原始数据:" . $requsePostData);
            $requestData = file_get_contents("php://input");
            if (!isset($requestData) || empty($requestData) || is_null($requestData)) {
                $logger->error("提交的数据为空（file_get_contents）, 原始数据:" . $requestData);
                $requestData = $_POST['fieldname'];
                if (!isset($requestData) || empty($requestData) || is_null($requestData)) {
                    $logger->error("提交的数据为空（_POST['fieldname']）, 原始数据:" . $requestData);
                    setErrorMsg(1, "提交数据为空!");
                }
            }
        }

        $taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
        $dictionary_plan_new = "";
        $taskConfig = null;

        $logger->info("TaskInfo:[" . var_export($taskinfo, true) . "].");
        //从数据库中查询当前任务配置信息
        $taskConfig = getTaskById($taskinfo->id);
        if ($taskinfo->id != NULL) {
            if (empty($taskConfig)) {
                //异常 退出
                $logger->error("can not get task by task id:[" . $taskinfo->id . "].");
                setErrorMsg(4, "can not get task by task id:[" . $taskinfo->id . "].");
            }
            $logger->debug("get task by task id success,TaskConfig:[" . var_export($taskConfig, true) . "] \ndictionary_plan:[" . $dictionary_plan_new . "].");
        } else {
            //异常 退出
            setErrorMsg(3, "task id can not null.");
        }

        //获取任务配置参数
        //获取当前任务的原始配置
        $taskParams_org = &$taskConfig->taskparams;
        $taskParams = converObjToRelArray($taskParams_org);
        //***********************************测试代码**************************
        //$taskParams = getTaskParam4Test($taskinfo->id, $taskConfig->taskparams);
        //***********************************测试代码**************************

        if (!empty($taskParams)) {
            $currentTaskParam = &$taskParams["root"];
        } else {
            setErrorMsg(1, "Task param is null.");
        }

        //***********************************测试代码**************************
        //$currentTaskParam["pathStructMap"] = getpathStructMap4_tm();
        // $currentTaskParam["pathStructMap"] = getpathStructMap();
        //***********************************测试代码**************************


        if (empty($currentTaskParam)) {
            setErrorMsg(1, "CurrentTaskParam param is null. for key:[root]");
        }
        //$logger->debug(__FILE__ . __LINE__ . " currentTaskParam:[ " . var_export($currentTaskParam, true) . "].");

        $logger->debug(SELF . " recived import document data : " . $requsePostData);
        $logger->debug(SELF . "start calling mb_detect_encoding");
        $logger->debug(SELF . " requsePostData =  " . $requsePostData);
        $encode = mb_detect_encoding($requsePostData, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
        $logger->debug(SELF . " encode =  " . $encode);
        if ($encode == "UTF-8") {
            $resultTemp = my_iconv("UTF-8", "GBK//TRANSLIT", $requsePostData);//忽略非法字符
        } else {
            $resultTemp = $requsePostData;
        }

        if (empty($resultTemp)) {
            $logger->debug(SELF . " ?????????! " . $requsePostData);
        } else if ($encode != "UTF-8") {
            $logger->debug(SELF . " ?????????! " . $resultTemp);
            $resultTemp = my_iconv($encode, "UTF-8", $resultTemp);//??utf8
            if (!empty($resultTemp)) {
                $requsePostData = $resultTemp;
            } else {
                $logger->debug(SELF . " ???????????! " . $requsePostData);
            }
        }

        //$logger->debug(__FILE__ . __LINE__ . " test json_decode:" . var_export($requsePostData, true));
        $requsePostData = json_decode($requsePostData, true);
        //$logger->debug(__FILE__ . __LINE__ . " test beforepostdata:" . var_export($requsePostData, true));


        if (isset($currentTaskParam["taskPro"]["filPageTag"]) && $currentTaskParam["taskPro"]["filPageTag"] == true) {
            //需要处理HTML标签，即提取图片、分段(根据<br>)、处理转意字符如< > 等
            $postdata = formatPostdata($requsePostData);
            $logger->debug(__FILE__ . __LINE__ . " FormatPostdata " . var_export($postdata, true));
        } else {
            //不需要处理，抓取到的数据直接能够使用
            $postdata = $requsePostData;
            $logger->debug(__FILE__ . __LINE__ . " no need to FormatPostdata Param:[" . var_export($currentTaskParam["taskPro"], true) . "].");
        }

        if (empty($postdata) || empty($postdata['data'])) {
            setErrorMsg(1, "数据为空");
        }

        //获取从运行时参数
        if (isset($postdata['param']) && isset($postdata['param']['runTimeParam'])) {
            $runTimeURLStr = $postdata['param']['runTimeParam'];
            if (!is_array($runTimeURLStr) && is_string($runTimeURLStr)) {
                $logger->debug(__FILE__ . __LINE__ . " update runTimeParam:[" . $runTimeURLStr . "].");
                $currentTaskParam['runTimeParam'] = json_decode($runTimeURLStr, true);
            }
        }

        //获取页数
        $page = isset($postdata['page']) ? $postdata['page'] : null;

        if (!empty($postdata['page_url'])) {
            $curPageUrl = $postdata['page_url'];
            $currentTaskParam["runTimeParam"][INNER_PARAM_CUR_PAGE_URL] = $curPageUrl;
            $logger->debug(__FILE__ . __LINE__ . " set run_url success! Value:[" . var_export($curPageUrl, true) . "].");
        }

        $logger->debug(__FILE__ . __LINE__ . " taskinfo " . var_export($taskinfo, true));

        if (isset($postdata['sourceid'])) {

            $logger->debug(__FILE__ . __LINE__ . " sourceId is seteed:[ " . $postdata['sourceid'] . "].");

            $task->tasksource = $postdata['sourceid'];
            $currentTaskParam["runTimeParam"]["source"] = $postdata['sourceid'];

        } else if (isset($postdata['page_url'])) {

            $logger->debug(__FILE__ . __LINE__ . " sourceId is not setted,get sourceid by page_url:[ " . $postdata['page_url'] . "].");

            $source = get_sourceid_from_url($postdata['page_url']);

            $logger->debug(__FILE__ . __LINE__ . " sourceId is not setted,get sourceid by page_url:[ " . $postdata['page_url'] . "] success,sourceId:[" . $source . "].");

            if ($source != NULL) {
                $currentTaskParam["runTimeParam"]["source"] = $source;
                $task->tasksource = $source;
            }
        }

        //默认情况下 从结果中去data作为所有需要结果数据来处理
        if (empty($currentTaskParam["outData"]) || empty($currentTaskParam["outData"]["datasPath"])) {
            //$logger->error(__FILE__ . __LINE__ . " generate task exception:['dataPath null']" . var_export($genTaskCfg, true));
            $resultDatas = $postdata['data'];
        } else {
            $dataPathId = $currentTaskParam["outData"]["datasPath"];
            $dataPathId = substr($dataPathId, 1, strlen($dataPathId) - 2);
            $pathStr = $currentTaskParam["pathStructMap"][$dataPathId];
            $logger->debug(__FILE__ . __LINE__ . " function " . __FUNCTION__ . " 根据our路径获取抓取结果,### dataPathId:[" . $dataPathId . "] pathStr:[" . var_export($pathStr, true) . "].");
            $resultDatas = getValueFromObjWrap($postdata['data'], $pathStr);
        }
        $allcount = count($resultDatas);
        $logger->debug(__FILE__ . __LINE__ . " function " . __FUNCTION__ . " ### 本次总共抓取到了:[" . $allcount . "] 条数据!");

        if (!empty($taskinfo)) {
            // 更新任务参数 主要用于计算任务轨迹
            $rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $resultDatas, $page, 1);
            if ($rt['result'] == false) {
                $logger->error(SELF . " " . $rt['msg']);
                setErrorMsg($rt['error'], $rt['msg']);
            } else if ($rt['done'] == true) {
                echo json_encode($result);
                exit;
            } else {
                $logger->debug(__FILE__ . __LINE__ . " success update taskinfo!");
            }
        } else {
            $logger->debug(__FILE__ . __LINE__ . " taskinfo is null!");
        }
        //*****************************************判断是否需要生任务*********************************//
        //$isseed = empty($postdata['isseed']) ? false : true;//是否种子微博

        //********************************根据配置判读当前任务是否需要入库(即当前任务抓取到的数据是否需要插入到数据库中)************************//
        //测试代码,以后需要从数据库中查询分词字典
        //$dictionary_plan_new ="0001";
        $saveData = $currentTaskParam["taskPro"]["dataSave"];
        if ($saveData) {
            $insertDataStartTime = time();
            //******************测试数据********************//
//                                $resultDatas = array_slice($resultDatas, 0, 1, false);
            //$resultDatas = array($resultDatas[0]);
            //*********************************************//


            $logger->info(SELF . " " . " --+-处理通用任务--+-准备为当前任务taskid:" . $taskinfo->id . "] 数据入库...");
            //从数据库获取任务方案
            $dictionary_plan_new = queryDictionaryPlan($taskinfo->id);

            //数据入库
            $r_info = array();//统计信息
            $usertimeline = !empty($postdata['usertimeline']) && $allcount > 0;
            $sw_r = true;
            $sourceid = isset($postdata['sourceid']) ? $postdata['sourceid'] : NULL;

            global $task;
            if ($task->task != $taskConfig->task) {
                $task->task = $taskConfig->task;
                //$task->remarks = "wangchaochao 设置的全局变量";
            }
            $task->taskparams = &$taskParams_org;
            $sourceHost = '';
            if (!empty($postdata['page_url'])) {
                $sourceHost = get_host_from_url($postdata['page_url']);
            } else {
                throw new Exception("get_host_from_url exception, curPageUrl null!");
            }
            //将source_host 设置到每一条抓取数据中去
            if (!empty($sourceHost)) {
                $dataLen = count($resultDatas);
                $logger->info(SELF . " " . " --+-处理通用任务--+-为所有的数据添加sourceHost以及page_url，数据总条数:[" . $dataLen . "].");
                if ($dataLen) {
                    for ($k = 0; $k < $dataLen; $k++) {
                        $resultDatas[$k]['source_host'] = $sourceHost;
                        $resultDatas[$k]['page_url'] = $postdata['page_url'];
                    }
                }
            } else {
                $logger->info(SELF . " " . " --+-处理通用任务--+-获取sourceHost失败，sourceHost为空:[" . $sourceHost . "].");
            }

            if ($dataLen) {
                $curTime = time();
                $curTimeStr = date('y-m-d H:i:s', $curTime);

                //***************测试数据***************//
//                $curTime = strtotime("16-06-06 14:43:38");
//                $curTimeStr = date('y-m-d H:i:s', $curTime);
                //***************测试数据***************//

                //是否配置了自动补全时间
                if (isset($currentTaskParam["taskPro"]["genCreatedAt"]) && $currentTaskParam["taskPro"]["genCreatedAt"] == true) {
                    $logger->info(SELF . " " . " --+-处理通用任务--+-补全字段:[created_at]，数据总条数:[" . $dataLen . "] 缺省时间:[" . $curTimeStr . "].");
                    for ($k = 0; $k < $dataLen; $k++) {
                        if (!isset($resultDatas[$k]['created_at']) || empty($resultDatas[$k]['created_at'])) {
                            $logger->info(SELF . " " . " --+-处理通用任务--+-补全字段:[created_at]，原始值:[]" . " 修改为当前时间:[" . $curTimeStr . "].");
                            $resultDatas[$k]['created_at'] = $curTime;

                            //***************测试数据***************//
//                        $resultDatas[$k]['proCurPrice'] = 7890;
                            //***************测试数据***************//
                        }
                    }
                }
            }

            $logger->info(SELF . " " . "add all grabData:[" . var_export($resultDatas, true) . "].");
            //允许数据不全 将抓取到的数据插入到数据库中

            //网站source_host 对于名 对应的id
            $r = addweibo($sourceid, $resultDatas, 0, 'show_status', false, true);

            if (!$r["result"]) {
                $logger->error(__FILE__ . " " . __LINE__ . " insert data for current task exception, all result data:[" . var_export($r, true) . ".");
                throw new Exception("为当前任务taskid:" . $taskinfo->id . "]插入数据异常,ErrMsg:[" . $r["msg"] . "].");
            } else {
                $logger->info(SELF . " " . "成功为当前任务taskid:" . $taskinfo->id . "]插入数据!");
            }
            $incomplete_count = 0;
            $task->taskparams->scene->incomplete_count = $incomplete_count;
            if (isset($updateusercount)) {
                if (isset($task->taskparams->scene->update_user_count)) {
                    $task->taskparams->scene->update_user_count += $updateusercount;
                } else {
                    $task->taskparams->scene->update_user_count = $updateusercount;
                }
            }

            if (isset($spiderusercount)) {
                if (isset($task->taskparams->scene->insert_user_count)) {
                    $task->taskparams->scene->insert_user_count += $spiderusercount;
                } else {
                    $task->taskparams->scene->insert_user_count = $spiderusercount;
                }
            }
            $resultInfos = formatResultInfo();
            $result['info'] = $resultInfos;
            $insertDataEndTime = time();
            $logger->info(SELF . " " . " --+-处理通用任务--+-数据入库，数据总条数:[" . $dataLen . "] 耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
        } else {
            $logger->info(SELF . " " . "数据不需要入库. 当前任务taskid:" . $taskinfo->id . "] dataSize:[" . $allcount . "].");
        }

        $logger->info(SELF . " " . "当前任务taskid:[" . $taskinfo->id . "] ResultSize:[" . $allcount . "].");
        //********************************当前数据需要生成子任务****************根据配置生成子任务************************//
        if (((isset($currentTaskParam["taskPro"]['isGenChildTask']) && $currentTaskParam["taskPro"]['isGenChildTask'] == true))) {
            if ((!empty($currentTaskParam["taskGenConf"]) && $currentTaskParam["taskGenConf"])) {
                //当前任务需要派生任务
                if ($allcount > 0) {
                    $remarks = "任务:[" . $taskinfo->id . "]###的子任务";
                    $genTaskCfgs = $currentTaskParam["taskGenConf"];
                    $logger->info(SELF . " " . "准备为当前任务taskid:" . $taskinfo->id . "] 抓取结果派生子任务，当前任务共有:[" . $allcount . "]条数据,配置参数:[" . var_export($genTaskCfgs, true) . "].");

                    //派生子任务的数据结构
                    // "taskGenConf": [
                    //    {
                    //      "dataPath": "datas[].user",
                    //      "splitStep": 1,
                    //      "childTaskUrl": "datas[].user.page_url",
                    //      "childTaskDefId": "0002",
                    //      //需要传递给子任务的数据，根据参数名获取
                    //      "params": [
                    //          {
                    //            "paramName": "uiserId",
                    //             "dataType": "int",
                    //             "paraType": "cons|vari", //变量或者常量
                    //              //变量来源:{1:全局变量(父任务设置的全局变量一般值的是内置变量) 2:全局常量(父任务中配置的全局常量) 3:当前任务的一次抓取记录}
                    //               "paramSource": 2,
                    //                "value": "${datas[].user.id}|000001" //变量表达式写法|常量写法
                    //          }
                    //       ]
                    //  }
                    //]

                    //*****************************测试代码**************************//
//                                if($taskinfo->id==2){
//                                    $resultDatas = array_slice($resultDatas,0,1,true );
//                                }else{
//                                    $resultDatas = array_slice($resultDatas,0,1,true );
//                                }
                    if (empty($genTaskCfgs)) {
                        $logger->error(__FILE__ . " " . __LINE__ . " generate task exception:['taskGenConf null']!");
                        setErrorMsg(1, "generate task exception:['taskGenConf null']");
                    }

                    if (!is_array($genTaskCfgs)) {
                        $logger->error(__FILE__ . " " . __LINE__ . " generate task exception:['genTaskCfgs' must be array']!");
                        setErrorMsg(1, "generate task exception:['genTaskCfgs must be array']");
                    }
                    $logger->debug(SELF . " " . "用于生成子任务的数据条数:[" . count($resultDatas) . "]!");

                    //根据配置生成N个子任务
                    foreach ($genTaskCfgs as $genIdx => $genTaskConfig) {

                        $logger->debug(__FILE__ . " " . __LINE__ . "根据第:[{$genIdx}]个子任务生成配置生成子任务...");

                        //默认每条记录生成一个子任务
                        $taskNumPerChild = empty($genTaskConfig["splitStep"]) ? 1 : $genTaskConfig["splitStep"];

                        //子任务参数定义Id
                        $childParamId = $genTaskConfig["childTaskDefId"];
                        if (empty($childParamId)) {
                            $logger->error(__FILE__ . __LINE__ . " generate task exception:['childTaskDefId null']" . var_export($genTaskConfig, true));
                            setErrorMsg(1, "generate task exception:['childTaskDefId null']");
                        }

                        $childTaskParam = $taskParams[$childParamId];
                        if (empty($childTaskParam)) {
                            $logger->error(__FILE__ . __LINE__ . " generate task exception:['childTaskDef null'] for childTaskDefId:[" . $childParamId . "] AllConfig:[" . var_export($genTaskCfg, true));
                            setErrorMsg(1, "generate task exception:['childTaskDef null']");
                        }

                        $addResult = chunkDeriveTask4Common($resultDatas, $taskParams, $currentTaskParam, $childParamId, $childTaskParam, $taskNumPerChild, $genIdx, $remarks);
                        if ($addResult) {
                            $logger->debug(SELF . " " . "为当前任务taskid:" . $taskinfo->id . "]生成子任务成功!");
                        } else {
                            //$logger->debug(SELF . " " . "为当前任务taskid:" . $taskinfo->id . "]生成子任务失败!");
                            throw new Exception("Generator child task execption,taskId:[" . $taskinfo->id . "].");
                        }
                        $logger->debug(__FILE__ . " " . __LINE__ . "根据第:[{$genIdx}]个子任务生成配置生成子任务成功!");
                    }
                } else {
                    $logger->info(SELF . " " . "不需要为当前任务taskid:" . $taskinfo->id . "] 抓取结果派生子任务，当前任务共有:[" . $allcount . "]条数据.");
                }
            } else {
                $logger->info(SELF . " " . "子任务生成配置:[taskGenConf] null!,不生成子任务!");
            }
        } else {
            $logger->info(SELF . " " . "当前任务taskid:[" . $taskinfo->id . "] 不生成子任务,属性[taskPro.isGenChildTask]为false,isGenChildTask:[" . var_export($currentTaskParam["taskPro"], true) . "].");
        }
        $logger->debug(SELF . " " . "为当前任务taskid:[" . $taskinfo->id . "] 处理抓取数据成功! result:[" . var_export($result, true) . "].");
        //最后跟新该参数
    } catch (Exception $e) {
        $logger->error(SELF . " 为通用任务添加文章异常:[" . $e->getMessage() . "].");
        $result['result'] = false;
        $result['msg'] .= " " . $e->getMessage();
    }
    if ($result['result'] == false) {
        $result['errorcode'] = -1;
        $result['error'] = $result['msg'];
        $logger->error($result['msg']);
        unset($result['result']);
        unset($result['msg']);
    }
    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == TASKTYPE_COMMON_CACHE) {
    //当前分支 用于通用远程任务 - cache，新版本的通用远程任务将数据抓取与数据入库分开:
    //  第一个阶段为，爬虫根据任务抓取数据，并将数据提交的服务器，服务器根据任务id等信息，将任务状态更新为完成状态.并进行数据校验等。最后将数据写入到缓存中去.
    //  第二个阶段为: 缓存中的数据通过定时器或者主动给拉取的方式进行数据入库. 类型为:TASKTYPE_COMMON_FLUSH
    try {
        ini_set("precision", 3);
        $logger->info(__FILE__ . __LINE__ . "处理通用类型的任务，type：[" . TASKTYPE_COMMON_CACHE . "] ...");
        $requsePostData = $HTTP_RAW_POST_DATA;
        $logger->info(__FILE__ . __LINE__ . "the post data" . var_export($requsePostData, true) . "].");
//        $requsePostData = json_decode($requsePostData, true);
//        $logger->info(__FILE__ . __LINE__ . "the post data" . var_export($requsePostData, true) . "].");
//        die;

        //*****************************测试代码-不进行添加文章,直接返回*************************//
        //    $logger->info("测试代码直接返回:[" . var_export($r,true) . "]!");
        //    echo json_encode($r);
        //    exit;
        //*****************************测试代码-不进行添加文章,直接返回*************************//
        if ($chkr != CHECKSESSION_SUCCESS) {
            $logger->error(__FILE__ . __LINE__ . "checksession failed,未登录或登陆超时!");
            setErrorMsg($chkr, "未登录或登陆超时!");
        }
        if (empty($requsePostData)) {
            $logger->error(__FILE__ . __LINE__ . "提交的数据为空, 原始数据:" . $requsePostData);
            $requestData = file_get_contents("php://input");
            if (!isset($requestData) || empty($requestData) || is_null($requestData)) {
                $logger->error(__FILE__ . __LINE__ . "提交的数据为空（file_get_contents）, 原始数据:" . $requestData);
                $requestData = $_POST['fieldname'];
                if (!isset($requestData) || empty($requestData) || is_null($requestData)) {
                    $logger->error(__FILE__ . __LINE__ . "提交的数据为空（_POST['fieldname']）, 原始数据:" . $requestData);
                    setErrorMsg(1, "提交数据为空!");
                }
            }
        }

        $taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
        $dictionary_plan_new = "";
        $taskConfig = null;
        $logger->info(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-获取任务信息成功:[" . var_export($taskinfo, true) . "].");
        //从数据库中查询当前任务配置信息
        $taskConfig = getTaskById($taskinfo->id);
        if ($taskinfo->id != NULL) {
            if (empty($taskConfig)) {
                //异常 退出
                $logger->error(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-从数据库加载任务失败:[task id:" . $taskinfo->id . "].");
                setErrorMsg(4, "处理通用任务:[remotecommtask_cache]--+-从数据库加载任务失败:[task id:" . $taskinfo->id . "].");
            }
            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-从数据库加载任务成功--+-TaskConfig:[" . var_export($taskConfig, true) . "].");
        } else {
            //异常 退出
            setErrorMsg(3, "task id can not null.");
        }
        $logger->info(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-从数据库加载任务成功!");

        //获取任务配置参数
        //获取当前任务的原始配置
        $taskParams_org = &$taskConfig->taskparams;
        $taskParams = converObjToRelArray($taskParams_org);
        //***********************************测试代码**************************
        //$taskParams = getTaskParam4Test($taskinfo->id, $taskConfig->taskparams);
        //***********************************测试代码**************************

        if (!empty($taskParams)) {
            $currentTaskParam = &$taskParams["root"];
        } else {
            setErrorMsg(1, "Task param is null for task:[" . $taskinfo->id . "].");
        }

        //***********************************测试代码**************************
        //$currentTaskParam["pathStructMap"] = getpathStructMap4_tm();
        // $currentTaskParam["pathStructMap"] = getpathStructMap();
        //***********************************测试代码**************************

        if (empty($currentTaskParam)) {
            setErrorMsg(1, "CurrentTaskParam param is null. for key:[root]");
        }

        $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-读入请求数据成功. 数据:[" . $requsePostData . "].");
        $encode = mb_detect_encoding($requsePostData, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
        $logger->info(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-获取请求数据编码成功--+-编码:[" . $encode . "].");

        if ($encode == "UTF-8") {
//            $resultTemp = my_iconv("UTF-8", "GBK//TRANSLIT", $requsePostData);//忽略非法字符
//            $resultTemp = my_iconv("GBK", "UTF-8", $requsePostData);//转回utf8
//            if (empty($resultTemp)) {
//                $logger->error(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据[utf8]--+-去除特殊字符失败. 处理后结果为空:[" . $resultTemp . "].");
//                setErrorMsg(1, "处理请求数据--+-去除utf8编码的特殊字符失败!");
//            } else {
//                $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据[utf8]--+-去除特殊字符成功:[" . $resultTemp . "].");
//            $requsePostData = $resultTemp;
//            }
        } else {
//            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据编码--+-将当前编码转化为UTF8编码. 当前编码:[" . $encode . "].");
//            $resultTemp = my_iconv($encode, "UTF-8", $resultTemp);
//            if (empty($resultTemp)) {
//                $logger->error(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据编码--+-将当前编码转化为UTF8编码失败.转化后数据为空. 当前编码:[" . $encode . "].");
//                setErrorMsg(1, "处理请求数据--+-编码转化失败:[请将编码转化为utf8]");
//            } else {
//            $requsePostData = $resultTemp;
//            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据编码--+-将当前编码转化为UTF8编码成功.数据:[" . $requsePostData . "].");
//            }
        }

        $logger->info(__FILE__ . __LINE__ . "json decode:[" . var_export($requsePostData, true) . "].");
        $requsePostData = json_decode($requsePostData, true);
//        $requsePostData['data'][0]['serviceScore'] = 9;
//        $requsePostData['data'][0]['compDesMatch'] = 9;
//        $requsePostData['data'][0]['satisfaction'] = 9;
//        $requsePostData['data'][0]['logisticsScore'] = 9;
        $logger->info(__FILE__ . __LINE__ . "json decode:[" . var_export($requsePostData, true) . "].");

        if (isset($currentTaskParam["taskPro"]["filPageTag"]) && $currentTaskParam["taskPro"]["filPageTag"] == true) {
            //需要处理HTML标签，即提取图片、分段(根据<br>)、处理转意字符如< > 等
            $postdata = formatPostdata($requsePostData);
            if (empty($postdata)) {
                $logger->error(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-过滤Html标签失败.过滤后数据为空:[" . var_export($postdata, true) . "].");
                setErrorMsg(1, "过滤Html标签失败.过滤后数据为空.");
            } else {
                $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-过滤Html标签成功.数据:[" . var_export($postdata, true) . "].");
            }
        } else {
            //不需要处理，抓取到的数据直接能够使用
            $postdata = $requsePostData;
            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-不过滤Html标签.数据:[" . var_export($postdata, true) . "].");
        }

        $logger->info(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据[转化编码|过滤html标签成功].数据:[" . var_export($postdata, true) . "].");

        //获取从运行时参数
        if (isset($postdata['param']) && isset($postdata['param']['runTimeParam'])) {
            $runTimeURLStr = $postdata['param']['runTimeParam'];
            if (!is_array($runTimeURLStr) && is_string($runTimeURLStr)) {
                $logger->info(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-update runTimeParam. AllRunTimeParams:[" . $runTimeURLStr . "].");
                $currentTaskParam['runTimeParam'] = json_decode($runTimeURLStr, true);
            }
        }

        //获取页数
        $page = isset($postdata['page']) ? $postdata['page'] : null;

        if (!empty($postdata['page_url'])) {
            $curPageUrl = $postdata['page_url'];
            $currentTaskParam["runTimeParam"][INNER_PARAM_CUR_PAGE_URL] = $curPageUrl;
            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-set current page url to runtimeParams success! curPageUrl:[" . $runTimeURLStr . "].");
        }

        if (isset($postdata['sourceid'])) {
            $task->tasksource = $postdata['sourceid'];
            $currentTaskParam["runTimeParam"]["source"] = $postdata['sourceid'];
            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-sourceId is setted by client. set sourceId to task. sourceid:[" . $postdata['sourceid'] . "].");
        } else if (isset($postdata['page_url'])) {
            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-sourceId is not setted. get sourceid by page_url:[" . $postdata['page_url'] . "].");
            $source = get_sourceid_from_url($postdata['page_url']);
            $logger->debug(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-sourceId is not setted. get sourceid by page_url success. sourceId:[" . $source . "] PageURL:[" . $postdata['page_url'] . "].");
            if ($source != NULL) {
                $currentTaskParam["runTimeParam"]["source"] = $source;
                $task->tasksource = $source;
            }
        }

        //默认情况下 从结果中去data作为所有需要结果数据来处理
        if (empty($currentTaskParam["outData"]) || empty($currentTaskParam["outData"]["datasPath"])) {
            $resultDatas = $postdata['data'];
        } else {
            $dataPathId = $currentTaskParam["outData"]["datasPath"];
            $dataPathId = substr($dataPathId, 1, strlen($dataPathId) - 2);
            $pathStr = $currentTaskParam["pathStructMap"][$dataPathId];
            $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-根据[out]路径获取抓取文章数据,dataPathId:[" . $dataPathId . "] pathStr:[" . var_export($pathStr, true) . "].");
            $resultDatas = getValueFromObjWrap($postdata['data'], $pathStr);
            $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-根据[out]路径获取抓取文章数据成功. 文章数据:[" . var_export($resultDatas, true) . "].");
        }
        $allcount = count($resultDatas);
        $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-根据[out]路径获取抓取文章数据成功. 本次抓取数据:[" . $allcount . "]条.");


//        if (!is_array($requsePostData)) {
//            $logger->error(__FILE__ . __LINE__ . "处理通用任务:[remotecommtask_cache]--+-处理数据错误--+-数据格式有误:[current data is not a array!]");
//            setErrorMsg(4, "处理通用任务:[remotecommtask_cache]--+-数据格式有误:[current data is not a array!] correct data format is : [{name:xxx,age:1},{name:xxx,age:2}]");
//        }

        if (!empty($taskinfo)) {
            // 更新任务参数 主要用于计算任务轨迹
            //add by zuo:2017-3-7
            $hostcheck = 1;
            if (isset($taskinfo->hostcheck)) {
                $hostcheck = $taskinfo->hostcheck;
            }
            //end by zuo:2017-3-7
            $rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $resultDatas, $page, 1, $hostcheck);
            if ($rt['result'] == false) {
                $logger->error(SELF . " " . $rt['msg']);
                setErrorMsg($rt['error'], $rt['msg']);
            } else if ($rt['done'] == true) {
                echo json_encode($result);
                exit;
            } else {
                $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-更新任务信息成功! host:[" . $taskinfo->host . "] stat:[" + $taskinfo->stat . "] page:[" . $page . "].");
            }
        } else {
            $logger->warn(__FILE__ . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-不用更新任务信息:[taskinfo is null]");
        }
        //********************************根据配置判读当前任务是否需要入库(即当前任务抓取到的数据是否需要插入到数据库中)************************//
        //测试代码,以后需要从数据库中查询分词字典
        //$dictionary_plan_new ="0001";
        $saveData = $currentTaskParam["taskPro"]["dataSave"];
        if ($saveData) {
            $insertDataStartTime = time();
            //******************测试数据********************//
            // $resultDatas = array_slice($resultDatas, 0, 1, false);
            //$resultDatas = array($resultDatas[0]);
            //*********************************************//

            $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-准备为当前任务taskid:" . $taskinfo->id . "] 进行数据缓存...");
            //从数据库获取任务方案
            $dictionary_plan_new = queryDictionaryPlan($taskinfo->id);
            $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-准备为当前任务taskid:" . $taskinfo->id . "] 进行数据缓存--+-获取分词方案成功:[" . var_export($dictionary_plan_new, true) . "].");

            //数据入库
            $r_info = array();//统计信息
            $usertimeline = !empty($postdata['usertimeline']) && $allcount > 0;
            $sw_r = true;
            $sourceid = isset($postdata['sourceid']) ? $postdata['sourceid'] : NULL;

            global $task;
            if ($task->task != $taskConfig->task) {
                $task->task = $taskConfig->task;
            }
            $task->taskparams = &$taskParams_org;
            $sourceHost = '';
            if (!empty($postdata['page_url'])) {
                $sourceHost = get_host_from_url($postdata['page_url']);
            } else {
                throw new Exception("get_host_from_url exception, curPageUrl null!");
            }
            //将source_host 设置到每一条抓取数据中去
            if (!empty($sourceHost)) {
                $dataLen = count($resultDatas);
                $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-为数据添加sourceHost以及page_url,数据总条数:[" . $dataLen . "].");
                if ($dataLen) {
                    for ($k = 0; $k < $dataLen; $k++) {
                        $resultDatas[$k]['source_host'] = $sourceHost;
                        $resultDatas[$k]['page_url'] = $postdata['page_url'];
                    }
                }
            } else {
                $logger->warn(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-获取sourceHost失败，sourceHost为空:[" . $sourceHost . "].");
            }

            if ($dataLen) {
                $curTime = time();
                $curTimeStr = date('y-m-d H:i:s', $curTime);

                //***************测试数据***************//
//                $curTime = strtotime("16-06-06 14:43:38");
//                $curTimeStr = date('y-m-d H:i:s', $curTime);
                //***************测试数据***************//

                //是否配置了自动补全时间
                if (isset($currentTaskParam["taskPro"]["genCreatedAt"]) && $currentTaskParam["taskPro"]["genCreatedAt"] == true) {
                    $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-补全字段:[created_at]，数据总条数:[" . $dataLen . "] 缺省时间:[" . $curTimeStr . "].");
                    for ($k = 0; $k < $dataLen; $k++) {
                        if (!isset($resultDatas[$k]['created_at']) || empty($resultDatas[$k]['created_at'])) {
                            $logger->debug(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-补全字段:[created_at]，原始值:[]" . " 修改为当前时间:[" . $curTimeStr . "].");
                            $resultDatas[$k]['created_at'] = $curTime;
                            //***************测试数据***************//
//                        $resultDatas[$k]['proCurPrice'] = 7890;
                            //***************测试数据***************//
                        }
                    }
                }
            }

            //在这里转化
            $dictionary_plan_new = formatDictionaryPlan($dictionary_plan_new);

            // 可以现将任务参数设置成空的
            // $cacheReqData = array("taskId" => $taskinfo->id, "dictPlan" => $dictionary_plan_new, "taskParam" => $currentTaskParam, "sourceid" => $sourceid);
            $cacheReqData = array("taskId" => $taskinfo->id, "dictPlan" => $dictionary_plan_new, "taskParam" => "{}", "sourceid" => $sourceid);

            $cacheReqData["datas"] = &$resultDatas;

            $currentPort = getCurrrentSrvPort();
            $currentHost = getCurrrentSrvHost();

            $cacheReqData["currentPort"] = $currentPort;
            $cacheReqData["currentHost"] = $currentHost;
            $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-将要缓存的数据:[" . var_export($cacheReqData, true) . "].");

            $serverHost = getCurrrentSrvAddress();

            //TODO 这个参数需要给成配置
            $targetDataType = IMPORT_DATA_TARGET_MONGO;

            if ($targetDataType == IMPORT_DATA_TARGET_H2) {
                $reqURL = SOLR_URL_CACHE . "&serverHost=" . $serverHost . "&cacheServerName=" . $cacheNameCurPort;
                $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 数据入库,将数据导入到H2缓存中...");
            } else if ($targetDataType == IMPORT_DATA_TARGET_MONGO) {
                //将数据写入到monge中
                //$reqURL = SOLR_URL_CACHE . "&cacheServerName=" . $cacheNameCurPort;
                //$reqURL = "http://10.28.36.186:9080/otaapi/DocCache/" . SOLR_PARAM_CACHE . "&cacheServerName=" . $cacheNameCurPort;
                $reqURL = "http://" . CACHE_MOGO_CACHE_IP . ":" . CACHE_MOGO_CACHE_PORT . CACHE_MOGO_CACHE_PATH . $cacheNameCurPort;
                $reqURL = "http://192.168.0.170:7080/otaapi/DocCache/cachedoc?type=insert&cacheServerName=cache01";
//                $reqURL = "http://192.168.3.104:7080/otaapi/DocCache/cachedoc?type=insert&cacheServerName=cache02";
                $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 数据入库,将数据导入到Mongo中...");
            }

            $cacheResult = send_solr($cacheReqData, $reqURL, "Content-type:text/plain;charset=utf-8");

            if ($cacheResult === false) {
                $logger->error(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-发送缓存数据失败! URL: " . $reqURL);
                throw new Exception("为当前任务taskid:[" . $taskinfo->id . "] 数据缓存--+-发送缓存数据失败!ErrMsg:[" . $cacheResult["msg"] . "].");
            } else {
                $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-发送缓存数据成功! taskid:[" . $taskinfo->id . "] URL: " . $reqURL);
            }

            $incomplete_count = 0;
            $task->taskparams->scene->incomplete_count = $incomplete_count;
            if (isset($updateusercount)) {
                if (isset($task->taskparams->scene->update_user_count)) {
                    $task->taskparams->scene->update_user_count += $updateusercount;
                } else {
                    $task->taskparams->scene->update_user_count = $updateusercount;
                }
            }
            if (isset($spiderusercount)) {
                if (isset($task->taskparams->scene->insert_user_count)) {
                    $task->taskparams->scene->insert_user_count += $spiderusercount;
                } else {
                    $task->taskparams->scene->insert_user_count = $spiderusercount;
                }
            }
            $resultInfos = formatResultInfo();
            $result['info'] = $resultInfos;
            $insertDataEndTime = time();
            $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-数据总条数:[" . $dataLen . "] 耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
        } else {
            $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . "处理通用任务:[remotecommtask_cache]--+-数据缓存--+数据不需要入库. taskId:[" . $taskinfo->id . "] ResultSize:[" . $allcount . "].");
        }

        //********************************当前数据需要生成子任务****************根据配置生成子任务************************//
        if (((isset($currentTaskParam["taskPro"]['isGenChildTask']) && $currentTaskParam["taskPro"]['isGenChildTask'] == true))) {
            if ((!empty($currentTaskParam["taskGenConf"]) && $currentTaskParam["taskGenConf"])) {
                //当前任务需要派生任务
                if ($allcount > 0) {
                    $remarks = "任务:[" . $taskinfo->id . "]###的子任务";
                    $genTaskCfgs = $currentTaskParam["taskGenConf"];
                    $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . "处理通用任务:[remotecommtask_cache]--+-数据缓存--+-准备为当前任务taskid:" . $taskinfo->id . "]生成子任务，当前任务共有:[" . $allcount . "]条数据,配置参数:[" . var_export($genTaskCfgs, true) . "].");

                    //派生子任务的数据结构
                    // "taskGenConf": [
                    //    {
                    //      "dataPath": "datas[].user",
                    //      "splitStep": 1,
                    //      "childTaskUrl": "datas[].user.page_url",
                    //      "childTaskDefId": "0002",
                    //      //需要传递给子任务的数据，根据参数名获取
                    //      "params": [
                    //          {
                    //            "paramName": "uiserId",
                    //             "dataType": "int",
                    //             "paraType": "cons|vari", //变量或者常量
                    //              //变量来源:{1:全局变量(父任务设置的全局变量一般值的是内置变量) 2:全局常量(父任务中配置的全局常量) 3:当前任务的一次抓取记录}
                    //               "paramSource": 2,
                    //                "value": "${datas[].user.id}|000001" //变量表达式写法|常量写法
                    //          }
                    //       ]
                    //  }
                    //]

                    //*****************************测试代码**************************//
//                                if($taskinfo->id==2){
//                                    $resultDatas = array_slice($resultDatas,0,1,true );
//                                }else{
//                                    $resultDatas = array_slice($resultDatas,0,1,true );
//                                }
                    if (empty($genTaskCfgs)) {
                        $logger->error(__FILE__ . " " . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务异常:['taskGenConf null']!");
                        setErrorMsg(1, "处理通用任务:[remotecommtask_cache]--+-数据缓存--+-generate task exception:['taskGenConf null']");
                    }

                    if (!is_array($genTaskCfgs)) {
                        $logger->error(__FILE__ . " " . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务异常:['genTaskCfgs' must be array']!");
                        setErrorMsg(1, "处理通用任务:[remotecommtask_cache]--+-数据缓存--+-generate task exception:['genTaskCfgs must be array']");
                    }

                    $logger->debug(__FILE__ . " " . __LINE__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务--+-用于生成子任务的数据条数:[" . count($resultDatas) . "]!");

                    //根据配置生成N种子任务
                    foreach ($genTaskCfgs as $genIdx => $genTaskConfig) {
                        //一个父任务可以生成多个不同的子任务 每个子任务
                        //默认每条记录生成一个子任务
                        $taskNumPerChild = empty($genTaskConfig["splitStep"]) ? 1 : $genTaskConfig["splitStep"];
                        $logger->debug(__FILE__ . " " . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务--+-根据第:[{$genIdx}]个子任务生成配置生成子任务. 每:[" . $taskNumPerChild . "] 条数据生成一条子任务...");

                        //子任务参数定义Id
                        $childParamId = $genTaskConfig["childTaskDefId"];
                        if (empty($childParamId)) {
                            $logger->error(__FILE__ . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务[第{$genIdx}个子任务]异常:['childTaskDefId null']" . var_export($genTaskConfig, true));
                            setErrorMsg(1, "处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务异常:['childTaskDefId null']");
                        }

                        $childTaskParam = $taskParams[$childParamId];
                        if (empty($childTaskParam)) {
                            $logger->error(__FILE__ . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务[第{$genIdx}个子任务]异常:['childTaskDef null'] for childTaskDefId:[" . $childParamId . "] AllConfig:[" . var_export($genTaskCfg, true));
                            setErrorMsg(1, "处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务[第{$genIdx}个子任务]异常:['childTaskDef null']");
                        }

                        $addResult = chunkDeriveTask4Common($resultDatas, $taskParams, $currentTaskParam, $childParamId, $childTaskParam, $taskNumPerChild, $genIdx, $remarks);
                        if ($addResult) {
                            $logger->debug(__FILE__ . __LINE__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务[第{$genIdx}个子任务]成功! 任务taskid:" . $taskinfo->id . "]!");
                        } else {
                            throw new Exception("处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务[第{$genIdx}个子任务]异常,taskId:[" . $taskinfo->id . "].");
                        }
                    }
                } else {
                    $logger->info(__FILE__ . __LINE__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务--+-不需要为当前任务taskid:" . $taskinfo->id . "]派生子任务，当前任务共有:[" . $allcount . "]条数据.");
                }
            } else {
                $logger->info(__FILE__ . __LINE__ . " " . "处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务--+-子任务生成配置:[taskGenConf] null!,不生成子任务!");
            }
        } else {
            $logger->info(__FILE__ . __LINE__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-生成子任务--+-不需要生成子任务. 当前任务taskid:[" . $taskinfo->id . "],属性[taskPro.isGenChildTask]为false,isGenChildTask:[" . var_export($currentTaskParam["taskPro"], true) . "].");
        }
        $logger->info(__FILE__ . __LINE__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存处理成功! 为当前任务taskid:[" . $taskinfo->id . "]");
        //最后跟新该参数
    } catch (Exception $e) {
        $logger->error(__FILE__ . __LINE__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存异常:[" . $e->getMessage() . "].");
        $result['result'] = false;
        $result['msg'] = " 缓存数据失败!" . $e->getMessage();
    }
    if ($result['result'] == false) {
        $result['errorcode'] = -1;
        $result['error'] = $result['msg'];
        $logger->error($result['msg']);
        unset($result['result']);
        unset($result['msg']);
    }
    echo json_encode($result);
    exit;
} else { //植入微博
    $logger->info("-----   进入测试类  --------------------");
    $requsePostData = $HTTP_RAW_POST_DATA;
    $logger->info("the post data is" . var_export($requsePostData, true));

    $da = json_decode($requsePostData);

    $logger->info("the post data is" . var_dump($da, true));
    $logger->info("-----   结束 --------------------");
    die;


    if ($chkr != CHECKSESSION_SUCCESS) {
        $arrs["result"] = false;
        $arrs["msg"] = "未登录或登陆超时!";
        echo json_encode($arrs);
        exit;
    }
    //为全局字典方案变量赋值
    global $dictionaryPlan;
    $dictionary_plan = $_POST['dictionary_plan'];
    $dictionaryPlan = $dictionary_plan;
    $logger->info("获取字典：" . $dictionaryPlan);
    $isfile = isset($_POST['isfile']) ? $_POST['isfile'] : NULL;
    if (isset($_POST['source']))
        $source = $_POST['source'];
    else if (isset($_POST['page_url']))
        $source = get_sourceid_from_url($_POST['page_url']);
    $weiboid = isset($_POST['weiboid']) ? $_POST['weiboid'] : NULL;

    $weiboidtype = isset($_POST['weiboidtype']) ? $_POST['weiboidtype'] : NULL;
    $isseed = empty($_POST['isseed']) ? false : true;//是否种子微博
    if (empty($isfile)) {
        if (empty($source) || empty($weiboid) || !isset($weiboidtype)) {
            $result["result"] = false;
            $result["msg"] = "参数错误";
            echo json_encode($result);
        } else {
            $commitinterval = defined('ADMIN_IMPORTCOMMIT_INTERVAL') ? ADMIN_IMPORTCOMMIT_INTERVAL : 100;//多少条commit一次
            $currindex = empty($_POST['currindex']) ? 1 : $_POST['currindex'];
            $pallcount = isset($_POST['allcount']) ? $_POST['allcount'] : 0;
            if (($currindex % $commitinterval == 0) || $currindex >= $pallcount) {
                $task->taskparams->iscommit = true;
            } else {
                $task->taskparams->iscommit = false;
            }
            $_r = getweibo($source, $weiboidtype, $weiboid, $isseed);//抓取微博
            $result = $_r;
            if ($_r['result']) {
                if (isset($_r['weibo'])) {//抓取成功
                    $weibos = array();
                    $weibos[] = $_r['weibo'];
                    $_r = addweibo($source, $weibos, $isseed);//将微博批量新增到solr
                    $result = $_r;
                    if ($_r['result'] && $taskadd) {//入库成功
                        $result = addRepostTask($source, $weibos[0]['id'], false, $reposttask->conflictdelay, $reposttask->local, $reposttask->remote, $reposttask);
                    }
                } else if (isset($_r['weiboid'])) {//数据库已存在
                    if ($taskadd) {
                        $result = addRepostTask($source, $_r['weiboid'], false, $reposttask->conflictdelay, $reposttask->local, $reposttask->remote, $reposttask);
                    }
                }
            } else {
                if (isset($result['error_code']) && $result['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                    $result['notext'] = 1;//微博不存在
                }
            }
            //本次没有资源；当前索引大于1说明上一次提交过数据；上一次的未执行commit；
            if ($result['result'] == false && !empty($result['nores']) && $currindex > 1 && (($currindex - 1) % $commitinterval != 0)) {
                $updatecache_r = handle_solr_data(array(), SOLR_URL_INSERT . "&commit=true");
                if ($updatecache_r !== NULL) {
                    $logger->warn(SELF . " 执行commit失败");
                }
            }
            $logger->debug(__LINE__ . "result: " . var_export($result, true));
            echo json_encode($result);
        }
    } else {//处理excel
        $error = "";
        $taskurls = array();//需要加入任务的微博
        $fileElementName = 'filetoupload';
        if (!empty($_FILES[$fileElementName]['error'])) {
            switch ($_FILES[$fileElementName]['error']) {

                case '1':
                    $error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                    break;
                case '2':
                    $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                    break;
                case '3':
                    $error = 'The uploaded file was only partially uploaded';
                    break;
                case '4':
                    $error = 'No file was uploaded.';
                    break;

                case '6':
                    $error = 'Missing a temporary folder';
                    break;
                case '7':
                    $error = 'Failed to write file to disk';
                    break;
                case '8':
                    $error = 'File upload stopped by extension';
                    break;
                case '999':
                default:
                    $error = 'No error code avaiable';
            }
            $result['result'] = false;
            $result["msg"] = $error;
        } else if (empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none') {
            $result['result'] = false;
            $result["msg"] = 'No file was uploaded..';
        } else {
            $realfile = $_FILES[$fileElementName]['tmp_name'];
            $objPHPExcel = PHPExcel_IOFactory::load($realfile);
            $weibos = array();
            $existsids = array();
            $errorurls = array();
            $nores = false;
            $allcount = 0;
            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {     //遍历工作表
                foreach ($worksheet->getRowIterator() as $row) {       //遍历行
                    $cellIterator = $row->getCellIterator();   //得到所有列
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {  //遍历列
                        if (!is_null($cell)) {  //如果列不给空就得到它的坐标和计算的值
                            $weibourl = $cell->getValue();
                            $allcount++;
                            if (!$nores) {
                                $_r = getweibo($source, $weiboidtype, $weibourl, $isseed);//抓取微博
                                if ($_r['result']) {
                                    if (isset($_r['weibo'])) {
                                        $weibos[] = $_r['weibo'];
                                    } else if (isset($_r['weiboid'])) {//数据库已存在
                                        $existsids[] = $_r['weiboid'];
                                    }
                                } else {//未抓取到微博
                                    $result['result'] = false;
                                    if (!empty($_r['nores'])) {
                                        $result['nores'] = true;
                                        $nores = true;
                                    }
                                    $result['msg'] = "抓取微博{$weibourl}时失败：" . $_r['msg'] . "。";
                                }
                            } else {
                                $taskurls[] = $weibourl;
                            }
                            break;
                        }
                    }
                }
            }
            //for security reason, we force to remove all uploaded file
            @unlink($_FILES[$fileElementName]);
            $reposttask_ids = array();
            //处理已经抓取的微博
            if (!empty($weibos)) {
                $_r = addweibo($source, $weibos, $isseed);//入库
                if ($_r['result'] == false) {
                    //入库失败且之前抓取微博时也有错误发生， 将msg拼接
                    if ($result['result'] == false) {
                        $result['msg'] .= "新增微博时失败：" . $_r['msg'];
                    } else {
                        $result = $_r;
                    }
                } else {
                    //入库成功，且需要增加任务
                    if ($taskadd) {
                        foreach ($weibos as $k => $v) {
                            if (!isset($v['retweeted_status'])) {
                                $reposttask_ids[] = $v['id'];
                            }
                        }
                    }
                }
            }

            if (!empty($weiboids) && $taskadd) {
                foreach ($weiboids as $k => $v) {
                    $reposttask_ids[] = $v;
                }
            }
            if (!empty($reposttask_ids)) {
                $_r = addRepostTask($source, $reposttask_ids, false, $reposttask->conflictdelay, $reposttask->local, $reposttask->remote, $reposttask);
                if ($_r['result'] == false) {//增加转发任务失败
                    if ($result['result'] == false) {
                        $result['msg'] .= $_r['msg'];
                        $logger->error(SELF . " 新增转发任务时失败：" . $_r['msg']);
                    } else {
                        $result['result'] = $_r['result'];
                        $result['msg'] = "添加微博成功，" . $_r['msg'];
                    }
                }
            }
            if (!empty($taskurls)) {//增加植入任务
                $remarks = "来自系统管理的请求（导入excel），共{$allcount}条请求，已抓取" . count($weibos);
                $remarks .= "条，数据库已存在" . count($existsids) . "条。剩余" . count($taskurls) . "条。";
                $t_r = addImportTask($source, $taskurls, $weiboidtype, $remarks, $isseed);
                if ($result['result'] == false) {
                    $result['msg'] .= $t_r['msg'];
                } else {
                    $result['result'] = $t_r['result'];
                    $result['msg'] = $t_r['msg'];
                }
                if (!$t_r['result']) {
                    $logger->error(SELF . " " . $t_r['msg']);
                } else {
                    $logger->info(SELF . " " . $t_r['msg']);
                }
            }
        }
        echo json_encode($result);
    }
}

/**
 * @param $postdata :当前任务抓取的结果数据集，用于生成子任务；每条记录生成一个子任务
 * @param $childTaskParam ：子任务参数配置
 * @param int $taskNumPerChild :每个子任务中的任务数(即结果集中的多少条结果产生一个子任务)
 * @param $remarks
 * @return mixed
 */
function chunkDeriveTask4Common($resultDatas, $taskParams, $currentTaskParam, $childParamId, $childTaskParam, $taskNumPerChild = 50, $genIdx, $remarks)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . " chunkDeriveTask4Common ... childParamId:[" . var_export($childParamId, true) . "] taskNumPerChild:[{$taskNumPerChild}].");

    if ($taskNumPerChild <= 0) {
        $logger->error(__FILE__ . __LINE__ . " generate task exception:['dataPath null'].");
        throw new Exception("Generator child task execption,param:[taskNumPerChild] must more than 0![" . $taskNumPerChild . "].");
    }

    if (empty($resultDatas)) {
        $logger->error(__FILE__ . __LINE__ . " generate task exception, resultDatas null.");
        throw new Exception("generate task exception, resultDatas null.");
    }

    if (empty($childParamId) || empty($childTaskParam)) {
        $logger->error(__FILE__ . __LINE__ . " generate task exception, childParamId or childParam null!");
        throw new Exception("generate task exception, childParamId or childParam null!");
    }
    unset($taskParams["root"]);
    unset($taskParams[$childParamId]);
    $taskParams["root"] = &$childTaskParam;
    $logger->debug("reset taskParam for childTask success. AllTaskParam:[" . var_export($taskParams, true) . "].");
//    //获取子任务生成规则
    $genTaskCfg = $currentTaskParam["taskGenConf"][$genIdx];

    if (isset($genTaskCfg["params"]) && !empty($genTaskCfg["params"])) {
        $logger->debug("transmit parentTaskParams to child task ...");

        $logger->debug("--+--------------------处理非grabDatas[] --> chiledParam 中的数据映射.....");

        //将父任务上的参数传递到子任务中(根据配置)
        foreach ($genTaskCfg["params"] as $paramSetCfg) {
            //父任务定义中的取值路径定义
            $currAllParaMap = $currentTaskParam["pathStructMap"];

            $fromPath = $paramSetCfg["paramPath"];
            $toParaPath = $paramSetCfg["toParamPath"];

            if (empty($fromPath) || empty($toParaPath)) {
                throw new Exception("transmit parentTaskParams to child task exception,fromPath or toParaPath null. fromPath:[" . $fromPath . "] toParaPath:[" . $toParaPath . "].");
            } else {
                $fromPath = substr($fromPath, 1, strlen($fromPath) - 2);
                $toParaPath = substr($toParaPath, 1, strlen($toParaPath) - 2);
            }

            if ($currAllParaMap[$fromPath]["paramSource"] == 5) {
                //在这里先不设置来自父任务抓取结果中的参数，到下面进行设置(因为首先需要根据配置将N条抓取结果最为一个小的数据集
                //来产生子任务(该数据集可以用于生成子任务URL时，替换Enum、obj、Num等规则定义)，参数N不同，则用于生成子任务的
                //数据就会不同，可能会直接影响Enum、Num等变量的取值个数，所以在这里不进行设置该类参数传递
                continue;
            }
            $logger->debug("transmit parentTaskParams to child task,fromPath:[" . $fromPath . "] toParaPath:[" . $toParaPath . "].");
            if (!isset($currAllParaMap[$fromPath])) {
                $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . " genChildTask exception[paramMapping exception],PathDef for from path:[{$fromPath}] is null. All pathDefMap: " . var_export($currAllParaMap, true));
            }

            if (!isset($currAllParaMap[$toParaPath])) {
                $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . " genChildTask exception[paramMapping exception],PathDef for to path:[{$toParaPath}] is null. All pathDefMap: " . var_export($currAllParaMap, true));
            }
            $souceValues = &getSourceObj($currentTaskParam, $currAllParaMap[$fromPath]);
            $paramValue = getValueFromObjWrap($souceValues, $currAllParaMap[$fromPath]);
            $targetValues = &getSourceObj($childTaskParam, $currAllParaMap[$toParaPath]);
            setValue4ObjWrap($targetValues, $currAllParaMap[$toParaPath], $paramValue);
        }
    } else {
        $logger->debug("no need to transmit parentTaskParams to child task! Param4Child:[" . var_export($genTaskCfg, true) . "].");
    }
    //$logger->debug("transmit parentTaskParams to child task success,all child Param: " . var_export($childTaskParam, true));
    $logger->debug("--+--------------------处理非grabDatas[] --> chiledParam 中的数据映射完成, child Param:" . var_export($childTaskParam, true));


    $allcount = count($resultDatas);
    $succcount = 0;
    //拆分成不同的小集合，每个小数据集将生成一个任务
    $eachdatas = array_chunk($resultDatas, $taskNumPerChild);

    foreach ($eachdatas as $ei => $eitem) {
        $curinx = $ei * $taskNumPerChild + 1;
        $logger->debug(__FILE__ . __LINE__ . " 使用第:[" . $curinx . "]-第:[" . ($curinx + count($eitem) - 1) . "]条抓取数据生成子任务...");
        $tmpr = " ###Index:[" . ($ei + 1) . "] ###处理第[" . $curinx . "]条-第[" . ($curinx + count($eitem) - 1) . "]条###";
        if (addDeriveTask4Common($eitem, $taskParams, $currentTaskParam, $genTaskCfg, $genIdx, $remarks . $tmpr)) {
            $logger->debug(__FILE__ . __LINE__ . " 成功 使用第:[" . $curinx . "]-第:[" . ($curinx + count($eitem) - 1) . "]条抓取数据生成子任务，新增任务数:[" . count($eitem) . "].");
            $succcount += count($eitem);
        } else {
            $logger->debug(__FILE__ . __LINE__ . " 失败! 使用第:[" . $curinx . "]-第:[" . ($curinx + count($eitem) - 1) . "]条抓取数据生成子任务!");
        }
    }
    $logger->debug("生成子任务成功！");
    if ($succcount != $allcount) {
        throw new Exception("添加植入任务失败! successNum:[" . $succcount . "] AllAcountNum:[" . $allcount . "]!");
    }
    return true;
}

/**
 * 用于将抓取结果通过拆分后的小数据集 生成一个子任务
 * @param $eachpostdata ：本次抓取结果按照配置参数 “splitStep”拆分后的小数据集
 * @param $taskparams :父任务参数(所有)
 * @param $parentTaskParams :
 * @param $genTaskCfg
 * @param $remarks
 * @return bool
 * @throws Exception
 */
function addDeriveTask4Common($eachpostdata, $taskparams, $parentTaskParams, $genTaskCfg, $genIdx, $remarks)
{
    global $logger;
//    $urlrule = $taskparams["taskUrls"];
    //解析url得到取哪个字段的值
    //"http://$<engine \"%s\">/$<userid \"%s\">/$<path \"%s\">{engine:Enum(s) userid:Enum(%E6%98%AF) path:Enum(谁的)}"
    $logger->debug(__FILE__ . __LINE__ . " 使用抓取数据[子集]生成子任务...数据子集类型:" . (gettype($eachpostdata)) . "] data:[" . var_export($eachpostdata, true) . "].");

    //通过拆分后的每个 $eachpostdata 将为一个数组(即使里面只有一条抓取记录) 在这里进行判断，后文将根据 $taskNumPerChild 来判断 每个 $eachpostdata中包含几条抓取数据
    if (count($eachpostdata) == 1) {
        //将list 转化为 map
        $eachpostdata = $eachpostdata[0];
        $taskNumPerChild = 1;
    } else {
        $taskNumPerChild = count($eachpostdata);
    }

    //
    try {
        $logger->debug(__FILE__ . __LINE__ . " 使用抓取数据[子集]生成子任务...数据子集类型:" . (gettype($eachpostdata)) . "] data:[" . var_export($eachpostdata, true) . "].");
        if (isset($genTaskCfg["params"]) && !empty($genTaskCfg["params"])) {
            $logger->debug("transmit params to child task ...");

            $logger->debug("--+--------------------处理grabDatas[] --> chiledParam 中的数据映射.....");

            //将父任务上的参数传递到子任务中(根据配置)
            foreach ($genTaskCfg["params"] as $paramSetCfg) {
                $paramAllParaMap = $parentTaskParams["pathStructMap"];
                $fromPath = $paramSetCfg["paramPath"];
                $toParaPath = $paramSetCfg["toParamPath"];

                if (empty($fromPath) || empty($toParaPath)) {
                    throw new Exception("transmit parentTaskParams to child task exception,fromPath or toParaPath null. fromPath:[" . $fromPath . "] toParaPath:[" . $toParaPath . "].");
                } else {
                    $fromPath = substr($fromPath, 1, strlen($fromPath) - 2);
                    $toParaPath = substr($toParaPath, 1, strlen($toParaPath) - 2);
                }

                $logger->debug("transmit grabDatas to child task,fromPath:[" . $fromPath . "] toParaPath:[" . $toParaPath . "].");

                if ($paramAllParaMap[$fromPath]["paramSource"] != 5) {
                    //在这里先不设置来自父任务抓取结果中的参数，到下面进行设置(因为每条抓取结果产生的ChildTaskParam都不一样)
                    $logger->debug("--+-源数据路径是指向了非GrabDatas中的数据，已经处理过了,先跳过...");
                    continue;
                }

//            $souceValues =  getSourceObj($parentTaskParams,$paramAllParaMap[$fromPath]);
                if ($taskNumPerChild > 1) {
                    //获取抓取数据[子集]中的每条数据
                    $paramValue = array();
                    foreach ($eachpostdata as $grabData) {
                        $paramValue[] = getValueFromObjWrap($grabData, $paramAllParaMap[$fromPath]);
                    }
                } else {
                    $paramValue = getValueFromObjWrap($eachpostdata, $paramAllParaMap[$fromPath]);
                }

                $logger->debug("transmit grabDatas to child task,get fromPath:[" . $fromPath . "] value ok:[" . var_export($paramValue, true) . "].");

                if (!isset($paramSetCfg["targetTyp"]) && !isset($paramSetCfg["targetType"])) {
                    $logger->error("createNewTaskByTaskParam excption,task param transfer config invalid,the proporty:[targetTyp] must be setted! config:[" . var_export($paramSetCfg, true) . "].");
                    throw new Exception("createNewTaskByTaskParam excption,task param transfer config invalid,the proporty:[targetTyp] must be setted! config:[" . var_export($paramSetCfg, true) . "].");
                }

                if ((isset($paramSetCfg["targetTyp"]) && $paramSetCfg["targetTyp"] == 0) || (isset($paramSetCfg["targetType"]) && $paramSetCfg["targetType"] == 0)) {
                    $targetParam = $taskparams["root"];//将参数设置到子任务参数中
                } else if ((isset($paramSetCfg["targetTyp"]) && $paramSetCfg["targetTyp"] == 1) || (isset($paramSetCfg["targetType"]) && $paramSetCfg["targetType"] == 1)) {
                    //将从抓取结果中提取到的数据设置到父参数中，一般用于替换子任务的URL中的变量
                    $targetParam =  &$parentTaskParams;
                } else {
                    throw new Exception("createNewTaskByTaskParam excption,task param transfer config invalid,invalid value of proporty:[targetTyp], Config:[" . var_export($paramSetCfg, true) . "].");
                }
                $targetValuesKey = getSourceObjKey($targetParam, $paramAllParaMap[$toParaPath]);
                $logger->debug("set new value:[" . $paramValue . "] to targetPath:[" . var_export($paramAllParaMap[$toParaPath], true) . "].");

                setValue4ObjWrap($targetParam[$targetValuesKey], $paramAllParaMap[$toParaPath], $paramValue);
                //处理一个参数成功!
                $logger->debug("--+--------------------处理grabDatas[] --> chiledParam 中的数据映射完成!,fromPath:[" . $fromPath . "] toParaPath:[" . $toParaPath . "] ok! targetParam:[" . var_export($targetParam, true) . "].");
            }
            $logger->debug("handle params to child task param success! parentTaskParams:[" . var_export($parentTaskParams, true) . "].");
        } else {
            $logger->debug("no need to handle params to child task param! Param4Child:[" . var_export($genTaskCfg, true) . "].");
        }

        $imtask = createNewTaskByTaskParam($eachpostdata, $taskparams, $parentTaskParams, $genIdx, $taskNumPerChild, $remarks);
        $res = addTask($imtask, true);
        $logger->debug(__FILE__ . __LINE__ . " 使用抓取数据生成子任务成功,添加成功! addResult:[" . var_export($res, true) . "].");

        //爬虫子任务入库到定时任务里面，进行持续抓取（针对价格趋势） 2017/3/14  by yu
        if (isset($parentTaskParams['taskPro']['creattask']) & !empty($parentTaskParams['taskPro']['creattask'])) {

            if (!empty($taskparams["root"]['taskPro']['crontime'])) {
                $crontime = $taskparams["root"]['taskPro']['crontime'];
                $crontime11 = json_decode($crontime);
                $crontime11->precision = 60;
                $crontime11->cronmask = getCronMask($crontime11);
                $crontime = json_encode($crontime11);

            } else {
                $crontime1 = $parentTaskParams['taskPro']['crontime'];
                $crontime22 = json_decode($crontime1);
                $crontime22->precision = 60;
                $crontime22->cronmask = getCronMask($crontime22);
                $crontime = json_encode($crontime22);
            }

            $res1 = addCrawlerTaskSchedule($imtask, true, $crontime);
            $logger->info(__FUNCTION__ . __LINE__ . "the res1 is:" . var_export($res1, true));
        }

        return $res;
    } catch (Exception $e) {
        $logger->error("createNewTaskByTaskParam excption:[" . $e->getMessage() . "].");
        throw $e;
    }
}

/**
 * 根据任务配置参数，产生一个任务实体
 */
function createNewTaskByTaskParam(&$eachpostdata, $taskparams, $parentTaskParams, $genIdx, $taskNumPerChild, $remarks)
{
    global $logger, $dsql;
    $logger->debug(__FILE__ . __LINE__ . " 通过任务参数创建新的子任务实例...");
    $logger->debug(__FILE__ . __LINE__ . " in createNewTaskByTaskParam the taskparam is:" . var_export($taskparams, true));
    $column = $taskparams['root']['parentParam']['column'];
    $column1 = $taskparams['root']['parentParam']['column1'];
    $logger->info("the column1 is:" . var_export($column1, true));
    $logger->info("the column is:" . var_export($column, true));

    $imtask = new Task(null);
    $imtask->tasktype = TASKTYPE_SPIDER;//爬虫抓取任务
    $imtask->task = TASK_COMMON;//通用任务类型
    $imtask->remarks = empty($remarks) ? "" : $remarks;

    $taskParamPro = &$taskparams["root"]["taskPro"];
    $imtask->tasklevel = empty($taskParamPro['tasklevel']) ? 1 : $taskParamPro['tasklevel'];
    $imtask->local = empty($taskParamPro['local']) ? 0 : $taskParamPro['local'];
    $imtask->remote = empty($taskParamPro['remote']) ? 1 : $taskParamPro['remote'];
    $imtask->activatetime = empty($taskParamPro['activatetime']) ? 0 : $taskParamPro['activatetime'];
    $imtask->conflictdelay = empty($taskParamPro['conflictdelay']) ? 60 : $taskParamPro['conflictdelay'];

    //add by  yu  将父任务的参数中的column，column1提取出来，存到mysql里面
    if (isset($taskparams['root']['parentParam']['column']) && isset($taskparams['root']['parentParam']['column1'])) {
        $column = $taskparams['root']['parentParam']['column'];
        $column1 = $taskparams['root']['parentParam']['column1'];
        $logger->info("the column is:" . var_export($column, true));
        $logger->info("the column1 is:" . var_export($column1, true));
        $imtask->column = $column;
        $imtask->column1 = $column1;
    }


    //add by wangcc 将对该任务(父任务)指定的 固定mac 以及 任务分类 传递到子任务中去
    if (isset($taskParamPro['specifiedType']) && !empty($taskParamPro['specifiedType'])) {
        //当前任务设定了 任务分类 则忽略父任务设置的该参数
        $imtask->taskclassify = $taskParamPro['specifiedType'];
        $logger->debug(__FILE__ . __LINE__ . " 使用该子任务指定的specifiedType:[" . $taskParamPro['specifiedType'] . "].");
    } else {
        //当前任务 没有设置:任务分类 查看父任务是否设置了 并且 看是否传递到子任务
        if (isset($parentTaskParams['taskPro']['specifiedType']) && !empty($parentTaskParams['taskPro']['specifiedType']) && $parentTaskParams['taskPro']['specifiedTypeForChild']) {
            $imtask->taskclassify = $parentTaskParams['taskPro']['specifiedType'];
            $taskParamPro['specifiedType'] = $parentTaskParams['taskPro']['specifiedType'];
            $logger->debug(__FILE__ . __LINE__ . " 使用父任务指定的specifiedType:[" . $parentTaskParams['taskPro']['specifiedType'] . "].");
        }
    }

    if (isset($taskParamPro['specifiedMac']) && !empty($taskParamPro['specifiedMac'])) {
        //当前任务设定了 指定特定主机 则忽略父任务设置的该参数
        $imtask->spcfdmac = $taskParamPro['specifiedMac'];
        $logger->debug(__FILE__ . __LINE__ . " 使用该子任务指定的 specifiedMac:[" . $taskParamPro['specifiedMac'] . "].");
    } else {
        //当前任务 没有设置:指定特定主机 查看父任务是否设置了 并且 看是否传递到子任务
        if (isset($parentTaskParams['taskPro']['specifiedMac']) && !empty($parentTaskParams['taskPro']['specifiedMac']) && $parentTaskParams['taskPro']['specifiedMacForChild']) {
            $imtask->spcfdmac = $parentTaskParams['taskPro']['specifiedMac'];
            $taskParamPro['specifiedMac'] = $parentTaskParams['taskPro']['specifiedMac'];
            $logger->debug(__FILE__ . __LINE__ . " 使用父任务指定的specifiedMac:[" . $parentTaskParams['taskPro']['specifiedMac'] . "].");
        }
    }

//    if (!empty($taskParamPro['submiturl']) && $taskParamPro['submiturl'] != "0") {
//      //当前任务设定了 提交地址 则忽略父任务设置的该参数
//        $category1 = "select * from data_import_category where id =". $taskParamPro["submiturl"];
//        $qc1 = $dsql->ExecQuery($category1);
//        if (!$qc1) {
//            $logger->error(TASKMANAGER . " " . __FUNCTION__ . " qc1error:" . $qc1 . " - " . $dsql->GetError());
//        } else {
//            while ($rc1 = $dsql->GetArray($qc1)) {
//                $industryid1 = $rc1["industry_id"];
//                $interfacename1 = $rc1["interface_name"];
//            }
//        }
//        $importin1 = "select * from data_import_industry where id =" . $industryid1;
//        $qi1 = $dsql->ExecQuery($importin1);
//        if (!$qi1) {
//            $logger->error(TASKMANAGER . " " . __FUNCTION__ . " qi1error:" . $qi1 . " - " . $dsql->GetError());
//        } else {
//            while ($ri1 = $dsql->GetArray($qi1)) {
//                $importserver1 = $ri1["import_server"];
//                $port1 = $ri1["port"];
//            }
//        }
//        $taskParamPro["contenturl"] = "http://".$importserver1 .":".$port1.$interfacename1;
//        $imtask->contenturl = $taskParamPro['contenturl'];
//        $logger->debug(__FILE__ . __LINE__ . " 使用该子任务指定的contenturl:[" . $taskParamPro['contenturl'] . "].");
//    } else {
//        //当前任务 没有设置:提交地址 查看父任务是否设置了 并且 看是否传递到子任务
//        if (isset($parentTaskParams['taskPro']['contenturl']) && !empty($parentTaskParams['taskPro']['contenturl']) && $parentTaskParams['taskPro']['submiturlForChild']) {
//            $imtask->contenturl = $parentTaskParams['taskPro']['contenturl'];
//            $taskParamPro['contenturl'] = $parentTaskParams['taskPro']['contenturl'];
//            $logger->debug(__FILE__ . __LINE__ . " 使用父任务指定的contenturl:[" . $parentTaskParams['taskPro']['contenturl'] . "].");
//        }
//    }

    $genUrlConfig = $parentTaskParams["taskGenConf"][$genIdx]["childTaskUrl"];
    if (empty($genUrlConfig)) {
        $logger->error(__FILE__ . __LINE__ . " generate task exception:['childTaskUrl null'] parentTaskParams:[" . var_export($parentTaskParams . true) . "].");
        throw new Exception("generate task exception:['childTaskUrl null'].");
    }

    //这里不需要替换URL中的参数,因为这里有可能有的参数获取不到，例如运行时候的参数，统一在
    //taskAgent.php里面爬虫获取任务的时候，进行统一替换
    //TODO 在这里需要将 使用到所有的
    if ($genUrlConfig["type"] == "gen") {
        $logger->debug("gen child task url type:[gen]...");
        $templ = $genUrlConfig["templ"];
        if (!empty($taskparams["root"]["taskUrls"])) {
            $taskparams["root"]["taskUrls"] = array();
//            $logger->error(__FILE__ . __LINE__ . " generate task exception:['childTaskParam[taskUrls] not null'].");
//            throw new Exception("generate task exception:['childTaskParam[taskUrls] not null'].");
        }
        if (empty($templ)) {
            $logger->error(__FILE__ . __LINE__ . " generate task exception:['childTaskParam[templ] is null'].");
            throw new Exception("generate task exception:['childTaskParam[templ] is null'].");
        }
        $taskparams["root"]["taskUrls"]["type"] = "gen";
        $taskparams["root"]["taskUrls"]["urlValues"][0] = $templ;

        $logger->debug(__FILE__ . __LINE__ . " 生成子任务的URL:[" . var_export($parentTaskParams, true) . "].");

        //$childTasdUrls = genChildUrlFromParentTaskParam($parentTaskParams, $eachpostdata);
        //生成URL成功，将gen修改为常量
        //$taskparams["root"]["taskUrls"]["type"] = "consts";
//        $taskparams["taskUrls"]["urlValues"][0] =$templ;
        //unset($taskparams["root"]["taskUrls"]["urlValues"]);
        //$taskparams["root"]["taskUrls"]["urlValues"][0] = $childTasdUrls;

        $logger->debug("reset child task paramURL success:[" . var_export($taskparams["root"]["taskUrls"]["urlValues"], true) . "].");
    } else if ($genUrlConfig["type"] == "consts") {
        $logger->debug(__FILE__ . __LINE__ . " gen child task url type:[consts]...");
        if (!empty($taskparams["root"]["taskUrls"])) {
            $taskparams["root"]["taskUrls"] = array();
//            $logger->error(__FILE__ . __LINE__ . " generate task exception:['childTaskParam[taskUrls] not null'].");
//            throw new Exception("generate task exception:['childTaskParam[taskUrls] not null'].");
        }
        //根据$eachpostdata生成子任务的URL
        $childTasdUrls = array();

        $paramValues = $genUrlConfig["value"]["paramValue"];
        $paramSource = $genUrlConfig["value"]["paramSource"];

        $logger->debug("gen child task url type:[consts] paramValues" . $paramValues . "] paramSource:[{$paramSource}].");

        if ($paramSource == 5) {
            $logger->debug("gen child task url type:[consts] from grabDatas, " . var_export($eachpostdata, true) . "]  taskNumPerChildTask:[{$taskNumPerChild}] ...");

            //从当前抓取数据中提取参数
            if (!empty($paramValues) && strpos($paramValues, '|') === 0) {// 以|开始的为变量名
                $paramValues = substr($paramValues, 1, strlen($paramValues) - 2);
                $paramPathStruct = $parentTaskParams["pathStructMap"][$paramValues];
                $logger->debug("根据变量路径[" . $paramValues . "]从抓取结果中提取子任务URL...");

                if ($taskNumPerChild == 1) {
                    //从抓取结果数据中获取参数的值(按照生成子任务配置，可以N条抓取结果生成一个子任务，类似Url中Enum这样的参数时候，需要将多条
                    //抓取结果中的值最为一条获取结果，即以数组的形式返回)
                    $dataUrl = getValueFromObjWrap($eachpostdata, $paramPathStruct);
                    $logger->debug("根据变量路径从抓取结果中提取一条URL成功:[" . var_export($dataUrl, true) . "].");
                    $childTasdUrls[] = $dataUrl;
                } else {
                    foreach ($eachpostdata as $grabData) {
                        //从抓取结果数据中获取参数的值(按照生成子任务配置，可以N条抓取结果生成一个子任务，类似Url中Enum这样的参数时候，需要将多条
                        //抓取结果中的值最为一条获取结果，即以数组的形式返回)
                        $dataUrl = getValueFromObjWrap($grabData, $paramPathStruct);
                        $logger->debug("根据变量路径从抓取结果中提取一条URL成功:[" . var_export($dataUrl, true) . "].");
                        $childTasdUrls[] = $dataUrl;
                    }
                }

            } else {
                // TODO 这种分支现在不被支持
                //[ 'www.baidu.com/?aaaa', 'www.baidu.com/?bbb', 'www.baidu.com/?ccc']
                $logger->debug("直接将整条抓取数据作为子任务的URL...paramValues:[" . $paramValues . "].");
                if ($paramValues != "{data}") {
                    $logger->error(__FILE__ . __LINE__ . " generate task exception:['cannot supported paramValue not equels[{data}]'] Value:[" . $paramValues . "].");
                    throw new Exception("generate task exception:['cannot supported paramValue not equels[{data}]'] Value:[" . $paramValues . "].");
                }

                if ($taskNumPerChild == 1) {
                    $logger->debug("从抓取结果中提取一条URL成功:[" . var_export($eachpostdata, true) . "].");
                    $childTasdUrls[] = $eachpostdata;
                } else {
                    foreach ($eachpostdata as $grabData) {
                        $logger->debug("从抓取结果中提取一条URL成功:[" . var_export($grabData, true) . "].");
                        $childTasdUrls[] = $grabData;
                    }
                }

            }
        } else {
            $logger->debug("根据变量路径[" . $paramValues . "]从当前任务参数中提取子任务URL...");

            if (strpos($paramValues, '|') === 0) {
                $paramValues = substr($paramValues, 1, strlen($paramValues) - 2);
            } else {
//                if($parentTaskParams["taskGenConf"]["splitStep"]!=1)
                $logger->error(__FILE__ . __LINE__ . " generate task url by const exception:['paramValues illegal']. paramValues:[" . var_export($paramValues, true) . "].");
                throw new Exception("generate task url by const exception:['paramValues illegal']. paramValues:[" . var_export($paramValues, true) . "].");
            }
            $paramPathStruct = $parentTaskParams["pathStructMap"][$paramValues];

            $logger->debug("paramValues:[" . $paramValues . "] paramPathStruct:[" . var_export($paramPathStruct, true) . "].");

            //从当前任务参数(父任务)中获取相关参数
            $souceValues = getSourceObj($parentTaskParams, $paramPathStruct);
            //
            //从其他地方的参数定义中提取参数
            $dataUrl = getValueFromObjWrap($souceValues, $paramPathStruct);

            if (empty($dataUrl)) {
                $logger->error(__FILE__ . __LINE__ . " generate task url by const exception:['从当前任务参数中提取子任务URL:[null]']. souceValues:[" . var_export($parentTaskParams, true) . "].");
                throw new Exception("generate task url by const exception:['从当前任务参数中提取子任务URL:[null]'].");
            }

            $logger->debug("成功! 根据变量路径[" . $paramValues . "]从当前任务参数中提取子任务URL.URL:[" . $dataUrl . "].");

            $childTasdUrls[] = $dataUrl;
        }

        $urlsStri = implode(",", $childTasdUrls);
        //taskurl$:"$<url "%s">"{url:Enum(http://bbs.pcauto.com.cn/topic-5468205.html)}
        //将多个URL生成上述格式
        //<
        $litTh = "&lt";
        //>
        $gth = "&gt";
        $childUrlTemp = "taskurl$:\"$" . $litTh . "url \"%s\"" . $gth . "\"{url:Enum(" . $urlsStri . ")}";
        //重置URL
        $taskparams["root"]["taskUrls"]["type"] = "consts";
        //$taskparams["root"]["taskUrls"]["urlValues"][0] =$templ;
        unset($taskparams["root"]["taskUrls"]["urlValues"]);
        $taskparams["root"]["taskUrls"]["urlValues"] = $childUrlTemp;
        $logger->debug("reset child task paramURL success:[" . var_export($taskparams["root"]["taskUrls"]["urlValues"], true) . "].");
    }

    if (empty($taskparams["root"]["loginAccounts"]) && !empty($parentTaskParams["loginAccounts"])) {
        $taskparams["root"]["loginAccounts"] = $parentTaskParams["loginAccounts"];
    }

    if (empty($taskparams["root"]["dictionaryPlan"]) && !empty($parentTaskParams["dictionaryPlan"])) {
        $taskparams["root"]["dictionaryPlan"] = $parentTaskParams["dictionaryPlan"];
    }

    $imtask->taskparams = $taskparams;
    $logger->debug("createNewTaskByTaskParam ok! Task:[" . var_export($imtask, true) . "].");
    return $imtask;
//    if (!empty($taskparams['derivetexttask'])) {
//        $imtask->taskpagestyletype = TASK_PAGESTYLE_ARTICLEDETAIL;
//        $imtask->taskparams->SStemplate = $taskparams['SStemplate']; //抓取模版id
//        if (isset($taskparams['lastrplytimestart'])) {
//            $imtask->taskparams->lastrplytimestart = $taskparams['lastrplytimestart'];//过滤器时间
//        }
//        if (isset($taskparams['lastrplytimeend'])) {
//            $imtask->taskparams->lastrplytimeend = $taskparams['lastrplytimeend'];//过滤器时间
//        }
//        $imtask->taskparams->texturls = $urlrule;
//        if (isset($taskparams['usertemplate'])) {
//            $imtask->taskparams->usertemplate = $taskparams['usertemplate'];
//        }

//        if (isset($taskparams['userurls'])) {
//            $imtask->taskparams->userurls = $taskparams['userurls'];
//        }
//        if (isset($taskparams['deriveusertask'])) {
//            $imtask->taskparams->deriveusertask = $taskparams['deriveusertask'];
//        }
//        if (isset($taskparams['importusercount'])) {
//            $imtask->taskparams->importusercount = $taskparams['importusercount'];
//        }
//    } else if (!empty($taskparams['deriveusertask'])) {
//        $imtask->taskpagestyletype = TASK_PAGESTYLE_USERDETAIL;
//        $imtask->taskparams->usertemplate = $taskparams['usertemplate'];
//        $imtask->taskparams->userurls = $urlrule;
//    }
//    if (!empty($taskparams['duration'])) {
//        $imtask->taskparams->duration = $taskparams['duration'];
//    }
//    if (!empty($taskparams['source'])) {
//        $imtask->taskparams->source = $taskparams['source'];
//    }
//    if (!empty($taskparams['accountid'])) {
//        $imtask->taskparams->accountid = $taskparams['accountid'];
//    }
//    if (isset($taskparams['logoutfirst'])) {
//        $imtask->taskparams->logoutfirst = $taskparams['logoutfirst'];
//    }
//    if (isset($taskparams['iscalctrend'])) {
//        $imtask->taskparams->iscalctrend = $taskparams['iscalctrend'];
//    }
//    if (isset($taskparams['isswitch'])) {
//        $imtask->taskparams->isswitch = $taskparams['isswitch'];
//        if ($taskparams['isswitch']) {
//            $imtask->taskparams->switchpage = $taskparams['switchpage'];
//            $imtask->taskparams->switchtime = $taskparams['switchtime'];
//            $imtask->taskparams->globalaccount = $taskparams['globalaccount'];
//        }
//    }

//    if (isset($taskparams['dictionaryPlan']) && $taskparams['dictionaryPlan'] != '') {
//        $imtask->taskparams->dictionaryPlan = $taskparams['dictionaryPlan'];
//    }
//    $imtask->taskparams->iscommit = true;
}

function getweibo($source, $weiboidtype, $weiboid, $isseed = false, $timeline = 'show_status')
{
    global $logger, $dsql, $oAuthThird, $task, $res_machine, $res_ip, $res_acc;
    $result = array("result" => true, "msg" => "");
    try {
        $needqueryid = false;
        if ($weiboidtype != "id") {
            $weiboid = weiboUrl2mid($weiboid, $source);
            $needqueryid = true;
        }
        $chkfieldname = $weiboidtype == 'id' ? 'id' : 'mid';
        $sqlsel = "select id,mid, isseed, update_time from " . DATABASE_WEIBO . " where sourceid = {$source} and {$chkfieldname} = '{$weiboid}'";
        $qr = $dsql->ExecQuery($sqlsel);
        if (!$qr) {
            $result['result'] = false;
            $result['msg'] = "sql error:" . $dsql->GetError();
            $logger->error(SELF . ' sql :' . $sqlsel . ' error: ' . $dsql->GetError());
        } else {
            $q_r = $dsql->GetArray($qr);
            if (!empty($q_r)) {
                $timediff = (time() - $q_r['update_time']);
                $logger->debug(__FUNCTION__ . " timediff:{$timediff},limit:" . TIMELIMIT_UPDATEWEIBO);
                if ($timeline != 'repost_timeline' && $timediff < TIMELIMIT_UPDATEWEIBO) {
                    $logger->debug(SELF . " weibo:" . $weiboid . "已存在，跳过");
                    $result['result'] = true;
                    $result['weiboid'] = $q_r['id'];
                    if ($q_r['isseed'] == 0 && $isseed) {//旧数据非种子，任务中指定为种子，修改旧数据
                        setSeedWeibo($source, $q_r['id'], $q_r['mid']);
                    }
                    return $result;
                } else {//时间超出，需要更新
                    if ($needqueryid && !empty($q_r['id'])) {
                        $weiboid = $q_r['id'];
                        $needqueryid = false;
                    }
                }
            }
            //else{
            $task->tasksource = $source;
            $task->taskparams->source = $source;

            getAllConcurrentRes($task, $res_machine, $res_ip, $res_acc);
            if ($task->taskparams->scene->state == SCENE_NORMAL) {
                //checkAndApplyResource($task,$res_machine,$res_ip,$res_acc);
                //if($task->taskparams->scene->state == SCENE_NORMAL){
                if ($needqueryid) {  //实际传递的是js转换后的 mid
                    //根据mid获取ID
                    $result = queryid($weiboid);
                    if ($result['result'] && isset($result['weiboid'])) {
                        $realid = $result['weiboid'];
                    } else {
                        $result['result'] = false;
                    }
                } else {
                    $realid = $weiboid;
                }
                if ($result['result'] !== false) {
                    $result = show_status($realid);
                }
                //}
                //else{
                //	$result['result'] = false;
                //	$result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
                //}
            } else {//无资源
                $result['result'] = false;
                $result['nores'] = true;
                $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
            }
            //}
        }
    } catch (Exception $e) {
        $result['result'] = false;
        $result['msg'] = $e->getMessage();
    }
    $task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
    myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    return $result;
}

//增加分词方案参数。从爬虫出衍生的新任务增加了方案参数 在此接收保存到数据库
function addImportTask($sourceid, $data, $urltype, $remarks, $isseed = false, $depend = 0, $dicionary_plan)
{
    global $logger, $taskadd, $reposttask;
    // $logger->error(__FUNCTION__." abcd:{$dicionary_plan} has error:".$dsql->GetError());
    $evcount = 1;
    if (defined('ADMIN_MAXIMPORT_COUNT')) {
        $evcount = ADMIN_MAXIMPORT_COUNT;
    } else {
        $evcount = 50;
    }
    $alldatas = array_chunk($data, $evcount);//拆分成不同的任务
    $allcount = count($alldatas);
    $succcount = 0;
    for ($i = 0; $i < $allcount; $i++) {
        $imtask = new Task(null);
        $imtask->taskparams->dictionary_plan = $dicionary_plan;//分词方案
        $imtask->tasktype = TASKTYPE_SPIDER;//抓取
        $imtask->task = TASK_IMPORTWEIBOURL;//批量植入
        $imtask->remarks = empty($remarks) ? "" : $remarks;
        $curridx = $i * $evcount + 1;
        $imtask->remarks .= "本任务处理第" . $curridx . "条 ~ 第" . ($curridx + count($alldatas[$i]) - 1) . "条";
        $imtask->tasksource = $sourceid;
        $imtask->tasklevel = 1;
        $imtask->local = 1;
        $imtask->remote = 0;
        $imtask->activatetime = 0;
        $imtask->conflictdelay = 60;
        $imtask->taskparams->isseed = $isseed;
        $imtask->taskparams->data = $alldatas[$i];//所有数据都放到data中
        $imtask->taskparams->datatype = $urltype;//数据类型:id  url  weibo comment四种
        $imtask->taskparams->source = $sourceid;
        $imtask->taskparams->iscommit = true;
        $imtask->taskparams->addreposttask = $taskadd;
        if ($taskadd) {
            $imtask->taskparams->reposttask = $reposttask;
        }
        if (!empty($depend)) {
            $imtask->taskparams->depend = $depend;
        }
        if (addTask($imtask)) {
            $succcount++;
        } else {
            break;
        }
    }
    $result['result'] = $succcount == $allcount;
    if ($result['result']) {
        $result['msg'] = '添加植入任务成功，每个任务处理' . $evcount . '条，共' . $allcount . "个";
    } else if ($succcount == 0) {
        $result['msg'] = '添加植入任务失败';
    } else {
        $result['msg'] = '共有' . $allcount . '个植入任务，每个任务处理' . $evcount . '条，添加成功' . $succcount . '个，第' . ($succcount + 1) . '个添加失败';
    }
    return $result;
}

function chunkDeriveTask($postdata, $taskid, $remarks)
{
    global $logger;
    $evcount = 1;
    $taskparams = getTaskparam($taskid);
    if (!empty($taskparams['derivetexttask'])) {
        $eachcount = $taskparams['importarticlecount'];
    } else if (!empty($taskparams['deriveusertask'])) {
        $eachcount = $taskparams['importusercount'];
    }
    if (!empty($eachcount)) {
        $evcount = $eachcount;
    } else {
        if (defined('ADMIN_MAXIMPORT_COUNT')) {
            $evcount = ADMIN_MAXIMPORT_COUNT;
        } else {
            $evcount = 50;
        }
    }

    $allcount = count($postdata['data']);
    $succcount = 0;
    $eachdatas = array_chunk($postdata['data'], $evcount);//拆分成不同的任务
    foreach ($eachdatas as $ei => $eitem) {
        $curinx = $ei * $evcount + 1;
        $tmpr = "本任务处理第" . $curinx . "条 ~ 第" . ($curinx + count($eitem) - 1) . "条";
        if (addDeriveTask($eitem, $taskparams, $remarks . $tmpr)) {
            $succcount += count($eitem);
        }
    }
    $result['result'] = $succcount == $allcount;
    if ($result['result']) {
        $result['msg'] = '添加植入任务成功，每个任务处理' . $evcount . '条，共' . count($eachdatas) . "个";
    } else if ($succcount == 0) {
        $result['msg'] = '添加植入任务失败';
    } else {
        $result['msg'] = '共有' . count($eachdatas) . '个植入任务，每个任务处理' . $evcount . '条，添加成功' . $succcount . '个，第' . ($succcount + 1) . '个添加失败';
    }
    return $result;
}

function addDeriveTask($eachpostdata, $taskparams, $remarks)
{
    global $logger;

    if (isset($taskparams['derivetexttask']) && isset($taskparams['texturls'])) {
        $urlrule = $taskparams['texturls'];
    } else if (isset($taskparams['deriveusertask']) && isset($taskparams['userurls'])) {
        $urlrule = $taskparams['userurls'];
    }
//    $logger->error(__FUNCTION__ . " addDeriveTask ******* test urlrule:" . $urlrule);

    //解析url得到取哪个字段的值
    //"http://$<engine \"%s\">/$<userid \"%s\">/$<path \"%s\">{engine:Enum(s) userid:Enum(%E6%98%AF) path:Enum(谁的)}"
    if (isset($urlrule) && !is_null($urlrule)) {
        preg_match_all("/(\w*):Enum\(\|([^\)]+)\|\)/", $urlrule, $matches);
        if (count($matches[0]) > 0) {
            $needvalue = $matches[2]; //需要从变量中取值
            $valuepos = array();
            foreach ($needvalue as $ni => $nitem) {
                $valuepos[] = explode(".", $nitem);
            }
            $replaceArray = array(); //需要替换的数组
            foreach ($eachpostdata as $key => $value) {
                for ($i = 0; $i < count($valuepos); $i++) {
                    //从数组中取值,可能从一维数组中取,也可能为二维的 ,待优化, 现在只支持从取两级
                    if (count($valuepos[$i]) == 1) {//name
                        if (isset($replaceArray[$matches[1][$i]])) {
                            if (isset($value[$valuepos[$i][0]])) {
                                if (!in_array($value[$valuepos[$i][0]], $replaceArray[$matches[1][$i]])) {
                                    $replaceArray[$matches[1][$i]][] = $value[$valuepos[$i][0]];
                                }
                            }
                        } else {
                            if (isset($value[$valuepos[$i][0]])) {
                                $replaceArray[$matches[1][$i]] = array();
                                $replaceArray[$matches[1][$i]][] = $value[$valuepos[$i][0]];
                            }
                        }
                    } else if (count($valuepos[$i]) == 2) {//user.name
                        if (isset($replaceArray[$matches[1][$i]])) {
                            if (isset($value[$valuepos[$i][0]]) && isset($value[$valuepos[$i][0]][$valuepos[$i][1]])) {
                                if (!in_array($value[$valuepos[$i][0]][$valuepos[$i][1]], $replaceArray[$matches[1][$i]])) {
                                    $replaceArray[$matches[1][$i]][] = $value[$valuepos[$i][0]][$valuepos[$i][1]];
                                }
                            }
                        } else {
                            if (isset($value[$valuepos[$i][0]]) && isset($value[$valuepos[$i][0]][$valuepos[$i][1]])) {
                                $replaceArray[$matches[1][$i]] = array();
                                $replaceArray[$matches[1][$i]][] = $value[$valuepos[$i][0]][$valuepos[$i][1]];
                            }
                        }
                    }
                }
            }
            foreach ($replaceArray as $ri => $ritem) {
                if (count($ritem) > 0) {
                    $pattern = "/" . $ri . ":Enum\(\|([^\)]+)\|\)/";
                    $replacement = "" . $ri . ":Enum(" . implode(",", $ritem) . ")";
                    $urlrule = preg_replace($pattern, $replacement, $urlrule);
                }
            }
        }
        //解析obj
        preg_match_all("/(\w*):Obj\(([^\s}]+)/", $urlrule, $matchesObj);
        if (count($matchesObj[0]) > 0) {
            foreach ($matchesObj[2] as $mi => $mitem) {
                preg_match_all("/(\w*):\|([\w|.]+)\|/", $mitem, $matchessub);
                $needvalue = $matchessub[2]; //从数组中取值的标识
                $valuepos = array();
                foreach ($needvalue as $ni => $nitem) {
                    $valuepos[] = explode(".", $nitem);
                }
                $replaceArray = array(); //需要替换的数组
                //循环N条抓取结果
                foreach ($eachpostdata as $key => $value) {
                    //$value :一条抓取结果
                    for ($i = 0; $i < count($valuepos); $i++) {
                        //从数组中取值,可能从一维数组中取,也可能为二维的
                        if (count($valuepos[$i]) == 1) {
                            if (isset($replaceArray[$matchessub[1][$i]])) {
                                if (isset($value[$valuepos[$i][0]])) {
                                    if (!in_array($value[$valuepos[$i][0]], $replaceArray[$matchessub[1][$i]])) {
                                        $replaceArray[$matchessub[1][$i]][] = $value[$valuepos[$i][0]];
                                    }
                                }
                            } else {
                                if (isset($value[$valuepos[$i][0]])) {
                                    $replaceArray[$matchessub[1][$i]] = array();
                                    $replaceArray[$matchessub[1][$i]][] = $value[$valuepos[$i][0]];
                                }
                            }
                        } else if (count($valuepos[$i]) == 2) {
                            if (isset($replaceArray[$matchessub[1][$i]])) {
                                if (isset($value[$valuepos[$i][0]]) && isset($value[$valuepos[$i][0]][$valuepos[$i][1]])) {
                                    if (!in_array($value[$valuepos[$i][0]], $replaceArray[$matchessub[1][$i]])) {
                                        $replaceArray[$matchessub[1][$i]][] = $value[$valuepos[$i][0]][$valuepos[$i][1]];
                                    }
                                }
                            } else {
                                if (isset($value[$valuepos[$i][0]]) && isset($value[$valuepos[$i][0]][$valuepos[$i][1]])) {
                                    $replaceArray[$matchessub[1][$i]] = array();
                                    $replaceArray[$matchessub[1][$i]][] = $value[$valuepos[$i][0]][$valuepos[$i][1]];
                                }
                            }
                        }
                    }
                }
                if (count($needvalue) > 1) {
                    $ab = array();
                    foreach ($replaceArray as $ri => $ritem) {
                        foreach ($ritem as $ai => $aitem) {
                            if (!isset($ab[$ai])) {
                                $ab[$ai] = array();
                                $ab[$ai][] = $ri . ":" . $aitem;
                            } else {
                                $ab[$ai][] = $ri . ":" . $aitem;
                            }
                        }
                    }
                } else {
                    foreach ($replaceArray as $ri => $ritem) {
                        $pattern = "/" . $ri . ":\|([\w|.]+)\|/";
                        $replacement = "" . $ri . ":~" . implode(".", $ritem) . "~";
                        $urlrule = preg_replace($pattern, $replacement, $urlrule);
                    }
                }
            }
            preg_match_all("/(\w*):Obj\(([^\s}]+)/", $urlrule, $matches3);
            foreach ($matches3[2] as $km => $kmite) {
                $vowels = array("(", ")");
                $onlyconsonants = str_replace($vowels, "", $kmite);
                $tmparr = explode(",", $onlyconsonants); //"user:~111.222~,path:path"
                $tmp1 = array();
                $maxcount = 0;
                foreach ($tmparr as $ti => $titem) { //"user:~111.222~
                    $tt = explode(":", $titem);
                    $tmp = array();
                    $cc = count(explode(".", $tt[1]));
                    if ($cc > $maxcount) {
                        $maxcount = $cc;
                    }

                    if ($cc > 0) {
                        $st = explode(".", str_replace("~", "", $tt[1]));
                        foreach ($st as $ei => $eitem) { //:~111.222~
                            $tmp[] = $eitem;
                        }
                    }
                    $tmp1[$tt[0]] = $tmp;
                }
                $ab = array();
                foreach ($tmp1 as $ri => $ritem) {
                    for ($ai = 0; $ai < $maxcount; $ai++) {
                        $aitem = isset($ritem[$ai]) ? $ritem[$ai] : "";
                        if (count($ritem) == 1) { //常量
                            $aitem = $ritem[0];
                        }
                        if (!isset($ab[$ai])) {
                            $ab[$ai] = array();
                            $ab[$ai][] = $ri . ":" . $aitem;
                        } else {
                            $ab[$ai][] = $ri . ":" . $aitem;
                        }
                    }
                }
                $farr = array();
                foreach ($ab as $ai => $aitem) {
                    $tmpitem = "(" . implode(",", $aitem) . ")";
                    if (!in_array($tmpitem, $farr)) {
                        $farr[] = $tmpitem;
                    }
                }
                $pattern = "/" . $matches3[1][$km] . ":Obj\(([\S|}]+)/";
                $replacement = $matches3[1][$km] . ":Obj(" . implode(",", $farr) . ")";
                $urlrule = preg_replace($pattern, $replacement, $urlrule);
            }
        }
    }

    $imtask = new Task(null);
    $imtask->tasktype = TASKTYPE_SPIDER;//抓取
    $imtask->task = TASK_WEBPAGE;//批量植入
    $imtask->remarks = empty($remarks) ? "" : $remarks;
    $imtask->tasklevel = $taskparams['tasklevel'];
    $imtask->local = 0;
    $imtask->remote = 1;
    $imtask->activatetime = 0;
    $imtask->conflictdelay = 60;
    if (!empty($taskparams['derivetexttask'])) {
        $imtask->taskpagestyletype = TASK_PAGESTYLE_ARTICLEDETAIL;
        $imtask->taskparams->SStemplate = $taskparams['SStemplate'];
        if (isset($taskparams['lastrplytimestart'])) {
            $imtask->taskparams->lastrplytimestart = $taskparams['lastrplytimestart'];
        }
        if (isset($taskparams['lastrplytimeend'])) {
            $imtask->taskparams->lastrplytimeend = $taskparams['lastrplytimeend'];
        }
        $imtask->taskparams->texturls = $urlrule;
        if (isset($taskparams['usertemplate'])) {
            $imtask->taskparams->usertemplate = $taskparams['usertemplate'];
        }
        if (isset($taskparams['userurls'])) {
            $imtask->taskparams->userurls = $taskparams['userurls'];
        }
        if (isset($taskparams['deriveusertask'])) {
            $imtask->taskparams->deriveusertask = $taskparams['deriveusertask'];
        }
        if (isset($taskparams['importusercount'])) {
            $imtask->taskparams->importusercount = $taskparams['importusercount'];
        }
    } else if (!empty($taskparams['deriveusertask'])) {
        $imtask->taskpagestyletype = TASK_PAGESTYLE_USERDETAIL;
        $imtask->taskparams->usertemplate = $taskparams['usertemplate'];
        if (isset($urlrule) && !is_null($urlrule)) {
            $imtask->taskparams->userurls = $urlrule;
        }
    }
    if (!empty($taskparams['duration'])) {
        $imtask->taskparams->duration = $taskparams['duration'];
    }
    if (!empty($taskparams['source'])) {
        $imtask->taskparams->source = $taskparams['source'];
    }
    if (!empty($taskparams['accountid'])) {
        $imtask->taskparams->accountid = $taskparams['accountid'];
    }
    if (isset($taskparams['logoutfirst'])) {
        $imtask->taskparams->logoutfirst = $taskparams['logoutfirst'];
    }
    if (isset($taskparams['iscalctrend'])) {
        $imtask->taskparams->iscalctrend = $taskparams['iscalctrend'];
    }
    if (isset($taskparams['isswitch'])) {
        $imtask->taskparams->isswitch = $taskparams['isswitch'];
        if ($taskparams['isswitch']) {
            $imtask->taskparams->switchpage = $taskparams['switchpage'];
            $imtask->taskparams->switchtime = $taskparams['switchtime'];
            $imtask->taskparams->globalaccount = $taskparams['globalaccount'];
        }
    }

    if (isset($taskparams['dictionaryPlan']) && $taskparams['dictionaryPlan'] != '') {
        $imtask->taskparams->dictionaryPlan = $taskparams['dictionaryPlan'];
    }
    $imtask->taskparams->iscommit = true;
    $res = addTask($imtask);
    return !empty($res);
}

function formatResultInfo()
{
    global $r_info, $task, $apicount, $spidercount, $newcount, $allcount;
    $r_info['datacount'] = $allcount ? $allcount : 0;//提交的数据
    $r_info['apicount'] = $apicount ? $apicount : 0;//访问API次数
    $r_info['spidercount'] = $spidercount ? $spidercount : 0;//总抓取微博数
    $r_info['newcount'] = $newcount ? $newcount : 0;//新增微博数
    $r_info['api_showuser_count'] = isset($task->taskparams->scene->api_showuser_count) ? $task->taskparams->scene->api_showuser_count : 0;//访问查用户API次数
    $r_info['api_queryid_count'] = isset($task->taskparams->scene->api_queryid_count) ? $task->taskparams->scene->api_queryid_count : 0;//访问查ID API次数
    $r_info['api_showstatus_count'] = isset($task->taskparams->scene->api_showstatus_count) ? $task->taskparams->scene->api_showstatus_count : 0;//访问查微博API次数
    $r_info['apierrorcount'] = isset($task->taskparams->scene->apierrorcount) ? $task->taskparams->scene->apierrorcount : 0;//访问API错误数
    $r_info['user_count'] = isset($task->taskparams->scene->user_count) ? $task->taskparams->scene->user_count : 0;//总用户数
    $r_info['userexists_count'] = isset($task->taskparams->scene->userexists_count) ? $task->taskparams->scene->userexists_count : 0;//已存在的用户数
    $r_info['update_user_count'] = isset($task->taskparams->scene->update_user_count) ? $task->taskparams->scene->update_user_count : 0;//更新的用户数
    $r_info['insert_user_count'] = isset($task->taskparams->scene->insert_user_count) ? $task->taskparams->scene->insert_user_count : 0;//新增用户数
    $r_info['exists_weibocount'] = isset($task->taskparams->scene->exists_weibocount) ? $task->taskparams->scene->exists_weibocount : 0;//已存的
    $r_info['update_weibocount'] = isset($task->taskparams->scene->update_weibocount) ? $task->taskparams->scene->update_weibocount : 0;//已更新的
    $r_info['solrerrorcount'] = isset($task->taskparams->scene->solrerrorcount) ? $task->taskparams->scene->solrerrorcount : 0;//调用solr失败条数
    $r_info['solr_count'] = isset($task->taskparams->scene->solr_count) ? $task->taskparams->scene->solr_count : 0;//调用solr多少次
    $r_info['incomplete_count'] = isset($task->taskparams->scene->incomplete_count) ? $task->taskparams->scene->incomplete_count : 0;//本次未处理的微博数
    return $r_info;
}

function getUserTimelineInfo($id, $sourceid)
{
    global $dsql, $logger;
    $result = array();
    $sql = "select id,userid,created_at from " . DATABASE_WEIBO . " where id = '{$id}' and sourceid={$sourceid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql:{$sql} has error:" . $dsql->GetError());
        return false;
    } else {
        $rs = $dsql->GetArray($qr);
        if (!empty($rs)) {
            $result['id'] = $rs['id'];
            $result['userid'] = $rs['userid'];
            $result['created_at'] = $rs['created_at'];
            if (!isset($_SESSION['utl_user']) || $_SESSION['utl_user']['id'] != $rs['userid'] || $_SESSION['utl_user']['sourceid'] != $sourceid) {
                $qr = solr_select_conds(array('users_statuses_count'), array('users_id' => $rs['userid'], 'users_sourceid' => $sourceid));
                if ($qr === false || empty($qr) || !isset($qr[0]['users_statuses_count'])) {
                    $logger->error(__FUNCTION__ . " solr:failed to get users_id:{$rs['userid']} users_sourceid:{$sourceid}");
                    return false;
                }
                unset($_SESSION['utl_user']);
                $_SESSION['utl_user'] = array('id' => $rs['userid'], 'sourceid' => $sourceid, 'statuses_count' => $qr[0]['users_statuses_count']);
            }
            $result['statuses_count'] = $_SESSION['utl_user']['statuses_count'];
        }
    }
    return $result;
}

function formatPostdata($postdata)
{
    global $logger;
    $retdata = array();

//    $isArray = is_array($postdata["data"]);
//    $logger->debug(__FILE__ . __LINE__ . " formatPostdata--+-allData: " . var_export($postdata, true) . " 是否数组:[" . ($isArray ? "是" : "否") . "].");

    if (!empty($postdata)) {
        //处理每条数据
        foreach ($postdata["data"] as $key => $item) {
//            $logger->debug(__FILE__ . __LINE__ . " 过滤html标签--+-数据: " . var_export($item, true) . " ...");

            /*
            $floor = isset($item['floor']) ? $item['floor'] : -1;
            if($floor == -1){
                continue;
            }
            $id = base64_encode($item["original_url"])."_".$floor;
             */
            $item_text = isset($item["text"]) ? $item["text"] : "";
            //$imgpattern = "/<IMG[^>]+src=\"([^\"]+)/"; 当src前有>时将不再适用, 例如前面有js,onmouseover=\"if(this.width>760)....
            $imgpattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.jpeg|\.bmp|\.png|\.pic]?))[\'|\"].*?[\/]?>/";

            preg_match_all($imgpattern, $item_text, $matches2);
            $imgs = $matches2[1];
            $pattern = "/<br>|<br\/>|<p>|<\/p>|<BR>|<BR\/>|<P>|<\/P>/";
            $replacement = "\r\n";
            $text = preg_replace($pattern, $replacement, $item_text);
            $text = strip_tags($text);
            $search = array("'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
                "'<[\/\!]*?[^<>]*?>'si",           // 去掉 HTML 标记
                "'([\r\n])[\s]+'",                 // 去掉空白字符
                "'&(quot|#34);'i",                 // 替换 HTML 实体
                "'&(amp|#38);'i",
                "'&(lt|#60);'i",
                "'&(gt|#62);'i",
                "'&(nbsp|#160);'i",
                "'&(iexcl|#161);'i",
                "'&(cent|#162);'i",
                "'&(pound|#163);'i",
                "'&(copy|#169);'i",
                "'　'i",
                "'&#(\d+);'e");                    // 作为 PHP 代码运行

            $replace = array("",
                "",
                "\\1",
                "\"",
                "&",
                "<",
                ">",
                " ",
                chr(161),
                chr(162),
                chr(163),
                chr(169),
                "",
                "chr(\\1)");
            $text = preg_replace($search, $replace, $text);
//            $logger->debug(__FILE__ . __LINE__ . " 过滤html标签--+-替换标签完成后:" . var_export($text, true));
            $text = strip_tags($text);
            //存段落
            $paragraphs = array();
            //按\r\n分段后,可能是空段,或是段前后有空格需要trim
            $paragraphs = preg_split("/[\r\n]+/", $text);
            //$paraArr = array();
            $article = array();
            foreach ($paragraphs as $pi => $pitem) {
//                $pitem = iconv("UTF-8", "GBK//IGNORE", $pitem);//忽略非法字符
//                $pitem = iconv("GBK", "UTF-8", $pitem);//转回utf8

                $pg_text = trim($pitem); //各个段落
                if (!empty($pg_text)) {
                    $article[] = $pg_text; //整篇文章
                    /*
                    $tmpdata = array();
                    $tmpdata['content'] = $pg_text;
                    $tmpdata['terms'] = array();
                    $paraArr[] = $tmpdata;
                     */
                }
            }
            if (count($article) > 1) {
                $item['pg_text'] = array();
                $item['pg_text'] = $article;
            }
            //存文章
            //段落分割符, 分词前使用\r\n 分词后转为 <BR/>
            $item['text'] = implode("\r\n", $article);

            if (!isset($item["bmiddle_pic"]) || is_null($item["bmiddle_pic"])) {
                $decr = 0;
                $pattern = '/.*?\.[gif|jpg|jpeg|bmp|png|pic]/';
                foreach ($imgs as $idx => $picURL) {
                    $logger->debug(__FILE__ . __LINE__ . " 图片后缀是: " . var_export($picURL, true));
                    $ifMatch = preg_match($pattern, $picURL);
                    if (!$ifMatch) {
                        $logger->debug(__FILE__ . __LINE__ . " current url for picture is not valid: " . var_export($picURL, true));
                        //
                        //unset($imgs[$idx]); // 下标不对齐 变成关联数组
//                    array_splice($imgs, $idx, 1); //对齐下标 下标重构
                        array_splice($imgs, $idx - $decr, 1);
                        $decr++;
                    } else {
                        $logger->debug(__FILE__ . __LINE__ . " current url for picture is valid: " . var_export($picURL, true));
                    }
                }
                $item["bmiddle_pic"] = $imgs;
            }
            //当每条记录的page_url不存在时,使用全局的page_url
            /*
            if(!isset($item["page_url"]))
                $item["page_url"] = $postdata["page_url"];
            if(isset($postdata["original_url"])){
                $item["original_url"] = $postdata["original_url"];
            }
            if(isset($postdata["source_host"])){
                $item["source_host"] = $postdata["source_host"];
            }
            if(isset($postdata["sourceid"])){
                $item["sourceid"] = $postdata["sourceid"];
            }
             */
            $item['paragraphid'] = 0;
            $retdata[] = $item;
        }
        $postdata["data"] = $retdata;
    } else {

    }
    return $postdata;
}

function supplyComment($sourceid, &$comments)
{
    if (empty($comments)) {
        return true;
    }
    $r = true;
    for ($i = 0; $i < count($comments); $i++) {
        if ($sourceid == WEIBO_SINA) {
            $comments[$i]['text'] = trim($comments[$i]['text']);
            if (!empty($comments[$i]['text'])) {
                if (strpos($comments[$i]['text'], "：") === 0) {
                    $comments[$i]['text'] = substr($comments[$i]['text'], strlen("："));
                }
                $comments[$i]['text'] = trim($comments[$i]['text']);
            }
        }
    }
    return $r;
}