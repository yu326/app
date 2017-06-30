<?php
include_once( 'includes.php' );

initLogger(LOGNAME_MIGRATEDATA);

define('DATABASE_WEIBO_NEW', 'weibo_new_tmp');
$updatesolr = true;
$end = 0;
$start = 0;
$each_count = 1000;
if(isset($_SERVER['argc']) && $_SERVER['argc'] > 1){
    $start = (int)$argv[1];
    if($_SERVER['argc'] > 2){
    	$each_count = (int)$argv[2];
    }
}
echo "INFO: start from:{$start}, each count:{$each_count}\n";

$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_WEIBOINFO,FALSE);

$sql_checkTBname = "SHOW tables like '".DATABASE_WEIBO_NEW."'";
$qr_check = $dsql->ExecQuery($sql_checkTBname);
if(!$qr_check){
    echo "ERROR: ".$dsql->GetError()."\n";
    exit;
}
$rsTBnum = $dsql->GetTotalRow($qr_check);
if($rsTBnum == 0){
	echo "ERROR: ".DATABASE_WEIBO_NEW." not exist\n";
    exit;
}

echo "INFO: begin alter data (".date("Y-m-d H:i:s").")\n";
while(1){
	if(!empty($end) && $start >= $end){
		echo "INFO: reach end number {$end}\n";
		break;
	}
	echo "INFO: update ".$start." ~ ".($start + $each_count)."\n";
	$s_time = microtime_float();
	$seqno = $start;
	$shift = 0;
	$sql = "select guid,father_guid,retweeted_status,retweeted_mid,sourceid,repost_trend_cursor
			from ".DATABASE_WEIBO_NEW." where father_guid != ''
			order by count_id asc limit {$start}, {$each_count}";
	$qr = $dsql->ExecQuery($sql);
	if(!$qr){
		echo "ERROR: ".$sql." ".$dsql->GetError()."\n";
		echo "INFO: failed on number {$seqno}\n";
		exit;
	}
	else{
		$rcnt = $dsql->GetTotalRow($qr);
		if($rcnt == 0){
			break;
		}
		$solrdata = array();
		while($rec = $dsql->GetArray($qr)){
			// no father_guid for level 1 repost
			$retweeted_guid = NULL;
			/*if(!empty($rec['retweeted_status'])){
				$retweeted_guid = $rec['sourceid']."_".$rec['retweeted_status'];
			}
			else if(!empty($rec['retweeted_mid'])){
				$retweeted_guid = $rec['sourceid']."m_".$rec['retweeted_mid'];
			}*/
			$retweeted_guid = getOriginalGuidFromSolr($rec);
			if($retweeted_guid === false){
				echo "²éÑ¯Ô­´´guidÊ§°Ü";
				exit;
			}
			$solru = array();
			$solru['guid'] = $rec['guid'];
			$solru['repost_trend_cursor'] = $rec['repost_trend_cursor'];
			$solru['father_id'] = '';
			if($rec['father_guid'] == $retweeted_guid){
				$shift++;
				$sqlu = "update ".DATABASE_WEIBO_NEW." set father_guid = NULL where guid = '{$rec['guid']}'";
				$qru = $dsql->ExecQuery($sqlu);
				if(!$qru){
					echo "ERROR: ".$sqlu." ".$dsql->GetError()."\n";
					echo "INFO: failed on number {$seqno}\n";
					exit;
				}
				$dsql->FreeResult($qru);
			}
			else{
				$solru['father_guid'] = $rec['father_guid'];
			}
			if($updatesolr){
				$solrdata[] = $solru;
			}
			$seqno++;
		}
		if(!empty($solrdata)){
			$url = SOLR_URL_UPDATE."&commit=true";
		    $solr_r = handle_solr_data($solrdata,$url);
		    if($solr_r === false){
		    	echo "ERROR: solr return false";
				echo "INFO: failed on number {$seqno}\n";
				exit;
		    }
		    else if($solr_r !== NULL && is_array($solr_r)){
		        echo "WARN: solr missing records:".var_export($solr_r, true);
		    }
		}
		$dsql->FreeResult($qr);
		unset($solrdata);
		$start += $each_count - $shift;
		$e_time = microtime_float();
        echo "INFO: this round cost ".($e_time - $s_time)."\n";
	}
}
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";

