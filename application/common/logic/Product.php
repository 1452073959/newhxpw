<?php
/**
 * Created by PhpStorm.
 * User: Win10
 * Date: 2019/1/8
 * Time: 17:13
 */
namespace app\common\logic;
use think\Db;
use think\Model;
class Product extends Model{

    # 车型分类
    public function get_list_vehicle_type($order='sort asc')
    {
        return model('VehicleType')->order($order)->select();
    }

    # 产品列表
    public function get_list_product($field="*",$w=array(),$order='',$limit='')
    {
        return $this->field($field)->where($w)->order($order)->limit($limit)->select();
    }

    # 产品列表单条记录
    public function get_info_product($field="*",$w=array())
    {
        return $this->field($field)->where($w)->find();
    }

    # 城市默认价格
    public function get_defawprice()
    {
        return array(
            'web_dafult_starting_price'=>$this->get_dbconfig('web_dafult_starting_price')['value'],
            'web_dafult_starting_kil'=>$this->get_dbconfig('web_dafult_starting_kil')['value'],
            'web_dafult_gobeyong_price'=>$this->get_dbconfig('web_dafult_gobeyong_price')['value'],
            'web_dafult_gobeyong_kil'=>$this->get_dbconfig('web_dafult_gobeyong_kil')['value'],
        );
    }

    # 獲取配置信息
    public function get_dbconfig($name = '')
    {
        return Db::name('config')->where(array('name'=>$name))->find();
    }

    # 价格明细
    ## kil = 默认1公里
    public function get_procuct_price($city,$product_id,$kil=1)
    {
        $w = [
            ['product_id','=',$product_id]
        ];
        $wo = [
            ['city_id','=',$city],
            ['city','like','%'.$city.'%']
        ];
        $info = model('ProductLevPrice')->where($w)->where($wo)->find();
        if($info)
        {
            $info['is_default'] = 0;
        }
        if(!$info)
        {
            $get_defawprice = $this->get_defawprice();
            $info['is_default'] = 1;
            $info['starting_price'] = $get_defawprice['web_dafult_starting_price'];
            $info['starting_kil'] = $get_defawprice['web_dafult_starting_kil'];
            $info['gobeyong_price'] = $get_defawprice['web_dafult_gobeyong_price'];
            $info['gobeyong_kil'] = $get_defawprice['web_dafult_gobeyong_kil'];
        }
        if ($kil <= $info['starting_kil'])
        {
            $pay_money = $info['starting_price'];
        }else{
            $pay_money = $info['gobeyong_price'] * $kil;
        }
        $info['pay_money'] = $pay_money;
        return $info;
    }

    # 提交

}