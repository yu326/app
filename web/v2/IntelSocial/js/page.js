
/**
 * 获取URL参数
 */
function getQueryString(name) {
     var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     var r = window.location.search.substr(1).match(reg);
     if(r) {
     	return  unescape(r[2]); 
     }

     return "";
}

/**
 * 判断年份是否为润年
 */
function isLeapYear(year) {
    return (year % 400 == 0) || (year % 4 == 0 && year % 100 != 0);
}

/**
 * 获取某一年份的某一月份的天数
 */
function getMonthDays(year, month) {
    return [31, null, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month] || (isLeapYear(year) ? 29 : 28);
}

/**
 * 获取某年的某天是第几周
 */
function getWeekNumber(timeValue) {
    var now = new Date(timeValue),
        year = now.getFullYear(),
        month = now.getMonth(),
        days = now.getDate();
    //那一天是那一年中的第多少天
    for (var i = 0; i < month; i++) {
        days += getMonthDays(year, i);
    }

    //那一年第一天是星期几
    var yearFirstDay = new Date(year, 0, 1).getDay() || 7;

    var week = null;
    if (yearFirstDay == 1) {
        week = Math.ceil(days / yearFirstDay);
    } else {
        days -= (7 - yearFirstDay + 1);
        week = Math.ceil(days / 7) + 1;
    }

    return week;
}

/*
*@单页面访问影射规则
*@http://127.0.0.1/intel/1.html?auth=xxx
*@http://127.0.0.1/intel/index.html?page=1&auth=xxx
*/
var env = "live";
var URL_MAP = {
	fast: {
		token: "data/token.txt?t=1",
		trendLine: "data/trendLine.txt?token=%token%",
		cityPie: "data/cityPie.txt?token=%token%",
		map: "data/map.txt?token=%token%",
		intelTag: "data/intelTag.txt?token=%token%",
		gender: "data/gender.txt?token=%token%",
		userTag: "data/userTag.txt?token=%token%",
		top10: "data/top10.txt?token=%token%",
		leftGrid: "data/leftGrid.txt?token=%token%",
		rightGrid: "data/rightGrid.txt?token=%token%",
		mediaPie: "data/mediaPie.txt?token=%token%",
		channelPie: "data/channelPie.txt?token=%token%",
		city_Pie: "data/city_Pie.txt?token=%token%",
		analysisBar:"data/analysisBar.txt?token=%token%"
	},
	
	live: {
		token:"http://demo2.3i.inter3i.com:7010/model/checkuser.php?type=gettoken&username=intel_reader&password=55f03012060cf62831e871b3af15e910",
		// token:"/model/checkuser.php?type=gettoken&username=demo3&password=f2f780f54ad732b89a91746461eedf78",
		// {"token":"c841VcgGZpsPwJ7C3zZdewf\/9dzF2CWGvD4bT1vt+QbEKjj9MWjhszY"}
		// {"token":"2824gwTsBQSU+vLVYjgDkHbJI1aV1fprtzn0Z9RX\/QrPuZAh+HHEjK4"}
		trendLine: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15223&elementid=16486&hasformjson=0&returnoriginal=0&offset=0&rows=30&token=%token%",
		cityPie: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15181&elementid=16444&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		map: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15384&elementid=16649&hasformjson=0&returnoriginal=0&offset=0&rows=38&token=%token%&needpinyin=1",
		intelTag: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15385&elementid=16650&hasformjson=0&returnoriginal=0&offset=0&rows=20&token=%token%",
		gender: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15184&elementid=16447&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		userTag: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15383&elementid=16648&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		// userTag: "/model/requestdata.php?instanceid=-2&elementid=-3&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=b7f6HRRgUjEo1QR1ARfnsd8l72xIFQzMHhD\/qQRJ0AoeKw",
		top10: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15386&elementid=16651&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%&needpinyin=1",
		leftGrid: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15424&elementid=16692&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		rightGrid: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15425&elementid=16693&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		mediaPie: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15389&elementid=16654&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		channelPie: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15390&elementid=16655&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		city_Pie: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15298&elementid=16562&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%",
		analysisBar: "http://demo2.3i.inter3i.com:7010/model/requestdata.php?instanceid=15388&elementid=16653&hasformjson=0&returnoriginal=0&offset=0&rows=10&token=%token%&needpinyin=1"
		// mediaPie: "data/mediaPie.txt?token=dc62LcQ+lug0Eb\/Jpcv20D0PXhSs437ZMFqX\/u8E42xxP7kSc3FsLv8",
		// channelPie: "data/channelPie.txt?token=dc62LcQ+lug0Eb\/Jpcv20D0PXhSs437ZMFqX\/u8E42xxP7kSc3FsLv8",
		// city_Pie: "data/city_Pie.txt?token=dc62LcQ+lug0Eb\/Jpcv20D0PXhSs437ZMFqX\/u8E42xxP7kSc3FsLv8",
		// analysisBar:"data/analysisBar.txt?token=dc62LcQ+lug0Eb\/Jpcv20D0PXhSs437ZMFqX\/u8E42xxP7kSc3FsLv8"
	}
};


