<?php
// +----------------------------------------------------------------------
// | 首页
// +----------------------------------------------------------------------
namespace app\applet\controller;
use think\Db;
use think\Controller;
use app\applet\controller\Base;
use think\facade\Cache;
class Index extends Base{
    public function login(){
        $username = input('username');
        $pwd = input('pwd');
        $admininfo = Db::name('admin')->where(['username'=>$username])->find();

        if(Cache::get('login_'.$this->request->ip().$username) >= 5){
            $this->json(1,'连续错误5次，请十分钟后再试');
        }
        if($admininfo['password'] == md5($pwd)){
            if ($admininfo['status'] == 0) {
                $this->json(1,'账号已停用');
            }
            $token = $this->setToken($admininfo['userid']);
            if($token){
                $applet_menu = array_column(Db::name('applet_menu')->where(['status'=>1])->order('pid','asc')->order('sort','asc')->order('id','asc')->select(), null,'id') ;
                $menu = [];
                foreach($applet_menu as $k=>$v){
                    if($v['auth']){
                        $v['auth'] = explode(',', $v['auth']);
                        if(!in_array($admininfo['roleid'],$v['auth'])){
                            continue;
                        }else{
                            unset($v['auth']);
                        }
                    }else{
                        continue;
                    }
                    
                    if($v['pid'] == 0){
                        $menu[$v['id']] = $v;
                    }else{
                        if(isset($menu[$v['pid']])){
                            $menu[$v['pid']]['child'][] = $v;
                        }
                    }
                }
                $auth_group = array_column(Db::name('auth_group')->select(),null, 'id');
                $admininfo['role_name'] = $auth_group[$admininfo['roleid']]['title'];
                Cache::rm('login_'.$this->request->ip().$username);
                $this->json(0,'登录成功',['token'=>$token,'admininfo'=>$admininfo,'menu'=>$menu]);
            }else{
                 $this->json(1,'获取token失败');
            }
        }else{
            if(Cache::get('login_'.$this->request->ip().$username)){
                Cache::set('login_'.$this->request->ip().$username,Cache::get('login_'.$this->request->ip().$username)+1,600);
                $this->json(1,'账号/密码错误（'.Cache::get('login_'.$this->request->ip().$username).'/5）');
            }else{
                Cache::set('login_'.$this->request->ip().$username,1,600);
                $this->json(1,'账号/密码错误（1/5）');
            }
            $this->json(1,'账号/密码错误');
        }
    }
}