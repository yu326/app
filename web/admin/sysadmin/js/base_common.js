function trace(msg) {
	try {
		if (config.debug) {
			alert(msg);
		}
	} catch (ex) {
	}
}
// JavaScript Document
// 2012-03-31 统一返回错误的格式
function errorOutPut(errorcode, error) {
	// trace("errorcode:"+errorcode+" error:"+error);
	return {
		"errorcode" : errorcode,
		"error" : error
	};
}
function HexRandom() {
	var s = Math.round(Math.random() * 0x1000000);
	return s.toString(16);
}
function getTimeSec(formatStr) {
	var d;
	if (undefined == formatStr) // 为定义时间时默认为当前时间
	{
		d = new Date();
		d = d.getTime();
	} 
	else {
		formatStr = commonFun.trim(formatStr);
		// 年月日时分秒格式的，parse能解析的格式只能是月,日,年的顺序来表示且用斜线分割（02/06/2012 10:44）
		// 传入格式可以是斜线或横线，按照年月日 时分秒顺序，格式化成能够解析的格式
		var slashStyle = formatStr.replace(/-/g, '/');
		var dateArr = slashStyle.split(" ");
		var strArr = dateArr[0].split("/");
		if (dateArr[1] != undefined) {
			var datestring = strArr[1] + "/" + strArr[2] + "/" + strArr[0] + " " + dateArr[1];
		} else { // 不含有 “时” “分”
			var datestring = strArr[1] + "/" + strArr[2] + "/" + strArr[0];
		}
		d = Date.parse(datestring);
	}
	return Math.round(d / 1000);
}
//格式化时间戳，输入秒
function timeToStr(timestamp){
	if(timestamp == null || timestamp == 0){
		return "";
	}
	//var d = new Date(timestamp*1000);
	//return d.toString("yyyy-MM-dd hh:mm:ss");
	return formatTime("yyyy-MM-dd hh:mm:ss",timestamp);
}
/*
 * yyyy-MM-dd hh:mm:ss
 */
function formatTime(format, timeStamp) {
	var dateObj = new Date(timeStamp * 1000);
	var o = {
		"M+" : dateObj.getMonth() + 1, // month
		"d+" : dateObj.getDate(), // day
		"w+" : dateObj.getDay(), // week 
		"h+" : dateObj.getHours(), // hour
		"m+" : dateObj.getMinutes(), // minute
		"s+" : dateObj.getSeconds(), // second
		"q+" : Math.floor((dateObj.getMonth() + 3) / 3), // quarter
		"S" : dateObj.getMilliseconds()
	// millisecond
	}

	if (/(y+)/.test(format)) {
		format = format.replace(RegExp.$1, (dateObj.getFullYear() + "")
				.substr(4 - RegExp.$1.length));
	}

	for ( var k in o) {
		if (new RegExp("(" + k + ")").test(format)) {
			format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k]
					: ("00" + o[k]).substr(("" + o[k]).length));
		}
	}
	return format;
}
function getFormatTime(timestamp) {
	var strdate = new Date(timestamp * 1000);
	var format = new Array();
	var formatDate = "";
	var formatTime = "";
	var c = ":";
	formatDate += strdate.getFullYear() + "/";
	formatDate += (strdate.getMonth() + 1) + "/";
	formatDate += strdate.getDate();

	if (strdate.getHours() <= 9) {
		formatTime += "0" + strdate.getHours() + c;
	} else {
		formatTime += strdate.getHours() + c;
	}
	if (strdate.getMinutes() <= 9) {
		formatTime += "0" + strdate.getMinutes();
	} else {
		formatTime += strdate.getMinutes();
	}
	// formatTime += strdate.getSeconds() + c;
	// formatTime += strdate.getMilliseconds();
	format['date'] = formatDate;
	format['time'] = formatTime;
	return format;
}
//时间戳 ,秒
function getWeek(timestamp){
	var dateObj = (new Date(timestamp * 1000));
	var dateObj2 = (new Date(timestamp * 1000));
	var week = {};
	var theday = dateObj.getDay();
	if(theday == 0){
		theday = 7;
	}
	week.monday = dateObj.dateAdd('d', 1-theday) / 1000;
    week.sunday = dateObj2.dateAdd('d', 7-theday) / 1000;
	return week;
}
//timestamp  时间戳 秒
function getLastDay(timestamp){
	var dateObj = (new Date(timestamp * 1000));
	var s = "";
	s += dateObj.getFullYear()+"/";
	s += (dateObj.getMonth() + 2)+"/";
	s += "01";
	var d = Date.parse(s) - 24*60*60*1000;
	return (new Date(d)).getDate();
} 
//某个月的最后一天
Date.prototype.lastDay = function(){
	var d = this;
	var s = "";
	s += d.getFullYear ()+"/";
	s += (d.getMonth()+2)+"/";  //下个月一号
	s += "01";
	return formatTime('yyyy-MM-dd hh:mm', Math.round(Date.parse(s)/1000)- 24*60*60);
}
/* 得到日期年月日等加数字后的日期 */ 
Date.prototype.dateAdd = function(interval,number) 
{ 
    var d = this; 
    var k={'y':'FullYear', 'q':'Month', 'm':'Month', 'w':'Date', 'd':'Date', 'h':'Hours', 'n':'Minutes', 's':'Seconds', 'ms':'MilliSeconds'}; 
    var n={'q':3, 'w':7}; 
    eval('d.set'+k[interval]+'(d.get'+k[interval]+'()+'+((n[interval]||1)*number)+')'); 
    return d; 
} 
/* 计算两日期相差的日期年月日等 */ 
Date.prototype.dateDiff = function(interval,objDate2) 
{ 
    var d=this, i={}, t=d.getTime(), t2=objDate2.getTime(); 
    i['y']=objDate2.getFullYear()-d.getFullYear(); 
    i['q']=i['y']*4+Math.floor(objDate2.getMonth()/4)-Math.floor(d.getMonth()/4); 
    i['m']=i['y']*12+objDate2.getMonth()-d.getMonth(); 
    i['ms']=objDate2.getTime()-d.getTime(); 
    i['w']=Math.floor((t2+345600000)/(604800000))-Math.floor((t+345600000)/(604800000)); 
    i['d']=Math.floor(t2/86400000)-Math.floor(t/86400000); 
    i['h']=Math.floor(t2/3600000)-Math.floor(t/3600000); 
    i['n']=Math.floor(t2/60000)-Math.floor(t/60000); 
    i['s']=Math.floor(t2/1000)-Math.floor(t/1000); 
    return i[interval]; 
} 
/*
 * 格式化时间 formatStr: yyyy:年 MM:月 dd:日 hh:小时 mm:分钟 ss:秒
 */
