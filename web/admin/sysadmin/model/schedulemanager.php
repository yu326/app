<?php
/*
 * 定时任务管理API
 */
define("SELF", basename(__FILE__));
define("GET_DATA" , 3);    //通过该标识，获取配置信息和任务信息
define("CONFIG_TYPE", GET_DATA);    //需要在include common.php之前，定义CONFIG_TYPE

define("TYPE","type");//根据参数类型调用不同的函数
define("ADD","add");
define("EDIT","edit");
define("CHANGE","changestatus");
define("DELETE","delete");
define("DELHISTORY","delhistory");
define("GETCURRENT","getcurrent");
define("GETHISTORY","gethistory");


include_once('includes.php');
include_once('datatableresult.php');
include_once('commonFun.php');
include_once('taskcontroller.php');
include_once('userinfo.class.php');
include_once('weibo_config.php');
include_once( 'weibo_class.php' );
include_once( 'saetv2.ex.class.php' );

ini_set('include_path',get_include_path().'/lib');


initLogger(LOGNAME_WEBAPI);//初始化日志配置
session_start();


$arg_type = "";
if(isset($_POST[TYPE])){
	$arg_type = $_POST[TYPE];
}
else if(isset($_GET[TYPE])){
		$arg_type = $_GET[TYPE];
}
else if(isset($HTTP_RAW_POST_DATA)){
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
	$arg_type = $arrsdata["type"];
}
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);

switch($arg_type){
	case ADD:
		addSchedule();
		break;
	case EDIT:
		editSchedule();
		break;
	case CHANGE:
		changeScheduleStatus();
		break;
	case DELETE:
		deleteSchedule();
		break;
	case DELHISTORY:
		deleteScheduleHistory();
		break;
	case GETCURRENT:
		getCurrentSchedule();
		break;
	case GETHISTORY:
		getScheduleHistory();
		break;
	default:
		$logger->error(SELF." 参数错误：".$arg_type);
}
function getuser($id, $screen_name, $source){
	$params = array();
	$params["id"] = $id;
	$params["screen_name"] = $screen_name;
	$params["source"] = $source;
	$timeline = "getuser";
	return getSinaInfo($params, $timeline);
}

