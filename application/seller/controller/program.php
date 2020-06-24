<?php

// +----------------------------------------------------------------------
// | 小程序配置
// +----------------------------------------------------------------------
namespace app\seller\controller;

use app\common\controller\Sellerbase;
use think\Db;

class Program extends Sellerbase
{
    public $AppID;
    public $AppSecret;
    protected function initialize()
    {
        parent::initialize();
        $this->set = Db::name('set')->find();
        $this->AppID     = $this->set['appid'];
        $this->AppSecret = $this->set['appsecret'];
    }

    //配置首页
    public function set()
    {
        if (Request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            $re = Db::name('set')->update($data);
            if ($re) {
                $info['status'] = 1;
                $info['info']   = '修改成功';
            }else{
                $info['status'] = 0;
                $info['info']   = '修改失败';
            }
            $this->ajaxReturn($info);
        }
        $data = $this->set;
        $data['time'] = date('Y-m-d H:i:s',$data['time']);
        $this->assign('data',$data);
        return $this->fetch('index');
    }

}
