<?php
include_once( 'includes.php' );

initLogger(LOGNAME_MIGRATEDATA);
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
echo "INFO: begin alter data (".date("Y-m-d H:i:s").")\n";
$totalnum = solr_select_conds(array('guid','users_sourceid'), "users_user_updatetime:*+AND+users_sourceid:*", 0, 0);
if($end == 0){
	$end = $totalnum;
}
echo "INFO: total alter user data (".$totalnum.")\n";
$sourceurlcache = array();

while(1){
	if(!empty($end) && $start >= $end){
		echo "INFO: reach end number {$end}\n";
		break;
	}
	echo "INFO: update ".$start." ~ ".($start + $each_count)."\n";
	$s_time = microtime_float();
	$seqno = $start;
	$shift = 0;
	$qr = solr_select_conds(array('guid','users_sourceid'), "users_user_updatetime:*+AND+users_sourceid:*", $start, $each_count, "users_user_updatetime+asc");
	if($qr === false){
		echo "ERROR: 从solr去数据出错 \n";
		echo "INFO: failed on number {$seqno}\n";
		exit;
	}
	else{
		$rcnt = count($qr);
		if($rcnt == 0){
			echo "this round result count".$rcnt."";
			break;
		}
		$solruser = array();
		foreach($qr as $qi=>$rec){
			if(isset($rec['users_sourceid'])){
				if(!empty($sourceurlcache)){
					$found = false;
					foreach($sourceurlcache as $si=>$sitem){
						if($sitem['users_sourceid'] == $rec['users_sourceid']){
							$sourceurl = $sitem['sourceurl'];
							$found = true;
							break;
						}
					}
					if(!$found){
						$sourcearr = get_source_url($rec['users_sourceid']);
						if(!empty($sourcearr)){
							$sourceurl = $sourcearr[0];
							$sourceurlcache[] = array('users_sourceid'=>$rec['users_sourceid'], 'sourceurl'=>$sourceurl);
						}
					}
				}
				else{
					$sourcearr = get_source_url($rec['users_sourceid']);
					if(!empty($sourcearr)){
						$sourceurl = $sourcearr[0];
						$sourceurlcache[] = array('users_sourceid'=>$rec['users_sourceid'], 'sourceurl'=>$sourceurl);
					}
				}
			}
			$solra= array();
			if(!empty($sourceurl)){
				$solra['guid'] = $rec['guid'];
				$solra['users_source_host'] = $sourceurl;
				if($updatesolr){
					$solruser[] = $solra;
				}
			}
			$seqno++;
		}
		//更新文章
		if(!empty($solruser)){
			$url = SOLR_URL_UPDATE."&commit=true";
		    $solruser_r = handle_solr_data($solruser,$url);
		    if($solruser_r === false){
		    	echo "ERROR: solruser return false";
				echo "INFO: failed on number {$seqno}\n";
				exit;
		    }
		    else if($solruser_r !== NULL && is_array($solruser_r)){
		        echo "WARN: solruser missing records:".var_export($solruser_r, true);
		    }
		}
		unset($solruser);
		$start += $each_count - $shift;
		$e_time = microtime_float();
        echo "INFO: this round cost ".($e_time - $s_time)."\n";
	}
}
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";