function createSchedule(){
	global $logger,$dsql;
	$result = array('result'=>true,'msg'=>'','schedule'=>null);
	$tasktype = $_POST["tasktype"];
	$task = $_POST["task"];
	$taskclassify =isset($_POST['specifiedType'])? $_POST['specifiedType']: NULL;
	$spcfdmac = isset($_POST['specifiedMac'])? $_POST['specifiedMac']: NULL;
	$logger->debug(" - createSchedule() specifiedType:[".$taskclassify."]  spcfdmac:[".$spcfdmac."].");
	if(!$tasktype || !$task){
		$result['result'] = false;
		$result['msg'] = '参数错误';
		$logger->error(SELF." - ".__FUNCTION__." 参数错误");
	}
	else{
		$taskparams = (object)array();
		$local = empty($_POST["local"]) ? 0 : 1;
		$remote = empty($_POST["remote"]) ? 0 : 1;
		if($local == 0 && $remote == 0)
		{
			$local = 1;
		}
		
		$conflictdelay = empty($_POST["conflictdelay"]) ? 60 : (int)$_POST["conflictdelay"];
		if($local == 1 || $task == TASK_REPOST_TREND || $task == TASK_COMMENTS){
			$iscommit = $_POST['iscommit'];
			if($iscommit == 1){
				$taskparams->iscommit = true;
			}
			else{
				$taskparams->iscommit = false;
			}
		}
		//添加字典方案
		//$logger->error(TASKMANAGER."abc".var_export($abc,true));
		if(isset($_POST['dictionaryPlan']) && $_POST['dictionaryPlan'] != ''){
			$taskparams->dictionaryPlan = $_POST['dictionaryPlan'];
		}
		//重新分析
		if($task == TASK_SYNC){
			if(!empty($_POST["analysistime"])){
				$aystime = strtotime($aystime);
			}
			else{
				$aystime = time();
			}
			$taskparams->maxanalysistime = $aystime;
			$taskparams->each_count = empty($_POST["each_count"]) ? 200 : $_POST["each_count"];
			if(!empty($_POST['starttime'])){
				$taskparams->min_created_time = strtotime($_POST['starttime']);
			}
			if(!empty($_POST['endtime'])){
				$taskparams->max_created_time = strtotime($_POST['endtime']);
			}
			if(!empty($_POST['urls'])){
				$urls = explode("\r\n", trim($_POST['urls']));
				$taskparams->urls = $urls;
			}
			if(isset($_POST['source_host']) && $_POST['source_host'] != ''){
				$source_host = split(",", $_POST['source_host']);
				$taskparams->source_host = $source_host;
			}

			if(!empty($_POST['startdataindex'])){
				$taskparams->startdataindex = $_POST['startdataindex'];
			}
			if(!empty($_POST['enddataindex'])){
				$taskparams->enddataindex = $_POST['enddataindex'];
			}
			if(!empty($_POST['source'])){
				$taskparams->source =$_POST['source'];
			}
			if(empty($_POST['func']) && empty($_POST['func_other'])){
				$result = array('result'=>false,'msg'=>'未选择功能');
				$logger->error(SELF." - ".__FUNCTION__." 参数错误 func is empty");
			}
			else{
				if(!empty($_POST['func'])){
					$taskparams->tokenize_fields = $_POST['func'];
				}
				if(!empty($_POST['func_other'])){
					if(empty($_POST['otherfields'])){
						$result = array('result'=>false,'msg'=>'未选择字段');
						$logger->error(SELF." - ".__FUNCTION__." 参数错误 otherfields is empty");
					}
					else{
						$taskparams->other_fields = $_POST['otherfields'];
					}
				}
			}
		}

		//抓取微博
		if($task == TASK_WEIBO){
			$sourceid = get_sourceid_from_url("s.weibo.com");
			$taskparams->source = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if($local == 1){
				if(!empty($_POST['each_count'])){
					$taskparams->each_count =$_POST['each_count'];
				}
			}
			if(isset($_POST['usertype']) && $_POST['usertype'] != ''){
				$taskparams->usertype = $_POST['usertype'];
			}
			if(!empty($_POST["min_follower_count"])){
				$taskparams->min_follower_count = $_POST["min_follower_count"];
			}
			if(!empty($_POST['inputtype'])){
				$taskparams->inputtype = $_POST['inputtype'];
			}
			if(!empty($_POST['addeduser'])){
				$adduser = split(",",$_POST['addeduser']);
				$taskparams->users = $adduser;
				//当时用户昵称时需要查询出对应的id , 解决用户修改昵称后,不能通过昵称抓取微博的问题
				if(!empty($_POST['inputtype']) && $_POST['inputtype'] == 'screen_name'){
					$userids = array();
					$noexistuser = array();
					foreach($adduser as $ai=>$aitem){ //循环添加到昵称去新浪查询对应的id
						$sourceid = get_sourceid_from_url("s.weibo.com");
						$cu_r = getuser(NULL, $aitem, $sourceid);
						if ($cu_r['result'] && !empty($cu_r['user']))
						{
							$user = $cu_r['user'];
							$userids[] = $user["id"]; //把用户id存在数组中
						}
						else if (!empty($cu_r['nores']))
						{
							$result['result'] = false;
							$result['nores'] = true;
							$result['msg'] = $cu_r['msg'];
							break;
						}
						else
						{
							if(isset($cu_r['error_code']) && $cu_r['error_code'] == ERROR_USER_NOT_EXIST)
							{
								$noexistuser[] = $aitem;
							}
						}
					}
					if(!$result['result']){ //无资源
						$logger->debug(__FILE__.__LINE__." nores: ".var_export($result, true));
					}
					if(!empty($noexistuser)){
						$logger->debug(__FILE__.__LINE__." ".$cu_r['msg']." : ".var_export($noexistuser, true));
						$result['result'] = false;
						$result['msg'] = "查询新浪,不存在的昵称";
						$result["noexistuser"] = $noexistuser;
					}
					$taskparams->userids = $userids;
				}
			}
			if($remote == 1){
				if(!empty($_POST['taskpagestyletype'])){
					$taskpagestyletype = (int)$_POST['taskpagestyletype'];
				}
				if(!empty($_POST['config'])){
					$taskparams->config = (int)$_POST['config'];
				}
				if(!empty($_POST['duration'])){
					$taskparams->duration = (int)$_POST['duration'];
				}
				if(isset($_POST['isseed'])){
					$taskparams->isseed = (int)$_POST['isseed'];
				}
				if(!empty($_POST['starttime'])){
					$taskparams->starttime = strtotime($_POST['starttime']);
				}
				if(!empty($_POST['endtime'])){
					$taskparams->endtime = strtotime($_POST['endtime']);
				}
				if(!empty($_POST['step'])){
					$taskparams->step = $_POST['step'];
				}
				if(!empty($_POST['accountid'])){
					$taskparams->accountid = split(",", $_POST['accountid']);
				}
				if(isset($_POST['logoutfirst'])){
					$taskparams->logoutfirst = (int)$_POST['logoutfirst'];
				}
				if(isset($_POST['isswitch'])){
					$taskparams->isswitch = (int)$_POST['isswitch'];
					if($taskparams->isswitch){
						$taskparams->switchpage = (int)$_POST['switchpage'];
						$taskparams->switchtime = (int)$_POST['switchtime'];
						$taskparams->globalaccount = (int)$_POST['globalaccount'];
					}
				}
				if(!empty($_POST['relativestart'])){
					$relativestart = $_POST['relativestart'];
				}
				if(!empty($_POST['relativeend'])){
					$relativeend = $_POST['relativeend'];
				}
			}
		}
		//分析转发轨迹
		if($task == TASK_REPOSTPATH){
			$oriurls = array();
			if(!empty($_POST["addedorigurl"])){
				$oriurls = split(",", $_POST["addedorigurl"]);
			}
			$taskparams->oriurls = $oriurls;
		}
		//分析转发轨迹
		if($task == TASK_COMMENTPATH){
			$oriurls = array();
			if(!empty($_POST["addedorigurl"])){
				$oriurls = split(",", $_POST["addedorigurl"]);
			}
			$taskparams->oriurls = $oriurls;
		}

		//处理转发
		if($task == TASK_REPOST_TREND){
			$sourceid = get_sourceid_from_url("s.weibo.com");
			$taskparams->source = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if(!empty($_POST['each_count'])){
				$taskparams->each_count =$_POST['each_count'];
			}
			if(!empty($_POST["rmin_reposts_count"])){
				$taskparams->min_reposts_count = $_POST["rmin_reposts_count"];
			}
			$oristatus = array();
			if(!empty($_POST["addedorigid"])){
				$oristatus = split(",", $_POST["addedorigid"]);
			}
			$oriurls = array();
			if(!empty($_POST["addedorigmid"])){
				$oriurls = split(",", $_POST["addedorigmid"]);
			}
			$taskparams->oristatus = array_merge($oristatus, $oriurls);
			if(!empty($_POST['taskpagestyletype'])){
				$taskpagestyletype = (int)$_POST['taskpagestyletype'];
			}
			if(!empty($_POST['config'])){
				$taskparams->config = (int)$_POST['config'];
			}
			if(!empty($_POST['duration'])){
				$taskparams->duration = (int)$_POST['duration'];
			}
			$taskparams->forceupdate = empty($_POST['forceupdate']) ? 0 : 1;
			if(isset($_POST['isseed'])){
				$taskparams->isseed = (int)$_POST['isseed'];
			}
			if(isset($_POST['isrepostseed'])){
				$taskparams->isrepostseed = (int)$_POST['isrepostseed'];
			}
			if(!empty($_POST['accountid'])){
				$taskparams->accountid = split(",", $_POST['accountid']);
			}
			if(isset($_POST['logoutfirst'])){
				$taskparams->logoutfirst = (int)$_POST['logoutfirst'];
			}
			if(isset($_POST['iscalctrend'])){
				$taskparams->iscalctrend = (int)$_POST['iscalctrend'];
			}
		}

		//抓取评论
		if($task == TASK_COMMENTS){
			$sourceid = get_sourceid_from_url("s.weibo.com");
			$taskparams->source = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if(!empty($_POST['each_count'])){
				$taskparams->each_count = (int)$_POST['each_count'];
			}
			if(!empty($_POST["rmin_comments_count"])){
				$taskparams->min_comments_count = $_POST["rmin_comments_count"];
			}
			$oristatus = array();
			if(!empty($_POST["addedorigid"])){
				$oristatus = split(",", $_POST["addedorigid"]);
			}
			$oriurls = array();
			if(!empty($_POST["addedorigmid"])){
				$oriurls = split(",", $_POST["addedorigmid"]);
			}
			$taskparams->oristatus = array_merge($oristatus, $oriurls);
			if(!empty($_POST['taskpagestyletype'])){
				$taskpagestyletype = (int)$_POST['taskpagestyletype'];
			}
			if(!empty($_POST['config'])){
				$taskparams->config = (int)$_POST['config'];
			}
			if(!empty($_POST['duration'])){
				$taskparams->duration = (int)$_POST['duration'];
			}
			$taskparams->forceupdate = empty($_POST['forceupdate']) ? 0 : 1;
			if(isset($_POST['isseed'])){
				$taskparams->isseed = (int)$_POST['isseed'];
			}
			if(!empty($_POST['accountid'])){
				$taskparams->accountid = split(",", $_POST['accountid']);
			}
			if(isset($_POST['logoutfirst'])){
				$taskparams->logoutfirst = (int)$_POST['logoutfirst'];
			}
			if(isset($_POST['iscalctrend'])){
				$taskparams->iscalctrend = (int)$_POST['iscalctrend'];
			}
		}

		//抓取关键词
		if($task == TASK_KEYWORD){
			if(!empty($_POST['taskpagestyletype'])){
				$taskpagestyletype = (int)$_POST['taskpagestyletype'];
			}
			if(!empty($_POST['config'])){
				$taskparams->config = (int)$_POST['config'];
			}
			$sourceid = get_sourceid_from_url("s.weibo.com");
			$taskparams->source = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if(!empty($_POST['duration'])){
				$taskparams->duration = (int)$_POST['duration'];
			}
			if(isset($_POST['isseed'])){
				$taskparams->isseed = (int)$_POST['isseed'];
			}
			if(!empty($_POST['keywords'])){
				$keywords = explode("\r\n", trim($_POST['keywords']));
				$taskparams->keywords = $keywords;
			}
			if(!empty($_POST['username'])){
				$taskparams->username = $_POST['username'];
			}
			if(!empty($_POST['starttime'])){
				$taskparams->starttime = strtotime($_POST['starttime']);
			}
			if(!empty($_POST['endtime'])){
				$taskparams->endtime = strtotime($_POST['endtime']);
			}
			if(!empty($_POST['step'])){
				$taskparams->step = $_POST['step'];
			}
			if(isset($_POST['filterdup'])){
				$taskparams->filterdup = (int)$_POST['filterdup'];
			}
			if(!empty($_POST['accountid'])){
				$taskparams->accountid = split(",", $_POST['accountid']);
			}
			if(isset($_POST['logoutfirst'])){
				$taskparams->logoutfirst = (int)$_POST['logoutfirst'];
			}
			if(isset($_POST['isswitch'])){
				$taskparams->isswitch = (int)$_POST['isswitch'];
				if($taskparams->isswitch){
					$taskparams->switchpage = (int)$_POST['switchpage'];
					$taskparams->switchtime = (int)$_POST['switchtime'];
					$taskparams->globalaccount= (int)$_POST['globalaccount'];
				}
			}
			if(!empty($_POST['relativestart'])){
				$relativestart = $_POST['relativestart'];
			}
			if(!empty($_POST['relativeend'])){
				$relativeend = $_POST['relativeend'];
			}
			$logger->info(__FILE__.__LINE__." " .var_export($_POST['is_grab_repost'],true));
			//是否抓取关键字相关微博
			if(isset($_POST['is_grab_repost'])){
				$taskparams->is_grab_repost = $_POST['is_grab_repost'];
			}
		}
		//监控账号信息
		if($task == TASK_NICKNAME){
			if (!empty($_POST['each_count'])) {
				$taskparams->each_count = $_POST['each_count'];
			}
			if(!empty($_POST['taskpagestyletype'])){
				$taskpagestyletype = (int)$_POST['taskpagestyletype'];
			}
			if(!empty($_POST['config'])){
				$taskparams->config = (int)$_POST['config'];
			}
			$sourceid = get_sourceid_from_url("s.weibo.com");
			$taskparams->source = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if(isset($_POST['inputtype'])){
				$taskparams->inputtype = $_POST['inputtype'];
			}
			if(isset($_POST['addeduser'])){
				$adduser = split(",", $_POST['addeduser']);
				$taskparams->users = $adduser;
			}
			if(!empty($_POST['keywords'])){
				$keywords = explode("\r\n", trim($_POST['keywords']));
				$taskparams->keywords = $keywords;
			}
			if(isset($_POST['is_monitor_nickname'])){
				$taskparams->is_monitor_nickname = (int)$_POST['is_monitor_nickname'];
			}
			//是否抓取关键字相关微博
			if(isset($_POST['is_grab_repost'])){
				$taskparams->is_grab_repost = $_POST['is_grab_repost'];
			}
			$logger->debug(__FILE__.__LINE__." 参数详情是 ".var_export($taskparams,true));
		}
		//更新微博
		if($task == TASK_STATUSES_COUNT){
			if(!empty($_POST['taskpagestyletype'])){
				$taskpagestyletype = (int)$_POST['taskpagestyletype'];
			}
			if(!empty($_POST['config'])){
				$taskparams->config = (int)$_POST['config'];
			}
			$sourceid = get_sourceid_from_url("s.weibo.com");
			$taskparams->source = $sourceid; //任务类型和来源关联, 抓取微博为抓取新浪微博,source为1;
			if(!empty($_POST['duration'])){
				$taskparams->duration = (int)$_POST['duration'];
			}
			if(isset($_POST['isseed'])){
				$taskparams->isseed = (int)$_POST['isseed'];
			}
			if(!empty($_POST['keywords'])){
				$keywords = explode("\r\n", trim($_POST['keywords']));
				$taskparams->keywords = $keywords;
			}
			if(isset($_POST['filterdup'])){
				$taskparams->filterdup = (int)$_POST['filterdup'];
			}
			if(!empty($_POST['accountid'])){
				$taskparams->accountid = split(",", $_POST['accountid']);
			}
			if(isset($_POST['logoutfirst'])){
				$taskparams->logoutfirst = (int)$_POST['logoutfirst'];
			}
			if(isset($_POST['isswitch'])){
				$taskparams->isswitch = (int)$_POST['isswitch'];
				if($taskparams->isswitch){
					$taskparams->switchpage = (int)$_POST['switchpage'];
					$taskparams->switchtime = (int)$_POST['switchtime'];
					$taskparams->globalaccount= (int)$_POST['globalaccount'];
				}
			}
			if(!empty($_POST['relativestart'])){
				$relativestart = $_POST['relativestart'];
			}
			if(!empty($_POST['relativeend'])){
				$relativeend = $_POST['relativeend'];
			}
		}
		//抓取论坛
		if($task == TASK_WEBPAGE){
			if(!empty($_POST['taskpagestyletype'])){
				$taskpagestyletype = (int)$_POST['taskpagestyletype'];
			}
			//派生用户任务
			if(!empty($_POST['deriveusertask'])){
				$taskparams->deriveusertask = (int)$_POST['deriveusertask'];
			}
			//爬取站点用户模板
			if(!empty($_POST['usertemplate']) && (!empty($_POST['deriveusertask']) || $taskpagestyletype==TASK_PAGESTYLE_USERDETAIL)){
				$taskparams->usertemplate = (int)$_POST['usertemplate'];
			}
			if(!empty($_POST['userweburl'])){
				$uu = json_decode($_POST['userweburl'], true);
				$taskparams->userurls = $uu[0];
				//$userurls = json_decode($_POST['userweburl'], true);
			}
			if(!empty($_POST['importusercount'])){
				$taskparams->importusercount = (int)$_POST['importusercount'];
			}
			//派生正文任务
			if(!empty($_POST['derivetexttask'])){
				$taskparams->derivetexttask = (int)$_POST['derivetexttask'];
			}
			//爬取站点模板
			if(!empty($_POST['SStemplate']) && (!empty($_POST['derivetexttask']) || $taskpagestyletype==TASK_PAGESTYLE_ARTICLEDETAIL)){
				$taskparams->SStemplate = (int)$_POST['SStemplate'];
			}
			if(!empty($_POST['textweburl'])){
				$tu = json_decode($_POST['textweburl'], true);
				$taskparams->texturls = $tu[0];
				//$texturls = json_decode($_POST['textweburl'], true);
			}
			if(!empty($_POST['importarticlecount'])){
				$taskparams->importarticlecount = (int)$_POST['importarticlecount'];
			}
			//搜索列表模板
			if(!empty($_POST['SEtemplate'])){
				$taskparams->SEtemplate = (int)$_POST['SEtemplate'];
			}
			if(!empty($_POST['listweburl'])){
				$lu = json_decode($_POST['listweburl'], true);
				$taskparams->listurls = $lu[0];
				//$listurls = json_decode($_POST['listweburl'], true);
			}
			if(!empty($_POST['source'])){
				$taskparams->source = $_POST['source'];
			}
			if(!empty($_POST['duration'])){
				$taskparams->duration = (int)$_POST['duration'];
			}
			if(!empty($_POST['crawlpage'])){
				$taskparams->crawlpage = (int)$_POST['crawlpage'];
			}
			/*
			if(!empty($_POST['keywords'])){
				$keywords = explode("\r\n", trim($_POST['keywords']));
				$taskparams['keywords'] = $keywords;
			}

			if(isset($_POST['filterdup'])){
				$taskparams['filterdup'] = (int)$_POST['filterdup'];
			}
			 */
			if(!empty($_POST['accountid'])){
				$taskparams->accountid = split(",", $_POST['accountid']);
			}
			if(isset($_POST['logoutfirst'])){
				$taskparams->logoutfirst = (int)$_POST['logoutfirst'];
			}
			if(isset($_POST['isswitch'])){
				$taskparams->isswitch = (int)$_POST['isswitch'];
				if($taskparams->isswitch){
					$taskparams->switchpage = (int)$_POST['switchpage'];
					$taskparams->switchtime = (int)$_POST['switchtime'];
					$taskparams->globalaccount = (int)$_POST['globalaccount'];
				}
			}
			if(isset($_POST['iscalctrend'])){
				$taskparams->iscalctrend = (int)$_POST['iscalctrend'];
			}
		}
		//抓取关注
		if($task == TASK_FRIEND){
			if(!empty($_POST['config'])){
				$taskparams->config = (int)$_POST['config'];
			}
			if(!empty($_POST['source'])){
				$taskparams->source = $_POST['source'];
			}
			if(!empty($_POST['duration'])){
				$taskparams->duration = (int)$_POST['duration'];
			}
			if(isset($_POST['isseed'])){
				$taskparams->isseed = (int)$_POST['isseed'];
			}
			if(isset($_POST['inputuser'])){
				$taskparams->inputuser = $_POST['inputuser'];
			}
			if(!empty($_POST['unames'])){
				$unames = explode("\r\n", trim($_POST['unames']));
				$taskparams->unames = $unames;
			}
			if(!empty($_POST['uids'])){
				$uids = split(",", $_POST['uids']);
				$taskparams->uids = $uids;
			}
		}
		//迁移数据
		if($task == TASK_MIGRATEDATA){
			$taskparams->srchost = empty($_POST['srchost']) ? 0 : (int)$_POST['srchost'];
			if(isset($_POST['dsthost']) && $_POST['dsthost'] != ''){
				$dsthost = split(",", $_POST['dsthost']);
				foreach($dsthost as $hostid){
					$taskparams->dsthost[] = (int)$hostid;
				}
			}
			$taskparams->keepsrc = empty($_POST['keepsrc']) ? 0 : 1;
			$taskparams->deluser = empty($_POST['deluser']) ? 0 : 1;
			$taskparams->delseedweibo = empty($_POST['delseedweibo']) ? 0 : 1;
			$taskparams->delseeduser = empty($_POST['delseeduser']) ? 0 : 1;
			$taskparams->offset = 0;
			if(!empty($_POST['maxcount'])){
				$taskparams->maxcount = (int)$_POST['maxcount'];
			}
			$taskparams->eachcount = empty($_POST['eachcount']) ? 100 : (int)$_POST['eachcount'];
			if(!empty($_POST['source'])){
				$taskparams->source = $_POST['source'];
			}
			if(isset($_POST['source_host']) && $_POST['source_host'] != ''){
				$source_host = split(",", $_POST['source_host']);
				$taskparams->source_host[] = $source_host[0];
				/*
				foreach($source_host as $hostid){
					$taskparams->source_host[] = (int)$hostid;
				}
				 */
			}
			if(isset($_POST['users_source_host']) && $_POST['users_source_host'] != ''){
				$users_source_host = split(",", $_POST['users_source_host']);
				$taskparams->users_source_host[] = $users_source_host[0];
				/*
				foreach($users_source_host as $hostid){
					$taskparams->users_source_host[] = (int)$hostid;
				}
				 */
			}

			$taskparams->cond_deleted = 1;
			if(!empty($_POST['cond_lt_created'])){
				$taskparams->cond_lt_created = strtotime($_POST['cond_lt_created']);
			}
			if(!empty($_POST['cond_ge_created'])){
				$taskparams->cond_ge_created = strtotime($_POST['cond_ge_created']);
			}
			if(!empty($_POST['cond_ex_text'])){
				$extext = explode("\r\n", trim($_POST['cond_ex_text']));
				$taskparams->cond_ex_text = $extext;
			}
			if(!empty($_POST['cond_in_text'])){
				$intext = explode("\r\n", trim($_POST['cond_in_text']));
				$taskparams->cond_in_text = $intext;
			}
			if(!empty($_POST['cond_ex_name'])){
				$exname = explode("\r\n", trim($_POST['cond_ex_name']));
				$taskparams->cond_ex_name = $exname;
			}
			if(!empty($_POST['cond_in_name'])){
				$inname = explode("\r\n", trim($_POST['cond_in_name']));
				$taskparams->cond_in_name = $inname;
			}
			$taskparams->orderby = 'created';
			$taskparams->order = "desc";
			if(!empty($taskparams->dsthost)){
				if(array_search($taskparams->srchost, $taskparams->dsthost) !== false){
					$result = array('result'=>false,'msg'=>'源主机和目标主机冲突');
					$logger->error(TASKMANAGER." - addNewTask() srchost and dsthost conflict");
				}
			}
		}
		//通用任务
		if ($task == TASK_COMMON) {
            $taskparams = json_decode($_POST['taskparams'], true);
			$logger->debug(__FILE__.__LINE__." taskparams ".var_export($taskparams, true));
        }
		if(!empty($_POST['cronstart'])){
			$starttime = strtotime($_POST['cronstart']);
		}
		if(!empty($_POST['cronend'])){
			$endtime = strtotime($_POST['cronend']);
		}
		if(!empty($_POST['crontime'])){
			$crontime = json_decode($_POST['crontime']);
			switch($task){
			case TASK_WEIBO:
			case TASK_KEYWORD:
				if(empty($crontime->minute)){
					$crontime->precision = 3600;
				}
				else{
					$crontime->precision = 60;
				}
				break;
			default:
				$crontime->precision = 60;
				break;
			}
			$crontime->cronmask = getCronMask($crontime);
		}
		$params = (object)array();
		$params->taskparams = $taskparams;
		if(isset($relativestart)){
			$params->relativestart = $relativestart;
		}
		if(isset($relativeend)){
			$params->relativeend = $relativeend;
		}
		$params->nodup = empty($_POST['nodup']) ? 0 : 1;
		$schedule = (object)array();
		$schedule->tasktype = $tasktype;
		if(isset($taskpagestyletype)){
			$schedule->taskpagestyletype = $taskpagestyletype;
		}
		$schedule->task = $task;
		$schedule->tasklevel = $_POST['tasklevel'];
		$schedule->local = $local;
		$schedule->remote = $remote;
		$schedule->conflictdelay = $conflictdelay;
//		$logger->debug(" - createSchedule() specifiedType1:[".$taskclassify."]  spcfdmac1:[".$spcfdmac."].");
		$schedule->taskclassify = $taskclassify;
		$schedule->spcfdmac = $spcfdmac;
		$schedule->params = $params;
		$schedule->remarks = $_POST['remarks'];
		$tenantid = "NULL";
		$userid = "NULL";
		$userinfo = isset($_SESSION['user']) ? $_SESSION['user'] : NULL;
		if($userinfo != NULL){
			$tenantid = $userinfo->tenantid;
			$userid = $userinfo->getuserid();
		}
		$schedule->tenantid = $tenantid;
		$schedule->userid = $userid;
		if(!empty($starttime)){
			$schedule->starttime = $starttime;
		}
		if(!empty($endtime)){
			$schedule->endtime = $endtime;
		}
		$schedule->crontime = $crontime;
		if(isset($_POST['status'])){
			$schedule->status = (int)$_POST['status'];
		}
		if(isset($_POST['id'])){
			$schedule->id = (int)$_POST['id'];
		}
		
		$result['schedule'] = $schedule;
	}
	return $result;
}


