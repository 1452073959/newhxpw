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
class Register extends Controller
{
    //注册api
    public function index()
    {
        if($_POST){

            $data = $_POST['regInfo'];

             if(Db::name('user')->where('phone',$data['phone'])->find()){
                $info['status'] =0;
                $info['code'] = '用户已存在';
                $info['msg'] = 'error001';
             }

            unset($data['repassword']);
            unset($data['account']);

            $passinfo = encrypt_password($data['passWord']); //加密密码

            $data['password'] = $passinfo['password'];
            unset($data['passWord']);

            $data['encrypt'] = $passinfo['encrypt'];

            $data['add_time'] = time();

            $data['status'] = 1;

            if(Db::name('user')->insert($data)){

                $info['status'] = 1;
                $info['code'] = '注册成功';
                $info['msg'] = 'ok';

            }else{

                $info['status'] = 2;
                $info['code'] = '注册失败';
                $info['msg'] = 'error002';
            }

            echo json_encode($info);
        }

    }

}
