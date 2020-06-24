<?php
// +----------------------------------------------------------------------
// | 后台订单管理
// +----------------------------------------------------------------------
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Session;
class Offerlist extends Model
{
    public $discount_type = [
        '工程直接费'=>0,
        '材料直接费'=>0,
        '人工直接费'=>0,
        '工程报价'=>0,
        '纯人工项目'=>0,
        '特价项目'=>0,
        '设计费'=>0,
        '打拆工程'=>0,
        '电工工程'=>0,
        '加建工程'=>0,
        '镜钢玻璃类'=>0,
        '木工工程'=>0,
        '泥瓦工程'=>0,
        '水工工程'=>0,
        '油灰工程'=>0,
        '综合类'=>0,
        '现金'=>0,
        //下面是广州分公司奇奇怪怪的
        '油漆煽灰工程'=>0,
        '木制作'=>0,
        '土建工程'=>0,
        '泥瓦工种'=>0,
        '防水'=>0,
        '加建工程'=>0,

    ];
    //获取订单详情
    public function get_order_info($id,$type=1,$tmp_cost=[]){ //offerlist 的id , type ->1:合同单 2:整单

        $oid = $id;
        if($type == 2){
            $order_project = Db::name('order_project')->where(['o_id'=>$oid])->select();
        }else{
            $order_project = Db::name('order_project')->where(['o_id'=>$oid,'type'=>1])->select();
        }
        // $order_project = Db::name('order_project')->where(['o_id'=>$oid])->select();
        $offerlist_info = $this->get_order_info_by_project($order_project,$tmp_cost);
        //计算辅材成本
        $offerlist_info['material_cb'] = $this->get_material_list($oid,$type)['total_money'];
        //计算杂项
        $offerlist_info['supervisor_commission'] = round($offerlist_info['supervisor_commission']/100*$offerlist_info['discount_proquant'],2);//监理提成
        $offerlist_info['design_commission'] = round($offerlist_info['design_commission']/100*$offerlist_info['discount_proquant'],2);;//设计提成
        $offerlist_info['repeat_commission'] = round($offerlist_info['repeat_commission']/100*$offerlist_info['discount_proquant'],2);;//回头客奖
        $offerlist_info['business_commission'] = round($offerlist_info['business_commission']/100*$offerlist_info['discount_proquant'],2);;//业务提成
        if($offerlist_info['direct_cost']){
            //工程毛利 优惠后工程报价 - 辅材成本-人工成本
            $offerlist_info['gross_profit'] = round(($offerlist_info['discount_proquant'] - $offerlist_info['artificial_cb'] - $offerlist_info['material_cb'] - $offerlist_info['gift'] - $offerlist_info['design_price'] ),2);
            //毛利率
            $offerlist_info['profit_rate'] = round( $offerlist_info['gross_profit'] / ($offerlist_info['discount_proquant']- $offerlist_info['design_price']) * 100,2);
            //总毛利   工程毛利 - 4个提成 - 运杂 
            $offerlist_info['gross_profit_total'] = round($offerlist_info['gross_profit'] - $offerlist_info['supervisor_commission'] - $offerlist_info['design_commission'] - $offerlist_info['repeat_commission'] - $offerlist_info['business_commission'] - $offerlist_info['sundry'],2);
            //总毛利率 
            $offerlist_info['profit_rate_total'] = round( $offerlist_info['gross_profit_total'] / ($offerlist_info['discount_proquant'] - $offerlist_info['design_price']) * 100,2);

        }else{
            $offerlist_info['gross_profit']  = 0;
            $offerlist_info['profit_rate']  = 0;
            $offerlist_info['gross_profit_total']  = 0;
            $offerlist_info['profit_rate_total']  = 0;
        }
        return $offerlist_info;
    }

    //获取增减项详情
    //oaids = 增减项id数组 
    public function get_append_order_info($oaids){ //offerquota表 的id type=2 直接费加上增减项的项目
        $order_project = Db::name('order_project')->where(['type'=>2,'oa_id'=>$oaids])->order('id','asc')->select();
        return $this->get_order_info_by_project($order_project);
    }

