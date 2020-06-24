<?php
namespace app\api\controller;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
 // //同源策略 跨域请求 头设置
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
use think\Db;
use think\Controller;
 
class Base extends Controller
{
    protected $code = 1;
    protected $msg  = '';
    protected $data = '';
    protected $cert = '';
    //初始化验证模块
    protected function initialize()
    {
        $this->cert = 'fdz2018';
    }
    /**
     * API数据返回，支持跨域方法
     * @return [type] [description]
     */
    protected function result($data = [], $code = null, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => is_null($code) ? $this->code : $code,
            'msg'  => $msg ?: ($this->msg ? $this->msg :'SUCCESS'),
            'time' => $_SERVER['REQUEST_TIME'],
        ];
        $result['data'] = $data ?: $this->data;
        echo json_encode($result,JSON_UNESCAPED_UNICODE);//中文不转译
        exit(0);
    }
    /**
     * 接口签名计算
     * @param  [type] $data [description]
     * @param  [type] $cert [description]
     * @return [type]       [description]
     */
    protected function encodeSign($data, $cert = null)
    {
        $cert = is_null($cert) ? $this->cert : $cert;
        if (is_array($data) && $cert) {
            $sign = '';
            ksort($data);
            foreach ($data as $key => $value) {
                $sign .= '&' . strval($key) . '=' . strval($value);
            }
            return md5(substr($sign, 1) . $cert);
        }
        return null;
    }
    /**
     * 接口回调签名验证
     * @param  [type] $data [description]
     * @param  [type] $sign [缺省时：data中有则取data['sign']]
     * @param  [type] $cert [缺省时：根据data['merid']的值从数据库中获取]
     * @return [type]       [description]
     */
    protected function decodeSign($data = null, $sign = null, $cert = null)
    {
        if (is_null($sign) && isset($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
        }
        if (is_null($this->cert)) {
            // $cert       = Db::name('set')->field('cert')->find()['cert'];
            // $this->cert = $cert;
        }
        if (empty($sign) || empty($this->cert)) {
            return false;
        }
        return $this->encodeSign($data, $this->cert) == strtolower($sign);
    }
    /**
     * 接口调用授权认证
     * @return [type] [description]
     */
    protected function auth()
    {
        $data = array_change_key_case(input('', '', 'trim'), CASE_LOWER);
        // 签名算法验证
        if (!$this->decodeSign($data)) {
            $this->result('', 9999, '签名验证失败');
        }
        // 签名时间验证
        $timestamp = isset($data['timestamp']) ? intval($data['timestamp']) : 0;
        if ($timestamp+120<$_SERVER['REQUEST_TIME']) {
            $this->result('', 9998, '请求时间过期');
        }
        return true;
    }
    // 设置登录秘钥
    protected function setToken($id)
    {
        $time = strtotime("+1 week");
        $token = sha1(md5($id.$time));
        $res = [
            'id'          => $id,
            'time_out'    => $time,
            'token'       => $token,
        ];
        Db::name('user')->update($res);
        return $token ? $token : "";
    } 
}