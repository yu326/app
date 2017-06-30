<?php
define("SELF", basename(__FILE__));


include_once('includes.php');
include_once('commonFun_v2.php');
include_once('authorization_v2.class.php');

define("HTTP_SUF", "http://");
//define("SERVER_HOST", "192.168.0.30");
define("SERVER_HOST", "demo2.3i.inter3i.com");

define("SERVER_PORT", "7010");
// ********************** 根据 用户名 获取token 的服务器地址 **************************//
define("GET_TOKEN_PATH", "/model/checkuser.php?type=gettoken");
// ********************** 根据 用户名 获取token 的服务器地址 **************************//
define("GET_MODE_DATA_PATH", "/model/requestdata.php");


define("MAX_RETRIVE_NUM", 1000);

// 获取数据类型 : 微博
define("RETRIVE_TYPE_WEIBO_ORIGINAL", "wb");

define("USER_NAME_KEY", "userName");
define("PASSWORD_KEY", "pswd");

define("ERROR_USER_NAME_NULL", "10001003");
define("ERROR_USER_MEDIA_TYPE_NULL", "10001004");
define("ERROR_USER_MEDIA_TYPE_INVALID", "10001005");
define("ERROR_USER_INNER_EXCPTION", "10001006");
define("ERROR_PASSWORD_NULL", "10001007");

define("ERROR_PARAM_NULL", "10001008");
define("ERROR_INNER_EXCEPTION", "10001009");
define("ERROR_MODE_CONFIG", "10001010");

define("ERROR_TIME_PARAM", "10001011");

define("RETRIVE_TYPE_NEW", "new_data");
define("RETRIVE_TYPE_APPEND", "append_data");


ini_set('include_path', get_include_path() . '/lib');
initLogger(LOGNAME_EXT_RETRIVE_DATA);//初始化日志配置

$logger;

//服务器端地址
//define("HTTP_SUF", "http://");
//define("SERVER_HOST", "192.168.0.30");
//define("SERVER_PORT", "7010");
//define("REQ_PATH", "/model/checkuser.php?type=gettoken");
//**** test *****//
//$userName = 'demo3';
//$passWorld = 'f2f780f54ad732b89a91746461eedf78';
//**** test *****//

$logger->info(SELF . " - 微博接口数据提取 ...");

$reqDataStri = $GLOBALS['HTTP_RAW_POST_DATA'];
$logger->info(SELF . " - 请求数据:" . $reqDataStri);

if (empty($reqDataStri)) {
    $logger->error(SELF . " - retrive weibo data excption--+-request data null");
    setErrorMsg(ERROR_PARAM_NULL, "request data null");
}
$requstDataObj = json_decode($reqDataStri, true);

$taskType = isset($requstDataObj["task_type"]) ? $requstDataObj["task_type"] : NULL;
if (!isset($taskType) || empty($taskType)) {
    $logger->error(SELF . " - retrive weibo data excption--+-error:task_type null:[" . $taskType . "].");
    setErrorMsg(ERROR_PARAM_NULL, "param:[task_type] null");
}

switch ($taskType) {
    case RETRIVE_TYPE_WEIBO_ORIGINAL:
        try {
            getWeiboOri($taskType);
        } catch (Exception $e) {
            $logger->error(SELF . " - retrive data excption. errorMsg:[" . $e->getMessage() . "].");
            setErrorMsg(ERROR_USER_INNER_EXCPTION, "retrive data exception.");
        }
        break;
    default:
        $logger->error(SELF . " --+-retrive data faild. retriveType invalid:[" . $reqType . "].");
        setErrorMsg(ERROR_USER_MEDIA_TYPE_INVALID, "retriveType invalid:[" . $reqType . "].");
}

function getWeiboOri($taskType)
{
    global $logger, $requstDataObj;
    $userName = isset($requstDataObj[USER_NAME_KEY]) ? $requstDataObj[USER_NAME_KEY] : NULL;
    if (!isset($userName) || empty($userName)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:user name null");
        setErrorMsg(ERROR_USER_NAME_NULL, "user name null");
    }
    $passWorld = isset($requstDataObj[PASSWORD_KEY]) ? $requstDataObj[PASSWORD_KEY] : NULL;
    if (!isset($passWorld) || empty($passWorld)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:passWorld null");
        setErrorMsg(ERROR_PASSWORD_NULL, "passWorld null");
    }

    $taskReqId = isset($requstDataObj["task_id"]) ? $requstDataObj["task_id"] : NULL;
    if (!isset($taskReqId) || empty($taskReqId)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:task_id null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[task_id] null");
    }

    $taskYear = isset($requstDataObj["task_year"]) ? $requstDataObj["task_year"] : NULL;
    if (!isset($taskYear) || empty($taskYear)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:task_year null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[task_year] null");
    }

    $taskMonth = isset($requstDataObj["task_month"]) ? $requstDataObj["task_month"] : NULL;
    if (!isset($taskMonth) || empty($taskMonth)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:task_month null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[task_month] null");
    }

    $taskDay = isset($requstDataObj["task_day"]) ? $requstDataObj["task_day"] : NULL;
    if (!isset($taskDay) || empty($taskDay)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:task_day null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[task_day] null");
    }

    $taskDay = isset($requstDataObj["task_day"]) ? $requstDataObj["task_day"] : NULL;
    if (!isset($taskDay) || empty($taskDay)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:task_day null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[task_day] null");
    }

    $taskHour = isset($requstDataObj["task_hour"]) ? $requstDataObj["task_hour"] : NULL;
    if (!isset($taskHour) || empty($taskHour)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:task_hour null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[task_hour] null");
    }

    //每次指定小时偏移 根据这个值计算开始时间
    $taskStartHour = isset($requstDataObj["hour_offset"]) ? $requstDataObj["hour_offset"] : NULL;

    $startTimeStr = isset($requstDataObj["start_time"]) ? $requstDataObj["start_time"] : NULL;

//    $websiteName = isset($requstDataObj["website_name"]) ? $requstDataObj["website_name"] : NULL;
//    if (!isset($websiteName) || empty($websiteName)) {
//        $logger->error(SELF . " - retrive weibo data excption--+-error:website_name null");
//        setErrorMsg(ERROR_PARAM_NULL, "Param:[website_name] null");
//    }

    $mediaName = isset($requstDataObj["media_name"]) ? $requstDataObj["media_name"] : NULL;
    if (!isset($mediaName) || empty($mediaName)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:media_name null");
        setErrorMsg(ERROR_USER_MEDIA_TYPE_NULL, "param:[media_name] null");
    }

    $mediaType = isset($requstDataObj["media_type"]) ? $requstDataObj["media_type"] : NULL;
    if (!isset($mediaType) || empty($mediaType)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:media_type null");
        setErrorMsg(ERROR_USER_MEDIA_TYPE_NULL, "param:[media_type] null");
    }


    $retriveType = isset($requstDataObj["retrive_type"]) ? $requstDataObj["retrive_type"] : NULL;
    if (!isset($retriveType) || empty($retriveType)) {
        $retriveType = RETRIVE_TYPE_NEW;
    }

    ////**** test 本地 *****//
    //    $userName = 'demo3';
    //    $passWorld = 'f2f780f54ad732b89a91746461eedf78';
    //**** test *****//


    $offsetCur = isset($requstDataObj["dataStart"]) ? $requstDataObj["dataStart"] : NULL;
    if (!isset($offsetCur)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:dataStart null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[dataStart] null");
    }

    $rows = isset($requstDataObj["dataRows"]) ? $requstDataObj["dataRows"] : NULL;
    if (!isset($rows) || empty($rows)) {
        $logger->error(SELF . " - retrive weibo data excption--+-error:dataRows null");
        setErrorMsg(ERROR_PARAM_NULL, "param:[dataRows] null");
    }

    if ($rows > MAX_RETRIVE_NUM) {
        $rows = MAX_RETRIVE_NUM;
    }

    //根据当前用户 获取用户取数的配置信息
    $curUserConfig = getRetriveConfig4User($userName);

    $logger->info(SELF . " - 获取当前用户配置的host:[" . $curUserConfig['hostName'] . "]");
    $GLOBALS['hostName'] = $curUserConfig['hostName'];
    $logger->info(SELF . " - 获取数据库配置成功,数据库:[" . DATABASE_NAME . "].");

    //向服务器发送请求 获取 token
//    $token = getTokenFromServer($userName, $passWorld);
    $token = gettokenBy($userName, $passWorld, $curUserConfig['childHostDomain']);
    $logger->info(SELF . " - 获取当前用户配置的token成功:[" . $token . "]");

    $childDomainName = $curUserConfig['childHostDomain'];

    $logger->debug(SELF . " - 获取当前用户配置信息...");
    $user = Authorization::getUserFromToken4($token, LOCALTYPE_TENANT, $childDomainName);
    $logger->info(SELF . " - 获取当前用户配置信息成功! user:[" . var_export($user, true) . "].");

    $dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME, FALSE);
    $elementId = $curUserConfig['elementId'];
    $dataJson = getDataJsonByUser($elementId, $user);

    if (!isset($dataJson)) {
        $logger->error(SELF . " - 从获取数据获取dataJson失败. data:[" . var_export($dataJson, true) . "].");
    }

    $logger->info(SELF . " - 从获取数据获取dataJson成功:[" . var_export($dataJson, true) . "].");

    $instanceId = $curUserConfig['instanceId'];
    $elementId = $curUserConfig['elementId'];
    $hasformjson = $curUserConfig['hasformjson'] ? 1 : 0;
    //是否要返回原创信息
    $returnoriginal = $curUserConfig['returnoriginal'] ? 1 : 0;

    //根据参数设置 $dataJson 的相应参数的值
    if (!isset($dataJson["filtervalue"]) || empty($dataJson["filtervalue"])) {
        $logger->error(SELF . " - retrive weibo data excption--+-model error :filtervalue null");
        setErrorMsg(ERROR_MODE_CONFIG, "model error :filtervalue null");
    }

