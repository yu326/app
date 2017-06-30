<?php
include_once('userinfo_v2.class.php');
include_once("commonFun_v2.php");
include_once("authorization_v2.class.php");
include_once("getdata_com.php");
include_once("solragent_com.php");
include_once("PHPExcel.php");

define("PROCESSER_TYPE_ELEMENT",'element');//普通模型
define("PROCESSER_TYPE_LINKAGE",'linkage');//联动模型
define("PROCESSER_TYPE_DOWNLOAD",'download');//下载模型
define("PROCESSER_TYPE_OVERLAY",'overlay');//叠加模型
/**
 *
 * 创建处理对象
 * @param string $datatype
 */
function createDataProcesser($datatype){
	$obj = NULL;
	switch ($datatype){
		case PROCESSER_TYPE_ELEMENT:
			$obj = new ElementDataProcesser();
			break;
		case PROCESSER_TYPE_LINKAGE:
			$obj = new LinkageDataProcesser();
			break;
		case PROCESSER_TYPE_DOWNLOAD:
			$obj = new DownLoadDataProcesser();
			break;
		case PROCESSER_TYPE_OVERLAY:
			$obj = new OverlayDataProcesser();
			break;
		default:
			break;
	}
	return $obj;
}

function createFileProcesser($filetype){
	$obj = NULL;
	switch ($filetype){
		case "excel":
			$obj = new ExcelProcesser();
			break;
		default:
			break;
	}
	return $obj;
}

abstract class DataProcesser{
	public $logger=NULL;
	protected $error;//错误信息
	public $crossDomain = false;
	public $dataOffset;//远程访问数据接口时，可以指定从第几条开始获取
	public $dataRows;//远程访问数据接口时，可以指定获取多少条
	//解析入参
	abstract protected function parseParams($params);
	//获取请求参数
	abstract protected function getDataParams();
	//验证数据范围
	abstract protected function checkLimit($dataparams=NULL, $isdownload=false);
	//获取数据
	abstract protected function getData($dataParams=NULL);
	//格式化数据
	abstract protected function formatData($data=NULL);
	//输出数据
	abstract protected function output($fmtdata=NULL);

	public function checkDomain(){
		//验证是否跨域访问
		return isSameDomain(); 
		/*
		if(isSameDomain() === true){
			$this->crossDomain = false;
		}
		else{
			$this->crossDomain = true;
		}
		return true;
		 */
	}
	
	//验证权限
	public function checkAuth(){
		//判断session是否存在
		$user = Authorization::checkUserSession(true);
		if($user != CHECKSESSION_SUCCESS){
			if(!empty($user)){
				$cdomain = $this->checkDomain();
				if($cdomain === true){
					$this->crossDomain = false;
				}
				else if($cdomain === -1){
					$this->crossDomain = true;
					if(empty($user['allowaccessdata'])){
						$this->error = "抱歉，您没有数据接口访问权限!";
						return false;
					}
				}
				else if($cdomain === -2){
					if(empty($user['allowwidget'])){
						$this->error = "抱歉，您没有widget使用权限!";
						return false;
					}
				}
				return true;
			}
			else{
				$this->error = "未登录或登陆超时!";
				return false;
			}
		}
		return true;
	}

	public function getError(){
		if(empty($this->error)){
			return "";
		}
		else{
			return $this->error;
		}
	}
	/**
	 *
	 * 设置日志对象
	 * @param unknown_type $logger
	 */
	public function setLogger($logg){
		$this->logger = $logg;
	}

	public function log($logtype, $msg){
		if($this->logger !== NULL){
			switch ($logtype){
				case "debug":
					$this->logger->debug(get_class()." - ".$msg);
					break;
				case "warn":
					$this->logger->warn(get_class()." - ".$msg);
					break;
				case "error":
					$this->logger->error(get_class()." - ".$msg);
					break;
				case "info":
					$this->logger->info(get_class()." - ".$msg);
					break;
				default:
					break;
			}
		}
	}

}

/**
 *
 * 单个element数据处理
 * @author Todd
 *
 */
class ElementDataProcesser extends DataProcesser {
	private $instanceID;
	private $elementID;
	private $hasFormJson;//参数中是否指定了json，如果指定，则不需要访问数据库
	private $needOrig = true;//是否需要原创
	private $needRepost = true;//是否需要转发
	private $needComment = true;//是否需要评论
	private $dataJson;//数据json对象
	private $outputData;//最终输出对象
	private $isDrilldown = false;//是否为drilldown查询
	private $needPinyin = false;//是否需要转发

	public function setInstanceID($value){
		$this->instanceID = $value;
	}
	public function getInstanceID(){
		return $this->instanceID;
	}
	public function setelementID($value){
		$this->elementID = $value;
	}
	public function getelementID(){
		return $this->elementID;
	}
	public function setneedOrig($value){
		$this->needOrig = $value;
	}
	public function getneedOrig(){
		return $this->needOrig;
	}
	public function setneedRepost($value){
		$this->needRepost= $value;
	}
	public function getneedRepost(){
		return $this->needRepost;
	}
	public function setneedComment($value){
		$this->needComment= $value;
	}
	public function getneedComment(){
		return $this->needComment;
	}

	public function setdataJson($value){
		$this->dataJson = $value;
	}
	public function getdataJson(){
		return $this->dataJson;
	}
	public function setoutputData($value){
		$this->outputData = $value;
	}
	public function getoutputData(){
		return $this->outputData;
	}

	/**
	 * ElementDataProcesser 
	 * 解析url参数
	 * @param unknown_type $params 传递$_REQUEST对象
	 */
	public function parseParams($params){
		if(empty($params)){
			$this->error = "params is empty";
			return false;
		}
		if(!isset($params['instanceid'])){
			$this->error = "instanceid is empty";
			return false;
		}
		if(!isset($params['elementid'])){
			$this->error = "elementid is empty";
			return false;
		}
		$this->instanceID = $params['instanceid'];
		$this->elementID = $params['elementid'];
		$this->hasFormJson = !empty($params['hasformjson']);
		if($this->hasFormJson){
			if(isset($GLOBALS['HTTP_RAW_POST_DATA'])){
				//从页面提交的datajosn， 不需要合并json因为是从数据库读取下来的已经经过权限合并
				$this->dataJson= json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
			}
			else{
				$this->error = "post data is empty";
				return false;
			}
		}
		$this->needOrig = !empty($params['returnoriginal']);
		$this->needRepost = !empty($params['returnrepost']);
		$this->needPinyin = !empty($params['needpinyin']);
		$this->needComment = !empty($params['returncomment']);
		//是否为drilldown查询
		$this->isDrilldown = !empty($params['isdrilldown']);
		if($this->crossDomain){
			if(isset($params['offset'])){
				$this->dataOffset = $params['offset'];
			}
			if(isset($params['rows'])){
				$this->dataRows = $params['rows'];
			}
		}
		return true;
	}

