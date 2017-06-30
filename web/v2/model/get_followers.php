<?php
/*
 * 传入参数
 *  userID：用户ID
 *  depth: 获取深度
 *  limit：获取的粉丝个数，查询粉丝时，根据粉丝的粉丝数大小排序，取前limit个
 *  fansCursor：获取粉丝时的起始位置，和limit参数配合使用
 *              1，当fansCursor有值时，查询粉丝时，以fansCursor为起始位置，
 *                  向后查询limit个
 *              2，当fansCursor为空时，只查询前limit个
 * 返回结果
 *  userID：用户ID，即传入参数的userID
 *  userName：用户名称
 *  fansCount：粉丝总数，包括粉丝数小于limit的粉丝，即全部粉丝
 *  children：存放userID用户的粉丝
 *   
 */

include_once( 'includes.php' );
include_once('userinfo.class.php');
include_once("authorization.class.php");
define( "CONFIG_TYPE", 3);    //需要在include common.php之前，定义CONFIG_TYP

//方便数据库查询中，将查询结果直接返回成childs结果中的key值
define('USER_ID', "userid");
define('USER_NAME', "username");
define('FOLLOWER_COUNT', "fanscount");
define('FRIENDS_COUNT', "fricount");
define('STATUES_COUNT', "stacount");
define('CHILDS', "children");
define('DEPTH','depth');//本次获取的深度
define('PARENTDEPTH', 'parentdepth');//父节点的深度
define('LIMIT_CURSOR','requestlimit');
define('LIMIT_COUNT','requestcount');
define('RET_GENDER','gender');
define('RET_VERIFIED','verified');
session_start();
initLogger(LOGNAME_WEBAPI);
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
//存放返回结果
$followers;
$depth = 5;//默认获取粉丝的层数
$follower_limit_count = 5;
$follower_cursor = 0;
$bridgeusercount = 5;//额外获取的桥接用户个数
$parentdepth;
$logger;
/*
 * set error msg
 */
function self_set_error_msg($error_str)
{
    $error['error'] = $error_str;
    $msg = json_encode($error);
    echo $msg;
    exit;
}

/*
 * get follower count
 */
function get_user_name_and_follower_count($uid)
{
    global $followers, $logger;
	//http://192.168.0.102:8080/solrstore/select?q=users_id:1642909335&fl=users_screen_name,users_gender,users_verified,users_followers_count,users_friends_count,users_statuses_count&indent=on
	$url = SOLR_URL_SELECT."?q=users_id:".$uid."&fl=users_screen_name,users_gender,users_verified,users_followers_count,users_friends_count,users_statuses_count";
	//$logger->debug("get_user_name_and_follower_count url ".$url);
	$r = getSolrData($url);
	if(isset($r['errorcode'])){
		$logger->error(__FUNCTION__." {$r['errormsg']}");
		$followers = null;
	}
	else{
    	$datalist = $r['query']['docs'][0];
        $followers[FOLLOWER_COUNT] = isset($datalist['users_followers_count']) ? $datalist['users_followers_count'] : "";
        $followers[FRIENDS_COUNT] =  isset($datalist['users_friends_count']) ? $datalist['users_friends_count'] : "";
        $followers[STATUES_COUNT] =  isset($datalist['users_statuses_count']) ? $datalist['users_statuses_count'] : "";
        $followers[USER_NAME] = isset($datalist['users_screen_name']) ? $datalist['users_screen_name'] : "";
        $followers[RET_GENDER] = isset($datalist['users_gender']) ? $datalist['users_gender'] : ""; 
        $followers[RET_VERIFIED] = isset($datalist['users_verified']) ? $datalist['users_verified'] : ""; 
	}

	/*
    $sql = "select screen_name,followers_count,gender, verified, friends_count, statuses_count from user_new where id='".$uid."'";
	$logger->debug("get_user_name_and_follower_count sql ".$sql);
    $q = mysql_query($sql);
    if (!$q)
    {
        $sql_note = mysql_errno()." file is ".__FILE__." line is ".__LINE__;
        self_set_error_msg($sql_note);
    }
    $r = mysql_fetch_array($q);
    if (!$r)
    {
        $followers = null;
    }
    else
    {
        $followers[FOLLOWER_COUNT] = $r['followers_count'];
        $followers[FRIENDS_COUNT] = $r['friends_count'];
        $followers[STATUES_COUNT] = $r['statuses_count'];
        $followers[USER_NAME] = $r['screen_name'];
        $followers[RET_GENDER] = $r['gender'];
        $followers[RET_VERIFIED] = $r['verified'];
    }
	 */
}

