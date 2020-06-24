<?php

// +----------------------------------------------------------------------
// | 统计报表
// +----------------------------------------------------------------------
namespace app\admin\controller; 

use app\applet\model\Jiezhi;
use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;

class Financial extends Adminbase{
    public $show_page = 15;
    //客户列表
    public function userlist(){
        $condition = [];//用于时间搜索 new where不会用
        $where = [];
        $da = [];
        if(input('customer_name')){
            $where[] = ['customer_name','LIKE','%'.input('customer_name').'%'];
        }
        if(input('quoter_name')){
            $where[] = ['quoter_name','LIKE','%'.input('quoter_name').'%'];
        }
        if(input('designer_name')){
            $where[] = ['designer_name','LIKE','%'.input('designer_name').'%'];
        }
        if(input('address')){
            $where[] = ['address','LIKE','%'.input('address').'%'];
        }
        if(input('manager_name')){
            $where[] = ['manager_name','LIKE','%'.input('manager_name').'%'];
        }
        if(input('begin_time') && input('end_time')){
            $condition = array(['sign_bill_time','>',strtotime(input('begin_time'))],['sign_bill_time','<',strtotime('+1 day',strtotime(input('end_time')))]);
        }
        if(input('status')){
            $where[] = ['status','=',input('status')];
        }else{
            $where[] = ['status','gt',1];
        }
        $userinfo = $this->_userinfo; 
        // if($userinfo['userid'] != 1 && $userinfo['roleid'] != 10){
        //     $da['userid'] = $userinfo['userid'];
        // }
        if($userinfo['roleid'] != 1){
            $da['frameid'] = $userinfo['companyid'];
        }
        $re = Db::name('userlist')->where($where)->where($da)->where($condition)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);

