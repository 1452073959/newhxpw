<?php

// +----------------------------------------------------------------------
// | 报价管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\OrderProject;
use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;
use think\db\Where;
use think\facade\Cache;

use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\paginator\driver\Bootstrap;

use app\admin\controller\Qrcodes;
class Offerlist extends Adminbase
{
    public $upper = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    public $lower = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    public $gongzhong=array('泥瓦工种','水电工程','木制作','木工其他','扇灰工程','油漆类','形象保护','打拆工种','其他综合项目','加建工程');//工种
    // protected function initialize()
    // {
    //     parent::initialize();
    // }
    public $search = [ 'customer_name','quoter_name','designer_name','address','manager_name' ];
    public $show_page = 15;
    public $status = [0=>'预报单',1=>'预报单',2=>'无效单',3=>'合同单(未审)',4=>'合同单',5=>'待结算',6=>'结算单'];

    //上饶设置结算单底部优惠文字
    public function ajax_add_other(){
        $oid = input('oid');
        $name = input('bname');
        $price = input('bprice');
        $content = input('bcontent');
        array_shift($name);
        array_shift($price);
        array_shift($content);
        $data = [];
        foreach($name as $k=>$v){
            if(empty($v) && empty($price[$k]) && empty($content[$k])){
                continue;
            }
            $info = [];
            $info['oid'] = $oid;
            $info['name'] = $v;
            $info['price'] = $price[$k];
            $info['content'] = $content[$k];
            $data[] = $info;
        }
        Db::startTrans();
        try{
            Db::name('order_bottom_charge')->where(['oid'=>$oid])->delete();
            Db::name('order_bottom_charge')->insertAll($data);
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('修改成功');
    }

    public function preview_order(){
        $admininfo = $this->_userinfo;
        $offerlist_info = $this->save_order_cost_cache(1);//验证取费模板的数据是否正确
        $data_project = input('data');
        if(input('design_type') == 1 && input('design_project') && input('design_num')){
            $data_project['设计费'][input('design_project')] = input('design_num');
        }
         $item_numbers = [];
        //把item_number取出来
        foreach($data_project as $k=>$v){
            foreach($v as $k1=>$v1){
                $item_numbers[] = $k1;
            }
        }
        $no_standard = [];
        if(input('no_standard')){
            $no_standard = input('no_standard');
        }
        $need_project = Db::name('offerquota')->where(['item_number'=>$item_numbers])->select();
        $need_project = array_column($need_project,null,'item_number');//所有添加的非标项目
        $order_project = [];//整合的报价项目
        $time = time();
        foreach ($data_project as $k1 => $v1) {
            foreach($v1 as $k2=>$v2){
                if(isset($no_standard[$k1][$k2])){
                    $item = [];
                    $item['frameid'] = $admininfo['companyid'];
                    $item['item_number'] = $k2;
                    $item['type_of_work'] = '非标报价';
                    $item['of_fb'] =1;
                    $item['uname'] =$admininfo['userid'];
                    $item['frame'] =$admininfo['companyid'];
                    $item['project'] = $no_standard[$k1][$k2]['name'];
                    $item['company'] = $no_standard[$k1][$k2]['unit'];
                    $item['cost_value'] = trim($no_standard[$k1][$k2]['mprice']) + trim($no_standard[$k1][$k2]['aprice']);
                    $item['quota'] = trim($no_standard[$k1][$k2]['mprice']);
                    $item['craft_show'] = trim($no_standard[$k1][$k2]['aprice']);
                    $item['labor_cost'] = 0;//人工成本 非标不算成本 所以为0
                    $item['material'] = $no_standard[$k1][$k2]['content'];
                    $item['content'] = '';
                }else{
                    if(isset($need_project[$k2])){
                        $item = $need_project[$k2];
                    }else{
                        $this->error('项目有误'.$k2,'',$k2);
                    }  
                }
                if(!is_numeric($v2)){
                    $this->error($k2.'工程量有误','',$k2);
                }else{
                    $v2 = trim($v2);
                }
                $project = [];
                $project['oa_id'] = 0;
                if(isset($item['frame']) && $item['frame'] ==$admininfo['companyid']){
                    $project['frame']=$admininfo['companyid'];
                }else{
                    $project['frame']='';
                }
                if(isset($item['of_fb']) && $item['of_fb'] ==1){
                    $project['of_fb']=1;
                }else{
                    $project['of_fb']=0;
                }
                if(isset($item['uname']) && $item['uname'] ==$admininfo['userid']){
                    $project['uname']=$admininfo['userid'];
                }else{
                    $project['uname']='';
                }
                $project['item_number'] = $k2;
                $project['num'] = $v2;
                $project['type_of_work'] = $item['type_of_work'];
                $project['project'] = $item['project'];
                $project['company'] = $item['company'];
                $project['cost_value'] = $item['cost_value'];
                $project['quota'] = $item['quota'];
                $project['quota_now'] = '';
                $project['craft_show'] = $item['craft_show'];
                $project['craft_show_now'] = '';
                $project['labor_cost'] = $item['labor_cost'];
                $project['material'] = $item['material'];
                $project['content'] = $item['content']; 
                $project['type'] = 1;
                $project['add_time'] = $time;
                $project['space'] = $k1;
                $order_project[] = $project;
            }
        }
        $design = [];
        if(input('word') == 1){
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    $design[] = $v;
                    unset($order_project[$k]);
                    continue;
                }
                if(!isset($datas[$v['type_of_work']][$v['space']][$v['item_number']])){
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['project'] = $v['project'];

                }
                $datas[$v['type_of_work']][$v['space']][$v['item_number']]['num'] += $v['num'];
            }
        }else{
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    $design[] = $v;
                    unset($order_project[$k]);
                    continue;
                }
                if(!isset($datas[$v['space']][$v['item_number']])){
                    $datas[$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['space']][$v['item_number']]['project'] = $v['project'];
                    // $item_number[] = $v['item_number'];

                }
                $datas[$v['space']][$v['item_number']]['num'] += $v['num'];
            }
        }

        $userinfo = Db::name('userlist')->where('id',input('t_uid'))->find();
        $offer_type_list = Db::name('offer_type')->where(['companyid'=>$userinfo['frameid'],'status'=>1])->select();
        $offer_type = [1=>[],2=>[]];
        foreach($offer_type_list as $k=>$v){
            $offer_type[$v['type']][] = $v;
        }
        $order_info['unit'] = input('framename');
        $order_info['frameid'] = $userinfo['frameid'];
        $order_info['o_remark'] = input('o_remark');
        $this->assign([
            'datas'=>$datas,
            'order_info'=>$order_info,
            'userinfo'=>$userinfo,
            // 'offerquota'=>$offerquota,
            'offer_type'=>$offer_type,
            'offerlist_info'=>$offerlist_info,
            // 'cost_tmp'=>$cost_tmp,
            'type'=>1,
            'design'=>$design,
        ]);
        return $this->fetch();
    }

    //获取订单设计费
    public function get_order_design(){
        $oid = input('oid');
        $order_info = Db::name('offerlist')->where(['id'=>$oid])->find();
        $other = Db::name('order_other')->where(['oid'=>$oid])->find();
        $order_project = Db::name('offerquota')->where(['frameid'=>$order_info['frameid']])->where('project','like','%设计%')->select();
        $design_project = Db::name('order_project')->where(['o_id'=>$oid])->where('project','like','%设计%')->find();
        // $order_discount = [];
        $this->success('success','',['other'=>$other,'order_project'=>$order_project,'design_project'=>$design_project]);
    }
    //修改订单设计费
    public function set_order_design(){
        $oid = input('oid');
        $order_info = Db::name('offerlist')->where(['id'=>$oid])->find();
        $order_other = [];
        $order_other['design_type'] = input('design_type');
        $order_other['design_in'] = input('design_in');
        // $order_other['design_show'] = input('design_show');
        //按项目收取 上面已经保存了
        if($order_other['design_type'] == 2){
            $order_other['rate_type'] = input('rate_type');
            $order_other['design_rate'] = input('design_rate');
            $order_other['design_content'] = input('design_content');
        }
        Db::startTrans();
        try{
            if($order_other['design_type'] == 1 && input('design_num') > 0){
                if(!is_numeric(input('design_num'))){
                    throw new \think\Exception('请输入正确的数量', 10006);
                }
                // $data_project['设计费'][input('design_project')] = input('design_num');
                $offerquota = Db::name('offerquota')->where(['item_number'=>input('design_project'),'frameid'=>$order_info['frameid']])->find();
                $project = [];
                $project['o_id'] = $oid;
                $project['item_number'] = $offerquota['item_number'];
                $project['num'] = input('design_num');
                $project['type_of_work'] = $offerquota['type_of_work'];
                $project['space'] = '设计费';
                $project['project'] = $offerquota['project'];
                $project['company'] = $offerquota['company'];
                $project['cost_value'] = $offerquota['cost_value'];
                $project['quota'] = $offerquota['quota'];
                $project['craft_show'] = $offerquota['craft_show'];
                $project['labor_cost'] = $offerquota['labor_cost'];
                $project['material'] = $offerquota['material'];
                $project['content'] = $offerquota['content'];
                $project['add_time'] = time();
                $project['frame'] = $order_info['frameid'];
                $project['uname'] = $this->_userinfo['userid'];
                $project['is_special'] = $offerquota['is_special'];
                $project['is_artificial'] = $offerquota['is_artificial'];
                $delete = Db::name('order_project')->where(['o_id'=>$oid])->where('project','like','%设计%')->delete();
                Db::name('order_project')->insert($project);
            }else{
                $delete = Db::name('order_project')->where(['o_id'=>$oid])->where('project','like','%设计%')->delete();
            }
            Db::name('order_other')->where(['oid'=>$oid])->update($order_other);
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('修改成功');
    }

    //获取订单优惠
    public function get_order_discount(){
        $oid = input('oid');
        $type = input('type');
        
        $order_info = Db::name('offerlist')->where(['id'=>$oid])->find();
        if($type == 'edit'){
            $discount_list = Db::name('discount')->where(['fid'=>$order_info['frameid'],'is_del'=>0])->select();
        }else{
            $discount_list = Db::name('discount')->where(['fid'=>$order_info['frameid']])->select();
        }
        
        $order_discount = Db::name('order_discount')->where(['oid'=>$oid])->select();
        $other = Db::name('order_other')->where(['oid'=>$oid])->find();
        // $order_discount = [];
        $this->success('success','',['discount_list'=>$discount_list,'order_discount'=>$order_discount,'other'=>$other]);
    }
    //修改订单优惠
    public function set_order_discount(){
        $oid = input('oid');
        $order_info = Db::name('offerlist')->where(['id'=>$oid])->find();
        if(!$order_info || $order_info['status'] >= 5){
            $this->error('结算订单禁止修改优惠');
        }
        $other = [];
        $other['append_discount'] = input('append_discount');
        // $other['discount_show'] = input('discount_show');
        $discount = [];
        $discount_type = input('discount_type');
        $discount_rate = input('discount_rate');
        $discount_content = input('discount_content');
        array_shift($discount_type);
        array_shift($discount_rate);
        array_shift($discount_content);
        $discount_datas = [];
        $time = time();
        $order_tmp_cost = Db::name('order_tmp_cost')->where(['oid'=>$oid])->select();
        $has_A2 = 0;
        foreach($order_tmp_cost as $k=>$v){
            if(substr_count($v['formula'],"A2") > 0 && $v['sign'] != 'A2'){
                $has_A2 = 1;
                break;
            }
        }
        if($has_A2){
            $discount_list = Db::name('discount')->where(['id'=>$discount_type])->select();
            //判断优惠与取费模板是否冲突
            foreach($discount_list as $k=>$v){
                if(substr_count("工程报价",$v['formula']) > 0){
                    $this->error('取费模板中，计算公式与优惠发生冲突，请修改后再提交');
                }
            }
        }
        if(count($discount_type) <= 1 && empty($discount_type[0])){
            //删除优惠
            Db::name('order_discount')->where(['oid'=>$oid])->delete();
            Db::name('order_other')->where(['oid'=>$oid])->update($other);
            $this->success('修改成功');
        }
        foreach($discount_type as $k=>$v){
            if(empty($v)){
                $this->error('设置优惠中，第'.($k+1) .'行未选择打折方式');
            }
            if(empty($discount_rate[$k])){
                $this->error('设置优惠中，第'.($k+1) .'行未填写折扣比例');
            }
            if(!is_numeric($discount_rate[$k])){
                $this->error('设置优惠中，第'.($k+1) .'行折扣比例必须为数字');
            }
           
            $info = [];
            $info = Db::name('discount')->where(['id'=>$v])->find();
            $info['rate'] = $discount_rate[$k];
            $info['content'] = $discount_content[$k];
            $discount_datas[] = $info;
        }
        $d_type_count = count(array_unique(array_column($discount_datas, 'id')));
        if($d_type_count !== count($discount_datas)){
            $this->error('设置优惠中，打折方式不得重复');
        }
        foreach($discount_datas as $k=>$v){
            $info = $v;
            $info['did'] = $v['id'];
            $info['oid'] = $oid;
            unset($info['id']);
            unset($info['addtime']);
            unset($info['fid']);
            unset($info['is_del']);
            $discount[] = $info;
        }
        
        Db::startTrans();
        try{
            Db::name('order_discount')->where(['oid'=>$oid])->delete();
            Db::name('order_discount')->insertAll($discount); 
            Db::name('order_other')->where(['oid'=>$oid])->update($other); 
            Db::name('offerlist')->where(['id'=>$oid])->update(['discount_type'=>1]); 
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('修改成功');
    }
    
    //保存/预览报价其他费用
    public function save_order_cost_cache($is_valid = 0){
        $data = input();
        $cache = [];
        $design = [];
        $user_id = input('t_uid');
        $userinfo = Db::name('userlist')->where(['id'=>$user_id])->find();
        if(!$user_id){
            $this->error('参数错误');
        }
        //============处理取费模板
        $name = input('t_name');
        $sign = input('t_sign');
        $formula = input('t_formula');
        $rate = input('t_rate');
        $content = input('t_content');
        $sort = input('t_sort');
        array_shift($name);
        array_shift($sign);
        array_shift($formula);
        array_shift($rate);
        array_shift($content);
        array_shift($sort);

        $rate_datas = [];
        $time = time();
        foreach($name as $k=>$v){
            if(empty($v)){
                $this->error('设置费率中，第'.($k+2) .'行字段名称不得为空');
            }
            if(empty($sign[$k])){
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'标识符不得为空');
            }
            if(empty($formula[$k])){
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'公式不得为空');
            }
            if(strlen($rate[$k]) == 0){
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'费率不得为空');
            }

            if (preg_match("/[\x7f-\xff]/", $sign[$k])) {
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'标识符不得含有中文');
            }
            if (preg_match("/[\x7f-\xff]/", $formula[$k])) {
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'公式不得含有中文');
            }
            if(!is_numeric($rate[$k])){
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'费率必须为数字');
            }
            if($rate[$k] > 100 || $rate[$k] < 0){
                $this->error('设置费率中，第'.($k+2) .'行'.$v.'费率必须≥0且≤100');
            }
            $info = [];
            $info['name'] = $v;
            $info['sign'] = $sign[$k];
            $info['formula'] = $formula[$k];
            $info['rate'] = $rate[$k];
            $info['content'] = $content[$k];
            $info['sort'] = $sort[$k];

            $rate_datas[] = $info;
        }
        
        $name_count = count(array_unique(array_column($rate_datas, 'name')));
        $sign_count = count(array_unique(array_column($rate_datas, 'sign')));
        if(count($rate_datas) !== $name_count){
            $this->error('设置费率中，字段名称不得重复');
        }
        if(count($rate_datas) !== $sign_count){
            $this->error('设置费率中，标识符不得重复');
        }
        $has_A2 = 0;
        //判断是否有效
        foreach($rate_datas as $k=>$v){
            if($k === 0){
                if($v['sign'] == 'A1'){
                   $sign_data['A1'] = 1000;//自定义一个直接费
                   $sign_data['A3'] = 100;//自定义一个材料直接费
                }
                continue;
            }
            if($v['sign'] == 'A1'){
                $sign_data['A1'] = 1000;//自定义一个直接费
               $sign_data['A3'] = 100;//自定义一个材料直接费
            }else if($v['sign'] == 'S'){
                //工程报价
                $sign_data['S'] = 10000;//自定义一个工程报价
            }else if($v['sign'] == 'T'){
                //合计
                $sign_data['T'] = 100000;//自定义一个合计
            }else if($v['sign'] == 'A2'){
                //材料直接费
                $sign_data['A2'] = 100;//自定义一个优惠
            }else{
                if(substr_count($v['formula'],"A2") > 0 && $v['sign'] != 'A2'){
                    $has_A2 = 1;
                }
                $count_sign = count($sign_data);
                $num = 1;
                foreach($sign_data as $k2=>$v2){
                    $v['formula'] = str_replace($k2,$v2,$v['formula']);
                    if($count_sign == $num){
                        $str = $v['formula'];
                        if(preg_match("/[\+\-\*\/\.]{2}|[^\+\-\*\/\(\)\d\.]+/i", $v['formula'], $matches)){
                             $this->error('设置费率中，'.$v['t_name'].'计算方式有误，请检查后重新提交3');
                        } else {
                            if(substr_count($v['formula'],"(")==substr_count($v['formula'],")")){
                                
                            } else {
                                 $this->error('设置费率中，'.$v['t_name'].'计算方式有误，请检查后重新提交2');
                            }
                        }

                        if(@eval("return $str;") && @is_numeric(eval("return $str;"))){
                            $sign_data[$v['sign']] = round(eval("return $str;")*$v['rate']/100,2);
                        }else{
                             //模板错误
                            $this->error('设置费率中，'.$v['t_name'].'计算方式有误，请检查后重新提交1');
                        }
                    }else{
                        $num++;
                    }
                }
            }
        }
        $cache['rate_datas'] = $rate_datas;
        //============处理取费模板end
        //============处理项目start
        $project_data = input('data');
        if(input('design_project') && input('design_num')){
            $project_data['设计费'][input('design_project')] = input('design_num');
        }
        $order_project = [];
        $no_standard = input('no_standard')?input('no_standard'):[];
        foreach($project_data as $k1=>$v1){
            foreach($v1 as $k2=>$v2){
                if(isset($no_standard[$k1][$k2])){
                    $item = [];
                    $item['type_of_work'] = '非标报价';
                    $item['project'] = $no_standard[$k1][$k2]['name'];
                    $item['company'] = $no_standard[$k1][$k2]['unit'];
                    $item['cost_value'] = trim($no_standard[$k1][$k2]['mprice']) + trim($no_standard[$k1][$k2]['aprice']);
                    $item['quota'] = trim($no_standard[$k1][$k2]['mprice']);
                    $item['craft_show'] = trim($no_standard[$k1][$k2]['aprice']);
                    $item['labor_cost'] = 0;//人工成本 非标不算成本 所以为0
                    $item['material'] = $no_standard[$k1][$k2]['content'];
                    $item['content'] = '';
                    $item['is_special'] = 0;
                    $item['is_artificial'] = 0;
                }else{
                    $item = Db::name('offerquota')->where('item_number',$k2)->where('frameid',$userinfo['frameid'])->find();//获取定额数据
                    if(!$item){
                        $this->error('项目有误'.$k2,'',$k2);
                    }   
                }
                if(!is_numeric($v2)){
                    $this->error($k2.'工程量有误','',$k2);
                }else{
                    $v2 = trim($v2);
                }

                $project = [];
                // $project['o_id'] = '';//订单id
                $project['oa_id'] = 0;
                
                $project['item_number'] = $k2;
                $project['num'] = $v2;
                $project['type_of_work'] = $item['type_of_work'];
                $project['project'] = $item['project'];
                $project['company'] = $item['company'];
                $project['cost_value'] = $item['cost_value'];
                $project['quota'] = $item['quota'];
                $project['craft_show'] = $item['craft_show'];
                $project['labor_cost'] = $item['labor_cost'];
                $project['material'] = $item['material'];
                $project['content'] = $item['content']; 
                $project['type'] = 1;
                $project['space'] = $k1;
                $project['is_special'] = $item['is_special']; 
                $project['is_artificial'] = $item['is_artificial']; 
                $project['type'] = 1;
                $order_project[] = $project;
                //保存项目的设计费
                if($k1 == '设计费'){
                    $design['project'] = $project;
                }
            }
        }
        //============处理项目end
        //============处理设计费start
        $design['design_type'] = input('design_type');//设计费模式 1:按项 2:按比例
        

        $design['design_rate'] = input('design_rate');
        $design['design_content'] = input('design_content');
        $design['design_in'] = input('design_in');//是否算进工程报价
        // $design['design_show'] = input('design_show');//是否显示在工程报价
        $design['rate_type'] = input('rate_type');//A1直接费 S工程报价 T合计
        $design['append_discount'] = input('append_discount');//
        // $design['discount_show'] = input('discount_show');//
        $cache['design'] = $design;
        //============处理设计费end


        //============处理优惠
        $discount_type = input('discount_type');
        $discount_rate = input('discount_rate');
        $discount_content = input('discount_content');
        array_shift($discount_type);
        array_shift($discount_rate);
        array_shift($discount_content);
        $discount_datas = [];
        $time = time();
        if(!empty($discount_type) && $discount_type[0]){
            if($has_A2){
                $discount_list = Db::name('discount')->where(['id'=>$discount_type])->select();
                //判断优惠与取费模板是否冲突
                foreach($discount_list as $k=>$v){
                    if(substr_count("工程报价",$v['formula']) > 0){
                        $this->error('取费模板中，计算公式与优惠发生冲突，请修改后再提交');
                    }
                }
            }
            
            foreach($discount_type as $k=>$v){
                if(empty($v)){
                    $this->error('设置优惠中，第'.($k+1) .'行未选择打折方式');
                }
                if(empty($discount_rate[$k])){
                    $this->error('设置优惠中，第'.($k+1) .'行未填写折扣比例');
                }
               
                $info = [];
                // $info['d_type'] = $v;
                // $info['d_rate'] = $discount_rate[$k];
                // $info['d_content'] = $discount_content[$k];
                $info = Db::name('discount')->where(['id'=>$v])->find();
                $info['did'] = $v;
                $info['rate'] = $discount_rate[$k];
                $info['content'] = $discount_content[$k];
                $discount_datas[] = $info;
            }
            $d_type_count = count(array_unique(array_column($discount_datas, 'id')));
            if($d_type_count !== count($discount_datas)){
                $this->error('设置优惠中，打折方式不得重复');
            }
        }
        
        $cache['discount_datas'] = $discount_datas;
        //============处理优惠end

        $order_info = Model('offerlist')->get_order_preview_by_project($order_project,$cache['rate_datas'],$cache['design'],$cache['discount_datas']);//这个方法没改过 要重新写

        // var_dump($order_info);die;

        $admininfo = $this->_userinfo;
        if(!$is_valid){
            if($cache){
                //生成订单的时候记得删缓存
                Cache::set('soca'.$user_id.$admininfo['userid'],$cache,86400*14);
            }else{
                Cache::rm('soca'.$user_id.$admininfo['userid']);
            }
        }
        // Cache::rm('soca'.$user_id.$admininfo['userid']);
        if($is_valid){
            return ['order_info'=>$order_info,'cache'=>$cache];
        }else{
            $this->success('success','',$order_info);
        }
    }


    public function del_order(){
        $oid = input('oid');
        $info = Db::name('offerlist')->where(['id'=>$oid])->find();
        if(!$info || $info['is_del'] == 1){
            $this->error('订单不存在');
        }
        if($info['status'] >= 3){
            $this->error('合同单禁止删除');
        }
        $del = Db::name('offerlist')->where(['id'=>$oid])->update(['is_del'=>1]);
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    //获取报价详情 用户导入报价
    public function ajax_get_tmp_order(){
        $oid = input('oid');
        $field = 'item_number,num,type_of_work,space,project,company,cost_value,quota,craft_show,labor_cost,type,of_fb';
        $order_project = Db::name('order_project')->field($field)->where(['o_id'=>$oid])->order('id','asc')->select();
        $item_number = array_unique(array_column($order_project, 'item_number'));
        $offerquota = array_column(Db::name('offerquota')->where(['frameid'=>$this->_userinfo['companyid'],'item_number'=>$item_number])->select(),null, 'item_number');
        foreach($order_project as $k=>$v){
            if(!isset($offerquota[$v['item_number']]) && $v['type_of_work'] != '非标报价'){
                unset($order_project[$k]);
                continue;
            }
        }
        //获取该订单的取费模板
        $tmp_cost_id = Db::name('offerlist')->where(['id'=>$oid])->value('tmp_cost_id');
        ksort($order_project);
        echo json_encode(array('code'=>1,'datas'=>$order_project,'tmp_cost_id'=>$tmp_cost_id));
    }

    //获取客户列表 用于导入客户报价
    public function get_userlist_order(){
        $where = [];
        $where['u.userid'] = $this->_userinfo['userid'];
        $where['o.is_del'] = 0;
        $field = 'u.id as uid,u.userid,u.customer_name,u.quoter_name,u.designer_name,u.manager_name,u.address,u.area,u.room_type,u.addtime';
        $field .= ',o.status,o.id as oid,o.discount_type,o.discount_num,o.discount,o.tmp_cost_id,o.entrytime';
        $offerlist = Db::name('offerlist')->where($where)->field($field)->alias('o')->join('userlist u','u.id = o.customerid')->select();
        //获取取费模板
        $tmp_cost = array_column(Db::name('tmp_cost')->where(['f_id'=>$this->_userinfo['companyid']])->select(),'tmp_name','tmp_id');
        // var_dump($offerlist);die;
        $datas = [];
        foreach($offerlist as $k=>$v){
            if(!isset($datas[$v['uid']])){
                $datas[$v['uid']]['uid'] = $v['uid'];
                $datas[$v['uid']]['customer_name'] = $v['customer_name'];
                $datas[$v['uid']]['quoter_name'] = $v['quoter_name'];
                $datas[$v['uid']]['designer_name'] = $v['designer_name'];
                $datas[$v['uid']]['manager_name'] = $v['manager_name'];
                $datas[$v['uid']]['address'] = $v['address'];
                $datas[$v['uid']]['area'] = $v['area'];
                $datas[$v['uid']]['room_type'] = $v['room_type'];
                $datas[$v['uid']]['addtime'] = date('Y-m-d',$v['addtime']);
            }
            $order_info = Model('offerlist')->get_order_info($v['oid'],2);
            $order = [];
            $order['oid'] = $v['oid'];
            $order['direct_cost'] = round($order_info['direct_cost'],2);
            $order['discount_proquant'] = round($order_info['discount_proquant'],2);
            //折扣
            if($v['discount_type'] == 2){
                $order['discount'] = '整单'. $v['discount_num']/10 .'折';
            }else if($v['discount_type'] == 3){
                $order['discount'] = '人工'. $v['discount_num']/10 .'折';
            }else if($v['discount_type'] == 2){
                $order['discount'] = '现金优惠￥'. $v['discount'];
            }else{
                $order['discount'] = '无';
            }
            $order['status'] = $this->status[$v['status']];
            $order['tmp_name'] = isset($tmp_cost[$v['tmp_cost_id']])?$tmp_cost[$v['tmp_cost_id']]:'';
            $order['addtime'] = date('Y-m-d',$v['entrytime']);
            $datas[$v['uid']]['order'][] = $order;
        }
        $this->success('success','',$datas);
    }

    //获取报价单(打印的最下面)备注
    public function set_o_remark(){
        $oid = input('oid');
        $o_remark = input('o_remark');
        $offerlist = Db::name('offerlist')->where(['id'=>$oid])->find();
        if(!$offerlist){
            $this->error('订单有误');
        }
        if($offerlist['status'] >= 5){
            $this->error('结算状态禁止修改备注');
        }
        if($o_remark){
            Db::name('offerlist')->where(['id'=>$oid])->update(['o_remark'=>$o_remark]);
        }else{
            $o_remark = Db::name('offerlist')->where(['id'=>$oid])->value('o_remark');
        }
        $this->success('success','',$o_remark);
    }

    //获取订单提成明细
    public function get_commission_info(){
        $o_id = input('o_id');
        $commission = Db::name('offerlist')->where(['id'=>$o_id])->field('business_commission,repeat_commission,design_commission,supervisor_commission')->find();
        if($commission){
            $this->success('success','',$commission);
        }else{
            $this->error('error');
        }
    }

    //设置订单折扣
    public  function set_discount(){
        $id = input('id');
        $discount_type = input('discount_type');
        $discount_num = input('discount_num');
        $discount_append = input('discount_append')?input('discount_append'):1;
        $offerlist = Db::name('offerlist')->where(['id'=>$id])->find();
        if($offerlist['status'] >= 5){
            $this->error('结算状态禁止修改优惠');
        }
        if($discount_type == 4){
            $res = Db::name('offerlist')->where(['id'=>$id])->update(['discount_type'=>$discount_type,'discount_num'=>100,'discount'=>$discount_num,'discount_append'=>$discount_append]);
        }else{
            if($discount_type == 1){
                //不打折
                $discount_num = 100;
            }else{
                if(!is_numeric($discount_num) || strpos($discount_num,".") || $discount_num <= 0 || $discount_num > 100){
                    $this->error('优惠额度设置有误');
                }
            }
            $res = Db::name('offerlist')->where(['id'=>$id])->update(['discount_type'=>$discount_type,'discount_num'=>$discount_num,'discount'=>0,'discount_append'=>$discount_append]);
        }
        $this->success('设置优惠成功');
        // if($res){
        //     $this->success('设置优惠成功');
        // }else{
        //     $this->error('设置优惠失败');
        // }

    }


    //临时保存报价订单
    public function temporary_save_order(){
        $user_id = input('user_id');
        if(!$user_id){
            $this->error('参数错误1');
        }
        $userinfo = $this->_userinfo;
        if(!$userinfo){
            $this->error('参数错误2');
        }
        if(input('data')){
            Cache::set('tso_'.$user_id.$userinfo['userid'],input('data'),86400*14);
            Cache::set('tson_'.$user_id.$userinfo['userid'],input('no_standard'),86400*14);
            $this->success('success');
        }else{
            Cache::rm('tso_'.$user_id.$userinfo['userid']);
            Cache::rm('tson_'.$user_id.$userinfo['userid']);
            $this->success('删除缓存');
        }
    }

    //获取临时保存的订单
    public function get_temporary_order(){
        $userinfo = $this->_userinfo;
        //判断是否有临时保存的订单
        $no_standard = [];
        $temporary_order = Cache::get('tso_'.input('customer_id').$userinfo['userid']);
        $tson = Cache::get('tson_'.input('customer_id').$userinfo['userid']);
        if($tson){
            foreach($tson as $k=>$v){
                foreach($v as $k1=>$v1){
                    $no_standard[$k1] = $v1;
                }
            }
        }
        
        if($temporary_order){
            $success_temporary_order = 1;//判断临时订单是否失效 1:有效 2:失效
            $item_number = [];//所有项目编号集合
            $offer_type_check = [1=>[],2=>[]];//用于检测工种/空间是否还有效
            $offer_type_list = Db::name('offer_type')->where(['companyid'=>$userinfo['companyid'],'status'=>1])->select();
            foreach($offer_type_list as $k=>$v){
                $offer_type_check[$v['type']][] = $v['name'];
            }
            foreach($temporary_order as $k=>$v){
                if(!in_array($k, $offer_type_check[2])){
                    //空间不存在
                    $success_temporary_order = 2;
                    break;
                }else{
                    foreach($v as $k1=>$v1){
                        $item_number[] = $k1;
                    }

                }
            }
            $offerquota = Db::name('offerquota')->where(['item_number'=>$item_number,'frameid'=>$userinfo['companyid']])->select();
            $item_number = array_unique($item_number);//去重
            if(count($item_number) != count($offerquota) && 0){
                $success_temporary_order = 2;
            }else{
                $offerquota = array_column($offerquota, null,'item_number');
                //重新组合数组 组合成模板导入的格式 (方便复制代码)
                $datas = [];
                foreach ($temporary_order as $k1 => $v1) {
                    foreach($v1 as $k2=>$v2){
                        $info = [];
                        if(isset($offerquota[$k2]['type_of_work'])){
                            $info['work_type'] = $offerquota[$k2]['type_of_work'];
                        }else{
                            $info['work_type'] = '非标工种';//这个可以不要
                        }
                        
                        $info['space'] = $k1;
                        $info['item_number'] = $k2;
                        $info['num'] = $v2;
                        $datas[] = $info;
                    }
                }
            }
            if($success_temporary_order == 1){
                echo json_encode(['code'=>1,'datas'=>$datas,'offerquota'=>$offerquota,'no_standard'=>$no_standard]);die;
            }else{
                $this->error('error temporary order');die;
            }
        }else{
            $this->error('no temporary order');die;
        }
    }

    //报价 根据工种 获取项目
    public function ajax_get_project(){
        $userinfo = $this->_userinfo;
        $word_name = input('word_name');
        $where = [];
        if(input('word_name')){
            $where['type_of_work'] = input('word_name');
        }
        $where['frameid'] = $userinfo['companyid'];
        $datas = Db::name('offerquota')->where($where)->field('item_number,type_of_work,project,company,cost_value,quota,craft_show,labor_cost')->select();
        foreach($datas as $k=>$v){
            if(strpos($v['project'],'设计') !== false){
                unset($datas[$k]);
            }
        }
        $datas = array_merge($datas);
        echo json_encode(['code'=>1,'datas'=>$datas]);die;
    }

    //选择模板
    public function check_tmp_cost(){
        $userinfo = $this->_userinfo;
        $tmp_id = input('tmp_id');
        $o_id = input('o_id');
        $offerlist = Db::name('offerlist')->where(['id'=>$o_id])->find();
        if(!$offerlist){
            $this->error('订单不存在');
        }
        $has = Db::name('order_tmp_cost')->where(['oid'=>$o_id])->count();
        if($has){
            $this->error('订单已存在模板');
        }

        $tmp_cost = Db::name('tmp_cost')->where(['tmp_id'=>$tmp_id])->field('f_id,name,sign,formula,rate,content,sort')->order('sort','asc')->order('id','asc')->select();
        $time = time();
        foreach($tmp_cost as $k=>$v){
            $tmp_cost[$k]['add_time'] = $time;
            $tmp_cost[$k]['oid'] = $o_id;
        }
        $res = Db::name('order_tmp_cost')->insertAll($tmp_cost);
        if($res){
            $this->success('选择模板成功');
        }else{
            $this->error('选择模板失败');
        }
    }



    //报单(新)
    public function add_order(){
        if(!input('customer_id')){
            $this->error('非法操作');
        }
        $userinfo = $this->_userinfo;
        $offer_type_list = Db::name('offer_type')->where(['adminid'=>[0,$this->_userinfo['userid']]])->where(['companyid'=>$userinfo['companyid'],'status'=>1])->select();
        $offer_type = [1=>[],2=>[]];
        foreach($offer_type_list as $k=>$v){
            $offer_type[$v['type']][] = $v;
        }
        $customer_info = Db::name('userlist')->where(['id'=>input('customer_id')])->find();
        if($customer_info['frameid'] != $userinfo['companyid']){
            $this->error('非本公司客户禁止报价');
        }
        //取费模板
        $tmp_cost = Db::name('tmp_cost')->where(['f_id'=>$userinfo['companyid'],'status'=>1])->group('tmp_id')->order('id','desc')->select();
        //另存订单
        if(input('report_id')){
            $order_project = Db::name('order_project')->where(['o_id'=>input('report_id')])->select();
            foreach($offer_type_list as $k=>$v){
                $offer_type_check[$v['type']][] = $v['name'];
            }
            $item_number = [];
            $fb_num = [];//存在非标个数
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    unset($order_project[$k]);
                    continue;
                }
                if(!isset($data[$v['space']][$v['item_number']])){
                    //空间不存在 自动添加该空间
                    if(!in_array($v['space'], $offer_type_check[2])){
                        //空间不存在 自动添加该空间
                        if($this->_userinfo['roleid'] == 10){
                            $adminid = 0;
                        }else{
                            $adminid = $this->_userinfo['userid'];
                        }
                        $space_data = Db::name('offer_type')->where(['name'=>$v['space'],'type'=>2,'companyid'=>$userinfo['companyid'],'adminid'=>$adminid])->find();
                        if($offer_type){
                            if($space_data['status'] == 0){
                                $has[] = $v;
                            }elseif($space_data['status'] == 9){
                                Db::name('offer_type')->where(['name'=>$v['space'],'type'=>2,'companyid'=>$userinfo['companyid'],'adminid'=>$adminid])->update(['status'=>1,'addtime'=>time()]);
                            }
                        }else{
                            Db::name('offer_type')->insert(['name'=>$v['space'],'type'=>2,'companyid'=>$userinfo['companyid'],'addtime'=>time(),'adminid'=>$adminid]);
                        }
                        // dump($offer_type);die;
                        $offer_type[2][] = ['name'=>$v['space']];
                        $offer_type_check[2][] = $v['space'];

                    }
                    //空间不存在 自动添加该空间 end
                    $data[$v['space']][$v['item_number']] = 0;//初始化数量
                    $item_number[] = $v['item_number'];//用于计算是否遗漏
                    if( $v['of_fb'] != 0 && $v['of_fb'] != 3 && $v['of_fb'] != 5){
                        $fb_num[] = $v['item_number'];
                    }
                }
                $data[$v['space']][$v['item_number']] += $v['num'];
            }
            $item_number = array_unique($item_number);
            $fb_num = array_unique($fb_num);
            // $item_number_num = count($item_number);
            $offerquota = Db::name('offerquota')->where(['item_number'=>$item_number,'frameid'=>$userinfo['companyid']])->select();
            $offerquota = array_column($offerquota, null,'item_number');
            $key_offerquota = array_keys($offerquota);
            if(count($item_number) != (count($offerquota)+count($fb_num))){
                // $this->error('订单部分项目不全，另存订单失败');
                foreach($data as $k=>$v){
                    foreach($v as $k1=>$v1){
                        if(!in_array($k1, $key_offerquota)){
                            unset($data[$k][$k1]);
                        }
                    }
                }
            }
            $order_info = Db::name('offerlist')->where(['id'=>input('report_id')])->find();
            $order_project = array_column($order_project, null,'item_number');
            Cache::rm('tso_'.input('customer_id').$userinfo['userid']);
            Cache::rm('tson_'.input('customer_id').$userinfo['userid']);
            
            //获取订单其他配置
            $soca = [];
            $soca['rate_datas'] = Db::name('order_tmp_cost')->where(['oid'=>input('report_id')])->select();
            $soca['discount_datas'] = Db::name('order_discount')->where(['oid'=>input('report_id')])->select();
            $soca['design'] = Db::name('order_other')->where(['oid'=>input('report_id')])->find();
            $design_project = Db::name('order_project')->where(['o_id'=>input('report_id')])->where('project','like','%设计%')->find();
            $order_info['info'] = Model('offerlist')->get_order_info(input('report_id'));
            if($design_project){
                $soca['design']['project'] = $design_project;
            }
            if($order_info['gift']){
                $gift = json_decode($order_info['gift'],true);
                $gift_key = array_keys($gift);
                $soca['gift'] = array_column(Db::name('gift')->where(['id'=>$gift_key])->select(),null,'id');
                foreach($soca['gift'] as $k=>$v){
                    $soca['gift'][$k]['num'] = $gift[$k];
                }
            }
            $this->assign([
                'data'=>$data,
                'order_info'=>$order_info,
                'offerquota'=>$offerquota,
                'order_project'=>$order_project,
            ]);
            // $soca = Cache::get('soca'.input('customer_id').$userinfo['userid']);
        }else{
            $soca = Cache::get('soca'.input('customer_id').$userinfo['userid']);
        }

        //获取当前用户分公司名称
        $f_name = Db::name('frame')->where(['id'=>$userinfo['companyid']])->value('name');
        //获取公司配置
        $cost_tmp=Db::name('cost_tmp')->where('f_id',$userinfo['companyid'])->find();
        //获取设计费
        $discount_project = Db::name('offerquota')->where(['frameid'=>$userinfo['companyid']])->where('project','like','%设计%')->select();
        //获取礼品库
        $gift = Model('gift')->getGiftTree($userinfo['companyid']);
        //获取优惠
        $discount = Db::name('discount')->where(['fid'=>$userinfo['companyid'],'is_del'=>0])->select();
        //获取取费模板缓存
        // $soca = Cache::get('soca'.input('customer_id').$userinfo['userid']);
        $this->assign([
            'offer_type'=>$offer_type,
            'customer_info'=>$customer_info,
            'tmp_cost'=>$tmp_cost,
            'f_name'=>$f_name,
            'cost_tmp'=>$cost_tmp,
            'discount_project'=>$discount_project,
            'gift'=>$gift,
            'discount'=>$discount,
            'soca'=>$soca,
        ]);
        // dump($soca);die;
        return $this->fetch();
    }

    //报价操作 - 生成订单
    public function add_order_operation(){
        if(input('data') && $this->request->isPost()){
            $time = time();
            $other = $this->save_order_cost_cache(1);//验证取费模板的数据是否正确
            // var_dump($other);die;
            $data = array(); //最终数据
            $data['entrytime'] = $time;
            $order_other = [];//订单额外数据
            $data_project = input('data');
            //===================================加上项目的设计费===============================data[客厅、餐厅、过道][4660369650]
            if(input('design_type') == 1 && input('design_project') && input('design_num')){
                $data_project['设计费'][input('design_project')] = input('design_num');
            }
            
            //===================================加上项目的设计费end===============================
            $admininfo = $this->_userinfo;
            $data['userid'] = $admininfo['userid'];
            $data['frameid'] = $admininfo['companyid'];//存公司id到报表
            $data['customerid'] = input('customerid');
            $data['unit'] = input('framename');//单位
            // $data['number'] = 1; //目测没用的
            $data['remark'] = input('remark');
            $data['o_remark'] = input('o_remark');
            //非标
            $no_standard = [];
            if(input('no_standard')){
                $no_standard = input('no_standard');
                $data['no_standard'] = 3;
            }
            //================================处理项目================================
            $item_numbers = [];
            //把item_number取出来
            foreach($data_project as $k=>$v){
                foreach($v as $k1=>$v1){
                    $item_numbers[] = $k1;
                }
            }
            $need_project = Db::name('offerquota')->where(['item_number'=>$item_numbers,'frameid'=>$admininfo['companyid']])->select();
            $need_project = array_column($need_project,null,'item_number');//所有添加的非标项目
            $order_project = [];//整合的报价项目
            foreach ($data_project as $k1 => $v1) {
                foreach($v1 as $k2=>$v2){
                    if(isset($no_standard[$k1][$k2])){
                        $item = [];
                        $item['frameid'] = $admininfo['companyid'];
                        $item['item_number'] = $k2;
                        $item['type_of_work'] = '非标报价';
                        $item['of_fb'] =1;
                        $item['uname'] =$admininfo['userid'];
                        $item['frame'] =$admininfo['companyid'];
                        $item['project'] = $no_standard[$k1][$k2]['name'];
                        $item['company'] = $no_standard[$k1][$k2]['unit'];
                        $item['cost_value'] = trim($no_standard[$k1][$k2]['mprice']) + trim($no_standard[$k1][$k2]['aprice']);
                        $item['quota'] = trim($no_standard[$k1][$k2]['mprice']);
                        $item['craft_show'] = trim($no_standard[$k1][$k2]['aprice']);
                        $item['labor_cost'] = 0;//人工成本 非标不算成本 所以为0
                        $item['material'] = $no_standard[$k1][$k2]['content'];
                        $item['content'] = '';
                    }else{
                        if(isset($need_project[$k2])){
                            $item = $need_project[$k2];
                        }else{
                            $this->error('项目有误'.$k2,'',$k2);
                        }  
                    }
                    if(!is_numeric($v2)){
                        $this->error($k2.'工程量有误','',$k2);
                    }else{
                        $v2 = trim($v2);
                    }
                    $project = [];
                    $project['oa_id'] = 0;
                    if(isset($item['frame']) && $item['frame'] ==$admininfo['companyid']){
                        $project['frame']=$admininfo['companyid'];
                    }else{
                        $project['frame']='';
                    }
                    if(isset($item['of_fb']) && $item['of_fb'] ==1){
                        $project['of_fb']=1;
                    }else{
                        $project['of_fb']=0;
                    }
                    if(isset($item['uname']) && $item['uname'] ==$admininfo['userid']){
                        $project['uname']=$admininfo['userid'];
                    }else{
                        $project['uname']='';
                    }
                    $project['item_number'] = $k2;
                    $project['num'] = $v2;
                    $project['type_of_work'] = $item['type_of_work'];
                    $project['project'] = $item['project'];
                    $project['company'] = $item['company'];
                    $project['cost_value'] = $item['cost_value'];
                    $project['quota'] = $item['quota'];
                    $project['quota_now'] = '';
                    $project['craft_show'] = $item['craft_show'];
                    $project['craft_show_now'] = '';
                    $project['labor_cost'] = $item['labor_cost'];
                    $project['material'] = $item['material'];
                    $project['content'] = $item['content']; 
                    $project['type'] = 1;
                    $project['add_time'] = $time;
                    $project['space'] = $k1;
                    $order_project[] = $project;
                }
            }
            //================================处理项目end================================
            //================================处理辅材成本================================
            $order_material = [];//订单辅料详情 - 以后顶替material_all这种json储存方法
            foreach($order_project as $k=>$v){
                $need_material = json_decode($v['content'],true);//需要的物料
                if($need_material){
                    foreach($need_material as $one_material){
                        if($one_material[0] && $one_material[1]){
                            $materials_info = Db::name('materials')->where(array('frameid'=>$admininfo['companyid']))->where('name|amcode','=',$one_material[0])->find();
                            if(!$materials_info['price']){
                                $this->error($one_material[0].'成本有误，请及时补充辅材仓库'.$v['item_number']);
                            }
                            //===============订单辅料详情  $arr['工种类']['项目编号']['辅材名称'] = ['数量','成本','单价','利润']
                            if(!isset($order_material[$v['type_of_work']][$v['item_number']][$one_material[0]])){
                                //初始化数据 这个框架会神奇的报错 = =
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['cb'] = $materials_info['price'];//成本
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['price'] = $v['quota'];//辅材单价
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['name'] = $materials_info['name'];//辅材名称
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['profit'] = $v['quota']-$materials_info['price'];//利润
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['coefficient'] = $materials_info['coefficient'];//系数
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['important'] = $materials_info['important'];//是否重要
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['num'] = 0;//初始化数据
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['amcode'] = $materials_info['amcode'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['fine'] = $materials_info['fine'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['brand'] = $materials_info['brand'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['place'] = $materials_info['place'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['img'] = $materials_info['img'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['phr'] = $materials_info['phr'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['remarks'] = $materials_info['remarks'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['category'] = $materials_info['category'];//
                                $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['units'] = $materials_info['units'];//
                            }
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['num'] += $one_material[1]*$v['num'];
                        }
                    }
                }
            }
            //=========订单辅料详情 组装数据存进数据库
            //$order_material['工种类']['项目编号']['辅材名称'] = ['数量','成本','单价','利润']
            $order_material_datas = [];
            foreach($order_material as $k1=>$v1){
                foreach($v1 as $k2=>$v2){
                    foreach($v2 as $k3=>$v3){
                        // $order_material_datas['o_id'] = '';//还没有
                        $data_info['c_id'] = $data['customerid'];
                        $data_info['f_id'] = $data['frameid'];
                        $data_info['type_of_work'] = $k1;;
                        $data_info['item_number'] = $k2;;
                        $data_info['m_name'] = $v3['name'];
                        $data_info['num'] = $v3['num'];
                        $data_info['cb'] = $v3['cb'];
                        $data_info['price'] = $v3['price'];
                        $data_info['profit'] = $v3['profit'];
                        $data_info['coefficient'] = $v3['coefficient'];
                        $data_info['important'] = $v3['important'];
                        $data_info['amcode'] = $v3['amcode'];
                        $data_info['fine'] = $v3['fine'];
                        $data_info['brand'] = $v3['brand'];
                        $data_info['place'] = $v3['place'];
                        $data_info['img'] = $v3['img'];
                        $data_info['phr'] = $v3['phr'];
                        $data_info['remarks'] = $v3['remarks'];
                        $data_info['category'] = $v3['category'];
                        $data_info['units'] = $v3['units'];
                        $order_material_datas[] = $data_info;
                    }
                }
            }
            //================================处理辅材成本end=============================
            //================================处理礼品=============================
            $gift = [];
            if(input('gift')){
                foreach(input('gift') as $k=>$v){
                    $gift[$k] = $v;
                }
                $data['gift'] = json_encode($gift);
                $order_other['gift_content'] = input('gift_content');
                // $order_other['gift_show'] = input('gift_show');
            }
            //================================处理礼品end=============================



            $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$data['frameid']])->find();
            if(!$cost_tmp){
                //没有设置  这个是默认值
                $cost_tmp_data = [
                    'supervisor_commission'=>0,
                    'design_commission'=>0,
                    'repeat_commission'=>0,
                    'business_commission'=>0
                ];
            }else{
                $cost_tmp_data = [
                    'supervisor_commission'=>$cost_tmp['supervisor'],
                    'design_commission'=>$cost_tmp['design'],
                    'repeat_commission'=>$cost_tmp['repeat'],
                    'business_commission'=>$cost_tmp['business']
                ];
            }
            $data = array_merge($data,$cost_tmp_data);
            Db::startTrans();
            try{
                $oid = Db::name('offerlist')->insertGetId($data);
                
                //项目
                foreach($order_project as $k=>$v){
                    $order_project[$k]['o_id'] = $oid;
                }
                $order_project_res = Db::name('order_project')->insertAll($order_project);

                //辅材成本
                foreach($order_material_datas as $k=>$v){
                    $order_material_datas[$k]['o_id'] = $oid;
                }
                if($order_material_datas){
                    $order_material_res = Db::name('order_material')->insertAll($order_material_datas);
                }else{
                    $order_material_res = 1;
                }
                //================================处理取费模板================================
                if(isset($other['order_info']['order_cost'])){
                    $order_tmp_cost = [];
                    foreach($other['order_info']['order_cost'] as $k=>$v){
                        $info = [];
                        $info['oid'] = $oid;
                        $info['f_id'] = $data['frameid'];
                        $info['name'] = $v['name'];
                        $info['sign'] = $v['sign'];
                        $info['formula'] = $v['formula'];
                        $info['rate'] = $v['rate'];
                        $info['add_time'] = $time;
                        $info['content'] = $v['content'];
                        $info['sort'] = $v['sort'];
                        $order_tmp_cost[] = $info;
                    }
                    Db::name('order_tmp_cost')->insertAll($order_tmp_cost);
                }
                //================================保存取费模板end=============================
                //================================保存设计费=============================
                if(isset($other['order_info']['design_val']) && !empty($other['order_info']['design_val'])){
                    $design = [];
                    //按项目收取 上面已经保存了
                    if($other['order_info']['design_val']['design_type'] == 2){
                        $order_other['rate_type'] = $other['order_info']['design_val']['rate_type'];
                        $order_other['design_rate'] = $other['order_info']['design_val']['design_rate'];
                        $order_other['design_content'] = $other['order_info']['design_val']['design_content'];
                    }
                }
                //================================保存设计费end=============================

                //================================保存优惠=============================
                if(isset($other['order_info']['discount_list']) && !empty($other['order_info']['discount_list'])){
                    $discount = [];
                    foreach($other['order_info']['discount_list'] as $k=>$v){
                        $info = $v;
                        $info['did'] = $v['id'];
                        $info['oid'] = $oid;
                        unset($info['id']);
                        unset($info['addtime']);
                        unset($info['fid']);
                        unset($info['is_del']);
                        $discount[] = $info;
                    }
                    Db::name('order_discount')->insertAll($discount);
                }
                //================================保存优惠end==========================
                //订单其他信息
                $order_other['design_type'] = input('design_type');
                $order_other['design_in'] = input('design_in');
                // $order_other['design_show'] = input('design_show');
                $order_other['append_discount'] = input('append_discount');
                // $order_other['discount_show'] = input('discount_show');
                $order_other['oid'] = $oid;
                Db::name('order_other')->insert($order_other);
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            if($oid!==false && $order_material_res && $order_project_res){
                Cache::rm('tso_'.input('customerid').$admininfo['userid']);
                Cache::rm('tson_'.input('customerid').$admininfo['userid']);
                Cache::rm('soca'.input('customerid').$admininfo['userid']);
                // echo 1;
                $this->success('保存订单成功',url('admin/offerlist/history',array('customerid'=>input('customerid'),'report_id'=>$oid)));
            }else{
                $this->error('失败');
            }
        }
    }

    public function upload_cad(){
        $file = request()->file('file');
        if($file && input('o_id') && input('customer_id')){
            $in = Db::name('offerlist')->where(['customerid'=>input('customer_id'),'status'=>[3,4,5]])->count();
            $order_info = Db::name('offerlist')->where(['id'=>input('o_id')])->find();
            $order_tmp_cost = Db::name('order_tmp_cost')->where(['oid'=>input('o_id')])->count();

            if(!$order_tmp_cost){
                $this->error('请选择取费模板');
            }
            if($in > 0){
                $this->error('一个客户只能拥有一份合同价');
            }
            // $no_standard=  Db::table('fdz_offerlist')->where('id',input('o_id'))->value('no_standard');
            // if($offerlist==1){
            //     $this->error('该订单有非标,请审核后再变更');
            // }else if($offerlist==3){
            //     $this->error('该订单正在审核,请在审核后变更');
            // }else if($offerlist==4) {
            //     $this->error('该订单正在审核,请在审核后变更');
            // }else if($offerlist==6){
            //     $this->error('该订单非标被驳回,无法变更');
            // }else{
            //     $info = $file->validate(['size'=>10485760])->move( './uploads/cad');
            //     if($info){
            //         // 成功上传后 获取上传信息
            //         $res = Db::name('offerlist')->where(['id'=>input('o_id')])->update(['status'=>3,'cad_file'=>$info->getSaveName()]);
            //         Db::name('userlist')->where(['id'=>input('customer_id')])->update(['status'=>2,'sign_bill_time'=>time(),'oid'=>input('o_id')]);
            //         Model('offerlist')->statistical_order(input('o_id'));
            //         if($res){
            //             $this->success('修改成功');
            //         }else{
            //             $this->error('修改失败');
            //         }
            //     }else{
            //         // 上传失败获取错误信息
            //         $this->error($file->getError());
            //     }
            // }
            $info = $file->validate(['size'=>10485760])->move( './uploads/cad');
            if($info){
                // 成功上传后 获取上传信息
                $res = Db::name('offerlist')->where(['id'=>input('o_id')])->update(['status'=>3,'cad_file'=>$info->getSaveName()]);
                Db::name('userlist')->where(['id'=>input('customer_id')])->update(['status'=>2,'sign_bill_time'=>time(),'oid'=>input('o_id')]);
                Model('offerlist')->statistical_order(input('o_id'));
                if($res){
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
        }else{
            $this->error('未上传图片');
        }
    }

    //上传效果图
    public function upload_effect_img(){
        $file = request()->file('file');
        if($file && input('o_id')){
            $info = $file->validate(['size'=>10485760])->move( './uploads/cad');
            $res = Db::name('offerlist')->where(['id'=>input('o_id')])->update(['effect_img'=>$info->getSaveName()]);
            if($res){
                $this->success('上传成功');
            }else{
                $this->error('上传失败');
            }
        }else{
            $this->error('未上传图片');
        }
    }












    //=====================================================================下面的不知道是什么 有用再往上挪

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
            $condition = array(['addtime','>',strtotime(input('begin_time'))],['addtime','<',strtotime('+1 day',strtotime(input('end_time')))]);
        }
        $userinfo = $this->_userinfo;
        if($userinfo['userid'] != 1 && $userinfo['roleid'] != 10  && $userinfo['roleid'] != 22){
            //只能看自己的单
            $da['userid'] = $userinfo['userid'];
        }
        if($userinfo['roleid'] == 1){
            if(input('fid')){
                $da['frameid'] = input('fid');
            }
        }else{
            if($userinfo['roleid'] == 10 || $userinfo['roleid'] == 22){
                $da['frameid'] = $userinfo['companyid'];
            }
        }
        
        if(input('oi')==1){
            $re = Db::name('userlist')->where('oid','>',0)->where($where)->where($da)->where($condition)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);
        }elseif(input('oi')==2){
            $re = Db::name('userlist')->where('oid','=',0)->where($where)->where($da)->where($condition)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);
        }else{
            $re = Db::name('userlist')->where($where)->where($da)->where($condition)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);
        }
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        $this->assign('data',$re);
        $this->assign('userinfo',$userinfo);
        $this->assign('frame',$frame);
        $this->assign('admininfo',$this->_userinfo);
        return $this->fetch();
    }
    public function user_delete(){
        $re = Db::name('userlist')->delete(input('id'));
        $re ? $this->success('删除成功') : $this->error('删除有误');
    }
    public function user_edit(){
        if($this->request->isPost()){
            $id = input('id');
            $userinfo = Db::name('userlist')->where(['id'=>$id])->find();
            if($userinfo['status'] >= 2){
                $this->error('该客户已签合同，禁止修改客户信息');
            }
            if($userinfo['userid'] != $this->_userinfo['userid']){
                $this->error('禁止修改他人的客户');
            }
            $data = input();
            if($data['areas']){
                $areas = explode('-', $data['areas']);
                $data['address'] = $areas[1].$data['address'];
            }
            if($data['cities']){
                $cities = explode('-', $data['cities']);
                $data['address'] = $cities[1].$data['address'];
            }
            if($data['provinces']){
                $provinces = explode('-', $data['provinces']);
                $data['address'] = $provinces[1].$data['address'];
            }
            // $data['phone']=$data['phone'];
            $data['address'] =  $data['address'];
            $data['provinceid'] = $provinces[0];
            $data['cityid'] = $cities[0];
            $data['areaid'] = $areas[0];
            unset($data['id']);
            unset($data['areas']);
            unset($data['cities']);
            unset($data['provinces']);

            $names = array_column(Db::name('personnel')->where(['fid'=>$this->_userinfo['companyid']])->select(), null,'id');
            if($data['designer_id']){
                foreach($data['designer_id'] as $k=>$v){
                    $designer_name[] = $names[$v]['name'];
                }
                $data['designer_id'] =  implode(',',$data['designer_id']);
                $data['designer_name'] =  implode(',', $designer_name);
            }else{
                $data['designer_id'] =  0;
                $data['designer_name'] =  '';
            }

            if($data['manager_id']){
                $data['manager_id'] = $data['manager_id'];
                $data['manager_name'] = $names[$data['manager_id']]['name'];
            }else{
                $data['manager_id'] = 0;
                $data['manager_name'] = '';
            }
            $data['quoter_name'] =  $names[$data['quoter_id']]['name'];
            $data['assistant_id'] =  implode(',',$data['assistant_id']); //***
            $data['sale_id'] =  implode(',',$data['sale_id']);  //*****
            $re = Db::name('userlist')->where('id',input('id'))->update($data);
            $re !== false ? $this->success('保存成功','admin/offerlist/userlist') : $this->error('保存失败');
        }else{
            $data = Db::name('userlist')->where('id',input('id'))->find();
            $provinces = array_column(Db::name('provinces')->order('id','asc')->select(),null, 'provinceid');
            $cities = array_column(Db::name('cities')->where(['provinceid'=>$data['provinceid']])->order('id','asc')->select(),null, 'cityid');
            $areas = array_column(Db::name('areas')->where(['cityid'=>$data['cityid']])->order('id','asc')->select(),null, 'areaid');
            $personnel = array_column(Db::name('personnel')->where(['fid'=>$data['frameid'],'status'=>1])->select(),null, 'id');
            foreach($personnel as $k=>$v){
                $personnel_datas[$v['job']][] = $v;
            }
            $this->assign([
                'provinces'=>$provinces,
                'cities'=>$cities,
                'areas'=>$areas,
                'personnel'=>$personnel,
                'personnel_datas'=>$personnel_datas,
                'address'=>$provinces[$data['provinceid']]['province'].$cities[$data['cityid']]['city'].$areas[$data['areaid']]['area'],
            ]);
            $this->assign('data',$data);
            return $this->fetch();
        }
    }
    /**
     * @function  index  客户档案首页
     * @author  Han
     * @version  1.0
     * @param $id 客户id   $cid 项目编号id
     */
    public function index()
    {
        // $this->newcheckrule();//权限检测
        error_reporting(E_ALL ^ E_WARNING);
        $admininfo = $this->_userinfo;
        if($admininfo['roleid'] != 1 && $admininfo['roleid'] != 10 && $admininfo['roleid'] != 22){
            $da['o.userid'] = $admininfo['userid'];
        }
        if($admininfo['roleid'] == 10 || $admininfo['roleid'] == 22){
            $da['o.frameid'] = $admininfo['companyid'];
        }
        // $da['o.number'] = 1;
        if(!empty(input('customer_id'))){
            $da['o.customerid'] = input('customer_id');
        }else{
            $this->error('参数错误！');
        }
        $da['o.is_del'] = 0;
        //所有客户信息
        $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address,u.manager_name as manager_name,u.area as area,u.room_type as room_type')->join('userlist u','o.customerid = u.id')->where($da)->select();
        //统计报价开始 
        foreach ($res as $key => $value) {
            //
            $res[$key]['info'] = Model('offerlist')->get_order_info($value['id'],2);
            //判断是否有增减项
            $res[$key]['append_num'] = Db::name('order_project')->where('o_id',$value['id'])->where('type',2)->count();
            //监理申请的增加项
            $res[$key]['add_append_num'] = Db::name('append_img')->where('oid',$value['id'])->count();
            //礼品
            $res[$key]['gift'] = Model('gift')->getGiftTotal($value['id']);
            $res[$key]['tmp_count'] = Db::name('order_tmp_cost')->where(['oid'=>$value['id']])->count();

        }
        $userinfo = Db::name('userlist')->where(['id'=>input('customer_id')])->find();

        $this->assign('data',$res);
        $this->assign('userinfo',$userinfo);
        $this->assign('admininfo',$admininfo);
        return $this->fetch();
    }

    //选择客户
    public function baojia_first(){
        $where = [];
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
        $userinfo = $this->_userinfo;
        $da = [];
        if($userinfo['userid'] != 1 && $userinfo['roleid'] != 10){
            $da['userid'] = $userinfo['userid'];
        }
        if($userinfo['roleid'] == 10){
            $da['frameid'] = $userinfo['companyid'];
        }
        $re = Db::name('userlist')->where($where)->where($da)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);

        $this->assign('data',$re);
        return $this->fetch();
    }

    //选择订单 (m没用了)
    public function baojiaguanli()
    {
        error_reporting(E_ALL ^ E_WARNING);
        $userinfo = $this->_userinfo;
        if($userinfo['userid'] != 1){
            $da['o.userid'] = $userinfo['userid'];
        }
        $da['o.number'] = 1;
        //客户姓名搜索
        //if($this->request->isPost()){
        $search = input('search');
        if(!empty($search)){
            // $this->error('请输入搜索内容', url("offerlist/index"));
            // }else{
            $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address,u.manager_name as manager_name')->join('userlist u','o.customerid = u.id')->where($da)->where('u.customer_name','LIKE','%'.$search)->select();
            //$this->assign('data',$res);    
            // return $this->fetch();
        }else{
            //所有客户信息
            $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address,u.manager_name as manager_name')->join('userlist u','o.customerid = u.id')->where($da)->select();
        }
        //统计报价开始 
        foreach ($res as $key => $value) {
            $content = json_decode($value['content'],true);
            foreach($content as $keys => $values){
                $res[$key]['matquant'] += $values['quotaall'];//辅材报价
                $res[$key]['manual_quota'] += $values['craft_showall'];//人工报价
            }
            $res[$key]['direct_cost'] = $res[$key]['matquant']+$res[$key]['manual_quota'];//工程直接费= 辅材报价+人工报价
            $res[$key]['proquant'] = $res[$key]['matquant']+$res[$key]['manual_quota']+$res[$key]['tubemoney']+$res[$key]['taxes']+$res[$key]['discount'];//工程报价

            $tariff = array();$labor_cost = '';$fucai = '';
            foreach ($content as $keys => $values) {
                $dinge[$keys] =  Db::name('offerquota')->field('item_number,labor_cost,content')->where('item_number',$content[$keys]['item_number'])->find();
                $tariff[$keys]['item_number'] = $content[$keys]['item_number'];
                $tariff[$keys]['gcl'] = $content[$keys]['gcl'];
                $tariff[$keys]['labor_cost'] = $dinge[$keys]['labor_cost'] * $content[$keys]['gcl'];//人工报价
                $tariff[$keys]['content'] = json_decode($dinge[$keys]['content'],true);
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
            $res[$key]['gross_profit'] = $labor_cost+$fucai;
            $res[$key]['content'] = $content;
        }
        $this->assign('data',$res);
        $this->assign('userinfo',$userinfo);
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

    //报表历史记录
    public function history(){

        $o_id = input('report_id');
        //订单数据
        $order_info = Db::name('offerlist')->where('id',$o_id)->find();
        $userinfo = Db::name('userlist')->where('id',$order_info['customerid'])->find();
        $type = Db::name('cost_tmp')->where(['f_id'=>$userinfo['frameid']])->value('type');
        $where = [];
        $where['o_id'] = $o_id;
        if(input('type') != 2){
            //增减项+原单
            $where['type'] = 1;
        }
        $order_project = Db::name('order_project')->where($where)->select();
        //==========获取工种 空间类型
        $offer_type_list = Db::name('offer_type')->where(['companyid'=>$userinfo['frameid'],'status'=>1])->select();
        $offer_type = [1=>[],2=>[]];
        foreach($offer_type_list as $k=>$v){
            $offer_type[$v['type']][] = $v;
        }
        //===========获取工种结束
        $datas = [];
        $item_number = [];
        $design = [];
        $is_oa = 0; // 是否含有增加项的订单  是的话 把工程量为0的去除
        if(input('word') == 1){
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    $design[] = $v;
                    unset($order_project[$k]);
                    continue;
                }
                if($v['oa_id'] != 0 && input('type') == 2){
                    $is_oa = 1;
                }
                if(!isset($datas[$v['type_of_work']][$v['space']][$v['item_number']])){
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['project'] = $v['project'];

                }
                $datas[$v['type_of_work']][$v['space']][$v['item_number']]['num'] += $v['num'];
            }
            if($is_oa){
                foreach($datas as $k1=>$v1){
                    foreach($v1 as $k2=>$v2){
                        foreach($v2 as $k3=>$v3){
                            if($v3['num'] == 0){
                                unset($datas[$k1][$k2][$k3]);
                                if(empty($datas[$k1][$k2])){
                                    unset($datas[$k1][$k2]);
                                }
                                if(empty($datas[$k1])){
                                    unset($datas[$k1]);
                                }
                            }
                        }
                    }
                }
            }
        }else{
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    $design[] = $v;
                    unset($order_project[$k]);
                    continue;
                }
                if($v['oa_id'] != 0 && input('type') == 2){
                    $is_oa = 1;
                }
                if(!isset($datas[$v['space']][$v['item_number']])){
                    $datas[$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['space']][$v['item_number']]['project'] = $v['project'];
                    // $item_number[] = $v['item_number'];

                }
                $datas[$v['space']][$v['item_number']]['num'] += $v['num'];
            }
            if($is_oa){
                foreach($datas as $k1=>$v1){
                    foreach($v1 as $k2=>$v2){
                        if($v2['num'] == 0){
                            unset($datas[$k1][$k2]);
                        }
                        if(empty($datas[$k1])){
                            unset($datas[$k1]);
                        }
                    }
                }
            }
        }
        if(input('settlement') == 1 || 1){
            $order_bottom_charge = Db::name('order_bottom_charge')->where(['oid'=>$o_id])->order('id asc')->select();
            $this->assign(['order_bottom_charge'=>$order_bottom_charge]);
        }
        
        if(input('type') == 2){
            $offerlist_info = Model('offerlist')->get_order_info($o_id,2);
        }else{
            $offerlist_info = Model('offerlist')->get_order_info($o_id);
        }
        // dump($offerlist_info);die;

        //订单底部文字
        $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$order_info['frameid']])->find();
        $this->assign([
            'datas'=>$datas,
            'order_info'=>$order_info,
            'userinfo'=>$userinfo,
            // 'offerquota'=>$offerquota,
            'offer_type'=>$offer_type,
            'offerlist_info'=>$offerlist_info,
            'cost_tmp'=>$cost_tmp,
            'type'=>$type,
            'design'=>$design,
            'admininfo'=>$this->_userinfo,
        ]);
        return $this->fetch();
    }


    // 弹窗修改信息
    public function ajaxedits(){
        $datas = input();
        if($datas){
            $data['type_of_work'] = $datas['type_of_work'];
            $data['project'] = $datas['project'];
            $data['company'] = $datas['company'];
            $data['cost_value'] = $datas['cost_value'];
            $data['quota'] = $datas['quota'];
            $data['craft_show'] = $datas['craft_show'];
            $data['material'] = $datas['material'];
            $res = Db::name('offerlist')->where('id',$datas['id'])->update($data);
            if($res) {
                Result(0,'更新成功',$data);
            }else{
                Result(1,'更新失败');
            }
        }else{
            Result(1,'获取数据失败');
        }
        // dump($datas);

    }
    //批量修改字段数据
    public function batchedit()
    {
        $datas = input();
        if($datas){
            $batch = $datas['batchname'];//字段名字
            $data[$batch] = $datas['value'];//字段内容
            $arr = $datas['idarray'];
            //利用 explode 函数分割字符串到数组 
            $arr = explode(',',$arr);
            //把获取到的二维数组遍历进数据库
            foreach ($arr as $key => $value) {
                $res = Db::name('offerlist')->where('id',$value)->update($data);
            }
            Result(0,'字段更新成功',$data);
        }else{
            Result(1,'获取数据失败');
        }
    }

    // 修改工程量接口
    public function gcledits(){
        if ($this->request->isPost()) {
            $data = input();
            // dump($data);exit;
            $find = Db::name('offerlist')->where('id',$data['dataid'])->find();
            if($find){
                $neirou = json_decode($find['content'],true);
                foreach ($neirou as $key => $value) {
                    if($key == $data['j']){
                        $neirou[$key]['gcl'] = $data['gcl'];
                        $neirou[$key]['quotaall'] = $data['quotaall'];
                        $neirou[$key]['craft_showall'] = $data['craft_showall'];
                        // $neirou[$key]['pro_apartment_type'] = $data['pro_apartment_type'];
                        // $neirou[$key]['area'] = $data['j']['area'];
                    }
                }
                $shu['content'] = str_replace("\\/", "/", json_encode($neirou,JSON_UNESCAPED_UNICODE));

                // dump($neirou);dump($data['dataid']);exit;
                if(Db::name('offerlist')->where('id',$data['dataid'])->update($shu)){
                    //计算辅材报价人工报价
                    $getall = Db::name('offerlist')->where('id',$data['dataid'])->find();
                    $newget = json_decode($getall['content'],true);
                    foreach ($newget as $key => $value) {

                    }



                    Result(0,'操作成功！');
                    // $this->success("添加条目成功！", url("offerlist/edit",array('id'=>$data['dataid'])));
                }else{
                    Result(1,'操作失败了！',$data);
                    // $this->error('添加失败了！');
                }

            }else{
                Result(1,'获取数据失败');
            }

        }

    }

    // 修改空间类型接口
    public function kjedits(){
        if ($this->request->isPost()) {
            $data = input();
            $find = Db::name('offerlist')->where('id',$data['dataid'])->find();
            if($find){
                $neirou = json_decode($find['content'],true);
                foreach ($neirou as $key => $value) {
                    if($key == $data['j']){
                        $neirou[$key]['kongjian'] = $data['kongjian'];
                    }
                }
                $shu['content'] = str_replace("\\/", "/", json_encode($neirou,JSON_UNESCAPED_UNICODE));

                // dump($neirou);dump($data['dataid']);exit;
                if(Db::name('offerlist')->where('id',$data['dataid'])->update($shu)){
                    Result(0,'操作成功！');
                    // $this->success("添加条目成功！", url("offerlist/edit",array('id'=>$data['dataid'])));
                }else{
                    Result(1,'操作失败了！');
                    // $this->error('添加失败了！');
                }

            }else{
                Result(1,'获取数据失败');
            }

        }

    }

    // 报价导出数据接口
    public function baojia()
    {
        // $res = Db::name('sertype')->alias('s')->field('s.*,u.img_url as img_url')->join('upload_img u','s.logo = u.id')->order('s.sort desc')->select();
        // $res = Db::name('offerlist')->alias('a')->field('a.*,b.name as bname')->join('offer_type b','a.typeid = b.id')->select();
        $userinfo = $this->_userinfo;
        $da['userid'] = $userinfo['userid'];
        $res = Db::name('offerlist')->where($da)->select();

        $newdata = array();
        //过滤无用字段
        foreach ($res as $k => $v) {
            unset($v['addtime']);
            $newdata[$k] = $v;

        }
        // dump($news);
        if($newdata){
            TreeResult(0,'ok',$newdata,count($newdata));
        }else{
            TreeResult(1,'获取失败');
        }
    }

    // 是否启用该报价
    public function status()
    {
        /*0:未报价 1:已报价 2:预算价 3:合同价 4:结算价*/
        if($this->request->isPost()){
            $offerlist = Db::name('offerlist')->where('id',input('id'))->find();
            if(!$offerlist){
                $this->error('无效订单');
            }
            if($offerlist['status'] == 5){
                $this->error('合同价/结算价禁止修改');
            }
            $data = input();
            if($data){
                $status = input('status');
                $id = input('id');
                if($id && ($status || input('status')==0 )){
                    $res = Db::name('offerlist')->where('id',$id)->update(['status'=>$status]);
                    if($res !== false){
                        $this->success('修改成功');
                    }else{
                        $this->error('修改失败');
                    }
                }
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('参数错误');
        }
    }


    //添加报表 新建客户
    public function add()
    {
        $admininfo = $this->_userinfo;
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $bao['userid'] = $admininfo['userid'];//报价员
            $bao['frameid'] =  ($admininfo['companyid']==1)?'152':$admininfo['companyid'];//存报价员公司
            $bao['customer_name'] =  $data['customer_name'];
            $bao['phone']=$data['phone'];
            $bao['director_designer'] =  $data['director_designer'];
            $bao['wood_designer'] =  $data['wood_designer'];
            $bao['furniture_designer'] =  $data['furniture_designer'];
            $bao['soft_designer'] =  $data['soft_designer'];
            $bao['sd_designer'] =  $data['sd_designer'];
            $bao['aid_designer'] =  $data['aid_designer'];
            $bao['assistant_id'] =  implode(',',$data['assistant_id']); //***
            $bao['sale_id'] =  implode(',',$data['sale_id']);  //*****
            $where = [];
            $where['id'][] = $data['quoter_id'];
            // $where['id'][] = $data['designer_id'];  //****
            if($data['designer_id']){
                foreach($data['designer_id'] as $k=>$v){
                    $where['id'][] = $v;
                }
                $bao['designer_id'] =  implode(',',$data['designer_id']);//***
            }else{
                $bao['designer_id'] =  0;
            }
            if($data['manager_id']){
                $where['id'][] = $data['manager_id'];
            }
            $names = array_column(Db::name('personnel')->where($where)->select(), null,'id');
            $bao['quoter_id'] =  $data['quoter_id'];
            
            
            if($data['manager_id']){
                $bao['manager_id'] = $data['manager_id'];
                $bao['manager_name'] = $names[$data['manager_id']]['name'];
            }else{
                $bao['manager_id'] = 0;
                $bao['manager_name'] = '';
            }
            $bao['quoter_name'] =  $names[$data['quoter_id']]['name'];
            if($data['designer_id']){
                $designer_name = [];
                foreach($data['designer_id'] as $k=>$v){
                    $designer_name[] = $names[$v]['name'];
                }
                $bao['designer_name'] =  implode(',', $designer_name);
            }else{
                $bao['designer_name'] =  '';
            }
            
            $bao['area'] = $data['area'];
            $bao['room_type'] = $data['room_type'];
            // $bao['is_new'] = $data['is_new'];
            $bao['house_type'] = $data['house_type'];
            // if($bao['is_new'] == 9){
            //     if($data['oldtime']){
            //         $bao['oldtime'] = strtotime($data['oldtime']);
            //     }else{
            //         $this->error('旧客户请填写签单时间');
            //     }
            // }

            if($data['areas']){
                $areas = explode('-', $data['areas']);
                $data['address'] = $areas[1].$data['address'];
            }
            if($data['cities']){
                $cities = explode('-', $data['cities']);
                $data['address'] = $cities[1].$data['address'];
            }
            if($data['provinces']){
                $provinces = explode('-', $data['provinces']);
                $data['address'] = $provinces[1].$data['address'];
            }

            $bao['address'] =  $data['address'];
            $bao['provinceid'] = $provinces[0];
            $bao['cityid'] = $cities[0];
            $bao['areaid'] = $areas[0];

            $bao['addtime'] = time();
            // dump($data);exit;
            //开启事务
            Db::startTrans();
            try{
                $userlist = Db::name('userlist')->insertGetId($bao);
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('失败添加');
            }
            $qr=New \app\admin\controller\Qrcodes();
            $qr->view();

            $this->success('添加成功','admin/offerlist/userlist');

        }
        //获取报价师 设计师  商务经理
        // 1 => '设计师', 2 => '报价师', 3 => '商务经理', 4 => '工程监理', 5 => '其他',6=>'仓管',7=>'质检',8=>'工程经理',9=>'财务',10=>'出纳',11=>'人事',12=>'总经理',13=>'总设计师',14=>'业务员',15=>'画图'
        $personnel = Db::name('personnel')->where(['status'=>1,'fid'=>$admininfo['companyid']])->select();
        $datas = [];
        foreach($personnel as $k=>$v){
            $datas[$v['job']][] = $v;
        }
        //获取公司默认地址
        $frame = Db::name('frame')->where(['id'=>$this->_userinfo['companyid']])->find();
        //获取省份
        $provinces = Db::name('provinces')->order('id','asc')->select();
        if($frame['provinceid'] && $frame['cityid']){
            $cities = array_column(Db::name('cities')->where(['provinceid'=>$frame['provinceid']])->order('id','asc')->select(),null, 'cityid');
            $areas = array_column(Db::name('areas')->where(['cityid'=>$frame['cityid']])->order('id','asc')->select(),null, 'areaid');
            $this->assign([
                'cities'=>$cities,
                'areas'=>$areas,
            ]);
        }
       
        $this->assign([
            'datas'=>$datas,
            'provinces'=>$provinces,
            'frame'=>$frame,
            'personnel'=>$personnel,
        ]);
        return $this->fetch();
    }

    //历史添加
    public function adds(){
        $userinfo = $this->_userinfo;
        if ($this->request->isPost()) {
            $param = input();
            $data = array();
            //dump(input());exit;
            $data['userid'] = $userinfo['userid'];
            $data['frameid'] = $userinfo['companyid'];//存公司id到报表
            $data['customerid'] = input('customerid');
            $data['unit'] = input('framename');//单位
            $data['entrytime'] = time();
            $data['number'] = 1;
            if(input('remark')){
                $data['remark'] = input('remark');
            }
            $time = time();
            $content = [];
            $order_project = [];
            foreach (input('gcl') as $key => $value) {
                foreach($value as $k=>$v){
                    foreach($v as $k1=>$v1){
                        $kongjian = Db::name('offer_type')->where('id',$k)->value('name');
                        if(!$kongjian){
                            $this->error('空间类型有误');
                        }
                        $item = Db::name('offerquota')->where('item_number',$k1)->find();//获取定额数据
                        $item['kongjian'] = $kongjian;
                        $item['gcl']= $v1; //数量
                        $item['quotaall'] = $v1 * $item['quota']; //该项目的辅材总价
                        $item['craft_showall'] = $v1 * $item['craft_show']; //该项目的人工总价
                        $content[] = $item;

                        //=========================项目 另存新数据库 后面慢慢完善
                        $project = [];
                        // $project['o_id'] = '';//订单id
                        $project['oa_id'] = 0;
                        $project['item_number'] = $k1;
                        $project['num'] = $v1;
                        $project['type_of_work'] = $item['type_of_work'];
                        $project['project'] = $item['project'];
                        $project['company'] = $item['company'];
                        $project['cost_value'] = $item['cost_value'];
                        $project['quota'] = $item['quota'];
                        $project['craft_show'] = $item['craft_show'];
                        $project['labor_cost'] = $item['labor_cost'];
                        $project['material'] = $item['material'];
                        $project['content'] = $item['content'];
                        $project['type'] = 1;
                        $project['add_time'] = $time;
                        $order_project[] = $project;
                    }
                }
            }
            $data['content'] = json_encode($content); //这里获取了项目详情
            //===============================计算物料总合计 成本
            // $arr['工种类']['项目编号']['辅材名称'] = ['数量','成本','单价','利润']
            $material_all = [];
            $order_material = [];//订单辅料详情 - 以后顶替material_all这种json储存方法
            foreach($content as $k=>$v){
                $need_material = json_decode($v['content'],true);//需要的物料
                foreach($need_material as $one_material){
                    if($one_material[0] && $one_material[1]){
                        if(!isset($material_all[$one_material[0]])){
                            //上面2个foreach筛选offerquota表里面的content的有用数据 ( 里面有20个所需辅材 没有的用空数组代替 上面是提出空数组 )
                            $material_all[$one_material[0]]['num'] = 0;
                            $material_all[$one_material[0]]['price'] = 0;//成本单价
                        }
                        // $materials_info = Db::name('materials')->where(array('frameid'=>$userinfo['companyid'],'name'=>$one_material[0]))->find();
                        $materials_info = Db::name('materials')->where(array('frameid'=>$userinfo['companyid']))->where('name|amcode','=',$one_material[0])->find();
                        $price = $materials_info['price'];
                        $coefficient = $materials_info['coefficient'];
                        if(!$price){
                            $this->error($one_material[0].'成本有误，请及时补充辅材仓库');
                        }
                        $material_all[$one_material[0]]['price'] = $price;//成本单价
                        $material_all[$one_material[0]]['coefficient'] = $coefficient;//系数
                        $material_all[$one_material[0]]['important'] = $materials_info['important'];
                        $material_all[$one_material[0]]['num'] += $one_material[1]*$v['gcl']; //需要的用料单数 * 工程单位

                        //===============订单辅料详情  $arr['工种类']['项目编号']['辅材名称'] = ['数量','成本','单价','利润']
                        if(!isset($order_material[$v['type_of_work']][$v['item_number']][$one_material[0]])){
                            //初始化数据 这个框架会神奇的报错 = =
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['cb'] = $materials_info['price'];//成本
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['price'] = $v['quota'];//辅材单价
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['profit'] = $v['quota']-$materials_info['price'];//利润
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['coefficient'] = $coefficient;//系数
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['important'] = $materials_info['important'];//是否重要
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['num'] = 0;//初始化数据

                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['amcode'] = $materials_info['amcode'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['fine'] = $materials_info['fine'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['brand'] = $materials_info['brand'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['place'] = $materials_info['place'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['img'] = $materials_info['img'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['phr'] = $materials_info['phr'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['remarks'] = $materials_info['remarks'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['category'] = $materials_info['category'];//
                            $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['units'] = $materials_info['units'];//
                        }
                        $order_material[$v['type_of_work']][$v['item_number']][$one_material[0]]['num'] += $one_material[1]*$v['gcl'];


                        //这里上面的数量 有可能是小数点. 后面需要根据需求来四舍五入  具体多少舍多少入看需求
                    }
                }
            }
            //=========订单辅料详情 组装数据存进数据库
            //$order_material['工种类']['项目编号']['辅材名称'] = ['数量','成本','单价','利润']
            $order_material_datas = [];
            foreach($order_material as $k1=>$v1){
                foreach($v1 as $k2=>$v2){
                    foreach($v2 as $k3=>$v3){
                        // $order_material_datas['o_id'] = '';//还没有
                        $data_info['c_id'] = $data['customerid'];
                        $data_info['f_id'] = $data['frameid'];
                        $data_info['type_of_work'] = $k1;;
                        $data_info['item_number'] = $k2;;
                        $data_info['m_name'] = $k3;
                        $data_info['num'] = $v3['num'];
                        $data_info['cb'] = $v3['cb'];
                        $data_info['price'] = $v3['price'];
                        $data_info['profit'] = $v3['profit'];
                        $data_info['coefficient'] = $v3['coefficient'];
                        $data_info['important'] = $v3['important'];

                        $data_info['amcode'] = $v3['amcode'];
                        $data_info['fine'] = $v3['fine'];
                        $data_info['brand'] = $v3['brand'];
                        $data_info['place'] = $v3['place'];
                        $data_info['img'] = $v3['img'];
                        $data_info['phr'] = $v3['phr'];
                        $data_info['remarks'] = $v3['remarks'];
                        $data_info['category'] = $v3['category'];
                        $data_info['units'] = $v3['units'];

                        $order_material_datas[] = $data_info;
                    }
                }
            }


            //=========旧版本的计算
            foreach($material_all as $k=>$v){
                //获取数量的小数
                $num = explode('.',$v['num']);
                if(!isset($num[1])){
                    $num[1] = 0;
                }
                if($num[1]*10 > $v['coefficient']){
                    $material_all[$k]['omit_num'] = ceil($v['num']);
                }else{
                    //不足1时向上取证
                    if($v['num'] < 1 && $v['important']){
                        $material_all[$k]['omit_num'] = ceil($v['num']);
                    }else{
                        $material_all[$k]['omit_num'] = floor($v['num']);
                    }
                }
                unset($material_all[$k]['coefficient']);
            }
            $data['material'] = json_encode($material_all); //物料成本 json格式 里面 辅材名字=>[num=>数量 ,price=>单价,omit_num=>系数后数量]

            //==============================计算人工成本

            $need_project = json_decode($data['content'],true);//需要的项目
            $artificial_all = [];//人工成本 , 报价(成本+利润)
            foreach($need_project as $k=>$v){
                $Offerquota_info = Db::name('Offerquota')->where(array('frameid'=>$userinfo['companyid'],'item_number'=>$v['item_number']))->find();
                if(!$Offerquota_info){
                    $this->error($one_material[0].'人工有误，请及时补充人工工费');
                }
                if(!isset($artificial_all[$v['item_number']])){
                    $artificial_all[$v['item_number']]['type_of_work'] = $Offerquota_info['type_of_work']; //工种
                    $artificial_all[$v['item_number']]['price'] = $Offerquota_info['craft_show']; //单价 
                    $artificial_all[$v['item_number']]['cb'] = $Offerquota_info['labor_cost']; //单个成本
                    $artificial_all[$v['item_number']]['profit'] = $Offerquota_info['craft_show'] - $Offerquota_info['labor_cost']; //单个利润 
                    $artificial_all[$v['item_number']]['num'] = 0;//数量
                }
                $artificial_all[$v['item_number']]['num'] += $v['gcl']; //数量

            }
            $data['artificial'] = json_encode($artificial_all); //人工成本 json格式 里面 num=>数量 price=>单价 cb=>成本 profit=>利润
            //其他各种费用比率
            $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$data['frameid']])->find();
            if(!$cost_tmp){
                //没有设置  这个是默认值
                $cost_tmp_data = [
                    'tubemoney'=>1,
                    'carry'=>0,
                    'clean'=>0,
                    'accident'=>0,
                    'remote'=>0,
                    'old_house'=>0,
                    'taxes'=>0,
                    'supervisor_commission'=>0,
                    'design_commission'=>0,
                    'repeat_commission'=>3,
                    'business_commission'=>0
                ];
            }else{
                $cost_tmp_data = [
                    'tubemoney'=>$cost_tmp['tubemoney'],
                    'carry'=>$cost_tmp['carry'],
                    'clean'=>$cost_tmp['clean'],
                    'accident'=>$cost_tmp['accident'],
                    'remote'=>$cost_tmp['remote'],
                    'old_house'=>$cost_tmp['old_house'],
                    'taxes'=>$cost_tmp['taxes'],
                    'supervisor_commission'=>$cost_tmp['supervisor'],
                    'design_commission'=>$cost_tmp['design'],
                    'repeat_commission'=>$cost_tmp['repeat'],
                    'business_commission'=>$cost_tmp['business']
                ];
            }
            $data = array_merge($data,$cost_tmp_data);
            Db::startTrans();
            try{
                $re = Db::name('offerlist')->insertGetId($data);
                foreach($order_material_datas as $k=>$v){
                    $order_material_datas[$k]['o_id'] = $re;
                }
                if($order_material_datas){
                    $order_material_res = Db::name('order_material')->insertAll($order_material_datas);
                }else{
                    $order_material_res = 1;
                }

                foreach($order_project as $k=>$v){
                    $order_project[$k]['o_id'] = $re;
                }

                $order_project_res = Db::name('order_project')->insertAll($order_project);

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('失败');
            }
            if($re!==false && $order_material_res && $order_project_res){
                $this->success('成功',url('admin/offerlist/history',array('customerid'=>input('customerid'),'report_id'=>$re)));
            }else{
                $this->error('失败');
            }
        }
    }
    //新版本添加条目
    public function addlist()
    {
        $userinfo = $this->_userinfo;
        if ($this->request->isPost()) {
            //返回选择的定额数据
            $res = [];
            $search = input('type_of_work');
            if($search){
                if($userinfo['userid']==1){
                    $res = Db::name('Offerquota')->where('type_of_work',$search)->select();
                }else{
                    $res = Db::name('Offerquota')->where('frameid',$userinfo['companyid'])->where('type_of_work',$search)->select();
                }
            }

            //搜索返回
            $project = input('project');//项目名称搜索
            $item_number = input('item_number');//编号搜索
            $searchtype = input('searchtype');//工种
            $where = [];
            if($item_number){
                $where['item_number'] = $item_number;
            }
            if($where || $project){
                $where['type_of_work'] = $searchtype;
                if($userinfo['userid']==1){
                    $res = Db::name('Offerquota')->where($where)->select();
                    if($project){
                        $res = Db::name('Offerquota')->where($where)->where('project','LIKE',"%".$project."%")->select();
                    }
                }else{
                    $res = Db::name('Offerquota')->where('frameid',$userinfo['companyid'])->where($where)->select();
                    if($project){
                        $res = Db::name('Offerquota')->where('frameid',$userinfo['companyid'])->where($where)->where('project','LIKE',"%".$project."%")->select();
                    }
                }
            }
            //数据处理
            $str = '';
            if($res){
                foreach ($res as $key => $value) {
                    $str .= '<tr>
                          <td><input type="checkbox" name="check" data-id="'.$value['id'].'"></td>
                          <td>'.$value['id'].'</td>    
                          <td>'.$value['item_number'].'</td>     
                          <td>'.getcid($value['frameid']).'</td>    
                          <td>'.$value['type_of_work'].'</td>                                     
                          <td>'.$value['project'].'</td>                                     
                          <td>'.$value['company'].'</td>                                     
                          <td>'.$value['quota'].'</td>                                     
                          <td>'.$value['craft_show'].'</td> 
                          <td>'.$value['cost_value'].'</td>                              
                        </tr> ';

                }
            }
            return json([ 'html'=>$str ]);
        }
    }
    /**
     * 获取数据
     */
    public function ceshi(){
        if ($this->request->isPost()){
            $data = input();
            $nid = $data['nid'];
            // dump($data);
            $valzhi = json_decode(htmlspecialchars_decode($data['value']),true);
            foreach ($valzhi as $key => $value) {
                $valzhi[$key]['kongjian'] = '';
                $valzhi[$key]['gcl'] = '';
                $valzhi[$key]['quotaall'] = '';
                $valzhi[$key]['craft_showall'] = '';
                unset( $valzhi[$key]['frameid']);
            }
            $find = Db::name('offerlist')->where('id',$nid)->find();
            if($find['content']){
                $arr1 = json_decode($find['content'],true);
                $arr2 = $valzhi;
                $newarr = array_merge($arr1,$arr2);
            }else{
                $newarr = $valzhi;
            }
            $shu['content'] = str_replace("\\/", "/", json_encode($newarr,JSON_UNESCAPED_UNICODE));

            if(Db::name('offerlist')->where('id',$nid)->update($shu)){
                $this->success("添加条目成功！", url("offerlist/edit",array('id'=>$nid)));
            }else{
                Result(1,'添加失败了！');
            }

        }

    }

    /**
     * 选择报价条目
     */
    public function ajaxquery(){
        $frameid = 6;//需计算当前报价员的公司id
        if ($this->request->isPost()){
            $sres = Db::name('offerquota')->where(array('frameid'=>$frameid,'item_number'=>input('value')))->find();
            if($sres){
                Result(0,'校检成功',$sres);
            }else{
                Result(1,'没有查询到该编号');
            }
        }
    }

    public function zengjian_first(){
        $where = new Where;
        if(input('customer_name')){
            $where['customer_name'] = ['LIKE','%'.input('customer_name').'%'];
        }
        if(input('quoter_name')){
            $where['quoter_name'] = ['LIKE','%'.input('quoter_name').'%'];
        }
        if(input('designer_name')){
            $where['designer_name'] = ['LIKE','%'.input('designer_name').'%'];
        }
        if(input('address')){
            $where['address'] = ['LIKE','%'.input('address').'%'];
        }
        if(input('manager_name')){
            $where['manager_name'] = ['LIKE','%'.input('manager_name').'%'];
        }
        $userinfo = $this->_userinfo;
        $da = [];
        if($userinfo['userid'] != 1 && $userinfo['roleid'] != 10){
            $da['userid'] = $userinfo['userid'];
        }
        if($userinfo['roleid'] == 10){
            $da['frameid'] = $userinfo['companyid'];
        }
        if(!empty($where)){
            $re = Db::name('userlist')->where($where)->where($da)->paginate($this->show_page);
        }else{
            $re = Db::name('userlist')->where($da)->paginate($this->show_page);
        }
        $this->assign('data',$re);
        return $this->fetch();
    }

    //增减项
    public function zengjian(){

        $userinfo = $this->_userinfo;

        $request = request();
        $id = $request->param('id');
        if(empty($id)){
            return $this->fetch();
        }
        $this->assign('id',$id);
        $rs = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address')->join('userlist u','o.customerid = u.id')->where(["o.id" => trim($id)])->find();
        // dump($rs);
        if(!empty($rs['content'])){
            $conditions_no = 0;$room_no = 0;$conditions = [];$room = [];
            foreach(json_decode($rs['content'],true) as $key=>$value){
                if(!in_array($value['type_of_work'],$conditions) ){
                    $id = Db::name('offer_type')->where('name',$value['type_of_work'])->value('id');
                    $conditions[$id] = $value['type_of_work'];
                }
            }
            foreach(json_decode($rs['content'],true) as $key=>$value){
                if(!in_array($value['kongjian'],$room) ){
                    $id = Db::name('offer_type')->where('name',$value['kongjian'])->value('id');
                    $room[$id] = $value['kongjian'];
                }
            }
            if( !empty($conditions) ){
                $new_data = [];
                foreach($conditions as $key=>$value){
                    foreach($room as $k=>$v){
                        foreach(json_decode($rs['content'],true) as $ke=>$val){
                            if($val['type_of_work'] ==$value){
                                $new_data[$key]['conditionsname'] = $value;
                                if($val['kongjian'] == $v){
                                    $new_data[$key]['son'][$k]['roomname'] = $v;
                                    $new_data[$key]['son'][$k]['item'][] = $val;
                                }
                            }
                        }
                    }
                }
            }
            $rs['details'] = $new_data;
        }

        $this->assign("data", $rs);
        $mould = Db::name('mould')->where('id','=',input('mouldid'))->find();
        if($mould['content']){
            // $conditions_no = 0;$room_no = 0;
            foreach(json_decode($mould['content'],true) as $key=>$value){
                $conditionsname = Db::name('offer_type')->where('id','=',$key)->value('name');//工种名称
                $mould['details'][$key]['conditionsname'] = $conditionsname;
                // $mould['details'][$key]['conditions_no'] = $this->upper[$conditions_no];
                foreach($value as $ke=>$va){
                    $roomname = Db::name('offer_type')->where('id','=',$ke)->value('name');//空间类型名称
                    $mould['details'][$key]['son'][$ke]['roomname'] = $roomname;
                    // $mould['details'][$key]['son'][$ke]['room_no'] = $this->lower[$room_no];
                    foreach($va as $k=>$v){
                        $item = Db::name('offerquota')->find($v);//定额条目
                        $mould['details'][$key]['son'][$ke]['item'][$k] = $item;
                    }
                    // $room_no++;
                }
                // $conditions_no++;
            }
        }
        $res = Db::name('offer_type')->select();//工种和空间类型
        $tree = [];//树状数据
        foreach($res as $key =>$value){
            if($value['pid'] === 0){
                $tree[$key] = $value;
                unset($res[$key]);
                foreach($res as $k=>$v){
                    if($v['pid'] == $value['id']){
                        $tree[$key]['son'][] = $v;
                    }
                }
            }
        }
        $this->assign([
            'tree'=>$tree,
            'mould'=>$mould
        ]);
        return $this->fetch();
    }



    //业务报价(旧)
    public function edit()
    {
        $userinfo = $this->_userinfo;

        $request = request();
        $id = $request->param('customer_id');
        if(empty($id) && empty(input('mouldid'))){
            return $this->fetch();
        }
        //dump($id);
        $this->assign('id',$id);
        $rs = Db::name('userlist')->where(['id'=>$id])->find();
        // dump($rs);
        $this->assign("data", $rs);


        $res = Db::name('offer_type')->select();//工种和空间类型
        $offer_type = array_column($res, null,'id');
        $tree = [];//树状数据
        foreach($res as $key =>$value){
            if($value['pid'] === 0){
                $tree[$value['id']] = $value;
                unset($res[$value['id']]);
                foreach($res as $k=>$v){
                    if($v['pid'] == $value['id']){
                        $tree[$value['id']]['son'][] = $v;
                    }
                }
            }
            //另外有用的
            if($value['pid'] === 0){
                $new_tree[$value['name']] = [];
                foreach($res as $k=>$v){
                    if($v['pid'] == $value['id']){
                        $new_tree[$value['name']][$v['name']][0] = $value['id'];
                        $new_tree[$value['name']][$v['name']][1] = $v['id'];
                    }
                }
            }
        }

        //使用模板
        if(input('tmp_id')){
            $tmp = Db::name('tmp')->where('tmp_id','=',input('tmp_id'))->select();
            $tmp_datas = [];
            $item_number = [];
            $frameid = '';
            foreach($tmp as $k=>$v){
                $tmp_datas[$new_tree[$v['work_type']][$v['space']][0]][$new_tree[$v['work_type']][$v['space']][1]][] = ['item_number'=>$v['item_number'],'num'=>$v['num']];
                $item_number[] = $v['item_number'];
                $frameid = $v['f_id'];
            }
            $offerquota = array_column(Db::name('offerquota')->where('item_number','in',$item_number)->where('frameid',$frameid)->select(), null,'item_number');
            $this->assign([
                'offerquota'=>$offerquota,
                'offer_type'=>$offer_type,
                'tmp_datas'=>$tmp_datas,
            ]);
        }
        $this->assign([
            'tree'=>$tree,
        ]);
        return $this->fetch();
    }

    //删除
    public function delete()
    {
        if ($this->request->isPost()){
            $data = $this->request->post();
            if(!is_numeric($data['id'])){
                Result(1,'参数错误！');
            }
            $find = Db::name('offerlist')->where('id',$data['id'])->find();
            if(empty($find)){
                Result(1,'无此数据！');
            }
            $json = json_decode($find['content'],true);
            foreach ($json as $key => $value) {
                $company[$key] =  Db::name('offerquota')->field('id')->where('item_number',$json[$key]['item_number'])->find();
                $json[$key]['cid'] = $company[$key]['id'];
                if($json[$key]['cid'] == $data['cid'] && $json[$key]['gcl']==$data['gcl']){
                    unset($json[$key]);
                }
            }
            $shu['content'] = str_replace("\\/", "/", json_encode($json,JSON_UNESCAPED_UNICODE));
            $update = Db::name('offerlist')->where('id',$data['id'])->update($shu);
            !$update ? Result(1,'删除失败了！') : Result(0,'删除成功！');
        }
    }

    //列表单字段修改
    public function singlefield_edit()
    {
        if(!$this->request->isPost()){
            Result(1,'请求错误！');
        }
        $offerlist = Db::name('offerlist')->where('id',input('id'))->find();
        if(!$offerlist){
            Result(1,'无效订单');
        }
        if($offerlist['status'] >= 5){
            Result(1,'结算状态禁止修改');
        }
        $receive = $this->request->param();
        $data[$receive['field']] = $receive['value'];
        if(Db::name('offerlist')->where('id', $receive['id'])->update($data)){
            Result(0,'单字段编辑成功');
        }else{
            Result(1,'编辑失败了！');
        }

    }

// 导入excel表
    public function ImportExcel(Request $request){

        if ($_FILES['excel']['error'] == 4) {
            $this->error('没有文件被上传');die;
        }

        $userInfo = $this->_userinfo;
        if(!$userInfo) {
            $this->error('无法获取当前操作人员');die;
        }
        require'../extend/PHPExcel/PHPExcel.php';
        $file = $request->file();
        // dump($file);
        if($file){
            foreach ($file as $files) {
                // dump($files);
                $info = $files->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
            }
            if (!$info) {
                // Result(1,'上传文件格式不正确'); 
                $this->error('上传文件格式不正确');
            }else{
                // Result(0,'上传成功'); 
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                //记录上传文件日志(先不做了)
                // $log['filepath'] = $filePath;
                // $log['addtime'] = time();
                // $rval = Db::name('excelfile')->insert($log);


                // 判断哪种类型
                if($suffix=="xlsx"){
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }

            }
            //处理表格数据
            //载入excel文件
            $excel = $reader->load("$filePath",$encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            $data = []; //数组形式获取表格数据 
            // dump($col_num);
            if ($col_num != 'I') {
                $this->error('文件数据字段不匹配，请重新选择');die;
            }
            for ($i = 2; $i <= $row_num; $i ++) {
                $data[$i]['item_number']  = $sheet->getCell("A".$i)->getValue();
                $data[$i]['typeid']  = $sheet->getCell("B".$i)->getValue();
                $data[$i]['type_of_work']  = $sheet->getCell("C".$i)->getValue();
                $data[$i]['project']  = $sheet->getCell("D".$i)->getValue();
                $data[$i]['company']  = $sheet->getCell("E".$i)->getValue();
                $data[$i]['cost_value']  = $sheet->getCell("F".$i)->getValue();
                $data[$i]['quota']  = $sheet->getCell("G".$i)->getValue();
                $data[$i]['craft_show']  = $sheet->getCell("H".$i)->getValue();
                $data[$i]['material']  = $sheet->getCell("I".$i)->getValue();
                $data[$i]['userid']  = $userInfo['userid'];
                $data[$i]['addtime']  = time();
            }

            //将数据保存到数据库
            if ($data) {
                //把获取到的二维数组遍历进数据库
                foreach ($data as $key => $value) {
                    $res = Db::name('offerlist')->insert($value);
                }
                $this->success('导入成功');
            }else{
                $this->error('获取导入文件数据失败');
            }

        }else{
            $this->error('请选择上传文件');
        }



    }
    public function chushihua(){
        //初始化引用代码
        $result = Db::execute('drop database baojia');
        echo 'ok';

    }

    public function excel_export(){
        $o_id = input('report_id');
        //订单数据
        $order_info = Db::name('offerlist')->where('id',$o_id)->find();
        $userinfo = Db::name('userlist')->where('id',$order_info['customerid'])->find();
        $filename = $userinfo['address'];
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=".$filename.".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        $str = input('html');
        $where = [];
        $where['o_id'] = $o_id;
        if(input('type') != 2){
            //增减项+原单
            $where['type'] = 1;
        }
        $order_project = Db::name('order_project')->where($where)->select();
        //==========获取工种 空间类型
        $offer_type_list = Db::name('offer_type')->where(['companyid'=>$userinfo['frameid'],'status'=>1])->select();
        $offer_type = [1=>[],2=>[]];
        foreach($offer_type_list as $k=>$v){
            $offer_type[$v['type']][] = $v;
        }
        //===========获取工种结束
        $datas = [];
        $is_oa = 0; // 是否含有增加项的订单  是的话 把工程量为0的去除
        if(input('word') == 1){
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    $design[] = $v;
                    unset($order_project[$k]);
                    continue;
                }
                if($v['oa_id'] != 0){
                    $is_oa = 1;
                }
                if(!isset($datas[$v['type_of_work']][$v['space']][$v['item_number']])){
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['type_of_work']][$v['space']][$v['item_number']]['project'] = $v['project'];

                }
                $datas[$v['type_of_work']][$v['space']][$v['item_number']]['num'] += $v['num'];
            }
            if($is_oa){
                foreach($datas as $k1=>$v1){
                    foreach($v1 as $k2=>$v2){
                        foreach($v2 as $k3=>$v3){
                            if($v3['num'] == 0){
                                unset($datas[$k1][$k2][$k3]);
                                if(empty($datas[$k1][$k2])){
                                    unset($datas[$k1][$k2]);
                                }
                                if(empty($datas[$k1])){
                                    unset($datas[$k1]);
                                }
                            }
                        }
                    }
                }
            }
        }else{
            foreach($order_project as $k=>$v){
                if(strpos($v['project'],'设计') !== false){
                    $design[] = $v;
                    unset($order_project[$k]);
                    continue;
                }
                if($v['oa_id'] != 0){
                    $is_oa = 1;
                }
                if(!isset($datas[$v['space']][$v['item_number']])){
                    $datas[$v['space']][$v['item_number']]['info'] = $v;
                    $datas[$v['space']][$v['item_number']]['num'] = 0;
                    $datas[$v['space']][$v['item_number']]['project'] = $v['project'];
                    // $item_number[] = $v['item_number'];
                }
                $datas[$v['space']][$v['item_number']]['num'] += $v['num'];
            }
            if($is_oa){
                foreach($datas as $k1=>$v1){
                    foreach($v1 as $k2=>$v2){
                        if($v2['num'] == 0){
                            unset($datas[$k1][$k2]);
                        }
                        if(empty($datas[$k1])){
                            unset($datas[$k1]);
                        }
                    }
                }
            }
        }
        // $item_number = array_unique($item_number);
        $condition = [];

        $offerquota = array_column(Db::name('order_project')->where(['o_id'=>$o_id])->select(), null,'item_number');

        if(input('type') != 2){
            $offerlist_info = Model('offerlist')->get_order_info($o_id);
        }else{
            $offerlist_info = Model('offerlist')->get_order_info($o_id,2);
        }
        

        //订单底部文字
        $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$order_info['frameid']])->find();
        $ty = Db::name('cost_tmp')->where(['f_id'=>$order_info['frameid']])->value('type');
        $room_type = $userinfo['room_type']?$userinfo['room_type']:'住宅';
        if($ty==1){
           $str = '<style>table,td,th{border:1px solid #000000;text-align:center;padding:2px;}</style>
            <table class="layui-table">
                    <thead>
                        <tr>
                            <th rowspan="2" colspan="3" style="text-align: center;font-size:25px">华浔品味装饰</th>
                            <th class="text-center text-large" colspan="5"><h3>'.$room_type.'装饰工程造价预算书</h3></th>
                            <th rowspan="2" colspan="1"></th>
                        </tr>
                        <tr>
                            <th class="text-center" colspan="5">全国统一24小时客服热线<br />
                            400-628-1968</th>
                        </tr>
                        <tr>
                            <th style="text-align:center;" colspan="9">公司：'.$order_info['unit'].'</th>        
                        </tr>
                        <tr>
                            <th colspan="3">工程名称：'.$userinfo['address'].'</th>       
                            <th colspan="3">客户姓名：'.$userinfo['customer_name'].'</th>       
                            <th colspan="2">设计师姓名：'.$userinfo['designer_name'].'</th>       
                            <th colspan="1">报价师姓名：'.$userinfo['quoter_name'].'</th>       
                        </tr>
                        <tr>      
                            <th rowspan="2" colspan="1" style="width:40px;">序号</th>
                            <th class="text-center" rowspan="2" style="width:120px;">工程项目名称</th>         
                            <th class="text-center" rowspan="2" style="width:35px;">数量</th>       
                            <th class="text-center" rowspan="2" style="width:35px;">单位</th>
                            <th class="text-center" colspan="2" style="width:50px;">辅材费</th> 
                            <th class="text-center" colspan="2" style="width:50px;">人工费</th>    
                            <th class="text-center" rowspan="2" style="width:250px;">施工工艺及材料说明</th> 
                        </tr>
                        <tr>   
                            <th class="text-center" style="width:50px;">单价</th>       
                            <th class="text-center" style="width:50px;">合计</th>       
                            <th class="text-center" style="width:50px;">单价</th>       
                            <th class="text-center" style="width:50px;">合计</th> 
                          </tr>
                    </thead>
                    <tbody> ';
        }else{
           // $data = $rs;
           $str = '<style>table,td,th{border:1px solid #000000;text-align:center;padding:2px;}</style>
            <table class="layui-table">
                    <thead>
                        <tr>
                            <th rowspan="2" colspan="3" style="text-align: center;font-size:25px">华浔品味装饰</th>
                            <th class="text-center text-large" colspan="5"><h3>'.$room_type.'装饰工程造价预算书</h3></th>
                            <th rowspan="2" colspan="1"></th>
                        </tr>
                        <tr>
                            <th class="text-center" colspan="5">全国统一24小时客服热线<br />
                            400-628-1968</th>
                        </tr>
                        <tr>
                            <th style="text-align:center;" colspan="9">公司：'.$order_info['unit'].'</th>        
                        </tr>
                        <tr>
                            <th colspan="3">工程名称：'.$userinfo['address'].'</th>       
                            <th colspan="3">客户姓名：'.$userinfo['customer_name'].'</th>       
                            <th colspan="2">设计师姓名：'.$userinfo['designer_name'].'</th>       
                            <th colspan="1">报价师姓名：'.$userinfo['quoter_name'].'</th>       
                        </tr>
                        <tr>      
                            <th rowspan="2" colspan="1" style="width:40px;">序号</th>
                            <th class="text-center" rowspan="2" style="width:120px;">工程项目名称</th>         
                            <th class="text-center" rowspan="2" style="width:35px;">数量</th>       
                            <th class="text-center" rowspan="2" style="width:35px;">单位</th>
                            <th class="text-center" colspan="4" style="width:100px;">综合价</th> 
                            <th class="text-center" rowspan="2" style="width:250px;">施工工艺及材料说明</th> 
                        </tr>
                        <tr>   
                            <th class="text-center" colspan="2">单价</th>       
                            <th class="text-center" colspan="2"">合计</th> 
                          </tr>
                    </thead>
                    <tbody> ';
       }


        $num1 = 65;
        $num2=65; 
        $total_quota = 0;
        $total_craft_show = 0;
        if(input('word') == 1){
            foreach($datas as $k1=>$v1){
                $str .= '<tr data-cate="tr'.$k1.'">
                                <td>'.chr($num1).'</td>
                                <td colspan="2">'.$k1.'</td>
                                <td colspan="6"></td>
                            </tr>';
                $num1++;$num2=97;
                $space_quota_total = 0;
                $space_craft_show_total = 0;
                foreach($v1 as $k2=>$v2){
                    $str .=  '<tr id="tr'.$k2.'">
                                <td>'.chr($num2).'</td>
                                <td class="text-center" colspan="8">'.$k2.'</td>
                            </tr>';
                    $num3=1; $num2++;
                    foreach($v2 as $k3=>$v3){
                        if($ty==1){
                            $str .=  '<tr class="tr'.$k1.$k2.'">
                                        <td>'.$num3.'</td>
                                        <td style="text-align:left">'.$offerquota[$k3]['project'].'</td>
                                        <td>'.$v3['num'].'</td>
                                        <td>'.$offerquota[$k3]['company'].'</td>
                                        <td>'.$offerquota[$k3]['quota'].'</td>
                                        <td>'. $v3['num']*$offerquota[$k3]['quota'] .'</td>
                                        <td>'.$offerquota[$k3]['craft_show'].'</td>
                                        <td>'. $v3['num']*$offerquota[$k3]['craft_show'] .'</td>
                                        <td>'.$offerquota[$k3]['material'].'</td>
                                    </tr>';
                        }else{
                            $str .=  '<tr class="tr'.$k1.$k2.'">
                                        <td>'.$num3.'</td>
                                        <td style="text-align:left">'.$offerquota[$k3]['project'].'</td>
                                        <td>'.$v3['num'].'</td>
                                        <td>'.$offerquota[$k3]['company'].'</td>
                                        <td colspan="2">'. ($offerquota[$k3]['quota'] + $offerquota[$k3]['craft_show']) .'</td>
                                        <td colspan="2">'. ($v3['num']*$offerquota[$k3]['craft_show'] + $v3['num']*$offerquota[$k3]['quota']) .'</td>
                                        <td>'.$offerquota[$k3]['material'].'</td>
                                    </tr>';
                        }
                        $space_quota_total += $v3['num']*$offerquota[$k3]['quota'];
                        $space_craft_show_total += $v3['num']*$offerquota[$k3]['craft_show'];
                        $total_quota += $v3['num']?$v3['num']*$offerquota[$k3]['quota']:0;
                        $total_craft_show += $v3['num']?$v3['num']*$offerquota[$k3]['craft_show']:0;
                        $num3++;
                    }
                }
                $str .= '<tr class="tr'.$k1.'total">
                            <td class="text-center" colspan="2">小计</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>'.$space_quota_total.'</td>
                            <td></td>
                            <td>'.$space_craft_show_total.'</td>
                            <td></td>
                        </tr>';
            }
        }else{
            foreach($datas as $k1=>$v1){
                $str .= '<tr>
                            <td>'.chr($num2).'</td>
                            <td colspan="8">'.$k1.'</td>
                        </tr>';
                $num1 = 1;
                $num2++; 
                $space_total1=0;//辅材
                $space_total2=0; //人工
                $space_quota_total = 0;
                $space_craft_show_total = 0;
                foreach($v1 as $k2=>$v2){
                    if($ty==1){
                        $str .=  '<tr class="">
                                    <td>'.$num1.'</td>
                                    <td style="text-align:left">'.$offerquota[$k2]['project'].'</td>
                                    <td>'.$v2['num'].'</td>
                                    <td>'.$offerquota[$k2]['company'].'</td>
                                    <td>'.$offerquota[$k2]['quota'].'</td>
                                    <td>'. $v2['num']*$offerquota[$k2]['quota'] .'</td>
                                    <td>'.$offerquota[$k2]['craft_show'].'</td>
                                    <td>'. $v2['num']*$offerquota[$k2]['craft_show'] .'</td>
                                    <td>'.$offerquota[$k2]['material'].'</td>
                                </tr>';
                    }else{
                        $str .=  '<tr class="tr'.$k1.$k2.'">
                                    <td>'.$num3.'</td>
                                    <td style="text-align:left">'.$offerquota[$k2]['project'].'</td>
                                    <td>'.$v2['num'].'</td>
                                    <td>'.$offerquota[$k2]['company'].'</td>
                                    <td colspan="2">'. ($offerquota[$k2]['quota'] + $offerquota[$k2]['craft_show']) .'</td>
                                    <td colspan="2">'. ($v2['num']*$offerquota[$k2]['craft_show'] + $v2['num']*$offerquota[$k2]['quota']) .'</td>
                                    <td>'.$offerquota[$k2]['material'].'</td>
                                </tr>';
                    }
                    $space_quota_total += $v2['num']*$offerquota[$k2]['quota'];
                    $space_craft_show_total += $v2['num']*$offerquota[$k2]['craft_show'];
                    $total_quota += $v2['num']?$v2['num']*$offerquota[$k2]['quota']:0;
                    $total_craft_show += $v2['num']?$v2['num']*$offerquota[$k2]['craft_show']:0;
                    $num1++;
                }
                $str .= '<tr">
                            <td class="text-center" colspan="2">小计</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>'.$space_quota_total.'</td>
                            <td></td>
                            <td>'.$space_craft_show_total.'</td>
                            <td></td>
                        </tr>';
            }
        }
        
        $str .= '<tr>
                    <td class="text-center" colspan="2">直接费小计</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>'.$total_quota.'</td>
                    <td></td>
                    <td>'.$total_craft_show.'</td>
                    <td></td>
                </tr>';

        //其他费用
        $num=97;
        if($offerlist_info['order_cost']){
            foreach ($offerlist_info['order_cost'] as $kcost=>$vcost){
                if($vcost['name'] == '优惠'){ 
                    if($vcost['price'] == 0){
                        continue;
                    }
                }
                if($offerlist_info['design_val']['design_in'] == 1 && $vcost['name']=='工程报价' && $offerlist_info['design_val']['design_show'] == 1 && $offerlist_info['design_price'] > 0){
                    if($offerlist_info['design_val']['design_type'] == 1){
                        $str .= '<tr>';
                        $str .=      '<td class="text-left" colspan="2">'.$offerlist_info["design_val"]["design_project_info"]["project"].'</td>';
                        $str .=      '<td colspan="6">'.$offerlist_info['design_price'].'</td>';
                        $str .=      '<td class="text-left">'. $offerlist_info['design_val']['design_project_info']['material'] ?: '' .'</td>';
                        $str .=  '</tr>';
                    }else{
                        $str .= '<tr>';
                        $str .= '    <td class="text-left" colspan="2">设计费</td>';
                        $str .= '    <td colspan="6">'.$offerlist_info['design_price'].'</td>';
                        $str .= '    <td class="text-left">'.$offerlist_info['design_val']['design_content'] ?: '' .'</td>';
                        $str .= '</tr>';
                    }
                }
                $str .= '<tr>
                           <td class="text-center">'.chr($num).'</td>
                           <td class="text-center">'.$vcost['name'].'</td>
                           <td></td>
                           <td></td>
                           <td></td>
                           <td>'.$vcost['price'].'</td>
                           <td></td>
                           <td></td>
                           <td>'.$vcost['content'] ?: "".'</td>
                       </tr>';
                $num++;
                if($offerlist_info['design_val']['design_show'] == 1 && $offerlist_info['design_val']['design_in'] == 2 && $vcost['name']=='工程报价' && $offerlist_info['design_price'] > 0){
                    if ($offerlist_info['design_val']['design_type'] == 1){
                        $str .=  '<tr>';
                        $str .=  '    <td class="text-left" colspan="2">'.$offerlist_info['design_val']['design_project_info']['project'].'</td>';
                        $str .=  '    <td colspan="6">'.$offerlist_info['design_price'].'</td>';
                        $str .=  '    <td class="text-left">'.$offerlist_info['design_val']['design_project_info']['material'] ?: ''.'</td>';
                        $str .=  '</tr>';
                    }else{
                        $str .= '<tr>';
                        $str .= '    <td class="text-left" colspan="2">设计费</td>';
                        $str .= '    <td colspan="6">'.$offerlist_info['design_price'].'</td>';
                        $str .= '    <td class="text-left">'.$offerlist_info['design_val']['design_content'] ?: '' .'</td>';
                        $str .= '</tr>';
                    }
                }
            }
        }else{
            $str .= '<tr>';
            $str .= '    <td class="text-left" colspan="2">直接费</td>';
            $str .= '    <td colspan="6">'.$total_quota+$total_craft_show.'</td>';
            $str .= '    <td></td>';
            $str .= '</tr>';
            $str .= '<tr>';
            $str .= '    <td class="text-left" colspan="2">工程报价</td>';
            $str .= '    <td colspan="6">'.$offerlist_info['discount_proquant'].'</td>';
            $str .= '    <td></td>';
            $str .= '</tr>';
        }
        
        //一串字
        if($order_info['o_remark']){
            foreach(explode("\n",$order_info['o_remark']) as $k=>$v){
                $str .= '<tr>
                        <td colspan="9" style="text-align: left">'.$v.'</td>
                    </tr>';
            }
        }
        $str .=  '</tbody></table>';

        echo($str);
    }

//cad文件下载
    public function down()
    {
        if(input('type') == 2){
            $src=Db::table('fdz_offerlist')->where('id',input('id'))->value('effect_img');
        }else{
            $src=Db::table('fdz_offerlist')->where('id',input('id'))->value('cad_file');  
        }
        $file=ROOT_PATH.'uploads/cad/'.$src;
        if(!file_exists($file)){
            $this->error('文件不存在');
        }else{
            return download($file,'cad');
        }
    }
    //提交非标申请,修改非标状态
    public function fbsh()
    {
        $data=input();
       $res= Db::table('fdz_offerlist')->where('id',$data['key'])->setField('no_standard',$data['status']);
        return json(['code'=>1,'msg'=>'成功','data'=>$res]);
    }
    //
    public function gcjlsh()
    {
        $user=$this->_userinfo;
        $res=\app\admin\model\Offerlist::with(['user','ddyl'])->where('frameid',$user['companyid'])->where('no_standard','in',[3,4,5,6])->select();
        $fb=[];
        foreach ($res as $k=>$v){
            foreach ($v['ddyl'] as $k1=>$v1){

                if($v1['of_fb']==0){
                    unset($v['ddyl'][$k1]);
                }else{
                    $fb[]= $v['ddyl'][$k1];
                }
            }
        }
        foreach ($fb as $k3=>$v3)
        {
            $fb[$k3] = json_decode($v3,true);

        }
        foreach ($fb as  $k4=>$v4)
        {
            $fb[$k4]['frame']=Db::table('fdz_frame')->where('id', $v4['frame'])->value('name');
            $fb[$k4]['uname']=Db::table('fdz_admin')->where('userid', $v4['uname'])->value('name');
        }
        $data = $fb;
        $curpage = input('page') ? input('page') : 1;//当前第x页，有效值为：1,2,3,4,5...
        $listRow = 10;//每页10行记录
        $dataTo = array();
        $dataTo = array_chunk($data, $listRow);

        $showdata = array();
        if ($dataTo) {
            $showdata = $dataTo[$curpage - 1];
        } else {
            $showdata = null;
        }
        $p = Bootstrap::make($showdata, $listRow, $curpage, count($data), false, [
            'var_page' => 'page',
            'path' => '/admin/offerlist/gcjlsh/',//这里根据需要修改url
            'query'=>request()->param(),//此处参数可以保留当前数据集的查询条件
            'fragment' => '',
        ]);
        $p->appends($_GET);
        $this->assign('fb',$p);
        return $this->fetch();
    }


    //工程经理修改
    public function gcjledit(Request $request )
    {
        if(request()->isGet()){
            $da=$request->get();
            $res=OrderProject::where('id',$da['id'])->find();
            $this->assign('data',$res);
            return $this->fetch();
        }else{
           $da=$request->post();
            $res=OrderProject::where('id',$da['id'])->data(['project' => $da['project'],'company'=>$da['company'],'quota'=>$da['quota'],'craft_show'=>$da['craft_show'],'material'=>$da['material']])
                ->update();
            if($res){
                echo "<script>window.parent.location.reload();</script>";
            }else{
                $this->error('更新出错');
            }
        }

    }


    public function cjsh()
    {
        $data=input();
        if($data['status']==6){
           $user= Db::table('fdz_order_project')->where('id',$data['key'])->value('o_id');
           Db::table('fdz_offerlist')->where('id',$user)->update(['no_standard'=>$data['status']]);
           Db::table('fdz_order_project')->where('id',$data['key'])->update(['of_fb'=>$data['status'],'gname'=>$user['userid']]);
           return json(['code'=>1,'msg'=>'提交成功']);
        }else{
            $user=$this->_userinfo;
            $res= Db::table('fdz_order_project')->where('id',$data['key'])->update(['of_fb'=>$data['status'],'gname'=>$user['userid']]);
            return json(['code'=>1,'msg'=>'成功','data'=>$res]);
        }
    }

    public function cjbind()
    {
        $res=\app\admin\model\Offerlist::with(['user','ddyl'])->where('no_standard','3')->select();
        $fb=[];
        foreach ($res as $k=>$v){
            foreach ($v['ddyl'] as $k1=>$v1){
                if($v1['of_fb']==2){
                    $fb[]= $v['ddyl'][$k1];
                }else{
                    unset($v['ddyl'][$k1]);
                }
            }
        }
        foreach ($fb as $k3=>$v3)
        {
            $fb[$k3] = json_decode($v3,true);
        }
        foreach ($fb as  $k4=>$v4)
        {
            $fb[$k4]['frame']=Db::table('fdz_frame')->where('id', $v4['frame'])->value('name');
            $fb[$k4]['gname']=Db::table('fdz_admin')->where('userid', $v4['uname'])->value('name');
        }

        $this->assign('data',$fb);
        return $this->fetch();

    }

    public function cjbindedit(Request $request)
    {
        if(request()->isGet()) {
            $da = $request->get();
            $user=$this->_userinfo;
            $where = [];
            if(input('typeof')){
                $where[] = ['type_of_work','like','%'.input('typeof').'%'];
            }
            if($user['roleid']==1){
                $frame=Db::table('fdz_order_project')->where('id',$da['id'])->value('frame');
                $type_of_work=Db::table('fdz_offerquota')->where('frameid',$frame)->group('type_of_work')->select();
                $company = Db::table('fdz_offerquota')->where('frameid',$frame)->where($where)->select();
            }else{
                $company = Db::table('fdz_offerquota')->where('frameid',$user['companyid'])->where($where)->select();
                $type_of_work=Db::table('fdz_offerquota')->where('frameid',$user['companyid'])->group('type_of_work')->select();
            }
            $this->assign('company', $company);
            $this->assign('typeof', $type_of_work);
            $this->assign('data', $da);
            return $this->fetch();
        }else{
            $da = $request->post();
            $bingid=Db::table('fdz_offerquota')->where('id',$da['bindid'])->find();
            $id=Db::table('fdz_order_project')->where('id',$da['id'])->find();
            $new['item_number']=$bingid['item_number'];
            $new['type_of_work']=$bingid['type_of_work'];
            $new['project']=$bingid['project'];
            $new['company']=$bingid['company'];
            $new['cost_value']=$bingid['cost_value'];
            $new['quota']=$bingid['quota'];
            $new['craft_show']=$bingid['craft_show'];
            $new['labor_cost']=$bingid['labor_cost'];
            $new['material']=$bingid['material'];
            $new['content']=$bingid['content'];
            $new['of_fb']=3;
            $res=Db::table('fdz_order_project')->where('id',$da['id'])->data($new)->update();
            if(!empty($new['content'])){
                $new['content']=json_decode( $new['content']);
                foreach ($new['content'] as $key=>$value)
                {
                    if(!$value[0]){
                        continue;
                    };
                    $nu[]=$value[1];
                    $new['content'][$key]['fc']=Db::table('fdz_materials')->where('frameid',$bingid['frameid'])->where('name|amcode',$value[0])->find();
                    $material['o_id']=$id['o_id'];
                    $material['c_id']=$bingid['userid'];
                    $material['f_id']=$bingid['frameid'];
                    $material['type_of_work']=$bingid['type_of_work'];
                    $material['item_number']=$bingid['item_number'];
                    $material['m_name']=  $new['content'][$key]['fc']['name'];
                    $material['num']=  $value[1]*$id['num'];
                    $material['cb']=  $new['content'][$key]['fc']['price'];
                    $material['price']=   $bingid['quota'];
                    $material['profit']=  $material['price']- $material['cb'];
                    $material['coefficient']= $new['content'][$key]['fc']['coefficient'];
                    $material['important']= $new['content'][$key]['fc']['important'];
                    $material['amcode']=  $new['content'][$key]['fc']['amcode'];
                    $material['fine']=  $new['content'][$key]['fc']['fine'];
                    $material['brand']=  $new['content'][$key]['fc']['brand'];
                    $material['place']=  $new['content'][$key]['fc']['place'];
                    $material['img']=  $new['content'][$key]['fc']['img'];
                    $material['phr']=  $new['content'][$key]['fc']['phr'];
                    $material['remarks']=  $new['content'][$key]['fc']['remarks'];
                    $material['category']=  $new['content'][$key]['fc']['category'];
                    $material['units']=  $new['content'][$key]['fc']['units'];
                    Db::table('fdz_order_material')->insert($material);
                }
            }

            $company = Db::table('fdz_order_project')->where('o_id',$id['o_id'])->select();
            foreach ($company as $k2=>$v2)
            {
                if($v2['type_of_work']!='非标报价'){
                    unset($company[$k2]);
                }
            }
            if(count($company)==0){
               Db::table('fdz_offerlist')->where('id',$id['o_id'])->update(['no_standard' => 5]);
            }
            if($res){
                echo "<script src='https://www.layuicdn.com/layer/layer.js'></script>";
                echo   "<script>layer.msg('绑定成功'); </script>";
                echo "<script>window.parent.location.reload()</script>";
            }else{
                $this->error('失败');
            }
        }

    }

    public function createfb(Request $request)
    {
        if(request()->isGet()) {
            $da = $request->get();
            $fb = Db::table('fdz_order_project')->where('id', $da['id'])->find();
            //工种类别type_of_work 编号item_number,.操作员userid,内容content
            $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(), null, 'id');
            //获取所有辅材细类
            $fines = Db::name('basis_materials')->field('fine,unit')->group('fine')->select();
//            dump($fb);
            $this->assign('data', $fb);

            $this->assign('fines', $fines);
            $this->assign('type_work', $type_work);
            return $this->fetch();
        }else{
            $da = $request->post();
            $datas = [];
            $datas['item_number'] = input('item_number');
            $datas['type_word_id'] = input('type_word_id');
            $datas['name'] = input('name');
            $datas['unit'] = input('unit');
            $datas['content'] = input('content');
            $find = input('find');
            $funit = input('funit');
            if ($find && count($find) != count(array_unique($find))) {
                $this->error('细类不得重复');
            }
            //判断编号是否有重复
            $has_project = Db::name('basis_project')->where(['item_number'=>$datas['item_number']])->value('id');
            if($has_project){
                $this->error('编号已存在');
            }
            if($find){
                $materials = [];
                foreach($find as $k=>$v){
                    $info = [];
                    $info['fine'] = $v;
                    $info['funit'] = $funit[$k];
                    $materials[] = $info;
                }
                $datas['fine'] = json_encode($materials);
            }else{
                $datas['fine'] = '{}';
            }
          $n=  Db::table('fdz_basis_type_work')->where('id',$da['type_word_id'])->find();
            $neq=Db::table('fdz_order_project')->where('id',$da['key'])->update([
                'item_number'=>$da['item_number'],
                'type_of_work'=>$n['name'] ,
                'project'=>$da['name'],
                'company'=>$da['unit'],
                'material'=>$da['content'],
            ]);
            $res = Db::name('basis_project')->insert($datas);
            if($res){
                Db::table('fdz_order_project')->where('id',$da['key'])->setField('of_fb',4);
                echo "<script>window.parent.location.reload()</script>";
            }else{
                $this->error('失败');
            }
        }
    }

    public function insergsck(Request $request)
    {
        if (request()->isGet()) {
            $da = $request->get();
            $fb = Db::table('fdz_order_project')->where('id', $da['id'])->find();
            $p_item_number = Db::table('fdz_basis_project')->where('item_number', $fb['item_number'])->find();
            //工种类别type_of_work 编号item_number,.操作员userid,内容content
            $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(), null, 'id');
            $p_item_number['fine'] = json_decode($p_item_number['fine'], true);
            foreach ($p_item_number['fine'] as $k => $v) {
                $p_item_number['fine'][$k]['fi'] = [];
                $p_item_number['fine'][$k]['fi'] = Db::table('fdz_f_materials')->where('fid',$fb['frame'])->where('fine', $v['fine'])->select();
                foreach ($p_item_number['fine'][$k]['fi'] as $k1 => $v1) {
                    $p_item_number['fine'][$k]['fi'][$k1]['name'] = '';
                    $p_item_number['fine'][$k]['fi'][$k1]['na'][] = Db::table('fdz_basis_materials')->where('amcode', $v1['p_amcode'])->find();
                    foreach ($p_item_number['fine'][$k]['fi'][$k1]['na'] as $k2 => $v2) {
                        $p_item_number['fine'][$k]['fi'][$k1]['name'] = $v2['name'];
                    }
                }
            }
            $this->assign('p_item_number', $p_item_number);
            $this->assign('fb', $fb);
            $this->assign('type_work', $type_work);
            return $this->fetch();
        } else {
            $da = $request->post();
            $jk=OrderProject::with('offerlist')->where('id',$da['id'])->find();
            $user = $this->_userinfo;
            $f_project['p_item_number'] = $da['item_number'];
            $f_project['fid'] = $user['companyid'];
            $f_project['cost_value'] = $da['quota'] + $da['craft_show'];
            $f_project['quota'] = $da['quota'];
            $f_project['craft_show'] = $da['craft_show'];
            $f_project['labor_cost'] = $da['labor_cost'];
            if(empty($da['fine'])){
           $f_project['material']=[];
            }else{
                foreach ($da['fine'] as $k => $v) {
                    foreach ($da['content'] as $k1 => $v1) {
                        if ($k == $k1) {
                            unset($da['content'][$k]);
                            $da['content'][$v] = $v1;
                        }
                    }
                }
                $f_project['material'] = json_encode($da['content']);
            }
            $res = Db::table('fdz_f_project')->insertGetId($f_project);
            if ($res) {
                $re=$f_project['p_item_number'] . '_' . $res;
                Db::name('f_project')->where(['id' => $res])->update(['item_number' => $re]);
            }
            $f_project = Db::name('f_project')->where(['item_number'=>$re])->find();
            $basis_project = Db::name('basis_project')->where(['item_number'=>$f_project['p_item_number']])->find();

            $info = [];
            //计算辅材基数
            if($f_project['material']){
                $fine = json_decode($basis_project['fine'],true);
                $fine = array_column($fine, 'funit','fine');//公式
                $material = json_decode($f_project['material'],true);
                $datas_material = [];
                foreach($material as $k1=>$v1){
                    // $fine[$k1] 需要的数量
                    $pack = Db::name('f_materials')->where(['amcode'=>$v1])->value('pack');//包装数量
                    $num = round($fine[$k1]/$pack,2);
                    if($num <= 0){
                        $num = 0.001;
                    }
                    $datas_material[] = [$v1,round($num,2)];
                }
                $info['content'] = json_encode($datas_material);
            }else{
                $info['content'] = '';
            }
            $info['frameid'] = $f_project['fid'];
            $info['userid'] = $this->_userinfo['companyid'];
            $info['item_number'] = $re;
            $info['type_of_work'] =$da['type_of_work'] ;
            $info['project'] = $da['project'];
            $info['company'] = $da['company'];
            $info['cost_value'] = $da['quota'] + $da['craft_show'];
            $info['quota'] = $da['quota'];
            $info['craft_show'] = $da['craft_show'];
            $info['labor_cost'] = $da['labor_cost'];
            $info['material'] = $da['material'];
          $unm=Db::table('fdz_offerquota')->insert($info);
         //更新到订单  $da['order_project']
            $new['item_number']=$info['item_number'];
            $new['type_of_work']=$info['type_of_work'];
            $new['project']=$info['project'];
            $new['company']=$info['company'];
            $new['cost_value']=$info['cost_value'];
            $new['quota']=$info['quota'];
            $new['craft_show']=$info['craft_show'];
            $new['labor_cost']=$info['labor_cost'];
            $new['material']=$info['material'];
            $new['content']=$info['content'];
            $new['of_fb']=5;

            $yes= Db::table('fdz_order_project')->where('id',$da['order_project'] )->update($new);
            if(!empty($new['content'])){
                $new['content']=json_decode( $new['content']);
                foreach ($new['content'] as $key=>$value)
                {
                    if(!$value[0]){
                        continue;
                    };
                    $nu[]=$value[1];

                    $new['content'][$key]['fc']=Db::table('fdz_materials')->where('frameid',$info['frameid'])->where('name|amcode',$value[0])->find();

                    $mater['o_id']=$jk['o_id'];
                    $mater['c_id']=$jk['offerlist']['customerid'];
                    $mater['f_id']=$new['content'][$key]['fc']['frameid'];
                    $mater['type_of_work']=$info['type_of_work'];
                    $mater['item_number']=$info['item_number'];
                    $mater['m_name']=  $new['content'][$key]['fc']['name'];
                    $mater['num']=  $value[1]*$jk['num'];
                    $mater['cb']=  $new['content'][$key]['fc']['price'];
                    $mater['price']=   $info['quota'];
                    $mater['profit']=  $mater['price']- $mater['cb'];
                    $mater['coefficient']= $new['content'][$key]['fc']['coefficient'];
                    $mater['important']= $new['content'][$key]['fc']['important'];
                    $mater['amcode']=  $new['content'][$key]['fc']['amcode'];
                    $mater['fine']=  $new['content'][$key]['fc']['fine'];
                    $mater['brand']=  $new['content'][$key]['fc']['brand'];
                    $mater['place']=  $new['content'][$key]['fc']['place'];
                    $mater['img']=  $new['content'][$key]['fc']['img'];
                    $mater['phr']=  $new['content'][$key]['fc']['phr'];
                    $mater['remarks']=  $new['content'][$key]['fc']['remarks'];
                    $mater['category']=  $new['content'][$key]['fc']['category'];
                    $mater['units']=  $new['content'][$key]['fc']['units'];
                    Db::table('fdz_order_material')->insert($mater);

                }
            }
            $yen= Db::table('fdz_order_project')->where('id',$da['order_project'] )->value('o_id');
            $company = Db::table('fdz_order_project')->where('o_id',$yen)->select();
            foreach ($company as $k2=>$v2)
            {
                if($v2['type_of_work']!='非标报价'){
                    unset($company[$k2]);
                }
            }
            if(count($company)==0){
                Db::table('fdz_offerlist')->where('id',$yen)->update(['no_standard' => 5]);
            }
            if($yes){
                echo "<script>window.parent.location.reload()</script>";
            }else{
                $this->error('操作失败');
            }
        }
    }

    public function superbind(Request $request)
    {
        if (request()->isGet()) {
            $da=$request->get();
            if(input('typeof')){
                $basis_project = Db::table('fdz_basis_project')->where('type_word_id',input('typeof'))->select();
            }else{
                $basis_project = Db::table('fdz_basis_project')->select();
            }
            $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(), null, 'id');
            $this->assign('basis_project', $basis_project);
            $this->assign('typeof', $type_work);
            $this->assign('da', $da);
            return $this->fetch();
        }else{
            $da=$request->post();
            $basis_project= Db::table('fdz_basis_project')->where('id',$da['basis_project'])->find();
            $n=  Db::table('fdz_basis_type_work')->where('id',$basis_project['type_word_id'])->value('name');
            $res= Db::table('fdz_order_project')->where('id',$da['id'])->update([
               'item_number'=>$basis_project['item_number'],
                'of_fb'=>4,
                'type_of_work'=>$n,
                'project'=>$basis_project['name'],
                'company'=>$basis_project['unit'],
                'material'=>$basis_project['content'],
                ]);
            if($res){
                echo "<script>window.parent.location.reload()</script>";
            }else{
                $this->error('失败');
            }
        }
    }

}
