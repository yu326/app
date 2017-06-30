/*
 * classname 值标签span类名称
 * name 对应text
 * code 对应value
 * isnot 是否显示"不" 按钮
 * exclude "不"按钮 初始值, 当exclude==1 时 "不" 按钮为选中状态
 * clickfun 删除标签函数名
 * rtext 和code等效 名称不同
 * emotype 情感字段,情感值
 * btype 词模糊类型
 * */
function createUtilSpanHtml(classname, name, code, isnot, exclude, clickfun, rtext, emotype, btype){
	var rtextattr= "";
	if(rtext != undefined && rtext != ""){
		rtextattr = "rtext='"+rtext+"'"; 
	}
	var codeattr = "";
	if(code != undefined && code != ""){
		codeattr = "code='"+code+"'";
	}
	var cf = "";
	if(clickfun != undefined){
		cf = "onclick='"+clickfun+"(this)'";
	}
	var emoattr = "";
	if(emotype != undefined && emotype != ""){
		emoattr = "emotype='"+emotype+"'"
	}
	var btypeattr = "";
	if(btype != undefined && btype != ""){
		btypeattr = "btype='"+btype+"'"
	}
	var spanhtml = "";
	spanhtml += "<span class='selwordsbox'>";
	var exattr = "";
	if(isnot){
		var exstyle = "";
		if(exclude != undefined && exclude != 0){
			exattr = "exclude='"+exclude+"'";
			exstyle = "style='color:#000000;'";
		}
		spanhtml += "<a class='excludeitem' onclick='excludeUtilItemFun(this)' "+exstyle+">不</a>";
	}
	spanhtml += "<span class='"+classname+"' "+rtextattr+" "+codeattr+" "+exattr+" "+emoattr+" "+btypeattr+" >"+name+"</span>";
	spanhtml += "<a class='cancleitem' "+rtextattr+" "+codeattr+" "+cf+">×</a>";
	spanhtml += "</span>";
	return spanhtml;
}
function excludeUtilItemFun(ele){
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
function myUserIDRecommend(userid, successResult){
	var _this = this;
	var userid = userid;
	var page; //第几页
	var pagesize=50; //每页显示条数
	var curpage2;//当前页码
	var blurtype;
	var initUserIDRecommend = function(){
		$("#namesbox").remove();
		var userlistdiv = "";
		userlistdiv +="<fieldset>";
		userlistdiv +="<legend>用户列表</legend>";
		userlistdiv +="<div id='names'></div><div id='uapagestyle' style='margin-top:2px;float:left;width:100%;margin:5px;'></div>";
		userlistdiv +="</fieldset>";
		userlistdiv +="<fieldset>";
		userlistdiv +="<legend>已选择的用户</legend>";
		userlistdiv +="<div id='selecteduser'></div>";
		userlistdiv +="</fieldset>";
		userlistdiv +="<div class='search'><input id='usersubmit' name='usersubmint' type='button' value='确定' />  <input id='usercancle' name='usercancle' type='button' value='取消' /></div>";
		$("<div id='namesbox' title='用户列表'></div>").insertAfter("body");
		$("#namesbox").append(userlistdiv);

		$("#namesbox").dialog({
			autoOpen: false,
			modal:true,
			width:550,
			height:240
		});

		_this.bluroptClick();
		$("#usersubmit").bind("click", function(){
			var resultArr = [];
			$("#selecteduser .seluserid").each(function(i,m){
				var tmpobj = {};
				tmpobj.name = $(this).text();
				tmpobj.code = $(this).attr("rtext");
				if($(this).attr("exclude") != undefined){
					tmpobj.exclude = $(this).attr("exclude");
				}
				resultArr.push(tmpobj);
			});		
			/*存储最后结果,使用successResult()函数调用*/
			successResult(resultArr);
			$("#namesbox").dialog("close");
		});
		$("#usercancle").bind("click", function(){
			$("#namesbox").dialog("close");
			$("#selecteduser").empty();
		});
	}
	this.bluroptClick = function(){
		if(userid != undefined && userid!= "" && userid!=undefined){
			searchRequest(pagesize,1);
			$("#names").html("<img src='"+config.imagePath+"wait.gif'  style='padding:100px;padding-left:230px;' id='uawait'/>");
			$("#uapagestyle").empty();
			$("#namesbox").dialog("open");
		}
		else{
			alert("请输入用户ID！");
		}
	}
	var searchRequest = function(pagesize, curpage){
		if(curpage == '' || curpage == null || curpage == undefined ){
			_this.curpage2 = 1;
		}
		else{
			_this.curpage2 = curpage;
		}
		var searchnameUrl = config.solrData+"?type=searchname&userid="+userid+"&page="+_this.curpage2+"&pagesize="+pagesize;
		ajaxRequest(searchnameUrl, function(data){
			var resultData = data[0].datalist;
			if(resultData!="" && resultData!=null && resultData!=undefined){
				$("#names").empty();
				var namehtml = "";
				$.each(resultData, function(i,m){
					//var idname = JSON.stringify(m);
					var cflag = true;
					$("#selecteduser .seluserid").each(function(k,v){
						var sltw = $(this).attr("rtext");
						if(m.users_id == sltw){
							cflag = false;
						}
					});
					var sltcolor = ""
					if(cflag){
						sltcolor = "class='notselected'";
					}
					else{
						sltcolor = "class='selected'";
					}
					namehtml += "<a "+sltcolor+" rtext="+m.users_id+" onclick='sendName(this)'>"+m.users_screen_name+"</a>";
				});
				$("#names").append(namehtml);
			}
			else{
				$("#uawait").css({display:"none"});
				$("#names").text("无此相关用户！");
			}
			//显示分页
			pageDisplay(data[0].totalcount, searchRequest, "uapagestyle", pagesize, _this.curpage2);

		}, "json", function(){}, function(){
			$("#names").html("<img src='"+config.imagePath+"wait.gif'  style='padding:100px;padding-left:230px;' id='uawait'/>");
		}, function(){
			//$("#uawait").css({display:"none"});
		});
	};
	this.sendName = function(elm){
		var uservalue= $(elm).attr("rtext");
		var username = $(elm).text();
		$(elm).removeClass().addClass("selected");
		var wflag = true;
		$("#selecteduser .seluserid").each(function(i,m){
			var sltw = $(this).attr("rtext");
			if(uservalue == sltw){
				$(this).parent().remove();
				$(elm).css("background-color", "white");
				wflag = false;
			}
		});
		if(wflag){
			var spanutilhtml = createUtilSpanHtml("seluserid", username, undefined, undefined, undefined, 'cancleWord', uservalue);
			$("#selecteduser").append(spanutilhtml);
		}
		else{
			//alert("选择相同了!");
		}
	};
	this.cancleWord = function(elm){
		$(elm).parent().remove();
		var rt = $(elm).attr("rtext");
		$("#names a[rtext='"+rt+"']").css("background-color", "white");
		$("#blurqueryuser a[rtext='"+rt+"']").css("background-color", "white");
	};
	initUserIDRecommend();
}
function myUserRecommend(username, successResult, isnot){
	var _this = this;
	var username = username;
	var page; //第几页
	var pagesize=50; //每页显示条数
	var curpage2;//当前页码
	var blurtype;
	var initUserRecommend = function(){
		$("#namesbox").remove();
		var userlistdiv = "<div id='bluropt'>用户筛选 :<input type='checkbox' name='bluruseropt' checked='checked' id='bluruseropt_prefix' value='prefix' /><label for='bluruseropt_prefix'>以\""+username+"\"开头</label><input type='checkbox'  name='bluruseropt' id='bluruseropt_suffix' value='suffix' /><label for='bluruseropt_suffix'>以\""+username+"\"结尾</label><input type='checkbox' name='bluruseropt' id='bluruseropt_infix' value='infix' /><label for='bluruseropt_infix'>包含\""+username+"\"</label></div>";
		userlistdiv +="<fieldset>";
		userlistdiv +="<legend>用户列表</legend>";
		userlistdiv +="<div id='names'></div><div id='uapagestyle' style='margin-top:2px;float:left;width:100%;margin:5px;'></div>";
		userlistdiv +="<div style='clear:both;'><span>模糊查询用户:</span></div>";
		userlistdiv +="<div id='blurqueryuser'></div>";
		userlistdiv +="</fieldset>";
		userlistdiv +="<fieldset>";
		userlistdiv +="<legend>已选择的用户</legend>";
		userlistdiv +="<div id='selecteduser'></div>";
		userlistdiv +="</fieldset>";
		userlistdiv +="<div class='search'><input id='usersubmit' name='usersubmint' type='button' value='确定' />  <input id='usercancle' name='usercancle' type='button' value='取消' /></div>";

		$("<div id='namesbox' title='用户列表-包含\""+username+"\"的用户'></div>").insertAfter("body");
		$("#namesbox").append(userlistdiv);

		var blurnamehtml = ""
		blurnamehtml += "<a style='margin:2px;color:blue;width:100px;float:left;cursor:pointer;' rtext='"+username+"*' onclick='sendName(this)'>以\""+username+"\"开头</a>"
		blurnamehtml += "<a style='margin:2px;color:blue;width:100px;float:left;cursor:pointer;' rtext='*"+username+"' onclick='sendName(this)'>以\""+username+"\"结尾</a>"
		blurnamehtml += "<a style='margin:2px;color:blue;width:100px;float:left;cursor:pointer;' rtext='*"+username+"*' onclick='sendName(this)'>包含\""+username+"\"</a>"
		$("#blurqueryuser").append(blurnamehtml);
		$("#bluropt").buttonset();;
		$("#namesbox").dialog({
			autoOpen: false,
			modal:true,
			width:550,
			height:540
		});

		$("#bluropt input[name=bluruseropt]").bind("click", function(){
			var checkedlen = $("#bluropt input[name=bluruseropt]:checked").length;
			if(checkedlen==0){
				var chkid = $(this).attr("id");
				$("#bluropt label[for="+chkid+"]").addClass("ui-state-active");
				return false;
			}
			_this.bluroptClick();
		});
		_this.bluroptClick();
		$("#usersubmit").bind("click", function(){
			var resultArr = [];
			$("#selecteduser .selusername").each(function(i,m){
				var tmpobj = {};
				tmpobj.name = $(this).attr("rtext");
				if($(this).attr("exclude") != undefined){
					tmpobj.exclude = $(this).attr("exclude");
				}
				resultArr.push(tmpobj);
			});		
			/*存储最后结果,使用successResult()函数调用*/
			successResult(resultArr);
			$("#namesbox").dialog("close");
		});
		$("#usercancle").bind("click", function(){
			$("#namesbox").dialog("close");
			$("#selecteduser").empty();
		});
	}
	this.bluroptClick = function(){
		//初始时默认查询 李*;
		var blurArr = [];
		$("#bluropt input[name=bluruseropt]").each(function(){
			if($(this).prop("checked")){
				blurArr.push($(this).val());
			}
		});
		if(blurArr.length>0){
			if(blurArr.length == 1){
				blurtype = blurArr[0];
			}
			else{
				var bflag = true;
				$.each(blurArr,function(i,m){
					if(m=='infix'){
						bflag = false;
						return false;
					}
				});
				if(bflag){
					blurtype = 'prefix_suffix';
				}
				else{
					blurtype = 'infix';
				}
			}
		}

		if(username != undefined && username!= "" && username!=undefined){
			searchRequest(pagesize,1);
			$("#names").html("<img src='"+config.imagePath+"wait.gif'  style='padding:100px;padding-left:230px;' id='uawait'/>");
			$("#uapagestyle").empty();
			$("#namesbox").dialog("open");
		}
		else{
			alert("请输入用户名！");
		}
	}
	var searchRequest = function(pagesize, curpage){
		if(curpage == '' || curpage == null || curpage == undefined ){
			_this.curpage2 = 1;
		}
		else{
			_this.curpage2 = curpage;
		}
		var searchnameUrl = config.solrData+ "?type=kwblur&fieldname=users_screen_name&keyword="+encodeURIComponent(username)+"&blurtype="+blurtype+"&page="+_this.curpage2+"&pagesize="+pagesize;
		ajaxRequest(searchnameUrl, function(data){
			if(data.result != undefined && data.result == false){
				alert(data.msg);
				return false;
			}
			var resultData = data[0].datalist;
			if(resultData!="" && resultData!=null && resultData!=undefined){
				$("#names").empty();
				var namehtml = "";
				$.each(resultData, function(i,m){
					//var idname = JSON.stringify(m);
					var cflag = true;
					$("#selecteduser .selusername").each(function(k,v){
						var sltw = $(this).attr("rtext");
						if(m.screen_name == sltw){
							cflag = false;
						}
					});
					var sltcolor = ""
					if(cflag){
						sltcolor = "class='notselected'";
					}
					else{
						sltcolor = "class='selected'";
					}
					namehtml += "<a "+sltcolor+" rtext="+m+" onclick='sendName(this)'>"+m+"</a>";
				});
				$("#names").append(namehtml);
			}
			else{
				$("#uawait").css({display:"none"});
				$("#names").text("无此相关用户！");
			}
			//显示分页
			pageDisplay(data[0].totalcount, searchRequest, "uapagestyle", pagesize, _this.curpage2);

		}, "json", function(){}, function(){
			$("#names").html("<img src='"+config.imagePath+"wait.gif'  style='padding:100px;padding-left:230px;' id='uawait'/>");
		}, function(){
			//$("#uawait").css({display:"none"});
		});
	};
	this.sendName = function(elm){
		var uservalue= $(elm).attr("rtext");
		var username = $(elm).text();
		$(elm).removeClass().addClass("selected");
		var wflag = true;
		$("#selecteduser .selusername").each(function(i,m){
			var sltw = $(this).attr("rtext");
			if(uservalue == sltw){
				$(this).parent().remove();
				$(elm).css("background-color", "white");
				wflag = false;
			}
		});
		if(wflag){
			if(isnot == undefined){ //用户模型的没有not功能, 调用推荐时, isnot 为false
				isnot = true;
			}
			var spanutilhtml = createUtilSpanHtml("selusername", username, undefined, isnot, undefined, 'cancleWord', uservalue);
			$("#selecteduser").append(spanutilhtml);
		}
		else{
			//alert("选择相同了!");
		}
	};
	this.cancleWord = function(elm){
		$(elm).parent().remove();
		var rt = $(elm).attr("rtext");
		$("#names a[rtext='"+rt+"']").css("background-color", "white");
		$("#blurqueryuser a[rtext='"+rt+"']").css("background-color", "white");
	};
	initUserRecommend();
}

function myAreaSelect(successResult, selectedVal){
	var _this = this;
	var sltVal = selectedVal;
	var areaSucc = successResult;
	var countrycode = 'CN';
	var initAreaSelect = function(){
		$("#areaselecthidediv").remove();
		var hidediv ="<div id='areaselectslttip'>";
		hidediv +="<h3>";
		hidediv +="<span>提示：</span><b><input type='button' value='确定' id='areaselecttipsmt'>&nbsp;&nbsp;<input type='button' value='清空'></b>";
		hidediv +="</h3>";
		hidediv +="<p>当您选择以下的城市时，将会获得更为准确的搜索结果<br>请选择：(点击省市名称可选具体地区)</p>";
		hidediv +="</div>";
		hidediv +="<div id='areaselectselectedbox' style='display:none;'>";
		hidediv +="<h3>";
		hidediv +="<span>您选择的地区是：</span><b><input type='button' value='确定' id='areaselectsmtarea'>&nbsp;&nbsp;<input type='button' value='清空' id='areaselectresetarea'></b>";
		hidediv +="</h3>";
		hidediv +="<div id='areaselectselectedarea'></div>";
		hidediv +="</div>";
		hidediv +="<div>";
		hidediv +="<h2>";
		hidediv +="<h3><span>省市列表：</span></h3>";
		hidediv +="</h2>";
		hidediv +="<ul id='areaselectallprocity'  style='list-style-type:none;padding-left:0;'>";
		hidediv +="<li><img src='"+config.imagePath+"wait.gif'  style='padding:80px;padding-left:200px;' id='areaawait'/></li>";
		hidediv +="</ul>";
		hidediv +="</div>";
		hidediv +="<div id='areaselectsublayer' class='sublayerdiv'>";
		hidediv +="<input type='hidden' name='' value='' id='areaselecthiddenp'>";
		hidediv +="<ul id='areaselectcity'  style='list-style-type:none;padding-left:0;'><li></li></ul></div>";
		$("<div id='areaselecthidediv' title='请选择地区'></div>").insertAfter("body");
		$("#areaselecthidediv").append(hidediv);

		//弹出层
		$("#areaselecthidediv").dialog({
			autoOpen: true,
			modal:true,
			width:480,
			height:460,
			resizable: false,
			close:function(){
				$("#areaselectsublayer").dialog("close");
			}
		});
		$("#areaselectsublayer").dialog({
			autoOpen: false
		});
		/*请求地区信息*/
		var provinceUrl = config.dataUrl+"?type=selectprovince&countrycode="+countrycode;
		ajaxRequest(provinceUrl, _this.areaProvince, 'json');

		$("#areaselectresetarea").bind("click", function(){
			$("#areaselectselectedarea").empty();  //被选中词清空
			$("#areaselectallprocity input:checkbox").attr("checked", false); //省市checkbox清空
			$("#areaselectcity input:checkbox").attr("checked", false); // 二级弹出层清空
			$("#areaselect").val("请选择地区"); //文本框清空
			$("#areaselectcode").val(""); //隐藏文本框清空

			$("#areaselectslttip").css("display", "block");
			$("#areaselectselectedbox").css("display", "none");
		});
		$("#areaselectsmtarea").bind("click", function(){
			var resultArr = [];
			$("#areaselectselectedarea .areaitem").each(function(){
				var resObj = {};
				resObj['name'] = commonFun.trim($(this).text());
				resObj['code'] = $(this).attr("areacode");
				resultArr.push(resObj);
			});		
			/*存储最后结果,使用successResult()函数调用*/
			areaSucc(resultArr);
			$("#areaselecthidediv").dialog("close");
		});
		$("#areaselecttipsmt").bind('click', function(){
			var resultArr = [];
			areaSucc(resultArr);
			$("#areaselecthidediv").dialog("close");
		});
	}
	this.cancleArea = function(elm){
		var areacode = $(elm).attr("id"); 
		$("input[value="+areacode+"]").attr("checked", false);
		$(elm).parent().remove();
		var hasflag = true;
		$("#areaselectselectedarea .areaitem").each(function(){
			var sdwcode = $(this).attr("areacode");
			if(sdwcode.substr(0,2) == areacode.substr(0,2)){
				hasflag = false;
			}
		});
		/*当第二级全部去掉时*/
		if(hasflag){
			var tmpcode = areacode.substr(0,2)+"0000";
			$("#areaselectallprocity input[value="+tmpcode+"]").parent().css("backgroundColor","#ffffff");
			$("#areaselectallprocity input[value="+tmpcode+"]").removeAttr("disabled");
		}

	};

	this.addArea = function(areainfo)
	{
		var areacode = areainfo.code;
		var areaname = areainfo.name;
		var ischecked = $("input[type=checkbox][value="+areacode+"]").prop("checked");
		if(ischecked){
			var len = $("#areaselectselectedarea .areaitem").length + 1;
			if(len>0 && len<36){
				$("#areaselectslttip").css("display", "none");
				$("#areaselectselectedbox").css("display", "block");
				$("#areaselectselectedarea").append("<span><span style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;' class='areaitem' areacode='"+areacode+"'>"+areaname+"</span><a style='border:1px solid #cccccc;padding:1.5px;color:red; cursor:pointer;' id="+areacode+"  onclick='cancleArea(this)'>×</a></span>");
				//当选择市或区时对应的上一级应该删除
				if(areainfo.code.substr(2,4) != '0000'){
					var tmpcode = areainfo.code.substr(0,2)+"0000";
					$("#areaselectselectedarea .areaitem").each(function(i){
						var sdwcode = $(this).attr("areacode"); 
						if(sdwcode == tmpcode){
							$(this).parent().remove();
							$("#areaselectallprocity input[value="+tmpcode+"]").attr("checked",false);
						}
					});		
					$("#areaselectallprocity input[value="+tmpcode+"]").parent().css("backgroundColor","#6FBAE4");
					$("#areaselectallprocity input[value="+tmpcode+"]").attr("disabled","disabled"); //当选择第二级时,第一级不可选
				}
			}
			else{
				alert("提醒,你选择的地区达到了最多!");
				$("input[value="+areacode+"]").attr("checked", false);
			}
		}
		else{ //去掉选择
			$("#areaselectselectedarea .areaitem").each(function(i){
				var sdw = $(this).text(); 
				if(sdw == areaname) {
					$(this).parent().remove();
				}
			});		
			var hasflag = true;
			$("#areaselectselectedarea .areaitem").each(function(){
				var sdwcode = $(this).attr("areacode");
				if(sdwcode.substr(0,2) == areacode.substr(0,2)){
					hasflag = false;
				}
			});
			/*当第二级全部去掉时*/
			if(hasflag){
				var tmpcode = areacode.substr(0,2)+"0000";
				$("#areaselectallprocity input[value="+tmpcode+"]").parent().css("backgroundColor","#ffffff");
				$("#areaselectallprocity input[value="+tmpcode+"]").attr("disabled",false);
			}

			var len = $("#areaselectselectedarea .areaitem").length;
			if(len == 0){
				$("#areaselectslttip").css("display", "block");
				$("#areaselectselectedbox").css("display", "none");
			}
		}

	};

	this.getSubArea = function(areainfo){
		var provincecode = areainfo.code;
		$("#areaselecthiddenp").attr("name", areainfo.name);
		$("#areaselecthiddenp").attr("value", areainfo.code);
		//直辖市属省级,需要再次处理
		if(provincecode=='110000' || provincecode=='120000'|| provincecode=='310000' || provincecode=='500000')
		{
			citycode = provincecode.substr(0,3)+"100,"+provincecode.substr(0,3)+"200";
			var cityUrl = config.dataUrl+"?type=selectdistrict&countrycode="+countrycode+"&provincecode="+provincecode+"&citycode="+citycode;
		}
		else{
			var cityUrl = config.dataUrl+"?type=selectcity&countrycode="+countrycode+"&provincecode="+provincecode;
		}
		ajaxRequest(cityUrl, _this.areaCity, 'json');
	};

	this.areaProvince = function(data){

		//获取文本框选中的地区
		$("#areaselectallprocity").empty();
		//var areacodeString= $("#areaselectcode").val();	
		$.each(data[0].datalist, function(i, item){
			var di = JSON.stringify(item);
			$("#areaselectallprocity").append("<li style='width:140px;float:left;cursor:pointer;'><input type='checkbox' class='outborder' onclick='addArea("+di+")' value="+item.code+" /><a onclick='getSubArea("+di+")'>"+item.name+"</a></li>");
		});
		//当地区加载完毕时,根据初始化值,进行checkbox勾选,和显示
		if(sltVal!=undefined && sltVal!="" && sltVal!=null){
			$("#areaselectselectedarea").empty();

			$("#areaselectslttip").css("display", "none");
			$("#areaselectselectedbox").css("display", "block");
			$.each(sltVal, function(k,v){
				//勾选checkbox
				$("input[value="+v.code+"]").attr("checked", "checked"); 
				if(v.code.substr(2,4) != '0000'){
					var tmpcode = v.code.substr(0,2)+"0000";
					$("#areaselectallprocity input[value="+tmpcode+"]").parent().css("backgroundColor", "#6FBAE4");
				}
				//在已选择区域中添加
				$("#areaselectselectedarea").append("<span><span style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;' class='areaitem' areacode='"+v.code+"'>"+v.name+"</span><a style='border:1px solid #cccccc;padding:1.5px;color:red; cursor:pointer;'  id="+v.code+"  onclick='cancleArea(this)'>×</a></span>");
			});
		}
	}
	this.areaCity = function(data)
	{
		var proslted= $("#areaselecthiddenp").val();
		var isslted = $("input[value="+proslted+"]:checked").val();
		$("#areaselectsublayer").dialog("open");
		$("#areaselectsublayer").attr("areacode", proslted);
		$("#areaselectcity").empty();
		$.each(data[0].datalist, function(i, item){
			var di = JSON.stringify(item);
			$("#areaselectcity").append("<li style='width:100px;float:left;cursor:pointer;'><input type='checkbox' class='outborder' onclick='addArea("+di+")' value="+item.code+" id=city"+item.code+" /><label for=city"+item.code+">"+item.name+"</label></li>");
		});
		/*需要进行初始化选择*/
		$("#areaselectselectedarea .areaitem").each(function(i){
			var sdwcode = $(this).attr("areacode"); 
			$("input[value="+sdwcode+"]").attr("checked", "checked");
		});		
	};
	//初始化调用方法
	initAreaSelect();
}

function myBizSelect(successResult, selectVal){
	var _this=this;
	var sltVal = selectVal;
	var bizSucc = successResult;
	var bizInit = function(){
		$("#bizbizlayer").remove();
		var bizdiv = "<div class='sltbox' id='bizselecttip'>";
		bizdiv += "<h3>";
		bizdiv += "<span>提示：</span><b><input type='button' value='确定' id='bizbiztip'>&nbsp;&nbsp;<input type='button' class='' value='清空' disabled='' name=''></b>";
		bizdiv += "</h3>";
		bizdiv += "<p>当您选择以下的具体行业时，将会获得更为准确的搜索结果</p>";
		bizdiv += "</div>";
		bizdiv += "<div class='sltbox' id='bizselectbox' style='display:none;'>";
		bizdiv += "<h3>";
		bizdiv += "<span>您选择的行业是：</span><b><input type='button' value='确定' id='bizsmtbiz'>&nbsp;&nbsp;<input type='button' class='' value='清空' name='' id='bizresetbiz'></b>";
		bizdiv += "</h3>";
		bizdiv += "<div id='bizselectedbiz'></div>";
		bizdiv += "</div>";
		bizdiv += "<div class=''>";
		bizdiv += "<h2 id=''>";
		bizdiv += "<h3><span>行业列表</span></h3>";
		bizdiv += "</h2>";
		bizdiv += "<ul id='bizallbusiness' style='list-style-type:none;padding-left:0;'>";
		bizdiv += "<li><img src='"+config.imagePath+"wait.gif'  style='padding:100px;padding-left:250px;' id='areaawait'/></li>";
		bizdiv += "</ul>";
		bizdiv += "</div>";
		$("<div id='bizbizlayer'  title='请选择行业'></div>").insertAfter("body");
		$("#bizbizlayer").append(bizdiv);

		//弹出层
		$("#bizbizlayer").dialog({
			autoOpen: true,
			modal:true,
			width:550,
			height:400,
			resizable: false
		});
		var getbusinessUrl = config.dataUrl + "?type=getbusiness";
		ajaxRequest(getbusinessUrl, _this.getbusiness, "json");
		$("#bizresetbiz").bind("click", function(){
			$("#bizselectedbiz").empty();  //被选中词清空
			$("#bizallbusiness input:checkbox").attr("checked", false); //省市checkbox清空
			$("#biz").val("请选择地区"); //文本框清空
			$("#bizcode").val(""); //隐藏文本框清空

			$("#bizselecttip").css("display", "block");
			$("#bizselectbox").css("display", "none");
		});
		$("#bizsmtbiz").bind("click", function(){
			var resultArr = [];
			$("#bizselectedbiz .bizitem").each(function(i){
				var resObj = {};
				resObj['name'] = commonFun.trim($(this).text());
				resObj['code'] = $(this).attr("title");
				resultArr.push(resObj);
			});		
			bizSucc(resultArr); 
			$("#bizbizlayer").dialog("close");

		});
		$("#bizbiztip").bind('click', function(){
			var resultArr = [];
			bizSucc(resultArr); 
			$("#bizbizlayer").dialog("close");
		});

	}; //bizInit end;
	this.addBusiness = function(bizinfo){
		var bizcode = bizinfo.code;
		var bizname = bizinfo.name;
		var ischecked = $("#bizallbusiness input[value="+bizcode+"]:checked").val();
		if(ischecked != undefined){
			//var len = $("#bizselectedbiz .bizitem").length + 1;
			//if(len>0 && len<6){
				$("#bizselecttip").css("display", "none");
				$("#bizselectbox").css("display", "block");
				$("#bizselectedbiz").append("<span class='selbizbox'><span style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;' class='bizitem' title='"+bizcode+"'>"+bizname+"</span><a style='border:1px solid #cccccc;padding:1.5px;color:red; cursor:pointer;' id="+bizcode+" onclick='cancleBiz(this)'>×</a></span>");
				/*
			}
			else{
				alert("提醒,你选择的行业达到了5个!");
				$("input[value="+bizcode+"]").attr("checked", false);
			}
			*/
		}
		else{
			$("#bizselectedbiz .bizitem").each(function(i){
				var sdw = $(this).text(); 
				if(sdw == bizname) {
					$(this).parent().remove();
				}
			});		

			var len = $("#bizselectedbiz .bizitem").length;
			if(len == 0){
				$("#bizselecttip").css("display", "block");
				$("#bizselectbox").css("display", "none");
			}
		}
	} //addBusiness end
	this.cancleBiz = function(elm){
		var bizcode = $(elm).attr("id"); 
		$("input[value="+bizcode+"]").attr("checked", false);
		$(elm).parent().remove();
	}


	this.getbusiness = function(data){
		//获取文本框选中的行业
		$("#bizallbusiness").empty();
		$.each(data[0].datalist, function(i, item){
			var di = JSON.stringify(item);
			$("#bizallbusiness").append("<li style='width:250px;float:left;cursor:pointer;'><input type='checkbox' class='outborder' onclick='addBusiness("+di+")' value="+item.code+" />"+item.name+"</li>");
		});
		if(sltVal!=undefined && sltVal!="" && sltVal!=null){
			$("#bizselectedbiz").empty();
			$("#bizselecttip").css("display", "none");
			$("#bizselectbox").css("display", "block");
			$.each(sltVal, function(k,v){
				$("#bizallbusiness input[value="+v.code+"]").attr("checked", "checked");
				$("#bizselectedbiz").append("<span class='selbizbox'><span class='selbizbox'><span style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;' class='bizitem' title='"+v.code+"'>"+v.name+"</span><a style='border:1px solid #cccccc;padding:1.5px;color:red; cursor:pointer;' id="+v.code+" onclick='cancleBiz(this)'>×</a></span>");
			});
		}

	} //getbusiness end
	bizInit();
};
/*
 * 关键词推荐
 * */
function myKeywordRecommend(keyword, successResult, fieldname, fieldtext){
	var _this = this;
	var page; //第几页
	this.pagesize=20; //每页显示条数
	var curpage2;//当前页码
	var blurtype;
	var initKeywordRecommend = function(){
		var ftext;
		if(fieldtext !="" && fieldtext!=undefined){
			ftext = fieldtext; 
		}
		else{
			ftext = "关键词"; 
		}
		$("#keywordsbox").remove();
		var keywordhtml = "<div id='blurkeywordopt'>"+ftext+"筛选:<br/><input type='checkbox' name='blurwordopt' checked='checked' id='blurwordopt_prefix' value='prefix'/><label for='blurwordopt_prefix'>以\""+keyword+"\"开头</label><input type='checkbox'  name='blurwordopt' id='blurwordopt_suffix' value='suffix' /><label for='blurwordopt_suffix'>以\""+keyword+"\"结尾</label><input type='checkbox' name='blurwordopt' id='blurwordopt_infix' value='infix' /><label for='blurwordopt_infix'>包含\""+keyword+"\"</label></div>";
			keywordhtml+= "<fieldset>"; 
			keywordhtml+="<legend>"+ftext+"列表</legend>";
			keywordhtml+="<div id='blurwords'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='wait1'/></div>";
			keywordhtml+="<div id='blurpage' style='margin:5px;float:left;width:100%;'></div>";
			keywordhtml+="<div style='clear:both;'><span>模糊查询"+ftext+":</span></div>";
			keywordhtml+="<div id='blurqueryword'></div>";
			keywordhtml+="</fieldset>";
			keywordhtml+="<fieldset>";
			keywordhtml+="<legend>分词列表</legend>";
			keywordhtml+="<div id='tokenwords'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='wait2'/></div>";
			keywordhtml+="<div id='tokenpage' style='margin:5px;float:left;width:100%;'></div>";
			keywordhtml+="</fieldset>";
			keywordhtml+="<fieldset>";
			keywordhtml+="<legend>已选择的"+ftext+"</legend>";
			keywordhtml+="<div id='selectedwords'></div>";
			keywordhtml+="</fieldset>";
			keywordhtml+="<div class='search'><input id='kasubmit' name='kasubmint' type='button' value='确定' />  <input id='kacancle' name='kacancle' type='button' value='取消' /></div>";
		$("<div id='keywordsbox' title='"+ftext+"列表-包含\""+keyword+"\"的"+ftext+"'></div>").insertAfter("body");
		$("#keywordsbox").append(keywordhtml);
		var blurwordhtml = ""
		blurwordhtml += "<a style='margin:2px;color:blue;width:100px;float:left;cursor:pointer;' rtext='"+keyword+"*' rvalue='prefix' onclick='sendWord(this)'>以\""+keyword+"\"开头</a>"
		blurwordhtml += "<a style='margin:2px;color:blue;width:100px;float:left;cursor:pointer;' rtext='*"+keyword+"' rvalue='suffix' onclick='sendWord(this)'>以\""+keyword+"\"结尾</a>"
		blurwordhtml += "<a style='margin:2px;color:blue;width:100px;float:left;cursor:pointer;' rtext='*"+keyword+"*' rvalue='infix' onclick='sendWord(this)'>包含\""+keyword+"\"</a>"
		$("#blurqueryword").append(blurwordhtml);
		$("#blurkeywordopt").buttonset();
		$("#keywordsbox").dialog({
			autoOpen: false,
			modal:true,
			width:550,
			height:460
		});
		$("#blurkeywordopt input[name=blurwordopt]").bind("click", function(){
			var checkedlen = $("#blurkeywordopt input[name=blurwordopt]:checked").length;
			if(checkedlen == 0){
				var chkid = $(this).attr("id");
				$("#blurkeywordopt label[for="+chkid+"]").addClass("ui-state-active");
				return false;
			}
			_this.blurwordoptClick();
		});
		_this.blurwordoptClick();

		$("#kasubmit").bind("click", function(){
			var resultArr = [];
			$("#selectedwords .selword").each(function(i,m){
				var tmpobj = {};
				tmpobj.name = $(this).attr("rtext"); 
				if($(this).attr("exclude") != undefined){
					tmpobj.exclude = $(this).attr("exclude");
				}
				resultArr.push(tmpobj);
			});		
			/*存储最后结果,使用successResult()函数调用*/
			successResult(resultArr);
			$("#keywordsbox").dialog("close");
		});
		$("#kacancle").bind("click", function(){
			$("#keywordsbox").dialog("close");
			$("#selectedwords").empty();
		});

	};
	this.blurwordoptClick = function(){
		var blurArr = [];
		$("#blurkeywordopt input[name=blurwordopt]").each(function(){
			if($(this).prop("checked")){
				blurArr.push($(this).val());
			}
		});
		if(blurArr.length>0){
			if(blurArr.length == 1){
				blurtype = blurArr[0];
			}
			else{
				var bflag = true;
				$.each(blurArr,function(i,m){
					if(m=='infix'){
						bflag = false;
						return false;
					}
				});
				if(bflag){
					blurtype = 'prefix_suffix';
				}
				else{
					blurtype = 'infix';
				}
			}
		}

		if(keyword!=null && keyword!="" && keyword!=undefined)
		{
			searchBlur(_this.pagesize, 1);
			searchToken(_this.pagesize, 1);
			$("#blurwords").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='wait1'/>");
			$("#blurpage").empty();
			$("#tokenwords").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='wait2'/>");
			$("#tokenpage").empty();
			$("#keywordsbox").dialog("open");
		}
		else{
			alert("请输入"+ftext+"！");
		}

	}
	this.sendWord = function(elm){
		var keyword = $(elm).text();
		var wordvalue = $(elm).attr("rtext");
		//$(elm).css("background-color", "red");
		$(elm).removeClass().addClass("selected");
		var wflag = true;
		$("#selectedwords .selword").each(function(i,m){
			var sltw = $(this).attr("rtext");
			if(wordvalue == sltw){
				$(this).parent().remove();
				$(elm).removeClass().addClass("notselected");
				wflag = false;
			}
		});
		if(wflag){
			var spanutilhtml = createUtilSpanHtml("selword", keyword, undefined, false, undefined, 'cancleWord', wordvalue);
			$("#selectedwords").append(spanutilhtml);
		}
		else{
			//alert("选择相同了!");
		}
	};
	this.cancleWord = function(elm){
		$(elm).parent().remove();
		var rt = $(elm).attr("rtext");
		$("#blurwords a[rtext='"+rt+"']").removeClass().addClass("notselected");
		$("#tokenwords a[rtext='"+rt+"']").removeClass().addClass("notselected");
		$("#blurqueryword a[rtext='"+rt+"']").removeClass().addClass("notselected");
	}
	this.changeWord = function(elm){
		var w = $(elm).text();
		$(elm).parent().html("<input type='text' value="+w+" onblur='returnBack(this)'/>");
	}
	this.returnBack = function(elm){
		var kw = $(elm).val();
		$(elm).parent().html("<span onclick='changeWord(this)'>"+kw+"</span>");
	}
	var searchBlur = function(pagesize, curpage){
		if(curpage == '' || curpage == null || curpage == undefined ){
			_this.curpage2 = 1;
		}
		else{
			_this.curpage2 = curpage;
		}
		if(fieldname == undefined){
			fieldname = "text";
		}
		var kwblurUrl = config.solrData+ "?type=kwblur&fieldname="+fieldname+"&keyword="+encodeURIComponent(keyword)+"&blurtype="+blurtype+"&page="+_this.curpage2+"&pagesize="+pagesize;
		ajaxRequest(kwblurUrl, function(data){
				if(data[0].totalcount> 0){
					$("#blurwords").empty();
					var blurwordshtml = "";
					$.each(data[0].datalist, function(i,m){
						var cflag = true
						$("#selectedwords .selword").each(function(k,v){
							var sltw = $(this).attr("rtext");
							if(m == sltw){
								cflag = false;
							}
						});
						var sltcolor = "";
						if(cflag){
							//sltcolor = "style='margin:3px;cursor:pointer;color:blue;float:left;display:inline-block;'";
							sltcolor = "class='notselected'";
						}
						else{
							//sltcolor = "style='margin:3px;cursor:pointer;color:blue;float:left;display:inline-block;background-color:red'";
							sltcolor = "class='selected'";
						}
						var urlstyleattr = "";
						if(fieldname == "users_url"){
							urlstyleattr = "style='overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;' title='"+m+"' ";
						}
						blurwordshtml += "<a "+sltcolor+" rtext="+m+" onclick='sendWord(this)' "+urlstyleattr+">"+m+"</a>";
					});				
					$("#blurwords").append(blurwordshtml);
					//显示分页
					if(data[0].totalcount>pagesize)
					{
						pageDisplay(data[0].totalcount, searchBlur, "blurpage", _this.pagesize, _this.curpage2);
					}
				}
				else{
					$("#blurwords").text("模糊查询暂无数据！");
				}
			} , "json", function(){}, function(){
				$("#blurwords").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='wait1'/>");
			}, function(){
				$("#wait1").css({display:"none"});
			}
		);
	}
		
	var searchToken = function(pagesize, curpage){
		if(curpage == '' || curpage == null || curpage == undefined ){
			_this.curpage2 = 1;
		}
		else{
			_this.curpage2 = curpage;
		}
		if(fieldname == undefined){
			fieldname = "text";
		}
		var kwtokenUrl= config.solrData+ "?type=kwtoken&fieldname="+fieldname+"&keyword="+encodeURIComponent(keyword)+"&page="+_this.curpage2+"&pagesize="+pagesize;
		ajaxRequest(kwtokenUrl, function(data){
			if(data[0].totalcount>0)
			{
				$("#tokenwords").empty();
				var tokenwordshtml = "";
				$.each(data[0].datalist, function(i,m){
					var cflag = true
					$("#selectedwords .selword").each(function(k,v){
						var sltw = $(this).attr("rtext");
						if(m == sltw){
							cflag = false;
						}
					});
					var sltcolor = "";
					if(cflag){
						//sltcolor = "style='margin:3px;cursor:pointer;color:blue;float:left;'";
						sltcolor = "class='notselected'";
					}
					else{
						//sltcolor = "style='margin:3px;cursor:pointer;color:blue;float:left;' background-color:red";
						sltcolor = "class='selected'";
					}
					tokenwordshtml +="<a "+sltcolor+" rtext="+m+" onclick='sendWord(this)'>"+m+"</a>";
				});				
				$("#tokenwords").append(tokenwordshtml);
				//显示分页
				if(data[0].totalcount>pagesize){
					pageDisplay(data[0].totalcount, searchToken, "tokenpage", _this.pagesize, _this.curpage2);
				}
			}
			else
			{
				$("#tokenwords").text("分词查询暂无数据！");
			}
		}, "json", function(){}, 
		function(){
			$("#tokenwords").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='wait2'/>");
		}, 
		function(){$("#wait2").css({display:"none"});});
	}
	//初始化调用方法
	initKeywordRecommend();
}

function myPartSelect(choiceVal, successResult, selectVal){
	var _this=this;
	var sltVal = selectVal;
	var partSucc = successResult;
	var partInit = function(){
		$("#parthidediv").remove();
		var	partdiv = "<div class='sltbox' id='partselecttip'>";
		partdiv += "<h3>";
		partdiv += "<span>提示：</span><b><input type='button' value='确定' id='partparttip'>&nbsp;&nbsp;<input type='button' value='清空' disabled='' name=''></b>";
		partdiv += "</h3>";
		partdiv += "<p>请从列表中选择</p>";
		partdiv += "</div>";
		partdiv += "<div class='sltbox' id='partselectbox' style='display:none;'>";
		partdiv += "<h3>";
		partdiv += "<span>您选择的是：</span><b><input type='button' value='确定' id='partsmtpart'>&nbsp;&nbsp;<input type='button' value='清空' name='' id='partresetpart'></b>";
		partdiv += "</h3>";
		partdiv += "<div id='partselectedpart'></div>";
		partdiv += "</div>";
		partdiv += "<div class=''>";
		partdiv += "<h2 id=''>";
		partdiv += "<span>可选列表:</span>";
		partdiv += "</h2>";
		partdiv += "<ul id='partallitems' style='list-style-type:none;padding-left:0;'>";
		partdiv += "<li><a><input type='checkbox' class='outborder' id=''/></a>&gt;</li>";
		partdiv += "</ul>";
		partdiv += "</div>";
		$("<div id='parthidediv' style='display:none'></div>").insertAfter("body");
		$("#parthidediv").append(partdiv);

		//弹出层
		$("#parthidediv").dialog({
			autoOpen: true,
			modal:true,
			width:385,
			height:220
		});
		_this.getitems(choiceVal);
		$("#partresetpart").bind("click", function(){
			$("#partselectedpart").empty();  //被选中词清空
			$("#partallitems input:checkbox").attr("checked", false); //省市checkbox清空

			$("#partselecttip").css("display", "block");
			$("#partselectbox").css("display", "none");
		});
		$("#partsmtpart").bind("click", function(){
			$("#parthidediv").dialog("close");
			var resultArr = [];
			$("#partselectedpart .partitem").each(function(i){
				var resObj = {};
				resObj['name'] = $(this).text();
				resObj['code'] = $(this).attr("title");
				if($(this).attr("blurtype") != undefined){
					resObj['blurtype'] = $(this).attr("blurtype");
				}
				resultArr.push(resObj);
			});		
			partSucc(resultArr); 
		});
		$("#partparttip").bind('click', function(){
			$("#parthidediv").dialog("close");
			var resultArr = [];
			partSucc(resultArr); 
		});

	}; //partInit end;
	_this.additems = function(partinfo){
		var partcode = partinfo.code;
		var partname = partinfo.name;
		var partblurtype = "";
		if(partinfo.blurtype != undefined){
			partblurtype = "blurtype="+partinfo.blurtype+"";
		}
		var ischecked = $("input[value='"+partcode+"']:checked").val();
		if(ischecked != undefined){
			//var len = $("#partselectedpart .partitem").length + 1;
			//if(len>0 && len<60){
				$("#partselecttip").css("display", "none");
				$("#partselectbox").css("display", "block");
				$("#partselectedpart").append("<span class='selpartbox'><span class='partitem' title='"+partcode+"' "+partblurtype+">"+partname+"</span><a style='border:1px solid #ffffff;color:red; cursor:pointer;' id='"+partcode+"' onclick='canclepart(this)'>×</a></span>");
				/*
			}
			else{
				alert("提醒,你选择的达到了5个!");
				$("input[value='"+partcode+"']").attr("checked", false);
			}
			*/
		}
		else{
			$("#partselectedpart .partitem").each(function(i){
				var sdw = $(this).text(); 
				if(sdw == partname) {
					$(this).parent().remove();
				}
			});		
			var len = $("#partselectedpart .partitem").length;
			if(len == 0){
				$("#partselecttip").css("display", "block");
				$("#partselectbox").css("display", "none");
			}
		}
	} //additems end
	_this.canclepart = function(elm){
		var partcode = $(elm).attr("id"); 
		$("input[value='"+partcode+"']").attr("checked", false);
		$(elm).parent().remove();
		var len = $("#partselectedpart .partitem").length;
		if(len == 0){
			$("#partselecttip").css("display", "block");
			$("#partselectbox").css("display", "none");
		}

	}
	_this.getitems = function(data){
		//获取文本框选中的
		$("#partallitems").empty();
		//判断是不是对象,不是对象时转换,或提示
		if(typeof(data) == 'string'){
			data = JSON.parse(data);
		}
		$.each(data, function(i, item){
			var di = JSON.stringify(item);
			var blurtype = "";
			if(item.blurtype != undefined){
				blurtype = "blurtype="+item.blurtype+"";
			}

			$("#partallitems").append("<li style='width:150px;float:left;cursor:pointer;'><input type='checkbox' class='outborder' onclick='additems("+di+")' value='"+item.code+"' "+blurtype+" />"+item.name+"</li>");
		});
		if(sltVal!=undefined && sltVal!="" && sltVal!=null){
			$("#partselectedpart").empty();
			$("#partselecttip").css("display", "none");
			$("#partselectbox").css("display", "block");
			if(typeof(sltVal) == 'string'){
				sltVal = JSON.parse(sltVal);
			}
			$.each(sltVal, function(k,v){
				var blurtype = "";
				if(v.blurtype != undefined){
					blurtype = "blurtype="+v.blurtype+"";
				}
				$("#partallitems input[value='"+v.code+"']").attr("checked", "checked");
				$("#partselectedpart").append("<span class='selpartbox'><span class='partitem' title='"+v.code+"' "+blurtype+">"+v.name+"</span><a style='border:1px solid #ffffff;color:red; cursor:pointer;' id='"+v.code+"' onclick='canclepart(this)'>×</a></span>");
			});
		}

	} //getitems end
	partInit();
};

function myDatePicker(){
	this.setTimeSelect = function(render){
		$("#"+render).append("<span class='rightalign'>时间：</span><input id='"+render+"d4311' class='Wdate' style='border:1px solid #DADADA;' type='text' readonly='readonly'/> 	至 <input id='"+render+"d4312' class='Wdate' style='border:1px solid #DADADA;' type='text'  readonly='readonly' />");
		//获取初始化时间，当url中有startdate和enddate时，控件中显示对应时间	 
		var startdate = commonFun.queryString('startdate');
		if(startdate!="" && startdate!=null && startdate!=undefined)
		{
			$("#"+render+"d4311").val(formatTime('yyyy-MM-dd hh:mm', startdate));
		}
		var enddate = commonFun.queryString('enddate');
		if(enddate!="" && enddate!=null && enddate!=undefined)
		{
			$("#"+render+"d4312").val(formatTime('yyyy-MM-dd hh:mm', enddate));
		}

		$("#"+render+"d4311").click(function(){
				WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});
		});
		$("#"+render+"d4312").click(function(){
			var otherid = render+"d4311";
			WdatePicker({minDate:"#F{$dp.$D(\'"+otherid+"\')}", maxDate:'%y-%M-%d', dateFmt:'yyyy-MM-dd HH:mm'});
		});
	},
	this.getStartTime = function(render){
		//return $dp.$('d4311').value;
		return $("#"+render+"d4311").val();
	},
	this.getEndTime = function(render){
		//return $db.$('d4312').value;	   
		return $("#"+render+"d4312").val();
	}

};
function myTopicRecommend(topic, successResult, fieldname, fieldtext, isemo){
	var _this = this;
	var page; //第几页
	this.pagesize=20; //每页显示条数
	var curpage2;//当前页码
	var blurtype;
	var isemotype = false;
	var initTopicRecommend = function(){
		var ftext;
		if(fieldtext !="" && fieldtext!=undefined){
			ftext = fieldtext; 
		}
		else{
			ftext = "短语"; 
		}

		if(isemo !="" && isemo!=undefined){
			isemotype = isemo; 
		}
		$("#topicsbox").remove();
		var topichtml = "<div id='blurtopicopt_div'>"+ftext+"筛选:<br/><input type='checkbox' name='blurtopicopt' checked='checked' id='blurtopicopt_tprefix' value='t_prefix'/><label for='blurtopicopt_tprefix'>关键词以\""+topic+"\"开头</label><input type='checkbox'  name='blurtopicopt' id='blurtopicopt_tsuffix' value='t_suffix' /><label for='blurtopicopt_tsuffix'>关键词以\""+topic+"\"结尾</label><input type='checkbox' name='blurtopicopt' id='blurtopicopt_tinfix' value='t_infix' /><label for='blurtopicopt_tinfix'>关键词包含\""+topic+"\"</label><input type='checkbox' name='blurtopicopt' id='blurtopicopt_tmblur' value='tm_blur' /><label for='blurtopicopt_tmblur'>包含关键词\""+topic+"\"</label></div>";
		topichtml += "<fieldset>"; 
		topichtml +="<legend>"+ftext+"列表</legend>";
		topichtml +="<div id='groupwords'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:305px;' id='wait3'/></div>";
		topichtml +="<div id='grouppage' style='margin-top:2px;float:left;width:100%;margin:5px;'></div>";
		topichtml +="<div style='clear:both;' id='blurquerytopicemotion'><span>模糊查询"+ftext+":</span></div>";
		topichtml +="<div id='blurquerytopic'></div>";
		topichtml +="</fieldset>";
		topichtml +="<fieldset>";
		topichtml +="<legend>已选择的"+ftext+"</legend>";
		topichtml +="<div id='slttopicrecommend'></div>";
		topichtml +="</fieldset>";
		topichtml +="<div class='search'><input id='topicsubmit' name='topicsubmint' type='button' value='确定' />  <input id='topiccancle' name='topiccancle' type='button' value='取消' /></div>";
		$("<div id='topicsbox' title='"+ftext+"列表-包含\""+topic+"\"的"+ftext+"'></div>").insertAfter("body");
		$("#topicsbox").append(topichtml);
		var blurtopichtml = ""
		blurtopichtml+= "<a style='margin:2px;color:blue;width:150px;float:left;cursor:pointer;' rtext='"+topic+"*' btype='t_prefix' >关键词以\""+topic+"\"开头</a>"
		blurtopichtml+= "<a style='margin:2px;color:blue;width:150px;float:left;cursor:pointer;' rtext='*"+topic+"' btype='t_suffix' >关键词以\""+topic+"\"结尾</a>"
		blurtopichtml+= "<a style='margin:2px;color:blue;width:150px;float:left;cursor:pointer;' rtext='*"+topic+"*' btype='t_infix' >关键词包含\""+topic+"\"</a>"
		blurtopichtml+= "<a style='margin:2px;color:blue;width:150px;float:left;cursor:pointer;' rtext='"+topic+"' btype='tm_blur' >包含关键词\""+topic+"\"</a>"
		$("#blurquerytopic").append(blurtopichtml);
		if(isemotype){
			_this.emotionChk();
		}
		$("#blurquerytopic a[rtext][btype]").bind("click", function(){
			if(isemotype){
				_this.sendEmotion(this);
			}
			else{
				_this.sendTopic(this);
			}
		});
		$("#blurtopicopt_div").buttonset();

		$("#topicsbox").dialog({
			autoOpen: false,
			modal:true,
			width:730,
			height:460
		});
		$("#blurtopicopt_div input[name=blurtopicopt]").bind("click", function(){
			var checkedlen = $("#blurtopicopt_div input[name=blurtopicopt]:checked").length;
			var chkid = $(this).attr("id");
			if(checkedlen==0){
				$("#blurtopicopt_div label[for="+chkid+"]").addClass("ui-state-active");
				return false;
			}
			_this.blurtopicoptClick();
		});
		_this.blurtopicoptClick();
		$("#topicsubmit").bind("click", function(){
			var resultArr = [];
			$("#slttopicrecommend .seltopic").each(function(i,m){
				var tmpobj = {};
				tmpobj.topic = $(this).attr("rtext");
				tmpobj.btype = $(this).attr("btype");
				if($(m).attr("emotype") != undefined){
					tmpobj.emotype = $(m).attr("emotype");
				}
				resultArr.push(tmpobj);
			});		
			/*存储最后结果,使用successResult()函数调用*/
			successResult(resultArr);
			$("#topicsbox").dialog("close");
		});
		$("#topiccancle").bind("click", function(){
			$("#topicsbox").dialog("close");
			$("#slttopicrecommend").empty();
		});

	}

	this.blurtopicoptClick = function(){
//处理不同type, 根据选择不同 给blurtype赋值
		var blurArr = [];
			$("#blurtopicopt_div input[name=blurtopicopt]").each(function(){
				if($(this).prop("checked")){
					blurArr.push($(this).val());
				}
			});
			var level1 = [];
			var level2 = [];
			var level3 = [];
			var level4 = [];
			$.each(blurArr, function(i,m){
				if(m == 't_infix'){
					level1.push(m);
				}
				if(m == 'tm_blur'){
					level4.push(m);
				}
				if(m == 't_prefix'){
					level2.push(m)
				}
				if(m == 't_suffix'){
					level2.push(m)
				}
			});
			if(level1.length > 0){
				blurtype = 't_infix';
			}
			else if(level2.length == 2){
				blurtype = 't_prefix_suffix';
			}
			else if(level2.length == 1){
				blurtype = level2[0];
			}
			else if(level4.length > 0){
				blurtype = 'tm_blur';
			}

		if(topic!=null && topic!="" && topic!=undefined)
		{
			searchGroup(pagesize, 1);
			$("#groupwords").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:305px;' id='wait3'/>");
			$("#grouppage").empty();
			$("#topicsbox").dialog("open");
		}
		else{
			alert("请输入"+ftext+"！");
		}

	}
	this.emotionChk= function(){
		var emohtml = '<div id="utilemotiondiv"><span id="utilemotion">';
			emohtml += '<input type="checkbox" name="utilemotion" id="utilemotion1" value="1" class="outborder" /><label for="utilemotion1">反对</label>';
			emohtml += '<input type="checkbox" name="utilemotion" id="utilemotion2" value="2" class="outborder" /><label for="utilemotion2">负面</label>';
			emohtml += '<input type="checkbox" name="utilemotion" id="utilemotion3" value="3" class="outborder" /><label for="utilemotion3">中性</label>';
			emohtml += '<input type="checkbox" name="utilemotion" id="utilemotion4" value="4" class="outborder" /><label for="utilemotion4">正面</label>';
			emohtml += 	'<input type="checkbox" name="utilemotion" id="utilemotion5" value="5" class="outborder" /><label for="utilemotion5">赞赏</label>';
			emohtml += 	'<input type="checkbox" name="utilemotion" id="utilemotionall" value="*" class="outborder" /><label for="utilemotionall">全部</label></span></div>';
		$("#blurquerytopicemotion").after(emohtml);

		$("#utilemotiondiv input[name=utilemotion]").bind("click", function(){
			var itemid = $(this).attr("id");
			if(itemid == "utilemotionall"){
				if($(this).prop("checked") == true){
					$("#utilemotiondiv input[name=utilemotion]").attr("checked", true);
				}
				else{
					$("#utilemotiondiv input[name=utilemotion]").attr("checked", false);
				}
			}
			else{
				var chklen = $("#utilemotiondiv input[name=utilemotion]:checked").length;
				var alllen = $("#utilemotiondiv input[name=utilemotion]").length;
				if($(this).prop("checked") == true){
					if(chklen == alllen-1){
						$("#utilemotiondiv input[name=utilemotion]").attr("checked", true);
					}
				}
				else{
					$("#utilemotiondiv input[name=utilemotion][id=utilemotionall]").attr("checked", false);
				}
			}
		});
	}
	this.sendEmotion = function(elm){
		var topic = $(elm).text();
		var topicvalue = $(elm).attr("rtext");
		var rtArr = [];
		var rvArr = [];
		if($(elm).attr("emotype")!=undefined){
			emocode = $(elm).attr("emotype");
		}
		else{
			$("#utilemotiondiv input[name=utilemotion]:checked").each(function(i,m){
				var rtid = $(this).attr("id");
				if(rtid != "utilemotionall"){ //全部checkbox 不添加到数组
					var rt = $("#utilemotiondiv label[for="+rtid+"]").text();
					rtArr.push(rt);
					var rv = $(this).val();
					rvArr.push(rv);
				}
			});
			var alllen = $("#utilemotiondiv input[name=utilemotion]").length;
			var emocode = "";
			if(rvArr.length>0){
				if(rvArr.length == alllen-1){
					topic +="(全部)";
					emocode = "*";
				}
				else{
					topic +="("+rtArr.join(",")+")";
					emocode = rvArr.join(",");
				}
			}
		}
		if(emocode == ""){
			alert("请选择情感!");
			return false;
		}

		var btype = $(elm).attr("btype");
		//$(elm).css("background-color", "red");
		$(elm).removeClass().addClass("selected");
		var wflag = true;
		$("#slttopicrecommend .seltopic").each(function(i,m){
			var sltw = $(this).attr("rtext");
			var sltc = $(this).attr("emotype");
			if(topicvalue == sltw && emocode == sltc){
				$(this).parent().remove();
				$(elm).removeClass().addClass("notselected");
				//$(elm).css("background-color", "white");
				wflag = false;
			}
		});
		if(wflag){
			$("#slttopicrecommend").append("<span class='selwordsbox' style='border:1px solid #cccccc;padding:1.5px 0 1.5px 1.5px;margin: 7px 0px 2px 2px;'><span class='seltopic' style='cursor:pointer;'  emotype='"+emocode+"' rtext="+topicvalue+" btype="+btype+" >"+topic+"</span><a class='cancleitem' rtext="+topicvalue+"  emotype='"+emocode+"' onclick='cancleTopic(this)'>×</a></span>");
			$("#utilemotiondiv input[name=utilemotion]").attr("checked", false);
		}
		else{
			//alert("选择相同了!");
		}
	}
	this.sendTopic = function(elm){
		var topic = $(elm).text();
		var topicvalue = $(elm).attr("rtext");

		var btype = $(elm).attr("btype");
		//$(elm).css("background-color", "red");
		$(elm).removeClass().addClass("selected");
		var wflag = true;
		$("#slttopicrecommend .seltopic").each(function(i,m){
			var sltw = $(this).attr("rtext");
			if(topicvalue == sltw){
				$(this).parent().remove();
				$(elm).removeClass().addClass("notselected");
				//$(elm).css("background-color", "white");
				wflag = false;
			}
		});
		if(wflag){
			var spanutilhtml = createUtilSpanHtml('seltopic', topic, undefined, false, undefined, 'cancleTopic', topicvalue, $(elm).attr("emotype"), btype);
			$("#slttopicrecommend").append(spanutilhtml);
		}
		else{
			//alert("选择相同了!");
		}
	}
	this.cancleTopic= function(elm){
		$(elm).parent().remove();
		var rt = $(elm).attr("rtext");
		if($(elm).attr("emotype") != undefined){
			var emotype = $(elm).attr("emotype");
			$("#groupwords a[rtext='"+rt+"'][emotype='"+emotype+"']").removeClass().addClass("notselected");
			$("#blurquerytopic a[rtext='"+rt+"'][emotype='"+emotype+"']").removeClass().addClass("notselected");
		}
		else{
			$("#groupwords a[rtext='"+rt+"']").removeClass().addClass("notselected");
			$("#blurquerytopic a[rtext='"+rt+"']").removeClass().addClass("notselected");
		}
	}

	var searchGroup = function(pagesize, curpage)
	{
		if(curpage == '' || curpage == null || curpage == undefined ){
			_this.curpage2 = 1;
		}
		else{
			_this.curpage2 = curpage;
		}
		if(fieldname == undefined){
			fieldname = "combinWord";
		}
		var kwgroupUrl = config.solrData+ "?type=kwgroup&fieldname="+fieldname+"&keyword="+encodeURIComponent(topic)+"&blurtype="+blurtype+"&page="+_this.curpage2+"&pagesize="+pagesize;
		ajaxRequest(kwgroupUrl, function(data){
			if(data!=null && data[0].datalist!= null && data[0].datalist.length>0 && data[0].totalcount>0) {
				$("#groupwords").empty();
				var groupwordshtml = "";
				$.each(data[0].datalist, function(i, m){
					var cflag = true;
					$("#slttopicrecommend .seltopic").each(function(k,v){
						var sltw = $(this).attr("rtext");
						if(m == sltw){
							cflag = false;
						}
					});
					var sltcolor="";
					if(cflag){
						//sltcolor = "style='margin:2px;cursor:pointer;color:blue;width:150px;float:left;'";
						sltcolor = "class='notselected'";
					}
					else{
						//sltcolor = "style='margin:2px;cursor:pointer;color:blue;width:150px;float:left;background-color:red;'";
						sltcolor = "class='selected'";
					}
					//处理情感字段 显示
					var dm = "";
					var vm = "";
					var emotype = "";
					if(isemotype){
						var dmarr = m.split(",");
						var tmparr = [];
						for(var i =0; i< dmarr.length-1; i++){
							tmparr.push(dmarr[i]);
						}
						vm = tmparr;
						emotype = "emotype='"+dmarr[dmarr.length-1]+"'";
						var dmtext = emoval2text(dmarr[dmarr.length-1]);
						dm = tmparr+"("+dmtext+")";
					}
					else{
						dm = m;
						vm = m;
					}
					groupwordshtml +="<a "+sltcolor+" rtext='"+vm+"' "+emotype+" btype='tbnull' >"+dm+"</a>"
				});				
				$("#groupwords").append(groupwordshtml);
				$("#groupwords a[rtext][btype]").bind("click", function(){
					if(isemotype){
						_this.sendEmotion(this);
					}
					else{
						_this.sendTopic(this);
					}
				});

				//显示分页
				if(data[0].totalcount>pagesize){
					pageDisplay(data[0].totalcount, searchGroup, "grouppage", _this.pagesize, _this.curpage2);
				}
			}
			else{
				$("#groupwords").text("组合查询暂无数据！");
			}
		}, "json", function(){}, function(){ 
			$("#groupwords").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:305px;' id='wait3'/>");
		}, function(){$("#wait3").css({display:"none"});});
	}
	//初始化调用方法
	initTopicRecommend();
}
//choiceVal 可选数据,来自传入静态数组,或使用url动态获取
//successResult 选择后回调函数
//selectVal 已选择数据
//isnot 标签是否有"不"选择按钮
//showselectallbtn 是否显示 "选择全部"按钮
//显示名称
function myAccountSelect(successResult, selectVal, choiceVal, isemo, isnot, batchquery, showselectallbtn, isgo, showName){
	var _this = this;
	var page; //第几页
	var pagesize=20; //每页显示条数
	var curpage2;//当前页码
	var username = "";
	if(isgo == undefined){
		isgo = true;
	}
	var sltVal;
	if(selectVal == undefined){
		sltVal = []; 
	}
	else{
		sltVal = selectVal;
	}
	var isemotype = false;
	if(isnot == undefined){
		isnot = false;
	}
	if(showName == undefined){
		showName = "用户";
	}

	var initAccountSelect = function(){
		$("#accountsbox").remove();
		$("#batchuserdiv").remove();
		var accounthtml ="搜索"+showName+":<input type='text' id='accounttxt' name='accounttxt' /> <input type='button' name='sac' id='sac' value='搜索' />(使用*模糊搜索)";
		if(batchquery){
			accounthtml += "<input type='button' name='batchquery' id='batchquery' value='批量查询' />";
		}
		accounthtml += "<fieldset>"; 
		accounthtml +="<legend>"+showName+"列表</legend>";
		accounthtml +="<div id='groupaccounts'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/></div>";
		accounthtml +="<div id='accountpage' style='margin-top:2px;float:left;width:100%;margin:5px;'></div>";
		if(showselectallbtn){
			accounthtml +="<div class='search' id='accountselectallbtndiv' style='float:left;width:100%;'><input id='accountselectallbtn' name='accountselectallbtn' type='button' value='全选' /> <input id='accountunselectallbtn' name='accountunselectallbtn' type='button' value='反选' /> <input id='accountunselectnonebtn' name='accountunselectnonebtn' type='button' value='清空' /></div>";
		}
		accounthtml +="</fieldset>";
		accounthtml += "<fieldset>"; 
		accounthtml +="<legend>已选择的"+showName+"</legend>";
		accounthtml +="<div id='selectedaccounts'></div>";
		accounthtml +="</fieldset>";
		accounthtml +="<div class='search'><input id='accsubmit' name='accsubmit' type='button' value='选择完毕' /></div>";

		$("<div id='accountsbox' title='"+showName+"列表'></div>").insertAfter("body");
		$("#accountsbox").append(accounthtml);
		//批量添加用户
		var batchhtml ='';
			batchhtml+='<table class="formtable">';
			batchhtml+='<tr>';
			batchhtml+='<td class="tdleft">'+showName+'昵称：</td>';
			batchhtml+='<td>';
			batchhtml+='<textarea rows="25" cols="15" id="batch_screen_name"></textarea>';
			batchhtml+='</td>';
			batchhtml+='<td class="tdtip"></td>';
			batchhtml+='</tr>';
			batchhtml+='<tr id="noexistnametr" style="display:none;">';
			batchhtml+='<td class="tdleft">右边'+showName+'昵称不存在请重新输入：</td>';
			batchhtml+='<td colspan="2">';
			batchhtml+='<textarea rows="8" cols="15" id="noexistname"></textarea>';
			batchhtml+='</td>';
			batchhtml+='</tr>';

			batchhtml+='<tr id="repeatusertr" style="display:none;">';
			batchhtml+='<td class="tdleft">右边'+showName+'昵称存在重复项,仅分别保留一项,点击"获取结果",以确认：</td>';
			batchhtml+='<td colspan="2">';
			batchhtml+='<textarea rows="5" cols="15" id="repeatuser"></textarea>';
			batchhtml+='</td>';
			batchhtml+='</tr>';

			batchhtml+='<tr id="systemrepeatusertr" style="display:none;">';
			batchhtml+='<td class="tdleft">系统中有多个'+showName+'分别拥有右边昵称,点击"获取结果"确认后,可分别选择：</td>';
			batchhtml+='<td colspan="2">';
			batchhtml+='<textarea rows="4" cols="15" id="systemrepeatuser"></textarea>';
			batchhtml+='</td>';
			batchhtml+='</tr>';

			batchhtml+='</table>';
			$("<div id='batchuserdiv' style='display:none;'></div>").insertAfter("body");
			$("#batchuserdiv").append(batchhtml);



		if(isemo !="" && isemo!=undefined){
			isemotype = isemo; 
		}
		$("#accountsbox").dialog({
			autoOpen: false,
			modal:true,
			width:550,
			height:360,
			close:function(){
				$("#accountemotiondiv").css("display", "none");
			}
		});
		selectedAccount(sltVal);

		searchAccount(pagesize, 1, username);
		//$("#groupaccounts").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/>");
		$("#accountpage").empty();
		$("#accountsbox").dialog("open");

		$("#accounttxt").unbind("change");

		$("#batchquery").bind("click", function(){
			$("#batch_screen_name").val("");
			$("#batch_screen_name").removeAttr("style");
			$("#noexistnametr").hide();
			$("#noexistname").val("");
			$("#repeatusertr").hide();
			$("#repeatuser").val("");
			$("#systemrepeatusertr").hide();
			$("#systemrepeatuser").val("");
			//$("#batchuserdiv").dialog("open");
			$("#batchuserdiv").dialog("destroy"); 
			$("#batchuserdiv").dialog({
				autoOpen: true,
				title:"查询"+showName+"是否存在",
				modal:true,
				height:560,
				width:573,
				buttons:{
					"查询":function(){
						var uhtml = "";
						var users = $("#batch_screen_name").val();
						var userArr = users.split('\n');
						var names = {};
						names.users_screen_name = [];
						var repeatUserArr = []; //存在的重复用户
						$.each(userArr, function(di, ditem){
							if(ditem != ""){
								var tmpitem = commonFun.trim(ditem);
								if(!names.users_screen_name.inArray(tmpitem)){
									names.users_screen_name.push(tmpitem);
								}
								else{
									if(!repeatUserArr.inArray(tmpitem)){
										repeatUserArr.push(tmpitem);
									}
								}
							}
						});
						var dataobj={type:"query_screen_name", names:names};
						$.ajax({
							type: "POST",
							contentType: "application/json",
							dataType: "json",
							async:false,
							url: config.modelUrl + "feature_model.php",
							data: JSON.stringify(dataobj),
							beforeSend:function(){
							},
							complete:function(){
							},
							success: function (msg) {
								if(msg.flag == 1) { //有不存在的用户
									$("#batch_screen_name").css({"width":"199px","height":"296px"});
									$("#noexistnametr").show();
									$("#repeatusertr").hide();
									$("#systemrepeatusertr").hide();
									$("#noexistname").val(msg.noexistname.join('\n'));
								} 
								else{
									$("#batch_screen_name").css({"width":"199px","height":"256px"});
									$("#noexistnametr").val("");
									$("#noexistnametr").hide();
									$("#repeatusertr").hide();
									$("#systemrepeatusertr").hide();
									if(repeatUserArr.length >0 || msg.flag == 2){
										//有重复用户昵称
										if(repeatUserArr.length >0){
											$("#repeatusertr").show();
											$("#repeatuser").val(repeatUserArr.join('\n'));
											$("#batch_screen_name").val(names.users_screen_name.join('\n'));
										}
										//系统中存在重复昵称
										if(msg.flag == 2){
											$("#systemrepeatusertr").show();
											$("#systemrepeatuser").val(msg.sysrepeatuser.join('\n'));
										}
										choiceVal =  msg.users;

										//显示获取结果按钮
										//$(".ui-dialog-buttonset button").eq(1).show(); //隐藏指定的button
										$("#batchuserdiv").dialog("option","buttons", [
												{
													text: "获取结果",
													click: function() {
														if(choiceVal != undefined && choiceVal.length>0){
															searchAccount(pagesize, 1);
															$("#batchuserdiv").dialog("close");
														}
													}
												}
											]);
									}
									else{
										choiceVal =  msg.users;
										searchAccount(pagesize, 1);
										$("#batchuserdiv").dialog("close");
									}
								}
							}
						});
					}/*,
					"获取结果":function(){
						if(choiceVal != undefined && choiceVal.length>0){
							searchAccount(pagesize, 1);
							$("#batchuserdiv").dialog("close");
						}
					}
					*/
				}
			});
		});
		//反选
		$("#accountunselectallbtn").bind("click", function(){
			$("#groupaccounts").find("a").each(function(i, item){
				sendAccount($(item));
			});
		});
		//全选
		$("#accountselectallbtn").bind("click", function(){
			$("#selectedaccounts").empty();
			$("#groupaccounts").find("a").each(function(i, item){
				sendAccount($(item));
			});
		});
		//清空
		$("#accountunselectnonebtn").bind("click", function(i, item){
			$("#selectedaccounts .cancleitem").each(function(i, m){
				cancleWord($(m));
			});
		});

		$("#sac").bind("click", function(){
			username = $("#accounttxt").val();
			if(choiceVal != undefined && choiceVal.length>0){
				choiceAccount(choiceVal, username);
				$("#waitimg").css({display:"none"});
			}
			else{
				searchAccount(pagesize, 1, username);
				$("#groupaccounts").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/>");
				$("#accountpage").empty();
			}
		});
		$("#accsubmit").unbind("click");
		$("#accsubmit").bind("click", function(){
			var resultArr = [];
			$("#selectedaccounts .selword").each(function(i,m){
				var accountObj ={};
				accountObj.code= $(this).attr("code"); 
				accountObj.name = $(this).text();
				if($(this).attr("emotype") != undefined){
					accountObj.emotype = $(this).attr("emotype");
				}
				if($(this).attr("exclude") != undefined){
					accountObj.exclude = $(this).attr("exclude");
				}
				resultArr.push(accountObj);
			});		
			/*存储最后结果,使用successResult()函数调用*/
			successResult(resultArr);
			$("#accountemotiondiv").css("display", "none");
			$("#accountsbox").dialog("close");
		});
	};
	this.emotionChk= function(){
		var emohtml = '<div id="accountemotiondiv" style="position:absolute;display:none;z-index:9999;background-color:white;border:1px solid blue;font-size: 12px;"><span id="accountemotionspan">';
			emohtml +='<input type="checkbox" name="accountemotion" id="accountemotion1" value="1" class="outborder" /><label for="accountemotion1">反对</label>';
			emohtml +='<input type="checkbox" name="accountemotion" id="accountemotion2" value="2" class="outborder" /><label for="accountemotion2">负面</label>';
			emohtml +='<input type="checkbox" name="accountemotion" id="accountemotion3" value="3" class="outborder" /><label for="accountemotion3">中性</label>';
			emohtml +='<input type="checkbox" name="accountemotion" id="accountemotion4" value="4" class="outborder" /><label for="accountemotion4">正面</label>';
			emohtml +='<input type="checkbox" name="accountemotion" id="accountemotion5" value="5" class="outborder" /><label for="accountemotion5">赞赏</label>';
			emohtml +='<input type="checkbox" name="accountemotion" id="accountemotionall" value="*" class="outborder" /><label for="accountemotionall">全部</label>';
			emohtml +='<br/><a id="itemaccountemo" style="cursor:pointer;">确定</a></span></div>';
			if($("#accountemotiondiv").length == 0){
				//$(emohtml).insertAfter("body");
				$("body").append(emohtml);
			}
			$("#accountemotiondiv input[name=accountemotion]").bind("click", function(){
				var itemid = $(this).attr("id");
				if(itemid == "accountemotionall"){
					if($(this).prop("checked") == true){
						$("#accountemotiondiv input[name=accountemotion]").attr("checked", true);
					}
					else{
						$("#accountemotiondiv input[name=accountemotion]").attr("checked", false);
					}
				}
				else{
					var chklen = $("#accountemotiondiv input[name=accountemotion]:checked").length;
					var alllen = $("#accountemotiondiv input[name=accountemotion]").length;
					if($(this).prop("checked") == true){
						if(chklen == alllen-1){
							$("#accountemotiondiv input[name=accountemotion]").attr("checked", true);
						}
					}
					else{
						$("#accountemotiondiv input[name=accountemotion][id=accountemotionall]").attr("checked", false);
					}
				}
			});
	}
	var selectedAccount = function(sltVal){
		var slthtml = "";
		$.each(sltVal, function(i,m){
			slthtml += createUtilSpanHtml('selword', m.name, m.code, isnot, m.exclude, 'cancleWord', undefined, m.emotype);
		});
		$("#selectedaccounts").append(slthtml);
	}
	var choiceAccount = function(choiceArr, username){
		var rethtml = "";
		$.each(choiceArr, function(i, m){
			var flag = false; //已选择
			var code = m.users_id == undefined ? m.code : m.users_id;
			var name = m.users_screen_name == undefined ? m.name : m.users_screen_name;
			if(sltVal!=undefined && sltVal.length>0){
				$.each(sltVal, function(k,v){
					if(m.emotype != undefined){
						if(v.emotype == m.emotype && v.code == code){
							flag = true;
							return false;
						}
					}
					else{
						if(v.code == code){
							flag = true;
							return false;
						}
					}
				});
			}
			var slco = "";
			if(flag){
				slco = "class='selected'";
			}
			else{
				slco = "class='notselected'";
			}
			//var search = username.replace(/^\*/g, "*.").replace(/\*$/g,".*");
			//var re = new RegExp("^"+search+"$", "g");
			var re = new RegExp("^"+username+".*", "g");
			if(re.test(name)){
				var emoattr = "";
				var bname = name;
				if(m.emotype != undefined){
					emoattr = "emotype='"+m.emotype+"'";
					bname = name+"("+emoval2text(m.emotype)+")";
				}
				var userurl = "";
				var icode = code;
				if(icode != undefined && icode !=null){
					var sourceid = 1;//新浪微博
					userurl = weiboUserurl(icode,sourceid);
				}
				var ahtml = '';
				if(isgo){
					ahtml = " <span href='"+userurl+"' style='color:#37547D;' repeatlink='true' >前往</span>";
				}
				rethtml += "<a "+slco+" onclick='sendAccount(this)' name='"+name+"' code='"+code+"' "+emoattr+" >"+bname+" "+ahtml+"</a>";
			}
		});				
		$("#groupaccounts").empty().append(rethtml);
		//阻止冒泡触发父级点击事件
		$("span[repeatlink]").unbind("click")
		$("span[repeatlink]").bind("click", function(event){
			var href = $(this).attr("href");
			window.open(href, "_blank")
			event.stopPropagation();
		});
	};
	this.sendAccount = function(ele){
		var acname = $(ele).attr("name");
		var acid = $(ele).attr("code");
		var acemo = $(ele).attr("emotype");
		if(isemotype){
			_this.emotionChk();
			var ex,ey;
			//ex = eve.offsetX;
			//ey = eve.offsetY;
			//ex = eve.clientX;
			//ey = eve.clientY;
			ex = $(ele).offset().left + 30;
			ey = $(ele).offset().top - $("#accountemotiondiv").height();
			$("#accountemotiondiv").css({top:ey+"px", left:ex+"px", display:"block"});
			//$("#itememo").attr({code:$(this).attr("code"), name:$(this).text()});;
			$("#accountemotionspan input[name=accountemotion]").attr("checked", false);
			$("#itemaccountemo").unbind("click");  //一次创建多次绑定时需要unbind 否则会多次添加
			$("#itemaccountemo").bind("click", function(){
				var chklen = $("#accountemotiondiv input[name=accountemotion]:checked").length;
				if(chklen==0){
					alert("请选择情感!");
					return false;
				}
				var rtArr = [];
				var rvArr = [];
				$("#accountemotiondiv input[name=accountemotion]:checked").each(function(i,m){
					var rtid = $(this).attr("id");
					if(rtid != "accountemotionall"){ //全部checkbox 不添加到数组
						var rt = $("#accountemotiondiv label[for="+rtid+"]").text();
						rtArr.push(rt);
						var rv = $(this).val();
						rvArr.push(rv);
					}
				});
				var alllen = $("#accountemotiondiv input[name=accountemotion]").length;
				var emotype = "";
				if(rvArr.length>0){
					if(rvArr.length == alllen-1){
						acname = acname+"(全部)";
						emotype = "*";
					}
					else{
						acname = acname+"("+rtArr.join(",")+")";
						emotype = rvArr.join(",");
					}
				}
				var wflag = true;
				$("#selectedaccounts .selword").each(function(i,m){
					var sltw = $(this).attr("code");
					if(acid == sltw){
						$(this).parent().remove();
						$(ele).removeClass().addClass("notselected");
						wflag = false;
					}
				});
				if(wflag){
					//$("#acc_"+account.userid).css("background-color", "red");
					$(ele).removeClass().addClass("selected");
					var spanutilhtml = createUtilSpanHtml('selword', acname, acid, isnot, undefined, 'cancleWord', undefined, emotype);
					$("#selectedaccounts").append(spanutilhtml);
				}
				else{
					//alert("选择相同了!");
				}
				$("#accountemotiondiv").css({display:"none"});
			});

		}
		else{
			acname = $(ele).attr("name");
			acid = $(ele).attr("code");
			acemo = $(ele).attr("emotype");
			var wflag = true;
			$("#selectedaccounts .selword").each(function(i,m){
				var sltw = $(this).attr("code");
				var sltemo = $(this).attr("emotype");
				if(acemo != undefined){
					if(acid == sltw && acemo == sltemo){
						$(this).parent().remove();
						$(ele).removeClass().addClass("notselected");
						wflag = false;
					}
				}
				else{
					if(acid == sltw){
						$(this).parent().remove();
						$(ele).removeClass().addClass("notselected");
						wflag = false;
					}
				}
			});
			if(wflag){
				$(ele).removeClass().addClass("selected");
				var dname = acname;
				if(acemo != undefined){
					dname = acname+"("+emoval2text(acemo)+")";
				}
				var spanutilhtml = createUtilSpanHtml('selword', dname, acid,  isnot, undefined, 'cancleWord', undefined, acemo);
				$("#selectedaccounts").append(spanutilhtml);
			}
			else{
				//alert("选择相同了!");
			}
		}
	};
	this.cancleWord = function(elm){
		$(elm).parent().remove();
		var delemo = $(elm).attr("emotype");
		if(delemo != undefined && !isemo){
			$("#groupaccounts a[code="+$(elm).attr("code")+"][emotype="+delemo+"]").removeClass().addClass("notselected");
		}
		else{
			$("#groupaccounts a[code="+$(elm).attr("code")+"]").removeClass().addClass("notselected");
		}
	};
	var searchAccount = function(pagesize, curpage, username){
		if(choiceVal == undefined || choiceVal.length==0){
			if(curpage == '' || curpage == null || curpage == undefined ){
				_this.curpage2 = 1;
			}
			else{
				_this.curpage2 = curpage;
			}
			if(username == undefined){
				username = $("#accounttxt").val();
			}

			//动态数据
			var searchnameUrl = config.solrData+"?type=searchname&blurname="+encodeURIComponent(username)+"&page="+_this.curpage2+"&pagesize="+pagesize;
			ajaxRequest(searchnameUrl, function(data){
				if(data[0].totalcount>0) {
					choiceAccount(data[0].datalist, "");
					//显示分页
					if(data[0].totalcount>pagesize){
						pageDisplay(data[0].totalcount, searchAccount, "accountpage", pagesize, _this.curpage2);
					}
				}
				else{
					$("#groupaccounts").text(""+showName+"查询暂无数据！");
				}
			}, "json", function(){}, function(){ 
				$("#groupaccounts").html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg'/>");
			}, function(){$("#waitimg").css({display:"none"});});

		}
		else{
			$("#accountpage").empty();
			choiceAccount(choiceVal, "");
			$("#waitimg").css({display:"none"});
		}
	}
	//初始化调用方法
	initAccountSelect();
}
function myURLGenerator(urlconfigrule, successResult, selectedVal, showName){
	var datafromfield = ""; //存来自父任务out字段的下拉菜单html
	var itemObjToHtml = function(item, itemObj){
		var rethtml = "";
		rethtml +="<div class='search' style='vertical-align: middle;'><span style='text-align:right;width:70px;display:inline-block;'>"+itemObj.label+"：</span>";
		switch(itemObj.datatype){
			case "string":
			case "int":
				if(itemObj.multivalue){
					rethtml += "<textarea id='urltpl"+item+"' name='urltpl"+item+"' rows='15' cols='20'></textarea>";
				}
				else{
					rethtml += "<input id='urltpl"+item+"' name='urltpl"+item+"' />";
				}
				break;
			case "object":
				if(itemObj.multivalue){
					rethtml +="<span id='urltpl"+item+"_addbtn' name='urltpl_field_addbtn' objfield='"+item+"' style='float:right;'><a href='javascript:void(0);'>添加多组</a></span>";
				}
				//对象数组容器
				rethtml +="<div id='urltpl"+item+"_div' name='urltpl"+item+"_div' groupcount='1'>"
				var cObj = itemObj.child;
				rethtml += "<div name='urltpl"+item+"_childdiv' groupnum='1' style='border:1px solid red;'>";
				if(itemObj.multivalue){
					rethtml += "<span name='urltpl_field_deletebtn' style='float:right;'><a href='javascript:void(0);'>删除</a></span>"
				}
				for(var citem in cObj){
					rethtml += itemObjToHtml(citem, cObj[citem]);
				}
				rethtml += "</div>";
				rethtml +="</div>";
				break;
			default:
				break;
		}
		if(itemObj.datatype != "object"){
			rethtml += "<select id='urltpl_"+item+"_datafrom' name='urltpl_field_dataform' tplfield='"+item+"' style='display:none;' ><option value='-1'>请选择..</option></select>";
			rethtml += "<a href='javascript:void(0);' name='urltpl_datafrom_btn' tplfield='"+item+"' datafrom='manully'>来自父任务</a>";
		}
		rethtml +="</div>";
		if(itemObj.multivalue && itemObj.split){
			rethtml +="<div class='search'><span style='text-align:right;width:70px;display:inline-block;'>是否拆分：</span>";
			rethtml +="<input type='radio' name='urltpl_field_split' tgtfield='"+item+"' id='urltpl"+item+"_split1' value='1' checked='checked'><label for='urltpl"+item+"_split1'>是</label>";
			rethtml +="<input type='radio' name='urltpl_field_split' tgtfield='"+item+"' id='urltpl"+item+"_split0' value='0'><label for='urltpl"+item+"_split0'>否</label>";
			rethtml +="</div>";
			rethtml +="<div class='search'><span style='text-align:right;width:70px;display:inline-block;'>拆分步长：</span>";
			rethtml +="<input type='text' name='urltpl"+item+"_split_count' id='urltpl"+item+"_split_count' value='1'>";
			rethtml +="</div>";
		}
		return rethtml;
	};
	var addObjFieldItem = function(fielditem){
		var groupcount = $("#urltpl"+fielditem+"_div").attr("groupcount");
		var nextnum = parseInt(groupcount, 10) + 1;
		var tmphtml = "<div name='urltpl"+fielditem+"_childdiv' groupnum='"+nextnum+"' style='border:1px solid red;'><span name='urltpl_field_deletebtn' style='float:right;'><a href='javascript:void(0);'>删除</a></span>";
		for(var item in urlconfigrule[fielditem].child){
			tmphtml += itemObjToHtml(item, urlconfigrule[fielditem].child[item]);
		}
		tmphtml += "</div>";
		$("#urltpl"+fielditem+"_div").append(tmphtml);
		$("#urltpl"+fielditem+"_div").attr("groupcount", nextnum);
	};
	/*
	 * df当前数据来源状态
	 * tf字段名
	 * */
	var dataFromChange = function(df, tf){
		if(df == "manully"){
            //来自参数的 TODO
			$("select[name=urltpl_field_dataform][tplfield="+tf+"]").empty().append(datafromfield);
			$("select[name=urltpl_field_dataform][tplfield="+tf+"]").show();
			$("input[name=urltpl"+tf+"]").hide();
			$("textarea[name=urltpl"+tf+"]").hide();
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").attr("datafrom", "parentfield");
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").text("手动添加");
		}
		else if(df == "parentfield"){
			$("select[name=urltpl_field_dataform][tplfield="+tf+"]").hide();
			$("input[name=urltpl"+tf+"]").show();
			$("textarea[name=urltpl"+tf+"]").show();
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").attr("datafrom", "manully");
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").text("来自父任务");
		}
	};
	var tmp = [];
	var fieldtmp = [];
	var firstItem = function(urlconfigrule){
		for(var item in urlconfigrule){
			if(item != "URLexpression"){
				return item;
			}
		}
	};
	var fieldValueToURL = function(result, urlconfigrule){
		var count = 0;
		for(var item in urlconfigrule){
			if(typeof(urlconfigrule[item]) == 'object'){
				if(!urlconfigrule[item].cycled){
					urlconfigrule[item].cycled = true;
					var datafrom = urlconfigrule[item].datafrom;
					if(urlconfigrule[item].value){
						$.each(urlconfigrule[item].value, function(ui, uitem){
							var tmpu;
							var fieldType = "Enum"; 
							if(isArray(uitem)){
								var tmparr = [];
								$.each(uitem, function(ui, uobj){
									if(typeof(uobj) == 'object'){
										fieldType = "Obj";
										tmparr.push(JSON.stringify(uobj));
									}
									else{
										tmparr.push(uobj);
									}
								});
								tmpu = tmparr.join(",").replace(/{/g, '(').replace(/}/g, ')').replace(/"/g, '');
							}
							else if(typeof(uitem) == 'object'){
								tmpu = JSON.stringify(uitem);
							}
							else{
								tmpu = uitem;
							}
						if(!fieldtmp.inArray(item)){
							var etmp = ""; 
							if(datafrom == "manully"){
								etmp = ""+item+":"+fieldType+"("+tmpu+")"; 
							}
							else if(datafrom == "parentfield"){
								etmp = ""+item+":"+fieldType+"(|"+tmpu+"|)"; 
							}
							tmp.push(etmp);
							fieldtmp.push(item);
						}
						else{
							var i = 0;
							$.each(fieldtmp, function(fi, fitem){
								if(fitem == item){
									i = fi;
									return false;
								}
							});
							if(datafrom == "manully"){
								tmp[i] = ""+item+":"+fieldType+"("+tmpu+")";
							}
							else if(datafrom == "parentfield"){
								tmp[i] = ""+item+":"+fieldType+"(|"+tmpu+"|)";
							}
							if(item == firstItem(urlconfigrule)){
								for(var tmpitem in urlconfigrule){
									if(tmpitem != firstItem(urlconfigrule) && urlconfigrule[tmpitem].cycled && urlconfigrule[tmpitem].value.length > 1){
										urlconfigrule[tmpitem].cycled = false;
									}
								}
							}
						}
						fieldValueToURL(result, urlconfigrule);
						var lurl = "taskurl$:\""+urlconfigrule["taskurl"]+"\"";
						if(urlconfigrule["mainurl"] != undefined){
							lurl += "mainurl$:\""+urlconfigrule["mainurl"]+"\"";
						}
						lurl += "{"+tmp.join(" ")+"}";
						if(!result.inArray(lurl)){
							result.push(lurl);
						}
						});
					}
				}
			}
		}
	};
	var initURLGenerator = function(){
		if(showName == undefined){
			showName = "";
		}
		$("#urltplbox").remove();
		var urltplhtml = "";
		urltplhtml +="<fieldset>";
		urltplhtml +="<legend>"+showName+"</legend>";
		urltplhtml +="<div>";
		for(var item in urlconfigrule){
			if(typeof(urlconfigrule[item]) == 'object'){//排除了taskurl和mainurl两个字段
				var itemObj = urlconfigrule[item];
				urltplhtml += itemObjToHtml(item, itemObj);
			}
		}
		urltplhtml +="</div>";
		urltplhtml +="</fieldset>";
		/*
		urltplhtml +="<fieldset>"; 
		urltplhtml +="<legend>已选择的"+showName+"</legend>";
		urltplhtml +="<div id='selectedurltpl'></div>";
		urltplhtml +="</fieldset>";
		*/
		$("<div id='urltplbox' title='"+showName+"列表'></div>").insertAfter("body");
		$("#urltplbox").append(urltplhtml);
		//数据来自于父级任务时, 列出父级任务out对应的字段值
		if(config.allTplOutfield == undefined || config.allTplOutfield.length == 0){
			getTplOutfield(function(data){
				if(data){
					config.allTplOutfield = data;
				}
			});
		}
		var opthtml = "";
		$.each(config.allTplOutfield, function(di, ditem){
			opthtml +='<option value="'+ditem.code+'">'+ditem.name+'</option>';
		});
		datafromfield = opthtml;
		//当字段为objcet类型时,可以添加多组值
		$("span[name=urltpl_field_addbtn]").unbind("click");
		$("span[name=urltpl_field_addbtn]").bind("click", function(){
			var fielditem = $(this).attr("objfield");
			addObjFieldItem(fielditem);
		});
		//删除添加的组
		$("span[name=urltpl_field_deletebtn]").die("click");
		$("span[name=urltpl_field_deletebtn]").live("click", function(){
			$(this).parent("div[groupnum]").remove();
		});
		$("input[name=urltpl_field_split]").unbind("click");
		$("input[name=urltpl_field_split]").bind("click", function(){
			var tgtfield = $(this).attr("tgtfield");
			var split = $("input[name=urltpl_field_split][tgtfield="+tgtfield+"]:checked").val();
			if(parseInt(split, 10)){
				$("#urltpl"+tgtfield+"_split_count").parent("div").show();
			}
			else{
				$("#urltpl"+tgtfield+"_split_count").parent("div").hide();
			}
		});
		//选择来自父任务
		$("a[name=urltpl_datafrom_btn]").die("click");
		$("a[name=urltpl_datafrom_btn]").live("click", function(){
			var df = $(this).attr("datafrom"); //数据来自
			var tf = $(this).attr("tplfield"); //字段
			dataFromChange(df, tf);
		});
		if(selectedVal){
			for(var item in selectedVal){
				if(typeof(selectedVal[item]) == 'object'){
					var itemObj = selectedVal[item];
					var datatype = itemObj.datatype;
					switch(datatype){
						case "string":
						case "int":
							if(itemObj.datafrom == "parentfield"){
								$("#urltpl_"+item+"_datafrom").val(itemObj.value[0]);
								dataFromChange("manully", item);
							}
							else{
								dataFromChange("parentfield", item);
								var vlen = 0;
								if(itemObj.value != undefined){
									vlen = itemObj.value.length;
								}
								if(vlen > 0){
									if(itemObj.multivalue){ //多值
										$("input[name=urltpl_field_split][tgtfield="+item+"][value=1]").attr("checked", true);
										var tmpfirst = itemObj.value[0].split(",");
										$("#urltpl"+item+"_split_count").val(tmpfirst.length);
										var valhtml = [];
										$.each(itemObj.value, function(vi, vitem){
											$.each(vitem.split(","), function(vvi, vvitem){
												valhtml.push(decodeURI(vvitem));;
											});
										});
										$("#urltpl"+item+"").val(valhtml.join("\n"));
									}
									else{ //单值
										$("#urltpl"+item+"").val(itemObj.value[0]);
										$("input[name=urltpl_field_split][tgtfield="+item+"][value=0]").attr("checked", false);
									}
								}
							}
							break;
						case "object":
							var groupcount = itemObj.value[0].length;
							for(var i=1;i<groupcount;i++){
								addObjFieldItem(item);
							}
							//为object 字段赋值
							$.each(itemObj.value, function(vi, vitem){
								if(isArray(vitem)){
									$.each(vitem, function(ti, titem){
										var gn = ti+1;
										for(var field in titem){
											if(itemObj.child[field].datafrom != undefined && itemObj.child[field].datafrom == "manully"){
												dataFromChange("parentfield", field);
												$("div[name=urltpl"+item+"_childdiv][groupnum="+gn+"] input[name=urltpl"+field+"]").val(titem[field]);
											}
											else{
												dataFromChange("manully", field);
												$("select[name=urltpl_field_dataform][tplfield="+field+"]").val(titem[field].replace(/\|/g, ""));
											}
										}
									});
								}
								else{
									for(var field in vitem){
										$("div[name=urltpl"+item+"_childdiv][groupnum="+gn+"] input[name=urltpl"+field+"]").val(vitem[field]);
									}
								}
							});
							break;
						default:
							break;
					}
				}
			}
		}
		$("#urltplbox").dialog({
			autoOpen: true,
			modal:true,
			width:550,
			buttons:{
				"确定":function(){
					for(var item in urlconfigrule){
						var itemObj = urlconfigrule[item];
						itemObj.cycled = false;
						var datatype = itemObj.datatype;
						switch(datatype){
							case "string":
							case "int":
								var kwvalarr = [];
								var datafrom = $("a[name=urltpl_datafrom_btn][tplfield="+item+"]").attr("datafrom");
								if(datafrom == "manully"){
									var kwval = $("#urltpl"+item+"").val();
									if(kwval){
										if(itemObj.multivalue){ //多值时处理
											var karr = kwval.split("\n");
											var split = $("input[name=urltpl_field_split][tgtfield="+item+"]:checked").val();
											var splitcount = 1;
											if(parseInt(split,10)){//拆分
												splitcount = parseInt($("#urltpl"+item+"_split_count").val(), 10);
											}
											else{ //不拆分
												splitcount = karr.length;
											}
											do{
												var tmp = karr.splice(0, splitcount);
												if(tmp.length > 0 && tmp[0]){
													tmp = encodeURI(tmp);
													kwvalarr.push(tmp);
												}
											}while(karr.length > 0);
										}
										else{ //单值处理
											var tmp = kwval;
											tmp = encodeURI(tmp);
											kwvalarr.push(tmp);
										}
									}
									/*
									else{
										var tmp = urlconfigrule[item].value;
										if(tmp){
											tmp = encodeURI(tmp);
											kwvalarr.push(urlconfigrule[item].value);
										}
									}
									*/
									if(kwvalarr.length > 0){
										urlconfigrule[item].value = kwvalarr;
										urlconfigrule[item].datafrom = "manully";
									}
								}
								else if(datafrom == "parentfield"){
									var pfield = $("#urltpl_"+item+"_datafrom").val();
									kwvalarr.push(pfield);
									urlconfigrule[item].value = kwvalarr;
									urlconfigrule[item].datafrom = "parentfield";
								}
								break;
							case "object":
								var kwvalarr = [];
								var grouparr = [];
								$("div[name=urltpl"+item+"_childdiv]").each(function(di, ditem){
									var tmpobj = {};
									for(var citem in itemObj.child){
										var datafrom = $("a[name=urltpl_datafrom_btn][tplfield="+citem+"]").attr("datafrom");
										itemObj.child[citem].datafrom = datafrom;
										var retval = encodeURI($(ditem).find("input[name=urltpl"+citem+"]").val()); 
										if(datafrom == "parentfield"){
											retval = "|"+$("#urltpl_"+citem+"_datafrom").val()+"|";
										}
										tmpobj[citem] = retval;
									}
									grouparr.push(tmpobj);
								});
								if(grouparr.length > 0){
									if(itemObj.multivalue){//多值
										var split = $("input[name=urltpl_field_split][tgtfield="+item+"]:checked").val();
										var splitcount = 1;
										if(parseInt(split, 10)){ //拆分
											splitcount = parseInt($("#urltpl"+item+"_split_count").val(), 10);
										}
										else{ //不拆分
											splitcount = grouparr.length;
										}

										do{
											var tmp = grouparr.splice(0, splitcount);
											//kwvalarr.push(JSON.stringify(tmp));
											kwvalarr.push(tmp);
										}while(grouparr.length > 0);
									}
									else{ //单组
										kwvalarr.push(grouparr);
									}
								}
								urlconfigrule[item].value = kwvalarr;
								urlconfigrule[item].datafrom = "manully";
								break;
							default:
								break;
						}
					}
					var urlarr = [];
					tmp = [];
					fieldtmp = []; 
					fieldValueToURL(urlarr, urlconfigrule); 
					var retobj = {};
					retobj.urls = urlarr;
					retobj.urlconfigrule = urlconfigrule;
					successResult(retobj);
					$("#urltplbox").dialog("close");
				},
				"取消":function(){
					$("#urltplbox").dialog("close");
				}
			},
			close:function(){
			}
		});
	};
	initURLGenerator();
}
function myParamGenerator(paramrule, successResult, selectedVal, showName){
	var datafromfield = ""; //存来自父任务out字段的下拉菜单html
    var paramdivnum = 0;
	var paramObjToHtml = function(paramObj, initVal){
        paramdivnum++;
		var rethtml = "";
		rethtml +="<div name='paramdiv' class='search' style='vertical-align: middle;' paramdivnum='"+paramdivnum+"' col_type='"+paramObj.col_type+"' code='"+paramObj.name+"' ><span style='text-align:right;width:70px;display:inline-block;'>"+paramObj.label+"：</span>";
		switch(parseInt(paramObj.col_type, 10)){
			case SE_TYPE_STRING:
			case SE_TYPE_INT32:
                var tmp = initVal ? initVal[paramObj.name] : "";
                rethtml += "<input id='urltpl_"+paramdivnum+"_"+paramObj.name+"_"+paramObj.col_type+"' code='"+paramObj.name+"'  value='"+tmp+"'/>";
				break;
            case SE_TYPE_TIMESTAMP:
                var tmp = initVal ? initVal[paramObj.name] : "";
                rethtml += '<input id="urltpl_'+paramdivnum+'_'+paramObj.name+'_'+paramObj.col_type+'" code="'+paramObj.name+'" value="'+formatTime('yyyy-MM-dd hh:mm', tmp)+'" class="Wdate" type="text" readonly="readonly" style="width:150px;" onclick="WdatePicker({dateFmt:\'yyyy-MM-dd HH:mm:ss\'})" />';
                break;
            case SE_TYPE_ARRAY:
                //rethtml += "<textarea id='urltpl"+paramObj.name+"' name='urltpl"+paramObj.name+"' rows='15' cols='20'></textarea>";
				//数组容器
				//rethtml +="<div id='urltpl"+paramObj.name+"_div' name='urltpl"+paramObj.name+"_div' groupcount='1'>"
				rethtml += "<div id='urltpl_"+paramdivnum+"_"+paramObj.name+"_"+paramObj.col_type+"' code='"+paramObj.name+"' style='border:1px solid red;'>";
                paramObj.col_type_ex.ele_col.label = '数组';
                paramObj.col_type_ex.ele_col.name = 'ele_col';
                rethtml += paramObjToHtml(paramObj.col_type_ex.ele_col);
                rethtml += "<div><input type='button' id='urltpl_"+paramdivnum+"_"+paramObj.name+"_"+paramObj.col_type+"_btn' name='urltpl_"+paramObj.name+"_"+paramObj.col_type+"_btn' paramdivnum='"+paramdivnum+"' value='新增'/></div>";
                var valHtml = '';
                if(initVal && initVal[paramObj.name]){
                    for(var i=0,ilen=initVal[paramObj.name].length;i<ilen;i++){
                        valHtml += "<span class='selwordsbox'><span class='useritem' code='"+JSON.stringify(initVal[paramObj.name][i])+"'>"+initVal[paramObj.name][i]+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                    }
                }
                rethtml += "<div>已添加的值:<span id='urltpl_"+paramdivnum+"_"+paramObj.name+"_"+paramObj.col_type+"_added'>"+valHtml+"</span></div>";
				rethtml += "</div>";
				//rethtml +="</div>";
                $("body").undelegate("input[name=urltpl_"+paramObj.name+"_"+paramObj.col_type+"_btn]", "click");
                $("body").delegate("input[name=urltpl_"+paramObj.name+"_"+paramObj.col_type+"_btn]", "click", function(){
                    var valdivnum = $(this).parent().parent().parent().attr("paramdivnum");
                    var addeddivnum = $(this).attr("paramdivnum");
                    //var tmparr = [];
                    var tmpobj = {};
                    var paramNodes = [];
                    getUserParamVal(valdivnum, paramObj.col_type, paramObj.name, tmpobj, paramNodes);
                    var disname = [];
                    if(typeof tmpobj.ele_col == "string" || typeof tmpobj.ele_col == "number"){
                        disname =  tmpobj.ele_col;
                    }
                    else{
                        for(var titem in tmpobj.ele_col){
                            if(typeof tmpobj.ele_col[titem] == "string" || typeof tmpobj.ele_col[titem] == "number"){
                                disname.push(tmpobj.ele_col[titem]); 
                            }
                        }
                    }
                    var html = "<span class='selwordsbox'><span class='useritem' code='"+JSON.stringify(tmpobj.ele_col)+"' >"+disname+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                    $("#urltpl_"+addeddivnum+"_"+paramObj.name+"_"+paramObj.col_type+"_added").append(html);
                });
                break;
			case SE_TYPE_OBJECT:
				//对象容器
				//rethtml +="<div id='urltpl_"+paramObj.name+"_"+paramObj.col_type+"' name='urltpl"+paramObj.name+"_div' groupcount='1'>"
				rethtml += "<div id='urltpl_"+paramdivnum+"_"+paramObj.name+"_"+paramObj.col_type+"' groupnum='1' style='border:1px solid red;'>";
                for(var item in paramObj.col_type_ex.sc_map){
                    paramObj.col_type_ex.sc_map[item].label = item; //添加上显示标签
                    paramObj.col_type_ex.sc_map[item].name = item; //添加上显示标签
                    var tmp = null;
                    if(initVal){
                        if(paramObj.col_type_ex.sc_map[item].col_type == SE_TYPE_OBJECT){
                            tmp = initVal[item];
                        }
                        else{
                            tmp = initVal;
                        }
                    }
                    rethtml += paramObjToHtml(paramObj.col_type_ex.sc_map[item], tmp);
                }
				rethtml += "</div>";
				//rethtml +="</div>";
				break;
			default:
				break;
		}
		rethtml +="</div>";
		return rethtml;
	};
	var initParamGenerator = function(){
		if(showName == undefined){
			showName = "";
		}
		$("#urltplbox").remove();
		var urltplhtml = "";
		urltplhtml +="<fieldset>";
		urltplhtml +="<legend>"+showName+"</legend>";
		urltplhtml +="<div id='paramtpldiv'>";
        /*
        for(var item in paramrule){
            urltplhtml += paramObjToHtml(paramrule[item]);
        }
        */
        urltplhtml += paramObjToHtml(paramrule, selectedVal);
		urltplhtml +="</div>";
		urltplhtml +="</fieldset>";
		$("<div id='urltplbox' title='"+showName+"列表'></div>").insertAfter("body");
		$("#urltplbox").append(urltplhtml);
		$("#urltplbox").dialog({
			autoOpen: true,
			modal:true,
			width:550,
			buttons:{
				"确定":function(){
                    var tmpobj = {};
                    var col_type = $("#paramtpldiv").children('div[name=paramdiv]').attr("col_type");
                    var code = $("#paramtpldiv").children('div[name=paramdiv]').attr("code");
                    var num = $("#paramtpldiv").children('div[name=paramdiv]').attr("paramdivnum");
                    var paramNodes = [];
                    var ret = getUserParamVal(num, col_type, code, tmpobj, paramNodes);
                    if(ret){
                        successResult(tmpobj, paramNodes);
                        $("#urltplbox").dialog("close");
                    }
				},
				"取消":function(){
					$("#urltplbox").dialog("close");
				}
			},
			close:function(){
			}
		});
	};
    var getUserParamVal = function(paramdivnum, col_type, code, tmpobj, paramNodes){
        var ret = true;
        $("#urltpl_"+paramdivnum+"_"+code+"_"+col_type+"").children("div[name=paramdiv]").each(function(i, item){
            var code = $(item).attr("code");
            var col_type = $(item).attr("col_type");
            var num = $(item).attr("paramdivnum");
            switch(parseInt(col_type, 10)){
                case SE_TYPE_STRING:
                    var id = "urltpl_"+num+"_"+code+"_"+col_type+"";
                    var code = $("#"+id+"").attr("code");
                    tmpobj[code] = $("#"+id+"").val();
                    var tmpnodes = {};
                    tmpnodes.name = $("#"+id+"").val();
                    paramNodes.push(tmpnodes);
                    break;
                case SE_TYPE_INT32:
                    var id = "urltpl_"+num+"_"+code+"_"+col_type+"";
                    var code = $("#"+id+"").attr("code");
                    var tmpval = parseInt($("#"+id+"").val(), 10);
                    if(isNaN(tmpval)){
                        //alert('请输入数字!');
                        $("#"+id+"").val('请输入数字!');
                        ret = false;
                        return false;
                    }
                    tmpobj[code] = tmpval;
                    var tmpnodes = {};
                    tmpnodes.name = tmpval;
                    paramNodes.push(tmpnodes);
                    break;
                case SE_TYPE_TIMESTAMP:
                    var id = "urltpl_"+num+"_"+code+"_"+col_type+"";
                    var code = $("#"+id+"").attr("code");
                    tmpobj[code] = getTimeSec($("#"+id+"").val());
                    var tmpnodes = {};
                    tmpnodes.name = $("#"+id+"").val();
                    paramNodes.push(tmpnodes);
                    break;
                case SE_TYPE_ARRAY:
                    var id = "urltpl_"+num+"_"+code+"_"+col_type+"";
                    var code = $("#"+id+"").attr("code");
                    tmpobj[code] = [];
                    var cid = $($("#"+id+"").children().get(2)).children('span').attr("id");
                    $("#"+cid+"").find(".useritem").each(function(i, item){
                        var itemobj = jQuery.parseJSON($(item).attr("code"));
                        tmpobj[code].push(itemobj);
                    });
                    var tmpnodes = {};
                    tmpnodes.name = tmpobj[code].join(',');
                    paramNodes.push(tmpnodes);
                    break;
                case SE_TYPE_OBJECT:
                    var id = "urltpl_"+num+"_"+code+"_"+col_type+"";
                    tmpobj[code] = {};
                    var tmpnodes = {};
                    tmpnodes.name = '父级';
                    tmpnodes.children = [];
                    paramNodes.push(tmpnodes);
                    getUserParamVal(num, col_type, code, tmpobj[code], paramNodes[paramNodes.length-1].children);
                    break;
            }
        });
        return ret;
    };
	initParamGenerator();
}
function myURLConfigure(urlconfigrule, successResult, selectedVal, showName, choiceParam){
	//var datafromfield = ""; //存来自父任务out字段的下拉菜单html
	var itemObjToHtml = function(item, itemObj){
		var rethtml = "";
		rethtml +="<div class='search' style='vertical-align: middle;'><span style='text-align:right;width:70px;display:inline-block;'>"+itemObj.label+"：</span>";
		switch(itemObj.datatype){
			case "string":
			case "int":
				if(itemObj.multivalue){
					rethtml += "<textarea id='urltpl"+item+"' name='urltpl"+item+"' rows='15' cols='20'></textarea>";
				}
				else{
					rethtml += "<input id='urltpl"+item+"' name='urltpl"+item+"' />";
				}
				break;
			case "object":
				if(itemObj.multivalue){
					rethtml +="<span id='urltpl"+item+"_addbtn' name='urltpl_field_addbtn' objfield='"+item+"' style='float:right;'><a href='javascript:void(0);'>添加多组</a></span>";
				}
				//对象数组容器
				rethtml +="<div id='urltpl"+item+"_div' name='urltpl"+item+"_div' groupcount='1'>"
				var cObj = itemObj.child;
				rethtml += "<div name='urltpl"+item+"_childdiv' groupnum='1' style='border:1px solid red;'>";
				if(itemObj.multivalue){
					rethtml += "<span name='urltpl_field_deletebtn' style='float:right;'><a href='javascript:void(0);'>删除</a></span>"
				}
				for(var citem in cObj){
					rethtml += itemObjToHtml(citem, cObj[citem]);
				}
				rethtml += "</div>";
				rethtml +="</div>";
				break;
			default:
				break;
		}
		if(itemObj.datatype != "object"){
			rethtml += "<span id='urltpl_"+item+"_datafrom' name='urltpl_field_dataform' tplfield='"+item+"' style='display:none;' ></span>";
			rethtml += "<a href='javascript:void(0);' name='urltpl_datafrom_btn' tplfield='"+item+"' datafrom='manully'>来自参数</a>";
		}
		rethtml +="</div>";
		if(itemObj.multivalue && itemObj.split){
			rethtml +="<div class='search'><span style='text-align:right;width:70px;display:inline-block;'>是否拆分：</span>";
			rethtml +="<input type='radio' name='urltpl_field_split' tgtfield='"+item+"' id='urltpl"+item+"_split1' value='1' checked='checked'><label for='urltpl"+item+"_split1'>是</label>";
			rethtml +="<input type='radio' name='urltpl_field_split' tgtfield='"+item+"' id='urltpl"+item+"_split0' value='0'><label for='urltpl"+item+"_split0'>否</label>";
			rethtml +="</div>";
			rethtml +="<div class='search'><span style='text-align:right;width:70px;display:inline-block;'>拆分步长：</span>";
			rethtml +="<input type='text' name='urltpl"+item+"_split_count' id='urltpl"+item+"_split_count' value='1'>";
			rethtml +="</div>";
		}
		return rethtml;
	};
	var addObjFieldItem = function(fielditem){
		var groupcount = $("#urltpl"+fielditem+"_div").attr("groupcount");
		var nextnum = parseInt(groupcount, 10) + 1;
		var tmphtml = "<div name='urltpl"+fielditem+"_childdiv' groupnum='"+nextnum+"' style='border:1px solid red;'><span name='urltpl_field_deletebtn' style='float:right;'><a href='javascript:void(0);'>删除</a></span>";
		for(var item in urlconfigrule[fielditem].child){
			tmphtml += itemObjToHtml(item, urlconfigrule[fielditem].child[item]);
		}
		tmphtml += "</div>";
		$("#urltpl"+fielditem+"_div").append(tmphtml);
		$("#urltpl"+fielditem+"_div").attr("groupcount", nextnum);
	};
	/*
	 * df当前数据来源状态
	 * tf字段名
	 * */
	var dataFromChange = function(df, tf){
		if(df == "manully"){
            if(choiceParam){
                myParamPath.init(choiceParam, function(data){
                    $("span[name=urltpl_field_dataform][tplfield="+tf+"]").attr("parampath",JSON.stringify(data));
                    $("span[name=urltpl_field_dataform][tplfield="+tf+"]").text(data.paramPathId);
                });
            }
            else{
                alert('请先进行参数定义');
            }
			$("span[name=urltpl_field_dataform][tplfield="+tf+"]").show();
			$("input[name=urltpl"+tf+"]").hide();
			$("textarea[name=urltpl"+tf+"]").hide();
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").attr("datafrom", "parentfield");
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").text("手动添加");
		}
		else if(df == "parentfield"){
			$("span[name=urltpl_field_dataform][tplfield="+tf+"]").hide();
			$("input[name=urltpl"+tf+"]").show();
			$("textarea[name=urltpl"+tf+"]").show();
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").attr("datafrom", "manully");
			$("a[name=urltpl_datafrom_btn][tplfield="+tf+"]").text("来自参数");
		}
	};
	var tmp = [];
	var fieldtmp = [];
	var firstItem = function(urlconfigrule){
		for(var item in urlconfigrule){
			if(item != "URLexpression"){
				return item;
			}
		}
	};
	var fieldValueToURL = function(result, urlconfigrule){
		var count = 0;
		for(var item in urlconfigrule){
			if(typeof(urlconfigrule[item]) == 'object'){
				if(!urlconfigrule[item].cycled){
					urlconfigrule[item].cycled = true;
					var datafrom = urlconfigrule[item].datafrom;
					if(urlconfigrule[item].value){
						$.each(urlconfigrule[item].value, function(ui, uitem){
							var tmpu;
							var fieldType = "Enum"; 
							if(isArray(uitem)){
								var tmparr = [];
								$.each(uitem, function(ui, uobj){
									if(typeof(uobj) == 'object'){
										fieldType = "Obj";
										tmparr.push(JSON.stringify(uobj));
									}
									else{
										tmparr.push(uobj);
									}
								});
								tmpu = tmparr.join(",").replace(/{/g, '(').replace(/}/g, ')').replace(/"/g, '');
							}
							else if(typeof(uitem) == 'object'){
								tmpu = JSON.stringify(uitem);
							}
							else{
								tmpu = uitem;
							}
						if(!fieldtmp.inArray(item)){
							var etmp = ""; 
							if(datafrom == "manully"){
								etmp = ""+item+":"+fieldType+"("+tmpu+")"; 
							}
							else if(datafrom == "parentfield"){
								etmp = ""+item+":"+fieldType+"(|"+tmpu+"|)"; 
							}
							tmp.push(etmp);
							fieldtmp.push(item);
						}
						else{
							var i = 0;
							$.each(fieldtmp, function(fi, fitem){
								if(fitem == item){
									i = fi;
									return false;
								}
							});
							if(datafrom == "manully"){
								tmp[i] = ""+item+":"+fieldType+"("+tmpu+")";
							}
							else if(datafrom == "parentfield"){
								tmp[i] = ""+item+":"+fieldType+"(|"+tmpu+"|)";
							}
							if(item == firstItem(urlconfigrule)){
								for(var tmpitem in urlconfigrule){
									if(tmpitem != firstItem(urlconfigrule) && urlconfigrule[tmpitem].cycled && urlconfigrule[tmpitem].value.length > 1){
										urlconfigrule[tmpitem].cycled = false;
									}
								}
							}
						}
						fieldValueToURL(result, urlconfigrule);
						var lurl = "taskurl$:\""+urlconfigrule["taskurl"]+"\"";
						if(urlconfigrule["mainurl"] != undefined){
							lurl += "mainurl$:\""+urlconfigrule["mainurl"]+"\"";
						}
						lurl += "{"+tmp.join(" ")+"}";
						if(!result.inArray(lurl)){
							result.push(lurl);
						}
						});
					}
				}
			}
		}
	};
	var initURLGenerator = function(){
		if(showName == undefined){
			showName = "";
		}
		$("#urltplbox").remove();
		var urltplhtml = "";
		urltplhtml +="<fieldset>";
		urltplhtml +="<legend>"+showName+"</legend>";
		urltplhtml +="<div>";
		for(var item in urlconfigrule){
			if(typeof(urlconfigrule[item]) == 'object'){//排除了taskurl和mainurl两个字段
				var itemObj = urlconfigrule[item];
				urltplhtml += itemObjToHtml(item, itemObj);
			}
		}
		urltplhtml +="</div>";
		urltplhtml +="</fieldset>";
		$("<div id='urltplbox' title='"+showName+"列表'></div>").insertAfter("body");
		$("#urltplbox").append(urltplhtml);
        /*
		//数据来自于父级任务时, 列出父级任务out对应的字段值
		if(config.allTplOutfield == undefined || config.allTplOutfield.length == 0){
			getTplOutfield(function(data){
				if(data){
					config.allTplOutfield = data;
				}
			});
		}
		var opthtml = "";
		$.each(config.allTplOutfield, function(di, ditem){
			opthtml +='<option value="'+ditem.code+'">'+ditem.name+'</option>';
		});
		datafromfield = opthtml;
        */
		//当字段为objcet类型时,可以添加多组值
		$("span[name=urltpl_field_addbtn]").unbind("click");
		$("span[name=urltpl_field_addbtn]").bind("click", function(){
			var fielditem = $(this).attr("objfield");
			addObjFieldItem(fielditem);
		});
		//删除添加的组
		$("span[name=urltpl_field_deletebtn]").die("click");
		$("span[name=urltpl_field_deletebtn]").live("click", function(){
			$(this).parent("div[groupnum]").remove();
		});
		$("input[name=urltpl_field_split]").unbind("click");
		$("input[name=urltpl_field_split]").bind("click", function(){
			var tgtfield = $(this).attr("tgtfield");
			var split = $("input[name=urltpl_field_split][tgtfield="+tgtfield+"]:checked").val();
			if(parseInt(split, 10)){
				$("#urltpl"+tgtfield+"_split_count").parent("div").show();
			}
			else{
				$("#urltpl"+tgtfield+"_split_count").parent("div").hide();
			}
		});
		//选择来自父任务
		$("a[name=urltpl_datafrom_btn]").die("click");
		$("a[name=urltpl_datafrom_btn]").live("click", function(){
			var df = $(this).attr("datafrom"); //数据来自
			var tf = $(this).attr("tplfield"); //字段
			dataFromChange(df, tf);
		});
		if(selectedVal){
			for(var item in selectedVal){
				if(typeof(selectedVal[item]) == 'object'){
					var itemObj = selectedVal[item];
					var datatype = itemObj.datatype;
					switch(datatype){
						case "string":
						case "int":
							if(itemObj.datafrom == "parentfield"){
								$("#urltpl_"+item+"_datafrom").val(itemObj.value[0]);
								dataFromChange("manully", item);
							}
							else{
								dataFromChange("parentfield", item);
								var vlen = 0;
								if(itemObj.value != undefined){
									vlen = itemObj.value.length;
								}
								if(vlen > 0){
									if(itemObj.multivalue){ //多值
										$("input[name=urltpl_field_split][tgtfield="+item+"][value=1]").attr("checked", true);
										var tmpfirst = itemObj.value[0].split(",");
										$("#urltpl"+item+"_split_count").val(tmpfirst.length);
										var valhtml = [];
										$.each(itemObj.value, function(vi, vitem){
											$.each(vitem.split(","), function(vvi, vvitem){
												valhtml.push(decodeURI(vvitem));;
											});
										});
										$("#urltpl"+item+"").val(valhtml.join("\n"));
									}
									else{ //单值
										$("#urltpl"+item+"").val(itemObj.value[0]);
										$("input[name=urltpl_field_split][tgtfield="+item+"][value=0]").attr("checked", false);
									}
								}
							}
							break;
						case "object":
							var groupcount = itemObj.value[0].length;
							for(var i=1;i<groupcount;i++){
								addObjFieldItem(item);
							}
							//为object 字段赋值
							$.each(itemObj.value, function(vi, vitem){
								if(isArray(vitem)){
									$.each(vitem, function(ti, titem){
										var gn = ti+1;
										for(var field in titem){
											if(itemObj.child[field].datafrom != undefined && itemObj.child[field].datafrom == "manully"){
												dataFromChange("parentfield", field);
												$("div[name=urltpl"+item+"_childdiv][groupnum="+gn+"] input[name=urltpl"+field+"]").val(titem[field]);
											}
											else{
												dataFromChange("manully", field);
												$("span[name=urltpl_field_dataform][tplfield="+field+"]").val(titem[field].replace(/\|/g, ""));
											}
										}
									});
								}
								else{
									for(var field in vitem){
										$("div[name=urltpl"+item+"_childdiv][groupnum="+gn+"] input[name=urltpl"+field+"]").val(vitem[field]);
									}
								}
							});
							break;
						default:
							break;
					}
				}
			}
		}
		$("#urltplbox").dialog({
			autoOpen: true,
			modal:true,
			width:550,
			buttons:{
				"确定":function(){
					for(var item in urlconfigrule){
						var itemObj = urlconfigrule[item];
						itemObj.cycled = false;
						var datatype = itemObj.datatype;
						switch(datatype){
							case "string":
							case "int":
								var kwvalarr = [];
								var datafrom = $("a[name=urltpl_datafrom_btn][tplfield="+item+"]").attr("datafrom");
								if(datafrom == "manully"){
									var kwval = $("#urltpl"+item+"").val();
									if(kwval){
										if(itemObj.multivalue){ //多值时处理
											var karr = kwval.split("\n");
											var split = $("input[name=urltpl_field_split][tgtfield="+item+"]:checked").val();
											var splitcount = 1;
											if(parseInt(split,10)){//拆分
												splitcount = parseInt($("#urltpl"+item+"_split_count").val(), 10);
											}
											else{ //不拆分
												splitcount = karr.length;
											}
											do{
												var tmp = karr.splice(0, splitcount);
												if(tmp.length > 0 && tmp[0]){
													tmp = encodeURI(tmp);
													kwvalarr.push(tmp);
												}
											}while(karr.length > 0);
										}
										else{ //单值处理
											var tmp = kwval;
											tmp = encodeURI(tmp);
											kwvalarr.push(tmp);
										}
									}
									/*
									else{
										var tmp = urlconfigrule[item].value;
										if(tmp){
											tmp = encodeURI(tmp);
											kwvalarr.push(urlconfigrule[item].value);
										}
									}
									*/
									if(kwvalarr.length > 0){
										urlconfigrule[item].value = kwvalarr;
										urlconfigrule[item].datafrom = "manully";
									}
								}
								else if(datafrom == "parentfield"){
									var pfieldStr = $("#urltpl_"+item+"_datafrom").attr("parampath");
                                    var pfieldObj = pfieldStr ? jQuery.parseJSON(pfieldStr) : null; 
									kwvalarr.push(pfieldObj.paramPathId);
									urlconfigrule[item].value = kwvalarr;
									urlconfigrule[item].datafrom = "parentfield";
									urlconfigrule[item].parampath = pfieldObj.paramPath;
								}
								break;
							case "object":
								var kwvalarr = [];
								var grouparr = [];
								$("div[name=urltpl"+item+"_childdiv]").each(function(di, ditem){
									var tmpobj = {};
									for(var citem in itemObj.child){
										var datafrom = $("a[name=urltpl_datafrom_btn][tplfield="+citem+"]").attr("datafrom");
										itemObj.child[citem].datafrom = datafrom;
										var retval = encodeURI($(ditem).find("input[name=urltpl"+citem+"]").val()); 
										if(datafrom == "parentfield"){
                                            var pfieldStr = $("#urltpl_"+item+"_datafrom").attr("parampath");
                                            var pfieldObj = pfieldStr ? jQuery.parseJSON(pfieldStr) : null; 
											retval = "|"+pfieldObj.paramPathId+"|";
										}
										tmpobj[citem] = retval;
									}
									grouparr.push(tmpobj);
								});
								if(grouparr.length > 0){
									if(itemObj.multivalue){//多值
										var split = $("input[name=urltpl_field_split][tgtfield="+item+"]:checked").val();
										var splitcount = 1;
										if(parseInt(split, 10)){ //拆分
											splitcount = parseInt($("#urltpl"+item+"_split_count").val(), 10);
										}
										else{ //不拆分
											splitcount = grouparr.length;
										}
										do{
											var tmp = grouparr.splice(0, splitcount);
											//kwvalarr.push(JSON.stringify(tmp));
											kwvalarr.push(tmp);
										}while(grouparr.length > 0);
									}
									else{ //单组
										kwvalarr.push(grouparr);
									}
								}
								urlconfigrule[item].value = kwvalarr;
								urlconfigrule[item].datafrom = "manully";
								break;
							default:
								break;
						}
					}
					var urlarr = [];
					tmp = [];
					fieldtmp = []; 
					fieldValueToURL(urlarr, urlconfigrule); 
					var retobj = {};
					retobj.urls = urlarr;
					retobj.urlconfigrule = urlconfigrule;
					successResult(retobj);
					$("#urltplbox").dialog("close");
				},
				"取消":function(){
					$("#urltplbox").dialog("close");
				}
			},
			close:function(){
			}
		});
	};
	initURLGenerator();
}
