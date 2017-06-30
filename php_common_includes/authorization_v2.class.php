<?php
include_once('commonFun_v2.php');
include_once('userinfo_v2.class.php');
define('CHECKSESSION_SUCCESS', 1);
define('CHECKSESSION_NULL', 0);//SESSION不存在
define('CHECKSESSION_ADDRERROR', 2);//地址错误
define('CHECKSESSION_CROSSDOMAIN', 3);//第三方站点访问
define('CHECKSESSION_ALLOWACCESSDATA', 4);//第三方站点访问
define('CHECKSESSION_NOTALLOWACCESSDATA', 5);//不允许第三方站点访问请求数据
define('CHECKSESSION_NOTALLOWWIDGET', 6); //不允许第三方使用widget
define('CHECKSESSION_USEREXPIRETIME', 7);

define('CHECKTOKEN_NULL', 0); //detoken 返回错误
define('CHECKTOKEN_USERNULL', 2); //通过token user返回为空

define('LOGIN_ERROR_EXCEPTION', 0);//操作异常
define('LOGIN_ERROR_SUCCESS', 1);//登录成功
define('LOGIN_ERROR_NOUSER', 2);//用户名错误
define('LOGIN_ERROR_NOPWD', 3);//密码错误
define('LOGIN_ERROR_EXPIRE', 4);//过期用户
define('LOGIN_ERROR_PARAM', 5);//参数错误
define('LOGIN_ERROR_TOKEN', 6);//获取token失败

define('VALIDATE_SUCCESS', 1);//权限未过错误
define('VALIDATE_ERROR_NOPERMISSION', 2);//权限未过错误
define('VALIDATE_ERROR_GETPERMISSION', 3);
define('VALIDATE_ERROR_PARAMERROR', 4);//参数错误
define('VALIDATE_ERROR_NULLVALUE', 5);//form表单值为空
define('VALIDATE_ERROR_OUTLIMIT', 6);//超出limit限制
initLogger(LOGNAME_WEBAPI);
/**
 * 权限验证函数类,包含查询验证和保存验证
 */