//    $taskHourInt = intval($taskHour);
    $endTimeStr = $taskYear . "-" . $taskMonth . "-" . $taskDay . " " . $taskHour . ":" . "00" . ":" . "00";
    $logger->info(SELF . " - 设置模型参数,endTimeStr:[" . $endTimeStr . "].");
    $endTime = strtotime($endTimeStr);

    if (!isset($startTimeStr) || empty($startTimeStr)) {
        // 没有指定开始时间 则提取N小时之内的数据
        if (!isset($taskStartHour) || empty($taskStartHour)) {
            $taskStartHour = 1; //默认一个小时
        } else {
            if ($taskStartHour > 1) {
                $taskStartHour = 1;
            }
        }
        $startTime = $endTime - ($taskStartHour * 3600);

        ////补充增量数据 在第一次获取 当前数据时候 需要查询增量数据的总记录条数
        if ($retriveType == RETRIVE_TYPE_NEW && $offsetCur == 0) {
            //拼接URL 只让solr 返回一条数据
            $url = HTTP_SUF . SERVER_HOST . ":" . SERVER_PORT . GET_MODE_DATA_PATH . "?instanceid=" . $instanceId . "&elementid=" . $elementId . "&hasformjson=" . $hasformjson . "&offset=" . $offsetCur . "&rows=1" . "&returnoriginal=" . $returnoriginal . "&token=" . $token;

            //将入库时间 设置在时间段 $saveStartTime $saveEndTime
            $dataJsonAppend = setModelStartEndTime($startTime, $endTime, $dataJson, true, "save_time");
            $appdResp = thirdPartyGetData($dataJsonAppend, $url);
            $logger->info(SELF . " - 获取模型数据--+-查询增量数据总数，应答:[" . $appdResp . "].");
            $appdResp = json_decode($appdResp, true);
            $logger->info(SELF . " - 获取模型数据--+-查询增量数据总数，应答(obj):[" . var_export($appdResp, true) . "].");

            $appdResp = $appdResp[0];
            if (!isset($appdResp['totalcount'])) {
                $logger->error(SELF . " - retrive weibo data--+-查询增量数据总数失败--+-error:cannot get totalcount!");
                setErrorMsg(ERROR_INNER_EXCEPTION, "获取模型数据--+-查询增量数据总数失败:[cannot get totalcount!]");
            }
            $logger->info(SELF . " - 获取模型数据--+-增量数据总数获取成功:[" . $appdResp['totalcount'] . "].");
            $appendDataCount = $appdResp['totalcount'];
//        $apdDataCount = $appdResp['totalcount'];
//        $apdDataList = $appdResp['datalist'];
        }
    } else {
        if ($startTimeStr == "noLimit") {
            $startTime = 0;
        } else {
            //计算 开始时间的最大限制
            $startTimeMin = strtotime("$endTimeStr -3 month");
            $startTime = strtotime($startTimeStr);

            // 取时间跨度 为最大三个月
            if ($startTime < $startTimeMin) {
                $startTimeMinStr = date('Y-m-d H:i:s', $startTimeMin);
                $logger->error(SELF . " - retrive weibo data excption--+-time setting error :start end time rang to long! The min startTime:[" . $startTimeMinStr . "].");
                setErrorMsg(ERROR_TIME_PARAM, "model error :start end time rang to long! The min startTime:[" . $startTimeMinStr . "].");
            }
        }
    }

    $respObj = array("tocken" => "", "task_id" => $taskReqId, "task_type" => $taskType, "task_year" => $taskYear, "task_month" => $taskMonth, "task_day" => $taskDay, "task_hour" => $taskHour, "website_name" => $mediaName, "website_type" => $mediaType);

    //获取
    if ($retriveType == RETRIVE_TYPE_NEW) {
        $logger->info(SELF . " - 设置模型参数完成，本次获取从:[" . date('Y-m-d H:i:s', $startTime) . "] 到:[" . date('Y-m-d H:i:s', $endTime) . "] 之间的微博数据.");
//        $logger->info(SELF . " - 设置模型参数完成，本次获取从:[" . $startTime . "] 到:[" . $endTime . "] 之间的微博数据.");

        $logger->debug(SELF . " - 设置模型参数完成，设置前的filtervalue: " . var_export($dataJson["filtervalue"], true));
        $dataJsonNearly = &setModelStartEndTime($startTime, $endTime, $dataJson);
        $logger->debug(SELF . " - 设置模型参数--+-设置后的filtervalue: " . var_export($dataJson["filtervalue"], true));

        $url = HTTP_SUF . SERVER_HOST . ":" . SERVER_PORT . GET_MODE_DATA_PATH . "?instanceid=" . $instanceId . "&elementid=" . $elementId . "&hasformjson=" . $hasformjson . "&offset=" . $offsetCur . "&rows=" . $rows . "&returnoriginal=" . $returnoriginal . "&token=" . $token;

        $res = thirdPartyGetData($dataJsonNearly, $url);
        $logger->info(SELF . " - 获取模型数据请求发送成功，应答:[" . $res . "].");
        $res = json_decode($res, true);
        $logger->info(SELF . " - 获取模型数据请求发送成功，应答:[" . var_export($res, true) . "].");

        $res = $res[0];

        if (!isset($res['totalcount'])) {
            $logger->error(SELF . " - retrive weibo data excption--+-error:cannot get totalcount!");
            setErrorMsg(ERROR_INNER_EXCEPTION, "data retrive failed. get data count failed!");
        }
        $logger->info(SELF . " - data retrive --+- dataCount:[" . $res['totalcount'] . "].");

        $dataCount = $res['totalcount'];
        $respObj['statusCode'] = 200;
        $respObj['sum_size'] = $dataCount;
        $respObj['dataStart'] = $offsetCur;

        $returnSize = isset($res['datalist']) ? count($res['datalist']) : 0;
        $respObj['return_size'] = $returnSize;

        if ($dataCount == 0) {
            $respObj['remain_size'] = 0;
            $respObj['article'] = array();;
        } else {
            if ($offsetCur > $dataCount - 1) {
                //offset 范围 为 0 - [$dataCount - 1]
                $logger->error(SELF . " - retrive weibo data excption--+-param:[offset] out of range:[0-" . ($dataCount - 1) . "].");
                setErrorMsg(ERROR_INNER_EXCEPTION, "data retrive failed. param:[offset] out of range:[0-" . ($dataCount - 1) . "].");
            }

            $remainCount = $dataCount - ($offsetCur + $returnSize);
            $respObj['remain_size'] = $remainCount;
            $innerDataList = ($returnSize > 0) ? $res['datalist'] : array();
            $fieldMappingCfg = getFieldMapping($userName);
            $logger->info(SELF . " - data retrive --+- filedMappingConfig: " . var_export($fieldMappingCfg, true));
            $respDataList =  &mappingFiledValue($requstDataObj, $innerDataList, $fieldMappingCfg);
            $logger->debug(SELF . " - data retrive --+- requet data : " . var_export($requstDataObj, true));
            $logger->info(SELF . " - data retrive --+- mappingFiledValue success, response data : " . var_export($respDataList, true));
            $respObj['article'] = $respDataList;

            //增量数据总条数
            $respObj['icmt_data_size'] = $appendDataCount;
        }
    } else if ($retriveType == RETRIVE_TYPE_APPEND) {
        //获取增量数据
        $appdStartTimeStr = $taskYear . "-" . $taskMonth . "-" . $taskDay . " " . "00" . ":" . "00" . ":" . "00";
        $appdEndTime = $startTime;
        $appdStartTime = strtotime($appdStartTimeStr);

        $logger->debug(SELF . " - 获取增量数据--+-设置模型参数完成，设置前的filtervalue: " . var_export($dataJson["filtervalue"], true));
        $dataJsonApp = setModelStartEndTime($appdStartTime, $appdEndTime, $dataJson, true, "save_time");
        $logger->debug(SELF . " - 获取增量数据--+-设置模型参数--+-设置后的filtervalue: " . var_export($dataJson["filtervalue"], true));

        $url = HTTP_SUF . SERVER_HOST . ":" . SERVER_PORT . GET_MODE_DATA_PATH . "?instanceid=" . $instanceId . "&elementid=" . $elementId . "&hasformjson=" . $hasformjson . "&offset=" . $offsetCur . "&rows=" . $rows . "&returnoriginal=" . $returnoriginal . "&token=" . $token;

        $res = thirdPartyGetData($dataJsonApp, $url);
//        $logger->info(SELF . " - 获取增量数据--+-获取模型数据请求发送成功，应答:[" . $res . "].");
        $res = json_decode($res, true);
        $logger->info(SELF . " - 获取增量数据--+-获取模型数据请求发送成功，应答(obj):[" . var_export($res, true) . "].");
        $res = $res[0];

        if (!isset($res['totalcount'])) {
            $logger->error(SELF . " - 获取增量数据--+-请求失败--+-error:cannot get totalcount!");
            setErrorMsg(ERROR_INNER_EXCEPTION, "data retrive failed. get data count failed!");
        }
        $logger->info(SELF . " - data retrive --+- 获取增量数据--+-dataCount:[" . $res['totalcount'] . "].");

        $dataCount = $res['totalcount'];
        $respObj['statusCode'] = 200;
        $respObj['sum_size'] = $dataCount;
        $respObj['dataStart'] = $offsetCur;

        $returnSize = isset($res['datalist']) ? count($res['datalist']) : 0;
        $respObj['return_size'] = $returnSize;
        if ($dataCount == 0) {
            $respObj['remain_size'] = 0;
            $respObj['article'] = array();
        } else {
            if ($offsetCur > $dataCount - 1) {
                //offset 范围 为 0 - [$dataCount - 1]
                $logger->error(SELF . " - retrive weibo data excption--+-param:[offset] out of range:[0-" . ($dataCount - 1) . "].");
                setErrorMsg(ERROR_INNER_EXCEPTION, "data retrive failed. param:[offset] out of range:[0-" . ($dataCount - 1) . "].");
            }

            $remainCount = $dataCount - ($offsetCur + $returnSize);
            $respObj['remain_size'] = $remainCount;
            $innerDataList = ($returnSize > 0) ? $res['datalist'] : array();
            $fieldMappingCfg = getFieldMapping($userName);
            $logger->info(SELF . " - data retrive --+-获取增量数据--+-filedMappingConfig: " . var_export($fieldMappingCfg, true));
            $respDataList =  &mappingFiledValue($requstDataObj, $innerDataList, $fieldMappingCfg);
            $logger->debug(SELF . " - data retrive --+-获取增量数据--+- requet data : " . var_export($requstDataObj, true));
            $logger->info(SELF . " - data retrive --+-获取增量数据--+- mappingFiledValue success, response data : " . var_export($respDataList, true));
            $respObj['article'] = $respDataList;
        }
    }

    $logger->info(SELF . " - retrive weibo data complete.--+-response:" . var_export($respObj, true));
    echo(json_encode($respObj));

//    echo(var_export($respObj, true));
    //var_dump("res ", json_decode($res, true));
}


function &setModelStartEndTime($startTime, $endTime, $modelConfigObj, $isAppRealName = false, $realFieldName = "")
{
    global $logger;
//    $allParam = $dataJson["filtervalue"];
    foreach ($modelConfigObj["filtervalue"] as $indx => &$param) {
        if ($param["fieldname"] == "createdtime") {
            $logger->info(SELF . " - retrive weibo data--+-setModelStartEndTime to new value. currentConfig: " . var_export($param, true) . "\n newValue:[start:" . $startTime . ", end:" . $endTime . "]");
            $param["fieldvalue"]["value"]["start"] = $startTime;
            $param["fieldvalue"]["value"]["end"] = $endTime;

            if ($isAppRealName) {
                $param["realFieldName"] = $realFieldName;
            }
        } else {
            continue;
        }
    }
    return $modelConfigObj;
}

