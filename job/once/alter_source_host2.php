<?php
include_once( 'includes.php' );

initLogger(LOGNAME_MIGRATEDATA);
$updatesolr = true;
$end = 0;
$start = 0;
$each_count = 1000;
if(isset($_SERVER['argc']) && $_SERVER['argc'] > 1){
    $each_count = (int)$argv[1];
}
echo "INFO(".date("Y-m-d H:i:s")."): start from:{$start}, each count:{$each_count}\n";
echo "INFO(".date("Y-m-d H:i:s")."): begin alter data (".date("Y-m-d H:i:s").")\n";
$totalnum = solr_select_conds(array('guid','sourceid'), "created_at:*+AND+!source_host:*", 0, 0);
if($end == 0){
	$end = $totalnum;
}
echo "INFO(".date("Y-m-d H:i:s")."): total alter article data (".$totalnum.")\n";
$sourceurlcache = array();

while(1){
	$totalnum = solr_select_conds(array('guid','sourceid'), "created_at:*+AND+!source_host:*", 0, 0);
	if($totalnum == 0){
		echo "INFO(".date("Y-m-d H:i:s")."): reach end \n";
		break;
	}
	echo "INFO(".date("Y-m-d H:i:s")."): remaining ".$totalnum."\n";
	$s_time = microtime_float();
	//每次查询还没有修改source_host的数据
	$qr = solr_select_conds(array('guid','sourceid'), "created_at:*+AND+!source_host:*", 0, $each_count, "created_at+asc");
	if($qr === false){
		echo "ERROR(".date("Y-m-d H:i:s")."): 从solr去数据出错 \n";
		echo "INFO(".date("Y-m-d H:i:s")."): failed on number {$seqno}\n";
		exit;
	}
	else{
		$rcnt = count($qr);
		if($rcnt == 0){
			echo "INFO(".date("Y-m-d H:i:s")."):this round result count".$rcnt."";
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
		}
		//更新文章
		if(!empty($solrarticle)){
			$url = SOLR_URL_UPDATE."&commit=true";
		    $solruser_r = handle_solr_data($solrarticle,$url);
		    if($solruser_r === false){
		    	echo "ERROR(".date("Y-m-d H:i:s")."): solrarticle return false";
				exit;
		    }
		    else if($solruser_r !== NULL && is_array($solruser_r)){
		        echo "WARN(".date("Y-m-d H:i:s")."): solrarticle missing records:".var_export($solruser_r, true);
		    }
		}
		unset($solrarticle);
		$e_time = microtime_float();
        echo "INFO(".date("Y-m-d H:i:s")."): this round cost ".($e_time - $s_time)."\n";
	}
}
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";
