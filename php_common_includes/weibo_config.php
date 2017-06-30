<?php
//定义各个source的ID,需要给数据库source表保持一致
define('WEIBO_SINA', 1);
define('WEIBO_TENGXUN', 2);

//定义具体的SDK类的唯一标记
define('WEIBO_SINA_V1_CODE', 1);
define('WEIBO_SINA_V2_CODE', 2);
define('WEIBO_SINA_V3_CODE', 3);
define('WEIBO_TENGXUN_V1_CODE', 3);

define('SINA_SDK_VERSION',WEIBO_SINA_V2_CODE);//配置访问新浪微博时使用哪个版本的SDK
define('TENGXUN_SDK_VERSION',WEIBO_TENGXUN_V1_CODE);//配置访问腾讯微博时使用哪个版本的SDK

define( "WB_CALLBACK_URL" , 'https://api.weibo.com/oauth2/default.html' );

?>