/*
Date.prototype.toString = function(formatStr) {
	var date = this;
	var timeValues = function() {
	};
	timeValues.prototype = {
		year : function() {
			if (formatStr.indexOf("yyyy") >= 0) {
				return date.getFullYear();
			} else {
				return date.getYear().toString().substr(2);
			}
		},
		elseTime : function(val, formatVal) {
			return formatVal >= 0 ? (val < 10 ? "0" + val : val) : (val);
		},
		month : function() {
			return this.elseTime(date.getMonth() + 1, formatStr.indexOf("MM"));
		},
		day : function() {
			return this.elseTime(date.getDate(), formatStr.indexOf("dd"));
		},
		hour : function() {
			return this.elseTime(date.getHours(), formatStr.indexOf("hh"));
		},
		minute : function() {
			return this.elseTime(date.getMinutes(), formatStr.indexOf("mm"));
		},
		second : function() {
			return this.elseTime(date.getSeconds(), formatStr.indexOf("ss"));
		}
	}
	var tV = new timeValues();
	var replaceStr = {
		year : [ "yyyy", "yy" ],
		month : [ "MM", "M" ],
		day : [ "dd", "d" ],
		hour : [ "hh", "h" ],
		minute : [ "mm", "m" ],
		second : [ "ss", "s" ]
	};
	for ( var key in replaceStr) {
		formatStr = formatStr.replace(replaceStr[key][0], eval("tV." + key
				+ "()"));
		formatStr = formatStr.replace(replaceStr[key][1], eval("tV." + key
				+ "()"));
	}
	return formatStr;
}
*/


/*
 * methodType: http请求方式 sendData: 向服务器发送数据 dataType: 返回数据类型
 */
