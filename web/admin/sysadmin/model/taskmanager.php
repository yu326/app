<?php
/*
 * 任务管理API
 * author: Todd
 */
define("TASKMANAGER", "taskmanager.php");
define("GET_DATA", 3);    //通过该标识，获取配置信息和任务信息
define("CONFIG_TYPE", GET_DATA);    //需要在include common.php之前，定义CONFIG_TYPE

define("TYPE", "type");//根据参数类型调用不同的函数
define("CURRENT", "current");
define("DELCURRENT", "delcurrent"); //批量删除
define("RECALLCURRENT", "recallcurrent");  //批量召回
define("ADD", "add");
define("CHANGE", "changestatus");
define("GETIP", "getip");
define("GETACCOUNTSOURCE", "getaccountsource");
define("GETSPIDERCONFIG", "getspiderconfig");
define("GETSPIDERACCOUNT", "getspideraccount");
define("GETDATAHOST", "getdatahost");
define("GETHISTORY", "gethistory");
define("GETIPLIST", "getiplist");
define("DELTASK", "deltask");//删除任务
define("GETALLDATACOUNT", "getalldatacount");
define("GETMACHINE", "getmachine");//获取所有machine
define("CHECKSCREENNAME", "checkscreenname");
define("SUBMITVERICODE", "submitvericode");
define("GETALLMIGRATECOUNT", "getallmigratecount");
define("GETTOTALCOUNT", "gettotalcount");
define("GETTASKSBYTASKTYPE", "gettasksbytasktype"); //根据任务类型获取具体任务
define("GETALLTASKPAGESTYLETYPE", "getalltaskpagestyletype"); //获取任务内容类型
define("GETSEARCHENGINELIST", "getsearchenginelist");
define("GETSEARCHSITELIST", "getsearchsitelist");

include_once('includes.php');
include_once("datatableresult.php");
include_once('commonFun.php');
include_once('taskcontroller.php');
include_once('userinfo.class.php');
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
ini_set('include_path', get_include_path() . '/lib');
include_once('OpenSDK/Tencent/Weibo.php');

initLogger(LOGNAME_WEBAPI);//初始化日志配置
session_start();

$arg_type = "";
if (isset($_POST[TYPE])) {
    $arg_type = $_POST[TYPE];
} else if (isset($_GET[TYPE])) {
    $arg_type = $_GET[TYPE];
} else if (isset($HTTP_RAW_POST_DATA)) {
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    $arg_type = $arrsdata["type"];
}
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);

switch ($arg_type) {
    case CURRENT:
        getCurrentTask();
        break;
    case DELCURRENT:
        delgetCurrent();
        break;
    case RECALLCURRENT:
        recallgetCurrent();
        break;
    case ADD:
        addNewTask();
        break;
    case CHANGE:
        changeTaskStatus();
        break;
    case GETIP:
        getTaskIP();
        break;
    case GETTASKSBYTASKTYPE:
        getTasksbyTasktype();
        break;
    case GETALLTASKPAGESTYLETYPE:
        getAllTaskPageStyleType();
        break;
    case GETSEARCHENGINELIST:
        getSearchEngineList();
        break;
    case GETSEARCHSITELIST:
        getsearchsitelist();
        break;
    case GETACCOUNTSOURCE:
        getAccountSourceList();
        break;
    case GETSPIDERCONFIG:
        getSpiderConfigList();
        break;
    case GETSPIDERACCOUNT:
        getSpiderAccount();
        break;
    case GETDATAHOST:
        getDataHostAliasList();
        break;
    case GETHISTORY:
        getTaskHistory();
        break;
    case GETIPLIST:
        getIPList();
        break;
    case DELTASK:
        deleteTask();
        break;
    case GETALLDATACOUNT:
        getAlldataCount();
        break;
    case GETMACHINE:
        getMachine();
        break;
    case CHECKSCREENNAME:
        checkScreenName();
        break;
    case SUBMITVERICODE:
        submitVeriCode();
        break;
    case GETALLMIGRATECOUNT:
        getAllMigrateCount();
        break;
    case GETTOTALCOUNT:
        gettotalcount();
        break;
    default:
        $logger->error("taskmanager.php参数错误：" . $arg_type);
}

/**
 *
 * distinct task表的所有机器
 */
function getMachine()
{
    global $logger, $dsql;
    $p_t = isset($_GET['t']) ? $_GET['t'] : "task";
    if ($p_t == "history") {
        $tablename = "taskhistory";
    } else {
        $tablename = "task";
    }
    $data_arr = array();
    $sql = "select distinct(machine) from {$tablename}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sqlerror:" . $dsql->GetError());
    } else {
        while ($rs = $dsql->GetArray($qr)) {
            if (!empty($rs['machine'])) {
                $data_arr[] = $rs['machine'];
            }
        }
    }
    $data_str = json_encode($data_arr);
    echo $data_str;
}

function getTaskMachine()
{
    global $logger, $dsql;
    $data_arr;
    $sql = "select machine from taskmachine where status = 1";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $data_arr = array();
        $logger->error(TASKMANAGER . " - getTaskMachine() sqlerror:" . $dsql->GetError());
    } else {
        while ($rs = $dsql->GetArray($qr)) {
            $data_arr[] = $rs->machine;
        }
    }
    $data_str = json_encode($data_arr);
    echo $data_str;
}

function getTaskIP()
{
    global $logger, $dsql;
    $data_arr;
    $sql = "select * from taskip where status=1";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $data_arr = array();
        $logger->error(TASKMANAGER . " - getTaskIP() sqlerror:" . $dsql->GetError());
    } else {
        while ($rs = $dsql->GetArray($qr)) {
            $data_arr[] = $rs;
        }
    }
    $data_str = json_encode($data_arr);
    echo $data_str;
}

//获取当前任务
function getCurrentTask()
{
    global $logger, $dsql;
    $iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
    $iDisplayLength = $_GET['iDisplayLength'];//每页条数
    $iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
    $iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
    $p_task = empty($_GET['task']) ? '' : ' and task=' . $_GET['task'];
    $p_taskstatus = '';
    if (isset($_GET['taskstatus']) && $_GET['taskstatus'] != '') {
        $p_taskstatus = ' and taskstatus=' . $_GET['taskstatus'];
    }

    $p_machine = empty($_GET['machine']) ? '' : " and machine='" . $_GET['machine'] . "'";
    $p_tasklevel = empty($_GET['tasklevel']) ? '' : ' and tasklevel=' . $_GET['tasklevel'];
    //    异常任务错误码查询  add by wang 2017-03-07
    $p_error_code='';
    if (isset($_GET['error_code']) && $_GET['error_code'] != '') {
        $p_error_code = ' and error_code=' . $_GET['error_code'];
    }
    $p_taskpagestyletype = empty($_GET['taskpagestyletype']) ? '' : ' and taskpagestyletype=' . $_GET['taskpagestyletype'];
    $p_local = '';
    //$p_error_code = empty($_GET['error_code']) ? '' : ' and error_code=' . $_GET['error_code'];
    $p_error_msg = empty($_GET['error_msg']) ? '' : ' and error_msg=' . $_GET['error_msg'];
    if (isset($_GET['local']) && $_GET['local'] != '') {
        $p_local = ' and local=' . $_GET['local'];
    }
    $p_remote = '';
    if (isset($_GET['remote']) && $_GET['remote'] != '') {
        $p_remote = ' and remote=' . $_GET['remote'];
    }
    $p_idstart = empty($_GET['id_start']) ? -1 : $_GET['id_start'];
    $p_idend = empty($_GET['id_end']) ? -1 : $_GET['id_end'];
    $p_id = '';
    if ($p_idstart != -1 && $p_idend != -1) {
        $p_id = ' and id >= ' . $p_idstart . ' and id <= ' . $p_idend . '';
    } else if ($p_idstart != -1) {
        $p_id = ' and id >= ' . $p_idstart . '';
    } else if ($p_idend != -1) {
        $p_id = ' and id <= ' . $p_idend . '';
    }
    $wh = " where 1=1 {$p_task} {$p_taskstatus} {$p_machine} {$p_tasklevel} {$p_taskpagestyletype} {$p_local} {$p_remote} {$p_id} {$p_error_code}{$p_error_msg}";
    $p_orderby = empty($_GET['orderby']) ? 'id' : $_GET['orderby'];
    $order = " order by {$p_orderby}";
    $result = new DatatableResult();
    $result->aaData = array();
    $sqlcount = "select count(0) as cnt from task {$wh}";
    $qr = $dsql->ExecQuery($sqlcount);
    if (!$qr) {
        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sqlerror:" . $sqlcount . " - " . $dsql->GetError());
    } else {
        $rcnt = $dsql->GetArray($qr);
        $result->sEcho = $_GET['sEcho'];
        $result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
        $result->iTotalRecords = $rcnt['cnt'];
        $result->iTotalDisplayRecords = $rcnt['cnt'];
        if ($rcnt['cnt'] > 0) {
            $sql = "select * from task {$wh} {$order} limit {$iDisplayStart},{$iDisplayLength}";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sqlerror:" . $sql . " - " . $dsql->GetError());
            } else {
                while ($r = $dsql->GetArray($qr)) {
                    //$r['taskparams'] = json_decode($r['taskparams']);
                    $result->aaData[] = $r;
                }
            }
        }
    }
    echo json_encode($result);
}