    //获取订单详情
    public function get_order_info_by_project($order_project,$tmp_cost=[]){
        $oid = $order_project[0]['o_id'];
        if($tmp_cost){
            $order_tmp_cost = $tmp_cost;
        }else{
            $order_tmp_cost = Db::name('order_tmp_cost')->where(['oid'=>$oid])->field('name,sign,formula,rate,content,sort')->order('sort','asc')->order('id','asc')->select();
        }
        $order_other = Db::name('order_other')->where(['oid'=>$oid])->find();
        $order_discount = Db::name('order_discount')->where(['oid'=>$oid])->select();
        $offerlist = Db::name('offerlist')->where(['id'=>$oid])->find();
        $offerlist_info = $this->get_order_preview_by_project($order_project,$order_tmp_cost,$order_other,$order_discount,$oid);
        // $offerlist_info = array_merge($offerlist_info,$offerlist);
        //计算礼品成本
        if(!empty($offerlist['gift'])){
            $gift = Model('gift')->getGiftList($oid);
            $offerlist_info['gift'] = $gift['money'];
            $offerlist_info['gift_cost'] = $gift['cost'];
            $offerlist_info['gift_list'] = $gift['data'];
        }else{
            $offerlist_info['gift'] = 0;
            $offerlist_info['gift_cost'] = 0;
            $offerlist_info['gift_list'] = [];
        }
        foreach($offerlist as $k=>$v){
            if(!isset($offerlist_info[$k])){
                $offerlist_info[$k] = $v;
            }
        }
        return $offerlist_info;
    }

