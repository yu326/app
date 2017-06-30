<?php
/*
 * 公共函数
 * @author Todd
 */
include_once('model_config.php');
include_once('taskcontroller.php');
include_once('chinese2pinyin.php');

define('LOCALTYPE_PLATFORM', 1);//数据平台
define('LOCALTYPE_TENANT', 2);//租户

define('RESOURCE_TYPE_SYSTEM', 1);      //资源类型，系统资源（后台管理中的增删改等权限）
define('RESOURCE_TYPE_TENANT', 3);      //租户资源

define('JSON_OUTPUTTYPE_QUERY', 1);//JSON数据中output字段的outputtype属性，1表示只查询
define('JSON_OUTPUTTYPE_FACET', 2);//JSON数据中output字段的outputtype属性，2表示只FACET

$global_timeoutsec = 120;//curl超时时间
/*
 * 获取当前平台类型
 */
function getLocalType()
{
    $hname = strtolower($_SERVER['HTTP_HOST']);
    if ((defined('HOSTNAME_TENANT') && HOSTNAME_TENANT != '' && strpos($hname, HOSTNAME_TENANT) !== false)
        || (defined('PATHHOST_TENANT') && PATHHOST_TENANT != '' && $hname == PATHHOST_TENANT)
    ) {
        return LOCALTYPE_TENANT;
    } else if ((defined('HOSTNAME_PLATFORM') && HOSTNAME_PLATFORM != '' && strpos($hname, HOSTNAME_PLATFORM) !== false)
        || (defined('PATHHOST_PLATFORM') && PATHHOST_PLATFORM != '' && $hname == PATHHOST_PLATFORM)
    ) {
        return LOCALTYPE_PLATFORM;
    } else {
        return false;
    }
}

/*
 * 从urlrewrite后的参数中获取租户二级域名代码
 */
function getTenantCode()
{
    $tcode = $_GET['re_tenantcode'];
    return $tcode;
}

function arrayToObject($array)
{
    if (!is_array($array)) {
        return $array;
    }

    $object = new stdClass();
    if (is_array($array) && count($array) > 0) {
        foreach ($array as $name => $value) {
            $name = strtolower(trim($name));
            if (!empty($name)) {
                $object->$name = arrayToObject($value);
            }
        }
        return $object;
    } else {
        return FALSE;
    }
}

/**
 * 根据fieldname从filtervalue数组中获取对象数组
 * @return fieldname的对象数组
 */
function getFilterValueItem($fieldname, $filtervalueArr)
{
    $result = array();
    foreach ($filtervalueArr as $key => $value) {
        if (isset($value['fieldname']) && $value['fieldname'] == $fieldname) {
            $result[] = $value;
        }
    }
    return $result;
}

/**
 * 获取字段的值，如果是数组则递归获取所有值
 * @param $fieldvalue fieldvalue:{datatype:"int", value:123}
 * @return 根据datatype返回value，数组或单值（只返回123）
 */
function getFilterValue($fieldvalue)
{
    $tmpArr = array();
    if ($fieldvalue["datatype"] == "array") {
        foreach ($fieldvalue["value"] as $key => $value) {
            if ($value["datatype"] == "array") {
                $res = getFilterValue($value);
                $tmpArr = array_merge($res, $tmpArr);
            } else {
                $tmpArr[] = $value['value'];
            }
        }
    } else {
        return $fieldvalue["value"];
    }
    return $tmpArr;
}

/**
 *
 * 获取valueobj中的值。比如获取{value:1, text:2} 中的 value
 * @param string $datatype
 * @param unknown_type $valueobj
 */
function getFieldValue($datatype, $valueobj)
{
    $value;
    switch ($datatype) {
        case "blur_value_object":
        case "value_text_object":
            $value = "";
            if (is_array($valueobj)) {
                if (isset($valueobj["guid"])) {
                    $value = $valueobj["guid"];
                } else {
                    $value = $valueobj["value"];
                }
            } else {
                if (isset($valueobj->guid)) {
                    $value = $valueobj->guid;
                } else {
                    $value = $valueobj->value;
                }
            }
            break;
        default:
            $value = $valueobj;
            break;
    }
    return $value;

}

function set_error_msg($error_str)
{
    $error['error'] = $error_str;
    $msg = json_encode($error);
    echo $msg;
    exit;
}

/**
 * 转义正则特殊符号
 * @param s
 * @returns
 */
function escapeRe($s)
{
    return preg_replace("/([\\.\\+\\?\\^\\\$\\{\\}\\(\\)\\|\\[\\]\\/\\\\])/i", '\\\$1', $s);
}


/**
 *
 * 验证jsonlimit是否合法
 */
function checkLimit($filter, $newlimit)
{
    if ($filter["limitcontrol"] == 0) {
        if (count($filter['limit']) == 0 && count($newlimit) == 0) {
            return 1;
        } else if (count($filter['limit']) != count($newlimit)) {
            return -1;
        } else {
            $r = 1;
            foreach ($newlimit as $i => $item) {
                $nv = getLimitValue($filter['datatype'], $item);
                $iseq = false;
                foreach ($$filter['limit'] as $j => $olimit) {
                    $ov = getLimitValue($filter['datatype'], $olimit);
                    if ($olimit['type'] == 'range') {
                        if ($olimit['repeat'] == $item['repeat'] && $olimit['type'] == $item['type']
                            && $ov['maxvalue'] == $nv['maxvalue'] && $ov['minvalue'] == $nv['minvalue']
                        ) {
                            $iseq = true;
                            break;
                        }
                    } else if ($olimit['type'] == 'gaprange') {
                        if ($olimit['repeat'] == $item['repeat'] && $olimit['type'] == $item['type']
                            && $ov['maxvalue'] == $nv['maxvalue'] && $ov['minvalue'] == $nv['minvalue'] && $ov['gap'] == $nv['gap']
                        ) {
                            $iseq = true;
                            break;
                        }
                    } else {
                        if ($olimit['repeat'] == $item['repeat'] && $olimit['type'] == $item['type'] && $ov == $nv) {
                            $iseq = true;
                            break;
                        }
                    }
                }
                if ($iseq == false) {
                    $r = -1;
                    break;
                }
            }
            return $r;
        }
    }
    $limit = $newlimit;
    if (count($filter["limit"]) > 0) {
        $ov = getLimitValue($filter["datatype"], $filter['limit'][0]);
        if ($filter["limit"][0]["type"] == 'range') {
            $result = 1;
            foreach ($limit as $key => $item) {
                $nv = getLimitValue($filter["datatype"], $item);
                if ($ov["maxvalue"] !== null && ($nv["minvalue"] > $ov["maxvalue"] || $nv["maxvalue"] > $ov["maxvalue"])) {
                    $result = 0;
                    break;
                } else if ($ov["minvalue"] !== null && ($nv["minvalue"] < $ov["minvalue"] || $nv["maxvalue"] < $ov["minvalue"])) {
                    $result = 0;
                    break;
                }
            }
            return $result;
        } else if ($filter["limit"][0]["type"] == 'gaprange') {
            $result = 1;
            foreach ($limit as $key => $item) {
                $nv = getLimitValue($filter["datatype"], $item);
                if ($ov["maxvalue"] != null) {
                    $ov["maxvalue"] = getGapRangeValue($ov["maxvalue"], $ov["gap"]);
                }
                if ($ov["minvalue"] != null) {
                    $ov["minvalue"] = getGapRangeValue($ov["minvalue"], $ov["gap"]);
                }

                $nv["maxvalue"] = getGapRangeValue($nv["maxvalue"], $nv["gap"]);
                $nv["minvalue"] = getGapRangeValue($nv["minvalue"], $nv["gap"]);
                if ($ov["maxvalue"] !== null && ($nv["minvalue"] > $ov["maxvalue"] || $nv["maxvalue"] > $ov["maxvalue"])) {
                    $result = 0;
                    break;
                } else if ($ov["minvalue"] !== null && ($nv["minvalue"] < $ov["minvalue"] || $nv["maxvalue"] < $ov["minvalue"])) {
                    $result = 0;
                    break;
                }
                /*
                else{
                    if($ov["gap"] != null && (checkRegisterTimeGap($nv["gap"], $ov["gap"]))){ //验证博龄的gap是否在limit范围内
                        $result = 0;
                        break;
                    }
                }
                 */
            }
            return $result;
        } else {
            //生成正则
            $exactReg = array();
            //根据计费中的limit生成正则
            foreach ($filter["limit"] as $key => $item) {
                $value = getLimitValue($filter["datatype"], $item);
                if (isset($value)) {
                    if ($item["type"] == "inexact") {
                        $value = str_replace("*", ".*", escapeRe($value));
                    } else {
                        $value = escapeRe($value);
                    }
                    $exactReg[] = array("repeat" => $item["repeat"], "reg" => '/^' . $value . '$/');
                }
            }

            if (count($exactReg) > 0) {
                for ($k = count($limit) - 1; $k > -1; $k--) {
                    foreach ($exactReg as $i => $item) {
                        $value = getLimitValue($filter["datatype"], $limit[$k]);
                        if (isset($value) && $item['repeat'] > $limit[$k]["repeat"]) {
                            $temp = array();
                            if (preg_match_all($item, $value, $temp) > 0) {
                                $item['repeat'] -= $limit[$k]["repeat"];
                                array_splice($limit, $k, 1);
                                break;
                            }
                        }
                    }
                }
            }
            return count($limit) == 0 ? 1 : 0;
        }
    } else {
        return 1;
    }

}

function setTimeStr($y, $m, $w, $d, $h, $i, $s)
{
    $year = "-" . $y . "year ";
    $month = "-" . $m . "month ";
    $day = "-" . $d . "day ";
    $week = "-" . $w . "week ";
    $hour = "-" . $h . "hour ";
    $minute = "-" . $i . "minute ";
    $second = "-" . $s . "second";
    return $year . $month . $day . $week . $hour . $minute . $second;
}

//设置日历时间
function setTimeBeginning($timestamp, $type)
{
    if ($type == "start") {
        $timestr = date("Y-m-d 0:0:0", $timestamp);
    } else if ($type == "end") {
        $timestr = date("Y-m-d 23:59:59", $timestamp);
    }
    return strtotime($timestr);
}

function getMondayDate($timestamp)
{
    $theday = date("w", $wtime);
    if ($theday == 0) {
        $theday = 7;
    }
    $tmpday = 1 - $theday;
    $monday = strtotime("" . $tmpday . " d", $wtime);
    return $monday;
}

function getSundayDate($timestamp)
{
    $theday = date("w", $wtime);
    if ($theday == 0) {
        $theday = 7;
    }
    $tmpday = 7 - $theday;
    $sunday = strtotime("" . $tmpday . " d", $wtime);
    return $sunday;
}

function setDateBeginning($timestamp, $gap, $type)
{
    $formatstr = "";
    switch ($gap) {
        case "year":
            if ($type == "start") {
                $formatstr = "Y-1-1 H:i:s";
            } else if ($type == "end") {
                $formatstr = "Y-12-31 H:i:s";
            }
            break;
        case "month":
            if ($type == "start") {
                $formatstr = "Y-m-01 H:i:s";
            } else if ($type == "end") {
                $lastday = getLastDay($timestamp);
                $formatstr = "Y-m-" . $lastday . " H:i:s";
            }
            break;
        case "week":
            if ($type == "start") {
                $m = getMondayDate($timestamp);
                $formatstr = date("Y-m-d H:i:s", $m);
            } else if ($type == "end") {
                $s = getSundayDate($timestamp);
                $formatstr = date("Y-m-d H:i:s", $s);
            }
            break;
        default:
            break;
    }
    return strtotime(date($formatstr, $timestamp));
}

function getRangeStateTime($start, $startgap, $end, $endgap, $datestate, $timestate)
{
    if ($start != null && $startgap != null) {
        $bts = strtotime("-" . $start . " " . $startgap . "");
    }
    if ($end != null && $endgap != null) {
        $bte = strtotime("-" . $end . " " . $endgap . "");
    } else {
        $bte = strtotime("now");
    }
    if (isset($datestate) && $datestate == "beginning") {
        $bts = setDateBeginning($bts, $startgap, "start");
        $bte = setDateBeginning($bte, $endgap, "end");
    }
    if (isset($timestate) && $timestate == "beginning") {
        $bts = setTimeBeginning($bts, "start");
        $bte = setTimeBeginning($bte, "end");
    }
    return array("startpoint" => $bts, "endpoint" => $bte);
}

function getLastDay($timestamp)
{
    $formatstr = "Y-m-1 0:0:0";
    $m = date($formatstr, $timestamp);
    $n = strtotime("+1 month", strtotime($m));
    $maxsto = date("d", $n - 24 * 60 * 60);
    return $maxsto;
}

function getMaxDate($thisgap, $togap, $untiltimes)
{
    $maxsto;
    $base = 1;
    switch ($thisgap) {
        case "year":
            $base = 366;
            break;
        case "month":
            $base = getLastDay($untiltimes);
            break;
        case "week":
            $base = 7;
            break;
        default:
            break;
    }
    switch ($togap) {
        case "month":
            $maxsto = 12;
            break;
        case "day":
            $maxsto = $base;
            break;
        case "week":
            if ($thisgap == "year") {
                $maxsto = 52;
            } else if ($thisgap == "month") {
                $maxsto = 4;
            }
            break;
        case "hour":
            $maxsto = $base * 24;
            break;
        case "minute":
            if ($thisgap == "hour") {
                $maxsto = $base * 60;
            } else {
                $maxsto = $base * 24 * 60;
            }
            break;
        case "second":
            if ($thisgap == "hour") {
                $maxsto = $base * 60 * 60;
            } else if ($thisgap == "minute") {
                $maxsto = $base * 60;
            } else {
                $maxsto = $base * 24 * 60 * 60;
            }
            break;
        default:
            break;
    }
    return $maxsto;
}

function sinceToThis($s, $sgap, $stgap, $t, $tgap, $timestate)
{
    $untiltimes = strtotime("-" . $s . " " . $sgap . ""); //至今? 年|月|日|时|分|秒 周
    $formatstr = "";
    $maxsto;
    switch ($stgap) {
        case "year":
            $formatstr = "Y-1-1 0:0:0";
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            break;
        case "month":
            $formatstr = "Y-m-1 0:0:0";
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            break;
        case "day":
            $formatstr = "Y-m-d 0:0:0";
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            break;
        case "hour":
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            $formatstr = "Y-m-d H:0:0";
            break;
        case "minute":
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            $formatstr = "Y-m-d H:i:0";
            break;
        case "second":
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            $formatstr = "Y-m-d H:i:s";
            break;
        case "week":
            //获取星期一
            $maxsto = getMaxDate($stgap, $tgap, $untiltimes);
            $wtime = strtotime("-" . $s . " " . $sgap . "");
            $theday = date("w", $wtime);
            if ($theday == 0) {
                $theday = 7;
            }
            $tmpday = 1 - $theday;
            $monday = strtotime("" . $tmpday . " d", $wtime);
            $formatstr = date("Y-m-d 0:0:0", $monday);
            break;
        default:
            $formatstr = "Y-m-d H:i:s";
            break;
    }
    $strstart = date($formatstr, $untiltimes); //该年|月|日|时|分|秒 周
    if (isset($maxsto) && $t > $maxsto) {
        $t = $maxsto;
    }
    if ($timestate == "beginning") {
        $t = $t - 1;
    }
    $startpoint = strtotime("+" . $t . " " . $tgap . "", strtotime($strstart));  //第? 年|月|日|时|分|秒 周
    if ($timestate == "ending") {
        return $startpoint - 1;
    } else {
        return $startpoint;
    }
}

/**
 *
 * 验证value是否合法
 * @param $filter 传递引用，需要修改repeat
 * @param $fieldvalue 具体的值，datajson["filtervalue"][$i]["fieldvalue"]["value"]
 * @param $datatype 为fieldvalue的datatype
 */
function checkFilterValue(&$filter, $fieldvalue, $datatype = NULL)
{
    global $logger;
    if (count($filter["limit"]) > 0) {
        if (isset($filter["limit"][0]["type"]) && $filter["limit"][0]["type"] == 'range') {
            $result = 1;
            //根据filtervalue的类型得出 时间的最大值最小值
            if ($datatype == "time_dynamic_range") {
                $startpoint = sinceToThis($fieldvalue["start"]["start"], $fieldvalue["start"]["startgap"], $fieldvalue["start"]["startthisgap"], $fieldvalue["start"]["startto"], $fieldvalue["start"]["starttogap"], "beginning");
                $endpoint = sinceToThis($fieldvalue["end"]["end"], $fieldvalue["end"]["endgap"], $fieldvalue["end"]["endthisgap"], $fieldvalue["end"]["endto"], $fieldvalue["end"]["endtogap"], "ending");
            } else if ($datatype == "time_dynamic_state") {
                $stateTime = getTimeDynamicStateValue($fieldvalue);
                $startpoint = $stateTime["startpoint"];
                $endpoint = $stateTime["endpoint"];
            } else if ($datatype == "classifyrange") {
                if ($fieldvalue["rangeinfo"]["type"] == "gap" || $fieldvalue["rangeinfo"]["type"] == "gapcount") {
                    $rangearr = facetrangevalue($fieldvalue["rangeinfo"]["value"]["rangevalue"]);
                }
                if (!empty($rangearr)) {
                    $startpoint = $rangearr["start"];
                    $endpoint = $rangearr["end"];
                }
            } else {
                $endpoint = $fieldvalue["end"];
                $startpoint = $fieldvalue["start"];
            }
            $countflag = 0;
            foreach ($filter["limit"] as $fi => $fitem) {
                $flag = true;
                if ($fitem['value']["maxvalue"] !== null) {
                    if ($startpoint > $fitem['value']["maxvalue"] || $endpoint > $fitem['value']["maxvalue"]) {
                        //return 0;
                        $flag = false;
                    }
                }
                if ($fitem['value']["minvalue"] !== null) {
                    if ($startpoint < $fitem['value']["minvalue"] || $endpoint < $fitem['value']["minvalue"]) {
                        $flag = false;
                        //return 0;
                    }
                }
                if (!$flag) {
                    $countflag++;
                }
            }
            if ($countflag == count($filter["limit"])) {
                $result = 0;
            }
            return $result;
        } else if (isset($filter["limit"][0]["type"]) && ($filter["limit"][0]["type"] == 'time_dynamic_state' || $filter["limit"][0]["type"] == 'time_dynamic_range')) {
            $result = 1;
            //根据filter中limit的类型得出 时间的最大值和最小值
            if ($filter["limit"][0]["type"] == 'time_dynamic_range') {
                $limitvalue = $filter["limit"][0]["value"];
                $limitstart = sinceToThis($limitvalue["start"]["start"], $limitvalue["start"]["startgap"], $limitvalue["start"]["startthisgap"], $limitvalue["start"]["startto"], $limitvalue["start"]["starttogap"], "beginning");
                $limitend = sinceToThis($limitvalue["end"]["end"], $limitvalue["end"]["endgap"], $limitvalue["end"]["endthisgap"], $limitvalue["end"]["endto"], $limitvalue["end"]["endtogap"], "ending");
            } else if ($filter["limit"][0]["type"] == 'time_dynamic_state') {
                $limitvalue = $filter["limit"][0]["value"];
                $stateTime = getTimeDynamicStateValue($limitvalue);
                $limitstart = $stateTime["startpoint"];
                $limitend = $stateTime["endpoint"];
            }
            //根据filtervalue的类型得出 时间的最大值最小值
            if ($datatype == "time_dynamic_range") {
                $startpoint = sinceToThis($fieldvalue["start"]["start"], $fieldvalue["start"]["startgap"], $fieldvalue["start"]["startthisgap"], $fieldvalue["start"]["startto"], $fieldvalue["start"]["starttogap"], "beginning");
                $endpoint = sinceToThis($fieldvalue["end"]["end"], $fieldvalue["end"]["endgap"], $fieldvalue["end"]["endthisgap"], $fieldvalue["end"]["endto"], $fieldvalue["end"]["endtogap"], "ending");
            } else if ($datatype == "time_dynamic_state") {
                $stateTime = getTimeDynamicStateValue($fieldvalue);
                $startpoint = $stateTime["startpoint"];
                $endpoint = $stateTime["endpoint"];
            } else if ($datatype == "classifyrange") {
                if ($fieldvalue["rangeinfo"]["type"] == "gap" || $fieldvalue["rangeinfo"]["type"] == "gapcount") {
                    $rangearr = facetrangevalue($fieldvalue["rangeinfo"]["value"]["rangevalue"]);
                }
                if (!empty($rangearr)) {
                    $startpoint = $rangearr["start"];
                    $endpoint = $rangearr["end"];
                }
            } else {
                $endpoint = $fieldvalue["end"];
                $startpoint = $fieldvalue["start"];
                $endpoint = isset($fieldvalue["end"]) ? $fieldvalue["end"] : time();
                $startpoint = isset($fieldvalue["start"]) ? $fieldvalue["start"] : 0;
            }
            //进行比较
            if (isset($limitend)) {
                if ($startpoint > $limitend || $endpoint > $limitend) {
                    return 0;
                }
            }
            if (isset($limitstart)) {
                if ($startpoint < $limitstart || $endpoint < $limitstart) {
                    return 0;
                }
            }
            return $result;
        } else if (isset($filter["limit"][0]["type"]) && $filter["limit"][0]["type"] == 'gaprange') {
            $result = 1;
            $fieldvaluestart = $fieldvalue["start"];
            $fieldvalueend = $fieldvalue["end"];
            if (isset($fieldvalue["gap"])) {
                $fieldvaluestart = getGapRangeValue($fieldvaluestart, $fieldvalue["gap"]);
                $fieldvalueend = getGapRangeValue($fieldvalueend, $fieldvalue["gap"]);
            }

            if ($filter["limit"][0]["value"]["maxvalue"] != null) {
                $limitmax = $filter["limit"][0]["value"]["maxvalue"];
                $limitmax = getGapRangeValue($limitmax, $filter["limit"][0]["value"]["gap"]);
                if (!isset($fieldvalue["gap"])) { //drilldown时没有设置gap, $fieldvaluestart 表示时间戳
                    $dstart = (time() - $fieldvaluestart) / (24 * 3600);
                    $dend = (time() - $fieldvalueend) / (24 * 3600);
                } else {
                    $dstart = $fieldvaluestart;
                    $dend = $fieldvalueend;
                }
                if ($dstart > $limitmax || $dend > $limitmax) {
                    return 0;
                }
            }
            if ($filter["limit"][0]["value"]["minvalue"] != null) {
                $limitmin = $filter["limit"][0]["value"]["minvalue"];
                $limitmin = getGapRangeValue($limitmin, $filter["limit"][0]["value"]["gap"]);
                if (!isset($fieldvalue["gap"])) { //drilldown时没有设置gap, 单位是秒
                    $dstart = (time() - $fieldvaluestart) / (24 * 3600);
                    $dend = (time() - $fieldvalueend) / (24 * 3600);
                } else {
                    $dstart = $fieldvaluestart;
                    $dend = $fieldvalueend;
                }
                if ($dstart < $limitmin || $dend < $limitmin) {
                    return 0;
                }
            }
            return $result;
        } else {
            $result = 0;
            foreach ($filter["limit"] as $key => $item) {
                $value = getLimitValue($filter["datatype"], $item);
                if (isset($value)) {
                    if (isset($item["type"]) && $item["type"] == "inexact") {
                        $value = str_replace("*", ".*", escapeRe($value));
                    } else {
                        $value = escapeRe($value);
                    }
                    $reg = '/^' . $value . '$/';
                    if (isset($item['repeat']) && $item['repeat'] > 0) {
                        $temp = array();
                        if (preg_match_all($reg, $fieldvalue, $temp) > 0) {
                            $filter["limit"][$key]["repeat"]--;
                            $result = 1;
                            break;
                        }
                    }
                }
            }
            return $result;
        }
    } else {
        return 1;
    }

}

