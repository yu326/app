<?php
//ʹ��jquery datatables ���ajax����ķ���ֵ�ṹ
class DatatableResult{
    var $sEcho;
    var $iTotalRecords;//�ܼ�¼��
    var $iTotalDisplayRecords;//���˺��ʵ����
    var $aaData;//���ݣ�ʹ�ù�������
    function __construct(){
        $aaData = array();
    }
}