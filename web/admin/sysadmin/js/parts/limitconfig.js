function LimitConfig(value,type,repeat,callback){
	var randid = Math.round(Math.random()*100000);
	var html = '<div id="LimitConfig_setonelimit'+randid+'" style="display:none">';
	html += '<div class="search"><span class="rightalign">限制值：</span><span id="LimitConfig_value'+randid+'"></span></div>';
	html += '<div class="search"><span class="rightalign">类型：</span><span id="LimitConfig_type'+randid+'"></span></div>';
	html += '<div class="search"><span class="rightalign">重复次数：</span>';
	html += '<input type="text" id="LimitConfig_repeat'+randid+'" value="1" class="shortestinput" />';
	html += '<input type="checkbox" id="LimitConfig_repeat_no'+randid+'"><label for="LimitConfig_repeat_no'+randid+'">无限</label></div></div>';
	var targetdiv = $("#LimitConfig_setonelimit"+randid+"");
	/*
	var LC_value = $("#LimitConfig_value"+randid+"");
	var LC_type = $("#LimitConfig_type"+randid+"");
	var LC_repeat = $("#LimitConfig_repeat"+randid+"");
	var LC_repeat_no = $("#LimitConfig_repeat_no"+randid+"");
	*/
	if(targetdiv.length == 0){
		$("body").append(html);
		targetdiv = $("#LimitConfig_setonelimit"+randid+"");
		targetdiv.dialog({
			width:300,
			height:200,
			modal:true,
			autoOpen:false,
			title:"设置权限"
		});
	}
	targetdiv.dialog("option","buttons",
			{
				"确定":function(){
					var rept;
					if(type == "inexact"){
						rept = $("#LimitConfig_repeat_no"+randid+"").prop("checked") ? -1 : parseInt($("#LimitConfig_repeat"+randid+"").val());
					}
					else{
						rept = 1;
					}
					callback({value:value,type:type,repeat:rept});
					targetdiv.dialog("close");
					targetdiv.remove();
				},
				"取消":function(){
					targetdiv.dialog("close");
					targetdiv.remove();
				}
			});
	$("#LimitConfig_repeat_no"+randid+"").bind("click",function(){
		if($(this).prop("checked")){
			$("#LimitConfig_repeat"+randid+"").val(-1);
			$("#LimitConfig_repeat"+randid+"").attr("disabled","disabled");
		}
		else{
			$("#LimitConfig_repeat"+randid+"").val(1);
			$("#LimitConfig_repeat"+randid+"").removeAttr("disabled");
		}
	});
	var typestr = "";
	var valuestr = "";
	switch(type){
	case 'exact':
		typestr = '精确匹配';
		valuestr = value;
		$("#LimitConfig_repeat_no"+randid+"").css("display","none");
		$("label[for=LimitConfig_repeat_no"+randid+"]").css("display","none");
		$("#LimitConfig_repeat"+randid+"").attr("disabled","disabled");
		break;
	case 'inexact':
		typestr = '模糊匹配';
		valuestr = value;
		$("#LimitConfig_repeat_no"+randid+"").css("display","");
		$("label[for=LimitConfig_repeat_no"+randid+"]").css("display","");
		$("#LimitConfig_repeat"+randid+"").removeAttr("disabled");
		break;
	case 'range':
		typestr = '区间';
		valuestr = value.minvalue + " 至 " + value.maxvalue;
		$("#LimitConfig_repeat_no"+randid+"").css("display","none");
		$("label[for=LimitConfig_repeat_no"+randid+"]").css("display","none");
		$("#LimitConfig_repeat"+randid+"").attr("disabled","disabled");
		break;
	default:
		break;
	}
	$("#LimitConfig_value"+randid+"").text(valuestr);
	$("#LimitConfig_type"+randid+"").text(typestr);
	if(parseInt(repeat,10) == -1){
		$("#LimitConfig_repeat_no"+randid+"").attr("checked",true);
		$("#LimitConfig_repeat"+randid+"").attr("disabled","disabled");
	}
	else{
		$("#LimitConfig_repeat_no"+randid+"").attr("checked",false);
		$("#LimitConfig_repeat"+randid+"").val(repeat);
	}
	
	targetdiv.dialog("open");
}
//关键词,作者昵称...
//精确匹配和模糊匹配的处理, 使用*进行模糊匹配
//当是精确匹配时直接添加, 模糊匹配时弹出选择窗选择匹配的次数
function limitConfigAdd(target, userArr){
	var txttarget = "#txt"+target;
	$.each(userArr, function(ui, uitem){
		var limittype = getTextLimitType(uitem);
		if(limittype == false){
			alert('格式有误，请重新输入');
			$(txttarget).val("");
			$(txttarget).focus();
			return false;
		}
		else{
			//情感字段, 拆分情感值显示为中文
			var tmpuitem = uitem;
			var emotypeattr = "";
			var codeattr = "";
			switch(target){
				case "emotion":
				case "ancestor_emotion":
				case "emoCombin":
				case "ancestor_emoCombin":
				case "emoNRN":
				case "ancestor_emoNRN":
				case "emoOrganization":
				case "ancestor_emoOrganization":
				case "emoTopic":
				case "ancestor_emoTopic":
				case "emoTopicKeyword":
				case "ancestor_emoTopicKeyword":
				case "emoTopicCombinWord":
				case "ancestor_emoTopicCombinWord":
					var code = tmpuitem.substr(0, tmpuitem.length-2).toString();
					var emotype = tmpuitem.substr(tmpuitem.length-1, 1);
					emotypeattr = "emotype='"+emotype+"'";
					codeattr = "code='"+code+"'";
					tmpuitem = code+"("+emoval2text(emotype)+")";
					break;
				default:
					break;
			}
			if(limittype == "exact"){
				var userhtml = "<span class='selwordsbox'><span class='useritem' limit_type='exact' limit_repeat='1' "+emotypeattr+" "+codeattr+" >"+tmpuitem+"</span>(1个)<a class='useritem_a' name='cancleselectedvaluetextobj' >×</a></span>";
				$("#end"+target).append(userhtml);
			}
			else{
				LimitConfig(tmpuitem,limittype,1,function(limitobj){
					var repeathtml="";
					if(limitobj.repeat == -1){
						repeathtml = "个数不限";
					}
					else{
						repeathtml = limitobj.repeat+"个";
					}
				var userhtml = "<span class='selwordsbox'><span class='useritem' limit_type='"+limitobj.type+"' limit_repeat='"+limitobj.repeat+"' "+emotypeattr+" "+codeattr+" >"+limitobj.value+"</span>("+repeathtml+")<a class='useritem_a' name='cancleselectedvaluetextobj'>×</a></span>";
				$("#end"+target).append(userhtml);
				});
			}
		}
	});
};