function is_assoc($arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function filterHasLimit($fitemlimit)
{
    $haslimit = false;
    if (count($fitemlimit) > 0) {
        if ($fitemlimit[0]["type"] == "range") {
            if (isset($fitemlimit[0]["value"]["maxvalue"]) || isset($fitemlimit[0]["value"]["minvalue"])) {
                if ($fitemlimit[0]["value"]["maxvalue"] != null || $fitemlimit[0]["value"]["minvalue"] != null) {
                    $haslimit = true;
                }
            } else {
                $haslimit = true;
            }
        } else { //其他类型,string, int, value_text_object
            $haslimit = true;
        }
    }
    return $haslimit;
}

/**
 *
 * 取出最新新版本json，从tmpjson中取value，从authJson中取limit部分
 * @param $tmpJson element 的JSON
 * @param $authJson  权限JSON
 */
function getNewVersionJson($tmpJson, $authJson)
{
    $newJson = getModelByID($tmpJson["modelid"]);
    $jsonArr = json_decode(json_encode($newJson), true);
    $newJsonArr = $jsonArr["datajson"];
    //account_rule对应limit限制赋值给新版本json
    if (!empty($newJsonArr["filter"])) {
        foreach ($newJsonArr["filter"] as $key => $value) {
            if (isset($authJson["filter"][$key])) {
                if ($newJsonArr["filter"][$key]["datatype"] == $authJson["filter"][$key]["datatype"]) {
                    if ($key == "nearlytime" || $key == "beforetime" || $key == "untiltime") {
                        if (count($authJson["filter"][$key]["limit"]) > 0 && !array_key_exists("maxvalue", $authJson["filter"][$key]["limit"][0]["value"]) && !array_key_exists("minvalue", $authJson["filter"][$key]["limit"][0]["value"])) {
                            $newJsonArr["filter"][$key]["limit"] = $authJson["filter"][$key]["limit"];  //account_rule对应字段json赋值给新json
                        } else {
                            $newJsonArr["filter"][$key]["limit"] = array();  //account_rule对应字段json赋值给新json
                        }
                    } else {
                        $newJsonArr["filter"][$key]["limit"] = $authJson["filter"][$key]["limit"];  //account_rule对应字段json赋值给新json
                    }
                }
                $newJsonArr["filter"][$key]["allowcontrol"] = $authJson["filter"][$key]["allowcontrol"];  //account_rule对应字段json赋值给新json
            }
            if (isset($tmpJson["filter"][$key]["isshow"])) {
                //直接显示时需要合并 tempJson中的isshow, 新增时不需要, 新增时 传入的 $tmpJson和$authJson 相同
                if (count(array_diff($tmpJson, $authJson)) > 0) {
                    $newJsonArr["filter"][$key]["isshow"] = $tmpJson["filter"][$key]["isshow"];
                }
            }
        }
    }

    //判断设置了下载的字段权限时，将权限合并.为空数组时，表示不限制，也合并
    if (!empty($authJson['download_FieldLimit'])) {
        $newJsonArr['download_FieldLimit'] = $authJson['download_FieldLimit'];
    } else {
        $newJsonArr['download_FieldLimit'] = array();//默认不关闭权限
    }
    if (isset($authJson['download_DataLimit'])) {
        $newJsonArr['download_DataLimit'] = $authJson['download_DataLimit'];
    }
    if (isset($authJson['allowDownload'])) {
        $newJsonArr['allowDownload'] = $authJson['allowDownload'];
    } else {
        $newJsonArr['allowDownload'] = false;
    }

    if (isset($authJson['allowupdatesnapshot'])) {
        $newJsonArr['allowupdatesnapshot'] = $authJson['allowupdatesnapshot'];
    } else {
        $newJsonArr['allowupdatesnapshot'] = true;
    }
    if (isset($authJson['alloweventalert'])) {
        $newJsonArr['alloweventalert'] = $authJson['alloweventalert'];
    } else {
        $newJsonArr['alloweventalert'] = true;
    }


    //处理facet的 filterlimit对应的值, 属于统计字段
    if (isset($authJson["facet"]["filterlimit"]["limit"])) {
        $newJsonArr["facet"]["filterlimit"]["limit"] = $authJson["facet"]["filterlimit"]["limit"];
    }
    if (isset($authJson["facet"]["limit"])) {
        if ($newJsonArr['facet']['datatype'] == $authJson["facet"]['datatype']) {
            if (count($authJson["facet"]["limit"]) > 0) {
                $newJsonArr["facet"]["limit"] = $authJson["facet"]["limit"];
            }
        }
        if ($authJson["version"] < 1021) {  //权限中存在的旧字段(后来改为其他名称) 需要改为新的名字
            foreach ($newJsonArr['facet']['limit'] as $fi => $fitem) {
                if ($fitem["value"] == "topic") {
                    $newJsonArr['facet']['limit'][$fi]["value"] = "wb_topic";
                }
            }
        }
        $newJsonArr["facet"]["allowcontrol"] = $authJson["facet"]["allowcontrol"];
    }
    if (isset($authJson["select"]["limit"])) {
        $newJsonArr["select"]["limit"] = $authJson["select"]["limit"];
    }
    if (isset($authJson["output"]["countlimit"]["limit"])) {
        $newJsonArr["output"]["countlimit"]["limit"] = $authJson["output"]["countlimit"]["limit"];
    }
    //elements中对应的value赋值给新版本json
    $newJsonArr["isdefaultrelation"] = $tmpJson["isdefaultrelation"];
    if (isset($tmpJson["facet"]["field"])) {
        if ($tmpJson["version"] < 1021) {
            foreach ($tmpJson['facet']['field'] as $ti => $titem) {  //进行facet查询时, 低版本中facet field 为topic 查询需改为 wb_topic
                if ($titem["name"] == "topic") {
                    $tmpJson['facet']['field'][$ti]["name"] = "wb_topic";
                }
            }
        }

        $newJsonArr["facet"]["field"] = $tmpJson["facet"]["field"];
    }
    if (isset($tmpJson["facet"]["range"])) {
        $newJsonArr["facet"]["range"] = $tmpJson["facet"]["range"];
    }
    if (isset($tmpJson["select"]["value"])) {
        $sltvalue = array_unique(array_merge($newJsonArr["select"]["value"], $tmpJson["select"]["value"]));
        array_multisort($sltvalue);
        $newJsonArr["select"]["value"] = $sltvalue;
    }
    if (isset($tmpJson["output"]["outputtype"])) {
        $newJsonArr["output"]["outputtype"] = $tmpJson["output"]["outputtype"];
    }
    if (isset($tmpJson["output"]["data_limit"])) {
        $newJsonArr["output"]["data_limit"] = $tmpJson["output"]["data_limit"];
    }
    if (isset($tmpJson["output"]["count"])) {
        $newJsonArr["output"]["count"] = $tmpJson["output"]["count"];
    }
    if (isset($tmpJson["output"]["orderby"])) {
        $newJsonArr["output"]["orderby"] = $tmpJson["output"]["orderby"];
    }
    if (isset($tmpJson["output"]["pageable"])) {
        $newJsonArr["output"]["pageable"] = $tmpJson["output"]["pageable"];
    }
    if (isset($tmpJson["output"]["ordertype"])) {
        $newJsonArr["output"]["ordertype"] = $tmpJson["output"]["ordertype"];
    }
    if (isset($tmpJson["distinct"]["distinctfield"])) {
        $newJsonArr["distinct"]["distinctfield"] = $tmpJson["distinct"]["distinctfield"];
    }
    if (isset($newJsonArr["filter"][$tmpJson["classifyquery"]["fieldname"]])) {
        $newJsonArr["classifyquery"] = $tmpJson["classifyquery"];
    } else {
        $newJsonArr["classifyquery"] = null;
    }
    if (isset($newJsonArr["filter"][$tmpJson["contrast"]["filtervalue"][0]["fieldname"]])) {
        $newJsonArr["contrast"] = $tmpJson["contrast"];
    } else {
        $newJsonArr["contrast"] = null;
    }

    $newJsonArr["filterrelation"] = $tmpJson["filterrelation"];
    foreach ($tmpJson["filtervalue"] as $key => $value) {
        if (isset($newJsonArr["filter"][$value["fieldname"]])) {
            $newJsonArr["filtervalue"][] = $value;
        } else { //当新json中 filter去掉某字段后 filtervalue对应字段的fieldvalue置为null
            $value["fieldvalue"] = null;
            $newJsonArr["filtervalue"][] = $value;
        }
    }
    return $newJsonArr;
}

/**
 *
 * 将authJson的limit部分合并到tmpjson中
 * @param $tmpJson element的json
 * @param $authJson  权限json
 */
function getMergeJson($tmpJson, $authJson)
{
    //取权限filter
    //$tmpJson["filter"] = $authJson["filter"];
    foreach ($tmpJson["filter"] as $key => $value) {
        if (isset($authJson["filter"][$key])) {
            if ($tmpJson["filter"][$key]["datatype"] == $authJson["filter"][$key]["datatype"]) {
                if ($key == "nearlytime" || $key == "beforetime" || $key == "untiltime") {
                    if (count($authJson["filter"][$key]["limit"]) > 0 && !array_key_exists("maxvalue", $authJson["filter"][$key]["limit"][0]["value"]) && !array_key_exists("minvalue", $authJson["filter"][$key]["limit"][0]["value"])) {
                        $tmpJson["filter"][$key]["limit"] = $authJson["filter"][$key]["limit"];  //account_rule对应字段json赋值给新json
                    } else {
                        $tmpJson["filter"][$key]["limit"] = array();  //account_rule对应字段json赋值给新json
                    }
                } else {
                    $tmpJson["filter"][$key]["limit"] = $authJson["filter"][$key]["limit"];  //account_rule对应字段json赋值给新json
                }
            }
            $tmpJson["filter"][$key]["allowcontrol"] = $authJson["filter"][$key]["allowcontrol"];  //account_rule对应字段json赋值给新json
        }
    }
    //判断设置了下载的字段权限时，将权限合并.为空数组时，表示不限制，也合并
    if (!empty($authJson['download_FieldLimit'])) {
        $tmpJson['download_FieldLimit'] = $authJson['download_FieldLimit'];
    } else {
        $tmpJson['download_FieldLimit'] = array();//默认关闭权限
    }
    if (isset($authJson['download_DataLimit'])) {
        $tmpJson['download_DataLimit'] = $authJson['download_DataLimit'];
    }
    if (isset($authJson['allowDownload'])) {
        $tmpJson['allowDownload'] = $authJson['allowDownload'];
    } else {
        $tmpJson['allowDownload'] = false;
    }
    if (isset($authJson['allowupdatesnapshot'])) {
        $tmpJson['allowupdatesnapshot'] = $authJson['allowupdatesnapshot'];
    } else {
        $tmpJson['allowupdatesnapshot'] = false;
    }
    if (isset($authJson['alloweventalert'])) {
        $tmpJson['alloweventalert'] = $authJson['alloweventalert'];
    } else {
        $tmpJson['alloweventalert'] = false;
    }

    if (isset($authJson["facet"]["filterlimit"]["limit"])) {
        $tmpJson["facet"]["filterlimit"]["limit"] = $authJson["facet"]["filterlimit"]["limit"];
    }
    if (isset($authJson["facet"]["limit"])) {
        if ($tmpJson['facet']['datatype'] == $authJson["facet"]['datatype']) {
            if (count($authJson["facet"]["limit"]) > 0) {
                $tmpJson["facet"]["limit"] = $authJson["facet"]["limit"];
            }
        }

        $tmpJson["facet"]["allowcontrol"] = $authJson["facet"]["allowcontrol"];
    }

    if (isset($authJson["select"]["limit"])) {
        $tmpJson["select"]["limit"] = $authJson["select"]["limit"];
    }
    if (isset($authJson["output"]["countlimit"]["limit"])) {
        $tmpJson["output"]["countlimit"]["limit"] = $authJson["output"]["countlimit"]["limit"];
    }
    if (isset($authJson["distinct"]["limit"])) {
        $tmpJson["distinct"]["limit"] = $authJson["distinct"]["limit"];
    }
    return $tmpJson;
}

/*
 * limit有值,filtervalue没有值,需要把limit值赋给filtervalue(循环filter,)
 * 因为js中会在limit有值时对filtervalue赋值,但是不从新生成filterrelation
 * filterrelation = null
 * */

function mergefiltervalue($tmpdataJson, $outlimit)
{
    /*
    global $logger;
    $flag = false;
    foreach($tmpdataJson["filter"] as $key=>$value){
        if($value["datatype"] == "time_dynamic_range" || $value["datatype"] == "time_dynamic_state"){
            $innerflag = true;
            if(count($value["limit"]) > 0 && (!array_key_exists("maxvalue", $value["limit"][0]["value"]) && !array_key_exists("minvalue", $value["limit"][0]["value"]))){
                //权限验证不通过前提下,limit有值,filtervalue也有值,但是allowcontrol=0也需要把limit值赋给filtervalue
                //前端,allowcontrol=0时不判断limit
                foreach($tmpdataJson["filtervalue"] as $k=> $v){
                    if($v["fieldname"] == $key){//filtervalue 存在
                        if(!empty($outlimit) && in_array($key, $outlimit["outlimit"]["filter"]) && $value["allowcontrol"]==0){
                            unset($tmpdataJson["filtervalue"][$k]);
                        }
                        else{
                            $innerflag = false;
                        }
                    }
                }
            }
            else{ //当limit没有值,filtervalue有值,且fromlimit==1时,需要重新赋值
                $fromlimitflag = true;
                $fieldvalues = array();
                foreach($tmpdataJson["filtervalue"] as $k=> $v){
                    if($v["fieldname"] == $key){
                        if(!isset($v["fromlimit"]) || $v["fromlimit"]!=1){
                            $fromlimitflag = false;
                        }
                        $fieldvalues[] = $v["fieldvalue"];
                    }
                }
                if($fromlimitflag){ //当elements的filtervalue全部从limit中得到的,重新设置limit后,当limit改变时需要给filteralue重新赋值
                    if(!empty($fieldvalues)){
                        //判断limitvalue和filtervalue是否相同
                        $diff = true;
                        if($value["datatype"] == "time_dynamic_range" && count($value["limit"]) > 0){
                            $sres = array_diff($fieldvalues[0]["value"]["start"], $value["limit"][0]["value"]["start"]);
                            $eres = array_diff($fieldvalues[0]["value"]["end"], $value["limit"][0]["value"]["end"]);
                            if(empty($sres) && empty($eres)){
                                $diff = false;
                            }
                        }
                        else if($value["datatype"] == "time_dynamic_state" && count($value["limit"]) > 0){
                            $res = array_diff($fieldvalues[0]["value"], $value["limit"][0]["value"]);
                            if(empty($res)){ //相同时返回空数组
                                $diff = false;
                            }
                        }

                        if($diff){
                            foreach($tmpdataJson["filtervalue"] as $k=>$v){
                                if($v["fieldname"] == $key){
                                    unset($tmpdataJson["filtervalue"][$k]);
                                }
                            }
                        }
                        else{
                            $innerflag = false;
                        }
                    }
                }
            }
                // 当filtervalue中没有对应的字段时添加新的字段
                if($innerflag){
                    if(count($value["limit"])>0 && (!isset($value["limit"][0]["value"]["maxvalue"]) || !isset($value["limit"][0]["value"]["minvalue"]))){
                        $tmpfilter["fieldname"] = $key;
                        $tmpfilter["fieldvalue"]["datatype"] =  $value["datatype"];
                        $tmpfilter["fieldvalue"]["value"] = $value["limit"][0]["value"];
                        $tmpfilter["fromlimit"] = 1;
                        $tmpdataJson["filtervalue"][] = $tmpfilter;

                        $flag = true; //filterrelattion置为null
                    }
                }
        }
        if($value["datatype"] == "range" || $value["datatype"] == "gaprange"){
            $innerflag = true;
            if(count($value["limit"]) > 0 && ($value["limit"][0]["value"]["maxvalue"]!=null || $value["limit"][0]["value"]["minvalue"]!=null)){
                //权限验证不通过前提下,limit有值,filtervalue也有值,但是allowcontrol=0也需要把limit值赋给filtervalue
                //前端,allowcontrol=0时不判断limit
                foreach($tmpdataJson["filtervalue"] as $k=> $v){
                    if($v["fieldname"] == $key){//filtervalue 存在
                        if(!empty($outlimit) && in_array($key, $outlimit["outlimit"]["filter"]) && $value["allowcontrol"]==0){
                            unset($tmpdataJson["filtervalue"][$k]);
                        }
                        else{
                            $innerflag = false;
                        }
                    }
                }
            }
            else{ //当limit没有值,filtervalue有值,且fromlimit==1时,需要重新赋值
                $fromlimitflag = true;
                $fieldvalues = array();
                foreach($tmpdataJson["filtervalue"] as $k=> $v){
                    if($v["fieldname"] == $key){
                        if(!isset($v["fromlimit"]) || $v["fromlimit"]!=1){
                            $fromlimitflag = false;
                        }
                        $fieldvalues[] = $v["fieldvalue"];
                    }
                }
                if($fromlimitflag){ //当elements的filtervalue全部从limit中得到的,重新设置limit后,当limit改变时需要给filteralue重新赋值
                    if(!empty($fieldvalues)){
                        if($fieldvalues[0]["value"]["start"]!=$value["limit"][0]["value"]["minvalue"] || $fieldvalues[0]["value"]["end"]!=$value["limit"][0]["value"]["maxvalue"]){
                            if($value["datatype"] == "gaprange"){
                                if($fieldvalues[0]["value"]["gap"]!=$value["limit"][0]["value"]["gap"]){
                                    foreach($tmpdataJson["filtervalue"] as $k=>$v){
                                        if($v["fieldname"] == $key){
                                            unset($tmpdataJson["filtervalue"][$k]);
                                        }
                                    }
                                }
                            }
                            else{
                                foreach($tmpdataJson["filtervalue"] as $k=>$v){
                                    if($v["fieldname"] == $key){
                                        unset($tmpdataJson["filtervalue"][$k]);
                                    }
                                }
                            }
                        }
                        else{
                            $innerflag = false;
                        }
                    }
                }
            }
                  //当filtervalue中没有对应的字段时添加新的字段
                if($innerflag){
                    if(count($value["limit"])>0 && ($value["limit"][0]["value"]["maxvalue"]!=null || $value["limit"][0]["value"]["minvalue"]!=null)){
                        $tmpfilter["fieldname"] = $key;
                        $tmpfilter["fieldvalue"]["datatype"] =  $value["datatype"];
                        if($value["datatype"] == "gaprange"){
                            $tmparr = array("start"=> $value["limit"][0]["value"]["minvalue"], "end"=>$value["limit"][0]["value"]["maxvalue"], "gap"=>$value["limit"][0]["value"]["gap"]);
                        }
                        else{
                            $tmparr = array("start"=> $value["limit"][0]["value"]["minvalue"], "end"=>$value["limit"][0]["value"]["maxvalue"]);
                        }
                        $tmpfilter["fieldvalue"]["value"] = $tmparr;
                        $tmpfilter["fromlimit"] = 1;
                        $tmpdataJson["filtervalue"][] = $tmpfilter;

                        $flag = true; //filterrelattion置为null
                    }
                }
        }
        else{ //非range字段
            $innerflag = true;
            if(!empty($value["limit"]) && count($value["limit"])>0){
                foreach($tmpdataJson["filtervalue"] as $i=> $m){
                    if($m["fieldname"] == $key){
                        if(!empty($outlimit) && in_array($key, $outlimit["outlimit"]["filter"]) && $value["allowcontrol"]==0){
                            unset($tmpdataJson["filtervalue"][$i]);
                        }
                        else{
                            $innerflag = false;
                        }
                    }
                }
            }
            else{
                $fromlimitflag = true;
                $fieldvalues = array();
                foreach($tmpdataJson["filtervalue"] as $i=> $m){
                    if($m["fieldname"] == $key){
                        if(!isset($m["fromlimit"]) || $m["fromlimit"]!=1){
                            $fromlimitflag = false;
                            break;
                        }
                        $fieldvalues[] = $m["fieldvalue"];
                    }
                }
                if($fromlimitflag){
                    if(count($fieldvalues)!=count($value["limit"])){ //limit 不同时说明limit改变
                        foreach($tmpdataJson["filtervalue"] as $k=>$v){
                            if($v["fieldname"] == $key){
                                unset($tmpdataJson["filtervalue"][$k]);
                            }
                        }
                        $flag = true;
                    }
                    else{
                        $sameflag = true;
                        foreach($value["limit"] as $j=>$n){
                            foreach($fieldvalues as $kj=>$kn){
                                switch($value["datatype"]){
                                    case "string":
                                    case "int":
                                        if($n["value"]!=$kn["value"]){
                                            $sameflag = false;
                                            break 2;
                                        }
                                        break;
                                    case "value_text_object":
                                    case "blur_value_object":
                                        if($n["value"]["value"]!=$kn["value"]["value"]){
                                            $sameflag = false;
                                            break 2;
                                        }
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                        if(!$sameflag){
                            foreach($tmpdataJson["filtervalue"] as $k=>$v){
                                if($v["fieldname"] == $key){
                                    unset($tmpdataJson["filtervalue"][$k]);
                                }
                            }
                        }
                        else{
                            $innerflag = false;
                        }
                    }
                }
            }
                if($innerflag){ //此分支说明limit已改变 $flag需要置 为true;
                    if(count($value["limit"])>0){
                        foreach($value["limit"] as $ki=>$vi){
                            $tmpfilter["fieldname"] = $key;
                            $tmpfilter["fieldvalue"]["datatype"] =  $value["datatype"];
                            $tmpfilter["fieldvalue"]["value"] = $vi["value"];
                            $tmpfilter["fromlimit"] = 1;
                            $tmpdataJson["filtervalue"][] = $tmpfilter;
                        }
                        $flag = true;
                    }
                }
        }
    }
    if($flag){
        $relation = initFilterRelation($tmpdataJson);
        $tmpdataJson["filterrelation"] = $relation;
        //$tmpdataJson["filterrelation"] = null;
    }
     */
    return $tmpdataJson;
}

/**
 *
 * 存储elementjson之前，移出所有不需要存储到element表的字段
 * @param $datajsonobj
 */
function removeNeedlessProperty(&$datajsonobj)
{
    if (isset($datajsonobj->download_FieldLimit)) {
        unset($datajsonobj->download_FieldLimit);
    }
    if (isset($datajsonobj->download_DataLimit)) {
        unset($datajsonobj->download_DataLimit);
    }
    if (isset($datajsonobj->allowDownload)) {
        unset($datajsonobj->allowDownload);
    }
}

/**
 * 生成模型的filtervalue对象
 * @param filtername
 * @param datatype
 * @param value
 * @param flimit fromlimit是否从权限表读取(account_limit)
 * @param isfeature 是否为特征分类
 * @param exclude 是否包含
 * @returns filtervalue对象
 */
function createFiltervalue($fieldname, $datatype, $value, $flimit = NULL, $isfeature = NULL, $exclude = NULL)
{
    $filtervalue = array();
    $filtervalue["fieldname"] = $fieldname;
    //权限
    if ($flimit != NULL) {
        $filtervalue["fromlimit"] = $flimit;
    } else {
        $filtervalue["fromlimit"] = 0;
    }
    //特征分类
    if ($isfeature != NULL) {
        $filtervalue["isfeature"] = $isfeature;
    } else {
        $filtervalue["isfeature"] = 0;
    }

    //包含
    if ($exclude != NULL) {
        $filtervalue["exclude"] = $exclude;
    } else {
        $filtervalue["exclude"] = 0;
    }
    $fieldvalue = array();
    $fieldvalue["datatype"] = $datatype;
    $fieldvalue["value"] = $value;
    $filtervalue["fieldvalue"] = $fieldvalue;
    return $filtervalue;
}

/*
 * @brief  新增加的filtervalue生成filterrelation, 索引从指定的起始
 * @param  array $dataJson filtervalue
 * @param  int $startindex 起始索引
 * @return 新增加的filtervalue对应的filterrelation
 * @author Bert
 * @date
 * @change 2016-6-28 Bert
 * */
function initFilterRelation($dataJson, $startindex = 0)
{
    global $logger;
    $relation = array("opt" => "and", "filterlist" => array(), "fields" => array());
    $keyvalues = array();//临时对象，将filtervalue按fieldname分组 只保存索引
    foreach ($dataJson["filtervalue"] as $i => $item) {
        $tmpfieldname = "";
        if ($item["fieldname"] == "verified" || $item["fieldname"] == "verified_type") {
            $tmpfieldname = "verified_type";
        } else {
            $tmpfieldname = $item["fieldname"];
        }
        if (!empty($item["isfeature"])) {
            $isfeature = $item["isfeature"];
        } else {
            $isfeature = 0;
        }
        $tmpfield = $tmpfieldname . "_" . $isfeature;
        if (!isset($keyvalues[$tmpfield])) {
            $keyvalues[$tmpfield] = array();
        }
        $keyvalues[$tmpfield][] = $i + $startindex;
    }
    foreach ($keyvalues as $k => $v) {
        if (count($v) == 1) {
            $relation["fields"][] = $v[0];
        } else {
            $childrelation = array("opt" => "or", "filterlist" => array(), "fields" => $v);
            $relation["filterlist"][] = $childrelation;
        }
    }
    return $relation;
}

//$limitemocode = array("110000,4", "110100,*", "130100,4");
//var_dump(emoareaInArray("110100,*", $limitemocode));
function emoareaInArray($code, $limitcode)
{
    $flag = false;
    $tmpcode = explode(",", $code);
    foreach ($limitcode as $key => $value) {
        $ec = explode(",", $value);
        if (checkChildArea($tmpcode[0], $ec[0])) {
            if ($ec[1] == "*") {
                $flag = true;
            } else {
                if ($tmpcode[1] == $ec[1]) {
                    $flag = true;
                }
            }
        }
    }
    return $flag;
}

//$limitcode = array(110000, 110100, 110102, 130100, 130102);
//var_dump(areaInArray(130150, $limitcode));
//判断一个地区是否在属于另一个地区
function areaInArray($code, $limitcode)
{
    $flag = false;
    foreach ($limitcode as $key => $value) {
        if (checkChildArea($code, $value)) {
            $flag = true;
        }
    }
    return $flag;
}

//判断城市县区是否属于本省的, 只能是单向的
function checkChildArea($code, $pcode)
{
    $flag = false;
    $pf = substr($pcode, 0, 2);
    $ps = substr($pcode, 2, 2);
    $pt = substr($pcode, 4, 2);

    $cf = substr($code, 0, 2);
    $cs = substr($code, 2, 2);
    $ct = substr($code, 4, 2);
    if ($pf == $cf) { //省份相同
        if ($ps == $cs) { //城市相同
            if ($pt == $ct) { //县区相同
                //两个地区相同
                $flag = true;
            } else {//属于同一个城市
                if ($pt == "00") {
                    $flag = true; //$code 属于 $pcode
                }
            }
        } else {//属于同一个省
            if ($ps == "00") {
                $flag = true;
            }
        }
    } else { //不是同一个省
        if ($pcode == "CN") { //都属于中国
            $flag = true;
        }
    }
    return $flag;
}

function getFilterLimit($filter, $fieldname)
{
    $fieldArr = array();
    foreach ($filter[$fieldname]["limit"] as $key => $value) {
        $fieldArr[] = getLimitValue($filter[$fieldname]["datatype"], $value);
    }
    return $fieldArr;
}

function checkRegisterTimeGap($filtergap, $limitgap)
{
    $flag = false;
    if ($limitgap == "year") {
        if (in_array($filtergap, array("year", "month", "day", "hour"))) {
            $flag = true;
        }
    } else if ($limitgap == "month") {
        if (in_array($filtergap, array("month", "day", "hour"))) {
            $flag = true;
        }
    } else if ($limitgap == "day") {
        if (in_array($filtergap, array("day", "hour"))) {
            $flag = true;
        }
    } else {
        if ($filtergap == "hour") {
            $flag = true;
        }
    }
    return $flag;
}

function bcdiv_i($first, $second, $scale = 0)
{
    /*
    if(!function_exists("bcdiv")){
        function bcdiv( $first, $second, $scale){
            $res = $first / $second;
            return round($res, $scale);
        }
    }
    else{
        return bcdiv( $first, $second, $scale);
    }
     */
    $res = $first / $second;
    return round($res, $scale);

}

function DateDiff($part, $begin, $end)
{
    $diff = strtotime($end) - strtotime($begin);
    switch ($part) {
        case "year":
            $retval = bcdiv_i($diff, (60 * 60 * 24 * 365));
            break;
        case "month":
            $retval = bcdiv_i($diff, (60 * 60 * 24 * 30));
            break;
        case "week":
            $retval = bcdiv_i($diff, (60 * 60 * 24 * 7));
            break;
        case "day":
            $retval = bcdiv_i($diff, (60 * 60 * 24));
            break;
        case "hour":
            $retval = bcdiv_i($diff, (60 * 60));
            break;
        case "n":
            $retval = bcdiv_i($diff, 60);
            break;
        case "s":
            $retval = $diff;
            break;
    }
    return $retval;
}

function codelevel($code, $isemo)
{
    $codelen = strlen($code);
    if ($codelen == 6) {
        $f = substr($code, 0, 2);
        $s = substr($code, 2, 2);
        $t = substr($code, 4, 2);
        if ($s == '00' && $t == '00') {
            if ($isemo) {
                $areatype = 'emoProvince';
            } else {
                $areatype = 'province';
            }
        } else if ($s != '00' && $t == '00') {
            if ($isemo) {
                $areatype = 'emoCity';
            } else {
                $areatype = 'city';
            }
        } else if ($s != '00' && $t != '00') {
            if ($isemo) {
                $areatype = 'emoDistrict';
            } else {
                $areatype = 'district';
            }
        }
    } else if ($codelen == 2 || $code == 400 || $code == "400") {
        if ($isemo) {
            $areatype = "emoCountry";
        } else {
            $areatype = "country";
        }
    }
    return $areatype;
}

//从solragent 和 getdata  php中移出的函数
function getRangeWhere($fieldname, $rangevalueobj)
{
    global $logger;

    $filtervalueobj["start"] = intval($rangevalueobj["start"])< 0 ? solrEsc($rangevalueobj["start"]):$rangevalueobj["start"];
    $filtervalueobj["end"] = intval($rangevalueobj["end"]) < 0 ? solrEsc( $rangevalueobj["end"]): $rangevalueobj["end"];
  
    if (isset($rangevalueobj["include"])) {
        $filtervalueobj["include"] = $rangevalueobj["include"];
    }
    $tmpwhere = "";
    if ($filtervalueobj["start"] === null && $filtervalueobj["end"] === null) {
        $filtervalueobj["start"] = "*";
        $filtervalueobj["end"] = "*";
        $tmpwhere = "" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "]";
    } else if ($filtervalueobj["start"] === null && $filtervalueobj["end"] !== null) {
        $filtervalueobj["start"] = "*";
        $tmpwhere = "" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "]";
    } else if ($filtervalueobj["start"] !== null && $filtervalueobj["end"] === null) {
        $filtervalueobj["end"] = "*";
        $tmpwhere = "" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "]";
    } else if ($filtervalueobj["start"] !== null && $filtervalueobj["end"] !== null) {
        if (isset($filtervalueobj["include"])) {
            switch ($filtervalueobj["include"]) {
                case 0: //开区间
                    $tmpwhere = "" . $fieldname . ":{" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "}";
                    break;
                case 1: //左闭右开
                    $mid = ($filtervalueobj["start"] + $filtervalueobj["end"]) / 2;
                    $midnum = floor($mid);
                    $tmpwhere = "(" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $midnum . "]+OR+" . $fieldname . ":{" . $midnum . "+TO+" . $filtervalueobj["end"] . "})";
                    break;
                case 2: //左开右闭
                    if ($filtervalueobj["end"] == "*") {
                        $tmpwhere = "(" . $fieldname . ":{" . $filtervalueobj["start"] . "+TO+*})";
                    } else {
                        $mid = ($filtervalueobj["start"] + $filtervalueobj["end"]) / 2;
                        $midnum = floor($mid);
                        $tmpwhere = "(" . $fieldname . ":{" . $filtervalueobj["start"] . "+TO+" . $midnum . "}+OR+" . $fieldname . ":[" . $midnum . "+TO+" . $filtervalueobj["end"] . "])";

                    }
                    break;
                case 3: //闭区间
                    $tmpwhere = "" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "]";
                    break;
                default:
                    $tmpwhere = "" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "]";
                    break;
            }
        } else {
            $tmpwhere = "" . $fieldname . ":[" . $filtervalueobj["start"] . "+TO+" . $filtervalueobj["end"] . "]";
        }
    }
    return $tmpwhere;
}

function gettimeDynamicState($valueobj)
{
    if (isset($valueobj["start"]) && $valueobj["start"] != null && $valueobj["startgap"] != null) {
        $bts = strtotime("-" . $valueobj["start"] . " " . $valueobj["startgap"] . "");
    }
    if (isset($valueobj["end"]) && $valueobj["end"] != null && $valueobj["endgap"] != null) {
        $bte = strtotime("-" . $valueobj["end"] . " " . $valueobj["endgap"] . "");
    } else {
        if (isset($bts) && $bts != null) {
            $bte = strtotime("now");
        }
    }
    if (isset($valueobj["datestate"]) && $valueobj["datestate"] == "beginning") {
        $bts = setDateBeginning($bts, $valueobj["startgap"], "start");
        $bte = setDateBeginning($bte, $valueobj["endgap"], "end");
    }
    if (isset($valueobj["timestate"]) && $valueobj["timestate"] == "beginning") {
        $bts = setTimeBeginning($bts, "start");
        $bte = setTimeBeginning($bte, "end");
    }
    $ret = array();
    if (isset($bts)) {
        $ret["start"] = $bts;
    }
    if (isset($bte)) {
        $ret["end"] = $bte;
    }
    return $ret;
}

function gettimeDynamicRange($valueobj)
{
    if ($valueobj["start"]["start"] != null) {
        $uts = $valueobj["start"]["start"];
        $utsgap = $valueobj["start"]["startgap"];
        $utsthisgap = $valueobj["start"]["startthisgap"];
        $utsto = $valueobj["start"]["startto"];
        $utstogap = $valueobj["start"]["starttogap"];
        $startpoint = sinceToThis($uts, $utsgap, $utsthisgap, $utsto, $utstogap, "beginning");
    }
    if ($valueobj["end"]["end"] != null) {
        $ute = $valueobj["end"]["end"];
        $utegap = $valueobj["end"]["endgap"];
        $utethisgap = $valueobj["end"]["endthisgap"];
        $uteto = $valueobj["end"]["endto"];
        $utetogap = $valueobj["end"]["endtogap"];
        $endpoint = sinceToThis($ute, $utegap, $utethisgap, $uteto, $utetogap, "ending");
    }
    $ret = array();
    if (isset($startpoint)) {
        $ret["start"] = $startpoint;
    }
    if (isset($endpoint)) {
        $ret["end"] = $endpoint;
    }
    return $ret;
}

function facetrangevalue($rangevalue)
{
    $res = array();
    switch ($rangevalue["type"]) {
        case "range":
            if (isset($rangevalue["value"]["start"])) {
                $res["start"] = $rangevalue["value"]["start"];
            }
            if (isset($rangevalue["value"]["end"])) {
                $res["end"] = $rangevalue["value"]["end"];
            }
            break;
        case "time_dynamic_state":
            $res = gettimeDynamicState($rangevalue["value"]);
            break;
        case "time_dynamic_range":
            $res = gettimeDynamicRange($rangevalue["value"]);
            break;
        default:
            break;
    }
    return $res;
}

function getusablegap($gap)
{
    global $logger;
    $gapstr = "";
    $posyear = strpos($gap, "year");
    $posmonth = strpos($gap, "month");
    $posday = strpos($gap, "day");
    //$poshour = strpos($gap, "hour");

    $v;
    $g;
    if ($posyear !== false) {
        $temp = explode("year", $gap);
        $v = $temp[0];
        $g = "year";
    } else if ($posmonth !== false) {
        $temp = explode("month", $gap);
        $v = $temp[0];
        $g = "month";
    } else if ($posday !== false) {
        $temp = explode("day", $gap);
        $v = $temp[0];
        $g = "day";
    }
    return $gapstr = "+" . $v . " " . $g . "";
}

//返回从$start到$end间用$gap分割后的数组, cutend=true时使用日历月分割
function getRangeintervalArr($start, $end, $gap, $cutend = true)
{
    $intervalarr = array();
    $posmonth = strpos($gap, "month");
    if ($cutend && $posmonth !== false) { //日历月
        $formatstr = "Y-m-1 0:0:0";
        $m = date($formatstr, $start);
        $tmpstart = strtotime($m);
    } else { //非日历月和其他gap
        $tmpstart = $start;
    }
    do {
        $temp = array();
        $temp["start"] = $tmpstart < $start ? $start : $tmpstart;
        $gapstr = getusablegap($gap);
        $tmpend = strtotime($gapstr, $tmpstart);
        $temp["end"] = $tmpend > $end ? $end : $tmpend - 1;
        $intervalarr[] = $temp;
        $tmpstart = $tmpend;
    } while ($tmpend < $end);
    return $intervalarr;
}

function getDefaultQparam($modelid)
{
    $str = "";
    switch ($modelid) {
        case 1:
        case 2:
            $str = "users_id:*";
            break;
        case 31:
        case 51:
            $str = "content_type:*";
            break;
        default:
            $str = "*:*";
            break;
    }
    return $str;
}

/**
 *
 * 通过curl发送请求,请求solr数据, url中不能含有空格 否则 response 返回为空
 * @param unknown_type $url
 * @param unknown_type $dataoutput facet 字段名，facetsearch时，用于决定返回值中表示最终数据的字段名
 * @param unknown_type $resulttype 取值范围：field range query。决定从response中如何取结果
 */
function solrRequest($param, $dataoutput, $resulttype, $url = SOLR_URL_SELECT, $needpinyin = false)
{
    $ch = curl_init();
    global $logger, $global_timeoutsec;
    $param = str_replace("\"", "",$param);     
    $logger->debug(__FILE__ . __LINE__ . __FUNCTION__ . " params:url is " . $url . "?" . $param . ", dataoutput:{$dataoutput}, resulttype:{$resulttype}");
    //curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_URL, $url);
    /*
     将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。//
     如果 CURLOPT_RETURNTRANSFER选项被设置，函数执行成功时会返回执行的结果，失败时返回 FALSE 。
     发送ajax请求时如果设置此选项则需要echo 结果，才能请求到结果
     设置false时不需要echo
     */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //设置cURL允许执行的最长秒数。
    if ($global_timeoutsec > 0) {
        curl_setopt($ch, CURLOPT_TIMEOUT, $global_timeoutsec);
    }
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

    //sleep(30);
    //抓取URL并把它传递给浏览器
    $start_url = microtime_float();
    $response = curl_exec($ch); //服务器返回的响应，包括正确和错误信息（数据级别错误）
    $end_url = microtime_float();
    $logger->debug(__FILE__ . __LINE__ . __FUNCTION__ . " start_time:" . date("Y-m-d H:i:s", $start_url) . " end_time:" . date("Y-m-d H:i:s", $end_url) . " use_time:" . ($end_url - $start_url));
    $logger->debug(__FILE__ . __LINE__ . " response " . var_export($response, true));
    //错误处理,包括错误代码,错误信息
    if ($response === false) //不能连接到服务器，curl返回错误（程序级别错误）
    {
        $errorcode;
        $errormsg;
        $errno = curl_errno($ch);
        if (28 == $errno) { //28 CURLE_OPERATION_TIMEDOUT
            $errorcode = WEBERROR_TIMEOUT;
            $errormsg = "后台数据处理中,请稍后重试.";
            $logger->warn(__FILE__ . " " . __FUNCTION__ . " errornum:" . $errno . ", error:" . curl_error($ch));
        } else {
            $errorcode = WEBERROR_CURLERROR;
            $errormsg = "数据分析异常";
            $logger->error(__FUNCTION__ . " " . $url . "?" . $param);
            $logger->error(__FILE__ . " " . __FUNCTION__ . " errornum:" . $errno . ", error:" . curl_error($ch));
        }
        curl_close($ch);
        return getErrorOutput($errorcode, $errormsg);
    }
    //关闭cURL资源，并且释放系统资源
    curl_close($ch);
    //处理返回结果,整理成需要的形式, 对应facet字段
    $fields = array('text', 'pg_text', 'combinWord', 'business', 'screen_name', 'country_code', 'country', 'province_code', 'province', 'city_code', 'city', 'district_code', 'district', 'NRN', 'account', 'organization', 'url', 'topic', 'emotion', 'emoCombin', 'emoNRN', 'emoOrganization', 'emoTopic', 'emoTopicKeyword', 'emoTopicCombinWord', 'emoAccount', 'emoBusiness', 'emoCountry', 'emoProvince', 'emoCity', 'emoDistrict', 'created_at', 'created_year', 'created_month', 'created_day', 'created_hour', 'created_weekday', 'retweeted_status', 'retweeted_guid', 'reply_comment', 'sex', 'verify', 'comments_count', 'direct_comments_count', 'praises_count', 'reposts_count', 'register_time', 'verified_reason', 'verified_type', 'source', 'description', 'wb_topic', 'wb_topic_keyword', 'wb_topic_combinWord', 'originalText', 'similar', 'userid', 'content_type', 'has_picture', 'host_domain', 'total_reposts_count', 'direct_reposts_count', 'followers_count', 'level', 'repost_trend_cursor', 'total_reach_count', 'users_screen_name', 'users_followers_count', 'users_friends_count', 'users_statuses_count', 'users_replys_count', 'users_recommended_count', 'users_level', 'users_created_at', 'users_sourceid', 'users_source_host', 'users_gender', 'users_verified', 'users_verified_type', 'users_verified_reason', 'users_country_code', 'users_province_code', 'users_city_code', 'users_district_code', 'users_description', 'users_friends_id', 'users_favourites_count', 'users_bi_followers_count', 'users_allow_all_act_msg', 'users_allow_all_comment', 'ancestor_text', 'ancestor_organization', 'ancestor_account', 'ancestor_wb_topic', 'ancestor_wb_topic_keyword', 'ancestor_wb_topic_combinWord', 'ancestor_NRN', 'ancestor_combinWord', 'ancestor_business', 'ancestor_country', 'ancestor_province', 'ancestor_city', 'ancestor_district', 'ancestor_emotion', 'ancestor_emoCombin', 'ancestor_emoNRN', 'ancestor_emoOrganization', 'ancestor_emoTopic', 'ancestor_emoTopicKeyword', 'ancestor_emoTopicCombinWord', 'ancestor_emoAccount', 'ancestor_emoBusiness', 'ancestor_emoCountry', 'ancestor_emoProvince', 'ancestor_emoCity', 'ancestor_emoDistrict', 'ancestor_url', 'ancestor_host_domain', 'ancestor_similar', 'floor', 'paragraphid', 'trample_count',
        'satisfaction', 'godRepPer', 'midRepPer', 'wosRepPer', 'godRepNum', 'midRepNum', 'wosRepNum', 'apdRepNum', 'showPicNum', 'cmtStarLevel', 'purchDate', 'productType', 'impress', 'commentTags',
        'isNewPro', 'proClassify', 'proPic', 'proOriPrice', 'proCurPrice', 'proPriPrice', 'promotionInfos', 'productFullName', 'productColor', 'productSize', 'productDesc', 'productComb', 'detailParam', 'stockNum', 'salesNumMonth', 'compName', 'compAddress', 'phoneNum', 'operateTime', 'compURL', 'serviceProm', 'logisticsInfo', 'payMethod', 'compDesMatch', 'logisticsScore', 'serviceScore', 'serviceComment', 'apdComment', 'isFavorite', 'isAttention',
        'question_id', 'answer_id', 'child_post_id', 'question_father_id', 'answer_father_id', 'read_count', 'recommended', 'column', 'column1', 'post_title', 'original_url', 'source_host', 'retweeted_created_at', 'nowLocation');
    //BKList 关键词模糊查询返回结果属性名
    //CKList 短语查询返回结果属性名
    //TKList 关键词分词查询返回结果属性名
    $customArr = array('BKList', 'CKList', 'TKList', 'SameOrSimilar', 'response');
    if ($needpinyin) {
        $chinese2pinyin = new Helper_Spell();
    }
    switch ($resulttype) {
        case "field":
            if (isset($dataoutput) && in_array($dataoutput, $fields)) //facet.fields
            {
                $retData = json_decode($response, true);
                if (is_array($retData) && $retData != null) {
                    $needData = $retData['facet_counts']['facet_fields'][$dataoutput];
                    if (!empty($needData['countList'])) {
                        //添加alias
                        switch ($dataoutput) {
                            case "sex":
                            case "verify":
                            case "verified_type":
                            case "content_type":
                            case "has_picture":
                            case "recommended":
                            case "users_verified":
                            case "users_verified_type":
                            case "users_allow_all_act_msg":
                            case "users_allow_all_comment":
                            case "users_gender":
                            case "users_sourceid":
                            case "users_source_host":
                            case "sourceid":
                            case "source_host":
                            case "country":
                            case "country_code":
                            case "users_country_code":
                            case "province":
                            case "province_code":
                            case "users_province_code":
                            case "city":
                            case "city_code":
                            case "users_city_code":
                            case "district":
                            case "district_code":
                            case "users_district_code":
                                $hasaliasArr = array();
                                foreach ($needData['countList'] as $k => $v) {
                                    if ($dataoutput == 'content_type') {
                                        $needData['countList'][$k]['alias'] = getweibotypealias($v['text']);
                                    } else if ($dataoutput == 'has_picture') {
                                        $needData['countList'][$k]['alias'] = gethaspicturealias($v['text']);
                                    } else if ($dataoutput == 'recommended') {
                                        $needData['countList'][$k]['alias'] = getrecommendedalias($v['text']);
                                    } else if ($dataoutput == 'users_allow_all_act_msg') {
                                        $needData['countList'][$k]['alias'] = getallowallactmsgalias($v['text']);
                                    } else if ($dataoutput == 'users_allow_all_comment') {
                                        $needData['countList'][$k]['alias'] = getallowallcommentalias($v['text']);
                                    } else if ($dataoutput == 'verified_type' || $dataoutput == 'users_verified_type') {
                                        $needData['countList'][$k]['alias'] = getverifiedtypealias($v['text']);
                                    } else if ($dataoutput == 'verify' || $dataoutput == 'users_verified') {
                                        $needData['countList'][$k]['alias'] = getverifyalias($v['text']);
                                    } else if ($dataoutput == 'users_sourceid' || $dataoutput == 'sourceid') {
                                        $needData['countList'][$k]['alias'] = get_source_name($v['text']); //数据来源
                                    } //solr应该返回 alias的, 这里做补充,当solr修改后可以删除
                                    else if ($dataoutput == 'country' || $dataoutput == 'country_code' || $dataoutput == 'users_country_code') {
                                        if (!isset($needData['countList'][$k]['alias'])) {
                                            $name = get_area_name_by_code($v['text']);
                                            $aliasname = $v['text'];
                                            if (!empty($name['another_name'])) {
                                                $aliasname = $name['another_name'];
                                            }
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        } else {
                                            $aliasname = $needData['countList'][$k]['alias'];
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        }
                                    } else if ($dataoutput == 'province' || $dataoutput == 'province_code' || $dataoutput == 'users_province_code') {
                                        if (!isset($needData['countList'][$k]['alias'])) {
                                            $name = get_area_name_by_code(NULL, $v['text']);
                                            $aliasname = $v['text'];
                                            if (!empty($name['another_name'])) {
                                                $aliasname = $name['another_name'];
                                            }
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        } else {
                                            $aliasname = $needData['countList'][$k]['alias'];
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        }
                                    } else if ($dataoutput == 'city' || $dataoutput == 'city_code' || $dataoutput == 'users_city_code') {
                                        if (!isset($needData['countList'][$k]['alias'])) {
                                            $name = get_area_name_by_code(NULL, NULL, $v['text']);
                                            $aliasname = $v['text'];
                                            if (!empty($name['name'])) {
                                                $aliasname = $name['name'];
                                            }
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        } else {
                                            $aliasname = $needData['countList'][$k]['alias'];
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        }
                                    } else if ($dataoutput == 'district' || $dataoutput == 'district_code' || $dataoutput == 'users_district_code') {
                                        if (!isset($needData['countList'][$k]['alias'])) {
                                            $name = get_area_name_by_code(NULL, NULL, NULL, $v['text']);
                                            $aliasname = $v['text'];
                                            if (!empty($name['name'])) {
                                                $aliasname = $name['name'];
                                            }
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        } else {
                                            $aliasname = $needData['countList'][$k]['alias'];
                                            $pinyin = $needpinyin ? $chinese2pinyin->getChineseChar($aliasname, false, false, '', true) : false;
                                            $needData['countList'][$k]['alias'] = $pinyin ? $pinyin : $aliasname;
                                        }
                                    } else if ($dataoutput == 'users_source_host' || $dataoutput == 'source_host') {
                                        $sn = getSourcenameFromHost(array($v['text'])); //数据来源
                                        if (!empty($sn)) {
                                            $needData['countList'][$k]['alias'] = $sn[0]['name'];
                                        } else {
                                            $needData['countList'][$k]['alias'] = $v['text'];
                                        }
                                    } else {
                                        $needData['countList'][$k]['alias'] = gettextalias($v['text']); //性别
                                    }
                                    //当alias相同时, 合并, 主要针对来源,不同域名可能对应相同的来源
                                    if (!empty($hasaliasArr)) {
                                        $found = false;
                                        $hasindex = 0;
                                        foreach ($hasaliasArr as $hi => $hitem) {
                                            if ($hitem['alias'] == $needData['countList'][$k]['alias']) {
                                                $found = true;
                                                $hasindex = $hitem['index'];
                                                break;
                                            }
                                        }
                                        if ($found) {
                                            foreach ($v as $vi => $vitem) {
                                                if (isset($needData['countList'][$hasindex][$vi])) {
                                                    //2016-8-2 Bert text字符串相加会变成数字
                                                    if ($vi != "text") {
                                                        $needData['countList'][$hasindex][$vi] += $needData['countList'][$k][$vi];
                                                    }
                                                }
                                            }
                                            unset($needData['countList'][$k]);
                                        } else {
                                            $hasaliasArr[] = array('index' => $k, 'alias' => $needData['countList'][$k]['alias']);
                                        }
                                    } else {
                                        $hasaliasArr[] = array('index' => $k, 'alias' => $needData['countList'][$k]['alias']);
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                        //修改text为具体的facet字段名, 保存到快照中
                        $tmparr = array("name" => $dataoutput);
                        $needData['facet'] = $tmparr;
                        return $needData;
                    } else {
                        //setErrorMsg('3002', "DATA IS NULL");
                        return array("datanull");
                    }
                } else {
                    $logger->error(__FUNCTION__ . " " . $url . "?" . $param);
                    $logger->error(__FILE__ . " " . __FUNCTION__ . " " . var_export($response, true));
                    //preg_match("/<h1>(.*)<\/h1>/s",$response, $val);
                    //数据分析错误
                    return getErrorOutput(WEBERROR_SOLRERROR, "数据分析错误");
                }
            }
            break;
        case "range":
            if (isset($dataoutput) && in_array($dataoutput, $fields)) //facet.range
            {
                $retData = json_decode($response, true);
                if (is_array($retData) && $retData != null) {
                    if (!empty($retData['facet_counts']['facet_ranges'][$dataoutput]['counts'])) {
                        //为了统一结构在把返回结果组成数组
                        $nD['countList'] = $retData['facet_counts']['facet_ranges'][$dataoutput]['counts'];
                        if (isset($retData['facet_counts']['facet_ranges'][$dataoutput]['before'])) {
                            $nD['before'] = $retData['facet_counts']['facet_ranges'][$dataoutput]['before'];
                        }
                        if (isset($retData['facet_counts']['facet_ranges'][$dataoutput]['after'])) {
                            $nD['after'] = $retData['facet_counts']['facet_ranges'][$dataoutput]['after'];
                        }
                        $nD['count'] = count($nD['countList']);
                        //修改text为具体的facet字段名, 保存到快照中
                        $tmparr = array("name" => $dataoutput);
                        $nD['facet'] = $tmparr;
                        return $nD;
                    } else {
                        //setErrorMsg('3002', "DATA IS NULL");
                        return array("datanull");
                        //return array();
                    }
                } else {
                    //preg_match("/<h1>(.*)<\/h1>/s",$response, $val);
                    $logger->error(__FUNCTION__ . " " . $url . "?" . $param);
                    $logger->error(__FILE__ . " " . __FUNCTION__ . " $response");
                    return getErrorOutput(WEBERROR_SOLRERROR, "数据分析错误");
                }
            }
            break;
        case "query":
            //根据host增加hostname。
            if (isset($dataoutput)) {
                $hosts_article = array();
                $hosts_user = array();
                $retData = json_decode($response, true);
                if (is_array($retData) && $retData != null) {
                    if (!empty($retData[$dataoutput])) {
                        if ($dataoutput == "response") {
                            //先循环取出所有的host
                            //$logger->debug("response docs:: ".var_export($retData["response"]["docs"],true));
                            foreach ($retData["response"]["docs"] as $k => $value) {
                                if (isset($value['source_host'])) {
                                    if (!in_array($value['source_host'], $hosts_article)) {
                                        $hosts_article[] = $value['source_host'];
                                    }
                                }
                                if (isset($value['users_source_host'])) {
                                    if (!in_array($value['users_source_host'], $hosts_user)) {
                                        $hosts_user[] = $value['users_source_host'];
                                    }
                                }
                            }
                            //$logger->debug("hosts_article: ".var_export($hosts_article,true));
                            //$logger->debug("hosts_user: ".var_export($hosts_user,true));
                            //统一查询，查不到的仍然写host。common.php的函数
                            $results_article = getSourcenameFromHost($hosts_article);
                            $results_user = getSourcenameFromHost($hosts_user);
                            //$logger->debug("hosts_article results: ".var_export($results_article,true));
                            //$logger->debug("hosts_user results: ".var_export($results_user,true));
                            //然后再依次插回去
                            foreach ($retData["response"]["docs"] as $k => $value) {
                                if (isset($value['source_host'])) {
                                    foreach ($results_article as $result) {
                                        if ($result['code'] == $value['source_host']) {
                                            $retData["response"]["docs"][$k]['source_hostname'] = $result['name'];
                                            break;
                                        }
                                    }
                                }
                                if (isset($value['users_source_host'])) {
                                    foreach ($results_user as $result) {
                                        //$logger->debug("result: ".var_export($result,true));
                                        //$logger->debug("users_source_host: ".$value['users_source_host']);
                                        if ($result['code'] == $value['users_source_host']) {
                                            $retData["response"]["docs"][$k]['users_source_hostname'] = $result['name'];
                                            break;
                                        }
                                    }
                                }
                            }
                            //$logger->debug("response docs: ".var_export($retData["response"]["docs"],true));
                        }
                    }
                }
            }


            if (in_array($dataoutput, $customArr)) {
                //$retData = json_decode($response, true);
                if (is_array($retData) && $retData != null) {
                    if (!empty($retData[$dataoutput])) {
                        //处理高亮显示
                        if ($dataoutput == "response") {
                            foreach ($retData["response"]["docs"] as $k => $value) {
                                if (isset($value["guid"]) && !empty($retData["highlighting"][$value["guid"]]) && count($retData["highlighting"][$value["guid"]]) > 0) {
                                    if (isset($retData["highlighting"][$value["guid"]]["text"])) {
                                        $retData["response"]["docs"][$k]["text"] = $retData["highlighting"][$value["guid"]]["text"];
                                    }
                                    if (isset($retData["highlighting"][$value["guid"]]["verified_reason"])) {
                                        $retData["response"]["docs"][$k]["verified_reason_highlight"] = $retData["highlighting"][$value["guid"]]["verified_reason"];
                                    } else if (isset($retData["highlighting"][$value["guid"]]["description"])) {
                                        $retData["response"]["docs"][$k]["description_highlight"] = $retData["highlighting"][$value["guid"]]["description"];
                                    }
                                }
                                //----add by jht temp code
                                if (!empty($retData["response"]["docs"][$k]['text'])) {
                                    $retData["response"]["docs"][$k]['text'][0] = transferToBR($retData["response"]["docs"][$k]['text'][0]);
                                    $retData["response"]["docs"][$k]['text'][0] = stripslashes($retData["response"]["docs"][$k]['text'][0]);
                                }
                                if (!empty($retData["response"]["docs"][$k]['verified_reason'])) {
                                    $retData["response"]["docs"][$k]['verified_reason'][0] = stripslashes($retData["response"]["docs"][$k]['verified_reason'][0]);
                                }
                                if (!empty($retData["response"]["docs"][$k]['description'])) {
                                    $retData["response"]["docs"][$k]['description'][0] = stripslashes($retData["response"]["docs"][$k]['description'][0]);
                                }
                                //----
                            }
                        }
                        return $needData = $retData[$dataoutput];
                    } else {
                        //setErrorMsg('3002', "DATA IS NULL");
                        return array("datanull");
                        //return array();
                    }
                } else {
                    $logger->error(__FUNCTION__ . " " . $url . "?" . $param);
                    $logger->error(__FILE__ . " " . __FUNCTION__ . " $response");
                    //preg_match("/<h1>(.*)<\/h1>/s",$response, $val);
                    return getErrorOutput(WEBERROR_SOLRERROR, "数据分析错误");
                }
            }
            break;
        default:
            break;
    }
}


/*
 *q条件中中文的词要加双引号,否则会进行分词
 * */
//统一返回的数据结构$categoryname, $totalcount, $datalist
//[{categoryname:张三, totalcount:2, datalist:[{"frq":"38496","text":"世界"},{"frq":"12689","text":"孩子"}]},{categoryname:张三, totalcount:2, datalist:[{"frq":"38496","text":"世界"},{"frq":"12689","text":"孩子"}]}]
function formatResult($resultData)
{
    global $logger;
    $resD = array();
    /*
    if(isset($resultData[0][0]) &&  $resultData[0][0] == "datanull"){
        $resD = array();
    }
    else */
    if (!empty($resultData)) {
        foreach ($resultData as $key => $value) {
            $fR['categoryname'] = $value['categoryname'];
            if (isset($value['categoryvalue'])) {
                $fR['categoryvalue'] = $value['categoryvalue'];
            }
            if (isset($value[0]) && $value[0] == "datanull") {
                $fR['totalcount'] = 0;
                $fR['datalist'] = array();
            } else {
                if (isset($value['count'])) {
                    $fR['totalcount'] = $value['count'];
                } else {
                    $fR['totalcount'] = $value['numFound'];
                }
                if (isset($value['facet'])) {
                    $fR['facet'] = $value['facet'];
                }
                if (isset($value['countList'])) {
                    $fR['datalist'] = $value['countList'];
                } else {
                    $fR['datalist'] = $value['docs'];
                }
                if (isset($value["before"])) {
                    $fR['before'] = $value['before'];
                }
                if (isset($value["after"])) {
                    $fR['after'] = $value['after'];
                }
            }

            $resD[] = $fR;
        }
    }
    return $resD;
}

/**
 *
 * 调用solr查询时，生成join查询的facet参数
 * @param unknown_type $datajson
 */
function getJoinFacetURL($datajson, $offset, $limit)
{
    global $logger;
    $avg = !empty($datajson['facet']['average']) ? "true" : "false";
    if (isset($datajson['facet']['field'][0]['facettype'])) {
        $facettype = $datajson['facet']['field'][0]['facettype'];
    }
    if (isset($datajson['facet']['field'][0]['allcount'])) {
        $allcount = $datajson['facet']['field'][0]['allcount'] ? "true" : "false";
    }
    if (isset($datajson['facet']['field'][0]['allusercount'])) {
        $allusercount = $datajson['facet']['field'][0]['allusercount'] ? "true" : "false";
    }
    $str = " method=facet facet.field={$datajson['facet']['field'][0]['name']} facetCounts=2";
    if (!empty($datajson['facet']['field'][0]['isfeature'])) {
        $str .= " facet.to=feature_class facet.from=feature_keyword";
        if (!empty($datajson['facet']['field'][0]['feature'])) {
            $featurewhere = array();
            //feature是value_text_object数组
            foreach ($datajson['facet']['field'][0]['feature'] as $feakey => $feavalue) {
                //$featurewhere[] = "feature_class:".solrPreg($feavalue['value']);
                if (!empty($feavalue['guid'])) {
                    $featurewhere[] = "guid:" . solrPreg($feavalue['guid']);
                }
            }
            if (!empty($featurewhere)) {
                $str .= " facet.query=\"feature_field:{$datajson['facet']['field'][0]['name']}+AND+(" . implode(" OR ", $featurewhere) . ")\"";
            }
        } else {
            $str .= " facet.query=\"feature_field:{$datajson['facet']['field'][0]['name']}\"";
        }
    }
    if (isset($offset) && isset($limit)) {
        $str .= " facet.offset={$offset} facet.limit={$limit}";
    } else {
        $str .= " facet.offset=0 facet.limit=-1";
    }
    if (isset($allcount)) {
        $str .= " facet.average={$avg} facet.type={$facettype} facet.allcount={$allcount}";
    }
    if (isset($allusercount)) {
        $str .= " facet.average={$avg} facet.type={$facettype} facet.allusercount={$allusercount}";
    }
    if (!empty($datajson["distinct"]["distinctfield"])) {
        $str .= " distinctType=facet facet.distinct={$datajson["distinct"]["distinctfield"]}";
    }
    $str .= " facet.minsumcount=1";
    foreach ($datajson['facet']['field'][0]["filter"] as $k => $v) { //include exclude
        if (!empty($v["value"]) && count($v["value"]) > 0) {
            $tmpArr = array();
            foreach ($v["value"] as $i => $item) {  //value_text_object类型 取value 其他类型 取text
                if (is_array($item)) {
                    if ($item["value"] != "") {
                        //$item["value"]= preg_replace("/\*$/", "?", $item["value"]);
                        $tmpArr[] = solrPreg($item["value"]);
                    } else {
                        //$item["text"]= preg_replace("/\*$/", "?", $item["text"]);
                        $tmpArr[] = solrPreg($item["text"]);
                    }
                } else {
                    $tmpArr[] = solrPreg($item);
                }
            }
            $filtervalue = implode(" ", $tmpArr);
            $str .= " facet.filter.type=" . $v["type"] . " " . $v["type"] . "=\"" . $filtervalue . "\"";
        }
    }
    if (isset($datajson['facet']['field'][0]["facetcalculate"])) {
        $arg_output_calc = $datajson['facet']['field'][0]["facetcalculate"];
        if (count($arg_output_calc) > 0) {
            $fieldarr = array();
            $sumarr = array();
            $countarr = array();
            $averagearr = array();
            $maxarr = array();
            $minarr = array();
            foreach ($arg_output_calc as $ai => $aitem) {
                if (isset($aitem["calctype"])) {
                    $aitem["code"] = getRealSolrFieldName($aitem['code']);
                    switch ($aitem["calctype"]) {
                        case "sum":
                            $sumarr[] = $aitem["code"];
                            break;
                        case "count":
                            $countarr[] = $aitem["code"];
                            break;
                        case "average":
                            $averagearr[] = $aitem["code"];
                            break;
                        case "max":
                            $maxarr[] = $aitem["code"];
                            break;
                        case "min":
                            $minarr[] = $aitem["code"];
                            break;
                        case "field":
                            $fieldarr[] = $aitem["code"];
                            break;
                        default:
                            break;
                    }
                } else {
                    $fieldarr[] = $aitem["code"];
                }
            }
            if (count($fieldarr) > 0) {
                $str .= " facet.calculate.field=" . implode(",", $fieldarr);
            }
            if (count($sumarr) > 0) {
                $str .= " facet.calculate.sum=" . implode(",", $sumarr);
            }
            if (count($countarr) > 0) {
                $str .= " facet.calculate.count=" . implode(",", $countarr);
            }
            if (count($averagearr) > 0) {
                $str .= " facet.calculate.average=" . implode(",", $averagearr);
            }
            if (count($maxarr) > 0) {
                $str .= " facet.calculate.max=" . implode(",", $maxarr);
            }
            if (count($minarr) > 0) {
                $str .= " facet.calculate.min=" . implode(",", $minarr);
            }
        }
    }
    return urlencode($str);
}

/* 没有被调用 需删除
function getUserOrderByAddition($fieldname){
    $addtion = "";
    switch ($fieldname){
        case 'no_id':
        case 'followers_count':
        case 'friends_count':
        case 'statuses_count':
        case 'favourites_count':
        case 'created_at':
        case 'follower_level':
        case 'friend_level':
        case 'sourceid':
            $addtion = " and {$fieldname} > -1 ";
            break;
        default:
            break;
    }
    return $addtion;
}
 */

/**
 *
 * 生成solrurl中非q的参数字符串
 */
function buildSolrParamsExQ($fieldArr)
{
    global $logger;
    $param = '';
    //select
    if(!empty($fieldArr["isdrilldown"])) {
        $arg_select_field = $fieldArr["select"]["value"];
        if (isset($_SESSION['user'])) {
            foreach ($arg_select_field as $key => $value) {
                if ($value === "article_taginfo") {
                    $userid = $_SESSION['user']->getuserid();
                    $arg_select_field[$key] = getUserArticleTaginfoField($userid);
                }
            }
        }
        $selectfield = implode(",", $arg_select_field);
        $param .= "&fl=". $selectfield;
    }
    ///facet
    $field = "text";
    $facetttype = "field";
    $facetfield = "text";
    if(empty($fieldArr["isdrilldown"])) {
        $arg_facet_field = $fieldArr["facet"]["field"];
        if (count($arg_facet_field) > 0) {
            foreach ($arg_facet_field as $key => $value) {
                //test 目前只支持一个字段facet
                $field = $value["name"];
                if (!empty($value['isfeature'])) {
                    //facet特征分类时，solr会执行二次查询，facet.to最终表示查询哪个字段，facet.from是指用哪个字段当条件
                    $param .= '&facet.to=feature_class&facet.from=feature_keyword';
                    if (!empty($value['feature'])) {
                        $featurewhere = array();
                        //feature为value_text_object数组
                        foreach ($value['feature'] as $feakey => $feavalue) {
                            //$featurewhere[] = "feature_class:".solrEsc($feavalue['value']);

                            $resultArr = array();
                            //begin:处理快照时保存的第一个guid给删除了  by zuo:2016-12-14
                            getAllFeatureByID($feavalue['guid'], $field, $resultArr, true, $feavalue['value']);
                            //begin:add by zuo:2016-12-14
                            if (!empty($resultArr)) {
                                if (!isset($resultArr["featureclasserror"])) {
                                    foreach ($resultArr as $ri => $ritem) {
                                        if (!empty($ritem['guid'])) {
                                            $featurewhere[] = "guid:" . $ritem['guid'];
                                        }
                                    }
                                } else {
                                    return $resultArr;
                                }
                                //end:add by zuo:2016-12-14
                            } else {
                                if (!empty($feavalue['guid'])) {
                                    $featurewhere[] = "guid:" . $feavalue['guid'];
                                }
                            }
                        }
                        if (!empty($featurewhere)) {
                            //$param .= "&facet.query=feature_field:{$field}+AND+(".implode("+OR+",$featurewhere).")";
                            //2016-8-19 Bert guid能唯一确定记录 ,不用添加feature_field
                            $param .= "&facet.query=(" . implode("+OR+", $featurewhere) . ")";
                        }
                    } else {
                        $param .= "&facet.query=feature_field:{$field}";
                    }
                    $param .= "&facet.sj=true&facet.sj.trans.fromField=guid&facet.sj.trans.toField=feature_father_guid";
                }
                $param .= '&facet.field=' . $field;
                $param .= "&facetCounts=2";
                $facetfield = $field;
                $needAlias = array("business", "country", "country_code", "city_code", "city", "district", "district_code", "province", "province_code", "sourceid", "account", "userid", 'emotion', 'emoCombin', 'emoNRN', 'emoOrganization', 'emoTopic', 'emoTopicKeyword', 'emoTopicCombinWord', 'emoAccount', 'emoBusiness', "emoCountry", "emoProvince", "emoCity", "emoDistrict", "users_id", "users_sourceid", "users_country_code", "users_province_code", "users_city_code", "users_district_code", "users_friends_id", "ancestor_business", "ancestor_country", "ancestor_country_code", "ancestor_city_code", "ancestor_city", "ancestor_district", "ancestor_district_code", "ancestor_province", "ancestor_province_code", "ancestor_sourceid", "ancestor_account", "ancestor_userid", "ancestor_emotion", "ancestor_emoCombin", "ancestor_emoNRN", "ancestor_emoOrganization", "ancestor_emoTopic", "ancestor_emoTopicKeyword", "ancestor_emoTopicCombinWord", "ancestor_emoAccount", "ancestor_emoBusiness", "ancestor_emoCountry", "ancestor_emoProvince", "ancestor_emoCity", "ancestor_emoDistrict"); //需要添加参数

            if (empty($value['isfeature']) && in_array($field, $needAlias)) {
                $param .= '&facet.showAlias=true'; //地区行业 显示 别名
                switch ($field) {
                    case "users_friends_id":
                    case "userid":
                    case "account":
                    case "emoAccount":
                    case "ancestor_account":
                    case "ancestor_emoAccount":
                        $param .= '&facet.to=users_screen_name&facet.from=users_id'; //用户显示 别名
                        break;
                    default:
                        break;
                }
            }
            //过滤器包含 显示其他 showother-> 1: 特征分类的包含显示其他  , 2: 非特征分类的包含显示其他 , 3: 两个都有其他
            if ((isset($value["featureconfig"]) && $value["featureconfig"]["showother"] == 1) &&
                (isset($value["includeconfig"]) && $value["includeconfig"]["showother"] == 1)
            ) {
                $param .= '&facet.showother=3';
            } else if (isset($value["featureconfig"]) && $value["featureconfig"]["showother"] == 1) {
                $param .= '&facet.showother=1';
            } else if (isset($value["includeconfig"]) && $value["includeconfig"]["showother"] == 1) {
                $param .= '&facet.showother=2';
            }

            foreach ($value["filter"] as $k => $v) { //include exclude
                if (!empty($v["value"]) && count($v["value"]) > 0) {
                    $tmpArr = array();
                    foreach ($v["value"] as $i => $item) {  //value_text_object类型 取value 其他类型 取text
                        if (is_array($item)) {
                            if ($item["value"] != "") {
                                //$item["value"]= preg_replace("/\*$/", "?", $item["value"]); //?单字匹配 * 全匹配
                                $tmpArr[] = $item["value"];
                            } else {
                                //$item["text"]= preg_replace("/\*$/", "?", $item["text"]);
                                $tmpArr[] = $item["text"];
                            }
                        } else {
                            $tmpArr[] = $item;
                        }
                    }
                    $filtervalue = implode(" ", $tmpArr);
                    $param .= "&facet.filter.type=" . $v["type"] . "&" . $v["type"] . "=" . urlencode($filtervalue);
                    //$param .= "&facet.filter.type=".$v["type"]."&".$filtervalue;
                }
            }
            if (isset($value["facettype"])) {
                $param .= '&facet.type=' . $value["facettype"];
            }
            //指定某个字段显示f.text.facet.allcount = true 或显示全部//text 是对应facet.field
            if (isset($value["allcount"])) {
                if ($value["allcount"]) {
                    $param .= "&facet.allcount=true";
                } else {
                    $param .= "&facet.allcount=false";
                }
            }
            if (isset($value["allusercount"])) {
                if ($value["allusercount"]) {
                    $param .= "&facet.allusercount=true";
                } else {
                    $param .= "&facet.allusercount=false";
                }
            }
            //显示平均
            if (isset($value["average"])) {
                if ($value["average"]) {
                    $param .= "&facet.average=true";
                }
            }
            //旧版接口end
            //新版接口
            //facet.calculate.sum=reposts_count\ADD(comments_count\ADD1),3\ADDreposts_count-2\ADDcomments_count\ADD1-1,(reposts_count*2),reposts_count/2,reposts_count\MOD10,comments_count
            if (isset($value["facetcalculate"])) {
                $arg_output_calc = $value["facetcalculate"];
                if (count($arg_output_calc) > 0) {
                    $fieldarr = array();
                    $sumarr = array();
                    $countarr = array();
                    $averagearr = array();
                    $maxarr = array();
                    $minarr = array();
                    foreach ($arg_output_calc as $ai => $aitem) {
                        if (isset($aitem["calctype"])) {
                            $aitem["code"] = getRealSolrFieldName($aitem['code']);
                            switch ($aitem["calctype"]) {
                                case "sum":
                                    $sumarr[] = $aitem["code"];
                                    break;
                                case "count":
                                    $countarr[] = $aitem["code"];
                                    break;
                                case "average":
                                    $averagearr[] = $aitem["code"];
                                    break;
                                case "max":
                                    $maxarr[] = $aitem["code"];
                                    break;
                                case "min":
                                    $minarr[] = $aitem["code"];
                                    break;
                                case "field":
                                    $fieldarr[] = $aitem["code"];
                                    break;
                                default:
                                    break;
                            }
                        } else {
                            $fieldarr[] = $aitem["code"];
                        }
                    }
                    if (count($fieldarr) > 0) {
                        $param .= "&facet.calculate.field=" . implode(",", $fieldarr);
                    }
                    if (count($sumarr) > 0) {
                        $param .= "&facet.calculate.sum=" . implode(",", $sumarr);
                    }
                    if (count($countarr) > 0) {
                        $param .= "&facet.calculate.count=" . implode(",", $countarr);
                    }
                    if (count($averagearr) > 0) {
                        $param .= "&facet.calculate.average=" . implode(",", $averagearr);
                    }
                    if (count($maxarr) > 0) {
                        $param .= "&facet.calculate.max=" . implode(",", $maxarr);
                    }
                    if (count($minarr) > 0) {
                        $param .= "&facet.calculate.min=" . implode(",", $minarr);
                    }
                }
            }
            $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " fieldArr:" . var_export($fieldArr["output"], true));
            if (isset($fieldArr["output"]["sort"])) {
                $arg_output_sort = $fieldArr["output"]["sort"];
                if (count($arg_output_sort) > 0) {
                    $sortstr = array();
                    foreach ($arg_output_sort as $si => $sitem) {
                        //facet.calculate.sort=sum:reposts_count\ADD(comments_count\ADD1)+desc,count:id+asc
                        if (!isset($sitem['calctype'])) {
                            $sitem['calctype'] = "field";
                        }
                        $sortstr[] = "" . $sitem['calctype'] . ":" . $sitem['orderby'] . "+" . $sitem['ordertype'] . "";
                    }
                    if (count($sortstr) > 0) {
                        $param .= "&facet.calculate.sort=" . implode(",", $sortstr);
                    }
                } else {
                    $param .= "&facet.calculate.sort=count:created_at+desc";
                }
            } else {
                $param .= "&facet.calculate.sort=count:created_at+desc";
            }
        }
    }
    $arg_facet_range = $fieldArr["facet"]["range"];
    if (count($arg_facet_range) > 0) {
        $facetttype = "range";
        foreach ($arg_facet_range as $key => $value) {
            $field = $value["name"];
            $facetfield = $field;
            $param .= '&facet.range=' . $value["name"];
            $param .= "&facetCounts=8"; //返回结果中只包含 range
            if (isset($value["include"])) {
                foreach ($value["include"] as $vi => $vitem) {
                    $param .= "&facet.range.include=" . $vitem . "";
                }
            } else {
                $param .= "&facet.range.include=edge&facet.range.include=lower"; // lower表示返回区间为左闭右开 edge表示最后一个区间左闭右闭
            }
            if (!isset($value["rangeinfo"])) { // $fieldArr["version"]<1025  兼容 rangejson结构改变前的查询
                $arg_registertime_gap = "day";
                if (isset($value["gap"])) {
                    $param .= "&f." . $value["name"] . ".facet.range.gap=" . $value["gap"];

                    $posyear = strpos($value["gap"], "year");
                    $posmonth = strpos($value["gap"], "month");
                    $posday = strpos($value["gap"], "day");
                    //$poshour = strpos($value["gap"], "hour");

                    if ($posyear !== false) {
                        $arg_registertime_gap = "year";
                    } else if ($posmonth !== false) {
                        $arg_registertime_gap = "month";
                    } else if ($posday !== false) {
                        $arg_registertime_gap = "day";
                    }
                }
                if (isset($value["start"])) {
                    if ($value["name"] == "register_time" || $value["name"] == "users_created_at") {
                        $start = strtotime("-" . $value["end"] . " " . $arg_registertime_gap . "");
                        //$start = strtotime("now") - $value["end"] * 24 * 3600;
                    } else {
                        $start = $value["start"];
                    }
                    $param .= "&f." . $value["name"] . ".facet.range.start=" . $start;
                }
                if (isset($value["end"])) {
                    if ($value["name"] == "register_time" || $value["name"] == "users_created_at") {
                        $end = strtotime("-" . $value["start"] . " " . $arg_registertime_gap . "");
                        //$end = strtotime("now") - $value["start"] * 24 * 3600;
                    } else {
                        $end = $value["end"];
                    }
                    $param .= "&f." . $value["name"] . ".facet.range.end=" . $end;
                }
                if (isset($value["gapCount"])) {
                    $param .= "&f." . $value["name"] . ".facet.range.gapCount=" . $value["gapCount"];
                }
            } else { // 新版range json结构查询
                switch ($value["rangeinfo"]["type"]) {
                    case "gap":
                        //根据rangevalue生成facet查询的start和end
                        $param .= "&f." . $value["name"] . ".facet.range.gap=" . $value["rangeinfo"]["value"]["gap"];
                        break;
                    case "gapcount":
                        //根据rangevalue生成facet查询的start和end
                        $param .= "&f." . $value["name"] . ".facet.range.gapCount=" . $value["rangeinfo"]["value"]["gapcount"];
                        $param .= "&f." . $value["name"] . ".facet.range.hardend=true";
                        break;
                    case "gaplist":
                        $sarr = array();
                        $earr = array();
                        foreach ($value["rangeinfo"]["value"] as $key => $vitem) {
                            //对应每一个vitme 生成facet查询的start和end
                            $gtarr = facetrangevalue($vitem);
                            if (isset($gtarr["start"]) && isset($gtarr["end"])) {
                                if ($gtarr["end"] > $gtarr["start"]) {
                                    $start = $gtarr["start"];
                                    $end = $gtarr["end"];
                                } else { //博龄字段
                                    $start = $gtarr["end"];
                                    $end = $gtarr["start"];
                                }
                                $sarr[] = $start;
                                $earr[] = $end;
                            }
                        }
                        $param .= "&f." . $value["name"] . ".facet.range.start=" . implode(",", $sarr);
                        $param .= "&f." . $value["name"] . ".facet.range.end=" . implode(",", $earr);
                        break;
                }
                if ($value["rangeinfo"]["type"] == "gap" || $value["rangeinfo"]["type"] == "gapcount") {
                    $gtarr = facetrangevalue($value["rangeinfo"]["value"]["rangevalue"]);
                    if (isset($gtarr["start"]) && isset($gtarr["end"])) {
                        if (($gtarr["end"] != "" || $gtarr["end"] != null) && ($gtarr["start"] != "" || $gtarr["start"] != null) && $gtarr["end"] < $gtarr["start"]) {//博龄字段
                            $start = $gtarr["end"];
                            $end = $gtarr["start"];
                        } else {
                            $start = $gtarr["start"];
                            $end = $gtarr["end"];
                        }
                        $param .= "&f." . $value["name"] . ".facet.range.start=" . $start;
                        $param .= "&f." . $value["name"] . ".facet.range.end=" . $end;
                    } else if (isset($gtarr["start"])) {
                        $start = $gtarr["start"];
                        $param .= "&f." . $value["name"] . ".facet.range.start=" . $start;
                    } else if (isset($gtarr["end"])) {
                        $end = $gtarr["end"];
                        $param .= "&f." . $value["name"] . ".facet.range.end=" . $end;
                    }
                }
            }
            if (isset($value["sides"])) {
                switch ($value["sides"]) {
                    case "0":
                        $param .= "&f." . $value["name"] . ".facet.range.other=none";
                        break;
                    case "1":
                        $param .= "&f." . $value["name"] . ".facet.range.other=before";
                        $param .= "&f." . $value["name"] . ".facet.range.other=after";
                        break;
                    case "2":
                        $param .= "&f." . $value["name"] . ".facet.range.other=before";
                        break;
                    case "3":
                        $param .= "&f." . $value["name"] . ".facet.range.other=after";
                        break;
                }
            }

                if ($value["name"] == "register_time" || $value["name"] == "users_created_at") {
                    $param .= "&facet.range.cutEndTime=false"; //true 为日历月 false 为非日历月
                }
                //$param .= "&f.".$value["name"].".facet.range.hardend=true"; //非整月(2011-07-03 到 2011-08-03)显示
                if (isset($value["facetcalculate"])) {
                    $arg_output_calc = $value["facetcalculate"];
                    if (count($arg_output_calc) > 0) {
                        $fieldarr = array();
                        $sumarr = array();
                        $countarr = array();
                        $averagearr = array();
                        $maxarr = array();
                        $minarr = array();
                        foreach ($arg_output_calc as $ai => $aitem) {
                            if (isset($aitem["calctype"])) {
                                $aitem["code"] = getRealSolrFieldName($aitem['code']);
                                switch ($aitem["calctype"]) {
                                    case "sum":
                                        $sumarr[] = $aitem["code"];
                                        break;
                                    case "count":
                                        $countarr[] = $aitem["code"];
                                        break;
                                    case "average":
                                        $averagearr[] = $aitem["code"];
                                        break;
                                    case "max":
                                        $maxarr[] = $aitem["code"];
                                        break;
                                    case "min":
                                        $minarr[] = $aitem["code"];
                                        break;
                                    case "field":
                                        $fieldarr[] = $aitem["code"];
                                        break;
                                    default:
                                        break;
                                }
                            } else {
                                $fieldarr[] = $aitem["code"];
                            }
                        }
                        if (count($fieldarr) > 0) {
                            $param .= "&facet.calculate.field=" . implode(",", $fieldarr);
                        }
                        if (count($sumarr) > 0) {
                            $param .= "&facet.calculate.sum=" . implode(",", $sumarr);
                        }
                        if (count($countarr) > 0) {
                            $param .= "&facet.calculate.count=" . implode(",", $countarr);
                        }
                        if (count($averagearr) > 0) {
                            $param .= "&facet.calculate.average=" . implode(",", $averagearr);
                        }
                        if (count($maxarr) > 0) {
                            $param .= "&facet.calculate.max=" . implode(",", $maxarr);
                        }
                        if (count($minarr) > 0) {
                            $param .= "&facet.calculate.min=" . implode(",", $minarr);
                        }
                    }
                }
                //$logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " fieldArr:" . var_export($fieldArr["output"], true));
                if (isset($fieldArr["output"]["sort"])) {
                    $arg_output_sort = $fieldArr["output"]["sort"];
                    if (count($arg_output_sort) > 0) {
                        $sortstr = array();
                        foreach ($arg_output_sort as $si => $sitem) {
                            //facet.calculate.sort=sum:reposts_count\ADD(comments_count\ADD1)+desc,count:id+asc
                            if (!isset($sitem['calctype'])) {
                                $sitem['calctype'] = "field";
                            }
                            $sortstr[] = "" . $sitem['calctype'] . ":" . $sitem['orderby'] . "+" . $sitem['ordertype'] . "";
                        }
                        if (count($sortstr) > 0) {
                            $param .= "&facet.calculate.sort=" . implode(",", $sortstr);
                        }
                    } else {
                        $param .= "&facet.calculate.sort=count:created_at+desc";
                    }
                } else {
                    $param .= "&facet.calculate.sort=count:created_at+desc";
                }
            }
        }
    }
    ///output
    $arg_output_outputtype = $fieldArr["output"]["outputtype"];
    if ($arg_output_outputtype == 1) { //query;
        $facetttype = "query";
        $field = "response";
        $arg_output_data_limit = $fieldArr["output"]["data_limit"];
        $param .= "&start=" . $arg_output_data_limit;
        $arg_output_count = $fieldArr["output"]["count"];
        $param .= "&rows=" . $arg_output_count;
        switch ($facetfield) {
            case "verified_reason":
            case "description":
            case "users_verified_reason":
            case "users_description":
                $ff = "," . $facetfield;
                break;
            default:
                $ff = "";
                break;
        }
        $param .= "&facet=off";
        //下载时不需要赋值highlight, fieldArr['isdownload'] 在下载时请求参数赋值$dp['isdownload'] = 1;
        if (isset($fieldArr['isdownload']) && $fieldArr['isdownload'] == 1) {
            $param .= "";
        } else {
            $param .= "&hl=on&hl.fragsize=50000&hl.fl=text" . $ff . "&hl.requireFieldMatch=true&hl.highlightMultiTerm=true";
        }
        //当时drilldown查询时需要添加查询参数usecache为保持facet后的drilldown结果一致
        if (isset($fieldArr["isdrilldown"]) && $fieldArr["isdrilldown"]) {
            $param .= "&usecache=true";
        }

        //微博为转发时显示对应原创
        //$param .= "&returnOriginal=true";

        //当页面提交orderby字段时, 按照对应字段排序 默认按照 solr文档顺序
        if (!empty($fieldArr["output"]["orderby"])) {
            $arg_output_orderby = $fieldArr["output"]["orderby"];
            if ($arg_output_orderby == "") {
                switch ($fieldArr["modelid"]) {
                    case 1:
                        $arg_output_orderby = "users_followers_count";
                        break;
                    case 51:
                        $arg_output_orderby = "created_at";
                        break;
                    default:
                        break;
                }
            }
            if ($arg_output_orderby != "") {
                $arg_output_ordertype = $fieldArr["output"]["ordertype"];
                $param .= "&sort=" . $arg_output_orderby . "+" . $arg_output_ordertype;
            }
        }
        if (isset($fieldArr["output"]["sort"])) {
            $arg_output_sort = $fieldArr["output"]["sort"];
            if (count($arg_output_sort) > 0) {
                $sortstr = array();
                foreach ($arg_output_sort as $si => $sitem) {
                    $sortstr[] = "" . $sitem['orderby'] . "+" . $sitem['ordertype'] . "";
                }
                $param .= "&sort=" . implode(",", $sortstr);
            }
        }

        //结果唯一字段
        if (isset($fieldArr['distinct'])) {
            $arg_distinct_field = $fieldArr["distinct"]["distinctfield"];
            if ($arg_distinct_field != "") {
                $param .= "&distinctType=query&query.distinct=" . $arg_distinct_field;
            }
        }
    } else { //facet
        $arg_output_count = $fieldArr["output"]["count"];
        $param .= "&facet.limit=" . $arg_output_count;
        $arg_output_data_limit = $fieldArr["output"]["data_limit"];
        $param .= "&facet=on&facet.offset=" . $arg_output_data_limit;
        $param .= "&rows=0";  //不返回response结果
        /*
        if(isset($fieldArr["classifyquery"])){
            $param .= "&facet.minsumcount=0&facet.mincount=0";  //当分类查询时,需要返回frq为0的数据
        }
        else{
        }
         */
        $param .= "&facet.minsumcount=1";
        if (isset($fieldArr['distinct'])) {
            $arg_distinct_field = $fieldArr["distinct"]["distinctfield"];
            if ($arg_distinct_field != "") {
                $param .= "&distinctType=facet&facet.distinct=" . $arg_distinct_field;
            }
        }
    }
    return array("facetttype" => $facetttype, "field" => $field, "facetfield" => $facetfield, "param" => $param);
}

/**
 *
 * 获取数据库user表真正的字段名
 * @param unknown_type $filtername
 * @param unknown_type $outfieldname
 */
function getRealUserFieldName($filtername, $outfieldname = NULL)
{
    switch ($filtername) {
        case "username":
            $where = "screen_name";
            break;
        case "usersfollower":
            $where = "usersfollower";
            break;
        case "usersfriend":
            $where = "usersfriend";
            break;
        case "source":
            $where = "sourceid";
            break;
        case "sex":
            $where = "gender";
            break;
        case "verified":
            $where = "verified";
            break;
        case "area":
            if (!empty($outfieldname)) {
                if (stristr($outfieldname, "country") > -1) {
                    $where = "country_code";
                } else if (stristr($outfieldname, "province") > -1) {
                    $where = "province_code";
                } else if (stristr($outfieldname, "city") > -1) {
                    $where = "city_code";
                } else {
                    $where = "district_code";
                }
            } else {
                $where = $filtername;
            }
            break;
        default:
            $where = $filtername;
            break;
    }
    return $where;
}

/**
 *
 * 返回solr中的字段名
 * @param $filtername
 * @param $outfieldname 输出pin的字段名，输出pin的字段名为真实字段名
 */
function getRealSolrFieldName($filtername, $outfieldname = NULL, $modelid = NUll,$filtervalue_obj=NULL)
{
    $where;
    if ($modelid != NULl && ($modelid == 1 || $modelid == 2)) {
        switch ($filtername) {
            case "username":
            case "users_screen_name":
                $where = "users_screen_name";
                break;
            case "userid":
            case "users_id":
                $where = "users_id";
                break;
            case "followerrank":
            case "users_followers_count":
                $where = "users_followers_count";
                break;
            case "friendrank":
            case "users_friends_count":
                $where = "users_friends_count";
                break;
            case "statusesrank":
            case "users_statuses_count":
                $where = "users_statuses_count";
                break;
            case "registertime":
            case "users_created_at":
                $where = "users_created_at";
                break;
            case "source": //在用户分析模型 filter 中 [source] 指的是 数据来源(新浪,腾讯..), 话题和微博模型中指应用来源(iPhone客户端..)
            case "sourceid":
            case "users_sourceid":
                $where = "users_sourceid";
                break;
            case "sex":
            case "users_gender":
                $where = "users_gender";
                break;
            case "verified":
            case "users_verified":
                $where = "users_verified";
                break;
            case "verified_type":
            case "users_verified_type":
                $where = "users_verified_type";
                break;
            case "verifiedreason":
            case "users_verified_reason":
                $where = "users_verified_reason";
                break;
            case "users_country_code":
                $where = "users_country_code";
                break;
            case "users_province_code":
                $where = "users_province_code";
                break;
            case "users_city_code":
                $where = "users_city_code";
                break;
            case "users_district_code":
                $where = "users_district_code";
                break;
            case "description":
            case "users_description":
                $where = "users_description";
                break;
            default:
                $where = $filtername;
                break;
        }
    } else {
        switch ($filtername) {
            case "searchword":
            case "keyword":
            case "text":
                $where = "text";
                break;
            case "topic":
            case "combinWord":
                $where = "combinWord";
                break;
            case "weibotopickeyword":
            case "wb_topic_keyword":
                $where = "wb_topic_keyword";
                break;
            case "weibotopiccombinword":
            case "wb_topic_combinWord":
                $where = "wb_topic_combinWord";
                break;
            case "weibotopic":
            case "wb_topic":
                $where = "wb_topic";
                break;
            case "weiboid":
                $where = "id";
                break;
            case "weiboguid":
                $where = "guid";
                break;
            case "weibourl":
                $where = "mid";
                break;
            case "oristatusurl":
                $where = "retweeted_mid";
                break;
            case "oristatus":
                $where = "retweeted_status:";  //需要添加数据源
                break;
            case "repostsnum":
                $where = "reposts_count";
                break;
            case "commentsnum":
                $where = "comments_count";
                break;
            case "areauser":
                if (!empty($outfieldname)) {
                    if (stristr($outfieldname, "country") > -1) {
                        $where = "country_code";
                    } else if (stristr($outfieldname, "province") > -1) {
                        $where = "province_code";
                    } else if (stristr($outfieldname, "city") > -1) {
                        $where = "city_code";
                    } else {
                        $where = "district_code";
                    }
                } else {
                    $where = $filtername;
                }
                break;
            case "areamentioned":
                if (!empty($outfieldname)) {
                    if (stristr($outfieldname, "country") > -1) {
                        $where = "country";
                    } else if (stristr($outfieldname, "province") > -1) {
                        $where = "province";
                    } else if (stristr($outfieldname, "city") > -1) {
                        $where = "city";
                    } else {
                        $where = "district";
                    }
                } else {
                    $where = $filtername;
                }
                break;
            case "nearlytime":
            case "beforetime":
            case "createdtime":
            case "untiltime":
                if(isset($filtervalue_obj) && !empty($filtervalue_obj) && isset($filtervalue_obj["realFieldName"]) && !empty($filtervalue_obj["realFieldName"])){
                    $where = $filtervalue_obj["realFieldName"];
                }else{
                    $where = "created_at";
                }
                break;
            case "registertime":
                $where = "register_time";
                break;
            case "weibotype":
                $where = "content_type";
                break;
            case "username":
            case "screen_name":
                $where = "screen_name";
                break;
            case "verified":
                $where = "verify";
                break;
            case "haspicture":
                $where = "has_picture";
                break;
            case "hostdomain":
            case "host_domain":
                $where = "host_domain";
                break;
            case "verifiedreason":
            case "verified_reason":
                $where = "verified_reason";
                break;
            default:
                $where = $filtername;
                break;
        }

    }
    return $where;
}

/**
 *
 * 获取分类查询和分类对比的动态pin数据
 * @param unknown_type $datas
 */
function getDynamicClassicQueryResult($datas, $datatype)
{
    global $logger;
    $result = array();
    $_fvs = array();
    $render = $datas['render']['datajson'];
    $currelementid = $datas['render']['elementid'];
    $isfeature = false;//是否输出特征分类
    if (!empty($render['classifyquery'])) {
        $classifyqueryFields = getFilterValueItem($render["classifyquery"]["fieldname"], $render['filtervalue']);
        $isfeature = !empty($classifyqueryFields[0]['isfeature']);
        for ($i = count($classifyqueryFields[0]['fieldvalue']['value']) - 1; $i > -1; $i--) {
            if ($classifyqueryFields[0]['fieldvalue']['value'][$i]['datatype'] == "dynamic") {
                $_fvs[] = $classifyqueryFields[0]['fieldvalue']['value'][$i];
            }
        }
    } else if (!empty($render['contrast'])) {
        $isfeature = count($render['contrast']['filtervalue']) > 0 && !empty($render['contrast']['filtervalue'][0]['isfeature']);
        foreach ($render['contrast']['filtervalue'] as $k => $v) {
            if ($v['fieldvalue']['datatype'] == 'dynamic') {
                $_fvs[] = $v['fieldvalue'];
            }
        }
    }
    if (!empty($_fvs)) {
        foreach ($_fvs as $_fk => $_fv) {
            foreach ($datas['elements'] as $k => $v) {
                if ($v['elementid'] == $_fv['outelementid']) {
                    $v['datajson']['output']['data_limit'] = $_fv['value']['start'] - 1;
                    $v['datajson']['output']['count'] = $_fv['value']['end'] - $_fv['value']['start'] + 1;
                    $qparam = filter_where($v['datajson']['filterrelation'], $v['datajson']['filtervalue'],
                        "", true, $datas['elements'], $datas['pinrelation'], $v['elementid'], $v['datajson']['modelid']);
                    if (empty($qparam)) {
                        //$qparam = "*:*";
                        $qparam = getDefaultQparam($v['datajson']['modelid']);
                    }
                    $otherparam = buildSolrParamsExQ($v['datajson']);
                    //begin:处理删除快照特征分类2016-12-14
                    /*if(is_array($otherparam) && isset($otherparam['featureclasserror'])){
                        return $otherparam;
                    }*/
                    //end:处理删除快照特征分类2016-12-14
                    //$url = SOLR_URL_SELECT."?q=".$qparam.$otherparam['param'];
                    $url = "q=" . $qparam . $otherparam['param'];
                    $_result = solrRequest($url, $otherparam['field'], $otherparam['facetttype']);
                    if (isset($_result['error'])) {
                        return $_result;
                    }
                    if ($otherparam['facetttype'] == "query") {
                        $datalist = $_result['docs'];
                    } else {
                        $datalist = $_result['countList'];
                    }
                    foreach ($datalist as $dk => $dv) {
                        if ($datatype == "value_text_objcet") {
                            $rv = array();
                            $rv['value'] = $dv[$_fv['outputfield']];
                            if (isset($dv['alias'])) {
                                $rv['text'] = $dv['alias'];
                            } else {
                                $rv['text'] = '';
                            }
                        } else if ($isfeature) {
                            $rv = array();
                            $rv['value'] = $dv[$_fv['outputfield']];
                            $rv['value'] = str_replace("##", ",", $rv["value"]);
                            $rv['value'] = str_replace("#", "", $rv["value"]);
                            $fea = explode(",", $rv['value']);
                            $rv['text'] = count($fea) > 1 ? $fea[1] : $fea[0];
                        } else {
                            $rv = $dv[$_fv['outputfield']];
                        }
                        if (in_array($rv, $result)) {
                            continue;
                        }
                        $result[] = $rv;
                    }
                    break;
                }
            }
        }
    }
    return $result;
}

/**
 *
 * 右侧为用户模型时调用
 * @param $filtervalue_obj 当前处理的filtervalue {fieldname:"", fieldvalue:""}
 * @param $linkageelements
 * @param $pinrelation
 */
function findLeftDynamicPinData($filtervalue_obj, $linkageelements, $pinrelation, $currelementid)
{
    $str = "";
    $pin;
    foreach ($pinrelation as $key => $value) {
        if ($value['outputdata']['pintype'] == "dynamic" && $value['inputdata']['value'] == $filtervalue_obj['fieldname']
            && $value['inelementid'] == $currelementid
            && $value['outelementid'] == $filtervalue_obj['fieldvalue']['outelementid']
        ) {
            //&& $value['outputdata']['value']['start'] == $filtervalue_obj['fieldvalue']['value']['start']
            //&& $value['outputdata']['value']['end'] == $filtervalue_obj['fieldvalue']['value']['end']){
            $pin = $value;
            break;
        }
    }
    if (empty($pin)) {
        return "";
    }
    foreach ($linkageelements as $k => $v) {
        if ($v['elementid'] == $pin['outelementid']) {
            //数据库没有多值字段，所以子查询关系为OR。
            $opt = "or";
            $realfieldname = getRealUserFieldName($filtervalue_obj['fieldname'], $pin['outputdata']['outputfield']);
            //左侧为需要访问solr的模型
            $qparam = filter_where($v['datajson']['filterrelation'], $v['datajson']['filtervalue'],
                "", true, $linkageelements, $pinrelation, $v['elementid'], $v['datajson']['modelid']);
            $otherparam = buildSolrParamsExQ($v['datajson']);
            //begin:处理删除快照特征分类2016-12-14
           /* if(is_array($otherparam) && isset($otherparam['featureclasserror'])){
                return $otherparam;
            }*/
            //end:处理删除快照特征分类2016-12-14
            //$url = SOLR_URL_SELECT."?q=".$qparam.$otherparam['param'];
            $url = "q=" . $qparam . $otherparam['param'];
            $result = solrRequest($url, $otherparam['field'], $otherparam['facetttype']);
            if (isset($result['error'])) {
                continue;
            }
            if ($otherparam['facetttype'] == "query") {
                $datalist = $result['docs'];
            } else {
                $datalist = $result['countList'];
            }
            $csql = array();
            for ($i = 0; $i < count($datalist); $i++) {
                $csql[] = $datalist[$i][$pin['outputdata']['outputfield']];
            }
            $csql = "'" . implode("','", $csql) . "'";
            if ($opt == "or") {
                if ($realfieldname == "usersfollower") {
                    $str .= " id in (select followerID from (select `followerID` from  " . DATABASE_USER_FOLLOWERS . " where userName in ({$csql})) as tb1)";
                } else if ($realfieldname == "usersfriend") {
                    $str .= " id in (select userID from (select `userID` from  " . DATABASE_USER_FOLLOWERS . " where followerName in ({$csql})) as tb2)";
                } else {
                    $str .= " {$realfieldname} in ({$csql})";
                }
            }
            break;
        }
    }
    return $str;
}

/*
 * @brief  重新组合filtervalue和filterrealtion, 旧特征分类设计只支持父级和特征分类两级,现修改为支持多级,
 *         此函数的作用是把父级的分类拆分到特征分类的级别并组合到filtervalue中,同时生成filterrelation
 * @param  Array $fieldArr 模型的json
 * @return 修改filtervalue和filterrelation后的$fieldArr
 * @author Bert
 * @date   2016-6-23
 * @change 2016-6-28 Bert 查询出子类后父类应该删除,这里调用了deleteFilterrealtionItem方法, 删除了字段关系,在filter_where中就不会添加这个字段
 * @change 2016-7-20 Bert 生成逻辑关系修改成新的方式
 * */
function addFeatureToFiltervalue($fieldArr)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . __FUNCTION__);
    $arg_filtervalue_old = $fieldArr["filtervalue"];
    $newFiltervalue = array();
    if (!empty($arg_filtervalue_old)) {
        $old_filtervalue_count = count($arg_filtervalue_old);
        $exRelation = array();
        foreach ($arg_filtervalue_old as $ai => $aitem) {
            if ($aitem['isfeature'] == 1) {
                $exclude = $aitem['exclude'];
                $featureid = getFieldValue($aitem['fieldvalue']['datatype'], $aitem['fieldvalue']['value']);
                $resultArr = array();
                $field = getRealSolrFieldName($aitem['fieldname']);
                getAllFeatureByID($featureid, $field, $resultArr);
                $logger->debug(__FILE__ . __LINE__ . " resultArr " . var_export($resultArr, true));
                $exRe = array();
                if (!empty($resultArr)) {
                    foreach ($resultArr as $ri => $ritem) {
                        if ($ritem['feature_field'] == 'text') {
                            $field = 'keyword';
                        }
                        //solr返回的结构中带有#,组查询条件时应该去掉
                        $value = array('value' => str_replace("#", "", $ritem['feature_class']), 'text' => str_replace("#", "", $ritem['feature_class']), 'guid' => $ritem['guid']);
                        $has = false;
                        foreach ($newFiltervalue as $ni => $nitem) {
                            if ($nitem["fieldvalue"]["value"]["value"] == str_replace("#", "", $ritem['feature_class'])) {
                                $has = true;
                            }
                        }
                        if (!$has) {
                            $logger->debug(__FILE__ . __LINE__ . " resultArr " . var_export($ritem, true));
                            $newFiltervaluetemp = createFiltervalue($field, 'value_text_object', $value, NULL, 1, $exclude);
                            $newFiltervaluetemp["feature_father_guid"] = $ritem["feature_father_guid"];
                            $newFiltervalue[] = $newFiltervaluetemp;
                            $exRe[] = $old_filtervalue_count;
                            $old_filtervalue_count++;
                        }
                    }
                }
                /*      else{
                    $has = false;
                    foreach($newFiltervalue as $ni=>$nitem){
                        if($nitem["fieldvalue"]["value"]["value"] == $aitem["fieldvalue"]["value"]["value"]){
                            $has = true;
                        }
                    }
                    if(!$has){
                        $newFiltervalue[] = $aitem;
                        $exRe[] = $old_filtervalue_count;
                        $old_filtervalue_count++;
                    }
                }*/
                $exRelation[$ai] = $exRe;
            }
        }
    }
    if (!empty($exRelation)) {
        $logger->debug(__FILE__ . __LINE__ . " exRelation " . var_export($exRelation, true));
        $arg_filtervalue = array_merge($fieldArr["filtervalue"], $newFiltervalue);
        $arg_filterrelation = $fieldArr["filterrelation"];
        exFilterrelation($arg_filterrelation, $exRelation);
        $fieldArr["filterrelation"] = $arg_filterrelation;
        $fieldArr["filtervalue"] = $arg_filtervalue;
        $logger->debug(__FILE__ . __LINE__ . " fieldArr[filtervalue] " . var_export($fieldArr["filtervalue"], true) . " fieldArr[filterrelation] " . var_export($fieldArr["filterrelation"], true));
    }
    return $fieldArr;
}

/*
 * @brief  判断filtervalue中是否有article_taginfo字段, 有此字段时,替换为对应用户的字段
 * @param  Array $fieldArr 模型的json
 * @return 修改filtervalue后的$fieldArr
 * @author Bert
 * @date   2016-7-2
 * @change 2016-7-2
 * */
function changeArticleTagInfoField($fieldArr)
{
    global $logger,$task;
    $arg_filtervalue = $fieldArr["filtervalue"];
    if (!empty($arg_filtervalue)) {
        foreach ($arg_filtervalue as $ai => $aitem) {
            if ($aitem["fieldname"] == "article_taginfo") {
                //解决后台查询solr条件中带标签,无法查询的问题   by  yu   2017/2/28   start
                if(!empty($_SESSION['user'])){
                    $userid = $_SESSION['user']->getuserid();
                }else{
                    $userid = $task->userid;
                }
                // end
                $real_field = getUserArticleTaginfoField($userid);
                $fieldArr['filtervalue'][$ai]['fieldname'] = $real_field;
            }
        }
    }
    return $fieldArr;
}

/*
    重新组合filtervalue和filterrealtion, 把权限中设置的值添加到filtervalue中,注意分类查询的情况, 在allowcontrol == 0 时,filteralue没有对应字段, 需要从limit中读出对应字段的值,组合成filtervalue, filterrealtion分两部分, 用户填写的都在filteralue中作为一部分, AND 上权限有值的filteralue
    循环fitter limit组成filtervalue数组, 分类查询和非分类查询的,
    getFiltervalueFromlimt
    createFiltervalue
    生成realtion
 */
function addLimitValueToFiltervalue($fieldArr)
{
    global $logger;
    $arg_filtervalue_old = $fieldArr["filtervalue"];
    $limitFiltervalue = array();
    foreach ($fieldArr["filter"] as $lkey => $litem) {
        $isclassify = false;
        if (!empty($fieldArr["classifyquery"]["fieldname"])) {
            if ($fieldArr["classifyquery"]["fieldname"] == $lkey) {
                $isclassify = true;
            }
        }
        $hasfield = false;
        if (!empty($arg_filtervalue_old)) {
            foreach ($arg_filtervalue_old as $ai => $aitem) {
                if ($lkey == "nearlytime" || $lkey == "beforetime" || $lkey == "untiltime" || $lkey == "createdtime") {
                    if (isset($aitem["fieldname"]) && ($aitem["fieldname"] == "nearlytime" || $aitem["fieldname"] == "beforetime" || $aitem["fieldname"] == "untiltime" || $aitem["fieldname"] == "createdtime")) {
                        $hasfield = true;
                        break;
                    }
                } else if ($lkey == "verified_type" || $lkey == "verified") {
                    if (isset($aitem["fieldname"]) && ($aitem["fieldname"] == "verified_type" || $aitem["fieldname"] == "verified")) {
                        $hasfield = true;
                        break;
                    }
                } else if (isset($aitem["fieldname"]) && $aitem["fieldname"] == $lkey) {
                    $hasfield = true;
                    break;
                }
            }
        }
        //当filtervalue中存在时,不从limit中取值
        if ($hasfield) {
            continue;
        }
        $limitfieldvalue = array();
        switch ($litem["datatype"]) {
            case "range":
                if ($isclassify && $litem["allowcontrol"] == 0) { //allowcontrol==1时不会是分类查询
                    foreach ($litem["limit"] as $vi => $vitem) {
                        if ($vitem["value"]["minvalue"] != null || $vitem["value"]["maxvalue"] != null) {
                            $tmpval["datatype"] = $litem["datatype"];
                            $tmpval["value"] = array("start" => $vitem["value"]["minvalue"], "end" => $vitem["value"]["maxvalue"]);
                            $limitfieldvalue[] = $tmpval;
                        }
                    }
                } else {
                    if (isset($litem["limit"]) && count($litem["limit"]) > 0) {
                        foreach ($litem["limit"] as $vi => $vitem) {
                            if ($vitem["value"]["minvalue"] != null || $vitem["value"]["maxvalue"] != null) {
                                $limitfieldvalue[] = array("start" => $vitem["value"]["minvalue"], "end" => $vitem["value"]["maxvalue"]);
                            }
                        }
                    }
                }
                break;
            case "gaprange":
                if ($isclassify && $litem["allowcontrol"] == 0) {
                    foreach ($litem["limit"] as $vi => $vitem) {
                        if ($vitem["value"]["minvalue"] != null || $vitem["value"]["maxvalue"] != null) {
                            $tmpval["datatype"] = $litem["datatype"];
                            $tmpval["value"] = array("start" => $vitem["value"]["minvalue"], "end" => $vitem["value"]["maxvalue"], "gap" => $vitem["value"]["gap"]);
                            $limitfieldvalue[] = $tmpval;
                        }
                    }
                } else {
                    if (isset($litem["limit"]) && count($litem["limit"]) > 0) {
                        foreach ($litem["limit"] as $vi => $vitem) {
                            if ($vitem["value"]["minvalue"] != null || $vitem["value"]["maxvalue"] != null) {
                                $limitfieldvalue[] = array("start" => $vitem["value"]["minvalue"], "end" => $vitem["value"]["maxvalue"], "gap" => $vitem["value"]["gap"]);
                            }
                        }
                    }
                }
                break;
            case "time_dynamic_range":
            case "time_dynamic_state":
                if ($isclassify && $litem["allowcontrol"] == 0) {
                    if (count($litem["limit"]) > 0) {
                        foreach ($litem["limit"] as $vi => $vitem) {
                            if (!array_key_exists("maxvalue", $vitem["value"]) && !array_key_exists("minvalue", $vitem["value"])) {
                                $tmpval["datatype"] = $litem["datatype"];
                                $tmpval["value"] = $vitem["value"];
                                $limitfieldvalue[] = $tmpval;
                            }
                        }
                    }
                } else {
                    if (isset($litem["limit"]) && count($litem["limit"]) > 0) {
                        foreach ($litem["limit"] as $vi => $vitem) {
                            if (!array_key_exists("maxvalue", $vitem["value"]) && !array_key_exists("minvalue", $vitem["value"])) {
                                $limitfieldvalue[] = $vitem["value"];
                            }
                        }
                    }
                }
                break;
            case "value_text_object":
            case "blur_value_object":
            case "string":
            case "int":
                if ($isclassify && $litem["allowcontrol"] == 0) {
                    foreach ($litem["limit"] as $vi => $vitem) {
                        $tmpval["datatype"] = $litem["datatype"];
                        $tmpval["value"] = $vitem["value"];
                        $limitfieldvalue[] = $tmpval;
                    }
                } else {
                    if (isset($litem["limit"])) {
                        foreach ($litem["limit"] as $vi => $vitem) {
                            $limitfieldvalue[] = $vitem["value"];
                        }
                    }
                }
                break;
            default:
                break;
        }
        if (count($limitfieldvalue) > 0) {
            if ($isclassify && $litem["allowcontrol"] == 0) {
                $datatype = "array";
                $limitFiltervalue[] = createFiltervalue($lkey, $datatype, $limitfieldvalue, 1);
            } else {
                foreach ($limitfieldvalue as $fi => $fitem) {
                    $datatype = $litem["datatype"];
                    $limitFiltervalue[] = createFiltervalue($lkey, $datatype, $fitem, 1);
                }
            }
        }
    }
    $arg_filterrelation = $fieldArr["filterrelation"];
    if ($arg_filterrelation == NULL) {
        $arg_filterrelation = initFilterRelation($fieldArr);
    }
    $arg_filtervalue = array_merge($fieldArr["filtervalue"], $limitFiltervalue);
    //$arg_filtervalue_copy = $arg_filtervalue;
    //从新生成filterrelation
    $lf["filtervalue"] = $limitFiltervalue;
    $limitrelation = initFilterRelation($lf, count($arg_filtervalue_old));

    if ($arg_filterrelation["opt"] == "or") {
        $arg_newfilterrelation = array();
        $arg_newfilterrelation["opt"] = "and";
        $arg_newfilterrelation["filterlist"] = array();
        $arg_newfilterrelation["filterlist"][] = $arg_filterrelation;
        $fr["opt"] = "and";
        $fr["filterlist"] = array();
        $fr["filterlist"][] = $limitrelation;
        $fr["fields"] = array();
        $arg_newfilterrelation["filterlist"][] = $fr;
        $arg_newfilterrelation["fields"] = array();
        $arg_filterrelation = $arg_newfilterrelation;
    } else {
        $arg_filterrelation["filterlist"][] = $limitrelation;
    }

    $fieldArr["filterrelation"] = $arg_filterrelation;
    $fieldArr["filtervalue"] = $arg_filtervalue;
    return $fieldArr;
}

function addTextValueToFiltervalue($fieldArr)
{
    global $logger;
    $arg_filtervalue_old = $fieldArr["filtervalue"];
    $hastext = false;
    $textfiltervalue = array();
    foreach ($arg_filtervalue_old as $ai => $aitem) {
        //当filtervalue中有text时不会查询出段落, 当有pg_text时,用户想在段落搜索,此时通过pg_text返回记录的guid查出整篇文章 ,不需要修改查询条件
        $hasarr = array("keyword", "searchword", "text", "pg_text");
        if (in_array($aitem['fieldname'], $hasarr)) {
            $hastext = true;
            break;
        }
    }
    $arg_filterrelation = $fieldArr["filterrelation"];
    if ($arg_filterrelation == NULL) {
        $arg_filterrelation = initFilterRelation($fieldArr);
    }
    if (!$hastext) {
        $textfiltervalue[] = createFiltervalue("text", "string", "*");
        $arg_filtervalue = array_merge($fieldArr["filtervalue"], $textfiltervalue);
        $tf["filtervalue"] = $textfiltervalue;
        $textrelation = initFilterRelation($tf, count($arg_filtervalue_old));
        if ($arg_filterrelation["opt"] == "or") {
            $arg_newfilterrelation = array();
            $arg_newfilterrelation["opt"] = "and";
            $arg_newfilterrelation["filterlist"] = array();
            $arg_newfilterrelation["filterlist"][] = $arg_filterrelation;
            $fr["opt"] = "and";
            $fr["filterlist"] = array();
            $fr["filterlist"][] = $textrelation;
            $fr["fields"] = array();
            $arg_newfilterrelation["filterlist"][] = $fr;
            $arg_newfilterrelation["fields"] = array();
            $arg_filterrelation = $arg_newfilterrelation;
        } else {
            $arg_filterrelation["filterlist"][] = $textrelation;
        }
        $fieldArr["filterrelation"] = $arg_filterrelation;
        $fieldArr["filtervalue"] = $arg_filtervalue;
    }
    return $fieldArr;
}

/**
 *
 * 右侧为话题模型或微博模型时调用，生成子查询的url
 * @param unknown_type $filtervalue_obj 当前处理的filtervalue {fieldname:"", fieldvalue:""}
 * @param unknown_type $linkageelements
 * @param unknown_type $pinrelation
 */
function findLeftDynamicPinURL($filtervalue_obj, $linkageelements, &$pinrelation, $currelementid, $modelid)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    $str = "";
    $pin;
    foreach ($pinrelation as $key => $value) {
        if ($value['outputdata']['pintype'] == "dynamic" && $value['inputdata']['value'] == $filtervalue_obj['fieldname']
            && $value['inelementid'] == $currelementid
            && $value['outelementid'] == $filtervalue_obj['fieldvalue']['outelementid']
            && $value['outputdata']['value']['start'] == $filtervalue_obj['fieldvalue']['value']['start']
            && $value['outputdata']['value']['end'] == $filtervalue_obj['fieldvalue']['value']['end']
        ) {
            $pin = $value;
            //array_splice($pinrelation, $key, 1);//删除已处理的pin，防止取到重复的
            break;
        }
    }
    if (empty($pin)) {
        $logger->debug("not found pin：currelementid:{$currelementid}, outeleid:{$filtervalue_obj['fieldvalue']['outelementid']}");
        return "";
    }
    $eqen = urlencode("=");
    foreach ($linkageelements as $k => $v) {
        if ($v['elementid'] == $pin['outelementid']) {
            //找到pinrelation
            if (empty($pin) || $pin['outputdata']['pintype'] != "dynamic") {
                $logger->error(__FUNCTION__ . " not found pin");
                return false;
            }
            $opt = isset($pin['inputdata']['opt']) ? $pin['inputdata']['opt'] : "or";
            if (!empty($v['datajson']['facet']['field'][0]['isfeature'])) {
                $realsolrfieldname = "feature_class";
            } else {
                //输出guid, 且输入为微博地址时，动态连接字段改为guid
                if ($filtervalue_obj['fieldname'] == "weibourl" && ($pin['outputdata']['outputfield'] == "guid" || $pin['outputdata']['outputfield'] == "docguid" || $pin['outputdata']['outputfield'] == "retweeted_guid")) {
                    $realsolrfieldname = "guid";
                } else if ($filtervalue_obj['fieldname'] == "oristatusurl" && ($pin['outputdata']['outputfield'] == "guid" || $pin['outputdata']['outputfield'] == "retweeted_guid")) {
                    $realsolrfieldname = "retweeted_guid";
                } else {
                    $logger->debug(__FUNCTION__ . " modelid " . $modelid);
                    if ($v['datajson']['output']['outputtype'] == 2 && count($v['datajson']['facet']['field']) > 0) {
                        $realsolrfieldname = getRealSolrFieldName($filtervalue_obj['fieldname'], $v['datajson']['facet']['field'][0]['name'], $modelid);
                    } else {
                        $realsolrfieldname = getRealSolrFieldName($filtervalue_obj['fieldname'], $pin['outputdata']['outputfield'], $modelid);
                    }
                }
            }
            //左侧不是用户模型，可以使用solr的join语法
            //if($v['datajson']['modelid'] != 1){
            if ($realsolrfieldname == "usersfollower") {
                $str = "{!join+from{$eqen}users_id+to{$eqen}users_friends_id}{!join+from{$eqen}{$pin['outputdata']['outputfield']}+to{$eqen}users_screen_name+op{$eqen}{$opt}";
            } else if ($realsolrfieldname == "usersfriend") {
                $str = "{!join+from{$eqen}users_friends_id+to{$eqen}users_id}{!join+from{$eqen}{$pin['outputdata']['outputfield']}+to{$eqen}users_screen_name+op{$eqen}{$opt}";
            } else {
                $str = "{!join+from{$eqen}{$pin['outputdata']['outputfield']}+to{$eqen}{$realsolrfieldname}+op{$eqen}{$opt}";
            }
            if (empty($pin['outputdata']['value']['start']) && empty($pin['outputdata']['value']['end'])) {
                $offset = 0;
                $limit = -1;
            } else {
                $offset = $pin['outputdata']['value']['start'] - 1;
                $limit = $pin['outputdata']['value']['end'] - $pin['outputdata']['value']['start'] + 1;
            }
            if ($v['datajson']['output']['outputtype'] == 2 && count($v['datajson']['facet']['field']) > 0) {//facet
                $str .= getJoinFacetURL($v['datajson'], $offset, $limit);
            } else {
                if (isset($offset) && isset($limit) && $limit != -1) {
                    $str .= '+sort' . $eqen . '"' . $v['datajson']['output']['orderby'] . '+' . $v['datajson']['output']['ordertype'] . '"';
                    $str .= "+start{$eqen}{$offset}+rows{$eqen}{$limit}";
                }
            }
            $str .= "}";
            $v['datajson'] = addLimitValueToFiltervalue($v['datajson']);
            $cstr = filter_where($v['datajson']['filterrelation'], $v['datajson']['filtervalue'],
                "", true, $linkageelements, $pinrelation, $v['elementid'], $v['datajson']['modelid']);
            $str .= empty($cstr) ? getDefaultQparam($v['datajson']['modelid']) : $cstr;
            break;
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    return $str;
}

//删除对应分类查询的索引, 其他索引保持不变, filter_where根据filterrelation取值, 删除的值不能取到
function deleteFilterrealtionItem(&$re, $delkey)
{
    global $logger;
    if (!empty($re["fields"])) {
        foreach ($re["fields"] as $key => $value) { //删除filtervalue中对应索引, 比删除索引大的索引减1
            if ($value == $delkey) {
                array_splice($re["fields"], $key, 1);
            }
        }
    }
    if (!empty($re["filterlist"])) {
        foreach ($re["filterlist"] as $key => $value) {
            deleteFilterrealtionItem($re["filterlist"][$key], $delkey);
        }
    }
    //$logger->debug(__FILE__.__LINE__." re ".var_export($re, true));
    return $re;
}

/*
 * @brief  清除filterrelation中的空关系
 * @param  Array $re 模型的filterrelation
 * @return 修改后端filterrelation
 * @author Bert
 * @date   2016-7-3
 * @change 2016-7-3
 * */
function clearFilterrelation(&$re)
{
    if (!empty($re["filterlist"])) {
        foreach ($re["filterlist"] as $key => $value) {
            if (empty($value["filterlist"]) && empty($value['fields'])) {
                array_splice($re["filterlist"], $key, 1);
            } else {
                clearFilterrelation($re["filterlist"][$key]);
            }
        }
    }
}

/*
 * @brief  展开特征分类的子类添加到filterrelation中
 * @param  array() $re 引用, 旧的逻辑关系
 * @param  array() $exRelation, 对象数组 key为旧的filtervalue中需要展开的索引, value为展开的数组
 * @return 带有新增索引的逻辑关系
 * @author Bert
 * @date   2016-7-20
 * @change 2016-7-20
 * */
function exFilterrelation(&$re, $exRelation)
{
    global $logger;
    if (!empty($re["filterlist"])) {
        foreach ($re["filterlist"] as $key => $value) {
            if (!isset($value['flag'])) {
                exFilterrelation($re["filterlist"][$key], $exRelation);
            }
        }
    }
    if (!empty($re["fields"])) {
        foreach ($re["fields"] as $key => $value) {
            if (isset($exRelation[$value])) {
                unset($re["fields"][$key]);
                $re["filterlist"][] = array('opt' => 'or', 'filterlist' => array(), 'fields' => $exRelation[$value], 'flag' => 1);
            }
        }
    }
    $logger->debug(__FILE__ . __LINE__ . " ex_relation " . var_export($re, true));
}

/**
 *
 * 生成查询url
 * @param $re 字段关系
 * @param $filtervalue 字段数组
 * @param $classifyqueryfield 对比字段名
 */
function filter_where($re, $filtervalue, $classifyqueryfield = "", $islinkage = false, $linkageelements, &$pinrelation, $currelementid, $modelid)
{
    global $logger;
    $eqen = urlencode("=");
    $logger->debug(__FILE__ . " " . __FUNCTION__ . " enter params:");
    $str = "";
    if ($islinkage) {
        $str = "{!bool";
    }
    $tmpArr = array();
    if (!empty($re["fields"])) {
        $fieldlen = count($re["fields"]) - 1;
        $exclude = false;
        foreach ($re["fields"] as $key => $value) {
            if (empty($filtervalue[$value]["exclude"])) { //当filteralue中全为不包含时,url 需要添加 *:*
                $exclude = true;
            }
            if (isset($filtervalue[$value]) && isset($filtervalue[$value]['fieldvalue']) && $filtervalue[$value]['fieldvalue']['datatype'] == "array") { //特征分类和普通字段 字段名相同, 需要增加类型判断是否为分类查询
                $isclassifyquery = $classifyqueryfield == $filtervalue[$value]['fieldname'];
            } else {
                $isclassifyquery = false;
            }

            //当json版本改变时,filter字段减少时,filterrelation没有变化,
            //filtervalue中对应的fieldvalue 置为null(modeflinterface.php getelements)
            if (isset($filtervalue[$value]) && isset($filtervalue[$value]['fieldvalue']) && $filtervalue[$value]['fieldvalue'] !== null) {
                $rest = filterrelation2url($filtervalue[$value], $filtervalue, $isclassifyquery, $linkageelements, $pinrelation, $currelementid, $modelid);
                if (!empty($rest)) {
                    if(  is_array($rest) && isset($rest['featureclasserror'])){
                        return $rest;
                    }else{
                    $tmpArr[] = $rest;
                }

                }
            }
        }
        //当filtervalue中只有一个值且为不包含时 需要url中拼上 *:*
        if (count($tmpArr) == 1 && !empty($filtervalue[$value]["exclude"])) {
            $tmpArr[] = getDefaultQparam($modelid);
        } else {
            if (!$exclude) {
                $tmpArr[] = getDefaultQparam($modelid);
            }
        }
        if (!empty($tmpArr) && count($tmpArr) > 0) {
            if ($islinkage) {
                for ($i = 0; $i < count($tmpArr); $i++) {
                    $str .= "+sub." . ($i + 1) . "{$eqen}<{$tmpArr[$i]}>";
                }
            } else {
                $opt = "+" . strtoupper($re["opt"]) . "+";
                $str .= implode($opt, $tmpArr);
            }
        }
    }
    if (!empty($re["filterlist"])) {
        foreach ($re["filterlist"] as $key => $value) {
            $res = filter_where($value, $filtervalue, $classifyqueryfield, $islinkage, $linkageelements, $pinrelation, $currelementid, $modelid);
            if (!empty($res)) {
                if ($islinkage) {
                    $subc = count($tmpArr) + 1 + $key;
                    $str .= "+sub.{$subc}{$eqen}<{$res}>";
                } else {
                    if ($str != "") {
                        $opt = "+" . strtoupper($re["opt"]) . "+";
                        $str .= $opt . "(" . $res;
                        $str .= ")";
                    } else {
                        $str .= "(" . $res . ")";
                    }
                }
            }
        }
    }
    if ($islinkage) {
        if ($str != "{!bool") {
            $str .= "+op{$eqen}{$re["opt"]}";
            $str .= "}";
        } else {
            $str = "";
        }
    }
    $logger->debug(__FILE__ . " " . __FUNCTION__ . " exit");
    return $str;
}

function queryAddQuotation($var, $need = false)
{
    if ($need) {
        return "\"" . $var . "\"";
    } else {
        return $var;
    }
}

function filterrelation2url($filtervalue_obj, $filtervalue, $isclassifyquery = false, $linkageelements, &$pinrelation, $currelementid, $modelid)
{
    global $logger;
    $logger->debug(__FILE__ . " " . __FUNCTION__ . " enter");
    $filterfieldname = $filtervalue_obj["fieldname"];
    $filtervalueobjarr = getFilterValue($filtervalue_obj["fieldvalue"]);//获取value
    //当使用特征分类时，生成join查询
    $rfname = getRealSolrFieldName($filtervalue_obj['fieldname'], NULL, $modelid,$filtervalue_obj);
    $eqen = urlencode("=");
    if (!empty($filtervalue_obj['isfeature'])) {
        if ($filtervalue_obj['fieldvalue']['datatype'] == "dynamic") {
            $appendquery = findLeftDynamicPinURL($filtervalue_obj, $linkageelements, $pinrelation, $currelementid, $modelid);
        } else {
            // if($isclassifyquery){
            //     $tmpfiltervalueobj = $filtervalueobjarr[0]['value'];
            // }
            // else{
            //     $tmpfiltervalueobj = $filtervalueobjarr['value'];
            // }
            // $tmpfiltervalueobj = queryAddQuotation(solrEsc($tmpfiltervalueobj));
            // $tmprfname;
            // if($rfname == "usersfollower"){
            //     $tmprfname = "users_friends_id";
            // }
            // else{
            //     $tmprfname = $rfname;
            // }
            // $appendquery = "feature_class:".$tmpfiltervalueobj."+AND+feature_field:{$tmprfname}";
            if ($isclassifyquery) {
                $tmpfiltervalueobj = $filtervalueobjarr[0]['guid'];
                //$tmpfiltervalueobj = $filtervalueobjarr[0]['value'];
                $tmpfiltervalueobjclass = $filtervalueobjarr[0]['value'];//zuoqian:2016-12-14

            } else {
                $tmpfiltervalueobj = $filtervalueobjarr['guid'];
                $tmpfiltervalueobjclass = $filtervalueobjarr['value'];//zuoqian:2016-12-14
            }
            //$tmpfiltervalueobj = queryAddQuotation(solrEsc($tmpfiltervalueobj));
            $tmprfname;
            if ($rfname == "usersfollower") {
                $tmprfname = "users_friends_id";
            } else {
                $tmprfname = $rfname;
            }
            //当查询到最后一级时, 用户选择的特征分类包含多个关键词时会是多条记录,需要全部查询出来
            $resultArr = array();
            getAllFeatureByID($tmpfiltervalueobj, $tmprfname, $resultArr, true, $tmpfiltervalueobjclass);
            if (!empty($resultArr)) {
                if(!isset($resultArr["featureclasserror"])){
                $guids = array();
                foreach ($resultArr as $ri => $ritem) {
                    $guids[] = "guid:" . $ritem['guid'] . "";
                }
                $appendquery = "(" . implode("+OR+", $guids) . ")";
                }else{
                    return $resultArr;//add by zuo:2016-12-14
                }

            } else {
                $appendquery = "guid:" . $tmpfiltervalueobj . "+AND+feature_field:{$tmprfname}";
            }
        }
        $exfeature = "";//是否选择的特征分类的"其他"
        if ($isclassifyquery) {
            if (!empty($filtervalue_obj["fieldvalue"]['value'][0]["exclude"])) { //分类查询时提交参数的不包含属性和value中datatype同级, 提前到和fieldname统计使和普通查询统一
                $exfeature = "!";
            }
        } else {
            if (!empty($filtervalue_obj['exclude'])) {
                $exfeature = "!";
            }
        }
        if ($rfname == "usersfollower") {
            $str = $exfeature . "{!join+from{$eqen}feature_keyword+to{$eqen}users_friends_id}" . $appendquery;
        } else if ($rfname == "usersfriend") {
            $str = $exfeature . "{!join+from{$eqen}users_friends_id+to{$eqen}users_id}{!join+from{$eqen}feature_keyword+to{$eqen}users_id}" . $appendquery;
        } else {
            //2016-8-18 Bert 支持多级特征分类
            $str = $exfeature . "{!join+from{$eqen}feature_keyword+to{$eqen}{$rfname}+self_join{$eqen}true+sj_trans_fromField{$eqen}guid+sj_trans_toField{$eqen}feature_father_guid}" . $appendquery;
        }
        return $str;
    }
    //处理不包含时 在字段前添加 !
    $excludestr = "";
    if ($isclassifyquery) { //分类查询时提交数据 exclude属性 存在fieldvalue中
        if (!empty($filtervalue_obj["fieldvalue"]['value'][0]["exclude"])) {
            $excludestr = "!";
        }
    } else {
        if (!empty($filtervalue_obj['exclude'])) {
            $excludestr = "!";
        }
    }

    if ($filtervalue_obj['fieldvalue']['datatype'] == "dynamic") {
        return $excludestr . findLeftDynamicPinURL($filtervalue_obj, $linkageelements, $pinrelation, $currelementid, $modelid);
    }
    $filtervaluelist = array();//存储value数组
    if (is_array($filtervalueobjarr) && is_assoc($filtervalueobjarr)) {//是单个对象时，非对比，非inputpin
        if ($filtervalue_obj["fieldvalue"]['datatype'] == "value_text_object" ||
            $filtervalue_obj["fieldvalue"]['datatype'] == "blur_value_object"
        ) {
            $filtervaluelist[] = $filtervalueobjarr['value'];
        } else {
            $filtervaluelist[] = $filtervalueobjarr;
        }
    } else if (is_array($filtervalueobjarr)) {
        if ($isclassifyquery) {//分类查询时，每次只取数组第一个元素
            if ($filtervalue_obj["fieldvalue"]['value'][0]['datatype'] == "value_text_object" ||
                $filtervalue_obj["fieldvalue"]['value'][0]['datatype'] == "blur_value_object"
            ) {
                $filtervaluelist[] = $filtervalueobjarr[0]['value'];
            } else {
                $filtervaluelist[] = $filtervalueobjarr[0];
            }
        } else {//是数组，但非对比，是inputpin
            $filtervaluelist = $filtervalueobjarr;
        }
    } else {
        $filtervaluelist[] = $filtervalueobjarr;
    }
    $resultsql = array();
    for ($i = 0; $i < count($filtervaluelist); $i++) {
        $filtervalueobj = $filtervaluelist[$i];
        $where = "";
        if ($modelid == 2 || $modelid == 1) {
            switch ($rfname) {
                case "users_id":
                case "users_gender":
                case "users_allow_all_act_msg":
                case "users_allow_all_comment":
                    $where = $rfname . ":" . $filtervalueobj;
                    break;
                case "usersfriend": //查关注
                    $where = "{!join+from{$eqen}users_friends_id+to{$eqen}users_id}users_screen_name:" . queryAddQuotation(solrEsc($filtervalueobj));
                    break;
                case "usersfollower": //查粉丝
                    $where = "{!join+from{$eqen}users_id+to{$eqen}users_friends_id}users_screen_name:" . queryAddQuotation(solrEsc($filtervalueobj));
                    break;
                case "users_verified":
                case "users_verified_type":
                    $filtervalueobj = solrEsc($filtervalueobj); //转义负数
                    $where = $rfname . ":" . $filtervalueobj . "";
                    break;
                case "users_source_host":
                    $sh = get_source_id($filtervalueobj);
                    if (!empty($sh)) {
                        //$where = "users_sourceid:".$sh;
                        $sourceArr = array();
                        foreach ($sourceurls as $si => $sitem) {
                            $sourceArr[] = "users_source_host:" . $sitem;
                        }
                        $where = "(" . implode("+OR+", $sourceArr) . ")";
                    } else {
                        $filtervalueobj = solrEsc($filtervalueobj); //转义负数
                        $where = "users_source_host:" . $filtervalueobj;
                    }
                    break;
                case "users_followers_count":
                case "users_friends_count":
                case "users_statuses_count":
                case "users_favourites_count":
                case "users_bi_followers_count":
                case "users_replys_count":
                case "users_recommended_count":
                case "users_level":
                    $tmpwhere = getRangeWhere($rfname, $filtervalueobj);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "users_created_at":
                    //博龄把以天为单位的转为时间戳
                    //$arg_query_timegapstart = "day";
                    //$arg_query_timegapend = "day";
                    if (isset($filtervalueobj["gap"])) {  //q 查询时 start end为相对时间
                        $arg_query_registertime_gap = $filtervalueobj["gap"];
                        if ($filtervalueobj["start"] !== null) {
                            $arg_query_registertime_start = strtotime("-" . $filtervalueobj["start"] . " " . $arg_query_registertime_gap . "");
                        } else {
                            $arg_query_registertime_start = $filtervalueobj["start"];
                        }
                        if ($filtervalueobj["end"] !== null) {
                            $arg_query_registertime_end = strtotime("-" . $filtervalueobj["end"] . " " . $arg_query_registertime_gap . "");
                        } else {
                            $arg_query_registertime_end = $filtervalueobj["end"];
                        }
                    } else { //drilldown时 start, end为时间戳
                        $arg_query_registertime_start = $filtervalueobj["start"];
                        $arg_query_registertime_end = $filtervalueobj["end"];
                    }
                    //博龄为相对现在的时间
                    if ($arg_query_registertime_start > $arg_query_registertime_end) {
                        $regtime["start"] = $arg_query_registertime_end;
                        $regtime["end"] = $arg_query_registertime_start;
                    } else {
                        $regtime["start"] = $arg_query_registertime_start;
                        $regtime["end"] = $arg_query_registertime_end;
                    }
                    if (isset($filtervalueobj["include"])) {
                        $regtime["include"] = $filtervalueobj["include"];
                    }
                    $tmpwhere = getRangeWhere("users_created_at", $regtime);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "areauser":
                case "area":
                    $areatype = codelevel($filtervalueobj, false);
                    $areatype .= "_code";
                    $where = "users_" . $areatype . ":" . $filtervalueobj;
                    break;
                default:
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = $rfname . ":" . $filtervalueobj . "";
                    break;
            }
        } else {
            switch ($filterfieldname) {
                case "ancestor_text":
                case "ancestor_organization":
                case "ancestor_NRN":
                case "ancestor_combinWord":
                case "ancestor_wb_topic_keyword":
                case "ancestor_wb_topic_combinWord":
                case "ancestor_wb_topic":
                case "ancestor_host_domain":
                case "ancestor_similar":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "" . $filterfieldname . ":" . $filtervalueobj . "";
                    break;
                case "searchword":
                case "keyword":
                case "text":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "text:" . $filtervalueobj . "";
                    break;
                case "pg_text":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "pg_text:" . $filtervalueobj . "";
                    break;
                case "organization":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "organization:" . $filtervalueobj . "";
                    break;
                case "NRN":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "NRN:" . $filtervalueobj . "";
                    break;
                case "description":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "description:" . $filtervalueobj . "";
                    break;
                case "topic":
                case "combinWord":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "combinWord:" . $filtervalueobj;
                    break;
                case "weibotopickeyword":
                case "wb_topic_keyword":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "wb_topic_keyword:" . $filtervalueobj;
                    break;
                case "weibotopiccombinword":
                case "wb_topic_combinWord":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "wb_topic_combinWord:" . $filtervalueobj;
                    break;
                case "weibotopic":
                case "wb_topic":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj), true);
                    $where = "wb_topic:" . $filtervalueobj;
                    break;
                case "weiboid":
                    $where = "id:" . $filtervalueobj;
                    break;
                case "weiboguid":
                    $where = "guid:" . $filtervalueobj;
                    break;
                case "original_url":
                    $sourceid = get_sourceid_from_url($filtervalueobj);
                    $mid = weiboUrl2mid($filtervalueobj, $sourceid);
                    if ($mid != "" && !empty($sourceid)) {
                        $where = "(sourceid:{$sourceid}+AND+mid:" . $mid . ")";
                    } else {
                        $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                        if (!empty($sourceid)) {
                            $where = "(sourceid:{$sourceid}+AND+original_url:" . $filtervalueobj . ")";
                        } else {
                            $where = "original_url:" . $filtervalueobj . "";
                        }
                    }
                    break;
                case "weibourl":
                    $sourceid = get_sourceid_from_url($filtervalueobj);
                    $mid = weiboUrl2mid($filtervalueobj, $sourceid);

                    if ($mid != "" && !empty($sourceid)) {
                        $where = "(sourceid:{$sourceid}+AND+mid:" . $mid . ")";
                    }
                    break;
                case "oristatusurl":
                    $sourceid = get_sourceid_from_url($filtervalueobj);
                    $mid = weiboUrl2mid($filtervalueobj, $sourceid);
                    if ($mid != "" && !empty($sourceid)) {
                        if ($sourceid == 1) {
                            $where = "(sourceid:{$sourceid}+AND+retweeted_mid:" . $mid . ")";
                        } else {
                            $guid = getGuid($sourceid, $mid);
                            $where = "(sourceid:{$sourceid}+AND+retweeted_guid:" . $guid . ")";
                        }
                    }
                    break;
                //当微博模型 类型 选择评论
                case "oristatus":
                    $where = "retweeted_status:" . $filtervalueobj;  //需要添加数据源
                    break;
                case "oristatus_username": //原创查转发
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "{!join+from{$eqen}guid+to{$eqen}retweeted_guid}screen_name:" . $filtervalueobj;
                    break;
                case "oristatus_userid": //原创查转发
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "{!join+from{$eqen}guid+to{$eqen}retweeted_guid}userid:" . $filtervalueobj;
                    break;
                case "repost_url": //转发url 查原创
                    $sourceid = get_sourceid_from_url($filtervalueobj);
                    $mid = weiboUrl2mid($filtervalueobj, $sourceid);
                    if ($mid != "" && !empty($sourceid)) {
                        if ($sourceid == 1) {
                            $where = "{!join+from{$eqen}retweeted_guid+to{$eqen}guid}(sourceid:{$sourceid}+AND+mid:" . $mid . ")";
                        } else {
                            $guid = getGuid($sourceid, $mid);
                            $where = "{!join+from{$eqen}retweeted_guid+to{$eqen}guid}(sourceid:{$sourceid}+AND+guid:" . $guid . ")";
                        }
                    }
                    break;
                case "repost_username": //转发查原创
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "{!join+from{$eqen}retweeted_guid+to{$eqen}guid}screen_name:" . $filtervalueobj;
                    break;
                case "repost_userid": //转发查原创
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "{!join+from{$eqen}retweeted_guid+to{$eqen}guid}userid:" . $filtervalueobj;
                    break;
                case "url":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "url:" . $filtervalueobj . "*"; //进行前缀匹配, 模糊查询以"url"开头的
                    break;
                case "ancestor_url":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "ancestor_url:" . $filtervalueobj . "*"; //进行前缀匹配, 模糊查询以"url"开头的
                    break;
                case "repostsnum":
                    $tmpwhere = getRangeWhere("reposts_count", $filtervalueobj);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "commentsnum":
                    $tmpwhere = getRangeWhere("comments_count", $filtervalueobj);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "direct_comments_count":
                case "praises_count":
                case "paragraphid":
                case "trample_count":
                case "satisfaction":
                case "godRepPer":
                case "midRepPer":
                case "wosRepPer":
                case "godRepNum":
                case "midRepNum":
                case "wosRepNum":
                case "apdRepNum":
                case "showPicNum":
                case "cmtStarLevel":
                case "proOriPrice":
                case "proCurPrice":
                case "proPriPrice":
                case "stockNum":
                case "salesNumMonth":
                case "operateTime":
                case "compDesMatch":
                case "logisticsScore":
                case "serviceScore":
                case "question_id":
                case "answer_id":
                case "child_post_id":
                case "question_father_id":
                case "answer_father_id":
                case "floor":
                case "read_count":
                case "total_reposts_count":
                case "direct_reposts_count":
                case "total_reach_count":
                case "followers_count":
                case "level":
                case "repost_trend_cursor":
                case "created_year":
                case "created_month":
                case "created_day":
                case "created_hour":
                case "created_weekday":
                    $tmpwhere = getRangeWhere($filterfieldname, $filtervalueobj);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "areauser":
                    $areatype = codelevel($filtervalueobj, false);
                    $areatype .= "_code";
                    $where = $areatype . ":" . $filtervalueobj;
                    break;
                case "areamentioned":
                    $areatype = codelevel($filtervalueobj, false);
                    $where = $areatype . ":" . $filtervalueobj;
                    break;
                case "ancestor_areamentioned":
                    $areatype = codelevel($filtervalueobj, false);
                    $where = "ancestor_" . $areatype . ":" . $filtervalueobj;
                    break;
                case "createdtime":
                    //$tmpwhere = getRangeWhere("created_at", $filtervalueobj);
                    $tmpwhere = getRangeWhere($rfname, $filtervalueobj);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "nearlytime":
                case "beforetime":
                    $timearr = gettimeDynamicState($filtervalueobj);
                    if (isset($filtervalueobj["include"])) {
                        $timearr["include"] = $filtervalueobj["include"];
                    }
                    //$tmpwhere = getRangeWhere("created_at", $timearr);
                    $tmpwhere = getRangeWhere($rfname, $timearr);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "untiltime":
                    $untiltime = gettimeDynamicRange($filtervalueobj);
                    if (isset($filtervalueobj["include"])) {
                        $untiltime["include"] = $filtervalueobj["include"];
                    }
                    //$tmpwhere = getRangeWhere("created_at", $untiltime);
                    $tmpwhere = getRangeWhere($rfname, $untiltime);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "registertime":
                    //博龄把以天为单位的转为时间戳
                    //$arg_query_timegapstart = "day";
                    //$arg_query_timegapend = "day";
                    if (isset($filtervalueobj["gap"])) {  //q 查询时 start end为相对时间
                        $arg_query_registertime_gap = $filtervalueobj["gap"];
                        if ($filtervalueobj["start"] !== null) {
                            $arg_query_registertime_start = strtotime("-" . $filtervalueobj["start"] . " " . $arg_query_registertime_gap . "");
                        } else {
                            $arg_query_registertime_start = $filtervalueobj["start"];
                        }
                        if ($filtervalueobj["end"] !== null) {
                            $arg_query_registertime_end = strtotime("-" . $filtervalueobj["end"] . " " . $arg_query_registertime_gap . "");
                        } else {
                            $arg_query_registertime_end = $filtervalueobj["end"];
                        }
                    } else { //drilldown时 start, end为时间戳
                        $arg_query_registertime_start = $filtervalueobj["start"];
                        $arg_query_registertime_end = $filtervalueobj["end"];
                    }
                    //博龄为相对现在的时间
                    if ($arg_query_registertime_start > $arg_query_registertime_end) {
                        $regtime["start"] = $arg_query_registertime_end;
                        $regtime["end"] = $arg_query_registertime_start;
                    } else {
                        $regtime["start"] = $arg_query_registertime_start;
                        $regtime["end"] = $arg_query_registertime_end;
                    }
                    if (isset($filtervalueobj["include"])) {
                        $regtime["include"] = $filtervalueobj["include"];
                    }
                    $tmpwhere = getRangeWhere("register_time", $regtime);
                    if ($tmpwhere != "") {
                        $where = $tmpwhere;
                    }
                    break;
                case "emotion": //情感关键词
                case "emoCombin": //情感短语
                case "emoNRN": //情感人名
                case "emoOrganization":
                case "emoTopic": //情感微博话题短语
                case "emoTopicKeyword": //情感微博话题关键词
                case "emoTopicCombinWord": //情感微博话题短语
                case "ancestor_emotion": //情感关键词
                case "ancestor_emoCombin": //情感短语
                case "ancestor_emoNRN": //情感人名
                case "ancestor_emoOrganization":
                case "ancestor_emoTopic": //情感微博话题短语
                case "ancestor_emoTopicKeyword": //情感微博话题关键词
                case "ancestor_emoTopicCombinWord": //情感微博话题短语
                    //当情感选择全部时,* 替换为?
                    $filtervalueobj = preg_replace("/\*$/", "?", $filtervalueobj);
                    $filtervalueobj = str_replace(" ", ",", $filtervalueobj);
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = $filterfieldname . ':' . $filtervalueobj;  //%3A->: %23->#
                    break;
                case "emoAccount":
                case "emoBusiness":
                case "ancestor_emoAccount":
                case "ancestor_emoBusiness":
                    //当情感选择全部时,* 替换为?
                    $filtervalueobj = preg_replace("/\*$/", "?", $filtervalueobj);
                    $filtervalueobj = str_replace(" ", ",", $filtervalueobj);
                    $where = $filterfieldname . ':' . $filtervalueobj;  //%3A->: %23->#
                    break;
                case "emoAreamentioned":
                    $filtervalueobj = preg_replace("/\*$/", "?", $filtervalueobj);
                    $emoval = explode(",", $filtervalueobj);
                    $emoareatype = codelevel($emoval[0], true);
                    $filtervalueobj = str_replace(" ", ",", $filtervalueobj);
                    $where = $emoareatype . ":" . $filtervalueobj;
                    break;
                case "ancestor_emoAreamentioned":
                    $filtervalueobj = preg_replace("/\*$/", "?", $filtervalueobj);
                    $emoval = explode(",", $filtervalueobj);
                    $emoareatype = codelevel($emoval[0], true);
                    $filtervalueobj = str_replace(" ", ",", $filtervalueobj);
                    $where = "ancestor_" . $emoareatype . ":" . $filtervalueobj;
                    break;
                case "emoCountry":
                case "emoProvince":
                case "emoCity":
                case "emoDistrict":
                case "ancestor_emoCountry":
                case "ancestor_emoProvince":
                case "ancestor_emoCity":
                case "ancestor_emoDistrict":
                    $filtervalueobj = preg_replace("/\*$/", "?", $filtervalueobj);
                    $filtervalueobj = str_replace(" ", ",", $filtervalueobj);
                    $where = $filterfieldname . ":" . $filtervalueobj;
                    break;
                case "weibotype":
                    $arg_query_weibotype = $filtervalueobj;
                    if ($arg_query_weibotype != null) {
                        if ($arg_query_weibotype == '100') {
                            $where = "content_type:0";
                        } else if ($arg_query_weibotype == '101') {
                            $where = "content_type:1";
                        } else if ($arg_query_weibotype == '102') {
                            //处理评论
                            $where = "content_type:2";
                        } else if ($arg_query_weibotype == '104') {
                            //处理提问
                            $where = "content_type:3";
                        } else if ($arg_query_weibotype == '105') {
                            //处理回答
                            $where = "content_type:4";
                        } else if ($arg_query_weibotype == '103') {   //当微博类型为空
                            //$where = "content_type:''";
                        }
                    }
                    break;
                case "source":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "source:" . $filtervalueobj;
                    break;
                /*
                case "sourceid":
                    $where = "sourceid:".$filtervalueobj;
                    break;
                     */
                case "username":
                case "screen_name":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "screen_name:" . $filtervalueobj;
                    break;
                /*
                case "sex":
                    $where = "sex:".$filtervalueobj."";
                    break;
                     */
                case "verified":
                case "verify":
                    $filtervalueobj = solrEsc($filtervalueobj); //转义负数
                    $where = "verify:" . $filtervalueobj . "";
                    break;
                case "verified_type":
                    $filtervalueobj = solrEsc($filtervalueobj); //转义负数
                    $where = "verified_type:" . $filtervalueobj;
                    break;
                case "source_host":
                    $sh = get_source_id($filtervalueobj);
                    if (!empty($sh)) {
                        //$where = "sourceid:".$sh;
                        $sourceurls = get_source_url($sh);
                        $sourceArr = array();
                        foreach ($sourceurls as $si => $sitem) {
                            $sourceArr[] = "source_host:" . $sitem;
                        }
                        $where = "(" . implode("+OR+", $sourceArr) . ")";
                    } else {
                        $filtervalueobj = solrEsc($filtervalueobj); //转义负数
                        $where = "source_host:" . $filtervalueobj;
                    }
                    break;
                case "haspicture":
                case "has_picture":
                    $filtervalueobj = solrEsc($filtervalueobj); //转义负数
                    $where = "has_picture:" . $filtervalueobj . "";
                    break;
                case "hostdomain":
                case "host_domain":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "host_domain:" . $filtervalueobj . "";
                    break;
                case "verifiedreason":
                case "verified_reason":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "verified_reason:" . $filtervalueobj . "";
                    break;
                case "originalcontent":
                case "originalText":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "originalText:" . $filtervalueobj . "";
                    break;
                case "digestcontent":
                case "similar":
                    $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj));
                    $where = "similar:" . $filtervalueobj . "";
                    break;
                default:
                    if ($filtervalueobj == "*") {
                        $filtervalueobj = solrEsc($filtervalueobj);
                    } else {
                        $filtervalueobj = queryAddQuotation(solrEsc($filtervalueobj), true);
                    }
                    $where = $filterfieldname . ":" . $filtervalueobj;
                    break;
            }
        }
        $resultsql[] = $excludestr . $where;
    }
    $sqlwhere = "";
    if (count($resultsql) == 1) {
        $sqlwhere = $resultsql[0];
    } else {
        $opt = isset($filtervalue_obj['opt']) ? '+' . $filtervalue_obj['opt'] . '+' : '+OR+';
        $sqlwhere = '(' . implode($opt, $resultsql) . ')';
    }
    return $sqlwhere;
}


