<?php
/**
 * Created by PhpStorm.
 * User: 覃宇彬
 * Date: 2019/6/17
 * Time: 10:40
 */

namespace app\admin\controller;


use app\common\controller\Adminbase;
use think\Db;
class Settlement extends Adminbase
{

    //财务审核结算列表
    public function audit_settlement_by_cw(){
        $where = [];
        $where[] = ['s.fid','=',$this->_userinfo['companyid']];
        $where[] = ['s.status','=',3];
        if(input('customer_name')){
            $where[] = ['u.customer_name','like','%'.input('customer_name').'%'];
        }
        if(input('address')){
            $where[] = ['u.address','like','%'.input('address').'%'];
        }
        if(input('jid')){
            $where[] = ['u.jid','=',input('jid')];
        }
        if(input('gcmanager_id')){
            $where[] = ['u.gcmanager_id','=',input('gcmanager_id')];
        }
        if(input('check_id')){
            $where[] = ['u.check_id','=',input('check_id')];
        }
        $field = 'u.id as uid , s.*';
        $settlement = Db::name('settlement')->alias('s')->leftJoin('userlist u','s.uid = u.id')->field($field)->where($where)->order('s.id','asc')->select();
        if($settlement){
            $oids = array_column($settlement, 'oid');
            $uids = array_column($settlement, 'uid');
            $userlist = array_column(Db::name('userlist')->where(['id'=>$uids])->select(),null, 'id');
            $jid = array_column($userlist,'jid');
            $check_id = array_column($userlist,'check_id');
            $gcmanager_id = array_column($userlist,'gcmanager_id');
            $adminids = array_merge($jid,$check_id,$gcmanager_id);
            $adminlist = array_column(Db::name('admin')->where(['userid'=>$adminids])->select(),null, 'userid');
            $this->assign('userlist',$userlist);
            $this->assign('adminlist',$adminlist);
            foreach($settlement as $k=>$v){
                $settlement[$k]['order_info'] = model('offerlist')->get_order_info($v['oid'],2);
                $settlement[$k]['settlement_money'] = model('offerlist')->getSettlementTotal($v['id']);
            }
        }
        $admins = Db::name('admin')->where(['roleid'=>[13,15,16],'companyid'=>$this->_userinfo['companyid']])->select();

        $this->assign('admins',$admins);
        $this->assign('settlement',$settlement);
        return $this->fetch();
    }

    //财务/分总审核结算列表
    public function audit_settlement_by_fz(){
        $where = [];
        $where[] = ['s.fid','=',$this->_userinfo['companyid']];
        $where[] = ['s.status','=',2];
        if(input('customer_name')){
            $where[] = ['u.customer_name','like','%'.input('customer_name').'%'];
        }
        if(input('address')){
            $where[] = ['u.address','like','%'.input('address').'%'];
        }
        if(input('jid')){
            $where[] = ['u.jid','=',input('jid')];
        }
        if(input('gcmanager_id')){
            $where[] = ['u.gcmanager_id','=',input('gcmanager_id')];
        }
        if(input('check_id')){
            $where[] = ['u.check_id','=',input('check_id')];
        }
        $field = 'u.id as uid , s.*';
        $settlement = Db::name('settlement')->alias('s')->leftJoin('userlist u','s.uid = u.id')->field($field)->where($where)->order('s.id','asc')->select();
        if($settlement){
            $oids = array_column($settlement, 'oid');
            $uids = array_column($settlement, 'uid');
            $userlist = array_column(Db::name('userlist')->where(['id'=>$uids])->select(),null, 'id');
            $jid = array_column($userlist,'jid');
            $check_id = array_column($userlist,'check_id');
            $gcmanager_id = array_column($userlist,'gcmanager_id');
            $adminids = array_merge($jid,$check_id,$gcmanager_id);
            $adminlist = array_column(Db::name('admin')->where(['userid'=>$adminids])->select(),null, 'userid');
            $this->assign('userlist',$userlist);
            $this->assign('adminlist',$adminlist);
            foreach($settlement as $k=>$v){
                $settlement[$k]['order_info'] = model('offerlist')->get_order_info($v['oid'],2);
                $settlement[$k]['settlement_money'] = model('offerlist')->getSettlementTotal($v['id']);
            }
        }
        $admins = Db::name('admin')->where(['roleid'=>[13,15,16],'companyid'=>$this->_userinfo['companyid']])->select();

        $this->assign('admins',$admins);
        $this->assign('settlement',$settlement);
        return $this->fetch();
    }

