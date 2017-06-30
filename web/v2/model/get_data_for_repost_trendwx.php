<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/13
 * add by wang
 */
define( "SELF", basename(__FILE__) );
include_once( 'includes.php' );
include_once('calc_bridge.php');
include_once('userinfo.class.php');
include_once("authorization.class.php");
include_once('db_mysql.class.php');

//传入参数
define('ARG_FATHER_ID', 'fatherid');
define('ARG_ARTICLEID','articleid');
define('ARG_REPOST_COUNT', 'totalrepostcount');    //转发数限制，只返回总转发数大于该值的微博
define('ARG_PARENT_DEPTH', 'parentdepth');//父节点相对层深。
define('ARG_LIMIT', 'limit');
define('ARG_COUNT', 'count');
define('ARG_TYPE', 'type');//请求类型
define('ARG_KEYWORD', 'keyword');//查询参数，姓名
define('ARG_ORIGID', 'origid');//查询参数，guid
define('MAX_EXTEND_COUNT',5);//最大取多少延伸的
define('CHILDREN_COUNT_RATIO','countratio');//取孩子的比例
define('MAX_CHILDREN_COUNT','maxcount');
define('MIN_CHILDREN_COUNT','mincount');
define('ARG_PATHTYPE', 'pathtype');//查询参数，转发轨迹或评论轨迹
define('ARG_IS_CENTER', 'is_center');//查询参数，转发轨迹或评论轨迹

//返回参数
define('RET_CHILDREN', 'children');    //子微博统称
define('RET_WEIBO_ID', 'weiboid');
define('RET_PARENT', 'parentid');
define('RET_ID', 'Id');
define('RET_FROMORG','fromOrg');
define('RET_OPENID','openid');
define('RET_REPOSTS_COUNT','reposts_count');//api返回的转发数
define('RET_TOTAL_COUNT', 'direct_reposts_count');    //总转发数
define('RET_DIRECT_COUNT', 'directcount');    //直接转发数
define('RET_REACH_COUNT', 'reachcount');    //直接到达数
define('RET_TOTAL_REACH', 'totalreach');    //总到达数
define('RET_FANS_COUNT', 'fanscount');    //粉丝数
define('RET_PHONESYSTEM','phonesystem'); //手机型号
define('RET_PROVINCE','province');         //省份
define('RET_CITY','ciyt');         //城市
define('RET_COUNTRY','country') ;            //国家
define('RET_USER_NAME', 'username');    //用户名
define('RET_USER_ID', 'userid');    //用户ID
define('RET_USER_GUID', 'userguid');    //用户ID
define('RET_FACE_IMAGE', 'faceimage');    //用户头像
define('RET_REPOSTDEPTH', 'depth');//转发深度
define('RET_GENDER','gender');
define('RET_VERIFIED','verified');
define('RET_BRIDGEWEIBO','bridgeweibo');//是否桥接微博
define('RET_MAXDEPTH','maxdepth');
define('RET_CREATEAT','created_at');//时间
define('RET_FATHERID','parentid');//父ID
define('RET_IS_EXTEND','is_extend');//是否延伸
define('RET_CONTENT_TYPE','content_type');//文章类型
define('RET_ORIG_ID','retweeted_guid');//源文章ID
define('RET_COMMENTS_COUNT','comments_count');//评论数
define('RET_DIRECT_COMMENT','direct_comments_count');//直接评论数
define('RET_PRAISES_COUNT','praises_count');//赞
define('SEARCH_COUNT','searchcount');//查询到的个数
define('MAX_DIRECT_COUNT','max_directcount');//最大直接转发数
define('RET_RETWEETED_GUID','retweeted_guid');
set_time_limit(0);
session_start();
initLogger(LOGNAME_WEBAPI);
//判断session是否存在
if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
    $user = Authorization::checkUserSession();
    if($user == CHECKSESSION_NULL){
        $arrs["result"]=false;
        $arrs["msg"]="未登录或登陆超时!";
        $arrs['errorcode'] = WEBERROR_NOSESSION;
        $arrs['error'] = $arrs["msg"];
        echo json_encode($arrs);
        exit;
    }
    else if(empty($user)){
        $arrs["result"]=false;
        $arrs["msg"]="没有权限访问!";
        echo json_encode($arrs);
        exit;
    }
}
/*
 * set error msg
 */
