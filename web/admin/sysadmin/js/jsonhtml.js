/**
 * 添加模型时，生成配置价格的html
 * @param jsonobj 模型json对象
 */
function createJsonPriceHtml(jsonobj){
	var html = '<table width="98%" border="0" cellspacing="0" cellpadding="0" class="list">';
	html += '<tr><th scope="col" style="width:26%">字段名</th>';
	html += '<th scope="col" style="width:12%">单值价</th>';
	html += '<th scope="col" style="width:12%">最高价</th>';
	html += '<th scope="col" style="width:12%">单次修改价</th>';
	html += '<th scope="col" style="width:12%">最高修改价</th>';
	html += '<th scope="col" style="width:14%">修改范围次数</th>';
	html += '<th scope="col" style="width:12%">最大值个数</th></tr>';
	html += '<tbody>';
	if(jsonobj.filter){
		for(var item in jsonobj.filter){
			if(jsonobj.filter[item].label != undefined){
				html += '<tr><td>'+jsonobj.filter[item].label+'</td>';
				html += '<td><input type="text" class="shortinput" id="unitprice_'+item+'" value="'+jsonobj.filter[item].unitprice+'" /></td>';
				html += '<td><input type="text" class="shortinput" id="maxprice_'+item+'" value="'+jsonobj.filter[item].maxprice+'" /></td>';
				html += '<td><input type="text" class="shortinput" id="onceeditprice_'+item+'" value="'+jsonobj.filter[item].onceeditprice+'" /></td>';
				html += '<td><input type="text" class="shortinput" id="maxeditprice_'+item+'" value="'+jsonobj.filter[item].maxeditprice+'" /></td>';
				html += '<td><input type="text" class="shortinput" id="limitcontrol_'+item+'" value="'+jsonobj.filter[item].limitcontrol+'" /></td>';
				html += '<td><input type="text" class="shortinput" id="maxlimitlength_'+item+'" value="'+jsonobj.filter[item].maxlimitlength+'" /></td></tr>';
			}
		}
	}
	if(typeof(jsonobj.facet) != "undefined" && jsonobj.facet != null){
		html += '<tr><td>分组查询</td>';
		html += '<td><input type="text" class="shortinput" id="unitprice_facet" value="'+jsonobj.facet.unitprice+'" /></td>';
		html += '<td><input type="text" class="shortinput" id="maxprice_facet" value="'+jsonobj.facet.maxprice+'" /></td>';
		html += '<td><input type="text" class="shortinput" id="onceeditprice_facet" value="'+jsonobj.facet.onceeditprice+'" /></td>';
		html += '<td><input type="text" class="shortinput" id="maxeditprice_facet" value="'+jsonobj.facet.maxeditprice+'" /></td>';
		html += '<td><input type="text" class="shortinput" id="limitcontrol_facet" value="'+jsonobj.facet.limitcontrol+'" /></td>';
		html += '<td><input type="text" class="shortinput" id="maxlimitlength_facet" value="'+jsonobj.facet.maxlimitlength+'" /></td>';
		html += '</tr>';
		if(typeof(jsonobj.facet.filterlimit) != "undefined" && jsonobj.facet.filterlimit != null){
			html += '<tr><td>输出过滤器</td>';
			html += '<td><input type="text" class="shortinput" id="unitprice_facetfilter" value="'+jsonobj.facet.filterlimit.unitprice+'" /></td>';
			html += '<td><input type="text" class="shortinput" id="maxprice_facetfilter" value="'+jsonobj.facet.filterlimit.maxprice+'" /></td>';
			html += '<td><input type="text" class="shortinput" id="onceeditprice_facetfilter" value="'+jsonobj.facet.filterlimit.onceeditprice+'" /></td>';
			html += '<td><input type="text" class="shortinput" id="maxeditprice_facetfilter" value="'+jsonobj.facet.filterlimit.maxeditprice+'" /></td>';
			html += '<td><input type="text" class="shortinput" id="limitcontrol_facetfilter" value="'+jsonobj.facet.filterlimit.limitcontrol+'" /></td>';
			html += '<td><input type="text" class="shortinput" id="maxlimitlength_facetfilter" value="'+jsonobj.facet.filterlimit.maxlimitlength+'" /></td>';
			html += '</tr>';
		}
	}
	
	html += '<tr><td>输出条件</td>';
	html += '<td><input type="text" class="shortinput" id="unitprice_output" value="'+jsonobj.output.unitprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="maxprice_output" value="'+jsonobj.output.maxprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="onceeditprice_output" value="'+jsonobj.output.onceeditprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="maxeditprice_output" value="'+jsonobj.output.maxeditprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="limitcontrol_output" value="'+jsonobj.output.limitcontrol+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="maxlimitlength_output" value="'+jsonobj.output.maxlimitlength+'" /></td>';
	html += '</tr>';
	html += '<tr><td>输出数据量</td>';
	html += '<td><input type="text" class="shortinput" id="unitprice_outputcount" value="'+jsonobj.output.countlimit.unitprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="maxprice_outputcount" value="'+jsonobj.output.countlimit.maxprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="onceeditprice_outputcount" value="'+jsonobj.output.countlimit.onceeditprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="maxeditprice_outputcount" value="'+jsonobj.output.countlimit.maxeditprice+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="limitcontrol_outputcount" value="'+jsonobj.output.countlimit.limitcontrol+'" /></td>';
	html += '<td><input type="text" class="shortinput" id="maxlimitlength_outputcount" value="'+jsonobj.output.countlimit.maxlimitlength+'" /></td>';
	html += '</tr>';
	html += '</tbody></table>';
	return html;
}

