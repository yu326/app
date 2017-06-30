<?php
define('INCLUDES_DIR', 'D:/app/php_common_includes');
define('CONFIG_DIR', 'D:/app/php_config');
//日志配置
define("LOGNAME_WEBAPI", "webapi");//不同的模块对应的日志名，使用 Logger::getLogger(LOGNAME_WEBAPI)获取对应的日志对象
define("LOGNAME_SYNC", "sync");
define("LOGNAME_CRAWLING", "crawling");//抓微博
define("LOGNAME_IMPORTWEIBOURL", "import_weibo_url");//植入微博任务
define("LOGNAME_IMPORTUSERID", "import_user_id");//植入用户任务
define("LOGNAME_REPOST_TREND", "repost_trend");//处理转发
define("LOGNAME_STATUSESCOUNT", "statusescount");//更新转发评论数
define("LOGNAME_GETCOMMENT", "getcomment");//抓取评论
define("LOGNAME_SCHEDULER", "scheduler");//定时调度
define("LOGNAME_MIGRATEDATA", "migrate_data");//迁移数据
define("LOGNAME_UPDATESNAPSHOT", "update_snapshot");//更新快照
define("LOGNAME_GETUPDATE", "getupdate");//更新数据
define("LOGNAME_MONITORING", "monitoring");//监控账号微博
define("LOGNAME_DATAPUSH", "datapush");//订阅微博
define("LOGNAME_HISTORYEMAIL", "historyemail");//发送邮件
define("LOGNAME_UPDATEMYSQL", "updatemysql");//更新mysql表
define("LOGNAME_DATAPUSHUSER", "datapushuser");//更新mysql表
define("LOGNAME_TEST", "test");//测试日志
define("LOGNAME_MONITORTASK", "monitor_task");//监控任务



define('LOG4PHP_DIR', INCLUDES_DIR . '/log4php');//日志控件地址
require_once(LOG4PHP_DIR . "/Logger.php");
Logger::configure(CONFIG_DIR . '/log4php.xml');

//----URL相关配置--------
//注释某配置，或将某配置的值置空，表示禁用该功能
define('HOSTNAME_TENANT','.3i.inter3i.com');//租户平台主机 ，将二级域名解析为租户代码
////define('HOSTNAME_PLATFORM','168.0.30:83');//数据平台主机
//define('PATHHOST_TENANT','www.a.com:83');//租户平台域名，将后缀解析为租户代码
//define('PATHHOST_PLATFORM','www.a.com:83');//数据平台域名，将后缀解析为租户代码
//-----------------------

//define('DATABASE_SERVER', 'localhost:/home/3iapp/data/sqldb/mysql/mysql.sock');
define('DATABASE_SERVER', 'localhost:3306');    //数据库server
define('DATABASE_USERNAME', 'root');    //用户名
define('DATABASE_PASSWORD', 'root');    //密码
//define('DATABASE_SERVER', '192.168.0.30:3306');    //数据库server
//define('DATABASE_USERNAME', 'root');    //用户名
//define('DATABASE_PASSWORD', 'inter3i.com');    //密码

define('SOLR_PARAM_SELECT', "select");//solr查询
define('SOLR_PARAM_KWBLUR', "kwblur");//solr模糊查询
define('SOLR_PARAM_KWGROUP', "kwgroup");//solr组合关键词查询
define('SOLR_PARAM_KWTOKEN', "kwtoken");//solr分词查询
define('SOLR_PARAM_UPDATE', "update/?type=update");//solr更新
define('SOLR_PARAM_INSERT', "update/?type=insert");//solr新增
define('SOLR_PARAM_DELETE', "update/?type=delete");//solr删除
define('SOLR_PARAM_RETRIEVE', "retrieve");//solr提取数据
define('SOLR_PARAM_DICTIONARY', "dictionary");//solr字典
define('SOLR_PARAM_ANALYSIS', "analysis");//solrNLP的分析

//**************************************** solr 多实例**********************************//
//the maaping of httpserverPort <------->  solr host
//$solrHosts = array('solrstore' => 'http://192.168.0.104:8080/solrstore/', 'solrstore_eb' => 'http://192.168.0.104:8080/solrstore/');
//$solrHosts = array('solrstore' => 'http://114.55.148.22:8080/solrstore/', 'solrstore_eb' => 'http://192.168.0.151:9000/solrstore/');//微博solr

