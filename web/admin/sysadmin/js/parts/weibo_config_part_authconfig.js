function WeiboConfigPartAuthConfig(accountjson,datajson,target){
	var _this = this;
	this.targetID = target;
	this.dataJson = datajson;
	this.accountJson = accountjson;//计费json
	/**
	 * 回复默认limit
	 */
	this.defaultLimit = function(filtername, paramsjson, type){
		if(type == "defaultclick" && filtername == "createdtime"){
			$.each(["createdtime", "beforetime", "nearlytime", "untiltime"], function(ti, titem){
				if(paramsjson.filter[titem].limit.length > 0){
					filtername = titem;
				}
			})
		}
		var item = filtername;
		var default_allowcontrol;
		if(paramsjson.filter[item] != undefined){
			default_allowcontrol = paramsjson.filter[item].allowcontrol;
		}
		switch(item)
		{
			case "username":
			case "keyword":
			case "pg_text":
			case "ancestor_text":
			case "verifiedreason":
			case "description":
			case "weibotopickeyword":
			case "ancestor_wb_topic_keyword":
			case "organization":
			case "ancestor_organization":
			case "NRN":
			case "ancestor_NRN":
			case "source":
			case "hostdomain":
			case "ancestor_host_domain":
			case "weibotopic":
			case "post_title":
            case "productType":
            case "impress":
            case "commentTags":
            case "proClassify":
            case "promotionInfos":
            case "productFullName":
            case "productColor":
            case "productSize":
            case "productDesc":
            case "productComb":
            case "detailParam":
            case "compName":
            case "compAddress":
            case "phoneNum":
            case "compURL":
            case "serviceProm":
            case "logisticsInfo":
            case "payMethod":
            case "apdComment":
            case "serviceComment":
            case "column":
			case "column1":
			case "ancestor_wb_topic":
			case "weibourl":
			case "original_url":
			case "oristatusurl":
			case "oristatus_username":
			case "repost_url":
			case "repost_username":
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
						//当limitcontrol == 0时不可修改 去掉点击事件
						var isclick = "";
						if(_this.accountJson.filter[item].limitcontrol !=0){
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
			case "topic":
			case "ancestor_combinWord":
			case "weibotopiccombinword":
			case "ancestor_wb_topic_combinWord":
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
						//当limitcontrol == 0时不可修改 去掉点击事件
						var isclick = "";
						if(_this.accountJson.filter[item].limitcontrol != 0){
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
			case "areauser":
			case "areamentioned":
			case "ancestor_areamentioned":
			case "business":
			case "ancestor_business":
			case "account":
			case "ancestor_account":
			case "userid":
			case "oristatus_userid":
			case "repost_userid":
				if(paramsjson.filter[item].limit.length>0) {
					var strhtml ="";
					var strcode ="";
					var len = paramsjson.filter[item].limit.length;
					for(var i=0;i<len;i++)
					{
						var comma = ",";
						if(i == len-1){
							comma = "";
						}
						strcode += paramsjson.filter[item].limit[i].value.value+comma;
						//当limitcontrol == 0时不可修改 去掉点击事件
						var isclick = "";
						if(_this.accountJson.filter[item].limitcontrol != 0){
							isclick = "<a name='cancleselectedvaluetextobj' class='cancleitem'>×</a>";
						}
						strhtml+="<span class='selwordsbox'><span class='"+item+"item'  code='"+paramsjson.filter[item].limit[i].value.value+"'>"+paramsjson.filter[item].limit[i].value.text+"</span>"+isclick+"</span>";	
					}
						$("#islimit_"+item+"code").val(strcode);
						$("#selected"+item).html(strhtml);
				}
				else{
					$("#selected"+item).empty();
					$("#islimit_"+item+"code").val("");
				}
				break;
			case "verified":
			case "verified_type":
			case "source_host":
			case "sourceid":
				var htmlitem = item;
				if(item == "verified_type"){
					htmlitem = "verified";
				}
				if(item == "sourceid" || item == "source_host"){
					htmlitem = "source_host";
				}
				if(paramsjson.filter[item].limit.length > 0){
					var strhtml = "";
					$.each(paramsjson.filter[item].limit, function(li, litem){
						var name = "";
						var code = "";
						if(item == "verified"){
							name = getVerifiedName("verify_"+litem.value);
							code = "verify_"+litem.value;
						}
						else if(item == "verified_type"){
							name = getVerifiedTypeName(litem.value);
							code = litem.value;
						}
						else if(item == "sourceid"){
							code = getSourceUrl(litem.value);
							name = getSourceName(litem.value);
						}
						else if(item == "source_host"){
							code = litem.value;
							name = getSourceHostName(litem.value);
						}
					strhtml+="<span class='selwordsbox'><span class='"+htmlitem+"item' code='"+code+"' >"+name+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";	
					});
					if(htmlitem == "verified"){
						$("#selected"+htmlitem+"").append(strhtml);
					}
					else if(htmlitem == "source_host"){
						$("#selected"+htmlitem+"").html(strhtml);
					}
				}
				else{
					if(paramsjson.filter["verified"].limit.length == 0 && paramsjson.filter["verified_type"].limit.length == 0){
						$("#selectedverified").empty();
					}
					if(paramsjson.filter["source_host"].limit.length == 0){
						$("#selected"+htmlitem+"").empty();
					}
				}
				break;
			case "sex":
            case "haspicture":
            case "isNewPro":
            case "isFavorite":
            case "isAttention":
			case "recommended":
			case "weibotype":
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
			case "emoAreamentioned":
			case "ancestor_emoAreamentioned":
			case "emoBusiness":
			case "ancestor_emoBusiness":
			case "emoAccount":
			case "ancestor_emoAccount":
				if(paramsjson.filter[item].limit.length > 0){
					var strhtml = "";
					$.each(paramsjson.filter[item].limit, function(li, litem){
						var tmpcode = litem.value.value;
						var tmpname = litem.value.text;
						var code = tmpcode.substr(0, tmpcode.length-2).toString();
						var emotype = tmpcode.substr(tmpcode.length-1, 1);
						var emotypeattr = "emotype='"+emotype+"'";
						var i = tmpname.indexOf("(");
						var txt = tmpname.substr(0, i);
						strhtml += "<span class='selwordsbox'><span class='"+item+"item' code='"+code+"' "+emotypeattr+" >"+txt+"("+emoval2text(emotype)+")</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";	
					});
					$("#selected"+item).html(strhtml);
				}
				else{
					$("#selected"+item).empty();
				}
				break;
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
				if(paramsjson.filter[item].limit.length>0){
					var strhtml = "";
					$.each(paramsjson.filter[item].limit, function(i, v){
						if(typeof(v.value) == "string"){
							var code = v.value.substr(0, v.value.length-2).toString();
							var emotype = v.value.substr(v.value.length-1, 1);
							var cv = code+"("+emotype2text(emotype)+")"; 
							var repeathtml="";
							if(v.repeat == -1){
								repeathtml = "个数不限";
							}
							else{
								repeathtml = v.repeat+"个";
							}

					strhtml+="<span class='selwordsbox'><span limit_type='"+v.type+"' limit_repeat='"+v.repeat+"'  code='"+code+"' emotype='"+emotype+"' class='useritem' >"+cv+"</span>("+repeathtml+")<a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";	
						}
					});
					$("#end"+item).html(strhtml);
				}
				else{
					$("#end"+item).empty();
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
						$('input[type=checkbox][name=islimit_sourceid]').each(function(){
							if($(this).val()==paramsjson.filter[item].limit[i].value){
								$(this).attr("checked",true);
							}
						});
					}
				}
				else{
					$('input[type="checkbox"][name=islimit_'+item+']').attr("checked",false);
				}
				break;
				*/
			case "createdtime":
				if(paramsjson.filter[item].limit.length > 0){
					var minv = paramsjson.filter[item].limit[0].value.minvalue != null ? formatTime('yyyy-MM-dd hh:mm:ss', paramsjson.filter[item].limit[0].value.minvalue) : "";
					var maxv = paramsjson.filter[item].limit[0].value.maxvalue != null ? formatTime('yyyy-MM-dd hh:mm:ss', paramsjson.filter[item].limit[0].value.maxvalue) : "";
					$("#weibo_authconfig_facet_range_createdtime_start").val(minv);
					$("#weibo_authconfig_facet_range_createdtime_end").val(maxv);
					if(minv == "" && maxv == ""){
						$("#weibo_authconfig_facet_range_createdtime_all").attr("checked", true);
					}
					else{
						setTimeSelectMsg("weibo_authconfig_facet_range", minv, maxv, true); 
					}
					changeTimeBorderColor("weibo_authconfig_facet_range_html", "createdtime"); 
					$("#weibo_authconfig_facet_range_html").attr("checkedtimetype", "createdtime");
				}
				else{
					$("#weibo_authconfig_facet_range_createdtime_all").attr("checked", true);
				}
				break;
			case "nearlytime":
				if(paramsjson.filter[item].limit.length > 0 && paramsjson.filter[item].limit[0].value.start!=undefined){
					var facet_range_prefix = "weibo_authconfig_facet_range";
					var ntv = paramsjson.filter[item].limit[0].value.start;
					var ntgap = paramsjson.filter[item].limit[0].value.startgap;
					var nttimestate = paramsjson.filter[item].limit[0].value.timestate;
					$("#"+facet_range_prefix+"_nearlytime").val(ntv);
					$("#"+facet_range_prefix+"_nearlytime_gap").val(ntgap);
					//$("#"+facet_range_prefix+"_nearlytime_"+ntgap+"").attr("checked", true);
					$("input[name="+facet_range_prefix+"_nearlytime_shortcut][nearly="+ntv+ntgap+"]").attr("checked", true);

					setThisTime("#"+facet_range_prefix+"_nearlytime_thistime", nttimestate);
					var nts = strtotime("-"+ntv+" "+ntgap+"");
					var nte = strtotime("now");
					setDateSelect("#"+facet_range_prefix+"_timeselect_start", nts, "#"+facet_range_prefix+"_timeselect_end", nte, "now", "", nttimestate, "#"+facet_range_prefix+"_splitmark"); 
					changeTimeBorderColor("weibo_authconfig_facet_range_html", "nearlytime"); 
					$("#weibo_authconfig_facet_range_html").attr("checkedtimetype", "nearlytime");
				}
				break;
			case "beforetime":
				if(paramsjson.filter[item].limit.length > 0 && paramsjson.filter[item].limit[0].value.start != undefined){
					var facet_range_prefix = "weibo_authconfig_facet_range";
					var btsv = paramsjson.filter[item].limit[0].value.start;
					var btev = paramsjson.filter[item].limit[0].value.end;
					var btegap = paramsjson.filter[item].limit[0].value.endgap;
					$("#"+facet_range_prefix+"_beforetime_start").val(btsv);
					$("#"+facet_range_prefix+"_beforetime_start_gap").val(btegap);
					$("#"+facet_range_prefix+"_beforetime_end").val(btev);
					$("#"+facet_range_prefix+"_beforetime_end_gap").val(btegap);
					var timestate = paramsjson.filter[item].limit[0].value.timestate;
					var datestate = paramsjson.filter[item].limit[0].value.datestate;
					setThisDate("#"+facet_range_prefix+"_beforetime_thisdate", datestate, btegap);
					setThisTime("#"+facet_range_prefix+"_beforetime_thistime", timestate, btegap);

					var bts = strtotime("-"+btsv+" "+btegap+"");
					var bte = strtotime("-"+btev+" "+btegap+"");
					setDateSelect("#"+facet_range_prefix+"_timeselect_start", bts, "#"+facet_range_prefix+"_timeselect_end", bte, datestate, btegap, timestate, "#"+facet_range_prefix+"_splitmark"); 
					changeTimeBorderColor("weibo_authconfig_facet_range_html", "beforetime"); 
					$("#weibo_authconfig_facet_range_html").attr("checkedtimetype", "beforetime");
				}
				break;
			case "untiltime":
				if(paramsjson.filter[item].limit.length > 0 && paramsjson.filter[item].limit[0].value.start != undefined){
					var facet_range_prefix = "weibo_authconfig_facet_range";
					var utsv = paramsjson.filter[item].limit[0].value.start.start;
					var utsgap = paramsjson.filter[item].limit[0].value.start.startgap;
					var utsthisgap = paramsjson.filter[item].limit[0].value.start.startthisgap; 
					var utsto = paramsjson.filter[item].limit[0].value.start.startto;
					var utstogap = paramsjson.filter[item].limit[0].value.start.starttogap;
					$("#"+facet_range_prefix+"_untiltime_start").val(utsv);
					$("#"+facet_range_prefix+"_untiltime_start_gap").val(utsgap);
					$("#"+facet_range_prefix+"_untiltime_start_this_gap").val(utsthisgap);
					$("#"+facet_range_prefix+"_untiltime_start_to").val(utsto);
					$("#"+facet_range_prefix+"_untiltime_start_to_gap").val(utstogap);
					$("#"+facet_range_prefix+"_untiltime_div input[untilstart="+utsv+utsgap+"]").attr("checked", true);
					//至今? (年|月|日|时|分|秒 周) 该(年|月|日|时|分|秒 周) 第? (年|月|日|时|分|秒 周)
					//end
					var utev = paramsjson.filter[item].limit[0].value.end.end;
					var utegap = paramsjson.filter[item].limit[0].value.end.endgap;
					var utethisgap = paramsjson.filter[item].limit[0].value.end.endthisgap; 
					var uteto = paramsjson.filter[item].limit[0].value.end.endto;
					var utetogap = paramsjson.filter[item].limit[0].value.end.endtogap;
					$("#"+facet_range_prefix+"_untiltime_end").val(utev);
					$("#"+facet_range_prefix+"_untiltime_end_gap").val(utegap);
					$("#"+facet_range_prefix+"_untiltime_end_this_gap").val(utethisgap);
					$("#"+facet_range_prefix+"_untiltime_end_to").val(uteto);
					$("#"+facet_range_prefix+"_untiltime_end_to_gap").val(utetogap);
					$("#"+facet_range_prefix+"_untiltime_div input[untilend="+utev+utegap+"]").attr("checked", true);
					if(utsv!="" && utsto!="" && utev!="" && uteto!=""){
						var us = sinceToThis(utsv, utsgap, utsthisgap, utsto, utstogap, "beginning");
						var ue = sinceToThis(utev, utegap, utethisgap, uteto, utetogap, "ending");
						setDateSelect("#"+facet_range_prefix+"_timeselect_start", us, "#"+facet_range_prefix+"_timeselect_end", ue, "now", "", "now", "#"+facet_range_prefix+"_splitmark"); 
					}
					changeTimeBorderColor("weibo_authconfig_facet_range_html", "untiltime"); 
					$("#weibo_authconfig_facet_range_html").attr("checkedtimetype", "untiltime");
				}
				break;
			case "outputcount":
				if(paramsjson.output.countlimit.limit.length > 0){
					var minv = paramsjson.output.countlimit.limit[0].value.minvalue != null ? paramsjson.output.countlimit.limit[0].value.minvalue : "";
					var maxv = paramsjson.output.countlimit.limit[0].value.maxvalue != null ? paramsjson.output.countlimit.limit[0].value.maxvalue : "";
					$("#"+item+"_minvalue").val(minv);
					$("#"+item+"_maxvalue").val(maxv);
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
						$("#"+item+"_minvalue").val(minv);
						$("#"+item+"_maxvalue").val(maxv);
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
		$("input[name=btndefaultlimit][type=button]").bind("click",function(){  //
			var filtername = $(this).attr("target");
			_this.defaultLimit(filtername, _this.accountJson, "defaultclick");
		});
		$.each(["areauser", "areamentioned","ancestor_areamentioned", "emoAreamentioned","ancestor_emoAreamentioned", "business","ancestor_business", "emoBusiness","ancestor_emoBusiness", "verified", "source_host"], function(k, item){
			$("#set"+item+"").bind('click', function(){
				var isemo = false;
				if(item == "emoAreamentioned" || item == "ancestor_emoAreamentioned" || item == "emoBusiness" || item == "ancestor_emoBusiness"){
					isemo = true;
				}
				var fieldtxt = "";
				if(item == "business" || item == "ancestor_business" || item == "emoBusiness" || item == "ancestor_emoBusiness"){
					fieldtxt = "行业";
				}
				else if(item == "verified"){
					fieldtxt = "认证";
					var pname = 1;
				}
				else if(item == "source_host"){
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
			if(_this.accountJson.filter[item].limit.length > 0){
				var choiceVal = [];
				if(item == "verified"){
					//认证和认证类型权限
					$(_this.accountJson.filter["verified"].limit).each(function(i,item){
						var tmpcode = "verify_"+item.value;
						var tmpname = getVerifiedName(tmpcode);
						choiceVal.push({name:tmpname,code:tmpcode});
					});
					$(_this.accountJson.filter["verified_type"].limit).each(function(i,item){
						var tmpcode = item.value;
						var tmpname = getVerifiedTypeName(tmpcode);
						choiceVal.push({name:tmpname,code:tmpcode});
					});
				}
				else if(item == "source_host"){
					$(_this.accountJson.filter["source_host"].limit).each(function(i,item){
						var tmpcode = item.value;
						var tmpname = getSourceHostName(tmpcode);
						choiceVal.push({name:tmpname,code:tmpcode});
					});
				}
				else{
					$(_this.accountJson.filter[item].limit).each(function(i,item){
						choiceVal.push({name:item.value.text,code:item.value.value});
					});
				}
			}
			else{
				var choiceVal = "";
				if(item == "business" || item == "ancestor_business" || item == "emoBusiness" || item == "ancestor_emoBusiness"){
					choiceVal = "businessUrl";
				}
				else if(item == "verified"){
					choiceVal = "verifiedUrl";
				}
				else if(item == "source_host"){
					choiceVal = "sourceidUrl";
				}
				else{
					choiceVal = "countryUrl";
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
						var emotypeattr = "";
						if(m.emotype != undefined){
							var emoarr = [];
							if(m.emotype == "*"){
								emoarr = [1,2,3,4,5];
							}
							else{
								emoarr = m.emotype.split(",");
							}
							var i = m.name.indexOf("(");
							var txt = m.name.substr(0, i);
							var tmpname = txt;
							$.each(emoarr, function(e, eitem){
								emotypeattr = "emotype='"+eitem+"'";
								sareas += "<span class='selwordsbox'><span class='"+item+"item' "+emotypeattr+" code="+m.code+" >"+tmpname+"("+emoval2text(eitem)+")</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";		
							});
						}
						else{
							sareas += "<span class='selwordsbox'><span class='"+item+"item' code="+m.code+" >"+m.name+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";		
						}

						/*
						if(m.emotype != undefined){
							emotypeattr = "emotype='"+m.emotype+"'";
						}
						sareas += "<span class='selwordsbox'><span class='"+item+"item' "+emotypeattr+" code="+m.code+" >"+m.name+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";		
						*/
					});
					$("#selected"+item+"").empty().append(sareas);
					$("#islimit_"+item+"code").val(sareacodes);
				}
				else{
					$("#selected"+item+"").empty();
					$("#islimit_"+item+"code").val("");
				}
			}, sltVal, fieldtxt, isemo, undefined, false, pname, undefined, item);
			});
		});
		$.each(["account","ancestor_account", "emoAccount","ancestor_emoAccount", "userid", "oristatus_userid", "repost_userid"], function(i, item){
			$("#set"+item+"").bind("click", function(){
				var isemo = false;
				if(item == "emoAccount" || item == "ancestor_emoAccount"){
					isemo = true;
				}
				var sltVal = [];
				$("#selected"+item+" ."+item+"item").each(function(si, sitem){
					var tmpObj = {};
					tmpObj["name"] = $(sitem).text();
					tmpObj["code"] = $(sitem).attr("code");
					if($(sitem).attr("emotype") != undefined){
						tmpObj["emotype"] = $(sitem).attr("emotype");
					}
					sltVal.push(tmpObj);
				});

				var choiceVal = [];
				if(_this.accountJson.filter[item].limit.length > 0){
					$(_this.accountJson.filter[item].limit).each(function(ti, titem){
						choiceVal.push({name:titem.value.text, code:titem.value.value});
					});
				}
				myAccountSelect(function(data){
					var accounthtml = "";
					var accountcode = "";
					if(data.length>0){
						var len = data.length;
						$.each(data,function(i,m){
							var username = m.name;
							var userid = m.code;
							var comma = ",";
							if (i == len - 1) {
								comma = "";
							}
							accountcode += userid + comma;
							var emotypeattr = "";
							if(m.emotype != undefined){
								var emoarr = [];
								if(m.emotype == "*"){
									emoarr = [1,2,3,4,5];
								}
								else{
									emoarr = m.emotype.split(",");
								}
								var i = m.name.indexOf("(");
								var txt = m.name.substr(0, i);
								var tmpname = txt;
								$.each(emoarr, function(e, eitem){
									emotypeattr = "emotype='"+eitem+"'";
									accounthtml += "<span class='selwordsbox'><span class='"+item+"item' "+emotypeattr+" code="+m.code+" >"+tmpname+"("+emoval2text(eitem)+")</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";		
								});
							}
							else{
								accounthtml += "<span class='selwordsbox'><span class='"+item+"item' code="+userid+" >"+username+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";
							}

							/*
							if(m.emotype != undefined){
								emotypeattr = "emotype='"+m.emotype+"'";
							}
							accounthtml += "<span class='selwordsbox'><span class='"+item+"item' "+emotypeattr+" code="+userid+" >"+username+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";
							*/
						});
						$("#selected"+item+"").empty().append(accounthtml);
						$("#islimit_"+item+"code").val(accountcode);
					}
					else{
						$("#selected"+item+"").empty();
						$("#islimit_"+item+"code").val("");
					}
				}, sltVal, choiceVal, isemo);
			});
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
		$.each(["keyword","pg_text","ancestor_text","verifiedreason","source","hostdomain","ancestor_host_domain","description", "weibotopic","ancestor_wb_topic", "organization","ancestor_organization","NRN","ancestor_NRN", "weibotopickeyword", "ancestor_wb_topic_keyword"], function(i, item){
			_this.keywordRecommend(item, myKeywordRecommend);
		});
		$.each(["emotion", "ancestor_emotion", "emoCombin","ancestor_emoCombin","emoNRN","ancestor_emoNRN","emoOrganization","ancestor_emoOrganization", "emoTopic","ancestor_emoTopic", "emoTopicKeyword","ancestor_emoTopicKeyword", "emoTopicCombinWord","ancestor_emoTopicCombinWord", "topic","ancestor_combinWord", "weibotopiccombinword", "ancestor_wb_topic_combinWord"], function(i, item){
			_this.keywordRecommend(item, myTopicRecommend);
		});
		$.each(["keyword","pg_text","ancestor_text", "verifiedreason", "source", "hostdomain","ancestor_host_domain","description", "weibotopic","ancestor_wb_topic", "organization","ancestor_organization", "NRN","ancestor_NRN", "weibotopickeyword", "ancestor_wb_topic_keyword"], function(i, item){
			_this.keywordAdd(item);
		});

		$.each(["emotion","ancestor_emotion", "emoCombin","ancestor_emoCombin", "emoNRN","ancestor_emoNRN", "emoOrganization","ancestor_emoOrganization", "emoTopic","ancestor_emoTopic", "emoTopicKeyword","ancestor_emoTopicKeyword", "emoTopicCombinWord", "ancestor_emoTopicCombinWord"], function(k, item){
			$("input[type=button][name=add"+item+"]").bind("click", function(){
				var target = $(this).attr("target");
				var sword = $("#txt"+target).val();
				var chklen = $("input[name=islimit_"+item+"]:checked").length;
				if(chklen == 0){
					alert("请选择情感!");
					return false;
				}
				if(sword != ""){
					var wordArr = [];
					$("input[name=islimit_"+item+"]:checked").each(function(i, ci){
						var et = $(ci).val();
						wordArr.push(sword+","+et);
					});
					limitConfigAdd(target, wordArr);
					$("#txt"+target).val("");
				}
			});
		});
		//话题添加
		$.each(["topic","ancestor_combinWord", "weibotopiccombinword", "ancestor_wb_topic_combinWord"], function(i, item){
			$("input[type=button][name=add"+item+"]").bind("click", function() {
				var target = $(this).attr("target");
				var sword = commonFun.trim($("#txt"+target).val());
				if(sword != ""){
					var wordArr = sword.split(" ");
					limitConfigAdd(target, wordArr);
					$("#txt"+target).val("");
				}
			});
		});
		/*
		   $('#createdtime_minvalue').bind("click", function() {
		   WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'});
		   });
		   $('#createdtime_maxvalue').bind("click", function() {                    
		   WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'});
		   });
		   */
		timeDisplayHtml("weibo_authconfig");
		for(var item in paramsjson.filter){		
			if(paramsjson.filter[item].limit.length == 0){
				if(item == "createdtime" || item == "nearlytime" || item == "beforetime" || item == "untiltime"){
					var ctlen = paramsjson.filter["createdtime"].limit.length;
					var ntlen = paramsjson.filter["nearlytime"].limit.length;
					var btlen = paramsjson.filter["beforetime"].limit.length;
					var utlen = paramsjson.filter["untiltime"].limit.length;
					if(ctlen == 0 && ntlen == 0 && btlen == 0 && utlen == 0){
						paramsjson.filter[item].limit = _this.accountJson.filter[item].limit;
					}
				}
				else{
					paramsjson.filter[item].limit = _this.accountJson.filter[item].limit;
				}
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
		_this.defaultLimit("outputcount",_this.dataJson);
		$("a[name=cancleselectedvaluetextobj]").die("click");
		$("a[name=cancleselectedvaluetextobj]").live("click", function(){
			$(this).parent().remove();
		});
	};
	this.keywordRecommend = function(field, recommendFun){
		if(field.indexOf("emo") > -1){
			isemo = true;
		}
		var fieldname = getFacetname(field);
		var objJson = _this.dataJson;
		var fieldtext = objJson.filter[field].label;

		$("input[name=commend"+field+"]").bind("click", function() {
			var target = $(this).attr("target");
			var txttarget = "#txt"+target;
			var keyword = commonFun.trim($(txttarget).val());
			if (keyword != "") {
				recommendFun(keyword, function(data){
					switch(field){
						case "topic":
						case "ancestor_combinWord":
						case "weibotopiccombinword":
						case "ancestor_wb_topic_combinWord":
							var resval = [];
							$.each(data, function(i, m){
								if(m.topic != ""){
									resval.push(m.topic);
								}
							});
							limitConfigAdd(target, resval);
							break;
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
							var resval = [];
							$.each(data, function(i, m){
								if(m.topic != "") {
									var mtxt = m.topic;
									if(m.emotype!=undefined){
										var emoarr = m.emotype.split(",");
										if(m.emotype == "*"){
											emoarr = [1,2,3,4,5]; 
										}
										$.each(emoarr, function(ei, eitem){
											mtxt = m.topic+","+eitem;
											resval.push(mtxt);
										});
									}
								}
							});
							limitConfigAdd(target, resval);
							break;
						default:
							var resval = [];
							$.each(data, function(i, m){
								if(m.name != ""){
									resval.push(m.name);
								}
							});
							limitConfigAdd(target, resval);
							break;
					}
					$(txttarget).val("");
				}, fieldname, fieldtext, isemo);
			}
		});
	};
	this.keywordAdd = function(field){
		$("input[type=button][name=add"+field+"]").bind("click", function() {
			var target = $(this).attr("target");
			var sword = commonFun.trim($("#txt"+target).val());
			if(sword != ""){
				var userArr = sword.split(" ");
				limitConfigAdd(target, userArr);
				$("#txt"+target).val("");
			}
		});
	};
	// 获取表单参数
	this.getParams = function(){
		var paramsjson = deepClone(_this.dataJson);
		var timetype = $("#weibo_authconfig_facet_range_html").attr("checkedtimetype"); //最后一次选择的时间控件
		for(var item in paramsjson.filter)
		{ 
			var htmlitem = item;
			switch(item){
				case "nearlytime":
				case "beforetime":
				case "untiltime":
					htmlitem = "createdtime";
					break;
				case "weiboid":
				case "weibourl":
					htmlitem = "original_url";
					break;
				case "oristatus":
					htmlitem = "oristatusurl";
					break;
				case "verified_type":
					htmlitem = "verified";
					break;
				default:
					htmlitem = item;
					break;
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
				case "keyword":
				case "pg_text":
				case "ancestor_text":
				case "verifiedreason":
				case "description":
				case "source":
				case "hostdomain":
				case "ancestor_host_domain":
				case "weibotopickeyword":
				case "ancestor_wb_topic_keyword":
				case "organization":
				case "ancestor_organization":
				case "NRN":
				case "ancestor_NRN":
				case "weibotopic":
				case "post_title":
                case "productType":
                case "impress":
                case "commentTags":
                case "proClassify":
                case "promotionInfos":
                case "productFullName":
                case "productColor":
                case "productSize":
                case "productDesc":
                case "productComb":
                case "detailParam":
                case "compName":
                case "compAddress":
                case "phoneNum":
                case "compURL":
                case "serviceProm":
                case "logisticsInfo":
                case "payMethod":
                case "apdComment":
                case "serviceComment":
                case "column":
				case "column1":
				case "ancestor_wb_topic":
				case "weibourl":
				case "original_url":
				case "oristatusurl":
				case "oristatus_username":
				case "repost_url":
				case "repost_username":
					target = "#end"+item;
					$(target+" .useritem").each(function(){
						var lv = $(this).text();
						var lt = $(this).attr('limit_type');
						var lr = $(this).attr('limit_repeat');
						paramsjson.filter[item].limit.push({value:lv,type:lt,repeat:parseInt(lr,10)});
					});
					break;
				case "topic":
				case "ancestor_combinWord":
				case "weibotopiccombinword":
				case "ancestor_wb_topic_combinWord":
					target = "#end"+item;
					$(target+" .useritem").each(function(){
						var lv = $(this).text();
						var lt = $(this).attr('limit_type');
						var lr = $(this).attr('limit_repeat');
						paramsjson.filter[item].limit.push({value:lv,type:lt,repeat:parseInt(lr,10)});
					});
					break;
				case "areauser":
				case "areamentioned":
				case "ancestor_areamentioned":
				case "business":
				case "ancestor_business":
				case "account":
				case "ancestor_account":
				case "userid":
				case "oristatus_userid":
				case "repost_userid":
					if($("#selected"+item+" ."+item+"item").length > 0){
						$("#selected"+item+" ."+item+"item").each(function(li, litem){
							var code = $(litem).attr("code");
							var name = $(litem).text();
							var t_v = {value:code, text:name};
							paramsjson.filter[item].limit.push( {value:t_v, type:'exact', repeat:1} );
						});
					}
					break;
				case "sex":
				case "haspicture":
                case "isNewPro":
                case "isFavorite":
                case "isAttention":
				case "recommended":
					$("input[name=islimit_"+item+"]:checked").each(function(){
						paramsjson.filter[item].limit.push( {value:$(this).val(), type:'exact', repeat:1} );
					});
					break;
				case "verified":
					//case "verified_type":
					paramsjson.filter["verified_type"].limit=[];
					paramsjson.filter["verified"].limit=[];
					if($("#selectedverified .verifieditem").length > 0){
						$("#selectedverified .verifieditem").each(function(si, sitem){
							var vcode = $(sitem).attr("code");
							if(vcode.indexOf("verify_") > -1){
								var va = vcode.split("verify_");
								paramsjson.filter["verified"].limit.push( {value:va[1], type:'exact', repeat:1} );
							}
							else{
								paramsjson.filter["verified_type"].limit.push( {value:vcode, type:'exact', repeat:1} );
							}
						});
					}
					break;
				case "source_host":
					paramsjson.filter["source_host"].limit=[];
					paramsjson.filter["sourceid"].limit=[];
					if($("#selectedsource_host .source_hostitem").length > 0){
						$("#selectedsource_host .source_hostitem").each(function(si, sitem){
							var vcode = $(sitem).attr("code");
							paramsjson.filter["source_host"].limit.push( {value:vcode, type:'exact', repeat:1} );
						});
					}
					break;
				case "weibotype":
				//case "sourceid":
					$("input[name=islimit_"+item+"]:checked").each(function(){
						paramsjson.filter[item].limit.push( {value:parseInt($(this).val(),10), type:'exact', repeat:1} );
					});
					break;
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
					target = "#end"+item;
					$(target+" .useritem").each(function(){
						var lv = $(this).attr("code")+","+$(this).attr("emotype");
						var lt = $(this).attr('limit_type');
						var lr = $(this).attr('limit_repeat');
						paramsjson.filter[item].limit.push({value:lv,type:lt,repeat:parseInt(lr,10)});
					});
					break;
				case "emoAreamentioned":
				case "ancestor_emoAreamentioned":
				case "emoBusiness":
				case "ancestor_emoBusiness":
				case "emoAccount":
				case "ancestor_emoAccount":
					if($("#selected"+item+" ."+item+"item").length > 0){
						$("#selected"+item+" ."+item+"item").each(function(si, sitem){
							var emotype = $(sitem).attr("emotype");
							var emoArr = emotype.split(",");
							$.each(emoArr, function(ei, eitem){
								var t_v = {value:$(sitem).attr("code")+","+eitem, text:$(sitem).text()};
								paramsjson.filter[item].limit.push( {value:t_v, type:'exact', repeat:1} );
							});
						});
					}
					break;
				case "createdtime":
					var allchk = $("#weibo_authconfig_facet_range_createdtime_all").prop("checked");
					if(timetype == "createdtime" && !allchk){
						var cts = $("#weibo_authconfig_facet_range_createdtime_start").val();
						var cte = $("#weibo_authconfig_facet_range_createdtime_end").val();
						var minv = commonFun.trim(cts != undefined ? cts : "");
						var maxv = commonFun.trim(cte != undefined ? cte : "");
						maxv = maxv == "" ? "" : getTimeSec(maxv);
						minv = minv == "" ? "" : getTimeSec(minv);
						var l_v = {value:{maxvalue:null,minvalue:null}, type:'range', repeat:1};
						l_v.value.maxvalue = maxv != "" ? parseInt(maxv,10) : null;
						l_v.value.minvalue = minv != "" ? parseInt(minv,10) : null;
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
				case "nearlytime":
					if(timetype == "nearlytime"){
						var timevalue = getnearlytimeValue("weibo_authconfig_facet_range");
						var l_v = {value:timevalue, type:'time_dynamic_state', repeat:1, name:"nearlytime"};
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
				case "beforetime":
					if(timetype == "beforetime"){
						var timevalue = getbeforeTimeValue("weibo_authconfig_facet_range");
						if(timevalue.start == "" || timevalue.end == ""){
							alert("请填写完整时间段");
							return false
						}
						var l_v = {value:timevalue, type:'time_dynamic_state', repeat:1, name:"beforetime"};
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
				case "untiltime":
					if(timetype == "untiltime"){
						var timevalue = getuntiltimeValue("weibo_authconfig_facet_range");
						if(timevalue.start.start == "" || timevalue.start.startto == "" || timevalue.end.end == "" || timevalue.end.endto == ""){
							alert("请填写完整日历时间段");
							return false;
						}
						var l_v = {value:timevalue, type:'time_dynamic_range', repeat:1};
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
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
			var alertitem = item;
			switch(item){
				case "weiboid":
				case "weibourl":
					alertitem = "original_url";
					break;
				case "oristatus":
					alertitem = "oristatusurl";
					break;
				default:
					alertitem = item;
					break;
			}
			if(!checkLimitlength(_this.accountJson.filter[item], paramsjson.filter[item].limit)){
				alert(paramsjson.filter[alertitem].label+"值个数超出最大值");
				return false;
			}
			var chklimit =checkLimit(_this.accountJson.filter[item], paramsjson.filter[item].limit, _this.dataJson.filter[item].limit); 
			if(chklimit == -1){
				alert(paramsjson.filter[item].label+"不允许修改");
				return false;
			}
			else if(chklimit == 0){
				alert(paramsjson.filter[item].label+"值超出范围");
				return false;
			}
		}
		//对时间字段进行范围验证,当计费和权限选择不同字段的情况
		var accountTimeField = "";//计费中设置范围的字段
		var authTimeField = "";//权限中设置范围的字段
		$.each(["beforetime", "createdtime", "nearlytime", "untiltime"], function(i, item){
			if(_this.accountJson.filter[item].limit.length > 0){
				accountTimeField = item;
			}
			if(paramsjson.filter[item].limit.length > 0){
				authTimeField = item;
			}
		});
		if(accountTimeField != "" && authTimeField != ""){
			var accountTime = getTimeLimitMaxMin(accountTimeField, _this.accountJson);
			var authTime = getTimeLimitMaxMin(authTimeField, paramsjson);
			var retflag = true;
			if(accountTime.max != null && (authTime.max > accountTime.max || authTime.min > accountTime.max)){
				retflag = false;
			}
			else if(accountTime.min != null && (authTime.min < accountTime.min || authTime.max < accountTime.min)){
				retflag = false;
			}
			if(!retflag){
				alert(paramsjson.filter[authTimeField].label+"值超出范围");
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

		item = "outputcount";
		alowc = $("#allowcontrol_"+item).val();
		alowc = alowc == undefined ? _this.accountJson.output.countlimit.allowcontrol : parseInt(alowc,10);
		if(alowc < _this.accountJson.output.countlimit.allowcontrol){
			alert(paramsjson.output.countlimit.label+'值限制字段错误');
			return false;
		}
		paramsjson.output.countlimit.allowcontrol = alowc;
		paramsjson.output.countlimit.limit = [];
		var maxv = commonFun.trim($("#"+item+"_maxvalue").val() != undefined ? $("#"+item+"_maxvalue").val() : "");
		var minv = commonFun.trim($("#"+item+"_minvalue").val() != undefined ? $("#"+item+"_minvalue").val() : "");
		var l_v = {value:{maxvalue:null,minvalue:null}, type:'range', repeat:1};
		l_v.value.maxvalue = maxv != "" ? parseInt(maxv,10) : null;
		l_v.value.minvalue = minv != "" ? parseInt(minv,10) : null;
		paramsjson.output.countlimit.limit.push(l_v);
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
    this.checkboxFieldHtml = function(fieldObj){
        var divhtml = '';
        divhtml += '<div class="search" id="islimit_'+fieldObj.name+'"> <span class="rightalign">'+fieldObj.label+'限制：</span>';
        divhtml += '&nbsp;&nbsp;<input type="checkbox" name="islimit_'+fieldObj.name+'" id="islimit_'+fieldObj.name+'1" value="1" /> 是';
        divhtml += '&nbsp;&nbsp;<input type="checkbox" name="islimit_'+fieldObj.name+'" id="islimit_'+fieldObj.name+'0" value="0" /> 否';
        divhtml += '&nbsp;&nbsp;<label for="allowcontrol_'+fieldObj.name+'">值限制：</label>';
        divhtml += '&nbsp;&nbsp;<input type="button" name="btndefaultlimit" target="'+fieldObj.name+'" value="恢复默认" />';
        divhtml += '</div>';
        return divhtml;
    };
    this.inputFieldHtml = function(fieldObj){
        var divhtml = '';
        divhtml += '<div class="search"><span class="rightalign">'+fieldObj.label+'：</span>';
        divhtml += '&nbsp;&nbsp;<input class="shortinput" type="text" id="txt'+fieldObj.name+'" name="'+fieldObj.name+'" />';
        divhtml += '<input name="commend'+fieldObj.name+'" target="'+fieldObj.name+'" type="button" value="推荐"/> ';
        divhtml += '<input type="button" id="btnadd'+fieldObj.name+'" name="add'+fieldObj.name+'" target="'+fieldObj.name+'" value="添加限制" />';
        divhtml += '&nbsp;&nbsp;<label for="allowcontrol_'+fieldObj.name+'">值限制：</label>';
        divhtml += '&nbsp;&nbsp;<input type="button" name="btndefaultlimit" target="'+fieldObj.name+'" value="恢复默认" />';
        divhtml += '</div>';
        return divhtml;
    };
    this.rangeFieldHtml = function(fieldObj){
        var divhtml = '';
        divhtml += '<div class="search"> <span class="rightalign">'+fieldObj.label+'：</span>';
        divhtml += '&nbsp;&nbsp;最小值：<input type="text" class="shortestinput"  name="'+fieldObj.name+'_minvalue" id="'+fieldObj.name+'_minvalue" value=""  />';
        divhtml += '&nbsp;&nbsp;最大值：<input type="text" class="shortestinput" name="'+fieldObj.name+'_maxvalue" id="'+fieldObj.name+'_maxvalue" value="" />';
        divhtml += '&nbsp;&nbsp;<label for="allowcontrol_'+fieldObj.name+'">值限制：</label>';
        divhtml += '&nbsp;&nbsp;<input type="button" name="btndefaultlimit" target="'+fieldObj.name+'" value="恢复默认" />';
        divhtml += '</div>';
        return divhtml;
    };
	this.render = function(){
		// 请求HTML
		$.ajax({type: "GET", url: config.sitePath+"parts/weibo_config_part_authconfig.html", async:false,  // 同步请求
			dataType: "html", 
			success: function(data){
				$(_this.targetID).append(data);
                var trhtml = '';
                $.each([{"name":"isNewPro","label":"是否为新品","widget_type":"checkbox"},
                    {"name":"isFavorite","label":"收藏","widget_type":"checkbox"},
                    {"name":"isAttention","label":"关注","widget_type":"checkbox"},
                    ], function(i, item){
                        trhtml += _this.checkboxFieldHtml(item);
                });
                $("#islimit_haspicture").after(trhtml);
                var trhtml = '';
                $.each([
                    {"name":"proClassify","label":"详细商品分类", "widget_type":"input"},
                    {"name":"promotionInfos","label":"促销信息", "widget_type":"input"},
                    {"name":"productFullName","label":"产品全名", "widget_type":"input"},
                    {"name":"productColor","label":"产品颜色", "widget_type":"input"},
                    {"name":"productSize","label":"产品尺寸", "widget_type":"input"},
                    {"name":"productDesc","label":"产品描述", "widget_type":"input"},
                    {"name":"productComb","label":"产品组合", "widget_type":"input"},
                    {"name":"detailParam","label":"规格参数", "widget_type":"input"},
                    {"name":"compName","label":"公司名称", "widget_type":"input"},
                    {"name":"compAddress","label":"公司地址", "widget_type":"input"},
                    {"name":"phoneNum","label":"公司电话", "widget_type":"input"},
                    {"name":"compURL","label":"公司URL", "widget_type":"input"},
                    {"name":"serviceProm","label":"服务承诺", "widget_type":"input"},
                    {"name":"logisticsInfo","label":"物流", "widget_type":"input"},
                    {"name":"payMethod","label":"支付方式", "widget_type":"input"},
                    {"name":"apdComment","label":"追评内容", "widget_type":"input"},
                    {"name":"serviceComment","label":"对服务的评论", "widget_type":"input"},
                    ], function(i, item){
                        trhtml += _this.inputFieldHtml(item);
                });
                $("#commentTags_div").after(trhtml);
                var trhtml = '';
                $.each([
                    {"name":"proOriPrice","label":"原价", "widget_type":"range"},
                    {"name":"proCurPrice","label":"现价", "widget_type":"range"},
                    {"name":"proPriPrice","label":"促销价", "widget_type":"range"},
                    {"name":"stockNum","label":"库存", "widget_type":"range"},
                    {"name":"salesNumMonth","label":"月成交量", "widget_type":"range"},
                    {"name":"operateTime","label":"开店时长", "widget_type":"range"},
                    {"name":"compDesMatch","label":"对公司总体打分", "widget_type":"range"},
                    {"name":"logisticsScore","label":"对公司物流打分", "widget_type":"range"},
                    {"name":"serviceScore","label":"对公司服务打分", "widget_type":"range"},
                    ], function(i,item){
                        trhtml += _this.rangeFieldHtml(item);
                });
                $("#cmtStarLevel_div").after(trhtml);

				_this.init();
			}});
	}
}