	/**
	 * ElementDataProcesser 
	 * 生成请求参数(datajson)
	 */
	public function getDataParams(){
		if(empty($this->dataJson)){
			$this->dataJson = getDataJson($this->elementID);//从数据库取datajson,包括合并权限等
			if(empty($this->dataJson)){
				$this->error = "not found datajson from database. elementid:{$this->elementID}";
				return false;
			}
		}
		if($this->crossDomain && isset($this->dataOffset) && isset($this->dataRows)){
			$this->dataJson['output']['data_limit'] = $this->dataOffset;
			$this->dataJson['output']['count'] = $this->dataRows;
		}
		$this->dataJson['returnoriginal'] = $this->needOrig;
		$this->dataJson['returnrepost'] = $this->needRepost;
		$this->dataJson['needpinyin'] = $this->needPinyin;
		$this->dataJson['returncomment'] = $this->needComment;
		//当为drilldown查询时添加此参数
		if($this->isDrilldown){
			$this->dataJson['isdrilldown'] = $this->isDrilldown;
		}
		return $this->dataJson;
	}

	/**
	 * ElementDataProcesser 
	 * 验证数据范围 ，验证未通过时，返回错误信息（object）
	 */
	public function checkLimit($dataparams=NULL, $isdownload=false){
		if(empty($dataparams)){
			$dataparams = $this->dataJson;
		}
		if(empty($dataparams) || !isset($this->instanceID) || !isset($this->elementID)){
			return false;
		}
        $modelid = $dataparams["modelid"];
        if($modelid == 6){
            return true;
        }
		$r = checkUserPure($this->instanceID, $this->elementID, $dataparams,$isdownload, $this->crossDomain);
		if($r == VALIDATE_SUCCESS){
			return true;
		}
		else{
			return $r;
		}
	}

	/**
	 * ElementDataProcesser 
	 * 获取数据
	 * @param unknown_type element的json对象
	 */
	public function getData($dataParams=NULL){
		if(!isset($dataParams)){
			$dataParams = $this->getDataParams();
		}
		if(!isset($dataParams)){
			$this->error = "dataparams is empty";
			return false;
		}
		$result = false;
		if(isset($dataParams['modelid'])){ //标准json
			$modelid = isset($dataParams['modelid']) ? $dataParams['modelid'] : '';
			switch($modelid){
				/*
				case 1:
					$result = getdataInit($dataParams);
					break;
				 */
				case 31:
				case 51:
				case 2:
				case 1:
					$result = solragentInit($dataParams);
					break;
                case 6:
                    $result = $dataParams['snapshot'];
                    break;
				default:
					$result = getErrorOutput("5001", 'requestdata.php modelid error');
					break;
			}
		}
		$this->outputData = $result;
		return $result;
	}

	/**
	 * ElementDataProcesser 
	 * 格式化输出
	 * @param unknown_type $data
	 * @return string
	 */
	public function formatData($data=NULL){
		if(!isset($data)){
			$data = $this->outputData;
		}
		if(!isset($data)){
			return "";
		}
		else{
			return $data;
		}
	}

	/**
	 * ElementDataProcesser 
	 * 输出数据到浏览器
	 * @param unknown_type $fmtdata
	 */
	public function output($fmtdata=NULL){
		if(!isset($fmtdata)){
			$fmtdata = $this->formatData();
		}
		if($this->crossDomain && !empty($_GET['callback'])){//跨域访问接口, 并且是jsonp形式
			echo $_GET['callback']."(".json_encode($fmtdata).")";
		}
		else{
			echo json_encode($fmtdata);
		}
		exit;
	}
}

/**
 *
 * 联动实例数据处理
 * @author Todd
 *
 */
class LinkageDataProcesser extends DataProcesser{
	private $instanceID;
	private $elements;//instance中的所有element
	private $pinRelation;//pin关系
	private $render;//elementtype为2的json. 对象{elementid, datajson}
	private $hasFormJson;//参数中是否指定了json，如果指定，则部需要访问数据库
	private $needOrig = true;//是否需要原创
	private $needRepost = true;//是否需要转发
	private $needComment= true;//是否需要评论
	private $outputData;//最终输出对象
	private $isDrilldown = false; //是否为drilldown查询
	private $checkedLimit = false;//是否已检查过数据范围

	public function setInstanceID($value){
		$this->instanceID = $value;
	}
	public function getInstanceID(){
		return $this->instanceID;
	}
	public function setelements($value){
		$this->elements = $value;
	}
	public function getelements(){
		return $this->elements;
	}
	public function setpinRelation($value){
		$this->pinRelation = $value;
	}
	public function getpinRelation(){
		return $this->pinRelation;
	}
	public function setrender($value){
		$this->render = $value;
	}
	public function getrender(){
		return $this->render;
	}
	public function setneedOrig($value){
		$this->needOrig = $value;
	}
	public function getneedOrig(){
		return $this->needOrig;
	}
	public function setneedRepost($value){
		$this->needRepost= $value;
	}
	public function getneedRepost(){
		return $this->needRepost;
	}
	public function setneedComment($value){
		$this->needComment= $value;
	}
	public function getneedComment(){
		return $this->needComment;
	}
	public function setoutputData($value){
		$this->outputData = $value;
	}
	public function getoutputData(){
		return $this->outputData;
	}


	/**
	 * LinkageDataProcesser 
	 * 解析url参数
	 * @param unknown_type $params 传递$_REQUEST对象
	 */
	public function parseParams($params){
		if(empty($params)){
			$this->error = "params is empty";
			return false;
		}
		$this->isDrilldown = !empty($params['isdrilldown']);
		$this->hasFormJson = !empty($params['hasformjson']);
		$this->needOrig = !empty($params['returnoriginal']);
		$this->needRepost= !empty($params['returnrepost']);
		$this->needComment = !empty($params['returncomment']);
		if(!$this->hasFormJson){
			if(!isset($params['instanceid'])){
				$this->error = "params error: need instanceid";
				return false;
			}
			if(!isset($params['elementid'])){
				$this->error = "params error: need render's id";//联动模型，必须指定一个render
				return false;
			}
			$this->instanceID = $params['instanceid'];
			$this->render = array("elementid"=>$params['elementid'], "datajson"=>NULL);
		}
		else{
			if(isset($GLOBALS['HTTP_RAW_POST_DATA'])){
				//从页面提交的参数
				$r = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
				if(empty($r)){
					$this->error = "post data is empty";
					return false;
				}
				$this->instanceID = $r['instanceid'];
				$this->elements = $r['elements'];
				$this->render = $r['render'];
				$this->pinRelation = $r['pinrelation'];
			}
			else{
				$this->error = "is not post";
				return false;
			}
		}
		if($this->crossDomain){
			if(isset($params['offset'])){
				$this->dataOffset = $params['offset'];
			}
			if(isset($params['rows'])){
				$this->dataRows = $params['rows'];
			}
		}
		return true;
	}

