function UserConfigPartAccountConfig(datajson,target){
	var _this = this;
	this.targetID = target;
	this.dataJson = datajson;
	this.init = function(){
		var paramsjson = _this.dataJson;
		//认证字段点击事件
        // 初始化地区选择控件
		$.each(["areauser", "verified", "users_source_host"], function(k, item){
			$("#set"+item+"").bind('click', function(){
				var isemo = false;
				var fieldtxt = "";
				if(item == "verified"){
					fieldtxt = "认证";
					var pname = 1;
				}
				else if(item == "users_source_host"){
					fieldtxt = "来源";
				}
				else{
					fieldtxt = "地区";
				}

				var sltVal = [];
				$("#selected"+item+" ."+item+"item").each(function(i, m){
					var tempObj = {};
					tempObj["name"] = $(this).text();
					tempObj["code"] = $(this).attr("code");;
					if($(this).attr("emotype") != undefined){
						tempObj["emotype"] = $(this).attr("emotype");
					}
					var ex = $(this).attr("exclude");
					if(ex != undefined){
						tempObj["exclude"] = ex;
					}
					sltVal.push(tempObj);
				});
				if(item == "verified"){
					choiceVal = "verifiedUrl";
				}
				else if(item == "users_source_host"){
					choiceVal = "sourceidUrl";
				}
				else{
					choiceVal = "countryUrl";
				}
			myCommonSelect(choiceVal, function(data) {
				if(data.length>0){
					var sareas = "";
					var sareacodes = "";
					var len = data.length;
					$.each(data, function(i, m){
						var comma = ",";
						if(i == len-1){
							comma = "";
						}
						sareacodes += m.code+comma;
						sareas += "<span class='selwordsbox'><span code="+m.code+" class='"+item+"item'>"+m.name+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";		
					});
					$("#selected"+item).empty().append(sareas);
					$("#islimit_"+item+"code").val(sareacodes);
				}
				else{
					$("#selected"+item).empty();
					$("#islimit_"+item+"code").val("");
				}
			}, sltVal, fieldtxt, isemo, undefined, false, pname, undefined, item);

			});
		});
		/*
		$("#setareauser").bind('click',function(){
			var selareas = [];
			var seltarget = "selectedareauser";
			$("#"+seltarget+" span").each(function(i, m){
					var tempObj = {};
					tempObj["name"] = $(this).text();
					tempObj["code"] = $(this).attr("code");;
					selareas.push(tempObj);
			});
	    	myAreaSelect(function(data){
				if(data.length>0){
					var sareas = "";
					var sareacodes = "";
					var len = data.length;
					$.each(data, function(i, m){
						var comma = ",";
						if(i == len-1){
							comma = "";
						}
						sareacodes += m.code+comma;
						sareas += "<span code="+m.code+" style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;'>"+m.name+"</span><a onclick='UserConfigPartAccountConfig.prototype.cancleArea(this)' style='border: 1px solid #cccccc;padding:1.5px; color: red; cursor: pointer;'>×</a>";		
					});
					$("#"+seltarget).empty().append(sareas);
					$("#islimit_areausercode").val(sareacodes);
				}
				else{
					$("#"+seltarget).empty();
					$("#islimit_areausercode").val("");
				}
	    	}, selareas);
	    });
		*/
		$("#clearfacetlimit").bind("click", function(){
			$("#selectedfacetlimit").empty();
		});
		$("#setfacetlimit").bind("click", function(){
			var latestjson;
			$.ajax({
				url: config.modelUrl+"resource_model.php?type=getlatestjson&modelid="+paramsjson.modelid,
				dataType: "json",
				cache: false, //默认为true， false时不会从浏览器缓存中加载请求信息
				async:false,  //同步请求
				success:function(data){
					if(data){
						if(data.data){
							latestjson = data.data.datajson;
						}
					}    
				} 
			});
			if(latestjson.facet.limit && latestjson.facet.limit.length>0){
				var choiceFacet = [];
				$.each(latestjson.facet.limit, function(key, item){
					var tempObj = {};
					var labelname = getDisplayName(item.value);
					tempObj["name"] = labelname; 
					tempObj["code"] = item.value; 
					choiceFacet.push(tempObj);
				});
			}
				var item = "facetlimit";
				var isemo = false;
				var fieldtxt = "统计字段";
				var sltVal = [];
				$("#selected"+item+" ."+item+"item").each(function(i, m){
					var tempObj = {};
					tempObj["name"] = $(this).text();
					tempObj["code"] = $(this).attr("code");
					if($(this).attr("emotype") != undefined){
						tempObj["emotype"] = $(this).attr("emotype");
					}
					sltVal.push(tempObj);
				});
				myCommonSelect(choiceFacet, function(data) {
					$("#selected"+item+"").empty();
					if (data != "") {
						sltVal = data; // 默认选择的值
						var sareas = "";
						var len = data.length;
						$.each(data, function(i, m) {
							var emocode = "";
							if(m.emotype != undefined){
								emocode = "emotype='"+m.emotype+"'";
							}
							sareas += "<span class='fixedselwordsbox'><span code=" + m.code + " "+emocode+" class='"+item+"item'>" + m.name + "</span><a name='cancleselectedvaluetextobj' value='"+item+"' class='cancleitem'>×</a></span>";
						});
						$("#selected"+item+"").append(sareas);
					}
				}, sltVal, fieldtxt, isemo, undefined, false, undefined,undefined,undefined, true);
		});
		//用户名
		$("#setuserid").bind("click", function(){
			var sltVal = [];
			$("#selecteduserid .useriditem").each(function(i, m){
				var tempObj = {};
				tempObj["name"] = $(this).text();
				tempObj["code"] = $(this).attr("code");
				sltVal.push(tempObj);
			});

			myAccountSelect(function(data){
				var useridhtml = "";
				var useridcode = "";
				if(data.length>0){
					var len = data.length;
					$.each(data,function(i,m){
						var username = m.name;
						var userid = m.code;
						var comma = ",";
						if (i == len - 1) {
							comma = "";
						}
						useridcode += userid + comma;
						useridhtml += "<span class='selwordsbox'><span code="+userid+" class='useriditem'>"+username+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";
					});
					$("#islimit_useridcode").val(useridcode);
					$("#selecteduserid").empty().append(useridhtml);
				}
				else{
					$("#islimit_useridcode").val("");
					$("#selecteduserid").empty();
				}
			}, sltVal);
		});
		//作者昵称,查粉丝,查关注
		$("input[name=commendName]").bind("click", function(){
			var target = $(this).attr("target");
			var txttarget = "#txt"+target;
			var username = $(txttarget).val();
			myUserRecommend(username, function(data){
				var userArr = [];
				$.each(data, function(di, ditem){
					userArr.push(ditem.name);
				});
				limitConfigAdd(target, userArr);
				$(txttarget).val("");
			}, false);

		});
		//认证原因, 简介, 博客地址, 个性域名.. 推荐
		$("input[name=commendWord]").bind("click", function(){
			var target = $(this).attr("target");
			var fieldname = "";
			switch(target){
				case "verifiedreason":
					fieldname = "users_verified_reason";
					break;
				case "description":
					fieldname = "users_description";
					break;
				default:
					fieldname = target;
					break;
			}
			var txttarget = "#txt"+target;
			var searchword = $(txttarget).val();
			if (searchword != "") {
				myKeywordRecommend(searchword, function(data) {
					var resval = [];
					$.each(data, function(i, m){
						if(m.name != ""){
							resval.push(m.name);
						}
					});
					limitConfigAdd(target, resval);
					$(txttarget).val("");
				}, fieldname);
			}
		});
		$("input[type=button][name=btnaddlimit]").bind("click", function(){
			var target = $(this).attr("target");
			var txttarget = "#txt"+target;
			var username = commonFun.trim($(txttarget).val());
			if(username!=undefined && username!=""){
				var userArr = username.split(" "); 
				limitConfigAdd(target, userArr);
				$(txttarget).val("");
			}
		});
		$("input:checkbox[id$=_nolimit]").bind("click",function(){
			var flname = $(this).attr('id').split('_');
			flname = flname[0];
			if($(this).prop("checked")){
				$("#maxlimitlength_"+flname).attr("disabled","disabled");
			}
			else{
				$("#maxlimitlength_"+flname).removeAttr("disabled");
			}
		});
		$("input:checkbox[id^=nolimitcontrol_]").bind("click",function(){
			var flname = $(this).attr('id').split('_');
			flname = flname[1];
			if($(this).prop("checked")){
				$("#limitcontrol_"+flname).attr("disabled","disabled");
			}
			else{
				$("#limitcontrol_"+flname).removeAttr("disabled");
			}
		});

		for(var item in paramsjson.filter){		
			//修改次数赋值
			//$("input[name=edit"+item+"]").val(paramsjson.filter[item].allowcontrol);
			//绑定控件的详细设置
			$("#maxlimitlength_"+item).val(paramsjson.filter[item].maxlimitlength);
			var nolimitchk = paramsjson.filter[item].maxlimitlength == -1;
			$("#"+item+"_nolimit").attr("checked",nolimitchk);
			if(nolimitchk){
				$("#maxlimitlength_"+item).attr("disabled","disabled");
			}
			else{
				$("#maxlimitlength_"+item).removeAttr("disabled");
			}
			$("#limitcontrol_"+item).val(paramsjson.filter[item].limitcontrol);
			var nolimitcontrol = paramsjson.filter[item].limitcontrol == -1;
			$("#nolimitcontrol_"+item).attr("checked",nolimitcontrol);
			if(nolimitcontrol){
				$("#limitcontrol_"+item).attr("disabled","disabled");
			}
			else{
				$("#limitcontrol_"+item).removeAttr("disabled");
			}
			var acchk = parseInt(paramsjson.filter[item].allowcontrol,10);
			addAllowControlElement($("label[for=allowcontrol_"+item+"]"), "allowcontrol_"+item, acchk);
			var target="";
			switch(item)
			{
				case "username":
				case "usersfollower":
				case "usersfriend":
				case "verifiedreason":
				case "description":
				case "users_url":
				case "users_page_url":
				case "users_domain":
					var strhtml ="";
					target = "#end"+item;
					if(paramsjson.filter[item].limit.length>0) {
						strhtml="";
						for(var i=0;i<paramsjson.filter[item].limit.length;i++)
						{
							var repeathtml="";
							if(paramsjson.filter[item].limit[i].repeat == -1){
								repeathtml = "个数不限";
							}
							else{
								repeathtml = paramsjson.filter[item].limit[i].repeat+"个";
							}
							strhtml += "<span class='selwordsbox'><span limit_type='"+paramsjson.filter[item].limit[i].type+"' limit_repeat='"+paramsjson.filter[item].limit[i].repeat+"' class='useritem'>"+paramsjson.filter[item].limit[i].value+"</span>("+repeathtml+")<a class='useritem_a' name='cancleselectedvaluetextobj'>×</a></span>";
						}
						$(target).append(strhtml);
					}
					/*$("#"+item+"_nolimit").bind("click",function(){
						if($(this).prop("checked")){
							$("#maxlimitlength_"+item).attr("disabled","disabled");
						}
						else{
							$("#maxlimitlength_"+item).removeAttr("disabled");
						}
						if($(this).prop("checked")){
							$(target).empty();
							$("#btnaddusername").attr("disabled",true);
							$("#maxlimitlength_"+item).val("-1");//不限制，同时将值个数-1
						}
						else{
							$("#btnaddusername").attr("disabled",false);
						}
					});*/
					break;
				case "areauser":
				case "userid":
					var strhtml="";
					//var strcode="";
					//初始化限制的值
					//$("#islimit_areausercode").val(paramsjson.filter[item].limit);
					if(paramsjson.filter[item].limit.length>0) {
						$.each(paramsjson.filter[item].limit, function(li, litem){
							strhtml+="<span class='selwordsbox'><span code='"+litem.value.value+"' class='"+item+"item'>"+litem.value.text+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span";	
						});
						/*
						var len = paramsjson.filter[item].limit.length;
						for(var i=0;i<len;i++){
							var comma = ",";
							if(i == len-1){
								comma = "";
							}
							strcode += paramsjson.filter[item].limit[i].value.value+comma;
							strhtml+="<span code=\""+paramsjson.filter[item].limit[i].value.value+"\">"+paramsjson.filter[item].limit[i].value.text+"</span><a onclick='UserConfigPartAccountConfig.prototype.cancleArea(this)' style='border: 1px solid rgb(255, 255, 255); color: red; cursor: pointer;'>×</a>";	
						}
						$("#islimit_"+item+"code").val(strcode);
						*/
						$("#selected"+item+"").html(strhtml);
					}
					/*$("#"+item+"_nolimit").bind("click",function(){
						if($(this).prop("checked")){
							$("#selectedarea").empty();
							$("#setarea").attr("disabled",true);
							$("#maxlimitlength_"+item).val("-1");//不限制，同时将值个数-1
						}
						else{
							$("#setarea").attr("disabled",false);
						}
					});*/
					break;
				case "verified":
				case "verified_type":
				case "sourceid":
				case "users_source_host":
					var htmlitem = item;
					if(item == "verified_type"){
						htmlitem = "verified";
					}
					if(item == "sourceid"){
						htmlitem = "users_source_host";
					}
					if(paramsjson.filter[item].limit.length > 0){
						var strhtml = "";
						//$("#selectedverified").empty();
						$.each(paramsjson.filter[item].limit, function(li, litem){
							var ltext = "";
							var lvalue = "";
							if(item == "verified"){ //认证类型和认证 使用同一个控件
								lvalue = "verify_"+litem.value;
								ltext = getVerifiedName(lvalue);
							}
							else if(item == "verified_type"){
								lvalue = litem.value;
								ltext = getVerifiedTypeName(lvalue);
							}
							else if(item == "sourceid"){
								lvalue = getSourceUrl(litem.value);
								ltext = getSourceName(litem.value);
							}
							else if(item == "users_source_host"){
								lvalue = litem.value;
								ltext = getSourceHostName(litem.value);
							}
							strhtml+="<span class='selwordsbox'><span code='"+lvalue+"' class='"+htmlitem+"item'>"+ltext+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";	
						});
						if(htmlitem == "verified"){
							$("#selected"+htmlitem+"").append(strhtml);
						}
						else if(htmlitem == "users_source_host"){
							$("#selected"+htmlitem+"").html(strhtml);
						}
					}
					break;
				case "sex":
				case "users_allow_all_act_msg":
				case "users_allow_all_comment":
					if(paramsjson.filter[item].limit.length > 0){
						$.each(paramsjson.filter[item].limit,function(i,v){
							$('#islimit_'+item+v.value).attr("checked",true);
						});
					}
					break;
					/*
				case "sourceid":
					var sourcelist = [];
					$.ajax({url:config.getSourceUrl,type:"GET",dataType:"json",data:{sourceid:""},
						async:false,
						success:function(rd){
							if(rd && rd.length > 0 && rd[0].datalist){
								sourcelist = rd[0].datalist;
							}
						}
					});
					$("#span_limit_sourceid").empty();
					$.each(sourcelist,function(i,v){
						$("#span_limit_sourceid").append('<input type="checkbox" name="islimit_sourceid" value="'+v.id+'" />'+v.name);
					});
					
					if(paramsjson.filter[item].limit.length>0)
					{
						for(var i=0;i<paramsjson.filter[item].limit.length;i++)
						{
							$('input[type="checkbox"][name="islimit_sourceid"]').each( function(){
								if($(this).val()==paramsjson.filter[item].limit[i].value)
								{
									$(this).attr("checked",true);
								}
							});
						}
					}
					break;
					*/
				default:
					if(paramsjson.filter[item].datatype == "range" || paramsjson.filter[item].datatype == "gaprange"){
						if(paramsjson.filter[item].limit.length > 0){
							var minv = paramsjson.filter[item].limit[0].value.minvalue != null ? paramsjson.filter[item].limit[0].value.minvalue : "";
							var maxv = paramsjson.filter[item].limit[0].value.maxvalue != null ? paramsjson.filter[item].limit[0].value.maxvalue : "";
							$("#"+item+"_minvalue").attr("value",minv);
							$("#"+item+"_maxvalue").attr("value",maxv);
							if(paramsjson.filter[item].datatype == "gaprange"){
								var pvalue = paramsjson.filter[item].limit[0].value;
								if(pvalue.gap != undefined && pvalue.gap !=null){
									$("#"+item+"_gap").val(pvalue.gap);
								}
							}
						}
					}
					break;
			}
		}
		initDownloadLimit(paramsjson);//初始化下载权限
		initUpdateSnapshotLimit(paramsjson);//初始化定时快照更新权限
		initEventAlertLimit(paramsjson);//初始化事件预警权限
		//facet filterlimit
		var facethtml ="";
		if(paramsjson.facet.filterlimit &&　paramsjson.facet.filterlimit.limit.length > 0) {
			strhtml="";
			for(var i=0;i<paramsjson.facet.filterlimit.limit.length;i++)
			{
				var repeathtml = "";
				if(paramsjson.facet.filterlimit.limit[i].repeat == -1){
					repeathtml = "个数不限";
				}
				else{
					repeathtml = paramsjson.facet.filterlimit.limit[i].repeat+"个";
				}
				facethtml += "<span class='selwordsbox'><span limit_type='"+paramsjson.facet.filterlimit.limit[i].type+"' limit_repeat='"+paramsjson.facet.filterlimit.limit[i].repeat+"' class='useritem'>"+paramsjson.facet.filterlimit.limit[i].value+"</span>("+repeathtml+")<a class='useritem_a' name='cancleselectedvaluetextobj'>×</a></span>";
			}
			$("#endfacetfilterlimit").html(facethtml);
		}
		$("#maxlimitlength_facetfilterlimit").val(paramsjson.facet.filterlimit.maxlimitlength);
		var nolimitchk = paramsjson.facet.filterlimit.maxlimitlength == -1;
		$("#facetfilterlimit_nolimit").attr("checked",nolimitchk);
		$("#limitcontrol_facetfilterlimit").val(paramsjson.facet.filterlimit.limitcontrol);
		var nolimitcontrol = paramsjson.facet.filterlimit.limitcontrol == -1;
		$("#nolimitcontrol_facetfilterlimit").attr("checked",nolimitcontrol);
		var ffl = parseInt(paramsjson.facet.filterlimit.allowcontrol,10);
		addAllowControlElement($("label[for=allowcontrol_facetfilterlimit]"), "allowcontrol_facetfilterlimit", ffl);
		//facet limit
		if(paramsjson.facet.limit && paramsjson.facet.limit.length>0){
			var facetlimithtml = "";
			$.each(paramsjson.facet.limit, function(key, item){
				var labelname = getDisplayName(item.value);
				if(labelname != ""){
					facetlimithtml += "<span class='fixedselwordsbox'><span code=" + item.value + " class='facetlimititem'>" + labelname + "</span><a name='cancleselectedvaluetextobj' value='"+item.value+"' class='cancleitem'>×</a></span>";
				}
			});
			$("#selectedfacetlimit").empty().append(facetlimithtml);
		}
		$("#maxlimitlength_facetlimit").val(paramsjson.facet.maxlimitlength);
		var nolimitchk = paramsjson.facet.maxlimitlength == -1;
		if(nolimitchk){
			$("#maxlimitlength_facetlimit").attr("disabled","disabled");
		}
		else{
			$("#maxlimitlength_facetlimit").removeAttr("disabled");
		}
		$("#facetlimit_nolimit").attr("checked",nolimitchk);

		$("#limitcontrol_facetlimit").val(paramsjson.facet.limitcontrol);
		var nolimitcontrol = paramsjson.facet.limitcontrol == -1;
		if(nolimitcontrol){
			$("#limitcontrol_facetlimit").attr("disabled","disabled");
		}
		else{
			$("#limitcontrol_facetlimit").removeAttr("disabled");
		}

		$("#nolimitcontrol_facetlimit").attr("checked",nolimitcontrol);
		var ffl = parseInt(paramsjson.facet.allowcontrol,10);
		addAllowControlElement($("label[for=allowcontrol_facetlimit]"), "allowcontrol_facetlimit", ffl);

		//output
		if(paramsjson.output.countlimit && paramsjson.output.countlimit.limit.length > 0){
			var minv = paramsjson.output.countlimit.limit[0].value.minvalue != null ? paramsjson.output.countlimit.limit[0].value.minvalue : "";
			var maxv = paramsjson.output.countlimit.limit[0].value.maxvalue != null ? paramsjson.output.countlimit.limit[0].value.maxvalue : "";
			$("#outputcount_minvalue").attr("value",minv);
			$("#outputcount_maxvalue").attr("value",maxv);
		}
		$("#maxlimitlength_outputcount").val(paramsjson.output.countlimit.maxlimitlength);
		var nolimitchk = paramsjson.output.countlimit.maxlimitlength == -1;
		$("#outputcount_nolimit").attr("checked",nolimitchk);
		$("#limitcontrol_outputcount").val(paramsjson.output.countlimit.limitcontrol);
		var nolimitcontrol = paramsjson.output.countlimit.limitcontrol == -1;
		$("#nolimitcontrol_outputcount").attr("checked",nolimitcontrol);
		var clv = parseInt(paramsjson.output.countlimit.allowcontrol,10);
		addAllowControlElement($("label[for=allowcontrol_outputcount]"), "allowcontrol_outputcount", clv);

		$("a[name=cancleselectedvaluetextobj]").die("click");
		$("a[name=cancleselectedvaluetextobj]").live("click", function(){
			$(this).parent().remove();
		});

	};

	// 获取表单参数
	this.getParams = function(){
		var formchk = true;
		$("input[type=textbox]:disabled").each(function(i,v){
			if($(v).val() == undefined || $(v).val() == ""){
				$(v).focus();
				formchk = false;
				return false;
				
			}
			var r = testPositiveInt($(v).val(),20);
			if(!r){
				$(v).focus();
				formchk = false;
				return false;
			}
		});
		if(!formchk){
			alert('字段值有误');
			return false;
		}
		var paramsjson = _this.dataJson;
		for(var item in paramsjson.filter)
		{
			var htmlitem = item;
			if(item == "verified_type"){//认证和认证类型页面使用同一个字段, 需要修改页面使用的字段名
				htmlitem = "verified";
			}
			if($("#limitcontrol_"+htmlitem).length == 0){
				paramsjson.filter[item].allowcontrol = -1;
				paramsjson.filter[item].maxlimitlength = -1;
				paramsjson.filter[item].limitcontrol = -1;
				continue;
			}
			paramsjson.filter[item].allowcontrol = parseInt($("#allowcontrol_"+htmlitem).val(),10);
			//获取值个数
			var maxlenlimit = $("#"+htmlitem+"_nolimit");
			if(maxlenlimit.length == 0){//值个数字段，没有"不限"按钮的，默认值为1，例如range类型的
				paramsjson.filter[item].maxlimitlength = -1;
			}
			else{
				if(maxlenlimit.prop("checked")){
					paramsjson.filter[item].maxlimitlength = -1;//选中"不限"则为-1
				}
				else{
					paramsjson.filter[item].maxlimitlength = parseInt($("#maxlimitlength_"+htmlitem).val(),10);
				}
			}
			//获取修改次数
			var limitcon = $("#nolimitcontrol_"+htmlitem);
			if(limitcon.prop("checked")){
				paramsjson.filter[item].limitcontrol = -1;
			}
			else{
				paramsjson.filter[item].limitcontrol = parseInt($("#limitcontrol_"+htmlitem).val(),10);
			}
			
			if(item != "verified" && item != "verified_type"){
				paramsjson.filter[item].limit=[];
			}
			var target;
			switch(item)
			{
				case "username":
				case "usersfollower":
				case "usersfriend":
				case "verifiedreason":
				case "description":
				case "users_url":
				case "users_page_url":
				case "users_domain":
					target = "#end"+item;
					$(target+" .useritem").each(function(){
						var lv = $(this).text();
						var lt = $(this).attr('limit_type');
						var lr = $(this).attr('limit_repeat');
						paramsjson.filter[item].limit.push({value:lv,type:lt,repeat:parseInt(lr,10)});
					});
					break;
				case "verified":
				//case "verified_type":
					paramsjson.filter["verified"].limit=[];
					paramsjson.filter["verified_type"].limit=[];
					$("#selectedverified .verifieditem").each(function(){
						var name = "";
						var lvalue;
						var vcode = $(this).attr("code");
						if(vcode.indexOf("verify_") > -1){
							name = "verified";
							var tmpcode = vcode.split("verify_");
							lvalue = tmpcode[1];
						}
						else{
							name = "verified_type";
							lvalue = vcode;
						}
						paramsjson.filter[name].limit.push({value:lvalue, type:'exact', repeat:1});
					});
					break;
				case "users_source_host":
					paramsjson.filter["users_source_host"].limit=[];
					paramsjson.filter["sourceid"].limit=[];
					$("#selectedusers_source_host .users_source_hostitem").each(function(){
						var lvalue = $(this).attr("code");
						paramsjson.filter[item].limit.push({value:lvalue, type:'exact', repeat:1});
					});
					break;
				case "areauser":
				case "userid":
					$("#selected"+item+" ."+item+"item").each(function(){
						var t_v = {value:$(this).attr("code"),text:$(this).text()};
						paramsjson.filter[item].limit.push( {value:t_v, type:'exact', repeat:1} );
					});
					/*
					if($("#islimit_"+item+"code").val()!=undefined && $("#islimit_"+item+"code").val()!="")
					{
						var strText="";
						var strValue="";

						var tmparr1 = $("#islimit_"+item+"code").val().split(",");
						$("#selected"+item+"").find("span").each(function(){
							    strText+=$(this).html()+",";
						    }
						);
						strText = strText.substring(0,strText.length-1);
						var tmparr2 = strText.split(",");
						$(tmparr1).each(function(i,v){
							var t_v = {value:v,text:tmparr2[i]};
							paramsjson.filter[item].limit.push( {value:t_v, type:'exact', repeat:1} );
						});
					}
					*/
					break;
				case "sex":
				case "users_allow_all_act_msg":
				case "users_allow_all_comment":
					$("input[name=islimit_"+item+"]:checked").each(function(){
						paramsjson.filter[item].limit.push( {value:$(this).val(), type:'exact', repeat:1} );
					});
					break;
					/*
				case "sourceid":
					$("input[name=islimit_"+item+"]:checked").each(function(){
						paramsjson.filter[item].limit.push( {value:parseInt($(this).val(),10), type:'exact', repeat:1} );
					});
					break;
					*/
				default:
					if(paramsjson.filter[item].datatype == "range" || paramsjson.filter[item].datatype == "gaprange"){
						var maxv = commonFun.trim($("#"+item+"_maxvalue").val() != undefined ? $("#"+item+"_maxvalue").val() : "");
						var minv = commonFun.trim($("#"+item+"_minvalue").val() != undefined ? $("#"+item+"_minvalue").val() : "");
						var l_v = {value:{maxvalue:null,minvalue:null}, type:'range', repeat:1};
						l_v.value.maxvalue = maxv != "" ? parseInt(maxv,10) : null;
						l_v.value.minvalue = minv != "" ? parseInt(minv,10) : null;
						if(paramsjson.filter[item].datatype == "gaprange"){
							l_v.type = "gaprange";
							var gap = $("#"+item+"_gap").val();
							l_v.value.gap = gap;
						}
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
			}
			if(!checkLimitlength(paramsjson.filter[item], paramsjson.filter[item].limit)){
				alert(paramsjson.filter[item].label+"值个数超出最大值");
				return false;
			}
		}
		var dlchk = getDownloadLimit(paramsjson);//获取下载相关参数
		if(!dlchk){
			return false;
		}
		var alertchk = getEventAlertLimit(paramsjson);//获取下载相关参数
		if(!alertchk){
			return false;
		}
		var snapshotchk = getUpdateSnapshotLimit(paramsjson);
		if(!snapshotchk){
			return false;
		}

		//facet
		//facetfilterlimit
		//获取值个数
		var item = "facetfilterlimit";
		paramsjson.facet.filterlimit.allowcontrol = parseInt($("#allowcontrol_"+item).val(),10);
		var maxlenlimit = $("#"+item+"_nolimit");
		if(maxlenlimit.length == 0){//值个数字段，没有"不限"按钮的，默认值为1，例如range类型的
			paramsjson.facet.filterlimit.maxlimitlength = -1;
		}
		else{
			if(maxlenlimit.prop("checked")){
				paramsjson.facet.filterlimit.maxlimitlength = -1;//选中"不限"则为-1
			}
			else{
				paramsjson.facet.filterlimit.maxlimitlength = parseInt($("#maxlimitlength_"+item).val(),10);
			}
		}
		//获取修改次数
		paramsjson.facet.maxlimitlength = -1;
		var limitcon = $("#nolimitcontrol_"+item);
		if(limitcon.prop("checked")){
			paramsjson.facet.filterlimit.limitcontrol = -1;
		}
		else{
			paramsjson.facet.filterlimit.limitcontrol = parseInt($("#limitcontrol_"+item).val(),10);
		}
		paramsjson.facet.filterlimit.limit = [];
		var target = "#end"+item;
		$(target+" .useritem").each(function(){
			var lv = $(this).text();
			var lt = $(this).attr('limit_type');
			var lr = $(this).attr('limit_repeat');
			paramsjson.facet.filterlimit.limit.push({value:lv,type:lt,repeat:parseInt(lr,10)});
		});
		if(!checkLimitlength(paramsjson.facet.filterlimit, paramsjson.facet.filterlimit.limit)){
			alert("输出过滤的值个数超出最大值");
			return false;
		}
		//facetlimit
		item = "facetlimit";
		var alowc = parseInt($("#allowcontrol_"+item).val(),10)
		paramsjson.facet.allowcontrol = alowc;
		var maxlenlimit = $("#"+item+"_nolimit");
		if(maxlenlimit.length == 0){//值个数字段，没有"不限"按钮的，默认值为1，例如range类型的
			paramsjson.facet.maxlimitlength = -1;
		}
		else{
			if(maxlenlimit.prop("checked")){
				paramsjson.facet.maxlimitlength = -1;//选中"不限"则为-1
			}
			else{
				paramsjson.facet.maxlimitlength = parseInt($("#maxlimitlength_"+item).val(),10);
			}
		}
		
		//获取修改次数
		limitcon = $("#nolimitcontrol_"+item);
		if(limitcon.prop("checked")){
			paramsjson.facet.limitcontrol = -1;
		}
		else{
			paramsjson.facet.limitcontrol = parseInt($("#limitcontrol_"+item).val(),10);
		}
		paramsjson.facet.limit = [];
		$("#selectedfacetlimit .facetlimititem").each(function(){
			var code = $(this).attr("code");
			paramsjson.facet.limit.push( {value:code, type:'exact', repeat:1} );
		});
		if(alowc == 0 || alowc == 1){
			if(paramsjson.facet.limit.length != 1){
				alert("禁止修改或禁止查看时,统计字段需选一个!");
				return false;
			}
		}

		//output
		var item = "outputcount";
		paramsjson.output.countlimit.allowcontrol = parseInt($("#allowcontrol_outputcount").val(),10);
		var maxlenlimit = $("#"+item+"_nolimit");
		if(maxlenlimit.length == 0){//值个数字段，没有"不限"按钮的，默认值为1，例如range类型的
			paramsjson.output.countlimit.maxlimitlength = -1;
		}
		else{
			if(maxlenlimit.prop("checked")){
				paramsjson.output.countlimit.maxlimitlength = -1;//选中"不限"则为-1
			}
			else{
				paramsjson.output.countlimit.maxlimitlength = parseInt($("#maxlimitlength_"+item).val(),10);
			}
		}
		//获取修改次数
		var limitcon = $("#nolimitcontrol_"+item);
		if(limitcon.prop("checked")){
			paramsjson.output.countlimit.limitcontrol = -1;
		}
		else{
			paramsjson.output.countlimit.limitcontrol = parseInt($("#limitcontrol_"+item).val(),10);
		}
		paramsjson.output.countlimit.limit = [];
		var maxv = commonFun.trim($("#"+item+"_maxvalue").val() != undefined ? $("#"+item+"_maxvalue").val() : "");
		var minv = commonFun.trim($("#"+item+"_minvalue").val() != undefined ? $("#"+item+"_minvalue").val() : "");
		var l_v = {value:{maxvalue:null,minvalue:null}, type:'range', repeat:1};
		l_v.value.maxvalue = maxv != "" ? parseInt(maxv,10) : null;
		l_v.value.minvalue = minv != "" ? parseInt(minv,10) : null;
		paramsjson.output.countlimit.limit.push(l_v);
		if(!checkLimitlength(paramsjson.output.countlimit, paramsjson.output.countlimit.limit)){
			alert("数据量限制值个数超出最大值");
			return false;
		}
		return paramsjson;
	};
	this.render = function(){
		// 请求HTML
		$.ajax({type: "GET", url: config.sitePath+"parts/user_config_part_accountconfig.html", async:false,  // 同步请求
				dataType: "html", 
				success: function(data){
					$(_this.targetID).html(data);
					_this.init();
			}});
	};
};
/**
 * 取消按钮事件，elm触发事件的控件，filter对应json中的filter的属性名称，opt操作类型（end表示取消的“限制”，default表示取消的“默认值”）
 */
UserConfigPartAccountConfig.prototype.cancleUser = function(elm){
	$(elm).parent().remove();
	/*if(opt == "end"){
		var len = $("#end"+filter+" .useritem").length;
		if(len == 0){
			$("#"+filter+"_nolimit").attr("checked",true);
		}
	}*/
};
UserConfigPartAccountConfig.prototype.cancleArea= function(elm){
	var parentid = $(elm).parent().attr("id"); //先获取父ID再删除本身
	$(elm).prev().remove();
	$(elm).remove();

	var strValue="";
	$("#"+parentid).find("span").each(function(){
		strValue+=$(this).attr("code")+",";
	});
	strValue = strValue.substring(0,strValue.length-1);
	$("#islimit_areausercode").val(strValue);
	/*if(parentid == "selectedarea"){
		$("#islimit_areacode").val(strValue);
		if(strValue == ""){
			$("#area_nolimit").attr("checked",true);
		}
	}
	else{
		$("#default_areacode").val(strValue);
	}*/
};
