<?php
/**
 * Created by wangchaochao.
 * User: wangchaochao
 * Date: 2015/12/24
 * Time: 12:37
 */


//对象取值路径对象定义常常量-属性数据类型-数组
define("PROPERTY_DATA_TYPE_ARRAY", 1);

//对象取值路径对象定义常常量-属性数据类型-对象
define("PROPERTY_DATA_TYPE_OBJ", 2);

define("INNER_PARAM_CUR_DATE", "cur_date_time");
define("INNER_PARAM_CUR_PAGE_URL", "curPageUrl");
define("INNER_PARAM_DATE_TEMP", "y-m-d h:i:s");
define("MUILTIP_FILE_TAG", "#FILENAME:");

initLogger(LOGNAME_WEBAPI);//初始化日志配置

function getUrlFromTaskParam(&$currentTaskParam, $grabDatas = NULL)
{
    global $logger;
    //*****************将任务中定义的所有参数设置大Jsconfig抓取模版中********************
    //设置爬虫任务抓取的地址规则
    //先判断有没有直接设置好值
    $taskUrls = $currentTaskParam["taskUrls"];
    $logger->debug(SELF . " " . __FUNCTION__ . " getUrlFromTaskParam ...");
    if ($taskUrls["type"] == "consts") {
        //常量字面量 或者 变量
        //常量字面量：www.baidu.com
        //变量：|childURL|
//                $url1 = "taskurl\$:\"\$<url \"%s\">\"{url:Enum(http:\/\/club.autohome.com.cn\/bbs\/forum-c-3170-1.html)}";
//                $url1 =   $taskUrls[0];
        $result = array();
        if (is_array($taskUrls["urlValues"])) {
            foreach ($taskUrls["urlValues"] as $urlVar) {
                $logger->debug(SELF . " " . __FUNCTION__ . " handle url:[" . $urlVar . "] for type:[consts].");

//            $resultValue[] = getValueFromObjWrap($grabData, $pathStruct);
                if (strpos($urlVar, '|') == 0) {
                    $paramValues = substr($urlVar, 1, strlen($urlVar) - 2);
                    $logger->debug(SELF . " " . __FUNCTION__ . " URL为变量:[" . $paramValues . "] allPathStruc: " . var_export($currentTaskParam["pathStructMap"], true));
                    $paramPathStruct = $currentTaskParam["pathStructMap"][$paramValues];
                    $souceValues = getSourceObj($currentTaskParam, $paramPathStruct);
                    //从其他地方的参数定义中提取参数
                    $curURls = getValueFromObjWrap($souceValues, $paramPathStruct);
                    if (is_array($curURls)) {
                        foreach ($curURls as $curUrlValue) {
                            $result[] = $curUrlValue;
                            $logger->debug(SELF . " " . __FUNCTION__ . " 生成当前任务的运行URLS,类型为list,增加一个URL:[" . $curUrlValue . "]");
                        }
                    } else {
                        $result[] = $curURls;
                    }

                } else {
                    $result[] = $urlVar;
                }
            }
        } else if (is_string($taskUrls["urlValues"])) {
            $result[] = $taskUrls["urlValues"];
        } else {
            $exceptionStr = "不支持的变量[]类型:[" . gettype($taskUrls["urlValues"]) . "]. 处理父任务的url异常!";
            $logger->error($exceptionStr);
            throw new Exception($exceptionStr);
        }
        $logger->debug(SELF . " " . __FUNCTION__ . " 处理父任务的url成功,URL:[" . var_export($result, true) . "].");
        return $result;
    } else if ($taskUrls["type"] == "gen") {
        //动态生成
        $logger->debug(SELF . " " . __FUNCTION__ . " url不存在需要动态生成.currentUrlSize:[0]，need generater!");
        //"taskurl$:\"http://i.autohome.com.cn/$<iduser \"%s\">/info\"{iduser:Enum(|user.id|) nameuser:Enum(|user.name|) }";

        if (empty($taskUrls["urlValues"])) {
            $logger->error(SELF . " " . __FUNCTION__ . " urlValues is null!");
            throw new Exception("getUrlFromTaskParam exception,urlValues is null! urlValues:[" . var_export($taskUrls["urlValues"], true) . "].");
        }

        if (count($taskUrls["urlValues"]) > 1) {
            $logger->error(SELF . " " . __FUNCTION__ . " urlValues length can not lager than 1!");
            throw new Exception("getUrlFromTaskParam exception,urlValues length can not lager than 1! urlValues:[" . var_export($taskUrls["urlValues"], true) . "].");
        }

        $logger->info(SELF . " " . __FUNCTION__ . " 开始处理当前任务的URL:[" . $taskUrls["urlValues"][0] . "].");
        $pathStructMap = $currentTaskParam["pathStructMap"];
        if (empty($pathStructMap)) {
            $logger->error(SELF . " " . __FUNCTION__ . " pathStructMap is null!");
            throw new Exception("getUrlFromTaskParam exception,pathStructMap null!");
        }
//                $logger->debug(SELF." ".__FUNCTION__." pathStructMap:[".var_export($pathStructMap,true)."].");

        //替换Enum类型的变量
        $taskUrls["urlValues"][0] = replaceParam4Url($taskUrls["urlValues"][0], $currentTaskParam, $pathStructMap, $grabDatas);
        //替换所有Time类型的变量
        $taskUrls["urlValues"][0] = replaceNumberTimeParam4Url("Time", $taskUrls["urlValues"][0], $currentTaskParam, $pathStructMap, $grabDatas);
        //替换所有Number类型的变量
        $taskUrls["urlValues"][0] = replaceNumberTimeParam4Url("Number", $taskUrls["urlValues"][0], $currentTaskParam, $pathStructMap, $grabDatas);
        $taskUrls["urlValues"][0] = replaceObjParam4Url($taskUrls["urlValues"][0], $currentTaskParam, $pathStructMap, $grabDatas);
        $urls = $taskUrls["urlValues"][0];
        $logger->debug(SELF . " " . __FUNCTION__ . " 处理URL中的参数成功，处理结果:[" . $urls . "].");
    } else {
        // 错误类型
        $logger->error("不支持的URL类型:[" . $taskUrls["type"] . "]. 生成任务URL错误!");
        throw new Exception("不支持的URL类型:[" . $taskUrls["type"] . "]. 生成任务URL错误!");
    }
    $logger->debug(SELF . " " . __FUNCTION__ . " getUrlFromTaskParam ok,result:[" . var_export($urls, true) . "].");
    return $urls;
}


