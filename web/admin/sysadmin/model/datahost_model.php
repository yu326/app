<?php
include_once('includes.php');
include_once('model_config.php');
include_once('checkpure.php');
include_once("authorization.class.php");

initLogger(LOGNAME_WEBAPI);

session_start();

if (Authorization::checkUserSession() != CHECKSESSION_SUCCESS) {
    $arrs["result"] = false;
    $arrs["msg"] = "未登录或登陆超时!";
    echo json_encode($arrs);
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
function getDatahostList($startnum, $pagesize, $host_alias = NULL, $host_dbserver = NULL, $host_dbname = NULL, $host_solrstore = NULL, $host_username = NULL, $host_password = NULL, $host_id = NULL, $get_type = "add")
{
    global $arrs, $dsql, $logger;
    $arrs["result"] = true;
    //where
    $where = array();
    if ($host_id != NULL) {
        if ($get_type == "update") {
            $where[] = "id != " . $host_id . "";
        } else {
            $where[] = "id = " . $host_id . "";
        }
    }
    if ($host_alias != NULL) {
        $where[] = "alias = '" . $host_alias . "'";
    }
    if ($host_dbserver != NULL) {
        $where[] = "dbserver = '" . $host_dbserver . "'";
    }
    //add host_dbname by zuoqian:2016-6-17
    if ($host_dbname != NULL) {
        $where[] = "dbname = '" . $host_dbname . "'";
    }
    //end host_dbname by zuoqian:2016-6-17
    if ($host_username != NULL) {
        $where[] = "username = '" . $host_username . "'";
    }
    if ($host_password != NULL) {
        $where[] = "password = '" . myEncrypt($host_password) . "'";
    }
    if ($host_solrstore != NULL) {
        $where[] = "solrstore = '" . $host_solrstore . "'";
    }

    $wherestr = "";
    if (count($where) > 0) {
        $wherestr = " where " . implode(" and ", $where);
    }
    $totalCount = "select count(*) as totalcount from " . DATABASE_DATAHOST . " " . $wherestr . "";
    $qr = $dsql->ExecQuery($totalCount);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$totalCount} " . $dsql->GetError());
        $arrs["flag"] = 0;
    } else {
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC)) {
            $arrs["totalcount"] = $result["totalcount"];
        }
        $limit = "";
        $startnum = empty($startnum) ? 0 : $startnum;
        if (!empty($pagesize)) {
            $limit = " limit " . $startnum . "," . $pagesize . "";
        }
        $sql = "select * from " . DATABASE_DATAHOST . " " . $wherestr . " " . $limit . "";
        $qr2 = $dsql->ExecQuery($sql);
        if (!$qr2) {
            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
            $arrs["flag"] = 0;
        } else {
            $temp_arr = array();
            $arrs["datalist"] = array();
            while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)) {
                $temp_arr["id"] = $result["id"];
                $temp_arr["alias"] = $result["alias"]; //主机别名
                $temp_arr["dbserver"] = $result["dbserver"]; //数据库地址
                $temp_arr["dbname"] = $result["dbname"]; //数据库名称 add:by zq:2016-6-16
                $temp_arr["username"] = $result["username"]; //数据库用户名
                $temp_arr["password"] = myDecrypt($result["password"]); //数据库密码
                $temp_arr["solrstore"] = $result["solrstore"]; //solr地址
                $arrs["datalist"][] = $temp_arr;
            }
            $arrs["flag"] = 1;
        }
    }
    return $arrs;
}

