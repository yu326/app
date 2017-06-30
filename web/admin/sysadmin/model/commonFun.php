<?php
/*
 * 公共函数
 * @author Todd
 */
include_once 'includes.php';
define('LOCALTYPE_PLATFORM', 1);//数据平台
define('LOCALTYPE_TENANT', 2);//租户

define('RESOURCE_TYPE_SYSTEM',1);      //资源类型，系统资源（后台管理中的增删改等权限）
define('RESOURCE_TYPE_TENANT',3);      //租户资源

define('JSON_OUTPUTTYPE_QUERY',1);//JSON数据中output字段的outputtype属性，1表示只查询
define('JSON_OUTPUTTYPE_FACET',2);//JSON数据中output字段的outputtype属性，2表示只FACET

/*
 * 获取当前平台类型
 */
function getLocalType(){
    $hname = $_SERVER['HTTP_HOST'];
    if($hname == SERVERNAME_PLATFORM){
        return LOCALTYPE_PLATFORM;
    }
    else if($hname == SERVERNAME_TENANT){
        return LOCALTYPE_TENANT;
    }
    else{
        return false;
    }
}

/*
 * 从urlrewrite后的参数中获取租户二级域名代码
 */
function getTenantCode(){
    $tcode = $_GET['re_tenantcode'];
    return $tcode;
}

function arrayToObject($array) {
    if(!is_array($array)) {
        return $array;
    }

    $object = new stdClass();
    if (is_array($array) && count($array) > 0) {
        foreach ($array as $name=>$value) {
            $name = strtolower(trim($name));
            if (!empty($name)) {
                $object->$name = arrayToObject($value);
            }
        }
        return $object;
    }
    else {
        return FALSE;
    }
}
/**
 * 根据fieldname从filtervalue数组中获取对象数组
 * @return fieldname的对象数组
 */
function getFilterValueItem($fieldname,$filtervalueArr){
    $result = array();
    foreach($filtervalueArr as $key => $value){
        if($value['fieldname'] == $fieldname){
            $result[] = $value;
        }
    }
    return $result;
}

/**
 * 获取字段的值，如果是数组则递归获取所有值
 * @param $fieldvalue fieldvalue:{datatype:"int", value:123}
 * @return 根据datatype返回value，数组或单值（只返回123）
 */
function getFilterValue($fieldvalue){
    $tmpArr = array();
    if($fieldvalue["datatype"]=="array"){
        foreach($fieldvalue["value"] as $key=>$value){
            if($value["datatype"] == "array"){
                $res = getFilterValue($value);
                $tmpArr = array_merge($res, $tmpArr);
            }
            else{
                $tmpArr[] = $value['value'];
            }
        }
    }
    else{
        return $fieldvalue["value"];
    }
    return $tmpArr;
}
/**
 *
 * 获取valueobj中的值。比如获取{value:1, text:2} 中的 value
 * @param string $datatype
 * @param unknown_type $valueobj
 */
function getFieldValue($datatype, $valueobj){
    $value;
    switch($datatype){
        case "blur_value_object":
        case "value_text_object":
            $value = is_array($valueobj) ? $valueobj["value"] : $valueobj->value;
            break;
        default:
            $value = $valueobj;
            break;
    }
    return $value;

}

/**
 * 转义正则特殊符号
 * @param s
 * @returns
 */
function escapeRe($s){
    return preg_replace("/([.+?^\$\\{\\}()|[\\]\\/\\\])/i",'\\\$1',$s);
}

/**
 *
 * 验证jsonlimit是否合法
 */