/**
 * 根据文本中的*字符，验证属于哪种limit类型（exact，inexact）
 * 文本中允许两边或一边出现*，例如： *张三* 
 */
function getTextLimitType(value){
	if(value == null || value == "*"){
		return false;
	}
	var matchrserr = value.match(/\*\*/g);//连续出现两个*
	var errcount = matchrserr ? matchrserr.length : 0;
	if(errcount > 0){
		return false;
	}
	var matchrs = value.match(/\*/g);
	var count = matchrs ? matchrs.length : 0;
	if(count == value.length){
		return false;
	}
	else if(count == 0){
		return 'exact';//没有*
	}
	else{
		return 'inexact';
	}
}

function checkLimitlength(accountfilter, newlimit){
	if(parseInt(accountfilter.maxlimitlength,10) == -1){
		return true;
	}
	var len = 0;
	var limitless = false;
	$(newlimit).each(function(i,item){
		if(parseInt(item.repeat,10) == -1){
			limitless = true;
		}
		else{
			len += parseInt(item.repeat,10);
		}
	});
	if(limitless){//如果某个limit的repeat为-1（无限循环），则必须指定maxlimitlength为不限
		return parseInt(accountfilter.maxlimitlength,10) == -1;
	}
	else{
		//alert("limit len" + len);
		//alert("maxlimit len" + accountfilter.maxlimitlength);
		return len <= parseInt(accountfilter.maxlimitlength,10);
	}
}
/**
 * 转义正则特殊符号
 * @param s
 * @returns
 */
function escapeRe(s){
    return s.replace(/([.+?^${}()|[\]\/\\])/g,"\\$1")
}

