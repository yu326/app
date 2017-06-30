<?php
class Weibo_Base
{
    private $oAuth_internal;
    private $source;//表示具体的SDK对应的类，通过sourceid 和 version 共同决定的一个唯一的值
    public $sourceid;
    private $ret;
    private $http_header;
    public $username;
    private $password;
    private $lastAPIData;//最后一次从API获取到的数据
    public $appkey;
    private $isbiz;
    //cookie files are used by tengxun
    private $cookie_file_1;
    private $cookie_file_2;
    function __construct($sourceid, $username, $appkey, $isbiz = false)
    {

        $this->sourceid = $sourceid;
        $this->username = $username;
        //$this->password = $password;
        $this->appkey = $appkey;
        $this->isbiz = $isbiz;
        $this->http_header = array();

        //根据source 获取其版本配置
        switch($sourceid){
            case WEIBO_SINA:
                $this->source = SINA_SDK_VERSION;
                break;
            case WEIBO_TENGXUN:
                $this->source = TENGXUN_SDK_VERSION;
                break;
            default:
                return false;
        }
        $this->init_weibo_class();
        if(empty($this->oAuth_internal)){
            throw new Exception("weiboclass初始化异常");
        }
    }
    
    public function getLastAPIData(){
        return $this->lastAPIData;	
    }
    