/*
* @Page Base
**/
function Page(token, id) {
	this.token = token;
	this.id = id;
	this.rendered = false;
}

Page.resetHeight = function(element) {
	if(screen.width <= 1336) {
		return;
	}

	var baseHeight = element.height();
	var newHeight = (baseHeight*screen.availHeight)/680;
	element.css("height", newHeight);
};

Page.prototype.setToken = function(token) {
	this.token = token;
};

Page.prototype.init = function() {};

Page.prototype.request = function(key, handler) {
	var url = URL_MAP[env][key].replace("%token%", this.token);
	$.get(url +"&_t="+ (new Date()).getTime(), $.proxy(handler, this));
};

Page.prototype.complete = function(directShow) {
	this.strut();
	if(this.rendered) {
		return;
	}

	this.rendered = true;
	$(".loading").hide();
	
	if(Page.singleModel || directShow) {
		this.element.css({left: "0px"});
	} else {
		this.element.animate({left: "0px"}, 1000);
	}
};

Page.prototype.strut = function() {
	if(this.id == 5) {
		this.showFiveText();
	}else if(this.id==6){
		this.showUpdate_7();
	}else{
		this.showCommonText();
	}
};

Page.prototype.show = function() {
	this.element.show();
	if(this.rendered) {
		this.strut();
		this.element.animate({left: "0px"}, 1000);
	} else {
		$(".loading").show();
		this.init();
	}
};

Page.prototype.hide = function() {
	var element = this.element;
	element.animate({left: "-3000px"}, 1000, "linear", function() {
		element.css({left: "3000px", display: "none"});
	});
};

Page.prototype.showFiveText = function() {
	$("#common-text").hide();
	$("#five-text").show();
	$("#update-text-7").hide();
	$("#update-text-last").hide();
	$("#footer").css("position", "static");
};

Page.prototype.showCommonText = function() {
	$("#common-text").show();
	$("#five-text").hide();
	$("#update-text-7").hide();
	$("#update-text-last").hide();
	$("#footer").css("position", "fixed");
};

Page.prototype.showUpdate_7=function(){
	$("#common-text").hide();
	$("#update-text-7").show();
	$("#five-text").hide();
	$("#update-text-last").hide();
	$("#footer").css("position", "fixed");
};
Page.prototype.showUpdata_last=function(){
	$("#common-text").hide();
	$("#update-text-7").hide();
	$("#five-text").hide();
	$("#update-text-last").show();
	$("#footer").css("position", "static");
}

/*
* @Page 1
**/
function Page1(token) {
	Page.call(this, token, 1);
	this.element = $("#page-1");
}

Page1.prototype = $.extend({}, Page.prototype);

Page1.prototype.build = function() {
	var html = [];
	html.push("<h2>Social Buzz</h2>");
	html.push("<table class='content'>");
	html.push("<tr><th>Buzz Volume</th><th>City Tier</th></tr>");
	html.push("<tr><td class='line-wrapper' valign='top'><div class='line' ></div></td><td class='pie-wrapper'><div class='pie'></div></td></tr>");
	html.push("</table>");

	this.element.html(html.join(""));
};

Page1.prototype.init = function() {
	this.build();
	this.requestTrendLine();
	this.requestCityPie();
};

Page1.prototype.handleLineData = function(data) {
	this.lineData = [];
	this.pointData = [];

	var datalist = data.datalist;
	if(!datalist) {
		return;
	}

	var totalValue = 0;
	for(var i=0;i<datalist.length;i++) {
		var item = datalist[i];
		var time = parseInt(item.col2) * 1000;
		this.lineData.push([time, item.frq, item.col3]);
		totalValue += item.frq;
		if(item.col3) {
			lastIndex = i;
			this.pointData.push({title: item.col3, fillColor: "#0585bf", color: "#0585bf", y: item.frq, x: time});
		}
	}

	if(datalist.length > 0) {
		this.avgValue = totalValue/datalist.length+650;
	}
};

Page1.prototype.requestTrendLine = function() {
	this.request("trendLine", function(responseText) {
		eval("var response = "+ responseText);
		this.handleLineData(response[0]);
		this.createLine();
	
		this.complete(true);
	});
};

Page1.prototype.requestCityPie = function() {
	this.request("cityPie", function(responseText) {
		eval("var response = "+ responseText);
		var datalist = response[0].datalist;
		this.pieData = [];
		for(var i=0;i<datalist.length;i++) {
			var item = datalist[i];
			this.pieData.push({
				name: item.text,
				value: item.frq
			});
		}

		this.createPie();
		this.complete(true);
	});
};

