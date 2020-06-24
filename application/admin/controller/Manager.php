<?php

namespace app\admin\controller;

use app\admin\model\AdminUser;
use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

/**
 * 管理员管理
 */
class Manager extends Adminbase
{
    // protected function initialize()
    // {
    //     parent::initialize();
    //     $this->AdminUser = new AdminUser;
    // }

    //账号管理  //人事专用
    public function account_management(){
        $admininfo = $this->_userinfo;
        $where = [];
        $condition = [];
        $where['companyid'] = $admininfo['companyid'];
        $where['roleid'] = [2,13];
        if(input('username')){
            $condition[] = ['username','like','%'.trim(input('username')).'%'];
        }
        if(input('name')){
            $condition[] = ['name','like','%'.trim(input('name')).'%'];
        }
        $datas = Db::name('admin')->where($where)->where($condition)->order('userid','desc')->paginate(20,false,['query'=>request()->param()]);
        $personnel = Db::name('personnel')->where(['fid'=>$admininfo['companyid']])->select();
        $this->assign("admininfo", $admininfo);
        $this->assign("personnel", $personnel);
        $this->assign("datas", $datas);
        return $this->fetch();
    }
    /**
     * 添加管理员 //人事专用
     */
    public function account_add(){
        $admininfo = $this->_userinfo;
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.insert');
            if ($result !== true) {
                return $this->error($result);
            }
            unset($data['password_confirm']);
            $data['roleid'] = 2;//报价师  目前只能添加报价师
            $data['companyid'] = $admininfo['companyid'];
            $data['password'] = md5($data['password']);
            $data['addid'] =$admininfo['userid'];
            if ($res = Db::name('admin')->insert($data)) {
                $this->success("添加账号成功！", url('admin/manager/account_management'));
            } else {
                $this->error('添加失败！');
            }
        } else {
            return $this->fetch();
        }
    }
    /**
     * 绑定管理员 //人事专用
     */
    public function account_edit(){
        $admininfo = $this->_userinfo;
        if (input('pid') && input('aid')) {
            $personnel = Db::name('personnel')->where(['id'=>input('pid')])->find();
            $info = [];
            $info['name'] = $personnel['name'];
            $info['phone'] = $personnel['phone'];
            $info['email'] = $personnel['email'];
            $info['pid'] = input('pid');
            if ($res = Db::name('admin')->where(['userid'=>input('aid')])->update($info)) {
                $this->success("修改成功！", url('admin/manager/account_management'));
            } else {
                $this->error('添加失败！');
            }
        } else {
            $this->error('添加失败！');
        }
    }

    /**
     * 管理员管理列表
     */
    public function index(){
        $where = [];
        if(input('name')){
            $where[] = ['username','like','%'.input('name').'%'];
        }
        if(input('companyid')){
            $where[] = ['companyid','=',input('companyid')];
        }
        if(input('roleid')){
            $where[] = ['roleid','=',input('roleid')];
        }

        $User = Db::name("admin")->where($where)->order('companyid','asc')->order('userid','asc')->select();
        $company = Db::name('frame')->field('id,name')->where(array('levelid'=>3))->select();
        $this->assign("Userlist", $User);
        $this->assign("company", $company);
        $this->assign("roles", model('admin/AuthGroup')->getGroups());
        return $this->fetch();
    }

    // 分公司选择
        public function myquery(){

          if (request()->isAjax()){
             $User = Db::name("admin")->where(array('companyid'=>input('value')))->order(array('userid' => 'ASC'))->select();
             if($User){
              $newdata = array();
             //把id转换文字
             foreach ($User as $k => $v) {
                 $newdata[$k] = $v;
                 $newdata[$k]['companyid'] = getcid($v['companyid']);
                 $newdata[$k]['roleid'] = model('admin/AuthGroup')->getRoleIdName($v['roleid']);

             }
                  // dump($newdata);
              Result(0,'查询成功',$newdata);
             }else{
                Result(1,'查询不到数据');
             }
          }else{
                Result(1,'请求失败');
          }

       }
    /**
     * 添加管理员
     */
    public function add()
    {
        $login = $this->_userinfo;
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $result = $this->validate($data, 'AdminUser.insert');

            if ($result !== true) {
                return $this->error($result);
            }
            if($login['roleid']==1) {
                if (!$data['companyid']) {
                    return $this->error('请选择所属公司');
                }
            }
//            if($login['roleid']!=1){
//                $role=['2','11','13','14','15','16','19','23','24'];
//                if(!in_array($data['roleid'],$role)){
//                    return $this->error('该岗位分公司无权限添加!');
//                }
//            }
            unset($data['password_confirm']);
            unset($data['nickname']);
            $data['roleid'] = $data['roleid'];
            if($login['roleid']!=1){
                $data['companyid'] = $login['companyid'];
            }else{
                $data['companyid'] = $data['companyid'];
            }
            $data['password'] = md5($data['password']);
            // $data = array_values($data);
            // $appinfo['username'] = $data['username'];
            // $appinfo['password'] = $data['password'];
            // $appinfo['email'] = $data['email'];
            // $appinfo['nickname'] = $data['nickname'];
            // $appinfo['phone'] = $data['phone'];
            // $appinfo['roleid'] = $data['roleid'];
            // dump($data);exit;

            if ($res = Db::name('admin')->insertGetId($data)) {
                if($login['roleid']!=1){
                    $log=[];
                    $log['operator']=$login['userid'];
                    $log['cname']=$res;
                    $log['description']='创建';
                    $log['operate_time']=date('Y-m-d H:i:s');
                    $log['ip']=$_SERVER["REMOTE_ADDR"];
                    Db::name('zlogs')->data($log)->insert();
                    $this->success("添加管理员成功！", url('admin/branch_manager/index'));
                }
                $this->success("添加管理员成功！", url('admin/manager/index'));
            } else {
                // $error = Db::name('admin')->getError();
                $this->error('添加失败！');
            }

        } else {
            $company = Db::name('frame')->field('id,name')->where(array('levelid'=>3))->select();
               
            $this->assign("company",$company);
            $this->assign("login",$login);
            $roles=model('admin/AuthGroup')->getGroups()->toarray();
            if($login['roleid']!=1){
               unset($roles['0']);
               unset($roles['2']);
               unset($roles['4']);
               unset($roles['9']);
               unset($roles['10']);
               unset($roles['12']);
               unset($roles['13']);
               unset($roles['14']);
               unset($roles['16']);
               unset($roles['15']);
               unset($roles['11']);
            }
            $this->assign("roles",$roles);
//            $this->assign("roles", model('admin/AuthGroup')->getGroups());

            return $this->fetch();
        }
    }
     /**
     * 选择公司
     */
    public function ajaxquery(){
          if ($this->request->isPost()){
              $sres = Db::name('frame')->where(array('levelid'=>3))->where('name','like',"%".input('value')."%")->select();
              if($sres){
                 Result(0,'查询成功',$sres);
              }else{
                 Result(1,'没有查询到该公司');
              }
          }
    } 

    /**
     * 管理员编辑
     */
    public function edit()
    {
        $admininfo = $this->_userinfo;
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.update');
            if ($result !== true) {
                return $this->error($result);
            }
            // var_dump($data);die;
            
            if(!empty($data['password'])){
                $data['password'] = md5($data['password']);
            }else{
                unset($data['password']);
            }
            if($admininfo['roleid'] != 1){
                unset($data['companyid']);
                unset($data['username']);
                unset($data['roleid']);
                unset($data['rule']);
                unset($data['status']);
                unset($data['userid']);
            }
            $data['name'] = $data['nickname'];
            unset($data['password_confirm']);
            unset($data['nickname']);
//             dump($data);exit;
            $data['userid']=Db::name('admin')->where(['userid'=>input('userid')])->value('userid');
            if (Db::name('admin')->where(['userid'=>input('userid')])->update($data) !== false) {
                $login = $this->_userinfo;
                if($login['roleid']!=1){
                    $log=[];
                    $log['operator']=$login['userid'];
                    $log['cname']=$data['userid'];
                    $log['description']='更新';
                    $log['operate_time']=date('Y-m-d H:i:s');
                    $log['ip']=$_SERVER["REMOTE_ADDR"];
                    Db::name('zlogs')->data($log)->insert();
                }
                $this->success("修改成功！");
            } else {
                $this->error(Db::name('admin')->getError() ?: '修改失败！');
            }
        }else{
            if($admininfo['roleid'] == 1){
                $id = input('id');
            }else{
//                $id = $admininfo['userid'];
                $id = input('id');
            }
            
            $data = Db::name('admin')->where(array("userid" => $id))->find();
            if (empty($data)) {
                $this->error('该信息不存在！');
            }
            $this->assign("data", $data);
            $this->assign("admininfo", $admininfo);
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

     /**
     * 管理员禁用启用
     */
    public function bankai()
    {
        $allurl = input('param.');
        if($allurl['status']==1){
            $data['status'] = 0;
        }else{
            $data['status'] = 1;
        }
        if (Db::name('admin')->where('userid',$allurl['id'])->update($data)){
            if($data['status'] === 0){
                $login = $this->_userinfo;
                if($login['roleid']!=1){
                    $log=[];
                    $log['operator']=$login['userid'];
                    $log['cname']=$allurl['id'];
                    $log['description']='禁用';
                    $log['operate_time']=date('Y-m-d H:i:s');
                    $log['ip']=$_SERVER["REMOTE_ADDR"];
                    Db::name('zlogs')->data($log)->insert();
                }
                $this->success("禁用成功！");
            }else{
                $login = $this->_userinfo;
                if($login['roleid']!=1){
                    $log=[];
                    $log['operator']=$login['userid'];
                    $log['cname']=$allurl['id'];
                    $log['description']='启用';
                    $log['operate_time']=date('Y-m-d H:i:s');
                    $log['ip']=$_SERVER["REMOTE_ADDR"];
                    Db::name('zlogs')->data($log)->insert();
                }
                $this->success("启用成功！");
            }
        } else {
            $this->error('操作失败！');
        }     
        
    }

    /**
     * 管理员删除
     */
    public function del()
    {
        $id = $this->request->param('id/d');
        if (Db::name('admin')->where('userid',$id)->delete()){
            $this->success("删除成功！");
        } else {
            $this->error(Db::name('admin')->getError() ?: '删除失败！');
        }
    }

}
