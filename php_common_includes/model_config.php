<?php
include_once('common.php');
//定义JSON filter中的数据类型
define('JSON_DATATYPE_INT', "int");
define('JSON_DATATYPE_STRING', "string");
define('JSON_DATATYPE_RANGE', "range");
define('JSON_DATATYPE_GAPRANGE', "gaprange");
define('JSON_DATATYPE_TIMEDYNAMIC_RANGE', "time_dynamic_range");
define('JSON_DATATYPE_TIMEDYNAMIC_STATE', "time_dynamic_state"); //带有state 状态
define('JSON_DATATYPE_VALUE_TEXT_OBJECT', "value_text_object");
define('JSON_DATATYPE_BLUR_VALUE_OBJECT', "blur_value_object");
//定义JSON filter中的范围类型
define('JSON_LIMITTYPE_EXACT', 'exact');
define('JSON_LIMITTYPE_INEXACT', 'inexact');
define('JSON_LIMITTYPE_RANGE', 'range');

define('OUTPUTTYPE_QUERY',1);//只输出query
define('OUTPUTTYPE_FACET',2);//只输出facet
/**
 * 
 * 模型的配置类（datajson）
 * @author Todd
 *
 */
class ModelJson{
    public $version;//版本
    public $modelid;//ID
    public $isdefaultrelation;//是否默认字段关系
    public $filterrelation;//条件之间的关系对象
    public $filter;//查询条件
    public $facet;//
    public $select;
    public $output;
    public $contrast;//对比
    public $classifyquery;
    function __construct(){
        $this->isdefaultrelation = true;
        $this->contrast = null;
        $this->classifyquery = null;//array("type"=>1,"fieldname"=>"");
    }
}
class Model {
	public $modelid;//模型ID(资源ID)
	public $modelname;//模型名称
	public $datajson;//ModelJson实例

	function __construct($modelid,$modelname){
		$this->modelid = $modelid;
		$this->modelname = $modelname;
		$this->datajson = new ModelJson();
	}
}

//导航
class Nav{
	public $id;//导航ID
	public $level;//导航级别,1级,2级
	public $name;	//导航名称
	public $pagetitle;//页面名称
	public $isdefault;//是否默认
	public $modelType;//导航对应的页面类型:2:多模型页面,1:单模型页面
	public $instances;//导航对应的实例数组.数据平台保存modelid,租户平台保存实例ID
	public $modelid;//导航对应的实例数组.数据平台保存modelid,租户平台保存实例ID
	public $childNav;//子导航
	function __construct($id,$level,$name, $modelType = 1,$isdefault = false, $instances=array(),$modelid=0,$filepath=""){
		$this->id = $id;
		$this->level = $level;
		$this->name = $name;
		$this->modelid= $modelid;
		$this->modelType = $modelType;
		$this->isdefault = $isdefault;
		$this->instances = $instances;
		$this->filepath = $filepath;
		$this->childNav = array();
	}

	function addChild($child){
		$this->childNav[] = $child;		
	}
}
//定义频道ID
define('USERCHANNEL',1);
define('TOPICCHANNEL',2);
define('WEIBOCHANNEL',3);
define('LINKCHANNEL',4);
define('USERSTATISTICSCHANNEL',5);
define('VIRTUALDATACHANNEL',6);

//模型数组
$arrmodel = array();
$userchannel = array(); //用户频道的模型列表
$userstatisticschannel= array(); //用户频道的模型列表
$virtualdatachannel= array(); //虚拟数据源频道的模型列表
$topicchannel = array();//热点频道的.....
$weibochannel = array();//微博频道的.....
$linkchannel = array(); //联动模型频道的
$modeljs = array();//js文件中的model对象
$allshow = array();//所有能使用show，二维数组[modelid][showes]

initModelData();

function getModelJS(){
	global $modeljs;
	echo json_encode($modeljs);
}

//根据频道获取模型列表,模型下拉列表
function getModelsByChannel($channelid){
	global $userchannel, $topicchannel, $weibochannel, $linkchannel, $userstatisticschannel, $virtualdatachannel;
	switch($channelid){
		case USERCHANNEL:
			return $userchannel;
			break;
		case TOPICCHANNEL:
			return $topicchannel;
			break;
		case WEIBOCHANNEL:
			return $weibochannel;
			break;
		case LINKCHANNEL:
			return $linkchannel;
			break;
		case USERSTATISTICSCHANNEL:
			return $userstatisticschannel;
			break;
		case VIRTUALDATACHANNEL:
			return $virtualdatachannel;
			break;
		default:
			return null;
	}
}

//根据model生成Nav导航
function getNavByModelByID($level,$id,$isdefault=false){
	global $arrmodel;
	$r = null;
	foreach($arrmodel as $v){
		if($v->modelid == $id){
			$r = new Nav(0,$level,$v->modelname,1,$isdefault);
			$r->instances[] = $id;
			$r->modelid = $id;
			break;
		}
	}
	return $r;
}
//根据modellid返回model
function getModelByID($modelid){
	global $arrmodel;
	$m = null;
	foreach($arrmodel as $v){
		if($v->modelid == $modelid){
			$m = $v;
			break;
		}
	}
	return $m;
}
//生成数据平台(typeid=1)的导航链接
function getDataPlatformNav($channelid){
	$dataPlatformChannels = array();
	switch($channelid){
		case USERCHANNEL:
			$nav = new Nav(0,1,'快捷搜索',null,true);
			$nav->addChild(getNavByModelByID(2,1,true));
			$dataPlatformChannels[] = $nav;   

			$nav = new Nav(0,1,'活动趋势',null,false, array(11));
			$dataPlatformChannels[] = $nav;   
			break;
		case USERSTATISTICSCHANNEL:
			$nav = new Nav(0,1,'快捷搜索',null,true);
			$nav->addChild(getNavByModelByID(2,5,true));
			$dataPlatformChannels[] = $nav;   
			break;
		case VIRTUALDATACHANNEL:
			$nav = new Nav(0,1,'快捷搜索',null,true);
			$nav->addChild(getNavByModelByID(2,6,true));
			$dataPlatformChannels[] = $nav;   
			break;
		case TOPICCHANNEL:
			$nav = new Nav(0,1,'快捷搜索',null,true);
			$nav->addChild(getNavByModelByID(2,31,true));
			$dataPlatformChannels[] = $nav;   

			$nav = new Nav(0,1,'文章统计',null);
			$dataPlatformChannels[] = $nav;   
			break;
		case WEIBOCHANNEL:
			$nav = new Nav(0,1,'快捷搜索',null,true);
			$nav->addChild(getNavByModelByID(2,51,true));
			$dataPlatformChannels[] = $nav;   

			$nav = new Nav(0,1,'文章分析',null);
			$dataPlatformChannels[] = $nav;   
			break;
		default:
			$dataPlatformChannels;
			break;

	}
	return $dataPlatformChannels;
}

/**
 * 
 * 创建JSON 中的filter
 * @param  $label           字段显示名称
 * @param  $datatype        字段类型
 * @param  $isshow          是否显示 onsitefilter
 * @param  $isdock          是否停靠 onsitefilter
 * @param  $limitlength     limit的个数
 */
function createJsonFilter($label,$datatype,$isshow,$isdock,$limitlength){
    $r = array();
//    switch ($datatype){
//        case JSON_DATATYPE_VALUE_TEXT_OBJECT:
//            $r['limit'] = array("value"=>array(),"text"=>array());
//            break;
//        case JSON_DATATYPE_RANGE:
//            switch ($integral){
//                case JSON_INTEGRAL_LIST:
//                   $r['limit'] = array(); 
//                   break;
//                case JSON_INTEGRAL_RANGE:
//                    $r['maxvalue'] = null;
//                    $r['minvalue'] = null;
//                    break;
//                case JSON_INTEGRAL_SINGLE:
//                    $r['limit'] = null;
//                    break;
//                default:
//                    $r['limit'] = null;
//                    break;
//            }
//            break;
//        case JSON_DATATYPE_TIMEDYNAMIC:
//            break;
//        default:
//            switch ($integral){
//                case JSON_INTEGRAL_LIST:
//                   $r['limit'] = array(); 
//                   break;
//                case JSON_INTEGRAL_RANGE:
//                    $r['maxvalue'] = null;
//                    $r['minvalue'] = null;
//                    break;
//                case JSON_INTEGRAL_SINGLE:
//                    $r['limit'] = null;
//                    break;
//                default:
//                    $r['limit'] = null;
//                    break;
//            }
//            break;
//    }
    $r['label'] = $label;
    $r['datatype'] = $datatype;
    $r['limit'] = array();
    $r['isshow'] = $isshow;
    $r['isdock'] = $isdock;
    $r['allowcontrol'] = -1;//允许修改的次数，-1不限制，0不允许
    $r['unitprice'] = 0;
    $r['maxprice'] = 0;
    $r['onceeditprice'] = 0;
    $r['maxeditprice'] = 0;
    $r['maxlimitlength'] = $limitlength;//limit中值的个数.-1不限制；非零
    $r['limitcontrol'] = -1;//修改limit的次数 。-1不限制 0不允许修改  大于0表示修改次数
    $r['required'] = false;
    return $r;
}
/**
 * 
 * 生成limit对象
 * @param unknown_type $value
 * @param unknown_type $repeat
 * @param unknown_type $type
 */
function createLimitItem($value,$repeat,$type){
    return array("value"=>$value,"repeat"=>$repeat,"type"=>$type);
}

