	
//初始化函数
$(function () {
//字典类别选择
		$("#dict_parent_button").bind("click", function () {
			var addtype = $(this).attr("opttype");
			if (addtype == "select") {
				//隐藏下拉选择,显示input,新增
				$("#dict_parent_text").css("display", "");
				$("#dict_parent_select").css("display", "none");

				$("#dict_child_text").css("display", "");
				$("#dict_child_select").css("display", "none");

				$("#dict_child_button").css("display", "none");
				$(this).attr("opttype", "add")
				//更改新增为选择
				$(this).text("选择父类");
			} 
			else if (addtype == "add") {
				//显示下拉选择,隐藏input,新增
				$("#dict_parent_text").css("display", "none");
				$("#dict_parent_select").css("display", "");

				$("#dict_child_text").css("display", "none");
				$("#dict_child_select").css("display", "");

				$("#dict_child_button").css("display", "");

				$(this).attr("opttype", "select")
				//更改新增为新增
				$(this).text("新增父类");
			}
		});
		$("#dict_child_button").bind("click", function () {
			var addtype = $(this).attr("opttype");
			if (addtype == "select") {
				//隐藏下拉选择,显示input,新增
				$("#dict_child_text").css("display", "");
				$("#dict_child_select").css("display", "none");

				$(this).attr("opttype", "add")
				//更改新增为选择
				$(this).text("选择子类");
			} else if (addtype == "add") {
				//显示下拉选择,隐藏input,新增
				$("#dict_child_text").css("display", "none");
				$("#dict_child_select").css("display", "");

				$(this).attr("opttype", "select")
				//更改新增为新增
				$(this).text("新增子类");
			}
		});
		//绑定父类选择事件
		$("#dict_parent_select").bind("change", function () {
			$("#dictionary_class_tip").text("");
			$("#dictionary_pclass_tip").text("");

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
			$("#dictionary_class_tip").css("display", "none");
			var id1 = $("#dict_child_select").val();
			if(id1==""||id1==-1){
				return false;
			}
			else{
				//检查分词是否已经存在，存在就禁用控件
				isWordExist(id1);
			}
		});
	});
	
	////检查分词是否已经存在，存在就禁用控件
	function isWordExist(id1){
		$("#sync_dict1").prop("disabled",false);
		$("#dict_tip1").text("");
		
		var words=$("#dict_area1").val().split("\n");
		var searchUrl = config.modelUrl + "dictionary_model.php?type=selectdictionaryinfo&page=1&pagesize=1&category_id="+id1+"&dict_value="+ encodeURIComponent(words.join());
		ajaxRequest(searchUrl, function (data) {
			if (data.errorcode != undefined) {
				alert(data.errormsg);
				return false;
			}
			if (data.totalcount > 0) {
				//var poption = "<option value='-1' >未选择</option>";
				$.each(data.datalist, function (di, ditem) {
					if($("#dict_area1").val().indexOf(ditem.value)>=0){
						$("#sync_dict1").prop("checked", false);
						$("#dict_tip1").text("("+ditem.value+")已经存在,请修改或删除");
					}

				});
		
			}
		}, "json");
	}
	// 获取父类
	function getParentCategory(targetid){
		var searchUrl = config.modelUrl + "dictionary_category_model.php?type=select_dictionary_category&parent_id=-1";
		ajaxRequest(searchUrl, function (data) {
			if (data.errorcode != undefined) {
				alert(data.errormsg);
				return false;
			}
			if (data.totalcount > 0) {
				var poption = "<option value='-1' >未选择</option>";
				$.each(data.datalist, function (di, ditem) {
					//为父级下拉菜单添加选项
					poption += "<option value="+ditem.id+">" + ditem.parent_name + "</option>";
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
		ajaxRequest(searchUrl, function (data) {
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
				});
				$("#" + targetid + "").append(classopthtml);
				if(sltclass != undefined){
					$("#" + targetid + "").val(sltclass);
				}
			}
		}, "json");
	}
	
	//设置添加分词窗口
	function addDicWord2() {
	 //重置控件状态
		$("#addfeature_class_text").css("display", "none");
		$("#addfeature_class_select").css("display", "");
		$("#addfeature_pclass_text").css("display", "none");
		$("#addfeature_pclass_select").css("display", "");
		$("#addfeature_pclass_add").attr("addopttype", "select");
		$("#addfeature_class_add").attr("addopttype", "select");
	
		$("#dictionary_category_div").dialog({ height: 330,width:650 });
		$("#dictionary_category_div").dialog("open");
		var dialogtitle = "添加到分词字典";

		$("#dictionary_category_div").dialog({
			title: dialogtitle
		});
	
		submitbtnDisplay2("#dictionary_category_div");
	
		
	}
	//添加确定取消按钮
	function submitbtnDisplay2(targetor){
		$(targetor).dialog("option", "buttons", {
			"确定": function () {
					submitDictionary();
			},
			"取消": function () {
				$(this).dialog("close");
			}
		});

	}
	
	//提交分词到数据库
	function submitDictionary(){

	//start字典类别
		var parent_id=-1; //选择的父类id
		var child_id=-1; //选择的子类id
		var tp = $("#dict_parent_button").attr("opttype");
		//获取父类id
		var parent_id = $("#dict_parent_select").val();
		//获取父类值
		var addfeature_pclass="";
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
					alert("新类别已存在");
					return false;
				} 

				});

		}
		else{
			if(parent_id==""||parent_id==-1){
				$("#dictionary_pclass_tip").text("父类未选择!");
				return false;
			}
		}

		var ctype = $("#dict_child_button").attr("opttype");
		var child_id = $("#dict_child_select").val();
		if(ctype=="add"||tp=="add"){
			//新增子类
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
						   category_name: addfeature_pclass,
						   parent_id:"-1",
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
									if(msg.id != undefined && msg.id !=""){
										//alert(msg.msg);
										parent_id=msg.id;
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
						//		existArr.push(pk); 
						//	});
						//	$("#ro_area_code_tip").text(existArr.join(", ")+" 已存在, 请更改!");
						$("#dictionary_class_tip").text(" 子类已存在, 请更改!");
							return false;
						} else {
						//添加
						var senddata3 = {
							   category_name: addfeature_class,
							   parent_id:parent_id,
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
										if(msg.id != undefined && msg.id !=""){
											//alert(msg.msg);
											child_id=msg.id;
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
	
		//添加到分词字典
		//单词拼接为数组
		var add_language=$("#adddic_language_select").val();

		var dict_array = $("#dict_area1").val().split("\n");
	    var senddata2 = {
	   category_id: child_id,
	   dic_language: add_language,
	   dic_value:dict_array,
	   type: "checkvalueexist"
		}
		$.ajax({
		type: "POST",
		contentType: "application/json",
		dataType: "json",
		url:  config.modelUrl + "dictionary_model.php",
		data: JSON.stringify(senddata2),
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
					$.each(data.datalist, function(ddi, dditem){
						var pk = dditem.value;
						existArr.push(pk); 
					});
					$("#dict_tip1").text("("+existArr.join(", ")+" )已存在, 请重新选择需要添加的分词!");
					return false;
				} else {
					//添加到分词字典
					senddata2.type = "adddictionary"; 
					$.ajax({
						type: "POST",
						contentType: "application/json",
						dataType: "json",
						url: config.modelUrl + "dictionary_model.php",
						data: JSON.stringify(senddata2),
						beforeSend:function(){
							btnWaitDisplay("#dictionary_category_div");
						},
						complete:function(){
							submitbtnDisplay("#dictionary_category_div");
						},
						success: function (msg) {
							if(msg.flag == 1) {
								if(msg.msg != undefined && msg.msg !=""){
									alert(msg.msg);
								}
								$("#dictionary_category_div").dialog("close");
							} 
						}
					});
				}
			}
		}
	});
	
}

