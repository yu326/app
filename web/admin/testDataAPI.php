<?php
//通过instanceid和elementid获取数据
//hasformjson 0:通过instanceid和elementid获取数据
//微博模型需要returnoriginal参数为1返回转发微博对应当原创, 其他模型需要置为 0
//offset 从第几条开始
//rows返回多少条
//标准
$url = "http://192.168.0.102/model/requestdata.php?instanceid=473&elementid=476&hasformjson=0&offset=0&rows=10&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
//联动
//$url = "http://192.168.0.102/model/requestdata.php?instanceid=1464&elementid=1614&hasformjson=0&islinkage=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
//叠加
$url = "http://192.168.0.102/model/requestdata.php?instanceid=1458&elementid=1608&hasformjson=0&isoverlay=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
$response = curl_exec($ch);
curl_close($ch); 
$json = json_decode($response, true);
//var_dump($json);
function thirdPartyGetData($jsoninfo, $url) 
{
    if(!$url){
		echo 'opt url is null';
        return false;
    }
    //$senddata = json_encode($jsoninfo);
    $senddata = $jsoninfo;
    $timeout = 0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $senddata);

    $header_array = array('Content-type:application/json');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);

    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE );
    
    //$start_time = microtime_float();
    $response = curl_exec($ch);
    //$end_time = microtime_float();
    if($response === FALSE){
        $log_note = 'curl error is'.curl_error($ch);
		var_dump("log_note".$log_note);
        curl_close($ch);
        return false;
    }
    //关闭cURL资源，并且释放系统资源
    curl_close($ch);
    unset($senddata);
    return $response;
}

$url = "http://192.168.0.102/model/requestdata.php?instanceid=-1&elementid=-1&hasformjson=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
/*
            "datajson": {
                "version": 1036, //请求数据json的版本号
                "modelid": 31,  //请求模型的id, 用户分析:1, 用户统计2, 话题分析31, 微博分析51
                "isdefaultrelation": true, //是否使用默认的relation, 相同字段之间关系为或,不同字段直接关系为且
                "filterrelation": { //filtervalue字段的逻辑关系, fields为数字数组, 数字为filtervalue数组中字段的索引
                    "opt": "and",
                    "filterlist": [],
                    "fields": []
                },
				"filter": {  //可配置的字段列表, 用户分析和用户统计对应用户信息, 列表相同, 话题分析和微博分析对应微博信息, 列表相同
					"weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
                "facet": { //分组统计 , 用户统计模型和话题分析模型用到, field为字段分组统计, range为区间分组统计, 不做分组统计时 field和range为空数组, 不能同时做facet field查询和facet range查询
                    "label": "分组统计",
                    "datatype": "string",
                    "field": [
                        {
                            "name": "organization", //统计字段名称
                            "includeconfig": { //统计字段包含功能, 是否显示其他, 
                                "showother": 0,
                                "alias": "" //其他的显示名称
                            },
                            "filter": [ //包含和去除
                                {
                                    "type": "include",
                                    "value": []
                                },
                                {
                                    "type": "exclude",
                                    "value": []
                                }
                            ],
                            "facettype": 101,
                            "allcount": false,
                            "featureconfig": {
                                "showother": 0,
                                "alias": ""
                            },
                            "isfeature": 0,
                            "feature": []
                        }
                    ],
					"range": [
						{
							"name": "created_at",
							"rangeinfo": {
								"value": {
									"gap": "1month",
									"rangevalue": {
										"type": "time_dynamic_state",
										"value": {
											"start": "1",
											"startgap": "year",
											"timestate": "now",
											"name": "nearlytime"
										}
									}
								},
								"type": "gap"
							},
							"sides": 0,
							"facettype": 101,
							"allcount": false
						}
					]
                },
                "select": { //query查询时,返回的字段对应solrurl的 fl字段
                    "label": "查询字段",
                    "datatype": "string",
                    "value": [
                        "id",
                        "screen_name",
                        "location",
                        "description",
                        "profile_image_url",
                        "followers_count",
                        "friends_count",
                        "statuses_count",
                        "verify",
                        "verified_reason",
                        "verified_type",
                        "sex",
                        "sourceid",
                        "text",
                        "created_at",
                        "reposts_count",
                        "comments_count",
                        "content_type",
                        "userid",
                        "mid",
                        "source",
                        "thumbnail_pic",
                        "bmiddle_pic",
                        "retweeted_status",
                        "retweeted_mid",
                        "guid"
                    ]
                },
                "output": {
                    "label": "输出条件",
                    "datatype": "string",
                    "outputtype": "2", //1:query查询, 2:facet统计
                    "data_limit": 0, //从第几条开始
                    "count": "10", //返回条数
                    "ordertype": "desc",
                    "pageable": true
                },
                "contrast": null, //分类对比查询
                "classifyquery": null, //分类查询
                "filtervalue": [], //查询条件字段对应的值
                "distinct": { //对微博进行分析时, 返回结果相同用户的去重
                    "label": "结果唯一",
                    "datatype": "string",
                    "limit": [
                        {
                            "value": "screen_name",
                            "repeat": 1,
                            "type": "exact"
                        }
                    ],
                    "distinctfield": ""
                }
            }
 */
//用户分析,和用户统计模型
//$jsoninfo = '{ "version": 1036, "modelid": 1, "isdefaultrelation": true, "filterrelation": {"opt":"and","filterlist":[],"fields":[0]}, "filter": { "username": { "label": "查昵称", "datatype": "string" }, "usersfollower": { "label": "查粉丝", "datatype": "string" }, "usersfriend": { "label": "查关注", "datatype": "string" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "followerrank": { "label": "粉丝数", "datatype": "range" }, "friendrank": { "label": "关注数", "datatype": "range" }, "statusesrank": { "label": "微博数", "datatype": "range" }, "users_favourites_count": { "label": "收藏数", "datatype": "range" }, "users_bi_followers_count": { "label": "互粉数", "datatype": "range" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "source": { "label": "来源", "datatype": "int" }, "sex": { "label": "作者性别", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" }, "area": { "label": "地区", "datatype": "value_text_object" }, "users_url": { "label": "博客地址", "datatype": "string" }, "users_domain": { "label": "个性化域名", "datatype": "string" }, "users_allow_all_act_msg": { "label": "允许私信", "datatype": "string" }, "users_allow_all_comment": { "label": "允许评论", "datatype": "string" } }, "facet": { "label": "分组统计", "datatype": "string", "field": [], "range": [] }, "select": { "label": "查询字段", "datatype": "string", "value": [ "users_id", "users_screen_name", "users_location", "users_description", "users_profile_image_url", "users_followers_count", "users_friends_count", "users_statuses_count", "users_favourites_count", "users_bi_followers_count", "users_verified", "users_verified_reason", "users_verified_type", "users_gender", "users_sourceid" ] }, "output": { "label": "输出条件", "datatype": "string", "outputtype": 1, "data_limit": 0, "count": "10", "orderby": "users_followers_count", "ordertype": "desc", "pageable": true }, "contrast": null, "classifyquery": null, "filtervalue":[{"fieldname":"verifiedreason","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"string","value":"董事长"}}]}'; 
//话题分析,微博分析
//$jsoninfo = ' { "version": 1036, "modelid": 31, "isdefaultrelation": true, "filterrelation": {"opt":"and","filterlist":[],"fields":[0]}, "filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } }, "facet": { "label": "分组统计", "datatype": "string", "field": [ { "name": "text", "includeconfig": { "showother": 0, "alias": "" }, "filter": [ { "type": "include", "value": [] }, { "type": "exclude", "value": [] } ], "facettype": 101, "allcount": false, "featureconfig": { "showother": 0, "alias": "" }, "isfeature": 1, "feature": [ { "value": "热门,高频词", "text": "高频词" } ] } ], "range": [] }, "select": { "label": "查询字段", "datatype": "string", "value": [ "bmiddle_pic", "comments_count", "content_type", "created_at", "description", "followers_count", "friends_count", "guid", "id", "location", "mid", "profile_image_url", "reposts_count", "retweeted_mid", "retweeted_status", "screen_name", "sex", "source", "sourceid", "statuses_count", "text", "thumbnail_pic", "userid", "verified_reason", "verified_type", "verify" ] }, "output": { "label": "输出条件", "datatype": "string", "outputtype": "2", "data_limit": 0, "count": 10, "orderby": "created_at", "ordertype": "desc", "pageable": true }, "contrast": null, "classifyquery":{"type":1,"fieldname":"organization"}, "filtervalue": [{"fieldname":"organization","fromlimit":0,"fieldvalue":{"datatype":"array","value":[{"datatype":"string","value":"中石油"},{"datatype":"string","value":"中石化"},{"datatype":"string","value":"发改委"},{"datatype":"string","value":"人民网"}]}}], "distinct": { "label": "结果唯一", "datatype": "string", "distinctfield": "" } } '; 




