<?php

// +----------------------------------------------------------------------
// | 后台菜单模型
// +----------------------------------------------------------------------
namespace app\seller\model;

use \think\Db;
use \think\Model;

class Course extends Model
{
    // 用户列表
    public function courseList($map = [])
    {
        $num  = 10;
        if (!empty($map['num'])) {
            $num = $map['num'];
            unset($map['num']);
        }
        $data = Db::name('course')->where($map)->paginate($num)->toArray();
        if (!empty($data)) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['time']   = date('Y-m-d H:i:s',$v['time']);
                $data['data'][$k]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_userinfo" title="查看" data-id="'.$v['id'].'"></button></button><button class="layui-btn layui-btn-xs check_delete" title="删除" data-id="'.$v['id'].'"></button></div></div>';
            }
            return $data;
        }
        return false;
    }
}
