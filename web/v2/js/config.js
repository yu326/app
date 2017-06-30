var config ={};
function initConfig(sitepath){
	if(typeof sitepath == "undefined" || sitepath == null|| sitepath == ""){
		config.sitePath = "/"; //html路径,虚拟目录
	}
	else{
		if(sitepath.lastIndexOf("/") != (sitepath.length - 1)){
			sitepath += "/";
		}
		config.sitePath = sitepath;
	}
	var secdir = getSecondDir(location.pathname);
	if(secdir!=null && secdir != ""){
		config.sitePath += secdir+"/";
	}
	config.loginPageUrl = config.sitePath+"login.html";
	config.fusionUrl = config.sitePath+"charts/"; //swf文件路径
	config.phpUrl = config.sitePath+"model/"; //请求数据路径
	config.requestData = config.phpUrl+"requestdata.php";
	config.dataUrl = config.phpUrl+"getdata.php"; //从数据库请求数据
	config.solrData = config.phpUrl+"solragent.php"; //从solr请求数据
	config.followsData = config.phpUrl+"get_followers.php"; //粉丝影响力请求数据
	config.repostData = config.phpUrl+"get_data_for_repost_trend.php"; //传播分析请求数据
	//config.folfriquaData = config.phpUrl+"analysis_users_followers_friens.php"; //粉丝关注象限图请求数据
	//config.bridgeuserData = config.phpUrl+"analysis_users_bridge.php"; //桥接用户象限图请求
	config.bridgetraceData = config.phpUrl+"analysis_users_trace.php"; //桥接案例轨迹ws_bridgeexampletrace.shtml
	
	config.leftmenuUrl = config.phpUrl+"modelinterface.php?type=getleftmenu";//获取租户左侧导航
	//数据平台左侧导航
	config.platformLeftmenuUrl= function(channelid){
		return config.phpUrl+"modelinterface.php?type=getplatformmenu&channelid="+channelid;
	};
	//根据导航ID获取所有可显示的元素
	config.navRendersUrl = function(navid){
		return config.phpUrl+"modelinterface.php?type=getrenders&navid="+navid;
	};
	//获取某个model的对象的URL
	config.modelDetailUrl = function(modelid,modeltype){
		return config.phpUrl+"modelinterface.php?type=getmodel&modelid="+modelid+"&modeltype="+modeltype;
	};
	//获取某个导航对应的所有实例
	config.navInstancesUrl = function(navid){
		return config.phpUrl+"modelinterface.php?type=getinstance&navid="+navid;
	};
	//获取联动模型所有的元素
	config.elementsUrl = function(instanceid,eletype,snapid){
		var p ="";
		if(snapid){
			p = "&snapid="+snapid+"";
		}
		return config.phpUrl+"modelinterface.php?type=getelements&instanceid="+instanceid+"&elementtype="+eletype+p;
	};
	//某个租户的所有模型
	config.tenantModels = config.phpUrl+"modelinterface.php?type=getmodelbytenantid";
	//租户的某个计费模型
	config.tenantModelRule = function(modelid){
		return config.phpUrl+"modelinterface.php?type=gettenantmodelrule&modelid="+modelid;
	};
	//某频道的模型列表，数据平台使用
	config.channelModels = function(channelid){
		return config.phpUrl+"modelinterface.php?type=getmodelbychannel&channelid="+channelid;
	};
	//从静态数组中获取所有模型
	config.allModels = config.phpUrl+"modelinterface.php?type=getallmodels";
	//设置单模型页面（包括修改、新增）
	//通过post方式进行传递, modelid:当前被修改的模型, instanceid:实例ID, elementid:元素ID, content:元素条件json格式
	config.setSinglePageUrl = config.phpUrl+"modelinterface.php?type=setsinglemodel";
	//添加新实例 post方式传递 navid:导航ID，instancetype：实例类型
	config.addInstanceUrl = config.phpUrl+"modelinterface.php?type=addinstance";
	//添加元素 post方式传递 instanceid:实例ID，type：元素类型,modelid:模型ID,title:元素标题，content:json内容
	config.addElementUrl = config.phpUrl+"modelinterface.php?type=addelement";
	//删除元素的url post 提交 instanceid 和 elementid 如果elementid为0，则删除所有
	config.delElementUrl = config.phpUrl+"modelinterface.php?type=delelement";
	//删除实例 post 提交 instanceid
	config.delInstanceUrl = config.phpUrl+"modelinterface.php?type=delinstance";
	//更新元素的url post 提交 instanceid elementid type：元素类型,modelid:模型ID,title:元素标题，content:json内容
	config.updateElementUrl = config.phpUrl+"modelinterface.php?type=updateelement";
	//更新实例post传递 instanceid  和  instancetype
	config.updateInstanceUrl = config.phpUrl+"modelinterface.php?type=updateinstance";
	config.addPinRelate = config.phpUrl + "modelinterface.php?type=addpinrelate";
	config.delPinRelate = config.phpUrl + "modelinterface.php?type=deletepinrelate";
	//获取数据源，参数sourceid 可以为空，可以是逗号分隔的id。返回id, name, sourcetype 
	config.getSourceUrl = config.dataUrl + "?type=getsource";
	//获取模型可用的show
	config.getModelShowUrl = function(modelid){
		return config.phpUrl + "modelinterface.php?type=getmodelshow&modelid="+modelid;
	};
	//保存多模型,post 方式提交参数 navid（导航ID），htmldata（需要保存的html）
	config.saveMultiModelUrl = config.phpUrl + "modelinterface.php?type=savemultimodel";

	//编辑导航 
	config.getNavbytidallUrl = config.phpUrl + "modelinterface.php?type=getnavbytidall";
	config.getNavbyidUrl = config.phpUrl + "modelinterface.php?type=getnavbyid";
	config.getNavbyTidUrl = config.phpUrl + "modelinterface.php?type=getnavbytid";
	config.checkexistUrl = config.phpUrl + "modelinterface.php?type=checkexist";
	//验证是否有权限drilldown
	config.checkDrilldownUrl = config.phpUrl + "checkuser.php?type=checkdrilldown";
	//验证是否有权限联动
	config.checkLinkageUrl = config.phpUrl + "checkuser.php?type=checklinkage";
	//保存页面html 用post方式提交 json数据
	config.savePageUrl = config.phpUrl + "modelinterface.php?type=savepage";
	//保存页面实例  用post方式提交 json数据
	config.saveInstanceUrl = config.phpUrl + "modelinterface.php?type=saveinstance";
	//更新合并的field字段  用post方式提交 json数据
	config.updatemergedataUrl = config.phpUrl + "modelinterface.php?type=updatemergedata";
	//获取合并的field字段  用post方式提交 json数据
	config.getmergedataUrl = config.phpUrl + "modelinterface.php?type=getmergedata";
	//获取合并的更新计划  用post方式提交 json数据
	config.getmergeschedUrl = config.phpUrl + "modelinterface.php?type=getmergesched";
	//根据MID获取微博ID
	config.getWeiboIDUrl = config.phpUrl + "modelinterface.php?type=geweibotidbymid";
	//根据navid获取html
	config.getNavHtmlUrl = config.phpUrl + "modelinterface.php?type=getnavhtml";
	//
	/*
	 * 包含三个函数,
	 * 桥接用户质量(us_bridgeuser.shtml)
	 * 用户粉丝数量(us_folfrinum.shtml)
	 * 用户粉丝质量(us_folfriqua.shtml)
	 * */
	config.userquaData = config.phpUrl+"analysis_users_quadrant.php"; //桥接用户象限
	config.jsPath = config.sitePath+"js/";
	config.controller = config.jsPath+"controller/"; 
	config.view = config.sitePath; 
	config.imagePath = config.sitePath+"images/";//图片目录
	config.cssPath = config.sitePath + "css/";
	config.debug = true;
	
	//定义url参数
	config.urlParams = new Object();
	config.urlParams.typeid = "typeid";
	config.timegap = 12;//单位：月
	config.defaultcloudcount = 50; //默认标签墙显示个数

	config.numvisibleplot = 10; //默认每屏显示格式
	
    config.maxNavigationNamePx = 150;  //添加导航时导航名称最大的像素值

	config.allowdrilldown = false;
	config.allowdownload = false;
	config.allowupdatesnapshot = false;
	config.alloweventalert = false;
	config.allowwidget = false;
	config.selfstyle = false;
	config.updateschedule = true;
	config.maxClassifyQueryCount = 20;//最多可分类查询的个数
	config.repostShowAllowPlay = true;//传播轨迹视图，是否允许使用"播放“功能
	config.repostShowAllowSearch = true;//传播轨迹视图，是否允许使用“搜索”功能
	config.maxSummaryLength = 70; //微博列表内容显示字个数
	config.allowshowselecttag = false;//是否显示标签查询 add by zuoqian:2016-6-13
	config.allowshowaddtag = true;//是否显示添加标签查询 add by zuoqian:2016-6-20
	//不能作为输入pin的字段
	config.hotinkingImg = ['http://mmbiz.qpic.cn/mmbiz/x1Jk3APKn6'];
	config.excludeInputPins = ["oristatus", "weiboid", "oristatus_username", "oristatus_userid", "repost_url", "repost_userid", "repost_username"];
	
	config.minFontSize = 1; //最小字体限制, 在highchart
	if(Browser.chrome){
		if(parseInt(Browser.chrome, 10) > 26){
			config.minFontSize = 12; //谷歌浏览器新版本最小字体限制.
		}
	}
    config.mapData = [{"map_chart_key":"北京","map_alias_name":["北京","北京市","110000","京"]},
{"map_chart_key":"天津","map_alias_name":["天津","天津市","120000","津"]},
{"map_chart_key":"河北","map_alias_name":["河北","河北省","130000","冀"]},
{"map_chart_key":"山西","map_alias_name":["山西","山西省","140000","晋"]},
{"map_chart_key":"内蒙古","map_alias_name":["内蒙古","内蒙古自治区","150000","内蒙古"]},
{"map_chart_key":"辽宁","map_alias_name":["辽宁","辽宁省","210000","辽"]},
{"map_chart_key":"吉林","map_alias_name":["吉林","吉林省","220000","吉"]},
{"map_chart_key":"黑龙江","map_alias_name":["黑龙江","黑龙江省","230000","黑"]},
{"map_chart_key":"上海","map_alias_name":["上海","上海市","310000","沪"]},
{"map_chart_key":"江苏","map_alias_name":["江苏","江苏省","320000","苏"]},
{"map_chart_key":"浙江","map_alias_name":["浙江","浙江省","330000","浙"]},
{"map_chart_key":"安徽","map_alias_name":["安徽","安徽省","340000","皖"]},
{"map_chart_key":"福建","map_alias_name":["福建","福建省","350000","闽"]},
{"map_chart_key":"江西","map_alias_name":["江西","江西省","360000","赣"]},
{"map_chart_key":"山东","map_alias_name":["山东","山东省","370000","鲁"]},
{"map_chart_key":"河南","map_alias_name":["河南","河南省","410000","豫"]},
{"map_chart_key":"湖北","map_alias_name":["湖北","湖北省","420000","鄂"]},
{"map_chart_key":"湖南","map_alias_name":["湖南","湖南省","430000","湘"]},
{"map_chart_key":"广东","map_alias_name":["广东","广东省","440000","粤"]},
{"map_chart_key":"广西","map_alias_name":["广西","广西壮族自治区","450000","桂"]},
{"map_chart_key":"海南","map_alias_name":["海南","海南省","460000","琼"]},
{"map_chart_key":"重庆","map_alias_name":["重庆","重庆市","500000","渝"]},
{"map_chart_key":"四川","map_alias_name":["四川","四川省","510000","川蜀"]},
{"map_chart_key":"贵州","map_alias_name":["贵州","贵州省","520000","贵黔"]},
{"map_chart_key":"云南","map_alias_name":["云南","云南省","530000","云滇"]},
{"map_chart_key":"西藏","map_alias_name":["西藏","西藏自治区","540000","藏"]},
{"map_chart_key":"陕西","map_alias_name":["陕西","陕西省","610000","陕秦"]},
{"map_chart_key":"甘肃","map_alias_name":["甘肃","甘肃省","620000","甘陇"]},
{"map_chart_key":"青海","map_alias_name":["青海","青海省","630000","青"]},
{"map_chart_key":"宁夏","map_alias_name":["宁夏","宁夏回族自治区","640000","宁"]},
{"map_chart_key":"新疆","map_alias_name":["新疆","新疆维吾尔自治区","650000","新"]},
{"map_chart_key":"台湾","map_alias_name":["台湾","台湾省","710000","台"]},
{"map_chart_key":"香港","map_alias_name":["香港","香港特别行政区","810000","港"]},
{"map_chart_key":"澳门","map_alias_name":["澳门","澳门特别行政区","820000","澳"]},
{"map_chart_key":"东城区","map_alias_name":["东城区","东城区","110000"]},
{"map_chart_key":"西城区","map_alias_name":["西城区","西城区","110000"]},
{"map_chart_key":"崇文区","map_alias_name":["崇文区","崇文区","110000"]},
{"map_chart_key":"宣武区","map_alias_name":["宣武区","宣武区","110000"]},
{"map_chart_key":"朝阳区","map_alias_name":["朝阳区","朝阳区","110000"]},
{"map_chart_key":"丰台区","map_alias_name":["丰台区","丰台","110000"]},
{"map_chart_key":"石景山区","map_alias_name":["石景山区","石景山","110000"]},
{"map_chart_key":"海淀区","map_alias_name":["海淀区","海淀","110000"]},
{"map_chart_key":"门头沟区","map_alias_name":["门头沟区","门头沟","110000"]},
{"map_chart_key":"房山区","map_alias_name":["房山区","房山","110000"]},
{"map_chart_key":"通州区","map_alias_name":["通州区","通州","110000"]},
{"map_chart_key":"顺义区","map_alias_name":["顺义区","顺义","110000"]},
{"map_chart_key":"昌平区","map_alias_name":["昌平区","昌平","110000"]},
{"map_chart_key":"大兴区","map_alias_name":["大兴区","大兴区","110000"]},
{"map_chart_key":"怀柔区","map_alias_name":["怀柔区","怀柔","110000"]},
{"map_chart_key":"平谷区","map_alias_name":["平谷区","平谷","110000"]},
{"map_chart_key":"密云县","map_alias_name":["密云县","密云","110000"]},
{"map_chart_key":"延庆县","map_alias_name":["延庆县","延庆","110000"]},
{"map_chart_key":"和平区","map_alias_name":["和平区","和平区","120000"]},
{"map_chart_key":"河东区","map_alias_name":["河东区","河东区","120000"]},
{"map_chart_key":"河西区","map_alias_name":["河西区","河西区","120000"]},
{"map_chart_key":"南开区","map_alias_name":["南开区","南开区","120000"]},
{"map_chart_key":"河北区","map_alias_name":["河北区","河北区","120000"]},
{"map_chart_key":"红桥区","map_alias_name":["红桥区","红桥","120000"]},
{"map_chart_key":"塘沽区","map_alias_name":["塘沽区","塘沽","120000"]},
{"map_chart_key":"汉沽区","map_alias_name":["汉沽区","汉沽","120000"]},
{"map_chart_key":"大港区","map_alias_name":["大港区","大港","120000"]},
{"map_chart_key":"东丽区","map_alias_name":["东丽区","东丽","120000"]},
{"map_chart_key":"西青区","map_alias_name":["西青区","西青","120000"]},
{"map_chart_key":"津南区","map_alias_name":["津南区","津南","120000"]},
{"map_chart_key":"北辰区","map_alias_name":["北辰区","北辰","120000"]},
{"map_chart_key":"武清区","map_alias_name":["武清区","武清","120000"]},
{"map_chart_key":"宝坻区","map_alias_name":["宝坻区","宝坻","120000"]},
{"map_chart_key":"滨海新区","map_alias_name":["滨海新区","滨海","120000",""]},
{"map_chart_key":"保税区","map_alias_name":["保税区","保税区","120000",""]},
{"map_chart_key":"宁河县","map_alias_name":["宁河县","宁河","120000"]},
{"map_chart_key":"静海县","map_alias_name":["静海县","静海","120000"]},
{"map_chart_key":"蓟县","map_alias_name":["蓟县","120000"]},
{"map_chart_key":"黄浦区","map_alias_name":["黄浦区","黄浦区","310000"]},
{"map_chart_key":"卢湾区","map_alias_name":["卢湾区","卢湾","310000"]},
{"map_chart_key":"徐汇区","map_alias_name":["徐汇区","徐汇区","310000"]},
{"map_chart_key":"长宁区","map_alias_name":["长宁区","长宁","310000"]},
{"map_chart_key":"静安区","map_alias_name":["静安区","静安区","310000"]},
{"map_chart_key":"普陀区","map_alias_name":["普陀区","普陀","310000"]},
{"map_chart_key":"闸北区","map_alias_name":["闸北区","闸北","310000"]},
{"map_chart_key":"虹口区","map_alias_name":["虹口区","虹口","310000"]},
{"map_chart_key":"杨浦区","map_alias_name":["杨浦区","杨浦","310000"]},
{"map_chart_key":"闵行区","map_alias_name":["闵行区","闵行","310000"]},
{"map_chart_key":"宝山区","map_alias_name":["宝山区","宝山区","310000"]},
{"map_chart_key":"嘉定区","map_alias_name":["嘉定区","嘉定","310000"]},
{"map_chart_key":"浦东新区","map_alias_name":["浦东新区","浦东","310000"]},
{"map_chart_key":"金山区","map_alias_name":["金山区","金山区","310000"]},
{"map_chart_key":"松江区","map_alias_name":["松江区","松江区","310000"]},
{"map_chart_key":"青浦区","map_alias_name":["青浦区","青浦","310000"]},
{"map_chart_key":"南汇区","map_alias_name":["南汇区","南汇","310000"]},
{"map_chart_key":"奉贤区","map_alias_name":["奉贤区","奉贤","310000"]},
{"map_chart_key":"崇明县","map_alias_name":["崇明县","崇明县","310000"]},
{"map_chart_key":"万州区","map_alias_name":["万州区","万州","500000"]},
{"map_chart_key":"涪陵区","map_alias_name":["涪陵区","涪陵","500000"]},
{"map_chart_key":"渝中区","map_alias_name":["渝中区","渝中","500000"]},
{"map_chart_key":"大渡口区","map_alias_name":["大渡口区","大渡口","500000"]},
{"map_chart_key":"江北区","map_alias_name":["江北区","江北区","500000"]},
{"map_chart_key":"沙坪坝区","map_alias_name":["沙坪坝区","沙坪坝","500000"]},
{"map_chart_key":"九龙坡区","map_alias_name":["九龙坡区","九龙坡","500000"]},
{"map_chart_key":"南岸区","map_alias_name":["南岸区","南岸区","500000"]},
{"map_chart_key":"北碚区","map_alias_name":["北碚区","北碚","500000"]},
{"map_chart_key":"万盛区","map_alias_name":["万盛区","万盛区","500000"]},
{"map_chart_key":"双桥区","map_alias_name":["双桥区","双桥区","500000"]},
{"map_chart_key":"渝北区","map_alias_name":["渝北区","渝北","500000"]},
{"map_chart_key":"巴南区","map_alias_name":["巴南区","巴南","500000"]},
{"map_chart_key":"黔江区","map_alias_name":["黔江区","黔江","500000"]},
{"map_chart_key":"长寿区","map_alias_name":["长寿区","长寿","500000"]},
{"map_chart_key":"江津区","map_alias_name":["江津区","江津","500000"]},
{"map_chart_key":"合川区","map_alias_name":["合川区","合川","500000"]},
{"map_chart_key":"永川区","map_alias_name":["永川区","永川","500000"]},
{"map_chart_key":"南川区","map_alias_name":["南川区","南川","500000"]},
{"map_chart_key":"綦江县","map_alias_name":["綦江县","綦江","500000"]},
{"map_chart_key":"潼南县","map_alias_name":["潼南县","潼南","500000"]},
{"map_chart_key":"铜梁县","map_alias_name":["铜梁县","铜梁","500000"]},
{"map_chart_key":"大足县","map_alias_name":["大足县","大足县","500000"]},
{"map_chart_key":"荣昌县","map_alias_name":["荣昌县","荣昌县","500000"]},
{"map_chart_key":"璧山县","map_alias_name":["璧山县","璧山","500000"]},
{"map_chart_key":"梁平县","map_alias_name":["梁平县","梁平","500000"]},
{"map_chart_key":"城口县","map_alias_name":["城口县","城口县","500000"]},
{"map_chart_key":"丰都县","map_alias_name":["丰都县","丰都县","500000"]},
{"map_chart_key":"垫江县","map_alias_name":["垫江县","垫江","500000"]},
{"map_chart_key":"武隆县","map_alias_name":["武隆县","武隆","500000"]},
{"map_chart_key":"忠县","map_alias_name":["忠县","500000"]},
{"map_chart_key":"开县","map_alias_name":["开县","500000"]},
{"map_chart_key":"云阳县","map_alias_name":["云阳县","云阳","500000"]},
{"map_chart_key":"奉节县","map_alias_name":["奉节县","奉节","500000"]},
{"map_chart_key":"巫山县","map_alias_name":["巫山县","巫山县","500000"]},
{"map_chart_key":"巫溪县","map_alias_name":["巫溪县","巫溪","500000"]},
{"map_chart_key":"石柱土家族自治县","map_alias_name":["石柱土家族自治县","石柱县","500000"]},
{"map_chart_key":"秀山土家族苗族自治县","map_alias_name":["秀山土家族苗族自治县","秀山县","500000"]},
{"map_chart_key":"酉阳土家族苗族自治县","map_alias_name":["酉阳土家族苗族自治县","酉阳","500000"]},
{"map_chart_key":"彭水苗族土家族自治县","map_alias_name":["彭水苗族土家族自治县","彭水","500000"]},
{"map_chart_key":"石家庄市","map_alias_name":["石家庄市","石家庄","130000"]},
{"map_chart_key":"唐山市","map_alias_name":["唐山市","唐山","130000"]},
{"map_chart_key":"秦皇岛市","map_alias_name":["秦皇岛市","秦皇岛","130000"]},
{"map_chart_key":"邯郸市","map_alias_name":["邯郸市","邯郸","130000"]},
{"map_chart_key":"邢台市","map_alias_name":["邢台市","邢台","130000"]},
{"map_chart_key":"保定市","map_alias_name":["保定市","保定","130000"]},
{"map_chart_key":"张家口市","map_alias_name":["张家口市","张家口","130000"]},
{"map_chart_key":"承德市","map_alias_name":["承德市","承德","130000"]},
{"map_chart_key":"沧州市","map_alias_name":["沧州市","沧州","130000"]},
{"map_chart_key":"廊坊市","map_alias_name":["廊坊市","廊坊","130000"]},
{"map_chart_key":"衡水市","map_alias_name":["衡水市","衡水","130000"]},
{"map_chart_key":"太原市","map_alias_name":["太原市","太原","140000"]},
{"map_chart_key":"大同市","map_alias_name":["大同市","大同","140000"]},
{"map_chart_key":"阳泉市","map_alias_name":["阳泉市","阳泉","140000"]},
{"map_chart_key":"长治市","map_alias_name":["长治市","长治","140000"]},
{"map_chart_key":"晋城市","map_alias_name":["晋城市","晋城","140000"]},
{"map_chart_key":"朔州市","map_alias_name":["朔州市","朔州","140000"]},
{"map_chart_key":"晋中市","map_alias_name":["晋中市","晋中","140000"]},
{"map_chart_key":"运城市","map_alias_name":["运城市","运城","140000"]},
{"map_chart_key":"忻州市","map_alias_name":["忻州市","忻州","140000"]},
{"map_chart_key":"临汾市","map_alias_name":["临汾市","临汾","140000"]},
{"map_chart_key":"吕梁市","map_alias_name":["吕梁市","吕梁","140000"]},
{"map_chart_key":"呼和浩特市","map_alias_name":["呼和浩特市","呼和浩特","150000"]},
{"map_chart_key":"包头市","map_alias_name":["包头市","包头","150000"]},
{"map_chart_key":"乌海市","map_alias_name":["乌海市","乌海","150000"]},
{"map_chart_key":"赤峰市","map_alias_name":["赤峰市","赤峰","150000"]},
{"map_chart_key":"通辽市","map_alias_name":["通辽市","通辽","150000"]},
{"map_chart_key":"鄂尔多斯市","map_alias_name":["鄂尔多斯市","鄂尔多斯","150000"]},
{"map_chart_key":"呼伦贝尔市","map_alias_name":["呼伦贝尔市","呼伦贝尔","150000"]},
{"map_chart_key":"巴彦淖尔市","map_alias_name":["巴彦淖尔市","巴彦淖尔","150000"]},
{"map_chart_key":"乌兰察布市","map_alias_name":["乌兰察布市","乌兰察布","150000"]},
{"map_chart_key":"兴安盟","map_alias_name":["兴安盟","150000"]},
{"map_chart_key":"锡林郭勒盟","map_alias_name":["锡林郭勒盟","150000"]},
{"map_chart_key":"阿拉善盟","map_alias_name":["阿拉善盟","150000"]},
{"map_chart_key":"沈阳市","map_alias_name":["沈阳市","沈阳","210000"]},
{"map_chart_key":"大连市","map_alias_name":["大连市","大连","210000"]},
{"map_chart_key":"鞍山市","map_alias_name":["鞍山市","鞍山","210000"]},
{"map_chart_key":"抚顺市","map_alias_name":["抚顺市","抚顺","210000"]},
{"map_chart_key":"本溪市","map_alias_name":["本溪市","本溪","210000"]},
{"map_chart_key":"丹东市","map_alias_name":["丹东市","丹东","210000"]},
{"map_chart_key":"锦州市","map_alias_name":["锦州市","锦州","210000"]},
{"map_chart_key":"营口市","map_alias_name":["营口市","营口","210000"]},
{"map_chart_key":"阜新市","map_alias_name":["阜新市","阜新","210000"]},
{"map_chart_key":"辽阳市","map_alias_name":["辽阳市","辽阳","210000"]},
{"map_chart_key":"盘锦市","map_alias_name":["盘锦市","盘锦","210000"]},
{"map_chart_key":"铁岭市","map_alias_name":["铁岭市","铁岭","210000"]},
{"map_chart_key":"朝阳市","map_alias_name":["朝阳市","朝阳","210000"]},
{"map_chart_key":"葫芦岛市","map_alias_name":["葫芦岛市","葫芦岛","210000"]},
{"map_chart_key":"长春市","map_alias_name":["长春市","长春","220000"]},
{"map_chart_key":"吉林市","map_alias_name":["吉林市","吉林","220000"]},
{"map_chart_key":"四平市","map_alias_name":["四平市","四平","220000"]},
{"map_chart_key":"辽源市","map_alias_name":["辽源市","辽源","220000"]},
{"map_chart_key":"通化市","map_alias_name":["通化市","通化","220000"]},
{"map_chart_key":"白山市","map_alias_name":["白山市","白山","220000"]},
{"map_chart_key":"松原市","map_alias_name":["松原市","松原","220000"]},
{"map_chart_key":"白城市","map_alias_name":["白城市","白城","220000"]},
{"map_chart_key":"延边朝鲜族自治州","map_alias_name":["延边朝鲜族自治州","延边","220000"]},
{"map_chart_key":"哈尔滨市","map_alias_name":["哈尔滨市","哈尔滨","230000"]},
{"map_chart_key":"齐齐哈尔市","map_alias_name":["齐齐哈尔市","齐齐哈尔","230000"]},
{"map_chart_key":"鸡西市","map_alias_name":["鸡西市","鸡西","230000"]},
{"map_chart_key":"鹤岗市","map_alias_name":["鹤岗市","鹤岗","230000"]},
{"map_chart_key":"双鸭山市","map_alias_name":["双鸭山市","双鸭山","230000"]},
{"map_chart_key":"大庆市","map_alias_name":["大庆市","大庆","230000"]},
{"map_chart_key":"伊春市","map_alias_name":["伊春市","伊春","230000"]},
{"map_chart_key":"佳木斯市","map_alias_name":["佳木斯市","佳木斯","230000"]},
{"map_chart_key":"七台河市","map_alias_name":["七台河市","七台河","230000"]},
{"map_chart_key":"牡丹江市","map_alias_name":["牡丹江市","牡丹江","230000"]},
{"map_chart_key":"黑河市","map_alias_name":["黑河市","黑河","230000"]},
{"map_chart_key":"绥化市","map_alias_name":["绥化市","绥化","230000"]},
{"map_chart_key":"大兴安岭地区","map_alias_name":["大兴安岭地区","大兴安岭","230000"]},
{"map_chart_key":"南京市","map_alias_name":["南京市","南京","320000"]},
{"map_chart_key":"无锡市","map_alias_name":["无锡市","无锡","320000"]},
{"map_chart_key":"徐州市","map_alias_name":["徐州市","徐州","320000"]},
{"map_chart_key":"常州市","map_alias_name":["常州市","常州","320000"]},
{"map_chart_key":"苏州市","map_alias_name":["苏州市","苏州","320000"]},
{"map_chart_key":"南通市","map_alias_name":["南通市","南通","320000"]},
{"map_chart_key":"连云港市","map_alias_name":["连云港市","连云港","320000"]},
{"map_chart_key":"淮安市","map_alias_name":["淮安市","淮安","320000"]},
{"map_chart_key":"盐城市","map_alias_name":["盐城市","盐城","320000"]},
{"map_chart_key":"扬州市","map_alias_name":["扬州市","扬州","320000"]},
{"map_chart_key":"镇江市","map_alias_name":["镇江市","镇江","320000"]},
{"map_chart_key":"泰州市","map_alias_name":["泰州市","泰州","320000"]},
{"map_chart_key":"宿迁市","map_alias_name":["宿迁市","宿迁","320000"]},
{"map_chart_key":"杭州市","map_alias_name":["杭州市","杭州","330000"]},
{"map_chart_key":"宁波市","map_alias_name":["宁波市","宁波","330000"]},
{"map_chart_key":"温州市","map_alias_name":["温州市","温州","330000"]},
{"map_chart_key":"嘉兴市","map_alias_name":["嘉兴市","嘉兴","330000"]},
{"map_chart_key":"湖州市","map_alias_name":["湖州市","湖州","330000"]},
{"map_chart_key":"绍兴市","map_alias_name":["绍兴市","绍兴","330000"]},
{"map_chart_key":"金华市","map_alias_name":["金华市","金华","330000"]},
{"map_chart_key":"衢州市","map_alias_name":["衢州市","衢州","330000"]},
{"map_chart_key":"舟山市","map_alias_name":["舟山市","舟山","330000"]},
{"map_chart_key":"台州市","map_alias_name":["台州市","台州","330000"]},
{"map_chart_key":"丽水市","map_alias_name":["丽水市","丽水","330000"]},
{"map_chart_key":"合肥市","map_alias_name":["合肥市","合肥","340000"]},
{"map_chart_key":"芜湖市","map_alias_name":["芜湖市","芜湖","340000"]},
{"map_chart_key":"蚌埠市","map_alias_name":["蚌埠市","蚌埠","340000"]},
{"map_chart_key":"淮南市","map_alias_name":["淮南市","淮南","340000"]},
{"map_chart_key":"马鞍山市","map_alias_name":["马鞍山市","马鞍山","340000"]},
{"map_chart_key":"淮北市","map_alias_name":["淮北市","淮北","340000"]},
{"map_chart_key":"铜陵市","map_alias_name":["铜陵市","铜陵","340000"]},
{"map_chart_key":"安庆市","map_alias_name":["安庆市","安庆","340000"]},
{"map_chart_key":"黄山市","map_alias_name":["黄山市","黄山","340000"]},
{"map_chart_key":"滁州市","map_alias_name":["滁州市","滁州","340000"]},
{"map_chart_key":"阜阳市","map_alias_name":["阜阳市","阜阳","340000"]},
{"map_chart_key":"宿州市","map_alias_name":["宿州市","宿州","340000"]},
{"map_chart_key":"巢湖市","map_alias_name":["巢湖市","巢湖","340000"]},
{"map_chart_key":"六安市","map_alias_name":["六安市","六安","340000"]},
{"map_chart_key":"亳州市","map_alias_name":["亳州市","亳州","340000"]},
{"map_chart_key":"池州市","map_alias_name":["池州市","池州","340000"]},
{"map_chart_key":"宣城市","map_alias_name":["宣城市","宣城","340000"]},
{"map_chart_key":"福州市","map_alias_name":["福州市","福州","350000"]},
{"map_chart_key":"厦门市","map_alias_name":["厦门市","厦门","350000"]},
{"map_chart_key":"莆田市","map_alias_name":["莆田市","莆田","350000"]},
{"map_chart_key":"三明市","map_alias_name":["三明市","三明","350000"]},
{"map_chart_key":"泉州市","map_alias_name":["泉州市","泉州","350000"]},
{"map_chart_key":"漳州市","map_alias_name":["漳州市","漳州","350000"]},
{"map_chart_key":"南平市","map_alias_name":["南平市","南平","350000"]},
{"map_chart_key":"龙岩市","map_alias_name":["龙岩市","龙岩","350000"]},
{"map_chart_key":"宁德市","map_alias_name":["宁德市","宁德","350000"]},
{"map_chart_key":"南昌市","map_alias_name":["南昌市","南昌","360000"]},
{"map_chart_key":"景德镇市","map_alias_name":["景德镇市","景德镇","360000"]},
{"map_chart_key":"萍乡市","map_alias_name":["萍乡市","萍乡","360000"]},
{"map_chart_key":"九江市","map_alias_name":["九江市","九江","360000"]},
{"map_chart_key":"新余市","map_alias_name":["新余市","新余","360000"]},
{"map_chart_key":"鹰潭市","map_alias_name":["鹰潭市","鹰潭","360000"]},
{"map_chart_key":"赣州市","map_alias_name":["赣州市","赣州","360000"]},
{"map_chart_key":"吉安市","map_alias_name":["吉安市","吉安","360000"]},
{"map_chart_key":"宜春市","map_alias_name":["宜春市","宜春","360000"]},
{"map_chart_key":"抚州市","map_alias_name":["抚州市","抚州","360000"]},
{"map_chart_key":"上饶市","map_alias_name":["上饶市","上饶","360000"]},
{"map_chart_key":"济南市","map_alias_name":["济南市","济南","370000"]},
{"map_chart_key":"青岛市","map_alias_name":["青岛市","青岛","370000"]},
{"map_chart_key":"淄博市","map_alias_name":["淄博市","淄博","370000"]},
{"map_chart_key":"枣庄市","map_alias_name":["枣庄市","枣庄","370000"]},
{"map_chart_key":"东营市","map_alias_name":["东营市","东营","370000"]},
{"map_chart_key":"烟台市","map_alias_name":["烟台市","烟台","370000"]},
{"map_chart_key":"潍坊市","map_alias_name":["潍坊市","潍坊","370000"]},
{"map_chart_key":"济宁市","map_alias_name":["济宁市","济宁","370000"]},
{"map_chart_key":"泰安市","map_alias_name":["泰安市","泰安","370000"]},
{"map_chart_key":"威海市","map_alias_name":["威海市","威海","370000"]},
{"map_chart_key":"日照市","map_alias_name":["日照市","日照","370000"]},
{"map_chart_key":"莱芜市","map_alias_name":["莱芜市","莱芜","370000"]},
{"map_chart_key":"临沂市","map_alias_name":["临沂市","临沂","370000"]},
{"map_chart_key":"德州市","map_alias_name":["德州市","德州","370000"]},
{"map_chart_key":"聊城市","map_alias_name":["聊城市","聊城","370000"]},
{"map_chart_key":"滨州市","map_alias_name":["滨州市","滨州","370000"]},
{"map_chart_key":"菏泽市","map_alias_name":["菏泽市","菏泽","370000"]},
{"map_chart_key":"郑州市","map_alias_name":["郑州市","郑州","410000"]},
{"map_chart_key":"开封市","map_alias_name":["开封市","开封","410000"]},
{"map_chart_key":"洛阳市","map_alias_name":["洛阳市","洛阳","410000"]},
{"map_chart_key":"平顶山市","map_alias_name":["平顶山市","平顶山","410000"]},
{"map_chart_key":"安阳市","map_alias_name":["安阳市","安阳","410000"]},
{"map_chart_key":"鹤壁市","map_alias_name":["鹤壁市","鹤壁","410000"]},
{"map_chart_key":"新乡市","map_alias_name":["新乡市","新乡","410000"]},
{"map_chart_key":"焦作市","map_alias_name":["焦作市","焦作","410000"]},
{"map_chart_key":"濮阳市","map_alias_name":["濮阳市","濮阳","410000"]},
{"map_chart_key":"许昌市","map_alias_name":["许昌市","许昌","410000"]},
{"map_chart_key":"漯河市","map_alias_name":["漯河市","漯河","410000"]},
{"map_chart_key":"三门峡市","map_alias_name":["三门峡市","三门峡","410000"]},
{"map_chart_key":"南阳市","map_alias_name":["南阳市","南阳","410000"]},
{"map_chart_key":"商丘市","map_alias_name":["商丘市","商丘","410000"]},
{"map_chart_key":"信阳市","map_alias_name":["信阳市","信阳","410000"]},
{"map_chart_key":"周口市","map_alias_name":["周口市","周口","410000"]},
{"map_chart_key":"驻马店市","map_alias_name":["驻马店市","驻马店","410000"]},
{"map_chart_key":"武汉市","map_alias_name":["武汉市","武汉","420000"]},
{"map_chart_key":"黄石市","map_alias_name":["黄石市","黄石","420000"]},
{"map_chart_key":"十堰市","map_alias_name":["十堰市","十堰","420000"]},
{"map_chart_key":"宜昌市","map_alias_name":["宜昌市","宜昌","420000"]},
{"map_chart_key":"襄樊市","map_alias_name":["襄樊市","襄樊","420000"]},
{"map_chart_key":"鄂州市","map_alias_name":["鄂州市","鄂州","420000"]},
{"map_chart_key":"荆门市","map_alias_name":["荆门市","荆门","420000"]},
{"map_chart_key":"孝感市","map_alias_name":["孝感市","孝感","420000"]},
{"map_chart_key":"荆州市","map_alias_name":["荆州市","荆州","420000"]},
{"map_chart_key":"黄冈市","map_alias_name":["黄冈市","黄冈","420000"]},
{"map_chart_key":"咸宁市","map_alias_name":["咸宁市","咸宁","420000"]},
{"map_chart_key":"随州市","map_alias_name":["随州市","随州","420000"]},
{"map_chart_key":"恩施土家族苗族自治州","map_alias_name":["恩施土家族苗族自治州","420000"]},
{"map_chart_key":"长沙市","map_alias_name":["长沙市","长沙","430000"]},
{"map_chart_key":"株洲市","map_alias_name":["株洲市","株洲","430000"]},
{"map_chart_key":"湘潭市","map_alias_name":["湘潭市","湘潭","430000"]},
{"map_chart_key":"衡阳市","map_alias_name":["衡阳市","衡阳","430000"]},
{"map_chart_key":"邵阳市","map_alias_name":["邵阳市","邵阳","430000"]},
{"map_chart_key":"岳阳市","map_alias_name":["岳阳市","岳阳","430000"]},
{"map_chart_key":"常德市","map_alias_name":["常德市","常德","430000"]},
{"map_chart_key":"张家界市","map_alias_name":["张家界市","张家界","430000"]},
{"map_chart_key":"益阳市","map_alias_name":["益阳市","益阳","430000"]},
{"map_chart_key":"郴州市","map_alias_name":["郴州市","郴州","430000"]},
{"map_chart_key":"永州市","map_alias_name":["永州市","永州","430000"]},
{"map_chart_key":"怀化市","map_alias_name":["怀化市","怀化","430000"]},
{"map_chart_key":"娄底市","map_alias_name":["娄底市","娄底","430000"]},
{"map_chart_key":"湘西土家族苗族自治州","map_alias_name":["湘西土家族苗族自治州","430000"]},
{"map_chart_key":"广州市","map_alias_name":["广州市","广州","440000"]},
{"map_chart_key":"韶关市","map_alias_name":["韶关市","韶关","440000"]},
{"map_chart_key":"深圳市","map_alias_name":["深圳市","深圳","440000"]},
{"map_chart_key":"珠海市","map_alias_name":["珠海市","珠海","440000"]},
{"map_chart_key":"汕头市","map_alias_name":["汕头市","汕头","440000"]},
{"map_chart_key":"佛山市","map_alias_name":["佛山市","佛山","440000"]},
{"map_chart_key":"江门市","map_alias_name":["江门市","江门","440000"]},
{"map_chart_key":"湛江市","map_alias_name":["湛江市","湛江","440000"]},
{"map_chart_key":"茂名市","map_alias_name":["茂名市","茂名","440000"]},
{"map_chart_key":"肇庆市","map_alias_name":["肇庆市","肇庆","440000"]},
{"map_chart_key":"惠州市","map_alias_name":["惠州市","惠州","440000"]},
{"map_chart_key":"梅州市","map_alias_name":["梅州市","梅州","440000"]},
{"map_chart_key":"汕尾市","map_alias_name":["汕尾市","汕尾","440000"]},
{"map_chart_key":"河源市","map_alias_name":["河源市","河源","440000"]},
{"map_chart_key":"阳江市","map_alias_name":["阳江市","阳江","440000"]},
{"map_chart_key":"清远市","map_alias_name":["清远市","清远","440000"]},
{"map_chart_key":"东莞市","map_alias_name":["东莞市","东莞","440000"]},
{"map_chart_key":"中山市","map_alias_name":["中山市","中山","440000"]},
{"map_chart_key":"潮州市","map_alias_name":["潮州市","潮州","440000"]},
{"map_chart_key":"揭阳市","map_alias_name":["揭阳市","揭阳","440000"]},
{"map_chart_key":"云浮市","map_alias_name":["云浮市","云浮","440000"]},
{"map_chart_key":"南宁市","map_alias_name":["南宁市","南宁","450000"]},
{"map_chart_key":"柳州市","map_alias_name":["柳州市","柳州","450000"]},
{"map_chart_key":"桂林市","map_alias_name":["桂林市","桂林","450000"]},
{"map_chart_key":"梧州市","map_alias_name":["梧州市","梧州","450000"]},
{"map_chart_key":"北海市","map_alias_name":["北海市","北海","450000"]},
{"map_chart_key":"防城港市","map_alias_name":["防城港市","防城港","450000"]},
{"map_chart_key":"钦州市","map_alias_name":["钦州市","钦州","450000"]},
{"map_chart_key":"贵港市","map_alias_name":["贵港市","贵港","450000"]},
{"map_chart_key":"玉林市","map_alias_name":["玉林市","玉林","450000"]},
{"map_chart_key":"百色市","map_alias_name":["百色市","百色","450000"]},
{"map_chart_key":"贺州市","map_alias_name":["贺州市","贺州","450000"]},
{"map_chart_key":"河池市","map_alias_name":["河池市","河池","450000"]},
{"map_chart_key":"来宾市","map_alias_name":["来宾市","来宾","450000"]},
{"map_chart_key":"崇左市","map_alias_name":["崇左市","崇左","450000"]},
{"map_chart_key":"海口市","map_alias_name":["海口市","海口","460000"]},
{"map_chart_key":"三亚市","map_alias_name":["三亚市","三亚","460000"]},
{"map_chart_key":"成都市","map_alias_name":["成都市","成都","510000"]},
{"map_chart_key":"自贡市","map_alias_name":["自贡市","自贡","510000"]},
{"map_chart_key":"攀枝花市","map_alias_name":["攀枝花市","攀枝花","510000"]},
{"map_chart_key":"泸州市","map_alias_name":["泸州市","泸州","510000"]},
{"map_chart_key":"德阳市","map_alias_name":["德阳市","德阳","510000"]},
{"map_chart_key":"绵阳市","map_alias_name":["绵阳市","绵阳","510000"]},
{"map_chart_key":"广元市","map_alias_name":["广元市","广元","510000"]},
{"map_chart_key":"遂宁市","map_alias_name":["遂宁市","遂宁","510000"]},
{"map_chart_key":"内江市","map_alias_name":["内江市","内江","510000"]},
{"map_chart_key":"乐山市","map_alias_name":["乐山市","乐山","510000"]},
{"map_chart_key":"南充市","map_alias_name":["南充市","南充","510000"]},
{"map_chart_key":"眉山市","map_alias_name":["眉山市","眉山","510000"]},
{"map_chart_key":"宜宾市","map_alias_name":["宜宾市","宜宾","510000"]},
{"map_chart_key":"广安市","map_alias_name":["广安市","广安","510000"]},
{"map_chart_key":"达州市","map_alias_name":["达州市","达州","510000"]},
{"map_chart_key":"雅安市","map_alias_name":["雅安市","雅安","510000"]},
{"map_chart_key":"巴中市","map_alias_name":["巴中市","巴中","510000"]},
{"map_chart_key":"资阳市","map_alias_name":["资阳市","资阳","510000"]},
{"map_chart_key":"阿坝藏族羌族自治州","map_alias_name":["阿坝藏族羌族自治州","510000"]},
{"map_chart_key":"甘孜藏族自治州","map_alias_name":["甘孜藏族自治州","510000"]},
{"map_chart_key":"凉山彝族自治州","map_alias_name":["凉山彝族自治州","510000"]},
{"map_chart_key":"贵阳市","map_alias_name":["贵阳市","贵阳","520000"]},
{"map_chart_key":"六盘水市","map_alias_name":["六盘水市","六盘水","520000"]},
{"map_chart_key":"遵义市","map_alias_name":["遵义市","遵义","520000"]},
{"map_chart_key":"安顺市","map_alias_name":["安顺市","安顺","520000"]},
{"map_chart_key":"铜仁地区","map_alias_name":["铜仁地区","铜仁","520000"]},
{"map_chart_key":"黔西南布依族苗族自治州","map_alias_name":["黔西南布依族苗族自治州","520000"]},
{"map_chart_key":"毕节地区","map_alias_name":["毕节地区","毕节","520000"]},
{"map_chart_key":"黔东南苗族侗族自治州","map_alias_name":["黔东南苗族侗族自治州","520000"]},
{"map_chart_key":"黔南布依族苗族自治州","map_alias_name":["黔南布依族苗族自治州","520000"]},
{"map_chart_key":"昆明市","map_alias_name":["昆明市","昆明","530000"]},
{"map_chart_key":"曲靖市","map_alias_name":["曲靖市","曲靖","530000"]},
{"map_chart_key":"玉溪市","map_alias_name":["玉溪市","玉溪","530000"]},
{"map_chart_key":"保山市","map_alias_name":["保山市","保山","530000"]},
{"map_chart_key":"昭通市","map_alias_name":["昭通市","昭通","530000"]},
{"map_chart_key":"丽江市","map_alias_name":["丽江市","丽江","530000"]},
{"map_chart_key":"普洱市","map_alias_name":["普洱市","普洱","530000"]},
{"map_chart_key":"临沧市","map_alias_name":["临沧市","临沧","530000"]},
{"map_chart_key":"楚雄彝族自治州","map_alias_name":["楚雄彝族自治州","楚雄","530000"]},
{"map_chart_key":"红河哈尼族彝族自治州","map_alias_name":["红河哈尼族彝族自治州","红河","530000"]},
{"map_chart_key":"文山壮族苗族自治州","map_alias_name":["文山壮族苗族自治州","文山","530000"]},
{"map_chart_key":"西双版纳傣族自治州","map_alias_name":["西双版纳傣族自治州","西双版纳","530000"]},
{"map_chart_key":"大理白族自治州","map_alias_name":["大理白族自治州","大理","530000"]},
{"map_chart_key":"德宏傣族景颇族自治州","map_alias_name":["德宏傣族景颇族自治州","德宏","530000"]},
{"map_chart_key":"怒江傈僳族自治州","map_alias_name":["怒江傈僳族自治州","怒江","530000"]},
{"map_chart_key":"迪庆藏族自治州","map_alias_name":["迪庆藏族自治州","迪庆","530000"]},
{"map_chart_key":"拉萨市","map_alias_name":["拉萨市","拉萨","540000"]},
{"map_chart_key":"昌都地区","map_alias_name":["昌都地区","昌都","540000"]},
{"map_chart_key":"山南地区","map_alias_name":["山南地区","山南","540000"]},
{"map_chart_key":"日喀则地区","map_alias_name":["日喀则地区","日喀则","540000"]},
{"map_chart_key":"那曲地区","map_alias_name":["那曲地区","那曲","540000"]},
{"map_chart_key":"阿里地区","map_alias_name":["阿里地区","阿里","540000"]},
{"map_chart_key":"林芝地区","map_alias_name":["林芝地区","林芝","540000"]},
{"map_chart_key":"西安市","map_alias_name":["西安市","西安","610000"]},
{"map_chart_key":"铜川市","map_alias_name":["铜川市","铜川","610000"]},
{"map_chart_key":"宝鸡市","map_alias_name":["宝鸡市","宝鸡","610000"]},
{"map_chart_key":"咸阳市","map_alias_name":["咸阳市","咸阳","610000"]},
{"map_chart_key":"渭南市","map_alias_name":["渭南市","渭南","610000"]},
{"map_chart_key":"延安市","map_alias_name":["延安市","延安","610000"]},
{"map_chart_key":"汉中市","map_alias_name":["汉中市","汉中","610000"]},
{"map_chart_key":"榆林市","map_alias_name":["榆林市","榆林","610000"]},
{"map_chart_key":"安康市","map_alias_name":["安康市","安康","610000"]},
{"map_chart_key":"商洛市","map_alias_name":["商洛市","商洛","610000"]},
{"map_chart_key":"兰州市","map_alias_name":["兰州市","兰州","620000"]},
{"map_chart_key":"嘉峪关市","map_alias_name":["嘉峪关市","嘉峪关","620000"]},
{"map_chart_key":"金昌市","map_alias_name":["金昌市","金昌","620000"]},
{"map_chart_key":"白银市","map_alias_name":["白银市","白银","620000"]},
{"map_chart_key":"天水市","map_alias_name":["天水市","天水","620000"]},
{"map_chart_key":"武威市","map_alias_name":["武威市","武威","620000"]},
{"map_chart_key":"张掖市","map_alias_name":["张掖市","张掖","620000"]},
{"map_chart_key":"平凉市","map_alias_name":["平凉市","平凉","620000"]},
{"map_chart_key":"酒泉市","map_alias_name":["酒泉市","酒泉","620000"]},
{"map_chart_key":"庆阳市","map_alias_name":["庆阳市","庆阳","620000"]},
{"map_chart_key":"定西市","map_alias_name":["定西市","定西","620000"]},
{"map_chart_key":"陇南市","map_alias_name":["陇南市","陇南","620000"]},
{"map_chart_key":"临夏回族自治州","map_alias_name":["临夏回族自治州","临夏","620000"]},
{"map_chart_key":"甘南藏族自治州","map_alias_name":["甘南藏族自治州","甘南","620000"]},
{"map_chart_key":"西宁市","map_alias_name":["西宁市","西宁","630000"]},
{"map_chart_key":"海东地区","map_alias_name":["海东地区","海东","630000"]},
{"map_chart_key":"海北藏族自治州","map_alias_name":["海北藏族自治州","630000"]},
{"map_chart_key":"黄南藏族自治州","map_alias_name":["黄南藏族自治州","630000"]},
{"map_chart_key":"海南藏族自治州","map_alias_name":["海南藏族自治州","630000"]},
{"map_chart_key":"果洛藏族自治州","map_alias_name":["果洛藏族自治州","630000"]},
{"map_chart_key":"玉树藏族自治州","map_alias_name":["玉树藏族自治州","630000"]},
{"map_chart_key":"海西蒙古族藏族自治州","map_alias_name":["海西蒙古族藏族自治州","630000"]},
{"map_chart_key":"银川市","map_alias_name":["银川市","银川","640000"]},
{"map_chart_key":"石嘴山市","map_alias_name":["石嘴山市","石嘴山","640000"]},
{"map_chart_key":"吴忠市","map_alias_name":["吴忠市","吴忠","640000"]},
{"map_chart_key":"固原市","map_alias_name":["固原市","固原","640000"]},
{"map_chart_key":"中卫市","map_alias_name":["中卫市","中卫","640000"]},
{"map_chart_key":"乌鲁木齐市","map_alias_name":["乌鲁木齐市","乌鲁木齐","650000"]},
{"map_chart_key":"克拉玛依市","map_alias_name":["克拉玛依市","克拉玛依","650000"]},
{"map_chart_key":"吐鲁番地区","map_alias_name":["吐鲁番地区","吐鲁番","650000"]},
{"map_chart_key":"哈密地区","map_alias_name":["哈密地区","哈密","650000"]},
{"map_chart_key":"昌吉回族自治州","map_alias_name":["昌吉回族自治州","650000"]},
{"map_chart_key":"博尔塔拉蒙古自治州","map_alias_name":["博尔塔拉蒙古自治州","650000"]},
{"map_chart_key":"巴音郭楞蒙古自治州","map_alias_name":["巴音郭楞蒙古自治州","650000"]},
{"map_chart_key":"阿克苏地区","map_alias_name":["阿克苏地区","阿克苏","650000"]},
{"map_chart_key":"克孜勒苏柯尔克孜自治州","map_alias_name":["克孜勒苏柯尔克孜自治州","650000"]},
{"map_chart_key":"喀什地区","map_alias_name":["喀什地区","喀什","650000"]},
{"map_chart_key":"和田地区","map_alias_name":["和田地区","和田","650000"]},
{"map_chart_key":"伊犁哈萨克自治州","map_alias_name":["伊犁哈萨克自治州","650000"]},
{"map_chart_key":"塔城地区","map_alias_name":["塔城地区","塔城","650000"]},
{"map_chart_key":"阿勒泰地区","map_alias_name":["阿勒泰地区","阿勒泰","650000"]}];
}