//联动模型, url参数中 islinkage 置为1;
$url = "http://192.168.0.102/model/requestdata.php?islinkage=1&hasformjson=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
/*
$jsoninfo = '{
    "instanceid": -1,
    "elements": [
        {
            "elementid": -1,
            "datajson": {
                "version": 1036,
                "modelid": 31,
                "isdefaultrelation": true,
                "filterrelation": {
                    "opt": "and",
                    "filterlist": [],
                    "fields": []
                },
"filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
                "facet": {
                    "label": "分组统计",
                    "datatype": "string",
                    "field": [
                        {
                            "name": "organization",
                            "includeconfig": {
                                "showother": 0,
                                "alias": ""
                            },
                            "filter": [
                                {
                                    "type": "include",
                                    "value": []
                                },
                                {
                                    "type": "exclude",
                                    "value": []
                                }
                            ],
                            "facettype": 101,
                            "allcount": false,
                            "featureconfig": {
                                "showother": 0,
                                "alias": ""
                            },
                            "isfeature": 0,
                            "feature": []
                        }
                    ],
                    "range": []
                },
                "select": {
                    "label": "查询字段",
                    "datatype": "string",
                    "value": [
                        "id",
                        "screen_name",
                        "location",
                        "description",
                        "profile_image_url",
                        "followers_count",
                        "friends_count",
                        "statuses_count",
                        "verify",
                        "verified_reason",
                        "verified_type",
                        "sex",
                        "sourceid",
                        "text",
                        "created_at",
                        "reposts_count",
                        "comments_count",
                        "content_type",
                        "userid",
                        "mid",
                        "source",
                        "thumbnail_pic",
                        "bmiddle_pic",
                        "retweeted_status",
                        "retweeted_mid",
                        "guid"
                    ]
                },
                "output": {
                    "label": "输出条件",
                    "datatype": "string",
                    "outputtype": "2",
                    "data_limit": 0,
                    "count": "10",
                    "ordertype": "desc",
                    "pageable": true
                },
                "contrast": null,
                "classifyquery": null,
                "filtervalue": [],
                "distinct": {
                    "label": "结果唯一",
                    "datatype": "string",
                    "limit": [
                        {
                            "value": "screen_name",
                            "repeat": 1,
                            "type": "exact"
                        }
                    ],
                    "distinctfield": ""
                }
            }
        }
    ],
    "pinrelation": [
        {
            "instanceid": -1,
            "inelementid": -2,
            "inpinid": 1,
            "inputdata": {
                "value": "keyword",
                "text": "关键词",
                "opt": "or",
                "datatype": "string",
                "isfeature": 0
            },
            "outelementid": -1,
            "outpinid": 1,
            "outputdata": {
                "pintype": "dynamic",
                "datatype": "string",
                "exclude": false,
                "text": "统计字段",
                "outputfield": "text",
                "value": {
                    "start": null,
                    "end": null
                },
                "isfeature": 0
            }
        }
    ],
    "render": {
        "elementid": -2,
        "datajson": {
            "version": 1036,
            "modelid": 51,
            "isdefaultrelation": true,
            "filterrelation": {
                "opt": "and",
                "filterlist": [],
                "fields": [
                    0
                ]
            },
"filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
            "facet": {
                "label": "分组统计",
                "datatype": "string",
                "field": [],
                "range": []
            },
            "select": {
                "label": "查询字段",
                "datatype": "string",
                "value": [
                    "id",
                    "screen_name",
                    "userid",
                    "mid",
                    "sourceid",
                    "description",
                    "profile_image_url",
                    "followers_count",
                    "friends_count",
                    "statuses_count",
                    "verify",
                    "verified_reason",
                    "verified_type",
                    "sex",
                    "text",
                    "created_at",
                    "reposts_count",
                    "comments_count",
                    "content_type",
                    "source",
                    "thumbnail_pic",
                    "bmiddle_pic",
                    "retweeted_status",
                    "retweeted_mid",
                    "retweeted_guid",
                    "guid"
                ]
            },
            "output": {
                "label": "输出条件",
                "datatype": "string",
                "outputtype": "1",
                "data_limit": 0,
                "count": "10",
                "orderby": "created_at",
                "ordertype": "desc",
                "pageable": true
            },
            "contrast": null,
            "classifyquery": null,
            "filtervalue": [
                {
                    "fieldname": "keyword",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "dynamic",
                        "value": {
                            "start": null,
                            "end": null
                        },
                        "outelementid": -1,
                        "outputfield": "text"
                    }
                }
            ],
            "distinct": {
                "label": "结果唯一",
                "datatype": "string",
                "limit": [
                    {
                        "value": "screen_name",
                        "repeat": 1,
                        "type": "exact"
                    }
                ],
                "distinctfield": ""
            }
        }
    }
}';
 */