Page1.prototype.createPie = function() {
	var option = {
		color : [ '#c9ebff', '#85cdfd', '#50affd',
				'#268ffb', '#2070d9' ],
		title : {
			text : null
		},
		tooltip : {
			show : false,
			showContent : false
		},
		visualMap : {
			show : false,
			min : 38,
			max : 382,
			inRange : {
			// colorLightness: [0, 1]
			}
		},
		series : [ {
			name : '访问来源',
			type : 'pie',
			radius : '55%',
			center : [ '50%', '50%' ],
			data : this.pieData.sort(function(a, b) {
				return a.value - b.value
			}),
			// roseType: 'angle',
			label : {
				normal : {
					textStyle : {
						fontFamily : 'intel',
						color : '#fdfdfd',
						fontSize:22
					}
				}
			},
			labelLine : {
				normal : {
					lineStyle : {
						color : '#fdfdfd'
					},
					smooth : 0.2,
					length : 10,
					length2 : 20
				}
			},
			itemStyle : {
				normal : {
					// color: '#c23531',
					shadowBlur : 200,
					shadowColor : 'rgba(0, 0, 0, 0.3)'
				}
			}
		} ]
	};
	
	var chartElement = this.element.find(".pie");
	Page.resetHeight(chartElement);
	var myChart = echarts.init(chartElement.get(0));
	myChart.setOption(option);
};

Page1.prototype.createLine = function() {
	var leftYA =-(this.element.find(".line").width() -150);
	var leftXA = (this.element.find(".line").width() - 250);
	var marginRight = 10;
	if(this.lineData && this.lineData.length > 10) {
		var j = 0;
		for(var i=this.lineData.length-1; i>0; i--) {
			if(j > 3) {
				break;
			}

			if(this.lineData[i][2]) {
				marginRight = 100;
				break;
			}
			j++;
		}
	}

	this.element.find(".line").highcharts('StockChart', {
		chart : {
			backgroundColor : 'rgba(0,0,0,0)',
			marginLeft : 170
		},
		rangeSelector : {
			selected : 5,
			enabled : false
		},
		scrollbar : {
			enabled : false
		},
		credits : {
			enabled : false
		},
		title : {
			text : null
		},
		lengend : {
			enabled : false
		},
		plotOptions : {
			areaspline : {
				fillColor : {
					linearGradient : {
						x1 : 0,
						y1 : 0,
						x2 : 0,
						y2 : 1
					},
					stops : [
						[ 0,'#1f80c2' ],[ 1,'rgba(31,128,194,0)'] 
					]
				},
				marker : {
					enabled : true,
					radius : 4,
					fillColor : '#fff',
					lineColor : '#1c83bb',
					lineWidth : 3
				},
				lineWidth : 2,
				lineColor : '#0e93dd',
				threshold : null
			}
		},
		navigator : {
			enabled : false
		},
		xAxis : {
			tickInterval: 24 * 3600 * 1000 * 7,
			labels : {
				enabled : true,
				formatter: function() {
					return "Week "+ getWeekNumber(this.value);
				},
				style: {
					color: '#fff',
					fontFamily : 'intel',
					fontSize: 18
					
				}
			}
		},
		yAxis : {
			max:22000,min:0,
			floor : 0,
			ceiling : 30000,
			gridLineWidth : 0,
			labels : {
				align : 'right',
				x : leftYA,
				style : {
					color : '#fdfdfd'
				},
				formatter : function() {
					if (this.value > 999 && this.value <= 9999) {
						var s = ""+ this.value;
						s = s.substring(0,1) + ","+ s.substring(1,4);
						return s;
					}
					if (this.value > 9999) {
						var s = ""+ this.value;
						s = s.substring(0,2)+ ","+ s.substring(2);
						return s;
					}
					return this.value;

				}
			},
			plotLines : [ {
				value : this.avgValue,
				color : 'white',
				dashStyle : 'shortdash',
				width : 1,
				label : {
					text : 'AVG Q4',
					x : leftXA,
					style : {
						color : '#fdfdfd',
						fontFamily : 'intel',
						fontSize : '14px'
					}
				}
			} ]
		},
		tooltip : {
			enabled : false,
			crosshairs : false
		},
		series : [{
			type : 'areaspline',
			data : this.lineData,
			tooltip : {
				valueDecimals : 2
			},
			states : {
				hover : {
					enabled : false
				}
			}
		}, {
			type : 'flags',
			data : this.pointData,
			style : {
				fontFamily : 'intel',
				color : '#fff',
				textAlign : 'center',
				fontSize : '12px'
			},

			onSeries : 'dataseries',
			shape : 'squarepin',
			y : -90,
			states : {
				hover : {
					enabled : false
				}
			}
		} ]
	});
};

/*
* @Page 2
**/
function Page2(token) {
	Page.call(this, token, 2);
	this.minValue = 0;
	this.maxValue = 0;
	this.element = $("#page-2");
}