function genChildUrlFromParentTaskParam($currentTaskParam, $grabDatas = NULL)
{
    global $logger;

    $logger->debug(SELF . " " . __FUNCTION__ . " gen Child Url From Parent TaskParam ...");
    //*****************将任务中定义的所有参数设置大Jsconfig抓取模版中********************
    //设置爬虫任务抓取的地址规则
    //先判断有没有直接设置好值
    $taskGenConf = $currentTaskParam["taskGenConf"]["childTaskUrl"];
    if ($taskGenConf["type"] == "consts") {
        throw new Exception("genChildUrlFromParentTaskParam error,type:[" . $taskGenConf["type"] . "].");
    } else if ($taskGenConf["type"] == "gen") {
        //动态生成
        if (empty($taskGenConf["templ"])) {
            $logger->error(SELF . " " . __FUNCTION__ . " templ is null!");
            throw new Exception("genChildUrlFromParentTaskParam exception,templ is null! urlValues:[" . var_export($taskGenConf, true) . "].");
        }
        $tempUrl = $taskGenConf["templ"];
        $logger->debug(SELF . " " . __FUNCTION__ . " genChildUrlFromParentTaskParam .templ:[" . $tempUrl . "].");
        $pathStructMap = $currentTaskParam["pathStructMap"];
        if (empty($pathStructMap)) {
            $logger->error(SELF . " " . __FUNCTION__ . " pathStructMap is null!");
            throw new Exception("genChildUrlFromParentTaskParam exception,pathStructMap null!");
        }

        //替换Enum类型的变量
        $tempUrl = replaceParam4Url($tempUrl, $currentTaskParam, $pathStructMap, $grabDatas);
        //替换所有Time类型的变量
        $tempUrl = replaceNumberTimeParam4Url("Time", $tempUrl, $currentTaskParam, $pathStructMap, $grabDatas);
        //替换所有Number类型的变量
        $tempUrl = replaceNumberTimeParam4Url("Number", $tempUrl, $currentTaskParam, $pathStructMap, $grabDatas);
        $tempUrl = replaceObjParam4Url($tempUrl, $currentTaskParam, $pathStructMap, $grabDatas);

        $logger->debug(SELF . " " . __FUNCTION__ . " 处理URL中的参数成功，处理结果:[" . $tempUrl . "].");
    } else {
        // 错误类型
        $logger->error("不支持的URL类型:[" . $taskGenConf["type"] . "]. 生成子任务URL错误!");
        throw new Exception("不支持的URL类型:[" . $taskGenConf["type"] . "]. 生成子任务URL错误!");
    }
    $logger->debug(SELF . " " . __FUNCTION__ . " genChildUrlFromParentTaskParam ok,result:[" . var_export($tempUrl, true) . "].");
    return $tempUrl;
}


//taskurl$:\"http://i.autohome.com.cn/$/info\"{namestudentclass:Enum(wangchao,zhansan,lisi) idstudentclass:Enum(0001,0002,0003) }
//$urlrule = "taskurl$:\"http://i.autohome.com.cn/$<idstudentclass \"%s\">/info\"{namestudentclass:Enum(|class.student.name|)  idstudentclass:Enum(|class.student.id|)}";

//$urlrule = "taskurl$:\"http://i.autohome.com.cn/$<idstudentclass \"%s\">/info\"{namestudentclass:Enum(|class.student.name|)  idstudentclass:Enum(|class.student.id|)}";


function getValue4UrlReplace($currentTaskParam, $pathStruct, $grabDatas = NULL)
{
    if ($pathStruct["paramSource"] == 5) {
        //从抓取数据中获取值，$grabDatas有可能是list
        //抓取数据不为空
        if (!empty($grabDatas)) {
            $resultValue = array();
            if (gettype($grabDatas) == "array") {
                foreach ($grabDatas as $grabData) {
                    $resultValue[] = getValueFromObjWrap($grabData, $pathStruct);
                }
            } else {
                $resultValue[] = getValueFromObjWrap($grabDatas, $pathStruct);
            }
        } else {
            return null;
        }
    } else {
        //根据参数来源类型获取参数数据
        //｛1:表示来自常量 2:表示来自变量，即参数定义 3:表示来自运行内置参数，如|cur_data|
        // 4:来自父参数} 在没有指定参数来源时候默认从当任务配置的变量定义中获取该参数
        $sourceObj = getSourceObj($currentTaskParam, $pathStruct);
        //获取变量$valuepath的值，如果为多值则用","分割
        $paramValue = getValueFromObjWrap($sourceObj, $pathStruct);
        return $paramValue;
    }
}