function self_set_error_msg($error_str)
{
    global $logger;
    $logger->error(SELF." ".$error_str);
    $error['error'] = $error_str;
    $msg = json_encode($error);
    echo $msg;
    exit;
}
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
//test
/*
$_GET[ARG_WEIBO_ID] = '3350253315428354';
$_GET[ARG_REPOST_COUNT] = 20;
 */

if (!$_GET)
{
    self_set_error_msg("opt is null");
}
$arg_type = isset($_GET[ARG_TYPE]) ? $_GET[ARG_TYPE] : "getrepost";
$arg_articleid = isset($_GET[ARG_ARTICLEID]) ? $_GET[ARG_ARTICLEID] : "1";
$res_arr = array();//返回的数据
$selectfield = array("userguid", "userid","guid","floor","total_reposts_count","reposts_count","direct_reposts_count","repost_trend_cursor","content_type","created_at","is_bridge_status","father_guid","followers_count","total_reach_count", "comments_count", "direct_comments_count", "praises_count", "retweeted_guid", "sourceid", "source_host");
$userQueryCache = array();
dbconnect();
switch ($arg_type){
    case "getrepost":
        getrepost();
        break;
    case "maxdirectcount":
        getMaxDirectRepostCount();
        break;
    case "searchcount":
        searchcount();
        break;
    case "search":
        search();
        break;
    default:
        break;
}
closeMysql();
echo json_encode($res_arr);

function getUserInfoByUserID($userid){
    global $logger,$dsql,$res,$sql,$userid_1;
    $users = array();
    $userid_1=$userid[0];
    $logger->info(__FILE__.__LINE__."the userid_1 is:".var_export($userid_1,true));
    $sql = "select * from weixin_user where Id='$userid_1'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the data is:".var_export($data,true));
    }
    $res = $data;
    if(empty($res)){
        $logger->debug(__FILE__.__LINE__." res ".var_export($res, true));
    }
    if($res === false){
        $logger->error(__FUNCTION__." getUserInfoByUserID call solr_select return false");
        return false;
    }
    else{
        if(!empty($res)){
            $logger->info(__FILE__.__LINE__."the res data is:".var_export($res,true));
            foreach($res as $k=>$u){
                $result[RET_USER_GUID] = $u["Id"];
                $result[RET_WEIBO_ID] = $u["Id"];
                $result[RET_OPENID] =$u["openid"];
                $result[RET_PROVINCE]=$u["province"];
                $result[RET_CITY]=$u["city"];
                $result[RET_COUNTRY]=$u["country"];
                $result[RET_REPOSTDEPTH]=getDepth($u["Id"]);
                $result[RET_USER_NAME] = isset($u["nickname"]) ? $u["nickname"] : "";
                $result[RET_FACE_IMAGE] = isset($u["headimgurl"]) ? $u["headimgurl"] : "";
                $result[RET_GENDER] =  isset($u["sex"]) ? $u["sex"] : "";
                $result[RET_VERIFIED] =  isset($u["users_verified"]) ? $u["users_verified"] : "";
                $result[RET_FANS_COUNT] =  isset($u["users_followers_count"]) ? $u["users_followers_count"] : "";
                $result[RET_PHONESYSTEM]=  isset($u["phonesystem"]) ? $u["phonesystem"] : "";
                $result[RET_PARENT]=$u["parent"];
                $users[] = $result;
                $logger->info(__FILE__.__LINE__."the users data is:".var_export($users,true));
            }
        }
    }
    return $users;
}
//function getFatherData(){
//    global $res_arr, $dsql,$logger;
//    $sql = "select * from weixin_user where parent='0'";
//    $qr = $dsql->ExecQuery($sql);
//    if(!$qr){
//        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
//    }else{
//        $data = array();
//        while ($rec = $dsql->GetArray($qr)) {
//            $data[] = $rec;
//        }
//        $logger->info(__FILE__.__LINE__."the data is:".var_export($data,true));
//    }
//    $result=$data;
//    if($result === false){
//        self_set_error_msg("query mysql is error.");
//    }
//    else{
//        if(isset($result[0])){
//            $res_arr[RET_USER_GUID] = $result[0]['Id'];
//            $res_arr[RET_OPENID]=$result[0]['openid'];
//
//        }
//        else{
//            self_set_error_msg('query mysql is empty');
//        }
//    }
//}


