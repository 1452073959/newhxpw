<?php

// +----------------------------------------------------------------------
// | 统计报表
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\AdminUser;
use app\admin\model\PickingMaterial;
use app\admin\model\Userlist;
use app\common\controller\Adminbase;
use app\common\model\Demo;
use think\Db;
use think\Paginator;
use think\Request;

class Statistical extends Adminbase
{
    //业绩统计 
    public function results_index(){

        
        //personnel表的
        $person_where = [];
        $person_where['fid'] = $this->_userinfo['companyid'];

        //admin表的
        $admin_where = [];
        $admin_where['companyid'] = $this->_userinfo['companyid'];
        $admin_where['roleid'] = [13,15,16];//监理,质检,工程经理
        if(input('position')){
            $position = explode('_', input('position'));
            if($position[0] == 'p'){
                $person_where['job'] = $position[1];
                $admin_where['roleid'] = 0;
            }else{
                $admin_where['roleid'] = $position[1];
                $person_where['job'] = 0;
            }
        }

        $name_where = [];
        if(input('name')){
            $name_where[] = ['name','like','%'.input('name').'%'];
        }

        $user_where = [];
        if(input('begin_time') && input('end_time')){
            $user_where = array(['sign_bill_time','>',strtotime(input('begin_time'))],['sign_bill_time','<',strtotime('+1 day',strtotime(input('end_time')))]);
          }

        $total = [];//合计
        $total['total_money'] = 0;//总开工额
        $total['total_profits'] = 0;//总利润
        $total['total_area'] = 0;//面积
        $total['total_num'] = 0;//开工数量
        $total['old_house_num'] = 0;//二手房数量
        $total['num1'] = 0;//平层数量
        $total['num2'] = 0;//复式数量
        $total['num3'] = 0;//别墅数量
        $total['num4'] = 0;//公装数量
        $end_total = [];//已经统计过的
        //personnel表的
        // $person_where = [];
        // $person_where['fid'] = $this->_userinfo['companyid'];
        // /[1 => '设计师', 2 => '报价师', 3 => '商务经理', 4 => '工程监理', 5 => '其他',6=>'仓管',7=>'质检',8=>'工程经理',9=>'财务',10=>'出纳',11=>'人事',12=>'总经理',13=>'总设计师'];
        $personnel = Db::name('personnel')->where($person_where)->where($name_where)->field('id,name,job,phone,status')->select();
        $personnel_ids = array_column($personnel, 'id');
        $userlist = Db::name('userlist')->where($user_where)->where('status','>=',3)->where(['quoter_id|designer_id|manager_id|assistant_id|sale_id'=>$personnel_ids])->select();
        // var_dump($userlist);die;
        foreach($personnel as $k1=>$v1){
            //初始化数据----------start
            $personnel[$k1]['total_money'] = 0;//总开工额
            $personnel[$k1]['total_profits'] = 0;//总利润
            $personnel[$k1]['total_area'] = 0;//面积
            $personnel[$k1]['total_num'] = 0;//开工数量
            $personnel[$k1]['old_house_num'] = 0;//二手房数量
            $personnel[$k1]['num1'] = 0;//平层数量
            $personnel[$k1]['num2'] = 0;//复式数量
            $personnel[$k1]['num3'] = 0;//别墅数量
            $personnel[$k1]['num4'] = 0;//公装数量
            //初始化数据---------end
            foreach($userlist as $k2=>$v2){
                if(($v1['id'] == $v2['quoter_id'] || $v1['id'] == $v2['designer_id'] || $v1['id'] == $v2['manager_id'] || $v1['id'] == $v2['assistant_id'] || $v1['id'] == $v2['sale_id']) && $v2['oid'] != 0){
                    $order_info = model('offerlist')->get_order_info($v2['oid'], 2);
                    $personnel[$k1]['total_money'] += $order_info['discount_proquant'];
                    $personnel[$k1]['total_profits'] += $order_info['gross_profit_total'];
                    $personnel[$k1]['total_area'] += $v2['area'];
                    $personnel[$k1]['total_num']++;
                    if($v2['house_type'] == 2){
                        $personnel[$k1]['old_house_num']++;
                    }
                    if ($v2['room_type'] == '平层') {
                        $personnel[$k1]['num1']++;
                    }elseif($v2['room_type'] == '复式'){
                        $personnel[$k1]['num2']++;
                    }elseif($v2['room_type'] == '别墅'){
                        $personnel[$k1]['num3']++;
                    }elseif($v2['room_type'] == '公装'){
                        $personnel[$k1]['num4']++;
                    }

                    if(!in_array($v2['oid'], $end_total)){
                        $total['total_money'] += $order_info['discount_proquant'];
                        $total['total_profits'] += $order_info['gross_profit_total'];
                        $total['total_area'] += $v2['area'];
                        $total['total_num']++;
                        if($v2['house_type'] == 2){
                            $total['old_house_num']++;
                        }
                        if ($v2['room_type'] == '平层') {
                            $total['num1']++;
                        }elseif($v2['room_type'] == '复式'){
                            $total['num2']++;
                        }elseif($v2['room_type'] == '别墅'){
                            $total['num3']++;
                        }elseif($v2['room_type'] == '公装'){
                            $total['num4']++;
                        }
                        $end_total[] = $v2['oid'];
                    }
                    unset($order_info);//释放内存
                }
            }
            if(empty($personnel[$k1]['total_num'])){
                unset($personnel[$k1]);
            }
        }
        //============================分割线=========================
        
        $admin = Db::name('admin')->where($admin_where)->where($name_where)->field('userid,username,name,roleid')->select();
        $admin_ids = array_column($admin, 'userid');
        $userlist = Db::name('userlist')->where($user_where)->where('status','>=',3)->where(['jid|check_id|gcmanager_id'=>$admin_ids])->select();
        foreach($admin as $k1=>$v1){
            //初始化数据----------start
            $admin[$k1]['total_money'] = 0;//总开工额
            $admin[$k1]['total_profits'] = 0;//总利润
            $admin[$k1]['total_area'] = 0;//面积
            $admin[$k1]['total_num'] = 0;//开工数量
            $admin[$k1]['old_house_num'] = 0;//二手房数量
            $admin[$k1]['num1'] = 0;//平层数量
            $admin[$k1]['num2'] = 0;//复式数量
            $admin[$k1]['num3'] = 0;//别墅数量
            $admin[$k1]['num4'] = 0;//公装数量
            //初始化数据---------end
            foreach($userlist as $k2=>$v2){
                if(($v1['userid'] == $v2['jid'] || $v1['userid'] == $v2['check_id'] || $v1['userid'] == $v2['gcmanager_id']) && $v2['oid'] != 0){
                    $order_info = model('offerlist')->get_order_info($v2['oid'], 2);
                    $admin[$k1]['total_money'] += $order_info['discount_proquant'];
                    $admin[$k1]['total_profits'] += $order_info['gross_profit_total'];
                    $admin[$k1]['total_area'] += $v2['area'];
                    $admin[$k1]['total_num']++;
                    if($v2['house_type'] == 2){
                        $admin[$k1]['old_house_num']++;
                    }
                    if ($v2['room_type'] == '平层') {
                        $admin[$k1]['num1']++;
                    }elseif($v2['room_type'] == '复式'){
                        $admin[$k1]['num2']++;
                    }elseif($v2['room_type'] == '别墅'){
                        $admin[$k1]['num3']++;
                    }elseif($v2['room_type'] == '公装'){
                        $admin[$k1]['num4']++;
                    }

                    if(!in_array($v2['oid'], $end_total)){
                        $total['total_money'] += $order_info['discount_proquant'];
                        $total['total_profits'] += $order_info['gross_profit_total'];
                        $total['total_area'] += $v2['area'];
                        $total['total_num']++;
                        if($v2['house_type'] == 2){
                            $total['old_house_num']++;
                        }
                        if ($v2['room_type'] == '平层') {
                            $total['num1']++;
                        }elseif($v2['room_type'] == '复式'){
                            $total['num2']++;
                        }elseif($v2['room_type'] == '别墅'){
                            $total['num3']++;
                        }elseif($v2['room_type'] == '公装'){
                            $total['num4']++;
                        }
                        $end_total[] = $v2['oid'];
                    }
                    unset($order_info);//释放内存
                }
            }
            if(empty($admin[$k1]['total_num'])){
                unset($admin[$k1]);
            }
        }
        // var_dump($personnel);
        // var_dump($total);die;
        $this->assign('personnel', $personnel);
        $this->assign('admin', $admin);
        $this->assign('total', $total);
        $this->assign('job', [1 => '设计师', 2 => '报价师', 3 => '商务经理', 4 => '工程监理', 5 => '其他',6=>'仓管',7=>'质检',
            8=>'工程经理',9=>'财务',10=>'出纳',11=>'人事',12=>'总经理',13=>'总设计师',14=>'业务员',15=>'画图',
           16=>'设计总监',17=>'设计组长',18=>'效果图',19=>'市场经理',20=>'业务员',21=>'网销',22=>'行政',23=>'前台',24=>'客服'

        ]);
        $this->assign('role', array_column(Db::name('auth_group')->field('id,title')->select(),null, 'id'));
        return $this->fetch();
    }

