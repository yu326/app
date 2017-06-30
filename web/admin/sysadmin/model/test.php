<?php
//header('Access-Control-Allow-Origin: *');

//ini_set('json_precision', -1);

header('Access-Control-Allow-Origin: http://192.3i.inter3i.com:83');

define("SELF", basename(__FILE__));
//定义请求任务类型:为通用任务,以后可以去掉默认就是通用类型的任务
define("TASKTYPE_COMMON", "remotecommtask");
define("IMPORT_DATA_TARGET_H2", "H2_CACHE");
define("IMPORT_DATA_TARGET_MONGO", "MONGO_CACHE");

include_once('commonFun.php');
include_once('PHPExcel/IOFactory.php');
ini_set('include_path', get_include_path() . '/lib');

$precisionReulst = ini_set('precision', 3);
$serialize_precisionReulst = ini_set('serialize_precision', -1);


session_start();
set_time_limit(0);//植入微博时，可能会超时
initLogger(LOGNAME_WEBAPI);
//$chkr = Authorization::checkUserSession();

//$taskadd = isset($_POST['taskadd']) ? $_POST['taskadd'] : 0;
$result = array("result" => true, "msg" => 'success');
$logger->info("进入测试类....");


$precision1 = ini_get('precision');
$serialize_precision1 = ini_get('serialize_precision');
$logger->info("precision...." . $precision1 . " precisionReulst" . $precisionReulst);
$logger->info("serialize_precision...." . $serialize_precision1 . "serialize_precisionReulst" . $serialize_precisionReulst);
//if (!empty($_GET['type']) && $_GET['type'] == "remote") {

//file_get_contents("php://input");
//$_POST['fieldname'];

//echo json_encode($result);
//exit;
//
if (!isset($HTTP_RAW_POST_DATA) || empty($HTTP_RAW_POST_DATA)) {
    $reqestDataStr = file_get_contents('php://input');
    if (!isset($reqestDataStr) || empty($reqestDataStr)) {
        $logger->debug(SELF . " recived import document data is null.");
        exit;
    } else {
        $requsePostData = &$reqestDataStr;
    }
    exit;
} else {
    $requsePostData = $HTTP_RAW_POST_DATA;
}
$logger->debug(SELF . " recived import document data : " . $requsePostData);

$decodeData = json_decode($requsePostData, true);
$logger->debug(SELF . " -----\n" . var_export($decodeData, true));

$decodeData11 = json_encode($decodeData);
$logger->debug(SELF . " -----endoce之后[" . $decodeData11 . "].");


//echo json_encode($result);
//exit;

//$solrStoreCons = SOLR_STORE;
//$logger->info("solrStoreCons:[{$solrStoreCons}]!");
//$solrHostStr = getSolrHostForCurSrv();

//$result["data"] = array();
//$result["data"]["solrHost"] = $solrHostStr;
//$logger->info("获取host成功:[{$solrHostStr}]!");


//$dbNamesAdminStr = getDBAdminName();
//$result["data"]["DBNameAdmin"] = $dbNamesAdminStr;

//根据监听端口，动态获取数据库名称-管理
//$dbNamesDataStr = getDBDataName();
//$result["data"]["DBNameData"] = $dbNamesDataStr;

//$beforepostdata = json_decode($HTTP_RAW_POST_DATA, true);
//
//$total = 2;
//while ($total >= 0) {
//    $total--;
//    $logger->debug(__FILE__ . __LINE__ . "current processor or thread sleep:[" . getmypid() . "].");
//    sleep(1);
//}


//if (empty($beforepostdata)) {
//    $beforepostdata = $GLOBALS['HTTP_RAW_POST_DATA'];
//}
//$logger->debug(__FILE__ . __LINE__ . " test php receive request data:" . var_export($beforepostdata, true));
//$cacheReqData =  json_decode("{name:\"zhangsan\"}");

//$postdata = formatPostdataTest($beforepostdata);

//$cacheReqData = "wangchaochao";
//$targetDataType = IMPORT_DATA_TARGET_MONGO;
//if ($targetDataType == IMPORT_DATA_TARGET_H2) {
//    $reqURL = SOLR_URL_CACHE . "&serverHost=" . $serverHost . "&cacheServerName=" . $cacheNameCurPort;
//} else if ($targetDataType == IMPORT_DATA_TARGET_MONGO) {
//将数据写入到monge中
//$reqURL = CACHE_SERVER . $cacheNameCurPort . "/insert";
//$reqURL = SOLR_URL_CACHE . "&cacheServerName=" . $cacheNameCurPort;
//    $reqURL = "http://wangcc:8080/DocCache/cachedoc?type=insert&cacheServerName=cache01";
//}
//$cacheResult = send_solr($cacheReqData, $reqURL, "Content-type:text/plain;charset=utf-8");

//if ($cacheResult === false) {
//    $logger->error(__FILE__ . __LINE__ . " " . __FUNCTION__ . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-发送缓存数据失败! URL: " . $reqURL);
//    throw new Exception("发送缓存数据失败!ErrMsg:[" . $cacheResult["msg"] . "].");
//} else {
//    $logger->info(__FILE__ . __LINE__ . " " . __FUNCTION__ . " " . " 处理通用任务:[remotecommtask_cache]--+-数据缓存--+-发送缓存数据成功! URL: " . $reqURL);
//}

//echo json_encode($result);
echo json_encode("test end!");
exit;


