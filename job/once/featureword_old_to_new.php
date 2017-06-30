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


function handlestr($item,$flag=false){
	global $logger;
	$logger->debug(__FILE__.__LINE__." feature333333333333feature_father_guid --- ".var_export($item , true));
	$tmp = str_replace("#", "", str_replace("##", ",", $item));
	$logger->debug(__FILE__.__LINE__." feature333333333333feature_father_guid --- ".var_export($tmp , true));
	$strarr = explode(",", $tmp);
	$redata ="";
	if($flag){
		$redata = count($strarr) > 1 ? $strarr[0] : $strarr[1];
	}else{
		$redata = count($strarr) > 1 ? $strarr[1] : $strarr[0];
	}
    return $redata;
}
$totalnum_1 = solr_select_conds(null, "feature_pclass:*", 0, $totalnum);

if(isset($_SERVER['argc']) && $_SERVER['argc'] > 2){
	$isbackups = (int)$argv[3];
	if(!empty($isbackups)){
		if(disk_free_space('./') > 1024){
			$filename="featureword_old_to_new.txt";
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

$featureguidarry = array();

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
		//$solrarticle = array();
		//$logger->error(__FILE__.__LINE__."--- ".var_export($qr, true));
		//echo "INFO: foreach begin (".date("Y-m-d H:i:s").")\n";

		$featurekeywordarray = array();
		$oldfeatureguid = array();
		foreach($qr as $fi=>$fitem){
			$flag = false;
			foreach($featureguidarry as $q=>$v){
				if($v["feature_class"]==$fitem["feature_pclass"]){
					$feaid=$fitem["feature_pclass"];
					$feaguid=$v["guid"];
					$flag = true;
				}
			}
			if(!$flag){
				$eachdate1 = array();
				$eachdate1["feature_father_guid"]=0;
				$eachdate1["feature_class"]=$fitem["feature_pclass"];
				$eachdate1["feature_field"]=$fitem["feature_field"];
				$logger->debug(__FILE__.__LINE__."featureguidarry".var_export($fitem, true));
				$maxid1 = addFeatureClass($eachdate1);
				$feature_father_guid = setFeatureMaxID($maxid1);
				$eachdate1["guid"]=$feature_father_guid;
				$featureguidarry[]=$eachdate1;
				unset($eachdate1);
			}
		}

		foreach($qr as $fi=>$fitem){
			$oldfeatureguid[] = $fitem["guid"];
			$flag = false;
			$feaguid="";
			foreach($featureguidarry as $q=>$v){
				if($v["feature_class"]==$fitem["feature_pclass"]){
					$feaid=$fitem["feature_pclass"];
					$feaguid=$v["guid"];
					$flag = true;
				}
			}
			if($flag){
				$eachdate_2 = array();
				$eachdate_2["feature_father_guid"]=$feaguid;
				$eachdate_2["feature_class"]= "#".handlestr($fitem["feature_class"])."#";
				$eachdate_2["feature_field"]=$fitem["feature_field"];
				$eachdate_2["feature_keyword"]=$fitem["feature_keyword"];
				$featurekeywordarray[]=$eachdate_2;
				unset($eachdate_2);
			}
		}
		$r["flag"] = addFeature($featurekeywordarray);
		if($r["flag"]){
			echo "INFO: addchildFeatureArry successed (".date("Y-m-d H:i:s").")\n";
		}else{
			echo "INFO: addchildFeatureArry failed (".date("Y-m-d H:i:s").")\n";
		}
		unset($featurekeywordarray);
		if($r["flag"]){
			$ifsucc = deleteFeature($oldfeatureguid,false);
			if(!$ifsucc){
				echo "INFO: deleteFeature failed (".date("Y-m-d H:i:s").")\n";
				break;
			}else{
				echo "INFO: deleteFeatureArry successed (".date("Y-m-d H:i:s").")\n";
			}
		}
		unset($oldfeatureguid);
	}
}
unset($featureguidarry);
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";