// 批量删除筛选后的当前任务
function delgetCurrent()
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '删除成功');
    $p_task = empty($_GET['task']) ? 'and task = 20' : ' and task=' . $_GET['task'];
    $p_taskstatus = '';
    if (isset($_GET['taskstatus']) && $_GET['taskstatus'] != '') {
        $p_taskstatus = ' and taskstatus=' . $_GET['taskstatus'];
    }

    $p_machine = empty($_GET['machine']) ? '' : " and machine='" . $_GET['machine'] . "'";
    $p_tasklevel = empty($_GET['tasklevel']) ? '' : ' and tasklevel=' . $_GET['tasklevel'];
    $p_error_code = empty($_GET['error_code']) ? '' : ' and error_code=' . $_GET['error_code'];
    $p_taskpagestyletype = empty($_GET['taskpagestyletype']) ? '' : ' and taskpagestyletype=' . $_GET['taskpagestyletype'];
    $p_local = '';
    if (isset($_GET['local']) && $_GET['local'] != '') {
        $p_local = ' and local=' . $_GET['local'];
    }
    $p_remote = '';
    if (isset($_GET['remote']) && $_GET['remote'] != '') {
        $p_remote = ' and remote=' . $_GET['remote'];
    }
    $p_idstart = empty($_GET['id_start']) ? -1 : $_GET['id_start'];
    $p_idend = empty($_GET['id_end']) ? -1 : $_GET['id_end'];
    $p_id = '';
    if ($p_idstart != -1 && $p_idend != -1) {
        $p_id = ' and id >= ' . $p_idstart . ' and id <= ' . $p_idend . '';
    } else if ($p_idstart != -1) {
        $p_id = ' and id >= ' . $p_idstart . '';
    } else if ($p_idend != -1) {
        $p_id = ' and id <= ' . $p_idend . '';
    }
    $wh = " where 1=1 {$p_task} {$p_taskstatus} {$p_machine} {$p_tasklevel} {$p_error_code} {$p_taskpagestyletype} {$p_local} {$p_remote} {$p_id}";
    $sql1 = "select * from task {$wh}";
    $qr1 = $dsql->ExecQuery($sql1);
    if (!$qr1) {
        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sqlerror:" . $sql1 . " - " . $dsql->GetError());
    } else {
        while ($r1 = $dsql->GetArray($qr1)) {
            if(!isset($delete_ids) || empty($delete_ids)){
                $delete_ids = $r1['id'];
            }else{
                $delete_ids .= ",".$r1['id'];
            }

        }
//        $logger->info(__LINE__.__LINE__." the $delete_ids is : ".$delete_ids);
        if( !isset($delete_ids) || empty($delete_ids) ) {
//            $logger->info(__LINE__.__LINE__." the $delete_ids is : ".$sql);
            $result = array('result' => false, 'msg' => '没有要删除的数据');
        } else {
            $sql = "delete from task where id in (".$delete_ids.")";
            $logger->info(__LINE__.__LINE__." the sql is : ".var_export($sql,true));
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $result = array('result' => false, 'msg' => '删除失败');
                $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sqlerror:" . $sql . " - " . $dsql->GetError());
            }
        }
    }

    echo json_encode($result);
}

//批量召回 筛选后的当前任务
function recallgetCurrent()
{
    global $logger, $dsql;
    $result = array('result' => false, 'msg' => '没有查到要召回的数据');
    $p_task = empty($_GET['task']) ?  ' and task = 20 '   : ' and task=' . $_GET['task'];
    $logger->info(__LINE__ . " the p_task is : " . var_export($p_task, true));
    $p_taskstatus = '';
    $taskstatus = '';
    if (isset($_GET['taskstatus']) && $_GET['taskstatus'] != '') {
        $p_taskstatus = ' and taskstatus=' . $_GET['taskstatus'];
        $taskstatus = $_GET['taskstatus'];
    }
    $p_machine = empty($_GET['machine']) ? '' : " and machine='" . $_GET['machine'] . "'";
    $p_tasklevel = empty($_GET['tasklevel']) ? '' : ' and tasklevel=' . $_GET['tasklevel'];
    $p_taskpagestyletype = empty($_GET['taskpagestyletype']) ? '' : ' and taskpagestyletype=' . $_GET['taskpagestyletype'];
    $p_local = '';
    if (isset($_GET['local']) && $_GET['local'] != '') {
        $p_local = ' and local=' . $_GET['local'];
    }
    $p_remote = '';
    if (isset($_GET['remote']) && $_GET['remote'] != '') {
        $p_remote = ' and remote=' . $_GET['remote'];
    }
    $p_idstart = empty($_GET['id_start']) ? -1 : $_GET['id_start'];
    $p_idend = empty($_GET['id_end']) ? -1 : $_GET['id_end'];
    $p_id = '';
    if ($p_idstart != -1 && $p_idend != -1) {
        $p_id = ' and id >= ' . $p_idstart . ' and id <= ' . $p_idend . '';
    } else if ($p_idstart != -1) {
        $p_id = ' and id >= ' . $p_idstart . '';
    } else if ($p_idend != -1) {
        $p_id = ' and id <= ' . $p_idend . '';
    }
    //等待启动状态不可被召回。 子已得鱼，还复求鱼为何？  by yu   2017/3/31
    if( $taskstatus != ''& $taskstatus != 0 ) {
        $wh = " where 1=1 {$p_task} {$p_taskstatus} {$p_machine} {$p_tasklevel} {$p_taskpagestyletype} {$p_local} {$p_remote} {$p_id}";
        $sql1 = "select * from task {$wh}";
        $logger->debug(__FUNCTION__.__FILE__.__LINE__."the sql1 is:".var_export($sql1,true));
        $row = $dsql->GetTotalRow($sql1);
        $logger->debug(__FUNCTION__.__FILE__.__LINE__."the row is:".var_export($row,true));
        if (!$row) {
            $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sql1error:" . $sql1 . " - " . $dsql->GetError());
        } else {
            if($row == 0){
                $result = array('result' => false, 'msg' => '没有查到要召回的数据');
            }else{
                $sql = "update task set taskstatus = '0' {$wh}";
                $logger->info(__LINE__ . __LINE__ . " the sql is : " . var_export($sql, true));
                $qr = $dsql->ExecQuery($sql);
                $result = array('result' => true, 'msg' => '召回成功');
                if (!$qr) {
                    $result = array('result' => false, 'msg' => '召回失败');
                    $logger->error(TASKMANAGER . " " . __FUNCTION__ . " sqlerror:" . $sql . " - " . $dsql->GetError());
                }
            }
        }
    }else{
        $result = array('result' => false, 'msg' => '不能召回等待启动的任务');
    }
    echo json_encode($result);
}


function addNewTask()
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    $tasktype = $_POST["tasktype"];
    $task = $_POST["task"];
    $taskclassify =isset($_POST['specifiedType'])? $_POST['specifiedType']: NULL;
    $spcfdmac = isset($_POST['specifiedMac'])? $_POST['specifiedMac']: NULL;
