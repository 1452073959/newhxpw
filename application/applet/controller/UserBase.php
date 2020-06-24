<?php
namespace app\applet\controller;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
 // //同源策略 跨域请求 头设置
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
use think\Db;
use think\Controller;
use app\applet\controller\Base;
 
class UserBase extends Base
{
    public $menu = [];//首页菜单
    public $token = '';
    public $admininfo = [];
    //初始化验证模块
    protected function initialize(){
        $this->token = input('token');
        $this->checkToken($this->token);
    }
    

    //判断登录状态
    protected function checkToken($token){
        if(!$token){
            $this->json(99,'请先登录');
        }
        $admininfo = Db::name('admin')->where(['token'=>$token])->field('userid,companyid,name,phone,status,rule,pid,token,timeout,roleid')->find();
        if($admininfo && $admininfo['timeout'] > time()){
            $auth_group = array_column(Db::name('auth_group')->select(),null, 'id');
            $admininfo['role_name'] = $auth_group[$admininfo['roleid']]['title'];
            $this->admininfo = $admininfo;
        }else{
            $this->json(99,'请先登录');
        }
    }

    protected function getMenu(){
        $this->menu = [];
    }
}