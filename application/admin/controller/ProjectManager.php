<?php

// +----------------------------------------------------------------------
// | 工程管理
// +----------------------------------------------------------------------
namespace app\admin\controller; 

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;

class ProjectManager extends Adminbase{
    public $show_page = 20;
    //工程派单
    public function send_order_index(){
        $admininfo = $this->_userinfo;
        $where = [];
        $condition = [];
        if(input('name')){
            $where[] = ['customer_name','like','%'.input('name').'%'];
        }
        if(input('begin_time') && input('end_time')){
            $condition = array(['sign_bill_time','>',strtotime(input('begin_time'))],['sign_bill_time','<',strtotime('+1 day',strtotime(input('end_time')))]);
        } 
        $where[] = ['status','>','2'];
        $where[] = ['jid','=','0'];
        $where[] = ['frameid','=',$admininfo['companyid']];
        $datas = Db::name('userlist')->where($where)->where($condition)->paginate($this->show_page,false,['query'=>request()->param()]);
        $this->assign('datas',$datas);
        return $this->fetch();
    }

    public function ajax_get_supervision(){
        $admininfo = $this->_userinfo;
        $where = [];
        $admin = Db::name('admin')->where(['roleid'=>[13,15,16],'companyid'=>$admininfo['companyid'],'status'=>1])->select();


        $userlist = Db::name('userlist')->where(['frameid'=>$admininfo['companyid'],'status'=>[3,4,5,6]])->where('oid','>','0')->select();

        foreach($userlist as $k=>$v){
            //获取工程报价(优惠后)
            $userlist[$k]['discount_proquant'] = model('offerlist')->get_order_info($v['oid'],2)['discount_proquant'];
        }

        $new_datas = [];
        foreach($admin as $k=>$v){
            $total_money = 0;
            $total_count = 0;
            $count1 = 0;
            $count2 = 0;
            $count3 = 0;
            foreach($userlist as $key=>$value){
                if($value['jid'] == $v['userid']){
                    $total_money += $value['discount_proquant'];
                    if($value['status'] == 3){
                        $count1++;
                    }
                    if($value['status'] == 4 || $value['status'] == 5){
                        $count2++;
                    }
                    if($value['status'] == 6){
                        $count3++;
                    }
                    $total_count++;
                }elseif($value['check_id'] == $v['userid']){
                    $total_money += $value['discount_proquant'];
                    if($value['status'] == 3){
                        $count1++;
                    }
                    if($value['status'] == 4 || $value['status'] == 5){
                        $count2++;
                    }
                    if($value['status'] == 6){
                        $count3++;
                    }
                    $total_count++;
                }elseif($value['gcmanager_id'] == $v['userid']){
                    $total_money += $value['discount_proquant'];
                    if($value['status'] == 3){
                        $count1++;
                    }
                    if($value['status'] == 4 || $value['status'] == 5){
                        $count2++;
                    }
                    if($value['status'] == 6){
                        $count3++;
                    }
                    $total_count++;
                }
            }
            $v['total_money'] = round($total_money,2);
            $v['total_count'] = $total_count;
            $v['count1'] = $count1;
            $v['count2'] = $count2;
            $v['count3'] = $count3;
            $new_datas[$v['roleid']][] = $v;
        }
        $this->success('success','',$new_datas);
        // var_dump($datas);die;
    }

    //派单操作
    public function send_order(){
        if(input('uid') && input('jid')){
            $where = [];
            $where[] = ['id','=',input('uid')];
            $where[] = ['jid','=',0];
            $where[] = ['check_id','=',0];
            $where[] = ['gcmanager_id','=',0];
            $where[] = ['status','>',2];
            if(Db::name('userlist')->where($where)->count()){
                $res = Db::name('userlist')->where($where)->update(['jid'=>input('jid'),'check_id'=>input('check_id'),'gcmanager_id'=>input('gcmanager_id')]);
                if($res){
                    $this->success('派单成功');
                }else{
                    $this->error('派单失败');
                }
            }else{
                $this->error('订单有误，请刷新页面后重试');
            }
        }else{
            $this->error('参数错误');
        }
    }
}