/**
 * 验证价格字段值  TODO 移动到html页
 */
function checkPriceValue(){
	var r = true;
	$("input[id^=unitprice_]").each(function(i,item){
		var reg = new RegExp(/^\d+$/g);
		if(!reg.test($(item).val())){
			alert('格式错误，请输入数字');
			$(item).focus();
			r = false;
			return false;
		}
		var itemid = $(item).attr("id");
		var itemidarr = itemid.split('_');
		if(itemidarr.length == 2){
			var maxv = $('#maxprice_'+itemidarr[1]);
			var reg1 = new RegExp(/^\d+$/g);
			if(!reg1.test(maxv.val())){
				alert('格式错误，请输入数字');
				maxv.focus();
				r = false;
				return false;
			}

			if(parseInt($(item).val(),10) > parseInt(maxv.val(),10)){
				alert('单值价不能大于最高价');
				$(item).focus();
				r = false;
				return false;
			}
		}
	});
	if(!r){
		return false;
	}
	$("input[id^=onceeditprice_]").each(function(i,item){
		var reg2 = new RegExp(/^\d+$/g);
		if(!reg2.test($(item).val())){
			alert('格式错误，请输入数字');
			$(item).focus();
			r = false;
			return false;
		}
		var itemid = $(item).attr("id");
		var itemidarr = itemid.split('_');
		if(itemidarr.length == 2){
			var maxv = $('#maxeditprice_'+itemidarr[1]);
			var reg3 = new RegExp(/^\d+$/g);
			if(!reg3.test(maxv.val())){
				alert('格式错误，请输入数字');
				maxv.focus();
				r = false;
				return false;
			}

			if(parseInt($(item).val(),10) > parseInt(maxv.val(),10)){
				alert('单次修改价不能大于最高修改价');
				$(item).focus();
				r = false;
				return false;
			}
		}
	});
	if(!r){
		return false;
	}
	$("input[id^=limitcontrol_]").each(function(i,item){
		var reg4 = new RegExp(/^(\d+|-1{1})$/g);
		if(!reg4.test($(item).val())){
			alert('格式错误，请输入-1或正整数');
			$(item).focus();
			r = false;
			return false;
		}
	});
	if(!r){
		return false;
	}
	$("input[id^=maxlimitlength_]").each(function(i,item){
		var reg5 = new RegExp(/^(\d+|-1{1})$/g);
		if(!reg5.test($(item).val())){
			alert('格式错误，请输入-1或正整数');
			$(item).focus();
			r = false;
			return false;
		}
	});
	return r;
}