$solrHosts = array('solrstore' => 'http://192.168.0.151:9000/solrstore/', 'solrstore_eb' => 'http://192.168.0.151:9000/solrstore/');//151的solr
//$solrHosts = array('solrstore' => 'http://192.168.0.10:8080/solrstore/', 'solrstore_eb' => 'http://192.168.0.151:9000/solrstore/');//151的solr
$solrHosts = json_encode($solrHosts);
define('SOLR_STORES', $solrHosts);
$srvPortSolrHostMap = array('83' => "solrstore", "8081" => "solrstore", '8090' => 'solrstore_eb', '8071' => 'solrstore_eb', '8061' => 'solrstore_eb');
$srvPortSolrHostMap = json_encode($srvPortSolrHostMap);
define('HOST_SOLR_MAPPING', $srvPortSolrHostMap);
$hostSolrMapping = json_decode(HOST_SOLR_MAPPING, true);
$solrHosts = json_decode(SOLR_STORES, true);

//the maaping of httpserverPort <------->  dataDabaBase
$db_data_names = array('weibo_info_2' => 'weibo_info_2', 'weibo_info_eb' => 'weibo_info_eb', 'weibo_info_for_wanli' => 'weibo_eb_wl');
$db_data_names = json_encode($db_data_names);
define('DB_DATA_NAMES', $db_data_names);
$srvPortDataDBMap = array('83' => "weibo_info_2", '8081' => 'weibo_info_2', '8090' => "weibo_info_eb", '8071' => 'weibo_info_eb', '8061' => 'weibo_info_for_wanli');
$srvPortDataDBMap = json_encode($srvPortDataDBMap);
define('HOST_DB_DATA_MAPPING', $srvPortDataDBMap);
$dbDataNames = json_decode(DB_DATA_NAMES, true);
$hostDBNameDataMapping = json_decode(HOST_DB_DATA_MAPPING, true);


//the maaping of httpserverPort <------->  adminDabaBase
$db_admin_names = array('3a' => '3a', '3a_eb' => '3a_eb', '3a_for_wanli' => '3a_eb_wl');
$db_admin_names = json_encode($db_admin_names);
define('DB_ADMIN_NAMES', $db_admin_names);
$srvPortAdminDBMap = array('83' => "3a", '8081' => '3a', '8090' => "3a_eb", '8071' => '3a_eb', '8061' => '3a_for_wanli');
$srvPortAdminDBMap = json_encode($srvPortAdminDBMap);
define('HOST_DB_ADMIN_MAPPING', $srvPortAdminDBMap);
$hostDBNameAdminMapping = json_decode(HOST_DB_ADMIN_MAPPING, true);
$dbAdminNames = json_decode(DB_ADMIN_NAMES, true);

function getCurrrentSrvAddress()
{
    //使用代理时会有HTTP_X_FORWARDED_HOST
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $serverhost = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } else if (isset($_SERVER['HTTP_HOST'])) {
        $serverhost = $_SERVER['HTTP_HOST'];
    } else {
        if (!isset($GLOBALS['hostName'])) {
            throw new Exception("param name:[hostName] not set for globals!");
        }
        $serverhost = $GLOBALS['hostName'];
    }
//    $serverhostarr = explode(":", trim($serverhost));
//    if (isset($serverhostarr[1])) {
//        $serverport = $serverhostarr[1];
//    } else {
//        $serverport = "80";
//    }
    return $serverhost;
}



/**
 * 获取当前网站监听的端口(区分不同的应用实例：汽车之家[8081] 电商[8082])
 * @return array
 */
function getCurrrentSrvPort()
{
    //使用代理时会有HTTP_X_FORWARDED_HOST
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $serverhost = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } else if (isset($_SERVER['HTTP_HOST'])) {
        $serverhost = $_SERVER['HTTP_HOST'];
    } else {
        if (!isset($GLOBALS['hostName'])) {
            throw new Exception("param name:[hostName] not set for globals!");
        }
        $serverhost = $GLOBALS['hostName'];
    }
    $serverhostarr = explode(":", trim($serverhost));
    if (isset($serverhostarr[1])) {
        $serverport = $serverhostarr[1];
    } else {
        $serverport = "80";
    }
    return $serverport;
}


function getCurrrentSrvHost()
{
    //使用代理时会有HTTP_X_FORWARDED_HOST
//    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
//        $serverhost = $_SERVER['HTTP_X_FORWARDED_HOST'];
//    } else if (isset($_SERVER['HTTP_HOST'])) {
//        $serverhost = $_SERVER['HTTP_HOST'];
//    } else {
//        if (!isset($GLOBALS['hostName'])) {
//            throw new Exception("param name:[hostName] not set for globals!");
//        }
//        $serverhost = $GLOBALS['hostName'];
//    }
    $serverhost = getCurrrentSrvAddress();
    if (!isset($serverhost)) {
        throw new Exception("get currrent web server address excption, serverhost is null. serverhost:[" . var_export($serverhost, true) . "].");
    }

    if(!strstr($serverhost,":"))
    {
        throw new Exception("get currrent web server address excption, serverhost is not valid. serverhost:[" . var_export($serverhost, true) . "].");
    }
    $serverhostarr = explode(":", trim($serverhost));
    if (isset($serverhostarr[0])) {
        $serverport = $serverhostarr[0];
    } else {
        $serverport = "127.0.0.1";
    }
    return $serverport;
}