function replaceParam4Url($urlrule, $currentTaskParam, $pathStructMap, $grabDatas = NULL)
{
    global $logger;

    preg_match_all("/(\w*):Enum\(\|([^\)]+)\|\)/", $urlrule, $matches);
    $logger->debug("开始替换Enum变量. 原始URL:[" . $urlrule . "] . ");

    if (count($matches[0]) > 0) {
        $paramsNedRepl = $matches[2]; //需要从变量中取值

        $replValues = array();
        $replKeys = array();

        //数组的长度即为Enum变量的个数
        $idx = 0;
        foreach ($paramsNedRepl as $key => $valuepathId) {
            $logger->debug("开始处理变量路径:[" . $valuepathId . "] ...");

//            $logger->debug("开始处理变量路径:[" . $valuepathId . "] pathStruct:[" . var_export($pathStructMap[$valuepathId], true) . "].");

//            $logger->debug("paramsDef:[" . var_export($currentTaskParam["paramsDef"], true) . "].");

//            //根据参数来源类型获取参数数据
//            //｛1:表示来自常量 2:表示来自变量，即参数定义 3:表示来自运行内置参数，如|cur_data|
//            // 4:来自父参数} 在没有指定参数来源时候默认从当任务配置的变量定义中获取该参数
//            $sourceObj = getSourceObj($currentTaskParam, $pathStructMap[$valuepathId]);
//
//            //获取变量$valuepath的值，如果为多值则用","分割
//            $paramValue = getValueFromObjWrap($sourceObj, $pathStructMap[$valuepathId]);
            $paramValue = getValue4UrlReplace($currentTaskParam, $pathStructMap[$valuepathId], $grabDatas);

            $logger->debug("get prop:[" . $valuepathId . "] value:[" . var_export($paramValue, true) . "].");


            //当前路径指定的值为空，不能生成URL直接记录错误日志，推出
            if (empty($paramValue) && $paramValue !== 0) {
                $sourceObj = empty($grabDatas) ? $currentTaskParam : $grabDatas;
                $logger->debug("prop:[" . $valuepathId . "not set or null in obj:[" . var_export($sourceObj, true) . "].");
                throw new Exception("prop:[" . $valuepathId . "not set or null in obj:[" . var_export($sourceObj, true) . "].");
            }

            $valuepathResStr = "";
            //判断该属性的值是否为数组
            if (is_array($paramValue)) {
                $valuepathResStr = implode(",", $paramValue);
                //遍历数组元素
//                foreach ($paramValue as $perValue) {
//                    $valuepathResStr = $valuepathResStr . $perValue;
//                    $valuepathResStr = $valuepathResStr . ",";
//                }
                //删除最后的","
//                $valuepathResStr = deletLastchar($valuepathResStr, ",");
            } else {
                //单值元素
                $valuepathResStr = $paramValue . "";
            }


            $replValues[$valuepathId] = $valuepathResStr;
            $replKeys[$valuepathId] = $matches[1][$idx];
            //
            $idx++;
        }

//    var_export($replKeys);
//    var_export($replValues);


        //替换 namestudentclass:Enum(|class.student.name|)  idstudentclass:Enum(|class.student.id|)
        //为  namestudentclass:Enum(zhangsan,lisi,wangwu)  idstudentclass:Enum(001,002,003)
        foreach ($paramsNedRepl as $paramPath) {
            $pattern = "/" . $replKeys[$paramPath] . ":Enum\(\|([^\)]+)\|\)/";
            $replacement = "" . $replKeys[$paramPath] . ":Enum(" . $replValues[$paramPath] . ")";
            $logger->debug("使用:[" . $replacement . "] 替换:[" . $pattern . "] . ");

            $urlrule = preg_replace($pattern, $replacement, $urlrule);
            $logger->debug("结果URL:[" . $urlrule . "] . ");
        }

    } else {
        $logger->debug("没有任何可以替换的变量，推出");
    }
    return $urlrule;
}


//$urlrule = "taskurl$:\"http://i.autohome.com.cn/$<idstudentclass \"%s\">/info\"{namestudentclass:Number(|start|,|step|,|end|)}";
/**
 *
 * 替换多个变量的值形如:{namestudentclass:Number(|student.start|,|student.step|,|student.end|)}
 * $paraType:Number|Time
 * @param $urlrule
 * @param $sourceObj
 * @throws Exception
 */
