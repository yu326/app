<?php
include_once( 'includes.php' );

define('DATABASE_WEIBO_OLD', 'weibo_new');
define('DATABASE_WEIBO_NEW', 'weibo_new_tmp');
$end = 0;// 0 for all records
$start = 0;
$each_count = 100000;
if(isset($_SERVER['argc']) && $_SERVER['argc'] > 1){
    $start = (int)$argv[1];
    if($_SERVER['argc'] > 2){
    	$each_count = (int)$argv[2];
    }
}
echo "INFO: start from:{$start}, each count:{$each_count} (".date("Y-m-d H:i:s").")\n";

$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);

$sql_checkTBname = "SHOW tables like '".DATABASE_WEIBO_NEW."'";
$qr_check = $dsql->ExecQuery($sql_checkTBname);
if(!$qr_check){
    echo "ERROR: ".$dsql->GetError()."\n";
    exit;
}
$rsTBnum = $dsql->GetTotalRow($qr_check);
if($rsTBnum == 0){
    echo "INFO: begin create new table ".DATABASE_WEIBO_NEW."\n";
    $sql_create = "CREATE TABLE IF NOT EXISTS `".DATABASE_WEIBO_NEW."` (
	  `count_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '条数',
	  `guid` VARCHAR(256) DEFAULT NULL COMMENT '唯一ID',
	  `id` char(65) DEFAULT NULL COMMENT '微博ID',
	  `mid` char(65) DEFAULT NULL COMMENT 'mid',
	  `retweeted_status` char(65) DEFAULT NULL COMMENT '原创的ID',
	  `retweeted_mid` char(65) DEFAULT NULL COMMENT '原创的MID',
	  `father_guid` VARCHAR(256) DEFAULT NULL COMMENT '上一级微博唯一ID',
	  `userid` varchar(12) DEFAULT NULL COMMENT '作者信息',
	  `sourceid` int(10) DEFAULT NULL COMMENT '内容来源',
	  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
	  `annotations` char(65) DEFAULT NULL COMMENT '附加信息，微群号对应annotations表',
	  `is_repost` tinyint(4) DEFAULT NULL COMMENT '根据solr要求，添加该字段表示微博是否为转发微博',
	  `isseed` int(11) DEFAULT '0' COMMENT '是否种子微博',
	  `is_bridge_status` smallint(6) DEFAULT NULL COMMENT '0:不是桥接微博；1:是桥接微博',
	  `reposts_count` int(11) DEFAULT NULL COMMENT '微博转发数',
	  `total_reposts_count` int(11) DEFAULT NULL COMMENT '微博总转发数',
	  `direct_reposts_count` int(11) DEFAULT NULL COMMENT '微博直接转发数',
	  `comments_count` int(11) DEFAULT NULL COMMENT '微博评论数',
	  `reach_count` int(11) DEFAULT NULL COMMENT '微博的直接到达数',
	  `total_reach_count` int(11) DEFAULT NULL COMMENT '微博的总到达数',
	  `year` int(11) DEFAULT NULL COMMENT '发微博的年',
	  `month` int(11) DEFAULT NULL COMMENT '发微博的月',
	  `day` int(11) DEFAULT NULL COMMENT '发微博的日',
	  `hour` int(11) DEFAULT NULL COMMENT '发微博的小时',
	  `minute` int(11) DEFAULT NULL COMMENT '发微博的分钟',
	  `second` int(11) DEFAULT NULL COMMENT '发微博的秒',
	  `province_code` char(6) DEFAULT NULL COMMENT '用户所属省',
	  `city_code` char(6) DEFAULT NULL COMMENT '用户所属市',
	  `country_code` char(2) DEFAULT NULL COMMENT '用户所属国家',
	  `district_code` char(6) DEFAULT NULL COMMENT '用户所属区或县',
	  `analysis_status` int(10) NOT NULL DEFAULT '0' COMMENT '分析状态，0：正常，1：没有原创（需要资源去抓取）',
	  `analysis_time` int(11) DEFAULT '0' COMMENT '对微博进行行业和地区分析的时间戳',
	  `repost_trend_cursor` int(11) DEFAULT NULL COMMENT '转发微博的层级',
	  `repost_sinceid` char(65) DEFAULT NULL COMMENT '获取转发时的sinceid',
	  `repost_maxid` char(65) DEFAULT NULL COMMENT '获取转发时的maxid',
	  `repost_trend_update` int(11) DEFAULT NULL COMMENT '获取转发微博的最新时间戳',
	  `comment_sinceid` char(65) DEFAULT NULL COMMENT '获取评论时的idsince',
	  `comment_maxid` char(65) DEFAULT NULL COMMENT '获取评论时的maxid',
	  `comment_updatetime` int(11) DEFAULT NULL COMMENT '获取评论的时间',
	  `interrupt_newtime` int(11) DEFAULT NULL COMMENT '自己作为father时，孩子中最新转发的时间',
	  `interrupt_orig_righttime` int(11) DEFAULT NULL COMMENT '抓取原创的转发，自己作为断档的左侧时，记录该断档右侧的时间',
	  `interrupt_repost_righttime` int(11) DEFAULT NULL COMMENT '抓取转发的转发，自己作为断档的左侧时，记录该断档右侧的时间',
	  `interrupt_user_righttime` int(11) DEFAULT NULL COMMENT '抓取用户的微博，自己作为断档的左侧时，记录该断档右侧的时间',
	  `update_time` int(11) DEFAULT '0' COMMENT '更新转发数等字段的时间',
	  PRIMARY KEY (`count_id`),
	  KEY `analysis_time` (`analysis_time`),
	  KEY `retweeted_status` (`retweeted_status`),
	  KEY `mid` (`mid`),
	  KEY `userid` (`userid`),
	  KEY `id` (`id`),
	  KEY `retweeted_mid` (`retweeted_mid`),
	  KEY `father_guid` (`father_guid`),
	  KEY `guid` (`guid`)
	)
	COLLATE='utf8_general_ci'
	ENGINE=InnoDB
	ROW_FORMAT=DEFAULT
	AUTO_INCREMENT=1";
    $qr_create = $dsql->ExecQuery($sql_create);
    if(!$qr_create){
       echo "ERROR: ".$dsql->GetError()."\n";
       exit;  
    }
}

echo "INFO: begin query total count\n";
$sqlsel = "select count(0) as cnt from ".DATABASE_WEIBO_OLD;
$qrsel = $dsql->ExecQuery($sqlsel);
if(!$qrsel){
   echo "ERROR: ".$dsql->GetError()."\n";
   exit;  
}
else{
    $rs = $dsql->GetArray($qrsel);
    $num = $rs['cnt'];
    echo "INFO: total count {$num}\n";
    echo "INFO: begin export\n";
    while($start < $num){
    	if(!empty($end) && $start >= $end){
    		echo "INFO: reach end number {$end}\n";
			break;
    	}
        echo "INFO: export ".$start." ~ ".($start + $each_count)."\n";
        $s_time = microtime_float();
        $sql = "insert into ".DATABASE_WEIBO_NEW." select `count_id`,
			`guid`,
		  `id`,`mid`,`retweeted_status`,`retweeted_mid`,
		  `father_guid`,
		  `userid`,`sourceid`,`created_at`,`annotations`,`is_repost`,`isseed`,`is_bridge_status`,
		  `reposts_count`,`total_reposts_count`,`direct_reposts_count`,`comments_count`,`reach_count`,`total_reach_count`,
		  `year`,`month`,`day`,`hour`,`minute`,`second`,
		  `province_code`,`city_code`,`country_code`,`district_code`,
		  `analysis_status`,`analysis_time`,
		  `repost_trend_cursor`,`repost_sinceid`,`repost_maxid`,`repost_trend_update`,
		  `comment_sinceid`,`comment_maxid`,`comment_updatetime`,
		  `interrupt_newtime`,`interrupt_orig_righttime`,`interrupt_repost_righttime`,`interrupt_user_righttime`,
		  `update_time`
		  from ".DATABASE_WEIBO_OLD." order by count_id asc limit {$start}, {$each_count}";
        $qr = $dsql->ExecQuery($sql);
        if(!$qr){
           echo "ERROR: ".$sql." ".$dsql->GetError()."\n";
           exit;  
        }
        else{
            $start += $each_count;
        }
        $e_time = microtime_float();
        echo "INFO: this round cost ".($e_time - $s_time)."\n";
    }
    echo "INFO: export completed (".date("Y-m-d H:i:s").")\n";
}
//ALTER TABLE `weibo_new`  RENAME TO `weibo_new_old`;
//ALTER TABLE `weibo_new_tmp`  RENAME TO `weibo_new`;
?>