	/**
	 * LinkageDataProcesser 
	 * 从数据库获取数据参数
	 */
	public function getDataParams(){
		if(!$this->hasFormJson){
			$this->elements = array();
			$inc = getelements($this->instanceID, 0, false);
			if(empty($inc) || empty($inc['elements'])){
				$this->error = "elements is empty. instanceid:{$this->instanceID}";
				return false;
			}
			else{
				foreach($inc['elements'] as $key => $value){
					if($value['type'] != 2){//非render
						$this->elements[] = array("elementid"=>$value['elementid'], "datajson"=>$value['datajson']);
					}
					else if($value['elementid'] == $this->render['elementid']){
						$this->render['datajson']= $value['datajson'];
					}
				}
				$this->checkedLimit = false;
				$this->pinRelation = $inc['pinrelation'];
			}
		}
		$this->render["returnoriginal"] = $this->needOrig;
		$this->render["returnrepost"] = $this->needRepost;
		$this->render["returncomment"] = $this->needComment;
		if($this->isDrilldown){
			$this->render["isdrilldown"] = $this->isDrilldown;
		}
		if($this->crossDomain && isset($this->dataOffset) && isset($this->dataRows)){
			$this->render['datajson']['output']['data_limit'] = $this->dataOffset;
			$this->render['datajson']['output']['count'] = $this->dataRows;
		}
		
		return array("instanceid"=>$this->instanceID, "elements"=>$this->elements, "pinrelation"=>$this->pinRelation,
			"render"=>$this->render);
	}

	/**
	 * LinkageDataProcesser 
	 * 验证数据范围 ，验证未通过时，返回错误信息（object）
	 */
	public function checkLimit($dataparams=NULL, $isdownload=false){
		global $logger;
		if(empty($this->elements)){
			$this->elements = array();
		}
		//只有验证render时，将参数isaccessData设置为true
		$renderdatajson = $this->render['datajson'];
		if($dataparams != NULL){
			$renderdatajson = $dataparams['render']['datajson'];
		}
		$result = checkUserPure($this->instanceID, $this->render['elementid'], $renderdatajson,$isdownload, $this->crossDomain);
		if($result == VALIDATE_SUCCESS && !$this->checkedLimit){
			foreach($this->elements as $key => $value){
				$r = checkUserPure($this->instanceID, $value['elementid'], $value['datajson'],$isdownload, false, $this->pinRelation);
				if($r != VALIDATE_SUCCESS){
					$result = $r;
					break;
				}
			}
			if($result == VALIDATE_SUCCESS){
				$this->checkedLimit = true;
			}
		}
		if($result == VALIDATE_SUCCESS){
			return true;
		}
		else{
			return $result;
		}
	}

	/**
	 * LinkageDataProcesser 
	 * 获取数据
	 * @param unknown_type element的json对象
	 */
	public function getData($dataParams=NULL){
		if(!isset($dataParams)){
			$dataParams = $this->getDataParams();
		}
		if(!isset($dataParams)){
			$this->error = "dataparams is empty";
			return false;
		}
		$result = linkageQuery($dataParams);
		$this->outputData = $result;
		return $result;
	}

	/**
	 * LinkageDataProcesser 
	 * 格式化输出
	 * @param unknown_type $data
	 * @return string
	 */
	public function formatData($data=NULL){
		if(!isset($data)){
			$data = $this->outputData;
		}
		if(!isset($data)){
			return "";
		}
		else{
			return $data;
		}
	}

	/**
	 * LinkageDataProcesser 
	 * 输出数据到浏览器
	 * @param unknown_type $fmtdata
	 */
	public function output($fmtdata=NULL){
		if(!isset($fmtdata)){
			$fmtdata = $this->formatData();
		}
		if($this->crossDomain && !empty($_GET['callback'])){//跨域访问接口, 并且是jsonp形式
			echo $_GET['callback']."(".json_encode($fmtdata).")";
		}
		else{
			echo json_encode($fmtdata);
		}
		exit;
	}
}

/*
 * 叠加模型数据处理
 * @author Bert
 * */
class OverlayDataProcesser extends DataProcesser{
	private $instanceID;
	private $elementID;
	private $elements;//instance中的所有element
	private $render;//elementtype为2的json. 对象{elementid, datajson}
	private $checkedLimit = false;//是否已检查过数据范围
	private $hasFormJson;//参数中是否指定了json，如果指定，则不需要访问数据库
	private $needOrig = true;//是否需要原创
	private $needRepost = true;//是否需要转发
	private $needComment = true;//是否需要评论
	private $xCombined = false;//x轴是否合并
	private $columnStacking = false;//柱状图叠加显示
	private $xzReverse = false;//xz轴是否反转
	private $dataJson;//数据json对象
	private $outputData;//最终输出对象
	private $overlays;//请求数据,[{instanceid:22, instancetype:1, datajson:{标准模型请求json}}, {instanceid:22, instancetype:2, datajson:{联动模型请求json}}] 

	public function setInstanceID($value){
		$this->instanceID = $value;
	}
	public function getInstanceID(){
		return $this->instanceID;
	}
	public function setelementID($value){
		$this->elementID = $value;
	}
	public function getelementID(){
		return $this->elementID;
	}
	public function setneedOrig($value){
		$this->needOrig = $value;
	}
	public function getneedOrig(){
		return $this->needOrig;
	}
	public function setneedRepost($value){
		$this->needRepost= $value;
	}
	public function getneedRepost(){
		return $this->needRepost;
	}
	public function setneedComment($value){
		$this->needComment= $value;
	}
	public function getneedComment(){
		return $this->needComment;
	}
	public function setXcombined($value){
		$this->xCombined = $value;
	}
	public function getXcombined(){
		return $this->xCombined;
	}
	public function setColumnStacking($value){
		$this->columnStacking = $value;
	}
	public function getColumnStacking(){
		return $this->columnStacking;
	}
	public function setXZReverse($value){
		$this->xzReverse = $value;
	}
	public function getXZReverse(){
		return $this->xzReverse;
	}