function getSnapShotByID($snapid)
{
    global $dsql, $logger;
    $result = array();
    $sql = "select snapshot, updatetime from " . DATABASE_SNAPSHOT_HISTORY . " where snapid = " . $snapid . "";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
    } else {
        $num = $dsql->GetTotalRow($qr);
        if ($num > 0) {
            $res = $dsql->GetArray($qr, MYSQL_ASSOC);
            $result["snapshot"] = json_decode($res["snapshot"], true);
            $result["updatetime"] = $res["updatetime"];
        }
    }
    return $result;
}

/**
 *
 * 根据实例ID获取元素信息
 * @param  $instanceid
 * @param $instanceid
 * @param $elementtype 对应数据库字段type，0：全部
 */
function getelements($instanceid, $elementtype = 0, $needsnap = true, $snapid = 0)
{
    global $dsql, $logger;
    if (!isset($instanceid)) {
        return false;
    }
    $arrs = array();

    $num = 0;
    $sql = "";
    $selfield = "a.instanceid,a.modelid, a.elementid,a.content,a.type,a.title,a.updatetime,a.modelparams,a.issavesnapshot";//add by zq
    if ($needsnap) {
        //$selfield .= ",a.snapshot";
        $selfield .= ",a.snapid";
    }
    if ($elementtype == 0) {
        $sql = "select {$selfield},b.instancetype from " . DATABASE_ELEMENT . " as a inner join " . DATABASE_TENANT_TAGINSTANCT . " as b  on a.instanceid = b.id where a.instanceid=" . $instanceid;
    } else {
        $sql = "select {$selfield},b.instancetype from " . DATABASE_ELEMENT . " as a inner join " . DATABASE_TENANT_TAGINSTANCT . " as b  on a.instanceid = b.id where a.instanceid=" . $instanceid . " and a.type=" . $elementtype;
    }
    $qr = $dsql->ExecQuery($sql);
    $logger->debug(__FILE__ . __LINE__ . " sql " . $sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
    } else {
        $inctype;
        $num = $dsql->GetTotalRow($qr);
        if ($num > 0) {
            while ($result = $dsql->GetArray($qr, MYSQL_ASSOC)) {
                $temp_arr = array();
                $inctype = $result["instancetype"];
                $temp_arr["instanceid"] = $result["instanceid"];
                $temp_arr["instancetype"] = $result["instancetype"];
                $temp_arr["modelid"] = $result["modelid"];
                $temp_arr["elementid"] = $result["elementid"];
                $temp_arr["type"] = $result["type"];
                $temp_arr["title"] = $result["title"];
                $temp_arr['updatetime'] = $result['updatetime'];
                $temp_arr['issavesnapshot'] = $result['issavesnapshot'];//add issavesnapshot by zuo:2016-9-9
                $tmpJson = json_decode($result["content"], true); //elements json
                $logger->debug(__FILE__ . __LINE__ . " tmpJson " . var_export($tmpJson, true));
                if ($needsnap) {
                    if (!empty($result["snapid"])) {
                        $sid = $result["snapid"];
                        if (!empty($snapid)) {
                            $sid = $snapid;
                        }
                        $snapshot = getSnapShotByID($sid);

                       /* if(isset($snapshot['snapshot']['errorcode'])){
                            $errormsg = "快照更新时失败，错误是".$snapshot['snapshot']['error'];
                            return getErrorOutput($snapshot['snapshot']['errorcode'], $errormsg);
                        }*/
                        //add 2017-2-20 && !isset($snapshot['snapshot']['errorcode'])
                        if (!empty($snapshot["snapshot"]) && !isset($snapshot['snapshot']['errorcode'])) {
                            $tmpresult = sortSnapshotField($snapshot["snapshot"], $temp_arr["modelid"]);
                            $temp_arr['snapshot'] = $tmpresult;
                            $temp_arr['updatetime'] = $snapshot["updatetime"];
                        }else{
                            $temp_arr['snapshot'] = $snapshot["snapshot"];
                        }
                    }
                    if (isset($temp_arr['snapshot']) && count($temp_arr['snapshot']) > 0 && $temp_arr["modelid"] == 1 && $tmpJson["version"] <= 1031) {//用户模型的字段添加users_前缀, 保存的快照数据需要更新
                        foreach ($temp_arr['snapshot'] as $si => $sitem) {
                            $sitemarr = array();
                            foreach ($sitem['datalist'] as $di => $ditem) {
                                $fitemarr = array();
                                foreach ($ditem as $fi => $fitem) {
                                    $fitemarr["users_" . $fi] = $fitem;
                                }
                                $sitemarr[] = $fitemarr;
                            }
                            $temp_arr['snapshot'][$si]['datalist'] = $sitemarr;
                        }
                    }
                    $snapshotsched = getSnapshotSchedule($result["instanceid"]);
                    if (!empty($snapshotsched)) {
                        $schedparams = array();
                        $schedparams["status"] = $snapshotsched->status;
                        $schedparams["cronstart"] = $snapshotsched->starttime;
                        $schedparams["cronend"] = $snapshotsched->endtime;
                        $schedparams["crontime"] = $snapshotsched->crontime;
                        $schedparams["remarks"] = $snapshotsched->remarks;
                        $schedparams["history_enable"] = $snapshotsched->params->taskparams->history_enable;
                        if (isset($snapshotsched->params->taskparams->history_duration)) {
                            $schedparams["history_duration"] = $snapshotsched->params->taskparams->history_duration;
                        }
                        if (isset($snapshotsched->params->taskparams->history_count)) {
                            $schedparams["history_count"] = $snapshotsched->params->taskparams->history_count;
                        }
                        //触发事件列表
                        if (isset($snapshotsched->params->taskparams->eventlist)) {
                            $schedparams["eventlist"] = $snapshotsched->params->taskparams->eventlist;
                        }
                        $arrs["schedparams"] = $schedparams;
                    }
                    //如果是虚拟数据源,直接返回结果
                    if ($result["modelid"] == 6) {
                        $temp_arr["datajson"] = json_decode($result["content"], true);
                        $arrs['elements'][] = $temp_arr;
                        return $arrs;
                    }
                }
                //$temp_arr["datajson"] = json_decode($result["content"]);
                /*当新添加字段时,根据版本 用account_rule中对应模型filter字段覆盖 elements中filter字段*/
                $authJson = getAuthJson($tmpJson["modelid"]); //获取account_rule的limit
                $accountJson = getAccountingJson($tmpJson["modelid"]);
                if (empty($accountJson)) {
                    return getErrorOutput(VALIDATE_ERROR_GETPERMISSION, "没有权限访问此资源!");
                }
                $authJson = getCommonMergeJson(2, $accountJson, $authJson); //计费和权限的合并
                if (VERSION != $tmpJson["version"] || VERSION != $authJson["version"]) { //合并model_config, account_rule, elements
                    $newJson = getModelByID($tmpJson["modelid"]);
                    $jsonArr = json_decode(json_encode($newJson), true);
                    $newJsonArr = $jsonArr["datajson"];
                    $temp_arr["datajson"] = getCommonMergeJson(1, $newJsonArr, $authJson, $tmpJson);
                    //$temp_arr["datajson"] = getNewVersionJson($tmpJson, $authJson);
                } else {
                    //合并account_rule和elements //版本相同时,不需要权限到最新版本的合并, 第一和第二个参数相同
                    $temp_arr["datajson"] = getCommonMergeJson(2, $tmpJson, $authJson);
                    //$temp_arr["datajson"] = getMergeJson($tmpJson, $authJson);
                }
                $outlimit = Authorization::validatingQueryformAll($tmpJson, $authJson, $accountJson);
                //解决用户分析字段改名,兼容旧版本orderby 名称
                if ($tmpJson["modelid"] == 1 && $tmpJson["version"] <= 1031) {
                    $ob = "";
                    switch ($temp_arr["datajson"]["output"]["orderby"]) {
                        case "followers_count":
                            $ob = "users_followers_count";
                            break;
                        case "friends_count":
                            $ob = "users_friends_count";
                            break;
                        case "statuses_count":
                            $ob = "users_statuses_count";
                            break;
                        default:
                            break;
                    }
                    $temp_arr["datajson"]["output"]["orderby"] = $ob;
                }

                //解决存存数据库 jsonencode4db时，将0 变为 null的  bug
                if (!empty($temp_arr['datajson']['filterrelation'])) {
                    array_walk_recursive($temp_arr['datajson']['filterrelation'], "null2zero");
                }
                if (!empty($temp_arr['datajson']['filtervalue'])) {
                    foreach ($temp_arr['datajson']['filtervalue'] as $b_k => $b_v) {
                        if (!isset($b_v['fromlimit'])) {
                            $temp_arr['datajson']['filtervalue'][$b_k]['fromlimit'] = 0;
                        }
                        if ($b_v['fieldvalue']['datatype'] == 'array') {
                            foreach ($b_v['fieldvalue']['value'] as $_b_k => $_b_v) {
                                if ($_b_v['datatype'] == "int" && $_b_v['value'] === null) {
                                    $temp_arr['datajson']['filtervalue'][$b_k]['fieldvalue']['value'][$_b_k]['value'] = 0;
                                }
                            }
                        } else {
                            if ($b_v['fieldvalue']['datatype'] == 'int' && $b_v['fieldvalue']['value'] === null) {
                                $temp_arr['datajson']['filtervalue'][$b_k]['fieldvalue']['value'] = 0;
                            }
                        }
                    }
                }
                if (!empty($temp_arr['datajson']['output']) && $temp_arr['datajson']['output']['data_limit'] === null) {
                    $temp_arr['datajson']['output']['data_limit'] = 0;
                }
                //end---
                //合并filtervalue
                $temp_arr["datajson"] = mergefiltervalue($temp_arr["datajson"], $outlimit);
                if ($inctype == 3) {
                    $mp = json_decode($result["modelparams"], true);
                    if (isset($mp["modelname"])) {
                        $temp_arr["modelname"] = $mp["modelname"];
                    }
                    if (isset($mp["referencedata"])) {
                        $temp_arr["referencedata"] = $mp["referencedata"];
                    }
                    /*
                    else{
                        $temp_arr["referencedata"] = false;
                    }
                     */
                    if (isset($mp["secondaryyaxis"])) {
                        $temp_arr["secondaryyaxis"] = $mp["secondaryyaxis"];
                    }
                    if (!empty($tmpJson["showid"])) {
                        $temp_arr["showid"] = $tmpJson["showid"][0];
                    } else if (isset($mp["showid"])) {
                        $temp_arr["showid"] = $mp["showid"];
                    }
                    if (!empty($tmpJson["linetype"])) {
                        $temp_arr["linetype"] = $tmpJson["linetype"];
                    }

                    if (isset($mp["referencedataratio"])) {
                        $temp_arr["referencedataratio"] = $mp["referencedataratio"];
                    }
                    /*
                    else{
                        $temp_arr["referencedataratio"] = 1;
                    }
                     */
                    if (isset($mp["xcombined"])) {
                        $temp_arr["xcombined"] = $mp["xcombined"];
                    }
                    if (isset($mp["columnstacking"])) {
                        $temp_arr["columnstacking"] = $mp["columnstacking"];
                    }
                    if (isset($mp["xzreverse"])) {
                        $temp_arr["xzreverse"] = $mp["xzreverse"];
                    }
                    if (isset($mp["subInstanceType"])) {
                        $temp_arr["subInstanceType"] = $mp["subInstanceType"];
                    }
                    if (isset($mp["overlayindex"])) {
                        $temp_arr["overlayindex"] = $mp["overlayindex"];
                    }
                }
                $temp_arr['outputpin'] = array();
                $temp_arr['inputpin'] = array();
                $arrs['elements'][] = $temp_arr;
            }
            if (($inctype == 2 || $inctype == 3) && $elementtype == 0) {//联动时,并且获取全部elements时，取pinrelation
                $sqlgetrelation = "select * from " . DATABASE_PINRELATION . " where instanceid={$instanceid}";
                $qrin = $dsql->ExecQuery($sqlgetrelation);
                if (!$qrin) {
                    $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sqlgetrelation} " . $dsql->GetError());
                } else {
                    while ($result = $dsql->GetArray($qrin, MYSQL_ASSOC)) {
                        unset($temp_element);
                        $temp_element["instanceid"] = $result["instanceid"];
                        $temp_element["outelementid"] = $result["outelementid"];
                        $temp_element["inelementid"] = $result["inelementid"];
                        $temp_element["inpinid"] = $result["inpinid"];
                        $temp_element["outpinid"] = $result["outpinid"];
                        $temp_element["inputdata"] = json_decode($result["inputdata"], true);
                        $temp_element["outputdata"] = json_decode($result["outputdata"], true);
                        for ($i = 0; $i < count($arrs['elements']); $i++) {
                            if ($arrs['elements'][$i]['elementid'] == $result['outelementid'] && !in_array($temp_element, $arrs['elements'][$i]['outputpin'])) {
                                if (isset($arrs['elements'][$i]["overlayindex"])) {
                                    $temp_element["overlayindex"] = $arrs['elements'][$i]["overlayindex"];
                                }
                                $arrs['elements'][$i]['outputpin'][] = $temp_element;
                            }
                            if ($arrs['elements'][$i]['elementid'] == $result['inelementid'] && !in_array($temp_element, $arrs['elements'][$i]['inputpin'])) {
                                if (isset($arrs['elements'][$i]["overlayindex"])) {
                                    $temp_element["overlayindex"] = $arrs['elements'][$i]["overlayindex"];
                                }
                                $arrs['elements'][$i]['inputpin'][] = $temp_element;
                            }
                        }
                        $arrs["pinrelation"][] = $temp_element;
                    }
                }
            }
        }
    }
    return $arrs;
}

