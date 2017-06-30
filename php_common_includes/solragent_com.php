<?php
include_once('userinfo_v2.class.php');
include_once('authorization_v2.class.php');
include_once('commonFun_v2.php');
if(!isset($_SESSION)){
    session_start();
}
initLogger(LOGNAME_WEBAPI);


$selectUrl = SOLR_URL_SELECT;
$kwblurUrl = SOLR_URL_KWBLUR;
$kwtokenUrl = SOLR_URL_KWTOKEN;
$kwgroupUrl = SOLR_URL_KWGROUP;

function solragentInit($dataArr,$islinkage=false,$datas=NULL){
    $result;
    //用户推荐
    if(isset($dataArr['modelid'])){ //标准json
        $modelid = isset($dataArr['modelid']) ? $dataArr['modelid'] : '';
        switch($modelid){
        case 31:
        case 51:
        case 2:
        case 1:
            $result = facetfieldlist($dataArr,$islinkage,$datas);
            break;
        default:
            $result = getErrorOutput("5001", ' solr arg '.$arg_type.' has a error, modelid error');
            break;
        }
    }
    return $result;
}

//is_repost 改为content_type 0原创 1转发 2评论 3评论的评论
/*facet.field高级搜索,facet.field对应参数dataoutput, 条件组合,所有的facet用同一个接口*/
/*
 *q条件中中文的词要加双引号,否则会进行分词
 * */