	public function setdataJson($value){
		$this->dataJson = $value;
	}
	public function getdataJson(){
		return $this->dataJson;
	}
	public function setoutputData($value){
		$this->outputData = $value;
	}
	public function getoutputData(){
		return $this->outputData;
	}
	/*
	 * OverlayDataProcesser
	 * 解析url参数
	 * 
	 * */
	public function parseParams($params){
		if(empty($params)){
			$this->error = "params is empty";
			return false;
		}
		$this->hasFormJson = !empty($params['hasformjson']);
		$this->needOrig = !empty($params['returnoriginal']);
		$this->needRepost = !empty($params['returnrepost']);
		$this->needComment = !empty($params['returncomment']);
		$this->xCombined = !empty($params['overlayxcombined']);
		$this->columnStacking = !empty($params['overlaycolumnstacking']);
		$this->xzReverse = !empty($params['overlayxzreverse']);
		if(!$this->hasFormJson){
			if(!isset($params['instanceid'])){
				$this->error = "params error: need instanceid";
				return false;
			}
			$this->instanceID = $params['instanceid'];
		}
		else{
			if(isset($GLOBALS['HTTP_RAW_POST_DATA'])){
				//从页面提交的参数
				$r = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
				if(empty($r)){
					$this->error = "post data is empty";
					return false;
				}
				$this->overlays = $r;
			}
			else{
				$this->error = "is not post";
				return false;
			}
		}

		if($this->crossDomain){
			if(isset($params['offset'])){
				$this->dataOffset = $params['offset'];
			}
			if(isset($params['rows'])){
				$this->dataRows = $params['rows'];
			}
		}
		return true;
	}
	/* OverlayDataProcesser
	 * 从数据库获取数据参数
	 * */
	public function getDataParams(){
		if(!$this->hasFormJson){
			$this->elements = array();
			$inc = getelements($this->instanceID, 0, false);
			if(empty($inc) || empty($inc['elements'])){
				$this->error = "elements is empty. instanceid:{$this->instanceID}";
				return false;
			}
			else{
				$postdata = array();
				$oindexarr = array();
				$lobj = array();
				foreach($inc['elements'] as $key=>$value){
					if(!in_array($value['overlayindex'], $oindexarr)){
						$oindexarr[] = $value['overlayindex'];
						$lobj['datajson'] = array("instanceid"=>$this->instanceID, "elements"=>array(), "pinrelation"=>array(), "render"=>array("elementid"=>$value['elementid'], "datajson"=>$value['datajson']));
					}

					if($value['subInstanceType']== 1){
						$dobj = array();
						$dobj['elementid'] = $value['elementid'];
						$dobj['instanceid'] = $this->instanceID;
						$dobj['instancetype'] = 1;
						if(isset($value['modelname'])){
							$dobj['modelname'] = $value['modelname'];
						}
						if(isset($value['referencedata'])){
							$dobj['referencedata'] = $value['referencedata'];
						}
						if(isset($value['secondaryyaxis'])){
							$dobj['secondaryyaxis'] = $value['secondaryyaxis'];
						}
						if(isset($value['showid'])){
							$dobj['showid'] = $value['showid'];
						}
						if(isset($value['linetype'])){
							$dobj['linetype'] = $value['linetype'];
						}
						if(isset($value['referencedataratio'])){
							$dobj['referencedataratio'] = $value['referencedataratio'];
						}
						$dobj['datajson'] = $value['datajson'];
						$postdata[]= $dobj;
					}
					else if($value['subInstanceType'] == 2){
						if($value['overlayindex'] == $oindexarr[count($oindexarr)-1]){ //同一组的
							$lobj['instancetype']= 2;

							$isstatic = true;
							foreach($inc['pinrelation'] as $ni=>$nitem){
								if($nitem['overlayindex'] == $value['overlayindex']){
									if($nitem['outputdata']['pintype'] == 'dynamic'){
										$isstatic = false;
										break;
									}
								}
							}
							//elements
							if(!$isstatic && $value['type'] != 2){
								$lobj['datajson']['elements'][] = array("elementid"=>$value['elementid'], "datajson"=>$value['datajson']);
							}
							//pinrelation
							foreach($inc['pinrelation'] as $pi=>$pitem){
								if($pitem['overlayindex'] == $value['overlayindex']){
									$lobj['datajson']['pinrelation'][] = $pitem;
								}
							}
							//render
							if($value['type'] == 2){
								$tmplobj = $lobj;
								if(isset($value['modelname'])){
									$tmplobj['modelname'] = $value['modelname'];
								}
								if(isset($value['referencedata'])){
									$tmplobj['referencedata'] = $value['referencedata'];
								}
								if(isset($value['secondaryyaxis'])){
									$tmplobj['secondaryyaxis'] = $value['secondaryyaxis'];
								}
								if(isset($value['showid'])){
									$tmplobj['showid'] = $value['showid'];
								}
                                if(isset($value['linetype'])){
                                    $dobj['linetype'] = $value['linetype'];
                                }
								if(isset($value['referencedataratio'])){
									$tmplobj['referencedataratio'] = $value['referencedataratio'];
								}
								$tmplobj['datajson']['render']['datajson'] = $value['datajson'];
								$postdata[] = $tmplobj; //render为一组的最后一个
							}
						}
					}
				}
				$this->overlays = $postdata;
			}
		}
		if($this->crossDomain && isset($this->dataOffset) && isset($this->dataRows)){
			$this->render['datajson']['output']['data_limit'] = $this->dataOffset;
			$this->render['datajson']['output']['count'] = $this->dataRows;
		}
		
		return $this->overlays;
	}
	/* OverlayDataProcesser
	 * */
	public function checkLimit($dataparams=NULL, $isdownload=false){
		global $logger;
		$result = true;
		foreach($this->overlays as $oi=>$oitem){
			if($oitem['instancetype'] == 1){
				$r = checkUserPure($this->instanceID,$oitem["elementid"], $oitem["datajson"], $isdownload, $this->crossDomain);
				if($r == VALIDATE_SUCCESS){
					continue;
				}
				else{
					$result = $r;
					break;
				}
			}
			else if($oitem['instancetype'] == 2){
				$renderdatajson = $oitem["datajson"]["render"]['datajson'];
				$result = checkUserPure($this->instanceID, $oitem["datajson"]["render"]['elementid'], $renderdatajson,$isdownload, $this->crossDomain);
				if($result == VALIDATE_SUCCESS && !$this->checkedLimit){
					foreach($oitem["datajson"]["elements"] as $key => $value){
						$r = checkUserPure($this->instanceID, $value['elementid'], $value['datajson'],$isdownload, false, $oitem["datajson"]["pinrelation"]);
						if($r != VALIDATE_SUCCESS){
							$result = $r;
							break;
						}
					}
					if($result == VALIDATE_SUCCESS){
						$this->checkedLimit = true;
					}
				}
			}
		}
		if($result == VALIDATE_SUCCESS){
			return true;
		}
		else{
			return $result;
		}
	}
	/* OverlayDataProcesser
	 * */
	public function getData($dataParams=NULL){
       global $logger; 
		if(!isset($dataparams)){
			$dataParams = $this->getDataParams();
		}
		if(!isset($dataparams)){
			$this->error = "dataparams is empty";
		}
		//需要根据请求的数据,判断使用ElementDataProcesser 或 LinkageDataProcesser获取数据
		$subprocesser = NULL;
		$this->outputData = array();
		foreach($dataParams as $oi=>$oitem){
			if($oitem['instancetype'] == 2){
				$subprocesser = createDataProcesser(PROCESSER_TYPE_LINKAGE);
				$oitem["datajson"]["render"]["returnoriginal"] = $this->needOrig;
				$oitem["datajson"]["render"]["returnrepost"] = $this->needRepost;
				$oitem["datajson"]["render"]["returncomment"] = $this->needComment;
			}
			else if($oitem['instancetype'] == 1){
				$subprocesser = createDataProcesser(PROCESSER_TYPE_ELEMENT);
			}
			$dr = $subprocesser->getData($oitem["datajson"]);
			if(!is_array($dr) || isset($dr['errorcode'])){
				$this->outputData = $dr;
				break;
			}
			//对返回的数据处理,添加
			foreach($dr as $di=>$ditem){
				if(isset($oitem["modelname"])){
					$ditem["categoryname"] = $oitem["modelname"];
				}
				if(isset($oitem["referencedataratio"])){
					if(isset($oitem["referencedata"]) && $oitem["referencedata"]){
						$ditem["referencedataratio"] = 1;
					}
					else{
						$ditem["referencedataratio"] = $oitem["referencedataratio"];
					}
				}
				if(isset($oitem["secondaryyaxis"])){
					$ditem["secondaryyaxis"] = $oitem["secondaryyaxis"];
				}
				if(isset($oitem["showid"])){
					$ditem["showid"] = $oitem["showid"];
				}
				if(isset($oitem["linetype"])){
					$ditem["linetype"] = $oitem["linetype"];
				}
                else if(isset($oitem['datajson']['linetype'])){
					$ditem["linetype"] = $oitem['datajson']["linetype"];
                }
				if($this->xCombined){
					$ditem["xcombined"] = $this->xCombined;
				}
				if($this->columnStacking){
					$ditem["columnstacking"] = $this->columnStacking;
				}
				if($this->xzReverse){
					$ditem["xzreverse"] = $this->xzReverse;
				}
				$this->outputData[] = $ditem;
			}
		}
		unset($subprocesser);
		return $this->outputData;
	}
	/* OverlayDataProcesser
	 * */
	public function formatData($data=NULL){
		if(!isset($data)){
			$data = $this->outputData;
		}
		if(!isset($data)){
			return "";
		}
		else{
			/*
			$retarr = array();
			foreach($data as $di=>$ditem){
				$retarr = array_merge($retarr, $ditem);
			}
			return $retarr;
			 */
			return $this->outputData;
		}
	}
	public function output($fmtdata=NULl){
		if(!isset($fmtdata)){
			$fmtdata = $this->formatData();
		}
		if($this->crossDomain && !empty($_GET['callback'])){//跨域访问接口, 并且是jsonp形式
			echo $_GET['callback']."(".json_encode($fmtdata).")";
		}
		else{
			echo json_encode($fmtdata);
		}
		exit;
	}
}

