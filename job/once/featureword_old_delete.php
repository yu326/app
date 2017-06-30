<?php
global $logger;
if (isset($_SERVER['argc']) && $_SERVER['argc'] > 1) {
	$allParam = $_SERVER['argv'];
	//$_SERVER['hostName'] = $allParam[1];
	//设置全局变量 在config.php中统一
	$GLOBALS['hostName'] = $allParam[1];
} else {
	$logger->error(SELF . " - 未传递参数【machine】");
	exit;
}
include_once( 'includes.php' );
initLogger(LOGNAME_MIGRATEDATA);

$updatesolr = true;
$end = 0;
$start = 0;
$each_count = 1000;
if(isset($_SERVER['argc']) && $_SERVER['argc'] > 1){
    $each_count = (int)$argv[2];
}
echo "INFO start from:{$start}, each count:{$each_count}\n";
$totalnum = solr_select_conds(null, "feature_pclass:*", 0, 0);
if($end == 0){
	$end = $totalnum;
}
echo "INFO: alter data begin (".date("Y-m-d H:i:s").")\n";
echo "total alter article data (".$totalnum.")\n";
$sourceurlcache = array();
$featureguidarry = array();

$totalnum_1 = solr_select_conds(null, "feature_pclass:*", 0, $totalnum);

if(isset($_SERVER['argc']) && $_SERVER['argc'] > 2){
	$isbackups = (int)$argv[3];
	if(!empty($isbackups)){
		if(disk_free_space('./') > 1024){
			$filename="featureword_delete_old_date.txt";
			$fp = fopen($filename, 'w');//写入方式打开,如果文件不存在则尝试创建之。
			fwrite($fp, json_encode($totalnum_1));
			fclose($fp);
			//return true;
		}
		else{
			$logger->error(__FILE__." func:".__FUNCTION__." error: disk space is not enough! ");
			return false;
		};
	}
};

while(1){
	$totalnum = solr_select_conds(null, "feature_pclass:*", 0, 0);
	if($totalnum == 0){
		echo "INFO feature_pclass: reach end \n";
		break;
	}
	echo "INFO feature_pclass: reach end ".$totalnum."\n";

	$qr = solr_select_conds(null, "feature_pclass:*", 0, $each_count);
	//echo "INFO feature_pclass2222: reach end ".$qr."\n";

	if($qr === false){
		echo "ERROR feature_pclass: 从solr去数据出错 \n";
		echo "INFO feature_pclass: failed on number {$seqno}\n";
		exit;
	}
	else{
		$rcnt = count($qr);
		if($rcnt == 0){
			echo "INFO feature_pclass:this round result count".$rcnt."";
			break;
		}
		$oldfeatureguid = array();
		foreach($qr as $fi=>$fitem){
			$oldfeatureguid[] = $fitem["guid"];
		}
		$ifsucc = deleteFeature($oldfeatureguid,false);
		unset($oldfeatureguid);
		if(!$ifsucc){
			echo "INFO: deleteFeature failed (".date("Y-m-d H:i:s").")\n";
			break;
		}else{
			echo "INFO: deleteFeatureArry successed (".date("Y-m-d H:i:s").")\n";
		}
	}
}
echo "INFO: delete old data completed (".date("Y-m-d H:i:s").")\n";