    public function init_weibo_class($need_oauth = false)
    {
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $this->oAuth_internal = $this->sina_oauth_1($need_oauth);
                break;
            case WEIBO_SINA_V2_CODE:
                $this->oAuth_internal = $this->sina_oauth_2($need_oauth);
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $this->oAuth_internal = $this->tengxun_oauth_1($need_oauth);
                break;
        }
    }
    /**
     * 通过微博（评论、私信）MID获取其ID
     *
     * 对应API：{@link http://open.weibo.com/wiki/2/statuses/queryid statuses/queryid}
     *
     * @param int|string $mid  需要查询的微博（评论、私信）MID，批量模式下，用半角逗号分隔，最多不超过20个。
     * @param int $type  获取类型，1：微博、2：评论、3：私信，默认为1。
     * @param int $is_batch 是否使用批量模式，0：否、1：是，默认为0。
     * @param int $inbox  仅对私信有效，当MID类型为私信时用此参数，0：发件箱、1：收件箱，默认为0 。
     * @param int $isBase62 MID是否是base62编码，0：否、1：是，默认为0。
     * @return array
     */
    public function queryid($mid, $type = 1, $is_batch = 0, $inbox = 0, $isBase62 = 0){
        $id = '';
        switch ($this->source)
        {
            case WEIBO_SINA_V2_CODE:
                $id = $this->oAuth_internal->queryid($mid, $type, $is_batch, $inbox,$isBase62);
                if ($this->check_result($id) === false){
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $id = $this->oAuth_internal->queryid($mid, $type, $is_batch, $inbox,$isBase62);
                        if ($this->check_result($id) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }                   
                }
                break;
            case WEIBO_TENGXUN_V1_CODE:
                break;
        }
        return $id;
    }
    //V2 通过关键字获取微博
    public function weibo_limited($q, $page = 1, $count = 50, $ids = NULL, $sort = NULL, $starttime = 0, $endtime = 0, $dup = 0, $onlynum = 0,$antispam = 0){
        $this->lastAPIData = null;
        $this->ret = array();
        switch($this->source)
        {
            //新的access_token:2.00SdyOsBnp71EDa660815103CQCIoB
            //显示的access_token值为：2.002AdWcCtxLFoC8335fcbb030Riwya
            case WEIBO_SINA_V1_CODE:
               break;
            case WEIBO_SINA_V2_CODE:
                $weibos_info = $this->oAuth_internal->limited($q, $page, $count, $ids, $sort, $starttime, $endtime, $dup, $onlynum, $antispam);
                if ($this->check_result($weibos_info) === false)
                {
                    //error is expired_token
                    if (isset($this->ret['error_code']) && $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = $this->oAuth_internal->limited($q, $page, $count, $ids, $sort, $starttime, $endtime, $dup, $onlynum, $antispam);
                        if ($this->check_result($weibos_info) === false)
                        {
                            return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                if($onlynum === 1){
                    $this->ret = $weibos_info['total_number'];
                }
                else{
                    foreach ($weibos_info['statuses'] as $weibo_info_key => $weibo_info)
                    {
                        $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                        $this->ret[] = $weibo_info_ret;
                    }
                }
                break;
            case WEIBO_TENGXUN_V1_CODE:
                break;
            default:
                break;
        }
        $this->lastAPIData = $weibos_info;
        return $this->ret;
    }
    /*
     * 查询指定一批微博id的转发，评论，喜欢数
     * */
    public function getNewRepost($ids){
        switch($this->source)
        {
            case WEIBO_SINA_V2_CODE:
                $weibos_info = $this->oAuth_internal->getNewRepost($ids);
                if ($this->check_result($weibos_info) === false)
                {
                    //error is expired_token
                    if (isset($this->ret['error_code']) && $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = $this->oAuth_internal->getNewRepost($ids);
                        if ($this->check_result($weibos_info) === false)
                        {
                            return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
            break;
            default:
                break;
        }
        $this->lastAPIData = $weibos_info;
        return $weibos_info;
    }
    //根据用户名获取用户信息
    public function get_userid($q,$count){
        global $logger;
        switch($this->source)
        {
            case WEIBO_SINA_V2_CODE:
                $weibos_info = $this->oAuth_internal->get_userid($q,1);
                $logger->debug(__FUNCTION__.' weibos_info '.var_export($weibos_info,true));
                if ($this->check_result($weibos_info) === false)
                {
                    if (isset($this->ret['error_code']) && $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = $this->oAuth_internal->get_userid($q,$count);
                        if ($this->check_result($weibos_info) === false)
                        {
                            return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                break;
            default:
                break;

        }
        $this->lastAPIData = $weibos_info;
        return $weibos_info;
    }
    /*
     *  添加订阅  微博关键词
     *
     * * */
    public function add_keywords($subid,$add_keywords,$del_keywords,$add_uids,$del_uids){
        global $logger;
        $weibos_info = $this->oAuth_internal->addkeyword($subid,$add_keywords,$del_keywords,$add_uids,$del_uids);
        $logger->debug(__FILE__.__LINE__."weibo_class这边的数据".var_export($weibos_info,true));
        return $weibos_info;
    }
    /*
     * 链接接口，获取数据
     *
     * * */
    public function datapush($subid,$since_id){
        global $logger;
        $weibos_info = $this->oAuth_internal->datapush($subid,$since_id);
        $logger->debug(__FILE__.__LINE__."datapush这边的数据".var_export($weibos_info,true));
        return $weibos_info;
    }
    //根据用户id获取最近的200条微博
    public function user_timeline($page = 1, $count = 20, $uid_or_name = NULL, $feature = NULL, $base_app = NULL, $flag = NULL){
        global $logger;
        $this->lastAPIData = null;
        $this->ret = array();
        switch ($this->source)
        {
        case WEIBO_SINA_V1_CODE:
            $weibos_info = $this->oAuth_internal->user_timeline($page, $count, $uid_or_name, $since_id, $max_id);
            if ($this->check_result($weibos_info) === false)
            {
                return $this->ret;
            }
            foreach ($weibos_info as $weibo_info_key => $weibo_info)
            {
                $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                $this->ret[] = $weibo_info_ret;
            }
            break;
        case WEIBO_SINA_V2_CODE:
            $weibos_info = $this->oAuth_internal->user_timeline_by_id($uid_or_name, $page, $count, $feature, $base_app, $flag);
            $logger->debug(__FILE__.__LINE__."weibo_class这边的数据".var_export($weibos_info,true));
            if ($this->check_result($weibos_info) === false)
            {
                //error is expired_token
                if ( isset($this->ret['error_code']) &&
                    $this->ret['error_code'] == ERROR_TOKEN)
                {
                    $this->init_weibo_class(true);
                    $weibos_info = $this->oAuth_internal->user_timeline_by_id($uid_or_name, $page, $count, $since_id, $max_id);
                    if ($this->check_result($weibos_info) === false)
                    {
                        return $this->ret;
                    }
                    else{
                        $this->ret = array();
                    }
                }
                else
                {
                    return $this->ret;
                }
            }
            foreach ($weibos_info['statuses'] as $weibo_info_key => $weibo_info)
            {
                $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                $this->ret[] = $weibo_info_ret;
            }
            break;
        case WEIBO_TENGXUN_V1_CODE:
            $api_name = 'statuses/user_timeline';
            //分页标识（0：第一页，1：向下翻页，2：向上翻页）
            $pageflag = $page <= 1 ? 0 : 1;//只向下翻页
            if($since_id == NULL && $max_id == NULL){

            }
            $params = array();
            $params['format'] = 'json';
            $params['type'] = 0x1 | 0x2;
            $params['fopenid'] = $uid_or_name;
            $params['reqnum'] = $count;
            //$params['reqnum'] = $count;
            $weibos_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
            if ($this->check_result($weibos_info) === false)
            {
                if ( isset($this->ret['error_code']) &&
                    $this->ret['error_code'] == ERROR_TOKEN)
                {
                    $this->init_weibo_class(true);
                    $weibos_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                    if ($this->check_result($weibos_info) === false)
                    {
                        return $this->ret;
                    }
                    else{
                        $this->ret = array();
                    }
                }
                else
                {
                    return $this->ret;
                }

            }
            foreach ($weibos_info['data']['info'] as $weibo_info)
            {
                $weibo_info_ret = $this->set_weibo_info_tengxun($weibo_info);
                $this->ret[] = $weibo_info_ret;
            }
            break;
        default:
            break;
        }
        $this->lastAPIData = $weibos_info;
        return $this->ret;
    }
    public function repost_timeline_all($id, $page = 1, $count = 20)
    {
        $this->lastAPIData = null;
        $this->ret = array();
        switch ($this->source)
        {
        case WEIBO_SINA_V1_CODE:
            break;
        case WEIBO_SINA_V2_CODE:
            $weibos_info = $this->oAuth_internal->repost_timeline_all($id, $page, $count);
            if ($this->check_result($weibos_info) === false)
            {
                if ( isset($this->ret['error_code']) &&
                    $this->ret['error_code'] == ERROR_TOKEN)
                {
                    $this->init_weibo_class(true);
                    $weibos_info = $this->oAuth_internal->repost_timeline_all($id, $page, $count);
                    if ($this->check_result($weibos_info) === false)
                    {
                        return $this->ret;
                    }
                }
                else
                {
                    return $this->ret;
                }
            }
            foreach ($weibos_info['reposts'] as $weibo_info_key => $weibo_info)
            {
                $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                $this->ret[] = $weibo_info_ret;
            }
            break;
        case WEIBO_TENGXUN_V1_CODE:
            break;
        default:
            break;
        }
        $this->lastAPIData = $weibos_info;
        return $this->ret;
    }
    public function repost_timeline($sid, $page = 1 , $count = 20, $since_id = NULL, $max_id = NULL)
    {
    	  $this->lastAPIData = null;
        $this->ret = array();
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $weibos_info = $this->oAuth_internal->repost_timeline($sid, $page, $count, $since_id, $max_id);
                if ($this->check_result($weibos_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = $this->oAuth_internal->repost_timeline($sid, $page, $count, $since_id, $max_id);
                        if ($this->check_result($weibos_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                foreach ($weibos_info as $weibo_info_key => $weibo_info)
                {
                    $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                    $this->ret[] = $weibo_info_ret;
                }
                break;
            case WEIBO_SINA_V2_CODE:
                $weibos_info = $this->oAuth_internal->repost_timeline($sid, $page, $count, $since_id, $max_id);
                if ($this->check_result($weibos_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = $this->oAuth_internal->repost_timeline($sid, $page, $count, $since_id, $max_id);
                        if ($this->check_result($weibos_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                
                foreach ($weibos_info['reposts'] as $weibo_info_key => $weibo_info)
                {
                    $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                    $this->ret[] = $weibo_info_ret;
                }
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 'statuses/repost_timeline';
                $params = array();
                $params['format'] = 'json';
                $params['type'] = 0x2;
                $weibos_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($weibos_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($weibos_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                foreach ($weibos_info['data']['info'] as $weibo_info)
                {
                    $weibo_info_ret = $this->set_weibo_info_tengxun($weibo_info);
                    $this->ret[] = $weibo_info_ret;
                }
                break;
            default:
                break;
        }
        $this->lastAPIData = $weibos_info;
        return $this->ret;
    }
    
    //add by Todd
    public function repost_timeline_ids($sid, $page = 1 , $count = 20, $since_id = NULL, $max_id = NULL)
    {
    	  $this->lastAPIData = null;
        $this->ret = array();
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                break;
            case WEIBO_SINA_V2_CODE:
                $weibos_info = $this->oAuth_internal->repost_timeline_ids($sid, $page, $count, $since_id, $max_id);
                if ($this->check_result($weibos_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = $this->oAuth_internal->repost_timeline_ids($sid, $page, $count, $since_id, $max_id);
                        if ($this->check_result($weibos_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $this->ret = $weibos_info['statuses'];
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 'statuses/repost_timeline/ids';
                $params = array();
                $params['format'] = 'json';
                $params['type'] = 0x2;
                $weibos_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($weibos_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibos_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($weibos_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $this->ret = $weibos_info['data']['info'];
                break;
            default:
                break;
        }
        $this->lastAPIData = $weibos_info;
        return $this->ret;
    }
    
    //public function get_status_by_id($sid)
    public function show_status($sid)
    {
        $this->ret = array();
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $weibo_info = $this->oAuth_internal->show_status($sid);
                if ($this->check_result($weibo_info) === false)
                {
                    return $this->ret;
                }
                $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                $this->ret = $weibo_info_ret;
                break;
            case WEIBO_SINA_V2_CODE:
                $weibo_info = $this->oAuth_internal->show_status($sid);
                if ($this->check_result($weibo_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibo_info = $this->oAuth_internal->show_status($sid);
                        if ($this->check_result($weibo_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $weibo_info_ret = $this->set_weibo_info_sina($weibo_info);
                $this->ret = $weibo_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 't/show';
                $params=array(
                'format'=>'json',
                'id'=>'kaifulee'
                );
                $weibo_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($weibo_info === false || $weibo_info === null)
                {
                    //log error, then return
                    return array();
                }
                if ($this->check_result($weibo_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $weibo_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($weibo_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $weibo_info_ret = $this->set_weibo_info_tengxun($weibo_info['data']);
                $this->ret = $weibo_info_ret;
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function show_user($uid, $uname = '')
    {
        $this->ret = array();
        $is_id = true;
        $uid_or_name = $uid;
        if (empty($uid) && empty($uname))
        {
            return $this->ret;
        }
        else if (empty($uid) && !empty($uname))
        {
            $is_id = false;
            $uid_or_name = $uname;
        }
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $user_info = $this->oAuth_internal->show_user($uid_or_name);
                if ($this->check_result($user_info) === false)
                {
                    return $this->ret;
                }
                $user_info_ret = $this->set_user_info_sina($user_info);
                $this->ret = $user_info_ret;
                break;
            case WEIBO_SINA_V2_CODE:
                if ($is_id)
                {
                    $user_info = $this->oAuth_internal->show_user_by_id($uid_or_name);
                }
                else
                {
                    $user_info = $this->oAuth_internal->show_user_by_name($uid_or_name);
                }
                if ($this->check_result($user_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        if ($is_id){
                            $user_info = $this->oAuth_internal->show_user_by_id($uid_or_name);
                        }
                        else{
                            $user_info = $this->oAuth_internal->show_user_by_name($uid_or_name);
                        }
                        if ($this->check_result($user_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $user_info_ret = $this->set_user_info_sina($user_info);
                $this->ret = $user_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 'user/other_info';
                $params = array();
                $params['format'] = 'json';
                if ($is_id)
                {
                    $params['fopenid'] = $uid_or_name;
                }
                else
                {
                    $params['name'] = $uid_or_name;
                }
                $user_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($user_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $user_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($user_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $user_info_ret = $this->set_user_info_tengxun($user_info['data']);
                $this->ret = $user_info_ret;
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function get_count_info_by_ids($sids)
    {
        $sids_str = '';
        if (is_array($sids) && !empty($sids))
        {
            foreach ($sids as $k => $v)
            {
                $this->id_format($sids[$k]);
            }
            $sids_str = join(',', $sids);
        }
        else
        {
            $sids_str = $sids;
        }
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $count_info = $this->oAuth_internal->get_count_info_by_ids($sids_str);
                if ($this->check_result($count_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $count_info = $this->oAuth_internal->get_count_info_by_ids($sids_str);
                        if ($this->check_result($count_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $count_info_ret = $this->set_count_info($count_info);
                $this->ret = $count_info_ret;
                break;
            case WEIBO_SINA_V2_CODE:
                $count_info = $this->oAuth_internal->get_count_info_by_ids($sids_str);
                if ($this->check_result($count_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $count_info = $this->oAuth_internal->get_count_info_by_ids($sids_str);
                        if ($this->check_result($count_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $count_info_ret = $this->set_count_info($count_info);
                $this->ret = $count_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 't/re_count';
                $params = array();
                $params['format'] = 'json';
                $params['ids'] = $sids_str;
                $params['flag'] = 2;
                $count_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($count_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $count_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($count_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $count_info_ret = $this->set_count_info($count_info['data']);
                $this->ret = $count_info_ret;
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function get_comments_by_sid($sid , $page = 1 , $count = 50, $sinceid=0,$maxid=0)
    {
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $comments_info = $this->oAuth_internal->get_comments_by_sid($sid , $page, $count, $sinceid,$maxid);
                if ($this->check_result($comments_info) === false)
                {
                    return $this->ret;
                }
                $comments_info_ret = $this->set_comments_info_sina($comments_info);
                $this->ret = $comments_info_ret;
                break;
            case WEIBO_SINA_V2_CODE:
                $comments_info = $this->oAuth_internal->get_comments_by_sid($sid , $page, $count, $sinceid,$maxid);
                if ($this->check_result($comments_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $comments_info = $this->oAuth_internal->get_comments_by_sid($sid , $page, $count, $sinceid,$maxid);
                        if ($this->check_result($comments_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $comments_info_ret = $this->set_comments_info_sina($comments_info['comments']);
                $this->ret = $comments_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 't/re_list';
                $params = array();
                $params['format'] = 'json';
                $comments_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($comments_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $comments_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($comments_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                    
                }
                $comments_info_ret = $this->set_comments_info_tengxun($comments_info);
                $this->ret = $comments_info_ret;
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function comments_show_all($id, $page = 1, $count = 20)
    {
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                // Not implemented
                break;
            case WEIBO_SINA_V2_CODE:

                $comments_info = $this->oAuth_internal->comments_show_all($id,$page,$count);
                if ($this->check_result($comments_info) === false)
                {
                    if(isset($this->ret['error_code']) && $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $comments_info = $this->oAuth_internal->comments_show_all($id,$page,$count);
                        if ($this->check_result($comments_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $comments_info_ret = $this->set_comments_info_sina($comments_info);
                $this->ret = $comments_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                // Not implemented
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function comments_show_batch($cids)
    {
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                // Not implemented
                break;
            case WEIBO_SINA_V2_CODE:
                $comments_info = $this->oAuth_internal->comments_show_batch($cids);
                if ($this->check_result($comments_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $comments_info = $this->oAuth_internal->comments_show_batch($cids);
                        if ($this->check_result($comments_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                $comments_info_ret = $this->set_comments_info_sina($comments_info);
                $this->ret = $comments_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                // Not implemented
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function followers($cursor = NULL , $count = 20 , $uid = NULL, $uname = '')
    {
        $this->ret = array();
        $users_info_ret = array();
        $is_id = true;
        $uid_or_name = $uid;
        if (empty($uid) && empty($uname))
        {
            return $this->ret;
        }
        else if (empty($uid) && !empty($uname))
        {
            $is_id = false;
            $uid_or_name = $uname;
        }
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $users_info = $this->oAuth_internal->followers($cursor, $count, $uid_or_name);
                if ($this->check_result($users_info) === false)
                {
                    return $this->ret;
                }
                foreach ($users_info['users'] as $user_info)
                {
                    $user_info_ret = $this->set_user_info_sina($user_info);
                    $users_info_ret[] = $user_info_ret;
                }
                $this->ret = $users_info_ret;
                break;
            case WEIBO_SINA_V2_CODE:
                if ($is_id)
                {
                    $users_info = $this->oAuth_internal->followers_by_id($uid_or_name, $cursor, $count);
                }
                else
                {
                    $users_info = $this->oAuth_internal->followers_by_name($uid_or_name, $cursor, $count);
                }
                if ($this->check_result($users_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        if ($is_id)
                        {
                            $users_info = $this->oAuth_internal->followers_by_id($uid_or_name, $cursor, $count);
                        }
                        else
                        {
                            $users_info = $this->oAuth_internal->followers_by_name($uid_or_name, $cursor, $count);
                        }
                        if ($this->check_result($weibo_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                foreach ($users_info['users'] as $user_info)
                {
                    $user_info_ret = $this->set_user_info_sina($user_info);
                    $users_info_ret[] = $user_info_ret;
                }
                $this->ret = $users_info_ret;
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 'friends/user_fanslist';
                $params = array();
                $params['format'] = 'json';
                $params['mode'] = 1;
                if ($is_id)
                {
                    $params['fopenid'] = $uid_or_name;
                }
                else
                {
                    $params['name'] = $uid_or_name;
                }
                $users_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($users_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $users_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($users_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                foreach ($users_info['data']['info'] as $user_info)
                {
                    $user_info_ret = $this->set_user_info_tengxun($user_info);
                    $users_info_ret[] = $user_info_ret;
                }
                $this->ret = $users_info_ret;
                break;
            default:
                break;
        }
        return $this->ret;
    }
    public function friends($cursor = NULL , $count = 20 , $uid = NULL, $uname = '')
    {
        $this->ret = array();
        $users_info_ret = array();
        $is_id = true;
        $uid_or_name = $uid;
        if (empty($uid) && empty($uname))
        {
            return $this->ret;
        }
        else if (empty($uid) && !empty($uname))
        {
            $is_id = false;
            $uid_or_name = $uname;
        }
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                $users_info = $this->oAuth_internal->friends($cursor, $count, $uid_or_name);
                if ($this->check_result($users_info) === false)
                {
                    return $this->ret;
                }
                foreach ($users_info['users'] as $user_info)
                {
                    $user_info_ret = $this->set_user_info_sina($user_info);
                    $users_info_ret[] = $user_info_ret;
                }
                $this->ret['users'] = $users_info_ret;
                $this->ret['next_cursor'] = $users_info['next_cursor'];
                $this->ret['previous_cursor'] = $users_info['previous_cursor'];
                break;
            case WEIBO_SINA_V2_CODE:
                if ($is_id)
                {
                    $users_info = $this->oAuth_internal->friends_by_id($uid_or_name, $cursor, $count);
                }
                else
                {
                    $users_info = $this->oAuth_internal->friends_by_name($uid_or_name, $cursor, $count);
                }
                if ($this->check_result($users_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        if ($is_id)
                        {
                            $users_info = $this->oAuth_internal->friends_by_id($uid_or_name, $cursor, $count);
                        }
                        else
                        {
                            $users_info = $this->oAuth_internal->friends_by_name($uid_or_name, $cursor, $count);
                        }
                        if ($this->check_result($weibo_info) === false)
                        {
                             return $this->ret;
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                }
                foreach ($users_info['users'] as $user_info)
                {
                    $user_info_ret = $this->set_user_info_sina($user_info);
                    $users_info_ret[] = $user_info_ret;
                }
                $this->ret['users'] = $users_info_ret;
                $this->ret['next_cursor'] = $users_info['next_cursor'];
                $this->ret['previous_cursor'] = $users_info['previous_cursor'];
                break;
            case WEIBO_TENGXUN_V1_CODE:
                $api_name = 'friends/user_idollist';
                $params = array();
                $params['format'] = 'json';
                $params['mode'] = 1;
                if ($is_id)
                {
                    $params['fopenid'] = $uid_or_name;
                }
                else
                {
                    $params['name'] = $uid_or_name;
                }
                $users_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                if ($this->check_result($users_info) === false)
                {
                    if ( isset($this->ret['error_code']) &&
                         $this->ret['error_code'] == ERROR_TOKEN)
                    {
                        $this->init_weibo_class(true);
                        $users_info = OpenSDK_Tencent_Weibo::call($api_name,$params);
                        if ($this->check_result($users_info) === false)
                        {
                             return $this->ret;
                        }
                        else{
                            $this->ret = array();
                        }
                    }
                    else
                    {
                        return $this->ret;
                    }
                    
                }
                foreach ($users_info['data']['info'] as $user_info)
                {
                    $user_info_ret = $this->set_user_info_tengxun($user_info);
                    $users_info_ret[] = $user_info_ret;
                }
                $this->ret = $users_info_ret;
                break;
            default:
                break;
        }
        return $this->ret;
    }
    private function id_format(&$id)
    {
        if ( is_float($id) )
        {
            $id = number_format($id, 0, '', '');
        }
        elseif ( is_string($id) )
        {
            $id = trim($id);
        }
    }
    private function check_result($result)
    {
        //unset($this->ret);
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
            case WEIBO_SINA_V2_CODE:
                if ($result === false || $result === null)
                {
                    $this->ret = array();
                    return false;
                }
                if (isset($result['error_code']) && isset($result['error']))
                {
                    $result = $this->format_error($result);
                    $this->ret = $result;
                    return false;
                }
                break;
            case WEIBO_TENGXUN_V1_CODE:
                if ($result === false || $result === null)
                {
                    return false;
                }
                if ($result['ret'] != 0)
                {
                    $tmp = array();
                    $tmp['ret'] = $result['ret'];
                    $tmp['error_code'] = $result['errcode'];
                    $tmp['error'] = $result['msg'];
                    $result = $this->format_error($tmp);
                    unset($tmp);
                    $this->ret = $result;
                    return false;
                }
                break;
        }
        return true;
    }
    private function format_error($result)
    {
        $ret_result = array();
        switch ($this->source)
        {
            case WEIBO_SINA_V1_CODE:
                break;
            case WEIBO_SINA_V2_CODE:
                switch ($result['error_code'])
                {
                case 10022:
                    $ret_result['error_code'] = ERROR_IP_OUT_LIMIT;
                    break;
                case 10023:
                case 10024:
                    $ret_result['error_code'] = ERROR_USER_OUT_LIMIT;
                    break;
                case 21302:
                    $ret_result['error_code'] = ERROR_LOGIN;
                    break;
                case 20101:
                case 20104:
                case 20201:
                case 20202:
                    $ret_result['error_code'] = ERROR_CONTENT_NOT_EXIST;
                    break;
                case 20112:
                    $ret_result['error_code'] = ERROR_CONTENT_PRIVATE;
                    break;
                case 21314:
                case 21315:
                case 21316:
                case 21317:
                case 21327:
                case 21332:
                case 21501:
                case 10006:
                    $ret_result['error_code'] = ERROR_TOKEN;
                    break;
                case 20003:
                    $ret_result['error_code'] = ERROR_USER_NOT_EXIST;
                    break;
                default:
                    $ret_result['error_code'] = ERROR_OTHER;
                    break;
                }
                 $ret_result['error'] = "code:{$result['error_code']} ".$result['error'];
                break;
            case WEIBO_TENGXUN_V1_CODE:
                switch ($result['ret']){
                    case 1:
                    case 4:
                        $ret_result['error_code'] = ERROR_OTHER;
                        break;
                    case 2:
                        //频率受限
                        $ret_result['error_code'] = ERROR_USER_OUT_LIMIT;
                        break;
                    case 3:
                        //鉴权失败
                        switch ($result['error_code']){
                            case 7:
                            case 11:
                                $ret_result['error_code'] = ERROR_OTHER;
                                break;
                            default:
                                $ret_result['error_code'] = ERROR_TOKEN;
                                break;
                        }
                        break;
                    default:
                        $ret_result['error_code'] = ERROR_OTHER;
                        break;
                }
                $ret_result['error'] = "ret:{$result['ret']} code:{$result['error_code']} ".$result['error'];
                break;
        }
        return $ret_result;
    }
    private function set_weibo_info_sina($weibo_info)
    {
        $weibo_info_ret = array();
        if (isset($weibo_info['idstr']))
        {
            $weibo_info_ret['id'] = $weibo_info['idstr'];
        }
        else
        {
            $weibo_info_ret['id'] = $weibo_info['id'];
        }
        $weibo_info_ret['text'] = $weibo_info['text'];
        /*
         * 如果微博已被删除，则只存在text和id信息
         */
        if (isset($weibo_info['user']))
        {
            $weibo_info_ret['created_at'] = $weibo_info['created_at'];
            $weibo_info_ret['source'] = $weibo_info['source'];
            $weibo_info_ret['favorited'] = $weibo_info['favorited'];
            $weibo_info_ret['truncated'] = $weibo_info['truncated'];
            $weibo_info_ret['in_reply_to_status_id'] = $weibo_info['in_reply_to_status_id'];
            $weibo_info_ret['in_reply_to_user_id'] = $weibo_info['in_reply_to_user_id'];
            $weibo_info_ret['in_reply_to_screen_name'] = $weibo_info['in_reply_to_screen_name'];
            $weibo_info_ret['thumbnail_pic'] = isset($weibo_info['thumbnail_pic']) ? $weibo_info['thumbnail_pic'] : null;
            $weibo_info_ret['bmiddle_pic'] = isset($weibo_info['bmiddle_pic']) ? $weibo_info['bmiddle_pic'] : null;
            $weibo_info_ret['original_pic'] = isset($weibo_info['original_pic']) ? $weibo_info['original_pic'] : null;
            $weibo_info_ret['user'] = $this->set_user_info_sina($weibo_info['user']);
            $weibo_info_ret['sourceid'] = $this->sourceid;
            if (isset($weibo_info['retweeted_status']))
            {
                $weibo_info_ret['retweeted_status'] = $weibo_info['retweeted_status'];
                $is_repost = 1;
				if (isset($weibo_info['pid'])) {
                    $weibo_info_ret['pid'] = $weibo_info['pid'];
                }else{
                    $weibo_info_ret['pid'] = $weibo_info['retweeted_status']['id'];
                }
            }
            else
            {
                $is_repost = 0;
            }
            /*
             * 需要添加上，区分是否是原创
             */
            $weibo_info_ret['is_repost'] = $is_repost;
            $weibo_info_ret['geo'] = isset($weibo_info['geo']) ? $weibo_info['geo'] : null;
            $weibo_info_ret['annotations'] = isset($weibo_info['annotations']) ? $weibo_info['annotations'] : null;
            $weibo_info_ret['mid'] = isset($weibo_info['mid']) ? $weibo_info['mid'] : '';
            if (isset($weibo_info['reposts_count']))
            {
                $weibo_info_ret['reposts_count'] = $weibo_info['reposts_count'];
            }
            if (isset($weibo_info['comments_count']))
            {
                $weibo_info_ret['comments_count'] = $weibo_info['comments_count'];
            }
            if (isset($weibo_info['attitudes_count']))
            {
                $weibo_info_ret['praises_count'] = $weibo_info['attitudes_count'];
            }
        }
        return $weibo_info_ret;
    }
    private function set_weibo_info_tengxun($weibo_info)
    {
        $weibo_info_ret = array();
        $weibo_info_ret['id'] = $weibo_info['id'];
        $weibo_info_ret['created_at'] = $weibo_info['timestamp'];
        $weibo_info_ret['text'] = $weibo_info['text'];
        $weibo_info_ret['source'] = $weibo_info['from'];
        $weibo_info_ret['favorited'] = false;
        $weibo_info_ret['truncated'] = false;
        $weibo_info_ret['in_reply_to_status_id'] = false;
        $weibo_info_ret['in_reply_to_user_id'] = false;
        $weibo_info_ret['in_reply_to_screen_name'] = false;
        //腾讯将图片存放在image中
        $weibo_info_ret['thumbnail_pic'] = false;
        $weibo_info_ret['bmiddle_pic'] = false;
        $weibo_info_ret['original_pic'] = false;
        $weibo_info_ret['sourceid'] = $this->sourceid;
        if (empty($weibo_info['openid']))
        {
            $user_info = array();
            $user_info = $this->show_user('', $weibo_info['name']);
            $weibo_info_ret['userid'] = $user_info['openid'];
        }
        else
        {
            $weibo_info_ret['userid'] = $weibo_info['openid'];
        }
        if (2 == $weibo_info['retweeted_status'])
        {
            $weibo_info_ret['retweeted_status'] = $weibo_info['source']['id'];
            $is_repost = 1;
        }
        else
        {
            $is_repost = 0;
        }
        $weibo_info_ret['is_repost'] = $is_repost;
        $weibo_info_ret['geo_type'] = isset($weibo_info['geo']['type']) ? $weibo_info['geo']['type'] : '';
        $weibo_info_ret['geo_coordinates_x'] = isset($weibo_info['geo']['coordinates'][0]) ? $weibo_info['geo']['coordinates'][0] : 0;
        $weibo_info_ret['geo_coordinates_y'] = isset($weibo_info['geo']['coordinates'][1]) ? $weibo_info['geo']['coordinates'][1] : 0;
        $weibo_info_ret['annotations'] = $weibo_info['annotations'];
        $weibo_info_ret['mid'] = isset($weibo_info['mid']) ? $weibo_info['mid'] : '';
        $weibo_info_ret['province'] = $weibo_info['province_code'];
        $weibo_info_ret['city'] = $weibo_info['city_code'];
        $weibo_info_ret['reposts_count'] = $weibo_info['count'];
        $weibo_info_ret['comments_count'] = $weibo_info['mcount'];
        return $weibo_info_ret;
    }
    private function set_user_info_sina($user_info)
    {
        $user_info_ret = array();
        $user_info_ret['id'] = $user_info['id'];
        $user_info_ret['screen_name'] = $user_info['screen_name'];
        $user_info_ret['name'] = $user_info['name'];
        $user_info_ret['province'] = $user_info['province'];
        $user_info_ret['city'] = $user_info['city'];
        $user_info_ret['location'] = $user_info['location'];
        if(isset($user_info['description'])){
            $user_info_ret['description'] = $user_info['description'];
        }
        $user_info_ret['url'] = $user_info['url'];
        $user_info_ret['profile_image_url'] = $user_info['profile_image_url'];
        $user_info_ret['allow_all_act_msg'] = $user_info['allow_all_act_msg'];
        $user_info_ret['domain'] = $user_info['domain'];
        $user_info_ret['geo_enabled'] = $user_info['geo_enabled'];
        $user_info_ret['verified'] = $user_info['verified'];
        $user_info_ret['gender'] = $user_info['gender'];
        $user_info_ret['followers_count'] = $user_info['followers_count'];
        $user_info_ret['friends_count'] = $user_info['friends_count'];
        $user_info_ret['statuses_count'] = $user_info['statuses_count'];
        $user_info_ret['favourites_count'] = $user_info['favourites_count'];
        $user_info_ret['created_at'] = $user_info['created_at'];
        $user_info_ret['following'] = $user_info['following'];
        if (WEIBO_SINA_V2_CODE == $this->source)
        {
            $user_info_ret['allow_all_comment'] = $user_info['allow_all_comment'];
            $user_info_ret['avatar_large'] = $user_info['avatar_large'];
            $user_info_ret['verified_reason'] = $user_info['verified_reason'];
            $user_info_ret['verified_type'] = $user_info['verified_type'];
            $user_info_ret['follow_me'] = $user_info['follow_me'];
            $user_info_ret['online_status'] = $user_info['online_status'];
            $user_info_ret['bi_followers_count'] = $user_info['bi_followers_count'];
        }
        return $user_info_ret;
    }
    private function set_user_info_tengxun($user_info)
    {
        $user_info_ret = array();
        $user_info_ret['id'] = $user_info['openid'];
        $user_info_ret['screen_name'] = $user_info['name'];
        $user_info_ret['name'] = $user_info['nick'];
        $user_info_ret['province'] = $user_info['province_code'];
        $user_info_ret['city'] = $user_info['city_code'];
        $user_info_ret['location'] = $user_info['location'];
        $user_info_ret['description'] = $user_info['introduction'];
        $user_info_ret['url'] = $user_info['url'];
        $user_info_ret['profile_image_url'] = $user_info['head'];
        $user_info_ret['allow_all_act_msg'] = $user_info['allow_all_act_msg'];
        $user_info_ret['domain'] = $user_info['domain'];
        $user_info_ret['geo_enabled'] = $user_info['geo_enabled'];
        $user_info_ret['verified'] = $user_info['isvip'];
        $user_info_ret['enterprise'] = $user_info['isent'];    //tengxun
        $user_info_ret['email'] = $user_info['email'];    //tengxun
        $user_info_ret['country'] = $user_info['country_code'];    //tengxun
        switch ($user_info['sex'])
        {
            case 1:
                $user_info_ret['gender'] = 'm';
                break;
            case 2:
                $user_info_ret['gender'] = 'f';
                break;
            case 0:
                $user_info_ret['gender'] = 'n';
                break;
            default:
                $user_info_ret['gender'] = 'n';
                break;
        }
        $user_info_ret['followers_count'] = $user_info['fansnum'];
        $user_info_ret['friends_count'] = $user_info['idolnum'];
        $user_info_ret['statuses_count'] = $user_info['tweetnum'];
        //tengxun doesn't exist
        //$user_info_ret['created_at'] = $user_info['created_at'];
        $user_info_ret['following'] = $user_info['ismyidol'];
        $user_info_ret['follow_me'] = $user_info['ismyfans'];
        return $user_info_ret;
    }
    private function set_count_info($count_info)
    {
        $count_info_ret = array();
        foreach ($count_info as $count_info_key => $count_info_value)
        {
            $count_info_inner = array();
            switch ($this->source)
            {
                case WEIBO_SINA_V1_CODE:
                    $count_info_inner = $count_info_value;
                    break;
                case WEIBO_SINA_V2_CODE:
                    $count_info_inner['id'] = $count_info_value['id'];
                    $count_info_inner['comments'] = $count_info_value['comments'];
                    $count_info_inner['rt'] = $count_info_value['reposts'];
                    $count_info_inner['attitudes'] = $count_info_value['attitudes'];
                    break;
                case WEIBO_TENGXUN_V1_CODE:
                    $count_info_inner['id'] = $count_info_key;
                    $count_info_inner['comments'] = $count_info_value['mcount'];
                    $count_info_inner['rt'] = $count_info_value['count'];
                    break;
                default:
                    break;
            }
            $count_info_ret[] = $count_info_inner;
        }
        return $count_info_ret;
    }
    private function set_comments_info_sina($comments_info)
    {
        $comments_info_ret = array();
        foreach ($comments_info['comments'] as $comment_info)
        {
            $comment_info_ret = array();
            $comment_info_ret['created_at'] = $comment_info['created_at'];
            $comment_info_ret['province'] = $comment_info['user']['province'];
            $comment_info_ret['city'] = $comment_info['user']['city'];
            $comment_info_ret['id'] = $comment_info['id'];
            $comment_info_ret['text'] = $comment_info['text'];
            $comment_info_ret['source'] = $comment_info['source'];
            $comment_info_ret['favorited'] = isset($comment_info['favorited']) ? $comment_info['favorited'] : false;
            $comment_info_ret['truncated'] = isset($comment_info['truncated']) ? $comment_info['truncated'] : false;
            $comment_info_ret['userid'] = $comment_info['user']['id'];
            $comment_info_ret['statusid'] = $comment_info['status']['id'];
            $comment_info_ret['user'] = $comment_info['user'];
            $comment_info_ret['status'] = $comment_info['status'];
            $comment_info_ret['total_number'] = $comments_info['total_number'];
            if (isset($comment_info['reply_comment']['id']))
            {
                $comment_info_ret['reply_comment_id'] = $comment_info['reply_comment']['id'];
                $comment_info_ret['reply_comment'] = $comment_info['reply_comment'];
            }
            if (isset($comment_info['mid']))
            {
                $comment_info_ret['mid'] = $comment_info['mid'];
            }
            $comments_info_ret[] = $comment_info_ret;
        }
        return $comments_info_ret;
    }
    //未完成
    private function set_comments_info_tengxun($comments_info)
    {
        $comments_info_ret = array();
        foreach ($comments_info as $comment_info)
        {
            $comment_info_ret = array();
            $comment_info_ret['created_at'] = $comment_info['created_at'];
            $comment_info_ret['province'] = $comment_info['user']['province'];
            $comment_info_ret['city'] = $comment_info['user']['city'];
            $comment_info_ret['id'] = $comment_info['id'];
            $comment_info_ret['text'] = $comment_info['text'];
            $comment_info_ret['source'] = $comment_info['source'];
            $comment_info_ret['favorited'] = $comment_info['favorited'];
            $comment_info_ret['truncated'] = $comment_info['truncated'];
            $comment_info_ret['userid'] = $comment_info['user']['id'];
            $comment_info_ret['statusid'] = $comment_info['status']['id'];
            if (isset($comment_info['reply_comment']['id']))
            {
                $comment_info_ret['reply_comment'] = $comment_info['reply_comment']['id'];
            }
            if (isset($comment_info['mid']))
            {
                $comment_info_ret['mid'] = $comment_info['mid'];
            }
            $comments_info_ret[] = $comment_info_ret;
        }
        return $comments_info_ret;
    }
    private function sina_oauth_1($need_oauth)
    {
        $res = $this->get_access_token();
        if (!empty($res) && !empty($res[0]) && !empty($res[1]) && !$need_oauth)
        {
            $oAuthThird = new WeiboClient($this->appkey, $res[1], $res[0], $res[3]);
            return $oAuthThird;
        }
        $oAuth = new WeiboOAuth($this->appkey, $res[1]);
        $requestToken = $oAuth->getRequestToken();
        $sess_arr['requestToken'] = $requestToken;

        $postfields = array(
            'oauth_callback' => 'json',        
            'oauth_token' => $requestToken['oauth_token'],
            'userId' => $this->username,
            'passwd' => $this->password
        );

        $oAuthRequest = $oAuth->post('http://api.t.sina.com.cn/oauth/authorize', $postfields);
        $oAuthSecond = new WeiboOAuth( $this->appkey, $res[1], $sess_arr['requestToken']['oauth_token'], $sess_arr['requestToken']['oauth_token_secret']  );
        $accessToken = $oAuthSecond->getAccessToken($oAuthRequest['oauth_verifier']) ;

        $sess_arr['accessToken'] = $accessToken;

        $oAuthThird = new WeiboClient($this->appkey, $res[1], $sess_arr['accessToken']['oauth_token'], $sess_arr['accessToken']['oauth_token_secret']  );
        $this->update_access_token($sess_arr['accessToken']['oauth_token'],
        $sess_arr['accessToken']['oauth_token_secret']);
        return $oAuthThird;
    }
    private function sina_oauth_2($need_oauth)
    {
        $res = $this->get_access_token();
        if (!empty($res) && !empty($res[0]) && !$need_oauth)
        {
            if($this->isbiz){
                $c = new SaeTClientV2Biz( $this->appkey , $res[1] , $res[0]);
            }
            else{
                $c = new SaeTClientV2( $this->appkey , $res[1] , $res[0]);
            }
            return $c;
        }
        $url = $this->get_prelogin_url($this->username);
        //var_dump("url aaaaaa___", $url);
        $rep = $this->http_use_curl($url);
        //var_dump("aaaaaa___", $rep);
        $arr = $this->get_callback_arg($rep);
        $url1 = $this->get_login_url($this->username, $this->password, $arr);
        //var_dump("url1 bbbbb___", $url1);
        $rep1 = $this->http_use_curl($url1);
        //var_dump("bbbbbb___", $rep1);
        $arr1 = $this->get_callback_arg($rep1);
        $ticket = isset($arr1['ticket']) ? $arr1['ticket'] : '';

        $code_url = "https://api.weibo.com/oauth2/authorize";
        //$code_url = "https://api.weibo.com/oauth2/authorize?client_id=2812373555&redirect_uri=https%3A%2F%2Fapi.weibo.com%2Foauth2%2Fdefault.html&response_type=code";
        $parameters = array(
            "action" => "submit",
            "from" => "",
            "state" => "",
            "regCallback" => "",
            "response_type" => "code",
            "redirect_uri" => WB_CALLBACK_URL,
            "userId" => $this->username,
            "passwd" => $this->password,
            "client_id" => $this->appkey,
            "isLoginSina" => 0,
            "withOfficalFlag" => 0,
            "ticket" => $ticket
        );
        //var_dump($parameters);
        $post_res = $this->http_use_curl($code_url, 'POST', $parameters);
        //var_dump("cccccc___", $post_res);
        $redirect_url = isset($this->http_header['location']) ? $this->http_header['location'] : "";
        //var_dump("ddddd___", $this->http_header['location']);
        $redirect_url_arg = $this->get_url_arg($redirect_url);
        $code = isset($redirect_url_arg['code']) ? $redirect_url_arg['code'] : null;
        //var_dump("token___", $token);
        $keys = array();
        $keys['code'] = $code;
        $keys['redirect_uri'] = WB_CALLBACK_URL;
        $o = new SaeTOAuthV2( $this->appkey, $res[1] );
        $token = $o->getAccessToken( 'code', $keys );
        if($this->isbiz){
            $c = new SaeTClientV2Biz( $this->appkey, $res[1], $token['access_token'] );
        }
        else{
            $c = new SaeTClientV2( $this->appkey, $res[1], $token['access_token'] );
        }
        $this->update_access_token($token['access_token'],'');
        return $c;
    }
    private function tengxun_oauth_1($need_oauth)
    {
        $res = $this->get_access_token();
        if (!empty($res) && !empty($res[0]) && !empty($res[1]) && !$need_oauth)
        {
            $weibo_obj = new OpenSDK_Tencent_Weibo();
            $weibo_obj->init($this->appkey, $res[1]);
            $_SESSION[OpenSDK_Tencent_Weibo::ACCESS_TOKEN] = $res[0];
            $_SESSION[OpenSDK_Tencent_Weibo::OAUTH_TOKEN_SECRET] = $res[3];
            return $weibo_obj;
        }
        include_once('simple_html_dom.php');
        $this->cookie_file_1 = tempnam('.', 'cookie');
        $this->cookie_file_2 = tempnam('.', 'cookie');

        $verifyCode = $this->get_verify_code_by_tengxun();
        $weibo_obj = new OpenSDK_Tencent_Weibo();
        $weibo_obj->init($this->appkey, $res[1]);
        $callback = "http://heqiang.sinaapp.com";
        $request_token = $weibo_obj->getRequestToken($callback);
        $weibo_url = $weibo_obj->getAuthorizeURL($request_token);
        $html_obj = file_get_html($weibo_url);
        foreach ($html_obj->find('input') as $inp)
        {
            $tmp_id = $inp->id;
            $tmp_name = $inp->name;
            $tmp_value = $inp->value;
            if ($tmp_name == 'u1')
            {
                $u1 = $tmp_value;
            }
        }
        $u1 = urlencode($u1);
        $pa = $this->preprocess_by_tengxun($this->password, $verifyCode);
        $action = '2-27-'.rand(5000, 9000);
        $lang_num = 2052;
        $appid = 46000101;
        $qq = $this->username;

        $g_url = "http://ptlogin2.qq.com/login?ptlang={$lang_num}&u={$qq}&p={$pa}&verifycode={$verifyCode}&aid={$appid}&target=top&u1={$u1}&ptredirect=1&h=1&from_ui=1&dumy=&wording=%E3%80%80&fp=loginerroralert&action={$action}&dummy=";
        $rep = $this->http_use_curl($g_url, 'GET', array(), $this->cookie_file_1, $this->cookie_file_2);
        $rep_arr_1 = explode('(', $rep);
        $rep_arr_2 = explode(')', $rep_arr_1[1]);
        $rep_arr_3 = explode("','", $rep_arr_2[0]);
        $g_url_1 = $rep_arr_3[2];
        $g_url_1 = trim($g_url_1);
        $rep_1 = $this->http_use_curl($g_url_1, 'GET', array(), $this->cookie_file_2);
        if (preg_match("!url=(.*)\"!", $rep_1, $matches))
        {
            $parse_arr = parse_url($matches[1]);
            parse_str($parse_arr['query'], $query_arr);
            $weibo_obj->getAccessToken($query_arr['oauth_verifier']);
        }
        else
        {
            return false;
        }
        $this->delete_file_by_tengxun($this->cookie_file_1);
        $this->delete_file_by_tengxun($this->cookie_file_2);
        $this->update_access_token($_SESSION[OpenSDK_Tencent_Weibo::ACCESS_TOKEN],
        $_SESSION[OpenSDK_Tencent_Weibo::OAUTH_TOKEN_SECRET]);
        return $weibo_obj;
    }
    private function get_prelogin_url($username)
    {
        $u = base64_encode(urlencode($username));
        $url = "https://login.sina.com.cn/sso/prelogin.php?entry=openapi&callback=sinaSSOController.preloginCallBack&su={$u}&client=ssologin.js(v1.4.18)&_=";
        $url .= time().'000';
        return $url;
    }
    private function get_header($ch, $header)
    {
        $i = strpos($header, ':');
        if (!empty($i))
        {
            $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
            $value = trim(substr($header, $i + 2));
            $this->http_header[$key] = $value;
        }
        return strlen($header);
    }
    private function http_use_curl($url, $method = 'GET', $params = array(), $readcookie = '', $writecookie = '')
    {
        $connecttimeout = 30;
        $ssl_verifypeer = FALSE;
        $timeout = 30;
        $headers = array();

        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        //curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $ssl_verifypeer);
        //curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, FALSE);
        curl_setopt($ci, CURLOPT_URL, $url );
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        if (!empty($readcookie) && is_file($readcookie))
        {
            curl_setopt($ci, CURLOPT_COOKIEFILE, $readcookie);
        }
        if (!empty($writecookie))
        {
            curl_setopt($ci, CURLOPT_COOKIEJAR, $writecookie);
        }

        if ($method == 'POST')
        {
            $postfields = http_build_query($params);
            curl_setopt($ci, CURLOPT_POST, TRUE);
            if (!empty($postfields)) {
                curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
            }
            $headers[] = "Referer: https://api.weibo.com/oauth2/authorize?client_id=".$this->appkey."&redirect_uri=https%3A%2F%2Fapi.weibo.com%2Foauth2%2Fdefault.html&response_type=code";
            //https://api.weibo.com/oauth2/authorize?client_id=2812373555&redirect_uri=https%3A%2F%2Fapi.weibo.com%2Foauth2%2Fdefault.html&response_type=code
        }
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'get_header'));

        $response = curl_exec($ci);

        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        if ($http_code != 200 && $http_code != 301 && $http_code != 302)
        {
            $response = false;
        }

        curl_close ($ci);
        return $response;
    }
    private function get_callback_arg($reponse)
    {
        //preg_match("/?*/", $reponse, $get_str);
        $arr1 = explode("(", $reponse);
        $arr2 = explode(")", $arr1[1]);
        $get_str = $arr2[0];
        $get_arr = json_decode($get_str, true);
        return $get_arr;
    }
    //login.php
    private function get_login_url($username, $password, $prelogin_ret_arr)
    {
        $su = base64_encode(urlencode($username));
        $sp = sha1(sha1(sha1($password)).$prelogin_ret_arr['servertime'].$prelogin_ret_arr['nonce']);
        $url = "https://login.sina.com.cn/sso/login.php?entry=openapi&gateway=1&from=&savestate=0&useticket=1&ct=1800&vsnf=1&vsnval=&su={$su}&service=miniblog&servertime={$prelogin_ret_arr['servertime']}&nonce={$prelogin_ret_arr['nonce']}&pwencode=wsse&sp={$sp}&encoding=UTF-8&callback=sinaSSOController.loginCallBack&cdult=2&domain=weibo.com&returntype=TEXT&client=ssologin.js(v1.4.18)&_=";
        $url .= time().'000';
        return $url;
    }
    private function get_url_arg($url)
    {
        $data = array();
        if(empty($url))
        {
            return $data;
        }
        $parameter = explode('&',end(explode('?',$url)));
        foreach($parameter as $val)
        {
            $tmp = explode('=',$val);
            $data[$tmp[0]] = $tmp[1];
        }
        return $data;
    }
    private function get_verify_code_by_tengxun()
    {
        $random_int = '0.';
        for ($i=0; $i<16; $i++)
        {
            if (15 == $i)
            {
                $random_int .= rand(1, 9);
            }
            else
            {
                $random_int .= rand(0, 9);
            }
        }
        $lang_num = 2052;
        $appid = 46000101;
        $qq = $this->username;
        $check_url = "http://check.ptlogin2.qq.com/check?uin={$qq}&appid={$appid}&ptlang={$lang_num}&r={$random_int}";
        $verify_code = $this->http_use_curl($check_url, 'GET', array(), '', $this->cookie_file_1);
        $verify_code = strtoupper(substr($verify_code, 18, 4));
        return $verify_code;
    }
    private function delete_file_by_tengxun($file)
    {
        if (is_file($file))
        {
            unlink($file);
        }
    }
    private function preprocess_by_tengxun($password,$verifycode)
    {
        return strtoupper(md5($this->md5_3_by_tengxun($password).strtoupper($verifycode)));
    }
    private function md5_3_by_tengxun($str)
    {
        return strtoupper(md5(md5(md5($str,true),true)));
    }
    private function get_access_token()
    {
        global $dsql;
        $sql = "select access_token,wb_skey,password,access_token_secret
            from ".DATABASE_TESTUSER." where 
            wb_akey='{$this->appkey}' and sourceid={$this->sourceid} 
            and name='{$this->username}'";
        $qr = $dsql->ExecQuery($sql);
        if (!empty($qr))
        {
            $result = $dsql->GetArray($qr, MYSQL_NUM);
            $this->password = $result[2];
            $dsql->FreeResult($qr);
            return $result;
        }
        else
        {
            return array();
        }
    }
    private function update_access_token($access_token, $access_token_secret)
    {
        global $dsql;
        $set_arr = array();
        $where_arr = array();
        $set_arr['access_token'] = $access_token;
        $set_arr['access_token_secret'] = $access_token_secret;
        $where_arr['wb_akey'] = $this->appkey;
        $where_arr['sourceid'] = $this->sourceid;
        $where_arr['name'] = $this->username;
        $sql = update_template(DATABASE_TESTUSER, $set_arr, $where_arr);
        $qr = $dsql->ExecQuery($sql);
        if ($qr === false)
        {
            return false;
        }
        else
        {
            $dsql->FreeResult($qr);
            return true;
        }
    }
}
?>