function getLimitValue(datatype,limititem){
	var value;
	switch(datatype){
	    case "blur_value_object":
		case "value_text_object":
			value = limititem.value.value;
			break;
		default:
			value = limititem.value;
			break;
	}
	return value;
}
function getTimeLimitMaxMin(item, tmpJson){
	var ret = {};
	if(item != ""){
		var datatype = tmpJson["filter"][item]["datatype"];
		switch(datatype){
			case "range":
				var nv = getLimitValue(datatype, tmpJson["filter"][item].limit[0]);
				ret.max = nv.maxvalue;
				ret.min = nv.minvalue;
				break;
			case "time_dynamic_state":
				var ov = getLimitValue(datatype, tmpJson["filter"][item].limit[0]);
				if(tmpJson["filter"][item].limit[0].name == "nearlytime"){
					var ntv = ov.start;
					var ntgap = ov.startgap;
					var nttimestate = ov.timestate;
					var nts = strtotime("-"+ntv+" "+ntgap+"");
					var nte = strtotime("now");
					var ovfinalret = getStateTime(nts, nte, "now", "", nttimestate);
					ret.min = ovfinalret.start;
					ret.max = ovfinalret.end;
				}
				else if(tmpJson["filter"][item].limit[0].name == "beforetime"){
					var btsv = ov.start;
					var btev = ov.end;
					var btegap = ov.endgap;

					var timestate = ov.timestate;
					var datestate = ov.datestate;
					var bts = strtotime("-"+btsv+" "+btegap+"");
					var bte = strtotime("-"+btev+" "+btegap+"");
					var ovfinalret = getStateTime(bts, bte, datestate, btegap, timestate);
					ret.min = ovfinalret.start;
					ret.max = ovfinalret.end;
				}
				break;
			case "time_dynamic_range":
				var ov = getLimitValue(datatype, tmpJson["filter"][item].limit[0]);
				//start
				var utsv = ov.start.start;
				var utsgap = ov.start.startgap;
				var utsthisgap = ov.start.startthisgap; 
				var utsto = ov.start.startto;
				var utstogap = ov.start.starttogap;

				//end
				var utev = ov.end.end;
				var utegap = ov.end.endgap;
				var utethisgap = ov.end.endthisgap; 
				var uteto = ov.end.endto;
				var utetogap = ov.end.endtogap;

				var us = sinceToThis(utsv, utsgap, utsthisgap, utsto, utstogap, "beginning");
				var ue = sinceToThis(utev, utegap, utethisgap, uteto, utetogap, "ending");
				var ovfinalret = getStateTime(us, ue, "now", "", "now");
				ret.min = ovfinalret.start;
				ret.max = ovfinalret.end;
				break;
			default:
				break;
		}
	}
	return ret;
}
/**
 * 验证表单输入的limit是否合法
 * @param filter 计费配置的filter
 * @param newlimit 表单中的limit，数组
 * @param oldlimit 更改之前的limit，数组
 */