class Authorization
{
	/**
	 * 
	 * 查询验证, 对提交的所有表单,如果合法,修改实例json中对应字段的值(getdata.php中checkuserpure()调用)
	 * @param assoc $form 需要验证的json
	 * @param assoc $authJson 权限json
	 * @param assoc $accountJson 计费json
	 * @param boolean $isdownload  是否下载数据
	 * @param boolean $isaccessData 是否远程访问数据API
	 */
	public static function validatingQueryformAll($form, $authJson, $accountJson,$isdownload=false, $isaccessData=false, $pinrelation=NULL){
		global $logger;
		//element json是最新的，合并权限json 2013-04-01 15:00
		$form = getMergeJson($form, $authJson);
		$outlimit = array();
		$outlimit["filter"] = array();
		$outlimit["facet"] = array();
		$outlimit['facetlimit'] = array();
		$outlimit["select"] = array();
		$outlimit["output"] = array();
		if($isdownload && empty($authJson['allowDownload'])){
			$outlimit['output'][] = "download";
			$validate = array();
			$validate["outlimit"] = $outlimit;
			return $validate;
		}
		if(!empty($authJson["filter"])){
			foreach($authJson["filter"] as $key=>$value){
				$valuearrs = getFilterValueItem($key,$form["filtervalue"]);
				$farr = getFilterLimit($authJson["filter"], $key);
				//maxlimitlength 从tenant_resource_relation中取值
				if($accountJson["filter"][$key]["maxlimitlength"] != -1 && count($valuearrs) > $accountJson["filter"][$key]["maxlimitlength"]){
					if(!in_array($key, $outlimit["filter"])){
						$outlimit["filter"][] = $key;
					}
					//return VALIDATE_ERROR_OUTLIMIT;
				}
				else{
					if($key == "createdtime" || $key == "nearlytime" || $key == "beforetime" || $key == "untiltime"){
						$timefield = array("createdtime", "nearlytime", "beforetime", "untiltime");
						foreach($timefield as $fk=>$val){
							$timeval = getFilterValueItem($val,$form["filtervalue"]);
							if(count($timeval) > 0){
								foreach($timeval as $tk=>$tv){
									$fieldvalues = getFilterValue($tv['fieldvalue']);
									//解决element中微博模型, nearlytime, beforetime, untiltime 字段类型存储错误的bug
									if($val == "nearlytime" || $val == "beforetime"){
										$tv["fieldvalue"]['datatype'] = "time_dynamic_state";
									}
									else if($val == "untiltime"){
										$tv["fieldvalue"]['datatype'] = "time_dynamic_range";
									}
									if(is_array($fieldvalues) && !is_assoc($fieldvalues)){ //分类查询
										foreach($fieldvalues as $ki=>$item){
											//时间分类查询
											if(checkFilterValue($authJson["filter"][$key],getFieldValue($tv["fieldvalue"]["value"][$ki]['datatype'], $item), $tv["fieldvalue"]["value"][$ki]['datatype']) != 1){
												if(!in_array($key, $outlimit["filter"])){
													$outlimit["filter"][] = $key;
												}
												//return VALIDATE_ERROR_OUTLIMIT;
											}
										}
									}
									else{
										if(checkFilterValue($authJson["filter"][$key],getFieldValue($tv["fieldvalue"]['datatype'], $fieldvalues), $tv["fieldvalue"]['datatype']) != 1){
											if(!in_array($val, $outlimit["filter"])){
												$outlimit["filter"][] = $val;
											}
											//return VALIDATE_ERROR_OUTLIMIT;
										}
									}
								}
							}
						}
					}
					else if($key == "verified_type"){
						//特殊处理认证类型,权限设置认证后,认证类型属于认证
						foreach($valuearrs as $vi=>$vitem){
							$vervalues = getFilterValue($vitem['fieldvalue']);
							$verarrs = array();
							if(is_array($vervalues) && !is_assoc($vervalues)){
								$verarrs = $vervalues;
								$vitemdatatype = $vitem["fieldvalue"]["value"][0]["datatype"];
							}
							else{
								$verarrs[] = $vervalues;
								$vitemdatatype = $vitem["fieldvalue"]["datatype"];
							}
							foreach($verarrs as $ki=>$kitem){
								if(checkFilterValue($authJson["filter"]["verified_type"],getFieldValue($vitemdatatype , $kitem)) != 1){
									switch(getFieldValue($vitemdatatype, $kitem)){
									case "0":
										$tmpverified = '1';
										break;
									case "200":
									case "210":
									case "220":
									case "230":
									case "240":
									case "250":
									case "260":
									case "270":
									case "280":
										$tmpverified = '2';
										break;
									case "-2":
									case "1":
									case "2":
									case "3":
									case "4":
									case "5":
									case "6":
									case "7":
										$tmpverified = '3';
										break;
									default:
										break;
									}
									if(count($authJson["filter"]["verified"]["limit"]) > 0){
										foreach($authJson["filter"]["verified"]["limit"] as $ai=>$aitem){
											if($aitem["value"] == $tmpverified){
												$authJson["filter"]["verified"]["limit"][$ai]["repeat"]++;
											}
										}
										if(checkFilterValue($authJson["filter"]["verified"],$tmpverified) != 1){
											if(!in_array($key, $outlimit["filter"])){
												$outlimit["filter"][] = $key;
											}
										}
									}
								}
							}

						}
					}
					else{
						//var_dump($farr);
						foreach($valuearrs as $k=>$v){
							if($v["fieldvalue"]['datatype'] == "dynamic"){ //联动模型
								continue;
							}
							$fieldvalues = getFilterValue($v['fieldvalue']);
							if($v["fieldname"] == "areauser" || $v["fieldname"] == "area"|| $v["fieldname"] == "ancestor_areamentioned" || $v["fieldname"] == "areamentioned"){
								if(!empty($farr) && count($farr)>0){
									$tmpfv = array();
									if(is_array($fieldvalues) && !is_assoc($fieldvalues)){
										$tmpfv = $fieldvalues;
									}
									else{
										$tmpfv[] = $fieldvalues;
									}
									foreach($tmpfv as $ti=>$titem){
										if(!areaInArray($titem["value"],$farr)){
											$outlimit["filter"][] = $key;
										}
									}
								}
							}
							else if($v["fieldname"] == "emoAreamentioned" || $v["fieldname"] == "ancestor_emoAreamentioned"){
								if(!empty($farr) && count($farr)>0){
									$tmpemofv = array();
									if(is_array($fieldvalues) && !is_assoc($fieldvalues)){
										$tmpemofv = $fieldvalues;
									}
									else{
										$tmpemofv[] = $fieldvalues;
									}
									foreach($tmpemofv as $ei=>$eitem){
										if(!emoareaInArray($eitem["value"],$farr)){
											$outlimit["filter"][] = $key;
										}
									}
								}
							}
							else if(is_array($fieldvalues) && !is_assoc($fieldvalues)){ //非关联数组, array类型
								foreach($fieldvalues as $i => $item){
									if(checkFilterValue($authJson["filter"][$key],getFieldValue($v["fieldvalue"]["value"][$i]['datatype'], $item)) != 1){
										if(!in_array($key, $outlimit["filter"])){
											$outlimit["filter"][] = $key;
										}
										//return VALIDATE_ERROR_OUTLIMIT;
									}
								}
							}
							else{
								if(checkFilterValue($authJson["filter"][$key],getFieldValue($v["fieldvalue"]['datatype'], $fieldvalues), $v["fieldvalue"]['datatype']) != 1){
									if(!in_array($key, $outlimit["filter"])){
										$outlimit["filter"][] = $key;
									}
									//return VALIDATE_ERROR_OUTLIMIT;
								}
							}
						}

					}
				}
			}
		}
		//统计字段 限制
		if($form['output']['outputtype'] == 2){
			if(isset($authJson['facet']['limit']) && count($authJson['facet']['limit']) > 0){
				if(isset($form['facet']['field']) && count($form['facet']['field']) > 0){
					foreach($form['facet']['field'] as $fi=>$fitem){
						$facetflag = false;
						foreach($authJson['facet']['limit'] as $ai=>$aitem){
							if($fitem['name'] == $aitem['value']){
								$facetflag = true;
								break;
							}
						}
						if(!$facetflag){
							$outlimit['facetlimit'][] = $fitem['name'];
						}
					}
				}
				if(isset($form['facet']['range']) && count($form['facet']['range']) > 0){
					foreach($form['facet']['range'] as $fi=>$fitem){
						$facetflag = false;
						foreach($authJson['facet']['limit'] as $ai=>$aitem){
							if($fitem['name'] == $aitem['value']){
								$facetflag = true;
								break;
							}
						}
						if(!$facetflag){
							$outlimit['facetlimit'][] = $fitem['name'];
						}
					}
				}
			}
		}
		//输出过滤限制
		if(isset($authJson['facet']['filterlimit']) && count($authJson['facet']['filterlimit']['limit']) > 0){
			foreach($form['facet']['field'] as $i=>$field){
				$chkarrs = array();
				foreach ($field['filter'] as $j=>$ff){
					array_merge($chkarrs,$ff['value']);
				}
				foreach ($chkarrs as $k=>$v){
					if(checkFilterValue($authJson["facet"]['filterlimit'],$v) != 1){
						if(!in_array($field["name"], $outlimit["facet"])){
							$outlimit["facet"][] = $field["name"];
						}
						//return VALIDATE_ERROR_OUTLIMIT;
					}
				}
			}
		}
		if($isdownload){
			//联动模型的render和普通模型的select验证
			if($form['output']['outputtype'] == OUTPUTTYPE_QUERY && !empty($authJson['download_FieldLimit']) && $pinrelation == NULL){
				foreach($form['select']['value'] as $k => $v){
					$finded = false;
					foreach($authJson['download_FieldLimit'] as $lk => $lv){
						if($v == $lv['value']){
							$finded = true;
							break;
						}
					}
					if(!$finded){//字段没有在fieldlimit中找到
						$outlimit["select"][] = $v;
					}
				}
			}
			else if($pinrelation != NULL){ //联动模型的source和transfrom验证
				if(count($authJson['select']['limit']) > 0){
					foreach ($pinrelation as $pk=>$pv){
						if(!in_array($pv["outputdata"]["outputfield"], $authJson['select']['limit'])){
							$outlimit["select"][] = $pv["outputdata"]["outputfield"];
						}
					}
				}
			}
		}
		else{
			if(count($authJson['select']['limit']) > 0){
				foreach ($form['select']['value'] as $k=>$v){
					if(checkFilterValue($authJson["select"],$v) != 1){
						if(!in_array($v, $outlimit["select"])){
							$outlimit["select"][] = $v;
						}
						//return VALIDATE_ERROR_OUTLIMIT;
					}
				}
			}
		}
		/*
		 if(count($authJson['output']['limit']) > 0 && isset($form["output"]['orderby'])){
		 if(checkFilterValue($authJson["output"],$form["output"]['orderby']) != 1){
		 $outlimit["output"][] = "orderby";
		 //return VALIDATE_ERROR_OUTLIMIT;
		 }
		 }
		 */
		if($isdownload){
			if(isset($authJson['download_DataLimit']) && $authJson['download_DataLimit'] > 0){
				if($form['output']['count'] > $authJson['download_DataLimit']){
					$outlimit['output'][] = "countlimit";
				}
			}
		}
		else if($isaccessData){//远程调用获取数据时，验证租户权限中的$accessdatalimit
			$user = isset($_SESSION['user']) ? $_SESSION['user'] : Authorization::getUserFromToken();
			$maxcount = !empty($user->accessdatalimit) ? $user->accessdatalimit : 0;
			if($form['output']['count'] > $maxcount){
				$outlimit['output'][] = "countlimit";
			}
		}
		else{
			if(count($authJson['output']['countlimit']['limit'])>0 && isset($form['output']['count'])){
				if($authJson['output']['countlimit']['limit'][0]['value']['minvalue'] !=null && $form['output']['count'] < $authJson['output']['countlimit']['limit'][0]['value']['minvalue']){
					$outlimit["output"][] = "countlimit";
				}
				if($authJson['output']['countlimit']['limit'][0]['value']['maxvalue']!=null && $form['output']['count'] > $authJson['output']['countlimit']['limit'][0]['value']['maxvalue']){
					$outlimit["output"][] = "countlimit";
				}
			}
		}
		if(!empty($outlimit) && count($outlimit)>0 && (count($outlimit["filter"]) > 0 || count($outlimit["facet"]) > 0 || count($outlimit["facetlimit"]) > 0 || count($outlimit["select"]) > 0 || count($outlimit["output"]) > 0)){
			$validate = array();
			$validate["outlimit"] = $outlimit;
			return $validate;
		}
		else{
			return array();
		}
	}
	/*
	 * 函数对每一个表单字段的权限进行验证
	 * $formfilter 页面提交表单对应字段
	 * $accountfilter 计费规则中对应字段
	 * $instancefilter 实例json中对应字段
	 * */
	public static function validatingQueryform($formfiltervalue, $accountfilter){

		switch($formfiltervalue["fieldvalue"]["datatype"])
		{
			case "int":
			case "string":
				switch($accountfilter["integral"])
				{
					case "list":
						if(count($accountfilter["limit"]) > 0){
							if(!in_array($formfiltervalue["fieldvalue"]["value"],$accountfilter["limit"])){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "int or string list out limit");
							}
						}
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			case "range":
				switch($accountfilter["integral"])
				{
					case "list":
						break;
					case "range":
						$form_value = $formfiltervalue["fieldvalue"]["value"];
						if($accountfilter["minvalue"] != null && $accountfilter["maxvalue"]!=null){
							if($form_value["start"]<$accountfilter["minvalue"]||$form_value["start"]>$accountfilter["maxvalue"]){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "range range out limit");
							}
							if($form_value["end"]<$accountfilter["minvalue"]||$form_value["end"]>$accountfilter["maxvalue"]){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "range ragne out limit");
							}
						}
						break;
					default;
					break;
				}
				break;
			case "time_dynamic":
				switch($accountfilter["integral"]){
					case "list":
						break;
					case "range":
						if($accountfilter["limit"]["start"]!=null || $accountfilter["limit"]["start"]!=null || $accountfilter["limit"]["intervalstart"]!=null || $accountfilter["limit"]["intervalend"]!=null){
							if($formfiltervalue["fieldvalue"]["timegap"]["start"] < $accountfilter["limit"]["intervalstart"] || $formfiltervalue["fieldvalue"]["timegap"]["start"] > $accountfilter["limit"]["intervalend"]){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "time_dynamic range out limit");
							}
							if($formfiltervalue["fieldvalue"]["timegap"]["end"] < $accountfilter["limit"]["intervalstart"] || $formfiltervalue["fieldvalue"]["timegap"]["end"] > $accountfilter["limit"]["intervalend"]){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "time_dynamic range out limit");
							}
							if($formfiltervalue["fieldvalue"]["value"]["start"]<$accountfilter["limit"]["start"]||$formfiltervalue["fieldvalue"]["value"]["start"]>$accountfilter["limit"]["end"]){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "time_dynamic range out limit");
							}
							if($formfiltervalue["fieldvalue"]["value"]["end"]<$accountfilter["limit"]["start"]||$formfiltervalue["fieldvalue"]["value"]["end"]>$accountfilter["limit"]["end"]){
								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "time_dynamic range out limit");
							}
						}
						break;
					default:
						break;
				}
				break;
			case "value_text_object":
				switch($accountfilter["integral"])
				{
					case "list":
						if(count($accountfilter["limit"]["value"])>0) {
							if(!in_array($formfiltervalue["fieldvalue"]["value"]["value"],$accountfilter["limit"]["value"])) {

								setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "value_text_object list out limit");
							}
						}
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			case "array":
				switch($accountfilter["integral"])
				{
					case "list":
						if(count($accountfilter["limit"])>0) {
							$fieldvalueArr = getFilterValue($formfiltervalue["fieldvalue"]);
							foreach($fieldvalueArr as $key=>$value){
								if(is_array($value)){
									if(isset($value["value"])){
										if(isset($accountfilter["limit"]["value"])){
											if(!in_array($value["value"],$accountfilter["limit"]["value"])) {
												setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "array value_text_object out limit");
											}
										}
										else{
											if(!in_array($value["value"],$accountfilter["limit"])) {
												setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "array limit is not object  value_text_object out limit");
											}
										}
									}
									else if(isset($value["start"])){
										if($accountfilter["minvalue"] != null && $accountfilter["maxvalue"]!=null){
											if($value["start"]<$accountfilter["minvalue"]||$value["start"]>$accountfilter["maxvalue"]){
												setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "array range out limit");
											}
											if($value["end"]<$accountfilter["minvalue"]||$value["end"]>$accountfilter["maxvalue"]){
												setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "array range out limit");
											}
										}
									}
								}
								else{
									if(!in_array($value,$accountfilter["limit"]["value"])) {
										setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "array int string out limit");
									}
								}
							}
						}
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			default:
				break;
		}
		return VALIDATE_SUCCESS;
	}
	/*
	 * 对保存验证表单合法性,包括修改allowcontrol值,
	 * $form 表单提交json
	 * $account 获取计费规则信息
	 * $instance 实例json
	 * $tenantbill 获取计费信息
	 * */
	public static function validatingSaveformAll($form, $account, $instance, $tenantbill){
		foreach($form["filtervalue"] as $k=>$v){
			$formfiltervalue = $v;
			$accountfiltervalue = $account["filtervalue"][$k];
			$instancefiltervalue = $instance["filtervalue"][$k];
			$tenantbillvalue = $tenantbill["filtervalue"][$k];
			$formfilter = $form["filter"][$v["fieldname"]];
			$accountfilter= $account["filter"][$v["fieldname"]];
			$instancefilter= $instance["filter"][$v["fieldname"]];
			$tenantbillfilter= $tenantbill["filter"][$v["fieldname"]];
			$tenantbilldres = Authorization::validatingSaveform($formfiltervalue, $formfilter, $accountfiltervalue, $accountfilter, $instancefiltervalue, $instancefilter, $tenantbillvalue, $tenantbillfilter);
			if($tenantbilldres){
				$tenantbill["filter"][$v["fieldname"]]["allowcontrol"] = $tenantbilldres;
			}
			else{
				return false;
			}
		}
		return $tenantbill;

	}
	/*
	 * 先判断allowcontrol=0时不允许修改 否则 在限制范围内修改*/
	public static function validatingSaveform($formfiltervalue, $formfilter, $accountfiltervalue, $accountfilter, $instancefiltervalue, $instancefilter, $tenantbillvalue, $tenantbillfilter){
		$flag = true;
		switch($formfiltervalue["fieldvalue"]["datatype"])
		{
			case "int":
				switch($formfilter["integral"])
				{
					case "list":
						if($accountfilter["allowcontrol"] == 0){
							if($formfiltervalue["fieldvalue"]["value"]!=$accountfiltervalue["fieldvalue"]["value"]){  //单值列表
								$flag = false;
							}
						}
						else{
							if(!in_array($formfiltervalue["fieldvalue"]["value"],$accountfilter["limit"])){
								$flag = false;
							}
						}
						/*
						 if($formfiltervalue["fieldvalue"]["value"]!=$instancefiltervalue["fieldvalue"]["value"]){
							if($tenantbillfilter["allowcontrol"]>0){
							//如果修改过默认值则计费规则表中的["allowcontrol"]-1
							$tenantbillfilter["allowcontrol"]--;
							}
							else{
							$flag = false;
							}
							}
						 */
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			case "string":
				switch($formfilter["integral"])
				{
					case "list":
						if($accountfilter["allowcontrol"] == 0){
							if($formfiltervalue["fieldvalue"]["value"]!=$accountfiltervalue["fieldvalue"]["value"]){  //单值列表
								$flag = false;
							}
						}
						else{
							if(!in_array($formfiltervalue["fieldvalue"]["value"],$accountfilter["limit"])){
								$flag = false;
							}
						}
						/*
						 if($formfiltervalue["fieldvalue"]["value"] != $instancefiltervalue["fieldvalue"]["value"]){
							if($tenantbillfilter["allowcontrol"]>0){
							//如果修改过默认值则计费规则表中的["allowcontrol"]-1
							$tenantbillfilter["allowcontrol"]--;
							}
							else{
							$flag = false;
							}
							}
						 */
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			case "range":
				switch($formfilter["integral"])
				{
					case "list":
						break;
					case "range":
						$form_value = $formfiltervalue["fieldvalue"]["value"];
						if($accountfilter["allowcontrol"] == 0){
							if($form_value["start"]!=$accountfiltervalue["fieldvalue"]["value"]["start"] || $form_value["end"]!=$accountfiltervalue["fieldvalue"]["value"]["end"]){
								$flag = false;
							}
						}
						else{
							if($form_value["start"]<$accountfilter["minvalue"]||$form_value["start"]>$accountfilter["maxvalue"]){
								$flag = false;
							}
							if($form_value["end"]<$accountfilter["minvalue"]||$form_value["end"]>$accountfilter["maxvalue"]){
								$flag = false;
							}
						}
						/*
						 if($form_value["start"]!=$instancefiltervalue["fieldvalue"]["value"]["start"] || $form_value["end"]!=$instancefiltervalue["fieldvalue"]["value"]["end"]){
						 if($tenantbillfilter["allowcontrol"]>0){
						 //如果修改过默认值则计费规则表中的["allowcontrol"]-1
						 $tenantbillfilter["allowcontrol"]--;
						 }
						 else{
						 $flag = false;
						 }
						 }
						 */
						break;
					default;
					break;
				}
				break;
			case "time_dynamic":
				switch($formfilter["integral"]){
					case "list":
						break;
					case "range":
						if($accountfilter["allowcontrol"] ==0){
							if($formfiltervalue["fieldvalue"]["value"]["start"] != $accountfiltervalue["fieldvalue"]["value"]["start"] || $formfiltervalue["fieldvalue"]["value"]["end"] != $accountfiltervalue["fieldvalue"]["value"]["end"]){
								$flag = false;
							}
						}
						else{
							if($formfiltervalue["fieldvalue"]["timegap"]["start"] < $accountfilter["limit"]["intervalstart"] || $formfiltervalue["fieldvalue"]["timegap"]["start"] > $accountfilter["intervalend"]){
								$flag = false;
							}
							if($formfiltervalue["fieldvalue"]["timegap"]["end"] < $accountfilter["limit"]["intervalstart"] || $formfiltervalue["fieldvalue"]["timegap"]["end"] > $accountfilter["intervalend"]){
								$flag = false;
							}
							if($formfiltervalue["fieldvalue"]["value"]["start"]<$accountfilter["limit"]["start"]||$formfiltervalue["fieldvalue"]["value"]["start"]>$accountfilter["limit"]["end"]){
								$flag = false;
							}
							if($formfiltervalue["fieldvalue"]["value"]["end"]<$accountfilter["limit"]["start"]||$formfiltervalue["fieldvalue"]["value"]["end"]>$accountfilter["limit"]["end"]){
								$flag = false;
							}
						}
						/*
						 if($formfiltervalue["fieldvalue"]["value"]["start"]!=$instancefiltervalue["fieldvalue"]["value"]["start"] || $$formfiltervalue["fieldvalue"]["value"]["end"]!=$instancefiltervalue["fieldvalue"]["value"]["end"]){
						 if($tenantbillfilter["allowcontrol"]>0){
						 //如果修改过默认值则计费规则表中的["allowcontrol"]-1
						 $tenantbillfilter["allowcontrol"]--;
						 }
						 else{
						 $flag = false;
						 }
						 }
						 */
						break;
					default:
						break;
				}
				break;
			case "value_text_object":
				switch($formfilter["integral"])
				{
					case "list":
						if($accountfilter["allowcontrol"]==0)
						{
							if($formfiltervalue["fieldvalue"]["value"]["value"] != $accountfiltervalue["fieldvalue"]["value"]["value"]){
								$flag = false;
							}
							if($formfiltervalue["fieldvalue"]["value"]["text"] != $accountfiltervalue["fieldvalue"]["value"]["text"]){
								$flag = false;
							}
						}
						else{
							if(!in_array($formfiltervalue["fieldvalue"]["value"]["value"],$accountfilter["limit"]["value"])) {
								$flag = false;
							}
							if(!in_array($formfiltervalue["fieldvalue"]["value"]["text"],$accountfilter["limit"]["text"])) {
								$flag = false;
							}
						}
						/*
						 if($formfiltervalue["fieldvalue"]["value"]["value"] != $instancefiltervalue["fieldvalue"]["value"]["value"]){
						 if($tenantbillfilter["allowcontrol"]>0){
						 //如果修改过默认值则计费规则表中的["allowcontrol"]-1
						 $tenantbillfilter["allowcontrol"]--;
						 }
						 else{
						 $flag = false;
						 }
						 }
						 if($formfilter["value"]["text"] != $instancefilter["value"]["text"]){
						 if($tenantbillfilter["allowcontrol"]>0){
						 //如果修改过默认值则计费规则表中的["allowcontrol"]-1
						 $tenantbillfilter["allowcontrol"]--;
						 }
						 else{
						 $flag = false;
						 }
						 }
						 */
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			case "array":
				switch($formfilter["integral"])
				{
					case "list":
						if($accountfilter["allowcontrol"]==0)
						{
							$formfieldvalueArr = getFilterValue($formfiltervalue["fieldvalue"]);
							$accountfieldvalueArr = getFilterValue($accountfiltervalue["fieldvalue"]);
							$diff = array_diff($formfieldvalueArr, $accountfieldvalueArr);
							if(!empty($diff)){
								$flag = false;
							}
						}
						else{
							$formfieldvalueArr = getFilterValue($formfiltervalue["fieldvalue"]);
							foreach($formfieldvalueArr as $key=>$value){
								if(!in_array($value,$accountfilter["limit"]["value"])){
									$flag = false;
								}
							}
						}
						break;
					case "range":
						break;
					default;
					break;
				}
				break;
			default:
				break;
		}
		if($flag){
			return $tenantbillfilter["allowcontrol"];
		}
		else{
			return $flag;
		}
	}
	/*
	 * 查询select时验证
	 */
	public static function validatingQueryformSelect($formselect , $accountselect){
		if(count($accountselect["limit"]) > 0){
			if(count($formselect["value"])>0){
				foreach($formselect["value"] as $key=>$value){
					if(!is_array($value, $accountselect["limit"])){
						setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "validatingQueryformSelect out limit");
					}
				}
			}
			else{ //空数组
				setErrorMsg(VALIDATE_ERROR_NULLVALUE, "validatingQueryformSelect select value is null");
			}
		}
		return VALIDATE_SUCCESS;
	}
	/*
	 * 查询时output单独验证
	 * */
	public static function validatingQueryOutput($formoutput, $accountoutput){
		if($accountoutput["limit"]!=null){
			if(!in_array($formoutput["orderby"],$accountoutput["limit"])){
				setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "validatingQueryOutput out limit");
			}
		}
		return VALIDATE_SUCCESS;
	}
	public static function validatingQueryFacet($formfacet, $accountfacet){
		if(count($accountfacet["limit"])>0){
			if(count($formfacet["field"])>0){
				foreach($formfacet["field"] as $key=>$value){
					if(!in_array($value["name"], $accountfacet["limit"])){
						setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "validatingQueryFacet out limit");
					}
				}
			}
			if(count($formfacet["range"])>0){  // gap start end 是在最大值中间 选择不会超出范围,不需要验证
				foreach($formfacet["range"] as $key=>$value){
					if(!in_array($value["name"], $accountfacet["limit"])){
						setErrorMsg(VALIDATE_ERROR_OUTLIMIT, "validatingQueryFacet out limit");
					}
				}
			}
		}
		return VALIDATE_SUCCESS;
	}
	/*
	 * 查询select时验证
	 */
	public static function validatingSaveformSelect($formselect , $accountselect , $instanceselect, $tenantbillselect){
		$flag = true;
		if($accountselect["allowcontrol"] == 0){
			$diff = array_diff($formselect["value"], $accountselect["value"]);
			if(!empty($diff)){
				$flag = false;
			}
		}
		else{
			if(count($formselect["value"])>0){
				foreach($formselect["value"] as $key=>$value){
					if(!is_array($value, $accountselect["limit"])){
						return false;
					}
				}
			}
		}
		/*
		 $diff2 = array_diff($formselect["value"],$instanceselect["value"]);
		 if(!empty($diff2)){
		 if($tenantbillselect["allowcontrol"]>0){
		 //如果修改过默认值则计费规则表中的["allowcontrol"]-1
		 $tenantbillselect["allowcontrol"]--;
		 }
		 else{
		 $flag = false;
		 }

		 }
		 */
		if($flag){
			return $tenantbillselect["allowcontrol"];
		}
		else{
			return $flag;
		}
	}

	/*
	 * 保存时output单独验证
	 *
	 */
	public static function validatingSaveOutput($formoutput, $accountoutput, $instanceoutput, $tenantbilloutput){
		$flag = true;
		if($accountoutput["allowcontrol"] == 0){
			if($formoutput["orderby"]!=$accountoutput["orderby"]){  //单值列表
				$flag = false;
			}
		}
		else{
			if(!in_array($formoutput["orderby"], $accountoutput["limit"])){
				$flag = false;
			}
		}
		/*
		 if($formoutput["orderby"]!=$instanceoutput["orderby"]){
		 if($tenantbilloutput["allowcontrol"]>0){
		 //如果修改过默认值则计费规则表中的["allowcontrol"]-1
		 $tenantbilloutput["allowcontrol"]--;
		 }
		 else{
		 $flag = false;
		 }

		 }
		 */
		if($flag){
			return $tenantbilloutput["allowcontrol"];
		}
		else{
			return false;
		}
	}
	/*
	 * 保存时output单独验证
	 *
	 */
	public static function validatingSaveFacet($formfacet, $accountfacet, $instancefacet, $tenantbillfacet){
		$flag = true;
		if($accountfacet["allowcontrol"] == 0){
			if(count($formfacet["field"]) >0){
				foreach($formfacet["field"] as $key=>$value){
					if($value["name"] != $accountfacet[$key]["name"]){
						$flag = false;
					}
					foreach($value["filter"] as $k=>$v){
						if($v["type"]!=$accountfacet[$key]["filter"][$k]["type"]){
							$flag = false;
						}
						$diff = array_diff($v["value"], $accountfacet[$key]["filter"][$k]["value"]);
						if(!empty($diff)){
							$flag = false;
						}
					}
				}
			}
			if(count($formfacet["range"]) >0){
				foreach($formfacet["range"] as $key=>$value){
					$sfv[] = $value["name"];
					$siv[] = $instancefacet[$key]["name"];
					if($value["gap"]!=$instancefacet[$key]["gap"]){
						$flag = false;
					}
					if($value["start"]!=$instancefacet[$key]["start"]){
						$flag = false;
					}
					if($value["end"]!=$instancefacet[$key]["end"]){
						$flag = false;
					}

				}
				$diff2 = array_diff($sfv, $siv);
				if(!empty($diff2)){
					$flag = false;
				}
			}
		}
		else{
			if(count($formfacet["field"])>0){
				foreach($formfacet["field"] as $key=>$value){
					if(!in_array($value["name"],$accountfacet["limit"])){
						$flag = false;
					}
				}
			}
			if(count($formfacet["range"])>0){
				foreach($formfacet["range"] as $key=>$value){
					if(!in_array($value["name"] , $accountfacet["limit"])){
						$flag = false;
					}
				}
			}
		}
		/*
		 //计费
		 if(count($formfacet["field"]) >0){
			foreach($formfacet["field"] as $key=>$value){
			$formname[] = $value["name"];
			$accountname[] = $accountfacet[$key]["name"];
			foreach($value["filter"] as $k=>$v){
			$formtype[] = $v["type"];
			$accounttype[] = $accountfacet[$key]["filter"][$k]["type"];
			foreach($v["value"] as $i=>$m){
			$formval[] = $m;
			}
			foreach($accountfacet[$key]["filter"][$k]["value"] as $i=>$m){
			$accountval[] = $m;
			}
			}
			}
			}
			$accountflag = true;
			$diff1 = array_diff($formname, $accountname);
			if(!empty($diff1)){
			$accountflag = false;
			}
			$diff2 = array_diff($formtype, $accounttype);
			if(!empty($diff2)){
			$accountflag = false;
			}
			$diff3 = array_diff($formval, $accountval);
			if(!empty($diff3)){
			$accountflag = false;
			}
			if(!$accountflag){
			if($tenantbillfacet["allowcontrol"]>0){
			//如果修改过默认值则计费规则表中的["allowcontrol"]-1
			$tenantbillfacet["allowcontrol"]--;
			}
			else{
			$flag = false;
			}
			}

			/////////////////////range
			$rangeflag = true;
			if(count($formfacet["range"]) >0){
			foreach($formfacet["range"] as $key=>$value){
			$formrangename[] = $value["name"];
			$accountrangename[] = $accountfacet[$key]["name"];
			if($value["gap"]!=$accountfacet[$key]["gap"]){
			$rangeflag = false;
			}
			if($value["start"]!=$accountfacet[$key]["start"]){
			$rangeflag = false;
			}
			if($value["end"]!=$accountfacet[$key]["end"]){
			$rangeflag = false;
			}

			}
			}
			$diff1 = array_diff($formrangename, $accountrangename);
			if(!empty($diff1)){
			$rangeflag = false;
			}
			if(!$rangeflag){
			if($tenantbillfacet["allowcontrol"]>0){
			//如果修改过默认值则计费规则表中的["allowcontrol"]-1
			$tenantbillfacet["allowcontrol"]--;
			}
			else{
			$flag = false;
			}
			}
		 */
		if($flag){
			return $tenantbillfacet["allowcontrol"];
		}
		else{
			return false;
		}
	}


	/*
	 * 验证session是否合法
	 * 判断是否是第三方的请求，第三方的不判断session，只取token。内部的只判断session不取token
	 */
	public static function checkUserSession($needreturnuser = false){
		global $logger;
		if(isSameDomain() === true){//相同域名
			if(isset($_SESSION['user']) && $_SESSION['user'] != null){
				$user = $_SESSION['user'];
				if($user->tenantid == '-1'){//系统用户
					return CHECKSESSION_SUCCESS;
				}
				else{
					$localtype=getLocalType();//获取当前登录的平台类型
					$tcode = getTenantCode();//获取二级域名
					$now = time();
					if(empty($tcode)){
						return CHECKSESSION_ADDRERROR;
					}
					else if($localtype != $user->localtype){//判断租户类型
						return CHECKSESSION_ADDRERROR;
					}
					else if($tcode != $user->weburl){
						return CHECKSESSION_ADDRERROR;
					}
					if(!empty($user->userexpiretime) && $now > $user->userexpiretime){
						return CHECKSESSION_USEREXPIRETIME;
					}
					return CHECKSESSION_SUCCESS;
				}
			}
			else{
				return CHECKSESSION_NULL;
			}
		}
		else{ //不同域
			if(!empty($_REQUEST['token'])){
				$accesstoken = getTokenParam();
				$detoken = authcode($accesstoken, 'DECODE', TOKENKEY);
				if(empty($detoken)){
					return CHECKSESSION_NULL;
				}
				else{
					$usertype=getLocalType();//获取当前登录的平台类型
					if(empty($usertype)){
						return CHECKSESSION_ADDRERROR;
					}
					$tcode = getTenantCode();//获取二级域名
					if(empty($tcode)){
						return CHECKSESSION_ADDRERROR;
					}
					$user = Authorization::getUser($tcode, $usertype, $detoken);

					if($user == null){
						return CHECKSESSION_NULL;
					}
					else{
						$now = time();
						if(!empty($user['expiretime']) && $now > $user['expiretime']){
							return CHECKSESSION_USEREXPIRETIME;
						}
						if($needreturnuser){
							return $user;
						}
						else{
							return CHECKSESSION_SUCCESS;
						}
					}
				}
			}
			else{
				return CHECKSESSION_NULL;
			}
		}
		/*
		if(!isset($_SESSION['user']) || $_SESSION['user'] == null){
			if(!empty($_REQUEST['token'])){
				$accesstoken = getTokenParam();
				$detoken = authcode($accesstoken, 'DECODE', TOKENKEY);
				if(empty($detoken)){
					return CHECKSESSION_NULL;
				}
				else{
					$usertype=getLocalType();//获取当前登录的平台类型
					if(empty($usertype)){
						return CHECKSESSION_ADDRERROR;
					}
					$tcode = getTenantCode();//获取二级域名
					if(empty($tcode)){
						return CHECKSESSION_ADDRERROR;
					}
					$user = Authorization::getUser($tcode,$usertype, $detoken);
					if($user == null){
						return CHECKSESSION_NULL;
					}
					else{
						$_SESSION['user'] = $user;
						return CHECKSESSION_SUCCESS;
					}
				}
			}
			else{
				return CHECKSESSION_NULL;
			}
		}
		else{
			$user = $_SESSION['user'];
			if($user->tenantid == '-1'){//系统用户
				return CHECKSESSION_SUCCESS;
			}
			else{
				$localtype=getLocalType();//获取当前登录的平台类型
				$tcode = getTenantCode();//获取二级域名
				if(empty($tcode)){
					return CHECKSESSION_ADDRERROR;
				}
				else if($localtype != $user->localtype){//判断租户类型
					return CHECKSESSION_ADDRERROR;
				}
				else if($tcode != $user->weburl){
					return CHECKSESSION_ADDRERROR;
				}
				return CHECKSESSION_SUCCESS;
			}
		}
		 */
	}
	/**
	 *
	 * 验证用户是否有权限使用资源
	 * @param $resourceid 资源ID
	 * @param $resourceType 资源类型
	 * @param $childid 子资源ID
	 */
	public static function checkUserUseage($resourceid,$resourceType,$childid=null)
	{
		if(isset($_SESSION['user']) && $_SESSION['user'] == null){
			return false;
		}
		else{
			$user = isset($_SESSION['user']) ? $_SESSION['user'] : Authorization::getUserFromToken();
			if($user->tenantid == -1 && $resourceType != RESOURCE_TYPE_SYSTEM){
				return false;//系统用户只能用系统用户资源
			}
			if($user->tenantid != -1 && $resourceType != RESOURCE_TYPE_TENANT){
				return false;
			}
			switch ($resourceType){
				case RESOURCE_TYPE_SYSTEM:
					$hasauth = in_array($resourceid, $user->systemResource);
					if($hasauth){
						//存在子资源
						if($childid != null && isset($user->systemResourceChildren[$resourceid])){
							$hasauth = in_array($childid, $user->systemResourceChildren[$resourceid]);
						}
					}
					return $hasauth;
					break;
				case RESOURCE_TYPE_TENANT:
					return in_array($resourceid, $user->tenantResource);
				default:
					return false;
			}
		}
	}

	/**
	 *
	 * 创建用户session
	 * @param 关联数组 $dbuser
	 */
	public static function createUserSession($dbuser){
		$userid = $dbuser["userid"];
		$binduserid = $dbuser["binduserid"];
		$usertype = $dbuser["usertype"];
		$alloweditinfo = $dbuser["alloweditinfo"];
		$tenantid = $dbuser["tenantid"];
		$allowlinkage = !empty($dbuser["allowlinkage"]);
		$allowdrilldown = !empty($dbuser["allowdrilldown"]);
		$localtype = $dbuser["localtype"];
		$securl = $dbuser["weburl"];
		$user = new UserInfo($userid,$tenantid,$localtype,$securl, $allowlinkage, $allowdrilldown, $binduserid, $usertype, $alloweditinfo );
		$user->allowdownload = !empty($dbuser['allowdownload']);
		$user->allowupdatesnapshot = !empty($dbuser['allowupdatesnapshot']);
		$user->alloweventalert = !empty($dbuser['alloweventalert']);
		$user->allowoverlay = !empty($dbuser['allowoverlay']);
		$user->allowlinkage = !empty($dbuser['allowlinkage']);
		$user->allowwidget = !empty($dbuser['allowwidget']);
		$user->userexpiretime = !empty($dbuser['expiretime']) ? $dbuser['expiretime'] : NULL;
		$user->allowaccessdata = !empty($dbuser['allowaccessdata']);
		$user->accessdatalimit = $dbuser['accessdatalimit'];
		$user->allowvirtualdata = !empty($dbuser['allowvirtualdata']);
		if($usertype == 2){ //只读用户使用使用所属用户的userid
			$userid = $dbuser["binduserid"];
		}
		else{
			$userid = $dbuser["userid"];
		}
		Authorization::setUserRole($user, $userid,$tenantid);
		return $user;
	}
	public static function getUserFromToken(){
		global $logger;
		if(!empty($_REQUEST['token'])){
			$accesstoken = getTokenParam();
			$detoken = authcode($accesstoken, 'DECODE', TOKENKEY);
			if(empty($detoken)){
				return CHECKTOKEN_NULL;
			}
			else{
				$usertype=getLocalType();//获取当前登录的平台类型
				if(empty($usertype)){
					return CHECKSESSION_ADDRERROR;
				}
				$tcode = getTenantCode();//获取二级域名
				if(empty($tcode)){
					return CHECKSESSION_ADDRERROR;
				}
				$result = Authorization::getUser($tcode, $usertype, $detoken);
				if($result == null){
					return CHECKTOKEN_USERNULL;
				}
				else{
					$user = Authorization::createUserSession($result);
					return $user;
				}
			}
		}
		else{
			return CHECKTOKEN_NULL;
		}
	}

	public static function getUserFromToken4($token,$usertype=LOCALTYPE_TENANT,$tcode){
		global $logger;
		if (!empty($token)) {
			$accesstoken = str_replace(" ", "+", $token);//get提交时，加号被decode成空格，此时改回去
		}else{
			throw new Exception("getUserFromToken by token exception. supported token null.");
		}

		$detoken = authcode($accesstoken, 'DECODE', TOKENKEY);
		if(empty($detoken)){
			throw new Exception("getUserFromToken by token exception. cannot decode password by token.");
		}
		else{
			if(empty($usertype)){
				$usertype=getLocalType();//获取当前登录的平台类型
				if(empty($usertype)){
					return CHECKSESSION_ADDRERROR;
				}
			}

			if(empty($tcode)){
				$tcode = getTenantCode();//获取二级域名
				if(empty($tcode)){
					return CHECKSESSION_ADDRERROR;
				}
			}

			$result = Authorization::getUser($tcode, $usertype, $detoken);
			if($result == null){
				return CHECKTOKEN_USERNULL;
			}
			else{
				$user = Authorization::createUserSession($result);
				return $user;
			}
		}
	}
	/**
	 *
	 * 获取用户对象, 只有在使用token时访问此函数
	 * @param $securl 租户domain
	 * @param unknown_type $localtype
	 * @param unknown_type $username
	 */
	public static function getUser($securl,$localtype, $username){
		global $logger;
		$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_NAME,FALSE);
		$sql = "select a.*, b.localtype,b.weburl,b.allowlinkage,b.allowvirtualdata, b.allowdrilldown,b.allowdownload,b.allowupdatesnapshot,b.alloweventalert,b.allowoverlay, b.allowwidget, 
			b.allowaccessdata, b.accessdatalimit from users a inner join tenant b on a.tenantid=b.tenantid
        	where a.username = '{$username}' and b.weburl='{$securl}' and b.localtype={$localtype}";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
			return null;
		}
		else{
			$result = $dsql->GetArray($qr, MYSQL_ASSOC);
			if(!empty($result)){
				return $result;
				//return Authorization::createUserSession($result);
			}
			else{
				return null;
			}
		}
	}

	/**
	 *
	 * 设置user对象的角色信息
	 * @param UserInfo $user
	 */
	public static function setUserRole(&$user, $userid,$tenantid){
		global $dsql,$logger;
		$rolesql ="select roleid,roletype from ".DATABASE_USER_ROLE_MAPPIMG." where userid = ".$userid;
		$qrole = $dsql->ExecQuery($rolesql);
		if(!$qrole){
			$logger->error(__FILE__." func:".__FUNCTION__." sql:{$rolesql} ".$dsql->GetError());
		}
		else
		{
			while ($r = $dsql->GetArray($qrole, MYSQL_ASSOC))
			{
				//获取当前用户的所有角色
				$user->roles[] = $r['roleid'];
				$resourcesql = "";
				switch ($r['roletype']){
					case RESOURCE_TYPE_SYSTEM:
						$resourcesql = "select resourceid,childid from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid =".$r["roleid"];
						$qrres = $dsql->ExecQuery($resourcesql);
						if(!$qrres){
						}
						else
						{
							while ($res = $dsql->GetArray($qrres, MYSQL_ASSOC)){
								$resid = $res['resourceid'];
								if(!empty($res['childid'])){
									//子ID 不为空，以resourceid为key，childid存到子数组
									$user->systemResourceChildren[$resid][] = $res['childid'];
									//将resourceid存到资源数组
									if(!in_array($resid, $user->systemResource)){
										$user->systemResource[] = $resid;
									}
								}
								else{
									$user->systemResource[] = $resid;
								}
							}
						}
						break;
					case RESOURCE_TYPE_TENANT:
						$resourcesql = "select resourceid from ".DATABASE_ACCOUNTING_RULE." where tenantid={$tenantid} and roleid={$r["roleid"]}";
						$qrres = $dsql->ExecQuery($resourcesql);
						if(!$qrres){
						}
						else
						{
							while ($res = $dsql->GetArray($qrres, MYSQL_ASSOC)){
								$user->tenantResource[] = $res['resourceid'];
							}
						}
						break;
					default:
						break;
				}
			}
		}
	}
}
?>
