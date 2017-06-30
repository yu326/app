<?php
/**
 * 脚本公共函数
 */
define('MAXREGET_COUNT',5);//请求数据时，重复请求次数

function calcPage($existscount, $pagesize){
    $page = floor($existscount / $pagesize)+1;
    return $page;
}

/**
 * 
 * 获取断档的起始页码
 * @param $userid
 * @param $sourceid
 * @param $lefttime
 * @param $eachcount
 */
function getUserInterruptPage($userid,$sourceid,$lefttime, $eachcount){
    global $dsql, $logger;
    $page = false;
    //去得用户的微博中比断档右侧时间大的微博数
    $sql = "select count(0) as cnt from ".DATABASE_WEIBO." where userid='{$userid}' and sourceid={$sourceid} and created_at > {$lefttime}";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql is {$sql} has error:".$dsql->GetError());
        return false;
    }
    else{
        $rs = $dsql->GetArray($qr);
        if(!empty($rs)){
            $exists_count = $rs['cnt'];
            $page = calcPage($exists_count, $eachcount);  
        }
    }
    return $page;
}

/**
 * 
 * 获取转发微博断档的起始页码
 * @param $father_id 父ID
 * @param $is_repost 是否转发
 * @param $sourceid  源ID
 * @param $lefttime 断档右侧时间
 * @param $eachcount 抓取每页多少条
 */
function getRepostInterruptPage($father_id, $is_repost, $sourceid, $lefttime, $eachcount, $ismid=false){
    global $dsql, $logger;
    $page = false;
    //去得用户的微博中比断档右侧时间大的微博数
    /*if($is_repost){
    	$father_guid = $sourceid.($ismid ? "m" : "")."_".$father_id;
    	$wh = " father_guid='{$father_guid}'";
    }
    else{
    	$wh = $ismid ? " retweeted_mid='{$father_id}'" : " retweeted_status='{$father_id}'";
    }*/
	$query_conds['sourceid'] = $sourceid;
	if($is_repost){
		if($ismid){
			$query_conds['reply_father_mid'] = $father_id;		
		}
		else{
			$query_conds['reply_father_id'] = $father_id;
		}
		$father_guid = getFatherGuidFromSolr($query_conds);
		if($father_guid===false){
			$logger->error(__FUNCTION__." 获取父guid失败");
			return false;
		}
		$wh = " father_guid='{$father_guid}'";
	}
	else{
		if($ismid){
			$query_conds['retweeted_mid'] = $father_id;
			$wh =" retweeted_mid='{$father_id}'";
		}
		else{
			$query_conds['retweeted_status'] = $father_id;
			$wh =" retweeted_status='{$father_id}'";
		}
	}


    $sql = "select count(0) as cnt from ".DATABASE_WEIBO." where {$wh} and sourceid={$sourceid} and created_at > {$lefttime}";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql is {$sql} has error:".$dsql->GetError());
        return false;
    }
    else{
        $rs = $dsql->GetArray($qr);
        if(!empty($rs)){
            $exists_count = $rs['cnt'];
            $page = calcPage($exists_count, $eachcount);  
        }
    }
    return $page;
}

/**
 * 获取微博的转发断档
 * @param $father_id 微博ID
 * @param $is_repost 是否是转发
 * @param $sourceid 数据源ID
 * @return array 断档对象,包括最新ID，各个断档的右侧时间戳 array("interrupt_newtime"=>(int), "interrupts"=>array());
 */
function getRepostInterrupts($father_id, $is_repost, $sourceid, $mintime=false, $ismid=false){
    global $dsql, $logger;
    $result = array();
	if($ismid){
    	$wh = " mid='{$father_id}'";
    }
    else{
    	$wh = " id='{$father_id}'";
    }
    //$father_guid = $sourceid.($ismid ? "m" : "")."_".$father_id;
	$query_conds['sourceid'] = $sourceid;
	$query_conds['reply_father_mid'] = $father_id;
	$father_guid = getFatherGuidFromSolr($query_conds);
	if($father_guid===false){
		$logger->error(__FUNCTION__." 获取父guid失败");
		return false;
	}

    $cwh = " father_guid='{$father_guid}'";
    //先获取上次获取的最新ID（认为未获取到的微博也是断档，读取断档的右侧时间）
    $sqlnew = "select id,interrupt_newtime from ".DATABASE_WEIBO." where {$wh} and sourceid={$sourceid}";
    $f_name = $is_repost ? "interrupt_repost_righttime" : "interrupt_orig_righttime";
    if(!empty($mintime)){
    	$whmin = " and {$f_name} <= {$mintime}";
    }
    else{
    	$whmin = "";
    }
    $sqlchild = "select id,{$f_name}, created_at from ".DATABASE_WEIBO." where {$cwh} 
        and {$f_name} is not null and sourceid={$sourceid} {$whmin} order by {$f_name} desc";
    $qrnew = $dsql->ExecQuery($sqlnew);
    if(!$qrnew){
        $logger->error(__FUNCTION__." sql:{$sqlnew} has error:".$dsql->GetError());
        return false;
    }
    else{
        $rsnew = $dsql->GetArray($qrnew);
        if(!empty($rsnew['interrupt_newtime'])){
            $result[] = array('id'=>'', 'righttime'=> $rsnew['interrupt_newtime'],'type'=>"newest");//最左侧断档
        }
    }
    $qrchild = $dsql->ExecQuery($sqlchild);
    if(!$qrchild){
        $logger->error(__FUNCTION__." sql:{$sqlchild} has error:".$dsql->GetError());
        return false;
    }
    else{
        while($rschild = $dsql->GetArray($qrchild)){
            if(!empty($rschild[$f_name])){
                $result[] = array("id"=>$rschild['id'],"righttime"=>$rschild[$f_name],'type'=>'normal', "created_at"=>$rschild['created_at']);
            }
        }
    }
    return $result;
}

/**
 * 
 * 记录子微博中的最新时间
 * @param $id 微博ID
 * @param $sourceid 数据源ID
 * @param $newtime 最新时间
 */
function setRepostNewtime($id,$sourceid,$newtime,$ismid){
    global $dsql, $logger;
    $fieldname = $ismid ? "mid" : "id";
    $sql = "update ".DATABASE_WEIBO." set interrupt_newtime = {$newtime} where {$fieldname}='{$id}' and sourceid={$sourceid}";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql:{$sql} has error:".$dsql->GetError());
        return false;
    }
    else{
        return true;
    }
}

/**
 * 设置转发的断档
 * @param $id 微博ID，断档的左侧微博ID
 * @param $sourceid 数据源ID
 * @param $father_isrepost 父微博是否转发
 * @param $righttime 断档右侧微博的时间戳
 */
function setRepostInterrupt($id, $sourceid, $father_isrepost, $righttime){
    global $dsql, $logger;
    $f_name = $father_isrepost ? "interrupt_repost_righttime" : "interrupt_orig_righttime";
    $subsql = empty($righttime) ? "{$f_name}=null" : "{$f_name}={$righttime}";
    $sql = "update ".DATABASE_WEIBO." set {$subsql} where id = '{$id}' and sourceid={$sourceid}";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql:{$sql} has error:".$dsql->GetError());
        return false;
    }
    else{
        return true;
    }
}
