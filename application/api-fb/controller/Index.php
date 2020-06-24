<?php
//首页
namespace app\api\controller;
use app\api\controller\Base;
use app\common\logic\Product;
use think\App;
use think\Request;
use think\Db;

class Index extends Base
{
    protected $ProductLogic;
    public $user_id;
    public function __construct()
    {
        $this->ProductLogic = new Product();
        $this->user_id = input('user_id',0);
    }


    # 首页
    public function index()
    {
        $vehicle_type = $this->ProductLogic->get_list_vehicle_type();
        foreach ($vehicle_type as $k => $v)
        {
            $w['vehicle_type_id'] = $v['vehicle_type_id'];
            $productData = $this->ProductLogic->get_list_product("*",$w);
            $vehicle_type[$k]['productData'] = $productData;
        }
        $d = array('status'=>1,'msg'=>'首页信息','data'=>$vehicle_type);
        echo json_encode($d);exit;
    }

    # 现在用车/预约
    public function by_order()
    {
        $act = input('act','info'); # info 信息, submit 提交
        $start_address = input('start_address',''); # 开始地
        $end_address = input('end_address',''); # 结束地
        $bz = input('bz',''); # 订单备注
        $contact = input('contact',''); # 联系人
        $mobile = input('mobile',''); # 手机号码
        $yu_start_time = input('yu_start_time',''); # 开始时间
        $yu_end_time = input('yu_end_time',''); # 结束时间
        $kill = input('kill',''); # 公里
        $city = input('city','广州'); # 城市
        $product_id = input('product_id'); # 产品ID
        $type = input('type',1); # 1即时订单2预约(回程单))订单
        if(!$this->user_id)
        {
            $d = array('status'=>0,'msg'=>'用户ID不存在','data'=>array());
            echo json_encode($d);exit;
        }
        if(!$product_id)
        {
            $d = array('status'=>0,'msg'=>'产品ID不存在','data'=>array());
            echo json_encode($d);exit;
        }
        # yes if no
        $priceData = $this->ProductLogic->get_procuct_price($city,$product_id,$kill);
        $data['priceData'] = $priceData;
        if($act == 'submit')
        {
            $pt_ordersn = "opt_".generate_sj(5).date('YmdHis',time()).generate_sj(5);
            $sh_ordersn = "osh_".generate_sj(5).date('YmdHis',time()).generate_sj(5);
            $d['pt_ordersn'] = $pt_ordersn;
            $d['sh_ordersn'] = $sh_ordersn;
            $d['order_money'] = $priceData['pay_money'];
            $d['city'] = $city;
            $d['start_address'] = $start_address;
            $d['end_address'] = $end_address;
            $d['contact'] = $contact;
            $d['mobile'] = $mobile;
            $d['yu_start_time'] = $yu_start_time;
            $d['yu_end_time'] = $yu_end_time;
            $d['bz'] = $bz;
            $d['order_time'] = time();
            $d['type'] = $type;
            try{
                model('ProductOrder')->add($d);
                $d = array('status'=>1,'msg'=>'提交成功','data'=>$data);
                echo json_encode($d);exit;
            }catch (\Exception $e)
            {
                $d = array('status'=>0,'msg'=>'系统繁忙','data'=>array());
                echo json_encode($d);exit;
            }
        }else{
            $d = array('status'=>1,'msg'=>'价格明细','data'=>$data);
            echo json_encode($d);exit;
        }
    }

    /**
     * 订单列表
     */
    public function order_list()
    {
        $w['user_id'] = $this->user_id;
        $data = model('ProductOrder')->where($w)->select();
        foreach ($data as $k => $v)
        {

        }
        $d = array('status'=>1,'msg'=>'订单列表','data'=>$data);
        echo json_encode($d);exit;
    }

    /**
     * 提交
     */
    public function submit_order()
    {

    }
}