//download $global_timeoutsec = 0
/**
 *
 * 下载数据处理
 * @author Administrator
 *
 */
class DownloadDataProcesser extends DataProcesser {
	private $fileType;//文件类型 “excel”
	private $fileName;//文件名
	private $version;//版本“2003” “2007”
	//以elementid为属性名，值为对象：{instanceid,instancetype,shows:[{showid, showtitle,downloadDatacount,downloadFields}]}
	private $elements;
	private $needOrig;
	private $needRepost;
	private $needComment;
	private $outputData = array();
	private $sheets = array();//输出多少个sheet。对象数组{title,}
	//private $subElementProcesser;
	//private $subLinkageProcesser;
	private $fileProcesser;

	function __construct() {
		//$this->subElementProcesser = createDataProcesser(PROCESSER_TYPE_ELEMENT);
		//$this->subLinkageProcesser = createDataProcesser(PROCESSER_TYPE_LINKAGE);
	}

	public function checkAuth(){
		//判断session是否存在
		if(Authorization::checkUserSession() != CHECKSESSION_SUCCESS){
			$user = Authorization::checkUserSession();
			if(!empty($user)){
				if(empty($user['allowdownload'])){
					$this->error = "抱歉，您没有权限访问!";
					return false;
				}
			}
			else{
				$this->error = "未登录或登陆超时!";
				return false;
			}
		}
		if(!$_SESSION['user']->allowdownload){
			$this->error = "对不起您无权下载";
			return false;
		}
		return true;
	}

	/**
	 *
	 * 解析url参数
	 * @param unknown_type $params 传递$_REQUEST对象
	 */
	public function parseParams($params){
		if(empty($params)){
			$this->error = "params is empty";
			return false;
		}
		if(empty($params['elements'])){
			$this->error = "param: elements is empty";
			return false;
		}
		$this->needOrig = !empty($params['returnoriginal']);
		$this->needRepost = !empty($params['returnrepost']);
		$this->needComment = !empty($params['returncomment']);
		//$this->subElementProcesser->setneedOrig($this->needOrig);
		//$this->subLinkageProcesser->setneedOrig($this->needOrig);
		$this->fileType = empty($params['filetype']) ? "excel" : $params['filetype'];
		$this->version = empty($params['version']) ? "excel" : $params['version'];
		$this->elements = $params['elements'];
		$this->fileName = empty($params['filename']) ? "" : $params['filename'];
		return true;
	}

	public function getDataParams(){
		return true;
	}

	public function checkLimit($dataparams=NULL, $isdownload=true){
		return true;
	}

