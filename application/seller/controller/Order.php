<?php

// +----------------------------------------------------------------------
// | 后台菜单管理
// +----------------------------------------------------------------------
namespace app\seller\controller;

use app\seller\model\Order as AdminOrder;
use app\common\controller\Sellerbase;
class Order extends Sellerbase
{

	protected function initialize()
    {
        parent::initialize();
        $this->AdminOrder = new AdminOrder;
    }

    // 订单列表
	public function order_list()
    {
    	if (Request()->isPost()) {
            $where = array();
            $page  = $_POST['limit'];
            if(!empty($_POST['word'])){
                if(preg_match("/^\d*$/",$_POST['word'])){
                    $where[] = ['phone','LIKE',"%{$_POST['word']}%"];
                }else{
                    $where[] = ['name','LIKE',"%{$_POST['word']}%"];
                }
            }
            if(!empty($_POST['oid'])){
                $where[] = ['oid','eq',$_POST['oid']];
            }
            if(!empty($_POST['order_status'])){
                $where[] = ['order_status','=',$_POST['order_status']];
            }
            if(!empty(input('search_date_start')) && !empty(input('search_date_end'))){
                $StarTtime = strtotime(input('search_date_start'));
                $EndTime   = strtotime(input('search_date_end'));
                $where[] = ['add_time','between',[$StarTtime,$EndTime]];
            }
    		$list = $this->AdminOrder->order_list($where,$page);
            foreach($list['data'] as $key=>$value){
                switch ($value['order_status']) {
                  case '0':
                    $list['data'][$key]['order_status'] = '已取消';
                    break;
                  case '1':
                    $list['data'][$key]['order_status'] = '已付款';
                    break;
                  case '2':
                    $list['data'][$key]['order_status'] = '待付款';
                    break;
                  case '3':
                    $list['data'][$key]['order_status'] = '待交付';
                    break;
                  case '4':
                    $list['data'][$key]['order_status'] = '第一期';
                    break;
                  case '5':
                    $list['data'][$key]['order_status'] = '第二期';
                    break;
                  case '6':
                    $list['data'][$key]['order_status'] = '第三期';
                    break;
                  case '7':
                    $list['data'][$key]['order_status'] = '第四期';
                    break;
                  case '10':
                    $list['data'][$key]['order_status'] = '已完成';
                    break;
                }
                 $list['data'][$key]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_orderinfo" title="查看" data-id="'.$value['id'].'"></button><button class="layui-btn layui-btn-xs check_delete" title="删除" data-id="'.$value['id'].'"></button></div></div>';
            }
            $info['code'] = 0;
            $info['msg'] = '';
            $info['count'] = $list['total'];
            $info['data'] = $list['data'];
			$this->ajaxReturn($info);
    	}
        return $this->fetch('order_list');
    }

    //订单详情
    public function order_info()
    {
        return $this->fetch();
    }
}