function checkLimit(filter, newlimit, oldlimit){
	if(filter.limitcontrol == 0){
		if(filter.limit.length == 0 && newlimit.length == 0 && oldlimit.length == 0){
			return 1;
		}
		else if(oldlimit.length != newlimit.length){
			return -1;
		}
		else{
			var r = 1;
			$(newlimit).each(function(i,item){
				var nv = getLimitValue(filter.datatype,item);
				var iseq = false;
				$(oldlimit).each(function(j,olimit){
					var ov = getLimitValue(filter.datatype,olimit);
					if(olimit.type == 'range'){
						if(olimit.repeat == item.repeat && olimit.type == item.type 
								&& ov.maxvalue == nv.maxvalue && ov.minvalue == nv.minvalue){
							iseq = true;
							return false;
						}
					}
					else if(olimit.type == 'time_dynamic_range'){
						if(olimit.repeat == item.repeat && olimit.type == item.type
							&& ov.start.start == nv.start.start && ov.start.startgap == nv.start.startgap && ov.start.startto == nv.start.startto && ov.start.starttogap == nv.start.starttogap && ov.start.startthisgap == nv.start.startthisgap
							&& ov.end.end == nv.end.end && ov.end.endgap == nv.end.endgap && ov.end.endto == nv.end.endto && ov.end.endtogap == nv.end.endtogap && ov.end.endthisgap == nv.end.endthisgap ){
							iseq = true;
							return false;
						}
					}
					else if(olimit.type == 'time_dynamic_state'){
						if(olimit.repeat == item.repeat && olimit.type == item.type
							&& ov.start == nv.start && ov.startgap == nv.startgap && ov.end == nv.end && ov.endgap == nv.endgap
							&& ov.timestate == nv.timestate && ov.datestate == nv.datestate ){
							iseq = true;
							return false;
						}
					}
					else if(olimit.type == 'gaprange'){
						if(olimit.repeat == item.repeat && olimit.type == item.type 
								&& ov.maxvalue == nv.maxvalue && ov.minvalue == nv.minvalue && ov.gap == nv.gap){
							iseq = true;
							return false;
						}
					}
					else{
						if(olimit.repeat == item.repeat && olimit.type == item.type && ov == nv){
							iseq = true;
							return false;
						}
					}
				});
				if(iseq == false){
					r = -1;
					return false;
				}
			});
			return r;
		}
	}
	var limit = deepClone(newlimit);
	if(filter.limit.length > 0){
		if(filter.limit[0].type == 'range'){
			var ov = getLimitValue(filter.datatype,filter.limit[0]);
			var result = 1;
			$(limit).each(function(i,item){
				var nv = getLimitValue(filter.datatype,item);
				if(ov.maxvalue != null && (nv.maxvalue > ov.maxvalue || nv.minvalue > ov.maxvalue )){
					result = 0;
					return false;
				}
				else if(ov.minvalue != null && (nv.minvalue < ov.minvalue ||  nv.maxvalue < ov.minvalue)){
					result = 0;
					return false;
				}
			});
			return result;
		}
		else if(filter.limit[0].type == 'time_dynamic_range'){
			var ov = getLimitValue(filter.datatype, filter.limit[0]);
			//start
			var utsv = ov.start.start;
			var utsgap = ov.start.startgap;
			var utsthisgap = ov.start.startthisgap; 
			var utsto = ov.start.startto;
			var utstogap = ov.start.starttogap;

			//end
			var utev = ov.end.end;
			var utegap = ov.end.endgap;
			var utethisgap = ov.end.endthisgap; 
			var uteto = ov.end.endto;
			var utetogap = ov.end.endtogap;

			var us = sinceToThis(utsv, utsgap, utsthisgap, utsto, utstogap, "beginning");
			var ue = sinceToThis(utev, utegap, utethisgap, uteto, utetogap, "ending");
			var ovfinalret = getStateTime(us, ue, "now", "", "now");
			var result = 1;
			$(limit).each(function(li, litem){
				var nv = getLimitValue(filter.datatype, litem);
				//start
				var utsv = nv.start.start;
				var utsgap = nv.start.startgap;
				var utsthisgap = nv.start.startthisgap; 
				var utsto = nv.start.startto;
				var utstogap = nv.start.starttogap;

				//end
				var utev = nv.end.end;
				var utegap = nv.end.endgap;
				var utethisgap = nv.end.endthisgap; 
				var uteto = nv.end.endto;
				var utetogap = nv.end.endtogap;

				var us = sinceToThis(utsv, utsgap, utsthisgap, utsto, utstogap, "beginning");
				var ue = sinceToThis(utev, utegap, utethisgap, uteto, utetogap, "ending");
				var nvfinalret = getStateTime(us, ue, "now", "", "now");

				if(ovfinalret.end != null && (nvfinalret.end > ovfinalret.end || nvfinalret.minvfinalretalue > ovfinalret.end )){
					result = 0;
					return false;
				}
				else if(ovfinalret.start != null && (nvfinalret.start < ovfinalret.start ||  nvfinalret.maxvalue < ovfinalret.start)){
					result = 0;
					return false;
				}
			});
		}
		else if(filter.limit[0].type == 'time_dynamic_state'){
			var ov = getLimitValue(filter.datatype, filter.limit[0]);
			var result = 1;
			if(filter.limit[0].name == "nearlytime"){
				var ntv = ov.start;
				var ntgap = ov.startgap;
				var nttimestate = ov.timestate;
				var nts = strtotime("-"+ntv+" "+ntgap+"");
				var nte = strtotime("now");
				var ovfinalret = getStateTime(nts, nte, "now", "", nttimestate);
				$(limit).each(function(li, litem){
					var nv = getLimitValue(filter.datatype, litem);
					var ntv = nv.start;
					var ntgap = nv.startgap;
					var nttimestate = nv.timestate;
					var nts = strtotime("-"+ntv+" "+ntgap+"");
					var nte = strtotime("now");
					var nvfinalret = getStateTime(nts, nte, "now", "", nttimestate);
					if(ovfinalret.end != null && (nvfinalret.end > ovfinalret.end || nvfinalret.minvfinalretalue > ovfinalret.end )){
						result = 0;
						return false;
					}
					else if(ovfinalret.start != null && (nvfinalret.start < ovfinalret.start ||  nvfinalret.maxvalue < ovfinalret.start)){
						result = 0;
						return false;
					}
				});
			}
			else if(filter.limit[0].name == "beforetime"){
				var btsv = ov.start;
				var btev = ov.end;
				var btegap = ov.endgap;

				var timestate = ov.timestate;
				var datestate = ov.datestate;
				var bts = strtotime("-"+btsv+" "+btegap+"");
				var bte = strtotime("-"+btev+" "+btegap+"");
				var ovfinalret = getStateTime(bts, bte, datestate, btegap, timestate);
				$(limit).each(function(li, litem){
					var nv = getLimitValue(filter.datatype, litem);
					var btsv = nv.start;
					var btev = nv.end;
					var btegap = nv.endgap;

					var timestate = nv.timestate;
					var datestate = nv.datestate;
					var bts = strtotime("-"+btsv+" "+btegap+"");
					var bte = strtotime("-"+btev+" "+btegap+"");
					var nvfinalret = getStateTime(bts, bte, datestate, btegap, timestate);
					if(ovfinalret.end != null && (nvfinalret.end > ovfinalret.end || nvfinalret.minvfinalretalue > ovfinalret.end )){
						result = 0;
						return false;
					}
					else if(ovfinalret.start != null && (nvfinalret.start < ovfinalret.start ||  nvfinalret.maxvalue < ovfinalret.start)){
						result = 0;
						return false;
					}
				});
			}
		}
		else if(filter.limit[0].type == 'gaprange'){
			var ov = getLimitValue(filter.datatype,filter.limit[0]);
			var result = 1;
			$(limit).each(function(i,item){
				var nv = getLimitValue(filter.datatype,item);
				var ovmax;
				var ovmin;
				var nvmax;
				var nvmin;
				if(ov["maxvalue"] != null){
					if(ov["gap"] == "year"){
						ovmax = ov["maxvalue"] * 365;
					}
					else if(ov["gap"] == "month"){
						ovmax = ov["maxvalue"] * 365/12;
					}
				}
				if(ov["minvalue"] != null){
					if(ov["gap"] == "year"){
						ovmin = ov["minvalue"] * 365;
					}
					else if(ov["gap"] == "month"){
						ovmin = ov["minvalue"] * 365/12;
					}
				}

				if(nv["gap"] == "year"){
					nvmax = nv["maxvalue"] * 365;
					nvmin = nv["minvalue"] * 365;
				}
				else if(nv["gap"] == "month"){
					nvmax = nv["maxvalue"] * 365/12;
					nvmin = nv["minvalue"] * 365/12;
				}

				if(ovmax != null && (nvmax > ovmax || nvmin > ovmax)){
					result = 0;
					return false;
				}
				else if(ovmin != null && (nvmin < ovmin ||  nvmax < ovmin)){
					result = 0;
					return false;
				}
			});
			return result;
		}
		else{
			//生成正则
			var exactReg = [];
			//var inexactReg = [];
			//根据计费中的limit生成正则
			$(filter.limit).each(function(i,item){
				var value = getLimitValue(filter.datatype,item);
				if(value!=undefined && value!=null ){
					/*if(item.type == "exact"){
						exactReg.push({ repeat:item.repeat, reg:"^"+escapeRe(value)+"$" });
					}*/
					if(item.type == "inexact"){
						var re = new RegExp("\\*","g"); 
						value = escapeRe(value).replace(re,".*");
						//exactReg.push({ repeat:item.repeat, reg:"^"+escapeRe(value)+"$" });
					}
					exactReg.push({ repeat:item.repeat, reg:"^"+(value)+"$" });
				}
			});
			if(exactReg.length > 0){
				for(var i = limit.length -1; i>-1;i--){
					$(exactReg).each(function(j,item){
						var regobj = new RegExp(item.reg);
						var v = getLimitValue(filter.datatype, limit[i]);
						if(v!=undefined && v!=null && (item.repeat >= limit[i].repeat || item.repeat==-1)){
							if(regobj.test(v)){
								if(item.repeat!=-1){
									item.repeat -= limit[i].repeat;
								}
								limit.splice(i,1);
								return false;//退出子循环
							}
						}
					});
				}
				/*for(var i = limit.length -1; i>-1;i--){
					var reg = "^["+exactReg.toString()+"]{1}$";
					var regobj = new RegExp(reg);
					var v = getLimitValue(filter.datatype,limit[i]);
					if(v){
						if(regobj.test(v)){
							limit.splice(i,1);
						}
					}
				}*/
			}
			/*if(inexactReg.length > 0){
				for(var i = limit.length -1; i>-1;i--){
					$(inexactReg).each(function(i,item){
						var regobj = new RegExp(item.reg);
						var v = getLimitValue(filter.datatype,limit[i]);
						if(v && item.repeat > 0){
							if(regobj.test(v)){
								item.repeat--;
								limit.splice(i,1);
								return false;//退出子循环
							}
						}
					});
				}
			}*/
			return limit.length == 0 ? 1 : 0;
		}
	}
	else{
		return 1;
	}

}

