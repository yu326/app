<?php
define( "GET_BUSINESS_BY_IDS" , 43 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", GET_BUSINESS_BY_IDS);    //需要在include common.php之前，定义CONFIG_TYPE
define("SELF", basename(__FILE__));
include_once( 'includes.php' );

define('ARG_TYPE','type');
define('ARG_IDS', 'ids');
define('ARG_SOURCEID','source');
define('FIELD_NAME', 'text');
initLogger(LOGNAME_WEBAPI);
$logger->debug($HTTP_RAW_POST_DATA);
/*
 * set error msg
 */
function set_error_msg($error_str)
{
    global $logger;
    $logger->error(SELF." ".$error_str);
    $error['error'] = $error_str;
    $msg = json_encode($error);
    echo $msg;
    exit;
}

//test
//$HTTP_RAW_POST_DATA = '{"comments_count":"200","source":"1","weiboid":"3","keyword":["张三","讨厌","尼康"],"mood":["高兴"],"reposts_count":"100","emotion":[{"word":["张三","尼康","张三#尼康"],"key":"讨厌"}]}';

if (!isset($HTTP_RAW_POST_DATA) || 
    !$HTTP_RAW_POST_DATA)
{
    set_error_msg('POST data is null');
}
/*set_log(DEBUG, 'HTTP_RAW_POST_DATA is '.$HTTP_RAW_POST_DATA, 
    __FILE__, __LINE__);*/

$data_arr = array();
$data_str;

$arg_arr = json_decode($HTTP_RAW_POST_DATA, true);
//do_log
/*foreach ($arg_arr as $tmp_arg_arr_key => $tmp_arg_arr_value)
{
    set_log(DEBUG, 'key is '.$tmp_arg_arr_key.' value is '.
        $tmp_arg_arr_value, 
    __FILE__, __LINE__);
    if (is_array($tmp_arg_arr_value))
    {
        foreach ($tmp_arg_arr_value as $tmp_value)
        {
            set_log(DEBUG, 'value is '.
                $tmp_value, 
            __FILE__, __LINE__);
        }
    }
}*/
$arg_type = isset($arg_arr[ARG_TYPE]) ? $arg_arr[ARG_TYPE] : '';
$arg_ids = isset($arg_arr[ARG_IDS]) ? $arg_arr[ARG_IDS] : array();

//test
//$arg_ids = array(2, 1, 3, 5, 9, 4);

if (empty($arg_type))
{
    set_error_msg('opt '.ARG_TYPE.' is null');
}

if (empty($arg_ids))
{
    set_error_msg('opt '.ARG_IDS.' is null');
}

connectMysql(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO);
mysql_query("SET NAMES utf8");

switch ($arg_type){
    case "business":
    case "emoBusiness":
        get_business();
        break;
    default:
        set_error_msg('opt '.ARG_TYPE.' is undefined');
        break;
}

/**
 * 
 * 获取行业名称
 */
function get_business(){
    global $arg_ids,$data_arr,$data_str,$arg_arr, $logger;
    $ids_arr = array();
    foreach ($arg_ids as $id)
    {
        $id .= '';
        $ids_arr[$id] = '';
    }
    $ids_str = implode("','", $arg_ids);
    $sql = "select business_id, business_name
        from ".DATABASE_BUSINESS." 
        where business_id in ('{$ids_str}')";
    $qr = mysql_query($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".mysql_error());
    }
    $num = mysql_num_rows($qr);
    if (!$num)
    {
        /*set_log(DEBUG, 'result is null',
        __FILE__, __LINE__);*/
        continue;
    }
    else
    {
        while ($result = mysql_fetch_array($qr, MYSQL_NUM))
        {
            $result[0] .= '';
            $ids_arr[$result[0]] = $result[1];
        }
        $data_arr[FIELD_NAME] = array_values($ids_arr);
    }
    $data_str = json_encode($data_arr);
    /*set_log(DEBUG, 'return str is '.$data_str, 
        __FILE__, __LINE__);*/
    echo $data_str;
    closeMysql();
    exit;
}
?>
