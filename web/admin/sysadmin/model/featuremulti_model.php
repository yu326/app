<?php
include_once( 'includes.php' );
initLogger(LOGNAME_WEBAPI);
$featureConfig = array();
$featureConfig["text"] = array("verified_reason", "description", "text","ancestor_text", "wb_topic", "ancestor_wb_topic","wb_topic_keyword", "ancestor_wb_topic_keyword", "screen_name", "NRN", "ancestor_NRN", "source", "host_domain", "ancestor_host_domain", "organization", "ancestor_organization", "pg_text", "column", "column1", "post_title",
"productType","impress","commentTags","proClassify","promotionInfos","productFullName","productColor","productSize","productDesc","productComb","detailParam","compName","compAddress","phoneNum","compURL","serviceProm","logisticsInfo","payMethod","serviceComment","apdComment","nowLocation"
);
$featureConfig["combintext"] = array("combinWord", "wb_topic_combinWord", "ancestor_combinWord", "ancestor_wb_topic_combinWord");
$featureConfig["uniqueuser"] = array("account", "ancestor_account", "userid", "users_friends_id", "usersfriend");
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
function handlestr($item,$flag=false){
	$value = trim($item);
	$tmp = str_replace("#", "",$value);
	return $tmp;
}
function handleoldstr($item,$flag=false){
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
function classFormatter($class){
	$class = trim($class);
	return "#".$class."#";
}
/*
 * @brief 以关键词起,获取所有上级的名称
 * @param &$ritem 这个字段是引用, 查询所有关键词结果的每一条记录
 * @param $level 级别,默认从1级开始,每增一级增加一个属性存储级别名称
 * @return 带有所有上级名称的结果
 * @author Bert
 * @date 2016-6-16
 * */
//关键词的上级相同时缓存,不用再次请求solr
//$feature_father_guid_cache = array();
//$feature_father_cache = array();
function getParentLevel(&$ritem, $level, $feature_father_guid){
    global /*$feature_father_guid_cache, $feature_father_cache,*/ $logger;
    $logger->debug(__FILE__.__LINE__." feature_father_guid ".var_export($feature_father_guid, true)." level ".var_export($level, true));
    //查询父级;
    //此处判断的是关键词上一级的feature_father_guid, 但会把关键词上级一直到根的名称都会存下来
    //if(!in_array($ritem['feature_father_guid'], $feature_father_guid_cache)){
        $irows = pow(2, 31) - 1;
        $r = getFeatureClass(0, $irows, NULL, NULL, NULL, NULL, $feature_father_guid);
	    $logger->debug(__FILE__.__LINE__." feature_father_cache ".var_export($r, true));
        /*
         * 2016-6-29 Bert 
         * 数据库存在guid为0的数据时导致死循环,此处添加查出的需要的特征分类
         * 此处判断 != "0" 必须加引号, 因为 字符串!=0 恒为false 
         * */
        if(!empty($r) && count($r['datalist'])>0 && $r['datalist'][0]['guid'] != "0"){
            $ritem['level_'.$level.''] = $r['datalist'][0];

            //$feature_father_guid_cache[] = $ritem['feature_father_guid'];
            //$feature_father_cache['level_'.$level.''] = $r['datalist'][0]['feature_keyword'];
            //$logger->debug(__FILE__.__LINE__." feature_father_cache ".var_export($feature_father_cache, true));
        }
        $logger->debug(__FILE__.__LINE__." ritem ".var_export($ritem , true));
        if(!empty($r) && count($r['datalist']) > 0 && isset($r['datalist'][0]['feature_father_guid'])){
            getParentLevel($ritem, $level+1, $r['datalist'][0]['feature_father_guid']);
        }
    //}
    //else{
    //    $ritem = $ritem + $feature_father_cache;
    //}
}
$kwblurUrl = "";
if(isset($_GET['type'])){
    if('selectfeatureinfo' == $_GET['type']){
		$page = $_GET['page']-1; //页码显示为1，solr需要从0开始
		$pagesize = isset($_GET['pagesize']) ? $_GET["pagesize"] : 0;
		$startnum = $page * $pagesize;
		$feature_field = isset($_GET['feature_field']) ? $_GET['feature_field'] : '*';
		$feature_keyword = isset($_GET['feature_keyword']) ? $_GET['feature_keyword'] : NULL;
		$feature_class= isset($_GET['feature_class']) ? $_GET['feature_class'] : NULL;
		$feature_father_guid = isset($_GET['feature_father_guid']) ? $_GET['feature_father_guid'] : NULL;
		//$feature_father_guid = isset($_GET['feature_father_guid']) ? true : NULL;
		//这个guid是过滤条件的最后一级的guid。
		$featureid = isset($_GET['guid']) ? $_GET['guid'] : NULL;
		$featureguidArr = array();
		if($featureid !== NULL){
			getAllFeatureByID($featureid, $feature_field, $featureguidArr);
		}
		$logger->debug(__FILE__.__LINE__." r ".var_export($featureguidArr, true));
        if(empty($featureguidArr)){
			$featureguidArr = $featureid;//$featureguidArr支持字符串和数组，数组时需要组成对象array（“guid”=>$featureid）赋值。
			$logger->debug(__FILE__.__LINE__." r ".var_export($featureid, true));
		}

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
		else{
			$feature_keyword = true;
		}
		if(empty($feature_father_guid)){
			$feature_father_guid = true;
		}
		$r = getFeatureKeyword($startnum, $pagesize, $feature_field, NULL, $feature_class, $feature_keyword, $feature_father_guid, $featureguidArr);

        //调用之前清空cache 
        //$feature_father_guid_cache = array();
        //$feature_father_cache = array();
        //获取当前关键词的级别
        foreach($r['datalist'] as $ri=>$ritem){
            if(isset($r['datalist'][$ri]['feature_father_guid'])){
                getParentLevel($r['datalist'][$ri], 1, $r['datalist'][$ri]['feature_father_guid']);
            }
        }
		$logger->debug(__FILE__.__LINE__." r with level ".var_export($r, true));
		echo json_encode($r);
	}
	else if('selectfeaturerootclass' == $_GET['type']){
		  $r = getFeatureClass(NULL, NULL, NULL, true, NULL, 0);
		  $tmpArr = array();
		  foreach($r['datalist'] as $key=>$item){
			  $tmpF = $item;
				if(!empty($item['feature_pclass'])){
					$tmpF = array();
					$tmpF['feature_class'] = $item['feature_pclass'];
					$tmpF['feature_field'] = $item['feature_field'];
                    if(isset($item['feature_keyword'])){
                        $tmpF['feature_keyword'] = $item['feature_keyword'];
                    }
					$tmpF['guid'] = $item['guid'];
					$tmpF['old_feature'] = true;
					//$tmpArr[] = $tmpF;
				}
				/*
				else{
					$tmpArr[] = $item;
				}*/
			  $has = false;
			  foreach($tmpArr as $key=>$value){
				  if($value['feature_class'] == $tmpF['feature_class']){
					  $has = true;
				  }
			  }
			  if(!$has){
				  $tmpArr[] = $tmpF;
			  }
		  }
		$r['datalist'] = $tmpArr;
		echo json_encode($r);
        exit;
	}
	else if('selectfeatureclass' == $_GET['type']){
		$father_guid = isset($_GET['feature_pclass']) ? $_GET['feature_pclass'] : NULL;
        $old_feature = isset($_GET['old_feature']) ? $_GET['old_feature'] : NULL;
        if($old_feature == 1){
            $r = getFeatureClass(NULL, NULL, NULL, true, NULL, NULL, $father_guid);
        }
        else{
            $r = getFeatureClass(NULL, NULL, NULL, true, NULL, $father_guid, NULL, NULL,true);
        }
		echo json_encode($r);
        exit;
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
        $feature_father_guid = 0;
        foreach($arrsdata['feature_class'] as $key=>$cls){
			if($key<count($arrsdata['feature_class'])-1){
				if($cls['type'] == 'add'){
					//此处使用$arrsdata['feature_class'][$key], 由于同时修改feature_father_guid的值,$cls不会跟随修改
					if($arrsdata['feature_class'][$key]['feature_father_guid'] == "-1"){
						$arrsdata['feature_class'][$key]['feature_father_guid'] = 0;
					}
					$maxid = addFeatureClass($arrsdata['feature_class'][$key]);
					$feature_father_guid = setFeatureMaxID($maxid);
					$arrsdata['feature_class'][$key]['guid'] = $feature_father_guid;
					if(isset($arrsdata['feature_class'][$key+1])){
						$arrsdata['feature_class'][$key+1]['feature_father_guid'] = $feature_father_guid;
					}
				}
				else{
					$feature_father_guid = $cls['guid'];
				}
			}
        }
        foreach($arrsdata["feature_keyword"] as $key => $value){
            if($value != ""){
                $farr["feature_father_guid"] = $feature_father_guid;
                $farr["feature_field"] = $arrsdata["feature_field"];
                $farr["feature_keyword"] = preFormatter($featureConfig, $farr["feature_field"], $value);
                //2016-7-3 Bert url中查询feature_class:夹克, 实际在solr查询是会转成feature_class:"#夹克#"处理, 
                //为兼容旧数据,需要存储时添加上"##";
                $fclass = $arrsdata['feature_class'][count($arrsdata['feature_class'])-1]["feature_class"];
				$farr["feature_class"] = "#".$fclass."#";
				$logger->debug(__FILE__.__LINE__." value ".var_export($value, true));
				//$farr["feature_keyword"] = preFormatter($featureConfig, $farr["feature_keyword"], $value);
				$featureArr[] = $farr;
            }
        }
		$logger->debug(__FILE__.__LINE__." featureArr ".var_export($featureArr, true));
        $r["flag"] = addFeature($featureArr);
        echo json_encode($r);
    }
	else if($arg_type == "importfeaturejson"){
		if(!empty($arrsdata["data"])){
			$levelarr = array();
			$levelobjarr = array();
			$featurekeywordarr = array();
			$logger->debug(__FILE__.__LINE__."featureguidarry".var_export($arrsdata["data"], true));
			foreach($arrsdata["data"] as $fi=>$fitem){
				  $flag = false;
				  $levelarray = array();
                  foreach($fitem as $key=>$value){
					  if(strpos($key, 'level_')!==false){
						  $levelarray[] = $value;
					  }
				  }
				  $len=count($levelarray);
				  if($arrsdata["check"]){
					  $feature_father_guid=$arrsdata["feature_fahter_guid"];
				  }else{
					  $feature_father_guid=0;
				  }
				  if($len>0){
					   //把所有的level缓存到一个数组中，目的是要进行去重 $levelobjarr
					   foreach(array_reverse($levelarray) as $ke=>$val){
						  $k=$ke+1;
						  $flag=false;
						  foreach($levelobjarr as $key_l=>$value_l){
							  if(!isset($levelobjarr["level_".$k])){
								  $levelobjarr["level_".$k] = array();
							  }else{
								  foreach($levelobjarr["level_".$k] as $k_ob=>$v_ob){
									  if($v_ob["oldguid"]==$val["guid"]){
										  $feature_father_guid=$v_ob["guid"];
										  $flag=true;
									  }
								  }
							  }
						  }
						   if(!$flag){
							   $eachdate = array();
							   $eachdate["feature_father_guid"]=$feature_father_guid;
							   $eachdate["feature_class"]=handlestr($val["feature_class"]);
							   $eachdate["feature_field"]=$val["feature_field"];
							   $maxid = addFeatureClass($eachdate);
							   $feature_father_guid = setFeatureMaxID($maxid);
							   $eachdate["guid"]=$feature_father_guid;
							   $eachdate["oldguid"]=$val["guid"];
							   $levelobjarr["level_".$k][] = $eachdate;
						   }
					  }
					  $eachkwdata = array();
					  $eachkwdata["feature_father_guid"] =$feature_father_guid;
					  $eachkwdata["feature_class"]="#".handlestr($fitem["feature_class"])."#";
					  $eachkwdata["feature_field"]=$fitem["feature_field"];
					  $eachkwdata["feature_keyword"]=$fitem["feature_keyword"];
					  $featurekeywordarr[] = $eachkwdata;
				  }
			}
		}
		$r["flag"] = addFeature($featurekeywordarr);
		echo json_encode($r);
	}
	else if($arg_type == "importmulfeaturejson"){
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
                    $fedata = $arrsdata["feature_fahter_guid"];
					$eachdate1["feature_father_guid"]=$fedata;
					$eachdate1["feature_class"]=$fitem["feature_pclass"];
					$eachdate1["feature_field"]=$fitem["feature_field"];
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
					$eachdate_2["feature_class"]= "#".handleoldstr($fitem["feature_class"])."#";
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
	else if($arg_type == "importmulfeaturejsonnodeleteold"){
		if(!empty($arrsdata["data"])){
			$featureguidarry = array();
			$eachdate1 = array();
			$featurekeywordarray = array();
			//$oldfeatureguid = array();//delete old data
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
					$fedata = $arrsdata["feature_fahter_guid"];
					$eachdate1["feature_father_guid"]=$fedata;
					$eachdate1["feature_class"]=$fitem["feature_pclass"];
					$eachdate1["feature_field"]=$fitem["feature_field"];
					$maxid1 = addFeatureClass($eachdate1);
					$feature_father_guid = setFeatureMaxID($maxid1);
					$eachdate1["guid"]=$feature_father_guid;
					$featureguidarry[]=$eachdate1;
				}
			}

			foreach($arrsdata["data"] as $fi=>$fitem){
				//$oldfeatureguid[] = $fitem["guid"];//delete old data
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
					$eachdate_2["feature_class"]= "#".handleoldstr($fitem["feature_class"])."#";
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
		/*if($r["flag"] && !isempty($oldfeatureguid)){
			deleteFeature($oldfeatureguid);
		}*/
		//end:delete old data
		echo json_encode($r);
	}
	else if($arg_type == "updatefeatureword"){ //修改
		foreach($arrsdata["feature_keyword"] as $key => $value){
			if($value != ""){
				$farr["guid"] = $arrsdata["feature_id"];
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
        $r["flag"] = 0;
        /*
         * $arrsdata['feature_class'] 为父级的分类,只允许最后的子类有关键词, 当查询到某一级不存在时,对应的子级都不存在
         * */
        foreach($arrsdata["feature_class"] as $key => $value){
            if(!empty($value)){
                if($value['type'] == 'add'){
                    //$result = getFeatureKeyword(NULL, NULL, NULL, NULL, NULL, $value['feature_class'], $value['feature_father_guid']);
					$logger->debug(__FILE__.__LINE__." ----cls------- ".var_export(classFormatter($value['feature_class']), true));
					if($value['feature_father_guid']==-1){
						$result = getFeatureClass(NULL, NULL, NULL, NULL, NULL, 0,NULL,$value['feature_class']);
					}else{
						$result = getFeatureClass(NULL, NULL, NULL, NULL, NULL, $value['feature_father_guid'],NULL,$value['feature_class']);
					}

					$logger->debug(__FILE__.__LINE__." ----cls------- ".var_export($result, true));
                    $hasitem = false;
                    if(isset($result["totalcount"]) && $result["totalcount"] > 0){
                        $hasitem = true;
						$flagkey = $key;
                        $r["datalist"] = $result["datalist"];
						$r["level"] =$key;
                    }
                    else{
                        break;
                    }
                    $r["flag"] = $hasitem ? 1:0;
                }
            }
        }
		//父类判断成功之后，要判断关键字是否一致
		$featureLastObj=$arrsdata["feature_class"][count($arrsdata["feature_class"])-1];
		if($r["flag"]==0){
			$logger->debug(__FILE__.__LINE__." ----cls------- ".var_export($featureArr, true));
			foreach($featureArr as $key => $value){
				$result =getFeatureKeyword(NULL, NULL, NULL, NULL, $featureLastObj['feature_class'], $value, $featureLastObj['feature_father_guid'],NULL);
				$hasitem = false;
				if(isset($result["totalcount"]) && $result["totalcount"] > 0){
					$hasitem = true;
					$r["datalist"] = $result["datalist"];
				}
				else{
					break;
				}
				$r["flag"] = $hasitem ? 1:0;
			}
		}
        echo json_encode($r);
        exit;
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