function createAccountConfigObject(datajson,target){
	switch(parseInt(datajson.modelid,10)){
		case 1:
			return new FoltopConfigPartAccountConfig(datajson,target);
		case 2:
			return new UserConfigPartAccountConfig(datajson,target);
		case 6:
			return new VirtualdataConfigPartAccountConfig(datajson,target);
		case 31:
			return new TopicConfigPartAccountConfig(datajson,target);
		case 51:
			return new WeiboConfigPartAccountConfig(datajson,target);
		default:
			return null;
	}
}

function createAuthConfigObject(accountjson,datajson,target){
	switch(parseInt(accountjson.modelid,10)){
		case 1:
			return new FoltopConfigPartAuthConfig(accountjson,datajson,target);
		case 2:
			return new UserConfigPartAuthConfig(accountjson,datajson,target);
		case 6:
			return new VirtualdataConfigPartAuthConfig(accountjson,datajson,target);
		case 31:
			return new TopicConfigPartAuthConfig(accountjson,datajson,target);
		case 51:
			return new WeiboConfigPartAuthConfig(accountjson,datajson,target);
		default:
			return null;
	}
}
//在preElement(jquery对象 或 DOM)后面插入一个控件
//eleid 新增控件的ID
//limit 限制可选的值
function addAllowControlElement(preElement, eleid, selectedValue,limit){
	var optstr = "";
	$(["允许修改","禁止修改","禁止查看"]).each(function(i,item){
		if(limit != undefined && (i-1) < parseInt(limit,10)){
			return true;
		}
		var selstr = selectedValue == (i-1) ? "selected" : "";
		optstr += "<option value='"+(i-1)+"' "+selstr+">"+item+"</option>";
	});
	var htm = "<select id='"+eleid+"'>"+optstr+"</select>";
	if(preElement != undefined && preElement != null){
		$(preElement).after(htm);
	}
}