    //获取结算单
    public function getSettlement(){
        $id = input('id');
        $settlement = Db::name('settlement')->where(['id'=>$id])->order('id','desc')->find();
        $artificial = Db::name('order_project')->where(['o_id'=>$settlement['oid']])->select();
        $worker_wage = [];//拼装数组
        foreach($artificial as $k=>$v){
            if(!isset($worker_wage[$v['type_of_work']]['due_wage'])){
                $worker_wage[$v['type_of_work']]['due_wage'] = 0;
            }
            $worker_wage[$v['type_of_work']]['due_wage'] += $v['labor_cost']*$v['num'];
        }
        $settlement_worker = Db::name('settlement_worker')->where(['sid'=>$id])->select();
        foreach($settlement_worker as $k=>$v){
            if(isset($worker_wage[$v['type_of_work']])){
                $worker_wage[$v['type_of_work']]['wage'] = $v['wage'];
                $worker_wage[$v['type_of_work']]['rent'] = $v['rent'];
                $worker_wage[$v['type_of_work']]['remote'] = $v['remote'];
            }
            
        }
        $this->success('success','',['worker_wage'=>$worker_wage,'settlement'=>$settlement]);
    }

    //财务审核结算
    public function checkSettlement(){
        $id = input('id');
        $status = input('status');

        $settlement = Db::name('settlement')->where(['id'=>$id])->find();
        $settlement_status = $settlement['status'];
        $uid = $settlement['uid'];
        if($status == 3 || $status == 33){
            if($settlement_status != 2){
                $this->error('订单已审核');
            }
            if($this->_userinfo['roleid'] != 10){
                $this->error('非经理禁止操作');
            }
        }
        if($status == 4 || $status == 44){
            if($settlement_status != 3){
                $this->error('订单已审核');
            }
            if($this->_userinfo['roleid'] != 18){
                $this->error('非财务禁止操作');
            }
        }
        

        $userlist = Db::name('userlist')->where(['id'=>$uid])->find();
        
        if($userlist['frameid'] != $this->_userinfo['companyid']){
            $this->error('禁止审核他人工地');
        }
        Db::startTrans();
        try {
            $update = Db::name('settlement')->where(['id'=>$id])->update(['status'=>$status]);
            if($status == 33 || $status == 44){
                //审核失败 解锁订单锁定
                Db::name('offerlist')->where(['id'=>$userlist['oid']])->update(['status'=>4]);
                Db::name('userlist')->where(['id'=>$uid])->update(['status'=>6,'work_status'=>'结算失败']);
            }
            if($status == 4){
                //完成结算
                Db::name('offerlist')->where(['id'=>$userlist['oid']])->update(['status'=>6]);
                Db::name('userlist')->where(['id'=>$uid])->update(['status'=>8,'work_status'=>'已结算']);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('审核失败');
        }
        $this->success('审核成功');
    }




    //========================下面的都不知道是什么东东



    public function index()
    {
        $userinfo = $this->_userinfo;
        // $da['o.userid'] = $userinfo['userid'];
        if($userinfo['roleid'] != 1){
           $da['o.frameid'] = $userinfo['companyid'];
        }
        $da['o.status'] = 1;
        if($this->request->isPost()){
            $search = input('search');
            if($search){
                $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address')->join('userlist u','o.customerid = u.id')->where($da)->select();
                $list = [];
                foreach ($res as $key => $value) {
                    if (strstr($value['customer_name'],$search)) {
                        $list[$key] = $value;
                    }
                }
                $this->assign('data',$list);
                return $this->fetch();
            }else{
                $this->error('请输入搜索内容', url("offerlist/index"));
            }

        }else{
            $res = Db::name('offerlist')->select();
            if ($res) {
                $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address')->join('userlist u','o.customerid = u.id')->where($da)->select();
            }
            $this->assign('data',$res);
            return $this->fetch();
        }
    }
	//自选报表对比
	public function compare(){
		
		return $this->fetch();
	}
	//结算毛利对比
    public function contrast(){
        $userinfo = $this->_userinfo;
        $request = request();
        $id = $request->param('id');
        $da['o.id'] = $id;
        // echo $customerid;
        $res = Db::name('offerlist')->select();
        if ($res) {
            $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address')->join('userlist u','o.customerid = u.id')->where($da)->select();
        }
        
        //统计报价开始 
        foreach ($res as $key => $value) {
            $content = json_decode($value['content'],true);
            foreach($content as $keys => $values){
                $res[$key]['matquant'] += $values['quotaall'];//辅材报价
                $res[$key]['manual_quota'] += $values['craft_showall'];//人工报价
            }
            $res[$key]['direct_cost'] = $res[$key]['matquant']+$res[$key]['manual_quota'];//工程直接费= 辅材报价+人工报价
            $res[$key]['proquant'] = $res[$key]['matquant']+$res[$key]['manual_quota']+$res[$key]['tubemoney']+$res[$key]['taxes']+$res[$key]['discount'];

            $tariff = array();$labor_cost = '';$fucai = '';
            foreach ($content as $keys => $values) {
                $dinge[$keys] =  Db::name('offerquota')->field('item_number,labor_cost,content')->where('item_number',$content[$keys]['item_number'])->find();
                $tariff[$keys]['item_number'] = $content[$keys]['item_number'];
                $tariff[$keys]['gcl'] = $content[$keys]['gcl'];
                $tariff[$keys]['labor_cost'] = $dinge[$keys]['labor_cost'] * $content[$keys]['gcl'];
                $tariff[$keys]['content'] = json_decode($dinge[$keys]['content'],true);
                //辅材成本
                foreach($tariff[$keys]['content'] as $k => $v){

                }
                $tariff[$keys]['fucai'] = 0;
                foreach ($tariff[$keys]['content'] as $e => $ll) {
                    if($ll[0] && is_numeric($ll[1])){
                        $price = $this->returnPrice($ll[0]);//辅材名称对应的价格；
                        $tariff[$keys]['fucai'] += $price*$ll[1]*$content[$keys]['gcl'];
                    }
                }
                $labor_cost += $tariff[$keys]['labor_cost'];
                $fucai += $tariff[$keys]['fucai']; 
            }
            $fc_cost = 0;//辅材成本
            $labor_cost = 0;//人工成本
            foreach($tariff as $k=>$v){
                foreach($v['content'] as $k1 => $v2){
                    $fc_cost += $v2[1] * $v['gcl'];
                }
                $labor_cost += $v['labor_cost'];
            }
            $res[$key]['fc_cost'] = $fc_cost;
            $res[$key]['labor_cost'] = $labor_cost;
            $res[$key]['gross_profit'] = $labor_cost+$fucai;
            $res[$key]['content'] = $content;
        }
        
        //结算数据
        $budget = Db::name('budget')->where('offerlist_id',$id)->find();
        $this->assign('budget',$budget);
        $this->assign('offerlist_id',$id);
        $this->assign('data',$res);
        $this->assign('tariff',$tariff);

        return $this->fetch();
    }

 //按辅材名称返回辅材单价
    public function returnPrice($val){
        if(is_null($val)){
            return null;
        }
        $re = Db::name('materials')->field('price')->where('name',$val)->find();
        return $re['price'];
    }

    //保存预算数据
    public function savebudget(){
        if($this->request->isPost()){
            $param = $this->request->param();
            $param['savetime'] = time();
            $has_save = Db::name('budget')->where('offerlist_id',$param['offerlist_id'])->find();
            if($has_save){
                $re = Db::name('budget')->where('id',$has_save['id'])->update($param);
            }else{
                $re = Db::name('budget')->insert($param);
            }
            if($re !== false){
                return json([ 'msg'=>'success' ]);
            }else{
                return json([ 'msg'=>'fail' ]);
            }
                    
        }
    }


}