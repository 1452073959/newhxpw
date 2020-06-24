<?php

// +----------------------------------------------------------------------
// | 司机管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Offertype extends Adminbase
{
    //空间工种
    public function index()
    {
        $userinfo = $this->_userinfo;
        $where = [];
        $where['adminid'] = [0,$this->_userinfo['userid']];
        $datas = Db::name('offer_type')->where($where)->where(['type'=>1,'companyid'=>$userinfo['companyid'],'status'=>1])->order('id','desc')->select();
        $this->assign('datas',$datas);   
        return $this->fetch();
    }

    //空间
    public function space_index(){
        $userinfo = $this->_userinfo;
        $where = [];
        $where['adminid'] = [0,$this->_userinfo['userid']];
        $datas = Db::name('offer_type')->where($where)->where(['type'=>2,'companyid'=>$userinfo['companyid'],'status'=>1])->order('id','desc')->select();
        $this->assign('datas',$datas);   
        return $this->fetch();
    }
    
    //新增
    public function ajax_add_word(){
        // var_dump(input());die;
        if(input('add_name') && input('type')){
            $userinfo = $this->_userinfo;
            if($this->_userinfo['roleid'] == 10){
                $adminid = 0;
            }else{
                $adminid = $this->_userinfo['userid'];
            }
            $has = [];
            foreach (input('add_name') as $k => $v) {
                if(empty($v)){
                    continue;
                }
                $offer_type = Db::name('offer_type')->where(['name'=>$v,'type'=>input('type'),'companyid'=>$userinfo['companyid'],'adminid'=>$adminid])->find();
                if($offer_type){
                    if($offer_type['status'] == 0){
                        $has[] = $v;
                    }elseif($offer_type['status'] == 9){
                        Db::name('offer_type')->where(['name'=>$v,'type'=>input('type'),'companyid'=>$userinfo['companyid'],'adminid'=>$adminid])->update(['status'=>1,'addtime'=>time()]);
                    }
                }else{
                    $id = Db::name('offer_type')->insertGetId(['name'=>$v,'type'=>input('type'),'companyid'=>$userinfo['companyid'],'addtime'=>time(),'adminid'=>$adminid]);
                    // echo json_encode(['code'=>1,'msg'=>'添加成功','id'=>$id]);
                }
            }
            echo json_encode(['code'=>1,'msg'=>'添加成功']);
        }else{
             echo json_encode(['code'=>0,'msg'=>'参数错误']);
        }
    }

    //编辑 这个没有匹配到个人.  不建议使用
    public function ajax_edit_word(){
        if(input('name') && input('type') && input('id')){
            $userinfo = $this->_userinfo;
            $res = Db::name('offer_type')->where(['id'=>input('id'),'type'=>input('type'),'companyid'=>$userinfo['companyid']])->update(['name'=>input('name')]);
            if($res){
                echo json_encode(['code'=>1,'msg'=>'修改成功']);
            }else{
                echo json_encode(['code'=>0,'msg'=>'修改失败']);
            }
        }else{
             echo json_encode(['code'=>0,'msg'=>'参数错误']);
        }
    }

    //删除
    public function ajax_delete_word(){
        if(input('id')){
            $userinfo = $this->_userinfo;
            $info = Db::name('offer_type')->where(['id'=>input('id')])->find();
            if($userinfo['roleid'] == 10 && $info['adminid'] == '0'){

            }else{
                if($userinfo['userid'] != $info['adminid']){
                    echo json_encode(['code'=>0,'msg'=>'禁止删除非本人添加的空间']);
                    die;
                }
            }
            $res = Db::name('offer_type')->where(['id'=>input('id')])->update(['status'=>9]);
            if($res){
                echo json_encode(['code'=>1,'msg'=>'删除成功']);
            }else{
                echo json_encode(['code'=>0,'msg'=>'删除失败']);
            }
        }else{
             echo json_encode(['code'=>0,'msg'=>'参数错误']);
        }
    }
}
