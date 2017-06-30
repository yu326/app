<?php
/*
 * 计算租户模型价格的相关函数
 * @author Todd
 */
include_once 'includes.php';
include_once 'userinfo.class.php';
include_once 'database_config.php';

initLogger(LOGNAME_WEBAPI);

/**
 * 计算租户的剩余积分
 */
function getTenantBalance($tenantid){
    global $logger,$dsql;
    $sqlprice = "select sum(lastprice) as price from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid = {$tenantid}";
    $qrprice = $dsql->ExecQuery($sqlprice);
    if(!$qrprice){
        $logger->error(__FILE__." ".__FUNCTION__." sql:{$sqlprice} error:".$dsql->GetError());
        return false;
    }
    else{
        $spds = $dsql->GetArray($qrprice);
        $price = $spds['price'];
        //获取租户的总积分
        $sqlprepay = "select prepayment from ".DATABASE_TENANT." where tenantid = {$tenantid}";
        $qrprepay = $dsql->ExecQuery($sqlprepay);
        if(!$qrprepay){
            $logger->error(__FILE__." ".__FUNCTION__." sql:{$sqlprepay} error:".$dsql->GetError());
            return false;
        }
        else{
            $preds = $dsql->GetArray($qrprepay);
            if(!$preds){
                $logger->error(__FILE__." ".__FUNCTION__." tenantid:{$tenantid} 未找到租户");
                return false;
            }
            else{
                return $preds['prepayment'] - $price;
            }
        }
    }
}

/**
 *
 * 根据json计算出积分
 * @param $datajson
 */