Page2.prototype = $.extend({}, Page.prototype);

Page2.prototype.build = function() {
	var html = [];
	html.push("<h2>Buzz Location</h2>");
	html.push("<table class='content'>");
	html.push("<tr><th></th><th>Location Top10</th></tr>");
	html.push("<tr><td style='width:62%;'><div class='map'></div></td><td><div class='top10'></div></td></tr></table>");

	this.element.html(html.join(""));
};

Page2.prototype.handleData = function(data) {

	var array = data.datalist;
	if(!data || !array) {
		return;
	}
	array.sort(function(a,b){return b.frq-a.frq;})
	this.mapData = [];
	this.barNames = [];
	this.barValues = [];
	for(var i=0;i<array.length;i++) {
		var entity = array[i];
		var d = {
			name: entity.text,
			value: entity.frq,
		};
		this.mapData.push(d);
		if(i < 10) {
			this.barNames.push(d.name);
			this.barValues.push(d.value);
		}
	}
	this.barNames.reverse();
	this.barValues.reverse();

	if(this.mapData.length > 0) {
		this.minValue = this.mapData[0].value;
		this.maxValue = this.mapData[this.mapData.length-1].value;
	}
};
Page2.prototype.init = function() {
	this.build();
	this.request("map", function(responseText) {
		var result = JSON.parse(responseText)
		this.handleData(result[0]);
		
		this.createMap();
		Page2.createTop(this.barNames, this.barValues, this.element);
		this.complete();
	});
};

Page2.prototype.createMap = function() {
	
	// this.barNames.sort(function(a,b){return a-b});
	// this.barValues.sort(function(a,b){return a-b});
	// if(this.mapData.length > 0) {
	// 	this.minValue = this.mapData[2].value;
	// 	this.maxValue = this.mapData[this.mapData.length-1].value;
	// }
	// alert(this.minValue);
	// alert(this.maxValue);
	var option = {
		title : {
			show : false
		},
		tooltip : {
			show : false,
			showContent : false
		},
		legend : {
			show : false,
			data : [ 'xxx' ]
		},
		visualMap : {
			type : 'continuous',
			min : this.minValue,
			max : this.maxValue,
			left : '5%',
			top : '53%',
			text : [ 'High', 'Low' ],
			calculable : false,
			realtime : false,
			color : [ '#145493', '#b0dff4' ],
			textStyle : {
				color : '#fff',
				fontSize : 14,
				fontFamily : 'intel'
			}
		},
		toolbox : {
			show : false
		},
		series : [ {
			name : 'xxx',
			type : 'map',
			mapType : 'china',
			label : {
				normal : {
					show : true
				},
				emphasis : {
					show : true
				}
			},
			data : this.mapData
		} ]
	};

	var chartElement = this.element.find(".map");
	Page.resetHeight(chartElement);
	var myChart = echarts.init(chartElement.get(0));
	myChart.setOption(option);
};

Page2.createTop = function(barNames, barValues, element) {
	var option = {
		color : [ '#1f80c2' ],
		grid : {
			borderWidth : 0,
			left: 100
		},
		legend : {
			data : [ 'xxx' ],
			show : false
		},
		toolbox : {
			show : false
		},
		calculable : false,
		xAxis : [ {
			type : 'value',
			boundaryGap : [ 0, 0 ],
			splitLine : {
				show : false
			},
			axisLine : {
				show : false
			},
			axisLabel : {
				show : false
			},
			axisTick : {
				show : false
			}
		} ],
		yAxis : [ {
			type : 'category',
			data : barNames,
			splitLine : {
				show : false
			},
			axisLine : {
				show : false
			},
			axisTick : {
				show : false
			},
			axisLabel : {
				show : true,
				textStyle : {
					color : '#fff',
					fontSize : 16,
					fontFamily : 'intel'
				}
			}
		} ],
		series : [ {
			name : 'Count',
			type : 'bar',
			data : barValues,
			barWidth : 26,
			itemStyle : {
				normal : {
					barBorderRadius : [ 0,
							10, 10, 0 ],
					// label : {show: true,
					// position:
					// 'insideRight'},
					color : function(params) {
						// build a color map
						// as your need.
						var colorList = [
								'#b0dff4',
								'#52b8e7',
								'#1f80c2',
								'#145493',
								'#145493',
								'#145493',
								'#145493',
								'#145493',
								'#145493',
								'#145493' ];
						return colorList[params.dataIndex]
					}
				},
				emphasis : {
					barBorderRadius : [ 0,
							10, 10, 0 ]
				}
			}
		} ]
	};
	
	var chartElement = element.find(".top10");
	Page.resetHeight(chartElement);
	var myChart = echarts.init(chartElement.get(0));
	myChart.setOption(option);
};