/**
 * 创建下载字段 html控件
 * @param all 所有
 * @param sel 选中的
 * @param name  checkbox名称前缀
 * @param parentid 父控件ID
 * @param disabled 是否允许修改
 */
function createDownloadFieldsHtml(all,sel, name, parentid, disabled){
	var dfhtml = "";
	for(var dfi=0; dfi<all.length; dfi++){
		var text = all[dfi].text;
		var value= all[dfi].value;
		if(disabled){
			dfhtml += '<label>'+text+'</label>&nbsp;';
		}
		else{
			dfhtml += '<input type="checkbox" name="'+name+'" id="'+name+value+'" value="'+value+'" text="'+text+'" /><label for="'+name+value+'">'+text+'</label>&nbsp;';
		}
	}
	$("#"+parentid).html(dfhtml);
	if(!disabled){
		for(var seldfi=0; seldfi<sel.length; seldfi++){
			$("#"+name+sel[seldfi].value).attr("checked",true);
		}
	}
}
function initUpdateSnapshotLimit(paramsjson){
	//获取租户快照更新权限
	var searchnameUrl = config.modelUrl+"tenant_user_model.php?type=gettenantbyid&tid="+tid;
	var tenantupdatesnapshot = false;
	ajaxCommon(searchnameUrl, function(data){
		if(data!=null){
			tenantupdatesnapshot = data.children[0].allowupdatesnapshot;
		}
	}, "json");

	//是否允许快照更新
	var allowupdatesnapshot = false;
	if(parseInt(tenantupdatesnapshot, 10)){ //租户允许快照更新
		allowupdatesnapshot = paramsjson.allowupdatesnapshot == true;
		$("#updatesnapshot_allow1").show();
		$("label[for=updatesnapshot_allow1]").show();
		$("label[for=updatesnapshot_allow0]").text("禁止");
	}
	else{
		$("#updatesnapshot_allow1").hide();
		$("label[for=updatesnapshot_allow1]").hide();
		$("label[for=updatesnapshot_allow0]").text("该租户禁止");
	}

	if(allowupdatesnapshot){
		$("input[name=updatesnapshot_allow][value=1]").attr("checked",true);
		$("div[name=updatesnapshot_div]").show();
	}
	else{
		$("input[name=updatesnapshot_allow][value=0]").attr("checked",true);
		$("div[name=updatesnapshot_div]").hide();
	}
}
function getUpdateSnapshotLimit(datajson){
	datajson.allowupdatesnapshot = $("#updatesnapshot_allow1").prop("checked");
	return true;
}
function initAuthUpdateSnapshotLimit(accountjson, authjson){
	if(!accountjson.allowupdatesnapshot){
		$("#div_updatesnapshot_allow").hide();
		$("div[name=updatesnapshot_div]").hide();
	}
	else{
		$("#div_updatesnapshot_allow").show();
		if(authjson.allowupdatesnapshot){
			$("input[name=updatesnapshot_allow][value=1]").attr("checked",true);
			$("div[name=updatesnapshot_div]").show();
		}
		else{
			$("input[name=updatesnapshot_allow][value=0]").attr("checked",true);
			$("div[name=updatesnapshot_div]").hide();
		}
	}
}
function getAuthUpdateSnapshotLimit(accountjson, authjson){
	if(accountjson.allowupdatesnapshot){
		authjson.allowupdatesnapshot = $("#updatesnapshot_allow1").prop("checked");
	}
	return true;
}
function initEventAlertLimit(paramsjson){
	//获取租户事件预警权限
	var searchnameUrl = config.modelUrl+"tenant_user_model.php?type=gettenantbyid&tid="+tid;
	var tenanteventalert = false;
	ajaxCommon(searchnameUrl, function(data){
		if(data!=null){
			tenanteventalert = data.children[0].alloweventalert;
		}
	}, "json");

	//是否允许事件预警
	var alloweventalert = false;
	if(parseInt(tenanteventalert, 10)){ //租户允许事件预警
		alloweventalert = paramsjson.alloweventalert == true;
		$("#eventalert_allow1").show();
		$("label[for=eventalert_allow1]").show();
		$("label[for=eventalert_allow0]").text("禁止");
	}
	else{
		$("#eventalert_allow1").hide();
		$("label[for=eventalert_allow1]").hide();
		$("label[for=eventalert_allow0]").text("该租户禁止");
	}

	if(alloweventalert){
		$("input[name=eventalert_allow][value=1]").attr("checked",true);
		$("div[name=eventalert_div]").show();
	}
	else{
		$("input[name=eventalert_allow][value=0]").attr("checked",true);
		$("div[name=eventalert_div]").hide();
	}
}
function getEventAlertLimit(datajson){
	datajson.alloweventalert = $("#eventalert_allow1").prop("checked");
	return true;
}
function initAuthEventAlertLimit(accountjson, authjson){
	if(!accountjson.alloweventalert){
		$("#div_eventalert_allow").hide();
		$("div[name=eventalert_div]").hide();
	}
	else{
		$("#div_eventalert_allow").show();
		if(authjson.alloweventalert){
			$("input[name=eventalert_allow][value=1]").attr("checked",true);
			$("div[name=eventalert_div]").show();
		}
		else{
			$("input[name=eventalert_allow][value=0]").attr("checked",true);
			$("div[name=eventalert_div]").hide();
		}
	}
}
function getAuthEventAlertLimit(accountjson, authjson){
	if(accountjson.alloweventalert){
		authjson.alloweventalert = $("#eventalert_allow1").prop("checked");
	}
	return true;
}
/**
 * 初始化下载计费。计费模块的config页面用
 * @param paramsjson
 */
