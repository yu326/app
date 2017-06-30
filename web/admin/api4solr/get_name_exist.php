<?php
define( "GET_NAME_EXIST" , 44 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", GET_NAME_EXIST);    //需要在include common.php之前，定义CONFIG_TYPE

include_once( 'includes.php' );

define('ARG_SOURCE', 'source');
define('ARG_NAMES', 'names');

define('RET_RESULT', 'result');

initLogger(LOGNAME_WEBAPI);
/*
 * set error msg
 */
function set_error_msg($error_str)
{
    $error['error'] = $error_str;
    $msg = json_encode($error);
    echo $msg;
    exit;
}

//test
//$HTTP_RAW_POST_DATA = '{"comments_count":"200","source":"1","weiboid":"3","keyword":["张三","讨厌","尼康"],"mood":["高兴"],"reposts_count":"100","emotion":[{"word":["张三","尼康","张三#尼康"],"key":"讨厌"}]}';
//$HTTP_RAW_POST_DATA = '{"source":1,"names":["人保电话车险","嘻嘻嘻","篮球队","刘强东","x","中国人保财险","中国人保","李开复","是否","信息"]}'; 
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

$arg_source = isset($arg_arr[ARG_SOURCE]) ? $arg_arr[ARG_SOURCE] : -1;
$arg_names = isset($arg_arr[ARG_NAMES]) ? $arg_arr[ARG_NAMES] : array();

//test
//$arg_source = 1;
//$arg_names = array('薛晨CCTV');

if ($arg_source == -1)
{
    set_error_msg('opt '.ARG_SOURCE.' is null');
}
if (empty($arg_names))
{
    set_error_msg('opt '.ARG_NAMES.' is null');
}

$names_arr = array();
foreach ($arg_names as $name)
{
    $name .= '';
    $names_arr[$name] = '0';
}
$qr = solr_select_conds(array('users_screen_name', 'users_id'), array('users_screen_name' => $arg_names, 'users_sourceid' => $arg_source));
if ($qr === false)
{
    set_error_msg('solr select failed');
}
foreach ($qr as $result)
{
    if (isset($result['users_screen_name']) && isset($result['users_id']))
    {
    	$names_arr[$result['users_screen_name']] = $result['users_id'];
    }
}
$data_arr[RET_RESULT] = array_values($names_arr);
$data_str = json_encode($data_arr);
/*set_log(DEBUG, 'return str is '.$data_str, 
    __FILE__, __LINE__);*/
echo $data_str;
exit;
?>
