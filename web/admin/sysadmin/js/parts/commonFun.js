function errorCallBack(response, error){
	trace(response);	
	trace(error);	
}
function timeDisplayHtml(prefix){
	var facet_range_prefix = ""+prefix+"_facet_range";
	var facet_created_at_html = "";
	facet_created_at_html += '<span class="rightalign"></span><span id="'+facet_range_prefix+'_timeselect_start">全部时间</span><span id="'+facet_range_prefix+'_splitmark" style="display:none;"> ~ </span><span id="'+facet_range_prefix+'_timeselect_end"></span>';
	facet_created_at_html += createRangeOnsiteFieldHtml(facet_range_prefix, true);
	$("#"+prefix+"_facet_range_html").empty().append(facet_created_at_html);
	//为时间控件绑定事件
	$("#"+facet_range_prefix+"_createdtime_start, #"+facet_range_prefix+"_createdtime_end").die("click");
	$("#"+facet_range_prefix+"_createdtime_start, #"+facet_range_prefix+"_createdtime_end").live("click", function() {
		WdatePicker({onpicked:function(){
			var cs = $("#"+facet_range_prefix+"_createdtime_start").val();
			var ce = $("#"+facet_range_prefix+"_createdtime_end").val();
			if(cs.length != 0 || ce.length != 0){
				$("input[target=createdtime]").attr("checked", false);

				$("#"+facet_range_prefix+"_timeselect_start").text(cs);
				$("#"+facet_range_prefix+"_timeselect_end").text(ce);
				isShowSplitmark("#"+facet_range_prefix+"_timeselect_start", "#"+facet_range_prefix+"_timeselect_end", "#"+facet_range_prefix+"_splitmark", true);
				$("#"+facet_range_prefix+"_createdtime_all").attr("checked", false);
			}
		},oncleared:function(){
			if($("#"+facet_range_prefix+"_createdtime_start").val().length == 0 && $("#"+facet_range_prefix+"_createdtime_end").val().length == 0){
				$("#"+facet_range_prefix+"_timeselect_start").text("全部时间");
				$("#"+facet_range_prefix+"_timeselect_end").text("");
				isShowSplitmark("#"+facet_range_prefix+"_timeselect_start", "#"+facet_range_prefix+"_timeselect_end", "#"+facet_range_prefix+"_splitmark");
				$("#"+facet_range_prefix+"_createdtime_all").attr("checked", true);
			}
			else{
				if($("input[name="+facet_range_prefix+"_createdtime_start]").val().length == 0){
					$("#"+facet_range_prefix+"_timeselect_start").text("");
				}
				if($("input[name="+facet_range_prefix+"_createdtime_end]").val().length == 0){
					$("#"+facet_range_prefix+"_timeselect_end").text("");
				}
			}
		},dateFmt:'yyyy-MM-dd HH:mm:ss'});
	});
	//为控件绑定颜色改变事件
	$("#"+prefix+"_facet_range_html div[timetype] input, #"+prefix+"_facet_range_html div[timetype] select").unbind("click");
	$("#"+prefix+"_facet_range_html div[timetype] input, #"+prefix+"_facet_range_html div[timetype] select").bind("click", function(){
		var tt = $(this).parent().attr("timetype");
		$("#"+prefix+"_facet_range_html").attr("checkedtimetype", tt); //最后一次选择的时间控件
		//_this.changeBorderColor(tt); 
		changeTimeBorderColor(prefix+"_facet_range_html", tt); 
	});
	//快捷按钮点击事件
	$("#"+prefix+"_facet_range_createdtime_all").bind("click", function(){
		if($(this).prop("checked")){
			$(this).attr("checked", true);
			setTimeSelectMsg(""+prefix+"_facet_range", "全部时间", ""); 
		}
		else{
			$(this).attr("checked", false);
			var cs = $("#"+prefix+"_facet_range_createdtime_start").val();
			var ce = $("#"+prefix+"_facet_range_createdtime_end").val();
			if(cs == "" && ce == ""){
				setTimeSelectMsg(""+prefix+"_facet_range", "请填写时间"); 
			}
			else if(cs == "" || cs == undefined){
				setTimeSelectMsg(""+prefix+"_facet_range", "", ce); 
			}
			else if(ce == "" || ce == undefined){
				setTimeSelectMsg(""+prefix+"_facet_range", cs, ""); 
			}
		}
	});
	//时间标签 当前时间和开始时间切换 并给 总标题赋值
	$("#"+prefix+"_facet_range_nearlytime_thistime").bind("click", function(){
		var timestate = $(this).attr("timestate");
		nearlytimeTagClick(""+prefix+"_facet_range", timestate);
	});

	//相对今天,快捷按钮点击事件
	$("#"+prefix+"_facet_range_nearlytime_div input[nearly]").bind("click", function(){
		var nearlytype = $(this).attr("nearly");
		nearlytimeShortCutBtnClick(""+prefix+"_facet_range", nearlytype);

	});
	$("#"+prefix+"_facet_range_nearlytime, #"+prefix+"_facet_range_nearlytime_gap").bind("change", function(){
		nearlytimeInputChange(""+prefix+"_facet_range");
	});
	//beforetime 控件 当前时间,开始时间标签点击事件
	$("#"+prefix+"_facet_range_beforetime_thistime").bind("click", function(){
		var timestate = $(this).attr("timestate");
		beforetimeTagTimeClick(""+prefix+"_facet_range", timestate);
	});
	$("#"+prefix+"_facet_range_beforetime_thisdate").bind("click", function(){
		var datestate = $(this).attr("datestate");
		beforetimeTagDateClick(""+prefix+"_facet_range", datestate);
	});
	//时间段 改变
	$("#"+prefix+"_facet_range_beforetime_start, #"+prefix+"_facet_range_beforetime_end, #"+prefix+"_facet_range_beforetime_end_gap").bind("change", function(){
		beforetimeInputChange(""+prefix+"_facet_range");

	});

	$("#"+prefix+"_facet_range_untiltime_div input[untiltime]").bind("click", function(){
		var type = $(this).attr("untiltime");
		var until= $(this).attr("until"+type+"");
		untiltimeShortCutBtnClick(""+prefix+"_facet_range", until, type);
	});
	//时间控件,下拉日期单位选择
	$.each(["start", "end"], function(k, item){
		//gap change
		$("#"+prefix+"_facet_range_untiltime_"+item+"_gap").bind("change", function(){
			var usgval = $(this).val();
			untiltimeGapChange(""+prefix+"_facet_range", usgval, item);
			untiltimeValueChange(""+prefix+"_facet_range");
			//_this.setUntilTimeChangeValue();
		});
		//this_gap change
		$("#"+prefix+"_facet_range_untiltime_"+item+"_this_gap").bind("change", function(){
			var ustgval = $(this).val();
			var unitArr = dateUnitDisplay(ustgval, "down");
			var opthtml = timeSelectOption(unitArr);
			$("#"+prefix+"_facet_range_untiltime_"+item+"_to_gap").empty().append(opthtml);
			//_this.setUntilTimeChangeValue();
			untiltimeValueChange(""+prefix+"_facet_range");
		});
		//to_gap change
		$("#"+prefix+"_facet_range_untiltime_"+item+"_to_gap").bind("change", function(){
			//_this.setUntilTimeChangeValue();
			untiltimeValueChange(""+prefix+"_facet_range");
		});
		//start change
		$("#"+prefix+"_facet_range_untiltime_"+item+"").bind("change", function(){
			untiltimeInputChange(""+prefix+"_facet_range", item);
			//_this.setUntilTimeChangeValue();
			untiltimeValueChange(""+prefix+"_facet_range");
		});
		//start to change
		$("#"+prefix+"_facet_range_untiltime_"+item+"_to").bind("change", function(){
			//_this.setUntilTimeChangeValue();
			untiltimeValueChange(""+prefix+"_facet_range");
		});
	});

	var measurehtml = "<option value='month'>月</option><option value='day'>天</option><option value='hour'>小时</option>";
	$("#"+prefix+"_facet_gap_measure").empty().append(measurehtml);
}
function createRangeOnsiteFieldHtml(prefix, pretitle){
	var html = "";
	//相对今天
	var nearlytimespan = "";
	if(pretitle){
		nearlytimespan = '<span class="rightalign">相对今天：</span>';
	}
	html += '<div style="margin:7px;" id="'+prefix+'_nearlytime_div" timetype="nearlytime">'+nearlytimespan+'<input type="radio" name="'+prefix+'_nearlytime_shortcut" id="'+prefix+'_nearlytime_day" class="outborder" nearly="1day" /><label for="'+prefix+'_nearlytime_day">近一天</label>&nbsp;&nbsp;<input type="radio" name="'+prefix+'_nearlytime_shortcut" id="'+prefix+'_nearlytime_week" class="outborder" nearly="1week" /><label for="'+prefix+'_nearlytime_week">近一周</label>&nbsp;&nbsp;<input type="radio" name="'+prefix+'_nearlytime_shortcut" id="'+prefix+'_nearlytime_month" class="outborder" nearly="1month" /><label for="'+prefix+'_nearlytime_month">近一月</label>&nbsp;&nbsp;<input type="radio" name="'+prefix+'_nearlytime_shortcut" id="'+prefix+'_nearlytime_year" class="outborder" nearly="1year" /><label for="'+prefix+'_nearlytime_year">近一年</label>&nbsp;&nbsp;近<input type="text" class="smallinput"  style="border:1px solid #ccc;" id="'+prefix+'_nearlytime" name="'+prefix+'_nearlytime" /><select id="'+prefix+'_nearlytime_gap">'+timeSelectOption()+'</select>&nbsp;&nbsp;<a id="'+prefix+'_nearlytime_thistime" timestate="now" style="display:none;"></a></div>';
	//时间段
	var beforetimespan = "";
	if(pretitle){
		beforetimespan = '<span class="rightalign">时间段：</span>';
	}
	html += '<div style="margin:7px;" id="'+prefix+'_beforetime_div" timetype="beforetime">'+beforetimespan+' 前<input type="text" value=""  id="'+prefix+'_beforetime_start" name="'+prefix+'_beforetime_start" class="smallinput" style="border:1px solid #ccc;"/>到前<input type="text" value="" class="smallinput" style="border:1px solid #ccc;" id="'+prefix+'_beforetime_end" name="'+prefix+'_beforetime_end" /><select id="'+prefix+'_beforetime_end_gap">'+timeSelectOption()+'</select>&nbsp;&nbsp; <a style="display:none;" id="'+prefix+'_beforetime_thisdate" datestate="now"></a> &nbsp;&nbsp; <a style="display:none;" id="'+prefix+'_beforetime_thistime" timestate="now"></a></div>';
	//绝对时间
	var createdtimespan = "";
	if(pretitle){
		createdtimespan = '<span class="rightalign">绝对时间：</span>';
	}
	html += "<div style='margin:7px;' id='"+prefix+"_createdtime_div' timetype='createdtime'>"+createdtimespan+"<input type='text' id='"+prefix+"_createdtime_start'  name='"+prefix+"_createdtime_start' style='border:1px solid #ccc;'> 至 <input type='text' id='"+prefix+"_createdtime_end' name='"+prefix+"_createdtime_end' style='border:1px solid #ccc;'>&nbsp;&nbsp;<input type='checkbox' name='"+prefix+"_createdtime_all' id='"+prefix+"_createdtime_all' /><label for='"+prefix+"_createdtime_all'>全部</label></div>";
	//日历时间段
	var untiltimespan = "";
	html += '<div style="margin:7px;" id="'+prefix+'_untiltime_div" timetype="untiltime">';
	$.each(["start", "end"], function(k,v){
		if(pretitle && v == "start"){
			untiltimespan = '<span class="rightalign">日历时间段：</span>';
		}
		else if(pretitle && v == "end"){
			untiltimespan = '<span class="rightalign"></span>';
		}
		html+= ''+untiltimespan+'<input type="radio" name="'+prefix+'_untiltime_'+v+'_shortcut" id="'+prefix+'_untiltime_'+v+'_shortcut1" class="outborder" untiltime="'+v+'" until'+v+'="1day" /><label for="'+prefix+'_untiltime_'+v+'_shortcut1">昨天</label>&nbsp;&nbsp;<input type="radio" name="'+prefix+'_untiltime_'+v+'_shortcut" id="'+prefix+'_untiltime_'+v+'_shortcut2" class="outborder" untiltime="'+v+'" until'+v+'="1week" /><label for="'+prefix+'_untiltime_'+v+'_shortcut2">上周</label>&nbsp;&nbsp;<input type="radio" name="'+prefix+'_untiltime_'+v+'_shortcut" id="'+prefix+'_untiltime_'+v+'_shortcut3" class="outborder" untiltime="'+v+'" until'+v+'="1month" /><label for="'+prefix+'_untiltime_'+v+'_shortcut3">上个月</label>&nbsp;&nbsp;';
		html+='至今<input type="text" value="" class="smallinput" style="border:1px solid #ccc;" id="'+prefix+'_untiltime_'+v+'" name="'+prefix+'_untiltime_'+v+'" untiltime="'+v+'" /><select id="'+prefix+'_untiltime_'+v+'_gap" untiltime="'+v+'" >'+timeSelectOption()+'</select>该<select id="'+prefix+'_untiltime_'+v+'_this_gap">'+timeSelectOption()+'</select>的第<input type="text" value="" class="smallinput" style="border:1px solid #ccc;" id="'+prefix+'_untiltime_'+v+'_to" name="'+prefix+'_untiltime_'+v+'_to"  untiltime="'+v+'" /><select id="'+prefix+'_untiltime_'+v+'_to_gap"  untiltime="'+v+'" >'+timeSelectOption()+'</select><br/>';
	});
	html += '</div>';
	return html;
}
/**
 * 从模型的filtervalue数组 中获取指定名称的 filtervalue
 * @param filtername
 * @param filtervalueArr 
 * @returns filtervalue的数组
 */