/**
 * 从html中获取价格值 
 * @param jsonobj  模型json对象
 */
function getJsonPriceValue(jsonobj){
	for(var item in jsonobj.filter){
		var mpv = $('#maxprice_'+item);
		if(mpv.length > 0){
			jsonobj.filter[item].maxprice = parseInt(mpv.val(),10);
		}
		var upv = $('#unitprice_'+item);
		if(upv.length > 0){
			jsonobj.filter[item].unitprice = parseInt(upv.val(),10);
		}
		var mep = $("#maxeditprice_"+item);
		if(mep.length > 0){
			jsonobj.filter[item].maxeditprice = parseInt(mep.val(),10);
		}
		var oep = $("#onceeditprice_"+item);
		if(oep.length > 0){
			jsonobj.filter[item].onceeditprice = parseInt(oep.val(),10);
		}
		var limitc = $("#limitcontrol_"+item).val();
		jsonobj.filter[item].limitcontrol = parseInt(limitc,10);
		var maxlimit = $("#maxlimitlength_"+item).val();
		jsonobj.filter[item].maxlimitlength = parseInt(maxlimit,10);

	}
	if(jsonobj.facet != undefined && jsonobj.facet != null){
		jsonobj.facet.unitprice = parseInt($("#unitprice_facet").val(),10);
		jsonobj.facet.maxprice = parseInt($("#maxprice_facet").val(),10);
		jsonobj.facet.unitprice = parseInt($("#onceeditprice_facet").val(),10);
		jsonobj.facet.maxprice = parseInt($("#maxeditprice_facet").val(),10);
		jsonobj.facet.unitprice = parseInt($("#limitcontrol_facet").val(),10);
		jsonobj.facet.maxprice = parseInt($("#maxlimitlength_facet").val(),10);
		if(typeof(jsonobj.facet.filterlimit) != "undefined" && jsonobj.facet.filterlimit != null){
			jsonobj.facet.filterlimit.unitprice = parseInt($("#unitprice_facetfilter").val(),10);
			jsonobj.facet.filterlimit.maxprice = parseInt($("#maxprice_facetfilter").val(),10);
			jsonobj.facet.filterlimit.unitprice = parseInt($("#onceeditprice_facetfilter").val(),10);
			jsonobj.facet.filterlimit.maxprice = parseInt($("#maxeditprice_facetfilter").val(),10);
			jsonobj.facet.filterlimit.unitprice = parseInt($("#limitcontrol_facetfilter").val(),10);
			jsonobj.facet.filterlimit.maxprice = parseInt($("#maxlimitlength_facetfilter").val(),10);

		}
	}
	jsonobj.output.unitprice = parseInt($("#unitprice_output").val(),10);
	jsonobj.output.maxprice = parseInt($("#maxprice_output").val(),10);
	jsonobj.output.onceeditprice = parseInt($("#onceeditprice_output").val(),10);
	jsonobj.output.maxeditprice = parseInt($("#maxeditprice_output").val(),10);
	jsonobj.output.limitcontrol = parseInt($("#limitcontrol_output").val(),10);
	jsonobj.output.maxlimitlength = parseInt($("#maxlimitlength_output").val(),10);
	jsonobj.output.countlimit.unitprice = parseInt($("#unitprice_outputcount").val(),10);
	jsonobj.output.countlimit.maxprice = parseInt($("#maxprice_outputcount").val(),10);
	jsonobj.output.countlimit.onceeditprice = parseInt($("#onceeditprice_outputcount").val(),10);
	jsonobj.output.countlimit.maxeditprice = parseInt($("#maxeditprice_outputcount").val(),10);
	jsonobj.output.countlimit.limitcontrol = parseInt($("#limitcontrol_outputcount").val(),10);
	jsonobj.output.countlimit.maxlimitlength = parseInt($("#maxlimitlength_outputcount").val(),10);
}

