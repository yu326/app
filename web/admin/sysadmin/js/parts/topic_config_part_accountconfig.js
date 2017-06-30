function TopicConfigPartAccountConfig(datajson,target){
	var _this = this;
	this.targetID = target;
	this.dataJson = datajson;
	this.init = function(){
		var paramsjson = _this.dataJson;
		$.each(["areauser", "areamentioned", "ancestor_areamentioned", "emoAreamentioned", "ancestor_emoAreamentioned", "business", "ancestor_business", "emoBusiness", "ancestor_emoBusiness", "verified", "source_host"], function(k, item){
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

		$("#clearfacetlimit").bind("click", function(){
			$("#selectedfacetlimit").empty();
		});
		//facet字段
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
					var labelname = "";
					if(latestjson.filter[item.value]!=undefined){
						labelname = latestjson.filter[item.value].label
					}
					else{
						labelname = getDisplayName(item.value);
					}
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
					/*
					if($(this).attr("emotype") != undefined){
						tempObj["emotype"] = $(this).attr("emotype");
					}
					*/
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
							/*
							if(m.emotype != undefined){
								emocode = "emotype='"+m.emotype+"'";
							}
							*/
							sareas += "<span class='fixedselwordsbox'><span code=" + m.code + " "+emocode+" class='"+item+"item'>" + m.name + "</span><a name='cancleselectedvaluetextobj' value='"+item+"' class='cancleitem'>×</a></span>";
						});
						$("#selected"+item+"").append(sareas);
					}
				}, sltVal, fieldtxt, isemo, undefined, false, undefined, undefined,undefined, true);
		});
		//点击@用户
		$.each(["account","ancestor_account", "emoAccount", "ancestor_emoAccount", "oristatus_userid", "repost_userid", "userid"], function(i, item){
			$("#set"+item+"").bind("click", function(){
				var isemo = false;
				if(item == "emoAccount" || item == "ancestor_emoAccount"){
					isemo = true;
				}

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
						$("#islimit_"+item+"code").val(accountcode);
						$("#selected"+item+"").empty().append(accounthtml);
					}
					else{
						$("#islimit_"+item+"code").val("");
						$("#selected"+item+"").empty();
					}
				}, sltVal, [], isemo);
			});

		});
		//作者推荐
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

		$.each(["searchword","pg_text","ancestor_text", "verifiedreason","source",  "hostdomain", "ancestor_host_domain","description", "weibotopic","post_title","productType","impress","commentTags","proClassify","promotionInfos","productFullName","productColor","productSize","productDesc","productComb", "detailParam","compName","compAddress","phoneNum","compURL","serviceProm","logisticsInfo","payMethod","apdComment","serviceComment","column","column1", "ancestor_wb_topic", "organization","ancestor_organization", "NRN","ancestor_NRN", "weibotopickeyword", "ancestor_wb_topic_keyword"], function(i, item){
			_this.keywordRecommend(item, myKeywordRecommend);
		});
		$.each(["emotion", "ancestor_emotion", "emoCombin", "ancestor_emoCombin","emoNRN","ancestor_emoNRN","emoOrganization", "ancestor_emoOrganization", "emoTopic", "ancestor_emoTopic", "emoTopicKeyword", "ancestor_emoTopicKeyword", "emoTopicCombinWord", "ancestor_emoTopicCombinWord", "topic", "ancestor_combinWord", "weibotopiccombinword", "ancestor_wb_topic_combinWord"], function(i, item){
			_this.keywordRecommend(item, myTopicRecommend);
		});
		$.each(["searchword","pg_text", "ancestor_text", "verifiedreason", "source",  "hostdomain", "ancestor_host_domain","description", "weibotopic","post_title","productType","impress","commentTags","proClassify","promotionInfos","productFullName","productColor","productSize","productDesc","productComb", "detailParam","compName","compAddress","phoneNum","compURL","serviceProm","logisticsInfo","payMethod","apdComment","serviceComment","column","column1", "ancestor_wb_topic", "organization","ancestor_organization", "NRN", "ancestor_NRN", "weibotopickeyword", "ancestor_wb_topic_keyword"], function(i, item){
			_this.keywordAdd(item);
		});

		$.each(["emotion", "ancestor_emotion", "emoCombin", "ancestor_emoCombin", "emoNRN", "ancestor_emoNRN", "emoOrganization", "ancestor_emoOrganization", "emoTopic", "ancestor_emoTopic", "emoTopicKeyword", "ancestor_emoTopicKeyword", "emoTopicCombinWord", "ancestor_emoTopicCombinWord"], function(k, item){
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
		$.each(["topic", "ancestor_combinWord", "weibotopiccombinword", "ancestor_wb_topic_combinWord"], function(i, item){
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
		$("input:checkbox[id$=_nolimit]").bind("click",function(){
			var flname = $(this).attr('id').split('_nolimit');
			flname = flname[0];
			if($(this).prop("checked")){
				$("#maxlimitlength_"+flname).attr("disabled","disabled");
			}
			else{
				$("#maxlimitlength_"+flname).removeAttr("disabled");
			}
		});
		$("input:checkbox[id^=nolimitcontrol_]").bind("click",function(){
			var flname = $(this).attr('id').split('nolimitcontrol_');
			flname = flname[1];
			if($(this).prop("checked")){
				$("#limitcontrol_"+flname).attr("disabled","disabled");
			}
			else{
				$("#limitcontrol_"+flname).removeAttr("disabled");
			}
		});

		//时间
		/*
		 $('#createdtime_minvalue').bind("click", function() {
             WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'});
             });
		 $('#createdtime_maxvalue').bind("click", function() {                    
             WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'});
         });
		 */
		timeDisplayHtml("topic_accountconfig");

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
				case "weibourl":
				case "original_url":
				case "repost_url":
				case "oristatusurl":
				case "oristatus_username":
				case "repost_username":
				case "searchword":
				case "pg_text":
				case "ancestor_text":
				case "verifiedreason":
				case "source":
				case "hostdomain":
				case "ancestor_host_domain":
				case "description":
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
				case "weibotopickeyword":
				case "ancestor_wb_topic_keyword":
				case "organization":
				case "ancestor_organization":
				case "NRN":
				case "ancestor_NRN":
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
							strhtml += "<span class='selwordsbox'><span limit_type='"+paramsjson.filter[item].limit[i].type+"' limit_repeat='"+paramsjson.filter[item].limit[i].repeat+"' class='useritem'>"+paramsjson.filter[item].limit[i].value+"</span>("+repeathtml+")<a class='useritem_a' name='cancleselectedvaluetextobj' >×</a></span>";
						}
						$(target).append(strhtml);
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
							strhtml += "<span class='selwordsbox'><span limit_type='"+paramsjson.filter[item].limit[i].type+"' limit_repeat='"+paramsjson.filter[item].limit[i].repeat+"' class='useritem'>"+paramsjson.filter[item].limit[i].value+"</span>("+repeathtml+")<a class='useritem_a' name='cancleselectedvaluetextobj' >×</a></span>";
						}
						$(target).html(strhtml);
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
					var strhtml="";
					var strcode="";
					//初始化限制的值
					if(paramsjson.filter[item].limit.length>0) {
						var len = paramsjson.filter[item].limit.length;
						for(var i=0;i<len;i++)
						{
							var comma = ",";
							if(i == len-1){
								comma = "";
							}
							strcode += paramsjson.filter[item].limit[i].value.value+comma;
							strhtml += "<span class='selwordsbox'><span class='"+item+"item' code='"+paramsjson.filter[item].limit[i].value.value+"'>"+paramsjson.filter[item].limit[i].value.text+"</span><a name='cancleselectedvaluetextobj' class='cancleitem'>×</a></span>";	
						}
						$("#selected"+item).html(strhtml);
						$("#islimit_"+item+"code").val(strcode);
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
					break;
				case "verified":
				case "verified_type":
				case "sourceid":
				case "source_host":
					var htmlitem = item;
					if(item == "verified_type"){
						htmlitem = "verified";
					}
					if(item == "sourceid"){
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
								name = getSourceName(litem.value);
								code = getSourceUrl(litem.value);
							}
							else if(item == "source_host"){
								name = getSourceHostName(litem.value);
								code = litem.value;
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
					break;
				case "sex":
				case "haspicture":
				case "recommended":
                case "isNewPro":
                case "isFavorite":
                case "isAttention":
				case "weibotype":
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
				case "createdtime":
					if(paramsjson.filter[item].limit.length > 0){
						var minv = paramsjson.filter[item].limit[0].value.minvalue != null ? formatTime('yyyy-MM-dd hh:mm:ss', paramsjson.filter[item].limit[0].value.minvalue) : "";
						var maxv = paramsjson.filter[item].limit[0].value.maxvalue != null ? formatTime('yyyy-MM-dd hh:mm:ss', paramsjson.filter[item].limit[0].value.maxvalue) : "";
						$("#topic_accountconfig_facet_range_createdtime_start").val(minv);
						$("#topic_accountconfig_facet_range_createdtime_end").val(maxv);
						if(minv == "" && maxv == ""){
							$("#topic_accountconfig_facet_range_createdtime_all").attr("checked", true);
						}
						else{
							setTimeSelectMsg("topic_accountconfig_facet_range", minv, maxv, true); 
						}
						changeTimeBorderColor("topic_accountconfig_facet_range_html", "createdtime"); 
						$("#topic_accountconfig_facet_range_html").attr("checkedtimetype", "createdtime");
					}
					else{
						$("#topic_accountconfig_facet_range_createdtime_all").attr("checked", true);
					}
					break;
				case "nearlytime":
					if(paramsjson.filter[item].limit.length > 0 && paramsjson.filter[item].limit[0].value.start!=undefined){
						var facet_range_prefix = "topic_accountconfig_facet_range";
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
						changeTimeBorderColor("topic_accountconfig_facet_range_html", "nearlytime"); 
						$("#topic_accountconfig_facet_range_html").attr("checkedtimetype", "nearlytime");
					}
					break;
				case "beforetime":
					if(paramsjson.filter[item].limit.length > 0 && paramsjson.filter[item].limit[0].value.start != undefined){
						var facet_range_prefix = "topic_accountconfig_facet_range";
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
						changeTimeBorderColor("topic_accountconfig_facet_range_html", "beforetime"); 
						$("#topic_accountconfig_facet_range_html").attr("checkedtimetype", "beforetime");
					}
					break;
				case "untiltime":
					if(paramsjson.filter[item].limit.length > 0 && paramsjson.filter[item].limit[0].value.start != undefined){
						var facet_range_prefix = "topic_accountconfig_facet_range";
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
						changeTimeBorderColor("topic_accountconfig_facet_range_html", "untiltime"); 
						$("#topic_accountconfig_facet_range_html").attr("checkedtimetype", "untiltime");
					}
					break;
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
		initUpdateSnapshotLimit(paramsjson);//初始化定时更新快照权限
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
				var labelname = "";
				if(paramsjson.filter[item.value]!=undefined){
					labelname = paramsjson.filter[item.value].label
				}
				else{
					labelname = getDisplayName(item.value);
				}
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
	this.keywordRecommend = function(field, recommendFun){
		var isemo = "";
		if(field.indexOf("emo") > -1){
			isemo = true;
		}
		var fieldname = getFacetname(field);
		var objJson = _this.dataJson;
		var fieldtext = objJson.filter[field].label;
		$("input[name=commend"+field+"]").bind("click", function() {
			var target = $(this).attr("target");
			var txttarget = "#txt"+target;
			var searchword = commonFun.trim($(txttarget).val());
			if (searchword != "") {
				recommendFun(searchword, function(data){
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
		var timetype = $("#topic_accountconfig_facet_range_html").attr("checkedtimetype"); //最后一次选择的时间控件
		var paramsjson = _this.dataJson;
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
				case "original_url":
				case "oristatusurl":
				case "repost_url":
				case "oristatus_username":
				case "repost_username":
				case "searchword":
				case "pg_text":
				case "ancestor_text":
				case "verifiedreason":
				case "source":
				case "hostdomain":
				case "ancestor_host_domain":
				case "description":
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
				case "weibotopickeyword":
				case "ancestor_wb_topic_keyword":
				case "organization":
				case "ancestor_organization":
				case "NRN":
				case "ancestor_NRN":
					var htmlitem = item;
					target = "#end"+htmlitem;
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
						$("#selected"+item+" ."+item+"item").each(function(si, sitem){
							var t_v = {value:$(sitem).attr("code"), text:$(sitem).text()};
							paramsjson.filter[item].limit.push( {value:t_v, type:'exact', repeat:1} );
						});
					}
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
				case "sex":
				case "haspicture":
				case "recommended":
                case "isNewPro":
                case "isFavorite":
                case "isAttention":
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
				case "weibotype":
				//case "sourceid":
					$("input[name=islimit_"+item+"]:checked").each(function(){
						paramsjson.filter[item].limit.push( {value:parseInt($(this).val(),10), type:'exact', repeat:1} );
					});
					break;
				case "createdtime":
					var allchk = $("#topic_accountconfig_facet_range_createdtime_all").prop("checked");
					if(timetype == "createdtime" && !allchk){
						var cts = $("#topic_accountconfig_facet_range_createdtime_start").val();
						var cte = $("#topic_accountconfig_facet_range_createdtime_end").val();
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
						var timevalue = getnearlytimeValue("topic_accountconfig_facet_range");
						var l_v = {value:timevalue, type:'time_dynamic_state', repeat:1, name:"nearlytime"};
						paramsjson.filter[item].limit.push(l_v);
					}
					break;
				case "beforetime":
					if(timetype == "beforetime"){
						var timevalue = getbeforeTimeValue("topic_accountconfig_facet_range");
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
						var timevalue = getuntiltimeValue("topic_accountconfig_facet_range");
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
			if(!checkLimitlength(paramsjson.filter[item], paramsjson.filter[item].limit)){
				alert(paramsjson.filter[alertitem].label+"值个数超出最大值");
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


		//处理outputcount
		item = "outputcount";
		paramsjson.output.countlimit.allowcontrol = parseInt($("#allowcontrol_"+item).val(),10);
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
		limitcon = $("#nolimitcontrol_"+item);
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
    this.checkboxFieldHtml = function(fieldObj){
        var divhtml = '';
        divhtml += '<div class="search" id="islimit_'+fieldObj.name+'"> <span class="rightalign">'+fieldObj.label+'限制：</span>';
        divhtml += '<input type="checkbox" name="islimit_'+fieldObj.name+'" id="islimit_'+fieldObj.name+'1" value="1" /><label for="islimit_'+fieldObj.name+'1">是</label>';
        divhtml += '<input type="checkbox" name="islimit_'+fieldObj.name+'" id="islimit_'+fieldObj.name+'1" value="0" /><label for="islimit_'+fieldObj.name+'0">否</label>';
        divhtml += '&nbsp;&nbsp;&nbsp;&nbsp;修改次数：<input type="text" id="limitcontrol_'+fieldObj.name+'" class="shortestinput" />';
        divhtml += '<input type="checkbox" id="nolimitcontrol_'+fieldObj.name+'" name="nolimitcontrol_'+fieldObj.name+'" class="outborder"/><label for="nolimitcontrol_'+fieldObj.name+'">不限</label>';
        divhtml += '&nbsp;&nbsp;<label for="allowcontrol_'+fieldObj.name+'">值：</label>';
        divhtml += '</div>';
        return divhtml;
    };
    this.inputFieldHtml = function(fieldObj){
        var divhtml = '';
        divhtml += '<div class="search"><span class="rightalign">'+fieldObj.label+'限制：</span>';
        divhtml += '&nbsp;&nbsp;<input class="shortinput" type="text" id="txt'+fieldObj.name+'" name="'+fieldObj.name+'" />';
        divhtml += '<input name="commend'+fieldObj.name+'" target="'+fieldObj.name+'" type="button" value="推荐"/> ';
        divhtml += '<input type="button" id="btnadd'+fieldObj.name+'" name="add'+fieldObj.name+'" target="'+fieldObj.name+'" value="添加限制" />';
        divhtml += '&nbsp;&nbsp;值个数：<input type="text" id="maxlimitlength_'+fieldObj.name+'" class="shortestinput" />';
        divhtml += '<input type="checkbox" id="'+fieldObj.name+'_nolimit" name="'+fieldObj.name+'_nolimit" class="outborder"/><label for="'+fieldObj.name+'_nolimit">不限</label>';
        divhtml += '&nbsp;&nbsp;&nbsp;&nbsp;修改次数：<input type="text" id="limitcontrol_'+fieldObj.name+'" class="shortestinput" />';
        divhtml += '<input type="checkbox" id="nolimitcontrol_'+fieldObj.name+'" name="nolimitcontrol_'+fieldObj.name+'" class="outborder"/><label for="nolimitcontrol_'+fieldObj.name+'">不限</label>';
        divhtml += '&nbsp;&nbsp;<label for="allowcontrol_'+fieldObj.name+'">值：</label>';
        divhtml += '</div>';
        return divhtml;
    };
    this.rangeFieldHtml = function(fieldObj){
        var divhtml = '';
        divhtml += '<div class="search" id="islimit_'+fieldObj.name+'"> <span class="rightalign">'+fieldObj.label+'：</span>';
        divhtml += '&nbsp;&nbsp;最小值：<input type="text" class="shortestinput"  name="'+fieldObj.name+'_minvalue" id="'+fieldObj.name+'_minvalue" value=""  />';
        divhtml += '&nbsp;&nbsp;最大值：<input type="text" class="shortestinput" name="'+fieldObj.name+'_maxvalue" id="'+fieldObj.name+'_maxvalue" value="" />';
        divhtml += '&nbsp;&nbsp;&nbsp;&nbsp;修改次数：<input type="text" id="limitcontrol_'+fieldObj.name+'" class="shortestinput" />';
        divhtml += '<input type="checkbox" id="nolimitcontrol_'+fieldObj.name+'" name="nolimitcontrol_'+fieldObj.name+'" class="outborder"/><label for="nolimitcontrol_'+fieldObj.name+'">不限</label>';
        divhtml += '&nbsp;&nbsp;<label for="allowcontrol_'+fieldObj.name+'">值：</label>';
        divhtml += '</div>';
        return divhtml;
    };
	this.render = function(){
		// 请求HTML
		$.ajax({type: "GET", url: config.sitePath+"parts/topic_config_part_accountconfig.html", async:false,  // 同步请求
			dataType: "html", 
			success: function(data){
				$(_this.targetID).html(data);
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
                    ], function(i, item){
                            trhtml += _this.rangeFieldHtml(item);
                });
                $("#islimit_cmtStarLevel").after(trhtml);
				_this.init();
			}
		});
	}
}
/**
 * 取消按钮事件，elm触发事件的控件，filter对应json中的filter的属性名称，opt操作类型（end表示取消的“限制”，default表示取消的“默认值”）
 */
TopicConfigPartAccountConfig.prototype.cancleUser = function(elm){
	$(elm).parent().remove();
	/*if(opt == "end"){
	  var len = $("#end"+filter+" .useritem").length;
	  if(len == 0){
	  $("#"+filter+"_nolimit").attr("checked",true);
	  }
	  }*/
};
TopicConfigPartAccountConfig.prototype.cancleArea= function(elm,filtername){
	var parentid = $(elm).parent().attr("id"); //先获取父ID再删除本身
	$(elm).prev().remove();
	$(elm).remove();

	var strValue="";
	$("#"+parentid).find("span").each(function(){
		strValue+=$(this).attr("code")+",";
	});
	strValue = strValue.substring(0,strValue.length-1);
	$("#islimit_"+filtername+"code").val(strValue);
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
