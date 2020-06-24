<?php

// +----------------------------------------------------------------------
// | 司机管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Driver extends Adminbase
{
    // protected function initialize()
    // {
    //     parent::initialize();
    //     $this->Menu = new Menu_Model;
    // }

    //司机
    public function index()
    {
        $res = Db::name('driver')->paginate(1);

        
        $count = count(Db::name('driver')->select());

         $urls = ROOT_URL.'public/uploads';
             $this->assign('urls',$urls);   
        $this->assign('data',$res);    
        // echo ($count);
        $this->assign('count',$count);    
        return $this->fetch();
    }

       //设置状态
    public function setstate($id, $status)
    {
        $id = (int) input('id/d');
        $status = (int) input('status/d');


        if (($status != 0 && $status != 1) || !is_numeric($id) || $id < 0) {
            return '参数错误';
        }elseif ($status==0) {
           $status=1;
        }elseif ($status==1) {
           $status=0;
        }


        if (Db::name('driver')->where('id', $id)->update(['status' => $status])) {
            $this->error('更新成功');
        } else {
            $this->error('更新失败');
        }
    }

    //添加
    // public function add()
    // {
    //     if ($this->request->isPost()) {
    //         $data = $this->request->param();
    //         if (!isset($data['status'])) {
    //             $data['status'] = 0;
    //         } else {
    //             $data['status'] = 1;
    //         }
    //         if ($this->Menu->add($data)) {
    //             $this->success("添加成功！", url("Menu/index"));
    //         } else {
    //             $error = $this->Menu->getError();
    //             $this->error($error ? $error : '添加失败！');
    //         }
    //     } else {
    //         $tree = new \util\Tree();
    //         $parentid = $this->request->param('parentid/d', '');
    //         $result = Db::name('menu')->order(array('listorder', 'id' => 'DESC'))->select();
    //         $array = array();
    //         foreach ($result as $r) {
    //             $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
    //             $array[] = $r;
    //         }
    //         $str = "<option value='\$id' \$selected>\$spacer \$title</option>";
    //         $tree->init($array);
    //         $select_categorys = $tree->get_tree(0, $str);
    //         $this->assign("select_categorys", $select_categorys);
    //         return $this->fetch();
    //     }
    // }

    //编辑后台菜单
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            // dump($data);exit;
               if (($data['status'] != 0 && $data['status'] != 1) || ($data['pass'] != 0 && $data['pass'] != 1)) {
                    return '参数错误';
                }
                $eui['status'] = $data['status'];
                $eui['pass'] = $data['pass'];
   

            if (Db::name('driver')->where('id', $data['id'])->update($eui)) {
                $this->success("编辑成功！", url("Driver/index"));
            } else {
                $error = Db::name('driver')->getError();
                $this->error($error ? $error : '编辑失败！');
            }
        } else {
            $request = request();
            $id = $request->param('id');
            $rs = Db::name('driver')->where(["id" => $id])->find();

            // echo $id;
            // dump($rs);
            $urls = ROOT_URL.'public/';
             $this->assign('urls',$urls);      
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
    //     $result = Db::name('menu')->where(["parentid" => $id])->find();
    //     if ($result) {
    //         $this->error("含有子菜单，无法删除！");
    //     }
    //     if ($this->Menu->del($id) !== false) {
    //         $this->success("删除菜单成功！");
    //     } else {
    //         $this->error("删除失败！");
    //     }
    // }

}