function ajaxRequest(url, successCallBack, dataType, errorCallBack, beforeSendCallBack, completeCallBack, methodType, sendData) {
	if (methodType == undefined || methodType == "" || methodType == null) {
		methodType = "GET";
	}
	if (sendData == undefined || sendData == "" || sendData == null) {
		sendData = {
			sid : Math.random()
		};
	}
	if (dataType == undefined || dataType == "" || dataType == null) {
		dataType = "json";
	}
	if (beforeSendCallBack == undefined || beforeSendCallBack == ""
			|| beforeSendCallBack == null) {
		beforeSendCallBack = new Function();
	}
	if (completeCallBack == undefined || completeCallBack == ""
			|| completeCallBack == null) {
		completeCallBack = new Function();
	}
	if (errorCallBack == undefined || errorCallBack == ""
			|| errorCallBack == null) {
		errorCallBack = new Function();
	}
	$.ajax( {
		type : methodType,
		url : url,
		async : true, // (默认: true) 默认设置下，所有请求均为异步请求。如果需要发送同步请求，请将此选项设置为 false
		data : sendData,
		cache : true, // 默认为true， false时不会从浏览器缓存中加载请求信息
		dataType : dataType,// text返回纯文本字符串
		contentType: "application/json",
		beforeSend : function() {
			beforeSendCallBack();
		},
		success : function(returnData, textStatus) {
			if (returnData != null) {
				if (returnData.errorcode == undefined
						&& returnData.error == undefined) {
					successCallBack(returnData);
				} else {
					var rd = [ {
						totalcount : 0,
						datalist : []
					} ];
					successCallBack(rd);
				}
			} else {
				errorOutPut('2001', '数据为空');
			}
		},
		complete : function(XMLHttpRequest, textStatus) {
			completeCallBack();
		},
		error : function(XMLHttpRequest, textStatus, errorThrown) {
			errorCallBack(XMLHttpRequest.responseText, errorThrown);
		}
	}); // ajax end
}
// 获取url中参数,输入参数获取键值
/*
 * 用法： url = http://localhost/../getdata.php?name=zhangsan var name =
 * commonFun.queryString("name");
 */
var commonFun = {
	// 获取url中参数,输入参数获取键值
	/*
	 * 用法： url = http://localhost/../getdata.php?name=zhangsan var name =
	 * commonFun.queryString("name");
	 */
	queryString : function(param) {
		var uri = window.location.search;
		var reg = new RegExp("(^|&)" + param + "=([^&]*)(&|$)", "i");
		var r = uri.substr(1).match(reg);
		return r ? r[2] : null;
	},
	// 删除左右两端的空格
	// alert(trim(document.getElementById('abc').value));
	// alert(trim($("#abc").value));
	trim : function(str) {
		// return str.replace(/(^\s*)|(\s*$)/g, "");
		str = str.toString().replace(/^\s+/, "");
		for ( var i = str.length - 1; i >= 0; i--) {
			if (/\S/.test(str.charAt(i))) {
				str = str.substring(0, i + 1);
				break;
			}
		}
		return str;
	},
	// 删除左边的空格
	// alert(ltrim(document.getElementById('abc').value));
	// alert(ltrim($("#abc").value));
	ltrim : function(str) {
		return str.toString().replace(/(^\s*)/g, "");
	},
	// 删除右边的空格
	// alert(rtrim(document.getElementById('abc').value));
	// alert(rtrim($("#abc").value));
	rtrim : function(str) {
		return str.toString().replace(/(\s*$)/g, "");
	},
	//数组去空值
	//add by zq:2016-6-12
	arrytrim : function(array){
		for(var i = 0 ;i<array.length;i++){
			if(array[i] == "" || typeof(array[i]) == "undefined") {
				array.splice(i,1);
				i= i-1;
			}
		}
		return array;
	},
	//end by zq:2016-6-12
	//数组去重
	arryunique:function(arry){
		var hash = {}, len = arry.length, result = [];
		for (var i = 0; i < len; i++){
			if (!hash[arry[i]]){
				hash[arry[i]] = true;
				result.push(arry[i]);
			}
		}
		return result;
	},
	//处理字符串#企业##职务#
	strsplit:function(str){
		var arry =str.split("#");
		var res=[];
		for(var i=0;i<arry.length;i++){
			if(arry[i]!=0){
				res.push(arry[i]);
			}
		};
		return res[res.length-1];
	}
};
/*
 *
var b = new Base64();  
        var str = b.encode("admin:admin");  
        alert("base64 encode:" + str);  
　　　　　//解密
        str = b.decode(str);  
        alert("base64 decode:" + str);  
 * */
