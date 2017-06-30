//为图标和列表添加标题
function addTabTitle(render, msg)
{
	$("."+render).html(msg);
}
/**
 * 从模型的filtervalue数组 中删除指定名称的 filtervalue
 * @param filtername
 * @param filtervalueArr 
 */
function deleteFilterValue(filtername,filtervalueArr){
	for(var i = filtervalueArr.length-1; i>-1; i--){
		if(filtervalueArr[i].fieldname == filtername){
			filtervalueArr.splice(i,1);
		}
	}
}
//"不"按钮点击事件
function excludeItemFun(ele){
	var sltor = $(ele).next();
	var ex = $(sltor).attr("exclude");
	if(ex == undefined){
		$(sltor).attr("exclude", 1);
		$(ele).css("color", "#000000");
	}
	else{
		$(sltor).removeAttr("exclude");
		$(ele).css("color", "#DADADA");
	}
}
//根据情感值,返回情感文本
function emoval2text(m){
	var m1 = parseInt(m, 10);
	var emotionL = "";
	switch (m1) {
		case 1:
			emotionL = "反对";
			break;
		case 2:
			emotionL = "负面";
			break;
		case 3:
			emotionL = "中立";
			break;
		case 4:
			emotionL = "正面";
			break;
		case 5:
			emotionL = "赞赏";
			break;
		default:
			break;
	}
	return emotionL;
}
function emotype2text(emotype){
	var emotxtArr = [];
	if(emotype.split(",").length==5){
		emotxtArr.push("全部");
	}
	else{
		$.each(emotype.split(","), function(k, v){
			var et = "";
			if(v == "*"){
				et = "全部";
			}
			else{
				et = emoval2text(v);
			}
		emotxtArr.push(et);
		});
	}
	return emotxtArr;
}
//将微博的url转换成微博mid
function weiboUrl2mid(url, sourceid){
	if(url == undefined || url == ""){
		return "";
	}
	switch(parseInt(sourceid,10)){
		case 1: //新浪
			if(url.substr(url.length-1,1) == '/'){
				url = url.substr(0,substr.length - 1);//去除最后一个斜杠
			}
			 var lasti = url.lastIndexOf('/');
			 if(lasti > -1){
				 var urlid = url.substring(lasti+1);
				 return WeiboUtility.url2mid(urlid);
			 }
			 else{
				 return "";
			 }
		case 2: //腾讯
			return "";
		default:
			return "";
	}
}
//timestamp //时间戳 秒
//返回 以秒为单位时间戳
function strtotime(time, timestamp){
//trace("传入日期 " + formatTime("yyyy-MM-dd hh:mm:ss", timestamp));
	var dateObj;
	if(timestamp != undefined){
		dateObj = new Date(timestamp * 1000);
	}
	else{
		dateObj = new Date();
	}
	var returnValue = "";
	if(time == "now"){
		returnValue = Date.parse(dateObj);
	}
	else{
		var timeArr = time.split(" ");
		var timegap = timeArr[1];
		var gaptype = "";
		if(timegap.indexOf("year") != -1){
			gaptype = 'y';
		}
		else if(timegap.indexOf("month") != -1 || timegap.indexOf("q") != -1){
			gaptype = 'm';
		}
		else if(timegap.indexOf("day") != -1){
			gaptype = 'd';
		}
		else if(timegap.indexOf("hour") != -1){
			gaptype = 'h';
		}
		else if(timegap.indexOf("minute") != -1){
			gaptype = 'n';
		}
		else if(timegap.indexOf("second") != -1){
			gaptype = 's';
		}
		else if(timegap.indexOf("week") != -1){
			gaptype = 'w';
		}
		//trace("1736 dateObj: "+dateObj+ " gaptype: "+ gaptype+" add: "+timeArr[0]);
		var retObj = dateObj.dateAdd(gaptype, timeArr[0]);
		returnValue = Date.parse(retObj);
	}
	return returnValue/1000;
}
function timeSelectOption(optArr, slted){
	var option = "";
	if(optArr == undefined){
		option = '<option value="year">年</option><option value="month">月</option><option value="week">周</option><option value="day" selected="selected">天</option><option value="hour">时</option><option value="minute">分</option><option value="second">秒</option>';
	}
	else{
		if(optArr.length > 0){
			$.each(optArr,function(k, v){
				var slt = "";
				if(slted == undefined){
					if(k == 0){
						slt = "selected='selected'";
					}
				}
				else{
					if(v.value == slted){
						slt = "selected='selected'";
					}
				}
				option +="<option value='"+v.value+"' "+slt+">"+v.text+"</option>";
			});
		}
	}
	return option;
}
//样式 当两组时间都显示时,显示其间隔线
//forcedisplay 控制强制显示 间隔符
function isShowSplitmark(startsltor, endsltor, markidsltor, forcedisplay){
	if(forcedisplay){
		$(markidsltor).css("display", "");
	}
	else{
		var stxt = $(startsltor).text();
		var etxt = $(endsltor).text();
		if(stxt != "" && etxt != ""){
			$(markidsltor).css("display", "");
		}
		else{
			$(markidsltor).css("display", "none");
		}
	}
}
function setTimeSelectMsg(prefix, msg1, msg2, forcedisplay){
	if(msg2 === undefined){
		$("#"+prefix+"_timeselect_start").html("<span style='color:red'>"+msg1+"</span>");
		$("#"+prefix+"_timeselect_end").text("");
		isShowSplitmark("#"+prefix+"_timeselect_start", "#"+prefix+"_timeselect_end", "#"+prefix+"_splitmark");
	}
	else{
		var tmpmsg = msg1 == null ? "" : msg1;
		$("#"+prefix+"_timeselect_start").text(tmpmsg);
		$("#"+prefix+"_timeselect_end").html(msg2);
		isShowSplitmark("#"+prefix+"_timeselect_start", "#"+prefix+"_timeselect_end", "#"+prefix+"_splitmark", forcedisplay);
	}
};
function changeTimeBorderColor(targetor, timetype){
	$("#"+targetor +" div[timetype]").css({"border": "0"});
	$("#"+targetor +" div[timetype="+timetype+"]").css({"border": "1px solid #ff0000"});

	$("#"+targetor +" div[timetype!="+timetype+"] input").attr("checked", false);
};
function setThisTime(selector, timestate){
	$(selector).css("display", "");
	if(timestate == "beginning"){
		$(selector).text('00时00分00秒');
	}
	else if(timestate == "now"){
		//给当前时间赋值
		var timenow = formatTime('hh时mm分ss秒', strtotime("now"));
		$(selector).text(timenow);
	}
	$(selector).attr("timestate", timestate);
};
//dateunit默认为空,只有datestate为beginning时才需要dateunit获得年,月,日的起始和结束
function getStateTime(startval,endval, datestate, dateunit, timestate){
	var finalret = {};
	if(!isNaN(parseInt(startval, 10)) && !isNaN(parseInt(endval, 10))){
		var fmttimebeg = "";
		var fmttimeend = "";

		if(timestate == "beginning"){
			fmttimebeg= "00:00:00";
			fmttimeend= "23:59:59";
		}
		else{
			fmttimebeg= "hh:mm:ss";
			fmttimeend= "hh:mm:ss";
		}

		if(datestate == "beginning"){
			var formatstrbeg = "";
			var formatstrend = "";
			switch(dateunit){
				case "year":
					formatstrbeg = "yyyy-01-01 "+fmttimebeg+"";
					formatstrend = "yyyy-12-31 "+fmttimeend+"";
					break;
				case "month":
					formatstrbeg = "yyyy-MM-01 "+fmttimebeg+"";
					//处理月最后一天 
					var lastday = getLastDay(endval);
					formatstrend = "yyyy-MM-"+lastday+" "+fmttimeend+"";
					break;
				case "week":
					//处理星期
					var s = getWeek(startval);
					var mon = formatTime("yyyy-MM-dd "+fmttimebeg+"", s.monday);
					var e = getWeek(endval);
					var sun = formatTime("yyyy-MM-dd "+fmttimeend+"", e.sunday);
					formatstrbeg = mon;
					formatstrend = sun;
					break;
				default:
					formatstrbeg = "yyyy-MM-dd "+fmttimebeg+"";
					formatstrend = "yyyy-MM-dd "+fmttimeend+"";
					break;
			}
			finalret.start = formatTime(formatstrbeg, startval);
			finalret.end = formatTime(formatstrend, endval);
		}
		else if(datestate == "now"){
			finalret.start = formatTime('yyyy-MM-dd '+fmttimebeg+'', startval);
			finalret.end = formatTime('yyyy-MM-dd '+fmttimeend+'', endval);
		}
		finalret.start = getTimeSec(finalret.start);
		finalret.end = getTimeSec(finalret.end);
	}

	return finalret;
}
function setDateSelect(startsltor, startval, endsltor, endval, datestate, dateunit, timestate, splitmarksltor){
	var finalret = getStateTime(startval,endval, datestate, dateunit, timestate);
	$(startsltor).text(formatTime('yyyy-MM-dd hh:mm:ss', finalret.start));
	$(endsltor).text(formatTime('yyyy-MM-dd hh:mm:ss', finalret.end));
	isShowSplitmark(startsltor, endsltor, splitmarksltor);
};
//相对今天 当前时间或开始时间标签点击事件
function nearlytimeTagClick(prefix, timestate){
	//var timestate = $(this).attr("timestate");
	var ntv = $("#"+prefix+"_nearlytime").val();
	var ntgap = $("#"+prefix+"_nearlytime_gap").val();
	var nts = strtotime("-"+ntv+" "+ntgap+"");
	var nte = strtotime("now");

	if(timestate == "now"){
		setThisTime("#"+prefix+"_nearlytime_thistime", "beginning");
		setDateSelect("#"+prefix+"_timeselect_start", nts, "#"+prefix+"_timeselect_end", nte, "now", "", "beginning", "#"+prefix+"_splitmark"); 
	}
	else{
		setThisTime("#"+prefix+"_nearlytime_thistime", "now");
		setDateSelect("#"+prefix+"_timeselect_start", nts, "#"+prefix+"_timeselect_end", nte, "now", "", "now", "#"+prefix+"_splitmark"); 
	}
}
//拆分日期字符串,返回对应的数字和时间单位
function getGapNumDate(gap){
	var gapObj = {};
	if(gap != undefined){
		if(gap.indexOf("year") != -1){
			gapObj.num = gap.substr(0, gap.length - 4);
			gapObj.unit = "year";
		}
		else if(gap.indexOf("month") != -1){
			gapObj.num = gap.substr(0, gap.length - 5);
			gapObj.unit = "month";
		}
		else if(gap.indexOf("week") != -1){
			gapObj.num = gap.substr(0, gap.length - 4);
			gapObj.unit = "week";
		}
		else if(gap.indexOf("day") != -1){
			gapObj.num = gap.substr(0, gap.length - 3);
			gapObj.unit = "day";
		}
		else if(gap.indexOf("hour") != -1){
			gapObj.num = gap.substr(0, gap.length - 4);
			gapObj.unit = "hour";
		}
		else if(gap.indexOf("minute") != -1){
			gapObj.num = gap.substr(0, gap.length - 6);
			gapObj.unit = "minute";
		}
		else if(gap.indexOf("second") != -1){
			gapObj.num = gap.substr(0, gap.length - 6);
			gapObj.unit = "second";
		}
		else{
			gapObj.num = gap;
			gapObj.unit = "";
		}
	}
	if(gapObj.num == ""){
		gapObj.num = 1;
	}
	return gapObj;
}
function nearlytimeShortCutBtnClick(prefix, nearlytype){
	var nt = getGapNumDate(nearlytype);
	$("#"+prefix+"_nearlytime").val(nt.num);
	$("#"+prefix+"_nearlytime_gap").val(nt.unit);

	setThisTime("#"+prefix+"_nearlytime_thistime", "now");

	var nts = strtotime("-"+nt.num+" "+nt.unit+"");
	var nte = strtotime("now");
	setDateSelect("#"+prefix+"_timeselect_start", nts, "#"+prefix+"_timeselect_end", nte, "now", "", "now", "#"+prefix+"_splitmark"); 
}
function nearlytimeInputChange(prefix){
	//给标题时间赋值
	var ntv = $("#"+prefix+"_nearlytime").val();
	var ntgap = $("#"+prefix+"_nearlytime_gap").val();
	var nts = strtotime("-"+ntv+" "+ntgap+"");
	var nte = strtotime("now");
	if(ntv != ""){
		//给当前时间赋值
		setThisTime("#"+prefix+"_nearlytime_thistime", "now");
		setDateSelect("#"+prefix+"_timeselect_start", nts, "#"+prefix+"_timeselect_end", nte, "now", "", "now", "#"+prefix+"_splitmark"); 

		$("input[name="+prefix+"_nearlytime_shortcut][nearly]").attr("checked", false);
		$("input[name="+prefix+"_nearlytime_shortcut][nearly="+ntv+ntgap+"]").attr("checked", true);
	}
	else{
		$("#"+prefix+"_nearlytime").focus();
	}
}
function getnearlytimeValue(prefix){
	value = {};
	value.start = $("#"+prefix+"_nearlytime").val();
	value.startgap = $("#"+prefix+"_nearlytime_gap").val();
	value.timestate = $("#"+prefix+"_nearlytime_thistime").attr("timestate");
	return value;
}
function beforetimeTagTimeClick(prefix, timestate){
	var bs = $("#"+prefix+"_beforetime_start").val();
	var be = $("#"+prefix+"_beforetime_end").val();
   var bgap = $("#"+prefix+"_beforetime_end_gap").val();

   var bstime = strtotime("-"+bs+" "+bgap+"");
   var betime = strtotime("-"+be+" "+bgap+"");
   //var timestate = $(this).attr("timestate");
   var datestate = $("#"+prefix+"_beforetime_thisdate").attr("datestate");
   if(timestate == "now"){
	   setThisTime("#"+prefix+"_beforetime_thistime", "beginning");
	   setDateSelect("#"+prefix+"_timeselect_start", bstime, "#"+prefix+"_timeselect_end", betime, datestate, bgap, "beginning", "#"+prefix+"_splitmark"); 
   }
   else{
	   setThisTime("#"+prefix+"_beforetime_thistime", "now");
	   setDateSelect("#"+prefix+"_timeselect_start", bstime, "#"+prefix+"_timeselect_end", betime, datestate, bgap, "now", "#"+prefix+"_splitmark"); 
   }
}
function setThisDate(selector, datestate, dateunit){
	$(selector).css("display", "");
	var formatstrbeg = "";
	var formatstrnow = "";
	switch(dateunit){
		case "year":
			formatstrbeg = "1月1号";
			formatstrnow = "MM月dd号";
			break;
		case "month":
			formatstrbeg = "1号";
			formatstrnow = "dd号";
			break;
		case "week":
			formatstrbeg = "周1";
			formatstrnow = "周w";
			break;
		default:
			break;
	}
	if(datestate == "beginning"){
		$(selector).text(formatstrbeg);
	}
	else if(datestate == "now"){
		var timenow = formatTime(formatstrnow, strtotime("now"));
		if(timenow == "周0"){
			timenow = "周日";
		}
		$(selector).text(timenow);
	}
	$(selector).attr("datestate", datestate);
};
function beforetimeTagDateClick(prefix, datestate){
   var bs = $("#"+prefix+"_beforetime_start").val();
   var be = $("#"+prefix+"_beforetime_end").val();
   var bgap = $("#"+prefix+"_beforetime_end_gap").val();

   var bstime = strtotime("-"+bs+" "+bgap+""); //start time;
   var betime = strtotime("-"+be+" "+bgap+""); //entime
   //var datestate = $(this).attr("datestate");
   var timestate = $("#"+prefix+"_beforetime_thistime").attr("timestate");
   if(datestate == "now"){
	   setThisDate("#"+prefix+"_beforetime_thisdate", "beginning", bgap);
	   setDateSelect("#"+prefix+"_timeselect_start", bstime, "#"+prefix+"_timeselect_end", betime, "beginning", bgap, timestate, "#"+prefix+"_splitmark");
   }
   else{
	   setThisDate("#"+prefix+"_beforetime_thisdate", "now", bgap);
	   setDateSelect("#"+prefix+"_timeselect_start", bstime, "#"+prefix+"_timeselect_end", betime, "now", bgap, timestate, "#"+prefix+"_splitmark");
   }
}
function beforetimeInputChange(prefix){
	var bs = $("#"+prefix+"_beforetime_start").val();
	var be = $("#"+prefix+"_beforetime_end").val();
	var bgap = $("#"+prefix+"_beforetime_end_gap").val();

	if(bs != "" && be != ""){
		var bstime = strtotime("-"+bs+" "+bgap+"");
		var betime = strtotime("-"+be+" "+bgap+"");
		setDateSelect("#"+prefix+"_timeselect_start", bstime, "#"+prefix+"_timeselect_end", betime, "now", "", "now", "#"+prefix+"_splitmark"); 
		//给当前时间赋值
		setThisTime("#"+prefix+"_beforetime_thistime", "now");
		//给当前日期赋值
		if(bgap == "year" || bgap == "month" || bgap == "week"){
			setThisDate("#"+prefix+"_beforetime_thisdate", "now", bgap);
		}
		else{
			$("#"+prefix+"_beforetime_datetime").css("display", "none");
		}
	}
	else{
		if(be == ""){
			$("#"+prefix+"_beforetime_end").focus();
		}
		if(bs == ""){
			$("#"+prefix+"_beforetime_start").focus();
		}
	}
}
function getbeforeTimeValue(prefix){
	var value = {};
	value.start = $("#"+prefix+"_beforetime_start").val();
	value.startgap = $("#"+prefix+"_beforetime_end_gap").val();
	value.end = $("#"+prefix+"_beforetime_end").val();
	value.endgap = $("#"+prefix+"_beforetime_end_gap").val();
	value.timestate = $("#"+prefix+"_beforetime_thistime").attr("timestate");
	value.datestate = $("#"+prefix+"_beforetime_thisdate").attr("datestate");
	return value;
}
//获取时间单位
//dateunit 起始时间单位
//order 顺序标记, 返回比起始单位大的单位时 包含此单位 ,返回小的单位时根据参数 containdown 包含, down不包含 
function dateUnitDisplay(dateunit, order){
	var dateUnit = [{value:"year", text:"年"}, {value:"month", text:"月"}, {value:"week", text:"周"} , {value:"day", text:"天"}, {value:"hour", text:"时"}, {value:"minute", text:"分"}, {value:"second", text:"秒"}];
	var pos;
	$.each(dateUnit, function(k, v){
		if(dateunit == v.value){
			pos = k;	
			return false;
		}
	});
	var retArr = [];
	if(order == "up"){
		for(var i = 0; i<= pos; i++){
			retArr.push(dateUnit[i]); 
		}
	}
	else{
		var su;
		if(order == "down"){
			su = pos + 1;
		}
		else if(order == "containdown"){
			su = pos;
		}
		for(var j = su; j< dateUnit.length; j++){
			retArr.push(dateUnit[j]); 
		}
	}
	return retArr;
}
function untiltimeShortCutBtnClick(prefix, until, type){
	if(until != undefined){
		var nt = getGapNumDate(until);
		var uv = nt.num;
		var ug = nt.unit;
		$("#"+prefix+"_untiltime_"+type+"").val(uv);
		$("#"+prefix+"_untiltime_"+type+"_gap").val(ug);
		$("#"+prefix+"_untiltime_"+type+"_to").val(1);
		$("#"+prefix+"_untiltime_"+type+"_gap").change();
	}
}
function untiltimeGapChange(prefix, usgval, item){
	//var usgval = $(this).val();
	var unitArr = dateUnitDisplay(usgval, "containdown");
	var opthtml = timeSelectOption(unitArr, usgval);
	$("#"+prefix+"_untiltime_"+item+"_this_gap").empty().append(opthtml);
	var s = $("#"+prefix+"_untiltime_"+item+"").val();
	var sg = $("#"+prefix+"_untiltime_"+item+"_gap").val();
	$("input[name="+prefix+"_untiltime_"+item+"_shortcut][untiltime="+item+"]").attr("checked", false);
	$("input[name="+prefix+"_untiltime_"+item+"_shortcut][untiltime="+item+"][until"+item+"="+s+sg+"]").attr("checked", true);

	var tounitArr = dateUnitDisplay(usgval, "down");
	var toopthtml = timeSelectOption(tounitArr, usgval);
	$("#"+prefix+"_untiltime_"+item+"_to_gap").empty().append(toopthtml);
}
function untiltimeInputChange(prefix, item){
	var s = $("#"+prefix+"_untiltime_"+item+"").val();
	var sg = $("#"+prefix+"_untiltime_"+item+"_gap").val();
	$("input[name="+prefix+"_untiltime_"+item+"_shortcut][untiltime="+item+"]").attr("checked", false);
	$("input[name="+prefix+"_untiltime_"+item+"_shortcut][untiltime="+item+"][until"+item+"="+s+sg+"]").attr("checked", true);
}
function untiltimeValueChange(prefix){
	var s = $("#"+prefix+"_untiltime_start").val();
	var sg = $("#"+prefix+"_untiltime_start_gap").val();
	var sthisg = $("#"+prefix+"_untiltime_start_this_gap").val();
	var st = $("#"+prefix+"_untiltime_start_to").val();
	var stg = $("#"+prefix+"_untiltime_start_to_gap").val();

	var e = $("#"+prefix+"_untiltime_end").val();
	var eg = $("#"+prefix+"_untiltime_end_gap").val();
	var ethisg = $("#"+prefix+"_untiltime_end_this_gap").val();
	var et = $("#"+prefix+"_untiltime_end_to").val();
	var etg = $("#"+prefix+"_untiltime_end_to_gap").val();

	if(s!="" && st!="" && e!="" && et!=""){
		var us = sinceToThis(s, sg, sthisg, st, stg, "beginning");
		var ue = sinceToThis(e, eg, ethisg, et, etg, "ending");
		setDateSelect("#"+prefix+"_timeselect_start", us, "#"+prefix+"_timeselect_end", ue, "now", "", "now", "#"+prefix+"_splitmark"); 
	}
	else{
		if(et == ""){
			$("#"+prefix+"_untiltime_end_to").focus();
		}
		if(e == ""){
			$("#"+prefix+"_untiltime_end").focus();
		}
		if(st == ""){
			$("#"+prefix+"_untiltime_start_to").focus();
		}
		if(s == ""){
			$("#"+prefix+"_untiltime_start").focus();
		}
	}
}
function getuntiltimeValue(prefix){
	value = {};
	value.start = {};
	value.start.start = $("#"+prefix+"_untiltime_start").val();
	value.start.startgap = $("#"+prefix+"_untiltime_start_gap").val();
	value.start.startthisgap = $("#"+prefix+"_untiltime_start_this_gap").val();
	value.start.startto = $("#"+prefix+"_untiltime_start_to").val();
	value.start.starttogap = $("#"+prefix+"_untiltime_start_to_gap").val();
	value.end = {};
	value.end.end = $("#"+prefix+"_untiltime_end").val();
	value.end.endgap = $("#"+prefix+"_untiltime_end_gap").val();
	value.end.endthisgap = $("#"+prefix+"_untiltime_end_this_gap").val();
	value.end.endto = $("#"+prefix+"_untiltime_end_to").val();
	value.end.endtogap = $("#"+prefix+"_untiltime_end_to_gap").val();
	return value;
}
function getMaxDate(thisgap, togap, untiltimes){
	var maxsto;
	var base = 1;
	switch(thisgap){
		case "year":
			base = 366;
			break;
		case "month":
			base  = getLastDay(untiltimes);
			break;
		case "week":
			base = 7;
			break;
		default:
			break;
	}
	switch(togap){
		case "month":
			maxsto = 12;
			break;
		case "day":
			maxsto = base;
			break;
		case "week":
			if(thisgap == "year"){
				maxsto = 52;
			}
			else if(thisgap == "month"){
				maxsto = 4;
			}
			break;
		case "hour":
			maxsto = base * 24;
			break;
		case "minute":
			if(thisgap == "hour"){
				maxsto = base * 60;
			}
			else{
				maxsto = base * 24 * 60;
			}
			break;
		case "second":
			if(thisgap == "hour"){
				maxsto = base * 60 * 60;
			}
			else if(thisgap == "minute"){
				maxsto = base * 60;
			}
			else{
				maxsto = base * 24 * 60 * 60;
			}
			break;
		default:
			break;
	}
	return maxsto;
}

