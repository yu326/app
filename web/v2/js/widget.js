if (!window.inter3i_widget) {
	inter3i_widget = new Object();
}

inter3i_widget.sitePath = '';
inter3i_widget.token = null;
inter3i_widget.events = new Object();// 事件
inter3i_widget.thirdPartyMsgUrl = '';//第三方网站放置的处理消息的html地址

inter3i_widget.setToken = function(value){
	inter3i_widget.token = value;
} ;

inter3i_widget.setMsgUrl = function(value){ 
	if(typeof value != "undefined"){
		if(value.indexOf("http:") === 0 || value.indexOf("https:") === 0){//绝对地址直接赋值
			inter3i_widget.thirdPartyMsgUrl = value;
		}
		else if(value.indexOf("../") === 0){ //相对地址，根据..的个数，取路径
			var valuearr = value.split("/") ;
			var pathnamearr = location.pathname.split("/");
			if(valuearr.length > 0 && pathnamearr.length > 0){
				pathnamearr.pop();//去掉文件名;
				while(valuearr.length > 0){
					var v = valuearr.shift();
					if(v == ".."){
						pathnamearr.pop();
					}
					else{
						break;
					}
				}
				pathnamearr = pathnamearr.concat(valuearr);
				var rpath = pathnamearr.join("/");
				inter3i_widget.thirdPartyMsgUrl =  location.protocol+"//"+location.hostname+(location.port ? ":"+location.port : "")+ rpath;
			}
		} 
		else if(value.indexOf("/") === 0){//根目录
			inter3i_widget.thirdPartyMsgUrl =  location.protocol+"//"+location.hostname+(location.port ? ":"+location.port : "")+value;
		}
		else {
			var rpath = "";
			var pathnamearr = location.pathname.split("/"); 
			if(pathnamearr.length > 0){ 
				pathnamearr[pathnamearr.length-1] = value.replace("./", "");
				rpath = pathnamearr.join("/");
			}
			else{ 
				rpath = value.replace("./", "/");
			}
			inter3i_widget.thirdPartyMsgUrl =  location.protocol+"//"+location.hostname+(location.port ? ":"+location.port : "")+ rpath;
		}
	}
};

/**
 * get方式跨域获取json
 */
inter3i_widget.getJson = function(url) {
	var head = document.getElementsByTagName("head")[0];
	var _script = document.createElement("script");
	_script.setAttribute("type", "text/javascript");
	_script.setAttribute("src", url);
	head.appendChild(_script);
};

/**
 * 挂载事件 eventname支持“tokenload" "error" "checktoken" "editsuccess" "editcancel" 
 */
inter3i_widget.attachEvent = function(eventname, fun) {
	if (typeof (inter3i_widget.events[eventname]) == "undefined") {
		inter3i_widget.events[eventname] = [];
	}
	inter3i_widget.events[eventname].push(fun);
};

/**
 * 移除事件
 */
inter3i_widget.removeEvent = function(eventname, fun) {
	if (typeof eventname != "undefined"
			&& typeof (inter3i_widget.events[eventname]) != "undefined") {
		if (typeof fun == "undefined") {
			inter3i_widget.events[eventname] = [];
		} else {
			for ( var i = inter3i_widget.events[eventname].length; i > -1; i--) {
				if (inter3i_widget.events[eventname][i] === fun) {
					inter3i_widget.events[eventname].splice(i, 1);
				}
			}
		}
	}
};

inter3i_widget.dispatchEvent = function(eventname) {
	if (typeof (inter3i_widget.events[eventname]) != "undefined") {
		var args = [];
		for(var i=0; i<arguments.length; i++){
			if(i > 0){
				args.push(arguments[i]);
			}
		}
		//var args = Array.prototype.slice(arguments);
		/*if (args.length > 0) {
			args.shift();// 第一个参数为 eventname，传递剩余的参数
		}*/
		for ( var i = 0; i < inter3i_widget.events[eventname].length; i++) {
			inter3i_widget.events[eventname][i].apply(null, args);
		}
	}
};
/**
 * 初始化widget环境
 */
inter3i_widget.init = function() {
	var allfrm = document.getElementsByTagName("iframe");
	//先找到主站地址
	for ( var i = 0; i < allfrm.length; i++) {
		if(allfrm[i].getAttribute("widget") == "inter3i"){
			var sitepath = allfrm[i].getAttribute("widgetsite");
			if (sitepath) {
				inter3i_widget.sitePath = sitepath;
				return true;
			}
		}
	}
	inter3i_widget.dispatchEvent('error', '代码格式有误，未找到主站地址');
	return false;
};

