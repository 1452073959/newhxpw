<?php

// +----------------------------------------------------------------------
// | 后台菜单模型
// +----------------------------------------------------------------------
namespace app\seller\model;

use \think\Db;
use \think\Model;

class User extends Model
{
    // 用户列表
    public function userList($map = [])
    {
        $num  = 10;
        if (!empty($map['num'])) {
            $num = $map['num'];
            unset($map['num']);
        }
        $data = Db::name('user')->where($map)->paginate($num)->toArray();
        if (!empty($data)) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                if ($data['data'][$k]['partner'] == 1) {
                    $data['data'][$k]['type'] = '合伙人';
                }else{
                    $data['data'][$k]['type'] = '普通会员';
                }
                $data['data'][$k]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_userinfo" title="查看" data-id="'.$v['id'].'"></button></button><button class="layui-btn layui-btn-xs check_delete" title="删除" data-id="'.$v['id'].'"></button></div></div>';
            }
            return $data;
        }
        return false;
    }
    // 用户详细信息
    public function userOne($id)
    {
        if (!empty($id)) {
            $data = Db::name('user')->where(['id'=>$id])->find();
            if (!empty($data)) {
                $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
                if ($data['partner'] == 1) {
                    $data['type'] = '合伙人';
                }else{
                    $data['type'] = '普通会员';
                }
                return $data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    //删除用户
    public function userDel($id)
    {
        if (!empty($id)) {
            $re = Db::name('user')->delete($id);
            if ($re) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
