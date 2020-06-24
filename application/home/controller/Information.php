<?php
// +----------------------------------------------------------------------
// | 前台个人信息验证api接口
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
class Information extends Controller
{
    //个人信息

    public function index()

    {
        $data = array();
        if($_POST){
                $data = $_POST['info'];
                $phone = $_POST['phone'];
                $id = Db::name('user')->where('phone',$phone)->find();

            if($id){
                unset($data['phone']);
                $userinfo = Db::name('user_personal')->where('uid',$id['id'])->find();
                if($userinfo){

                    if(Db::name('user_personal')->where('uid',$id['id'])->update($data)){
                        $row['status'] = 3;
                        $row['code'] = '修改成功';
                        $row['msg'] = 'ok';
                        $row['data'] = $userinfo;
                    }else{
                        $row['status'] = 4;
                        $row['code'] = '还没修改';
                        $row['msg'] = 'error004';
                    }
                }else{
                    $data['uid'] = $id['id'];
                        if(Db::name('user_personal')->insert($data)){
                            $userinfo = Db::name('user_personal')->where('uid',$id['id'])->find();
                            $row['status'] = 1;
                            $row['code'] = '提交成功';
                            $row['msg'] = 'ok';
                            $row['data'] = $userinfo;
                        }else{
                            $row['status'] = 0;
                            $row['code'] = '提交失败';
                            $row['msg'] = 'error';
                        }

                    }
                }else{
                        $row['status'] = 2;
                        $row['code'] = '验证信息已过期,请重新登录';
                        $row['msg'] = 'error002';
                    }
            echo json_encode($row);

        }

    }

    //用户信息接口
    public function userInfo()
    {
        if($_POST){
            $data['phone'] = $_POST['phone'];
            $id = Db::name('user')->where('phone',$_POST['phone'])->find();
            if($id){
                $data['uid'] = $id['id'];
                $info = Db::name('user_personal')->where('uid',$data['uid'])->find();
                if($info){
                    $row['status'] = 5;
                    $row['code'] = '用户信息获取成功';
                    $row['msg'] = 'ok';
                    $row['data'] = $info;
                }else{
                    $row['staus'] = 6;
                    $row['code'] = '用户未提交信息';
                    $row['msg'] = 'error006';
                    $row['data'] = null;
                }
            }
            echo json_encode($row);
        }
    }
}