//
function getSolrHostForCurSrv()
{
    global $hostSolrMapping, $solrHosts;
    //$logger->debug(__FILE__ . __LINE__ . __FUNCTION__." getSolrHostForCurSrv ...");

    $serverPort = getCurrrentSrvPort();
    if (!is_string($serverPort)) {
        $serverPort = strval($serverPort);
    }
    //$logger->debug(__FILE__ . __LINE__ . __FUNCTION__." getCurrentServer port success:[".$serverPort."].");

    //$hostSolrMapping =  HOST_SOLR_MAPPING;
    //$hostSolrMapping = json_decode(HOST_SOLR_MAPPING,true);
    //$logger->debug(__FILE__ . __LINE__ . __FUNCTION__." all param constants: ".var_export($hostSolrMapping, true));
    if (isset($hostSolrMapping[$serverPort])) {
        $hostKey = $hostSolrMapping[$serverPort];
        //$logger->debug(__FILE__ . __LINE__ . __FUNCTION__." getSolrHostForCurSrv key success:[".$serverPort."] hostkey:[{$hostKey}]");
        //$solrHosts = json_decode(SOLR_STORES,true);
        if (isset($solrHosts[$hostKey])) {
            return $solrHosts[$hostKey];
        } else {
            return "solrHost for key:[{$hostKey}] is not set!";
        }
    } else {
        return "solrHostKey for port:[{$serverPort}] is not set!";
    }
}

/**
 * 根据服务器实例监听的端口号,动态获取当前数据库名(data)
 * @return string
 */
function getDBDataName()
{
    global $hostDBNameDataMapping, $dbDataNames;

    $serverPort = getCurrrentSrvPort();
    if (!is_string($serverPort)) {
        $serverPort = strval($serverPort);
    }
    if (isset($hostDBNameDataMapping[$serverPort])) {
        $dataNameKey = $hostDBNameDataMapping[$serverPort];
        if (isset($dbDataNames[$dataNameKey])) {
            return $dbDataNames[$dataNameKey];
        } else {
            return "DBDataName for key:[{$dataNameKey}] is not set!";
        }
    } else {
        return "DBNameData key for port:[{$serverPort}] is not set!";
    }
}


function getDBAdminName()
{
    global $hostDBNameAdminMapping, $dbAdminNames;

    $serverPort = getCurrrentSrvPort();
    if (!is_string($serverPort)) {
        $serverPort = strval($serverPort);
    }
    if (isset($hostDBNameAdminMapping[$serverPort])) {
        $adminNameKey = $hostDBNameAdminMapping[$serverPort];
        if (isset($dbAdminNames[$adminNameKey])) {
            return $dbAdminNames[$adminNameKey];
        } else {
            return "DBAdminName for key:[{$adminNameKey}] is not set!";
        }
    } else {
        return "DBNameAdmin key for port:[{$serverPort}] is not set!";
    }
}

//**************************************** solr 多实例**********************************//

//define('SOLR_URL',SOLR_STORE);//旧版solr地址

//define('SOLR_ANALYSIS', 'http://192.168.0.122:8070/solrNLP/');//新版solr地址     // 超哥的solr
//define('SOLR_ANALYSIS', 'http://127.0.0.1:8080/solrNLP/');//新版solr地址    //151的solr
define('SOLR_ANALYSIS', 'http://192.168.0.151:8070/solrNLP/');

define('SOLR_URL_DICTIONARY', SOLR_ANALYSIS . SOLR_PARAM_DICTIONARY);//solr字典地址
define('SOLR_URL_ANALYSIS', SOLR_ANALYSIS . SOLR_PARAM_ANALYSIS);//solrNLP的分析地址

//define('SOLR_STORE','http://localhost:8080/solrstore/');//新版solr地址
//define('SOLR_URL_SELECT',SOLR_STORE.SOLR_PARAM_SELECT);//solr查询地址
//define('SOLR_URL_KWBLUR',SOLR_STORE.SOLR_PARAM_KWBLUR);//solr模糊查询地址
//define('SOLR_URL_KWGROUP',SOLR_STORE.SOLR_PARAM_KWGROUP);//solr组合关键词查询地址
//define('SOLR_URL_KWTOKEN',SOLR_STORE.SOLR_PARAM_KWTOKEN);//solr分词查询地址
//define('SOLR_URL_UPDATE',SOLR_STORE.SOLR_PARAM_UPDATE);//solr更新地址
//define('SOLR_URL_INSERT',SOLR_STORE.SOLR_PARAM_INSERT);//solr新增地址
//define('SOLR_URL_DELETE',SOLR_STORE.SOLR_PARAM_DELETE);//solr删除地址
//define('SOLR_URL_RETRIEVE',SOLR_STORE.SOLR_PARAM_RETRIEVE);//solr提取数据地址