    //获取订单预览详情
    public function get_order_preview_by_project($order_project,$tmp_cost,$design = [],$discount = [],$oid = ''){
        // $oid = $order_project[0]['o_id'];
        $offerlist_info =[];
        $offerlist_info['matquant'] = 0;//辅材报价
        $offerlist_info['manual_quota'] = 0;//人工报价
        $offerlist_info['artificial_cb'] = 0;//人工成本
        $offerlist_info['discount'] = 0;//优惠
        $offerlist_info['discount_content'] = '';//优惠说明
        $offerlist_info['discount_zk'] = 0;//旧的折扣优惠
        $offerlist_info['discount_price_old'] = 0;//旧的折扣金额(最原始的优惠方式 大概有100张订单是用这种优惠方式的)
        $discount_type_of_work = $this->discount_type;
        if($oid){
            $order_info = Db::name('offerlist')->where(['id'=>$oid])->find();
            $no_discount = [];
            $no_discount['matquant'] = 0;
            $no_discount['manual_quota'] = 0;
        }
        if(is_array($order_project)){
            foreach($order_project as $keys => $values){
                if(strpos($values['project'],'设计') !== false){
                    $design['design_project_info'] = $values;
                    continue;
                }
                $offerlist_info['matquant'] += $values['quota']*$values['num'];//辅材报价
                $offerlist_info['manual_quota'] += $values['craft_show']*$values['num'];//人工报价
                $offerlist_info['artificial_cb'] += $values['labor_cost']*$values['num'];//人工成本

                //旧折扣 排除打拆不打折
                if(isset($order_info) && $order_info){
                    if($values['type_of_work'] == '打拆工程'){
                        // 打拆不打折
                        $no_discount['matquant'] += $values['quota']*$values['num'];//打拆辅材报价
                        $no_discount['manual_quota'] += $values['craft_show']*$values['num'];//人工报价
                    }else if($values['oa_id'] != 0 && $order_info['discount_append'] == 2){
                        //增加项不打折
                        $no_discount['matquant'] += $values['quota']*$values['num'];//打拆辅材报价
                        $no_discount['manual_quota'] += $values['craft_show']*$values['num'];//人工报价
                    }
                }
                //旧折扣 排除打拆不打折end


                if($design['append_discount'] == 2){
                    //增加项不打折
                    if($values['type'] == 1){
                        //获取是否特价项目 或 纯人工项目
                        if($values['is_special'] == 1){
                            $discount_type_of_work['特价项目'] += ($values['quota']*$values['num']+$values['craft_show']*$values['num']);
                        }
                        if($values['is_artificial'] == 1){
                            $discount_type_of_work['纯人工项目'] += ($values['quota']*$values['num']+$values['craft_show']*$values['num']);
                        }
                        if($values['type_of_work'] != '非标报价'){
                            $discount_type_of_work[$values['type_of_work']] += ($values['quota'] * $values['num'] + $values['craft_show'] * $values['num']);
                        }
                        $discount_type_of_work['材料直接费'] += $values['quota']*$values['num'];//辅材报价
                        $discount_type_of_work['人工直接费'] += $values['craft_show']*$values['num'];//人工报价
                        $discount_type_of_work['工程直接费'] += ($values['quota']*$values['num']+$values['craft_show']*$values['num']);//工程直接费= 辅材报价+人工报价
                    }
                }else{
                    //获取是否特价项目 或 纯人工项目
                    if($values['is_special'] == 1){
                        $discount_type_of_work['特价项目'] += ($values['quota']*$values['num']+$values['craft_show']*$values['num']);
                    }
                    if($values['is_artificial'] == 1){
                        $discount_type_of_work['纯人工项目'] += ($values['quota']*$values['num']+$values['craft_show']*$values['num']);
                    }
                    if($values['type_of_work'] != '非标报价'){
                        $discount_type_of_work[$values['type_of_work']] += ($values['quota'] * $values['num'] + $values['craft_show'] * $values['num']);
                    }
                    $discount_type_of_work['材料直接费'] += $values['quota']*$values['num'];//辅材报价
                    $discount_type_of_work['人工直接费'] += $values['craft_show']*$values['num'];//人工报价
                    $discount_type_of_work['工程直接费'] += ($values['quota']*$values['num']+$values['craft_show']*$values['num']);//工程直接费= 辅材报价+人工报价
                }
                
            }
        }

        $offerlist_info['direct_cost'] = $offerlist_info['matquant']+$offerlist_info['manual_quota'];//工程直接费= 辅材报价+人工报价

       //=================================================旧优惠 
        if(isset($order_info) && $order_info){
            if($order_info['discount_type'] == 2){
                //整单打折
                $offerlist_info['discount_zk'] = ($offerlist_info['direct_cost']-$no_discount['matquant']-$no_discount['manual_quota']) * (1-$order_info['discount_num']/100);//折扣优惠金额

            }elseif($order_info['discount_type'] == 3){
                // 人工打折
                $offerlist_info['discount_zk'] = ($offerlist_info['manual_quota']-$no_discount['manual_quota']) * (1-$order_info['discount_num']/100);//折扣优惠金额
            }else{
                $offerlist_info['discount_zk'] = 0;
            }
            $offerlist_info['discount_zk'] = round($offerlist_info['discount_zk'],2);//旧的折扣优惠
            $offerlist_info['discount_price_old'] = $order_info['discount']?$order_info['discount']:0;//旧的现金优惠
        }
       //=================================================旧优惠 end


        

        //=========================处理优惠=========================
        //第一次算 用于取费模板, 如果有工程报价 加上设计费 有可能数据不正确  所以下面再重新算一次
        foreach($discount as $k1=>$v1){
            if($v1['formula'] == '现金'){
                $discount_type_of_work['现金'] += $v1['rate'];
                continue;
            }
        }
        foreach($discount as $k1=>$v1){
            if(substr_count("工程报价",$v1['formula']) > 0){
                $offerlist_info['discount'] = 0;//有工程报价 等算完取费模板再算优惠
                break;
            }
            if($v1['formula'] == '现金'){
                $offerlist_info['discount'] += $v1['rate'];
                continue;
            }else{
                foreach($discount_type_of_work as $k2=>$v2){
                    $v1['formula'] = str_replace($k2,$v2,$v1['formula']);
                }
                $formula = '('.$v1['formula'].')*'.'(1-'.$v1['rate'].'/100)';
                $offerlist_info['discount'] += eval("return $formula ;");
            }
        }
        //=========================处理优惠end=========================

        //=========================处理取费模板=========================
        $cost_all = 0;//其他费用总计
        $cost_list = [];
        $sign['A1'] = $offerlist_info['direct_cost'];//直接费

        $sign['A2'] = $offerlist_info['discount'] + $offerlist_info['discount_zk'] + $offerlist_info['discount_price_old'];//优惠
        $sign['A3'] = $offerlist_info['matquant'];//材料直接费
        // $sign['A2'] = $offerlist_info['discount'] + $offerlist_info['discount_zk'];//优惠
        $s_location = 0;
        foreach($tmp_cost as $k=>$v){
            if($v['sign'] == 'A1'){
                $tmp_cost[$k]['price'] = round($offerlist_info['direct_cost'],2);
            }else if($v['sign'] == 'S'){
                //工程报价
                if($sign['A2'] > 0){
                    $info = [];
                    $info['price'] = round($offerlist_info['direct_cost'] + $cost_all,2);
                    $info['name'] = '优惠前报价';
                    $s_location = $k;
                }
                $tmp_cost[$k]['price'] = round($offerlist_info['direct_cost'] + $cost_all,2);
                $sign['S'] = $tmp_cost[$k]['price'];
                //工程报价 = 直接费+其他费用总计
                $offerlist_info['proquant'] = round($offerlist_info['direct_cost'] + $cost_all,2);
                //优惠后工程报价 工程报价-优惠
                $offerlist_info['discount_proquant'] = $tmp_cost[$k]['price'];
                $discount_type_of_work['工程报价'] = round($discount_type_of_work['工程直接费'] + $cost_all,2);
                // echo $offerlist_info['discount_proquant'];die;
            }else if($v['sign'] == 'T'){
                //合计
                $tmp_cost[$k]['price'] = round($offerlist_info['direct_cost'] + $cost_all,2);
                $sign['T'] = $tmp_cost[$k]['price'];
                $offerlist_info['total_money'] = $tmp_cost[$k]['price'];
                if($tmp_cost[$k]['price'] == $sign['S']){
                    //总计等于工程报价
                    // unset($tmp_cost[$k]);
                }
            }else if($v['sign'] == 'A2'){
                //优惠
                // if($sign['A2'] == 0){
                //     // unset($tmp_cost[$k]);
                // }else{
                //     $tmp_cost[$k]['price'] = $sign['A2'];
                // }
                $tmp_cost[$k]['price'] = round($sign['A2'],2);
            }else{
                $count_sign = count($sign);
                $num = 1;
                foreach($sign as $k2=>$v2){
                    $v['formula'] = str_replace($k2,$v2,$v['formula']);
                    if($count_sign == $num){
                        $str = $v['formula'];
                        $sign[$v['sign']] = round(eval("return $str ;")*$v['rate']/100,2);
                    }else{
                        $num++;
                    }
                }
                $tmp_cost[$k]['price'] = round($sign[$v['sign']],2);
                $cost_all += $sign[$v['sign']];
            }
        }
        $cost_all = round($cost_all,2);
        

        if($s_location){
            // array_splice($tmp_cost,$s_location,0,array($info));
        }
        $tmp_cost = array_column($tmp_cost,null,'sign');
        //=========================处理设计费=========================
        $offerlist_info['design_price'] = 0;
        if(!empty($design)){
            // $offerlist_info['design_content'] = $design['design_content'];
            $offerlist_info['design_val'] = $design;
            if($design['design_type'] == 1){
                //按项目收取
                if(isset($design['design_project_info'])){
                    if(is_numeric($design['design_project_info']['cost_value']) && is_numeric($design['design_project_info']['num'])){
                        $offerlist_info['design_price'] = $design['design_project_info']['cost_value'] * $design['design_project_info']['num'];
                    }
                }
            }else{
                //按比例收取
                if($design['rate_type'] == 'A1'){
                    $offerlist_info['design_price'] = $design['design_rate'] * $tmp_cost['A1']['price'] / 100; 
                }else if($design['rate_type'] == 'T'){
                    $offerlist_info['design_price'] = $design['design_rate'] * $tmp_cost['T']['price'] / 100;
                }else if($design['rate_type'] == 'S'){
                    $offerlist_info['design_price'] = $design['design_rate'] * $tmp_cost['S']['price'] / 100;
                }else{
                    //提示错误
                    $offerlist_info['design_price'] = 0;
                }
            }
            $offerlist_info['design_price'] = round($offerlist_info['design_price'],2);
            $discount_type_of_work['设计费'] = round($offerlist_info['design_price'],2);//优惠的设计费字段
            if($design['design_in'] == 1){
                //包含在工程报价里
                $tmp_cost['S']['price'] = round($tmp_cost['S']['price'] + $offerlist_info['design_price'],2);
                $tmp_cost['T']['price'] = round($tmp_cost['T']['price'] + $offerlist_info['design_price'],2);
                $discount_type_of_work['工程报价'] = round($discount_type_of_work['工程报价'] + $offerlist_info['design_price'],2);
            }else{
                $tmp_cost['T']['price'] = round($tmp_cost['T']['price'] + $offerlist_info['design_price'],2);
            }
        }else{
            $offerlist_info['design_val'] = [];
        }
        //=========================处理优惠=========================
        
        // $discount_type_of_work['工程报价'] = $tmp_cost['S']['price'];
        $offerlist_info['discount'] = round($offerlist_info['discount_zk'] + $offerlist_info['discount_price_old'],2);
        foreach($discount as $k1=>$v1){
            if($v1['formula'] == '现金'){
                $offerlist_info['discount'] += $v1['rate'];
                if($k1 == 0){
                    $offerlist_info['discount_content'] .= $v1['content'];
                }else{
                    $offerlist_info['discount_content'] .= '、'.$v1['content'];
                }
                continue;
            }else{
                foreach($discount_type_of_work as $k2=>$v2){
                    $v1['formula'] = str_replace($k2,$v2,$v1['formula']);
                }
                $formula = '('.$v1['formula'].')*'.'(1-'.$v1['rate'].'/100)';
                $offerlist_info['discount'] += eval("return $formula ;");
                if($k1 == 0){
                    $offerlist_info['discount_content'] .= $v1['content'];
                }else{
                    $offerlist_info['discount_content'] .= '、'.$v1['content'];
                }
            }
        }
        $offerlist_info['discount'] = round($offerlist_info['discount'],2);
        $offerlist_info['discount_list'] = $discount;
        $tmp_cost['A2']['price'] = $offerlist_info['discount'];
        $tmp_cost['A2']['content'] = $offerlist_info['discount_content'];
        $tmp_cost['T']['price'] = round($tmp_cost['T']['price'] - $offerlist_info['discount'],2);
        //=========================处理优惠end=========================
        $offerlist_info['order_cost'] = array_values($tmp_cost); //全部模板明细
        $offerlist_info['order_cost_all_price'] = round($cost_all,2); //其他费用总计

        //计算辅材成本
        // $offerlist_info['material_cb'] = $this->get_material_list($offerlist_info['id'],$type)['total_money'];
        $offerlist_info['material_cb'] = 0; // 不计算辅材成本 暂时不需要

        //工程报价 = 直接费+其他费用总计
        $offerlist_info['proquant'] = $tmp_cost['S']['price'];
        //优惠后工程报价 工程报价-优惠
        $offerlist_info['discount_proquant'] = $tmp_cost['S']['price'] - $offerlist_info['discount'];
        //总计
        $offerlist_info['total_money'] = $tmp_cost['T']['price'];
        //计算杂项
        
        return $offerlist_info;
    }