function getFilterValue(filtername,filtervalueArr){
	var returnval = [];
	$.each(filtervalueArr,function(i,item){
		if(item.fieldname == filtername){
			returnval.push(item);
		}
	});
	return returnval;
}
/**
 * 生成模型的filtervalue对象
 * @param filtername
 * @param datatype 
 * @param value
 * @returns filtervalue对象
 */
function createFiltervalue(fieldname, datatype, value){
        var filtervalue = {};
        filtervalue.fieldname = fieldname;
        var fieldvalue = {};
        fieldvalue.datatype = datatype;
        fieldvalue.value = value;
        filtervalue.fieldvalue = fieldvalue;
        return filtervalue;
}
/*
 * 创建span标签
 * item 值标签span的class前缀 默认和fieldname相同
 * name 对应值的name string类型对应fieldvalue中的 value, value_text_object类型的存 text
 * code 对应值的code string类型不存在, 或 与name相同, value_text_object类型存 value
 * isnot 非range类型字段可以选择不包含某值, isnot 为 true时 添加"不"选择按钮 绑定点击事件
 * exclude isnot 为true时 为exclude赋值 是否排除
 * isfeature 特征分类 ,当选择特征分类时添加此属性
 * emotype 存情感值 可能不存在
 * marktype 微博标识和原创微博, 标记添加 的是id还是url
 * blurtype 兼容旧版本的 blur_value_objcet类型
 * canclename 删除按钮<a>标签的名称
 * */
function createFormConfigSpanHtml(item, name, code, isnot, exclude, isfeature, emotype, marktype, blurtype, canclename){
	//情感
	var emocode = "";
	if(emotype != undefined && emotype != ""){
		emocode = "emotype='"+emotype+"'";
	}
	//特征分类
	var fea = "";
	if(isfeature != undefined && isfeature != ""){
		fea = "isfeature='"+isfeature+"'";
	}
	var marktypeattr = "";
	if(marktype != undefined && marktype != ""){
		marktypeattr = "marktype='"+marktype+"'";
	}
	
	var blurtypeattr = "";
	if(blurtype != undefined && blurtype != ""){
		blurtypeattr = "blurtype='"+blurtype+"'";
	}
	var codeattr = "";
	if(code != undefined){
		code = commonFun.trim(code); 
		codeattr = "code='"+code+"'";
	}
	//默认值
	if(canclename == undefined || canclename == ""){
		canclename = "cancleselectedvaluetextobj";
	}
	//绑定删除事件
	$("a[name=cancleselectedvaluetextobj]").die("click");
	$("a[name=cancleselectedvaluetextobj]").live("click", function(){
		$(this).parent().remove();
	});

	name = commonFun.trim(name); //去除两端空格
	var spanhtml = "";
	spanhtml += "<span class='selwordsbox'>";
	var exattr = "";
	if(isnot){
		var exstyle = "";
		if(exclude != undefined && exclude != 0){
			exattr = "exclude='"+exclude+"'";
			exstyle = "style='color:#000000;'";
		}
		spanhtml += "<a class='excludeitem' onclick='excludeItemFun(this)' "+exstyle+" >不</a>";
	}
	spanhtml += "<span class='"+item+"item' "+codeattr+" "+exattr+" "+fea+" "+emocode+" "+marktypeattr+" "+blurtypeattr+" >"+name+"</span>";
	spanhtml += "<a name='"+canclename+"' value='"+item+"' class='cancleitem'>×</a>";
	spanhtml += "</span>";
	return spanhtml;
}
function checkSession(){
	$.ajax({url:config.phpPath+"checkuser.php",dataType:"json",type:"POST",data:{type:"existsession"},async:false,
	    success:function(d){
	        if(!d.result){
	            location.href = config.sitePath+"login.shtml";
	        }
	    },
	    error:function(){
	        location.href = config.sitePath+"login.shtml";
	    }
	});
}

function getSpiderAccountName(sourceid, accountid){
	var n = '';
	if(config.allSpiderAccount.length == 0){
		getSpiderAccount(function(data){
			if(data){
                config.allSpiderAccount = data;
			}
		});
	}
	$(config.allSpiderAccount).each(function(i,item){
		if(item.id == accountid && item.sourceid == sourceid ){
			n = item.username;
			return false;
		}
	});
	return n;
}
function getSpiderAccount(callback){
	$.ajax({url:config.phpPath+"taskmanager.php",async:false,data:{type:"getspideraccount"}, type:"post", dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	}});
}
//根据主机id获取别名
function getHostName(hostid){
	if(!hostid){
		return "默认主机";
	}
	var n = '';
	if(config.allDataHost.length == 0){
		getDataHost(function(data){
			if(data){
                config.allDataHost = data;
			}
		});
	}
	$(config.allDataHost).each(function(i,item){
		if(item.id == hostid){
			n = item.alias;
			return false;
		}
	});
	return n;
}
function getDataHost(callback){
	$.ajax({url:config.phpPath+"taskmanager.php",async:false,data:{type:"getdatahost"}, type:"post", dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	}});
}
function getTplOutfield(callback){
	var tploutfieldurl = config.phpPath+"spiderconfig_model.php";
	$.ajax({url:tploutfieldurl,async:false,data:{type:"selecttploutfield"}, type:"get", dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	}});
}
function getAccountSource(callback){
	$.ajax({
		url:config.phpPath+"taskmanager.php",
		async:false,
		data:{type:"getaccountsource"}, 
		type:"get", 
		dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		}
	});
}

//根据配置id获取名称
function getSpiderConfigName(configid){
	var n = '';
	if(config.allSpiderConfig.length == 0){
		getSpiderConfig(function(data){
			if(data){
                config.allSpiderConfig= data;
			}
		});
	}
	$(config.allSpiderConfig).each(function(i,item){
		if(item.id == configid){
			n = item.name;
			return false;
		}
	});
	return n;
}
/*
function getSpiderConfig(callback){
	var getspiderconfigurl = config.phpPath+"spiderconfig_model.php";
	$.ajax({url:getspiderconfigurl,async:false,data:{type:"selectspiderconfiginfo"}, type:"get", dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	}});
}
*/

