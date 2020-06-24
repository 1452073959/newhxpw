<?php

// +----------------------------------------------------------------------
// | 订单
// +----------------------------------------------------------------------
namespace app\applet\controller;
use mysql_xdevapi\Table;
use think\Db;
use think\Controller;
use app\applet\controller\UserBase;
 
class Order extends UserBase{
    //获取报价详情
    public function getOrderInfo(){
        $type = input('type')==1?1:2; //1合同单 2:整单

        $uid = input('uid');
        $total_money = 0;
        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find();
        if(!$userinfo || $userinfo['oid'] == 0 || $userinfo['status'] < 3){
            $this->json('2','无效订单');
        }
        $where = [];
        $where['o_id'] = $userinfo['oid'];
        if($type == 1){
            $where['type'] = 1;
        }
        $order_project = Db::name('order_project')->where($where)->select();
        
        if(0){
            //按工种分 暂时先不要
            foreach($order_project as $k=>$v){
                if(!isset($datas[$v['type_of_work']][$v['item_number']])){
                    $datas[$v['type_of_work']][$v['item_number']]['info'] = $v;
                    $datas[$v['type_of_work']][$v['item_number']]['num'] = 0;
                    $datas[$v['type_of_work']][$v['item_number']]['project'] = $v['project'];

                }
                $datas[$v['type_of_work']][$v['item_number']]['num'] += $v['num'];
                $total_money += $datas[$v['type_of_work']][$v['item_number']]['info']['quota']*$v['num'];
                $total_money += $datas[$v['type_of_work']][$v['item_number']]['info']['craft_show']*$v['num'];
            }
        }else{
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    unset($order_project[$k]);
                    continue;
                }
                if(!isset($datas[$v['space']][$v['item_number']])){
                    $datas[$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['space']][$v['item_number']]['project'] = $v['project'];
                }
                $datas[$v['space']][$v['item_number']]['num'] += $v['num'];
                $total_money += $datas[$v['space']][$v['item_number']]['info']['quota']*$v['num'];
                $total_money += $datas[$v['space']][$v['item_number']]['info']['craft_show']*$v['num'];
            }
        }

        $order_info = Model('admin/offerlist')->get_order_info($userinfo['oid'],$type);
        //如果优惠是0 把优惠去掉
        if($order_info['order_cost']){
            foreach($order_info['order_cost'] as $k=>$v){
                if($v['sign'] == 'A2' && $v['price'] == 0){
                    unset($order_info['order_cost'][$k]);
                    $order_info['order_cost'] = array_values($order_info['order_cost']);
                    break;
                }
            }

        }
        $supervisor=Db::Table('fdz_admin')->where('userid',$userinfo['jid'])->field('name,phone')->find();
        $quoter_name=Db::table('fdz_admin')->where('userid',$userinfo['gcmanager_id'])->field('phone,name')->find();
        $quoter_name=Db::table('fdz_admin')->where('userid',$userinfo['check_id'])->field('phone,name')->find();
        $userinfo['gcmanager_name']=$quoter_name;
        $userinfo['supervisor']=$supervisor;
        $userinfo['check_name']=$quoter_name;

        $manager_name=Db::table('fdz_personnel')->where('id',$userinfo['manager_id'])->field('phone,name')->find();
        $quoter_name=Db::table('fdz_personnel')->where('id',$userinfo['quoter_id'])->field('phone,name')->find();
        $designer_name=Db::table('fdz_personnel')->where('id',$userinfo['designer_id'])->field('phone,name')->find();
        $quoter_name=Db::table('fdz_personnel')->where('id',$userinfo['quoter_id'])->field('phone,name')->find();
        $userinfo['quoter_name']=$quoter_name;
        $userinfo['manager_name']=$manager_name;
        $userinfo['designer_name']=$designer_name;
        $userinfo['quoter_name']=$quoter_name;

        if($userinfo['house_type']==1){
            $userinfo['house_type']='新房';
        }else{
            $userinfo['house_type']='旧房';
        }

        unset($order_info['content']);
        unset($order_info['artificial']);
        unset($order_info['material']);
        $this->json(0,'success',['order'=>$datas,'userinfo'=>$userinfo,'total_money'=>$total_money,'order_info'=>$order_info]);
    }

    //监理提交增加项
    public function addAppend(){
        $uid = input('uid');
        $remark = input('remark'); //先可不填吧
        $img = input('img');
        $userinfo = Db::name('userlist')->where(['id'=>input('uid')])->find(); //用户详情
        if(!$userinfo){
            $this->json(2,'参数错误');
        }
        if($userinfo['status'] > 6){
            $this->json(2,'结算状态禁止提交增加项');
        }
        if(!isset($img[0])){
            $this->json(2,'未上传图片');
        }
        Db::startTrans();
        $data = [];
        try {
            if($img){
                foreach($img as $k=>$v){
                    //其实只有一张图片 预防以后可能有多张图片. 先这样写
                    $info = [];
                    $info['oid'] = $userinfo['oid'];
                    $info['fid'] = $userinfo['frameid'];
                    $info['uid'] = $uid;
                    $info['img'] = $v;
                    $info['remark'] = $remark;
                    $data[] = $info;
                }
                Db::name('append_img')->insertAll($data);
            }
            // 提交事务
            Db::commit();
            $this->json(0,'提交成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->json(0,'提交失败');
        }
    }

    //获取增减项列表
    public function getAppendList(){
        $uid = input('uid');
        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find();
        if(!$userinfo || $userinfo['oid'] == 0 || $userinfo['status'] < 3){
            $this->json('2','无效订单');
        }
        $order_append = Db::name('order_append')->where(['o_id'=>$userinfo['oid']])->order('id','asc')->select();
        if(!$order_append){
            //没有增减项
            $this->json('2','none',[]);
        }
        foreach($order_append as $key=>$val){
            $order_append[$key]['info'] = model('admin/offerlist')->get_append_order_info($val['id']);
            $order_project = Db::name('order_project')->where(['oa_id'=>$val['id']])->order('id','asc')->select();
            $datas = [];
            $total_money = 0;
            if(0){
                foreach($order_project as $k=>$v){
                    if(!isset($datas[$v['type_of_work']][$v['item_number']])){
                        $datas[$v['type_of_work']][$v['item_number']]['info'] = $v;
                        $datas[$v['type_of_work']][$v['item_number']]['num'] = 0;
                        $datas[$v['type_of_work']][$v['item_number']]['project'] = $v['project'];

                    }
                    $datas[$v['type_of_work']][$v['item_number']]['num'] += $v['num'];
                    // $total_money += $datas[$v['type_of_work']][$v['item_number']]['info']['quota']*$v['num'];
                    // $total_money += $datas[$v['type_of_work']][$v['item_number']]['info']['craft_show']*$v['num'];
                }
            }else{
                foreach($order_project as $k=>$v){
                    if(!isset($datas[$v['space']][$v['item_number']])){
                        $datas[$v['space']][$v['item_number']]['info'] = $v;
                        $datas[$v['space']][$v['item_number']]['num'] = 0;
                        $datas[$v['space']][$v['item_number']]['project'] = $v['project'];
                    }
                    $datas[$v['space']][$v['item_number']]['num'] += $v['num'];
                    // $total_money += $datas[$v['space']][$v['item_number']]['info']['quota']*$v['num'];
                    // $total_money += $datas[$v['space']][$v['item_number']]['info']['craft_show']*$v['num'];
                }
            }
            $order_append[$key]['project'] = $datas;
            $order_append[$key]['add_time'] = date('Y-m-d',$val['add_time']);
            // $order_append[$key]['total_money'] = $total_money;
        }
        $this->json('0','success',$order_append);
    }

    //工地开工
    public function doWork(){
        $uid = input('uid');
        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find();
        if(!$userinfo || $userinfo['oid'] == 0 || $userinfo['status'] < 3 || !empty($userinfo['work_time'])){
            $this->json('2','无效订单');
        }

        // $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$userinfo['frameid']])->find();
        // if(!$cost_tmp || !$cost_tmp['order_check']){
        //     //未设置质检流程
        //     $this->json(2,'施工流程未设置，请联系管理员设置施工流程');
        // }
        // //获取第一个流程
        // $order_check = json_decode($cost_tmp['order_check'],true);
        // if(is_array($order_check)){
        //     $order_check = array_column($order_check, null,0);
        // }else{
        //     $this->json(2,'验收流程有误，请联系管理员');
        // }
        // //获取所有工种
        // $type_of_work = Db::name('order_project')->where(['o_id'=>$userinfo['oid']])->field('type_of_work')->group('type_of_work')->select();
        // $type_of_work = array_unique(array_column($type_of_work,'type_of_work'));
        // //只取前面2个字
        // foreach($type_of_work as $k=>$v){
        //     $type_of_work[$k] = mb_substr($v, 0, 2);
        // }
        // $next_check = '待结算';
        // foreach($order_check as $k=>$v){
        //     if(in_array($k, $type_of_work)){
        //         $next_check = $k;
        //         break;
        //     }

        // }

        
        //这里需要找到分公司验收顺序 是个字符串
        // $work_status = $next_check;
        $work_status = '开工验收';
        $res = Db::name('userlist')->where(['id'=>$uid])->update(['work_status'=>$work_status,'work_time'=>time()]);
        if($res){
            $this->json('0','success','开工验收');
        }else{
            $this->json('2','开工失败');
        }
    }
    
}