//    $logger->debug(" - createSchedule() specifiedType:[".$taskclassify."]  spcfdmac:[".$spcfdmac."]  importurl:[" .$importurl ."]");

    if (!$tasktype || !$task) {
        $result = array('result' => false, 'msg' => '参数错误');
        $logger->error(TASKMANAGER . " - addNewTask() 参数错误");
    } else {
        $local = empty($_POST["local"]) ? 0 : 1;
        $remote = empty($_POST["remote"]) ? 0 : 1;
        if ($local == 0 && $remote == 0) {
            $local = 1;
        }
        $activatetime = empty($_POST["activatetime"]) ? 0 : strtotime($_POST["activatetime"]);
        $conflictdelay = empty($_POST["conflictdelay"]) ? 60 : (int)$_POST["conflictdelay"];
        if ($local == 1 || $task == TASK_REPOST_TREND || $task == TASK_COMMENTS) {
            $iscommit = $_POST['iscommit'];
            if ($iscommit == 1) {
                $taskparams['iscommit'] = true;
            } else {
                $taskparams['iscommit'] = false;
            }
        }
        //添加字典方案
        //$abc=$_POST['dictionaryPlan'];
        //$logger->error(TASKMANAGER."abc".var_export($abc,true));
        if (isset($_POST['dictionaryPlan']) && $_POST['dictionaryPlan'] != '') {
            $taskparams['dictionaryPlan'] = $_POST['dictionaryPlan'];
        }
        //重新导入
        if ($task == TASK_SYNC) {
            //max
            if (!empty($_POST["maxanalysistime"])) {
                $maxaystime = strtotime($_POST["maxanalysistime"]);
            } else {
                $maxaystime = null;
            }
            $taskparams['maxanalysistime'] = $maxaystime;
            //min
            if (!empty($_POST["minanalysistime"])) {
                $minaystime = strtotime($_POST["minanalysistime"]);
            } else {
                $minaystime = null;
            }
            $taskparams['minanalysistime'] = $minaystime;
            //$taskparams = array("maxanalysistime"=>$aystime,"each_count"=>$_POST["each_count"]);
            if (!empty($_POST['starttime'])) {
                $taskparams['min_created_time'] = strtotime($_POST['starttime']);
            }
            if (!empty($_POST['endtime'])) {
                $taskparams['max_created_time'] = strtotime($_POST['endtime']);
            }
            if (!empty($_POST['urls'])) {
                $urls = explode("\r\n", trim($_POST['urls']));
                $taskparams['urls'] = $urls;
            }
            if (isset($_POST['source_host']) && $_POST['source_host'] != '') {
                $source_host = split(",", $_POST['source_host']);
                $taskparams['source_host'] = $source_host;
            }

            $taskparams['each_count'] = empty($_POST["each_count"]) ? 200 : $_POST["each_count"];
            if (!empty($_POST['startdataindex'])) {
                $taskparams['startdataindex'] = $_POST['startdataindex'];
            }
            if (!empty($_POST['enddataindex'])) {
                $taskparams['enddataindex'] = $_POST['enddataindex'];
            }
            if (!empty($_POST['source'])) {
                $taskparams['source'] = $_POST['source'];
            }
            if (!empty($_POST['add_title_after_text'])) {
                $taskparams['add_title_after_text'] = $_POST['add_title_after_text'];
            }
            if (empty($_POST['func']) && empty($_POST['func_other'])) {
                $result = array('result' => false, 'msg' => '未选择功能');
                $logger->error(TASKMANAGER . " - addNewTask() 参数错误 func is empty");
            } else {
                if (!empty($_POST['func'])) {
                    $taskparams['tokenize_fields'] = $_POST['func'];
                }
                if (!empty($_POST['func_other'])) {
                    if (empty($_POST['otherfields'])) {
                        $result = array('result' => false, 'msg' => '未选择字段');
                        $logger->error(TASKMANAGER . " - addNewTask() 参数错误 otherfields is empty");
                    } else {
                        $taskparams['other_fields'] = $_POST['otherfields'];
                    }
                }
            }
        }

        //抓取微博
        if ($task == TASK_WEIBO) {
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
            if ($local == 1) {
                if (!empty($_POST['each_count'])) {
                    $taskparams['each_count'] = $_POST['each_count'];
                }
            }
            if (isset($_POST['usertype']) && $_POST['usertype'] != '') {
                $taskparams['usertype'] = $_POST['usertype'];
            }
            if (!empty($_POST["min_follower_count"])) {
                $taskparams["min_follower_count"] = $_POST["min_follower_count"];
            }
            if (!empty($_POST['inputtype'])) {
                $taskparams['inputtype'] = $_POST['inputtype'];
            }
            if (!empty($_POST['addeduser'])) {
                $adduser = split(",", $_POST['addeduser']);
                $taskparams["users"] = $adduser;
                //当时用户昵称时需要查询出对应的id , 解决用户修改昵称后,不能通过昵称抓取微博的问题
                if (!empty($_POST['inputtype']) && $_POST['inputtype'] == 'screen_name') {
                    $userids = array();
                    $noexistuser = array();
                    foreach ($adduser as $ai => $aitem) { //循环添加到昵称去新浪查询对应的id
                        $sourceid = get_sourceid_from_url("s.weibo.com");
                        $cu_r = getuser(NULL, $aitem, $sourceid);
                        if ($cu_r['result'] && !empty($cu_r['user'])) {
                            $user = $cu_r['user']['0'];
                            $userids[] = $user["uid"]; //把用户id存在数组中
                        } else if (!empty($cu_r['nores'])) {
                            $result['result'] = false;
                            $result['nores'] = true;
                            $result['msg'] = $cu_r['msg'];
                            break;
                        } else {
                            if (isset($cu_r['error_code']) && $cu_r['error_code'] == ERROR_USER_NOT_EXIST) {
                                $noexistuser[] = $aitem;
                            }
                        }
                    }
                    if (!$result['result']) { //无资源
                        $logger->debug(__FILE__ . __LINE__ . " nores: " . var_export($result, true));
                    }
                    if (!empty($noexistuser)) {
                        $logger->debug(__FILE__ . __LINE__ . " " . $cu_r['msg'] . " : " . var_export($noexistuser, true));
                        $result['result'] = false;
                        $result['msg'] = "查询新浪,不存在的昵称";
                        $result["noexistuser"] = $noexistuser;
                    }
                    $taskparams["userids"] = $userids;
                }
            }
            if ($remote == 1) {
                if (!empty($_POST['taskpagestyletype'])) {
                    $taskpagestyletype = (int)$_POST['taskpagestyletype'];
                }
                if (!empty($_POST['config'])) {
                    $taskparams['config'] = (int)$_POST['config'];
                }
                if (!empty($_POST['duration'])) {
                    $taskparams['duration'] = (int)$_POST['duration'];
                }
                if (isset($_POST['isseed'])) {
                    $taskparams['isseed'] = (int)$_POST['isseed'];
                }
                if (!empty($_POST['starttime'])) {
                    $taskparams['starttime'] = strtotime($_POST['starttime']);
                }
                if (!empty($_POST['endtime'])) {
                    $taskparams['endtime'] = strtotime($_POST['endtime']);
                }
                if (!empty($_POST['step'])) {
                    $taskparams['step'] = $_POST['step'];
                }
                if (!empty($_POST['accountid'])) {
                    $taskparams['accountid'] = split(",", $_POST['accountid']);
                }
                if (isset($_POST['logoutfirst'])) {
                    $taskparams['logoutfirst'] = (int)$_POST['logoutfirst'];
                }
                if (isset($_POST['isswitch'])) {
                    $taskparams['isswitch'] = (int)$_POST['isswitch'];
                    if ($taskparams['isswitch']) {
                        $taskparams['switchpage'] = (int)$_POST['switchpage'];
                        $taskparams['switchtime'] = (int)$_POST['switchtime'];
                        $taskparams['globalaccount'] = (int)$_POST['globalaccount'];
                    }
                }
            }
        }
        //分析转发轨迹
        if ($task == TASK_REPOSTPATH) {
            $oriurls = array();
            if (!empty($_POST["addedorigurl"])) {
                $oriurls = split(",", $_POST["addedorigurl"]);
            }
            $taskparams["oriurls"] = $oriurls;
        }
        //分析转发轨迹
        if ($task == TASK_COMMENTPATH) {
            $oriurls = array();
            if (!empty($_POST["addedorigurl"])) {
                $oriurls = split(",", $_POST["addedorigurl"]);
            }
            $taskparams["oriurls"] = $oriurls;
        }
        //处理转发
        if ($task == TASK_REPOST_TREND) {
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
            if (!empty($_POST['each_count'])) {
                $taskparams['each_count'] = $_POST['each_count'];
            }
            if (!empty($_POST["rmin_reposts_count"])) {
                $taskparams["min_reposts_count"] = $_POST["rmin_reposts_count"];
            }
            $oristatus = array();
            if (!empty($_POST["addedorigid"])) {
                $oristatus = split(",", $_POST["addedorigid"]);
            }
            $oriurls = array();
            if (!empty($_POST["addedorigmid"])) {
                $oriurls = split(",", $_POST["addedorigmid"]);
            }
            $taskparams["oristatus"] = array_merge($oristatus, $oriurls);
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
            if (!empty($_POST['duration'])) {
                $taskparams['duration'] = (int)$_POST['duration'];
            }
            $taskparams['forceupdate'] = empty($_POST['forceupdate']) ? 0 : 1;
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];
            }
            if (isset($_POST['isrepostseed'])) {
                $taskparams['isrepostseed'] = (int)$_POST['isrepostseed'];
            }
            if (!empty($_POST['accountid'])) {
                $taskparams['accountid'] = split(",", $_POST['accountid']);
            }
            if (isset($_POST['logoutfirst'])) {
                $taskparams['logoutfirst'] = (int)$_POST['logoutfirst'];
            }
            if (isset($_POST['iscalctrend'])) {
                $taskparams['iscalctrend'] = (int)$_POST['iscalctrend'];
            }
        }

        //抓取评论
        if ($task == TASK_COMMENTS) {
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
            if (!empty($_POST['each_count'])) {
                $taskparams['each_count'] = (int)$_POST['each_count'];
            }
            if (!empty($_POST["rmin_comments_count"])) {
                $taskparams["min_comments_count"] = $_POST["rmin_comments_count"];
            }
            $oristatus = array();
            if (!empty($_POST["addedorigid"])) {
                $oristatus = split(",", $_POST["addedorigid"]);
            }
            $oriurls = array();
            if (!empty($_POST["addedorigmid"])) {
                $oriurls = split(",", $_POST["addedorigmid"]);
            }
            $taskparams["oristatus"] = array_merge($oristatus, $oriurls);

            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
            if (!empty($_POST['duration'])) {
                $taskparams['duration'] = (int)$_POST['duration'];
            }
            $taskparams['forceupdate'] = empty($_POST['forceupdate']) ? 0 : 1;
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];
            }
            if (!empty($_POST['accountid'])) {
                $taskparams['accountid'] = split(",", $_POST['accountid']);
            }
            if (isset($_POST['logoutfirst'])) {
                $taskparams['logoutfirst'] = (int)$_POST['logoutfirst'];
            }
            if (isset($_POST['iscalctrend'])) {
                $taskparams['iscalctrend'] = (int)$_POST['iscalctrend'];
            }
        }
        //更新微博
        if ($task == TASK_STATUSES_COUNT) {
            $logger->debug(__FILE__.__LINE__.var_export($_POST,true));
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];  //是否种子  默认1
            }
            if (!empty($_POST['starttime'])) {
                $taskparams['starttime'] = strtotime($_POST['starttime']);
            }
            if (!empty($_POST['endtime'])) {
                $taskparams['endtime'] = strtotime($_POST['endtime']);
            }
            if (!empty($_POST['keywords'])) {
                $keywords = explode("\r\n", trim($_POST['keywords']));
                $taskparams['keywords'] = $keywords;
            }
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
        }
        //抓取关键词
        if ($task == TASK_KEYWORD) {
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
            if (!empty($_POST['duration'])) {
                $taskparams['duration'] = (int)$_POST['duration'];
            }
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];
            }
            //起始页面
            if (isset($_POST['page'])) {
                $taskparams['page'] = (int)$_POST['page'];
            }
            //抓取页码
            if (isset($_POST['crawlpage'])) {
                $taskparams['crawlpage'] = (int)$_POST['crawlpage'];
            }
            if (!empty($_POST['keywords'])) {
                $keywords = explode("\r\n", trim($_POST['keywords']));
                $taskparams['keywords'] = $keywords;
            }
            if(!empty($_POST['username'])) {
                $taskparams['username'] = $_POST['username'];
            }
            if (!empty($_POST['inputtype'])) {
                $taskparams['inputtype'] = $_POST['inputtype'];
            }
            if (!empty($_POST['addeduser'])) {
                $adduser = split(",", $_POST['addeduser']);
                $taskparams["users"] = $adduser;
                //当时用户昵称时需要查询出对应的id , 解决用户修改昵称后,不能通过昵称抓取微博的问题
                if (!empty($_POST['inputtype']) && $_POST['inputtype'] == 'screen_name') {
                    $userids = array();
                    $noexistuser = array();
                    foreach ($adduser as $ai => $aitem) { //循环添加到昵称去新浪查询对应的id
                        $sourceid = get_sourceid_from_url("s.weibo.com");
                        $cu_r = getuser(NULL, $aitem, $sourceid);
                        if ($cu_r['result'] && !empty($cu_r['user'])) {
                            $user = $cu_r['user'];
                            $userids[] = $user["id"]; //把用户id存在数组中
                        } else if (!empty($cu_r['nores'])) {
                            $result['result'] = false;
                            $result['nores'] = true;
                            $result['msg'] = $cu_r['msg'];
                            break;
                        } else {
                            if (isset($cu_r['error_code']) && $cu_r['error_code'] == ERROR_USER_NOT_EXIST) {
                                $noexistuser[] = $aitem;
                            }
                        }
                    }
                    if (!$result['result']) { //无资源
                        $logger->debug(__FILE__ . __LINE__ . " nores: " . var_export($result, true));
                    }
                    if (!empty($noexistuser)) {
                        $logger->debug(__FILE__ . __LINE__ . " " . $cu_r['msg'] . " : " . var_export($noexistuser, true));
                        $result['result'] = false;
                        $result['msg'] = "查询新浪,不存在的昵称";
                        $result["noexistuser"] = $noexistuser;
                    }
                    $taskparams["userids"] = $userids;
                }
                else{
                    $taskparams["userids"] = $adduser;
                }
            }
            if (!empty($_POST['starttime'])) {

                $taskparams['starttime'] = strtotime($_POST['starttime']);
            }
            if (!empty($_POST['endtime'])) {
                $taskparams['endtime'] = strtotime($_POST['endtime']);
            }
            if (!empty($_POST['step'])) {
                $taskparams['step'] = $_POST['step'];
            }
            if (isset($_POST['filterdup'])) {
                $taskparams['filterdup'] = (int)$_POST['filterdup'];
            }
            if (!empty($_POST['accountid'])) {
                $taskparams['accountid'] = split(",", $_POST['accountid']);
            }
            if (isset($_POST['logoutfirst'])) {
                $taskparams['logoutfirst'] = (int)$_POST['logoutfirst'];
            }
            if (isset($_POST['isswitch'])) {
                $taskparams['isswitch'] = (int)$_POST['isswitch'];
                if ($taskparams['isswitch']) {
                    $taskparams['switchpage'] = (int)$_POST['switchpage'];
                    $taskparams['switchtime'] = (int)$_POST['switchtime'];
                    $taskparams['globalaccount'] = (int)$_POST['globalaccount'];
                }
            }
			//是否抓取关键字相关微博
			if(isset($_POST['is_grab_repost'])){
				$taskparams['is_grab_repost'] = $_POST['is_grab_repost'];
			}
        }
        //抓取论坛
        if ($task == TASK_WEBPAGE) {
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            //派生用户任务
            if (!empty($_POST['deriveusertask'])) {
                $taskparams['deriveusertask'] = (int)$_POST['deriveusertask'];
            }
            //爬取站点用户模板
            if (!empty($_POST['usertemplate']) && (!empty($_POST['deriveusertask']) || $taskpagestyletype == TASK_PAGESTYLE_USERDETAIL)) {
                $taskparams['usertemplate'] = (int)$_POST['usertemplate'];
            }
            if (!empty($_POST['userweburl'])) {
                //$taskparams['userurls'] = explode(",", $_POST['userweburl']);
                $userurls = json_decode($_POST['userweburl'], true);
            }
            if (!empty($_POST['importusercount'])) {
                $taskparams['importusercount'] = (int)$_POST['importusercount'];
            }
            //派生正文任务
            if (!empty($_POST['derivetexttask'])) {
                $taskparams['derivetexttask'] = (int)$_POST['derivetexttask'];
            }
            //爬取站点模板
            if (!empty($_POST['SStemplate']) && (!empty($_POST['derivetexttask']) || $taskpagestyletype == TASK_PAGESTYLE_ARTICLEDETAIL)) {
                $taskparams['SStemplate'] = (int)$_POST['SStemplate'];
            }
            if (!empty($_POST['textweburl'])) {
                //$taskparams['texturls'] = explode(",", $_POST['textweburl']);
                $texturls = json_decode($_POST['textweburl'], true);
            }
            if (!empty($_POST['importarticlecount'])) {
                $taskparams['importarticlecount'] = (int)$_POST['importarticlecount'];
            }
            if (!empty($_POST['createdtimestart'])) {
                $taskparams['createdtimestart'] = strtotime($_POST['createdtimestart']);
            }
            if (!empty($_POST['createdtimeend'])) {
                $taskparams['createdtimeend'] = strtotime($_POST['createdtimeend']);
            }
            //搜索列表模板
            if (!empty($_POST['SEtemplate'])) {
                $taskparams['SEtemplate'] = (int)$_POST['SEtemplate'];
            }
            if (!empty($_POST['listweburl'])) {
                //$taskparams['listurls'] = explode(",", $_POST['listweburl']);
                $listurls = json_decode($_POST['listweburl'], true);
            }

            if (!empty($_POST['source'])) {
                $taskparams['source'] = $_POST['source'];
            }
            if (!empty($_POST['duration'])) {
                $taskparams['duration'] = (int)$_POST['duration'];
            }
            if (!empty($_POST['crawlpage'])) {
                $taskparams['crawlpage'] = (int)$_POST['crawlpage'];
            }
            if (!empty($_POST['lastrplytimestart'])) {
                $taskparams['lastrplytimestart'] = strtotime($_POST['lastrplytimestart']);
            }
            if (!empty($_POST['lastrplytimeend'])) {
                $taskparams['lastrplytimeend'] = strtotime($_POST['lastrplytimeend']);
            }

            /*
            if(!empty($_POST['keywords'])){
                $keywords = explode("\r\n", trim($_POST['keywords']));
                $taskparams['keywords'] = $keywords;
            }

            if(isset($_POST['filterdup'])){
                $taskparams['filterdup'] = (int)$_POST['filterdup'];
            }
             */
            if (!empty($_POST['accountid'])) {
                $taskparams['accountid'] = split(",", $_POST['accountid']);
            }
            if (isset($_POST['logoutfirst'])) {
                $taskparams['logoutfirst'] = (int)$_POST['logoutfirst'];
            }
            if (isset($_POST['isswitch'])) {
                $taskparams['isswitch'] = (int)$_POST['isswitch'];
                if ($taskparams['isswitch']) {
                    $taskparams['switchpage'] = (int)$_POST['switchpage'];
                    $taskparams['switchtime'] = (int)$_POST['switchtime'];
                    $taskparams['globalaccount'] = (int)$_POST['globalaccount'];
                }
            }
            if (isset($_POST['iscalctrend'])) {
                $taskparams['iscalctrend'] = (int)$_POST['iscalctrend'];
            }
        }
        if ($task == TASK_COMMON) {
            $logger->debug(__FILE__.__LINE__." taskparams ".var_export($_POST['taskparams'], true));
            $taskparams = json_decode($_POST['taskparams'], true);
        }
        //抓取关注
        if ($task == TASK_FRIEND) {
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
            if (!empty($_POST['source'])) {
                $taskparams['source'] = $_POST['source'];
            }
            if (!empty($_POST['duration'])) {
                $taskparams['duration'] = (int)$_POST['duration'];
            }
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];
            }
            if (!empty($_POST['unames'])) {
                $unames = explode("\r\n", trim($_POST['unames']));
                $taskparams['unames'] = $unames;
            }
            if (!empty($_POST['uids'])) {
                $uids = split(",", $_POST['uids']);
                $taskparams['uids'] = $uids;
            }
        }

        //迁移数据
        if ($task == TASK_MIGRATEDATA) {
            $taskparams['srchost'] = empty($_POST['srchost']) ? 0 : (int)$_POST['srchost'];
            if (isset($_POST['dsthost']) && $_POST['dsthost'] != '') {
                $dsthost = split(",", $_POST['dsthost']);
                foreach ($dsthost as $hostid) {
                    $taskparams['dsthost'][] = (int)$hostid;
                }
            }
            $taskparams['keepsrc'] = empty($_POST['keepsrc']) ? 0 : 1;
            $taskparams['deluser'] = empty($_POST['deluser']) ? 0 : 1;
            $taskparams['delseedweibo'] = empty($_POST['delseedweibo']) ? 0 : 1;
            $taskparams['delseeduser'] = empty($_POST['delseeduser']) ? 0 : 1;
            $taskparams['offset'] = 0;
            if (!empty($_POST['maxcount'])) {
                $taskparams['maxcount'] = (int)$_POST['maxcount'];
            }
            $taskparams['eachcount'] = empty($_POST['eachcount']) ? 100 : (int)$_POST['eachcount'];
            if (!empty($_POST['source'])) {
                $taskparams['source'] = $_POST['source'];
            }
            if (isset($_POST['source_host']) && $_POST['source_host'] != '') {
                $source_host = split(",", $_POST['source_host']);
                $taskparams['source_host'] = $source_host[0];
                /*
                foreach($source_host as $hostid){
                    $taskparams['source_host'][] = (int)$hostid;
                }
                 */
            }
            if (isset($_POST['users_source_host']) && $_POST['users_source_host'] != '') {
                $users_source_host = split(",", $_POST['users_source_host']);
                $taskparams['users_source_host'] = $users_source_host[0];
                /*
                foreach($users_source_host as $hostid){
                    $taskparams['users_source_host'][] = (int)$hostid;
                }
                 */
            }
            $taskparams['cond_deleted'] = 1;
            if (!empty($_POST['cond_in_customquery'])) {
                //$inname = explode("\r\n", trim($_POST['cond_in_customquery']));
                $taskparams['cond_in_customquery'] = trim($_POST['cond_in_customquery']);
            }
            else{
                if (!empty($_POST['cond_lt_created'])) {
                    $taskparams['cond_lt_created'] = strtotime($_POST['cond_lt_created']);
                }
                if (!empty($_POST['cond_ge_created'])) {
                    $taskparams['cond_ge_created'] = strtotime($_POST['cond_ge_created']);
                }
                if (!empty($_POST['cond_ex_text'])) {
                    $extext = explode("\r\n", trim($_POST['cond_ex_text']));
                    $taskparams['cond_ex_text'] = $extext;
                }
                if (!empty($_POST['cond_in_text'])) {
                    $intext = explode("\r\n", trim($_POST['cond_in_text']));
                    $taskparams['cond_in_text'] = $intext;
                }
                if (!empty($_POST['cond_ex_name'])) {
                    $exname = explode("\r\n", trim($_POST['cond_ex_name']));
                    $taskparams['cond_ex_name'] = $exname;
                }
                if (!empty($_POST['cond_in_name'])) {
                    $inname = explode("\r\n", trim($_POST['cond_in_name']));
                    $taskparams['cond_in_name'] = $inname;
                }
            }
            $taskparams['orderby'] = 'created';
            $taskparams['order'] = "desc";
            if (!empty($taskparams['dsthost'])) {
                if (array_search($taskparams['srchost'], $taskparams['dsthost']) !== false) {
                    $result = array('result' => false, 'msg' => '源主机和目标主机冲突');
                    $logger->error(TASKMANAGER . " - addNewTask() srchost and dsthost conflict");
                }
            }
        }
        //抓取账号微博
        if( $task == TASK_NICKNAME ){
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if (!empty($_POST['each_count'])) {
                $taskparams['each_count'] = $_POST['each_count'];
            }
            if (!empty($_POST['inputtype'])) {
                $taskparams['inputtype'] = $_POST['inputtype'];
            }
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
            if (!empty($_POST['keywords'])) {
                $keywords = explode("\r\n", trim($_POST['keywords']));
                $taskparams['keywords'] = $keywords;
            }
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];  //是否种子  默认1
            }
            if (!empty($_POST['addeduser'])) {
                $adduser = split(",", $_POST['addeduser']);
                $taskparams["users"] = $adduser;
            }
            if (!empty($_POST['is_monitor_nickname'])){
                $taskparams['is_monitor_nickname'] = $_POST['is_monitor_nickname'];
            }
            //是否抓取关键字相关微博
            if(isset($_POST['is_grab_repost'])){
                $taskparams['is_grab_repost'] = $_POST['is_grab_repost'];
            }
        }
        //推送数据任务    task=19，select * from task where task = 19；
        //有，则只更新taskparams参数，重要的是分词词典。 无，则新增一条任务。
        if( $task == TASK_DATAPUSH ){
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            $sourceid = get_sourceid_from_url("s.weibo.com");
            $taskparams['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
            if (!empty($_POST['duration'])) {
                $taskparams['duration'] = (int)$_POST['duration'];
            }
            if (isset($_POST['dictionaryPlan']) && $_POST['dictionaryPlan'] != '') {
                $taskparams['dictionaryPlan'] = $_POST['dictionaryPlan'];
            }
            if (isset($_POST['isseed'])) {
                $taskparams['isseed'] = (int)$_POST['isseed'];  //是否种子  默认1
            }
            if (!empty($_POST['inputtype'])) {
                $taskparams['inputtype'] = $_POST['inputtype'];
            }
            if (!empty($_POST['taskpagestyletype'])) {
                $taskpagestyletype = (int)$_POST['taskpagestyletype'];
            }
            if (!empty($_POST['config'])) {
                $taskparams['config'] = (int)$_POST['config'];
            }
            //訂閱微博的數據需要進行多重分詞，所以在這加入添加分詞。如果數據庫有數據，則修改最後入庫的那條的分詞，若沒有，則執行入庫操作
            $sql = "select * from task where task =".$task." order by id desc limit 0,1";
            $logger->info(__FILE__.__LINE__."sql is".var_export($sql,true));
            $qr = $dsql->ExecQuery($sql);

            if(is_resource($qr)){
                $logger->info(__FILE__.__LINE__." is resource");
            }
            if(!$qr){
                $result = array('result' => false, 'msg' => '操作失败');
                $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
            }else{
                $data = mysql_fetch_assoc($qr);
                $logger->info(__FILE__.__LINE__."data is".var_export($data,true));
                if(!empty($data)){
                    $id = $data['id'];
//                    $taskparams = "我一聲的念想盡賦予你";
                    $taskparams_encode = jsonEncode4DB($taskparams);
                    $sql = "update task set taskparams ="." '".$taskparams_encode."'"." where id = ".$id;
                    $logger->info(__FILE__.__LINE__."update sql is".var_export($sql,true));
                    $qr = $dsql->ExecQuery($sql);
                    if(!$qr){
                        $result = array('result' => false, 'msg' => '操作失败');
                        $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                    }else{
                        $logger->info(__FILE__.__LINE__." update success~");
                    }
                    echo json_encode($result);
                    die;
                }


            }
        }

        if ($result['result']) {
            $tenantid = "NULL";
            $userid = "NULL";
            $userinfo = isset($_SESSION['user']) ? $_SESSION['user'] : NULL;
            if ($userinfo != NULL) {
                $tenantid = $userinfo->tenantid;
                $userid = $userinfo->getuserid();
            }
            $fieldname = array();
            $fieldvalue = array();
            if (!empty($tenantid)) {
                $fieldname[] = "tenantid";
                $fieldvalue[] = "'" . $tenantid . "'";
            }
            if (!empty($userid)) {
                $fieldname[] = "userid";
                $fieldvalue[] = "'" . $userid . "'";
            }
            if (!empty($taskpagestyletype)) {
                $fieldname[] = "taskpagestyletype";
                $fieldvalue[] = "'" . $taskpagestyletype . "'";
            }
            $namestr = implode(", ", $fieldname);
            $valuestr = implode(", ", $fieldvalue);
            if (isset($listurls) && isset($texturls) && isset($userurls)) {
                for ($i = 0; $i < count($listurls); $i++) {
                    $taskparams['listurls'] = $listurls[$i];
                    if (isset($texturls)) {
                        for ($j = 0; $j < count($texturls); $j++) {
                            $taskparams['texturls'] = $texturls[$j];
                            if (isset($userurls)) {
                                for ($k = 0; $k < count($userurls); $k++) {
                                    $taskparams['userurls'] = $userurls[$k];
                                    //分析桥接用户  分析桥接案例 无参数
                                    $taskparams_encode = jsonEncode4DB($taskparams);
                                    $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                                    $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                                    $qr = $dsql->ExecQuery($sql);
                                    if (!$qr) {
                                        $result = array('result' => false, 'msg' => '操作失败');
                                        $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                                    }
                                }
                            } else {
                                //分析桥接用户  分析桥接案例 无参数
                                $taskparams_encode = jsonEncode4DB($taskparams);
                                $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                                $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                                $qr = $dsql->ExecQuery($sql);
                                if (!$qr) {
                                    $result = array('result' => false, 'msg' => '操作失败');
                                    $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                                }
                            }
                        }
                    } else {
                        //分析桥接用户  分析桥接案例 无参数
                        $taskparams_encode = jsonEncode4DB($taskparams);
                        $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                        $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                        $qr = $dsql->ExecQuery($sql);
                        if (!$qr) {
                            $result = array('result' => false, 'msg' => '操作失败');
                            $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                        }
                    }
                }
            } else if (isset($listurls) && isset($texturls)) {
                for ($j = 0; $j < count($listurls); $j++) {
                    $taskparams['listurls'] = $listurls[$j];
                    if (isset($texturls)) {
                        for ($k = 0; $k < count($texturls); $k++) {
                            $taskparams['texturls'] = $texturls[$k];
                            //分析桥接用户  分析桥接案例 无参数
                            $taskparams_encode = jsonEncode4DB($taskparams);
                            $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                            $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                            $qr = $dsql->ExecQuery($sql);
                            if (!$qr) {
                                $result = array('result' => false, 'msg' => '操作失败');
                                $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                            }
                        }
                    }
                }
            } else if (isset($texturls) && isset($userurls)) {
                for ($j = 0; $j < count($texturls); $j++) {
                    $taskparams['texturls'] = $texturls[$j];
                    if (isset($userurls)) {
                        for ($k = 0; $k < count($userurls); $k++) {
                            $taskparams['userurls'] = $userurls[$k];
                            //分析桥接用户  分析桥接案例 无参数
                            $taskparams_encode = jsonEncode4DB($taskparams);
                            $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                            $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                            $qr = $dsql->ExecQuery($sql);
                            if (!$qr) {
                                $result = array('result' => false, 'msg' => '操作失败');
                                $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                            }
                        }
                    }
                }
            } else if (isset($userurls)) {
                for ($k = 0; $k < count($userurls); $k++) {
                    $taskparams['userurls'] = $userurls[$k];
                    //分析桥接用户  分析桥接案例 无参数
                    $taskparams_encode = jsonEncode4DB($taskparams);
                    $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                    $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                    $qr = $dsql->ExecQuery($sql);
                    if (!$qr) {
                        $result = array('result' => false, 'msg' => '操作失败');
                        $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                    }
                }
            } else if (isset($listurls)) {
                for ($i = 0; $i < count($listurls); $i++) {
                    $taskparams['listurls'] = $listurls[$i];
                    //分析桥接用户  分析桥接案例 无参数
                    $taskparams_encode = jsonEncode4DB($taskparams);
                    $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                    $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                    $qr = $dsql->ExecQuery($sql);
                    if (!$qr) {
                        $result = array('result' => false, 'msg' => '操作失败');
                        $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                    }
                }
            } else if (isset($texturls)) {
                for ($i = 0; $i < count($texturls); $i++) {
                    $taskparams['texturls'] = $texturls[$i];
                    //分析桥接用户  分析桥接案例 无参数
                    $taskparams_encode = jsonEncode4DB($taskparams);
                    $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
                    $sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams_encode . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
                    $qr = $dsql->ExecQuery($sql);
                    if (!$qr) {
                        $result = array('result' => false, 'msg' => '操作失败');
                        $logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
                    }
                }
            } else {
				
				//抓取关键词时且任务类型为本地时  步长处理	
				//     不对其他类型的步长处理操作造成影响
				if ($task == TASK_KEYWORD && $local == '1') {	
					//判断是否有步长存在  
					//    为空，则没有步长，正常入库
					//    不为空，则获取开始结束时间，根据步长，就行循环，最后入库
					@$step = $taskparams['step'];
					if( empty($step) ){
						 //分析桥接用户  分析桥接案例 无参数
						$taskparams = jsonEncode4DB($taskparams);
						$sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
						$sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
						$qr = $dsql->ExecQuery($sql);
						if (!$qr) {
							$result = array('result' => false, 'msg' => '操作失败');
							$logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
						}
					}else if ( strpos($step, 'h') !== false ){   //步长为小时
						$starttime = $taskparams['starttime'];
						$endtime = $taskparams['endtime'];
						$stepnum = intval($step);
						$arr = array();
						for( $i = 2; $i>0; $i++ ){
							$start = $starttime+(($i-2)*$stepnum*3600);
							$end = $starttime+(($i-1)*$stepnum*3600);
							
							$taskparams['starttime'] = $start;
							$taskparams['endtime'] = $end;
							
							 //分析桥接用户  分析桥接案例 无参数
							$re_taskparams = jsonEncode4DB($taskparams);
														
							$sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
							$sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $re_taskparams . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
							$qr = $dsql->ExecQuery($sql);
							if (!$qr) {
								$result = array('result' => false, 'msg' => '操作失败');
								$logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
							}
							if( $end >= $endtime ){
								break;
							}
							
						}
						
					}else if( $step == '1d' ){    //步长为一天
						$starttime = $taskparams['starttime'];
						$endtime = $taskparams['endtime'];
						$stepnum = intval($step);
						$arr = array();
						for( $i = 2; $i>0; $i++ ){
							$start = $starttime+(($i-2)*$stepnum*24*3600);
							$end = $starttime+(($i-1)*$stepnum*24*3600);				
							
							$taskparams['starttime'] = $start;
							$taskparams['endtime'] = $end;
							
							 //分析桥接用户  分析桥接案例 无参数
							$re_taskparams = jsonEncode4DB($taskparams);
														
							$sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ")";
							$sql = $sql . " values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $re_taskparams . "','" . $_POST["remarks"] . "', " . $valuestr . ")";
							$qr = $dsql->ExecQuery($sql);
							if (!$qr) {
								$result = array('result' => false, 'msg' => '操作失败');
								$logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
							}
							if( $end >= $endtime ){
								break;
							}
							
						}
					}
				}else{
//                    $category = "select * from data_import_category where id =". $taskparams["root"]["taskPro"]["submiturl"];
//                    $qc = $dsql->ExecQuery($category);
//                    if (!$qc) {
//                        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " qcerror:" . $qc . " - " . $dsql->GetError());
//                     } else {
//                        while ($rc = $dsql->GetArray($qc)) {
//                            $industryid = $rc["industry_id"];
//                            $interfacename = $rc["interface_name"];
//                        }
//                    }
//                    $importin = "select * from data_import_industry where id =" . $industryid;
//                    $qi = $dsql->ExecQuery($importin);
//                    if (!$qi) {
//                        $logger->error(TASKMANAGER . " " . __FUNCTION__ . " qierror:" . $qi . " - " . $dsql->GetError());
//                    } else {
//                        while ($ri = $dsql->GetArray($qi)) {
//                            $importserver = $ri["import_server"];
//                            $port = $ri["port"];
//                        }
//                    }
//                    $taskparams["root"]["taskPro"]["contenturl"] = "http://".$importserver .":".$port.$interfacename;
//
//
//                    $logger->debug(__FILE__.__LINE__ . " - addNewTask() contenturl is ".var_export($taskparams["root"]["taskPro"]["contenturl"],true)."].");
                    $taskparams = jsonEncode4DB($taskparams);
//                    if(isset($spcfdmac) && !empty($spcfdmac)){
//                        $logger->debug(TASKMANAGER . " - addNewTask() spcfdmac is un...........................".gettype($spcfdmac)."].");
//                    }

                    $sql = "insert into task(tasktype,task,tasklevel,local,remote,activatetime,conflictdelay,taskstatus,taskparams,remarks, " . $namestr . ",taskclassify,spcfdmac ".")";
                    $sql = $sql." values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams . "','" . $_POST["remarks"] . "', "  . $valuestr  . ","  .((isset($taskclassify) && !empty($taskclassify) && $taskclassify!="undefined" )? "'".$taskclassify."'" : "NULL"). "," . ((isset($spcfdmac) && !empty($spcfdmac) && $spcfdmac!="undefined")? "'".$spcfdmac."'" :"NULL") . ")";
//                    $sql = $sql." values(" . $tasktype . "," . $task . "," . $_POST["tasklevel"] . "," . $local . "," . $remote . "," . $activatetime . "," . $conflictdelay . ",0,'" . $taskparams . "','" . $_POST["remarks"] . "', "  . $valuestr  . ","  .((isset($taskclassify) && !empty($taskclassify))? "'".$taskclassify."'" : "NULL"). "," . ((isset($spcfdmac) && !empty($spcfdmac) )? "'".$spcfdmac."'" :"NULL") . ")";
                    //$logger->debug(TASKMANAGER . " - addNewTask() specifiedType1:[".$taskclassify."]  spcfdmac1:[".$spcfdmac."].");
                    $logger->debug(TASKMANAGER . " - addNewTask() specifiedType3:[".$sql."].");

                    $qr = $dsql->ExecQuery($sql);
					if (!$qr) {
						$result = array('result' => false, 'msg' => '操作失败');
						$logger->error(TASKMANAGER . " - addNewTask() sqlerror:" . $sql . "-" . $dsql->GetError());
					}
				}	
               
            }
        }
    }
    echo json_encode($result);
}