function initDownloadLimit(paramsjson){
	//初始化选中
	$("input[name=download_allow]").bind("click",function(){
		if($("#download_allow0").prop("checked")){
			$("div[name=download_div]").hide();//隐藏相关DIV
		}
		else{
			$("div[name=download_div]").show();
		}
	});
	//获取租户下载权限
	var searchnameUrl = config.modelUrl+"tenant_user_model.php?type=gettenantbyid&tid="+tid;
	var tenantdownload = false;
	ajaxCommon(searchnameUrl, function(data){
		if(data!=null){
			tenantdownload = data.children[0].allowdownload;
		}
	}, "json");

	//是否允许下载
	var allowdownload = false;
	if(parseInt(tenantdownload, 10)){ //租户允许下载
		allowdownload = paramsjson.allowDownload == true;
		$("#download_allow1").show();
		$("label[for=download_allow1]").show();
		$("label[for=download_allow0]").text("禁止");
	}
	else{
		$("#download_allow1").hide();
		$("label[for=download_allow1]").hide();
		$("label[for=download_allow0]").text("该租户禁止");
	}

	if(allowdownload){
		$("input[name=download_allow][value=1]").attr("checked",true);
		$("div[name=download_div]").show();
	}
	else{
		$("input[name=download_allow][value=0]").attr("checked",true);
		$("div[name=download_div]").hide();
	}
	$.ajax({
        url: config.modelUrl+"resource_model.php?type=getlatestjson&modelid="+paramsjson.modelid,
        dataType: "json",
        cache: false, //默认为true， false时不会从浏览器缓存中加载请求信息
        success:function(r){
            if(r && r.data && r.data.datajson.download_FieldLimit){
            	var dfs = r.data.datajson.download_FieldLimit;
            	createDownloadFieldsHtml(dfs, paramsjson.download_FieldLimit, "download_fields", "selecteddownloadfieldlimit");
            }    
        } 
    });
	//下载数据条数限制
	if(paramsjson.download_DataLimit != undefined && paramsjson.download_DataLimit != null){
		$("#download_datalimit").val(paramsjson.download_DataLimit);
	}
	else{
		$("#download_datalimit").val(0);
	}
	var downloaddatalc = paramsjson.download_DataLimit_limitcontrol ? paramsjson.download_DataLimit_limitcontrol : -1;
	$("#limitcontrol_downloaddatalimit").val(downloaddatalc);
	var nolimitcontrol = downloaddatalc == -1;
	$("#nolimitcontrol_downloaddatalimit").bind("click",function(){
		if($(this).prop("checked")){
			$("#limitcontrol_downloaddatalimit").attr("disabled","disabled");
		}
		else{
			$("#limitcontrol_downloaddatalimit").removeAttr("disabled");
		}
	});
	if(nolimitcontrol){
		$("#limitcontrol_downloaddatalimit").attr("disabled","disabled");
	}
	else{
		$("#limitcontrol_downloaddatalimit").removeAttr("disabled");
	}
	$("#nolimitcontrol_downloaddatalimit").attr("checked",nolimitcontrol);
	downloaddatalc = paramsjson.download_FieldLimit_limitcontrol ? paramsjson.download_FieldLimit_limitcontrol : -1;
	$("#limitcontrol_downloadfieldlimit").val(downloaddatalc);
	nolimitcontrol = downloaddatalc == -1;
	$("#nolimitcontrol_downloadfieldlimit").bind("click",function(){
		if($(this).prop("checked")){
			$("#limitcontrol_downloadfieldlimit").attr("disabled","disabled");
		}
		else{
			$("#limitcontrol_downloadfieldlimit").removeAttr("disabled");
		}
	});
	if(nolimitcontrol){
		$("#limitcontrol_downloadfieldlimit").attr("disabled","disabled");
	}
	else{
		$("#limitcontrol_downloadfieldlimit").removeAttr("disabled");
	}
	$("#nolimitcontrol_downloadfieldlimit").attr("checked",nolimitcontrol);
}

/**
 * 获取"下载"计费信息，计费模块用
 * @param datajson
 */
