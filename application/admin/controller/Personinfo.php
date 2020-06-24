<?php

// +----------------------------------------------------------------------
// | 司机管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Personinfo extends Adminbase
{


    //编辑后台菜单
    public function edit(){
        if ($this->request->isPost()) {
            $data = $this->request->post('');
             // dump($data);exit;

            if($data['password']) {
              if(($data['password'] != $data['password_confirm'])){
                   return $this->error('两次输入密码不一致');
              } 
              $data['password'] = md5($data['password']);            
            }else{
              unset($data['password']);
            }
            $result = $this->Verification($data);
            if($result != 1){
               return $this->error($result);
            }         
            
            unset($data['password_confirm']);
            // dump($data);exit;
            if (Db::name('admin')->update($data)) {
                $this->success("修改成功！");
            } else {
               return $this->error('修改失败！');
            }
        } else {
            $this->assign('userInfo', $this->_userinfo);
            return $this->fetch();
        }
    }

    //验证信息完整性
    private function Verification($data){
         $preg_email='/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims';
         $preg_phone='/^1[34578]\d{9}$/ims';
        if(!preg_match($preg_email,$data['email'])){
            return '邮箱格式不正确';
        }else if(!preg_match($preg_phone,$data['phone'])){
            return '手机号不正确';
        }else if(!$data['name']){
            return '真实姓名不能为空';
        }else{
            return 1;
        }

    }


}