function insertTask($taskparams)
{
    global $logger, $dsql;
}

/**
 *
 * 验证是否原创
 * @param $ids
 * @param $mids  URL数组
 * @param $sourceid
 */
function checkOrig($ids, $mids, $sourceid)
{
    global $logger, $dsql;
    $result = true;
    if (!empty($ids)) {
        $whids = "'" . implode("','", $ids) . "'";
        $checksql = "select id,mid,is_repost from weibo_new where id in({$whids}) and sourceid={$sourceid}";
        $qr = $dsql->ExecQuery($checksql);
        if (!$qr) {
            $logger->error(TASKMANAGER . " - " . __FUNCTION__ . " sqlerror: {$checksql}  " . $dsql->GetError());
            return array("result" => false, "msg" => "操作异常");
        } else {
            $existsarr = array();
            $err = array();
            while ($rs = $dsql->GetArray($qr)) {
                if ($rs['is_repost'] == 1) {
                    $err['result'] = false;
                    $err['id'][] = $rs['id'];
                } else {
                    $existsarr[] = $rs['id'];
                }
            }
            if (!empty($err)) {
                $err['msg'] = "有非原创的ID：" . implode($err['id']);
                return $err;
                //echo json_encode($err);
                //exit;
            }
            foreach ($ids as $k => $v) {
                if (in_array($v, $existsarr)) {
                    unset($ids[$k]);
                }
            }

        }
    }
    if (!empty($mids)) {
        $realmids = array();
        $existsarr = array();
        foreach ($mids as $key => $value) {
            $realmid = weiboUrl2mid($value, $sourceid);
            if (!empty($realmid)) {
                $realmids[] = $realmid;
                $checksql = "select id,mid,is_repost from weibo_new where mid ='{$realmid}' and sourceid={$sourceid}";
                $qr = $dsql->ExecQuery($checksql);
                if (!$qr) {
                    $logger->error(TASKMANAGER . " - " . __FUNCTION__ . " sqlerror: {$checksql}  " . $dsql->GetError());
                    return array("result" => false, "msg" => "操作异常");
                } else {
                    $err = array();
                    while ($rs = $dsql->GetArray($qr)) {
                        if ($rs['is_repost'] == 1) {
                            $err['result'] = false;
                            $err['url'][] = $value;
                        } else {
                            $existsarr[] = $realmid;
                        }
                    }
                    if (!empty($err)) {
                        $err['msg'] = "有非原创的URL：" . $value;
                        return $err;
                        //echo json_encode($err);
                        //exit;
                    }
                }
            }
        }
        foreach ($realmids as $k => $v) {
            if (in_array($v, $existsarr)) {
                unset($realmids[$k]);
            }
        }
    }
    //需要按id抓取
    if (!empty($ids)) {
        foreach ($ids as $k => $v) {
            $r = getweibo("id", $v, $sourceid);
            if ($r['result'] == false) {
                $r['msg'] = $v . " " . $r['msg'];
                return $r;
                //echo json_encode($r);
                //exit;
            }
        }
    }
    if (!empty($realmids)) {
        foreach ($realmids as $k => $v) {
            $r = getweibo("mid", $v, $sourceid);
            if ($r['result'] == false) {
                foreach ($mids as $mk => $kv) {
                    $_mid = weiboUrl2mid($kv, $sourceid);
                    if ($_mid == $v) {
                        $r['msg'] = $_mid . " " . $r['msg'];
                        break;
                    }
                }
                return $r;
                //echo json_encode($r);
                //exit;
            }
        }
    }
    return $result;
}

