<?php

// +----------------------------------------------------------------------
// | 后台首页
// +----------------------------------------------------------------------
namespace app\seller\controller;

use app\common\controller\Sellerbase;
use think\Db;

class User extends Sellerbase
{
    public function index()
    {

    }
    // 会员列表
    public function userList()
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
            if (!empty(input('status'))) {
                if (input('status') == 1) {
                    $map[] = ['partner','eq',0];
                }else{
                    $map[] = ['partner','eq',1];
                }
            }
            if(!empty(input('search_date_start')) && !empty(input('search_date_end'))){
                $StarTtime = strtotime(input('search_date_start'));
                $EndTime   = strtotime(input('search_date_end'));
                $map[] = ['add_time','between',[$StarTtime,$EndTime]];
            }
            $data = model('user')->userList($map);
            $info['code']  = 0;
            $info['count'] = $data['total'];
            $info['data']  = $data['data'];
            $this->ajaxReturn($info);
        }
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


}
