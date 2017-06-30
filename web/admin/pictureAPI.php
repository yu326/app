<?php
/**
 * Created by PhpStorm.
 * User: koreyoshi
 * Date: 2016/11/30
 * Time: 16:06
 */

ini_set('include_path', realpath('../../php_config'));
require_once('config.php');
ini_set('include_path', realpath('../../php_common_includes'));
require_once('common.php');
require_once('database_config.php');
require_once('db_mysql.class.php');

initLogger(LOGNAME_DATAPUSH);
$dsql;


$logger->info("1");
if(empty($_POST)){
    $logger->info("2");
    $result['result'] = false;
    $result['msg'] = "the data is empty";
    echo json_encode($result);
    exit;
}
$sdata = $_POST;
//$sdata = json_decode($_POST,true);
$logger->info(__FUNCTION__." the sdata is:".var_export($sdata,true));
if(empty($sdata)){
    $logger->error(__FILE__.__LINE__." before json_decode ".$_POST);
    $logger->error(__FILE__.__LINE__." after json_decode ".var_export($sdata, true));
    $result['result'] = false;
    $result['msg'] = "josn error";
    echo json_encode($result);
    exit;
}
$logger->info("3");
if(isset($sdata['type'])){
    $type = isset($sdata['type']) ? $sdata['type'] : "addarticle";
    $data['image_path'] = isset($sdata['image_path']) ? $sdata['image_path'] : false;
    $data['real_url'] = isset($sdata['real_url']) ? $sdata['real_url'] : false;
    $data['page_url'] = isset($sdata['page_url']) ? $sdata['page_url'] : false;
    $data['image_bin'] = isset($sdata['image_bin']) ? $sdata['image_bin'] : false;
    $data['request_type'] = isset($sdata['request_type']) ? $sdata['request_type'] : false;
    $data['sourceid'] = isset($sdata['sourceid']) ? $sdata['sourceid'] : false;
}else{
    $result['result'] = false;
    $result['msg'] = "request type is not null";
    echo json_encode($result);die;
}
//echo $request_type."\r";
//die;
//$logger->info(__FUNCTION__."the request_type is:".var_export($request_type,true));
if ($type = "addPicture") {
    $logger->info("4");
    if($data['request_type'] == "0"){
        //抓取图片成功，信息入库  处理图片

        $logger->info("5");
        $res = addImage($data);
        $logger->info(__FUNCTION__." the res is:".var_export($res,true));
        if ($res) {
            $result['result'] = "ok";
            $result['msg'] = "add craw picture to mysql ok~";
            echo json_encode($result);die;
        }else{
            $result['result'] = "false";
            $result['msg'] = "add craw picture to mysql false~";
            echo json_encode($result);die;
        }


    }else if($data['request_type'] == "1"){
        //信息入库
        $logger->info("6");
        $res = addImage($data);
        $logger->info(__FUNCTION__." the res is:".var_export($res,true));
        if ($res) {
            $result['result'] = "ok";
            $result['msg'] = "add wrong picture to mysql ok~";
            echo json_encode($result);die;
        }else{
            $result['result'] = "false";
            $result['msg'] = "add picture to mysql false~";
            echo json_encode($result);die;
        }
    }else{
        $logger->info("7");
        $result['result'] = false;
        $result['msg'] = "request_type error";
        echo json_encode($result);
        die;
    }


    //处理图片
}







?>