/*
 * var us = sinceToThis(utsv, utsgap, utsthisgap, utsto, utstogap, "beginning");
 * var ue = sinceToThis(utev, utegap, utethisgap, uteto, utetogap, "ending");
 * */
//至今? (年|月|日|时|分|秒 周) 该(年|月|日|时|分|秒 周) 第? (年|月|日|时|分|秒 周)
function sinceToThis(sv, sgap, thisgap, sto, togap, timestate){
	var untiltimes = strtotime("-"+sv+" "+sgap+""); //至今? 年|月|日|时|分|秒 周
	//trace("至今 "+sv+" "+sgap+" 日期"+formatTime("yyyy-MM-dd hh:mm:ss", untiltimes));
	var formatstr = "";
	var maxsto;
	switch(thisgap){
		case "year":
			formatstr = "yyyy-01-01 00:00:00";
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			break;
		case "month":
			formatstr = "yyyy-MM-01 00:00:00";
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			break;
		case "day":
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			formatstr = "yyyy-MM-dd 00:00:00";
			break;
		case "hour":
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			formatstr = "yyyy-MM-dd hh:00:00";
			break;
		case "minute":
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			formatstr = "yyyy-MM-dd hh:mm:00";
			break;
		case "second":
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			formatstr = "yyyy-MM-dd hh:mm:ss";
			break;
		case "week":
			maxsto = getMaxDate(thisgap, togap, untiltimes);
			var wtime = strtotime("-"+sv+" "+sgap+"");
			var s = getWeek(wtime);
			var mon = formatTime("yyyy-MM-dd 00:00:00", s.monday);
			formatstr = mon; 
			break;
		default:
			formatstr = "yyyy-MM-dd hh:mm:ss";
			break;
	}
	var str = formatTime(formatstr, untiltimes); //得到该 年 月 天 ...

	//trace("该 "+thisgap+" 日期"+str);
	if(sto > maxsto){
		sto = maxsto;
	}
	//结束时间 需要到对应时间单位的最后一秒, 当为end时对应时间单位多加 1 ,返回结果中减去对应的 时间单位
	if(timestate == "beginning"){
		sto = sto - 1;
	}
	var ret = strtotime("+"+sto+" "+togap+"", getTimeSec(str));  //第? 年|月|日|时|分|秒 周
	//减去对应时间单位
	if(timestate == "ending"){
		return ret - 1;  //减去1秒得到上一单位的最后一秒
	}
	else{
		return ret;
	}
}
function getTimeUintAlias(unit){
	var retunit = "";
	switch(unit){
		case "month":
			retunit = "月";
			break;
		case "day":
			retunit = "日";
			break;
		case "hour":
			retunit = "时";
			break;
		case "minute":
			retunit = "分";
			break;
		case "weekday":
			retunit = "星期";
			break;
		case "week":
			retunit = "第";
			break;
		default:
			break;
	}
	return retunit;
}
function getTimeValAlias(val, item){
	var str = "";
	if(val == -1){
		str = "最后一";
	}
	else{
		if(item == "weekday" || item == 3){
			switch(parseInt(val, 10)){
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					str = Utils.numberToChinese(parseInt(val, 10));
					break;
				case 7:
					str = "日";
					break;
				default:
					break;
			}
		}
		else{
			str = val;
		}
	}
	return str;
}
function getDesHtml(crontime){
	var des = getCrontimeDes(crontime); 
	var fontSize = 12;
	var tdwidth = 100;
	var partdes = getPartDes(des, tdwidth, fontSize); 
	var deshtml = "";
	if(partdes.truncation){
		deshtml = "<a title='"+des+"'>"+partdes.retstr+"</a>"; 
	}
	else{
		deshtml =  des;
	}
	return deshtml;
};
//在1,2月的4号6时3分重复
//每月的4号6时3分重复
//每月的第2,3周,周三 6时 3分重复
function getCrontimeDes(crontime){
	var retstr = "";
	for(var item in crontime){
		if(item == "repeat"){
			var repeatalias = "";
			switch(parseInt(crontime[item], 10)){
				case 1:
					repeatalias = "每分钟";
					break;
				case 2:
				//case "hourly":
					repeatalias = "每小时";
					break;
				case 3:
				//case "daily":
					repeatalias = "每天";
					break;
				case 4:
				//case "weekly":
					repeatalias = "每周";
					break;
				case 6:
				case 7:
				//case "monthly":
				//case "monthweek":
					repeatalias = "每月";
					break;
				default:
					break;
			}
			retstr += "重复类型:"+repeatalias+" \n";
		}
		if(crontime[item] != null && crontime[item].length > 0){
			if(getTimeUintAlias(item) != ""){ //此判断去除用单位描述的字段
				var need = false;
				/*
				if(item == "weekday"){
					retstr += " "+getTimeUintAlias(item);
				}
				if(item == "week"){
					retstr += " "+getTimeUintAlias(item)+" ";
				}
				*/
				var needflag = true;
				if(item == "week"){
					var tmp = crontime[item];
					if(tmp.length == 1 && tmp[0] == -1){
						needflag = false;
					}
				}
				if(needflag){
					retstr += getTimeUintAlias(item);
				}
				if(item != "weekday" && item != "week"){
					retstr += ":";
				}
				retstr += getTimeValAlias(crontime[item][0], item);
				var sarr = [];
				for(var i=1;i<crontime[item].length;i++){
					var diff = crontime[item][i] - crontime[item][i-1];
					if(diff == 1){
						var str = "~";
						if(!sarr.inArray(str)){
							sarr.push(str);
							retstr += "~";
						}
						if(i == crontime[item].length-1){
							retstr += getTimeValAlias(crontime[item][i], item);
						}
						need = true;
					}
					else{
						sarr.length = 0;
						if(need){
							retstr += getTimeValAlias(crontime[item][i-1], item);
							need = false;
						}
						retstr += ", "+getTimeValAlias(crontime[item][i], item);
					}
				}
				/*
				if(item == "weekday"){
					retstr += " ";
				}
				if(item != "weekday" && item != "week"){
					retstr += getTimeUintAlias(item) +"\n";
				}
				*/
				if(item == "week"){
					retstr += "个";
				}
				else{
					retstr += "\n";
				}
			}
		}
	}
	return retstr;
}
//字符串大于最大长度时截断
//return 是否截断 true/false  , 字符串
function getPartDes(des, maxwidth, fontSize){
	var retobj = {};
	var despxlen = getStringPx(des, fontSize); //字体大小12
	retobj.truncation = false;
	retobj.retstr = "";
	if(despxlen > maxwidth){ //表格的宽度
		var tmpstrlen = 0;
		var strcount = 0;
		var availtdwidth = maxwidth-15; //减去三个.的宽度
		for(var d=0;d<des.length;d++){
			tmpstrlen += getStringPx(des.charAt(d), fontSize);
			if(tmpstrlen < availtdwidth){ //
				strcount++; //计算截断后剩余的字符个数
			}
			else{
				break;
			}
		}
		retobj.truncation = true;
		retobj.retstr = des.substr(0,strcount-1) + "...";
	}
	else{
		retobj.truncation = false;
		retobj.retstr = des;
	}
	return retobj;
}
function getGaptext(gap){
	var gaptxt = "";
	switch(gap){
		case "year":
		case "y":
			gaptxt = "年";
			break;
		case "month":
		case "m":
			gaptxt = "月";
			break;
		case "week":
		case "w":
			gaptxt = "周";
			break;
		case "day":
		case "d":
			gaptxt = "天";
			break;
		case "hour":
		case "h":
			gaptxt = "小时";
			break;
		case "minute":
			gaptxt = "分";
			break;
		case "second":
			gaptxt = "秒";
			break;
		default:
			break;
	}
	return gaptxt;
}
//filter字段名和facet字段名的转换,不包括地区,地区需要根据code进行分类
function getFacetname(field){
	var fieldname = "";
    switch(field){
        case "searchword":
        case "keyword":
            fieldname = "text";
			break;
        case "verifiedreason":
            fieldname = "verified_reason";
			break;
        case "hostdomain":
            fieldname = "host_domain";
			break;
        case "weibotopic":
            fieldname = "wb_topic";
			break;
        case "weibotopickeyword":
			fieldname = "wb_topic_keyword";
			break;
        case "weibotopiccombinword":
            fieldname = "wb_topic_combinWord";
			break;
        case "topic":
            fieldname = "combinWord";
			break;
		case "weiboid":
			fieldname = "id";
			break;
		case "father_id":
			fieldname = "father_id";
			break;
		case "oristatus":
			fieldname = "retweeted_status";
			break;
		case "repostsnum":
			fieldname = "reposts_count";
			break;
		case "commentsnum":
			fieldname = "comments_count";
			break;
		case "createdtime":
			fieldname = "created_at";
			break;
		case "weibotype":
			fieldname = "content_type";
			break;
		case "username":
			fieldname = "screen_name";
			break;
		case "verified":
			fieldname = "verify";
			break;
		case "haspicture":
			fieldname = "has_picture";
			break;
		default:
			fieldname = field;
			break;
    }
	return fieldname;
}
function getRetweetDisplayName(field, filter){
	var ret = "";
	if(field.indexOf("retweeted_") != -1){
		var tmpfield = field.split("retweeted_");
		if(tmpfield.length > 1){
			var tmpret = getDisplayName(tmpfield[1], filter);
			if(tmpret != ""){
				ret = "原创"+tmpret;
			}
		}
	}
	else{
		ret = getDisplayName(field, filter);
	}
	return ret;
}
function getLogicExpDes(logicobj, elecondition){
	var retdes = "";
	var retarr = [];
	var retconds = [];
	var viewopt = "";
	for(var item in logicobj){
		if(item == "bop"){
			switch(logicobj[item]){
				case "&&":
					viewopt = "并且";
					color = "#ff0000";
					break;
				case "||":
					viewopt = "或";
					color = "#00ff00";
					break;
				case "!":
					viewopt = "非";
					color = "#0000ff";
					break;
				case "N/A":
					viewopt = "";
					color = "#CCCCCC";
					break;
				default:
					break;
			}	
		}
		else{
			if(item != "type"){
				if("bop" in logicobj[item]){
					retarr.push(getLogicExpDes(logicobj[item], elecondition));
				}
				else{
					var tmp = "<span style='border:1px "+color+" solid;padding:2px;margin:2px;display:inline-block;'>"+getCondExpDes(logicobj[item], elecondition)+"</span>";
					retarr.push(tmp);
				}
			}
		}
	}
	//retdes = retarr.join(viewopt);
	var pre = viewopt == "非" ? viewopt : ""; 
	if(pre != ""){
		retdes = "<span style='border:1px #0000ff solid;padding:2px;margin:2px;display:inline-block;'>"+pre+"<span style='border:1px #A6C9E2 solid;padding:2px;margin:2px;display:inline-block;'>"+retarr.join(viewopt)+"</span></span>";
	}
	else{
		if(retarr.length > 1){
			retdes = "<span style='border:1px #A6C9E2 solid;padding:2px;margin:2px;display:inline-block;'>"+retarr.join(viewopt)+"</span>";
		}
		else{
			retdes = retarr.join(viewopt);
		}
	}
	return retdes;
};
function getCondExpDes(cond, elecondition){
	var retdes = "";
	if(cond.obj1 != null){
		retdes += getCalExpDes(cond.obj1, elecondition); 
		if(cond.obj2){
			switch(cond.rop){
				case "==":
					retdes += "等于";
					break;
				case "!=":
					retdes += "不等于";
					break;
				case ">":
					retdes += "大于";
					break;
				case ">=":
					retdes += "大于等于";
					break;
				case "<":
					retdes += "小于";
					break;
				case "<=":
					retdes += "小于等于";
					break;
				case "[]":
					retdes += "包含";
					break;
				default:
					break;
			}
			retdes += getCalExpDes(cond.obj2);
		}
	}
	return retdes;
};
//去掉逻辑关系,平铺cond
function getLogicConds(retconds, logicobj, elecondition){
	var retarr = [];
	var viewopt = "";
	for(var item in logicobj){
		if(item != "type" && item != "bop"){
			if("bop" in logicobj[item]){
				getLogicConds(retconds, logicobj[item], elecondition)
			}
			else{
				var condexpdes = getCondExpDes(logicobj[item], elecondition);
				var condexp = logicobj[item];
				var tmpobj = {};
				tmpobj.expdes = condexpdes;
				tmpobj.exp = condexp;
				retconds.push(tmpobj);
			}
		}
	}
}
function getCalExpDes(exp, elecondition){
	var retdes = " ";
	retdes += getAtomicExpDes(exp.arg1, elecondition);
	if(exp.arg2){
		switch(exp.cop){
			case "+":
				retdes += "加";
				break;
			case "-":
				retdes += "减";
				break;
			case "×":
				retdes += "乘";
				break;
			case "÷":
				retdes += "除";
				break;
			default:
				break;
		}
		retdes += getAtomicExpDes(exp.arg2, elecondition);
	}
	return retdes;
};
function getAtomicExpDes(operand, elecondition){
	var retdes = "";
	if(operand.table != undefined){
		var tbl = parseInt(operand.table, 10) + 1;
		if(operand.tablename){
			retdes += " "+operand.tablename+"组 "; 
		}
		else{
			retdes += "第"+tbl+"组 "; 
		}
	}
	if(operand.column != undefined){
		var cdes = "";
		cdes = parseInt(operand.column, 10) + 1;
		var dn = "";
		if(operand.field){
			if(operand.field == "text" || operand.field == "alias"){
				var facetfield = "";
				var snapshotData;
				if(window.currInstance){
					snapshotData = getCurrSnapshotData();
					if(snapshotData != null && snapshotData[0].facet != undefined){
						facetfield = snapshotData[0].facet.name;
					}
				}
				else{
					if(elecondition){
						snapshotData = elecondition.firstrequestData;
						if(snapshotData != null && snapshotData[0].facet != undefined){
							facetfield = snapshotData[0].facet.name;
						}
					}
				}
				if(snapshotData){
					if(facetfield == ""){
						dn = "正文";
					}
					else{
						dn = getDisplayName(facetfield);
					}
				}
			}
			else if(operand.field == "range"){
				dn = "区间起始";
			}
			else if(operand.field == "rangeend"){
				dn = "区间结束";
			}
			else{
				dn = getRetweetDisplayName(operand.field);
			}
		}
		if(dn){
			retdes += "第"+cdes+"("+dn+")列";
		}
		else{
			retdes += "第"+cdes+"列";
		}
	}
	if(operand.row != undefined){
		switch(operand.row){
			case "totalnum":
				retdes = "记录个数";
				break;
			case "all":
				retdes += "任意值";
				break;
			case "count":
				retdes += "计数";
				break;
			case "sum":
				retdes += "和";
				break;
			case "max":
				retdes += "最大值";
				break;
			case "min":
				retdes += "最小值";
				break;
			case "average":
				retdes += "平均值";
				break;
			default:
				var trow = parseInt(operand.row, 10) + 1;
				retdes += "第"+trow+"行";
				break;
		}
	}
	if(operand.constant){
		retdes += ""+operand.constant;
	}
	return retdes;
};
function getDisplayName(field, filter){
	var displayName;
	switch(field){
		case "text":
			displayName = "关键词";
			break;
		case "created_at":
			displayName = "创建时间";
			break;
		case "nearlytime":
			displayName = "相对今天";
			break;
		case "beforetime":
			displayName = "时间段";
			break;
		case "untiltime":
			displayName = "日历时间段";
			break;
		case "combinWord":
			displayName = "短语";
			break;
		case "business":
			displayName = "行业";
			break;
		case "areauser":
		case "users_location":
			displayName = "用户地区";
			break;
		case "users_city_code":
		case "city_code":
			displayName = "用户城市";
			break;
		case "users_district_code":
		case "district_code":
			displayName = "用户县区";
			break;
		case "users_province_code":
		case "province_code":
			displayName = "用户省份";
			break;
		case "users_country_code":
		case "country_code":
			displayName = "用户国家";
			break;
		case "areamentioned":
			displayName = "提及地区";
			break;
		case "city":
			displayName = "提及城市";
			break;
		case "district":
			displayName = "提及县区";
			break;
		case "province":
			displayName = "提及省份";
			break;
		case "country":
			displayName = "提及国家";
			break;
		case "ancestor_city":
			displayName = "上层转发提及城市";
			break;
		case "ancestor_district":
			displayName = "上层转发提及县区";
			break;
		case "ancestor_province":
			displayName = "上层转发提及省份";
			break;
		case "ancestor_country":
			displayName = "上层转发提及国家";
			break;
		case "account":
			displayName = "@用户";
			break;
		case "userid":
		case "users_id":
			displayName = "用户名";
			break;
		case "url":
			displayName = "URL";
			break;
		case "original_url":
			displayName = "页面地址";
			break;
		case "NRN":
			displayName = "人名";
			break;
		case "organization":
			displayName = "机构";
			break;
		case "wb_topic":
			displayName = "微博话题";
			break;
		case "wb_topic_keyword":
			displayName = "微博话题关键词";
			break;
		case "wb_topic_combinWord":
			displayName = "微博话题短语";
			break;
		case "reply_comment":
			displayName = "父评论";
			break;
		case "screen_name":
		case "users_screen_name":
			displayName = "作者昵称";
			break;
		case "users_verified_reason":
		case "verified_reason":
			displayName = "认证原因";
			break;
		case "users_verified_type":
		case "verified_type":
			displayName = "认证类型";
			break;
		case "originalText": 
			displayName = "原文内容";
			break;
		case "similar":
			displayName = "摘要内容";
			break;
		case "source":
			displayName = "应用来源";
			break;
		case "users_description":
		case "description":
			displayName = "简介";
			break;
		case "emotion":
			displayName = "情感关键词";
			break;
		case "emoCombin":
			displayName = "情感短语";
			break;
		case "emoNRN":
			displayName = "情感人名";
			break;
		case "emoOrganization":
			displayName = "情感机构";
			break;
		case "emoTopic":
			displayName = "情感微博话题";
			break;
		case "emoTopicKeyword":
			displayName = "情感微博话题关键词";
			break;
		case "emoTopicCombinWord":
			displayName = "情感微博话题短语";
			break;
		case "emoAccount":
			displayName = "情感@用户";
			break;
		case "emoBusiness":
			displayName = "行业情感";
			break;
		case "emoCountry":
			displayName = "提及国家情感";
			break;
		case "emoProvince":
			displayName = "提及省份情感";
			break;
		case "emoCity":
			displayName = "提及城市情感";
			break;
		case "emoDistrict":
			displayName = "提及县区情感";
			break;
		case "ancestor_emoCountry":
			displayName = "上层转发提及国家情感";
			break;
		case "ancestor_emoProvince":
			displayName = "上层转发提及省份情感";
			break;
		case "ancestor_emoCity":
			displayName = "上层转发提及城市情感";
			break;
		case "ancestor_emoDistrict":
			displayName = "上层转发提及县区情感";
			break;
		case "sex":
		case "users_gender":
			displayName = "性别";
			break;
		case "users_allow_all_act_msg":
			displayName = "允许私信";
			break;
		case "users_allow_all_comment":
			displayName = "允许评论";
			break;
		case "users_verified":
		case "verify":
			displayName = "认证";
			break;
		case "users_level":
			displayName = "用户级别";
			break;
		case "father_guid": 
			displayName = "上层文章唯一标识";
			break;
		case "retweeted_guid":
			displayName = "原创唯一标识";
			break;
		case "retweeted_status":
			displayName = "原创ID";
			break;
		case "id":
			displayName = "微博ID";
			break;
		case "reposts_count":
		case "repostsnum":
			displayName = "转发数";
			break;
		case "comments_count":
		case "commentsnum":
			displayName = "评论数";
			break;
		case "direct_comments_count":
			displayName = "直接评论数";
			break;
		case "praises_count":
			displayName = "赞";
			break;
		case "total_reposts_count":
			displayName = "总转发数";
			break;
		case "followers_count":
			displayName = "直接到达数";
			break;
		case "total_reach_count":
			displayName = "总到达数";
			break;
		case "repost_trend_cursor":
			displayName = "转发所处层级";
			break;
		case "direct_reposts_count":
			displayName = "直接转发数";
			break;
		case "register_time":
		case "users_created_at":
			displayName = "博龄";
			break;
		case "content_type":
			displayName = "类型";
			break;
		case "has_picture":
			displayName = "含有图片";
			break;
		case "host_domain":
			displayName = "主机域名";
			break;
		case "users_followers_count":
			displayName = "粉丝数";
			break;
		case "users_friends_count":
			displayName = "关注数";
			break;
		case "users_friends_id":
			displayName = "关注";
			break;
		case "users_statuses_count":
			displayName = "文章数";
			break;
		case "users_replys_count":
			displayName = "回复数";
			break;
		case "users_recommended_count":
			displayName = "精华帖数";
			break;
		case "users_favourites_count":
			displayName = "收藏数";
			break;
		case "users_bi_followers_count":
			displayName = "互粉数";
			break;
		case "users_sourceid":
		case "sourceid":
		case "users_source_host":
			displayName = "数据来源";
			break;
		case "discuss_count":
			displayName = "讨论数";
			break;
		default:
			displayName = "";
			break;
	}
	if(displayName == ""){
		if(filter != null && filter[field] != undefined){
			displayName = filter[field].label;
		}
	}
	return displayName;
}
function getSourceHostName(host){
	var sourcename = host;
	$.ajax({
		url:config.dataUrl+"?type=getsourcehostname",
		type:"GET",
		dataType:"json",
		data:{host:host},
		async:false,
		success:function(rd){
			if(rd && rd[0].datalist){
				$.each(rd[0].datalist, function(k, v){
					if(v.code == host){
						sourcename = v.name;
						return false;
					}
				});
			}
		},
		error:function(){
				  alert("数据请求异常!");
			  }
	});
	return sourcename;
}
function getSourceUrl(sourceid){
	var sourceurl = sourceid;
	$.ajax({
		url:config.dataUrl+"?type=getsourceurlbyid",
		type:"GET",
		dataType:"json",
		data:{sourceid:sourceid},
		async:false,
		success:function(data){
			sourceurl = data[0].datalist;
		}
	});
	return sourceurl;
}
function getSourceName(sourceid){
	var sourceurl = sourceid;
	$.ajax({
		url:config.dataUrl+"?type=getsourcenamebyid",
		type:"GET",
		dataType:"json",
		data:{sourceid:sourceid},
		async:false,
		success:function(data){
			sourcename = data[0].datalist;
		}
	});
	return sourcename;
}
	/*
function getSource(){
	var ids = "";
	$.ajax({
		url:config.dataUrl+"?type=getsource",
		type:"GET",
		dataType:"json",
		data:{sourceid:ids},
		async:false,
		success:function(rd){
			if(rd){
				$.each(rd[0].datalist, function(k, v){
					var id = v["id"].toString();
					sourcelist[id] = {name:v.name};
				});
			}
		},
		error:function(){
				  alert("数据请求异常!");
			  }
	});
	config.sourceList = sourcelist;
	return sourcelist;
}
	*/
