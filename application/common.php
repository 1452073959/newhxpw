<?php
// +----------------------------------------------------------------------
// | 全局函数文件
// +----------------------------------------------------------------------

/**
 * 系统缓存缓存管理
 * cache('model') 获取model缓存
 * cache('model',null) 删除model缓存
 * @param mixed $name 缓存名称
 * @param mixed $value 缓存值
 * @param mixed $options 缓存参数
 * @return mixed
 */
function cache($name, $value = '', $options = null)
{
    static $cache = '';
    if (empty($cache)) {
        $cache = \libs\Cache_factory::instance();
    }
    // 获取缓存
    if ('' === $value) {
        if (false !== strpos($name, '.')) {
            $vars = explode('.', $name);
            $data = $cache->get($vars[0]);
            return is_array($data) ? $data[$vars[1]] : $data;
        } else {
            return $cache->get($name);
        }
    } elseif (is_null($value)) {
//删除缓存
        return $cache->remove($name);
    } else {
//缓存数据
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : null;
        } else {
            $expire = is_numeric($options) ? $options : null;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * 获取客户端IP地址
 * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param bool $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_real_ip(){//获取ip

    $ip=false;

    if(!empty($_SERVER['HTTP_CLIENT_IP'])){

        $ip=$_SERVER['HTTP_CLIENT_IP'];

    }

    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){

        $ips=explode (', ', $_SERVER['HTTP_X_FORWARDED_FOR']);

        if($ip){ array_unshift($ips, $ip); $ip=FALSE; }

        for ($i=0; $i < count($ips); $i++){

            if(!eregi ('^(10│172.16│192.168).', $ips[$i])){

                $ip=$ips[$i];

                break;

            }

        }

    }

    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);

}

function getCitys($ip)//获取地区

{
        $url="http://ip.ws.126.net/ipquery?ip=".$ip;
        $ip = iconv("gb2312", "utf-8//IGNORE",fkd_curl($url));
        $data = trim(strrchr($ip, '='),'=');
        preg_match_all("/([\x{4e00}-\x{9fa5}]+)/u", $data, $match);
        $count = count($match[0]);
        if($count == 2){
            return $match[0][1].$match[0][0];
        }else{
            return $match[0];
        }
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array) $data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 解析配置
 * @param string $value 配置值
 * @return array|string
 */
