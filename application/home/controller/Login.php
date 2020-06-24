<?php
// +----------------------------------------------------------------------
// | 前台登录api接口
// +----------------------------------------------------------------------
namespace app\home\controller;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
 // //同源策略 跨域请求 头设置
header('content-type:text/html;charset=utf8 ');
header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
use think\helper\Hash;
use think\Session;
use think\Controller;
use think\Db;
class Login extends Controller
{
    //登录判断
    public function index()
    {
        if($_POST){

            $info = $_POST;
            $userphone = trim($info['phone']);
            $password = trim($info['passWord']);
            $map['phone'] = $userphone;
            $userInfo = Db::name('user')->where($map)->find();

            if (!$userInfo) {

                $data['status'] = '-1';

            }else {
                //密码判断
                if (!empty($password) && encrypt_password($password, $userInfo['encrypt']) != $userInfo['password']){

                     $data['status']  = '0';

                } else {

                    $data['status']  = '1';
                    $data['data']['phone'] = $userInfo['phone'];
                    $data['data']['password'] = $userInfo['password'];
                    $userInfo['token'] = Token::settoken();
                    $time_out = strtotime("+7 days");
                    $data['token'] = $userInfo['token'];
                    $data['time_out'] = $time_out;

                    Db::name('user')->where('id',$userInfo['id'])->update(array('token' => $userInfo['token'], 'time_out' => $time_out));
                    $this->autoLogin($userInfo);

                }
            }
            echo json_encode($data);
        }


    }

    //获取用户信息
    public function getUserInfo($identifier, $password = null)
    {
        if (empty($identifier)) {
            return false;
        }
        $userInfo = Db::name('user')->where($identifier)->find();
        if (empty($userInfo)) {
            return false;
        }
        //密码验证
        if (!empty($password) && encrypt_password($password) != $userInfo['password']) {
            return false;
        }
        return $userInfo;
    }

    /**
     * 自动登录用户
     */
    public function autoLogin($userInfo)
    {
        /* 记录登录SESSION和COOKIES */
        $auth = [
            'uid' => $userInfo['id'],
            'phone' => $userInfo['phone'],
            'token'=> $userInfo['token'],
        ];
        Session('user_auth', $auth);
        Session('user_auth_sign', data_auth_sign($auth));
    }



}
