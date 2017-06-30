<?php
define( "SELF", basename(__FILE__) );
include_once( 'includes.php' );
include_once('calc_bridge.php');
include_once('userinfo.class.php');
include_once("authorization.class.php");

//传入参数
define('ARG_FATHER_ID', 'fatherid');
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
define('RET_REPOSTS_COUNT','reposts_count');//api返回的转发数
define('RET_TOTAL_COUNT', 'totalcount');    //总转发数
define('RET_DIRECT_COUNT', 'directcount');    //直接转发数
define('RET_REACH_COUNT', 'reachcount');    //直接到达数
define('RET_TOTAL_REACH', 'totalreach');    //总到达数
define('RET_FANS_COUNT', 'fanscount');    //粉丝数
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
    global $logger;
	$selectFields = array("guid", "users_id","users_screen_name", "users_profile_image_url","users_gender", "users_verified", "users_followers_count");
	$guids = array();
	foreach($userid as $k=>$u){
		//$guids[] = "1u_".$u; 
		if(!empty($u)){
			$guids[] = $u; 
		}
	}
	$users = array();
	$res = solr_select($guids,$selectFields);
	if(empty($res)){
		$logger->debug(__FILE__.__LINE__." res ".var_export($guids, true));
	}
	if($res === false){
		$logger->error(__FUNCTION__." getUserInfoByUserID call solr_select return false");
		return false;
	}
	else{
		if(!empty($res)){
			foreach($res as $k=>$u){
				$result[RET_USER_GUID] = $u["guid"];
				$result[RET_USER_ID] = $u["users_id"];
				$result[RET_USER_NAME] = isset($u["users_screen_name"]) ? $u["users_screen_name"] : "";
				$result[RET_FACE_IMAGE] = isset($u["users_profile_image_url"]) ? $u["users_profile_image_url"] : "";
				$result[RET_GENDER] =  isset($u["users_gender"]) ? $u["users_gender"] : "";
				$result[RET_VERIFIED] =  isset($u["users_verified"]) ? $u["users_verified"] : "";
				$result[RET_FANS_COUNT] =  isset($u["users_followers_count"]) ? $u["users_followers_count"] : "";
				$users[] = $result;
			}
		}
	}
	return $users;
}