function &mappingFiledValue(&$requData, &$dataList, $fieldMappingCfg)
{
    global $logger;
    $respDataList = array();
    if (count($dataList) <= 0) {
        return $respDataList;
    }

    foreach ($dataList as $dataIdx => &$innerDataObj) {
        $respData = array();
        //处理每一个字段
        foreach ($fieldMappingCfg as $extFiledName => $mappingConfig) {

            if ($mappingConfig["skip"]) {
                //当前字段直接跳过 赋值 默认值
                $respData[$extFiledName] = $mappingConfig["dfValue"];
            } else if (isset($mappingConfig["requsetFiled"]) && !empty($mappingConfig["requsetFiled"])) {
                //当前字段 的值 来自 请求报文中
                if (!empty($requData[$mappingConfig["requsetFiled"]])) {
                    $respData[$extFiledName] = $requData[$mappingConfig["requsetFiled"]];
                } else {
                    $logger->warn(SELF . " - mappingFiledValue.--+-fieldName:" . $mappingConfig["requsetFiled"] . " 在请求报文中不存在.");
                }
            } else if (isset($mappingConfig["innerName"]) && !empty($mappingConfig["innerName"])) {
                $innerName = &$mappingConfig["innerName"];
                //根据内部字段名获取内部字段
                if (isset($innerDataObj[$innerName]) && !empty($innerDataObj[$innerName])) {
                    $curFiledValue = $innerDataObj[$innerName];
                    //针对时间类型的数据 进行时间格式化
                    if (isset($mappingConfig["dataType"]) && $mappingConfig["dataType"] == "time") {
                        //获取时间模版
                        $timeFormatTmpl = 'Y-m-d H:i:s';
                        if (isset($mappingConfig["formatTmpl"]) && !empty($mappingConfig["formatTmpl"])) {
                            $timeFormatTmpl = $mappingConfig["formatTmpl"];
                        }
                        $curFiledValue = date($timeFormatTmpl, $curFiledValue);
                    }
                    $respData[$extFiledName] = $curFiledValue;
                } else {
                    //内部数据中没有找到 则将默认值 赋值给 应答数据
                    $respData[$extFiledName] = $mappingConfig["dfValue"];
                }
            }
        }
        $respDataList[] = $respData;
    }
    return $respDataList;
}


/**
 *外字段和内部字段不一致时候 需要将内部字段转化为外部字段
 * 并在
 */
function getFieldMapping($userName)
{
    $weiboInfoMapping = array();
    $weiboInfoMapping["docid"] = array("innerName" => "id", "dfValue" => "", "skip" => false);
//    $weiboInfoMapping["media_id"] = array("innerName" => "", "dfValue" => "", "skip" => true);
    $weiboInfoMapping["media_id"] = array("requsetFiled" => "media_id", "dfValue" => "", "skip" => false);
    $weiboInfoMapping["baseurl"] = array("innerName" => "page_url", "dfValue" => "", "skip" => true);

    $weiboInfoMapping["page_url"] = array("innerName" => "page_url", "dfValue" => "", "skip" => false);
    $weiboInfoMapping["webnews_title"] = array("innerName" => "", "dfValue" => "", "skip" => true);
    $weiboInfoMapping["screen_name"] = array("innerName" => "screen_name", "dfValue" => "", "skip" => false);
    $weiboInfoMapping["created_at"] = array("innerName" => "created_at", "dfValue" => 0, "skip" => false, "dataType" => "time", "formatTmpl" => 'Y-m-d H:i:s');
    $weiboInfoMapping["source"] = array("innerName" => "verified_reason", "dfValue" => "", "skip" => false);
    $weiboInfoMapping["text"] = array("innerName" => "text", "dfValue" => "", "skip" => false);

//    $weiboInfoMapping["website_name"] = array("requsetFiled" => "website_name", "dfValue" => "", "skip" => false);
//    $weiboInfoMapping["website_type"] = array("requsetFiled" => "website_type", "dfValue" => "", "skip" => false);

    $weiboInfoMapping["website_name"] = array("innerName" => "website_name", "dfValue" => "新浪微博", "skip" => true);
    $weiboInfoMapping["website_type"] = array("innerName" => "website_type", "dfValue" => 2, "skip" => true);


    $weiboInfoMapping["browse"] = array("innerName" => "", "dfValue" => "", "skip" => true);
    $weiboInfoMapping["reposts_count"] = array("innerName" => "reposts_count", "dfValue" => 0, "skip" => false);
    $weiboInfoMapping["comments_count"] = array("innerName" => "comments_count", "dfValue" => 0, "skip" => false);
    $weiboInfoMapping["praises_count"] = array("innerName" => "praises_count", "dfValue" => 0, "skip" => false);
    $weiboInfoMapping["verified_type"] = array("innerName" => "verified_type", "dfValue" => "0", "skip" => false);
    $weiboInfoMapping["emotiom_type"] = array("innerName" => "emotiom_type", "dfValue" => "0", "skip" => false);
    $weiboInfoMapping["uid"] = array("innerName" => "userid", "dfValue" => "", "skip" => false);

    $filedMappingTables = array("demo2" => $weiboInfoMapping);
    return $filedMappingTables[$userName];
}


function getRetriveConfig4User($userName)
{
    if (empty($userName)) {
        throw new Exception("getRetriveConfig4User excpetion. userName is null.");
    }

    // ******************** 生产上配置 ***************************//
    $userConfigs = array("elementId" => 16757, "instanceId" => 15488, "hostName" => "http://demo2.3i.inter3i.com:7010", "hasformjson" => true, "returnoriginal" => false, "childHostDomain" => "demo2");
    $userPortMapping = array('demo2' => $userConfigs);
// ******************** 生产上配置 ***************************//

// ******************** 30上配置 ***************************//
//$userConfigs = array("elementId" => 1177, "instanceId" => 2081, "hostName" => "http://192.168.0.30:80");
//$userPortMapping = array('demo3' => $userConfigs);
// ******************** 30上配置 ***************************//

// ******************** 30上配置 ***************************//
//    $userConfigs = array("elementId" => 1177, "instanceId" => 2081, "hostName" => "http://wangcc:80", "hasformjson" => true, "returnoriginal" => false, "childHostDomain" => "192");
//    $userPortMapping = array('demo3' => $userConfigs);
// ******************** 30上配置 ***************************//

    if (!isset($userPortMapping[$userName])) {
        throw new Exception("getRetriveConfig4User exception. config not exist for current userName:[" . $userName . "].");
    }
    return $userPortMapping[$userName];
}

//function getErrorOutput($error_code = -1, $error_str)
//{
//    return array("errorcode" => $error_code, "error" => $error_str);
//}
//
//function setErrorMsg($error_code = -1, $error_str)
//{
//    $error = getErrorOutput($error_code, $error_str);
//    echo json_encode($error);
//    exit;
//}


//通过instanceid和elementid获取数据
//hasformjson 0:通过instanceid和elementid获取数据
//微博模型需要returnoriginal参数为1返回转发微博对应当原创, 其他模型需要置为 0
//offset 从第几条开始
//rows返回多少条
//标准
//$url = "http://192.168.0.30:7010/model/requestdata.php?instanceid=2077&elementid=1172&hasformjson=0&offset=0&rows=10&returnoriginal=0&token=" . $token;


//联动
//$url = "http://192.168.0.102/model/requestdata.php?instanceid=1464&elementid=1614&hasformjson=0&islinkage=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";


//叠加
//$url = "http://192.168.0.30/model/requestdata.php?instanceid=2077&elementid=1172&hasformjson=0&isoverlay=1&offset=0&rows=20&returnoriginal=0&token=" . $token;

//$logger->info(SELF . " - 获取模型数据--+-发送请求... url:[" . $url . "].");
//
//$modeConfigData = sendHttpReqGet($url);
//$logger->info(SELF . " - 获取模型数据--+-发送请求成功. 应答数据:[" . $modeConfigData . "].");
//
//$modeConfigData = json_decode($modeConfigData, true);
//$logger->info(SELF . " - 获取模型数据成功 responseData:[" . var_export($modeConfigData, true) . "].");
//
//if (!isset($modeConfigData['content'])) {
//    $logger->info(SELF . " - 获取模型content失败. content is null.");
//}
//var_dump("res ", $res);
//var_dump($json);

//从服务端获取token
function getTokenFromServer($userName, $password)
{
    global $logger;
    // http://demo2.3i.inter3i.com:7010 /model/checkuser.php?type=gettoken &username=intel_reader&password=55f03012060cf62831e871b3af15e910
    $reqURL = HTTP_SUF . SERVER_HOST . ":" . SERVER_PORT . GET_TOKEN_PATH . "&username=" . $userName . "&password=" . $password;
    $logger->info(SELF . " - getTokenFromServer ... URL:[" . $reqURL . "].");
    $tokenRsp = sendHttpReqGet($reqURL);
    $logger->info(SELF . " - getTokenFromServer ok. response: " . $tokenRsp);

//    $tokenRsp = json_decode($tokenRsp, true);
//    $logger->info(SELF . " - getTokenFromServer ok. response: " . var_export($tokenRsp, true));
//    if (empty($tokenRsp['token'])) {
//        throw new Exception("getTokenFromServer exception,token is null.");
//    }
//    return $tokenRsp['token'];

    return "cc211tRnuA\/K9FbufAMtOGCxyhmHjNE5raUGUYle0hlYng";
}

function sendHttpReqGet($url, $timeOut = 5000, $connecttimeout = 2000)
{
    if (!$url) {
        throw new Exception("sendHttpReqGet exception:[url is null.]");
    }
    $timeout = 0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

    $response = curl_exec($ch);

    if ($response === FALSE) {
        $log_note = 'curl error is' . curl_error($ch) . " response is false!";
        var_dump("log_note" . $log_note);
        curl_close($ch);
        return false;
    }
    //关闭cURL资源，并且释放系统资源
    curl_close($ch);

    return $response;
}

function sendHttpReqPost($url, $senddata, $timeOut = 5000, $connecttimeout = 2000)
{
    if (!$url) {
        throw new Exception("sendHttpReq exception:[url is null.]");
    }

    $timeout = 0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $senddata);

    $header_array = array('Content-type:application/json');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);

    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

    $response = curl_exec($ch);

    if ($response === FALSE) {
        $log_note = 'curl error is' . curl_error($ch);
        var_dump("log_note" . $log_note);
        curl_close($ch);
        return false;
    }
    //关闭cURL资源，并且释放系统资源
    curl_close($ch);
    unset($senddata);
    return $response;
}

function thirdPartyGetData($jsoninfoObj, $url)
{
    if (!$url) {
        echo 'opt url is null';
        return false;
    }
    $senddata = json_encode($jsoninfoObj);
    $timeout = 0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $senddata);

    $header_array = array('Content-type:application/json');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);

    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

    //$start_time = microtime_float();
    $response = curl_exec($ch);
    //$end_time = microtime_float();
    if ($response === FALSE) {
        $log_note = 'curl error is' . curl_error($ch);
        var_dump("log_note" . $log_note);
        curl_close($ch);
        return false;
    }
    //关闭cURL资源，并且释放系统资源
    curl_close($ch);
    unset($senddata);
    return $response;
}

/**
 *  add by wangcc:第三方接口获取数据时候，提供用户名密码 这里为后台提供
 *  根据用户名密码 获取 token的方法
 */
function gettokenBy($userName, $passWord, $childHostDomain)
{
    global $logger;
    $r = checkuser($userName, $passWord, LOCALTYPE_TENANT, $childHostDomain);
    if ($r !== true) {
        throw new Exception("验证用户信息失败! ErrorInof:" . var_export($r, true));
    } else {
        //生成 token
        $token = authcode($userName, 'ENCODE', TOKENKEY, TOKENEXPIRY);
        if (empty($token)) {
            throw new Exception("为用户生成token失败!");
        } else {
            $logger->info(SELF . " - generate token ok. token:[" . $token . "].");
            return $token;
        }
    }
}

//function setErrorMsg($error_code = -1, $error_str)
//{
//    $error = getErrorOutput($error_code, $error_str);
//    echo json_encode($error);
//    exit;
//}

