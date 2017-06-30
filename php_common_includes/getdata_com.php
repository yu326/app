<?php
include_once('userinfo_v2.class.php');
include_once('authorization_v2.class.php');
include_once('commonFun_v2.php');
if(!defined('_NOSESSION_')){
	if(!isset($_SESSION)){
		session_start();
	}

	//判断session是否存在
	if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
	    $arrs["result"]=false;
	    $arrs["msg"]="未登录或登陆超时!";
	    echo json_encode($arrs);
	    exit;
	}
}
initLogger(LOGNAME_WEBAPI);
define( "GET_DATA" , 3 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", GET_DATA);    //需要在include common.php之前，定义CONFIG_TYPE

define('COLOR_PR', 'COLOR_');    //颜色配置名称的前缀
define('COLOR_SEPARATOR', '|');    //颜色值的分隔符

define('RET_CATEGORYNAME', 'categoryname');
define('RET_TOTALCOUNT', 'totalcount');
define('RET_DATALIST', 'datalist');
//传入的参数名
define('ARG_TYPE', 'type');    //获取数据的类型
define('ARG_COLORTYPE', 'colortype');    //获取颜色的类型
define('ARG_TOPN', 'topn');    //获取数据的个数
define('ARG_STARTDATE', 'startdate');    //起始时间
define('ARG_ENDDATE', 'enddate');    //结束时间
define('ARG_USERNAME', 'username');    //用户名
//用于当type为TYPE_SEARCHNAME
define('ARG_BLURNAME', 'blurname');
define('ARG_BLURTYPE', 'blurtype');
define('ARG_USERIDS', 'userids');
define('ARG_BIZIDS', 'bizids');

define('ARG_SEARCHNAME_PAGE', 'page');
define('ARG_SEARCHNAME_PAGESIZE', 'pagesize');
//用于当type为TYPE_SEARCHBUSINESS
define('ARG_BLURBUSINESS', 'blurbusiness');
//用于当type为TYPE_USERID
define('ARG_USERID_USERNAME', 'username');
/*
 * 用于当type为TYPE_EMOORISTATUSES或TYPE_EMOREPOST或
 * TYPE_EMOCOMMENT
 */
define('ARG_EMO_TIMEDRILL', 'timedrill');
define('ARG_EMO_KEYWORD', 'keyword');
//VAL是value的缩写
define('VAL_EMO_MONTH', 'month');
define('VAL_EMO_DAY', 'day');
define('VAL_EMO_HOUR', 'hour');

//当type为TYPE_EMOTION时
define('ARG_EMOTION_TYPE', 'emotype');

/*
 * 2012-1-10:
 * 用于按省返回和按市返回时，
 * 指定国家代码和省代码
 */
//当type为TYPE_AREA_PROVIENCE_USER或TYPE_AREA_PROVIENCE_STATUS时
define('ARG_COUNTRY_CODE', 'countrycode');
//当type为TYPE_AREA_CITY_USER或TYPE_AREA_CITY_STATUS时
define('ARG_PROVINCE_CODE', 'provincecode');
//15:47 2012-02-24 bert 地区联动时
define('ARG_CITY_CODE', 'citycode');
define('ARG_DISTRICT_CODE', 'districtcode');

//用于当type为searchbridgeuser
define('ARG_PROVINCE', 'province');
define('ARG_CITY', 'city');
define('ARG_SEARCH_PAGE', 'currpage');
define('ARG_SEARCH_PAGESIZE2', 'pagesize');
//用于当type为weibolist
define('ARG_WEIBOLIST_PAGE', 'page');
define('ARG_WEIBOLIST_PAGESIZE', 'pagesize');
define('ARG_WEIBOLIST_WEIBOTYPE', 'weibotype');
define('ARG_WEIBOLIST_KEYWORD', 'keyword');
define('ARG_WEIBOLIST_EMOTYPE', 'emotype');
define('ARG_USERID', 'uid');
//用于当type为caselist bert
define('ARG_CASELIST_PAGE', 'page');
define('ARG_CASELIST_PAGESIZE', 'pagesize');
define('ARG_CASELIST_REPOSTCOUNT', 'repostcount');
define('ARG_CASELIST_AREATYPE', 'areatype');
define('ARG_CASELIST_AREACODE', 'areacode');
define('ARG_CASELIST_BUSINESS', 'business');
define('ARG_CASELIST_BRIDGINGQUALITY', 'bridgingquality');
define('ARG_CASELIST_BRIDGINGDEPTH', 'bridgingdepth');
define('ARG_CASELIST_CASETYPE', 'casetype');


//获取数据类型 (type)的具体内容
//2012-1-13 add
define('TYPE_SEARCHBUSINESS', 'searchbusiness');    //根据用户输入进行模糊查询，查出行业信息
define('TYPE_GETBUSINESS', 'getbusiness');    //根据用户输入进行模糊查询，查出行业信息
define('TYPE_GETBUSINESSBYIDS', 'getbusinessbyids');    //根据用户输入进行模糊查询，查出行业信息

define('TYPE_SOURCE_STATUE', 'sourcestatus');    //根据设备统计微博数
define('TYPE_EMOORISTATUSES', 'emooristatuses');    //原创：指定时间段内，按在指定的时间段内，按时间点（月、日、小时）、五种情感分组统计某关键词微博数量
define('TYPE_EMOREPOST', 'emorepost');    //转发：指定时间段内，按在指定的时间段内，按时间点（月、日、小时）、五种情感分组统计某关键词微博数量
define('TYPE_EMOCOMMENT', 'emocomment');    //评论：指定时间段内，按在指定的时间段内，按时间点（月、日、小时）、五种情感分组统计某关键词微博数量
define('TYPE_EMOSTATUS', 'emostatus');    //总体：指定时间段内，按在指定的时间段内，按时间点（月、日、小时）、五种情感分组统计某关键词微博数量
define('TYPE_EMOTION', 'emotion');    //指定关键词，按五种情感分组统计被转发和评论最多的前N个用户
//可在指定的时间段内统计用户在原创中对某个关键词的情感，即统计五种情感的比例和数量。（原创、评论、转发和总体四个饼图）
define('TYPE_EMORATIOORISTATUSES', 'emoratiooristatuses');    //原创
define('TYPE_EMORATIOREPOST', 'emoratiorepost');    //转发
define('TYPE_EMORATIOSTATUSES', 'emoratiostatuses');    //总体

define('TYPE_EMOTYPE', 'emotype');    //返回表情类型和对应的汉字描述
/*
 * 2012-1-10:
 * 添加查询国家代码，根据国家查询省代码，
 * 根据国家和省查询市代码三个接口
 */
define('TYPE_SELECTCOUNTRY', 'selectcountry');
define('TYPE_SELECTPROVINCE', 'selectprovince');
define('TYPE_SELECTCITY', 'selectcity');
define('TYPE_SELECTDISTRICT', 'selectdistrict');
/*
 * 2012-1-14:
 */
define('TYPE_SEARCHBEIDGE', 'searchbridgeuser');
define('TYPE_SEARCHBEIDGE_COUNT', 'bridgeusercount');
define('TYPE_GETBRIDGE', 'bridgeexample');
define('TYPE_GETBRIDGE_USER', 'getcasebyuser');
/*
 * 2012-2-21
 * 传播统计-转发
 */
define('TYPE_BRIDGINGDEPTH', 'bridgingdepthrepost');
define('TYPE_BRIDGINGUSERCOUNT', 'bridgingusercountrepost');
define('TYPE_BRIDGINGQUALITY', 'bridgingqualityrepost');
//到达
define('TYPE_BRIDGINGDEPTHARRIVE', 'bridgingdeptharrive');
define('TYPE_BRIDGINGUSERCOUNTARRIVE', 'bridgingusercountarrive');
define('TYPE_BRIDGINGQUALITYARRIVE', 'bridgingqualityarrive');
/*
 * 2012-03-02 bert
 * 用户高级查询根据查询条件查询用户列表
 * */
define('TYPE_QUERYUSERLIST', 'queryuserlist');

//drill down 用户
define('TYPE_USERLIST', 'userlist');

//获取source信息 (新浪,腾讯)
define('TYPE_GETSOURCE', 'getsource');
define('TYPE_GETSOURCEHOSTNAME', 'getsourcehostname');
define('TYPE_GETVERIFIEDTYPE', 'getverifiedtype');
define('TYPE_SELECTVERIFIED', 'selectverified'); //获取认证子类别 包含个人认证和企业机构
define('TYPE_SELECTWELLUSER', 'selectwelluser');
define('TYPE_SELECTWELLORG', 'selectwellorg');
define('TYPE_SELECTWELLOTHER', 'selectwellother');
define('TYPE_GETVERIFIED', 'getverified');
define('TYPE_GETSOURCEURLBYID', 'getsourceurlbyid');
define('TYPE_GETSOURCENAMEBYID', 'getsourcenamebyid');
define('TYPE_GETFIELDTEXT', 'getfieldtext');//获取字段对应的text，比如地区名称
//返回参数 (RET：return缩写)
//返回的参数名称
define('RET_LABEL', 'label');    //标签，表示内容的类型名称
define('RET_VALUE', 'value');    //标签对应的内容
/*
 * 2012-1-10:
 * 为了使返回的地区信息更明确
 * 使用以下两个标签代表RET_LABEL和
 * RET_VALUE
 */
define('RET_CODE', 'code');    //标签，表示地区时，代替RET_LABEL
define('RET_NAME', 'name');    //标签对应的内容，表示地区时，代替RET_VALUE

define('RET_DATA_COLOR', 'color');    //返回数据的color字段名
//用于当type为TYPE_REPOSTCOUNT或TYPE_COMMENTCOUNT时
define('RET_DATA', 'date');
define('RET_TIME', 'time');
define('RET_USERNAME', 'username');
define('RET_CONTENT', 'content');
define('RET_RETWEETED', 'retweeted');
define('RET_COMMENTCOUNT', 'commentcount');
define('RET_WEIBO_ID', 'weiboid');

define('RET_TOPSTATUSES_LABEL_NAME', 'top');    //返回标签label的内容
define('RET_TOPSTATUSES_OTHER', '其他');
define('RET_USERINFO_WEIBO_COUNT', '微博数');
define('RET_USERINFO_REPOST_COUNT', '转发数');
define('RET_USERINFO_COMMENT_COUNT', '评论数');

/*
 * 根据需求，去掉粉丝数和关注数两项内容的获取
 */
/*
 define('RET_USERINFO_FRIEND_COUNT', '关注数');
 define('RET_USERINFO_FOLLOWER_COUNT', '粉丝数');
 */

//用于当type为TYPE_SEARCHNAME
//define('RET_SEARCHNAME_COUNT', 'count');
define('RET_SEARCHNAME_COUNT', RET_TOTALCOUNT);
//define('RET_SEARCHNAME_USERS', 'users');
define('RET_SEARCHNAME_USERS', RET_DATALIST);
//用于当type为TYPE_SEARCHBUSINESS
define('RET_SEARCHBUSINESSES', 'businesses');
/*
 * 为了减少json数据的数据量，去掉该标签
 */
//define('RET_SEARCHNAME_USERNAME', 'username');

//用于当type为TYPE_USERID
define('RET_USERID_USERID', 'userid');
/*
 * 用于当type为TYPE_EMOORISTATUSES或TYPE_EMOREPOST或
 * TYPE_EMOCOMMENT
 */
define('RET_EMO_CATEGORY', 'category');
define('RET_EMO_DATASET', 'dataset');
define('RET_EMO_SERIESNAME', 'seriesname');
define('RET_EMO_DATA', 'data');

//传播统计
define('RET_BRIDGINGDEPTH_DEPTHNAME', '深度');
//type为userlist
//define('RET_USERLIST_COUNT', 'count');
//define('RET_USERLIST_LIST', 'list');
define('RET_USERLIST_COUNT', RET_TOTALCOUNT);
define('RET_USERLIST_LIST', RET_DATALIST);
define('RET_USERLIST_SCREEN_NAME', 'screen_name');
define('RET_USERLIST_PROFILE_IMAGE_URL', 'profile_image_url');
define('RET_USERLIST_LOCATION', 'location');
define('RET_USERLIST_DESCRIPTION', 'description');
define('RET_USERLIST_FOLLOWERS_COUNT', 'followers_count');
define('RET_USERLIST_FRIENDS_COUNT', 'friends_count');
define('RET_USERLIST_STATUSES_COUNT', 'statuses_count');
define('RET_USERLIST_VERIFIED', 'verified');

//用户高级查询
define('RET_QUERYUSER_ORISTATUSES_COUNT', 'oristatuses_count');
define('RET_QUERYUSER_REPOST_COUNT', 'reposts_count');
define('RET_QUERYUSER_COMMENT_COUNT', 'comment_count');
/*
 * 在define之后include common.php
 * 为了RET_LABEL和
 * RET_VALUE在common.php中起作用
 * 因为getdata.php中需要调用common.php中的
 * format_result函数，而format_result中
 * 使用了RET_LABEL和
 * RET_VALUE
 */
include_once( 'common.php' );

$error_str;    //错误消息
$data_arr;    //返回数据的数组
$data_str;    //返回数据的字符串
$is_need_color = TRUE;    //表示返回结果中是否需要颜色信息，默认返回
$emotion_arr = array();    //存放表情类型


//获取传入参数的值
$arg_type = isset($_GET[ARG_TYPE]) ? $_GET[ARG_TYPE] : '';
$arg_colortype = isset($_GET[ARG_COLORTYPE]) ? $_GET[ARG_COLORTYPE] : 0;
$arg_topn = isset($_GET[ARG_TOPN]) ? $_GET[ARG_TOPN] : '';
$arg_startdate = isset($_GET[ARG_STARTDATE]) ? $_GET[ARG_STARTDATE] : '';
$arg_enddate = isset($_GET[ARG_ENDDATE]) ? $_GET[ARG_ENDDATE] : '';
$arg_username = isset($_GET[ARG_USERNAME]) ? $_GET[ARG_USERNAME] : '';
$arg_blurname = isset($_GET[ARG_BLURNAME]) ? $_GET[ARG_BLURNAME] : '';
$arg_blurtype= isset($_GET[ARG_BLURTYPE]) ? $_GET[ARG_BLURTYPE] : '';
$arg_userids = isset($_GET[ARG_USERIDS]) ? $_GET[ARG_USERIDS] : '';
$arg_bizids  = isset($_GET[ARG_BIZIDS]) ? $_GET[ARG_BIZIDS] : '';
$arg_blurbusiness = isset($_GET[ARG_BLURBUSINESS]) ? $_GET[ARG_BLURBUSINESS] : '';
$arg_searchname_page = isset($_GET[ARG_SEARCHNAME_PAGE]) ? $_GET[ARG_SEARCHNAME_PAGE] : 1;
$arg_searchname_pagesize = isset($_GET[ARG_SEARCHNAME_PAGESIZE]) ? $_GET[ARG_SEARCHNAME_PAGESIZE] : 10;
$arg_userid_username = isset($_GET[ARG_USERID_USERNAME]) ? $_GET[ARG_USERID_USERNAME] : '';
$arg_emo_timedrill = isset($_GET[ARG_EMO_TIMEDRILL]) ? $_GET[ARG_EMO_TIMEDRILL] : '';
$arg_emo_keyword = isset($_GET[ARG_EMO_KEYWORD]) ? $_GET[ARG_EMO_KEYWORD] : '';
$arg_emotion_type = isset($_GET[ARG_EMOTION_TYPE]) ? $_GET[ARG_EMOTION_TYPE] : 0;
$arg_country_code = isset($_GET[ARG_COUNTRY_CODE]) ? $_GET[ARG_COUNTRY_CODE] : 0;
$arg_province_code = isset($_GET[ARG_PROVINCE_CODE]) ? $_GET[ARG_PROVINCE_CODE] : 0;
//15:50 2012-02-24 bert 地区联动
$arg_city_code= isset($_GET[ARG_CITY_CODE]) ? $_GET[ARG_CITY_CODE] : 0;
$arg_district_code= isset($_GET[ARG_DISTRICT_CODE]) ? $_GET[ARG_DISTRICT_CODE] : 0;

//桥接用户列表分页参数
$arg_province = isset($_GET[ARG_PROVINCE]) ? $_GET[ARG_PROVINCE] : '';
$arg_city = isset($_GET[ARG_CITY]) ? $_GET[ARG_CITY] : '';
$arg_search_page = isset($_GET[ARG_SEARCH_PAGE]) ? $_GET[ARG_SEARCH_PAGE] : 1;
$arg_pagesize2 = isset($_GET[ARG_SEARCH_PAGESIZE2]) ? $_GET[ARG_SEARCH_PAGESIZE2] : 10;
$arg_weibolist_page = isset($_GET[ARG_WEIBOLIST_PAGE]) ? $_GET[ARG_WEIBOLIST_PAGE] : 1;
$arg_weibolist_pagesize = isset($_GET[ARG_WEIBOLIST_PAGESIZE]) ? $_GET[ARG_WEIBOLIST_PAGESIZE] : 10;
$arg_weibolist_weibotype = isset($_GET[ARG_WEIBOLIST_WEIBOTYPE]) ? $_GET[ARG_WEIBOLIST_WEIBOTYPE] : '';
$arg_weibolist_keyword = isset($_GET[ARG_WEIBOLIST_KEYWORD]) ? $_GET[ARG_WEIBOLIST_KEYWORD] : '';
$arg_weibolist_emotype = isset($_GET[ARG_WEIBOLIST_EMOTYPE]) ? $_GET[ARG_WEIBOLIST_EMOTYPE] : '';

//bert 桥接案例综合查询
$arg_caselist_page = isset($_GET[ARG_CASELIST_PAGE]) ? $_GET[ARG_CASELIST_PAGE] : 1;
$arg_caselist_pagesize = isset($_GET[ARG_CASELIST_PAGESIZE]) ? $_GET[ARG_CASELIST_PAGESIZE] : 10;
$arg_caselist_repostcount= isset($_GET[ARG_CASELIST_REPOSTCOUNT]) ? $_GET[ARG_CASELIST_REPOSTCOUNT] : '';
$arg_caselist_business= isset($_GET[ARG_CASELIST_BUSINESS]) ? $_GET[ARG_CASELIST_BUSINESS] : '';
$arg_caselist_areatype= isset($_GET[ARG_CASELIST_AREATYPE]) ? $_GET[ARG_CASELIST_AREATYPE] : '';
$arg_caselist_areacode= isset($_GET[ARG_CASELIST_AREACODE]) ? $_GET[ARG_CASELIST_AREACODE] : '';
$arg_caselist_bridgingquality= isset($_GET[ARG_CASELIST_BRIDGINGQUALITY]) ? $_GET[ARG_CASELIST_BRIDGINGQUALITY] : '';
$arg_caselist_bridgingdepth= isset($_GET[ARG_CASELIST_BRIDGINGDEPTH]) ? $_GET[ARG_CASELIST_BRIDGINGDEPTH] : '';
$arg_caselist_casetype= isset($_GET[ARG_CASELIST_CASETYPE]) ? $_GET[ARG_CASELIST_CASETYPE] : '';

//获取source
$arg_userid = isset($_GET[ARG_USERID]) ? $_GET[ARG_USERID] : '';
if($arg_type!=''){
	$dataArr['type'] = $arg_type;
	getdataInit($dataArr);
}

/*
 * 产生一个随机的颜色值
 */
function hex_random()
{
    return dechex(mt_rand(0, 16777215));
}

/*
 * 为每一组数据赋予一个颜色值
 */
function add_color($datas, $color_type)
{
    if (!$color_type)
    {
        $data_count = count($datas);
        for ($i = 0; $i < $data_count; $i++)
        {
            $datas[$i][RET_DATA_COLOR] = hex_random();
        }
        return $datas;
    }
    global $config;
    $config_color_name = COLOR_PR."$color_type";
    if (array_key_exists($config_color_name, $config))
    {
        $color_str = $config[$config_color_name];
        $color_arr = explode(COLOR_SEPARATOR, $color_str);
        $color_count = count($color_arr);
        $data_count = count($datas);
        for ($i = 0; $i < $data_count; $i++)
        {
            if ($color_count <= $i)
            {
                $datas[$i][RET_DATA_COLOR] = hex_random();
            }
            else
            {
                $datas[$i][RET_DATA_COLOR] = $color_arr[$i];
            }
        }
        return $datas;
    }
    else
    {
        set_error_msg("colortype(".$color_type.") does not exist");
    }
}

/*
 * 处理查询结果的通用办法
 */
function common_handle_query($qr)
{
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    global $data_arr;
    while($result = mysql_fetch_array($qr, MYSQL_NUM)){
        $chart[RET_LABEL] = $result[0];
        $chart[RET_VALUE] = $result[1];
        $data_arr[] = $chart;
    }
}

/*
 * 为按年月日返回结果的数据进行格式化
 */
function month_day_hour_handle_query($qr, $is_month, $is_hour)
{
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    global $data_arr;
    if ($is_month)
    {
        while($result = mysql_fetch_array($qr, MYSQL_ASSOC)){
            $result['month'] = ($result['month'] < 10) ? '0'.$result['month'] : $result['month'];
            $chart[RET_LABEL] = $result['year'].'-'.$result['month'];
            $chart[RET_VALUE] = $result['data_count'];
            $data_arr[] = $chart;
        }
    }
    else
    {
        if (!$is_hour)
        {
            while($result = mysql_fetch_array($qr, MYSQL_ASSOC)){
                $result['month'] = ($result['month'] < 10) ? '0'.$result['month'] : $result['month'];
                $result['day'] = ($result['day'] < 10) ? '0'.$result['day'] : $result['day'];
                $chart[RET_LABEL] = $result['year'].'-'.$result['month'].'-'.$result['day'];
                $chart[RET_VALUE] = $result['data_count'];
                $data_arr[] = $chart;
            }
        }
        else
        {
            while($result = mysql_fetch_array($qr, MYSQL_ASSOC)){
                if ($result['hour'] < 10)
                {
                    $chart[RET_LABEL] = '0'.$result['hour'].':00';
                }
                else
                {
                    $chart[RET_LABEL] = $result['hour'].':00';
                }
                $chart[RET_VALUE] = $result['data_count'];
                $data_arr[] = $chart;
            }
        }
    }
}

/*
 * 为data_arr加入color项，并转换为data_str
 * 在脚本执行的最后调用
 */
function handle_data_arr()
{
    global $data_arr, $data_str, $arg_colortype, $is_need_color;
    if ($is_need_color)
    {
        $data_arr = add_color($data_arr, $arg_colortype);
    }
    if (!isset($data_arr[RET_CATEGORYNAME]))
    {
        $tmp_data_arr = array();
        $tmp_data_arr[RET_CATEGORYNAME] = '';
        $tmp_data_arr[RET_TOTALCOUNT] = '';
        $tmp_data_arr[RET_DATALIST] = $data_arr;
        $data_arr = $tmp_data_arr;
    }
    echo json_encode(array($data_arr));
}

/*
 * 处理sql语句，获取用户信息时被调用
 */
function userinfo_handle_sql($sql, $label)
{
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    global $data_arr;
    while($result = mysql_fetch_array($qr, MYSQL_NUM)){
        $chart[RET_LABEL] = $label;
        $chart[RET_VALUE] = $result[0];
        $data_arr[] = $chart;
    }
}
/*
 * 根据用户输入进行模糊查询，查出行业
 * bert 2012-02-26 添加标记
 * 增加查询所有行业的方法
 */
function get_searchbusiness($flag=0)
{
    global $arg_blurbusiness, $is_need_color, $arg_searchname_page, $arg_searchname_pagesize, $arg_bizids;
    //此类型数据，不需要附加颜色信息
    $is_need_color = FALSE;
    //计算limit的起始位置
    $limit_cursor = ($arg_searchname_page - 1) * $arg_searchname_pagesize;
    if($flag===0)
    {
        if (!$arg_blurbusiness)
        {
            set_error_msg("opt ".ARG_BLURBUSINESS." is null");
        }

        $sql = "select business_name from ".DATABASE_BUSINESS."
            where business_name like '%".$arg_blurbusiness."%'";
    }
    else if($flag===1)
    {
        $sql = "select business_name, business_id from ".DATABASE_BUSINESS;
    }
    else if($flag = 3){
        $sql = "select business_name, business_id from ".DATABASE_BUSINESS." where business_id in(".$arg_bizids.")";
    }
    //test
    //echo '<br>'.$sql.'<br>';
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    $num_rows = mysql_num_rows($qr);
    global $data_arr;
    if (!$num_rows)
    {
        $data_arr = array();
    }
    else
    {
        //       $data_arr[RET_SEARCHNAME_COUNT] = $num_rows;
        //      $tmp_arr = array();
        while($result = mysql_fetch_array($qr, MYSQL_NUM))
        {
            unset($tmp_arr);
            $tmp_arr = array();
            $tmp_arr[RET_CODE] = $result[1];
            $tmp_arr[RET_NAME] = $result[0];
            $data_arr[] = $tmp_arr;

            /*
             $tmp_arr[RET_CODE] = $result[1];
             $tmp_arr[RET_NAME] = $result[0];
             $data_arr[] = $tmp_arr;
             */
        }
        /*
         $tmp_arr = array_slice($tmp_arr, $limit_cursor, $arg_searchname_pagesize);
         implode(',', $tmp_arr);
         $data_arr[RET_SEARCHBUSINESSES] = $tmp_arr;
         */
    }
}

/*
 * 初始化表情类型数据
 */
function init_emotion_type()
{
    global $emotion_arr;
    $count_emotion_type = VAL_EMO_LEVEL;
    for ($i = 1; $i <= $count_emotion_type; $i++)
    {
        $emotion_type_code = constant('VAL_EMO_LEVEL_CODE_'.$i);
        $emotion_type_name = constant('VAL_EMO_LEVEL_NAME_'.$i);
        /*
         $tmp_key = $i -1;
         $emotion_arr[$tmp_key]['code'] = $emotion_type_code;
         $emotion_arr[$tmp_key]['name'] = $emotion_type_name;
         */
        $emotion_arr[$emotion_type_code] = $emotion_type_name;
    }
}

/*
 * 格式化emotion_data_arr
 */
function format_emotion_data_arr($data, $is_month, $is_hour, $startdate, $enddate)
{
    global $emotion_arr, $arg_colortype;
    /*
     * 如果起始时间或结束时间不存在，可能会导致
     * 不同表情对应的数据存在不同的起始值或结束
     * 值，导致最后的整体数据不对应，所以，需要
     * 设置time_type变量进行标识
     * 0：起始时间和结束时间都已经设置
     * 1：只没有设置了开始时间
     * 2：只没有设置了结束时间
     * 3：都没有设置
     * 当起始时间和结束时间都设置了的时候，不做
     * 任何额外操作；
     * 如果只是没有设置开始时间，则需要设置最小时间戳
     * 对每一条数据都要进行比较，如果小于最小时间
     * 戳，则将其设置为最小时间戳；
     * 如果只是没有设置结束时间，同上，区别在于是设置
     * 最大时间戳；
     * 如果都没有设置，则最小最大时间戳都需要设置。
     * 另外，需要在查询结果中把微博的创建时间加上，这样
     * 便于设置最小最大时间戳
     */
    $min_timestamp = 0;
    $max_timestamp = 0;
    $time_type = 0;
    if (!$startdate)
    {
        $time_type++;
    }
    if (!$enddate)
    {
        $time_type += 2;
    }

    $res_data = array();
    if (!$data)
    {
        return $res_data;
    }
    $emotion_arr_keys = array_keys($emotion_arr);
    foreach ($emotion_arr_keys as $emotion_arr_key)
    {
        $res_data[$emotion_arr_key][RET_EMO_SERIESNAME] = $emotion_arr_key;
    }
    $chart = array();
    foreach ($data as $data_value)
    {
        /*
         * 设置最大最小时间戳
         * 如果$time_type为0，即起始时间和结束时间
         * 全部设置了，则跳过该操作
         */
        switch ($time_type)
        {
            case 0:
                break;
            case 1:
                if (!$min_timestamp)
                {
                    $min_timestamp = $data_value['created_at'];
                }
                else
                {
                    $min_timestamp = min($min_timestamp, $data_value['created_at']);
                }
                break;
            case 2:
                $max_timestamp = max($max_timestamp, $data_value['created_at']);
                break;
            case 3:
                if (!$min_timestamp)
                {
                    $min_timestamp = $data_value['created_at'];
                }
                else
                {
                    $min_timestamp = min($min_timestamp, $data_value['created_at']);
                }
                $max_timestamp = max($max_timestamp, $data_value['created_at']);
                break;
            default:
                break;
        }

        if ($is_month)
        {
            $data_value['month'] = ($data_value['month'] < 10) ? '0'.$data_value['month'] : $data_value['month'];
            $chart[RET_LABEL] = $data_value['year'].'-'.$data_value['month'];
            $chart[RET_VALUE] = $data_value['status_count'];
            $res_data[$data_value['expression_value']]['tmp'][] = $chart;
        }
        else if (!$is_hour)
        {
            $data_value['month'] = ($data_value['month'] < 10) ? '0'.$data_value['month'] : $data_value['month'];
            $data_value['day'] = ($data_value['day'] < 10) ? '0'.$data_value['day'] : $data_value['day'];
            $chart[RET_LABEL] = $data_value['year'].'-'.$data_value['month'].'-'.$data_value['day'];
            $chart[RET_VALUE] = $data_value['status_count'];
            $res_data[$data_value['expression_value']]['tmp'][] = $chart;
        }
        else
        {
            $data_value['hour'] = ($data_value['hour'] < 10) ? '0'.$data_value['hour'] : $data_value['hour'];
            $chart[RET_LABEL] = $data_value['hour'].':00';
            $chart[RET_VALUE] = $data_value['status_count'];
            $res_data[$data_value['expression_value']]['tmp'][] = $chart;
        }
    }
    /*
     * 更具最大最小时间戳
     * 设置$startdate和$enddate
     * 但是有一个问题需要解决，就是当起始时间或
     * 结束时间没有设置时，则按月返回时，需要返回
     * 整年信息；按日返回，需要返回整月的信息。
     * 所以：
     * 按月返回
     *  如果起始时间没有设置，则将开始时间设置成当
     *  年的第一个月；
     *  如果结束时间没有设置，则将结束时间设置成当
     *  年的最后一个月；
     * 按天返回
     *  如果起始时间没有设置，则将开始时间设置成当
     *  月的第一天；
     *  如果结束时间没有设置，则将结束时间设置成当
     *  月的最后一天；
     */
    switch ($time_type)
    {
        case 0:
            break;
        case 1:
            if ($is_month)
            {
                $startdate = strtotime(date('Y-01-01', $min_timestamp));
            }
            else
            {
                $startdate = strtotime(date('Y-m-01', $min_timestamp));
            }
            break;
        case 2:
            if ($is_month)
            {
                $year_str = date('Y', $max_timestamp);
                $year_str += 1;
                $enddate = strtotime($year_str.'-01-01') - 1;
            }
            else
            {
                $year_month_str = date('Y-m', $max_timestamp);
                $year_month_arr = explode('-', $year_month_str);
                if ($year_month_arr[1] == 12)
                {
                    $year_month_arr[0] += 1;
                    $year_month_arr[1] = '01';
                }
                else
                {
                    $year_month_arr[1] = intval($year_month_arr[1]) + 1;
                    if ($year_month_arr[1] < 10)
                    {
                        $year_month_arr[1] = '0'.$year_month_arr[1];
                    }
                }
                $year_month_str = $year_month_arr[0].'-'.$year_month_arr[1].'-01';
                $enddate = strtotime($year_month_str) - 1;
            }
            break;
        case 3:
            if ($is_month)
            {
                $startdate = strtotime(date('Y-01-01', $min_timestamp));
                $year_str = date('Y', $max_timestamp);
                $year_str += 1;
                $enddate = strtotime($year_str.'-01-01') - 1;
            }
            else
            {
                $startdate = strtotime(date('Y-m-01', $min_timestamp));
                $year_month_str = date('Y-m', $max_timestamp);
                $year_month_arr = explode('-', $year_month_str);
                if ($year_month_arr[1] == 12)
                {
                    $year_month_arr[0] += 1;
                    $year_month_arr[1] = '01';
                }
                else
                {
                    $year_month_arr[1] = intval($year_month_arr[1]) + 1;
                    if ($year_month_arr[1] < 10)
                    {
                        $year_month_arr[1] = '0'.$year_month_arr[1];
                    }
                }
                $year_month_str = $year_month_arr[0].'-'.$year_month_arr[1].'-01';
                $enddate = strtotime($year_month_str) - 1;
            }
            break;
        default:
            break;
    }

    $dataset = array();
    $category = array();
    $i = 0;
    foreach ($res_data as $res_data_arr_value)
    {
        $dataset[$i][RET_EMO_SERIESNAME] = $emotion_arr[$res_data_arr_value[RET_EMO_SERIESNAME]];
        unset($tmp_data_arr);
        $tmp_data_arr = array();
        $tmp_data_arr = isset($res_data_arr_value['tmp']) ? $res_data_arr_value['tmp'] : array();
        $tmp_data_arr = format_result($tmp_data_arr, $startdate, $enddate, $is_month, $is_hour);
        foreach ($tmp_data_arr as $tmp_data_arr_value)
        {
            if (!$i)
            {
                $category[][RET_LABEL] = $tmp_data_arr_value[RET_LABEL];
            }
            $dataset[$i][RET_EMO_DATA][][RET_VALUE] = $tmp_data_arr_value[RET_VALUE];
        }
        $i++;
    }
    $dataset = add_color($dataset, $arg_colortype);
    $ret_arr = array();
    $ret_arr[RET_EMO_CATEGORY] = $category;
    $ret_arr[RET_EMO_DATASET] = $dataset;
    return $ret_arr;
}

/*
 * 原创：指定时间段内，按在指定的时间段内，
 * 按时间点（月、日、小时）、五种情感分组
 * 统计某关键词微博数量
 * $status_type:
 *  0：总体
 *  1：原创
 *  2：转发
 */
function get_emotion_status($status_type)
{
    global $emotion_arr, $arg_startdate,
    $arg_enddate, $arg_emo_timedrill,
    $arg_emo_keyword, $is_need_color;
    $is_need_color = false;
    $is_month = true;
    $is_hour = false;
    $emotion_data_arr = array();
    if (!$arg_emo_keyword)
    {
        set_error_msg('arg '.ARG_EMO_KEYWORD.' is null');
    }
    $where = '';
    if ($arg_startdate)
    {
        $where .= " and a.created_at>{$arg_startdate}";
    }
    if ($arg_enddate)
    {
        $where .= " and a.created_at<{$arg_enddate}";
    }
    if (1 == $status_type)
    {
        $where .= " and (a.retweeted_status is null or a.retweeted_status='') ";
    }
    else if (2 == $status_type)
    {
        $where .= " and a.retweeted_status is not null and a.retweeted_status<>'' ";
    }
    switch ($arg_emo_timedrill)
    {
        case VAL_EMO_MONTH:
            $group_by = ' group by b.expression_value, a.year, a.month';
            $order_by = ' order by b.expression_value DESC';
            break;
        case VAL_EMO_DAY:
            $is_month = false;
            $group_by = ' group by b.expression_value, a.year, a.month, a.day';
            $order_by = ' order by b.expression_value DESC';
            break;
        case VAL_EMO_HOUR:
            if (!$arg_startdate || !$arg_enddate)
            {
                set_error_msg('startdate or enddate is null');
            }
            $date_startdate = date('Y-m-d', $arg_startdate);
            $date_enddate = date('Y-m-d', $arg_enddate);
            if ($date_startdate != $date_enddate)
            {
                set_error_msg("startdate(".$arg_startdate.") and enddate(".$arg_enddate.") are not in same day");
            }
            $is_month = false;
            $is_hour = true;
            $group_by = ' group by b.expression_value, a.year, a.month, a.day, a.hour';
            $order_by = ' order by b.expression_value DESC';
            break;
        default:
            set_error_msg('arg '.ARG_EMO_TIMEDRILL.' has a wrong value or null');
    }
    /*
     * 查询结果中添加created_at
     * 为了在format_emotion_data_arr中设置最大最小时间
     * 戳时使用
     */
    $sql = "select b.expression_value, a.year, a.month, a.day, a.hour,
            count(b.status_id) as status_count, 
            a.created_at
            from `".DATABASE_WEIBO."` as a inner join 
            `".DATABASE_STATUS_EXPRESSION_KEYWORD."` as b 
            on a.id=b.status_id
            where b.keyword='{$arg_emo_keyword}'{$where}
            {$group_by}{$order_by}";
            //test
            //echo $sql;
            $qr = mysql_query($sql);
            if (!$qr)
            {
                set_error_msg("sql error:".mysql_error());
            }
            else if (($num_rows = mysql_num_rows($qr)) == 0)
            {
                //set_error_msg('do not have data in database');
                $emotion_data_arr = array();
            }
            while($result = mysql_fetch_array($qr, MYSQL_ASSOC))
            {
                $emotion_data_arr[] = $result;
            }
            init_emotion_type();
            global $data_arr;
            $data_arr = format_emotion_data_arr($emotion_data_arr, $is_month, $is_hour, $arg_startdate, $arg_enddate);
}

/*
 * 根据转发数+评论数获取关键词的topn个用户
 */
function get_emotion_topn_user()
{
    global $config, $arg_topn, $arg_emotion_type, $arg_emo_keyword;
    if (!$arg_topn)
    {
        $arg_topn = $config['DEFAULT_TOP'];
    }
    $sql = "select d.screen_name,c.total_count
            from
            (select a.userid, sum(b.reposts_count+b.comments_count) as total_count
            from `".DATABASE_WEIBO."` as a inner join `".DATABASE_STATUS_EXPRESSION_KEYWORD."` as b
            on a.id=b.status_id
            where b.keyword='{$arg_emo_keyword}' and 
            b.expression_value='{$arg_emotion_type}'
            group by a.userid
            order by b.reposts_count+b.comments_count limit {$arg_topn}) as c inner join `user_new` as d
            on c.userid=d.id";
    //test bert
    //echo $sql;
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    common_handle_query($qr);
}

/*
 * 原创：指定时间段内，按在指定的时间段内，
 * 按时间点（月、日、小时）、五种情感分组
 * 统计某关键词情感比例
 * $status_type:
 *  0：总体
 *  1：原创
 *  2：转发
 */
function get_emotion_ratio($status_type)
{
    global $arg_startdate, $arg_enddate, $arg_emo_keyword;
    if (!$arg_emo_keyword)
    {
        set_error_msg('arg '.ARG_EMO_KEYWORD.' is null');
    }
    init_emotion_type();
    global $emotion_arr;
    $where = '';
    if ($arg_startdate)
    {
        $where .= " and a.created_at>{$arg_startdate}";
    }
    if ($arg_enddate)
    {
        $where .= " and a.created_at<{$arg_enddate}";
    }
    if (1 == $status_type)
    {
        $where .= " and (a.retweeted_status is null or a.retweeted_status='') ";
    }
    else if (2 == $status_type)
    {
        $where .= " and a.retweeted_status is not null and a.retweeted_status<>'' ";
    }
    $sql = "select b.expression_value, count(b.status_id)
            from `".DATABASE_WEIBO."` as a inner join 
            `".DATABASE_STATUS_EXPRESSION_KEYWORD."` as b 
            on a.id=b.status_id
            where b.keyword='{$arg_emo_keyword}'{$where} 
            group by b.expression_value";
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    common_handle_query($qr);
    global $data_arr;
    $data_arr_count = count($data_arr);
    for ($i = 0; $i < $data_arr_count; $i++)
    {
        $data_arr[$i][RET_LABEL] = $emotion_arr[$data_arr[$i][RET_LABEL]];
    }
}

/*
 * 根据设备统计微博数
 */
function get_source_status()
{
    global $arg_topn;
    $limit_str = '';
    if (!empty($arg_topn))
    {
        $limit_str = " limit {$arg_topn}";
    }
    $sql = "select `source`, count(id)
            from `".DATABASE_WEIBO."` 
            group by `source`{$limit_str}";
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    common_handle_query($qr);
}

/*
 * 获取用户个人信息
 */
function get_emotion_type()
{
    global $is_need_color, $data_arr;
    $is_need_color = false;
    init_emotion_type();
    global $emotion_arr;
    foreach ($emotion_arr as $emotion_arr_key => $emotion_arr_value)
    {
        unset($tmp_emotion_arr);
        $tmp_emotion_arr = array();
        $tmp_emotion_arr[RET_LABEL] = $emotion_arr_key;
        $tmp_emotion_arr[RET_VALUE] = $emotion_arr_value;
        $data_arr[] = $tmp_emotion_arr;
    }
}

/*
 * 获取地区代码
 * $area_level:
 * 1：返回国家代码
 * 2：返回省代码
 * 3：返回市代码
 * 4: 返回县(区)代码
 */
function get_area_code_by_area_level($area_level)
{
    global $arg_country_code, $arg_province_code, $arg_city_code, $arg_district_code,
    $is_need_color, $data_arr;
    $is_need_color = false;
    $field = '';
    $where = '';
    switch ($area_level)
    {
        case 1:
            $field = 'country';
            $where = " `province` is null
            and `country` is not null";
            break;
        case 2:
            if (empty($arg_country_code))
            {
                set_error_msg('opt '.ARG_COUNTRY_CODE.' is null');
            }
            $field = 'province';
            $where = " `country`='{$arg_country_code}'
            and `city` is null 
            and `province` is not null";
            break;
        case 3:
            if (empty($arg_country_code))
            {
                set_error_msg('opt '.ARG_COUNTRY_CODE.' is null');
            }
            if (empty($arg_province_code))
            {
                set_error_msg('opt '.ARG_PROVINCE_CODE.' is null');
            }
            $field = 'city';
            $where = " `country`='{$arg_country_code}'
            and `province`='{$arg_province_code}' 
            and `district` is null 
            and `city` is not null";
            break;
        case 4:
            if(empty($arg_country_code))
            {
                set_error_msg('opt '.ARG_COUNTRY_CODE.' is null');
            }
            if (empty($arg_province_code))
            {
                set_error_msg('opt '.ARG_PROVINCE_CODE.' is null');
            }
            if (empty($arg_city_code))
            {
                set_error_msg('opt '.ARG_CITY_CODE.' is null');
            }
            $field = 'district';
            $where = " `country`='{$arg_country_code}'
            and `province`='{$arg_province_code}' 
            and `city` in ({$arg_city_code})
            and `district` is not null";
            break;

        default:
            set_error_msg("arg area_level({$area_level}) has a error");
            break;
    }
    $sql = "select {$field}, `name`
        from ".DATABASE_AREA."
        where {$where}";
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    while ($result = mysql_fetch_array($qr, MYSQL_NUM))
    {
        unset($tmp_arr);
        $tmp_arr = array();
        $tmp_arr[RET_CODE] = $result[0];
        $tmp_arr[RET_NAME] = $result[1];
        $data_arr[] = $tmp_arr;
    }
}

//获取桥接用户列表

function get_bridge_user()
{
    global $totalCount,$arg_pagesize2, $arg_search_page, $arg_city, $arg_province;

    //计算limit的起始位置
    $limit_cursor = ($arg_search_page - 1) * $arg_pagesize2;

    /*
     if ()
     {
     set_error_msg("opt ".ARG_BLURNAME." is null");
     }
     */
    if($arg_city!=null || $arg_city!="")
    {
        $totalCount ="select count(*) from user_new where (is_celebrity_friend>0 and  is_celebrity_friend<3)
            and (is_celebrity_follower>0 and is_celebrity_follower<3) where city_code='".$arg_city."'"; 
        $sql = "select id,screen_name,b.name,gender,followers_count,friends_count,is_celebrity_friend,is_celebrity_follower from user_new as a,area as b where (is_celebrity_friend>0 and  is_celebrity_friend<3)
            and (is_celebrity_follower>0 and is_celebrity_follower<3) where city_code='".$arg_city."' and a.city_code = b.area_code order by followers_count desc limit ".$limit_cursor.",".$arg_pagesize2;

    }
    else
    {
        if($arg_province!=null || $arg_province!="")
        {
            $totalCount = "select count(*) from user_new where (is_celebrity_friend>0 and  is_celebrity_friend<3)
            and (is_celebrity_follower>0 and is_celebrity_follower<3) where province_code='".$arg_province."'";
            $sql = "select id,screen_name,b.name,gender,followers_count,friends_count,is_celebrity_friend,is_celebrity_follower from user_new as a,area as b where (is_celebrity_friend>0 and  is_celebrity_friend<3)
            and (is_celebrity_follower>0 and is_celebrity_follower<3) where province_code='".$arg_province."' and a.city_code = b.area_code order by followers_count desc limit ".$limit_cursor.",".$arg_pagesize2;  
        }
        else
        {
            $totalCount="select count(*) from user_new where (is_celebrity_friend>0 and  is_celebrity_friend<3)
            and (is_celebrity_follower>0 and is_celebrity_follower<3)";

            $sql = "select id,screen_name,b.name,gender,followers_count,friends_count,is_celebrity_friend,is_celebrity_follower from user_new as a,area as b where (is_celebrity_friend>0 and  is_celebrity_friend<3)
            and (is_celebrity_follower>0 and is_celebrity_follower<3) and a.province_code = b.area_code order by followers_count desc limit ".$limit_cursor.",".$arg_pagesize2;
        }

    }

    //echo '<br>'.$sql.'<br>';exit;
    $qr2 = mysql_query($totalCount);
    $qr = mysql_query($sql);

    //common_handle_query($qr);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    if (!$qr2)
    {
        set_error_msg("sql error:".mysql_error());
    }

    while($result = mysql_fetch_array($qr2, MYSQL_NUM))
    {
        $num_rows = $result[0];

    }
    //$num_rows = mysql_num_rows($qr);
    global $data_arr;
    if (!$num_rows)
    {
        $data_arr = array();
    }
    else
    {
        $data_arr[RET_SEARCHNAME_COUNT] = $num_rows;
        $tmp_arr = array();
        while($result = mysql_fetch_array($qr, MYSQL_NUM))
        {
            //$tmp_arr[] = $result[1];
            $tmp_arr["id"] = $result[0];
            $tmp_arr["screen_name"] = $result[1];
            $tmp_arr["name"] = $result[2];
            $tmp_arr["gender"] = $result[3];
            $tmp_arr["followers_count"] = $result[4];
            $tmp_arr["friends_count"] = $result[5];
            $tmp_arr["is_celebrity_friend"] = $result[6];
            $tmp_arr["is_celebrity_follower"] = $result[7];$data_arr['children'][] = $tmp_arr;
        }
    }
}


//获取桥接案例列表
function get_bridge_example()
{
    global $totalCount,$arg_pagesize2, $arg_search_page;

    //计算limit的起始位置
    $limit_cursor = ($arg_search_page - 1) * $arg_pagesize2;

    /*
     if ()
     {
     set_error_msg("opt ".ARG_BLURNAME." is null");
     }
     */
    $totalCount ="select count(*) from  bridge_case_id";
    $sql = "select a.id,a.name,count(b.case_id) as sumcount, b.case_type,b.case_id from bridge_case_id as a inner join bridge_case as b
on a.id = b.case_id group by b.case_id limit ".$limit_cursor.",".$arg_pagesize2;




    // echo '<br>'.$sql.'<br>';exit;
    $qr2 = mysql_query($totalCount);
    $qr = mysql_query($sql);

    //common_handle_query($qr);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    if (!$qr2)
    {
        set_error_msg("sql error:".mysql_error());
    }

    while($result = mysql_fetch_array($qr2, MYSQL_NUM))
    {
        $num_rows = $result[0];

    }
    //$num_rows = mysql_num_rows($qr);
    global $data_arr;
    if (!$num_rows)
    {
        $data_arr = array();
    }
    else
    {
        $data_arr[RET_SEARCHNAME_COUNT] = $num_rows;
        $tmp_arr = array();
        while($result = mysql_fetch_array($qr, MYSQL_NUM))
        {
            //$tmp_arr[] = $result[1];
            $tmp_arr["id"] = $result[0];
            $tmp_arr["case_type"] = $result[2];
            $tmp_arr["name"] = $result[1];
            $tmp_arr["sumcount"] = $result[3];
            $tmp_arr["caseid"] = $result[4];
            //tmp_arr["followers_count"] = $result[4];
            //$tmp_arr["friends_count"] = $result[5];
            //$tmp_arr["is_celebrity_friend"] = $result[6];
            //$tmp_arr["is_celebrity_follower"] = $result[7];$data_arr['children'][] = $tmp_arr;
        }
    }
}

//根据用户ID获取桥接案例列表
function get_bridgecase_byuser($userid)
{ global $totalCount,$arg_pagesize2, $arg_search_page;
//计算limit的起始位置
$limit_cursor = ($arg_search_page - 1) * $arg_pagesize2;

/*
 if ()
 {
 set_error_msg("opt ".ARG_BLURNAME." is null");
 }
 */
//2是代表桥接微博
$totalCount ="select count(*) from bridge_case where repost_user_id ='".$userid."' and is_celebrity_status=2";
$sql = "select case_id,count(case_id) as sumcount,case_type from bridge_case where repost_user_id = '".$userid."' and is_celebrity_status=2 group by case_id
        limit ".$limit_cursor.",".$arg_pagesize2;




// echo '<br>'.$sql.'<br>';exit;
$qr2 = mysql_query($totalCount);
$qr = mysql_query($sql);

//common_handle_query($qr);
if (!$qr)
{
    set_error_msg("sql error:".mysql_error());
}
if (!$qr2)
{
    set_error_msg("sql error:".mysql_error());
}

while($result = mysql_fetch_array($qr2, MYSQL_NUM))
{
    $num_rows = $result[0];

}
//$num_rows = mysql_num_rows($qr);
global $data_arr;
if (!$num_rows)
{
    $data_arr = array();
}
else
{
    $data_arr[RET_SEARCHNAME_COUNT] = $num_rows;
    $tmp_arr = array();
    while($result = mysql_fetch_array($qr, MYSQL_NUM))
    {
        //$tmp_arr[] = $result[1];
        $tmp_arr["id"] = $result[0];
        $tmp_arr["case_type"] = $result[1];
        $tmp_arr["name"] = "";
        $tmp_arr["sumcount"] = $result[3];
        //tmp_arr["followers_count"] = $result[4];
        //$tmp_arr["friends_count"] = $result[5];
        //$tmp_arr["is_celebrity_friend"] = $result[6];
        //$tmp_arr["is_celebrity_follower"] = $result[7];$data_arr['children'][] = $tmp_arr;
    }
}
}

//共用方法，计算桥接案例深度
function common_count_casedepth($caseid, $casetype=-1){
    $sqldepth = "select * from `bridge_case` where `case_id` = ".$caseid."";
    if(isset($casetype) && $casetype!=-1)
    {
        $sqldepth .= " and `case_type`=".$casetype;
    }
    $resdepth = mysql_query($sqldepth);
    if(!$resdepth)
    {
        set_error_msg("sql error:".mysql_error());
    }
    if(mysql_num_rows($resdepth))
    {
        $countdepth = 0;
        $maxdepth = 0;
        while($resultdepth = mysql_fetch_array($resdepth, MYSQL_ASSOC))
        {
            $countdepth++;
            if($resultdepth['is_celebrity_status'] == 1) //判断is_celebrity_status计算个数
            {
                $countdepth = 0;
            }
            if($countdepth > $maxdepth)
            {
                $maxdepth = $countdepth;
            }
        }
        return $maxdepth;
    }
}

//按桥接深度分组统计
function get_bridgingdepth($case_type=1)
{
    global $config, $arg_topn, $data_arr;
    if (!$arg_topn)
    {
        $arg_topn = $config['DEFAULT_TOP'];
    }
    $sql = "select * from `bridge_case_id`";
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    $depthnum = array();
    for($j=1; $j<=$arg_topn; $j++)
    {
        $depthnum[RET_BRIDGINGDEPTH_DEPTHNAME.$j] = 0;
    }
    while($result = mysql_fetch_array($qr, MYSQL_ASSOC))
    {
        $casedepth = common_count_casedepth($result['id'], $case_type);
        if($casedepth!=null)
        {
            $depthnum[RET_BRIDGINGDEPTH_DEPTHNAME.$casedepth]++;
        }
    }
    foreach($depthnum as $key=>$value)
    {
        $chart[RET_LABEL] = $key;
        $chart[RET_VALUE] = $value;
        $data_arr[] = $chart;
    }
}
/**
 * 
 * 生成sql语句
 * @param 字段关系对象
 * @param 字段数组
 * @param 对比的字段名
 */
function filter_where_sql($re, $filtervalue, $classifyqueryfield = ""){
    global $logger;
    $logger->debug(__FILE__." ".__FUNCTION__." enter");
    $str="";
	$tmpArr = array();
	if(!empty($re["fields"])){
		foreach($re["fields"] as $key=>$value){
		    $isclassifyquery = $classifyqueryfield == $filtervalue[$value]['fieldname'];
			$rest = filterrelation2sql($filtervalue[$value], $isclassifyquery);
			if($rest!=""){
				$tmpArr[] = $rest;
			}
		}
        if(!empty($tmpArr)){
			$opt = strtoupper($re["opt"]);  //逻辑拼接用大写 AND OR
            $str .= implode(" ".$opt." ", $tmpArr);
        }
	}
	if(!empty($re["filterlist"])){
		foreach($re["filterlist"] as $key=>$value){
			$res = filter_where_sql($value, $filtervalue, $classifyqueryfield);
			if($res!=""){
				if($str!=""){
					$opt = " ".$re["opt"];
					$str .= $opt." (".$res.") ";
				}
				else{
					$str .= "(".$res.")";
				}
			}
		}
	}
	$logger->debug(__FILE__." ".__FUNCTION__." exit");
	return $str;
}
function filter_where_folfri($re, $filtervalue, $classifyqueryfield = "", $excludeField){
    global $logger;
    $logger->debug(__FILE__." ".__FUNCTION__." enter");
    $str="";
	$tmpArr = array();
	if(!empty($re["fields"])){
		foreach($re["fields"] as $key=>$value){
			if($excludeField != $filtervalue[$value]["fieldname"]){ //只处理一个字段
				$isclassifyquery = $classifyqueryfield == $filtervalue[$value]['fieldname'];
				$rest = filterrelation2sql($filtervalue[$value], $isclassifyquery);
				if($rest!=""){
					$tmpArr[] = $rest;
				}
			}
		}
        if(!empty($tmpArr)){
			$opt = strtoupper($re["opt"]);  //逻辑拼接用大写 AND OR
            $str .= implode(" ".$opt." ", $tmpArr);
        }
	}
	if(!empty($re["filterlist"])){
		foreach($re["filterlist"] as $key=>$value){
			$res = filter_where_folfri($value, $filtervalue, $classifyqueryfield, $excludeField);
			if($res!=""){
				$opt = " ".$re["opt"];
				$str .= $opt." (".$res.") ";
			}
		}
	}
	$logger->debug(__FILE__." ".__FUNCTION__." exit");
	return $str;
}
function get_verified(){
	global $data_arr, $is_need_color;
	$is_need_color = false;
	$sourceid = isset($_GET['sourceid']) ? $_GET['sourceid'] : '';
	$verifiedarr = verifiedArr();

    $data_arr[RET_USERLIST_COUNT] = count($verifiedarr);
    $data_arr[RET_CATEGORYNAME] = '';
    $data_arr[RET_USERLIST_LIST] = $verifiedarr;
}
function get_verifiedtype($vt){
	global $data_arr, $is_need_color;
	$is_need_color = false;
	$sourceid = isset($_GET['sourceid']) ? $_GET['sourceid'] : '';
	$verifiedtype = isset($_GET['verified']) ? $_GET['verified'] : '';
	$verifiedarr = verifiedTypeArr();
	$retlist = array();

	if($sourceid != ''){
		if($sourceid == '1'){
			$retlist = $verifiedarr;
		}
	}
	else{
		$retlist = $verifiedarr;
	}
	//根据认证类型返回对应的
	if($verifiedtype === 0){
		$retlist[] = $verifiedarr['0'];
	}
	else if($verifiedtype === 1){
		$retlist[] = $verifiedarr['1'];
	}
	else{
		$retlist = $verifiedarr;
	}

	if($vt === 1){
		$retlist = array();
		$retlist = array_merge($verifiedarr['1'], $verifiedarr['3']);
	}
	else if($vt === 2){
		$retlist = array();
		$tmp =  $verifiedarr['2'];
		foreach($tmp as $ti=>$titem){
			if($titem["code"] >= 200){
				$retlist[] = $titem;
			}
		}
	}
	else if($vt === 3){
		$retlist = array();
		$retlist = $verifiedarr['3'];
	}
	else if($vt === 4){
		$retlist = array();
		$retlist = $verifiedarr['4'];
	}
    $data_arr[RET_USERLIST_COUNT] = count($retlist);
    $data_arr[RET_CATEGORYNAME] = '';
    $data_arr[RET_USERLIST_LIST] = $retlist;
}
function get_sourcename(){
	global $data_arr, $is_need_color, $logger;
	$is_need_color = false;
	$sourceid = isset($_GET['sourceid']) ? $_GET['sourceid'] : '';
	$sourcename = get_source_name($sourceid);
	$data_arr[RET_CATEGORYNAME] = '';
	$data_arr[RET_USERLIST_LIST] = $sourcename;
}
function get_sourceurl(){
	global $data_arr, $is_need_color, $logger;
	$is_need_color = false;
	$sourceid = isset($_GET['sourceid']) ? $_GET['sourceid'] : '';
	$hostarr = get_source_url($sourceid);
	if(count($hostarr) > 0){
		$host = $hostarr[0];
	}
	$data_arr[RET_CATEGORYNAME] = '';
	$data_arr[RET_USERLIST_LIST] = $host;
}
/*根据source_host查出 name*/
function get_sourcehostname(){
	global $data_arr, $is_need_color, $logger;
	$is_need_color = false;
    $host = isset($_GET['host']) ? $_GET['host'] : NULL;
	$hostarr = array($host);
    $data_arr[RET_CATEGORYNAME] = '';
	$data_arr[RET_USERLIST_LIST] = getSourcenameFromHost($hostarr);
}
function get_source(){
	global $data_arr, $is_need_color, $logger;
	$is_need_color = false;
	$start = isset($_GET['page']) ? $_GET['page'] - 1 : NULL;
	$pagesize = isset($_GET['pagesize']) ? $_GET['pagesize'] : NULL;
    $limit_cursor = $start * $pagesize;
	$field = isset($_GET['queryfield']) ? $_GET['queryfield'] : "source_host"; //查询字段
	$searchtxt = isset($_GET['searchtxt']) ? $_GET['searchtxt'] : NULL; //搜索时用户填的值
	$srchostid = isset($_GET['srchostid']) ? $_GET['srchostid'] : NULL; //搜索时用户填的值
	$searchhost = getSourceHostByName($searchtxt, $srchostid);
	if(!empty($searchhost)){
		$searchtxt = $searchhost;
	}
	$result = getSourceHostFromSolr($limit_cursor, $pagesize, $field, $searchtxt, $srchostid);
	$hostarr = array(); //solr返回结果 sourceid
	if(count($result['datalist']) > 0){
		foreach($result['datalist'] as $ri=>$ritem){
			if(!empty($ritem)){
				$sid = $ritem[$field];
				if(!in_array($sid, $hostarr)){
					$hostarr[] = $sid;
				}
			}
		}
	}
    $data_arr[RET_USERLIST_COUNT] = $result['totalcount'];
    $data_arr[RET_CATEGORYNAME] = '';
	$hostnamearr = getSourcenameFromHost($hostarr, $srchostid);
	//根据name去重,控件选择时, 不同域名对应相同的名称,只保留一个域名,
	//当选择域名查询时,会根据域名查出对应的sourceid, 使用sourceid来查询对应源的数据 
	//同时也能保证搜索到这个站点所有域名的数据
	$retarr = array();
	$tmparr = array();
	foreach($hostnamearr as $hi=>$hitem){
		if(!in_array($hitem['name'], $tmparr)){
			$tmparr[] = $hitem['name'];
			$retarr[] = $hitem;
		}
	}
	$data_arr[RET_USERLIST_LIST] = $retarr;
}
//按桥接用户数量分组统计
function get_bridgingusercount($case_type=1)
{
}
//按桥接质量分组统计
function get_bridgingquality($case_type=1)
{
}
//test
//init_emotion_type();

//检查开始时间不能大于结束时间
if (!empty($arg_startdate) && !empty($arg_enddate)
&& $arg_startdate > $arg_enddate)
{
    set_error_msg("startdate({$arg_startdate}) more than enddate({$arg_enddate})");
}

/**
 * 
 * 查询value对应的text
 */
function getfieldtext(){
	if(!isset($GLOBALS['HTTP_RAW_POST_DATA'])){
		set_error_msg("post data not found");
	}
	$arg_valuetype = $_GET['valuetype'];
	$arg_isemo = $_GET['isemo'];
	//$arg_values 为value_text_object对象数组
	$arg_values = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
	$fcon = array();
	foreach($arg_values as $k => $v){
		//$v 包括text和value属性
		if($arg_isemo){
			$arg_values[$k]["value"] = explode(",", $v["value"]);
			$fcon[] = $arg_values[$k]["value"][0];	
		}
		else{
			$fcon[] = $v["value"];
		} 
	}
	$rs_arrobj = array();
	if ($arg_valuetype == "account"){
		foreach($fcon as $value){
			$text = getScreenNameByUserId($value);
			if(!empty($text)){
				$rs_arrobj[$value] = $text;
			}
		}
	}
	else{
		$wh = "('".implode("','", $fcon)."')";
		switch ($arg_valuetype){
			case "area":
				$sql = "select area_code as value, name as text from ".DATABASE_ARERA." where area_code in {$wh}";
				break;
			case "business":
				$sql = "select id as value, business_name as text from ".DATABASE_BUSINESS." where id in {$wh}";
				break;
			default:
				break; 
		}
		if(!isset($sql)){
			set_error_msg("arg valuetype error:".$arg_valuetype);
		}
		else{
			$qr = mysql_query($sql);
			if(!$qr){
				$logger->error(__FUNCTION__." sql:{$sql} error:".mysql_error());
				set_error_msg("sql error");
			}
			else{
				while($rs = mysql_fetch_array($qr, MYSQL_ASSOC)){
					$rs_arrobj[$rs['value']] = $rs['text'];
				}
			}
		}
	}
	foreach($arg_values as $k => $v){
		if($arg_isemo && is_array($v)){
			if(isset($rs_arrobj[$arg_values[$k]['value'][0]])){
				$arg_values[$k]['text'] = $rs_arrobj[$arg_values[$k]['value'][0]]." ".constant('VAL_EMO_LEVEL_NAME_'.$arg_values[$k]['value'][1]);
			}
			$arg_values[$k]['value'] = implode(",", $arg_values[$k]['value']);
		}
		else{
			$arg_values[$k]['text'] = $rs_arrobj[$arg_values[$k]['value']];
		}
	}
	echo json_encode($arg_values);
	exit;
}

//主流程
function getdataInit($dataArr){
	global $arg_userid;
		$arg_type = isset($dataArr['type']) ? $dataArr['type'] : '' ;
		$arg_blurname = isset($dataArr['blurname']) ? $dataArr['blurname'] : '' ;
		$arg_searchname_page = isset($dataArr['page']) ? $dataArr['page']-1 : '' ;   //limit
		$arg_searchname_pagesize = isset($dataArr['pagesize']) ? $dataArr['pagesize'] : '' ;
	connectMysql(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO);   //数据库连接到weibo_info_2
	mysql_query("SET NAMES utf8");
	switch ($arg_type)
	{
		case TYPE_SEARCHBUSINESS:
			get_searchbusiness(0);
			break;
		case TYPE_GETBUSINESS:
			get_searchbusiness(1);
			break;
		case TYPE_GETBUSINESSBYIDS:
			get_searchbusiness(3);
			break;
		case TYPE_EMOORISTATUSES:
			get_emotion_status(1);
			break;
		case TYPE_EMOREPOST:
			get_emotion_status(2);
			break;
		case TYPE_EMOSTATUS:
			get_emotion_status(0);
			break;
		case TYPE_EMOTION:
			get_emotion_topn_user();
			break;
		case TYPE_EMORATIOORISTATUSES:
			get_emotion_ratio(1);
			break;
		case TYPE_EMORATIOREPOST:
			get_emotion_ratio(2);
			break;
		case TYPE_EMORATIOSTATUSES:
			get_emotion_ratio(0);
			break;
		case TYPE_SOURCE_STATUE:
			get_source_status();
			break;
		case TYPE_EMOTYPE:
			get_emotion_type();
			break;
		case TYPE_SELECTCOUNTRY:
			get_area_code_by_area_level(1);
			break;
		case TYPE_SELECTPROVINCE:
			get_area_code_by_area_level(2);
			break;
		case TYPE_SELECTCITY:
			get_area_code_by_area_level(3);
			break;
		case TYPE_SELECTDISTRICT:
			get_area_code_by_area_level(4);
			break;
		case TYPE_SEARCHBEIDGE:
			get_bridge_user();
			break;
		case TYPE_GETBRIDGE:     //桥接案例接口
			get_bridge_example();
			break;
		case TYPE_GETBRIDGE_USER:
			get_bridgecase_byuser($arg_userid);
			break;
		case TYPE_SEARCHBEIDGE_COUNT:
			get_bridge_user_count();
			break;
		case TYPE_BRIDGINGDEPTH:
			get_bridgingdepth(1);
			break;
		case TYPE_BRIDGINGDEPTHARRIVE:
			get_bridgingdepth(2);
			break;
		case TYPE_BRIDGINGUSERCOUNT:
			get_bridgingusercount(1);
			break;
		case TYPE_BRIDGINGUSERCOUNTARRIVE:
			get_bridgingusercount(2);
			break;
		case TYPE_BRIDGINGQUALITY:
			get_bridgingquality(1);
			break;
		case TYPE_BRIDGINGQUALITYARRIVE:
			get_bridgingquality(2);
			break;
		case TYPE_GETSOURCE:
			get_source();
			break;
		case TYPE_GETSOURCEHOSTNAME:
			get_sourcehostname();
			break;		
		case TYPE_GETSOURCENAMEBYID:
			get_sourcename();
			break;
		case TYPE_GETSOURCEURLBYID:
			get_sourceurl();
			break;
		case TYPE_GETVERIFIEDTYPE:
			get_verifiedtype(-1);
			break;
		case TYPE_SELECTVERIFIED:
			get_verifiedtype(1);
			break;
		case TYPE_SELECTWELLUSER:
			get_verifiedtype(2);
			break;
		case TYPE_SELECTWELLORG:
			get_verifiedtype(3);
			break;
		case TYPE_SELECTWELLOTHER:
			get_verifiedtype(4);
			break;
		case TYPE_GETVERIFIED:
		   get_verified();	
		   break;
		case TYPE_GETFIELDTEXT:
			getfieldtext();
			break;
		default:
			set_error_msg('arg '.$arg_type.' has a error');
			break;
	}

	//关闭数据库
	closeMysql();
	//处理$data_arr，转换成json格式
	handle_data_arr();
}
?>