function parse_attr($value = '')
{
    $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
    if (strpos($value, ':')) {
        $value = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k] = $v;
        }
    } else {
        $value = $array;
    }
    return $value;
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 */
function time_format($time = null, $format = 'Y-m-d H:i')
{
    $time = $time === null ? $_SERVER['REQUEST_TIME'] : intval($time);
    return date($format, $time);
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data, $map = array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array) $data;
    foreach ($data as $key => $row) {
        foreach ($map as $col => $pair) {
            if (isset($row[$col]) && isset($pair[$row[$col]])) {
                $data[$key][$col . '_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'parentid', $child = '_child', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

// curl 函数
//用于发送数据
function fkd_curl( $url, $data=null ){
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, FALSE );
    if( $data ){
      curl_setopt( $curl, CURLOPT_POST, 1 );
      curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
    }
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    $res = curl_exec($curl);
    curl_close( $curl );
    return $res;
}

function curlPost($url,$postFields=null){
    // $postFields = json_encode($postFields);//转json格式
    $ch = curl_init ();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));//json版本需要填写  Content-Type: application/json;
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt( $ch, CURLOPT_TIMEOUT,60);
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
    $ret = curl_exec ( $ch );
    if (false == $ret) {
        $result = curl_error(  $ch);
    } else {
        $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
        if (200 != $rsp) {
            $result = "请求状态 ". $rsp . " " . curl_error($ch);
        } else {
            $result = $ret;
        }
    }
    curl_close ( $ch );
    return $result;
}
/**
 * [将Base64图片转换为本地图片并保存]
 * @E-mial wuliqiang_aa@163.com
 * @TIME   2017-04-07
 * @WEB    http://blog.iinu.com.cn
 * @param  [Base64] $base64_image_content [要保存的Base64]
 * @param  [目录] $path [要保存的路径]
 */
function base64_image_content($base64_image_content,$path){
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
        $filename = md5(microtime(true));
        $filepath = "/".date('Ymd') . DIRECTORY_SEPARATOR;
        $type = $result[2];
        $new_file = $path.$filepath;
        if(!file_exists($new_file)){
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($new_file, 0700);
        }
        $new_file = $new_file.$filename.".{$type}";
        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
            return $filepath.$filename.".{$type}";
        }else{
            return false;
        }
    }else{
        return false;
    }
}
// 签名
function rsaSign($args,$path) {
    // $args=array_filter($args);//过滤掉空值
    ksort($args);
    $query  =   '';
    foreach($args as $k=>$v){
        if($k=='SignType'){
            continue;
        }
        if($query){
            $query  .=  '&'.$k.'='.$v;
        }else{
            $query  =  $k.'='.$v;
        }
    }
    $private_key= file_get_contents($path);//私钥
    $pkeyid = openssl_get_privatekey($private_key);
    openssl_sign($query, $sign, $pkeyid);
    openssl_free_key($pkeyid);
    $sign = base64_encode($sign);
    return $sign;
}
/**
 * 私钥签名签名
 * @param content  代签名字符串
 * @param privateKey   
 * @return  签名后的数据
 */
 function __sign($query = array(),$privateKey){
    if( ! is_array( $query ) ){
        return null;
    }
    //排序参数，
    ksort( $query );
    //重新组装参数
    $params = array();
    foreach($query as $key => $value){
        $params[] = $key .'='. $value ;
    }
    $data = implode('&', $params);
    // 私钥密码
    $passphrase = '';
    $key_width = 64;
    $p_key = array();
    //如果私钥是 1行
    if( ! stripos( $privateKey, "\n" )  ){
        $i = 0;
        while( $key_str = substr( $privateKey , $i * $key_width , $key_width) ){
            $p_key[] = $key_str;
            $i ++ ;
        }
    }else{
        //echo '一行？';           
    }
    
    //将一行代码
    $privateKey = "-----BEGIN PRIVATE KEY-----\n" . implode("\n", $p_key) ;
    $privateKey = $privateKey ."\n-----END PRIVATE KEY-----";       
    $pkeyid = openssl_get_privatekey($privateKey);       
    openssl_sign($data, $sign, $pkeyid);
    openssl_free_key($pkeyid);
    $sign = base64_encode($sign);
    return $sign;
}
/**
 * 功能：加密
 * @param $args 加密原文数组
 * return 密文数组
 */
function publicRsaSign($args,$path) {
    $args=array_filter($args);//过滤掉空值
    $public_key= file_get_contents($path);//公钥
    foreach($args as $k=>$v){
        openssl_public_encrypt($v,$encryptStr,$public_key);
        $args[$k] = base64_encode($encryptStr);
    }
    return $args;
}
function rsaEncrypt($content,$path){
    $pubKey = file_get_contents($path);//公钥
    $res = openssl_get_publickey($pubKey);
    //把需要加密的内容，按128位拆开解密
    $result  = '';
    for($i = 0; $i < strlen($content)/128; $i++  ) {
        $data = substr($content, $i * 128, 128);
        openssl_public_encrypt ($data, $encrypt, $res);
        $result .= $encrypt;
    }
    $result = base64_encode($result);
    openssl_free_key($res);
    return $result;
}
/**
     * 功能： 验证签名
     * @param $args 需要签名的数组
     * @param $sign 签名结果
     * return 验证是否成功
     */ 