/*
 * get followers
 */
function get_followers(&$fol)
{
    global $follower_limit_count, $userID, $follower_cursor,$depth, $parentdepth, $bridgeusercount, $logger;
    if( ($fol[DEPTH]-$parentdepth) < $depth){
        $cdepth = $fol[DEPTH] + 1;
        if($bridgeusercount > 0){
			$urlb = SOLR_URL_SELECT."?q=users_friends_id:".$fol[USER_ID]."+AND+users_is_bridge_user:1&fl=users_id,users_gender,users_verified,users_screen_name,users_followers_count,users_friends_count,users_statuses_count&sort=users_followers_count+desc&start=0&rows=".$bridgeusercount."";
			//$logger->debug("get_followers sqlb ".$urlb);
			$result = getSolrData($urlb);
			if(isset($result['errorcode'])){
				$logger->error(__FUNCTION__." {$result['errormsg']}");
				$followers = null;
			}
			else{
				$datalist = $result['query']['docs'];
				foreach($datalist as $key=>$item){
					$rb['bridgeuser'] = 1;
					$rb[DEPTH] = $cdepth;
					$rb[USER_ID] = $item['users_id'];
					$rb[USER_NAME] = isset($item['users_screen_name']) ? $item['users_screen_name'] : "";
					$rb[RET_VERIFIED] = isset($item['users_verified']) ? $item['users_verified'] : "";
					$rb[RET_GENDER] = isset($item['users_gender']) ? $item['users_gender'] : ""; 
					$rb[FOLLOWER_COUNT] = isset($item['users_followers_count']) ? $item['users_followers_count'] : "";
					$rb[FRIENDS_COUNT] = isset($item['users_friends_count']) ? $item['users_friends_count'] : "";
					$rb[STATUES_COUNT] = isset($item['users_statuses_count']) ? $item['users_statuses_count'] : "";
					$fol[CHILDS][] = $rb;
				}
			}

			/*
            $sqlb = "select ".$cdepth." as ".DEPTH.",a.id as ".USER_ID.",a.gender, a.verified,a.screen_name as ".USER_NAME.", a.followers_count as ".FOLLOWER_COUNT.",a.friends_count as ".FRIENDS_COUNT.", a.statuses_count as ".STATUES_COUNT." from user_new a inner join user_followers b
                on a.id =b.followerID 
                where b.userID='".$fol[USER_ID]."' and a.is_bridge_user = 1 order by a.followers_count desc LIMIT 0,".$bridgeusercount;

			$logger->debug("get_followers sqlb ".$sqlb);
            $qb = mysql_query($sqlb);
            if (!$qb)
            {
                $sql_note = mysql_errno()." file is ".__FILE__." line is ".__LINE__;
                self_set_error_msg($sql_note." ".$sqlb);
            }
            while ($rb = mysql_fetch_array($qb, MYSQL_ASSOC))
            {
                $r['bridgeuser'] = 1;
                $fol[CHILDS][] = $rb;
            }
			 */
        }
        $bcount = isset($fol[CHILDS]) ? count($fol[CHILDS]) : 0;
		//http://192.168.0.102:8080/solrstore/select?q=users_friends_id:1642909335+AND+!users_is_bridge_user:1&fl=users_id,users_gender,users_verified,users_screen_name,users_followers_count,users_friends_count,users_statuses_count&start=0&rows=50&sort=users_followers_count+desc&indent=on
		$url = SOLR_URL_SELECT."?q=users_friends_id:".$fol[USER_ID]."+AND+!users_is_bridge_user:1&fl=users_id,users_gender,users_verified,users_screen_name,users_followers_count,users_friends_count,users_statuses_count&start=".$follower_cursor."&rows=".($follower_limit_count - $bcount)."&sort=users_followers_count+desc";
		//$logger->debug("get_followers url ".$url);
		$result = getSolrData($url);
		if(isset($result['errorcode'])){
			$logger->error(__FUNCTION__." {$result['errormsg']}");
		}
		else{
			$datalist = $result['query']['docs'];
			foreach($datalist as $key=>$item){
				$r['bridgeuser'] = 0;
				$r[DEPTH] = $cdepth;
				$r[USER_ID] = $item['users_id'];
				$r[USER_NAME] = isset($item['users_screen_name']) ? $item['users_screen_name'] : "";
				$r[RET_VERIFIED] = isset($item['users_verified']) ? $item['users_verified'] : "";
				$r[RET_GENDER] = isset($item['users_gender']) ? $item['users_gender'] : ""; 
				$r[FOLLOWER_COUNT] = isset($item['users_followers_count']) ? $item['users_followers_count'] : "";
				$r[FRIENDS_COUNT] = isset($item['users_friends_count']) ? $item['users_friends_count'] : "";
				$r[STATUES_COUNT] = isset($item['users_statuses_count']) ? $item['users_statuses_count'] : "";
				$fol[CHILDS][] = $r;
			}
		}

		/*
        $sql = "select ".$cdepth." as ".DEPTH.",a.id as ".USER_ID.",a.gender, a.verified,a.screen_name as ".USER_NAME.", a.followers_count as ".FOLLOWER_COUNT.",a.friends_count as ".FRIENDS_COUNT.", a.statuses_count as ".STATUES_COUNT." from user_new a inner join user_followers b
                on a.id =b.followerID 
                where b.userID='".$fol[USER_ID]."' and (a.is_bridge_user =0 or a.is_bridge_user is null)  order by a.followers_count desc LIMIT ".$follower_cursor.",".($follower_limit_count - $bcount);
		$logger->debug("get_followers sql ".$sql);
        $q = mysql_query($sql);
        if (!$q)
        {
            $sql_note = mysql_errno()." file is ".__FILE__." line is ".__LINE__;
            self_set_error_msg($sql_note." ".$sql);
        }
        while ($r = mysql_fetch_array($q, MYSQL_ASSOC))
        {
            $r['bridgeuser'] = 0;
            $fol[CHILDS][] = $r;
        }
		 */
        if(isset($fol[CHILDS])){
			//$logger->debug("followers ".var_export($fol, true));
            foreach($fol[CHILDS] as $key => $value){
                get_followers($fol[CHILDS][$key]);
            }
        }
        else{
            $fol[CHILDS] = array();
        }
    }

}
if (empty($_GET))
{
    self_set_error_msg("opt is null");
}
else
{
    $userID = isset($_GET[USER_ID]) ? $_GET[USER_ID] : null;
    $follower_cursor = isset($_GET[LIMIT_CURSOR]) ? $_GET[LIMIT_CURSOR] : $follower_cursor;
    $follower_limit_count = isset($_GET[LIMIT_COUNT]) ? $_GET[LIMIT_COUNT] : $follower_limit_count;
    $depth = isset($_GET[DEPTH]) ? $_GET[DEPTH] : $depth;
    $parentdepth = $_GET[PARENTDEPTH];
    $bridgeusercount  = isset($_GET['bridgeusercount']) ? $_GET['bridgeusercount'] : $bridgeusercount;
    if (null == $userID || !isset($parentdepth) )//|| null == $follower_limit_count)
    {
        self_set_error_msg("opt has error");
    }
    $followers[USER_ID] = $userID;
    $followers[DEPTH] = $parentdepth;
	
	//$logger->debug("followers ".var_export($followers, true));
}
if($followers[DEPTH] == 0){
    get_user_name_and_follower_count($userID);
}
get_followers($followers);
//sleep(0);
if(!$followers){
    echo "";
}
else{
    $json_str = json_encode($followers);
    echo $json_str;
}
