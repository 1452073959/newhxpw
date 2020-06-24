<?php

// +----------------------------------------------------------------------
// | 后台菜单管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\Order as AdminOrder;
use app\common\controller\Adminbase;
class Order extends Adminbase
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
                  case '-1':
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
    {   if(Request()->isPost()){
            $data['oid']     = input('id');
            $data['beizhu']  = input('beizhu');
            $data['status']  = input('status');
            $data['code']    = input('code');
            $data['operation_time'] = time();
            $data['admin_name']     = Session('admin_user_auth')['username'];
            $data['aid']            = Session('admin_user_auth')['uid'];
            $res = model('Orderoperation')->add($data);
            if($res){
                $da['id']           = $data['oid'];
                $da['order_status'] = $data['status'];
                $re = model('Order')->edit($da);
                if ($re) {
                    $result = array('status'=>1,'info'=>'设置成功');
                } else {
                    $result = array('status'=>2,'info'=>'设置失败');
                }
                $this->ajaxReturn($result);
            }
        }
        if(!empty($_GET['id'])){
            $where[] = ['id','eq',$_GET['id']];
            $list = $this->AdminOrder->order_list($where,'','','');
            $info = $list['data'][0];
            switch ($info['order_status']) {
                      case '-1':
                        $info['order_status'] = '已取消';
                        break;
                      case '1':
                        $info['order_status'] = '已付款';
                        break;
                      case '2':
                        $info['order_status'] = '待付款';
                        break;
                      case '3':
                        $info['order_status'] = '待交付';
                        break;
                      case '4':
                        $info['order_status'] = '第一期';
                        break;
                      case '5':
                        $info['order_status'] = '第二期';
                        break;
                      case '6':
                        $info['order_status'] = '第三期';
                        break;
                      case '7':
                        $info['order_status'] = '第四期';
                        break;
                      case '10':
                        $info['order_status'] = '已完成';
                        break;
                    }
            $map[] = ['id','eq',$info['gid']];
            $goods = model('Goods')->goods_list($map,'1','','');
            $user = model('User')->userOne($info['uid']);
            $this->assign('goods',$goods);
            $this->assign('operation',model('Orderoperation')->info());
            $this->assign('user',$user);
            $this->assign('info',$info);
        }
        
        return $this->fetch();
    }

    //订单合同信息
    public function compact()
    {
        if (Request()->isPost()) {
            $data = input('');
            if ($data) {
                $data['compact_time']    = strtotime($data['compact_time']);
                $data['compact_paytime'] = strtotime($data['compact_paytime']);
                $res  = model('Order')->edit($data);
                $da   = array('status'=>1,'info'=>'操作成功');
                $this->ajaxReturn($da);
            }
            $file = Request()->file('file');
            if (!empty($file)) {
                $files     = $file->move( config('app.save_path') );
                $fileName = $files->getSaveName();
                $pic['contract_pic'] = config('app.disp_path').$fileName;
                $pic['id']           = input('id');
                $where[] = ['id','eq',$pic['id']];
                $res1   =  model('Order')->order_list($where,'1','','');
                if ($res1['data'][0]['contract_pic'] != '') {
                    $path = config('app.app_path').$res1['data'][0]['contract_pic'];
                    if(file_exists($path)){
                        unlink($path);
                    }
                }
                $result = model('Order')->edit($pic);
                if ($result) {
                    $re['code']   = 1;
                    $re['info']   = "添加成功";
                    $this->ajaxReturn($re);
                } else {
                    $re['code']   = 0;
                    $re['info']   ='添加失败';
                    $this->ajaxReturn($re);
                }
            }
        }    
        $where = input('');
        $info = model('Order')->compact($where);
        $this->assign('info',$info);
        $this->assign('id',$where);
        return $this->fetch();
    }

    //项目进展确认
    public function addstate1()
    {
        $data = input('');
        if ($data['id']) {
            switch ($data['evolve']) {
              case '1':
                $data['evolve1'] = '1';
                break;
              case '2':
                $data['evolve2'] = '1';
                break;
              case '3':
                $data['evolve3'] = '1';
                break;
              case '4':
                $data['evolve4'] = '1';
                break;
              case '5':
                $data['evolve5'] = '1';
                break;
            }
            $info = model('Order')->edit($data);
            if ($info) {
                $res = array('status'=>1,'info'=>'操作成功');
            } else {
                $res = array('status'=>2,'info'=>'操作失败');
            }
            $this->ajaxReturn($res);

        }
    }

    //项目进展取消
    public function delstate1()
    {
        $data = input('');
        if ($data['id']) {
            switch ($data['evolve']) {
              case '1':
                $data['evolve1'] = '0';
                break;
              case '2':
                $data['evolve2'] = '0';
                break;
              case '3':
                $data['evolve3'] = '0';
                break;
              case '4':
                $data['evolve4'] = '0';
                break;
              case '5':
                $data['evolve5'] = '0';
                break;
            }
            $info = model('Order')->edit($data);
            if ($info) {
                $res = array('status'=>1,'info'=>'操作成功');
            } else {
                $res = array('status'=>2,'info'=>'操作失败');
            }
            $this->ajaxReturn($res);

        }
    }
}