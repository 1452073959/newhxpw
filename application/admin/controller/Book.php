<?php

// +----------------------------------------------------------------------
// | 统计报表
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class Book extends Adminbase{

    public function test6(){
        echo '暂停使用';die;
        $amcode = array_column(Db::name('f_materials')->where(['auto_add'=>1])->field('amcode')->select(), 'amcode');
        Db::name('materials')->where(['amcode'=>$amcode])->update(['auto_add'=>1]);
        echo 'ok';
    }

    public function test5(){
        echo '暂停使用';die;
        $furniture = Db::name('furniture')->where(['frameid'=>152])->select();
        // $fids = array_column(Db::name('cost_tmp')->select(), 'f_id');
        $fids = [45,46,61,82,85,153,155,188];
        $time = time();
        foreach($fids as $k1=>$v1){
            if($v1 == '152'){
                continue;
            }else{
                $add = [];
                foreach($furniture as $k2=>$v2){
                    $info = [];
                    unset($v2['id']);
                    $v2['addtime'] = $time;
                    $v2['frameid'] = $v1;
                    $info = $v2;
                    $add[] = $info;
                }
                Db::name('furniture')->insertAll($add);
            }
            
        }
        
        echo 'ok';
        // dump($add);
    }

    public function test4(){
        echo '暂停使用';die;
        $offerlist = Db::name('offerlist')->where(['is_del'=>0])->select();
        $oids = array_column($offerlist, 'id');
        $order_tmp_cost = Db::name('order_tmp_cost')->where(['oid'=>$oids])->group('oid')->select();
        $count_oids = array_column($order_tmp_cost, 'oid');
        $list = [];
        foreach($oids as $k=>$v){
            if(!in_array($v, $count_oids)){
                $list[] = $v;
            }
        }
        $datas = Db::name('offerlist')->field('frameid,id')->where(['id'=>$list])->select();
        $add = [];
        $time = time();
        foreach($datas as $k=>$v){
            $info = [];
            $info['oid'] = $v['id'];
            $info['f_id'] = $v['frameid'];
            $info['name'] = '直接费';
            $info['sign'] = 'A1';
            $info['formula'] = 'A1';
            $info['rate'] = '100';
            $info['add_time'] = $time;
            $info['content'] = '';
            $info['sort'] = 1;
            $add[] = $info;
            $info = [];
            $info['oid'] = $v['id'];
            $info['f_id'] = $v['frameid'];
            $info['name'] = '优惠';
            $info['sign'] = 'A2';
            $info['formula'] = 'A2';
            $info['rate'] = '100';
            $info['add_time'] = $time;
            $info['content'] = '';
            $info['sort'] = 2;
            $add[] = $info;

            $info = [];
            $info['oid'] = $v['id'];
            $info['f_id'] = $v['frameid'];
            $info['name'] = '工程报价';
            $info['sign'] = 'S';
            $info['formula'] = 'S';
            $info['rate'] = '100';
            $info['add_time'] = $time;
            $info['content'] = '';
            $info['sort'] = 3;
            $add[] = $info;

            $info = [];
            $info['oid'] = $v['id'];
            $info['f_id'] = $v['frameid'];
            $info['name'] = '总计';
            $info['sign'] = 'T';
            $info['formula'] = 'T';
            $info['rate'] = '100';
            $info['add_time'] = $time;
            $info['content'] = '';
            $info['sort'] = 4;
            $add[] = $info;
        }
        Db::name('order_tmp_cost')->insertAll($add);
        echo 'ok';
    }

    //给所有订单增加order_other字段
    public function test3(){
        echo '暂停使用';die;
        $offerlist = Db::name('offerlist')->select();
        $datas = [];
        foreach($offerlist as $k=>$v){
            $info = [];
            $info['oid'] = $v['id'];
            $datas[] = $info;
        }
        Db::name('order_other')->insertAll($datas);
        echo  'ok';
    }

    //把所有的取费模板都存在客户订单里面
    public function test2(){
        echo '暂停使用';die;
        $time = time();
        $add_datas = [];
        $offerlist = Db::name('offerlist')->field('tmp_cost_id,id,tmp_append_cost')->select();
        foreach($offerlist as $key=>$value){
            if($value['tmp_cost_id']){
                $tmp_cost = Db::name('tmp_cost')->where(['tmp_id'=>$value['tmp_cost_id']])->field('f_id,tmp_name,name,sign,formula,rate,content,sort,tmp_id')->order('sort','asc')->order('id','asc')->select();
                $append_tmp_cost = json_decode($value['tmp_append_cost'],true);//附加项
                if(!empty($append_tmp_cost)){
                    $i = 1;
                    foreach($append_tmp_cost as $k=>$v){
                        foreach($tmp_cost as $k2=>$v2){
                            if($v2['sign'] == 'S'){
                                $v['f_id'] = $v2['f_id'];
                                $v['content'] = isset($v['content'])?$v['content']:'';
                                // $v['tmp_id'] = $v2['tmp_id'];
                                if(isset($v['sort'])){
                                    //下面的if是判断是否为0, 上面的 是兼容以前的其他取费模板 没有sort这个值
                                    if($v['sort']){
                                        //插在后面
                                        array_splice($tmp_cost,$k2+$i,0,[$v]);
                                        $i++;
                                    }else{
                                        //插在前面
                                        array_splice($tmp_cost,$k2,0,[$v]);
                                    }
                                }else{
                                    //插在前面
                                    array_splice($tmp_cost,$k2,0,[$v]);
                                }
                            }else{
                                continue;
                            }
                        }
                    }
                }
                foreach($tmp_cost as $k=>$v){
                    $info['oid'] = $value['id'];
                    $info['f_id'] = $v['f_id'];
                    $info['name'] = $v['name'];
                    $info['sign'] = $v['sign'];
                    $info['formula'] = $v['formula'];
                    $info['rate'] = $v['rate'];
                    $info['add_time'] = $time;
                    $info['content'] = $v['content'];
                    $info['sort'] = $k+1;
                    $add_datas[] = $info;
                    
                }
                
            }
        }
        Db::name('order_tmp_cost')->insertAll($add_datas);
        echo 11;
        
    }

    //重置取费模板
    public function test1(){
        echo '暂停使用';die;
        $tmp_id = Db::name('tmp_cost')->group('tmp_id')->select();

        Db::startTrans();
        try {
            foreach($tmp_id as $k=>$v){
                $list = Db::name('tmp_cost')->where(['tmp_id'=>$v['tmp_id']])->select();
                foreach($list as $k2=>$v2){
                    Db::name('tmp_cost')->where(['id'=>$v2['id']])->update(['sort'=>$k2+2]);
                }
                $datas = [];
                $info = [];
                $info['tmp_id'] = $v['tmp_id'];
                $info['f_id'] = $v['f_id'];
                $info['tmp_name'] = $v['tmp_name'];
                $info['name'] = '直接费';
                $info['sign'] = 'A1';
                $info['formula'] = 'A1';
                $info['rate'] = 100;
                $info['content'] = '';
                $info['sort'] = 0;
                $info['add_time'] = $v['add_time'];
                $datas[] = $info;

                //优惠
                $info = [];
                $info['tmp_id'] =  $v['tmp_id'];
                $info['f_id'] = $v['f_id'];
                $info['tmp_name'] = $v['tmp_name'];
                $info['name'] = '优惠';
                $info['sign'] = 'A2';
                $info['formula'] = 'A2';
                $info['rate'] = 100;
                $info['content'] = '';
                $info['sort'] = 1;
                $info['add_time'] = $v['add_time'];
                $datas[] = $info;

                //工程报价
                $info = [];
                $info['tmp_id'] = $v['tmp_id'];
                $info['f_id'] = $v['f_id'];
                $info['tmp_name'] = $v['tmp_name'];
                $info['name'] = '工程报价';
                $info['sign'] = 'S';
                $info['formula'] = 'S';
                $info['rate'] = 100;
                $info['content'] = '';
                $info['sort'] = count($list)+2;
                $info['add_time'] = $v['add_time'];
                $datas[] = $info;

                //总计
                $info = [];
                $info['tmp_id'] = $v['tmp_id'];
                $info['f_id'] = $v['f_id'];
                $info['tmp_name'] = $v['tmp_name'];
                $info['name'] = '总计';
                $info['sign'] = 'T';
                $info['formula'] = 'T';
                $info['rate'] = 100;
                $info['content'] = '';
                $info['sort'] = count($list)+3;
                $info['add_time'] = $v['add_time'];
                $datas[] = $info;
                // var_dump($datas);
                Db::name('tmp_cost')->insertAll($datas);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        echo 'ok';
        
    }

    
    public function test(){
        echo '暂停使用';die;
        $frame = array_column(Db::name('frame')->select(),null, 'id');
        $offerlist = Db::name('offerlist')->select();
        $data = [];
        foreach($offerlist as $k=>$v){
            if(!isset($data[$frame[$v['frameid']]['name']])){
                $data[$frame[$v['frameid']]['name']] = 0;
            }
            $data[$frame[$v['frameid']]['name']]++;
            
        }
        dump($data);die;

        die;
        $data = [];
        $admin = Db::name('admin')->where(['status'=>1])->select();
        $auth_group = array_column(Db::name('auth_group')->select(),null, 'id');
        foreach($admin as $k=>$v){
            // echo $frame[$v['companyid']]['name'];
            if(!isset($data[$frame[$v['companyid']]['name']][$auth_group[$v['roleid']]['title']]['num'])){
                $data[$frame[$v['companyid']]['name']][$auth_group[$v['roleid']]['title']]['num'] = 1;
                $data[$frame[$v['companyid']]['name']][$auth_group[$v['roleid']]['title']]['is_login'] = 0;
                if($v['token'] || $v['last_login_time']){
                    $data[$frame[$v['companyid']]['name']][$auth_group[$v['roleid']]['title']]['is_login'] = 1;
                }
            }else{
                $data[$frame[$v['companyid']]['name']][$auth_group[$v['roleid']]['title']]['num']++;
                if($v['token'] || $v['last_login_time']){
                    $data[$frame[$v['companyid']]['name']][$auth_group[$v['roleid']]['title']]['is_login']++;
                }
            }
        }
        echo '<style>table{border:1px solid #ccc} td{border:1px solid #ccc;width:100px;text-align:center} </style>';
        echo '<table>';
        echo '<tr><th>分公司</th><th>分总经理</th><th>报价师</th><th>人事</th><th>财务</th><th>工程监理</th><th>仓管</th><th>质检</th><th>工程经理</th></th>';
        foreach($data as $k=>$v){
            echo '<tr>';
            echo '<td>'.$k.'</td>';
            if(isset($v['分总经理'])){
                echo '<td>'.$v['分总经理']['is_login'].'//'.$v['分总经理']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            if(isset($v['报价师'])){
                echo '<td>'.$v['报价师']['is_login'].'//'.$v['报价师']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            if(isset($v['人事'])){
                echo '<td>'.$v['人事']['is_login'].'//'.$v['人事']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            if(isset($v['财务'])){
                echo '<td>'.$v['财务']['is_login'].'//'.$v['财务']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }

            if(isset($v['工程监理'])){
                echo '<td>'.$v['工程监理']['is_login'].'//'.$v['工程监理']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            if(isset($v['仓管'])){
                echo '<td>'.$v['仓管']['is_login'].'//'.$v['仓管']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            if(isset($v['质检'])){
                echo '<td>'.$v['质检']['is_login'].'//'.$v['质检']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            if(isset($v['工程经理'])){
                echo '<td>'.$v['工程经理']['is_login'].'//'.$v['工程经理']['num'].'</td>';
            }else{
                echo '<td>0</td>';
            }
            
            echo '</tr>';
        }

        echo '</table>';

        dump($data);
    }
    public function index(){
        $data = Db::name('book')->order('sort','asc')->order('id','asc')->select();
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function add_book(){
        $data['name'] = input('name');
        $data['tag'] = input('tag');
        $id = input('id');
        if($id){
            $res = Db::name('book')->where(['id'=>$id])->update($data);
        }else{
            $res = Db::name('book')->insert($data);
        }
        if(!$res){
            $this->error('添加失败');
        }
        $this->success('添加成功');
    }

    //批量更新图片
    public function update_img(){
        $id = input('id');
        $tag = Db::name('book')->where(['id'=>$id])->value('tag');
        if(!$tag){
            $this->error('参数错误');
        }
        $path = './uploads/book';
        $myfile = scandir($path.'/'.$tag);
        $file = [];
        foreach ($myfile as $value){
            if($value != '.' && $value != '..'){
                $file[] = $value;
            }
        }
        if(empty($file)){
            $this->error('文件夹没有图片');
        }
        Db::startTrans();
        try {
            Db::name('book_img')->where(['bid'=>$id])->delete();
            foreach($file as $k=>$v){
                $info = [];
                $info['sort'] = 0;
                $info['bid'] = $id;
                $info['img'] = '/'.$tag.'/'.$v;
                Db::name('book_img')->insert($info);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('更新图片成功');
    }

    public function del_book(){
        $id = input('id');
        $res = Db::name('book')->where(['id'=>$id])->delete();
        if(!$res){
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }

    //获取图片列表
    public function book_info(){
        
    }
}