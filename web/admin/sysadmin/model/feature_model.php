<?php
include_once( 'includes.php' );
initLogger(LOGNAME_WEBAPI);
$featureConfig = array();
$featureConfig["text"] = array("verified_reason", "description", "text","ancestor_text", "wb_topic", "ancestor_wb_topic","wb_topic_keyword", "ancestor_wb_topic_keyword", "screen_name", "NRN", "ancestor_NRN", "source", "host_domain", "ancestor_host_domain", "organization", "ancestor_organization", "pg_text", "column", "column1", "post_title",
"productType","impress","commentTags","proClassify","promotionInfos","productFullName","productColor","productSize","productDesc","productComb","detailParam","compName","compAddress","phoneNum","compURL","serviceProm","logisticsInfo","payMethod","serviceComment","apdComment"
);
$featureConfig["combintext"] = array("combinWord", "wb_topic_combinWord", "ancestor_combinWord", "ancestor_wb_topic_combinWord");
$featureConfig["uniqueuser"] = array("account", "ancestor_account", "userid", "users_friends_id", "usersfriend");
function handlestr($item,$flag=false){
	global $logger;
	$tmp = str_replace("#", "", str_replace("##", ",", $item));
	$strarr = explode(",", $tmp);
	$redata ="";
	if($flag){
		$redata = count($strarr) > 1 ? $strarr[0] : $strarr[1];
	}else{
		$redata = count($strarr) > 1 ? $strarr[1] : $strarr[0];
	}
	return $redata;
}

//给solr发送请求前格式化字段值
function formatter($type, $value){
	$value = trim($value); //去除空格
	switch($type){
	case "combintext":
		$tmp =str_replace(",", "##", $value);
		$value = "#".$tmp."#";
		break;
	default:
		break;
	}
	return $value;
}
//$featureConfig 字段分类
function preFormatter($featureConfig, $fields, $value){
	foreach($featureConfig as $fkey=>$fval){
		foreach($fval as $fvkey=>$fvitem){
			foreach($fields as $ffi=>$ffitem){
				if($fvitem == $ffitem){
					//调用函数处理 $value
					$retvalue=formatter($fkey, $value);
				}
			}
		}
	}
	return $retvalue;
}
function classFormatter($pclass, $class){
	$pclass = trim($pclass);
	$class = trim($class);
	return "#".$pclass."##".$class."#";
}