function formatPostdataTest($item_text)
{
    global $logger;
//    $retdata = array();
//    if (!empty($postdata)) {
    //处理每条数据
//        foreach ($postdata["data"] as $key => $item) {
    /*
    $floor = isset($item['floor']) ? $item['floor'] : -1;
    if($floor == -1){
        continue;
    }
    $id = base64_encode($item["original_url"])."_".$floor;
     */
//            $item_text = isset($item["text"]) ? $item["text"] : "";
    //$imgpattern = "/<IMG[^>]+src=\"([^\"]+)/"; 当src前有>时将不再适用, 例如前面有js,onmouseover=\"if(this.width>760)....  .*?  (?:[\.gif|\.jpg|\.jpeg|\.bmp|\.png|\.pic])
//    $imgpattern = "/<[img|IMG].*?src=[\'|\"](.*?[\.gif|\.jpg|\.jpeg|\.bmp|\.png|\.pic])[\'|\"][^\/><]*[\/] ? >/si";
    $imgpattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.jpeg|\.bmp|\.png|\.pic]?))[\'|\"].*?[\/]?>/";

    preg_match_all($imgpattern, $item_text, $matches2);
    $imgs = $matches2[1];
//    $imgs_prifix = $matches2[2];

    $pattern = "/<br>|<br\/>|<p>|<\/p>|<BR>|<BR\/>|<P>|<\/P>/";
    $replacement = "\r\n";
    $text = preg_replace($pattern, $replacement, $item_text);
    $text = strip_tags($text);
    $search = array("'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
        "'<[\/\!]*?[^<>]*?>'si",           // 去掉 HTML 标记
        "'([\r\n])[\s]+'",                 // 去掉空白字符
        "'&(quot|#34);'i",                 // 替换 HTML 实体
        "'&(amp|#38);'i",
        "'&(lt|#60);'i",
        "'&(gt|#62);'i",
        "'&(nbsp|#160);'i",
        "'&(iexcl|#161);'i",
        "'&(cent|#162);'i",
        "'&(pound|#163);'i",
        "'&(copy|#169);'i",
        "'　'i",
        "'&#(\d+);'e");                    // 作为 PHP 代码运行

    $replace = array("",
        "",
        "\\1",
        "\"",
        "&",
        "<",
        ">",
        " ",
        chr(161),
        chr(162),
        chr(163),
        chr(169),
        "",
        "chr(\\1)");
    $text = preg_replace($search, $replace, $text);
    $text = strip_tags($text);
    //存段落
    $paragraphs = array();
    //按\r\n分段后,可能是空段,或是段前后有空格需要trim
    $paragraphs = preg_split("/[\r\n]+/", $text);
    //$paraArr = array();
    $article = array();
    foreach ($paragraphs as $pi => $pitem) {
        $pitem = iconv("UTF-8", "GBK//IGNORE", $pitem);//忽略非法字符
        $pitem = iconv("GBK", "UTF-8", $pitem);//转回utf8

        $pg_text = trim($pitem); //各个段落
        if (!empty($pg_text)) {
            $article[] = $pg_text; //整篇文章
            /*
            $tmpdata = array();
            $tmpdata['content'] = $pg_text;
            $tmpdata['terms'] = array();
            $paraArr[] = $tmpdata;
             */
        }
    }
    if (count($article) > 1) {
        $item['pg_text'] = array();
        $item['pg_text'] = $article;
    }
    //存文章
    //段落分割符, 分词前使用\r\n 分词后转为 <BR/>
    $item['text'] = implode("\r\n", $article);

    $pattern = '/.*?\.[gif|jpg|jpeg|bmp|png|pic]/';

    foreach ($imgs as $idx => $picURL) {
        $logger->debug(__FILE__ . __LINE__ . " 图片后缀是: " . var_export($picURL, true));
        //
        $ifMatch = preg_match($pattern, $picURL);
        if (!$ifMatch) {
            $logger->debug(__FILE__ . __LINE__ . " current url for picture is not valid: " . var_export($picURL, true));
//            unset($imgs[$idx]);
            array_splice($imgs, $idx, 1); //对齐下标 下标重构
        } else {
            $logger->debug(__FILE__ . __LINE__ . " current url for picture is valid: " . var_export($picURL, true));
        }
    }

    $decr = 0;      //常量，用于和$idx一起确定array_splice函数的开始位置
    //循环上面获取到的路径，用正则匹配，如果不符合，则用array_splice删除数据
    //由于array_splice删除之后，并会由新元素替换他，所以光凭$idx,无法正确删除，引入$desc,删除一个数据，则$desc-1，array_aplice开始位置$start = $idx+$desc,这样就可以解决删除问题了
    foreach ($imgs as $idx => $picURL) {
        $ifMatch = preg_match($pattern, $picURL);
        if (!$ifMatch) {
            $logger->debug(__FILE__ . __LINE__ . " current url for picture is not valid: " . var_export($picURL, true));
            array_splice($imgs, $idx - $decr, 1);
            $decr++;
        } else {
            $logger->debug(__FILE__ . __LINE__ . " current url for picture is valid: " . var_export($picURL, true));
        }
    }

    $item["bmiddle_pic"] = $imgs;
    //当每条记录的page_url不存在时,使用全局的page_url
    /*
    if(!isset($item["page_url"]))
        $item["page_url"] = $postdata["page_url"];
    if(isset($postdata["original_url"])){
        $item["original_url"] = $postdata["original_url"];
    }
    if(isset($postdata["source_host"])){
        $item["source_host"] = $postdata["source_host"];
    }
    if(isset($postdata["sourceid"])){
        $item["sourceid"] = $postdata["sourceid"];
    }
     */
//    $item['paragraphid'] = 0;
//    $retdata[] = $item;
//        }
//        $postdata["data"] = $retdata;
//    }
    return $item;
}
