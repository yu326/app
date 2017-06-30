<?php
define( "GET_DICTIONARY" , 42 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", GET_DICTIONARY);    //需要在include common.php之前，定义CONFIG_TYPE

include_once( 'includes.php' );

//define('RET_DICTIONARY_CATEGORY', 'dictionary_category');

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
define('RET_PROVINCE', 'province');
define('RET_CITY', 'city');
define('RET_DISTRICT', 'district');
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
define('RET_LANGUAGE','language');//语言
define('RET_CATEGORY_ID', 'category_id');


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
$type_dictionary_category = 0x4;
$type_emotion = 0x8;
$type_default_category = 0x10;//默认分类
$type_urldict = 0x20;

$arg_arr = json_decode($HTTP_RAW_POST_DATA, true);
initLogger(LOGNAME_WEBAPI);

$arg_type = isset($arg_arr[ARG_TYPE]) ? $arg_arr[ARG_TYPE] : 0;

if (!$arg_type)
{
    set_error_msg('opt '.ARG_TYPE.' is null');
}
//语义字典表
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
if ($arg_type & $type_dict)
{
    $sql = "select *
        from ".DATABASE_SEMANTIC_DICTIONARY;
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
     //   $tmp_business_words = array();
     //   $tmp_new_words = array();
		
        while ($result = $dsql->GetArray($qr, MYSQL_ASSOC))
        {
            if ($result['value'] !== null && $result['value'] !== '')
            {
                switch ($result['type'])
                {
                case 1:
//                	 $tmp = array();
//                	 $tmp[0]= $result['value'];
//                	 $tmp[1]= $result['language'];
//                	 $tmp[1]= $result['NR'];
//                	 $tmp_peopel[0][] = $result['value'];
                    $tmp_peopel['1'][0][] = $result['value'];
                   // $tmp_peopel['1'][1][] = $result['pos'];
                    break;
                case 2:
                    $tmp_organization['1'][0][] = $result['value'];
                   // $tmp_organization['1'][1][] = $result['pos'];
                    break;
//                case 3:
//                    $result['subtype'] .= '';
//                    $tmp_business_words[$result['subtype']][0][] = $result['value'];
//                    $tmp_business_words[$result['subtype']][1][] = $result['pos'];
//                    $tmp_business_words[$result['subtype']][2][] = $result['language'];
//                    break;
//                case 4:
//                    $result['subtype'] .= '';
//                    $tmp_new_words[$result['subtype']][0][] = $result['value'];
//                    $tmp_new_words[$result['subtype']][1][] = $result['pos'];
//                    $tmp_new_words[$result['subtype']][2][] = $result['language'];
//                    break;
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
  //             $tmp_peopel_inner[RET_DICT_BUSINESS_CODE] = $tmp_peopel_key;
                $tmp_peopel_inner[RET_WORDS] = $tmp_peopel_value[0];
              //  $tmp_peopel_inner[RET_LANGUAGE] = $tmp_peopel_value[1];
                $tmp_peopel_inner[RET_POS] = 'NR';
                $tmp_arr[RET_DATA][RET_DICT_PEOPLE][] = $tmp_peopel_inner;
            }
        }
        if (!empty($tmp_organization))
        {
            foreach($tmp_organization as $tmp_organization_key => $tmp_organization_value)
            {
                $tmp_org_inner = array();
  //              $tmp_org_inner[RET_DICT_BUSINESS_CODE] = $tmp_organization_key;
                $tmp_org_inner[RET_WORDS] = $tmp_organization_value[0];
              //  $tmp_org_inner[RET_LANGUAGE] = $tmp_organization_value[1];
                $tmp_org_inner[RET_POS] = 'NR';
                $tmp_arr[RET_DATA][RET_DICT_ORGANIZATION][] = $tmp_org_inner;
            }
        }
//        if (!empty($tmp_business_words))
//        {
//            foreach($tmp_business_words as $tmp_business_words_key => $tmp_business_words_value)
//            {
//                $tmp_business_inner = array();
//                $tmp_business_inner[RET_DICT_BUSINESS_CODE] = $tmp_business_words_key;
//                $tmp_business_inner[RET_DICT_BUSINESS_WORDS] = $tmp_business_words_value[0];
//                $tmp_business_inner[RET_POS] = $tmp_business_words_value[1];
//                $tmp_business_inner[RET_LANGUAGE] = $tmp_business_words_value[2];
//                $tmp_arr[RET_DATA][RET_DICT_BUSINESS][] = $tmp_business_inner;
//            }
//        }
//        if (!empty($tmp_new_words))
//        {
//            foreach($tmp_new_words as $tmp_new_words_key => $tmp_new_words_value)
//            {
//                $tmp_new_inner = array();
//                $tmp_new_inner[RET_DICT_BUSINESS_CODE] = $tmp_new_words_key;
//                $tmp_new_inner[RET_WORDS] = $tmp_new_words_value[0];
//                $tmp_new_inner[RET_POS] = $tmp_new_words_value[1];
//                $tmp_new_inner[RET_LANGUAGE] = $tmp_new_words_value[2];
//                $tmp_arr[RET_DATA][RET_DICT_NEWWORD][] = $tmp_new_inner;
//            }
//        }
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}

//地区
//set_log(DEBUG, 'sql is '.$sql,__FILE__, __LINE__);
if ($arg_type & $type_addr)
{
    $sql = "select name, another_name, country, province,city,district
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
            $tmp_arr_1[RET_PROVINCE] = empty($result['province']) ? '' : $result['province'];
            $tmp_arr_1[RET_CITY] = empty($result['city']) ? '' : $result['city'];
            $tmp_arr_1[RET_DISTRICT] = empty($result['district']) ? '' : $result['district'];
            if ($result['name'] !== null && $result['name'] !== '')
            {
                $tmp_arr_1[RET_AREA] = empty($result['name']) ? '' : $result['name'];
                $tmp_arr[RET_DATA][RET_WORDS][] = $tmp_arr_1;
            }
            if ($result['another_name'] !== null && $result['another_name'] !== '')
            {
                $tmp_arr_1[RET_AREA] = empty($result['another_name']) ? '' : $result['another_name'];
                $tmp_arr[RET_DATA][RET_WORDS][] = $tmp_arr_1;
            }
        }
        $tmp_arr[RET_DATA][RET_POS] = 'NR';
        //$tmp_arr[RET_DATA] = array_unique($tmp_arr[RET_DATA]);
        //$tmp_arr[RET_DATA] = array_values($tmp_arr[RET_DATA]);
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
//分词字典
if ($arg_type && $type_dictionary_category)
{

	//查出所有分词
    $sql = "SELECT  dictionary.`id`,  `value`,  `language`, `category_id`  
    FROM dictionary left join  dictionary_category on 
    dictionary_category.id=dictionary.category_id  ";//order by category_id 
    $qr = $dsql->ExecQuery($sql);
        //   set_log(DEBUG, var_export($qr,true)." erro ".$dsql->GetError(), __FILE__, __LINE__);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
//         set_log(DEBUG, var_export($qr,true),
//         __FILE__, __LINE__);
    if (!$num)
    {
        ;
    }
    else
    {   	
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_dictionary_category);
        while ($result = $dsql->GetArray($qr,  MYSQL_ASSOC))
        {
            $tmp_arr_1 = array();

            $tmp_arr_1[RET_WORDS] = $result['value'];
            $tmp_arr_1[RET_LANGUAGE] = empty($result['language']) ? 'cn' : $result['language'];
            $tmp_arr_1[RET_CATEGORY_ID] = $result['category_id'];
         $tmp_arr[RET_DATA][] = $tmp_arr_1;
        }
        $data_arr[RET_DICTS][] = $tmp_arr;
        
    }
}
//获取所有类别分类 
if ($arg_type && $type_default_category)
{
	
    $sql = "SELECT  `id`,  `category_name`,  `state`
    FROM   dictionary_category  ";
    $qr = $dsql->ExecQuery($sql);
        //   set_log(DEBUG, var_export($qr,true)." erro ".$dsql->GetError(), __FILE__, __LINE__);
    if (!$qr)
    {
        set_error_msg("sql error:".$dsql->GetError());
    }
    $num = $dsql->GetTotalRow($qr);
//         set_log(DEBUG, var_export($qr,true),
//         __FILE__, __LINE__);
    if (!$num)
    {
        ;
    }
    else
    {   	
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_default_category);
        while ($result = $dsql->GetArray($qr,  MYSQL_ASSOC))
        {
            $tmp_arr_1 = array();
            $tmp_arr_1["id"] = $result['id'];
           // $tmp_arr_1["category_name"] = $result['category_name'];
            $tmp_arr_1["state"] = $result['state'];
         $tmp_arr[RET_DATA][] = $tmp_arr_1;
        }
        $data_arr[RET_DICTS][] = $tmp_arr;
        
    }
}
//表情
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

//取短链接字典
if($arg_type & $type_urldict){
    $sql = "select url from ".DATABASE_URL_DICT;
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        set_error_msg($dsql->GetError());
    }
    else{
        $tmp_arr = array();
        $tmp_arr[RET_TYPE] = intval($type_urldict);
        while($rs = $dsql->GetArray($qr)){
            $tmp_arr[RET_DATA][] = $rs['url'];
        }
        $dsql->FreeResult($qr);
        $data_arr[RET_DICTS][] = $tmp_arr;
    }
}
//set_log(DEBUG, "______".var_export($data_arr,true),  __FILE__, __LINE__);
$data_str = json_encode($data_arr);


echo $data_str;

exit;
?>