/*
* @Page 3
**/
function Page3(token) {
	Page.call(this, token, 3);
	this.element = $("#page-3");
};

Page3.prototype = $.extend({}, Page.prototype);

Page3.prototype.build = function() {
	var html = [];
	html.push("<h2>Keywords Cloud</h2>");
	html.push("<div class='content'><div class='tag'></div></div>");

	this.element.html(html.join(""));
};

Page3.prototype.handleData = function(data) {
	var array = data.datalist;
	if(!data || !array) {
		return;
	}

	this.tagData = [];
	for(var i=0;i<array.length;i++) {
		var entity = array[i];
		var d = {
			text: entity.text,
			weight: (array.length - i) * 10000 - i * 1000
		};
		this.tagData.push(d);
	}
};

Page3.prototype.init = function() {
	this.build();
	this.request("intelTag", function(responseText) {
		eval("var response="+ responseText);
		this.handleData(response[0]);
		Page3.createTag(this.tagData, this.element);

		this.complete();
	});
};

Page3.createTag = function(tagData, element) {
	var chartElement = element.find(".tag");
	chartElement.jQCloud(tagData);
	Page.resetHeight(chartElement);
};

/*
* @Page 4
**/
function Page4(token) {
	Page.call(this, token, 4);
	this.element = $("#page-4");
};

Page4.prototype = $.extend({}, Page.prototype);

Page4.prototype.build = function() {
	var html = [];
	html.push("<h2>Social Media Personas</h2>");
	html.push("<table class='content'>");
	html.push("<tr><th>Gender</th><th>User Tags</th><th>Location Top10</th></tr>");
	html.push("<tr><td class='person'></td><td class='tag-wrapper'><div class='tag'></div></td><td><div class='top10'></div></td></tr>");
	html.push("</table>");

	this.element.html(html.join(""));
};

Page4.prototype.handleTagData = function(data) {
	var array = data.datalist;
	if(!data || !array) {
		return;
	}
	
	this.tagData = [];
	for(var i=0;i<array.length;i++) {
		var entity = array[i];
		var d = {
			text: entity.text,
			weight: (array.length - i) * 10000 - i * 1000
			//weight: entity["frq"]
		};
		this.tagData.push(d);
	}
};

Page4.prototype.handleTop10Data = function(data) {
	Page2.prototype.handleData.call(this, data);
};

Page4.prototype.init = function() {
	this.build();
	this.request("userTag", function(responseText) {
		eval("var response = "+ responseText);
		this.handleTagData(response[0]);
		Page3.createTag(this.tagData, this.element);

		this.complete();
	});

	this.request("top10", function(responseText) {
		eval("var response = "+ responseText);
		this.handleTop10Data(response[0]);
		Page2.createTop(this.barNames, this.barValues, this.element);

		this.complete();
	});

	this.request("gender", function(responseText) {
		eval("var response = "+ responseText);
		var datalist = response[0].datalist;

		var options = {
			"男": {
				img: "http://intel.inter3i.com/IntelSocial/images/male.png",
				color: "#1f80c2"
			},
			"女": {
				img: "http://intel.inter3i.com/IntelSocial/images/female.png",
				color: "#52b8e7"
			}
		};
		var html = [];
		html.push("<table class='gender'>");
		html.push("<tr>");
		for(var i=0;i<datalist.length;i++) {
			var item = datalist[i];
			var option = options[item.text];
			html.push("<td align='center' style='color:"+ option.color +";'>");
			html.push("<p style='padding:0;margin:0;'><img src='"+ option.img +"' /></p>");
			html.push("<p style='padding-top:50px;margin:0;'>"+ item.frq +"%</p>");
			html.push("</td>");
		}
		html.push("</tr>");
		html.push("</table>");

		this.element.find(".person").html( html.join("") );

		this.complete();
	});
};

/*
* @Page 5
**/


function Page5(token) {
	Page.call(this, token, 5);
	this.element = $("#page-5");
	this.fieldlist = [
		{
			"text":"Offical Account",
			"value":"text"
		},
		{
			"text":"Weekly Content #",
			"value":"frq"
		},
		{
			"text":"Total Read",
			"value":"col2"
		},
		{
			"text":"NRI",
			"value":"col3"
		},
		{
			"text":"logo",
			"value":"col4"
		}
	];
}

Page5.prototype = $.extend({}, Page.prototype);

Page5.prototype.build = function() {
	var html = [];
	html.push("<h2>WeChat Performance Monitor</h2>");
	html.push("<table class='content'>");
	html.push("<tr><td class='family'></td><td class='partner'></td></tr>");
	html.push("</table>");

	this.element.html(html.join(""));
};