/**
 *
 * 联动查询
 * @param $datas 查询参数 {instanceid, elements:[{elementid,datajson}], pinrelation, render:{elementid,datajson}}
 */
function linkageQuery($datas)
{
    global $logger, $dsql;
    $isstatic = count($datas['elements']) == 0;
    $renderjson = $datas['render']['datajson'];
    $renderjson['returnoriginal'] = $datas['render']["returnoriginal"];
    $renderjson['returnrepost'] = $datas['render']["returnrepost"];
    $renderjson['returncomment'] = $datas['render']["returncomment"];
    if (isset($datas['render']["isdrilldown"]) && $datas['render']["isdrilldown"]) {
        $renderjson['isdrilldown'] = $datas['render']["isdrilldown"];
    }
    //$renderjson['returnoriginal'] = $needorig;
    //if($renderjson['modelid'] != 1){//请求solr
    return solragentInit($renderjson, !$isstatic, $datas);
    //}
    //else{
    //	return getdataInit($renderjson, !$isstatic, $datas);
    //}
}

function getDataJson($elementid)
{
    global $dsql, $logger;
    $dataArr;
    $user = getEffectUser();
    $sql = "select elmt.content, elmt.snapid from " . DATABASE_NAME . "." . DATABASE_ELEMENT . " as elmt inner join " . DATABASE_NAME . "." . DATABASE_TENANT_TAGINSTANCT . " as tntinstance on elmt.instanceid = tntinstance.id where elmt.elementid = " . $elementid . " and tntinstance.tenantid =" . $user->tenantid;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
        return false;
    } else {
        $r = $dsql->GetArray($qr);
        if (!$r) {
            $logger->error(__FILE__ . __LINE__ . __FUNCTION__ . " result is null sql:" . $sql);
            return false;
        } else {
            //从数据库读取的datajson 不是最新的datajson,需要经过合并保证filter包含全部字段,保证查询时有最新的字段
            $dataArr = json_decode($r['content'], true);
            /*通过第三方请求虚拟数据模型,虚拟数据没有权限验证,结果存在快照中,请求快照返回结果*/
            if ($dataArr['modelid'] == 6) {
                if (!empty($r["snapid"])) {
                    $sid = $r["snapid"];
                    $snapshot = getSnapShotByID($sid);
                    if (!empty($snapshot["snapshot"])) {
                        $dataArr['snapshot'] = $snapshot["snapshot"];
                        $dataArr['updatetime'] = $snapshot["updatetime"];
                    }
                }
                return $dataArr;
            }
            $authJson = getAuthJson($dataArr["modelid"]); //获取account_rule的limit
            $accountJson = getAccountingJson($dataArr["modelid"]);
            if (empty($accountJson)) {
                $logger->error(__FUNCTION__ . " - 没有权限访问此资源");
                return false;
            }
            $authJson = getCommonMergeJson(2, $accountJson, $authJson); //计费和权限的合并
            //element的JSON版本与配置的最新版本不同
            $oldversion = $dataArr["version"];
            if (VERSION != $dataArr["version"] || VERSION != $authJson["version"]) {
                $newJson = getModelByID($dataArr["modelid"]);
                $jsonArr = json_decode(json_encode($newJson), true);
                $newJsonArr = $jsonArr["datajson"];
                $dataArr = getCommonMergeJson(1, $newJsonArr, $authJson, $dataArr);
                //$dataArr = getNewVersionJson($dataArr, $authJson);//获取最新的结构给element JSON，
            } else { //合并account_rule和elements
                $dataArr = getCommonMergeJson(2, $dataArr, $authJson);
                //$dataArr = getMergeJson($dataArr, $authJson);//element json是最新的，合并权限json
            }
            $outlimit = Authorization::validatingQueryformAll($dataArr, $authJson, $accountJson);
            $outlimit = array();//此处不验证，只合并。
            //解决用户分析字段改名,兼容旧版本orderby 名称
            if ($dataArr["modelid"] == 1 && $oldversion <= 1031) {
                $ob = "";
                switch ($dataArr["output"]["orderby"]) {
                    case "followers_count":
                        $ob = "users_followers_count";
                        break;
                    case "friends_count":
                        $ob = "users_friends_count";
                        break;
                    case "statuses_count":
                        $ob = "users_statuses_count";
                        break;
                    default:
                        break;
                }
                $dataArr["output"]["orderby"] = $ob;
            }

            //解决存存数据库 jsonencode4db时，将0 变为 null的  bug
            if (!empty($dataArr['filterrelation'])) {
                array_walk_recursive($dataArr['filterrelation'], "null2zero");
            }
            if (!empty($dataArr['filtervalue'])) {
                foreach ($dataArr['filtervalue'] as $b_k => $b_v) {
                    //filtervalue中没有fromlimit属性，默认赋值为0
                    if (!isset($b_v['fromlimit'])) {
                        $dataArr['filtervalue'][$b_k]['fromlimit'] = 0;
                    }
                    //filtervalue中int类型的值，如果为null（bug），改成0
                    if ($b_v['fieldvalue']['datatype'] == 'array') {
                        foreach ($b_v['fieldvalue']['value'] as $_b_k => $_b_v) {
                            if ($_b_v['datatype'] == "int" && $_b_v['value'] === null) {
                                $dataArr['filtervalue'][$b_k]['fieldvalue']['value'][$_b_k]['value'] = 0;
                            }
                        }
                    } else {
                        if ($b_v['fieldvalue']['datatype'] == 'int' && $b_v['fieldvalue']['value'] === null) {
                            $dataArr['filtervalue'][$b_k]['fieldvalue']['value'] = 0;
                        }
                    }
                }
            }
            if (!empty($dataArr['output']) && $dataArr['output']['data_limit'] === null) {
                $dataArr['output']['data_limit'] = 0;
            }

            //end---
            //合并filtervalue
            $dataArr = mergefiltervalue($dataArr, $outlimit);
            /*
            if(!isset($dataArr["filterrelation"])){
                $re = initFilterRelation($dataArr);
                $dataArr["filterrelation"] = $re;
            }
             */
        }
        $dsql->FreeResult($qr);
    }
    return $dataArr;
}