function computeJsonPrice(&$datajson,$tenantid){
    global $logger,$dsql;
    try{
        $priceJson;
        $pricedata;
        $oldjson;
        //每次从租户资源表获取最新价格
        $getTenantSource = "select * from ".DATABASE_TENANT_RESOURCE." where resourceid={$datajson['modelid']}";
        $qrts = $dsql->ExecQuery($getTenantSource);
        if(!$qrts){
            $logger->error(__FILE__." ".__FUNCTION__." sql:{$getTenantSource} ");
            return false;
        }
        else{
            $pricedata = $dsql->GetArray($qrts);
            if(!$pricedata){
                $logger->error(__FILE__." ".__FUNCTION__." pricedata is empty. sql:{$getTenantSource} ");
                return false;
            }
            else{
                $priceJson = json_decode($pricedata['ruledata'],true);
            }
        }
        $sqloldjson = "select * from ".DATABASE_TENANT_RESOURCE_RELATION." where tenantid={$tenantid} and resourceid={$datajson['modelid']}";
        $qrold = $dsql->ExecQuery($sqloldjson);
        if(!$qrold){
            $logger->error(__FILE__." ".__FUNCTION__." sql:{$sqloldjson} ");
            return false;
        }
        else{
            $olddata = $dsql->GetArray($qrold);
            if($olddata){
                $oldjson = json_decode($olddata['content'],true);
            }
        }
        $allprice = $pricedata['score'];//模型初始价格
        if(isset($oldjson)){
            $allprice = $olddata['lastprice'];//修改时
        }
        foreach($datajson["filter"] as $key=>$value){
            if(isset($oldjson)){//修改时，按旧价格计算。每次从数据库获取价格，防止http被破坏
                $value['maxprice'] = $oldjson['filter'][$key]['maxprice'];
                $value['unitprice'] = $oldjson['filter'][$key]['unitprice'];
                $value['maxeditprice'] = $oldjson['filter'][$key]['maxeditprice'];
                $value['onceeditprice'] = $oldjson['filter'][$key]['onceeditprice'];
            }
            else{
				if(isset($priceJson['filter'][$key])){
					$value['maxprice'] = $priceJson['filter'][$key]['maxprice'];
					$value['unitprice'] = $priceJson['filter'][$key]['unitprice'];
					$value['maxeditprice'] = $priceJson['filter'][$key]['maxeditprice'];
					$value['onceeditprice'] = $priceJson['filter'][$key]['onceeditprice'];
				}
            }
            if(isset($oldjson)){//修改，计算差价
                $pricespread = filterSpread($value,$oldjson['filter'][$key]);
                $allprice += $pricespread;
            }
            else{//新增
                if($value['maxlimitlength'] == -1){
                    $allprice += $value["maxprice"];
                }
                else{
                    $itemprice = $value["unitprice"] * $value["maxlimitlength"];
                    if($itemprice > $value["maxprice"]){
                        $itemprice = $value["maxprice"];
                    }
                    $allprice += $itemprice;
                }
    
                if($value["limitcontrol"] == -1){
                    $allprice += $value["maxeditprice"];
                }
                else{
                    $itemprice =    $value["onceeditprice"] * $value["limitcontrol"];
                    if($itemprice > $value["maxeditprice"]){
                        $itemprice = $value["maxeditprice"];
                    }
                    $allprice += $itemprice;
                }
    
            }
        }
        
        if(isset($datajson['facet'])){
            if(isset($oldjson)){
                $datajson['facet']['maxprice'] = $oldjson['facet']['maxprice'];
                $datajson['facet']['unitprice'] = $oldjson['facet']['unitprice'];
                $datajson['facet']['maxeditprice'] = $oldjson['facet']['maxeditprice'];
                $datajson['facet']['onceeditprice'] = $oldjson['facet']['onceeditprice'];
            }
            else{
                $datajson['facet']['maxprice'] = $priceJson['facet']['maxprice'];
                $datajson['facet']['unitprice'] = $priceJson['facet']['unitprice'];
                $datajson['facet']['maxeditprice'] = $priceJson['facet']['maxeditprice'];
                $datajson['facet']['onceeditprice'] = $priceJson['facet']['onceeditprice'];
            }
            if(isset($oldjson)){//修改，计算差价
                $pricespread = filterSpread($datajson['facet'],$oldjson['facet']);
                $allprice += $pricespread;
            }
            else{//新增
                if($datajson['facet']['maxlimitlength'] == -1){
                    $allprice += $datajson['facet']["maxprice"];
                }
                else{
                    $itemprice = $datajson['facet']["unitprice"] * $datajson['facet']["maxlimitlength"];
                    if($itemprice > $datajson['facet']["maxprice"]){
                        $itemprice = $datajson['facet']["maxprice"];
                    }
                    $allprice += $itemprice;
                }
    
                if($datajson['facet']["limitcontrol"] == -1){
                    $allprice += $datajson['facet']["maxeditprice"];
                }
                else{
                    $itemprice = $datajson['facet']["onceeditprice"] * $datajson['facet']["limitcontrol"];
                    if($itemprice > $datajson['facet']["maxeditprice"]){
                        $itemprice = $datajson['facet']["maxeditprice"];
                    }
                    $allprice += $itemprice;
                }
            }
            if(isset($datajson['facet']['filterlimit'])){
                if(isset($oldjson)){
                    $datajson['facet']['filterlimit']['maxprice'] = $oldjson['facet']['filterlimit']['maxprice'];
                    $datajson['facet']['filterlimit']['unitprice'] = $oldjson['facet']['filterlimit']['unitprice'];
                    $datajson['facet']['filterlimit']['maxeditprice'] = $oldjson['facet']['filterlimit']['maxeditprice'];
                    $datajson['facet']['filterlimit']['onceeditprice'] = $oldjson['facet']['filterlimit']['onceeditprice'];
                }
                else{
                    $datajson['facet']['filterlimit']['maxprice'] = $priceJson['facet']['filterlimit']['maxprice'];
                    $datajson['facet']['filterlimit']['unitprice'] = $priceJson['facet']['filterlimit']['unitprice'];
                    $datajson['facet']['filterlimit']['maxeditprice'] = $priceJson['facet']['filterlimit']['maxeditprice'];
                    $datajson['facet']['filterlimit']['onceeditprice'] = $priceJson['facet']['filterlimit']['onceeditprice'];
                }
                if(isset($oldjson)){//修改，计算差价
                    $pricespread = filterSpread($datajson['facet']['filterlimit'],$oldjson['facet']['filterlimit']);
                    $allprice += $pricespread;
                }
                else{//新增
                    if($datajson['facet']['filterlimit']['maxlimitlength'] == -1){
                        $allprice += $datajson['facet']['filterlimit']["maxprice"];
                    }
                    else{
                        $itemprice = $datajson['facet']['filterlimit']["unitprice"] * $datajson['facet']['filterlimit']["maxlimitlength"];
                        if($itemprice > $datajson['facet']['filterlimit']["maxprice"]){
                            $itemprice = $datajson['facet']['filterlimit']["maxprice"];
                        }
                        $allprice += $itemprice;
                    }
    
                    if($datajson['facet']['filterlimit']["limitcontrol"] == -1){
                        $allprice += $datajson['facet']['filterlimit']["maxeditprice"];
                    }
                    else{
                        $itemprice = 	$datajson['facet']['filterlimit']["onceeditprice"] * $datajson['facet']['filterlimit']["limitcontrol"];
                        if($itemprice > $datajson['facet']['filterlimit']["maxeditprice"]){
                            $itemprice = $datajson['facet']['filterlimit']["maxeditprice"];
                        }
                        $allprice += $itemprice;
                    }
                }
            }
        }
        if(isset($oldjson)){
            $datajson['output']['maxprice'] = $oldjson['output']['maxprice'];
            $datajson['output']['unitprice'] = $oldjson['output']['unitprice'];
            $datajson['output']['maxeditprice'] = $oldjson['output']['maxeditprice'];
            $datajson['output']['onceeditprice'] = $oldjson['output']['onceeditprice'];
        }
        else{
            $datajson['output']['maxprice'] = $priceJson['output']['maxprice'];
            $datajson['output']['unitprice'] = $priceJson['output']['unitprice'];
            $datajson['output']['maxeditprice'] = $priceJson['output']['maxeditprice'];
            $datajson['output']['onceeditprice'] = $priceJson['output']['onceeditprice'];
        }
        if(isset($oldjson)){//修改，计算差价
            $pricespread = filterSpread($datajson['output'],$oldjson['output']);
            $allprice += $pricespread;
        }
        else{//新增
            if($datajson['output']['maxlimitlength'] == -1){
                $allprice += $datajson['output']["maxprice"];
            }
            else{
                $itemprice = $datajson['output']["unitprice"] * $datajson['output']["maxlimitlength"];
                if($itemprice > $datajson['output']["maxprice"]){
                    $itemprice = $datajson['output']["maxprice"];
                }
                $allprice += $itemprice;
            }
    
            if($datajson['output']["limitcontrol"] == -1){
                $allprice += $datajson['output']["maxeditprice"];
            }
            else{
                $itemprice = 	$datajson['output']["onceeditprice"] * $datajson['output']["limitcontrol"];
                if($itemprice > $datajson['output']["maxeditprice"]){
                    $itemprice = $datajson['output']["maxeditprice"];
                }
                $allprice += $itemprice;
            }
        }
        if(isset($oldjson)){
            $datajson['select']['maxprice'] = $oldjson['select']['maxprice'];
            $datajson['select']['unitprice'] = $oldjson['select']['unitprice'];
            $datajson['select']['maxeditprice'] = $oldjson['select']['maxeditprice'];
            $datajson['select']['onceeditprice'] = $oldjson['select']['onceeditprice'];
        }
        else{
            $datajson['select']['maxprice'] = $priceJson['select']['maxprice'];
            $datajson['select']['unitprice'] = $priceJson['select']['unitprice'];
            $datajson['select']['maxeditprice'] = $priceJson['select']['maxeditprice'];
            $datajson['select']['onceeditprice'] = $priceJson['select']['onceeditprice'];
        }
        if(isset($oldjson)){//修改，计算差价
            $pricespread = filterSpread($datajson['select'],$oldjson['select']);
            $allprice += $pricespread;
        }
        else{//新增
            if($datajson['select']['maxlimitlength'] == -1){
                $allprice += $datajson['output']["maxprice"];
            }
            else{
                $itemprice = $datajson['select']["unitprice"] * $datajson['select']["maxlimitlength"];
                if($itemprice > $datajson['select']["maxprice"]){
                    $itemprice = $datajson['select']["maxprice"];
                }
                $allprice += $itemprice;
            }
    
            if($datajson['select']["limitcontrol"] == -1){
                $allprice += $datajson['select']["maxeditprice"];
            }
            else{
                $itemprice = $datajson['select']["onceeditprice"] * $datajson['select']["limitcontrol"];
                if($itemprice > $datajson['select']["maxeditprice"]){
                    $itemprice = $datajson['select']["maxeditprice"];
                }
                $allprice += $itemprice;
            }
        }
        return $allprice;
    }
    catch (Exception $ex){
        $logger->error(__FILE__." ".__FUNCTION__." exception:".$ex->getMessage());
    }
}