    //获取监理申请结算的其他费用总额
    public function getSettlementTotal($sid){
        $total_money = 0;
        $settlement = Db::name('settlement')->where(['id'=>$sid])->find();
        $total_money = $settlement['material_append'] + $settlement['carry'] + $settlement['other_fee'];
        $settlement_worker = Db::name('settlement_worker')->where(['sid'=>$sid])->select();
        foreach($settlement_worker as $k=>$v){
            $total_money += $v['wage'];
            $total_money += $v['rent'];
            $total_money += $v['remote'];
        }
        return $total_money;
    }

    //统计订单 工程报价 直接费 辅材报价 人工报价  并修改到订单
    public function statistical_order($oid){
        $order_info = $this->get_order_info($oid);
        $info = [];
        $info['direct_cost'] = $order_info['direct_cost'];//直接费
        $info['matquant'] = $order_info['matquant'];//辅材报价
        $info['manual_quota'] = $order_info['manual_quota'];//人工报价
        $info['order_cost_all_price'] = $order_info['order_cost_all_price'];//其他费用
        $info['discount_proquant'] = $order_info['discount_proquant'];//优惠后报价
        return Db::name('userlist')->where(['id'=>$order_info['customerid']])->update($info);
        //加入订单表??  加入客户表???  看以后需求
        //增减项后的 要存吗??
    }