function addSchedule(){
	global $logger,$dsql;
	$result = array('result'=>true,'msg'=>'');
	$ret = createSchedule();
	$logger->info(__FILE__.__LINE__." ret ".var_export($ret, true));
	if(!$ret['result']){
		$result = $ret;
		//$result['result'] = false;
		//$result['msg'] = $ret['msg'];
	}
	else if(empty($ret['schedule'])){
		$result['result'] = false;
		$result['msg'] = '创建定时任务失败';
	}
	else{
		try{
			$result['result'] = addTaskSchedule($ret['schedule']);
		}
		catch(Exception $ex){
			$result['result'] = false;
			$result['msg'] = '添加定时任务失败';
			$logger->error(SELF." - ".__FUNCTION__." ".$ex->getMessage());
		}
	}
	echo json_encode($result);
}

function editSchedule(){
	global $logger,$dsql;
	$result = array('result'=>true,'msg'=>'');
	$sql = "select * from ".DATABASE_TASKSCHEDULE." where id = ".$_POST["id"];
	$qr = $dsql->ExecQuery($sql);
	if (!$qr){
		$result['result'] = false;
		$result['msg'] = '检查定时任务状态失败';
		$logger->error(SELF." - ".__FUNCTION__." sqlerror:".$dsql->GetError());
	}
	else{
		$oldsched = $dsql->GetObject($qr);
		if(!$oldsched){
			$result['result'] = false;
			$result['msg'] = '定时任务未找到';
		}
		else if($oldsched->status == 2){
			$result['result'] = false;
			$result['msg'] = '定时任务运行中，请稍后重试';
		}
		else{
			$ret = createSchedule();
			if(!$ret['result']){
				$result = $ret;
				//$result['result'] = false;
				//$result['msg'] = $ret['msg'];
			}
			else if(empty($ret['schedule'])){
				$result['result'] = false;
				$result['msg'] = '创建定时任务失败';
			}
			else{
				$schedule = $ret['schedule'];
				if(!isScheduleIdentical($schedule, $oldsched)){
					try{
						if(updateTaskScheduleFull($schedule, 'status != 2') == 0){
							$result['result'] = false;
							$result['msg'] = '定时任务运行中，请稍后重试';
						}
					}
					catch(Exception $ex){
						$result['result'] = false;
						$result['msg'] = '更新定时任务失败';
						$logger->error(SELF." - ".__FUNCTION__." ".$ex->getMessage());
					}
				}
			}
		}
	}
	echo json_encode($result);
}

