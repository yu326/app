<?php
/**
 * 计算桥接微博的函数
 */
define('REPOST_EXTEND_COUNT',5);//子微博延伸个数
define('REPOST_EXTEND_CONDITION',0.05);//子微博延伸条件，总转发大于父微博的0.05倍
/**
 * 计算微博是否可延伸
 * 微博的总转发数大于等于（父微博的总转发减去父微博的直接转发）除以延伸个数
 */
function checkExtendWeibo($father_direct_reposts_count,$father_total_reposts_count, $childrepostcount){
    if($childrepostcount == 0){
        return false;
    }
    return $childrepostcount >= ($father_total_reposts_count - $father_direct_reposts_count) * REPOST_EXTEND_CONDITION;
}

/**
 * 找所有祖先，当有祖先的直接转发大于等于自己的直接转发的二倍时，返回祖先的树形结构
 * 同时判断祖先中是否有桥接微博，如果有，取出案例编号
 * $foreinfo 数组对象 {"hasbebridge"=>false/true, "case_id"=>'', "foretree"=>array()}
 *            hasbebridge表示祖先中是否有被桥接的目标，case_id祖先的案例ID，foretree保存所有祖先
 */
function getForefather($weibo,$fatherid, &$foreinfo){
    global $dsql, $logger;
    $sql = "select a.id,a.guid,a.sourceid, a.father_guid,a.is_repost,a.is_bridge_status, a.total_reposts_count,a.reposts_count, b.case_id
        from ".DATABASE_WEIBO." a left join ".DATABASE_BRIDGE_CASE." b on a.id = b.repost_id
        where a.guid='{$fatherid}' and a.sourceid = {$weibo['sourceid']}";
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql:{$sql} error:".$dsql->GetError());
        return false;
    }
    else{
        $rs = $dsql->GetArray($qr);
        $dsql->FreeResult($qr);
        if($rs['is_repost'] == 1){
            $bebridge = $rs['reposts_count'] > $weibo['reposts_count'] * 2;//祖先中直接转发比自己大两倍
        }
        else{
            $bebridge = $rs['total_reposts_count'] >= $weibo['reposts_count'] * 2;//祖先中直接转发比自己大两倍
        }
        $rs['bebridge'] = $bebridge;
        if($bebridge){
            $foreinfo['hasbebridge'] = true;
        }
        $foreinfo['case_id'] = $rs['case_id'];
        //因为找祖先是倒着找，所以每次将foreinfo 中的foretree赋值给当前找到的祖先的children
        $fore = array("id"=>$rs['id'],"bebridge"=>$rs['bebridge'],"children"=>array());
        if(!empty($foreinfo['foretree'])){
            $fore['children'][] = $foreinfo['foretree'];
        }
        $foreinfo['foretree'] = $fore;
        if(!empty($rs['father_guid']) && $rs['is_repost'] == 1){
            getForefather($weibo, $rs['father_guid'], $foreinfo);
        }
    }
}

/**
 * 找后代，并按后代的总转发、直接转发判断是否可延伸，后代的直接转发是自己的两倍
 * 返回后代树结构
 * 返回 符合被桥接条件的子孙的树结构
 * 不在函数中组成完整的需要显示的树，是因为孩子中未全部处理完毕，有可能有的孩子是桥接
 */