function getMaxDirectRepostCount(){
    global $res_arr,$logger,$dsql,$arg_articleid;
//    $logger->info(__FILE__.__LINE__."the myarticleid is:".var_export($GLOBALS['arg_articleid'],true));
    $sql = "SELECT COUNT(*) AS count FROM weixin_article_user where articleid='$arg_articleid'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the data is:".var_export($data,true));
    }
    $result=$data;
    if($result === false){
        self_set_error_msg("query mysql is error.");
    }
    else{
        if(isset($result[0]['count'])){
            $res_arr[MAX_DIRECT_COUNT] = $result[0]['count']-1;
        }
        else{
            self_set_error_msg('query mysql is empty');
        }
    }
}
//入口
function getrepost(){
    global $res_arr, $logger;
    if(isset($_GET[ARG_FATHER_ID])){
        $arg_father_id = $_GET[ARG_FATHER_ID];
    }
    else{
        self_set_error_msg("opt ".ARG_FATHER_ID." has a wrong value");
    }
    $res_arr = get_weibo_info($arg_father_id);
    $logger->info(__FILE__.__LINE__."the res_arr is".var_export($res_arr,true));
    $res_arr[RET_CHILDREN] = get_repost_info($res_arr);
    $res_arr[RET_IS_EXTEND]=count($res_arr[RET_CHILDREN]);
    $logger->info(__FILE__.__LINE__."the res_arr is".var_export($res_arr,true));
}

function searchcount(){
    global $res_arr,$logger,$dsql;
    $origid = isset($_GET[ARG_ORIGID]) ? $_GET[ARG_ORIGID] : "";
    if(empty($keyword)){
        self_set_error_msg("opt ".ARG_KEYWORD." has a wrong value");
    }
    if(empty($origid)){
        self_set_error_msg("opt ".ARG_ORIGID." has a wrong value");
    }
    //mysql读取数据
    $sql = "SELECT COUNT(*) AS count FROM weixin_user";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the weixin data is:".var_export($data,true));
    }

//    $pathtype = isset($_GET[ARG_PATHTYPE]) ? $_GET[ARG_PATHTYPE] : "";
//    $content_type = "";
//    if($pathtype == "weixinrepost"){
//        $content_type = "+AND+content_type:1";
//    }
//    else if($pathtype == "weibocomment"){
//        $content_type = "+AND+content_type:2";
//    }

    $qr = $data;
    if($qr === false){
        self_set_error_msg("query mysql is error.");
    }
    else{
        $res_arr[SEARCH_COUNT] = $qr;
    }
}

function search(){
    global $res_arr, $logger;
    $logger->info(__FILE__.__LINE__."调用search:");
    $sql = "select * from weixin_article_user where parentid='0'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the searchdata is:".var_export($data,true));
    }

        if($data === false){
            self_set_error_msg("query mysql is error.");
        }
        else{
            if(!empty($data)){
                foreach($data as $key=>$rssearch){

                    $rssearch = retresult($rssearch);
                    $rssearch[RET_IS_EXTEND] = true;
                    $rssearch[RET_CHILDREN] = array();
                    $fathertree = $rssearch;
                    $logger->info(__FILE__.__LINE__."the fathertree is:".var_export($fathertree,true));
                    if(!empty($rssearch[RET_FATHERID]) || !empty($rssearch[RET_ORIG_ID])){ //当有father_guid或有retweeted_guid
                        $fid = "";
                        if(!empty($rssearch[RET_FATHERID])){
                            $fid = $rssearch[RET_FATHERID];
                        }
                        else if(!empty($rssearch[RET_ORIG_ID])){
                            $fid = $rssearch[RET_ORIG_ID];
                        }
                        searchfather($fid, $fathertree);
                    }
                    if(empty($res_arr) && !empty($fathertree)){
                        $res_arr = $fathertree;
                    }
                    else if(!empty($fathertree)){
                        $is_in=false;
                        //判断此分支是否已存在
                        foreach($res_arr[RET_CHILDREN] as $key=>$value){
                            if($value[RET_WEIBO_ID] == $fathertree[RET_CHILDREN][0][RET_WEIBO_ID]){
                                $is_in = true;
                            }
                        }
                        if(!$is_in){
                            $res_arr[RET_CHILDREN][] = $fathertree[RET_CHILDREN][0];
                        }
                    }
                }
            }
            //找每个分支的孩子
            if(!empty($res_arr[RET_CHILDREN])){
                for($i=0; $i<count($res_arr[RET_CHILDREN]);$i++){
                    $child = &$res_arr[RET_CHILDREN][$i];
                    //子节点添加用户信息
                    $guid = getUserGUID($child);
                    $users = getUserInfoByUserID(array($guid));
                    if(count($users) > 0){
                        $child = array_merge($child, $users[0]);
                    }

                    while(true){
                        if(empty($child[RET_CHILDREN])){
                            searchchildren($child);//找到叶子节点，继续按照找转发的规则找孩子
                            break;
                        }
                        //子节点添加用户信息
                        $users = getUserInfoByUserID(array($child[RET_CHILDREN][0][RET_USER_GUID]));
                        if(count($users) > 0){
                            $child[RET_CHILDREN][0] = array_merge($child[RET_CHILDREN][0], $users[0]);
                        }
                        $child = &$child[RET_CHILDREN][0];
                        $users = getUserInfoByUserID(array($child[RET_USER_GUID]));
                        if(count($users) > 0){
                            $child = array_merge($child, $users[0]);
                        }
                    }
                }
            }
        }
    }