function replaceNumberTimeParam4Url($paraType, $urlrule, $currentTaskParam, $pathStructMap, $grabDatas = NULL)
{
    global $logger;
    preg_match_all("/ (\w*):" . $paraType . "\(([\w\.\,\|]+)\)/U", $urlrule, $matches);
    $logger->debug("*****************************************************************");
    $logger->debug("替换" . $paraType . ":(start,end,step) 类型的变量! 原始URL:[" . $urlrule . "].");
    $logger->debug("\n\n");

    if (count($matches[0]) > 0) {
        $paramsNedRepl = $matches[2]; //需要从变量中取值
        $replValues = array();
        $replKeys = array();
        $replParamPath = array();

        $logger->debug("总共匹配到了[" . count($paramsNedRepl) . "]个number/time类型的变量!");

        $idx = 0;
        foreach ($paramsNedRepl as $key => $valueComppath) {
            $logger->debug("开始处理第:[" . $idx . "] 个" . $paraType . "标签变量:[" . $matches[1][$idx] . "] 表达式:[" . $valueComppath . "] . ");
            //$valueComppath |classstudentstart|,|wangc|
            $paramNames = explode(",", $valueComppath);
            $logger->debug("总共需要处理处:[" . count($paramNames) . "] 个变量 . ");
//            $valueStr = "";//1,2,100 //start,step,end
            $paramValues = array();
            foreach ($paramNames as $paramPathId) {

                $paramPathId = substr($paramPathId, 1, strlen($paramPathId) - 2);
                $logger->debug("变量路径:[" . $paramPathId . "]");

                //根据参数来源类型获取参数数据
                //｛1:表示来自常量 2:表示来自变量，即参数定义 3:表示来自运行内置参数，如|cur_data|
                // 4:来自父参数} 在没有指定参数来源时候默认从当任务配置的变量定义中获取该参数
//                $sourceObj = getSourceObj($currentTaskParam, $pathStructMap[$paramPathId]);
//
//                //class.student.nunber.start
//                $paramValue = getValueFromObjWrap($sourceObj, $pathStructMap[$paramPathId]);

                $paramValue = getValue4UrlReplace($currentTaskParam, $pathStructMap[$paramPathId], $grabDatas);


                if (!is_numeric($paramValue)) {
                    throw new Exception("Param of number/time value is not a number/time,type:[" . gettype($paramValue) . "] . ");
                }

                //获取变量$valuepath的值，如果为多值则用","分割
                $logger->debug("get prop:[" . $paramPathId . "] value:[" . var_export($paramValue, true) . "] . ");

                //当前路径指定的值为空，不能生成URL直接记录错误日志，推出
                if (empty($paramValue) && $paramValue !== 0) {
                    $sourceObj = empty($grabDatas) ? $currentTaskParam : $grabDatas;
                    $logger->debug("prop:[" . $paramPathId . "not set or null in obj:[" . var_export($sourceObj, true) . "] . ");
                    throw new Exception("prop:[" . $paramPathId . "not set or null in obj:[" . var_export($sourceObj, true) . "] . ");
                }
                $paramValues[] = $paramValue;
            }

            $valueStr = implode(",", $paramValues);
            $replKeys[$matches[1][$idx]] = $valueComppath; // namestudentclass => |student.start|,|student.step|,|student.end|
            $replValues[$matches[1][$idx]] = $valueStr;// namestudentclass =>  1,2,100 即(start,step,end)
            //
            $idx++;
        }
        $logger->debug("参数名:[" . var_export($matches[1], true) . "]!");
        $logger->debug("------------------------------------------------------------------------------------------");
        foreach ($replKeys as $paramName => $origParmaPath) {
            $pattern = "/" . $paramName . ":" . $paraType . "\(([\w\.\,\|]+)\)/";
//            preg_match_all(" / (\w *):Number\(([\w\.\,\|]+)\) / ", $urlrule, $matches);
            $replacement = "" . $paramName . ":" . $paraType . "(" . $replValues[$paramName] . ")";
            $logger->debug("使用:[" . $replacement . "] 替换:[" . $pattern . "] . ");
            $urlrule = preg_replace($pattern, $replacement, $urlrule);
            $logger->debug(" ****************************************");
            $logger->debug("结果URL:[" . $urlrule . "] . ");
        }
    } else {
        $logger->debug("没有匹配到任何可以替换的变量，推出！");
    }

    return $urlrule;
}


/**
 *
 *处理URL中的Obj标签：
 * taskurl$:"http://i.autohome.com.cn/$<keyword "%s">/info"{keyword:Obj(param1:|user.id|,param2:|user.name|)}
 *
 * ["taskurl$:\"http://www.baidu.com/s?wd=$<keyword \"%s\">\"{keyword:Obj((test1:1-1111,test2:2-1111))}",
 * ["taskurl$:\"http://www.baidu.com/s?wd=$<keyword \"%s\">\"{keyword:Obj((test1:1-1111,test12:2-1111),(test1:1-2222,test2:2-2222))}"]
 *
 * user:Obj(user:|user.id|,path:|user.path|)
 * user:Obj((user:user1,path:path1),(user:user2,path:path2))
 *
 * @param $urlrule
 * @param $sourceObj
 * @throws Exception
 */