/**
 * 验证用户创建session
 * @param $username 用户名
 * @param $password 密码
 * @param $localtype 租户类型，系统用户登录时，为null
 * @param $securl 二级域名 ，系统用户登录时为null
 */
function checkuser($username, $password, $localtype, $securl)
{
    global $dsql, $logger;
    $checksql = "";
    if (empty($localtype)) {
        $checksql = "select * from users where username = '{$username}' and password='{$password}' and tenantid=-1";
    } else {
        $checksql = "select * from users a inner join tenant b on a.tenantid=b.tenantid
          where a.username = '{$username}' and a.password='{$password}'
          and b.weburl='{$securl}' and b.localtype={$localtype}";
    }
    $qr = $dsql->ExecQuery($checksql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$checksql} " . $dsql->GetError());
        return array("errorcode" => LOGIN_ERROR_EXCEPTION, "error" => "操作异常");
    } else {
        $num = $dsql->GetTotalRow($qr);
        $logininfo = array();
        if ($num > 0) {
            $result = $dsql->GetArray($qr, MYSQL_ASSOC);
            if (!empty($result)) {
                $userid = $result["userid"];
                $tenantid = $result["tenantid"];
                $logininfo["userid"] = $result["userid"];
                //失效时间
                $expiretime = $result["expiretime"];
                $now = time();
                if (!empty($expiretime) && $now > $expiretime) {
                    //判断失效时间
                    $logger->warn("login faild: expire time user");
                    $errtype = LOGIN_ERROR_EXPIRE;
                    $errmsg = "该账号已经过期";
                    $logininfo["loginresult"] = LOGIN_ERROR_EXPIRE;
                } else {
//                    $user = new UserInfo($userid, $tenantid, $localtype, $securl);
//                    $user->userexpiretime = $expiretime; //session中存过期时间
//                    Authorization::setUserRole($user);
//                    $_SESSION["user"] = $user;
                    $logininfo["loginresult"] = LOGIN_ERROR_SUCCESS;
                }
            }
        } else {
            //判断是用户名错误还是密码错误
            if (empty($localtype)) {
                $checkusersql = "select * from users where username = '{$username}' and tenantid=-1";
            } else {
                $checkusersql = "select a.*, b.localtype,b.weburl, b.allowlinkage, b.allowdrilldown,b.allowdownload,
					b.allowwidget, b.allowaccessdata, b.accessdatalimit from users a inner join tenant b on a.tenantid=b.tenantid
					where a.username = '{$username}' and b.weburl='{$securl}' and b.localtype={$localtype}";
            }
            $usqr = $dsql->ExecQuery($checkusersql);
            if (!$usqr) {
                $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$checkusersql} " . $dsql->GetError());
                return getErrorOutput(LOGIN_ERROR_EXCEPTION, "操作异常");
            } else {
                $unum = $dsql->GetTotalRow($usqr);
                $result = $dsql->GetArray($usqr, MYSQL_ASSOC);
                if ($unum > 0) { //查询有对应的用户名
                    $errtype = LOGIN_ERROR_NOPWD;
                    $errmsg = "密码错误";
                    $logininfo["loginresult"] = LOGIN_ERROR_NOPWD;
                    $logininfo["userid"] = $result["userid"];
                } else {
                    $errtype = LOGIN_ERROR_NOUSER;
                    $errmsg = "用户名错误";
                    $logininfo["loginresult"] = LOGIN_ERROR_NOUSER;
                    $logininfo["errorusername"] = $username;
                    if ($securl != NULL) {
                        $logininfo["errorusertype"] = 1;
                        //根据weburl查询对应的租户id
                        $seltenantid = "select tenantid from tenant where weburl = '" . $securl . "'";
                        $seltenantidqr = $dsql->ExecQuery($seltenantid);
                        $tidnum = $dsql->GetTotalRow($seltenantidqr);
                        if ($tidnum > 0) {
                            $tidresult = $dsql->GetArray($seltenantidqr, MYSQL_ASSOC);
                            $logininfo["errortenantid"] = $tidresult["tenantid"];
                        }
                    } else {
                        $logininfo["errorusertype"] = -1;
                    }
                }
                $url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $remote_addr = $_SERVER['REMOTE_ADDR'] . ":" . $_SERVER['REMOTE_PORT'];
                $logger->warn("login faild: URL:{$url} REMOTE_ADDR:{$remote_addr} USERNAME:{$username}");
            }
        }
//        $fields = array();
//        $values = array();
//        //remoteip;
//        $remoteip = getIP();
//        if ($remoteip) {
//            $fields[] = "remoteip";
//            $values[] = "'" . $remoteip . "'";
//        }
//        //OS
//        $remoteos = getOS();
//        if ($remoteos) {
//            $fields[] = "remoteos";
//            $values[] = "'" . $remoteos . "'";
//        }
//        //logintime
//        $logintime = time();
//        $fields[] = "logintime";
//        $values[] = $logintime;
//        //userbrowser
//        $userbrowser = getBrowse();
//        if ($userbrowser) {
//            $fields[] = "userbrowser";
//            $values[] = "'" . $userbrowser . "'";
//        }
//        //userid
//        $userid = NULL;
//        if (isset($logininfo["userid"])) {
//            $userid = $logininfo["userid"];
//            $fields[] = "userid";
//            $values[] = $userid;
//        }
//        //errorusername
//        if (isset($logininfo["errorusername"])) {
//            $errusername = $logininfo["errorusername"];
//            $fields[] = "errorusername";
//            $values[] = "'" . $errusername . "'";
//        }
//        //errortenantid
//        if (isset($logininfo["errortenantid"])) {
//            $errtenantid = $logininfo["errortenantid"];
//            $fields[] = "errortenantid";
//            $values[] = "'" . $errtenantid . "'";
//        }
//        //errorusertype
//        if (isset($logininfo["errorusertype"])) {
//            $errusertype = $logininfo["errorusertype"];
//            $fields[] = "errorusertype";
//            $values[] = "'" . $errusertype . "'";
//        }

        //loginresult
//        $lr = $logininfo["loginresult"];
//        $fields[] = "loginresult";
//        $values[] = $lr;
//        $fieldstr = implode(",", $fields);
//        $valuestr = implode(",", $values);
//        $lsql = "INSERT INTO `loginhistory` (" . $fieldstr . ") VALUES (" . $valuestr . ")";
//        $lqr = $dsql->ExecQuery($lsql);
//        if (!$lqr) {
//            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$lsql} " . $dsql->GetError());
//            return getErrorOutput(LOGIN_ERROR_EXCEPTION, "操作异常");
//        }

        if ($logininfo["loginresult"] == LOGIN_ERROR_SUCCESS) {
            return true;
        } else {
            return getErrorOutput($errtype, $errmsg);
        }
    }
}

// 根据 content json 请求数据
//1. 根据数据 模型 查询数据库 获取 content


$url = "http://192.168.0.102/model/requestdata.php?instanceid=-1&elementid=-1&hasformjson=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
/*
            "datajson": {
                "version": 1036, //请求数据json的版本号
                "modelid": 31,  //请求模型的id, 用户分析:1, 用户统计2, 话题分析31, 微博分析51
                "isdefaultrelation": true, //是否使用默认的relation, 相同字段之间关系为或,不同字段直接关系为且
                "filterrelation": { //filtervalue字段的逻辑关系, fields为数字数组, 数字为filtervalue数组中字段的索引
                    "opt": "and",
                    "filterlist": [],
                    "fields": []
                },
				"filter": {  //可配置的字段列表, 用户分析和用户统计对应用户信息, 列表相同, 话题分析和微博分析对应微博信息, 列表相同
					"weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
                "facet": { //分组统计 , 用户统计模型和话题分析模型用到, field为字段分组统计, range为区间分组统计, 不做分组统计时 field和range为空数组, 不能同时做facet field查询和facet range查询
                    "label": "分组统计",
                    "datatype": "string",
                    "field": [
                        {
                            "name": "organization", //统计字段名称
                            "includeconfig": { //统计字段包含功能, 是否显示其他, 
                                "showother": 0,
                                "alias": "" //其他的显示名称
                            },
                            "filter": [ //包含和去除
                                {
                                    "type": "include",
                                    "value": []
                                },
                                {
                                    "type": "exclude",
                                    "value": []
                                }
                            ],
                            "facettype": 101,
                            "allcount": false,
                            "featureconfig": {
                                "showother": 0,
                                "alias": ""
                            },
                            "isfeature": 0,
                            "feature": []
                        }
                    ],
					"range": [
						{
							"name": "created_at",
							"rangeinfo": {
								"value": {
									"gap": "1month",
									"rangevalue": {
										"type": "time_dynamic_state",
										"value": {
											"start": "1",
											"startgap": "year",
											"timestate": "now",
											"name": "nearlytime"
										}
									}
								},
								"type": "gap"
							},
							"sides": 0,
							"facettype": 101,
							"allcount": false
						}
					]
                },
                "select": { //query查询时,返回的字段对应solrurl的 fl字段
                    "label": "查询字段",
                    "datatype": "string",
                    "value": [
                        "id",
                        "screen_name",
                        "location",
                        "description",
                        "profile_image_url",
                        "followers_count",
                        "friends_count",
                        "statuses_count",
                        "verify",
                        "verified_reason",
                        "verified_type",
                        "sex",
                        "sourceid",
                        "text",
                        "created_at",
                        "reposts_count",
                        "comments_count",
                        "content_type",
                        "userid",
                        "mid",
                        "source",
                        "thumbnail_pic",
                        "bmiddle_pic",
                        "retweeted_status",
                        "retweeted_mid",
                        "guid"
                    ]
                },
                "output": {
                    "label": "输出条件",
                    "datatype": "string",
                    "outputtype": "2", //1:query查询, 2:facet统计
                    "data_limit": 0, //从第几条开始
                    "count": "10", //返回条数
                    "ordertype": "desc",
                    "pageable": true
                },
                "contrast": null, //分类对比查询
                "classifyquery": null, //分类查询
                "filtervalue": [], //查询条件字段对应的值
                "distinct": { //对微博进行分析时, 返回结果相同用户的去重
                    "label": "结果唯一",
                    "datatype": "string",
                    "limit": [
                        {
                            "value": "screen_name",
                            "repeat": 1,
                            "type": "exact"
                        }
                    ],
                    "distinctfield": ""
                }
            }
 */