$kwblurUrl = "";
if (isset($_GET['type'])) {
    if ('selectdatahostinfo' == $_GET['type']) {
        $page = $_GET['page'] - 1; //页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;
        $host_alias = isset($_GET['host_alias']) ? $_GET['host_alias'] : NULL;
        $host_dbserver = isset($_GET['host_dbserver']) ? $_GET['host_dbserver'] : NULL;
        $host_dbname = isset($_GET['host_dbname']) ? $_GET['host_dbname'] : NULL;//add by zuoqain:2016-6-16
        $host_solrstore = isset($_GET['host_solrstore']) ? $_GET['host_solrstore'] : NULL;
        $r = getDatahostList($startnum, $pagesize, $host_alias, $host_dbserver, $host_dbname, $host_solrstore);
        echo json_encode($r);
    }
} else if (isset($HTTP_RAW_POST_DATA)) {
    global $arrsdata, $logger;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if (!isset($arg_type)) {
        $arg_type = $arrsdata["type"];
    }
    //新增
    if ($arg_type == "adddatahost") {
        $host_alias = $arrsdata["host_alias"];
        $host_dbserver = $arrsdata["host_dbserver"];
        $host_dbname = $arrsdata["host_dbname"];
        $host_username = $arrsdata["host_username"];
        $host_password = myEncrypt($arrsdata["host_password"]);
        $host_solrstore = $arrsdata["host_solrstore"];

        $fieldname = array();
        $fieldvalue = array();
        if ($host_alias != "") {
            $fieldname[] = "alias";
            $fieldvalue[] = "'" . $host_alias . "'";
        }
        if ($host_dbserver != "") {
            $fieldname[] = "dbserver";
            $fieldvalue[] = "'" . $host_dbserver . "'";
        }
        //add by zuoqain 2016-6-16
        if ($host_dbname != "") {
            $fieldname[] = "dbname";
            $fieldvalue[] = "'" . $host_dbname . "'";
        }
        if ($host_username != "") {
            $fieldname[] = "username";
            $fieldvalue[] = "'" . $host_username . "'";
        }
        if ($host_password != "") {
            $fieldname[] = "password";
            $fieldvalue[] = "'" . $host_password . "'";
        }
        if ($host_solrstore != "") {
            $fieldname[] = "solrstore";
            $fieldvalue[] = "'" . $host_solrstore . "'";
        }

        $namestr = implode(", ", $fieldname);
        $valuestr = implode(", ", $fieldvalue);
        $senddata = array();
        $sql = "insert into " . DATABASE_DATAHOST . " (" . $namestr . ") values (" . $valuestr . ")";
        $qr = $dsql->ExecQuery($sql);
        $failed = array();
        if (!$qr) {
            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
            $failed[] = $host_alias;
        } else {
            $sourceid = $dsql->GetLastID();
        }

        if (count($failed) == 0) { //数据库添加成功
            $arr["flag"] = 1;
            $arrs["msg"] = "数据更新成功!";
        } else {
            $arr["flag"] = 0;
            if (count($failed) > 0) {
                $arr["msg"] = "数据 " . implode(", ", $failed) . " 数据库添加失败";
            }
        }
        echo json_encode($arr);
    } else if ($arg_type == "updatedatahost") { //修改
        $host_id = $arrsdata["host_id"];
        $host_alias = $arrsdata["host_alias"];
        $host_dbserver = $arrsdata["host_dbserver"];
        $host_dbname = $arrsdata["host_dbname"];//add by zuoqian:2016-6-16
        $host_username = $arrsdata["host_username"];
        $host_password = myEncrypt($arrsdata["host_password"]);
        $host_solrstore = $arrsdata["host_solrstore"];

        $sql = "update " . DATABASE_DATAHOST . " set alias = '" . $host_alias . "', dbserver = '" . $host_dbserver . "', dbname = '" . $host_dbname . "', username = '" . $host_username . "', password = '" . $host_password . "', solrstore = '" . $host_solrstore . "' where id = " . $host_id . "";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
            $arrs["flag"] = 0;
            $arrs["msg"] = "数据失败!";
        } else {
            $arrs["flag"] = 1;
            $arrs["msg"] = "数据更新成功!";
        }
        echo json_encode($arrs);
    } else if ($arg_type == "deletedatahost") {
        $host_arr = $arrsdata["deldata"];
        foreach ($host_arr as $key => $value) {
            $host_id[] = $value["id"];
        }
        $idstr = implode(", ", $host_id);
        $sql = "delete from " . DATABASE_DATAHOST . " where `id` in (" . $idstr . ")";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
            $arrs["flag"] = 0;
            $arrs["msg"] = "数据删除失败!";
        } else {
            $arrs["flag"] = 1;
            $arrs["msg"] = "数据删除成功!";
        }
        echo json_encode($arrs);
    } else if ($arg_type == 'checkvalueexist') { //新增前检查 对应项是否存在
        $hasitem = false;
        $host_id = isset($arrsdata["host_id"]) ? $arrsdata["host_id"] : NULL;
        $get_type = $host_id == NULL ? "add" : "update";
        if (isset($arrsdata["host_alias"])) {
            if ($arrsdata["host_alias"] == "默认主机") {
                $hasitem = true;
                $resulta = array();
                $resulta["datalist"] = array(array("alias" => "默认主机"));
                $r["datalist"]["alias"] = $resulta["datalist"];
                $r["defaulthost"] = true;
            } else {
                //zuoqian
                $resulta = getDatahostList(NULL, NULL, $arrsdata["host_alias"], NULL, NULL, NULL,NULL ,NULL, $host_id, $get_type);
                if (isset($resulta["totalcount"]) && $resulta["totalcount"] > 0) {
                    $hasitem = true;
                    $r["datalist"]["alias"] = $resulta["datalist"];
                }
            }
        }
        if (isset($arrsdata["host_dbserver"])) {
            if ($arrsdata["host_dbserver"] == DATABASE_SERVER && $arrsdata["host_dbname"]==DATABASE_WEIBOINFO) {
                $hasitem = true;
                $resultdb = array();
                $resultdb["datalist"] = array(array("dbserver" => DATABASE_SERVER));
                $r["datalist"]["dbserver"] = $resultdb["datalist"];
                $r["defaulthostservername"] = true;
            } else {
                //ZUOQIAN
                $resultdb = getDatahostList(NULL, NULL, NULL, $arrsdata["host_dbserver"], $arrsdata["host_dbname"], NULL, NULL, NULL, $host_id, $get_type);
                if (isset($resultdb["totalcount"]) && $resultdb["totalcount"] > 0) {
                    $hasitem = true;
                    $r["datalist"]["dbserver"] = $resultdb["datalist"];
                }
            }
        }
        if (isset($arrsdata["host_solrstore"])) {
            if ($arrsdata["host_solrstore"] == SOLR_STORE) {
                $hasitem = true;
                $resulta = array();
                $resulta["datalist"] = array(array("solrstore" => SOLR_STORE));
                $r["datalist"]["solrstore"] = $resulta["datalist"];
                $r["defaulthost"] = true;
            } else {
                $result = getDatahostList(NULL, NULL, NULL, NULL, NULL,$arrsdata["host_solrstore"], NULL, NULL, $host_id, $get_type);
                if (isset($result["totalcount"]) && $result["totalcount"] > 0) {
                    $hasitem = true;
                    $r["datalist"]["solrstore"] = $result["datalist"];
                }
            }
        }
        $r["flag"] = $hasitem ? 1 : 0;
        echo json_encode($r);
    }
}