/*
 * 启用操作：禁用/0 => 启用/1
 * 禁用操作：启用/1 => 禁用/0
 * 停止操作：运行/2 => 禁用/0
 */
function changeScheduleStatus(){
	global $logger,$dsql;
	$result = array('result'=>true,'msg'=>'');
	$status = (int)$_POST["status"];
	$sql = "select id,status from ".DATABASE_TASKSCHEDULE." where id = ".$_POST["id"];
	$qr = $dsql->ExecQuery($sql);
	if (!$qr){
		$result['result'] = false;
		$result['msg'] = '检查定时任务状态失败';
		$logger->error(SELF." - ".__FUNCTION__." sqlerror:".$dsql->GetError());
	}
	else{
		$rs = $dsql->GetObject($qr);
		if(!$rs){
			$result['result'] = false;
			$result['msg'] = '定时任务未找到';
		}
		else{
			$oldstatus = $rs->status;
			$statusflag = true;
			switch($status){
			case 0:
				if($oldstatus != 1 && $oldstatus != 2){
					$statusflag = false;
				}
				break;
			case 1:
				if($oldstatus != 0){
					$statusflag = false;
				}
				break;
			case 2:
			default:
				$statusflag = false;
				break;
			}
			if($statusflag == false){
				$result['result'] = false;
				$result['msg'] = '任务状态错误，请刷新页面';
			}
			else{
				try{
					$rs->status = $status;
					if(updateTaskScheduleStatus($rs, "status = {$oldstatus}") == 0){
						$result['result'] = false;
						$result['msg'] = '任务状态错误，请刷新页面';
					}
				}
				catch(Exception $ex){
					$result['result'] = false;
					$result['msg'] = '更新定时任务状态失败';
					$logger->error(SELF." - ".__FUNCTION__." ".$ex->getMessage());
				}
			}
		}
	}
	echo json_encode($result);
}