function searchchildren(&$father){
    global $logger;
    $children_count_ratio = isset($_GET[CHILDREN_COUNT_RATIO]) ? $_GET[CHILDREN_COUNT_RATIO] : 1;
    $maxcount = isset($_GET[MAX_CHILDREN_COUNT]) ? $_GET[MAX_CHILDREN_COUNT] : 2000;
    $mincount = isset($_GET[MIN_CHILDREN_COUNT]) ? $_GET[MIN_CHILDREN_COUNT] : 10;
    $arg_pathtype = isset($_GET[ARG_PATHTYPE]) ? $_GET[ARG_PATHTYPE] : "weixinrepost";
    //评论和转发取children的个数
    if($arg_pathtype == "weixinrepost"){
        $get_count = round($father[RET_DIRECT_COUNT] * $children_count_ratio);
    }
    else if($arg_pathtype == "weibocomment"){
        $get_count = $father[RET_COMMENTS_COUNT];
    }
    $get_count = $get_count > $maxcount ? $maxcount : $get_count;
    $get_count = $get_count < $mincount ? $mincount : $get_count;
    $father[RET_CHILDREN] = get_repost_info($father);
    if(!empty($father[RET_CHILDREN])){
        for($i=0;$i<count($father[RET_CHILDREN]);$i++){
            $child = &$father[RET_CHILDREN][$i];
            searchchildren($child);
        }
    }
}

function searchfather($fatherid, &$fathertree){
    global $selectfield, $logger;
    $sql = "SELECT COUNT(*) AS count FROM weixin_user where parent='$fatherid'";
    $sql_1 = "select * from weixin_user where parent='$fatherid'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the weixin data is:".var_export($data,true));
    }
//    $qrtotal = solr_select_conds($selectfield, $qstr, 0, 0);
    $qrtotal=$data;
    $totalcount = 0;
    if($qrtotal === false){
        self_set_error_msg("query mysql is error.");
    }
    else{
        $totalcount = $qrtotal;
    }

    $tmprs = $sql_1;
    $logger->info(__FILE__.__LINE__."the tmprs is:".var_export($tmprs,true));
    if($tmprs === false){
        self_set_error_msg("query mysql is error.");
    }
    else{
        $getarr = array();
        $getarr[] = getUserGUID($tmprs[0]);
        $users = getUserInfoByUserID($getarr);
        if(count($users) > 0){
            $res = array_merge($tmprs[0], $users[0]);
        }
        else{
            $res = $tmprs[0];
        }
        $res = retresult($res);
        $res[RET_IS_EXTEND]=true;
        $res[RET_CHILDREN] = array();
        if(empty($fathertree)){
            $fathertree = $res;
        }
        else{
            $res[RET_CHILDREN][] = $fathertree;
            $fathertree = $res;
        }
        if(!empty($res[RET_FATHERID]) && !empty($res['floor'])){
            searchfather($res[RET_FATHERID],$fathertree);
        }
    }
}
/*
 * 根据微信guid获取微信
 */
