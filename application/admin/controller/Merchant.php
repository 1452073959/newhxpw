<?php

// +----------------------------------------------------------------------
// | 后台菜单管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\Goods;
use app\admin\model\Language;
use app\admin\model\Product;
use app\admin\model\Trade;
use app\common\controller\Adminbase;
use think\Db;

class Merchant extends Adminbase
{
	protected function initialize()
    {
        parent::initialize();
        $this->AdminGoods = new Goods;
        $this->Language = new Language;
        $this->Product = new Product;
        $this->Trade = new Trade;
    }
	public function goods_list()
    {	
    	$where = array();
    	$gname = input('g_name');
    	if ($gname != '') {
    		$where['g_name'] = $gname;
    	}
    	$id = input('id');
    	if ($id != '') {
    		$where['id'] = $id;
    	}
    	$type = input('type');
    	if ($type != '') {
    		$where['type'] = $type;
    	}
    	$trial = input('trial');
    	if ($trial != '') {
    		$where['trial'] = $trial;
    	}
    	$tr_id = input('tr_id');
    	if ($tr_id != '') {
    		$where['tr_id'] = $tr_id;
    	}
    	$la_id = input('la_id');
    	if ($la_id != '') {
    		$where['la_id'] = $la_id;
    	}
    	$sname = input('name');
    	$map   = array();
    	$maps   = array();
    	if ($sname != '') {
    		 $map['name'] = array('like', '%' .$sname . '%');
    		 $maps['name'] = $sname;
    	}
    	$shelf_status = input('shelf_status');
    	if ($shelf_status != '') {
    		$where['shelf_status'] = $shelf_status;
    	}
    	$audit_status = input('audit_status');
    	if ($audit_status != '') {
    		$where['audit_status'] = $audit_status;
    	}
    	$data = $this->AdminGoods->goods_list($where,'5','id desc','0',$map);
    	foreach($data as $key=>$value){
    		if ($value['type'] == 1) {
    			$data[$key]['type'] = '成品';
    		}else if ($value['type'] == 2) {
    			$data[$key]['type'] = '定制';
    		}
    		if ($value['shelf_status'] == 1) {
    			$data[$key]['shelf_status'] = '展示中';
    		}else if ($value['shelf_status'] == 2) {
    			$data[$key]['shelf_status'] = '已下架';
    		}
    		if ($value['trial'] == 1) {
    			$data[$key]['trial'] = '可试用';
    		}else if ($value['trial'] == 2) {
    			$data[$key]['trial'] = '不可使用';
    		}
    		if ($value['audit_status'] == 1) {
    			$data[$key]['audit_status'] = '审核通过';
    		}else if ($value['audit_status'] == 2) {
    			$data[$key]['audit_status'] = '拒绝通过';
    		}
    		$data[$key]['class'] = $data[$key]['type'].':　接口类型：'.$data[$key]['ln_id'].'　语言类型：'.$data[$key]['la_id'].'　行业：'.$data[$key]['tr_id'];
    	}
    	$this->assign('language',$this->Language->language_list());
    	$this->assign('lnterface',$this->Product->Interface_list());
    	$this->assign('trade',$this->Trade->trade_list());
    	$this->assign('data',$data);
    	$this->assign('where',$where);
    	$this->assign('map',$maps);
    	$this->assign('show_page', $this->AdminGoods->page_info->render());
        return $this->fetch();
    }


    //产品下架
    public function goods_lockup()
    {
    	$id     = input('id');
    	if (!empty($id)) {
    		$where = array();
            $where['id'] = array('in', $id);
            $id_array = ex_array($id);
            if ($this->AdminGoods->goods_lockup($where,$id_array)) {
            	$info = array('status'=>1,'info'=>'下架成功');
            	$this->ajaxReturn($info);
            } else {
            	$info = array('status'=>2,'info'=>'下架失败');
            	$this->ajaxReturn($info);
            }
    	}
    }
}