function rsaVerify($args, $sign,$path) {
       $args=array_filter($args);//过滤掉空值
    ksort($args);
    $query  =   '';
    foreach($args as $k=>$v){
        if($k=='sign_type' || $k=='sign'){
            continue;
        }
        if($query){
            $query  .=  '&'.$k.'='.$v;
        }else{
            $query  =  $k.'='.$v;
        }
    }
    $sign = base64_decode($sign);
    $public_key= file_get_contents($path);//公钥
    $pkeyid = openssl_get_publickey($public_key);
    if ($pkeyid) {
        $verify = openssl_verify($query, $sign, $pkeyid);
        openssl_free_key($pkeyid);
    }
    if($verify == 1){
        return true;
    }else{
        return false;
    }
}
// 阿里短信验证码发送
function sendSms($code,$mobile,$SignName)
{
    $KeyId  = config('accessKeyId');
    $Secret = config('accessKeySecret');
    if (empty($code) || empty($mobile) || empty($KeyId) || empty($Secret) || empty($SignName)) {
        return false;
    }
    $data = [
        'PhoneNumbers'  => $mobile,
        'SignName'      => $SignName,//短信签名
        'TemplateCode'  => 'SMS_150742584',
        'TemplateParam' => '{"code":"'.$code.'"}',
        'RegionId'      => 'cn-hangzhou',
        'Action'        => 'SendSms',
        'Version'       => '2017-05-25',
    ];
    $helper  = new util\sms\SignatureHelper;
    $content = $helper->request($KeyId,$Secret,"dysmsapi.aliyuncs.com",$data,false);
    return $content;
}
// 创蓝短信验证码
function blueSendSms($mobile,$msg)
{
    $account  = config('account');
    $password = config('password');
    if (empty($mobile)  || empty($account) || empty($password) || empty($msg)) {
        return false;
    }
    $data = [
        'account'  => $account,
        'password' => $password,//短信签名
        'msg'      => urlencode($msg),
        'phone'    => $mobile,
        'report'   => 'true',
    ];
    $url = 'https://smssh1.253.com/msg/send/json';
    return json_decode(curlPost($url,json_encode($data)),true); 
}
/**
*将参数拼接成自动提交表单
*/
function submitFormStr($query = array(),$url){
    $content = "<form id ='zqwssubmit' name='zqwssubmit' action='" . $url . "' method='post'>";
    foreach($query as $key => $value){
        $content = $content . "<input type ='hidden' name='" . $key . "' value='" . $value . "'/>" ;
    }
    
    $content = $content . "<input type='submit' value='确认' style='display:none;'></form>" ;
    $content = $content . "<script>document.forms['zqwssubmit'].submit();</script>" ;
    return $content;
}

/**
 * 针对批量id进行处理  '1,2,3' 转换为数组批量
 * @param type $ids
 * @return boolean
 */
function ex_array($ids){
    //转换为数组
    $ids_array = explode(',', $ids);
    //数组值转为整数型
    $ids_array = array_map("intval", $ids_array);
    if(empty($ids_array)||  in_array(0, $ids_array)){
        return FALSE;
    }else{
        return $ids_array;
    }
}

/**
 * 设置默认头像图片
 */
function defaultimg($img = "",$default = "")
{
    if($img)
    {
        if(preg_match('/^http(s)?:\\/\\/.+/',$img))
        {
            return $img;
        }else
        {
            return C('web_url')."public/Mobile/images/head.png";
        }
    }else{
        return C('web_url')."public/Mobile/images/head.png";
    }
}


/**
 * 取得随机数
 *
 * @param int $length 生成随机数的长度
 * @param int $numeric 是否只产生数字随机数 1是0否
 * @return string
 */
function random($length, $numeric = 0) {
    $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}

/**
 * @param int $length
 * @return string
 *
 * 生成唯一字符串
 */
function generate_sj( $length = 8 ) {
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $password = '';
    for ( $i = 0; $i < $length; $i++ )
    {
        // 这里提供两种字符获取方式
        // 第一种是使用 substr 截取$chars中的任意一位字符；
        // 第二种是取字符数组 $chars 的任意元素
        // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }

    return $password;
}