<?php
define("SELF","db2solr.php");
define( "DB2SOLR" , 200 );    //通过该标识，获取配置信息和任务信息
define( "CONFIG_TYPE", DB2SOLR);    //需要在include common.php之前，定义CONFIG_TYPE
define( 'TEST_USER_CURRENT', 1 );    //设置使用的测试用户数组中获取用户的位置

include_once( 'includes.php' );
include_once( 'config.php' );    //通过WB_AKEY, WB_SKEY获取对应的测试用户
include_once('weibo_config.php');
include_once( 'weibo_class.php' );
include_once( 'saetv2.ex.class.php' );
include_once( 'weibooauth.php' );


include_once('taskcontroller.php');
initLogger(LOGNAME_SYNC);//使用同步模块的日志配置

//每次查询的微博数
define('STATUS_COUNT', 500);

$currentmachine;//当前机器名称

if(isset($_SERVER['argc']) && $_SERVER['argc']>=1){
    $logger->debug(SELF." - 参数1：".$argv[1]);
    $currentmachine = $argv[1];
}
else{
    $logger->error(SELF." - 未传递参数【machine】");
    exit;
}
$script_start_time = microtime_float();

$count = 0;//总处理次数
$newcount=0;//新抓取的条数
$total_status_count = 0;//已处理条数

$taskstarttime = microtime_float();//任务开始时间
$maxhandletime=0;
$minhandletime=999999;//最大最小每次处理时间
$accessapicount = 0;//访问API次数
$ignorecount_1 = 0;//有资源时，执行忽略的数据条数
$ignorecount_2 = 0;//无资源时，执行忽略的数据条数
$notexistcount = 0;//原创被删除的个数
$privatecount = 0;//原创被屏蔽的个数
$errorcount = 0;//最终处理失败的
$iscomplete = true;//是否完成
$notexsistoriginal = array();//已被删除的原创id数组
$privateoriginal = array();//被屏蔽的原创id数组
$res_machine;
$currexec_hasresource = false;//当前是否有资源

$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);
try{
    //首先获取等待启动的任务
    $task = getWaitingTask(TASKTYPE_ANALYSIS,TASK_SYNC);
    //将任务插入排队表，等待机器资源
    if(!empty($task)){
        $task->machine=$currentmachine;
        queueTask($task);
        $logger->debug(SELF." - 将任务插入排队表");
    }
    else{
        $logger->debug(SELF." - 未找到待启动任务,查询排队任务");
    }

    //获取当前机器等待排队的任务
    $task = getQueueTask($currentmachine);
    if(empty($task)){
        $logger->debug(SELF." - 未获取到排队任务，退出");
        exit;
    }
    $logger->debug(SELF." - 获取到排队任务，任务ID：".$task->id);
    $task->machine = $currentmachine;//获取指定的机器资源
    //获取机器资源
    $res_machine = applySpecificResource(USETYPE_CONCURRENT, RESOURCE_TYPE_MACHINE, $currentmachine, NULL,NULL,$task->tasklevel,$task->queuetime);
    if(!$res_machine){//未获取到资源
        $logger->info(SELF." - 未获取到机器资源【{$currentmachine}】");
        exit;
    }
    //$logger->info(SELF." - 获取到机器资源【{$currentmachine}】");
    unQueueTask($task->id);//获取到资源，解除排队
    $logger->debug(SELF." - 获取到机器资源{$task->machine}，解除排队");
    startTask($task);//启动任务
    $logger->debug(SELF." - 启动任务");
}
catch(Exception $ex){
    fatalTask($task);
    $logger->fatal(SELF." - ".$ex->getMessage());
    if(!empty($res_machine)){
        releaseResource($res_machine);
    }
    exit;
}
//key保存sourceid，value保存sourcetype
$allsourcetype = get_all_source();//获取所有source