//用户分析,和用户统计模型
//$jsoninfo = '{ "version": 1036, "modelid": 1, "isdefaultrelation": true, "filterrelation": {"opt":"and","filterlist":[],"fields":[0]}, "filter": { "username": { "label": "查昵称", "datatype": "string" }, "usersfollower": { "label": "查粉丝", "datatype": "string" }, "usersfriend": { "label": "查关注", "datatype": "string" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "followerrank": { "label": "粉丝数", "datatype": "range" }, "friendrank": { "label": "关注数", "datatype": "range" }, "statusesrank": { "label": "微博数", "datatype": "range" }, "users_favourites_count": { "label": "收藏数", "datatype": "range" }, "users_bi_followers_count": { "label": "互粉数", "datatype": "range" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "source": { "label": "来源", "datatype": "int" }, "sex": { "label": "作者性别", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" }, "area": { "label": "地区", "datatype": "value_text_object" }, "users_url": { "label": "博客地址", "datatype": "string" }, "users_domain": { "label": "个性化域名", "datatype": "string" }, "users_allow_all_act_msg": { "label": "允许私信", "datatype": "string" }, "users_allow_all_comment": { "label": "允许评论", "datatype": "string" } }, "facet": { "label": "分组统计", "datatype": "string", "field": [], "range": [] }, "select": { "label": "查询字段", "datatype": "string", "value": [ "users_id", "users_screen_name", "users_location", "users_description", "users_profile_image_url", "users_followers_count", "users_friends_count", "users_statuses_count", "users_favourites_count", "users_bi_followers_count", "users_verified", "users_verified_reason", "users_verified_type", "users_gender", "users_sourceid" ] }, "output": { "label": "输出条件", "datatype": "string", "outputtype": 1, "data_limit": 0, "count": "10", "orderby": "users_followers_count", "ordertype": "desc", "pageable": true }, "contrast": null, "classifyquery": null, "filtervalue":[{"fieldname":"verifiedreason","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"string","value":"董事长"}}]}'; 
//话题分析,微博分析
//$jsoninfo = ' { "version": 1036, "modelid": 31, "isdefaultrelation": true, "filterrelation": {"opt":"and","filterlist":[],"fields":[0]}, "filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } }, "facet": { "label": "分组统计", "datatype": "string", "field": [ { "name": "text", "includeconfig": { "showother": 0, "alias": "" }, "filter": [ { "type": "include", "value": [] }, { "type": "exclude", "value": [] } ], "facettype": 101, "allcount": false, "featureconfig": { "showother": 0, "alias": "" }, "isfeature": 1, "feature": [ { "value": "热门,高频词", "text": "高频词" } ] } ], "range": [] }, "select": { "label": "查询字段", "datatype": "string", "value": [ "bmiddle_pic", "comments_count", "content_type", "created_at", "description", "followers_count", "friends_count", "guid", "id", "location", "mid", "profile_image_url", "reposts_count", "retweeted_mid", "retweeted_status", "screen_name", "sex", "source", "sourceid", "statuses_count", "text", "thumbnail_pic", "userid", "verified_reason", "verified_type", "verify" ] }, "output": { "label": "输出条件", "datatype": "string", "outputtype": "2", "data_limit": 0, "count": 10, "orderby": "created_at", "ordertype": "desc", "pageable": true }, "contrast": null, "classifyquery":{"type":1,"fieldname":"organization"}, "filtervalue": [{"fieldname":"organization","fromlimit":0,"fieldvalue":{"datatype":"array","value":[{"datatype":"string","value":"中石油"},{"datatype":"string","value":"中石化"},{"datatype":"string","value":"发改委"},{"datatype":"string","value":"人民网"}]}}], "distinct": { "label": "结果唯一", "datatype": "string", "distinctfield": "" } } '; 


//联动模型, url参数中 islinkage 置为1;
$url = "http://192.168.0.102/model/requestdata.php?islinkage=1&hasformjson=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
/*
$jsoninfo = '{
    "instanceid": -1,
    "elements": [
        {
            "elementid": -1,
            "datajson": {
                "version": 1036,
                "modelid": 31,
                "isdefaultrelation": true,
                "filterrelation": {
                    "opt": "and",
                    "filterlist": [],
                    "fields": []
                },
"filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
                "facet": {
                    "label": "分组统计",
                    "datatype": "string",
                    "field": [
                        {
                            "name": "organization",
                            "includeconfig": {
                                "showother": 0,
                                "alias": ""
                            },
                            "filter": [
                                {
                                    "type": "include",
                                    "value": []
                                },
                                {
                                    "type": "exclude",
                                    "value": []
                                }
                            ],
                            "facettype": 101,
                            "allcount": false,
                            "featureconfig": {
                                "showother": 0,
                                "alias": ""
                            },
                            "isfeature": 0,
                            "feature": []
                        }
                    ],
                    "range": []
                },
                "select": {
                    "label": "查询字段",
                    "datatype": "string",
                    "value": [
                        "id",
                        "screen_name",
                        "location",
                        "description",
                        "profile_image_url",
                        "followers_count",
                        "friends_count",
                        "statuses_count",
                        "verify",
                        "verified_reason",
                        "verified_type",
                        "sex",
                        "sourceid",
                        "text",
                        "created_at",
                        "reposts_count",
                        "comments_count",
                        "content_type",
                        "userid",
                        "mid",
                        "source",
                        "thumbnail_pic",
                        "bmiddle_pic",
                        "retweeted_status",
                        "retweeted_mid",
                        "guid"
                    ]
                },
                "output": {
                    "label": "输出条件",
                    "datatype": "string",
                    "outputtype": "2",
                    "data_limit": 0,
                    "count": "10",
                    "ordertype": "desc",
                    "pageable": true
                },
                "contrast": null,
                "classifyquery": null,
                "filtervalue": [],
                "distinct": {
                    "label": "结果唯一",
                    "datatype": "string",
                    "limit": [
                        {
                            "value": "screen_name",
                            "repeat": 1,
                            "type": "exact"
                        }
                    ],
                    "distinctfield": ""
                }
            }
        }
    ],
    "pinrelation": [
        {
            "instanceid": -1,
            "inelementid": -2,
            "inpinid": 1,
            "inputdata": {
                "value": "keyword",
                "text": "关键词",
                "opt": "or",
                "datatype": "string",
                "isfeature": 0
            },
            "outelementid": -1,
            "outpinid": 1,
            "outputdata": {
                "pintype": "dynamic",
                "datatype": "string",
                "exclude": false,
                "text": "统计字段",
                "outputfield": "text",
                "value": {
                    "start": null,
                    "end": null
                },
                "isfeature": 0
            }
        }
    ],
    "render": {
        "elementid": -2,
        "datajson": {
            "version": 1036,
            "modelid": 51,
            "isdefaultrelation": true,
            "filterrelation": {
                "opt": "and",
                "filterlist": [],
                "fields": [
                    0
                ]
            },
"filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
            "facet": {
                "label": "分组统计",
                "datatype": "string",
                "field": [],
                "range": []
            },
            "select": {
                "label": "查询字段",
                "datatype": "string",
                "value": [
                    "id",
                    "screen_name",
                    "userid",
                    "mid",
                    "sourceid",
                    "description",
                    "profile_image_url",
                    "followers_count",
                    "friends_count",
                    "statuses_count",
                    "verify",
                    "verified_reason",
                    "verified_type",
                    "sex",
                    "text",
                    "created_at",
                    "reposts_count",
                    "comments_count",
                    "content_type",
                    "source",
                    "thumbnail_pic",
                    "bmiddle_pic",
                    "retweeted_status",
                    "retweeted_mid",
                    "retweeted_guid",
                    "guid"
                ]
            },
            "output": {
                "label": "输出条件",
                "datatype": "string",
                "outputtype": "1",
                "data_limit": 0,
                "count": "10",
                "orderby": "created_at",
                "ordertype": "desc",
                "pageable": true
            },
            "contrast": null,
            "classifyquery": null,
            "filtervalue": [
                {
                    "fieldname": "keyword",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "dynamic",
                        "value": {
                            "start": null,
                            "end": null
                        },
                        "outelementid": -1,
                        "outputfield": "text"
                    }
                }
            ],
            "distinct": {
                "label": "结果唯一",
                "datatype": "string",
                "limit": [
                    {
                        "value": "screen_name",
                        "repeat": 1,
                        "type": "exact"
                    }
                ],
                "distinctfield": ""
            }
        }
    }
}';
 */
