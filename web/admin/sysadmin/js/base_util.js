/*
 * 添加定时计划
 * */
function myCrontimeSelect(successResult, selectedVal){
	//选择重复类型,对应类型的全部值选中
	var needVal = {
		//按月重复，可以选日期，或者第几周+周几
		"monthly":[["monthly", "daily", "hourly", "minutely"], ["monthly", "monthweek", "weekly", "hourly", "minutely"]], 
		"weekly":[["weekly", "hourly", "minutely"]], 
		"daily":[["daily", "hourly", "minutely"]], 
		"hourly":[["hourly", "minutely"]],
	    "minutely":[["minutely"]]};
	//当选择重复方式时,清空不可有值的字段, key为重复方式
	var checkRepeatNeedEmpty = {
		"daily":["monthly","weekly", "monthweek"], 
		"weekly":["monthly", "daily","monthweek"],
		"hourly":["monthly", "daily", "weekly", "monthweek"],
		"minutely":["monthly", "daily", "weekly", "monthweek", "hourly"]
	};
	//选择重复方式的条件下, 点击某一组时,去掉重复方式选中, value为不可点击的组
	var checkGroupEmptyRepeat = {
		"monthly":["monthly"], 
		"daily":["monthly", "weekly","monthweek", "daily"], 
		"weekly":["monthly", "daily", "monthweek"],
		"hourly":["monthly", "daily", "monthweek", "weekly", "hourly"],
		"minutely":["monthly", "daily", "monthweek", "weekly","hourly", "minutely"]
	};
	var initCrontimeSelect = function(){
		var cronhtml = "";
		cronhtml +="<fieldset style='padding-right:0px;'>";
		cronhtml +="<legend>重复类型</legend>";
		cronhtml += '<div id="cronshortcut" repeattype="">';
		cronhtml += '<div id="cronshortcut_every_div">';
		cronhtml += '<span><input type="checkbox" id="crontime_repeat_monthly" name="crontime_repeat" /><label for="crontime_repeat_monthly">每月</label></span>';
		cronhtml += '<span><input type="checkbox" id="crontime_repeat_daily" name="crontime_repeat" /><label for="crontime_repeat_daily">每天</label></span>';
		cronhtml += '<span><input type="checkbox" id="crontime_repeat_monthweek" name="crontime_repeat" /><label for="crontime_repeat_monthweek">每周</label></span>';
		cronhtml += '<span><input type="checkbox" id="crontime_repeat_hourly" name="crontime_repeat" /><label for="crontime_repeat_hourly">每小时</label></span>';
		cronhtml += '<span><input type="checkbox" id="crontime_repeat_minutely" name="crontime_repeat" /><label for="crontime_repeat_minutely">每分钟</label></span>';
		cronhtml += '<span><input type="checkbox" id="crontime_repeat_custom" name="crontime_repeat" /><label for="crontime_repeat_custom">定制</label></span>';
		cronhtml += '</div>';
		cronhtml += '</div>';
		cronhtml +="</fieldset>";
		cronhtml += '<table class="list" id="crontime_table" width="98%" cellspacing="0" cellpadding="0" border="0" style="text-align:left;">';
		cronhtml += '<tr>';
		cronhtml += '<td colspan="2" style="height:17px;">月</td><td style="height:17px;">';
		cronhtml += '<div id="crontime_monthly_div" groupnametext="月">';
		for(var i=1;i<=12;i<i++){
			cronhtml +='<span><input type="checkbox" name="crontime_monthly" value="'+i+'" groupname="monthly" id="crontime_monthly_'+i+'" /><label for="crontime_monthly_'+i+'">'+i+'</label></span>';
		}
		cronhtml += '</div>';
		cronhtml += '<div id="all_crontime_monthly_div">';
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_monthly_select_btn" value="全选" /></span>'; //全选/清空
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_monthly_empty_btn" value="清空" /></span>'; //全选/清空
		cronhtml += '</div>';
		cronhtml += '</td>';
		cronhtml += '</tr>';
		cronhtml += '<tr>';
		cronhtml += '<td rowspan="2">天</td><td style="width:40px;" >日历天</td><td>';
		cronhtml += '<div id="crontime_daily_div" groupnametext="日历天">';
		for(var i=1;i<=31;i++){
			cronhtml += '<span><input type="checkbox" name="crontime_daily" value="'+i+'" groupname="daily" id="crontime_daily_'+i+'" /><label for="crontime_daily_'+i+'">'+i+'</label></span>';
			if(i%12 == 0){
				cronhtml +="<br/>";
			}
		}
		cronhtml += '</div>';
		cronhtml += '<div id="all_crontime_daily_div">';
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_daily_select_btn" value="全选"/></span>'; //全选/清空
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_daily_empty_btn" value="清空" /></span>'; //全选/清空
		cronhtml += '</div>';
		cronhtml += '</td>';
		cronhtml += '</tr>';
		cronhtml += '<tr>';
		cronhtml += '<td style="width:40px;" >星期</td><td>';
		cronhtml += '<div id="crontime_monthweek_div" groupnametext="第几周">第';
		for(var i=1;i<=4;i++){
			cronhtml += '<span><input type="checkbox" id="crontime_monthweek_'+i+'" value="'+i+'" groupname="monthweek" name="crontime_monthweek" /><label for="crontime_monthweek_'+i+'">'+i+'</label></span>';
		}
		cronhtml += '<span><input type="checkbox" id="crontime_monthweek_last" value="-1" groupname="monthweek" name="crontime_monthweek" /><label for="crontime_monthweek_last">最后一</label></span>&nbsp;&nbsp;';
		cronhtml += '个</div>';
		cronhtml += '<div id="crontime_weekly_div" groupnametext="星期几">周';
		for(var i=1;i<=7;i++){
			var ialias = "";
			switch(i){
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					ialias = Utils.numberToChinese(i);
					break;
				case 7:
					ialias = "日";
					break;
				default:
					break;
			}
			cronhtml += '<span><input type="checkbox" name="crontime_weekly" value="'+i+'" groupname="weekly" id="crontime_weekly_'+i+'" /><label for="crontime_weekly_'+i+'">'+ialias+'</label></span>';
		}
		cronhtml += '</div>';
		cronhtml += '</td>';
		cronhtml += '</tr>';
		cronhtml += '<tr>';
		cronhtml += '<td colspan="2" >时</td><td>';
		cronhtml += '<div id="crontime_hourly_div" groupnametext="时">';
		for(var i=0;i<=23;i++){
			cronhtml += '<span><input type="checkbox" name="crontime_hourly" value="'+i+'" groupname="hourly" id="crontime_hourly_'+i+'" /><label for="crontime_hourly_'+i+'">'+i+'</label></span>';
			if((i+1)%12 == 0 && i!=23){
				cronhtml +="<br/>";
			}
		}
		cronhtml += '</div>';
		cronhtml += '<div id="all_crontime_hourly_div">';
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_hourly_select_btn" value="全选"/></span>'; //全选/清空
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_hourly_empty_btn" value="清空"/></span>'; //全选/清空
		cronhtml += '</div>';
		cronhtml += '</td>';
		cronhtml += '</tr>';
		cronhtml += '<tr>';
		cronhtml += '<td colspan="2" >分</td><td>';
		cronhtml += '<div id="crontime_minutely_div" groupnametext="分">';
		for(var i=0;i<=59;i++){
			cronhtml += '<span><input type="checkbox" name="crontime_minutely" value="'+i+'" groupname="minutely" id="crontime_minutely_'+i+'" /><label for="crontime_minutely_'+i+'">'+i+'</label></span>';
			if((i+1)%12 == 0 && i!=59){
				cronhtml +="<br/>";
			}
		}
		cronhtml += '</div>';
		cronhtml += '<div id="all_crontime_minutely_div">';
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_minutely_select_btn" value="全选"/></span>'; //全选/清空
		cronhtml += '<span class="helpbtn"><input type="button" id="all_crontime_minutely_empty_btn" value="清空"/></span>'; //全选/清空
		cronhtml += '</div>';
		cronhtml += '</td>';
		cronhtml += '</tr>';
		cronhtml += '</table>';
		if($("#crontimeDiv").length == 0){
			$("<div id='crontimeDiv'></div>").insertAfter("body");
			$("#crontimeDiv").append(cronhtml);
		}
		$("#crontimeDiv").dialog({
			title:"制定计划",
			autoOpen: true,
			modal:true,
			height:443,
			width:442,
			close:function(){
			},
			buttons:{
				"提交":function(){
					//获取时间计划的值
					var countflag = 0;
					var scheobj = {};
					var repeat = $("#cronshortcut").attr("repeattype");
					if(repeat){
						var repeattype = "";
						switch(repeat){
							case "minutely":
								repeattype = 1;
								break;
							case "hourly":
								repeattype = 2;
								break;
							case "daily":
								repeattype = 3;
								break;
							case "weekly":
								repeattype = 4;
								break;
							case "monthly":
								repeattype = 6;
								break;
							default:
								break;
						}
						scheobj.repeat = repeattype;
					}
					//month
					var month = [];
					$("input[name=crontime_monthly]:checked").each(function(mi, mitem){
						month.push($(mitem).val());
					});
					if(month.length > 0){
						countflag++;
						scheobj.month = month;
					}
					//day
					var day = [];
					$("input[name=crontime_daily]:checked").each(function(mi, mitem){
						day.push($(mitem).val());
					});
					if(day.length > 0){
						countflag++;
						scheobj.day = day;
					}
					//月的第几周
					var week = [];
					$("input[name=crontime_monthweek]:checked").each(function(mi, mitem){
						week.push($(mitem).val());
					});
					if(week.length > 0){
						countflag++;
						scheobj.week = week;
					}
					//星期几
					var weekday = [];
					$("input[name=crontime_weekly]:checked").each(function(wi, witem){
						weekday.push($(witem).val());
					});
					if(weekday.length > 0){
						countflag++;
						scheobj.weekday = weekday;
					}
					if(week.length > 0 && weekday.length == 0){
						alert("请选择星期几");
						return false;
					}
					if(repeat && day.length > 0 && weekday.length > 0){
						alert("不能同时选择星期和日历天!");
						return false;
					}

					//验证选择的第几周和选择的几号是否冲突
					if(week.length > 0){
						var availdays = []; //可选的day
						$.each(week, function(wi, witem){
							if(witem == -1){
								availdays.push(22,23,24,25,26,27,28,29,30,31);
							}
							else{
								for(var i=1;i<=7;i++){
									availdays.push(((witem-1)*7)+i);
								}
							}
						});
						if(day.length > 0){
							var flag = [];
							$.each(day, function(di, ditem){
								if(!availdays.inArray(ditem)){
									flag.push(ditem);
								}
							});
							if(flag.length > 0){
								alert("日历天"+flag.join(",")+"和星期冲突!");
								return false;
							}
						}
					}
					var hour = [];
					$("input[name=crontime_hourly]:checked").each(function(hi, hitem){
						hour.push($(hitem).val());
					});
					if(hour.length > 0){
						countflag++;
						scheobj.hour = hour;
					}
					var minute = [];
					$("input[name=crontime_minutely]:checked").each(function(mi, mitem){
						minute.push($(mitem).val());
					});
					if(minute.length > 0){
						countflag++;
						scheobj.minute = minute;
					}
					//if(countflag == 0 && !repeat){
					//	alert("请至少选择一项!");
					//	return false;
					//}
					//必选的
					if(repeat in needVal){
						var warn = false;
						var nv = needVal[repeat];//需要有值的
						var needvarr = [];
						$.each(nv, function(ni, nitem){
							if(nv.length == 1){
								$.each(nitem, function(nci, ncitem){
									var chklen = $("input[name=crontime_"+ncitem+"]:checked").length;
									if(chklen == 0){
										var grouptext = $("#crontime_table input[groupname="+ncitem+"]").parents("div").attr("groupnametext");
										alert("请选择"+grouptext+"!");
										warn = true;
										return false;
									}
								});
							}
							else{
								var needvarrchild = [];
								$.each(nitem, function(nci, ncitem){
									var chklen = $("input[name=crontime_"+ncitem+"]:checked").length;
									if(chklen == 0){
										var grouptext = $("#crontime_table input[groupname="+ncitem+"]").parents("div").attr("groupnametext");
										needvarrchild.push(grouptext);
									}
								});
								if(needvarrchild.length > 0){
									needvarr.push(needvarrchild);
								}
							}
						});
						if(needvarr.length > 1){
							var errstr = "请选择";
							$.each(needvarr, function(di, ditem){
								errstr += ditem.join(",");
								if(di!=(needvarr.length-1)){
									errstr +="或";
								}
							});
							alert(errstr);
							warn = true;
						}
						if(warn){
							return false;
						}
					}

					//不能选的
					for(var item in checkRepeatNeedEmpty){
						if(item == repeat){
							var warn = false;
							var ne = checkRepeatNeedEmpty[item];
							$.each(ne, function(ni, nitem){
								var chklen = $("input[name=crontime_"+nitem+"]:checked").length;
								if(chklen != 0 && nitem!=repeat){
									var grouptext = $("#crontime_table td[groupnametext="+nitem+"]").text();
									alert("不能选择"+grouptext+"!");
									warn = true;
									return false;
								}
							});
							if(warn){
								return false;
							}
						}
					}
					if(repeat == "monthly"){
						if(week.length > 0){
							scheobj.repeat = 7; //monthweek
						}
					}
					successResult(scheobj);
					$(this).dialog("close");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			}
		});
		//已选择的项初始化
		$("#cronshortcut").attr("repeattype", "");
		$("#crontimeDiv input[type=checkbox]").attr("checked", false);
		selectedCrontime();
		//重复方式只能选择一种, 选中重复方式后,对应的组选中
		$.each(["monthly", "daily", "hourly", "minutely", "monthweek", "custom"], function(i, item){
			$("#crontime_repeat_"+item+"").bind("click", function(){
				if($("#crontime_repeat_"+item+"").prop("checked")){
					//重复类型选中
					$("input[type=checkbox][name=crontime_repeat]").attr("checked", false);
					$("#crontime_repeat_"+item+"").attr("checked", true);
					if(item != "custom"){
						$("#cronshortcut").attr("repeattype", item);
					}
					else{
						$("#cronshortcut").attr("repeattype", "");
					}

					//重复类型对应的组选中,并清除冲突的字段
					if(item != "monthweek"){
						$("input[name=crontime_"+item+"]").attr("checked", true);
						//if( item == "monthly" || item == "daily" ) {
						//	$("#crontime_monthweek_div span").css({
						//		"background-color":"#dfeffc",
						//		"color": "#2e6e9e"
						//	});
						//}

					} else {
						$("input[name=crontime_monthweek]").attr("checked", true);
					}
					if(item in checkRepeatNeedEmpty){
						$.each(checkRepeatNeedEmpty[item], function(ei, eitem){
							$("input[name=crontime_"+eitem+"]").attr("checked", false);
						});
					}
				}
				else{
					$("#cronshortcut").attr("repeattype", "");
					if(item != "monthweek"){
						$("input[name=crontime_"+item+"]").attr("checked", false);
					}
					else {
					//	$("#crontime_monthweek_div span").css({
						//	"background-color":"#dfeffc",
						//	"color": "#2e6e9e"
						//});
						$("input[name=crontime_monthweek]").attr("checked", false);
					}
					if(item != "custom"){
						$("#crontime_repeat_custom").attr("checked", true);
					}
				}
				addButtonStyle();
			});
		});
		//选中重复类型后, 当选择此类型后,再选择和此类型相同,或单位较大的类型时,清空此重复类型.
		//比如选择按分钟重复,表示每分钟都做,比分钟大的单位不起作用
		//当选择某种重复类型时, 对应必需有值的字段和需要为空的字段
		$("#crontime_table input[type=checkbox][name^=crontime_]").bind("click", function(){
			var chkgroupname = $(this).attr("groupname");
			var repeattype = $("#cronshortcut").attr("repeattype");
			if(repeattype == ""){
				$("#crontime_repeat_custom").attr("checked", true);
			}
			if(repeattype in checkGroupEmptyRepeat){
				if(checkGroupEmptyRepeat[repeattype].inArray(chkgroupname)){
					$("#crontime_repeat_"+repeattype+"").attr("checked", false);
					$("#cronshortcut").attr("repeattype", "");
					$("#crontime_repeat_custom").attr("checked", true);
				}
			}
			addButtonStyle();
		});
		//全选和清空
		$.each(["monthly", "daily", "monthweek", "hourly", "minutely"], function(i, item){
			$("#all_crontime_"+item+"_select_btn").bind("click", function(){
				$("input[name=crontime_"+item+"]").attr("checked", true);
				var repeatchk = $("input[name=crontime_repeat]:checked").length;
				if(repeatchk == 0){
					$("#crontime_repeat_custom").attr("checked", true);
				}
				addButtonStyle();
			});
			$("#all_crontime_"+item+"_empty_btn").bind("click", function(){
				$("input[name=crontime_"+item+"]").attr("checked", false);
				if($("#crontime_repeat_"+item+"").prop("checked")){
					$("#crontime_repeat_"+item+"").attr("checked", false);
					$("#crontime_repeat_custom").attr("checked", true);
				}
				addButtonStyle();
			});
		});
		//日历天和星期不能同时选择
		$("#crontime_table input[name=crontime_daily]").bind("click", function(){
			var repeattype = $("#cronshortcut").attr("repeattype");
			var chklen = $("#crontime_table input[name=crontime_daily]:checked").length;
			if(chklen > 0){
				if(repeattype){
					$("#crontime_table input[name=crontime_weekly]").attr("checked", false);
					$("#crontime_table input[name=crontime_monthweek]").attr("checked", false);
				}
				addButtonStyle();
			}
		});
		$("#crontime_table input[name=crontime_weekly]").bind("click", function(){
			var chklen = $("#crontime_table input[name=crontime_weekly]:checked").length;
			var repeattype = $("#cronshortcut").attr("repeattype");
			if(chklen > 0 && repeattype){
				$("#crontime_table input[name=crontime_daily]").attr("checked", false);
				addButtonStyle();
			}
		});
		$("#crontime_table input[name=crontime_monthweek]").bind("click", function(){
			var chklen = $("#crontime_table input[name=crontime_monthweek]:checked").length;
			var repeattype = $("#cronshortcut").attr("repeattype");
			if(chklen > 0 && repeattype){
				$("#crontime_table input[name=crontime_daily]").attr("checked", false);
				addButtonStyle();
			}
		});
		addButtonStyle();
	};
	var addButtonStyle = function(){
		//添加buttonset样式
		var cssobj = {'width':'48px','display':'inline-block'};
		var cssobj1 = {'width':'20px','display':'inline-block'};

		$("#cronshortcut_every_div").children("span").css(cssobj);
		$("#cronshortcut_every_div").children("span").last().css({'float':'right'});
		$("#cronshortcut_every_div").find("label").css({'width':'100%'});
		$("#cronshortcut_every_div").buttonset();

		$("#crontime_monthweek_div").children("span").css(cssobj1);
		$("#crontime_monthweek_div").children("span").last().css({'width':'48px'});
		$("#crontime_monthweek_div").find("label").css({'width':'100%'});
		$("#crontime_monthweek_div").buttonset();

		$.each(["monthly", "daily", "weekly", "hourly", "minutely"], function(i, item){
			$("#crontime_"+item+"_div").css({"float":"left"});
			$("#crontime_"+item+"_div").children("span").css(cssobj1);
			$("#crontime_"+item+"_div").find("label").css({'width':'100%'});
			$("#crontime_"+item+"_div").buttonset();
		});
		$.each(["monthly", "daily", "weekly", "hourly", "minutely"], function(i, item){
			$("#all_crontime_"+item+"_div").css({"float":"right"});
			$("#all_crontime_"+item+"_div").children("span").css({"margin-right":"4px"});
			$("#all_crontime_"+item+"_div").buttonset();
		});
	};
	var selectedCrontime= function(){
		for(var item in selectedVal){
			if("repeat" in selectedVal){
				if(item == "repeat"){
					var repeat = selectedVal[item];
					var repeattype = "";
					switch(parseInt(repeat,10)){
						case 1:
							repeattype = "minutely";
							break;
						case 2:
							repeattype = "hourly";
							break;
						case 3:
							repeattype = "daily";
							break;
						case 4:
							repeattype = "monthweek";
							break;
						case 6:
						case 7: //monthweek
							repeattype = "monthly";
							break;
						default:
							break;
					}
					$("#crontime_repeat_"+repeattype+"").attr("checked", true);
					$("#cronshortcut").attr("repeattype", repeattype);
				}
			}
			else{
				$("#crontime_repeat_custom").attr("checked", true);
			}
			if(selectedVal[item].length > 0){
				$.each(selectedVal[item], function(si, sitem){
					var htmlname = "";
					switch(item){
						case "month":
							htmlname = "monthly";
							break;
						case "day":
							htmlname = "daily";
							break;
						case "weekday":
							htmlname = "weekly";
							break;
						case "week":
							htmlname = "monthweek";
							break;
						case "hour":
							htmlname = "hourly";
							break;
						case "minute":
							htmlname = "minutely";
							break;
						default:
							break;
					}
					if(sitem == -1){
						$("#crontime_"+htmlname+"_last").attr("checked", true);
					}
					else{
						$("#crontime_"+htmlname+"_"+sitem+"").attr("checked", true);
					}
				});
			}
		}
	};
	//初始化
	initCrontimeSelect();
}
/*
 * choiceValue 可选的值,可以是URL 或 name,code 对象数组
 * url时需要添加对应的处理逻辑
 * successResult: 结果的回调函数
 * selectedVal: 默认已选中的值
 * showName 列表名称
 * isemo 是否为情感
 * ele 弹出新对话框,或是 下拉div形式(用户模型的onsitefilter使用)
 * isnot 标签是否有"不"选择按钮
 * pname 父级名称显示 '全部' 还是自身名称 ,默认显示自身名称, pname == 1时显示全部
 * needcheckchild, 是否需要显示子级, 地区情感时不需要显示子级
 * showselectallbtn 是否显示 "选择全部"按钮
 * showfilter 是否显示 "选择全部"按钮
 * */
function myCommonSelect(choiceValue, successResult, selectedVal, showName, isemo, ele, isnot, pname, needcheckchild, queryfield, showselectallbtn, srchostid){
	var page; 
	var pagesize = 20;
	var curpage2;
	var haschild = false;
	var choiceVal = choiceValue;
	var parentArr = [];
	var goparent = false;
	var successFun;
	var emochk;
	var searchtxt = "";
	var fieldflag; //字段标识, 查询的字段标识,再判断子节点时用到 isChild
	var initCommonSelect = function(){
		if(showName == undefined){
			showName = "";
		}
		$("#choicebox").remove();
		var commonhtml = "";
		commonhtml = "搜索:<input type='text' id='searchtxt' name='searchtxt' /><input type='button' name='searchbtn' id='searchbtn' value='搜索' />(使用*模糊搜索)";
		commonhtml += "<fieldset>"; 
		commonhtml +="<legend>"+showName+"列表</legend>";
		commonhtml +="<div id='groupcommon'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/></div>";
		commonhtml +="<div id='commonpage' style='margin-top:2px;float:left;width:100%;margin:5px;'></div>";
		if(showselectallbtn){
			commonhtml +="<div class='search' id='commonselectallbtndiv' style='float:left;width:100%;'><input id='commonselectallbtn' name='commonselectallbtn' type='button' value='全选' /> <input id='commonunselectallbtn' name='commonunselectallbtn' type='button' value='反选' /> <input id='commonunselectnonebtn' name='commonunselectnonebtn' type='button' value='清空' /></div>";
		}
		commonhtml +="</fieldset>";
		commonhtml += "<fieldset>"; 
		commonhtml +="<legend>已选择的"+showName+"</legend>";
		commonhtml +="<div id='selectedcommons'></div>";
		commonhtml +="</fieldset>";
		commonhtml +="<div class='search'><input id='commonsubmit' name='commonsubmit' type='button' value='确定' />   <input id='canclesubmit' name='canclesubmit' type='button' value='取消' /></div>";

		var commonboxhtml = '<div id="choicebox" title="'+showName+'列表" style="position:absolute;display:none;z-index:9999;background-color:white;border:1px solid blue;font-size: 12px; width:490px;"></div>';
		if(ele != undefined){
			$("body").append(commonboxhtml);

			$("#choicebox").append(commonhtml);
		}
		else{
			$("<div id='choicebox' title='"+showName+"列表'></div>").insertAfter("body");
			$("#choicebox").append(commonhtml);
			$("#choicebox").dialog({
				autoOpen: false,
				modal:true,
				width:550,
				close:function(){
					$("#commonemotiondiv").css("display", "none");
				}
			});
		}

		//已选择的项初始化
		selectedCommon();
		if(!isArray(choiceVal)){  //可选择项为url
			//模拟第一次点击事件
			this.sendItem("<a haschild='"+choiceVal+"' code='' name=''></a>");
		}
		else{ //可选择想为静态数组
			//第一次请求为parent赋值
			var parentObj = {};
			parentObj.name = ""; 
			parentObj.code = ""; 
			parentObj.searchurl = "";
			parentArr.push(parentObj); 
			//数据初始化
			searchCommon(pagesize, 1, "#groupcommon"); //同时为分页回调函数
		}
		if(ele != undefined){
			var ex,ey;
			ex = $(ele).offset().left + 130;
			if(ex > ($(document.body).width() - $("#choicebox").width())){
				ex = $(document.body).width() - $("#choicebox").width() -10;
			}
			ey = $(ele).offset().top/* - $("#commonemotiondiv").height()*/;
			//$("#choicebox").css({top:ey+"px", left:ex+"px", display:"block"});
			$("#choicebox").slideDown("slow").css({
				'display':'block',
				'left': ex+"px",
				'top': ey+"px"
			});
		}
		else{
			$("#choicebox").dialog("open");
		}
		$("#searchbtn").bind("click", function(){
			searchtxt = $("#searchtxt").val();
			searchCommon(pagesize, 1, "#groupcommon"); //同时为分页回调函数
		});
		//反选
		$("#commonunselectallbtn").bind("click", function(){
			$("#groupcommon").find("a").each(function(i, item){
				sendItem($(item));
			});
		});
		//全选
		$("#commonselectallbtn").bind("click", function(){
			$("#selectedcommons").empty();
			$("#groupcommon").find("a").each(function(i, item){
				sendItem($(item));
			});
		});
		//清空
		$("#commonunselectnonebtn").bind("click", function(i, item){
			$("#selectedcommons .cancleitem").each(function(i, m){
				cancleItem($(m));
			});
		});

		$("#commonsubmit").unbind("click");
		$("#commonsubmit").bind("click", function(){
			var resultArr = [];
			resultArr = getSelectedVal();
			successResult(resultArr);
			$("#commonemotiondiv").css("display", "none");
			if(ele != undefined){
				//$("#choicebox").css("display", "none");
				$("#choicebox").slideUp("slow");
			}	
			else{
				$("#choicebox").dialog("close");
			}
		});
		$("#canclesubmit").bind("click", function(){
			$("#commonemotiondiv").css("display", "none");
			$("#selectedcommons").empty();
			if(ele != undefined){
				//$("#choicebox").css("display", "none");
				$("#choicebox").slideUp("slow");
			}
			else{
				$("#choicebox").dialog("close");
			}
		});
	};
	this.cancleItem = function(ele){
		$(ele).parent().remove();
		var delcode = $(ele).attr("code");
		var delemo = $(ele).attr("emotype");
		if(delemo != undefined && !isemo){
			$("#groupcommon a[code='"+delcode+"'][emotype='"+delemo+"']").removeClass().addClass("notselected");
			//改变父项的选择状态
			deleteParent(delcode, delemo); 
		}
		else{
			$("#groupcommon a[code='"+delcode+"']").removeClass().addClass("notselected");
			//改变父项的选择状态
			deleteParent(delcode); 
		}
	};
	//递归删除
	var deleteParent = function(delcode, delemo){
		//子项全部删除后,改变父项选中状态
		var parcode = getParent(delcode);
		var childflag = true;
		$("#selectedcommons .sltitem").each(function(i, m){
			var code = $(m).attr("code");
			if(code == parcode){
				if(delemo != undefined){
					var emo = $(m).attr("emotype");
					if(emo == delemo){
						childflag = 1;
						return false;
					}
					else{
						childflag = 3;
					}
				}
				else{
					childflag = 1;
					return false;
				}
			}
			else if(isChild(code, parcode)){  //已选择项中有删除项的孩子
				childflag = 2;
				return false;
			}
			else{
				childflag = 3;
			}
		});
		if(childflag === 1){
			$("#groupcommon a[code='"+parcode+"']").removeClass().addClass("selected");
		}
		else if(childflag == 2){
			$("#groupcommon a[code='"+parcode+"']").removeClass().addClass("halfselected");
		}
		else if(childflag == 3){ //没有子项时
			if(delemo!=undefined && !isemo){
				$("#groupcommon a[code='"+parcode+"'][emotype="+delemo+"]").removeClass().addClass("notselected");
				var pclass = $("#groupcommon a[code='"+parcode+"'][emotype="+delemo+"]").hasClass("notselected");
			}
			else{
				$("#groupcommon a[code='"+parcode+"']").removeClass().addClass("notselected");
				var pclass = $("#groupcommon a[code='"+parcode+"']").hasClass("notselected");
			}
			if(fieldflag == "area" && parcode != "CN" && !pclass){
				deleteParent(parcode, delemo);
			}
		}
	};
	this.getSelectedVal = function(){
		var resultArr = [];
		$("#selectedcommons .sltitem").each(function(i, m){
			var itemObj = {};
			itemObj.code = $(this).attr("code");
			itemObj.name = $(this).text();
			if($(this).attr("emotype") != undefined){
				itemObj.emotype = $(this).attr("emotype");
			}
			if($(this).attr("exclude") != undefined){
				itemObj.exclude = $(this).attr("exclude");
			}
			resultArr.push(itemObj);
		});
		return  resultArr;
	}
	this.sendItem = function(ele, eve){
		selectedVal = getSelectedVal();

		//var elename = $(ele).text();
		var elename = $(ele).attr("name");
		var elecode = $(ele).attr("code");
		var eleemo = $(ele).attr("emotype");
		var hc = $(ele).attr("haschild");
		if(hc != "false"){  //当有子节点时再次弹出层
			if($(ele).attr("goparent") != undefined && $(ele).attr("goparent")){  //删除
				parentArr.splice(parentArr.length-1, 1);
			}
			else{
				var parentObj = {};
				parentObj.name = elename;
				parentObj.code = elecode;
				parentObj.searchurl = hc;
				parentArr.push(parentObj); 
			}
			//choiceVal = hc;  //更改取数据url; 
			choiceVal = parentArr[parentArr.length-1].searchurl;
			if(choiceVal == ""){ //为空时从新赋值 ,choiceVal为静态数组时第一级parent存储 searchurl为 "";
				choiceVal = choiceValue;
			}
			searchCommon(pagesize, 1, "#groupcommon");

			//更改样式
			$(ele).removeClass().addClass("halfselected");
		}
		else{
			if(isemo!=undefined && (isemo == true || isemo == "true")){  //需要选择情感
				myCommonEmotionChk("", ele, function(data){
					var n = elename += data.name;
					addItem(ele, n, elecode, data.code);
					$("#commonemotiondiv").css({display:"none"});
				});
			}
			else{
				var newname = elename;
				if(eleemo != undefined){
					newname = elename+"("+emoval2text(eleemo)+")";
				}
				addItem(ele, newname, elecode, eleemo);
			}
		}
	};
	//ele元素本身用于更改样式, newname newcode拼接了情感值
	var addItem = function(ele, newname, newcode, emotype){
		$(ele).removeClass().addClass("selected");
		var pcode = getParent(newcode);
		if(pcode != newcode){
			$("#groupcommon a[code='"+pcode+"']").removeClass().addClass("halfselected");
		}
		var wflag = true;
		$("#selectedcommons .sltitem").each(function(i, m){
			if(emotype != undefined){
				var sltcode = $(this).attr("code");
				var sltemo = $(this).attr("emotype");
				if(newcode == sltcode && emotype == sltemo){
					$(this).parent().remove();
					$(ele).removeClass().addClass("notselected");
					deleteParent(newcode, sltemo);
					wflag = false;
				}
			}
			else{
				var sltcode = $(this).attr("code");
				if(newcode == sltcode){
					$(this).parent().remove();
					$(ele).removeClass().addClass("notselected");
					deleteParent(newcode);
					//$(ele).css("background-color", "white");
					wflag = false;
				}
			}
		});
		if(wflag){
			var spanutilhtml = createUtilSpanHtml('sltitem', newname, newcode, isnot, undefined, 'cancleItem', undefined, emotype);
			$("#selectedcommons").append(spanutilhtml);
		}
	};
	var selectedCommon = function(){
		var slthtml = "";
		if(isnot == undefined){
			isnot = true;
		}
		$.each(selectedVal, function(i,m){
			slthtml += createUtilSpanHtml('sltitem', m.name, m.code, isnot, m.exclude, 'cancleItem', undefined, m.emotype);
		});
		$("#selectedcommons").append(slthtml);
	}
	var searchCommon = function(pagesize, curpage, selector){
		if(selector == undefined || selector=="commonpage"){
			selector = "#groupcommon";
		}
		if(!isArray(choiceVal)){
			var searchUrl = "";
			if(curpage == '' || curpage == null || curpage == undefined ){
				curpage2 = 1;
			}
			else{
				curpage2 = curpage;
			}
			switch(choiceVal){
				case "countryUrl":
					searchUrl = config.dataUrl+"?type=selectcountry";
					haschild = "provinceUrl"; 
					fieldflag = "area";
					break;
				case "provinceUrl":
					var countrycode = parentArr[parentArr.length-1].code;
					if(countrycode == "" || countrycode == undefined){
						countrycode = "CN";
					}
					searchUrl = config.dataUrl+"?type=selectprovince&countrycode="+countrycode;
					haschild = "cityUrl"; 
					fieldflag = "area";
					break;
				case "cityUrl":
					var countrycode = parentArr[parentArr.length-2].code;
					if(countrycode == "" || countrycode == undefined){
						countrycode = "CN";
					}
					var provincecode = parentArr[parentArr.length-1].code;
					if(provincecode=='110000' || provincecode=='120000'|| provincecode=='310000' || provincecode=='500000'){
						citycode = provincecode.substr(0,3)+"100,"+provincecode.substr(0,3)+"200";
						searchUrl = config.dataUrl+"?type=selectdistrict&countrycode="+countrycode+"&provincecode="+provincecode+"&citycode="+citycode;
						haschild = false; 
					}
					else{
						searchUrl = config.dataUrl+"?type=selectcity&countrycode="+countrycode+"&provincecode="+provincecode;
						haschild = "districtUrl"; 
					}
					fieldflag = "area";
					break;
				case "districtUrl":
					if(parentArr[parentArr.length-3] != undefined){
						var countrycode = parentArr[parentArr.length-3].code;
					}
					if(countrycode == "" || countrycode == undefined){
						countrycode = "CN";
					}
					var provincecode = parentArr[parentArr.length-2].code;
					if(provincecode == "" || provincecode == undefined){
						provincecode = parentArr[parentArr.length-1].code.substr(0,2)+"0000";
					}
					searchUrl = config.dataUrl+"?type=selectdistrict&countrycode="+countrycode+"&provincecode="+provincecode+"&citycode="+parentArr[parentArr.length-1].code;
					haschild = false; 
					fieldflag = "area"
					break;
				case "searchnameUrl":
					searchUrl = config.solrData+ "?type=kwblur&fieldname=users_screen_name&keyword="+encodeURIComponent(username)+"&page="+curpage2+"&pagesize="+pagesize;
					//searchUrl = config.dataUrl + "?type=searchname&blurname="+encodeURIComponent(username)+"&page="+_this.curpage2+"&pagesize="+pagesize;
					haschild = false; 
					break;
				case "sourceidUrl":
					var field = queryfield;
					if(queryfield == undefined){
						field = "source_host";
					}
					searchUrl = config.dataUrl+"?type=getsource&queryfield="+field+"&srchostid="+srchostid+"&searchtxt="+encodeURIComponent(searchtxt)+"&page="+curpage2+"&pagesize="+pagesize;
					haschild = false; 
					break;
				case "businessUrl":
					searchUrl = config.dataUrl + "?type=getbusiness";
					haschild = false; 
					break;
				case "verifiedTypeUrl": //facet 认证类型包含,去除
					searchUrl = config.dataUrl + "?type=getverifiedtype";
					fieldflag = "verifiedtype";
					haschild = false;
					break;
				case "verifiedUrl":
					searchUrl = config.dataUrl + "?type=getverified";
					haschild = true;
					fieldflag = "verified";
					break;
				case "selectverified":
					searchUrl = config.dataUrl + "?type=selectverified";
					haschild = false;
					fieldflag = "verified";
					break;
				case "selectwelluser":
					searchUrl = config.dataUrl + "?type=selectwelluser";
					haschild = false;
					fieldflag = "verified";
					break;
				case "selectwellorg":
					searchUrl = config.dataUrl + "?type=selectwellorg";
					haschild = false;
					fieldflag = "verified";
					break;
				case "selectwellother":
					searchUrl = config.dataUrl + "?type=selectwellother";
					haschild = false;
					fieldflag = "verified";
					break;
				default:
					searchUrl = choiceVal;
					haschild = false; 
					break;
			}

			//动态数据
			ajaxRequest(searchUrl , function(data){
				if(data!=null && data.length> 0 && data[0].datalist!=null && data[0].datalist.length>0) {
					if(choiceVal == "verifiedTypeUrl"){
						var vtypearr = [];
						$.each(data[0].datalist, function(di, ditem){
							$.each(ditem, function(vi, vitem){
								if(vitem.verified != 0){
									vtypearr.push(vitem);
								}
							})
						});
						choiceCommon(vtypearr, selector, vtypearr.length);
					}
					else{
						choiceCommon(data[0].datalist, selector, data[0].totalcount);
					}
				}
				else{
					var phtml = parentHtml();
					if(phtml != ""){
						$(selector).empty().append(phtml);
					}
					else{
						$(selector).text("查询数据不存在！");
					}
				}
			}, "json", function(){}, function(){ 
				$(selector).html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/>");
			}, function(){$("#waitimg").css({display:"none"});});
		}
		else{
			choiceCommon(choiceVal, selector);
		}
	}
	var parentHtml = function(){
		var ahtml = "";
		if(parentArr.length>0 && parentArr[parentArr.length-1].name!="" && parentArr[parentArr.length-1].name!=undefined && haschild != "false"){
			var slco = selectedStatus(parentArr[parentArr.length-1].code);
			var parentname = parentArr[parentArr.length-1].name;
			if(pname != undefined && pname == 1){
				parentname = "全部";
			}
			ahtml = "<a "+slco+" onclick='sendItem(this, event)' haschild='false' code="+parentArr[parentArr.length-1].code+" name="+parentArr[parentArr.length-1].name+">"+parentname+"</a><span style='cursor:pointer;float:left;margin:2px;width: 60%;' onclick='sendItem(this, event)' goparent=true haschild="+parentArr[parentArr.length-1].searchurl+" code="+parentArr[parentArr.length-1].code+" name="+parentArr[parentArr.length-1].name+">[返回上一级]</span>";
		}
		return ahtml;

	}
	var selectedType = function(code, emotype){
		var flag = false; //已选择
		if(selectedVal != undefined && selectedVal.length>0){
			$.each(selectedVal, function(k,v){
				if(v.code == code){
					if(emotype != undefined){
						if(v.emotype == emotype){
							flag = 1;
							return false;
						}
					}
					else{
						flag = 1;
						return false;
					}
				}
				else if(isChild(v.code, code)){
					flag = 2;
				}
			//当选择的项为要显示的项的子项 ,显示的项半选中状态
			});
		}
		return flag;
	}
	var selectedStatus = function(code, emotype){
		var flag = selectedType(code, emotype);
		var slco = "";
		if(flag == 1){
			slco = "class='selected'";
		}
		else if(flag == 2){
			slco = "class='halfselected'";
		}
		else{
			slco = "class='notselected'";
		}
		return slco
	}
	var choiceCommon = function(choiceData, selector, totalcount){ //choiceData = [{name:"zhang", code:'1111'},{name:"li", code:'2222'}];
		var acchtml = "";
		acchtml += parentHtml();
		$.each(choiceData, function(i, m){
			var slco = selectedStatus(m.code, m.emotype);
			if(needcheckchild == undefined || needcheckchild == true){
				if(m.haschild == undefined){
					haschild = checkChildNode(m.code);
				}
				else{
					haschild = m.haschild; 
				}
			}
			else{
				haschild = false;
			}
			var dname = m.name;
			var emotypeattr = "";
			if(m.emotype != undefined){
				emotypeattr = "emotype='"+m.emotype+"'";
				dname  = m.name+"("+emoval2text(m.emotype)+")";
			}
			//判断是否为url地址
			var urlstyleattr = "";
			//根据字体大小计算字符长度, 12为默认12px 120为a标签长度120px
			if(getStringPx(dname, 12) > 120){
				urlstyleattr = "style='overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;' title='"+dname+"' ";
			}
			acchtml += "<a "+slco+" onclick='sendItem(this, event)' haschild="+haschild+" "+emotypeattr+" code='"+m.code+"' "+urlstyleattr+" name='"+m.name+"'>"+dname+"</a>";
		});
		$(selector).empty().append(acchtml);
		$("#waitimg").css({display:"none"});
		//显示分页
		if(totalcount != undefined && totalcount > pagesize){
			pageDisplay(totalcount, searchCommon, "commonpage", pagesize, curpage2);
		}
		else{
			$("#commonpage").empty();
		}
	}
	var getParent = function(code){
		if(fieldflag == "verified"){
			return getVerifiedParent(code);
		}
		else if(fieldflag == "area"){
			return getAreaParent(code);
		}
		else{
			return code;
		}
	}
	var getVerifiedParent = function(code){
		var par = "";
		if(code.toString().indexOf("verify_") > -1){
			par = code;
		}
		else{
			var c = parseInt(code, 10);
			if(c>=0 && c<=7){
				par = "verify_1"; //认证
			}
			else if(c >= 200 && c<= 280){
				par = "verify_2"; //达人
			}
			else if(c == -2 || (c>=0 && c<=7)){
				par = "verify_3"; //企业机构
			}
			else{
				par = "verify_4"; //其他
			}
		}
		return par;
	}
	var getAreaParent = function(code){
		var parcode = code;
		if(code.length == 6){
			var f = code.substr(0,2);
			var s = code.substr(2,2);
			var t = code.substr(4,2);
			if(s!='00' && t!='00'){ //县区
				//直辖市特殊处理
				if(f == "11" || f == "12" || f == "31" || f == "50"){
					parcode = code.substr(0,2)+"0000";
				}
				else{
					parcode = code.substr(0,4)+"00";
				}
			}
			else if(s!='00' && t=='00'){
				parcode = code.substr(0,2)+"0000";
			}
			else if(s=='00' && t=='00'){
				parcode = "CN";
			}
		}
		return parcode;
	}
	var isChild = function(code, pcode){
		if(fieldflag == "verified"){
			return isVerifiedChild(code, pcode);
		}
		else if(fieldflag == "area"){
			return isAreaChild(code, pcode);
		}
		else{
			return false;
		}
	}
	//验证认证类型的子级
	var isVerifiedChild = function(code, pcode){
		var flag = false;
		if(code.toString().indexOf("verify_") > -1){
			flag = false
		}
		else{
			if(pcode.toString().indexOf("verify_") > -1){
				var tmpcode = pcode.split("verify_");
				if(0 == tmpcode[1]){
					flag = false;
				}
				else if(1 == tmpcode[1]){
					var c = parseInt(code, 10);
					if(c >=0 && c <=7){
						flag = true;
					}
				}
				else if(2 == tmpcode[1]){
					var c = parseInt(code, 10);
					if(c >= 200 && c <= 280){
						flag = true;
					}
				}
				else if(3 == tmpcode[1]){
					var c = parseInt(code, 10);
					if(c == -2 || (c >=1 && c <=7)){
						flag = true;
					}
				}
			}
		}
		return flag;
	}
	//判断第一项是否为第二项子项
	var isAreaChild = function(code, pcode){
		var flag = false;
		var pf = pcode.substr(0, 2);
		var ps = pcode.substr(2, 2);
		var pt = pcode.substr(4, 2);

		var cf = code.substr(0, 2);
		var cs = code.substr(2, 2);
		var ct = code.substr(4, 2);
		if(pf == cf){ //省份相同
			if(ps == cs){ //城市相同
				if(pt==ct){ //县区相同
					//两个地区相同
					flag = true;
				}
				else{//属于同一个城市
					if(pt == "00"){
						flag = true; //code 属于 pcode
					}
				}
			}
			else{//属于同一个省
				if(ps == "00"){
					flag = true;
				}
			}
		}
		else{ //不是同一个省
			if(pcode == "CN"){ //都属于中国
				flag = true;
			}
		}
		return flag;
	}
	var checkChildNode = function(code){
		var hc = false;
		if(code.length == 6){
			var f = code.substr(0,2);
			var s = code.substr(2,2);
			var t = code.substr(4,2);
			if(s == '00' && t == '00'){
				hc = "cityUrl";
			}
			else if(s!='00' && t=='00'){
				hc = "districtUrl";
			}
			else if(s!='00' && t!='00'){
				hc = false;
			}
		}
		else if(code.length == 2){
			var len = code.length;
			var flag = true;
			for(var i=0; i< len; i++){
				if(!(code.charCodeAt(i)>=65 && code.charCodeAt(i)<=90)){//大写英文
					flag = false;
				}
			}
			if(flag){ //国家
				hc = "provinceUrl";
			}
			else{
				hc = false;
			}
		}
		return hc;
	}

	//初始化
	initCommonSelect(); 
}
//包括两种情况一种是指定 显示位置,可浮动的
//指定显示位置的点击事件在外部,
//可以调要函数返回结果,或使用回调函数
//需要传入ele参数确定浮动是放置位置
function myCommonEmotionChk(target, ele, successResult){
	var _this = this;
	this.target = target;
	this.ele = ele;
	this.commonemotionChk = function(){
		var emohtml = "";
		if(_this.target){
			emohtml += '<div id="commonemotiondiv"  style="font-size: 12px;" class="search">'
		}
		else{
			emohtml += '<div id="commonemotiondiv" style="position:absolute;display:none;z-index:9999;background-color:white;border:1px solid blue;font-size: 12px; width:264px;">'
		}
		emohtml +='<span id="commonemotionspan">';
		emohtml +='<input type="checkbox" name="commonemotion" id="commonemotion1" value="1" class="outborder" /><label for="commonemotion1">反对</label>';
		emohtml +='<input type="checkbox" name="commonemotion" id="commonemotion2" value="2" class="outborder" /><label for="commonemotion2">负面</label>';
		emohtml +='<input type="checkbox" name="commonemotion" id="commonemotion3" value="3" class="outborder" /><label for="commonemotion3">中性</label>';
		emohtml +='<input type="checkbox" name="commonemotion" id="commonemotion4" value="4" class="outborder" /><label for="commonemotion4">正面</label>';
		emohtml +='<input type="checkbox" name="commonemotion" id="commonemotion5" value="5" class="outborder" /><label for="commonemotion5">赞赏</label>';
		emohtml +='<input type="checkbox" name="commonemotion" id="commonemotionall" value="*" class="outborder" /><label for="commonemotionall">全部</label>';
		if(!_this.target){
			emohtml +='<br/><a id="itememo" style="cursor:pointer;">确定</a>';
		}
		emohtml +='</span></div>';
		if($("#commonemotiondiv").length == 0){
			if(!_this.target){
				//$(emohtml).insertAfter("body");
				$("body").append(emohtml);
			}
			else{
				$(_this.target).before(emohtml);
			};
		}
		$("#commonemotiondiv input[name=commonemotion]").bind("click", function(){
			var itemid = $(this).attr("id");
			if(itemid == "commonemotionall"){
				if($(this).prop("checked") == true){
					$("#commonemotiondiv input[name=commonemotion]").attr("checked", true);
				}
				else{
					$("#commonemotiondiv input[name=commonemotion]").attr("checked", false);
				}
			}
			else{
				var chklen = $("#commonemotiondiv input[name=commonemotion]:checked").length;
				var alllen = $("#commonemotiondiv input[name=commonemotion]").length;
				if($(this).prop("checked") == true){
					if(chklen == alllen-1){
						$("#commonemotiondiv input[name=commonemotion]").attr("checked", true);
					}
				}
				else{
					$("#commonemotiondiv input[name=commonemotion][id=commonemotionall]").attr("checked", false);
				}
			}
		});
		if(!_this.target){
			var ex,ey;
			ex = $(_this.ele).offset().left + 30;
			ey = $(_this.ele).offset().top - $("#commonemotiondiv").height();
			$("#commonemotiondiv").css({top:ey+"px", left:ex+"px", display:"block"});
		}

		$("#commonemotionspan input[name=commonemotion]").attr("checked", false);
		$("#itememo").unbind("click");
		$("#itememo").bind("click", function(){
			var result = _this.commonemotionResult();
			//选择结果
			if(result!= false){
				successResult(result);
			}
		});
	}
	this.commonemotionResult = function(){
		var emoObj = {};
		var rtArr = [];
		var rvArr = [];
		var chklen = $("#commonemotionspan input[name=commonemotion]:checked").length;
		if(chklen == 0){
			alert("请选择情感!");
			return false;
		}
		$("#commonemotionspan input[name=commonemotion]:checked").each(function(){
			var rtid = $(this).attr("id");
			if(rtid != "commonemotionall"){ //全部checkbox 不添加到数组
				var rt = $("#commonemotiondiv label[for="+rtid+"]").text();
				rtArr.push(rt);
				var rv = $(this).val();
				rvArr.push(rv);
			}
		});
		var alllen = $("#commonemotionspan input[name=commonemotion]").length;
		if(rvArr.length>0){
			if(rvArr.length == alllen-1){
				emoObj.name = "(全部)";
				emoObj.code ="*";
			}
			else{
				emoObj.name ="("+rtArr.join(",")+")";
				emoObj.code =""+rvArr.join(",");
			}
		}
		return emoObj;
	}
	this.commonemotionClose = function(){
		$("#commonemotiondiv").css("display", "none");
	}
	_this.commonemotionChk();
}
function myBatchAdd(successResult){
	var initBatchAdd = function(){
		$("#batchbox").remove();
		var batchhtml = "";
		batchhtml += '<div>';
		batchhtml += '<textarea rows="20" cols="50" id="batchdata"></textarea>';
		batchhtml += '<div><input class="buttono" type="button" value="添加" id="batchaddbtn" /></div>';
		batchhtml += '</div>';
		$("<div id='batchbox'></div>").insertAfter("body");
		$("#batchbox").append(batchhtml);
		$("#batchbox").dialog({
			autoOpen: true,
			modal:true,
			width:500,
			close:function(){
			}
		});
		$("#batchaddbtn").bind("click", function(){
			var bd= $("#batchdata").val();
			commonFun.trim(bd);
			var bdArr = bd.split('\n');
			successResult(commonFun.arrytrim(bdArr));//add arrytrim by zq:2016-6-12
			$("#batchbox").dialog("close");
		});
	};
	initBatchAdd();
};
/*
 * @brief 多级特征分类的添加
 * @param String choiceVal 查询地址的映射
 * @param String 表头名称
 * @param String ele jQuery selector 控件放置位置 
 * @param String isnot 非
 * @param String pname 父级名称
 * @param String needcheckchild 检查是否子类
 * @param String showselectallbtn 是否有全选按钮
 * @return 无
 * @author zuoqian
 * @date 2016-6-14
 * @change 2016-6-30 Bert 
 * */
function addNewTag(choiceValue, showName, ele, isnot, pname, needcheckchild, showselectallbtn){
	var page;
	var pagesize = 20;
	var curpage2;
	var haschild = false;
	var choiceVal = choiceValue;
	var parentArr = [];
	var goparent = false;
	var successFun;
	var emochk;
	var searchtxt = "";
	var fieldflag; //字段标识, 查询的字段标识,再判断子节点时用到 isChild
	var initNewTagSelect = function(){
		if(showName == undefined){
			showName = "";
		}
		$("#choicebox").remove();
		var newtaghtml = "";
		//newtaghtml = "请选择种类:<input type='text' id='searchtxt' name='searchtxt' /><input type='button' name='searchbtn' id='searchbtn' value='搜索' />(使用*模糊搜索)";
		newtaghtml = "请选择种类:<span id='searchtxt' name='searchtxt'></span>";
		newtaghtml += "<fieldset>";
		newtaghtml +="<legend>"+showName+"</legend>";
		newtaghtml +="<div id='groupnewtag'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/></div>";
		newtaghtml +="<div id='newtagpage' style='margin-top:2px;float:left;width:100%;margin:5px;'></div>";
		if(showselectallbtn){
			newtaghtml +="<div class='search' id='newtagselectallbtndiv' style='float:left;width:100%;'><input id='newtagselectallbtn' name='newtagselectallbtn' type='button' value='全选' /> <input id='newtagunselectallbtn' name='newtagunselectallbtn' type='button' value='反选' /> <input id='newtagunselectnonebtn' name='newtagunselectnonebtn' type='button' value='清空' /></div>";
		}
		newtaghtml +="</fieldset>";
		/*newtaghtml += "<fieldset>";
		newtaghtml +="<legend>已选择的"+showName+"</legend>";
		newtaghtml +="<div id='selectednewtags'></div>";
		newtaghtml +="</fieldset>";*/
		newtaghtml +="<div class='search'><input id='newtagsubmit' name='newtagsubmit' type='button' value='确定' style='display:none'/>   <input id='canclesubmit' name='canclesubmit' type='button' value='取消' /></div>";

		var newtagboxhtml = '<div id="choicebox" title="'+showName+'列表" style="position:absolute;display:none;z-index:9999;background-color:white;border:1px solid blue;font-size: 12px; width:490px;"></div>';
		if(ele != undefined){
			$("body").append(newtagboxhtml);

			$("#choicebox").append(newtaghtml);
		}
		else{
			$("<div id='choicebox' title='"+showName+"列表'></div>").insertAfter("body");
			$("#choicebox").append(newtaghtml);
			$("#choicebox").dialog({
				autoOpen: false,
				modal:true,
				width:550,
				close:function(){
					$("#newtagemotiondiv").css("display", "none");
				}
			});
		}

		//已选择的项初始化
		selectedNewTag();
		if(!isArray(choiceVal)){  //可选择项为url
			//模拟第一次点击事件
			this.sendItemTag("<a haschild='"+choiceVal+"' code='' name=''></a>");
		}
		else{ //可选择想为静态数组
			//第一次请求为parent赋值
			var parentObj = {};
			parentObj.name = "";
			parentObj.code = "";
			parentObj.searchurl = "";
			parentArr.push(parentObj);
			//数据初始化
			searchNewTag(pagesize, 1, "#groupnewtag"); //同时为分页回调函数
		}
		if(ele != undefined){
			var ex,ey;
			ex = $(ele).offset().left + 130;
			if(ex > ($(document.body).width() - $("#choicebox").width())){
				ex = $(document.body).width() - $("#choicebox").width() -10;
			}
			ey = $(ele).offset().top/* - $("#newtagemotiondiv").height()*/;
			//$("#choicebox").css({top:ey+"px", left:ex+"px", display:"block"});
			$("#choicebox").slideDown("slow").css({
				'display':'block',
				'left': ex+"px",
				'top': ey+"px"
			});
		}
		else{
			$("#choicebox").dialog("open");
		}
		$("#searchbtn").bind("click", function(){
			searchtxt = $("#searchtxt").val();
			searchNewTag(pagesize, 1, "#groupnewtag"); //同时为分页回调函数
		});
		//反选
		$("#newtagunselectallbtn").bind("click", function(){
			$("#groupnewtag").find("a").each(function(i, item){
				sendItem($(item));
			});
		});
		//全选
		$("#newtagselectallbtn").bind("click", function(){
			$("#selectednewtags").empty();
			$("#groupnewtag").find("a").each(function(i, item){
				sendItem($(item));
			});
		});
		//清空
		$("#newtagunselectnonebtn").bind("click", function(i, item){
			$("#selectednewtags .cancleitem").each(function(i, m){
				cancleItem($(m));
			});
		});

		$("#newtagsubmit").unbind("click");
		$("#newtagsubmit").bind("click", function(){
			var feature_father_guid = $("#myaddnewtag").attr("feature_father_guid");
			var feature_keyword = $("#myaddnewtag").val();

			if(feature_keyword==""){
				$("#myaddnewtag").css("border-color","red");
				return false;
			}
			//var feature_field = $("#myaddnewtag").attr("feature_field");
			//var resultArr = [];
		    var data={"type":"addfeatureword","feature_father_guid":feature_father_guid,"feature_keyword":[feature_keyword],"feature_field":["text"]};
			$.ajax({
				url:config.solrData,
				//data:{type:"searchcount",keyword:keyword,origid:encodeURIComponent(origid), pathtype:_this.showid},
				type:"post",
				data:JSON.stringify(data),
				dataType:"json",
				contentType:"application/json",
				success:function(data){
			     if(data.flag){
					 alert("添加成功");
				 }else{
					 alert("添加失败");
				 }
				},
				error:function(){
					alert("数据请求异常");
				}
			});

			$("#newtagemotiondiv").css("display", "none");
			if(ele != undefined){
				//$("#choicebox").css("display", "none");
				$("#choicebox").slideUp("slow");
			}
			else{
				$("#choicebox").dialog("close");
			}
		});
		$("#canclesubmit").bind("click", function(){
			$("#newtagemotiondiv").css("display", "none");
			$("#selectednewtags").empty();
			if(ele != undefined){
				//$("#choicebox").css("display", "none");
				$("#choicebox").slideUp("slow");
			}
			else{
				$("#choicebox").dialog("close");
			}
		});
	};
/*	this.cancleItem = function(ele){
		$(ele).parent().remove();
		var delcode = $(ele).attr("code");
		var delemo = $(ele).attr("emotype");
		if(delemo != undefined && !isemo){
			$("#groupnewtag a[code='"+delcode+"'][emotype='"+delemo+"']").removeClass().addClass("notselected");
			//改变父项的选择状态
			deleteParent(delcode, delemo);
		}
		else{
			$("#groupnewtag a[code='"+delcode+"']").removeClass().addClass("notselected");
			//改变父项的选择状态
			deleteParent(delcode);
		}
	};
	//递归删除
	var deleteParent = function(delcode, delemo){
		//子项全部删除后,改变父项选中状态
		var parcode = getParent(delcode);
		var childflag = true;
		$("#selectednewtags .sltitem").each(function(i, m){
			var code = $(m).attr("code");
			if(code == parcode){
				if(delemo != undefined){
					var emo = $(m).attr("emotype");
					if(emo == delemo){
						childflag = 1;
						return false;
					}
					else{
						childflag = 3;
					}
				}
				else{
					childflag = 1;
					return false;
				}
			}
			else if(isChild(code, parcode)){  //已选择项中有删除项的孩子
				childflag = 2;
				return false;
			}
			else{
				childflag = 3;
			}
		});
		if(childflag === 1){
			$("#groupnewtag a[code='"+parcode+"']").removeClass().addClass("selected");
		}
		else if(childflag == 2){
			$("#groupnewtag a[code='"+parcode+"']").removeClass().addClass("halfselected");
		}
		else if(childflag == 3){ //没有子项时
			if(delemo!=undefined && !isemo){
				$("#groupnewtag a[code='"+parcode+"'][emotype="+delemo+"]").removeClass().addClass("notselected");
				var pclass = $("#groupnewtag a[code='"+parcode+"'][emotype="+delemo+"]").hasClass("notselected");
			}
			else{
				$("#groupnewtag a[code='"+parcode+"']").removeClass().addClass("notselected");
				var pclass = $("#groupnewtag a[code='"+parcode+"']").hasClass("notselected");
			}
			if(fieldflag == "area" && parcode != "CN" && !pclass){
				deleteParent(parcode, delemo);
			}
		}
	};*/
	this.getSelectedVal = function(){
		var resultArr = [];
		$("#selectednewtags .sltitem").each(function(i, m){
			var itemObj = {};
			itemObj.code = $(this).attr("code");
			itemObj.name = $(this).text();
			if($(this).attr("emotype") != undefined){
				itemObj.emotype = $(this).attr("emotype");
			}
			if($(this).attr("exclude") != undefined){
				itemObj.exclude = $(this).attr("exclude");
			}
			resultArr.push(itemObj);
		});
		return  resultArr;
	}
	/*this.sendItem = function(ele, eve){
		selectedVal = getSelectedVal();

		//var elename = $(ele).text();
		var elename = $(ele).attr("name");
		var elecode = $(ele).attr("code");
		var eleemo = $(ele).attr("emotype");
		var hc = $(ele).attr("haschild");
		if(hc != "false"){  //当有子节点时再次弹出层
			if($(ele).attr("goparent") != undefined && $(ele).attr("goparent")){  //删除
				parentArr.splice(parentArr.length-1, 1);
			}
			else{
				var parentObj = {};
				parentObj.name = elename;
				parentObj.code = elecode;
				parentObj.searchurl = hc;
				parentArr.push(parentObj);
			}
			//choiceVal = hc;  //更改取数据url;
			choiceVal = parentArr[parentArr.length-1].searchurl;
			if(choiceVal == ""){ //为空时从新赋值 ,choiceVal为静态数组时第一级parent存储 searchurl为 "";
				choiceVal = choiceValue;
			}
			searchNewTag(pagesize, 1, "#groupnewtag");

			//更改样式
			$(ele).removeClass().addClass("halfselected");
		}
		else{
			if(isemo!=undefined && (isemo == true || isemo == "true")){  //需要选择情感
				myNewTagEmotionChk("", ele, function(data){
					var n = elename += data.name;
					addItem(ele, n, elecode, data.code);
					$("#newtagemotiondiv").css({display:"none"});
				});
			}
			else{
				var newname = elename;
				if(eleemo != undefined){
					newname = elename+"("+emoval2text(eleemo)+")";
				}
				addItem(ele, newname, elecode, eleemo);
			}
		}
	};*/
	//begin:add by zuoqian:2016-6-14
	this.sendItemTag = function(ele, eve){
		//selectedVal = getSelectedVal();
		//var elename = $(ele).text();
		var elename = $(ele).attr("name");
		var elefather = $(ele).attr("father_id");
		var hc = $(ele).attr("haschild");
		if( $(ele).text()!="[返回上一级]"){
			if($("#searchtxt").text()!="请选择"&&$("#searchtxt").text()!=""){
				$("#searchtxt").append("<span>-"+$(ele).text()+"</span>");
			}else if($("#searchtxt").text()==""){
				$("#searchtxt").html("请选择");
			}else{
				$("#searchtxt").html("").html("<span oldlevel="+$(ele).text()+">"+$(ele).text()+"</span>");
			}
		}else{
			if($("#searchtxt").children("span").length>1){
				$("#searchtxt").children("span").last().remove();
			}else if($("#searchtxt").children("span").length==1){
				$("#searchtxt").html("").html("请选择");
			}
		}
		if(hc != "false"){  //当有节点时再次弹出层 是否有子级
			if($(ele).attr("goparent") != undefined && $(ele).attr("goparent")){
				  parentArr.splice(parentArr.length-1, 1);
			}
			else{
				var parentObj = {};
				parentObj.name = elename;
				parentObj.parent = elefather;
				parentObj.searchurl = hc;
				parentArr.push(parentObj);
			}
			//choiceVal = hc;  //更改取数据url;
			choiceVal = parentArr[parentArr.length-1].searchurl;
			if(choiceVal == ""){ //为空时从新赋值 ,choiceVal为静态数组时第一级parent存储 searchurl为 "";
				choiceVal = choiceValue;
			}
			searchNewTag(pagesize, 1, "#groupnewtag");

			//更改样式
			//$(ele).removeClass().addClass("halfselected");
		}
		/*else{
			var feature_field=$(ele).attr("feature_field");
			var html = "请填写新标签:<input name='myaddnewtag' id='myaddnewtag' feature_father_guid='"+elefather+"' feature_keyword='"+elename+"' feature_field='"+feature_field+"' type='text'/>"
			var ifadd = true;
			var ahtml = "<div style='cursor:pointer;margin:2px;'onclick='sendItemTag(this, event,true)' goparent=true hasnosearchurl=true haschild="+elefather+" name="+elename+">[返回上一级]</div>";
			$(ele).closest("fieldset").find("#groupnewtag").html("").append(ahtml+html);
			var parentObj = {};
			parentObj.name = elename;
			parentObj.parent = elefather;
			parentObj.searchurl = hc;
			parentArr.push(parentObj);
			//$("#newtagsubmit").show();
		}*/
	};
	//end:add by zuoqian:2016-6-14
	//ele元素本身用于更改样式, newname newcode拼接了情感值
	var addItem = function(ele, newname, newcode, emotype){
		$(ele).removeClass().addClass("selected");
		var pcode = getParent(newcode);
		if(pcode != newcode){
			$("#groupnewtag a[code='"+pcode+"']").removeClass().addClass("halfselected");
		}
		var wflag = true;
		$("#selectednewtags .sltitem").each(function(i, m){
			if(emotype != undefined){
				var sltcode = $(this).attr("code");
				var sltemo = $(this).attr("emotype");
				if(newcode == sltcode && emotype == sltemo){
					$(this).parent().remove();
					$(ele).removeClass().addClass("notselected");
					deleteParent(newcode, sltemo);
					wflag = false;
				}
			}
			else{
				var sltcode = $(this).attr("code");
				if(newcode == sltcode){
					$(this).parent().remove();
					$(ele).removeClass().addClass("notselected");
					deleteParent(newcode);
					//$(ele).css("background-color", "white");
					wflag = false;
				}
			}
		});
		if(wflag){
			var spanutilhtml = createUtilSpanHtml('sltitem', newname, newcode, isnot, undefined, 'cancleItem', undefined, emotype);
			$("#selectednewtags").append(spanutilhtml);
		}
	};
	var selectedNewTag = function(){
		var slthtml = "";
		if(isnot == undefined){
			isnot = true;
		}
		/*$.each(selectedVal, function(i,m){
			slthtml += createUtilSpanHtml('sltitem', m.name, m.code, isnot, m.exclude, 'cancleItem', undefined, m.emotype);
		});*/
		$("#selectednewtags").append(slthtml);
	}
	var searchNewTag = function(pagesize, curpage, selector){
		if(selector == undefined){
			selector = "#groupnewtag";
		}
		if(!isArray(choiceVal)){
			var searchUrl = "";
			if(curpage == '' || curpage == null || curpage == undefined ){
				curpage2 = 1;
			}
			else{
				curpage2 = curpage;
			}
			switch(choiceVal){
				case "addnewtags":
					searchUrl = config.solrData+"?type=selectfeaturepclass&fieldname=text";
					break;
				default:
					searchUrl = config.solrData+"?type=addnewtags&feature_father_guid="+choiceVal;
					break;
			}

			//动态数据
			//alert(searchUrl)
			ajaxRequest(searchUrl , function(data){
				if(data!=null && data.length> 0 && data[0].datalist!=null && data[0].datalist.length>0) {
                    choiceNewTag(data[0].datalist, selector, data[0].totalcount);
				}
				else{
					var phtml = parentHtml();
					if(phtml != ""){
						$(selector).empty().append(phtml).append("您查询数据不存在！");
					}
					else{
						//var ahtml = "<div style='cursor:pointer;margin:2px;' onclick='sendItemTag(this, event,false)' goparent=true haschild="+parentArr[parentArr.length-1].searchurl+" name="+parentArr[parentArr.length-1].name+">[返回上一级]</div>";
						var ahtml = "<div style='margin-bottom:6px;' onclick='sendItemTag(this, event)' goparent=true haschild="+parentArr[parentArr.length-1].parent+" father_id="+parentArr[parentArr.length-1].parent+" name="+parentArr[parentArr.length-1].name+"><span style='cursor:pointer'>[返回上一级]</span></div>";
						if(parentArr[parentArr.length-1].parent!=undefined){
							$(selector).html(ahtml+"查询数据不存在！");
						}else{
							$(selector).html("查询数据不存在！");
						}

					}
				}
			}, "json", function(){}, function(){
				$(selector).html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/>");
			}, function(){$("#waitimg").css({display:"none"}),null,"true"});
		}
		else{
			choiceNewTag(choiceVal, selector);
		}
	}
	var parentHtml = function(){
		var ahtml = "";
		if(parentArr.length>0 && parentArr[parentArr.length-1].name!="" && parentArr[parentArr.length-1].name!=undefined && haschild != "false"){
			var slco = selectedStatus(parentArr[parentArr.length-1].code);
			var parentname = parentArr[parentArr.length-1].name;
			if(pname != undefined && pname == 1){
				parentname = "全部";
			}
			ahtml = "<div style='margin-bottom:6px;' onclick='sendItemTag(this, event)' goparent=true haschild="+parentArr[parentArr.length-1].parent+" father_id="+parentArr[parentArr.length-1].parent+" name="+parentArr[parentArr.length-1].name+"><span style='cursor:pointer'>[返回上一级]</span></div>";
		}
		return ahtml;

	}
	/*var selectedType = function(code, emotype){
		var flag = false; //已选择
		if(selectedVal != undefined && selectedVal.length>0){
			$.each(selectedVal, function(k,v){
				if(v.code == code){
					if(emotype != undefined){
						if(v.emotype == emotype){
							flag = 1;
							return false;
						}
					}
					else{
						flag = 1;
						return false;
					}
				}
				else if(isChild(v.code, code)){
					flag = 2;
				}
				//当选择的项为要显示的项的子项 ,显示的项半选中状态
			});
		}
		return flag;
	}*/
	var selectedStatus = function(code, emotype){
		//var flag = selectedType(code, emotype);
		var flag = "";
		var slco = "";
		if(flag == 1){
			slco = "class='selected'";
		}
		else if(flag == 2){
			slco = "class='halfselected'";
		}
		else{
			slco = "class='notselected'";
		}
		return slco
	}
	var choiceNewTag = function(choiceData, selector, totalcount){ //choiceData = [{name:"zhang", code:'1111'},{name:"li", code:'2222'}];
		var acchtml = "";
		acchtml += parentHtml();
		var isshowinput = false;
		var elefather = "";
		$.each(choiceData, function(i, m){
			var slco = selectedStatus(m.code, m.emotype);
			if(needcheckchild == undefined || needcheckchild == true){
				if(m.feature_field == undefined){
					haschild = m.guid;
				}
				else{
					haschild = false;
				};
			}
			else{
				haschild = false;
			}
			var dname = m.feature_class;//var dname = m.name
			var emotypeattr = "";
			//判断是否为url地址
			var urlstyleattr = "";
			//根据字体大小计算字符长度, 12为默认12px 120为a标签长度120px
			if(getStringPx(dname, 12) > 120){
				urlstyleattr = "style='overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;' title='"+dname+"' ";
			};
            if("feature_keyword" in m){
				isshowinput = true;
				var clnopoiner="style='margin:2px;width:150px;float:left'"
				elefather = m.feature_father_guid;
				acchtml += "<a "+clnopoiner+" haschild="+haschild+" name="+dname+" feature_field="+m.feature_field+" "+emotypeattr+" "+urlstyleattr+" father_id='"+m.feature_father_guid+"'>"+dname+"</a>";
			}
            else{
				acchtml += "<a "+slco+" onclick='sendItemTag(this, event)' haschild="+haschild+" name="+dname+" "+emotypeattr+" "+urlstyleattr+" father_id='"+m.feature_father_guid+"'>"+dname+"</a>";
			}
		});
		if(isshowinput){
			acchtml += "<div style='clear:both;padding-top:6px'>请填写新标签:<input name='myaddnewtag' id='myaddnewtag' feature_father_guid='"+elefather+"' type='text'/></div>"
			$("#newtagsubmit").show();
		}else{
			$("#newtagsubmit").hide();
		}
		$(selector).empty().append(acchtml);

		$("#waitimg").css({display:"none"});
		//显示分页
		if(totalcount != undefined && totalcount > pagesize){
			pageDisplay(totalcount, searchNewTag, "newtagpage", pagesize, curpage2);
		}
		else{
			$("#newtagpage").empty();
		}
	}
	var getParent = function(code){
		if(fieldflag == "verified"){
			return getVerifiedParent(code);
		}
		else if(fieldflag == "area"){
			return getAreaParent(code);
		}
		else{
			return code;
		}
	}
	var getVerifiedParent = function(code){
		var par = "";
		if(code.toString().indexOf("verify_") > -1){
			par = code;
		}
		else{
			var c = parseInt(code, 10);
			if(c>=0 && c<=7){
				par = "verify_1"; //认证
			}
			else if(c >= 200 && c<= 280){
				par = "verify_2"; //达人
			}
			else if(c == -2 || (c>=0 && c<=7)){
				par = "verify_3"; //企业机构
			}
			else{
				par = "verify_4"; //其他
			}
		}
		return par;
	}
	var getAreaParent = function(code){
		var parcode = code;
		if(code.length == 6){
			var f = code.substr(0,2);
			var s = code.substr(2,2);
			var t = code.substr(4,2);
			if(s!='00' && t!='00'){ //县区
				//直辖市特殊处理
				if(f == "11" || f == "12" || f == "31" || f == "50"){
					parcode = code.substr(0,2)+"0000";
				}
				else{
					parcode = code.substr(0,4)+"00";
				}
			}
			else if(s!='00' && t=='00'){
				parcode = code.substr(0,2)+"0000";
			}
			else if(s=='00' && t=='00'){
				parcode = "CN";
			}
		}
		return parcode;
	}
	var isChild = function(code, pcode){
		if(fieldflag == "verified"){
			return isVerifiedChild(code, pcode);
		}
		else if(fieldflag == "area"){
			return isAreaChild(code, pcode);
		}
		else{
			return false;
		}
	}
	//验证认证类型的子级
	var isVerifiedChild = function(code, pcode){
		var flag = false;
		if(code.toString().indexOf("verify_") > -1){
			flag = false
		}
		else{
			if(pcode.toString().indexOf("verify_") > -1){
				var tmpcode = pcode.split("verify_");
				if(0 == tmpcode[1]){
					flag = false;
				}
				else if(1 == tmpcode[1]){
					var c = parseInt(code, 10);
					if(c >=0 && c <=7){
						flag = true;
					}
				}
				else if(2 == tmpcode[1]){
					var c = parseInt(code, 10);
					if(c >= 200 && c <= 280){
						flag = true;
					}
				}
				else if(3 == tmpcode[1]){
					var c = parseInt(code, 10);
					if(c == -2 || (c >=1 && c <=7)){
						flag = true;
					}
				}
			}
		}
		return flag;
	}
	//判断第一项是否为第二项子项
	var isAreaChild = function(code, pcode){
		var flag = false;
		var pf = pcode.substr(0, 2);
		var ps = pcode.substr(2, 2);
		var pt = pcode.substr(4, 2);

		var cf = code.substr(0, 2);
		var cs = code.substr(2, 2);
		var ct = code.substr(4, 2);
		if(pf == cf){ //省份相同
			if(ps == cs){ //城市相同
				if(pt==ct){ //县区相同
					//两个地区相同
					flag = true;
				}
				else{//属于同一个城市
					if(pt == "00"){
						flag = true; //code 属于 pcode
					}
				}
			}
			else{//属于同一个省
				if(ps == "00"){
					flag = true;
				}
			}
		}
		else{ //不是同一个省
			if(pcode == "CN"){ //都属于中国
				flag = true;
			}
		}
		return flag;
	}
	var checkChildNode = function(code){
		var hc = false;
		if(code.length == 6){
			var f = code.substr(0,2);
			var s = code.substr(2,2);
			var t = code.substr(4,2);
			if(s == '00' && t == '00'){
				hc = "cityUrl";
			}
			else if(s!='00' && t=='00'){
				hc = "districtUrl";
			}
			else if(s!='00' && t!='00'){
				hc = false;
			}
		}
		else if(code.length == 2){
			var len = code.length;
			var flag = true;
			for(var i=0; i< len; i++){
				if(!(code.charCodeAt(i)>=65 && code.charCodeAt(i)<=90)){//大写英文
					flag = false;
				}
			}
			if(flag){ //国家
				hc = "provinceUrl";
			}
			else{
				hc = false;
			}
		}
		return hc;
	}

	//初始化
	initNewTagSelect();
}