function getDownloadLimit(datajson){
	datajson.allowDownload = $("#download_allow1").prop("checked");
	if(datajson.allowDownload){
		var dl = $("#nolimitcontrol_downloaddatalimit").prop("checked") ? -1 : $("#limitcontrol_downloaddatalimit").val();
		if(isNaN(dl)){
			dl = 0;
		}
		datajson.download_DataLimit_limitcontrol = parseInt(dl,10);
		datajson.download_DataLimit = $("#download_datalimit").val();
		if(isNaN(datajson.download_DataLimit)){
			alert("下载数据量格式有误，请填写数字");
			$("#download_datalimit").focus();
			return false;
		}
		else{
			datajson.download_DataLimit = parseInt(datajson.download_DataLimit, 10);
		}
		dl = $("#nolimitcontrol_downloadfieldlimit").prop("checked") ? -1 : $("#limitcontrol_downloadfieldlimit").val();
		if(isNaN(dl)){
			dl = 0;
		}
		datajson.download_FieldLimit_limitcontrol = dl;
		datajson.download_FieldLimit = [];
		$("input[name=download_fields]").each(function(i,item){
			if($(item).prop("checked")){
				datajson.download_FieldLimit.push({text:$(item).attr("text"), value:$(item).attr("value")});
			}
		});
	}
	else{
		datajson.download_FieldLimit = [];
	}
	return true;
}

/**
 * 初始化权限模块的 下载权限
 * @param accountjson
 * @param authjson
 */
function initAuthDownloadLimit(accountjson, authjson){
	if(!accountjson.allowDownload){
		$("#div_download_allow").hide();
		$("div[name=download_div]").hide();
	}
	else{
		$("#div_download_allow").show();
		if(authjson.allowDownload){
			$("input[name=download_allow][value=1]").attr("checked",true);
			$("div[name=download_div]").show();
		}
		else{
			$("input[name=download_allow][value=0]").attr("checked",true);
			$("div[name=download_div]").hide();
		}
	}
	//初始化选中
	$("input[name=download_allow]").bind("click",function(){
		if($("#download_allow0").prop("checked")){
			$("div[name=download_div]").hide();//隐藏相关DIV
		}
		else{
			$("div[name=download_div]").show();
		}
	});
	createDownloadFieldsHtml(accountjson.download_FieldLimit, authjson.download_FieldLimit, "download_fields", 
			"selecteddownloadfieldlimit", (accountjson.download_FieldLimit_limitcontrol == 0));
	//下载数据条数限制
	if(authjson.download_DataLimit != undefined && authjson.download_DataLimit != null){
		$("#download_datalimit").val(authjson.download_DataLimit);
	}
	else if(accountjson.download_DataLimit != undefined && accountjson.download_DataLimit != null){
		$("#download_datalimit").val(accountjson.download_DataLimit);//如果权限中没有配置，赋值为计费json的值
	}
	else{
		$("#download_datalimit").val(0);
	}
	var maxdl = accountjson.download_DataLimit ? accountjson.download_DataLimit : 0;
	if(maxdl > 0){
		$("#download_datalimit_tip").text("最大"+maxdl.toString());
	}
	if(accountjson.download_DataLimit_limitcontrol  == 0){//修改次数用完
		$("#download_datalimit").attr("disabled","disabled");
	}
}

function getAuthDownloadLimit(accountjson, authjson){
	if(accountjson.allowDownload){
		authjson.allowDownload = $("#download_allow1").prop("checked");
		if(authjson.allowDownload){
			if(accountjson.download_DataLimit_limitcontrol != 0 ){//允许修改时
				var downlimit = $("#download_datalimit").val();
				if(isNaN(downlimit)){
					alert("下载数据量格式有误，请填写数字");
					$("#download_datalimit").focus();
					return false;
				}
				var maxdl = accountjson.download_DataLimit ? accountjson.download_DataLimit : -1;
				downlimit = parseInt(downlimit, 10);
				if(downlimit < 0 || (maxdl > -1 && downlimit > maxdl)){
					alert("下载数据量值有误，请输入0~"+maxdl+"之间的数字");
					$("#download_datalimit").focus();
					return false;
				}
				authjson.download_DataLimit = downlimit;
			}
			if(accountjson.download_FieldLimit_limitcontrol != 0){
				authjson.download_FieldLimit = [];
				$("input[name=download_fields]").each(function(i,item){
					if($(item).prop("checked")){
						var isinacc = false;
						for(var j=0; j<accountjson.download_FieldLimit.length; j++){
							if(accountjson.download_FieldLimit[j].value == $(item).attr("value")){
								isinacc = true;
								break;
							}
						}
						if(isinacc){//验证选择的值是否在计费权限中
							authjson.download_FieldLimit.push({text:$(item).attr("text"), value:$(item).attr("value")});
						}
					}
				});
			}
		}
		else{
			authjson.download_FieldLimit = [];
		}
	}
	else{
		authjson.download_FieldLimit = [];
	}
	return true;
}