function getSource(callback){
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

function getVerifiedType(verified, sourceid){
	var verifiedlist = [];
	if(verified == undefined){
		verified = '';
	}
	if(sourceid == undefined){
		sourceid = '';
	}
	$.ajax({
		url:config.dataUrl+"?type=getverifiedtype",
		type:"GET",
		dataType:"json",
		data:{sourceid:sourceid, verified:verified},
		async:false,
		success:function(data){
			verifiedlist = data[0].datalist;
		},
		error:function(){
				  alert("数据请求异常!");
			  }
	});
	if(verifiedlist.length > 0){
		config.verifiedlist = verifiedlist;
	}
	return verifiedlist;
}
function getVerified(sourceid){
	var verified = [];
	if(sourceid == undefined){
		sourceid = '';
	}
	$.ajax({
		url:config.dataUrl+"?type=getverified",
		type:"GET",
		dataType:"json",
		data:{sourceid:sourceid},
		async:false,
		success:function(data){
			verified= data[0].datalist;
		},
		error:function(){
					alert("数据请求异常!");
			  }
	});
	if(verified.length > 0){
		config.verified= verified;
	}
	return verified;
}
function getVerifiedTypeName(typeid){
	var vtlist = []; 
	var vtname = "未知";
	if(!config.verifiedlist){
		vtlist =  getVerifiedType(); //从common中定义的认证类型的数组
	}
	else{
		vtlist= config.verifiedlist;
	}
	$.each(vtlist, function(vi, vitem){
		$.each(vitem, function(ei, eitem){
			if(eitem.code == typeid){
				vtname = eitem.name;
				return false;
			}
		});
	});
	return vtname;
}
function getVerifiedName(vid){
	var vtlist = []; 
	var vtname = "未知";
	if(!config.verified){
		vtlist =  getVerified(); //从common中定义的认证类型的数组
	}
	else{
		vtlist= config.verified;
	}
	$.each(vtlist, function(ei, eitem){
		if(eitem.code == vid){
			vtname = eitem.name;
			return false;
		}
	});
	return vtname;
}
//通过element中 filter获取字段 label,datatype等信息
//可能存在filter为null或filter中某个字段删除的情况,此时label返回item, 其他返回空串.
function getEleFilterItem(objJson, item, prop){
	var retval;
	if(objJson){
		if(objJson.filter){
			retval = getFilterItem(objJson.filter, item, prop);
		}
	}
	return retval;
}
function getFilterItem(filter, item, prop){
	var retval;
	if(filter != null && filter[item]){
		if(prop){
			if(filter[item][prop]){
				retval = filter[item][prop];
			}
			else{
				retval = "";
			}
		}
		else{
			retval = filter[item];
		}
	}
	else{
		switch(prop){
			case "label":
				retval = item;
				break;
			default:
				retval = "";
				break;
		}
	}
	return retval;
}
/*
 * @brief  网站允许空白referer的防盗链图片的js破解代码
 * @param  string url 图片地址
 * @param  jQuery selector 图片显示位置
 * @param  string width 图片宽
 * @param  string height 图片高
 * @param  string imgindex 图片索引
 * @return 无
 * @author Bert
 * @date   2016-6-30
 * @change 2016-6-30
 * */
function showHotlinkingImg(url, selector, width, height, imgindex) {
	var imgrandid = Math.round(Math.random()*100000);
	var frameid = 'frameimg' + imgrandid;
	//window.img = '<img src="' + url + '?' + imgrandid + '" width="'+width+'" height="'+height+'" imgindex="'+imgindex+'"/>';
	window.img = '<html><head></head><body><img src="' + url + '?' + imgrandid + '" onerror="javascript:this.name=1;" width="'+width+'" height="'+height+'" imgindex="'+imgindex+'"/></body></html>';
	var widthiframe=width+10;
	var heightiframe=height+10;
	$(selector).html('<iframe id="' + frameid + '" src="javascript:parent.img;" frameBorder="0" scrolling="no" width="'+widthiframe+'" height="'+heightiframe+'"></iframe>');
	$("#"+frameid+"").load(function(){
		var flag = $(document.getElementById(frameid).contentWindow.document.body).find("img").attr("name");
		if(flag){
			$("#"+frameid).css("display","none");
		}
	});
}
