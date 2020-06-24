<?php
// +----------------------------------------------------------------------
// | 后台产品管理
// +----------------------------------------------------------------------
namespace app\seller\model;
use think\Model;
use think\Db;
use think\Session;
class Order extends Model
{
    //获取订单列表
    public function order_list($where, $page, $order = 'id desc', $limit = '0')
    {
        $list = $this->where($where)->order($order)->paginate($page)->toArray();
        foreach($list['data'] as $key=>$value){
            $list['data'][$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $sname = Db::name('shop')->field('name')->where('id',$value['sid'])->find();
            $list['data'][$key]['sname'] = $sname['name'];
        }
        // dump($where);die;
        return $list;
    }
}