Page5.prototype.init = function() {
	this.build();
	this.request("leftGrid", function(responseText) {
		eval("var response = "+ responseText);
		this.leftData = {
			fieldlist: this.fieldlist,
			snapshot: response
		};
		this.createGrid(this.leftData, this.element.find(".family"));

		this.complete();
	});

	this.request("rightGrid", function(responseText) {
		eval("var response = "+ responseText);
		this.rightData = {
			fieldlist: this.fieldlist,
			snapshot: response
		};
		this.createGrid(this.rightData, this.element.find(".partner"));

		this.complete();
	});
};
Page5.prototype.createGrid = function(data, wrapper) {
	var columns = data.fieldlist;
	if(!columns) {
		return;
	}
	
	var html = [];
	html.push('<table border="0" cellpadding="0" cellspacing="0" class="grid">');
	
	// 表头
	html.push('<tr class="table-title">');
	html.push('<th>Group</th>')
	html.push('<th colspan="2" nowrap>Official Account</th>');
	html.push("<th nowrap style='text-align:left;'>Weekly Content #</th>");
	html.push('<th nowrap>Total Read</th>');
	html.push('<th nowrap>NRI</th>');
	html.push('</tr>');

	// 表体
	var snapshot = data.snapshot;
	for(var i=0;i<snapshot.length;i++) {
		var ss = snapshot[i];
		this.createRows(ss, html);
	}
	
	html.push("</table>");
	wrapper.html( html.join("") );
};

Page5.prototype.createRows = function(data, html) {
	var rows = data.datalist;

	for(var i=0;i<rows.length;i++) {
		var row = rows[i];

		if(i == rows.length-1) {
			html.push("<tr class='split'>");
		} else {
			html.push("<tr>");	
		}

		if(i == 0) {
			html.push("<td rowspan='"+ rows.length +"' style='border-bottom: 1px solid #145493;' class='TD1'>");
			html.push(data.categoryname);
			html.push("</td>");
		}
function parseNum(num){  
    var list = new String(num).split('').reverse();  
    for(var i = 0; i < list.length; i++){  
        if(i % 4 == 3){  
            list.splice(i, 0, ',');  
        }  
    }  
    return list.reverse().join('');  
};
// alert(rows["col2"]);
		html.push("<td width=15%><img src='"+ row["col4"] +"' /></td>");
		html.push("<td id='TD1' style='text-align:left;'width=15%>"+ row["text"] +"</td>");
		html.push("<td id='TD3'style='text-align:left;'width=15%>"+ row["frq"] +"</td>");
		html.push("<td id='TD2'>"+ parseNum(row["col2"])+"</td>");
		html.push("<td id='TD2'>"+ parseInt(row["col3"]) +"</td>");
		html.push("</tr>");	
	}
	$("#TD1").css("fontWeight","bold");

	

};



/*
* @Page 6
**/

// function Page6(token) {
// 	Page.call(this, token, 6);
// 	this.element = $("#page-6");
// };

// Page6.prototype = $.extend({}, Page.prototype);

// Page6.prototype.build = function() {
// 	var html = [];
// 	html.push("<h2>Keywords Heat Analysis</h2>");
// 	html.push("<table class='content'>");
// 	html.push("<tr><td><div class='top10'></div></td></tr></table>")

// 	this.element.html(html.join(""));
// };

// Page6.prototype.handleData=function(data){
// 		var array = data.datalist;
// 	if(!data || !array) {
// 		return;
// 	}
// 	this.barNames = [];
// 	this.barValues = [];
// 	for(var i=0;i<array.length;i++) {
// 		var entity = array[i];
// 		var d = {
// 			name: entity.text,
// 			value: entity.frq
// 		};
// 		if(i < 25) {
// 			this.barNames.push(d.name);
// 			this.barValues.push(d.value);
// 		}
// 	}
	
// };

// Page6.prototype.init = function() {
// 	this.build();
// 	this.request("analysisBar", function(responseText) {
// 		eval("var response = "+ responseText);
// 		this.handleData(response[0]);
// 		Page6.createTop(this.barNames, this.barValues, this.element);

// 		this.complete();
// 	});
// };
// Page6.createTop = function(barNames, barValues, element) {
// 	// formatDate=function(datestring){
// 	// 		if(datestring.length!=8) return;
// 	// 		return datestring.substring(2,4)+'/'+datestring.substring(4,6)+'/'+datestring.substring(6,8);
// 	// 	},
// 	var option = {
// 		color : [ '#1f80c2' ],
// 		legend : {
// 			data : [ 'xxx' ],
// 			show : false
// 		},
// 		toolbox : {
// 			show : false
// 		},
// 		calculable : false,
// 		xAxis : [ { 
// 			type : 'category',
// 			show:true,
// 			data:barNames,
// 			axisLabel: {
// 		　　show: true,
// 			interval:0,
// 			rotate:60,
// 		　　textStyle: {
// 		　　color: '#fff',
// 			fontSize:18
// 		　　}},
// 			splitLine:{show: false},
// 			axisLine:{show:true,lineStyle:{color:'#fff',type:'solid'}}
// 		} ],
// 		yAxis : [ {
// 			type : 'value',
// 			axisLabel: {
// 		　　show: true,
// 		　　textStyle: {
// 		　　color: '#fff'
// 		　　}},
// 			splitLine:{show: false},
// 			axisLine:{show:true,lineStyle:{color:'#fff',type:'solid'}}
// 		} ],
// 		grid:{
// 			y2:100
// 		},
// 		series : [ {
// 			name : 'Count',
// 			type : 'bar',
// 			data : barValues,
// 			barWidth : 26
// 			}
			
			
// 		 ]
// 	};
	