function checkLimit($filter,$newlimit, $oldlimit){
    if($filter["limitcontrol"] == 0){
        if(count($filter['limit']) == 0 && count($newlimit) == 0 && count($oldlimit) == 0){
            return 1;
        }
        else if(count($oldlimit) != count($newlimit)){
            return -1;
        }
        else{
            $r = 1;
            foreach($newlimit as $i => $item){
                $nv = getLimitValue($filter['datatype'],$item);
                $iseq = false;
                foreach($oldlimit as $j => $olimit){
                    $ov = getLimitValue($filter['datatype'],$olimit);
                    if($olimit['type'] == 'range'){
                        if($olimit['repeat'] == $item['repeat'] && $olimit['type'] == $item['type']
                        && $ov['maxvalue'] == $nv['maxvalue'] && $ov['minvalue'] == $nv['minvalue']){
                            $iseq = true;
                            break;
                        }
                    }
                    else{
                        if($olimit['repeat'] == $item['repeat'] && $olimit['type'] == $item['type'] && $ov == $nv){
                            $iseq = true;
                            break;
                        }
                    }
                }
                if($iseq == false){
                    $r = -1;
                    break;
                }
            }
            return $r;
        }
    }
    $limit = $newlimit;
    if(count($filter["limit"]) > 0){
        $ov = getLimitValue($filter["datatype"],$filter['limit'][0]);
        if($filter["limit"][0]["type"] == 'range'){
            $result = 1;
            foreach($limit as $key => $item){
                $nv = getLimitValue($filter["datatype"],$item);
                if($ov["maxvalue"] !== null && ($nv["minvalue"] > $ov["maxvalue"] || $nv["minvalue"] > $ov["maxvalue"] )){
                    $result = 0;
                    break;
                }
                else if($ov["minvalue"] !== null && ($nv["minvalue"] < $ov["minvalue"] ||  $nv["minvalue"] < $ov["minvalue"])){
                    $result = 0;
                    break;
                }
            }
            return $result;
        }
		else if($filter["limit"][0]["type"] == 'gaprange'){
            $result = 1;
            foreach($limit as $key => $item){
                $nv = getLimitValue($filter["datatype"],$item);
				if($ov["maxvalue"] != null){
					if($ov["gap"] == "year"){
						$ov["maxvalue"] = $ov["maxvalue"] * 365;
					}
					else if($ov["gap"] == "month"){
						$ov["maxvalue"] = $ov["maxvalue"] * 30;
					}
				}
				if($ov["minvalue"] != null){
					if($ov["gap"] == "year"){
						$ov["minvalue"] = $ov["minvalue"] * 365;
					}
					else if($ov["gap"] == "month"){
						$ov["minvalue"] = $ov["minvalue"] * 30;
					}
				}

				if($nv["gap"] == "year"){
					$nv["maxvalue"] = $nv["maxvalue"] * 365;
					$nv["minvalue"] = $nv["minvalue"] * 365;
				}
				else if($nv["gap"] == "month"){
					$nv["maxvalue"] = $nv["maxvalue"] * 30;
					$nv["minvalue"] = $nv["minvalue"] * 30;
				}

				if($ov["maxvalue"] !== null && ($nv["minvalue"] > $ov["maxvalue"] || $nv["maxvalue"] > $ov["maxvalue"] )){
                    $result = 0;
                    break;
                }
                else if($ov["minvalue"] !== null && ($nv["minvalue"] < $ov["minvalue"] ||  $nv["maxvalue"] < $ov["minvalue"])){
                    $result = 0;
                    break;
				}
				/*
				else{
					if($ov["gap"] != null && (checkRegisterTimeGap($nv["gap"], $ov["gap"]))){ //验证博龄的gap是否在limit范围内
						$result = 0;
						break;
					}
				} 
				 */
            }
            return $result;
		}		
		else if($filter["limit"][0]["type"] == 'time_dynamic_range'){
		    return 1; //TODO
		}
		else if($filter["limit"][0]["type"] == 'time_dynamic_state'){
			return 1;
		}
        else{
            //生成正则
            $exactReg = array();
            //根据计费中的limit生成正则
            foreach($filter["limit"] as $key => $item){
                $value = getLimitValue($filter["datatype"],$item);
                //var_dump($value);
                if(isset($value)){
                    if($item["type"] == "inexact"){
                        $value = str_replace("*",".*",escapeRe($value));
                    }
					else{
						$value = escapeRe($value);
					}
                    $exactReg[] = array("repeat"=>$item["repeat"], "reg"=>'/^'.$value.'$/');
                }
            }
            /*
             if($filter["label"] == "关键词"){
             var_dump($exactReg);
             var_dump($limit);
             }
             */
            if(count($exactReg) > 0){
                for($k = count($limit)-1; $k>-1;$k--){
                    foreach ($exactReg as $i=>$item){
                        $value =  getLimitValue($filter["datatype"],$limit[$k]);
                        if(isset($value) && ($item['repeat'] >= $limit[$k]["repeat"] || $item['repeat'] == -1)){
                            $temp = array();
                            if(preg_match_all($item["reg"],$value,$temp) > 0){
                                $item['repeat'] -= $limit[$k]["repeat"];
                                array_splice($limit, $k, 1);
                                break;
                            }
                        }
                    }
                }
            }
            return count($limit) == 0 ? 1 : 0;
        }
    }
    else{
        return 1;
    }

}

