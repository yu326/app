<?php
function thirdPartySendData($jsoninfo, $url) 
{
    if(!$url){
		echo 'opt url is null';
        return false;
    }
    $senddata = $jsoninfo;
    $timeout = 0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $senddata);

    $header_array = array('Content-type:application/json');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);

    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE );
    
    //$start_time = microtime_float();
    $response = curl_exec($ch);
    //$end_time = microtime_float();
    if($response === FALSE){
        $log_note = 'curl error is'.curl_error($ch);
		var_dump("log_note".$log_note);
        curl_close($ch);
        return false;
    }
    //关闭cURL资源，并且释放系统资源
    curl_close($ch);
    unset($senddata);
    return $response;
}
$url = "http://192.168.0.102:8081/handleDataAPI.php";
for($i=0;$i<3;$i++){
 $tmp = array (
    'floor' => $i,
    'paragraphid' => 0,
    'read_count' => 0,
    'recommended' => 0,
    'original_url' => 'http://bbs.autohome.com.cn/club-20413/thread-11-1.shtml',
    'page_url' => 'http://bbs.autohome.com.cn/club-20413/thread-11-1.shtml',
    'column' => '自定义某论坛这样的',
    'post_title' => 
    array (
      'content' => '白大S凯迪拉克浙江舟山 增加和rx350点点滴滴',
      'terms' => 
      array (
        0 => array ( 'end' => '2', 'start' => '0', 'text' => '白大',),
        1 => array ( 'end' => '7', 'start' => '3', 'text' => '凯迪拉克',),
        2 => array ( 'end' => '9', 'start' => '7', 'text' => '浙江',),
        3 => array ( 'end' => '11', 'start' => '9', 'text' => '舟山',),
        4 => array ( 'end' => '14', 'start' => '12', 'text' => '增加',),
        5 => array ( 'end' => '20', 'start' => '15', 'text' => 'rx350',),
      ),
    ),
    'created_at_ts' => 1331041052+(60*$i),
    'text' => 
    array (
      'content' => '先不废话，首先提供大家最近提到的“简配”，到底有几条呢？看下列对比列表！<BR/>1；前雾灯10月前为LED,10月后为卤素灯泡。<BR/>2；轮胎10月前为坦然者245-45-18，10月后为固特异235-50-18。',
      'terms' => 
      array (
        0 => array ( 'end' => '4', 'start' => '2', 'text' => '废话'),
        1 => array ( 'end' => '9', 'start' => '7', 'text' => '提供'),
        2 => array ( 'end' => '13', 'start' => '11', 'text' => '最近'),
        3 => array ( 'end' => '15', 'start' => '13', 'text' => '提到'),
        4 => array ( 'end' => '19', 'start' => '17', 'text' => '简配'),
        5 => array ( 'end' => '31', 'start' => '28', 'text' => '看下列'),
        6 => array ( 'end' => '33', 'start' => '31', 'text' => '对比'),
        7 => array ( 'end' => '35', 'start' => '33', 'text' => '列表'),
        8 => array ( 'end' => '42', 'start' => '40', 'text' => '前雾'),
        9 => array ( 'end' => '46', 'start' => '42', 'text' => '灯10月'),
        10 => array ( 'end' => '51', 'start' => '48', 'text' => 'led'),
        11 => array ( 'end' => '55', 'start' => '52', 'text' => '10月'),
        12 => array ( 'end' => '59', 'start' => '57', 'text' => '卤素'),
        13 => array ( 'end' => '61', 'start' => '59', 'text' => '灯泡'),
        14 => array ( 'end' => '68', 'start' => '66', 'text' => '轮胎'),
        15 => array ( 'end' => '71', 'start' => '68', 'text' => '10月'),
        16 => array ( 'end' => '75', 'start' => '73', 'text' => '坦然'),
        17 => array ( 'end' => '89', 'start' => '86', 'text' => '10月'),
        18 => array ( 'end' => '93', 'start' => '91', 'text' => '固特')
      ),
    ),
    'pg_text' => 
	array (
		0 => 
		array (
			'content' => '先不废话，首先提供大家最近提到的“简配”，到底有几条呢？看下列对比列表！',
			'terms' => 
			array ( 
				0 => array ( 'end' => '4', 'start' => '2', 'text' => '废话'), 
				1 => array ( 'end' => '9', 'start' => '7', 'text' => '提供'), 
				2 => array ( 'end' => '13', 'start' => '11', 'text' => '最近'), 
				3 => array ( 'end' => '15', 'start' => '13', 'text' => '提到'), 
				4 => array ( 'end' => '19', 'start' => '17', 'text' => '简配'), 
				5 => array ( 'end' => '31', 'start' => '28', 'text' => '看下列'), 
				6 => array ( 'end' => '33', 'start' => '31', 'text' => '对比'), 
				7 => array ( 'end' => '35', 'start' => '33', 'text' => '列表'),
			),
		),
		1 => 
		array (
			'content' => '1；前雾灯10月前为LED,10月后为卤素灯泡。',
			'terms' => 
			array (
				0 => array ( 'end' => '4', 'start' => '2', 'text' => '前雾'),
				1 => array ( 'end' => '8', 'start' => '4', 'text' => '灯10月'),
				2 => array ( 'end' => '13', 'start' => '10', 'text' => 'led'),
				3 => array ( 'end' => '17', 'start' => '14', 'text' => '10月'),
				4 => array ( 'end' => '21', 'start' => '19', 'text' => '卤素'),
				5 => array ( 'end' => '23', 'start' => '21', 'text' => '灯泡'),
			),
		),
		2 => 
		array (
			'content' => '2；轮胎10月前为坦然者245-45-18，10月后为固特异235-50-18。',
			'terms' => 
			array (
				0 => array ( 'end' => '4', 'start' => '2', 'text' => '轮胎'),
				1 => array ( 'end' => '7', 'start' => '4', 'text' => '10月'),
				2 => array ( 'end' => '11', 'start' => '9', 'text' => '坦然'),
				3 => array ( 'end' => '25', 'start' => '22', 'text' => '10月'),
				4 => array ( 'end' => '29', 'start' => '27', 'text' => '固特'),
			),
		) 
	),
  'source' => '未知应用来源',
    'geo_coordinates_x' => 0,
    'geo_coordinates_y' => 0,
    'annotations' => 0,
    'province' => '',
    'city' => '',
    'country' => '',
    'district' => '',
    'NRN' => '',
    'source_host' => 'bbs.autohome.com.cn',
    'url' => '',
    'organization' => '',
    'analysis_status' => 0,
    'repost_trend_cursor' => 1,
    'has_picture' => 0,
    'wb_topic' => '',
    'wb_topic_keyword' => '',
    'wb_topic_combinWord' => '',
	'user'=>array('id'=>2478882+(3*$i), 'source_host'=>'bbs.autohome.com.cn','screen_name'=>'当'.$i.'')
	/* 这次数据中不存在的字段
	'toped' => 0,
    'column1' => '',
	'id'=>
	'pg_text'=>
	'combinWord'=>
	'account'=>
	'thumbnail_pic'=>
	'bmiddle_pic'=>
	'original_pic'=>
	'retweeted_status'=>
	'geo_type'=>
	'reposts_count'=>
	'comments_count'=>
	'total_reposts_count'=>
	'direct_reposts_count'=>
	'ancestor_text'=>
ancestor_NRN
ancestor_district
ancestor_city
ancestor_province
ancestor_country
ancestor_combinWord
ancestor_organization
ancestor_wb_topic
ancestor_wb_topic_keyword
ancestor_wb_topic_combinWord
ancestor_url
ancestor_host_domain
ancestor_account
total_reach_count
direct_comments_count
praises_count
mid
retweeted_mid
retweeted_url
	 * */

	  /* 样例数据字段
    'guid' => 'saa.auto.sohu.com_aHR0cDovL3NhYS5hdXRvLnNvaHUuY29tL2NsdWItMTA0NDMvdGhyZWFkLXRwc3NveXFwejJweWZrYmFhYWEtMS5zaHRtbA==_8_0',
    'level' => '1',
    'paragraphid' => 0,

    'created_year' => '2012',
    'created_month' => '3',
    'created_day' => '6',
    'created_hour' => '21',
    'created_weekday' => '2',

    'originalText' => 
    array (
      0 => '3f73744c4b79d368f442e65508848689',
    ),
    'userid' => '2268442',
    'screen_name' => 'szqxlysr',
    'country_code' => 'CN',
    'province_code' => '440000',
    'city_code' => '440300',
    'register_time' => 1330963200,
    'verify' => 0,
    'verified_type' => -1,
    'userguid' => 'aS5hdXRvLnNvaHUuY29tu_2268442',
    'similar' => 
    array (
      0 => '93db33dfa14721fee51fad4886f835ce',
    ),
    'emoNRN' => '',
    'emoDistrict' => '',
    'emoCity' => '',
    'emoProvince' => '',
    'emoCountry' => '',
    'business' => '',
    'emoBusiness' => '',
    'combinWord' => '',
    'emoCombin' => '',
    'emoOrganization' => '',
    'emotion' => '',
    'emoTopic' => '',
    'emoTopicKeyword' => '',
    'emoTopicCombinWord' => '',
    'host_domain' => '',
    'account' => '',
    'emoAccount' => '',
	   */
);
 /*reply_father_floor 需有值 quote_father_floor只有引用时有值*/
 if($i!=0){
	 $tmp['reply_father_floor'] = 0;
 }
 if($i==3){
	 $tmp['quote_father_floor'] = 0;
	 $tmp['reply_father_floor'] = 0;
 }
	if($i==6){
		$tmp['quote_father_floor'] = 2;
	 $tmp['reply_father_floor'] = 2;
	}
 if($i==7){
		$tmp['quote_father_floor'] = 6;
	 $tmp['reply_father_floor'] = 6;
 }
 if($i==9){
		$tmp['quote_father_floor'] = 7;
	 $tmp['reply_father_floor'] = 7;
 }
	if($i==0){
		$tmp['content_type'] = 0;
	}
	else{
		$tmp['content_type'] = 2;
	}
	if($i==0){
		$tmp['comments_count'] = 20;
	}
	$jsonarr[] =$tmp;
}
//////////////////////微博数据
for($k=0;$k<1;$k++){
$tmp = array (
    'id' => '377572069165292'.($k*2).'',
    'text' => 
    array (
      'content' => '斯内德 范德法特 厄齐尔 迪马利亚 阿隆索 莫德里奇 赫迪拉 托尼克罗斯 皇马中场真心人才济济',
      'terms' => 
      array (
        0 => array ( 'end' => '3', 'start' => '0', 'text' => '斯内德',),
        1 => array ( 'end' => '8', 'start' => '4', 'text' => '范德法特',),
        2 => array ( 'end' => '12', 'start' => '9', 'text' => '厄齐尔',),
        3 => array ( 'end' => '17', 'start' => '13', 'text' => '迪马利亚',),
        4 => array ( 'end' => '21', 'start' => '18', 'text' => '阿隆索',),
        5 => array ( 'end' => '26', 'start' => '22', 'text' => '莫德里奇',),
        6 => array ( 'end' => '30', 'start' => '27', 'text' => '赫迪拉',),
        7 => array ( 'end' => '36', 'start' => '31', 'text' => '托尼克罗斯',),
        8 => array ( 'end' => '39', 'start' => '37', 'text' => '皇马',),
        9 => array ( 'end' => '41', 'start' => '39', 'text' => '中场',),
        10 => array ( 'end' => '43', 'start' => '41', 'text' => '真心',),
        11 => array ( 'end' => '45', 'start' => '43', 'text' => '人才',),
        12 => array ( 'end' => '47', 'start' => '45', 'text' => '济济',),
      ),
  ),    
  'created_at_ts' => 1331041052,
    'source' => 'iPhone 5s',
    'favorited' => false,
    'truncated' => false,
    'in_reply_to_status_id' => '',
    'in_reply_to_user_id' => '',
    'in_reply_to_screen_name' => '',
    'thumbnail_pic' => NULL,
    'bmiddle_pic' => NULL,
    'original_pic' => NULL,
    'user' => 
    array (
      'id' => 1233827051+(3*$k),
      'screen_name' => '我先溜了'.$k.'',
      'name' => '我先溜了',
      'province' => '11',
      'city' => '8',
      'location' => '北京 海淀区',
      'description' => 
      array (
        'content' => '人的野心是随着年龄在增长的',
        'terms' => 
        array (
			array( 'end'=> '4', 'start'=> '2', 'text'=> '野心'),
			array( 'end'=> '9', 'start'=> '7', 'text'=> '年龄'),
			array( 'end'=> '12', 'start'=> '10', 'text'=> '增长')
        ),
      ),
      'url' => '',
      'profile_image_url' => 'http://tp4.sinaimg.cn/1233827051/50/5700654675/1',
      'allow_all_act_msg' => false,
      'domain' => '',
      'geo_enabled' => true,
      'verified' => false,
      'gender' => 'm',
      'followers_count' => 243,
      'friends_count' => 397,
      'statuses_count' => 1291,
      'favourites_count' => 1,
  'created_at_ts' => 1331041052,
      'following' => false,
      'allow_all_comment' => true,
      'avatar_large' => 'http://tp4.sinaimg.cn/1233827051/180/5700654675/1',
      'verified_reason' => 
      array (
        'content' => '',
        'terms' => array (),
      ),
      'verified_type' => 220,
      'follow_me' => false,
      'online_status' => 0,
      'bi_followers_count' => 131,
      'page_url' => 'http://weibo.com/u/1233827051',
    ),
    'status' => 
    array (
  'created_at_ts' => 1331041052,
      'id' => 3775665335616101,
      'mid' => '3775665335616101',
      'idstr' => '3775665335616101',
      'text' => 
      array (
        'content' => '双十二惊曝史上最无良原创',
        'terms' => 
        array (
			array( 'end'=>'6', 'start'=>'0', 'text'=>'双十一惊曝史'),
			array( 'end'=>'10', 'start'=>'8', 'text'=>'无良'),
			array( 'end'=>'12', 'start'=>'10', 'text'=>'原创')
        ),
      ),
      'source' => '微博 weibo.com',
      'favorited' => false,
      'truncated' => false,
      'in_reply_to_status_id' => '',
      'in_reply_to_user_id' => '',
      'in_reply_to_screen_name' => '',
      'thumbnail_pic' => 'http://ww2.sinaimg.cn/thumbnail/6801f129gw1em6ua9bqkrj20hs1usn8z.jpg',
      'bmiddle_pic' => 'http://ww2.sinaimg.cn/bmiddle/6801f129gw1em6ua9bqkrj20hs1usn8z.jpg',
      'original_pic' => 'http://ww2.sinaimg.cn/large/6801f129gw1em6ua9bqkrj20hs1usn8z.jpg',
      'geo' => NULL,
      'user' => 
      array (
        'id' => 1744957737,
        'idstr' => '1744957737',
        'screen_name' => 'PPTV体育',
        'name' => 'PPTV体育',
        'province' => '31',
        'city' => '1000',
        'location' => '上海',
        'description' => 
        array (
          'content' => 'PPTV体育中心 http://sports.pptv.com',
          'terms' => 
		  array (
			  array( 'end'=> '4', 'start'=> '0', 'text'=> 'pptv'),
			  array( 'end'=> '6', 'start'=> '4', 'text'=> '体育'),
			  array( 'end'=> '8', 'start'=> '6', 'text'=> '中心')
		  ),
        ),
        'url' => 'http://sports.pptv.com/',
        'profile_image_url' => 'http://tp2.sinaimg.cn/1744957737/50/40066162174/1',
        'cover_image' => 'http://ww4.sinaimg.cn/crop.0.18.980.300/6801f129gw1elfexbn734j20r809b75l.jpg',
        'cover_image_phone' => 'http://ww4.sinaimg.cn/crop.0.0.640.640/7c85468fjw1e8yq7122ixj20hs0hsq3j.jpg',
        'profile_url' => 'pptvsports',
        'domain' => 'pptvsports',
        'weihao' => '',
        'gender' => 'm',
        'followers_count' => 95604,
        'friends_count' => 998,
        'pagefriends_count' => 1,
        'statuses_count' => 22923,
        'favourites_count' => 43,
  'created_at_ts' => 1331041052,
        'following' => false,
        'allow_all_act_msg' => true,
        'geo_enabled' => true,
        'verified' => true,
        'verified_type' => 5,
        'remark' => '',
        'ptype' => 0,
        'allow_all_comment' => true,
        'avatar_large' => 'http://tp2.sinaimg.cn/1744957737/180/40066162174/1',
        'avatar_hd' => 'http://ww3.sinaimg.cn/crop.0.0.179.179.1024/6801f129gw1ekmfjb0plqj20500503ym.jpg',
        'verified_reason' => 
        array (
          'content' => 'PPTV体育官方账号',
          'terms' => 
		  array ( 
			  array( 'end'=> '4', 'start'=> '0', 'text'=> 'pptv'),
			  array( 'end'=> '6', 'start'=> '4', 'text'=> '体育'),
			  array( 'end'=> '8', 'start'=> '6', 'text'=> '官方'),
			  array( 'end'=> '10', 'start'=> '8', 'text'=> '账号')
		  ),
        ),
        'bi_followers_count' => 565,
        'page_url' => 'http://weibo.com/u/1744957737',
      ),
      'reposts_count' => 1881,
      'comments_count' => 142,
      'attitudes_count' => 178,
      'source_host' => 'weibo.com',
      'page_url' => 'http://weibo.com/1744957737/BvKoRn8Zn',
    ),
    'geo' => NULL,
    'annotations' => NULL,
    'mid' => '3775720691652923',
    'reposts_count' => 0,
    'comments_count' => 0,
    'praises_count' => 0,
    'page_url' => 'http://weibo.com/1233827051/BvLQ96W03',
    'source_host' => 'weibo.com',
    'reply_comment' => 
    array (
      'created_at' => 'Thu Oct 02 11:23:11 +0800 2014',
      'id' => 3761186447330693,
      'text' => '@野鸽想去巴西',
      'source' => 'iPhone客户端',
      'user' => 
      array (
        'id' => 1806583694,
        'idstr' => '1806583694',
        'class' => 1,
        'screen_name' => 'flipped_July',
        'name' => 'flipped_July',
        'province' => '31',
        'city' => '16',
        'location' => '上海 金山区',
        'description' => '',
        'url' => '',
        'profile_image_url' => 'http://tp3.sinaimg.cn/1806583694/50/5708306036/0',
        'profile_url' => 'u/1806583694',
        'domain' => '',
        'weihao' => '',
        'gender' => 'f',
        'followers_count' => 54,
        'friends_count' => 61,
        'pagefriends_count' => 0,
        'statuses_count' => 129,
        'favourites_count' => 10,
        'created_at' => 'Sat Sep 04 13:51:47 +0800 2010',
        'following' => false,
        'allow_all_act_msg' => false,
        'geo_enabled' => true,
        'verified' => false,
        'verified_type' => -1,
        'remark' => '',
        'ptype' => 0,
        'allow_all_comment' => true,
        'avatar_large' => 'http://tp3.sinaimg.cn/1806583694/180/5708306036/0',
        'avatar_hd' => 'http://ww4.sinaimg.cn/crop.0.1.510.510.1024/6bae478ejw8elbm81lrvdj20e60e8t9e.jpg',
        'verified_reason' => '',
        'verified_trade' => '',
        'verified_reason_url' => '',
        'verified_source' => '',
        'verified_source_url' => '',
        'follow_me' => false,
        'online_status' => 0,
        'bi_followers_count' => 22,
        'lang' => 'zh-cn',
        'star' => 0,
        'mbtype' => 0,
        'mbrank' => 0,
        'block_word' => 0,
        'block_app' => 0,
        'credit_score' => 80,
      ),
      'mid' => '3761186447330693',
      'idstr' => '3761186447330693',
      'pic_ids' => 
      array (
      ),
      'floor_num' => 50,
    )
  );
//$jsonarr[] =$tmp;
}