function deleteSchedule(){
	global $logger,$dsql;
	$result = array('result'=>true,'msg'=>'');
	$sql = "select * from ".DATABASE_TASKSCHEDULE." where id = ".$_POST["id"];
	$qr = $dsql->ExecQuery($sql);
	if (!$qr){
		$result['result'] = false;
		$result['msg'] = '检查定时任务状态失败';
		$logger->error(SELF." - ".__FUNCTION__." sqlerror:".$dsql->GetError());
	}
	else{
		$rs = $dsql->GetObject($qr);
		if(!$rs){
			$result['result'] = false;
			$result['msg'] = '定时任务未找到';
		}
		else if($rs->status == 2){
			$result['result'] = false;
			$result['msg'] = '定时任务运行中，请稍后重试';
		}
		else{
			try{
				if(deleteTaskSchedule($rs, 'status != 2') == 0){
					$result['result'] = false;
					$result['msg'] = '定时任务运行中，请稍后重试';
				}
			}
			catch(Exception $ex){
				$result['result'] = false;
				$result['msg'] = '删除定时任务失败';
				$logger->error(SELF." - ".__FUNCTION__." ".$ex->getMessage());
			}
		}
	}
	echo json_encode($result);
}

function deleteScheduleHistory(){
	global $logger,$dsql;
	$result = array('result'=>true,'msg'=>'');
	$sql = "delete from ".DATABASE_TASKSCHEDULEHISTORY." where id = ".$_POST["id"]." limit 1";
	$qr = $dsql->ExecQuery($sql);
	if (!$qr){
		$result['result'] = false;
		$result['msg'] = '删除历史定时任务失败';
		$logger->error(SELF." - ".__FUNCTION__." sqlerror:".$dsql->GetError());
	}
	echo json_encode($result);
}

