<?php
/**
 * Created by PhpStorm.
 * User: Jeffery
 * Date: 2019/1/7
 * Time: 18:34
 * 定制(人工定制,智能定制)
 */
namespace app\admin\Controller;
use app\admin\model\AuthGroup;
use app\admin\model\AuthRule;
use app\common\controller\Adminbase;
use think\Db;
class Customized extends Adminbase
{
    # 功能逻辑图
    public function fun_logic_diagram()
    {
        $list = array();
        $this->assign('_list', $list);
        return $this->fetch();
    }

    # 智能定制模块

}