$jsoninfo = '
{
    "instanceid": -1,
    "elements": [],
    "pinrelation": [
        {
            "instanceid": -1,
            "inelementid": -2,
            "inpinid": 2,
            "inputdata": {
                "value": "organization",
                "text": "机构",
                "opt": "or",
                "datatype": "string",
                "isfeature": 0
            },
            "outelementid": -1,
            "outpinid": 2,
            "outputdata": {
                "pintype": "static",
                "datatype": "string",
                "exclude": false,
                "text": "统计字段",
                "outputfield": "text",
                "value": [
                    {
                        "text": "发改委",
                        "value": "发改委"
                    },
                    {
                        "text": "人民网",
                        "value": "人民网"
                    },
                    {
                        "text": "中石油",
                        "value": "中石油"
                    }
                ],
                "isfeature": 0
            }
        }
    ],
    "render": {
        "elementid": -2,
        "datajson": {
            "version": 1036,
            "modelid": 51,
            "isdefaultrelation": true,
            "filterrelation": {
                "opt": "and",
                "filterlist": [
                    {
                        "opt": "or",
                        "filterlist": [],
                        "fields": [
                            0,
                            1,
                            2
                        ]
                    }
                ],
                "fields": []
            },
"filter": { "weiboid": { "label": "微博ID", "datatype": "string" }, "weibourl": { "label": "微博URL", "datatype": "string" }, "oristatus": { "label": "原创ID", "datatype": "string" }, "oristatusurl": { "label": "原创URL", "datatype": "string" }, "oristatus_username": { "label": "昵称查转发", "datatype": "string" }, "oristatus_userid": { "label": "用户名查转发", "datatype": "value_text_object" }, "repost_url": { "label": "转发URL", "datatype": "string" }, "repost_username": { "label": "昵称查原创", "datatype": "string" }, "repost_userid": { "label": "用户名查原创", "datatype": "value_text_object" }, "searchword": { "label": "关键词", "datatype": "string" }, "organization": { "label": "机构", "datatype": "string" }, "account": { "label": "@用户", "datatype": "value_text_object" }, "userid": { "label": "用户名", "datatype": "value_text_object" }, "weibotopic": { "label": "微博话题", "datatype": "string" }, "weibotopickeyword": { "label": "微博话题关键词", "datatype": "string" }, "weibotopiccombinword": { "label": "微博话题短语", "datatype": "string" }, "NRN": { "label": "人名", "datatype": "string" }, "topic": { "label": "短语", "datatype": "string" }, "business": { "label": "行业", "datatype": "value_text_object" }, "repostsnum": { "label": "转发数", "datatype": "range" }, "commentsnum": { "label": "评论数", "datatype": "range" }, "total_reposts_count": { "label": "总转发数", "datatype": "range" }, "direct_reposts_count": { "label": "直接转发数", "datatype": "range" }, "total_reach_count": { "label": "总到达数", "datatype": "range" }, "followers_count": { "label": "直接到达数", "datatype": "range" }, "repost_trend_cursor": { "label": "转发所处层级", "datatype": "range" }, "areauser": { "label": "用户地区", "datatype": "value_text_object" }, "areamentioned": { "label": "提及地区", "datatype": "value_text_object" }, "createdtime": { "label": "发布时间", "datatype": "range" }, "nearlytime": { "label": "相对今天", "datatype": "time_dynamic_state" }, "beforetime": { "label": "时间段", "datatype": "time_dynamic_state" }, "untiltime": { "label": "日历时间", "datatype": "time_dynamic_range" }, "emotion": { "label": "情感关键词", "datatype": "string" }, "emoCombin": { "label": "情感短语", "datatype": "string" }, "emoNRN": { "label": "情感人名", "datatype": "string" }, "emoOrganization": { "label": "情感机构", "datatype": "string" }, "emoTopic": { "label": "情感微博话题", "datatype": "string" }, "emoTopicKeyword": { "label": "情感微博话题关键词", "datatype": "string" }, "emoTopicCombinWord": { "label": "情感微博话题短语", "datatype": "string" }, "emoAccount": { "label": "@用户情感", "datatype": "value_text_object" }, "emoBusiness": { "label": "行业情感", "datatype": "value_text_object" }, "emoAreamentioned": { "label": "地区情感", "datatype": "value_text_object" }, "weibotype": { "label": "微博类型", "datatype": "int" }, "source": { "label": "应用来源", "datatype": "string" }, "hostdomain": { "label": "主机域名", "datatype": "string" }, "sourceid": { "label": "来源", "datatype": "int" }, "username": { "label": "作者昵称", "datatype": "string" }, "verified": { "label": "认证", "datatype": "int" }, "verified_type": { "label": "认证类型", "datatype": "int" }, "haspicture": { "label": "含有图片", "datatype": "int" }, "verifiedreason": { "label": "认证原因", "datatype": "string" }, "registertime": { "label": "博龄", "datatype": "gaprange" }, "sex": { "label": "作者性别", "datatype": "string" }, "originalcontent": { "label": "原文内容", "datatype": "string" }, "digestcontent": { "label": "摘要内容", "datatype": "string" }, "description": { "label": "简介", "datatype": "string" } },
            "facet": {
                "label": "分组统计",
                "datatype": "string",
                "field": [],
                "range": []
            },
            "select": {
                "label": "查询字段",
                "datatype": "string",
                "value": [
                    "id",
                    "screen_name",
                    "userid",
                    "mid",
                    "sourceid",
                    "description",
                    "profile_image_url",
                    "followers_count",
                    "friends_count",
                    "statuses_count",
                    "verify",
                    "verified_reason",
                    "verified_type",
                    "sex",
                    "text",
                    "created_at",
                    "reposts_count",
                    "comments_count",
                    "content_type",
                    "source",
                    "thumbnail_pic",
                    "bmiddle_pic",
                    "retweeted_status",
                    "retweeted_mid",
                    "retweeted_guid",
                    "guid"
                ]
            },
            "output": {
                "label": "输出条件",
                "datatype": "string",
                "outputtype": "1",
                "data_limit": 0,
                "count": "10",
                "orderby": "created_at",
                "ordertype": "desc",
                "pageable": true
            },
            "contrast": null,
            "classifyquery": null,
            "filtervalue": [
                {
                    "fieldname": "organization",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "string",
                        "value": "发改委"
                    }
                },
                {
                    "fieldname": "organization",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "string",
                        "value": "人民网"
                    }
                },
                {
                    "fieldname": "organization",
                    "fromlimit": 0,
                    "isfeature": 0,
                    "exclude": 0,
                    "fieldvalue": {
                        "datatype": "string",
                        "value": "中石油"
                    }
                }
            ],
            "distinct": {
                "label": "结果唯一",
                "datatype": "string",
                "distinctfield": ""
            }
        }
    }
}';
//叠加分析模型
$url = "http://192.168.0.102/model/requestdata.php?isoverlay=1&hasformjson=1&offset=0&rows=20&returnoriginal=0&token=5a35Dg2IX+eqh8cfRSgZhc1A0zim/knB7HSAFCX84u+z0w";
$jsoninfo = '[{"elementid":-2,"instanceid":-1,"instancetype":1,"modelname":"用户","referencedata":false,"secondaryyaxis":false,"showid":"smallmulticolumn3d","referencedataratio":"","datajson":{"version":1040,"modelid":31,"isdefaultrelation":true,"filterrelation":{"opt":"and","filterlist":[],"fields":[0]},"filter":{"weiboid":{"label":"微博ID","datatype":"string"},"weibourl":{"label":"微博URL","datatype":"string"},"oristatus":{"label":"原创ID","datatype":"string"},"oristatusurl":{"label":"原创URL","datatype":"string"},"oristatus_username":{"label":"昵称查转发","datatype":"string"},"oristatus_userid":{"label":"用户名查转发","datatype":"value_text_object"},"repost_url":{"label":"转发URL","datatype":"string"},"repost_username":{"label":"昵称查原创","datatype":"string"},"repost_userid":{"label":"用户名查原创","datatype":"value_text_object"},"searchword":{"label":"关键词","datatype":"string"},"organization":{"label":"机构","datatype":"string"},"account":{"label":"@用户","datatype":"value_text_object"},"userid":{"label":"用户名","datatype":"value_text_object"},"weibotopic":{"label":"微博话题","datatype":"string"},"weibotopickeyword":{"label":"微博话题关键词","datatype":"string"},"weibotopiccombinword":{"label":"微博话题短语","datatype":"string"},"NRN":{"label":"人名","datatype":"string"},"topic":{"label":"短语","datatype":"string"},"business":{"label":"行业","datatype":"value_text_object"},"repostsnum":{"label":"转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"commentsnum":{"label":"评论数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reposts_count":{"label":"总转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"direct_reposts_count":{"label":"直接转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reach_count":{"label":"总到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"followers_count":{"label":"直接到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"repost_trend_cursor":{"label":"转发所处层级","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"areauser":{"label":"用户地区","datatype":"value_text_object"},"areamentioned":{"label":"提及地区","datatype":"value_text_object"},"createdtime":{"label":"创建时间","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"nearlytime":{"label":"相对今天","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"beforetime":{"label":"时间段","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"untiltime":{"label":"日历时间","datatype":"time_dynamic_range","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_year":{"label":"创建时间(年)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_month":{"label":"创建时间(月)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_day":{"label":"创建时间(日)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_hour":{"label":"创建时间(时)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_weekday":{"label":"创建时间(周)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"emotion":{"label":"情感关键词","datatype":"string"},"emoCombin":{"label":"情感短语","datatype":"string"},"emoNRN":{"label":"情感人名","datatype":"string"},"emoOrganization":{"label":"情感机构","datatype":"string"},"emoTopic":{"label":"情感微博话题","datatype":"string"},"emoTopicKeyword":{"label":"情感微博话题关键词","datatype":"string"},"emoTopicCombinWord":{"label":"情感微博话题短语","datatype":"string"},"emoAccount":{"label":"@用户情感","datatype":"value_text_object"},"emoBusiness":{"label":"行业情感","datatype":"value_text_object"},"emoAreamentioned":{"label":"地区情感","datatype":"value_text_object"},"weibotype":{"label":"微博类型","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"source":{"label":"应用来源","datatype":"string"},"hostdomain":{"label":"主机域名","datatype":"string"},"sourceid":{"label":"数据来源","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"username":{"label":"作者昵称","datatype":"string"},"verified":{"label":"认证","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verified_type":{"label":"认证类型","datatype":"int"},"haspicture":{"label":"含有图片","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verifiedreason":{"label":"认证原因","datatype":"string"},"registertime":{"label":"博龄","datatype":"gaprange","limit":[{"value":{"maxvalue":null,"minvalue":null,"gap":"year"},"type":"gaprange","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"sex":{"label":"作者性别","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"originalcontent":{"label":"原文内容","datatype":"string"},"digestcontent":{"label":"摘要内容","datatype":"string"},"description":{"label":"简介","datatype":"string"},"ancestor_text":{"label":"上层转发关键词","datatype":"string"},"ancestor_organization":{"label":"上层转发机构","datatype":"string"},"ancestor_account":{"label":"上层转发@用户","datatype":"value_text_object"},"ancestor_wb_topic":{"label":"上层转发微博话题","datatype":"string"},"ancestor_wb_topic_keyword":{"label":"上层转发微博话题关键词","datatype":"string"},"ancestor_wb_topic_combinWord":{"label":"上层转发微博话题短语","datatype":"string"},"ancestor_NRN":{"label":"上层转发人名","datatype":"string"},"ancestor_combinWord":{"label":"上层转发短语","datatype":"string"},"ancestor_business":{"label":"上层转发行业","datatype":"value_text_object"},"ancestor_areamentioned":{"label":"上层转发提及地区","datatype":"value_text_object"},"ancestor_emotion":{"label":"上层转发情感关键词","datatype":"string"},"ancestor_emoCombin":{"label":"上层转发情感短语","datatype":"string"},"ancestor_emoNRN":{"label":"上层转发情感人名","datatype":"string"},"ancestor_emoOrganization":{"label":"上层转发情感机构","datatype":"string"},"ancestor_emoTopic":{"label":"上层转发情感微博话题","datatype":"string"},"ancestor_emoTopicKeyword":{"label":"上层转发情感微博话题关键词","datatype":"string"},"ancestor_emoTopicCombinWord":{"label":"上层转发情感微博话题短语","datatype":"string"},"ancestor_emoAccount":{"label":"上层转发@用户情感","datatype":"value_text_object"},"ancestor_emoBusiness":{"label":"上层转发行业情感","datatype":"value_text_object"},"ancestor_emoAreamentioned":{"label":"上层转发地区情感","datatype":"value_text_object"},"ancestor_url":{"label":"上层转发URL","datatype":"string"},"ancestor_host_domain":{"label":"上层转发主机域名","datatype":"string"},"ancestor_similar":{"label":"上层转发摘要内容","datatype":"string"}},"facet":{"label":"分组统计","datatype":"string","limit":[{"value":"text","type":"exact","repeat":1},{"value":"organization","type":"exact","repeat":1},{"value":"wb_topic","type":"exact","repeat":1},{"value":"wb_topic_keyword","type":"exact","repeat":1},{"value":"wb_topic_combinWord","type":"exact","repeat":1},{"value":"account","type":"exact","repeat":1},{"value":"country","type":"exact","repeat":1},{"value":"country_code","type":"exact","repeat":1},{"value":"province_code","type":"exact","repeat":1},{"value":"city","type":"exact","repeat":1},{"value":"city_code","type":"exact","repeat":1},{"value":"district","type":"exact","repeat":1},{"value":"district_code","type":"exact","repeat":1},{"value":"business","type":"exact","repeat":1},{"value":"url","type":"exact","repeat":1},{"value":"created_at","type":"exact","repeat":1},{"value":"retweeted_status","type":"exact","repeat":1},{"value":"screen_name","type":"exact","repeat":1},{"value":"reposts_count","type":"exact","repeat":1},{"value":"comments_count","type":"exact","repeat":1},{"value":"register_time","type":"exact","repeat":1},{"value":"sex","type":"exact","repeat":1},{"value":"verify","type":"exact","repeat":1},{"value":"has_picture","type":"exact","repeat":1},{"value":"emotion","type":"exact","repeat":1},{"value":"originalText","type":"exact","repeat":1},{"value":"similar","type":"exact","repeat":1},{"value":"verified_reason","type":"exact","repeat":1},{"value":"verified_type","type":"exact","repeat":1},{"value":"description","type":"exact","repeat":1},{"value":"source","type":"exact","repeat":1},{"value":"emoCombin","type":"exact","repeat":1},{"value":"emoOrganization","type":"exact","repeat":1},{"value":"emoTopic","type":"exact","repeat":1},{"value":"emoTopicKeyword","type":"exact","repeat":1},{"value":"emoTopicCombinWord","type":"exact","repeat":1},{"value":"emoBusiness","type":"exact","repeat":1},{"value":"emoCountry","type":"exact","repeat":1},{"value":"emoProvince","type":"exact","repeat":1},{"value":"emoCity","type":"exact","repeat":1},{"value":"emoDistrict","type":"exact","repeat":1},{"value":"userid","type":"exact","repeat":1},{"value":"content_type","type":"exact","repeat":1},{"value":"host_domain","type":"exact","repeat":1},{"value":"total_reposts_count","type":"exact","repeat":1},{"value":"direct_reposts_count","type":"exact","repeat":1},{"value":"followers_count","type":"exact","repeat":1},{"value":"repost_trend_cursor","type":"exact","repeat":1},{"value":"emoAccount","type":"exact","repeat":1},{"value":"emoNRN","type":"exact","repeat":1},{"value":"NRN","type":"exact","repeat":1},{"value":"province","type":"exact","repeat":1},{"value":"combinWord","type":"exact","repeat":1},{"value":"total_reach_count","type":"exact","repeat":1},{"value":"ancestor_text","type":"exact","repeat":1},{"value":"ancestor_NRN","type":"exact","repeat":1},{"value":"ancestor_wb_topic","type":"exact","repeat":1},{"value":"ancestor_country","type":"exact","repeat":1},{"value":"ancestor_district","type":"exact","repeat":1},{"value":"ancestor_emoNRN","type":"exact","repeat":1},{"value":"ancestor_emoTopicKeyword","type":"exact","repeat":1},{"value":"ancestor_emoBusiness","type":"exact","repeat":1},{"value":"ancestor_emoCity","type":"exact","repeat":1},{"value":"ancestor_host_domain","type":"exact","repeat":1},{"value":"ancestor_similar","type":"exact","repeat":1},{"value":"ancestor_emoDistrict","type":"exact","repeat":1},{"value":"ancestor_emoCountry","type":"exact","repeat":1},{"value":"ancestor_emoTopicCombinWord","type":"exact","repeat":1},{"value":"ancestor_emoOrganization","type":"exact","repeat":1},{"value":"ancestor_emotion","type":"exact","repeat":1},{"value":"ancestor_province","type":"exact","repeat":1},{"value":"ancestor_combinWord","type":"exact","repeat":1},{"value":"ancestor_wb_topic_keyword","type":"exact","repeat":1},{"value":"ancestor_organization","type":"exact","repeat":1},{"value":"ancestor_account","type":"exact","repeat":1},{"value":"ancestor_wb_topic_combinWord","type":"exact","repeat":1},{"value":"ancestor_business","type":"exact","repeat":1},{"value":"ancestor_city","type":"exact","repeat":1},{"value":"ancestor_emoCombin","type":"exact","repeat":1},{"value":"ancestor_emoTopic","type":"exact","repeat":1},{"value":"ancestor_emoAccount","type":"exact","repeat":1},{"value":"ancestor_emoProvince","type":"exact","repeat":1},{"value":"ancestor_url","type":"exact","repeat":1},{"value":"created_year","type":"exact","repeat":1},{"value":"created_hour","type":"exact","repeat":1},{"value":"created_day","type":"exact","repeat":1},{"value":"created_month","type":"exact","repeat":1},{"value":"created_weekday","type":"exact","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"filterlimit":{"label":"输出过滤","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"field":[{"name":"account","includeconfig":{"showother":0,"alias":""},"filter":[],"facettype":101,"allcount":false,"featureconfig":{"showother":0,"alias":""},"isfeature":0,"feature":[]}],"range":[]},"select":{"label":"查询字段","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":0,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"value":["bmiddle_pic","comments_count","content_type","created_at","description","followers_count","friends_count","guid","id","location","mid","profile_image_url","reposts_count","retweeted_mid","retweeted_status","screen_name","sex","source","sourceid","statuses_count","text","thumbnail_pic","userid","verified_reason","verified_type","verify"]},"output":{"label":"输出条件","datatype":"string","limit":[{"value":"comments_count","repeat":1,"type":"exact"},{"value":"reposts_count","repeat":1,"type":"exact"}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"outputtype":"2","countlimit":{"label":"数据量限制","datatype":"range","limit":[{"value":{"maxvalue":100,"minvalue":0},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"data_limit":0,"count":10,"ordertype":"desc","pageable":false},"contrast":null,"classifyquery":null,"filtervalue":[{"fieldname":"nearlytime","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"time_dynamic_state","value":{"start":"1","startgap":"month","datestate":"now","timestate":"now"}}}],"download_DataLimit":1000,"download_DataLimit_limitcontrol":-1,"download_FieldLimit_limitcontrol":-1,"allowDownload":true,"download_FieldLimit":[{"text":"序号","value":"number"},{"text":"统计结果","value":"facet"},{"text":"文章数","value":"frq"},{"text":"转发数","value":"reposts_count"},{"text":"评论数","value":"comments_count"},{"text":"讨论数","value":"discuss_count"},{"text":"直接转发数","value":"direct_reposts_count"},{"text":"总转发数","value":"total_reposts_count"},{"text":"直接到达数","value":"followers_count"},{"text":"总到达数","value":"total_reach_count"}],"distinct":{"label":"结果唯一","datatype":"string","limit":[{"value":"screen_name","repeat":1,"type":"exact"}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"distinctfield":""}}},{"datajson":{"instanceid":-1,"elements":[{"elementid":-3,"datajson":{"version":1040,"modelid":51,"isdefaultrelation":true,"filterrelation":{"opt":"and","filterlist":[],"fields":[0]},"filter":{"weiboid":{"label":"微博ID","datatype":"string"},"weibourl":{"label":"微博URL","datatype":"string"},"oristatus":{"label":"原创ID","datatype":"string"},"oristatusurl":{"label":"原创URL","datatype":"string"},"oristatus_username":{"label":"昵称查转发","datatype":"string"},"oristatus_userid":{"label":"用户名查转发","datatype":"value_text_object"},"repost_url":{"label":"转发URL","datatype":"string"},"repost_username":{"label":"昵称查原创","datatype":"string"},"repost_userid":{"label":"用户名查原创","datatype":"value_text_object"},"keyword":{"label":"关键词","datatype":"string"},"organization":{"label":"机构","datatype":"string"},"account":{"label":"@用户","datatype":"value_text_object"},"userid":{"label":"用户名","datatype":"value_text_object"},"weibotopic":{"label":"微博话题","datatype":"string"},"weibotopickeyword":{"label":"微博话题关键词","datatype":"string"},"weibotopiccombinword":{"label":"微博话题短语","datatype":"string"},"NRN":{"label":"人名","datatype":"string"},"topic":{"label":"短语","datatype":"string"},"business":{"label":"行业","datatype":"value_text_object"},"repostsnum":{"label":"转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"commentsnum":{"label":"评论数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reposts_count":{"label":"总转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"direct_reposts_count":{"label":"直接转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reach_count":{"label":"总到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"followers_count":{"label":"直接到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"repost_trend_cursor":{"label":"转发所处层级","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"areauser":{"label":"用户地区","datatype":"value_text_object"},"areamentioned":{"label":"提及地区","datatype":"value_text_object"},"createdtime":{"label":"创建时间","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"nearlytime":{"label":"相对今天","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"beforetime":{"label":"时间段","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"untiltime":{"label":"日历时间","datatype":"time_dynamic_range","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_year":{"label":"创建时间(年)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_month":{"label":"创建时间(月)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_day":{"label":"创建时间(日)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_hour":{"label":"创建时间(时)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_weekday":{"label":"创建时间(周)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"emotion":{"label":"情感关键词","datatype":"string"},"emoCombin":{"label":"情感短语","datatype":"string"},"emoNRN":{"label":"情感人名","datatype":"string"},"emoOrganization":{"label":"情感机构","datatype":"string"},"emoTopic":{"label":"情感微博话题","datatype":"string"},"emoTopicKeyword":{"label":"情感微博话题关键词","datatype":"string"},"emoTopicCombinWord":{"label":"情感微博话题短语","datatype":"string"},"emoAccount":{"label":"@用户情感","datatype":"value_text_object"},"emoBusiness":{"label":"行业情感","datatype":"value_text_object"},"emoAreamentioned":{"label":"地区情感","datatype":"value_text_object"},"weibotype":{"label":"微博类型","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"source":{"label":"应用来源","datatype":"string"},"hostdomain":{"label":"主机域名","datatype":"string"},"sourceid":{"label":"数据来源","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"username":{"label":"作者昵称","datatype":"string"},"verified":{"label":"认证","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verified_type":{"label":"认证类型","datatype":"int"},"haspicture":{"label":"含有图片","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"registertime":{"label":"博龄","datatype":"gaprange","limit":[{"value":{"maxvalue":null,"minvalue":null,"gap":"day"},"type":"gaprange","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":1,"limitcontrol":-1,"required":false},"sex":{"label":"作者性别","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verifiedreason":{"label":"认证原因","datatype":"string"},"description":{"label":"简介","datatype":"string"},"originalcontent":{"label":"原文内容","datatype":"string"},"digestcontent":{"label":"摘要内容","datatype":"string"},"ancestor_text":{"label":"上层转发关键词","datatype":"string"},"ancestor_organization":{"label":"上层转发机构","datatype":"string"},"ancestor_account":{"label":"上层转发@用户","datatype":"value_text_object"},"ancestor_wb_topic":{"label":"上层转发微博话题","datatype":"string"},"ancestor_wb_topic_keyword":{"label":"上层转发微博话题关键词","datatype":"string"},"ancestor_wb_topic_combinWord":{"label":"上层转发微博话题短语","datatype":"string"},"ancestor_NRN":{"label":"上层转发人名","datatype":"string"},"ancestor_combinWord":{"label":"上层转发短语","datatype":"string"},"ancestor_business":{"label":"上层转发行业","datatype":"value_text_object"},"ancestor_areamentioned":{"label":"上层转发提及地区","datatype":"value_text_object"},"ancestor_emotion":{"label":"上层转发情感关键词","datatype":"string"},"ancestor_emoCombin":{"label":"上层转发情感短语","datatype":"string"},"ancestor_emoNRN":{"label":"上层转发情感人名","datatype":"string"},"ancestor_emoOrganization":{"label":"上层转发情感机构","datatype":"string"},"ancestor_emoTopic":{"label":"上层转发情感微博话题","datatype":"string"},"ancestor_emoTopicKeyword":{"label":"上层转发情感微博话题关键词","datatype":"string"},"ancestor_emoTopicCombinWord":{"label":"上层转发情感微博话题短语","datatype":"string"},"ancestor_emoAccount":{"label":"上层转发@用户情感","datatype":"value_text_object"},"ancestor_emoBusiness":{"label":"上层转发行业情感","datatype":"value_text_object"},"ancestor_emoAreamentioned":{"label":"上层转发地区情感","datatype":"value_text_object"},"ancestor_url":{"label":"上层转发URL","datatype":"string"},"ancestor_host_domain":{"label":"上层转发主机域名","datatype":"string"},"ancestor_similar":{"label":"上层转发摘要内容","datatype":"string"}},"facet":{"label":"分组统计","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":null,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"filterlimit":{"label":"输出过滤","datatype":"string"},"field":[],"range":[]},"select":{"label":"查询字段","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":0,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":0,"limitcontrol":-1,"required":false,"value":["account","bmiddle_pic","comments_count","content_type","created_at","description","followers_count","friends_count","guid","id","mid","profile_image_url","reposts_count","retweeted_guid","retweeted_mid","retweeted_status","screen_name","sex","source","sourceid","statuses_count","text","thumbnail_pic","userid","verified_reason","verified_type","verify"]},"output":{"label":"输出条件","datatype":"string","limit":[{"value":"comments_count","repeat":1,"type":"exact"},{"value":"reposts_count","repeat":1,"type":"exact"},{"value":"created_at","repeat":1,"type":"exact"},{"value":"direct_reposts_count","repeat":1,"type":"exact"},{"value":"total_reposts_count","repeat":1,"type":"exact"}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"outputtype":"1","countlimit":{"label":"数据量限制","datatype":"range","limit":[{"value":{"maxvalue":100,"minvalue":0},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"data_limit":0,"count":"10","orderby":"created_at","ordertype":"desc","pageable":true},"contrast":null,"classifyquery":null,"filtervalue":[{"fieldname":"nearlytime","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"time_dynamic_state","value":{"start":"1","startgap":"month","datestate":"now","timestate":"now"}}}],"download_DataLimit":1000,"download_DataLimit_limitcontrol":-1,"download_FieldLimit_limitcontrol":-1,"allowDownload":true,"download_FieldLimit":[],"distinct":{"label":"结果唯一","datatype":"string","limit":[{"value":"screen_name","repeat":1,"type":"exact"}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"distinctfield":""}}}],"pinrelation":[{"instanceid":-1,"inelementid":-4,"inpinid":1,"inputdata":{"value":"account","text":"@用户","opt":"or","datatype":"value_text_object","isfeature":0},"outelementid":-3,"outpinid":1,"outputdata":{"pintype":"dynamic","datatype":"value_text_object","exclude":false,"text":"用户名","outputfield":"userid","value":{"start":null,"end":null}},"overlayindex":"2"},{"instanceid":-1,"inelementid":-4,"inpinid":1,"inputdata":{"value":"account","text":"@用户","opt":"or","datatype":"value_text_object","isfeature":0},"outelementid":-3,"outpinid":1,"outputdata":{"pintype":"dynamic","datatype":"value_text_object","exclude":false,"text":"用户名","outputfield":"userid","value":{"start":null,"end":null}},"overlayindex":"2"}],"render":{"elementid":-4,"datajson":{"version":1040,"modelid":31,"isdefaultrelation":true,"filterrelation":{"opt":"and","filterlist":[],"fields":[0,1]},"filter":{"weiboid":{"label":"微博ID","datatype":"string"},"weibourl":{"label":"微博URL","datatype":"string"},"oristatus":{"label":"原创ID","datatype":"string"},"oristatusurl":{"label":"原创URL","datatype":"string"},"oristatus_username":{"label":"昵称查转发","datatype":"string"},"oristatus_userid":{"label":"用户名查转发","datatype":"value_text_object"},"repost_url":{"label":"转发URL","datatype":"string"},"repost_username":{"label":"昵称查原创","datatype":"string"},"repost_userid":{"label":"用户名查原创","datatype":"value_text_object"},"searchword":{"label":"关键词","datatype":"string"},"organization":{"label":"机构","datatype":"string"},"account":{"label":"@用户","datatype":"value_text_object"},"userid":{"label":"用户名","datatype":"value_text_object"},"weibotopic":{"label":"微博话题","datatype":"string"},"weibotopickeyword":{"label":"微博话题关键词","datatype":"string"},"weibotopiccombinword":{"label":"微博话题短语","datatype":"string"},"NRN":{"label":"人名","datatype":"string"},"topic":{"label":"短语","datatype":"string"},"business":{"label":"行业","datatype":"value_text_object"},"repostsnum":{"label":"转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"commentsnum":{"label":"评论数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reposts_count":{"label":"总转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"direct_reposts_count":{"label":"直接转发数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"total_reach_count":{"label":"总到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"followers_count":{"label":"直接到达数","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"repost_trend_cursor":{"label":"转发所处层级","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"areauser":{"label":"用户地区","datatype":"value_text_object"},"areamentioned":{"label":"提及地区","datatype":"value_text_object"},"createdtime":{"label":"创建时间","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"nearlytime":{"label":"相对今天","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"beforetime":{"label":"时间段","datatype":"time_dynamic_state","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"untiltime":{"label":"日历时间","datatype":"time_dynamic_range","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_year":{"label":"创建时间(年)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_month":{"label":"创建时间(月)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_day":{"label":"创建时间(日)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_hour":{"label":"创建时间(时)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"created_weekday":{"label":"创建时间(周)","datatype":"range","limit":[{"value":{"maxvalue":null,"minvalue":null},"type":"range","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"emotion":{"label":"情感关键词","datatype":"string"},"emoCombin":{"label":"情感短语","datatype":"string"},"emoNRN":{"label":"情感人名","datatype":"string"},"emoOrganization":{"label":"情感机构","datatype":"string"},"emoTopic":{"label":"情感微博话题","datatype":"string"},"emoTopicKeyword":{"label":"情感微博话题关键词","datatype":"string"},"emoTopicCombinWord":{"label":"情感微博话题短语","datatype":"string"},"emoAccount":{"label":"@用户情感","datatype":"value_text_object"},"emoBusiness":{"label":"行业情感","datatype":"value_text_object"},"emoAreamentioned":{"label":"地区情感","datatype":"value_text_object"},"weibotype":{"label":"微博类型","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"source":{"label":"应用来源","datatype":"string"},"hostdomain":{"label":"主机域名","datatype":"string"},"sourceid":{"label":"数据来源","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"username":{"label":"作者昵称","datatype":"string"},"verified":{"label":"认证","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verified_type":{"label":"认证类型","datatype":"int"},"haspicture":{"label":"含有图片","datatype":"int","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"verifiedreason":{"label":"认证原因","datatype":"string"},"registertime":{"label":"博龄","datatype":"gaprange","limit":[{"value":{"maxvalue":null,"minvalue":null,"gap":"year"},"type":"gaprange","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"sex":{"label":"作者性别","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"originalcontent":{"label":"原文内容","datatype":"string"},"digestcontent":{"label":"摘要内容","datatype":"string"},"description":{"label":"简介","datatype":"string"},"ancestor_text":{"label":"上层转发关键词","datatype":"string"},"ancestor_organization":{"label":"上层转发机构","datatype":"string"},"ancestor_account":{"label":"上层转发@用户","datatype":"value_text_object"},"ancestor_wb_topic":{"label":"上层转发微博话题","datatype":"string"},"ancestor_wb_topic_keyword":{"label":"上层转发微博话题关键词","datatype":"string"},"ancestor_wb_topic_combinWord":{"label":"上层转发微博话题短语","datatype":"string"},"ancestor_NRN":{"label":"上层转发人名","datatype":"string"},"ancestor_combinWord":{"label":"上层转发短语","datatype":"string"},"ancestor_business":{"label":"上层转发行业","datatype":"value_text_object"},"ancestor_areamentioned":{"label":"上层转发提及地区","datatype":"value_text_object"},"ancestor_emotion":{"label":"上层转发情感关键词","datatype":"string"},"ancestor_emoCombin":{"label":"上层转发情感短语","datatype":"string"},"ancestor_emoNRN":{"label":"上层转发情感人名","datatype":"string"},"ancestor_emoOrganization":{"label":"上层转发情感机构","datatype":"string"},"ancestor_emoTopic":{"label":"上层转发情感微博话题","datatype":"string"},"ancestor_emoTopicKeyword":{"label":"上层转发情感微博话题关键词","datatype":"string"},"ancestor_emoTopicCombinWord":{"label":"上层转发情感微博话题短语","datatype":"string"},"ancestor_emoAccount":{"label":"上层转发@用户情感","datatype":"value_text_object"},"ancestor_emoBusiness":{"label":"上层转发行业情感","datatype":"value_text_object"},"ancestor_emoAreamentioned":{"label":"上层转发地区情感","datatype":"value_text_object"},"ancestor_url":{"label":"上层转发URL","datatype":"string"},"ancestor_host_domain":{"label":"上层转发主机域名","datatype":"string"},"ancestor_similar":{"label":"上层转发摘要内容","datatype":"string"}},"facet":{"label":"分组统计","datatype":"string","limit":[{"value":"text","type":"exact","repeat":1},{"value":"organization","type":"exact","repeat":1},{"value":"wb_topic","type":"exact","repeat":1},{"value":"wb_topic_keyword","type":"exact","repeat":1},{"value":"wb_topic_combinWord","type":"exact","repeat":1},{"value":"account","type":"exact","repeat":1},{"value":"country","type":"exact","repeat":1},{"value":"country_code","type":"exact","repeat":1},{"value":"province_code","type":"exact","repeat":1},{"value":"city","type":"exact","repeat":1},{"value":"city_code","type":"exact","repeat":1},{"value":"district","type":"exact","repeat":1},{"value":"district_code","type":"exact","repeat":1},{"value":"business","type":"exact","repeat":1},{"value":"url","type":"exact","repeat":1},{"value":"created_at","type":"exact","repeat":1},{"value":"retweeted_status","type":"exact","repeat":1},{"value":"screen_name","type":"exact","repeat":1},{"value":"reposts_count","type":"exact","repeat":1},{"value":"comments_count","type":"exact","repeat":1},{"value":"register_time","type":"exact","repeat":1},{"value":"sex","type":"exact","repeat":1},{"value":"verify","type":"exact","repeat":1},{"value":"has_picture","type":"exact","repeat":1},{"value":"emotion","type":"exact","repeat":1},{"value":"originalText","type":"exact","repeat":1},{"value":"similar","type":"exact","repeat":1},{"value":"verified_reason","type":"exact","repeat":1},{"value":"verified_type","type":"exact","repeat":1},{"value":"description","type":"exact","repeat":1},{"value":"source","type":"exact","repeat":1},{"value":"emoCombin","type":"exact","repeat":1},{"value":"emoOrganization","type":"exact","repeat":1},{"value":"emoTopic","type":"exact","repeat":1},{"value":"emoTopicKeyword","type":"exact","repeat":1},{"value":"emoTopicCombinWord","type":"exact","repeat":1},{"value":"emoBusiness","type":"exact","repeat":1},{"value":"emoCountry","type":"exact","repeat":1},{"value":"emoProvince","type":"exact","repeat":1},{"value":"emoCity","type":"exact","repeat":1},{"value":"emoDistrict","type":"exact","repeat":1},{"value":"userid","type":"exact","repeat":1},{"value":"content_type","type":"exact","repeat":1},{"value":"host_domain","type":"exact","repeat":1},{"value":"total_reposts_count","type":"exact","repeat":1},{"value":"direct_reposts_count","type":"exact","repeat":1},{"value":"followers_count","type":"exact","repeat":1},{"value":"repost_trend_cursor","type":"exact","repeat":1},{"value":"emoAccount","type":"exact","repeat":1},{"value":"emoNRN","type":"exact","repeat":1},{"value":"NRN","type":"exact","repeat":1},{"value":"province","type":"exact","repeat":1},{"value":"combinWord","type":"exact","repeat":1},{"value":"total_reach_count","type":"exact","repeat":1},{"value":"ancestor_text","type":"exact","repeat":1},{"value":"ancestor_NRN","type":"exact","repeat":1},{"value":"ancestor_wb_topic","type":"exact","repeat":1},{"value":"ancestor_country","type":"exact","repeat":1},{"value":"ancestor_district","type":"exact","repeat":1},{"value":"ancestor_emoNRN","type":"exact","repeat":1},{"value":"ancestor_emoTopicKeyword","type":"exact","repeat":1},{"value":"ancestor_emoBusiness","type":"exact","repeat":1},{"value":"ancestor_emoCity","type":"exact","repeat":1},{"value":"ancestor_host_domain","type":"exact","repeat":1},{"value":"ancestor_similar","type":"exact","repeat":1},{"value":"ancestor_emoDistrict","type":"exact","repeat":1},{"value":"ancestor_emoCountry","type":"exact","repeat":1},{"value":"ancestor_emoTopicCombinWord","type":"exact","repeat":1},{"value":"ancestor_emoOrganization","type":"exact","repeat":1},{"value":"ancestor_emotion","type":"exact","repeat":1},{"value":"ancestor_province","type":"exact","repeat":1},{"value":"ancestor_combinWord","type":"exact","repeat":1},{"value":"ancestor_wb_topic_keyword","type":"exact","repeat":1},{"value":"ancestor_organization","type":"exact","repeat":1},{"value":"ancestor_account","type":"exact","repeat":1},{"value":"ancestor_wb_topic_combinWord","type":"exact","repeat":1},{"value":"ancestor_business","type":"exact","repeat":1},{"value":"ancestor_city","type":"exact","repeat":1},{"value":"ancestor_emoCombin","type":"exact","repeat":1},{"value":"ancestor_emoTopic","type":"exact","repeat":1},{"value":"ancestor_emoAccount","type":"exact","repeat":1},{"value":"ancestor_emoProvince","type":"exact","repeat":1},{"value":"ancestor_url","type":"exact","repeat":1},{"value":"created_year","type":"exact","repeat":1},{"value":"created_hour","type":"exact","repeat":1},{"value":"created_day","type":"exact","repeat":1},{"value":"created_month","type":"exact","repeat":1},{"value":"created_weekday","type":"exact","repeat":1}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"filterlimit":{"label":"输出过滤","datatype":"string","limit":[],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"field":[{"name":"account","includeconfig":{"showother":0,"alias":""},"filter":[],"facettype":101,"allcount":false,"featureconfig":{"showother":0,"alias":""},"isfeature":0,"feature":[]}],"range":[]},"select":{"label":"查询字段","datatype":"string","limit":[],"isshow":false,"isdock":false,"allowcontrol":0,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"value":["bmiddle_pic","comments_count","content_type","created_at","description","followers_count","friends_count","guid","id","location","mid","profile_image_url","reposts_count","retweeted_mid","retweeted_status","screen_name","sex","source","sourceid","statuses_count","text","thumbnail_pic","userid","verified_reason","verified_type","verify"]},"output":{"label":"输出条件","datatype":"string","limit":[{"value":"comments_count","repeat":1,"type":"exact"},{"value":"reposts_count","repeat":1,"type":"exact"}],"isshow":true,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"outputtype":"2","countlimit":{"label":"数据量限制","datatype":"range","limit":[{"value":{"maxvalue":100,"minvalue":0},"type":"range","repeat":1}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false},"data_limit":0,"count":10,"ordertype":"desc","pageable":false},"contrast":null,"classifyquery":null,"filtervalue":[{"fieldname":"nearlytime","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"time_dynamic_state","value":{"start":"1","startgap":"month","datestate":"now","timestate":"now"}}},{"fieldname":"account","fromlimit":0,"isfeature":0,"exclude":0,"fieldvalue":{"datatype":"dynamic","value":{"start":null,"end":null},"outelementid":-3,"outputfield":"userid"}}],"download_DataLimit":1000,"download_DataLimit_limitcontrol":-1,"download_FieldLimit_limitcontrol":-1,"allowDownload":true,"download_FieldLimit":[{"text":"序号","value":"number"},{"text":"统计结果","value":"facet"},{"text":"文章数","value":"frq"},{"text":"转发数","value":"reposts_count"},{"text":"评论数","value":"comments_count"},{"text":"讨论数","value":"discuss_count"},{"text":"直接转发数","value":"direct_reposts_count"},{"text":"总转发数","value":"total_reposts_count"},{"text":"直接到达数","value":"followers_count"},{"text":"总到达数","value":"total_reach_count"}],"distinct":{"label":"结果唯一","datatype":"string","limit":[{"value":"screen_name","repeat":1,"type":"exact"}],"isshow":false,"isdock":false,"allowcontrol":-1,"unitprice":0,"maxprice":0,"onceeditprice":0,"maxeditprice":0,"maxlimitlength":-1,"limitcontrol":-1,"required":false,"distinctfield":""}}}},"instancetype":2,"modelname":"人名","referencedata":false,"secondaryyaxis":false,"showid":"smallmulticolumn3d","referencedataratio":""}]'; 
$res = thirdPartyGetData($jsoninfo, $url); 
//var_dump("res ", json_decode($res, true));
var_dump("res ", $res);