function getScheduleTable($table){
	global $logger,$dsql;
	$iDisplayStart = $_GET['iDisplayStart'];//从多少条开始显示
	$iDisplayLength = $_GET['iDisplayLength'];//每页条数
	$iDisplayStart = empty($iDisplayStart) ? 0 : $iDisplayStart;
	$iDisplayLength = empty($iDisplayLength) ? 10 : $iDisplayLength;
	$p_task = empty($_GET['task']) ? '' : ' and task='.$_GET['task'];
	$p_taskstatus = '';
	if($table == DATABASE_TASKSCHEDULE){
		if(isset($_GET['taskstatus']) && $_GET['taskstatus'] != ''){
			$p_taskstatus = ' and status='.$_GET['taskstatus'];
		}
	}
	$p_tasklevel =  empty($_GET['tasklevel']) ? '' : ' and tasklevel='.$_GET['tasklevel'];
	$p_taskpagestyletype =  empty($_GET['taskpagestyletype']) ? '' : ' and taskpagestyletype='.$_GET['taskpagestyletype'];
	$p_local = '';
	if(isset($_GET['local']) && $_GET['local'] != ''){
		$p_local = ' and local='.$_GET['local'];
	}
	$p_remote = '';
	if(isset($_GET['remote']) && $_GET['remote'] != ''){
		$p_remote = ' and remote='.$_GET['remote'];
	}
	$p_idstart = empty($_GET['id_start']) ? -1 : $_GET['id_start'];
	$p_idend = empty($_GET['id_end']) ? -1 : $_GET['id_end'];
	$p_id = '';
	if($p_idstart != -1 && $p_idend != -1){
		$p_id = ' and id >= '.$p_idstart.' and id <= '.$p_idend.'';
	}
	else if($p_idstart != -1){
		$p_id = ' and id >= '.$p_idstart.'';
	}
	else if($p_idend != -1){
		$p_id = ' and id <= '.$p_idend.'';
	}

	$wh = " where 1=1 {$p_task} {$p_taskstatus} {$p_tasklevel} {$p_taskpagestyletype} {$p_local} {$p_remote} {$p_id}";
//	$logger->debug(__LINE__.__FILE__. " whdebug:".$wh);
	$p_orderby = empty($_GET['orderby']) ? 'id desc' : $_GET['orderby'];
	$order = " order by {$p_orderby}";
	$result = new DatatableResult();
	$result->aaData = array();
	$sql = "select count(0) as cnt from {$table} {$wh}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		$logger->error(SELF." ".__FUNCTION__." sqlerror:".$sql." - ".$dsql->GetError());
	}
	else{
		$rcnt = $dsql->GetArray($qr);
		$result->sEcho = empty($_GET['sEcho']) ? 0 : $_GET['sEcho'];
		$result->iTotalRecords = $rcnt['cnt'];
		$result->iTotalDisplayRecords = $rcnt['cnt'];
		if($rcnt['cnt'] > 0){
			$sql = "select * from {$table} {$wh} {$order} limit {$iDisplayStart},{$iDisplayLength}";
//			$logger->debug(__LINE__.__FILE__. " sqldebug:".$sql);
			$qr = $dsql->ExecQuery($sql);
			if(!$qr){
				$logger->error(SELF." ".__FUNCTION__." sqlerror:".$sql." - ".$dsql->GetError());
			}
			else{
				while ($r = $dsql->GetArray($qr)){
					$result->aaData[] = $r;
				}
			}
		}
	}
	echo json_encode($result);
}

function getCurrentSchedule(){
	getScheduleTable(DATABASE_TASKSCHEDULE);
}

function getScheduleHistory(){
	getScheduleTable(DATABASE_TASKSCHEDULEHISTORY);
}

