<?php

// +----------------------------------------------------------------------
// | 后台首页
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;

class User extends Adminbase
{
    public function index()
    {}
    // 会员列表
    public function userList()
    {
         $arr=Db::name('userinfo')->select();
        $this->assign('array',$arr);
        return $this->fetch('userList');
    }
    // 查看会员详细信息
    public function userInfo()
    {
        $id = input('id');
        $this->assign('id',$id);
        $this->assign('user',model('user')->userOne($id));
        return $this->fetch('userInfo');
    }
    // 调整为合伙人
    public function userAdjust()
    {
        $ids = input('uid');
        if (!empty($ids)) {
            $re = model('user')->userSave($ids,['partner'=>1]);
            if ($re == true) {
                $info['status'] = 1;
                $info['info']   = '调整成功';
            }else{
                $info['status'] = 0;
                $info['info']   = '调整失败';
            }
            $this->ajaxReturn($info);
        }
    }
    // 删除会员
    public function userDel()
    {
        if (Request()->isPost()) {
            $id = input('id');
            $re = model('user')->userDel($id);
            if ($re == true) {
                $info['status'] = 1;
                $info['info']   = '删除成功';
            }else{
                $info['status'] = 0;
                $info['info']   = '删除失败';
            }
            $this->ajaxReturn($info);
        }
    }
    // 合伙人列表
    public function userList2()
    {
        if (Request()->isPost()) {
            $map[] = ['status','eq',1];
            if (!empty(input('limit'))) {
                $map['num'] = input('limit');
            }
            if(!empty(input('word'))){
                $word = input('word');
                if(preg_match("/^\d*$/",$word)){
                    $map[] = ['phone','LIKE',"%{$word}%"];
                }else{
                    $map[] = ['name','LIKE',"%{$word}%"];
                }
            }
            if(!empty(input('search_date_start')) && !empty(input('search_date_end'))){
                $StarTtime = strtotime(input('search_date_start'));
                $EndTime   = strtotime(input('search_date_end'))+86399;
                $map[] = ['add_time','between',[$StarTtime,$EndTime]];
            }
            $data = model('user')->userList($map,1);
            $info['code']  = 0;
            $info['count'] = $data['total'];
            $info['data']  = $data['data'];
            $this->ajaxReturn($info);
        }
        return $this->fetch('userList2');
    }
    // 新增合伙人
    public function userAdd()
    {
        if (Request()->isPost()) {
            $data = input('post.');
            $data['add_time'] = time();
            $data['partner']  = 1;
            $re = model('user')->userAdd($data);
            if ($re) {
                $info['status'] = 1;
                $info['info']   = '新增成功';
            }else{
                $info['status'] = 2;
                $info['info']   = '新增失败';
            }
            $this->ajaxReturn($info);
        }
    }
    // 合伙人等级
    public function partnerGrade()
    {
        if (Request()->isPost()) {
            $data = model('user')->gradeList();
            $info['code']  = 0;
            $info['count'] = count($data);
            $info['data']  = $data;
            $this->ajaxReturn($info);
        }
        return $this->fetch('partnerGrade');
    }
    // 编辑、新增等级
    public function gradeSave()
    {
        $data = input('post.');
        $re = model('user')->gradeSave($data);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '操作成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '操作失败';
        }
        $this->ajaxReturn($info);
    }
    // 删除等级
    public function gradeDel()
    {
        if (Request()->isPost()) {
            $id = input('id');
            $re = model('user')->gradeDel($id);
            if ($re == true) {
                $info['status'] = 1;
                $info['info']   = '删除成功';
            }else{
                $info['status'] = 0;
                $info['info']   = '删除失败';
            }
            $this->ajaxReturn($info);
        }
    }
}
