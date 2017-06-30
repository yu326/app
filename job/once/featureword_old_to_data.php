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
/*if(isset($_SERVER['argc']) && $_SERVER['argc'] > 1){
    $each_count = (int)$argv[2];
}*/
//echo "INFO start from:{$start}, each count:{$each_count}\n";
//$totalnum = solr_select_conds(null, "feature_pclass:*", 0, 0);
/*if($end == 0){
	$end = $totalnum;
}*/
//echo "total alter article data (".$totalnum.")\n";
//$sourceurlcache = array();
//$featureguidarry = array();


//$totalnum_1 = solr_select_conds(null, "feature_pclass:*", 0, $totalnum);
$file_path = "featureword_old_to_data.txt";
$jsondata="";
if(file_exists($file_path)){
	$fp = fopen($file_path,"r");
	$str = fread($fp,filesize($file_path));//指定读取大小，这里把整个文件内容读取出来
	$jsondata =json_decode($str,true);
	$logger->debug(__FILE__.__LINE__."------- ".var_export($jsondata , true));
	echo "read file success (".$r.")\n";
}
else{
	$logger->error(__FILE__." func:".__FUNCTION__." error: disk space is not enough! ");
	echo "read file failed (".$r.")\n";
	return false;
};

if(!empty($jsondata)){
	foreach($jsondata as $fi=>$fitem){
		$logger->debug(__FILE__.__LINE__."fitem".var_export($fitem, true));
		$farr["feature_pclass"] = $fitem["feature_pclass"];
		$farr["feature_class"] = $fitem["feature_class"];
		$farr["feature_field"] = $fitem["feature_field"];
		$farr["feature_keyword"] = $fitem["feature_keyword"];
		$result = getFeatureKeyword(NULL, NULL, $fitem["feature_field"], $fitem["feature_pclass"], NULL,$farr["feature_keyword"]);
		$logger->debug(__FILE__.__LINE__."result ".var_export($result, true));
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
		}
		else{
			$featureArr[] = $farr;
		}
	}
	$r = addFeature($featureArr);
	echo "migration data is (".$r.")\n";
}




/*if(disk_free_space('./') > 1024){
	$filename="featureword_old_to_new.txt";
	$fp = fopen($filename, 'w');//写入方式打开,如果文件不存在则尝试创建之。
	fwrite($fp, json_encode($totalnum_1));
	fclose($fp);
	//return true;
}
else{
	$logger->error(__FILE__." func:".__FUNCTION__." error: disk space is not enough! ");
	return false;
};*/







/*while(1){
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
		$solrarticle = array();
		$logger->error(__FILE__.__LINE__." feature333333333333Arr-88888--- ".var_export($qr, true));
		foreach($qr as $qi=>$rec){
			$guid_old = $rec["guid"];
            $flag = false;
			$feaid ="";
			$feaguid ="";
			foreach($featureguidarry as $q=>$v){
				if($v["feature_class"]==$rec["feature_pclass"]){
					$feaid=$rec["feature_pclass"];
					$feaguid=$v["guid"];
					$flag = true;
				}
			}
			//判断旧的数据是入库一条还是2条
			if($flag&&$feaid!=""){
				$eachdate_2 = array();
				$eachdate_2["feature_father_guid"]=$feaguid;
				$eachdate_2["feature_class"]= "#".handlestr($rec["feature_class"])."#";
				$eachdate_2["feature_field"]=$rec["feature_field"];
				$eachdate_2["feature_keyword"]=$rec["feature_keyword"];
				$maxid_2 = addFeature(array($eachdate_2));
				//$feature_father_guid = setFeatureMaxID($maxid_2);
			}else{
				$eachdate1 = array();
				$eachdate1["feature_father_guid"]=0;
				$eachdate1["feature_class"]=$rec["feature_pclass"];
				$eachdate1["feature_field"]=$rec["feature_field"];
				$maxid1 = addFeatureClass($eachdate1);
				$feature_father_guid = setFeatureMaxID($maxid1);
				$logger->error(__FILE__.__LINE__." featur777777feature_father_guid --- ".var_export($maxid1 , true));
				$eachdate1["guid"]=$feature_father_guid;
				$featureguidarry[]=$eachdate1;
				$logger->error(__FILE__.__LINE__." feature333333333333feature_father_guid --- ".var_export($feature_father_guid , true));
				$logger->error(__FILE__.__LINE__." feature333333333333Arr55-8555558888--- ".var_export($eachdate1, true));

				$eachdate2 = array();
				$eachdate2["feature_father_guid"]=$feature_father_guid;
				$eachdate2["feature_class"]= "#".handlestr($rec["feature_class"])."#";
				$logger->error(__FILE__.__LINE__." ---------fffsdfwef--- ".var_export($eachdate2["feature_class"], true));
				$eachdate2["feature_field"]=$rec["feature_field"];
				$eachdate2["feature_keyword"]=$rec["feature_keyword"];
				$logger->error(__FILE__.__LINE__." feature333333333333Arr55-8555558888--- ".var_export($eachdate2["feature_keyword"], true));
				$maxid2=addFeature(array($eachdate2));
				$logger->error(__FILE__.__LINE__." feature333333333333Arr55-8555558888--- ".var_export($maxid2, true));
			}
          // break 2;
			$ifsucc = deleteFeature(array($guid_old));
			if(!$ifsucc){
				$logger->error(__FILE__.__LINE__." 删除失败--- ".var_export($guid_old, true));
				break;
			}
		}
		//break;
	}
}*/
echo "INFO: alter data completed (".date("Y-m-d H:i:s").")\n";