function replaceObjParam4Url($urlrule, $currentTaskParam, $pathStructMap, $grabDatas = NULL)
{
    global $logger;
    preg_match_all("/ (\w*):Obj" . "\(([\w\.\,\|\:]+)\)/U", $urlrule, $matches);
    $logger->debug("*****************************************************************");
    $logger->debug("替换Obj类型的变量! 原始URL:[" . $urlrule . "].");
    $logger->debug("\n");

    if (count($matches[0]) > 0) { //count($matches[0])的个数即需要替换的obj类型的参数的个数
        $paramsNedRepl = $matches[2]; //需要从变量中取值
        $replValues = array();
        $replKeys = array();
        $replParamPath = array();

        $logger->debug("总共匹配到了[" . count($paramsNedRepl) . "]个Obj类型的变量!");

        $idex = 0;
        foreach ($paramsNedRepl as $key => $valueComppath) {
            $logger->debug("开始处理第:[" . $idex . "] 个[Obj]类型的标签变量:[" . $matches[1][$idex] . "] 表达式:[" . $valueComppath . "].");
            //$valueComppath : param1:|user.id|,param2:|user.name|

            //每个匹配到的Obj:()类型的变量标签里面可能有多个参数，需要处理每个参数

            $paramNames = explode(",", $valueComppath);
            $logger->debug("总共需要处理处:[" . count($paramNames) . "] 个变量.");
            $allParamValuesPerObj = array();

            $childParamKey = array();// ["test1:","test2:"]

            foreach ($paramNames as $paramPathId) {
                $paramPathIdValues = array();
                $paramPathIdBak = $paramPathId;  //$paramPathIdBak: param1:|user.id|

                $logger->debug("开始处理变量:[" . $paramPathIdBak . "]..");

                $firstIndex = strpos($paramPathId, ":");
                if (!$firstIndex) {
                    $logger->error("URL替换Obj类型变量:[" . $paramPathIdBak . "失败,路径不合法,路径中没有[:]!");
                    throw new Exception("URL替换Obj类型变量:[" . $paramPathIdBak . "失败,路径不合法,路径中没有[:]!");
                }

                $childParamKey[] = substr($paramPathId, 0, $firstIndex + 1);

                //echo($idex);
                $paramPathId = substr($paramPathId, $firstIndex + 1);

                if (strpos($paramPathId, ":")) {
                    $logger->error("URL替换Obj类型变量:[" . $paramPathIdBak . "失败,路径不合法,路径中不能出现多个[:]!");
                    throw new Exception("URL替换Obj类型变量:[" . $paramPathIdBak . "失败,路径不合法,路径中不能出现多个[:]!");
                }

                //$paramPathId:|user.id|
                //去除两边的"||"
                $paramPathId = substr($paramPathId, 1, strlen($paramPathId) - 2);

                //$paramPathId ：user.id
                $logger->debug("处理变量路径:[" . $paramPathId . "]");

                //根据参数来源类型获取参数数据
                //｛1:表示来自常量 2:表示来自变量，即参数定义 3:表示来自运行内置参数，如|cur_data|
                // 4:来自父参数} 在没有指定参数来源时候默认从当任务配置的变量定义中获取该参数
//                $sourceObj = getSourceObj($currentTaskParam, $pathStructMap[$paramPathId]);
                //class.student.nunber.start
//                $paramValue = getValueFromObjWrap($sourceObj, $pathStructMap[$paramPathId]);
                $paramValue = getValue4UrlReplace($currentTaskParam, $pathStructMap[$paramPathId], $grabDatas);


                //获取变量$valuepath的值，如果为多值则用","分割
                $logger->debug("get prop:[" . $paramPathId . "] value:[" . var_export($paramValue, true) . "] . ");

                //当前路径指定的值为空，不能生成URL直接记录错误日志，推出
                if (empty($paramValue) && $paramValue !== 0) {
                    $sourceObj = empty($grabDatas) ? $currentTaskParam : $grabDatas;
                    $logger->debug("prop:[" . $paramPathId . "not set or null in obj:[" . var_export($sourceObj, true) . "] . ");
                    throw new Exception("prop:[" . $paramPathId . "not set or null in obj:[" . var_export($sourceObj, true) . "] . ");
                }

                if (is_array($paramValue)) {
                    //该参数有多个值的情况，将来就会出现多组
                    foreach ($paramValue as $paraName => $paramPerValue) {
                        $paramPathIdValues[] = $paramPerValue;
                    }
                } else {
                    //该值下面有一个值的情况，将来就只有一组值
                    $paramPathIdValues[] = $paramValue;
                }
                $allParamValuesPerObj[$paramPathIdBak] = $paramPathIdValues; // param1:|user.id| => [001,002,003]
            }

            //*******************************当前Obj:()参数中所有的变量的值获取成功*****************************
            $paramValueArrayLen = 0;
            $idx = 0;
            foreach ($allParamValuesPerObj as $paraPathKey => $paramValueArray) {
                if ($idx == 0) {
                    $paramValueArrayLen = count($paramValueArray);
                } else {
                    if (count($paramValueArray) != $paramValueArrayLen) {
                        $logger->error("URL替换Obj类型变量:[" . $paraPathKey . "失败,多个变量的值的个数不想等,所有变量的值数组:[" . var_export($allParamValuesPerObj, true) . "].");
                        throw  new Exception("URL替换Obj类型变量:[" . $paraPathKey . "失败,多个变量的值的个数不想等!");
                    }
                }
                $idx++;
            }

            if ($paramValueArrayLen == 0) {
                $logger->error("URL替换Obj类型变量:[" . $paraPathKey . "失败,每个变量的值的个数都为0,所有变量的值数组:[" . var_export($allParamValuesPerObj, true) . "].");
                throw  new Exception("URL替换Obj类型变量:[" . $paraPathKey . "失败,每个变量的值的个数都为0!");
            }

            //将每个Obj:()标签中的多个参数进行组合
            //Obj:(  (test1:1-1111,test12:2-1111),(test1:1-2222,test2:2-2222)  )
            $valueStrArray = array();

            for ($idx1 = 0; $idx1 < $paramValueArrayLen; $idx1++) {
                $paramvalueGoup = array();
                $paramKeyIndx = 0;
                foreach ($allParamValuesPerObj as $paraPathKey => $paramValueArray) {
                    //param1:|user.id| => [001,002,003]
//                    $paramPathStr = substr($paraPathKey, 1, strlen($paraPathKey) - 2);
//                    $paramvalueGoupStr = $paramvalueGoupStr . $paramPathStr . ":" . $paramValueArray[$idx];
                    $paramvalueGoup[] = $childParamKey[$paramKeyIndx] . $paramValueArray[$idx1]; //param1:1-1111,param2:2-1111
                    $paramKeyIndx++;
                }
                $paramvalueGoupStr = "(" . implode(",", $paramvalueGoup) . ")"; // $paramvalueGoupStr: (param1:1-1111,param2:2-1111)
                $valueStrArray[] = $paramvalueGoupStr;
            }

            $valueStr = implode(",", $valueStrArray); //$valueStr : (param1:1-1111,param2:2-1111),(param1:1-2222,param2:2-2222)

            $replKeys[$matches[1][$idex]] = $valueComppath; // keyword  =>   param1:|user.id|,param2:|user.name|
            $replValues[$matches[1][$idex]] = $valueStr;//     keyword  =>  (test1:1-1111,test12:2-1111),(test1:1-2222,test2:2-2222)

            $logger->debug("参数名:[" . $matches[1][$idex] . "] 原始路径:[" . $valueComppath . "].");
            $logger->debug("参数名:[" . $matches[1][$idex] . "] ValueStr:[" . $valueStr . "].");
            //
            $idex++;
        }
        $logger->debug("参数名:[" . var_export($matches[1], true) . "]!");
        $logger->debug("------------------------------------------------------------------------------------------");
        foreach ($replKeys as $paramName => $origParmaPath) {
            $pattern = "/" . $paramName . ":Obj" . "\(([\w\.\,\|\:]+)\)/";
//            preg_match_all(" / (\w *):Number\(([\w\.\,\|]+)\) / ", $urlrule, $matches);
            $replacement = "" . $paramName . ":Obj" . "(" . $replValues[$paramName] . ")";
            $logger->debug("使用:[" . $replacement . "] 替换:[" . $pattern . "] . ");
            $urlrule = preg_replace($pattern, $replacement, $urlrule);
            $logger->debug(" ****************************************");
            $logger->debug("结果URL:[" . $urlrule . "] . ");
        }
    } else {
        $logger->debug("没有匹配到任何可以替换的变量，退出！");
    }
    return $urlrule;
}