function getDescendant($weibo,$father=NULL){
    global $dsql, $logger;
    $father = empty($father) ? $weibo : $father;
    //$limit = 0;
    $eachcount = 1 / REPOST_EXTEND_CONDITION;
    $result = array();
    //while(true){
    $sql = "select id,sourceid,is_repost,repost_trend_cursor, reposts_count,direct_reposts_count,total_reposts_count from ".DATABASE_WEIBO."
            where father_guid = '{$father['guid']}' and reposts_count > 0 and sourceid={$weibo['sourceid']}
             order by total_reposts_count desc limit 0, ".$eachcount;
    $qr = $dsql->ExecQuery($sql);
    if(!$qr){
        $logger->error(__FUNCTION__." sql:{$sql} error:".$dsql->GetError());
        break;
    }
    else{
        //$rsnum = $dsql->GetTotalRow($qr);
        //if($rsnum == 0){
        //    break;
        //}
        while($rs = $dsql->GetArray($qr)){
            $total_extend = checkExtendWeibo($father['direct_reposts_count'],$father['total_reposts_count'],$rs['total_reposts_count']);//根据总转发数判断是否可延伸
            $repost_extend = checkExtendWeibo($father['direct_reposts_count'],$father['total_reposts_count'],$rs['direct_reposts_count']);//根据直接转发数判断是否可延伸
            $bigrepost = $rs['direct_reposts_count'] >= $weibo['direct_reposts_count'] * 2;//后代的直接转发大于自己的2倍
            $logger->debug("第{$rs['repost_trend_cursor']}层转发的{$rs['id']}:total_extend:{$total_extend},repost_extend:{$repost_extend},bigrepost:{$bigrepost}");
            if($total_extend && $repost_extend && $bigrepost){//符合条件,找到被桥接的子孙后，不再往下找
                $result[] = array("id"=>$rs['id'],"bebridge"=>true);
            }
            else{
                $desc = getDescendant($weibo,$rs);
                if(!empty($desc)){
                    $result[] = array("id"=>$rs['id'],"bebridge"=>false, "children"=>$desc);
                }
            }
        }
        $dsql->FreeResult($qr);
    }
    //if($rsnum < $eachcount){
    //    break;
    //}
    //$limit += $eachcount;
    //}
    return $result;
}

function calcBridge($weibo,$father){
    global $dsql, $logger;
    $resulttree = array("bridgetree"=>null);
    //先判断自己是否可延伸
    if(checkExtendWeibo($father['direct_reposts_count'],$father['total_reposts_count'],$weibo['total_reposts_count'])){
        //$resulttree['extended'] = true;
        //判断祖先是否有被桥接的微博
        $foreinfo = array('hasbebridge'=>false, 'case_id'=>null, 'foretree'=>array("id"=>$weibo['id'],"bebridge"=>false,"children"=>array()));
        $logger->debug(__FUNCTION__." begin getForefather:{$weibo['id']}");
        getForefather($weibo, $weibo['father_guid'], $foreinfo);
        $logger->debug(__FUNCTION__." end getForefather");
        if(!empty($foreinfo['hasbebridge'])){
            //判断子孙中是否有被桥接的
            $logger->debug(__FUNCTION__." begin getDescendant:{$weibo['id']}");
            $desctree = getDescendant($weibo);
            $logger->debug(__FUNCTION__." end getDescendant");
            if(!empty($desctree)){
                $resulttree['case_id'] = $foreinfo['case_id'];
                $resulttree['bridgetree'] = $foreinfo['foretree'];//取出祖先的树结构
                if(empty($resulttree['bridgetree']['children'])){
                    unset($resulttree['bridgetree']);
                }
                else{
                    //找到祖先结构中的最后一级，将后代结构添加到最后一集的children中
                    $child = &$resulttree['bridgetree']['children'][0];
                    while(true){
                        if(empty($child['children'])){
                            $child['children'][] = $desctree;
                            break;
                        }
                        $child = &$child['children'][0];
                    }
                }
            }
        }
        else{
            unset($resulttree['bridgetree']);
        }
    }
    return $resulttree;
}

/**
 * 查找桥接微博
 * @param 关联数组  $orig 原创微博对象
 */
