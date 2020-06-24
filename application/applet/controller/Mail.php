<?php
// +----------------------------------------------------------------------
// | 领料
// +----------------------------------------------------------------------
namespace app\applet\controller;
use app\admin\model\Userlist;
use think\Db;
use think\Controller;
use think\facade\Cache;
use app\applet\controller\UserBase;
 use app\applet\model\Salesrecord;
class Mail extends UserBase{
    //监理领料 客户列表
    public function userlist(){
        $where = [];
        if($this->admininfo['roleid'] != 1 && $this->admininfo['roleid'] != 17){
            $where['jid'] = $this->admininfo['userid'];
        }
        $where['frameid'] = $this->admininfo['companyid'];
        $where['status'] = [3,4,5,6,7];
        $userlist = array_column(Db::name('userlist')->where($where)->order('sign_bill_time','asc')->select(),null, 'id') ;
        foreach($userlist as $k=>$v){
            $userlist[$k]['sign_bill_time'] = date('Y-m-d',$v['sign_bill_time']);
        }
        $this->json(0,'success',$userlist);
    }

    public function getStoreClassify(){
        $datas = [];
        $where = [];
        $where['frameid'] = $this->admininfo['companyid'];
        $materials = Db::name('materials')->where($where)->field('category,fine')->group('category')->group('fine')->select();
        foreach($materials as $k=>$v){
            if(empty(trim($v['fine']))){
                continue;
            }
            $v['category'] = mb_substr($v['category'] , 0 , 2);
            if(!isset($datas[$v['category']])){
                $datas[$v['category']] = [];
            }
            $datas[$v['category']][] = $v['fine'];
        }
        $i = 0;
        $new_datas = [];
        $new_datas[0][0] = '搜索';
        foreach($datas as $k=>$v){
            $new_datas[$i+1][0] = $k;
            $new_datas[$i+1][1] = $v;
            $new_datas[$i+1][2] = 0;
            unset($datas[$k]);
            $i++;
        }
        $this->json(0,'success',$new_datas);
    }

    //获取商品详情
    public function getGoodsInfo(){
        $amcode = input('amcode');
        if(empty($amcode)){
            $this->json(1,'none',[]);
        }
        $where = [];
        $where['frameid'] = $this->admininfo['companyid'];
        $where['amcode'] = $amcode;
        $goods_info = Db::name('materials')->where($where)->find();
        if($goods_info){
            $goods_info['img'] = $this->getImgSrc($goods_info['img']);
            $this->json(0,'success',$goods_info);
        }else{
            $this->json(1,'none',[]);
        }
    }

