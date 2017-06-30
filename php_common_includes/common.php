<?php
/*
 * 脚本运行时出现如下错误
 * Fatal error: Allowed memory size of 8388608 bytes exhausted (tried to allocate 793263 bytes) in D:\ajax\test\wamp\www\weibo_zq\weibooauth.php on line 2507
 * 分配的内存不足，导致该错误的产生
 */
ini_set("memory_limit", "888M");
/*
 * php.ini里有一个precision的设置，默认是14，影响float的精度设置。由于正常的unsigned int64的位数是20位，所以，把这个数字设置到20以上，就可以保证int64的正常处理
 */
ini_set('precision', 20);
date_default_timezone_set('Asia/Shanghai');

include_once('db_mysql.class.php');
include_once("class.phpmailer.php");
include_once("SegementHelper.php");
define("MEMORY_RESERVED", 7340032);//保留内存 byte,  7M.其中有5M不可分配
//记录日志时使用，表示日志类型
define("NORMAL", "normal");
define("WARNING", "warning");
define("DEBUG", "debug");
define("ERROR", "error");
define("FATAL", "fatal");

define('TOKENKEY', 'inter3i.com');//TOKEN加密key
define('TOKENEXPIRY', 0);//TOKEN有效期，单位秒。0表示永久

define('ENCRYPT_KEY', 'inter3i');//密码加密key

define('TIMELIMIT_UPDATEUSER', 86400);//用户上次更新时间比当前时间早于此值时，更新
define('TIMELIMIT_UPDATEWEIBO', 86400);//微博上次更新时间比当前时间早于此值时，
define('ADMIN_MAXIMPORT_COUNT', 50);//后台植入微博，资源不够时，指定每多少条生成一个任务
define('ADMIN_IMPORTCOMMIT_INTERVAL', 100);//后台植入微博，多少条commit一次
//最多可分类查询/对比的值个数
define("MAX_CLASSIFYQUERYCOUNT", 20);
//版本号
define("VERSION", 1067);
//定义抓取任务场景
define('SCENE_NORMAL', 1);
define('SCENE_WAITCONCURRENT_MACHINE', 2);//等待机器资源
define('SCENE_WAITCONCURRENT_IP', 3);//等待并发IP资源
define('SCENE_WAITSPIDER_IP', 4);//等待IP抓取资源
define('SCENE_WAITCONCURRENT_ACCOUNT', 5);//等待帐号抓取资源
define('SCENE_WAITSPIDER_ACCOUNT', 6);//等待帐号抓取资源

//决定分词时使用哪些词典
define('TOKENIZE_DICTTYPE_ALL', 1);//全词典
define('TOKENIZE_DICTTYPE_NOEB', 2);//不包含情感和行业特征

//表情级别定义
define('VAL_EMO_LEVEL', '5');
define('VAL_EMO_LEVEL_CODE_1', '1');
define('VAL_EMO_LEVEL_NAME_1', '反对');
define('VAL_EMO_LEVEL_CODE_2', '2');
define('VAL_EMO_LEVEL_NAME_2', '负面');
define('VAL_EMO_LEVEL_CODE_3', '3');
define('VAL_EMO_LEVEL_NAME_3', '中性');
define('VAL_EMO_LEVEL_CODE_4', '4');
define('VAL_EMO_LEVEL_NAME_4', '正面');
define('VAL_EMO_LEVEL_CODE_5', '5');
define('VAL_EMO_LEVEL_NAME_5', '赞赏');
//web返回错误
define('WEBERROR_CURLERROR', 1001); //未知错误
define('WEBERROR_TIMEOUT', 1002); //请求超时
define('WEBERROR_SOLRERROR', 1003); //请求超时
define('WEBERROR_NOSESSION', 'nosession');

//微博API的返回错误
define('ERROR_IP_OUT_LIMIT', 1);
define('ERROR_USER_OUT_LIMIT', 2);
define('ERROR_LOGIN', 3);
define('ERROR_CONTENT_NOT_EXIST', 4);
define('ERROR_TOKEN', 5);
define('ERROR_OTHER', 6);
define('ERROR_CONTENT_PRIVATE', 7);//微博已被官方屏蔽 对应sina错误码20112
define('ERROR_USER_NOT_EXIST', 8);//20003 用户不存在

//组策略类型
define('POLICY_TYPE_APP', 1);//对某个应用的限制
define('POLICY_TYPE_SOURCE', 2);//对具体源的限制
define('POLICY_TYPE_SOURCETYPE', 3);//对某类源的限制

//定义任务类型
define("TASKTYPE_ANALYSIS", 1);
define("TASKTYPE_SPIDER", 2);
define("TASKTYPE_MIGRATE", 3);
define("TASKTYPE_UPDATE", 4);
//定义任务
define("TASK_BRIDGEUSER", 1);//分析桥接用户
define("TASK_BRIDGECASE", 2);//分析桥接案例
define("TASK_SYNC", 3);//重新分析
define("TASK_WEIBO", 4);//抓取微博
define("TASK_REPOST_TREND", 5);//处理转发
define("TASK_STATUSES_COUNT", 6);//更新微博转发数
define("TASK_COMMENTS", 7);//抓取评论
define("TASK_IMPORTWEIBOURL", 8);//批量植入微博
define("TASK_IMPORTUSERID", 9);//批量植入用户
define("TASK_KEYWORD", 10);//抓取关键词
define("TASK_FRIEND", 11);//抓取关注
define("TASK_MIGRATEDATA", 12);//迁移数据
define("TASK_SNAPSHOT", 13);//更新快照
define("TASK_EVENTALERT", 14);//事件预警
define("TASK_WEBPAGE", 15);//根据url抓取内容
define("TASK_REPOSTPATH", 16);//根据url,计算转发轨迹
define("TASK_COMMENTPATH", 17);//根据url,计算评论轨迹
define("TASK_NICKNAME", 18);//监控账号微博
define("TASK_DATAPUSH", 19);//监控账号微博

//***********通用任务类型***********add by wangcc
define("TASK_COMMON", 20);//通用任务

//任务内容类型
//内容类型1.文章列表2.文章详情3.用户详情4.文章列表＋文章详情＋用户详情
define("TASK_PAGESTYLE_ARTICLELIST", 1);//1.文章列表
define("TASK_PAGESTYLE_ARTICLEDETAIL", 2);//2.文章详情
define("TASK_PAGESTYLE_USERDETAIL", 3);//3.用户详情
define("TASK_PAGESTYLE_ALL", 4);//4.文章列表＋文章详情＋用户详情
//搜索引擎
define("SEARCHENGINE_BAIDU", 1);
//抓取站点
define("SEARCHSITE_AUTOHOME", 1);
//定义资源类型
define("RESOURCE_TYPE_MACHINE", 1);//机器
define("RESOURCE_TYPE_IP", 2);//ip
define("RESOURCE_TYPE_ACCOUNT", 3);//帐号
//定义资源用途
define('USETYPE_CONCURRENT', 1);//并发使用
define('USETYPE_SPIDER', 2);//抓取

//数据的分析状态
define('ANALYSIS_STATUS_NORMAL', 0);//正常
define('ANALYSIS_STATUS_NEEDORG', 1);//需要原创
define('ANALYSIS_STATUS_ORGNOTEXIST', 2);//原创已删除
define('ANALYSIS_STATUS_ORGPRIVATE', 3);//原创不适宜公开
define('ANALYSIS_STATUS_OTHERERROR', 4);//其他错误
//图片类型
define('PICTURE_TYPE_BIG', 'big');//大图
define('PICTURE_TYPE_MIDDLE', 'middle');//中图
define('PICTURE_TYPE_SMALL', 'small');//小图
//数据更新类型
define('UPDATE_ACTION_NONE', 0);//无操作
define('UPDATE_ACTION_INSONLY', 1);//只插入
define('UPDATE_ACTION_UPDONLY', 2);//只更新
define('UPDATE_ACTION_INSUPD', 3);//插入或更新
define('UPDATE_ACTION_FORCE', 4);//强制更新

//样式类型, 对于微博,论坛,SNS
define('STYLETYPE_1', 1);
define('STYLETYPE_2', 2);
define('STYLETYPE_3', 3);

//时间重复方式
define('CRONTIME_REPEAT_ONCE', 0);
define('CRONTIME_REPEAT_MINUTELY', 1);
define('CRONTIME_REPEAT_HOURLY', 2);
define('CRONTIME_REPEAT_DAILY', 3);
define('CRONTIME_REPEAT_WEEKLY', 4);
define('CRONTIME_REPEAT_WORKDAY', 5);
define('CRONTIME_REPEAT_MONTHLY', 6);
define('CRONTIME_REPEAT_MONTHWEEK', 7);
define('CRONTIME_REPEAT_YEARLY', 8);

//数据分类 --> 用于趋势分析 $rec['dataClsfct'] == "trendAnalysis"
define('DATA_CLASSIFY_TRENDANALYSIS', 'trendAnalysis');

//趋势分析数据更新频率 - 按天
define('TREND_ANA_FREQ_DAY', 'day');
//趋势分析数据更新频率 -按月
define('TREND_ANA_FREQ_MON', 'mon');
//趋势分析数据更新频率 -按周
define('TREND_ANA_FREQ_WEEK', 'week');
//趋势分析数据更新频率 -按年
define('TREND_ANA_FREQ_YEAR', 'year');

define('DEFAULT_HTTP_TIMEOUT', 5);

//solr的整型范围
define('SOLR_MAX_INT', 2147483647);
define('SOLR_MIN_INT', -2147483647);

define('SOLR_NLP_TIME_KEY', "SOLR_NLP_TMIE");

define('SOLR_SELECT_TIME_KEY', "SOLR_S_TMIE");
define('SOLR_UPDATE_TIME_KEY', "SOLR_U_TMIE");
define('SOLR_INSERT_TIME_KEY', "SOLR_I_TMIE");
define('SOLR_DELET_TIME_KEY', "SOLR_D_TMIE");

define('DB_SELECT_TIME_KEY', "DB_S_TMIE");
define('DB_UPDATE_TIME_KEY', "DB_U_TMIE");
define('DB_INSERT_TIME_KEY', "DB_I_TMIE");
define('DB_DELET_TIME_KEY', "DB_D_TMIE");

define('DATA_HANDLE', "DATA_HANDLE"); //方法总时间
define('FETCH_USER', "FETCH_USER"); //提取用户时间
define('HANDLE_USER_SUM', "HANDLE_USER_SUM"); //处理用户时间
define('INSER_DELETED_WEIBO', "INSER_DELETED_WEIBO"); //插入已经删除状态的微博

define('HANDLE_ORG_DOC', "HANDLE_ORG_DOC"); //处理原创文章总时间
define('HANDLE_CMT_DOC', "HANDLE_CMT_DOC");//处理评论/转发文章总时间
define('HANDLE_UPDT_DOC', "HANDLE_UPDT_DOC");//处理需要更新的文章总时间
define('HANDLE_DEL_DOC', "HANDLE_DEL_DOC");//处理需要删除的文章总时间
define('HANDLE_SPL_GUID', "HANDLE_SPL_GUID");//补充所有guid的时间



$config;    //数据库中配置的数据
$task;    //数据库中的任务信息
$statistics_info = (object)array();    //提交数据统计信息
$test_users;    //数据库中的测试用户
$test_user_count;    //测试用户的个数
//分词方案
$dictionaryPlan = "";
//任务id用来判断 任务id不同就从数据库取新方案
$taskID = -1;

/*
 * test_user_current变量用来记录当前正在使用的用户序号
 * 在某一个小时内，如果一个用户的api访问次数达到限制以
 * 后，需要切换用户，该变量表示切换用户在$test_users数组
 * 中对应的位置
 * 初始值为0
 * now：
 *  由于运行多个脚本时，需要使用不同的测试用户，所以
 *  允许脚本在include comment.php之前，定义一个常量
 *  TEST_USER_CURRENT，设置使用的测试用户，如果不设置，
 *  则默认为0
 */
if (defined('TEST_USER_CURRENT')) {
    $test_user_current = TEST_USER_CURRENT;
} else {
    $test_user_current = 0;
}

$api_access_count_current = 0;    //当前用户访问api次数，初始值为0
$start_hour_count = 0;    //某小时内，第一次访问api的小时数，通过时间戳计算
$current_hour_count;    //当前访问api的小时数，通过时间戳计算
$test_user_rest;    //在某小时内，可以使用的测试用户的数量，初始值为$test_user_count
$total_sql_execute_time = 0;    //操作数据库的总时间
$total_api_execute_time = 0;    //调用api的总时间
$total_script_execute_time = 0;    //脚本执行的总时间
$max_sql_execute_time = 0;    //每次执行数据库操作的最长时间
$min_sql_execute_time = 0;    //每次执行数据库操作的最短时间
$max_api_execute_time = 0;    //每次调用api操作的最长时间
$min_api_execute_time = 0;    //每次调用api操作的最短时间
$total_api_execute_count = 0;    //调用api的总数
$total_sql_execute_count = 0;    //执行数据库操作的总数
/*
 * 2012-1-8:
 * 为了统计每一种类型的sql或api调用的
 * 最大时间，最小时间，平均时间，和本次
 * 时间
 */
$take_notes_sql_arr = array();
$take_notes_api_arr = array();
//在handle_take_notes_arr中使用
define('TAKE_NOTES_SQL_SELECT', 'select');
define('TAKE_NOTES_SQL_INSERT', 'insert');
define('TAKE_NOTES_SQL_DELETE', 'delete');
define('TAKE_NOTES_SQL_UPDATE', 'update');
define('TAKE_NOTES_API_USER_TIMELINE', 'user_timeline');
define('TAKE_NOTES_API_GET_COUNT_INFO_BY_IDS', 'get_count_info_by_ids');
define('TAKE_NOTES_API_SHOW_USER', 'show_user');
define('TAKE_NOTES_API_SHOW_STATUS', 'show_status');

define('RET_SEND_SOLR_RESPONSEhEADER', 'responseHeader');
define('RET_SEND_SOLR_STATISTICS', 'statistics');
define('RET_SEND_SOLR_QTIME', 'QTime');
define('RET_SEND_SOLR_DOCCOUNT', 'docCount');
define('RET_SEND_SOLR_STATUS', 'status');
define('RET_SEND_SOLR_IDS', 'ids');
define('RET_TO_SOLR_ANCESTOR_TEXT', 'ancestor_text');
define('RET_TO_SOLR_FLAG', 'flag');
define('RET_TO_SOLR_FIRST', '1');
define('RET_TO_SOLR_SECOND', '2');

$log_file;    //日志文件名
$log_dir;    //日志文件路径


$link;    //数据库连接实例
$db;    //选定数据库

//验证用户需要使用到的变量
$oAuth;
$requestToken;
$postfields;
$oAuthRequest;
$oAuthSecond;
$accessToken;
$oAuthThird;
$oAuthThirdBiz;

//记录总的出错数量
$total_error = 0;
$total_rate_limit_error = 0;

$logger;//记录日志的对象
//solr中文章字段  add by wang_cc 新增的字段需要在这里添加
$solr_article_tags = array('guid', 'docguid', 'retweeted_guid', 'sourceid', 'level', 'floor', 'father_floor', 'paragraphid',
    'read_count', 'recommended', 'page_url', 'column', 'column1', 'post_title', 'id', 'created_at', 'created_year', 'created_month',
    'created_day', 'created_hour', 'created_weekday', 'text', 'originalText', 'source', 'thumbnail_pic', 'bmiddle_pic', 'original_pic',
    'userid', 'retweeted_status', 'timeline_type', 'geo_type', 'geo_coordinates_x', 'geo_coordinates_y', 'annotations',
    'screen_name', 'reposts_count', 'content_type', 'country_code', 'province_code', 'city_code', 'district_code',
    'comments_count', 'total_reposts_count', 'direct_reposts_count', 'register_time', 'verify', 'sex',
    'followers_count', 'repost_trend_cursor', 'total_reach_count', 'father_guid', 'direct_comments_count', 'praises_count', 'has_picture', 'verified_reason', 'verified_type', 'description', 'mid', 'retweeted_mid', 'userguid', 'analysis_status', 'pg_text', 'source_host', 'original_url', 'quote_father_mid', 'reply_father_floor', 'reply_father_mid',
    'child_post_id', 'question_id', 'answer_id', 'answer_father_id', 'question_father_id', 'trample_count',
    'satisfaction', 'godRepPer', 'midRepPer', 'wosRepPer', 'godRepNum', 'midRepNum', 'wosRepNum', 'apdRepNum', 'showPicNum', 'cmtStarLevel',
    'isNewPro', 'proClassify', 'proPic', 'proOriPrice', 'proCurPrice', 'proPriPrice', 'promotionInfos', 'productFullName', 'productColor', 'productSize', 'productDesc', 'productComb', 'detailParam', 'stockNum', 'salesNumMonth', 'compName', 'compAddress', 'phoneNum', 'operateTime', 'compURL', 'serviceProm', 'logisticsInfo', 'payMethod', 'compDesMatch', 'logisticsScore', 'serviceScore', 'serviceComment', 'apdComment', 'isFavorite', 'isAttention',
    'purchDate', 'productType', 'impress', 'commentTags',
    'dataClsfct', 'usersAge', 'workTimeLong', 'worksNum', 'eduBackGro', 'userPhoneNum', 'nowLocation', 'professionName', 'position', 'email', 'isMarried', 'nationality', 'credentials', 'politicalStatus', 'foreignExper', 'workStatus', 'curPay', 'homePhoneNum', 'height', 'zipCode', 'weiXinNum', 'qqNum', 'homeLocation', 'expectWorkLct', 'expectYearPay', 'expectPay', 'curYearPay', 'expWorkQuality', 'expectProfession', 'expectTrades', 'expCmpQuality',
    'isMpRcm', 'compQuality', 'compScale', 'department', 'leader', 'underlingNum', 'professionType', 'certifier', 'techName', 'RRAbility', 'HSAbility', 'proficiency', 'certificate', 'certOrg', 'projName', 'curPayMax', 'nedNum', 'workTimeLongMax', 'area',
    'apperScore', 'decorateScore', 'perfPriScale', 'attachNum', 'startTimeStr', 'endTimeStr', 'attachName', 'integral', 'subj_article_count', 'article_count', 'gold_count', 'favour_count', 'signin_days', 'conti_signin_days', 'integral_beans',
    'trading_count_m', 'save_time',
    'NRN', 'emoNRN', 'province', 'city', 'country', 'district', 'emoProvince', 'emoCity', 'emoCountry', 'emoDistrict', 'business', 'emoBusiness', 'combinWord', 'emoCombin', 'organization', 'emoOrganization', 'emotion', 'wb_topic', 'emoTopic', 'wb_topic_keyword', 'emoTopicKeyword', 'wb_topic_combinWord', 'emoTopicCombinWord', 'account', 'emoAccount', 'host_domain', 'url'
);

//比schema缺少的有：
//stored是false的：'friends_count', (pg_text虽然也是不stored，但是它是自己分析的来源)
//以后不要的字段:'status_mid','retweeted_userid','retweeted_description','retweeted_verified_reason','retweeted_text','retweeted_created_at','retweeted_verify','retweeted_screen_name','retweeted_source','retweeted_comments_count','retweeted_reposts_count','retweeted_followers_count'
//分词：'similar'
//text分词出来的字段:'NRN','emoNRN','province','city','country','district','emoProvince','emoCity','emoCountry','emoDistrict','business','emoBusiness', 'combinWord','emoCombin',	'organization','emoOrganization','emotion','wb_topic','emoTopic','wb_topic_keyword','emoTopicKeyword','wb_topic_combinWord','emoTopicCombinWord','account','emoAccount','host_domain','url'
//ancestor的字段
//数据库中文章表weibo_new的字段，注意最后有几个updatetime
$sql_article_tags = array('guid', 'id', 'mid', 'created_at', 'year', 'month', 'day', 'hour', 'minute', 'second', 'retweeted_status', 'retweeted_mid', 'father_guid', 'userid', 'sourceid', 'annotations',
    'is_repost', 'isseed', 'is_bridge_status', 'reposts_count', 'total_reposts_count', 'direct_reposts_count', 'comments_count', 'reach_count', 'total_reach_count', 'province_code', 'city_code', 'country_code', 'district_code', 'analysis_status', 'analysis_time', 'repost_trend_cursor', 'repost_sinceid', 'repost_maxid', 'repost_trend_update', 'comment_sinceid', 'comment_maxid', 'update_time', 'comment_updatetime', 'interrupt_newtime', 'interrupt_orig_righttime', 'interrupt_repost_righttime', 'interrupt_user_righttime');

//文章中包含的用户字段 add by wang_cc original_url 用户的信息直接包含在文章中，需要在这里添加该字段 添加文章时候，会将artical['user']对象中的这些属性
//直接设置到 artical上面去, 例如 artical.userid == artical.user.userid 注意,该动作只会针对文章中没有的字段，例如文章中会有guid这时候如果user中也存在guid的话将不会覆盖
$solr_article_user_tags = array('guid', 'userid', 'level', 'screen_name', 'register_time', 'verified_reason', 'verify', 'verified_type', 'followers_count', 'total_reach_count', 'friends_count', 'sex', 'description', 'country_code', 'province_code', 'city_code', 'district_code', 'country', 'province', 'city', 'district',
    'users_profile_image_url', 'users_replys_count', 'users_location', 'users_friends_count');

/**
 * add by wangcc
 * 在添加已经分词的文章时候，针对于分段的字段需要将下面的字段设置到分段后的新文章中去
 * 针对text字段和pg_text字段需要，在分词的地方将所有的terms直接设置到value中去
 */
$segementedParagraphFieldMapping = array("NRN", "emoNRN", "district", "emoDistrict", "city", "emoCity", "province", "emoProvince", "country", "emoCountry", "business", "emoBusiness", "combinWord", "emoCombin", "organization", "emoOrganization", "emotion", "wb_topic", "emoTopic", "wb_topic_keyword", "emoTopicKeyword", "wb_topic_combinWord", "emoTopicCombinWord", "url", "host_domain", "account", "emoAccount");
/*
 * 初始化记录日志的实例
 * author: Todd
 * param：$conf 日志配置名称
 * return：返回Logger实例
 */
function initLogger($conf)
{
    global $logger;
    $logger = Logger::getLogger($conf);
}

//样式类型数组
function styletypelist()
{
    return array(array('code' => STYLETYPE_1, 'name' => '微博'),
        array('code' => STYLETYPE_2, 'name' => '论坛'),
        array('code' => STYLETYPE_3, 'name' => 'SNS'));
}

function taskpagestyletype()
{
    return array(
        array('id' => TASK_PAGESTYLE_ARTICLELIST, 'name' => '文章列表'),
        array('id' => TASK_PAGESTYLE_ARTICLEDETAIL, 'name' => '文章详情'),
        array('id' => TASK_PAGESTYLE_USERDETAIL, 'name' => '用户详情'),
        array('id' => TASK_PAGESTYLE_ALL, 'name' => '文章列表,文章详情,用户详情综合')
    );
}

//根据任务类型获取任务 tasktype 1:新增分析任务 2:新增抓取任务 3:新增数据迁移任务
//-1表示没有在当前任务页面和定时任务页面处理的任务,批量植入微博和批量植入用户在有单独的导航,更新快照是v2下创建的任务
function gettasksbytype($tasktype = NULL)
{
    $tasklist = array(
        array('id' => TASK_SYNC, 'name' => '重新分析', 'tasktype' => 1),
        array('id' => TASK_BRIDGEUSER, 'name' => '分析桥接用户', 'tasktype' => 1),
        array('id' => TASK_BRIDGECASE, 'name' => '分析桥接案例', 'tasktype' => 1),
        array('id' => TASK_WEIBO, 'name' => '抓取微博', 'tasktype' => 2),
        array('id' => TASK_REPOST_TREND, 'name' => '处理转发', 'tasktype' => 2),
        array('id' => TASK_STATUSES_COUNT, 'name' => '更新微博转发数', 'tasktype' => 2),
        array('id' => TASK_COMMENTS, 'name' => '抓取评论', 'tasktype' => 2),
        array('id' => TASK_NICKNAME, 'name' => '监控账号微博', 'tasktype' => 2),
        array('id' => TASK_IMPORTWEIBOURL, 'name' => '批量植入微博', 'tasktype' => -1),
        array('id' => TASK_IMPORTUSERID, 'name' => '批量植入用户', 'tasktype' => -1),
        array('id' => TASK_KEYWORD, 'name' => '抓取关键词', 'tasktype' => 2),
        array('id' => TASK_DATAPUSH, 'name' => '订阅推送', 'tasktype' => 2),
        array('id' => TASK_FRIEND, 'name' => '抓取关注', 'tasktype' => 2),
        array('id' => TASK_MIGRATEDATA, 'name' => '数据迁移', 'tasktype' => 3),
        array('id' => TASK_SNAPSHOT, 'name' => '更新快照', 'tasktype' => 4),
        array('id' => TASK_EVENTALERT, 'name' => '事件预警', 'tasktype' => 4),
        array('id' => TASK_WEBPAGE, 'name' => '抓取网页', 'tasktype' => 2),
        array('id' => TASK_REPOSTPATH, 'name' => '分析转发轨迹', 'tasktype' => 1),
        array('id' => TASK_COMMENTPATH, 'name' => '分析评论轨迹', 'tasktype' => 1)
    );
    $ret = array();
    if ($tasktype == NULL) {
        $ret = $tasklist;
    } else {
        foreach ($tasklist as $ti => $titem) {
            if ($titem['tasktype'] == $tasktype) {
                $ret[] = $titem;
            }
        }
    }
    return $ret;
}

/*
function getsearchengine(){
	$selist = array(
		array('id'=>SEARCHENGINE_BAIDU, 'name'=>'百度', 'website'=>'http://www.baidu.com', 'pagestyle'=>PAGESTYLETYPE_9)
	);
	return $selist;
}
function getsearchsite(){
	$selist = array(
		array('id'=>SEARCHSITE_AUTOHOME, 'name'=>'汽车之家', 'website'=>'club.autohome.com.cn', 'pagestyle'=>PAGESTYLETYPE_10)
	);
	return $selist;
}
 */
function getSpiderConfig()
{
    global $logger, $dsql;
    $result = array();
    $sql = "select id, name, urlconfigrule, templatetype as pagestyletype from spiderconfig";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->debug(__FILE__ . __LINE__ . " sqlerror " . $dsql->GetError());
    } else {
        while ($r = $dsql->GetArray($qr)) {
            $r['urlconfigrule'] = json_decode($r['urlconfigrule'], true);
            $result[] = $r;
        }
    }
    return $result;
}

function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}


$global_memory_limit = return_bytes(ini_get("memory_limit"));
/**
 *
 * 检测内存是否足够
 */
function checkMemory()
{
    global $global_memory_limit, $logger;
    if (!empty($global_memory_limit)) {
        $usedm = memory_get_usage(true);
        $r = ($global_memory_limit - $usedm) >= MEMORY_RESERVED;
        if (!$r) {
            $logger->warn(__FILE__ . __LINE__ . " memory used {$usedm}, max:{$global_memory_limit}");
        }
        return $r;
    } else {
        return true;
    }
}

/**
 *
 * 生成错误输出对象
 * @param $error_code
 * @param $error_str
 * @return {errorcode:xx, error:xxx}
 */
function getErrorOutput($error_code = -1, $error_str)
{
    return array("errorcode" => $error_code, "error" => $error_str);
}

function setErrorMsg($error_code = -1, $error_str)
{
    $error = getErrorOutput($error_code, $error_str);
    echo json_encode($error);
    exit;
}


function getAPIErrorText($code)
{
    $text = '';
    switch ($code) {
        case ERROR_IP_OUT_LIMIT:
            $text = 'IP超出限制';
            break;
        case ERROR_USER_OUT_LIMIT:
            $text = '用户超出限制';
            break;
        case ERROR_LOGIN:
            $text = '登录API失败';
            break;
        case ERROR_CONTENT_NOT_EXIST:
            $text = '内容不存在';
            break;
        case ERROR_TOKEN:
            $text = 'token失效';
            break;
        case ERROR_OTHER:
            $text = '其他错误';
            break;
        case ERROR_CONTENT_PRIVATE:
            $text = '内容被屏蔽';
            break;
        case ERROR_USER_NOT_EXIST:
            $text = '用户不存在';
            break;
    }
    return $text;
}

//解决jsonencode后 0 变null的 bug
function null2zero(&$value, $key)
{
    if ($value === null) {
        $value = 0;
    }
}

//转义solr的特殊符号
function solrPreg($str)
{
    $r = '/([\\+\\-\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"~\\:\\\\])/i';
    return preg_replace($r, '\\\$1', $str);
}

//对solr中的特殊符号进行编码
function solrEsc($str)
{
    $str1 = solrPreg($str);
    return urlencode($str1);
}

/*
 * 汉字json_encode存数据库
 * 汉字直接经过json_encode存入数据库,会因为汉字转为unicode形式,\uae中的\被转义存入数据库错误,所以
 * 先经过rawurlencode把中文转为%后跟两位数字的形式,再进行json_encode,转为字符串后再rawurldecode转为汉字
 * $data要转为字符串的数组或对象
 */
function jsonEncode4DB($data)
{
    global $dsql;
    if (!$dsql->isInit) {
        $dsql->Init();
    }
    /*if(is_array($data)){
        $dataStr = arrJsonEncode4DB($data);
    }
    else if(is_object($data)){
        $dataStr = objJsonEncode4DB($data);
    }*/
    $dataStr = json_encode($data);
    $dataStr = $dsql->Esc($dataStr);
    return $dataStr;
}

//深度遍历array的每个健时，使用此函数编码value
function urlEncodeItem(&$value, $key)
{
    if (!empty($value)) {
        $value = rawurlencode($value);
    }
}

function arrJsonEncode4DB($dataArr)
{
    array_walk_recursive($dataArr, 'urlEncodeItem');
    $dataStr = rawurldecode(json_encode($dataArr));
    return $dataStr;
}

//递归遍历对象的属性，并将值编码
function recursiveUrlEncodeObject($obj)
{
    $r;
    if (is_object($obj)) {
        $r = (object)array();
        foreach ($obj as $key => $value) {
            $r->$key = recursiveUrlEncodeObject($obj->$key);
        }
    } else if (is_array($obj)) {
        $r = array();
        foreach ($obj as $key => $value) {
            $r[$key] = recursiveUrlEncodeObject($obj[$key]);
        }
    } else {
        if (!empty($obj)) {
            $r = rawurlencode($obj);
        } else {
            $r = $obj;
        }
    }
    return $r;
}

function objJsonEncode4DB($dataObj)
{
    $r = recursiveUrlEncodeObject($dataObj);
    $dataStr = rawurldecode(json_encode($r));
    return $dataStr;
}

/*
 * 记录日志时，每次记录ERROR或FATAL类型的错误时，
 * 调用此函数，记录出错次数，根据出错次数，做
 * 相应的对策
 */
function take_notes_error($log_note = '')
{
    global $config, $total_error, $logger, $total_rate_limit_error;
    $inner_log_note = "enter take_notes_error";
    if ($config['DO_ALL_LOG']) {
        $logger->debug($inner_log_note);
    }
    if (strpos($log_note, 'user requests out of rate limit') !== false) {
        /*
         * 临时方案：当访问api返回次数用尽的错误时，如果次数大于5，
         *  则认为需要sleep，sleep到下一个小时，
         */
        $total_rate_limit_error++;
        if ($total_rate_limit_error >= $config['MAX_RATE_LIMIT_ERROR_COUNT']) {
            change_test_user();
            $api_access_count_current = 1;    //切换用户后，本次api的访问是第一次
        }
    } else {
        $total_error++;
        if ($total_error >= $config['MAX_ERROR_COUNT']) {
            $inner_log_note = "total error count is " . $total_error . " times, then exit!";
            if ($config['DO_ALL_LOG']) {
                $logger->debug($inner_log_note);
            }
            global $total_api_execute_time, $total_api_execute_count, $total_sql_execute_time, $total_sql_execute_count, $total_script_execute_time;
            $inner_log_note = "api调用总数:" . $total_api_execute_count . " api执行时间:" . $total_api_execute_time . "s
                         sql脚本总数:" . $total_sql_execute_count . " sql脚本执行时间:" . $total_sql_execute_time . "s
                         总时间:" . $total_script_execute_time . "s";
            if ($config['DO_ALL_LOG']) {
                $logger->debug($inner_log_note);
            }
            exit;
        }
    }
}

function object_array($obj)
{
    if (is_object($obj)) {
        $obj = (array)$obj;
    }
    if (is_array($obj)) {
        foreach ($obj as $key => $value) {
            $obj[$key] = object_array($value);
        }
    }
    return $obj;
}

/*
 * 用于计算执行时间，返回当前的时间点
 */
function microtime_float()
{
    //set_log(DEBUG, "enter microtime_float", __FILE__, __LINE__);
    list($usec, $sec) = explode(" ", microtime());
    //set_log(DEBUG, "exit microtime_float", __FILE__, __LINE__);
    return ((float)$usec + (float)$sec);
}

//链接数据库
function dbconnect()
{
    connectMysql(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO);
    mysql_query("SET NAMES utf8");
}

/*
 * 数据库连接函数
 */
function connectMysql($host, $user, $password, $table)
{
    global $link, $db;
    /*
     * 调用connectMysql时，还未对log类进行初始化
     */
    //set_log(DEBUG, "enter connectMysql", __FILE__, __LINE__);
    $link = mysql_connect($host, $user, $password) or die ("connect DB failed" . mysql_error());
    $db = mysql_select_db($table) or die ("select db failed " . mysql_error());
    //set_log(DEBUG, "exit connectMysql", __FILE__, __LINE__);
}

/*
 * 数据库关闭函数
 */
function closeMysql()
{
    global $link;
    //set_log(DEBUG, "enter closeMysql", __FILE__, __LINE__);
    //mysql_close($link);
    //set_log(DEBUG, "exit closeMysql", __FILE__, __LINE__);
}

/*
 * 检查数据库连接
 */
function ping()
{
    global $link;
    if (!mysql_ping($link)) {
        closeMysql();
        connectMysql(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        mysql_query("SET NAMES utf8");
    } else {
        set_log(DEBUG, "mysql connect is normal", __FILE__, __LINE__);
    }
}

/*
 * 为$config变量设置默认值，是$config不依赖于数据库
 * 这样当添加一个任务时，如果任务中需要配置在数据库
 * 中配置变量，则也需要在这里对该变量进行默认值设置
 *
 * 该函数需要在通过读取数据库数据对$config进行初始化
 * 的操作之前调用
 * 这样做可以是该函数中设置的默认值被数据库中的值覆盖
 * 掉
 */
function set_config_default_value()
{
    global $config;
    //是否记录所有日志：1，记录；2，不记录
    $config['DO_ALL_LOG'] = '1';
    //每个应用测试用户的最大值
    $config['TEST_USER_MAX'] = '15';
    //存放日志的目录
    $config['LOG_DIR'] = 'E:\weibolog';
    //用户的粉丝数少于该值时，不录入数据库
    $config['FOLLOWER_LIMIT'] = '100';
    //但出现错误数达到该值时，退出脚本
    $config['MAX_ERROR_COUNT'] = '10';
    /*
     * 当出现api次数限制错误的次数大于该值时，切换用户
     * 对于该值的大小，建议为1，因为超过访问次数限制这
     * 个错误发生，就意味着需要切换用户或sleep到下一个
     * 小时，所以设置为1，即只要发生该错误，就调用
     * change_test_user
     */
    $config['MAX_RATE_LIMIT_ERROR_COUNT'] = '1';
    //每个测试用户每小时访问api的最大次数
    $config['ACCESS_MAX'] = '1000';
    //当粉丝数大于该值时，认为用户是名人
    $config['CELE_FOLLOWER_COUNT'] = 100000;
    /*
     * type=1
     */
    //用户更新时间至今超过该值，则需要更新
    $config['UPDATE_TIME'] = '2592000';
    /*
     * 2012-1-9
     * 分析桥接案例时用到
     */
    //总转发数减去转发数小于此值时，认为不是桥接微博
    $config['REPOST_IGNORE_COUNT'] = 50;
    //总转发数减去转发数大于此值时，认为是桥接微博
    $config['REPOST_BRIDGE_COUNT'] = 7500;
    //总转发数除以转发数大于此值时，认为是桥接微博
    $config['REPOST_TOTAL_AND_DIRECT_RATIO'] = 2;
    //总达到数减去达到数小于此值时，认为不是桥接微博
    $config['REACH_IGNORE_COUNT'] = 5000;
    //总达到数减去达到数大于此值时，认为是桥接微博
    $config['REACH_BRIDGE_COUNT'] = 750000;
    //总达到数除以达到数大于此值时，认为是桥接微博
    $config['REACH_TOTAL_AND_DIRECT_RATIO'] = 20;
    /*
     * 2012-1-13
     * type 30
     */
    $config['MIN_REPOST_COUNT'] = 2000;
    $config['SELECT_WEIBO_COUNT'] = 10;
    $config['SELECT_WEIBO_CURSOR'] = 0;
    $config['REPOST_UPDATE_TIME'] = 720000;
    $config['MAX_GET_REPOST_COUNT'] = 200;
    $config['WEIBO_CURSOR_UPDATE_TIME'] = 720000;
    $config['SELECT_WEIBO_CURSOR_UPDATE'] = 0;
}

/*
 * 在数据库中获取配置信息，通过type为GET_USER进行查询
 */
function get_config()
{
    global $config, $dsql, $logger;
    set_config_default_value();
    /*
     * 先读取type=0的配置，即通用配置
     * 如果具体类型中定义了与通用配置相同的项，
     * 则覆盖通用配置中的值
     */
    $sql = "select `key`,`value` from " . DATABASE_CONFIG . " where type=0";
    $queryResult = $dsql->ExecQuery($sql, 'get_config');
    if (!$queryResult) {
        $logger->error('mysql error - ' . $dsql->GetError());
        exit;
    }
    while (NULL != ($fetchResult = $dsql->GetArray($queryResult, MYSQL_ASSOC))) {
        $config[$fetchResult['key']] = $fetchResult['value'];
    }
    $dsql->FreeResult($queryResult);

    $sql = "select `key`,`value` from " . DATABASE_CONFIG . " where type=" . CONFIG_TYPE;
    $queryResult = $dsql->ExecQuery($sql, 'get_config');
    if (!$queryResult) {
        $logger->error('mysql error - ' . $dsql->GetError());
        exit;
    }
    while (NULL != ($fetchResult = $dsql->GetArray($queryResult, MYSQL_ASSOC))) {
        $config[$fetchResult['key']] = $fetchResult['value'];
    }
    $dsql->FreeResult($queryResult);
}


/*
 * $log_type:日志类型，定义了NORMAL，DEBUG，ERROR，FATAL四种类型，也可自行定义
 * $log_note:日志内容
 * $file:记录位置所属的文件
 * $line:记录日志的行号
 */
function set_log($log_type, $log_note, $file = NULL, $line = 0)
{
    global $logger;
    if (!empty($logger)) {
        switch ($log_type) {
            case NORMAL:
                $logger->info($log_note);
                break;
            case WARNING:
                $logger->warn($log_note);
                break;
            case DEBUG:
                $logger->debug($log_note);
                break;
            case ERROR:
                $logger->error($log_note);
                break;
            case FATAL:
                $logger->fatal($log_note);
                break;
            default:
                return;
        }
    }
    /*global $debug_logs, $config, $error_logs;
    if (!$config['DO_ALL_LOG'] && DEBUG == $log_type)
    {
        return;
    }
    if (!$file || 0 == $line)
    {
        $debug_logs->setLog("[".date('Y-m-d H:i:s', time())."][".$log_type." msg]: ".$log_note."\n");
        if (ERROR == $log_type || FATAL == $log_type)
        {
            $error_logs->setLog("[".date('Y-m-d H:i:s', time())."][".$log_type." msg]: ".$log_note."\n");
        }
    }
    else
    {
        $debug_logs->setLog("[".date('Y-m-d H:i:s', time())." File:".$file." Line:".$line."][".$log_type." msg]: ".$log_note."\n");
        if (ERROR == $log_type || FATAL == $log_type)
        {
            $error_logs->setLog("[".date('Y-m-d H:i:s', time())." File:".$file." Line:".$line."][".$log_type." msg]: ".$log_note."\n");
        }
    }
    if (ERROR == $log_type || FATAL == $log_type)
    {
        take_notes_error($log_note);
    }*/
}

/*
 * 2012-1-8:
 * 处理take_notes_sql(or api)_arr
 */
function handle_take_notes_arr($take_notes_arr, $sql_or_api_type, $use_time)
{
    if (array_key_exists($sql_or_api_type, $take_notes_arr)) {
        $take_notes_arr[$sql_or_api_type]['total_time'] += $use_time;
        $take_notes_arr[$sql_or_api_type]['total_count']++;
        if ($take_notes_arr[$sql_or_api_type]['max_time'] < $use_time) {
            $take_notes_arr[$sql_or_api_type]['max_time'] = $use_time;
        }
        if ($take_notes_arr[$sql_or_api_type]['min_time'] > $use_time) {
            $take_notes_arr[$sql_or_api_type]['min_time'] = $use_time;
        }
        $take_notes_arr[$sql_or_api_type]['average_time'] = $take_notes_arr[$sql_or_api_type]['total_time'] / $take_notes_arr[$sql_or_api_type]['total_count'];
    } else {
        $tmp_take_notes_arr = array();
        $tmp_take_notes_arr['total_time'] = $use_time;
        $tmp_take_notes_arr['total_count'] = 1;
        $tmp_take_notes_arr['average_time'] = $use_time;
        $tmp_take_notes_arr['max_time'] = $use_time;
        $tmp_take_notes_arr['min_time'] = $use_time;
        $take_notes_arr[$sql_or_api_type] = $tmp_take_notes_arr;
    }
    foreach ($take_notes_arr[$sql_or_api_type] as $take_notes_arr_key => $take_notes_arr_value) {
        set_log(DEBUG, "{$sql_or_api_type} {$take_notes_arr_key} is {$take_notes_arr_value}");
    }
    set_log(DEBUG, "{$sql_or_api_type} this time use_time is {$use_time}");
    return $take_notes_arr;
}

/*
 * 记录每次sql操作执行的时间
 * $start_time：sql执行的开始时间点
 * $end_time：sql执行完成后的时间点
 * $note：记录日志的内容，作为set_log的参数
 * $file：__FILE__
 * $note：__LINE__
 */
function take_notes_sql_time($start_time, $end_time, $note, $file, $line, $sql_type = null)
{
    global $total_sql_execute_time, $max_sql_execute_time,
           $min_sql_execute_time, $total_sql_execute_count,
           $take_notes_sql_arr;
    set_log(DEBUG, "enter take_notes_sql_time", __FILE__, __LINE__);
    $use_time = $end_time - $start_time;
    $total_sql_execute_time += $use_time;
    if ($use_time > $max_sql_execute_time || 0 == $max_sql_execute_time) {
        $max_sql_execute_time = $use_time;
    }
    if ($use_time < $min_sql_execute_time || 0 == $min_sql_execute_time) {
        $min_sql_execute_time = $use_time;
    }
    //统计数据库操作总数
    $total_sql_execute_count++;
    $log_note = $note . " sql use time is " . $use_time . " s";
    set_log(DEBUG, $log_note, $file, $line);
    if ($sql_type) {
        $take_notes_sql_arr = handle_take_notes_arr($take_notes_sql_arr, $sql_type, $use_time);
    }
    set_log(DEBUG, "exit take_notes_sql_time", __FILE__, __LINE__);
}

/*
 * 记录每次api操作执行的时间
 * $start_time：api执行的开始时间点
 * $end_time：api执行完成后的时间点
 * $note：记录日志的内容，作为set_log的参数
 * $file：__FILE__
 * $note：__LINE__
 */
function take_notes_api_time($start_time, $end_time, $note, $file, $line, $api_type = null)
{
    global $total_api_execute_time, $max_api_execute_time,
           $min_api_execute_time, $total_api_execute_count,
           $take_notes_api_arr;
    set_log(DEBUG, "enter take_notes_api_time", __FILE__, __LINE__);
    $use_time = $end_time - $start_time;
    $total_api_execute_time += $use_time;
    if ($use_time > $max_api_execute_time || 0 == $max_api_execute_time) {
        $max_api_execute_time = $use_time;
    }
    if ($use_time < $min_api_execute_time || 0 == $min_api_execute_time) {
        $min_api_execute_time = $use_time;
    }
    //统计api调用总数
    $total_api_execute_count++;
    $log_note = $note . " api use time is " . $use_time . " s";
    set_log(DEBUG, $log_note, $file, $line);
    if ($api_type) {
        $take_notes_api_arr = handle_take_notes_arr($take_notes_api_arr, $api_type, $use_time);
    }
    set_log(DEBUG, "exit take_notes_api_time", __FILE__, __LINE__);
}

/*
 * 通过get_test_users获取测试用户后，根据得到的用户，
 * 实例化WeiboClient
 */
function init_weiboclient($sourceid, $username, $appkey)
{
    global $oAuthThird, $oAuthThirdBiz, $logger;
    $logger->debug("enter init_weibo_object");
    /*test start bert*/
    //token = '2.00SdyOsBnp71EDa660815103CQCIoB';
    $username = 'zhyucn@hotmail.com';
    $appkey = '2812373555';
    /*test end bert*/
    $oAuthThird = new Weibo_Base($sourceid, $username, $appkey);
    $oAuthThirdBiz = new Weibo_Base($sourceid, $username, $appkey, true);
    $logger->debug("exit init_weibo_object");
}

/*
 * 切换用户函数，实现了切换用户的策略
 * 在某个小时内，当前用户访问数超过限制时，需要切换用户
 * $test_user_current表示当前用户在$test_users数组中的
 * 位置
 * $test_user_count表示测试用户总数
 * 进入函数，首先比较$test_user_current和$test_user_count
 * 判断测试用户在一个小时之内是否全部用完
 */
function change_test_user()
{
    global $test_user_count, $test_user_rest, $test_user_current, $test_users;
    set_log(DEBUG, "enter change_test_user", __FILE__, __LINE__);
    if ($test_user_rest <= 0) {
        //sleep_time多加10秒，确保一定能够进入下一个小时的api调用(个人想法)
        $sleep_time = 3600 - strtotime(date('Y-m-d H:i:s', time())) % 3600 + 10;
        $log_str = "sleep time is " . $sleep_time . " s";
        set_log(DEBUG, $log_str, __FILE__, __LINE__);
        sleep($sleep_time);
        $test_user_current = 0;
        $test_user_rest = $test_user_count;
    }
    $position = $test_user_current % $test_user_count;
    init_weiboclient($test_users[$position]['name'], $test_users[$position]['password']);
    $test_user_current++;
    $test_user_rest--;
    set_log(DEBUG, "exit change_test_user", __FILE__, __LINE__);
}

/*
 * 每次调用api之前，都需要调用该函数，检查是否需要切换用户
 * $start_hour_count表示一小时内第一次调用api时的小时数，
 * 通过时间戳计算出来
 * $current_hour_count表示当前调用api时的小时数
 * 如果在同一小时内，则在$api_access_count_current基础上
 * 加1，判断是否超过单个用户每小时访问的最大值，如果超过，
 * 需要切换用户，并将$api_access_count_current置为1
 * 如果不在同一小时内，则$start_hour_count置为$current_hour_count，
 * $api_access_count_current置为1
 * 需要添加一个变量存放用户切换的剩余量$test_user_rest，该变
 * 量的初始值为$test_user_count
 */
function before_invoke_api()
{
    global $config, $api_access_count_current,
           $start_hour_count, $current_hour_count,
           $test_user_current, $test_user_rest,
           $test_user_count;
    set_log(DEBUG, "enter before_invoke_api", __FILE__, __LINE__);
    //每次调用api之前，都要判断该次调用是否和上一次调用在同一个小时内
    if (!$start_hour_count) {
        $start_hour_count = (int)(strtotime(date('Y-m-d H:i:s', time())) / 3600);
    } else {
        $current_hour_count = (int)(strtotime(date('Y-m-d H:i:s', time())) / 3600);
        //如果该次调用与上次调用不在同一个小时内，则将$api_access_count_current清零
        if ($current_hour_count != $start_hour_count) {
            /*
             * 在调用api时判断与上次调用api是否在同一个小时
             * 如果不在同一个小时，
             * api访问数置0
             * 同时，
             * 也要对$test_user_current和$test_user_rest
             * 进行初始化操作
             */
            $api_access_count_current = 0;
            /*
             * 这里不需要把$test_user_current置为0
             * 保持当前用户即可
             */
            //$test_user_current = 0;
            $test_user_rest = $test_user_count;
        }
    }
    $api_access_count_current++;
    if ($api_access_count_current > $config['ACCESS_MAX']) {
        change_test_user();
        $api_access_count_current = 1;    //切换用户后，本次api的访问是第一次
    }
    set_log(DEBUG, "exit before_invoke_api", __FILE__, __LINE__);
}

/*
 * 更新config配置表的内容
 * type：确定要更新的类型
 * key：要更新的项
 * value：要更新的内容
 */
function update_config($type, $key, $value)
{
    global $logger, $dsql;
    $logger->debug("enter update_config");
    $updata_sql = "update " . DATABASE_CONFIG . " set `value`='" . $value . "' where `type`='" . $type . "' and `key`='" . $key . "'";
    $start_sql = microtime_float();
    $qr = $dsql->ExecQuery($updata_sql);
    $end_sql = microtime_float();
    if (!$qr) {
        $sql_note = $dsql->GetError() . " sql:" . $updata_sql;
        $logger->error($sql_note);
    } else {
        $note = "update_config key is " . $key . ",value is " . $value . " sql is " . $updata_sql;
        take_notes_sql_time($start_sql, $end_sql, $note,
            __FILE__, __LINE__,
            TAKE_NOTES_SQL_UPDATE);
    }
    $dsql->FreeResult($qr);
    $logger->debug('exit update_config');
}

/*
 * 脚本结束前，记录各项数据
 */
function take_notes_execute_result()
{
    global $total_api_execute_count, $total_api_execute_time,
           $total_sql_execute_count, $total_sql_execute_time,
           $total_script_execute_time, $take_notes_sql_arr,
           $take_notes_api_arr;
    set_log(DEBUG, "enter take_notes_execute_result", __FILE__, __LINE__);
    $log_note = "api调用总数:" . $total_api_execute_count . " api执行时间:" . $total_api_execute_time . "s
                 sql脚本总数:" . $total_sql_execute_count . " sql脚本执行时间:" . $total_sql_execute_time . "s
                 总时间:" . $total_script_execute_time . "s";
    set_log(DEBUG, $log_note);
    foreach ($take_notes_sql_arr as $take_notes_sql_arr_key => $take_notes_sql_arr_value) {
        set_log(DEBUG, "sql type is {$take_notes_sql_arr_key}");
        foreach ($take_notes_sql_arr_value as $tmp_key => $tmp_value) {
            set_log(DEBUG, "{$take_notes_sql_arr_key}:{$tmp_key} is {$tmp_value}");
        }
    }
    foreach ($take_notes_api_arr as $take_notes_api_arr_key => $take_notes_api_arr_value) {
        set_log(DEBUG, "api type is {$take_notes_api_arr_key}");
        foreach ($take_notes_api_arr_value as $tmp_key => $tmp_value) {
            set_log(DEBUG, "{$take_notes_api_arr_key}:{$tmp_key} is {$tmp_value}");
        }
    }
    set_log(DEBUG, "exit take_notes_execute_result", __FILE__, __LINE__);
}

/*
define('RET_LABEL', 'label');
define('RET_VALUE', 'value');
 */

/*
 * 拆分数据
 * 按月获取：
 *  需要把不同年份拆分出来
 * 按天获取
 *  需要把不同年和月拆分出来
 */
function format_result($data, $startdate, $enddate, $is_month = TRUE, $is_hour = FALSE)
{
    set_log(DEBUG, 'enter format_result', __FILE__, __LINE__);
    if ((!$startdate || !$enddate) && !$data) {
        set_log(DEBUG, 'exit format_result', __FILE__, __LINE__);
        return $data;
    }
    $res_arr = array();
    if (!$is_hour) {
        $start_year = null;
        $start_month = null;
        $start_day = null;
        $end_year = null;
        $end_month = null;
        $end_day = null;
        /*
         * 标识是否存在起始时间和结束时间
         * 如果不存在，则将按月返回的每一年的开始月和
         * 结束月，都设置为默认的开始月和结束月；将按天
         * 返回的每一月的开始天和结束天都设置成默认的开
         * 始天和结束天
         */
        $exist_startdate = TRUE;
        $exist_enddate = TRUE;

        $count_data = count($data);

        if (!$startdate) {
            $exist_startdate = FALSE;
            $startdate = strtotime($data[0][RET_LABEL]);
        }
        $start_year = date('Y', $startdate);
        $start_month = date('n', $startdate);
        $start_day = date('j', $startdate);
        if (!$enddate) {
            $exist_enddate = FALSE;
            $enddate = strtotime($data[$count_data - 1][RET_LABEL]);
        }
        $end_year = date('Y', $enddate);
        $end_month = date('n', $enddate);
        $end_day = date('j', $enddate);
        if ($is_month) {
            $diff_year = $end_year - $start_year;
            $tmp_year = $start_year;
            for ($i = $diff_year; $i >= 0; $i--) {
                $tmp_data = null;
                $tmp_month_init = 1;
                $tmp_month_end = 0;
                foreach ($data as $data_key => $data_value) {
                    $unix_time = strtotime($data_value[RET_LABEL]);
                    $data_value_year = date('Y', $unix_time);
                    if ($data_value_year == $tmp_year) {
                        $tmp_data[] = $data_value;
                    }
                }
                if ($exist_startdate && $tmp_year == $start_year) {
                    $tmp_month_init = $start_month;
                }
                if ($exist_enddate && $tmp_year == $end_year) {
                    $tmp_month_end = $end_month;
                }
                if (!$tmp_data) {
                    $tmp_chart[RET_LABEL] = $tmp_year . '-01';
                    $tmp_chart[RET_VALUE] = 0;
                    $tmp_data[] = $tmp_chart;
                }
                $tmp_res_arr = format_result_in($tmp_data, $is_month, $is_hour,
                    $tmp_month_init, $tmp_month_end);
                $res_arr = array_merge($res_arr, $tmp_res_arr);
                $tmp_year++;
            }
        } else {
            $diff_year = $end_year - $start_year;
            $tmp_year = $start_year;
            for ($i = $diff_year; $i >= 0; $i--) {
                $tmp_start_month = 1;
                $tmp_end_month = 12;
                if ($tmp_year == $start_year) {
                    $tmp_start_month = $start_month;
                }
                if ($tmp_year == $end_year) {
                    $tmp_end_month = $end_month;
                }
                for ($j = $tmp_start_month; $j <= $tmp_end_month; $j++) {
                    $tmp_data = null;
                    $tmp_day_init = 1;
                    $tmp_day_end = 0;

                    foreach ($data as $data_key => $data_value) {
                        $unix_time = strtotime($data_value[RET_LABEL]);
                        $data_value_year = date('Y', $unix_time);
                        $data_value_month = date('n', $unix_time);
                        if ($data_value_year == $tmp_year
                            && $data_value_month == $j
                        ) {
                            $tmp_data[] = $data_value;
                        }
                    }
                    if ($exist_startdate && $tmp_year == $start_year
                        && $tmp_start_month == $j
                    ) {
                        $tmp_day_init = $start_day;
                    }
                    if ($exist_enddate && $tmp_year == $end_year
                        && $tmp_end_month == $j
                    ) {
                        $tmp_day_end = $end_day;
                    }
                    if (!$tmp_data) {
                        if ($j < 10) {
                            $tmp_chart[RET_LABEL] = $tmp_year . '-0' . $j . '-01';
                        } else {
                            $tmp_chart[RET_LABEL] = $tmp_year . '-' . $j . '-01';
                        }
                        $tmp_chart[RET_VALUE] = 0;
                        $tmp_data[] = $tmp_chart;
                    }
                    $tmp_res_arr = format_result_in($tmp_data, $is_month, $is_hour,
                        $tmp_day_init, $tmp_day_end);
                    $res_arr = array_merge($res_arr, $tmp_res_arr);
                }
                $tmp_year++;
            }
        }
    } else {
        $res_arr = format_result_in($data, $is_month, $is_hour);
    }
    set_log(DEBUG, 'exit format_result', __FILE__, __LINE__);
    return $res_arr;
}

/*
 * 对按月，按天，按小时返回的结果集进行格式化操作
 * 对$data变量进行操作
 * 比如按小时返回，当$data中只存在24小时中部分小时信息时，
 * 即只有部分小时内有数据，不包含其他没有数据的小时，
 * 此时，需要该函数，将其他小时的内容填补上，并把数据设为0
 */
function format_result_in($data, $is_month = TRUE, $is_hour = FALSE, $month_or_day_init = 1, $month_or_day_end = 0)
{
    set_log(DEBUG, "enter format_result_in", __FILE__, __LINE__);
    /*
     * 进入该函数时，不需要判断data，因为不合法的data，在
     * format_result中已经被拦住了
     */
    /*
    if (!$data)
    {
        set_log(DEBUG, "exit format_result_in", __FILE__, __LINE__);
        return $data;
    }
    */
    if ($is_month) {
        /*
        $date_label = $data[0][RET_LABEL];
        $date_arr = explode('-', $date_label);
        $year = $date_arr[0];
         */
        $date_label = new DateTime($data[0][RET_LABEL]);
        $year = $date_label->format('Y');
        if (!$month_or_day_end) {
            $month_or_day_end = 12;
        }
        for ($i = $month_or_day_init; $i <= $month_or_day_end; $i++) {
            if ($i < 10) {
                $res_key = $year . "-0" . $i;
            } else {
                $res_key = $year . "-" . $i;
            }
            $res[$res_key] = 0;
        }
    } else if (!$is_hour) {
        /*
        $date_label = $data[0][RET_LABEL];
        $date_arr = explode('-', $date_label);
        $year = $date_arr[0];
        $month = $date_arr[1];
         */
        $date_label = new DateTime($data[0][RET_LABEL]);
        $year = $date_label->format('Y');
        $month = intval($date_label->format('m'));
        switch ($month) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                $i = 31;
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                $i = 30;
                break;
            case 2:
                //判断是否为闰年
                $time = mktime(20, 20, 20, 4, 20, $year);//取得一个日期的 Unix 时间戳;
                if (date("L", $time) == 1) {
                    //闰年
                    $i = 29;
                } else {
                    $i = 28;
                }
                break;
        }
        if (!$month_or_day_end) {
            $month_or_day_end = $i;
        }
        if ($month < 10) {
            $tmp_month = '0' . $month;
        } else {
            $tmp_month = $month;
        }
        for ($j = $month_or_day_init; $j <= $month_or_day_end; $j++) {
            if ($j < 10) {
                $res_key = $year . "-" . $tmp_month . "-0" . $j;
            } else {
                $res_key = $year . "-" . $tmp_month . "-" . $j;
            }
            $res[$res_key] = 0;
        }
    } else {
        for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
                $res_key = "0" . $i . ":00";
            } else {
                $res_key = $i . ":00";
            }
            $res[$res_key] = 0;
        }
    }
    foreach ($res as $res_key => $res_value) {
        foreach ($data as $data_key => $data_value) {
            if ($data_value[RET_LABEL] == $res_key) {
                $res_value = $data_value[RET_VALUE];
                break;
            }
        }
        $chart[RET_LABEL] = $res_key;
        $chart[RET_VALUE] = $res_value;
        $rel_res[] = $chart;
    }
    set_log(DEBUG, "exit format_result_in", __FILE__, __LINE__);
    return $rel_res;
}

/*
$data[0][RET_LABEL] = '08:00';
$data[0][RET_VALUE] = 108;
$data[1][RET_LABEL] = '09:00';
$data[1][RET_VALUE] = 109;

$start = strtotime('2010-07-01');
$end = strtotime('2011-11-30');

format_result($data, $start, $end, FALSE, TRUE);
exit;
 */

/*
 * sql insert语句的模版函数
 * 根据参数生成sql语句
 */
function insert_template($table_name, $data)
{
    global $logger, $dsql;
    $key_str = '';
    $value_str = '';
    $i = 0;
    foreach ($data as $key => $value) {
        if (empty($value) && ($value !== false || $value !== 0)) {
            continue;
        }

        if (!$i) {
            $key_str = "`{$key}`";
            $value_str = "'" . $dsql->Esc($value) . "'";
            $i++;
        } else {
            $key_str .= ", `{$key}`";
            $value_str .= ", '" . $dsql->Esc($value) . "'";
        }
    }
    $sql = "insert into {$table_name} ({$key_str}) values ({$value_str});";
    return $sql;
}

/*
 * sql update语句的模版函数
 * 根据参数生成sql语句
 */
function update_template($table_name, $set_data, $where_data)
{
    global $logger, $dsql;
    //$logger->debug('enter update_template');
    $set_str = '';
    $where_str = '';
    $i = 0;
    foreach ($set_data as $key => $value) {
        if (!$i) {
            $set_str = "`$key` = '" . $dsql->Esc($value) . "'";
            $i++;
        } else {
            $set_str .= ", `$key` = '" . $dsql->Esc($value) . "'";
        }
    }
    $i = 0;
    foreach ($where_data as $key => $value) {
        if (!$i) {
            $where_str = "`$key` = '" . $dsql->Esc($value) . "'";
            $i++;
        } else {
            $where_str .= "and `$key` = '" . $dsql->Esc($value) . "'";
        }
    }
    $sql = "update {$table_name} set {$set_str} where {$where_str}";
    //$logger->debug('exit update_template');
    return $sql;
}

/*
 * 向solr发送数据，并根据返回结果，做
 * 相应的返回
 */
function handle_solr_data($solr_weibos_infos, $url)
{
    global $logger;
    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用solr--+-ReqURL:[" . $url . "]...");
    $send_solr_result = send_solr($solr_weibos_infos, $url);
    //set_log(DEBUG, "enter handle_solr_data,resp:[" . var_export($send_solr_result, true) . "].", __FILE__, __LINE__);

    $send_solr_status = isset($send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_STATUS]) ? $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_STATUS] : -1;
    $send_solr_ids = isset($send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_IDS]) ? $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_IDS] : array();
    if ($send_solr_status != 0) {
        //set_log(DEBUG, 'exit handle_solr_data', __FILE__, __LINE__);
        $logger->error(__FUNCTION__ . __LINE__ . " request solr error, url:" . $url . " response data is:" . var_export($send_solr_result, true));
        return false;
    } else {
        if (empty($send_solr_ids)) {
            //$logger->debug(__FUNCTION__.__LINE__." url:".$url." data is:".var_export($solr_weibos_infos, true));
            solr_statics($send_solr_result);
            return NULL;
        } else {
            return $send_solr_ids;
        }
    }
}

/**
 * solr数据统计
 */
function solr_statics($send_solr_result)
{
    global $logger;
    global $solrlog;
    if (isset($send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_STATISTICS])) {
        $loginfo = "";
        $loginfo_all = "";
        $statistics = $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_STATISTICS];

        //本次耗费总时间
        if (isset($send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_QTIME])) {
            if (!isset($solrlog["time"])) {
                $solrlog["time"] = $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_QTIME];
            } else {
                $solrlog["time"] += $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_QTIME];
            }
            $total_time = $solrlog["time"];
            $loginfo_all .= "总共耗费：" . $total_time . "毫秒";
            $loginfo .= "本次处理耗费：" . $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_QTIME] . "毫秒";
        }

        //本次处理条数
        if (isset($send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_DOCCOUNT])) {
            if (!isset($solrlog["docCount"])) {
                $solrlog["docCount"] = $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_DOCCOUNT];
            } else {
                $solrlog["docCount"] += $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_DOCCOUNT];
            }
            $total_docCount = $solrlog["docCount"];
            $loginfo_all .= "---总共处理：" . $total_docCount . "条";
            $loginfo .= "---本次处理：" . $send_solr_result[RET_SEND_SOLR_RESPONSEhEADER][RET_SEND_SOLR_DOCCOUNT] . "条";
        }

        foreach ($statistics as $keys => $values) {
            switch ($keys) {
                case "0_solr":
                    $loginfo .= "\n---solr耗时统计信息：\n";
                    $loginfo_all .= "\n---solr总耗时统计信息：\n";
                    break;
                case "1_lucene":
                    $loginfo .= "---lucene耗时统计信息：\n";
                    $loginfo_all .= "---lucene总耗时统计信息：\n";
                    break;
                case "2_analyzer":
                    $loginfo .= "---lucene分析器耗时统计信息：\n";
                    $loginfo_all .= "---lucene分析器总耗时统计信息：\n";
                    break;
                case "3_fudan":
                    $loginfo .= "---复旦分词器耗时统计信息：\n";
                    $loginfo_all .= "---复旦分词器总耗时统计信息：\n";
                    break;
                default:
                    break;
            }
            foreach ($values as $key => $value) {
                if ($key != "2_l_commit_flush" && $key != "3_l_commit_merge" && $key != "4_l_commit_internal" && $key != "3_s_updateSearcher") {
                    if (!empty($value)) {
                        if (!isset($solrlog[$key . "_total_all"]))
                            $solrlog[$key . "_total_all"] = $value[0];
                        else
                            $solrlog[$key . "_total_all"] += $value[0];
                    }
                    if (count($value) > 1) {
                        if (!isset($solrlog[$key . "_max"]))
                            $solrlog[$key . "_max"] = $value[1];
                        else
                            $solrlog[$key . "_max"] = max($solrlog[$key . "_max"], $value[1]);
                    }

                    if (count($value) > 2) {
                        if (!isset($solrlog[$key . "_min"])) {
                            $solrlog[$key . "_min"] = $value[2];
                        } else {
                            $solrlog[$key . "_min"] = min($solrlog[$key . "_min"], $value[2]);
                        }
                    }

                    if (count($value) > 3) {
                        if (!isset($solrlog[$key . "_count_all"]))
                            $solrlog[$key . "_count_all"] = $value[3];
                        else
                            $solrlog[$key . "_count_all"] += $value[3];
                        if ($value[3] != 0) {
                            $loginfo .= "------" . get_readable_text($key) . "总耗费：" . $value[0] . "微秒--最大：" . $value[1] . "微秒--最小：" . $value[2] . "微秒--平均：" . $value[0] / $value[3] . "微秒--执行" . $value[3] . "次\n";
                        }
                    }
                } else {
                    if (count($value) > 0) {
                        if (!isset($solrlog[$key . "_total_all"]))
                            $solrlog[$key . "_total_all"] = $value[0];
                        else
                            $solrlog[$key . "_total_all"] += $value[0];

                        if (!isset($solrlog[$key . "_max"]))
                            $solrlog[$key . "_max"] = $value[0];
                        else
                            $solrlog[$key . "_max"] = max($solrlog[$key . "_max"], $value[0]);

                        if (!isset($solrlog[$key . "_min"]))
                            $solrlog[$key . "_min"] = $value[0];
                        else
                            $solrlog[$key . "_min"] = min($solrlog[$key . "_min"], $value[0]);

                        if (!isset($solrlog[$key . "_count_all"]))
                            $solrlog[$key . "_count_all"] = 1;
                        else
                            $solrlog[$key . "_count_all"] += 1;

                        $loginfo .= "------" . get_readable_text($key) . "耗费：" . $value[0] . "微秒\n";
                    }
                }
                if (isset($solrlog[$key . "_count_all"]) && $solrlog[$key . "_count_all"] != 0) {
                    $loginfo_all .= "------" . get_readable_text($key) . "总耗费：" . $solrlog[$key . "_total_all"] . "微秒--最大：" . $solrlog[$key . "_max"] . "微秒--最小：" . $solrlog[$key . "_min"] . "微秒--平均：" . ($solrlog[$key . "_total_all"] / $solrlog[$key . "_count_all"]) . "微秒--执行" . $solrlog[$key . "_count_all"] . "次\n";
                }
            }
        }
        $logger->info("本次处理结果：" . $loginfo . "-----------------------------------------------------------------------------------------------------------");
        $logger->info("总处理结果：" . $loginfo_all . "\n");
    } else {
        //无统计信息
    }
}

/**
 * 将solr返回的数据统计字段转成好理解的文本 //数字表示所属关系
 */
function get_readable_text($item)
{
    $text;
    switch ($item) {
        case "1_0_s_convertSolrDoc":
            $text = "转换solr文档";
            break;
        case "1_1_s_search":
            $text = "查询文档是否存在";
            break;
        case "1_2_s_search_get":
            $text = "查询并获取文档内容";
            break;
        case "2_s_handleLuceneDoc":
            $text = "处理lucene文档";
            break;
        case "3_s_updateSearcher":
            $text = "更新searcher";
            break;
        case "1_0_l_processDoc":
            $text = "处理文档";
            break;
        case "1_1_l_addIndex":
            $text = "添加到索引域";
            break;
        case "1_2_l_addStore":
            $text = "添加到存储域";
            break;
        case "2_l_commit_flush":
            $text = "将文档写入磁盘";
            break;
        case "3_l_commit_merge":
            $text = "合并";
            break;
        case "4_l_commit_internal":
            $text = "同步文件";
            break;
        case "1_la_fudan":
            $text = "复旦分词";
            break;
        case "2_la_checkAccount":
            $text = "验证社交媒体账户";
            break;
        case "1_0_0_fd_POS_predict":
            $text = "提取词性";
            break;
        case "1_1_0_fd_doProcess":
            $text = "doProcess";
            break;
        case "1_1_1_fd_prePipe":
            $text = "词序转换";
            break;
        case "1_1_2_fd_folPipe":
            $text = "词典分词及提取特征";
            break;
        case "1_2_0_fd_predict":
            $text = "词性预测";
            break;
        case "1_2_1_fd_initialLattice":
            $text = "initialLattice";
            break;
        case "1_2_2_fd_doForwardViterbi":
            $text = "doForwardViterbi";
            break;
        case "1_2_3_fd_getPath":
            $text = "getPath";
            break;
        case "2_0_fd_parser_predict":
            $text = "提取依赖关系";
            break;
        case "2_1_fd_createSentence":
            $text = "createSentence";
            break;
        case "2_2_fd_getBest":
            $text = "getBest";
            break;
        case "3_fd_model_process":
            $text = "复旦分词后";
            break;
        default:
            $text = $item;
            break;
    }

    return $text;
}

/**
 *
 * 更新微博的转发数、评论数.调用solr时，传递  guid（sourceid_id）、comments_count、reposts_count
 * @param $counts 对象数组 {id,comments,rt, attitudes} 微博ID，评论数，转发数, 赞
 * @param $sourceid 数据源
 * @param $updatesolr 是否更新solr
 * @param $old_counts_info 当前转发数、评论数，以微博id为key 的数组对象
 */
function update_status_counts($counts, $sourceid, $updatesolr = true, $old_counts_info = NULL)
{
    global $dsql, $logger, $solr_update_time, $solr_update_count, $sql_updatecounts_time;
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    if ($updatesolr) {
        $postdata = array();
        for ($i = 0; $i < count($counts); $i++) {
            $postdata[] = array("guid" => $counts[$i]['guid'], "comments_count" => $counts[$i]['comments'], "reposts_count" => $counts[$i]['rt'], "praises_count" => $counts[$i]['attitudes']);
        }
        $start_time = microtime_float();
        $logger->info(__FILE__ . __LINE__ . " calls solr update. ");
        $result = handle_solr_data($postdata, SOLR_URL_UPDATE);
        unset($postdata);
        $end_time = microtime_float();
        $timediff = $end_time - $start_time;
        $logger->debug("update_status_counts 调用solr更新数据" . count($postdata) . "条，花费时间：{$timediff}");
        $solr_update_time += $timediff;
        $solr_update_count++;
    } else {
        $result = NULL;
    }
    if ($result === NULL) {//chenggong
        $analysis_time_sql = $updatesolr ? ", analysis_time=" . time() : "";
        $start_time = microtime_float();
        foreach ($counts as $key => $value) {
            //判断数据库中当前转发数、评论数是否与新获取的相同，相同则不更新数据库
            if (isset($old_counts_info) && isset($old_counts_info[$value['guid']])
                && $old_counts_info[$value['guid']]['comments_count'] == $value['comments']
                && $old_counts_info[$value['guid']]['reposts_count'] == $value['rt']
            ) {
                continue;
            }
            if (!empty($value['mid'])) {
                $wh = "mid='{$value['mid']}'";
            } else {
                $wh = "id='{$value['id']}'";
            }
            $sql = "update " . DATABASE_WEIBO . " set comments_count = {$value['comments']}, reposts_count = {$value['rt']},
                direct_reposts_count=IF(is_repost,{$value['rt']},direct_reposts_count), total_reposts_count = {$value['rt']},
                repost_trend_update = " . time() . $analysis_time_sql . " where {$wh} and sourceid = {$sourceid}";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $logger->error("update_status_counts sql:{$sql} error:" . $dsql->GetError());
                $result = false;
                break;
            }
            $dsql->FreeResult($qr);
        }
        $end_time = microtime_float();
        $timediff = $end_time - $start_time;
        $logger->debug(__FILE__ . __LINE__ . " update sql time:" . $timediff);
        $sql_updatecounts_time += $timediff;
    }
    if ($result === false) {
        return false;
    } else {
        return true;
    }
}

/**
 *
 * 插入被删除的微博
 * @param object array $weibos 微博数组
 */
function insert_deleted_weibo($weibos, $sourceid)
{
    global $logger, $dsql;
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    $logger->debug(__FUNCTION__ . " " . var_export($weibos, true));
    if (!empty($weibos)) {
        foreach ($weibos as $key => $value) {
            $created_at = strtotime($value['created_at']);
            $sql = "insert into " . DATABASE_WEIBO . " (id,mid,created_at,sourceid) values('{$value['id']}','{$value['mid']}',{$created_at},{$sourceid})";
            $qr = $dsql->ExecQuery($sql);
            if (!$qr) {
                $dsql->FreeResult($qr);
                $logger->error(__FILE__ . __LINE__ . " sql is {$sql} has error:" . $dsql->GetError());
                return false;
            }
            $dsql->FreeResult($qr);
        }
    }
    return true;
}

/**
 *
 * 删除数据库中的微博
 * @param $ids 逗号分隔的id字符串，id使用单引号
 * @param $sourceid
 */
function deleteWeibo($ids, $sourceid, $mids = false, $guids = false,&$timeStatisticObj=null)
{
    global $logger, $dsql;
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    $wh = "";
    if (!empty($ids) && !empty($mids)) {
        $wh = " (id in ({$ids}) or mid in ({$mids}))";
    } else if (!empty($ids)) {
        $wh = " id in ({$ids})";
    } else if (!empty($mids)) {
        $wh = " mid in ({$mids})";
    } else if (!empty($guids)) {
        $wh = " guid in ({$mids})";
    } else {
        return false;
    }
    $delsql = "delete from weibo_new where {$wh} and sourceid = {$sourceid}";

    $sqlstart_time = microtime_float();
    $qr = $dsql->ExecNoneQuery($delsql);
    $sqlend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,DB_DELET_TIME_KEY,$sqlend_time - $sqlstart_time);

    if (!$qr) {
        $logger->error("insert_status 删除微博异常,sql:{$delsql} " . $dsql->GetError());
    }
}

//把换行符\r\n转换成BR
function transferToBR($data)
{
    $pattern = "/[\r\n]+/";
    $replacement = "<BR/>";
    return preg_replace($pattern, $replacement, $data);
}

//把换行符BR转换成\r\n
function transferToRN($data)
{
    $pattern = "/<BR>|<BR\/>/";
    $replacement = "\r\n";
    return preg_replace($pattern, $replacement, $data);
}

/**
 *
 * 生成存储的微博数据,只处理分词后返回的字段
 * @param $weibo_infos 引用类型，微博对象数组，包含不需要分析的字段
 * @param $analysisdata 分析出来的数据
 * @param $tokenize_fields 分词字段数组
 */
function formatStoreData(&$weibo_infos, &$analysisdata, $tokenize_fields)
{
    global $logger;
    if (empty($tokenize_fields)) {
        return false;
    }
    //现将个字段的结构转换
    //TODO 需要将这些字段进行动态配置
    $objfields = array("text", "pg_text", "post_title", "description", "verified_reason");
    //$logger->info(__FILE__ . __LINE__ . " formatStoreData, originalDoc:[" . var_export($weibo_infos, true)." analysisResult:" . var_export($analysisdata, true)." tokenize_fields:". var_export($tokenize_fields, true));

    foreach ($weibo_infos as $wkey => $weibo) {

        foreach ($objfields as $k => $v) {
            if (isset($weibo[$v])) {
                //add by wangcc 增加注释
                //solrstore需要的text格式为{content:"", terms:[]}
                //将原文内容赋值给content字段
                //新抓取的数据text字段为字符串，重新分析时，text字段从solrstore读取的，为数组
                //$content = is_array($weibo[$v]) ? (count($weibo[$v]) > 0 ? $weibo[$v][0] : '') : $weibo[$v];
                //
                // 1.第一种情况filed为单值的情况:
                // filedname -----filedValue--+--"xxxxxxxxx"
                //                            |
                //                            +--content--+--"xxxxxxxx"
                //2.如果为多值的情况下：
                //filedname -----filedValue---+
                //                            |
                //                          0-+--"xxxxxxxx"
                //
                //需要将上述结构转化为下面的结构，才能存储到solr中
                //filedname -----filedValue---+--"content"--+--"xxxxxxx"
                //                            |
                //                            +--"terms"--+
                //                                        |

                //调用insert_status2之前已格式化
                $content = '';
                if (isset($weibo[$v]['content'])) {
                    $content = $weibo[$v]['content'];
                } else if (isset($weibo[$v][0])) {
                    $content = $weibo[$v][0];
                }
                $content = empty($content) ? '' : $content;
                //将分词工具返回的数据中 text字段（分词结果）赋值给terms
                $weibo_infos[$wkey][$v] = array("content" => $content, "terms" => array());
            }
        }

        if ((in_array("text", $tokenize_fields) && !empty($weibo_infos[$wkey]['text']['content']))
            || (in_array("pg_text", $tokenize_fields) && !empty($weibo_infos[$wkey]['pg_text']['content']))
        ) {
            $text_fields = array("text", "pg_text", "NRN", "emoNRN", "district", "emoDistrict", "city", "emoCity", "province", "emoProvince", "country", "emoCountry", "business", "emoBusiness", "combinWord", "emoCombin", "organization", "emoOrganization", "emotion", "wb_topic", "emoTopic", "wb_topic_keyword", "emoTopicKeyword", "wb_topic_combinWord", "emoTopicCombinWord", "url", "host_domain", "account", "emoAccount");

            //分析的结果数据
            $analysis_item = array_shift($analysisdata);
            //$logger->debug(__FILE__.__LINE__." analysis_item ".var_export($analysis_item, true));

            foreach ($text_fields as $k => $v) {
                if ($v == "text" || $v == "pg_text") {
                    if (!empty($weibo_infos[$wkey][$v]['content'])) {//不为空的才分词
                        $weibo_infos[$wkey][$v]['content'] = transferToRN($weibo_infos[$wkey][$v]['content']);
                        if (empty($analysis_item['text'])) {
                            //$logger->warn("weibo {$weibo_infos[$wkey]['guid']} text:{$weibo_infos[$wkey][$v]['content']} 分词失败");
                            $terms = array();
                        } else {
                            $terms = $analysis_item['text'];
                        }
                        //$terms = !empty($analysis_item['text']) ? $analysis_item['text'] : array();
                        $weibo_infos[$wkey][$v]['terms'] = $terms;
                        //similar生成MD5
                        $keywordstr = '';
                        foreach ($terms as $tmi => $tmv) {
                            $keywordstr .= $tmv['text'];
                        }

                        //给当前微博 ($weibo) 增加属性:similar 表示当前微博的散列值:及所有分词的term的MD5值
                        $weibo_infos[$wkey]['similar'] = array(md5($keywordstr));//多值的转成数组
                    }
                } else {
                    $weibo_infos[$wkey][$v] = isset($analysis_item[$v]) ? $analysis_item[$v] : '';
                }
            }
            $repostStart = false;
            if (isset($weibo_infos[$wkey]['text']) && $weibo_infos[$wkey]['content_type'] == 1) {
                $repostStart = strpos($weibo_infos[$wkey]['text']['content'], '//@');
            }
            if ($repostStart !== false) {
                $weibo_infos[$wkey]['ancestor_text']['content'] = substr($weibo_infos[$wkey]['text']['content'], $repostStart);
                $analysis_item = array_shift($analysisdata);
                foreach ($text_fields as $k => $v) {
                    $f = 'ancestor_' . $v;
                    if ($v == "text") {
                        if (empty($analysis_item['text'])) {
                            $terms = array();
                        } else {
                            $terms = $analysis_item['text'];
                        }
                        $weibo_infos[$wkey][$f]['terms'] = $terms;
                        $keywordstr = '';
                        foreach ($terms as $tmi => $tmv) {
                            $keywordstr .= $tmv['text'];
                        }
                        $weibo_infos[$wkey]['ancestor_similar'] = array(md5($keywordstr));//多值的转成数组
                    } else {
                        $weibo_infos[$wkey][$f] = isset($analysis_item[$v]) ? $analysis_item[$v] : '';
                    }
                }
            }
        }

        if (in_array("post_title", $tokenize_fields)) {
            if (!empty($weibo_infos[$wkey]["post_title"]['content'])) {//不为空的才分词
                $analysis_item = array_shift($analysisdata);
                if (empty($analysis_item['text'])) {
                    //$logger->warn("weibo {$weibo_infos[$wkey]['guid']} post_title:{$weibo_infos[$wkey]['post_title']['content']} 分词失败");
                    $terms = array();
                } else {
                    $terms = $analysis_item['text'];
                }
                //$terms = !empty($analysis_item['text']) ? $analysis_item['text'] : array();
                $weibo_infos[$wkey]['post_title']['terms'] = $terms;
            }
        }
        if (in_array("verified_reason", $tokenize_fields)) {
            if (!empty($weibo_infos[$wkey]["verified_reason"]['content'])) {//不为空的才分词
                $analysis_item = array_shift($analysisdata);
                if (empty($analysis_item['text'])) {
                    //$logger->warn("weibo {$weibo_infos[$wkey]['guid']} verified_reason:{$weibo_infos[$wkey]['verified_reason']['content']} 分词失败");
                    $terms = array();
                } else {
                    $terms = $analysis_item['text'];
                }
                //$terms = !empty($analysis_item['text']) ? $analysis_item['text'] : array();
                $weibo_infos[$wkey]['verified_reason']['terms'] = $terms;
            }
        }
        if (in_array("description", $tokenize_fields)) {
            if (!empty($weibo_infos[$wkey]["description"]['content'])) {//不为空的才分词
                $analysis_item = array_shift($analysisdata);
                if (empty($analysis_item['text'])) {
                    //$logger->warn("weibo {$weibo_infos[$wkey]['guid']} description:{$weibo_infos[$wkey]['description']['content']} 分词失败");
                    $terms = array();
                } else {
                    $terms = $analysis_item['text'];
                }
                //$terms = !empty($analysis_item['text']) ? $analysis_item['text'] : array();
                $weibo_infos[$wkey]['description']['terms'] = $terms;
            }
        }
        //删除临时数据
        $delprop = array("orig_emotion", "orig_business", "orig_emoBusiness");
        foreach ($delprop as $k => $v) {
            if (isset($weibo_infos[$wkey][$v])) {
                unset($weibo_infos[$wkey][$v]);
            }
        }
    }
}

/*提交有嵌套结构的数据,例如:转发文章包含对应的原创结构
 * 提交的数据
 * {
 *   "partialdata": true/false 提供的数据是否是完整数据,一篇主帖包含所有的跟贴
 *   "data": [] //提供的数据数组
 * }
 * */

function insert_nested_data($dataobj, $issegmented = false, $statistics_to = 'task', $timeline_type = 'show_status', $sourceid = NULL, $maxtime = NULL, $mintime = NULL, $isseed = false)
{
    global $logger;
    $oriarr = array();
    $arr = array();
    foreach ($dataobj['data'] as $di => $ditem) {
//        $tmp = splitNestedData($dataobj['data'][$di]);
        $result = splitNestedData($dataobj['data'][$di]);
        if (!empty($result['0'])) {
            $arr[] = $result['0'];
        }
        $tmp = $result['1'];
        if (!empty($tmp)) {
            $oriarr[] = $tmp;
            if (!empty($result['2'])) {
                $oriarr[] = $result['2'];
            }
        }
        supplyContentType($dataobj['data'][$di]);
    }

    //  返回数据格式为① n级评论
    //                   status原创
    //                  reply_comment{（n-1）级评论}
    //                ② (n-1)级评论
    //                    status原创
    //规则，入库的时候，先查询有没有原创，在查询父级是否存在
    //   如同上面的数据格式，先把下面的（n-1）级评论组合成$arr,并在上面的$dataobj['data']中把reply_comment部分删除
    //   循环两个数组，判断数据类型①中的reply_comment中的id是否存在$dataobj[$key]['id'],如果存在，在$arr中删除这个值，避免重复入库
    //   如果他的父级没有在这一次同时返回，则把父级father_id设为原创id，下次返回的数据有这个father_id,则执行更新
    foreach ($arr as $k => $v) {
        foreach ($dataobj['data'] as $key => $value) {
            if ($value['id'] == $v['id']) {
                $logger->debug(__FILE__ . __LINE__ . "id相同的为" . var_export($v['id'], true));
                unset($arr[$k]);
            }
        }
    }
    //$logger->debug(__FILE__.__LINE__."sssss".var_export($oriarr, true));
    //处理原创
    if (count($oriarr) > 0) {
        $oriidarr = array();
        foreach ($oriarr as $oi => $oitem) {
            if (!in_array($oitem['mid'], $oriidarr)) {
                $oitem['content_type'] = 0;
                $dataobj['data'][] = $oitem;
                $oriidarr[] = $oitem['mid'];
            }
        }
        unset($oriidarr);
        unset($oriarr);
    }

    if (!empty($arr)) {
        $relplace = $dataobj['data'];
        $dataobj['data'] = array_reverse($relplace);
        $sui = array_merge($arr, $dataobj['data']);
        $dataobj['data'] = $sui;
    }

    return insert_status2($dataobj, $timeline_type, $sourceid, $maxtime, $mintime, $isseed, $issegmented, $statistics_to);
}

/*提交的数据是扁平结构, 只包含原创的id或父级的id
  * 提交的数据
  {
    "partialdata": true/false 提供的数据是否是完整数据,一篇主帖包含所有的跟贴
    "data": [] //提供的数据数组
  }
 * */
function insert_data($dataobj, $issegmented = false, $statistics_to = 'task', $timeline_type = 'show_status', $sourceid = NULL, $maxtime = NULL, $mintime = NULL, $isseed = false, $isDistributedTask = false,&$timeStatisticObj=null)
{
    global $logger;
    foreach ($dataobj['data'] as $di => $ditem) {
        supplyContentType($dataobj['data'][$di]);
        //重新分析转发轨迹，拼装正确的父级guid
        if (isset($dataobj['data'][$di]['pid'])) {
            if ($dataobj['data'][$di]['sourceid'] == "1") {
                $dataobj['data'][$di]['reply_father_mid'] = $dataobj['data'][$di]['pid'];
                $dataobj['data'][$di]['father_guid'] = "1_".$dataobj['data'][$di]['pid'];
            }
        }

    }
    return insert_status2($dataobj, $timeline_type, $sourceid, $maxtime, $mintime, $isseed, $issegmented, $statistics_to, $isDistributedTask,$timeStatisticObj);
}

/**
 * 判断当前文章是否需要删除
 * @param $curArticle ：当前文章信息，来源于爬虫提交上来到数据，或者通过外部接口获过来的数据
 * @param $task ：当前任务配置实体对象，来自数据库查找出来的，里面存储了任务参数配置
 */
function isCurDel(&$curArticle, &$task)
{
//    !empty($weibo_info['deleted']) || ( && isset($weibo_info['user']) && empty($weibo_info['user']))
    if (isCommonTask($task)) {
        //通用类型的任务  $statistics_info->taskparams->scene->deleted_weibocount++;
        return false;
    } else {
        //以前旧的任务类型,当获取到的文章信息里面有[""]
        if (!empty($curArticle['deleted']) || (isset($curArticle['user']) && empty($curArticle['user']))) {
            return true;
        }
        return false;
    }
}

/**
 * 当前文章被删除时候，在任务参数中统计当前任务下面的总共删除的文章数，每删除一个文章，将该计数增加一
 * @param $curArticle ：当前文章
 * @param $task ：当前任务实体
 */
function del4ArticleAddDelNum(&$curArticle, &$task, &$deletedcount, &$deleted_weibos)
{
    global $logger;

    $logger->debug(__FILE__ . __FUNCTION__ . __LINE__ . " del4ArticleAddDelNum ...");
    if (isCommonTask($task)) { //->scene
        $sceneParentParam = &$task->taskparams->root->runTimeParam;
    } else {
        $sceneParentParam = &$task->taskparams;
    }

    if (empty($sceneParentParam->scene->deleted_weibocount)) {
        $sceneParentParam->scene->deleted_weibocount = 0;
    }
    $sceneParentParam->scene->deleted_weibocount++;

    if (empty($sceneParentParam->scene->deleted_counts)) {
        $sceneParentParam->scene->deleted_counts = array();
    }
    if (empty($sceneParentParam->select_cursor)) {
        $sceneParentParam->select_cursor = 0; //第三方数据时,不需要
    }
    $sceneParentParam->scene->deleted_counts[$sceneParentParam->select_cursor]++;//分别记录每个原创的删除数
    $deletedcount++;
    if (isset($curArticle['created_at'])) {
        $deleted_weibos[] = $curArticle;
    } else {
        if (empty($sceneParentParam->scene->deleted_noinsert)) {
            $sceneParentParam->scene->deleted_noinsert = 0;
        }
        $sceneParentParam->scene->deleted_noinsert++;//被删除的且未入库的
    }
    $logger->debug(__FILE__ . __FUNCTION__ . __LINE__ . " del4ArticleAddDelNum ok! allParam: " . var_export($sceneParentParam, true));
}


function isNeedAddUser(&$task)
{
    global $logger;
//    $logger->debug(__FILE__ . __FUNCTION__ . __LINE__ . " isNeedAddUser ... taskObj: " . var_export($task, true));
    if (isCommonTask($task)) {
//        $logger->debug(__FILE__ . __FUNCTION__ . __LINE__ . " isNeedAddUser for commom task...");
        if (isset($task->taskparams->root->taskPro->addUser) && $task->taskparams->root->taskPro->addUser == true) {
            return true;
        }
        return false;
    } else {
//        $logger->debug(__FILE__ . __FUNCTION__ . __LINE__ . " isNeedAddUser for old task:[true]");
        return true;
    }
}

function getDefaultUserPathId()
{
    $result = array();
    $result['col_name'] = 'user';
    $result['paramSource'] = '';
    return $result;
}


$orig_infos_cache;

function &getDictionaryPlan($isDistributedTask)
{
    global $dictionaryPlan, $logger, $taskID;
    if ($isDistributedTask) {
        if (empty($dictionaryPlan)) {
            throw new Exception(" insert data exception:[dictionaryPlan is null for distributedTask].");
        }
    } else {
        //if(!$issegmented){
        if (!empty($task) && !empty($task->id) && $task->id > -1) {
            //获取方案
            $taskID = $task->id;
            //从数据库获取任务方案
            $dictionaryPlan = queryDictionaryPlan($taskID);
            //跟新任务id
            //$taskID=$tempid;
            $logger->debug('分词方案：' . var_export($dictionaryPlan, true));
        } else if ($taskID != -1) {
            $dictionaryPlan = queryDictionaryPlan($taskID);
        } else {
            $dictionaryPlan = formatDictionaryPlan($dictionaryPlan);
            //$logger->degug('对植入微博分词'.var_export($dictionaryPlan,true)."id".var_export($taskID,true));
        }
        //}
    }
    return $dictionaryPlan;
}

function getCurrentInsertDataIsCommit(&$statistics_info, $isDistributedTask)
{
    global $dsql, $logger, $task;
    $isCommomTaskStr = isCommonTask($statistics_info);
    $iscommit = true;
    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-判断当前提交数据时候是否[commit]. 是否是分布式任务:[" . ($isDistributedTask ? "是" : "否") . "] 是否是通用任务:[" . ($isCommomTaskStr ? "是" : "否") . "].");
    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-判断当前提交数据时候是否[commit]. taskInfo:" . var_export($statistics_info, true));

    if ($isCommomTaskStr || $isDistributedTask) {
        if (!isset($statistics_info->taskparams->root->taskPro->iscommit)) {
            $statistics_info->taskparams->root->taskPro->iscommit = true;
            $iscommit = true;
        } else {
            $iscommit = $statistics_info->taskparams->root->taskPro->iscommit;
        }
    } else {
        if (!isset($statistics_info->taskparams->iscommit)) {
            $statistics_info->taskparams->iscommit = true;
            $iscommit = true;
        } else {
            $iscommit = $statistics_info->taskparams->iscommit;
        }
    }
    return $iscommit;
}

/**
 *
 * 新版：插入微博
 * @param $ms
 * @param $timeline_type
 * @param $sourceid //TODO  第三方没有sourceid参数需要处理
 * @param $maxtime
 * @param $mintime
 * @param $isseed 是否种子
 * @param $issegmented 是否已分词
 * @param $statistics_info 统计信息
 * @param $isDistributedTask :是否是分布式任务（add by wangcc） 该参数用于在系统升级时候 将任务执行和 数据入库 分成两个不同的部分
 *      进行；先将任务执行的数据写入到缓存中 在从缓存中定时/任务 将缓存中的数据同步到solr中，在数据缓存阶段需要保证任务的完成，任务完成
 *      后任务被删除。 在数据入库阶段该任务将不存在，所以分词计划 以及 task对象将不能从数据库中还原 故增加该参数来指定是否从数据库中还原
 *      任务
 */
function insert_status2($ms, $timeline_type, $sourceid, $maxtime = NULL, $mintime = NULL, $isseed = false, $issegmented = false, $statistics_to = 'task', $isDistributedTask = false,&$timeStatisticObj=null)
{
    //两个全局变量记录用户更新状态，仅用于抓取用户微博（user_timeline）时，微博带下来的用户都一样，所以用户只更新一次
    global $seeduserupdated, $preseeduserid;
    global $dictionaryPlan, $taskID;
    $fun_starttime = microtime_float();
    //插入数据库的总时间，分析花费的总时间，本函数花费总时间,总抓取条数，总新增条数,总错误数,总新抓取用户,插入用户总时间
    global $dsql, $logger, $task, $statistics_info, $insertweibotime, $analysistime, $funtime, $spidercount, $newcount, $solrerrorcount,
           $spiderusercount, $insertusertime, $apicount, $apierrorcount, $updateusercount;
    global $orig_infos_cache, $global_usercache;//存放原创的缓存
    global $OriginalIdArray;    //原创id数组
    //防止内存过大，每次清空用户缓存
    if (count($global_usercache) > 10)
        $global_usercache = array();
    foreach ($global_usercache as $k => $cache) {
        if (count($cache) > 100)
            unset($global_usercache[$k]);
    }
    if (count($orig_infos_cache) > 100)
        $orig_infos_cache = array();
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    //获取方案 如果任务id未空 是植入 就直接使用$dictionaryPlan，
    //$logger->degug('对植入微博分词'.var_export($dictionaryPlan,true)."id".var_export($taskID,true));

    $result = array("result" => true, "msg" => "");

    //获取分词计划
    if(!$issegmented){
        $dictionaryPlan = &getDictionaryPlan($isDistributedTask);
    }

    if (!empty($task)) {
        $statistics_info = $task;
    } else {
        throw new Exception("current task is null!");
    }
    //获取 $sceneParentParam
    if (isCommonTask($statistics_info)) { //->scene
        $sceneParentParam = &$statistics_info->taskparams->root->runTimeParam;
    } else {
        $sceneParentParam = &$statistics_info->taskparams;
    }

    if (empty($sceneParentParam->scene->deleted_noinsert)) {
        $sceneParentParam->scene->deleted_noinsert = 0;
    }
    if (!isset($sceneParentParam->scene->solr_count)) {
        $sceneParentParam->scene->solr_count = 0;
    }


    //$logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "测试代码: timeStatisticObj:[" . var_export($timeStatisticObj,true) . "].");

    // ************************************* 根据任务类型 来构造 solr 请求的url ************************************* //
    $iscommit = getCurrentInsertDataIsCommit($statistics_info, $isDistributedTask);
    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-判断当前提交数据时候是否[commit]. 是否提交数据:[" . ($iscommit ? "是" : "否") . "].");
    // ************************************* 根据任务类型 来构造 solr 请求的url ************************************* //

    //$tokenfields = array("text", "pg_text", "post_title", "description", "verified_reason");
    //modified by wangcc 增加电商中需要分词的字段: serviceComment(对服务的评论) apdComment(追平) productDesc(商品描述)
    $tokenfields = array("text", "pg_text", "post_title", "description", "verified_reason", "serviceComment", "apdComment", "productDesc");

    //该批入库的文章中 有可能多个文章(评论、恢复等)包含的用户一样 所以该对象用于缓存用户
    $userdatas = array();

    $onscount = count($ms['data']);//本次新抓条数
    $spidercount += $onscount;//总新抓取
    if (empty($sceneParentParam->scene->spider_statuscount)) {
        $sceneParentParam->scene->spider_statuscount = 0;
    }
    $sceneParentParam->scene->spider_statuscount += $onscount;//总抓取条数
    $allids = "";//存放新增的微博id，当调用solr失败时，从数据库中删除
    $allmids = "";
    $allorigids = "";//本次新增的转发中的原创ID
    $allorigmids = "";//本次新增的转发中的原创MID
    $logappend = "";
    $solr_analysistime = 0;
    $deletedcount = 0;
    $markedDeleted_weibos = array();

    $partialdata = isset($ms['ispartialdata']) ? $ms['ispartialdata'] : false;
    $ms = $ms['data'];

    //提交的数据里面是否包含用户信息?旧版本都需要添加；新版本的需要根据taskPro属性中的addUser来进一步判断
    $needAddUser = isNeedAddUser($statistics_info);
    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-是否需要提取用户信息:[" . ($needAddUser ? "需要" : "不需要") . "].");

    $curUserPath = null;

    //原创文章
    $ocstart_time = microtime_float();
    $tmp_orig_ms = array();
    foreach ($ms as $k => $weibo_info) {
        $nedDel = isCurDel($weibo_info, $statistics_info);
        if ($nedDel) {
            //当文章被删除时候更新任务参数下 统计的当前任务中被删除的原创文章个数/计数
            del4ArticleAddDelNum($weibo_info, $statistics_info, $deletedcount, $markedDeleted_weibos);
            array_splice($ms, $k, 1);
            continue;
        }
        //先插原创 content_type{0:原创;  1:转发;  2:评论;  3:}
        if ($weibo_info['content_type'] == 0 || ($weibo_info['content_type'] == 3 && $weibo_info['question_id'] == 0)) {
            $addorg = true;
            //判断是否已处理过该原创
            foreach ($tmp_orig_ms as $v) {
                $chkid = "";
                if (!empty($weibo_info['id'])) {
                    $chkid = 'id';
                } else if (isset($weibo_info['mid'])) {
                    $chkid = 'mid';
                } else if (isset($weibo_info['original_url'])) {
                    $chkid = 'original_url';
                }
                if (!empty($chkid) && $v[$chkid] == $weibo_info[$chkid]) {
                    $addorg = false;
                    break;
                }
            }
            if ($addorg) {
                $tmp_orig_ms[] = $weibo_info;
                //  因为入库方式的变化。加入原创数组，记录抓取数据中的原创的id，把去查solr的操作，变成查数组  start
                if(in_array($weibo_info['id'],$OriginalIdArray)){
                    $logger->debug("原创是存在的");
                }else{
                    $logger->debug("原创是不存在的");
                    $OriginalIdArray[] = $weibo_info['id'];
                }
                // end    by  yu  2017/3/23
                $logger->debug(__FUNCTION__.__FILE__.__LINE__."the OriginalIdArray info is:".var_export($OriginalIdArray,true));
                unset($ms[$k]);
            }
        }

        // ***************************************** 处理用户 ****************************************//
        if (!$needAddUser) {
            //如果任务(即通用任务)中配置的时候 明确指明了不需要处理用户信息 则在这里不处理用户
            continue;
        }
        if ($timeline_type != 'user_timeline') {
            $adduser = true;
            if (isCommonTask($statistics_info)) {
                if (!$curUserPath) {
                    //获取用户信息路径，根据配置
                    if (empty($statistics_info->taskparams->root->taskPro->userPathId)) {
                        $curUserPath = getDefaultUserPathId();
                    } else {
                        $userPathId = $statistics_info->taskparams->root->taskPro->userPathId;
                        if (!strpos($userPathId, '|') === 0) {
                            throw new Error("Illegal [taskPro->userPathId] :[{$userPathId}]");
                        }
                        $userPathId = substr($userPathId, 1, strlen($userPathId) - 2);
                        $allParamMap = $statistics_info->taskparams->root->pathStructMap;
                        $allParamMap = json_encode($allParamMap);
                        $allParamMap = json_decode($allParamMap, true);
                        $curUserPath = $allParamMap[$userPathId];
                    }
                    if (empty($curUserPath)) {
                        throw new Error("can not get UserPath for grabData!");
                    }
                }
                $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取用户+--用户数据路径:" . var_export($curUserPath, true));
                $userInfo = getValueFromObjWrap($weibo_info, $curUserPath,false);
                if (!empty($userInfo)) {
                    //判断当前缓存中是否已经存在该用户信息
                    foreach ($userdatas as $userk => $v) {
                        if (isset($userInfo['id']) && isset($v['id']) && $v['id'] == $userInfo['id']) {
                            $adduser = false;
                            break;
                        }
                    }
                    if ($adduser) {
                        $userdatas[] = $userInfo;
                    }
                }else{
                    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取用户+--根据用户数据路径获取用户数据为空!");
                }
            } else {
                //通过抓取方式 获取的数据 处理用户逻辑
                foreach ($userdatas as $userk => $v) {
                    if (isset($weibo_info['user']) && isset($weibo_info['user']['id']) && isset($v['id']) && $v['id'] == $weibo_info['user']['id']) {
                        $adduser = false;
                        break;
                    }
                }
                if ($adduser && isset($weibo_info['user'])) {
                    $userdatas[] = $weibo_info['user'];
                }
            }
        } else {
            //通过微博接口抓取数据,每次获取到的数据集合的所有的用户都是一样的，所以值针对该批数据，插入用户一次
            ////$preseeduserid $seeduserupdated 两个全局变量记录用户更新状态，仅用于抓取用户微博（user_timeline）时，微博带下来的用户都一样，所以用户只更新一次
            if (empty($preseeduserid) || $preseeduserid != $weibo_info['user']['id']) {
                $preseeduserid = $weibo_info['user']['id'];
                $seeduserupdated = false;
            }
            if (empty($seeduserupdated)) {
                $userdatas[] = $weibo_info['user'];
                $seeduserupdated = true;
            }
        }
    }
    $ocend_time = microtime_float();
    $fetchUserCountTime = $ocend_time - $ocstart_time;
    addTime4Statistic($timeStatisticObj,FETCH_USER,$fetchUserCountTime);

    $solrinfo = '';
    // ************************************* 插入用户 ************************************* //
    $userDataNum = count($userdatas);
    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取用户:[" . $userDataNum . "]个. AllUsers: " . var_export($userdatas, true));
    $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取用户:[" . $userDataNum . "]个.");

    $start_time = microtime_float();
    $insertuserdiff = 0;
    $newusercount = 0;
    $upusercount = 0;
    if (!empty($userdatas) && $needAddUser) {
        $r_iu = insert_user($userdatas, $timeline_type, $sourceid, 0, $iscommit, NULL, false, $issegmented, $statistics_to, $isDistributedTask,$timeStatisticObj);
        $end_time = microtime_float();
        unset($userdatas);
        $insertuserdiff = $end_time - $start_time;//插入用户时间

        if ($r_iu['result'] === false) {
            $result['result'] = false;
            $result['msg'] = $r_iu['msg'];
            return $result;
        }
        $newusercount = $r_iu['newcount'];
        $upusercount = $r_iu['updatecount'];
        $spiderusercount += $newusercount;
        $updateusercount += $upusercount;
    } else {
        $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-插入用户--+-用户数据为空或者不需要提取用户.");
    }
    addTime4Statistic($timeStatisticObj,HANDLE_USER_SUM,$insertuserdiff);
    // ************************************* 插入用户 ************************************* //


    // ***************************** 将被标记为删除状态的微博的插入到mysql数据库中 *****************************//
    if (!empty($markedDeleted_weibos)) {
        $start_time = microtime_float();
        $in_delr = insert_deleted_weibo($markedDeleted_weibos, $sourceid);
        $end_time = microtime_float();
        $insertdiff = $end_time - $start_time;
        $insertweibotime += $insertdiff;
        addTime4Statistic($timeStatisticObj,INSER_DELETED_WEIBO,$insertweibotime);

        if ($in_delr === false) {
            //return false;
            $result['result'] = false;
            $result['msg'] = '插入文章时，插入[需要删除]的文章失败';
            return $result;
        }
    }
    // ***************************** 将被标记为删除状态的微博的插入到mysql数据库中 *****************************//

    //************************************************   插入原创  ***********************************************************************//
    $oc = 0;
    $origcount = count($tmp_orig_ms);
    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取原创文章:[" . $origcount . "]个. AllDOc: " . var_export($tmp_orig_ms, true));
    $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取原创文章:[" . $origcount . "]个.");
    $time_handle_org = 0;
    $solr_orig_infos = array();
    $update_solr_infos = array();//需要更新的内容
    $delete_solr_infos = array();

    $start_time = microtime_float();
    if ($origcount) {
        //向solr中插入文章
        $insert_r = inner_insert_status($tmp_orig_ms, $timeline_type, $sourceid, true, $maxtime, $mintime, $isseed, $issegmented, $statistics_to, $partialdata,$timeStatisticObj);
        if ($insert_r['result'] === false) {
            $result['result'] = false;
            $result['msg'] = $insert_r['msg'];
            return $result;
        }
        //$logger->debug(__FUNCTION__." YUANCHUANG--:".var_export($insert_r,true));
        $solr_orig_infos = $insert_r['send_solr_data'];
        $update_solr_infos = $insert_r['update_solr_data'];
        $delete_solr_infos = $insert_r['delete_solr_data'];
        $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理原创文章--+-需要新入库:[" . count($solr_orig_infos) . "条. 需要更新:[" . count($update_solr_infos) . "条. 需要删除:[" . count($delete_solr_infos) . "] 条.");

        $oc = count($solr_orig_infos);//新
        $newcount += $oc;
        if ($oc > 0) {
            foreach ($solr_orig_infos as $k => $v) {
                if (!empty($v['id'])) {
                    $allorigids .= "'" . $v['id'] . "',";
                } else if (isset($v['mid'])) {
                    $allorigmids .= "'" . $v['mid'] . "',";
                }
            }
            $allorigids = substr($allorigids, 0, -1);
            $allorigmids = substr($allorigmids, 0, -1);
            //对需要新入库的原创文章进行分词
            if (!$issegmented) {
//                $start_time = microtime_float();
                $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理原创文章--+-原创文章分词--+-使用分词方案: " . var_export($dictionaryPlan, true));
                if (!empty($dictionaryPlan)) {
                    $dictionaryPlan = formatDictionaryPlan($dictionaryPlan);
                }

                $solrstart_time = microtime_float();
                $ana_result = solr_analysis($solr_orig_infos, $tokenfields, $dictionaryPlan, false);//分析微博
                $solrend_time = microtime_float();
                //统计时间
                addTime4Statistic($timeStatisticObj,SOLR_NLP_TIME_KEY,$solrend_time - $solrstart_time);

//                $end_time = microtime_float();
//                $solr_analysistime = $end_time - $start_time;
                if (!$ana_result) {
                    $solrerrorcount += $oc;
                    $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理原创文章--+-原创文章分词失败. ErrorMsg:[" . var_export($ana_result, true) . "].");
                    deleteWeibo($allorigids, $sourceid, $allorigmids,false,$timeStatisticObj);
                    $result['result'] = false;
                    $result['msg'] = '--+-添加文章--+-数据入库--+-处理原创文章--+-原创文章分词失败';
                    return $result;
                } else {
                    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理原创文章--+-原创文章分词--+-分词成功!");
                    formatStoreData($solr_orig_infos, $ana_result, $tokenfields);//生成存储数据
                }
            }else{
                $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理原创文章--+-原创文章分词--+-文章已分词!");
            }
            //放入缓存
            foreach ($solr_orig_infos as $each_orig) {
                if (isset($each_orig['paragraphid']) && $each_orig['paragraphid'] != 0) {
                    $logger->debug("原创的paragraphid不为0，跳过该原创：" . var_export($each_orig, true));
                    continue;
                } else {
                    $orig_infos_cache[] = $each_orig;
                }
            }
            //入库
            $url = SOLR_URL_INSERT;
            if ($iscommit) {
                $url .= "&commit=true";
            } else {
                $url .= "&commit=false";
            }
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-原创文章入库solr--+-URL:[" . $url . "] 文章信息:[" . var_export($solr_orig_infos, true) . "].");

            $solrstart_time = microtime_float();
            $tmp_result = handle_solr_data($solr_orig_infos, $url);
            $solrend_time = microtime_float();
            //统计时间
            addTime4Statistic($timeStatisticObj,SOLR_INSERT_TIME_KEY,$solrend_time - $solrstart_time);

            if (empty($sceneParentParam->scene->solr_count)) {
                $sceneParentParam->scene->solr_count = 0;
            }
            $sceneParentParam->scene->solr_count++;

            $errorcount = 0;
            if ($tmp_result === false) {
                $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-原创文章入库solr失败:[调用solr异常!]");
                $errorcount = count($solr_orig_infos);
                $solrerrorcount += $errorcount;
                $strresult = "--+-添加文章--+-数据入库--+-原创文章入库--+-调用solr失败[返回false]";
            } else if ($tmp_result === NULL) {
                $strresult = "成功";
                $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-原创文章入库solr成功!");
            } else {
                $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-原创文章入库--+-调用solr失败,结果信息:" . var_export($tmp_result, true) . "].");
                $result['result'] = false;
                $result['msg'] = '--+-添加文章--+-数据入库--+-原创文章入库--+-调用solr失败';
                return $result;
                $errorcount = count($tmp_result);
                $strresult = "失败{$errorcount}条";
                $solrerrorcount += $errorcount;
            }
            //$solrinfo = "原创调用solr({$oc})条,{$strresult},花费时间：" . $analysisdiff;
            if (empty($sceneParentParam->scene->solrerrorcount)) {
                $sceneParentParam->scene->solrerrorcount = 0;
            }
//            $sceneParentParam->scene->solrerrorcount += $errorcount;
//            if (empty($sceneParentParam->scene->analysistime)) {
//                $sceneParentParam->scene->analysistime = 0;
//            }
//            $sceneParentParam->scene->analysistime += $solr_analysistime;
//            if (empty($sceneParentParam->scene->storetime)) {
//                $sceneParentParam->scene->storetime = 0;
//            }
            //$sceneParentParam->scene->storetime += $analysisdiff;
//            $logger->debug(__FILE__ . __LINE__ . " statistics_info " . var_export($statistics_info, true));
            if (empty($statistics_info->datastatus)) {
                $statistics_info->datastatus = 0;
            }
            $statistics_info->datastatus += ($oc - $errorcount);
        }
    }
    $end_time = microtime_float();
    $insertdiff = $end_time - $start_time;
    $time_handle_org += $insertdiff;
    addTime4Statistic($timeStatisticObj,HANDLE_ORG_DOC,$time_handle_org);
    //************************************************   插入原创 ok ***********************************************************************//

    //************************************************   处理转发/评论 ***********************************************************************//
    //处理转发 和 直接抓取得原创
    $time_handle_comment = 0;
    $start_time = microtime_float();
    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取非原创文章:[" . count($ms) . "]个,被标记为删除的文章:[" . $deletedcount . "]条. 所有非原创文章: " . var_export($ms, true));
    $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-提取非原创文章:[" . count($ms) . "]个.");

    $insert_r = inner_insert_status($ms, $timeline_type, $sourceid, true, $maxtime, $mintime, $isseed, $issegmented, $statistics_to, $partialdata,$timeStatisticObj);
    if ($insert_r['result'] === false) {
        $result['result'] = false;
        $result['msg'] = $insert_r['msg'];
        return $result;
    }
    $solr_weibos_info = $insert_r['send_solr_data'];
    $update_solr_infos = array_merge($update_solr_infos, $insert_r['update_solr_data']);
    $delete_solr_infos = array_merge($delete_solr_infos, $insert_r['delete_solr_data']);

    $c = count($solr_weibos_info);//成功入库条数
    $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-需要新入库:[" . ($c) . "条. 需要更新:[" . count($update_solr_infos) . "条. 需要删除:[" . count($delete_solr_infos) . "] 条.");

    $newcount += $c;
    $strresult = "";
    $errorids = "";
    $errormids = "";
    //新文章分词
    if ($c > 0) {
        $allids = "";
        $allmids = "";
        $allguids = "";
        foreach ($solr_weibos_info as $k => $v) {
            if (!empty($v['id'])) {
                $allids .= "'" . $v['id'] . "',";
            } else if (isset($v['mid'])) {
                $allmids .= "'" . $v['mid'] . "',";
            } else if (!empty($v['id'])) {
                $allguids .= "'" . $v['guid'] . "',";
            }
        }
        $allids = substr($allids, 0, -1);
        $allmids = substr($allmids, 0, -1);
        $allguids = substr($allguids, 0, -1);
        if (!$issegmented) {
            if (!empty($dictionaryPlan)) {
                $dictionaryPlan = formatDictionaryPlan($dictionaryPlan);
            }
            $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章分词--+-使用分词方案: " . var_export($dictionaryPlan, true));
            $solrstart_time = microtime_float();
            $ana_result = solr_analysis($solr_weibos_info, $tokenfields, $dictionaryPlan, false);//分析微博
            $solrend_time = microtime_float();
            // $solr_analysistime = $end_time - $start_time;
            //统计时间
            addTime4Statistic($timeStatisticObj,SOLR_NLP_TIME_KEY,$solrend_time - $solrstart_time);

            if (!$ana_result) {
                $solrerrorcount += $c;
                $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章分词失败. ErrorMsg:[" . var_export($ana_result, true) . "].");
                deleteWeibo($allids, $sourceid, $allmids, $allguids,$timeStatisticObj);
                $result['result'] = false;
                $result['msg'] = '--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章分词失败';
                return $result;
            } else {
                formatStoreData($solr_weibos_info, $ana_result, $tokenfields);//生成存储数据
            }
        }else{
            $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章分词--+-文章已经分词!");
        }
    }

    //新文章插入solr - 非原创
    if ($c > 0) {
        $url = SOLR_URL_INSERT;
        if ($iscommit) {
            $url .= "&commit=true";
        } else {
            $url .= "&commit=false";
        }
        $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章入库solr. URL:[" . $url . "].");

        $solrstart_time = microtime_float();
        $tmp_result = handle_solr_data($solr_weibos_info, $url);
        $solrend_time = microtime_float();
        //统计时间
        addTime4Statistic($timeStatisticObj,SOLR_INSERT_TIME_KEY,$solrend_time - $solrstart_time);

        if (empty($sceneParentParam->scene->solr_count)) {
            $sceneParentParam->scene->solr_count = 0;
        }
        $sceneParentParam->scene->solr_count++;

        $errorcount = 0;
        if ($tmp_result === false) {
            $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章入库solr失败. ErrorMsg:[返回false]. 文章信息:[" . json_encode($solr_weibos_info) . "].");
            $errorcount = count($solr_weibos_info);
            $solrerrorcount += $errorcount;
            if (!empty($allids) || !empty($allmids) || !empty($allguids)) {
                deleteWeibo($allids, $sourceid, $allmids, $allguids,$timeStatisticObj);
            }
            if (!empty($allorigids) || !empty($allorigmids)) {//删除原创的
                deleteWeibo($allorigids, $sourceid, $allorigmids,false,$timeStatisticObj);
            }
            $strresult = "返回false";
            //add by wangcc 调用solr失败直接返回
            $result['result'] = false;
            $result['msg'] = '--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章入库solr失败.';
            return $result;
        } else if ($tmp_result === NULL) {
//            $result = true;
            $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章入库solr成功!");
            $strresult = "成功";
        } else {
            $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章入库solr失败. ErrorMsg:[" . var_export($tmp_result, true) . "].");
            $result['result'] = false;
            $result['msg'] = '--+-添加文章--+-数据入库--+-处理非原创文章--+-非原创文章入库solr失败.';
            return $result;

            $errorcount = count($tmp_result);
            $strresult = "失败{$errorcount}条";
            $solrerrorcount += $errorcount;
            foreach ($tmp_result as $k => $v) {
                $guid_arr = split('_', $v);
                if ($guid_arr[0] == ($sourceid . "m")) {
                    $errormids .= "'" . $guid_arr[1] . "',";
                } else {
                    $errorids .= "'" . $guid_arr[1] . "',";
                }
            }
            $errorids = substr($errorids, 0, -1);//错误ID从数据库删除
            $errormids = substr($errormids, 0, -1);//错误mID从数据库删除
            if (!empty($errorids)) {
                deleteWeibo($errorids, $sourceid, $errormids,false,$timeStatisticObj);
            }
        }
        //$solrinfo = "新文章[非原创]调用solr({$c})条,{$strresult},花费时间：" . $analysisdiff;
        if (empty($sceneParentParam->scene->solrerrorcount)) {
            $sceneParentParam->scene->solrerrorcount = 0;
        }
        $sceneParentParam->scene->solrerrorcount += $errorcount;
//        if (empty($sceneParentParam->scene->analysistime)) {
//            $sceneParentParam->scene->analysistime = 0;
//        }
//        $sceneParentParam->scene->analysistime += $solr_analysistime;
//        if (empty($sceneParentParam->scene->storetime)) {
//            $sceneParentParam->scene->storetime = 0;
//        }
        //$sceneParentParam->scene->storetime += $analysisdiff;

        if (empty($statistics_info->datastatus)) {
            $statistics_info->datastatus = 0;
        }
        $statistics_info->datastatus += ($c - $errorcount);
    }
    $end_time = microtime_float();
    $insertdiff = $end_time - $start_time;
    $time_handle_comment += $insertdiff;
    addTime4Statistic($timeStatisticObj,HANDLE_CMT_DOC,$time_handle_comment);
    //************************************************   处理转发/评论 ok ***********************************************************************//


    //************************************************   处理更新  ***********************************************************************//
    $update_count = count($update_solr_infos);
    $time_handle_update = 0;
    //更新solr
    $udtart_time = microtime_float();
    if ($update_count > 0) {
        if (!$issegmented) {
            //加入分词
            if (!empty($dictionaryPlan)) {
                $dictionaryPlan = formatDictionaryPlan($dictionaryPlan);
            }
            $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章分词--+-使用分词方案:[" . var_export($dictionaryPlan, true) . "].");
            $solrstart_time = microtime_float();
            $ana_result = solr_analysis($update_solr_infos, $tokenfields, $dictionaryPlan, false);
            $solrend_time = microtime_float();
            //统计时间
            addTime4Statistic($timeStatisticObj,SOLR_NLP_TIME_KEY,$solrend_time - $solrstart_time);

            if (!$ana_result) {
                $solrerrorcount += $c;
                $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章分词失败! ErrorMsg:[" . var_export($ana_result, true) . "].");
                deleteWeibo($allids, $sourceid, $allmids, $allguids,$timeStatisticObj);
                //return false;
                $result['result'] = false;
                $result['msg'] = '--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章分词失败!';
                return $result;
            } else {
                formatStoreData($update_solr_infos, $ana_result, $tokenfields);//生成存储数据
            }
        }else{
            $logger->info(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章分词--+-文章已分词!");
        }
        $uurl = SOLR_URL_UPDATE;
        if ($iscommit) {
            $uurl .= "&commit=true";
        } else {
            $uurl .= "&commit=false";
        }
        $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章更新--+-URL:[" . $uurl . "].");
        $solrstart_time = microtime_float();
        $solr_r = handle_solr_data($update_solr_infos, $uurl);
        $solrend_time = microtime_float();
        //统计时间
        addTime4Statistic($timeStatisticObj,SOLR_UPDATE_TIME_KEY,$solrend_time - $solrstart_time);

        $errupcount = 0;
        if (empty($sceneParentParam->scene->solr_count)) {
            $sceneParentParam->scene->solr_count = 0;
        }
        $sceneParentParam->scene->solr_count++;
        if ($solr_r === false) {
            $errupcount = $update_count;
            $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章更新失败! 文章信息:[" . var_export($update_solr_infos, true) . "].");
            $result['result'] = false;
            $result['msg'] = '--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章更新失败!';
            return $result;
        } else if ($solr_r !== NULL && is_array($solr_r)) {
            $result['result'] = false;
            $result['msg'] = '--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章更新失败!';
            $logger->error(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要更新的文章--+-文章更新失败! ErrorMsg:[" . var_export($solr_r, true) . "].");
            return $result;
            $errupcount = count($solr_r);
            $solrerrorcount += $errupcount;
            foreach ($solr_r as $k => $v) {
                $guid_arr = split('_', $v);
                if ($guid_arr[0] == ($sourceid . "m")) {
                    $errormids .= "'" . $guid_arr[1] . "',";
                } else {
                    $errorids .= "'" . $guid_arr[1] . "',";
                }
            }
            $errorids = substr($errorids, 0, -1);//错误ID从数据库删除
            $errormids = substr($errormids, 0, -1);//错误mID从数据库删除
            updateDataAnalysisStatus($errorids, ANALYSIS_STATUS_OTHERERROR, $sourceid, $errormids,$timeStatisticObj);
        }
        unset($update_solr_infos);
        if (!isset($solrinfo)) {
            $solrinfo = '';
        }
        //$solrinfo .= "  更新solr({$update_count})条,花费时间：" . $updiff;
        if (empty($sceneParentParam->scene->solrerrorcount)) {
            $sceneParentParam->scene->solrerrorcount = 0;
        }
        $sceneParentParam->scene->solrerrorcount += $errupcount;
//        if (empty($sceneParentParam->scene->storetime)) {
//            $sceneParentParam->scene->storetime = 0;
//        }
        //$sceneParentParam->scene->storetime += $updiff;
        if (empty($statistics_info->datastatus)) {
            $statistics_info->datastatus = 0;
        }
        $statistics_info->datastatus += ($update_count - $errupcount);
    }
    //统计时间
    $udend_time = microtime_float();
    $time_handle_update += ($udend_time - $udtart_time);
    addTime4Statistic($timeStatisticObj,HANDLE_UPDT_DOC,$time_handle_update);
    //************************************************   处理更新 Ok ***********************************************************************//

    if (($oc + $c + $update_count) == 0) {
        $solrinfo = "未调用solr";
    }

    $supply_starttime = microtime_float();
    if(!$issegmented){
        if (supplyIndirectGuids($partialdata,$iscommit,$timeStatisticObj) === false) {
            $logger->error("补充guid失败");
            //$result = false;
            $result['result'] = false;
            $result['msg'] = '插入文章时，补充文章的doc/父/原创guid失败';
        }
    } else {
        addNedSupplyDocInfos($partialdata,$iscommit,$timeStatisticObj);
    }

    $supply_endtime = microtime_float();
    $supplydiff = $supply_endtime - $supply_starttime;
    addTime4Statistic($timeStatisticObj,HANDLE_SPL_GUID,$supplydiff);

    $in_count = $c + $deletedcount;
    unset($solr_weibos_info);
    if (empty($sceneParentParam->scene->insertsql_statustime)) {
        $sceneParentParam->scene->insertsql_statustime = 0;
    }
    $sceneParentParam->scene->insertsql_statustime += $insertdiff;//总入库时间
    if (empty($sceneParentParam->scene->insertsql_statuscount)) {
        $sceneParentParam->scene->insertsql_statuscount = 0;
    }
    $sceneParentParam->scene->insertsql_statuscount += $c;//入库条数
    /* 移到insert_user
	if(empty($statistics_info->taskparams->scene->spider_usercount)){
		$statistics_info->taskparams->scene->spider_usercount = 0;
	}
	$statistics_info->taskparams->scene->spider_usercount += $newusercount;//新用户数
	if(empty($statistics_info->taskparams->scene->update_usercount)){
		$statistics_info->taskparams->scene->update_usercount = 0;
	}
	$statistics_info->taskparams->scene->update_usercount += $upusercount;//更新用户数
	 */
    if (empty($sceneParentParam->scene->insertsql_usertime)) {
        $sceneParentParam->scene->insertsql_usertime = 0;
    }
    $sceneParentParam->scene->insertsql_usertime += $insertuserdiff;//用户入库时间
    if ($statistics_to == 'task') {
        $task = $statistics_info;
    }

    $time_handle_del = 0;
    $handle_del_start = microtime_float();
    if (!empty($delete_solr_infos)) {
        $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-处理需要删除的文章--+-文章个数:[" . count($delete_solr_infos) . "]个 文章信息:[" . var_export($delete_solr_infos, true) . "].");
        foreach ($delete_solr_infos as $v) {
            $delete_guids[] = $v['guid'];
        }
        $cds = "guid:" . "(" . implode(" OR ", $delete_guids) . ")";
        if (delete_solrdata($cds,$iscommit,$timeStatisticObj) === false) {
            $result['result'] = false;
            $result['msg'] = '--+-添加文章--+-数据入库--+-处理需要删除的文章--+-删除solr中的文章失败!';
        } else {
            $result['msg'] = '--+-添加文章--+-数据入库--+-处理需要删除的文章--+-删除solr中的文章成功!';
        }
    }
    $fun_endtime = microtime_float();
    $time_handle_del = $fun_endtime - $handle_del_start;
    addTime4Statistic($timeStatisticObj,HANDLE_DEL_DOC,$time_handle_del);


    $fundiff = $fun_endtime - $fun_starttime;
    $funtime += $fundiff;
    //统计时间
    addTime4Statistic($timeStatisticObj,DATA_HANDLE,$fundiff);

    $logger->info("insert_status {$logappend}, 新抓{$onscount}条, 新增{$in_count}条,更新{$update_count}条，新增{$newusercount}个用户、更新{$upusercount}个用户.");
    $logger->info(__FILE__ . __LINE__ . " 总访问API次数{$apicount},出错{$apierrorcount}次, 总抓取{$spidercount}条,入库{$newcount}条, 调用solr总失败{$solrerrorcount}条,总新增用户{$spiderusercount}个");
    return $result;
}

//将入库的数据填上father_guid, retweeted_guid，并且放到update_solr_infos中。tbd。insert_comment那头呢？
/**
 * @param bool|false $ispartialdata
 * @param bool|TRUE $isCommit
 * @param null $timeStatisticObj:统计时间的关联数组
 * @param bool|false $isErrorSkip :遇到补充失败的是否跳过，在批量处理的时候，如果原创或者需要补充的文章不存在，需要跳过当前文章处理下一条
 * @return bool|null
 * @throws Exception
 */
function supplyIndirectGuids($ispartialdata = false,$isCommit=TRUE,&$timeStatisticObj=null,$isErrorSkip = false,$errorDocCollect = null)
{
    global $indirect_guid_query_conds, $logger;

    if($isCommit){
        $updateURL = SOLR_URL_UPDATE . "&commit=true";
    }else{
        $updateURL = SOLR_URL_UPDATE . "&commit=false";
    }

    if (empty($indirect_guid_query_conds))
        return NULL;

    $update_solr_infos = array();
    $ori_guid = ''; //认为一批数据的原创guid是一样的，只取一次。
    $logger->debug("开始补充间接guid,partial:" . $ispartialdata);
    $logger->debug("indirect_guid_query_conds: " . var_export($indirect_guid_query_conds, true));
    foreach ($indirect_guid_query_conds as $key => $cond) {
        unset($update_tmp);
        //查出自己的guid
        $update_tmp['guid'] = isset($cond['guid']) ? $cond['guid'] : getArticleGuidOrMore($cond,false,$timeStatisticObj);
        if ($update_tmp['guid'] === false) {
            if(!$isErrorSkip){
                return false;
            }else{
                $logger->error("补充间接guid失败，需要补充间接guid的文章不存在! ispartialdata:[" . $ispartialdata."].");
                $errorDocCollect[] = array("errorMsg"=>"需要补充间接guid的文章本身不存在!","id"=>$key,"doc"=>$cond);
                continue;
            }
        } else if (empty($update_tmp['guid'])) {
            continue;
        }
        //$logger->debug("cond value: ".var_export($cond,true));
        if (isset($cond['add_guid_ori']) && $cond['add_guid_ori']) {
            $update_tmp ['retweeted_guid'] = getOriginalGuidFromSolr($cond, $ispartialdata,$timeStatisticObj);
            if ($update_tmp ['retweeted_guid'] === false) {
                //return false;
                if($isErrorSkip){
                    $logger->error("补充间接guid失败，需要补充间接guid的[原创文章]不存在! ispartialdata:[" . $ispartialdata."].");
                    $errorDocCollect[] = array("errorMsg"=>"需要补充间接guid的[原创文章]不存在!","id"=>$key,"doc"=>$cond);
                    continue;
                }else{
                    return false;
                }
            }
        }
        if (isset($cond['add_guid_father']) && $cond['add_guid_father']) {
            $tmp = getFatherGuidFromSolr($cond, $ispartialdata,$timeStatisticObj);
            if ($tmp === false) {
                //查询失败
                if($isErrorSkip){
                    $logger->error("补充间接guid失败，需要补充间接guid的[father文章]不存在! ispartialdata:[" . $ispartialdata."].");
                    $errorDocCollect[] = array("errorMsg"=>"需要补充间接guid的[father文章]不存在!","id"=>$key,"doc"=>$cond);
                    continue;
                }else{
                    return false;
                }
            }
            if (!empty($tmp))
                $update_tmp ['father_guid'] = $tmp;
        }
        if (isset($cond['add_guid_doc']) && $cond['add_guid_doc']) {
            $update_tmp ['docguid'] = getDocGuidFromSolr($cond,$timeStatisticObj);
            if ($update_tmp ['docguid'] === false) {
                //查询失败
                if($isErrorSkip){
                    $logger->error("补充间接guid失败，需要补充间接guid的[doc_guid文章]不存在! ispartialdata:[" . $ispartialdata."].");
                    $errorDocCollect[] = array("errorMsg"=>"需要补充间接guid的[doc_guid文章]不存在!","id"=>$key,"doc"=>$cond);
                    continue;
                }else{
                    return false;
                }
            }
        }
        /*if(isset($cond['add_quote_floor']) && $cond['add_quote_floor']){
			$tmp = getQuoteFromSolr($cond, $ispartialdata);
			if($tmp===false){
				return false;
			}
			if(!empty($tmp))
				$update_tmp ['father_floor'] = $tmp['floor'];
		}*/
        if (count($update_tmp) > 1) {
            $update_solr_infos[] = $update_tmp;
            //$logger->debug("需要补充的：".var_export($update_tmp,true));
        }
    }
    //清空
    $indirect_guid_query_conds = array();

    $ind_count = count($update_solr_infos);
    if (!empty($update_solr_infos)) {
        //$logger->debug("更新补充guid：".var_export($update_solr_infos,true));
        $logger->info("solr更新旧文章补充间接guid");

        $solrstart_time = microtime_float();
        $solr_r = handle_solr_data($update_solr_infos, $updateURL);
        $solrend_time = microtime_float();
        //统计时间
        addTime4Statistic($timeStatisticObj,SOLR_UPDATE_TIME_KEY,$solrend_time - $solrstart_time);

        if ($solr_r === false) {
            //$errupcount = $update_count;
            //$result = false;
            $logger->error(__FILE__ . __LINE__ . " 更新solr失败, data is ：" . json_encode($update_solr_infos));
            return false;
        } else if ($solr_r !== NULL && is_array($solr_r)) {
            //$result = false;
            $logger->error(__FILE__ . __LINE__ . " 更新SOLR 未找到的:" . var_export($solr_r, true));
            return false;
        } else {
            $logger->debug("补充guid成功，共更新：" . $ind_count . "条");
        }
        unset($update_solr_infos);
    } else
        $logger->debug("结束补充间接guid，共更新0条");
}

//拼接文章guid
function setArticleGuid($item)
{
    global $logger;

    //$logger->debug("setArticleGuid for data:[" . var_export($item, true) . "].");
    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成文章Guid--+-文章内容:[" . var_export($item, true) . "].");


    if (isset($item['sourceid']))
        $part1 = $item['sourceid'];
    else if (isset($item['source_host']))
        $part1 = $item['source_host'];
    else {
        $logger->error("Both sourceid and host are undefined! GUID can't be set.");
        return false;
    }

    if (isset($item['id'])) {
        return $part1 . "_" . $item['id'];
    } else if (isset($item['mid'])) {
        return $part1 . "m_" . $item['mid'];
    } else if (isset($item['original_url']) && isset($item['floor']) && isset($item['paragraphid'])) {
        if (!empty($item['dataClsfct']) && !empty($item['updateFreq'])) {
            //趋势分析数据
            if (empty($item["updateFreq"])) {
                throw new Exception("生成[趋势分析]文章guid异常,value of field:[updateFreq] is null.");
            }
            $newGUidStr = $part1 . "_" . base64_encode(generalGuidFactStr4TrenAnal($item["updateFreq"], $item["created_at"], $item['original_url'])) . "_" . $item['floor'] . "_" . $item['paragraphid'];
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成文章Guid--+-趋势分析数据--+-生成结果:[{$newGUidStr}]");
            return $newGUidStr;
        } else if (!isset($item['dataClsfct']) && !isset($item['updateFreq'])) {
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成文章Guid--+- 根据 original_url:[{$item['original_url']}] floor:[{$item['floor']}] paragraphid:[{$item['paragraphid']}] 生成!");
            return $part1 . "_" . base64_encode($item["original_url"]) . "_" . $item['floor'] . "_" . $item['paragraphid'];
        } else {
            throw new Exception("生成[趋势分析]文章guid异常,不是普通数据也不是趋势分析数据!");
        }
    } else if (isset($item['original_url']) && isset($item['reply_father_floor']) && isset($item['child_post_id']) && isset($item['paragraphid'])) {
        return $part1 . "_" . base64_encode($item["original_url"]) . "_" . $item['reply_father_floor'] . "_" . $item['child_post_id'] . "_" . $item['paragraphid'];
    } else if (isset($item['original_url']) && isset($item['question_id']) && isset($item['paragraphid'])) {
        return $part1 . "_" . base64_encode($item["original_url"]) . "_" . $item['question_id'] . "_" . $item['paragraphid'];
    } else if (isset($item['original_url']) && isset($item['answer_id']) && isset($item['paragraphid'])) {
        return $part1 . "_" . base64_encode($item["original_url"]) . "_" . $item['answer_id'] . "_" . $item['paragraphid'];
    } else if (isset($item['original_url']) && isset($item['answer_father_id']) && isset($item['child_post_id']) && isset($item['paragraphid'])) {
        return $part1 . "_" . base64_encode($item["original_url"]) . "_" . $item['answer_father_id'] . "_" . $item['child_post_id'] . "_" . $item['paragraphid'];
    } else {
        $logger->error("GUID can't be set.");
        return false;
    }
}


function addTime4Statistic(&$timeStatisticObj=null,$timeKey,$timeSpd){
    if(null!=$timeStatisticObj){
        $timeStatisticObj[$timeKey] += $timeSpd;
    }
}


//$global_noidorigs = array();//存储没有ID的原创信息，以mid为key
//由于爬虫抓取的数据,企业认证没有verified_type,需要每次去数据库查询。增加此全局变量作为缓存，减少去数据库的次数。以userid为key
$global_usercache = array();
/*
 * 向数据库中插入文章信息。
 * $sourceid 参数不再使用
 * $data_is_partial 数据不完全，允许不报错
 */
function inner_insert_status($ms, $timeline_type, $sourceid, $is_insert = true, $maxtime = NULL, $mintime = NULL, $isseed = false, $issegmented = false, $statistics_to = 'task', $data_is_partial = false,&$timeStatisticObj=null)
{
    global $logger, $task, $statistics_info, $global_usercache, $dsql, $solr_article_tags, $sql_article_tags, $indirect_guid_query_conds;
    global $OriginalIdArray;
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    //$logger->debug("enter inner_insert_status, partial:" . $data_is_partial . " All data: " . var_export($ms, true));

    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-向solr中插入文章...");

    //$logger->debug('enter '.var_export($task,true));
    //作为函数的返回值，用于向solr发送数据
    //$send_solr_data = array();
    $flag = true;
    $count = 0;
    $annscount = 0;
    $annicount = 0;
    $result = array("send_solr_data" => array(), "update_solr_data" => array(), "delete_solr_data" => array(), "result" => true, "msg" => "");
    if (!empty($task)) {
        $statistics_info = $task;
    }

    if (isCommonTask($statistics_info)) {
        $sceneParentNode = &$statistics_info->taskparams->root->runTimeParam;
    } else {
        $sceneParentNode = &$statistics_info->taskparams;
    }

    if (!isset($sceneParentNode->scene->exists_weibocount)) {
        $sceneParentNode->scene->exists_weibocount = 0;
    }
    if (!isset($sceneParentNode->scene->update_weibocount)) {
        $sceneParentNode->scene->update_weibocount = 0;
    }

    if (!$is_insert)
        return $result;

    if (empty($ms)) {
        return $result;
    }
    $floor_cache = array();
    $mid_cache = array();
    //全循环设置host，取cache
    foreach ($ms as $k => $item) {
        //检查是否有host
        if (!isset($item['source_host'])) {
            if (!empty($item['page_url'])) {
                $ms[$k]['source_host'] = get_host_from_url($item['page_url']);
            } else if (!empty($item['original_url'])) {
                $ms[$k]['source_host'] = get_host_from_url($item['original_url']);
            }
        }
        if (empty($ms[$k]['source_host'])) {
            $logger->error(__FUNCTION__ . __LINE__ . " 插入数据缺少source_host:" . var_export($item, true));
            $result['result'] = false;
            $result['msg'] = '插入文章时，数据缺少source_host';
            return $result;
            //return false;
        }

        if (isset($item['floor'])) {
            $floor_cache[$ms[$k]['source_host']][] = strval($item['floor']);
        }
        if (isset($item['mid']))
            $mid_cache[$ms[$k]['source_host']][] = strval($item['mid']);
    }
    //$logger->debug("floor_cache:".var_export($floor_cache,true));
    //$logger->debug("mid_cache:".var_export($mid_cache,true));
    global $task;
    if (isNeedAddUser($task)) {
        $needAddUser = true;
    } else {
        $needAddUser = false;
    }

    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-判断是否需要增加用户:[" . ($needAddUser ? "true" : "false") . "].");

    foreach ($ms as $item) {
        $ieachstart_time = microtime_float();
        if (empty($item)) {
            continue;
        }
        //created_at_ts是时间戳，created_at是时间
        if (isset($item['created_at'])) {
            $created_at = is_numeric($item['created_at']) ? $item['created_at'] : strtotime($item['created_at']);
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-处理字段[created_at]的值--+-处理结果:[{$created_at}]");
        } else if (isset($item['created_at_ts'])) {
            $created_at = $item['created_at_ts'];
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-处理字段[created_at]的值--+-处理结果:[{$created_at}]");
        } else {
            $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "[警告]--+-添加文章--+-字段:[created_at]以及[created_at_ts] 缺失!");
        }
        if (isset($created_at)) {
            $created_at = narrowToSolrInt($created_at);
        }

        //如果指定了抓取的时间条件，则进行判断，不符合条件的忽略
        if (!empty($maxtime)) {
            if ($created_at > $maxtime) {
                continue;
            }
        }
        if (!empty($mintime)) {
            if ($created_at < $mintime) {
                continue;
            }
        }
        //purchDate是时间字符串 add by wangcc dateStr 转化为 int 类型
        if (isset($item['purchDate'])) {
            $item['purchDate'] = is_numeric($item['purchDate']) ? $item['purchDate'] : strtotime($item['purchDate']);
        }
        if (isset($item['purchDate'])) {
            $item['purchDate'] = narrowToSolrInt($item['purchDate']);
        }

        if (isset($item['register_time'])) {
            $item['register_time'] = is_numeric($item['register_time']) ? $item['register_time'] : strtotime($item['register_time']);
        }
        if (isset($item['register_time'])) {
            $item['register_time'] = narrowToSolrInt($item['register_time']);
        }
        //$logger->debug(__FILE__.__LINE__." item: ".var_export($item, true));

        //$item['sourceid'] = isset($sourceid)? $sourceid:NULL;
        //检查是否有host
        /*if(!isset($item['source_host'])){
			if(!empty($item['page_url'])){
				$item['source_host'] = get_host_from_url($item['page_url']);
			}
		}
		if(empty($item['source_host'])){
			$logger->error(__FUNCTION__." 插入数据缺少source_host:".var_export($item,true));
			$result['result'] = false;
			$result['msg'] = '插入文章时，数据缺少source_host';
			return $result;
			//return false;
		}*/
        $item['sourceid'] = get_source_id($item['source_host'],$timeStatisticObj);
        $sourceid = $item['sourceid'];
        //查询是否存在
        $solrarticle = getArticleGuidOrMore($item, true,$timeStatisticObj);
        if ($solrarticle === false) {
            $result['result'] = false;
            $result['msg'] = '插入文章时，判断当前文章是否存在solr中时候异常，查询solr异常!';
            return $result;
        }

        ///$logger->debug(__FILE__ . __LINE__ . " 获取solr中已经存在的当前文章结果：" . var_export($solrarticle, true));
        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-查询文章是否存在--+- 查询结果:[" . var_export($solrarticle, true) . "].");

        $is_exist = !empty($solrarticle);
        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-查询文章是否存在--+- 是否存在:[" . ($is_exist ? "存在" : "不存在") . "].");

        if ($is_exist) {
            $item['guid'] = $solrarticle['guid'];
            $guid = $item['guid'];
            if (empty($sceneParentNode->scene->exists_weibocount)) {
                $sceneParentNode->scene->exists_weibocount = 0;
            }
            $sceneParentNode->scene->exists_weibocount++;
            //solr旧数据中有ID而新数据没有，给新数据补上（weibo新数据是mid）
            if (empty($item['id']) && !empty($solrarticle['id'])) {
                $item['id'] = $solrarticle['id'];
            }
        } //new
        else {
            //设置文章guid
            $guid = setArticleGuid($item);
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-文章不存在--+-生成GUID--+- Guid:[{$guid}]!");

            //modified by wangcc 应该根据 $guid 返回结果判断
            if ($guid === false) {
                $result['result'] = false;
                $result['msg'] = '插入文章时，拼接guid失败';
                return $result;
            }
            $item['guid'] = $guid;
        }

        //$logger->debug(__FILE__ . __LINE__ . " guid: " . $guid . " exists: " . $is_exist);

        //判断sql数据库中是否存在
        $s = "select `guid` from " . DATABASE_WEIBO . " where guid = '{$guid}'";
        $srstart_time = microtime_float();
        $sr = $dsql->ExecQuery($s);
        $srend_time = microtime_float();

        //统计时间
        addTime4Statistic($timeStatisticObj,DB_SELECT_TIME_KEY,$srend_time - $srstart_time);

        //$logger->debug(__FILE__ . __LINE__ . " 判断sql数据库中是否存在 use time: " . ($srend_time - $srstart_time));
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-查询Mysql中是否存在该文章--+- 用时:[" . ($srend_time - $srstart_time) . "].");
        if (!$sr) {
            //$logger->error("sql is " . $s . " mysql error is " . $dsql->GetError());
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-查询Mysql中是否存在该文章异常--+- mysql error is:[" . $dsql->GetError() . "].");
        }

        $res = $dsql->GetArray($sr);
        $dsql->FreeResult($sr);
        //sql没有而solr有，插入一条到sql
        if (empty($res) && $is_exist) {
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-Mysql中不存在，但是solr中存在该文章--+-向Mysql中插入该文章!");
            if (isset($item['mid']) && isset($sourceid))
                $s = "insert into " . DATABASE_WEIBO . " (guid,sourceid,mid) values ('{$guid}',{$sourceid},'{$item['mid']}')";
            else
                $s = "insert into " . DATABASE_WEIBO . " (guid) values ('{$guid}')";

            $sqlstart_time = microtime_float();
            $dsql->ExecQuery($s);
            $sqlend_time = microtime_float();

            //统计时间
            addTime4Statistic($timeStatisticObj,DB_INSERT_TIME_KEY,$sqlend_time - $sqlstart_time);
            if (!$sr) {
                $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-Mysql中不存在，但是solr中存在该文章--+-向Mysql中插入该文章异常,ErrorMsg:[" . $dsql->GetError() . "].");
            }
        } //sql有而solr没有，删除sql
        else if (!empty($res) && !$is_exist) {
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-solr中不存在，但是mysql中存在该文章--+-删除mysql中该文章!");
            $s = "delete from " . DATABASE_WEIBO . " where guid = {$guid}";

            $sqlstart_time = microtime_float();
            $dsql->ExecQuery($s);
            $sqlend_time = microtime_float();

            //统计时间
            addTime4Statistic($timeStatisticObj,DB_DELET_TIME_KEY,$sqlend_time - $sqlstart_time);

            if (!$sr) {
                //$logger->error("sql is " . $s . " mysql error is " . $dsql->GetError());
                $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-solr中不存在，但是mysql中存在该文章--+-删除mysql中该文章异常,ErrorMsg:[" . $dsql->GetError() . "].");
            }
        } else {
            //$logger->debug("solr and sql data are consistent.");
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-查询Mysql中的文章和solr文章是否同步--+-同步!");
        }
        //先做各种预处理，值放入自己item。如果是直接赋值的字段，那么不需要做处理。
        //因为要比较全部字段决定更新字段，所以这些预处理逻辑不仅在插入前要做，在更新前也要做。
        //sql数据库的字段，solr的字段都在这里处理赋值给自己item
        $item['analysis_status'] = isset($item['analysis_status']) ? $item['analysis_status'] : ANALYSIS_STATUS_NORMAL;
        $analysis_status_tmp = $item['analysis_status'];

        $item['timeline_type'] = $timeline_type;
        $item['isseed'] = empty($isseed) ? 0 : 1;//是否种子微博
        if (isset($created_at)) {
            $item['created_at'] = $created_at;
            $item['created_year'] = date('Y', $created_at);
            $item['created_month'] = date('n', $created_at);
            $item['created_day'] = date('j', $created_at);
            $item['created_hour'] = date('G', $created_at);
            $item['created_weekday'] = date('N', $created_at);
            $item['year'] = date('Y', $created_at);
            $item['month'] = date('n', $created_at);
            $item['day'] = date('j', $created_at);
            $item['hour'] = date('G', $created_at);
            $item['minute'] = intval(date('i', $created_at));
            $item['second'] = intval(date('s', $created_at));
        }
        if (isset($item['text']['content'])) {
            $item['originalText'] = array(md5($item['text']['content']));//多值的字段
        }
        if (isset($item['quote_father_floor'])) {
            $item['father_floor'] = $item['quote_father_floor'];//father_floor代表引用楼，名字不妥，不好改了
        }
        //默认值
        //如果要设floor和paragraphid的默认值，应当在外面的加工中设置。
        //$item['floor'] = isset($item['floor']) ? $item['floor'] : 0;
        //$item['paragraphid'] = isset($item['paragraphid']) ? $item['paragraphid'] : 0;
        $item['read_count'] = isset($item['read_count']) ? $item['read_count'] : 0;
        $item['recommended'] = isset($item['recommended']) ? $item['recommended'] : 0;
        $item['toped'] = isset($item['toped']) ? $item['toped'] : 0;
        $item['source'] = isset($item['source']) ? strip_tags($item['source']) : "未知应用来源";

        //从user获取的
        //处理：userid	level	screen_name	register_time	verified_reason	verified	verified_type	verify	followers_count
        //friends_count	description	gender	sex	reach_count	total_reach_count	userguid

        //需要抓取存在用户的文章时候，用户信息必须写到{oneGrabData.user}中，如果不需要，或者不能唯一确定用户id,需要
        //一部分用户信息，例如用户名称、用户地区等，这种情况时候，将用户信息作为文章属性的一部分插入即可,这时候不需要添加用户即：$needAddUser=false
        if ($needAddUser && isset($item['user']) && isset($item['user']['id'])) {
            //$item['userguid'] = "{$sourceid}u_{$item['userid']}";
            //$logger->debug("call getUserGuidOrMore".var_export($item['user'],true));
            //user已经插入了
            $user_tmp = $item['user'];
            if (isset($user_tmp['page_url'])) {
                $item['user']['source_host'] = get_host_from_url($user_tmp['page_url']);
            }

            //从全局缓存中获取当前用户的 "guid" (在处理用户时候已经将该用户的guid设置到了全局缓存中)
            $item['userguid'] = isset($global_usercache[$item['user']['source_host']][strval($user_tmp['id'])]['guid']) ? $global_usercache[$item['user']['source_host']][strval($user_tmp['id'])]['guid'] : getUserGuidOrMore($item['user'], false, NULL, $data_is_partial,$timeStatisticObj);
            /*if(isset($global_usercache[$item['user']['source_host']][$user_tmp['id']]['guid'])){
				$logger->debug(__FUNCTION__."命中user cache");
			}*/
            if ($item['userguid'] === false) {
                unset($item['userguid']);
                $logger->error("查询文章用户失败" . var_export($item, true));
                $result['result'] = false;
                $result['msg'] = '查询文章用户失败';
                return $result;
            }

            //获取用户的认证类型 以及 根据用户的'country' 'province' 'city' 'district' 字段获取用户的地区编码
            $tmp = processUserData($item['user'], $item['sourceid']);
            if (isset($tmp['result']) && $tmp['result'] === false) {
                $result['result'] = false;
                $result ['msg'] = $tmp['msg'];
                return $result;
                //return false;
            } else {
                $item['user'] = $tmp;
            }
            //$logger->debug("before addUserToArticle :" . var_export($item, true));
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-处理文章中的用户信息--+--将artial['user']中的字段设置到artical上面--+-设置前文章信息:[" . var_export($item, true) . "].");

            /**
             * 将文章中没有的但是artical['user']中有的字段(仅限$solr_article_user_tags中的这些字段) 设置到文章上面去
             */
            $r = addUserToArticle(array($item), $item['user']);
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-处理文章中的用户信息--+--将artial['user']中的字段设置到artical上面--+-设置以后文章信息:[" . var_export($r, true) . "].");

            $item = $r[0];
            //$logger->debug("after addUserToArticle :" . var_export($item, true));
        } else {
            //$logger->debug("对当前文章不用处理用户信息！");
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-不用处理文章中的用户信息!");
        }

        //处理：praises_count	geo_type	geo_coordinates_x	geo_coordinates_y	thumbnail_pic	bmiddle_pic	original_pic	has_picture
        if (isset($item['attitudes_count'])) {
            $item['praises_count'] = $item['attitudes_count'];
        } else if (isset($item['praises_count'])) {
            $item['praises_count'] = $item['praises_count'];
        }
        $item['geo_type'] = isset($item['geo']['type']) ? $item['geo']['type'] : NULL;
        if (isset($item['geo']['lat'])) {
            $x = $item['geo']['lat'];
            $y = $item['geo']['lon'];
        } else {
            $x = isset($item['geo']['coordinates'][0]) ? $item['geo']['coordinates'][0] : 0;
            $y = isset($item['geo']['coordinates'][1]) ? $item['geo']['coordinates'][1] : 0;
        }
        $item['geo_coordinates_x'] = $x;
        $item['geo_coordinates_y'] = $y;

        $thumbnail_pic = !empty($item['thumbnail_pic']) ? $item['thumbnail_pic'] : NULL;
        $bmiddle_pic = !empty($item['bmiddle_pic']) ? $item['bmiddle_pic'] : NULL;
        $original_pic = !empty($item['original_pic']) ? $item['original_pic'] : NULL;

        if (empty($thumbnail_pic) && !empty($item['square_pic'])) {//转换得到小图
            $thumbnail_pic = convertPicturePath($sourceid, $item['square_pic'][0], PICTURE_TYPE_SMALL);
        }
        if (!empty($thumbnail_pic) && empty($bmiddle_pic)) {//转换得到中图
            $bmiddle_pic = convertPicturePath($sourceid, $thumbnail_pic, PICTURE_TYPE_MIDDLE);
        }
        if (!empty($thumbnail_pic) && empty($original_pic)) {//转换得到原图
            $original_pic = convertPicturePath($sourceid, $thumbnail_pic, PICTURE_TYPE_BIG);
        }
        $item['thumbnail_pic'] = $thumbnail_pic;
        $item['bmiddle_pic'] = $bmiddle_pic;
        $item['original_pic'] = $original_pic;
        $item['has_picture'] = (isset($item['thumbnail_pic']) || isset($item['bmiddle_pic'])) ? 1 : 0;
        /*
		 * 由于solr的需要，添加一个是否为转发字段
			 * 0代表原创，1代表转发
			 */
        //处理：retweeted_status(原创id)	retweeted_mid(原创mid)	is_repost	repost_trend_cursor	retweeted_guid	direct_reposts_count	total_reposts_count
        //如果是转发
        /*if (isset($item['retweeted_status']))
		{
			$is_repost = 1;
			//$logger->debug("retweeted_status:".var_export($item['retweeted_status'],true));
			if(isset($item['retweeted_status']['mid']))
			{
				$item['retweeted_mid'] = $item['retweeted_status']['mid'];
				$item['retweeted_status'] = $item['retweeted_mid'];
				//不查，直接拼，因为只有weibo用mid，规则固定
				//$item['retweeted_guid'] = $item['sourceid']."m_".$item['retweeted_mid'];
			}
			//以后不会有id了，删除
			else if(isset($item['retweeted_status']['id']))
			{
				$item['retweeted_status'] = $item['retweeted_status']['id'];
				//查询出原创guid
				$s = "select guid from ".DATABASE_WEIBO." where id = '{$item['retweeted_status']}' and sourceid = {$sourceid}";
				$sr = $dsql->ExecQuery($s);
				if(!$sr)
				{
					$logger->error("sql is ".$s." mysql error is ".$dsql->GetError());
				}
				$res = $dsql->GetArray($sr);
				$dsql->FreeResult($sr);
				if(isset($res['guid']))
					$item['retweeted_guid'] = $res['guid'];
			}
		}
		else
		{
			$is_repost = 0;
		}*/
        //$item['is_repost'] = $is_repost;
        //$item['content_type'] = $item['is_repost'];
        //if(isset($item['floor']) && $item['floor']>0)
        //$item['content_type'] = 2 ;//评论
        //$item['repost_trend_cursor'] = $is_repost+1;

        //转发和评论的默认深度为2，原创的深度为1
        switch ($item['content_type']) {
            case 0:
                $item['repost_trend_cursor'] = 1;
                $item['is_repost'] = 0;
                break;
            case 1: //转发
                $item['is_repost'] = 1;
                $item['repost_trend_cursor'] = 2;
                break;
            case 2: //评论
                $item['repost_trend_cursor'] = 2;
                $item['is_repost'] = 0;
                break;
            case 3://提问
                if ($item['question_id'] == 0) {
                    $item['repost_trend_cursor'] = 1;
                    $item['is_repost'] = 0;
                } else {
                    $item['repost_trend_cursor'] = 2;
                    $item['is_repost'] = 0;
                }
                break;
            case 4://回答
                $item['repost_trend_cursor'] = 2;
                $item['is_repost'] = 0;
                break;
            default:
                break;
        }
        if (isset($item['reposts_count'])) {
            //$item['direct_reposts_count'] = ($item['content_type'] == 1) ?  $item['reposts_count'] : 0;
            $item['direct_reposts_count'] = $item['reposts_count'];
            $item['total_reposts_count'] = $item['reposts_count'];
        }
        //这段用到了数据库，处理：annotations
        $annotations = empty($item['annotations']) ? '' : $item['id']; //附加信息，不为空时填写微博id
        $id = isset($item['annotations'][0]['id']) ? $item['annotations'][0]['id'] : 0; //微群号
        if ($annotations) {
            $appid = isset($item['annotations'][0]['appid']) ? $dsql->Esc($item['annotations'][0]['appid']) : 0;
            $name = isset($item['annotations'][0]['name']) ? $dsql->Esc($item['annotations'][0]['name']) : '';
            $title = isset($item['annotations'][0]['title']) ? $dsql->Esc($item['annotations'][0]['title']) : '';
            $url = isset($item['annotations'][0]['url']) ? $dsql->Esc($item['annotations'][0]['url']) : '';
            $skey = isset($item['annotations'][0]['skey']) ? $dsql->Esc($item['annotations'][0]['skey']) : '';
            $server_ip = isset($item['annotations'][0]['server_ip']) ? $dsql->Esc($item['annotations'][0]['server_ip']) : '';
            $cartoon = (isset($item['annotations'][0]['cartoon']) && $item['annotations'][0]['cartoon']) == false ? 0 : 1;
            if ($id !== 0) {
                $annscount++;
                $sa = "select `server_ip` from annotations where id='" . $id . "'"; //去掉重复weibo
                $sqlstart_time = microtime_float();
                $sra = $dsql->ExecQuery($sa);
                $sqlend_time = microtime_float();

                //统计时间
                addTime4Statistic($timeStatisticObj,DB_SELECT_TIME_KEY,$sqlend_time - $sqlstart_time);
                if (!$sra) {
                    $flag = false;
                    $logger->error("sql is " . $sa . " mysql error is " . $dsql->GetError());
                }


                $res = $dsql->GetTotalRow($sra);
                $dsql->FreeResult($sra);
                if (!$res) {
                    $annicount++;
                    $ai = "insert into `" . DATABASE_ANNOTATIONS . "` (`weiboid`,`id`,`appid`,`name`,`title`,`url`,`skey`,`server_ip`, `cartoon`) values('" . $item['id'] . "', '" . $id . "', '" . $appid . "', '" . $name . "', '" . $title . "', '" . $url . "', '" . $skey . "', '" . $server_ip . "', '" . $cartoon . "')";

                    $sqlstart_time = microtime_float();
                    $aq = $dsql->ExecQuery($ai);
                    $sqlend_time = microtime_float();

                    //统计时间
                    addTime4Statistic($timeStatisticObj,DB_INSERT_TIME_KEY,$sqlend_time - $sqlstart_time);

                    if (!$aq) {
                        $logger->error($dsql->GetError() . " sql is " . $ai);
                        $flag = false;
                    }
                    $dsql->FreeResult($ai);
                }
            }
        }
        $item['annotations'] = $id;
        //$logger->debug(__FILE__.__LINE__." 处理后item： ".var_export($item, true));
        //$logger->debug(__FILE__.__LINE__." solr原来的： ".var_export($solrarticle[$guid], true));


        //-------------------设置是否需要插入/更新间接guid
        $indirect_tmp = array();
        $indirect_tmp['add_guid_ori'] = 0; //原创一定是先插入的，其实不需补充
        $indirect_tmp['add_guid_father'] = 0; //回复
        $indirect_tmp['add_guid_doc'] = 0;
        //$indirect_tmp['add_quote_floor'] = 0; //引用还没有设guid

        //father_guid, retweeted_guid
        //非原创文章。
        if ($item['content_type'] != 0) {
            //存在原创文章 但是 solr中存储的原创的guid为空
            if ($item['analysis_status'] != ANALYSIS_STATUS_ORGNOTEXIST && empty($solrarticle['retweeted_guid'])) {
                $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-当前文章为[非原创]--+-原创信息存在--+-查询原创的guid...");
                // 入库方式的修改，引起的逻辑修改。 把本次抓取的原创放在数组中，先查数组是否有，没有则查solr。
                if( in_array($item['retweeted_mid'],$OriginalIdArray) ){
                    $logger->debug(__FUNCTION__.__FILE__.__LINE__."存在原创中");
                    $tmp = $item['sourceid']."_".$item['retweeted_mid'];
                }else{
                    $tmp = getOriginalGuidFromSolr($item, $data_is_partial,$timeStatisticObj);
                }
                // end   by yu 2017/3/23
//                $tmp = getOriginalGuidFromSolr($item, $data_is_partial,$timeStatisticObj);
                if ($tmp === false) {
                    //$logger->error("查询原创失败，本文章：" . var_export($item, true));
                    $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-当前文章为[非原创]--+-原创信息存在--+-查询原创失败!");
                    $result['result'] = false;
                    $result['msg'] = "查询原创失败";
                    return $result;
                } else if (!empty($tmp)) {
                    $item['retweeted_guid'] = $tmp;
                    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-当前文章为[非原创]--+-原创信息存在--+-查询原创Guid成功:[" . $tmp . "].");
                }
            }
            //回复, reply_father_floor/reply_father_mid -> father_guid
            if ((isset($item['reply_father_floor']) || isset($item['reply_father_mid'])) && empty($solrarticle['father_guid'])) {
                if (isset($item['reply_father_floor']) && $item['reply_father_floor'] == 0 && isset($item['retweeted_guid'])) {
                    $item['father_guid'] = $item['retweeted_guid'];
                } else {
                    if (isset($item['reply_father_floor']) && isset($floor_cache[$item['source_host']])) {
                        foreach ($floor_cache[$item['source_host']] as $each_floor) {
                            if ($each_floor == strval($item['reply_father_floor'])) {
                                //在本页，现在不可以查，最后supply的时候查
                                $indirect_tmp['add_guid_father'] = 1;
                                break;
                            }
                        }
                    } else if (isset($item['reply_father_mid']) && isset($mid_cache[$item['source_host']])) {
                        foreach ($mid_cache[$item['source_host']] as $each_mid) {
                            if ($each_mid == strval($item['reply_father_mid'])) {
                                $indirect_tmp['add_guid_father'] = 1;
                                break;
                            }
                        }
                    }
                    //未被上面的设置，说明不在本页，已入库，现在就可查
                    if (!$indirect_tmp['add_guid_father']) {
                        //  入库方式的修改引起的逻辑修改。
                        if(in_array($item['retweeted_mid'],$OriginalIdArray)){
                            $logger->info(__FUNCTION__.__FILE__.__LINE__."存在原创中2");
                            $tmp = $item['sourceid']."_".$item['retweeted_mid'];
                        }else{
                            $tmp = getFatherGuidFromSolr($item, $data_is_partial,$timeStatisticObj);
                        }
                        //end   by yu 2017/3/23
//                        $tmp = getFatherGuidFromSolr($item, $data_is_partial,$timeStatisticObj);
                        if ($tmp === false) {
                            $logger->error("查询father失败，本文章：" . var_export($item, true));
                            $result['result'] = false;
                            $result['msg'] = "查询father失败";
                            return $result;
                        } else if (!empty($tmp)) {
                            $item['father_guid'] = $tmp;
                        }
                    }
                }
            }
            //引用, quote_father_mid -> father_floor
            /*if((isset($item['quote_father_mid'])) && !isset($item['father_floor'])){
				if(!empty($mid_cache[$item['source_host']])){
					foreach($mid_cache[$item['source_host']] as $each_mid){
						if($each_mid == $item['quote_father_mid']){
							$indirect_tmp['add_quote_floor'] = 1;
							break;
						}
					}
				}
				//引用不在本批数据
				if(!$indirect_tmp['add_quote_floor']){
					$tmp = getQuoteFromSolr($item, array('floor'));
					if(!empty($tmp)){
						$item['father_floor'] = $tmp['floor'];
					}
					else if(!$data_is_partial){
						$logger->error("引用不存在，本文章：".var_export($item,true));
						$result['result'] = false;
						$result['msg'] = "引用不存在";
						return $result;
					 }
				}
			}*/
        }
        if ($indirect_tmp['add_guid_ori'] || $indirect_tmp['add_guid_father']) {
            if ($is_exist) {
                $indirect_tmp['guid'] = $item['guid'];
            }
            //guid得不到，需要字段参见函数getArticleGuidOrMore
            $indirect_tmp['id'] = isset($item['id']) ? $item['id'] : NULL;
            $indirect_tmp['mid'] = isset($item['mid']) ? $item['mid'] : NULL;
            $indirect_tmp['original_url'] = isset($item['original_url']) ? $item['original_url'] : NULL;
            $indirect_tmp['floor'] = isset($item['floor']) ? $item['floor'] : NULL;
            $indirect_tmp['reply_father_floor'] = isset($item['reply_father_floor']) ? $item['reply_father_floor'] : NULL;
            $indirect_tmp['child_post_id'] = isset($item['child_post_id']) ? $item['child_post_id'] : NULL;
            $indirect_tmp['question_id'] = isset($item['question_id']) ? $item['question_id'] : NULL;
            $indirect_tmp['answer_id'] = isset($item['answer_id']) ? $item['answer_id'] : NULL;
            $indirect_tmp['question_father_id'] = isset($item['question_father_id']) ? $item['question_father_id'] : NULL;
            $indirect_tmp['answer_father_id'] = isset($item['answer_father_id']) ? $item['answer_father_id'] : NULL;
            $indirect_tmp['sourceid'] = isset($sourceid) ? $sourceid : NULL;
            $indirect_tmp['source_host'] = isset($item['source_host']) ? $item['source_host'] : NULL;
            $indirect_tmp ['paragraphid'] = isset($item['paragraphid']) ? $item['paragraphid'] : NULL;
            //$indirect_tmp['page_url'] = isset($item['page_url'])?$item['page_url']:NULL;
            //需要的字段参见函数getOriginalGuidFromSolr
            if ($indirect_tmp['add_guid_ori']) {
                //$logger->debug('add_guid_ori');
                $indirect_tmp['retweeted_mid'] = isset($item['retweeted_mid']) ? $item['retweeted_mid'] : NULL;
                //$indirect_tmp['retweeted_status'] = isset($item['retweeted_status'])?$item['retweeted_status']:NULL;
            }
            //需要的字段参见函数getFatherGuidFromSolr
            if ($indirect_tmp['add_guid_father']) {
                //$logger->debug('add_guid_father');
                $indirect_tmp['retweeted_guid'] = isset($item['retweeted_guid']) ? $item['retweeted_guid'] : NULL;
                $indirect_tmp['reply_father_floor'] = isset($item['reply_father_floor']) ? $item['reply_father_floor'] : NULL;
                $indirect_tmp['reply_father_mid'] = isset($item['reply_father_mid']) ? $item['reply_father_mid'] : NULL;
            }
            //if($indirect_tmp['add_guid_doc']){
            //$logger->debug('add_guid_doc');
            //$indirect_tmp ['page_url'] = isset($item['page_url'])?$item['page_url']:NULL;
            //$indirect_tmp ['paragraphid'] = isset($item['paragraphid'])?$item['paragraphid']:NULL;
            //}
            //if($indirect_tmp['add_quote_floor']){
            //$indirect_tmp['quote_father_mid'] = $item['quote_father_mid'];
            //}
            $logger->debug('indirect_tmp: ' . var_export($indirect_tmp, true));
            $indirect_guid_query_conds[] = $indirect_tmp;
        }

        //与solr比较
        unset($solr_arr);
        $solr_arr = array();
        global $solr_article_user_tags;
        //循环设置solr字段，是否更新比较solr字段 add by wangcc 文章中的字段和 文章中包含user的字段进行合并 将合并后的字段 最为要处理(判断变更)的字段集合
        $compereFileds = array_merge($solr_article_tags, $solr_article_user_tags);
        foreach ($compereFileds as $tag) {
            if (isset($item[$tag])) {
                $itemValue = $item[$tag];
                $itemType = gettype($itemValue);

                //为每条转发加上正确的父级微博id
                if ($item[$tag] == 'pid') {
                    if (isset($item['pid']) & !empty($item['pid'])) {
                        settype($itemValue,"string");
                        $solr_arr['pid'] = $item['pid'];
                    }
                }

                //double 精度
                if ($itemType == "double" || $itemType == "float") {
                    $itemValue = sprintf("%.3f", $itemValue);
                    $itemValue = doubleval($itemValue);
                }
                //$logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-生成solr字段--+-处理字段:[" . $tag . "] 当前值:[" . $itemValue . "] type:[" . $itemType . "].");

                if (!isset($solrarticle[$tag])) {
                    if ($itemType == 'string'){
                        $itemValue = trim($itemValue);
                        if(empty($itemValue)){
                            continue;
                        }
                    }
                    if ($itemType == 'array' && empty($itemValue))
                        continue;
                    if ($itemType == 'array' && isset($itemValue['content']) && is_string($itemValue['content'])){
                        $itemValue['content'] = trim($itemValue['content']);
                        if(empty($itemValue['content'])){
                            continue;
                        }
                    }
                }
                //空白字符串过滤。注意，如果item是空白字符串，solr中有值，则最终会把原有的值删掉
                //if(!isset($solrarticle[$tag]) && $itemType=='string' && trim($itemValue) == "" )
                //continue;

                //现在schema中无bool型，转换
                if ($itemType == 'boolean') {
                    $itemValue = ($itemValue == false ? 0 : 1);
                }

                //new article
                if (!$is_exist) {
                    $solr_arr[$tag] = $itemValue;
                    //$logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-文章不存在--+-添加文章--+-生成solr字段完成--+-solrArray中该字段:[{$tag}] 的值:[" . $solr_arr[$tag] . "]!");
                } //exist article
                else {
                    //该字段不作为更新依据
                    if ($tag == 'analysis_status' || $tag == 'timeline_type' || $tag == 'pg_text' || $tag == 'repost_trend_cursor')
                        continue;
                    if ($tag == 'post_title' || $tag == 'text' || $tag == 'description' || $tag == 'verified_reason') {
                        $tmp_itemValue = $itemValue;
                        $itemValue = $itemValue['content']; //用content比较
                        if ($tag == 'text') {
                            //if(gettype($itemValue)=='array')
                            //$logger->debug("text toBR:".var_export($itemValue,true));
                            $itemValue = transferToRN($itemValue);
                        }
                    }
                    //该字段solr已有
                    if (isset($solrarticle[$tag])) {

                        $itemType = gettype($itemValue);
                        $solrInfoType = gettype($solrarticle[$tag]);
                        //类型不同，做转换，以solr的结构为准，将item的转换为solr的数据结构
                        if ($itemType != $solrInfoType) {
                            if ($solrInfoType == 'array')
                                $itemValue = array($itemValue);
                            switch ($itemType) {
                                case 'string':
                                    //过滤空白字符串
                                    if ($solrInfoType == 'integer')
                                        $itemValue = intval($itemValue);
                                    else if ($solrInfoType == 'double')
                                        $itemValue = floatval($itemValue);
                                    break;
                                case 'double':
                                case 'integer':
                                    if ($solrInfoType == 'string') {
                                        $itemValue = strval($itemValue);
                                    } else if ($solrInfoType == 'double') {
                                        $itemValue = $itemValue * 1.00;
                                    }
                                    break;
                                case 'array':
                                    $logger->debug(__FILE__ . __LINE__ . "itemValue " . var_export($itemValue, true) . "tag" . var_export($tag, true));
                                    if ($solrInfoType == 'string')
                                        $itemValue = implode(' ', $itemValue);
                                    break;
                                default:
                                    break;
                            }
                        }
                        //检查类型，即使不通过，也往下走
                        if (gettype($itemValue) != $solrInfoType) {
                            $logger->error(__FILE__ . __LINE__ . " different type, can't compare! " . $tag . " " . $itemValue . " " . $solrarticle[$tag]);
                            $logger->error(__FILE__ . __LINE__ . " different type, can't compare! " . $tag . " " . gettype($itemValue) . " " . $solrInfoType);
                        }
                        //值不同才设置solr_arr
                        if ($solrarticle[$tag] != $itemValue) {
                            if ($tag == 'post_title' || $tag == 'text' || $tag == 'description' || $tag == 'verified_reason') {
                                $solr_arr[$tag] = $tmp_itemValue;
                            } //status结构中拆分出的原创comments_count是0，不准的
                            else if ($tag == 'comments_count' || $tag == 'reposts_count' || $tag == 'direct_reposts_count' || $tag == 'total_reposts_count') {
                                if ($itemValue != 0) {
                                    $solr_arr[$tag] = $itemValue;
                                }
                            } else{
                                if($tag != "professionType"){
                                    $solr_arr[$tag] = $itemValue;
                                }else{
                                    $logger->info(__FUNCTION__.__FILE__.__LINE__."the data is:".var_export($solr_arr['professionType'],true));
                                }
                            }
                        }
                    } //该字段solr没有，加上
                    else {
                        $logger->debug("exist article. update solr key:" . $tag . " for new value:" . $itemValue);
                        if ($tag == 'post_title' || $tag == 'text' || $tag == 'description' || $tag == 'verified_reason')
                            $solr_arr[$tag] = $tmp_itemValue;
                        else{
                            if($tag != "professionType"){
                                $solr_arr[$tag] = $itemValue;
                            }else{
                                $logger->info(__FUNCTION__.__FILE__.__LINE__."the data is:".var_export($solr_arr['professionType'],true));
                            }
                        }                    }
                    //$logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-生成solr字段完成--+-solrArray中该字段:[{$tag}]的值:[" . $solr_arr[$tag] . "]!");
                }
            } else {
                //$logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-生成solr字段--+-该字段:[{$tag}]的值没有被set. AllData:[" . var_export($item, true) . "].");
            }
        }

        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-设置solr字段完成--+-solrArray:[" . var_export($solr_arr, true) . "].");
        //设置sql数据库字段：全部有值的。
        unset($sql_arr);
        $sql_arr = array();
        foreach ($sql_article_tags as $tag) {
            if (isset($item[$tag])) {
                //新文章和已存在文章都改变所有字段，不再比较
                //if(!$is_exist)
                $sql_arr[$tag] = $item[$tag];
            }
        }

        $content_type_tmp = $item['content_type'];

        //新记录
        if (!$is_exist) {
            //补插段落
            if (!empty($solr_arr['pg_text'])) {
                $general_pg_tmp = array();
                //$general_pg_tmp['content_type'] = $content_type_tmp;
                //$general_pg_tmp['analysis_status'] = $analysis_status_tmp;
                copyFieldValue4ParagraphDoc($general_pg_tmp,$solr_arr);
                //补充段落，并按照当前文章的设置，添加更新gocId的配置
                supplyParagraph4NewDoc($result,$solr_arr['pg_text'],$general_pg_tmp,$indirect_guid_query_conds,$content_type_tmp, $analysis_status_tmp,$issegmented);
//                foreach ($solr_arr['pg_text'] as $pgindex => $pg) {
//                    $tmp = array();
//                    $tmp['pg_text'] = $pg;
//                    $tmp['paragraphid'] = $pgindex + 1;
//                    $tmp = array_merge($tmp, $general_pg_tmp);
//
//                    //补充docguid标记
//                    $indirect_tmp = array();
//                    $indirect_tmp['add_guid_doc'] = 1;
//                    $indirect_tmp = array_merge($tmp, $indirect_tmp);
//                    $indirect_guid_query_conds[] = $indirect_tmp;
//                    //$logger->debug('indirect_tmp: '.var_export($indirect_tmp,true));
//
//                    //插入的段落guid
//                    $tmp['guid'] = setArticleGuid($tmp);
//                    if ($tmp['guid'] === false) {
//                        return array("result" => false, "msg" => "设置段落guid失败");
//                    }
//                    $result["send_solr_data"][] = $tmp;
//                    $logger->debug("新文章，补充段落：" . var_export($tmp, true));
//                }

                //把自己的pg_text去掉
                unset($solr_arr['pg_text']);
            }

            //time不作为比较更新的字段，这里加上
            $sql_arr['analysis_time'] = time();
            $sql_arr['repost_trend_update'] = time();//更新转发数、评论数的时间
            $sql_arr['update_time'] = time();
            $sql = insert_template(DATABASE_WEIBO, $sql_arr);

            $sqlstart_time = microtime_float();
            $query = $dsql->ExecNoneQuery($sql);
            $sqlend_time = microtime_float();
            //$logger->debug(__FILE__ . __LINE__ . " 插入数据库 use time " . ($istend_time - $iststart_time));
            //统计时间
            addTime4Statistic($timeStatisticObj,DB_INSERT_TIME_KEY,$sqlend_time - $sqlstart_time);

            if (!$query) {
                $sql_error = $dsql->GetError();
                $logger->error("inner_insert_status insert sql is:{$sql}  error is " . $sql_error);
                continue;
            }
            if (!empty($query)) {
                $dsql->FreeResult($query);
            }
            //sql插入成功后再赋值solr
            $result["send_solr_data"][] = $solr_arr;
            $logger->debug("需要新增的文章： " . var_export($solr_arr, true));
        } //exist
        else {//更新微博信息
            $logger->debug("文章存在,准备更新solr...solr_arr:" . var_export($solr_arr, true));
            //文章不存在时候，$solr_arr字段中存储的是所有需要更新的字段，不需要更新的字段不应该设置
            if (!empty($solr_arr)) {//需要更新的字段不为空
                $sql_arr['update_time'] = time();
                $sql_arr['repost_trend_update'] = time();//更新转发数、评论数的时间
                $sql_arr['update_time'] = time();

                //text字段需要更新
                if (isset($solr_arr['text'])) {
                    //$solr_arr['text'] = $text_tmp;
                    $solr_arr['content_type'] = $content_type_tmp;
                    $solr_arr['analysis_status'] = $analysis_status_tmp;

                    //补插段落
                    $newpgnum = isset($item['pg_text']) ? count($item['pg_text']) : 0;
                    //查询出来所有的段落
                    $oldparas = getParaFromSolr($item, array('guid', 'docguid'));
                    $logger->debug(__FILE__ . __LINE__ . " para from solr:" . var_export($oldparas, true));
                    $oldpgnum = count($oldparas);
                    //$logger->debug("newpgnum:".$newpgnum." oldpgnum:".$oldpgnum);

                    if ($newpgnum || $oldpgnum) {//
                        $general_pg_tmp = array();
                        copyFieldValue4ParagraphDoc($general_pg_tmp,$item);
//                        $general_pg_tmp['original_url'] = $item['original_url'];
//                        if (isset($item['floor'])) {
//                            $general_pg_tmp['floor'] = $item['floor'];
//                        }
//                        if (isset($item['reply_father_floor'])) {
//                            $general_pg_tmp['reply_father_floor'] = $item['reply_father_floor'];
//                        }
//                        if (isset($item['child_post_id'])) {
//                            $general_pg_tmp['child_post_id'] = $item['child_post_id'];
//                        }
//                        if (isset($item['question_id'])) {
//                            $general_pg_tmp['question_id'] = $item['question_id'];
//                        }
//                        if (isset($item['answer_id'])) {
//                            $general_pg_tmp['answer_id'] = $item['answer_id'];
//                        }
//                        if (isset($item['question_father_id'])) {
//                            $general_pg_tmp['question_father_id'] = $item['question_father_id'];
//                        }
//                        if (isset($item['answer_father_id'])) {
//                            $general_pg_tmp['answer_father_id'] = $item['answer_father_id'];
//                        }
//                        $general_pg_tmp['source_host'] = $item['source_host'];
//                        if (isset($item['sourceid']))
//                            $general_pg_tmp['sourceid'] = $item['sourceid'];
//                        if (isset($item['mid']))
//                            $general_pg_tmp['mid'] = $item['mid'];

                        if ($newpgnum >= $oldpgnum) {
                            $update_pg_num = $oldpgnum;
                            $insert_pg_num = $newpgnum - $oldpgnum;
                            //给出udpate数组
                            if ($update_pg_num > 0) {
                                //循环所有的旧段落，将旧段落中的guid设置到新的段落上，
                                //如果就的段落上没有godid，则添加docid更新配置，最后更新docid
                                supplyParagraph4UpdateDoc($result, $general_pg_tmp, $indirect_guid_query_conds, $oldparas, $content_type_tmp, $analysis_status_tmp, $item,count($oldparas), $issegmented);
//                                //循环旧的数组
//                                $tmp = array();
//                                $tmp['content_type'] = $content_type_tmp;
//                                $tmp['analysis_status'] = $analysis_status_tmp;
//                                foreach ($oldparas as $oldpk => $oldp) {
//                                    $tmp['guid'] = $oldp['guid'];
//                                    $tmp['pg_text'] = $item['pg_text'][$oldpk];
//                                    $tmp['paragraphid'] = $oldpk + 1;
//                                    $result['update_solr_data'][] = $tmp; //插入更新数组
//                                    //$logger->debug("新段落>旧段落，更新段落：".var_export($tmp,true));
//
//                                    if (empty($oldp['docguid'])) {
//                                        $indirect_tmp = array();
//                                        $indirect_tmp['add_guid_doc'] = 1;
//                                        $indirect_tmp = array_merge($tmp, $general_pg_tmp, $indirect_tmp);
//                                        //$logger->debug('indirect_tmp: '.var_export($indirect_tmp,true));
//                                        $indirect_guid_query_conds[] = $indirect_tmp;
//                                    }
//                                }
                            }
                            //给出insert数组
                            if ($insert_pg_num > 0) {
                                $skipnum = $update_pg_num;
                                //补充段落，并按照当前文章的设置，添加更新gocId的配置
                                supplyParagraph4NewDoc($result,$item['pg_text'],$general_pg_tmp,$indirect_guid_query_conds,$content_type_tmp, $analysis_status_tmp,$issegmented,$skipnum);

                                //$tmp = array();
                                //$tmp['content_type'] = $content_type_tmp;
                                //$tmp['analysis_status'] = $analysis_status_tmp;
                                //循环
//                                for ($i = $update_pg_num; $i < $newpgnum; $i++) {
//                                    $tmp['pg_text'] = $item['pg_text'][$i];
//                                    $tmp['paragraphid'] = $update_pg_num + 1;
//                                    $tmp = array_merge($tmp, $general_pg_tmp);
//
//                                    $indirect_tmp = array();
//                                    $indirect_tmp['add_guid_doc'] = 1;
//                                    $indirect_tmp = array_merge($tmp, $indirect_tmp);
//                                    //$logger->debug('indirect_tmp: '.var_export($indirect_tmp,true));
//                                    $indirect_guid_query_conds[] = $indirect_tmp;
//
//                                    $tmp['guid'] = setArticleGuid($tmp);
//                                    if ($tmp['guid'] === false) {
//                                        return array("result" => false, "msg" => "设置段落guid失败");
//                                    }
//                                    $result["send_solr_data"][] = $tmp;
//                                }
                            }
                        } else {
                            //$update_pg_num = $newpgnum;
                            //$delete_pg_num = $oldpgnum - $newpgnum;
                            //循环旧数组，给出update和delete
                            $logger->debug(__FILE__ . __LINE__ . " oldparas " . var_export($oldparas, true));
                            supplyParagraph4UpdateDoc($result,$general_pg_tmp , $indirect_guid_query_conds, $oldparas, $content_type_tmp, $analysis_status_tmp, $item,$newpgnum, $issegmented);
//                            foreach ($oldparas as $oldpk => $oldp) {
//                                $tmp = array();
//                                if ($oldpk < $update_pg_num) {
//                                    $tmp['content_type'] = $content_type_tmp;
//                                    $tmp['analysis_status'] = $analysis_status_tmp;
//                                    $tmp['guid'] = $oldp['guid'];
//                                    $tmp['paragraphid'] = $oldpk + 1;
//                                    $tmp['pg_text'] = $item['pg_text'][$oldpk];
//                                    $result['update_solr_data'][] = $tmp;
//                                    //$logger->debug("新段落<旧段落，更新段落：".var_export($tmp,true));
//
//                                    if (empty($oldp['docguid'])) {
//                                        $indirect_tmp = array();
//                                        $indirect_tmp['add_guid_doc'] = 1;
//                                        $indirect_tmp = array_merge($tmp, $indirect_tmp);
//                                        //$logger->debug('indirect_tmp: '.var_export($indirect_tmp,true));
//                                        $indirect_guid_query_conds[] = $indirect_tmp;
//                                    }
//                                } else {
//                                    $tmp['guid'] = $oldp['guid'];
//                                    $result['delete_solr_data'][] = $tmp;
//                                    //$logger->debug("新段落<旧段落，删除段落：".var_export($tmp,true));
//
//                                }
//                            }
                        }
                    }
                }


                //必要字段
                $solr_arr['guid'] = $guid;
                if (isset($sourceid))
                    $solr_arr['sourceid'] = $sourceid;

                //更新sql数据库
                $wh_updatearr = array();//条件字段
                $wh_updatearr['guid'] = $sql_arr['guid'];
                $up_sql = update_template(DATABASE_WEIBO, $sql_arr, $wh_updatearr);
                $upstart_time = microtime_float();
                $upqr = $dsql->ExecNoneQuery($up_sql);
                $upend_time = microtime_float();
                //$logger->debug(__FILE__ . __LINE__ . " 更新数据库 use time " . ($upend_time - $upstart_time));
                //统计时间
                addTime4Statistic($timeStatisticObj,DB_UPDATE_TIME_KEY,$upend_time - $upstart_time);

                if (!$upqr) {
                    $sql_error = $dsql->GetError();
                    $logger->error(__FILE__ . __LINE__ . " sql is:{$up_sql}  error is " . $sql_error);
                    continue;
                }
                if (empty($sceneParentNode->scene->update_weibocount)) {
                    $sceneParentNode->scene->update_weibocount = 0;
                }
                $sceneParentNode->scene->update_weibocount++;//已存在且需要更新的
                $result['update_solr_data'][] = $solr_arr;
                $logger->debug(__FILE__ . __LINE__ . " 需要更新的solr_arr： " . var_export($solr_arr, true));
                $logger->debug(__FILE__ . __LINE__ . " 原来的： " . var_export($solrarticle, true));
            } else {
                $logger->debug("文章存在,准备更新solr--+-需要更新的字段为空!");
            }
        }
        $ieachend_time = microtime_float();
        $logger->info(__FILE__ . __LINE__ . " insert database each use time " . ($ieachend_time - $ieachstart_time));
    }
    if ($statistics_info == 'task') {
        $task = $statistics_info;
    }

    //$logger->debug("exit inner_insert_status");
    //return $send_solr_data;
    //$logger->debug("send solr data:".var_export($result["send_solr_data"],true));
    return $result;
}

/**
 *
 * 根据小图路径得到大图和中图
 * @param $sourceid
 * @param $smallpic 小图路径
 * @param $outtype  输出类型
 */
function convertPicturePath($sourceid, $smallpic, $outtype)
{
    global $logger;
    $outpicpath = "";
    switch ($sourceid) {
        case 1:
            if ($outtype == PICTURE_TYPE_BIG) {
                $outpicpath = str_replace("/thumbnail/", "/large/", $smallpic);
            } else if ($outtype == PICTURE_TYPE_MIDDLE) {
                $outpicpath = str_replace("/thumbnail/", "/bmiddle/", $smallpic);
            } else if ($outtype == PICTURE_TYPE_SMALL) {
                $outpicpath = str_replace("/square/", "/thumbnail/", $smallpic);
            }
            break;
        default:
            break;
    }
    return $outpicpath;
}

/**
 *
 * 设置认证字段、认证类型, 用户级别字段
 * @param $result
 * @param $apidata
 */
//users_verified_type变成了多值
function setVerified(&$result, $user)
{
    //如果verified已设
    if (isset($user['verified'])) {
        $result['verified'] = (isset($user['verified']) && $user['verified'] == true) ? 1 : 0;
        $result['verified_type'] = isset($user['verified_type']) ? $user['verified_type'] : NULL;
    } //verified没设
    else {
        //如果type有值，则设置verified为真（爬虫抓取的没有verified字段）
        if (isset($user['verified_type'])) {
            $result['verified'] = 1;//有认证类型，说明是认证用户
            $result['verified_type'] = $user['verified_type'];
        }
        //如果verified_type没有设置
        //达人,根据level字段获取认证类型（非weibo不进）
        else if (isset($user['daren'])) {
            $result['verified'] = 2;
            $result['verified_type'] = isset($user['daren']['level']) ? getVerifiedType($user['daren']['level']) : '';
        } //verified和verified_type都没有设置（非weibo进）
        else {
            $result['verified'] = 0;
            $result['verified_type'] = -1;
        }
    }
    //这里是weibo特殊处理，weibo抓下来的不是array。（非weibo不进）
    if (gettype($result['verified_type']) != 'array') {
        if ($result['verified_type'] == 10) {//微女郎,改成普通用户
            $result['verified_type'] = -1;
        }
        //认证类型为机构类时，将verified置为3（企业机构）
        if ($result['verified'] == 1 && $result['verified_type'] != ''
            && $result['verified_type'] != 0
        ) {
            $result['verified'] = 3;
        }
        if ($result['verified'] == 0 && !empty($result['verified_type'])
            && $result['verified_type'] != -1
        ) {
            $result['verified'] = 2;//微博达人
        }
    }
}

/**
 *
 * get方式请求solr查询数据
 * 发生错误时
 * @param $url
 */
function getSolrData($url)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . " url " . $url . "");
    $result = array();
    if (empty($url)) {
        $result['errorcode'] = -1;
        $result['errormsg'] = "参数错误";
        return $result;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, DEFAULT_HTTP_TIMEOUT * 60);
    $response = curl_exec($ch); //服务器返回的响应，包括正确和错误信息（数据级别错误）
    if ($response === false) {//失败
        $errno = curl_errno($ch);
        if (28 == $errno) { //28 CURLE_OPERATION_TIMEDOUT
            $errorcode = WEBERROR_TIMEOUT;
            $errormsg = "查询超时";
        } else {
            $errorcode = WEBERROR_CURLERROR;
            $errormsg = "访问数据服务器异常";
            $log_note = 'curl error is' . curl_error($ch);
            $logger->error(__FILE__ . __LINE__ . " url: " . $url . " " . $log_note);
        }
    } else {
        if (empty($response)) {
            $errorcode = 0;
            $errormsg = "返回格式错误";
        } else {
            $data = json_decode($response, true);
            $logger->debug(__FILE__ . __LINE__ . " data " . var_export($data, true));
            if ($data == null) {
                $logger->error(__FILE__ . __LINE__ . "url：{$url} curl response:{$response}");
                $errorcode = -1;
                $errormsg = "查询出错";
            } else {
                if (!empty($data['response'])) {
                    $result['query'] = $data['response'];
                }
                if (!empty($data['facet_counts']) && !empty($data['facet_counts']['facet_fields'])) {
                    $result['facet_field'] = $data['facet_counts']['facet_fields'];
                }
                if (!empty($data['facet_counts']) && !empty($data['facet_counts']['facet_ranges'])) {
                    $result['facet_range'] = $data['facet_counts']['facet_ranges'];
                }
            }
        }
    }
    curl_close($ch);
    if (!empty($errormsg)) {
        $result['errorcode'] = $errorcode;
        $result['errormsg'] = $errormsg;
    }
    return $result;
}

/*
 * @brief 生成特征分类的查询条件
 * @param string $field 特征分类字段
 * @param string $pclass 两级分类的父级
 * @param string $class 两级分类的子级
 * @param string/array/true $keyword 特征分类关键词,支持关键词和关键词数组, true表示feature_keyword:*
 * @param string $feature_father_guid 已选中的feature_father_guid
 * @param string $guid 已选中的guid
 * @return array 查询条件数组
 * @author Bert
 * @date 2016-6-19
 * @change 2016-6-19
 * */
function formatFeatureParams($field = NULL, $pclass = NULL, $class = NULL, $keyword = NULL, $feature_father_guid = NULL, $guid = NULL)
{
    global $logger;
    $urlparams = array();
    if (isset($field)) {
        if (is_array($field)) {
            if (count($field) > 1) {
                $tarr = array();
                foreach ($field as $key => $value) {
                    if ($value == "") {
                        continue;
                    }
                    $tarr[] = "feature_field:" . $value;
                }
                if (!empty($tarr)) {
                    $urlparams[] = "(" . implode("+OR+", $tarr) . ")";//用OR关系关联
                }
            } else if (count($field) > 0 && $field[0] != "") {
                $urlparams[] = "feature_field:" . $field[0];
            }
        } else {
            $fieldstr = $field === true ? "*" : $field;
            $urlparams[] = "feature_field:{$fieldstr}";
        }
    }
    if (isset($pclass)) {
        $pclass = $pclass === true ? "*" : $pclass;
        $urlparams[] = "feature_pclass:" . solrEsc($pclass);
    }
    if (isset($class)) {
        $class = $class === true ? "*" : $class;
        $urlparams[] = "feature_class:" . solrEsc($class);
    }

    if (isset($feature_father_guid)) {
        $feature_father_guid = $feature_father_guid === true ? "*" : $feature_father_guid;
        $urlparams[] = "feature_father_guid:" . solrEsc($feature_father_guid);
    }

    if (isset($guid)) {
        if (is_array($guid)) {
            if (count($guid) > 1) {
                $tarr = array();
                foreach ($guid as $key => $value) {
                    if ($value == "") {
                        continue;
                    }
                    $tarr[] = "guid:" . $value["guid"];
                }
                if (!empty($tarr)) {
                    $urlparams[] = "(" . implode("+OR+", $tarr) . ")";//用OR关系关联
                }
            } else if (count($guid) > 0 && $guid[0] != "") {
                $urlparams[] = "guid:" . $guid[0]["guid"];
            }
        } else {
            $guid = $guid === NULL ? "*" : $guid;
            $urlparams[] = "guid:" . solrEsc($guid);
        }
    }
    /*
    if (isset($guid)) {
        $guid = $guid === NULL ? "*" : $guid;
        $urlparams[] = "guid:" . solrEsc($guid);
    }*/
    if ($keyword !== NULL) {
        if (is_array($keyword)) {
            if (count($keyword) > 1) {
                $temparr = array();
                foreach ($keyword as $key => $value) {
                    if ($value == "") {
                        continue;
                    }
                    $temparr[] = "feature_keyword:" . solrEsc($value);
                }
                if (!empty($temparr)) {
                    $urlparams[] = "(" . implode("+OR+", $temparr) . ")";//用OR关系关联
                }
            } else if (count($keyword) > 0 && $keyword[0] != "") {
                $urlparams[] = "feature_keyword:" . solrEsc($keyword[0]);
            }
        } else {
            $keyword = $keyword === true ? "*" : $keyword;
            $urlparams[] = "feature_keyword:" . solrEsc($keyword);
        }
    }
    $logger->debug(__FILE__ . __LINE__ . " urlparams " . var_export($urlparams, true));
    return $urlparams;
}

/*
 * @brief getUrlParamsByField  获取formatFeatureParams结果中的具体某个字段的参数
 * @param Array $urlparams
 * @param String $field 要获取的字段
 * @return urlparams中符合条件的item
 * @author Bert
 * @date 2016-6-19
 * @change 2016-6-19
 * */
function getUrlParamsByField($urlparams, $field)
{
    foreach ($urlparams as $u => $key) {
        $tmpArr = explode(":", $key);
        if ($tmpArr[0] == $field) {
            return $key;
        }
    }
}

/*
 * @brief  获取特征分类词典列表
 * @param  int $start      从第几条获取
 * @param  int $count      获取多少条
 * @param  string $field   指定字段的
 * @param  string $pclass  指定父类的
 * @param  string $class   指定分类的
 * @param  string/array $keyword 匹配的关键词。支持字符串或数组
 * @param  string $feature_father_guid 指定父级guid
 * @param  string $guid 指定guid
 * @return array  包含指定字段是数据
 * @author Bert
 * @date   
 * @change 2016-6-28
 * */
function getFeatureKeyword($start, $count, $field = NULL, $pclass = NULL, $class = NULL, $keyword = NULL, $feature_father_guid = NULL, $guid = NULL)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . __FUNCTION__ . " start " . var_export($start, true) . " count " . var_export($count, true) . " filed " . var_export($field, true) . " pclass " . var_export($pclass, true) . " class " . var_export($class, true) . " keyword " . var_export($keyword, true) . "  feature_father_guid " . var_export($feature_father_guid, true) . "  guid " . var_export($guid, true) . " ");
    /*
	*当keyword字段过多时,会 报too many boolean clauses 的错误,比如使用模糊查询时,返回对应的id 超过 1024时保错
	*BooleanQuery.SetMaxClauseCount()
    *默认是1024
	*可以设置BooleanQuery.SetMaxClauseCount(比较大的数)；这样就可以解决
	*为不改solr ,现通过多次查询的方式解决
	* */
    $eachQueryNum = 1024;
    $totalkeyword = count($keyword);
    $tmpresulttotal = 0;
    $tmpresult = array();
    if ($totalkeyword > $eachQueryNum) {
        $totalres = array();
        $cursor = 0;
        do {
            $eachArr = array_slice($keyword, $cursor * $eachQueryNum, $eachQueryNum);
            if ($feature_father_guid !== NULL || $guid !== NULL) {
                $urlparams = formatFeatureParams($field, NULL, $class, $eachArr, $feature_father_guid, $guid);
            } else {
                $urlparams = formatFeatureParams($field, $pclass, $class, $eachArr, NULL, NULL);
            }
            if (empty($eachArr)) {
                $urlparams[] = "feature_keyword:*";
            }
            $qparam = implode("+AND+", $urlparams);
            $fields = array("guid", "feature_pclass", "feature_class", "feature_keyword", "feature_field", "feature_father_guid");
            $tmpresulttotal += solr_select_conds($fields, $qparam, 0, 0);
            $irows = pow(2, 31) - 1;
            $totalres += solr_select_conds($fields, $qparam, 0, $irows);
            $cursor++;
        } while ($cursor * $eachQueryNum < $totalkeyword);
        $start = empty($start) ? "0" : $start;
        $rows = empty($count) ? (pow(2, 31) - 1) : $count;
        $tmpresult = array_slice($totalres, $start, $rows);
    } else {
        if ($feature_father_guid !== NULL || $guid !== NULL) {
            $urlparams = formatFeatureParams($field, NULL, $class, $keyword, $feature_father_guid, $guid);
        } else {
            $urlparams = formatFeatureParams($field, $pclass, $class, $keyword);
        }
        if ($keyword === NULL) {
            $urlparams[] = "feature_keyword:*";
        }
        $qparam = implode("+AND+", $urlparams);
        $fields = array("guid", "feature_pclass", "feature_class", "feature_keyword", "feature_field", "feature_father_guid");
        $start = empty($start) ? "0" : $start;
        $rows = empty($count) ? (pow(2, 31) - 1) : $count;
        $tmpresulttotal = solr_select_conds($fields, $qparam, 0, 0);
        $tmpresult = solr_select_conds($fields, $qparam, $start, $rows);
    }
    $result["query"]["numFound"] = $tmpresulttotal;
    $result["query"]["docs"] = $tmpresult;
    $result["query"]["start"] = $start;

    $r;
    if ($tmpresulttotal === false) {
        $logger->error(__FILE__ . __LINE__ . " query solr error field:" . var_export($fields, true) . " params:" . $qparam . " start:" . $start . " rows:" . $rows . "");
        $r = $result;
    } else {
        $r['totalcount'] = $result['query']['numFound'];
        $r['datalist'] = $result['query']['docs'];
        $r['start'] = $result['query']['start'];
    }
    $logger->debug(__FILE__ . __LINE__ . " r " . var_export($r, true));
    return $r;
}

/*
 * @brief 获取特征分类
 * @param Number $start 起始
 * @param Number $count 条数
 * @param Boolen $ispclass 是否获取父类
 * @param String $field
 * @param String $pclass
 * @param String $feature_father_guid
 * @return Array
 * @author Bert
 * @date 2016-6-18
 * @change 2016-6-18
 * */
function getFeatureClass($start, $count, $ispclass = false, $field = NULL, $pclass = NULL, $feature_father_guid = NULL, $guid = NULL, $class = NULL, $isdistinctClass = false)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . __FUNCTION__ . " start " . $start . " count " . $count . " ispclass " . var_export($ispclass, true) . " filed " . var_export($field, true) . " pclass " . var_export($pclass, true) . " feature_father_guid " . var_export($feature_father_guid, true) . " guid " . var_export($guid, true) . " class " . var_export($class, true));
    $distinct = "";
    $fl = array("guid", "feature_father_guid", "feature_keyword", "feature_field", "feature_pclass", "feature_class");
    $urlparams = formatFeatureParams($field, $pclass, $class, NULL, $feature_father_guid, $guid);
    if ($ispclass === true) { //查看根级
        $distinct = "feature_pclass";
        /*
         * 2016-6-29 Bert 此处处理三个条件的组合, 解决字段为空的问题
         * */
        $tmpArr = array();
        $p_feature_pclass = getUrlParamsByField($urlparams, "feature_pclass");
        if (!empty($p_feature_pclass)) {
            $tmpArr[] = $p_feature_pclass;
        }
        $p_feature_father_guid = getUrlParamsByField($urlparams, "feature_father_guid");
        if (!empty($p_feature_father_guid)) {
            $tmpArr[] = $p_feature_father_guid;
        }
        $tmpStr = implode("+OR+", $tmpArr);
        $logger->debug(__FILE__ . __LINE__ . " tmpStr " . var_export($tmpStr, true));
        $tmp2Arr = array();
        if (!empty($tmpStr)) {
            $tmpStr = "(" . $tmpStr . ")";
            $tmp2Arr[] = $tmpStr;
        }
        $p_feature_field = getUrlParamsByField($urlparams, "feature_field");
        if (!empty($p_feature_field)) {
            $tmp2Arr[] = $p_feature_field;
        }
        $qparam = implode("+AND+", $tmp2Arr);
        //$qparam = getUrlParamsByField($urlparams, "feature_field")."+AND+(".getUrlParamsByField($urlparams, "feature_pclass")."+OR+".getUrlParamsByField($urlparams, "feature_father_guid").")";
        $logger->debug(__FILE__ . __LINE__ . " pclass " . var_export($qparam, true) . "");
    } else if ($ispclass === false) {
        $distinct = "feature_class";
        /*
         * 2016-6-29 Bert 此处处理三个条件的组合, 解决字段为空的问题
         * */
        $tmpArr = array();
        $p_feature_pclass = getUrlParamsByField($urlparams, "feature_pclass");
        if (!empty($p_feature_pclass)) {
            $tmpArr[] = $p_feature_pclass;
        }
        $p_feature_father_guid = getUrlParamsByField($urlparams, "feature_father_guid");
        if (!empty($p_feature_father_guid)) {
            $tmpArr[] = $p_feature_father_guid;
        }
        $tmpStr = implode("+OR+", $tmpArr);
        $logger->debug(__FILE__ . __LINE__ . " tmpStr " . var_export($tmpStr, true));
        $tmp2Arr = array();
        if (!empty($tmpStr)) {
            $tmpStr = "(" . $tmpStr . ")";
            $tmp2Arr[] = $tmpStr;
        }
        $p_feature_field = getUrlParamsByField($urlparams, "feature_field");
        if (!empty($p_feature_field)) {
            $tmp2Arr[] = $p_feature_field;
        }
        $qparam = implode("+AND+", $tmp2Arr);
        //$qparam = getUrlParamsByField($urlparams, "feature_field")."+AND+(".getUrlParamsByField($urlparams, "feature_pclass")."+OR+".getUrlParamsByField($urlparams, "feature_father_guid").")";
    } else {
        if ($isdistinctClass == true) {
            $distinct = "feature_class";
        }
        $qparam = implode("+AND+", $urlparams);

    }
    $result['query'] = array();
    $result['query']['numFound'] = solr_select_conds($fl, $qparam, 0, 0, "", $distinct);
    $r;
    if ($result['query']['numFound'] === 0) {
        $r['totalcount'] = $result['query']['numFound'];
        $r['datalist'] = array();
        $r['start'] = 0;
    } else {
        $start = empty($start) ? "0" : $start;
        $rows = empty($count) ? (pow(2, 31) - 1) : $count;
        $result['query']['docs'] = solr_select_conds($fl, $qparam, $start, $rows, "", $distinct);
        $result['query']['start'] = $start;

        if (isset($result['errorcode'])) {
            $logger->error(__FILE__ . __LINE__ . " {$result['errormsg']}");
            $r = $result;
        } else {
            if (!isset($result['query'])) {
                $r['errorcode'] = 0;
                $r['errormsg'] = "数据结构错误";
                $logger->error(__FILE__ . __LINE__ . " {$r['errormsg']}");
            } else {
                $r['totalcount'] = $result['query']['numFound'];
                $r['datalist'] = $result['query']['docs'];
                $r['start'] = $result['query']['start'];
            }
        }
    }
    $logger->debug(__FILE__ . __LINE__ . " r " . var_export($r, true));
    return $r;
}

/**
 *
 * 获取特征分类词典最大guid中的数字部分
 * 需要使用query查询进行sort排序, facet,有缓存,两次查询间隔较短时,查询不出数据.
 */
/*
function getFeatureMaxID()
{
    global $logger;
    $url = SOLR_URL_SELECT . "?q=feature_keyword:*&fl=&facet.field=*&facetCounts=2&facet.calculate.max=guid&facet.limit=1&facet=on&facet.offset=0&rows=0&facet.minsumcount=1";
    $result = getSolrData($url);
    $r;
    if (isset($result['errorcode'])) {
        $r = $result;
    } else {
        if(isset($result['facet_field'])){
            if($result['facet_field']['*']['count'] > 0){
                $guid = $result['facet_field']['*']['countList'][0]['max:guid'];
                $guidarr = explode("_", $guid);
                if (count($guidarr) > 1) {
                    $r = $guidarr[1];
                } else {
                    $r = array("errorcode" => 0, "errormsg" => "特征分类ID格式有误");
                }
            }
            else{
                $r = 1;
            }
        }
        else{
            $r = array("errorcode" => 0, "errormsg" => "数据格式有误");
        }
    }
    $logger->debug(__FILE__.__LINE__." r ".var_export($r, true));
    return $r;
}
 */

/**
 *
 * 获取特征分类词典最大guid中的数字部分
 */
/* 2015-08-19 这个函数使用到了sort需要根据最大文档数建缓存,会消耗很高的内存.修改成使用facet.calculate.max
 * 2016-06-16 修改成增量缓存,使用facet.calculate.max时因为缓存原因,两次查询间隔较短时,查询不出数据.
 */
function getFeatureMaxID()
{
    global $logger;
    $url = SOLR_URL_SELECT . "?q=feature_class:*&facet=off&fl=guid&sort=guid+desc&start=0&rows=1";
    $result = getSolrData($url);
    $r;
    if (isset($result['errorcode'])) {
        $r = $result;
    } else {
        if (isset($result['query'])) {
            if ($result['query']['numFound'] > 0) {
                $guid = $result['query']['docs'][0]['guid'];
                $guidarr = explode("_", $guid);
                if (count($guidarr) > 1) {
                    $r = $guidarr[1];
                } else {
                    $r = array("errorcode" => 0, "errormsg" => "特征分类ID格式有误");
                }
            } else {
                $r = 1;
            }
        } else {
            $r = array("errorcode" => 0, "errormsg" => "数据格式有误");
        }
    }
    $logger->debug(__FILE__ . __LINE__ . " -----getFeatureMaxID------- " . var_export($r, true));
    return $r;
}

function addFeatureClass($cls, $postagain = true)
{
    global $logger;
    $maxid = getFeatureMaxID();
    $logger->debug(__FILE__ . __LINE__ . " -----getFeatureMaxID------- " . var_export($maxid, true));
    $logger->debug(__FILE__ . __LINE__ . " ----cls------- " . var_export($cls, true));
    if (empty($maxid)) {
        $logger->error(__FUNCTION__ . " 获取ID失败");
        return false;
    } else if (is_array($maxid)) {
        $logger->error(__FILE__ . __LINE__ . " 获取ID失败：errorcode:{$maxid['errorcode']}, error:{$maxid['errormsg']}");
        return false;
    }
    $classdata = array();
    $farr = array();
    $maxid++;
    $farr["guid"] = setFeatureMaxID($maxid);
    $farr["feature_father_guid"] = $cls['feature_father_guid'] == NULL ? 0 : $cls['feature_father_guid'];
    //2016-7-3 Bert url中查询feature_class:夹克, 实际在solr查询是会转成feature_class:"#夹克#"处理, 
    //为兼容旧数据,需要存储时添加上"##";
    $farr["feature_class"] = "#" . $cls['feature_class'] . "#";
    if (isset($cls['feature_field'])) {
        $farr["feature_field"] = $cls['feature_field'];
    }
    $classdata[] = $farr;
    $logger->debug(__FILE__ . __LINE__ . " classdata " . var_export($classdata, true));
    $r = insert_solrdata($classdata);
    $logger->debug(__FILE__ . __LINE__ . " classdatarrrrr " . var_export($r, true));
    if ($postagain && is_array($r)) {
        //部分失败，说明guid重复,再提交一次
        $post2 = array();
        foreach ($classdata as $key => $value) {
            if (in_array($value['guid'], $r)) {
                $post2[] = $value;
            }
        }
        if (!empty($post2)) {
            $r = addFeatureClass($post2, false);
        }
    }
    return $maxid;
}

function setFeatureMaxID($maxid)
{
    return "fd_" . str_pad($maxid, 32, '0', STR_PAD_LEFT);
}

/**
 *
 * 新增特征分类
 * @param $datas 对象数组
 * @param $postagain 失败后是否继续提交
 */
function addFeature($datas, $postagain = true)
{
    global $logger;
    $maxid = getFeatureMaxID();
    if (empty($maxid)) {
        $logger->error(__FILE__ . __LINE__ . " 获取ID失败");
        return false;
    } else if (is_array($maxid)) {
        $logger->error(__FILE__ . __LINE__ . " 获取ID失败：errorcode:{$maxid['errorcode']}, error:{$maxid['errormsg']}");
        return false;
    } else {
        for ($i = 0; $i < count($datas); $i++) {
            $maxid++;
            $datas[$i]['guid'] = setFeatureMaxID($maxid);
            for ($j = 0; $j < count($datas); $j++) {
                if (isset($datas[$i]['old_guid']) && $datas[$j]['feature_father_guid'] == $datas[$i]['old_guid']) {
                    $datas[$j]['feature_father_guid'] = setFeatureMaxID($maxid);
                }
            }
            unset($datas[$i]['old_guid']);
        }
    }
    $logger->debug(__FILE__ . __LINE__ . " datas " . var_export($datas, true));
    $r = insert_solrdata($datas);
    if ($postagain && is_array($r)) {
        //部分失败，说明guid重复,再提交一次
        $post2 = array();
        foreach ($datas as $key => $value) {
            if (in_array($value['guid'], $r)) {
                $post2[] = $value;
            }
        }
        if (!empty($post2)) {
            $r = addFeature($post2, false);
        }
    }
    return $r === true;
}

/**
 *
 * 编辑特征分类词
 * @param $datas 对象数组
 */
function updateFeature($datas)
{
    $r = insert_solrdata($datas, true);
    return $r === true;
}

/*
 * 通过guid删除solr中的数据
 * $isgetfeatureclass = false 旧的数据不进行getFeatureClass查询 by zuo
 * @brief 获取子级并创建子级的option
 * @param Array $guids
 * @return true/false
 * @author 
 * @date 
 * @change 2016-6-18 Bert 删除子级时,检查子级是否全部被删除, 没有子级时同时删除父级
 * */
function deleteFeature($guids, $isgetfeatureclass = true)
{
    global $logger;
    $father_guidArr = array();
    $logger->error(__FILE__ . __LINE__ . " param is empty" . var_export($guids, true));
    foreach ($guids as $key => $value) {
        $guids[$key] = "guid:{$value}";
        $irows = pow(2, 31) - 1;
        //$r = getFeatureKeyword(0, $irows, NULL, NULL, NUll, NULL, NULL, $value);
        if ($isgetfeatureclass) {
            $r = getFeatureClass(0, $irows, NULL, NULL, NUll, NULL, $value);
            if (count($r['datalist']) > 0) {
                if (!isset($r['datalist'][0]["feature_pclass"])) {
                    $father_guidArr[] = $r['datalist'][0]['feature_father_guid'];
                }
            }
        }
    }
    $logger->error(__FILE__ . __LINE__ . " param is empty" . var_export($father_guidArr, true));
    $params = implode(" OR ", $guids);
    if (empty($params)) {
        $logger->error(__FILE__ . __LINE__ . " param is empty");
        return false;
    } else {
        $ret = delete_solrdata($params);
        $logger->error(__FILE__ . __LINE__ . " param is empty" . var_export($ret, true));
        if ($ret) { //删除父级
            $fa_guids = array();
            $logger->error(__FILE__ . __LINE__ . " param is empty" . var_export($father_guidArr, true));
            foreach ($father_guidArr as $key => $guid) {
                $irows = pow(2, 31) - 1;
                $r = getFeatureClass(0, $irows, NULL, NULL, NULL, $guid, NULL); //查询父级是guid的子类是否还存在
                $logger->error(__FILE__ . __LINE__ . " param is empty" . var_export($r, true));
                if (count($r['datalist']) == 0) { //查询feature_father_guid 为 删除的$guid的数据不存在时,说明子级全部被删除了,此时删除父级
                    $logger->error(__FILE__ . __LINE__ . " param is empty" . var_export($guid, true));
                    $fa_guids[] = $guid;
                }
            }
            if (count($fa_guids) > 0) {
                deleteFeature($fa_guids);
            }
        }
        return $ret;
    }
}

/**
 *
 * 新增数据到solr
 * @param $datas 需要新增的数据(json数组)
 * @param $isupdate 是否是更新
 */
function insert_solrdata($datas, $isupdate = false,$isCommit=TRUE)
{
    global $logger;
    $url = $isupdate ? SOLR_URL_UPDATE : SOLR_URL_INSERT;
    $pcheckguid = $isupdate ? "" : "&checkguid=true";
    if($isCommit){
        $url .= "&commit=true{$pcheckguid}";
    }else {
        $url .= "&commit=false{$pcheckguid}";
    }

    $logger->debug(__FILE__ . __LINE__ . " url " . var_export($url, true) . " calls solr update/insert. commit: true");
    $r = handle_solr_data($datas, $url);
    if ($r === false) {
        $logger->error(__FILE__ . __LINE__ . " error handle_solr_data return false");
        return false;
    } else if ($r === NULL) {
        return true;
    } else {
        $logger->error(__FILE__ . __LINE__ . " error handle_solr_data return " . var_export($r, true));
        return $r;
    }
}

/**
 *
 * 删除solr中的数据
 * @param $paramStr  指定查询参数字符串（例如：name:张三）
 */
function delete_solrdata($paramStr,$isCommit=TRUE,&$timeStatisticObj=null)
{
    global $logger;
    $solrurl = SOLR_URL_DELETE;
    $postdata = array("query" => $paramStr);
    if($isCommit){
        $url = $solrurl . "&commit=true";
    }else{
        $url = $solrurl . "&commit=false";
    }
    $logger->info(__FILE__ . __LINE__ . " calls solr delete. commit: {$isCommit}");

    $solrstart_time = microtime_float();
    $r = handle_solr_data($postdata, $url);
    $solrend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_DELET_TIME_KEY,$solrend_time - $solrstart_time);

    if ($r === false) {
        $logger->error(__FILE__ . __LINE__ . " error handle_solr_data return false");
        return false;
    } else if ($r === NULL) {
        return true;
    } else {
        $logger->error(__FILE__ . __LINE__ . " error handle_solr_data return " . var_export($r, true));
        return false;
    }
}

//将数据集转换为 solr接口参数
function setSendData($ds)
{
    if (empty($ds)) {
        return false;
    }
    /*$tags = array('guid', 'id', 'mid', 'created_at', 'created_year','created_month','created_day', 'created_hour', 'created_weekday','text','originalText',
		'source','thumbnail_pic', 'has_picture','bmiddle_pic','original_pic','userid','retweeted_status'.'timeline_type','geo_type','geo_coordinates_x',
		'geo_coordinates_y','annotations','retweeted_mid','screen_name','country_code','province_code','city_code','district_code','reposts_count',
		'comments_count','total_reposts_count','praises_count','sourceid','register_time','followers_count','friends_count','analysis_status',
		'verified_reason','verified_type','description','repost_trend_cursor','total_reach_count','direct_reposts_count','content_type','verify','sex',
		'retweeted_guid');*/
    $inner_result = array();
    foreach ($solr_article_tags as $tag) {
        switch ($tag) {
            case 'verify':
                if (isset($ds['verified']))
                    $inner_result['verify'] = $ds['verified'];
                break;
            case 'content_type':
                if (isset($ds['is_repost']))
                    $inner_result['content_type'] = $ds['is_repost'];
                break;
            case 'sex':
                if (isset($ds['gender'])) {
                    $inner_result['sex'] = $ds['gender'];
                }
                break;
            case 'originalText':
                if (isset($ds['text'])) {
                    if (is_array($ds['text'])) {
                        $inner_result['originalText'] = array(md5($ds['text']['content']));//已分词的text字段
                    } else {
                        $inner_result['originalText'] = array(md5($ds['text']));//多值的字段
                    }
                }
                break;
            case 'has_picture':
                $inner_result['has_picture'] = (isset($ds['thumbnail_pic']) || isset($ds['bmiddle_pic'])) ? 0 : 1;
                break;
            case 'country_code':
            case 'province_code':
            case 'city_code':
            case 'district_code':
                if (isset($ds[$tag]))
                    $inner_result[$tag] = array($ds[$tag]);
                break;
            case 'analysis_status':
                //默认值
                $inner_result[$tag] = ANALYSIS_STATUS_NORMAL;;
                break;
            case 'direct_reposts_count':
                $inner_result[$tag] = 0;
                break;
            /*case 'retweeted_guid':
				if(!empty($ds['retweeted_status'])){
					$inner_result['retweeted_guid'] = $ds['sourceid']."_".$ds['retweeted_status'];
				}
				else if(!empty($ds['retweeted_mid'])){
					$inner_result['retweeted_guid'] = $ds['sourceid']."m_".$ds['retweeted_mid'];
				}
				break;*/
            default:
                break;
        }
        if (isset($ds[$tag]))
            $inner_result[$tag] = $ds[$tag];
    }
    //$tmp_sql_arr[RET_TO_SOLR_FLAG] = RET_TO_SOLR_FIRST;
    //$tmp_sql_arr[RET_TO_SOLR_ANCESTOR_TEXT] = '';

    return $inner_result;
}

/**
 *
 * 解析solr返回的微博
 * @param $data
 */
function parseSolrData($data)
{
    global $logger;
    $stringArrayFields = array("combinWord", "wb_topic_combinWord");
    $objectArrayFields = array("emoCombin", "emoAccount", "emoProvince", "emoCity", "emoCountry", "emoDistrict", "emoBusiness",
        "emoNRN", "emoTopic", "emoTopicKeyword", "emoTopicCombinWord", "emotion");
    foreach ($stringArrayFields as $key => $value) {
        if (isset($data[$value]) && is_array($data[$value])) {
            $strarr = array();
            foreach ($data[$value] as $k => $v) {
                $tmp = str_replace("#", "", str_replace("##", ",", $v));
                $strarr[] = explode(",", $tmp);
            }
            $data[$value] = $strarr;
        }
    }
    foreach ($objectArrayFields as $key => $value) {
        if (isset($data[$value]) && is_array($data[$value])) {
            $strarr = array();
            foreach ($data[$value] as $k => $v) {
                $tmp = str_replace("#", "", str_replace("##", ",", $v));
                $tmparr = explode(",", $tmp);
                $tmpobj = array();
                $tmpobj['level'] = array_pop($tmparr);//最后一个元素为情感值
                $tmpobj['text'] = $tmparr;//其他的元素为词
                $strarr[] = $tmpobj;
            }
            $data[$value] = $strarr;
        }
    }
    return $data;
}

/*拆出原创,补充上原创id,父级id, 返回原创的数组*/
function splitNestedData(&$ditem)
{
    global $logger;
    //$logger->debug(__FILE__.__LINE__." enter ".__FUNCTION__);
    //$logger->debug(__FILE__.__LINE__." ditem ".var_export($ditem, true));

    //更改数据的结构
    //先处理评论中的回复
    if (isset($ditem['reply_comment']) && gettype($ditem['reply_comment']) == 'array') {
        if (isset($ditem['reply_comment']['mid'])) {
            $ditem['reply_father_mid'] = $ditem['reply_comment']['mid'];
        } else if (isset($ditem['reply_comment']['id'])) {
            $ditem['reply_father_mid'] = $ditem['reply_comment']['id'];
        }
        if (!isset($ditem['content_type'])) {
            $ditem['content_type'] = 2;
        }
        $arr = $ditem['reply_comment'];
        $arr['userid'] = $ditem['reply_comment']['user']['id'];
        $arr['reply_father_mid'] = $ditem['status']['mid'];
        $arr['retweeted_mid'] = $ditem['status']['id'];
        if (!isset($arr['content_type'])) {
            $arr['content_type'] = 2;
        }
        $result['0'] = $arr;
        unset($ditem['reply_comment']);
    }

    //再处理原创
    $tmp = array();
    if (isset($ditem['retweeted_status']) && gettype($ditem['retweeted_status']) == 'array') {
        $tmp = $ditem['retweeted_status'];
        unset($ditem['retweeted_status']);
        if (!isset($ditem['content_type'])) {
            $ditem['content_type'] = 1;
        }
    }
    //转发中的原创
    if (isset($ditem['status']['retweeted_status']) && gettype($ditem['status']['retweeted_status']) == 'array') {
        $a_tmp = $ditem['status']['retweeted_status'];
        $a_tmp['userid'] = $ditem['status']['retweeted_status']['user']['id'];
        $a_tmp['reply_father_mid'] = $ditem['status']['retweeted_status']['mid'];
        $a_tmp['retweeted_mid'] = $ditem['status']['retweeted_status']['id'];
        if (!isset($ditem['status']['retweeted_status']['content_type'])) {
            $ditem['status']['retweeted_status']['content_type'] = 1;
        }
        $result['2'] = $a_tmp;
        unset($ditem['status']['retweeted_status']);
    } //评论中的原创
    if (isset($ditem['status']) && gettype($ditem['status']) == 'array') {
        $tmp = $ditem['status'];
        unset($ditem['status']);
        //根据status字段此条微博为评论
        if (!isset($ditem['content_type'])) {
            $ditem['content_type'] = 2;
        }
    }
    $ori = array();
    if (!empty($tmp)) {
        if (!empty($tmp['deleted']) || (!isset($tmp['mid']) && !isset($tmp['id']))) {
            $ditem['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST;//原创不存在
        } else {
            $ori = $tmp; //取出原创
            if (isset($tmp['mid'])) {
                $ditem['retweeted_mid'] = $tmp['mid'];        //记原创mid
                //补充父
                if (!isset($ditem['reply_father_mid'])) {
                    $ditem['reply_father_mid'] = $ditem['retweeted_mid'];
                }
            } else if (isset($tmp['id'])) {
                $ditem['retweeted_status'] = $tmp['id']; //记原创id
                if (!isset($ditem['reply_father_mid'])) {
                    $ditem['reply_father_mid'] = $ditem['retweeted_status'];
                }
            }
        }
    }

    //$logger->debug(__FILE__.__LINE__." exit ".__FUNCTION__);
    //$logger->debug(__FILE__.__LINE__." ditem ".var_export($ditem, true)." ori ".var_export($ori, true));
    $result['1'] = $ori;
    return $result;
}

//对一条数据补充content_type{0:原创,1:转发,2:评论}
function supplyContentType(&$item)
{
    global $logger;
    //$logger->debug(__FILE__.__LINE__." enter ".__FUNCTION__);
    //$logger->debug(__FILE__.__LINE__." item ".var_export($item, true));
    if (!isset($item['content_type'])) {
        if (isset($item['retweeted_status']) || isset($item['retweeted_mid']) || isset($item['reply_father_floor']) || isset($item['reply_father_mid'])) {
            $item['content_type'] = 2;
        } else if (isset($item['floor'])) {
            if ($item['floor'] == 0) {
                $item['content_type'] = 0;
            } else if ($item['floor'] == -1) {
                //趋势分析数据 floor为-1
                $item['content_type'] = 5;
            } else {
                //默认为2 即评论
                $item['content_type'] = 2;
            }
        } else {
            $item['content_type'] = 0;
        }
    }
    $logger->debug(__FILE__ . __LINE__ . " 补充content_type ok: " . var_export($item['content_type'], true) . " [0:原创,1:转发,2:评论,5:趋势分析/工作经历等附件信息]");
}

function changeUserTokenfieldsType(&$data)
{
    foreach ($data as $di => $ditem) {
        if (isset($ditem['description'])) {
            $data[$di]['description'] = transTokenFieldToObj($ditem['description']);
        }
        if (isset($ditem['verified_reason'])) {
            $data[$di]['verified_reason'] = transTokenFieldToObj($ditem['verified_reason']);
        }
    }
}

//TODO 将这些字段进行配置
function changeTokenfieldsType(&$data)
{
    foreach ($data as $di => $ditem) {
        if (isset($ditem['text'])) {
            // data--+
            //       fieldName ---> value
            //                        +------text
            //                                 +-----content
            $data[$di]['text'] = transTokenFieldToObj($ditem['text']);
        }
        if (isset($ditem['pg_text'])) {
            foreach ($ditem['pg_text'] as $pi => $pitem) {
                $data[$di]['pg_text'][$pi] = transTokenFieldToObj($pitem);
            }
        }
        if (isset($ditem['post_title'])) {
            $data[$di]['post_title'] = transTokenFieldToObj($ditem['post_title']);
        }
        if (isset($ditem['description'])) {
            $data[$di]['description'] = transTokenFieldToObj($ditem['description']);
        }
        if (isset($ditem['verified_reason'])) {
            $data[$di]['verified_reason'] = transTokenFieldToObj($ditem['verified_reason']);
        }
        if (isset($ditem['user'])) {
            if (isset($ditem['user']['description'])) {
                $data[$di]['user']['description'] = transTokenFieldToObj($ditem['user']['description']);
            }
            if (isset($ditem['user']['verified_reason'])) {
                $data[$di]['user']['verified_reason'] = transTokenFieldToObj($ditem['user']['verified_reason']);
            }
        }
        if (isset($ditem['retweeted_status'])) {
            if (isset($ditem['retweeted_status']['text'])) {
                $data[$di]['retweeted_status']['text'] = transTokenFieldToObj($ditem['retweeted_status']['text']);
            }
            if (isset($ditem['retweeted_status']['user'])) {
                if (isset($ditem['retweeted_status']['user']['description'])) {
                    $data[$di]['retweeted_status']['user']['description'] = transTokenFieldToObj($ditem['retweeted_status']['user']['description']);
                }
                if (isset($ditem['retweeted_status']['user']['verified_reason'])) {
                    $data[$di]['retweeted_status']['user']['verified_reason'] = transTokenFieldToObj($ditem['retweeted_status']['user']['verified_reason']);
                }
            }
        }
        if (isset($ditem['status'])) { //通过微博api抓取评论
            if (isset($ditem['status']['text'])) {
                $data[$di]['status']['text'] = transTokenFieldToObj($ditem['status']['text']);
            }
            if (isset($ditem['status']['user'])) {
                if (isset($ditem['status']['user']['description'])) {
                    $data[$di]['status']['user']['description'] = transTokenFieldToObj($ditem['status']['user']['description']);
                }
                if (isset($ditem['status']['user']['verified_reason'])) {
                    $data[$di]['status']['user']['verified_reason'] = transTokenFieldToObj($ditem['status']['user']['verified_reason']);
                }
            }
        }
        if (isset($ditem['status']['retweeted_status'])) { //原创微博
            if (isset($ditem['status']['retweeted_status']['text'])) {
                $data[$di]['status']['retweeted_status']['text'] = transTokenFieldToObj($ditem['status']['text']);
            }
            if (isset($ditem['status']['retweeted_status']['user'])) {
                if (isset($ditem['status']['retweeted_status']['user']['description'])) {
                    $data[$di]['status']['retweeted_status']['user']['description'] = transTokenFieldToObj($ditem['status']['user']['description']);
                }
                if (isset($ditem['status']['retweeted_status']['user']['verified_reason'])) {
                    $data[$di]['status']['retweeted_status']['user']['verified_reason'] = transTokenFieldToObj($ditem['status']['user']['verified_reason']);
                }
            }
        }
        //reply_comment
        if (isset($ditem['reply_comment'])) { //通过微博api抓取评论
            if (isset($ditem['reply_comment']['text'])) {
                $data[$di]['reply_comment']['text'] = transTokenFieldToObj($ditem['reply_comment']['text']);
            }
            if (isset($ditem['reply_comment']['user'])) {
                if (isset($ditem['reply_comment']['user']['description'])) {
                    $data[$di]['reply_comment']['user']['description'] = transTokenFieldToObj($ditem['reply_comment']['user']['description']);
                }
                if (isset($ditem['reply_comment']['user']['verified_reason'])) {
                    $data[$di]['reply_comment']['user']['verified_reason'] = transTokenFieldToObj($ditem['reply_comment']['user']['verified_reason']);
                }
            }
        }
    }
}

function transTokenFieldToObj($fieldval)
{
    global $logger;
    $ret = $fieldval;
    $tmp = array();
    if (is_array($fieldval)) {
        if (isset($fieldval['content'])) {
            return $fieldval;
        }
        $tmp['content'] = $fieldval[0];
    } else
        $tmp['content'] = $fieldval;
    $tmp['terms'] = array();
    $ret = $tmp;
    return $ret;
}

function getTokenFieldVal($fieldval)
{
    $t = $fieldval;
    if (is_array($fieldval)) {
        if (isset($fieldval['content'])) {
            $t = $fieldval['content']; //新增数据时
        } else if (isset($fieldval[0])) {
            $t = $fieldval[0]; //更新数据时从solr查出
        } else {
            $t = '';
        }
    }
    return $t;
}

/**
 *
 * 格式化提交到分词工具的数据
 */
function formatAnalysisData($weibo, $dictionary_plan)
{
    global $logger;

    // add by wangcc 在进行分词时候 需要将当前 服务器的 主机地址(ip) 以及 端口号 作为参数传递给solrNLP
    //$logger->debug(__FILE__.__LINE__." union Json ".var_export($weibo, true));
    $currentPort = getCurrrentSrvPort();
    $currentHost = getCurrrentSrvHost();

    if (empty($weibo)) {
        return false;
    }
    //将方案中的父类转化为子类
    //$plan=$dictionary_plan;
    $plan = formatDictionaryPlan($dictionary_plan);
    ///$logger->error("向NLP发送分析请求：".var_export($weibo.$dictionary_plan,true));
    //guid,sourceid,analysis_status,retweeted_guid,content_type,
    //text(objcet:{string:content,objectarray:emotion,stringarray:business,objectarray:emoBusiness})
    //verified_reason:string  认证原因需要分词
    //description:string 用户描述，需要分词
    $result = array();
    if (!empty($weibo['text']['content']) || !empty($weibo['pg_text']['content'])) {
        $tokenize = array();
        $tokenize['text_type'] = 1;//是正文
        $tokenize["cur_port"] = $currentPort;
        $tokenize["cur_host"] = $currentHost;

        //如果未设置sourceid,说明sourceid为非已知来源, 不送sourceid时solrNLP当作一般文章处理
        if (isset($weibo['sourceid'])) {
            //if(isset($weibo['sourceid']) && is_int($weibo['sourceid'])){
            $tokenize['sourceid'] = $weibo['sourceid'];
        }
        $tokenize['content_type'] = $weibo['content_type'];
        $tokenize['dicttype'] = TOKENIZE_DICTTYPE_ALL;
        $tokenize['dictionary_plan'] = $plan;
        if (!empty($weibo['text'])) {
            //$tokenize['content'] = is_array($weibo['text']) ? (isset($weibo['text'][0]) ? $weibo['text'][0] : '') : $weibo['text'];
            $tokenize['content'] = getTokenFieldVal($weibo['text']);
        }
        if (!empty($weibo['pg_text'])) {
            //$tokenize['content'] = is_array($weibo['pg_text']) ? (isset($weibo['pg_text'][0]) ? $weibo['pg_text'][0] : '') : $weibo['pg_text'];
            $tokenize['content'] = getTokenFieldVal($weibo['pg_text']);
        }
        if ($weibo['content_type'] && isset($weibo['analysis_status']) && $weibo['analysis_status'] == ANALYSIS_STATUS_NORMAL) {
            $tokenize['dependorig'] = 1;
            $originfo = array();
            $originfo['emotion'] = isset($weibo['orig_emotion']) ? $weibo['orig_emotion'] : array();
            $originfo['business'] = isset($weibo['orig_business']) ? $weibo['orig_business'] : array();
            $originfo['emoBusiness'] = isset($weibo['orig_emoBusiness']) ? $weibo['orig_emoBusiness'] : array();
            $tokenize['originfo'] = $originfo;
        } else {
            $tokenize['dependorig'] = 0;
            $tokenize['originfo'] = '';
        }
        $repostStart = false;
        if ($weibo['content_type'] == 1 && !empty($tokenize['content'])) {
            $repostStart = strpos($tokenize['content'], '//@');
        }
        if ($repostStart !== false) {
            $origcontent = $tokenize['content'];
            $tokenize['content'] = substr($origcontent, 0, $repostStart);
            $result[] = $tokenize;
            $tokenize['content'] = substr($origcontent, $repostStart);
        }
        $result[] = $tokenize;
    }
    if (!empty($weibo['post_title']['content'])) {
        $tokenize = array();
        $tokenize['text_type'] = 0;//不是正文
        //如果未设置sourceid,说明sourceid为非已知来源, 不送sourceid时solrNLP当作一般文章处理
        if (isset($weibo['sourceid'])) {
            $tokenize['sourceid'] = $weibo['sourceid'];
        }
        $tokenize['dicttype'] = TOKENIZE_DICTTYPE_NOEB;
        $tokenize['dictionary_plan'] = $plan;
        $tokenize['dependorig'] = 0;
        $tokenize['originfo'] = '';
        $tokenize['content'] = getTokenFieldVal($weibo['post_title']);
        $tokenize["cur_port"] = $currentPort;
        $tokenize["cur_host"] = $currentHost;

        $result[] = $tokenize;
    }
    if (!empty($weibo['verified_reason']['content'])) {
        $tokenize = array();
        $tokenize['text_type'] = 0;//不是正文
        //如果未设置sourceid,说明sourceid为非已知来源, 不送sourceid时solrNLP当作一般文章处理
        if (isset($weibo['sourceid'])) {
            $tokenize['sourceid'] = $weibo['sourceid'];
        }
        $tokenize['dicttype'] = TOKENIZE_DICTTYPE_NOEB;
        $tokenize['dictionary_plan'] = $plan;
        $tokenize['dependorig'] = 0;
        $tokenize['originfo'] = '';
        $tokenize['content'] = getTokenFieldVal($weibo['verified_reason']);
        $tokenize["cur_port"] = $currentPort;
        $tokenize["cur_host"] = $currentHost;

        $result[] = $tokenize;
    }
    if (!empty($weibo['description']['content'])) {
        $tokenize = array();
        $tokenize['text_type'] = 0;//不是正文
        //如果未设置sourceid,说明sourceid为非已知来源, 不送sourceid时solrNLP当作一般文章处理
        if (isset($weibo['sourceid'])) {
            $tokenize['sourceid'] = $weibo['sourceid'];
        }
        $tokenize['dicttype'] = TOKENIZE_DICTTYPE_NOEB;
        $tokenize['dictionary_plan'] = $plan;
        $tokenize['dependorig'] = 0;
        $tokenize['originfo'] = '';
        $tokenize['content'] = getTokenFieldVal($weibo['description']);
        $tokenize["cur_port"] = $currentPort;
        $tokenize["cur_host"] = $currentHost;

        $result[] = $tokenize;
    }
    return $result;
}

/**
 *
 * 从solr查询一条微博
 * @param stringarray $guids
 * @param array $selectFields
 * @param bool $readCache //是否从缓存中读
 * return 以guid为key的对象数组
 */
function solr_select($guids, $selectFields, $readCache = false, $solrurl = SOLR_URL_SELECT)
{
    global $logger, $orig_infos_cache;//原创缓存
    $logger->debug("enter " . __FILE__ . __LINE__);
    if (is_array($selectFields) && !in_array("guid", $selectFields)) {
        $selectFields[] = "guid";
    }
    $fl = empty($selectFields) ? "*" : implode(",", $selectFields);
    $result = array();
    $queryguids = array();
    if (empty($guids)) {
        $logger->error(__FUNCTION__ . " guids is empty");
        $result = false;
    } else {
        if ($readCache) {
            foreach ($guids as $k => $v) {
                $ns = true;
                if (!empty($orig_infos_cache)) {//先从缓存中取
                    foreach ($orig_infos_cache as $ori => $oriv) {
                        if ($oriv['guid'] == $v) {
                            $tmp = array();
                            foreach ($selectFields as $fi => $fv) {
                                if (isset($oriv[$fv])) {
                                    $tmp[$fv] = $oriv[$fv];
                                }
                            }
                            if (!empty($tmp)) {
                                $result[$v] = $tmp;
                                $ns = false;
                                break;
                            }
                        }
                    }
                }
                if ($ns) {
                    $queryguids[] = $v;
                }
            }
        } else {
            $queryguids = $guids;
        }
        $rows = count($queryguids);
        if ($rows > 0) {
            $offset = 0;
            while ($offset < $rows && $result !== false) {
                $len = $rows - $offset;
                $len = $len < 100 ? $len : 100;//每次最多查询100个guid，防止url超长
                $subguids = array_slice($queryguids, $offset, $len);
                $offset += $len;
                $ids = implode("+OR+", $subguids);
                $url = $solrurl;
                $param = "q=guid:({$ids})&fl={$fl}&facet=off&rows={$len}";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);//返回文件流
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
                $stime = microtime_float();
                $response = curl_exec($ch);
                $etime = microtime_float();
                $logger->debug(__FUNCTION__ . " curl_exec time:" . ($etime - $stime));
                if ($response === false) {
                    $errno = curl_errno($ch);
                    $error = curl_error($ch);
                    curl_close($ch);
                    $logger->error(__FILE__ . __LINE__ . " url: " . $url . " param " . $param);
                    $logger->error(__FUNCTION__ . " curl errorcode:{$errno}, error:{$error}");
                    $result = false;
                } else {
                    curl_close($ch);
                    $data = json_decode($response, true);
                    if (!empty($data)) {
                        if (isset($data['response']) && isset($data['response']['docs'])) {
                            if (count($data['response']['docs']) > 0) {
                                foreach ($data['response']['docs'] as $k => $v) {
                                    $result[$v['guid']] = $v;
                                    if ($readCache) {
                                        $orig_infos_cache[] = $v;
                                        //$logger->debug(__FUNCTION__." 增加原创cache：".var_export($orig_infos_cache,true));
                                    }

                                }
                            }
                        } else {
                            $logger->error(__FUNCTION__ . " curl data property error:" . var_export($response, true) . ". url : {$url}");
                            $result = false;
                        }
                    } else {
                        $logger->error(__FUNCTION__ . " curl data is empty:" . var_export($response, true) . ". url : {$url}");
                        $result = false;
                    }
                }
            }
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

/**
 *
 * 根据指定条件查询solr
 * @param $fields 返回字段数组，空返回所有
 * @param $conditions 查询条件关联数组，键为字段名，值为单值或数组。多条件间与关系，多值间或关系
 *                    或查询条件字符串
 * @param $start 返回记录偏移量
 * @param $limit 返回记录数限制，负数返回全部，0返回值为记录数
 * @param $order 按指定字段排序，字段+asc/desc
 * @param $distinct distinct字段名
 * @param $facet facet对象，空不做facet。输入request属性为facet参数字符串，输出response属性为facet结果对象
 * return 失败返回false，$limit为0返回记录数，否则返回记录对象数组
 */
function solr_select_conds($fields, $conditions = "", $start = 0, $limit = -1, $order = "", $distinct = "", &$facet = "", $url = SOLR_URL_SELECT)
{
    global $logger;
    //$logger->debug("enter ".__FUNCTION__);
    $result = array();
    $fl = empty($fields) ? "*" : implode(",", $fields);
    $condstr = "";
    if (!empty($conditions) && is_array($conditions)) {
        $condarr = array();
        foreach ($conditions as $key => $values) {
            $valstr = "";
            if (is_array($values)) {
                if (empty($values)) {
                    $logger->error(__FUNCTION__ . " values of {$key} in conditions is empty");
                    $result = false;
                    return $result;
                }
                foreach ($values as $index => $value) {

                    //add by wangcc,进行非空验证 remove value like '',but not 0
                    if (empty($value) && $value !== 0) {
                        unset($values[$index]);
                        continue;
                    }
                    $values[$index] = solrEsc($value);
                }
                if (count($values) == 1) {
                    $valstr = $values[0];
                } else {
                    $valstr = "(" . implode("+OR+", $values) . ")";
                }
            } else {
                $valstr = $values;
            }
            $condarr[] = "{$key}:{$valstr}";
        }
        $condstr = implode("+AND+", $condarr);
    } else {
        $condstr = str_replace(" ", "+", trim($conditions));
    }
    if (empty($condstr)) {
        $condstr = "*:*";
    }
    $rows = $limit >= 0 ? $limit : 100;
    $sort = empty($order) ? "" : "&sort=" . str_replace(" ", "+", trim($order));
    $diststr = empty($distinct) ? "" : "&distinctType=query&query.distinct={$distinct}";
    $facetstr = empty($facet) ? "&facet=off" : "&facet=on&" . $facet['request'];
    while (true) {
        $param = "q={$condstr}&fl={$fl}&start={$start}&rows={$rows}" . $sort . $diststr . $facetstr;
        $logger->debug(__FILE__ . __LINE__ . "  ************************ 准备调用solr,请求URL:[" . $param . "]...Domain:[" . $url . "].");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_TIMEOUT, DEFAULT_HTTP_TIMEOUT * 60);
        $stime = microtime_float();
        $response = curl_exec($ch);
        $logger->debug(__FUNCTION__ . " ************************收到solr响应,请求成功! result response" . var_export($response, true));
        $etime = microtime_float();
        $logger->debug(__FUNCTION__." curl_exec time:".($etime-$stime));
        if ($response === false) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);
            $logger->error(__FILE__ . __LINE__ . " url: " . $url . " param " . $param);
            $logger->error(__FUNCTION__ . " curl errorcode:{$errno}, error:{$error}");
            $result = false;
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
            if (!empty($data)) {
                if (isset($data['response']) && isset($data['response']['docs'])) {
                    if ($limit == 0 && isset($data['response']['numFound'])) {
                        $result = $data['response']['numFound'];
                        break;
                    }
                    if ($limit < 0 && isset($data['response']['numFound']) && $data['response']['numFound'] > $rows) {
                        $rows = $data['response']['numFound'];
                        continue;
                    }
                    $result = $data['response']['docs'];
                } else {
                    $logger->error(__FUNCTION__ . " curl data property error:" . var_export($response, true) . ". param : {$param}");
                    $result = false;
                }
                if (!empty($facet)) {
                    if (isset($data['facet_counts'])) {
                        $result = $data['facet_counts'];
                    } else {
                        $logger->error(__FUNCTION__ . " curl data property error:" . var_export($response, true) . ". param : {$param}");
                        $result = false;
                    }
                }
            } else {
                $logger->error(__FUNCTION__ . " curl data is empty:" . var_export($response, true) . ". param : {$param}");
                $result = false;
            }
        }
        break;
    }
    //$logger->debug("exit ".__FUNCTION__);
    return $result;
}


function getReturnInfo4HttpReq($ch, $response)
{
//    $response = curl_exec($this->ch);
    $error = curl_error($ch);
    $result = array('header' => '',
        'body' => '',
        'curl_error' => '',
        'http_code' => '',
        'last_url' => '');
    if ($error != "") {
        $result['curl_error'] = $error;
        return $result;
    }
//    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//    $result['headerSize'] = $header_size;
//    $result['header'] = substr($response, 0, $header_size);
//    $result['body'] = substr($response, $header_size);
    $result['body'] = $response;
    $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result['last_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return $result;
}

function httpRespValid($result)
{
    if ($result['curl_error']) throw new Exception($result['curl_error']);
    if ($result['http_code'] != '200') throw new Exception("HTTP Code = " . $result['http_code']);
    if (!$result['body']) throw new Exception("Body of file is empty");
}


/**
 *
 * 根据指定条件从solr取得原始数据
 * @param $conditions 查询条件关联数组，键为字段名，值为单值或数组。多条件间与关系，多值间或关系
 *                    或查询条件字符串
 * @param $start 返回记录偏移量
 * @param $limit 返回记录数限制，负数返回全部
 * @param $order 按指定字段排序，字段+asc/desc
 * return 失败返回false，成功返回记录对象数组
 */
function solr_retrieve($conditions = "", $start = 0, $limit = -1, $order = "", $url = SOLR_URL_RETRIEVE)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    $result = array();
    $condstr = "";
    if (!empty($conditions) && is_array($conditions)) {
        $condarr = array();
        foreach ($conditions as $key => $values) {
            $valstr = "";
            if (is_array($values)) {
                if (empty($values)) {
                    $logger->error(__FUNCTION__ . " values of {$key} in conditions is empty");
                    $result = false;
                    return $result;
                }
                foreach ($values as $index => $value) {
                    $values[$index] = solrEsc($value);
                }
                if (count($values) == 1) {
                    $valstr = $values[0];
                } else {
                    $valstr = "(" . implode("+OR+", $values) . ")";
                }
            } else {
                $valstr = $values;
            }
            $condarr[] = "{$key}:{$valstr}";
        }
        $condstr = implode("+AND+", $condarr);
    } else {
        $condstr = str_replace(" ", "+", trim($conditions));
    }
    if (empty($condstr)) {
        $condstr = "*:*";
    }
    $rows = $limit >= 0 ? "&rows={$limit}" : "";
    $sort = empty($order) ? "" : "&sort=" . str_replace(" ", "+", trim($order));
    $param = "q={$condstr}&start={$start}" . $rows . $sort;
    $logger->debug(__FUNCTION__ . " param:" . $param);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_TIMEOUT, DEFAULT_HTTP_TIMEOUT * 60);   //只需要设置一个秒的数量:60秒
    $stime = microtime_float();
    $response = curl_exec($ch);
    //$logger->debug(__FUNCTION__." response:".$response);
    $etime = microtime_float();
    $logger->debug(__FUNCTION__ . " curl_exec time:" . ($etime - $stime));
    if ($response === false) {
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        $logger->error(__FILE__ . __LINE__ . " url: " . $url . " param " . $param);
        $logger->error(__FUNCTION__ . " curl errorcode:{$errno}, error:{$error}");
        $result = false;
    } else {
        $result = getReturnInfo4HttpReq($ch, $response);
        curl_close($ch);
        httpRespValid($result);
        $response = $result['body'];

        $data = json_decode($response, true);
        if (!empty($data)) {
            if (isset($data['response']) && isset($data['response']['docs'])) {
                $result = $data['response']['docs'];
            } else {
                $logger->error(__FUNCTION__ . " curl data property error:" . var_export($response, true) . ". param : {$param}");
                $result = false;
            }
        } else {
            $logger->error(__FUNCTION__ . " curl data is empty:" . var_export($response, true) . ". param : {$param}");
            $result = false;
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

/**
 *
 * 重新分析微博
 * @param array $weibo_infos 微博对象数组
 * @param array $tokenize_fields 需要分词的字段，字符串数组
 */
function solr_analysis(&$weibo_infos, $tokenize_fields, $dictionary_plan = NULL, $extra_infos = true)
{
    global $logger;
    global $dictionaryPlan;
    $start_time = microtime_float();
    $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词,分词字段:" . var_export($tokenize_fields, true) . "] ....");

    if (empty($dictionary_plan)) {
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词,分词计划为空，使用默认的计划:[" . var_export($dictionaryPlan, true) . "]!");
        $dictionary_plan = $dictionaryPlan;
    }
    if (empty($weibo_infos) || empty($tokenize_fields)) {
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词,分词异常，参数:[weibo_infos]或者[tokenize_fields]为空!");
        return false;
    }
    $result = true;

    //当前需要分词的文章中，关联的所有原创文章(guid--doc)
    $orig_infos = array();

    //需要分词的字段在当前传入的参数中的当前的文章中不存在,则记录当前缺少分词字段的guid, [$weibo_infos[idx]]
    $notext_infos = array();
    $analysis_datas = array();
    //先找到转发微博的原创id，和需要从solr获取分词字段的id
    foreach ($weibo_infos as $key => $weibo) {
        if ($extra_infos || isset($weibo['text']) || isset($weibo['pg_text'])) {
            if (!empty($weibo['retweeted_guid'])) {//当前weibo设置了 retweeted_guid 原创的guid

                if (isset($weibo['analysis_status'])) {
                    if ($weibo['analysis_status'] == ANALYSIS_STATUS_NORMAL) {
                        //待获取的原创列表中不存在，且原创在solr存在。
                        if (!isset($orig_infos[$weibo['retweeted_guid']])) {
                            $orig_infos[$weibo['retweeted_guid']] = '';
                            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-处理非原创文章--+-添加该文章的原创文章guid:[" . $weibo['retweeted_guid'] . "].");
                        }
                    } else {
                        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-处理非原创文章--+-文章analysis_status状态非正常:[" . $weibo['analysis_status'] . "].");
                    }
                } else {
                    //未设置analysis_status
                    $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词,分词异常,非原创Doc的[analysis_status]属性为空!");
                    $result = false;
                    break;
                }
            }
        }

        //缺少字段时，记录guid, 从solrstore查询出缺少的字段
        if ($extra_infos) {
            $needtokenize = false;
            foreach ($tokenize_fields as $ti => $tf) {
                if (!isset($weibo[$tf])) {
                    $needtokenize = true;
                    break;
                }
            }
            if ($needtokenize) {
                $notext_infos[$weibo['guid']] = '';
                $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-当前文章中需要分词的字段值为空--+-添加该文章guid,将从solr中查询,guid:[" . $weibo['guid'] . "].");
            }
        }
    }

    if ($result === false) {
        return $result;
    }
    $origcount = count($orig_infos);
    if ($origcount > 0) {
        $origids = array();
        $selectfield = array("guid", "emotion", "business", "emoBusiness");
        foreach ($orig_infos as $k => $v) {
            $origids[] = $k;
        }
        //调用的是先从缓存中取
        $tmporigs = solr_select($origids, $selectfield, true);
        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-从solr中查询原创文章--+-Allguids:[" . var_export($origids, true) . "] 查询字段:[" . var_export($selectfield, true) . "].");

        if ($tmporigs === false) {
            $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-从solr中查询原创文章失败! Allguids:[" . var_export($origids, true) . "].");
            return false;
        } else if (!empty($tmporigs)) {
            foreach ($tmporigs as $_id => $_orig) {
                $orig_infos[$_id] = $_orig;
            }
        }
    }

    $notextcount = count($notext_infos);
    if ($notextcount > 0) {
        $ids = array();
        $selectfield = array_merge(array("guid"), $tokenize_fields);
        foreach ($notext_infos as $k => $v) {
            $ids[] = $k;
        }
        $tmpweibos = solr_select($ids, $selectfield, true);
        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-从solr中查询文章[获取缺失的分词字段的值]--+-Allguids:[" . var_export($ids, true) . "] 查询字段:[" . var_export($selectfield, true) . "].");
        if ($tmpweibos === false) {
            $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-调用分词--+-从solr中查询文章[获取缺失的分词字段的值]失败!");
            return false;
        } else if (!empty($tmpweibos)) {
            foreach ($tmpweibos as $_id => $_w) {
                $notext_infos[$_id] = $_w;
            }
            changeTokenfieldsType($notext_infos);
        }
    }
    foreach ($weibo_infos as $key => $weibo) {
        if (isset($weibo['retweeted_guid']) && isset($orig_infos[$weibo['retweeted_guid']])) {
            $orig_info = $orig_infos[$weibo['retweeted_guid']];
            //将原创的分析结果添加到当前文章中
            if (!empty($orig_info)) {
                $weibo_infos[$key]['orig_emotion'] = isset($orig_info["emotion"]) ? $orig_info["emotion"] : array();//取出原创的分析结果
                $weibo_infos[$key]['orig_business'] = isset($orig_info["business"]) ? $orig_info["business"] : array();
                $weibo_infos[$key]['orig_emoBusiness'] = isset($orig_info["emoBusiness"]) ? $orig_info["emoBusiness"] : array();
            } else {
                $weibo_infos[$key]['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST;//未找到原创
            }
        }

        if ($notextcount > 0 && isset($notext_infos[$weibo['guid']])) {
            //微博信息中没有text字段，则从notext_infos中取
            if (!isset($weibo_infos[$key]['post_title']) && isset($notext_infos[$weibo['guid']]['post_title'])) {
                $weibo_infos[$key]['post_title'] = !empty($notext_infos[$weibo['guid']]['post_title']) ? $notext_infos[$weibo['guid']]['post_title'] : array();
            }
            if (!isset($weibo_infos[$key]['text']) && isset($notext_infos[$weibo['guid']]['text'])) {
                $weibo_infos[$key]['text'] = !empty($notext_infos[$weibo['guid']]['text']) ? $notext_infos[$weibo['guid']]['text'] : array();
            }
            if (!isset($weibo_infos[$key]['pg_text']) && ((isset($weibo['docguid']) && isset($notext_infos[$weibo['docguid']]['text'])) || isset($notext_infos[$weibo['guid']]['pg_text']))) {
                //添加docguid, paraid, 更改结构
                if (isset($notext_infos[$weibo['guid']]['pg_text'])) {
                    $weibo_infos[$key]['pg_text'] = !empty($notext_infos[$weibo['guid']]['pg_text']) ? $notext_infos[$weibo['guid']]['pg_text'] : array();
                } else if (!empty($notext_infos[$weibo['docguid']]['text'])) {
                    $paras = explode("<BR/>", $notext_infos[$weibo['docguid']]['text']['content']);
                    if (isset($weibo_infos[$key]['paragraphid']) && $weibo_infos[$key]['paragraphid'] > 0) {
                        if (isset($paras[$weibo_infos[$key]['paragraphid'] - 1])) {
                            $weibo_infos[$key]['pg_text'] = transTokenFieldToObj($paras[$weibo_infos[$key]['paragraphid'] - 1]);
                        } else {
                            $weibo_infos[$key]['pg_text'] = array();
                        }
                    }
                }
            }
            if (!isset($weibo_infos[$key]['description']) && isset($notext_infos[$weibo['guid']]['description'])) {
                $weibo_infos[$key]['description'] = !empty($notext_infos[$weibo['guid']]['description']) ? $notext_infos[$weibo['guid']]['description'] : array();
            }
            if (!isset($weibo_infos[$key]['verified_reason']) && isset($notext_infos[$weibo['guid']]['verified_reason'])) {
                $weibo_infos[$key]['verified_reason'] = !empty($notext_infos[$weibo['guid']]['verified_reason']) ? $notext_infos[$weibo['guid']]['verified_reason'] : array();
            }
        }

        //检查数据是否有tokenfileds
        $no_tokenfileds = true;
        foreach ($tokenize_fields as $tf) {
            if (isset($weibo_infos[$key][$tf])) {
                $no_tokenfileds = false;
                break;
            }
        }
        if ($no_tokenfileds) { //当前文章中没有包含需要分词的字段 continue;
            continue;
        }

        //当前文章中包含需要分词的字段;
        $tmp_analysis = formatAnalysisData($weibo_infos[$key], $dictionary_plan);
        if (!empty($tmp_analysis)) {
            $analysis_datas = array_merge($analysis_datas, $tmp_analysis);
        }
    }
    //$logger->debug("------analysis data:".var_export($analysis_datas,true));
    if ($result && !empty($analysis_datas)) {
        $analysis_count = count($analysis_datas);
        //$logger->info("------analysis data:" . var_export($analysis_datas, true) . " dictionary_plan:" . var_export($dictionary_plan, true));
        $solr_r = send_solr($analysis_datas, SOLR_URL_ANALYSIS);
        //$logger->info("------analysis data result:" . var_export($solr_r, true));

        if (isset($solr_r['error'])) {//分析出错
            $result = false;
            $logger->error(__FUNCTION__ . " send_solr faild:{$solr_r['error']}");
        } else {
            //analysis 返回的不是response，是tokenresult
            if (isset($solr_r['tokenresult'])) {
                $returncount = count($solr_r['tokenresult']);
                if ($returncount != $analysis_count) {
                    $logger->error(__FUNCTION__ . " return value count faild. need {$analysis_count},return {$returncount}");
                    $result = false;
                } else {
                    //$logger->debug("------ solrnlp return:".var_export($solr_r['tokenresult'],true));
                    $result = $solr_r['tokenresult'];
                }
            } else {
                $logger->error(__FUNCTION__ . __FILE__ . __LINE__ . " analysis data property error：" . var_export($solr_r, true) . " analysis data " . var_export($analysis_datas, true));
                $result = false;
            }
        }
        unset($analysis_datas);
    }
    //$logger->debug(var_export($result,true));
    $logger->debug("exit " . __FUNCTION__);

    $end_time = microtime_float();
    $diff = ($end_time - $start_time) * 1000;
    $logger->info(__FUNCTION__ . " 分词花费时间:" . $diff . "毫秒");
    return $result;
}


/**
 *
 * 构建发给solr的用户数据包 该用户所有字段前面加上: users_ 前缀 users_的字段为单独一个表:用户表
 * @param unknown_type $userdata
 * @return 用户数组
 */
function formatSolrUserData($userdata)
{
    $result = array();
    foreach ($userdata as $key => $value) {
        $solruser = array();
        foreach ($value as $fieldname => $fieldvalue) {
            if ($fieldname != 'guid') {
                $fieldname = 'users_' . $fieldname;
            }
            $solruser[$fieldname] = $fieldvalue;
        }
        $result[] = $solruser;
    }
    return $result;
}

/**
 *
 * 插入用户的信息
 * @param $user_data 用户数组
 * @param $get_type  来自哪个API ：user.get_type = $get_type,要设置到每个用户属性上去
 * @param $sourceid  数据源,user.sourceid= $sourceid;要设置到每个用户属性上去
 * @param $seeduser  是否种子用户
 * @param $commit 指定调用solr时的参数commit（是否立即提交）
 * @param $issegmented 是否已分词
 * @param $statistics_info 统计信息
 *
 */
function insert_user($user_data, $get_type = NULL, $sourceid = NULL, $seeduser = 0, $commit = true, $main_userurl = NULL, $update_article = false, $issegmented = false, $statistics_to = 'task', $isDistributedTask = false,&$timeStatisticObj)
{
    global $config, $oAuthThird, $logger, $dsql, $task, $statistics_info, $global_usercache;
    global $dictionaryPlan, $taskID, $solr_article_user_tags;
    $result = array("result" => true, "msg" => "");
    $result['statisticInfo'] = array();

    $dsql->SelectDB(DATABASE_WEIBOINFO);
    $logger->debug(__FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库--+-插入用户 ...");

    $succcount = 0;
    $updatecount = 0;
    //获取方案 如果任务id未空 是植入用户 就直接使用$dictionaryPlan，
//    $logger->debug(__FILE__ . __LINE__ . "issegmented " . var_export($issegmented, true));
    $dictionaryPlan = &getDictionaryPlan($isDistributedTask);
    $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库-+-插入用户--+-使用分词计划:[" . var_export($dictionaryPlan, true) . "].");

    if (!empty($task)) {
        $statistics_info = $task;
    }

    if (isCommonTask($statistics_info)) {
        $sceneParentNode = &$statistics_info->taskparams->root->runTimeParam;
    } else {
        $sceneParentNode = &$statistics_info->taskparams;
    }
    //***********************************处理用户********************************//
    if ($user_data) {
        if (empty($sceneParentNode->scene->user_count)) {
            $sceneParentNode->scene->user_count = 0;
        }
        $sceneParentNode->scene->user_count = count($user_data); //每次要提交的用户数
        $tempSourceHost = "";
        foreach ($user_data as $key => $user) {
            //*******清除没有id属性的用户***********//
            if (isset($user['id']))
                $user_data[$key]['id'] = strval($user['id']);//id转成字符串
            else {
                unset($user_data[$key]);
                continue;
            }

            //设置source_host
            if (!isset($user['source_host']) && isset($user['page_url'])) {
                $user_data[$key]['source_host'] = get_host_from_url($user['page_url']);
            }
            //检查是否有host
            if (empty($user_data[$key]['source_host'])) {
                $logger->error(__FUNCTION__ . " insert_user插入数据缺少source_host: " . var_export($user_data[$key], true));
                $result['result'] = false;
                $result['msg'] = '插入用户时缺少source_host';
                return $result;
            }

//            if(!empty($tempSourceHost) && !empty($sourceid) && $user_data[$key]['source_host']== $tempSourceHost){
//                $user_data[$key]['sourceid'] = $sourceid;
//            }else{
//                $user_data[$key]['sourceid'] = get_source_id($user_data[$key]['source_host']);
//                $sourceid = $user_data[$key]['sourceid'];
//                $tempSourceHost = $user_data[$key]['source_host'];
//            }

            $user_data[$key]['sourceid'] = get_source_id($user_data[$key]['source_host']);
            $sourceid = $user_data[$key]['sourceid'];
        }

        $newusers = array();//需要新增的
        $updateusers = array();//需要更新的 数据库中
        $newuserids = array();//需要插入数据库的用户id
        $updatearts = array(); //更新用户时候 同时更新包含在文章中的用户信息

        //根据用户的id 以及 source_host 或者 source_host_id 从 solr 中中进行查询
        $query_results = getUsers($user_data);
        if ($query_results === false) {
            $logger->error(__FUNCTION__ . "获取user失败。");
            $result['result'] = false;
            $result['msg'] = '插入用户时查询用户失败';
            return $result;
        }

        //TODO 用户信息字段
        if (!empty($user_data)) {
            //设置solr中用户信息包含的所有字段，将针对这些字段进行比较，看有没有跟新/变化 (如果新增了一个用户字段，则需要在这里添加)
            $useful_tags = array('guid', 'id', 'screen_name', 'name', 'province', 'city', 'location', 'description', 'url', 'profile_image_url', 'avatar_large',
                'domain', 'gender', 'followers_count', 'friends_count', 'statuses_count', 'favourites_count', 'replys_count', 'level', 'bi_followers_count',
                'created_at', 'allow_all_act_msg', 'allow_all_comment', 'geo_enabled', 'verified', 'taginfo', 'trendinfo', 'is_celebrity_friend',
                'is_celebrity_follower', 'is_bridge_user', 'bridge_count', 'country_code', 'province_code', 'city_code', 'district_code', 'get_type',
                'sourceid', 'seeduser', 'verified_reason', 'verified_type', 'friends_id', 'source_host', 'page_url', 'recommended_count');

            foreach ($user_data as $uikey => $userInfo) {

                //从solr读出来的用户信息，直接跳过
                if (!empty($userInfo['fromdatabase'])) {
                    continue;
                }
                //判断用户是否已存在
                if (isset($query_results[$userInfo['source_host']][$userInfo['id']])) {
                    $exists_user = 1;
                    $solruser = $query_results[$userInfo['source_host']][$userInfo['id']];
                    $global_usercache[$userInfo['source_host']][strval($userInfo['id'])]['guid'] = $solruser['guid'];
                    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库-+-插入用户--+-当前用户是否存在:[是]. User:" . var_export($userInfo, true));
                } else {
                    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-数据入库-+-插入用户--+-当前用户是否存在:[否]. User:" . var_export($userInfo, true));
                    $exists_user = 0;
                }

                //*********************************获取或者生成guid*********************************
                if ($exists_user) {
                    $guid = $solruser['guid'];
                } else {
                    //set guid, including two parts
                    if (isset($sourceid))
                        $part1 = $sourceid;
                    else if (isset($userInfo['source_host']))
                        $part1 = base64_encode($userInfo['source_host']);
                    else {
                        $logger->error("Both sourceid and host are undefined! GUID can't be set.");
                        $result['result'] = false;
                        $result['msg'] = '插入用户时,guid拼接失败，无源信息';
                        return $result;
                    }
                    if (isset($userInfo['id']))
                        $part2 = $userInfo['id'];
                    else if (isset($main_userurl)) //没有用户id，用主url来拼
                        $part2 = base64_encode($main_userurl);
                    else {
                        $logger->error("Both userid and usermainurl are undefined! GUID can't be set.");
                        $result['result'] = false;
                        $result['msg'] = '插入用户时,guid拼接失败，无id或主url';
                        return $result;
                    }
                    $guid = "{$part1}u_{$part2}";
                    $userInfo['guid'] = $guid;
                }
                $user_guid = $guid;
                $logger->debug("user: " . $user_guid . " exist: " . $exists_user);
                //*********************************获取或者生成guid*********************************

                if ($get_type) {
                    $userInfo['get_type'] = $get_type;
                }
                if (isset($sourceid))
                    $userInfo['sourceid'] = $sourceid;

                //这几个是写死的。既然写死的，不写可以吗？
                /**    上面的注释值得考虑      **/
                $userInfo['is_celebrity_friend'] = 0; //是否名人关注
                $userInfo['is_celebrity_follower'] = 0;//是否名人的粉丝
                $userInfo['is_bridge_user'] = 0;
                $userInfo['seeduser'] = (isset($solruser) && !empty($solruser['seeduser'])) ? $solruser['seeduser'] : $seeduser;//是否种子用户

                //获取用户的认证类型 以及 根据用户的'country' 'province' 'city' 'district' 字段获取用户的地区编码
                $userInfo = processUserData($userInfo, $sourceid);
                if (isset($userInfo['result']) && $userInfo['result'] === false) {
                    $result['result'] = false;
                    $result ['msg'] = $userInfo['msg'];
                    return $result;
                }

                $sql_data = array();
                //要求schema与爬虫模版命名除掉users前缀相同
                foreach ($useful_tags as $tag) {
                    if (isset($userInfo[$tag])) {
                        $userInfoValue = $userInfo[$tag];
                        $userInfoType = gettype($userInfoValue);
                        if (!isset($solrarticle[$tag])) {
                            if ($userInfoType == 'string' && trim($userInfoValue) == "")
                                continue;
                            if ($userInfoType == 'array' && empty($userInfoValue))
                                continue;
                            if ($userInfoType == 'array' && isset($userInfoValue['content']) && trim($userInfoValue['content']) == "")
                                continue;
                        }
                        //空白字符串过滤。注意，如果userinfo是空白字符串，solr中有值，则最终会把原有的值删掉
                        //if($userInfoType=='string' && strlen($userInfoValue)==0 && !isset($solruser[$tag]))
                        //continue;
                        //现在schema中无bool型，转换
                        if ($userInfoType == 'boolean') {
                            $userInfoValue = ($userInfoValue == false ? 0 : 1);
                        }

                        //new users
                        if (!$exists_user) {
                            //**********************当前用户信息不存在******************//
                            $sql_data[$tag] = $userInfoValue;
                        } else {
                            //**********************当前用户信息存在******************//
                            //get_type变化不作为更新依据
                            if ($tag == 'get_type')
                                continue;

                            // description和verified_reason字段使用content子属性来比较:用content比较
                            if ($tag == 'description' || $tag == 'verified_reason') {
                                $tmp_userInfoValue = $userInfoValue;
                                $userInfoValue = $userInfoValue['content'];
                            }
                            //该字段solr已有
                            if (isset($solruser[$tag])) {
                                $userInfoType = gettype($userInfoValue);
                                $solrInfoType = gettype($solruser[$tag]);
                                //类型不同，做转换
                                if ($userInfoType != $solrInfoType) {
                                    if ($solrInfoType == 'array')
                                        $userInfoValue = array($userInfoValue);
                                    switch ($userInfoType) {
                                        case 'string':
                                            //过滤空白字符串
                                            if ($solrInfoType == 'integer')
                                                $userInfoValue = intval($userInfoValue);
                                            else if ($solrInfoType == 'double')
                                                $userInfoValue = floatval($userInfoValue);
                                            break;
                                        case 'double':
                                        case 'integer':
                                            if ($solrInfoType == 'string')
                                                $userInfoValue = strval($userInfoValue);
                                            break;
                                        case 'array':
                                            if ($solrInfoType == 'string')
                                                $userInfoValue = implode(' ', $userInfoValue);
                                            break;
                                        default:
                                            break;
                                    }
                                }
                                //检查类型，即使不通过，也往下走
                                if (gettype($userInfoValue) != $solrInfoType) {
                                    $logger->error(__FILE__ . __LINE__ . " different type, can't compare! " . $tag . " " . $userInfoValue . " " . $solruser[$tag]);
                                }

                                //值不同才设置sql_data
                                if ($solruser[$tag] != $userInfoValue) {
                                    if ($tag == 'description' || $tag == 'verified_reason') {
                                        $sql_data[$tag] = $tmp_userInfoValue;
                                    } else
                                        $sql_data[$tag] = $userInfoValue;
                                }
                            } //该字段solr用户没有，加上
                            else {
                                $logger->debug("exist user. unset solr key:" . $tag . " value:" . $userInfoValue);
                                if ($tag == 'description' || $tag == 'verified_reason')
                                    $sql_data[$tag] = $tmp_userInfoValue;
                                else
                                    $sql_data[$tag] = $userInfoValue;
                            }
                        }
                    }
                }

                //old user
                if ($exists_user) {
                    //由于爬虫抓取的verified_type没有认证明细，当为-2机构认证时，判断数据库的verified_type是否较新
                    //数据库中已存的认证字段为认证，且认证类型大于0说明为企业认证，同时有真正的认证类型，则取值
                    if (isset($solruser)
                        && isset($sql_data["verified_type"]) && $sql_data["verified_type"] == -2
                        && isset($solruser['verified'])
                        && isset($solruser['verified_type'])
                        && ($solruser['verified'] == 1 || $solruser['verified'] == 3)
                        && $solruser['verified_type'] > 0
                    ) {
                        $sql_data["verified_type"] = $solruser['verified_type'];
                    }
                    if (!empty($sql_data)) {
                        $sql_data['guid'] = $user_guid;
                        //$sql_data['sourceid'] = isset($userInfo['sourceid'])?$userInfo['sourceid']:NULL;
                        $sql_data['user_updatetime'] = time();
                        $updateusers[] = $sql_data;

                        //查询出该user的文章
                        if ($update_article) {
                            $art_tmp = array();

                            $solrstart_time = microtime_float();
                            $qr = solr_select_conds($solr_article_user_tags, "userguid:" . $user_guid, 0, (pow(2, 31) - 1));
                            $solrend_time = microtime_float();
                            addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

                            if ($qr === false) {
                                $logger->error("从solr取数据出错 ");
                                $result['result'] = false;
                                $result['msg'] = '插入用户时，查询该用户的文章出错!，查询失败，结果为:[false]!';
                                return $result;
                                //return false;
                            } else {
                                //$logger->debug("doc guid results:".var_export($qr,true));
                                if (count($qr) == 0) {
                                    //$logger->debug("该user没有doc");
                                } else {
                                    $art_tmp = addUserToArticle($qr, $sql_data, true);
                                    $logger->debug(__FILE__ . __LINE__ . " art_tmp " . var_export($art_tmp, true));
                                    $updatearts = array_merge($updatearts, $art_tmp);
                                }
                            }
                        }
                    }
                } else { //new user，分词要考虑吗？
                    $sql_data['user_updatetime'] = time();
                    $newusers[] = $sql_data;
                    $newuserids[] = "'{$sql_data['id']}','{$sourceid}'";
                }
            }
            unset($solruser);
        }

        $commitstr = $commit ? "&commit=true" : "&commit=false";

        //TODO 用户信息里面需要分词的字段
        $tokenfields = array("verified_reason", "description");

        if (!empty($newusers)) { //有需要插入的用户，即新增用户
            $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "################################ 本次新增用户:[" . count($newusers) . "] 条 ################################");
            $result['statisticInfo']['addUserNum'] = count($newusers);

            //当前用户没有分词
            if (!$issegmented) {
                if (!empty($dictionaryPlan)) {
                    $dictionaryPlan = formatDictionaryPlan($dictionaryPlan);
                }
                $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-新增用户-+-用户信息分词,分词字典:[" . var_export($dictionaryPlan, true) . "] ...");

                //对认证原因、简介分词
                $solrstart_time = microtime_float();
                $ana_result = solr_analysis($newusers, $tokenfields, $dictionaryPlan, false);
                $solrend_time = microtime_float();
                //统计时间
                addTime4Statistic($timeStatisticObj,SOLR_NLP_TIME_KEY,$solrend_time - $solrstart_time);

                if (!$ana_result) { //分词失败
                    $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-新增用户-+-用户信息分词failed, result:[" . var_export($ana_result, true) . "].");
                    $result['result'] = false;
                    $result['msg'] = '插入新用户时，solr分词失败';
                    return $result;
                } else {
                    //生成存储数据
                    formatStoreData($newusers, $ana_result, $tokenfields);
                }
            }else{
                $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-新增用户-+-用户信息分词--+-文章已分词!");
            }

            foreach ($newuserids as $useridstr) {
                $insert_sql = "insert into " . DATABASE_USER . " (id,sourceid) values ({$useridstr})";

                $sqlstart_time = microtime_float();
                $qr = $dsql->ExecQuery($insert_sql);
                $sqlend_time = microtime_float();
                //统计时间
                addTime4Statistic($timeStatisticObj,DB_INSERT_TIME_KEY,$sqlend_time - $sqlstart_time);

                if (!$qr) {
                    $logger->error(__FUNCTION__ . " insert user sql:{$insert_sql} error:" . $dsql->GetError());
                }
            }
            //该用户所有字段前面加上: users_ 前缀 users_的字段为单独一个表:用户表
            $solrstart_time = microtime_float();
            $newusers = formatSolrUserData($newusers);
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-新增用户-+-调用solr插入新用户, newUsersData:[" . var_export($newusers, true) . "]...");
            $res = handle_solr_data($newusers, SOLR_URL_INSERT . $commitstr);
            $solrend_time = microtime_float();
            //统计时间
            addTime4Statistic($timeStatisticObj,SOLR_INSERT_TIME_KEY,$solrend_time - $solrstart_time);

            if ($res === NULL) {
                //全部成功
                $succcount = count($newusers);
            } else {
                //有插入失败的文章(即用户)
                $errorids = '';
                if (is_array($res)) {
                    foreach ($res as $k => $v) {
                        $guid_arr = split('_', $v);
                        $errorids .= "'" . $guid_arr[1] . "',";
                    }
                } else if ($res === false) {
                    foreach ($newusers as $uinfo) {
                        $errorids .= "'" . $uinfo['users_id'] . "',";
                    }
                }
                if (!empty($errorids)) {
                    $errorids = substr($errorids, 0, -1);//错误ID从数据库删除
                    //$del_sql = "delete from ".DATABASE_USER." where sourceid={$sourceid} and id in ({$errorids})";
                    $del_sql = "delete from " . DATABASE_USER . " where id in ({$errorids})";
                    //这句写错了吧.whl.
                    //$qr = $dsql->ExecQuery($insert_sql);

                    $sqlstart_time = microtime_float();
                    $qr = $dsql->ExecQuery($del_sql);
                    $sqlend_time = microtime_float();
                    //统计时间
                    addTime4Statistic($timeStatisticObj,DB_DELET_TIME_KEY,$sqlend_time - $sqlstart_time);

                    if (!$qr) {
                        $logger->error(__FUNCTION__ . " delete user sql:{$del_sql} error:" . $dsql->GetError());
                    }
                }
                $logger->error(__FUNCTION__ . " insert to solr return " . var_export($res, true) . ". data is:" . json_encode($newusers));
                $result['result'] = false;
                $result['msg'] = '插入用户时，solr插入出错';
                return $result;
                //return false;
            }
        }

        if (!empty($updateusers)) {
            $logger->debug("has update users: " . var_export($updateusers, true));
            if (!$issegmented) {
                if (!empty($dictionaryPlan)) {
                    $dictionaryPlan = formatDictionaryPlan($dictionaryPlan);
                }
                $logger->info("旧用户分词");

                $solrstart_time = microtime_float();
                $ana_result = solr_analysis($updateusers, $tokenfields, $dictionaryPlan, false);//对认证原因、简介分词
                $solrend_time = microtime_float();
                //统计时间
                addTime4Statistic($timeStatisticObj,SOLR_NLP_TIME_KEY,$solrend_time - $solrstart_time);

                if (!$ana_result) { //分词失败
                    $logger->error(__FUNCTION__ . " solr_analysis failed:" . var_export($ana_result, true));
                    $result['result'] = false;
                    $result['msg'] = '插入已有用户时，solr分词失败';
                    return $result;
                    //return false;
                } else {
                    formatStoreData($updateusers, $ana_result, $tokenfields);//生成存储数据
                }
            }else{
                $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-更新用户-+-用户信息分词--+-文章已分词!");
            }
            $updateusers = formatSolrUserData($updateusers);

            //文章分词
            if (!empty($updatearts) && !$issegmented) {
                $logger->info("旧用户文章分词");
                //$logger->debug("updatearts:".var_export($updatearts,true));

                $solrstart_time = microtime_float();
                $ana_result = solr_analysis($updatearts, $tokenfields, $dictionaryPlan, false);
                $solrend_time = microtime_float();
                //统计时间
                addTime4Statistic($timeStatisticObj,SOLR_NLP_TIME_KEY,$solrend_time - $solrstart_time);

                if (!$ana_result) { //分词失败
                    $logger->error(__FUNCTION__ . " solr_analysis failed:" . var_export($ana_result, true));
                    $result['result'] = false;
                    $result['msg'] = '插入已有用户时，用户文章solr分词失败';
                    return $result;
                    //return false;
                } else {
                    formatStoreData($updatearts, $ana_result, $tokenfields);//生成存储数据
                }
            }else{
                if($issegmented){
                    $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-添加文章--+-就用户文章处理-+-文章分词--+-文章已分词!");
                }
            }

            //连同文章，一起更新
            $updates = array_merge($updatearts, $updateusers);
            $logger->info("solr更新旧用户与其文章，其中文章" . count($updatearts) . "条，用户" . count($updateusers) . "条");

            $solrstart_time = microtime_float();
            $res = handle_solr_data($updates, SOLR_URL_UPDATE . $commitstr);
            $solrend_time = microtime_float();
            //统计时间
            addTime4Statistic($timeStatisticObj,SOLR_UPDATE_TIME_KEY,$solrend_time - $solrstart_time);

            //$result = handle_solr_data($updateusers, SOLR_URL_UPDATE.$commitstr);
            if ($res === NULL) {
                $updatecount = count($updateusers);
            } else {
                $logger->error(__FUNCTION__ . " update to solr return " . var_export($result, true) . ". data is:" . json_encode($updateusers));
                $result['result'] = false;
                $result['msg'] = '插入用户时，更新用户solr出错';
                return $result;
                //return false;
            }
        }
    } else {
        $logger->debug('user_data is null');
    }
    if (empty($sceneParentNode->scene->spider_usercount)) {
        $sceneParentNode->scene->spider_usercount = 0;
    }
    $sceneParentNode->scene->spider_usercount += $succcount;//新用户数
    if (empty($sceneParentNode->scene->update_usercount)) {
        $sceneParentNode->scene->update_usercount = 0;
    }
    $sceneParentNode->scene->update_usercount += $updatecount;//更新用户数

    if ($statistics_to == 'task') {
        $task = $statistics_info;
    }
    $logger->debug("insert user newcount: " . $succcount . " updatecount: " . $updatecount);
    $logger->debug('exit insert_user]');
    return array_merge(array("newcount" => $succcount, "updatecount" => $updatecount), $result);//返回新入库的条数和修改的条数
}

/**
 *
 * 更新用户的信息
 * @param $source      数据源
 * @param $id          用户ID
 * @param $name        用户昵称
 * @param $isseed      是否种子用户
 * @param $getfriends  是否抓关注
 * @param $friendsinfo 关注用户信息数组
 * @param $action      更新动作（只插入，只更新，插入或更新）
 * @param $exist       是否确定存在（跳过查询solr）
 */
$solrquerytime = 0;
$solrupdatetime = 0;
function update_userinfo($source, $id, $name, $isseed, $getfriends, &$friendsinfo = NULL, $action = UPDATE_ACTION_INSUPD, $exist = false)
{
    global $logger, $task, $res_machine, $res_ip, $res_acc,
           $solrquerytime, $solrupdatetime;
    $result = array("result" => true, "msg" => "", "found" => 0, "newcount" => 0, "updatecount" => 0);
    if (empty($source) || (empty($id) && empty($name)) ||
        ($action != UPDATE_ACTION_INSONLY && $action != UPDATE_ACTION_UPDONLY && $action != UPDATE_ACTION_INSUPD && $action != UPDATE_ACTION_FORCE) ||
        ($action == UPDATE_ACTION_INSONLY && $exist)
    ) {
        $logger->error(__FUNCTION__ . " 参数错误");
        $result['result'] = false;
        $result['msg'] = "参数错误";
        return $result;
    }
    try {
        //先去新浪查询看用户昵称是否变化
        getAllConcurrentRes($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state != SCENE_NORMAL) {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
            $task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
            myReleaseResource($task, $res_machine, $res_ip, $res_acc);
            return $result;
        }
        $user = NULL;
        $change_screen_name = false;
        $cu_r = crawling_user($id, $name);
        if ($cu_r['result'] && !empty($cu_r['user'])) {
            $user = $cu_r['user'];
            $result["user"] = array("users_id" => $user["id"], "users_screen_name" => $user["screen_name"]);
        } else if (!empty($cu_r['nores'])) {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = $cu_r['msg'];
        } else {
            $result['result'] = false;
            $result['msg'] = $cu_r['msg'];
            if (isset($cu_r['error_code']) && $cu_r['error_code'] == ERROR_USER_NOT_EXIST) {
                $result['notext'] = 1;//用户不存在
            }
        }
        if (!$result['result']) {
            $task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
            myReleaseResource($task, $res_machine, $res_ip, $res_acc);
            return $result;
        } else {
            if ($name != NULL) {
                if ($user['screen_name'] != $name) { //改变昵称需要更新solr
                    $change_screen_name = true;
                    $result['change_screen_name'] = true;
                }
            }
        }

        $found = 0;
        if (!$exist) {
            $qarr = array();//查询条件数组
            $qarr[] = 'users_sourceid:' . solrEsc($source);
            if (!empty($id)) {
                $qarr[] = 'users_id:' . solrEsc($id);
            } else {
                $qarr[] = 'users_screen_name:' . solrEsc($name);
            }
            $qstr = implode("+AND+", $qarr);
            $url = SOLR_URL_SELECT . "?q={$qstr}&facet=off&fl=guid,users_seeduser,users_user_updatetime&start=0&rows=1";
            $start_t = microtime_float();
            $solr_r = getSolrData($url);
            $end_t = microtime_float();
            $solrquerytime += $end_t - $start_t;
            if (isset($solr_r['errorcode'])) {
                $logger->error(__FUNCTION__ . " call getSolrData({$url}) error: {$solr_r['errormsg']}");
                $result['result'] = false;
                $result['msg'] = "查询用户({$id},{$name})时出错";
                return $result;
            }
            $found = $solr_r['query']['numFound'];
            $exist = $found > 0;
            if ($exist && !empty($solr_r['query']['docs'])) {
                $lastupdatetime = $solr_r['query']['docs'][0]['users_user_updatetime'];
                $seeduser = $solr_r['query']['docs'][0]['users_seeduser'];
            }
        }
        if (($exist && $action == UPDATE_ACTION_INSONLY) || ((!$exist) && $action == UPDATE_ACTION_UPDONLY)) {
            return $result;
        }
        //不是强制更新
        if ($action != UPDATE_ACTION_FORCE) {
            //系统中存在用户, 并且 不是种子用户, 或系统中为种子用户
            if ($exist && (!$isseed || $seeduser) && !$change_screen_name) {
                $currenttime = time();
                if ($currenttime - $lastupdatetime < TIMELIMIT_UPDATEUSER) {
                    return $result;
                }
            }
        }
        $friends = NULL;
        $gettype = 'friends';
        if ($getfriends) {
            if (empty($friendsinfo)) {
                $cf_r = crawling_friends($id, $name);
                if ($cf_r['result'] && !empty($cf_r['friends'])) {
                    $friends = $cf_r['friends'];
                } else if (!empty($cf_r['nores'])) {
                    $result['result'] = false;
                    $result['nores'] = true;
                    $result['msg'] = $cf_r['msg'];
                } else {
                    $result['result'] = false;
                    $result['msg'] = $cf_r['msg'];
                }
            } else {
                $gettype = 'remote';
                $friends = array();
                foreach ($friendsinfo as $fid => $fuser) {
                    if (empty($fuser)) {
                        $cu_r = crawling_user($fid);
                        if ($cu_r['result'] && !empty($cu_r['user'])) {
                            $fuser = $cu_r['user'];
                            $friendsinfo[$fid] = $fuser;
                        } else if (isset($cu_r['error_code']) && $cu_r['error_code'] == ERROR_USER_NOT_EXIST) {
                            $fuser = 'notext';
                            $friendsinfo[$fid] = $fuser;
                        } else if (!empty($cu_r['nores'])) {
                            $result['result'] = false;
                            $result['nores'] = true;
                            $result['msg'] = $cu_r['msg'];
                            break;
                        } else {
                            $result['result'] = false;
                            $result['msg'] = $cu_r['msg'];
                            break;
                        }
                    }
                    if ($fuser != 'notext') {
                        $friends[] = $fuser;
                    }
                }
                if (!$result['result']) {
                    unset($friends);
                }
            }
        }
        if (!empty($friends)) {
            $start_t = microtime_float();
            changeUserTokenfieldsType($friends);
            $succount = insert_user($friends, $gettype, $source, 0);
            $end_t = microtime_float();
            $solrupdatetime += $end_t - $start_t;
            if ($succount['result'] === false) {
                $result['result'] = false;
                $result['msg'] = $succount['msg'] . ' 新增关注失败';
            } else {
                $result['result'] = true;
                $result['newcount'] += $succount['newcount'];
                $result['updatecount'] += $succount['updatecount'];
                $friends_id = array();
                foreach ($friends as $fuser) {
                    if (isset($fuser['id'])) {
                        $friends_id[] = "{$fuser['id']}";
                    }
                }
                if (!empty($friends_id)) {
                    $user['friends_id'] = $friends_id;
                }
            }
        }
        if (!$result['result']) {
            $task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
            myReleaseResource($task, $res_machine, $res_ip, $res_acc);
            return $result;
        }
        if ($found) {
            $result['found'] = $found;
        }
        if ($exist) {
            $user['exists'] = true;
        }
        $start_t = microtime_float();
        $tmpuser = array($user);
        changeUserTokenfieldsType($tmpuser);
        $succount = insert_user($tmpuser, 'show_user', $source, $isseed);
        $end_t = microtime_float();
        $solrupdatetime += $end_t - $start_t;
        //if ($succount === false || ($succount['newcount'] + $succount['updatecount']) == 0)
        if ($succount['result'] === false) {
            $result['result'] = false;
            $result['msg'] = $succount['msg'] . ' 新增用户失败';
        } else {
            $result['result'] = true;
            $result['newcount'] += $succount['newcount'];
            $result['updatecount'] += $succount['updatecount'];
        }
        $task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    } catch (Exception $e) {
        $result->result = false;
        $result->msg = $e->getMessage();
        myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    }
    $logger->debug(__FUNCTION__ . " exits]");
    return $result;
}

//参数extra为false，表示返回结果中只包含country_code, province_code, city_code, district_code；为真表示另外多返回汉字字段(未做)
function get_area_code_from_user($userInfo, $sourceid, $extra = false)
{
    global $logger, $dsql;
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    $conds = array();
    //优先使用分字段查询
    if (!empty($userInfo['country']) || !empty($userInfo['province']) || !empty($userInfo['city']) || !empty($userInfo['district'])) {
        $country = isset($userInfo['country']) ? $userInfo['country'] : NULL;
        $province = isset($userInfo['province']) ? $userInfo['province'] : NULL;
        $city = isset($userInfo['city']) ? $userInfo['city'] : NULL;
        $district = isset($userInfo['district']) ? $userInfo['district'] : NULL;
        //保持顺序push
        if (!empty($country)) {
            $conds[] = $country;
        }
        if (!empty($province)) {
            $conds[] = $province;
        }
        if (!empty($city)) {
            $conds[] = $city;
        }
        if (!empty($district)) {
            $conds[] = $district;
        }
    } //使用整字段location拆分后查询
    else if (!empty($userInfo['location'])) {
        $areaarr = explode(' ', $userInfo['location']);
        if ($areaarr) {
            foreach ($areaarr as $value) {
                $conds[] = $value;
            }
        }
    } else {
        return NULL;
    }

    $area_codes = get_area_code($conds, $sourceid);
    /*if($area_codes['result'] === false){
		$logger->debug("user is: ".var_export($userInfo,true));
		return false;
	}*/
    //根据返回结果的code分别查询area表，得出汉字，先不做
    /*if($extra)
	{
		//$ex = array();

	}*/
    //$logger->debug("exit ".__FUNCTION__.var_export($area_codes,true));
    return $area_codes;
}

//通过省$conds查询出country,provice,city,district的code。
//$sourceid默认为-1，用的是dict_area表的默认配置，省市区都是汉字。
//如果省市区通过api抓取为数字，则需传入自己的sourceid。
function get_area_code($conds, $sourceid = -1)
{
    global $logger, $dsql;
    //$logger->debug('enter '.__FUNCTION__.var_export($conds,true));
    $result = array("result" => true, "msg" => "");

    $dsql->SelectDB(DATABASE_WEIBOINFO);

    if (empty($conds[0]) && empty($conds[1]) && empty($conds[2]) && empty($conds[3]))
        return NULL;

    //第一个为数字，即代表都为数字，需做特殊处理
    if (is_numeric($conds[0])) {
        switch ($sourceid) {
            case 1://新浪微博
                $province = $conds[0];
                $city = $conds[1];
                if (empty($province)) {
                    $province = 100;
                }
                //46|90，海南其他
                if (is_numeric($province) && is_numeric($city)) {
                    if (($province == 400 && $city == 16) || $city == 0
                        || ($province == 71 && $city == 90) || ($province == 100) || ($province == 46 && $city == 90)
                        || ($province == 81 && $city == 1) || ($province == 82 && $city == 1)
                    ) {//选择“海外-其他”时， 修改为1000（不限）
                        $conds[1] = 1000;
                    }
                    if ($province == 45) {//广西柳州的代码由2变动为22
                        if ($city == 22) {
                            $conds[1] = 2;
                        }
                        if ($city == 21) {//南宁
                            $conds[1] = 1;
                        }
                    }
                    if ($province == 14 && $city == 23) {//山西吕梁的代码由11变动为23
                        $conds[1] = 11;
                    }
                    if ($province == 15) {
                        if ($city == 26) {//内蒙古乌兰察布
                            $conds[1] = 9;
                        }
                        if ($city == 28) {//内蒙古巴彦淖尔市
                            $conds[1] = 8;
                        }
                    }
                    if ($province == 50) {//重庆
                        if ($city == 81) {
                            $conds[1] = 16;
                        }
                        if ($city == 82) {
                            $conds[1] = 17;
                        }
                        if ($city == 83) {
                            $conds[1] = 18;
                        }
                        if ($city == 84) {
                            $conds[1] = 19;
                        }
                    }
                    if ($province == 53) {//云南
                        if ($city == 32) {//丽江
                            $conds[1] = 7;
                        }
                        if ($city == 35) {//临沧
                            $conds[1] = 9;
                        }
                        if ($city == 83) {
                            $conds[1] = 18;
                        }
                        if ($city == 84) {
                            $conds[1] = 19;
                        }
                    }
                    if ($province == 62) {//甘肃
                        if ($city == 24) {
                            $conds[1] = 11;
                        }
                        if ($city == 26) {
                            $conds[1] = 12;
                        }
                    }
                    if ($province == 11) {//北京
                        if ($city == 20 || $city == 10) {
                            $conds[1] = 1000;
                        }
                    }
                    if ($province == 12) {//天津
                        if ($city == 16) {
                            $conds[1] = 1000;
                        }
                    }
                }
                break;
            default:
                return NULL;
                break;
        }
    } else {
        $sourceid = -1;
    }

    $sql_1n = 'b.cond1 is null';
    $sql_2n = 'b.cond2 is null';
    $sql_3n = 'b.cond3 is null';
    $sql_4n = 'b.cond4 is null';

    $and = ' and ';

    //条件不会跳着为空,只可能是1000, 1100, 1110, 1111
    if (!empty($conds[0]) && ($conds[0] != '其他') && ($conds[0] != '其它')) {//有第一个条件
        $sql_1 = "b.cond1='{$conds[0]}'";
        if (!empty($conds[1]) && ($conds[1] != '其他') && ($conds[1] != '其它')) { //有第二个条件
            $sql_2 = "b.cond2='{$conds[1]}'";
            if (!empty($conds[2]) && ($conds[2] != '其他') && ($conds[2] != '其它')) {//有第三个条件
                $sql_3 = "b.cond3='{$conds[2]}'";
                if (!empty($conds[3]) && ($conds[3] != '其他') && ($conds[3] != '其它')) {//有第四个条件
                    $sql_4 = "b.cond4='{$conds[3]}'";
                    $sql_wh = $sql_1 . $and . $sql_2 . $and . $sql_3 . $and . $sql_4;
                } else {//没有第四个条件
                    $sql_wh = $sql_1 . $and . $sql_2 . $and . $sql_3 . $and . $sql_4n;
                }
            } else {//没有第三个条件
                $sql_wh = $sql_1 . $and . $sql_2 . $and . $sql_3n . $and . $sql_4n;
            }
        } else {//没有第二个条件
            $sql_wh = $sql_1 . $and . $sql_2n . $and . $sql_3n . $and . $sql_4n;
        }
    } else {
        return NULL;
    }

    //DATABASE_DICTAREA，即dict_area表
    $sql = "select a.country,a.province,a.city,a.district from " . DATABASE_AREA . " a inner join " . DATABASE_DICTAREA . " b on a.area_code = b.area_code where ";
    $sql .= $sql_wh . " and 3rd_part='{$sourceid}' order by a.area_code limit 1";
    //$logger->debug("sql is: ".$sql);
    $qr = $dsql->ExecQuery($sql);

    if (!$qr) {
        $logger->error("sql is " . $sql . " mysql error" . $dsql->GetError());
        $result['result'] = false;
        $result['msg'] = '获取地区码的sql出错：' . $sql;
        return $result;
        //return false;
    }
    $count = $dsql->GetTotalRow($qr);
    if ($count == 0) {
        $dsql->FreeResult($qr);
        $logger->error('ATTENTION: Got area code failed! Please check your area condition in table DICT_AREA. If not found, please add a record into DICT_AREA according to table AREA. ' . var_export($conds, true));
        $result['result'] = false;
        $result['msg'] = '码表中查询不到该地区:【' . implode(' | ', $conds) . '】，请补充';
        return $result;
        //return false;
    }
    $result = $dsql->GetArray($qr, MYSQL_ASSOC);
    $dsql->FreeResult($qr);
    $area_arr = array();
    $area_arr['country_code'] = (!$result['country']) ? '' : $result['country'];
    $area_arr['province_code'] = (!$result['province']) ? '' : $result['province'];
    $area_arr['city_code'] = (!$result['city']) ? '' : $result['city'];
    $area_arr['district_code'] = (!$result['district']) ? '' : $result['district'];
    //$logger->debug(__FUNCTION__.var_export($area_arr,true));
    return $area_arr;
}

function get_area_name_by_code($arg_country_code = NULL, $arg_province_code = NULL, $arg_city_code = NULL, $arg_district_code = NULL)
{
    $retname = '';
    $where = '';
    if ($arg_country_code != NULl) {
        $where = "country = '" . $arg_country_code . "' and province is null and city is null and district is null";
    } else if ($arg_province_code != NULL) {
        $where = "province = '" . $arg_province_code . "' and city is null and district is null";
    } else if ($arg_city_code != NULl) {
        $where = "city = '" . $arg_city_code . "' and district is null";
    } else if ($arg_district_code != NULL) {
        $where = "district = '" . $arg_district_code . "'";
    }
    $sql = "select `name`, another_name from " . DATABASE_WEIBOINFO . "." . DATABASE_AREA . " where {$where}";
    $qr = mysql_query($sql);
    if (!$qr) {
        set_error_msg("sql error:" . mysql_error());
    }
    $result = mysql_fetch_array($qr, MYSQL_ASSOC);
    /*
    if(!empty($result['another_name'])){
        $retname = $result['another_name'];
	}
	else if(!empty($result['name'])){
		$retname = $result['name'];
	}
     */
    return $result;
}

/*
 * 通过新浪微博用户的province和city信息，得到匹配的地理信息
 */
/*function get_area_code($province, $city, $sourceid)
{
	global $logger, $dsql;
	$dsql->SelectDB(DATABASE_WEIBOINFO);
	//$logger->debug(__FUNCTION__." params province:{$province}, city:{$city}, sourceid:{$sourceid}。");
    if (!$province && !$city)
    {
        return NULL;
    }
    $area_arr;
    $sourceid = isset($sourceid) ? $sourceid : 1;
    switch ($sourceid){
        case 1://新浪微博
            if(empty($province)){
                $province = 100;
            }
        	if(is_numeric($province) && is_numeric($city)){
	            if(($province == 400 && $city == 16) || $city == 0
	                || ($province == 71 && $city == 90) || ($province == 100)
	                || ($province == 81 && $city == 1) || ($province == 82 && $city == 1)){//选择“海外-其他”时， 修改为1000（不限）
	                $city = 1000;
	            }
	            if($province == 45){//广西柳州的代码由2变动为22
	                if($city == 22){
	                    $city = 2;
	                }
	                if($city == 21){//南宁
	                    $city = 1;
	                }
	            }
	            if($province == 14 && $city == 23){//山西吕梁的代码由11变动为23
	                $city = 11;
	            }
	            if($province == 15){
	                if($city == 26){//内蒙古乌兰察布
	                    $city = 9;
	                }
	                if($city == 28){//内蒙古巴彦淖尔市
	                    $city = 8;
	                }
	            }
	            if($province == 50){//重庆
	                if($city == 81){
	                    $city = 16;
	                }
	                if($city == 82){
	                    $city = 17;
	                }
	                if($city == 83){
	                    $city = 18;
	                }
	                if($city == 84){
	                    $city = 19;
	                }
	            }
	            if($province == 53){//云南
	                if($city == 32){//丽江
	                    $city = 7;
	                }
	                if($city == 35){//临沧
	                    $city = 9;
	                }
	                if($city == 83){
	                    $city = 18;
	                }
	                if($city == 84){
	                    $city = 19;
	                }
	            }
	            if($province == 62){//甘肃
	                if($city == 24){
	                    $city = 11;
	                }
	                if($city == 26){
	                    $city = 12;
	                }
	            }
	            if($province == 11){//北京
	                if($city == 20 || $city == 10){
	                    $city = 1000;
	                }
	            }
	            if($province == 12){//天津
	                if($city == 16){
	                    $city = 1000;
	                }
	            }
	        }
            break;
        case 2://腾讯微博
            return NULL;
            break;
        default:
            return NULL;
            break;
    }
	//$sql = "select * from ".DATABASE_AREA." where int_province='".$province."'
     //   and int_city='".$city."';";
    $wh = isset($city) ? "and b.3rd_city='{$city}'" : "and b.3rd_city is null";
    $sql = "select a.country,a.province,a.city,a.district from ".DATABASE_AREA." a inner join ".DATABASE_DICTAREA." b";
    $sql .= " on a.area_code = b.area_code where b.3rd_province='{$province}' {$wh}";
    $sqlc = $sql." and 3rd_part={$sourceid}";
    $qr = $dsql->ExecQuery($sqlc);
    if (!$qr)
    {
        $logger->error("sql is ".$sqlc." mysql error".$dsql->GetError());
        return NULL;
    }
    $count = $dsql->GetTotalRow($qr);
    if($count == 0){
    	$dsql->FreeResult($qr);
    	$sqlc = $sql." and 3rd_part=-1";
    	$qr = $dsql->ExecQuery($sqlc);//搜索通用的
	    if (!$qr)
	    {
	        $logger->error("sql is ".$sqlc." mysql error".$dsql->GetError());
	        return NULL;
	    }
	    $count = $dsql->GetTotalRow($qr);
	    if($count == 0){
        	return NULL;
	    }
    }
    $result = $dsql->GetArray($qr, MYSQL_ASSOC);
    $dsql->FreeResult($qr);
    $area_arr = array();
    $area_arr['country_code'] = (!$result['country']) ? '' : $result['country'];
    $area_arr['province_code'] = (!$result['province']) ? '' : $result['province'];
    $area_arr['city_code'] = (!$result['city']) ? '' : $result['city'];
    $area_arr['district_code'] = (!$result['district']) ? '' : $result['district'];
    return $area_arr;
}
*/
/*
 * 向solr发送数据
 */
function send_solr(&$statuses_info, $url,$contentType = 'Content-type:application/json')
{
    global $logger;
    if (!$url) {
        $logger->error('opt url is null');
        return false;
    }
//    $logger->debug(__FILE__ . __LINE__ . " curl_exec statuses_info:" . var_export($statuses_info, true));
    if (!empty($statuses_info)) {
        $senddata = json_encode($statuses_info);
    }
    $logger->debug(__FILE__ . __LINE__ . " send data is:" . var_export($senddata, true));
    $timeout = 0;
    //$logger->debug(__FUNCTION__ . " curl_exec statuses_info:" . var_export($statuses_info, true));
    //$logger->debug(__FILE__ . __LINE__ . " invoke solr url:" . $url . " senddata " . var_export($senddata, true));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, DEFAULT_HTTP_TIMEOUT * 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);

    if (!empty($senddata)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $senddata);
    }


    $header_array = array($contentType);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

    $start_time = microtime_float();
    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ( $httpCode != 200 ){
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $logger->error(__FILE__ . __LINE__ . " send http request [post] faield. URL:[" . $url . "] Returncode:{$httpCode}, error:{$error}" . " errorNo:[" . $errno . "].");
        curl_close($ch);
        return false;
    }

    $end_time = microtime_float();
    $logger->info(__FUNCTION__ . " 调用solr花费时间:[" . ($end_time - $start_time) . "] 秒！");
    $logger->debug(__FILE__ . __LINE__ . " respones " . var_export($response, true));
    if ($response === FALSE) {
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $logger->error(__FILE__ . __LINE__ . " invoke solr response is false, URL:[" . $url . "] curl errorcode:{$errno}, error:{$error}" . " reqData:[" . (isset($senddata) ? $senddata : "") . "].");
        //$logger->error(__FUNCTION__ . " curl errorcode:{$errno}, error:{$error}");
        curl_close($ch);
        return false;
    } else {
        //$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        //$header = substr($response, 0, $headerSize);
        //$body = substr($response, $headerSize);
        //$data = json_decode(trim($body), true);

        $data = json_decode($response, true);

        if (empty($data)) {
            $logger->error(__FILE__ . __LINE__ . " invoke solr response json data is null! OriginalData:".$response);
            curl_close($ch);
            return false;
        } else {
            unset($senddata);
            unset($response);
            curl_close($ch);
            //$log_note = 'count is ' . count($statuses_info);
            $logger->info(__FILE__ . __LINE__ . " invoke solr success! URL:[" . $url . "]. data:[" . (isset($data) ? $data : "应答数据为空!"));
            return $data;
            //$logger->error(__FILE__ . __LINE__ . " invoke solr success, count of records is:[" . count($statuses_info) . "].");
        }
    }
    //关闭cURL资源，并且释放系统资源
//    curl_close($ch);
//    unset($senddata);
//    return json_decode($response, true);
}



function send_solr_get($url)
{
    global $logger;
    if (!$url) {
        $logger->error('opt url is null');
        return false;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, DEFAULT_HTTP_TIMEOUT * 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $header_array = array('Content-type:application/json');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

    $start_time = microtime_float();

    $options = array(
        //CURLOPT_URL            => $html_brand,
        //CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING       => "utf8",
        CURLOPT_AUTOREFERER    => true
        //CURLOPT_CONNECTTIMEOUT => 120,
        //CURLOPT_TIMEOUT        => 120,
//        CURLOPT_MAXREDIRS      => 10,
    );
    curl_setopt_array( $ch, $options );

    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ( $httpCode != 200 ){
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $logger->error(__FILE__ . __LINE__ . " send http get request faield. URL:[" . $url . "] Returncode:{$httpCode}, error:{$error}" . " errorNo:[" . $errno . "].");
        curl_close($ch);
        return false;
    }

    $end_time = microtime_float();
    $logger->info(__FUNCTION__ . " send http get request花费时间:[" . ($end_time - $start_time) . "] 秒！");
    $logger->debug(__FILE__ . __LINE__ . " respones " . var_export($response, true));
    if ($response === FALSE) {
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $logger->error(__FILE__ . __LINE__ . " send http get request is false, URL:[" . $url . "] curl errorcode:{$errno}, error:{$error}" . " reqData:[" . (isset($senddata) ? $senddata : "") . "].");
        curl_close($ch);
        return false;
    } else {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

//        list($header, $body) = explode("\r\n\r\n", response, 2);

        $data = json_decode(trim($body), true);
        if (empty($data)) {
            $logger->error(__FILE__ . __LINE__ . " invoke solr response json data is null! OriginalData:".$data);
            curl_close($ch);
            return false;
        } else {
            $logger->info(__FILE__ . __LINE__ . " invoke solr success! URL:[" . $url . "]. data:[" . (isset($data) ? var_export($data,true) : "应答数据为空!"));
            return $data;
        }
    }
    //关闭cURL资源，并且释放系统资源
//    curl_close($ch);
//    unset($senddata);
//    return json_decode($response, true);
}

/*
 * 从solr中查询来源信息
 * */
function getSourceHostFromSolr($start, $pagesize, $field, $searchtxt, $srchostid = NULL)
{
    $srchost = array();
    if (!empty($srchostid) && $srchostid != 'undefined') {
        $srchost = getHostById($srchostid);
    }
    $solrstore = !empty($srchost['solrstore']) ? $srchost['solrstore'] : SOLR_STORE;
    $solrselect = $solrstore . SOLR_PARAM_SELECT;

    $start = empty($start) ? "0" : $start;
    $rows = empty($pagesize) ? (pow(2, 31) - 1) : $pagesize;
    if (empty($field)) {
        $field = "source_host";
    }
    $fl = array();
    $fl[] = $field;
    $condition = "";
    if (!empty($searchtxt)) {
        if (is_array($searchtxt)) {
            $searchtxt = "(" . implode("+", $searchtxt) . ")";
        }
        $condition = $field . ":" . $searchtxt;
    } else {
        $condition = $field . ":*";
    }
    $result = array();
    $facet = "";
    $result['totalcount'] = solr_select_conds($fl, $condition, 0, 0, "", $field, $facet, $solrselect);
    $result['datalist'] = solr_select_conds($fl, $condition, $start, $rows, "", $field, $facet, $solrselect);
    return $result;
}

function getSourceHostByName($sourcename, $srchostid = NULL)
{
    global $logger;
    $srchost = array();
    if (!empty($srchostid) && $srchostid != 'undefined') {
        $srchost = getHostById($srchostid);
    }
    $dbserver = !empty($srchost['dbserver']) ? $srchost['dbserver'] : DATABASE_SERVER;
    $dbuser = !empty($srchost['username']) ? $srchost['username'] : DATABASE_USERNAME;
    $dbpwd = !empty($srchost['password']) ? $srchost['password'] : DATABASE_PASSWORD;

    $adsql = new DB_MYSQL($dbserver, $dbuser, $dbpwd, DATABASE_WEIBOINFO, FALSE);
    $adsql->SelectDB(DATABASE_WEIBOINFO);
    $pos = strpos($sourcename, '*');
    $where = "";
    if ($pos === false) {
        $where = "a.name = '" . $sourcename . "'";
    } else {
        $sourcename = str_replace("*", "%", $sourcename);
        $where = "a.name like '" . $sourcename . "'";
    }
    $sql = "select a.name, b.source from source a, sourceurl b where a.id=b.sourceid and " . $where . "";
    $qr = $adsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error("sql is " . $sql . " mysql error" . $adsql->GetError());
        return NULL;
    }
    $count = $adsql->GetTotalRow($qr);
    if ($count == 0) {
        $adsql->FreeResult($qr);
        return NULL;
    }
    $result = array();
    while ($tmpresult = $adsql->GetArray($qr, MYSQL_ASSOC)) {
        $result[] = $tmpresult['source'];
    }
    $adsql->FreeResult($qr);
    return $result;

}

//通过host查询name，表中查不到的返回host。hosts和names一起返回.
function getSourcenameFromHost($hosts, $srchostid = NULL)
{
    global $logger;
    if (empty($hosts)) {
        return NULL;
    }
    $srchost = array();
    if (!empty($srchostid) && $srchostid != 'undefined') {
        $srchost = getHostById($srchostid);
    }
    $dbserver = !empty($srchost['dbserver']) ? $srchost['dbserver'] : DATABASE_SERVER;
    $dbuser = !empty($srchost['username']) ? $srchost['username'] : DATABASE_USERNAME;
    $dbpwd = !empty($srchost['password']) ? $srchost['password'] : DATABASE_PASSWORD;

    $adsql = new DB_MYSQL($dbserver, $dbuser, $dbpwd, DATABASE_WEIBOINFO, FALSE);
    $sql_hosts = implode("','", $hosts);
    $sql = "select a.name,b.source from " . DATABASE_WEIBOINFO . ".source a, " . DATABASE_WEIBOINFO . ".sourceurl b where a.id=b.sourceid and b.source in ('$sql_hosts')";
    $qr = $adsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error("sql is " . $sql . " mysql error" . $adsql->GetError());
        return NULL;
    }
    $count = $adsql->GetTotalRow($qr);
    $result = array();
    if ($count > 0) {
        while ($tmpresult = $adsql->GetArray($qr, MYSQL_ASSOC)) {
            $result[] = array('name' => $tmpresult['name'], 'code' => $tmpresult['source']);
        }
    }
    $adsql->FreeResult($qr);
    foreach ($hosts as $value) {
        $found = false;
        foreach ($result as $everyresult) {
            if ($value == $everyresult['code']) {
                $found = true;
            }
        }
        if (!$found)
            $result[] = array('name' => $value, 'code' => $value);
    }
    return $result;
}

//key保存sourceid，value保存sourcetype
function get_all_source()
{
    $adsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    $allsourcetype = array();
    $sqlsourcetype = "select id,sourcetype from source";
    $qrst = $adsql->ExecQuery($sqlsourcetype, 'get_all_source');
    if (!empty($qrst)) {
        while ($rsst = $adsql->GetObject($qrst)) {
            $allsourcetype[$rsst->id] = $rsst->sourcetype;
        }
    }
    $adsql->FreeResult($qrst);
    return $allsourcetype;
}

function get_host_from_url($url)
{
    global $logger;
    if (strpos($url, '//') === false)
        $url = 'http://' . $url;
    $urlarr = parse_url($url);
    $host = $urlarr["host"];
    return $host;
}

function get_sourceid_from_url($url)
{
    global $logger;
    if (strpos($url, '//') === false)
        $url = 'http://' . $url;
    $urlarr = parse_url($url);
    $host = $urlarr["host"];
    $sourceid = get_source_id($host);
    return $sourceid;
}

function get_source_id($host,&$timeStatisticObj=null)
{
    global $logger;
    $dbSelectStart =  microtime_float();
    $adsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    $sourceid = NULL;
    $sql = "select sourceid from sourceurl where source='{$host}'";
    $qrst = $adsql->ExecQuery($sql);
    if (!$qrst) {
        $logger->error(__FUNCTION__ . " sql:{$sql} error:" . $adsql->GetError());
    }
    $dbSelectEnd =  microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,DB_SELECT_TIME_KEY,$dbSelectEnd - $dbSelectStart);

    if (!empty($qrst)) {
        $rest = $adsql->GetArray($qrst);
        $sourceid = $rest["sourceid"];
    }
    $adsql->FreeResult($qrst);
    return $sourceid;
}

function get_source_url($sourceid)
{
    global $logger;
    $adsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    $sql = "select source from sourceurl where sourceid = '{$sourceid}'";
    $qrst = $adsql->ExecQuery($sql);
    if (!$qrst) {
        $logger->error(__FUNCTION__ . " sql:{$sql} error:" . $adsql->GetError());
    }
    $sourceurl = array();
    if (!empty($qrst)) {
        while ($rest = $adsql->GetArray($qrst)) {
            $sourceurl[] = $rest["source"];
        }
    }
    $adsql->FreeResult($qrst);
    return $sourceurl;
}

function get_source_name($sourceid)
{
    global $logger;
    $adsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    $sql = "select name from source where id = '{$sourceid}'";
    $qrst = $adsql->ExecQuery($sql);
    if (!$qrst) {
        $logger->error(__FUNCTION__ . " sql:{$sql} error:" . $adsql->GetError());
    }
    $sourcename = "";
    if (!empty($qrst)) {
        $rest = $adsql->GetArray($qrst);
        $sourcename = $rest["name"];
    }
    $adsql->FreeResult($qrst);
    return $sourcename;
}

function myReleaseResource(&$task, &$res_machine, &$res_ip, &$res_acc)
{
    $c_r = $task->taskparams->scene->state;
    if ($c_r == SCENE_NORMAL) {
        if (isset($task->taskparams->scene->machine)) {
            releaseResource($task->taskparams->scene->machine);
            unset($task->taskparams->scene->machine);
        }
        if (isset($task->taskparams->scene->ip)) {
            releaseResource($task->taskparams->scene->ip);
            unset($task->taskparams->scene->ip);
        }
        if (isset($task->taskparams->scene->account)) {
            releaseResource($task->taskparams->scene->account);
            unset($task->taskparams->scene->account);
        }
        if (isset($task->taskparams->scene->appkey)) {
            unset($task->taskparams->scene->appkey);
        }
    } else if ($c_r == SCENE_WAITCONCURRENT_MACHINE) {//无机器资源
        $task->wait_resourcetype = RESOURCE_TYPE_MACHINE;
        $task->wait_resource = $task->machine;
        $task->usetype = USETYPE_CONCURRENT;
        if (isset($task->taskparams->scene->machine)) {
            releaseResource($task->taskparams->scene->machine);
            unset($task->taskparams->scene->machine);
        }
        if (isset($task->taskparams->scene->ip)) {
            releaseResource($task->taskparams->scene->ip);
            unset($task->taskparams->scene->ip);
        }
        if (isset($task->taskparams->scene->account)) {
            releaseResource($task->taskparams->scene->account);
            unset($task->taskparams->scene->account);
        }
        if (isset($task->taskparams->scene->appkey)) {
            unset($task->taskparams->scene->appkey);
        }

    } else if ($c_r == SCENE_WAITCONCURRENT_IP) {
        $task->wait_resourcetype = RESOURCE_TYPE_IP;
        $task->usetype = USETYPE_CONCURRENT;
        if (isset($task->taskparams->scene->ip)) {
            releaseResource($task->taskparams->scene->ip);
            unset($task->taskparams->scene->ip);
        }
        if (isset($task->taskparams->scene->account)) {
            releaseResource($task->taskparams->scene->account);
            unset($task->taskparams->scene->account);
        }
        if (isset($task->taskparams->scene->appkey)) {
            unset($task->taskparams->scene->appkey);
        }

    } else if ($c_r == SCENE_WAITSPIDER_IP) {
        $task->wait_resourcetype = RESOURCE_TYPE_IP;
        $task->wait_resource = $res_ip->resource;
        $task->wait_appkey = $res_ip->appkey;
        $task->usetype = USETYPE_SPIDER;
        if (isset($task->taskparams->scene->account)) {
            releaseResource($task->taskparams->scene->account);
            unset($task->taskparams->scene->account);
        }
    } else if ($c_r == SCENE_WAITCONCURRENT_ACCOUNT) {
        $task->wait_resourcetype = RESOURCE_TYPE_ACCOUNT;
        $task->usetype = USETYPE_CONCURRENT;
        if (isset($task->taskparams->scene->account)) {
            releaseResource($task->taskparams->scene->account);
            unset($task->taskparams->scene->account);
        }
    } else if ($c_r == SCENE_WAITSPIDER_ACCOUNT) {
        $task->wait_resourcetype = RESOURCE_TYPE_ACCOUNT;
        $task->wait_resource = $res_acc->resource;
        $task->wait_appkey = $res_acc->appkey;
        $task->usetype = USETYPE_SPIDER;
    }
}

//检查并获取所有并发资源
//返回状态
function getAllConcurrentRes(&$task, &$res_machine, &$res_ip, &$res_acc)
{
    global $logger;
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " enter res_machine:" . var_export($res_machine, true) . " res_ip " . var_export($res_ip, true) . " res_acc: " . var_export($res_acc, true));
    $isowned_machine = isset($task->taskparams->scene->machine);
    $isowned_ip = isset($task->taskparams->scene->ip);
    $isowned_account = isset($task->taskparams->scene->account);
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " isowned_machine:" . var_export($isowned_machine, true) . " isowned_ip:" . var_export($isowned_ip, true) . " isowned_account:" . var_export($isowned_account, true));
    $result = SCENE_NORMAL;//申请资源的结果
    //检查资源
    do {
        //先获取机器资源 USETYPE_CONCURRENT:并发使用资源
        $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " 获取机器资源 :" . var_export($task->machine, true));
        $m_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_MACHINE, $task->machine, null, $task->tasklevel, $task->queuetime, $isowned_machine);
        if (!$m_r) {//无机器资源
            $result = SCENE_WAITCONCURRENT_MACHINE;//等待机器资源
            break;
        }
        if (!$isowned_machine) {
            $m_r = applySpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_MACHINE, $task->machine, NULL, NULL, $task->tasklevel, $task->queuetime, NULL, $task->machine);
            if (empty($m_r)) {
                $result = SCENE_WAITCONCURRENT_MACHINE;//等待机器资源
                break;
            } else {
                $res_machine = getResourceById($m_r);
                $task->taskparams->scene->machine = $res_machine->id;
            }
        } else {//已占用资源
            $res_machine = getResourceById($task->taskparams->scene->machine);
        }
        if ($isowned_ip) {//已占有IP资源
            $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " 已占有IP资源 :" . var_export($res_ip->resource, true));
            $res_ip = getResourceById($task->taskparams->scene->ip);
            $ip_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_IP, $res_ip->resource, $res_ip->appkey, $task->tasklevel, $task->queuetime, true, $res_machine->resource);
            if (!$ip_r) {
                $result = SCENE_WAITCONCURRENT_IP;//等待IP并发资源
                break;
            }
        } else {//未占用IP资源
            $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " 获取IP资源 :" . var_export($task->tasksource, true));
            $res_ip = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_IP, $task->tasksource, null, $task->machine, $task->tasklevel, $task->queuetime, $task->machine);
        }
        if (empty($res_ip)) {
            $result = SCENE_WAITCONCURRENT_IP;//等待IP并发资源
            break;
        }
        $task->taskparams->scene->ip = $res_ip->id;
        $task->taskparams->scene->appkey = $res_ip->appkey;
        if ($isowned_account) {
            $res_acc = getResourceById($task->taskparams->scene->account);
            if ($res_acc->appkey != $res_ip->appkey) {
                $res_acc = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $task->tasksource, $res_ip->appkey, $res_ip->resource,
                    $task->tasklevel, $task->queuetime, $task->machine);
                if (empty($res_acc)) {
                    //$result = SCENE_WAITCONCURRENT_ACCOUNT;
                    disableResource($res_ip->id);
                    continue;
                    //break;
                }
            } else {
                $acc_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $res_acc->resource,
                    $res_acc->appkey, $task->tasklevel, $task->queuetime, true, $res_ip->resource);
                if (!$acc_r) {
                    $res_acc = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $task->tasksource, $res_ip->appkey, $res_ip->resource,
                        $task->tasklevel, $task->queuetime, $task->machine);
                }
            }
        } else {
            $res_acc = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $task->tasksource, $res_ip->appkey, $res_ip->resource,
                $task->tasklevel, $task->queuetime, $task->machine);
        }
        //如果未取到帐号资源，可能是由于当前的appkey下已经无帐号资源，再次申请其他appkey
        /*if(empty($res_acc)){
            $res_acc = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT,$task->tasksource,null, null,$task->tasklevel, $task->queuetime);
            if(!empty($res_acc)){
                //再次获取到帐号资源后，使用帐号资源的appkey获取新的ip资源
                $res_ip = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_IP,$task->tasksource,$res_acc->appkey,$task->machine, $task->tasklevel, $task->queuetime);
                if(empty($res_ip)){
                    $result = SCENE_WAITCONCURRENT_IP;//等待IP并发资源
                    break;
                }
            }
        }*/
        if (empty($res_acc)) {
            //$result = SCENE_WAITCONCURRENT_ACCOUNT;//等待帐号并发资源
            //有IP资源，无帐号资源。将IP资源禁用，切换下一个IP资源
            disableResource($res_ip->id);
            continue;
            //break;
        }
        $task->taskparams->scene->account = $res_acc->id;
        break;
    } while (true);
    $task->taskparams->scene->state = $result;
}

//检查具体的资源是否可用，可用则申请使用，否则退出任务并排队
//使用资源之前调用
function checkAndApplyResource(&$task, &$res_machine, &$res_ip, &$res_acc)
{
    global $oAuthThird, $logger;
    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " enter task:" . var_export($task, true) . " res_machine:" . var_export($res_machine, true) . " res_ip:" . var_export($res_ip, true) . " res_acc:" . var_export($res_acc, true));
    do {
        $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " check machine :" . var_export($task->machine, true));
        $m_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_MACHINE, $task->machine, null, $task->tasklevel, $task->queuetime, true);
        if (!$m_r) {//不可用
            $task->taskparams->scene->state = SCENE_WAITCONCURRENT_MACHINE;
            break;
        }
        $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " check ip :" . var_export($res_ip->resource, true));
        $ip_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_IP, $res_ip->resource, $res_ip->appkey, $task->tasklevel, $task->queuetime, true, $task->machine);
        if (!$ip_r) {//无IP资源
            $res_ip = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_IP, $task->tasksource, null, $task->machine, $task->tasklevel, $task->queuetime, $task->machine);
            if (empty($res_ip)) {
                $task->taskparams->scene->state = SCENE_WAITCONCURRENT_IP;
                break;
            } else {//成功切换IP
                releaseResource($task->taskparams->scene->ip);
                $task->taskparams->scene->ip = $res_ip->id;
            }
        }
        //IP的apkey与帐号一致
        if ($res_ip->appkey == $res_acc->appkey) {
            $acc_r = checkSpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $res_acc->resource, $res_acc->appkey, $task->tasklevel, $task->queuetime, true, $res_ip->resource);
            if (!$acc_r) {//资源不可用，重新申请
                $res_acc = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $task->tasksource, $res_ip->appkey, $res_ip->resource, $task->tasklevel, $task->queuetime, $task->machine);
                if (empty($res_acc)) {
                    //$task->taskparams->scene->state = SCENE_WAITCONCURRENT_ACCOUNT;
                    //有IP资源，无帐号资源。将IP资源禁用，切换下一个IP资源
                    disableResource($res_ip->id);
                    continue;
                    //break;
                } else {//成功切换帐号
                    releaseResource($task->taskparams->scene->account);
                    $task->taskparams->scene->account = $res_acc->id;
                }
            }
        } else {
            $res_acc = applyResource(USETYPE_CONCURRENT, RESOURCE_TYPE_ACCOUNT, $task->tasksource, $res_ip->appkey, $res_ip->resource, $task->tasklevel, $task->queuetime, $task->machine);
            if (empty($res_acc)) {
                $logger->error(__FUNCTION__ . " 已申请到IP资源(appkey:{$res_ip->appkey}, IP:{$res_ip->resource}), 但无帐号并发资源，禁用当前IP资源并寻找下一个IP资源");
                //$task->taskparams->scene->state = SCENE_WAITCONCURRENT_ACCOUNT;
                //有IP资源，无帐号资源。将IP资源禁用，切换下一个IP资源
                disableResource($res_ip->id);
                continue;
                break;
            } else {//成功切换帐号
                releaseResource($task->taskparams->scene->account);//释放老版本
                $task->taskparams->scene->account = $res_acc->id;
            }
        }
        //申请使用资源
        $ip_r = applySpecificResource(USETYPE_SPIDER, RESOURCE_TYPE_IP, $res_ip->resource, $task->tasksource, $res_ip->appkey, $task->tasklevel, $task->queuetime, $task->machine, $task->machine);
        if (!empty($ip_r)) {
            $acc_r = applySpecificResource(USETYPE_SPIDER, RESOURCE_TYPE_ACCOUNT, $res_acc->resource, $task->tasksource, $res_acc->appkey, $task->tasklevel, $task->queuetime, $res_ip->resource, $task->machine);
            if (empty($acc_r)) {//未取到帐号使用资源
                rollbackSpecificResource($res_ip->id);
                $task->taskparams->scene->state = SCENE_WAITSPIDER_ACCOUNT;
                continue;//继续申请资源
            } else {
                if (empty($oAuthThird) || $oAuthThird->username != $res_acc->resource || $oAuthThird->appkey != $res_acc->appkey) {
                    init_weiboclient($task->tasksource, $res_acc->resource, $res_acc->appkey);
                }
                break;
            }
        } else {
            $task->taskparams->scene->state = SCENE_WAITSPIDER_IP;
            continue;
        }
        break;
    } while (true);
    $task->taskparams->scene->res_ip = $res_ip;
    $task->taskparams->scene->res_acc = $res_acc;
}

//生成错误信息
function getResourceErrorMsg($state)
{
    $msg = '';
    switch ($state) {
        case SCENE_NORMAL:
            break;
        case SCENE_WAITCONCURRENT_MACHINE:
            $msg = '无机器并发资源';
            break;
        case SCENE_WAITCONCURRENT_IP:
            $msg = '无IP并发资源';
            break;
        case SCENE_WAITSPIDER_IP:
            $msg = '无IP使用资源';
            break;
        case SCENE_WAITCONCURRENT_ACCOUNT:
            $msg = '无帐号并发资源';
            break;
        case SCENE_WAITSPIDER_ACCOUNT:
            $msg = '无帐号使用资源';
            break;
        default:
            $msg = '未知错误';
            break;
    }
    return $msg;
}

/**
 *
 * 更新转发数评论数
 * @param $ids
 */
function crawling_count_info_by_ids($ids)
{
    global $apierrorcount, $task, $logger, $oAuthThird, $api_counts_time, $api_counts_count, $res_machine, $res_ip, $res_acc, $needqueue;
    $st = getTaskStatus($task->id);
    if ($st == -1) {
        $logger->info(SELF . " - 人工停止");
        return false;
    }

    checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
    if ($task->taskparams->scene->state != SCENE_NORMAL) {
        $logger->info(SELF . " - " . getResourceErrorMsg($task->taskparams->scene->state));
        $needqueue = true;
        return false;
    }
    $needqueue = false;
    //获取原创的所有转发，包括直接转发、间接转发
    $start_time = microtime_float();
    $apiname = 'statuses_count';
    $statuses_counts = $oAuthThird->get_count_info_by_ids($ids);
    $end_time = microtime_float();
    $api_counts_count++;
    $apitimediff = $end_time - $start_time;
    $api_counts_time += $apitimediff;
    $logger->info(SELF . " - 调用{$apiname}花费时间：" . $apitimediff);
    $result = true;
    if ($statuses_counts === false || $statuses_counts === null) {
        $apierrorcount++;
        $logger->error("{$apiname} API return empty (" . var_export($statuses_counts, true) . "). params ids:{$ids}");
        $result = false;
    } else if (isset($statuses_counts['error_code']) && isset($statuses_counts['error'])) {
        $apierrorcount++;
        $result = checkAPIResult($statuses_counts);
    }
    if ($result === false || $result === NULL) {
        unset($statuses_counts);
        return $result;
    } else {
        return $statuses_counts;
    }
}

function deleteUserFieldPre($user)
{
    $newobj = array();
    //solr中存储的用户字段，都以users_开头，此处去掉users_
    foreach ($user as $fieldname => $fieldvalue) {
        $fieldname = preg_replace("/^users_/", '', $fieldname);
        $newobj[$fieldname] = $fieldvalue;
    }
    return $newobj;
}


/**
 *
 * 从solr中查询用户信息。
 * @param $guids 用户guID数组
 * @param $selectFields 需要查询的字段，NULL表示全部
 * @return 以guid为key的关联数组
 */
function getUsersFromSolr($guids, $selectFields = NULL)
{
    /*if(!empty($selectFields) && !in_array("users_user_updatetime", $selectFields)){
		$selectFields[] = "users_user_updatetime";
	}*/
    $result = solr_select($guids, $selectFields);
    if (!empty($result)) {
        $currtime = time();
        foreach ($result as $key => $value) {
            $result[$key] = deleteUserFieldPre($value);
        }
    }
    return $result;
}

/*
 * 根据用户名查询对应的ID
 * */
function getUserIdByScreenName($screen_name)
{
    global $logger;
    $screen_name = solrEsc($screen_name);
    $url = SOLR_URL_SELECT . "?q=users_screen_name:" . $screen_name . "&fl=users_id&facet=off";
    $r = getSolrData($url);
    $userid = "";
    if (isset($r['errorcode'])) {
        $logger->error(__FUNCTION__ . " {$r['errormsg']}");
    } else if (count($r['query']['docs']) > 0) {
        $datalist = $r['query']['docs'][0];
        if (isset($datalist['users_id'])) {
            $userid = $datalist['users_id'];
        }
    }
    return $userid;
}

/*
 * 根据ID查询对应的用户名
 */
function getScreenNameByUserId($userid)
{
    global $logger;
    $userid = solrEsc($userid);
    $url = SOLR_URL_SELECT . "?q=users_id:" . $userid . "&fl=users_screen_name&facet=off";
    $r = getSolrData($url);
    $screen_name = "";
    if (isset($r['errorcode'])) {
        $logger->error(__FUNCTION__ . " {$r['errormsg']}");
    } else if (count($r['query']['docs']) > 0) {
        $datalist = $r['query']['docs'][0];
        if (isset($datalist['users_screen_name'])) {
            $screen_name = $datalist['users_screen_name'];
        }
    }
    return $screen_name;
}

function getUserFriendIDByScreenName($screen_name)
{
    global $logger;
    $screen_name = solrEsc($screen_name);
    $url = SOLR_URL_SELECT . "?q=users_screen_name:" . $screen_name . "&fl=users_friends_id&facet=off";
    $r = getSolrData($url);
    $userfriendsid = array();
    if (isset($r['errorcode'])) {
        $logger->error(__FUNCTION__ . " {$r['errormsg']}");
    } else if (count($r['query']['docs']) > 0) {
        $datalist = $r['query']['docs'][0];
        if (isset($datalist["users_friends_id"])) {
            $userfriendsid = $datalist['users_friends_id'];
        }
    }
    return $userfriendsid;
}

function setSeedWeibo($sourceid, $id, $mid)
{
    global $logger;
    $sqlconn = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
    if (!empty($id) && !empty($mid)) {
        $wh = " (id='{$id}' or mid='{$mid}')";
    } else if (!empty($id)) {
        $wh = " id='{$id}'";
    } else if (!empty($mid)) {
        $wh = " mid='{$mid}'";
    } else {
        return false;
    }
    $sql = "update " . DATABASE_WEIBO . " set isseed=1 where sourceid={$sourceid} and {$wh}";
    $qr = $sqlconn->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql:{$sql} error:" . $sqlconn->GetError());
    }
    $sqlconn->FreeResult($qr);
}

$apishowstatuscount = 0;
function show_status($id)
{
    global $logger, $task, $res_machine, $res_ip, $res_acc, $oAuthThird, $apierrorcount, $apicount, $apishowstatuscount;
    $result = array("result" => false, "msg" => "");
    do {
        checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state == SCENE_NORMAL) {
            //$logger->debug(__FUNCTION__.' begin show_status id is:'.$id);
            $start_t = microtime_float();
            $weibo_info = $oAuthThird->show_status($id);
            $end_t = microtime_float();
            $timediff = $end_t - $start_t;
            $task->taskparams->scene->api_showstatus_time += $timediff;
            $task->taskparams->scene->api_showstatus_count++;
            $apishowstatuscount++;
            $apicount++;
            //$logger->debug(__FUNCTION__.' end show_status ');//.var_export($weibo_info,true));
            if (empty($weibo_info)) {
                $apierrorcount++;
                $task->taskparams->scene->api_showstatus_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error("show_status({$id}) API return empty (" . var_export($weibo_info, true) . ")");
                $result['msg'] = "调用API：show_status({$id})异常，API返回空";
                $result['result'] = false;
                $result['apiempty'] = true;
            } else if (isset($weibo_info['error'])) {
                $apierrorcount++;
                $task->taskparams->scene->apierrorcount++;
                $task->taskparams->scene->api_showstatus_errorcount++;
                $logger->error("show_status error:" . $weibo_info['error'] . ' error_code:' . $weibo_info['error_code']);
                /*if(strpos($weibo_info['error'], "source paramter(appkey) is missing") !== false){
					//当前帐号使用的appkey出现不可用，将当前帐号资源置成不可用切换下一个资源
					$logger->warn(__FUNCTION__." 禁用当前帐号资源，继续申请其他资源");
					disableResource($res_acc->id);
					continue;
				}*/
                $c_r = checkAPIResult($weibo_info);//检查错误，如果是返回NULL说明资源超出限制
                if ($c_r === NULL) {
                    continue;
                }
                $result['result'] = false;
                $result['msg'] = getAPIErrorText($weibo_info['error_code']);
                $result['error_code'] = $weibo_info['error_code'];
            } else {
                $result['result'] = true;
                //加入url。这个函数要求的是mid，是否要将id先转换成mid
                $weibo_info['page_url'] = weibomid2Url($weibo_info['user']['id'], $id, 1);
                //$weibo_info['sourceid'] = get_sourceid_from_url("weibo.com");
                $weibo_info['source_host'] = "weibo.com";
                //user的host会在insert_user统一做，这里不处理.
                $weibo_info['user']['page_url'] = userid2Url($weibo_info['user']['id'], 1);
                //$weibo_info['user']['sourceid'] = get_sourceid_from_url("weibo.com");
                if (isset($weibo_info['retweeted_status'])) {
                    $weibo_info['retweeted_status']['source_host'] = "weibo.com";
                    if (isset($weibo_info['retweeted_status']['user'])) {
                        $weibo_info['retweeted_status']['page_url'] = weibomid2Url($weibo_info['retweeted_status']['user']['id'], $weibo_info['retweeted_status']['mid'], 1);
                        $weibo_info['retweeted_status']['user']['page_url'] = userid2Url($weibo_info['retweeted_status']['user']['id'], 1);
                    }
                }
                //$logger->debug("result:".var_export( $weibo_info,true));
                $result['weibo'] = $weibo_info;
            }
        } else {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
        }
        break;
    } while (true);
    return $result;
}

$apicommentscount = 0;
function comments_show_batch($cids)
{
    global $logger, $task, $res_machine, $res_ip, $res_acc, $oAuthThird, $apierrorcount, $apicount, $apicommentscount;
    $result = array("result" => false, "msg" => "");
    do {
        checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state == SCENE_NORMAL) {
            $idstr = implode(',', $cids);
            $logger->debug(__FUNCTION__ . ' begin comments_show_batch cids:' . $idstr);
            $start_t = microtime_float();
            $comments = $oAuthThird->comments_show_batch($cids);
            $end_t = microtime_float();
            $timediff = $end_t - $start_t;
            $task->taskparams->scene->api_comments_time += $timediff;
            $task->taskparams->scene->api_comments_count++;
            $apicommentscount++;
            $apicount++;
            $logger->debug(__FUNCTION__ . ' end comments_show_batch ');
            if (empty($comments)) {
                $apierrorcount++;
                $task->taskparams->scene->api_comments_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error("comments_show_batch({$idstr}) API return empty (" . var_export($comments, true) . ")");
                $result['msg'] = "调用API：comments_show_batch({$idstr})异常，API返回空";
                $result['result'] = false;
                $result['apiempty'] = true;
            } else if (isset($comments['error'])) {
                $apierrorcount++;
                $task->taskparams->scene->apierrorcount++;
                $task->taskparams->scene->api_comments_errorcount++;
                $logger->error("show_status error:" . $comments['error'] . ' error_code:' . $comments['error_code']);
                $c_r = checkAPIResult($comments);//检查错误，如果是返回NULL说明资源超出限制
                if ($c_r === NULL) {
                    continue;
                }
                $result['result'] = false;
                $result['msg'] = getAPIErrorText($comments['error_code']);
                $result['error_code'] = $comments['error_code'];
            } else {
                $result['result'] = true;
                //评论没有url
                foreach ($comments as $k => $value) {
                    $comments[$k]['source_host'] = "weibo.com";
                    //$comments[$k]['sourceid'] = get_sourceid_from_url("weibo.com");
                    $comments[$k]['user']['page_url'] = userid2Url($comments[$k]['user']['id'], 1);
                    //$comments[$k]['user']['sourceid'] = get_sourceid_from_url("weibo.com");
                    if (isset($comments[$k]['status'])) {
                        $comments[$k]['status']['source_host'] = "weibo.com";
                        $comments[$k]['status']['page_url'] = weibomid2Url($comments[$k]['status']['user']['id'], $comments[$k]['status']['mid'], 1);
                        $comments[$k]['status']['user']['page_url'] = userid2Url($comments[$k]['status']['user']['id'], 1);
                    }

                }
                $result['comments'] = $comments;
            }

        } else {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
        }
        break;
    } while (true);
    return $result;
}

/**
 *
 * 根据mid获取ID
 * @param $mid
 */
$apiqueryidcount = 0;
function queryid($mid)
{
    global $logger, $task, $res_machine, $res_ip, $res_acc, $oAuthThird, $apierrorcount, $apicount, $apiqueryidcount;
    $result = array("result" => false, "msg" => "");
    do {
        checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state == SCENE_NORMAL) {
            $logger->debug(__FUNCTION__ . ' begin queryid mid is:' . $mid);
            $start_t = microtime_float();
            $weiboid_r = $oAuthThird->queryid($mid);
            $end_t = microtime_float();
            $task->taskparams->scene->api_queryid_count++;
            $apiqueryidcount++;
            $timediff = $end_t - $start_t;
            $task->taskparams->scene->api_queryid_time += $timediff;
            $apicount++;
            if (isset($weiboid_r['error'])) {
                $apierrorcount++;
                $task->taskparams->scene->api_queryid_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error(__FUNCTION__ . " queryid({$mid}) error:" . $weiboid_r['error'] . ' error_code:' . $weiboid_r['error_code']);
                /*if(strpos($weiboid_r['error'], "source paramter(appkey) is missing") !== false){
					//当前帐号使用的appkey出现不可用，将当前帐号资源置成不可用切换下一个资源
					$logger->warn(__FUNCTION__." 禁用当前帐号资源，继续申请其他资源");
					disableResource($res_acc->id);
					continue;
				}*/
                $c_r = checkAPIResult($weiboid_r);//检查错误，如果是返回NULL说明资源超出限制
                if ($c_r === NULL) {
                    continue;
                }
                $result['result'] = false;
                $result['msg'] = getAPIErrorText($weiboid_r['error_code']);
                $result['error_code'] = $weiboid_r['error_code'];
            } else if (empty($weiboid_r) || empty($weiboid_r['id'])) {
                $logger->error(__FUNCTION__ . " queryid({$mid}) error: empty data:" . var_export($weiboid_r, true));
                $result['result'] = false;
                $result['msg'] = '获取微博ID失败';
            } else {
                $result['result'] = true;
                $result['weiboid'] = $weiboid_r['id'];
            }
        } else {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
        }
        break;
    } while (true);
    return $result;
}

/**
 *
 * 抓取用户，两个参数二选一
 * @param $id 用户ID
 * @param $screen_name 用户昵称
 */
$apishowusercount = 0;
function crawling_user($id, $screen_name = NULL)
{
    global $logger, $task, $apicount, $apierrorcount, $apishowusercount,
           $oAuthThird, $res_machine, $res_ip, $res_acc, $apitime;
    if (!isset($task->taskparams->scene->api_showuser_count)) {
        $task->taskparams->scene->api_showuser_count = 0;
    }
    if (!isset($task->taskparams->scene->api_showuser_time)) {
        $task->taskparams->scene->api_showuser_time = 0;
    }
    if (!isset($task->taskparams->scene->api_showuser_errorcount)) {
        $task->taskparams->scene->api_showuser_errorcount = 0;
    }
    $result = array("result" => false, "msg" => "");
    do {
        checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state == SCENE_NORMAL) {
            $logger->debug(__FUNCTION__ . ' begin show_user id is:' . $id . ', screen_name is' . $screen_name);
            $start_t = microtime_float();
            $user_info = $oAuthThird->show_user($id, $screen_name);
            $task->taskparams->scene->api_showuser_count++;
            $apishowusercount++;
            $end_t = microtime_float();
            $timediff = $end_t - $start_t;
            $task->taskparams->scene->api_showuser_time += $timediff;
            $apitime += $timediff;
            $apicount++;
            $logger->debug(__FUNCTION__ . ' end show_user ');
            if ($user_info === false || $user_info === null) {
                $apierrorcount++;
                $task->taskparams->scene->api_showuser_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error(__FUNCTION__ . " show_user({$id},{$screen_name}) API return empty (" . var_export($user_info, true) . ")");
                $task->taskparams->scene->status_desp = "调用API：show_user异常, API返回空";
                $result['result'] = false;
                $result['msg'] = '获取用户失败';
            } else if (isset($user_info['error'])) {
                $apierrorcount++;
                $task->taskparams->scene->api_showuser_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error(__FUNCTION__ . " show_user({$id}, {$screen_name}) error:" . $user_info['error'] . ' error_code:' . $user_info['error_code']);
                /*if(strpos($user_info['error'], "source paramter(appkey) is missing") !== false){
					//当前帐号使用的appkey出现不可用，将当前帐号资源置成不可用切换下一个资源
					$logger->warn(__FUNCTION__." 禁用当前帐号资源，继续申请其他资源");
					disableResource($res_acc->id);
					continue;
				}*/
                $c_r = checkAPIResult($user_info);//检查错误，如果是返回NULL说明资源超限
                if ($c_r === NULL) {//无资源，继续获取资源
                    continue;
                }
                $result['result'] = false;
                $result['msg'] = getAPIErrorText($user_info['error_code']);
                $result['error_code'] = $user_info['error_code'];
            } else {
                $result['result'] = true;
                //add page_url。
                //user的host会在insert_user统一做，这里不处理.
                $user_info['page_url'] = userid2Url($user_info['id'], 1);
                //$user_info['sourceid'] = get_sourceid_from_url("weibo.com");
                $result['user'] = $user_info;
            }
        } else {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
        }
        break;
    } while (true);
    return $result;
}

/**
 *
 * 抓取关注，两个参数二选一
 * @param $id 用户ID
 * @param $screen_name 用户昵称
 */
$apifriendscount = 0;
function crawling_friends($id, $screen_name = NULL)
{
    global $logger, $task, $apicount, $apierrorcount, $apifriendscount,
           $oAuthThird, $res_machine, $res_ip, $res_acc, $apitime;
    if (!isset($task->taskparams->scene->api_friends_count)) {
        $task->taskparams->scene->api_friends_count = 0;
    }
    if (!isset($task->taskparams->scene->api_friends_time)) {
        $task->taskparams->scene->api_friends_time = 0;
    }
    if (!isset($task->taskparams->scene->api_friends_errorcount)) {
        $task->taskparams->scene->api_friends_errorcount = 0;
    }
    $result = array("result" => false, "msg" => "");
    $cursor = 0;
    $count = 200;
    do {
        checkAndApplyResource($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state == SCENE_NORMAL) {
            $logger->debug(__FUNCTION__ . " begin friends({$cursor},{$count},{$id},{$screen_name})");
            $start_t = microtime_float();
            $friends = $oAuthThird->friends($cursor, $count, $id, $screen_name);
            $task->taskparams->scene->api_friends_count++;
            $apifriendscount++;
            $end_t = microtime_float();
            $timediff = $end_t - $start_t;
            $task->taskparams->scene->api_friends_time += $timediff;
            $apitime += $timediff;
            $apicount++;
            $logger->debug(__FUNCTION__ . ' end friends ');
            if ($friends === false || $friends === null) {
                $apierrorcount++;
                $task->taskparams->scene->api_showuser_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error(__FUNCTION__ . " friends({$cursor},{$count},{$id},{$screen_name}) API return empty (" . var_export($friends, true) . ")");
                $task->taskparams->scene->status_desp = "调用API：friends异常, API返回空";
                $result['result'] = false;
                $result['msg'] = '获取关注失败';
            } else if (isset($friends['error'])) {
                $apierrorcount++;
                $task->taskparams->scene->api_showuser_errorcount++;
                $task->taskparams->scene->apierrorcount++;
                $logger->error(__FUNCTION__ . " friends({$cursor},{$count},{$id},{$screen_name}) error:" . $friends['error'] . ' error_code:' . $friends['error_code']);
                $c_r = checkAPIResult($friends);//检查错误，如果是返回NULL说明资源超限
                if ($c_r === NULL) {//无资源，继续获取资源
                    unset($friends);
                    continue;
                }
                $result['result'] = false;
                $result['msg'] = getAPIErrorText($friends['error_code']);
                $result['error_code'] = $friends['error_code'];
            } else {
                $result['result'] = true;
                if (empty($result['friends'])) {
                    $result['friends'] = $friends['users'];
                } else {
                    $result['friends'] = array_merge($result['friends'], $friends['users']);
                }
                $logger->debug(__FUNCTION__ . " get number " . var_export(count($friends['users']), true));
                if ($friends['next_cursor']) {
                    $cursor = $friends['next_cursor'];
                    unset($friends);
                    continue;
                }
            }
        } else {
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
        }
        if (!$result['result']) {
            unset($result['friends']);
        }
        break;
    } while (true);
    return $result;
}

/**
 *
 * 补全微博内容, 爬虫抓取的微博数据不全，缺少用户信息
 * @param $sourceid 数据源
 * @param &$weibos 需要补全用户信息的微博列表
 */
function supplyWeibo($sourceid, &$weibos)
{
    global $logger, $task;
    //以userid为key，value为对象{"w"=>array(),"o"=>array()}，w存放微博索引, o存放原创索引。
    $userhash = array();
    $r = true;
    try {
        for ($i = 0; $i < count($weibos); $i++) {
            if (empty($weibos[$i]['user'])) {
                if (empty($weibos[$i]['userid'])) {
                    $logger->error(__FUNCTION__ . " 未找到userid:" . var_export($weibos[$i], true));
                    return false;
                } else {
                    $userhash["" . $weibos[$i]['userid']]['w'][] = $i;  //'w'中存放微博索引
                }
            }
            if (!empty($weibos[$i]['retweeted_status'])) {
                if (isset($weibos[$i]['retweeted_status']['created_at_ts']) && $weibos[$i]['retweeted_status']['created_at_ts'] == -1) {
                    //原创被删除的
                    $task->taskparams->scene->deleted_weibocount++;
                    $weibos[$i]['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST;
                    continue;
                }
                //原创不为空，原创的user为空，userid不为空
                if (empty($weibos[$i]['retweeted_status']['user'])) {
                    if (empty($weibos[$i]['retweeted_status']['userid'])) {
                        $logger->error(__FUNCTION__ . " 未找到原创的userid:" . var_export($weibos[$i], true));
                        return false;
                    } else {
                        $userhash["" . $weibos[$i]['retweeted_status']['userid']]['o'][] = $i;//'o'中存放原创微博索引
                    }
                }
            }
        }
        if (empty($task->taskparams->scene->user_count)) {
            $task->taskparams->scene->user_count = count($userhash);
        }
        //$logger->debug(__FUNCTION__." 1userhash:".var_export($userhash, true));
        $exists_user = 0;

        foreach ($userhash as $key => $value) {
            $ind_conds = array();
            $ind_conds['users_id'] = $key;
            $ind_conds['sourceid'] = $sourceid;
            $solruser = getUserGuidOrMore($ind_conds, true);
            //$logger->debug(__FUNCTION__."get user from solr:".var_export($solruser,true));
            if ($solruser === false) {
                $logger->error(__FUNCTION__ . " call getUserGuidOrMore return false");
                return false;
            } else {
                //处理数据库中存在的
                if (!empty($solruser)) {
                    $user['fromdatabase'] = true;//指定 来自数据库
                    $exists_user++;
                    //找到user后，将user赋值给所有该用户的微博user字段
                    if (isset($value['w'])) {
                        foreach ($value['w'] as $subk => $subv) {
                            $weibos[$subv]['user'] = $user;
                        }
                    }
                    //找到user后，将user赋值给所有该用户的微博user字段
                    if (isset($value['o'])) {
                        foreach ($value['o'] as $subk => $subv) {
                            $weibos[$subv]['retweeted_status']['user'] = $user;
                        }
                    }
                } else {
                    //处理数据库中没有的
                    $user_result = crawling_user($key);
                    if (!empty($user_result['result']) && !empty($user_result['user'])) {
                        $user = $user_result['user'];
                        if (isset($value['w'])) {
                            foreach ($value['w'] as $subk => $subv) {
                                $weibos[$subv]['user'] = $user;
                            }
                        }
                        if (isset($value['o'])) {
                            foreach ($value['o'] as $subk => $subv) {
                                $weibos[$subv]['retweeted_status']['user'] = $user;
                            }
                        }
                        unset($userhash[$key]);
                    } else if (!empty($user_result['nores'])) {//无资源
                        $r = NULL;
                        break;
                    } else {
                        $r = false;
                        break;
                    }
                }
            }
        }
        /*//先处理数据库中存在的
		$guids = array();
		foreach($userhash as $key => $value){
			$guids[] = "{$sourceid}u_{$key}";
		}
		$solrusers = array();
		if(!empty($guids)){
			$solrusers = getUsersFromSolr($guids);
		}
		if($solrusers === false){
			$logger->error(__FUNCTION__." call getUsersFromSolr return false");
			return false;
		}
		if(!empty($solrusers)){
			foreach($userhash as $key => $value){
				$guid = "{$sourceid}u_{$key}";
				$user = $solrusers[$guid];
				if(!empty($user)){//数据库存在
					$user['fromdatabase'] = true;//指定 来自数据库
					$exists_user ++;
					//找到user后，将user赋值给所有该用户的微博user字段
					if(isset($value['w'])){
						foreach($value['w'] as $subk => $subv){
							$weibos[$subv]['user'] = $user;
						}
					}
					//找到user后，将user赋值给所有该用户的微博user字段
					if(isset($value['o'])){
						foreach($value['o'] as $subk => $subv){
							$weibos[$subv]['retweeted_status']['user'] = $user;
						}
					}
					unset($userhash[$key]);
				}
			}
		}*/
        if (empty($task->taskparams->scene->userexists_count)) {
            $task->taskparams->scene->userexists_count = $exists_user;
        }
        //$logger->debug(__FUNCTION__." 2userhash:".var_export($userhash, true));
        /*//处理数据库中没有的
		foreach($userhash as $key => $value){
			$user_result = crawling_user($key);
			if(!empty($user_result['result']) && !empty($user_result['user'])){
				$user = $user_result['user'];
				if(isset($value['w'])){
					foreach($value['w'] as $subk => $subv){
						$weibos[$subv]['user'] = $user;
					}
				}
				if(isset($value['o'])){
					foreach($value['o'] as $subk => $subv){
						$weibos[$subv]['retweeted_status']['user'] = $user;
					}
				}
				unset($userhash[$key]);
			}
			else if (!empty($user_result['nores'])){//无资源
				$r = NULL;
				break;
			}
			else{
				$r = false;
				break;
			}
		}*/
    } catch (Exception $e) {
        $r = false;
        $logger->error(__FUNCTION__ . " exception:" . $e->getMessage());
    }
    return $r;
}

function checkAPIResult($weibos_info)
{
    global $logger, $res_ip, $res_acc, $task;
    if ($weibos_info['error_code'] == ERROR_IP_OUT_LIMIT) {
        //IP使用超出
        disableResource($res_ip->id);
        $logger->error(SELF . " - 资源{$res_ip->id} IP:{$res_ip->resource}使用超出限制");
        $result = NULL;
    } else if ($weibos_info['error_code'] == ERROR_USER_OUT_LIMIT) {
        //帐号使用超出
        disableResource($res_acc->id);
        $logger->error(SELF . " - 资源{$res_acc->id} 帐号:{$res_acc->resource}使用超出限制");
        $result = NULL;
    } else if ($weibos_info['error_code'] == ERROR_LOGIN) {
        $logger->error(SELF . " - 登录失败,源{$task->tasksource},username:{$res_acc->resource} " . $weibos_info['error_code'] . " - " . $weibos_info['error']);
        disableResource($res_acc->id);
        $result = NULL;
    } else if ($weibos_info['error_code'] == ERROR_TOKEN) {
        $logger->error(SELF . " - token 失效 ,源{$task->tasksource},username:{$res_acc->resource} " . $weibos_info['error_code'] . " - " . $weibos_info['error']);
        $result = NULL;
    } else {
        $logger->error(SELF . " - 访问API失败：" . $weibos_info['error_code'] . " - " . $weibos_info['error']);
        $result = false;
    }
    return $result;
}

/**
 *
 * 根据名称获取认证类型
 * @param $name
 */
function getVerifiedType($name)
{
    $vtarr = verifiedTypeArr();
    if (!empty($vtarr)) {
        foreach ($vtarr as $vi => $vitem) {
            foreach ($vitem as $ei => $eitem) {
                if ($name == $eitem["name"]) {
                    return $eitem["code"];
                }
            }
        }
    }
    return "";
}

function getVerifiedTypeFromSolr()
{
    global $logger;
    $facet = array();
    $limit = pow(2, 31) - 1;
    $facet['request'] = "facet.field=users_verified_type&facetCounts=2&facet.allusercount=false&facet.limit=" . $limit . "&facet.offset=0&facet.minsumcount=1";
    $tmpresult = solr_select_conds(NULL, "", 0, 1, "", "", $facet);
    $otherres = $tmpresult["facet_fields"]['users_verified_type']['countList'];
    $othervt = array();
    // $other[] = array("name"=>"认证车主","code"=>'认证车主', "verified"=>4);
    foreach ($otherres as $oi => $oitem) {
        if (!is_numeric($oitem['text'])) {
            $tmparr = array('name' => $oitem['text'], 'code' => $oitem['text'], 'verified' => 4);
            $othervt[] = $tmparr;
        }
    }
    return $othervt;
}

//认证类型
function verifiedTypeArr()
{
    $notverified = array();
    $notverified[] = array("name" => "普通用户", "code" => -1, "verified" => 0);

    $personal = array();//个人
    $personal[] = array("name" => "个人认证", "code" => '0', "verified" => 1);

    $daren = array();//达人
    $daren[] = array("name" => "初级达人", "code" => 200, "verified" => 2);
    $daren[] = array("name" => "中级达人", "code" => 210, "verified" => 2);//微博接口没有给具体的值，自定义的
    $daren[] = array("name" => "高级达人", "code" => 220, "verified" => 2);
    $daren[] = array("name" => "白银达人", "code" => 230, "verified" => 2);
    $daren[] = array("name" => "黄金达人", "code" => 240, "verified" => 2);
    $daren[] = array("name" => "白金达人", "code" => 250, "verified" => 2);
    $daren[] = array("name" => "星钻达人", "code" => 260, "verified" => 2);
    $daren[] = array("name" => "晶钻达人", "code" => 270, "verified" => 2);
    $daren[] = array("name" => "璀钻达人", "code" => 280, "verified" => 2);

    $org = array();//企业机构
    $org[] = array("name" => "机构认证", "code" => -2, "verified" => 3);//爬虫抓取的没有具体的值，只能辨别黄V和蓝V，蓝V时赋值为-2
    $org[] = array("name" => "政府认证", "code" => 1, "verified" => 3);
    $org[] = array("name" => "企业认证", "code" => 2, "verified" => 3);
    $org[] = array("name" => "媒体认证", "code" => 3, "verified" => 3);
    $org[] = array("name" => "校园认证", "code" => 4, "verified" => 3);
    $org[] = array("name" => "应用认证", "code" => 5, "verified" => 3);
    $org[] = array("name" => "网站认证", "code" => 6, "verified" => 3);
    $org[] = array("name" => "团体认证", "code" => 7, "verified" => 3);

    $other = array();//其他
    $other = getVerifiedTypeFromSolr();

    $verified = array("0" => $notverified, "1" => $personal, "2" => $daren, "3" => $org, "4" => $other);
    return $verified;
}

function verifiedArr()
{
    $verified = array();
    //$verified[] = array("name"=>"认证", "code"=>"verify_1", "haschild"=>"selectverified");
    $verified[] = array("name" => "个人认证", "code" => "verify_1");
    $verified[] = array("name" => "非认证", "code" => "verify_0");
    $verified[] = array("name" => "达人", "code" => "verify_2", "haschild" => "selectwelluser");
    $verified[] = array("name" => "企业机构", "code" => "verify_3", "haschild" => "selectwellorg");
    $verified[] = array("name" => "其他", "code" => "verify_4", "haschild" => "selectwellother");
    return $verified;
}

/**
 * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
 * @param string $key 密钥
 * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
 *
 * @example
 * $a = authcode('abc', 'ENCODE', 'key');
 * $b = authcode($a, 'DECODE', 'key'); // $b(abc)
 * $a = authcode('abc', 'ENCODE', 'key', 3600);
 * $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;
    // 随机密钥长度 取值 0-32;
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
    // 当此值为 0 时，则不产生随机密钥
    $key = md5($key ? $key : EABAX::getAppInf('KEY'));
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);

    $rndkey = array();

    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

// url 为: http://weibo.com/1837553744/ychb5jMVT
//$mid = '3429259714716333';
//echo '3429259714716333<br>' . midToStr($mid);
//mid转换为url地址后缀
function midToStr($mid)
{
    settype($mid, 'string');
    $mid_length = strlen($mid);
    $url = '';
    $str = strrev($mid);
    $str = str_split($str, 7);
    $grouplen = count($str);
    foreach ($str as $k => $v) {
        $tmpurl = intTo62(strrev($v));
        if ($k < $grouplen - 1) {
            while (strlen($tmpurl) < 4) {
                $tmpurl .= "0";
            }
        }
        $url .= $tmpurl;
    }
    $url_str = strrev($url);

    return $url_str;
}

function str62value($key) //62进制字典
{
    $str62keys = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b",
        "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r",
        "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H",
        "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X",
        "Y", "Z");
    return $str62keys[$key];
}

/* url 10 进制 转62进制*/
function intTo62($int10)
{
    $s62 = '';
    $r = 0;
    while ($int10 != 0) {
        $r = $int10 % 62;
        $s62 .= str62value($r);
        $int10 = floor($int10 / 62);
    }
    return $s62;
}

//echo 'ychb5jMVT<br>' . sinaWburl2ID('ychb5jMVT');
function sinaWburl2ID($url)
{
    $surl[2] = str62to10(substr($url, strlen($url) - 4, 4));
    while (strlen($surl[2]) < 7) {
        $surl[2] = '0' . $surl[2];
    }
    $surl[1] = str62to10(substr($url, strlen($url) - 8, 4));
    while (strlen($surl[1]) < 7) {
        $surl[1] = '0' . $surl[1];
    }

    $surl[0] = str62to10(substr($url, 0, strlen($url) - 8));
    return $surl[0] . $surl[1] . $surl[2];
}

function str62to10($str62)
{
    $strarry = str_split($str62);
    $str = 0;
    for ($i = 0; $i < strlen($str62); $i++) {
        $vi = Pow(62, (strlen($str62) - $i - 1));
        $str += $vi * str62keys($strarry[$i]);
    }
    return $str;
}

function str62keys($ks) //62进制字典
{
    $str62keys = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b",
        "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r",
        "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H",
        "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X",
        "Y", "Z");
    return array_search($ks, $str62keys);
}

//根据url或者sourceid
function weiboUrl2mid($url, $sourceid = NULL)
{
    if (!isset($url)) {
        return "";
    }
    $fi = strpos($url, "?");
    if ($fi !== false) {
        $url = substr($url, 0, $fi);//只去url中问号前面的部分
    }
    //根据url 获取 sourceid
    if ($sourceid == NULL) {
        //根据url得到host
        $sourceid = get_sourceid_from_url($url);
    }
    switch ($sourceid) {
        case 1:
            if (substr($url, -1, 1) == "/") {
                $url = substr($url, 0, strlen($url) - 1);
            }
            $urlid = strrchr($url, "/");
            return sinaWburl2ID($urlid);
        default:
            return "";
    }
}

//把id或mid或url转为guid
function getGuid($sourceid, $mid, $id = NULL)
{
    if (empty($mid)) {
        return "";
    } else {
        switch ($sourceid) {
            case 1:
                if (!empty($id)) {
                    return $sourceid . "_" . $id;
                } else if (!empty($mid)) {
                    return $item['sourceid'] . "m_" . $art['mid'];
                }
                break;
            default:
                return $sourceid . "_" . base64_encode($mid) . "_0";
                break;
        }
    }
}

/**
 *
 * mid转微博地址
 * @param $userid
 * @param $mid
 * @param $sourceid
 */
function weibomid2Url($userid, $mid, $sourceid)
{
    $url = "";
    switch ($sourceid) {
        case 1:
            $host = "weibo.com";
            $hostarr = get_source_url($sourceid);
            if (count($hostarr) > 0) {
                $host = $hostarr[0];
            }
            $enmid = midToStr($mid);
            $url = "http://{$host}/{$userid}/{$enmid}";
            break;
        default:
            break;
    }
    return $url;
}

function userid2Url($userid, $sourceid)
{
    $url = '';
    switch ($sourceid) {
        case 1:
            $host = '';
            $hostarr = get_source_url($sourceid);
            if (count($hostarr) > 0) {
                $host = $hostarr[0];
            }
            if (!empty($host)) {
                $url = "http://{$host}/u/{$userid}";
            }
            break;
        default:
            break;
    }
    return $url;
}

/**
 *
 * 获取客户端提交的token
 */
function getTokenParam()
{
    $accesstoken = $_REQUEST['token'];
    if (!empty($_GET['token'])) {
        $accesstoken = str_replace(" ", "+", $accesstoken);//get提交时，加号被decode成空格，此时改回去
    }
    return $accesstoken;
}

function isSameDomain()
{
    $samedomain = true;
    //验证是否跨域访问
    if (isset($_SERVER['HTTP_REFERER'])) {
        if (isset($_SERVER['SERVER_NAME'])) {
            $refererarr = parse_url($_SERVER['HTTP_REFERER']);
            if (isset($refererarr['port'])) {
                $refport = $refererarr['port'];
            } else {
                $refport = "80";
            }
            $refhostname = $refererarr['host'] . ":" . $refport;
            if (isset($_SERVER['SERVER_PORT'])) {
                $serverport = $_SERVER['SERVER_PORT'];
            } else {
                $serverport = "80";
            }
            $serverhostname = $_SERVER['SERVER_NAME'] . ":" . $serverport;
            if ($refhostname != $serverhostname) {
                $samedomain = -1;
            } else {
                if (isset($refererarr['path']) && $refererarr['path'] == "/widget.html") { //widget访问
                    $samedomain = -2;
                }
            }
        }
    } else { //没有HTTP_REFRER时为第三方站点
        $samedomain = -1;
    }
    return $samedomain;
}

function setUserNewtime($userid, $sourceid, $newtime)
{
    global $dsql, $logger;
    $sql = "update " . DATABASE_USER . " set interrupt_newtime = {$newtime} where id='{$userid}' and sourceid={$sourceid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql:{$sql} has error:" . $dsql->GetError());
        return false;
    } else {
        return true;
    }
}

function setUserInterrupt($id, $sourceid, $righttime)
{
    global $dsql, $logger;
    $subsql = empty($righttime) ? "interrupt_user_righttime=null" : "interrupt_user_righttime={$righttime}";
    $sql = "update " . DATABASE_WEIBO . " set {$subsql} where id = '{$id}' and sourceid={$sourceid}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql:{$sql} has error:" . $dsql->GetError());
        return false;
    } else {
        return true;
    }
}

/**
 *
 * 获取已存在的微博
 * @param $userinfo
 */
function getExistsCountByUserID($userid)
{
    global $dsql, $logger;
    $sql = "select count(0) as cnt from " . DATABASE_WEIBO . " where userid='{$userid}'";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql is {$sql} has error:" . $dsql->GetError());
        return false;
    } else {
        $rs = $dsql->GetArray($qr);
        if (!empty($rs)) {
            $exists_count = $rs['cnt'];
            return $exists_count;
        } else {
            return 0;
        }
    }
}

/**
 * 获取用户微博的断档
 * @param $userid 用户ID
 * @param $sourceid 数据源ID
 * @return array 断档对象,包括最新ID，各个断档的右侧时间戳 array("interrupt_newtime"=>(int), "interrupts"=>array());
 */
function getUserInterrupts($userid, $sourceid)
{
    global $dsql, $logger;
    //$result = array('interrupt_newtime'=>null, 'interrupts'=>array());
    $result = array();
    //先获取上次获取的最新ID（认为未获取到的微博也是断档，读取断档的右侧时间）
    $sqlnew = "select interrupt_newtime from " . DATABASE_USER . " where id = '{$userid}' and sourceid={$sourceid}";
    $sqlchild = "select id,interrupt_user_righttime, created_at from " . DATABASE_WEIBO . " where userid = '{$userid}'
        and interrupt_user_righttime is not null and sourceid={$sourceid} order by interrupt_user_righttime";
    $qrnew = $dsql->ExecQuery($sqlnew);
    if (!$qrnew) {
        $logger->error(__FUNCTION__ . " sql:{$sqlnew} has error:" . $dsql->GetError());
        return false;
    } else {
        $rsnew = $dsql->GetArray($qrnew);
        if (!empty($rsnew['interrupt_newtime'])) {
            $result[] = array('id' => '', 'righttime' => $rsnew['interrupt_newtime'], 'type' => "newest");//最左侧断档
        }
    }
    $qrchild = $dsql->ExecQuery($sqlchild);
    if (!$qrchild) {
        $logger->error(__FUNCTION__ . " sql:{$sqlchild} has error:" . $dsql->GetError());
        return false;
    } else {
        while ($rschild = $dsql->GetArray($qrchild)) {
            if (!empty($rschild['interrupt_user_righttime'])) {
                $result[] = array("id" => $rschild['id'], "righttime" => $rschild['interrupt_user_righttime'], 'type' => 'normal', "created_at" => $rschild['created_at']);//普通断档
            }
        }
    }
    return $result;
}

/**
 * 处理用户微博时间线
 * @param $left 连续时间线的左边界（新），array('id'=>(string), 'userid'=>(string), 'created_at'=>(int), 'statuses_count'=>(int))
 * @param $right 连续时间线的右边界（旧），类型同left
 * @param $sourceid 数据源ID
 * @param $interrupts 用户微博断档
 * @param $insession 是否在连续会话中
 * @param $inapi 是否基于API应用
 */
function handleUserTimeline($left, $right, $sourceid, &$interrupts = NULL, $insession = true, $inapi = false)
{
    global $logger;
    $logger->debug(__FUNCTION__ . " enter, left=" . var_export($left, true) . " right=" . var_export($right, true) . " sourceid={$sourceid} interrupts=" . var_export($interrupts, true) . " insession={$insession} inapi={$inapi}");
    $result = array('result' => true, 'msg' => '', 'stop' => false);
    if (!isset($interrupts)) {
        $interrupts = getUserInterrupts($left['userid'], $sourceid);
        if ($interrupts === false) {
            $result['result'] = false;
            $result['msg'] = '获取用户断档失败';
            return $result;
        }
        $logger->debug(__FUNCTION__ . " 获取用户断档 " . var_export($interrupts, true));
    }
    if ($inapi && empty($left)) {
        //依次处理每个断档
        foreach ($interrupts as $key => $value) {
            if ($value['type'] == 'newest') {
                continue;
            }
            if ($right['created_at'] < $value['righttime']) {
                if (setUserInterrupt($value['id'], $sourceid, NULL) == false) {
                    $result['result'] = false;
                    $result['msg'] = '删除断档失败';
                    return $result;
                }
                $logger->debug(__FUNCTION__ . " 删除断档 " . var_export($value, true));
                array_shift($interrupts);
            } else {
                break;
            }
        }
    } else {
        $internum = count($interrupts);
        // 处理最左断档
        $_interrupt = NULL;
        foreach ($interrupts as $key => $value) {
            if ($value['type'] == 'newest') {
                $_interrupt = $value;
                $internum--;
                break;
            }
        }
        $logger->debug(__FUNCTION__ . " start internum={$internum}");
        if (empty($_interrupt) || $left['created_at'] > $_interrupt['righttime']) {
            if ($left['created_at'] > 0) {
                if (setUserNewtime($left['userid'], $sourceid, $left['created_at']) == false) {
                    $result['result'] = false;
                    $result['msg'] = '设置新断档时间失败';
                    return $result;
                }
                $logger->debug(__FUNCTION__ . " 设置新断档时间 " . $left['created_at']);
            }
            if (!empty($_interrupt) && $right['created_at'] >= $_interrupt['righttime']) {
                if (setUserInterrupt($right['id'], $sourceid, $_interrupt['righttime']) == false) {
                    $result['result'] = false;
                    $result['msg'] = '建立断档失败';
                    return $result;
                }
                $logger->debug(__FUNCTION__ . " 建立断档 id=" . $right['id'] . " righttime=" . $_interrupt['righttime']);
                $internum++;
            }
        }
        if ($inapi) {
            // 交叠断档已在外部处理
            $logger->debug(__FUNCTION__ . " exit " . var_export($result, true));
            return $result;
        }
        // 处理有交叠的断档
        foreach ($interrupts as $key => $value) {
            if ($value['type'] == 'newest') {
                continue;
            }
            if ($left['created_at'] <= $value['righttime'] && $left['id'] != $value['id'] ||
                $right['created_at'] >= $value['created_at']
            ) {
                continue;
            }
            if ($left['created_at'] > $value['created_at'] ||
                $left['id'] == $value['id'] ||
                $insession && isset($_SESSION['utl_right']) && $_SESSION['utl_right']['id'] == $value['id'] && $_SESSION['utl_right']['sourceid'] == $sourceid
            ) {
                if (setUserInterrupt($value['id'], $sourceid, NULL) == false) {
                    $result['result'] = false;
                    $result['msg'] = '删除断档失败';
                    return $result;
                }
                $logger->debug(__FUNCTION__ . " 删除断档 " . var_export($value, true));
                $internum--;
            } else {
                if (setUserInterrupt($value['id'], $sourceid, $left['created_at']) == false) {
                    $result['result'] = false;
                    $result['msg'] = '更新断档失败';
                    return $result;
                }
                $logger->debug(__FUNCTION__ . " 更新断档 " . var_export($value, true) . " righttime=" . $left['created_at']);
            }
            if ($right['created_at'] >= $value['righttime']) {
                if (setUserInterrupt($right['id'], $sourceid, $value['righttime']) == false) {
                    $result['result'] = false;
                    $result['msg'] = '建立断档失败';
                    return $result;
                }
                $logger->debug(__FUNCTION__ . " 建立断档 id=" . $right['id'] . " righttime=" . $value['righttime']);
                $internum++;
            }
        }
        $logger->debug(__FUNCTION__ . " end internum={$internum}");
        // 无断档并已抓取全
        if ($internum == 0) {
            $exist_count = getExistsCountByUserID($left['userid']);
            if ($exist_count === false) {
                $result['result'] = false;
                $result['msg'] = '读取微博数失败';
                return $result;
            }
            if (isset($left['statuses_count'])) {
                if ($exist_count >= $left['statuses_count']) {
                    $result['stop'] = true;
                }
            }
        }
    }
    // 缓存右边界
    if ($insession) {
        unset($_SESSION['utl_right']);
        $_SESSION['utl_right'] = array('id' => $right['id'], 'sourceid' => $sourceid);
        $logger->debug(__FUNCTION__ . " 缓存右边界 " . var_export($_SESSION['utl_right'], true));
    }
    $logger->debug(__FUNCTION__ . " exit " . var_export($result, true));
    return $result;
}

//获取一个（或全部）种子用户
//$task->taskparams 参数：andor,users,usertype,min_follower_count
//分别代表：条件之间的与或关系，指定具体的用户id数组，指定用户类型，指定大于该粉丝数的
function getSeedUser(&$task, $getall = false)
{
    global $logger, $needqueue;
    if (!isset($task->taskparams->select_user_cursor)) {
        $task->taskparams->select_user_cursor = 0;
    }
    $seeduser = null;
    //根据配置的参数生成查询条件
    $sqlarr = array();
    //指定了用户
    $screen_name_count = isset($task->taskparams->users) ? count($task->taskparams->users) : 0;
    //指定了用户时, 需要根据id查询最新的昵称,不相同时更新
    $isid = false;
    $hasname = false;
    $isid = !empty($task->taskparams->inputtype) && $task->taskparams->inputtype == 'id';
    $userarr = array();//保存用户id //需要根据ID循环, 没有ID数组时报错
    if ($isid) {
        $userarr = $task->taskparams->users;
    } else {
        $userarr = $task->taskparams->userids;
        $isid = true; //当用户添加的是昵称时,会自动添加对应的id数组
        $hasname = true;
    }
    if (empty($userarr)) {
        $logger->error(__FILE__ . __LINE__ . " 用户id 数组为空");
        return false;
    }
    if (!empty($userarr) && $screen_name_count > 0) {
        $isseeduser = empty($task->taskparams->usertype) ? 0 : 1;
        $users_ids = array();
        $users_screen_names = array();
        $needupdateusers = false; //是否需要更改任务中用户信息 ,当用户不存在或用户昵称改变是更改
        $start_t = microtime_float();
        foreach ($userarr as $i => $user) {
            $username = NULL;
            if ($hasname) {
                $username = $task->taskparams->users[$i];
            }
            $res = update_userinfo($task->taskparams->source, $user, $username, $isseeduser, false);
            if ($res['result'] == false) {
                if (!empty($res['notext'])) {
                    $needupdateusers = true;
                    if (!isset($task->taskparams->scene)) {
                        $task->taskparams->scene = (object)array();
                    }
                    if (!isset($task->taskparams->scene->users_notext)) {
                        $task->taskparams->scene->users_notext = array();
                    }
                    $task->taskparams->scene->users_notext[] = $user;
                    if (!isset($task->taskparams->scene->users_notexistname)) {
                        $task->taskparams->scene->users_notexistname = array();
                    }
                    $task->taskparams->scene->users_notexistname[] = $username;
                } else {
                    if (!empty($res['nores'])) {
                        $needqueue = true;
                    }
                    $logger->error(__FUNCTION__ . " " . $res['msg']);
                    return false;
                }
            } else {
                $logger->debug(__FILE__ . __LINE__ . " res " . var_export($res, true));
                if (isset($res['change_screen_name']) && $res['change_screen_name']) {
                    $needupdateusers = true;
                    if (!isset($task->taskparams->scene->users_change_screen_name)) {
                        $task->taskparams->scene->users_change_screen_name = array();
                    }
                    $task->taskparams->scene->users_change_screen_name[] = $username;
                }
                $users_ids[] = $res['user']['users_id'];
                $users_screen_names[] = $res['user']['users_screen_name'];
            }
        }
        $end_t = microtime_float();
        $use_time = $end_t - $start_t;
        $logger->info(__FILE__ . __LINE__ . " 用户昵称校验用时: " . $use_time);
        if ($needupdateusers) {
            if ($hasname) {
                $task->taskparams->users = $users_screen_names;
                $task->taskparams->userids = $users_ids;
            } else {
                $task->taskparams->users = $users_ids;
            }
            //要抓取微博的用户需要更改, 新浪不存在的用户,在solr中仍然存在,根据id会查出来, 所以抓取微博时去除新浪不存在用户
            $userarr = $users_ids;
        }
        if ($isid) {
            $sqlarr[] = 'users_id:(' . implode("+OR+", $userarr) . ')';
        } else {
            $screen_name_arr = array();
            foreach ($userarr as $iu => $u) {
                $screen_name_arr[] = solrEsc($u);
            }
            if (!empty($screen_name_arr)) {
                $sqlarr[] = 'users_screen_name:(' . implode("+OR+", $screen_name_arr) . ')';
            }
        }
    }
    //如果未指定种子用户，则判断是否指定了用户类型
    if (isset($task->taskparams->usertype)) {//usertype： 0 普通用户，1 种子用户
        //指定抓取所有种子用户
        $sqlarr[] = "users_seeduser:{$task->taskparams->usertype}";
    }
    if (isset($task->taskparams->min_follower_count)) {
        //限制了种子用户粉丝数
        $sqlarr[] = "users_followers_count:[{$task->taskparams->min_follower_count}+TO+*]";
    }
    $andor = isset($task->taskparams->andor) ? $task->taskparams->andor : "and";
    $andor = '+' . strtoupper($andor) . '+';
    if (count($sqlarr) == 0) {
        return null;
    }
    $wh = implode($andor, $sqlarr);
    $start = $task->taskparams->select_user_cursor;
    if ($getall) {
        $rows = 100;
    } else {
        $rows = 1;
    }
    $tmpresulttotal = solr_select_conds("", $wh, 0, 0);
    $tmpresult = solr_select_conds("", $wh, $start, $rows, "guid+asc");
    $result["query"]["numFound"] = $tmpresulttotal;
    $result["query"]["docs"] = $tmpresult;
    if ($tmpresulttotal === false) {
        $logger->error(__FUNCTION__ . " call solr_select_conds error: {$result['errormsg']}.  condition is {$wh}. start is {$start}. rows is {$rows}. sort is guid asc");
        return false;
    } else {
        $task->taskparams->seedusercount = $result['query']['numFound'];
        if (count($result['query']['docs']) > 0) {
            if ($getall) {
                if ($task->taskparams->seedusercount > $rows) {
                    $rows = $task->taskparams->seedusercount;
                    $tmpresulttotal = solr_select_conds("", $wh, 0, 0);
                    $tmpresult = solr_select_conds("", $wh, $start, $rows, "guid+asc");
                    $result["query"]["numFound"] = $tmpresulttotal;
                    $result["query"]["docs"] = $tmpresult;
                    if ($tmpresulttotal === false) {
                        $logger->error(__FUNCTION__ . " call solr_select_conds error: {$result['errormsg']}.  condition is {$wh}. start is {$start}. rows is {$rows}. sort is guid asc");
                        return false;
                    }
                }
                $seeduser = array();
                foreach ($result['query']['docs'] as $user) {
                    $seeduser[] = deleteUserFieldPre($user);//去除 users_前缀
                }
            } else {
                $seeduser = $result['query']['docs'][0];
                $seeduser = deleteUserFieldPre($seeduser);//去除 users_前缀
            }
        }
    }
    if ($isid && !$hasname && !empty($seeduser)) {
        if (!isset($task->taskparams->scene)) {
            $task->taskparams->scene = (object)array();
        }
        if (!isset($task->taskparams->scene->users_id2name)) {
            $task->taskparams->scene->users_id2name = array();
        }
        if ($getall) {
            foreach ($seeduser as $user) {
                $task->taskparams->scene->users_id2name[] = $user['id'] . "(" . $user['screen_name'] . ")";
            }
        } else {
            $task->taskparams->scene->users_id2name[] = $seeduser['id'] . "(" . $seeduser['screen_name'] . ")";
        }
    }
    return $seeduser;
}

//获取一个种子微博(原创)
//$task->taskparams 参数：andor,oristatus,min_reposts_count
//分别代表：条件之间的与或关系，指定具体的id数组，指定大于该转发数的
function getRepostSeedWeibo(&$task, $comment = false)
{

    global $logger, $dsql, $currentseed_id, $needqueue;
    $logger->debug('getRepostSeedWeibo' . var_export($task, true));

    $sql = "select * from " . DATABASE_WEIBO . " where sourceid = {$task->taskparams->source}";//数据条件
    //根据配置的参数生成查询条件
    if (!isset($task->taskparams->select_cursor)) {
        $task->taskparams->select_cursor = 0;
    }

    $sqlarr = array();
    $seedidorurl = '';
    $seedmid = '';
    $isurl = false;
    if (!empty($task->taskparams->oristatus)) {
        $task->taskparams->scene->alldatacount = count($task->taskparams->oristatus);
        //select_cursor, 添加的转发或评论任务可以添加多条原创分析轨迹,select_cursor是索引
        if ($task->taskparams->select_cursor >= $task->taskparams->scene->alldatacount) {
            return NULL;
        }
        $seedidorurl = $task->taskparams->oristatus[$task->taskparams->select_cursor];
        if (filter_var($seedidorurl, FILTER_VALIDATE_URL)) {//如果是url
            $isurl = true;
            $seedmid = weiboUrl2mid($seedidorurl, $task->taskparams->source);
            if (empty($seedmid)) {
                $logger->error(__FUNCTION__ . " url:{$seedidorurl} sourceid:{$task->taskparams->source}转MID失败{$seedmid}, 跳过");
                $task->taskparams->select_cursor++;
                return getRepostSeedWeibo($task, $comment);
            } else {
                $sqlarr[] = "mid = '{$seedmid}'";
            }
        } else {
            $sqlarr[] = "id='{$seedidorurl}'";
        }
    }
    if (empty($sqlarr)) {
        $task->taskparams->scene->status_desp = "未找到种子微博";
        $logger->error(__FUNCTION__ . " " . $task->taskparams->scene->status_desp);
        return NULL;
    }

    if (isset($task->taskparams->min_reposts_count)) {
        //限制了种子微博转发数
        $sqlarr[] = "reposts_count >= {$task->taskparams->min_reposts_count}";
    }
    if (isset($task->taskparams->min_comments_count)) {
        //限制了种子微博评论数
        $sqlarr[] = "comments_count >= {$task->taskparams->min_comments_count}";
    }
    if (isset($task->taskparams->min_created_time)) {
        $sqlarr[] = "created_at >= {$task->taskparams->min_created_time}";
    }
    if (isset($task->taskparams->max_created_time)) {
        $sqlarr[] = "created_at <= {$task->taskparams->max_created_time}";
    }
    $andor = isset($task->taskparams->andor) ? $task->taskparams->andor : "and";

    $wh = implode(" {$andor} ", $sqlarr);
    //计算总个数
    /*if(!isset($task->taskparams->scene->alldatacount)){
        $sqlseedcount = "select count(0) as cnt from ".DATABASE_WEIBO." a where is_repost = 0 and sourceid = {$task->taskparams->source}  and ({$wh})";
        $qrseedcount = $dsql->ExecQuery($sqlseedcount);
        if($qrseedcount){
            $rsseedcount = $dsql->GetArray($qrseedcount);
            $task->taskparams->scene->alldatacount = $rsseedcount['cnt'];//总种子个数
        }
        else{
            $logger->error(__FUNCTION__." sql:{$sqlseedcount} error:".$dsql->GetError());
        }
    }
    */
    $sql .= " and ({$wh})";

    //$sql .= " limit {$task->taskparams->select_cursor} ,1";
    $logger->debug(__FUNCTION__ . " sql : {$sql}");
    $seedweibo = null;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $task->taskparams->scene->status_desp = "获取种子微博异常";
        $logger->error(__FUNCTION__ . " - {$task->taskparams->scene->status_desp} sql:{$sql} - " . $dsql->GetError());
        return false;
    } else {
        $seedweibo = $dsql->GetArray($qr);
        if (!empty($seedweibo)) {
            if ((!$comment) && $seedweibo['is_repost']) {
                $logger->warn(__FUNCTION__ . " {$seedidorurl}不是原创,跳过");
                $task->taskparams->select_cursor++;
                return getRepostSeedWeibo($task, $comment);
            }
            if (!empty($seedweibo['id'])) {
                $currentseed_id = $seedweibo['id'];
                $r = get_repost_comment_count($seedweibo);
                if ($r == false) {
                    $task->taskparams->scene->status_desp = "更新{$currentseed_id}的转发数评论数失败";
                    $logger->error(__FUNCTION__ . " 更新{$currentseed_id}的转发数评论数失败");
                    return false;
                }
            } else {
                if (!isset($task->taskparams->scene->api_queryid_count)) {
                    $task->taskparams->scene->api_queryid_count = 0;
                }
                $qid_r = queryid($seedweibo['mid']);
                if ($qid_r['result'] && !empty($qid_r['weiboid'])) {
                    $currentseed_id = $qid_r['weiboid'];
                } else {
                    if (isset($qid_r['error_code']) && $qid_r['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                        $logger->warn(__FUNCTION__ . " 查询{$seedidorurl}的ID，queryid({$seedweibo['mid']})：微博不存在，跳过");
                        $task->taskparams->select_cursor++;
                        return getRepostSeedWeibo($task, $comment);
                    }
                    $task->taskparams->scene->status_desp = "查询原创({$seedidorurl})的ID，queryid({$seedweibo['mid']})失败:" . $qid_r['msg'];
                    $logger->error(__FUNCTION__ . ' ' . $task->taskparams->scene->status_desp);
                    return false;
                }
            }
            $task->taskparams->scene->current_seedweibo_reposts_count = $seedweibo['reposts_count'];//当前种子微博的转发数
        } else {
            $realid;
            if ($isurl) {
                $qidr = queryid($seedmid);
                if ($qidr['result'] && !empty($qidr['weiboid'])) {
                    $realid = $qidr['weiboid'];
                } else {
                    if (isset($qidr['error_code']) && $qidr['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                        $logger->warn(__FUNCTION__ . " 查询{$seedidorurl}的ID，queryid({$seedmid})：微博不存在，跳过");
                        $task->taskparams->select_cursor++;
                        return getRepostSeedWeibo($task, $comment);
                    }
                    $task->taskparams->scene->status_desp = "查询({$seedidorurl})的ID失败，queryid({$seedmid}):" . $qidr['msg'];
                    $logger->error(__FUNCTION__ . ' ' . $task->taskparams->scene->status_desp);
                    return false;
                }
            } else {
                $realid = $seedidorurl;
            }
            $sr = show_status($realid);
            if ($sr['result'] == false) {
                if (isset($sr['error_code']) && $sr['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                    $logger->warn(__FUNCTION__ . " 抓取{$seedidorurl}，show_status({$realid})：微博不存在，跳过");
                    $task->taskparams->select_cursor++;
                    return getRepostSeedWeibo($task, $comment);
                }
                $task->taskparams->scene->status_desp = "抓取{$seedidorurl}，show_status($realid)返回false:" . $sr['msg'];
                $logger->error(__FUNCTION__ . ' ' . $task->taskparams->scene->status_desp);
                return false;
            }
            if ($sr['result'] && !empty($sr['weibo'])) {
                $weibos = array();
                $weibos[] = $sr['weibo'];
                //changeTokenfieldsType($weibos);
                //$solr_r = insert_nested_data($weibos,false,'task','show_status',$task->taskparams->source,NULL,NULL,$task->taskparams->isseed);
                $solr_r = addweibo($task->taskparams->source, $weibos, $task->taskparams->isseed, 'show_status', true);
                if ($solr_r['result'] !== true) {
                    $task->taskparams->scene->status_desp = "新增微博异常";
                    $logger->error(__FUNCTION__ . ' ' . $task->taskparams->scene->status_desp);
                    unset($weibos);
                    return false;
                } else {
                    unset($solr_r);
                    unset($weibos);
                }
                return getRepostSeedWeibo($task, $comment);//重新从数据库中获取种子微博
            } else {
                if ($sr['nores']) {//无资源
                    $needqueue = true;
                    $task->taskparams->scene->status_desp = "无资源";
                    return false;
                }
            }
        }
    }
    $dsql->FreeResult($qr);
    updateTaskInfo($task);
    //将ID转换为url
    if (!empty($seedweibo)) {
        if (!$isurl && !empty($seedweibo['userid']) && !empty($seedweibo['mid'])) {
            $seedweibo['id2url'] = true;//已将ID转换为url
            $seedweibo['weibourl'] = weibomid2Url($seedweibo['userid'], $seedweibo['mid'], $task->taskparams->source);
        } else {
            $seedweibo['weibourl'] = $seedidorurl;
        }
    }
    $tmp = array($seedweibo);
    changeTokenfieldsType($tmp);
    $seedweibo = $tmp[0];
    return $seedweibo;
}

function getWeiboById($source, $id, $isseed = false)
{

    global $logger, $dsql, $task;
    $result = array('result' => true, 'notext' => false, 'nores' => false, 'weibo' => null, 'msg' => '');
    $sql = "select * from " . DATABASE_WEIBO . " where sourceid = {$source} and id = '{$id}'";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        $result['result'] = false;
        $result['msg'] = "访问数据库异常";
        return $result;
    } else {
        $weibo = $dsql->GetArray($qr);
        $dsql->FreeResult($qr);
        if (!empty($weibo)) {
            $result['weibo'] = $weibo;
        } else {
            $sr = show_status($id);
            if ($sr['result'] == false) {
                $result['result'] = false;
                if (isset($sr['error_code']) && $sr['error_code'] == ERROR_CONTENT_NOT_EXIST) {
                    $result['notext'] = true;
                } else {
                    if ($sr['nores']) {
                        $result['nores'] = true;
                    }
                    $result['msg'] = $sr['msg'];
                }
                return $result;
            } else if (!empty($sr['weibo'])) {
                $weibos = array();
                $weibos[] = $sr['weibo'];
                //changeTokenfieldsType($weibos);
                //$solr_r = insert_nested_data($weibos,false,'task', 'show_status', $source, NULL, NULL, $isseed);
                $solr_r = addweibo($source, $weibos, $isseed, 'show_status', true);
                if ($solr_r['result'] !== true) {
                    $result['result'] = false;
                    $result['msg'] = "新增微博异常";
                    return $result;
                }
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
                    $result['result'] = false;
                    $result['msg'] = "访问数据库异常";
                    return $result;
                } else {
                    $weibo = $dsql->GetArray($qr);
                    $dsql->FreeResult($qr);
                    if (!empty($weibo)) {
                        $result['weibo'] = $weibo;
                    } else {
                        $result['result'] = false;
                        $result['msg'] = "微博入库失败";
                        return $result;
                    }
                }
            }
        }
    }
    return $result;
}

function updateFatherId($source, $ids, $fatherid, $depth)
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    if (empty($ids)) {
        return $result;
    }
    //$father_guid = $source."_".$fatherid;
    $ind_conds['sourceid'] = $source;
    $ind_conds['reply_father_mid'] = $fatherid;
    $father_guid = getFatherGuidFromSolr($ind_conds);
    if ($father_guid === false) {
        $logger->error(__FUNCTION__ . " 获取父guid失败");
        return false;
    }

    $sql = "update " . DATABASE_WEIBO . " set father_guid = '{$father_guid}', repost_trend_cursor = {$depth} where sourceid = {$source} and id in ('" . implode("','", $ids) . "')";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        $result['result'] = false;
        $result['msg'] = "访问数据库异常";
        return $result;
    }
    return $result;
}

function addRepostInfo($origid, $repostids, &$startnum, $taskid)
{
    global $logger, $dsql;
    foreach ($repostids as $repostid) {
        $sql = "insert into " . DATABASE_REPOSTINFO . " (origid,repostid,seqno,taskid) values('{$origid}','{$repostid}',{$startnum},{$taskid})";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            $dsql->FreeResult($qr);
            if ($dsql->GetErrorNo() == 1062) {
                // update seqno
                $sql = "update " . DATABASE_REPOSTINFO . " set seqno = {$startnum} where origid = '{$origid}' and repostid = '{$repostid}' and taskid = {$taskid}";
                $qr = $dsql->ExecQuery($sql);
                if (!$qr) {
                    $dsql->FreeResult($qr);
                    $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
                    return false;
                }
                $dsql->FreeResult($qr);
                $startnum++;
                continue;
            }
            $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
            return false;
        }
        $dsql->FreeResult($qr);
        $startnum++;
    }
    return true;
}

function delRepostInfo($taskid, $origid = NULL, $repostid = NULL)
{
    global $logger, $dsql;
    $condstr = "taskid = {$taskid}";
    if (!empty($origid)) {
        $condstr .= " and origid = '{$origid}'";
    }
    if (!empty($repostid)) {
        $condstr .= " and repostid = '{$repostid}'";
    }
    $sql = "delete from " . DATABASE_REPOSTINFO . " where " . $condstr;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $dsql->FreeResult($qr);
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        return false;
    }
    $dsql->FreeResult($qr);
    return true;
}

function getRepostId($origid, $repostid, $seqno, $taskid)
{
    global $logger, $dsql;
    $result = '';
    $condstr = "origid = '{$origid}' and taskid = {$taskid}";
    if (!empty($repostid)) {
        $condstr .= " and repostid = '{$repostid}'";
    }
    if ($seqno >= 0) {
        $condstr .= " and seqno = {$seqno}";
    }
    $sql = "select repostid from " . DATABASE_REPOSTINFO . " where " . $condstr;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        $result = false;
    } else {
        $repost = $dsql->GetArray($qr);
        $dsql->FreeResult($qr);
        if (!empty($repost)) {
            $result = $repost['repostid'];
        }
    }
    return $result;
}

function updateRepostTrend(&$taskobj, $origin)
{
    global $logger, $dsql;
    $result = array('result' => true, 'msg' => '');
    $repost = $taskobj->taskparams->repost[$origin];
    if (empty($repost)) {
        $result['result'] = false;
        $result['msg'] = '一级转发空';
        return $result;
    }
    $res = getWeiboById($taskobj->taskparams->source, $repost->orig, $taskobj->taskparams->isseed);
    if ($res['result'] == false) {
        $result['result'] = false;
        $result['msg'] = '获取原创失败';
        return $result;
    }
    $orig = $res['weibo'];
    $s_time = microtime_float();
    $r = setTotalCounts($taskobj, $orig);
    $e_time = microtime_float();
    $setcountstimediff = $e_time - $s_time;
    $logger->info(SELF . " - 计算总转发数、总到达数完毕，花费{$setcountstimediff}");
    if ($r == false) {
        $result['result'] = false;
        $result['msg'] = '更新转发到达数失败';
        return $result;
    }
    delRepostInfo($taskobj->id, $repost->orig);
    return $result;
}

//设置总转发数、总达到数
function  setTotalCounts(&$task = NULL, $orig)
{
    global $logger, $dsql;
    $logger->debug(__FUNCTION__ . " enter");
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $retweeted_guid = '';
    $depth = 2;// level 1 repost depth
    $wh = "";
    $idflag = false;
    $repost_wh = "is_repost=1";
    if (!empty($orig['id'])) {
        $idormid = $orig['id'];
        $ismid = false;
        $wh = "retweeted_status = '{$orig['id']}'";
        $idflag = true;
    }
    if (!empty($orig['mid'])) {
        $idormid = $orig['mid'];
        $ismid = true;
        $wh = "retweeted_mid = '{$orig['mid']}'";
        $idflag = true;
    }
    if (!$idflag) {
        $logger->error(__FUNCTION__ . " id and mid is empty");
        return false;
    }
    $iscommit = $task->taskparams->iscommit == true ? "true" : "false";//是否立即提交
    $ori_total_repost_count = 0;
    while (1) {
        $st = getTaskStatus($task->id);
        if ($st == -1) {
            $logger->info(__FUNCTION__ . " - 人工停止");
            return false;
        }
        //按时间倒序取
        $sql = "select id,mid,guid, reposts_count,direct_reposts_count, reach_count, sourceid,retweeted_status,retweeted_mid,
            father_guid,comments_count,repost_trend_cursor,analysis_status,total_reach_count,total_reposts_count
            from " . DATABASE_WEIBO . " where {$wh} and {$repost_wh} and sourceid = {$task->taskparams->source}
            order by created_at desc limit {$limitcursor},{$eachcount}";
        $stime = microtime_float();
        $qr = $dsql->ExecQuery($sql);
        $etime = microtime_float();
        $task->taskparams->scene->calc_select_repost_count++;
        $task->taskparams->scene->calc_select_repost_time += $etime - $stime;//查询转发时间
        if (!$qr) {
            $logger->error(__FUNCTION__ . " - 获取转发异常sql:{$sql} - " . $dsql->GetError());
            $result = false;
            break;
        } else {
            $r_count = $dsql->GetTotalRow($qr);
            $ori_total_repost_count += $r_count;
            if ($r_count == 0) {
                $logger->debug(__FUNCTION__ . " - 未获取到" . $idormid . "的转发：limit {$limitcursor},{$eachcount}");
                break;
            }
            $sendsolrdata = array();
            while ($repost_weibo = $dsql->GetArray($qr)) {
                if ($repost_weibo['reposts_count'] > 0) {
                    $sqlgetrepostinfo = "select sum(total_reach_count) as totalreach, count(0) as directrepost from " . DATABASE_WEIBO . "
                        where father_guid = '{$repost_weibo['guid']}' and {$repost_wh} and sourceid = {$task->taskparams->source}";
                    $start_time = microtime_float();
                    $qrgetri = $dsql->ExecQuery($sqlgetrepostinfo);
                    $end_time = microtime_float();
                    $calc_sumtimediff = $end_time - $start_time;
                    $task->taskparams->scene->calc_counts_count++;
                    $task->taskparams->scene->calc_counts_time += $calc_sumtimediff;//计算总转发时间
                    if (!$qrgetri) {
                        $logger->error(__FUNCTION__ . ' sql:' . $sqlgetrepostinfo . ' error:' . $dsql->GetError());
                        $result = false;
                        break 2;
                    } else {
                        $rirs = $dsql->GetArray($qrgetri);
                        $totalreach = $repost_weibo['reach_count'] + $rirs['totalreach'];
                        $totalrepost = $repost_weibo['reposts_count'];
                        $directrepost = $rirs['directrepost'];
                        $upcountssql = "update " . DATABASE_WEIBO . " set total_reach_count = {$totalreach}, total_reposts_count = {$totalrepost} , direct_reposts_count = {$directrepost} where id = '{$repost_weibo['id']}' and sourceid = {$task->taskparams->source}";
                        $start_time = microtime_float();
                        $qrupcount = $dsql->ExecQuery($upcountssql);
                        $end_time = microtime_float();
                        $calc_uptimediff = $end_time - $start_time;
                        $task->taskparams->scene->calc_updatecounts_count++;
                        $task->taskparams->scene->calc_updatecounts_time += $calc_uptimediff;//更新转发数时间
                        if (!$qrupcount) {
                            $logger->error(__FUNCTION__ . ' sql:' . $upcountssql . ' error:' . $dsql->GetError());
                            $result = false;
                            break 2;
                        }
                        $dsql->FreeResult($qrupcount);
                    }
                    $dsql->FreeResult($qrgetri);
                    updateTaskInfo($task);
                } else {
                    $totalreach = $repost_weibo['total_reach_count'];
                    $totalrepost = $repost_weibo['total_reposts_count'];
                    $directrepost = $repost_weibo['direct_reposts_count'];
                }
                $temsolrdata = array();
                $temsolrdata['guid'] = $repost_weibo['guid'];
                if (!empty($repost_weibo['retweeted_status'])) {
                    //$temsolrdata['retweeted_guid'] = $repost_weibo['sourceid']."_".$repost_weibo['retweeted_status'];
                    $temsolrdata['retweeted_guid'] = getOriginalGuidFromSolr(array("sourceid" => $repost_weibo['sourceid'], "retweeted_status" => $repost_weibo['retweeted_status']));
                    if ($temsolrdata['retweeted_guid'] === false) {
                        $logger->error(__FUNCTION__ . " 获取原创guid失败");
                        return false;
                    }
                } else if (!empty($repost_weibo['retweeted_mid'])) {
                    //$temsolrdata['retweeted_guid'] = $repost_weibo['sourceid']."m_".$repost_weibo['retweeted_mid'];
                    $temsolrdata['retweeted_guid'] = getOriginalGuidFromSolr(array("sourceid" => $repost_weibo['sourceid'], "retweeted_mid" => $repost_weibo['retweeted_mid']));
                    if ($temsolrdata['retweeted_guid'] === false) {
                        $logger->error(__FUNCTION__ . " 获取原创guid失败");
                        return false;
                    }
                }
                $retweeted_guid = $temsolrdata['retweeted_guid']; //记录原创guid用于更新原创的直接转发数
                if (!empty($repost_weibo['father_guid'])) {
                    $temsolrdata['father_guid'] = $repost_weibo['father_guid'];
                }
                $temsolrdata['reposts_count'] = $repost_weibo['reposts_count'];
                $temsolrdata['direct_reposts_count'] = $directrepost;
                $temsolrdata['total_reposts_count'] = $totalrepost;
                $temsolrdata['comments_count'] = $repost_weibo['comments_count'];
                $temsolrdata['repost_trend_cursor'] = $repost_weibo['repost_trend_cursor'];
                $temsolrdata['total_reach_count'] = $totalreach;
                $temsolrdata['analysis_status'] = $repost_weibo['analysis_status'];
                $sendsolrdata[] = $temsolrdata;
            }
            //发送给solr
            $url = SOLR_URL_UPDATE;
            $solrcount = count($sendsolrdata);
            $task->taskparams->scene->calc_updatesolr_count += $solrcount;
            $url .= "&commit=" . $iscommit;
            $start_time = microtime_float();
            $logger->info(__FUNCTION__ . " calls solr update. commit: " . $iscommit);
            $solr_r = handle_solr_data($sendsolrdata, $url);
            $end_time = microtime_float();
            $calc_updatesolr_time = $end_time - $start_time;
            $task->taskparams->scene->calc_updatesolr_time += $calc_updatesolr_time;
            unset($sendsolrdata);
            if ($solr_r === false) {
                $result = false;
                $logger->error(__FUNCTION__ . " 调用solr失败");
                break;
            } else if ($solr_r !== NULL && is_array($solr_r)) {
                $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
                $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
            }
            if ($solrcount < $eachcount) {
                break;
            }
            $limitcursor += $eachcount;
        }
        $dsql->FreeResult($qr);
    }
    if (!$result) {
        return $result;
    }
    //更新原创的
    $logger->info(__FUNCTION__ . "更新原创{$idormid}到solr");
    $selorigcountsql = "select sum(total_reach_count) as totalreach, count(0) as directrepost from " . DATABASE_WEIBO . " where {$wh} and sourceid = {$task->taskparams->source} and father_guid = '" . $retweeted_guid . "'"; //以前的逻辑一级转发的father_guid是NULL, 后改成存原创的guid.
    $stime = microtime_float();
    $qrorigcount = $dsql->ExecQuery($selorigcountsql);
    $etime = microtime_float();
    $task->taskparams->scene->calc_counts_time += $etime - $stime;//查询转发时间
    if (!$qrorigcount) {
        $logger->error(__FUNCTION__ . ' sql:' . $selorigcountsql . ' error:' . $dsql->GetError());
        return false;
    }
    $rsorig = $dsql->GetArray($qrorigcount);
    $origreachcount = $orig['reach_count'];
    $totalreach = $origreachcount + $rsorig['totalreach'];
    $directrepost = $rsorig['directrepost'];//实际值是直接转发
    $uporigwh = $ismid ? "mid='{$orig['mid']}'" : "id = '{$orig['id']}'";
    $upcountssql = "update " . DATABASE_WEIBO . " set total_reach_count = {$totalreach}, direct_reposts_count = {$directrepost}
        where {$uporigwh} and sourceid = {$task->taskparams->source}";
    $stime = microtime_float();
    $qrupcount = $dsql->ExecQuery($upcountssql);
    $etime = microtime_float();
    $task->taskparams->scene->calc_updatecounts_time += $etime - $stime;
    if (!$qrupcount) {
        $logger->error(__FUNCTION__ . ' sql:' . $upcountssql . ' error:' . $dsql->GetError());
        $result = false;
        break;
    }
    $dsql->FreeResult($qrupcount);
    $temsolrdata = array();
    if ($ismid) {
        //$temsolrdata['guid'] = $task->taskparams->source."m_".$orig['mid'];
        $temsolrdata['guid'] = getArticleGuidOrMore(array("sourceid" => $task->taskparams->source, "mid" => $orig['mid']));
    } else {
        //$temsolrdata['guid'] = $task->taskparams->source."_".$orig['id'];
        $temsolrdata['guid'] = getArticleGuidOrMore(array("sourceid" => $task->taskparams->source, "id" => $orig['id']));
    }
    if ($temsolrdata['guid'] === false) {
        $logger->error(__FUNCTION__ . " 获取文章guid失败");
        return false;
    }

    $temsolrdata['reposts_count'] = $orig['reposts_count'];
    if (isset($orig['praises_count'])) {
        $temsolrdata['praises_count'] = $orig['praises_count'];
    }
    $temsolrdata['direct_reposts_count'] = $directrepost;
    $temsolrdata['total_reach_count'] = $totalreach;
    //$temsolrdata['total_reposts_count'] = $orig['total_reposts_count'];
    $temsolrdata['total_reposts_count'] = $ori_total_repost_count;
    $temsolrdata['analysis_status'] = $orig['analysis_status'];
    $sendsolrdata[] = $temsolrdata;
    $orig_url = SOLR_URL_UPDATE . "&commit=" . $iscommit;
    $stime = microtime_float();
    $logger->info(__FUNCTION__ . " calls solr update. commit: " . $iscommit);
    $solr_r = handle_solr_data($sendsolrdata, $orig_url);
    $etime = microtime_float();
    $task->taskparams->scene->calc_updatesolr_time += $etime - $stime;
    unset($sendsolrdata);
    if ($solr_r === false) {
        $result = false;
        $logger->error(__FUNCTION__ . " 调用solr失败");
    } else if ($solr_r !== NULL && is_array($solr_r)) {
        $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
    }
    $logger->debug(__FUNCTION__ . " exit");
    return $result;
}

//获取转发数,评论数,赞
function get_repost_comment_count(&$orig)
{
    global $logger, $dsql, $task, $currentseed_id;
    $eachidcount = 100;//每次查询多少个id
    $limitcursor = 0;
    $result = true;
    //请求API获取最新的转发数和评论数
    do {
        $counts_info = crawling_count_info_by_ids($currentseed_id);
        $task->taskparams->scene->statuses_count_count++;
        if ($counts_info === false) {
            $result = false;
            break;
        } else if ($counts_info === NULL) {
            continue;
        }
        if (!empty($counts_info)) {
            if (!empty($orig['id'])) {
                //$guid = $orig['sourceid']."_".$orig['id'];
                $guid = getArticleGuidOrMore(array("sourceid" => $orig['sourceid'], "id" => $orig['id']));
            } else if (!empty($orig['mid'])) {
                //$guid = $orig['sourceid']."m_".$orig['mid'];
                $guid = getArticleGuidOrMore(array("sourceid" => $orig['sourceid'], "mid" => $orig['mid']));
                $counts_info[0]['mid'] = $orig['mid'];
            } else {
                return false;
            }
            if ($guid === false) {
                $logger->error(__FUNCTION__ . " 获取文章guid失败");
                $result = false;
                break;
            }
            $counts_info[0]['guid'] = $guid;
            $tempstatusescount[$guid] = array("comments_count" => $orig['comments_count'], "reposts_count" => $orig['reposts_count']);
            $r = update_status_counts($counts_info, $task->taskparams->source, false, $tempstatusescount);
            if ($r === false) {
                unset($counts_info);
                $result = false;
                break;
            }
            //更新微博的转发数评论数
            $orig['comments_count'] = $counts_info[0]['comments'];
            $orig['reposts_count'] = $counts_info[0]['rt'];
            if (isset($counts_info[0]['attitudes'])) {
                $orig['praises_count'] = $counts_info[0]['attitudes'];
            }
            unset($counts_info);

        }
        break;
    } while (true);

    return $result;
}

/**
 *
 * 获取已存在的微博
 * @param $father
 */
function getExistsCount($father)
{
    global $dsql, $logger;
    $whfield = '';
    if (empty($father['guid']) || (empty($father['id']) && empty($father['mid']))) {
        return false;
    }
    if ($father['is_repost']) {
        $whfield = "father_guid='{$father['guid']}'";
    } else {
        $whfield = !empty($father['id']) ? "retweeted_status='{$father['id']}'" : "retweeted_mid='{$father['mid']}'";
    }
    $sql = "select count(0) as cnt from " . DATABASE_WEIBO . " where {$whfield}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql is {$sql} has error:" . $dsql->GetError());
        return false;
    } else {
        $rs = $dsql->GetArray($qr);
        if (!empty($rs)) {
            $exists_count = $rs['cnt'];
            return $exists_count;
        } else {
            return 0;
        }
    }
}

function matchCronMask($cronmask, $time = NULL)
{
    if (empty($cronmask)) {
        return false;
    }
    if (!isset($time)) {
        $time = time();
    }
    $minute = (int)date('i', $time); //0-59
    $midx = floor($minute / 32);
    $mbit = $minute % 32;
    $hour = (int)date('H', $time); //0-23
    $day = (int)date('d', $time); //1-31
    $month = (int)date('m', $time); //1-12
    $weekday = (int)date('w', $time); //0-6
    if (($cronmask->minute[$midx] & (1 << $mbit)) == 0 ||
        ($cronmask->hour & (1 << $hour)) == 0 ||
        ($cronmask->day & (1 << $day)) == 0 ||
        ($cronmask->month & (1 << $month)) == 0 ||
        ($cronmask->weekday & (1 << $weekday)) == 0
    ) {
        return false;
    }
    $week = floor(($day - 1) / 7) + 1;
    $year = (int)date('Y', $time); //2013
    $lastday = (int)date('d', mktime(0, 0, 0, $month + 1, 0, $year));
    $lastweek = ($lastday - $day < 7) ? 1 : 0;
    if ($cronmask->week & ((1 << $week) | $lastweek) == 0) {
        return false;
    }
    return true;
}

function getCronMask($cronobj)
{
    $cronmask = (object)array('minute' => array(0, 0), 'hour' => 0, 'day' => 0, 'month' => 0, 'weekday' => 0);
    if (empty($cronobj)) {
        return false;
    }
    $minutes = isset($cronobj->minute) ? $cronobj->minute : NULL;
    $hours = isset($cronobj->hour) ? $cronobj->hour : NULL;
    $days = isset($cronobj->day) ? $cronobj->day : NULL;
    $months = isset($cronobj->month) ? $cronobj->month : NULL;
    $weekdays = isset($cronobj->weekday) ? $cronobj->weekday : NULL;
    $weeks = isset($cronobj->week) ? $cronobj->week : NULL;
    $repeat = isset($cronobj->repeat) ? $cronobj->repeat : -1;
    switch ($repeat) {
        case CRONTIME_REPEAT_ONCE:
            break;
        case CRONTIME_REPEAT_MINUTELY:
            unset($minutes);
            unset($hours);
            unset($days);
            unset($months);
            unset($weekdays);
            unset($weeks);
            break;
        case CRONTIME_REPEAT_HOURLY:
            unset($hours);
            unset($days);
            unset($months);
            unset($weekdays);
            unset($weeks);
            break;
        case CRONTIME_REPEAT_DAILY:
            unset($days);
            unset($months);
            unset($weekdays);
            unset($weeks);
            break;
        case CRONTIME_REPEAT_WEEKLY:
            unset($days);
            unset($months);
            unset($weeks);
            break;
        case CRONTIME_REPEAT_WORKDAY:
            unset($days);
            unset($months);
            unset($weeks);
            unset($weekdays);
            $weekdays = array(1, 2, 3, 4, 5);
            break;
        case CRONTIME_REPEAT_MONTHLY:
            unset($months);
            unset($weekdays);
            unset($weeks);
            break;
        case CRONTIME_REPEAT_MONTHWEEK:
            unset($days);
            unset($months);
            break;
        case CRONTIME_REPEAT_YEARLY:
            unset($weekdays);
            unset($weeks);
            break;
        default:
            break;
    }
    if (empty($minutes)) {
        $cronmask->minute[0] = ~0;
        $cronmask->minute[1] = ~0;
    } else {
        $cronmask->minute[0] = 0;
        $cronmask->minute[1] = 0;
        foreach ($minutes as $minute) {
            $midx = floor($minute / 32);
            $mbit = $minute % 32;
            $cronmask->minute[$midx] |= (1 << $mbit);
        }
    }
    if (empty($hours)) {
        $cronmask->hour = ~0;
    } else {
        $cronmask->hour = 0;
        foreach ($hours as $hour) {
            $cronmask->hour |= (1 << $hour);
        }
    }
    if (empty($days)) {
        $cronmask->day = ~0;
    } else {
        $cronmask->day = 0;
        foreach ($days as $day) {
            $cronmask->day |= (1 << $day);
        }
    }
    if (empty($months)) {
        $cronmask->month = ~0;
    } else {
        $cronmask->month = 0;
        foreach ($months as $month) {
            $cronmask->month |= (1 << $month);
        }
    }
    if (empty($weekdays)) {
        $cronmask->weekday = ~0;
    } else {
        $cronmask->weekday = 0;
        foreach ($weekdays as $weekday) {
            if ($weekday == 7) {
                $weekday = 0;
            }
            $cronmask->weekday |= (1 << $weekday);
        }
    }
    if (empty($weeks)) {
        $cronmask->week = ~0;
    } else {
        $cronmask->week = 0;
        foreach ($weeks as $week) {
            if ($week == -1) {
                $week = 0;
            }
            $cronmask->week |= (1 << $week);
        }
    }
    return $cronmask;
}

function getHostById($id)
{
    global $logger, $dsql;
    $logger->debug(__FILE__ . __LINE__ . " id " . var_export($id, true));
    $sql = "select * from " . DATABASE_DATAHOST . " where id = {$id}";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " - sql:{$sql} - " . $dsql->GetError());
        $result = false;
    } else {
        $result = $dsql->GetArray($qr);
        $result['password'] = myDecrypt($result['password']);
        $dsql->FreeResult($qr);
    }
    return $result;
}

function getQuery4Migrate($params)
{
    $q = "sourceid:{$params->source}";
    if (empty($params->cond_deleted)) {
        $q .= " AND !created_at:0";
    }
    if (isset($params->cond_lt_created) && isset($params->cond_ge_created)) {
        $q .= " AND created_at:[{$params->cond_ge_created} TO {$params->cond_lt_created}]";
    } else if (isset($params->cond_lt_created)) {
        $q .= " AND created_at:[* TO {$params->cond_lt_created}]";
    } else if (isset($params->cond_ge_created)) {
        $q .= " AND created_at:[{$params->cond_ge_created} TO *]";
    }
    if (!empty($params->cond_ex_text)) {
        $extext = array();
        foreach ($params->cond_ex_text as $value) {
            $extext[] = solrEsc($value);
        }
        $q .= " !text:(" . implode(" OR ", $extext) . ")";
    }
    if (!empty($params->cond_in_text)) {
        $intext = array();
        foreach ($params->cond_in_text as $value) {
            $intext[] = solrEsc($value);
        }
        $q .= " AND text:(" . implode(" OR ", $intext) . ")";
    }
    if (!empty($params->cond_ex_name)) {
        $exname = array();
        foreach ($params->cond_ex_name as $value) {
            $exname[] = solrEsc($value);
        }
        $q .= " !screen_name:(" . implode(" OR ", $exname) . ")";
    }
    if (!empty($params->cond_in_name)) {
        $inname = array();
        foreach ($params->cond_in_name as $value) {
            $inname[] = solrEsc($value);
        }
        $q .= " AND screen_name:(" . implode(" OR ", $inname) . ")";
    }
    return $q;
}

function connectDB($dbname)
{
    global $dsql;
    if (empty($dsql)) {
        $dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, $dbname, FALSE);
    } else {
        $dsql->SelectDB($dbname);
    }
    return $dsql;
}

/* event structure
	eventlist:
	{
		alarms[]:
		{
			severity
			sevtext
			trigtext
			trigger:
			{
				type: "relation", "boolean"
				"relation"
				{
					obj1:
					{
						type: "const", "var", "cal"
						"const"
						{
							value
						}
						"var"
						{
							props[]
						}
						"cal"
						{
							arg1:
							{
								type: "const", "var", "cal"
								"const"
								{
									value
								}
								"var"
								{
									props[]
								}
								"cal"
								{
									arg1
									arg2
									cop
								}
							}
							arg2
							cop: "+", "-", "*", "/", "%"
						}
					}
					obj2
					rop: "==", "!=", ">", "<", ">=", "<=", "[]", "![", "=]", "[="
				}
				"boolean"
				{
					cond1:
					{
						type: "relation", "boolean"
						"relation"
						{
							obj1
							obj2
							rop
						}
						"boolean"
						{
							cond1
							cond2
							bop
						}
					}
					cond2
					bop: "&&", "||", "!"
				}
			}
			action:
			{
				type: "mail"
				subject
				message
				to
				cc
				bcc
			}
		}
	}
*/
function sortSnapshotField($snapshot, $modelid = NULL)
{
    global $logger;
    //字段顺序和列表中列的顺序一致
    $retfield1 = array("users_screen_name", "users_followers_count", "users_friends_count", "users_statuses_count", "users_replys_count", "users_recommended_count", "users_description", "users_verified_reason", "users_location");
    $retfield2 = array("text", "range", "frq", "rangeend", "users_followers_count", "users_friends_count", "users_statuses_count", "users_recommended_count", "users_favourites_count", "users_bi_followers_count", "alias", "other");
    $retfield31 = array("text", "range", "frq", "rangeend", "reposts_count", "comments_count", "direct_comments_count", "praises_count", "discuss_count", "direct_reposts_count", "total_reposts_count", "followers_count", "total_reach_count", "alias", "other");
    $retfield51 = array("screen_name", "reposts_count", "verify", "userid", "content_type", "praises_count", "sex", "verified_type", "id", "thumbnail_pic", "created_at", "followers_count", "comments_count", "bmiddle_pic", "guid", "source", "sourceid", "mid", "direct_reposts_count", "direct_comments_count", "text", "description", "verified_reason", "retweeted_screen_name", "retweeted_reposts_count", "retweeted_verify", "retweeted_userid", "retweeted_content_type", "retweeted_praises_count", "retweeted_sex", "retweeted_verified_type", "retweeted_id", "retweeted_thumbnail_pic", "retweeted_created_at", "retweeted_followers_count", "retweeted_comments_count", "retweeted_bmiddle_pic", "retweeted_guid", "retweeted_source", "retweeted_sourceid", "retweeted_mid", "retweeted_direct_reposts_count", "retweeted_direct_comments_count", "retweeted_text", "retweeted_description", "retweeted_verified_reason");

    $retfield = array();
    if ($modelid != NULL) {
        switch ($modelid) {
            case 1:
                $retfield = $retfield1;
                break;
            case 2:
                $retfield = $retfield2;
                break;
            case 31:
                $retfield = $retfield31;
                break;
            case 51:
                $retfield = $retfield51;
                break;
            default:
                break;
        }
    } else {
        $retfield = $retfield31;
    }
    //$retArr = array();
    foreach ($snapshot as $si => $sitem) {
        /*
		$tmparr = array();
		$tmparr["categoryname"] = $sitem["categoryname"];
		$tmparr["categoryvalue"] = $sitem["categoryvalue"];
		$tmparr["totalcount"] = $sitem["totalcount"];
		if(isset($sitem["facet"])){
			$tmparr["facet"] = $sitem["facet"];
		}
		$tmparr["datalist"] = array();
		 */
        $datalist = array();
        foreach ($sitem["datalist"] as $di => $ditem) {
            $dtmp = array();
            foreach ($retfield as $ri => $ritem) { //排序字段
                $flag = false;
                foreach ($ditem as $dti => $dtitem) { //数据字段
                    if ($dti == $ritem) {
                        $dtmp[$dti] = $dtitem;
                        $flag = true;
                        unset($ditem[$dti]);
                        break;
                    }
                }
                /*
				if(!$flag){
					$dtmp[$ritem] = "";
				}
				 */
            }
            if (!empty($ditem)) {
                $dtmp = $dtmp + $ditem; //ditem 附加到后面
            }
            $datalist[] = $dtmp;
            //$tmparr["datalist"][] = $dtmp;
        }
        $snapshot[$si]["datalist"] = $datalist;
    }
    //$logger->debug(__FILE__.__LINE__." modelid ".$modelid." snapshot ".var_export($snapshot, true));
    return $snapshot;
}

function checkEvents($instanceid, $elementid, $triggertime, $data, $eventlist, $scheduleid)
{
    global $logger,$task,$dsql;
    $logger->info(__FUNCTION__.__FILE__.__LINE__."事件预警开始了");
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the task is:".var_export($task,true));
//    $logger->info(__FUNCTION__.__FILE__.__LINE__."the data is:".var_export($data,true));
    if (empty($eventlist)) {
        return;
    }
    $userid = $task->userid;
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the userid is:".var_export($userid,true));
    $sql = "select * from ".DATABASE_USERS." where userid = ". $userid;
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $office_name = "";
    } else {
        $result = $dsql->GetArray($qr);
        $office_name = $result['realname'];
        $dsql->FreeResult($qr);
    }
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the user name  is:".var_export($office_name,true));
    //$logger->debug(__FILE__.__LINE__." data ".var_export($data, true)." eventlist ".var_export($eventlist, true));
    $tmpdata = array();
    $logger->debug(__FUNCTION__.__FILE__.__LINE__."the data update begin~");
    //取值范围
    if (isset($eventlist->datastart)) {
        $doffset = $eventlist->datastart;
    } else {
        $doffset = 0;
    }
    $logger->debug(__FILE__.__LINE__." the doffset is :".var_export($doffset,true));
    if (isset($eventlist->dataend)) {
        $dlength = ($eventlist->dataend) - $doffset + 1;
    }
    $logger->debug(__FILE__.__LINE__." the dlength is :".var_export($dlength,true));
    $logger->debug(__FILE__.__LINE__." the data is :".var_export($data,true));
    $hasdata = false;
    foreach ($data as $i => $item) {
        $logger->debug(__FILE__.__LINE__." item data is :".var_export($item["datalist"],true));
        $logger->info(__FILE__.__LINE__." the model data num is :".var_export(count($item["datalist"]),true));

        $tmpitem = array();
        if (isset($item["totalcount"])) {
            $tmpitem["totalcount"] = $item["totalcount"];
        }
        if (isset($item["categoryname"])) {
            $tmpitem["categoryname"] = $item["categoryname"];
        }
        if (isset($item["categoryvalue"])) {
            $tmpitem["categoryvalue"] = $item["categoryvalue"];
        }
        if (isset($dlength)) {
            $total_num = count($item['datalist']);
            $logger->debug(__FILE__.__LINE__." the total data num is :".var_export($total_num,true));
            $logger->debug(__FILE__.__LINE__." the update data start is :".var_export($doffset,true));
            $logger->debug(__FILE__.__LINE__." the update data end is :".var_export($dlength,true));
            //如果事件预警处设置的显示条数大于前台模型输出，以前台模型为准
            if($dlength<$total_num){
                $tmpitem["datalist"] = array_slice($item["datalist"], $doffset, $dlength);
            }else{
                $tmpitem["datalist"] = $item["datalist"];
            }
        } else {
            $tmpitem["datalist"] = array_slice($item["datalist"], $doffset);
        }
        $logger->debug(__FILE__ . __LINE__ . " tmpitem的值为 " . var_export($tmpitem, true));
        if (!empty($tmpitem["datalist"])) {
            $hasdata = true;
        }
        if (isset($item["before"])) {
            if (isset($dlength)) {
                $tmpitem["before"] = array_slice($item["before"], $doffset, $dlength);
            } else {
                $tmpitem["before"] = array_slice($item["before"], $doffset);
            }
        }
        if (!empty($tmpitem["before"])) {
            $hasdata = true;
        }
        if (isset($item["after"])) {
            if (isset($dlength)) {
                $tmpitem["after"] = array_slice($item["after"], $doffset, $dlength);
            } else {
                $tmpitem["after"] = array_slice($item["after"], $doffset);
            }
        }
        if (!empty($tmpitem["after"])) {
            $hasdata = true;
        }
        $tmpdata[] = $tmpitem;
    }


    $logger->info(__FILE__.__LINE__." the tmpdata is :".var_export($tmpdata,true));
    $logger->info(__FILE__.__LINE__." the tmpdata num is :".var_export(count($tmpdata['0']['datalist']),true));
    if (!$hasdata) { //根据过滤条件查询后返回数据为空
        return;
    }
    if (!empty($eventlist->alarms)) {
        $logger->info(__FILE__ . __LINE__ . " eventname " . $eventlist->name . "");
        foreach ($eventlist->alarms as $alarm) {
            //由于发送邮件需要添加上原微博内容和触发条件，所以做出修改   在doEventAction里加入微博内容
            //   之前只是判断结果，所以checkboolean函数只是返回true/false
            //    因为要返回具体微博数据和他的触发条件，所以对返回数据就行组装和处理
            //  关于触发条件处理逻辑 getLogicConds 方法，是从前台逻辑修改的，部分还不可用
            //    下面的测试逻辑第一步到第六步都是
            $res = checkBoolean($tmpdata, $alarm->trigger);

            if ($res && !empty($res)) {

                if($res['key_data']){
                    foreach($res['0'] as $id){
                        foreach($tmpdata['0']['datalist'] as $k=>$v){
                            if($id == $k){
                                $v['office_name'] = $office_name;
                                $res = dock_data($v);
                                foreach($res['key_data'] as $key_data){
                                    if(is_array($key_data)){
                                        $condition = getLogicConds(null, $key_data, null);
                                        if(empty($get_warn_data)){
                                            $get_warn_data = $condition['0'];
                                        }else{
                                            $get_warn_data .= "&&".$condition['0'];
                                        }
                                    }else{
                                        $data = getLogicConds(null, $res['key_data'], null);
                                        $get_warn_data = $data['0'];
                                    }

                                }
                                $logger->info(__FILE__.__LINE__." the insert mysql data is :".var_export($v,true));
                                $logger->info(__FILE__.__LINE__." the warn information is".var_export($get_warn_data,true));

                                //针对不同的应用源可能某列值为空，则显示 “一”  如：百度贴吧没有转帖，则转发数为空
                                $repost_count1 = isset($v['reposts_count']) ? $v['reposts_count'] : "一";
                                $comments_count1 = isset($v['comments_count']) ? $v['comments_count'] : "一";
                                $praises_count1 =  isset($v['praises_count']) ? $v['praises_count'] : "一";
                                $followers_count1 =  isset($v['followers_count']) ? $v['followers_count'] : "一";


                                $header = "<table border='1' style='border-collapse: collapse' align='left' width='90%'><tr><td width='8%' align='center'>用户名</td><td width='30%' align='center'>内容</td><td width='8%' align='center'>时间</td><td width='8%'align='center'>转发数</td><td width='8%' align='center'>评论数</td><td width='8%' align='center'>点赞数</td><td width='8%' align='center'>粉丝数</td><td width='8%' align='center'>原因</td><td width='8%' align='center'>应用来源</td></tr>";
                                if(!isset($warn_data) || empty($warn_data)){
                                    $warn_data = "<tr><td align='center'>".$v['screen_name']."</td><td align='center'><a href='".$v['page_url']."'>".$v['text']['0']."</a></td><td align='center'>".date('Y-m-d H:i:s',$v['created_at'])."</td><td align='center'>".$repost_count1."</td><td align='center'>".$comments_count1."</td><td align='center'>".$praises_count1."</td><td align='center'>".$followers_count1."</td><td align='center'>".$get_warn_data."</td><td align='center'>".$v['source_hostname']."</td></tr>";
									$v['reason'] = $get_warn_data;
                                    $insert_data[] = $v;
                                }else{
                                    $warn_data .= "<tr><td align='center'>".$v['screen_name']."</td><td align='center'><a href='".$v['page_url']."'>".$v['text']['0']."</a></td><td align='center'>".date('Y-m-d H:i:s',$v['created_at'])."</td><td align='center'>".$repost_count1."</td><td align='center'>".$comments_count1."</td><td align='center'>".$praises_count1."</td><td align='center'>".$followers_count1."</td><td align='center'>".$get_warn_data."</td><td align='center'>".$v['source_hostname']."</td></tr>";
									$v['reason'] = $get_warn_data;
                                    $insert_data[] = $v;
                                }
                                $footer = "</table>";
                                unset($get_warn_data);
                            }
                        }
                    }
                }else{
                    foreach($res as $key => $d){
                        foreach($d as $id_condition){
                            foreach($tmpdata['0']['datalist'] as $k=>$v){
                                if($id_condition['id'] == $k){
                                    $v['office_name'] = $office_name;
                                    $res = dock_data($v);
                                    $logger->debug(__FILE__ . __LINE__ . " 预警条件为 " . var_export($id_condition['condition'],true));
                                    $get_warn_data = getLogicConds(null, $id_condition['condition'], null);
                                    $logger->info(__FILE__.__LINE__." the insert mysql data is :".var_export($v,true));
                                    $logger->info(__FILE__.__LINE__." the warn information is".var_export($get_warn_data['0'],true));

                                    //针对不同的应用源可能某列值为空，则显示 “一”  如：百度贴吧没有转帖，则转发数为空
                                    $repost_count1 = isset($v['reposts_count']) ? $v['reposts_count'] : "一";
                                    $comments_count1 = isset($v['comments_count']) ? $v['comments_count'] : "一";
                                    $praises_count1 =  isset($v['praises_count']) ? $v['praises_count'] : "一";
                                    $followers_count1 =  isset($v['followers_count']) ? $v['followers_count'] : "一";

                                    $header = "<table border='1' style='border-collapse: collapse' align='left' width='90%'><tr><td width='8%' align='center'>用户名</td><td width='30%' align='center'>内容</td><td width='8%' align='center'>时间</td><td width='8%'align='center'>转发数</td><td width='8%' align='center'>评论数</td><td width='8%' align='center'>点赞数</td><td width='8%' align='center'>粉丝数</td><td width='8%' align='center'>原因</td><td width='8%' align='center'>应用来源</td></tr>";
                                    if(!isset($warn_data) || empty($warn_data)){
                                        $warn_data = "<tr><td align='center'>".$v['screen_name']."</td><td align='center'><a href='".$v['page_url']."'>".$v['text']['0']."</a></td><td align='center'>".date('Y-m-d H:i:s',$v['created_at'])."</td><td align='center'>".$repost_count1."</td><td align='center'>".$comments_count1."</td><td align='center'>".$praises_count1."</td><td align='center'>".$followers_count1."</td><td align='center'>".$get_warn_data['0']."</td><td align='center'>".$v['source_hostname']."</td></tr>";
                                        $v['reason'] = $get_warn_data['0'];
                                        $insert_data[] = $v;
                                    }else{
                                        $warn_data .= "<tr><td align='center'>".$v['screen_name']."</td><td align='center'><a href='".$v['page_url']."'>".$v['text']['0']."</a></td><td align='center'>".date('Y-m-d H:i:s',$v['created_at'])."</td><td align='center'>".$repost_count1."</td><td align='center'>".$comments_count1."</td><td align='center'>".$praises_count1."</td><td align='center'>".$followers_count1."</td><td align='center'>".$get_warn_data['0']."</td><td align='center'>".$v['source_hostname']."</td></tr>";
										$v['reason'] = $get_warn_data['0'];
										$insert_data[] = $v;
									}
                                    $footer = "</table>";


                                }
                            }
                        }
                    }
                }
                unset($res);
                unset($get_warn_data);
                $warn_data = $header.$warn_data.$footer;
                $status = doEventAction($alarm,$warn_data) ? 1 : 0;
                $logger->info(__FUNCTION__.__FILE__.__LINE__."the warn event result is".var_export($status,true));
                $logger->info(__FILE__ . __LINE__ . " eventname " . $eventlist->name . " do action status " . $status);


                $email_content['alarm'] = $alarm;
                $email_content['content'] = $insert_data;
                $email_content['status'] = $status;
                $email_content['taskid'] = $task->id;
                $logger->info(__FILE__.__LINE__." the email content is:".var_export($email_content,true));
                $result = insert_emailhistory($email_content);
                if($result){
                    $logger->info(__FILE__.__LINE__." the email data insert databse result is:".var_export($result,true));
                }else{
                    $logger->error(__FILE__.__LINE__." the email data insert databse result is failed.");
                }
                unset($warn_data);
                unset($eamil_content);

                saveEventHistory($instanceid, $elementid, $alarm, $triggertime, $status, $scheduleid);
            }
        }
    }
}

function checkBoolean($data, $exp)
{
    global $logger;
    if (empty($data) || empty($exp)) {
        $logger->error(__FUNCTION__ . " Empty data or expression");
        return false;
    }
    $logger->debug(__FILE__ . __LINE__ . " exp " . var_export($exp, true));
    switch ($exp->type) {
        case "relation":
            $res = checkRelation($data, $exp);
            $logger->debug(__FILE__ . __LINE__ . " relation的值 " . var_export($res, true));

            return $res;
        case "boolean":
            switch ($exp->bop) {
                case "!":
                    return !checkBoolean($data, $exp->cond1);
                case "N/A":
                    return checkBoolean($data, $exp->cond1);
                case "&&":

                        $warn_data = array();
                        foreach($exp as $key=>$condition){
                            if($key == 'type' || $key == 'bop'){

                            }else{
                                $warn_data[] = checkBoolean($data, $condition);
                            }
                        }
//                        return $warn_data;
                        $first_ids = array();
                        $last_ids = array();
                        $new_ids = array();
                        $reponse_data = array();
                        $key_data = array();
                        foreach($warn_data as $key=>$data_list){
//                            foreach($data_list['0'] as $k=>$id_list){
                                $first_ids = $data_list['0'];
                                $key_data[$key] = $data_list['key_data'];
                                if(is_array($last_ids) && !empty($last_ids)){
//                                    return $last_ids;
                                    $new_ids = array_intersect($first_ids,$last_ids);
//                                    return $new_ids;
                                    $reponse_data['0'] = $new_ids;
                                    $reponse_data['key_data'] = $key_data;
                                }else{
                                    $last_ids = $first_ids;
//                                    unset($first_ids);
                                }


//                                $reponse_data[$key][$k]['id'] = $id_list;
//                                $reponse_data[$key][$k]['condition'] = $data_list['key_data'];
//                            }
                        }
                        unset($warn_data);
                        return $reponse_data;

    //                    return (checkBoolean($data, $exp->cond1) && checkBoolean($data, $exp->cond2));
                    case "||":
                        $warn_data = array();
                        foreach($exp as $key=>$condition){
                            if($key == 'type' || $key == 'bop'){

                            }else{
                                $warn_data[] = checkBoolean($data, $condition);
                            }
                        }
                        $reponse_data = array();
                        foreach($warn_data as $key=>$data_list){
                            foreach($data_list['0'] as $k=>$id_list){
                                $reponse_data[$key][$k]['id'] = $id_list;
                                $reponse_data[$key][$k]['condition'] = $data_list['key_data'];
                            }
                        }

                        unset($warn_data);
                        return $reponse_data;




    //                    return (checkBoolean($data, $exp->cond1) || checkBoolean($data, $exp->cond2));
                    default:
                        $logger->error(__FUNCTION__ . " Unsupported bool operator " . $exp->bop);
                        return false;
                }
            default:
                $logger->error(__FUNCTION__ . " Unsupported expression type " . $exp->type);
                return false;
        }
}

function checkRelation($data, $exp)
{
    global $logger;
    $v1 = getCalResultNew($data, $exp->obj1);
    if (isset($exp->obj2)) {
        $tbl = isset($exp->obj1->arg1->table) ? $exp->obj1->arg1->table : 0;
        $v2 = getCalResultNew($data, $exp->obj2);
        $logger->info(__FILE__.__LINE__." the v1 num is ".var_export(count($v1),true));
        $logger->info(__FILE__ . __LINE__ . " v1 " . var_export($v1, true) . " rop " . $exp->rop . " v2 " . var_export($v2, true));
        switch ($exp->rop) {
            case "==":
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr) {
                    return (in_array($v2, $v1));
                } else if ($v2arr) {
                    return false;
                } else {
                    return ($v1 == $v2);
                }
            case "!=":
                //return ($v1 != $v2);
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr) {
                    return (!in_array($v2, $v1));
                } else if ($v2arr) {
                    return false;
                } else {
                    return ($v1 != $v2);
                }
            case ">":
                //return ($v1 > $v2);
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr) {
//                    $flag = false;
                    $result = array();
                    $vi_data = array();
                    foreach ($v1 as $vi => $vitem) {
                        if (is_numeric($vitem)) {
                            if ($vitem > $v2) {
                                $vi_data[] = $vi;
                                $result['key_data']['condition'] = $exp;
                            }
                        }
                    }
                    if(!empty($vi_data)){
                        $result[] = $vi_data;
                    }
                    return $result;
                } else if ($v2arr) {
                    return false;
                } else {
                    if (is_numeric($v1) && $v1 > $v2) {
                        return true;
                    } else {
                        return false;
                    }
                }
            case "<":
                //return ($v1 < $v2);
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr) {
                    $flag = false;
                    foreach ($v1 as $vi => $vitem) {
                        if (is_numeric($vitem)) {
                            if ($vitem < $v2) {
                                $vi_data[] = $vi;
                                $result['key_data']['condition'] = $exp;
                            }
                        }
                    }
                    if(!empty($vi_data)){
                        $result[] = $vi_data;
                    }
                    return $result;
                } else if ($v2arr) {
                    return false;
                } else {
                    if (is_numeric($v1) && $v1 < $v2) {
                        return true;
                    } else {
                        return false;
                    }
                }
            case ">=":
                //return ($v1 >= $v2);
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr) {
                    $flag = false;
                    foreach ($v1 as $vi => $vitem) {
                        if (is_numeric($vitem)) {
                            if ($vitem > $v2) {
                                $vi_data[] = $vi;
                                $result['key_data']['condition'] = $exp;
                            }
                        }
                    }
                    if(!empty($vi_data)){
                        $result[] = $vi_data;
                    }
                    return $result;
                } else if ($v2arr) {
                    return false;
                } else {
                    if (is_numeric($v1) && $v1 >= $v2) {
                        return true;
                    } else {
                        return false;
                    }
                }
            case "<=":
                //return ($v1 <= $v2);
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr) {
                    $flag = false;
                    foreach ($v1 as $vi => $vitem) {
                        if (is_numeric($vitem)) {
                            if ($vitem <= $v2) {
                                $vi_data[] = $vi;
                                $result['key_data']['condition'] = $exp;
                            }
                        }
                    }
                    if(!empty($vi_data)){
                        $result[] = $vi_data;
                    }
                    return $result;
                } else if ($v2arr) {
                    return false;
                } else {
                    //return ($v1 <= $v2);
                    if (is_numeric($v1) && $v1 <= $v2) {
                        return true;
                    } else {
                        return false;
                    }
                }
            case "[]":
                $v1arr = is_array($v1);
                $v2arr = is_array($v2);
                if ($v1arr && $v2arr) { //都是数组
                    //检查v2是否包含在v1中
                    $flag = true;
                    foreach ($v2 as $key => $item) {
                        if (!in_array($item, $v1)) {
                            $flag = false;
                            break;
                        }
                    }
                    return $flag;
                } else if ($v1arr) {
                    //某个值含有字符串
//                    $flag = false;
                    $result = array();
                    foreach ($v1 as $vi => $vitem) {
                        if (strpos($vitem, $v2) !== false) {
                            $vi_data[] = $vi;
                            $result['key_data']['condition'] = $exp;
//                            $result['flag'] = true;
                        }
                    }
                    if(!empty($vi_data)){
                        $result[] = $vi_data;
                    }
//                    return (in_array($v2, $v1) || $flag);
                    return $result;
                } else if ($v2arr) {
                    return false;
                } else {
                    return (strpos($v1, $v2) !== false);
                }
            case "![":
                return (strpos($v1, $v2) === false);
            case "=]":
                return (strpos($v1, $v2) === 0);
            case "[=":
                return (strpos($v1, $v2) === strlen($v1) - strlen($v2));
            default:
                $logger->error(__FUNCTION__ . " Unsupported relation operator " . $exp->rop);
                return false;
        }
    } else {
        return $v1;
    }
}

/*
function getObjectValue($data, $obj)
{
	global $logger;
	if(empty($data) || empty($obj)){
		$logger->error(__FUNCTION__." Empty data or object");
		return 0;
	}
	switch($obj->type){
	case "const":
		return $obj->value;
	case "var":
		return getVarValue($data, $obj);
	case "cal":
		return getCalResult($data, $obj);
	default:
		$logger->error(__FUNCTION__." Unsupported object type ".$obj->type);
		return 0;
	}
}

function getVarValue($data, $obj)
{
	global $logger;
	if(empty($data) || empty($obj) || empty($obj->props)){
		$logger->error(__FUNCTION__." Empty data or property");
		return 0;
	}
	$value = $data;
	foreach($obj->props as $prop)
	{
		$value = $value[$prop];
	}
	return $value;
}

function getCalResult($data, $obj)
{
	global $logger;
	if(empty($data) || empty($obj)){
		$logger->error(__FUNCTION__." Empty data or calculation");
		return 0;
	}
	$v1 = getObjectValue($data, $obj->arg1);
	$v2 = getObjectValue($data, $obj->arg2);
	switch($obj->cop){
	case "+":
		return $v1 + $v2;
	case "-":
		return $v1 - $v2;
	case "*":
		return $v1 * $v2;
	case "/":
		return $v1 / $v2;
	case "%":
		return $v1 % $v2;
	default:
		$logger->error(__FUNCTION__." Unsupported calculation operator ".$obj->cop);
		return 0;
	}
}

 */
function getCalResultNew($data, $obj)
{
    global $logger;
    if (empty($data) || empty($obj)) {
        $logger->error(__FUNCTION__ . " Empty data or calculation");
        return 0;
    }
    if (isset($obj->arg1) && isset($obj->arg2)) {
        $v1 = getDataValue($data, $obj->arg1);
        $v2 = getDataValue($data, $obj->arg2);
        switch ($obj->cop) {
            case "+":
                return $v1 + $v2;
            case "-":
                return $v1 - $v2;
            case "*":
                return $v1 * $v2;
            case "/":
                return $v1 / $v2;
            case "%":
                return $v1 % $v2;
            default:
                $logger->error(__FUNCTION__ . " Unsupported calculation operator " . $obj->cop);
                return 0;
        }
    } else {
        $v1 = getDataValue($data, $obj->arg1);
        return  $v1;
//        return getDataValue($data, $obj->arg1);

    }
}

function getDataValue($data, $arg)
{
    global $logger;
    if (isset($arg->constant)) {
        return $arg->constant;
    } else {
        $tbl = isset($arg->table) ? $arg->table : 0;
        $field = "";
        if (isset($arg->field)) {
            $field = $arg->field;
        } else {
            switch ($arg->column) {
                case 0:
                    $field = "text"; //第一列
                    break;
                case 1:
                    $field = "frq"; //第二列
                    break;
                default:
                    $field = $arg->column;
                    break;
            }
        }
        if (isset($arg->row)) {
            if (is_numeric($arg->row)) {
                return $data[$tbl]["datalist"][$arg->row][$field];
            } else {
                switch ($arg->row) {
                    case "totalnum": //记录个数
                        return count($data[$tbl]["datalist"]);
                        break;
                    case "all": //任意值
                        $tall = array();
                        foreach ($data[$tbl]["datalist"] as $li => $litem) {
                            if (isset($litem[$field])) {
                                if($field == "verified_type"){
                                    switch($litem[$field]['0']){
                                    case -1:
                                        $litem[$field] = "普通用户";
                                        break;
                                    case 0:
                                        $litem[$field] = "个人认证";
                                        break;
                                    case 1:
                                        $litem[$field] = "政府认证";
                                        break;
                                    case 2:
                                        $litem[$field] = "企业认证";
                                        break;
                                    case 3:
                                        $litem[$field] = "媒体认证";
                                        break;
                                    case 4:
                                        $litem[$field] = "校园认证";
                                        break;
                                    case 5:
                                        $litem[$field] = "网站认证";
                                        break;
                                    case 6:
                                        $litem[$field] = "团体认证";
                                        break;
                                    case 7:
                                        $litem[$field] = "机构认证";
                                        break;
                                    }
                                }
                                $lval = $litem[$field];
                                if (is_array($lval)) {
                                    $lval = implode(",", $litem[$field]);
                                }
                                $tall[] = $lval;
                            }
                        }
                        return $tall;
                        break;
                    case "count":
                        $tmparr = array();
                        foreach ($data[$tbl]["datalist"] as $li => $litem) {
                            if (isset($litem[$field])) {
                                $tmparr[] = $litem[$field];
                            }
                        }
                        return count($tmparr);
                        break;
                    case "sum":
                        $tmp = 0;
                        foreach ($data[$tbl]["datalist"] as $li => $litem) {
                            if (isset($litem[$field])) {
                                $tmp += (int)$litem[$field];
                            }
                        }
                        return $tmp;
                        break;
                    case "max":
                        $tmparr = array();
                        foreach ($data[$tbl]["datalist"] as $li => $litem) {
                            if (isset($litem[$field])) {
                                $tmparr[] = $litem[$field];
                            }
                        }
                        return max($tmparr);
                    case "min":
                        $tmparr = array();
                        foreach ($data[$tbl]["datalist"] as $li => $litem) {
                            if (isset($litem[$field])) {
                                $tmparr[] = $litem[$field];
                            }
                        }
                        return min($tmparr);
                    case "average":
                        $tmparr = array();
                        $tmp = 0;
                        foreach ($data[$tbl]["datalist"] as $li => $litem) {
                            if (isset($litem[$field])) {
                                $tmparr[] = $litem[$field];
                                $tmp += (int)$litem[$field];
                            }
                        }
                        return $tmp / count($tmparr);
                    default:
                        return $data[$tbl]["datalist"][$arg->row][$field];
                        break;
                }
            }
        }
    }
}

function doEventAction($event,$warn_data)
{
    global $logger;
    if (empty($event) || empty($event->action)) {
        return false;
    }
    switch ($event->action->type) {
        case "mail":
            return sendAlarmMail($event,$warn_data);
        default:
            $logger->error(__FUNCTION__ . " Unsupported action type " . $event->action->type);
            return false;
    }
}

function sendAlarmMail($event,$warn_data)
{
    global $logger;
    $send_res = send_mail($event,$warn_data);
    $res = json_decode($send_res);
    $res = (array)$res;
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the send res is:".var_export($res,true));
    if($res['message'] == "success"){
        $logger->info(__FUNCTION__.__FILE__.__LINE__."the email send ok~~~~");
        $ret = true;
    }else{
        $ret = false;
        $logger->error(__FUNCTION__.__FILE__.__LINE__."the send res is:".var_export($res,true));
    }
    return $ret;
}
function send_mail($event,$warn_data) {
    global $logger;
    $toStr = $event->action->to;
    $ccStr = empty($event->action->cc) ? "" : $event->action->cc;
    $bccStr = empty($event->action->bcc) ? "" : $event->action->bcc;

    $logger->info(__FUNCTION__.__FILE__.__LINE__." the toStr is:".var_export($toStr,true));
    $url = 'http://sendcloud.sohu.com/webapi/mail.send.json';

    $param = array(

        'api_user' => API_USER,

        'api_key' => API_KEY,

        'from' => ALARM_MAIL_FROM,

        'fromname' => ALARM_MAIL_FROMNAME,

        'to' => $toStr,

        'cc' => $ccStr,

        'bcc' => $bccStr,

        'subject' => $event->trigtext.$event->action->subject,

        'html' => $event->action->message."<br/><br/>下面是相关内容<br/>".$warn_data,

        'resp_email_id' => 'true');

    $data = http_build_query($param);
    $options = array(

        'http' => array(

            'method'  => 'POST',

            'header' => 'Content-Type: application/x-www-form-urlencoded',

            'content' => $data

        ));

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;

}

function saveEventHistory($instanceid, $elementid, $event, $triggertime, $status, $scheduleid)
{
    global $dsql, $logger;
    $sql = "insert into " . DATABASE_EVENT_HISTORY . " (instanceid,elementid,triggertime,sevtext,trigtext,action,status, scheduleid)" .
        " values ({$instanceid},{$elementid},{$triggertime},'{$event->sevtext}','{$event->trigtext}','{$event->action->type}',{$status},{$scheduleid})";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
        return false;
    }
    $dsql->FreeResult($qr);
    return true;
}

function deepClone(&$obj)
{
    if (is_object($obj)) {
        $copy = (object)array();
    } else if (is_array($obj)) {
        $copy = array();
    } else {
        return $obj;
    }

    if (empty($obj)) {
        return $copy;
    }

    foreach ($obj as $key => $val) {
        if (is_object($val) || (is_array($val))) {
            $copy->$key = unserialize(serialize($val));
        } else {
            $copy->$key = $val;
        }
    }
    return $copy;
}

function getBrowse()
{
    // The order of this array should NOT be changed. Many browsers return
    // multiple browser types so we want to identify the sub-type first.
    $browsers = array(
        'Flock' => 'Flock',
        'Chrome' => 'Chrome',
        'Opera' => 'Opera',
        'MSIE' => 'IE',
        'Internet Explorer' => 'IE',
        'Shiira' => 'Shiira',
        'Firefox' => 'Firefox',
        'Chimera' => 'Chimera',
        'Phoenix' => 'Phoenix',
        'Firebird' => 'Firebird',
        'Camino' => 'Camino',
        'Netscape' => 'Netscape',
        'OmniWeb' => 'OmniWeb',
        'Safari' => 'Safari',
        'Mozilla' => 'Mozilla',
        'Konqueror' => 'Konqueror',
        'icab' => 'iCab',
        'Lynx' => 'Lynx',
        'Links' => 'Links',
        'hotjava' => 'HotJava',
        'amaya' => 'Amaya',
        'IBrowse' => 'IBrowse'
    );
    global $_SERVER;
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
    $browser = "Unknown Browser";
    if (is_array($browsers) AND count($browsers) > 0) {
        foreach ($browsers as $key => $val) {
            if (preg_match("|" . preg_quote($key) . ".*?([0-9\.]+)|i", $agent, $match)) {
                $version = $match[1];
                $browser = $val;
                return $browser . ' ' . $version;
            }
        }
    }
    return $browser;
}

function getIP()
{
    global $_SERVER;
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } else if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function getOS()
{
    $platforms = array(
        'windows nt 6.2' => 'Win8',
        'windows nt 6.1' => 'Win7',
        'windows nt 6.0' => 'Win Longhorn',
        'windows nt 5.2' => 'Win2003',
        'windows nt 5.0' => 'Win2000',
        'windows nt 5.1' => 'WinXP',
        'windows nt 4.0' => 'Windows NT 4.0',
        'winnt4.0' => 'Windows NT 4.0',
        'winnt 4.0' => 'Windows NT',
        'winnt' => 'Windows NT',
        'windows 98' => 'Win98',
        'win98' => 'Win98',
        'windows 95' => 'Win95',
        'win95' => 'Win95',
        'windows' => 'Unknown Windows OS',
        'os x' => 'MacOS X',
        'ppc mac' => 'Power PC Mac',
        'freebsd' => 'FreeBSD',
        'ppc' => 'Macintosh',
        'linux' => 'Linux',
        'debian' => 'Debian',
        'sunos' => 'Sun Solaris',
        'beos' => 'BeOS',
        'apachebench' => 'ApacheBench',
        'aix' => 'AIX',
        'irix' => 'Irix',
        'osf' => 'DEC OSF',
        'hp-ux' => 'HP-UX',
        'netbsd' => 'NetBSD',
        'bsdi' => 'BSDi',
        'openbsd' => 'OpenBSD',
        'gnu' => 'GNU/Linux',
        'unix' => 'Unknown Unix OS'
    );
    global $_SERVER;
    $agent = empty($_SERVER['HTTP_USER_AGENT']) ? "" : $_SERVER['HTTP_USER_AGENT'];
    $os = "Unknown OS";
    if (is_array($platforms) AND count($platforms) > 0) {
        foreach ($platforms as $key => $val) {
            if (preg_match("|" . preg_quote($key) . "|i", $agent)) {
                $os = $val;
                return $os;
            }
        }
    }
    return $os;
}

/*
 * 插入评论
 */
/*function insert_comment($comments_info, $timeline_type, $sourceid)
{
    global $logger, $dsql, $task, $insertweibotime,$analysistime,$insertusertime,$newcount,$spiderusercount,$solrerrorcount,
    $user_ids;
    global $dictionaryPlan,$taskID;
	global $indirect_guid_query_conds;

	$result = array("result"=>true,"msg"=>"");

    if (!isset($user_ids))
    {
    	$user_ids = array();
    }
    //获取方案 如果任务id未空 是植入 就直接使用$dictionaryPlan，
	if($task->id!=NULL){
		//获取方案
		$taskID=$task->id;
//		if($taskID!=$tempid){
			//从数据库获取任务方案
			$dictionaryPlan=queryDictionaryPlan($taskID);
			//跟新任务id
//			$taskID=$tempid;
//		}
	}
    $solrdata = array();
    $start_time = microtime_float();
    $newcommentcount = 0;
    $newusercount = 0;
    $datacount = 0;
    $user_data = array();
	//$logger->debug("comment:".var_export($comments_info,true));
    foreach ($comments_info as $comment_info)
    {
        $sql = "select * from ".DATABASE_COMMENT." where id =".$comment_info['id'];
        $start_sql = microtime_float();
        $qr = $dsql->ExecQuery($sql);
        $end_sql = microtime_float();
        if (!$qr)
        {
            $logger->error("sql is ".$sql." mysql error is".$dsql->GetError());
			$result['result'] = false;
			$result['msg'] = '插入评论时sql出错：'.$sql;
			return $result;
            //return false;
        }
        $datacount++;
        if ($dsql->GetTotalRow($qr))
        {
        	continue;
        }
		$indirect_tmp = array();
        $sql_arr = array();
        $sql_arr['id'] = $comment_info['id'];
        $sql_arr['userid'] = $comment_info['user']['id'];
        $sql_arr['sourceid'] = $sourceid;
		$sql_arr['retweeted_status'] = $comment_info['status']['id'];
		$indirect_tmp['retweeted_status'] = $sql_arr['retweeted_status']; //原创id
		$indirect_tmp['id'] = $comment_info['id']; //自己的id

		//set guid
        $sql_arr['guid'] = $sql_arr['sourceid']."c_".$sql_arr['id'];

		$indirect_tmp['add_guid_ori'] = 1;
		$indirect_tmp['sourceid'] = isset($sql_arr['sourceid'])?$sql_arr['sourceid']:NULL;
		if(!isset($comment_info['source_host'])){
			$logger->error("缺少source_host");
			$result['result'] = false;
			$result['msg'] = '插入评论时，缺少source_host';
			return $result;
		}
		$indirect_tmp['source_host'] = $comment_info['source_host'];

		//father_guid为必填
		$indirect_tmp['add_guid_father'] = 1;
        if(isset($comment_info['reply_comment']['id'])){
        	//$sql_arr['father_guid'] = $sql_arr['sourceid']."c_".$comment_info['reply_comment']['id'];
			//$sql_arr['father_id'] = $comment_info['reply_comment']['id'];
			$indirect_tmp['father_id'] = $comment_info['reply_comment']['id'];
        }
		else{
			$indirect_tmp['father_id'] = $sql_arr['retweeted_status'];
		}

        $sql = insert_template(DATABASE_COMMENT, $sql_arr);
        $qr = $dsql->ExecQuery($sql);
        if(!$qr)
        {
            if ($dsql->GetErrorNo() == 1062)
            {
                $logger->debug("sql is ".$sql." mysql error is ".$dsql->GetError());
                continue;
            }
            else
            {
                $logger->error("sql is ".$sql." mysql error is ".$dsql->GetError());
                //$result = false;
				$result['result'] = false;
				$result['msg'] = '插入评论时，sql出错'.$sql;
                break;
            }
        }
        else{
            $newcommentcount++;
        }
        //生成sql语句后赋值
        if (isset($comment_info['mid']))
        {
            $sql_arr['mid'] = $comment_info['mid'];
        }
		//原创mid
        if (isset($comment_info['status']['mid'])){
        	$sql_arr['retweeted_mid'] = $comment_info['status']['mid'];
			$indirect_tmp['retweeted_mid'] = $comment_info['status']['mid'];
        }
		$indirect_guid_query_conds[] = $indirect_tmp;

        //if(!empty($sql_arr['retweeted_status'])){
      	    //$sql_arr['retweeted_guid'] = $sql_arr['sourceid']."_".$sql_arr['retweeted_status'];
        //}
        //else if(!empty($sql_arr['retweeted_mid'])){
      	    //$sql_arr['retweeted_guid'] = $sql_arr['sourceid']."m_".$sql_arr['retweeted_mid'];
        //}
        $tmp = isset($comment_info['created_at_ts']) ? $comment_info['created_at_ts'] : strtotime($comment_info['created_at']);
		$created_at = narrowToSolrInt($tmp);

        $sql_arr['created_at'] = $created_at;

        $sql_arr['created_year'] = date('Y', $created_at);
        $sql_arr['created_month'] = date('n', $created_at);
        $sql_arr['created_day'] = date('j', $created_at);
        $sql_arr['created_hour'] = date('G', $created_at);
        $sql_arr['created_weekday'] = date('N', $created_at);

		$area_arr = get_area_code_from_user($comment_info['user'],$sourceid);
		if(isset($area_arr['result']) && $area_arr['result']===false)
		{
			$result['result'] = false;
			$result['msg'] = $area_arr['msg'];
			return $result;
		}
        else if(!empty($area_arr))
        {
            foreach ($area_arr as $area_key => $area_value)
            {
                if ($area_value)
                {
                    $sql_arr[$area_key] = $area_value;
                }
            }
        }


		if(isset($comment_info['user']['screen_name'])){
			$sql_arr['screen_name'] = $comment_info['user']['screen_name'];
		}
		if(isset($comment_info['user']['gender'])){
			$sql_arr['sex'] = $comment_info['user']['gender'];
		}
        $sql_arr['description'] = isset($comment_info['user']['description']) ? $comment_info['user']['description'] : '';
        $sql_arr['verified_reason'] = isset($comment_info['user']['verified_reason']) ? $comment_info['user']['verified_reason'] : '';
        $vinfo = array();
        setVerified($vinfo, $comment_info['user']);//设置认证、认证类型
        $sql_arr['verify'] = $vinfo['verified'];
        $sql_arr['verified_type'] = isset($vinfo['verified_type']) ? $vinfo['verified_type'] : '';
        $sql_arr['timeline_type'] = $timeline_type;
        if (!empty($comment_info['source'])){
        	$sql_arr['source'] = strip_tags($comment_info['source']);
        }
        //$sql_arr['status_mid'] = $comment_info['status']['mid'];
        $sql_arr['text'] = $comment_info['text'];
        $sql_arr['content_type'] = 2;//评论
        $sql_arr['comments_count'] = 0;
        $sql_arr['direct_comments_count'] = 0;
        $sql_arr['repost_trend_cursor'] = 2;//默认一级评论
        $sql_arr['analysis_status'] = ANALYSIS_STATUS_NORMAL;
        $solrdata[] = $sql_arr;
        //如果评论的作者不在数据库中，需要将其插入
        if (array_search($comment_info['user']['id'], $user_ids) === false)
        {
        	$user_ids[] = $comment_info['user']['id'];
        	$user_data[] = $comment_info['user'];
        }
    }
	//$logger->debug("ind conds of comment:".var_export($indirect_guid_query_conds,true));
    $task->taskparams->scene->spider_statuscount += $datacount;
    $newcount += $newcommentcount;
    $end_time = microtime_float();
    $sqlinserttime = $end_time - $start_time;
    $insertweibotime += $sqlinserttime;//插入评论时间
    $start_time = microtime_float();
    $r_iu = insert_user($user_data, $timeline_type, $sourceid);
	if($r_iu['result'] === false)
	{
		$result['result'] = false;
		$result ['msg'] = $r_iu['msg'];
		return $result;
	}
    $end_time = microtime_float();
    $sqlinsertusertime = $end_time - $start_time;
    $insertusertime += $sqlinsertusertime;
    $newusercount = $r_iu['newcount'];
    $spiderusercount += $newusercount;
    $task->taskparams->scene->new_user_count += $newusercount;
    unset($user_data);
    if($newcommentcount > 0){
    	$tokenfields = array("text","description","verified_reason");
		$start_time = microtime_float();
		if(!empty($dictionaryPlan)){
			$dictionaryPlan=formatDictionaryPlan($dictionaryPlan);
		}
		$ana_result = solr_analysis($solrdata,$tokenfields,$dictionaryPlan,false);//分析微博
		$end_time = microtime_float();
		$analysistime += $end_time - $start_time;
		if(!$ana_result){
		    $solrerrorcount += $newcommentcount;
		    $task->taskparams->scene->solrerrorcount += $newcommentcount;
		    $newcommentcount = 0;
		    $logger->error(__FUNCTION__." solr_analysis failed:".var_export($ana_result,true));
		    deleteComment($solrdata, $sourceid);
			$result['result'] = false;
			$result['msg'] = '插入评论时，分词出错';
			return $result;
		    //return false;
		}
		else{
		    formatStoreData($solrdata, $ana_result,$tokenfields);//生成存储数据
		}
		$url = SOLR_URL_INSERT;
        $url .= "&commit=true";
        $start_time = microtime_float();
		$logger->info(__FUNCTION__." calls solr insert. commit: true");
        $tmp_result = handle_solr_data($solrdata, $url);
        $end_time = microtime_float();
        $solrtime = $end_time-$start_time;
        if($tmp_result === false){
            $logger->error(__FUNCTION__." 调用solr返回false data is : ".json_encode($solrdata));
            $solrerrorcount += $newcommentcount;
            $task->taskparams->scene->solrerrorcount += $newcommentcount;
            $newcommentcount = 0;
            deleteComment($solrdata, $sourceid);
            $strresult = "返回false";
        }
        else if($tmp_result === NULL){
            //$result = true;
            $strresult = "成功";
			if(supplyIndirectGuids()===false){
				$logger->error("补充guid失败");
				$result['result'] = false;
				$result['msg'] = '插入评论时，补充guid失败';
				//$result = false;
			}
        }
        else {
            $logger->error(__FUNCTION__." 调用solr失败的".var_export($tmp_result,true));
            $errorcount = count($tmp_result);
            $strresult = "失败{$errorcount}条";
            $solrerrorcount += $errorcount;
            $task->taskparams->scene->solrerrorcount += $errorcount;
            $newcommentcount -= $errorcount;
            if ($errorcount > 0){
            	$errorids = array();
	            foreach($tmp_result as $k=>$v){
	                $guid_arr = split('_', $v);
	                $errorids[] = $guid_arr[1];
	            }
	            if(!empty($errorids)){
	                deleteComment("'".implode("','",$errorids)."'",$sourceid);
	            }
            }
        }
        $logger->info(SELF."抓取{$datacount}条数据，插入数据库{$newcommentcount}条，花费{$sqlinserttime}。新增{$newusercount}个用户，花费{$sqlinsertusertime}。调用solr{$strresult},花费{$solrtime}");
    }
    else{
        $logger->warn(SELF." solrdata is empty");
        //$result = true;
    }
    $task->taskparams->scene->new_comments_count += $newcommentcount;

    return $result;
}*/

function deleteComment($comments, $sourceid)
{
    global $logger, $dsql;
    if (empty($comments)) {
        return false;
    }
    $wh = "";
    if (is_string($comments)) {
        $wh = " id in ({$comments})";
    } else {
        $ids = array();
        foreach ($comments as $comment) {
            if (!empty($comment['id'])) {
                $ids[] = $comment['id'];
            }
        }
        if (!empty($ids)) {
            $wh = " id in ('" . implode("','", $ids) . "')";
        } else {
            return false;
        }
    }
    $dsql->SelectDB(DATABASE_WEIBOINFO);
    $sql = "delete from " . DATABASE_COMMENT . " where {$wh} and sourceid = {$sourceid}";
    $qr = $dsql->ExecNoneQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
    }
}

/*
function set_comment_trend(&$task, $orig)
{
    global $logger, $dsql;
    $logger->debug("enter ".__FUNCTION__);
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $depth = 2;// level 1 comment depth
    while(1){
    	// all level 1 comments with children
        $sql = "select distinct a.father_guid as guid from ".DATABASE_COMMENT." a inner join ".DATABASE_COMMENT." b on a.father_guid = b.guid
        		where a.retweeted_status = '{$orig['id']}' and a.sourceid = {$task->taskparams->source} and b.father_guid is NULL
        		order by a.father_guid limit {$limitcursor},{$eachcount}";
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $logger->error(__FUNCTION__." - 获取评论异常sql:{$sql} - ".$dsql->GetError());
            $result = false;
            break;
        }
        else{
            $r_count = $dsql->GetTotalRow($qr);
            if($r_count == 0){
                break;
            }
            $comments = array();
            while($comment = $dsql->GetArray($qr)){
            	$cr = set_comment_father($task, $comment['guid'], $depth);
            	if($cr === false){
		            $logger->error(__FUNCTION__." - 更新评论{$comment['guid']}失败");
		            $result = false;
		            break 2;
		        }
                $sqlchk = "select count(0) as cnt from ".DATABASE_COMMENT." where father_guid = '{$comment['guid']}'";
                $qrchk = $dsql->ExecQuery($sqlchk);
                if($qrchk){
                    $rschk = $dsql->GetArray($qrchk);
                    $comment['direct_comments_count'] = $rschk['cnt'];
                    $comment['comments_count'] = $comment['direct_comments_count'] + $cr;
                    $comment['repost_trend_cursor'] = $depth;
                    $dsql->FreeResult($qrchk);
                    $comments[] = $comment;
                }
            }
            if(!empty($comments)){
            	$url = SOLR_URL_UPDATE;
	            $url .= "&commit=true";
				$logger->info(__FUNCTION__." calls solr update. commit: true");
	            $solr_r = handle_solr_data($comments,$url);
	            if($solr_r === false){
	                $result = false;
	                $logger->error(__FUNCTION__." 调用solr失败");
	                break;
	            }
	            else if($solr_r !== NULL && is_array($solr_r)){
	                $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
	                $logger->error(__FUNCTION__." SOLR 未找到的:".var_export($solr_r,true));
	            }
	            unset($comments);
            }
            if($r_count < $eachcount){
                break;
            }
            $limitcursor += $eachcount;
        }
        $dsql->FreeResult($qr);
    }
	//更新原创微博
    if($result){
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $logger->error(__FUNCTION__." - 获取评论异常sql:{$sql} - ".$dsql->GetError());
            $result = false;
            break;
        }
    	$sqlcnt = "select count(0) as cnt from ".DATABASE_COMMENT." where father_guid != ''
            and retweeted_status = '{$orig['id']}' and sourceid = {$task->taskparams->source}";
	    $qrcnt = $dsql->ExecQuery($sqlcnt);
	    if($qrcnt){
	        $rscnt = $dsql->GetArray($qrcnt);
	        $indirect = $rscnt['cnt'];
	        $dsql->FreeResult($qrcnt);
	        $upd = array();
	        $upd['guid'] = $orig['guid'];
	        $upd['direct_comments_count'] = $orig['comments_count'] - $indirect;
			if(!empty($orig['comments_count'])){
				$upd['comments_count'] = $orig['comments_count']; //最新的评论数更新到solr
			}
			if(!empty($orig['praises_count'])){
				$upd['praises_count'] = $orig['praises_count']; //最新的赞更新到solr
			}
	        $url = SOLR_URL_UPDATE;
		    $url .= "&commit=true";
			$logger->info(__FUNCTION__." calls solr update. commit: true");
		    $solr_r = handle_solr_data(array($upd),$url);
		    if($solr_r === false){
		        $result = false;
		        $logger->error(__FUNCTION__." 调用solr失败");
		        break;
		    }
		    else if($solr_r !== NULL && is_array($solr_r)){
		        $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
		        $logger->error(__FUNCTION__." SOLR 未找到的:".var_export($solr_r,true));
		    }
	    }
    }

    $logger->debug("exit ".__FUNCTION__);
    return $result;
}
 */

/*
function set_comment_father(&$task, $fatherid, $depth)
{
    global $logger, $dsql;
    $logger->debug("enter ".__FUNCTION__);
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $indirectcount = 0;
    while(1){
        $sql = "select guid from ".DATABASE_COMMENT." where father_guid = '{$fatherid}'
            order by guid limit {$limitcursor},{$eachcount}";
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $logger->error(__FUNCTION__." - 获取评论异常sql:{$sql} - ".$dsql->GetError());
            $result = false;
            break;
        }
        else{
            $r_count = $dsql->GetTotalRow($qr);
            if($r_count == 0){
                break;
            }
            $comments = array();
            while($comment = $dsql->GetArray($qr)){
            	$cr = set_comment_father($task, $comment['guid'], $depth+1);
            	if($cr === false){
		            $logger->error(__FUNCTION__." - 更新评论{$comment['guid']}失败");
		            $result = false;
		            break 2;
		        }
                $sqlchk = "select count(0) as cnt from ".DATABASE_COMMENT." where father_guid = '{$comment['guid']}'";
                $qrchk = $dsql->ExecQuery($sqlchk);
                if($qrchk){
                    $rschk = $dsql->GetArray($qrchk);
                    $comment['direct_comments_count'] = $rschk['cnt'];
                    $comment['comments_count'] = $comment['direct_comments_count'] + $cr;
                    $indirectcount += $comment['comments_count'];
                    $comment['repost_trend_cursor'] = $depth+1;
                    $dsql->FreeResult($qrchk);
                    $comments[] = $comment;
                }
            }
            if(!empty($comments)){
            	$url = SOLR_URL_UPDATE;
	            $url .= "&commit=true";
				$logger->info(__FUNCTION__." calls solr update. commit: true");
	            $solr_r = handle_solr_data($comments,$url);
	            if($solr_r === false){
	                $result = false;
	                $logger->error(__FUNCTION__." 调用solr失败");
	                break;
	            }
	            else if($solr_r !== NULL && is_array($solr_r)){
	                $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
	                $logger->error(__FUNCTION__." SOLR 未找到的:".var_export($solr_r,true));
	            }
	            unset($comments);
            }
            if($r_count < $eachcount){
                break;
            }
            $limitcursor += $eachcount;
        }
        $dsql->FreeResult($qr);
    }
    $logger->debug("exit ".__FUNCTION__);
    if($result){
    	return $indirectcount;
    }
    else{
    	return $result;
    }
}
 */

function getCommentsCount($father)
{
    global $dsql, $logger;
    $whfield = '';
    if (empty($father['id'])) {
        return false;
    }
    $sql = "select count(0) as cnt from " . DATABASE_COMMENT . " where retweeted_status = '{$father['id']}'";
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        $logger->error(__FUNCTION__ . " sql is {$sql} has error:" . $dsql->GetError());
        return false;
    } else {
        $rs = $dsql->GetArray($qr);
        if (!empty($rs)) {
            $exists_count = $rs['cnt'];
            return $exists_count;
        } else {
            return 0;
        }
    }
}

/*更新帖子的评论数,直接评论数*/
/*
function updateFollowPostTrend($oriarr, &$taskobj=NULL){
	global $logger;
	$logger->debug(__FILE__.__FUNCTION__.__LINE__." data ".var_export($oriarr, true));
	$result = array('result' => true, 'msg' => '');
	foreach($oriarr as $oi=>$oitem){
		$s_time = microtime_float();
		$r = set_followpost_trend($oitem,$taskobj);
		$e_time = microtime_float();
		$setcountstimediff = $e_time - $s_time;
		$logger->debug(__FILE__.__LINE__." - 计算总评论数、直接评论数完毕，花费{$setcountstimediff}");
		if($r == false){
			$result['result'] = false;
			$result['msg'] = '更新评论数失败';
			return $result;
		}
	}
	return $result;
}
 */
/*
 * 计算一条原创或转发的评论轨迹*/
function set_followpost_trend($origitem, &$task = NULL)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $depth = 2;// level 1 comment depth
    $orig = (object)$origitem;
    while (1) {
        //原创信息
        $conds['sourceid'] = isset($orig->sourceid) ? $orig->sourceid : NULL;
        $conds['source_host'] = isset($orig->source_host) ? $orig->source_host : NULL;
        $conds['original_url'] = isset($orig->original_url) ? $orig->original_url : NULL;
        $conds['floor'] = isset($orig->floor) ? $orig->floor : NULL;
        $conds['paragraphid'] = isset($orig->paragraphid) ? $orig->paragraphid : NULL;
        $conds['mid'] = isset($orig->mid) ? $orig->mid : NULL;
        $conds['id'] = isset($orig->id) ? $orig->id : NULL;
        //微博评论任务中存微博id
        $conds['id'] = isset($orig->orig) ? $orig->orig : NULL;
        $oriarticle = getArticleGuidOrMore($conds, true);
        $guid = $oriarticle['guid'];
        //补充原创的comments_count字段,直接评论数由此得到
        if (isset($oriarticle['comments_count'])) {
            $orig->comments_count = $oriarticle['comments_count'];
        } else {
            $logger->error(__FILE__ . __LINE__ . "原创无comments_count, 请更新原创." . var_export($oriarticle, true));
            $result = false;
            break;
        }

        //需要根据原创查出
        $qr = solr_select_conds(array('guid', 'comments_count', 'direct_comments_count', 'repost_trend_cursor', 'father_guid'), "father_guid:" . $guid . "", $limitcursor, $eachcount, "father_guid+asc");
        //$logger->debug(__FILE__.__LINE__." set_followpost_trend qr ".var_export($qr, true));
        if ($qr === false) {
            $logger->error(__FUNCTION__ . " - 获取评论异常:");
            $result = false;
            break;
        } else {
            $r_count = count($qr);
            if ($r_count == 0) {
                break;
            }
            $comments = array();
            foreach ($qr as $qi => $comment) {
                $cr = set_followpost_father($comment['guid'], $depth, $task);
                if ($cr === false) {
                    $logger->error(__FUNCTION__ . " - 更新评论{$comment['guid']}失败");
                    $result = false;
                    break 2;
                }
                $cnt = solr_select_conds(array(), "father_guid:" . $comment['guid'] . "", 0, 0);
                $logger->debug(__FILE__ . __LINE__ . " set_followpost_trend comment " . var_export($comment, true));
                $comment['direct_comments_count'] = $cnt === false ? 0 : $cnt;
                $comment['comments_count'] = $comment['direct_comments_count'] + $cr;
                $comment['repost_trend_cursor'] = $depth;
                $logger->debug(__FILE__ . __LINE__ . " set_followpost_trend after comment " . var_export($comment, true));
                $comments[] = $comment;
            }
            if (!empty($comments)) {
                $url = SOLR_URL_UPDATE;
                $url .= "&commit=true";
                $logger->info(__FUNCTION__ . " calls solr update. commit: true");
                $solr_r = handle_solr_data($comments, $url);
                if ($solr_r === false) {
                    $result = false;
                    $logger->error(__FUNCTION__ . " 调用solr失败");
                    break;
                } else if ($solr_r !== NULL && is_array($solr_r)) {
                    if (!empty($task)) {
                        if ($task->task == TASK_COMMON) {
                            $task->taskparams->root->runTimeParam->scene->calc_solrerrorcount += count($solr_r);
                        } else {
                            $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
                        }
                    }
                    $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
                }
                unset($comments);
            }
            if ($r_count < $eachcount) {
                break;
            }
            $limitcursor += $eachcount;
        }
    }
    //更新原创微博
    if ($result) {
        $indirect = solr_select_conds(array(), "retweeted_guid:" . $guid . "+AND+father_guid:*+AND+!father_guid:" . $guid . "", 0, 0);
        $upd = array();
        $upd['guid'] = $guid;
        $upd['direct_comments_count'] = $orig->comments_count - $indirect;
        if (!empty($orig->comments_count)) {
            $upd['comments_count'] = $orig->comments_count; //最新的评论数更新到solr
        }
        $url = SOLR_URL_UPDATE;
        $url .= "&commit=true";
        $logger->info(__FUNCTION__ . " calls solr update. commit: true");
        $solr_r = handle_solr_data(array($upd), $url);
        if ($solr_r === false) {
            $result = false;
            $logger->error(__FUNCTION__ . " 调用solr失败");
            //break;
        } else if ($solr_r !== NULL && is_array($solr_r)) {
            if (!empty($task)) {
                if ($task->task == 20) {
                    $task->taskparams->root->runTimeParam->scene->calc_solrerrorcount += count($solr_r);
                } else {
                    $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
//                    $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
                }
            }
            $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
        }
    }

    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

function set_followpost_father($fatherid, $depth, &$task = NULL)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $indirectcount = 0;
    while (1) {
        $qr = solr_select_conds(array('guid', 'comments_count', 'direct_comments_count', 'repost_trend_cursor'), "father_guid:" . $fatherid . "", $limitcursor, $eachcount, "guid+asc");
        if ($qr === false) {
            $logger->error(__FUNCTION__ . " - 获取评论异常: - ");
            $result = false;
            break;
        } else {
            $r_count = count($qr);
            $logger->debug(__FILE__ . __LINE__ . " r_count " . var_export($r_count, true));
            if ($r_count == 0) {
                break;
            }
            $comments = array();
            foreach ($qr as $qi => $comment) {
                $cr = set_followpost_father($comment['guid'], $depth + 1, $task);
                if ($cr === false) {
                    $logger->error(__FUNCTION__ . " - 更新评论{$comment['guid']}失败");
                    $result = false;
                    break 2;
                }
                $cnt = solr_select_conds(array(), "father_guid:" . $comment['guid'] . "", 0, 0);
                if ($cnt === false) {
                    $logger->error(__FUNCTION__ . " - 获取评论异常: - ");
                    $result = false;
                    break;
                }

                $logger->debug(__FILE__ . __LINE__ . "set_followpost_father " . var_export($comment, true));
                $comment['direct_comments_count'] = $cnt === false ? 0 : $cnt;
                $comment['comments_count'] = $comment['direct_comments_count'] + $cr;
                $indirectcount += $comment['comments_count'];
                $comment['repost_trend_cursor'] = $depth + 1;
                $logger->debug(__FILE__ . __LINE__ . "set_followpost_father after " . var_export($comment, true));
                $comments[] = $comment;
            }
            if (!empty($comments)) {
                $url = SOLR_URL_UPDATE;
                $url .= "&commit=true";
                $logger->info(__FUNCTION__ . " calls solr update. commit: true");
                $solr_r = handle_solr_data($comments, $url);
                if ($solr_r === false) {
                    $result = false;
                    $logger->error(__FUNCTION__ . " 调用solr失败");
                    break;
                } else if ($solr_r !== NULL && is_array($solr_r)) {
                    if (!empty($task)) {
                        $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
                    }
                    $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
                }
                unset($comments);
            }
            if ($r_count < $eachcount) {
                break;
            }
            $limitcursor += $eachcount;
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    if ($result) {
        return $indirectcount;
    } else {
        return $result;
    }
}

/* 计算一条原创的转发轨迹
 * 微博转发任务结束时也可以调用这个函数计算轨迹,
 * 第三方调用此函数计算转发轨迹,但要需要在提供父级mid, reply_father_mid字段
 * 现在微博转发任务在抓取过程中已经计算过转发轨迹,不需要调用
 * set_repost_trend()和set_followpost_trend() 只是更新的字段不同,逻辑基本相同, 可以考虑合并,
 * 合并后, 对应微博转发任务需要改成抓取过程中只记录父级mid,最后计算转发轨迹,
 * 也就是微博转发任务updateRepostTrend(),计算转发轨迹set_repost_trend(),计算评论轨迹set_followpost_trend()可以统一成一个函数.
 *
 * */
function set_repost_trend($origitem, &$task = NULL)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $depth = 2;// level 1 repost depth
    $orig = (object)$origitem;
    while (1) {
        //原创信息
        $conds['sourceid'] = isset($orig->sourceid) ? $orig->sourceid : NULL;
        $conds['source_host'] = isset($orig->source_host) ? $orig->source_host : NULL;
        $conds['original_url'] = isset($orig->original_url) ? $orig->original_url : NULL;
        $conds['floor'] = isset($orig->floor) ? $orig->floor : NULL;
        $conds['paragraphid'] = isset($orig->paragraphid) ? $orig->paragraphid : NULL;
        $conds['mid'] = isset($orig->mid) ? $orig->mid : NULL;
        $conds['id'] = isset($orig->id) ? $orig->id : NULL;
        //微博评论任务中存微博id
        $conds['id'] = isset($orig->orig) ? $orig->orig : NULL;
        $oriarticle = getArticleGuidOrMore($conds, true);
        $guid = $oriarticle['guid'];
        //记录原创的reposts_count字段,用于计算原创的直接转发数
        if (isset($oriarticle['reposts_count'])) {
            $orig->reposts_count = $oriarticle['reposts_count'];
            $orig->followers_count = $oriarticle['followers_count'];
        } else {
            $logger->error(__FILE__ . __LINE__ . "原创无reposts_count, 请更新原创." . var_export($oriarticle, true));
            $result = false;
            break;
        }

        //需要根据原创查出所有的转发
        $qr = solr_select_conds(array('guid', 'reposts_count', 'direct_reposts_count', 'followers_count', 'total_reach_count', 'repost_trend_cursor', 'father_guid'), "father_guid:" . $guid . "+AND+content_type:1", $limitcursor, $eachcount, "father_guid+asc");
        $logger->debug(__FILE__ . __LINE__ . " set_followpost_trend qr " . var_export($qr, true));
        if ($qr === false) {
            $logger->error(__FUNCTION__ . " - 获取转发异常:");
            $result = false;
            break;
        } else {
            $r_count = count($qr);
            if ($r_count == 0) {
                break;
            }
            $articles = array();
            foreach ($qr as $qi => $article) {
                $cr = set_repost_father($article['guid'], $depth, $task);
                if ($cr === false) {
                    $logger->error(__FUNCTION__ . " - 更新评论{$article['guid']}失败");
                    $result = false;
                    break 2;
                }
                $cnt = solr_select_conds(array(), "father_guid:" . $article['guid'] . "", 0, 0);
                $logger->debug(__FILE__ . __LINE__ . " set_followpost_trend article" . var_export($article, true));
                $article['direct_reposts_count'] = $cnt === false ? 0 : $cnt;
                $article['reposts_count'] = $article['direct_reposts_count'] + $cr['indirectcount'];
                $article['total_reach_count'] = $article['followers_count'] + $cr['indirect_reach_count'];
                $article['repost_trend_cursor'] = $depth;
                $logger->debug(__FILE__ . __LINE__ . " set_followpost_trend after article" . var_export($article, true));
                $articles[] = $article;
            }
            if (!empty($articles)) {
                $url = SOLR_URL_UPDATE;
                $url .= "&commit=true";
                $logger->info(__FUNCTION__ . " calls solr update. commit: true");
                $solr_r = handle_solr_data($articles, $url);
                if ($solr_r === false) {
                    $result = false;
                    $logger->error(__FUNCTION__ . " 调用solr失败");
                    break;
                } else if ($solr_r !== NULL && is_array($solr_r)) {
                    if (!empty($task)) {
                        $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
                    }
                    $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
                }
                unset($articles);
            }
            if ($r_count < $eachcount) {
                break;
            }
            $limitcursor += $eachcount;
        }
    }
    //更新原创微博
    if ($result) {
        //间接转发的个数
        $indirect = solr_select_conds(array(), "retweeted_guid:" . $guid . "+AND+father_guid:*+AND+!father_guid:" . $guid . "", 0, 0);
        $upd = array();
        $upd['guid'] = $guid;
        $upd['direct_reposts_count'] = $orig->reposts_count - $indirect;
        if (!empty($orig->reposts_count)) {
            $upd['reposts_count'] = $orig->reposts_count; //最新的转发数更新到solr
        }
        //计算总到达数
        $qlevel1 = solr_select_conds(array('total_reach_count'), 'father_guid:' . $guid . '', 0, (pow(2, 31) - 1));
        $totalreachcount = 0;
        foreach ($qlevel1 as $qi => $qitem) {
            if (isset($qitem['total_reach_count'])) {
                $totalreachcount += $qitem['total_reach_count'];
            }
        }
        $upd['total_reach_count'] = $orig->followers_count + $totalreachcount;
        $url = SOLR_URL_UPDATE;
        $url .= "&commit=true";
        $logger->info(__FUNCTION__ . " calls solr update. commit: true");
        $solr_r = handle_solr_data(array($upd), $url);
        if ($solr_r === false) {
            $result = false;
            $logger->error(__FUNCTION__ . " 调用solr失败");
            break;
        } else if ($solr_r !== NULL && is_array($solr_r)) {
            if (!empty($task)) {
                $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
            }
            $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
        }
    }

    $logger->debug("exit " . __FUNCTION__);
    return $result;
}

/*更新转发对应的级别关系*/
function set_repost_father($fatherid, $depth, &$task = NULL)
{
    global $logger;
    $logger->debug("enter " . __FUNCTION__);
    $eachcount = 100;
    $limitcursor = 0;
    $result = true;
    $indirectcount = 0;
    $retobj = array();
    $retobj['indirectcount'] = 0;
    $retobj['indirect_reach_count'] = 0;
    while (1) {
        $qr = solr_select_conds(array('guid', 'comments_count', 'direct_comments_count', 'repost_trend_cursor', 'followers_count', 'reposts_count', 'direct_reposts_count', 'total_reach_count', 'total_reposts_count'), "father_guid:" . $fatherid . "+AND+content_type:1", $limitcursor, $eachcount, "guid+asc");
        if ($qr === false) {
            $logger->error(__FUNCTION__ . " - 获取文章异常: - ");
            $result = false;
            break;
        } else {
            $r_count = count($qr);
            $logger->debug(__FILE__ . __LINE__ . " r_count " . var_export($r_count, true));
            if ($r_count == 0) {
                break;
            }
            $articles = array();
            foreach ($qr as $qi => $article) {
                $cr = set_repost_father($article['guid'], $depth + 1, $task);
                if ($cr === false) {
                    $logger->error(__FUNCTION__ . " - 更新评论{$article['guid']}失败");
                    $result = false;
                    break 2;
                }
                $cnt = solr_select_conds(array(), "father_guid:" . $article['guid'] . "", 0, 0);
                if ($cnt === false) {
                    $logger->error(__FUNCTION__ . " - 获取评论异常: - ");
                    $result = false;
                    break;
                }
                $logger->debug(__FILE__ . __LINE__ . "set_repost_father" . var_export($article, true));
                $article['direct_reposts_count'] = $cnt === false ? 0 : $cnt;
                $article['total_reposts_count'] = $article['direct_reposts_count'] + $cr['indirectcount'];
                $article['total_reach_count'] = $article['followers_count'] + $cr['indirect_reach_count'];
                $retobj['indirect_reach_count'] += $article['followers_count'];
                $retobj['indirectcount'] += $article['reposts_count'];
                $article['repost_trend_cursor'] = $depth + 1;
                $logger->debug(__FILE__ . __LINE__ . "set_repost_father after " . var_export($article, true));
                $articles[] = $article;
            }
            if (!empty($articles)) {
                $url = SOLR_URL_UPDATE;
                $url .= "&commit=true";
                $logger->info(__FUNCTION__ . " calls solr update. commit: true");
                $solr_r = handle_solr_data($articles, $url);
                if ($solr_r === false) {
                    $result = false;
                    $logger->error(__FUNCTION__ . " 调用solr失败");
                    break;
                } else if ($solr_r !== NULL && is_array($solr_r)) {
                    if (!empty($task)) {
                        $task->taskparams->scene->calc_solrerrorcount += count($solr_r);
                    }
                    $logger->error(__FUNCTION__ . " SOLR 未找到的:" . var_export($solr_r, true));
                }
                unset($articles);
            }
            if ($r_count < $eachcount) {
                break;
            }
            $limitcursor += $eachcount;
        }
    }
    $logger->debug("exit " . __FUNCTION__);
    if ($result) {
        return $retobj;
    } else {
        return $result;
    }
}

/*
function updateCommentTrend(&$taskobj, $origin){
	global $logger,$dsql;
	$result = array('result' => true, 'msg' => '');
	$comment = $taskobj->taskparams->comment[$origin];
	if(empty($comment)){
		$result['result'] = false;
		$result['msg'] = '评论微博空';
		return $result;
	}
	$res = getWeiboById($taskobj->taskparams->source, $comment->orig, $taskobj->taskparams->isseed);
	if ($res['result'] == false){
		$result['result'] = false;
		$result['msg'] = '获取微博失败';
		return $result;
	}
	$orig = $res['weibo'];
	$s_time = microtime_float();
	$r = set_comment_trend($taskobj, $orig);
	$e_time = microtime_float();
	$setcountstimediff = $e_time - $s_time;
	$logger->info(SELF." - 计算总评论数、直接评论数完毕，花费{$setcountstimediff}");
	if ($r == false){
		$result['result'] = false;
		$result['msg'] = '更新评论数失败';
		return $result;
	}
	return $result;
}
 */
/*
 * 计算轨迹的的接口函数
 * $trendtype 轨迹类型,转发轨迹:repost_trend,评论轨迹comment_trend
 * $taskobj 任务参数
 * $oriarr 要计算轨迹的源, 微博的转发轨迹是原创, 微博的评论轨迹可以是原创或转发, 论坛的评论轨迹是主贴
 * $needfather_guid, 是否需要更新father_guid, 微博的评论是从新到旧的抓取,未补充father_guid, 算轨迹前需要先赋值
 * */
function calcTrendPath($trendtype, $oriarr, $needfather_guid, &$taskobj = NULL)
{
    global $logger;
    $logger->debug(__FILE__ . __LINE__ . " enter " . __FUNCTION__ . var_export($oriarr, true));
    $result = array('result' => true, 'msg' => '');
    $errorflag = true;
    $erroritems = array();
    //多条源文章
    foreach ($oriarr as $oi => $oitem) {
        if (!is_object($oitem)) {
            $oitem = (object)$oitem;
        }
        if ($needfather_guid) {
            $conds['sourceid'] = isset($oitem->sourceid) ? $oitem->sourceid : NULL;
            $conds['source_host'] = isset($oitem->source_host) ? $oitem->source_host : NULL;
            $conds['original_url'] = isset($oitem->original_url) ? $oitem->original_url : NULL;
            $conds['floor'] = isset($oitem->floor) ? $oitem->floor : NULL;
            $conds['paragraphid'] = isset($oitem->paragraphid) ? $oitem->paragraphid : NULL;
            $conds['mid'] = isset($oitem->mid) ? $oitem->mid : NULL;
            $conds['id'] = isset($oitem->id) ? $oitem->id : NULL;
            //微博评论任务中存微博id
            $conds['id'] = isset($oitem->orig) ? $oitem->orig : NULL;

            $guid = getArticleGuidOrMore($conds);
            $qr = solr_select_conds(array(), 'retweeted_guid:' . $guid . '', 0, (pow(2, 31) - 1));
            if ($qr === false) {
                $logger->debug(__FILE__ . __LINE__ . " 从solr去数据出错 " . var_export($conds, true));
            } else {
                $solr_r = addweibo(NULL, $qr, 0, 'show_status', false);
                if ($solr_r['result'] !== true) {
                    $result['result'] = false;
                    $result['msg'] = "新增微博异常";
                    return $result;
                }
            }
        }
        if ($trendtype == "repost_trend") {
            $s_time = microtime_float();
            $r = set_repost_trend($oitem, $taskobj);
            $e_time = microtime_float();
            $setcountstimediff = $e_time - $s_time;
            $logger->debug(__FILE__ . __LINE__ . " - 计算" . var_export($oitem, true) . "完毕，花费{$setcountstimediff}");
        } else if ($trendtype == "comment_trend") {
            $s_time = microtime_float();
            $r = set_followpost_trend($oitem, $taskobj);
            $e_time = microtime_float();
            $setcountstimediff = $e_time - $s_time;
            $logger->debug(__FILE__ . __LINE__ . " - 计算" . var_export($oitem, true) . "完毕，花费{$setcountstimediff}");
        }
        if ($r == false) {
            $oriobj = (object)$oitem;
            $erroritem = '';
            if (isset($oriobj->guid)) {
                $erroritem = $oriobj->guid;
            } else if (isset($oriobj->original_url)) {
                $erroritem = $oriobj->original_url;
            } else if (isset($oriobj->mid)) {
                $erroritem = $oriobj->mid;
            } else if (isset($oriobj->id)) {
                $erroritem = $oriobj->id;
            } else if (isset($oriobj->orig)) {
                $erroritem = $oriobj->orig;
            } else {
                $erroritem = '未知的源';
            }
            $errorflag = false;
            $erroritems[] = $erroritem;
            $logger->debug(__FILE__ . __LINE__ . " - 计算" . var_export($oitem, true) . "失败");
        }
    }
    if ($errorflag) {
        $result['msg'] = '计算轨迹成功';
    } else {
        $result['result'] = false;
        $result['msg'] = '计算轨迹' . implode(',', $erroritems) . ' 失败';
    }
    return $result;
}

function getcomment($source, $cids)
{
    global $logger, $dsql, $oAuthThird, $task, $res_machine, $res_ip, $res_acc;
    $result = array("result" => true, "msg" => "");
    try {
        if (empty($cids)) {
            return $result;
        }
        $sqlsel = "select id from " . DATABASE_COMMENT . " where sourceid = {$source} and id in ('" . implode("','", $cids) . "')";
        $qr = $dsql->ExecQuery($sqlsel);
        if (!$qr) {
            $result['result'] = false;
            $result['msg'] = "sql error:" . $dsql->GetError();
            $logger->error(SELF . ' sql :' . $sqlsel . ' error: ' . $dsql->GetError());
        } else {
            $existids = array();
            while ($rec = $dsql->GetArray($qr)) {
                $existids[] = $rec['id'];
            }
            if (!empty($existids)) {
                $result['existids'] = $existids;
                $cids = array_merge(array_diff($cids, $existids));
            }
        }
        if (empty($cids)) {
            return $result;
        }
        $task->tasksource = $source;
        $task->taskparams->source = $source;
        getAllConcurrentRes($task, $res_machine, $res_ip, $res_acc);
        if ($task->taskparams->scene->state == SCENE_NORMAL) {
            if ($result['result'] !== false) {
                $result = comments_show_batch($cids);
            }
        } else {//无资源
            $result['result'] = false;
            $result['nores'] = true;
            $result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
        }
    } catch (Exception $e) {
        $result['result'] = false;
        $result['msg'] = $e->getMessage();
    }
    $task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
    myReleaseResource($task, $res_machine, $res_ip, $res_acc);
    return $result;
}

function myEncrypt($input)
{
    // NOTE: keep consistent with spider account decription method
    global $logger;
    $keys = str_split(ENCRYPT_KEY);
    $inputs = str_split($input);
    $output = "";
    foreach ($inputs as $ch) {
        foreach ($keys as $key) {
            $ch = $ch ^ $key;
        }
        $output .= $ch;
    }
    return $output;
}

function myDecrypt($input)
{
    global $logger;
    $keys = str_split(strrev(ENCRYPT_KEY));
    $inputs = str_split($input);
    $output = "";
    foreach ($inputs as $ch) {
        foreach ($keys as $key) {
            $ch = $ch ^ $key;
        }
        $output .= $ch;
    }
    return $output;
}

function getLimitValue($datatype, $limititem)
{
    $value;
    switch ($datatype) {
        case "blur_value_object":
        case "value_text_object":
            $value = is_array($limititem) ? $limititem["value"]["value"] : $limititem->value->value;
            break;
        default:
            $value = is_array($limititem) ? $limititem["value"] : $limititem->value;
            break;
    }
    return $value;
}

//得到两个对象数组的交集和并集, 每个对象的值属性为value
function getIntersectionAndUnion($arr1, $arr2, $datatype = "int")
{
    global $logger;
    $result = array();
    $tmpunion = array();//并集
    $tmpintersect = array(); //交集
    if (!empty($arr1) && !empty($arr2)) {
        foreach ($arr1 as $ni => $nitem) {
            if (!in_array($nitem, $tmpunion)) {
                $tmpunion[] = $nitem;
            }
            foreach ($arr2 as $ai => $aitem) {
                if (getLimitValue($datatype, $nitem) == getLimitValue($datatype, $aitem)) { //相等时,值相同,为交集
                    $tmpintersect[] = $aitem;
                }
                if (!in_array($aitem, $tmpunion)) {
                    $tmpunion[] = $aitem;
                }
            }
        }
    } else if (!empty($arr1) || !empty($arr2)) {
        if (!empty($arr1)) {
            $tmpintersect = $arr1;
        } else if (!empty($arr2)) {
            $tmpintersect = $arr2;
        }
        //limit为空表示不限制
        $tmpunion = array();
    }
    $union = array();
    foreach ($tmpunion as $ti => $titem) {
        if (count($union) > 0) {
            $has = false;
            foreach ($union as $ui => $uitem) {
                if (getLimitValue($datatype, $titem) == getLimitValue($datatype, $uitem)) { //相等时,值相同,为交集
                    $has = true;
                    break;
                }
            }
            if (!$has) {
                $union[] = $titem;
            }
        } else {
            $union[] = $titem;
        }
    }
    $result["union"] = $union;
    $result["intersect"] = $tmpintersect;
    return $result;
}

//获取gaprange类型值,返回value * gap
function getGapRangeValue($rValue, $gap)
{
    $retVal = $rValue;
    switch ($gap) {
        case "year":
            $retVal = $rValue * 365;
            break;
        case "month":
            $retVal = $rValue * 365 / 12;
            break;
        default:
            break;
    }
    return $retVal;
}

//获取time_dynamic_state类型的值
function getTimeDynamicStateValue($fieldvalue)
{
    if (isset($fieldvalue["start"])) {
        $start = $fieldvalue["start"];
    } else {
        $start = 0;
    }
    if (isset($fieldvalue["startgap"])) {
        $startgap = $fieldvalue["startgap"];
    } else {
        $startgap = "second";
    }
    if (isset($fieldvalue["end"])) {
        $end = $fieldvalue["end"];
    } else {
        $end = 0;
    }
    if (isset($fieldvalue["endgap"])) {
        $endgap = $fieldvalue["endgap"];
    } else {
        $endgap = "second";
    }
    if (isset($fieldvalue["datestate"])) {
        $datestate = $fieldvalue["datestate"];
    } else {
        $datestate = "";
    }
    if (isset($fieldvalue["timestate"])) {
        $timestate = $fieldvalue["timestate"];
    } else {
        $timestate = "";
    }
    return getRangeStateTime($start, $startgap, $end, $endgap, $datestate, $timestate);
}

//当为range类型是可能会为数组,经过时间段合并都会转为range类型,limit没有交集时会存为多段
function getTimeFieldMaxMin($item, $tmpJson)
{
    global $logger;
    $retArr = array();
    $ret = array("maxvalue" => null, "minvalue" => null);
    if ($item != "") {
        switch ($tmpJson["filter"][$item]["datatype"]) {
            case "time_dynamic_range":
                $nsv = $tmpJson["filter"][$item]["limit"][0]["value"];
                $ret["minvalue"] = sinceToThis($nsv["start"]["start"], $nsv["start"]["startgap"], $nsv["start"]["startthisgap"], $nsv["start"]["startto"], $nsv["start"]["starttogap"], "beginning");
                $ret["maxvalue"] = sinceToThis($nsv["end"]["end"], $nsv["end"]["endgap"], $nsv["end"]["endthisgap"], $nsv["end"]["endto"], $nsv["end"]["endtogap"], "ending");
                $retArr[] = $ret;
                break;
            case "time_dynamic_state":
                $njv = $tmpJson["filter"][$item]["limit"][0]["value"];
                $njvTime = getTimeDynamicStateValue($njv);
                $ret["minvalue"] = $njvTime["startpoint"];
                $ret["maxvalue"] = $njvTime["endpoint"];
                $retArr[] = $ret;
                break;
            case "range":
                foreach ($tmpJson["filter"][$item]["limit"] as $ti => $titem) {
                    $ret["maxvalue"] = $titem["value"]["maxvalue"];
                    $ret["minvalue"] = $titem["value"]["minvalue"];
                    $retArr[] = $ret;
                }
                break;
        }
    }
    return $retArr;
}

function getControlOpt($mergetype, &$newJsonCtrl, $authJsonCtrl)
{
    switch ($mergetype) {
        case 1:
            //account_rule对应字段json赋值给新json
            $newJsonCtrl["allowcontrol"] = $authJsonCtrl["allowcontrol"];
            break;
        case 2:
            $newJsonCtrl["allowcontrol"] = max($newJsonCtrl["allowcontrol"], $authJsonCtrl["allowcontrol"]);
            break;
        case 3:
            $newJsonCtrl["allowcontrol"] = min($newJsonCtrl["allowcontrol"], $authJsonCtrl["allowcontrol"]);
            break;
        default:
            $newJsonCtrl['allowcontrol'] = $authJsonCtrl['allowcontrol'];
            $newJsonCtrl['maxlimitlength'] = $authJsonCtrl['maxlimitlength'];
            $newJsonCtrl['limitcontrol'] = $authJsonCtrl['limitcontrol'];
            $newJsonCtrl['unitprice'] = $authJsonCtrl['unitprice'];
            $newJsonCtrl['maxprice'] = $authJsonCtrl['maxprice'];
            $newJsonCtrl['onceeditprice'] = $authJsonCtrl['onceeditprice'];
            $newJsonCtrl['maxeditprice'] = $authJsonCtrl['maxeditprice'];
            break;
    }
}

function setLimitMaxMin($max, $min)
{
    $tmplimit = array();
    $tmplimit["value"]["maxvalue"] = $max;
    $tmplimit["value"]["minvalue"] = $min;
    return $tmplimit;
}

function getRangeTypeMergeLimit($mergetype, $newJsonLimit, $authJsonLimit, $datatype = "range")
{
    global $logger;
    if (count($newJsonLimit) > 0 && count($authJsonLimit) > 0) {
        $allNewLimit = array();
        foreach ($newJsonLimit as $ni => $nitem) {
            foreach ($authJsonLimit as $ai => $aitem) {
                $newJsonValue = $newJsonLimit[$ni]["value"];
                $njMax = isset($newJsonValue["maxvalue"]) ? $newJsonValue["maxvalue"] : null;
                $njMin = isset($newJsonValue["minvalue"]) ? $newJsonValue["minvalue"] : null;

                $authJsonValue = $authJsonLimit[$ai]["value"];
                $ajMax = isset($authJsonValue["maxvalue"]) ? $authJsonValue["maxvalue"] : null;
                $ajMin = isset($authJsonValue["minvalue"]) ? $authJsonValue["minvalue"] : null;

                $tmpnewlimit = array();
                switch ($mergetype) {
                    case 1:
                        $allNewLimit = $authJsonLimit;
                        break;
                    case 2://取交集
                        if (!empty($authJsonValue) && !empty($newJsonValue)) {
                            if ($njMax != null && $njMin != null && $ajMax != null && $ajMin != null) {
                                $tmpnewlimit[] = setLimitMaxMin(min($njMax, $ajMax), max($njMin, $ajMin));
                            } else if ($njMax != null && $njMin != null && ($ajMax == null && $ajMin == null)) {
                                $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                            } else if ($njMax != null && $njMin != null && ($ajMax != null && $ajMin == null)) {
                                if ($ajMax > $njMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $njMin);
                                }
                            } else if ($njMax != null && $njMin != null && ($ajMax == null && $ajMin != null)) {
                                if ($ajMin < $njMin) {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $ajMin);
                                }
                            } else if ($njMax == null && $njMin == null && ($ajMax != null || $ajMin != null)) {
                                $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                            } else if ($njMax == null && $njMin != null && ($ajMax != null || $ajMin != null)) {
                                if ($njMin < $ajMin) { //可能有数字和null进行比较
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else { //有交集或交集为空
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $njMin);
                                }
                            } else if ($njMax == null && $njMin != null && ($ajMax == null && $ajMin == null)) {
                                $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                            } else if ($njMax != null && $njMin == null && ($ajMax != null || $ajMin != null)) {
                                if ($njMax > $ajMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $ajMin);
                                }
                            } else if ($njMax != null && $njMin == null && ($ajMax == null && $ajMin == null)) {
                                $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                            }
                        } else if (!empty($authJsonValue)) {
                            $allNewLimit = $authJsonLimit;
                        }
                        break;
                    case 3: //并集
                        if (!empty($authJsonValue) && !empty($newJsonValue)) {
                            if ($ajMax != null && $ajMin != null && $njMax != null && $njMin != null) {
                                if ($ajMax < $njMin || $ajMin > $njMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin(max($njMax, $ajMax), min($njMin, $ajMin));
                                }
                            } else if ($ajMax == null && $ajMin == null && ($njMax != null || $njMin != null)) {
                                $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                            } else if ($ajMax == null && $ajMin != null && $njMax != null && $njMin != null) {
                                if ($ajMin < $njMin) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else if ($ajMin < $njMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $njMin);
                                } else {//分段
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                }
                            } else if ($ajMax == null && $ajMin != null && $njMax == null && $njMin != null) {
                                if ($ajMin < $njMin) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $njMin);
                                }
                            } else if ($ajMax == null && $ajMin != null && $njMax != null && $njMin == null) {
                                if ($ajMin < $njMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $njMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                }
                            } else if ($ajMax != null && $ajMin == null && $njMax != null && $njMin != null) {
                                if ($ajMax > $njMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else if ($ajMax > $njMin) {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $ajMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                }
                            } else if ($ajMax != null && $ajMin == null && $njMax == null && $njMin != null) {
                                if ($ajMax > $njMin) {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $ajMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $njMin);
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                }
                            } else if ($ajMax != null && $ajMin == null && $njMax != null && $njMin == null) {
                                if ($ajMax > $njMax) {
                                    $tmpnewlimit[] = setLimitMaxMin($ajMax, $ajMin);
                                } else {
                                    $tmpnewlimit[] = setLimitMaxMin($njMax, $ajMin);
                                }
                            }
                        } else {
                            $tmpnewlimit[0]["value"]["maxvalue"] = null;
                            $tmpnewlimit[0]["value"]["minvalue"] = null;
                        }
                        break;
                    default:
                        break;
                }
                $allNewLimit = array_merge($allNewLimit, $tmpnewlimit);
            }
        }
        $newJsonLimit = $allNewLimit;
    } else {
        switch ($mergetype) {
            case 1:
                $newJsonLimit = $authJsonLimit;
                break;
            case 2: //交集
                if (count($authJsonLimit) > 0) {
                    $newJsonLimit = $authJsonLimit;
                }
                break;
            case 3:
                $newJsonLimit[] = setLimitMaxMin(null, null);
                break;
            default:
                break;
        }
    }
    foreach ($newJsonLimit as $ni => $nitem) {
        if ($datatype == "gaprange") {
            //gaprange类型的需要给value添加上 gap "day" , gaprange类型的都转化为"天"为单位进行比较
            $newJsonLimit[$ni]["value"]["gap"] = "day";
        }
        $newJsonLimit[$ni]["type"] = $datatype;
        $newJsonLimit[$ni]["repeat"] = 1;
    }
    return $newJsonLimit;
}

//转换年月为单位的limit为以天为单位的
function changeGapRange($filterLimit)
{
    $retArr = array();
    foreach ($filterLimit as $ai => $aitem) {
        $limitValue = $filterLimit[$ai]["value"];
        $max = null;
        if ($limitValue["maxvalue"] != null) {
            $max = getGapRangeValue($limitValue["maxvalue"], $limitValue["gap"]);
        }
        $min = null;
        if ($limitValue["minvalue"] != null) {
            $min = getGapRangeValue($limitValue["minvalue"], $limitValue["gap"]);
        }
        $tmplimit = array();
        $tmplimit["type"] = "gaprange";
        $tmplimit["repeat"] = 1;
        $tmplimit["value"]["maxvalue"] = $max;
        $tmplimit["value"]["minvalue"] = $min;
        $tmplimit["value"]["gap"] = "day";
        $retArr[] = $tmplimit;
    }
    return $retArr;
}

/*
 * 共用的合并权限的函数包含三种情况,
 * $mergetype: 1.权限json或计费json和最新版本(model_config静态数组)json的合并, 权限和计费的限制直接赋值给最新的json
 * $mergetype: 2.计费和权限的合并,取限制的交集
 * $mergetype: 3.权限和权限的合并,不同角色间的权限合并,取限制的并集
 * $baseArr 主循环的jsonArr, 最新的json
 * $authJson 含有权限的json
 * $tmpJson element json包含用户填写的值
 */
function getCommonMergeJson($mergetype, $newJsonArr, $authJson, $tmpJson = NULL)
{
    global $logger;
    $timeFieldArr = array("nearlytime", "beforetime", "untiltime", "createdtime");

    if (!empty($newJsonArr["filter"])) {
        //处理时间字段
        if (isset($newJsonArr["filter"]["createdtime"]) && isset($authJson["filter"]["createdtime"])) {
            $njTimeKey = "";
            $ajTimeKey = "";
            foreach ($timeFieldArr as $ti => $titem) {
                if (isset($authJson["filter"][$titem]) && count($authJson["filter"][$titem]["limit"]) > 0) {
                    $tmpajvalue = $authJson["filter"][$titem]["limit"][0]["value"];
                    if (array_key_exists("maxvalue", $tmpajvalue) || array_key_exists("minvalue", $tmpajvalue)) {
                        if ($tmpajvalue["maxvalue"] != null || $tmpajvalue["minvalue"] != null) {
                            $ajTimeKey = $titem;
                        }
                    } else {
                        $ajTimeKey = $titem;
                    }
                }
                if (isset($newJsonArr["filter"][$titem]) && count($newJsonArr["filter"][$titem]["limit"]) > 0) {
                    $tmpnjvalue = $newJsonArr["filter"][$titem]["limit"][0]["value"];
                    if (array_key_exists("maxvalue", $tmpnjvalue) || array_key_exists("minvalue", $tmpnjvalue)) {
                        if ($tmpnjvalue["maxvalue"] != null || $tmpnjvalue["minvalue"] != null) {
                            $njTimeKey = $titem;
                        }
                    } else {
                        $njTimeKey = $titem;
                    }
                }
            }
            //得到newjsonarr 和 authjson 时间字段的最大值最小值
            $njvTime = getTimeFieldMaxMin($njTimeKey, $newJsonArr);
            $ajvTime = getTimeFieldMaxMin($ajTimeKey, $authJson);

            //相对时间通过转为绝对时间来进行验证
            $njLimit = array();
            $ajLimit = array();
            if ($mergetype == 1) {
                if ($njTimeKey != "") {
                    $njLimit = $newJsonArr["filter"][$njTimeKey]["limit"];
                }
                if ($ajTimeKey != "") {
                    $ajLimit = $authJson["filter"][$ajTimeKey]["limit"];
                }
            } else {
                if ($njTimeKey != "") {
                    $newJsonArr["filter"][$njTimeKey]["limit"] = array();
                }
                foreach ($njvTime as $ni => $nitem) {
                    $tmp = array();
                    $tmp["type"] = "range";
                    $tmp["repeat"] = 1;
                    $tmp["value"] = $nitem;
                    $njLimit[] = $tmp;
                }
                foreach ($ajvTime as $ai => $aitem) {
                    $tmp = array();
                    $tmp["type"] = "range";
                    $tmp["repeat"] = 1;
                    $tmp["value"] = $aitem;
                    $ajLimit[] = $tmp;
                }
            }
            $newJsonArr["filter"]["createdtime"]["limit"] = getRangeTypeMergeLimit($mergetype, $njLimit, $ajLimit);
        }
        foreach ($newJsonArr["filter"] as $key => $value) {
            if (isset($authJson["filter"][$key])) {
                //合并allowcontrol等字段
                getControlOpt($mergetype, $newJsonArr["filter"][$key], $authJson["filter"][$key]);
                if (in_array($key, $timeFieldArr)) {
                    continue;
                } else if ($newJsonArr["filter"][$key]["datatype"] == $authJson["filter"][$key]["datatype"]) {
                    $datatype = $newJsonArr["filter"][$key]["datatype"];
                    switch ($datatype) {
                        case "int":
                        case "value_text_object":
                        case "string":
                            $result = getIntersectionAndUnion($newJsonArr["filter"][$key]["limit"], $authJson["filter"][$key]["limit"], $datatype);
                            switch ($mergetype) {
                                case 1:
                                    //account_rule对应字段json赋值给新json
                                    $newJsonArr["filter"][$key]["limit"] = $authJson["filter"][$key]["limit"];
                                    break;
                                case 2:
                                    $newJsonArr["filter"][$key]["limit"] = $result["intersect"];
                                    break;
                                case 3:
                                    $newJsonArr["filter"][$key]["limit"] = $result["union"];
                                    break;
                                default:
                                    break;
                            }
                            break;
                        case "range":
                            $newJsonArr["filter"][$key]["limit"] = getRangeTypeMergeLimit($mergetype, $newJsonArr["filter"][$key]["limit"], $authJson["filter"][$key]["limit"]);
                            break;
                        case "gaprange":
                            if (count($newJsonArr["filter"][$key]["limit"]) > 0) {
                                $newJsonArr["filter"][$key]["limit"] = changeGapRange($newJsonArr["filter"][$key]["limit"]);
                            }
                            if (count($authJson["filter"][$key]["limit"]) > 0) {
                                $authJson["filter"][$key]["limit"] = changeGapRange($authJson["filter"][$key]["limit"]);
                            }
                            $newJsonArr["filter"][$key]["limit"] = getRangeTypeMergeLimit($mergetype, $newJsonArr["filter"][$key]["limit"], $authJson["filter"][$key]["limit"], $datatype);
                            break;
                        default:
                            break;
                    }
                }
            }
            if ($tmpJson != NULL && isset($tmpJson["filter"][$key]["isshow"])) {
                //直接显示时需要合并 tempJson中的isshow, 新增时不需要, 新增时 传入的 $tmpJson和$authJson 相同
                if (count(array_diff($tmpJson, $authJson)) > 0) {
                    $newJsonArr["filter"][$key]["isshow"] = $tmpJson["filter"][$key]["isshow"];
                }
            }
        }
    }
    //判断设置了下载的字段权限时，将权限合并.为空数组时，表示不限制，也合并
    if (!empty($authJson['download_FieldLimit']) && !empty($newJsonArr['download_FieldLimit'])) {
        $result = getIntersectionAndUnion($newJsonArr['download_FieldLimit'], $authJson['download_FieldLimit']);
        switch ($mergetype) {
            case 1: //直接赋值
                $newJsonArr['download_FieldLimit'] = $authJson['download_FieldLimit'];
                break;
            case 2: //取交集
                $newJsonArr['download_FieldLimit'] = $result['intersect'];
                break;
            case 3: //并集
                $newJsonArr['download_FieldLimit'] = $result['union'];
                break;
            default:
                break;
        }
    } else if (!empty($authJson['download_FieldLimit'])) {//权限字段有值
        $newJsonArr['download_FieldLimit'] = $authJson['download_FieldLimit'];
    }

    //download_DataLimit
    if (isset($authJson['download_DataLimit']) && isset($newJsonArr['download_DataLimit'])) {
        switch ($mergetype) {
            case 1:
                $newJsonArr['download_DataLimit'] = $authJson['download_DataLimit'];
                break;
            case 2: //交集
                if (isset($newJsonArr['download_DataLimit'])) {
                    $newJsonArr['download_DataLimit'] = min($newJsonArr['download_DataLimit'], $authJson['download_DataLimit']);
                }
                break;
            case 3:
                if (isset($newJsonArr['download_DataLimit'])) {
                    $newJsonArr['download_DataLimit'] = max($newJsonArr['download_DataLimit'], $authJson['download_DataLimit']);
                }
                break;
            default:
                break;
        }
    } else if (isset($authJson['download_DataLimit'])) {
        $newJsonArr['download_DataLimit'] = $authJson['download_DataLimit'];
    }
    //allowDownload
    if (isset($authJson['allowDownload']) && isset($newJsonArr['allowDownload'])) {
        switch ($mergetype) {
            case 1:
                $newJsonArr['allowDownload'] = $authJson['allowDownload'];
                break;
            case 2:
                if (isset($newJsonArr['allowDownload'])) {
                    $newJsonArr['allowDownload'] = $newJsonArr['allowDownload'] && $authJson['allowDownload'];
                }
                break;
            case 3:
                if (isset($newJsonArr['allowDownload'])) {
                    $newJsonArr['allowDownload'] = $newJsonArr['allowDownload'] || $authJson['allowDownload'];
                }
                break;
            default:
                break;
        }
    } else if (isset($authJson['allowDownload'])) {
        $newJsonArr['allowDownload'] = $authJson['allowDownload'];
    }
    //allowupdatesnapshot
    if (isset($authJson['allowupdatesnapshot']) && isset($newJsonArr['allowupdatesnapshot'])) {
        switch ($mergetype) {
            case 1:
                $newJsonArr['allowupdatesnapshot'] = $authJson['allowupdatesnapshot'];
                break;
            case 2:
                if (isset($newJsonArr['allowupdatesnapshot'])) {
                    $newJsonArr['allowupdatesnapshot'] = $newJsonArr['allowupdatesnapshot'] && $authJson['allowupdatesnapshot'];
                }
                break;
            case 3:
                if (isset($newJsonArr['allowupdatesnapshot'])) {
                    $newJsonArr['allowupdatesnapshot'] = $newJsonArr['allowupdatesnapshot'] || $authJson['allowupdatesnapshot'];
                }
                break;
            default:
                break;
        }
    } else if (isset($authJson['allowupdatesnapshot'])) {
        $newJsonArr['allowupdatesnapshot'] = $authJson['allowupdatesnapshot'];
    }
    //alloweventalert
    if (isset($authJson['alloweventalert']) && isset($newJsonArr['alloweventalert'])) {
        switch ($mergetype) {
            case 1:
                $newJsonArr['alloweventalert'] = $authJson['alloweventalert'];
                break;
            case 2:
                if (isset($newJsonArr['alloweventalert'])) {
                    $newJsonArr['alloweventalert'] = $newJsonArr['alloweventalert'] && $authJson['alloweventalert'];
                }
                break;
            case 3:
                if (isset($newJsonArr['alloweventalert'])) {
                    $newJsonArr['alloweventalert'] = $newJsonArr['alloweventalert'] || $authJson['alloweventalert'];
                }
                break;
            default:
                break;
        }
    } else if (isset($authJson['alloweventalert'])) {
        $newJsonArr['alloweventalert'] = $authJson['alloweventalert'];
    }

    //处理facet的 filterlimit对应的值, 属于统计字段
    if (isset($authJson["facet"]["filterlimit"]["limit"]) && isset($newJsonArr["facet"]["filterlimit"]["limit"])) {
        if (count($authJson["facet"]["filterlimit"]["limit"]) > 0) {
            $result = getIntersectionAndUnion($newJsonArr["facet"]["filterlimit"]["limit"], $authJson["facet"]["filterlimit"]["limit"]);
            switch ($mergetype) {
                case 1:
                    $newJsonArr["facet"]["filterlimit"]["limit"] = $authJson["facet"]["filterlimit"]["limit"];
                    break;
                case 2:
                    $newJsonArr["facet"]["filterlimit"]["limit"] = $result['intersect'];
                    break;
                case 3:
                    $newJsonArr["facet"]["filterlimit"]["limit"] = $result['union'];
                    break;
                default:
                    break;
            }
        }
        getControlOpt($mergetype, $newJsonArr["facet"]["filterlimit"], $authJson["facet"]["filterlimit"]);
    }
    //facet limit
    if (isset($authJson["facet"]["limit"])) {
        if ($newJsonArr['facet']['datatype'] == $authJson["facet"]['datatype']) {
            if (count($authJson["facet"]["limit"]) > 0) {
                $result = getIntersectionAndUnion($newJsonArr["facet"]["limit"], $authJson["facet"]["limit"]);
                switch ($mergetype) {
                    case 1:
                        $newJsonArr["facet"]["limit"] = $authJson["facet"]["limit"];
                        break;
                    case 2:
                        $newJsonArr["facet"]["limit"] = $result['intersect'];
                        break;
                    case 3:
                        $newJsonArr["facet"]["limit"] = $result['union'];
                        break;
                    default:
                        break;
                }
            }
        }
        if ($authJson["version"] < 1021) {  //权限中存在的旧字段(后来改为其他名称) 需要改为新的名字
            foreach ($newJsonArr['facet']['limit'] as $fi => $fitem) {
                if ($fitem["value"] == "topic") {
                    $newJsonArr['facet']['limit'][$fi]["value"] = "wb_topic";
                }
            }
        }
        getControlOpt($mergetype, $newJsonArr["facet"], $authJson["facet"]);
    }

    if (isset($authJson["select"]["limit"])) {
        switch ($mergetype) {
            case 1:
                $newJsonArr["select"]["limit"] = $authJson["select"]["limit"];
                break;
            case 2:
                $newJsonArr["select"]["limit"] = array_intersect($newJsonArr["select"]["limit"], $authJson["select"]["limit"]);
                break;
            case 3:
                $newJsonArr["select"]["limit"] = array_merge($newJsonArr["select"]["limit"], $authJson["select"]["limit"]);
                break;
            default:
                break;
        }
        getControlOpt($mergetype, $newJsonArr["select"], $authJson["select"]);
    }
    if (isset($authJson["output"]["countlimit"]["limit"])) {
        $newJsonArr["output"]["countlimit"]["limit"] = getRangeTypeMergeLimit($mergetype, $newJsonArr["output"]["countlimit"]["limit"], $authJson["output"]["countlimit"]["limit"]);
        getControlOpt($mergetype, $newJsonArr["output"]["countlimit"], $authJson["output"]["countlimit"]);
    }
    //elements中对应的value赋值给新版本json
    if ($tmpJson != NULL) {
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
        if (isset($tmpJson["showid"])) {
            $newJsonArr["showid"] = $tmpJson["showid"];
        }
        if (isset($tmpJson["linetype"])) {
            $newJsonArr["linetype"] = $tmpJson["linetype"];
        }
    }
    return $newJsonArr;
}

function getGeneralOpt($tenantOpt, $rolesOpt)
{
    $userArr = array();
    $userArr["allowdrilldown"] = false;
    $userArr["allowdownload"] = false;
    $userArr["allowupdatesnapshot"] = false;
    $userArr["alloweventalert"] = false;
    $userArr["allowoverlay"] = false;
    $userArr["allowlinkage"] = false;
    $userArr["allowwidget"] = false;
    $userArr["allowaccessdata"] = false;
    $userArr["accessdatalimit"] = 0;
    $userArr["selfstyle"] = false;
    $userArr["allowvirtualdata"] = false;
    if (!empty($rolesOpt)) {
        foreach ($rolesOpt as $ri => $ritem) {
            foreach ($userArr as $ui => $uitem) {
                if ($ui == "accessdatalimit") {
                    if ($ritem[$ui] < $tenantOpt[$ui]) {
                        if ($ritem[$ui] > $userArr[$ui]) {
                            $userArr[$ui] = $ritem[$ui];
                        }
                    } else {
                        $userArr[$ui] = $tenantOpt[$ui];
                    }
                } else if ($ui == "selfstyle") {
                    if (!empty($tenantOpt[$ui])) {
                        $userArr[$ui] = true;
                    }
                } else {
                    if (!empty($tenantOpt[$ui])) {
                        if (!empty($ritem[$ui])) {
                            $userArr[$ui] = true;
                        }
                    }
                }
            }
        }
    }
    return $userArr;
}

/**
 *
 * 将方案内的父类转为子类
 */
function formatDictionaryPlan($plan)
{
    global $dsql, $logger;
    connectDB(DATABASE_WEIBOINFO);
    $arrsdata = json_decode($plan, true);
    $arrs = array();
    if (!empty($arrsdata)) {
        foreach ($arrsdata as $value) {
            if (empty($value)) {
                $arrs[] = array();
            } else {
                //循环方案每个类别
                $arr2 = array();
                $arr3 = array();
                foreach ($value as $value2) {
                    $arr2[] = "'" . $value2["code"] . "'";
                }
                $str = join(",", $arr2);
                $sql = "SELECT  `id`,  `parent_id`,  `category_name`,  `parent_name`,  `state` FROM weibo_info_eb"."."
                    . DATABASE_DICTIONARY_CATEGORY . " where state!=2 and parent_id !=-1 and id in(" . $str . ") or parent_id in(" . $str . ")";
                $qr2 = $dsql->ExecQuery($sql);
                if (!$qr2) {
                    $logger->error(__FILE__ . " func:" . __FUNCTION__ . " sql:{$sql} " . $dsql->GetError());
                    $arrs["flag"] = 0;
                } else {
                    $temp_arr = array();
                    while ($result = $dsql->GetArray($qr2, MYSQL_ASSOC)) {
                        $temp_arr["code"] = $result["id"];
                        $temp_arr["name"] = $result["category_name"];
                        $arr3[] = $temp_arr;
                    }

                }
                //$logger->info("转换方案：".var_export(($arr3),true));
                $arrs[] = $arr3;
            }
        }
    } else {
        $arr4 = array();
        $arrs[] = $arr4;
    }

    return json_encode($arrs);
}

//第一个参数：条件数组；第二个参数：查询字段；第三个参数：查询条件不完整或查不到时，不返回false
//返回结果是关联数组
function getQuoteFromSolr($rec, $fileds, $partial = false)
{
    global $logger;
    global $g_quote_guid_cache;
    if (count($g_quote_guid_cache) > 100) {
        $g_quote_guid_cache = array();
    }
    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'source_host:' . solrEsc($rec['source_host']);
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询引用的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    if (isset($rec['quote_father_mid'])) {
        $conds[] = 'mid:' . $rec['quote_father_mid'];
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询引用的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    $cds = implode('+AND+', $conds);
    //$logger->debug("quote conds:".$cds);
    if (!empty($g_quote_guid_cache[$cds])) {
        $hit = true;
        foreach ($fileds as $value) {
            if (!isset($g_quote_guid_cache[$cds][$value])) {
                $hit = false;
                break;
            }
        }
        if ($hit)
            return $g_quote_guid_cache[$cds];
    }

    $qr = solr_select_conds($fileds, $cds, 0, 1);
    if ($qr === false) {
        $logger->error(__FUNCTION__ . "从solr取数据出错 ");
        return false;
    } else {
        //$logger->debug("quote guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            if ($partial) {
                return NULL;
            } else {
                $logger->error(__FUNCTION__ . "引用在solr中不存在，请插入父亲。 " . var_export($rec, true));
                return false;
            }
        } else {
            //$logger->debug("quote guid:".var_export($qr,true));
            //$logger->debug("增加quote cache".var_export($g_quote_guid_cache,true));
            $g_quote_guid_cache[$cds][$fields] = $qr[0];
            return $qr[0];
        }
    }
}


//根据sourceid/source_host，retweeted_guid原创guid+father_floor/father_mid
function getFatherGuidFromSolr($rec, $partial = false,&$timeStatisticObj=null)
{
    global $logger;
    global $g_father_guid_cache;
    if (count($g_father_guid_cache) > 100) {
        $g_father_guid_cache = array();
    }
    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'source_host:' . solrEsc($rec['source_host']);
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询父亲的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    if (isset($rec['reply_father_mid'])) {
        $conds[] = 'mid:' . $rec['reply_father_mid'];
    } else if (isset($rec['original_url']) && isset($rec['reply_father_floor'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+' . 'floor:' . $rec['reply_father_floor'] . '+AND+' . 'paragraphid:0';
    } else if (isset($rec['retweeted_guid']) && isset($rec['reply_father_floor'])) {
        $conds[] = 'retweeted_guid:' . $rec['retweeted_guid'] . '+AND+' . 'floor:' . $rec['reply_father_floor'];
    } else if (isset($rec['reply_father_id'])) {
        $conds[] = 'id:' . $rec['reply_father_id'];
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询父亲的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    $cds = implode('+AND+', $conds);
    //$logger->debug("father conds:".$cds);
    if (!empty($g_father_guid_cache[$cds]['guid'])) {
        //$logger->debug("命中father cache");
        return $g_father_guid_cache[$cds]['guid'];
    }

    $solrstart_time = microtime_float();
    $qr = solr_select_conds(array('guid'), $cds, 0, 1);
    $solrend_time = microtime_float();

    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

    if ($qr === false) {
        $logger->error(__FUNCTION__ . "从solr取数据出错 ");
        return false;
    } else {
        //$logger->debug("father guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            if ($partial) {
                $logger->debug(__FUNCTION__ . "父亲在solr中不存在");
                return NULL;
            } else {
                $logger->error(__FUNCTION__ . "父亲在solr中不存在，请插入父亲。 " . var_export($rec, true));
                return false;
            }
        } else {
            //$logger->debug("father guid:".var_export($qr,true));
            //$logger->debug("增加father cache".var_export($g_father_guid_cache,true));
            $g_father_guid_cache[$cds]['guid'] = $qr[0]['guid'];
            return $qr[0]['guid'];
        }
    }
}

//根据sourceid/source_host，retweeted_status/retweeted_mid/original_url获取原创的guid。单条查询。
function getOriginalGuidFromSolr($rec, $partial = false,&$timeStatisticObj=null)
{
    global $logger;
    global $g_original_guid_cache;
    if (count($g_original_guid_cache) > 100) {
        $g_original_guid_cache = array();
    }

    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-根据文章信息从solr中获取原创/贴的Guid...");

    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'source_host:' . solrEsc($rec['source_host']);
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询原创的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    if (isset($rec['retweeted_mid'])) {
        $conds[] = 'mid:' . $rec['retweeted_mid'];
    } /*else if(isset($rec['original_url'])) {
		$conds[] = 'page_url:'.solrEsc($rec['original_url']);
	}*/
    else if (isset($rec['original_url'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+' . 'floor:0' . '+AND+' . 'paragraphid:0';
    } else if (isset($rec['retweeted_status'])) {
        $conds[] = 'id:' . $rec['retweeted_status'];
    } else {
        if ($partial) {
            return NULL;
        } else {
            //$logger->error(__FUNCTION__ . ' 查询原创的条件不完整 ' . var_export($rec, true));
            $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-根据文章信息从solr中获取原创/贴的Guid失败--+-查询原创时候条件不完整 当前文章信息:[" . var_export($rec, true) . "].");
            return false;
        }
    }

    $cds = implode('+AND+', $conds);
    //$logger->debug("ori conds:".$cds);
    if (!empty($g_original_guid_cache[$cds])) {
        //$logger->debug("命中原创cache");
        return $g_original_guid_cache[$cds];
    }

    $solrstart_time = microtime_float();
    $qr = solr_select_conds(array('guid'), $cds, 0, 1);
    $solrend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

    if ($qr === false) {
        //$logger->error(__FUNCTION__ . "从solr取数据出错 ");
        $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-根据文章信息从solr中获取原创/贴的Guid失败--+-从solr取数据出错!");
        return false;
    } else {
        //$logger->debug("原创guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            //$logger->error("原创在solr中不存在 ");
            //return NULL;
            if ($partial) {
                return NULL;
            } else {
                //$logger->error(__FUNCTION__ . "原创在solr中不存在，请插入原创。 " . var_export($rec, true));
                $logger->error(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-根据文章信息从solr中获取原创/贴的Guid失败--+-原创在solr中不存在,请先插入原创!");
                return false;
            }
        } else {
            //$logger->debug("原创guid:".var_export($qr,true));
            $g_original_guid_cache[$cds] = $qr[0]['guid'];
            //$logger->debug("增加原创cache".var_export($g_original_guid_cache,true));
            return $qr[0]['guid'];
        }
    }
}

/**
 * 根据趋势分析数据更新的频率 生成查询条件 查询当前更新周期内的数据是否存在(即是否已经该周期内的数据)
 * @param $updateFreq
 */
function generalSolrSelectConds4TrenAnal($updateFreq, $originalUrl, $floor = -1, $paragraphid = 0, $createdTime)
{
    global $logger;
    if ($floor != -1) {
        throw new Exception("处理趋势分析数据--+-生成当前更新周期查询条件异常--+-floor非法:[" . $floor . "] expected value is[-1]!");
    }
    if (empty($createdTime)) {
        throw new Exception("处理趋势分析数据--+-生成当前更新周期查询条件异常--+-createdTime为空--+-createdTime:[" . $createdTime . "].");
    }
    if (empty($updateFreq)) {
        throw new Exception("处理趋势分析数据--+-生成当前更新周期查询条件异常--+-updateFreq为空--+-createdTime:[" . $updateFreq . "].");
    }
    if (empty($originalUrl)) {
        throw new Exception("处理趋势分析数据--+-生成当前更新周期查询条件异常--+-originalUrl为空--+-createdTime:[" . $originalUrl . "].");
    }

    //计数配置
    $condsStr = 'original_url:' . solrEsc($originalUrl) . '+AND+' . 'floor:' . solrEsc(strval($floor)) . '+AND+' . 'paragraphid:' . $paragraphid;
    if ($updateFreq == TREND_ANA_FREQ_DAY) {
        //获取当前日期
        $curDayStr = date("y-m-d", $createdTime);
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-处理趋势分析数据--+-生成当前更新周期查询条件--+- 更新频率:[Day]--+-当前日期:[" . $curDayStr . "].");
//        $curDayInt  = narrowToSolrInt();
        //$curDayInt = strtotime($curDayStr);

        $year = date('Y', $createdTime);
        $month = date('n', $createdTime);
        $day = date('j', $createdTime);

//        $item['created_year'] = date('Y', $created_at);
//        $item['created_month'] = date('n', $created_at);
//        $item['created_day'] = date('j', $created_at);
        $condsStr = $condsStr . '+AND+' . 'created_year:' . $year . '+AND+' . 'created_month:' . $month . '+AND+' . 'created_day:' . $day . '+AND+' . 'dataClsfct:trendAnalysis';
        return $condsStr;
    } else if ($updateFreq == TREND_ANA_FREQ_WEEK) {
        throw new Exception("处理趋势分析数据--+-生成当前更新周期查询条件--+-暂时不支持的更新频率:[" . $updateFreq . "].");
    } else if ($updateFreq == TREND_ANA_FREQ_MON) {
        //获取当前日期
        $curDayStr = date("y-m-d", $createdTime);
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-处理趋势分析数据--+-生成当前更新周期查询条件--+- 更新频率:[Mon]--+-当前日期:[" . $curDayStr . "].");
        $year = date('Y', $createdTime);
        $month = date('n', $createdTime);
        $condsStr = $condsStr . '+AND+' . 'created_year:' . $year . '+AND+' . 'created_month:' . $month . '+AND+' . 'dataClsfct:trendAnalysis';
        return $condsStr;
    } else if ($updateFreq == TREND_ANA_FREQ_YEAR) {
        //获取当前日期
        $curDayStr = date("y-m-d", $createdTime);
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-处理趋势分析数据--+-生成当前更新周期查询条件--+- 更新频率:[Year]--+-当前日期:[" . $curDayStr . "].");
        $year = date('Y', $createdTime);
        $condsStr = $condsStr . '+AND+' . 'created_year:' . $year . '+AND+' . 'dataClsfct:trendAnalysis';
        return $condsStr;
    } else {
        throw new Exception("处理趋势分析数据--+-生成当前更新周期查询条件异常--+-更新频率的值非法:[" . $updateFreq . "].");
    }
}


function generalGuidFactStr4TrenAnal($updateFreq, $time, $originalURL)
{
    global $logger;
    $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成guid--+-趋势数据--+-更新频率:[{$updateFreq}]--+-created_at:[" . $time . "] originalURL:[{$originalURL}]");
    if (empty($time)) {
        throw new Exception("generate guid fact string for TrenAnal excption,value of filed:[created_at] is null.");
    }
    $factStr = $originalURL;
    //计数配置
    if ($updateFreq == TREND_ANA_FREQ_DAY) {
        //获取当前日期
        $curDayStr = date("ymd", $time);
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成guid--+-趋势数据--+-更新频率:[Day]--+-主键因素:[" . $curDayStr . "].");
//        $year = date('Y', $time);
//        $month = date('n', $time);
//        $day = date('j', $time);
        $factStr = $originalURL . "_" . $curDayStr;
        return $factStr;
    } else if ($updateFreq == TREND_ANA_FREQ_WEEK) {
        throw new Exception("生成guid--+-趋势数据--+-暂时不支持的更新频率:[" . $updateFreq . "].");
    } else if ($updateFreq == TREND_ANA_FREQ_MON) {
        //获取当前日期
        $curDayStr = date("ym", $time);
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成guid--+-趋势数据--+-更新频率:[Mon]--+-主键因素:[" . $curDayStr . "].");
//        $year = date('Y', $time);
//        $month = date('n', $time);
        $factStr = $originalURL . "_" . $curDayStr;
        return $factStr;
    } else if ($updateFreq == TREND_ANA_FREQ_YEAR) {
        //获取当前日期
        $curDayStr = date("y", $time);
        $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-生成guid--+-趋势数据--+-更新频率:[Year]--+-主键因素:[" . $curDayStr . "].");
        $factStr = $originalURL . "_" . $curDayStr;
        return $factStr;
    } else {
        throw new Exception("生成guid--+-趋势数据--+-更新频率的值非法:[" . $updateFreq . "].");
    }
}


//查出solr中已存在文章的guid。
function getArticleGuidOrMore($rec, $all = false,&$timeStatisticObj=null)
{
    global $logger;
    if (empty($rec))
        return NULL;
    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'source_host:' . solrEsc($rec['source_host']);
    } else {
        $logger->error(__FILE__ . __LINE__ . " --+-查询文章是否存在--+-查询文章的条件不完整 " . var_export($rec, true));
        return false;
    }

    if (isset($rec['mid'])) {
        $conds[] = 'mid:' . $rec['mid'];
    } else if (isset($rec['original_url']) && isset($rec['floor']) && isset($rec['paragraphid'])) {

        if (isset($rec['dataClsfct']) && $rec['dataClsfct'] == DATA_CLASSIFY_TRENDANALYSIS) {
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-查询文章是否存在--+- 当前文档数据为[趋势分析]数据!");
            //趋势分析数据
            if (empty($rec['updateFreq'])) {
                throw new Exception("添加文章--+-查询文章是否存在--+-获取查询条件异常! ErrorMsg:[当前数据为趋势分析数据，但是缺少更新频率!]");
            }
            //根据数据更新的频率 生成查询条件 查询当天/月/年的数据是否更新
            $conds[] = generalSolrSelectConds4TrenAnal($rec['updateFreq'], $rec['original_url'], $rec['floor'], $rec['paragraphid'], $rec['created_at']);
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-查询文章是否存在--+- 趋势分析数据--+-查询条件:[" . var_export($conds, true) . "].");
        } else {
            //非趋势分析数据
            if (isset($rec['paragraphid']))
                $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+' . 'floor:' . $rec['floor'] . '+AND+' . 'paragraphid:' . $rec['paragraphid'];
            $logger->info(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . "--+-查询文章是否存在--+- 根据[original_url:[{$rec['original_url']}] + floor:[{$rec['floor']}] + paragraphid:[{$rec['paragraphid']}]] 来生成GUID!");
        }
        //else
        //$conds[] = 'original_url:'.solrEsc($rec['original_url']).'+AND+'.'floor:'.$rec['floor'];
    } else if (isset($rec['id'])) {
        $conds[] = 'id:' . $rec['id'];
    } else if (isset($rec['child_post_id']) && isset($rec['original_url']) && isset($rec['reply_father_floor']) && isset($rec['paragraphid'])) {
        $conds[] = 'child_post_id:' . $rec['child_post_id'] . '+AND+reply_father_floor:' . $rec['reply_father_floor'] . '+AND+original_url:' . solrEsc($rec['original_url']) . '+AND+paragraphid:' . $rec['paragraphid'] . '';
    } else if (isset($rec['question_id']) && isset($rec['original_url']) && isset($rec['paragraphid'])) {
        $conds[] = 'question_id:' . $rec['question_id'] . '+AND+original_url:' . solrEsc($rec['original_url']) . '+AND+paragraphid:' . $rec['paragraphid'] . '';
    } else if (isset($rec['answer_id']) && isset($rec['original_url']) && isset($rec['paragraphid'])) {
        $conds[] = 'answer_id:' . $rec['answer_id'] . '+AND+original_url:' . solrEsc($rec['original_url']) . '+AND+paragraphid:' . $rec['paragraphid'] . '';
    } else if (isset($rec['child_post_id']) && isset($rec['original_url']) && isset($rec['answer_father_id']) && isset($rec['paragraphid'])) {
        $conds[] = 'child_post_id:' . $rec['child_post_id'] . '+AND+answer_father_id:' . $rec['answer_father_id'] . '+AND+original_url:' . solrEsc($rec['original_url']) . '+AND+paragraphid:' . $rec['paragraphid'] . '';
    } else {
        $logger->error(__FILE__ . __LINE__ . "  --+-查询文章是否存在--+-查询文章的条件不完整, 查询数据:[" . var_export($rec, true) . "].");
        return false;
    }

    $solrSelectStart =  microtime_float();
    $cds = implode('+AND+', $conds);
    if (!$all)
        $qr = solr_select_conds(array('guid'), $cds, 0, 1);
    else
        $qr = solr_select_conds('', $cds, 0, 1);
    $solrSelectEnd =  microtime_float();

    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrSelectEnd - $solrSelectStart);

    if ($qr === false) {
        //$logger->error(__FUNCTION__ . " 从solr取数据出错 ");
        $logger->error(__FILE__ . __LINE__ . "  --+-查询文章是否存在--+-调用solr失败!");
        return false;
    } else {
        //$logger->debug("自己guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            $logger->debug(__FILE__ . __LINE__ . "  --+-查询文章是否存在--+-文章不存在!");
            return NULL;
        } else {
            $guid = $qr[0]['guid'];
            $logger->debug(__FILE__ . __LINE__ . "  --+-查询文章是否存在--+-文章存在,Guid:[" . $guid . "].");
            if (!$all)
                return $guid;
            else {
                //$logger->debug("article:".var_export($qr[0],true));
                return $qr[0];
            }
        }
    }
}

//查询一批user的guid。返回结果source_host为一级key，id作为key。
function getUsers($users,&$timeStatisticObj=null)
{
    global $logger;
    $cds = array();
    $keys = array();

    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . " 准备查询用户信息...");

    foreach ($users as $k => $value) {
        if (isset($value['sourceid'])) {
            $source_cond = 'users_sourceid:' . $value['sourceid'];
        } else if (isset($value['source_host'])) {
            $source_cond = 'users_source_host:' . $value['source_host'];
        } else
            return false;
        $keys[] = '(' . $source_cond . '+AND+' . 'users_id:' . $value['id'] . ')';
    }

    $cds = implode('+OR+', $keys);

    $solrstart_time = microtime_float();
    $qr = solr_select_conds('', $cds, 0, count($users));
    $solrend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

    if ($qr === false) {
        $logger->error(__FUNCTION__ . "从solr查询用户信息出错,查询条件为:[" . var_export($cds, true) . "].");
        return false;
    } else {
        $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . " 从solr查询用户成功,结果:[" . var_export($qr, true) . "].");
        if (count($qr) == 0) {
            return NULL;
        } else {
            $logger->debug("user guid:" . var_export($qr, true));
            $results = array();
            foreach ($qr as $k => $value) {
                $results[$value['users_source_host']][$value['users_id']] = deleteUserFieldPre($value);
            }
            return $results;
        }
    }
    $logger->debug(" " . __FILE__ . " " . __LINE__ . " " . __FUNCTION__ . " 查询用户信息结束.");
}

//查出solr中已存在user的guid。
function getUserGuidOrMore($rec, $all = false, $mainurl = NULL, $partial = false,&$timeStatisticObj=null)
{
    global $logger;
    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'users_sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'users_source_host:' . solrEsc($rec['source_host']);
    } else if (isset($rec['page_url'])) {
        $conds[] = 'users_source_host:' . solrEsc(get_host_from_url($rec['page_url']));
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询用户的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    if (isset($rec['id'])) {
        $conds[] = 'users_id:' . $rec['id'];
    } else if (isset($mainurl)) {
        //$conds[] = 'users_mainurl:'.solrEsc($mainurl); //还没有在schema
    } else {
        if ($partial) {
            return NULL;
        } else {
            $logger->error(__FUNCTION__ . ' 查询用户的条件不完整 ' . var_export($rec, true));
            return false;
        }
    }

    $cds = implode('+AND+', $conds);
    $solrstart_time = microtime_float();
    if (!$all)
        $qr = solr_select_conds(array('guid'), $cds, 0, 1);
    else
        $qr = solr_select_conds('', $cds, 0, 1);
    $solrend_time = microtime_float();

    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

    if ($qr === false) {
        $logger->error(__FUNCTION__ . "从solr取数据出错 conds:" . var_export($cds, true));
        return false;
    } else {
        //$logger->debug("自己guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            //$logger->debug("user在solr中不存在 ");
            return NULL;
            //echo "user在solr中不存在 ";
            //exit;
        } else {
            //$logger->debug("user guid:".var_export($qr,true));
            $guid = $qr[0]['guid'];
            if (!$all)
                return $guid;
            else {
                if (!empty($qr[0])) {
                    $qr[0] = deleteUserFieldPre($qr[0]);
                }
                //$logger->debug("user2:".var_export($qr[0],true));
                return $qr[0];
            }
        }
    }
}

function getDocGuidFromSolr($rec,&$timeStatisticObj=null)
{
    global $logger;
    global $g_doc_guid_cache;
    if (count($g_doc_guid_cache) > 100) {
        $g_doc_guid_cache = array();
    }
    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'source_host:' . solrEsc($rec['source_host']);
    } else {
        $logger->error(__FUNCTION__ . ' 查询doc的条件不完整 ' . var_export($rec, true));
        return false;
    }

    if (isset($rec['original_url']) && isset($rec['floor']) && isset($rec['paragraphid'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+' . 'floor:' . $rec['floor'] . '+AND+' . 'paragraphid:0';
    } else if (isset($rec['original_url']) && isset($rec['reply_father_floor']) && isset($rec['child_post_id']) && isset($rec['paragraphid'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+reply_father_floor:' . $rec['reply_father_floor'] . '+AND+child_post_id:' . $rec['child_post_id'] . '+AND+paragraphid:0';
    } else if (isset($rec['original_url']) && isset($rec['question_id']) && isset($rec['paragraphid'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+question_id:' . $rec['question_id'] . '+AND+paragraphid:0';
    } else if (isset($rec['original_url']) && isset($rec['answer_id']) && isset($rec['paragraphid'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+answer_id:' . $rec['answer_id'] . '+AND+paragraphid:0';
    } else if (isset($rec['original_url']) && isset($rec['answer_father_id']) && isset($rec['child_post_id']) && isset($rec['paragraphid'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+answer_father_id:' . $rec['answer_father_id'] . '+AND+child_post_id:' . $rec['child_post_id'] . '+AND+paragraphid:0';
    } else {
        $logger->error(__FUNCTION__ . ' 查询doc的条件不完整 ' . var_export($rec, true));
        return false;
    }

    $cds = implode('+AND+', $conds);
    if (!empty($g_doc_guid_cache[$cds])) {
        return $g_doc_guid_cache[$cds];
    }

    $solrstart_time = microtime_float();
    $qr = solr_select_conds(array('guid'), $cds, 0, 1);
    $solrend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

    if ($qr === false) {
        $logger->error(__FUNCTION__ . "从solr取数据出错 ");
        return false;
    } else {
        //$logger->debug("doc guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            $logger->error(__FUNCTION__ . " doc在solr中不存在，请先插入doc " . var_export($qr, true));
            return false;
        } else {
            //$logger->debug("docguid:".var_export($qr,true));
            $guid = $qr[0]['guid'];
            $g_doc_guid_cache[$cds] = $qr[0]['guid'];
            return $guid;
        }
    }
}

/*function getMidFromUrlAndFloor($url, $floor){
	return solrEsc($url).$floor;
}*/

//将user字段放到article里
//一个user，多个文章
//参数1：文章数组；参数二：用户；参数三：delete只留下改变的字段和记录
function addUserToArticle($articles, $userInfo, $delete = false)
{
    global $logger, $solr_article_user_tags;
    //$logger->debug("userinfo:".var_export($userInfo,true));
    $item = array();
    if (isset($userInfo['created_at']))
        $userInfo['register_time'] = $userInfo['created_at'];
    if (isset($userInfo['verified']))
        $userInfo['verify'] = $userInfo['verified'];
    if (isset($userInfo['followers_count']))
        $userInfo['total_reach_count'] = $userInfo['followers_count'];
    if (isset($userInfo['gender'])) {
        $userInfo['sex'] = $userInfo['gender'];
    }
    if (isset($userInfo['id'])) {
        $userInfo['userid'] = $userInfo['id'];
    }

    foreach ($solr_article_user_tags as $tag) {
        if (isset($userInfo[$tag]))
            $item[$tag] = $userInfo[$tag];
    }

    //对文章的字段进行比较，没有的才加，已有的不改
    foreach ($articles as $k => $art) {
        $change = false;
        $tmp_result = null;
        foreach ($solr_article_user_tags as $tag) {
            if (isset($item[$tag])) {

                //如果文章中没有该用户属性[$tag] 则把该用户属性添加到文章
                if (!isset($articles[$k][$tag])) {
                    $articles[$k][$tag] = $item[$tag];
                    $change = true;
                }
                //两端都set的不改变
            } else {
                //如果用户信息中没有该属性[$tag] 则 文章中如果存在该属性并且需要删除文章中的属性，则删除文章中的该属性
                if (isset($articles[$k][$tag]) && $delete) {
                    unset($articles[$k][$tag]);
                }
            }
        }
        if (!$change && $delete) {
            unset($articles[$k]);
        }
    }
    //$logger->debug("补充user的article：".var_export($articles,true));
    return $articles;
    //return $results;
}

function narrowToSolrInt($value)
{
    if ($value > SOLR_MAX_INT)
        return SOLR_MAX_INT;
    else if ($value < SOLR_MIN_INT)
        return SOLR_MIN_INT;
    else
        return $value;
}

//根据文章条件查询出段落记录
function getParaFromSolr($rec, $fileds)
{
    global $logger;
    $conds = array();
    if (isset($rec['sourceid'])) {
        $conds[] = 'sourceid:' . $rec['sourceid'];
    } else if (isset($rec['source_host'])) {
        $conds[] = 'source_host:' . solrEsc($rec['source_host']);
    } else {
        $logger->error(__FUNCTION__ . ' 查询段落的条件不完整 ' . var_export($rec, true));
        return false;
    }

    if (isset($rec['original_url']) && isset($rec['floor'])) {
        $conds[] = 'original_url:' . solrEsc($rec['original_url']) . '+AND+' . 'floor:' . $rec['floor'] . '+AND+' . '!paragraphid:0';
    } else {
        $logger->error(__FUNCTION__ . ' 查询段落的条件不完整 ' . var_export($rec, true));
        return false;
    }

    $cds = implode('+AND+', $conds);

    $solrstart_time = microtime_float();
    $qr = solr_select_conds($fileds, $cds, 0, (pow(2, 31) - 1));
    $solrend_time = microtime_float();
    //统计时间
    addTime4Statistic($timeStatisticObj,SOLR_SELECT_TIME_KEY,$solrend_time - $solrstart_time);

    if ($qr === false) {
        $logger->error(__FUNCTION__ . "从solr取数据出错 ");
        return false;
    } else {
        //$logger->debug("doc guid results:".var_export($qr,true));
        if (count($qr) == 0) {
            return NULL;
        } else {
            //$logger->debug("docguid:".var_export($qr,true));
            return $qr;
        }
    }
}


/**
 * 获取用户的认证类型 以及 根据用户的'country' 'province' 'city' 'district' 字段获取用户的地区编码
 * @param $userInfo
 * @param $sourceid
 * @return array|bool|null
 */
function processUserData($userInfo, $sourceid)
{

    if (isset($userInfo['created_at_ts'])) {
        $tmp = $userInfo['created_at_ts'];
    } else if (isset($userInfo['created_at'])) {
        $tmp = is_numeric($userInfo['created_at']) ? $userInfo['created_at'] : strtotime($userInfo['created_at']);
    }
    if (isset($tmp))
        $userInfo['created_at'] = narrowToSolrInt($tmp);
    //设置认证、认证类型
    if (isset($global_usercache[$userInfo['source_host']][$userInfo['id']]['verified']) && isset($global_usercache[$userInfo['source_host']][$userInfo['id']]['verified_type'])) {
        $userInfo['verified'] = $global_usercache[$userInfo['source_host']][$userInfo['id']]['verified'];
        $userInfo['verified_type'] = $global_usercache[$userInfo['source_host']][$userInfo['id']]['verified_type'];
    } else {
        $verify2 = array();
        setVerified($verify2, $userInfo);
        $userInfo['verified'] = $verify2['verified'];
        $userInfo['verified_type'] = $verify2['verified_type'];
        //将认证类型、认证放入缓存，inner_insert_status用到。global_usercache是有问题的，以id为标识.tbd
        $global_usercache[$userInfo['source_host']][$userInfo['id']]['verified'] = $userInfo['verified'];
        $global_usercache[$userInfo['source_host']][$userInfo['id']]['verified_type'] = $userInfo['verified_type'];
    }

    //获得省市区
    if (isset($global_usercache[$userInfo['source_host']][$userInfo['id']]["country_code"]) && isset($global_usercache[$userInfo['source_host']][$userInfo['id']]["province_code"])) {
        $areanames = array("country_code", "province_code", "city_code", "district_code");
        foreach ($areanames as $area_key => $area_name) {
            if (isset($global_usercache[$userInfo['source_host']][$userInfo['id']][$area_name])) {
                $userInfo[$area_name] = $global_usercache[$userInfo['source_host']][$userInfo['id']][$area_name];
            }
        }
    } else {
        $area_arr = get_area_code_from_user($userInfo, $sourceid, true);
        if (isset($area_arr['result']) && $area_arr['result'] === false) {
            return $area_arr;
        } else if (!empty($area_arr)) {
            foreach ($area_arr as $area_key => $area_value) {
                if ($area_value) {
                    $userInfo[$area_key] = $area_value;
                    $global_usercache[$userInfo['id']][$area_key] = $area_value;//放入cache
                }
            }
        }
    }

    return $userInfo;
}

/**
 *
 * @param $source
 * @param $weibos
 * @param int $isseed
 * @param string $timeline
 * @param bool|true $isnested : 提交有嵌套结构的数据,例如:转发文章包含对应的原创结构
 * @param bool|false $ispartialdata : 是否是不完整的数据
 * @return array
 */
function addweibo($source, $weibos, $isseed = 0, $timeline = 'show_status', $isnested = true, $ispartialdata = false,$isDistributedTask = false,&$timeStatisticObj = null,$isSegmented = false)
{
    global $logger;
    //更改分词字段的结构
    if(!$isSegmented){
        changeTokenfieldsType($weibos);
    }
    //$logger->debug(__FILE__ . __LINE__ . "addweibo: " . var_export($weibos, true));
    $needdata = array();
    $dataobj['ispartialdata'] = $ispartialdata;
    $dataobj['data'] = $weibos;
    if ($isnested) {
        $solr_r = insert_nested_data($dataobj, $isSegmented, 'task', $timeline, $source, NULL, NULL, $isseed);
    } else {
        $solr_r = insert_data($dataobj, $isSegmented, 'task', $timeline, $source, NULL, NULL, $isseed, $isDistributedTask,$timeStatisticObj);
    }
    /*
	if($timeline == 'comments_show_batch' || $timeline == 'comments_spider'){
		$solr_r = insert_comment($weibos,$timeline,$source);
	}
	else{
		$solr_r = insert_status2($weibos,$timeline,$source,NULL,NULL, $isseed);
	}
	*/
    return $solr_r;
}

/*根据统计信息转换成文字*/
function formatStatisticsInfo($statistics_info)
{
    $msgstr = "";
    $stat = $statistics_info->taskparams->scene;
    if (isset($stat->solr_count)) {
        $msgstr .= "调用solr" . $stat->solr_count . "次, ";
    }
    if (isset($stat->spider_statuscount)) {
        $msgstr .= "总需提交" . $stat->spider_statuscount . "条, ";
    }
    if (isset($stat->user_count)) {
        $msgstr .= "总用户数" . $stat->user_count . ", ";
    }
    if (isset($stat->spider_usercount)) {
        $msgstr .= "总新增用户" . $stat->spider_usercount . "个, ";
    }
    if (isset($stat->exists_weibocount)) {
        $msgstr .= "数据库存在" . $stat->exists_weibocount . "条, ";
    }
    if (isset($stat->update_weibocount)) {
        $msgstr .= "已更新" . $stat->update_weibocount . "条, ";
    }
    if (isset($stat->solrerrorcount)) {
        $msgstr .= "失败" . $stat->solrerrorcount . "条, ";
    }
    if (isset($stat->storetime)) {
        $msgstr .= "存储到solr花费" . $stat->storetime . ", ";
    }
    if (isset($stat->insertsql_statustime)) {
        $msgstr .= "插入数据库花费" . $stat->insertsql_statustime . ", ";
    }
    if (isset($stat->insertsql_statuscount)) {
        $msgstr .= "总入库数" . $stat->insertsql_statuscount . "条, ";
    }
    if (isset($stat->insertsql_usertime)) {
        $msgstr .= "插入用户花费" . $stat->insertsql_usertime . "条, ";
    }
    return $msgstr;
}

function my_iconv($from, $to, $string)
{
    global $logger;
    @trigger_error('iconv_error', E_USER_NOTICE);
    $result = @iconv($from, $to, $string);
    $error = error_get_last();
    if ($error['message'] != 'iconv_error') {
        $logger->debug(__FILE__ . __LINE__ . " iconv error " . var_export($string, true));
        //处理错误
//        $result = $string;
        return null;
    }
    return $result;
}

/*
 * @brief  article_taginfo 字段映射表, 根据$_SESSION中用户id找出此用户使用的article_taginfo字段 
 *         用户给文章打标签有两种设计思路:
 *         1.在schema.xml中单独存放,在schema.xml中增加三个字段 userid guid article_taginfo
 *         这样再文章查询中就需要join查询, 再模型配置中就是联动分析,配置不灵活,查询也比较复制
 *         2.为每个用户在schema中增加一个字段, 比如sanfu_i的用户对应article_taginfo1, 这种设计造成
 *         schema.xml字段增多, 好处是和其他字段进行逻辑运算方便.
 *         TODO 映射表应该放到数据库管理
 * @param  string $userid 当前登录用的userid
 * @return string schema.xml中对应此用户的字段
 * @author Bert
 * @date   2016-7-1
 * @change 2016-7-1
 * */
function getUserArticleTaginfoField($userid)
{
    $tagfield = "article_taginfo1";
    //这里应该在数据库维护
    $userid_tagfield_array = array(
        array('userid' => 47, 'tagfield' => 'article_taginfo1'),
        array('userid' => 123, 'tagfield' => 'article_taginfo1')
    );
    foreach ($userid_tagfield_array as $key => $uitem) {
        if ($userid == $uitem['userid']) {
            $tagfield = $uitem['tagfield'];
            break;
        }
    }
    return $tagfield;
}

/*
 * @brief  根据feature guid查询是否为父类,当为父类时需要一直展开到特征分类最后一级
 * @param  String $guid
 * @param  String $field 要查询的字段
 * @param  Array  $resultArr 查询出所有特征分类的数组
 * @param  Boolen $onlyLast 只查询最后一级, 首先检查是否为最后一级,只有最后一级时查询数据
 * @return 特征分类数组
 * @author Bert
 * @date   2016-6-23
 * @change 2016-6-23
 * */
function getAllFeatureByID($father_guid, $field, &$resultArr, $onlyLast=false,$featureclass=NULL)
{
    global $logger;
    //$logger->debug(__FILE__.__LINE__." father_guid ".var_export($father_guid, true)." field ".var_export($field, true)." resultArr ".var_export($resultArr, true)."");
    $r = getFeatureClass(NULL, NULL, NULL, $field, NULL, $father_guid);
    if (!empty($r['datalist'])) {
        //$logger->debug(__FILE__.__LINE__." father_guid ".var_export($father_guid, true)." field ".var_export($field, true)." r['datalist'] ".var_export($r['datalist'], true)."");
        if(!$onlyLast){
            foreach ($r['datalist'] as $ri => $ritem) {
                if (!isset($ritem['feature_keyword'])) {
                    getAllFeatureByID($ritem['guid'], $field, $resultArr);
                } else {
                    $tmpobj = array();
                    $tmpobj['guid'] = $ritem['guid'];
                    $tmpobj['feature_father_guid'] = $ritem['feature_father_guid'];
                    $tmpobj['feature_class'] = $ritem['feature_class'];
                    $tmpobj['feature_field'] = $field;
                    $resultArr[] = $tmpobj;
                }
            }
        }
    } else { //根据feature_father_guid没有查询出子级,说明为最后一级
		//参数不同，意义不同。通过guid，查出所属词典的类。2017/2/16
		$lastLevel = getFeatureClass(NULL, NULL, NULL, $field, NULL, NULL, $father_guid);
        $lastLevelClass =  getFeatureClass(NULL, NULL, NULL, $field, NULL, NULL, NULL,$featureclass);

        if (!empty($lastLevel['datalist'])) {
            $lastitem = $lastLevel['datalist'][0];
            //当查询到最后一级时, 用户选择的特征分类包含多个关键词时会是多条记录,需要全部查询出来
            $r_keyword = getFeatureKeyword(NULL, NULL, NULL, NULL, str_replace("#", "", $lastitem['feature_class']), NULL, $lastitem['feature_father_guid']);
            foreach ($r_keyword['datalist'] as $r_ki => $r_kitem) {
                $tmpobj = array();
                $tmpobj['guid'] = $r_kitem['guid'];
                $tmpobj['feature_father_guid'] = $r_kitem['feature_father_guid'];
                $tmpobj['feature_class'] = $r_kitem['feature_class'];
                $tmpobj['feature_field'] = $field;
                $resultArr[] = $tmpobj;
            }
        }
        $tmpobjclass = array();
        if(empty($lastLevel['datalist']) && !empty($lastLevelClass['datalist'])){
            $lastitemclass = $lastLevelClass['datalist'];
            foreach ($lastitemclass as $r => $kitem) {
                $flag = true;
                foreach ($tmpobjclass as $ri => $kitemclass){
                    if($kitemclass==$kitem['feature_father_guid']){
                        $flag=false;
                    }
                }
                if($flag){
                    $tmpobjclass[] = $kitem['feature_father_guid'];
                }
            }

            $lenclass= count($tmpobjclass);
            if( $lenclass>0){
                if($lenclass==1){
                    $lastitem = $lastLevelClass['datalist'][0];
                    //当查询到最后一级时, 用户选择的特征分类包含多个关键词时会是多条记录,需要全部查询出来
                    $r_keyword = getFeatureKeyword(NULL, NULL, NULL, NULL, str_replace("#", "", $lastitem['feature_class']), NULL, $lastitem['feature_father_guid']);
                    foreach ($r_keyword['datalist'] as $r_ki => $r_kitem) {
                        $tmpobj = array();
                        $tmpobj['guid'] = $r_kitem['guid'];
                        $tmpobj['feature_father_guid'] = $r_kitem['feature_father_guid'];
                        $tmpobj['feature_class'] = $r_kitem['feature_class'];
                        $tmpobj['feature_field'] = $field;
                        $resultArr[] = $tmpobj;
                    }
                }else{
                    $tmpobj = array();
                    $resultArr["featureclasserror"]=$featureclass;
                }
            }
        }

    }
}

/**
 *   预警条件逻辑
 **/




/*   第一步   */
function getLogicConds($retconds, $logicobj, $elecondition){
    global $logger;

    foreach($logicobj as $k=>$v){
        $logger->debug(__FILE__.__LINE__."key值为数据为 ".var_export($k, true));
        $logger->debug(__FILE__.__LINE__."v值数据为 ".var_export($v, true));

        if ($k != "type" && $k != "bop") {
            if (strpos("bop",$v)) {
                getLogicConds($retconds, $v, $elecondition);
            }
            else {
        //        return 1;
                $data = getCondExpDes($v, $elecondition);
                $logger->debug(__FILE__.__LINE__."单个数据为 ".var_export($data, true));
                $condtion[] = $data;
            }
        }
    }
    return $condtion;
}

/***    第二步   **/
function getCondExpDes($cond, $elecondition){
    global $logger;
    $logger->debug(__FILE__.__LINE__." 这是传到第二步的数据 ".var_export($cond->obj1, true));
//    return 1;
    if (!empty($cond->obj1)) {
        $retdes .= getCalExpDes($cond->obj1, $elecondition);
        if ($cond->obj2) {
            switch ($cond->rop) {
                case "==":
                    $retdes .= "等于";
                    break;
                case "!=":
                    $retdes .= "不等于";
                    break;
                case ">":
                    $retdes .= "大于";
                    break;
                case ">=":
                    $retdes .= "大于等于";
                    break;
                case "<":
                    $retdes .= "小于";
                    break;
                case "<=":
                    $retdes .= "小于等于";
                    break;
                case "[]":
                    $retdes .= "包含";
                    break;
                default:
                    break;
            }
            $retdes .= getCalExpDes($cond->obj2);
        }
    }
    return $retdes;
}

/**     第三步   **/
function getCalExpDes($exp, $elecondition)
{
    global $logger;
    $logger->debug(__FILE__.__LINE__." 这是传到第三步的数据 ".var_export($exp->arg1, true));
//    return 1;
    $retdes .= getAtomicExpDes($exp->arg1, $elecondition);
    if ($exp->arg2) {
        switch ($exp->cop) {
            case "+":
                $retdes .= "加";
                break;
            case "-":
                $retdes .= "减";
                break;
            case "×":
                $retdes .= "乘";
                break;
            case "÷":
                $retdes .= "除";
                break;
            default:
                break;
        }
        $retdes .= getAtomicExpDes($exp->arg2, $elecondition);
    }
    return $retdes;
}

/**   第四步   **/
function getAtomicExpDes($operand, $elecondition)
{
    global $logger;
    $logger->debug(__FILE__.__LINE__." 这是传到第四步的数据 ".var_export($operand, true));

    if (isset($operand->table)) {
            $tbl = intval($operand->table) + 1;
            if ($operand->tablename) {
                $retdes .= " " . $operand->tablename . "组 ";
            } else {
                $retdes .= "第" . $tbl . "组 ";
            }
        }
        if (isset($operand->column)) {
//                    var cdes = "";
            $cdes = intval($operand->column) + 1;
//                    var dn = "";
            if ($operand->field) {
                if ($operand->field == "text" || $operand->field == "alias") {
//                            var $facetfield = "";
//                            $snapshotData;
//                    if (window . currInstance) {
////                                snapshotData = getCurrSnapshotData();
////                                if(snapshotData != null && snapshotData[0].facet != undefined){
////                                    facetfield = snapshotData[0].facet.name;
////                                }
//                    } else {
//                        if ($elecondition) {
////                                    $snapshotData = elecondition.firstrequestData;
////                                    if($snapshotData != null && $snapshotData[0].facet != undefined){
////                                        $facetfield = snapshotData[0].facet.name;
////                                    }
//                        }
//                    }
                    if ($snapshotData) {
                        if ($facetfield == "") {
                            $dn = "正文";
                        } else {
                            $dn = MoregetDisplayName($facetfield);
                        }
                    }
                } else if ($operand->field == "range") {
                    $dn = "区间起始";
                } else if ($operand->field == "rangeend") {
                    $dn = "区间结束";
                } else {
                    $dn = MoregetRetweetDisplayName($operand->field, $field);
                }
            }
            if ($dn) {
                $retdes .= "第" .$cdes . "(" . $dn . ")列";
            } else {
                $retdes .= "第" . $cdes . "列";
            }
        }
        if (isset($operand->row)) {
            switch ($operand->row) {
                case "totalnum":
                    $retdes = "记录个数";
                    break;
                case "all":
                    $retdes .= "任意值";
                    break;
                case "count":
                    $retdes .= "计数";
                    break;
                case "sum":
                    $retdes .= "和";
                    break;
                case "max":
                    $retdes .= "最大值";
                    break;
                case "min":
                    $retdes .= "最小值";
                    break;
                case "average":
                    $retdes .= "平均值";
                    break;
                default:
                    $trow = intval($operand->row) + 1;
                    $retdes .= "第" . $trow . "行";
                    break;
            }
        }
        if ($operand->constant) {
            $retdes .= "" . $operand->constant;
        }
        return $retdes;
    }
/***     第五步    ***/
function MoregetRetweetDisplayName($field, $filter)
{
    global $logger;
    $logger->debug(__FILE__.__LINE__." 这是传到第五步的数据 ".var_export($field, true));
//                var ret = "";
//                if(field.indexOf("retweeted_") != -1){
//        if (strpos($field, "retweeted_")) {
////                    $tmpfield = field.split("retweeted_");
//            $tmpfield = str_replace("retweeted_", "", $field);
//            if (strlen($tmpfield) > 1) {
//                $tmpret = self::getDisplayName($tmpfield['1'], $filter);
//                if ($tmpret != "") {
//                    $ret = "原创" . $tmpret;
//                }
//            }
//        } else {
    $ret = MoregetDisplayName($field, $filter);
//        }
    return $ret;
}
/***   第六步    **/
function MoregetDisplayName($field, $filter)
{
    global $logger;
    switch ($field) {
        case "text":
            $displayName = "关键词";
            break;
        case "created_at":
            $displayName = "创建时间";
            break;
        case "nearlytime":
            $displayName = "相对今天";
            break;
        case "beforetime":
            $displayName = "时间段";
            break;
        case "untiltime":
            $displayName = "日历时间段";
            break;
        case "combinWord":
            $displayName = "短语";
            break;
        case "business":
            $displayName = "行业";
            break;
        case "areauser":
        case "users_location":
            $displayName = "用户地区";
            break;
        case "users_city_code":
        case "city_code":
            $displayName = "用户城市";
            break;
        case "users_district_code":
        case "district_code":
            $displayName = "用户县区";
            break;
        case "users_province_code":
        case "province_code":
            $displayName = "用户省份";
            break;
        case "users_country_code":
        case "country_code":
            $displayName = "用户国家";
            break;
        case "areamentioned":
            $displayName = "提及地区";
            break;
        case "city":
            $displayName = "提及城市";
            break;
        case "district":
            $displayName = "提及县区";
            break;
        case "province":
            $displayName = "提及省份";
            break;
        case "country":
            $displayName = "提及国家";
            break;
        case "ancestor_city":
            $displayName = "上层转发提及城市";
            break;
        case "ancestor_district":
            $displayName = "上层转发提及县区";
            break;
        case "ancestor_province":
            $displayName = "上层转发提及省份";
            break;
        case "ancestor_country":
            $displayName = "上层转发提及国家";
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
        case "original_url":
            $displayName = "页面地址";
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
            $displayName = "作者昵称";
            break;
        case "users_verified_reason":
        case "verified_reason":
            $displayName = "认证原因";
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
            $displayName = "情感@用户";
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
        case "ancestor_emoCountry":
            $displayName = "上层转发提及国家情感";
            break;
        case "ancestor_emoProvince":
            $displayName = "上层转发提及省份情感";
            break;
        case "ancestor_emoCity":
            $displayName = "上层转发提及城市情感";
            break;
        case "ancestor_emoDistrict":
            $displayName = "上层转发提及县区情感";
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
        case "users_level":
            $displayName = "用户级别";
            break;
        case "father_guid":
            $displayName = "上层文章唯一标识";
            break;
        case "retweeted_guid":
            $displayName = "原创唯一标识";
            break;
        case "retweeted_status":
            $displayName = "原创ID";
            break;
        case "id":
            $displayName = "微博ID";
            break;
        case "reposts_count":
        case "repostsnum":
            $displayName = "转发数";
            break;
        case "comments_count":
        case "commentsnum":
            $displayName = "评论数";
            break;
        case "direct_comments_count":
            $displayName = "直接评论数";
            break;
        case "praises_count":
            $displayName = "赞";
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
            $displayName = "文章数";
            break;
        case "users_replys_count":
            $displayName = "回复数";
            break;
        case "users_recommended_count":
            $displayName = "精华帖数";
            break;
        case "users_favourites_count":
            $displayName = "收藏数";
            break;
        case "users_bi_followers_count":
            $displayName = "互粉数";
            break;
        case "users_sourceid":
        case "sourceid":
        case "users_source_host":
            $displayName = "数据来源";
            break;
        case "discuss_count":
            $displayName = "讨论数";
            break;
        default:
            $displayName = "";
            break;
    }
    $logger->debug(__FILE__.__LINE__." displayname数据 ".var_export($displayName, true));
//        if ($displayName == "") {
//            if ($filter != null && $filter['field'] != undefined) {
//                $displayName = $filter['field']['label'];
//            }
//        }
    return $displayName;
}



/**
 *   查询数据更新表
 *
 ***/
function selectUpdateWeibo($q, $starttime, $endtime, $page, $each_count) {
    global $dsql,$logger;
    $condition = " where status = 0 and ";
    if(isset($q) && !empty($q)){
        $condition .= "search_key = "."'".$q."' ";
    }
    if(isset($starttime) && !empty($starttime)){
        $condition .= "and release_time >= ".$starttime." ";
    }
    if(isset($endtime) && !empty($endtime)){
        $condition .= "and release_time <= ".$endtime." ";
    }
    if(isset($q) && !empty($q) && isset($q) && !empty($q)){
        $condition .= "limit ".($page-1)*$each_count.",".$each_count;
    }

    $sql = "select d_id,repost_num from weibo_update";
    $sqlsel = $sql.$condition;
    $logger->debug(__FILE__.__LINE__."sqlsel".var_export($sqlsel,true));
    $qrsel = $dsql->ExecQuery($sqlsel);
    if (!$qrsel) {
        $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sqlsel . " error:" . mysql_error());
        return false;
    } else {
        $hasids = array();
        while ($tmpresult = $dsql->GetArray($qrsel, MYSQL_ASSOC)) {
//            $logger->debug(__FILE__.__LINE__."tmpresult".var_export($tmpresult,true));
            $hasids[] = $tmpresult;
        }
    }
    return $hasids;
}




/*
 * 微博内容匹配关键词
 * */
function GetMatch($data,$q) {
	
    $data = strtolower($data);        //把微博正文转化为小写
    
    $is_right = preg_match("/[\s]+/",$q);//判断关键词是否是  且关系的那种  如（西门子  家电）
    if($is_right){
        $b = preg_split('/[\s]+/',$q);
        foreach($b as $value){
            if(strpos($data,$value) !== false){
                $result = true;
            }else{
                $result = false;
                break;
            }
        }
    }else{
        if(strpos($data,$q) !== false){
            $result = true;
        }else{
            $result = false;
        }
    }
    return $result;
}


/*
 * 创建转发任务
 * */
function created_repost($condition,$task,$each_count = 200) {
    $imtask = new Task(null);
    $imtask->task = TASK_REPOST_TREND;
    $imtask->tasktype = TASKTYPE_SPIDER;
    $imtask->tasklevel = $task->tasklevel;
    $imtask->local = "1";
    $imtask->activatetime = $task->activatetime;
    $imtask->conflictdelay = $task->conflictdelay;
    $imtask->remarks = "这是通过" . $task->id . "号任务派生出来的抓取转发任务";
    $imtask->taskpagestyletype = TASK_PAGESTYLE_ARTICLELIST;
    $imtask->remote = $task->remote;
    $imtask->tenantid = "";
    $imtask->userid = "";
    $imtask->taskparams->iscommit = true;
    $imtask->taskparams->dictionaryPlan = $task->taskparams->dictionaryPlan;
    $imtask->taskparams->source = WEIBO_SINA; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
    $imtask->taskparams->each_count = $each_count;
    $oristatus = array();
	$oristatus['0'] = $condition;
    $imtask->taskparams->oristatus = $oristatus;
    $imtask->taskparams->config = $task->taskparams->config;
    $imtask->taskparams->duration = $task->taskparams->duration;
    $imtask->taskparams->forceupdate = "0";
    $imtask->taskparams->isseed = $task->taskparams->isseed;
    $imtask->taskparams->isrepostseed = "1";
    $imtask->taskparams->logoutfirst = $task->taskparams->logoutfirst;
    $imtask->taskparams->iscalctrend = "0";
    $result = addTask($imtask);
    return $result;
}


/*
 *  对入库数据进行赋值
 * */
function Handle_data($data,$q,$is_retweeted) {
    //因为数据不完整，所以要将这一部分转发中带有关键词的这一部分微博id入库，便于更新
    $iitask = new Task(null);
    $iitask->d_id = $data['id'];
    $iitask->release_time = strtotime($data['created_at']);
    $iitask->type = '1';   //类型 微博1
    $iitask->is_retweeted = $is_retweeted;   //原创0转发1
    $iitask->search_key = $q;
    $iitask->repost_num = $data['reposts_count'];
    $result = addWeiboUpdate($iitask);
    return $result;
}
/*
 *  入库到更新表
 * */
function addWeiboUpdate($iitask) {
    global $dsql, $logger;
    $isInsert = true;

        //先查询是否存在--不能重复
        //先查询是否存在
        $sqlsel = "select z_id from weibo_update where d_id = " . $iitask->d_id . " and search_key = '" . $iitask->search_key."'";
        $qrsel = $dsql->ExecQuery($sqlsel);
        if (!$qrsel) {
            $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sqlsel . " error:" . mysql_error());
            return false;
        } else {
            $hasids = array();
            while ($tmpresult = $dsql->GetArray($qrsel, MYSQL_ASSOC)) {
                $hasids[] = $tmpresult['z_id'];
            }
        }
        if (count($hasids) > 0) {
            //以及存在，不能重复的时候，不需要插入
            $isInsert = false;
        }

    if ($isInsert) {
        $sql = "insert into weibo_update(d_id,release_time,type,is_retweeted,search_key,repost_num,status)";
        $sql = $sql . " values(" . $iitask->d_id . "," . $iitask->release_time . "," . $iitask->type . "," . $iitask->is_retweeted . ",'" . $iitask->search_key . "'," . $iitask->repost_num . ",0)";
        $qr = $dsql->ExecQuery($sql);
        if (!$qr) {
            throw new Exception("common.php - addWeiboUpdate() sql: {$sql} - " . mysql_error());
        } else {
            $dsql->FreeResult($qr);
            return true;
        }
    } else {
        $logger->debug(__FILE__ . __LINE__ . " 要插入的数据和数据库id: " . implode(",", $hasids) . "重复");
        return true;
    }
}

/*
 *  更新最新的转发数及状态
 * */
function updateRepost($d_id,$repost_num,$status) {
    global $dsql,$logger;
    $sql = "update weibo_update set repost_num = ".$repost_num.",status= ".$status." where d_id = ".$d_id;
    $logger->debug(__FILE__.__LINE__."sql是".var_export($sql,true));
    $qrsel = $dsql->ExecQuery($sql);
    if (!$qrsel) {
        $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sql . " error:" . mysql_error());
        return false;
    } else {
        return true;
    }
}


/*
 *  入库到email_history(邮件历史表)
 * */
function insert_emailhistory($data) {
    global $dsql,$logger;
    $storagetime = time();
    $sql = "insert into email_history(e_taskid,e_event,e_content,e_status,e_storagetime,e_sendtime)";
    $sql = $sql . " values(" . $data['taskid'] . ",'" . urlencode(json_encode($data['alarm'])) . "','" . urlencode(json_encode($data['content'])) . "'," . $data['status'] .",". $storagetime . ",null)";
    $logger->debug(__FILE__.__LINE__." the sql is :".var_export($sql,true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("common.php - insertEmailhistory() sql: {$sql} - " . mysql_error());
    } else {
        $dsql->FreeResult($qr);
        return true;
    }
}



/*
 *  查询未发送邮件记录
 */
function get_failemail($limit,$time) {
    global $dsql,$logger;
    if(isset($limit)){
        $sqlsel = " limit 0,".$limit;
    }
    if(isset($time)){
        $sqlsel1 = "and e_storagetime>=".$time;
    }
    $sql = "select * from email_history where e_status = 0 ";
    $sql = isset($sqlsel1) ? $sql.$sqlsel1 : $sql;
    $sql = isset($sqlsel) ? $sql.$sqlsel : $sql;
    $logger->info(__FILE__.__LINE__." the sql is :".var_export($sql,true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("common.php - addWeiboUpdate() sql: {$sql} - " . mysql_error());
    } else {
        if( mysql_num_rows($qr) > 0 ){
            while ($emailresult = $dsql->GetArray($qr, MYSQL_ASSOC)) {
                $email[] = $emailresult;
            }
            $logger->info(__FILE__.__LINE__." email data is:".var_export($email,true));
            return $email;
        }else{
            return false;
        }

    }
}



/*
 * 成功发送失败邮件后，修改其在邮件历史表里的状态。
 */
function update_fail_email_status($e_id,$status,$sendtime) {
    global $dsql,$logger;
    $sql = "update email_history set e_status = ".$status.",e_sendtime = ". $sendtime."  where e_id = ".$e_id;
    $logger->debug(__FILE__.__LINE__."sql是".var_export($sql,true));
    $qrsel = $dsql->ExecQuery($sql);
    if (!$qrsel) {
        $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sql . " error:" . mysql_error());
        return false;
    } else {
        return true;
    }
}


/**
 *   查询可用的 账号信息
 */
function selectEmailInformation() {
    global $dsql,$logger;
    $sql = "select * from email_sender_information where status = 1 limit 1";
    $logger->info(__FILE__.__LINE__." the sql is :".var_export($sql,true));
    $qr = $dsql->ExecQuery($sql);
    if (!$qr) {
        throw new Exception("common.php - addWeiboUpdate() sql: {$sql} - " . mysql_error());
    } else {
        if( mysql_num_rows($qr) > 0 ){
            while ($userresult = $dsql->GetArray($qr, MYSQL_ASSOC)) {
                $user = $userresult;
            }
            $logger->info(__FILE__.__LINE__." email data is:".var_export($user,true));
            return $user;
        }else{
            return false;
        }
    }
}
/*
 * 修改对应账号信息的状态，
 *
 *  for : MAIL FROM command failed,550,RP:TRC 126 smtp2   //超过单账号上限了
 */
function updateEmailInformation($id,$status,$where) {
    global $dsql, $logger;
    $logger->info(__FILE__ . __LINE__ . " the id is:" . var_export($id, true));
    if (isset($where)) {
        $sqlsel = " where id =" . $id;
    }
    $sql = "update email_sender_information set status = " . $status;
    $sql = isset($sqlsel) ? $sql . $sqlsel : $sql;
    $logger->debug(__FILE__ . __LINE__ . "sql是" . var_export($sql, true));
    $qrsel = $dsql->ExecQuery($sql);
    if (!$qrsel) {
        $logger->debug(__FILE__ . __LINE__ . " sqlerror:" . $sql . " error:" . mysql_error());
        return false;
    } else {
        return true;
    }
}


/*
 *  和预警事件的对接
 * */
function dock_data($data){
    global $logger;
    $data_string = json_encode($data);
    $logger->info(__FILE__.__LINE__."the data_string is:".var_export($data_string,true));

    $url = "http://".YQ_URL."/solm/a/nms/crisisData/saveJson";
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the send data url is:".var_export($url,true));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, DEFAULT_HTTP_TIMEOUT * 6);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
    );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);


    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE );
    curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response = curl_exec($ch);
    curl_close($ch);
    $logger->info(__FILE__.__LINE__."the response is:".var_export($response,true));

}
/*
 *  修改微博commit方式
 * */
function initWeiboTaskObj(&$task)
{
    //判读当前的数据是否需要提交到solr中
    $commitData = false;
    $task->taskparams->iscommit = $commitData;
}



/*
 * 公共处理方法 addweibo之前的操作
 * $param sourceid    数据类型 微博1
 * $param weibos_info 待入库数据
 * $param isseed      是否种子微博
 * $param grabtype    抓取数据的类型，weibo_limit,weibo_repost_timeline_all,monitor_nickname
 * $return array      处理结果
 * */
function commonHandleData($sourceid, $weibos_info, $isseed, $grabtype){
    global $task;
    initWeiboTaskObj($task);
    $solr_r = addweibo($sourceid, $weibos_info, $isseed, $grabtype);
    return $solr_r;
}

/*
 * 每次任务提交后的数据后，flush一次
 * */
function flushData($url){
    global $logger;
    if(empty($url))
    {
        $reqURL = SOLR_STORE . "update/?type=no_data_commite&commit=true";
    }else
    {
        $reqURL = $url."update/?type=no_data_commite&commit=true";
    }
    $logger->debug(__FILE__ . " " . __LINE__ . " flush weibo data--+-no_doc_update_commit--+-数据提交url:[" . $reqURL . ".");
    $reqData = "no caceh data commit";
    $reqResult = send_solr($reqData, $reqURL);
    if ($reqResult === false) {
        $logger->error(__FILE__ . " " . __LINE__ . " weibo data--+-no_doc_update_commit--+-数据提交异常,错误信息:[" . var_export($reqResult, true) . ".");
        return false;
    }else{
        return true;
    }
}