        $this->assign('data',$re);
        $this->assign('userinfo',$userinfo);
        $this->assign('status',[2=>'待付款',3=>'已收一期',4=>'已收二期 ',5=>'已收三期',6=>'已收四期',7=>'结算审核',8=>'已结算']);
        return $this->fetch();
    }

    //收钱 
    public function get_money(){
        $userinfo = Db::name('userlist')->where(['id'=>input('customer_id')])->find();
        $o_id = $userinfo['oid'];
        $order_info = Model('offerlist')->get_order_info($o_id,2);//原单
        $append_list = Model('offerlist')->get_append_info(input('customer_id'));//增减项
        $financial = Db::name('financial')->field('sum(money) as money,id,userid,fid,type,remark,addtime')->where(['userid'=>input('customer_id'),'type'=>[1,2,3,4,8,9]])->group('type')->select();
        $get_money = array_column($financial,null,'type');//收款详情
        $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$userinfo['frameid']])->find();
        if(!$cost_tmp){
            $this->error('未设置收款比率，请先设置');
        }

        $rate = [1=>$cost_tmp['take_rate1'],2=>$cost_tmp['take_rate2'],3=>$cost_tmp['take_rate3'],4=>$cost_tmp['take_rate4']];//收款比率
        $this->assign('userinfo',$userinfo);   
        $this->assign('order_info',$order_info);   
        $this->assign('append_list',$append_list);   
        $this->assign('get_money',$get_money); 
        $this->assign('rate',$rate);   
        return $this->fetch();
    }

    //收钱 接口
    public function take_money(){
        if(input('uid') && input('type') && input('money')){
            if(in_array(input('type'), [1,2,3,4])){
                $now_type = Db::name('financial')->where(['userid'=>input('uid'),'type'=>[1,2,3,4]])->order('type','desc')->value('type');
                if($now_type > input('type')){
                    $this->error('已收款，请勿重复添加');
                }
            }
            //一期款 改变订单状态
            if(input('type') == 1){
                Db::name('offerlist')->where(['customerid'=>input('uid'),'status'=>3])->update(['status'=>4]);
            }
            if(input('type') == 4){
                Db::name('offerlist')->where(['customerid'=>input('uid'),'status'=>4])->update(['status'=>5]);
            }
            $data = [];
            $data['userid'] = input('uid');
            $data['remark'] = input('remark');
            $data['type'] = input('type');
            $data['money'] = input('money');
            $userinfo = Db::name('userlist')->where(['id'=>input('uid')])->find();
            $data['fid'] = $userinfo['frameid'];
            Db::startTrans();
            try{
                Db::name('financial')->insert($data);
                if(input('type') < 8){
                    Db::name('userlist')->where(['id'=>input('uid')])->update(['status'=>(input('type')+2)]);
                }
                
                Db::commit();    
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('添加失败');
            }
            $this->success('添加成功');
        }
    }

    //获取收款明细
    public function get_financial_info(){
        $financial = Db::name('financial')->where(['userid'=>input('uid'),'type'=>input('type')])->order('id','asc')->select();
        if($financial){
            $this->success('success','',$financial);
        }else{
            $this->error('无收款记录');
        }
    }

    //借支 
    public function lend_money(){
        $userinfo = Db::name('userlist')->where(['id'=>input('customer_id')])->find();
        $login = $this->_userinfo;
        if($login['roleid']!=1) {
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('uid',$userinfo['id'])->where('status','in', [2,3,5])->where('frameid', $login['companyid'])->paginate(10);
        }else{
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('uid',$userinfo['id'])->where('status','in', [2,3,5])->paginate(10);
        }
        $this->assign('userinfo',$userinfo);
        $this->assign('audit',$audit);
        return $this->fetch();
    }

    public function net_payroll(Request $request)
    {
        $login = $this->_userinfo;
        $net_payroll=$request->get();
        $res = Jiezhi::get($net_payroll['id']);
        $res->status=3;
        $res->bid=$login['userid'];
        $res->cwtime=date('y-m-d H:i:s', time());
        $res->save();
        if($res){
            $this->success('拨款成功');
        }else{
            $this->error('拨款失败');
        }
    }


    //监理
    public function financeaudit()
    {
        $login = $this->_userinfo;
        if($login['roleid']!=1) {
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('type',1)->where('status', 'in', [2,3,5])->where('frameid', $login['companyid'])->order('status','asc')->paginate(10);
        }else{
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('type',1)->where('status', 'in', [2,3,5])->order('status','asc')->paginate(10);
        }
        $this->assign('audit',$audit);
        return $this->fetch();
    }
    //监理代工人
    public function agencyaudit()
    {
        $login = $this->_userinfo;
        if($login['roleid']!=1) {
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('type',2)->where('status', 'in', [2,3,5])->where('frameid', $login['companyid'])->order('status','asc')->paginate(10);
        }else{
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('type',2)->where('status', 'in', [2,3,5])->order('status','asc')->paginate(10);
        }
        $this->assign('audit',$audit);
        return $this->fetch();
    }


    public function financeajax(Request $request)
    {
        $login = $this->_userinfo;
        $net_payroll=$request->get();
        if($net_payroll['status']==3){
            $res = Jiezhi::get($net_payroll['key']);
            $res->status=$net_payroll['status'];
            $res->bid=$login['userid'];
            $res->cwtime=date('y-m-d H:i:s', time());
            $res->save();
            return json(['code'=>1,'msg'=>'拨款成功','data'=>$res]);
        }else{
            $res = Jiezhi::get($net_payroll['key']);
            $res->status=$net_payroll['status'];
            $res->bid=$login['userid'];
            $res->cwtime=date('y-m-d H:i:s', time());
            $res->save();
            return json(['code'=>2,'msg'=>'操作成功','data'=>$res]);
        }

    }

    public function printing()
    {
        $login = $this->_userinfo;
        if($login['roleid']!=1) {
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('status', 'in', 2)->where('frameid', $login['companyid'])->order('status','asc')->select();
        }else{
            $audit = Jiezhi::with(['offer', 'user', 'audit'])->where('status', 'in', 2)->order('status','asc')->select();
        }
        $this->assign('audit',$audit);
        return $this->fetch();
    }
    // //订单列表 (只显示 合同价-未审 合同价以审 结算价) 未审订单靠上
    // public function order_list(){
    //     $where = [];
    //     if(input('status')){
    //         $where['status'] = input('status');
    //     }else{
    //         $where['status'] = [3,4,5];
    //     }
    //     $datas = Db::name('offerlist')->where($where)->order('id','desc')->paginate(10,false,['query'=>request()->param()]);
    //     $userids = array_column($datas->items(),'userid');
    //     $this->assign([
    //         'datas'=>$datas
    //     ]);
    //     return $this->fetch();
    // }

   
}