	/**
	 *
	 * 从数据库获取数据参数
	 */
	public function getData($dataParams=NULL){
		global $global_timeoutsec, $logger;
		if(empty($this->elements)){
			$this->error = "elements is empty";
			return false;
		}
		else{
			//key 为elementID
			//$this->log("info"," memory limit:".ini_get("memory_limit"));
			//$this->log("info"," before getdata memory".memory_get_usage());
			$overlaymodelarr = array();
			$sheetarr = array();
			foreach($this->elements as $key => $value){
				//计算最大count
				$outcount = 0;
				$selfields = array();
				foreach($value['shows'] as $sk => $sv){ //相同elements的不同show
					$outcount = max($outcount, $sv['downloadDatacount']);
					$selfields = array_merge($selfields, $sv['downloadFields']);//将所有show的显示字段合并
				}
				if($outcount == 0){
					$this->log("warn","instanceid:{$value['instanceid']}, elementid:{$key} outcount is 0");
					continue;
				}
				$subprocesser = NULL;
				if($value['instancetype'] == 2){ //联动模型
					$subprocesser = createDataProcesser(PROCESSER_TYPE_LINKAGE);
					$subprocesser->setelements(NULL);
					$subprocesser->setinstanceID($value['instanceid']);
					$subprocesser->setrender(array("elementid"=>$key, "datajson"=>NULL));
				}
				else if($value['instancetype'] == 1){//普通模型
					if(!isset($value['instanceid'])){
						$this->error = "instanceid is empty elementid:{$key}";
						return false;
					}
					$subprocesser = createDataProcesser(PROCESSER_TYPE_ELEMENT);
					$subprocesser->setdataJson(NULL);
					$subprocesser->setinstanceID($value['instanceid']);
					$subprocesser->setelementID($key);
				}
				$subprocesser->setneedOrig($this->needOrig);
				$subprocesser->setneedRepost($this->needRepost);
				$subprocesser->setneedComment($this->needComment);
				$subprocesser->setoutputData(NULL);//清空数据
				$dp = $subprocesser->getDataParams();//请求数据前获取参数
				if($dp === false){
					$this->log("warn", "instanceid:{$value['instanceid']}, elementid:{$key} not found. error:".$subprocesser->getError());
					continue;
				}

				$modelid;//模型ID
				$_json;
				if($value['instancetype'] == 2){
					$_json = &$dp['render']['datajson'];
				}
				else if($value['instancetype'] == 1){
					$_json = &$dp;
				}
				$_json['output']['count'] = $outcount;
				$modelid = $_json['modelid'];
				if($_json['output']['outputtype'] == OUTPUTTYPE_QUERY){//非facet模型，修改select
					foreach($selfields as $si => $sv){
						if($sv == "number"){
							unset($selfields[$si]);
							break;
						}
					}
					$selfields = array_unique($selfields);//去重
					$_json['select']['value'] = $selfields; //同时修改了 $dp
				}
				else if($_json['output']['outputtype'] == OUTPUTTYPE_FACET){
					if($modelid == 2){
						if(count($_json['facet']['field']) > 0){
							$_json['facet']['field'][0]['allusercount'] = true;
						}
						if(count($_json['facet']['range']) > 0){
							$_json['facet']['range'][0]['allusercount'] = true;
						}
					}
					else{
						if(count($_json['facet']['field']) > 0){
							$_json['facet']['field'][0]['allcount'] = true;
						}
						if(count($_json['facet']['range']) > 0){
							$_json['facet']['range'][0]['allcount'] = true;
						}
					}
				}
				//下载时,json中的select字段为需要下载的字段
				$r = $subprocesser->checkLimit($dp, true);
				if($r !== true){
					$this->log("warn", "instanceid:{$value['instanceid']}, elementid:{$key} out of limit:".var_export($r,true));
					continue;
				}
				$hasweibourl = false;
				foreach($_json['select']['value'] as $_fk => $_fv){
					if($_fv == 'weibourl'){
						unset($_json['select']['value'][$_fk]);
						$hasweibourl = true;
						break;
					}
				}
				if($hasweibourl){//生成微博地址需要userid
					if(!in_array("userid", $_json['select']['value'])){
						$_json['select']['value'][] = "userid";
					}
					if(!in_array("mid", $_json['select']['value'])){
						$_json['select']['value'][] = "mid";
					}
					if(!in_array("sourceid", $_json['select']['value'])){
						$_json['select']['value'][] = "sourceid";
					}
				}
				$hasuserurl = false;
				foreach($_json['select']['value'] as $_fk => $_fv){
					if($_fv == 'userurl'){
						unset($_json['select']['value'][$_fk]);
						$hasuserurl = true;
						break;
					}
				}
				if($hasuserurl){
					if($modelid == 51 && !in_array("userid",$_json['select']['value'])){
						$_json['select']['value'][] = "userid";
					}
					if(!in_array("sourceid", $_json['select']['value'])){
						$_json['select']['value'][] = "sourceid";
					}
					if($modelid == 1 && !in_array("users_id",$_json['select']['value'])){
						$_json['select']['value'][] = "users_id";
					}
					if($modelid == 1 && !in_array("users_sourceid", $_json['select']['value'])){
						$_json['select']['value'][] = "users_sourceid";
					}
				}
				$hasparagraphid = false;
				foreach($_json['select']['value'] as $_fk=>$_fv){
					if($_fv == 'paragraphid'){
						unset($_json['select']['value'][$_fk]);
						$hasparagraphid = true;
						break;
					}
				}
				if($hasparagraphid){
					if(!in_array("guid",$_json['select']['value'])){
						$_json['select']['value'][] = "guid";
					}
					if(!in_array("source_host",$_json['select']['value'])){
						$_json['select']['value'][] = "source_host";
					}
					if(!in_array("original_url",$_json['select']['value'])){
						$_json['select']['value'][] = "original_url";
					}
					if(!in_array("floor",$_json['select']['value'])){
						$_json['select']['value'][] = "floor";
					}
					if(!in_array("docguid",$_json['select']['value'])){
						$_json['select']['value'][] = "docguid";
					}
					if(!in_array("paragraphid",$_json['select']['value'])){
						$_json['select']['value'][] = "paragraphid";
					}

				}
				//为了兼容旧数据中企业认证为1的情况（新值为3），需要判断verified_type
				if($modelid == 51 && in_array("verify", $_json['select']['value'])){
					if(!in_array('verified_type',$_json['select']['value'])){
						$_json['select']['value'][] = "verified_type";
					}
				}

                //2016-8-31 Bert 下载时在$dp中做一个标记 
                $dp['isdownload'] = 1;
				$dr = $subprocesser->getData($dp);
				if(!$dr){
					$this->error = "instanceid:{$value['instanceid']}, elementid:{$key} data is empty";
					$this->log("error", $this->error);
					return false;
				}
				else if(isset($dr['error'])){
					$this->error = "instanceid:{$value['instanceid']}, elementid:{$key} data error:".$dr['error'];
					$this->log("error", $this->error);
					return false;
				}
				else{
					foreach($value['shows'] as $sk => $sv){
						if(!in_array($sv['showindex'], $overlaymodelarr)){
							$overlaymodelarr[] = $sv['showindex'];
							$sheetarr[$sv['showindex']] = array();
							$sheetarr[$sv['showindex']]['title'] = $sv['showtitle'];
							$sheetarr[$sv['showindex']]['overlaymodel'] = array();
						}
						$sheetmodel = array();
						$sheetmodel['modelid'] = $modelid;
						$sheetmodel['count'] = $sv['downloadDatacount'];
						if(!empty($value['modelname'])){
							$sheetmodel['modelname'] = $value['modelname'];
						}
						$sheetmodel['fields'] = $sv['downloadFields'];
						$sheetmodel['timerange'] = (count($_json['facet']['range']) > 0) && ($_json['facet']['range'][0]['name'] == "created_at" || $_json['facet']['range'][0]['name'] == "register_time");
						if($_json['output']['outputtype'] == OUTPUTTYPE_FACET){
							if(count($_json['facet']['field']) > 0){
								$sheetmodel['facetname'] = $_json['facet']['field'][0]['name'];
							}
							else if(count($_json['facet']['range']) > 0){
								$sheetmodel['facetname'] = $_json['facet']['range'][0]['name'];
							}
						}
						$sheetmodel['data'] = $dr;
						$sheetarr[$sv['showindex']]['overlaymodel'][] = $sheetmodel;
					}
				}
			}
			foreach($sheetarr as $si=>$sitem){
				$this->outputData[] = $sitem;
			}
			//$this->log("info"," after getdata memory".memory_get_usage());
			unset($subprocesser);
			return $this->outputData;
		}
	}

	public function formatData($data=NULL){
		try{
			$this->fileProcesser = createFileProcesser($this->fileType);//文件处理
			if(!empty($this->outputData)){
				$model_fields_hash = array();//以modelid为一级key，字段名为二级key，值为字段label
				foreach($this->outputData as $i => $value){
					foreach($value['overlaymodel'] as $oi=>$oitem){
						//没有对应的
						if(!isset($model_fields_hash[''.$oitem['modelid']])){
							$model = getModelByID($oitem["modelid"]);
							if(empty($model)){
								$this->log("error","not found Model object. modelid:{$oitem['modelid']}");
								continue;
							}
							if(empty($model->datajson->download_FieldLimit)){
								$this->log("error","Model {$oitem['modelid']} download_FieldLimit is empty");
								continue;
							}
							$model_fields_hash[''.$oitem['modelid']] = array();
							foreach($model->datajson->download_FieldLimit as $dfi => $dfv){
								$model_fields_hash[''.$oitem['modelid']][$dfv['value']] = $dfv['text'];//以字段名为key，text为值
							}
						}
						$value['overlaymodel'][$oi]['fieldtexts'] = array();//生成表头
						foreach($oitem['fields'] as $fi => $fv){
							if(isset($oitem['facetname']) && $fv == "facet"){//如果是facet查询，转换字段名
								$fieldlabel = "";
								if(isset($model->datajson->filter[$oitem['facetname']])){
									$fieldlabel = $model->datajson->filter[$oitem['facetname']]["label"];
								}
								else{
									$fieldlabel = getDisplayName($oitem['facetname']);
								}
								$value['overlaymodel'][$oi]['fieldtexts'][] = $fieldlabel;
							}
							else{
								$value['overlaymodel'][$oi]['fieldtexts'][] = $model_fields_hash[''.$oitem['modelid']][$fv];//按字段名在hash中取text
							}
						}
					}
					//$this->log("info","{$i} before append memory".memory_get_usage());
					$appendr = $this->fileProcesser->append($value);//添加数据到文件
					if($appendr !== true){
						$this->error = $appendr;
						return false;
					}
					//$this->log("info","{$i} after append memory".memory_get_usage());
				}
			}
			return true;
		}
		catch (Exception $ex){
			$this->error = $ex->getMessage();
			return false;
		}
	}

