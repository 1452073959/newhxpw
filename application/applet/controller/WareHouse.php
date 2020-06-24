<?php
// +----------------------------------------------------------------------
// | 仓管
// +----------------------------------------------------------------------
namespace app\applet\controller;
use app\admin\model\PickingMaterial;
use think\Db;
use think\Controller;
use app\applet\controller\UserBase;
 
class WareHouse extends UserBase{
    //获取领料列表
    public function getOrder(){
        $where = [];
        if(input('status') == 1){
            //全部订单
            //审核不通过和未审核不需要
            $where['status'] = [2,3,4];
        }else{
            $where['status'] = input('status');
        }
        $where['f_id'] = $this->admininfo['companyid'];
        $picking_material = PickingMaterial::with(['userlist','jianli'])->where($where)->order('id','desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['addtime'] = date('Y-m-d',strtotime($item['addtime']));
            $info = Db::name('picking_material_info')->where(['pmid'=>$item['id']])->order('id','asc')->select();
            foreach($info as $k=>$v){
                if($item['status'] == 3 || $item['status'] == 4){
                    $info[$k]['num'] = $info[$k]['actual_num'];
                }
                $info[$k]['total_price'] = round($info[$k]['num'] * $info[$k]['price'],2) ;
            }
            if($item['status'] == 3 || $item['status'] == 4){
                $item['total_money'] = $item['actual_total_money'];
            }
            
            $item['info'] = $info;
            return $item;
        });
        if(!$picking_material){
            //为空 未领料
            $this->json(2,'none',[]);
        }

        $this->json(0,'success',['datas'=>$picking_material,'status'=>input('status')]);
    }

    //获取某用户全部领料列表
    public function getOrderByUser(){
        $where = [];
        $where['userid'] = input('uid');
        $where['status'] = [2,3,4];
        $picking_material = Db::name('picking_material')->where($where)->order('id','desc')->select();
        foreach($picking_material as $key=>$val){
            $picking_material[$key]['addtime'] = date('Y-m-d',strtotime($val['addtime']));
            $info = Db::name('picking_material_info')->where(['pmid'=>$val['id']])->order('id','asc')->select();
            foreach($info as $k=>$v){
                $info[$k]['total_price'] = round($info[$k]['actual_num'] * $info[$k]['price'],2);
            }
            $picking_material[$key]['info'] = $info;
        }
        $this->json(0,'success',$picking_material);
    }

    //获取领料单条
    public function getOrderInfo(){
        $id = input('id');
        $picking_material = Db::name('picking_material')->where(['id'=>$id])->find();
        $picking_material_info = array_column(Db::name('picking_material_info')->where(['pmid'=>$picking_material['id']])->order('id','asc')->select(),null, 'id');
        foreach($picking_material_info as $k=>$v){
            $picking_material_info[$k]['img'] = $this->getImgSrc($v['img']);
            $picking_material_info[$k]['total_price'] = ($v['actual_num']?$v['actual_num']:$v['num']) * $v['price'];
        }
        $picking_material['info'] = $picking_material_info;
        $this->json(0,'success',$picking_material);
    }

    //提交配货内容
    public function submitAllot(){

        $id = input('id');
        $datas = input('datas');

        $total_money = 0;
        $picking_material = Db::name('picking_material')->where(['id'=>$id])->find();
        if(!$picking_material){
            $this->json(2,'订单有误');
        }
        $oid = $picking_material['oid'];
        $userid = $picking_material['userid'];
        if($picking_material['status'] != 2){
            $this->json(2,'该订单已配货');
        }
        //预测可领金额
        // $material_total_money = model('admin/offerlist')->get_material_list($oid,2)['total_money'];
        //所有订单的领料金额(排除了在处理订单金额)
        // $figure=Db::name('picking_material')->where('oid',$oid)->field(['id','total_money','status','actual_total_money'])->select();
        // $figurenum=0;
        // foreach ($figure as $k1=>$v1)
        // {
        //     if($v1['id']==$id){
        //         continue;
        //     }
        //     if($v1['status']==1){
        //         $figurenum+=$v1['total_money'];
        //     }elseif ($v1['status']==2){
        //         $figurenum+=$v1['total_money'];
        //     }elseif ($v1['status']==3){
        //         $figurenum+=$v1['actual_total_money'];
        //     }elseif ($v1['status']==4){
        //         $figurenum+=$v1['actual_total_money'];
        //     }elseif ($v1['status']==5){
        //         continue;
        //     }
        // }
        // //定点自购的金额
        // $figurenum += Db::name('picking_order')->where(['userid'=>$userid,'status'=>[0,2]])->sum('money');
        //获取领料超过多少则需要审核
        $pick_rate = Db::name('cost_tmp')->where(['f_id'=>$this->admininfo['companyid']])->value('pick_rate');
        if(!$pick_rate){
            $pick_rate = 80;
        }
        foreach($datas as $k=>$v){
            $total_money += $v['num']*$v['price'];
        }
        //配料逻辑 可以领取多100块钱的

        if($total_money <= $picking_material['total_money'] +100 && $total_money <= $picking_material['total_money']*1.1){
            // 启动事务
            Db::startTrans();
            try {
                foreach($datas as $k=>$v){
                    Db::name('picking_material_info')->where(['id'=>$v['id']])->update(['actual_num'=>$v['num']]);
                }
                    Db::name('picking_material')->where(['id' => $id])->update(['actual_total_money' => $total_money, 'status' => 3]);
                    // 提交事务
                    Db::commit();
                    $this->json(0, '配货成功');
            } catch (\Exception $e) {
                Db::rollback();
                $this->json(2, '配货失败');
            }
        }else{
            $this->json(2,'领料金额不得大于原金额10%或100块');
        }

        
    }

    //确认领料
    public function confirmPick(){
        $id = input('id');
        $status = Db::name('picking_material')->where(['id'=>$id])->value('status');
        if(!$status || $status != 3){
            $this->json(2,'订单状态有误');
        }
        $res = Db::name('picking_material')->where(['id'=>$id])->update(['status'=>4,'gettime'=>time()]);
        if($res){
            $this->json(0,'确认领料成功');
        }else{
            $this->json(2,'确认领料失败');
        }
    }

}