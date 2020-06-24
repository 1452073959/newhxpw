<?php

// +----------------------------------------------------------------------
// | 司机管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Contract extends Adminbase
{
    // protected function initialize()
    // {
    //     parent::initialize();
    //     $this->Menu = new Menu_Model;
    // }

    //
    public function index(){
    
        $res = Db::name('contract')->order('addtime desc')->select();

        $this->assign('data',$res);    
        // dump($res);

        return $this->fetch();
    }

 

    //添加
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();   
       
              $result = $this->validate($data, 'Contract.adds');

            if ($result !== true) {
                return $this->error($result);
            }
            $data['other'] = stripslashes($data['other']);

            // dump($data);exit;

            // if(empty($data['name'])) {
            //     $this->error('车辆不能为空');             
            // }elseif($data['sort']<0) {
            //     $this->error('排序数字不合法');        
            // }
 
            if (Db::name('contract')->insert($data)) {
                $this->success("添加成功！", url("Contract/index"));
            } else {
                $error = Db::name('vehicle_type')->getError();
                $this->error($error ? $error : '添加失败！');
            }
        } else {

            return $this->fetch();
        }
    }

    //编辑后台菜单
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            // dump($data);exit;
              $result = $this->validate($data, 'Contract.adds');
            if ($result !== true) {
                return $this->error($result);
            }
            $data['other'] = htmlspecialchars_decode($data['other']);
                // $eui['name'] = $data['name'];
                // $eui['sort'] = $data['sort'];
                // $eui['remarks'] = $data['remarks'];
                // $eui['load'] = $data['load'];
                // $eui['lwh'] = $data['lwh'];
                // $eui['volume'] = $data['volume'];
                // $eui['status'] = $data['status'];
                // $eui['start_money'] = $data['start_money'];
                // $eui['extra_mileage'] = $data['extra_mileage'];
            // dump($data);exit;

            if (Db::name('contract')->where('id', $data['id'])->update($data)) {
                $this->success("编辑成功！", url("Contract/index"));
            } else {
                $error = Db::name('contract')->getError();
                $this->error($error ? $error : '编辑失败！');
            }
        } else {
            $request = request();
            $id = $request->param('id');
            $rs = Db::name('contract')->where(["id" => $id])->find();
            // dump($rs);
            $this->assign("data", $rs);
            return $this->fetch();
        }

    }

    /**
     * 删除
     */
    // public function delete()
    // {
    //     $id = $this->request->param('id/d');
    //     if (empty($id)) {
    //         $this->error('ID错误');
    //     }

    //    // echo $id;
    //     if (Db::name('vehicle_type')->where(["vehicle_type_id" => $id])->delete()) {
    //         $this->success("删除成功", url("Contract/index"));
    //     } else {
    //         $this->error("删除失败！");
    //     }
    // }
    // 

         //设置状态
    // public function setstate($id, $status)
    // {
    //     $id = (int) input('id/d');
    //     $status = (int) input('status/d');


    //     if (($status != 0 && $status != 1) || !is_numeric($id) || $id < 0) {
    //         return '参数错误';
    //     }elseif ($status==0) {
    //        $status=1;
    //     }elseif ($status==1) {
    //        $status=0;
    //     }


    //     if (Db::name('driver')->where('id', $id)->update(['status' => $status])) {
    //         $this->error('更新成功');
    //     } else {
    //         $this->error('更新失败');
    //     }
    // }

}