function findBridgeWeibo($orig){
    global $dsql, $logger;
    $father_guid = $orig['guid'];
    $ext_total_reposts_count = ($orig['total_reposts_count']-$orig['direct_reposts_count']) * REPOST_EXTEND_CONDITION;
    $ext_total_reposts_count = empty($ext_total_reposts_count) ? 0 : $ext_total_reposts_count;
    //while(true){
        $sql = "select id,guid,sourceid,is_repost,retweeted_status,userid,father_guid,direct_reposts_count,total_reposts_count from ".DATABASE_WEIBO." where father_guid = '{$father_guid}'
            and total_reposts_count >= {$ext_total_reposts_count} order by total_reposts_count desc ";
        $logger->debug(__FUNCTION__." sql:{$sql}");
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
            $logger->error(__FUNCTION__." sql:{$sql} error:".$dsql->GetError());
            return false;
        }
        else{
            $bridgeids = array();
            //$extendids = array();
            while($rs = $dsql->GetArray($qr)){
                $logger->debug(__FUNCTION__." begin calcBridge:{$rs['id']}");
                $cr = calcBridge($rs,$orig);
                $logger->debug(__FUNCTION__." end calcBridge ".var_export($cr,true));
                if(!empty($cr['bridgetree'])){//属性不为空，则表示为桥接微博，桥接微博一定是可延伸的
                    $logger->debug(__FUNCTION__." finded bridgeweibo:".var_export($cr['bridgetree'],true));
                    $bridgeids[] = $rs['id'];
                    //$extendids[] = $rs['id'];
                    if(empty($cr['case_id'])){
                        $sqlinscaseid = "insert into ".DATABASE_BRIDGE_CASE_ID." values()";
                        $qrinsid = $dsql->ExecQuery($sqlinscaseid);
                        if(!$qrinsid){
                            $logger->error(__FUNCTION__." sql:{$sqlinscaseid} error:".$dsql->GetError());
                            return false;
                        }
                        else{
                            $sqlgetnewid = "select LAST_INSERT_ID() as id";
                            $qrgetnewid = $dsql->ExecQuery($sqlgetnewid);
                            if(!$qrinsid){
                                $logger->error(__FUNCTION__." sql:{$sqlgetnewid} error:".$dsql->GetError());
                                return false;
                            }
                            else {
                                $rsnewid = $dsql->GetArray($qrgetnewid);
                                $dsql->FreeResult($qrgetnewid);
                                if(empty($rsnewid)){
                                    return false;
                                }
                                else{
                                    $newid = $rs['id'];
                                }
                            }

                        }
                    }
                    else{
                        $newid = $cr['case_id'];//使用祖先中的案例ID
                    }
                    $sqlins = "insert into ".DATABASE_BRIDGE_CASE." values({$newid},'{$rs['retweeted_status']}',
                            '{$rs['id']}','{$rs['userid']}',1,'".json_encode($cr['bridgetree'])."')";
                    $qrins = $dsql->ExecQuery($sqlins);
                    if(!$qrins){
                        $logger->error(__FUNCTION__." sql:{$sqlins} error:".$dsql->GetError());
                        return false;
                    }
                    
                }
                $fr = findBridgeWeibo($rs);
                if(!$fr){
                    return false;
                }
                unset($cr);
                unset($rs);
                //                else if($cr['extended']){
                //                    $extendids[] = $rs['id'];
                //                    $fr = findBridgeWeibo($rs);
                //                    if(!$fr){
                //                        return false;
                //                    }
                //                }
            }
            //更新桥接属性
            if(count($bridgeids)){
                $bridgeids = "'".implode("','", $bridgeids)."'";
                $sqlupbridge = "update ".DATABASE_WEIBO." set  is_bridge_status=1 where id in ({$bridgeids}) and sourceid = {$orig['sourceid']}";
                $qrupbridge = $dsql->ExecQuery($sqlupbridge);
                if(!$qrupbridge){
                    $logger->error(__FUNCTION__." sql:{$sqlupbridge} error:".$dsql->GetError());
                    return false;
                }
                unset($bridgeids);
                $dsql->FreeResult($qrupbridge);
            }
        }
    //}
    return true;
}
