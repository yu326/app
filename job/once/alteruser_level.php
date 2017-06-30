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
$totalnum = solr_select_conds(array('guid', 'users_verified_type','users_sourceid','users_id'), "users_sourceid:1+AND+users_verified:2", 0, 0);
if($end == 0){
	$end = $totalnum;
}
echo "INFO: total alter user data (".$totalnum.")\n";

while(1){
	if(!empty($end) && $start >= $end){
		echo "INFO: reach end number {$end}\n";
		break;
	}
	echo "INFO: update ".$start." ~ ".($start + $each_count)."\n";
	$s_time = microtime_float();
	$seqno = $start;
	$shift = 0;
	$qr = solr_select_conds(array('guid', 'users_verified_type','users_sourceid','users_id'), "users_sourceid:1+AND+users_verified:2", $start, $each_count, "users_user_updatetime+asc");
	if($qr === false){
		echo "ERROR: 从solr去数据出错 \n";
		echo "INFO: failed on number {$seqno}\n";
		exit;
	}
	else{
		$rcnt = solr_select_conds(array('guid', 'users_verified_type','users_sourceid','users_id'), "users_sourceid:1+AND+users_verified:2", $start, $each_count);
		if($rcnt == 0){
			break;
		}
		$solrdata = array();
		foreach($qr as $qi=>$rec){
			$solru = array();
			$solru['guid'] = $rec['guid'];
			if(isset($rec['users_verified_type'])){
				$vt;
				if(is_array($rec['users_verified_type'])){
					$vt = $rec['users_verified_type'][0];
				}
				else{
					$vt = $rec['users_verified_type'];
				}
				switch($vt){
				case 200:
					$users_level = 1;
					break;
				case 210:
					$users_level = 2;
					break;
				case 220:
					$users_level = 3;
					break;
				case 230:
					$users_level = 4;
					break;
				case 240:
					$users_level = 5;
					break;
				case 250:
					$users_level = 6;
					break;
				case 260:
					$users_level = 7;
					break;
				case 270:
					$users_level = 8;
					break;
				case 280:
					$users_level = 9;
					break;
				default:
					break;
				}
			}
			$solru['users_level'] = $users_level;
			if($updatesolr){
				$solrdata[] = $solru;
			}
			$seqno++;
		}
		//更新用户
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
		unset($solrdata);
		$start += $each_count - $shift;
		$e_time = microtime_float();
        echo "INFO: this round cost ".($e_time - $s_time)."\n";
	}
}
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";

