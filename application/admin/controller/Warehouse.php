<?php

// +----------------------------------------------------------------------
// | 统计报表
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Warehouse extends Adminbase{
    //仓管待领料订单
    public function for_picking(){
        $where = [];
        $u_condition = [];
        $a_condition = [];
        $f_id = [];
        if($this->_userinfo['roleid'] != 1){
            $u_condition['frameid'] = $this->_userinfo['companyid'];
            $a_condition['companyid'] = $this->_userinfo['companyid'];
            $f_id['f_id'] = $this->_userinfo['companyid'];

        }
        if(input('uname')){
            $id = array_column(Db::name('userlist')->where($u_condition)->where('customer_name','like','%'.input('uname').'%')->select(), 'id');
            $where['userid'] = $id;
        }
        if(input('uaddress')){
            $id = array_column(Db::name('userlist')->where($u_condition)->where('address','like','%'.input('uaddress').'%')->select(), 'id');
            if(isset($where['userid'])){
                $where['userid'] = array_intersect($where['userid'],$id);
            }else{
                $where['userid'] = $id;
            }
            
        }
        if(input('jname')){
            $id = array_column(Db::name('admin')->where($a_condition)->where('name','like','%'.input('jname').'%')->select(), 'userid');
            $where['adminid'] = $id;
        }
        $picking_material = Db::name('picking_material')->where($f_id)->where(['status'=>2])->paginate(20,false,['query'=>request()->param()])->each(function($item, $key){
        // $picking_material = Db::name('picking_material')->where($where)->where(['status'=>2])->paginate(20,false,['query'=>request()->param()])->each(function($item, $key){
            // $item['orderinfo'] = Model('offerlist')->get_order_info($item['oid'],2);
            $item['userinfo'] = Db::name('userlist')->where(['id'=>$item['userid']])->find();
            $item['admininfo'] = Db::name('admin')->where(['userid'=>$item['adminid']])->find();
            $item['company'] = Db::name('frame')->where(['id'=>$item['f_id']])->find();
            if($item['userinfo'] && $item['admininfo']){
                $item['addtime'] = date('Y-m-d H:i',strtotime($item['addtime']));
                return $item;
            }
        });

        $this->assign([
            'picking_material' => $picking_material,
            'admininfo' => $this->_userinfo,
        ]);
        return $this->fetch();
    }

    //订单详情
    public function pm_info(){
        $id = input('id');
        $picking_material = Db::name('picking_material')->where(['id'=>$id])->find();
        $data = Db::name('picking_material_info')->where(['pmid'=>$id])->select();
        $userinfo = Db::name('userlist')->where(['id'=>$picking_material['userid']])->find();
        $jadmin = Db::name('admin')->where(['userid'=>$picking_material['adminid']])->find();
        $this->assign('data',$data);
        $this->assign('userinfo',$userinfo);
        $this->assign('jadmin',$jadmin);
        return $this->fetch();
    }
}