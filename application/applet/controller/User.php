<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
namespace app\applet\controller;
use think\Db;
use think\Controller;
use app\applet\controller\UserBase;
 
class User extends UserBase
{
    public function getAdminInfo(){
        $applet_menu = array_column(Db::name('applet_menu')->where(['status'=>1])->order('pid','asc')->order('sort','asc')->order('id','asc')->select(), null,'id') ;
        $menu = [];
        foreach($applet_menu as $k=>$v){
            if($v['auth']){
                $v['auth'] = explode(',', $v['auth']);
                if(!in_array($this->admininfo['roleid'],$v['auth'])){
                    continue;
                }else{
                    unset($v['auth']);
                }
            }else{
                continue;
            }
            
            if($v['pid'] == 0){
                $menu[$v['id']] = $v;
            }else{
                if(isset($menu[$v['pid']])){
                    $menu[$v['pid']]['child'][] = $v;
                }
            }
        }
        $this->json(0,'success',['admininfo'=>$this->admininfo,'menu'=>$menu]);
    }

    public function getUserInfo(){
        $uid = input('uid');
        $userinfo = Db::name('userlist')->field('frameid,phone,customer_name,address,area,room_type,work_time,oid')->where(['id'=>$uid])->find();
        $this->json(0,'success',$userinfo);
    }

    public function getProcessInfo(){
        $id = input('id');
        $info = Db::name('process')->where(['id'=>$id])->find();
        $this->json(0,'success',$info);
    }

    public function getProcess(){
        $datas = Db::name('process')->where(['is_del'=>1])->order('id','asc')->select();
        $this->json(0,'success',$datas);
    }

    public function getProcessChild(){
        $pid = input('pid');
        $datas = Db::name('process_child')->order('id','asc')->where(['pid'=>$pid])->select();
        $this->json(0,'success',$datas);
    }

    public function getBook(){
        $datas = Db::name('book')->order('id','asc')->select();
        $this->json(0,'success',$datas);
    }
    public function getBookImg(){
        $bid = input('bid');
        $datas = Db::name('book_img')->order('id','asc')->where(['bid'=>$bid])->select();
        $arr = [];
        foreach($datas as $k=>$v){
            $arr[] = $this->getImgSrc($v['img'],'uploads/book');
        }
        $this->json(0,'success',$arr);
    }
}