    //按收款期 获取订单增减项详情
    public function get_append_info($uid){
        $datas = [];//返回数据
        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find();
        $o_id = Db::name('offerlist')->where(['customerid'=>$uid,'status'=>[3,4,5]])->value('id');//订单id

        $order_append = Db::name('order_append')->where(['o_id'=>$o_id])->select();
        if(!$order_append){
            return $datas;//没有增减项 返回空
        }
        //按收款期分的增减项id
        $oa_ids = [];
        foreach($order_append as $k=>$v){
            $oa_ids[$v['type']][] = $v['id'];
        }
        //获取每期的详情
        foreach($oa_ids as $k=>$v){
            $datas[$k] =  Model('admin/offerlist')->get_append_order_info($v);
        }
        return $datas;
    }

    //根据项目编号返回订单明细
    public function get_info_for_item($id){
        $arr = [];
        $total = ['a_price'=>0,'a_cb'=>0,'m_price'=>0,'m_cb'=>0];
        $offerlist = Db::name('offerlist')->where(['id'=>$id])->find();
        $content = json_decode($offerlist['content'],true);
        $content = $content?$content:[];
        foreach($content as $k=>$v){
             if(!isset($arr[$v['type_of_work']])){
                $arr[$v['type_of_work']]['m_cb'] = 0;
                $arr[$v['type_of_work']]['a_cb'] = 0;
                $arr[$v['type_of_work']]['m_price'] = 0;
                $arr[$v['type_of_work']]['a_price'] = 0;
            }
            $arr[$v['type_of_work']]['m_price'] += $v['quota']*$v['gcl'];//辅材单价
            $arr[$v['type_of_work']]['a_price'] += $v['craft_show']*$v['gcl'];//人工单价
            $arr[$v['type_of_work']]['a_cb'] += $v['labor_cost']*$v['gcl'];//人工成本

            $total['m_price'] += $v['quota']*$v['gcl'];
            $total['a_price'] +=$v['craft_show']*$v['gcl'];
            $total['a_cb'] += $v['labor_cost']*$v['gcl'];
        }
        //辅材成本
        $order_material = Db::name('order_material')->where(['o_id'=>$id,'status'=>1])->select();//该订单全部辅料
        foreach($order_material as $k=>$v){
            if(!isset($arr[$v['type_of_work']])){
                $arr[$v['type_of_work']]['m_cb'] = 0;
            }
            $arr[$v['type_of_work']]['m_cb'] += $v['cb']*$v['num'];//辅材成本
            $total['m_cb'] += $v['cb']*$v['num'];
        }
        return ['datas'=>$arr,'total'=>$total];
    }

