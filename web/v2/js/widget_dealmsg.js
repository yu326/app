/*
* widget编辑时，页面与第三方网站页面交互时处理接收到的消息
*
*/
function GetRequest() {
	   var url = location.search; //获取url中"?"符后的字串
	   var theRequest = new Object();
	   if (url.indexOf("?") != -1) {
	      var str = url.substr(1);
	      var strs = str.split("&");
	      for(var i = 0; i < strs.length; i ++) {
	         theRequest[strs[i].split("=")[0]]=decodeURIComponent(strs[i].split("=")[1]);
	      }
	   }
	   return theRequest;
}

var req = GetRequest(); 
if(typeof req.msgtype != "undefined"){
	window.parent.parent.inter3i_widget.dispatchEvent(req.msgtype);
}