$kwblurUrl = "";
if(isset($_GET['type'])){
	if('selectfeatureinfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = isset($_GET['pagesize']) ? $_GET["pagesize"] : 0;
		$startnum = $page * $pagesize;
		$feature_pclass = isset($_GET['feature_pclass']) ? $_GET['feature_pclass'] : NULL;
		$feature_class = isset($_GET['feature_class']) ? $_GET["feature_pclass"].",".$_GET["feature_class"] : NULL;
		$feature_field = isset($_GET['feature_field']) ? $_GET['feature_field'] : NULL;
		$feature_keyword = isset($_GET['feature_keyword']) ? $_GET['feature_keyword'] : NULL;

		if($feature_keyword != NULL){
			$keyword = solrEsc($feature_keyword);
			$qparam = "users_screen_name:".$keyword;
			$field = array("users_screen_name","users_id");
			$totalres = solr_select_conds($field, $qparam, 0, 0);
			$totalcount = 0;
			if($totalres === false){
				$logger->error(__FILE__.__LINE__." solr_select_conds return false ");
				exit;
			}
			else{
				$totalcount = $totalres;
			}

			$tmprs = solr_select_conds($field, $qparam, 0, $totalcount);
			$feature_keyword_id = array();
			if(!empty($tmprs)){
				foreach($tmprs as $ti=>$titem){
					$feature_keyword_id[] = $titem["users_id"];
				}
			}
			if(is_array($feature_keyword) && !empty($feature_keyword_id)){
				$feature_keyword = array_merge($feature_keyword, $feature_keyword_id);
			}
			else{
				$feature_keyword_id[] = $feature_keyword;
				$feature_keyword = $feature_keyword_id;
			}
		}
        if(empty($feature_pclass)){$feature_pclass=true;}
		$r = getFeatureKeyword($startnum, $pagesize, $feature_field, $feature_pclass, $feature_class, $feature_keyword);
		/*
		$resultArr = array();
		for($i=0; $i<6; $i++){
			$result["feature_pclass"] = "质量".($i%2);
			$result["feature_class"] = "产品质量".($i%3);
			$result["feature_keyword"] = "优良".$i;
			$result["feature_field"] = array("text", "wb_topic"); 
			$resultArr[] = $result; 
			$result["feature_pclass"] = "用户".($i%2);
			$result["feature_class"] = "活跃用户".($i%3);
			$result["feature_keyword"] = "张".$i;
			$result["feature_field"] = array("screen_name", "NRN"); 
			$resultArr[] = $result; 
			$result["feature_pclass"] = "组合".($i%2);
			$result["feature_class"] = "话题".($i%3);
			$result["feature_keyword"] = "#北京##暴雨#".$i;
			$result["feature_field"] = array("combinWord", "wb_topic_combinWord"); 
			$resultArr[] = $result; 
			$result["feature_pclass"] = "用户".($i%2);
			$result["feature_class"] = "唯一用户".($i%3);
			$result["feature_keyword"] = "13232323".$i;
			$result["feature_field"] = array("account", "userid"); 
			$resultArr[] = $result; 
		}
		$r['totalcount'] = 30;
		$r['datalist'] = $resultArr;
		 */
		echo json_encode($r);
	}
	else if('selectfeaturepclass' == $_GET['type']){
		$r = getFeatureClass(NULL, NULL, true, NULL, true);
		echo json_encode($r);
	}
	else if('selectfeatureclass' == $_GET['type']){
		$pclass = isset($_GET['feature_pclass']) ? $_GET['feature_pclass'] : NULL;
		$r = getFeatureClass(NULL, NULL, false, NULL, $pclass);
		/*
		$r['totalcount'] = 30;
		$r['datalist'] = array("产品质量", "产品质量1", "产品质量3", "产品质量4");
		 */
		echo json_encode($r);
	}
	else if('selectfeaturefield' == $_GET['type']){
		$r['totalcount'] = count($featureConfig);
		$r['datalist'] = $featureConfig;
		echo json_encode($r);
	}
	else if('getusernamebyids' == $_GET['type']){
		$userids = isset($_GET['userids']) ? $_GET['userids'] : "";
		if($userids != ""){
			$userid = explode(",", $userids); 
		}
		$selectFields = array("users_id","users_screen_name");
		foreach($userid as $k=>$u){
			$tmp = getUserGuidOrMore(array("sourceid"=>"*", "users_id"=>$u));
			if($tmp === false){
				echo " feature_model.php 获取用户guid失败";
				exit;
			}
			$guids[] = $tmp;
			//$guids[] = "1u_".$u; 
		}
		$res = solr_select($guids,$selectFields);
		if($res === false){
			echo "feature_model.php call solr_select error";
			exit;
		}
		else{
			if(!empty($res)){
				foreach($res as $k=>$u){
					$result["userid"] = $u["users_id"];
					$result["screen_name"] = isset($u["users_screen_name"]) ? $u["users_screen_name"] : "";
					$users[] = $result;
				}
			}
			$data_arr["totalcount"] = count($users);
			$data_arr["datalist"] = $users;
			echo json_encode(array($data_arr));
		}
	}
}
else if(isset($HTTP_RAW_POST_DATA)){
    global $arrsdata,  $logger;
    $arrsdata = json_decode($HTTP_RAW_POST_DATA, true);
    if(!isset($arg_type)){
        $arg_type = $arrsdata["type"];
    }
	//新增
	if($arg_type == "addfeatureword"){
		foreach($arrsdata["feature_keyword"] as $key => $value){
			if($value != ""){
				$farr["feature_pclass"] = $arrsdata["feature_pclass"];  
				$farr["feature_class"] = classFormatter($arrsdata["feature_pclass"], $arrsdata["feature_class"]);  
				$farr["feature_field"] = $arrsdata["feature_field"];  
				$farr["feature_keyword"] = preFormatter($featureConfig, $farr["feature_field"], $value);  
				$featureArr[] = $farr;
			}
		}
		$r["flag"] = addFeature($featureArr);
		echo json_encode($r);
	}
	else if($arg_type == "importfeaturejson"){
		if(!empty($arrsdata["data"])){
			foreach($arrsdata["data"] as $fi=>$fitem){
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
		}
		$r["flag"] = addFeature($featureArr);
		echo json_encode($r);
	}
	else if($arg_type == "importfeaturenewjson"){
		if(!empty($arrsdata["data"])){
			$featureguidarry = array();
			$eachdate1 = array();
			$featurekeywordarray = array();
			$oldfeatureguid = array();//delete old data
			foreach($arrsdata["data"] as $fi=>$fitem){
				$flag = false;
				foreach($featureguidarry as $q=>$v){
					if($v["feature_class"]==$fitem["feature_pclass"]){
						$feaid=$fitem["feature_pclass"];
						$feaguid=$v["guid"];
						$flag = true;
					}
				}
				if(!$flag){
					$eachdate1["feature_father_guid"]=0;
					$eachdate1["feature_class"]=$fitem["feature_pclass"];
					$eachdate1["feature_field"]=$fitem["feature_field"];
					$logger->debug(__FILE__.__LINE__."featureguidarry".var_export($fitem, true));
					$maxid1 = addFeatureClass($eachdate1);
					$feature_father_guid = setFeatureMaxID($maxid1);
					$eachdate1["guid"]=$feature_father_guid;
					$featureguidarry[]=$eachdate1;
				}
			}

			foreach($arrsdata["data"] as $fi=>$fitem){
				$oldfeatureguid[] = $fitem["guid"];//delete old data
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
					//$maxid_2 = addFeature(array($eachdate_2));
				}
			}
			$logger->debug(__FILE__.__LINE__."featureguidarry".var_export($featureguidarry, true));
		}
		$r["flag"] = addFeature($featurekeywordarray);
		//beigin:delete old data
		if($r["flag"]){
		     deleteFeature($oldfeatureguid);
		}
		//end:delete old data
		echo json_encode($r);
	}
	else if($arg_type == "updatefeatureword"){ //修改
		foreach($arrsdata["feature_keyword"] as $key => $value){
			if($value != ""){
				$farr["guid"] = $arrsdata["feature_id"];
				$farr["feature_pclass"] = $arrsdata["feature_pclass"];  
				$farr["feature_class"] = classFormatter($arrsdata["feature_pclass"], $arrsdata["feature_class"]);  
				$farr["feature_field"] = $arrsdata["feature_field"];  
				$farr["feature_keyword"] = preFormatter($featureConfig, $farr["feature_field"], $value);  
				$featureArr[] = $farr;
			}
		}
		$r["flag"] = updateFeature($featureArr);
		echo json_encode($r);
	}
	else if($arg_type == "deletefeatureword"){
		$r["flag"] = deleteFeature($arrsdata["deldata"]);
		echo json_encode($r);
	}
	else if($arg_type == 'checkfeatureexist'){ //新增前检查 对应项是否存在
		foreach($arrsdata["feature_keyword"] as $key => $value){
			if($value != ""){
				$featureArr[] = preFormatter($featureConfig, $arrsdata["feature_field"], $value);  
			}
		}
		$result = getFeatureKeyword(NULL, NULL, $arrsdata["feature_field"], NULL, $arrsdata["feature_pclass"].",".$arrsdata["feature_class"], $featureArr);
		$hasitem = false;
		if(isset($result["totalcount"]) && $result["totalcount"] > 0){
			$hasitem = true;
			$r["datalist"] = $result["datalist"];
		}
		$r["flag"] = $hasitem ? 1:0;
		echo json_encode($r);
	}
	else if($arg_type == 'query_screen_name'){
		$fields = array("users_id", "users_screen_name");
		$noexistname = array();
		$choiceNames = array();
		$systemRepeatUsername = array();
		$namesArr = solr_select_conds($fields, $arrsdata["names"], 0, -1);
		foreach($arrsdata["names"]["users_screen_name"] as $key=>$item){
			$find = false;
			foreach($namesArr as $ni=>$nitem){
				if($nitem["users_screen_name"] == $item){
					if(!empty($choiceNames)){
						$has = false;
						foreach($choiceNames as $ci=>$citem){
							if($citem["name"] == $nitem["users_screen_name"]){
								$has = true;
								break;
								//$choiceNames[$ci]["userrepeat"] = true;
							}
						}
						if($has){
							if(!in_array($nitem["users_screen_name"], $systemRepeatUsername)){
								$systemRepeatUsername[] = $nitem["users_screen_name"];
							}
						}
					}
					$c["code"] = $nitem["users_id"];
					$c["name"] = $nitem["users_screen_name"];
					$choiceNames[] = $c;
					$find = true;
				}
			}
			if(!$find){
				$noexistname[] = $item;
			}
		}
		if(count($noexistname) > 0){
			$r["flag"] = 1;
			$r["noexistname"] = $noexistname;
		}
		else if(count($systemRepeatUsername)){
			$r["flag"] = 2;
			$r["sysrepeatuser"] = $systemRepeatUsername;
			$r["users"] = $choiceNames;
		}
		else{
			$r["flag"] = 0;
			$r["users"] = $choiceNames;
		}
		echo json_encode($r);
	}
}
