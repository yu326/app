
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
 * */

function myCommonSelect2(choiceValue, successResult, selectedVal, showName, isemo, ele, isnot, pname, needcheckchild,count){
	var page; 
	var pagesize = 20;
	var curpage2;
	var haschild = false;
	var choiceVal = choiceValue;
	var parentArr = [];
	var goparent = false;
	var successFun;
	var emochk;
	var fieldflag; //字段标识, 查询的字段标识,再判断子节点时用到 isChild
	var initCommonSelect = function(){
		if(showName == undefined){
			showName = "";
		}
		$("#choicebox"+count+"").remove();
		var commonhtml ="<div id='planParent"+count+"'>"
		commonhtml += "<div style='vertical-align:middle;height:30'><input type='radio' id='plan1"+count+"' value='1' name='radio_plan"+count+"' ><label for='plan1"+count+"'>默认用户词典</label>";
		commonhtml +="<input type='radio' id='plan2"+count+"' value='0' name='radio_plan"+count+"' checked='checked'><label for='plan2"+count+"'>定制用户词典</label>"; 
		commonhtml +="<a id='delete"+count+"' href='javascript:void(0);' style='color:blue;margin-left:260px' align='right'>删除</a></div>";
		commonhtml +="<div id='planChild"+count+"'>";
		commonhtml += "<fieldset>"; 
		commonhtml +="<legend>"+showName+"列表</legend>";
		commonhtml +="<div id='groupcommon"+count+"' index='"+count+"'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg"+count+"'/></div>";
		commonhtml +="<div id='commonpage"+count+"' index='"+count+"' style='margin-top:2px;float:left;height:100%,width:100%;margin:5px;'></div>";
		commonhtml +="</fieldset>";
		commonhtml += "<fieldset>"; 
		commonhtml +="<legend>已选择的"+showName+"</legend>";
		commonhtml +="<div id='selectedcommons"+count+"' index='"+count+"' select='0'></div>";
		commonhtml +="</fieldset></DIV></div>";
		//

		var commonboxhtml = '<div id="choicebox'+count+'" title="'+showName+'列表" style="position:absolute;display:none;z-index:9999;background-color:white;border:1px solid blue;font-size: 12px; width:490px;"></div>';
		if(ele != undefined){
			$("body").append(commonboxhtml);

			$("#choicebox"+count+"").append(commonhtml);
		}
		else{
		//if($("#once").length==0)
			$('#accordion').append('<h3 id="head'+count+'"><a href="#">分词方案'+count+'</a></h3><div id="planDiv_'+count+'"></div>');
			$('#accordion').accordion('destroy');
			$('#accordion').accordion({ clearStyle: true, active : 0, autoHeight: false,
                collapsible: true});
			//$('#accordion').accordion();
			//$("#accordion div").css('height', 'auto');
			var active = count > 1 ? count -1 : 0;
			//$('#accordion').accordion( "option", "active", active);
			$("#planDiv_"+count).append(commonhtml);
			
			//绑定radio
			$("input[name=radio_plan"+count+"]").bind("click",function(){
				if($(this).val()==1){
					//$('#accordion').accordion("destroy");
					//$("#planDiv_1").remove();
					//$(".selector" ).accordion( "destroy" );
					//隐藏类别选择控件
					$("#planChild"+count+"").css("display", "none");
					//设置是否选择默认
					$("#selectedcommons"+count+"").attr("select","1");
				}
				else{
					//$("#accordion").show();
					$("#selectedcommons"+count+"").attr("select","0");
					$("#planChild"+count+"").css("display", "");
				}
			});
			//绑定删除按钮
			$("#delete"+count).bind("click",function(){
				$("#planDiv_"+count).remove();
				$("#head"+count).remove();
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
			searchCommon(pagesize, 1, "#groupcommon"+count+""); //同时为分页回调函数
		}
		if(ele != undefined){
			var ex,ey;
			ex = $(ele).offset().left + 130;
			if(ex > ($(document.body).width() - $("#choicebox"+count+"").width())){
				ex = $(document.body).width() - $("#choicebox"+count+"").width() -10;
			}
			ey = $(ele).offset().top/* - $("#commonemotiondiv"+count+").height()*/;
			//$("#choicebox").css({top:ey+"px", left:ex+"px", display:"block"});
			$("#choicebox"+count+"").slideDown("slow").css({
				'display':'block',
				'left': ex+"px",
				'top': ey+"px"
			});
		}
		else{
			$("#choicebox"+count+"").dialog("open");
		}

		$("#commonsubmit").unbind("click");
		$("#commonsubmit").bind("click", function(){
			var resultArr = [];
			/*
			$("#selectedcommons .sltitem").each(function(i, m){
				var itemObj = {};
				itemObj.code = $(this).attr("code");
				itemObj.name = $(this).text();
				if($(this).attr("emotype") != undefined){
					itemObj.emotype = $(this).attr("emotype");
				}
				resultArr.push(itemObj);
			});
			*/
			resultArr = getSelectedVal();
			successResult(resultArr);
			$("#commonemotiondiv").css("display", "none");
			if(ele != undefined){
				//$("#choicebox").css("display", "none");
				$("#choicebox"+count+"").slideUp("slow");
			}	
			else{
				$("#choicebox"+count+"").dialog("close");
			}
		});
		$("#canclesubmit").bind("click", function(){
			$("#commonemotiondiv"+count+"").css("display", "none");
			$("#selectedcommons"+count+"").empty();
			if(ele != undefined){
				//$("#choicebox").css("display", "none");
				$("#choicebox"+count+"").slideUp("slow");
			}
			else{
				$("#choicebox"+count+"").dialog("close");
			}
		});
	};
	this.cancleItem = function(ele){
		//获取index
		var count=$(ele).closest("div").attr("index");
		$(ele).parent().remove();
		var delcode = $(ele).attr("code");
		var delemo = $(ele).attr("emotype");
		if(delemo != undefined && !isemo){
			$("#groupcommon"+count+" a[code='"+delcode+"'][emotype='"+delemo+"']").removeClass().addClass("notselected");
			//改变父项的选择状态
			deleteParent(delcode, delemo); 
		}
		else{
			$("#groupcommon"+count+" a[code='"+delcode+"']").removeClass().addClass("notselected");
			//改变父项的选择状态
			deleteParent(delcode); 
		}
	};
	//递归删除
	var deleteParent = function(delcode, delemo){
		//子项全部删除后,改变父项选中状态
			//获取index
		//var count=$(this).closest("div").attr("index");
		var parcode = getParent(delcode);
		var childflag = true;
		$("#selectedcommons"+count+" .sltitem").each(function(i, m){
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
			$("#groupcommon"+count+" a[code='"+parcode+"']").removeClass().addClass("selected");
		}
		else if(childflag == 2){
			$("#groupcommon"+count+" a[code='"+parcode+"']").removeClass().addClass("halfselected");
		}
		else if(childflag == 3){ //没有子项时
			if(delemo!=undefined && !isemo){
				$("#groupcommon"+count+" a[code='"+parcode+"'][emotype="+delemo+"]").removeClass().addClass("notselected");
				var pclass = $("#groupcommon"+count+" a[code='"+parcode+"'][emotype="+delemo+"]").hasClass("notselected");
			}
			else{
				$("#groupcommon"+count+" a[code='"+parcode+"']").removeClass().addClass("notselected");
				var pclass = $("#groupcommon"+count+" a[code='"+parcode+"']").hasClass("notselected");
			}
			if(fieldflag == "area" && parcode != "CN" && !pclass){
				deleteParent(parcode, delemo);
			}
		}
	}
	this.getSelectedVal = function(){
		var resultArr = [];
		
		$("#selectedcommons"+count+" .sltitem").each(function(i, m){
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
		var count2=$(ele).closest("div").attr("index");
		//非用户点击时此处为undefined
		if(count2!=undefined)
			count=count2;
		selectedVal = getSelectedVal();
		
		//var elename = $(ele).text();
		var elename = $(ele).attr("name");
		var elecode = $(ele).attr("code");
		var eleemo = $(ele).attr("emotype");
		var hc = $(ele).attr("haschild");
		if(hc != "false"){  //当有子节点时再次弹出层
			/*
			var randid = Math.round(Math.random()*100000);
			var childdivid = "childcommon"+randid;
			var childbox = "<div id="+ childdivid +"></div>";
			if($("#"+childdivid).length == 0){
				$(childbox).insertAfter("body");
				$("#"+childdivid).dialog({
					autoOpen:false,
					width:550,
					modal:true,
					close:function(){ //关闭弹出层,对应的父id数组减一
						parentArr.splice(parentArr.length-1, 1);
					}
				});
			}
			searchCommon(pagesize, 1, "#"+childdivid);
			$("#"+childdivid).dialog("open");
			*/
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
			searchCommon(pagesize, 1, "#groupcommon"+count+"");

			//更改样式
			$(ele).removeClass().addClass("halfselected");
		}
		else{
			if(isemo!=undefined && (isemo == true || isemo == "true")){  //需要选择情感
				myCommonEmotionChk("", ele, function(data){
					var n = elename += data.name;
					addItem(ele, n, elecode, data.code);
					$("#commonemotiondiv"+count+"").css({display:"none"});
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
			$("#groupcommon"+count+" a[code='"+pcode+"']").removeClass().addClass("halfselected");
		}
		var wflag = true;
		$("#selectedcommons"+count+" .sltitem").each(function(i, m){
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
			/*
			var emocode = "";
			if(emotype){
				emocode = "emotype='"+emotype+"'";
			}
			*/
			// style='border:1px solid #cccccc;padding:1.5px;color:red; cursor:pointer;'
			var spanutilhtml = createUtilSpanHtml('sltitem', newname, newcode, isnot, undefined, 'cancleItem', undefined, emotype);
			$("#selectedcommons"+count+"").append(spanutilhtml);
		}
	}
	//已选择的项初始化
	var selectedCommon = function(){
		
		var slthtml = "";
		if(isnot == undefined){
			isnot = true;
		}
		//如果传过来的是default
		if(selectedVal!=undefined&&selectedVal.length==0){
			//选中默认按钮
			$("#plan1"+count).attr("checked",'checked');
			//隐藏类别选择控件
			$("#planChild"+count+"").css("display", "none");
			//设置是否选择默认
			$("#selectedcommons"+count+"").attr("select","1")
		}else{
			$.each(selectedVal, function(i,m){
				slthtml += createUtilSpanHtml('sltitem', m.name, m.code, isnot, m.exclude, 'cancleItem', undefined, m.emotype);
				//slthtml += "<span class='selwordsbox' ><span class='sltitem' style='cursor:pointer;' "+emotype+" code= "+m.code+">"+m.name+"</span><a class='cancleitem' code="+m.code+" onclick='cancleItem(this)'>×</a></span>";
			});
			$("#selectedcommons"+count+"").append(slthtml);
		}
		$("#planDiv_"+count).css("min-height","140px;");
	}
	var searchCommon = function(pagesize, curpage, selector){
	
		if(!isArray(choiceVal)){
			var searchUrl = "";
			switch(choiceVal){
				//新增字典类别
				case "dictionaryParentUrl":
					searchUrl =config.modelUrl + "dictionary_category_model.php?type=select_dictionary_category&parent_id=-1";
					haschild = "dictionaryChildUrl"; 
					fieldflag = "dictionary";
					break;
				case "dictionaryChildUrl":
				var countrycode = parentArr[parentArr.length-1].code;
					searchUrl = config.modelUrl + "dictionary_category_model.php?type=select_dictionary_category&disable_state=1&parent_id="+countrycode;
					haschild = false; 
					fieldflag = "dictionary";
					break;
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
					searchUrl = config.solrData+ "?type=kwblur&fieldname=users_screen_name&keyword="+encodeURIComponent(username)+"&page="+_this.curpage2+"&pagesize="+pagesize;
					//searchUrl = config.dataUrl + "?type=searchname&blurname="+encodeURIComponent(username)+"&page="+_this.curpage2+"&pagesize="+pagesize;
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
				default:
					searchUrl = choiceVal;
					haschild = false; 
					break;
			}

			if(curpage == '' || curpage == null || curpage == undefined ){
				curpage2 = 1;
			}
			else{
				curpage2 = curpage;
			}
			//动态数据
			ajaxRequest(searchUrl , function(data1){
				var data=[];
				var data2 = {};
				data2["totalcount"]=data1.totalcount;
				data2["datalist"]=[];
				if(data1.datalist!=undefined){
					$.each(data1.datalist,function(index,item){
						var temp={};
						//
						
						temp.code=item.id;
						if(item.parent_id==-1)
						{
							temp.name=item.parent_name;
							temp.haschild="dictionaryChildUrl";
						}else{
							temp.haschild=false;
							temp.name=item.category_name;
						}
						data2["datalist"].push(temp);
					});
					data.push(data2);
				}
				if(data!=null && data2["totalcount"]!=0&&data.length> 0 && data[0].datalist!=null && data[0].datalist.length>0) {
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
						//$(selector).text("查询数据不存在！");
						$("#waitimg"+count+"").css({display:"none"});
					}
				}
			}, "json", function(){}, function(){ 
				$(selector).html("<img src='"+config.imagePath+"wait.gif'  style='padding:10px;padding-left:215px;' id='waitimg"+count+"'/>");
			}, function(){$("#waitimg"+count+"").css({display:"none"});});
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
			acchtml += "<a "+slco+" onclick='sendItem(this, event)' haschild='"+haschild+"' "+emotypeattr+" code="+m.code+" "+urlstyleattr+" name="+m.name+">"+dname+"</a>";
		});
		$(selector).empty().append(acchtml);
		$("#waitimg"+count+"").css({display:"none"});
		//显示分页
		if(totalcount != undefined && totalcount > pagesize){
			pageDisplay(totalcount, searchCommon, "commonpage"+count+"", pagesize, curpage2);
		}
	}
	var getParent = function(code){
		//获取父节点
		var pid=-1;
		var searchUrl = config.modelUrl + "dictionary_category_model.php?type=select_dictionary_category_all&id="+code+"&page=1&pagesize=10";
		
		$.ajax({
		url:searchUrl,
		type:"GET",
		dataType:"json",
		async:false,
		beforeSend : function() {
		//	$("#waitimg").parents("tr").first().remove();
		//	$("#dictionaryinfo").append("<tr><td colspan='7'><img src='"+config.imagePath+"wait.gif'  style='padding:10px;' id='waitimg'/></td></tr>");
		},
		complete:function(XMLHttpRequest, textStatus) {
		//	$("#waitimg").parents("tr").first().remove();
		},
		success:function(data){
			if (data.totalcount > 0) {
			$.each(data.datalist, function (di, item) {
				pid=item.parent_id;
				 
			});
			}
		}
	});
		/*
		ajaxRequest(searchUrl, function (data) {
		if (data.errorcode != undefined) {
			alert(data.errormsg);
			return false;
		}
		if (data.totalcount > 0) {
			$.each(data.datalist, function (di, item) {
				//为父级下拉菜单添加选项
				
				pid=item.parent_id;
				 
			});
			//添加到方案数组
		}
	}, "json");
	
	*/
		return pid;
		
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