$jsoninfo = '
{
    "instanceid": -1,
    "elements": [],
    "pinrelation": [
        {
            "instanceid": -1,
            "inelementid": -2,
            "inpinid": 2,
            "inputdata": {
                "value": "organization",
                "text": "机构",
                "opt": "or",
                "datatype": "string",
                "isfeature": 0
            },
            "outelementid": -1,
            "outpinid": 2,
            "outputdata": {
                "pintype": "static",
                "datatype": "string",
                "exclude": false,
                "text": "统计字段",
                "outputfield": "text",
                "value": [
                    {
                        "text": "发改委",
                        "value": "发改委"
                    },
                    {
                        "text": "人民网",
                        "value": "人民网"
                    },
                    {
                        "text": "中石油",
                        "value": "中石油"
                    }
                ],
                "isfeature": 0
            }
        }
    ],
    "render": {
        "elementid": -2,
        "datajson": {
            "version": 1036,
            "modelid": 51,
            "isdefaultrelation": true,
            "filterrelation": {
                "opt": "and",
                "filterlist": [
                    {
                        "opt": "or",
                        "filterlist": [],
                        "fields": [
                            0,
                            1,
                            2
                        ]
                    }
                ],
                "fields": []
            },
"filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
            "facet": {
                "label": "分组统计",
                "datatype": "string",
                "field": [],
                "range": []
            },
            "select": {
                "label": "查询字段",
                "datatype": "string",
                "value": [
                    "id",
                    "screen_name",
                    "userid",
                    "mid",
                    "sourceid",
                    "description",
                    "profile_image_url",
                    "followers_count",
                    "friends_count",
                    "statuses_count",
                    "verify",
                    "verified_reason",
                    "verified_type",
                    "sex",
                    "text",
                    "created_at",
                    "reposts_count",
                    "comments_count",
                    "content_type",
                    "source",
                    "thumbnail_pic",
                    "bmiddle_pic",
                    "retweeted_status",
                    "retweeted_mid",
                    "retweeted_guid",
                    "guid"
                ]
            },
            "output": {
                "label": "输出条件",
                "datatype": "string",
                "outputtype": "1",
                "data_limit": 0,
                "count": "10",
                "orderby": "created_at",
                "ordertype": "desc",
                "pageable": true
            },
            "contrast": null,
            "classifyquery": null,
            "filtervalue": [
                {
                    "fieldname": "organization",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "string",
                        "value": "发改委"
                    }
                },
                {
                    "fieldname": "organization",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "string",
                        "value": "人民网"
                    }
                },
                {
                    "fieldname": "organization",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "string",
                        "value": "中石油"
                    }
                }
            ],
            "distinct": {
                "label": "结果唯一",
                "datatype": "string",
                "distinctfield": ""
            }
        }
    }
}';
//叠加分析模型
$url = "http://192.168.0.102/model/requestdata.php?isoverlay=1&hasformjson=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
$jsoninfo = '[{"elementid":-2,"instanceid":-1,"instancetype":1,"modelname":"用户","referencedata":false,"secondaryyaxis":false,"showid":"smallmulticolumn3d","referencedataratio":"","datajson":{"version":1040,"modelid":31,"isdefaultrelation":true,"filterrelation":{"opt":"and","filterlist":[],"fields":[0]},"filter":{"weiboid":{"label":"微博ID","datatype":"string"},"weibourl":{"label":"微博URL","datatype":"string"},"oristatus":{"label":"原创ID","datatype":"string"},"oristatusurl":{"label":"原创URL","datatype":"string"},"oristatus_username":{"label":"昵称查转发","datatype":"string"},"oristatus_userid":{"label":"用户名查转发","datatype":"value_text_object"},"repost_url":{"label":"转发URL","datatype":"string"},"repost_username":{"label":"昵称查原创","datatype":"string"},"repost_userid":{"label":"用户名查原创","datatype":"value_text_object"},"searchword":{"label":"关键词","datatype":"string"},"organization":{"label":"机构","datatype":"string"},"account":{"label":"@用户","datatype":"value_text_object"},"userid":{"label":"用户名","datatype":"value_text_object"},"weibotopic":{"label":"微博话题","datatype":"string"},"weibotopickeyword":{"label":"微博话题关键词","datatype":"string"},"weibotopiccombinword":{"label":"微博话题短语","datatype":"string"},"NRN":{"label":"人名","datatype":"string"},"topic":{"label":"短语","datatype":"string"},"business":{"label":"行业","datatype":"value_text_object"},"repostsnum":{"label":"转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"commentsnum":{"label":"评论数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reposts_count":{"label":"总转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"direct_reposts_count":{"label":"直接转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reach_count":{"label":"总到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"followers_count":{"label":"直接到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"repost_trend_cursor":{"label":"转发所处层级","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"areauser":{"label":"用户地区","datatype":"value_text_object"},"areamentioned":{"label":"提及地区","datatype":"value_text_object"},"createdtime":{"label":"创建时间","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"nearlytime":{"label":"相对今天","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"beforetime":{"label":"时间段","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"untiltime":{"label":"日历时间","datatype":"time_dynamic_range","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_year":{"label":"创建时间(年)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_month":{"label":"创建时间(月)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_day":{"label":"创建时间(日)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_hour":{"label":"创建时间(时)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_weekday":{"label":"创建时间(周)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"emotion":{"label":"情感关键词","datatype":"string"},"emoCombin":{"label":"情感短语","datatype":"string"},"emoNRN":{"label":"情感人名","datatype":"string"},"emoOrganization":{"label":"情感机构","datatype":"string"},"emoTopic":{"label":"情感微博话题","datatype":"string"},"emoTopicKeyword":{"label":"情感微博话题关键词","datatype":"string"},"emoTopicCombinWord":{"label":"情感微博话题短语","datatype":"string"},"emoAccount":{"label":"@用户情感","datatype":"value_text_object"},"emoBusiness":{"label":"行业情感","datatype":"value_text_object"},"emoAreamentioned":{"label":"地区情感","datatype":"value_text_object"},"weibotype":{"label":"微博类型","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"source":{"label":"应用来源","datatype":"string"},"hostdomain":{"label":"主机域名","datatype":"string"},"sourceid":{"label":"数据来源","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"username":{"label":"作者昵称","datatype":"string"},"verified":{"label":"认证","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verified_type":{"label":"认证类型","datatype":"int"},"haspicture":{"label":"含有图片","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verifiedreason":{"label":"认证原因","datatype":"string"},"registertime":{"label":"博龄","datatype":"gaprange","limit":[{"value":{"maxvalue":null,"minvalue":null,"gap":"year"},"type":"gaprange","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"sex":{"label":"作者性别","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"originalcontent":{"label":"原文内容","datatype":"string"},"digestcontent":{"label":"摘要内容","datatype":"string"},"description":{"label":"简介","datatype":"string"},"ancestor_text":{"label":"上层转发关键词","datatype":"string"},"ancestor_organization":{"label":"上层转发机构","datatype":"string"},"ancestor_account":{"label":"上层转发@用户","datatype":"value_text_object"},"ancestor_wb_topic":{"label":"上层转发微博话题","datatype":"string"},"ancestor_wb_topic_keyword":{"label":"上层转发微博话题关键词","datatype":"string"},"ancestor_wb_topic_combinWord":{"label":"上层转发微博话题短语","datatype":"string"},"ancestor_NRN":{"label":"上层转发人名","datatype":"string"},"ancestor_combinWord":{"label":"上层转发短语","datatype":"string"},"ancestor_business":{"label":"上层转发行业","datatype":"value_text_object"},"ancestor_areamentioned":{"label":"上层转发提及地区","datatype":"value_text_object"},"ancestor_emotion":{"label":"上层转发情感关键词","datatype":"string"},"ancestor_emoCombin":{"label":"上层转发情感短语","datatype":"string"},"ancestor_emoNRN":{"label":"上层转发情感人名","datatype":"string"},"ancestor_emoOrganization":{"label":"上层转发情感机构","datatype":"string"},"ancestor_emoTopic":{"label":"上层转发情感微博话题","datatype":"string"},"ancestor_emoTopicKeyword":{"label":"上层转发情感微博话题关键词","datatype":"string"},"ancestor_emoTopicCombinWord":{"label":"上层转发情感微博话题短语","datatype":"string"},"ancestor_emoAccount":{"label":"上层转发@用户情感","datatype":"value_text_object"},"ancestor_emoBusiness":{"label":"上层转发行业情感","datatype":"value_text_object"},"ancestor_emoAreamentioned":{"label":"上层转发地区情感","datatype":"value_text_object"},"ancestor_url":{"label":"上层转发URL","datatype":"string"},"ancestor_host_domain":{"label":"上层转发主机域名","datatype":"string"},"ancestor_similar":{"label":"上层转发摘要内容","datatype":"string"}},"facet":{"label":"分组统计","datatype":"string","limit":[{"value":"text","type":"exact","repeat":1},{"value":"organization","type":"exact","repeat":1},{"value":"wb_topic","type":"exact","repeat":1},{"value":"wb_topic_keyword","type":"exact","repeat":1},{"value":"wb_topic_combinWord","type":"exact","repeat":1},{"value":"account","type":"exact","repeat":1},{"value":"country","type":"exact","repeat":1},{"value":"country_code","type":"exact","repeat":1},{"value":"province_code","type":"exact","repeat":1},{"value":"city","type":"exact","repeat":1},{"value":"city_code","type":"exact","repeat":1},{"value":"district","type":"exact","repeat":1},{"value":"district_code","type":"exact","repeat":1},{"value":"business","type":"exact","repeat":1},{"value":"url","type":"exact","repeat":1},{"value":"created_at","type":"exact","repeat":1},{"value":"retweeted_status","type":"exact","repeat":1},{"value":"screen_name","type":"exact","repeat":1},{"value":"reposts_count","type":"exact","repeat":1},{"value":"comments_count","type":"exact","repeat":1},{"value":"register_time","type":"exact","repeat":1},{"value":"sex","type":"exact","repeat":1},{"value":"verify","type":"exact","repeat":1},{"value":"has_picture","type":"exact","repeat":1},{"value":"emotion","type":"exact","repeat":1},{"value":"originalText","type":"exact","repeat":1},{"value":"similar","type":"exact","repeat":1},{"value":"verified_reason","type":"exact","repeat":1},{"value":"verified_type","type":"exact","repeat":1},{"value":"description","type":"exact","repeat":1},{"value":"source","type":"exact","repeat":1},{"value":"emoCombin","type":"exact","repeat":1},{"value":"emoOrganization","type":"exact","repeat":1},{"value":"emoTopic","type":"exact","repeat":1},{"value":"emoTopicKeyword","type":"exact","repeat":1},{"value":"emoTopicCombinWord","type":"exact","repeat":1},{"value":"emoBusiness","type":"exact","repeat":1},{"value":"emoCountry","type":"exact","repeat":1},{"value":"emoProvince","type":"exact","repeat":1},{"value":"emoCity","type":"exact","repeat":1},{"value":"emoDistrict","type":"exact","repeat":1},{"value":"userid","type":"exact","repeat":1},{"value":"content_type","type":"exact","repeat":1},{"value":"host_domain","type":"exact","repeat":1},{"value":"total_reposts_count","type":"exact","repeat":1},{"value":"direct_reposts_count","type":"exact","repeat":1},{"value":"followers_count","type":"exact","repeat":1},{"value":"repost_trend_cursor","type":"exact","repeat":1},{"value":"emoAccount","type":"exact","repeat":1},{"value":"emoNRN","type":"exact","repeat":1},{"value":"NRN","type":"exact","repeat":1},{"value":"province","type":"exact","repeat":1},{"value":"combinWord","type":"exact","repeat":1},{"value":"total_reach_count","type":"exact","repeat":1},{"value":"ancestor_text","type":"exact","repeat":1},{"value":"ancestor_NRN","type":"exact","repeat":1},{"value":"ancestor_wb_topic","type":"exact","repeat":1},{"value":"ancestor_country","type":"exact","repeat":1},{"value":"ancestor_district","type":"exact","repeat":1},{"value":"ancestor_emoNRN","type":"exact","repeat":1},{"value":"ancestor_emoTopicKeyword","type":"exact","repeat":1},{"value":"ancestor_emoBusiness","type":"exact","repeat":1},{"value":"ancestor_emoCity","type":"exact","repeat":1},{"value":"ancestor_host_domain","type":"exact","repeat":1},{"value":"ancestor_similar","type":"exact","repeat":1},{"value":"ancestor_emoDistrict","type":"exact","repeat":1},{"value":"ancestor_emoCountry","type":"exact","repeat":1},{"value":"ancestor_emoTopicCombinWord","type":"exact","repeat":1},{"value":"ancestor_emoOrganization","type":"exact","repeat":1},{"value":"ancestor_emotion","type":"exact","repeat":1},{"value":"ancestor_province","type":"exact","repeat":1},{"value":"ancestor_combinWord","type":"exact","repeat":1},{"value":"ancestor_wb_topic_keyword","type":"exact","repeat":1},{"value":"ancestor_organization","type":"exact","repeat":1},{"value":"ancestor_account","type":"exact","repeat":1},{"value":"ancestor_wb_topic_combinWord","type":"exact","repeat":1},{"value":"ancestor_business","type":"exact","repeat":1},{"value":"ancestor_city","type":"exact","repeat":1},{"value":"ancestor_emoCombin","type":"exact","repeat":1},{"value":"ancestor_emoTopic","type":"exact","repeat":1},{"value":"ancestor_emoAccount","type":"exact","repeat":1},{"value":"ancestor_emoProvince","type":"exact","repeat":1},{"value":"ancestor_url","type":"exact","repeat":1},{"value":"created_year","type":"exact","repeat":1},{"value":"created_hour","type":"exact","repeat":1},{"value":"created_day","type":"exact","repeat":1},{"value":"created_month","type":"exact","repeat":1},{"value":"created_weekday","type":"exact","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"filterlimit":{"label":"输出过滤","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"field":[{"name":"account","includeconfig":{"showother":0,"alias":""},"filter":[],"facettype":101,"allcount":false,"featureconfig":{"showother":0,"alias":""},"isfeature":0,"feature":[]}],"range":[]},"select":{"label":"查询字段","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":0,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"value":["bmiddle_pic","comments_count","content_type","created_at","description","followers_count","friends_count","guid","id","location","mid","profile_image_url","reposts_count","retweeted_mid","retweeted_status","screen_name","sex","source","sourceid","statuses_count","text","thumbnail_pic","userid","verified_reason","verified_type","verify"]},"output":{"label":"输出条件","datatype":"string","limit":[{"value":"comments_count","repeat":1,"type":"exact"},{"value":"reposts_count","repeat":1,"type":"exact"}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"outputtype":"2","countlimit":{"label":"数据量限制","datatype":"range","limit":[{"value":{"maxvalue":100,"minvalue":0},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"data_limit":0,"count":10,"ordertype":"desc","pageable":false},"contrast":null,"classifyquery":null,"filtervalue":[{"fieldname":"nearlytime","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"time_dynamic_state","value":{"start":"1","startgap":"month","datestate":"now","timestate":"now"}}}],"download_DataLimit":1000,"download_DataLimit_limitcontrol":-1,"download_FieldLimit_limitcontrol":-1,"allowDownload":true,"download_FieldLimit":[{"text":"序号","value":"number"},{"text":"统计结果","value":"facet"},{"text":"文章数","value":"frq"},{"text":"转发数","value":"reposts_count"},{"text":"评论数","value":"comments_count"},{"text":"讨论数","value":"discuss_count"},{"text":"直接转发数","value":"direct_reposts_count"},{"text":"总转发数","value":"total_reposts_count"},{"text":"直接到达数","value":"followers_count"},{"text":"总到达数","value":"total_reach_count"}],"distinct":{"label":"结果唯一","datatype":"string","limit":[{"value":"screen_name","repeat":1,"type":"exact"}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"distinctfield":""}}},{"datajson":{"instanceid":-1,"elements":[{"elementid":-3,"datajson":{"version":1040,"modelid":51,"isdefaultrelation":true,"filterrelation":{"opt":"and","filterlist":[],"fields":[0]},"filter":{"weiboid":{"label":"微博ID","datatype":"string"},"weibourl":{"label":"微博URL","datatype":"string"},"oristatus":{"label":"原创ID","datatype":"string"},"oristatusurl":{"label":"原创URL","datatype":"string"},"oristatus_username":{"label":"昵称查转发","datatype":"string"},"oristatus_userid":{"label":"用户名查转发","datatype":"value_text_object"},"repost_url":{"label":"转发URL","datatype":"string"},"repost_username":{"label":"昵称查原创","datatype":"string"},"repost_userid":{"label":"用户名查原创","datatype":"value_text_object"},"keyword":{"label":"关键词","datatype":"string"},"organization":{"label":"机构","datatype":"string"},"account":{"label":"@用户","datatype":"value_text_object"},"userid":{"label":"用户名","datatype":"value_text_object"},"weibotopic":{"label":"微博话题","datatype":"string"},"weibotopickeyword":{"label":"微博话题关键词","datatype":"string"},"weibotopiccombinword":{"label":"微博话题短语","datatype":"string"},"NRN":{"label":"人名","datatype":"string"},"topic":{"label":"短语","datatype":"string"},"business":{"label":"行业","datatype":"value_text_object"},"repostsnum":{"label":"转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"commentsnum":{"label":"评论数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reposts_count":{"label":"总转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"direct_reposts_count":{"label":"直接转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reach_count":{"label":"总到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"followers_count":{"label":"直接到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"repost_trend_cursor":{"label":"转发所处层级","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"areauser":{"label":"用户地区","datatype":"value_text_object"},"areamentioned":{"label":"提及地区","datatype":"value_text_object"},"createdtime":{"label":"创建时间","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"nearlytime":{"label":"相对今天","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"beforetime":{"label":"时间段","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"untiltime":{"label":"日历时间","datatype":"time_dynamic_range","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_year":{"label":"创建时间(年)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_month":{"label":"创建时间(月)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_day":{"label":"创建时间(日)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_hour":{"label":"创建时间(时)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_weekday":{"label":"创建时间(周)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"emotion":{"label":"情感关键词","datatype":"string"},"emoCombin":{"label":"情感短语","datatype":"string"},"emoNRN":{"label":"情感人名","datatype":"string"},"emoOrganization":{"label":"情感机构","datatype":"string"},"emoTopic":{"label":"情感微博话题","datatype":"string"},"emoTopicKeyword":{"label":"情感微博话题关键词","datatype":"string"},"emoTopicCombinWord":{"label":"情感微博话题短语","datatype":"string"},"emoAccount":{"label":"@用户情感","datatype":"value_text_object"},"emoBusiness":{"label":"行业情感","datatype":"value_text_object"},"emoAreamentioned":{"label":"地区情感","datatype":"value_text_object"},"weibotype":{"label":"微博类型","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"source":{"label":"应用来源","datatype":"string"},"hostdomain":{"label":"主机域名","datatype":"string"},"sourceid":{"label":"数据来源","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"username":{"label":"作者昵称","datatype":"string"},"verified":{"label":"认证","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verified_type":{"label":"认证类型","datatype":"int"},"haspicture":{"label":"含有图片","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"registertime":{"label":"博龄","datatype":"gaprange","limit":[{"value":{"maxvalue":null,"minvalue":null,"gap":"day"},"type":"gaprange","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":1,"limitcontrol":-1,"required":false},"sex":{"label":"作者性别","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verifiedreason":{"label":"认证原因","datatype":"string"},"description":{"label":"简介","datatype":"string"},"originalcontent":{"label":"原文内容","datatype":"string"},"digestcontent":{"label":"摘要内容","datatype":"string"},"ancestor_text":{"label":"上层转发关键词","datatype":"string"},"ancestor_organization":{"label":"上层转发机构","datatype":"string"},"ancestor_account":{"label":"上层转发@用户","datatype":"value_text_object"},"ancestor_wb_topic":{"label":"上层转发微博话题","datatype":"string"},"ancestor_wb_topic_keyword":{"label":"上层转发微博话题关键词","datatype":"string"},"ancestor_wb_topic_combinWord":{"label":"上层转发微博话题短语","datatype":"string"},"ancestor_NRN":{"label":"上层转发人名","datatype":"string"},"ancestor_combinWord":{"label":"上层转发短语","datatype":"string"},"ancestor_business":{"label":"上层转发行业","datatype":"value_text_object"},"ancestor_areamentioned":{"label":"上层转发提及地区","datatype":"value_text_object"},"ancestor_emotion":{"label":"上层转发情感关键词","datatype":"string"},"ancestor_emoCombin":{"label":"上层转发情感短语","datatype":"string"},"ancestor_emoNRN":{"label":"上层转发情感人名","datatype":"string"},"ancestor_emoOrganization":{"label":"上层转发情感机构","datatype":"string"},"ancestor_emoTopic":{"label":"上层转发情感微博话题","datatype":"string"},"ancestor_emoTopicKeyword":{"label":"上层转发情感微博话题关键词","datatype":"string"},"ancestor_emoTopicCombinWord":{"label":"上层转发情感微博话题短语","datatype":"string"},"ancestor_emoAccount":{"label":"上层转发@用户情感","datatype":"value_text_object"},"ancestor_emoBusiness":{"label":"上层转发行业情感","datatype":"value_text_object"},"ancestor_emoAreamentioned":{"label":"上层转发地区情感","datatype":"value_text_object"},"ancestor_url":{"label":"上层转发URL","datatype":"string"},"ancestor_host_domain":{"label":"上层转发主机域名","datatype":"string"},"ancestor_similar":{"label":"上层转发摘要内容","datatype":"string"}},"facet":{"label":"分组统计","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":null,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"filterlimit":{"label":"输出过滤","datatype":"string"},"field":[],"range":[]},"select":{"label":"查询字段","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":0,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":0,"limitcontrol":-1,"required":false,"value":["account","bmiddle_pic","comments_count","content_type","created_at","description","followers_count","friends_count","guid","id","mid","profile_image_url","reposts_count","retweeted_guid","retweeted_mid","retweeted_status","screen_name","sex","source","sourceid","statuses_count","text","thumbnail_pic","userid","verified_reason","verified_type","verify"]},"output":{"label":"输出条件","datatype":"string","limit":[{"value":"comments_count","repeat":1,"type":"exact"},{"value":"reposts_count","repeat":1,"type":"exact"},{"value":"created_at","repeat":1,"type":"exact"},{"value":"direct_reposts_count","repeat":1,"type":"exact"},{"value":"total_reposts_count","repeat":1,"type":"exact"}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"outputtype":"1","countlimit":{"label":"数据量限制","datatype":"range","limit":[{"value":{"maxvalue":100,"minvalue":0},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"data_limit":0,"count":"10","orderby":"created_at","ordertype":"desc","pageable":true},"contrast":null,"classifyquery":null,"filtervalue":[{"fieldname":"nearlytime","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"time_dynamic_state","value":{"start":"1","startgap":"month","datestate":"now","timestate":"now"}}}],"download_DataLimit":1000,"download_DataLimit_limitcontrol":-1,"download_FieldLimit_limitcontrol":-1,"allowDownload":true,"download_FieldLimit":[],"distinct":{"label":"结果唯一","datatype":"string","limit":[{"value":"screen_name","repeat":1,"type":"exact"}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"distinctfield":""}}}],"pinrelation":[{"instanceid":-1,"inelementid":-4,"inpinid":1,"inputdata":{"value":"account","text":"@用户","opt":"or","datatype":"value_text_object","isfeature":0},"outelementid":-3,"outpinid":1,"outputdata":{"pintype":"dynamic","datatype":"value_text_object","exclude":false,"text":"用户名","outputfield":"userid","value":{"start":null,"end":null}},"overlayindex":"2"},{"instanceid":-1,"inelementid":-4,"inpinid":1,"inputdata":{"value":"account","text":"@用户","opt":"or","datatype":"value_text_object","isfeature":0},"outelementid":-3,"outpinid":1,"outputdata":{"pintype":"dynamic","datatype":"value_text_object","exclude":false,"text":"用户名","outputfield":"userid","value":{"start":null,"end":null}},"overlayindex":"2"}],"render":{"elementid":-4,"datajson":{"version":1040,"modelid":31,"isdefaultrelation":true,"filterrelation":{"opt":"and","filterlist":[],"fields":[0,1]},"filter":{"weiboid":{"label":"微博ID","datatype":"string"},"weibourl":{"label":"微博URL","datatype":"string"},"oristatus":{"label":"原创ID","datatype":"string"},"oristatusurl":{"label":"原创URL","datatype":"string"},"oristatus_username":{"label":"昵称查转发","datatype":"string"},"oristatus_userid":{"label":"用户名查转发","datatype":"value_text_object"},"repost_url":{"label":"转发URL","datatype":"string"},"repost_username":{"label":"昵称查原创","datatype":"string"},"repost_userid":{"label":"用户名查原创","datatype":"value_text_object"},"searchword":{"label":"关键词","datatype":"string"},"organization":{"label":"机构","datatype":"string"},"account":{"label":"@用户","datatype":"value_text_object"},"userid":{"label":"用户名","datatype":"value_text_object"},"weibotopic":{"label":"微博话题","datatype":"string"},"weibotopickeyword":{"label":"微博话题关键词","datatype":"string"},"weibotopiccombinword":{"label":"微博话题短语","datatype":"string"},"NRN":{"label":"人名","datatype":"string"},"topic":{"label":"短语","datatype":"string"},"business":{"label":"行业","datatype":"value_text_object"},"repostsnum":{"label":"转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"commentsnum":{"label":"评论数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reposts_count":{"label":"总转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"direct_reposts_count":{"label":"直接转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reach_count":{"label":"总到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"followers_count":{"label":"直接到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"repost_trend_cursor":{"label":"转发所处层级","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"areauser":{"label":"用户地区","datatype":"value_text_object"},"areamentioned":{"label":"提及地区","datatype":"value_text_object"},"createdtime":{"label":"创建时间","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"nearlytime":{"label":"相对今天","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"beforetime":{"label":"时间段","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"untiltime":{"label":"日历时间","datatype":"time_dynamic_range","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_year":{"label":"创建时间(年)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_month":{"label":"创建时间(月)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_day":{"label":"创建时间(日)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_hour":{"label":"创建时间(时)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_weekday":{"label":"创建时间(周)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"emotion":{"label":"情感关键词","datatype":"string"},"emoCombin":{"label":"情感短语","datatype":"string"},"emoNRN":{"label":"情感人名","datatype":"string"},"emoOrganization":{"label":"情感机构","datatype":"string"},"emoTopic":{"label":"情感微博话题","datatype":"string"},"emoTopicKeyword":{"label":"情感微博话题关键词","datatype":"string"},"emoTopicCombinWord":{"label":"情感微博话题短语","datatype":"string"},"emoAccount":{"label":"@用户情感","datatype":"value_text_object"},"emoBusiness":{"label":"行业情感","datatype":"value_text_object"},"emoAreamentioned":{"label":"地区情感","datatype":"value_text_object"},"weibotype":{"label":"微博类型","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"source":{"label":"应用来源","datatype":"string"},"hostdomain":{"label":"主机域名","datatype":"string"},"sourceid":{"label":"数据来源","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"username":{"label":"作者昵称","datatype":"string"},"verified":{"label":"认证","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verified_type":{"label":"认证类型","datatype":"int"},"haspicture":{"label":"含有图片","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verifiedreason":{"label":"认证原因","datatype":"string"},"registertime":{"label":"博龄","datatype":"gaprange","limit":[{"value":{"maxvalue":null,"minvalue":null,"gap":"year"},"type":"gaprange","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"sex":{"label":"作者性别","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"originalcontent":{"label":"原文内容","datatype":"string"},"digestcontent":{"label":"摘要内容","datatype":"string"},"description":{"label":"简介","datatype":"string"},"ancestor_text":{"label":"上层转发关键词","datatype":"string"},"ancestor_organization":{"label":"上层转发机构","datatype":"string"},"ancestor_account":{"label":"上层转发@用户","datatype":"value_text_object"},"ancestor_wb_topic":{"label":"上层转发微博话题","datatype":"string"},"ancestor_wb_topic_keyword":{"label":"上层转发微博话题关键词","datatype":"string"},"ancestor_wb_topic_combinWord":{"label":"上层转发微博话题短语","datatype":"string"},"ancestor_NRN":{"label":"上层转发人名","datatype":"string"},"ancestor_combinWord":{"label":"上层转发短语","datatype":"string"},"ancestor_business":{"label":"上层转发行业","datatype":"value_text_object"},"ancestor_areamentioned":{"label":"上层转发提及地区","datatype":"value_text_object"},"ancestor_emotion":{"label":"上层转发情感关键词","datatype":"string"},"ancestor_emoCombin":{"label":"上层转发情感短语","datatype":"string"},"ancestor_emoNRN":{"label":"上层转发情感人名","datatype":"string"},"ancestor_emoOrganization":{"label":"上层转发情感机构","datatype":"string"},"ancestor_emoTopic":{"label":"上层转发情感微博话题","datatype":"string"},"ancestor_emoTopicKeyword":{"label":"上层转发情感微博话题关键词","datatype":"string"},"ancestor_emoTopicCombinWord":{"label":"上层转发情感微博话题短语","datatype":"string"},"ancestor_emoAccount":{"label":"上层转发@用户情感","datatype":"value_text_object"},"ancestor_emoBusiness":{"label":"上层转发行业情感","datatype":"value_text_object"},"ancestor_emoAreamentioned":{"label":"上层转发地区情感","datatype":"value_text_object"},"ancestor_url":{"label":"上层转发URL","datatype":"string"},"ancestor_host_domain":{"label":"上层转发主机域名","datatype":"string"},"ancestor_similar":{"label":"上层转发摘要内容","datatype":"string"}},"facet":{"label":"分组统计","datatype":"string","limit":[{"value":"text","type":"exact","repeat":1},{"value":"organization","type":"exact","repeat":1},{"value":"wb_topic","type":"exact","repeat":1},{"value":"wb_topic_keyword","type":"exact","repeat":1},{"value":"wb_topic_combinWord","type":"exact","repeat":1},{"value":"account","type":"exact","repeat":1},{"value":"country","type":"exact","repeat":1},{"value":"country_code","type":"exact","repeat":1},{"value":"province_code","type":"exact","repeat":1},{"value":"city","type":"exact","repeat":1},{"value":"city_code","type":"exact","repeat":1},{"value":"district","type":"exact","repeat":1},{"value":"district_code","type":"exact","repeat":1},{"value":"business","type":"exact","repeat":1},{"value":"url","type":"exact","repeat":1},{"value":"created_at","type":"exact","repeat":1},{"value":"retweeted_status","type":"exact","repeat":1},{"value":"screen_name","type":"exact","repeat":1},{"value":"reposts_count","type":"exact","repeat":1},{"value":"comments_count","type":"exact","repeat":1},{"value":"register_time","type":"exact","repeat":1},{"value":"sex","type":"exact","repeat":1},{"value":"verify","type":"exact","repeat":1},{"value":"has_picture","type":"exact","repeat":1},{"value":"emotion","type":"exact","repeat":1},{"value":"originalText","type":"exact","repeat":1},{"value":"similar","type":"exact","repeat":1},{"value":"verified_reason","type":"exact","repeat":1},{"value":"verified_type","type":"exact","repeat":1},{"value":"description","type":"exact","repeat":1},{"value":"source","type":"exact","repeat":1},{"value":"emoCombin","type":"exact","repeat":1},{"value":"emoOrganization","type":"exact","repeat":1},{"value":"emoTopic","type":"exact","repeat":1},{"value":"emoTopicKeyword","type":"exact","repeat":1},{"value":"emoTopicCombinWord","type":"exact","repeat":1},{"value":"emoBusiness","type":"exact","repeat":1},{"value":"emoCountry","type":"exact","repeat":1},{"value":"emoProvince","type":"exact","repeat":1},{"value":"emoCity","type":"exact","repeat":1},{"value":"emoDistrict","type":"exact","repeat":1},{"value":"userid","type":"exact","repeat":1},{"value":"content_type","type":"exact","repeat":1},{"value":"host_domain","type":"exact","repeat":1},{"value":"total_reposts_count","type":"exact","repeat":1},{"value":"direct_reposts_count","type":"exact","repeat":1},{"value":"followers_count","type":"exact","repeat":1},{"value":"repost_trend_cursor","type":"exact","repeat":1},{"value":"emoAccount","type":"exact","repeat":1},{"value":"emoNRN","type":"exact","repeat":1},{"value":"NRN","type":"exact","repeat":1},{"value":"province","type":"exact","repeat":1},{"value":"combinWord","type":"exact","repeat":1},{"value":"total_reach_count","type":"exact","repeat":1},{"value":"ancestor_text","type":"exact","repeat":1},{"value":"ancestor_NRN","type":"exact","repeat":1},{"value":"ancestor_wb_topic","type":"exact","repeat":1},{"value":"ancestor_country","type":"exact","repeat":1},{"value":"ancestor_district","type":"exact","repeat":1},{"value":"ancestor_emoNRN","type":"exact","repeat":1},{"value":"ancestor_emoTopicKeyword","type":"exact","repeat":1},{"value":"ancestor_emoBusiness","type":"exact","repeat":1},{"value":"ancestor_emoCity","type":"exact","repeat":1},{"value":"ancestor_host_domain","type":"exact","repeat":1},{"value":"ancestor_similar","type":"exact","repeat":1},{"value":"ancestor_emoDistrict","type":"exact","repeat":1},{"value":"ancestor_emoCountry","type":"exact","repeat":1},{"value":"ancestor_emoTopicCombinWord","type":"exact","repeat":1},{"value":"ancestor_emoOrganization","type":"exact","repeat":1},{"value":"ancestor_emotion","type":"exact","repeat":1},{"value":"ancestor_province","type":"exact","repeat":1},{"value":"ancestor_combinWord","type":"exact","repeat":1},{"value":"ancestor_wb_topic_keyword","type":"exact","repeat":1},{"value":"ancestor_organization","type":"exact","repeat":1},{"value":"ancestor_account","type":"exact","repeat":1},{"value":"ancestor_wb_topic_combinWord","type":"exact","repeat":1},{"value":"ancestor_business","type":"exact","repeat":1},{"value":"ancestor_city","type":"exact","repeat":1},{"value":"ancestor_emoCombin","type":"exact","repeat":1},{"value":"ancestor_emoTopic","type":"exact","repeat":1},{"value":"ancestor_emoAccount","type":"exact","repeat":1},{"value":"ancestor_emoProvince","type":"exact","repeat":1},{"value":"ancestor_url","type":"exact","repeat":1},{"value":"created_year","type":"exact","repeat":1},{"value":"created_hour","type":"exact","repeat":1},{"value":"created_day","type":"exact","repeat":1},{"value":"created_month","type":"exact","repeat":1},{"value":"created_weekday","type":"exact","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"filterlimit":{"label":"输出过滤","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"field":[{"name":"account","includeconfig":{"showother":0,"alias":""},"filter":[],"facettype":101,"allcount":false,"featureconfig":{"showother":0,"alias":""},"isfeature":0,"feature":[]}],"range":[]},"select":{"label":"查询字段","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":0,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"value":["bmiddle_pic","comments_count","content_type","created_at","description","followers_count","friends_count","guid","id","location","mid","profile_image_url","reposts_count","retweeted_mid","retweeted_status","screen_name","sex","source","sourceid","statuses_count","text","thumbnail_pic","userid","verified_reason","verified_type","verify"]},"output":{"label":"输出条件","datatype":"string","limit":[{"value":"comments_count","repeat":1,"type":"exact"},{"value":"reposts_count","repeat":1,"type":"exact"}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"outputtype":"2","countlimit":{"label":"数据量限制","datatype":"range","limit":[{"value":{"maxvalue":100,"minvalue":0},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"data_limit":0,"count":10,"ordertype":"desc","pageable":false},"contrast":null,"classifyquery":null,"filtervalue":[{"fieldname":"nearlytime","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"time_dynamic_state","value":{"start":"1","startgap":"month","datestate":"now","timestate":"now"}}},{"fieldname":"account","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"dynamic","value":{"start":null,"end":null},"outelementid":-3,"outputfield":"userid"}}],"download_DataLimit":1000,"download_DataLimit_limitcontrol":-1,"download_FieldLimit_limitcontrol":-1,"allowDownload":true,"download_FieldLimit":[{"text":"序号","value":"number"},{"text":"统计结果","value":"facet"},{"text":"文章数","value":"frq"},{"text":"转发数","value":"reposts_count"},{"text":"评论数","value":"comments_count"},{"text":"讨论数","value":"discuss_count"},{"text":"直接转发数","value":"direct_reposts_count"},{"text":"总转发数","value":"total_reposts_count"},{"text":"直接到达数","value":"followers_count"},{"text":"总到达数","value":"total_reach_count"}],"distinct":{"label":"结果唯一","datatype":"string","limit":[{"value":"screen_name","repeat":1,"type":"exact"}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"distinctfield":""}}}},"instancetype":2,"modelname":"人名","referencedata":false,"secondaryyaxis":false,"showid":"smallmulticolumn3d","referencedataratio":""}]';
//$res = thirdPartyGetData($jsoninfo, $url);
//var_dump("res ", json_decode($res, true));
//var_dump("res ", $res);
