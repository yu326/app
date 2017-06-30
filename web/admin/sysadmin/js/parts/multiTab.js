//方案数组
var  planArray=[];
 //点击添加次数，用来生成新控件名。每次加一 删除不减
var countAdd=0;
function initForm(selectedArray)
{
    myCommonSelect2("dictionaryParentUrl", function(data){
        var dhtml = "";}, selectedArray, "词典类别", undefined, undefined, false,undefined, true,countAdd);
}
function popPlan2(planType){
	if(planType != undefined && planType == "newTask"){
		//新增任务
		$("#planMain").attr("task_type","newTask");
	}
    else if(planType != undefined && planType == "commonTask"){
		$("#planMain").attr("task_type","commonTask");
	}
    else{
		//重分析任务
		$("#planMain").attr("task_type","analysisTask");
	}
	//动态生成控件。检查当前方案长度，来决定新建方案所有控件的名称 
	var len=planArray.length;
	//下标从1开始
	len=len+1;
	//添加方案
	$("#addPlan").unbind("click");
	$("#addPlan").bind("click",function(){
		//默认
		var len=planArray.length;
		//下标从1开始
		//新窗口	
		len=len+1;
		countAdd=countAdd+1;
		myCommonSelect2("dictionaryParentUrl", function(data){
		var dhtml = "";}, "", "词典类别", undefined, undefined, false,undefined, true,countAdd);
	});
	//清空界面 重新加载
	for(var i=1;i<=countAdd;i++){
		if($("#head"+i)!=undefined){
			$("#head"+i).remove();
			$("#planDiv_"+i).remove();
		}
	}
	countAdd=0;

	$.each(planArray,function(di, itemPlan){
		countAdd++;
		initForm(itemPlan);
	});
		
	$( "#accordion" ).accordion({
		collapsible: true,
		heightStyle: "content"
		});

	$("#planMain").dialog({ width:600,height:400,modal: true,title:"分词方案" });
	$("#planMain").dialog("open");
	$("#planMain").dialog("option", "buttons", {
			"确定": function () {
					//获取所有方案数据
					//清空数组
					planArray.length=0;
					for(var i=1;i<=countAdd;i++){
						if($("#selectedcommons"+i)!=undefined){
							//判断是否选择默认
							var t1=$("#selectedcommons"+i).attr("select");
							if(t1==undefined)
								continue;
							if(t1=="1"){
                                //默认方案 
                                var plan2=[];
                                planArray.push(plan2); 
							}else{
								//从控件中获取
								var plan1=[];
								$("#selectedcommons"+i).find("span.sltitem").each(function (di, item) {
									var temp={};
									temp.code=$(item).attr("code");
									temp.name=$(item).text();
									plan1.push(temp); 
								});
								//添加到方案数组
								if(plan1.length!=0)
									planArray.push(plan1); 
							}
						}
					}
					//显示到窗体
					var temp1;
					var planHtml="";
					var count=1;
					$.each(planArray,function(di, itemPlan){
						planHtml +="方案"+count+":(";
						var t2="";
						var count2=0;
						if(itemPlan!=undefined&&itemPlan.length==0){
						if(count2!=0){
								t2=t2+","+"默认方案";
							}
							else{
								t2="默认方案";
							}
							count2++;
							planHtml+=t2+")";
							count++;
						}
						else {
							$.each(itemPlan, function (dj, itemCategory){
								if(count2!=0)
								{
									t2=t2+","+itemCategory.name;
								}
								else{
									t2=itemCategory.name;
									}
								count2++;
							});
							planHtml+=t2+")";
							count++;
						}
					});
					//赋值给input 
					var task_type=$("#planMain").attr("task_type");
					if(planArray.length==0){
						//被删除添加默认方案
						var plan2=[];
						//var tmp={};
						//tmp.default=1;
						planArray.push(plan2);
						var s1=JSON.stringify(planArray);
						
						if(task_type=="newTask"){
							$("#dictionaryPlanText2").text("默认方案");
							$("#dictionaryPlan2").val(s1);
						}
                        else if(task_type=="commonTask"){
							$("#tp_dictionaryPlanText2").text("默认方案");
							$("#tph_dictionaryPlan2").val(s1);
						}
                        else{
							$("#dictionaryPlanText1").text("默认方案");
							$("#dictionaryPlan1").val(s1);
						}
					}
					else{
						if(task_type=="newTask"){
							$("#dictionaryPlanText2").text(planHtml);
							var s1=JSON.stringify(planArray);
							$("#dictionaryPlan2").val(s1);
						}
                        else if(task_type=="commonTask"){
							$("#tp_dictionaryPlanText2").text(planHtml);
							var s1=JSON.stringify(planArray);
							$("#tph_dictionaryPlan2").val(s1);
						}
                        else{
							$("#dictionaryPlanText1").text(planHtml);
							var s1=JSON.stringify(planArray);
							$("#dictionaryPlan1").val(s1);
						}
					}
					$(this).dialog("close");
				
			},
			"取消": function () {
				$(this).dialog("close");
			}
		});
}


