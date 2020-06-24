<?php
// +----------------------------------------------------------------------
// | 后台订单管理
// +----------------------------------------------------------------------
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Session;
class Artificial extends Model
{
    public function getmaterial_info($id){
        $list = Db::name('order_material')->where(['o_id'=>$id,'status'=>1])->select();//该订单全部辅料
        $material_list = [];//辅材
        foreach($list as $k=>$v){
            if(!isset($material_list[$v['type_of_work']][$v['amcode']])){
                $material_list[$v['type_of_work']][$v['amcode']]['num'] = 0;
                $material_list[$v['type_of_work']][$v['amcode']]['cb'] = $v['cb'];
                $material_list[$v['type_of_work']][$v['amcode']]['price'] = $v['price'];
                $material_list[$v['type_of_work']][$v['amcode']]['coefficient'] = $v['coefficient'];
                $material_list[$v['type_of_work']][$v['amcode']]['important'] = $v['important'];
            }
            $material_list[$v['type_of_work']][$v['amcode']]['num'] += $v['num'];
        }
        //整理系数 获取真正的数量
        foreach($material_list as $k1=>$v1){
            foreach($v1 as $k2=>$v2){
                $num = explode('.',$v2['num']);
                if(!isset($num[1])){
                    $num[1] = 0;
                }
                if($num[1]*10 > $v2['coefficient']){
                   $material_list[$k1][$k2]['omit_num'] = ceil($v2['num']);
                }else{
                    //不足1时向上取证
                    if($v2['num'] < 1 && $v2['important']){
                       $material_list[$k1][$k2]['omit_num'] = ceil($v2['num']);
                    }else{
                       $material_list[$k1][$k2]['omit_num'] = floor($v2['num']);
                    }
                }
            }
        }
        return $material_list;
    }
}