function get_weibo_info($weibo_id)
{
    global $logger,$dsql,$result;
    $sql = "select * from weixin_user where Id='$weibo_id'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the weixin data is:".var_export($data,true));
    }
    //$is_bridge_status 数据库查询时有此字段,
    //数据库reach_count 直接到达数,solr使用followers_count
    $tmpresult=$data;
    $logger->info(__FILE__.__LINE__."the tmpresult data is:".var_export($tmpresult,true));
    if($tmpresult === false){
        self_set_error_msg("query mysql is error.");
    }
    else if(count($tmpresult) > 0){
        $getarr=getUserGUID($tmpresult[0]);
        $logger->info(__FILE__.__LINE__."the tmpresultid is:".var_export($getarr,true));
        $users = getUserInfoByUserID($getarr);
        $logger->info(__FILE__.__LINE__."the getarr is:".var_export($users,true));
        if(count($users) > 0){
            $result = array_merge($tmpresult[0], $users[0]);
        }
        $result = retresult($result);
        $result[RET_WEIBO_ID]=$users[0]["weiboid"];
        $result[RET_USER_ID]=$users[0]["weiboid"];
        $result[RET_FATHERID]=$users[0]["parentid"];
        $result[RET_REPOSTDEPTH]=getDepth($users[0]["weiboid"]);
        $result[RET_COUNTRY]=$users[0]["country"];
        $result[RET_PROVINCE]=$users[0]["province"];
       // $result[RET_CITY]=$users[0]["city"];
        $result[RET_PHONESYSTEM]=$users[0]["phonesystem"];
//        $result[RET_CONTENT_TYPE]=1;
//        $result[RET_REPOSTS_COUNT]=0;
//        $result[RET_MAXDEPTH]=8;
        return $result;
    }
    else{
        self_set_error_msg("status guid (".$weibo_id.") is not in solr");
    }
}


/*
 * 获取微信及子微信信息
 */