function getweibo($weiboidtype, $weiboid, $source)
{
    $params = array();
    $params["weiboidtype"] = $weiboidtype;
    $params["weiboid"] = $weiboid;
    $params["source"] = $source;
    $timeline = "getweibo";
    return getSinaInfo($params, $timeline);
}

function getuser($id, $screen_name, $source)
{
    $params = array();
    $params["id"] = $id;
    $params["screen_name"] = $screen_name;
    $params["source"] = $source;
    $timeline = "getuser";
    return getSinaInfo($params, $timeline);
}

function changeTaskStatus()
{
    global $logger, $dsql;
    $poststatus = $_POST["taskstatus"];
    $r = array('result' => true, 'msg' => '');
    $sqlcheck = "select task,taskstatus,taskparams,timeout from task where id = " . $_POST["id"];
    $qrcheck = $dsql->ExecQuery($sqlcheck);
    if (!$qrcheck) {
        $r = array('result' => false, 'msg' => '检查任务状态失败');
        $logger->error(TASKMANAGER . " - changeTaskStatus() sqlerror:" . $dsql->GetError());
    } else {
        $rscheck = $dsql->GetArray($qrcheck);
        if (!$rscheck) {
            $r = array('result' => false, 'msg' => '任务未找到');
        } else {
            $oldtaskstatus = $rscheck['taskstatus'];
            $taskparams = json_decode($rscheck['taskparams'], true);
            $statusflag = true;
            $withdraw = '';
            switch ($poststatus) {
                case "-1"://停止时，判断当前状态必须为正常：1
                    if ($oldtaskstatus != 1) {
                        $statusflag = false;
                    }
                    break;
                case "0"://启动时，判断当前状态必须为停止状态
                    if ($oldtaskstatus == 1 && $rscheck['timeout'] != NULL) {
                        if ($rscheck['task'] == TASK_REPOST_TREND || $rscheck['task'] == TASK_COMMENTS) {
                            if (isset($taskparams['scene']) && isset($taskparams['scene']['stat'])) {
                                $taskparams['scene']['historystat'] = $taskparams['scene']['stat'];
                            }
                            $withdraw = ", timeout=NULL, machine=NULL, endtime=NULL";
                        } else {
                            if (isset($taskparams['scene'])) {
                                unset($taskparams['scene']);
                            }
                            $withdraw = ", timeout=NULL, machine=NULL, datastatus=0, starttime=0, endtime=NULL";
                        }
                    } else if ($oldtaskstatus < 2 || $oldtaskstatus == 4) {
                        $statusflag = false;//非停止状态，（4为排队状态）
                    }
                    break;
                case "1"://取消停止时，判断当前状态必须为等待停止: -1
                    if ($oldtaskstatus == 6) {
                        if (isset($taskparams['scene']['veriimage'])) {
                            unlink($taskparams['scene']['veriimage']);
                            unset($taskparams['scene']['veriimage']);
                        }
                    } else if ($oldtaskstatus != -1) {
                        $statusflag = false;
                    }
                    break;
                case "2"://取消启动，取消排队时，任务状态必须为等待启动状态 或 排队状态
                    if ($oldtaskstatus != 0 && $oldtaskstatus != 4) {
                        $statusflag = false;
                    }
                    break;
                case "-71"://继续执行挂起的任务
                    $poststatus = 0;
                    $taskparams['scene']['hangaction'] = 1;
                    break;
                case "-72"://重试挂起的任务
                    $poststatus = 0;
                    $taskparams['scene']['hangaction'] = 2;
                    break;
            }
            if ($statusflag == false) {
                $r = array('result' => false, 'msg' => '任务状态错误，请刷新页面');
            }
        }

        if ($r['result'] == true) {
            $dsql->safeCheck = false;//不检查sql，sql的内容是由jsonencode出来的
            $sql = "update task set taskstatus=" . $poststatus . $withdraw . ", taskparams='" . jsonEncode4DB($taskparams) . "' where id =" . $_POST["id"];
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $r = array('result' => false, 'msg' => '操作失败');
                $logger->error(TASKMANAGER . " - changeTaskStatus() sqlerror:" . $dsql->GetError());
            } else {
                if ($oldtaskstatus == 4) {//当前任务为排队，则删除排队表
                    $sqlunqueue = "delete from taskqueue where taskid=" . $_POST["id"];
                    $qrunqueue = $dsql->ExecQuery($sqlunqueue);
                    if (!$qrunqueue) {
                        $logger->error(TASKMANAGER . " - " . __FUNCTION__ . " delete taskqueue error {$sqlunqueue}:" . $dsql->GetError());
                    }
                }
            }
        }
    }
    echo json_encode($r);
}

