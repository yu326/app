function FoltopConfigPartAuthConfig(accountjson,datajson,target){
	var _this = this;
	this.targetID = target;
	this.dataJson = datajson;//权限json
	this.accountJson = accountjson;//计费json
	
	/**
	 * 回复默认limit
	 */
	this.defaultLimit = function(filtername,paramsjson){
		var item = filtername;
		var default_allowcontrol;
		if(paramsjson.filter[item] != undefined){
			default_allowcontrol = paramsjson.filter[item].allowcontrol;
		}
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
						var isclick = "";
						if(_this.accountJson.filter[item].limitcontrol!=0){
							isclick = "<a class='useritem_a' name='cancleselectedvaluetextobj'>×</a>";
						}
						strhtml += "<span class='selwordsbox'><span limit_type='"+paramsjson.filter[item].limit[i].type+"' limit_repeat='"+paramsjson.filter[item].limit[i].repeat+"' class='useritem'>"+paramsjson.filter[item].limit[i].value+"</span>("+repeathtml+")"+isclick+"</span>";
					}
					$(target).html(strhtml);
				}
				else{
					$(target).empty();
				}
				break;
			case "area":
			case "userid":
				var strcode="";
				var strhtml="";
				if(paramsjson.filter[item].limit.length>0) {
					var len = paramsjson.filter[item].limit.length;
					for(var i=0;i<len;i++)
					{
						var comma = ",";
						if(i == len-1){
							comma = "";
						}
						strcode += paramsjson.filter[item].limit[i].value.value+comma;
						var isclick = "";
						if(_this.accountJson.filter[item].limitcontrol!=0){
							isclick = "<a name='cancleselectedvaluetextobj' class='cancleitem'>×</a>";
						}
						strhtml+="<span class='selwordsbox'><span class='"+item+"item' code='"+paramsjson.filter[item].limit[i].value.value+"'>"+paramsjson.filter[item].limit[i].value.text+"</span>"+isclick+"</span>";	
					}
					$("#islimit_"+item+"code").val(strcode);
					$("#selected"+item+"").html(strhtml);
				}
				else{
					$("#islimit_"+item+"code").val("");
					$("#selected"+item+"").empty();
				}
				break;
			case "verified":
			case "verified_type":
			case "source":
			case "users_source_host":
				var htmlitem = item;
				if(item == "verified_type"){
					htmlitem = "verified";
				}
				if(item == "source"){
					htmlitem = "users_source_host";
				}
				if(paramsjson.filter[item].limit.length>0){
					var strhtml = "";
					$.each(paramsjson.filter[item].limit, function(li, litem){
						var isclick = "";
						if(_this.accountJson.filter[item].limitcontrol!=0){
							isclick = "<a class='cancleitem' name='cancleselectedvaluetextobj'>×</a>";
						}
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
						else if(item == "source"){
							lvalue = getSourceUrl(litem.value);
							ltext = getSourceName(litem.value);
						}
						else if(item == "users_source_host"){
							lvalue = litem.value;
							ltext = getSourceHostName(litem.value);
						}
						strhtml+="<span class='selwordsbox'><span code='"+lvalue+"' class='"+htmlitem+"item'>"+ltext+"</span>"+isclick+"</span>";	
					});
					if(htmlitem == "verified"){
						$("#selected"+htmlitem+"").append(strhtml);
					}
					else if(htmlitem == "users_source_host"){
						$("#selected"+htmlitem+"").html(strhtml);
					}
				}
				else{
					if(paramsjson.filter["verified"].limit.length == 0 && paramsjson.filter["verified_type"].limit.length == 0){
						$("#selectedverified").empty();
					}
					if(paramsjson.filter["users_source_host"].limit.length == 0){
						$("#selectedusers_source_host").empty();
					}
				}
				break;
			case "sex":
			case "users_allow_all_act_msg":
			case "users_allow_all_comment":
				if(paramsjson.filter[item].limit.length > 0){
					$('input[type=checkbox][name=islimit_'+item+']').attr("checked",false);
					$.each(paramsjson.filter[item].limit,function(i,v){
						$('#islimit_'+item+v.value).attr("checked",true);
					});
				}
				else{
					$('input[type=checkbox][name=islimit_'+item+']').attr("checked",false);
				}
				break;
				/*
			case "source":
				var sourcelist = [];
				$.ajax({url:config.getSourceUrl,type:"GET",dataType:"json",data:{sourceid:""},
					async:false,
					success:function(rd){
						if(rd && rd.length > 0 && rd[0].datalist){
							sourcelist = rd[0].datalist;
						}
					}
				});
				$("#span_limit_source").empty();
				$.each(sourcelist,function(i,v){
					$("#span_limit_source").append('<input type="checkbox" name="islimit_source" value="'+v.id+'" />'+v.name);
				});
				
				if(paramsjson.filter[item].limit.length>0)
				{
					for(var i=0;i<paramsjson.filter[item].limit.length;i++)
					{
						$('input[type="checkbox"][name="islimit_source"]').each( function(){
							if($(this).val()==paramsjson.filter[item].limit[i].value)
							{
								$(this).attr("checked",true);
							}
						});
					}
				}
				else{
					$('input[type="checkbox"][name="islimit_source"]').attr("checked",false);
				}
				break;
				*/
			case "outputcount":
				if(paramsjson.output.countlimit.limit.length > 0){
					var minv = paramsjson.output.countlimit.limit[0].value.minvalue != null ? paramsjson.output.countlimit.limit[0].value.minvalue : "";
					var maxv = paramsjson.output.countlimit.limit[0].value.maxvalue != null ? paramsjson.output.countlimit.limit[0].value.maxvalue : "";
					$("#"+item+"_minvalue").attr("value",minv);
					$("#"+item+"_maxvalue").attr("value",maxv);
				}
				else{
					$("#"+item+"_minvalue").val('');
					$("#"+item+"_maxvalue").val('');
				}
				default_allowcontrol = paramsjson.output.countlimit.allowcontrol;
				break;
			default:
				if(paramsjson.filter[item].datatype == "range" || paramsjson.filter[item].datatype == "gaprange"){
					if(paramsjson.filter[item].limit.length > 0){
						var minv = paramsjson.filter[item].limit[0].value.minvalue != null ? paramsjson.filter[item].limit[0].value.minvalue : "";
						var maxv = paramsjson.filter[item].limit[0].value.maxvalue != null ? paramsjson.filter[item].limit[0].value.maxvalue : "";
						$("#"+item+"_minvalue").attr("value",minv);
						$("#"+item+"_maxvalue").attr("value",maxv);
						if(paramsjson.filter[item].datatype == "gaprange"){
							var gap = paramsjson.filter[item].limit[0].value.gap; 
							$("#"+item+"_gap").val(gap);
						}
					}
					else{
						$("#"+item+"_minvalue").val('');
						$("#"+item+"_maxvalue").val('');
						$("#"+item+"_gap").val('');
					}
				}
				break;
		}
		if(undefined != default_allowcontrol){
			$("#allowcontrol_"+item).val(default_allowcontrol);
		}
		//当limitcontrol==0时,对应input置为不可修改
		if(_this.accountJson.filter[item] != undefined){
			if(_this.accountJson.filter[item].limitcontrol == 0){
				$("input[name=btndefaultlimit][type=button][target="+item+"]").prevAll(":input").attr("disabled", "disabled");
				$("input[name=btndefaultlimit][type=button][target="+item+"]").attr("disabled", "disabled");
			}
			else{
				$("input[name=btndefaultlimit][type=button][target="+item+"]").prevAll(":input").removeAttr("disabled");
				$("input[name=btndefaultlimit][type=button][target="+item+"]").removeAttr("disabled");
			}
		}
	};
	this.init = function(){
		var paramsjson = _this.dataJson;
	
		$("input[name=btndefaultlimit][type=button]").bind("click",function(){
			var filtername = $(this).attr("target");
			_this.defaultLimit(filtername, _this.accountJson);
		});
		//认证字段点击事件
		// 初始化地区选择控件
		$.each(["area", "verified","users_source_host"], function(k, item){
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
				//没有limit限制
				if(_this.accountJson.filter[item].limit.length == 0){
					var choiceVal = "";
					if(item == "verified"){
						choiceVal = "verifiedUrl";
					}
					else if(item == "users_source_host"){
						choiceVal = "sourceidUrl";
					}
					else{
						choiceVal = "countryUrl";
					}
				}
				else{ // 有limit限制
					var choiceVal = [];
					if(item == "verified"){
						$(_this.accountJson.filter["verified"].limit).each(function(i,item){
							var lvalue = "verify_"+item.value;
							var ltext = getVerifiedName(lvalue);
							choiceVal.push({name:ltext,code:lvalue});
						});
						$(_this.accountJson.filter["verified_type"].limit).each(function(i,item){
							var lvalue = item.value;
							var ltext = getVerifiedTypeName(lvalue);
							choiceVal.push({name:ltext,code:lvalue});
						});
					}
					else if(item == "users_source_host"){
						$(_this.accountJson.filter["users_source_host"].limit).each(function(i,item){
							var lvalue = item.value;
							var ltext = getSourceHostName(lvalue);
							choiceVal.push({name:ltext,code:lvalue});
						});
					}
					else{
						$(_this.accountJson.filter[item].limit).each(function(i,item){
							choiceVal.push({name:item.value.text,code:item.value.value});
						});
					}
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
		$("input[name=setarea]").bind('click',function(){
			var selareas = [];
			var seltarget = "";
			if($(this).attr("id") == "setarea"){
				seltarget = "selectedarea";
			}
			else{
				seltarget = "default_area";
			}
			$("#"+seltarget+" span").each(function(i, m){
					var tempObj = {};
					tempObj["name"] = $(this).text();
					tempObj["code"] = $(this).attr("code");;
					selareas.push(tempObj);
			});
			if(_this.accountJson.filter.area.limit.length > 0){
				//可选地区,从limit中读值
                var choiceVal = [];
                $(_this.accountJson.filter.area.limit).each(function(i,item){
                	choiceVal.push({name:item.value.text,code:item.value.value});
                });
				myPartSelect(choiceVal, function(data){
                    if(data.length>0){
	                    var sareas = "";
	                    var sareacodes = "";
	                    var len = data.length;
	                    $.each(data, function(i, m){
	                        var comma = ",";
	                        if(i == len-1){
	                        comma = "";
	                        }
	                        //sareas += m.name+comma; 
	                        sareacodes += m.code+comma;
	                        sareas += "<span code="+m.code+" style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;'>"+m.name+"</span><a onclick='FoltopConfigPartAuthConfig.prototype.cancleArea(this)' style='border: 1px solid #cccccc;padding:1.5px; color: red; cursor: pointer;'>×</a>";		
	                        });
	                    $("#"+seltarget).empty().append(sareas);
                    	$("#islimit_areacode").val(sareacodes);
	                    $("#area_nolimit").attr("checked",false);
                    }
                    else{
                        $("#area_nolimit").attr("checked",true);
                        $("#islimit_areacode").val("");
						$("#"+seltarget).empty();
                    }
				},selareas);
			}
			else{
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
							//sareas += m.name+comma; 
							sareacodes += m.code+comma;
							sareas += "<span code="+m.code+" style='border:1px solid #cccccc;padding:1.5px;margin: 2px 0px 2px 2px;'>"+m.name+"</span><a onclick='FoltopConfigPartAuthConfig.prototype.cancleArea(this)' style='border: 1px solid #cccccc;padding:1.5px; color: red; cursor: pointer;'>×</a>";		
						});
						$("#"+seltarget).empty().append(sareas);
						$("#islimit_areacode").val(sareacodes);
						var hiddenTarget = "";
						if(seltarget == "selectedarea"){
							hiddenTarget = "islimit_areacode";
							$("#area_nolimit").attr("checked",false);
						}
						else{
							hiddenTarget = "default_areacode";
						}
						$("#"+hiddenTarget).val(sareacodes);
					}
					else{
						if(seltarget == "selectedarea"){
							$("#area_nolimit").attr("checked",true);
						}
						$("#islimit_areacode").val("");
						$("#"+seltarget).empty();
					}
		    	}, selareas);
			}
	    });
		*/
		//用户名
		$("input[name=setuserid]").bind('click',function(){
			var selusers = [];
			var seltarget = "";
			if($(this).attr("id") == "setuserid"){
				seltarget = "selecteduserid";
			}
			else{
				seltarget = "default_userid";
			}
			$("#"+seltarget+" .useriditem").each(function(i, m){
					var tempObj = {};
					tempObj["name"] = $(this).text();
					tempObj["code"] = $(this).attr("code");;
					selusers.push(tempObj);
			});
                var choiceVal = [];
			if(_this.accountJson.filter.userid.limit.length > 0){
				//可选地区,从limit中读值
                $(_this.accountJson.filter.userid.limit).each(function(i,item){
                	choiceVal.push({name:item.value.text,code:item.value.value});
                });
			}
		    	myAccountSelect(function(data){
                    if(data.length>0){
						var useridhtml = "";
						var useridcode = "";
						var len = data.length;
						$.each(data, function(i, m){
							var username = m.name;
							var userid = m.code;
							var comma = ",";
							if(i == len-1){
								comma = "";
							}
							useridcode += userid+comma;
							useridhtml += "<span class='selwordsbox'><span code="+userid+" class='useriditem'>"+username+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";		
						});
						$("#"+seltarget).empty().append(useridhtml);
						$("#islimit_useridcode").val(useridcode);
						var hiddenTarget = "";
						if(seltarget == "selecteduserid"){
							hiddenTarget = "islimit_useridcode";
							$("#userid_nolimit").attr("checked",false);
						}
						else{
							hiddenTarget = "default_useridcode";
						}
						$("#"+hiddenTarget).val(useridcode);
					}
					else{
						if(seltarget == "selecteduserid"){
							$("#userid_nolimit").attr("checked",true);
						}
						$("#islimit_useridcode").val("");
						$("#"+seltarget).empty();
					}
		    	}, selusers, choiceVal);
	    });
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
			var sword = commonFun.trim($("#txt"+target).val());
			if(sword != ""){
				var userArr = sword.split(" ");
				limitConfigAdd(target, userArr);
				$("#txt"+target).val("");
			}
		});
		
		for(var item in paramsjson.filter){
			if(paramsjson.filter[item].limit.length == 0){
				paramsjson.filter[item].limit = _this.accountJson.filter[item].limit;
			}
			else{
				var plimit = paramsjson.filter[item].limit[0];
				if(plimit.value.maxvalue === null && plimit.value.minvalue === null){ //range 类型
					paramsjson.filter[item].limit = _this.accountJson.filter[item].limit;
				}
			}
			var acchk = parseInt(paramsjson.filter[item].allowcontrol,10);
			addAllowControlElement($("label[for=allowcontrol_"+item+"]"), "allowcontrol_"+item, acchk, _this.accountJson.filter[item].allowcontrol);
			_this.defaultLimit(item, _this.dataJson);
		}
		initAuthDownloadLimit(_this.accountJson, paramsjson);//初始化“下载”权限
		initAuthUpdateSnapshotLimit(_this.accountJson, paramsjson);//初始化“定时更新快照”权限
		initAuthEventAlertLimit(_this.accountJson, paramsjson);//初始化“事件预警”权限
		var climit = paramsjson.output.countlimit.limit;
		if(climit.length > 0 && (climit[0].value.maxvalue === null || climit[0].value.minvalue === null)){
			paramsjson.output.countlimit.limit = _this.accountJson.output.countlimit.limit;
		}
		acchk = parseInt(paramsjson.output.countlimit.allowcontrol,10);
		addAllowControlElement($("label[for=allowcontrol_outputcount]"), "allowcontrol_outputcount", acchk, _this.accountJson.output.countlimit.allowcontrol);
		_this.defaultLimit("outputcount", _this.dataJson);

		$("a[name=cancleselectedvaluetextobj]").die("click");
		$("a[name=cancleselectedvaluetextobj]").live("click", function(){
			$(this).parent().remove();
		});

	};
	// 获取表单参数
	this.getParams = function(){
		var paramsjson = deepClone(_this.dataJson);
		for(var item in paramsjson.filter)
		{ 
			var htmlitem = item;
			if(item == "verified_type"){
				htmlitem = "verified";
			}
			if(item == "source"){
				htmlitem = "users_source_host";
			}
			if($("#allowcontrol_"+htmlitem).length == 0){
				paramsjson.filter[item].allowcontrol = -1;
				paramsjson.filter[item].maxlimitlength = -1;
				paramsjson.filter[item].limitcontrol = -1;
				continue;
			}
			var alowc = $("#allowcontrol_"+htmlitem).val();
			alowc = alowc == undefined ? _this.accountJson.filter[item].allowcontrol : parseInt(alowc,10);
			if(alowc < _this.accountJson.filter[item].allowcontrol){
				alert(paramsjson.filter[item].label+'值限制字段错误');
				return false;
			}
			paramsjson.filter[item].allowcontrol = alowc; 
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
				case "area":
				case "userid":
					$("#selected"+item+" ."+item+"item").each(function(){
						var t_v = {value:$(this).attr("code"),text:$(this).text()};
						paramsjson.filter[item].limit.push( {value:t_v, type:'exact', repeat:1} );
					});
					break;
				case "sex":
				case "users_allow_all_act_msg":
				case "users_allow_all_comment":
					$("input[name=islimit_"+item+"]:checked").each(function(){
						paramsjson.filter[item].limit.push( {value:$(this).val(), type:'exact', repeat:1} );
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
					paramsjson.filter["source"].limit=[];
					$("#selectedusers_source_host .users_source_hostitem").each(function(){
						var lvalue = $(this).attr("code");
						paramsjson.filter[item].limit.push({value:lvalue, type:'exact', repeat:1});
					});
					break;
					/*
				case "source":
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
							l_v.type = 'gaprange';
							var gap =  $("#"+item+"_gap").val();
							l_v.value.gap = gap;
						}
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
			}
			if(!checkLimitlength(_this.accountJson.filter[item], paramsjson.filter[item].limit)){
				alert(paramsjson.filter[item].label+"值个数超出最大值");
				return false;
			}
			var chklimit =checkLimit(_this.accountJson.filter[item],paramsjson.filter[item].limit, _this.dataJson.filter[item].limit); 
			if(chklimit == -1){
				alert(paramsjson.filter[item].label+"不允许修改");
				return false;
			}
			else if(chklimit == 0){
				alert(paramsjson.filter[item].label+"值超出范围");
				return false;
			}
		}
		var dlchk = getAuthDownloadLimit(_this.accountJson, paramsjson);//获取“下载”相关参数
		if(!dlchk){
			return false;
		}
		var alertchk = getAuthEventAlertLimit(_this.accountJson, paramsjson);
		if(!alertchk){
			return false;
		}
		var snapshotchk = getAuthUpdateSnapshotLimit(_this.accountJson, paramsjson);
		if(!snapshotchk){
			return false;
		}

		var item = "outputcount";
		
		paramsjson.output.countlimit.limit = [];
		var maxv = commonFun.trim($("#"+item+"_maxvalue").val() != undefined ? $("#"+item+"_maxvalue").val() : "");
		var minv = commonFun.trim($("#"+item+"_minvalue").val() != undefined ? $("#"+item+"_minvalue").val() : "");
		var l_v = {value:{maxvalue:null,minvalue:null}, type:'range', repeat:1};
		l_v.value.maxvalue = maxv != "" ? parseInt(maxv,10) : null;
		l_v.value.minvalue = minv != "" ? parseInt(minv,10) : null;
		paramsjson.output.countlimit.limit.push(l_v);
		var alowc = $("#allowcontrol_"+item).val();
		alowc = alowc == undefined ? _this.accountJson.output.countlimit.allowcontrol : parseInt(alowc,10);
		if(alowc < _this.accountJson.output.countlimit.allowcontrol){
			alert(paramsjson.output.countlimit.label+'值限制字段错误');
			return false;
		}
		paramsjson.output.countlimit.allowcontrol = alowc;
		
		if(!checkLimitlength(_this.accountJson.output.countlimit, paramsjson.output.countlimit.limit)){
			alert("数据量限制值个数超出最大值");
			return false;
		}
		var chklimit =checkLimit(_this.accountJson.output.countlimit,paramsjson.output.countlimit.limit, _this.dataJson.output.countlimit.limit); 
		if(chklimit == -1){
			alert("数据量限制不允许修改");
			return false;
		}
		else if(chklimit == 0){
			alert("数据量限制值超出范围");
			return false;
		}
		return paramsjson;
	};
	this.render = function(){
		// 请求HTML
		$.ajax({type: "GET", url: config.sitePath+"parts/foltop_config_part_authconfig.html", async:false,  // 同步请求
				dataType: "html", 
				success: function(data){
					$(_this.targetID).append(data);
					_this.init();
			}});
	}
}
/**
 * 取消按钮事件，elm触发事件的控件，filter对应json中的filter的属性名称，opt操作类型（end表示取消的“限制”，default表示取消的“默认值”）
 */
FoltopConfigPartAuthConfig.prototype.cancleUser = function(elm){
	$(elm).parent().remove();
	/*if(opt == "end"){
		var len = $("#end"+filter+" .useritem").length;
		if(len == 0){
			$("#"+filter+"_nolimit").attr("checked",true);
		}
	}*/
};
FoltopConfigPartAuthConfig.prototype.cancleArea= function(elm){
	var parentid = $(elm).parent().attr("id"); //先获取父ID再删除本身
	$(elm).prev().remove();
	$(elm).remove();

	var strValue="";
	$("#"+parentid).find("span").each(function(){
		strValue+=$(this).attr("code")+",";
	});
	strValue = strValue.substring(0,strValue.length-1);
	$("#islimit_areacode").val(strValue);
};
