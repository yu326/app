if (!window.Global){
    window.Global = new Object();
}
Global.oldajaxfun;
if(typeof($) != "undefined" && typeof($.ajax) != "undefined"){
	Global.oldajaxfun = $.ajax;
	$.ajax = function(params){
		if(typeof(params.success) == 'function'){
			var _oldsuccfun = params.success;
			params.success = function(data){
				if(!checkSession(data)){
					return false;
				}
				_oldsuccfun(data);
			};
		}
		Global.oldajaxfun(params);
	};
}
Global.checkedsession = false;//防止重复alert，增加全局变量记录是否已验证了session

//判断某种浏览器只需用if(Browser.ie)或if(Browser.firefox)等形式，
//而判断浏览器版本只需用if(Browser.ie == '8.0')或if(Browser.firefox == '3.0')等形式
var Browser = {};
var ua = navigator.userAgent.toLowerCase();
var s;
(s = ua.match(/msie ([\d.]+)/)) ? Browser.ie = s[1] :
(s = ua.match(/firefox\/([\d.]+)/)) ? Browser.firefox = s[1] :
(s = ua.match(/chrome\/([\d.]+)/)) ? Browser.chrome = s[1] :
(s = ua.match(/opera.([\d.]+)/)) ? Browser.opera = s[1] :
(s = ua.match(/version\/([\d.]+).*safari/)) ? Browser.safari = s[1] : 0;
function checkSession(returnvalue){
	if(returnvalue != undefined && returnvalue.errorcode == 'nosession' ){
		if(!Global.checkedsession){
			Global.checkedsession = true;
			var msg = returnvalue.error;
			msg = (msg == undefined || msg == null || msg == '') ? '登录超时' : msg;
			alert(msg);
			window.location.href = config.loginPageUrl;
		}
		return false;
	}
	else{
		return true;
	}
}
//按比例缩放图片 使用方法 <img onload="picresize(this,100,100)">
//add by jht
//2011-05-19
function picresize(obj, MaxWidth, MaxHeight) {
	obj.onload = null;
	img = new Image();
	img.src = obj.src;
	if (img.height > MaxHeight) {
		obj.width = MaxHeight * img.width / img.height;
		obj.height = MaxHeight;
	} else {
		obj.width = img.width;
		obj.height = img.height;
	}
	img = null;
}
function picresize80(obj, MaxWidth, MaxHeight) {
	obj.width = MaxWidth;
	obj.height = MaxHeight;
}
function showWaitImg(targetselector){
	var imgtop =  $(targetselector).height() / 2 - 33;
    var mleft = $(targetselector).width() / 2 - 33;
    $(targetselector).html('<img style="padding-left:'+mleft+'px;padding-top:'+imgtop+'px" src="'+config.imagePath+'wait.gif" />');
}