function deleteTask()
{
    global $logger, $dsql;
    $r = array('result' => true, 'msg' => '');
    $delids = $_POST["ids"];
    foreach($delids as $key=>$id){
        $sqlcheck = "select * from task where id = " . $id;
        $qrcheck = $dsql->ExecQuery($sqlcheck);
        if (!$qrcheck) {
            $r = array('result' => false, 'msg' => '检查任务状态失败');
            $logger->error(TASKMANAGER . " - deleteTask() sqlerror:" . $dsql->GetError());
            break;
        } else {
            $rscheck = $dsql->GetArray($qrcheck);
            if (!$rscheck) {
                $r = array('result' => false, 'msg' => '任务未找到');
                break;
            } else {
                $sql = "delete from task where id = " . $id;
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $r = array('result' => false, 'msg' => '操作失败');
                    $logger->error(TASKMANAGER . " - deleteTask() sqlerror:" . $dsql->GetError());
                    break;
                } else {
                    if ($rscheck['taskstatus'] == 4) {
                        $sqlunqueue = "delete from taskqueue where taskid=" . $id;
                        $qrunqueue = $dsql->ExecQuery($sqlunqueue);
                        if (!$qrunqueue) {
                            $r = array('result' => false, 'msg' => 'delete taskqueue error');
                            $logger->error(TASKMANAGER . " - " . __FUNCTION__ . " delete taskqueue error {$sqlunqueue}:" . $dsql->GetError());
                            break;
                        }
                    }
                    if ($rscheck['taskstatus'] != 0) {
                        $his_starttime = empty($rscheck['starttime']) ? 0 : $rscheck['starttime'];
                        $his_endtime = empty($rscheck['endtime']) ? 0 : $rscheck['endtime'];
                        $conflictdelay = empty($rscheck['conflictdelay']) ? "NULL" : $rscheck['conflictdelay'];
                        $tenantid = empty($rscheck['tenantid']) ? "NULL" : $rscheck['tenantid'];
                        $userid = empty($rscheck['userid']) ? "NULL" : $rscheck['userid'];
                        $taskpagestyletype = empty($rscheck['taskpagestyletype']) ? "NULL" : $rscheck['taskpagestyletype'];
                        $taskparams = jsonEncode4DB(json_decode($rscheck['taskparams'], true));
                        $sqlhis = "insert into taskhistory values(" . $rscheck['id'] . "," . $rscheck['tasktype'] . "," . $taskpagestyletype . "," . $rscheck['task'] . ", " . $rscheck['tasklevel'] . "," . $rscheck['local'] . "," . $rscheck['remote'] . "," . $rscheck['activatetime'] . "," . $conflictdelay . "," . $his_starttime . "," . $his_endtime . "," . $rscheck['datastatus'] . ", '" . $taskparams . "','" . $rscheck['remarks'] . "','" . $rscheck['machine'] . "', " . $tenantid . ", " . $userid . ")";
                        $qrhis = $dsql->ExecQuery($sqlhis);
                        if (!$qrhis) {
                            $r = array('result' => false, 'msg' => 'insert taskhistory error');
                            $logger->error(TASKMANAGER . " - " . __FUNCTION__ . " insert taskhistory error {$sqlhis}:" . $dsql->GetError());
                            break;
                        }
                    }
                    if ($rscheck['task'] == TASK_REPOST_TREND && $rscheck['remote'] == 1 && $rscheck['local'] == 0) {
                        delRepostInfo($id);
                    }
                }
            }
        }
    }
    echo json_encode($r);
    exit;
}

