<?php
define("SELF", basename(__FILE__));

//定义请求任务类型:为通用任务,以后可以去掉默认就是通用类型的任务
define("TASKTYPE_COMMON", "remotecommtask_commit");
define("TASKTYPE_SUPPLY_ID", "supply_ori_id");

include_once('includes.php');
include_once('commonFun.php');
include_once('taskcontroller.php');
include_once("authorization.class.php");
include_once('weibo_config.php');
include_once('weibo_class.php');
include_once('saetv2.ex.class.php');
include_once('SegementHelper.php');

include_once('PHPExcel/IOFactory.php');
ini_set('include_path', get_include_path() . '/lib');
include_once('OpenSDK/Tencent/Weibo.php');

session_start();
set_time_limit(0);//植入微博时，可能会超时
initLogger(LOGNAME_FLUSHDAT);
//$chkr = Authorization::checkUserSession();
//$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);

//global $task;
$task = new Task(null);
$logger->info(__FILE__ . __LINE__ . " flush data to solr ...");

//分词计划
$dictionaryPlan = '';
$result = array("result" => true, "msg" => '操作成功');
$isSegmented = isset($_GET['isSegmented']) ? $_GET['isSegmented'] : false;

$nedSupplyDocs = array();
$indirect_guid_query_conds = array();

/**
 *
 *缓存的数据，批量入库：可以支持已经分词的数据和没有分词的数据
 *
 */