function getDataJsonByUser($elementid, $user)
{
    global $dsql, $logger;
    $dataArr;
//    $user = getEffectUser();
    $sql = "select elmt.content, elmt.snapid from " . DATABASE_NAME . "." . DATABASE_ELEMENT . " as elmt inner join " . DATABASE_NAME . "." . DATABASE_TENANT_TAGINSTANCT . " as tntinstance on elmt.instanceid = tntinstance.id where elmt.elementid = " . $elementid . " and tntinstance.tenantid =" . $user->tenantid;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
        return false;
    } else {
        $r = $dsql->GetArray($qr);
        if (!$r) {
            $logger->error(__FILE__ . __LINE__ . __FUNCTION__ . " result is null sql:" . $sql);
            return false;
        } else {
            //从数据库读取的datajson 不是最新的datajson,需要经过合并保证filter包含全部字段,保证查询时有最新的字段
            $dataArr = json_decode($r['content'], true);
            /*通过第三方请求虚拟数据模型,虚拟数据没有权限验证,结果存在快照中,请求快照返回结果*/
            if ($dataArr['modelid'] == 6) {
                if (!empty($r["snapid"])) {
                    $sid = $r["snapid"];
                    $snapshot = getSnapShotByID($sid);
                    if (!empty($snapshot["snapshot"])) {
                        $dataArr['snapshot'] = $snapshot["snapshot"];
                        $dataArr['updatetime'] = $snapshot["updatetime"];
                    }
                }
                return $dataArr;
            }
            $authJson = getAuthJson($dataArr["modelid"], $user); //获取account_rule的limit
            $accountJson = getAccountingJson($dataArr["modelid"], $user);
            if (empty($accountJson)) {
                $logger->error(__FUNCTION__ . " - 没有权限访问此资源");
                return false;
            }
            $authJson = getCommonMergeJson(2, $accountJson, $authJson); //计费和权限的合并
            //element的JSON版本与配置的最新版本不同
            $oldversion = $dataArr["version"];
            if (VERSION != $dataArr["version"] || VERSION != $authJson["version"]) {
                $newJson = getModelByID($dataArr["modelid"]);
                $jsonArr = json_decode(json_encode($newJson), true);
                $newJsonArr = $jsonArr["datajson"];
                $dataArr = getCommonMergeJson(1, $newJsonArr, $authJson, $dataArr);
                //$dataArr = getNewVersionJson($dataArr, $authJson);//获取最新的结构给element JSON，
            } else { //合并account_rule和elements
                $dataArr = getCommonMergeJson(2, $dataArr, $authJson);
                //$dataArr = getMergeJson($dataArr, $authJson);//element json是最新的，合并权限json
            }
            $outlimit = Authorization::validatingQueryformAll($dataArr, $authJson, $accountJson);
            $outlimit = array();//此处不验证，只合并。
            //解决用户分析字段改名,兼容旧版本orderby 名称
            if ($dataArr["modelid"] == 1 && $oldversion <= 1031) {
                $ob = "";
                switch ($dataArr["output"]["orderby"]) {
                    case "followers_count":
                        $ob = "users_followers_count";
                        break;
                    case "friends_count":
                        $ob = "users_friends_count";
                        break;
                    case "statuses_count":
                        $ob = "users_statuses_count";
                        break;
                    default:
                        break;
                }
                $dataArr["output"]["orderby"] = $ob;
            }

            //解决存存数据库 jsonencode4db时，将0 变为 null的  bug
            if (!empty($dataArr['filterrelation'])) {
                array_walk_recursive($dataArr['filterrelation'], "null2zero");
            }
            if (!empty($dataArr['filtervalue'])) {
                foreach ($dataArr['filtervalue'] as $b_k => $b_v) {
                    //filtervalue中没有fromlimit属性，默认赋值为0
                    if (!isset($b_v['fromlimit'])) {
                        $dataArr['filtervalue'][$b_k]['fromlimit'] = 0;
                    }
                    //filtervalue中int类型的值，如果为null（bug），改成0
                    if ($b_v['fieldvalue']['datatype'] == 'array') {
                        foreach ($b_v['fieldvalue']['value'] as $_b_k => $_b_v) {
                            if ($_b_v['datatype'] == "int" && $_b_v['value'] === null) {
                                $dataArr['filtervalue'][$b_k]['fieldvalue']['value'][$_b_k]['value'] = 0;
                            }
                        }
                    } else {
                        if ($b_v['fieldvalue']['datatype'] == 'int' && $b_v['fieldvalue']['value'] === null) {
                            $dataArr['filtervalue'][$b_k]['fieldvalue']['value'] = 0;
                        }
                    }
                }
            }
            if (!empty($dataArr['output']) && $dataArr['output']['data_limit'] === null) {
                $dataArr['output']['data_limit'] = 0;
            }

            //end---
            //合并filtervalue
            $dataArr = mergefiltervalue($dataArr, $outlimit);
            /*
            if(!isset($dataArr["filterrelation"])){
                $re = initFilterRelation($dataArr);
                $dataArr["filterrelation"] = $re;
            }
             */
        }
        $dsql->FreeResult($qr);
    }
    return $dataArr;
}

