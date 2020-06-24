<?php
namespace app\api\controller;
use app\api\controller\Base;
use think\Request;
use think\Db;

 
class User extends Base
{
    // 用户注册
    public function userReg()
    {
        $this->auth();
        $data = [
            'type'        => input('type'),
            'phone'       => input('phone'),
            'password'    => input('password'),
            'openid'      => input('openid'),
        ];
        $data = array_filter($data);
        if (empty($data['type'])) {
            $this->msg  = '注册类型不能为空';
            $this->code = '0';
            $this->result(); 
        }
        if ($data['type'] == 'xcx' && empty($data['openid'])) {//小程序
            $this->msg  = 'openid不能为空';
            $this->code = '0';
            $this->result();
        }
        if (empty($data['phone']) || empty($data['password'])) {
            $this->msg  = '手机号或密码不能为空';
            $this->code = '0';
            $this->result();
        }
        $cache = Db::name('user')->where(['phone' => $data['phone']])->find();
        if (!empty($cache)) {
            $this->msg  = '该手机号已注册';
            $this->code = '0';
            $this->result();
        }
        unset($data['type']);
        $data['status']   = 1;
        $data['add_time'] = time();
        $password         = encrypt_password($data['password']);
        $data['encrypt']  = $password['encrypt'];
        $data['password'] = $password['password'];
        $re = Db::name('user')->insert($data);
        if (!$re) {
            $this->msg  = '注册失败';
            $this->code = '0';
            $this->result();
        }else{
            $this->result();
        }
    }
    // 短信验证码
    public function userSendSms()
    {
        $this->auth();
        $data = [
            'phone'     => input('phone'),
        ];
        if (empty($data['phone'])) {
            $this->msg  = '手机号不能为空';
            $this->code = '0';
            $this->result();
        }
        $code = rand(100000,999999);
        $msg  = '【金花贝】'.'您的短信验证码为'.$code.'，若非本人操作请忽略。';
        $res  = blueSendSms($data['phone'],$msg);
        if ($res['code'] == '0') {
            // 短信发送记录
            $log2['phone'] = $data['phone'];
            $log2['content']= $msg;
            $log2['code']   = 'OK';
            $log2['status'] = 1;
            $log2['time']   = time();
            Db::name('sms_log')->insert($log2);
            $this->result(['code' => $code]);
        }else{
            $this->msg  = '验证码发送失败';
            $this->code = '3';
            $this->result();
        }
    }
    // 用户登录并返回token
    public function userLogin()
    {
        $this->auth();
        $data = [
            'phone'     => input('phone'),
            'password'  => input('password'),
        ];
        if (empty($data['phone']) || empty($data['password'])) {
            $this->msg  = '手机号或密码不能为空';
            $this->code = '0';
            $this->result();
        }
        $field = 'id,password,encrypt';
        $cache = Db::name('user')->field($field)->where(['phone' => $data['phone']])->find();
        if (empty($cache)) {
            $this->msg  = '查无此人';
            $this->code = '2';
            $this->result();
        }
        if ($cache['password'] != encrypt_password($data['password'], $cache['encrypt'])) {
            $this->msg  = '密码错误';
            $this->code = '0';
            $this->result();
        }
        $token['id']    = $cache['id'];
        $token['token'] = $this->setToken($cache['id']);
        $this->result($token);
    }
    // 用户token登录验证
    public function userToken()
    {
        $this->auth();
        $data = [
            'token'    => input('token'),
        ];
        if (empty($data['token'])) {
            $this->msg  = 'token不能为空';
            $this->code = '0';
            $this->result();
        }
        $cache = Db::name('user')->field('id,phone,token,time_out')->where(['token' => $data['token']])->find();
        if (empty($cache)) {
            $this->msg  = '该token无效，请重新登录';
            $this->code = '0';
            $this->result();
        }
        if ($cache['time_out'] < time()) {
            $this->msg  = 'token已过期请重新登录';
            $this->code = '0';
            $this->result();
        }
        $token= $this->setToken($cache['id']);
        $this->result(['token' => $token ,'phone' => $cache['phone']]);
    }
    // 用户信息
    public function userInfo()
    {
        $this->auth();
        $data = [
            'id'       => input('uid'),
            'phone'    => input('phone'),
        ];
        $data = array_filter($data);
        if (empty($data['id']) && empty($data['phone'])) {
            $this->msg  = 'uid和phone不能同时为空';
            $this->code = '0';
            $this->result();
        }
        $field = 'id,phone,nickname,sex,qrcode';
        $cache = Db::name('user')->field($field)->where($data)->find();
        if (empty($cache)) {
            $this->msg  = '查无此人';
            $this->code = '2';
            $this->result();
        }
        $this->result($cache);
    }
    // 重置密码
    public function userResetPwd()
    {
        $this->auth();
        $data = [
            'pyid'     => input('pyid'),
            'phone'    => input('phone'),
            'password' => input('password'),
        ];
        if (empty($data['pyid'])) {
            $this->msg  = 'pyid不能为空';
            $this->code = '0';
            $this->result();
        }
        if (empty($data['phone'])) {
            $this->msg  = '手机号不能为空';
            $this->code = '0';
            $this->result();
        }
        if (empty($data['password'])) {
            $this->msg  = '密码不能为空';
            $this->code = '0';
            $this->result();
        }
        $cache = Db::name('user')->field('id')->where(['pyid' => $data['pyid'],'phone' => $data['phone']])->find();
        if (empty($cache)) {
            $this->msg  = '该号码暂未注册';
            $this->code = '0';
            $this->result();
        }
        $password         = encrypt_password($data['password']);
        $data['encrypt']  = $password['encrypt'];
        $data['password'] = $password['password'];
        $data['id']       = $cache['id'];
        $re = Db::name('user')->update($data);
        if ($re) {
            $this->result();
        }else{
            $this->msg  = '重置失败';
            $this->code = '0';
            $this->result();
        }
    }
    // 用户验证
    public function test()
    {   
        // die;
        $url='http://'.$_SERVER['HTTP_HOST'].'/api/user/userInfo';
         $data = [
                'phone'=>13267871755,
                'timestamp'   =>time()
        ];
        $sign = '';
        ksort($data);
        foreach ($data as $key => $value) {
            $sign .= '&' . strval($key) . '=' . strval($value);
        }
        $data['sign']=md5(substr($sign, 1) . $this->cert);
        echo fkd_curl($url,$data);die;
        
    }
}
