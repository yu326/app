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
$totalnum = solr_select_conds(array('guid','sourceid'), "created_at:*+AND+sourceid:*", 0, 0);
if($end == 0){
	$end = $totalnum;
}
echo "INFO: total alter article  data (".$totalnum.")\n";
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
	$qr = solr_select_conds(array('guid','sourceid'), "created_at:*+AND+sourceid:*", $start, $each_count, "created_at+asc");
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
		$solrarticle = array();
		foreach($qr as $qi=>$rec){
			if(isset($rec['sourceid'])){
				if(!empty($sourceurlcache)){
					$found = false;
					foreach($sourceurlcache as $si=>$sitem){
						if($sitem['sourceid'] == $rec['sourceid']){
							$sourceurl = $sitem['sourceurl'];
							$found = true;
							break;
						}
					}
					if(!$found){
						$sourcearr = get_source_url($rec['sourceid']);
						if(!empty($sourcearr)){
							$sourceurl = $sourcearr[0];
							$sourceurlcache[] = array('sourceid'=>$rec['sourceid'], 'sourceurl'=>$sourceurl);
						}
					}
				}
				else{
					$sourcearr = get_source_url($rec['sourceid']);
					if(!empty($sourcearr)){
						$sourceurl = $sourcearr[0];
						$sourceurlcache[] = array('sourceid'=>$rec['sourceid'], 'sourceurl'=>$sourceurl);
					}
				}
			}
			$solra= array();
			if(!empty($sourceurl)){
				$solra['guid'] = $rec['guid'];
				$solra['source_host'] = $sourceurl;
				if($updatesolr){
					$solrarticle[] = $solra;
				}
			}
			$seqno++;
		}
		//更新文章
		if(!empty($solrarticle)){
			$url = SOLR_URL_UPDATE."&commit=true";
		    $solrarticle_r = handle_solr_data($solrarticle,$url);
		    if($solrarticle_r === false){
		    	echo "ERROR: solrarticle return false";
				echo "INFO: failed on number {$seqno}\n";
				exit;
		    }
		    else if($solrarticle_r !== NULL && is_array($solrarticle_r)){
		        echo "WARN: solrarticle missing records:".var_export($solrarticle_r, true);
		    }
		}
		unset($solrarticle);
		$start += $each_count - $shift;
		$e_time = microtime_float();
        echo "INFO: this round cost ".($e_time - $s_time)."\n";
	}
}
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";

