<?php
//数据库变量

//modified by wang_cc:change to defined a constant array to support multiple databases
//define('DATABASE_NAME', '3a');    //数据库名称
//define('DATABASE_WEIBOINFO', 'weibo_info_2');    //数据库名称
//modified by wang_cc:change to defined a constant array to support multiple databases --end


//数据库用到的表
define('DATABASE_ANNOTATIONS', 'annotations');    //附加信息表
define('DATABASE_COMMENT', 'comment_new');    //评论表
define('DATABASE_CONFIG', 'config');    //配置信息表
define('DATABASE_REPOST', 'repost');    //转发表
define('DATABASE_TAGINFO', 'taginfo');    //标签表
define('DATABASE_TESTUSER', 'test_user');    //微博应用程序对应的测试用户表
define('DATABASE_TRENDS', 'trends');    //话题表
define('DATABASE_USER', 'user_new');    //微博用户表
define('DATABASE_USER_FOLLOWERS', 'user_followers');    //用户关注关系列表
define('DATABASE_USER_KEYWORDS', 'user_keywords');    //关键词列表
define('DATABASE_WEIBO', 'weibo_new');    //微博列表
define('DATABASE_TERM_KEYWORDS', 'term_keywords');    //关键词表
define('DATABASE_TERM_DOC', 'term_doc');    //term的位置信息表
define('DATABASE_SYNONYMY_BUSINESS', 'synonymy_business');    //行业同义词表
define('DATABASE_RELATE_BUSINESS', 'relate_business');    //行业相关表
define('DATABASE_BUSINESS', 'business');    //行业表
define('DATABASE_KEYWORD_BUSINESS', 'keyword_business');    //关键词与行业关系表
define('DATABASE_AREA', 'area');    //区域代码表
define('DATABASE_DICTAREA', 'dict_area'); //第三方区域代码与标准代码对应关系
define('DATABASE_EXPRESSION', 'expression');    //表情表
define('DATABASE_STATUS_EXPRESSION_KEYWORD', 'status_expression_keyword');    //微博表情关键字表
define('DATABASE_BRIDGE_CASE', 'bridge_case');    //桥接案例表
define('DATABASE_BRIDGE_CASE_ID', 'bridge_case_id');    //桥接案例序号表
define('DATABASE_SEMANTIC_DICTIONARY', 'semantic_dictionary');    //2014-7-7语义字典表
define('DATABASE_DICTIONARY', 'dictionary');    //字典表
define('DATABASE_DICTIONARY_CATEGORY', 'dictionary_category');    //字典表
define('DATABASE_SOURCE', 'source');    //资源表
define('DATABASE_SOURCEURL', 'sourceurl');    //资源对应主机表
define('DATABASE_USERS', 'users');    //用户表
define('DATABASE_TENANT', 'tenant');    //租户表
define('DATABASE_ROLE', 'role');    //角色表
define('DATABASE_USER_ROLE_MAPPIMG', 'user_role_mapping');    //用户表
define('DATABASE_ROLE_RESOURCE_RELATION', 'role_resource_relation');    //用户表
define('DATABASE_RESOURCE', 'resource');    //资源表
define('DATABASE_RESOURCE_GROUP', 'resource_group');    //资源组信息表
define('DATABASE_ACCOUNTING_STATISTICS', 'accounting_statistics');    //资源统计表
define('DATABASE_ACCOUNTING_RULE','accounting_rule');    //租户资源规则表
define('DATABASE_TEMPLATE','template');    //模板信息表
define('DATABASE_TENANT_TAGINFO','tenant_taginfo');    //用户模板信息表
define('DATABASE_TENANT_TAGINSTANCT','tenant_taginstanct');    //用户标签实例表
define('DATABASE_TENANT_TAGSOURCE','tagsource');    //标签信息表
define('DATABASE_PAGE_DESCRIPTION','page_description');    //页面描述信息表
define('DATABASE_CUSTOMER_NAVIGATE','customer_navigate');    //用户导航信息表
define('DATABASE_TENANT_ROLE_RESOURCE','tenant_role_resource_relation');    //用户导航信息表
define('DATABASE_PRODUCTS','products');    //产品信息表
define('DATABASE_SYSTEM_RESOURCE','system_resource');    //系统资源表
define('DATABASE_TENANT_MANAGE_RESOURCE','tenant_manage_resource');    //租户管理资源表
define('DATABASE_TENANT_RESOURCE','tenant_resource');    //租户资源表
define('DATABASE_PRODUCT_RESOURCE','product_resource_relation');    //产品资源关系表
define('DATABASE_BILLRULEMODEL','billrulemodel');//计费模型表
define('DATABASE_ELEMENT','element');//元素信息表
define('DATABASE_PINRELATION','pinrelation');//联动关系表
define('DATABASE_TENANT_RESOURCE_RELATION','tenant_resource_relation');    //租户资源关系表
define('DATABASE_URL_DICT','url_dict');//短url字典
define('DATABASE_SNAPSHOT','snapshot');//快照表
define('DATABASE_SPIDERCONFIG','spiderconfig');//爬虫配置表
define('DATABASE_REPOSTINFO', 'repostinfo');    //转发表
define('DATABASE_TASKHISTORY', 'taskhistory'); //历史任务表
define('DATABASE_TASK', 'task'); //历史任务表
define('DATABASE_TASKSCHEDULE', 'taskschedule'); //定时任务表
define('DATABASE_TASKSCHEDULEHISTORY', 'taskschedulehistory'); //定时任务历史表
define('DATABASE_DATAHOST', 'datahost'); //数据主机表
define('DATABASE_SNAPSHOT_HISTORY','snapshot_history');//快照历史表
define('DATABASE_EVENT_HISTORY','event_history');//事件历史表
define('DATABASE_LOGINHISTORY','loginhistory');//登录历史表
define('DATABASE_SPIDERACCOUNT', 'spideraccount'); //爬虫帐号表
define('DATABASE_TENANT_ROLE_MAPPING', 'tenant_role_mapping'); //租户角色表
?>