function deletLastchar($sourceStr, $endStr)
{
    if (endWith($sourceStr, $endStr)) {
        $sourceStr = substr($sourceStr, 0, strlen($sourceStr) - strlen($endStr));
    }
    return $sourceStr;
}

function setValue4ObjWrap(&$tagetValues, $pathStruct, $value)
{
    global $logger;
    $logger->debug("set value for tagetValues, tagetValues:[" . var_export($tagetValues, true) . "] value:[" . var_export($value, true) . "] pathStruct:" . var_export($pathStruct, true) . "].");
    if (!empty($pathStruct["col_name_ex"])) {
        $logger->debug("col_name_ex: not null " . $tagetValues[$pathStruct["col_name"]]);
        setValue4Obj($tagetValues[$pathStruct["col_name"]], $pathStruct["col_name_ex"], $value);
    } else {
        $logger->debug("col_name_ex: null");
        $tagetValues[$pathStruct["col_name"]] = $value;
    }
    $logger->debug("set value for tagetValues success! tagetValues:[" . var_export($tagetValues, true) . "] value:[" . var_export($value, true) . "] pathStruct:" . var_export($pathStruct, true) . "].");
}

function setValue4Obj(&$tagetValues, $pathStruct, $value)
{
    global $logger;
    $logger->debug("set param:[" . var_export($pathStruct, true) . "] to obj:[" . var_export($tagetValues, true) . "] . ");

    if ($pathStruct["type"] === PROPERTY_DATA_TYPE_ARRAY) {
        $logger->debug("处理数组... ");
        $arrayIdx = $pathStruct["data"]["arr_data"]["arr_idx"];

        //为所有的元素设置值
        if ($arrayIdx === -1) {
            //取每个索引的值
            if (!empty($pathStruct["name_ex"])) {
                foreach ($tagetValues as $indx => $oneElement) {
                    setValue4Obj($oneElement, $pathStruct["name_ex"], $value);
                }
            } else {
                $tagetValues[] = $value;//给当前数组的中增加一个值(即把当前值添加到数组的最后一个位置)
            }
        } else {
            //为单个元素设置值
            if (!empty($pathStruct["name_ex"])) {
                if (empty($tagetValues[$arrayIdx])) {
                    $tagetValues[$arrayIdx] = array();
                }
                $tagetValues = $tagetValues[$arrayIdx];
                setValue4Obj($tagetValues, $pathStruct["name_ex"]);
            } else {
                $tagetValues[$arrayIdx] = $value;
            }
        }
        //return getValueFromObj($sourceObj->$curPath, $childPath);
    } else if ($pathStruct["type"] === PROPERTY_DATA_TYPE_OBJ) {
        $logger->debug("处理对象...");
        $propName = $pathStruct["data"]["chld_col_name"];

        //需要递归获取下一级的数据
        if (!empty($pathStruct["name_ex"])) {
            if (empty($tagetValues[$propName])) {
                $tagetValues[$propName] = array();
            }
            return setValue4Obj($tagetValues[$propName], $pathStruct["name_ex"]);
        } else {
            $tagetValues[$propName] = $value;
        }
        //$logger->debug("get it param:[" . $valuepath . "] value:[" . json_encode($sourceObj->$valuepath) . "] . ");
    } else {
        throw new Exception("illegal PathStruct . type:[" . $pathStruct["type"] . "].");
    }
}