/**
 * 添加模型时，生成配置计费的html
 * @param jsonobj 模型json对象
 */
function createJsonAccountingHtml(jsonobj){
	var html = '<span style="color:gray">说明： -1表示不限制，0表示不允许修改，大于0的表示可修改次数</span><br/>';
	html += '<table width="98%" border="0" cellspacing="0" cellpadding="0" class="list">';
	html += '<tr><th scope="col" style="width: 25%">字段名</th><th scope="col" style="width: 25%">允许修改值</th>';
	html += '<th scope="col" style="width: 25%">修改范围次数</th><th scope="col" style="width: 25%">最大值个数</th>';
	html += '</tr><tbody>';
	for(var item in jsonobj.filter){
		html += '<tr><td>'+jsonobj.filter[item].label+'</td>';
		html += '<td><input type="checkbox" id="allowcontrol_'+item+'" value="-1" checked="checked" /></td>';
		html += '<td><input type="text" class="shortinput" id="limitcontrol_'+item+'" value="'+jsonobj.filter[item].limitcontrol+'" /></td>';
		html += '<td><input type="text" class="shortinput" id="maxlimitlength_'+item+'" value="'+jsonobj.filter[item].maxlimitlength+'" /></td></tr>';
	}
	if(typeof(jsonobj.facet) != "undefined"){
		html += '<tr><td>分组查询</td>';
		html += '<td><input type="checkbox" id="allowcontrol_facet" value="-1" /></td>';
		html += '<td></td><td></td></tr>';
	}
	html += '<tr><td>输出条件</td>';
	html += '<td><input type="checkbox" id="allowcontrol_output" value="-1" /></td>';
	html += '<td></td><td></td></tr></tbody></table>';
	return html;
}



/**
 * 验证allowcontrol字段值  TODO 移动到html页
 */
function checkAllowcontrolValue(){
	var r = true;
	$("input[id^=limitcontrol_]").each(function(i,item){
		var reg = new RegExp(/^(\d+|-1{1})$/g);
		if(!reg.test($(item).val())){
			alert('格式错误，请输入-1或正整数');
			$(item).focus();
			r = false;
			return false;
		}
	});
	if(!r){
		return false;
	}
	$("input[id^=maxlimitlength_]").each(function(i,item){
		var reg = new RegExp(/^(\d+|-1{1})$/g);
		if(!reg.test($(item).val())){
			alert('格式错误，请输入-1或正整数');
			$(item).focus();
			r = false;
			return false;
		}
	});

	return r;
}



/**
 * 从html中获取allowcontrol值 
 * @param jsonobj  模型json
 * @returns 返回计算得到的积分
 */
function getJsonAllowcontrolValue(jsonobj){
	for(var item in jsonobj.filter){
		var alv = $('#allowcontrol_'+item);
		if(alv.prop("checked")){
			jsonobj.filter[item].allowcontrol = -1;
		}
		else{
			jsonobj.filter[item].allowcontrol = 0;
		}
		var limitc = $("#limitcontrol_"+item).val();
		jsonobj.filter[item].limitcontrol = parseInt(limitc,10);
		var maxlimit = $("#maxlimitlength_"+item).val();
		jsonobj.filter[item].maxlimitlength = parseInt(maxlimit,10);
	}
	if(jsonobj.facet != undefined && jsonobj.facet != null){
		jsonobj.facet.allowcontrol = parseInt($("#allowcontrol_facet").val(),10);
	}
	jsonobj.output.allowcontrol = parseInt($("#allowcontrol_output").val(),10);

}