function Base64() {
	// private property
	_keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
 
	// public method for encoding
	this.encode = function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
		input = _utf8_encode(input);
		while (i < input.length) {
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
			output = output +
			_keyStr.charAt(enc1) + _keyStr.charAt(enc2) +
			_keyStr.charAt(enc3) + _keyStr.charAt(enc4);
		}
		return output;
	}
 
	// public method for decoding
	this.decode = function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
		while (i < input.length) {
			enc1 = _keyStr.indexOf(input.charAt(i++));
			enc2 = _keyStr.indexOf(input.charAt(i++));
			enc3 = _keyStr.indexOf(input.charAt(i++));
			enc4 = _keyStr.indexOf(input.charAt(i++));
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
			output = output + String.fromCharCode(chr1);
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
		}
		output = _utf8_decode(output);
		return output;
	}
 
	// private method for UTF-8 encoding
	_utf8_encode = function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);
			if (c < 128) {
				utftext += String.fromCharCode(c);
			} else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			} else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 
		}
		return utftext;
	}
 
	// private method for UTF-8 decoding
	_utf8_decode = function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
		while ( i < utftext.length ) {
			c = utftext.charCodeAt(i);
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			} else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
		}
		return string;
	}
}

//对象中没有任何可读属性
function isEmptyObject(obj){
    for(var n in obj){return false} 
    return true; 
} 
function isArray(obj){   
  return Object.prototype.toString.call(obj) === '[object Array]';    
}  
//当item不存在数组arr中时,添加到数组arr中
function arrayAppend(arr, item){
	if(!arr.inArray(item)){
		arr.push(item);
	}
	return arr;
}
//当item存在数组中时,从数组删除
function arrayDelete(arr, item){
	if(arr.length > 0){
		var deletei;
		for(var i=0;i<arr.length;i++){
			if(arr[i] == item){
				deletei = i;
			}
		}
		arr.splice(deletei, 1);
	}
	return arr;
}
/*
 * 用法:itemArr.inArray(item);
 * */
