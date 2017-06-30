// ajax 共用方法
function ajaxCommon(url, callback, dataType) {
	$.ajax( {
		type : "GET",
		url : url,
		async : false, // (默认: true) 默认设置下，所有请求均为异步请求。如果需要发送同步请求，请将此选项设置为 false
		data : {
			sid : Math.random()
		},
		cache : true, // 默认为true， false时不会从浏览器缓存中加载请求信息
		dataType : dataType,// text返回纯文本字符串
		beforeSend : function() {
			// $("#chart1div").text("loading...");
		},
		success : function(data, textStatus) {
			callback(data);
		},
		complete : function(XMLHttpRequest, textStatus) {
			// successFunction(XMLHttpRequest.responseText, type);
			// alert(XMLHttpRequest.readyState);
			// alert(XMLHttpRequest.responseText);
			// alert(textStatus);
		},
		error : function(XMLHttpRequest, textStatus, errorThrown) {
			trace(XMLHttpRequest.responseText);
		}
	}); // ajax end
}
function weiboUserurl(userid, sourceid){
	switch(parseInt(sourceid,10)){
		case 1: //新浪
			if(userid){
				//var url = "http://weibo.com/u/"+userid;
				var url = "http://weibo.com/"+userid;
				return url;
			}
			else{
				return "";
			}
		case 2: //腾讯
			return "";
		case 3://汽车之家
			if(userid){
				//var url = "http://weibo.com/u/"+userid;
				var url = "http://i.autohome.com.cn/"+userid;
				return url;
			}
			else{
				return "";
			}
		default:
			return "";
	}
}
function getSystemTitle(){
	var title = "";
	$.ajax({url:config.phpPath+"checkuser.php",dataType:"json",type:"POST",data:{type:"systitle"},async:false,
	    success:function(d){
	        if(d.result){
				title = d.systitle;
	        }
	    },
	    error:function(){
	    }
	});
	return title;
}
function getNavObj(){
	var navobj = {};
	if(config.systemTitle == ""){
		config.systemTitle = getSystemTitle();
	}
	navobj.systitle = config.systemTitle;
	var level1 = "";
	var level2 = "";
	var curPath = window.location.pathname;
	var tmparr = curPath.split('/');
	var curfilename = tmparr[tmparr.length-1];
	$("#left .f").each(function(fi, fitem){
		if($(fitem).find("a[name=level1]").attr("href") != undefined){
			level1file = $(fitem).find("a[name=level1]").attr("href");
			if(curfilename == level1file){
				level1 = $(fitem).find("a[name=level1]").text();
				navobj.level1 = level1;
				return false;
			}
		}
		else{
			var find = false;
			level1 = $(fitem).find("a[name=level1]").text();
			$(fitem).find("a[name=level2]").each(function(li, litem){
				level2file = $(litem).attr("href");
				if(curfilename == level2file){
					level2 = $(litem).text();
					navobj.level1 = level1;
					navobj.level2 = level2;
					find = true;
					return false;
				}
			});
			if(find){
				return false;
			}
		}
	});
	return navobj;
}
function changeTabTitle(){
	var navobj = getNavObj();
	var nhtml = "";
	if(navobj.systitle){
		nhtml += navobj.systitle;
	}
	if(navobj.level1){
		nhtml += "--"+navobj.level1;
	}
	if(navobj.level2){
		nhtml += "--"+navobj.level2;
	}
	document.title = nhtml;
}