function facetfieldlist($fieldArr, $islinkage=false, $datas=NULL){
    global $selectUrl,$logger;
	 $start_time = microtime_float();
    $logger->debug(__FILE__.__LINE__." enter ".__FUNCTION__." fieldArr ".var_export($fieldArr, true)." islinkage ".$islinkage." datas ".var_export($datas, true)."");
    $where = array();
    $paramobj = buildSolrParamsExQ($fieldArr);
    //begin:处理删除快照特征分类2016-12-14
    if(is_array($paramobj) && isset($paramobj['featureclasserror'])){
        return getErrorOutput(0, "请重新选择一下统计特征".$paramobj["featureclasserror"]);
    }
    //end:处理删除快照特征分类2016-12-14
    $facetttype = $paramobj['facetttype'];
    $field = $paramobj['field'];
    $facetfield = $paramobj['facetfield'];
    $param = $paramobj['param'];
    //$fieldArr = addFeatureToFiltervalue($fieldArr);
    //query
    //重新组合filtervalue和filterrealtion, 把权限中设置的值添加到filtervalue中
    $fieldArr = addLimitValueToFiltervalue($fieldArr);
    //判断filtervalue中是否有text字段,没有时,增加 text:*记录,因为段落存储字段为pg_text,但是没有存储内容只存储了分词结果,需要过滤掉
    /*
    if($fieldArr['modelid'] == 31 || $fieldArr['modelid'] == 51){
        $fieldArr = addTextValueToFiltervalue($fieldArr);
    }
     */
    //2016-7-2 Bert 修改article_taginfo字段为用户对应的字段
    $fieldArr = changeArticleTagInfoField($fieldArr);
    $arg_filtervalue = $fieldArr["filtervalue"];
    $arg_filtervalue_copy = $arg_filtervalue;
    $arg_filterrelation= $fieldArr["filterrelation"];
    //当分类查询为range类型时, 根据range进行分类
    $israngeclassify = false;
    if(isset($fieldArr["classifyquery"])){
        foreach($arg_filtervalue as $ai=>$aitem){
            if(isset($aitem["fieldvalue"]) && $aitem["fieldvalue"]["datatype"] == "array"){
                if($aitem["fieldvalue"]["value"][0]["datatype"] == "classifyrange"){
                    $israngeclassify = true;
                    $tmprange = $aitem["fieldvalue"]["value"][0]["value"];
                }
            }
        }
        if(empty($tmprange["rangeinfo"]["value"]["rangevalue"]["value"]) && $israngeclassify && $tmprange["rangeinfo"]["type"] != "gaplist"){ //需要用推荐的值
            $tmp_arg_filtervalue = $arg_filtervalue;
            $tmpfield = "createdtime";
            foreach($tmp_arg_filtervalue as $ti=>$titem){
                if($titem["fieldvalue"]["datatype"] == "array"){
                    $tmpfield = $titem["fieldname"];
                    $tmpv = array("start"=>null, "end"=>null);
                    $tmp_arg_filtervalue[$ti] = createFiltervalue($titem["fieldname"], "range", $tmpv);
                }
            }
            $tmppinrelation = array(); 
            $tmpqparam  = filter_where($arg_filterrelation, $tmp_arg_filtervalue, "", false, null, $tmppinrelation, null, $fieldArr["modelid"]);
            if($tmpqparam == ""){
                $tmpqparam = getDefaultQparam($fieldArr["modelid"]);                
            }
            //add by zuo:2016-12-14
            if(is_array($tmpqparam) && isset($tmpqparam["featureclasserror"])){
                return getErrorOutput(0, "请重新选择一下此特征分类".$tmpqparam["featureclasserror"]);
            }
            //end by zuo:2016-12-4
            $tmpfield = getRealSolrFieldName($tmpfield, NULL, $fieldArr["modelid"]);
            $tmpparm = "&fl=".$tmpfield."&start=0&rows=1&facet=off&sort=".$tmpfield."+desc"; //最大值
            //$tmpurl = $selectUrl."?q=".$tmpqparam.$tmpparm; //rows = 0不返回文档个数
            $tmpurl = "q=".$tmpqparam.$tmpparm; //rows = 0不返回文档个数
            $tmpresult = solrRequest($tmpurl, "response", "query");
            $maxv = $tmpresult["docs"][0][$tmpfield];
            $tmpparmmin = "&fl=".$tmpfield."&start=0&rows=1&facet=off&sort=".$tmpfield."+asc";
            //$tmpurlmin = $selectUrl."?q=".$tmpqparam.$tmpparmmin; //rows = 0不返回文档个数
            $tmpurlmin = "q=".$tmpqparam.$tmpparmmin; //rows = 0不返回文档个数
            $tmpresultmin = solrRequest($tmpurlmin, "response", "query");
            $minv = $tmpresultmin["docs"][0][$tmpfield];

            $tmprange["rangeinfo"]["value"]["rangevalue"]["value"] = array("start"=>$minv, "end"=>$maxv);
        }
    }
    if($israngeclassify){
        $arg_filtervalue_range_copy = $arg_filtervalue; 
        if($tmprange["rangeinfo"]["type"] == "gap" || $tmprange["rangeinfo"]["type"] == "gapcount"){
            $gtarr = facetrangevalue($tmprange["rangeinfo"]["value"]["rangevalue"]);
        }
        //range分类查询value数组
        $tmpfvarr = array();
        switch($tmprange["rangeinfo"]["type"]){
        case "gap":
            $intervalarr = getRangeintervalArr($gtarr["start"], $gtarr["end"], $tmprange["rangeinfo"]["value"]["gap"]);
            foreach($intervalarr as $ki=>$kitem){
                $tmpfv = array();
                $tmpfv["datatype"] = "value_text_object";
                $tmpfv["value"]["text"] = date("Y-m-d H:i:s", $kitem["start"])."~".date("Y-m-d H:i:s", $kitem["end"]);
                $tmpfv["value"]["value"] = $kitem; 
                $tmpfvarr[] = $tmpfv;
            }
            break;
        case "gapcount":
            $tdiff = $gtarr["end"] - $gtarr["start"];
            $intervalnum = ceil($tdiff / $tmprange["rangeinfo"]["value"]["gapcount"]);
            $tmpstart = $gtarr["start"];
            $tmpend;
            do{
                $tmpfv = array();
                $tmpfv["datatype"] = "value_text_object";
                $tmpfv["value"]["value"]["start"] = $tmpstart; 
                $tmpend = $tmpstart + $intervalnum;
                $tmpfv["value"]["value"]["end"] = $tmpend > $gtarr["end"] ? $gtarr["end"] : $tmpend-1;
                $tmpfv["value"]["text"] = date("Y-m-d H:i:s", $tmpfv["value"]["value"]["start"])."~".date("Y-m-d H:i:s", $tmpfv["value"]["value"]["end"]);
                $tmpfvarr[] = $tmpfv;
                $tmpstart = $tmpend;
            }while($tmpend < $gtarr["end"]);
            break;
        case "gaplist":
            foreach($tmprange["rangeinfo"]["value"] as $key=>$vitem){
                //对应每一个vitem 生成facet查询的start和end
                $gtarr = facetrangevalue($vitem);
                if(isset($gtarr["start"]) && isset($gtarr["end"])){
                    if($gtarr["end"] > $gtarr["start"]){
                        $start = $gtarr["start"];
                        $end = $gtarr["end"];
                    }
                    else{ //博龄字段 
                        $start = $gtarr["end"];
                        $end = $gtarr["start"];
                    }
                    $tmpfv = array();
                    $tmpfv["datatype"] = "value_text_object";
                    $tmpfv["value"]["text"] = $tmprange["tag"]["x"]["value"][$key];
                    $tmpfv["value"]["value"]["start"] = $start; 
                    $tmpfv["value"]["value"]["end"] = $end;
                    $tmpfvarr[] = $tmpfv;
                }
            }
            break;
        default:
            break;
        }

        //重新给filtervalue赋值
        foreach($arg_filtervalue as $ri=>$ritem){
            if($ritem["fieldvalue"]["datatype"] == "array"){
                $arg_filtervalue[$ri]["fieldvalue"]["value"] = $tmpfvarr;
            }
        }
        $arg_filtervalue_copy = $arg_filtervalue;
    }

    $classifyqueryFields = array();//需要查询的字段对象数组
    $classifyqueryFieldValues = array();//需要查询的字段的具体的值
    $classifyqueryFieldName = "";
    if(isset($fieldArr["classifyquery"])){//需要分类查询
        if(empty($fieldArr["classifyquery"]["fieldname"])){//未设置fieldname
            $logger->error(__FILE__." ".__FUNCTION__." classifyquery->fieldname is empty");
            return getErrorOutput(0, "查询字段未设置");
        }
        else{
            $classifyqueryFieldName = $fieldArr["classifyquery"]["fieldname"];
            $classifyqueryFields = getFilterValueItem($fieldArr["classifyquery"]["fieldname"],$arg_filtervalue);
            //特征分类和普通字段 字段名相同, 需要根据 类型array找到分类查询的字段
            $tmpcf = array();
            foreach($classifyqueryFields as $ki => $kitem){
                if($kitem["fieldvalue"]["datatype"] == "array"){
                    $tmpcf[] = $kitem;
                }
            }
            $classifyqueryFields = $tmpcf;
            if(count($classifyqueryFields) != 1 && !$islinkage){
                $logger->error(__FILE__." ".__FUNCTION__." classifyquery field count is 0 classifyqueryFieldName ".var_export($classifyqueryFieldName, true)."");
                return getErrorOutput(0, "查询字段对象个数不为1");
            }
            else{
                $classifyqueryfeature = !empty($classifyqueryFields[0]['isfeature']);//是否分类查询特征分类
                if($islinkage){
                    //联动实例且，分类查询是联动字段，将动态pin的数据先查出来
                    $classqueryresult = getDynamicClassicQueryResult($datas, $fieldArr['filter'][$classifyqueryFieldName]['datatype']);
                    if(isset($classqueryresult['error'])){
                        return $classqueryresult;
                    }
                    for($i=count($classifyqueryFields[0]['fieldvalue']['value'])-1;$i>-1;$i--){
                        if($classifyqueryFields[0]['fieldvalue']['value'][$i]['datatype'] == "dynamic"){
                            array_splice($classifyqueryFields[0]['fieldvalue']['value'], $i, 1);
                        }
                    }
                    if(!empty($classqueryresult)){
                        foreach($classqueryresult as $k => $v){
                            $cqr['datatype'] = $classifyqueryfeature ? "value_text_object" : $fieldArr['filter'][$classifyqueryFieldName]['datatype'];
                            $cqr['value'] = $v;		        				
                            $classifyqueryFields[0]['fieldvalue']['value'][] = $cqr;
                        } 
                    }
                    for($i=0;$i<count($arg_filtervalue);$i++){
                        if($arg_filtervalue[$i]['fieldname'] == $classifyqueryFields[0]['fieldname']){
                            $arg_filtervalue[$i] = $classifyqueryFields[0];
                            break;
                        }
                    }
                }

                $classifyqueryFieldValues = getFilterValue($classifyqueryFields[0]['fieldvalue']);//获取查询字段的值
                if(count($classifyqueryFieldValues) != count($classifyqueryFields[0]['fieldvalue']['value'])){
                    //出现嵌套数组，查询不允许嵌套数组
                    $logger->error(__FILE__." ".__FUNCTION__." classifyquery field has nested");
                    return getErrorOutput(0, "查询字段值非法");
                }
            }
        }
    }
    $isfacetfeature = false;//是否facet特征分类
    if($fieldArr['output']['outputtype'] == 2 && count($fieldArr['facet']['field']) > 0 
        && !empty($fieldArr['facet']['field'][0]['isfeature'])){
            $isfacetfeature = true;
        }
    //使用特征分类查询时或是查关注和查粉丝，修改为联动查询, 目的为了使用bool语法
    $isjoin = false;
    $needbool = array("usersfollower", "usersfriend", "oristatus_username", "oristatus_userid", "repost_username", "repost_userid", "repost_url");
    if(!$islinkage){
        foreach($arg_filtervalue as $key => $value){
            if(!empty($value['isfeature']) || (isset($value["fieldname"]) && in_array($value["fieldname"], $needbool))){
                $isjoin = true;
                break;
            }
        }
    }

    $res = array();
    $emoStringArr = array("emotion", "emoCombin","emoNRN","emoOrganization", "emoTopic", "emoTopicKeyword", "emoTopicCombinWord" ,"ancestor_emotion", "ancestor_emoCombin", "ancestor_emoNRN", "ancestor_emoOrganization","ancestor_emoTopic", "ancestor_emoTopicKeyword", "ancestor_emoTopicCombinWord");
    $emoVTArr = array("emoBusiness","emoAccount", "emoCountry", "emoProvince", "emoCity", "emoDistrict", "ancestor_emoAccount", "ancestor_emoBusiness", "ancestor_emoCountry", "ancestor_emoProvince", "ancestor_emoCity", "ancestor_emoDistrict");
    $classifyqueryCount = count($classifyqueryFieldValues);//需要查询的次数
    if($classifyqueryCount > MAX_CLASSIFYQUERYCOUNT){
        return getErrorOutput(0, "分类查询的值个数超出最大限制".MAX_CLASSIFYQUERYCOUNT);
    }
    //分类查询的查询次数
    $allSqlExecCount = $classifyqueryCount > 0 ? $classifyqueryCount : 1;
    $tmpclassifyqueryCount = $classifyqueryCount; //临时存储执行次数, 执行时增加了递减
    $allexeced = -1;
    $otherexeced = -1;
    if(!empty($fieldArr["classifyquery"]["all"])){ //全部
        $allSqlExecCount++; //增加一次执行次数
        $allexeced = $allSqlExecCount;
    }
    if(!empty($fieldArr["classifyquery"]["other"])){//其他
        $allSqlExecCount++; 
        $otherexeced = $allSqlExecCount;
    }
    if(!empty($fieldArr["classifytype"]) && $fieldArr["classifytype"] == "all"){ //drilldown用到
        $allexeced = 1;
    }
    if(!empty($fieldArr["classifytype"]) && $fieldArr["classifytype"] == "other"){ //drilldown
        $otherexeced = 1;
    }
    for($__i=0; $__i < $allSqlExecCount; $__i++){
        $categoryname = "";
        $categoryvalue = "";
        if($classifyqueryCount == 0 && ($allexeced >0 || $otherexeced>0)){ //"全部" 和 "其他"  的查询
            //分类查询"全部","其他" drilldown, classifyquery为null, 需要找出对应分类查询字段
            if($classifyqueryFieldName == ""){
                foreach($arg_filtervalue_copy as $akey=>$avalue){
                    if($avalue["fieldvalue"]["datatype"] == "array"){
                        $classifyqueryFieldName = $avalue["fieldname"];
                    }
                }
            }
            $classifyqueryFieldLimit; //分类查询字段的limit值
            foreach($fieldArr["filter"] as $fkey=>$fvalue){
                if($fkey == $classifyqueryFieldName){
                    $classifyqueryFieldLimit = $fvalue["limit"];
                }
            }
            //分类查询的"全部": 去除分类查询字段的filtervalue进行查询,有limit值时, limit之间逻辑关系为or, 与其他字段进行and查询 
            //当分类查询为all 或是drilldown时classifytype 为all
            if((!empty($fieldArr["classifyquery"]["all"]) || ((!empty($fieldArr["classifytype"]) && $fieldArr["classifytype"] == "all"))) && ($allexeced -1 == $__i)){
                $allexeced = 0;
                $categoryname = "全部";
                $categoryvalue = "all";
                //删除对应的分类查询字段
                $datatype = "";
                $deletekey;
                foreach($arg_filtervalue_copy as $akey=>$avalue){
                    if($avalue["fieldvalue"]["datatype"] == "array"){
                        $deletekey = $akey;
                        $datatype = $avalue["fieldvalue"]["value"][0]["datatype"];
                        //array_splice($arg_filtervalue,$akey,1); //不删除filteralue,删除对应的filterrealtion

                    }
                }
                $oldfvlen = count($arg_filtervalue);
                $rel = deleteFilterrealtionItem($arg_filterrelation, $deletekey);
                if(filterHasLimit($classifyqueryFieldLimit)){
                    foreach($classifyqueryFieldLimit as $ck=>$cvalue){
                        $tmpdatatype = $datatype;
                        if($datatype == "classifyrange"){ //时间分类查询limit 添加filtervlaue
                            $classifyqueryFieldName = isset($cvalue["name"]) ? $cvalue["name"] : "createdtime";
                            if($cvalue["type"] == "time_dynamic_range"){
                                $classifyqueryFieldName = "untiltime";
                            }
                            $tmpdatatype = $cvalue["type"];
                        }
                        $arg_filtervalue[] = createFiltervalue($classifyqueryFieldName, $tmpdatatype, $cvalue["value"], 1, NULL, 0);
                    }
                }
                //生成filterrelation
                //$arg_filterrelation = $fieldArr["filterrelation"];
                $newfvlen = count($arg_filtervalue);
                //生成filterrelation
                if($rel != NULL && isset($rel["opt"])){
                    if($rel["opt"] == "and"){
                        $limitrel = array();
                        $limitrel["opt"] = "or";
                        $limitrel["filterlist"] = array();
                        $limitrel["fields"] = array();
                        for($i = $oldfvlen; $i < $newfvlen; $i++){
                            $limitrel["fields"][] = $i;
                        }
                        $rel["filterlist"][] = $limitrel;
                    }
                    else{
                        for($i = $oldfvlen; $i < $newfvlen; $i++){
                            $rel["fields"][] = $i;
                        }
                    }
                    $arg_filterrelation = $rel;
                }
                else{
                    $fieldArr["filtervalue"] = $arg_filtervalue;
                    $re = initFilterRelation($fieldArr);
                    $arg_filterrelation = $re;
                }
            }
            //分类查询的"其他", 分类查询字段的fieldvalue, exclude取反使用and逻辑进行查询, 有limit时, 已添加的分类查询字段 exclue取反 使用and查询, 对未添加的limit中值之间使用or逻辑, 和已有字段进行and查询 
            if((!empty($fieldArr["classifyquery"]["other"]) || (!empty($fieldArr["classifytype"]) && $fieldArr["classifytype"] == "other")) && ($otherexeced -1 == $__i)){//其他
                $otherexeced = 0;
                $categoryname = "其他";
                $categoryvalue = "other";
                $othervalue = array();
                $otherlimitvalue = array();
                if(filterHasLimit($classifyqueryFieldLimit) && !isset($arg_filtervalue_range_copy)){
                    foreach($classifyqueryFieldLimit as $ck=>$cvalue){
                        $hasvalue = false;
                        $datatype = "";
                        $exclude = 0; //是否包含
                        $isfeature = 0;
                        foreach($arg_filtervalue_copy as $akey=>$avalue){
                            $isfeature = $avalue["isfeature"];
                            if($avalue["fieldvalue"]["datatype"] == "array"){
                                foreach($avalue["fieldvalue"]["value"] as $vk=>$vv){
                                    $datatype = $vv["datatype"];
                                    $exclude = $vv["exclude"] == 1 ? 0 : 1;
                                    if($datatype == "value_text_object"){
                                        if($cvalue["value"]["value"] == $vv["value"]["value"]){
                                            $hasvalue = true;
                                            break;
                                        }
                                    }
                                    else{
                                        if($cvalue["value"] == $vv["value"]){
                                            $hasvalue = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        $fromlimt = 1; //是否从limit中取值
                        if($hasvalue){
                            $fromlimt = 0;
                            $othervalue[] = createFiltervalue($classifyqueryFieldName, $datatype, $cvalue["value"], $fromlimt, $isfeature, $exclude);
                        }
                        else{
                            $otherlimitvalue[] = createFiltervalue($classifyqueryFieldName, $datatype, $cvalue["value"], $fromlimt, $isfeature, 0); 
                        }
                    }
                }
                else{
                    if(isset($arg_filtervalue_range_copy)){
                        foreach($arg_filtervalue_range_copy as $rk=>$rvalue){
                            if($rvalue["fieldvalue"]["datatype"] == "array"){
                                if($rvalue["fieldvalue"]["value"][0]["datatype"] == "classifyrange"){
                                    $trv = $rvalue["fieldvalue"]["value"][0]["value"]; 
                                    //获取rangelimit
                                    $classifyqueryRangeLimit = array();
                                    foreach($fieldArr["filter"] as $fi=>$fitem){
                                        if($classifyqueryFieldName == "createdtime"){
                                            if($fi == "createdtime" || $fi == "nearlytime" || $fi == "beforetime" || $fi == "untiltime"){
                                                if(filterHasLimit($fitem["limit"])){
                                                    $classifyqueryRangeLimit = $fitem["limit"];
                                                }
                                            }
                                        }
                                        else if($fi == $classifyqueryFieldName){
                                            if(filterHasLimit($fitem["limit"])){
                                                $classifyqueryRangeLimit = $fitem["limit"];
                                            }
                                        }
                                    }
                                    $tstart = null;
                                    $tend = null;
                                    if(count($classifyqueryRangeLimit) > 0){
                                        if($classifyqueryRangeLimit[0]["type"] == "time_dynamic_range" || $classifyqueryRangeLimit[0]["type"] == "time_dynamic_state"){
                                            $limitarr = facetrangevalue($classifyqueryRangeLimit[0]);
                                            $tstart = $limitarr["start"];
                                            $tend = $limitarr["end"];
                                        }
                                        else{
                                            $tstart = $classifyqueryRangeLimit[0]["value"]["minvalue"];
                                            $tend = $classifyqueryRangeLimit[0]["value"]["maxvalue"];
                                        }
                                    }
                                    if($trv["rangeinfo"]["type"] == "gap" || $trv["rangeinfo"]["type"] == "gapcount"){
                                        $gtarr = facetrangevalue($tmprange["rangeinfo"]["value"]["rangevalue"]);
                                        //左其他
                                        $l["start"] = $tstart;
                                        if($tstart != null){
                                            $lend = $gtarr["start"] > $tstart ? $gtarr["start"]-1 : $tstart;
                                        }
                                        else{
                                            $lend = $gtarr["start"] - 1;
                                        }
                                        $l["end"] = $lend;
                                        $othervalue[] = createFiltervalue($classifyqueryFieldName, "range", $l, 0);
                                        //右其他
                                        if($tend != null){
                                            $rstart = $tend > $gtarr["end"] ? $gtarr["end"]+1 : $tend;
                                        }
                                        else{
                                            $rstart = $gtarr["end"]+1;
                                        }
                                        $r["start"] = $rstart;
                                        $r["end"] = $tend;
                                        $othervalue[] = createFiltervalue($classifyqueryFieldName, "range", $r, 0);
                                    }
                                    else{ //分段查询gaplist
                                        $listarr = array();
                                        foreach($trv["rangeinfo"]["value"] as $ti=>$titem){
                                            $titemarr = facetrangevalue($titem);
                                            if(isset($titemarr["start"]) && isset($titemarr["end"])){
                                                if($titemarr["end"] > $titemarr["start"]){
                                                    $start = $titemarr["start"];
                                                    $end = $titemarr["end"];
                                                }
                                                else{ //博龄字段 
                                                    $start = $titemarr["end"];
                                                    $end = $titemarr["start"];
                                                }
                                                $tmplist = array();
                                                $tmplist["start"] = $start; 
                                                $tmplist["end"] = $end;
                                                $listarr[] = $tmplist;
                                            }
                                        }
                                        foreach($listarr as $li=>$litem){
                                            //左其他
                                            $l["start"] = $tstart;
                                            if($tstart != null){
                                                $lend = $litem["start"] > $tstart ? $litem["start"]-1 : $tstart;
                                            }
                                            else{
                                                $lend = $litem["start"]-1;
                                            }
                                            $l["end"] = $lend;
                                            $othervalue[] = createFiltervalue($classifyqueryFieldName, "range", $l, 0);
                                            if(isset($listarr[$li+1])){
                                                $next = $listarr[$li+1]; 
                                                if($next["start"] > $litem["end"]){
                                                    $m["start"] = $litem["end"] + 1;
                                                    $m["end"] = $next["start"] - 1;
                                                    $othervalue[] = createFiltervalue($classifyqueryFieldName, "range", $m, 0);
                                                }
                                            }
                                            else{
                                                if($tend != null){
                                                    $rstart = $tend > $litem["end"] ? $litem["end"]+1 : $tend;
                                                }
                                                else{
                                                    $rstart = $litem["end"]+1;
                                                }
                                                $r["start"] = $rstart;
                                                $r["end"] = $tend;
                                                $othervalue[] = createFiltervalue($classifyqueryFieldName, "range", $r, 0);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else{
                        foreach($arg_filtervalue_copy as $akey=>$avalue){
                            if($avalue["fieldvalue"]["datatype"] == "array"){
                                foreach($avalue["fieldvalue"]["value"] as $vk=>$vv){
                                    if(isset($vv["exclude"])){
                                        $exclude = $vv["exclude"] == 1 ? 0 : 1;
                                    }
                                    else{
                                        $exclude = 1;
                                    }
                                    $othervalue[] = createFiltervalue($classifyqueryFieldName, $vv["datatype"], $vv["value"], 0, $avalue["isfeature"], $exclude);
                                }
                            }
                        }
                    }
                }
                //删除对应的分类查询字段
                $otherdelkey;
                foreach($arg_filtervalue_copy as $akey=>$avalue){
                    if($avalue["fieldvalue"]["datatype"] == "array"){
                        $otherdelkey = $akey;
                        //array_splice($arg_filtervalue_copy,$akey,1); //不删除filtervalue,删filterrelation对应的索引, 
                    }
                }
                $rel = deleteFilterrealtionItem($fieldArr["filterrelation"], $otherdelkey);
                //添加新的查询字段
                $oldfvlen = count($arg_filtervalue_copy);
                $arg_filtervalue = array_merge($arg_filtervalue_copy, $othervalue, $otherlimitvalue);
                //生成filterrelation
                //$arg_filterrelation = $fieldArr["filterrelation"];
                $newfvlen = count($arg_filtervalue);
                if($rel != NULL && isset($rel["opt"]) && !isset($arg_filtervalue_range_copy)){
                    if($rel["opt"] == "or"){
                        $newfilterrelation = $rel;
                        $rel = array();
                        $rel["opt"] = "and";
                        $rel["filterlist"][] = $newfilterrelation;
                        $rel["fields"] = array();
                    }
                    for($i = $oldfvlen; $i < $newfvlen-count($otherlimitvalue); $i++){
                        $rel["fields"][] = $i;
                    }
                    if($rel["opt"] == "and"){
                        $otherlimit = array(); 
                        $otherlimit["opt"] = "or";
                        $otherlimit["filterlist"][] = array();
                        $otherlimit["fields"] = array();
                        for($i; $i < $newfvlen; $i++){
                            $otherlimit["fields"][] = $i;
                        }
                        $rel["filterlist"][] = $otherlimit;
                    }
                    $arg_filterrelation = $rel;
                }
                else if($rel != NULL && isset($rel["opt"]) && isset($arg_filtervalue_range_copy)){
                    $tmp["filtervalue"] = $othervalue;
                    $addlimitrelation = initFilterRelation($tmp, $oldfvlen);
                    if($rel["opt"] == "or"){
                        $arg_newfilterrelation = array();
                        $arg_newfilterrelation["opt"] = "and";
                        $arg_newfilterrelation["filterlist"] = array();
                        $arg_newfilterrelation["filterlist"][] = $rel;
                        $fr["opt"] = "and";
                        $fr["filterlist"] = array();
                        $fr["filterlist"][] = $addlimitrelation;
                        $fr["fields"] = array();
                        $arg_newfilterrelation["filterlist"][] = $fr;
                        $arg_newfilterrelation["fields"] = array();
                        $rel = $arg_newfilterrelation;
                    }
                    else{
                        $rel["filterlist"][] = $addlimitrelation;
                    }
                    $arg_filterrelation = $rel;
                }
                else{
                    $fieldArr["filtervalue"] = $arg_filtervalue;
                    $re = initFilterRelation($fieldArr);
                    $arg_filterrelation = $re;
                }
            }//other end
        }
        $pinrelation = ($islinkage && !empty($datas)) ? $datas['pinrelation'] : array();

        $qparam = filter_where($arg_filterrelation, $arg_filtervalue, $classifyqueryFieldName, ($islinkage || $isjoin), 
        ($islinkage && !empty($datas)) ? $datas['elements'] : null, 
        $pinrelation, 
        ($islinkage && !empty($datas)) ? $datas['render']['elementid'] : null, $fieldArr["modelid"]);
        if($qparam==""){
            if($tmpclassifyqueryCount > 0){
                if(!empty($fieldArr["classifyquery"]["all"])){
                    $qparam = getDefaultQparam($fieldArr["modelid"]);                
                }
                else{
                    $logger->error(__FILE__." ".__FUNCTION__." query url is empty:");
                    continue;
                }
            }
            else{
                //$qparam = "*:*";                
                $qparam = getDefaultQparam($fieldArr["modelid"]);                
            }
        }else{//begin:add by zuo:2016-12-14
            if(is_array($qparam)&&isset($qparam["featureclasserror"])){
                return getErrorOutput(0, "请重新选择一下此特征分类:".$qparam["featureclasserror"]);
        }
        }//end:add by zuo:2016-12-14
        $url = "q=".$qparam.$param; //rows = 0不返回文档个数
        $needpinyin = isset($fieldArr['needpinyin']) ? $fieldArr['needpinyin']  : false;
        $result = solrRequest($url, $field, $facetttype, SOLR_URL_SELECT, $needpinyin);
        if(isset($result['error'])){
            return $result;
        }
        //对查询的结果进行修改, article_taginfox字段修改为article_taginfo字段
        $arg_filtervaluearry = array();
        foreach($arg_filtervalue as $i=>$item){
          if(isset($item['isfeature']) && $item['isfeature'] == 1){
              if($item['fieldname']=="keyword" || $item['fieldname']=="searchword"){
                  $arg_filtervaluearry[]=$item;
              }

          }
        };
        if(!empty($result['docs']) && !empty($arg_filtervaluearry)){
            //$userid = $_SESSION['user']->getuserid();
            //$userfield = getUserArticleTaginfoField($userid);
            foreach($result['docs'] as $pi=>$pitem){
              /*if(isset($pitem[$userfield])){
                    $result['docs'][$pi]['article_taginfo'] = $pitem[$userfield];
                }*/
                $arg_filter_guid=$result['docs'][$pi]["guid"];
                $arg_filter_result=searchTagInfoById($arg_filtervaluearry, $arg_filter_guid);
                $result['docs'][$pi]["tag_info"]= $arg_filter_result;
            }

        }   
        if(!empty($result['docs'])){
            $needqarr = array();
            //begin:如果没有特征分类，用户也可以打文章标签 by zuo:2016-8-31
	        if(isset($_SESSION) && !empty($_SESSION)){
                $userid = $_SESSION['user']->getuserid();
                $userfield = getUserArticleTaginfoField($userid);
            }
            foreach($result['docs'] as $pi=>$pitem){
                if(isset($pitem[$userfield])){
                    $result['docs'][$pi]['article_taginfo'] = $pitem[$userfield];
                }
                if(isset($pitem['paragraphid']) && $pitem['paragraphid'] > 0){
                    //$p_guid = $pitem['guid'];
                    //$article_guid = substr($p_guid, 0, strrpos($p_guid, "_"));
                    if(!empty($pitem['docguid'])){
                        $article_guid = $pitem['docguid'];
                        $qguid = "guid:".$article_guid."";
                        if(!in_array($qguid, $needqarr)){
                            $needqarr[] = $qguid;
                        }
                    }
                }
            }
            if(count($needqarr)>0){
                $qp = implode("+OR+", $needqarr);
                //select
                $arg_select_field = $fieldArr["select"]["value"];
                $selectfield = implode(",", $arg_select_field);
                $select = "&fl=".$selectfield;
                //rows
                $arg_output_data_limit= $fieldArr["output"]["data_limit"];
                $start = "&start=0";
                $arg_output_count = $fieldArr["output"]["count"];
                $rows = "&rows=".$arg_output_count;
                //$surl = $selectUrl."?q=".$qp.$select.$start.$rows."&facet=off";
                //当时drilldown查询时需要添加查询参数usecache为保持facet后的drilldown结果一致
                $drilldownparam = "";
                if(isset($fieldArr["isdrilldown"]) && $fieldArr["isdrilldown"]){
                    $drilldownparam = "&usecache=true";
                }
                $surl = "q=".$qp.$select.$start.$rows."&facet=off".$drilldownparam;
                $para_result = solrRequest($surl, $field, $facetttype);
                if(isset($para_result['error'])){
                    $logger->error(__FILE__.__LINE__." query by paragraphid pre url: ".$url." pre result:".var_export($result, true));
                    return $para_result;
                }
                $guidarr = array();
                //返回结果中的段落替换为文章
                foreach($result['docs'] as $pi=>$pitem){
                    if(isset($pitem['paragraphid']) && $pitem['paragraphid'] > 0){
                        $p_guid = $pitem['guid'];
                        //$article_guid = substr($p_guid, 0, strrpos($p_guid, "_"));
                        $article_guid = $pitem['docguid'];
                        if(!in_array($article_guid, $guidarr)){
                            $guidarr[] = $article_guid;
                            foreach($para_result['docs'] as $ai=>$aitem){
                                if($aitem['guid'] == $article_guid){
                                    $result['docs'][$pi] = $aitem;
                                    $result['docs'][$pi]['paragraphid'] = array();
                                    $result['docs'][$pi]['paragraphid'][] = $pitem['paragraphid'];
                                    break;
                                }
                            }
                        }
                        else{
                            foreach($result['docs'] as $si=>$sitem){
                                if($sitem['guid'] == $article_guid){
                                    if(!is_array($result['docs'][$si]['paragraphid'])){
                                        $result['docs'][$si]['paragraphid'] = array();
                                    }
                                    $result['docs'][$si]['paragraphid'][] = $pitem['paragraphid'];
                                    break;
                                }
                            }
                            unset($result['docs'][$pi]);
                        }
                    }
                }
                foreach($result['docs'] as $rdi => $rditem){
                    if(isset($rditem['text'])){
                        //把对应段落以黑体显示
                        $tarr = array();
                        $pattern = "/<br>|<br\/>|<BR>|<BR\/>/";
                        $paragraphs = preg_split($pattern , $rditem['text'][0]);
                        if(isset($rditem['paragraphid']) && is_array($rditem['paragraphid'])){
                            foreach($paragraphs as $pai=>$paitem){
                                $pnum = $pai+1;
                                if(in_array($pnum, $rditem['paragraphid'])){
                                    $tarr[] = "<B>".$paitem."</B>";
                                }
                                else{
                                    $tarr[] = $paitem;
                                }
                            }
                        }
                        if(!empty($tarr)){
                            $tstr = implode("<BR/>", $tarr);
                            $result['docs'][$rdi]['text'][0] = $tstr;
                        }
                    }
                }
            }
        }
        //对查询结果中有引用的,进行二次查询,查出对应引用
        $logger->debug(__FILE__.__LINE__." query quote ");
        if(!empty($result['docs'])){
            $needqarr = array();
            //quote联动做不到,quote_father_guid没有
            foreach($result['docs'] as $qi=>$qitem){
                if(isset($qitem['father_floor']) || isset($qitem['quote_father_mid'])){
                    if(isset($qitem['father_floor'])){
                        if(isset($qitem['original_url'])){
                            $qguid = "(original_url:".solrEsc($qitem['original_url'])."+AND+floor:".$qitem['father_floor']."+AND+paragraphid:0)";
                        }
                        else if(isset($qitem['retweeted_guid'])){
                            $qguid = "(guid:".$qitem['retweeted_guid']."+AND+floor:".$qitem['father_floor']."+AND+paragraphid:0)";
                        }
                    }
                    else if(isset($qitem['quote_father_mid'])){
                        $qguid = "(source_host:".$qitem['source_host']."+AND+mid:".$qitem['quote_father_mid'].")";
                    }
                    if(!in_array($qguid, $needqarr)){
                        $needqarr[] = $qguid;
                    }
                }
            }
            if(count($needqarr)>0){
                $qp = implode("+OR+", $needqarr);
                //select
                $arg_select_field = $fieldArr["select"]["value"];
                $selectfield = implode(",", $arg_select_field);
                $select = "&fl=".$selectfield;
                //rows
                $arg_output_data_limit= $fieldArr["output"]["data_limit"];
                $start = "&start=0";
                $arg_output_count = $fieldArr["output"]["count"];
                $rows = "&rows=".$arg_output_count;
                //$surl = $selectUrl."?q=".$qp.$select.$start.$rows."&facet=off";
                //当时drilldown查询时需要添加查询参数usecache为保持facet后的drilldown结果一致
                $drilldownparam = "";
                if(isset($fieldArr["isdrilldown"]) && $fieldArr["isdrilldown"]){
                    $drilldownparam = "&usecache=true";
                }
                $surl = "q=".$qp.$select.$start.$rows."&facet=off".$drilldownparam;
                $quto_result = solrRequest($surl, $field, $facetttype);
                if(isset($quto_result['error'])){
                    $logger->error(__FILE__.__LINE__." query by quote pre url: ".$url." pre result:".var_export($result, true));
                    return $quto_result;
                }
                foreach($result["docs"] as $vi=>$vitem){
                    foreach($quto_result["docs"] as $ri=>$ritem){
                        if((isset($vitem["father_floor"]) && !empty($vitem["father_floor"])) || (isset($vitem['quote_father_mid']) && !empty($vitem['quote_father_mid']))){
                            $equalflag = false;
                            if(isset($vitem["father_floor"])){
                                if($ritem['original_url'] == $vitem['original_url'] && $ritem['floor'] == $vitem['father_floor'] && $ritem['paragraphid'] == 0){
                                    $equalflag = true;
                                }
                            }
                            else if(isset($vitem['quote_father_mid'])){
                                if($ritem['source_host'] == $vitem['source_host'] && $ritem['mid'] == $vitem['quote_father_mid']){
                                    $equalflag = true;
                                }
                            }
                            if($equalflag){
                                foreach($ritem as $ti=>$titem){
                                    $result["docs"][$vi]["quote_".$ti] = $titem;
                                }
                            }
                        }
                    }
                }
            }
        }
        //对查询的结果中转发,进行二次查询,查出对应的原创
        $logger->debug(__FILE__.__LINE__." query original ".empty($fieldArr["returnoriginal"]));
        if(isset($fieldArr["returnoriginal"]) && $fieldArr["returnoriginal"]){
            $qArr = array();
            if(!empty($result["docs"])){
                foreach($result["docs"] as $vi=>$vitem){
                    if(isset($vitem["content_type"]) && ($vitem["content_type"] == 1 || $vitem["content_type"] == 2)){
                        if(isset($vitem["sourceid"]) || isset($vitem["source_host"])){
                            if(isset($vitem["sourceid"])){
                                $sitesource = $vitem["sourceid"];
                                $qsource = "sourceid:".$sitesource."";
                            }
                            else if(isset($vitem["source_host"])){
                                $sitesource = $vitem["source_host"];
                                $qsource = "source_host:".$sitesource."";
                            }
                            if(isset($vitem["retweeted_status"]) && !empty($vitem["retweeted_status"])){
                                $key = $sitesource."_".$vitem["retweeted_status"];
                                $qArr[$key] = "(".$qsource."+AND+id:".$vitem["retweeted_status"].")";
                            }
                            else if(isset($vitem["retweeted_mid"]) && !empty($vitem["retweeted_mid"])){
                                $key = $sitesource."_".$vitem["retweeted_mid"];
                                $qArr[$key] = "(".$qsource."+AND+mid:".$vitem["retweeted_mid"].")";
                            }
                        }
                        else if(isset($vitem["retweeted_guid"]) && !empty($vitem["retweeted_guid"])){
                            $qArr[$vitem["retweeted_guid"]] = "guid:".$vitem["retweeted_guid"]."";
                        }
                    }
                }
            }
            if(count($qArr) > 0){
                $qp = implode("+OR+", $qArr);
                //select
                $arg_select_field = $fieldArr["select"]["value"];
                $selectfield = implode(",", $arg_select_field);
                $select = "&fl=".$selectfield;
                //rows
                $arg_output_data_limit= $fieldArr["output"]["data_limit"];
                $start = "&start=0";
                $arg_output_count = $fieldArr["output"]["count"];
                $rows = "&rows=".$arg_output_count;
                //$surl = $selectUrl."?q=".$qp.$select.$start.$rows."&facet=off";
                //当时drilldown查询时需要添加查询参数usecache为保持facet后的drilldown结果一致
                $drilldownparam = "";
                if(isset($fieldArr["isdrilldown"]) && $fieldArr["isdrilldown"]){
                    $drilldownparam = "&usecache=true";
                }
                $surl = "q=".$qp.$select.$start.$rows."&facet=off".$drilldownparam;
                $repost_result = solrRequest($surl, $field, $facetttype);
                if(isset($repost_result['error'])){
                    $logger->error(__FILE__.__LINE__." pre url: ".$url." pre result:".var_export($result, true));
                    return $repost_result;
                }
                foreach($result["docs"] as $vi=>$vitem){
                    foreach($repost_result["docs"] as $ri=>$ritem){
                        $sourceflag = false;
                        if(isset($vitem["sourceid"])){
                            if(isset($ritem["sourceid"]) && $vitem["sourceid"] == $ritem["sourceid"]){
                                $sourceflag = true;
                            }
                        }
                        else if(isset($vitem["source_host"])){
                            if(isset($ritem["source_host"]) && $vitem["source_host"] == $ritem["source_host"]){
                                $sourceflag = true;
                            }
                        }
                        if(isset($vitem["retweeted_status"])  && !empty($vitem["retweeted_status"]) && $sourceflag){
                            if($vitem["retweeted_status"] == $ritem["id"]){
                                foreach($ritem as $ti=>$titem){
                                    $result["docs"][$vi]["retweeted_".$ti] = $titem;
                                }
                            }
                        }
                        else if(isset($vitem["retweeted_mid"]) && !empty($vitem["retweeted_mid"]) && $sourceflag){
                            if($vitem["retweeted_mid"] == $ritem["mid"]){
                                foreach($ritem as $ti=>$titem){
                                    $result["docs"][$vi]["retweeted_".$ti] = $titem;
                                }
                            }
                        }
                        else if(isset($vitem["retweeted_guid"]) && !empty($vitem["retweeted_guid"])){
                            if($vitem["retweeted_guid"] == $ritem["guid"]){
                                foreach($ritem as $ti=>$titem){
                                    $result["docs"][$vi]["retweeted_".$ti] = $titem;
                                }
                            }
                        }
                    }
                }
            }
        }
        //对查询的结果中的数据判断是否有转发，并查出
        $logger->debug(__FILE__.__LINE__." query repost ".empty($fieldArr["returnrepost"]));
        if(isset($fieldArr["returnrepost"]) && $fieldArr["returnrepost"]){
            if(!empty($result["docs"])){
                foreach($result["docs"] as $vi=>$vitem){
                    $qp = "";
                    if(isset($vitem["reposts_count"]) && $vitem["reposts_count"] > 0){
                        if(isset($vitem["guid"]) && !empty($vitem["guid"])){
                            $qp = "retweeted_guid:".$vitem["guid"]."+AND+content_type:1";
                        }
                        else if(isset($vitem["sourceid"]) || isset($vitem["source_host"])){
                            if(isset($vitem["sourceid"])){
                                $sitesource = $vitem["sourceid"];
                                $qsource = "sourceid:".$sitesource."";
                            }
                            else if(isset($vitem["source_host"])){
                                $sitesource = $vitem["source_host"];
                                $qsource = "source_host:".$sitesource."";
                            }
                            if(isset($vitem["id"]) && !empty($vitem["id"])){
                                $qp = "(".$qsource."+AND+retweeted_status:".$vitem["id"]."+AND+content_type:1)";
                            }
                            else if(isset($vitem["mid"]) && !empty($vitem["mid"])){
                                $qp = "(".$qsource."+AND+retweeted_mid:".$vitem["mid"]."+AND+content_type:1)";
                            }
                        }
                    }
                    //每一篇文章发送一个请求，获取转发
                    if($qp != ""){
                        //select
                        $arg_select_field = $fieldArr["select"]["value"];
                        $selectfield = implode(",", $arg_select_field);
                        $select = "&fl=".$selectfield;
                        //rows
                        $arg_output_data_limit= $fieldArr["output"]["data_limit"];
                        $start = "&start=0";
                        $arg_output_count = isset($fieldArr["output"]["repostpagesize"]) ? $fieldArr["output"]["repostpagesize"] : $fieldArr["output"]["count"];
                        $rows = "&rows=".$arg_output_count;
                        //$surl = $selectUrl."?q=".$qp.$select.$start.$rows."&facet=off";
                        //当时drilldown查询时需要添加查询参数usecache为保持facet后的drilldown结果一致
                        $drilldownparam = "";
                        if(isset($fieldArr["isdrilldown"]) && $fieldArr["isdrilldown"]){
                            $drilldownparam = "&usecache=true";
                        }
                        //当页面提交orderby字段时, 按照对应字段排序 默认按照 solr文档顺序
                        if(!empty($fieldArr["output"]["orderby"])){
                            $arg_output_orderby = "created_at";
                            $arg_output_orderby = $fieldArr["output"]["orderby"];
                            $arg_output_ordertype = $fieldArr["output"]["ordertype"];
                            $sort = "&sort=".$arg_output_orderby."+".$arg_output_ordertype;
                        }
                        $surl = "q=".$qp.$select.$start.$rows."&facet=off".$drilldownparam.$sort;
                        $repost_result = solrRequest($surl, $field, $facetttype);
                        if(isset($repost_result['error'])){
                            $logger->error(__FILE__.__LINE__." pre url: ".$url." pre result:".var_export($result, true));
                            return $repost_result;
                        }
                        $result["docs"][$vi]["reposts"] = $repost_result["docs"]; 
                        $result["docs"][$vi]["crawl_reposts_count"] = $repost_result["numFound"]; 
                        $result["docs"][$vi]["repostparam"] = $qp.$select.$drilldownparam.$sort; 
                    }
                }
            }
        }
        //对查询的结果中的数据判断是否有评论，并查出
        $logger->debug(__FILE__.__LINE__." query comment".empty($fieldArr["returncomment"]));
        if(isset($fieldArr["returncomment"]) && $fieldArr["returncomment"]){
            if(!empty($result["docs"])){
                foreach($result["docs"] as $vi=>$vitem){
                    $qp = "";
                    if(isset($vitem["comments_count"]) && $vitem["comments_count"] > 0){
                        if(isset($vitem["guid"]) && !empty($vitem["guid"])){
                            $qp = "retweeted_guid:".$vitem["guid"]."+AND+content_type:2";
                        }
                        else if(isset($vitem["sourceid"]) || isset($vitem["source_host"])){
                            if(isset($vitem["sourceid"])){
                                $sitesource = $vitem["sourceid"];
                                $qsource = "sourceid:".$sitesource."";
                            }
                            else if(isset($vitem["source_host"])){
                                $sitesource = $vitem["source_host"];
                                $qsource = "source_host:".$sitesource."";
                            }
                            if(isset($vitem["id"]) && !empty($vitem["id"])){
                                $qp = "(".$qsource."+AND+retweeted_status:".$vitem["id"]."+AND+content_type:2)";
                            }
                            else if(isset($vitem["mid"]) && !empty($vitem["mid"])){
                                $qp = "(".$qsource."+AND+retweeted_mid:".$vitem["mid"]."+AND+content_type:2)";
                            }
                        }
                    }
                    //每一篇文章发送一个请求，获取评论
                    if($qp != ""){
                        //select
                        $arg_select_field = $fieldArr["select"]["value"];
                        $selectfield = implode(",", $arg_select_field);
                        $select = "&fl=".$selectfield;
                        //rows
                        $arg_output_data_limit= $fieldArr["output"]["data_limit"];
                        $start = "&start=0";
                        $arg_output_count = isset($fieldArr["output"]["commentpagesize"]) ? $fieldArr["output"]["commentpagesize"] : $fieldArr["output"]["count"];
                        $rows = "&rows=".$arg_output_count;
                        //$surl = $selectUrl."?q=".$qp.$select.$start.$rows."&facet=off";
                        //当时drilldown查询时需要添加查询参数usecache为保持facet后的drilldown结果一致
                        $drilldownparam = "";
                        if(isset($fieldArr["isdrilldown"]) && $fieldArr["isdrilldown"]){
                            $drilldownparam = "&usecache=true";
                        }
                        //当页面提交orderby字段时, 按照对应字段排序 默认按照 solr文档顺序
                        if(!empty($fieldArr["output"]["orderby"])){
                            $arg_output_orderby = "created_at";
                            $arg_output_orderby = $fieldArr["output"]["orderby"];
                            $arg_output_ordertype = $fieldArr["output"]["ordertype"];
                            $sort = "&sort=".$arg_output_orderby."+".$arg_output_ordertype;
                        }
                        $surl = "q=".$qp.$select.$start.$rows."&facet=off".$drilldownparam.$sort;
                        $comment_result = solrRequest($surl, $field, $facetttype);
                        if(isset($comment_result['error'])){
                            $logger->error(__FILE__.__LINE__." pre url: ".$url." pre result:".var_export($result, true));
                            return $comment_result;
                        }
                        $result["docs"][$vi]["comments"] = $comment_result["docs"]; 
                        $result["docs"][$vi]["crawl_comments_count"] = $comment_result["numFound"]; 
                        $result["docs"][$vi]["commentparam"] = $qp.$select.$drilldownparam.$sort; 
                    }
                }
            }
        }
        //range类型(不包含时间) 添加alias
        //当facet字段为特征分类时添加 alias
        //处理 facet filter 包含 显示其他 alias(用户填写的)
        if(isset($result["countList"]) && ($facetttype == "range" || $facetttype == "field")){
            $datalist = $result["countList"];
            $datalistlen = count($datalist);
            $rangeFacetfield = array("reposts_count","direct_comments_count","comments_count" ,"praises_count","satisfaction", "godRepPer", "midRepPer", "wosRepPer", "godRepNum", "midRepNum", "wosRepNum", "apdRepNum", "showPicNum", "cmtStarLevel","purchDate","proOriPrice","proCurPrice","proPriPrice","stockNum","salesNumMonth","operateTime","compDesMatch","logisticsScore","serviceScore","direct_reposts_count" ,"total_reposts_count","total_reach_count","followers_count","level","users_followers_count","users_replys_count","users_recommended_count","users_friends_count", "users_statuses_count", "users_favourites_count", "users_bi_followers_count" ,"created_year", "created_month", "created_day", "created_hour","created_weekday","floor","paragraphid","trample_count","question_id","answer_id","child_post_id","question_father_id","answer_father_id","read_count","users_level");
            foreach($datalist as $key=>$value){
                if($facetfield == "register_time" || $facetfield == "users_created_at"){
                    $rangeinfo = $fieldArr["facet"]["range"][0]["rangeinfo"];
                    if(!isset($rangeinfo)){
                        foreach($arg_filtervalue as $k=>$v){
                            if($v["fieldname"] == "registertime"){
                                $o = getFilterValue($v["fieldvalue"]);
                            }
                        }
                        if(isset($o["gap"])){
                            $gap = $o["gap"];
                        }
                        else{
                            $gap = "day";
                        }
                    }
                    else if(isset($rangeinfo["type"])){
                        if($rangeinfo["type"] == "gap"){
                            $tmpgap = $rangeinfo["value"]["gap"]; 
                            $posyear = strpos($tmpgap, "year");
                            $posmonth = strpos($tmpgap, "month");
                            $posday = strpos($tmpgap, "day");
                            if($posyear !== false){
                                $gap = "year";
                            }
                            else if($posmonth !== false){
                                $gap = "month";
                            }
                            else if($posday !== false){
                                $gap = "day";
                            }
                        }
                        else{
                            $gap = "day";
                        }
                    }
                    else{
                        $gap = "day";
                    }
                    $gaptype = $gap;
                    $ft = "";
                    if($key == $datalistlen -1){
                        $s = $datalist[$key]["range"];
                        $diffstart = DateDiff($gaptype, date("Y-m-d H:i:s", $s), date("Y-m-d H:i:s"));
                        if(isset($o["start"]) && $o["start"] != null){
                            $rangestart = $o["start"];
                            $ft = $diffstart.getGaptext($gaptype)."~".$rangestart;
                        }
                        else{
                            if(isset($datalist[$key]["rangeend"])){
                                $e = $datalist[$key]["rangeend"];
                                $diffend = DateDiff($gaptype, date("Y-m-d H:i:s", $e), date("Y-m-d H:i:s"));
                                $ft = $diffstart."~".$diffend.getGaptext($gaptype);
                            }
                            else{
                                $ft = "小于".$diffstart.getGaptext($gaptype);
                            }
                        }
                    }
                    else{
                        $s = $datalist[$key]["range"];
                        $diffstart = DateDiff($gaptype, date("Y-m-d H:i:s", $s), date("Y-m-d H:i:s"));
                        if(isset($datalist[$key]["rangeend"])){
                            $e = $datalist[$key]["rangeend"];
                        }
                        else{
                            $e = $datalist[$key + 1]["range"];
                        }
                        $diffend = DateDiff($gaptype, date("Y-m-d H:i:s", $e), date("Y-m-d H:i:s"));
                        $ft = $diffstart."~".$diffend.getGaptext($gaptype);
                    }
                    $result["countList"][$key]['alias'] = $ft;
                }
                else if(in_array($facetfield, $rangeFacetfield)){
                    $s = $datalist[$key]["range"];
                    if(isset($datalist[$key+1]) && $datalist[$key+1]["range"] != ""){
                        if(isset($datalist[$key]["rangeend"])){
                            $e = $datalist[$key]["rangeend"];
                        }
                        else{
                            $e = $datalist[$key+1]["range"] - 1 ;
                        }
                        $ft = $s."~".$e;
                    }
                    else{
                        if(isset($datalist[$key]["rangeend"])){
                            $e = $datalist[$key]["rangeend"];
                            $ft = $s."~".$e;
                        }
                        else{
                            $ft = "大于".$s;
                        }
                    }
                    $result["countList"][$key]['alias'] = $ft;
                }
                else if($isfacetfeature){ //当facet字段为特征分类时添加 alias(只有父类时,alias为父类, 父类和子类同时存在时 alias为子类)
                        /*
                        $tmp =str_replace("##", ",", $value["text"]);
                        $tmptext = str_replace("#", "", $tmp);
                        $tmparr = explode(",", $tmptext);
                        if(isset($tmparr[1]) && $tmparr[1] != "*"){
                            $resalias = $tmparr[1];
                        }
                        else{
                            $resalias = $tmparr[0];
                        }
                         */
                    $tmptext = str_replace(" ", ",", $value["text"]);
                    $tmparr = explode(",", $tmptext);
                    if(isset($tmparr[1]) && $tmparr[1] != "*"){
                        $resalias = $tmparr[1];
                    }
                    else{
                        $resalias = $tmparr[0];
                    }
                    $result["countList"][$key]["text"] = $tmptext;
                    $result["countList"][$key]['alias'] = $resalias;
                }
                //处理 facet filter 包含 显示其他 alias(用户填写的)
                if(count($fieldArr['facet']['field']) > 0 ){
                    if(isset($result["countList"][$key]["other"]) && $result["countList"][$key]["other"] == 1){
                        if(array_key_exists('alias', $result["countList"][$key])){
                            $result["countList"][$key]['alias'] = $fieldArr['facet']['field'][0]['featureconfig']["alias"];
                        }
                        else{
                            $result["countList"][$key]['text'] = $fieldArr['facet']['field'][0]['featureconfig']["alias"];
                        }
                    }
                    if(isset($result["countList"][$key]["other"]) && $result["countList"][$key]["other"] == 2){
                        if(array_key_exists('alias', $result["countList"][$key])){
                            $result["countList"][$key]['alias'] = $fieldArr['facet']['field'][0]['includeconfig']["alias"];
                        }
                        else{
                            $result["countList"][$key]['text'] = $fieldArr['facet']['field'][0]['includeconfig']["alias"];
                        }
                    }
                }
            }
        }
        //分类对比
        if(isset($fieldArr["contrast"])){
            $contrast_feature = count($fieldArr["contrast"]["filtervalue"]) > 0 && !empty($fieldArr["contrast"]["filtervalue"][0]['isfeature']);
            //不包含分类对比标识
            $contrast_exclude = count($fieldArr["contrast"]["filtervalue"]) > 0 && !empty($fieldArr["contrast"]["filtervalue"][0]['exclude']);
            if($islinkage){
                //联动实例且，分类对比是联动字段，将动态pin的数据先查出来
                $contrastqueryresult = getDynamicClassicQueryResult($datas, $fieldArr['filter'][$fieldArr["contrast"]["fieldname"]]['datatype']);
                for($i=count($fieldArr["contrast"]["filtervalue"])-1;$i>-1;$i--){
                    if($fieldArr["contrast"]["filtervalue"][$i]['fieldvalue']['datatype'] == "dynamic"){
                        array_splice($fieldArr["contrast"]["filtervalue"], $i, 1);
                    }
                }
                if(!empty($contrastqueryresult)){
                    foreach($contrastqueryresult as $k => $v){
                        $cqr['datatype'] = $contrast_feature ? "value_text_object" : $fieldArr['filter'][$fieldArr["contrast"]["fieldname"]]['datatype'];
                        $cqr['value'] = $v;		        				
                        $fieldArr["contrast"]["filtervalue"][] = array("fieldname"=>$fieldArr["contrast"]["fieldname"],
                            "fieldvalue"=>$cqr, "isfeature"=>$contrast_feature);
                    } 
                }
            }
            if(count($fieldArr["contrast"]["filtervalue"]) > MAX_CLASSIFYQUERYCOUNT){
                return getErrorOutput(0, "分类对比的值个数超出最大限制".MAX_CLASSIFYQUERYCOUNT);
            }
            $dbfield = $fieldArr["contrast"]["filtervalue"][0]["fieldname"];
            $dbfield = getRealSolrFieldName($dbfield, NULL, $fieldArr["modelid"]);
            $fiArr = array();
            foreach($fieldArr["contrast"]["filtervalue"] as $key=>$value){
                //特征分类的分类对比，先从solr查出所有词
                if(!empty($value['isfeature'])){
                    $feaclass = getFilterValue($value["fieldvalue"]);//特征分类的分类名
                    //获取某分类的词
                    $feakeywords = getFeatureKeyword(0,PHP_INT_MAX, $dbfield, NULL, $feaclass['value'], NULL);
                    if(!empty($feakeywords['datalist'])){
                        foreach($feakeywords['datalist'] as $fek => $fev){
                            //使用二维数组存储词
                            $fiArr[$feaclass['value']][] = $fev['feature_keyword'];
                        }
                    }
                }
                else{
                    $fiArr[] = getFilterValue($value["fieldvalue"]);
                }
            }

            $useridCache = array();
            $userfriendidCache = array();
            $sourceHostNameCache = array();
            $contrastArr = $fiArr;
            $arr = array();
            //拆分
            foreach($result["docs"] as $key=>$value){
                $flag = true;
                foreach($contrastArr as $k=>$v){
                    //特征分类的分类对比
                    if($contrast_feature){
                        //特征分类是二维数组
                        if(!empty($value[$dbfield])){
                            if(is_array($value[$dbfield])){
                                //多值的字段，依次判断每个值是否在特征分类关键词数组中出现
                                foreach($value[$dbfield] as $wvk => $wvv){
                                    if($contrast_exclude){ //不包含此项 
                                        if(!in_array($wvv, $v)){
                                            $arr[$k][] = $value;
                                            $flag = false;
                                            //break;
                                        }
                                    }
                                    else{
                                        if(in_array($wvv, $v)){
                                            $arr[$k][] = $value;
                                            $flag = false;
                                            break;
                                        }
                                    }
                                }
                            }
                            else{
                                //非多值类型的字段，直接判断是否在特征分类关键词数组中出现，出现则归为该类
                                if($contrast_exclude){
                                    if(!in_array($value[$dbfield], $v)){
                                        //分组名称为 子类。$k 为类名
                                        $arr[$k][] = $value;
                                        //$flag = false;
                                    }
                                }
                                else{
                                    if(in_array($value[$dbfield], $v)){
                                        //分组名称为 子类。$k 为类名
                                        $arr[$k][] = $value;
                                        $flag = false;
                                    }
                                }
                            }
                        }
                        continue;
                    }
                    //决定比较那个字段
                    if($dbfield == "areauser" || $dbfield == "areamentioned" || $dbfield == "emoAreamentioned" || $dbfield == "ancestor_emoAreamentioned" || $dbfield == "ancestor_areamentioned"){  //地区情感字段返回的是数组
                        $tmpcode =explode(",", $v['value']); 
                        if($dbfield == "emoAreamentioned" || $dbfield == "ancestor_emoAreamentioned"){
                            $isemo = true;
                        }
                        else{
                            $isemo = false;
                        }
                        $tmpfield = codelevel($tmpcode[0], $isemo);
                        if($dbfield == "areauser"){ //用户地区
                            $tmpfield .="_code";
                        }
                        if($dbfield == "ancestor_emoAreamentioned" || $dbfield == "ancestor_areamentioned"){
                            $tmpfield = "ancestor_".$tmpfield;
                        }
                    }
                    else if($dbfield == "usersfollower"){
                        $tmpfield = "users_friends_id"; //查粉丝的分类对比, 取结果中的关注id字段, 例如对比姚晨和谢娜, 查看姚晨是否在关注字段中
                    }
                    else if($dbfield == "usersfriend"){
                        $tmpfield = "users_id";
                    }
                    else{
                        $tmpfield = $dbfield;
                    }
                    //比较是否匹配
                    if(isset($value[$tmpfield]) && is_array($value[$tmpfield])){ //solr返回字段为数组
                        $tmpArr = array();
                        if(in_array($tmpfield, $emoStringArr) || in_array($tmpfield, $emoVTArr) || $tmpfield == "ancestor_combinWord" || $tmpfield == "combinWord" || $tmpfield == "ancestor_wb_topic_combinWord" || $tmpfield == "wb_topic_combinWord"){  //比较字段是情感,会含有多个情感
                            foreach($value[$tmpfield] as $i=>$item){ //返回结果对应字段是字符串数组,去掉#号后存成数组
                                $tmp =str_replace("##", ",", $item);
                                $tmpArr[]  = str_replace("#", "", $tmp);
                            }
                        }
                        else{
                            $tmpArr = $value[$tmpfield];
                        }
                        //处理要分类对比的值
                        if($dbfield == "usersfollower"){ //分类对比的是查粉丝字段, 需要根据 value查出对应的id
                            $ttmp = $v;
                            $has = false;
                            if(count($useridCache) > 0){
                                foreach($useridCache as $i=>$user){
                                    if($user["screen_name"] == $v){
                                        $vtmp = $user["userid"];
                                        $has = true;
                                        break;
                                    }
                                }
                            }
                            if(!$has){
                                $vtmp = getUserIdByScreenName($v);
                                $u["userid"] = $vtmp; 
                                $u["screen_name"] = $v;
                                $useridCache[] = $u;
                            }
                        }
                        else{
                            if(is_array($v)){
                                $vtmp = $v["value"];
                                if(in_array($tmpfield, $emoStringArr) || in_array($tmpfield, $emoVTArr)){  //比较字段是情感,会含有多个情感
                                    if(isset($v["text"])){
                                        $pos = strpos($v["text"], "(");
                                        $txt = substr($v["text"], 0, $pos);
                                        $tmp = emoval2text(substr($v["value"], -1)); //情感值对应中文
                                        $ttmp = $txt." ".$tmp;
                                    }
                                    else{
                                        $emotmp = $v["value"];
                                        $emotmp = str_replace(",", " ", $emotmp);       
                                        $tmp = emoval2text(substr($emotmp, -1)); //情感值对应中文
                                        if($tmp != ""){
                                            $ttmp = preg_replace("/\d$/", $tmp, $emotmp);
                                        }
                                    }
                                }
                                else{
                                    $ttmp = isset($v["text"]) ? $v["text"] : $v["value"];
                                }
                            }
                            else{
                                $vtmp = $v;
                                $ttmp = $v;
                            }
                        }
                        //需要判断$vtmp中是否含有*, 当有*时需要模糊比较
                        if(stripos($vtmp, "*") !== false){
                            $reg = str_replace("*", ".*", $vtmp);
                            foreach($tmpArr as $ti=>$titem){
                                if($contrast_exclude){ //不包含的分类对比
                                    if(!preg_match("/".$reg."/i",$titem)){
                                        $arr[$v][] = $value;
                                        $flag = false;
                                        //break;
                                    }
                                }
                                else{
                                    if(preg_match("/".$reg."/i",$titem)){
                                        $arr[$v][] = $value;
                                        $flag = false;
                                        break;
                                    }
                                }
                            }
                        }
                        else{
                            if($contrast_exclude){ //不包含的分类对比
                                if(!in_array($vtmp, $tmpArr)){ //或是模糊查询
                                    $arr[$ttmp][] = $value;
                                    $flag = false;
                                }
                            }
                            else{
                                if(in_array($vtmp, $tmpArr)){ //或是模糊查询
                                    $arr[$ttmp][] = $value;
                                    $flag = false;
                                }
                            }
                        }
                    }
                    else{
                        if($dbfield == "usersfriend"){//查关注的对比, 根据用户名取出所关注的用户id
                            $has = false;
                            if(count($userfriendidCache) > 0){
                                foreach($userfriendidCache as $i=>$fri){
                                    if($fri["screen_name"] == $v){
                                        $userfriendsid = $fri["friends_id"];
                                        $has = true;
                                        break;
                                    }
                                }
                            }
                            if(!$has){
                                $userfriendsid = getUserFriendIDByScreenName($v);
                                $u["screen_name"] = $v;
                                $u["friends_id"] = $userfriendsid;
                                $userfriendidCache[] = $u;
                            }
                            if(count($userfriendsid) > 0){
                                if(in_array($value[$tmpfield], $userfriendsid)){ //返回数据中 userid在对比用户的 friends_id数组中 则是此用户的关注
                                    $arr[$v][] = $value;
                                    $flag = false;
                                }
                            }
                        }
                        else if($dbfield == "users_source_host" || $dbfield == "source_host"){
                            $has = false;
                            foreach($sourceHostNameCache as $si=>$sitem){
                                if($sitem['code'] == $v['value']){
                                    $cfield = $sitem['name'];
                                    $has = true;
                                    break;
                                }
                            }
                            if(!$has){
                                $cfield = get_source_id($v['value']); //要分类对比的域名  
                                if($cfield == NULL){
                                    $cfield = $v['value'];
                                }
                                $sourceHostNameCache[] = array('name'=>$cfield, 'code'=>$v['value']);
                            }
                            $found = false;
                            foreach($sourceHostNameCache as $si=>$sitem){
                                if($sitem['code'] == $value[$dbfield]){
                                    $datafield = $sitem['name'];
                                    $found = true;
                                    break;
                                }
                            }
                            if(!$found){
                                $datafield = get_source_id($value[$dbfield]);
                                if($datafield == NULL){
                                    $datafield = $value[$dbfield];
                                }
                                $sourceHostNameCache[] = array('name'=>$datafield, 'code'=>$value[$dbfield]);
                            }
                            if($cfield == $datafield){
                                $arr[$v['text']][] = $value;
                                $flag = false;
                            }
                        }
                        else{
                            if($tmpfield == "screen_name" || $tmpfield == "users_screen_name" || $tmpfield == "users_domain" || $tmpfield == "users_url" || $tmpfield == "users_page_url"){
                                $reg = str_replace("*", ".*", $v);
                                if($contrast_exclude){ //不包含的分类对比
                                    if(isset($value[$tmpfield]) && !preg_match("/".$reg."/i",$value[$tmpfield])){
                                        $arr[$v][] = $value;
                                        $flag = false;
                                        //break;
                                    }
                                }
                                else{
                                    if(isset($value[$tmpfield]) && preg_match("/".$reg."/i",$value[$tmpfield])){
                                        $arr[$v][] = $value;
                                        $flag = false;
                                        break;
                                    }
                                }
                            }
                            else{
                                if($tmpfield == "content_type"){
                                    switch($v["value"]){
                                    case "100":
                                        $v["value"] = 0;
                                        break;
                                    case "101":
                                        $v["value"] = 1;
                                        break;
                                    default:
                                        break;
                                    }
                                }
                                if(is_array($v)){
                                    $vtmp = $v["value"];
                                    $ttmp = isset($v["text"]) ? $v["text"] : $v["value"];

                                }
                                else{
                                    $vtmp = $v;
                                    $ttmp = $v;
                                }

                                if($contrast_exclude){ //不包含的分类对比
                                    if(isset($value[$tmpfield]) && $value[$tmpfield] != $vtmp){
                                        $arr[$ttmp][] = $value;
                                        $flag = false;
                                        //break;
                                    }
                                }
                                else{
                                    if(isset($value[$tmpfield]) && $value[$tmpfield] == $vtmp){
                                        $arr[$ttmp][] = $value;
                                        $flag = false;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                if($flag){
                    $arr["其他"][] = $value;
                }
            }
            //格式化
            foreach($arr as $key=>$value){
                $tmpresult["numFound"] = count($value);
                $tmpresult["docs"] = $value;
                $field1 = $tmpresult;
                $field1['categoryname'] = $key;
                $res[] = $field1;
            }
        }
        else{
            //$categoryname = "";
            if($classifyqueryCount > 0){ //分类查询, 查询次数
                $classifyqueryCount--; //普通分类查询,查询递减, 减为0时进行分类查询的"全部" 和 分类查询的"其他" 查询
                if($classifyqueryFields[0]['fieldvalue']['value'][0]['datatype'] == 'value_text_object'){
                    $categoryname = $classifyqueryFieldValues[$__i]['text'];    
                    $categoryname = str_replace(",", " ", $categoryname);
                }
                else if($classifyqueryFields[0]['fieldvalue']['value'][0]['datatype'] == 'blur_value_object'){
                    $categoryname = $classifyqueryFieldValues[$__i]['value'];    
                    $categoryname = str_replace(",", " ", $categoryname); 
                }
                else{
                    $categoryname = $classifyqueryFieldValues[$__i];
                    //2015-8-10 bert 修改, 分类查询情况下,用户添加的关键词包含数字,如:马3 会替换成马中立,使含有改变
                    //情感关键词的分类查询用到,分类查询的组名称是: 马,3 3表示情感
                    $isemocate = strpos($categoryname, ","); 
                    if($isemocate !== false){
                        $categoryname = str_replace(",", " ", $categoryname);
                        $tmp = emoval2text(substr($categoryname, -1)); 
                        if($tmp != ""){
                            $categoryname = preg_replace("/\d$/", $tmp, $categoryname);
                        }
                    }
                }
                $categoryvalue = array("datatype"=>$classifyqueryFields[0]['fieldvalue']['value'][0]['datatype']);
                $categoryvalue['value'] = $classifyqueryFieldValues[$__i];//动态pin连接分类查询字段时，drilldown取不到当前分组的的value，所以增加此属性
                if(isset($classifyqueryFields[0]['fieldvalue']['value'][$__i]['exclude'])){
                    $categoryvalue['exclude'] =$classifyqueryFields[0]['fieldvalue']['value'][$__i]['exclude'];
                }
                //递归生成url时，只读取数组的第一个元素，查询完即删除第一个
                foreach($arg_filtervalue as $k=>$v){
                    if($v['fieldname'] == $classifyqueryFieldName){
                        //var_dump($arg_filtervalue[$k]['fieldvalue']['value']);
                        array_splice($arg_filtervalue[$k]['fieldvalue']['value'],0,1);
                        break;
                    }
                }
            }
            $result['categoryname'] = $categoryname;//注意和上行顺序,在原数组中添加字段
            $result['categoryvalue'] = $categoryvalue;
            $res[] = $result;
        }
    } //分类查询请求循环 end
    //分类查询时,返回多组数据的个数要保持一致, 个数少的要 补 0
        /*
        if(isset($fieldArr["classifyquery"])){
            $maxdatalen = 0;
            $equalflag = true;
            foreach($res as $key=>$value){
                if(isset($value["countList"])){
                    $tmpObj = $value["countList"][0];
                    if(count($value["countList"]) > $maxdatalen){
                        $maxdatalen = count($value["countList"]);
                    }
                    if(isset($res[0]["countList"])){
                        if(count($value["countList"]) != count($res[0]["countList"])){
                            $equalflag = false;
                        }
                    }
                }
                else{ //当countList不存在时
                    $equalflag = false;
                }
            }
            if(!$equalflag){
                $tmpresArr = array();
                foreach($res as $k => $v){
                    $tmpres = array();
                    $countlist = array();
                    for($i=0; $i< $maxdatalen; $i++){
                        if(isset($v["countList"][$i])){
                            $countlist[] = $v["countList"][$i];
                        }
                        else{
                            if(isset($tmpObj)){
                                foreach($tmpObj as $field=>$item){
                                    if($field == "text" || $field == "alias"){
                                        $tmpObj[$field] = "";
                                    }
                                    else{
                                        $tmpObj[$field] = 0;
                                    }
                                }
                                $countlist[] = $tmpObj;
                            }
                        }
                    }
                    $tmpres["count"] = isset($v["count"]) ? $v["count"] : 0;
                    $tmpres["countList"] = $countlist;
                    $tmpres["categoryname"] = $v["categoryname"];
                    $tmpres["categoryvalue"] = $v["categoryvalue"];
                    if(isset($v["before"])){
                        $tmpres["before"] = $v["before"];
                    }
                    if(isset($v["after"])){
                        $tmpres["after"] = $v["after"];
                    }
                    $tmpresArr[] = $tmpres; 
                }
                $res = $tmpresArr;
            }
        }
         */
    $needsumArr = array("frq", "reposts_count", "comments_count", "direct_comments_count", "praises_count","satisfaction", "godRepPer", "midRepPer", "wosRepPer", "godRepNum", "midRepNum", "wosRepNum", "apdRepNum", "showPicNum", "cmtStarLevel", "discuss_count","direct_reposts_count","total_reposts_count", "followers_count", "total_reach_count", "users_followers_count", "users_friends_count", "users_statuses_count", "users_replys_count","users_recommended_count","users_favourites_count", "users_bi_followers_count");
    //当分类查询的sum为1时, 合并数据为总计
    if(!empty($fieldArr["classifyquery"]["sum"])){ //分类总计
        $needsumArr = array("frq");
        if(isset($fieldArr['facet']['field'][0]["facetcalculate"])){
            $arg_output_calc = $fieldArr['facet']['field'][0]["facetcalculate"];
            if(count($arg_output_calc) > 0){
                foreach($arg_output_calc as $ai=>$aitem){
                    $needsumArr[] = "".$aitem["calctype"].":".$aitem["code"]."";
                }
            }
        }
        $sumBefore = array();
        $sumCountlist = array();
        $sumAfter = array();
        $count = 0;
        foreach($res as $rk=>$rv){
            //返回结果中含有"其他" "全部", 不添加到"分类总计"
            if(isset($rv["categoryvalue"]) && ($rv["categoryvalue"] == "all" || $rv["categoryvalue"] == "other")){
                continue;
            }
            if(isset($rv["before"])){
                if($rk == 0){
                    foreach($rv["before"] as $bk=>$bval){
                        $has = false; 
                        foreach($needsumArr as $nk=>$nval){
                            if($bk == $nval){
                                $sumBefore[$bk] = $bval;
                                $has = true;
                            }
                        }
                        if(!$has){
                            $sumBefore[$bk] = $bval;
                        }
                    }
                }
                else{
                    foreach($needsumArr as $nk=>$nval){
                        if(array_key_exists($nval, $rv["before"])){
                            $sumBefore[$nval] += $rv["before"][$nval]; //合并
                        }
                    }
                }
            }
            if(isset($rv["countList"])){
                $count += $rv["count"];
                foreach($rv["countList"] as $ck=>$clist){
                    $cArr = array();
                    if($rk == 0){
                        foreach($clist as $key=>$cval){
                            $has = false; 
                            foreach($needsumArr as $nk=>$nval){
                                if($key == $nval){
                                    $cArr[$key] = $cval;
                                    $has = true;
                                }
                            }
                            if(!$has){
                                $cArr[$key] = $cval;
                            }
                        }
                        $sumCountlist[] = $cArr;
                    }
                    else{
                        if(isset($sumCountlist[$ck])){
                            foreach($needsumArr as $nk=>$nval){
                                if(array_key_exists($nval, $clist)){
                                    $sumCountlist[$ck][$nval] += $clist[$nval];
                                }
                            }
                            foreach($clist as $key=>$cval){
                                if(!in_array($key, $needsumArr) && $key != "other"){
                                    if($cval  != $sumCountlist[$ck][$key]){
                                        $sumCountlist[$ck][$key] .= " ".$cval;
                                    }
                                }
                            }
                        }
                        else{//返回多组时,第一组数据个数小于其他组时,单独列出
                            foreach($clist as $key=>$cval){
                                $has = false; 
                                foreach($needsumArr as $nk=>$nval){
                                    if($key == $nval){
                                        $cArr[$key] = $cval;
                                        $has = true;
                                    }
                                }
                                if(!$has){
                                    $cArr[$key] = $cval;
                                }
                            }
                            $sumCountlist[] = $cArr;
                        }
                    }
                }
            }
            if(isset($rv["after"])){
                if($rk == 0){
                    foreach($rv["after"] as $bk=>$bval){
                        $has = false; 
                        foreach($needsumArr as $nk=>$nval){
                            if($bk == $nval){
                                $sumAfter[$bk] = $bval;
                                $has = true;
                            }
                        }
                        if(!$has){
                            $sumAfter[$bk] = $bval;
                        }
                    }
                }
                else{
                    foreach($needsumArr as $nk=>$nval){
                        if(array_key_exists($nval, $rv["after"])){
                            $sumAfter[$nval] += $rv["after"][$nval]; //合并
                        }
                    }
                }
            }
        }
        $sumres["count"] = $count;
        $sumres["categoryname"] = "分类总计"; 
        $sumres["categoryvalue"] = "sum"; 
        if(count($sumBefore) > 0){
            $sumres["before"] = $sumBefore;
        }
        if(count($sumAfter) > 0){
            $sumres["after"] = $sumAfter;
        }
        $sumres["countList"] = $sumCountlist;
        $res[] = $sumres;
    }
    $logger->debug(__FILE__." ".__FUNCTION__." exit");
    //对返回数据调整字段顺序
    $resD = formatResult($res);
    $fieldsortres = sortSnapshotField($resD, $fieldArr["modelid"]);
	$end_time = microtime_float();
    $logger->debug(__FILE__.__LINE__.__FUNCTION__ . " 调用solr花费时间:[" . ($end_time - $start_time) . "] 秒！");
    return $fieldsortres;
}

/*
 * @brief  根据$guid和$tagdata查询出关键词的文章
 * @param  Array  $tagdata 查询出所有特征分类的数组
 * @param  String $guid
 * @return 特征分类数组
 * @author Cathy Zuo
 * @date   2016-7-22
 * @change 2016-7-22
 * */
function searchTagInfoById($tagdata, $guid){
    global $logger;
    if($guid != ""){
        $qparam = "guid:".$guid;
    }
    $page = isset( $_GET['page']) ? $_GET['page']-1 : 0;
    $pagesize = isset($_GET['pagesize']) ? $_GET['pagesize']:(pow(2, 31) - 1);
    $startnum = $page * $pagesize;
    $url = "q=".$qparam."&fl=&facet.field=text&facetCounts=2&facet.calculate.count=floor&facet.limit=".$pagesize."&facet=on&facet.offset=".$startnum."&rows=0&facet.minsumcount=1";
    $nameresult = solrRequest($url, "text", "field");
    if(isset($nameresult['error'])){
        return $nameresult;
    };
    //datatagresult
    $tagarry = array();
    if(!empty($tagdata)){
        $newarry = array();
        //此处最后一级的查询不能使用guid,
        foreach($tagdata as $key => $value){
            if(isset($value["fieldvalue"]["value"]["guid"])){
                $featureid = $value["fieldvalue"]["value"]["guid"];
                $resultArr = array();
                getAllFeatureByID($featureid, 'text', $resultArr);
                $logger->debug(__FILE__.__LINE__." resultArr ".var_export($resultArr, true));
                foreach($resultArr as $ri=>$ritem){
                    $newarry[] ="(feature_class:".solrEsc(str_replace("#", "", $ritem['feature_class']))."+AND+feature_father_guid:".$ritem["feature_father_guid"].")";
                }
            }
        }
        $qp = implode("+OR+", $newarry);
        $logger->debug(__FILE__.__LINE__." ------qp------- ".var_export($qp, true));
        if(!empty($qp)){
            $qp = "(".$qp.")+AND+feature_field:text";
            $tagurl = "q=".$qp."&fl=&start=".$startnum."&rows=".$pagesize."&facet=off";
            $tagresult = solrRequest($tagurl, "response", "query");
            if(isset($tagresult['error'])){
                return $tagresult;
            }
            foreach($tagresult["docs"] as $keytag => $valuetag){
                foreach($nameresult["countList"] as $key => $value){
                    if(isset($valuetag["feature_keyword"]) && $valuetag["feature_keyword"] == $value["text"]){
                        //begin：查看标签：如果是同一类下有多个关键字，选在该类作为分类会重复 by zuo 2015-7-25
                        $flag = false;
                        foreach($tagarry as $k => $v){
                            if($v["pclass"] == $valuetag["feature_class"]){
                                $flag=true;
                            }
                        }
                        if(!$flag){
                            $tmpobj = array();
                            $tmpobj['keyword'] = $valuetag["feature_keyword"];
                            $tmpobj['pclass'] = $valuetag["feature_class"];
                            $tagarry[]=$tmpobj;
                        }
                        //end：查看标签：如果是同一类下有多个关键字，选在该类作为分类会重复 by zuo 2015-7-25
                    }
                };
            }
        }
        else{
            $logger->error(__FILE__.__LINE__." feature_class array empty");
        }
    }
    return $tagarry;
}

if(isset($_GET['type'])){
    $outputdata = solragentFun($_GET);
    echo json_encode($outputdata);
    exit;
}else if(isset($HTTP_RAW_POST_DATA)){ /*新增标签*/
    global $arrsdata,  $logger;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
    if($arg_type == "addfeatureword"){
        foreach($arrsdata["feature_keyword"] as $key => $value){
            if($value != ""){
                $farr["feature_father_guid"] = $arrsdata["feature_father_guid"];
                $farr["feature_field"] = $arrsdata["feature_field"];
                //TODO
               // $farr["feature_keyword"] = preFormatter($featureConfig, $farr["feature_field"], $value);
                $farr["feature_keyword"] =$value;
                $featureArr[] = $farr;
            }
        }
        $logger->debug(__FILE__.__LINE__." featureArr---- ".var_export($featureArr, true));
        $r["flag"] = addFeature($featureArr);
        echo json_encode($r);
    }
    /*else if($arg_type == "searchtags"){
        $tagdata = $arrsdata["tagdata"];
        $guid = isset($arrsdata["guid"]) ? $arrsdata["guid"] : "";
        if($guid != ""){
            $qparam = "guid:".$guid;
        }
        $page = isset( $_GET['page']) ? $_GET['page']-1 : 0;
        $pagesize = isset($_GET['pagesize']) ? $_GET['pagesize']:(pow(2, 31) - 1);
        $startnum = $page * $pagesize;
        $url = "q=".$qparam."&fl=&facet.field=text&facetCounts=2&facet.calculate.count=floor&facet.calculate.sort=count:floor+desc&facet.limit=".$pagesize."&facet=on&facet.offset=".$startnum."&rows=0&facet.minsumcount=1";
        $nameresult = solrRequest($url, "text", "field");
        if(isset($nameresult['error'])){
            return $nameresult;
        };
        //datatagresult
        $tagarry = array();
        if(!empty($tagdata)){
            $newarry = array();
            //此处最后一级的查询不能使用guid, 
            foreach($tagdata as $key => $value){
                if(isset($value["fieldvalue"]["value"]["guid"])){
                    $featureid = $value["fieldvalue"]["value"]["guid"];
                    $resultArr = array();
                    getAllFeatureByID($featureid, 'text', $resultArr);
                    $logger->debug(__FILE__.__LINE__." resultArr ".var_export($resultArr, true));
                    foreach($resultArr as $ri=>$ritem){
                        $newarry[] ="(feature_class:".solrEsc(str_replace("#", "", $ritem['feature_class']))."+AND+feature_father_guid:".$ritem["feature_father_guid"].")";
                    }
                }
            }
            $qp = implode("+OR+", $newarry);
            $logger->debug(__FILE__.__LINE__." ------qp------- ".var_export($qp, true));
            if(!empty($qp)){
                $qp = "(".$qp.")+AND+feature_field:text";
                $tagurl = "q=".$qp."&fl=&start=".$startnum."&rows=".$pagesize."&facet=off";
                $tagresult = solrRequest($tagurl, "response", "query");
                if(isset($tagresult['error'])){
                    return $tagresult;
                }
                foreach($tagresult["docs"] as $keytag => $valuetag){
                    foreach($nameresult["countList"] as $key => $value){
                        if(isset($valuetag["feature_keyword"]) && $valuetag["feature_keyword"] == $value["text"]){
                            $tmpobj = array();
                            $tmpobj['keyword'] = $valuetag["feature_keyword"];
                            $tmpobj['pclass'] = $valuetag["feature_class"];
                            $tagarry[]=$tmpobj;
                        }
                    }
                }
            }
            else{
                $logger->error(__FILE__.__LINE__." feature_class array empty");
            }
        }
        $r['datalist'] = $tagarry;
        echo json_encode($r);
        exit;
    }*/
    else if('addtags' == $arg_type){
        /*
         * 新增标签
         * */
        $guid = isset($arrsdata["guid"]) ? $arrsdata["guid"] : "";
        $article_taginfo = $arrsdata['article_taginfo'];
        $allarticle = $arrsdata['allarticle'];
        $guidarray = $arrsdata['guidarray'];
        $r['flag'] = 0;
        if(!empty($article_taginfo)){
            if($allarticle){
                $flag_allarticle = 2;
                $datas = array();
                foreach($guidarray as $gi=>$gui){
                    $tag['guid'] = $gui;
                    $userid = $_SESSION['user']->getuserid();
                    if(!empty($userid)){
                        $real_field = getUserArticleTaginfoField($userid);
                        $tag[$real_field] = $article_taginfo;
                        $datas[] = $tag;
                    }
                }
                if(!empty($datas)){
                    $res = insert_solrdata($datas, true);
                    if($res !== true){
                        $flag_allarticle = 0;
                    }
                }
                $r['flag']= $flag_allarticle;
            }else{
                $datas = array();
                $tag['guid'] = $guid;
                $logger->debug(__FILE__.__LINE__." _SESSION ".var_export($_SESSION, true));
                $userid = $_SESSION['user']->getuserid();
                $logger->debug(__FILE__.__LINE__." userid ".var_export($userid, true));
                if(!empty($userid)){
                    $real_field = getUserArticleTaginfoField($userid);
                    $tag[$real_field] = $article_taginfo;
                    $datas[] = $tag;
                    $res = insert_solrdata($datas, true);
                    if($res === true){
                        $r['flag'] = 1;
                    }
                }
            }
        }
        echo json_encode($r);
        exit;
    }
}
function solragentFun($get){
    global $selectUrl, $kwblurUrl, $kwtokenUrl, $kwgroupUrl, $weibosame ,$tokenize, $logger;

    $_GET = $get;
    /*查询条件基本类似,整合相似的*/
    if('kwblur' == $_GET['type'])
    {
        $keywords = $_GET['keyword'];
        $logger->debug(__FILE__.__LINE__." before analysis origdata ".var_export($keywords, true));
        $keywords = urlencode($keywords);
        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : 'text';
        $blurtype = isset($_GET["blurtype"]) ? $_GET['blurtype'] : '';
        $page = $_GET['page']-1; //页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;
        //$url = $kwblurUrl."?word=".$keywords."&type=".$blurtype."&fieldname=".$fieldname."&start=".$startnum."&row=".$pagesize;
        $url = "word=".$keywords."&type=".$blurtype."&fieldname=".$fieldname."&start=".$startnum."&row=".$pagesize;
        $result = solrRequest($url, 'BKList', "query", $kwblurUrl);
        if(isset($result['error'])){
            return $result;
        }
        $keywordArr = array();
        foreach($result[0]['keywords'] as $key=>$value){
            $tmp =str_replace("##", ",", $value);
            $keywordArr[]  = str_replace("#", "", $tmp);
        }
        $r['categoryname'] = $result[0]['word'];//注意和上行顺序,在原数组中添加字段
        $r['count'] = $result[0]['count'];
        $r['countList'] = $keywordArr;
        $res[] = $r;
        return formatResult($res);
    }
    else if('kwtoken' == $_GET['type'])
    {
        $keywords = $_GET['keyword'];
        $keywordslen = strlen($keywords);
        //$keywords = solrEsc($keywords);
        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : 'text';
        $page = $_GET['page']-1;//页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];

        $startnum = $page * $pagesize;
        $sendarr =array();
        $sendarr['text_type'] = 0;//不是正文
        $sendarr['sourceid'] = null;
        $sendarr['dicttype'] = TOKENIZE_DICTTYPE_NOEB;
        $sendarr['dependorig'] = 0;
        $sendarr['originfo'] = '';
        $sendarr['content'] = $keywords;
        $analysis_datas[] = $sendarr;
        //$logger->debug(__FILE__." ".__FUNCTION__."  kwtoken_result:".var_export($analysis_datas,true));
        $solr_r = send_solr($analysis_datas,SOLR_URL_ANALYSIS);
        //$logger->debug(__FILE__." ".__FUNCTION__." word:".$keywords." kwtoken_result:".var_export($solr_r,true));
        if(isset($solr_r['tokenresult']) && count($solr_r['tokenresult'][0]) > 0){
            //tokeresult[{text:[{start:0,end:5,text:"中华"},{start:0,end:5,text:"人民"}]}]
            $tokenizeresult = array();
            foreach($solr_r['tokenresult'][0]["text"] as $si=>$sitem){
                if(!in_array($sitem['text'], $tokenizeresult)){
                    $tokenizeresult[] = $sitem['text']; 
                }
            }
            if(count($tokenizeresult) > 0){
                $kArr = implode(",", $tokenizeresult);
                $kArr = urlencode($kArr);
                //$kwtokenurl = $kwtokenUrl."?word=".$kArr."&fieldname=".$fieldname."&start=".$startnum."&row=".$pagesize;
                $kwtokenurl = "word=".$kArr."&fieldname=".$fieldname."&start=".$startnum."&row=".$pagesize;
                $result = solrRequest($kwtokenurl, 'TKList', "query", $kwtokenUrl);
                if(isset($result['error'])){
                    return $result;
                }
                $r['categoryname'] = "";
                $r['count'] = count($result[0]['keywords']);
                $r['countList'] = $result[0]['keywords'];
            }
        }
        else{
            $r['categoryname'] = "";
            $r['count'] = 0;
            $r['countList'] = array();
        }
        $res[] = $r;
        return formatResult($res);
    }
    else if('kwgroup' == $_GET['type'])
    {
        $keywords = $_GET['keyword'];
        $keywords = urlencode($keywords);
        $blurtype = isset($_GET["blurtype"]) ? $_GET['blurtype'] : '';
        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : 'combinWord';
        $page = $_GET['page']-1;//页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;
        //$url = $kwgroupUrl."?word=".$keywords."&type=".$blurtype."&fieldname=".$fieldname."&start=".$startnum."&row=".$pagesize;
        $url = "word=".$keywords."&type=".$blurtype."&fieldname=".$fieldname."&start=".$startnum."&row=".$pagesize;
        $result = solrRequest($url, 'CKList', "query", $kwgroupUrl);
        if(isset($result['error'])){
            return $result;
        }
        //处理结果把 ##转为, 一个的去掉
        $keywordArr = array();
        foreach($result[0]['keywords'] as $key=>$value){
            $tmp =str_replace("##", ",", $value);
            $keywordArr[]  = str_replace("#", "", $tmp);
        }
        $result['categoryname'] = "";//注意和上行顺序,在原数组中添加字段
        $r['categoryname'] = $result[0]['word'];//注意和上行顺序,在原数组中添加字段
        $r['count'] = $result[0]['count'];
        $r['countList'] = $keywordArr;
        $res[] = $r;
        return formatResult($res);
    }
    else if('searchname' == $_GET['type']){
        $bname = isset($_GET['blurname']) ? $_GET["blurname"] : "";
        if($bname != ""){
            $bname = urlencode($bname);
            $qparam = "users_screen_name:".$bname;
        }
        else{
            $qparam = "users_id:*";
        }
        $page = $_GET['page'] - 1;//页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;
        //http://192.168.0.102:8080/solrstore/select?q=users_screen_name:*%E7%8E%8B*&fl=users_screen_name,users_id&start=0&rows=10&facet=off;
        //$url = $selectUrl."?q=".$qparam."&fl=users_screen_name,users_id&start=".$startnum."&rows=".$pagesize."&facet=off";
        $url = "q=".$qparam."&fl=users_screen_name,users_id&start=".$startnum."&rows=".$pagesize."&facet=off";
        $nameresult = solrRequest($url, "response", "query");
        if(isset($nameresult['error'])){
            return $nameresult;
        }
        $res["categoryname"] = "";
        $res["numFound"] = $nameresult['numFound'];
        $res["docs"] = $nameresult['docs'];
        $logger->debug(__FILE__.__LINE__." res: ".var_export(array($res), true));
        return formatResult(array($res));
    }
    else if('rangerecommend' == $_GET['type']){
        $facetfield = $_GET['facetfield'];
        //http://192.168.0.30:8080/solrstore/select?q=*:*&fl=comments_count&start=0&rows=1&facet=off&sort=comments_count+asc
        //$minurl = $selectUrl."?q=".$facetfield.":*&fl=".$facetfield."&start=0&rows=1&facet=off&sort=".$facetfield."+asc"; //rows = 0不返回文档个数
        $minurl = "q=".$facetfield.":*&fl=".$facetfield."&start=0&rows=1&facet=off&sort=".$facetfield."+asc"; //rows = 0不返回文档个数
        $minresult = solrRequest($minurl, "response", "query");
        if(isset($minresult['error'])){
            return $minresult;
        }

        //$maxurl = $selectUrl."?q=".$facetfield.":*&fl=".$facetfield."&start=0&rows=1&facet=off&sort=".$facetfield."+desc"; //rows = 0不返回文档个数
        $maxurl = "q=".$facetfield.":*&fl=".$facetfield."&start=0&rows=1&facet=off&sort=".$facetfield."+desc"; //rows = 0不返回文档个数
        $maxresult = solrRequest($maxurl, "response", "query");
        if(isset($maxresult['error'])){
            return $maxresult;
        }

        $res["categoryname"] = "";
        $res["count"] = 1;
        $res["countList"] = array(array("range"=>$minresult['docs'][0][$facetfield], "rangeend"=>$maxresult['docs'][0][$facetfield]));
        return formatResult(array($res));
    }
    else if('selectfeaturepclass' == $_GET['type']){
        $page = $_GET['page'] - 1;//页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;

        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : '';
        $res = getFeatureClass($startnum, $pagesize, NULL, $fieldname, NULL, 0);
        $logger->debug(__FILE__.__LINE__." res ".var_export($res, true));
        $r['categoryname'] = "";
        $r['count'] = $res["totalcount"];
        $tmpArr = array();
        foreach($res['datalist'] as $key=>$item){
            if(!empty($item['feature_pclass'])){
                $tmpF = array();
                $tmpF['feature_class'] = $item['feature_pclass'];
                $tmpF['feature_field'] = $item['feature_field'];
                if(isset($item['feature_keyword'])){
                    $tmpF['feature_keyword'] = $item['feature_keyword'];
                }
                $tmpF['guid'] = $item['guid'];
                $tmpF['old_feature'] = true;
                $tmpArr[] = $tmpF;
            }
            else{
                $tmpArr[] = $item;
            }
        }
        $r['countList'] = $tmpArr;
        return formatResult(array($r));
    }
   /*
    else if('selectfeaturerootclass' == $_GET['type']){
        $feature_father_guid = 0; 
        $res = getFeatureClass(NULL, NULL, NULL, NULL, NULL, $feature_father_guid); 
        $r['categoryname'] = "";
        $r['count'] = $res["totalcount"];
        $r['countList'] = $res["datalist"];
        return formatResult(array($r));
    }
    */
    else if('selectfeatureclass' == $_GET['type']){
        $fpclass = isset($_GET['feature_class']) ? $_GET['feature_class'] : NULL;
        $guid = isset($_GET['guid']) ? $_GET['guid'] : NULL;
        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : NULL;
        $page = $_GET['page']-1; //页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;
        $result = getFeatureClass($startnum, $pagesize, false, $fieldname, null, $guid,NULL,$fpclass);
        $r['categoryname'] = "";
        $r['count'] = $result["totalcount"];
        //$r['countList'] = $result["datalist"];
        $tmpArr = array();
        foreach($result['datalist'] as $key=>$item){
            if(!empty($item['feature_pclass'])){
                $tmpF = array();
                /*
                $tmp = str_replace("#", "", str_replace("##", ",", $item['feature_class']));
                $strarr = explode(",", $tmp);
                $tmpF['feature_class'] = count($strarr) > 1 ? $strarr[1] : $strarr[0];
                 */
                $tmpF['feature_class'] = $item['feature_class'];
                $tmpF['feature_field'] = $item['feature_field'];
                $tmpF['feature_keyword'] = $item['feature_keyword'];
                $tmpF['guid'] = $item['guid'];
                $tmpF['feature_pclass'] = $item['feature_pclass'];
                $tmpF['old_feature'] = true;
                $tmpArr[] = $tmpF;
            }
            else{
                $pclass = getFeatureKeyword(0, 1, $fieldname, NULL, NULL, NULL, NULL, $item['feature_father_guid']);
                if(count($pclass['datalist']) > 0){
                    $item['feature_pclass'] = $pclass['datalist'][0]['feature_class'];
                }
                $tmpArr[] = $item;
            }
        }
        $r['countList'] = $tmpArr;
        return formatResult(array($r));
    }
    else if('selectguidbyfeature_class' == $_GET['type']){
        $fclass = isset($_GET['feature_class']) ? $_GET['feature_class'] : NULL;
        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : NULL;
        $startnum = NULL;
        $pagesize = NULL;
        $result = getFeatureClass($startnum, $pagesize, NULL, $fieldname, NULL, NULL, NULL, $fclass);

        $r['categoryname'] = "";
        $r['count'] = $result["totalcount"];
        $tmpArr = array();
        foreach($result['datalist'] as $key=>$item){
            if(!empty($item['feature_class'])){
                $tmpArr[] = $item;
            }
        }
        $r['countList'] = $tmpArr;
        return formatResult(array($r));
    }
    else if('selectfeatuebyguid' == $_GET['type']){
        $fieldname = isset($_GET["fieldname"]) ? $_GET['fieldname'] : NULL;
        $guid = isset($_GET['guid']) ? $_GET['guid'] : NULL;
        $startnum = NULL;
        $pagesize = NULL;
        $result = getFeatureClass($startnum, $pagesize, NULL, $fieldname, NULL, NULL, $guid);
        $r['categoryname'] = "";
        $r['count'] = $result["totalcount"];
        $tmpArr = array();
        foreach($result['datalist'] as $key=>$item){
            if(!empty($item['feature_class'])){
                $tmpArr[] = $item;
            }
        }
        $r['countList'] = $tmpArr;
        return formatResult(array($r));
    }
    else if('selectrepost' == $_GET['type']){
        $qp= isset($_GET['param']) ? $_GET["param"] : "";
        $page = $_GET['page']-1;//页码显示为1，solr需要从0开始
        $pagesize = $_GET['pagesize'];
        $startnum = $page * $pagesize;
        $url = "q=".$qp."&start=".$startnum."&rows=".$pagesize."&facet=off";
        $nameresult = solrRequest($url, "response", "query");
        if(isset($nameresult['error'])){
            return $nameresult;
        }
        $res["categoryname"] = "";
        $res["numFound"] = $nameresult['numFound'];
        $res["docs"] = $nameresult['docs'];
        return formatResult(array($res));
    }
} //solragentFun end;
?>
