<?php
// +----------------------------------------------------------------------
// | 后台订单管理
// +----------------------------------------------------------------------
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Session;
class Orderoperation extends Model
{
    //添加订单操作纪录
    public function add($data)
    {
        $result = Db::name('order_operation')->strict(false)->insertGetId($data);
        if($result){
            return $result;
        }else{
            return false;
        }
    }

    //获取订单操作记录
    public function info()
    {
        $info = Db::name('order_operation')->select();
        if ($info) {
            foreach($info as $k=>$v){
                $info[$k]['operation_time'] = date('Y-m-d H:i:s',$v['operation_time']);
            }
            return $info;
        } else {
            return false;
        }
    }
}
