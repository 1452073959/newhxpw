<?php

// +----------------------------------------------------------------------
// | 统计报表
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Process extends Adminbase{
    public function index(){
        $data = Db::name('process')->where(['is_del'=>1])->select();
        foreach($data as $k=>$v){
            $data[$k]['child'] = Db::name('process_child')->where(['pid'=>$v['id']])->select();
        }
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function add_process(){
        $data['name'] = input('process_name');
        $id = input('id');
        if($id){
            $res = Db::name('process')->where(['id'=>$id])->update($data);
        }else{
            $res = Db::name('process')->insert($data);
        }
        if(!$res){
            $this->error('添加失败');
        }
        $this->success('添加成功');
    }
    public function del_process(){
        $id = input('id');
        Db::startTrans();
        try {
            Db::name('process')->where(['id'=>$id])->update(['is_del'=>2]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('删除成功');
    }

    public function add_child(){
        $data['b_name'] = input('child_name');
        $data['content'] = input('child_content');
        $data['pid'] = input('pid');
        $res = Db::name('process_child')->insert($data);
        if(!$res){
            $this->error('添加失败');
        }
        $this->success('添加成功');
    }
    public function edit_child(){
        $id = input('id');
        $data['b_name'] = input('child_name');
        $data['content'] = input('child_content');
        $res = Db::name('process_child')->where(['id'=>$id])->update($data);
        if(!$res){
            $this->error('修改失败');
        }
        $this->success('修改成功');
    }
    public function del_child(){
        $id = input('id');
        
        $res = Db::name('process_child')->where(['id'=>$id])->delete();
        if(!$res){
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }

    public function get_child(){
        $id = input('id');
        $info = Db::name('process_child')->where(['id'=>$id])->find();
        if($info){
            $this->success('success','',$info);
        }
        $this->error('参数错误');
    }
}