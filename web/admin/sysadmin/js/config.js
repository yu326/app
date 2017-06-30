var config ={};
config.systemTitle = "";
config.sitePath = "/sysadmin/";
config.phpPath = config.sitePath + "model/";
config.modelUrl = config.phpPath;
config.dataUrl = config.phpPath+"getdata.php"; //从数据库请求数据
config.solrData = config.modelUrl+"solragent.php"; //从solr请求数据
config.getSourceUrl = config.dataUrl + "?type=getsource";
config.imagePath = config.sitePath+"images/";//图片目录
config.allSource = [];//全部source
config.allAccountSource= [];//全部帐号source
config.allSpiderConfig = [];//全部爬虫配置
/*
config.allSearchEngine =[]; //搜索引擎
config.allSearchSite = []; //爬取站点
*/
config.allDataHost = [];//数据主机
config.allSpiderAccount = [];//数据主机
config.allTask = [];
config.allTaskPageStyleType = [];
/*
config.allTask = {};
config.allTask["1"] = "分析桥接用户";
config.allTask["2"] = "分析桥接案例";
config.allTask["3"] = "重新分析";
config.allTask["4"] = "抓取微博";
config.allTask["5"] = "处理转发";
config.allTask["6"] = "更新转发评论数";
config.allTask["7"] = "抓取评论";
config.allTask["8"] = "批量植入微博";
config.allTask["9"] = "批量植入用户";
config.allTask["10"] = "抓取关键词";
config.allTask["11"] = "抓取关注";
config.allTask["12"] = "迁移数据";
config.allTask["13"] = "更新快照";
config.allTask["14"] = "事件预警";
config.allTask["15"] = "抓取论坛";
*/
config.TASK_BRIDGEUSER = 1; //分析桥接用户
config.TASK_BRIDGECASE = 2;//分析桥接案例
config.TASK_SYNC = 3;//重新分析
config.TASK_WEIBO = 4;//抓取微博
config.TASK_REPOST_TREND = 5;//处理转发
config.TASK_STATUSES_COUNT = 6;//更新转发数、评论数
config.TASK_COMMENTS = 7;//抓取评论
config.TASK_IMPORTWEIBOURL = 8;//批量植入微博
config.TASK_IMPORTUSERID = 9;//批量植入用户
config.TASK_KEYWORD = 10;//抓取关键词
config.TASK_FRIEND = 11;//抓取关注
config.TASK_MIGRATEDATA = 12;//迁移数据
config.TASK_SNAPSHOT = 13;//更新快照
config.TASK_EVENTALERT = 14;//事件预警
config.TASK_WEBPAGE = 15;//抓取网页
config.TASK_REPOSTPATH = 16;//分析转发轨迹
config.TASK_COMMENTPATH = 17;//分析评论轨迹
config.TASK_NICKNAME = 18;//抓取账号微博
config.TASK_DATAPUSH = 19;//抓取账号微博

config.TASK_COMMON = 20;//通用抓取

config.TASK_PAGESTYLE_ARTICLELIST = 1;//1.文章列表
config.TASK_PAGESTYLE_ARTICLEDETAIL = 2;//2.文章详情
config.TASK_PAGESTYLE_USERDETAIL = 3;//3.用户详情
config.TASK_PAGESTYLE_ALL = 4;//4.文章列表＋文章详情＋用户详情

//抓取url配置
/*
config.spiderConfigUrl = [
{"name":"抓取微博", "value":'http://s.weibo.com/weibo/$<topic "%s">&timescope=custom:$<time.year>-$<time.month "%02d">-$<time.day "%02d">-$<time.hour "%02d">:$<time.year>-$<time.month "%02d">-$<time.day "%02d">-$<time.hour "%02d">'},
{"name":"抓取关注", "value":'http://s.weibo.com/weibo/'}
];
*/
config.spiderConfigUrl = {};
config.spiderConfigUrl["1"] = {};
config.spiderConfigUrl["1"]["4"] = "http://s.weibo.com/weibo/";
config.spiderConfigUrl["1"]["10"] = "http://s.weibo.com/weibo/";
config.spiderConfigUrl["1"]["11"] = "http://weibo.com/";
config.spiderConfigUrl["1"]["5"] = "http://weibo.com/";