function getDisplayName($facetField)
{
    $displayName;
    switch ($facetField) {
        case "text":
            $displayName = "关键词";
            break;
        case "pg_text":
            $displayName = "段关键词";
            break;
        case "created_at":
            $displayName = "创建时间";
            break;
        case "created_year":
            $displayName = "创建时间(年)";
            break;
        case "created_month":
            $displayName = "创建时间(月)";
            break;
        case "created_day":
            $displayName = "创建时间(日)";
            break;
        case "created_hour":
            $displayName = "创建时间(时)";
            break;
        case "created_weekday":
            $displayName = "创建时间(周)";
            break;
        case "combinWord":
            $displayName = "短语";
            break;
        case "business":
            $displayName = "行业";
            break;
        case "areauser":
            $displayName = "用户地区";
            break;
        case "users_city_code":
        case "city_code":
            $displayName = "用户地区";
            break;
        case "users_district_code":
        case "district_code":
            $displayName = "用户地区";
            break;
        case "users_province_code":
        case "province_code":
            $displayName = "用户地区";
            break;
        case "users_country_code":
        case "country_code":
            $displayName = "用户地区";
            break;
        case "areamentioned":
            $displayName = "提及地区";
            break;
        case "city":
            $displayName = "提及地区";
            break;
        case "district":
            $displayName = "提及地区";
            break;
        case "province":
            $displayName = "提及地区";
            break;
        case "country":
            $displayName = "提及地区";
            break;
        case "account":
            $displayName = "@用户";
            break;
        case "userid":
        case "users_id":
            $displayName = "用户名";
            break;
        case "url":
            $displayName = "URL";
            break;
        case "NRN":
            $displayName = "人名";
            break;
        case "organization":
            $displayName = "机构";
            break;
        case "wb_topic":
            $displayName = "微博话题";
            break;
        case "wb_topic_keyword":
            $displayName = "微博话题关键词";
            break;
        case "wb_topic_combinWord":
            $displayName = "微博话题短语";
            break;
        case "reply_comment":
            $displayName = "父评论";
            break;
        case "screen_name":
        case "users_screen_name":
            $displayName = "作者";
            break;
        case "users_verified_reason":
        case "verified_reason":
            $displayName = "认证原因";
            break;
        case "users_level":
        case "level":
            $displayName = "用户级别";
            break;
        case "users_recommended_count":
        case "recommended_count":
            $displayName = "精华帖数";
            break;
        case "users_replys_count":
        case "replys_count":
            $displayName = "回复数";
            break;
        case "users_verified_type":
        case "verified_type":
            $displayName = "认证类型";
            break;
        case "originalText":
            $displayName = "原文内容";
            break;
        case "similar":
            $displayName = "摘要内容";
            break;
        case "source":
            $displayName = "应用来源";
            break;
        case "users_description":
        case "description":
            $displayName = "简介";
            break;
        case "emotion":
            $displayName = "情感关键词";
            break;
        case "emoCombin":
            $displayName = "情感短语";
            break;
        case "emoNRN":
            $displayName = "情感人名";
            break;
        case "emoOrganization":
            $displayName = "情感机构";
            break;
        case "emoTopic":
            $displayName = "情感微博话题";
            break;
        case "emoTopicKeyword":
            $displayName = "情感微博话题关键词";
            break;
        case "emoTopicCombinWord":
            $displayName = "情感微博话题短语";
            break;
        case "emoAccount":
            $displayName = "@用户情感";
            break;
        case "emoBusiness":
            $displayName = "行业情感";
            break;
        case "emoCountry":
            $displayName = "提及国家情感";
            break;
        case "emoProvince":
            $displayName = "提及省份情感";
            break;
        case "emoCity":
            $displayName = "提及城市情感";
            break;
        case "emoDistrict":
            $displayName = "提及县区情感";
            break;
        case "sex":
        case "users_gender":
            $displayName = "性别";
            break;
        case "users_allow_all_act_msg":
            $displayName = "允许私信";
            break;
        case "users_allow_all_comment":
            $displayName = "允许评论";
            break;
        case "users_verified":
        case "verify":
            $displayName = "认证";
            break;
        case "retweeted_status":
            $displayName = "原创ID";
            break;
        case "reposts_count":
            $displayName = "转发数";
            break;
        case "comments_count":
            $displayName = "评论数";
            break;
        case "direct_comments_count":
            $displayName = "直接评论数";
            break;
        case "praises_count":
            $displayName = "赞";
            break;
        case "paragraphid":
            $displayName = "段落号";
            break;
        case "trample_count":
            $displayName = "踩";
            break;
        case "satisfaction":
            $displayName = "满意度";
            break;
        case "godRepPer":
            $displayName = "好评百分比";
            break;
        case "midRepPer":
            $displayName = "中评百分比";
            break;
        case "wosRepPer":
            $displayName = "差评百分比";
            break;
        case "godRepNum":
            $displayName = "好评数";
            break;
        case "midRepNum":
            $displayName = "中评数";
            break;
        case "wosRepNum":
            $displayName = "差评数";
            break;
        case "apdRepNum":
            $displayName = "追评数";
            break;
        case "showPicNum":
            $displayName = "有晒单的评价数";
            break;
        case "cmtStarLevel":
            $displayName = "单评论的标签";
            break;
        case "proOriPrice":
            $displayName = "原价";
            break;
        case "proCurPrice":
            $displayName = "现价";
            break;
        case "proPriPrice":
            $displayName = "促销价";
            break;
        case "stockNum":
            $displayName = "库存";
            break;
        case "salesNumMonth":
            $displayName = "月成交量";
            break;
        case "operateTime":
            $displayName = "开店时长";
            break;
        case "compDesMatch":
            $displayName = "对公司总体打分";
            break;
        case "logisticsScore":
            $displayName = "对公司物流打分";
            break;
        case "serviceScore":
            $displayName = "对公司服务打分";
            break;
        case "question_id":
            $displayName = "提问ID";
            break;
        case "answer_id":
            $displayName = "回答ID";
            break;
        case "child_post_id":
            $displayName = "子帖ID";
            break;
        case "question_father_id":
            $displayName = "提问父ID";
            break;
        case "answer_father_id":
            $displayName = "回答父ID";
            break;
        case "floor":
            $displayName = "楼";
            break;
        case "read_count":
            $displayName = "阅读数";
            break;
        case "total_reposts_count":
            $displayName = "总转发数";
            break;
        case "followers_count":
            $displayName = "直接到达数";
            break;
        case "total_reach_count":
            $displayName = "总到达数";
            break;
        case "repost_trend_cursor":
            $displayName = "转发所处层级";
            break;
        case "direct_reposts_count":
            $displayName = "直接转发数";
            break;
        case "register_time":
        case "users_created_at":
            $displayName = "博龄";
            break;
        case "content_type":
            $displayName = "类型";
            break;
        case "has_picture":
            $displayName = "含有图片";
            break;
        case "host_domain":
            $displayName = "主机域名";
            break;
        case "users_followers_count":
            $displayName = "粉丝数";
            break;
        case "users_friends_count":
            $displayName = "关注数";
            break;
        case "users_friends_id":
            $displayName = "关注";
            break;
        case "users_statuses_count":
            $displayName = "微博数";
            break;
        case "users_favourites_count":
            $displayName = "收藏数";
            break;
        case "users_bi_followers_count":
            $displayName = "互粉数";
            break;
        case "users_sourceid":
            $displayName = "数据来源";
            break;
        case "users_source_host":
            $displayName = "数据来源";
            break;
        default:
            $displayName = "";
            break;
    }
    return $displayName;
}