/**
 * 计算单个filter的差价
 */
function filterSpread($newfilter,$oldfilter){
    $pricespread = 0;//差价
    //计算修改次数价格
    if($newfilter['limitcontrol'] != $oldfilter['limitcontrol']){
        if($newfilter['limitcontrol'] == -1){//新值为 不限, 差价为max减去上次剩余
            //如果旧价格大于最大值，则没有差价
            if($newfilter['onceeditprice']*$oldfilter['limitcontrol'] > $newfilter['maxeditprice']){
                $pricespread = 0;
            }
            else{
                $pricespread = $newfilter['maxeditprice'] - $newfilter['onceeditprice']*$oldfilter['limitcontrol'];
            }
        }
        else if($oldfilter['limitcontrol'] == -1){//旧值为不限
            if($newfilter['onceeditprice']*$newfilter['limitcontrol'] > $newfilter['maxeditprice']){
                $pricespread = 0;//旧值为不限时，上次已扣除最大价格。当前价格超过最大价，则差价为0
            }
            else{
                $pricespread = $newfilter['onceeditprice']*$newfilter['limitcontrol'] - $newfilter['maxeditprice'];
            }
        }
        else{//新值旧值，都不是“不限”，差价为本次减去上次剩余次数*单价
            $pricespread = $newfilter['onceeditprice']*($newfilter['limitcontrol'] - $oldfilter['limitcontrol']);
        }
    }
    //计算值价格
    if($newfilter['maxlimitlength'] != $oldfilter['maxlimitlength']){
        if($newfilter['maxlimitlength'] == -1){//新值为 不限, 差价为max减去上次价格
            if($newfilter['unitprice']*$oldfilter['maxlimitlength'] > $newfilter['maxprice']){
                $pricespread += 0;
            }
            else{
                $pricespread += $newfilter['maxprice'] - $newfilter['unitprice']*$oldfilter['maxlimitlength'];
            }
        }
        else if($oldfilter['maxlimitlength'] == -1){//旧值为不限
            if($newfilter['unitprice']*$newfilter['maxlimitlength'] > $newfilter['maxprice']){
                $pricespread += 0;//旧值为不限时，上次已扣除最大价格。当前价格超过最大价，则差价为0
            }
            else{
                $pricespread += $newfilter['unitprice']*$newfilter['maxlimitlength'] - $newfilter['maxprice'];
            }
        }
        else{//新值旧值，都不是“不限”，差价为本次减去上次剩余次数*单价
            $pricespread += $newfilter['unitprice']*($newfilter['maxlimitlength'] - $oldfilter['maxlimitlength']);
        }
    }
    return $pricespread;
}