    //领料清单
    //type 1合同单 2整单
    public function get_material_list($id,$type=1){
        $total_money = 0;
        $where = [];
        $where['o_id'] = $id;
        if($type == 2){
            $where['status'] = [1,2];
        }else{
            $where['status'] = 1;
        }
        $order_material = Db::name('order_material')->where($where)->select();
        $datas = [];
        foreach($order_material as $k=>$v){
            $v['fine'] = $v['fine']?$v['fine']:'通用';
            if(!isset($datas[$v['type_of_work']][$v['fine']][$v['amcode']])){
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['amcode'] = $v['amcode']; //辅材编码
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['m_name'] = $v['m_name']; //辅材名称
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['brand'] = $v['brand']; //品牌
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['img'] = $v['img']; //图片
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['units'] = $v['units']; //单位
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['num'] = 0; //数量
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['remarks'] = $v['remarks']; //供应来源
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['place'] = $v['place']; //产地
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['phr'] = $v['phr']; //包装规格
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['cb'] = $v['cb']; //单价
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['price_total'] = 0; //总价
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['category'] = $v['category']; //工种
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['price_total'] = $v['fine']; //辅材类别
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['coefficient'] = $v['coefficient'];
                $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['important'] = $v['important'];
            }
            $datas[$v['type_of_work']][$v['fine']][$v['amcode']]['num'] += $v['num'];
        }
        //处理系数
        foreach($datas as $k1=>$v1){
            foreach($v1 as $k2=>$v2){
                foreach($v2 as $k3=>$v3){
                    $num = explode('.',$v3['num']);
                    if(!isset($num[1])){
                        $num[1] = 0;
                    }
                    if($num[1]*10 > $v3['coefficient']){
                        $datas[$k1][$k2][$k3]['omit_num'] = ceil($v3['num']);
                    }else{
                        //不足1时向上取证
                        if($v3['num'] < 1 && $v['important']){
                            $datas[$k1][$k2][$k3]['omit_num'] = ceil($v3['num']);
                        }else{
                            $datas[$k1][$k2][$k3]['omit_num'] = floor($v3['num']);
                        }
                    }
                    $datas[$k1][$k2][$k3]['coefficient'] = ($datas[$k1][$k2][$k3]['omit_num'] * $v3['cb']);
                    $total_money += $datas[$k1][$k2][$k3]['coefficient'];
                }
            }
        }
        return ['datas'=>$datas,'total_money'=>$total_money];
    }
    //获取人工成本


    public function user()
    {
        return $this->belongsTo(AdminUser::class,'userid','userid');
    }
    public function ddyl()
    {
        return $this->hasMany(OrderProject::class,'o_id','id');
    }
}