function getTasksbyTasktype()
{
    global $logger;
    $result = array();
    $tasktype = isset($_GET['tasktype']) ? $_GET['tasktype'] : NULL;
    $result = gettasksbytype($tasktype);
    echo json_encode($result);
    exit;
}

function getAllTaskPageStyleType()
{
    global $logger;
    $result = array();
    $result = taskpagestyletype();
    echo json_encode($result);
    exit;
}

/*
function getSearchEngineList(){
	$se = array();
	$se = getsearchengine();
	echo json_encode($se);
	exit;
}
function getsearchsitelist(){
	$se = array();
	$se = getsearchsite();
	echo json_encode($se);
	exit;
}
 */
//获取帐号来源
function getAccountSourceList()
{
    global $dsql, $logger;
    $result = array();
    $sql = "select id,name from source";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(TASKMANAGER . " - getSourceList() sqlerror:" . $dsql->GetError());
    } else {
        while ($r = $dsql->GetArray($qr)) {
            $result[] = $r;
        }
    }
    echo json_encode($result);
}

//获取爬虫配置
function getSpiderConfigList()
{
    global $dsql, $logger;
    $result = getSpiderConfig(); //common.php
    echo json_encode($result);
    exit;
}

//获取抓取帐号
function getSpiderAccount()
{
    global $dsql, $logger;
    $result = array();
    $sql = "select id, username, sourceid from " . DATABASE_SPIDERACCOUNT . "";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(TASKMANAGER . " - getSpiderAccount() sqlerror:" . $dsql->GetError());
    } else {
        while ($r = $dsql->GetArray($qr)) {
            $result[] = $r;
        }
    }
    echo json_encode($result);
}

//获取数据主机
function getDataHostAliasList()
{
    global $dsql, $logger;
    $result = array();
    $sql = "select id, alias from datahost";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(TASKMANAGER . " - getDataHostAliasList() sqlerror:" . $dsql->GetError());
    } else {
        while ($r = $dsql->GetArray($qr)) {
            $result[] = $r;
        }
    }
    echo json_encode($result);
}

function getTaskHistory()
{
    global $dsql, $logger;
    $iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
    $iDisplayLength = $_GET['iDisplayLength'];//每页条数
    $iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
    $iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
    $p_task = empty($_GET['task']) ? '' : ' and task=' . $_GET['task'];
    $p_machine = empty($_GET['machine']) ? '' : " and machine='" . $_GET['machine'] . "'";
    $p_tasklevel = empty($_GET['tasklevel']) ? '' : ' and tasklevel=' . $_GET['tasklevel'];
    $p_taskpagestyletype = empty($_GET['taskpagestyletype']) ? '' : ' and taskpagestyletype=' . $_GET['taskpagestyletype'];
    $p_local = '';
    if (isset($_GET['local']) && $_GET['local'] != '') {
        $p_local = ' and local=' . $_GET['local'];
    }
    $p_remote = '';
    if (isset($_GET['remote']) && $_GET['remote'] != '') {
        $p_remote = ' and remote=' . $_GET['remote'];
    }
    $p_idstart = empty($_GET['id_start']) ? -1 : $_GET['id_start'];
    $p_idend = empty($_GET['id_end']) ? -1 : $_GET['id_end'];
    $p_id = '';
    if ($p_idstart != -1 && $p_idend != -1) {
        $p_id = ' and id >= ' . $p_idstart . ' and id <= ' . $p_idend . '';
    } else if ($p_idstart != -1) {
        $p_id = ' and id >= ' . $p_idstart . '';
    } else if ($p_idend != -1) {
        $p_id = ' and id <= ' . $p_idend . '';
    }
    $wh = " where 1=1 {$p_task} {$p_machine} {$p_tasklevel} {$p_taskpagestyletype} {$p_local} {$p_remote} {$p_id}";
    $p_orderby = empty($_GET['orderby']) ? 'id desc' : $_GET['orderby'];
    $order = " order by {$p_orderby}";
    $result = new DatatableResult();
    $result->aaData = array();
    $sqlcount = "select count(0) as cnt from taskhistory {$wh}";
    $qr = $dsql->ExecQuery($sqlcount);
    if (!$qr) {
        $logger->error(TASKMANAGER . " - getTaskHistory() sqlerror:" . $sqlcount . " - " . $dsql->GetError());
    } else {
        $rcnt = $dsql->GetArray($qr);
        $result->sEcho = $_GET['sEcho'];
        $result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
        $result->iTotalRecords = $rcnt['cnt'];
        $result->iTotalDisplayRecords = $rcnt['cnt'];
        if ($rcnt['cnt'] > 0) {
            $sql = "select * from taskhistory {$wh} {$order} limit {$iDisplayStart},{$iDisplayLength}";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $logger->error(TASKMANAGER . " - getTaskHistory() sqlerror:" . $sql . " - " . $dsql->GetError());
            } else {
                while ($r = $dsql->GetArray($qr)) {
                    $result->aaData[] = $r;
                }
            }
        }
    }
    echo json_encode($result);
}

//获取所有IP
function getIPList()
{
    global $dsql, $logger;
    $iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
    $iDisplayLength = $_GET['iDisplayLength'];//每页条数
    $iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
    $iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
    $result = new DatatableResult();
    $sqlcount = "select count(0) as cnt from taskip";
    $sql = "select a.*,b.taskcount,b.usedcount,b.status as usestatus,b.changetime from taskip a
     left join resourcestatus b on a.ip = b.resource limit {$iDisplayStart},{$iDisplayLength}";
    $qr = $dsql->ExecQuery($sqlcount);
    if (!$qr) {
        $logger->error(TASKMANAGER . " - getIPList() sqlerror:" . $sqlcount . " - " . $dsql->GetError());
    } else {
        $rcnt = $dsql->GetArray($qr);
        $result->sEcho = $_GET['sEcho'];
        $result->sEcho = empty($result->sEcho) ? 0 : $result->sEcho;
        $result->iTotalRecords = $rcnt['cnt'];
        $result->iTotalDisplayRecords = $rcnt['cnt'];
        if ($rcnt['cnt'] > 0) {
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $logger->error(TASKMANAGER . " - getIPList() sqlerror:" . $sql . " - " . $dsql->GetError());
            } else {
                while ($r = $dsql->GetObject($qr)) {
                    $result->aaData[] = $r;
                }
            }
        }
    }
    echo json_encode($result);
}

