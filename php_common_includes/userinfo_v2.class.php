<?php
//用户信息类，创建session时需要创建此类的对象
class UserInfo{
	
	private $userid;
	private $binduserid;
	public $usertype;
	public $alloweditinfo;
	public $tenantid;
	public $localtype;
	public $weburl;
	public $allowlinkage;
	public $allowdrilldown;
	public $allowdownload;//是否允许下载excel
	public $allowupdatesnapshot;//是否定时更新快照
	public $alloweventalert;//是否事件预警
	public $allowwidget;//是否允许使用widget
	public $allowaccessdata;//是否允许访问数据接口
	public $allowvirtualdata;//是否允许虚拟数据源
	public $allowoverlay;//是否允许叠加分析
	public $userexpiretime;
	public $accessdatalimit;//数据接口数据条数限制
	public $roles;
    public $systemResource;//系统资源
    public $tenantResource;//租户资源
    public $systemResourceChildren;//子资源，关联数组，key为父资源ID，value为子资源ID数组
    private $downloadinfo;//用于下载excel时，存储参数
    private $snapshotinfo;//用于下载excel时，存储参数
    
	function __construct($userid,$tenantid,$localtype,$seccode, $allowlinkage, $allowdrilldown, $binduserid, $usertype, $alloweditinfo){
		
		$this->userid = $userid;
		$this->tenantid = $tenantid;
        $this->weburl = $seccode;
        $this->localtype = $localtype;
        $this->allowlinkage= $allowlinkage;
        $this->allowdrilldown= $allowdrilldown;
        $this->allowdownload= false;
        $this->allowupdatesnapshot= false;
        $this->alloweventalert= false;
        $this->allowoverlay = true;
        $this->allowvirtualdata = false;
        $this->allowwidget= false;
        $this->allowaccessdata = false;
        $this->accessdatalimit = 0;
        $this->userexpiretime = NULL; //用户过期时间
		$this->binduserid = $binduserid;
		$this->usertype = $usertype;
		$this->alloweditinfo = $alloweditinfo;
        $this->roles = array();//存储角色id
		$this->systemResource = array();
		$this->tenantResource = array();
		$this->systemResourceChildren = array();
		$this->downloadinfo = array();
		$this->snapshotinfo = array();
	}
	function getuserid(){
		if($this->usertype == 2){
			return $this->binduserid;
		}
		else{
			return $this->userid;
		}
	}
	
	public function setDownloadInfo($key, $value){
		$this->downloadinfo[$key] = $value;
	}
	
	public function getDownloadInfo($key){
		return $this->downloadinfo[$key];
	}

	public function setSnapshotInfo($key, $value){
		$this->snapshotinfo[$key] = $value;
	}

	public function getSnapshotInfo($key){
		if(isset($this->snapshotinfo[$key])){
			return $this->snapshotinfo[$key];
		}
		else{
			return false;
		}
	}
}
?>