//初始化模型静态数据
function initModelData(){ 
	global $arrmodel,$userchannel,$userstatisticschannel,$topicchannel,$weibochannel,$linkchannel,$virtualdatachannel,$modeljs,$allshow;
    //用户频道模型
	$m = new Model(1,'用户分析');
	$m->datajson->version = VERSION;
	$m->datajson->modelid = $m->modelid;
	$m->datajson->filter['username'] = createJsonFilter('作者昵称',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['usersfollower'] = createJsonFilter('查粉丝',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['usersfriend'] = createJsonFilter('查关注',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['userid'] = createJsonFilter('用户名',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['followerrank'] = createJsonFilter('粉丝数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['friendrank'] = createJsonFilter('关注数', JSON_DATATYPE_RANGE,false, false, -1);
    $m->datajson->filter['statusesrank'] = createJsonFilter('文章数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_replys_count'] = createJsonFilter('回复数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_recommended_count'] = createJsonFilter('精华帖数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_favourites_count'] = createJsonFilter('收藏数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_bi_followers_count'] = createJsonFilter('互粉数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['registertime'] = createJsonFilter('博龄', JSON_DATATYPE_GAPRANGE,false, false, -1);
	$m->datajson->filter['registertime']["limit"] = array(array("repeat" => 1, "type"=>"gaprange", "value"=>array("maxvalue"=>null, "minvalue"=>null, "gap"=>null)));
    $m->datajson->filter['source'] = createJsonFilter('数据来源',JSON_DATATYPE_INT, true, false, -1);
    $m->datajson->filter['users_source_host'] = createJsonFilter('数据来源',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_page_url'] = createJsonFilter('页面地址',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['sex'] = createJsonFilter('作者性别', JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_level'] = createJsonFilter('用户级别',JSON_DATATYPE_RANGE, true, false,-1);
    $m->datajson->filter['verified'] = createJsonFilter('认证',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['verified_type'] = createJsonFilter('认证类型',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['verifiedreason'] = createJsonFilter('认证原因',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['description'] = createJsonFilter('简介',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['area'] = createJsonFilter('地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, true, false, -1);
    $m->datajson->filter['users_url'] = createJsonFilter('博客地址',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_domain'] = createJsonFilter('个性化域名',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_allow_all_act_msg'] = createJsonFilter('允许私信', JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_allow_all_comment'] = createJsonFilter('允许评论', JSON_DATATYPE_STRING, true, false, -1);

    $m->datajson->filtervalue = array();
    $m->datajson->filterrelation = null;
    //$m->datajson->facet = null;//模型不需要facet
    $m->datajson->facet = createJsonFilter('分组统计',JSON_DATATYPE_STRING,false, false,-1);
    $m->datajson->facet['filterlimit'] = createJsonFilter('输出过滤',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->facet['field'] = array();
    $m->datajson->facet['range'] = array();
    /*$m->datajson->facet['field'][] = array("name"=>"text","facettype"=>101,
        "filter"=>array("type"=>"include","value"=>array("张三*", "李*")));
    $m->datajson->facet['range'] = array(); 
    $m->datajson->facet['range'][] = array("name"=>"created_at","gap"=>1000,"start"=>100,"end"=>9000);
    */
    $m->datajson->select = createJsonFilter('查询字段',JSON_DATATYPE_STRING, false, false, -1);
    
    //$allsel = array("id","screen_name","location","description","profile_image_url", "followers_count", "friends_count", "statuses_count","verified", "verified_reason","verified_type","gender", "sourceid");
    $allsel = array("users_id","users_screen_name","users_location","users_description","users_profile_image_url","users_followers_count","users_friends_count","users_statuses_count", "users_replys_count","users_recommended_count","users_favourites_count","users_bi_followers_count","users_level","users_verified","users_verified_reason","users_verified_type","users_gender","users_sourceid","users_source_host", "users_page_url");
    $m->datajson->allowupdatesnapshot = true;//是否允许此模型定时快照更新
    $m->datajson->alloweventalert = true;//是否允许此模型事件预警
    $m->datajson->download_DataLimit = 1000;//最多下载多少
    $m->datajson->download_DataLimit_limitcontrol = -1;//不限制修改次数
    $m->datajson->download_FieldLimit_limitcontrol = -1;
    $m->datajson->allowDownload = true;//是否允许下载此模型
    $m->datajson->select['value'] = $allsel;
    $downfields = array();
    $downfields[] = array("text"=>"序号", "value"=>"number");
    $downfields[] = array("text"=>"数据来源", "value"=>"users_source_host");
    $downfields[] = array("text"=>"用户昵称", "value"=>"users_screen_name");
    $downfields[] = array("text"=>"用户地址", "value"=>"userurl");
    $downfields[] = array("text"=>"页面地址", "value"=>"users_page_url");
    $downfields[] = array("text"=>"所在地", "value"=>"users_location");
    $downfields[] = array("text"=>"简介", "value"=>"users_description");
    $downfields[] = array("text"=>"博客", "value"=>"users_url");
    $downfields[] = array("text"=>"域名", "value"=>"users_domain");
    $downfields[] = array("text"=>"性别", "value"=>"users_gender");
    $downfields[] = array("text"=>"粉丝数", "value"=>"users_followers_count");
    $downfields[] = array("text"=>"关注数", "value"=>"users_friends_count");
    $downfields[] = array("text"=>"文章数", "value"=>"users_statuses_count");
    $downfields[] = array("text"=>"回复数", "value"=>"users_replys_count");
    $downfields[] = array("text"=>"精华帖数", "value"=>"users_recommended_count");
    $downfields[] = array("text"=>"收藏数", "value"=>"users_favourites_count");
    $downfields[] = array("text"=>"互粉数", "value"=>"users_bi_followers_count");
    $downfields[] = array("text"=>"认证用户", "value"=>"users_verified");
    $downfields[] = array("text"=>"认证类型", "value"=>"users_verified_type");
    $downfields[] = array("text"=>"认证原因", "value"=>"users_verified_reason");
    $downfields[] = array("text"=>"用户级别", "value"=>"users_level");
    $m->datajson->download_FieldLimit = $downfields;//允许下载哪些字段
    
    /*foreach($allsel as $k=>$v){
        $m->datajson->select['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT); 
    }*/
    $alloutputlimit = array("users_followers_count","users_friends_count","users_statuses_count","users_replys_count","users_recommended_count", "users_favourites_count", "users_bi_followers_count");
    $m->datajson->output = createJsonFilter('输出条件',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->output['outputtype'] = OUTPUTTYPE_QUERY;//1只查询，2只facet
    foreach($alloutputlimit as $k=>$v){
        $m->datajson->output['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);  
    }
    $m->datajson->output['countlimit'] = createJsonFilter('数据量限制',JSON_DATATYPE_RANGE, false, false,-1);
    $m->datajson->output['data_limit'] = 0;
    $m->datajson->output['count'] = 10;
    $m->datajson->output['orderby'] = "users_followers_count";
    $m->datajson->output['ordertype'] = "desc";
    $m->datajson->output['pageable'] = true;//是否可分页,选中列表视图时，控制是否显示分页
	$modeljs["foltop"] = $m->modelid;
	$arrmodel[] = $m;
	$userchannel[] = $m;

	/*
    $allshow[$m->modelid][] = array("name"=>"动态过滤图表(2/3屏)","id"=>"filterchart");
	 */
    $allshow[$m->modelid][] = array("name"=>"列表(全屏)","id"=>"fullscreenlist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(2/3屏)","id"=>"singlelist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(1/3屏)","id"=>"smalllist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"柱状图(全屏)","id"=>"fullscreenmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(2/3屏)","id"=>"bigmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(1/3屏)","id"=>"smallmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(全屏)","id"=>"fullscreenmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(2/3屏)","id"=>"bigmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(1/3屏)","id"=>"smallmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"饼状图(全屏)","id"=>"fullscreenpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(2/3屏)","id"=>"bigpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(1/3屏)","id"=>"smallpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"环形图(全屏)","id"=>"fullscreencircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(2/3屏)","id"=>"bigcircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(1/3屏)","id"=>"smallcircularpiechart", "groupid"=>"circularpie");
    /*
    //雷达图
    $allshow[$m->modelid][] = array("name"=>"雷达图(全屏)","id"=>"fullscreenradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(2/3屏)","id"=>"bigradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(1/3屏)","id"=>"smallradarchart", "groupid"=>"radar");
    //仪表盘
    $allshow[$m->modelid][] = array("name"=>"仪表盘(全屏)","id"=>"fullscreengaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(2/3屏)","id"=>"biggaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(1/3屏)","id"=>"smallgaugechart", "groupid"=>"gauge");
     */
    //气泡图
    $allshow[$m->modelid][] = array("name"=>"气泡图(全屏)","id"=>"fullscreenscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(2/3屏)","id"=>"bigscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(1/3屏)","id"=>"smallscatterchart", "groupid"=>"scatter");

    $allshow[$m->modelid][] = array("name"=>"用户信息(2/3屏)","id"=>"userinfo", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"粉丝影响力(2/3屏)","id"=>"userfollowers", "groupid"=>"influence");

	//用户频道模型
	$m = new Model(2,'用户统计');
	$m->datajson->version = VERSION;
	$m->datajson->modelid = $m->modelid;
	$m->datajson->filter['username'] = createJsonFilter('作者昵称',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['usersfollower'] = createJsonFilter('查粉丝',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['usersfriend'] = createJsonFilter('查关注',JSON_DATATYPE_STRING, false, false, -1);
	$m->datajson->filter['userid'] = createJsonFilter('用户名',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['followerrank'] = createJsonFilter('粉丝数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['friendrank'] = createJsonFilter('关注数', JSON_DATATYPE_RANGE,false, false, -1);
    $m->datajson->filter['statusesrank'] = createJsonFilter('微博数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_favourites_count'] = createJsonFilter('收藏数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_replys_count'] = createJsonFilter('回复数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_recommended_count'] = createJsonFilter('精华帖数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['users_bi_followers_count'] = createJsonFilter('互粉数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['registertime'] = createJsonFilter('博龄', JSON_DATATYPE_GAPRANGE,false, false, -1);
	$m->datajson->filter['registertime']["limit"] = array(array("repeat" => 1, "type"=>"gaprange", "value"=>array("maxvalue"=>null, "minvalue"=>null, "gap"=>null)));
    $m->datajson->filter['sourceid'] = createJsonFilter('数据来源',JSON_DATATYPE_INT, true, false, -1);
    $m->datajson->filter['users_source_host'] = createJsonFilter('数据来源',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_page_url'] = createJsonFilter('页面地址',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['sex'] = createJsonFilter('作者性别', JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_level'] = createJsonFilter('用户级别',JSON_DATATYPE_RANGE, true, false,-1);
    $m->datajson->filter['verified'] = createJsonFilter('认证',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['verified_type'] = createJsonFilter('认证类型',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['verifiedreason'] = createJsonFilter('认证原因',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['description'] = createJsonFilter('简介',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['areauser'] = createJsonFilter('用户地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, true, false, -1);
    $m->datajson->filter['users_url'] = createJsonFilter('博客地址',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_domain'] = createJsonFilter('个性域名',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_allow_all_act_msg'] = createJsonFilter('允许私信', JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['users_allow_all_comment'] = createJsonFilter('允许评论', JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filtervalue = array();
    $m->datajson->filterrelation = null;
    //$m->datajson->facet = null;//模型不需要facet
    $m->datajson->facet = createJsonFilter('分组统计',JSON_DATATYPE_STRING,false, false,-1);
	$allfacetlimit = array("users_screen_name", "users_followers_count", "users_friends_count", "users_statuses_count","users_replys_count","users_recommended_count", "users_created_at","users_source_host","users_gender","users_level", "users_verified", "users_verified_type", "users_verified_reason", "users_country_code", "users_province_code", "users_city_code", "users_district_code", "users_description", "users_friends_id", "users_favourites_count", "users_bi_followers_count", "users_allow_all_act_msg", "users_allow_all_comment");
	foreach($allfacetlimit as $key=>$value){
		$m->datajson->facet["limit"][] = createLimitItem($value, 1, JSON_LIMITTYPE_EXACT);
	}
    $m->datajson->facet['filterlimit'] = createJsonFilter('输出过滤',JSON_DATATYPE_STRING, false, false,-1);
	/*
    $m->datajson->facet['field'] = array();
    $m->datajson->facet['range'] = array();
	 */
    $m->datajson->select = createJsonFilter('查询字段',JSON_DATATYPE_STRING, false, false, -1);
    $allsel = array("users_id","users_screen_name","users_location","users_description","users_profile_image_url","users_followers_count","users_friends_count","users_statuses_count","users_favourites_count","users_bi_followers_count","users_level","users_verified","users_verified_reason","users_verified_type","users_gender","users_sourceid", "users_source_host", "users_page_url");
    $m->datajson->select['value'] = $allsel;

	$m->datajson->allowupdatesnapshot = true;//是否允许此模型定时快照更新
    $m->datajson->alloweventalert = true;//是否允许此模型事件预警
    $m->datajson->download_DataLimit = 1000;//最多下载多少
    $m->datajson->download_DataLimit_limitcontrol = -1;//不限制修改次数
    $m->datajson->download_FieldLimit_limitcontrol = -1;
    $m->datajson->allowDownload = true;//是否允许下载此模型
    $downfields = array();
    $downfields[] = array("text"=>"序号", "value"=>"number");
    $downfields[] = array("text"=>"统计结果", "value"=>"facet");
    $downfields[] = array("text"=>"用户数", "value"=>"frq");
    $downfields[] = array("text"=>"粉丝数", "value"=>"users_followers_count");
    $downfields[] = array("text"=>"关注数", "value"=>"users_friends_count");
    $downfields[] = array("text"=>"微博数", "value"=>"users_statuses_count");
    $downfields[] = array("text"=>"收藏数", "value"=>"users_favourites_count");
    $downfields[] = array("text"=>"互粉数", "value"=>"users_bi_followers_count");
    $m->datajson->download_FieldLimit = $downfields;//允许下载哪些字段
    
    $alloutputlimit = array("users_followers_count","users_friends_count","users_statuses_count", "users_favourites_count", "users_bi_followers_count");
    $m->datajson->output = createJsonFilter('输出条件',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->output['outputtype'] = OUTPUTTYPE_FACET;//1只查询，2只facet
    foreach($alloutputlimit as $k=>$v){
        $m->datajson->output['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);  
    }
    $m->datajson->output['countlimit'] = createJsonFilter('数据量限制',JSON_DATATYPE_RANGE, false, false,-1);
    $m->datajson->output['data_limit'] = 0;
    $m->datajson->output['count'] = 10;
    $m->datajson->output['orderby'] = "users_followers_count";
    $m->datajson->output['ordertype'] = "desc";
    $m->datajson->output['pageable'] = true;//是否可分页,选中列表视图时，控制是否显示分页
	$modeljs["userstatistics"] = $m->modelid;
	$arrmodel[] = $m;
	$userstatisticschannel[] = $m;

	/*
    $allshow[$m->modelid][] = array("name"=>"动态过滤图表(2/3屏)","id"=>"filterchart");
	 */
    $allshow[$m->modelid][] = array("name"=>"列表(全屏)","id"=>"fullscreenlist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(2/3屏)","id"=>"singlelist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(1/3屏)","id"=>"smalllist", "groupid"=>"list");
	//标签墙
    $allshow[$m->modelid][] = array("name"=>"标签墙(2/3屏)","id"=>"bigcloudchart", "groupid"=>"cloud");
    $allshow[$m->modelid][] = array("name"=>"标签墙(1/3屏)","id"=>"smallcloudchart", "groupid"=>"cloud");
	//地图
    $allshow[$m->modelid][] = array("name"=>"地图(2/3屏)","id"=>"mapchart", "groupid"=>"map");
    //热力图
    $allshow[$m->modelid][] = array("name"=>"热力图(2/3屏)","id"=>"heatmapchart", "groupid"=>"heatmap");
	//线型图
    $allshow[$m->modelid][] = array("name"=>"线型图(全屏)","id"=>"fullscreenmslinechart", "groupid"=>"msline");
    $allshow[$m->modelid][] = array("name"=>"线型图(2/3屏)","id"=>"bigmslinechart", "groupid"=>"msline");
    $allshow[$m->modelid][] = array("name"=>"线型图(1/3屏)","id"=>"smallmslinechart", "groupid"=>"msline");
	//柱状图
    $allshow[$m->modelid][] = array("name"=>"柱状图(全屏)","id"=>"fullscreenmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(2/3屏)","id"=>"bigmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(1/3屏)","id"=>"smallmulticolumn3d", "groupid"=>"barchart");
	//横向柱状图
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(全屏)","id"=>"fullscreenmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(2/3屏)","id"=>"bigmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(1/3屏)","id"=>"smallmultibar3d", "groupid"=>"barchart");
	//饼图
    $allshow[$m->modelid][] = array("name"=>"饼状图(全屏)","id"=>"fullscreenpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(2/3屏)","id"=>"bigpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(1/3屏)","id"=>"smallpiechart", "groupid"=>"pie");
    //环形图
    $allshow[$m->modelid][] = array("name"=>"环形图(全屏)","id"=>"fullscreencircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(2/3屏)","id"=>"bigcircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(1/3屏)","id"=>"smallcircularpiechart", "groupid"=>"circularpie");
    //雷达图
    $allshow[$m->modelid][] = array("name"=>"雷达图(全屏)","id"=>"fullscreenradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(2/3屏)","id"=>"bigradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(1/3屏)","id"=>"smallradarchart", "groupid"=>"radar");
    //和弦图
    $allshow[$m->modelid][] = array("name"=>"和弦图(全屏)","id"=>"fullscreenchordchart", "groupid"=>"chord");
    $allshow[$m->modelid][] = array("name"=>"和弦图(2/3屏)","id"=>"bigchordchart", "groupid"=>"chord");
    $allshow[$m->modelid][] = array("name"=>"和弦图(1/3屏)","id"=>"smallchordchart", "groupid"=>"chord");
    //箱线图
    $allshow[$m->modelid][] = array("name"=>"箱线图(全屏)","id"=>"fullscreenboxplotchart", "groupid"=>"boxplot");
    $allshow[$m->modelid][] = array("name"=>"箱线图(2/3屏)","id"=>"bigboxplotchart", "groupid"=>"boxplot");
    $allshow[$m->modelid][] = array("name"=>"箱线图(1/3屏)","id"=>"smallboxplotchart", "groupid"=>"boxplot");
    //漏斗图
    $allshow[$m->modelid][] = array("name"=>"漏斗图(全屏)","id"=>"fullscreenfunnelchart", "groupid"=>"funnel");
    $allshow[$m->modelid][] = array("name"=>"漏斗图(2/3屏)","id"=>"bigfunnelchart", "groupid"=>"funnel");
    $allshow[$m->modelid][] = array("name"=>"漏斗图(1/3屏)","id"=>"smallfunnelchart", "groupid"=>"funnel");
    //仪表盘
    $allshow[$m->modelid][] = array("name"=>"仪表盘(全屏)","id"=>"fullscreengaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(2/3屏)","id"=>"biggaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(1/3屏)","id"=>"smallgaugechart", "groupid"=>"gauge");
    //气泡图
    $allshow[$m->modelid][] = array("name"=>"气泡图(全屏)","id"=>"fullscreenscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(2/3屏)","id"=>"bigscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(1/3屏)","id"=>"smallscatterchart", "groupid"=>"scatter");



    //虚拟数据源
	$m = new Model(6,'虚拟数据源');
	$m->datajson->version = VERSION;
	$m->datajson->modelid = $m->modelid;
	$m->datajson->filter = null;

    $m->datajson->filtervalue = array();
    $m->datajson->filterrelation = null;

    $m->datajson->facet = createJsonFilter('分组统计',JSON_DATATYPE_STRING,false, false,-1);
    $m->datajson->facet['filterlimit'] = createJsonFilter('输出过滤',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->facet['field'] = array();
    $m->datajson->facet['range'] = array();

    $m->datajson->select = createJsonFilter('查询字段',JSON_DATATYPE_STRING, false, false, -1);
    $allsel = array();
    $m->datajson->select['value'] = $allsel;

    $m->datajson->output = createJsonFilter('输出条件',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->output['outputtype'] = OUTPUTTYPE_QUERY;//1只查询，2只facet
    $alloutputlimit = array();
    foreach($alloutputlimit as $k=>$v){
        $m->datajson->output['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);  
    }
    $m->datajson->output['countlimit'] = createJsonFilter('数据量限制',JSON_DATATYPE_RANGE, false, false,-1);
    $m->datajson->output['data_limit'] = 0;
    $m->datajson->output['count'] = 10;
    $m->datajson->output['orderby'] = "";
    $m->datajson->output['ordertype'] = "desc";
    $m->datajson->output['pageable'] = true;//是否可分页,选中列表视图时，控制是否显示分页
	$modeljs["virtualdatasource"] = $m->modelid; //Virtual data source
	$arrmodel[] = $m;
	$virtualdatachannel[] = $m;

    $allshow[$m->modelid][] = array("name"=>"列表(全屏)","id"=>"fullscreenlist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(2/3屏)","id"=>"singlelist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(1/3屏)","id"=>"smalllist", "groupid"=>"list");
	//标签墙
    $allshow[$m->modelid][] = array("name"=>"标签墙(2/3屏)","id"=>"bigcloudchart", "groupid"=>"cloud");
    $allshow[$m->modelid][] = array("name"=>"标签墙(1/3屏)","id"=>"smallcloudchart", "groupid"=>"cloud");
	//线型图
    $allshow[$m->modelid][] = array("name"=>"线型图(全屏)","id"=>"fullscreenmslinechart", "groupid"=>"msline");
    $allshow[$m->modelid][] = array("name"=>"线型图(2/3屏)","id"=>"bigmslinechart", "groupid"=>"msline");
    $allshow[$m->modelid][] = array("name"=>"线型图(1/3屏)","id"=>"smallmslinechart", "groupid"=>"msline");

    $allshow[$m->modelid][] = array("name"=>"柱状图(全屏)","id"=>"fullscreenmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(2/3屏)","id"=>"bigmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(1/3屏)","id"=>"smallmulticolumn3d", "groupid"=>"barchart");

    $allshow[$m->modelid][] = array("name"=>"横向柱状图(全屏)","id"=>"fullscreenmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(2/3屏)","id"=>"bigmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(1/3屏)","id"=>"smallmultibar3d", "groupid"=>"barchart");
	//地图
    $allshow[$m->modelid][] = array("name"=>"地图(2/3屏)","id"=>"mapchart", "groupid"=>"map");
    //热力图
    $allshow[$m->modelid][] = array("name"=>"热力图(2/3屏)","id"=>"heatmapchart", "groupid"=>"heatmap");
    //饼图
    $allshow[$m->modelid][] = array("name"=>"饼状图(全屏)","id"=>"fullscreenpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(2/3屏)","id"=>"bigpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(1/3屏)","id"=>"smallpiechart", "groupid"=>"pie");
    //环形图
    $allshow[$m->modelid][] = array("name"=>"环形图(全屏)","id"=>"fullscreencircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(2/3屏)","id"=>"bigcircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(1/3屏)","id"=>"smallcircularpiechart", "groupid"=>"circularpie");
    //雷达图
    $allshow[$m->modelid][] = array("name"=>"雷达图(全屏)","id"=>"fullscreenradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(2/3屏)","id"=>"bigradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(1/3屏)","id"=>"smallradarchart", "groupid"=>"radar");
    //和弦图
    $allshow[$m->modelid][] = array("name"=>"和弦图(全屏)","id"=>"fullscreenchordchart", "groupid"=>"chord");
    $allshow[$m->modelid][] = array("name"=>"和弦图(2/3屏)","id"=>"bigchordchart", "groupid"=>"chord");
    $allshow[$m->modelid][] = array("name"=>"和弦图(1/3屏)","id"=>"smallchordchart", "groupid"=>"chord");
    //箱线图
    $allshow[$m->modelid][] = array("name"=>"箱线图(全屏)","id"=>"fullscreenboxplotchart", "groupid"=>"boxplot");
    $allshow[$m->modelid][] = array("name"=>"箱线图(2/3屏)","id"=>"bigboxplotchart", "groupid"=>"boxplot");
    $allshow[$m->modelid][] = array("name"=>"箱线图(1/3屏)","id"=>"smallboxplotchart", "groupid"=>"boxplot");
    //漏斗图
    $allshow[$m->modelid][] = array("name"=>"漏斗图(全屏)","id"=>"fullscreenfunnelchart", "groupid"=>"funnel");
    $allshow[$m->modelid][] = array("name"=>"漏斗图(2/3屏)","id"=>"bigfunnelchart", "groupid"=>"funnel");
    $allshow[$m->modelid][] = array("name"=>"漏斗图(1/3屏)","id"=>"smallfunnelchart", "groupid"=>"funnel");
    //仪表盘
    $allshow[$m->modelid][] = array("name"=>"仪表盘(全屏)","id"=>"fullscreengaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(2/3屏)","id"=>"biggaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(1/3屏)","id"=>"smallgaugechart", "groupid"=>"gauge");
    //气泡图
    $allshow[$m->modelid][] = array("name"=>"气泡图(全屏)","id"=>"fullscreenscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(2/3屏)","id"=>"bigscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(1/3屏)","id"=>"smallscatterchart", "groupid"=>"scatter");



	
	//话题模型
	$m = new Model(31,'文章统计');
	$m->datajson->version = VERSION;
    $m->datajson->modelid = $m->modelid;
    $m->datajson->filter['question_id'] = createJsonFilter('提问序号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['answer_id'] = createJsonFilter('回答序号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['question_father_id'] = createJsonFilter('提问父级序号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['answer_father_id'] = createJsonFilter('回答父级号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['child_post_id'] = createJsonFilter('子级序列号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['trample_count'] = createJsonFilter('踩',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['floor'] = createJsonFilter('楼号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['paragraphid'] = createJsonFilter('段落号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['read_count'] = createJsonFilter('阅读数',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['recommended'] = createJsonFilter('精华帖',JSON_DATATYPE_INT,  false, false, -1);
    $m->datajson->filter['original_url'] = createJsonFilter('页面地址',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['column'] = createJsonFilter('子级栏目',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['column1'] = createJsonFilter('父级栏目',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['post_title'] = createJsonFilter('帖子标题',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['weiboid'] = createJsonFilter('微博ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['weibourl'] = createJsonFilter('微博URL',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['oristatus'] = createJsonFilter('原创ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['oristatusurl'] = createJsonFilter('原创URL',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['oristatus_username'] = createJsonFilter('昵称查转发',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['oristatus_userid'] = createJsonFilter('用户名查转发',JSON_DATATYPE_VALUE_TEXT_OBJECT,  false, false, -1); //查转发
    $m->datajson->filter['repost_url'] = createJsonFilter('转发URL',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['repost_username'] = createJsonFilter('昵称查原创',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['repost_userid'] = createJsonFilter('用户名查原创',JSON_DATATYPE_VALUE_TEXT_OBJECT,  false, false, -1); //查转发
    $m->datajson->filter['father_guid'] = createJsonFilter('上层文章唯一标识',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['docguid'] = createJsonFilter('文章标识',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['mid'] = createJsonFilter('文章ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['retweeted_mid'] = createJsonFilter('原创文章ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['searchword'] = createJsonFilter('关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['pg_text'] = createJsonFilter('段关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['organization'] = createJsonFilter('机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['account'] = createJsonFilter('@用户',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['userid'] = createJsonFilter('用户名',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['weibotopic'] = createJsonFilter('微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['weibotopickeyword'] = createJsonFilter('微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['weibotopiccombinword'] = createJsonFilter('微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['NRN'] = createJsonFilter('人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['topic'] = createJsonFilter('短语',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['business'] = createJsonFilter('行业',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['repostsnum'] = createJsonFilter('转发数',JSON_DATATYPE_RANGE,false, false, -1);
    $m->datajson->filter['commentsnum'] = createJsonFilter('评论数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['direct_comments_count'] = createJsonFilter('直接评论数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['praises_count'] = createJsonFilter('赞',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['total_reposts_count'] = createJsonFilter('总转发数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['total_reposts_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['direct_reposts_count'] = createJsonFilter('直接转发数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['direct_reposts_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['total_reach_count'] = createJsonFilter('总到达数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['total_reach_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['followers_count'] = createJsonFilter('直接到达数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['followers_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['repost_trend_cursor'] = createJsonFilter('转发所处层级',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['repost_trend_cursor']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['areauser'] = createJsonFilter('用户地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['areamentioned'] = createJsonFilter('提及地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['createdtime'] = createJsonFilter('创建时间',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['nearlytime'] = createJsonFilter('相对今天',JSON_DATATYPE_TIMEDYNAMIC_STATE, true, false, -1);
    $m->datajson->filter['beforetime'] = createJsonFilter('时间段',JSON_DATATYPE_TIMEDYNAMIC_STATE, true, false, -1);
    $m->datajson->filter['untiltime'] = createJsonFilter('日历时间',JSON_DATATYPE_TIMEDYNAMIC_RANGE, true, false, -1);
    $m->datajson->filter['created_year'] = createJsonFilter('创建时间(年)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_month'] = createJsonFilter('创建时间(月)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_day'] = createJsonFilter('创建时间(日)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_hour'] = createJsonFilter('创建时间(时)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_weekday'] = createJsonFilter('创建时间(周)',JSON_DATATYPE_RANGE, true, false, -1);


    $m->datajson->filter['emotion'] = createJsonFilter('情感关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoCombin'] = createJsonFilter('情感短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoNRN'] = createJsonFilter('情感人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoOrganization'] = createJsonFilter('情感机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoTopic'] = createJsonFilter('情感微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoTopicKeyword'] = createJsonFilter('情感微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoTopicCombinWord'] = createJsonFilter('情感微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoAccount'] = createJsonFilter('@用户情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['emoBusiness'] = createJsonFilter('行业情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['emoAreamentioned'] = createJsonFilter('地区情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['weibotype'] = createJsonFilter('微博类型',JSON_DATATYPE_INT, true, false, -1);
    $m->datajson->filter['source'] = createJsonFilter('应用来源',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['hostdomain'] = createJsonFilter('主机域名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['sourceid'] = createJsonFilter('数据来源',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['source_host'] = createJsonFilter('数据来源',JSON_DATATYPE_STRING, true, false,-1);
    $m->datajson->filter['username'] = createJsonFilter('作者昵称',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['level'] = createJsonFilter('用户级别',JSON_DATATYPE_RANGE, true, false,-1);
    $m->datajson->filter['verified'] = createJsonFilter('认证',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['verified_type'] = createJsonFilter('认证类型',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['haspicture'] = createJsonFilter('含有图片',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['verifiedreason'] = createJsonFilter('认证原因',JSON_DATATYPE_STRING, false, false,-1);
	//$m->datajson->filter['verifiedreason']["allowcontrol"] = 1;  //新添加字段 不显示
    $m->datajson->filter['registertime'] = createJsonFilter('博龄',JSON_DATATYPE_GAPRANGE, false, false, -1);
	$m->datajson->filter['registertime']["limit"] = array(array("repeat" => 1, "type"=>"gaprange", "value"=>array("maxvalue"=>null, "minvalue"=>null, "gap"=>null)));
    $m->datajson->filter['sex'] = createJsonFilter('作者性别',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['originalcontent'] = createJsonFilter('原文内容',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['digestcontent'] = createJsonFilter('摘要内容',JSON_DATATYPE_STRING, false, false, -1);
    //$m->datajson->filter['excludeweiboid'] = createJsonFilter('exclude微博',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['description'] = createJsonFilter('简介',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_text'] = createJsonFilter('上层转发关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_organization'] = createJsonFilter('上层转发机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_account'] = createJsonFilter('上层转发@用户',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_wb_topic'] = createJsonFilter('上层转发微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_wb_topic_keyword'] = createJsonFilter('上层转发微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_wb_topic_combinWord'] = createJsonFilter('上层转发微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_NRN'] = createJsonFilter('上层转发人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_combinWord'] = createJsonFilter('上层转发短语',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['ancestor_business'] = createJsonFilter('上层转发行业',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_areamentioned'] = createJsonFilter('上层转发提及地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_emotion'] = createJsonFilter('上层转发情感关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoCombin'] = createJsonFilter('上层转发情感短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoNRN'] = createJsonFilter('上层转发情感人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoOrganization'] = createJsonFilter('上层转发情感机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoTopic'] = createJsonFilter('上层转发情感微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoTopicKeyword'] = createJsonFilter('上层转发情感微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoTopicCombinWord'] = createJsonFilter('上层转发情感微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoAccount'] = createJsonFilter('上层转发@用户情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_emoBusiness'] = createJsonFilter('上层转发行业情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_emoAreamentioned'] = createJsonFilter('上层转发地区情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_url'] = createJsonFilter('上层转发URL',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['ancestor_host_domain'] = createJsonFilter('上层转发主机域名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_similar'] = createJsonFilter('上层转发摘要内容',JSON_DATATYPE_STRING, false, false, -1);
    //为电商增加的数据字段
    $m->datajson->filter['productType'] = createJsonFilter('产品型号',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['impress'] = createJsonFilter('买家印象',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['commentTags'] = createJsonFilter('单评论的标签',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['satisfaction'] = createJsonFilter('满意度',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['godRepPer'] = createJsonFilter('好评百分比',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['midRepPer'] = createJsonFilter('中评百分比',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['wosRepPer'] = createJsonFilter('差评百分比',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['godRepNum'] = createJsonFilter('好评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['midRepNum'] = createJsonFilter('中评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['wosRepNum'] = createJsonFilter('差评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['apdRepNum'] = createJsonFilter('追评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['showPicNum'] = createJsonFilter('有晒单的评价',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['cmtStarLevel'] = createJsonFilter('评价所属星级',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['purchDate'] = createJsonFilter('购买日期',JSON_DATATYPE_INT, false, false, -1);

    $m->datajson->filter['isNewPro'] = createJsonFilter('是否为新品',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['proClassify'] = createJsonFilter('详细商品分类',JSON_DATATYPE_STRING, false, false, -1);//详细商品分类(栏目)
    $m->datajson->filter['proOriPrice'] = createJsonFilter('原价',JSON_DATATYPE_INT, false, false, -1);//原价/专柜价
    $m->datajson->filter['proCurPrice'] = createJsonFilter('现价',JSON_DATATYPE_INT, false, false, -1);//现价/京东价/天猫价
    $m->datajson->filter['proPriPrice'] = createJsonFilter('促销价',JSON_DATATYPE_INT, false, false, -1);//促销价/优惠价
    $m->datajson->filter['product_no'] = createJsonFilter('产品的编号',JSON_DATATYPE_STRING, false, false, -1);//产品的编号：2017-1-24
    $m->datajson->filter['pro_init_price'] = createJsonFilter('上市价格',JSON_DATATYPE_INT, false, false, -1);//上市价格：2017-1-24
    $m->datajson->filter['promotionInfos'] = createJsonFilter('促销信息',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productFullName'] = createJsonFilter('产品全名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productColor'] = createJsonFilter('产品颜色',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productSize'] = createJsonFilter('产品尺寸',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productDesc'] = createJsonFilter('产品描述',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productComb'] = createJsonFilter('产品组合',JSON_DATATYPE_STRING, false, false, -1);//产品组合/产品套餐
    $m->datajson->filter['detailParam'] = createJsonFilter('规格参数',JSON_DATATYPE_STRING, false, false, -1);//规格参数/详细参数
    $m->datajson->filter['stockNum'] = createJsonFilter('库存',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['salesNumMonth'] = createJsonFilter('月成交量',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['compName'] = createJsonFilter('公司名称',JSON_DATATYPE_STRING, false, false, -1);//掌柜/公司名称
    $m->datajson->filter['compAddress'] = createJsonFilter('公司地址',JSON_DATATYPE_STRING, false, false, -1);// 公司地址/卖家地址
    $m->datajson->filter['phoneNum'] = createJsonFilter('公司电话',JSON_DATATYPE_STRING, false, false, -1);//公司电话/卖家电话
    $m->datajson->filter['operateTime'] = createJsonFilter('开店时长',JSON_DATATYPE_INT, false, false, -1);//开店时长(以天为单位)
    $m->datajson->filter['compURL'] = createJsonFilter('公司URL',JSON_DATATYPE_STRING, false, false, -1);//公司URL
    $m->datajson->filter['serviceProm'] = createJsonFilter('服务承诺',JSON_DATATYPE_STRING, false, false, -1);//服务承诺
    $m->datajson->filter['logisticsInfo'] = createJsonFilter('物流',JSON_DATATYPE_STRING, false, false, -1);//物流
    $m->datajson->filter['payMethod'] = createJsonFilter('支付方式',JSON_DATATYPE_STRING, false, false, -1);//支付方式
    $m->datajson->filter['compDesMatch'] = createJsonFilter('对公司总体打分',JSON_DATATYPE_INT, false, false, -1);//对公司总体打分/与描述相符
    $m->datajson->filter['logisticsScore'] = createJsonFilter('对公司物流打分',JSON_DATATYPE_INT, false, false, -1);//对公司物流打分
    $m->datajson->filter['serviceScore'] = createJsonFilter('对公司服务打分',JSON_DATATYPE_INT, false, false, -1);//对公司服务打分
    $m->datajson->filter['serviceComment'] = createJsonFilter('对服务的评论',JSON_DATATYPE_STRING, false, false, -1);//对服务的评论 (天猫中对服务有)单独的评论选项
    $m->datajson->filter['apdComment'] = createJsonFilter('追评内容',JSON_DATATYPE_STRING, false, false, -1);//追评内容
    $m->datajson->filter['isFavorite'] = createJsonFilter('收藏',JSON_DATATYPE_INT, false, false, -1);//收藏
    $m->datajson->filter['isAttention'] = createJsonFilter('关注',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['article_taginfo'] = createJsonFilter('文章标签',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['retweeted_created_at'] = createJsonFilter('出发时间',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['nowLocation'] = createJsonFilter('出发地',JSON_DATATYPE_STRING, false, false, -1);//出发地 by zuo:2016-8-4
    $m->datajson->filter['trading_count_m'] = createJsonFilter('月成交笔数',JSON_DATATYPE_INT, false, false, -1);//新加的字段：月成交笔数by zuo:2016-11-2
    $m->datajson->filter['retweeted_guid'] = createJsonFilter('原创guid',JSON_DATATYPE_STRING, false, false, -1);//原创guid 2017-3-13

    $m->datajson->filtervalue = array();
    $m->datajson->filterrelation = null;
	$allfacetlimit = array("floor","paragraphid","read_count","recommended","column","column1","post_title","original_url","text","pg_text","organization","combinWord","wb_topic","wb_topic_keyword","wb_topic_combinWord","account","country","country_code", "province","province_code","city","city_code","district","district_code","business","url","NRN", "created_at","created_year","created_month","created_day","created_hour","created_weekday",/*"retweeted_status",*/"retweeted_guid", "screen_name", "reposts_count", "comments_count","direct_comments_count","praises_count", "register_time", "sex", "level", "verify", "has_picture",/* "video", */"emotion", "originalText", "similar", "verified_reason","verified_type", "description", "source", "emoCombin", "emoNRN", "emoOrganization", "emoTopic", "emoTopicKeyword", "emoTopicCombinWord","emoBusiness", "emoAccount","emoCountry", "emoProvince", "emoCity", "emoDistrict", "userid", "content_type", "host_domain", "total_reposts_count", "direct_reposts_count","followers_count", "repost_trend_cursor", "total_reach_count", "ancestor_text", "ancestor_organization", "ancestor_account", "ancestor_wb_topic", "ancestor_wb_topic_keyword", "ancestor_wb_topic_combinWord", "ancestor_NRN", "ancestor_combinWord", "ancestor_business", "ancestor_country", "ancestor_province", "ancestor_city", "ancestor_district", "ancestor_emotion", "ancestor_emoCombin", "ancestor_emoNRN", "ancestor_emoOrganization","ancestor_emoTopic", "ancestor_emoTopicKeyword", "ancestor_emoTopicCombinWord", "ancestor_emoAccount", "ancestor_emoBusiness", "ancestor_emoCountry", "ancestor_emoProvince", "ancestor_emoCity", "ancestor_emoDistrict", "ancestor_url", "ancestor_host_domain", "ancestor_similar", "father_guid", "source_host","question_id","answer_id","question_father_id","answer_father_id","child_post_id","trample_count", "productType", "impress", "commentTags", "satisfaction", "godRepPer", "midRepPer", "wosRepPer", "godRepNum", "midRepNum", "wosRepNum", "apdRepNum", "showPicNum", "cmtStarLevel", "purchDate", 'isNewPro','proClassify','proOriPrice','proCurPrice','proPriPrice','promotionInfos','productFullName','productColor','productSize','productDesc','productComb','detailParam','stockNum','salesNumMonth','compName','compAddress','phoneNum','operateTime','compURL','serviceProm','logisticsInfo','payMethod','compDesMatch','logisticsScore','serviceScore','serviceComment','apdComment','isFavorite','isAttention','retweeted_created_at','nowLocation','trading_count_m','article_taginfo','product_no','pro_init_price');
    $m->datajson->facet = createJsonFilter('分组统计',JSON_DATATYPE_STRING, true, false,-1);
    foreach($allfacetlimit as $k=>$v){
        $m->datajson->facet['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT); 
    }
    
	$m->datajson->allowupdatesnapshot = true;//是否允许此模型定时快照更新
    $m->datajson->alloweventalert = true;//是否允许此模型事件预警
    $m->datajson->download_DataLimit = 1000;//最多下载多少
    $m->datajson->download_DataLimit_limitcontrol = -1;//不限制修改次数
    $m->datajson->download_FieldLimit_limitcontrol = -1;
    $m->datajson->allowDownload = true;//是否允许下载此模型
    $downfields = array();
    $downfields[] = array("text"=>"序号", "value"=>"number");
    $downfields[] = array("text"=>"统计结果", "value"=>"facet");
    $downfields[] = array("text"=>"文章数", "value"=>"frq");
    $downfields[] = array("text"=>"转发数", "value"=>"reposts_count");
    $downfields[] = array("text"=>"评论数", "value"=>"comments_count");
    $downfields[] = array("text"=>"讨论数", "value"=>"discuss_count");
    $downfields[] = array("text"=>"直接转发数", "value"=>"direct_reposts_count");
    $downfields[] = array("text"=>"总转发数", "value"=>"total_reposts_count");
    $downfields[] = array("text"=>"直接到达数", "value"=>"followers_count");
    $downfields[] = array("text"=>"总到达数", "value"=>"total_reach_count");

    $m->datajson->download_FieldLimit = $downfields;//允许下载哪些字段
    
    //输出过滤器限制
    $m->datajson->facet['filterlimit'] = createJsonFilter('输出过滤',JSON_DATATYPE_STRING, true, false,-1);
    /*
    $m->datajson->facet['field'] = array();
    $m->datajson->facet['field'][] = array("name"=>"text","facettype"=>101, "filter"=>array());
    $m->datajson->facet['range'] = array(); 
    $m->datajson->facet['range'][] = array("name"=>"created_at","gap"=>1000,"start"=>100,"end"=>9000);*/
    $m->datajson->select = createJsonFilter('查询字段',JSON_DATATYPE_STRING, false, false, -1);
    $allsellimit = array("id","screen_name","location","description","profile_image_url", "followers_count", "friends_count", "statuses_count","level","verify", "verified_reason","verified_type","sex", "sourceid","text","created_at","reposts_count", "comments_count","direct_comments_count","direct_reposts_count","praises_count","content_type", "userid", "mid","source", "thumbnail_pic", "bmiddle_pic", "retweeted_status", "retweeted_mid", "guid", "father_guid","floor","question_id","answer_id","question_father_id","answer_father_id","child_post_id","trample_count","satisfaction", "godRepPer", "midRepPer", "wosRepPer", "godRepNum", "midRepNum", "wosRepNum", "apdRepNum", "showPicNum", "cmtStarLevel","productType","impress","commentTags","purchDate", 'isNewPro','proClassify','proPic','proOriPrice','proCurPrice','proPriPrice','promotionInfos','productFullName','productColor','productSize','productDesc','productComb','detailParam','stockNum','salesNumMonth','compName','compAddress','phoneNum','operateTime','compURL','serviceProm','logisticsInfo','payMethod','compDesMatch','logisticsScore','serviceScore','serviceComment','apdComment','nowLocation','isFavorite','isAttention',"father_floor","paragraphid","read_count","recommended","original_url","page_url","column","column1","post_title","source_host", "docguid", "article_taginfo","trading_count_m");
    /*foreach($allsellimit as $k=>$v){
        $m->datajson->select['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);
    }*/
    $m->datajson->select['value'] = $allsellimit;
    $m->datajson->select['allowcontrol'] = 0;
    
    $alloutputlimit2 = array("id","screen_name","location","description","profile_image_url", "followers_count", "friends_count", "statuses_count","level","verify", "verified_reason","verified_type","sex", "sourceid","text","created_at","reposts_count", "comments_count","direct_comments_count","direct_reposts_count","praises_count","content_type", "userid", "mid","source", "thumbnail_pic", "bmiddle_pic", "retweeted_status", "retweeted_mid", "guid", "father_guid","floor","question_id","answer_id","question_father_id","answer_father_id","child_post_id","trample_count","satisfaction", "godRepPer", "midRepPer", "wosRepPer", "godRepNum", "midRepNum", "wosRepNum", "apdRepNum", "showPicNum", "cmtStarLevel","productType","impress","commentTags","purchDate", 'isNewPro','proClassify','proPic','proOriPrice','proCurPrice','proPriPrice','promotionInfos','productFullName','productColor','productSize','productDesc','productComb','detailParam','stockNum','salesNumMonth','compName','compAddress','phoneNum','operateTime','compURL','serviceProm','logisticsInfo','payMethod','compDesMatch','logisticsScore','serviceScore','serviceComment','apdComment','nowLocation','isFavorite','isAttention',"father_floor","paragraphid","read_count","recommended","original_url","page_url","column","column1","post_title","source_host", "docguid", "article_taginfo","trading_count_m");
    $m->datajson->output = createJsonFilter('输出条件',JSON_DATATYPE_STRING,  true, false, -1);
    foreach($alloutputlimit2 as $k=>$v){
        $m->datajson->output['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);  
    }
    $m->datajson->output['allowcontrol'] = -1;
    $m->datajson->output['outputtype'] = OUTPUTTYPE_FACET;//1只查询，2只facet
    $m->datajson->output['countlimit'] = createJsonFilter('数据量限制',JSON_DATATYPE_RANGE, false, false,-1);
    $m->datajson->output['data_limit'] = 0;
    $m->datajson->output['count'] = 10;
    $m->datajson->output['orderby'] = "created_at";
	$m->datajson->output['ordertype'] = "desc";
    $m->datajson->output['pageable'] = true;//是否可分页,选中列表视图时，控制是否显示分页
	//唯一字段
    //$alldictinctlimit = array("screen_name", "created_at", "originalText", "city_code", "district_code", "province_code", "country_code", "sex", "verify", "reposts_count", "comments_count", "content_type", "sourceid", "register_time");
    $alldictinctlimit = array("screen_name");
	$m->datajson->distinct = createJsonFilter("结果唯一", JSON_DATATYPE_STRING, false, false, -1);
	foreach($alldictinctlimit as $key => $value){
		$m->datajson->distinct["limit"][] = createLimitItem($value, 1, JSON_LIMITTYPE_EXACT);
	}
	$m->datajson->distinct["distinctfield"] = "";

    $modeljs["topkeyword"] = $m->modelid;
    $arrmodel[] = $m;
    $topicchannel[] = $m;
    
	/*
    $allshow[$m->modelid][] = array("name"=>"动态过滤图表(2/3屏)","id"=>"filterchart");
	 */
    $allshow[$m->modelid][] = array("name"=>"列表(全屏)","id"=>"fullscreenlist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(2/3屏)","id"=>"singlelist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"列表(1/3屏)","id"=>"smalllist", "groupid"=>"list");
	//标签墙
    $allshow[$m->modelid][] = array("name"=>"标签墙(2/3屏)","id"=>"bigcloudchart", "groupid"=>"cloud");
    $allshow[$m->modelid][] = array("name"=>"标签墙(1/3屏)","id"=>"smallcloudchart", "groupid"=>"cloud");
	//线型图
    $allshow[$m->modelid][] = array("name"=>"线型图(全屏)","id"=>"fullscreenmslinechart", "groupid"=>"msline");
    $allshow[$m->modelid][] = array("name"=>"线型图(2/3屏)","id"=>"bigmslinechart", "groupid"=>"msline");
    $allshow[$m->modelid][] = array("name"=>"线型图(1/3屏)","id"=>"smallmslinechart", "groupid"=>"msline");
	//柱状图
    $allshow[$m->modelid][] = array("name"=>"柱状图(全屏)","id"=>"fullscreenmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(2/3屏)","id"=>"bigmulticolumn3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"柱状图(1/3屏)","id"=>"smallmulticolumn3d", "groupid"=>"barchart");
	//横向柱状图
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(全屏)","id"=>"fullscreenmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(2/3屏)","id"=>"bigmultibar3d", "groupid"=>"barchart");
    $allshow[$m->modelid][] = array("name"=>"横向柱状图(1/3屏)","id"=>"smallmultibar3d", "groupid"=>"barchart");
	//地图
    $allshow[$m->modelid][] = array("name"=>"地图(2/3屏)","id"=>"mapchart", "groupid"=>"map");
    //热力图
    $allshow[$m->modelid][] = array("name"=>"热力图(2/3屏)","id"=>"heatmapchart", "groupid"=>"heatmap");
	//饼图
    $allshow[$m->modelid][] = array("name"=>"饼状图(全屏)","id"=>"fullscreenpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(2/3屏)","id"=>"bigpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(1/3屏)","id"=>"smallpiechart", "groupid"=>"pie");
    //环形图
    $allshow[$m->modelid][] = array("name"=>"环形图(全屏)","id"=>"fullscreencircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(2/3屏)","id"=>"bigcircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(1/3屏)","id"=>"smallcircularpiechart", "groupid"=>"circularpie");
    //雷达图
    $allshow[$m->modelid][] = array("name"=>"雷达图(全屏)","id"=>"fullscreenradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(2/3屏)","id"=>"bigradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(1/3屏)","id"=>"smallradarchart", "groupid"=>"radar");
    //和弦图
    $allshow[$m->modelid][] = array("name"=>"和弦图(全屏)","id"=>"fullscreenchordchart", "groupid"=>"chord");
    $allshow[$m->modelid][] = array("name"=>"和弦图(2/3屏)","id"=>"bigchordchart", "groupid"=>"chord");
    $allshow[$m->modelid][] = array("name"=>"和弦图(1/3屏)","id"=>"smallchordchart", "groupid"=>"chord");
    //箱线图
    $allshow[$m->modelid][] = array("name"=>"箱线图(全屏)","id"=>"fullscreenboxplotchart", "groupid"=>"boxplot");
    $allshow[$m->modelid][] = array("name"=>"箱线图(2/3屏)","id"=>"bigboxplotchart", "groupid"=>"boxplot");
    $allshow[$m->modelid][] = array("name"=>"箱线图(1/3屏)","id"=>"smallboxplotchart", "groupid"=>"boxplot");
    //漏斗图
    $allshow[$m->modelid][] = array("name"=>"漏斗图(全屏)","id"=>"fullscreenfunnelchart", "groupid"=>"funnel");
    $allshow[$m->modelid][] = array("name"=>"漏斗图(2/3屏)","id"=>"bigfunnelchart", "groupid"=>"funnel");
    $allshow[$m->modelid][] = array("name"=>"漏斗图(1/3屏)","id"=>"smallfunnelchart", "groupid"=>"funnel");
    //仪表盘
    $allshow[$m->modelid][] = array("name"=>"仪表盘(全屏)","id"=>"fullscreengaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(2/3屏)","id"=>"biggaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(1/3屏)","id"=>"smallgaugechart", "groupid"=>"gauge");
    //气泡图
    $allshow[$m->modelid][] = array("name"=>"气泡图(全屏)","id"=>"fullscreenscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(2/3屏)","id"=>"bigscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(1/3屏)","id"=>"smallscatterchart", "groupid"=>"scatter");


    
    //微博模型
    $m = new Model(51,'文章分析');
    $m->datajson->version = VERSION;
    $m->datajson->modelid = $m->modelid;

    $m->datajson->filter['question_id'] = createJsonFilter('提问序号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['answer_id'] = createJsonFilter('回答序号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['question_father_id'] = createJsonFilter('提问父级序号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['answer_father_id'] = createJsonFilter('回答父级号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['child_post_id'] = createJsonFilter('子级序列号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['trample_count'] = createJsonFilter('踩',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['floor'] = createJsonFilter('楼号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['paragraphid'] = createJsonFilter('段落号',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['read_count'] = createJsonFilter('阅读数',JSON_DATATYPE_RANGE,  false, false, -1);
    $m->datajson->filter['recommended'] = createJsonFilter('精华帖',JSON_DATATYPE_INT,  false, false, -1);
    $m->datajson->filter['original_url'] = createJsonFilter('页面地址',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['column'] = createJsonFilter('子级栏目',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['column1'] = createJsonFilter('父级栏目',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['post_title'] = createJsonFilter('帖子标题',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['weiboid'] = createJsonFilter('微博ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['weibourl'] = createJsonFilter('微博URL',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['oristatus'] = createJsonFilter('原创ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['oristatusurl'] = createJsonFilter('原创URL',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['oristatus_username'] = createJsonFilter('昵称查转发',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['oristatus_userid'] = createJsonFilter('用户名查转发',JSON_DATATYPE_VALUE_TEXT_OBJECT,  false, false, -1); //查转发
    $m->datajson->filter['repost_url'] = createJsonFilter('转发URL',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['repost_username'] = createJsonFilter('昵称查原创',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['repost_userid'] = createJsonFilter('用户名查原创',JSON_DATATYPE_VALUE_TEXT_OBJECT,  false, false, -1); //查转发
    $m->datajson->filter['father_guid'] = createJsonFilter('上层文章唯一标识',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['docguid'] = createJsonFilter('文章标识',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['mid'] = createJsonFilter('文章ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['retweeted_mid'] = createJsonFilter('原创文章ID',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['keyword'] = createJsonFilter('关键词',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['pg_text'] = createJsonFilter('段关键词',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['organization'] = createJsonFilter('机构',JSON_DATATYPE_STRING, false, false, -1);
	$m->datajson->filter['organization']["allowcontrol"] = 1;  //新添加字段 不显示
    $m->datajson->filter['account'] = createJsonFilter('@用户',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
	$m->datajson->filter['account']["allowcontrol"] = 1;  //新添加字段 不显示
    $m->datajson->filter['userid'] = createJsonFilter('用户名',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
	//$m->datajson->filter['userid']["allowcontrol"] = 1;  //新添加字段 不显示
    $m->datajson->filter['weibotopic'] = createJsonFilter('微博话题',JSON_DATATYPE_STRING, false, false, -1);
	$m->datajson->filter['weibotopic']["allowcontrol"] = 1;  //新添加字段 不显示
    $m->datajson->filter['weibotopickeyword'] = createJsonFilter('微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['weibotopiccombinword'] = createJsonFilter('微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['NRN'] = createJsonFilter('人名',JSON_DATATYPE_STRING, false, false, -1);
	$m->datajson->filter['NRN']["allowcontrol"] = 1;  //新添加字段 不显示
    $m->datajson->filter['topic'] = createJsonFilter('短语',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['business'] = createJsonFilter('行业',JSON_DATATYPE_VALUE_TEXT_OBJECT,  false, false, -1);
    $m->datajson->filter['repostsnum'] = createJsonFilter('转发数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['commentsnum'] = createJsonFilter('评论数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['direct_comments_count'] = createJsonFilter('直接评论数',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['praises_count'] = createJsonFilter('赞',JSON_DATATYPE_RANGE, false, false, -1);
    $m->datajson->filter['total_reposts_count'] = createJsonFilter('总转发数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['total_reposts_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['direct_reposts_count'] = createJsonFilter('直接转发数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['direct_reposts_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['total_reach_count'] = createJsonFilter('总到达数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['total_reach_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['followers_count'] = createJsonFilter('直接到达数',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['followers_count']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['repost_trend_cursor'] = createJsonFilter('转发所处层级',JSON_DATATYPE_RANGE, false, false, -1);
	$m->datajson->filter['repost_trend_cursor']["limit"] = array(array("repeat" => 1, "type"=>"range", "value"=>array("maxvalue"=>null, "minvalue"=>null)));
    $m->datajson->filter['areauser'] = createJsonFilter('用户地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['areamentioned'] = createJsonFilter('提及地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['createdtime'] = createJsonFilter('创建时间',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['nearlytime'] = createJsonFilter('相对今天',JSON_DATATYPE_TIMEDYNAMIC_STATE, true, false, -1);
    $m->datajson->filter['beforetime'] = createJsonFilter('时间段',JSON_DATATYPE_TIMEDYNAMIC_STATE, true, false, -1);
    $m->datajson->filter['untiltime'] = createJsonFilter('日历时间',JSON_DATATYPE_TIMEDYNAMIC_RANGE, true, false, -1);
    $m->datajson->filter['created_year'] = createJsonFilter('创建时间(年)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_month'] = createJsonFilter('创建时间(月)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_day'] = createJsonFilter('创建时间(日)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_hour'] = createJsonFilter('创建时间(时)',JSON_DATATYPE_RANGE, true, false, -1);
    $m->datajson->filter['created_weekday'] = createJsonFilter('创建时间(周)',JSON_DATATYPE_RANGE, true, false, -1);

    $m->datajson->filter['emotion'] = createJsonFilter('情感关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoCombin'] = createJsonFilter('情感短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoNRN'] = createJsonFilter('情感人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoOrganization'] = createJsonFilter('情感机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoTopic'] = createJsonFilter('情感微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoTopicKeyword'] = createJsonFilter('情感微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoTopicCombinWord'] = createJsonFilter('情感微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['emoAccount'] = createJsonFilter('@用户情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['emoBusiness'] = createJsonFilter('行业情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['emoAreamentioned'] = createJsonFilter('地区情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['weibotype'] = createJsonFilter('微博类型',JSON_DATATYPE_INT, true, false, -1);
    $m->datajson->filter['source'] = createJsonFilter('应用来源',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['hostdomain'] = createJsonFilter('主机域名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['sourceid'] = createJsonFilter('数据来源',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['source_host'] = createJsonFilter('数据来源',JSON_DATATYPE_STRING, true, false,-1);
    $m->datajson->filter['username'] = createJsonFilter('作者昵称',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['level'] = createJsonFilter('用户级别',JSON_DATATYPE_RANGE, true, false,-1);
    $m->datajson->filter['verified'] = createJsonFilter('认证',JSON_DATATYPE_INT, true, false,-1);
    $m->datajson->filter['verified_type'] = createJsonFilter('认证类型',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['haspicture'] = createJsonFilter('含有图片',JSON_DATATYPE_INT, true, false,-1);
    //$m->datajson->filter['registertime'] = createJsonFilter('博龄',JSON_DATATYPE_RANGE, false, false, 1);
    $m->datajson->filter['registertime'] = createJsonFilter('博龄',JSON_DATATYPE_GAPRANGE, false, false, 1);
	$m->datajson->filter['registertime']["limit"] = array(array("repeat" => 1, "type"=>"gaprange", "value"=>array("maxvalue"=>null, "minvalue"=>null, "gap"=>null)));
    $m->datajson->filter['sex'] = createJsonFilter('作者性别',JSON_DATATYPE_STRING, true, false, -1);
    $m->datajson->filter['verifiedreason'] = createJsonFilter('认证原因',JSON_DATATYPE_STRING, false, false,-1);
    $m->datajson->filter['description'] = createJsonFilter('简介',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['originalcontent'] = createJsonFilter('原文内容',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['digestcontent'] = createJsonFilter('摘要内容',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_text'] = createJsonFilter('上层转发关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_organization'] = createJsonFilter('上层转发机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_account'] = createJsonFilter('上层转发@用户',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_wb_topic'] = createJsonFilter('上层转发微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_wb_topic_keyword'] = createJsonFilter('上层转发微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_wb_topic_combinWord'] = createJsonFilter('上层转发微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_NRN'] = createJsonFilter('上层转发人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_combinWord'] = createJsonFilter('上层转发短语',JSON_DATATYPE_STRING,  false, false, -1);
    $m->datajson->filter['ancestor_business'] = createJsonFilter('上层转发行业',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_areamentioned'] = createJsonFilter('上层转发提及地区',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_emotion'] = createJsonFilter('上层转发情感关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoCombin'] = createJsonFilter('上层转发情感短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoNRN'] = createJsonFilter('上层转发情感人名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoOrganization'] = createJsonFilter('上层转发情感机构',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoTopic'] = createJsonFilter('上层转发情感微博话题',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoTopicKeyword'] = createJsonFilter('上层转发情感微博话题关键词',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoTopicCombinWord'] = createJsonFilter('上层转发情感微博话题短语',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_emoAccount'] = createJsonFilter('上层转发@用户情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_emoBusiness'] = createJsonFilter('上层转发行业情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_emoAreamentioned'] = createJsonFilter('上层转发地区情感',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['ancestor_url'] = createJsonFilter('上层转发URL',JSON_DATATYPE_STRING,  false, false, -1); //查转发
    $m->datajson->filter['ancestor_host_domain'] = createJsonFilter('上层转发主机域名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['ancestor_similar'] = createJsonFilter('上层转发摘要内容',JSON_DATATYPE_STRING, false, false, -1);

    //为电商增加的数据字段
    $m->datajson->filter['productType'] = createJsonFilter('产品型号',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['impress'] = createJsonFilter('买家印象',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['commentTags'] = createJsonFilter('单评论的标签',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['satisfaction'] = createJsonFilter('满意度',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['godRepPer'] = createJsonFilter('好评百分比',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['midRepPer'] = createJsonFilter('中评百分比',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['wosRepPer'] = createJsonFilter('差评百分比',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['godRepNum'] = createJsonFilter('好评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['midRepNum'] = createJsonFilter('中评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['wosRepNum'] = createJsonFilter('差评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['apdRepNum'] = createJsonFilter('追评数',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['showPicNum'] = createJsonFilter('有晒单的评价',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['cmtStarLevel'] = createJsonFilter('评价所属星级',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['purchDate'] = createJsonFilter('购买日期',JSON_DATATYPE_INT, false, false, -1);

    $m->datajson->filter['isNewPro'] = createJsonFilter('是否为新品',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['proClassify'] = createJsonFilter('详细商品分类',JSON_DATATYPE_STRING, false, false, -1);//详细商品分类(栏目)
    $m->datajson->filter['proOriPrice'] = createJsonFilter('原价',JSON_DATATYPE_INT, false, false, -1);//原价/专柜价
    $m->datajson->filter['proCurPrice'] = createJsonFilter('现价',JSON_DATATYPE_INT, false, false, -1);//现价/京东价/天猫价
    $m->datajson->filter['proPriPrice'] = createJsonFilter('促销价',JSON_DATATYPE_INT, false, false, -1);//促销价/优惠价
    $m->datajson->filter['product_no'] = createJsonFilter('产品的编号',JSON_DATATYPE_STRING, false, false, -1);//产品的编号：2017-1-24
    $m->datajson->filter['pro_init_price'] = createJsonFilter('上市价格',JSON_DATATYPE_INT, false, false, -1);//上市价格：2017-1-24
    $m->datajson->filter['promotionInfos'] = createJsonFilter('促销信息',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productFullName'] = createJsonFilter('产品全名',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productColor'] = createJsonFilter('产品颜色',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productSize'] = createJsonFilter('产品尺寸',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productDesc'] = createJsonFilter('产品描述',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filter['productComb'] = createJsonFilter('产品组合',JSON_DATATYPE_STRING, false, false, -1);//产品组合/产品套餐
    $m->datajson->filter['detailParam'] = createJsonFilter('规格参数',JSON_DATATYPE_STRING, false, false, -1);//规格参数/详细参数
    $m->datajson->filter['stockNum'] = createJsonFilter('库存',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['salesNumMonth'] = createJsonFilter('月成交量',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['compName'] = createJsonFilter('公司名称',JSON_DATATYPE_STRING, false, false, -1);//掌柜/公司名称
    $m->datajson->filter['compAddress'] = createJsonFilter('公司地址',JSON_DATATYPE_STRING, false, false, -1);// 公司地址/卖家地址
    $m->datajson->filter['phoneNum'] = createJsonFilter('公司电话',JSON_DATATYPE_STRING, false, false, -1);//公司电话/卖家电话
    $m->datajson->filter['operateTime'] = createJsonFilter('开店时长',JSON_DATATYPE_INT, false, false, -1);//开店时长(以天为单位)
    $m->datajson->filter['compURL'] = createJsonFilter('公司URL',JSON_DATATYPE_STRING, false, false, -1);//公司URL
    $m->datajson->filter['serviceProm'] = createJsonFilter('服务承诺',JSON_DATATYPE_STRING, false, false, -1);//服务承诺
    $m->datajson->filter['logisticsInfo'] = createJsonFilter('物流',JSON_DATATYPE_STRING, false, false, -1);//物流
    $m->datajson->filter['payMethod'] = createJsonFilter('支付方式',JSON_DATATYPE_STRING, false, false, -1);//支付方式
    $m->datajson->filter['compDesMatch'] = createJsonFilter('对公司总体打分',JSON_DATATYPE_INT, false, false, -1);//对公司总体打分/与描述相符
    $m->datajson->filter['logisticsScore'] = createJsonFilter('对公司物流打分',JSON_DATATYPE_INT, false, false, -1);//对公司物流打分
    $m->datajson->filter['serviceScore'] = createJsonFilter('对公司服务打分',JSON_DATATYPE_INT, false, false, -1);//对公司服务打分
    $m->datajson->filter['serviceComment'] = createJsonFilter('对服务的评论',JSON_DATATYPE_STRING, false, false, -1);//对服务的评论 (天猫中对服务有)单独的评论选项
    $m->datajson->filter['apdComment'] = createJsonFilter('追评内容',JSON_DATATYPE_STRING, false, false, -1);//追评内容
    $m->datajson->filter['nowLocation'] = createJsonFilter('出发地',JSON_DATATYPE_STRING, false, false, -1);//出发地 add by zuo:2016-8-10
    $m->datajson->filter['trading_count_m'] = createJsonFilter('月成交笔数',JSON_DATATYPE_INT, false, false, -1);//新加的字段：月成交笔数by zuo:2016-11-2
    $m->datajson->filter['isFavorite'] = createJsonFilter('收藏',JSON_DATATYPE_INT, false, false, -1);//收藏
    $m->datajson->filter['isAttention'] = createJsonFilter('关注',JSON_DATATYPE_INT, false, false, -1);
    $m->datajson->filter['article_taginfo'] = createJsonFilter('文章标签',JSON_DATATYPE_VALUE_TEXT_OBJECT, false, false, -1);
    $m->datajson->filter['id'] = createJsonFilter('微博ID',JSON_DATATYPE_STRING, false, false, -1);//微博id：2017-1-11
    $m->datajson->filter['pid'] = createJsonFilter('父级微博ID',JSON_DATATYPE_STRING, false, false, -1);//父级微博id：2017-1-11
    $m->datajson->filter['retweeted_guid'] = createJsonFilter('原创guid',JSON_DATATYPE_STRING, false, false, -1);//原创guid 2017-3-13

    //$m->datajson->filter['excludeweiboid'] = createJsonFilter('exclude微博',JSON_DATATYPE_STRING, false, false, -1);
    $m->datajson->filtervalue = array();
    $m->datajson->filterrelation = null;
    $m->datajson->facet = createJsonFilter('分组统计',JSON_DATATYPE_STRING,false, false,-1);
    //输出过滤器限制
    $m->datajson->facet['filterlimit'] = createJsonFilter('输出过滤',JSON_DATATYPE_STRING, false, false, -1);
    $r['allowcontrol'] = 0;//允许修改的次数，-1不限制，0不允许
    /*
    $m->datajson->facet['field'] = array();
    $m->datajson->facet['field'][] = array("name"=>"text","facettype"=>101, "filter"=>array());
    $m->datajson->facet['range'] = array(); 
    $m->datajson->facet['range'][] = array("name"=>"created_at","gap"=>1000,"start"=>100,"end"=>9000);*/
    $m->datajson->select = createJsonFilter('查询字段',JSON_DATATYPE_STRING,false, false, 0);
    //$allsellimit = array("id","userid","mid","sourceid","screen_name","text","created_at", "reposts_count", "comments_count");
    $allsellimit = array("id","screen_name","userid","mid","sourceid","description","profile_image_url", "followers_count", "friends_count", "statuses_count","level", "verify", "verified_reason","verified_type", "sex" ,"text","created_at","reposts_count", "comments_count","direct_comments_count","direct_reposts_count", "praises_count", "content_type", "source", "thumbnail_pic", "bmiddle_pic", "retweeted_status", "retweeted_mid", "retweeted_guid","guid","account", "father_guid", "floor","question_id","answer_id","question_father_id","answer_father_id","child_post_id","trample_count","productType","impress","commentTags","satisfaction", "godRepPer", "midRepPer", "wosRepPer", "godRepNum", "midRepNum", "wosRepNum", "apdRepNum", "showPicNum", "cmtStarLevel","purchDate", 'isNewPro','proClassify','proPic','proOriPrice','proCurPrice','proPriPrice','promotionInfos','productFullName','productColor','productSize','productDesc','productComb','detailParam','stockNum','salesNumMonth','compName','compAddress','phoneNum','operateTime','compURL','serviceProm','logisticsInfo','payMethod','compDesMatch','logisticsScore','serviceScore','serviceComment','apdComment','nowLocation','isFavorite','isAttention',"father_floor","paragraphid","read_count","recommended","original_url","page_url","column","column1","post_title","source_host", "docguid", "article_taginfo","trading_count_m","id","pid","product_no","pro_init_price");

    /*foreach($allsellimit as $k=>$v){
        $m->datajson->select['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);
    }*/
    $m->datajson->select['value'] = $allsellimit;
    $m->datajson->select['allowcontrol'] = 0;
  
	$m->datajson->allowupdatesnapshot = true;//是否允许此模型定时快照更新
    $m->datajson->alloweventalert = true;//是否允许此模型事件预警
    $m->datajson->download_DataLimit = 1000;//最多下载多少
    $m->datajson->download_DataLimit_limitcontrol = -1;//不限制修改次数
    $m->datajson->download_FieldLimit_limitcontrol = -1;
    $m->datajson->allowDownload = true;//是否允许下载此模型
    $downfields = array();
    $downfields[] = array("text"=>"序号", "value"=>"number");
    $downfields[] = array("text"=>"楼号", "value"=>"floor");
    $downfields[] = array("text"=>"阅读数", "value"=>"read_count");
    $downfields[] = array("text"=>"栏目", "value"=>"column");
    $downfields[] = array("text"=>"父级栏目", "value"=>"column1");
    $downfields[] = array("text"=>"帖子标题", "value"=>"post_title");
    $downfields[] = array("text"=>"当页地址", "value"=>"page_url");
    $downfields[] = array("text"=>"页面地址", "value"=>"original_url");// add original_url zuo by 2016-8-10
    $downfields[] = array("text"=>"用户国家", "value"=>"country_code");//add 下载加字段 by zuo:2016-9-2 "city_code", "district_code", "province_code", "country_code"
    $downfields[] = array("text"=>"用户省份", "value"=>"province_code");
    $downfields[] = array("text"=>"用户县区", "value"=>"district_code");
    $downfields[] = array("text"=>"用户城市", "value"=>"city_code");
    $downfields[] = array("text"=>"数据来源", "value"=>"source_host");
    $downfields[] = array("text"=>"用户昵称", "value"=>"screen_name");
    $downfields[] = array("text"=>"用户地址", "value"=>"userurl");
    $downfields[] = array("text"=>"内容", "value"=>"text");
    $downfields[] = array("text"=>"所在段落号", "value"=>"paragraphid");
    $downfields[] = array("text"=>"转发数", "value"=>"reposts_count");
    $downfields[] = array("text"=>"评论数", "value"=>"comments_count");
    $downfields[] = array("text"=>"直接评论数", "value"=>"direct_comments_count");
    $downfields[] = array("text"=>"赞", "value"=>"praises_count");
    $downfields[] = array("text"=>"直接转发数", "value"=>"direct_reposts_count");
    $downfields[] = array("text"=>"总转发数", "value"=>"total_reposts_count");
    $downfields[] = array("text"=>"应用来源", "value"=>"source");
    $downfields[] = array("text"=>"用户简介", "value"=>"description");
    $downfields[] = array("text"=>"性别", "value"=>"sex");
    $downfields[] = array("text"=>"认证用户", "value"=>"verify");
    $downfields[] = array("text"=>"认证类型", "value"=>"verified_type");
    $downfields[] = array("text"=>"用户级别", "value"=>"level");
    $downfields[] = array("text"=>"认证原因", "value"=>"verified_reason");
    $downfields[] = array("text"=>"微博地址", "value"=>"weibourl");
    $downfields[] = array("text"=>"发布时间", "value"=>"created_at");
    $downfields[] = array("text"=>"创建时间(年)", "value"=>"created_year");
    $downfields[] = array("text"=>"创建时间(月)", "value"=>"created_month");
    $downfields[] = array("text"=>"创建时间(日)", "value"=>"created_day");
    $downfields[] = array("text"=>"创建时间(时)", "value"=>"created_hour");
    $downfields[] = array("text"=>"创建时间(周)", "value"=>"created_weekday");
    $downfields[] = array("text"=>"直接到达数", "value"=>"followers_count");
    $downfields[] = array("text"=>"总到达数", "value"=>"total_reach_count");
    $downfields[] = array("text"=>"产品型号", "value"=>"productType");
    $downfields[] = array("text"=>"买家印象", "value"=>"impress");
    $downfields[] = array("text"=>"单评论的标签", "value"=>"commentTags");
    $downfields[] = array("text"=>"满意度", "value"=>"satisfaction");
    $downfields[] = array("text"=>"好评百分比", "value"=>"godRepPer");
    $downfields[] = array("text"=>"中评百分比", "value"=>"midRepPer");
    $downfields[] = array("text"=>"差评百分比", "value"=>"wosRepPer");
    $downfields[] = array("text"=>"好评数", "value"=>"godRepNum");
    $downfields[] = array("text"=>"中评数", "value"=>"midRepNum");
    $downfields[] = array("text"=>"差评数", "value"=>"wosRepNum");
    $downfields[] = array("text"=>"追评数", "value"=>"apdRepNum");
    $downfields[] = array("text"=>"有晒单的评价", "value"=>"showPicNum");
    $downfields[] = array("text"=>"评价所属星级", "value"=>"cmtStarLevel");
    $downfields[] = array("text"=>"购买日期", "value"=>"purchDate");
    $downfields[] = array("text"=>"是否为新品", "value"=>"isNewPro");
    $downfields[] = array("text"=>"详细商品分类", "value"=>"proClassify");
    $downfields[] = array("text"=>"原价", "value"=>"proOriPrice");
    $downfields[] = array("text"=>"现价", "value"=>"proCurPrice");
    $downfields[] = array("text"=>"促销价", "value"=>"proPriPrice");
    $downfields[] = array("text"=>"促销信息", "value"=>"promotionInfos");
    $downfields[] = array("text"=>"产品的编号", "value"=>"product_no");//产品的编号 2017-1-24
    $downfields[] = array("text"=>"上市价格", "value"=>"pro_init_price");//上市价格 2017-1-24
    $downfields[] = array("text"=>"产品全名", "value"=>"productFullName");
    $downfields[] = array("text"=>"产品颜色", "value"=>"productColor");
    $downfields[] = array("text"=>"产品描述", "value"=>"productDesc");
    $downfields[] = array("text"=>"产品组合", "value"=>"productComb");
    $downfields[] = array("text"=>"规格参数", "value"=>"detailParam");
    $downfields[] = array("text"=>"库存", "value"=>"stockNum");
    $downfields[] = array("text"=>"月成交量", "value"=>"salesNumMonth");
    $downfields[] = array("text"=>"公司名称", "value"=>"compName");
    $downfields[] = array("text"=>"公司地址", "value"=>"compAddress");
    $downfields[] = array("text"=>"公司电话", "value"=>"phoneNum");
    $downfields[] = array("text"=>"开店时长", "value"=>"operateTime");
    $downfields[] = array("text"=>"公司URL", "value"=>"compURL");
    $downfields[] = array("text"=>"服务承诺", "value"=>"serviceProm");
    $downfields[] = array("text"=>"物流", "value"=>"logisticsInfo");
    $downfields[] = array("text"=>"支付方式", "value"=>"payMethod");
    $downfields[] = array("text"=>"对公司物流打分", "value"=>"logisticsScore");
    $downfields[] = array("text"=>"对公司服务打分", "value"=>"serviceScore");
    $downfields[] = array("text"=>"对服务的评论", "value"=>"serviceComment");
    $downfields[] = array("text"=>"出发地", "value"=>"nowLocation");
    $downfields[] = array("text"=>"月成交笔数", "value"=>"trading_count_m");//新加的字段：月成交笔数by zuo:2016-11-2
    $downfields[] = array("text"=>"文章标签", "value"=>"article_taginfo");
    $downfields[] = array("text"=>"追评内容", "value"=>"apdComment");
    $downfields[] = array("text"=>"收藏", "value"=>"isFavorite");
    $downfields[] = array("text"=>"关注", "value"=>"isAttention");
    $downfields[] = array("text"=>"微博ID", "value"=>"id");//2017-1-11
    $downfields[] = array("text"=>"父级微博ID", "value"=>"pid");//2017-1-11
    $downfields[] = array("text"=>"原创guid", "value"=>"retweeted_guid");//2017-3-13
    $m->datajson->download_FieldLimit = $downfields;//允许下载哪些字段
    
    $alloutputlimit2 = array("comments_count","direct_comments_count","praises_count","reposts_count", "created_at", "direct_reposts_count", "total_reposts_count", "read_count", "floor");
    $m->datajson->output = createJsonFilter('输出条件',JSON_DATATYPE_STRING, true, false, -1);
    foreach($alloutputlimit2 as $k=>$v){
        $m->datajson->output['limit'][] = createLimitItem($v, 1, JSON_LIMITTYPE_EXACT);  
    }
    $m->datajson->output['allowcontrol'] = -1;
    $m->datajson->output['outputtype'] = OUTPUTTYPE_QUERY;//1只查询，2只facet
    $m->datajson->output['countlimit'] = createJsonFilter('数据量限制',JSON_DATATYPE_RANGE, false, false,-1);
    $m->datajson->output['data_limit'] = 0;
    $m->datajson->output['count'] = 10;
    $m->datajson->output['orderby'] = "created_at";
    $m->datajson->output['ordertype'] = "";
    $m->datajson->output['pageable'] = true;//是否可分页,选中列表视图时，控制是否显示分页
    //唯一字段
    $alldictinctlimit = array("screen_name");
	$m->datajson->distinct = createJsonFilter("结果唯一", JSON_DATATYPE_STRING, false, false, -1);
	foreach($alldictinctlimit as $key => $value){
		$m->datajson->distinct["limit"][] = createLimitItem($value, 1, JSON_LIMITTYPE_EXACT);
	}
	$m->datajson->distinct["distinctfield"] = "";

    
    $modeljs["weiboquery"] = $m->modelid;
    $arrmodel[] = $m;
    $weibochannel[] = $m;
    
	/*
    $allshow[$m->modelid][] = array("name"=>"动态过滤图表(2/3屏)","id"=>"filterchart");
	 */
    $allshow[$m->modelid][] = array("name"=>"完整内容列表(全屏)","id"=>"fullscreenlist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"完整内容列表(2/3屏)","id"=>"singlelist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"完整内容列表(1/3屏)","id"=>"smalllist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"表格列表(1/3屏)","id"=>"postlist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"图片列表","id"=>"picturelist", "groupid"=>"list");
    $allshow[$m->modelid][] = array("name"=>"饼状图(全屏)","id"=>"fullscreenpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(2/3屏)","id"=>"bigpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"饼状图(1/3屏)","id"=>"smallpiechart", "groupid"=>"pie");
    $allshow[$m->modelid][] = array("name"=>"环形图(全屏)","id"=>"fullscreencircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(2/3屏)","id"=>"bigcircularpiechart", "groupid"=>"circularpie");
    $allshow[$m->modelid][] = array("name"=>"环形图(1/3屏)","id"=>"smallcircularpiechart", "groupid"=>"circularpie");

    /*
    $allshow[$m->modelid][] = array("name"=>"雷达图(全屏)","id"=>"fullscreenradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(2/3屏)","id"=>"bigradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"雷达图(1/3屏)","id"=>"smallradarchart", "groupid"=>"radar");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(全屏)","id"=>"fullscreengaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(2/3屏)","id"=>"biggaugechart", "groupid"=>"gauge");
    $allshow[$m->modelid][] = array("name"=>"仪表盘(1/3屏)","id"=>"smallgaugechart", "groupid"=>"gauge");
     */

    $allshow[$m->modelid][] = array("name"=>"气泡图(全屏)","id"=>"fullscreenscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(2/3屏)","id"=>"bigscatterchart", "groupid"=>"scatter");
    $allshow[$m->modelid][] = array("name"=>"气泡图(1/3屏)","id"=>"smallscatterchart", "groupid"=>"scatter");

    $allshow[$m->modelid][] = array("name"=>"转发轨迹(2/3屏)","id"=>"weiborepost", "groupid"=>"propagationpath");
    $allshow[$m->modelid][] = array("name"=>"评论轨迹(2/3屏)","id"=>"weibocomment", "groupid"=>"propagationpath");

    // add by wang 2017-03 微信传播
    $allshow[$m->modelid][] = array("name"=>"微信转发(2/3屏)","id"=>"weixinrepost", "groupid"=>"propagationpathwx");
    $allshow[$m->modelid][] = array("name"=>"微信评论(2/3屏)","id"=>"weixincomment", "groupid"=>"propagationpathwx");
}

?>