    //领料统计
    public function picking_index()
    {
        $where = [];
        $condition = [];
        if($this->_userinfo['roleid'] != 1){
            $where[] = ['frameid','=',$this->_userinfo['companyid']];
        }
        if(input('username')){
            $where[] = ['customer_name','like','%'.input('username').'%'];
        }
        if(input('jlname')){
            $jid = Db::name('admin')->where('name','like','%'.input('jlname').'%')->select();
            if($jid){
                $where[] = ['jid','in',array_column($jid, 'userid')];
            }else{
                $where[] = ['jid','=',-1];
            }
        }
        if(input('begin_time') && input('end_time')){
            $condition = array(['sign_bill_time','>',strtotime(input('begin_time'))],['sign_bill_time','<',strtotime('+1 day',strtotime(input('end_time')))]);
        }
        
        $where[] = ['status','in',[3,4,5,6,7]];
        $datas = Db::name('userlist')->where($where)->where($condition)->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['all_material_money'] = Model('offerlist')->get_material_list($item['oid'],2)['total_money'];
            $item['status1'] = 0; //未审核辅材
            $item['status23'] = 0;//待领辅材
            $item['status4'] = 0;//已领辅材
            $item['type1'] = 0;//定点
            $item['type2'] = 0;//自购
            $picking_material = Db::name('picking_material')->where(['oid'=>$item['oid'],'status'=>[1,2,3,4]])->select();
            $picking_order = Db::name('picking_order')->where(['userid'=>$item['id']])->select();
            if($picking_order){
                foreach($picking_order as $k=>$v){
                    switch ($v['type']) {
                        case '1':
                            $item['type1'] += $v['money'];
                            break;
                        case '2':
                            $item['type2'] += $v['money'];
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
            if($picking_material){
                foreach($picking_material as $k=>$v){
                    switch ($v['status']) {
                        case '1':
                            $item['status1'] += $v['total_money'];
                            break;
                        case '2':
                            $item['status23'] += $v['total_money'];
                            break;
                        case '3':
                            $item['status23'] += $v['actual_total_money'];
                            break;
                        case '4':
                            $item['status4'] += $v['actual_total_money'];
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
            return $item;
        });
        $admins = array_column($datas->items(),'jid');
        // var_dump($datas->items());die;
        $admins = array_column(Db::name('admin')->where(['userid'=>$admins])->select(),null, 'userid') ;
        $this->assign('datas', $datas);
        $this->assign('admins', $admins);
        return $this->fetch();
    }

    //实际领料详情 公司仓库
    public function actual_picking_ck(){
        $where = [];
        $where['userid'] = input('uid');
        $userinfo = Db::name('userlist')->where(['id'=>input('uid')])->find();
        //仓库领料
        $datas = Db::name('picking_material')->where($where)->order('id','asc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['addtime'] = date('Y-m-d',strtotime($item['addtime']));
            // $item['info'] = Db::name('picking_material_info')->where(['pmid'=>$item['id']])->order('id','asc')->select();
            // foreach($item['info'] as $k1=>$v1){
            //     $item['info'][$k1]['total'] = round($v1['price']*$v1['num'],2);
            // }
            return $item;
        });
        $admins = array_column($datas->items(),'adminid');
        $admins = array_column(Db::name('admin')->where(['userid'=>$admins])->select(),null, 'userid') ;
        $this->assign('datas', $datas);
        $this->assign('admins', $admins);
        $this->assign('userinfo', $userinfo);
        return $this->fetch();
    }

    //实际领料详情 定点,自购
    public function actual_picking_dz(){
        $where = [];
        $where['userid'] = input('uid');
        $userinfo = Db::name('userlist')->where(['id'=>input('uid')])->find();
        //定点,自购
        $datas = Db::name('picking_order')->where($where)->order('id','asc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $img = Db::name('picking_order_img')->where(['poid'=>$item['id']])->order('id','desc')->select();
            foreach($img as $k1=>$v2){
                $item['img'][] = $this->getImgSrc($v2['img']);
            }
            return $item;
        }); 
        $admins = array_column($datas->items(),'adminid');
        $admins = array_column(Db::name('admin')->where(['userid'=>$admins])->select(),null, 'userid') ;
        $this->assign('datas', $datas);
        $this->assign('userinfo', $userinfo);
        $this->assign('admins', $admins);
        return $this->fetch();
    }
    //自购/订单领料图片
    public function buying(Request $request)
    {
        $data=$request->get();
        $buying = Db::table('fdz_picking_order_img')->where('poid',$data['uid'])->select();

        foreach ($buying as $k=>$v)
        {
            $buyingimg[]='/uploads/images/'.$v['img'];
        }
        $this->assign('buyingimg',$buyingimg);
        return $this->fetch();
    }

//    合计
    public function total(Request $request)
    {
        $data=$request->get();
        $userinfo = Userlist::with(['user','gcjl','zj'])->where('id',$data['uid'])->find();
        $buying=Db::table('fdz_picking_order')->where('userid',$data['uid'])->select();
        $summation=0;
        foreach ($buying as $key=>$value)
        {
            $summation+=$value['money'];
        }
        $history=PickingMaterial::where('userid',$data['uid'])->where('status',4)->select();
        $reality=[];
        foreach ($history as $k=>$v)
        {
            $history[$k]['details']=Db::table('fdz_picking_material_info')->where('pmid',$v['id'])->select();
            foreach ($history[$k]['details'] as $k1=>$v1){
                $reality[]=$v1;
            }
        }
        $amount=0;
        foreach ($reality as $k1=>$v1){
            $amount+=$v1['price']*$v1['actual_num'];
        }
//        dump($userinfo);
        $this->assign('userinfo',$userinfo);
        $this->assign('reality',$reality);
        $this->assign('summation',$summation);
        $this->assign('amount',$amount);
        return $this->fetch();
    }
    //工程派单
    public function send_order_index()
    {
        return $this->fetch();
    }

    //监理管理
    public function supervision_index()
    {
        $user = $this->_userinfo;
        $user=AdminUser::with('Supervision')->where('roleid',13)->where('companyid',$user['companyid'])->select();
        $this->assign('user',$user);
        return $this->fetch('supervision_index1');
    }
    //监理工地
    public function construction(Request $request)
    {
        $construction=$request->get();
        $construction=Userlist::where('jid',$construction['id'])->select();
        $this->assign('construction',$construction);
        return $this->fetch();
    }

    //在施工地
    public function in_word(){

        $where = [];
        if (!empty($_GET['customer_name'])) {
            $where[] = ['customer_name', 'like', "%{$_GET['customer_name']}%"];
        }
        if (!empty($_GET['jid'])) {
            $where[] = ['jid', 'in', "{$_GET['jid']}"];
        }
        if (!empty($_GET['address'])) {
            $where[] = ['address', 'like', "%{$_GET['address']}%"];
        }
        if($this->_userinfo['roleid'] != 1){
            $where[] = ['frameid', '=', $this->_userinfo['companyid']];
        }
        $order = Userlist::with('profile', 'user', 'picking')->where($where)->where('status','>=',3)->where('oid','>','0')->paginate(10,false,['query'=>request()->param()]);
        foreach ($order as $k => $v) {
            $order[$k]['total_picking'] = 0;
            if (!empty($v['picking'])) {
                foreach ($v['picking'] as $k1 => $v1) {
                    $order[$k]['total_picking'] += $v1['actual_total_money'];
                }
            }
        }

        foreach ($order as $k2 => $v2) {

            if (!$v2['profile']) {
                continue;
            }
            $order[$k2]['order_info'] = model('offerlist')->get_order_info($v2['profile']['id'], 2);
        }
        $user = Db::table('fdz_admin')->where('roleid', '13')->where(['companyid'=>$this->_userinfo['companyid']])->select();
        $this->assign('order', $order);
        $this->assign('users', $user);

        return $this->fetch();
    }
    //领料记录
    public function history(Request $request)
    {
        $data=$request->get();
        $history=PickingMaterial::with(['user','client','user1'])->where('userid',$data['uid'])->select();
        $this->assign('history',$history);

      return  $this->fetch();
    }
    //领料详情

    public function particulars(Request $request)
    {
        $data=$request->get();
        $particulars=Db::table('fdz_picking_material_info')->where('pmid',$data['pmid'])->select();
         $this->assign('particulars',$particulars);
        return $this->fetch();
    }





    public function change_array($array)
    {
        foreach ($array as $k => $v) {
            //若$v仍为数组 则调用自身
            if (is_array($v)) {
                $this->change_array($v);
            } else {
                $this->arr[] = $v;
            }
        }
        return $this->arr;

    }

    //模拟返回数据
    public function return_data()
    {
        $data = [
            [
                "td1" => "商务经理",
                "td2" => "张三",
                "td3" => "101900",
                "td4" => "51%",
                "td5" => "1250",
                "td6" => "8",
                "td7" => "2",
                "td8" => "6",
                "td9" => "0",
                "td10" => "0",
                "td11" => "0",
            ],
            [
                "td1" => "报价师",
                "td2" => "李四",
                "td3" => "56900",
                "td4" => "49%",
                "td5" => "850",
                "td6" => "7",
                "td7" => "2",
                "td8" => "2",
                "td9" => "1",
                "td10" => "2",
                "td11" => "0",
            ],
            [
                "td1" => "商务经理",
                "td2" => "小明",
                "td3" => "12900",
                "td4" => "45%",
                "td5" => "850",
                "td6" => "3",
                "td7" => "0",
                "td8" => "1",
                "td9" => "0",
                "td10" => "2",
                "td11" => "0",
            ],
            [
                "td1" => "商务经理",
                "td2" => "黄五",
                "td3" => "99800",
                "td4" => "47%",
                "td5" => "1150",
                "td6" => "11",
                "td7" => "2",
                "td8" => "3",
                "td9" => "1",
                "td10" => "4",
                "td11" => "2",
            ],
            [
                "td1" => "合计",
                "td2" => "",
                "td3" => "271500",
                "td4" => "48%",
                "td5" => "1025",
                "td6" => "29",
                "td7" => "6",
                "td8" => "12",
                "td9" => "3",
                "td10" => "8",
                "td11" => "2",
            ]
        ];
        echo json_encode(array('code' => 0, 'count' => count($data), 'data' => $data, 'msg' => 'ok'));
    }

    public function return_department_data()
    {
        echo '{"code":0,"msg":"ok","data":[{"id":0,"name":"\u5e7f\u5dde\u5206\u516c\u53f8","other":"\u5206\u516c\u53f8","levelid":3,"pid":-1,"status":0},{"id":1,"fid":152,"name":"\u8425\u4e1a\u90e8","pid":0,"info_pid":"0","sort":0,"remark":"\u8425\u4e1a\u90e8","addtime":"1567051211"},{"id":15,"fid":152,"name":"\u8bbe\u8ba1\u4e8c\u90e8","pid":3,"info_pid":"0-3","sort":0,"remark":"","addtime":"1567238498"},{"id":3,"fid":152,"name":"\u8bbe\u8ba1\u90e8","pid":0,"info_pid":"0","sort":0,"remark":"\u8bbe\u8ba1\u90e8","addtime":"1567051255"},{"id":5,"fid":152,"name":"\u8bbe\u8ba1\u4e00\u90e8","pid":3,"info_pid":"0-3","sort":0,"remark":"","addtime":"1567051357"},{"id":6,"fid":152,"name":"\u5e02\u573a\u90e8","pid":1,"info_pid":"0-1","sort":0,"remark":"","addtime":"1567051561"},{"id":7,"fid":152,"name":"\u5de5\u7a0b\u90e8","pid":0,"info_pid":"0","sort":0,"remark":"","addtime":"1567070315"},{"id":8,"fid":152,"name":"\u5de5\u7a0b\u4e00\u90e8","pid":7,"info_pid":"0-7","sort":0,"remark":"","addtime":"1567070339"},{"id":10,"fid":152,"name":"\u4ed3\u5e93","pid":8,"info_pid":"0-7-8","sort":0,"remark":"","addtime":"1567130232"},{"id":11,"fid":152,"name":"\u65bd\u5de5","pid":8,"info_pid":"0-7-8","sort":0,"remark":"","addtime":"1567130313"},{"id":12,"fid":152,"name":"\u884c\u653f\u90e8","pid":0,"info_pid":"0","sort":0,"remark":"","addtime":"1567147708"},{"id":13,"fid":152,"name":"\u4eba\u529b\u90e8","pid":12,"info_pid":"0-12","sort":0,"remark":"","addtime":"1567147718"},{"id":14,"fid":152,"name":"\u4f1a\u8ba1","pid":12,"info_pid":"0-12","sort":0,"remark":"","addtime":"1567152792"}],"count":13}';
    }

    //获取图片完整路径 并判断图片是否存在 不存在找到替换
    public function getImgSrc($src,$path="uploads/images/",$http='http://'){

        if(file_exists($path.$src) && $src != '/' && !empty($src)){
            $src = str_replace('\\','/',$src);
            return $http.$_SERVER['HTTP_HOST'].'/'.$path.$src;
        }else{
            return $http.$_SERVER['HTTP_HOST']."/static/imgs/logo1.png";
        }
    }
}