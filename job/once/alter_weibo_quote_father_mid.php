<?php
/*
 * 有father_guid的但是没有father_floor的，补quote_father_mid
 * 用father_guid查出mid，补上quote_father_mid
 * */

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
$totalnum = solr_select_conds(array('guid'), "sourceid:1+AND+quote_father_mid:*", 0, 0);
if($end == 0){
	$end = $totalnum;
}
echo "INFO(".date("Y-m-d H:i:s")."): total alter article data (".$totalnum.")\n";
$updated = 0;
while(1){
	$remaincount = $totalnum - $updated;
	if($remaincount <= 0){
		echo "INFO(".date("Y-m-d H:i:s")."): reach end \n";
		break;
	}
	echo "INFO(".date("Y-m-d H:i:s")."): remaining ".$remaincount."\n";
	$s_time = microtime_float();
	$qr = solr_select_conds(array('guid'), "sourceid:1+AND+quote_father_mid:*", 0, $each_count, "created_at+asc");
	if($qr === false){
		echo "ERROR(".date("Y-m-d H:i:s")."): 从solr取数据出错 \n";
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
			$solra= array();
			$solra['guid'] = $rec['guid'];
			$solra['quote_father_mid'] = '';
			if($updatesolr){
				$solrarticle[] = $solra;
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
		$updated += $rcnt;
	}
}
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";
