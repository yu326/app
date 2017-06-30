	
//初始化函数
$(function () {
//字典类别选择
		$("#dict_parent_button").bind("click", function () {
			var addtype = $(this).attr("opttype");
			if (addtype == "select") {
				//隐藏下拉选择,显示input,新增
				$("#dict_parent_text").css("display", "");
				$("#dict_parent_select").css("display", "none");
				$("#dict_parent_text").val("");
				$("#dict_child_text").val("");
				$("#dict_child_text").css("display", "");
				$("#dict_child_select").css("display", "none");
				$("#dict_parent_text").removeAttr("disabled");
				$("#dict_child_text").removeAttr("disabled");
		
				$(this).attr("opttype", "add")
				//更改新增为选择
				$(this).text("选择父类");
			} 
			else if (addtype == "add") {
				//显示下拉选择,隐藏input,新增
				$("#dict_parent_text").css("display", "none");
				$("#dict_parent_select").css("display", "");
				$("#dict_parent_text").removeAttr("disabled");
				$("#dict_child_text").removeAttr("disabled");
				$("#dict_child_text").val("");
				$("#dict_child_select").css("display", "none");
				$("#dict_child_text").css("display", "");
				$(this).attr("opttype", "select")
				//更改新增为新增
				$(this).text("新增父类");
			}
		});
		
		//绑定父类选择事件
		$("#dict_parent_select").bind("change", function () {
			$("#dictionary_class_tip").text("");
			$("#dictionary_pclass_tip").text("");
		$("#dict_parent_button").css("display", "");
		$("#dict_child_select").css("display", "none");
		$("#dict_child_text").css("display", "");
		$("#dict_child_text").removeAttr("disabled");


			var pclass = $("#dict_parent_select").val();
			if(pclass==""||pclass==-1){
				$("#dict_child_select").empty().append("<option value='-1' >未选择</option>");
				return false;
			}
			getChildCategory("dict_child_select", pclass);
		});
		getParentCategory(); //获取父级下拉菜单
		//绑定子类选择事件
		$("#dict_child_select").bind("change", function () {
			$("#dictionary_class_tip").text("");
		});
		$("#dict_parent_select").bind("change", function () {
			$("#dictionary_pclass_tip").text("");
		});
		$("#add_state").bind("change", function () {
			$("#dictionary_state_tip").text("");
		});

		$("#dict_child_text").bind("focus", function(){
			$("#dictionary_class_tip").text("");
		});

		$("#dict_parent_text").bind("focus", function(){
			$("#dictionary_pclass_tip").text("");
		});

	});
	
	// 获取父类
	function getParentCategory(targetid, parentid){
		var searchUrl = config.modelUrl + "dictionary_category_model.php?type=select_dictionary_category&parent_id=-1";
		ajaxCommon(searchUrl, function (data) {
			if (data.errorcode != undefined) {
				alert(data.errormsg);
				return false;
			}
			if (data.totalcount > 0) {
				var poption = "<option value='-1' >未选择</option>";
				$.each(data.datalist, function (di, ditem) {
					//为父级下拉菜单添加选项
					poption += "<option value="+ditem.id+">" + ditem.parent_name + "</option>";
					if(parentid != undefined){
						if(parentid == ditem.id){
							$("#dict_parent_text").val(ditem.parent_name);
							$("#dict_parent_text").css("display", "");
							$("#dict_parent_select").css("display", "none");
						}
					}
				});
				//为父级下拉菜单添加选项
				if(targetid == undefined){
					targetid =  "dict_parent_select";
				}
				$("#"+targetid+"").empty().append(poption);
				
			}
		}, "json");
	}

	//获取子类
	function getChildCategory(targetid, pclass, sltclass) {
		var searchUrl = config.modelUrl + "dictionary_category_model.php?type=select_dictionary_category&parent_id="+ encodeURIComponent(pclass);
		ajaxCommon(searchUrl, function (data) {
			if (data.errorcode != undefined) {
				alert(data.errormsg);
				return false;
			}
			$("#" + targetid + "").empty().append("<option value='-1' >未选择</option>");
			if (data.totalcount > 0) {
				var classopthtml = "";
				//currCarr = []; //每次只保存当前父级下的二级元素
				$.each(data.datalist, function (di, ditem) {
					//currCarr.push(ditem.feature_class);
					classopthtml += "<option value="+ditem.id+">" + ditem.category_name + "</option>"
					if(sltclass != undefined){
						if(sltclass == ditem.id){
							$("#dict_child_text").val(ditem.category_name);
							$("#dict_child_text").css("display", "");
							$("#dict_child_select").css("display", "none");
						}
					}
				});
				$("#" + targetid + "").append(classopthtml);
				if(sltclass != undefined){
					$("#" + targetid + "").val(sltclass);
				}
			}
		}, "json");
	}
	
	//设置添加分词窗口
	function addDicWord2(item) {
		var dialogtitle = "修改类别状态";
	var str2,str3,str4;
	 //获取父级下拉菜单
		getParentCategory("dict_parent_select");
	 //重置控件状态
	 //隐藏新增控件
		if(item!=undefined){
		$("#dict_child_text").val(item.category_name);
		$("#dict_parent_button").css("display", "none");
		$("#dict_child_select").css("display", "none");
		$("#dict_child_text").css("display", "");
		$("#dict_child_text").attr("disabled", "disabled");
		//$("#dict_child_select").val(item.id);
		var classopthtml = "<option value="+item.id+">" + item.category_name + "</option>"
		$("#dict_child_select").empty().append(classopthtml);

		$("#dict_parent_text").val(item.parent_name);
		$("#dict_parent_text").css("display", "");
		$("#dict_parent_select").css("display", "none");
		$("#dict_parent_text").attr("disabled", "disabled");
		//$("#dict_parent_select").val(item.parent_id);
		var classopthtml = "<option value="+item.parent_id+">" + item.parent_name + "</option>"
		$("#dict_parent_select").empty().append(classopthtml);

		$("#add_state").val(item.state);

		/*
			$("#dict_parent_button").css("display", "none");
			$("#dict_child_text").css("display", "none");
			$("#dict_child_select").css("display", "");
			
			 str2=$("#"+item).attr("state");
			 str4=$("#"+item).attr("child_id");
			 str3=$("#"+item).attr("parent_id");
			 getChildCategory("dict_child_select", str3, str4);
			 */
			//$("#add_state").val(str2);
			
		//	$("#dict_parent_select").val(str3);
			
		//	$("#dict_child_select").val(str4);
		}
		else{
			//新增类别
			$("#dict_parent_select").val("-1");
			$("#dict_child_text").val("");
			$("#add_state").val(-1);
			dialogtitle = "新增类别";
			$("#dict_parent_button").css("display", "");
			$("#dict_parent_button").attr("opttype", "select");
			$("#dict_parent_button").text("新增父类");
			
			$("#dict_child_text").css("display", "");
			$("#dict_child_select").css("display", "none");
		}
		$("#dictionary_category_div").dialog({ height: 230,width:650,title: dialogtitle});
		//修过操作 用item设置控件的值
		$("#dict_parent_button").attr("addopttype", "select");
		$("#dictionary_pclass_tip").text("");
		$("#dictionary_state_tip").text("");
		$("#dictionary_class_tip").text("");
		
		submitbtnDisplay2("#dictionary_category_div",item);
		/*
		setTimeout(function () { 
		if(str2!=undefined){
			$("#add_state").val(str2);
				
				$("#dict_parent_select").val(str3);
				
				$("#dict_child_select").val(str4);
				}
		}, 200);
		*/
	   
		
	}
	//添加确定取消按钮
	function submitbtnDisplay2(targetor,item){
		$(targetor).dialog("option", "buttons", {
			"确定": function () {
					if(item!=undefined){
					//修过操作 用item设置控件的值
						submitUpdate(item);
					}else{
						submitAdd();
					}
					
			},
			"取消": function () {
				$(this).dialog("close");
			}
		});

	}
	//新增