function get_repost_info($father)
{
    global  $logger,$dsql,$arg_articleid;
    $father_1=$father['userid'];
        $sql = "select * from weixin_article_user where parentid='$father_1'and articleid='$arg_articleid'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql is :".$sql. "mysql error is:".$dsql->GetError());
    }else{
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__.__LINE__."the get_repost_info is:".var_export($data,true));
    }
    $qr=$data;
    if($qr === false){
        self_set_error_msg("query mysql is error.");
    }
    else if(count($qr) > 0){
        $inner_res_arr = array();
        $userids = array();
        foreach($qr as $key=>$result){
            $result = retresult($result);
            $logger->info(__FILE__.__LINE__."the result is:".var_export($result,true));
            if(isset($result['userid'])){
                $userids[] = getUserGUID($result);
                $userids=array_unique($userids);
                $logger->info(__FILE__.__LINE__."the userids is:".var_export($userids,true));
            }}
        foreach($userids as $u=>$result_1){
            $result_2=get_weibo_info(($result_1));
            $inner_res_arr[] = $result_2;
            foreach($inner_res_arr as $key=>$val){
                $inner_res_arr[$key]['weiboid'] = $val['userid'];
                $inner_res_arr[$key]['userguid'] = $val['userid'];
//                $inner_res_arr[$key]['retweeted_guid'] = 0;
                $inner_res_arr[$key]['depth']=getDepth($val['userid']);
                $inner_res_arr[$key]['is_extend']=true;
//                $inner_res_arr[$key]['content_type']=1;
//                $inner_res_arr[$key]['username']=$val['nickname'];
//                $inner_res_arr[$key]['gender']=$val['sex'];
//                $inner_res_arr[$key]['reposts_count']=0;
//                $inner_res_arr[$key]['directcount']=0;
//                $inner_res_arr[$key]['comments_count']=0;

            }
        }
        $logger->debug(__FILE__.__LINE__." inner_res_arr  ".var_export($inner_res_arr, true));
        $logger->debug(__FILE__.__LINE__." userids".var_export($userids, true));
        $users = getUserInfoByUserID($userids);
        $logger->debug(__FILE__.__LINE__." userids".var_export($userids, true));
        if(!empty($users)){
            foreach($inner_res_arr as $i=>$ir){
                foreach($users as $k=>$u){
                    if(getUserGUID($ir) == getUserGUID($u)){
                        $inner_res_arr[$i] = array_merge($inner_res_arr[$i], $u);
                        break;
                    }
                }
            }
        }
        return $inner_res_arr;
    }
    else{
        return array();
    }
}
function getUserGUID($uitem){
    global $logger, $userQueryCache;
    if(isset($uitem["userid"])){
        $guid = $uitem["userid"];
    }
    else{
        $found = false;
        if(!empty($userQueryCache)){
            foreach($userQueryCache as $qi=>$qitem){
                if($qitem['Id'] == $uitem['Id']){
                    if((isset($qitem['sourceid']) && isset($uitem['sourceid']) && $qitem['sourceid'] == $uitem['sourceid']) ||
                        (isset($qitem['source_host']) && isset($uitem['source_host']) && $qitem['source_host'] == $uitem['source_host'])){
                        if(isset($qitem['guid'])){
                            $guid = $qitem['guid'];
                            $found = true;
                            break;
                        }
                    }
                }
            }
        }
        if(!$found){
//            $tmpuitem = $uitem;
//            if(isset($uitem["userid"])){
//                $tmpuitem["userid"] = $uitem["userid"]; //getUserGuidOrMore 函数使用的是id
//            }
            $guid = getUserGuidOrMore_1($uitem);
            $logger->info(__FILE__.__LINE__."the tmpuitem is:".var_export($uitem,true));
            $logger->info(__FILE__.__LINE__."the tmpuitem is:".var_export($guid,true));
            if($guid === false){
                $logger->error(__FUNCTION__." 获取用户guid失败 uitem".var_export($uitem, true));
                return false;
            }
            if(!empty($guid)){
                $uitem['guid'] = $guid;
                $userQueryCache[] = $uitem;
            }
        }
    }
    return $guid;
}
function retresult($tmpresult){
    $result = array();
    if(isset($tmpresult["userid"])){
        $result[RET_USER_ID] = $tmpresult["userid"];
    }
    if(isset($tmpresult["Id"])){
        $result[RET_ID] = $tmpresult["Id"];
    }
    if(isset($tmpresult["fromOrg"])){
        $result[RET_FROMORG] = $tmpresult["fromOrg"];
    }
    if(isset($tmpresult[RET_USER_GUID])){
        $result[RET_USER_GUID] = $tmpresult[RET_USER_GUID];
    }
    if(isset($tmpresult[RET_USER_NAME])){
        $result[RET_USER_NAME] = $tmpresult[RET_USER_NAME];
    }
    if(isset($tmpresult[RET_FACE_IMAGE])){
        $result[RET_FACE_IMAGE] = $tmpresult[RET_FACE_IMAGE];
    }
    if(isset($tmpresult[RET_GENDER])){
        $result[RET_GENDER] = $tmpresult[RET_GENDER];
    }
    if(isset($tmpresult[RET_VERIFIED])){
        $result[RET_VERIFIED] = $tmpresult[RET_VERIFIED];
    }
    if(isset($tmpresult[RET_FANS_COUNT])){
        $result[RET_FANS_COUNT] = $tmpresult[RET_FANS_COUNT];
    }
    if(isset($tmpresult["guid"])){
        $result[RET_WEIBO_ID] = $tmpresult["guid"];
    }
    if(isset($tmpresult['floor'])){
        $result['floor'] = $tmpresult["floor"];
    }
    if(isset($tmpresult['sourceid'])){
        $result['sourceid'] = $tmpresult["sourceid"];
    }
    if(isset($tmpresult["parentid"])){
        $result[RET_PARENT] = $tmpresult["parentid"];
    }
    if(isset($tmpresult["total_reposts_count"])){
        $result[RET_TOTAL_COUNT] = $tmpresult["total_reposts_count"];
    }
    if(isset($tmpresult["reposts_count"])){
        $result[RET_REPOSTS_COUNT] = $tmpresult["reposts_count"];
    }
    if(isset($tmpresult["direct_reposts_count"])){
        $result[RET_DIRECT_COUNT] = $tmpresult["direct_reposts_count"];
    }
    if(isset($tmpresult["repost_trend_cursor"])){
        $result[RET_REPOSTDEPTH] = $tmpresult["repost_trend_cursor"];
    }
    //$result[RET_CONTENT_TYPE] = $tmpresult["content_type"];
    if(isset($tmpresult["is_bridge_status"])){
        $result[RET_BRIDGEWEIBO] = $tmpresult["is_bridge_status"];
    }
    if(isset($tmpresult["comments_count"])){
        $result[RET_COMMENTS_COUNT] = $tmpresult["comments_count"];
    }
    if(isset($tmpresult["direct_comments_count"])){
        $result[RET_DIRECT_COMMENT] = $tmpresult["direct_comments_count"];
    }
    if(isset($tmpresult["praises_count"])){
        $result[RET_PRAISES_COUNT] = $tmpresult["praises_count"];
    }
    if(isset($tmpresult["retweeted_guid"])){
        $result[RET_ORIG_ID] = $tmpresult["retweeted_guid"];
    }
    if(isset($tmpresult["created_at"])){
        $result[RET_CREATEAT] = $tmpresult["created_at"];
    }
    if(isset($tmpresult["followers_count"])){
        $result[RET_REACH_COUNT] = $tmpresult["followers_count"];
    }
    if(isset($tmpresult["total_reach_count"])){
        $result[RET_TOTAL_REACH] = $tmpresult["total_reach_count"];
    }
    return $result;
}

    function getUserGuidOrMore_1($rec)
    {
        global $logger;
        $conds = array();
        if (isset($rec['userguid'])) {
            $conds[] = 'users_id:' . $rec['userguid'];
        }
    }