    //获取商品列表
    public function getGoods(){
        $category = input('category');
        $fine = input('fine');
        if(!$category || !$fine){
            $this->json(1,'none',[]);
        }
        $where = [];
        $cate = Db::name('materials')->where(['frameid'=>$this->admininfo['companyid'],'remarks'=>'公司仓库','auto_add'=>0])->field('category')->group('category')->select();
        $cate = array_column($cate,'category' ,'category');
        foreach($cate as $k=>$v){
            if($v){
                $cate[mb_substr($v , 0 , 2)] = $k;
                unset($cate[$k]);
            }else{
                unset($cate[$k]);
            }
        }
        $where['category'] = $cate[$category];
        $where['fine'] = $fine;
        $where['frameid'] = $this->admininfo['companyid'];
        $where['auto_add'] = 0;
        $where['remarks'] = '公司仓库';
        $goods = Db::name('materials')->where($where)->field('amcode,fine,brand,category,name,img,units,phr,price')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
            $item['img'] = $this->getImgSrc($item['img']);
            return $item;
        });
        $this->json(0,'success',$goods);
    }

    //获取商品列表 - 搜索
    public function searchGoods(){
        $search = input('search');
        if(!$search){
            $this->json(1,'none',[]);
        }
        $where = [];
        $where[] = ['frameid','=',$this->admininfo['companyid']];
        $where[] = ['remarks','=','公司仓库'];
        $where[] = ['auto_add','=','0'];
        $where[] = ['name','like','%'.trim($search).'%'];
        // $where[] = ['brand','like','%'.trim($search).'%'];
        $goods = Db::name('materials')->where($where)->field('amcode,fine,brand,category,name,img,units,phr,price')->paginate(10,false,['query'=>request()->param()])->each(function($item, $key){
            $item['img'] = $this->getImgSrc($item['img']);
            return $item;
        });
        $this->json(0,'success',$goods);
    }

    //下单
    public function placeTheOrder(){
        $cart = input('cart');
        if(empty($cart)){
            $this->json(1,'请选择辅材后再提交');
        }
        $uid = input('uid');
        $amcode = array_keys($cart);
        $materials = array_column(Db::name('materials')->where(['frameid'=>$this->admininfo['companyid'],'amcode'=>$amcode])->select(),null,'amcode');
        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find(); //用户详情
        if($userinfo['status'] > 6){
            $this->json(2,'结算状态禁止下单');
        }
        //领料单具体每个辅材明细
        $picking_material_info = [];
        $total_money = 0;
        foreach($cart as $k=>$v){
            $info = [];
            $info['num'] = $v['num'];
            $info['img'] = $v['img'];
            $info['pmid'] = 0;
            $info['oid'] = $userinfo['oid'];
            $info['userid'] = $uid;
            $info['f_id'] = $this->admininfo['companyid'];
            $info['type_of_work'] = $materials[$k]['category'];
            $info['m_name'] = $materials[$k]['name'];
            $info['price'] = $materials[$k]['price'];
            $info['amcode'] = $materials[$k]['amcode'];
            $info['fine'] = $materials[$k]['fine'];
            $info['brand'] = $materials[$k]['brand'];
            $info['place'] = $materials[$k]['place'];
            $info['img'] = $materials[$k]['img'];
            $info['phr'] = $materials[$k]['phr'];
            $info['remarks'] = $materials[$k]['remarks'];
            $info['category'] = $materials[$k]['category'];
            $info['units'] = $materials[$k]['units'];
            $picking_material_info[] = $info;
            $total_money += ($info['num']*$info['price']);
        }

        // 已领金额
        //********* 这里的status 未审核的要不要算已领金额??? *********
//        $pink_total_money = Db::name('picking_material')->where(['userid'=>$uid,'status'=>[2,3]])->sum('total_money');
        $pink_total_money = Db::name('picking_material')->where(['userid'=>$uid,'status'=>[1,2]])->sum('total_money');
        $pink_total_money += Db::name('picking_material')->where(['userid'=>$uid,'status'=>[3,4]])->sum('actual_total_money');
        $pink_total_money += Db::name('picking_order')->where(['userid'=>$uid,'status'=>[0,2]])->sum('money');
        //领料单数据
        $picking_material = [];
        $picking_material['oid'] = $userinfo['oid'];
        $picking_material['userid'] = $uid;
        $picking_material['adminid'] = $this->admininfo['userid'];
        $picking_material['f_id'] = $this->admininfo['companyid'];
        $picking_material['auditid'] = 0;
        $picking_material['total_money'] = $total_money;
        
        //********这里需要判断 已领金额 到达预算金额的多少后 需要审核
        //获取领料超过多少则需要审核
        $pick_rate = Db::name('cost_tmp')->where(['f_id'=>$this->admininfo['companyid']])->value('pick_rate');
        if(!$pick_rate){
            $pick_rate = 0;
        }
        //获取订单辅材成本总额
        $material_total_money = model('admin/offerlist')->get_material_list($userinfo['oid'],2)['total_money'];
        if($material_total_money * $pick_rate/100 >= ($pink_total_money+$total_money)){
            //未达到金额 不需要审核
            $picking_material['status'] = 2;
        }else{
            // 超过金额 需要审核
            $picking_material['status'] = 1;
        }
        Db::startTrans();
        try {
            $picking_material_id = Db::name('picking_material')->insertGetId($picking_material);
            if($picking_material_id){
                foreach($picking_material_info as $k=>$v){
                    $picking_material_info[$k]['pmid'] = $picking_material_id;
                }
            }
            $picking_material_info = Db::name('picking_material_info')->insertAll($picking_material_info);
            Cache::rm('cart_'.$uid.$this->admininfo['userid']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->json(1,'下单失败');
        }
        $this->json(0,'下单成功');
        
    }

    //获取购物车
    public function getCart(){
        $cart = Cache::get('cart_'.input('uid').$this->admininfo['userid']);
        $cart = $cart?$cart:[];
        $this->json(0,'success',$cart);
    }

    //保存购物车
    public function saveCart(){
        $uid = input('uid');
        $cart = input('cart');
        // var_dump(input());die;
        Cache::set('cart_'.$uid.$this->admininfo['userid'],$cart,86400*7);
        $this->json(0,'success',$cart);
    }

    //缓存保存购物车 (详情页点击加入购物车)
    public function saveCartGoodsOne(){
        $amcode = input('amcode');
        $num = input('num');
        $name = input('name');
        $price = input('price');
        $uid = input('uid');
        $img = input('img');
        $cart = Cache::get('cart_'.$uid.$this->admininfo['userid']);
        if($cart){
            $cart[$amcode]['num'] = $num;
            $cart[$amcode]['name'] = $name;
            $cart[$amcode]['price'] = $price;
            $cart[$amcode]['img'] = $img;
        }else{
            $cart = [];
            $cart[$amcode]['num'] = $num;
            $cart[$amcode]['name'] = $name;
            $cart[$amcode]['price'] = $price;
            $cart[$amcode]['img'] = $img;
        }
        Cache::set('cart_'.$uid.$this->admininfo['userid'],$cart,86400*7);
        $this->json(0,'success',$cart);
    }

    //获取领料详情
    public function getHistoryPicking(){
        $where = [];
        $where['userid'] = input('uid');
        // $where['adminid'] = $this->admininfo['userid'];
        $picking_material = Db::name('picking_material')->where($where)->order('id','asc')->select();
        if(!$picking_material){
            //为空 未领料
            $this->json(2,'none',[]);
        }
        // $picking_material = array_column($picking_material,null ,'id');
        foreach($picking_material as $k=>$v){
            $picking_material[$k]['addtime'] = date('Y-m-d',strtotime($v['addtime']));
            $picking_material[$k]['info'] = Db::name('picking_material_info')->where(['pmid'=>$v['id']])->order('id','asc')->select();
            foreach($picking_material[$k]['info'] as $k1=>$v1){
                $picking_material[$k]['info'][$k1]['total'] = round($v1['price']*$v1['num'],2);
            }
        }
        $this->json(0,'success',$picking_material);
    }

     //提交定点/自购领料
    public function addPickingOrder(){
        $data = [];
        $data['type'] = input('type');
        $data['money'] = input('money');
        $data['remark'] = input('remark');
        $data['userid'] = input('uid');
        $userinfo = Db::name('userlist')->where(['id'=>input('uid')])->find(); //用户详情
        if($userinfo['status'] > 6){
            $this->json(1,'结算状态禁止下单');
        }
        $data['f_id'] = $userinfo['frameid'];
        $data['adminid'] = $this->admininfo['userid'];

        
        $pink_total_money = Db::name('picking_material')->where(['userid'=>input('uid'),'status'=>[1,2]])->sum('total_money');
        $pink_total_money += Db::name('picking_material')->where(['userid'=>input('uid'),'status'=>[3,4]])->sum('actual_total_money');
        $pink_total_money += Db::name('picking_order')->where(['userid'=>input('uid'),'status'=>[0,2]])->sum('money');
        $pick_rate = Db::name('cost_tmp')->where(['f_id'=>$userinfo['frameid']])->value('pick_rate');
        if(!$pick_rate){
            $pick_rate = 80;
        }
        $material_total_money = model('admin/offerlist')->get_material_list($userinfo['oid'],2)['total_money'];
        if($material_total_money * $pick_rate/100 >= ($pink_total_money+input('money'))){
            //未达到金额 不需要审核
            $status['status'] = 2;
        }else{
            // 超过金额 需要审核
            $status['status'] = 0;
        }

        $img = [];
        Db::startTrans();
        try {
            //保存验收记录
            $poid = Db::name('picking_order')->insertGetId($data);
            //保存图片
            if(input('img')){
                foreach(input('img') as $k=>$v){
                    $info = [];
                    $info['img'] = $v;
                    $info['poid'] = $poid;
                    $img[] = $info;
                }
                Db::name('picking_order_img')->insertAll($img);
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


    //获取定点/自购领料列表  type 1:定点 2:自购
    public function getPickingOrderList(){
        $where = [];
        $where['userid'] = input('uid');
        $where['type'] = input('type');
        $picking_order = Db::name('picking_order')->where($where)->order('id','asc')->select();
        if(!$picking_order){
            $this->json(2,'success',[]);
        }
        foreach ($picking_order as $k => $v) {
            $picking_order[$k]['addtime'] = date('Y-m-d',strtotime($v['addtime']));
            $img = Db::name('picking_order_img')->where(['poid'=>$v['id']])->order('id','desc')->select();
            foreach($img as $k1=>$v2){
                $picking_order[$k]['img'][] = $this->getImgSrc($v2['img']);
            }
        }
        $this->json(0,'success',$picking_order);
    }

    //获取订单领料总计  工程总报价,已领金额,已领比率
    public function picking_statistic(){
        $uid = input('uid');
        $userinfo = Db::name('userlist')->where(['id'=>$uid])->find();
        if(!$userinfo || !$userinfo['oid']){
            $this->json(1,'获取用户信息失败');
        }
        $order_info  = model('admin/offerlist')->get_order_info($userinfo['oid'],2);
        $data = [];
        $data['discount_proquant'] = round($order_info['discount_proquant'],2); //工程总报价
        if(!$data['discount_proquant'] || $data['discount_proquant'] == 0){
            $this->json(1,'工程报价有误');
        }
        $data['actual_total_money'] = round(Db::name('picking_material')->where(['oid'=>$userinfo['oid']])->sum('actual_total_money'),2);
        $data['actual_total_money'] += round(Db::name('picking_order')->where(['userid'=>$userinfo['id']])->sum('money'),2);
        $data['pinking_rate'] = round($data['actual_total_money'] / $data['discount_proquant'] *100,2);
        $this->json(0,'success',$data);
    }
	//退料
	public function sales()
	{
		$user= Db::name('userlist')->where(['id'=>input('uid')])->find();
		$fdz_picking_material_info=Db::table('fdz_picking_material_info')->where('userid',$user['id'])->select();
		if(empty($fdz_picking_material_info)){
			  $this->json(1,'该客户无领料记录');
		}
		$newma=[];
		foreach($fdz_picking_material_info as $k=>$v){
			$newma[]=$v['amcode'];				
		}
		$newma=array_unique($newma);
		foreach($newma as $k1=>$v1){
			$material_info[]=Db::table('fdz_picking_material_info')->where('amcode',$v1)->find();
		}

        foreach($material_info as $k3=>$v3){
            $material_info[$k3]['actual_num'] = Db::table('fdz_picking_material_info')->where(['userid'=>input('uid')])->where('amcode',$v3['amcode'])->sum('actual_num')- Db::table('fdz_sales')->where(['userid'=>input('uid')])->where('amcode',$v3['amcode'])->sum('num');
        }
		foreach($material_info as $k2=>$v2){
		 $material_info[$k2]['img'] = $this->getImgSrc($v2['img']);
		 $material_info[$k2]['num'] = '';
		}
		$this->json(0,'查询领料记录成功',$material_info);
	}
	
	
	
	public function salesover()
	{
		$data=input('data');
        $userinfo = Db::name('userlist')->where(['id'=>input('uid')])->find(); //用户详情
        if(!$userinfo){
            $this->json(2,'参数错误');
        }
        if($userinfo['status'] > 6){
            $this->json(2,'结算状态禁止退料');
        }
		$user=$this->admininfo;
		if(!input('totalPrice')||input('totalPrice')==0){
			  $this->json(1,'请输入退料数量');
		}
		$req=Db::name('sales_record')->insertGetId(['cid'=>$user['userid'],'fid'=>$user['companyid'],
            'time'=>date('y-m-d H:i:s', time()),
            'money'=>input('totalPrice'),
            'uid'=>input('uid')
        ]);

		foreach($data as $k=>$v)
		{
			unset($data[$k]['id']);
			unset($data[$k]['pmid']);
			unset($data[$k]['oid']);
			unset($data[$k]['actual_num']);
			$data[$k]['tid']=$req;
			$data[$k]['userid']=input('uid');
			if($v['num']==''){
			    unset($data[$k]);
            }
		}
		$res=	Db::name('sales')->insertAll($data);
		if($res){
				$this->json(0,'退料成功',$res);
		}else{
			$this->json(1,'退料失败');
		}
			
	}
	
	public function salelist()
	{
		$user=$this->admininfo;
		$user=Userlist::with(['sale','user'])->where(['frameid'=>$user['companyid'],])->where('oid','>',0)->all();
		//去掉没有领料记录的,还有已结算的未写
		foreach($user as $k=>$v){
            if (count($v['sale'])==0) {
               unset($user[$k]);
            }
            if($v['status']==7){
                unset($user[$k]);
            }
            if($v['status']==8){
                unset($user[$k]);
            }
        }

        $this->json(0,'success',$user);

	}

	 public function salehistory()
     {
        $salehistory=Salesrecord::with('show')->where('uid',input('uid'))->select();
         if(count($salehistory)==0){
             $this->json(1,'error');
         }
        foreach ($salehistory as $k=>$v)
        {
            $salehistory[$k]['time'] = date('Y-m-d H:i', strtotime($v['time']));
        }

         $this->json(0,'success',$salehistory);
     }

}