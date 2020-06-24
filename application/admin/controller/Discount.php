<?php

// +----------------------------------------------------------------------
// | 优惠管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;

class discount extends Adminbase{
    public $sign = [
            '工程直接费'=>1,
            '材料直接费'=>1,
            '人工直接费'=>1,
            '工程报价'=>1,
            '纯人工项目'=>1,
            '特价项目'=>1,
            '设计费'=>1,
            '打拆工程'=>1,
            '电工工程'=>1,
            '加建工程'=>1,
            '镜钢玻璃类'=>1,
            '木工工程'=>1,
            '泥瓦工程'=>1,
            '水工工程'=>1,
            '油灰工程'=>1,
            '综合类'=>1,
            '现金'=>1,
            ];
    public function index(){
        $discount = Db::name('discount')->where(['fid'=>0,'is_del'=>0])->select();
        $this->assign([
            'discount' => $discount
        ]);
        return $this->fetch();
    }

    //添加优惠
    public function add_basis_discount(){
        $discount_val = input('discount_val');
        $discount_name = input('discount_name');
        $discount_content = input('discount_content');
        $formula = $discount_val;
        if(input('fid')){
            $fid = input('fid');
        }else{
            $fid = 0;
        }
        if(empty($discount_name)){
            $this->error('优惠名称不能为空');
        }
        foreach($this->sign as $k=>$v){
            $discount_val = str_replace($k,$v,$discount_val);
        }
        
        if(substr_count($discount_val,"11") > 0){
            $this->error('计算方式有误，请检查后重新提交');
        }
        if(preg_match("/[\+\-\*\/\.]{2}|[^\+\-\*\/\(\)\d\.]+/i", $discount_val, $matches)){
             $this->error('计算方式有误，请检查后重新提交');
        } else {
            if(substr_count($discount_val,"(") != substr_count($discount_val,")")){
                $this->error('计算方式有误，请检查后重新提交');
            }
        }
        $res = Db::name('discount')->insert(['fid'=>$fid, 'name'=>$discount_name, 'content'=>$discount_content, 'formula'=>$formula]);
        if($res){
            $this->success('添加成功');
        }
    }

    //删除优惠
    public function del_discount(){
        $did = input('did');
        Db::name('discount')->where(['id'=>$did])->update(['is_del'=>1]);
        $this->success('删除成功');
    }

    //选择基础库添加到自己的优惠库里面
    public function add_discount_by_basis(){
        $did = input('did');
        if(!$did){
            $this->error('参数错误');
        }
        $info = Db::name('discount')->where(['id'=>$did,'fid'=>0,'is_del'=>0])->find();
        if(!$info){
            $this->error('参数错误');
        }
        $info['fid'] = $this->_userinfo['companyid'];
        unset($info['addtime']);
        unset($info['id']);
        $res = Db::name('discount')->insert($info);
        if($res){
            $this->success('添加成功');
        }
    }

    public function get_discount_list(){
        $fid = input('fid')?input('fid'):0;
        $discount = Db::name('discount')->where(['fid'=>$fid,'is_del'=>0])->select();
        $this->success('success','',$discount);
    }
}
