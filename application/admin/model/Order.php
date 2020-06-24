<?php
// +----------------------------------------------------------------------
// | 后台订单管理
// +----------------------------------------------------------------------
namespace app\admin\model;
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

    //更新订单列表
    public function edit($data)
    {
        $res = $this->allowField(true)->isUpdate(true, ['id' => $data['id']])->save($data);
        if ($res) {
            return $res;
        }else{
            return false;
        }
    }

    //获取合同信息
    public function compact($where)
    {
        $field = 'compact_name,compact_phone,compact_money,compact_time,compact_paytime,phase1,phase2,phase3,contract_pic';
        $info  = $this->field($field)->where($where)->find();
        if ($info['compact_time'] != '') {
            $info['compact_time']    = date('Y-m-d',$info['compact_time']);
        }
        if ($info['compact_paytime'] != '') {
            $info['compact_paytime'] = date('Y-m-d',$info['compact_paytime']);
        }
        if ($info) {
            return $info;
        } else {
            return false;
        }
    }
}