// 	var chartElement = element.find(".top10");
// 	Page.resetHeight(chartElement);
// 	var myChart = echarts.init(chartElement.get(0));
// 	myChart.setOption(option);
// };

/*
* @Page 7
**/

function Page6(token) {
	Page.call(this, token, 6);
	this.element = $("#page-6");
}

Page6.prototype = $.extend({}, Page.prototype);

Page6.prototype.build = function() {
	var html = [];
	html.push("<h2>Social Media Distribution</h2>");
	html.push("<table class='content'>");
	html.push("<tr><th>Media Distribution</th><th>Channel Distribution</th></tr>");
	html.push("<tr class='pie-body'><td class='pie-wrapper_1'><div class='pie1' ></div></td><td class='pie-wrapper_2'><div class='pie2' ></div></td></tr>");
	html.push("</table>");
	this.element.html(html.join(""));
};
Page6.prototype.handleTagData = function(data) {
	var array = data.datalist;
	if(!data || !array) {
		return;
	}
	
	this.tagData = [];
	for(var i=0;i<array.length;i++) {
		var entity = array[i];
		var d = {
			name: entity.text,
			//value: (array.length - i) * 10000 - i * 1000,
			value: entity["frq"],
			itemStyle : {
				normal : {
					color : (i<5 ? '#08bbd1': '#cbd0c4')
				}
			}
		};
		this.tagData.push(d);
	}
};
Page6.prototype.init = function() {
	this.build();
	this.requestMediaPie();
	this.requestChannelPie();
};

Page6.prototype.requestMediaPie=function(){
	this.request("mediaPie",function(responseText){
		eval("var response="+responseText);
		var datalist=response[0].datalist;
		this.pieData=[];
		for(var i=0;i<datalist.length;i++){
			var item=datalist[i];
			this.pieData.push({
				name:item.text,
				value:item.frq
			});
		}
		this.createPie_1();
		this.complete(true);
	});
};
Page6.prototype.createPie_1= function() {
	var option = {
		color : [ '#c9ebff', '#85cdfd', '#50affd',
				'#268ffb' ],	
		title : {
			text : null
		},
		tooltip : {
			show : false,
			showContent : false
		},
		visualMap : {
			show : false,
			min : 38,
			max : 382,
			inRange : {
			// colorLightness: [0, 1]
			}
		},
		series : [ {
			name : '访问来源',
			type : 'pie',
			radius : '50%',
			center : [ '50%', '50%' ],
			data : this.pieData.sort(function(a, b) {
				return a.value - b.value
			}),
			// roseType: 'angle',
			label : {
				normal : {
					textStyle : {
						fontFamily : 'intel',
						color : '#fdfdfd',
						fontSize:22
					}
				}
			},
			labelLine : {
				normal : {
					lineStyle : {
						color : '#fdfdfd'
					},
					smooth : 0.5,
					length : 19,
					length2 : 18
				}
			},
			itemStyle : {
				normal : {
					// color: '#c23531',
					shadowBlur : 200,
					shadowColor : 'rgba(0, 0, 0, 0.3)',
					
                  labelLine:{show:true}
				}
			}
		} ]
	};
	
	var chartElement = this.element.find(".pie1");
	Page.resetHeight(chartElement);
	var myChart = echarts.init(chartElement.get(0));
	myChart.setOption(option);
};