Array.prototype.inArray = function(e){ 
    for(i=0;i<this.length;i++){
        if(this[i] == e){
			return true;
		}
    }
    return false;
}
/*
    工具包
	数字转中文数字
*/
var Utils={
    /*
        单位
    */
    units:'个十百千万@#%亿^&~',
    /*
        字符
    */
    chars:'零一二三四五六七八九',
    /*
        数字转中文
        @number {Integer} 形如123的数字
        @return {String} 返回转换成的形如 一百二十三 的字符串             
    */
    numberToChinese:function(number){
        var a=(number+'').split(''),s=[],t=this;
        if(a.length>12){
            throw new Error('too big');
        }else{
            for(var i=0,j=a.length-1;i<=j;i++){
                if(j==1||j==5||j==9){//两位数 处理特殊的 1*
                    if(i==0){
                        if(a[i]!='1')s.push(t.chars.charAt(a[i]));
                    }else{
                        s.push(t.chars.charAt(a[i]));
                    }
                }else{
                    s.push(t.chars.charAt(a[i]));
                }
                if(i!=j){
                    s.push(t.units.charAt(j-i));
                }
            }
        }
        //return s;
        return s.join('').replace(/零([十百千万亿@#%^&~])/g,function(m,d,b){//优先处理 零百 零千 等
            b=t.units.indexOf(d);
            if(b!=-1){
                if(d=='亿')return d;
                if(d=='万')return d;
                if(a[j-b]=='0')return '零';
            }
            return '';
        }).replace(/零+/g,'零').replace(/零([万亿])/g,function(m,b){// 零百 零千处理后 可能出现 零零相连的 再处理结尾为零的
            return b;
        }).replace(/亿[万千百]/g,'亿').replace(/[零]$/,'').replace(/[@#%^&~]/g,function(m){
            return {'@':'十','#':'百','%':'千','^':'十','&':'百','~':'千'}[m];
        }).replace(/([亿万])([一-九])/g,function(m,d,b,c){
            c=t.units.indexOf(d);
            if(c!=-1){
                if(a[j-c]=='0')return d+'零'+b;
            }
            return m;
        });
    }
};

/*共用的回调函数
 *totalCount   int, 页面总条数
 *callFun Function，发送ajax请求的函数名，
 *pagebox string 显示页码的div属性id
 */
function pageDisplay(totalCount, callFun, pagebox, ipagesize, curpage2, pid, param)
{
	var pagenum =5;//显示的页码数
	$("#"+pagebox).empty();
	if (curpage2 != 1){
		$("<a/>", {
			text:"上一页 ",
			style:"padding:2px;border:1px solid #cccccc;cursor:pointer;margin:1px;",
			click: function(){
				var curpage3 = parseInt(curpage2-1); 
				callFun(ipagesize, curpage3 ,pagebox, pid, param);
			}
		}).appendTo("#"+pagebox);
	}
	var totalpage = Math.ceil(totalCount/ipagesize);
	if(totalpage>10){
		if(curpage2>totalpage){
			curpage2 = totalpage;
		}
		var tmp1;
		var tmp2;

		tmp1 = parseInt(pagenum/2);
		tmp2 = pagenum%2==0?tmp1-1:tmp1;

			//当前页数小于或等于显示页码减去末尾项,当前位置还处于页码范围 
			if(curpage2<=pagenum-tmp2){ 
				startpage=1;
				endpage=pagenum;
			}
			else{	
				startpage = parseInt(curpage2, 10) - parseInt(tmp1, 10);
				endpage = parseInt(curpage2, 10) + parseInt(tmp2,10);	
			}
			/*当计算出来的末尾项大于总页数*/
			if(endpage>totalpage){
				startpage=(totalpage-pagenum)+1;//开始项等于总页数减去要显示的数量然后再自身加1
				endpage=totalpage;	
			}
			for(var i=startpage;i<=endpage;i++){
				if(curpage2==i){
					$("<a/>", {
						text:i,
						style:"background-color:#EA3D3D;padding:2px;border:1px solid #cccccc;cursor:pointer;margin:1px;",
						click: function(){
							var curpage2 = $(this).text(); 
						}
					}).appendTo("#"+pagebox);
				}
				else{
					$("<a/>", {
						text:i,
						style:"padding:2px;border:1px solid #cccccc;cursor:pointer;margin:1px;",
						click: function(){
							var curpage3 = parseInt($(this).text()); 
							callFun(ipagesize, curpage3 ,pagebox, pid, param);
						}
					}).appendTo("#"+pagebox);
				}
			}
	}
	else{
		for(var i=1;i<=totalpage;i++){
			if(curpage2==i){
				$("<a/>", {
					text:i,
					style:"background-color:#EA3D3D;padding:2px;border:1px solid #cccccc;cursor:pointer;margin:1px;",
					click: function(){
						var curpage2 = $(this).text(); 
					}
				}).appendTo("#"+pagebox);
			}
			else{
				$("<a/>", {
					text:i,
					style:"padding:2px;border:1px solid #cccccc;cursor:pointer;margin:1px;",
					click: function(){
						var curpage3 = parseInt($(this).text()); 
						callFun(ipagesize, curpage3 ,pagebox, pid, param);
					}
				}).appendTo("#"+pagebox);
			}
		}
	}
	if(curpage2 != totalpage){
		$("<a/>", {
			text:" 下一页",
			style:"padding:2px;border:1px solid #cccccc;cursor:pointer;margin:1px;",
			click: function(){
				var curpage3 = parseInt(curpage2+1); 
				callFun(ipagesize, curpage3 ,pagebox, pid, param);
			}
		}).appendTo("#"+pagebox);
	}
	var thtml = "<span><input type='text' id='"+pagebox+"_page_goto' style='width:35px;'><input type='button' id='"+pagebox+"_page_btn' value='GO'>共"+totalpage+"页 共"+totalCount+"条</span>";
	$("#"+pagebox).append(thtml);
	$("#"+pagebox+"_page_btn").die("click");
	$("#"+pagebox+"_page_btn").live("click", function(){
		var page_goto = $("#"+pagebox+"_page_goto").val();
		if(!page_goto || parseInt(page_goto, 10) > parseInt(totalpage, 10)){
			alert('请输入正确页码!');
		}
		else{
			callFun(ipagesize, page_goto ,pagebox, pid, param);
		}
	});
}
/*
function clone(object){
    function F(){};
    F.prototype = object;
    return new F;
}
*/
// 深度克隆,子级中包含父级
function deepClone(source) {
	if (source === null) {
		return null;
	}
	if (source === undefined) {
		return undefined;
	}
	if (typeof (source) != 'object') {
		return source;
	}
	if (source.constructor == Array) {
		var ret = [];
		for ( var i = 0; i < source.length; i++) {
			ret[i] = arguments.callee(source[i]);
		}
	} else {
		var ret = {};
		for ( var d in source) {
			if (source[d] === null) {
				ret[d] = null;
				continue;
			}
			if (source[d] === undefined) {
				ret[d] = undefined;
				continue;
			}
			if (typeof (source[d]) != 'object') {
				ret[d] = source[d];
				continue;
			}
			if (source[d].constructor == Array) {
				ret[d] = [];
				for ( var j = 0; j < source[d].length; j++) {
					if (source[d][j] === null) {
						ret[d][j] = null;
						continue;
					}
					if (source[d][j] === undefined) {
						ret[d][j] = undefined;
						continue;
					}
					if (typeof (source[d][j]) != 'object') {
						ret[d][j] = source[d][j];
						continue;
					}
					if (source[d][j].constructor == Array) {
						ret[d][j] = arguments.callee(source[d][j]);
						continue;
					}
					if (source[d][j] === source) {
						ret[d][j] = ret;
					}
					else {
						var recur = null;
						for ( var p in source[d][j]) {
							if (source[d][j][p] === source) { //判断children中是否有parent,有parent时会陷入死循环
								recur = p;
								break;
							}
						}
						if (recur) {
							source[d][j][recur] = null;
						}
						ret[d][j] = arguments.callee(source[d][j]);
						if (recur) {
							source[d][j][recur] = source;
							ret[d][j][recur] = ret;
						}
					}
				}
			} else {
				ret[d] = arguments.callee(source[d]);
			}
		}
	}
	return ret;
}

/*
 * 获取url中的二级目录 url: 传递window.location.pathname 例：url
 * 为http://www.inter3i.com/secdir/index.html 时 本函数获取“secdir” 字符串，无二级目录时，返回null
 */
function getSecondDir(url) {
	if (url == null || url == "") {
		return null;
	}
	var reg = /^\/(\w+)($|\/+.*)/i;
	var t = url.match(reg);
	if (t != null) {
		return t[1];
	} else {
		return null;
	}

}

//同步请求
function syncAjax(url,dataType,data,method,successCallback,errorCallback){
	var ajaxParams = {};
	ajaxParams.url = url;
	ajaxParams.dataType = dataType;
	ajaxParams.data = data ? data : {};
	ajaxParams.type = method ? method : "get";
	ajaxParams.async = false;
	ajaxParams.success = successCallback ? successCallback : function(){};
	ajaxParams.error = errorCallback ? errorCallback : function(){};
	$.ajax(ajaxParams);
}

/**
 * 使用正则验证正整数
 * @param value 值
 * @param len  最大长度
 */
function testPositiveInt(value,len){
	if(value.length > len){
		return false;
	}
	var reg = new RegExp(/^\d*$/g); //  /^[0-9]\d*$/g
	return reg.test(value);
}
function isUrl(str_url){      
    var strRegex = "^((https|http|ftp|rtsp|mms)?://)"       
                    + "?(([0-9a-zA-Z_!~*'().&=+$%-]+: )?[0-9a-zA-Z_!~*'().&=+$%-]+@)?" //ftp的user@      
                    + "(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP形式的URL- 199.194.52.184      
                    + "|" // 允许IP和DOMAIN（域名）      
                    + "([0-9a-zA-Z_!~*'()-]+\.)*" // 域名- www.      
                    + "([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\." // 二级域名      
                    + "[a-zA-Z]{2,6})" // first level domain- .com or .museum      
                    + "(:[0-9]{1,4})?" // 端口- :80      
                    + "((/?)|"       
                    + "(/[0-9a-zA-Z_!~*'().;?:@&=+$,%#-]+)+/?)$";      
    var re=new RegExp(strRegex);      
    return re.test(str_url);      
}   
/*
* 新浪微博mid与url互转实用工具
*/
var WeiboUtility = {};
 
//62进制字典
WeiboUtility.str62keys = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

/**
* 10进制值转换为62进制
* @param {String} int10 10进制值
* @return {String} 62进制值
*/
WeiboUtility.int10to62 = function(int10) {
    var s62 = '';
    var r = 0;
    while (int10 != 0) {
        r = int10 % 62;
        s62 = this.str62keys.charAt(r) + s62;
        int10 = Math.floor(int10 / 62);
    }
    return s62;
};
 
/**
* mid转换为URL字符
* @param {String} mid 微博mid，如 "201110410216293360"
* @return {String} 微博URL字符，如 "wr4mOFqpbO"
*/
WeiboUtility.mid2url = function(mid) {
    if (typeof (mid) != 'string') return false; //mid数值较大，必须为字符串！
    var url = '';
    for (var i = mid.length - 7; i > -7; i = i - 7) //从最后往前以7字节为一组读取mid
    {
        var offset1 = i < 0 ? 0 : i;
        var offset2 = i + 7;
        var num = mid.substring(offset1, offset2);
		var tmpnum = this.int10to62(num);
		if(num.length==7){
			while(tmpnum.length < 4){
				tmpnum = "0"+tmpnum;
			}
		}
        url = tmpnum + url;
    }
 
    return url;
};
 
/**
* 62进制值转换为10进制
* @param {String} str62 62进制值
* @return {String} 10进制值
*/
WeiboUtility.str62to10 = function(str62) {
    var i10 = 0;
    for (var i = 0; i < str62.length; i++) {
        var n = str62.length - i - 1;
        var s = str62.substr(i,1);  // str62[i]; 字符串用数组方式获取，IE下不支持，显示为“undefined”
 
        i10 += this.str62keys.indexOf(s) * Math.pow(62, n);
    }
    return i10;
};
 
/**
* URL字符转换为mid
* @param {String} url 微博URL字符，如 "wr4mOFqpbO"
* @return {String} 微博mid，如 "201110410216293360"
*/
WeiboUtility.url2mid = function(url) {
    var mid = '';
 
    for (var i = url.length - 4; i > -4; i = i - 4) //从最后往前以4字节为一组读取URL字符
    {
        var offset1 = i < 0 ? 0 : i;
        var offset2 = i + 4;
        var str = url.substring(offset1, offset2);
 
        str = this.str62to10(str).toString(10);
        if (offset1 > 0) //若不是第一组，则不足7位补0
        {
            while (str.length < 7) {
                str = '0' + str;
            }
        }
 
        mid = str + mid;
    }
    return mid;
};

WeiboUtility.weibomid2url = function(sourceid, userid,mid) {
	var url = "";
	switch(parseInt(sourceid,10)){
		case 1:
			var enmid = WeiboUtility.mid2url(mid);
			url = "http://weibo.com/"+userid+"/"+enmid;
			break;
		default:
			break;
	}
	return url;
};
function getHighIntegrity(number){
    var numstr = parseInt(number, 10).toString();
    var retstr = "";
    for(var i=0;i<numstr.length;i++){
        if(i==0){
            var f = numstr.substr(i,1);
            retstr += parseInt(f, 10)+1;
        }
        else{
            retstr += "0";
        }
    }
    return parseInt(retstr, 10);
};
//获取字符串的最大高度
function getStringMaxHeight(strText, fontSize){
	var strlen = strText.length;
	var maxh = 0;
	for(var i=0; i< strlen; i++){
		var fs = 0;
		if((strText.charCodeAt(i)>= 0x4e00 && strText.charCodeAt (i) <= 0x9fa5) || (strText.charCodeAt(i)>= 0xff00 && strText.charCodeAt (i) <= 0xffff)){//中文
			fs = fontSize;
		}
		else if(strText.charCodeAt(i)>=65 && strText.charCodeAt(i)<=90){//大写英文
			fs = fontSize * 2/3;
		}
		else if(strText.charCodeAt(i)>=97 && strText.charCodeAt(i)<=122){//小写英文
			fs = fontSize/2;
		}
		else if(strText.charCodeAt(i)>=48 && strText.charCodeAt(i)<=57){//数字
			fs = fontSize * 2/3;
		}
		else{ //其他字符
			fs = fontSize * 2/3;
		}
		if(fs > maxh){
			maxh = fs;
		}
	}
	return maxh;
}

//根据字符串(包括中文,英文(大小写),数字,特殊符号),计算长度
function getStringPx(strText, fontSize){
    var strpxlen = 0;
    if(typeof strText === "string"){
        var strlen = strText.length;
        for(var i=0; i< strlen; i++){
            if((strText.charCodeAt(i)>= 0x4e00 && strText.charCodeAt (i) <= 0x9fa5) || (strText.charCodeAt(i)>= 0xff00 && strText.charCodeAt (i) <= 0xffff)){//中文
                strpxlen += fontSize;
            }
            else if(strText.charCodeAt(i)>=65 && strText.charCodeAt(i)<=90){//大写英文
                strpxlen += fontSize * 2/3;
            }
            else if(strText.charCodeAt(i)>=97 && strText.charCodeAt(i)<=122){//小写英文
                strpxlen += fontSize/2;
            }
            else if(strText.charCodeAt(i)>=48 && strText.charCodeAt(i)<=57){//数字
                strpxlen += fontSize * 0.625;
            }
            else{ //其他字符
                strpxlen += fontSize * 0.4;
            }
        }
    }
    return strpxlen;
}

//获取圆周上的点
//cocx:圆心坐标
//cocy:圆心坐标y
//r:半径
//angle：角度
//return: {x:123, y:213}
function getCirclePoint(cocx,cocy,r,angle){
	var result = {x:0, y:0};
	result.x = cocx + r*Math.cos(Math.PI*angle/180);
	result.y = cocy - r*Math.sin(Math.PI*angle/180);
	return result;
}

/**  
 *  
 * @param {Function} callback 回调函数  
 * @param {Integer} delay   延迟时间，单位为毫秒(ms)，默认150  
 * @param {Object} context  上下文，即this关键字指向的对象，默认为null  
 * @return {Function}  
 */    
function debounce(callback, delay, context){    
    if (typeof(callback) !== "function") {    
        return;    
    }    
    delay = delay || 150;    
    context = context || null;    
    var timeout;    
    var runIt = function(){    
            callback.apply(context);    
        };    
    return (function(){    
        window.clearTimeout(timeout);    
        timeout = window.setTimeout(runIt, delay);    
    });    
} 
function HTMLEncode(html){ 
	/*
	var temp = document.createElement("div"); 
	(temp.textContent != null) ? (temp.textContent = html) : (temp.innerText = html); 
	var output = temp.innerHTML; 
	temp = null; 
	return output; 
	*/
    var ret = '';
    for(var i=0;i<html.length;i++){
        try{
            ret +=  encodeURIComponent(html.charAt(i));
        }
        catch(e){
            console.log(html.charAt(i));
        }
    }
	return ret;
	//return encodeURIComponent(html);
}

function HTMLDecode(text){ 
	/*
	var temp = document.createElement("div"); 
	temp.innerHTML = text; 
	var output = temp.innerText || temp.textContent; 
	temp = null; 
	return output; 
	*/
	return decodeURIComponent(text);
}

$.fn.outerHTML = function(){
    // IE, Chrome & Safari will comply with the non-standard outerHTML, all others (FF) will have a fall-back for cloning
    return (!this.length) ? this : (this[0].outerHTML || (
      function(el){
          var div = document.createElement('div');
          div.appendChild(el.cloneNode(true));
          var contents = div.innerHTML;
          div = null;
          return contents;
    })(this[0]));
};

//增加longPress（鼠标长按）事件 add by jht
//time：多少毫秒执行一次callback
//callback :按下时执行的事件
//outcallback：弹起时执行的事件
(function($) {
    $.extend($.fn, {
        longPress : function(time,callBack, outCallBack){
           time = time || 1000;
           var timer = null;
           var realExec = false;
           var timerfun = function(){
	       	    realExec = true;
	            typeof callBack == 'function' && callBack.call(this);
           };
           $(this).mousedown(function(e){
        	   realExec = false;
               timer = setInterval(timerfun,time);
           }).mouseup(function(){
	        	   clearTimeout(timer);
	        	   if(!realExec){
	        		   timerfun();
	        	   }
	        	   typeof outCallBack == 'function' && outCallBack.call(this);
           }).mouseout(function(){
	        	   clearTimeout(timer);
        		   typeof outCallBack == 'function' && outCallBack.call(this);
            });
        }
    });
}) (jQuery);
function loadImage(url, callback) {     
    var img = new Image(); //创建一个Image对象，实现图片的预下载     
    img.onload = function(){
        img.onload = null;
        callback(img);
    }
    img.src = url; 
}
/**
 * 获取url中的参数
 * @returns {Object} 以参数名为属性的对象
 */
function GetUrlParams(url) {
	if(typeof url == "undefined" || url == null || url == ""){
		return null;
	}
	var theRequest = null;
	if (url.indexOf("?") != -1) {
		var urlarr = url.split("?");
		if(urlarr.length == 2){
			theRequest = new Object()
			var str = urlarr[1];
			var strs = str.split("&");
			for(var i = 0; i < strs.length; i ++) {
				var parr = strs[i].split("=");
				if(parr.length == 2){
					theRequest[parr[0]]=decodeURIComponent(parr[1]);
				}
			}
		}
	}
	return theRequest;
}
//by函数接受一个成员名字符串和一个可选的次要比较函数做为参数
//并返回一个可以用来包含该成员的对象数组进行排序的比较函数
//当o[age] 和 p[age] 相等时，次要比较函数被用来决出高下
// employees.sort(by('age',by('name')));
var by = function(name,minor){
    return function(o,p){
        var a,b;
        if(o && p && typeof o === 'object' && typeof p ==='object'){
            a = o[name];
            b = p[name];
            if(a === b){
                return typeof minor === 'function' ? minor(o,p):0;
            }
            if(typeof a === typeof b){
                return a <b ? -1:1;
            }
            return typeof a < typeof b ? -1 : 1;
        }else{
            thro("error");
        }
    }
}