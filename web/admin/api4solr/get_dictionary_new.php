<?php
define( "GET_DICTIONARY" , 42 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", GET_DICTIONARY);    //需要在include common.php之前，定义CONFIG_TYPE

include_once( 'includes.php' );

//define('ARG_SOURCE', 'source');
define('ARG_TYPE', 'dictValue');

define('RET_DICTS', 'dicts');
define('RET_TYPE', 'type');
define('RET_DATA', 'data');

define('RET_DICT_BUSINESS', 'business_word');
define('RET_DICT_BUSINESS_CODE', 'code');
define('RET_DICT_BUSINESS_WORDS', 'words');
define('RET_DICT_PEOPLE', 'people');
define('RET_DICT_ORGANIZATION', 'organization');
define('RET_DICT_NEWWORD', 'new_word');

define('RET_AREA', 'area');
define('RET_COUNTRY', 'country');
define('RET_CODE', 'code');

define('RET_BUSI_KEYWORD_KEYWORD', 'word');
define('RET_BUSI_KEYWORD_CODE', 'code');

define('RET_EMOTION_CONTENT', 'content_emotion');
define('RET_EMOTION_SPECIAL', 'special_emotion');
define('RET_EMOTION_NAME', 'word');
define('RET_EMOTION_VALUE', 'value');
define('RET_EMOTION_SOURCE', 'source');
define('RET_EMOTION_DATA', 'data');

define('RET_POS', 'pos');    //词性
define('RET_WORDS', 'words');

define('RET_SOURCEID', 'sourceid');
define('RET_BUSINESSID', 'businessid');

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
//$HTTP_RAW_POST_DATA = '{"123":"234"}';

if (!isset($HTTP_RAW_POST_DATA) ||
!$HTTP_RAW_POST_DATA)
{
    set_error_msg('POST data is null');
}
/*set_log(DEBUG, 'HTTP_RAW_POST_DATA is '.$HTTP_RAW_POST_DATA,
 __FILE__, __LINE__);*/

$data_arr = array();
$data_str;


$type_dict = 0x1;
$type_addr = 0x2;
$type_business = 0x4;
$type_emotion = 0x8;
$type_source_business = 0x10;

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

//$arg_source = isset($arg_arr[ARG_SOURCE]) ? $arg_arr[ARG_SOURCE] : -1;
$arg_type = isset($arg_arr[ARG_TYPE]) ? $arg_arr[ARG_TYPE] : 0;

//test
//$arg_source = 1;
//$arg_type = 31;

/*
if (-1 == $arg_source)
{
    set_error_msg('opt '.ARG_SOURCE.' is null');
}
 */

if (!$arg_type)
{
    set_error_msg('opt '.ARG_TYPE.' is null');
}


if ($arg_type & $type_dict)
{
    $sql = "select *
        from ".DATABASE_DICTIONARY;
    /*set_log(DEBUG, 'sql is '.$sql,
     __FILE__, __LINE__);*/
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
    if (!$num)
    {
        /*set_log(DEBUG, 'result is null',
         __FILE__, __LINE__);*/
        ;
    }
    else
    {
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_dict);
        $tmp_peopel = array();
        $tmp_organization = array();
        $tmp_business_words = array();
        $tmp_new_words = array();
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
            if ($result['value'] !== null && $result['value'] !== '')
            {
                switch ($result['type'])
                {
                case 1:
                    $tmp_peopel[$result['subtype']][] = $result['value'];
                    break;
                case 2:
                    $tmp_organization[$result['subtype']][] = $result['value'];
                    break;
                case 3:
                    $result['subtype'] .= '';
                    $tmp_business_words[$result['subtype']][0][] = $result['value'];
                    $tmp_business_words[$result['subtype']][1][] = $result['pos'];
                    break;
                case 4:
                    $result['subtype'] .= '';
                    $tmp_new_words[$result['subtype']][0][] = $result['value'];
                    $tmp_new_words[$result['subtype']][1][] = $result['pos'];
                    break;
                default:
                    break;
                }
            }
        }
        if (!empty($tmp_peopel))
        {
            foreach($tmp_peopel as $tmp_peopel_key => $tmp_peopel_value)
            {
                $tmp_peopel_inner = array();
                $tmp_peopel_inner[RET_DICT_BUSINESS_CODE] = $tmp_peopel_key;
                $tmp_peopel_inner[RET_WORDS] = $tmp_peopel_value;
                $tmp_peopel_inner[RET_POS] = 'NR';
                $tmp_arr[RET_DATA][RET_DICT_PEOPLE][] = $tmp_peopel_inner;
            }
        }
        if (!empty($tmp_organization))
        {
            foreach($tmp_organization as $tmp_organization_key => $tmp_organization_value)
            {
                $tmp_org_inner = array();
                $tmp_org_inner[RET_DICT_BUSINESS_CODE] = $tmp_organization_key;
                $tmp_org_inner[RET_WORDS] = $tmp_organization_value;
                $tmp_org_inner[RET_POS] = 'NR';
                $tmp_arr[RET_DATA][RET_DICT_ORGANIZATION][] = $tmp_org_inner;
            }
        }
        if (!empty($tmp_business_words))
        {
            foreach($tmp_business_words as $tmp_business_words_key => $tmp_business_words_value)
            {
                $tmp_business_inner = array();
                $tmp_business_inner[RET_DICT_BUSINESS_CODE] = $tmp_business_words_key;
                $tmp_business_inner[RET_DICT_BUSINESS_WORDS] = $tmp_business_words_value[0];
                $tmp_business_inner[RET_POS] = $tmp_business_words_value[1];
                $tmp_arr[RET_DATA][RET_DICT_BUSINESS][] = $tmp_business_inner;
            }
        }
        if (!empty($tmp_new_words))
        {
            foreach($tmp_new_words as $tmp_new_words_key => $tmp_new_words_value)
            {
                $tmp_new_inner = array();
                $tmp_new_inner[RET_DICT_BUSINESS_CODE] = $tmp_new_words_key;
                $tmp_new_inner[RET_WORDS] = $tmp_new_words_value[0];
                $tmp_new_inner[RET_POS] = $tmp_new_words_value[1];
                $tmp_arr[RET_DATA][RET_DICT_NEWWORD][] = $tmp_new_inner;
            }
        }
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
if ($arg_type & $type_addr)
{
    $sql = "select name, another_name, country, area_code
        from ".DATABASE_AREA;
    /*set_log(DEBUG, 'sql is '.$sql,
     __FILE__, __LINE__);*/
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
    if (!$num)
    {
        /*set_log(DEBUG, 'result is null',
         __FILE__, __LINE__);*/
        ;
    }
    else
    {
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_addr);
        while ($result = $dsql->GetArray($qr,  MYSQL_ASSOC))
        {
            $tmp_arr_1 = array();
            $tmp_arr_1[RET_COUNTRY] = $result['country'];
            $tmp_arr_1[RET_CODE] = $result['area_code'];
            if ($result['name'] !== null && $result['name'] !== '')
            {
                $tmp_arr_1[RET_AREA] = $result['name'];
                $tmp_arr[RET_DATA][RET_WORDS][] = $tmp_arr_1;
            }
            if ($result['another_name'] !== null && $result['another_name'] !== '')
            {
                $tmp_arr_1[RET_AREA] = $result['another_name'];
                $tmp_arr[RET_DATA][RET_WORDS][] = $tmp_arr_1;
            }
        }
        $tmp_arr[RET_DATA][RET_POS] = 'NR';
        //$tmp_arr[RET_DATA] = array_unique($tmp_arr[RET_DATA]);
        //$tmp_arr[RET_DATA] = array_values($tmp_arr[RET_DATA]);
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
if ($arg_type & $type_business)
{
    $sql = "select keyword, business_id
        from ".DATABASE_KEYWORD_BUSINESS;
    /*set_log(DEBUG, 'sql is '.$sql,
     __FILE__, __LINE__);*/
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
    if (!$num)
    {
        /*set_log(DEBUG, 'result is null',
         __FILE__, __LINE__);*/
        ;
    }
    else
    {
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_business);
        while ($result = $dsql->GetArray($qr, MYSQL_NUM))
        {
            if ($result[0] !== null && $result[0] !== '')
            {
                $tmp_busi_arr = array();
                $tmp_busi_arr[RET_BUSI_KEYWORD_KEYWORD] = $result[0];
                $tmp_busi_arr[RET_BUSI_KEYWORD_CODE] = $result[1];
                $tmp_arr[RET_DATA][] = $tmp_busi_arr;
            }
        }
    }
    if (!empty($tmp_arr[RET_DATA]))
    {
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
if ($arg_type & $type_emotion)
{
    $sql = "select expression, value, type, source
        from ".DATABASE_EXPRESSION;
    /*set_log(DEBUG, 'sql is '.$sql,
     __FILE__, __LINE__);*/
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
    if (!$num)
    {
        /*set_log(DEBUG, 'result is null',
         __FILE__, __LINE__);*/
        ;
    }
    else
    {
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_emotion);
        $tmp_arr_1 = array();
        $tmp_arr_2 = array();
        while ($result = $dsql->GetArray($qr, MYSQL_NUM))
        {
            if ($result[0] !== null && $result[0] !== '')
            {
                if (1 == $result[2])
                {
                    $tmp_arr_inner_1 = array();
                    $tmp_arr_inner_1[RET_EMOTION_NAME] = $result[0];
                    $tmp_arr_inner_1[RET_EMOTION_VALUE] = $result[1];
                    $tmp_arr_2[$result[3]][] = $tmp_arr_inner_1;
                }
                else if (2 == $result[2])
                {
                    $tmp_arr_inner_1 = array();
                    $tmp_arr_inner_1[RET_EMOTION_NAME] = $result[0];
                    $tmp_arr_inner_1[RET_EMOTION_VALUE] = $result[1];
                    $tmp_arr_1[RET_EMOTION_CONTENT][] = $tmp_arr_inner_1;
                }
            }
        }
        if (!empty($tmp_arr_1))
        {
            $tmp_arr[RET_DATA][RET_EMOTION_CONTENT] = $tmp_arr_1[RET_EMOTION_CONTENT];
        }
        if (!empty($tmp_arr_2))
        {
            foreach ($tmp_arr_2 as $tmp_arr_2_key => $tmp_arr_2_value)
            {
                $tmp_arr_inner_1 = array();
                $tmp_arr_inner_1[RET_EMOTION_SOURCE] = $tmp_arr_2_key;
                $tmp_arr_inner_1[RET_EMOTION_DATA] = $tmp_arr_2_value;
                $tmp_arr[RET_DATA][RET_EMOTION_SPECIAL][] = $tmp_arr_inner_1;
            }
        }
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
if ($arg_type & $type_source_business)
{
    $sql = "select id, business
        from ".DATABASE_SOURCE;
    /*set_log(DEBUG, 'sql is '.$sql,
     __FILE__, __LINE__);*/
    $qr = $dsql->ExecQuery($sql);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
    if (!$num)
    {
        /*set_log(DEBUG, 'result is null',
         __FILE__, __LINE__);*/
        ;
    }
    else
    {
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_source_business);
        while ($result = $dsql->GetArray($qr, MYSQL_NUM))
        {
            if (!empty($result[1]))
            {
                $tmp_busi_arr = array();
                $tmp_busi_arr[RET_SOURCEID] = $result[0];
                $tmp_busi_arr[RET_BUSINESSID] = $result[1];
                $tmp_arr[RET_DATA][] = $tmp_busi_arr;
            }
        }
    }
    if (!empty($tmp_arr[RET_DATA]))
    {
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
$data_str = json_encode($data_arr);
echo $data_str;
exit;
?>