//获取微信单个用户层级
function getDepth($Id){
    global $logger,$data_1,$dsql,$data,$dataCount,$arg_articleid;
    $sql = "select * from weixin_article_user where articleid='$arg_articleid'";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error("sql1 is :".$sql. "mysql error is:".$dsql->GetError());
    }else {
        $data = array();
        while ($rec = $dsql->GetArray($qr)) {
            $data[] = $rec;
        }
        $logger->info(__FILE__ . __LINE__ . "the searchdata is:" . var_export($data, true));
    }
        $data_1=array_depth(tree($data,$Id));
        $logger->info(__FILE__ . __LINE__ . "the keydata:" . var_export($data_1, true));

}

//更新depth字段
//function updateWeixinDepth($task)
//{
//    global $link, $logger;
//    $upendtime = empty($task->endtime) ? '' : " endtime=" . $task->endtime . ",";
//    $updatastatus = empty($task->taskstatus) ? '' : " taskstatus=" . $task->taskstatus . ",";
//
//    $updatasErrorCode = empty($task->error_code) ? '' : " error_code=" . $task->error_code . ",";
//    $updatasErrorMsg = empty($task->error_msg) ? '' : "error_msg="."'"."\"".$task->error_msg."\""."'".",";
//
//    $sql = "update task set " . $upendtime . $updatastatus . $updatasErrorMsg . $updatasErrorCode . " machine= " . "'" . $task->machine . "'" . " where id=" . $task->id;
//    dbconnect();
//    $logger->debug(__FUNCTION__ . __FILE__ . __LINE__ . " update task sql:" . var_export($sql, true));
//    $qr = mysql_query($sql);
//    if (!$qr) {
//        throw new Exception("taskcontroller.php - updateTask() - " . $sql . " - " . mysql_error());
//    }
//    //closeMysql();
//    return mysql_affected_rows($link);
//}

//查找父级，并存入数组
//        function tree($arr,$id){
//            global $count;
//            $count=0;
//            foreach($arr as $u){
//                if($u['userid']==$id){
//                    $count++;
//                    if($u['parent']>'0'){
//                        tree($arr,$u['parent']);
//                    }
//                }
//            }
//            return $count;
//        }
function tree($array,$Id){
    $arr=array();
    $tem=array();
    foreach($array as $v){
        if($v['Id']==$Id){
            $tem=tree($array,$v['parentid']);
            $tem&&$v['parentid']=$tem;
            $arr[]=$v;
        }
    }
    return $arr;
}

function array_depth($array) {
    if(!is_array($array)) return 0;
    $max_depth = 1;
    foreach ($array as $value) {
        if (is_array($value)) {
            $depth = array_depth($value) + 1;

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }
    }
    return $max_depth;
}
?>