if (!empty($_GET['type']) && $_GET['type'] == TASKTYPE_COMMON) {
    //远程导入文章详情 updateTaskFull($taskobj);
    global $dictionaryPlan, $task, $nedSupplyDocs;
    $errorDocIds = array();
    $nedSupplyDocs = array();
    try {
        $logger->info(__FILE__ . __LINE__ . " 处理通用类型的任务，type：[" . TASKTYPE_COMMON . "].");

        if (!isset($HTTP_RAW_POST_DATA) || empty($HTTP_RAW_POST_DATA)) {
            $reqestDataStr = file_get_contents('php://input');
            if (!isset($reqestDataStr) || empty($reqestDataStr)) {
                throw new Exception("request data is null for remotecommtask_commit in cacheweibo.php.");
//                $result['errorCacheIds'] = $errorDocIds;
//                $result["nedSupplyDocs"] = $nedSupplyDocs;
//                $logger->info(__FILE__ . __LINE__ . "----- 提交的数据为空! -----");
//                echo json_encode($result);
//                exit;
            } else {
                $requsePostData = &$reqestDataStr;
            }
        } else {
            $requsePostData = $HTTP_RAW_POST_DATA;
        }


        $insertDataStartTime = time();

        if (empty($requsePostData)) {
            $logger->debug(__FILE__ . __LINE__ . " flush data to solr failed. 通过:[HTTP_RAW_POST_DATA] 获取到的数据为空. data:[" . $requsePostData . "].");
            $requestData = file_get_contents("php://input");
            if (!isset($requestData) || empty($requestData) || is_null($requestData)) {
                $logger->debug(__FILE__ . __LINE__ . " flush data to solr failed. 通过:[file_get_contents] 获取到的数据为空. data:[" . $requestData . "].");
                $requestData = $_POST['fieldname'];
                if (!isset($requestData) || empty($requestData) || is_null($requestData)) {
                    $logger->error(__FILE__ . __LINE__ . " flush data to solr failed. 提交的数据为空, 原始数据:[" . $requestData . "].");
                    setErrorMsg(1, "提交数据为空!");
                } else {
                    $logger->debug(__FILE__ . __LINE__ . " flush data to solr -+--通过:[fieldname] 获取到的数据成功. data:[" . $requestData . "].");
                }
            } else {
                $requsePostData = &$requestData;
                $logger->debug(__FILE__ . __LINE__ . " flush data to solr -+--通过:[file_get_contents] 获取到的数据成功. data:[" . $requestData . "].");
            }
        }

        $logger->info(__FILE__ . __LINE__ . " flush data to solr -+--读取数据成功. data length:[" . strlen($requsePostData) . "].");
        $requsePostData = json_decode($requsePostData, true);

        if (empty($requsePostData) || !isset($requsePostData)) {
            $logger->error(__FILE__ . " " . __LINE__ . " flush data to solr -+--读取数据成功--+-数据为空,all data:[" . var_export($requsePostData, true) . "].");
            throw new Exception("flush data to solr error -+--读取数据成功--+-数据为空!");
        }

        $logger->info(__FILE__ . __LINE__ . " flush data to solr -+--读取数据成功. 本次需要处理:[" . count($requsePostData) . "] 个任务的数据. 数据是否分词:[" . $isSegmented ? "已经分词" : "没有分词" . "].");

        $timeStatisticObj = array(SOLR_SELECT_TIME_KEY => 0, SOLR_UPDATE_TIME_KEY => 0, SOLR_DELET_TIME_KEY => 0, SOLR_INSERT_TIME_KEY => 0, DB_SELECT_TIME_KEY => 0, DB_INSERT_TIME_KEY => 0, DB_DELET_TIME_KEY => 0, DB_UPDATE_TIME_KEY => 0);
        $timeStatisticObj[DATA_HANDLE] = 0;
        $timeStatisticObj[FETCH_USER] = 0;
        $timeStatisticObj[HANDLE_USER_SUM] = 0;
        $timeStatisticObj[INSER_DELETED_WEIBO] = 0;
        $timeStatisticObj[HANDLE_ORG_DOC] = 0;
        $timeStatisticObj[HANDLE_CMT_DOC] = 0;
        $timeStatisticObj[HANDLE_UPDT_DOC] = 0;
        $timeStatisticObj[HANDLE_DEL_DOC] = 0;
        $timeStatisticObj[HANDLE_SPL_GUID] = 0;
        $timeStatisticObj[SOLR_NLP_TIME_KEY] = 0;

        $logger->info(__FILE__ . __LINE__ . " flush data to solr 需要处理文章:[" . count($requsePostData) . "] 个!");

        //提交上来的数据是多条记录 每一条记录 存储的是一个任务抓取的数据
        foreach ($requsePostData as $taskDataIdx => $taskData) {
            $logger->debug(__FILE__ . __LINE__ . " flush data to solr -+--数据提交--+-处理第:[{$taskDataIdx}]]个任务的数据...");
            //
            global $task;

            initTaskObj($task);
            $logger->debug(__FILE__ . __LINE__ . " flush data to solr -+--数据提交--+-初始化taskObj完成: " . var_export($task, true));

            //设置本次刷新数据的分词计划
            $dictionaryPlan = $taskData["dictPlan"];

            //默认情况下 从结果中去data作为所有需要结果数据来处理
            $allData = $taskData["datas"];
            $allcount = count($allData);
            $sourceid = isset($taskData["sourceid"]) ? $taskData["sourceid"] : null;
            $taskId = $taskData['taskId'];
            $cacheDataId = $taskData["id4cahe"];
            $task->id = $taskId;
            $logger->debug(__FILE__ . __LINE__ . " flush data to solr -+--数据提交--+-处理第:[{$cacheDataId}]]个数据--+-初始化taskObj成功: " . var_export($task, true));
//        docStr:
//        {
//            taskId:0001,
//            sourceid:xxx,
//            //当前任务配置的分词计划，在从cache提交数据时候，为了提高性能可以将同一个分词计划的数据一次性提交
//	        dictPlan:[[1,2,3,4],[4,5,2,1]],
//
//	        taskParam:{
//            //这里值存储当前任务的参数 对于子任务的参数这里不进行存储
//            },
//
//	        data:[] //爬虫抓取到的数据
//	    }
            // 判断 提交数最大长度
            $strData = json_encode($allData);
            /*if (strlen($strData) > (1024 * 512)) {
                $errorDocIds[] = $cacheDataId;
                $logger->error(__FILE__ . " " . __LINE__ . " flush data to solr--+-处理第:[{$cacheDataId}]]个数据--+-数据长度超出限制. 数据长度:[" . strlen($strData) . "].");
                unset($strData);
                continue;
            }*/
            $logger->info(__FILE__ . __LINE__ . " flush data to solr -+--数据提交--+-处理第:[{$cacheDataId}]]个数据--+本次需要入库的数据:[" . $allcount . "]条. 使用分词方案:[" . var_export($dictionaryPlan, true) . "].");
            //允许数据不全 将抓取到的数据插入到数据库中
            //$result = array("result" => true, "msg" => "");
            $r = addweibo($sourceid, $allData, 0, 'show_status', false, true, true, $timeStatisticObj, $isSegmented);
            if (!$r["result"]) {
                $logger->error(__FILE__ . " " . __LINE__ . " flush data to solr--+-处理第:[{$cacheDataId}]]个数据--+-数据提交异常,错误信息:[" . var_export($r, true) . ". cacheDataId:[" . $cacheDataId . "].");
                //throw new Exception("为任务taskid:[" . $taskId . "]插入数据异常,ErrMsg:[" . $r["msg"] . "].");
                $errorDocIds[] = $cacheDataId;
            } else {
                $logger->info(__FILE__ . " " . __LINE__ . " " . " flush data to solr--+-处理第:[{$cacheDataId}]]个数据--+-数据提交成功! taskid:[" . $taskId . "]!");
            }
        }

        $logger->info(__FILE__ . __LINE__ . " flush data to solr -+--数据提交--+-所有任务的数据提交完成!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " flush data to solr--+-数据提交完成，所有任务数:[" . count($requsePostData) . "] 耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");

        //输出所有的时间统计信息
        $logger->info(SELF . " " . " +++++++++++++时间统计[sum]:\n 方法总耗时:[" . $timeStatisticObj[DATA_HANDLE] . "]微妙 提取用户:[" . $timeStatisticObj[FETCH_USER] .
            "]微妙 处理用户总用时:[" . $timeStatisticObj[HANDLE_USER_SUM] . "]微妙 插入删除状态的微博用时:[" . $timeStatisticObj[INSER_DELETED_WEIBO] . "]微妙 处理原创用时:[" . $timeStatisticObj[HANDLE_ORG_DOC] . "]微妙
             处理转发评论用时:[" . $timeStatisticObj[HANDLE_CMT_DOC] . "]微妙 处理更新用时:[" . $timeStatisticObj[HANDLE_UPDT_DOC] . "]微妙 处理删除用时:[" . $timeStatisticObj[HANDLE_DEL_DOC] . "]微妙
             补充guid用时:[" . $timeStatisticObj[HANDLE_SPL_GUID] . "]微妙 ");

        $logger->info(SELF . " " . " +++++++++++++时间统计[solr|db]:\n 分词总耗时:[" . $timeStatisticObj[SOLR_NLP_TIME_KEY] . "]微妙 solr查询:[" . $timeStatisticObj[SOLR_SELECT_TIME_KEY] .
            "]微妙 solr更新:[" . $timeStatisticObj[SOLR_UPDATE_TIME_KEY] . "]微妙 solr插入:[" . $timeStatisticObj[SOLR_INSERT_TIME_KEY] . "]微妙 solr删除:[" . $timeStatisticObj[SOLR_DELET_TIME_KEY] . "]微妙
             DB查询:[" . $timeStatisticObj[DB_SELECT_TIME_KEY] . "]微妙 DB更新:[" . $timeStatisticObj[DB_UPDATE_TIME_KEY] . "]微妙 DB插入:[" . $timeStatisticObj[DB_INSERT_TIME_KEY] . "]微妙
             DB删除:[" . $timeStatisticObj[DB_DELET_TIME_KEY] . "]微妙 ");
        $result['timeStatisticInfo'] = $timeStatisticObj;
    } catch (Exception $e) {
        $logger->error(SELF . " flush data to solr--+-数据提交异常:[" . $e->getMessage() . "]. \n\t\t" . exceptionHandler($e));
        $result['result'] = false;
        $result['msg'] = " " . $e->getMessage();
    }
    if ($result['result'] == false) {
        $result['errorcode'] = -1;
        $result['error'] = $result['msg'];
        $logger->error($result['msg']);
        unset($result['result']);
        unset($result['msg']);
    }
    $result['errorCacheIds'] = $errorDocIds;
    $result["nedSupplyDocs"] = $nedSupplyDocs;
    echo json_encode($result);
    exit;
}//
/**
 *
 *已经分词的文章入库时候，需要在最后提交(flush)之后，分批将需要补充original_guid的文章进行补充
 *
 */
else if (!empty($_GET['type']) && $_GET['type'] == TASKTYPE_SUPPLY_ID) {

    $logger->info(__FILE__ . __LINE__ . " 处理通用类型的任务，type：[" . TASKTYPE_SUPPLY_ID . "].");
    $supplyInfos = $HTTP_RAW_POST_DATA;
    if (empty($supplyInfos)) {
        $logger->warn(__FILE__ . __LINE__ . " 处理通用类型的任务，type：[" . TASKTYPE_SUPPLY_ID . "] 数据为空!");
        echo json_encode($result);
        exit;
    }
    $supplyInfos = json_decode($supplyInfos, true);
    $partialdata = $supplyInfos["ispartialdata"];

    $timeStatisticObj = null;
    $errorDocCollect = array();//收集错误的文档
    global $indirect_guid_query_conds;
    if (isset($supplyInfos["docs"]) && !empty($supplyInfos["docs"])) {
        //$logger->info(__FILE__ . __LINE__ . " supply original doc id,request data:[" . var_export($supplyInfos["docs"], true) . "].");
        $indirect_guid_query_conds = $supplyInfos["docs"];
        $logger->info(__FILE__ . __LINE__ . " set indirect_guid_query_conds:" . var_export($indirect_guid_query_conds, true));
        if (supplyIndirectGuids($partialdata, false, $timeStatisticObj, true, $errorDocCollect) === false) {
            $logger->error("补充guid失败");
            //$result = false;
            $result['result'] = false;
            $result['msg'] = '插入文章时，补充文章的doc/父/原创guid失败';
        }
    } else {
        $logger->info(__FILE__ . __LINE__ . " supply original doc id,doc is empty!");
    }
    $result["ErrorDocs"] = $errorDocCollect;
    echo json_encode($result);
    exit;
} else {
    $logger->error(__FILE__ . __LINE__ . " flush doc to solr error. Invalid taskType:[" . $_GET['type'] . "].");
    $result['errorcode'] = -1;
    $result['error'] = "Invalid taskType:[" . $_GET['type'] . "].";
    echo json_encode($result);
    exit;
}

function initTaskObj(&$task)
{
    $task->taskparams->root->runTimeParam->scene->state = SCENE_NORMAL;
    $task->tasklevel = 2;
    //判读当前的数据是否需要提交到solr中
    $commitData = empty($_GET['commit']) ? false : ($_GET['commit'] == "true" ? true : false);
    $task->taskparams->root->taskPro->iscommit = $commitData;
    $task->task = TASK_COMMON;
    $task->taskparams->root->taskPro->addUser = true;
}

function exceptionHandler($exception)
{
    //global $logger;
    // these are our templates
    $traceline = "#%s %s(%s): %s(%s)";
    $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

    // 打印异常调用堆栈信息
    $trace = $exception->getTrace();
    foreach ($trace as $key => $stackPoint) {
        //返回异常类似，异常描述信息
        $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
    }

    // 格式化异常信息
    $result = array();
    foreach ($trace as $key => $stackPoint) {
        $result[] = sprintf(
            $traceline,
            $key,
            $stackPoint['file'],
            $stackPoint['line'],
            $stackPoint['function'],
            implode(', ', $stackPoint['args'])
        );
    }
    // trace always ends with {main}
    //$result[] = '#' . ++$key . ' {main}';
    $result[] = '# {main}';

    // write tracelines into main template
    $msg = sprintf(
        $msg,
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        implode("\n", $result),
        $exception->getFile(),
        $exception->getLine()
    );
    //$logger->info(__FILE__ . __LINE__ . " excepion info :\n" . $msg);
    return $msg;
}