//获取认证显示名称
function getverifyalias($text)
{
    $alias = "未知";
    $vtarr = verifiedArr();
    foreach ($vtarr as $vi => $vitem) {
        if ("verify_" . $text == $vitem["code"]) {
            $alias = $vitem["name"];
            break;
        }
    }
    return $alias;
}

//获取认证类型显示名称
function getverifiedtypealias($text)
{
    global $logger;
    $alias = "未知"; //返回数据中对应的verified_type字段不在已知数组内, 如:微博女郎
    $vtarr = verifiedTypeArr();
    foreach ($vtarr as $vi => $vitem) {
        foreach ($vitem as $ei => $eitem) {
            if ($text == $eitem["code"]) {
                $alias = $eitem["name"];
                break;
            }
        }
    }
    return $alias;
}

function getweibotypealias($text)
{
    $alias = "";
    switch ($text) {
        case "0":
            $alias = "原创";
            break;
        case "1":
            $alias = "转发";
            break;
        case "2":
            $alias = "评论";
            break;
        case "3":
            $alias = "提问";
            break;
        case "4":
            $alias = "回答";
            break;
        default:
            $alias = $text;
            break;
    }
    return $alias;
}

function getrecommendedalias($text)
{
    $alias = "";
    switch ($text) {
        case "0":
            $alias = "普通帖";
            break;
        case "1":
            $alias = "精华帖";
            break;
        default:
            $alias = $text;
            break;
    }
    return $alias;
}

