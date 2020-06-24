<?php

// +----------------------------------------------------------------------
// | 后台菜单管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\Menu as Menu_Model;
use app\common\controller\Adminbase;
use think\Db;

class Menu extends Adminbase
{
    protected function initialize()
    {
        parent::initialize();
        $this->Menu = new Menu_Model;
    }

    //后台菜单首页
    public function index()
    {
        $tree = new \util\Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = Db::name('menu')->order(array('listorder', 'id' => 'DESC'))->select();
        $array = array();
        foreach ($result as $r) {
            $r['str_manage'] = '<a class="" href=' . url("Menu/edit", array("id" => $r['id'])) . '><i class="layui-icon layui-icon-edit" title="编辑"></i></a><a class="" href=' . url("Menu/add", array("parentid" => $r['id'])) . '><i class="layui-icon layui-icon-add-1" title="添加"></i></a><a class="" url=' . url("Menu/delete", array("id" => $r['id'])) . '><i class="layui-icon layui-icon-delete" title="删除"></i></a>';
            $r['status'] = $r['status'] ? "<span class='on'><i class='icon iconfont icon-xianshi'></i>显示</span>" : "<span class='off'><i class='icon iconfont icon-yincang'></i>隐藏</span>";
            $array[] = $r;
        }
        $str = "<tr>
        <td>\$listorder</td>
        <td>\$id</td>
        <td>\$str_manage</td>
        <td>\$spacer\$title</td>
        <td>\$status</td>
        </tr>";
        $tree->init($array);
        $categorys = $tree->get_tree(0, $str);
        $this->assign('categorys', $categorys);
        return $this->fetch();
    }

    //添加后台菜单
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (!isset($data['status'])) {
                $data['status'] = 0;
            } else {
                $data['status'] = 1;
            }
            if ($this->Menu->add($data)) {
                $this->success("添加成功！", url("Menu/index"));
            } else {
                $error = $this->Menu->getError();
                $this->error($error ? $error : '添加失败！');
            }
        } else {
            $tree = new \util\Tree();
            $parentid = $this->request->param('parentid/d', '');
            $result = Db::name('menu')->order(array('listorder', 'id' => 'DESC'))->select();
            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$title</option>";
            $tree->init($array);
            $select_categorys = $tree->get_tree(0, $str);
            $this->assign("select_categorys", $select_categorys);
            return $this->fetch();
        }
    }

    //编辑后台菜单
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (!isset($data['status'])) {
                $data['status'] = 0;
            } else {
                $data['status'] = 1;
            }
            if ($this->Menu->edit($data)) {
                $this->success("编辑成功！", url("Menu/index"));
            } else {
                $error = $this->Menu->getError();
                $this->error($error ? $error : '编辑失败！');
            }
        } else {
            $tree = new \util\Tree();
            $id = $this->request->param('id/d', '');
            $rs = Db::name('menu')->where(["id" => $id])->find();
            $result = Db::name('menu')->order(array('listorder', 'id' => 'DESC'))->select();
            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $rs['parentid'] ? 'selected' : '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$title</option>";
            $tree->init($array);
            $select_categorys = $tree->get_tree(0, $str);
            $this->assign("data", $rs);
            $this->assign("select_categorys", $select_categorys);
            return $this->fetch();
        }

    }

    /**
     * 菜单删除
     */
    public function delete()
    {
        $id = $this->request->param('id/d');
        if (empty($id)) {
            $this->error('ID错误');
        }
        $result = Db::name('menu')->where(["parentid" => $id])->find();
        if ($result) {
            $this->error("含有子菜单，无法删除！");
        }
        if ($this->Menu->del($id) !== false) {
            $this->success("删除菜单成功！");
        } else {
            $this->error("删除失败！");
        }
    }


    public function applet_menu(){
        $menu_list = [];
        $applet_menu = array_column(Db::name('applet_menu')->order('pid','asc')->order('sort','asc')->order('id','asc')->select(), null,'id') ;
        //获取角色信息
        $auth_group = array_column(Db::name('auth_group')->select(), null,'id') ;
        $menu = [];
        foreach($applet_menu as $k=>$v){
            if($v['auth']){
                $v['auth'] = explode(',', $v['auth']);
                foreach($v['auth'] as $k1=>$v1){
                    $v['auth'][$k1] = $auth_group[$v1]['title'];
                }
                $v['auth'] = implode(',', $v['auth']);
            }
            
            if($v['pid'] == 0){
                $menu[$v['id']] = $v;
            }else{
                $menu[$v['pid']]['child'][] = $v;
            }
        }
        $this->assign("auth_group", $auth_group);
        $this->assign("menu", $menu);//排序好的
        $this->assign("applet_menu", $applet_menu);//原始数据
        return $this->fetch();
    }

    //添加小程序菜单
    public function add_applet_menu(){
        if(input()){
            $data = input();
            unset($data['file']);
            if(isset($data['auth'])){
                $data['auth'] = implode(',', $data['auth']);
            }
            $res = Db::name('applet_menu')->insert($data);
            if($res){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->error("添加失败");
        }
    }

    //编辑小程序菜单
    public function edit_applet_menu(){
        if(input() && input('id')){
            $data = input();
            unset($data['file']);
            if(isset($data['auth'])){
                $data['auth'] = implode(',', $data['auth']);
            }
            if(empty($data['img'])){
                unset($data['img']);
            }
            $res = Db::name('applet_menu')->update($data);
            if($res){
                $this->success('编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $this->error("编辑失败");
        }
    }

    //删除小程序菜单
    public function del_applet_menu(){
        if(input('id')){
            $result = Db::name('applet_menu')->where(["pid" => input('id')])->count();
            if($result){
                $this->error('含有子菜单，无法删除！');
            }
            $res = Db::name('applet_menu')->where(['id'=>input('id')])->delete();
            if($res){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error("删除失败");
        }
    }

    //上传图片
    public function upload_image(){
        $file = request()->file('file');
        if($file){
            // 1048576 = 1mb
            $info = $file->validate(['size'=>1048576])->move( './uploads/images');
            if($info){
                // 成功上传后 获取上传信息
                $images = $info->getSaveName();
                $images = str_replace('\\', '/', $images);
                $this->success('success','',$images);
            }else{
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
        }else{
            $this->error('图片未上传');
        }
    }

}