	public function output($fmtdata=NULL){
		try{
			$this->fileProcesser->output($this->version, $this->fileName);
		}
		catch (Exception $ex){
			$this->error = $ex->getMessage();
			return false;
		}
	}
}

$global_fieldwidth = array();
$global_fieldwidth['text'] = 30;
$global_fieldwidth['description'] = 20;
$global_fieldwidth['verified_reason'] = 20;
//需要处理特殊字符的
$global_fieldconvert['text'] = 1;
$global_fieldconvert['description'] = 1;
$global_fieldconvert['verified_reason'] = 1;
$global_fieldconvert['screen_name'] = 1;
$global_fieldconvert['source'] = 1;
$global_fieldconvert['users_description'] = 1;
$global_fieldconvert['users_verified_reason'] = 1;
$global_fieldconvert['users_screen_name'] = 1;

class ExcelProcesser{
	public $objPHPExcel;
	private $sheetindex = -1;//当前sheet

	function __construct() {
		global $logger;
		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
		$r = PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
		//$logger->info("setCacheStorageMethod:".($r ? "true" : "false"));
		$this->objPHPExcel = new PHPExcel();
		$this->objPHPExcel->getDefaultStyle()->getFont()->setName( 'Arial');
		$this->objPHPExcel->getDefaultStyle()->getFont()->setSize(12);
	}

	/**
	 * 获取字段的的值
	 * @param Array $datalist 数据集
	 * @param int $index 第几条
	 * @param string $fieldname 字段名
	 * @param int $modelid 模型ID
	 */
	public function getFieldvalue($datalist, $index, $fieldname, $modelid){
		$value = "";
		$ov = !isset($datalist[$index][$fieldname]) ? "" : $datalist[$index][$fieldname];
		if(is_array($ov)){
			$ov = !isset($ov[0]) ? "" : implode(",", $ov);
		}
		switch ($fieldname){
			case "number":
				$value = $index+1;//索引序号
				break;
			case "users_gender":
			case "gender":
			case "sex":
				if($ov == "f"){
					$value = "女";
				}
				else if($ov == "m"){
					$value = "男";
				}
				break;
			case "users_verified":
			case "verify":
			case "verified":
				if($ov == 1){
					$value ="个人认证";
					if((isset($datalist[$index]['verified_type']) && $datalist[$index]['verified_type'] != 0)
					    || (isset($datalist[$index]['users_verified_type']) && $datalist[$index]['users_verified_type'] != 0)){
						$value="企业机构";
					}
				}
				else if($ov == 0){
					$value = "非认证";
				}
				else if($ov == 2){
					$value = "达人";
				}
				else if($ov == 3){
					$value="企业机构";
				}
				break;
			case "users_verified_type":
			case "verified_type":
				if($ov != ""){
					$value = getverifiedtypealias($ov);
				}
				break;
			case "users_sourceid":
			case "sourceid":
				if($ov){
					$value = get_source_name($ov);
				}
				break;
			case "users_source_host":
			case "source_host":
				if($ov){
					$sn = getSourcenameFromHost(array($ov)); //数据来源
					$value = $sn[0]['name'];
				}
				break;
			case "users_created_at":
			case "created_at":
			case "register_time":
                if(is_numeric($ov)){
                    $value = date("Y年n月j日 G时i分s秒", $ov);
                }
				break;
			case "facet":
				if(isset($datalist[$index]['alias'])){
					$value = $datalist[$index]['alias'];
				}
				else if(isset($datalist[$index]['text'])){
					$value = $datalist[$index]['text'];
				}
				else if(isset($datalist[$index]['range'])){
					$value = date("Y年n月j日 G时i分s秒", $datalist[$index]['range']);
					if(isset($datalist[$index]['rangeend'])){
						$value .= " ~ ".date("Y年n月j日 G时i分s秒", $datalist[$index]['rangeend']);
					}
				}
				break;
			case "weibourl":
				if(!empty($datalist[$index]['mid']) && isset($datalist[$index]['userid']) && isset($datalist[$index]['sourceid'])
				&& $datalist[$index]['mid'] != "null"){//旧数据中mid有字符串null
					$value = weibomid2Url($datalist[$index]['userid'], $datalist[$index]['mid'], $datalist[$index]['sourceid']);
				}
				else{
					$value = "";
				}
				break;
			case "userurl":
				if($modelid == 51 && isset($datalist[$index]['userid']) && isset($datalist[$index]['sourceid'])){
					$value = userid2Url($datalist[$index]['userid'], $datalist[$index]['sourceid']);
				}
				else if($modelid == 1 && isset($datalist[$index]['users_id']) && isset($datalist[$index]['users_sourceid'])){
					$value = userid2Url($datalist[$index]['users_id'], $datalist[$index]['users_sourceid']);
				}
				break;
			case "reposts_count":
			case "comments_count":
				$value = $ov == "" ? 0 : $ov;
				break;
			default:
				$value = $ov;
				break;
		}
		return $value;
	}

