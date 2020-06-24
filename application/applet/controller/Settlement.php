<?php
// +----------------------------------------------------------------------
// | 仓管
// +----------------------------------------------------------------------
namespace app\applet\controller;
use think\Db;
use think\Controller;
use app\applet\controller\UserBase;
 
class Settlement extends UserBase{

    //获取结算单
    public function getSettlement(){
        $uid = input('uid');

        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find();
        if(!$userinfo || !$userinfo['oid']){
            $this->json(2,'客户信息有误');
        }
        if($userinfo['work_status'] != '待结算' && $userinfo['work_status'] != '结算待审核' && $userinfo['work_status'] != '结算失败'){
            // $this->json(2,'请先验收所有工种后再申请结算');
        }
        $artificial = Db::name('order_project')->where(['o_id'=>$userinfo['oid']])->select();
        $worker_wage = [];//拼装数组
        foreach($artificial as $k=>$v){
            if(!isset($worker_wage[$v['type_of_work']]['due_wage'])){
                $worker_wage[$v['type_of_work']]['due_wage'] = 0;
            }
            $worker_wage[$v['type_of_work']]['due_wage'] += $v['labor_cost']*$v['num'];
        }

        $settlement = Db::name('settlement')->where(['uid'=>$uid])->order('id','desc')->find();
        if(!$settlement){
            $this->json(0,'success',['worker_wage'=>$worker_wage,'settlement'=>[]]);
        }
        $settlement_worker = Db::name('settlement_worker')->where(['sid'=>$settlement])->select();
        foreach($settlement_worker as $k=>$v){
            if(isset($worker_wage[$v['type_of_work']])){
                $worker_wage[$v['type_of_work']]['wage'] = $v['wage'];
                $worker_wage[$v['type_of_work']]['rent'] = $v['rent'];
                $worker_wage[$v['type_of_work']]['remote'] = $v['remote'];
            }
            
        }
        $this->json(0,'success',['worker_wage'=>$worker_wage,'settlement'=>$settlement]);
    }

    //提交结算申请
    public function applySettlement(){
        $uid = input('uid');
        $data = input('data');
        $material_append = input('material_append');
        $carry = input('carry');
        $other_fee = input('other_fee');
        $other_fee_content = input('other_fee_content');
        $userlist = Db::name('userlist')->where(['id'=>$uid])->find();
        $settlement = Db::name('settlement')->where(['uid'=>$uid,'status'=>[1,2,3,4]])->find();

        if($settlement){
            $this->json(2,'正在审核中，请耐心等待');
        }
        if($userlist['work_status'] != '待结算' && $userlist['work_status'] != '结算失败'){
            // $this->json(2,'请先验收所有工种后再申请结算');
        }
        if($userlist['jid'] != $this->admininfo['userid']){
            // $this->json(2,'申请人有误');
        }
        //判断是否含有未审核的 领料 定点 借支 等
        //仓库领料
        $picking_material = Db::name('picking_material')->where(['oid'=>$userlist['oid'],'status'=>[1,2,3]])->count();
        if($picking_material > 0){
            $this->json(2,'存有领料未处理，请处理后再申请');
        }
        //定点/自购
        $picking_order = Db::name('picking_order')->where(['userid'=>$uid,'status'=>[1,2,3]])->count();
        if($picking_material > 0){
            $this->json(2,'存有定点/自购领料未处理，请处理后再申请');
        }
        //借支 / 工人
        $jiezhi = Db::name('jiezhi')->where(['uid'=>$uid,'status'=>[1,2,3]])->count();
        if($picking_material > 0){
            $this->json(2,'存有借支未处理，请处理后再申请');
        }
        Db::startTrans();
        try {
            //修改订单和用户状态
            Db::name('offerlist')->where(['id'=>$userlist['oid']])->update(['status'=>5]);
            Db::name('userlist')->where(['id'=>$uid])->update(['status'=>7,'work_status'=>'结算待审核']);
            //保存验收记录
            $sid = Db::name('settlement')->insertGetId([
                'oid'=>$userlist['oid'],
                'uid'=>$uid,
                'fid'=> $userlist['frameid'],
                'material_append'=>$material_append,
                'carry'=>$carry,
                'other_fee'=>$other_fee,
                'other_fee_content'=>$other_fee_content
            ]);
            if($data){
                $settlement_worker = [];
                foreach($data as $k=>$v){
                    $info = [];
                    $info['oid'] = $userlist['oid'];
                    $info['uid']= $uid;
                    $info['sid']= $sid;
                    $info['fid']= $userlist['frameid'];
                    $info['type_of_work']= $k;
                    $info['wage']= $v['wage'];
                    $info['rent']= $v['rent'];
                    $info['remote']= $v['remote'];
                    $settlement_worker[] = $info;
                }
                Db::name('settlement_worker')->insertAll($settlement_worker);
            }
            // 提交事务
            Db::commit();
            $this->json(0,'申请成功，请等待审核');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->json(2,$e->getMessage());
        }
    }

    //工程经理审核结算
    public function checkSettlement(){
        $id = input('id');
        $uid = input('uid');
        $status = input('status');
        $userlist = Db::name('userlist')->where(['id'=>$uid])->find();
        if($userlist['gcmanager_id'] != $this->admininfo['userid']){
            // $this->json(2,'禁止审核他人工地');
        }
        $settlement_status = Db::name('settlement')->where(['id'=>$id])->value('status');
        if($settlement_status != 1){
            $this->json(2,'订单已审核');
        }
        Db::startTrans();
        try {
            $update = Db::name('settlement')->where(['id'=>$id])->update(['status'=>$status]);
            if($status == 22){
                //审核失败 解锁订单锁定
                Db::name('offerlist')->where(['id'=>$userlist['oid']])->update(['status'=>4]);
                Db::name('userlist')->where(['id'=>$uid])->update(['status'=>6,'work_status'=>'结算失败']);
            }
            // 提交事务
            Db::commit();
            $this->json(0,'审核成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->json(2,$e->getMessage());
        }
    }
}