function gethaspicturealias($text)
{
    $alias = "";
    switch ($text) {
        case "0":
            $alias = "不含图片";
            break;
        case "1":
            $alias = "含有图片";
            break;
        default:
            $alias = $text;
            break;
    }
    return $alias;
}

function getallowallactmsgalias($text)
{
    $alias = "";
    switch ($text) {
        case "0":
            $alias = "拒绝私信";
            break;
        case "1":
            $alias = "允许私信";
            break;
        default:
            $alias = $text;
            break;
    }
    return $alias;
}

function getallowallcommentalias($text)
{
    $alias = "";
    switch ($text) {
        case "0":
            $alias = "拒绝评论";
            break;
        case "1":
            $alias = "允许评论";
            break;
        default:
            $alias = $text;
            break;
    }
    return $alias;
}

function gettextalias($text)
{
    $alias = "";
    switch ($text) {
        case "m":
            $alias = "男";
            break;
        case "f":
            $alias = "女";
            break;
        default:
            break;
    }
    return $alias;
}

function emoval2text($m)
{
    $emotionL = "";
    switch ($m) {
        case 1:
            $emotionL = "反对";
            break;
        case 2:
            $emotionL = "负面";
            break;
        case 3:
            $emotionL = "中立";
            break;
        case 4:
            $emotionL = "正面";
            break;
        case 5:
            $emotionL = "赞赏";
            break;
        default:
            break;
    }
    return $emotionL;
}

function calcval2text($type)
{
    $retstr = "";
    switch ($type) {
        case "field":
            $retstr = "标准";
            break;
        case "count":
            $retstr = "计数";
            break;
        case "sum":
            $retstr = "求和";
            break;
        case "average":
            $retstr = "平均值";
            break;
        case "max":
            $retstr = "最大值";
            break;
        case "min":
            $retstr = "最小值";
            break;
        default:
            break;
    }
    return $retstr;
}

function getGaptext($gap)
{
    $gaptxt = "";
    switch ($gap) {
        case "year":
        case "y":
            $gaptxt = "年";
            break;
        case "month":
        case "m":
            $gaptxt = "月";
            break;
        case "day":
        case "d":
            $gaptxt = "天";
            break;
        case "hour":
        case "h":
            $gaptxt = "小时";
            break;
        default:
            break;
    }
    return $gaptxt;
}

//url中参数facet.field对应schema中 facet字段
function custom2field($custom)
{
    switch ($custom) {
        case 'keyword':
            $schema = 'text';
            break;
        case 'topic':
            $schema = 'combinWord';
            break;
        case 'business':
            $schema = 'business';
            break;
        case 'username':
            $schema = 'screen_name';
            break;
        case 'peoplename':
            $schema = 'NRN'; //人名
            break;
        case 'socialmediauser';
            $schema = 'account';
            break;
        case 'organization':
            $schema = 'organization';
            break;
        case 'url':
            $schema = 'url';
            break;
        case 'weibotopic':
            $schema = 'topic';
            break;
    }
    return $schema;
}

/**
 * 获取权限JSON
 * @param $resourceid 资源ID
 */
function getAuthJson($resourceid, $user = NULL)
{
    global $dsql, $logger;
    if (empty($user)) {
        $user = getEffectUser();
    }
    $tenant_id = $user->tenantid;//租户ID
    if (count($user->roles) == 0) {
        return null;
    } else if (count($user->roles) > 0) {//同时属于多个租户角色，则合并权限
        $rolestr = implode(",", $user->roles);
        $sql = "select ruledata from " . DATABASE_ACCOUNTING_RULE . " where roleid in (" . $rolestr . ") and resourceid = {$resourceid} and tenantid = " . $user->tenantid;
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
            return null;
        } else {
            $authJsonArr = array();
            while ($r = $dsql->GetArray($qr, MYSQL_ASSOC)) {
                $authJson = json_decode($r['ruledata'], true);
                $authJsonArr[] = $authJson;
            }
            $firstAuthJson = $authJsonArr[0];
            if (VERSION != $firstAuthJson["version"]) {
                $newJson = getModelByID($authJson["modelid"]);
                $jsonArr = json_decode(json_encode($newJson), true);
                $newJsonArr = $jsonArr["datajson"];
                $firstAuthJson = getCommonMergeJson(1, $newJsonArr, $firstAuthJson);
            }
            $AJcount = count($authJsonArr);
            for ($i = 1; $i < $AJcount; $i++) {
                $firstAuthJson = getCommonMergeJson(3, $firstAuthJson, $authJsonArr[$i]);
            }
        }
        return $firstAuthJson;
    }
    /*
    else{
        $sql = "select ruledata from ".DATABASE_ACCOUNTING_RULE." where roleid = {$user->roles[0]} and resourceid = {$resourceid} and tenantid = ".$user->tenantid;
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
            return null;
        }
        else{
            $r = $dsql->GetArray($qr);
            if(!$r){
                return null;
            }
            else{
                $authJson = json_decode($r['ruledata'],true);
                if(VERSION != $authJson["version"]){
                    $newJson  = getModelByID($authJson["modelid"]);
                    $jsonArr = json_decode(json_encode($newJson), true);
                    $newJsonArr = $jsonArr["datajson"];
                    $authJson = getNewVersionJson($newJsonArr, $authJson);
                }
                return $authJson;
            }
            $dsql->FreeResult($qr);
        }
    }
     */
}

/**
 * 获取实例的某个元素的json
 * @param $incid 实例ID
 * @param $eleid 元素ID
 */
function getElementJSon($incid, $eleid)
{
    global $dsql, $logger;
    $sql = "select content from " . DATABASE_ELEMENT . " where instanceid = {$incid} and elementid = {$eleid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
        return null;
    } else {
        $r = $dsql->GetArray($qr);
        if (!$r) {
            return null;
        } else {
            return json_decode($r['content'], true);
        }
        $dsql->FreeResult($qr);
    }
}


function getAccountingJson($resourceid, $user = NULL)
{
    global $dsql, $logger;
    if (empty($user)) {
        $user = getEffectUser();
    }
    $tenant_id = $user->tenantid;//租户ID
    $sql = "select * from " . DATABASE_TENANT_RESOURCE_RELATION . " where tenantid ={$tenant_id} and resourceid={$resourceid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
        return null;
    } else {
        $r = $dsql->GetArray($qr);
        if (!$r) {
            return null;
        } else {
            $accountJson = json_decode($r['content'], true);
            if (VERSION != $accountJson["version"]) {
                $newJson = getModelByID($accountJson["modelid"]);
                $jsonArr = json_decode(json_encode($newJson), true);
                $newJsonArr = $jsonArr["datajson"];
                $accountJson = getCommonMergeJson(1, $newJsonArr, $accountJson);
                //$accountJson = getNewVersionJson($newJsonArr, $accountJson);
            }
            return $accountJson;
        }
        $dsql->FreeResult($qr);
    }
}

/**
 *
 * 判断用户的权限 查询时调用
 * 如果计费表中的allowcontrol=-1,则value获取寄给规则中的limit;如果>-1，去实例中的value
 * @param $instanceid 实例ID
 * @param $elementid 元素ID
 * @param $jsondata
 * @param $isdownload 是否是下载文件时进行的验证，如果是，验证output中的count时，与download_DataLimit比较
 * @param $isaccessData 是否远程访问数据API
 * @return 未通过时 返回false  通过后返回全部json的值
 */
function checkUserPure($instanceid, $elementid, $jsondata, $isdownload = false, $isaccessData = false, $pinrelation = NULL)
{
    global $dsql, $logger;
    $user = getEffectUser();
    if ($user->getuserid() == null || $user->getuserid() == "") {
        return getErrorOutput(-1, "登录超时");
    } else {
        $modelid = $jsondata["modelid"];
        //判断角色是否有使用权限
        $chkUseage = Authorization::checkUserUseage($modelid, RESOURCE_TYPE_TENANT);
        if (!$chkUseage) {
            return getErrorOutput(VALIDATE_ERROR_NOPERMISSION, "没有权限使用资源,请设置后重新登录!");
            //return false;
        }
        //获取权限JSON
        $authJson = getAuthJson($modelid);
        if (empty($authJson)) {
            return getErrorOutput(VALIDATE_ERROR_GETPERMISSION, "获取权限失败!");
            //return false;
        }
        $accountJson = getAccountingJson($modelid);
        if (empty($accountJson)) {
            return getErrorOutput(VALIDATE_ERROR_GETPERMISSION, "获取计费失败!");
            //return false;
        }
        $authJson = getCommonMergeJson(2, $accountJson, $authJson); //计费和权限的合并
        $res = Authorization::validatingQueryformAll($jsondata, $authJson, $accountJson, $isdownload, $isaccessData, $pinrelation);
        if (!empty($res) && count($res) > 0) { //权限验证未通过
            return $res;
        } else {
            return VALIDATE_SUCCESS;
        }
    }
}

/**
 *
 * 验证用户权限，保存json配置时调用
 * @param $instanceid 实例ID
 * @param $elementid  元素ID
 * @param $jsondata   json对象
 */
function checkUserAuth($instanceid, $elementid, $jsondata)
{
    global $dsql, $logger;
    $user = getEffectUser();
    if ($user->getuserid() == null || $user->getuserid() == "") {
        return VALIDATE_ERROR_NOPERMISSION;
    } else {
        $modelid = $jsondata["modelid"];
        //判断角色是否有使用权限
        $chkUseage = Authorization::checkUserUseage($modelid, RESOURCE_TYPE_TENANT);
        if (!$chkUseage) {
            return VALIDATE_ERROR_NOPERMISSION;
        }
        //获取权限JSON
        $authJson = getAuthJson($modelid);
        if (empty($authJson)) {
            return VALIDATE_ERROR_GETPERMISSION;
        }
        $accountJson = getAccountingJson($modelid);
        if (empty($accountJson)) {
            setErrorMsg(VALIDATE_ERROR_GETPERMISSION, "获取计费失败!");
        }
        $authJson = getCommonMergeJson(2, $accountJson, $authJson); //计费和权限的合并
        $res = Authorization::validatingQueryformAll($jsondata, $authJson, $accountJson);
        if (!empty($res) && count($res) > 0) { //权限验证未通过
            return $res;
        } else {
            return VALIDATE_SUCCESS;
        }
    }
}

function getEffectUser()
{
    if (!defined('_NOSESSION_')) {
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : Authorization::getUserFromToken();
    } else {
        $user = $GLOBALS['effectuser'];
    }
    return $user;
}

function sortAlarms(&$alarms)
{
    global $logger;
    if (empty($alarms)) {
        return array();
    }
    $newarr = array();
    foreach ($alarms as $alarm) {
        if (empty($newarr)) {
            $newarr[] = $alarm;
        } else {
            $tmparr = array();
            $added = false;
            foreach ($newarr as $item) {
                if (!$added) {
                    if ($alarm->sevtext < $item->sevtext) {
                        $tmparr[] = $alarm;
                        $added = true;
                    }
                }
                $tmparr[] = $item;
            }
            if (!$added) {
                $tmparr[] = $alarm;
            }
            $newarr = $tmparr;
            unset($tmparr);
        }
    }
    return $newarr;
}

function newSnapshotSchedule($params)
{
    global $logger;
    if (empty($params)) {
        return false;
    }
    $params->crontime = json_decode($params->crontime);
    if (!empty($params->crontime)) {
        $crontime = clone $params->crontime;
        $crontime->precision = 60;
        $crontime->cronmask = getCronMask($crontime);
    }
    $taskparams = (object)array();
    $taskparams->instanceid = $params->instanceid;
    $taskparams->history_enable = $params->history_enable;
    if (!empty($params->history_count)) {
        $taskparams->history_count = $params->history_count;
    }
    if (!empty($params->history_duration)) {
        $taskparams->history_duration = $params->history_duration;
    }
    if (!empty($params->eventlist)) {
        $taskparams->eventlist = (object)array();
        if (!empty($params->eventlist->name)) {
            $taskparams->eventlist->name = $params->eventlist->name;
        }
        if (!empty($params->eventlist->remarks)) {
            $taskparams->eventlist->remarks = $params->eventlist->remarks;
        }
        if (!empty($params->eventlist->datastart)) {
            $taskparams->eventlist->datastart = $params->eventlist->datastart;
        }
        if (!empty($params->eventlist->dataend)) {
            $taskparams->eventlist->dataend = $params->eventlist->dataend;
        }
        if (!empty($params->eventlist->alarms)) {
            $taskparams->eventlist->alarms = sortAlarms($params->eventlist->alarms);
        }
    }
    $schedparams = (object)array();
    $schedparams->taskparams = $taskparams;
    $schedparams->nodup = 1;
    $schedparams->recordtime = 1;
    $schedule = (object)array();
    $schedule->tasktype = TASKTYPE_UPDATE;
    //$schedule->task = TASK_SNAPSHOT;
    $schedule->task = $params->task;
    $schedule->tasklevel = 1;
    $schedule->local = 1;
    $schedule->remote = 0;
    $schedule->conflictdelay = 0;
    $schedule->params = $schedparams;
    if (!empty($params->cronstart)) {
        $schedule->starttime = $params->cronstart;
    }
    if (!empty($params->cronend)) {
        $schedule->endtime = $params->cronend;
    }
    $schedule->remarks = '';
    if (!empty($params->remarks)) {
        $schedule->remarks = $params->remarks;
    }
    if (!empty($crontime)) {
        $schedule->crontime = $crontime;
    }
    if (isset($params->status)) {
        $schedule->status = $params->status;
    }
    return $schedule;
}

function getSnapshotSchedule($instanceid = NULL, $schedstatus = NULL, $scheduleid = NULL)
{
    global $dsql, $logger;
    $result = null;
    $limitcursor = 0;
    $eachcount = 10;
    while (1) {
        $dbtable = DATABASE_WEIBOINFO . "." . DATABASE_TASKSCHEDULE;
        if ($schedstatus != NULL) {
            if ($schedstatus == 0) {
                $dbtable = DATABASE_WEIBOINFO . "." . DATABASE_TASKSCHEDULEHISTORY;
            }
        }
        $sql = "select * from " . $dbtable . " where tasktype = " . TASKTYPE_UPDATE . " and (task = " . TASK_SNAPSHOT . " or task = " . TASK_EVENTALERT . ") order by id limit {$limitcursor},{$eachcount}";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
            $result = false;
            break;
        } else {
            $r_count = $dsql->GetTotalRow($qr);
            if ($r_count == 0) {
                break;
            }
            while ($sched = $dsql->GetObject($qr)) {
                $sched->params = json_decode($sched->params);
                if ($scheduleid != NULL) {
                    if ($sched->id == $scheduleid) {
                        $sched->history = false; //从事件历史表中得到
                        $result = $sched;
                        break 2;
                    }
                } else if ($instanceid != NULL) {
                    if ($sched->params->taskparams->instanceid == $instanceid) {
                        $sched->history = false; //从事件历史表中得到
                        $result = $sched;
                        break 2;
                    }
                }
            }
            $dsql->FreeResult($qr);
        }
        if ($r_count < $eachcount) {
            break;
        }
        $limitcursor += $eachcount;
    }
    return $result;
}

//根据事件历史查出触发事件历史的条件,最初事件历史表没有scheduleid字段,需要通过triggertime
//确定每一条历史事件对应哪个定时任务,triggertime是存在 任务json taskparams中的,由于事件历史对应
//的定时任务可能过期也可能没有过期,所以通过历史任务表查询,这里包括所有执行过的任务,不用分别从定时任务表和
//过期定时任务表查询了.
//后为事件历史表添加了scheduleid字段并通过脚本更新这个字段为定时任务id
function getSnapshotScheduleAll($instanceid, $triggertime = NULL, $scheduleid = 0)
{
    global $dsql, $logger;
    $result = null;
    $limitcursor = 0;
    $eachcount = 10;
    while (1) {
        $where = array();
        $where[] = "tasktype = " . TASKTYPE_UPDATE . "";
        $where[] = "task = " . TASK_EVENTALERT . "";
        $wherestr = "";
        if (count($where) > 0) {
            $wherestr = " where " . implode(" and ", $where);
        }
        $sql = "select * from " . DATABASE_WEIBOINFO . "." . DATABASE_TASKHISTORY . " " . $wherestr . " order by id limit {$limitcursor},{$eachcount}";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
            $result = false;
            break;
        } else {
            $r_count = $dsql->GetTotalRow($qr);
            if ($r_count == 0) {
                break;
            }
            while ($sched = $dsql->GetObject($qr)) {
                $sched->taskparams = json_decode($sched->taskparams);
                if ($sched->taskparams->instanceid == $instanceid) {
                    //先根据触发时间查找,找到后并不退出,继续通过scheduleid查找,当全部循环完查找不到时再返回
                    if ($sched->taskparams->spawntime == $triggertime) {
                        /*
                        if(!isset($sched->taskparams->scheduleid)){
                            $tmpremark = $sched->remarks;
                            //#创建自定时任务#48#测试计划描述
                            preg_match_all ("/#(.*)#(\d*)#(.*)$/", $tmpremark, $matches);
                            $sched->taskparams->scheduleid = $matches[2][0];
                        }
                         */
                        $result = $sched;
                        //break 2;
                    }
                    //存在scheduleid 直接通过 scheduleid判断
                    if (isset($sched->taskparams->scheduleid)) {
                        if ($sched->taskparams->scheduleid == $scheduleid && $sched->taskparams->spawntime == $triggertime) {
                            $result = $sched;
                            break 2;
                        }
                    } else {
                        if ($sched->taskparams->spawntime == $triggertime) {
                            $tmpremark = $sched->remarks;
                            //#创建自定时任务#48#测试计划描述
                            preg_match_all("/#(.*)#(\d*)#(.*)$/", $tmpremark, $matches);
                            $sched->taskparams->scheduleid = $matches[2][0];
                            if ($sched->taskparams->scheduleid == $scheduleid && $sched->taskparams->spawntime == $triggertime) {
                                $result = $sched;
                                break 2;
                            }
                        }
                    }
                }
            }
            $dsql->FreeResult($qr);
        }
        if ($r_count < $eachcount) {
            break;
        }
        $limitcursor += $eachcount;
    }
    return $result;
}

function updateSnapshotSchedule($params)
{
    global $dsql, $logger;
    $result = array('result' => true, 'msg' => '');
    connectDB(DATABASE_WEIBOINFO);
    $incid = isset($params->instanceid) ? $params->instanceid : NULL;
    $scheduleid = isset($params->scheduleid) ? $params->scheduleid : NULL;
    $oldsched = getSnapshotSchedule($incid, NULL, $scheduleid);
    if ($oldsched === false) {
        $result['result'] = false;
        $result['msg'] = '读取快照定时更新计划失败';
        connectDB(DATABASE_NAME);
        return $result;
    } else if (!empty($oldsched) && $oldsched->status == 2) {
        $result['result'] = false;
        $result['msg'] = '快照定时更新任务运行中，请稍后重试';
        connectDB(DATABASE_NAME);
        return $result;
    }
    //定时快照和事件预警当作两种任务,但是不同时存在,
    //通过判断$params中是否设置eventlist,确定是定时更新任务,还是事件预警任务
    $params->task = TASK_SNAPSHOT;
    if (isset($params->eventlist)) {
        //只有在有事件条件组合时才有事件预警 2014-08-12
        if (isset($params->eventlist->alarms) && count($params->eventlist->alarms) > 0) {
            $params->task = TASK_EVENTALERT;
        }
    }
    $newsched = newSnapshotSchedule($params);
    try {
        if (empty($oldsched)) {
            $result['result'] = addTaskSchedule($newsched);
        } else {
            $newsched->id = $oldsched->id;
            if (!isset($newsched->status)) {
                $newsched->status = $oldsched->status;
            }
            if (isScheduleIdentical($newsched, $oldsched)) {
                connectDB(DATABASE_NAME);
                return $result;
            }
            if (updateTaskScheduleFull($newsched, 'status != 2') == 0) {
                $result['result'] = false;
                $result['msg'] = '快照定时更新任务运行中，请稍后重试';
            } else {
                connectDB(DATABASE_NAME);
                if (empty($newsched->params->taskparams->history_enable) && !empty($oldsched->params->taskparams->history_enable)) {
                    $sql = "delete from " . DATABASE_NAME . "." . DATABASE_SNAPSHOT_HISTORY . " where instanceid = {$params->instanceid}";
                    $qr = $dsql->ExecQuery($sql);
                    if (!$qr) {
                        $result['result'] = false;
                        $result['msg'] = '删除快照历史失败';
                        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
                    }
                    $dsql->FreeResult($qr);
                } else if (!empty($newsched->params->taskparams->history_enable)) {
                    if (!empty($newsched->params->taskparams->history_count)) {
                        $sql = "select distinct elementid from " . DATABASE_NAME . "." . DATABASE_SNAPSHOT_HISTORY . " where instanceid = {$params->instanceid}";
                        $qr = $dsql->ExecQuery($sql);
                        if (!$qr) {
                            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
                            $result['result'] = false;
                            $result['msg'] = '查询快照历史失败';
                            return $result;
                        }
                        if ($dsql->GetTotalRow($qr) > 0) {
                            $elements = array();
                            while ($ele = $dsql->GetObject($qr)) {
                                $elements[] = $ele;
                            }
                        }
                        $dsql->FreeResult($qr);
                        if (!empty($elements)) {
                            foreach ($elements as $element) {
                                $sql = "select count(0) as cnt from " . DATABASE_NAME . "." . DATABASE_SNAPSHOT_HISTORY . " where elementid = {$element->elementid} and instanceid = {$params->instanceid}";
                                $qr = $dsql->ExecQuery($sql);
                                if (!$qr) {
                                    $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
                                    $result['result'] = false;
                                    $result['msg'] = '查询快照历史个数失败';
                                    return $result;
                                }
                                $rcnt = $dsql->GetArray($qr);
                                $dsql->FreeResult($qr);
                                $delcnt = $rcnt['cnt'] - $newsched->params->taskparams->history_count;
                                if ($delcnt > 0) {
                                    $sql = "delete from " . DATABASE_NAME . "." . DATABASE_SNAPSHOT_HISTORY . " where elementid = {$element->elementid} and instanceid = {$params->instanceid} order by updatetime asc limit {$delcnt}";
                                    $qr = $dsql->ExecQuery($sql);
                                    if (!$qr) {
                                        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
                                        $result['result'] = false;
                                        $result['msg'] = '删除多余快照历史失败';
                                        return $result;
                                    }
                                    $dsql->FreeResult($qr);
                                }
                            }
                        }
                    } else if (!empty($newsched->params->taskparams->history_duration)) {
                        $deltime = time() - $newsched->params->taskparams->history_duration;
                        $sql = "delete from " . DATABASE_NAME . "." . DATABASE_SNAPSHOT_HISTORY . " where instanceid = {$params->instanceid} and updatetime < {$deltime}";
                        $qr = $dsql->ExecQuery($sql);
                        if (!$qr) {
                            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
                            $result['result'] = false;
                            $result['msg'] = '删除过期快照历史失败';
                        }
                        $dsql->FreeResult($qr);
                    }
                }
            }
        }
    } catch (Exception $ex) {
        $result['result'] = false;
        $result['msg'] = '保存快照定时更新计划失败';
        $logger->error(__FUNCTION__ . " " . $ex->getMessage());
    }
    connectDB(DATABASE_NAME);
    return $result;
}

function deleteSnapshotSchedule($instanceid, $schedstatus = NULL, $scheduleid = NULL)
{
    global $dsql, $logger;
    $result = array('result' => true, 'msg' => '');
    connectDB(DATABASE_WEIBOINFO);
    //删除过期定时任务
    if ($schedstatus != NULL && $schedstatus == 0) { //过期定时任务
        if (deleteTaskScheduleHistory($scheduleid) == 0) {
            $result["result"] = false;
            $result["msg"] = "删除过期定时任务失败!";
        }
        return $result;
    }
    //删除定时任务
    $oldsched = getSnapshotSchedule($instanceid, $schedstatus, $scheduleid);
    if ($oldsched === false) {
        $result['result'] = false;
        $result['msg'] = '读取快照定时更新计划失败';
        connectDB(DATABASE_NAME);
        return $result;
    } else if (!empty($oldsched) && $oldsched->status == 2) {
        $result['result'] = false;
        $result['msg'] = '快照定时更新任务运行中，请稍后重试';
        connectDB(DATABASE_NAME);
        return $result;
    } else if (empty($oldsched)) {
        connectDB(DATABASE_NAME);
        return $result;
    }
    try {
        if (deleteTaskSchedule($oldsched, 'status != 2') == 0) {
            $result['result'] = false;
            $result['msg'] = '快照定时更新任务运行中，请稍后重试';
        } else {
            connectDB(DATABASE_NAME);
            $sql = "delete from " . DATABASE_SNAPSHOT_HISTORY . " where instanceid = {$instanceid}";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $result['result'] = false;
                $result['msg'] = '删除快照历史失败';
                $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
            }
        }
    } catch (Exception $ex) {
        $result['result'] = false;
        $result['msg'] = '删除快照定时更新计划失败';
        $logger->error(__FUNCTION__ . " " . $ex->getMessage());
    }
    connectDB(DATABASE_NAME);
    return $result;
}

function enableSnapshotSchedule($instanceid)
{
    global $dsql, $logger;
    $result = array('result' => true, 'msg' => '');
    connectDB(DATABASE_WEIBOINFO);
    $oldsched = getSnapshotSchedule($instanceid);
    if ($oldsched === false) {
        $result['result'] = false;
        $result['msg'] = '读取快照定时更新计划失败';
        connectDB(DATABASE_NAME);
        return $result;
    } else if (empty($oldsched)) {
        $result['result'] = false;
        $result['msg'] = '快照定时更新计划不存在';
        connectDB(DATABASE_NAME);
        return $result;
    } else if ($oldsched->status != 0) {
        connectDB(DATABASE_NAME);
        return $result;
    }
    try {
        $oldsched->status = 1;
        if (updateTaskScheduleStatus($oldsched, "status = 0") <= 0) {
            $logger->info(__FUNCTION__ . " 发生冲突，快照定时更新计划{$oldsched->id}已启用");
        }
    } catch (Exception $ex) {
        $result['result'] = false;
        $result['msg'] = '启用快照定时更新计划失败';
        $logger->error(__FUNCTION__ . " " . $ex->getMessage());
    }
    connectDB(DATABASE_NAME);
    return $result;
}

function disableSnapshotSchedule($instanceid)
{
    global $dsql, $logger;
    $result = array('result' => true, 'msg' => '');
    connectDB(DATABASE_WEIBOINFO);
    $oldsched = getSnapshotSchedule($instanceid);
    if ($oldsched === false) {
        $result['result'] = false;
        $result['msg'] = '读取快照定时更新计划失败';
        connectDB(DATABASE_NAME);
        return $result;
    } else if (empty($oldsched)) {
        $result['result'] = false;
        $result['msg'] = '快照定时更新计划不存在';
        connectDB(DATABASE_NAME);
        return $result;
    } else if ($oldsched->status == 2) {
        $result['result'] = false;
        $result['msg'] = '快照定时更新任务运行中，请稍后重试';
        connectDB(DATABASE_NAME);
        return $result;
    } else if ($oldsched->status == 0) {
        connectDB(DATABASE_NAME);
        return $result;
    }
    try {
        $oldsched->status = 0;
        if (updateTaskScheduleStatus($oldsched, "status != 2") <= 0) {
            $result['result'] = false;
            $result['msg'] = '快照定时更新任务运行中，请稍后重试';
        }
    } catch (Exception $ex) {
        $result['result'] = false;
        $result['msg'] = '禁用快照定时更新计划失败';
        $logger->error(__FUNCTION__ . " " . $ex->getMessage());
    }
    connectDB(DATABASE_NAME);
    return $result;
}

function disableEventHistory($instanceid)
{
    global $dsql, $logger;
    $result = array('result' => true, 'msg' => '');
    $deletetime = time();
    $sql = "update " . DATABASE_EVENT_HISTORY . " set instanceid = 0, elementid = 0, deletetime = {$deletetime}
    where instanceid = {$instanceid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $result['result'] = false;
        $result['msg'] = '更新事件历史失败';
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
    } else {
        $dsql->FreeResult($qr);
    }
    return $result;
}



?>