	/**
	 * 添加数据，每调用一次增加一个sheet
	 *
	 */
	public function append($data){
		global $global_fieldwidth, $global_fieldconvert, $logger;
		$this->sheetindex++;
		$exsheetcount = $this->objPHPExcel->getSheetCount();
		//$logger->info(__FUNCTION__." before set sheet:".memory_get_usage()." real:".memory_get_usage(true));
		if($this->sheetindex >= $exsheetcount){
			$actsheet = $this->objPHPExcel->createSheet($this->sheetindex);
		}
		else{
			$actsheet = $this->objPHPExcel->setActiveSheetIndex($this->sheetindex);
		}
		//$logger->info(__FUNCTION__." before set title:".memory_get_usage()." real:".memory_get_usage(true));
		if(!empty($data['title'])){
			$chkmem = checkMemory();
			if(!$chkmem){
				return "memory out of limit";
			}
			if(mb_strlen($data['title'],"utf8") > 31){
				$title = mb_substr($data['title'],0,27,"utf8")."...";
			}
			else{
				$title = $data['title'];
			}
			if(!empty($title)){
				$title = iconv("UTF-8", "GBK//IGNORE",$title);//忽略非法字符
				$title = iconv("GBK", "UTF-8",$title);//转回utf8
				$title = $this->formatSheetTitle($title);
				$actsheet->setTitle($title);
			}
		}
		$celli = 0;
		$rowi = 1;
		foreach($data['overlaymodel'] as $oi=>$oitem){
			if(!empty($oitem['modelname'])){
				$actsheet->setCellValueByColumnAndRow($celli,$rowi, $oitem['modelname']);
				$actsheet->mergeCellsByColumnAndRow(0, $rowi, count($oitem['fields']), $rowi);//合并单元格
				$rowi++;
			}
			//$logger->info(__FUNCTION__." before set head:".memory_get_usage()." real:".memory_get_usage(true));
			foreach($oitem['fieldtexts'] as $headi => $headv){
				$chkmem = checkMemory();
				if(!$chkmem){
					return "memory out of limit";
				}
				$actsheet->setCellValueByColumnAndRow($headi, $rowi, $headv);//设置第一行为表头
				$cellstyle = $actsheet->getStyleByColumnAndRow($headi, $rowi);//单元格样式
				$cellstyle->getFont()->setBold(true);//加粗
				$col = $actsheet->getColumnDimensionByColumn($headi);
				$col->setWidth(mb_strlen($headv));//按列名字长度设置列宽
			}
			$rowi++;//下一行开始写数据
			if(count($oitem['data']) > 0){//有多组数据，加categoryname
				$isgroup = true;
			}
			//$logger->info(__FUNCTION__." before set data:".memory_get_usage()." real:".memory_get_usage(true));
			//循环分组
			for($i=0; $i<count($oitem['data']); $i++){
				if($isgroup && isset($oitem['data'][$i]['categoryname']) && $oitem['data'][$i]['categoryname'] != ""){//创建分组名
					$actsheet->setCellValueByColumnAndRow($celli,$rowi, $oitem['data'][$i]['categoryname']);
					$actsheet->mergeCellsByColumnAndRow(0, $rowi, count($oitem['fields']), $rowi);//合并单元格
					$rowi++;
				}
				unset($datalist);
				$datalist = $oitem['data'][$i]['datalist'];//数据
				for($j=0; $j<count($datalist); $j++){
					foreach($oitem['fields'] as $fk => $fv){//循环所有数据列
						$chkmem = checkMemory();
						if(!$chkmem){
							return "memory out of limit";
						}
						unset($realvalue);
						$realvalue = $this->getFieldvalue($datalist, $j, $fv, $oitem['modelid']);
                        //$logger->debug(__FILE__.__LINE__." realvalue ".var_export($realvalue, true)." j ".var_export($j, true)." fv ".var_export($fv, true));
						//string类型的字段
						if(isset($global_fieldconvert[$fv]) && !empty($realvalue)){
							//$start_time = microtime_float();
							$realvalue = iconv("UTF-8", "UCS-2//IGNORE",$realvalue);//忽略非法字符
							$realvalue = iconv("UCS-2", "UTF-8",$realvalue);//转回utf8
						/*$firsstr = mb_substr($realvalue, 0, 1, "UTF-8");
						if($firsstr == "=" || $firsstr == "-" || $firsstr == "+"){//字符串中首字符为=-+，excel中会认为是公式，增加单引号
						$realvalue = "'".$realvalue;
						}*/
							//$end_time = microtime_float();
							//$timediff += ($end_time - $start_time);
							$actsheet->setCellValueExplicitByColumnAndRow($fk, $rowi, $realvalue);//默认STRING类型
						}
						else{
							$actsheet->setCellValueByColumnAndRow($fk, $rowi, " ".$realvalue);
						}
					/*$start_time = microtime_float();
					 $actsheet->getStyleByColumnAndRow($fk,$rowi)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
					 $end_time = microtime_float();
					$timediff_setFormatCode += ($end_time - $start_time);*/
						//$start_time = microtime_float();
						$col = $actsheet->getColumnDimensionByColumn($fk);
						$cellwidth = mb_strlen($realvalue) + 1;
						if(isset($global_fieldwidth[$fv])){ //如果设置了最大宽度
							$cellwidth = min($cellwidth, $global_fieldwidth[$fv]);
						}
						if($cellwidth > $col->getWidth()){
							$col->setWidth($cellwidth);
						}
						//$end_time = microtime_float();
						//$timediff_setWidth += ($end_time - $start_time);
					}
					$rowi++;
				}
			}
		}

		return true;
		//$logger->debug("iconv time:{$timediff}, setcellvalue:{$timediff_setcellvalue}, setFormatCode:{$timediff_setFormatCode}, setwidth:{$timediff_setWidth}");
	}

	public function output($version="2003", $filename=NULL){
		if(empty($filename)){
			$filename = time();
		}
		else{
			$useragent = $_SERVER['HTTP_USER_AGENT'];
			if(preg_match('/MSIE/', $useragent)){
				$filename = rawurlencode($filename);
			}
		}
		if($version == "2003"){
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
		}
		else {
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory:: createWriter($this->objPHPExcel, 'Excel2007');
		}
		$objWriter->save('php://output');
		exit;
	}

	public function formatSheetTitle($title){
		$preg = array(":","\\","/","?","*","[","]","|","：","＼","／","？","＊","［","］","｜");
		$str = str_replace($preg, "-", $title);
		//$str = preg_replace("/[\:\\\\\/\?\*\[\]\|]/", ' ', $title);
		return $str;
	}
}
/*$strmap = array(
 '/à|á|å|â|ä/' => 'a',
 '/è|é|ê|ẽ|ë/' => 'e',
 '/ì|í|î/' => 'i',
 '/ò|ó|ô|ø/' => 'o',
 '/ù|ú|ů|û/' => 'u',
 '/ç|č/' => 'c',
 '/ñ|ň/' => 'n',
 '/ľ/' => 'l',
 '/ý/' => 'y',
 '/ť/' => 't',
 '/ž/' => 'z',
 '/š/' => 's',
 '/æ/' => 'ae',
 '/ö/' => 'oe',
 '/ü/' => 'ue',
 '/Ä/' => 'Ae',
 '/Ü/' => 'Ue',
 '/Ö/' => 'Oe',
 '/ß/' => 'ss',
 '/😱/'=>' ');
 function FilterPartialUTF8Char($str)
 {
 $str = preg_replace('/[\xC0-\xDF](?=[\x00-\x7F\xC0-\xDF\xE0-\xEF\xF0-\xF7]|$)/', "", $str);
 $str = preg_replace('/[\xE0-\xEF][\x80-\xBF]{0,1}(?=[\x00-\x7F\xC0-\xDF\xE0-\xEF\xF0-\xF7]|$)/', "", $str);
 $str = preg_replace('/[\xF0-\xF7][\x80-\xBF]{0,2}(?=[\x00-\x7F\xC0-\xDF\xE0-\xEF\xF0-\xF7]|$)/', "", $str);
 return $str;
 }

 function safeEncoding($string,$outEncoding ='UTF-8')
 {
 $encoding = "UTF-8";
 for($i=0;$i<strlen($string);$i++)
 {
 if(ord($string{$i})<128)
 continue;

 if((ord($string{$i})&224)==224)
 {
 //第一个字节判断通过
 $char = $string{++$i};
 if((ord($char)&128)==128)
 {
 //第二个字节判断通过
 $char = $string{++$i};
 if((ord($char)&128)==128)
 {
 $encoding = "UTF-8";
 break;
 }
 }
 }

 if((ord($string{$i})&192)==192)
 {
 //第一个字节判断通过
 $char = $string{++$i};
 if((ord($char)&128)==128)
 {
 //第二个字节判断通过
 $encoding = "GB2312";
 break;
 }
 }
 }

 if(strtoupper($encoding) == strtoupper($outEncoding))
 return $string;
 else
 return iconv($encoding,$outEncoding,$string);
 }*/
?>