/**
 *
 * 验证value是否合法
 * @param $filter 传递引用，需要修改repeat
 * @param $fieldvalue 具体的值，datajson["filtervalue"][$i]["fieldvalue"]["value"]
 */
function checkFilterValue(&$filter,$fieldvalue){
    if(count($filter["limit"]) > 0){
        if($filter["limit"][0]["type"] == 'range'){
            $result = 1;
            if($filter["limit"][0]['value']["maxvalue"] != null){
                if($fieldvalue["start"] > $filter["limit"][0]['value']["maxvalue"] || $fieldvalue["end"] > $filter["limit"][0]['value']["maxvalue"]){
                    return 0;
                }
            }
            if($filter["limit"][0]['value']["minvalue"] != null){
                if($fieldvalue["start"] < $filter["limit"][0]['value']["minvalue"] || $fieldvalue["end"] < $filter["limit"][0]['value']["minvalue"]){
                    return 0;
                }
            }
            return $result;
        }
        else{
            $result = 0;
            foreach($filter["limit"] as $key => $item){
                $value = getLimitValue($filter["datatype"],$item);
                if(isset($value)){
                    if($item["type"] == "inexact"){
                        $value = str_replace("*",".*",escapeRe($value));
                    }
					else{
						$value = escapeRe($value);
					}
                    $reg = '/^'.$value.'$/';
                    if($item['repeat'] > 0){
                        $temp = array();
                        if(preg_match_all($reg,$fieldvalue,$temp) > 0){
                            $filter["limit"][$key]["repeat"]--;
                            $result = 1;
                            break;
                        }
                    }
                }
            }
            return $result;
        }
    }
    else{
        return 1;
    }

}
function is_assoc($arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
}
function getNewJson($oldjson){
    $newModel  = getModelByID($oldjson["modelid"]);
    $newJson = json_decode(json_encode($newModel->datajson), true);
    foreach($newJson["filter"] as $key=>$value){
        if(isset($oldjson["filter"][$key])){
            if($newJson["filter"][$key]['datatype'] == $oldjson["filter"][$key]['datatype']){
                $newJson["filter"][$key]['limit'] = $oldjson["filter"][$key]['limit'];
            }
            //$newJson["filter"][$key]['isshow'] = $oldjson["filter"][$key]['isshow'];
            $newJson["filter"][$key]['allowcontrol'] = $oldjson["filter"][$key]['allowcontrol'];
            $newJson["filter"][$key]['maxlimitlength'] = $oldjson["filter"][$key]['maxlimitlength'];
            $newJson["filter"][$key]['limitcontrol'] = $oldjson["filter"][$key]['limitcontrol'];
            $newJson["filter"][$key]['unitprice'] = $oldjson["filter"][$key]['unitprice'];
            $newJson["filter"][$key]['maxprice'] = $oldjson["filter"][$key]['maxprice'];
            $newJson["filter"][$key]['onceeditprice'] = $oldjson["filter"][$key]['onceeditprice'];
            $newJson["filter"][$key]['maxeditprice'] = $oldjson["filter"][$key]['maxeditprice'];
        }
    }
    if(isset($newJson['facet']) && isset($oldjson['facet'])){
        if($newJson['facet']['datatype'] == $oldjson["facet"]['datatype']){
			if(count($oldjson["facet"]['limit']) > 0){
				$newJson['facet']['limit'] = $oldjson["facet"]['limit'];
			}
			if($oldjson["version"] < 1021){
				foreach($newJson['facet']['limit'] as $fi=>$fitem){
					if($fitem["value"] == "topic"){
						$newJson['facet']['limit'][$fi]["value"] = "wb_topic";
					}
				}
			}
        }
        //$newJson['facet']['isshow'] = $oldjson["facet"]['isshow'];
        $newJson['facet']['allowcontrol'] = $oldjson["facet"]['allowcontrol'];
        $newJson['facet']['maxlimitlength'] = $oldjson["facet"]['maxlimitlength'];
        $newJson['facet']['limitcontrol'] = $oldjson["facet"]['limitcontrol'];
        $newJson['facet']['unitprice'] = $oldjson["facet"]['unitprice'];
        $newJson['facet']['maxprice'] = $oldjson["facet"]['maxprice'];
        $newJson['facet']['onceeditprice'] = $oldjson["facet"]['onceeditprice'];
        $newJson['facet']['maxlimitlength'] = $oldjson["facet"]['maxlimitlength'];
        if(isset($newJson['facet']['filterlimit']) && isset($oldjson['facet']['filterlimit'])){
            if($newJson['facet']['filterlimit']['datatype'] == $oldjson["facet"]['filterlimit']['datatype']){
                $newJson['facet']['filterlimit']['limit'] = $oldjson["facet"]['filterlimit']['limit'];
            }
            //$newJson['facet']['filterlimit']['isshow'] = $oldjson["facet"]['filterlimit']['isshow'];
            $newJson['facet']['filterlimit']['allowcontrol'] = $oldjson["facet"]['filterlimit']['allowcontrol'];
            $newJson['facet']['filterlimit']['maxlimitlength'] = $oldjson["facet"]['filterlimit']['maxlimitlength'];
            $newJson['facet']['filterlimit']['limitcontrol'] = $oldjson["facet"]['filterlimit']['limitcontrol'];
            $newJson['facet']['filterlimit']['unitprice'] = $oldjson["facet"]['filterlimit']['unitprice'];
            $newJson['facet']['filterlimit']['maxprice'] = $oldjson["facet"]['filterlimit']['maxprice'];
            $newJson['facet']['filterlimit']['onceeditprice'] = $oldjson["facet"]['filterlimit']['onceeditprice'];
            $newJson['facet']['filterlimit']['maxeditprice'] = $oldjson["facet"]['filterlimit']['maxeditprice'];
        }
    }
    if(isset($newJson['output']) && isset($oldjson['output'])){
        if($newJson['output']['datatype'] == $oldjson["output"]['datatype']){
            //$newJson['output']['limit'] = $oldjson["output"]['limit'];
        }
        //$newJson['output']['isshow'] = $oldjson["output"]['isshow'];
        $newJson['output']['allowcontrol'] = $oldjson["output"]['allowcontrol'];
        $newJson['output']['maxlimitlength'] = $oldjson["output"]['maxlimitlength'];
        $newJson['output']['limitcontrol'] = $oldjson["output"]['limitcontrol'];
		$newJson['output']['unitprice'] = $oldjson['output']['unitprice'];
		$newJson['output']['maxprice'] = $oldjson['output']['maxprice'];
		$newJson['output']['maxeditprice'] = $oldjson['output']['maxeditprice'];
		$newJson['output']['onceeditprice'] = $oldjson['output']['onceeditprice'];
        if(isset($newJson['output']['countlimit']) && isset($oldjson['output']['countlimit'])){
            if($newJson['output']['countlimit']['datatype'] == $oldjson['output']['countlimit']['datatype']){
                $newJson['output']['countlimit']['limit'] = $oldjson['output']['countlimit']['limit'];
            }
            //$newJson['output']['countlimit']['isshow'] = $oldjson['output']['countlimit']['isshow'];
            $newJson['output']['countlimit']['allowcontrol'] = $oldjson['output']['countlimit']['allowcontrol'];
            $newJson['output']['countlimit']['maxlimitlength'] = $oldjson['output']['countlimit']['maxlimitlength'];
            $newJson['output']['countlimit']['limitcontrol'] = $oldjson['output']['countlimit']['limitcontrol'];
            $newJson['output']['countlimit']['unitprice'] = $oldjson['output']['countlimit']['unitprice'];
            $newJson['output']['countlimit']['maxprice'] = $oldjson['output']['countlimit']['maxprice'];
            $newJson['output']['countlimit']['maxeditprice'] = $oldjson['output']['countlimit']['maxeditprice'];
            $newJson['output']['countlimit']['onceeditprice'] = $oldjson['output']['countlimit']['onceeditprice'];
        }
    }
    if(isset($newJson['select']) && isset($oldjson['select'])){
        if($newJson['select']['datatype'] == $oldjson["select"]['datatype']){
            //$newJson['select']['limit'] = $oldjson["select"]['limit'];
        }
        //$newJson['select']['isshow'] = $oldjson["select"]['isshow'];
        $newJson['select']['allowcontrol'] = $oldjson["select"]['allowcontrol'];
        $newJson['select']['maxlimitlength'] = $oldjson["select"]['maxlimitlength'];
        $newJson['select']['limitcontrol'] = $oldjson["select"]['limitcontrol'];
        $newJson['select']['unitprice'] = $oldjson["select"]['unitprice'];
        $newJson['select']['maxprice'] = $oldjson["select"]['maxprice'];
        $newJson['select']['onceeditprice'] = $oldjson["select"]['onceeditprice'];
        $newJson['select']['maxeditprice'] = $oldjson["select"]['maxeditprice'];
    }
    if(isset($oldjson['allowupdatesnapshot'])){
    	$newJson['allowupdatesnapshot'] = $oldjson['allowupdatesnapshot'];
    }
    else{
    	$newJson['allowupdatesnapshot'] = true;
    }
    if(isset($oldjson['alloweventalert'])){
    	$newJson['alloweventalert'] = $oldjson['alloweventalert'];
    }
    else{
    	$newJson['alloweventalert'] = true;
    }

    if(isset($oldjson['allowDownload'])){
    	$newJson['allowDownload'] = $oldjson['allowDownload'];
    }
    else{
    	$newJson['allowDownload'] = false;
    }

	if(isset($oldjson['download_FieldLimit'])){
    	$newJson['download_FieldLimit'] = $oldjson['download_FieldLimit'];
    }
    else{
    	$newJson['download_FieldLimit'] = array();
    }
	if(isset($oldjson['download_DataLimit'])){
    	$newJson['download_DataLimit'] = $oldjson['download_DataLimit'];
    }
	if(isset($oldjson['download_DataLimit_limitcontrol'])){
    	$newJson['download_DataLimit_limitcontrol'] = $oldjson['download_DataLimit_limitcontrol'];
    }
	if(isset($oldjson['download_FieldLimit_limitcontrol'])){
    	$newJson['download_FieldLimit_limitcontrol'] = $oldjson['download_FieldLimit_limitcontrol'];
    }
    return $newJson;
}
$task;//common php中的insert_status需要全局的task对象
function getSinaInfo($params, $timeline){
	global $oAuthThird, $oAuthThirdBiz, $logger, $task;
    $source = $params["source"];
	switch($timeline){
	case "getweibo":
		$weiboidtype = $params["weiboidtype"];
		$weiboid = $params["weiboid"];
		break;
	case "getuser":
		$id = $params["id"];
		$screen_name = $params["screen_name"];
		break;
	default:
		break;
	}
	$res_machine;
	$res_ip;
	$res_acc;
	$task = new Task(null);
	$task->machine = SERVER_MACHINE;
	$task->taskparams->scene->state = SCENE_NORMAL;
	$task->tasklevel = 2;
	$task->queuetime = time();
	$task->tasksource = $source;
	$task->taskparams->source = $source;
	$task->taskparams->iscommit = true;
	$result = array('result'=>true, 'msg'=>'');
	getAllConcurrentRes($task,$res_machine,$res_ip,$res_acc);
	if($task->taskparams->scene->state == SCENE_NORMAL){
		checkAndApplyResource($task,$res_machine,$res_ip,$res_acc);
		if($task->taskparams->scene->state == SCENE_NORMAL){
			switch($timeline){
			case "getweibo":
				if($weiboidtype == 'mid'){  //实际传递的是js转换后的 mid
					$logger->debug(TASKMANAGER.' begin queryid mid is:'.$weiboid);
					$weiboid = $oAuthThird->queryid($weiboid);
					$logger->debug(TASKMANAGER.' end queryid result:'.$weiboid);
					//var_dump($weiboid);
					//exit;
					if(isset($weiboid['error'])){
						$logger->error("queryid error:".$weiboid['error'].' error_code:'.$weiboid['error_code']);
						$result['result'] = false;
						$result['msg'] = '获取微博ID异常';
					}
					else if($weiboid == ''){
						$result['result'] = false;
						$result['msg'] = '获取微博ID失败';
					}
					else{
						$realid = $weiboid['id'];
					}
				}
				else{
					$realid = $weiboid;
				}
				if($result['result'] !== false){
					$weibo_info = $oAuthThird->show_status($realid);
					if(isset($weibo_info['error'])){
						$logger->error("show_status error:".$weibo_info['error'].' error_code:'.$weibo_info['error_code']);
						$result['result'] = false;
						switch ($weibo_info['error_code']){
						case ERROR_CONTENT_NOT_EXIST:
							$result['msg'] = '微博不存在';
							break;
						case ERROR_IP_OUT_LIMIT:
							$result['msg'] = '无IP资源';
							break;
						case ERROR_USER_OUT_LIMIT:
							$result['msg'] = '无用户资源';
							break;
						default:
							$result['msg'] = '获取微博失败';
							break;
						}
					}
					else {
						$tempArr = array();
						//补全page_url
						$weibo_info['page_url'] = weibomid2Url($weibo_info['user']['id'], $weibo_info['mid'], 1);
						$weibo_info['source_host'] = "weibo.com";	
						$weibo_info['user']['page_url'] = userid2Url($weibo_info['user']['id'],1);
						if(isset($weibo_info['retweeted_status'])){
							$weibo_info['retweeted_status']['source_host'] = "weibo.com";
							$weibo_info['retweeted_status']['page_url'] = weibomid2Url($weibo_info['retweeted_status']['user']['id'], $weibo_info['retweeted_status']['mid'], 1);
							$weibo_info['retweeted_status']['user']['page_url'] = userid2Url($weibo_info['retweeted_status']['user']['id'],1);
						}

						$tempArr[] = $weibo_info;
						//$solr_r = insert_status2($tempArr,'show_status',$source);						
						$solr_r = addweibo($source, $tempArr,0,'show_status',true);
						if($solr_r !== true){
							unset($tempArr);
							$result['result'] = false;
							$result['msg'] = '新增微博异常';
						}
						else{
							unset($solr_r);
							unset($tempArr);
						}
					}
				}
				break;
			case "getuser":
				$logger->debug(__FUNCTION__.' begin show_user id is:'.$id.', screen_name is'.$screen_name);
				$start_t = microtime_float();
                $user_info = $oAuthThirdBiz->get_userid($screen_name);    //由于老接口有频次限制  所以新写一个方法(get_userid)调用写接口解决这个问题
//                $logger->debug(__FUNCTION__.' userinfo '.var_export($user_info,true));
//				$user_info = $oAuthThird->show_user($id, $screen_name);
//                $logger->debug(__FUNCTION__.' userinfo '.var_export($user_info,true));
				$end_t = microtime_float();
				$timediff = $end_t - $start_t;
				$logger->debug(__FUNCTION__.' end show_user  user time is '.$timediff);
				if ($user_info === false || $user_info === null)
				{
					$logger->error(__FUNCTION__." show_user({$id},{$screen_name}) API return empty (".var_export($user_info,true).")");
					$result['result'] = false;
					$result['msg'] = '获取用户失败';
				}
				else if(isset($user_info['error'])){
					$logger->error(__FUNCTION__." show_user({$id}, {$screen_name}) error:".$user_info['error'].' error_code:'.$user_info['error_code']);
					$c_r = checkAPIResult($user_info);//检查错误，如果是返回NULL说明资源超限
					if($c_r === NULL){//无资源，继续获取资源
						continue;
					}
					$result['result'] = false;
					$result['msg'] = getAPIErrorText($user_info['error_code']);
					$result['error_code'] = $user_info['error_code'];
				}
				else {
					//补全page_url
					$user_info['page_url'] = userid2Url($user_info['0']['uid'],1);
					$result['result'] = true;
					$result['user'] = $user_info;
				}
				break;
            case "weibo_limited":
                $result['totalnum'] = 0;
                $ids = NULL;
                if(isset($params['userids'])){
                    $idArr = $params['userids'];
                    $ids = implode("~", $idArr);
                }
                $starttime = !empty($params['starttime']) ? $params['starttime'] : 0;
                $endtime = !empty($params['endtime']) ? $params['endtime'] : 0;

                $dup = 0;         //是否排重（不显示相似数据），0：否、1：是，默认为1。
                $antispam = 0;    //是否反垃圾（不显示低质量数据），0：否、1：是，默认为1。

                foreach($params['keywords'] as $key=>$q){
                    $s_time = microtime_float();
                    $resnum = $oAuthThirdBiz->weibo_limited($q, 1, 50, $ids, NULL, $starttime, $endtime, $dup, 1, $antispam);
                    $logger->debug(__FILE__.__LINE__." resnum ".var_export($resnum, true));
                    if(!isset($resnum['error_code'])){
                        $result['totalnum'] += $resnum;
                    }
                    $logger->debug(__FILE__.__LINE__." totalnum ".var_export($result['totalnum'], true));
                    $e_time = microtime_float();
                    $diff_time = $e_time - $s_time;
                    $logger->debug(__FILE__.__LINE__." weibo_limited onlynum cost".$diff_time);
                }
                break;
            case "GetMoreWbnum":
                $result = array();
                $ids = NULL;
                if(isset($params['userids'])){
                    $idArr = $params['userids'];
                    $ids = implode("~", $idArr);
                }
                $starttime = !empty($params['starttime']) ? $params['starttime'] : 0;
                $endtime = !empty($params['endtime']) ? $params['endtime'] : 0;

                $dup = 0;         //是否排重（不显示相似数据），0：否、1：是，默认为1。
                $antispam = 0;    //是否反垃圾（不显示低质量数据），0：否、1：是，默认为1。

                foreach($params['keywords'] as $key=>$q){
                    $s_time = microtime_float();
                    $resnum = $oAuthThirdBiz->weibo_limited($q, 1, 50, $ids, NULL, $starttime, $endtime, $dup, 1, $antispam);
                    $logger->debug(__FILE__.__LINE__." resnum ".var_export($resnum, true));
                    if(!isset($resnum['error_code'])){
                        $result[] = $resnum;
                    }else{
                        $result[] = 0;
                    }
//                    $logger->debug(__FILE__.__LINE__." totalnum ".var_export($result['totalnum'], true));
                    $e_time = microtime_float();
                    $diff_time = $e_time - $s_time;
                    $logger->debug(__FILE__.__LINE__." weibo_limited onlynum cost".$diff_time);
                }
                break;
			default:
				break;
			}
		}
		else{
			$result['result'] = false;
			$result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
		}
	}
	else{//无资源
		$result['result'] = false;
		$result['msg'] = getResourceErrorMsg($task->taskparams->scene->state);
	}
	$task->taskparams->scene->state = SCENE_NORMAL;//强制释放所有资源
	myReleaseResource($task,$res_machine,$res_ip,$res_acc);
	return $result;
}

?>