//获取爬虫配置的id和名称
function getSpiderConfig(callback){
	$.ajax({url:config.phpPath+"taskmanager.php",async:false,data:{type:"getspiderconfig"}, type:"post", dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	}});
}
/*
//根据搜索引擎id获取名称
function getSearchEngineName(engineid){
	var n = '';
	if(config.allSearchEngine.length == 0){
		getSearchEngine(function(data){
			if(data){
                config.allSearchEngine = data;
			}
		});
	}
	$(config.allSearchEngine).each(function(i,item){
		if(item.id == engineid){
			n = item.name;
			return false;
		}
	});
	return n;
}

//获取搜索引擎的id和名称
function getSearchEngine(callback){
	$.ajax({
		url:config.phpPath+"taskmanager.php",
		async:false,
		data:{type:"getsearchenginelist"}, 
		type:"get", 
		dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		}
	});
}
//根据抓取站点id获取名称
function getSearchSiteName(siteid){
	var n = '';
	if(config.allSearchSite.length == 0){
		getSearchSite(function(data){
			if(data){
                config.allSearchSite = data;
			}
		});
	}
	$(config.allSearchSite).each(function(i,item){
		if(item.id == siteid){
			n = item.name;
			return false;
		}
	});
	return n;
}

//获取搜索引擎的id和名称
function getSearchSite(callback){
	$.ajax({
		url:config.phpPath+"taskmanager.php",
		async:false,
		data:{type:"getsearchsitelist"}, 
		type:"get", 
		dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		}
	});
}
*/
//获取用户类型的描述
function getUserTypeText(usertype){
	if(undefined == usertype || null == usertype ){
		return "全部";
	}
	else{
		return parseInt(usertype,10) == 1 ? "种子用户" : "普通用户";
	}
}
function _getintvalue(v){
	return (v == undefined || v == null) ? 0 : v;
}
function getTaskDetail(task){
	var v = task;
	if(v.taskparams.scene == undefined){
		v.taskparams.scene = {};
	}
	var infohtm = '';
	var ld = v.local == 1 ? "是":"否";
	infohtm += "本地："+ld+"<br/>";
	var rd = v.remote == 1 ? "是":"否";
	infohtm += "远程："+rd+"<br/>";
	var cdelay = v.conflictdelay == null ? "未设置" : v.conflictdelay+"秒";
	infohtm += "冲突延迟："+cdelay+"<br/>";
	infohtm += "<hr>";
//add by wang 2016.12.05  异常任务状态	

	var error_code = v.error_code;
	if (error_code!=null) {
		switch (error_code) {
			case 0:
				error_code = "正确没有错误";
				break;
			case 1:
				error_code = "提交任务超时";
				break;
			case 2:
				error_code = "执行的是不符合规则的url或者错误的url";
				break;
			case 3:
				error_code = "浏览器加载网页发生错误";
				break;
			case 4:
				error_code = "超过最大重试次数";
				break;
			case 5:
				error_code = "模板配置有问题";
				break;
			case 6:
				error_code = "向服务器提交数据异常";
				break;
			case 7:
				error_code = "js脚本发生错误";
				break;
			default:
				error_code = "其他错误类型";
				break;
		}

		infohtm += "<b>错误码:</b>" + v.error_code + "&nbsp&nbsp" + "<b>错误原因:</b>" + error_code + "<br/>";
	};
	var error_msg= v.error_msg;
		if (error_msg != null) {
			infohtm += "<b>错误详情:</b>" + "'" + error_msg + "'" + "<br/>";
		}


	/*
	//定时计划
	if(task.crontime != undefined){
		infohtm +="定时计划："+getCrontimeDes(v.crontime)+"<br/>";
	}
	*/
	var task_term = '';
	var exeinfo = "";
	var comitstr = "";
	if(v.taskparams.iscommit){
		comitstr += "立即更新"; 
	}
	else{
		comitstr += "完成时提交";
	}
	exeinfo +="处理方式：";
	if(v.taskparams.each_count){
		exeinfo +="每次提交"+v.taskparams.each_count+"条,"
	}
	exeinfo +=comitstr+"<br/>";
	//显示分词方案 用户配置则显示
	if(task.taskparams.dictionaryPlan != undefined){
		var data1=JSON.parse(task.taskparams.dictionaryPlan);
		var planHtml="";
		var count=1;
		$.each(data1,function(di, itemPlan){
			planHtml +="方案"+count+":(";
			var t2="";
			var count2=0;
			if(itemPlan!=undefined&&itemPlan.length==0){
				if(count2!=0)
					t2=t2+","+"默认方案";
				else
					t2="默认方案";
				count2++;
			}
			else{
				$.each(itemPlan, function (dj, itemCategory) {
					if(count2!=0)
						t2=t2+","+itemCategory.name;
					else
						t2=itemCategory.name;
					count2++;
				});
			}
			planHtml+=t2+")";
			count++;
		});
		infohtm += "分词方案："+planHtml+"<br/>";
	}
	
	//
	if(task.task == config.TASK_REPOST_TREND){//分析转发脚本
		
		infohtm += exeinfo;
		var seedweibo = "";
		/*if(task.taskparams.orimid != undefined && task.taskparams.orimid.length > 0){
			seedweibo = task.taskparams.orimid.join("<br/>");
			infohtm += "种子微博(URL)：<br/>"+seedweibo+"<br/>";
		}*/

		if(task.taskparams.forceupdate != undefined){
			infohtm += "强制抓取："+(v.taskparams.forceupdate ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.oristatus != undefined && task.taskparams.oristatus.length > 0){
			seedweibo = task.taskparams.oristatus.join("<br/>");
			infohtm += "种子微博("+task.taskparams.oristatus.length.toString()+"条)：<br/>"+seedweibo+"<br/>";
		}
		if(task.taskparams.isseed != undefined){
			infohtm += "原创是否种子："+(task.taskparams.isseed ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.isrepostseed!= undefined){
			infohtm += "转发是否种子："+(v.taskparams.isrepostseed ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.iscalctrend!= undefined){
			infohtm += "是否计算轨迹："+(task.taskparams.iscalctrend ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.config != undefined){
			infohtm += "抓取配置："+getSpiderConfigName(task.taskparams.config)+"<br/>";
		}
	    if(task.taskparams.source != undefined){
	    	var sn = getSourceName(task.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        infohtm += "数据来源："+sourcename+"<br/>";
	    }
	    if(task.taskparams.accountid != undefined){
			var accountname = [];
			$.each(task.taskparams.accountid, function(i, item){
				var sn = getSpiderAccountName(task.taskparams.source, item);
				accountname.push(sn); 
			})
	        infohtm += "抓取帐号："+accountname.join(", ")+"<br/>";
	    }
		if(task.taskparams.logoutfirst != undefined){
			infohtm += "任务开始前退出当前登录账号："+(task.taskparams.logoutfirst ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.duration != undefined){
			infohtm += "任务超时："+task.taskparams.duration+"秒<br/>";
		}
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		/*if(task.taskparams.oristatus != undefined && task.taskparams.oristatus.length > 0){
			seedweibo += "ID: "+task.taskparams.oristatus.toString();
		}
		if(task.taskparams.orimid != undefined && task.taskparams.orimid.length > 0){
			seedweibo += " MID: "+task.taskparams.orimid.toString();
		}*/
		if(task.local == 1){
			if(task.taskparams.currorigurl){
				infohtm += "正在处理："+task.taskparams.currorigurl+"<br/>";
			}
			var apicount = _getintvalue(task.taskparams.scene.repost_timeline_count) + _getintvalue(task.taskparams.scene.repost_timeline_ids_count) + _getintvalue(task.taskparams.scene.statuses_count_count);
			infohtm += "<b>统计条数：</b>总访问API次数"+apicount+"，包括：repost_timeline:"+_getintvalue(task.taskparams.scene.repost_timeline_count)+", repost_timeline_ids:"+_getintvalue(task.taskparams.scene.repost_timeline_ids_count)+"，statuses_count:"+_getintvalue(task.taskparams.scene.statuses_count_count);
			infohtm += "共出错"+_getintvalue(task.taskparams.scene.apierrorcount)+"次。 总抓取"+_getintvalue(task.taskparams.scene.spider_statuscount)+"条，被删除"+_getintvalue(task.taskparams.scene.deleted_weibocount)+"条，入库"+_getintvalue(task.taskparams.scene.insertsql_statuscount)+"条, 新增到solr失败"+_getintvalue(task.taskparams.scene.solrerrorcount)+"条,总新增用户"+_getintvalue(task.taskparams.scene.spider_usercount)+"个，更新solr总转发、总到达出错"+_getintvalue(task.taskparams.scene.calc_solrerrorcount)+"次<br/>";
			var get_seedweibo_time = _getintvalue(task.taskparams.scene.get_seedweibo_time);
			infohtm += "<b>获取种子微博总花费</b>"+get_seedweibo_time+"，当前种子微博总转发数"+_getintvalue(task.taskparams.scene.current_seedweibo_reposts_count)+"<br/>";
			var get_origrepost_time = _getintvalue(task.taskparams.scene.get_origrepost_time);
			var repost_timeline_count = _getintvalue(task.taskparams.scene.repost_timeline_count);
			var repost_timeline_time = _getintvalue(task.taskparams.scene.repost_timeline_time);
			var analysistime = _getintvalue(task.taskparams.scene.analysistime);
			var storetime = _getintvalue(task.taskparams.scene.storetime);
			var insertsql_statustime = _getintvalue(task.taskparams.scene.insertsql_statustime);
			var insertsql_usertime = _getintvalue(task.taskparams.scene.insertsql_usertime);
			infohtm += "<b>抓取原创的转发总花费</b>"+get_origrepost_time+"。包括：访问API "+repost_timeline_count+" 次花费"+repost_timeline_time+"，分析花费"+analysistime+"，存储到solr花费"+storetime+"，插入数据库花费"+insertsql_statustime+"，插入用户花费"+insertsql_usertime+"<br/>";
			var set_repost_father_time = _getintvalue(task.taskparams.scene.set_repost_father_time);
			infohtm += "<b>分析父ID总花费</b>"+set_repost_father_time+"，有二级转发的微博总数"+_getintvalue(task.taskparams.scene.all_secrepost_count)+"。包括：查询转发"+_getintvalue(task.taskparams.scene.select_repost_sqlcount)+"次(共"+_getintvalue(task.taskparams.scene.select_repost_count)+"条)花费"+_getintvalue(task.taskparams.scene.select_repost_time)+"，";
			infohtm += "检查已存在的孩子数"+_getintvalue(task.taskparams.scene.check_repost_count)+"次花费"+_getintvalue(task.taskparams.scene.check_repost_time)+"，访问API "+_getintvalue(task.taskparams.scene.repost_timeline_ids_count)+" 次花费"+_getintvalue(task.taskparams.scene.repost_timeline_ids_time)+"，";
			infohtm += "更新fatherid、转发层级"+_getintvalue(task.taskparams.complete_repost_count)+"次花费"+_getintvalue(task.taskparams.scene.update_fatherid_time)+"，更新内存数据中的转发层级"+_getintvalue(task.taskparams.scene.update_memory_count)+"次花费"+_getintvalue(task.taskparams.scene.update_memory_time)+"<br/>";
			var update_taskids_time = _getintvalue(task.taskparams.scene.update_taskids_time);
			infohtm += "<b>更新抓取进度状态(共"+_getintvalue(task.taskparams.scene.update_taskids_count)+"条)花费</b>"+update_taskids_time+"<br/>";
			var set_total_counts_time = _getintvalue(task.taskparams.scene.set_total_counts_time);
			infohtm += "<b>计算总转发数、总到达数并更新到solr总花费</b>"+set_total_counts_time+"。包括：查询转发"+_getintvalue(task.taskparams.scene.calc_select_repost_count)+"次花费"+_getintvalue(task.taskparams.scene.calc_select_repost_time)+"，计算转发数"+_getintvalue(task.taskparams.scene.calc_counts_count)+"次花费"+_getintvalue(task.taskparams.scene.calc_counts_time)+"，更新数据库"+_getintvalue(task.taskparams.scene.calc_updatecounts_count)+"次花费"+_getintvalue(task.taskparams.scene.calc_updatecounts_time)+"，更新到solr"+_getintvalue(task.taskparams.scene.calc_select_repost_count)+"次(共"+_getintvalue(task.taskparams.scene.calc_updatesolr_count)+"条)花费"+_getintvalue(task.taskparams.scene.calc_updatesolr_time)+"<br/>";
			var all_time = get_seedweibo_time + get_origrepost_time + set_repost_father_time + update_taskids_time + set_total_counts_time;
			infohtm += "<b>总计</b>：总处理原创"+_getintvalue(task.taskparams.select_cursor)+"条，总处理转发"+_getintvalue(task.taskparams.complete_repost_count)+"条，当前正在处理的转发位置"+_getintvalue(task.taskparams.select_repost_cursor)+"，总时间："+all_time+"。";
			var fatalcount = _getintvalue(task.taskparams.scene.fatalcount);
			var manual_shutdowncount = _getintvalue(task.taskparams.scene.manual_shutdowncount);
			var queuecount = _getintvalue(task.taskparams.scene.queuecount);
			var allshutdown = fatalcount + manual_shutdowncount + queuecount;
			infohtm += "共停止"+allshutdown+"次，其中任务崩溃"+fatalcount+"次，人工停止"+manual_shutdowncount+"次，由于等待资源停止"+queuecount+"次，挂起"+_getintvalue(task.taskparams.scene.hangcount)+"次， 等待时间"+_getintvalue(task.taskparams.scene.waittime)+"<br/>";
			if(task.taskparams.scene.hangstate != undefined && task.taskparams.scene.hangstate != 0){
				var hangreason = task.taskparams.scene.hangreason == undefined ? "" : task.taskparams.scene.hangreason;
				infohtm += "任务挂起，原因：" + hangreason+"<br/>";
			}
			if(task.taskparams.scene.status_desp != undefined && task.taskparams.scene.status_desp != ""){
				infohtm += "任务状态描述："+task.taskparams.scene.status_desp;
			}
		}
		else{
			if(task.taskparams.phase == 1){
				infohtm += "任务阶段：抓取原创转发<br/>";
				infohtm += "任务进度：已处理原创"+task.taskparams.select_cursor+"条";
				if(task.taskparams.currorigurl){
					infohtm += "，正在处理："+task.taskparams.currorigurl;
				}
				if(task.taskparams.repost != undefined && task.taskparams.repost.length > 0){
					if(task.taskparams.select_cursor < task.taskparams.oristatus.length){
						var origprogress = task.taskparams.repost[task.taskparams.repost.length - 1].idnum;
						infohtm += "，已抓取当前原创转发"+origprogress+"条";
					}
				}
				infohtm += "<br/>";
			}
			else if(task.taskparams.phase == 2){
				infohtm += "任务阶段：抓取二级转发<br/>";
				infohtm += "任务进度：共有二级转发"+task.taskparams.repostcount+"条，已处理二级转发"+task.taskparams.repostdone+"条<br/>";
			}
			if(task.taskparams.repost != undefined){
				infohtm += "<b>转发数列表：</b><br/>";
				for(var i = 0; i < task.taskparams.repost.length; i++){
					infohtm += "原创："+task.taskparams.repost[i].orig+"，转发"+task.taskparams.repost[i].idnum+"条<br/>";
				}
			}
			if(task.taskparams.scene.stat != undefined){
				infohtm += "<b>任务统计信息：</b><br/>";
				infohtm += printStat(task.taskparams.scene.stat);
			}
			if(task.taskparams.scene.dropcount != undefined){
				infohtm += "放弃次数："+task.taskparams.scene.dropcount+"<br/>";
			}
		}
	}
	else if(task.task == config.TASK_REPOSTPATH || task.task == config.TASK_COMMENTPATH){
		infohtm += exeinfo;
		if(task.taskparams.oriurls != undefined && task.taskparams.oriurls.length > 0){
			var seedweibo = task.taskparams.oriurls.join("<br/>");
			infohtm += "源链接("+task.taskparams.oriurls.length.toString()+"条)：<br/>"+seedweibo+"<br/>";
		}
		if(task.taskparams.errorurl != undefined && task.taskparams.errorurl.length > 0){
			infohtm += "出错链接("+task.taskparams.errorurl.length.toString()+"条)：<br/>";
			$.each(task.taskparams.errorurl, function(ei, eitem){
				infohtm += "链接:"+eitem.url+" 原因:"+eitem.msg+"<br/>";
			});
		}
	}
	else if(task.task == config.TASK_COMMENTS){//抓取评论
		infohtm += exeinfo;

		if(task.taskparams.forceupdate != undefined){
			infohtm += "强制抓取："+(v.taskparams.forceupdate ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.oristatus != undefined && task.taskparams.oristatus.length > 0){
			var seedweibo = task.taskparams.oristatus.join("<br/>");
			infohtm += "种子微博("+task.taskparams.oristatus.length.toString()+"条)：<br/>"+seedweibo+"<br/>";
		}
		if(task.taskparams.isseed != undefined){
			infohtm += "是否种子："+(task.taskparams.isseed ? "是" : "否")+"<br/>";
		}

		if(task.taskparams.config != undefined){
			infohtm += "抓取配置："+getSpiderConfigName(task.taskparams.config)+"<br/>";
		}
		  if(task.taskparams.source != undefined){
			var sn = getSourceName(task.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        infohtm += "数据来源："+sourcename+"<br/>";
		  }
	    if(task.taskparams.accountid != undefined){
			var accountname = [];
			$.each(task.taskparams.accountid, function(i, item){
				var sn = getSpiderAccountName(task.taskparams.source, item);
				accountname.push(sn); 
			})
	        infohtm += "抓取帐号："+accountname.join(", ")+"<br/>";
	    }
		if(task.taskparams.logoutfirst != undefined){
			infohtm += "任务开始前退出当前登录账号："+(task.taskparams.logoutfirst ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.duration != undefined){
			infohtm += "任务超时："+task.taskparams.duration+"秒<br/>";
		}
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.iscalctrend!= undefined){
			infohtm += "是否计算轨迹："+(task.taskparams.iscalctrend ? "是" : "否")+"<br/>";
		}

		if(task.local == 1){
			if(task.taskparams.currorigurl){
				infohtm += "正在处理："+task.taskparams.currorigurl+"<br/>";
			}
			infohtm += "<b>统计条数：</b>已处理原创"+_getintvalue(task.taskparams.select_cursor)+"条，总抓取"+_getintvalue(task.taskparams.scene.spider_statuscount)+"条，入库"+_getintvalue(task.taskparams.scene.new_comments_count)+"条，新增用户"+_getintvalue(task.taskparams.scene.new_user_count)+"个。API共出错"+_getintvalue(task.taskparams.scene.apierrorcount)+"次，新增到solr失败"+_getintvalue(task.taskparams.scene.solrerrorcount)+"条，更新回复数失败"+_getintvalue(task.taskparams.scene.calc_solrerrorcount)+"条<br/>";
			infohtm += "<b>统计时间：</b>获取种子微博总花费"+_getintvalue(task.taskparams.scene.get_seedweibo_time)+"，获取评论总花费"+_getintvalue(task.taskparams.scene.get_comments_time)+"，入库总花费"+_getintvalue(task.taskparams.scene.insert_comments_time)+"，更新回复数总花费"+_getintvalue(task.taskparams.scene.calc_comments_time)+"<br/>";
			if(task.taskparams.scene.status_desp != undefined && task.taskparams.scene.status_desp != ""){
				infohtm += "任务状态描述："+task.taskparams.scene.status_desp;
			}
		}
		else{
			infohtm += "任务进度：已处理微博"+(task.taskparams.select_cursor ? task.taskparams.select_cursor : 0)+"条";
			if(task.taskparams.currorigurl){
				infohtm += "，正在处理："+task.taskparams.currorigurl;
			}
			if(task.taskparams.comment != undefined && task.taskparams.comment.length > 0){
				if(task.taskparams.select_cursor < task.taskparams.oristatus.length){
					var origprogress = task.taskparams.comment[task.taskparams.comment.length - 1].idnum;
					infohtm += "，已抓取当前微博评论"+origprogress+"条";
				}
			}
			infohtm += "<br/>";
			if(task.taskparams.comment != undefined){
				infohtm += "<b>评论数列表：</b><br/>";
				for(var i = 0; i < task.taskparams.comment.length; i++){
					infohtm += "微博："+task.taskparams.comment[i].orig+"，评论"+task.taskparams.comment[i].idnum+"条<br/>";
				}
			}
			if(task.taskparams.scene.stat != undefined){
				infohtm += "<b>任务统计信息：</b><br/>";
				infohtm += printStat(task.taskparams.scene.stat);
			}
			if(task.taskparams.scene.dropcount != undefined){
				infohtm += "放弃次数："+task.taskparams.scene.dropcount+"<br/>";
			}
		}
	}
	else if(task.task == config.TASK_KEYWORD){//抓取关键词
		infohtm += "抓取配置："+getSpiderConfigName(task.taskparams.config)+"<br/>";
	    if(task.taskparams.source != undefined){
	    	var sn = getSourceName(task.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        infohtm += "数据来源："+sourcename+"<br/>";
	    }
	    if(task.taskparams.accountid != undefined){
			var accountname = [];
			$.each(task.taskparams.accountid, function(i, item){
				var sn = getSpiderAccountName(task.taskparams.source, item);
				accountname.push(sn); 
			})
	        infohtm += "抓取帐号："+accountname.join(", ")+"<br/>";
	    }
		if(task.taskparams.logoutfirst != undefined){
			infohtm += "任务开始前退出当前登录账号："+(task.taskparams.logoutfirst ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.isswitch != undefined){
			infohtm += "是否切换帐号："+(task.taskparams.isswitch ? "是" : "否")+"<br/>";
			if(task.taskparams.isswitch){
				infohtm += "帐号切换策略："+task.taskparams.switchpage+"页/"+task.taskparams.switchtime+"秒<br/>";
			}
			if(parseInt(task.taskparams.globalaccount,10)){
				infohtm += "使用全局帐号："+(task.taskparams.isswitch ? "是" : "否")+"<br/>";
			}
		}
		if(task.taskparams.keywords != undefined){
			infohtm += "关键词："+task.taskparams.keywords+"<br/>";
		}
		if(task.taskparams.username != undefined){
			infohtm += "用户昵称："+task.taskparams.username+"<br/>";
		}
		infohtm += "是否种子："+(task.taskparams.isseed ? "是" : "否")+"<br/>";
		if(task.taskparams.filterdup != undefined){
			infohtm += "去除重复："+(task.taskparams.filterdup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.starttime != undefined){
			infohtm += "开始时间："+timeToStr(task.taskparams.starttime)+"<br/>";
		}
		if(task.taskparams.endtime != undefined){
			infohtm += "结束时间："+timeToStr(task.taskparams.endtime)+"<br/>";
		}
		if(task.relativestart!= undefined){
			var ttrs = task.relativestart;
			var rsstr = "";
			if(ttrs == "now"){
				rsstr = "创建时间";
			}
			else{
				var rs = task.relativestart.split(" ");
				var num = parseFloat(rs[0]);
				var gap = getGaptext(rs[1]);
				if(num > 0){
					rsstr = "后"+num+gap;
				}
				else{
					var tnum = -num;
					rsstr = "前"+tnum+gap;
				}
			}
			infohtm += "相对开始时间："+rsstr+"<br/>";
		}
		if(task.relativeend != undefined){
			var ttre = task.relativeend;
			var restr = "";
			if(ttre == "now"){
				restr = "创建时间";
			}
			else{
				var rs = task.relativeend.split(" ");
				var num = parseFloat(rs[0]);
				var gap = getGaptext(rs[1]);
				if(num > 0){
					restr = "后"+num+gap;
				}
				else{
					var tnum = -num;
					restr = "前"+tnum+gap;
				}
			}
			infohtm += "相对结束时间："+restr+"<br/>";
		}
		if(task.taskparams.step != undefined && task.taskparams.step.length > 0){
			infohtm += "步长："+parseInt(task.taskparams.step, 10)+(task.taskparams.step.indexOf("h") > 0 ? "小时" : "天")+"<br/>";
		}
		infohtm += "任务超时："+task.taskparams.duration+"秒<br/>";
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.scene.dropcount != undefined){
			infohtm += "放弃次数："+task.taskparams.scene.dropcount+"<br/>";
		}
		if(task.taskparams.scene.stat != undefined){
			infohtm += printStat(task.taskparams.scene.stat);
		}
	}
	else if(task.task == config.TASK_WEBPAGE){//论坛
		if(task.taskparams.searchengine != undefined){
			infohtm += "搜索引擎："+getSearchEngineName(task.taskparams.searchengine)+"<br/>";
		}
		if(task.taskparams.crawlpage){
			infohtm += "抓取页数："+task.taskparams.crawlpage+"<br/>";
		}
		if(task.taskparams.createdtimestart != undefined){
			infohtm += "主帖发表时间开始："+timeToStr(task.taskparams.createdtimestart)+"<br/>";
		}
		if(task.taskparams.createdtimeend != undefined){
			infohtm += "主帖发表时间结束："+timeToStr(task.taskparams.createdtimeend)+"<br/>";
		}
		if(task.taskparams.searchsite != undefined){
			infohtm += "抓取站点："+getSearchSiteName(task.taskparams.searchsite)+"<br/>";
		}
	    if(task.taskparams.source != undefined){
	    	var sn = getSourceName(task.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        infohtm += "数据来源："+sourcename+"<br/>";
	    }
		if(task.taskparams.listurls){
			var u = task.taskparams.listurls;
			if(isArray(u)){
				var urlstr = "";
				$.each(u, function(ui, uitem){
					urlstr += "http:"+uitem.url+"<br/>";
				});
				infohtm += "抓取列表URL："+urlstr+"<br/>";
			}
			else{
				u = u.toString().replace(/</g, "&lt;");
				u = u.toString().replace(/>/g, "&gt;");
				infohtm += "抓取列表URL："+u+"<br/>";
			}
		}
		if(task.taskparams.texturls){
			var u = task.taskparams.texturls;
			if(isArray(u)){
				var urlstr = "";
				/*
				$.each(u, function(ui, uitem){
					urlstr += "http:"+uitem.url+"<br/>";
				});
				*/
				infohtm += "抓取文章详情URL："+urlstr+"<br/>";
			}
			else{
				u = u.toString().replace(/</g, "&lt;");
				u = u.toString().replace(/>/g, "&gt;");
				infohtm += "抓取文章详情URL："+u+"<br/>";
			}
		}
		if(task.taskparams.importarticlecount){
			infohtm += "每个任务包含源文章数："+task.taskparams.importarticlecount+"<br/>";
		}
		if(task.taskparams.lastrplytimestart != undefined){
			infohtm += "文章发表时间开始："+timeToStr(task.taskparams.lastrplytimestart)+"<br/>";
		}
		if(task.taskparams.lastrplytimeend != undefined){
			infohtm += "文章发表时间结束："+timeToStr(task.taskparams.lastrplytimeend)+"<br/>";
		}
		if(task.taskparams.userurls){
			var u = task.taskparams.userurls;
			if(isArray(u)){

				var urlstr = "";
				$.each(u, function(ui, uitem){
					urlstr += "http:"+uitem.url+"<br/>";
				});
				infohtm += "抓取用户URL："+urlstr+"<br/>";
			}
			else{
				u = u.toString().replace(/</g, "&lt;");
				u = u.toString().replace(/>/g, "&gt;");
				infohtm += "抓取用户URL："+u+"<br/>";
			}
		}
		if(task.taskparams.importusercount){
			infohtm += "每个任务包含用户数："+task.taskparams.importusercount+"<br/>";
		}
		if(task.taskparams.userids){
			var userids = task.taskparams.userids;
			if(isArray(userids)){
				infohtm += "抓取用户id："+userids.join(",")+"<br/>";
			}
		}
		if(task.taskparams.logoutfirst != undefined){
			infohtm += "任务开始前退出当前登录账号："+(task.taskparams.logoutfirst ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.iscalctrend!= undefined){
			infohtm += "是否计算轨迹："+(task.taskparams.iscalctrend ? "是" : "否")+"<br/>";
		}

	    if(task.taskparams.accountid != undefined){
			var accountname = [];
			$.each(task.taskparams.accountid, function(i, item){
				var sn = getSpiderAccountName(task.taskparams.source, item);
				accountname.push(sn); 
			})
	        infohtm += "抓取帐号："+accountname.join(", ")+"<br/>";
	    }
		if(task.taskparams.isswitch != undefined){
			infohtm += "是否切换帐号："+(task.taskparams.isswitch ? "是" : "否")+"<br/>";
			if(task.taskparams.isswitch){
				infohtm += "帐号切换策略："+task.taskparams.switchpage+"页/"+task.taskparams.switchtime+"秒<br/>";
			}
			if(parseInt(task.taskparams.globalaccount,10)){
				infohtm += "使用全局帐号："+(task.taskparams.isswitch ? "是" : "否")+"<br/>";
			}
		}

		if(task.taskparams.keywords != undefined){
			infohtm += "关键词："+task.taskparams.keywords+"<br/>";
		}
		if(task.taskparams.username != undefined){
			infohtm += "用户昵称："+task.taskparams.username+"<br/>";
		}
		if(task.taskparams.filterdup != undefined){
			infohtm += "去除重复："+(task.taskparams.filterdup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.starttime != undefined){
			infohtm += "开始时间："+timeToStr(task.taskparams.starttime)+"<br/>";
		}
		if(task.taskparams.endtime != undefined){
			infohtm += "结束时间："+timeToStr(task.taskparams.endtime)+"<br/>";
		}
		if(task.relativestart!= undefined){
			var ttrs = task.relativestart;
			var rsstr = "";
			if(ttrs == "now"){
				rsstr = "创建时间";
			}
			else{
				var rs = task.relativestart.split(" ");
				var num = parseFloat(rs[0]);
				var gap = getGaptext(rs[1]);
				if(num > 0){
					rsstr = "后"+num+gap;
				}
				else{
					var tnum = -num;
					rsstr = "前"+tnum+gap;
				}
			}
			infohtm += "相对开始时间："+rsstr+"<br/>";
		}
		if(task.relativeend != undefined){
			var ttre = task.relativeend;
			var restr = "";
			if(ttre == "now"){
				restr = "创建时间";
			}
			else{
				var rs = task.relativeend.split(" ");
				var num = parseFloat(rs[0]);
				var gap = getGaptext(rs[1]);
				if(num > 0){
					restr = "后"+num+gap;
				}
				else{
					var tnum = -num;
					restr = "前"+tnum+gap;
				}
			}
			infohtm += "相对结束时间："+restr+"<br/>";
		}
		if(task.taskparams.step != undefined && task.taskparams.step.length > 0){
			infohtm += "步长："+parseInt(task.taskparams.step, 10)+(task.taskparams.step.indexOf("h") > 0 ? "小时" : "天")+"<br/>";
		}
		infohtm += "任务超时："+task.taskparams.duration+"秒<br/>";
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.scene.dropcount != undefined){
			infohtm += "放弃次数："+task.taskparams.scene.dropcount+"<br/>";
		}
		if(task.taskparams.scene.stat != undefined){
			infohtm += printStat(task.taskparams.scene.stat);
		}
	}
	else if(task.task == config.TASK_FRIEND){//抓取关注
		infohtm += "抓取配置："+getSpiderConfigName(task.taskparams.config)+"<br/>";
	    if(task.taskparams.source != undefined){
	    	var sn = getSourceName(task.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        infohtm += "数据来源："+sourcename+"<br/>";
	    }
		if(task.taskparams.unames != undefined){
			infohtm += "用户："+task.taskparams.unames+"<br/>";
		}
		else{
			infohtm += "用户ID："+task.taskparams.uids+"<br/>";
		}
		infohtm += "是否种子："+(task.taskparams.isseed ? "是" : "否")+"<br/>";
		infohtm += "任务超时："+task.taskparams.duration+"秒<br/>";
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.scene.dropcount != undefined){
			infohtm += "放弃次数："+task.taskparams.scene.dropcount+"<br/>";
		}
		if(task.taskparams.scene.stat != undefined){
			infohtm += printStat(task.taskparams.scene.stat);
		}
	}
	else if(task.task == config.TASK_MIGRATEDATA){//迁移数据
		infohtm += "<b>配置信息</b><br/>";
		infohtm += "源主机："+getHostName(task.taskparams.srchost)+"<br/>";
		var dsthosts = [];
		if(task.taskparams.dsthost != undefined){
			for(var i = 0; i < task.taskparams.dsthost.length; i++){
				dsthosts.push(getHostName(task.taskparams.dsthost[i]));
			}
			infohtm += "目标主机："+dsthosts.join(",")+"<br/>";
		}
		infohtm += "最大处理条数：";
		if(task.taskparams.maxcount != undefined){
			infohtm += task.taskparams.maxcount+"<br/>";
		}
		else{
			infohtm += "全部<br/>";
		}
		if(task.taskparams.eachcount != undefined){
			infohtm += "每次处理条数："+task.taskparams.eachcount+"<br/>";
		}
		if(task.taskparams.keepsrc != undefined){
			infohtm += "保留源数据："+(task.taskparams.keepsrc ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.delseedweibo != undefined){
			infohtm += "删除种子微博："+(task.taskparams.delseedweibo ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.deluser != undefined){
			infohtm += "删除用户："+(task.taskparams.deluser ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.delseeduser != undefined){
			infohtm += "删除种子用户："+(task.taskparams.delseeduser ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.source != undefined){
			var sn = getSourceName(task.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        infohtm += "数据来源："+sourcename+"<br/>";
		}
		if(task.taskparams.cond_ge_created != undefined){
			infohtm += "最小创建时间："+timeToStr(task.taskparams.cond_ge_created)+"<br/>";
		}
		if(task.taskparams.cond_lt_created != undefined){
			infohtm += "最大创建时间："+timeToStr(task.taskparams.cond_lt_created)+"<br/>";
		}
		if(task.taskparams.cond_ex_text != undefined){
			infohtm += "排除关键词："+task.taskparams.cond_ex_text+"<br/>";
		}
		if(task.taskparams.cond_in_text != undefined){
			infohtm += "包含关键词："+task.taskparams.cond_in_text+"<br/>";
		}
		if(task.taskparams.cond_ex_name != undefined){
			infohtm += "排除用户昵称："+task.taskparams.cond_ex_name+"<br/>";
		}
		if(task.taskparams.cond_in_name != undefined){
			infohtm += "包含用户昵称："+task.taskparams.cond_in_name+"<br/>";
		}
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		infohtm += "<b>统计信息</b><br/>";
		if(!task.taskparams.keepsrc){
			if(task.taskparams.select_cursor != undefined){
				infohtm += "<span style='color:red;'>保留文章数：</span>"+task.taskparams.select_cursor+"<br/>";
			}
		}
		if(task.taskparams.scene.delete_status_count != undefined){
			infohtm += "删除文章数："+task.taskparams.scene.delete_status_count+"<br/>";
		}
		if(task.taskparams.scene.delete_user_count != undefined){
			infohtm += "删除用户数："+task.taskparams.scene.delete_user_count+"<br/>";
		}
		if(task.taskparams.scene.alltime != undefined){
			infohtm += "总花费时间："+task.taskparams.scene.alltime+"<br/>";
		}
		if(task.taskparams.scene.solr_time != undefined){
			infohtm += "源solr总时间："+task.taskparams.scene.solr_time+"<br/>";
		}
		if(task.taskparams.scene.solr_query_time != undefined){
			infohtm += "源solr查询时间："+task.taskparams.scene.solr_query_time+"<br/>";
		}
		if(task.taskparams.scene.solr_retrieve_time != undefined){
			infohtm += "源solr提取时间："+task.taskparams.scene.solr_retrieve_time+"<br/>";
		}
		if(task.taskparams.scene.solr_delete_time != undefined){
			infohtm += "源solr删除时间："+task.taskparams.scene.solr_delete_time+"<br/>";
		}
		if(task.taskparams.scene.db_time != undefined){
			infohtm += "源数据库总时间："+task.taskparams.scene.db_time+"<br/>";
		}
		if(task.taskparams.scene.db_query_time != undefined){
			infohtm += "源数据库查询时间："+task.taskparams.scene.db_query_time+"<br/>";
		}
		if(task.taskparams.scene.db_retrieve_time != undefined){
			infohtm += "源数据库提取时间："+task.taskparams.scene.db_retrieve_time+"<br/>";
		}
		if(task.taskparams.scene.db_delete_time != undefined){
			infohtm += "源数据库删除时间："+task.taskparams.scene.db_delete_time+"<br/>";
		}
		if(task.taskparams.scene.dst != undefined){
			for(var i = 0; i < task.taskparams.scene.dst.length; i++){
				infohtm += "目标主机"+dsthosts[i]+"<br/>";
				if(task.taskparams.scene.dst[i].insert_status_count != undefined){
					infohtm += "插入文章数："+task.taskparams.scene.dst[i].insert_status_count+"<br/>";
				}
				if(task.taskparams.scene.dst[i].update_status_count != undefined){
					infohtm += "更新文章数："+task.taskparams.scene.dst[i].update_status_count+"<br/>";
				}
				if(task.taskparams.scene.dst[i].insert_user_count != undefined){
					infohtm += "插入用户数："+task.taskparams.scene.dst[i].insert_user_count+"<br/>";
				}
				if(task.taskparams.scene.dst[i].update_user_count != undefined){
					infohtm += "更新用户数："+task.taskparams.scene.dst[i].update_user_count+"<br/>";
				}
				if(task.taskparams.scene.dst[i].solr_time != undefined){
					infohtm += "solr总时间："+task.taskparams.scene.dst[i].solr_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].solr_query_time != undefined){
					infohtm += "solr查询时间："+task.taskparams.scene.dst[i].solr_query_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].solr_insert_time != undefined){
					infohtm += "solr插入时间："+task.taskparams.scene.dst[i].solr_insert_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].solr_update_time != undefined){
					infohtm += "solr更新时间："+task.taskparams.scene.dst[i].solr_update_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].db_time != undefined){
					infohtm += "数据库总时间："+task.taskparams.scene.dst[i].db_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].db_query_time != undefined){
					infohtm += "数据库查询时间："+task.taskparams.scene.dst[i].db_query_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].db_insert_time != undefined){
					infohtm += "数据库插入时间："+task.taskparams.scene.dst[i].db_insert_time+"<br/>";
				}
				if(task.taskparams.scene.dst[i].db_update_time != undefined){
					infohtm += "数据库更新时间："+task.taskparams.scene.dst[i].db_update_time+"<br/>";
				}
			}
		}
	}
	else if(task.task == config.TASK_SNAPSHOT || task.task == config.TASK_EVENTALERT){//更新快照
		infohtm += "<b>配置信息</b><br/>";
		infohtm += "实例ID："+task.taskparams.instanceid+"<br/>";
		if(task.taskparams.spawntime != undefined){
			infohtm += "更新时间："+timeToStr(task.taskparams.spawntime)+"<br/>";
		}
		if(task.taskparams.history_enable != undefined){
			infohtm += "保存历史："+(task.taskparams.history_enable ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.history_count != undefined){
			infohtm += "保存历史条数："+task.taskparams.history_count+"<br/>";
		}
		if(task.taskparams.history_duration != undefined){
			infohtm += "保存历史时间："+task.taskparams.history_duration+"秒<br/>";
		}
		if("nodup" in task){
			infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
		}
		if(task.taskparams.eventlist!= undefined){
			if(task.taskparams.eventlist.name){
				infohtm += "事件名称："+task.taskparams.eventlist.name+"<br/>";
			}
			if(task.taskparams.eventlist.remarks){
				infohtm += "事件描述："+task.taskparams.eventlist.remarks+"<br/>";
			}
			if(task.taskparams.eventlist.alarms){
				var alarmArr = task.taskparams.eventlist.alarms;
				var elecondition = {}
				/*
				if(!elecondition){
					var elecondition = {}
					elecondition.firstrequestData = ritem.snapshot;
					elecondition.dataJson = ritem.content;
				}
				*/
				infohtm += "预警条件:<br/>";
				$.each(alarmArr, function(ai, aitem){
					var tmpnum = parseInt(ai, 10) + 1;
					infohtm += "&nbsp;&nbsp;&nbsp;&nbsp;条件"+tmpnum+":"+getLogicExpDes(aitem.trigger, elecondition)+"<br/>";
				});
			}
		}
		else{
				infohtm += "预警条件：无<br/>";
		}
		if(task.taskparams.scene.userid != undefined){
			infohtm += "<b>统计信息</b><br/>";
			infohtm += "用户ID："+task.taskparams.scene.userid+"<br/>";
		}
		if(task.taskparams.scene.reqstat != undefined){
			infohtm += "请求数："+task.taskparams.scene.reqstat.length+"<br/>";
			for(var i = 0; i < task.taskparams.scene.reqstat.length; i++){
				infohtm += "请求"+i+"元素ID："+task.taskparams.scene.reqstat[i].elementid+"，时间："+task.taskparams.scene.reqstat[i].reqtime+"秒<br/>";
			}
		}
		if(task.taskparams.scene.alltime != undefined){
			infohtm += "总时间："+task.taskparams.scene.alltime+"秒<br/>";
		}
	}
	else{
	    if(v.taskparams.source != undefined){
	    	var sn = getSourceName(v.taskparams.source);
			var sourcename = sn;
			if(!sourcename){
				sourcename = task.taskparams.source;
			}
	        task_term += sourcename;
	    }
	    else{
	    	task_term += '全部';
	    }

	    if(v.task == config.TASK_IMPORTWEIBOURL){//植入微博
	    	var datatype = v.taskparams.datatype ? v.taskparams.datatype : v.taskparams.urltype;
	    	var _data = v.taskparams.urls ? v.taskparams.urls : v.taskparams.data;
	    	if(datatype == 'comment'){
	    		task_term += ' 植入评论';
	    	}
	    	else if(datatype == 'weibo'){
	    		task_term += ' 植入微博内容';
	    	}
	    	else if(datatype == 'url'){
	    		task_term += ' 植入微博URL';
	    	}
	    	else{
	    		task_term += ' 植入微博ID';
	    	}
	    	if(v.taskparams.isseed){
	    		task_term += ' 种子微博';
	    	}
	    	else{
	    		task_term += ' 非种子微博';
	    	}
	    	if(v.taskparams.addreposttask){
	    		task_term += ' 同时添加转发任务';
	    	}
	    	task_term += '<br/>数据：<br/>';
	    	for(var i=0; i<_data.length; i++){
	    		var rd = '';
	    		if(datatype == 'id' || datatype == 'url' || datatype == 'comment'){
	    			rd = typeof(_data[i]) == 'object' ? _data[i][datatype] : _data[i];
	    		}
	    		else{
	    			continue;//微博内容不显示
	    		}
	    		if(i == (_data.length -1)){
	    			task_term += rd;
	    		}
	    		else{
		    		task_term += rd+', ';
	    		}
	    	}
	    	if(task_term != ''){
	    		infohtm += '参数：'+task_term + '<br/>';
	    	}
	    	if(v.taskparams.depend != undefined){
	    		infohtm += '依赖任务：'+v.taskparams.depend + '<br/>';
	    	}
	    }
	    else if(v.task == config.TASK_IMPORTUSERID){//植入用户
	    	var _data = v.taskparams.urls ? v.taskparams.urls : v.taskparams.data;
	    	if(v.taskparams.datatype == 'id'){
	    		task_term += ' 植入用户ID';
	    	}
	    	else{
	    		task_term += ' 植入用户昵称';
	    	}
	    	if(v.taskparams.isseed){
	    		task_term += ' 种子用户';
	    	}
	    	else{
	    		task_term += ' 非种子用户';
	    	}
	    	if(v.taskparams.getfriends){
	    		task_term += ' 立即抓取关注';
	    	}
	    	task_term += '<br/>数据：<br/>';
	    	for(var i = 0; i < v.taskparams.data.length; i++){
	    		if(i == (v.taskparams.data.length -1)){
	    			task_term += v.taskparams.data[i];
	    		}
	    		else{
		    		task_term += v.taskparams.data[i]+', ';
	    		}
	    	}
	    	if(task_term != ''){
	    		infohtm += '参数：'+task_term + '<br/>';
	    	}
	    }
	    else{
			/*
		    if(v.taskparams.maxanalysistime != undefined){
		    	 task_term += '  时间早于 '+timeToStr(_getintvalue(v.taskparams.maxanalysistime))+' 的数据';
		    } 
			*/
		    if(v.taskparams.users != undefined){
		    	if(v.taskparams.inputtype == 'id'){
		    		task_term += '  指定用户ID： ';
		    	}
		    	else{
		    		task_term += '  指定用户昵称： ';
		    	}
		   	 	task_term += v.taskparams.users.toString()+' ';
		    }
		    if(v.taskparams.usertype != undefined){
		   	 	task_term += '  用户类型： '+getUserTypeText(v.taskparams.usertype.toString())+' ';
		    }
		    if(v.taskparams.min_follower_count != undefined){
		   	 	task_term += '  粉丝数大于： '+_getintvalue(v.taskparams.min_follower_count)+'的 ';
		    }
		    if(task_term != ''){
		    	infohtm += '数据范围：'+task_term + '<br/>';
		    }
		    if(v.taskparams.config != undefined){
				infohtm += "抓取配置："+getSpiderConfigName(v.taskparams.config)+"<br/>";
		    }
		    if(v.taskparams.isseed != undefined){
		    	infohtm += "是否种子："+(v.taskparams.isseed ? "是" : "否")+"<br/>";
		    }
		    if(v.taskparams.minanalysistime!= undefined){
		    	infohtm += "文章分析开始时间："+timeToStr(v.taskparams.minanalysistime)+"<br/>";
		    }
		    if(v.taskparams.maxanalysistime != undefined){
		    	infohtm += "文章分析结束时间："+timeToStr(v.taskparams.maxanalysistime)+"<br/>";
		    }
		    if(v.taskparams.min_created_time!= undefined){
		    	infohtm += "开始时间："+timeToStr(v.taskparams.min_created_time)+"<br/>";
		    }
		    if(v.taskparams.max_created_time != undefined){
		    	infohtm += "结束时间："+timeToStr(v.taskparams.max_created_time)+"<br/>";
		    }
			if(v.taskparams.source_host){
				infohtm += "分析数据来源："+v.taskparams.source_host.join(",")+"<br/>";
			}
			if(v.taskparams.urls){
				var u = v.taskparams.urls;
				if(isArray(u)){
					var urlstr = "";
					$.each(u, function(ui, uitem){
						urlstr += ""+uitem+"<br/>";
					});
					infohtm += "分析列表URL："+urlstr+"<br/>";
				}
			}
		    if(v.taskparams.starttime != undefined){
		    	infohtm += "开始时间："+timeToStr(v.taskparams.starttime)+"<br/>";
		    }

		    if(v.taskparams.endtime != undefined){
		    	infohtm += "结束时间："+timeToStr(v.taskparams.endtime)+"<br/>";
		    }
			if(task.relativestart!= undefined){
				var ttrs = task.relativestart;
				var rsstr = "";
				if(ttrs == "now"){
					rsstr = "创建时间";
				}
				else{
					var rs = task.relativestart.split(" ");
					var num = parseFloat(rs[0]);
					var gap = getGaptext(rs[1]);
					if(num > 0){
						rsstr = "后"+num+gap;
					}
					else{
						var tnum = -num;
						rsstr = "前"+tnum+gap;
					}
				}
				infohtm += "相对开始时间："+rsstr+"<br/>";
			}
			if(task.relativeend != undefined){
				var ttre = task.relativeend;
				var restr = "";
				if(ttre == "now"){
					restr = "创建时间";
				}
				else{
					var rs = task.relativeend.split(" ");
					var num = parseFloat(rs[0]);
					var gap = getGaptext(rs[1]);
					if(num > 0){
						restr = "后"+num+gap;
					}
					else{
						var tnum = -num;
						restr = "前"+tnum+gap;
					}
				}
				infohtm += "相对结束时间："+restr+"<br/>";
			}
			if(task.taskparams.logoutfirst != undefined){
				infohtm += "任务开始前退出当前登录账号："+(task.taskparams.logoutfirst ? "是" : "否")+"<br/>";
			}
			if(task.taskparams.accountid != undefined){
				var accountname = [];
				$.each(task.taskparams.accountid, function(i, item){
					var sn = getSpiderAccountName(task.taskparams.source, item);
					accountname.push(sn); 
				})
				infohtm += "抓取帐号："+accountname.join(", ")+"<br/>";
			}
			if(task.taskparams.isswitch != undefined){
				infohtm += "是否切换帐号："+(task.taskparams.isswitch ? "是" : "否")+"<br/>";
				if(task.taskparams.isswitch){
					infohtm += "帐号切换策略："+task.taskparams.switchpage+"页/"+task.taskparams.switchtime+"秒<br/>";
				}
				if(parseInt(task.taskparams.globalaccount,10)){
					infohtm += "使用全局帐号："+(task.taskparams.isswitch ? "是" : "否")+"<br/>";
				}
			}

		    if(v.taskparams.step != undefined && v.taskparams.step.length > 0){
		    	infohtm += "步长："+parseInt(v.taskparams.step, 10)+(v.taskparams.step.indexOf("h") > 0 ? "小时" : "天")+"<br/>";
		    }
		    if(v.taskparams.duration != undefined){
		    	infohtm += "任务超时："+v.taskparams.duration+"秒<br/>";
		    }
			if("nodup" in task){
				infohtm += "允许重复任务："+(task.nodup ? "是" : "否")+"<br/>";
			}
		    if(v.taskparams.scene.dropcount != undefined){
		    	infohtm += "放弃次数："+v.taskparams.scene.dropcount+"<br/>";
		    }
		    if(v.taskparams.scene.stat != undefined){
		    	infohtm += printStat(v.taskparams.scene.stat);
		    }
		    if(v.taskparams.scene.users_notext != undefined && v.taskparams.scene.users_notext.length > 0){
				var darr = [];
				$.each(v.taskparams.scene.users_notext, function(vi, vitem){
					var n = "";
					n+=vitem;
					if(v.taskparams.scene.users_notexistname != undefined && v.taskparams.scene.users_notexistname.length > 0){
						n += "("+v.taskparams.scene.users_notexistname[vi]+")"; 
					}
					darr.push(n); 
				});
				if(darr.length > 0){
					infohtm += "不存在的用户："+darr.join(", ")+"<br/>";
				}
				else{
					infohtm += "不存在的用户："+v.taskparams.scene.users_notext.join(", ")+"<br/>";
				}
		    }
			if(v.taskparams.scene.users_change_screen_name != undefined && v.taskparams.scene.users_change_screen_name.length > 0){
				infohtm += "更改昵称的用户:"+v.taskparams.scene.users_change_screen_name.join(", ")+"<br/>";
			}
		    if(v.taskparams.scene.users_id2name != undefined && v.taskparams.scene.users_id2name.length > 0){
		    	infohtm += "用户ID昵称映射："+v.taskparams.scene.users_id2name.join(", ")+"<br/>";
		    }
	    }
	    var func = "";
	    var tokenize_fieldname = {text:"原文内容", verified_reason:"认证原因", description:"用户简介", post_title:"帖子标题"};
	    if(v.taskparams.tokenize_fields != undefined){
	    	func += "重新分析："
	    	$.each(v.taskparams.tokenize_fields,function(tfi,tf){
	    		var sp = ",";
	    		if(tfi == v.taskparams.tokenize_fields.length -1){
	    			sp="<br/>";
	    		}
		    	func += tokenize_fieldname[tf]+sp;
	    	});
	    }
	    var other_fieldname = {area:"用户地区", reposts_count:"转发数", direct_reposts_count:"直接转发数",total_reposts_count:"总转发数",
	    		followers_count:"直接到达数",total_reach_count:"总到达数",comments_count:"评论数",time:"时间"};
	    if(v.taskparams.other_fields != undefined){
	    	func += "更新："
		    	$.each(v.taskparams.other_fields,function(tfi,tf){
		    		var sp = ",";
		    		if(tfi == v.taskparams.other_fields.length -1){
		    			sp="<br/>";
		    		}
			    	func += other_fieldname[tf]+sp;
		    	});
	    }
	    infohtm += func;
    	infohtm += exeinfo;
    if(v.task == config.TASK_IMPORTUSERID){
      if(v.taskparams.scene.alltime){
    	  infohtm += '总花费时间：'+_getintvalue(v.taskparams.scene.alltime)+'<br/>';
      }
      if(v.taskparams.scene.solr_query_time){
    	  infohtm += '查询用户花费时间：'+_getintvalue(v.taskparams.scene.solr_query_time)+'<br/>';
      }
      if(v.taskparams.scene.solr_update_time){
    	  infohtm += '更新用户花费时间：'+_getintvalue(v.taskparams.scene.solr_update_time)+'<br/>';
      }
    }
    if(!v.taskparams.scene.isremote){
			if(v.taskparams.scene.execcount != undefined){
				infohtm += '执行次数：'+_getintvalue(v.taskparams.scene.execcount)+'<br/>';
			}
			if(v.taskparams.scene.analysistime != undefined){
				infohtm += '分析文档花费时间：'+_getintvalue(v.taskparams.scene.analysistime)+'<br/>';
			}
			if(v.taskparams.scene.storetime != undefined){
				infohtm += '存储文档花费时间：'+_getintvalue(v.taskparams.scene.storetime)+'<br/>';
			}
	    if(v.taskparams.scene.sqlquerytime != undefined){
	    	infohtm += '查询数据花费时间：'+_getintvalue(v.taskparams.scene.sqlquerytime)+'<br/>';
	    }
	    if(v.taskparams.scene.sqlupdatetime != undefined){
	    	infohtm += '更新分析状态花费时间：'+_getintvalue(v.taskparams.scene.sqlupdatetime)+'<br/>';
	    }
	    
	    if(v.taskparams.scene.apicount_usertimeline != undefined){
	    	infohtm += '访问API(user_timeline)次数：'+v.taskparams.scene.apicount_usertimeline;
	    	var errorcnt = v.taskparams.scene.apierrorcount_usertimeline ? v.taskparams.scene.apierrorcount_usertimeline : 0;
	    	infohtm += ', 出错数：'+_getintvalue(v.taskparams.scene.apierrorcount_usertimeline);
	    	infohtm += ', 花费时间：'+_getintvalue(v.taskparams.scene.apitime_usertimeline)+'<br/>';
	    }
	    if(v.taskparams.scene.spider_statuscount != undefined){
	    	infohtm += '总抓取数：'+v.taskparams.scene.spider_statuscount;
	    	infohtm += ', 总入库数：'+_getintvalue(v.taskparams.scene.insertsql_statuscount);
	    	infohtm += ', 入库花费时间：'+_getintvalue(v.taskparams.scene.insertsql_statustime)+'<br/>';
	    }
	    if(v.taskparams.scene.spider_usercount != undefined){
	    	infohtm += '新用户数：'+v.taskparams.scene.spider_usercount;
	    	infohtm += ', 入库花费时间：'+_getintvalue(v.taskparams.scene.insertsql_usertime)+'<br/>';
	    }
	    if(v.taskparams.seedusercount != undefined){
	    	infohtm += '总种子用户数：'+v.taskparams.seedusercount;
	    	infohtm += ', 已处理：'+_getintvalue(v.taskparams.select_user_cursor);
	    	infohtm += ', 总花费时间：'+_getintvalue(v.taskparams.scene.alltime);
	    }
	    
	    if(v.taskparams.scene.databasecount != undefined){
	        var dbcount = _getintvalue(v.taskparams.scene.databasecount);
	    	infohtm += '全部数据数：'+dbcount+'';
	    	var allcount = _getintvalue(v.taskparams.scene.alldatacount);
            if(allcount){
                infohtm += ', 过滤后的原创数据数：'+allcount+'';
            }
            infohtm += '<br/>';
	    }
        if(v.taskparams.scene.totalanalysiscount != undefined){
            var tas = _getintvalue(v.taskparams.scene.totalanalysiscount); 
	    	infohtm += '需分析数据数：'+tas+'';
            infohtm += '<br/>';
        }
    	if(v.taskparams.scene.api_queryid_count){
    		infohtm += '访问API(queryid)'+_getintvalue(v.taskparams.scene.api_queryid_count)+'次, 出错'+_getintvalue(v.taskparams.scene.api_queryid_errorcount)+', 花费'+_getintvalue(v.taskparams.scene.api_queryid_time)+'<br/>';
    	}
    	if(v.taskparams.scene.api_showuser_count){
    		infohtm += '访问API(抓取用户show_user)'+_getintvalue(v.taskparams.scene.api_showuser_count)+'次, 出错'+_getintvalue(v.taskparams.scene.api_showuser_errorcount)+', 花费'+_getintvalue(v.taskparams.scene.api_showuser_time)+'<br/>';
    	}
    	if(v.taskparams.scene.api_showstatus_count){
    		infohtm += '访问API(抓取微博show_status)'+_getintvalue(v.taskparams.scene.api_showstatus_count)+'次, 出错'+_getintvalue(v.taskparams.scene.api_showstatus_errorcount)+', 花费'+_getintvalue(v.taskparams.scene.api_showstatus_time)+'<br/>';
    	}
	    if(v.taskparams.scene.api_friends_count){
    		infohtm += '访问API(抓取关注friends)'+_getintvalue(v.taskparams.scene.api_friends_count)+'次, 出错'+_getintvalue(v.taskparams.scene.api_friends_errorcount)+', 花费'+_getintvalue(v.taskparams.scene.api_friends_time)+'<br/>';
    	}
		}
	    var solrerrorcount = v.taskparams.scene.solrerrorcount;//总失败条数
	    if(v.task == 8){
	    	var allcount = v.taskparams.urls ? v.taskparams.urls.length : v.taskparams.data.length;
	    	var datatype = v.taskparams.datatype ? v.taskparams.datatype : v.taskparams.urltype;
	    	var str1 = ""; 
	    	if(datatype == 'comment'){
	    		str1 = '接收到评论ID';
	    	}
	    	else if(datatype == 'weibo'){
	    		str1 = '接收到微博内容';
	    	}
	    	else if(datatype == 'url'){
	    		str1 = '接收到微博URL';
	    	}
	    	else{
	    		str1 = '接收到微博ID';
	    	}
	    	var dc = _getintvalue(v.taskparams.select_cursor);
	    	if(datatype == 'comment'){
	    		infohtm += str1+'：'+allcount+ '条, 已处理：'+dc+'. 数据库存在'+_getintvalue(v.taskparams.scene.exists_weibocount)+'条.';
	    	}
	    	else{
	    		infohtm += str1+'：'+allcount+ '条, 已处理：'+dc+'. 数据库存在'+_getintvalue(v.taskparams.scene.exists_weibocount)+'条,';
	    		infohtm += ' 已更新微博'+_getintvalue(v.taskparams.scene.update_weibocount)+'条. 数据库已存在'+_getintvalue(v.taskparams.scene.userexists_count)+'个用户,';
	    		infohtm += ' 新增用户'+_getintvalue(v.taskparams.scene.insert_user_count)+'个, 更新用户'+_getintvalue(v.taskparams.scene.update_user_count)+'个.';
	    	}
	    }
	    else if(v.task == config.TASK_IMPORTUSERID){
	    	var allcount = v.taskparams.data.length;
	    	var datatype = v.taskparams.datatype;
	    	var str1 = ""; 
	    	if(datatype == 'id'){
	    		str1 = '接收到用户ID';
	    	}
	    	else{
	    		str1 = '接收到用户昵称';
	    	}
	    	var dc = _getintvalue(v.taskparams.select_cursor);
	    	infohtm += str1+'：'+allcount+ '条, 已处理：'+dc+'条,';
	    	infohtm += ' 新增用户'+_getintvalue(v.taskparams.scene.insert_user_count)+'个, 更新用户'+_getintvalue(v.taskparams.scene.update_user_count)+'个.';
	    }
	    else if(!v.taskparams.scene.isremote){
			var datacount = v.datastatus ? v.datastatus : 0;
		    solrerrorcount = solrerrorcount ? solrerrorcount : 0;
		    var startindex = v.taskparams.startdataindex ? v.taskparams.startdataindex : 0;
		    var endindex = v.taskparams.enddataindex ? v.taskparams.enddataindex : 0;
		    var taskinfostr = (startindex == 0 && endindex == 0) ? "全部" : "起始："+startindex+',结束：'+endindex;
			infohtm += '任务需要处理的：'+taskinfostr+ ', 已处理：'+datacount+', 失败数：<span style="color:red">'+solrerrorcount+'</span>';
	    }
	  if(!v.taskparams.scene.isremote){
	    var resstr = "";
	    if(v.taskparams.scene.res_ip){
	    	resstr += "IP:"+v.taskparams.scene.res_ip.resource;
	    	resstr += ", appkey:"+v.taskparams.scene.res_ip.appkey;
	    }
	    if(v.taskparams.scene.res_acc){
	    	resstr += ", 帐号:"+v.taskparams.scene.res_acc.resource;
	    }
	    if(resstr != ""){
	    	infohtm += "<br/>当前资源："+resstr;
	    }
	  }
		if(v.taskparams.scene.status_desp != undefined && v.taskparams.scene.status_desp != ""){
			infohtm += "<br/>任务状态描述："+v.taskparams.scene.status_desp;
		}
		if(v.taskparams.scene.error_notext_datas != undefined && v.taskparams.scene.error_notext_datas.length > 0){
			if(v.task == 9){
			  infohtm += '<br/><span style="color:red;">用户不存在的:</span><br/>';
			}
			else{
			  infohtm += '<br/><span style="color:red;">微博不存在的:</span><br/>';
		  }
			for(var i=0; i< v.taskparams.scene.error_notext_datas.length; i++){
				var fixstr = (i == v.taskparams.scene.error_notext_datas.length-1) ? "" : ", ";
				infohtm += v.taskparams.scene.error_notext_datas[i] + fixstr;
			}
		}
		if(v.taskparams.scene.error_other_datas != undefined && v.taskparams.scene.error_other_datas.length > 0){
			infohtm += '<br/><span style="color:red;">发生其他错误的:</span><br/>';
			for(var i=0; i< v.taskparams.scene.error_other_datas.length; i++){
				var fixstr = (i == v.taskparams.scene.error_other_datas.length-1) ? "" : ", ";
				infohtm += v.taskparams.scene.error_other_datas[i] + fixstr;
			}
		}
	}

	if("scene" in task){
		infohtm += "<br/>运行结果：";
		if(task.scene.duplicate != undefined){
			infohtm += "重复任务个数："+task.scene.duplicate+", ";
		}
		if(task.scene.taskadded != undefined){
			infohtm += "新增任务个数："+task.scene.taskadded+"<br/>";
		}
	}

	return infohtm;
}
function printStat(stat)
{
	var statstr = "";
	if(stat.count != undefined){
		statstr += "抓取条数："+stat.count+"<br/>";
	}
	if(stat.child != undefined){
		statstr += "子任务数："+stat.child+"<br/>";
	}
	if(stat.time != undefined){
		statstr += "总时间："+stat.time+"毫秒 ";
	}
	if(stat.avg_time != undefined){
		statstr += "平均时间："+stat.avg_time+"毫秒 ";
	}
	if(stat.min_time != undefined){
		statstr += "最小时间："+stat.min_time+"毫秒 ";
	}
	if(stat.max_time != undefined){
		statstr += "最大时间："+stat.max_time+"毫秒<br/>";
	}
	if(stat.svr_time != undefined){
		statstr += "服务器总时间："+stat.svr_time+"毫秒 ";
	}
	if(stat.avg_svr_time != undefined){
		statstr += "服务器平均时间："+stat.avg_svr_time+"毫秒 ";
	}
	if(stat.min_svr_time != undefined){
		statstr += "服务器最小时间："+stat.min_svr_time+"毫秒 ";
	}
	if(stat.max_svr_time != undefined){
		statstr += "服务器最大时间："+stat.max_svr_time+"毫秒<br/>";
	}
	return statstr;
}
var tenantArr = [];
function getTenantName(tenantid){
	var tenantname = "";
	if(tenantid != null){
		var find = false;
		$.each(tenantArr, function(ti, titem){
			if(titem.tenantid == tenantid){
				tenantname = titem.tenantname;
				find = true;
				return false;
			}
		});
		if(!find){
			$.ajax({
				type: "GET",
				dataType: "json",
				async:false,
				url: config.modelUrl+"tenant_user_model.php",
				data: {tid:tenantid, type:"gettenantbyid"},
				success:function(data){
					if(data.totalcount > 0){
						$.each(data.children, function(di, ditem){
							tenantArr.push({tenantid:ditem.tenantid,tenantname:ditem.tenantname});
							tenantname = ditem.tenantname;
						});
					}
					else{
						tenantArr.push({tenantid:tenantid,tenantname:""});
					}
				}
			});
		}
	}
	return tenantname;
}
var userArr = [];
function getUserName(userid){
	var username = "";
	if(userid != null){
		var find = false;
		$.each(userArr, function(ti, titem){
			if(titem.userid == userid){
				username = titem.username;
				find = true;
				return false;
			}
		});
		if(!find){
			$.ajax({
				type: "GET",
				dataType: "json",
				async:false,
				url: config.modelUrl+"user_model.php",
				data: {uid:userid, type:"getuserbyid"},
				success:function(data){
					if(data.totalcount > 0){
						$.each(data.children, function(di, ditem){
							userArr.push({userid:ditem.userid,username:ditem.username});
							username = ditem.username;
						});
					}
				}
			});
		}
	}
	return username;
}