function getMaxDirectRepostCount(){
    global $res_arr;
    if(empty($_GET[ARG_ORIGID])){
        self_set_error_msg("opt ".ARG_ORIGID." has a wrong value");
    }
    $origid = $_GET[ARG_ORIGID];
	$pathtype = $_GET[ARG_PATHTYPE];
	$qfield = "direct_reposts_count";
	$content = "";
	if($pathtype == "weibocomment"){
		$qfield = "comments_count";
		$content = "+AND+content_type:(2+0)";
	}
	else if($pathtype == "weiborepost"){
		$qfield = "direct_reposts_count";
		$content = "+AND+content_type:(0+1)";
	}
	$fl = array($qfield);
	$qstr = "(retweeted_guid:".urldecode($origid)."+OR+guid:".urldecode($origid).")".$content;
	$result = solr_select_conds($fl, $qstr, 0, 1, "".$qfield."+desc");
	if($result === false){
        self_set_error_msg("query solr is error.");
    }
	else{
		if(isset($result[0][$qfield])){
            $res_arr[MAX_DIRECT_COUNT] = $result[0][$qfield];
		}
		else{
            self_set_error_msg('query result is empty');
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
    $arg_parent_depth = isset($_GET[ARG_PARENT_DEPTH]) ? $_GET[ARG_PARENT_DEPTH] : -1;
    $arg_repost_count = isset($_GET[ARG_REPOST_COUNT]) ? $_GET[ARG_REPOST_COUNT] : -1;
    $arg_limit = isset($_GET[ARG_LIMIT]) ? $_GET[ARG_LIMIT] : 0;
    $arg_count = isset($_GET[ARG_COUNT]) ? $_GET[ARG_COUNT] : 50;
	$arg_pathtype = isset($_GET[ARG_PATHTYPE]) ? $_GET[ARG_PATHTYPE] : "weiborepost";
	$arg_is_center = isset($_GET[ARG_IS_CENTER]) ? $_GET[ARG_IS_CENTER] : 0;
	$res_arr = get_weibo_info($arg_father_id, $arg_is_center, $arg_pathtype);
    $res_arr[RET_CHILDREN] = get_repost_info($res_arr, $arg_repost_count, $arg_limit, $arg_count, $arg_pathtype);
}

function searchcount(){
    global $res_arr;
    $keyword = isset($_GET[ARG_KEYWORD]) ? $_GET[ARG_KEYWORD] : "";
    $origid = isset($_GET[ARG_ORIGID]) ? $_GET[ARG_ORIGID] : "";
    if(empty($keyword)){
        self_set_error_msg("opt ".ARG_KEYWORD." has a wrong value");
    }
    if(empty($origid)){
        self_set_error_msg("opt ".ARG_ORIGID." has a wrong value");
    }
	$userid = getUserIdByScreenName($keyword);
	$pathtype = isset($_GET[ARG_PATHTYPE]) ? $_GET[ARG_PATHTYPE] : "";
	$content_type = "";
	if($pathtype == "weiborepost"){
		$content_type = "+AND+content_type:1";
	}
	else if($pathtype == "weibocomment"){
		$content_type = "+AND+content_type:2";
	}

	$qstr = "retweeted_guid:".$origid."+AND+userid:".$userid."".$content_type;
	$qr = solr_select_conds("", $qstr, 0, 0); 
	if($qr === false){
        self_set_error_msg("query solr is error.");
    }
	else{
        $res_arr[SEARCH_COUNT] = $qr;
	}
}

function search(){
    global $res_arr,$selectfield, $logger;
    $keyword = isset($_GET[ARG_KEYWORD]) ? $_GET[ARG_KEYWORD] : "";
    $origid = isset($_GET[ARG_ORIGID]) ? $_GET[ARG_ORIGID] : "";
    $arg_pathtype = isset($_GET[ARG_PATHTYPE]) ? $_GET[ARG_PATHTYPE] : "";
    if(empty($keyword)){
        self_set_error_msg("opt ".ARG_KEYWORD." has a wrong value");
    }
    if(empty($origid)){
        self_set_error_msg("opt ".ARG_ORIGID." has a wrong value");
    }
    //找到所有需要查询的孩子
    $userid = getUserIdByScreenName($keyword);
	$content_type = "";
	if($arg_pathtype == "weiborepost"){
		$content_type = "+AND+content_type:1";
	}
	else if($arg_pathtype == "weibocomment"){
		$content_type = "+AND+content_type:2";
	}
	$qstr = "userid:".$userid."+AND+retweeted_guid:".$origid."".$content_type; 
	$qrtotal = solr_select_conds($selectfield, $qstr, 0, 0);
	$totalcount = 0;
	if($qrtotal === false){
        self_set_error_msg("query solr is error.");
    }
	else{
        $totalcount = $qrtotal;
	}
	if($totalcount > 0){
		$sortfield = "";
		if($arg_pathtype == "weibocomment"){
			$sortfield = "";
		}
		else if($arg_pathtype == "weiborepost"){
			$sortfield = "repost_trend_cursor+asc";
		}
		$qr = solr_select_conds($selectfield, $qstr, 0, $totalcount, $sortfield);
		if($qr === false){
			self_set_error_msg("query solr is error.");
		}
		else{
			if(!empty($qr)){
				foreach($qr as $key=>$rssearch){

					$rssearch = retresult($rssearch);
					$rssearch[RET_IS_EXTEND] = true;
					$rssearch[RET_CHILDREN] = array();
					$fathertree = $rssearch;
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
}

function searchchildren(&$father){
	global $logger;
    $children_count_ratio = isset($_GET[CHILDREN_COUNT_RATIO]) ? $_GET[CHILDREN_COUNT_RATIO] : 1;
    $maxcount = isset($_GET[MAX_CHILDREN_COUNT]) ? $_GET[MAX_CHILDREN_COUNT] : 2000; 
    $mincount = isset($_GET[MIN_CHILDREN_COUNT]) ? $_GET[MIN_CHILDREN_COUNT] : 10;
	$arg_pathtype = isset($_GET[ARG_PATHTYPE]) ? $_GET[ARG_PATHTYPE] : "weiborepost";
	//评论和转发取children的个数
	if($arg_pathtype == "weiborepost"){
		$get_count = round($father[RET_DIRECT_COUNT] * $children_count_ratio);
	}
	else if($arg_pathtype == "weibocomment"){
		$get_count = $father[RET_COMMENTS_COUNT];
	}
	$get_count = $get_count > $maxcount ? $maxcount : $get_count; 
    $get_count = $get_count < $mincount ? $mincount : $get_count;
    $father[RET_CHILDREN] = get_repost_info($father, -1,0,$get_count, $arg_pathtype);
    if(!empty($father[RET_CHILDREN])){
        for($i=0;$i<count($father[RET_CHILDREN]);$i++){
            $child = &$father[RET_CHILDREN][$i];
            searchchildren($child);
        }        
    }
}

function searchfather($fatherid, &$fathertree){
	global $selectfield, $logger;
	$qstr = "guid:".$fatherid."";
	$qrtotal = solr_select_conds($selectfield, $qstr, 0, 0);
	$totalcount = 0;
	if($qrtotal === false){
        self_set_error_msg("query solr is error.");
    }
	else{
        $totalcount = $qrtotal;
	}
	$tmprs = solr_select_conds($selectfield, $qstr, 0, $totalcount);
	if($tmprs === false){
        self_set_error_msg("query solr is error.");
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
 * 根据微博guid获取微博
 */
function get_weibo_info($weibo_id, $is_center=0, $arg_pathtype)
{
    global $logger, $selectfield;
	$qstr = "guid:".$weibo_id.""; 
	//$is_bridge_status 数据库查询时有此字段,
	//数据库reach_count 直接到达数,solr使用followers_count
	$tmpresult = solr_select_conds($selectfield, $qstr, 0, 1); 
	if($tmpresult === false){
        self_set_error_msg("query solr is error.");
    }
	else if(count($tmpresult) > 0){
		$getarr = array();
		$getarr[] = getUserGUID($tmpresult[0]);
		$users = getUserInfoByUserID($getarr);
		if(count($users) > 0){
			$result = array_merge($tmpresult[0], $users[0]);
		}
		$result = retresult($result);
		$result[RET_IS_EXTEND] = 1;
		if($is_center){
			if($arg_pathtype == "weiborepost"){
				if($result[RET_CONTENT_TYPE] == 0){//原创的reposts_count为总转发，total_reposts_count为直接转发
					$flmaxdepth = "repost_trend_cursor";
					$qstrmaxdepth = "retweeted_guid:".$weibo_id."+AND+content_type:1";
					$urlmaxdepth = SOLR_URL_SELECT."?q=".$qstrmaxdepth."&facet=off&fl=".$flmaxdepth."&start=0&rows=1&sort=repost_trend_cursor+desc";
					$qmaxdepth = getSolrData($urlmaxdepth);
					if(isset($qmaxdepth['errorcode'])){
						self_set_error_msg("url is {$urlmaxdepth}, query error is ".$qmaxdepth['errormsg']);
					}
					else if(count($qmaxdepth['query']['docs']) > 0){
						$resmaxdepth = $qmaxdepth['query']['docs'][0];
						$result[RET_MAXDEPTH] = empty($resmaxdepth["repost_trend_cursor"]) ? 0 : $resmaxdepth["repost_trend_cursor"];
					}
				}
				else if($result[RET_CONTENT_TYPE] == 1){
					$flmaxdepth = "repost_trend_cursor";
					$qstrmaxdepth = "retweeted_guid:".$result[RET_ORIG_ID]."+AND+content_type:1";
					$urlmaxdepth = SOLR_URL_SELECT."?q=".$qstrmaxdepth."&facet=off&fl=".$flmaxdepth."&start=0&rows=1&sort=repost_trend_cursor+desc";
					$qmaxdepth = getSolrData($urlmaxdepth);
					if(isset($qmaxdepth['errorcode'])){
						self_set_error_msg("url is {$urlmaxdepth}, query error is ".$qmaxdepth['errormsg']);
					}
					else if(count($qmaxdepth['query']['docs']) > 0){
						$resmaxdepth = $qmaxdepth['query']['docs'][0];
						$oricursor = empty($resmaxdepth["repost_trend_cursor"]) ? 0 : $resmaxdepth["repost_trend_cursor"];
						$result[RET_MAXDEPTH] = $oricursor - $result[RET_REPOSTDEPTH] + 1;
					}
				}
			}
			else if($arg_pathtype == "weibocomment"){
				$flmaxdepth = "repost_trend_cursor";
				$qstrmaxdepth = "retweeted_guid:".$weibo_id."+AND+content_type:2";
				$urlmaxdepth = SOLR_URL_SELECT."?q=".$qstrmaxdepth."&facet=off&fl=".$flmaxdepth."&start=0&rows=1&sort=repost_trend_cursor+desc";
				$qmaxdepth = getSolrData($urlmaxdepth);
				if(isset($qmaxdepth['errorcode'])){
					self_set_error_msg("url is {$urlmaxdepth}, query error is ".$qmaxdepth['errormsg']);
				}
				else if(count($qmaxdepth['query']['docs']) > 0){
					$resmaxdepth = $qmaxdepth['query']['docs'][0];
					$result[RET_MAXDEPTH] = empty($resmaxdepth["repost_trend_cursor"]) ? 0 : $resmaxdepth["repost_trend_cursor"];
				}
				$result[RET_REPOSTDEPTH] = 1;
			}
		}
        return $result;
	}
	else{
        self_set_error_msg("status guid (".$weibo_id.") is not in solr");
	}
}


/*
 * 获取微博及子微博信息
 */
function get_repost_info($father, $repost_count, $limit, $count, $arg_pathtype)
{
    global $selectfield, $logger;
	$tstr = "";
	if($arg_pathtype == "weibocomment"){
		$tstr = "comments_count:[".$repost_count."+TO+*]+AND+content_type:2+AND+";
	}
	else if($arg_pathtype == "weiborepost"){
		$tstr = "total_reposts_count:[".$repost_count."+TO+*]+AND+content_type:1+AND+"; 
	}
	$qstr = $tstr."father_guid:".$father[RET_WEIBO_ID]."";
	$sortfield = "";
	if($arg_pathtype == "weibocomment"){
		$sortfield = "comments_count";
	}
	else if($arg_pathtype == "weiborepost"){
		$sortfield = "total_reposts_count";
	}
	$qr = solr_select_conds($selectfield, $qstr, $limit, $count, "".$sortfield."+desc");
	if($qr === false){
        self_set_error_msg("query solr is error.".$qstr);
    }
	else if(count($qr) > 0){
        $i=isset($limit) ? $limit : 0;
        $inner_res_arr = array();
		$userids = array();
		foreach($qr as $key=>$result){
			$result = retresult($result);
			if(isset($result['userid'])){
				$userids[] = getUserGUID($result);
			}
			$checkfield = RET_TOTAL_COUNT;
			if($arg_pathtype == "weibocomment"){
				$checkfield = RET_COMMENTS_COUNT;
			}
            if($i < MAX_EXTEND_COUNT){ 
                if($arg_pathtype == "weiborepost"){
                	if($father[RET_CONTENT_TYPE] == 0){
                        $result[RET_IS_EXTEND] = $result[$checkfield] > 0;//原创的孩子只要总转发大于0且排名靠前就延伸
                    }
                    else if($father[RET_CONTENT_TYPE] == 1){
						$result[RET_IS_EXTEND] = checkExtendWeibo($father[RET_DIRECT_COUNT],$father[$checkfield],$result[$checkfield]);
                    }
                }
                else if($arg_pathtype == "weibocomment"){
                	if($father[RET_CONTENT_TYPE] == 0 || $father[RET_CONTENT_TYPE] == 1){
                        $result[RET_IS_EXTEND] = $result[$checkfield] > 0;//源微博的孩子只要总转发大于0且排名靠前就延伸
                    }
                    else if($father[RET_CONTENT_TYPE] == 2){
                    	$result[RET_IS_EXTEND] = true;
                    }
                }
            }
            else{
                $result[RET_IS_EXTEND] = false;
            } 
			$inner_res_arr[] = $result;
            $i++;
		}
		$logger->debug(__FILE__.__LINE__." inner_res_arr  ".var_export($inner_res_arr, true));
		$logger->debug(__FILE__.__LINE__." userids".var_export($userids, true));
		$users = getUserInfoByUserID($userids);
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
	if(isset($uitem["userguid"])){
		$guid = $uitem["userguid"];
	}
	else{
		$found = false;
		if(!empty($userQueryCache)){
			foreach($userQueryCache as $qi=>$qitem){
				if($qitem['userid'] == $uitem['userid']){
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
			$tmpuitem = $uitem;
			if(isset($uitem["userid"])){
				$tmpuitem["id"] = $uitem["userid"]; //getUserGuidOrMore 函数使用的是id
			}
			$guid = getUserGuidOrMore($tmpuitem);
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
	if(isset($tmpresult['source_host'])){
		$result['source_host'] = $tmpresult["source_host"];
	}
	if(isset($tmpresult['sourceid'])){
		$result['sourceid'] = $tmpresult["sourceid"];
	}
	if(isset($tmpresult["father_guid"])){
		$result[RET_FATHERID] = $tmpresult["father_guid"];
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
	$result[RET_CONTENT_TYPE] = $tmpresult["content_type"];
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
?>