function submitAdd(){
	var isSuccess=true;
	var child_state=$("#add_state").val();
	//start字典类别
		var parent_id=-1; //选择的父类id
		var child_id=-1; //选择的子类id
		var tp = $("#dict_parent_button").attr("opttype");
		//获取父类id
		var parent_id = $("#dict_parent_select").val();
		//获取父类值
		var pname = $("#dict_parent_select option[value="+parent_id+"]").text();
		var addfeature_pclass = pname;
		if (tp == "add") { //新增父类
		
			addfeature_pclass = $("#dict_parent_text").val();
			//判断父类是否有效
			if (addfeature_pclass == "" || addfeature_pclass == -1) {
				$("#dictionary_pclass_tip").text("父类不能为空!");
				return false;
			} else {
				addfeature_pclass = commonFun.trim(addfeature_pclass);
				if (addfeature_pclass.length > 50) {
					$("#dictionary_pclass_tip").text("最多50个字符!")
					return false;
				} else {
					var reg = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？]");
					if (reg.test(addfeature_pclass)) {
						$("#dictionary_pclass_tip").text("不能含有特殊字符!");
						return false;
					}
				}
			}
			//判断新增父类是否已经存在
			$("#dict_parent_select option").each(function(){
				if($(this).text()==addfeature_pclass) {
					//alert($(this).text());
					$("#dictionary_pclass_tip").text("父类类别已存在");
					//$("#dictionary_pclass_tip").css("display", "");
					//alert("新类别已存在");
					isSuccess=false;
					return false;
				} 
			});
			if(!isSuccess){
				return false;
			}

		}
		else{
			if(parent_id==""||parent_id==-1){
				$("#dictionary_pclass_tip").text("父类未选择!");
				//$("#dictionary_pclass_tip").css("display", "");
				return false;
			}
		}

		var ctype ="add";//
		var child_id = $("#dict_child_select").val();
		if(ctype=="add"||tp=="add"){
			//新子类
			var addfeature_class ="";
			if (tp == "add" || ctype == "add") {
				addfeature_class = $("#dict_child_text").val();
			}
			if (addfeature_class == "" || addfeature_class == -1) {
				$("#dictionary_class_tip").text("子类不能为空!");
				return false;
			} else {
				addfeature_class = commonFun.trim(addfeature_class);
				if (addfeature_class.length > 50) {
					$("#dictionary_class_tip").text("最多50个字符!")
					return false;
				} else {
					var reg = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？]");
					if (reg.test(addfeature_class)) {
						$("#dictionary_class_tip").text("不能含有特殊字符!");
						return false;
					}
				}
			}
		}else{
			if(child_id==""||child_id==-1){
				$("#dictionary_class_tip").text("子类不能为空!");
				return false;
			}
		}
		if(child_state==""||child_state==-1){
			$("#dictionary_state_tip").text("状态不能为空!");
			return false;
		}
		//--end
		
		if (tp == "add") {	
			//新增父类 
			var senddata3 = {
			   category_name: addfeature_pclass,
			   type: "checkvalueexist"
			}
			$.ajax({
			type: "POST",
			contentType: "application/json",
			async :false,
			dataType: "json",
			url: config.modelUrl + "dictionary_category_model.php",
			data: JSON.stringify(senddata3),
			beforeSend:function(){
				btnWaitDisplay("#dictionary_category_div");
			},
			complete:function(){
				submitbtnDisplay2("#dictionary_category_div");
			},
			success: function (data) {
				if (data != null) {
					if (data.flag == 1) {
						var existArr = [];
						//$.each(data.datalist, function(ddi, dditem){
						//	var pk = dditem.name;
						//	existArr.push(pk); 
						//});
						$("#dictionary_pclass_tip").text(" 父类已存在, 请更改!");
						return false;
					} else {
					//添加
					var senddata3 = {
						   parent_name: addfeature_pclass,
						   parent_id:"-1",
						   state:child_state,
						   async :false,
						   type: "add_dictionary_category"
						}
						$.ajax({
							type: "POST",
							contentType: "application/json",
							async :false,
							dataType: "json",
							url: config.modelUrl + "dictionary_category_model.php",
							data: JSON.stringify(senddata3),
							beforeSend:function(){
								btnWaitDisplay("#dictionary_category_div");
							},
							complete:function(){
								submitbtnDisplay2("#dictionary_category_div");
							},
							success: function (msg) {
								if(msg.flag == 1) {
									if(msg.id != undefined && msg.id !=""){
										//alert(msg.msg);
										parent_id=msg.id;
										/*
										var classopthtml = "<option value="+parent_id+" selected='selected'>" + addfeature_pclass + "</option>"
										$("#dict_parent_select").append(classopthtml);
										$("#dict_parent_select").val(parent_id);
										$("#dict_parent_select").css("display", "");
										$("#dict_parent_text").css("display", "none");
										$("#dict_parent_button").attr("opttype", "select");
										$("#dict_parent_button").text("新增父类");
										*/
									}
									//$("#addareadiv").dialog("close");
									
								} 
								}
							});
						}
					}
				}
			});
		}
	    
		
		if (ctype == "add"||tp=="add") { 
		
			var senddata3 = {
			   category_name: addfeature_class,
			   parent_id: parent_id,
			   type: "checkvalueexist"
			}
				$.ajax({
				type: "POST",
				contentType: "application/json",
				dataType: "json",
				async :false,
				url: config.modelUrl + "dictionary_category_model.php",
				data: JSON.stringify(senddata3),
				beforeSend:function(){
					btnWaitDisplay("#dictionary_category_div");
				},
				complete:function(){
					submitbtnDisplay2("#dictionary_category_div");
				},
				success: function (data) {
					if (data != null) {
						if (data.flag == 1) {
							var existArr = [];
						$("#dictionary_class_tip").text(" 子类已存在, 请更改!");
						var ot = $("#dict_parent_button").attr("opttype");
						if(ot == "add"){
							var classopthtml = "<option value="+parent_id+">" + addfeature_pclass + "</option>";
							$("#dict_parent_select").append(classopthtml);
							$("#dict_parent_select").val(parent_id);
							$("#dict_parent_select").css("display", "");
							$("#dict_parent_text").css("display", "none");
							$("#dict_parent_button").attr("opttype", "select");
							$("#dict_parent_button").text("新增父类");

						}

					return false;
							} else {
						//添加
						var senddata3 = {
							   category_name: addfeature_class,
							   parent_id:parent_id,
							   parent_name:addfeature_pclass,
							   state:child_state,
							   type: "add_dictionary_category"
							}
							$.ajax({
								type: "POST",
								contentType: "application/json",
								dataType: "json",
								url: config.modelUrl + "dictionary_category_model.php",
								data: JSON.stringify(senddata3),
								beforeSend:function(){
									btnWaitDisplay("#dictionary_category_div");
								},
								complete:function(){
									submitbtnDisplay2("#dictionary_category_div");
								},
								success: function (msg) {
									if(msg.flag == 1) {
										if(msg.msg != undefined && msg.msg !=""){
											alert(msg.msg);
											child_id=msg.id;
										}

										$("#dictionary_category_div").dialog("close");
											//加载列表
										searchRequest(pagesize);
										//获取父类
										getParentCategory("dic_type");
									} 
								}
							});
						}
					}
				}
			});
		}

}
//修改
function submitUpdate(item){
	var child_state=$("#add_state").val();
	//start字典类别
	var parent_id=-1; //选择的父类id
	var child_id=-1; //选择的子类id
	var parent_id = $("#dict_parent_select").val();
	if(parent_id==""||parent_id==-1){
		$("#dictionary_pclass_tip").text("父类未选择!");
		return false;
	}
	var child_id = $("#dict_child_select").val();
	if(child_id==""||child_id==-1){
		$("#dictionary_class_tip").text("子类不能为空!");
		return false;
	}
	if(child_state==""||child_state==-1){
		$("#dictionary_state_tip").text("状态不能为空!");
		return false;
	}
	var senddata3 = {
		   id: child_id,
		   state:child_state,
		   type: "updatetype"
		}
		$.ajax({
			type: "POST",
			contentType: "application/json",
			dataType: "json",
			url: config.modelUrl + "dictionary_category_model.php",
			data: JSON.stringify(senddata3),
			beforeSend:function(){
				btnWaitDisplay("#dictionary_category_div");
			},
			complete:function(){
				submitbtnDisplay2("#dictionary_category_div");
			},
			success: function (msg) {
				if(msg.flag == 1) {
					if(msg.msg != undefined && msg.msg !=""){
						alert(msg.msg);
						child_id=msg.id;
					}
					searchRequest(pagesize);
					$("#dictionary_category_div").dialog("close");
				} 
			}
	});
}



