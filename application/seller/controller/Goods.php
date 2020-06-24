<?php

// +----------------------------------------------------------------------
// | 后台菜单管理
// +----------------------------------------------------------------------
namespace app\seller\controller;

use app\seller\model\Goods as AdminGoods;
use app\common\controller\Sellerbase;
use think\Db;
use think\Request;
class Goods extends Sellerbase
{

	protected function initialize()
    {
        parent::initialize();
        $this->AdminGoods = new AdminGoods;
    }

	public function goods_add(Request $request)
    {
    	if ($this->request->isPost()) {
    	    $data = $this->request->post('');
    		if (array_key_exists('descr', $_POST)) {
    		 	$data['descr'] = $_POST['descr'];
    		}else{
    			$data['descr'] = "";
    		}
    		$id = $this->AdminGoods->goods_add($data);
			if ($id) {
	            $info['status'] = 1;
                $info['info']   = "添加成功";
                $info['id']     = $id;
                $this->ajaxReturn($info);
			}else{
				$info['status'] = 2;
				$info['info']   = $this->AdminGoods->getError() ?: '添加失败！';
				$this->ajaxReturn($info);
			}
    	}else{
    		return $this->fetch();
    	}
    }

    public function upload(Request $request)
    {
    	$file = $request->file('file');
    	if(!empty($file)){
            $info     = $file->move( config('app.save_path') );
            $fileName = $info->getSaveName();
            $pic['pic']      = config('app.disp_path').$fileName;
            $id = $this->AdminGoods->goods_pic($pic);
            if ($id) {
				$data['code']   = 1;
				$data['id']     = $id;
                $data['info']   = "添加成功";
                $this->ajaxReturn($data);
			}else{
				$info['code']   = 0;
				$info['info']   = $this->AdminGoods->getError() ?: '添加失败！';
				$this->ajaxReturn($info);
			}
        }else{
        	$data['status'] = 2;
            $data['info']   = "请上传图片";
            $this->ajaxReturn($data);
        }
    }
    // 技术架构
    public function goods_language()
    {
        if (Request()->isPost()) {
            $num  = input('limit');
            $data = model('goods')->language_list($num);
            $info['code']  = 0;
            $info['count'] = $data['total'];
            $info['data']  = $data['data'];
            $this->ajaxReturn($info);
        }
        return $this->fetch();
    }
    // 架构编辑、新增
    public function language_edit()
    {
        $data = [
            'id'           => input('id'),
            'language_name'=> input('language_name'),
            'multiple'     => input('multiple'),
        ];
        $data = array_filter($data);
        if (empty($data['language_name']) || empty($data['multiple'])) {
            $info['status'] = 2;
            $info['info']   = '缺少参数';
            $this->ajaxReturn($info);
        }
        $re = model('goods')->language_save($data);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '操作成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '操作失败';
        }
        $this->ajaxReturn($info);
    }
    // 架构删除
    public function language_del()
    {
        $id = input('id');
        if (empty($id)) {
            $info['status'] = 2;
            $info['info']   = '缺少参数';
            $this->ajaxReturn($info);
        }
        $re = model('goods')->language_del($id);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '删除成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '删除失败';
        }
        $this->ajaxReturn($info);
    }
    // 产品分类
    public function goods_type()
    {
        if (Request()->isPost()) {
            $num  = input('limit');
            $data = model('goods')->type_list($num);
            $info['code']  = 0;
            $info['count'] = $data['total'];
            $info['data']  = $data['data'];
            $this->ajaxReturn($info);
        }
        return $this->fetch();
    }
    // 产品分类编辑、新增
    public function type_edit()
    {
        $data = [
            'id'           => input('id'),
            'trade_name'   => input('trade_name'),
        ];
        $data = array_filter($data);
        if (empty($data['trade_name'])) {
            $info['status'] = 2;
            $info['info']   = '缺少参数';
            $this->ajaxReturn($info);
        }
        $re = model('goods')->type_save($data);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '操作成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '操作失败';
        }
        $this->ajaxReturn($info);
    }
    // 产品分类删除
    public function type_del()
    {
        $id = input('id');
        if (empty($id)) {
            $info['status'] = 2;
            $info['info']   = '缺少参数';
            $this->ajaxReturn($info);
        }
        $re = model('goods')->type_del($id);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '删除成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '删除失败';
        }
        $this->ajaxReturn($info);
    }
    // 应用类型
    public function goods_style()
    {
        if (Request()->isPost()) {
            $num  = input('limit');
            $data = model('goods')->style_list($num);
            $info['code']  = 0;
            $info['count'] = $data['total'];
            $info['data']  = $data['data'];
            $this->ajaxReturn($info);
        }
        return $this->fetch();
    }
    // 应用类型编辑、新增
    public function style_edit()
    {
        $data = [
            'id'            => input('id'),
            'interface_name'=> input('interface_name'),
        ];
        $data = array_filter($data);
        if (empty($data['interface_name'])) {
            $info['status'] = 2;
            $info['info']   = '缺少参数';
            $this->ajaxReturn($info);
        }
        $re = model('goods')->style_save($data);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '操作成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '操作失败';
        }
        $this->ajaxReturn($info);
    }
    // 应用类型删除
    public function style_del()
    {
        $id = input('id');
        if (empty($id)) {
            $info['status'] = 2;
            $info['info']   = '缺少参数';
            $this->ajaxReturn($info);
        }
        $re = model('goods')->style_del($id);
        if ($re) {
            $info['status'] = 1;
            $info['info']   = '删除成功';
        }else{
            $info['status'] = 2;
            $info['info']   = '删除失败';
        }
        $this->ajaxReturn($info);
    }
}