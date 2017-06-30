<?php
//使用jquery datatables 插件ajax请求的返回值结构
class DatatableResult{
    var $sEcho;
    var $iTotalRecords;//总记录数
    var $iTotalDisplayRecords;//过滤后的实际数
    var $aaData;//数据，使用关联数组
    function __construct(){
        $aaData = array();
    }
}