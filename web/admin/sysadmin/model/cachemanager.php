<?php
define("SELF", basename(__FILE__));

//定义请求任务类型:为通用任务,以后可以去掉默认就是通用类型的任务
define("TASKTYPE_COMMON", "remotecommtask_commit");
define("RESULT_KEY", "result");
define("MESSEGE_KEY", "msg");

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
initLogger(LOGNAME_FLUSHDAT);

//$chkr = Authorization::checkUserSession();
$logger->info(__FILE__ . __LINE__ . " 缓存管理...");

//分词计划
$result = array("result" => true, "msg" => '操作成功');


// **************************************** 页面上选择缓存[提交] 刷新当前缓存数据到solr内存  *************************************//
if (!empty($_GET['type']) && $_GET['type'] == 'commit') {
    //缓存数据提交
    try {
        $logger->info(__FILE__ . __LINE__ . "缓存管理--+-数据全量提交切换...");
//        if ($chkr != CHECKSESSION_SUCCESS) {
//            $logger->error("checksession failed,未登录或登陆超时!");
//            setErrorMsg($chkr, "未登录或登陆超时!");
//        }
        $insertDataStartTime = time();

        $reqURL = SOLR_URL_CACHE_FLUSH . "&cacheServerName=" . $cacheNameCurPort;
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-数据全量提交切换--+-数据提交url:[" . $reqURL . ".");
        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-数据全量提交切换--+-数据提交异常,错误信息:[" . var_export($reqResult, true) . ".");
//            throw new Exception("缓存管理--+-缓存数据提交--+-数据提交异常,ErrMsg:[" . $reqResult . "].");
            $errMsg = "缓存管理--+-数据全量提交切换--+-提交异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//            throw new Exception("缓存管理--+-数据全量提交切换 失败.");
            $errorMsg = "数据全量提交切换失败! " . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-数据全量提交切换--+-数据提成功!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-数据全量提交切换完成,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-数据全量提交切换--+-数据提交异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
}
// ****************************************  刷新当前缓存数据到solr内存 -end *************************************//
//

//
// ****************************************  缓存刷新后的磁盘同步  *************************************//
else if (!empty($_GET['type']) && $_GET['type'] == 'no_doc_update_commit') {
    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-no_doc_update_commit...");
    $insertDataStartTime = time();
    $reqURL = SOLR_URL_UPDATE_NO_DATA_COMMIT . "&commit=true";
    $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-no_doc_update_commit--+-数据提交url:[" . $reqURL . ".");
    $reqData = "no caceh data commit";
    //允许数据不全 将抓取到的数据插入到数据库中
    $reqResult = send_solr($reqData, $reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-no_doc_update_commit--+-数据提交异常,错误信息:[" . var_export($reqResult, true) . ".");
//        throw new Exception("缓存管理--+-no_doc_update_commit,ErrMsg:[" . $reqResult . "].");
        $errMsg = "缓存管理--+-no_doc_update_commit--+-提交异常";
        setErrorWhenServerFalseWithMsg($result, $errMsg);
    }

//    if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//        throw new Exception("缓存管理--+-no_doc_update_commit 失败.");
//    }

    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-no_doc_update_commit--+-数据提成功! RetrueMsg: " . var_export($reqResult, true));
    $insertDataEndTime = time();
    $logger->info(SELF . " " . " 缓存管理--+-no_doc_update_commit,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    echo json_encode($result);
    exit;
}
// ****************************************  缓存刷新后的磁盘同步 -end  *************************************//
//

// ****************************************  cache wrapper  *************************************//
else if (!empty($_GET['type']) && $_GET['type'] == 'getAllCacheWrapper') {
    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getAllCacheWrapper...");

    $iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
    $iDisplayLength = $_GET['iDisplayLength'];//每页条数
    $iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
    $iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;

    $insertDataStartTime = time();
    $reqURL = SOLR_URL_CACHE_SELECT_WRAPPER . "&cacheServerName=" . $cacheNameCurPort . "&start=" . $iDisplayStart . "&limit=" . $iDisplayLength;
    $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-获取封装器信息--+-URL:[" . $reqURL . "].");
//    $reqData = null;
    //允许数据不全 将抓取到的数据插入到数据库中
    $reqResult = send_solr_get($reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-获取封装器信息--+-数据获取异常,错误信息:[" . var_export($reqResult, true) . ".");
//        throw new Exception("缓存管理--+-获取封装器信息异常,ErrMsg:[" . $reqResult . "].");
        $errMsg = "缓存管理--+-获取封装器信息--+-异常";
        setErrorWhenServerFalseWithMsg($result, $errMsg);
    }
    if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//        throw new Exception("缓存管理--+-获取封装器信息失败.");
        $errorMsg = "缓存管理--+-获取封装器信息失败." . $reqResult["msg"];
        writteErrorMsgNoCode($result, $errorMsg);
    }

    $recordsCount = getAllWrapperCount();

    $allWrapperInfo = array();

    $allWrapperStr = $reqResult['datas'];
    $allWrapperInfo = json_decode($allWrapperStr, true);
    $allWrapperInfo['aaData'] = $allWrapperInfo;
    $allWrapperInfo['sEcho'] = (empty($_GET['sEcho']) ? 0 : $_GET['sEcho']);
    $allWrapperInfo['iTotalRecords'] = $recordsCount;
    $allWrapperInfo['iTotalDisplayRecords'] = $recordsCount;

    $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-获取封装器信息--+-数据获取成功! AllCacheWrpperInfo: " . var_export($allWrapperInfo, true));
    $insertDataEndTime = time();
    $logger->info(SELF . " " . " 缓存管理--+-获取封装器信息,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    echo json_encode($allWrapperInfo);
    exit;
}
// **************************************** cache wrapper  --end *************************************//

//
// ****************************************  inner cache *************************************//
else if (!empty($_GET['type']) && $_GET['type'] == 'getAllInnerCacheInfos') {
    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getAllInnerCacheInfos...");

    $iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
    $iDisplayLength = $_GET['iDisplayLength'];//每页条数
    $wrapperId = $_GET['id'];//当前wrapperId
    if (!isset($wrapperId)) {
    }

    $iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
    $iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;

    $insertDataStartTime = time();
    $reqURL = SOLR_URL_CACHE_SELECT_INNER_INFOS . "&cacheServerName=" . $cacheNameCurPort . "&start=" . $iDisplayStart . "&limit=" . $iDisplayLength . "&id=" . $wrapperId;
    $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllInnerCacheInfos--+-URL:[" . $reqURL . "].");
//    $reqData = null;
    //允许数据不全 将抓取到的数据插入到数据库中
    $reqResult = send_solr_get($reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllInnerCacheInfos--+-数据获取异常,错误信息:[" . var_export($reqResult, true) . ".");
//        throw new Exception("缓存管理--+-getAllInnerCacheInfos,ErrMsg:[" . $reqResult . "].");
        $errMsg = "缓存管理--+-getAllInnerCacheInfos异常";
        setErrorWhenServerFalseWithMsg($result, $errMsg);
    }
    if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//        throw new Exception("缓存管理--+-getAllInnerCacheInfos 失败.");
        $errorMsg = "缓存管理--+-getAllInnerCacheInfos 失败." . $reqResult["msg"];
        writteErrorMsgNoCode($result, $errorMsg);
    }

    $recordsCount = getAllInnerCacheCount($wrapperId);

    $allInnerCacheInfo = array();
    $allInnerCacheInfoStr = $reqResult['datas'];
    $allInnerCacheArray = json_decode($allInnerCacheInfoStr, true);
    $allInnerCacheInfo['aaData'] = $allInnerCacheArray;

    $allInnerCacheInfo['sEcho'] = (empty($_GET['sEcho']) ? 0 : $_GET['sEcho']);
    $allInnerCacheInfo['iTotalRecords'] = $recordsCount;
    $allInnerCacheInfo['iTotalDisplayRecords'] = $recordsCount;

    $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getAllInnerCacheInfos--+-数据获取成功! allInnerCacheInfo: " . var_export($allInnerCacheInfo, true));
    $insertDataEndTime = time();
    $logger->info(SELF . " " . " 缓存管理--+-getAllInnerCacheInfos,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    echo json_encode($allInnerCacheInfo);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == 'getDatasFromInnerCache') {
    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getDatasFromInnerCache...");

    $iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
    $iDisplayLength = $_GET['iDisplayLength'];//每页条数
    $iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
    $iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
    $wrapperId = $_GET['wrapperId'];//当前wrapperId
    $cacheId = $_GET['cacheId'];//当前cacheId

    if (!isset($wrapperId)) {
    }
    if (!isset($cacheId)) {
    }

    $insertDataStartTime = time();
    $reqURL = SOLR_URL_CACHE_SELECT_INNER_GETDATAS . "&cacheServerName=" . $cacheNameCurPort . "&start=" . $iDisplayStart . "&limit=" . $iDisplayLength . "&wrapperId=" . $wrapperId . "&cacheId=" . $cacheId;
    $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getDatasFromInnerCache--+-URL:[" . $reqURL . ".");
//    $reqData = null;
    //允许数据不全 将抓取到的数据插入到数据库中
    $reqResult = send_solr_get($reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getDatasFromInnerCache--+-数据获取异常,错误信息:[" . var_export($reqResult, true) . ".");
//        throw new Exception("缓存管理--+-getDatasFromInnerCache,ErrMsg:[" . $reqResult . "].");
        $errMsg = "缓存管理--+-getDatasFromInnerCache--+-";
        setErrorWhenServerFalseWithMsg($result, $errMsg);
    }
    if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//        throw new Exception("缓存管理--+-getDatasFromInnerCache 失败.");
        $errorMsg = "缓存管理--+-getDatasFromInnerCache 失败." . $reqResult["msg"];
        writteErrorMsgNoCode($result, $errorMsg);
    }

    $dataCount = (empty($_GET['dataCount']) ? 0 : $_GET['dataCount']);

    $allInnerDatas = array();
    $allDatasStr = $reqResult['datas'];
    $allInnerDatasArray = json_decode($allDatasStr, true);
    $allInnerDatas['aaData'] = $allInnerDatasArray;

    $allInnerDatas['sEcho'] = (empty($_GET['sEcho']) ? 0 : $_GET['sEcho']);
    $allInnerDatas['iTotalRecords'] = $dataCount;
    $allInnerDatas['iTotalDisplayRecords'] = $dataCount;

    $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getDatasFromInnerCache--+-数据获取成功! inner cache data: " . var_export($allInnerDatas, true));
    $insertDataEndTime = time();
    $logger->info(SELF . " " . " 缓存管理--+-getDatasFromInnerCache,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    echo json_encode($allInnerDatas);
    exit;
}

//else if (!empty($_GET['type']) && $_GET['type'] == 'getInnerCacheCount') {
//}
// ****************************************  inner cache *************************************//
//
//
// **************************************************  数据删除操作  **************************************************//
else if (!empty($_GET['type']) && $_GET['type'] == 'delWrapperData') {
    try {
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-delWrapperData...");

        $wrpperId = $_GET['wrapperId'];//从多少条开始显示
        $innerCacheId = (isset($_GET['innerCacheId'])) ? $_GET['innerCacheId'] : "";//每页条数
        $dataIds = (isset($_GET['dataIds'])) ? $_GET['dataIds'] : ""; //每页条数
        $insertDataStartTime = time();

        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-delWrapperData--+-wrapperId:" . $wrpperId);
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-delWrapperData--+-innerCacheId:" . $innerCacheId);
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-delWrapperData--+-dataIds:" . $dataIds);


        $wrapperIds = json_decode($wrpperId);
        $innerCacheIds = json_decode($innerCacheId);
        if (is_array($wrapperIds)) {
            //删除多条数据
            $reqURL = SOLR_URL_CACHE_DELETE_WRPPER . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrpperId;
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-删除多个wrapper--+-URL:[" . $reqURL . ".");

        } else if (is_array($innerCacheIds)) {
            //删除多个innerCache
            $reqURL = SOLR_URL_CACHE_DELETE_INNERCACHE . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrpperId . "&innerCacheId=" . $innerCacheId;
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-删除多个innerCache--+-URL:[" . $reqURL . ".");
        } else {
            //删除多个wrapper
            $reqURL = SOLR_URL_CACHE_DELETE_DATA . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrpperId . "&innerCacheId=" . $innerCacheId . "&dataIds=" . $dataIds;
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-删除多条记录--+-URL:[" . $reqURL . ".");

        }

//        $reqData = null;
        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-delWrapperData--+-数据获取异常,错误信息:[" . var_export($reqResult, true) . ".");
            $errMsg = "缓存管理--+-delWrapperData--+-删除数据异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//            throw new Exception("缓存管理--+-delWrapperData失败.");
            $errorMsg = "删除数据失败! " . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-清除缓存数据成功!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-清除缓存数据,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-清除缓存数据--+-清除缓存数据异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
}
//
// **************************************************  数据删除操作 end  **************************************************//

//
// **************************************************  数据提交操作(人工事后提交)  **************************************************//
else if (!empty($_GET['type']) && $_GET['type'] == 'flushAndCommitData') {
    try {
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-人工数据提交...");

        $wrpperId = $_GET['wrapperId'];//从多少条开始显示
        $innerCacheId = isset($_GET['innerCacheId']) ? $_GET['innerCacheId'] : "";//每页条数
        $dataIds = isset($_GET['dataIds']) ? $_GET['dataIds'] : "";//每页条数
        $insertDataStartTime = time();

        $cmtDataSourceType = isset($_GET['commitDataSourceType']) ? $_GET['commitDataSourceType'] : ""; //从备份文件提交 或者从缓存数据直接提交
        if (empty($cmtDataSourceType)) {
            $cmtDataSourceType = "cache";
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-人工数据提交--+-提交数据来源:[缓存数据].");
        } else if ($cmtDataSourceType == "backFile") {
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-人工数据提交--+-提交数据来源:[备份文件]");

            //备份文件提交数据时候 只能按照整个 wrapper 提交
            if (!empty($innerCacheId) || !empty($dataIds)) {
                $errorMsg = "cannot commitData by type:[backFile] for innerCache or innerCacheData.";
                writteErrorMsgNoCode($result, $errorMsg);
            }
        }

        $wrapperIds = json_decode($wrpperId);
        $innerCacheIds = json_decode($innerCacheId);
        if (is_array($wrapperIds)) {
            //删除多条数据
            $reqURL = SOLR_URL_CACHE_MANUAL_COMMIT_WRAPPER . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrpperId . "&commitDataSourceType=" . $cmtDataSourceType;
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-人工数据提交--+-URL:[" . $reqURL . ".");
        } else if (is_array($innerCacheIds)) {
            //删除多个innerCache
            $reqURL = SOLR_URL_CACHE_MANUAL_COMMIT_INNERCACHE . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrpperId . "&innerCacheId=" . $innerCacheId . "&commitDataSourceType=" . $cmtDataSourceType;
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-人工数据提交--+-URL:[" . $reqURL . ".");
        } else {
            //删除多个wrapper
            $reqURL = SOLR_URL_CACHE_MANUAL_COMMIT_DATA . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrpperId . "&innerCacheId=" . $innerCacheId . "&dataIds=" . $dataIds . "&commitDataSourceType=" . $cmtDataSourceType;
            $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-人工数据提交--+-URL:[" . $reqURL . ".");
        }

        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-人工数据提交异常,错误信息:[" . var_export($reqResult, true) . ".");
            $errMsg = "缓存管理--+-人工数据提交异常--+-提交异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }

        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
            $errorMsg = "人工数据提交失败! " . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }
        $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-人工数据提交成功!");

        $returnInfos = $reqResult["commitInfos"];
        $result['msg'] = json_encode($returnInfos);
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-人工数据提交成功,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒! commitInfos: " . $returnInfos);
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-人工数据提交异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
}
//
// **************************************************  数据提交操作(人工事后提交) end  **************************************************//

// **************************************************  缓存切换   **************************************************//
//
else if (!empty($_GET['type']) && $_GET['type'] == 'switchCache') {
    try {
        $insertDataStartTime = time();
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-switchCache...");
        //
        $reqURL = SOLR_URL_CACHE_MANUAL_SWITCHCACHE . "&cacheServerName=" . $cacheNameCurPort;
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-switchCache--+-URL:[" . $reqURL . ".");

        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-switchCache,错误信息:[" . var_export($reqResult, true) . ".");
//            throw new Exception("缓存管理--+-switchCache,ErrMsg:[" . $reqResult . "].");
            $errMsg = "缓存管理--+-switchCach异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//            throw new Exception("缓存管理--+-switchCache 失败.");
            $errorMsg = "缓存管理--+-switchCach 失败." . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }
        $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-switchCache成功!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-switchCache异常,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-switchCache异常--+-switchCache异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
}
//
// **************************************************  缓存切换  end **************************************************//


// **************************************************  参数查询与修改  **************************************************//
//
else if (!empty($_GET['type']) && $_GET['type'] == 'getCacheInsServeMapping') {
    try {
        $insertDataStartTime = time();
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getCacheInsServeMapping...");
        //
        $reqURL = SOLR_URL_CACHE_PARAMS_CACHE_INS_SERVER;
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getCacheInsServeMapping--+-URL:[" . $reqURL . ".");

        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getCacheInsServeMapping,错误信息:[" . var_export($reqResult, true) . ".");
//            throw new Exception("缓存管理--+-switchCache,ErrMsg:[" . $reqResult . "].");
            $errMsg = "缓存管理--+-getCacheInsServeMapping 异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//            throw new Exception("缓存管理--+-switchCache 失败.");
            $errorMsg = "缓存管理--+-getCacheInsServeMapping 失败." . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $allCacheInsInfoStr = $reqResult['datas'];
        $allCacheInsArray = json_decode($allCacheInsInfoStr, true);
        $result['aaData'] = $allCacheInsArray;


        $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getCacheInsServeMapping 成功!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-getCacheInsServeMapping 成功,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-getCacheInsServeMapping异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == 'getAllCacheInsCfg') {
    try {
        $insertDataStartTime = time();
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getAllCacheInsCfg...");
        //
        $reqURL = SOLR_URL_CACHE_PARAMS_GET_ALLCACHEINSCFG;
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllCacheInsCfg--+-URL:[" . $reqURL . ".");

        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllCacheInsCfg,错误信息:[" . var_export($reqResult, true) . ".");
            $errMsg = "缓存管理--+-getAllCacheInsCfg 异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
            $errorMsg = "缓存管理--+-getAllCacheInsCfg 失败." . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $allCacheCfgStr = $reqResult['datas'];
        $allCacheCfgArray = json_decode($allCacheCfgStr, true);
        $result['aaData'] = $allCacheCfgArray;

        $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getAllCacheInsCfg 成功!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-getAllCacheInsCfg 成功,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-getAllCacheInsCfg 异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == 'getAllGlbParams') {
    try {
        $insertDataStartTime = time();
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getAllGlbParams...");
        //
        $reqURL = SOLR_URL_CACHE_PARAMS_GET_ALLGLBPARAMS;
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllGlbParams--+-URL:[" . $reqURL . ".");

        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllGlbParams,错误信息:[" . var_export($reqResult, true) . ".");
            $errMsg = "缓存管理--+-getAllGlbParams 异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
            $errorMsg = "缓存管理--+-getAllGlbParams 失败." . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $allGParamStr = $reqResult['datas'];
        $allGParamArray = json_decode($allGParamStr, true);
        $result['aaData'] = $allGParamArray;

        $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getAllGlbParams 成功!");
        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-getAllGlbParams 成功,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-getAllGlbParams 异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
} else if (!empty($_GET['type']) && $_GET['type'] == 'getCmtCfg') {
    try {
        $insertDataStartTime = time();
        $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getCmtCfg...");
        //
        $cacheInsName = isset($_GET['cacheInsName']) ? $_GET['cacheInsName'] : "";
        if (empty($cacheInsName)) {
            $errorMsg = "缓存管理--+-getCmtCfg 失败. 参数:[cacheInsName]为空!";
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $reqURL = SOLR_URL_CACHE_PARAMS_GET_CMTCFG . "&cacheInsName=" . $cacheInsName;
        $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getCmtCfg--+-URL:[" . $reqURL . ".");

        //允许数据不全 将抓取到的数据插入到数据库中
        $reqResult = send_solr_get($reqURL);

        if ($reqResult === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getCmtCfg,错误信息:[" . var_export($reqResult, true) . ".");
            $errMsg = "缓存管理--+-getCmtCfg 异常";
            setErrorWhenServerFalseWithMsg($result, $errMsg);
        }
        if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
            $errorMsg = "缓存管理--+-getCmtCfg 失败." . $reqResult["msg"];
            writteErrorMsgNoCode($result, $errorMsg);
        }

        $cmtCfgStr = $reqResult['datas'];
        $cmtCfgArray = json_decode($cmtCfgStr, true);
        $result['aaData'] = $cmtCfgArray;

        $insertDataEndTime = time();
        $logger->info(SELF . " " . " 缓存管理--+-getCmtCfg 成功,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    } catch (Exception $e) {
        $logger->error(SELF . " 缓存管理--+-getCmtCfg 异常:[" . $e->getMessage() . "].");
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
    echo json_encode($result);
    exit;
}
//
// **************************************************  参数查询与修改 -end  **************************************************//

else {
    $logger->error(__FILE__ . __LINE__ . " 缓存管理 error. Invalid type:[" . $_GET['type'] . "].");
    $result['errorcode'] = -1;
    $result['error'] = "Invalid type:[" . $_GET['type'] . "].";
    echo json_encode($result);
    exit;
}


function writteErrorMsgNoCode(&$result, &$errorMsg)
{
    $errorCode = -1;
    writteErrorMsg($result, $errorCode, $errorMsg);
}

function writteErrorMsg(&$result, &$errorCode, &$errorMsg)
{
    $result['result'] = false;
    $result['errorcode'] = $errorCode;
    $result['error'] = $errorMsg;
    $result['msg'] = "操作失败!";
    echo json_encode($result);
    exit;
}

function setErrorWhenServerFalse(&$result)
{
    $errorMsg = "操作异常:[服务端返回false!]";
    setErrorWhenServerFalseWithMsg($result, $errorMsg);
}

function setErrorWhenServerFalseWithMsg(&$result, &$errorMsg)
{
    $errorMsg = $errorMsg . " 操作异常:[服务端返回false!]";
    writteErrorMsgNoCode($result, $errorMsg);
}


function getAllWrapperCount()
{
    global $logger, $cacheNameCurPort;
    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getAllCacheWrapperCount...");
    $insertDataStartTime = time();
    $reqURL = SOLR_URL_CACHE_SELECT_WRAPPER_COUNT . "&cacheServerName=" . $cacheNameCurPort;
    $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllCacheWrapperCount--+-URL:[" . $reqURL . ".");
//    $reqData = null;
    //允许数据不全 将抓取到的数据插入到数据库中
    $reqResult = send_solr_get($reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getAllCacheWrapperCount--+-数据获取异常,错误信息:[" . var_export($reqResult, true) . ".");
//        throw new Exception("缓存管理--+-getAllCacheWrapperCount,ErrMsg:[" . $reqResult . "].");
        $errMsg = "缓存管理--+-getAllCacheWrapperCount异常";
        setErrorWhenServerFalseWithMsg($result, $errMsg);
    }
    if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//        throw new Exception("缓存管理--+-getAllCacheWrapperCount 失败.");
        $errorMsg = "缓存管理--+-getAllCacheWrapperCount 失败." . $reqResult["msg"];
        writteErrorMsgNoCode($result, $errorMsg);
    }

    $allWrapperInfo = $reqResult['datas'];
    $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getAllCacheWrapperCount--+-数据获取成功! count: " . var_export($allWrapperInfo, true));
    $insertDataEndTime = time();
    $logger->info(SELF . " " . " 缓存管理--+-getAllCacheWrapperCount,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
    return $allWrapperInfo;
}


function getAllInnerCacheCount($wrapperId)
{
    global $logger, $cacheNameCurPort;
    $logger->info(__FILE__ . __LINE__ . " 缓存管理--+-getInnerCacheCount...");
    $insertDataStartTime = time();
    $reqURL = SOLR_URL_CACHE_SELECT_INNER_COUNT . "&cacheServerName=" . $cacheNameCurPort . "&wrapperId=" . $wrapperId;
    $logger->debug(__FILE__ . " " . __LINE__ . " 缓存管理--+-getInnerCacheCount--+-URL:[" . $reqURL . ".");
//    $reqData = null;
    //允许数据不全 将抓取到的数据插入到数据库中
    $reqResult = send_solr_get($reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " 缓存管理--+-getInnerCacheCount--+-数据获取异常,错误信息:[" . var_export($reqResult, true) . ".");
//        throw new Exception("缓存管理--+-getInnerCacheCount,ErrMsg:[" . $reqResult . "].");
        $errMsg = "缓存管理--+-getInnerCacheCount异常";
        setErrorWhenServerFalseWithMsg($result, $errMsg);
    }
    if (!isset($reqResult[RESULT_KEY]) || $reqResult[RESULT_KEY] !== true) {
//        throw new Exception("缓存管理--+-getInnerCacheCount 失败.");
        $errorMsg = "缓存管理--+-getInnerCacheCount 失败." . $reqResult["msg"];
        writteErrorMsgNoCode($result, $errorMsg);
    }

    $innerCacheCount = $reqResult['datas'];
    $logger->debug(__FILE__ . __LINE__ . " 缓存管理--+-getInnerCacheCount--+-数据获取成功! count: " . var_export($innerCacheCount, true));
    $insertDataEndTime = time();
    $logger->info(SELF . " " . " 缓存管理--+-getInnerCacheCount,耗时:[" . ($insertDataEndTime - $insertDataStartTime) . "] 秒!");
//    echo json_encode($innerCacheCount);
//    exit;
    return $innerCacheCount;
}