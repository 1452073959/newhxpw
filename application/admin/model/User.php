<?php

// +----------------------------------------------------------------------
// | 后台菜单模型
// +----------------------------------------------------------------------
namespace app\admin\model;

use \think\Db;
use \think\Model;

class User extends Model
{
    // 用户列表
    public function userList($map = [],$type = null)
    {
        $num  = 10;
        if (!empty($map['num'])) {
            $num = $map['num'];
            unset($map['num']);
        }
        if ($type == 1) {
            $map[] = ['partner','eq',1];
        }else{
            $map[] = ['partner','eq',0];
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
    // 修改用户信息
    public function userSave($ids,$map)
    {
        if (!empty($ids) && !empty($map)) {
            Db::name('user')->where('id','in',$ids)->update($map);
            return true;
        }
        return false;
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
    // 新增用户、合伙人
    public function userAdd($data)
    {
        if (Db::name('user')->where(['phone'=>$data['phone']])->find()) {
            return false;
        }
        if (!empty($data['password'])) {
            $password         = encrypt_password($data['password']);
            $data['encrypt']  = $password['encrypt'];
            $data['password'] = $password['password'];
        }else{
            unset($data['password']);
        }
        $re = Db::name('user')->insert($data);
        if ($re) {
            return true;
        }else{
            return false;
        }
    }
    // 等级列表
    public function gradeList()
    {
        $data = Db::name('partner_grade')->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['time'] = date('Y-m-d H:i:s',$v['time']);
                $data[$k]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_userinfo" lay-event="edit" title="编辑" data-id="'.$v['id'].'"></button><button class="layui-btn layui-btn-xs check_delete" lay-event="del" title="删除" data-id="'.$v['id'].'"></button></div></div>';
            }
            return $data;
        }
        return false;
    }
    // 等级新增、编辑
    // 技术架构新增、修改
    public function gradeSave($data)
    {
        $data['time'] = time();
        if (!empty($data['id'])) {
            Db::name('partner_grade')->update($data);
            return true;
        }else{
            Db::name('partner_grade')->insert($data);
            return true;
        }
        return false;
    }
    //删除等级
    public function gradeDel($id)
    {
        if (!empty($id)) {
            $re = Db::name('partner_grade')->delete($id);
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