//根据监听端口，动态获取solr地址
$solrHost = getSolrHostForCurSrv();
define('SOLR_STORE', $solrHost);//新版solr地址
define('SOLR_URL_SELECT', SOLR_STORE . SOLR_PARAM_SELECT);//solr查询地址
define('SOLR_URL_KWBLUR', SOLR_STORE . SOLR_PARAM_KWBLUR);//solr模糊查询地址
define('SOLR_URL_KWGROUP', SOLR_STORE . SOLR_PARAM_KWGROUP);//solr组合关键词查询地址
define('SOLR_URL_KWTOKEN', SOLR_STORE . SOLR_PARAM_KWTOKEN);//solr分词查询地址
define('SOLR_URL_UPDATE', SOLR_STORE . SOLR_PARAM_UPDATE);//solr更新地址
define('SOLR_URL_INSERT', SOLR_STORE . SOLR_PARAM_INSERT);//solr新增地址
define('SOLR_URL_DELETE', SOLR_STORE . SOLR_PARAM_DELETE);//solr删除地址
define('SOLR_URL_RETRIEVE', SOLR_STORE . SOLR_PARAM_RETRIEVE);//solr提取数据地址


//根据监听端口，动态获取数据库名称-管理
$dbNamesAdmin = getDBAdminName();
define('DATABASE_NAME', $dbNamesAdmin);

//根据监听端口，动态获取数据库名称-数据
$dbNamesData = getDBDataName();
define('DATABASE_WEIBOINFO', $dbNamesData);


//旧脚本用到的地址
define('SOLRURL_ANALYSIS', 'http://localhost:8080/solrmain/update/json?analysisType=reAnalysis');
define('SOLRURL_CRAWL', 'http://localhost:8080/solrmain/update/json?analysisType=crawl');

define('SERVER_MACHINE', 'testsvr30');

//define('SPIDER_IMPORT_URL','http://wangcc:8081/sysadmin/');//爬虫提交数据地址

//define('ALARM_MAIL_HOST', 'smtp.126.com'); //您的企业邮局域名
//define('ALARM_MAIL_HOST_PORT', 25);
//define('ALARM_MAIL_HOST_USERNAME', 'i2382157507@126.com,i2382157508@126.com,i2382157509@126.com,i2382157510@126.com'); // 邮局用户名(请填写完整的email地址)
//define('ALARM_MAIL_HOST_PASSWORD', 'bxt123321'); // 邮局密码
//define('ALARM_MAIL_FROM', 'i2382157507@126.com,i2382157508@126.com,i2382157509@126.com,i2382157510@126.com'); //告警邮件发送方,需要和邮局用户名相同

/*
define('ALARM_MAIL_HOST', 'smtp.qq.com'); //您的企业邮局域名
define('ALARM_MAIL_HOST_PORT', 25);
define('ALARM_MAIL_HOST_USERNAME', '291432298@qq.com'); // 邮局用户名(请填写完整的email地址)
define('ALARM_MAIL_HOST_PASSWORD', 'inter3i.mail'); // 邮局密码
define('ALARM_MAIL_FROM', '291432298@qq.com'); //告警邮件发送方,需要和邮局用户名相同

define('ALARM_MAIL_HOST', 'smtp.126.com'); //您的企业邮局域名
define('ALARM_MAIL_HOST_PORT', 25);
define('ALARM_MAIL_HOST_USERNAME', 'i2382157507@126.com'); // 邮局用户名(请填写完整的email地址)
define('ALARM_MAIL_HOST_PASSWORD', 'inter3i'); // 邮局密码
define('ALARM_MAIL_FROM', 'i2382157507@126.com'); //告警邮件发送方,需要和邮局用户名相同

 */
//define('ALARM_MAIL_FROMNAME', '博晓通'); //告警邮件发送方名称, 可以为空

//define('SYSTEM_TITLE', '博晓通');


//设置发送邮件常量
define('API_USER',"suixin_yu");
define('API_KEY',"AFrnPZ7Fm4Ve4FVu");

define('ALARM_MAIL_FROM', 'service-no-reply@inter3i.com');
define('ALARM_MAIL_FROMNAME', '博晓通服务');

//设置推给舆情平台的url
//define('YQ_URL', '101.201.45.176');
define('YQ_URL', '192.168.0.108');




