<?php

// +----------------------------------------------------------------------
// | 后台首页
// +----------------------------------------------------------------------
namespace app\seller\controller;

use app\common\controller\Sellerbase;
use think\Db;

class Index extends Sellerbase
{
    //后台首页
    public function index()
    {
        $re = Session('seller_user_auth');
        $data = model('seller/Menu')->getMenuList();
        // dump($data);die;
        // 非超管进行权限移除
        if($re['roleid'] != '1'){
            foreach ($data as $k => $v) {
                // 默认权限
                if ($this->_userinfo['is_rule'] != 1) {
                    $rules  = Db::name('seller_auth_group')->field('rules')->where(['id' =>$this->_userinfo['roleid']])->find()['rules'];
                    $rules1 = Db::name('seller_auth_rule')->where('id','in',$rules)->column('title');
                    if (!empty($v['items'])) {//存在第二级
                        foreach ($v['items'] as $k2 => $v2) {
                            if (!in_array($v2['title'],$rules1)) {
                                unset($data[$k]['items'][$k2]);
                            }
                        }
                    }
                }else{
                    $rule = Session('seller_user_auth')['rules'];
                    if (!empty($v['items'])) {//存在第二级
                        foreach ($v['items'] as $k2 => $v2) {
                            if (!in_array($v2['title'],$rule)) {
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
        $this->assign('userInfo', $this->_userinfo);
        $this->assign("SUBMENU_CONFIG", json_encode($data));
        return $this->fetch();
    }
    // 设置皮肤
    public function set_color()
    {
        if (Request()->isPost())
        {
            $color = input('color');
            Db::name('admin_seller')->where(['userid'=>Session('seller_user_auth')['uid']])->update(['color'=>$color]);
            $auth = Session('seller_user_auth');
            $auth['color']  = $color;
            Session('seller_user_auth',$auth);
            Session('seller_user_auth_sign', data_auth_sign($auth));
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
                    if (Session('seller_user_auth')['pyid'] == '0') {
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