//$sqlwhsource = empty($task->taskparams->source) ? "" : " and weibo_new.sourceid=".$task->taskparams->source;
//一次向solr发送的微博条数
$status_count = empty($task->taskparams->each_count)? STATUS_COUNT : $task->taskparams->each_count;
//最大时间
$analysistime = empty($task->taskparams->analysistime) ? time() : $task->taskparams->analysistime;
//$analysistime = time();
$sqlwhtime = " and weibo_new.analysis_time < {$analysistime}";
if(!empty($task->taskparams->minanalysistime)){//最小时间
    $sqlwhtime .= " and weibo_new.analysis_time > {$task->taskparams->minanalysistime}"; 
}
$logger->info(SELF." - 启动时间：".time()." analysistime:{$analysistime}");
$selectfield = "user_new.screen_name,user_new.created_at as register_time,user_new.followers_count,user_new.friends_count,user_new.verified,user_new.gender,weibo_new.*";
$selecttable = "weibo_new, user_new";
$ischeckresource = true;//是否退出
$needqueue = false;// 是否排队
while (1)
{
    $needqueue = false;
    $ignoreids = "";//忽略的微博ID列表，逗号分隔
    $notexistids = "";//被原创删除的转发ids
    $privateids = "";//原创被屏蔽的转发ids
    $loopstarttime = microtime_float();
    try{
        $completeIDs = '';
        $loopnewcount=0;//本次新抓取条数
        
        $sqlwhere = $sqlwhtime;//条件
        $sourceList = array();
        if($ischeckresource){//需要预处理检查资源
            $sourceList = preProccess();//得到有资源的source
        }
        if(count($sourceList) > 0){//有资源的
            $sids = implode(',', $sourceList);
            $sqlwhere .= " and weibo_new.sourceid in ({$sids}) limit {$ignorecount_1},{$status_count}";
            $currexec_hasresource = true;
            $logger->info(SELF." 源{$sids}有资源");
        }
        else{
            $sqlwhere .= " and analysis_status != ".ANALYSIS_STATUS_NEEDORG."  limit 0,{$status_count}";//不限制源
            $currexec_hasresource = false;
            $logger->info(SELF." 所有源无资源");
        }
        $sql = "select {$selectfield} from {$selecttable} where weibo_new.userid = user_new.id {$sqlwhere}";
        $logger->debug(SELF." - 查询数据sql：{$sql}");
        $start_time = microtime_float();

        $query = mysql_query($sql);
        $end_time = microtime_float();
        $difftime = $end_time - $start_time;
        $logger->info(SELF." - 查询数据 sql时间：{$difftime}");
        //take_notes_sql_time($start_time, $end_time, "sql is {$sql}",
        //    __FILE__, __LINE__, TAKE_NOTES_SQL_SELECT);

        if(!$query)
        {
            $logger->error(SELF." - sql is ".$sql." mysql error is".mysql_error());
            $iscomplete = false;
            break;
        }
        else if (!($num = mysql_num_rows($query)))
        {
            $logger->info("data's count is 0");
            //$needqueue = true;
            break;            
        }
        else
        {
            if ($num != $status_count)
            {
                /*
                 * 当查询条数小于$status_count时，
                 * 意味着查询完全部数据，记录日志
                 */
                $logger->info("data's count is {$num}");
            }
            unset($ms);
            $ms = array();
            $unsort_ms = array();
            $logger->debug(SELF." - 将数据库中的数据转换为senddata");
            while ($result = mysql_fetch_array($query, MYSQL_ASSOC))
            {
                $tmp_ds = setSendData($result);
                if($tmp_ds !== false){
                    $unsort_ms[] = $tmp_ds;
                }
            }
            //将原创放到转发前面
            foreach($unsort_ms as $ms_key => $ms_value){
                if($ms_value["content_type"] == 0){//判断是否原创
                    array_unshift($ms,$ms_value);
                }
                else{
                    array_push($ms,$ms_value);
                }
            }
            unset($unsort_ms);
            if(!empty($ms))
            {
                $start_time = microtime_float();
                $handle_solr_data_arr = handle_solr_data($ms);
                $end_time = microtime_float();
                $logger->info(SELF." - handle_solr_data调用时间(".count($ms)."条)：".($end_time - $start_time));
                if ($handle_solr_data_arr === false)
                {
                    $logger->error(SELF." - handle_solr_data返回false");
                    $iscomplete = false;
                    break;
                }
                else if ($handle_solr_data_arr === NULL)
                {
                    $logger->debug(SELF." - 发送完成全部成功");
                    //发送完成
                    foreach($ms as $ms_key => $ms_value){
                        $completeIDs .= "'".$ms_value['id']."',";
                    }
                    $completeIDs = substr($completeIDs,0,-1);
                    $start_time = microtime_float();
                    updateDataAnalysisTime($completeIDs);
                    $end_time = microtime_float();
                    $logger->debug(SELF." - updateDataAnalysisTime调用时间：".($end_time - $start_time));
                    $total_status_count += count($ms);
                }
                else
                {
                    $logger->warn(SELF." - solr中未找到原创的转发微博：".count($handle_solr_data_arr)."条");
                    //$logger->warn(SELF." - solr中未找到原创的转发微博：".var_export($handle_solr_data_arr,true));
                    $tmp_ms = array();
                    foreach ($ms as $ms_key => $ms_value)
                    {
                        $temp_isin = false;
                        foreach ($handle_solr_data_arr as $status_id)
                        {
                            if ($ms_value['id'] == $status_id)
                            {
                                $temp_isin = true;
                                $tmp_ms[] = $ms_value;
                                break;
                                //unset($ms[$ms_key]);
                            }
                        }
                        if(!$temp_isin){
                            $completeIDs .= "'".$ms_value['id']."',";
                        }
                    }
                    $total_status_count += count($ms) - count($handle_solr_data_arr);
                    $completeIDs = substr($completeIDs,0,-1);
                    $start_time = microtime_float();
                    updateDataAnalysisTime($completeIDs);
                    $end_time = microtime_float();
                    $logger->debug(SELF." - updateDataAnalysisTime调用时间：".($end_time - $start_time));
                    $original = array();//存储原创
                    $logger->debug(SELF." - 开始获取原创");
                    foreach ($tmp_ms as $tmp_ms_key => $tmp_ms_value)
                    {
                        if ($tmp_ms_value['retweeted_status'])
                        {
                            //检查原创是否已在本轮抓取过
                            foreach ($original as $k => $v){
                                if($v['id'] == $tmp_ms_value['retweeted_status']){
                                    continue 2;
                                }
                            }
                            //检查原创是否已被删除
                            if(in_array($tmp_ms_value['retweeted_status'],$notexsistoriginal)){
                                $notexistids .= "'".$tmp_ms_value['id']."',";
                                //$tmp_ms[$tmp_ms_key]['retweeted_status'] = '';
                                $tmp_ms[$tmp_ms_key]['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST;
                                continue;
                            }
                            //检查原创是否已被屏蔽
                            if(in_array($tmp_ms_value['retweeted_status'],$privateoriginal)){
                                $privateids .= "'".$tmp_ms_value['id']."',";
                                //$tmp_ms[$tmp_ms_key]['retweeted_status'] = '';
                                $tmp_ms[$tmp_ms_key]['analysis_status'] = ANALYSIS_STATUS_ORGPRIVATE;
                                continue;
                            }
                            $inner_sql = "select {$selectfield} from {$selecttable}
                            where  weibo_new.userid = user_new.id and weibo_new.id='{$tmp_ms_value['retweeted_status']}'";
                            $inner_query = mysql_query($inner_sql);
                            if(!$inner_query)
                            {
                                $logger->error(SELF." - 查询原创异常".$inner_sql." - mysql error is ".mysql_error());
                                continue;
                            }
                            else if (!($num = mysql_num_rows($inner_query)))
                            {
                                $logger->debug(SELF." - 数据库中不存在原创:".$tmp_ms_value['retweeted_status']);
                                if(empty($tmp_ms_value['sourceid'])){
                                    $logger->error(SELF." - sourceid为空：".var_export($tmp_ms_value,true));
                                    continue;
                                }
                                
                                $task->tasksource = $tmp_ms_value['sourceid'];
                                $task->sourcetype = $allsourcetype[$tmp_ms_value['sourceid']];
                                $hasresource = false;
                                //首先获取IP
                                $getiptime = date('Y-m-d H:00:00');
                                $res_ip = getResource(RESOURCE_TYPE_IP, $tmp_ms_value['sourceid'],NULL,$task->tasklevel,$task->queuetime);
                                $res_acc = null;
                                if(!empty($res_ip)){//获取到IP后，申请相同app的帐号资源
                                    $res_acc = getResource(RESOURCE_TYPE_ACCOUNT, $tmp_ms_value['sourceid'],$res_ip->appkey,$task->tasklevel,$task->queuetime);
                                    if(empty($res_acc)){//未获取到帐号资源
                                        if($getiptime == date('Y-m-d H:00:00')){
                                            rollbackSpecificResource($res_ip->id);//回滚IP资源
                                        }
                                        releaseResource($res_ip->id);//释放IP资源
                                        $logger->info(SELF." 未获取到sourceid为{$tmp_ms_value['sourceid']}的appkey为{$res_ip->appkey}的帐号资源");
                                    }
                                    else{
                                        $hasresource = true;//获取到资源
                                        if(empty($oAuthThird) || $oAuthThird->sourceid != $tmp_ms_value['sourceid'] || $oAuthThird->username != $res_acc->resource 
                                           || $oAuthThird->appkey != $res_acc->appkey){
                                            init_weiboclient($tmp_ms_value['sourceid'], $res_acc->resource, $res_acc->appkey);
                                        }
                                    }
                                }
                                else{
                                    $logger->info(SELF." 未获取到sourceid为{$tmp_ms_value['sourceid']}的IP资源");
                                }
                                if($hasresource){
                                    $need_insert = true;
                                    $logger->debug(SELF." - 获取资源完毕，开始调用API-show_status");
                                    $start_time = microtime_float();
                                    $weibo_info = $oAuthThird->show_status($tmp_ms_value['retweeted_status']);
                                    $end_time = microtime_float();
                                    //$logger->info(SELF." - 调用API花费时间：".($end_time-$start_time));
                                    $accessapicount++;
                                    
                                    releaseResource($res_ip->id);//释放资源
                                    releaseResource($res_acc->id);//释放资源
                                    
                                    if ($weibo_info === false || $weibo_info === null){
                                        $ignoreids .= "'".$tmp_ms_value['id']."',";
                                        unset($tmp_ms[$tmp_ms_key]);
                                        plusIgnoreCount();
                                        set_log(ERROR, "调用API失败【{$tmp_ms_value['retweeted_status']}】：api show_status:Error occured", __FILE__, __LINE__);
                                        continue;
                                    }
                                    if (isset($weibo_info['error_code']) && isset($weibo_info['error'])){
                                        if($weibo_info['error_code'] == ERROR_CONTENT_NOT_EXIST){//原创不存在
                                            $logger->warn(SELF." - 原创{$tmp_ms_value['retweeted_status']}已被作者删除");
                                            $need_insert = false;
                                            $notexsistoriginal[] = $tmp_ms_value['retweeted_status'];
                                            $notexistids .= "'".$tmp_ms_value['id']."',";
                                            $notexistcount++;//原创被删除个数
                                            //$tmp_ms[$tmp_ms_key]['retweeted_status'] = '';
                                            $tmp_ms[$tmp_ms_key]['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST; 
                                        }
                                        else if($weibo_info['error_code'] == ERROR_CONTENT_PRIVATE){//被屏蔽的微博
                                            $logger->warn(SELF." - 原创{$tmp_ms_value['retweeted_status']}不适合公开");
                                            $need_insert = false;
                                            $privateoriginal[] = $tmp_ms_value['retweeted_status'];
                                            $privateids .= "'".$tmp_ms_value['id']."',";
                                            $privatecount++;//原创被屏蔽个数
                                            //$tmp_ms[$tmp_ms_key]['retweeted_status'] = '';
                                            $tmp_ms[$tmp_ms_key]['analysis_status'] = ANALYSIS_STATUS_ORGPRIVATE;
                                        }
                                        else{
                                            if($weibo_info['error_code'] == ERROR_IP_OUT_LIMIT){
                                                //IP使用超出
                                                disableResource($res_ip->id);
                                            }
                                            else if($weibo_info['error_code'] == ERROR_USER_OUT_LIMIT){
                                                //帐号使用超出
                                                disableResource($res_acc->id);
                                            }
                                            $logger->error(SELF." - 获取{$tmp_ms_value['id']}的原创{$tmp_ms_value['retweeted_status']}，调用API异常：".$weibo_info['error_code']."-".$weibo_info['error']);
                                            $ignoreids .= "'".$tmp_ms_value['id']."',";
                                            unset($tmp_ms[$tmp_ms_key]);
                                            plusIgnoreCount();
                                            continue;
                                        }
                                        /*if(strpos($weibo_info['error'], 'user requests out of rate limit') !== false){//帐号达到上限
                                         //将帐号临时禁用

                                         break;
                                         }*/
                                        //continue;
                                    }
                                    if ($need_insert)
                                    {
                                        if(!isset($weibo_info['userid'])){
                                            $notexsistoriginal[] = $tmp_ms_value['retweeted_status'];
                                            $notexistids .= "'".$tmp_ms_value['id']."',";
                                            //$tmp_ms[$tmp_ms_key]['retweeted_status'] = '';
                                            $tmp_ms[$tmp_ms_key]['analysis_status'] = ANALYSIS_STATUS_ORGNOTEXIST;
                                            $notexistcount++;//原创被删除个数
                                            continue;
                                        }
                                        
                                        $logger->debug(SELF." - 需要将API获取的原创插入数据库");
                                        unset($weibos_info);
                                        $weibos_info = array();
                                        unset($user_data);
                                        $user_data = array();
                                        //首先将用户入库，因为在数据获取原创时，由于联合查询，有可能原创在数据库存在，但作者信息不存在，所以查询不到结果
                                        //可以保证再次在数据查询原创时，有记录
                                        if(!empty($weibo_info['user'])){
                                            $user_data[] = $weibo_info['user'];
                                            insert_user(NULL, NULL, $user_data, 0, 'show_status',$task->tasksource, true);
                                            $logger->debug(SELF." - 将用户插入数据库");
                                        }
                                        else{
                                            $logger->warn(SELF." - 在{$tmp_ms_value['id']}的原创中未找到user信息：".var_export($weibo_info,true));
                                        }
                                        
                                        //补全page_url
                                        $weibo_info['page_url'] = weibomid2Url($weibo_info['user']['id'], $weibo_info['mid'], 1);
                                        $weibo_info['source_host'] = "weibo.com";	
                                        $weibo_info['user']['page_url'] = userid2Url($weibo_info['user']['id'],1);
                                        if(isset($weibo_info['retweeted_status'])){
                                            $weibo_info['retweeted_status']['source_host'] = "weibo.com";
                                            $weibo_info['retweeted_status']['page_url'] = weibomid2Url($weibo_info['retweeted_status']['user']['id'], $weibo_info['retweeted_status']['mid'], 1);
                                            $weibo_info['retweeted_status']['user']['page_url'] = userid2Url($weibo_info['retweeted_status']['user']['id'],1);
                                        }

                                        $weibos_info[] = $weibo_info;
                                        $weibos_info = inner_insert_status($weibos_info, "show_status",$task->tasksource,true);
                                        if($weibos_info['result']){
                                            $loopnewcount++;//本次新抓条数
                                            $newcount++;//新抓取的条数
                                            $original[] = $weibos_info[0];//入库成功后，将返回符合格式的solr数据
                                        }
                                        else{
                                            //入库未成功，有可能是数据库已存在原创
                                            //有可能是微博被删除，但能从API获取
                                            //再次查询原创，目的：获取符合solr格式的数据
                                            $inner_query2 = mysql_query($inner_sql);
                                            if(!$inner_query2){
                                                $ignoreids .= "'".$tmp_ms_value['id']."',";
                                                unset($tmp_ms[$tmp_ms_key]);
                                                plusIgnoreCount();
                                                $logger->error(SELF." - 再次查询原创异常".$inner_sql." - mysql error is ".mysql_error());
                                            }
                                            else{
                                                $tmp_result2 = mysql_fetch_array($inner_query2, MYSQL_ASSOC);
                                                $tmp_ds2 = setSendData($tmp_result2);
                                                if(!empty($tmp_ds2)){
                                                    $original[] =$tmp_ds2;
                                                }
                                                else{
                                                    //$ignoreids .= "'".$tmp_ms_value['id']."',";
                                                    unset($tmp_ms[$tmp_ms_key]);
                                                    plusIgnoreCount();
                                                    $logger->warn(SELF." - 再次查询{$tmp_ms_value['id']}的原创{$tmp_ms_value['retweeted_status']}失败，忽略");
                                                }
                                            }
                                        }

                                    }
                                }
                                else{
                                    //未获取资源，忽略
                                    $ignoreids .= "'".$tmp_ms_value['id']."',";
                                    unset($tmp_ms[$tmp_ms_key]);
                                    plusIgnoreCount();
                                }
                            }
                            else
                            {
                                $tmp_result = mysql_fetch_array($inner_query, MYSQL_ASSOC);
                                //$tmp_ms[$tmp_ms_key][RET_TO_SOLR_ANCESTOR_TEXT] = $tmp_result[0];
                                $tmp_ds = setSendData($tmp_result);
                                if($tmp_ds !== false){
                                    $original[] = $tmp_ds;
                                }
                            }
                        }
                        else
                        {
                            $tmp_ms[$tmp_ms_key][RET_TO_SOLR_ANCESTOR_TEXT] = '';
                        }
                    }
                    $time1 = microtime_float();
                    $ignoreids = substr($ignoreids, 0,-1);
                    updateDataAnalysisStatus($ignoreids,ANALYSIS_STATUS_NEEDORG);//将忽略的数据状态置为（未取到原创）
                    $notexistids = substr($notexistids,0,-1);
                    updateDataAnalysisStatus($notexistids,ANALYSIS_STATUS_ORGNOTEXIST);//将原创被删除的数据置为（原创不存在）
                    $privateids = substr($privateids, 0,-1);
                    updateDataAnalysisStatus($privateids,ANALYSIS_STATUS_ORGPRIVATE);//将原创被删除的数据置为（原创被屏蔽）
                    $time2 = microtime_float();
                    $logger->info(SELF." 设置analysisstatus时间：".($time2-$time1));
                    $tmp_ms = array_merge($original,$tmp_ms);// 将新获取的原创与未处理的转发合并

                    if(count($tmp_ms) > 0 ){
                        $logger->debug(SELF." - 第二次发送");
                        $start_time = microtime_float();
                        $handle_solr_data_arr_2 = handle_solr_data($tmp_ms);
                        $end_time = microtime_float();
                        $logger->info(SELF." - 再次发送handle_solr_data时间(".count($tmp_ms)."条)：".($end_time - $start_time));
                        if($handle_solr_data_arr_2 ===  false){
                            $logger->error(SELF." - handle_solr_data返回false");
                            break;
                        }
                        else if ($handle_solr_data_arr_2 === NULL){
                            $logger->debug(SELF." - 再次调用handle_solr_data处理成功：");
                            $completeIDs = '';
                            foreach($tmp_ms as $ms_key => $ms_value){
                                $completeIDs .= "'".$ms_value['id']."',";
                            }
                            $completeIDs = substr($completeIDs,0,-1);

                        }
                        else{
                            $completeIDs = '';
                            foreach($tmp_ms as $ms_key => $ms_value){
                                $temp_in = false;
                                foreach($handle_solr_data_arr_2 as $status_id){
                                    if($ms_value['id'] == $status_id){
                                        $temp_in = true;
                                        break;
                                    }
                                }
                                if(!$temp_in){
                                    $completeIDs .= "'".$ms_value['id']."',";
                                }
                            }
                            $completeIDs = substr($completeIDs,0,-1);
                            $errorcount += count($handle_solr_data_arr_2);
                            $oerrorids = implode("','", $pieces);
                            if(!empty($oerrorids)){
                                $oerrorids = "'".$oerrorids."'";
                            }
                            updateDataAnalysisStatus($oerrorids, ANALYSIS_STATUS_OTHERERROR);
                            $logger->warn(SELF." - 再次调用handle_solr_data处理失败的：".var_export($handle_solr_data_arr_2,true));
                        }
                        $start_time = microtime_float();
                        updateDataAnalysisTime($completeIDs);
                        $end_time = microtime_float();
                        $logger->debug(SELF." - 更新数据updateDataAnalysisTime时间：".($end_time - $start_time));
                        $total_status_count += count($tmp_ms) - count($handle_solr_data_arr_2);//加总数
                    }

                }
            }
            //$status_cursor += $status_count;
            //$total_status_count += count($ms);
        }
        $loopendtime = microtime_float();
        $loopdiff = $loopendtime - $loopstarttime;
        if($loopdiff > $maxhandletime){
            $maxhandletime = $loopdiff;
        }
        if($loopdiff < $minhandletime){
            $minhandletime = $loopdiff;
        }
        $count++;
        $logger->info(SELF."--------执行次数：{$count},本次处理时间：{$loopdiff}, 本次新抓取条数：{$loopnewcount}，总访问API次数：{$accessapicount}，总新抓取条数：{$newcount},总处理条数： {$total_status_count}，总忽略条数：".getIgnoreCount()."，总被删除条数：{$notexistcount}，总屏蔽条数：{$privatecount}，solr二次处理失败条数：{$errorcount}，总处理条数： {$total_status_count}");
        $task->datastatus = $total_status_count;

        $st = getTaskStatus($task->id);
        if($st == -1){
            $iscomplete = false;
            break;
        }
        //if($total_status_count >= 10000){
        //    break;//test
        //}
    }
    catch(Exception $ex){
        $logger->fatal("db2solr - 异常：".$ex->getMessage());
        $iscomplete = false;
        break;
    }
}
$taskendtime = microtime_float();
$alltime = $taskendtime-$taskstarttime;//总时间
$avgtime = $total_status_count == 0 ? 0 : $alltime / $total_status_count;//每条处理时间
$avgtime1=$count == 0 ? 0 : $alltime / $count;//每次处理时间
$logger->info(SELF."--------总执行次数：{$count}, 访问API次数：{$accessapicount}，新抓取条数：{$newcount},总处理条数： {$total_status_count}，总处理时间：{$alltime}，平均每次处理时间：{$avgtime1}，平均每条处理时间：{$avgtime}，每次最大处理时间：{$maxhandletime}，每次最小处理时间：{$minhandletime}");
try{
    releaseResource($res_machine);
    
    //判断是否有忽略的数据
    if(getIgnoreCount() > 0 || $needqueue){
        queueTask($task);
        $logger->info(SELF." - 有忽略的数据".getIgnoreCount()."条，进入排队，退出");
    }
    else if($iscomplete){
        completeTask($task);
        $logger->info(SELF." - 任务完成，退出");
    }
    else{
        stopTask($task);
        $logger->info(SELF." - 任务未完成，退出");
    }
}
catch(Exception $ex){
    fatalTask($task);
    $logger->fatal("db2solr - 循环外异常：".$ex->getMessage());
}

//预处理-------返回有资源的source--------------------
function preProccess(){
    global $task,$allsourcetype;
    $sources = array();
    if(empty($task->taskparams->source)){//未指定源时，循环所有源
        foreach ($allsourcetype as $k => $v){
            $check_ip = checkResource(RESOURCE_TYPE_IP, $k,$task->tasklevel,$task->queuetime,NULL);//检查IP资源
            if(!empty($check_ip)){
                $check_acc = checkResource(RESOURCE_TYPE_ACCOUNT, $k,$task->tasklevel,$task->queuetime,$check_ip);//检查帐号资源
                if(!empty($check_acc)){
                    $sources[] = $k;//返回有资源的ID
                } 
                else{
                    continue;                        
                }
            }
            else{
                continue;
            }
        }
    }
    else{//指定了源
        $check_ip = checkResource(RESOURCE_TYPE_IP, $task->taskparams->source,$task->tasklevel,$task->queuetime,NULL);//检查IP资源
        if(!empty($check_ip)){
            $check_acc = checkResource(RESOURCE_TYPE_ACCOUNT, $task->taskparams->source,$task->tasklevel,$task->queuetime,$check_ip);//检查帐号资源
            if(!empty($check_acc)){
                $sources[] = $task->taskparams->source;//返回有资源的ID
            } 
        }
    }
    return $sources;
}

//增加忽略数
function plusIgnoreCount(){
    global $currexec_hasresource,$ignorecount_1,$ignorecount_2;
    if($currexec_hasresource){
        $ignorecount_1++;
    }
    else{
        $ignorecount_2++;
    }
}
//获取总忽略数
function getIgnoreCount(){
    global $ignorecount_1,$ignorecount_2;
    return $ignorecount_1+$ignorecount_2;
}
