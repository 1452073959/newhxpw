<?php

namespace app\seller\controller;

use app\seller\model\AdminUser;
use app\common\controller\Sellerbase;
use think\Db;

/**
 * 管理员管理
 */
class Manager extends Sellerbase
{
    protected function initialize()
    {
        parent::initialize();
        $this->AdminUser = new AdminUser;
    }

    /**
     * 管理员管理列表
     */
    public function index()
    {
        $User = Db::name("admin_seller")->order(array('userid' => 'ASC'))->select();
        $this->assign("Userlist", $User);
        return $this->fetch();
    }

    /**
     * 添加管理员
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.insert');
            if (true !== $result) {
                return $this->error($result);
            }
            if ($this->AdminUser->createManager($data)) {
                $this->success("添加管理员成功！", url('admin/manager/index'));
            } else {
                $error = $this->AdminUser->getError();
                $this->error($error ? $error : '添加失败！');
            }

        } else {
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

    /**
     * 管理员编辑
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.update');
            if (true !== $result) {
                return $this->error($result);
            }
            if ($this->AdminUser->editManager($data)) {
                $this->success("修改成功！");
            } else {
                $this->error($this->User->getError() ?: '修改失败！');
            }
        } else {
            $id = $this->request->param('id/d');
            $data = $this->AdminUser->where(array("userid" => $id))->find();
            if (empty($data)) {
                $this->error('该信息不存在！');
            }
            $this->assign("data", $data);
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

    /**
     * 管理员删除
     */
    public function del()
    {
        $id = $this->request->param('id/d');
        if ($this->AdminUser->deleteManager($id)) {
            $this->success("删除成功！");
        } else {
            $this->error($this->AdminUser->getError() ?: '删除失败！');
        }
    }

}