//开始显示
inter3i_widget.render = function(){
	if (inter3i_widget.token == null) {
		inter3i_widget.dispatchEvent('error', '尚未设置token');
		return false;
	} else {
		var allfrm = document.getElementsByTagName("iframe");
		var frmurl = inter3i_widget.sitePath + "widget.html?token="
				+ inter3i_widget.token;
		for ( var i = 0; i < allfrm.length; i++) {
			var widgetattr = allfrm[i].getAttribute("widget");
			if (widgetattr == "inter3i") {
				var incid = allfrm[i].getAttribute("instanceid");
				var eleid = allfrm[i].getAttribute("elementid");
				var showid = allfrm[i].getAttribute("showid");
				var navid = allfrm[i].getAttribute("navid");
				allfrm[i].setAttribute("src", frmurl + "&instanceid=" + incid
						+ "&elementid=" + eleid + "&navid=" + navid
						+ "&showid=" + showid + "&_r="
						+ Math.round(Math.random() * 100000));
			}
		}
		return true;
	}
};

inter3i_widget.showError = function(error) {
	if (inter3i_widget.sitePath != ''){
		var allfrm = document.getElementsByTagName("iframe");
		for ( var i = 0; i < allfrm.length; i++) {
			if(allfrm[i].getAttribute("widget") == "inter3i"){
				var height = allfrm[i].getAttribute('height');
				allfrm[i].setAttribute("src",inter3i_widget.sitePath + "widget_error.html?error="+encodeURIComponent(error)+"&height="+height);
			}
		}
	}
};

inter3i_widget.onGetToken = function(json) {
	if (typeof json != "undefined" && typeof json.token != "undefined"
			&& json.token != "") {
		inter3i_widget.token = json.token;
		inter3i_widget.dispatchEvent('tokenload', json.token);
	} else {
		if (typeof json.error != "undefined") {
			inter3i_widget.dispatchEvent('error', json.error);
		}
	}
};

inter3i_widget.onCheckToken = function(json) {
	if (typeof json != "undefined" && typeof json.result != "undefined"){
		inter3i_widget.dispatchEvent('checktoken', json.result);
	}
	else{
		if (typeof json.error != "undefined") {
			inter3i_widget.dispatchEvent('error', json.error);
		}
	}
};

/**
 * 获取element的Json配置
 */
/*inter3i_widget.getElementJson = function(instanceid, successCallback){
	if(inter3i_widget.token != null){
		if(typeof successCallback != 'undefined'){
			var funname = 'inter3i_widget.fun_'+Math.round(Math.random()*1000000000);
			eval(funname+"=successCallback;");
			var url = inter3i_widget.sitePath + "model/modelinterface.php?type=getelements&instanceid="+
				instanceid+"&token="+inter3i_widget.token+"&callback="+funname;
			inter3i_widget.getJson(url);
			return true;
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
};

inter3i_widget.commitElementJson = function(elementJson, callback){
	
};
*/
/**
 * 编辑element，通过嵌入iframe实现
 * @param targetid 将iframe放入哪个容器
 * @param frameid 编辑的iframe的ID
 */
inter3i_widget.editElement = function(targetid, frameid){
	var targetele = document.getElementById(targetid);
	if(targetele && frameid){
		var tarfrm = document.getElementById(frameid);
		var instanceid = tarfrm.getAttribute("instanceid");
		var elementid = tarfrm.getAttribute("elementid");
		var showid = tarfrm.getAttribute("showid");
		var navid = tarfrm.getAttribute("navid");
		var frm = document.createElement('iframe');
		frm.setAttribute('width','100%');
		frm.setAttribute('height','100%');
		//frm.setAttribute('scrolling','no');
		frm.setAttribute('frameborder','0');
		var url = inter3i_widget.sitePath + "widget_edit.html?instanceid="+instanceid+"&elementid="+elementid+
			"&showid="+showid+"&navid="+navid+"&token="+inter3i_widget.token+"&msgurl="+encodeURIComponent(inter3i_widget.thirdPartyMsgUrl);
		frm.setAttribute('src',url);
		targetele.innerHTML = '';
		targetele.appendChild(frm);
		return true;
	}
	else{
		return false;
	}
};

inter3i_widget.completeEditElement = function(frameid){
	var tarfrm = document.getElementById(frameid);
	tarfrm.setAttribute('src', tarfrm.getAttribute('src'));
};

//验证token是否有效
inter3i_widget.checkToken = function(token){
	var tokenurl = inter3i_widget.sitePath
	+ "model/checkuser.php?type=checktoken&token="+token
	+ "&callback=inter3i_widget.onCheckToken";
	inter3i_widget.getJson(tokenurl);
};

/**
 * 使用jsonp获取token
 */
inter3i_widget.getToken = function(username, password) {
	var tokenurl = inter3i_widget.sitePath
			+ "model/checkuser.php?type=gettoken&username="
			+ encodeURIComponent(username) + "&password=" + password
			+ "&callback=inter3i_widget.onGetToken";
	inter3i_widget.getJson(tokenurl);
};
