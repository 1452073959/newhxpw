<?php
// +----------------------------------------------------------------------
// | 后台产品管理
// +----------------------------------------------------------------------
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Session;
class Product extends Model
{
    //获取产品接口
    public function Interface_list($where = '')
    {
        $data = $this->where($where)->select();
        if ($data) {
            return $data;
        }
        $this->error = '入库失败！';
        return false;
    }
}