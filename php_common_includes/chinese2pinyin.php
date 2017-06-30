<?php
class Helper_Spell{
    public $spellArray = array();

    static public function getArray() {

        $sRealPath = realpath('../');
        $dir = $sRealPath."/../../php_common_includes/";
        return unserialize(file_get_contents($dir.'pytable_without_tune.txt'));
    }
    /**
     * @desc 获取字符串的首字母
     * @param $string 要转换的字符串
     * @param $isOne 是否取首字母
     * @param $upper 是否转换为大写
     * @return string
     * 
     * 例如：getChineseFirstChar('我是作者') 首字符全部字母+小写
     * return "wo"
     * 
     * 例如：getChineseFirstChar('我是作者',true) 首字符首字母+小写
     * return "w"
     * 
     * 例如：getChineseFirstChar('我是作者',true,true) 首字符首字母+大写
     * return "W"
     * 
     * 例如：getChineseFirstChar('我是作者',false,true) 首字符全部字母+大写
     * return "WO"
     */
    static public function getChineseFirstChar($string,$isOne=false,$upper=false) {
        $spellArray = self::getArray();
        $str_arr = self::utf8_str_split($string,1); //将字符串拆分成数组

        if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$str_arr[0])) { //判断是否是汉字
            $chinese = $spellArray[$str_arr[0]];
            $result = $chinese[0];
        }else {
            $result = $str_arr[0];
        }

        $result = $isOne ? substr($result,0,1) : $result; 

        return $upper?strtoupper($result):$result;
    }

    /**
     * @desc 将字符串转换成拼音字符串
     * @param $string 汉字字符串
     * @param $isOne 是否取首字母
     * @param $upper 是否大写
     * @return string
     * 
     * 例如：getChineseChar('我是作者'); 全部字符串+小写
     * return "wo shi zuo zhe"
     * 
     * 例如：getChineseChar('我是作者',true); 首字母+小写
     * return "w s z z"
     * 
     * 例如：getChineseChar('我是作者',true,true); 首字母+大写
     * return "W S Z Z"
     * 
     * 例如：getChineseChar('我是作者',false,true); 首字母+大写
     * return "WO SHI ZUO ZHE"
     */
    static public function getChineseChar($string,$isOne=false,$upper=false, $separator=' ', $firstUpper=false) {
        //global $spellArray;
        $spellArray = self::getArray();
        $str_arr = self::utf8_str_split($string,1); //将字符串拆分成数组
        $result = array();
        foreach($str_arr as $char)
        {
            if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$char))
            {
                $chinese = $spellArray[$char];
                $chinese  = $chinese[0];
            }else{
                $chinese=$char;
            }
            $chinese = $isOne ? substr($chinese,0,1) : $chinese;
            $result[] = $upper ? strtoupper($chinese) : $chinese;
        }
        if($firstUpper){
            $tmpstr = implode($separator, $result);
            return strtoupper(substr($tmpstr,0,1)).substr($tmpstr,1,strlen($tmpstr)-1);
        }
        else{
            return implode($separator, $result);
        }
    }
    /**
     * @desc 将字符串转换成数组
     * @param $str 要转换的数组
     * @param $split_len
     * @return array
     */
    private function utf8_str_split($str,$split_len=1) {
        if(!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1) {
            return FALSE;
        }

        $len = mb_strlen($str, 'UTF-8');

        if ($len <= $split_len) {
            return array($str);
        }
        preg_match_all('/.{'.$split_len.'}|[^\x00]{1,'.$split_len.'}$/us', $str, $ar);
        return $ar[0];
    }
}
//
// $test = new Helper_Spell();
// $res = $test->getChineseChar('北京', false, false, '', true);
// var_dump($res);
?>
