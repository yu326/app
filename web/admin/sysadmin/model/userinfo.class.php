<?php
//用户信息类，创建session时需要创建此类的对象
class UserInfo{
	
	public $userid;
	public $tenantid;
	public $localtype;
	public $weburl;
	public $roles;
	public $userexpiretime;
    public $systemResource;//系统资源
    public $tenantResource;//租户资源
    public $systemResourceChildren;//子资源，关联数组，key为父资源ID，value为子资源ID数组
    
	function __construct($userid,$tenantid,$localtype,$seccode){
		
		$this->userid = $userid;
		$this->tenantid = $tenantid;
        $this->weburl = $seccode;
        $this->localtype = $localtype;
		$this->userexpiretime = NULL;
        $this->roles = array();//存储角色id
		$this->systemResource = array();
		$this->tenantResource = array();
		$this->systemResourceChildren = array();
	}
	function getuserid(){
		return $this->userid;
	}

}
?>
