<?php

// +----------------------------------------------------------------------
// | 后台函数文件
// +----------------------------------------------------------------------

/**
 * 对用户的密码进行加密
 * @param $password
 * @param $encrypt //传入加密串，在修改密码时做认证
 * @return array/password
 */

function encrypt_password($password, $encrypt = '')
{
    $pwd = array();
    $pwd['encrypt'] = $encrypt ? $encrypt : genRandomString();
    $pwd['password'] = md5(trim($password) . $pwd['encrypt']);
    return $encrypt ? $pwd['password'] : $pwd;
}
/**
p
 */
function p($val)
{
   print_r('<pre>');
   print_r($val);
   print_r('</pre>');
}
//获取字符串里面的数字
function ast($str){
return preg_match('/([0-9]{8})/',$str,$a) ? $a[1] : 0;
}

//判断奇数偶数
function checkNum($num){
  return ($num%2) ? TRUE : FALSE;
}
/**
通过id获取分类
 */
function gettypeid($typeid){
  $res = Db::name('offer_type')->where(['id'=>$typeid])->find();
  if($res){
    return $res['name'];
  }else{
    return 'error not find';
  }
}
/**
通过id获取分公司
 */
function getcid($cid){
    $res = Db::name('frame')->where(['id'=>$cid])->find();
    if($res){
      return $res['name'];
    }else{
      return 'error not find';
    } 
}
/**
通过id获取用户名
 */
function getyh($yhid){
    $res = Db::name('userlist')->where(['id'=>$yhid])->find();
    if($res){
      return $res['name'];
    }else{
      return 'error not find';
    } 
}
/**
通过id获取用户名
 */
function getkong($kid){
    $res = Db::name('space_type')->where(['id'=>$kid])->find();
    if($res){
      return $res['title'];
    }else{
      return 'error not find';
    } 
}
/**
/**
返回json
 */
function Result($status, $msg,$data='') {
         $result['status'] = $status;
         $result['msg'] = $msg;
         $result['data'] = $data;
         exit(json_encode($result));
    }

/**
返回树形结构json
 */
function TreeResult($code, $msg,$data='',$count='') {
         $result['code'] = $code;
         $result['msg'] = $msg;
         $result['data'] = $data;
         $result['count'] = $count;
         exit(json_encode($result));
}

//获取单个汉字拼音首字母。注意:此处不要纠结。汉字拼音是没有以U和V开头的
function getfirstchar($s0){   
    $fchar = ord($s0{0});
    if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($s0{0});
    $s1 = iconv("UTF-8","gb2312", $s0);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $s0){$s = $s1;}else{$s = $s0;}
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17922 and $asc <= -17418) return "I";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return NULL;
    //return $s0;
}
function pinyin_long($zh){  //获取整条字符串所有汉字拼音首字母
    $ret = "";
    $s1 = iconv("UTF-8","gb2312", $zh);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $zh){$zh = $s1;}
    for($i = 0; $i < strlen($zh); $i++){
        $s1 = substr($zh,$i,1);
        $p = ord($s1);
        if($p > 160){
            $s2 = substr($zh,$i++,2);
            $ret .= getfirstchar($s2);
        }else{
            $ret .= $s1;
        }
    }
    return $ret;
}
/**
 * 产生一个指定长度的随机字符串,并返回给用户
 * @param type $len 产生字符串的长度
 * @return string 随机字符串
 */
function genRandomString($len = 6)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9",
    );
    $charsLen = count($chars) - 1;
    // 将数组打乱
    shuffle($chars);
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}



