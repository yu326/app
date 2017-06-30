/*
 任务管理相关处理函数
*/

function getAllTask(callback){
	$.ajax({
		type:'GET',
		url: config.phpPath+"taskmanager.php",
		data: "type=gettasksbytasktype",
		async:false,
		dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
				callback(data);
			}
		}
	});
}
function getTaskType(task){
	if(config.allTask.length == 0){
		getAllTask(function(data){
			if(data){
				config.allTask = data;
			}
		});
	}
	var strtype = '';
	$.each(config.allTask, function(ti, titem){
		if(titem.id == task){
			strtype = titem.name;
		}
	});
	return strtype;
}
function getAllTaskPageStyleType(callback){
	$.ajax({
		type:'GET',
		url: config.phpPath+"taskmanager.php",
		data: "type=getalltaskpagestyletype",
		async:false,
		dataType:"json",
		success:function(data){
			if(jQuery.isFunction(callback)){
				callback(data);
			}
		}
	});
}
function getTaskPageStyleType(pagestyletype){
	if(config.allTaskPageStyleType.length == 0){
		getAllTaskPageStyleType(function(data){
			if(data){
				config.allTaskPageStyleType = data;
			}
		});
	}
	var strtype = '';
	$.each(config.allTaskPageStyleType, function(ti, titem){
		if(titem.id == pagestyletype){
			strtype = titem.name;
		}
	});
	return strtype;
}
function getScheduleTaskStatus(status){
	var result;
	switch(status){
		case 0:
			result = "禁用";
			break;
		case 1:
			result = "启用";
			break;
		case 2:
			result = "运行";
			break;
		default:
			result = "未知";
			break;
	}
	return result;
}
function getTaskStatus(taskstatus){
	var result;
	switch(taskstatus){
		case -1:
		  result = "等待停止";
		  break;
		case 0:
		  result = "等待启动";
		  break;
	  case 1:
	    result = "正常";
		  break;
	  case 2:
	    result = "停止";
		  break;
	  case 3:
	    result = "完成";
		  break;
	  case 4:
	    result = "排队中";
		  break;
	  case 5:
	    result = "崩溃";
		  break;
	  case 6:
	    result = "等待验证";
		  break;
	  case 7:
	    result = "挂起";
	    break;
	   case 8:
	     result = "任务异常";
	     break;
		case 9:
			result = "相同任务";
			break;
	  default:
	    result = "未启动";
		  break;
	}
	return result;
}

function addScheduleTask(params, callback){
	$.ajax({
		type: 'POST',
		url: config.phpPath+"schedulemanager.php",
		data: "type=add&"+params,
		beforeSend:function(){
			$("#waitsubmit").append("<span id='testwait'><img src='"+config.imagePath+"minwait.gif' width='20' height='20' /></span>");
		},
		complete:function(){
			 $("#testwait").remove();
	    },
		success: function(data){
			if(jQuery.isFunction(callback)){
				callback(data);
			}
		},
		error:function(){
				  if(jQuery.isFunction(callback)){
					  callback({result:false, msg:"请求发生异常"});
				  } 
			  },
		dataType: "json"
	});
}
function editScheduleTask(params, callback){
	$.ajax({
		type: 'POST',
		url: config.phpPath+"schedulemanager.php",
		data: "type=edit&"+params,
		beforeSend:function(){
			$("#waitsubmit").append("<span id='testwait'><img src='"+config.imagePath+"minwait.gif' width='20' height='20' /></span>");
		},
		complete:function(){
			 $("#testwait").remove();
	    },
		success: function(data){
			if(jQuery.isFunction(callback)){
				callback(data);
			}
		},
		error:function(){
				  if(jQuery.isFunction(callback)){
					  callback({result:false, msg:"请求发生异常"});
				  } 
			  },
		dataType: "json"
	});
}
function deleteScheduleTask(id, callback){
	$.post(config.phpPath+"schedulemanager.php",{type:"delete",id:id},function(data){
		    if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		},"json");
}
function changeScheduleTaskStatus(id,status,callback){
	$.post(config.phpPath+"schedulemanager.php",{type:"changestatus",id:id,status:status},function(data){
		    if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		},"json");
}

//传入字符串参数 a=1&b=2&c=3
function addTask(params,callback){
	$.ajax({
		type: 'POST',
		url: config.phpPath+"taskmanager.php",
		data: "type=add&"+params,
		beforeSend:function(){
			$("#waitsubmit").append("<span id='testwait'><img src='"+config.imagePath+"minwait.gif' width='20' height='20' /></span>");
		},
		complete:function(){
			 $("#testwait").remove();
				 },
		success: function(data){
					 if(jQuery.isFunction(callback)){
						 callback(data);
					 }
				 },
		error:function(){
				  if(jQuery.isFunction(callback)){
					  callback({result:false, msg:"请求发生异常"});
				  } 
			  },
		dataType: "json"
	});
}

//获取当前任务
function getCurrentTask(callback){
	$.post(config.phpPath+"taskmanager.php",{type:"current"},function(data){
		    if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		},"json");
}

function deleteTask(ids, callback){
	$.post(config.phpPath+"taskmanager.php",{type:"deltask",ids:ids},function(data){
		    if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		},"json");
}

function getAllDataCount(sourceid, callback){
	$.post(config.phpPath+"taskmanager.php",{type:"getalldatacount",sourceid:sourceid},function(data){
	    if(jQuery.isFunction(callback)){
	    	callback(data);
	    }
	},"json");
}

function changeTaskStatus(id,status,callback){
	$.post(config.phpPath+"taskmanager.php",{type:"changestatus",id:id,taskstatus:status},function(data){
		    if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
		},"json");
}

function getTaskIP(callback){
	$.post(config.phpPath+"taskmanager.php",{type:"getip"},function(data){
		if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	},"json");
}

function getMachines(callback){
	$.post(config.phpPath+"taskmanager.php",{type:"getmachine"},function(data){
		if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	},"json");
}


//获取历史任务
function getTaskHistory(pageindex,pagesize,callback){
	$.post(config.phpPath+"taskmanager.php",{type:"gethistory",pageindex:pageindex,pagesize:pagesize},function(data){
		if(jQuery.isFunction(callback)){
		    	callback(data);
		    }
	},"json");	
}
/**
 *获取div的前半部分标签 
 */
function getFHHtml(div){
	var htm = '';
	htm = $(div).clone().wrap('<div></div>').parent().html();
	htm = htm == undefined ? "" : htm;
	var regex2 = /(<div[^>/]*>).*/i;
	var groups = regex2.exec(htm); 
	if(groups){
		htm = groups[1];
	}
	return htm;
}