function getAlldataCount()
{
    global $dsql, $logger;
    $sourceid = $_POST['sourceid'];
    $whsql = empty($sourceid) ? "" : " where sourceid = " . $sourceid;
    $r = array('result' => true, 'msg' => '');
    $sql = "select count(0) as cnt from weibo_new {$whsql}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $r = array('result' => false, 'msg' => '');
        $logger->error(TASKMANAGER . " - " . __FUNCTION__ . " sqlerror:" . $sql . " - " . $dsql->GetError());
    } else {
        $rs = $dsql->GetArray($qr);
        $r = array('result' => true, 'data' => $rs['cnt']);
    }
    echo json_encode($r);
}
/*查询符合条件的微博条数*/
function gettotalcount(){
	global $logger;
    $params = array();
    $sourceid = get_sourceid_from_url("s.weibo.com");
    $params['source'] = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;

    if (!empty($_POST['keywords'])) {
        // $keywords = explode("\r\n", trim($_POST['keywords']));
		// 获取两个关键词的总条数，由于ajax和form传值不同，所以接收处理用/n，来作为分隔符处理字符串
        $keywords = explode("\n", trim($_POST['keywords']));
        $params['keywords'] = $keywords;
    }
    if (!empty($_POST['inputtype'])) {
        $params['inputtype'] = $_POST['inputtype'];
    }
    $params['filterdup'] = isset($_POST['filterdup']) ? $_POST['filterdup'] : null;
    if (!empty($_POST['addeduser'])) {
        $adduser = split(",", $_POST['addeduser']);
        $params["users"] = $adduser;
        //当时用户昵称时需要查询出对应的id , 解决用户修改昵称后,不能通过昵称抓取微博的问题
        if (!empty($_POST['inputtype']) && $_POST['inputtype'] == 'screen_name') {
            $userids = array();
            $noexistuser = array();
            foreach ($adduser as $ai => $aitem) { //循环添加到昵称去新浪查询对应的id
                $sourceid = get_sourceid_from_url("s.weibo.com");
                $cu_r = getuser(NULL, $aitem, $sourceid);
                if ($cu_r['result'] && !empty($cu_r['user'])) {
                    $user = $cu_r['user'];
                    $userids[] = $user["id"]; //把用户id存在数组中
                } else if (!empty($cu_r['nores'])) {
                    $result['result'] = false;
                    $result['nores'] = true;
                    $result['msg'] = $cu_r['msg'];
                    break;
                } else {
                    if (isset($cu_r['error_code']) && $cu_r['error_code'] == ERROR_USER_NOT_EXIST) {
                        $noexistuser[] = $aitem;
                    }
                }
            }
            if (!$result['result']) { //无资源
                $logger->debug(__FILE__ . __LINE__ . " nores: " . var_export($result, true));
            }
            if (!empty($noexistuser)) {
                $logger->debug(__FILE__ . __LINE__ . " " . $cu_r['msg'] . " : " . var_export($noexistuser, true));
                $result['result'] = false;
                $result['msg'] = "查询新浪,不存在的昵称";
                $result["noexistuser"] = $noexistuser;
            }
            $params["userids"] = $userids;
        }
        else{
            $params["userids"] = $adduser;
        }
    }
    if (!empty($_POST['starttime'])) {
        $params['starttime'] = strtotime($_POST['starttime']);
    }
    if (!empty($_POST['sendtime'])) {
        $params['endtime'] = strtotime($_POST['sendtime']);
    }
    isset($_POST['request_type']) ? $request_type = "GetMoreWbnum" : $request_type = "weibo_limited";
    $logger->info("the requesttype is:".var_export($request_type,true));
    $result = getSinaInfo($params, $request_type);
    $logger->info(__FILE__.__LINE__."the result is:".var_export($result,true));
    echo json_encode($result);
    exit;
}
function getAllMigrateCount()
{
    global $logger;
    $result = array('result' => true, 'count' => 0, 'msg' => '');
    if (!empty($_POST['srchost'])) {
        $hostid = (int)$_POST['srchost'];
        $host = getHostById($hostid);
        if (empty($host)) {
            $logger->error(__FUNCTION__ . "数据主机{$hostid}不存在");
            $result['result'] = false;
            $result['msg'] = "源数据主机不存在";
            echo json_encode($result);
            exit;
        }
        $solr = trim($host['solrstore']);
    } else {
        $solr = trim(SOLR_STORE);
    }
    if ($solr[strlen($solr) - 1] != '/') {
        $solr .= '/';
    }
    if (!empty($_POST['cond_in_customquery'])) {
        $q = trim($_POST['cond_in_customquery']);
    }
    else{
        $params = (object)array();
        if (!empty($_POST['source'])) {
            $params->source = $_POST['source'];
        }
        $params->cond_deleted = 1;
        if (!empty($_POST['cond_lt_created'])) {
            $params->cond_lt_created = strtotime($_POST['cond_lt_created']);
        }
        if (!empty($_POST['cond_ge_created'])) {
            $params->cond_ge_created = strtotime($_POST['cond_ge_created']);
        }
        if (!empty($_POST['cond_ex_text'])) {
            $extext = explode("\r\n", trim($_POST['cond_ex_text']));
            $params->cond_ex_text = $extext;
        }
        if (!empty($_POST['cond_in_text'])) {
            $intext = explode("\r\n", trim($_POST['cond_in_text']));
            $params->cond_in_text = $intext;
        }
        if (!empty($_POST['cond_ex_name'])) {
            $exname = explode("\r\n", trim($_POST['cond_ex_name']));
            $params->cond_ex_name = $exname;
        }
        if (!empty($_POST['cond_in_name'])) {
            $inname = explode("\r\n", trim($_POST['cond_in_name']));
            $params->cond_in_name = $inname;
        }
        $q = getQuery4Migrate($params);
    }
    $url = $solr . "select";
    $facet = "";
    $count = solr_select_conds("", $q, 0, 0, "", "", $facet, $url);
    if ($count === false) {
        $logger->error(__FUNCTION__ . "查询solr出错");
        $result['result'] = false;
        $result['msg'] = "查询solr出错";
    } else {
        $result['count'] = $count;
    }
    echo json_encode($result);
}

function checkScreenName()
{
    global $arrsdata, $logger;
    $users = array();
    $existusers = array();
    $tmpuser = array();
    $tmpuser = $arrsdata["uids"];
    $qr = solr_select_conds(array('users_screen_name', 'users_id'), array('users_screen_name' => $tmpuser, 'users_sourceid' => $arrsdata["sourceid"]));
    $r = array();
    if ($qr === false) {
        $r['flag'] = 2;
        $r["error"] = "solr select failed";
        $logger->debug(__FUNCTION__ . "solr select failed");
    } else {
        foreach ($arrsdata["uids"] as $ui => $uitem) {
            $exist = false;
            foreach ($qr as $result) {
                if (isset($result['users_screen_name']) && isset($result['users_id'])) {
                    if ($result['users_screen_name'] == $uitem) {
                        $exist = true;
                        $existusers[] = $result['users_id'];
                        break;
                    }
                }
            }
            if (!$exist) {
                $users[] = $uitem;
            }
        }
        if (count($users) > 0) {
            $r['flag'] = 0;
            $r['user'] = $users;
        } else {
            $r['flag'] = 1;
            $r['user'] = $existusers;
        }
    }
    echo json_encode($r);
    exit;
}

function submitVeriCode()
{
    global $logger, $dsql;
    $r = array('result' => true, 'msg' => '');
    $sql = "select taskstatus,taskparams from task where id = " . $_POST["id"];
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $r['result'] = false;
        $r['msg'] = '提交验证码失败';
        $logger->error(TASKMANAGER . " - submitVeriCode() sqlerror:" . $dsql->GetError());
    } else {
        $rs = $dsql->GetArray($qr);
        if (!$rs) {
            $r['result'] = false;
            $r['msg'] = '任务未找到';
        } else {
            if ($rs['taskstatus'] == 6) {
                $taskparams = json_decode($rs['taskparams'], true);
                $taskparams['scene']['vericode'] = $_POST["code"];
                if (isset($taskparams['scene']['veriimage'])) {
                    unlink($taskparams['scene']['veriimage']);
                    unset($taskparams['scene']['veriimage']);
                }
                $dsql->safeCheck = false;//不检查sql，sql的内容是由jsonencode出来的
                $sql = "update task set taskstatus = 1, taskparams = '" . jsonEncode4DB($taskparams) . "' where id = " . $_POST["id"] . " and taskstatus = 6";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $r['result'] = false;
                    $r['msg'] = '提交验证码失败';
                    $logger->error(TASKMANAGER . " - submitVeriCode() sqlerror:" . $dsql->GetError());
                } else {
                    if ($dsql->GetAffectedRows() == 0) {
                        $r['result'] = false;
                        $r['msg'] = '任务状态错误，请刷新页面';
                    }
                }
            } else {
                $r['result'] = false;
                $r['msg'] = '任务状态错误，请刷新页面';
            }
        }
    }
    echo json_encode($r);
}
/*
function getNextSpiderAccount(){
	global $logger,$dsql;
	$r = array('result'=>true,'msg'=>'');
	$sql = "select id,username,password,inuse,sourceid from spideraccount where inuse = (select min(inuse) from spideraccount) limit 1";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$r['result'] = false;
		$r['msg'] = '查询账户失败';
		$logger->error(TASKMANAGER." - getNextSpiderAccount() sqlerror:".$dsql->GetError());
	}
	else{
		$res = $dsql->GetArray($qr);
		if(!$res){
			$r['result'] = false;
			$r['msg'] = '未找到可用帐号';
		}
		else{
			//需要判断任务状态,
			//考虑把用户放到taskparam中
			$MAX_INT_UNSIGNED = 4294967290; //mysql中 int(10) 无符号的最大值 4294967295
			if($res['inuse']+1 >= $MAX_INT_UNSIGNED){
				$upsql = "update spideraccount set inuse = 0 where sourceid = ".$res['sourceid']."";
				$upqr = $dsql->ExecQuery($upsql);
				if(!$upqr){
					$r['result'] = false;
					$r['msg'] = 'inuse 清0失败!';
					$logger->debug(__FILE__.__LINE__." sqlerror ".$dsql->GetError());
				}
			}
			$upsqli = "update spideraccount set inuse = inuse+1 where id = ".$res['id']."";
			$upqri = $dsql->ExecQuery($upsqli);
			if(!$upqri){
				$r['result'] = false;
				$r['msg'] = 'inuse 清0失败!';
				$logger->debug(__FILE__.__LINE__." sqlerror ".$dsql->GetError());
			}
		}
	}
	echo json_encode($r);
}
 */
