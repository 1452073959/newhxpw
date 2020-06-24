<?php


// +----------------------------------------------------------------------
// | 后台控制模块
// +----------------------------------------------------------------------
namespace app\common\controller;
use think\Db;
use app\admin\model\AdminUser as AdminUser_model;

class Adminbase extends Base
{
    public $_userinfo; //当前登录账号信息
    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->AdminUser_model = new AdminUser_model;
        if (defined('UID')) {
            return;
        }
        define('UID', (int) $this->AdminUser_model->isLogin());
        //验证登录
        if (false == $this->competence()) {
            //跳转到登录界面
            if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){ 
                // ajax 请求的处理方式 
                $this->error('登录状态失效，请重新登录','admin/login/index');
            }else{ 
                // 正常请求的处理方式 
                $this->redirect('admin/login/index');
            };
           
            // $this->redirect('admin/login/index');
        } else {
            //是否超级管理员
            if (!$this->AdminUser_model->isAdministrator()) {
                //检测访问权限
                $rule = strtolower($this->request->module() . '/' . $this->request->controller() . '/' . $this->request->action());
                // 自定义权限
                if (Session('admin_user_auth')['is_rule'] == 1) {
                    $authList = Db::name('auth_rule')->where('title','in',Session('admin_user_auth')['rules'])->column('name');
                    if (!in_array($rule,$authList)) {
                        // $this->error('未授权访问!');
                        $this->error('未授权访问!', url('admin/login/index',['type'=>1]));
                        // echo "<script>alert('未授权访问!')</script>";die;
                    }
                }else{
                    if (!$this->checkRule($rule, [1, 2])) {
                        $this->error('未授权访问!', url('admin/login/index',['type'=>1]));
                        // echo "<script>alert('未授权访问!')</script>";die;
                    }
                }
            }
        }
    }

    //验证登录
    private function competence()
    {
        //检查是否登录
        $uid = (int) $this->AdminUser_model->isLogin();
        if (empty($uid)) {
            return false;
        }
        //获取当前登录用户信息
        $userInfo = $this->AdminUser_model->getUserInfo($uid);
        if (empty($userInfo)) {
            $this->AdminUser_model->logout();
            return false;
        }
        $this->_userinfo = $userInfo;
        //是否锁定
        /*if (!$userInfo['status']) {
        User::getInstance()->logout();
        $this->error('您的帐号已经被锁定！', url('admin/index/login'));
        return false;
        }*/
        return $userInfo;

    }
	public function newcheckrule(){
		//是否超级管理员
		if (!$this->AdminUser_model->isAdministrator()) {
		    //检测访问权限
		    $rule = strtolower($this->request->module() . '/' . $this->request->controller() . '/' . $this->request->action());
		    // 自定义权限
		    if (Session('admin_user_auth')['is_rule'] == 1) {
		        $authList = Db::name('auth_rule')->where('title','in',Session('admin_user_auth')['rules'])->column('name');
		        if (!in_array($rule,$authList)) {
		            // $this->error('未授权访问!');
                    $this->error('未授权访问!', url('admin/login/index',['type'=>1]));
		            // echo "<script>alert('未授权访问!')</script>";die;
		        }
		    }else{
		        if (!$this->checkRule($rule, [1, 2])) {
		            // $this->error('未授权访问!');
                    $this->error('未授权访问!', url('admin/login/index',['type'=>1]));
		            // echo "<script>alert('未授权访问!')</script>";die;
		        }
		    }
		}
		return 333;
	}
    /**
     * 权限检测
     * @param string  $rule    检测的规则
     * @param string  $mode    check模式
     * @return boolean
     */
    final protected function checkRule($rule, $type = AuthRule::RULE_URL, $mode = 'url')
    {
        static $Auth = null;
        if (!$Auth) {
            $Auth = new \libs\Auth();
        }
        if (!$Auth->check($rule, UID, $type, $mode)) {
            return false;
        }
        return true;
    }

    protected function qukong($val){
        $kongzhi = json_decode($val,true);
        foreach ($kongzhi as $key => $value) {
            if($value[0]==null && $value[1]==null){
                unset($kongzhi[$key]);
            }else{
                $kongzhi[$key]['info'] =  Db::name('materials')->where(['name'=>$value[0],'frameid'=>6])->find();
                if(empty($kongzhi[$key]['info'])) unset($kongzhi[$key]);
            }
        }
        return  $kongzhi;
    }

}
