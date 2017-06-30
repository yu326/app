<?php
include_once 'includes.php';
include_once('commonFun.php');
include_once('userinfo.class.php');
define('CHECKSESSION_SUCCESS', 1);
define('CHECKSESSION_NULL', 0);//SESSION不存在
define('CHECKSESSION_NOUSER', -1);//用户不存在
define('CHECKSESSION_ADDRERROR', 2);//地址错误
define('CHECKSESSION_USEREXPIRETIME', 7);


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
/**
 * 权限验证函数类,包含查询验证和保存验证
 */
class Authorization
{
	/*
	 * 查询验证, 对提交的所有表单,如果合法,修改实例json中对应字段的值(getdata.php中checkuserpure()调用)
	 * $form 表单提交json
	 * $account 计费规则json
	 * $instance 实例json
	 * */
	public static function validatingQueryformAll($form, $account){
		foreach($account["filter"] as $key=>$value){
			$valuearrs = getFilterValueItem($key,$form["filtervalue"]);
			if($value["maxlimitlength"] != -1 && count($valuearrs) > $value["maxlimitlength"]){
				return VALIDATE_ERROR_OUTLIMIT;
			}
			else{
				foreach ($valuearrs as $k=>$v){
					$fieldvalues = getFilterValue($v['fieldvalue']);
					if(is_array($fieldvalues) && !is_assoc($fieldvalues)){ //非关联数组
						foreach ($fieldvalues as $i => $item){
							if(checkFilterValue($account["filter"][$key],getFieldValue($v["fieldvalue"]["value"][$i]['datatype'], $item)) != 1){
								return VALIDATE_ERROR_OUTLIMIT;
							}
						}
					}
					else{
						if(checkFilterValue($account["filter"][$key],getFieldValue($value['datatype'], $fieldvalues)) != 1){
							return VALIDATE_ERROR_OUTLIMIT;
						}
					}
				}
			}
		}
		if(isset($account['facet']['filterlimit']) && count($account['facet']['filterlimit']['limit']) > 0){
			foreach($form['facet']['field'] as $i=>$field){
				$chkarrs = array();
				foreach ($field['filter'] as $j=>$ff){
					array_merge($chkarrs,$ff['value']);
				}
				foreach ($chkarrs as $k=>$v){
					if(checkFilterValue($account["facet"]['filterlimit'],$v) != 1){
						return VALIDATE_ERROR_OUTLIMIT;
					}
				}
			}
		}
		if(count($account['select']['limit']) > 0){
			foreach ($form['select']['value'] as $k=>$v){
				if(checkFilterValue($account["select"],$v) != 1){
					return VALIDATE_ERROR_OUTLIMIT;
				}
			}
		}
		if(count($account['output']['limit']) > 0 && isset($form["output"]['orderby'])){
			if(checkFilterValue($account["output"],$form["output"]['orderby']) != 1){
				return VALIDATE_ERROR_OUTLIMIT;
			}
		}
		return VALIDATE_SUCCESS;
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

	/**
	 * 
	 * 获取用户对象
	 * @param unknown_type $username
	 * @param unknown_type $password
	 */
	public static function getUser($username, $password=NULL){
		global $logger;
		$dsql = new DB_MYSQL(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_NAME,FALSE);
		$pwdwh = empty($password) ? "" : " and password='{$password}'";
		$sql = "select * from users where username = '{$username}' {$pwdwh} and tenantid=-1";
		$qr = $dsql->ExecQuery($sql);
		if(!$qr){
	        $logger->error(__FILE__." func:".__FUNCTION__." sql:{$sql} ".$dsql->GetError());
	        return null;
	    }
	    else{
	    	$result = $dsql->GetArray($qr, MYSQL_ASSOC);
            if(!empty($result)){
                $userid=$result["userid"];
                $user = new UserInfo($userid,-1,NULL,NULL);
                $user->userexpiretime = $result["expiretime"];
				return $user;
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
	public static function setUserRole(&$user){
		global $dsql,$logger;
		$rolesql ="select roleid,roletype from ".DATABASE_USER_ROLE_MAPPIMG." where userid = ".$user->userid;
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

	/*
	 * 验证session是否合法
	 */
	public static function checkUserSession(){
		global $logger;
		if(!isset($_SESSION['user']) || $_SESSION['user'] == null){
			if(!empty($_REQUEST['token'])){
				$accesstoken = $_REQUEST['token'];
				if(!empty($_GET['token'])){
					$accesstoken = str_replace(" ", "+", $accesstoken);//get提交时，加号被decode成空格，此时改回去
				}
				$detoken = authcode($accesstoken, 'DECODE', TOKENKEY);
				$logger->debug(__FUNCTION__." acctoken:{$accesstoken}, decode:{$detoken}");
				if(empty($detoken)){
					return CHECKSESSION_NULL;
				}
				else{
					$user = Authorization::getUser($detoken);
					if($user == null){
						return CHECKSESSION_NULL;
					}
					else{
						$now = time();
						if(!empty($user->expiretime) && $now > $user->expiretime){
							return CHECKSESSION_USEREXPIRETIME;
						}
						Authorization::setUserRole($user);
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
				else if(!empty($user->userexpiretime) && $now > $user->userexpiretime){
					return CHECKSESSION_USEREXPIRETIME;
				}
				return CHECKSESSION_SUCCESS;
			}
		}
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
		global $logger;
		if(!isset($_SESSION) || $_SESSION['user'] == null){
			return false;
		}
		else{
			$user = $_SESSION['user'];
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
}
?>