/**
 * @param $taskParam
 * @param $pathStruct
 *
 *    //该参数的取值方式
 **   // ｛1:表示来自常量(用于爬虫抓取的时候，使用常量的值如:电商名称等)
 *    //  2:表示来自变量，即参数定义(发起任务时候定义的参数)
 *    //  3:表示来自运行内置参数，平台为了简单用户操作，将一些常用的易变的参数进行内置，方便使用。如|cur_data|
 *    //  4:来自父参数} 在没有指定参数来源时候默认从当任务配置的变量定义中获取该参数
 *    //  5:来自当前一条抓取记录(在父任务生成子任务方法时候，会利用当前记录一条记录或者多条记录生成子任务)
 *    // #########任何获取参数的操作都需要通过参数的取值路径来获取参数的值，禁止直接获取参数#######
 * @return null
 * @throws Exception
 */
function &getSourceObj(&$taskParam, $pathStruct)
{
    global $logger;
    $logger->debug("开始处理getSourceObj方法...");

    if (empty($taskParam)) {
        $logger->debug(" taskParam is null.");
    }

    if (empty($pathStruct)) {
        $logger->debug(" pathStruct is null.");
    }

//    $logger->debug("getSourceObj.:[" . var_export($pathStruct, true) . "] \n TaskParam:[" . var_export($taskParam, true) . "].");
    $logger->debug("paramSource:[" . var_export($pathStruct["paramSource"], true) . "].");

    if ($pathStruct["paramSource"] == 1) {
        //1:表示来自常量
        return $taskParam["constants"];
//        return $sourceObj;
    } else if ($pathStruct["paramSource"] == 2) {
        //2:表示来自变量
        return $taskParam["paramsDef"];
//        return $sourceObj;
    } else if ($pathStruct["paramSource"] == 3) {
        //3:表示来自运行内置参数
        //处理内置参数
        if (!isset($taskParam["runTimeParam"])) {
            $taskParam["runTimeParam"] = json_decode("", true);
        }
        return $taskParam["runTimeParam"];
    } else if ($pathStruct["paramSource"] == 4) {
        //4:来自父参数
        return $taskParam["parentParam"];
    }
//    else if ($pathStruct["paramSource"] == 5) {
//        //4:来自当前一条抓取记录
//        return $grabDatas;
//    }
    else {
        $logger->error(SELF . " " . __FUNCTION__ . " 不支持的变量源类型:[" . $pathStruct["paramSource"] . "].");
        throw new Exception(" 不支持的变量源类型:[" . $pathStruct["paramSource"] . "].");
    }
}


function getSourceObjKey(&$taskParam, $pathStruct)
{
    global $logger;
    $logger->debug("开始处理setSourceObj方法...");

    if (empty($taskParam)) {
        $logger->debug(" taskParam is null.");
    }

    if (empty($pathStruct)) {
        $logger->debug(" pathStruct is null.");
    }

    if ($pathStruct["paramSource"] == 1) {
        //1:表示来自常量
//        $sourceObj = $taskParam["constants"];
        return "constants";
    } else if ($pathStruct["paramSource"] == 2) {
        //2:表示来自变量
        //$sourceObj = $taskParam["paramsDef"];
        // $logger->debug("来自变量:[" . var_export($sourceObj, true) . "].");
        return "paramsDef";
    } else if ($pathStruct["paramSource"] == 3) {
        //3:表示来自运行内置参数
        //处理内置参数
        $paramName = $pathStruct["col_name"];
        if (empty($taskParam["runTimeParam"])) {
            $taskParam["runTimeParam"] = json_decode("", true);
        }

        //现在只支持时间
//        if ($paramName === INNER_PARAM_CUR_DATE) {
//            //获取日期模版
//            if (!empty($pathStruct["template"])) {
//                $templt = $pathStruct["template"];
//            } else {
//                $templt = INNER_PARAM_DATE_TEMP;
//            }
//            $time = time();
//            $paramV = date("y-m-d", $time); //2010-08-29
//            $taskParam["runTimeParam"][$paramName] = $paramV;
//            return $taskParam["runTimeParam"];
//        } else {
//            return $taskParam["runTimeParam"];
////            throw new Exception("unsupportted InnerParamType:[" . $paramName . "].");
//        }

//        return $taskParam["runTimeParam"];
        return "runTimeParam";
    } else if ($pathStruct["paramSource"] == 4) {
        //4:来自父参数
//        return $taskParam["parentParam"];
        return "parentParam";
    }
//    else if ($pathStruct["paramSource"] == 5) {
//        //4:来自当前一条抓取记录
//        return $grabDatas;
//    }
    else {
        $logger->error(SELF . " " . __FUNCTION__ . " 不支持的变量源类型:[" . $pathStruct["paramSource"] . "].");
        return null;
    }
}