//addarticle
$t = array();
$t['type'] = 'addarticle';
$t['ispartialdata'] = true;
$t['isnested'] = true;
$t['data'] = $jsonarr;
//adduser
$jsonuser = array();
for($j=0;$j<3;$j++){
	$tmp =  array('id'=>1744957737+(3*$j),'screen_name' => 'PPTV测试加参数体育'.$j.'', 'source_host'=>'weibo.com', 'province_code' => '140000', 'city_code' => '140300', 'register_time' => 1330963200+(30*24*3600*$j), 'verify' => 0, 
        'name' => 'PPTV体育'.$j.'',
        'description' => 
        array (
          'content' => 'PPTV体育中心 http://sports.pptv.com',
          'terms' => 
		  array (
			  array( 'end'=> '4', 'start'=> '0', 'text'=> 'pptv'),
			  array( 'end'=> '6', 'start'=> '4', 'text'=> '体育'),
			  array( 'end'=> '8', 'start'=> '6', 'text'=> '中心')
		  ),
        ),
        'profile_image_url' => 'http://tp2.sinaimg.cn/1744957737/50/40066162174/1',
        'gender' => 'm',
        'followers_count' => 95604,
        'friends_count' => 998,
        'statuses_count' => 22923,
        'favourites_count' => 43,
		'created_at_ts' => 1331041052,
        'allow_all_act_msg' => true,
        'geo_enabled' => true,
        'verified_type' => 5,
        'avatar_large' => 'http://tp2.sinaimg.cn/1744957737/180/40066162174/1',
        'verified_reason' => 
        array (
          'content' => 'PPTV体育官方账号',
          'terms' => 
		  array ( 
			  array( 'end'=> '4', 'start'=> '0', 'text'=> 'pptv'),
			  array( 'end'=> '6', 'start'=> '4', 'text'=> '体育'),
			  array( 'end'=> '8', 'start'=> '6', 'text'=> '官方'),
			  array( 'end'=> '10', 'start'=> '8', 'text'=> '账号')
		  ),
        ),
        'bi_followers_count' => 565,
        'page_url' => 'http://weibo.com/u/1744957737'
	);
	$jsonuser[] = $tmp;
}
/*
$t = array();
$t['type'] = 'adduser';
$t['ispartialdata'] = true;
$t['data'] = $jsonuser;
 */
//calctrend
$trend = array();
$trend[] = array('url'=>'http://bbs.autohome.com.cn/club-20413/thread-11-1.shtml');
$trend[] = array('url'=>'http://weibo.com/3204782330/Bkw2r3szi');
/*
$t = array();
$t['type'] = 'calctrend';
$t['trendtype'] = 'comment_trend';
$t['data'] = $trend;
 */
$jsoninfo = json_encode($t);
var_dump($jsoninfo);
$res = thirdPartySendData($jsoninfo, $url); 
var_dump(json_decode($res,true));
//var_dump($res);
