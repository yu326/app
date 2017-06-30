<?php

if (isset($_SERVER['argc']) && $_SERVER['argc'] > 1) {
    $allParam = $_SERVER['argv'];
    //$_SERVER['hostName'] = $allParam[1];
    //设置全局变量 在config.php中统一
    $GLOBALS['hostName'] = $allParam[1];
} else {
    $logger->error(SELF . " - 未传递参数【machine】");
    exit;
}
include_once( 'includes.php' );
initLogger(LOGNAME_TEST);

$logger->info(__FUNCTION__.__FILE__.__LINE__." the selMysql is beginning");
$dsql = new DB_MYSQL(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_WEIBOINFO, FALSE);
$r = execute();
if ($r) {
    $logger->info(__FUNCTION__.__FILE__.__LINE__."正常结束了哦~");
}else{
    $logger->info(__FUNCTION__.__FILE__.__LINE__."任务有问题哦~");
}


function execute(){
    global $logger,$dsql;
    $result = true;

    $sql = "select * from weibo_update";
    $logger->info(__FUNCTION__.__FILE__.__LINE__." the sql is:".var_export($sql,true));
    $qrsel = $dsql->ExecQuery($sql);
    $rows = mysql_num_rows($qrsel);
    $logger->info(__FUNCTION__.__FILE__.__LINE__."the rows is:".var_export($rows,true));
    if($rows > 0){
        while ($dataResult = $dsql->GetArray($qrsel, MYSQL_ASSOC)) {
            $data[] = $dataResult;
        }
    }else{
        $result = false;
        $logger->info("{$sql} is null !!!");
    }
    $logger->info(__FILE__.__LINE__." the data is:".var_export($data,true));

    return $result;
}













?>