function getValueFromObjWrap(&$sourceObj, &$pathStruct,$notExistExcp = true)
{
    global $logger;
//    $logger->debug("getValueFromObjWrap, pathStruct: " . var_export($pathStruct, true) . "] sourceObj:[" . var_export($sourceObj, true) . "].");
    $paramName = $pathStruct["col_name"];

    if ($pathStruct["paramSource"] == 3) {
        //TODO 在这里应该先判断运行时参数里面是否设置了该值，如果没有设置，则进行初始化....
        //现在只支持时间
        if ($paramName === INNER_PARAM_CUR_DATE) {
            //获取日期模版
            if (!empty($pathStruct["template"])) {
                $templt = $pathStruct["template"];
            } else {
                $templt = INNER_PARAM_DATE_TEMP;
            }
            $time = time();
            $paramV = date("y-m-d", $time); //2010-08-29
            $sourceObj[$paramName] = $paramV;
        }
        $logger->debug("获取内置参数:" . $paramName . "] value:[" . $sourceObj[$paramName] . "].");
        return $sourceObj[$paramName];
    }

    if (empty($sourceObj)) {
        throw new Exception("sourceObj is null.getValueFromObjWrap excption!");
    }

    if (!isset($sourceObj[$paramName])) {
        if($notExistExcp){
            throw new Exception("paramName:[" . $paramName . "] not exist in sourceObj:[" . var_export($sourceObj, true) . "].");
        }else{
            return null;
        }
    }

    if (!empty($pathStruct["col_name_ex"])) {
        $value = getValueFromObj($sourceObj[$paramName], $pathStruct["col_name_ex"]);
    } else {
        $value = $sourceObj[$paramName];
    }
    return $value;
}

/**
 *根据参数属性的路径获取对象中某个属性的值
 * @param $sourceObj :存储值的对象
 * @param $valuepath ：存储取值路径的对象体
 */
function getValueFromObj($sourceObj, $pathStruct)
{
    global $logger;
    $logger->debug("get param:[" . var_export($pathStruct, true) . "] from obj:[" . var_export($sourceObj, true) . "] . ");
    if ($pathStruct["type"] === PROPERTY_DATA_TYPE_ARRAY) {
        $logger->debug("处理数组... ");
        $arrayIdx = $pathStruct["data"]["arr_data"]["arr_idx"];
        //取所有的元素
        if ($arrayIdx === -1) {
            //取每个索引的值
            $valueArrays = array();
            foreach ($sourceObj as $oneElement) {
                if (!empty($pathStruct["name_ex"])) {
                    $valueArrays[] = getValueFromObj($oneElement, $pathStruct["name_ex"]);
                } else {
                    $valueArrays[] = $oneElement;
                }
            }
            return $valueArrays;
        } else {
            //获取单个索引的值
            $value = $sourceObj[$arrayIdx];
            if (!empty($pathStruct["name_ex"])) {
                return getValueFromObj($value, $pathStruct["name_ex"]);
            } else {
                return $value;
            }
        }
        //return getValueFromObj($sourceObj->$curPath, $childPath);
    } else if ($pathStruct["type"] === PROPERTY_DATA_TYPE_OBJ) {
        $logger->debug("处理对象...");
        //
        $propName = $pathStruct["data"]["chld_col_name"];
        $value = $sourceObj[$propName];

        //需要递归获取下一级的数据
        if (!empty($pathStruct["name_ex"])) {
            return getValueFromObj($value, $pathStruct["name_ex"]);
        }
        return $value;
        //$logger->debug("get it param:[" . $valuepath . "] value:[" . json_encode($sourceObj->$valuepath) . "] . ");
    } else {
        throw new Exception("illegal PathStruct . type:[" . $pathStruct["type"] . "].");
    }
}

function converObjToRelArray($taskparams)
{
    if (!empty($taskparams)) {
        $paramStri = json_encode($taskparams);
        $taskparams = json_decode($paramStri, true);
        return $taskparams;
    } else {
        return array();
    }
}

/**
 * 判断字符串$haystack是否以字符串$needle结尾
 * @param $haystack
 * @param $needle
 * @return bool
 */
function endWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
}

function isCommonTask(&$taskobj)
{
    //global $logger;
    //$logger->debug("isCommonTask for taskObj:[" . var_export($taskobj, true) . "].");
    if (is_object($taskobj)) {
        if (!empty($taskobj->task) && $taskobj->task == TASK_COMMON) {
            return true;
        }
    } else if (is_array($taskobj)) {
        if (!empty($taskobj['task']) && $taskobj['task'] == TASK_COMMON) {
            return true;
        }
    }
    return false;
}