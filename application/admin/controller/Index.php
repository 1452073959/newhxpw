<?php

// +----------------------------------------------------------------------
// | 后台首页
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;

class Index extends Adminbase
{

    //教学视频
    public function video(){
        $admininfo = $this->_userinfo;
        $this->assign('admininfo', $admininfo);

        return $this->fetch();
    }
    //后台首页
    public function index()
    {
         
        // @$status=$_SESSION['status'];
        // if($status&&$status==1){
        //     $a=time();
        //     $tt=$a-$_SESSION['time'];
        //     if($tt>3600){
        //         unset($_SESSION['status']);
        //         session_destroy();
        //     }
           
        // }else{
        //    $this->redirect('Login/index');
        // }

        $re = Session('admin_user_auth');
        $data = model('admin/Menu')->getMenuList();
         // dump($this->_userinfo); 
        // dump($data);die;
        // 非超管进行权限移除
        if($re['roleid'] != '1'){
            // echo $this->_userinfo['is_rule'];exit;
            foreach ($data as $k => $v) {
                // 默认权限
                if ($this->_userinfo['is_rule'] != 1) {
                    $rules  = Db::name('auth_group')->field('rules')->where(['id' =>$this->_userinfo['roleid']])->find()['rules'];
                    // $rules1 = Db::name('auth_rule')->where('id','in',$rules)->column('title');
                    $rules_url = Db::name('auth_rule')->where('id','in',$rules)->column('name');
                    if (!empty($v['items'])) {//存在第二级
                        foreach ($v['items'] as $k2 => $v2) {

                            if (!in_array($v2['name'],$rules_url)) {
                                unset($data[$k]['items'][$k2]);
                            }
                            // var_dump($v2);die;
                            if(isset($v2['items'])){
                                foreach($v2['items'] as $k3=>$v3){
                                    if (!in_array($v3['name'],$rules_url)) {
                                        unset($data[$k]['items'][$k2]['items'][$k3]);
                                    }
                                }
                            }
                            
                        }
                    }
                }else{
                    $rule = Session('admin_user_auth')['rules'];
                    if (!empty($v['items'])) {//存在第二级
                        foreach ($v['items'] as $k2 => $v2) {
                            if (!in_array($v2['name'],$rule)) {
                                unset($data[$k]['items'][$k2]);
                            }
                        }
                    }
                }
            }
            foreach ($data as $k => $v) {
                if (empty($v['items'])) {
                    unset($data[$k]);
                }
                if ($v['title'] == '公告') {
                    unset($data[$k]);
                }
            }
        }
        


        // dump($data);
        $this->assign('userInfo', $this->_userinfo);

        $this->assign("SUBMENU_CONFIG", json_encode($data));

        // dump($data);
        return $this->fetch();
    }

      //退出登录
    public function logout()
    {
       
        if ($_SESSION) {
            session_destroy();
            return json(['stauts'=>1,'msg'=>'清除成功']);
        }
    }


    // 设置皮肤
    public function set_color()
    {
        if (Request()->isPost()) {
            $color = input('color');
            Db::name('admin')->where(['userid'=>Session('admin_user_auth')['uid']])->update(['color'=>$color]);
            $auth = Session('admin_user_auth');
            $auth['color']  = $color;
            Session('admin_user_auth',$auth);
            Session('admin_user_auth_sign', data_auth_sign($auth));
        }
    }


    //公告列表
    public function notice()
    {
        if (Request()->isPost()) {
            $limit = input('limit');
            if (empty($limit)) {
                $count = Db::name('notice_list')->count();
                $info['status'] = 1;
                $info['count']  = $count;
                $this->ajaxReturn($info);
            }else{
                $data = Db::name('notice_list')->select();
                $html = '';
                foreach ($data as $k => $v) {
                    if (Session('admin_user_auth')['pyid'] == '0') {
                        $html = $html.'<li data-method="offset" class="layui-btn news" data-id="'.$v['id'].'"><span style="display: inline-block;width: 300px;float: right;text-align: right;">'.date('Y-m-d H:i:s',$v['time']).'</span><span>通知：'.$v['title'].'</span></li><span style="float:right;color:red;" onclick="del_notice('.$v['id'].')">删除</span><hr />';
                    }else{
                        $html = $html.'<li data-method="offset" class="layui-btn news" data-id="'.$v['id'].'"><span style="display: inline-block;width: 300px;float: right;text-align: right;">'.date('Y-m-d H:i:s',$v['time']).'</span><span>通知：'.$v['title'].'</span></li><hr />';
                    }
                }
                $info['status'] = 1;
                $info['info']   = $html;
                $this->ajaxReturn($info);
            }
        }
        return $this->fetch();
    }
    // 新增公告
    public function add_notice()
    {
        if (Request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            $re = Db::name('notice_list')->insert($data);
            if ($re) {
                $info['status'] = 1;
                $info['info']   = '添加成功';
                $this->ajaxReturn($info);
            }else{
                $info['status'] = 2;
                $info['info']   = '添加失败';
                $this->ajaxReturn($info);
            }
        }
    }
    // 删除公告
    public function del_notice()
    {
        if (Request()->isPost()) {
            $id = input('id');
            $re = Db::name('notice_list')->delete($id);
            if ($re) {
                $info['status'] = 1;
                $info['info']   = '删除成功';
                $this->ajaxReturn($info);
            }else{
                $info['status'] = 2;
                $info['info']   = '删除失败';
                $this->ajaxReturn($info);
            }
        }
    }
    // 查看公告详情
    public function get_news()
    {
        $id = input('id');
        if (!empty($id)) {
            $data = Db::name('notice_list')->where(['id' => $id])->find();
            $data['time'] = date('Y-m-d H:i:s',$data['time']);
            $info['status'] = 1;
            $info['info']   = $data;
            $this->ajaxReturn($info);
        }
    }
    //检测是否有未读消息
    public function checkUnread()
    {
        echo 321;
    }

}
