<?php
define( "SELF", basename(__FILE__) );
include_once( 'includes.php' );
include_once( 'taskcontroller.php' );
include_once("authorization.class.php");
include_once('weibo_config.php');
include_once( 'weibo_class.php' );
include_once( 'saetv2.ex.class.php' );
ini_set('include_path',get_include_path().'/lib');
include_once ('OpenSDK/Tencent/Weibo.php');

session_start();
set_time_limit(0);
initLogger(LOGNAME_WEBAPI);
$chkr = Authorization::checkUserSession();
$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);

$result = array('result'=>true,'msg'=>'1');
if(!empty($_GET['type']) && $_GET['type'] == "remoteuser"){ //远程导入用户, tasktype为importuser，包含新浪关注id，关注全部
	if($chkr != CHECKSESSION_SUCCESS){
		setErrorMsg($chkr, "未登录或登陆超时!");
	}
	if(empty($HTTP_RAW_POST_DATA)){
		setErrorMsg(1, "未提交数据");
	}
	$logger->debug(SELF." received import remoteuser data : ".$HTTP_RAW_POST_DATA);
	$postdata = json_decode($HTTP_RAW_POST_DATA, true);
	$idcount = 0;
	$usercount = 0;
	$complete = false;
	$complete_stat = array();
	if(empty($postdata)){
		setErrorMsg(1, "数据为空");
	}
	$taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
	if(!empty($taskinfo)){
		$rawdata = isset($postdata['data']) ? $postdata['data'] : null;
		$rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $rawdata);
		if($rt['result'] == false){
			$logger->error(SELF." ".$rt['msg']);
			setErrorMsg($rt['error'], $rt['msg']);
		}
	}
	if(!empty($postdata['complete'])){
		$complete = true;
		$result = completeUser();
	}
	else if(!empty($postdata['data'])){
		$data = $postdata['data'][0];
		if(empty($data['name']) || !isset($postdata['sourceid']) || !isset($postdata['isseed'])){
			setErrorMsg(1, "数据不完整");
		}
		if(isset($data['ids'])){
			$idcount = count($data['ids']);
		}
		if(isset($data['users'])){
			$usercount = count($data['users']);
		}
		if(isset($data['ids']) && isset($data['users']) && $idcount != $usercount){
			setErrorMsg(1, "数据不一致");
		}
		if(empty($_SESSION['importuser'])){
			$_SESSION['importuser'] = $data;
			if(isset($postdata['sourceid']))
				$_SESSION['importuser']['sourceid'] = $postdata['sourceid'];
			else if(isset($postdata['page_url']))
				$_SESSION['importuser']['sourceid'] = get_sourceid_from_url($postdata['page_url']);
			$_SESSION['importuser']['isseed'] = $postdata['isseed'];
		}
		else{
			if($_SESSION['importuser']['name'] != $data['name']){
				$complete = true;
				$result = completeUser();
				if($result['result']){
					$_SESSION['importuser'] = $data;
					if(isset($postdata['sourceid']))
						$_SESSION['importuser']['sourceid'] = $postdata['sourceid'];
					else if(isset($postdata['page_url']))
						$_SESSION['importuser']['sourceid'] = get_sourceid_from_url($postdata['page_url']);
					$_SESSION['importuser']['isseed'] = $postdata['isseed'];
				}
			}
			else{
				if(isset($_SESSION['importuser']['ids'])){
					$_SESSION['importuser']['ids'] = array_merge($_SESSION['importuser']['ids'], $data['ids']);
				}
				if(isset($_SESSION['importuser']['users'])){
					$_SESSION['importuser']['users'] = array_merge($_SESSION['importuser']['users'], $data['users']);
				}
			}
		}
	}
	else{
		setErrorMsg(1, "数据为空");
	}
	if($result['result'] == false){
		$result['errorcode'] = -1;
		$result['error'] = $result['msg'];
		$logger->error($result['msg']);
		unset($result['result']);
		unset($result['msg']);
	}
	else{
		$result['info'] = array();
		$result['info']['keycount'] = $complete ? 1 : 0;
		$result['info']['idcount'] = $idcount;
		$result['info']['usercount'] = $usercount;
		$result['info']['completeidcount'] = empty($complete_stat)?0:$complete_stat['idcount'];
		$result['info']['completeusercount'] = empty($complete_stat)?0:$complete_stat['usercount'];
	}
	echo json_encode($result);
	exit;
}
if(!empty($_GET['type']) && $_GET['type'] == "remoteuserdetail"){ //远程导入用户
	$logger->debug(__FILE__.'enter remoteuserdetail branch.');
	if($chkr != CHECKSESSION_SUCCESS){
		setErrorMsg($chkr, "未登录或登陆超时!");
	}
	if(empty($HTTP_RAW_POST_DATA)){
		setErrorMsg(1, "未提交数据");
	}
	$logger->debug(SELF." received import remoteuserdetail data : ".$HTTP_RAW_POST_DATA);
	$postdata = json_decode($HTTP_RAW_POST_DATA, true);
	if(empty($postdata)){
		setErrorMsg(1, "数据为空");
	}
	$taskinfo = isset($_GET['task']) ? json_decode($_GET['task']) : null;
	if(!empty($taskinfo)){
		$rawdata = isset($postdata['data']) ? $postdata['data'] : null;
		$rt = updateAgentTask($taskinfo->id, $taskinfo->host, $taskinfo->stat, $rawdata);
		if($rt['result'] == false){
			$logger->error(SELF." ".$rt['msg']);
			setErrorMsg($rt['error'], $rt['msg']);
		}
	}

	//$logger->debug(__FILE__.__LINE__." taskinfo ".var_export($taskinfo, true));
	/*if(isset($postdata['sourceid']))
		$source = $postdata['sourceid'];
	else if(isset($postdata['page_url']))
		$source = get_sourceid_from_url($postdata['page_url']);
	else
		$source = NULL;*/

	$mainurl = isset($postdata['page_url']) ? $postdata['page_url']:NULL;
	$userdata = array();
	foreach($rawdata as $index=>$value)
	{
		$userdata[] = $rawdata[$index]['user'];
	}

	$task = $taskinfo;

	/*$task->machine = SERVER_MACHINE;
	$task->taskparams->scene->state = SCENE_NORMAL;
	$task->tasklevel = 2;
	$task->queuetime = time();
	$task->tasksource = $source;
	$task->taskparams->source = $source;*/
	changeUserTokenfieldsType($userdata);
	$ret = insert_user($userdata, NULL, NULL,0,true, $mainurl,true);

	//$logger->debug(__FILE__.__LINE__." add user result: ".var_export($ret, true));
	if(isset($ret['result']) && $ret['result']===false){
		$result["result"] = false;
		$result['msg'] = "新增用户失败:".$ret['msg'];
		$result['error'] = $result['msg'];
	}
	else{
		$result["result"] = true;
		$result['msg'] = '成功新增 '.$ret['newcount'].' 个用户，更新 '.$ret['updatecount']." 个用户";
	}
	echo json_encode($result);
	exit;
}
else if(!empty($_GET['type']) && $_GET['type'] == "addimporttask"){//增加任务时
	$logger->debug(SELF." addimporttask");
	if($chkr != CHECKSESSION_SUCCESS){
		$result["result"] = false;
		$result["msg"]="未登录或登陆超时!";
		echo json_encode($result);
		exit;
	}
	if(isset($_POST['source']))
		$source = $_POST['source'];
	else if(isset($_POST['page_url']))
		$source = get_sourceid_from_url($_POST['page_url']);
	$inputtype = $_POST['inputtype'];//输入类型  ID或screen_name
	$seeduser = $_POST['seeduser'];//0 或 1
	$getfriends = $_POST['getfriends'];//0 或 1
	$names = $_POST['names'];
	$total = $_POST['allcount'];
	if(empty($source) || empty($names) || empty($total) || !isset($seeduser) || !isset($getfriends)){
		$logger->error(SELF." 参数错误");
		$result['result'] = false;
		$result['msg'] = "参数错误";
	}
	else{
		$remarks = "来自系统管理的请求，共{$total}条请求，已抓取".($total - count($names))."条。剩余".count($names)."条。";
		$evcount;
		if(defined('ADMIN_MAXIMPORT_COUNT')){
			$evcount = ADMIN_MAXIMPORT_COUNT;
		}
		else{
			$evcount = 50;
		}
		$alldatas = array_chunk($names,$evcount);//拆分成不同的任务
		$allcount = count($alldatas);
		$succcount = 0;
		for($i=0; $i<$allcount; $i++){
			$imtask = new Task(null);
			$imtask->tasktype = TASKTYPE_SPIDER;//抓取
			$imtask->task = TASK_IMPORTUSERID;//批量植入
			$imtask->remarks = empty($remarks) ? "" : $remarks;
			$curridx = $i*$evcount + 1;
			$imtask->remarks .= "本任务处理第".$curridx."条 ~ 第".($curridx + count($alldatas[$i]) - 1)."条";
			$imtask->tasksource = $source;
			$imtask->tasklevel = 1;
			$imtask->local = 1;
			$imtask->remote = 0;
			$imtask->activatetime = 0;
			$imtask->conflictdelay = 60;
			$imtask->taskparams->isseed = $seeduser;
			$imtask->taskparams->getfriends = $getfriends;
			$imtask->taskparams->data = $alldatas[$i];//所有数据都放到data中
			$imtask->taskparams->datatype = $inputtype;//数据类型:id或screen_name
			$imtask->taskparams->source = $source;
			$imtask->taskparams->iscommit = true;
			if(addTask($imtask)){
				$succcount ++;
			}
			else{
				break;
			}
		}
		$result['result'] = $succcount == $allcount;
		if($result['result']){
			$result['msg'] = '添加植入任务成功，每个任务处理'.$evcount.'条，共'.$allcount."个";
		}
		else if($succcount == 0){
			$result['msg'] = '添加植入任务失败';
		}
		else{
			$result['msg'] = '共有'.$allcount.'个植入任务，每个任务处理'.$evcount.'条，添加成功'.$succcount.'个，第'.($succcount+1).'个添加失败';
		}
		if($result['result'] == false){
			$logger->error(SELF.$result['msg']);
		}
	}
	echo json_encode($result);
	exit;
}
else {
	$logger->debug(SELF." last else");
	//后台植入用户功能
	if($chkr != CHECKSESSION_SUCCESS){
		$result["result"] = false;
		$result["msg"]="未登录或登陆超时!";
		echo json_encode($result);
		exit;
	}
	//为全局字典方案变量赋值
	global $dictionaryPlan;
	$dictionary_plan= $_POST['dictionary_plan'];
	$dictionaryPlan=$dictionary_plan;
	//$logger->info("获取字典：".$dictionaryPlan);
	if(isset($_POST['source']))
		$source = $_POST['source'];
	else if(isset($_POST['page_url']))
		$source = get_sourceid_from_url($_POST['page_url']);
	$inputtype = $_POST['inputtype'];//输入类型  ID或screen_name
	$seeduser = $_POST['seeduser'];//0 或 1
	$getfriends = isset($_POST['getfriends']) ? $_POST['getfriends'] : 0;//0 或 1
	$screen_name = $_POST['screen_name'];//输入的值（ID或screen_name）
	if(empty($source) || empty($screen_name) || !isset($seeduser) || !isset($getfriends)){
		$result["result"] = false;
		$result["msg"] = "参数错误";
		echo json_encode($result);
	}
	else{
		$task = new Task(null);
		$task->machine = SERVER_MACHINE;
		$task->taskparams->scene->state = SCENE_NORMAL;
		$task->tasklevel = 2;
		$task->queuetime = time();
		$task->tasksource = $source;
		$task->taskparams->source = $source;
		$id = NULL;
		$name = NULL;
		if($inputtype == "id"){
			$id = $screen_name;
		}
		else{
			$name = $screen_name;
		}
		$friendsinfo = NULL;
		$result = update_userinfo($source, $id, $name, $seeduser, $getfriends, $friendsinfo, UPDATE_ACTION_FORCE);
		//$logger->error(json_encode($result)."---test");
		echo json_encode($result);
	}
	exit;
}
function completeUser()
{
	global $task, $logger, $complete_stat;
	$logger->debug("[enter completeUser");
	$result = array('result'=>true,'msg'=>'');
	if(isset($task)){
		$result['result'] = false;
		$result['msg'] = '内部系统错误';
		$logger->error(__FUNCTION__."全局任务已存在");
		return $result;
	}
	if(empty($_SESSION['importuser'])){
		return $result;
	}
	$name = $_SESSION['importuser']['name'];
	$source = $_SESSION['importuser']['sourceid'];
	$seeduser = $_SESSION['importuser']['isseed'];
	$ids = empty($_SESSION['importuser']['ids'])?array():$_SESSION['importuser']['ids'];
	$users = empty($_SESSION['importuser']['users'])?array():$_SESSION['importuser']['users'];
	$idcount = count($ids);
	$usercount = count($users);
	$getfriends = false;
	$friendsinfo = array();
	if($idcount > 0){
		$getfriends = true;
		for($i = 0; $i < $idcount; $i++){
			if($usercount == $idcount){
				$users[$i]['id'] = $ids[$i];
				$friendsinfo[$ids[$i]] = $users[$i];
			}
			else{
				$friendsinfo[$ids[$i]] = '';
			}
		}
	}
	$task = new Task(null);
	$task->machine = SERVER_MACHINE;
	$task->taskparams->scene->state = SCENE_NORMAL;
	$task->tasklevel = 2;
	$task->queuetime = time();
	$task->tasksource = $source;
	$task->taskparams->source = $source;
	$result = update_userinfo($source, NULL, $name, $seeduser, $getfriends, $friendsinfo, UPDATE_ACTION_FORCE);
	if(!$result['result']){
		if($result['nores']){
			$task->tasktype = TASKTYPE_SPIDER;
			$task->task = TASK_IMPORTUSERID;
			$task->remarks = "来自spider的请求，用户名 ".$name.",共".$idcount."条关注。";
			$task->tasklevel = 1;
			$task->local = 1;
			$task->remote = 0;
			$task->activatetime = 0;
			$task->conflictdelay = 60;
			$task->taskparams->isseed = $seeduser;
			$task->taskparams->getfriends = $getfriends;
			if(!empty($friendsinfo)){
				$task->taskparams->friendsinfo = $friendsinfo;
			}
			$task->taskparams->data = array($name);
			$task->taskparams->datatype = 'screen_name';
			$task->taskparams->iscommit = true;
			if(addTask($task)){
				$result['result'] = true;
				$logger->info(SELF.'添加植入任务成功');
			}
			else{
				$result['msg'] .= ' 添加植入任务失败';
				$logger->error(SELF.$result['msg']);
			}
		}
		else{
			$logger->error(SELF.$result['msg']);
		}
	}
	$complete_stat['idcount'] = $idcount;
	$complete_stat['usercount'] = $usercount;
	unset($_SESSION['importuser']);
	$logger->debug("exit completeUser]");
	return $result;
}