Page6.prototype.requestChannelPie=function(){
	this.request("channelPie",function(responseText){
		eval("var response="+responseText);
		var datalist=response[0].datalist;
		this.pieData=[];
		for(var i=0;i<datalist.length;i++){
			var item=datalist[i];
			this.pieData.push({
				name:item.text,
				value:item.frq
			});
		}
		this.createPie_2();
		this.complete(true);
	});
};
Page6.prototype.createPie_2 = function() {
	var option = {
		color : [ '#c9ebff', '#85cdfd', '#50affd',
				'#268ffb'],
		title : {
			text : null
		},
		tooltip : {
			show : false,
			showContent : false
		},
		visualMap : {
			show : false,
			min : 38,
			max : 382,
			inRange : {
			// colorLightness: [0, 1]
			}
		},
		
		series : [ {
			name : '访问来源',
			type : 'pie',
			radius : '50%',
			center : [ '50%', '50%' ],
			data : this.pieData.sort(function(a, b) {
				return a.value - b.value
			}),
			// roseType: 'angle',
			
			label : {
				normal : {
					textStyle : {
						fontFamily : 'intel',
						color : '#fdfdfd',
						fontSize:22
					}
				}
			},
			labelLine : {
				normal : {
					lineStyle : {
						color : '#fdfdfd'
					},
					smooth : 0.5,
					length : 20,
					length2 : 18
				}
			},
			itemStyle : {
				normal : {
					// color: '#c23531',
					shadowBlur : 200,
					shadowColor : 'rgba(0, 0, 0, 0.3)',
					
                  labelLine:{show:true}
				}
			}
		} ]
	};
	
	var chartElement = this.element.find(".pie2");
	Page.resetHeight(chartElement);
	var myChart = echarts.init(chartElement.get(0));
	myChart.setOption(option);

};



/*
* @Page 8
**/

function Page7(token) {
	Page.call(this, token, 7);
	this.element = $("#page-7");
}

Page7.prototype = $.extend({}, Page.prototype);

Page7.prototype.build = function() {
	var html = [];
	html.push("<h2>Social Hotspot Buzz Volume</h2>");
	html.push("<div id='page8div'>");
	html.push("<img src='images/page-9.png'width='100%'height='100%' />")
	html.push("</div>")
	this.element.html(html.join(""));
};
Page7.prototype.init = function() {
	this.build();
	$(".loading").hide();
	this.element.animate({left: "0px"}, 1000);
	this.showUpdata_last();
};


/*
* @Page 9
**/

// function Page9(token) {
// 	Page.call(this, token, 9);
// 	this.element = $("#page-9");
// }

// Page9.prototype = $.extend({}, Page.prototype);

// Page9.prototype.build = function() {
// 	var html = [];
// 	html.push("<h2>Hotspot Buzz Mentioned Partner</h2>");
// 	html.push("<div id='page8div'>");
// 	html.push("<img src='images/page-8.png'width='100%'height='100%' />")
// 	html.push("</div>")
// 	this.element.html(html.join(""));
// };
// Page9.prototype.init = function() {
// 	this.build();
// 	$(".loading").hide();
// 	this.element.animate({left: "0px"}, 1000);
// 	this.showUpdata_last();
// };

/*
 * @Page Manager
**/
function PageManager() {
	this.index = 1;
	this.token = "";
};

PageManager.prototype.requestToken = function() {
	$.get(URL_MAP[env]["token"] +"&_t="+ (new Date()).getTime(), $.proxy(function(responseText) {
		eval("var response = "+ responseText);
		this.token = response.token;
		this.render();
	}, this));
};

PageManager.prototype.init = function() {
	this.page1 = new Page1();
	this.page2 = new Page2();
	this.page3 = new Page3();
	this.page4 = new Page4();
	this.page5 = new Page5();
	this.page6 = new Page6();
	this.page7 = new Page7();

	var date = new Date();
	date.setTime(date.getTime() - 60*60*24*1000);
	var array = date.toDateString().split(" ");
	$(".current-date").html(array[1] +" "+ array[2] +" "+ array[3]);
	this.requestToken();
	setInterval(function() {location.reload()}, 1000*60*60);
};

PageManager.prototype.render = function() {
	this.page1.setToken(this.token);
	this.page2.setToken(this.token);
	this.page3.setToken(this.token);
	this.page4.setToken(this.token);
	this.page5.setToken(this.token);
	this.page6.setToken(this.token);
	this.page7.setToken(this.token);

	var page = window.$page || getQueryString("page");
	if(!page || isNaN(page)) {
		Page.singleModel = false;
		this.page1.show();
		this.timer = setInterval($.proxy(this.nextPage, this), 20000);
		$(document).bind("keyup", $.proxy(this.handleKeyUp, this));
	} else {
		this["page"+ page].show();
		Page.singleModel = true;		
	}
};

PageManager.prototype.prevPage = function() {
	this["page"+ this.index].hide();

	this.index--;
	if(this.index < 1) {
		this.index = 7;
	}
	this["page"+ this.index].show();
};

PageManager.prototype.nextPage = function() {
	this["page"+ this.index].hide();

	this.index++;
	if(this.index > 7) {
		this.index = 1;
	}
	this["page"+ this.index].show();
};

PageManager.prototype.handleKeyUp = function(e) {
	if(e.which != 37 && e.which != 39 && e.which != 32) {
		return;
	}

	if(e.which == 32) {
		if(this.timer) {
			clearInterval(this.timer);
			this.timer = null;
		} else {
			this.timer = setInterval($.proxy(this.nextPage, this), 15000);
		}
	}

	if(e.which == 37) {
		this.prevPage();
	}

	if(e.which == 39) {
		this.nextPage();
	}
};