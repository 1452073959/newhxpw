<?php

// +----------------------------------------------------------------------
// | 辅材基础数据 分公司提供
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;
use think\db\Where;
use think\Cache;

use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;



class BasisData extends Adminbase{


    //分公司基础数据报表
    public function tmp_report(){
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        $cost_tmp = array_column(Db::name('cost_tmp')->where(['f_id'=>array_keys($frame)])->select(), null,'f_id');
        $this->assign('data',$cost_tmp);
        $this->assign('frame',$frame);
        return $this->fetch();
    }
    //分公司基础数据报表
    public function tmp_report2(){
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        // $cost_tmp = array_column(Db::name('cost_tmp')->where(['f_id'=>array_keys($frame)])->select(), null,'f_id');
        $tmp_cost = Db::name('tmp_cost')->where(['status'=>1])->order('f_id','asc')->select();
        // foreach($tmp_cost as $k=>$v){
        //     if(isset($cost_tmp[$v['f_id']])){
        //         $cost_tmp[$v['f_id']]['cost_tmp'][$v['tmp_id']][] = $v;
        //     }
        // }
        $this->assign('data',$tmp_cost);
        $this->assign('frame',$frame);
        return $this->fetch();
    }

    //报价报表1
    public function projct_report(){
        $where = [];
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        // $new_frame = ['嘉兴分公司','海宁分公司','长兴分公司','德清分公司','丽水分公司','海门分公司','金华分公司','青田分公司','广元分公司','宜兴分公司','镇江分公司','太仓分公司','惠州分公司','衡阳分公司','郴州分公司','邵阳分公司','星沙分公司','花都分公司','河源分公司','九江分公司','四会分公司','南宁分公司','玉林分公司','北海分公司','柳州分公司','百色分公司','宁德分公司','金阳分公司','兴义分公司','临海分公司','海口分公司','琼海分公司','三亚分公司','曲靖分公司'];
        $new_frame = ['丽水分公司','青田分公司','金华分公司','海门分公司','太仓分公司','连云港分公司','镇江分公司','海宁分公司','嘉兴分公司','九江分公司','惠州分公司','郴州分公司','邵阳分公司','星沙分公司','兴义分公司','金阳分公司','河源分公司','花都分公司','百色分公司','北海分公司','柳州分公司','南宁分公司','三亚分公司','临海分公司','四会分公司'];
        foreach($frame as $k=>$v){
            if(!in_array($v['name'], $new_frame)){
                unset($frame[$k]);
            }
        }
        if(input('type_of_work')){
            $where['type_word_id'] = explode(',', input('type_of_work'));
        }
        if(input('frame')){
            $where['fid'] = explode(',', input('frame'));
        }else{
            $where['fid'] = array_keys($frame);
        }

        if(!empty($where)){
            $data = Db::name('f_project')->alias('f')->leftJoin('basis_project b','f.p_item_number = b.item_number')->group('f.item_number')->where($where)->order('f.item_number','asc')->select();
            // var_dump($data);
        }else{
            $data = [];
        }

        $type_of_work = array_column(Db::name('basis_type_work')->select(), null,'id');
        $this->assign('type_of_work',$type_of_work);
        $this->assign('frame',$frame);
        $this->assign('data',$data);
        return $this->fetch();
    }

    //报价报表2
    public function project_report_by_f(){
        $field = ['cost_value'=>'综合价','quota'=>'辅材单价','craft_show'=>'人工单价','labor_cost'=>'人工成本'];
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        // $new_frame = ['嘉兴分公司','海宁分公司','长兴分公司','德清分公司','丽水分公司','海门分公司','金华分公司','青田分公司','广元分公司','宜兴分公司','镇江分公司','太仓分公司','惠州分公司','衡阳分公司','郴州分公司','邵阳分公司','星沙分公司','花都分公司','河源分公司','九江分公司','四会分公司','南宁分公司','玉林分公司','北海分公司','柳州分公司','百色分公司','宁德分公司','金阳分公司','兴义分公司','临海分公司','海口分公司','琼海分公司','三亚分公司','曲靖分公司'];
        $new_frame = ['丽水分公司','青田分公司','金华分公司','海门分公司','太仓分公司','连云港分公司','镇江分公司','海宁分公司','嘉兴分公司','九江分公司','惠州分公司','郴州分公司','邵阳分公司','星沙分公司','兴义分公司','金阳分公司','河源分公司','花都分公司','百色分公司','北海分公司','柳州分公司','南宁分公司','三亚分公司','临海分公司','四会分公司'];
        foreach($frame as $k=>$v){
            if(!in_array($v['name'], $new_frame)){
                unset($frame[$k]);
            }
        }
        $where = [];
        $condition = [];
        $f_project = [];
        $has_f_project = [];
        if(input('type_of_work')){
            $where[] = ['type_word_id','in',explode(',', input('type_of_work'))];
        }
        if(input('item_number')){
            $where[] = ['item_number','like','%'.input('item_number').'%'];
        }
        if(input('frame')){
            $condition['fid'] = explode(',', input('frame'));
        }else{
            $condition['fid'] = array_keys($frame);
        }
        $basis_project = Db::name('basis_project')->group('item_number')->where($where)->order('item_number','asc')->select();
        foreach($basis_project as $k=>$v){
            $basis_project[$k]['count_all'] = Db::name('f_project')->where(['p_item_number'=>$v['item_number']])->field('count(id) as count')->find()['count'];
            $basis_project[$k]['count'] = Db::name('f_project')->where(['p_item_number'=>$v['item_number'],'fid'=>$condition['fid']])->field('count(id) as count')->find()['count'];
        }
        $item_number = array_column($basis_project, 'item_number');
        if(!empty($item_number)){
            $f_datas = Db::name('f_project')->where($condition)->where(['p_item_number'=>$item_number])->select();
            foreach($f_datas as $k=>$v){
                
                $f_project[$v['fid']][$v['p_item_number']][] = $v;
                $has_f_project[] = $v['p_item_number'];
            }
        }

        $type_of_work = array_column(Db::name('basis_type_work')->select(), null,'id');
        $this->assign('type_of_work',$type_of_work);
        $this->assign('frame',$frame);
        $this->assign('basis_project',$basis_project);
        $this->assign('f_project',$f_project);
        $this->assign('has_f_project',$has_f_project);
        $this->assign('fid',$condition['fid']);
        $this->assign('field',$field);
        return $this->fetch();
    }

    //辅材报表1
    public function material_report(){

        $where = [];
        if(input('type_of_work')){
            $where['b.type_of_work'] = explode(',', input('type_of_work'));
        }
        if(input('classify')){
            $where['b.classify'] = explode(',', input('classify'));
        }
        if(input('fine')){
            $where['b.fine'] = explode(',', input('fine'));
        }
        if(input('source')){
            $where['f.source'] = explode(',', input('source'));
        }
        if(input('frame')){
            $where['f.fid'] = explode(',', input('frame'));
        }

        if(!empty($where)){
            $data = Db::name('f_materials')->alias('f')->leftJoin('basis_materials b','f.p_amcode = b.amcode')->group('f.amcode')->where($where)->order('f.p_amcode','asc')->select();
            // var_dump($data);
        }else{
            $data = [];
        }

        $type_of_work = Db::name('basis_materials')->field('type_of_work')->group('type_of_work')->select();
        $classify = Db::name('basis_materials')->field('classify')->group('classify')->select();
        $fine = Db::name('basis_materials')->field('fine')->group('fine')->select();
        $source = Db::name('f_materials')->field('source')->group('source')->select();
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        $new_frame = ['嘉兴分公司','海宁分公司','长兴分公司','德清分公司','丽水分公司','海门分公司','金华分公司','青田分公司','广元分公司','宜兴分公司','镇江分公司','太仓分公司','惠州分公司','衡阳分公司','郴州分公司','邵阳分公司','星沙分公司','花都分公司','河源分公司','九江分公司','四会分公司','南宁分公司','玉林分公司','北海分公司','柳州分公司','百色分公司','宁德分公司','金阳分公司','兴义分公司','临海分公司','海口分公司','琼海分公司','三亚分公司','曲靖分公司'];
        foreach($frame as $k=>$v){
            if(!in_array($v['name'], $new_frame)){
                unset($frame[$k]);
            }
        }
        $this->assign('type_of_work',$type_of_work);
        $this->assign('classify',$classify);
        $this->assign('fine',$fine);
        $this->assign('source',$source);
        $this->assign('frame',$frame);
        $this->assign('data',$data);
        return $this->fetch();
    }

    //辅材报表2
    public function material_report_by_f(){
        $field = ['brank'=>'品牌','place'=>'产地','price'=>'出库价','in_price'=>'入库价','pack'=>'包装数量','one_price'=>'出库计量单价','one_in_price'=>'入库计量单价','phr'=>'出库单位','source'=>'来源'];
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
        $new_frame = ['嘉兴分公司','海宁分公司','长兴分公司','德清分公司','丽水分公司','海门分公司','金华分公司','青田分公司','广元分公司','宜兴分公司','镇江分公司','太仓分公司','惠州分公司','衡阳分公司','郴州分公司','邵阳分公司','星沙分公司','花都分公司','河源分公司','九江分公司','四会分公司','南宁分公司','玉林分公司','北海分公司','柳州分公司','百色分公司','宁德分公司','金阳分公司','兴义分公司','临海分公司','海口分公司','琼海分公司','三亚分公司','曲靖分公司'];
        foreach($frame as $k=>$v){
            if(!in_array($v['name'], $new_frame)){
                unset($frame[$k]);
            }
        }
        $where = [];
        $condition = [];
        $f_materials = [];
        $has_f_materials = [];
        if(input('type_of_work')){
            $where['type_of_work'] = explode(',', input('type_of_work'));
        }
        if(input('classify')){
            $where['classify'] = explode(',', input('classify'));
        }
        if(input('fine')){
            $where['fine'] = explode(',', input('fine'));
        }
        if(input('frame')){
            $condition['fid'] = explode(',', input('frame'));
        }else{
            $condition['fid'] = array_keys($frame);
        }

        

        if(1){
            $basis_materials = Db::name('basis_materials')->group('amcode')->where($where)->order('amcode','asc')->select();
            foreach($basis_materials as $k=>$v){
                $basis_materials[$k]['count_all'] = Db::name('f_materials')->where(['p_amcode'=>$v['amcode']])->field('count(id) as count')->find()['count'];
                $basis_materials[$k]['count'] = Db::name('f_materials')->where(['p_amcode'=>$v['amcode'],'fid'=>$condition['fid']])->field('count(id) as count')->find()['count'];
            }
            $amcode = array_column($basis_materials, 'amcode');
            if(!empty($amcode)){
                $f_datas = Db::name('f_materials')->where($condition)->where(['p_amcode'=>$amcode])->select();
                foreach($f_datas as $k=>$v){
                    if(is_numeric($v['pack']) && $v['pack'] > 0){
                        $v['one_price']  = $v['price'] / $v['pack'];
                    }else{
                        $v['one_price'] = 0;
                    }
                    if(is_numeric($v['pack']) && $v['pack'] > 0){
                        $v['one_in_price']  = $v['in_price'] / $v['pack'];
                    }else{
                        $v['one_in_price'] = 0;
                    }
                    
                    $f_materials[$v['fid']][$v['p_amcode']][] = $v;
                    $has_f_materials[] = $v['p_amcode'];
                }
            }
        }else{
            $basis_materials = [];
        }
        $type_of_work = Db::name('basis_materials')->field('type_of_work')->group('type_of_work')->select();
        $classify = Db::name('basis_materials')->field('classify')->group('classify')->select();
        $fine = Db::name('basis_materials')->field('fine')->group('fine')->select();
        $this->assign('type_of_work',$type_of_work);
        $this->assign('classify',$classify);
        $this->assign('fine',$fine);
        $this->assign('frame',$frame);
        $this->assign('basis_materials',$basis_materials);
        $this->assign('f_materials',$f_materials);
        $this->assign('has_f_materials',$has_f_materials);
        $this->assign('fid',$condition['fid']);
        $this->assign('field',$field);
        return $this->fetch();
    }

    //查看每间分公司录入情况报表
    public function get_entry_statistical(){
        $arr = [];
        $f_materials = array_column(Db::name('f_materials')->group('fid')->field('count(id) as f_materials,fid')->select(),null, 'fid') ;
        $f_project = array_column(Db::name('f_project')->group('fid')->field('count(id) as f_project,fid')->select(),null, 'fid');
        $apply_material1 = array_column(Db::name('apply_material')->where(['status'=>1])->group('fid')->field('count(id) as apply_material1,fid')->select(),null, 'fid');
        $apply_material2 = array_column(Db::name('apply_material')->where(['status'=>2])->group('fid')->field('count(id) as apply_material2,fid')->select(),null, 'fid');
        $apply_project1 = array_column(Db::name('apply_project')->where(['status'=>1])->group('fid')->field('count(id) as apply_project1,fid')->select(),null, 'fid');
        $apply_project2 = array_column(Db::name('apply_project')->where(['status'=>2])->group('fid')->field('count(id) as apply_project2,fid')->select(),null, 'fid');
        $personnel = array_column(Db::name('personnel')->group('fid')->field('count(id) as personnel,fid')->select(),null, 'fid');
        $department = array_column(Db::name('department')->group('fid')->field('count(id) as department,fid')->select(),null, 'fid');
        $frame = Db::name('frame')->where('levelid',3)->field('id,name')->select();
        foreach($frame as $k=>$v){
            if(isset($f_materials[$v['id']])){
                $arr[$v['name']]['f_materials'] = $f_materials[$v['id']]['f_materials'];
            }
            if(isset($f_project[$v['id']])){
                $arr[$v['name']]['f_project'] = $f_project[$v['id']]['f_project'];
            }
            if(isset($apply_material1[$v['id']])){
                $arr[$v['name']]['apply_material1'] = $apply_material1[$v['id']]['apply_material1'];
            }
            if(isset($apply_material2[$v['id']])){
                $arr[$v['name']]['apply_material2'] = $apply_material2[$v['id']]['apply_material2'];
            }
            if(isset($apply_project1[$v['id']])){
                $arr[$v['name']]['apply_project1'] = $apply_project1[$v['id']]['apply_project1'];
            }
            if(isset($apply_project2[$v['id']])){
                $arr[$v['name']]['apply_project2'] = $apply_project2[$v['id']]['apply_project2'];
            }
            if(isset($personnel[$v['id']])){
                $arr[$v['name']]['personnel'] = $personnel[$v['id']]['personnel'];
            }
            if(isset($department[$v['id']])){
                $arr[$v['name']]['department'] = $department[$v['id']]['department'];
            }
        }
        $this->assign('data',$arr);
        return $this->fetch();
    }

    //公共工种列表
    public function public_type_work(){
        $where = [];
        $res = Db::name('basis_type_work')->where($where)->order('id','asc')->paginate(20,false,['query'=>request()->param()]);
        $this->assign('data',$res);
        return $this->fetch();
    }

    public function add_public_type_work(){
        $data['name'] = input('name');
        $id = Db::name('basis_type_work')->where(['name'=>$data['name']])->value('id');
        if($id){
            $this->error('添加工种已存在');
        }
        $res = Db::name('basis_type_work')->insert($data);
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    //辅材公共基础库列表
    public function public_warehouse(){
        $where = [];
        if(input('samcode')){
            $where[] = ['amcode','like','%'.input('samcode').'%'];
        }
        if(input('stype_of_work')){
            $where[] = ['type_of_work','like','%'.input('stype_of_work').'%'];
        }
        if(input('sclassify')){
            $where[] = ['classify','like','%'.input('sclassify').'%'];
        }
        if(input('sfine')){
            $where[] = ['fine','like','%'.input('sfine').'%'];
        }
        if(input('sname')){
            $where[] = ['name','like','%'.input('sname').'%'];
        }
        $res = Db::name('basis_materials')->where($where)->order('id','asc')->paginate(20,false,['query'=>request()->param()])->each(function($item, $key){
            $item['count'] = Db::name('f_materials')->where(['p_amcode'=>$item['amcode']])->field('count(id) as count')->find()['count'];
            return $item;
        });

        $this->assign('data',$res);
        return $this->fetch();
    }

    //添加辅材公共基础库
    public function add_public_warehouse(){
        $data['amcode'] = input('amcode');
        $data['type_of_work'] = input('type_of_work');
        $data['classify'] = input('classify');
        $data['fine'] = input('fine');
        $data['name'] = input('name');
        $data['unit'] = input('unit');
        $data['price'] = input('price');
        $data['in_price'] = input('in_price');
        $data['pack'] = input('pack');
        $data['phr'] = input('phr');
        $data['source'] = input('source');
        $data['in_warehouse'] = input('in_warehouse')==1?1:2;
        if(strlen($data['price']) == 0 || strlen($data['in_price']) == 0 || !$data['pack'] || !$data['phr'] || !$data['source'] ){
            $this->error('大仓辅材,入库价、出库价、包装数量、出库单位、来源不得为空');
        }
        if($data['source'] != '公司仓库' && $data['source'] != '公司定点' && $data['source'] != '监理自购'){
            $this->error('来源必须为公司仓库、公司定点、监理自购其中一个');
        }
        if(input('brank')){
            $data['brank'] = input('brank');
        }else{
            $data['brank'] = '';
        }
        if(input('place')){
            $data['place'] = input('place');
        }else{
            $data['place'] = '';
        }
        if(input('warehouse_id')){
            $data['warehouse_id'] = input('warehouse_id');
        }else{
            $data['warehouse_id'] = '';
        }
        $data['coefficient'] = input('coefficient');
        $data['important'] = input('important');
        if(!$data['amcode'] || !$data['type_of_work'] || !$data['fine'] || !$data['name'] || !$data['unit'] ){
            $this->error('参数不得为空');
        }
        if($data['coefficient'] < 0 || $data['coefficient'] > 100 || !is_numeric($data['coefficient']) || $data['coefficient']%1 != 0){
            $this->error('系数输入不规范');
        }
        if($data['important'] != '0' && $data['important'] != 1){
            $this->error('是否重要输入不规范');
        }

        $id = Db::name('basis_materials')->where(['amcode'=>$data['amcode']])->value('id');
        if($id){
            $this->error('编号已存在');
        }
        //判断细类单位是否一致
        $unit = Db::name('basis_materials')->where(['fine'=>$data['fine']])->value('unit');
        if($unit && $unit != $data['unit']){
            $this->error('分类单位错误，应为：'.$unit);
        }
        Db::startTrans();
        try {
            $res = Db::name('basis_materials')->insert($data);
            if(input('apply_material_id')){
                //分公司申请后 管理员添加的辅材 自动绑定的申请的辅材里面
                $apply_material = Db::name('apply_material')->where(['id'=>input('apply_material_id')])->find();
                Db::name('apply_material')->where(['id'=>input('apply_material_id')])->update(['p_amcode'=>$data['amcode'],'status'=>2,'audittime'=>time()]);
                $f_materials = [];
                $f_materials['p_amcode'] = $data['amcode'];
                $f_materials['fine'] = $data['fine'];
                $f_materials['fid'] = $apply_material['fid'];
                $f_materials['brank'] = $data['brank'];
                $f_materials['place'] = $data['place'];
                $f_materials['warehouse_id'] = $data['warehouse_id'];
                $f_materials['img'] = '';
                $f_materials['price'] = $data['price'];
                $f_materials['in_price'] = $data['in_price'];
                $f_materials['pack'] = $data['pack'];
                $f_materials['phr'] = $data['phr'];
                $f_materials['source'] = $data['source'];
                $f_materials['auto_add'] = 1;
                $res = Db::name('f_materials')->insertGetId($f_materials);
                Db::name('f_materials')->where(['id'=>$res])->update(['amcode'=>$data['amcode'].'_'.$res]);
                $this->update_fwarehouse($data['amcode'].'_'.$res);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('添加成功');
    }

    //修改辅材公共基础库
    public function edit_public_warehouse(){
        $data['amcode'] = input('amcode');
        $data['type_of_work'] = input('type_of_work');
        $data['classify'] = input('classify');
        $data['fine'] = input('fine');
        $data['name'] = input('name');
        $data['unit'] = input('unit');
        $data['brank'] = input('brank');
        $data['place'] = input('place');
        $data['price'] = input('price');
        $data['in_price'] = input('in_price');
        $data['pack'] = input('pack');
        $data['phr'] = input('phr');
        $data['source'] = input('source');
        $data['warehouse_id'] = input('warehouse_id');
        $data['in_warehouse'] = input('in_warehouse')==1?1:2;
        
        if(!$data['price'] || !$data['in_price'] || !$data['pack'] || !$data['phr'] || !$data['source'] ){
            $this->error('大仓辅材,入库价、出库价、包装数量、出库单位、来源不得为空');
        }
        if($data['source'] != '公司仓库' && $data['source'] != '公司定点' && $data['source'] != '监理自购'){
            $this->error('来源必须为公司仓库、公司定点、监理自购其中一个');
        }
        
        $data['coefficient'] = input('coefficient');
        $data['important'] = input('important');
        if(!$data['amcode'] || !$data['type_of_work'] || !$data['fine'] || !$data['name'] || !$data['unit'] ){
            $this->error('参数不得为空');
        }
        if($data['coefficient'] < 0 || $data['coefficient'] > 100 || !is_numeric($data['coefficient']) || $data['coefficient']%1 != 0){
            $this->error('系数输入不规范');
        }
        if($data['important'] != '0' && $data['important'] != 1){
            $this->error('是否重要输入不规范');
        }
        //判断细类单位是否一致
        $edit_unit = 0;
        Db::startTrans();
        try {
            $basis_materials = Db::name('basis_materials')->where(['fine'=>$data['fine']])->find();
            $unit = $basis_materials['unit'];
            $fine = $basis_materials['fine'];
            if($unit && $unit != $data['unit']){
                $edit_unit = Db::name('basis_materials')->where(['fine'=>$data['fine']])->update(['unit'=>$data['unit']]);
                unset($data['unit']);
            }
            //修改了分类 那么就把分公司的分类也改了
            if($fine && $fine != $data['fine']){
                Db::name('f_materials')->where(['p_amcode'=>$data['amcode']])->update(['fine'=>$data['fine']]);
            }
            $res = Db::name('basis_materials')->where(['amcode'=>$data['amcode']])->update($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if($edit_unit){
            $this->success('编辑成功，同时修改了'. ($edit_unit-1) .'个辅材单位');
        }else{
            $this->success('编辑成功');
        }
    }

    //项目公共基础库列表
    public function public_project(){
        $where = [];
        if(input('item_number')){
            $where[] = ['item_number','like','%'.input('item_number').'%'];
        }
        if(input('type_word_id')){
            $where[] = ['type_word_id','=',input('type_word_id')];
        }
        if(input('name')){
            $where[] = ['name','like','%'.input('name').'%'];
        }
        $res = Db::name('basis_project')->where($where)->order('id','asc')->paginate(20,false,['query'=>request()->param()])->each(function($item, $key){
            $item['count'] = Db::name('f_project')->where(['p_item_number'=>$item['item_number']])->field('count(id) as count')->find()['count'];
            return $item;
        });;
        //获取所有辅材细类
        $fines = Db::name('basis_materials')->field('fine,unit')->group('fine')->select();
        $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(),null,'id');
        $this->assign('data',$res);
        $this->assign('fines',$fines);
        $this->assign('type_work',$type_work);
        return $this->fetch();
    }

    public function add_public_project(){
        $datas = [];
        $datas['item_number'] = input('item_number');
        $datas['type_word_id'] = input('type_word_id');
        $datas['name'] = input('name');
        $datas['unit'] = input('unit');
        $datas['content'] = input('content');
        $find = input('find');
        $funit = input('funit');
        if(input('material')){
            $material = explode(',', str_replace('，', ',', input('material')));
            foreach($material as $k=>$v){
                $arr = explode('-', $v);
                if(isset($arr[1])){
                    $find[] = trim($arr[0]);
                    $funit[] = trim($arr[1]);
                }else{
                    $this->error('辅材分类输入错误');
                }
            }
        }
        $fine_arr = array_column(Db::name('basis_materials')->where(['fine'=>$find])->group('fine')->field('fine')->select(), 'fine');
        if ($find && count($find) != count(array_unique($find))) {   
            $this->error('辅材分类不得重复');
        } 
        //判断编号是否有重复
        $has_project = Db::name('basis_project')->where(['item_number'=>$datas['item_number']])->value('id');
        if($has_project){
            $this->error('编号已存在');
        }
        if(count($find) != count($funit)){
            $this->error('辅材参数错误');
        }
        if($find){
            $materials = [];
            foreach($find as $k=>$v){
                if(!in_array($v, $fine_arr)){
                    $this->error('辅材分类'.$v.'不存在');
                }
                $info = [];
                $info['fine'] = $v;
                $info['funit'] = $funit[$k];
                $materials[] = $info;
            }
            $datas['fine'] = json_encode($materials);
        }else{
            $datas['fine'] = '{}';
        }

        Db::startTrans();
        try {
            $res = Db::name('basis_project')->insert($datas);
            if(input('apply_project_id')){
                //分公司申请后 管理员添加的辅材 自动绑定的申请的辅材里面
                Db::name('apply_project')->where(['id'=>input('apply_project_id')])->update(['p_item_number'=>$datas['item_number'],'status'=>2,'audittime'=>time()]);
                $this->add_fwarehouse_by_apply(input('apply_project_id'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('添加成功');
    }

    //审核的报价 同时自动为分公司自动添加到仓库
    public function add_fwarehouse_by_apply($aid){
        $apply_project = Db::name('apply_project')->where(['id'=>$aid])->find();
        $basis_project = Db::name('basis_project')->where(['item_number'=>$apply_project['p_item_number']])->find();
        $data = [];
        $data['p_item_number'] = $apply_project['p_item_number'];
        // $data['item_number'] = $apply_project[''];
        $data['fid'] = $apply_project['fid'];
        $data['quota'] = $apply_project['quota']?$apply_project['quota']:0;
        $data['craft_show'] = $apply_project['craft_show']?$apply_project['craft_show']:0;
        $data['cost_value'] = $data['quota'] + $data['craft_show'];
        $data['labor_cost'] = $apply_project['labor_cost']?$apply_project['labor_cost']:0;
        //自动匹配所需辅材
        if($basis_project['fine'] && $basis_project['fine'] != '{}'){
            foreach(json_decode($basis_project['fine'],true) as $k=>$v){
                $amcode = Db::name('f_materials')->where(['fine'=>$v['fine'],'fid'=>$data['fid']])->order('id','desc')->value('amcode');
                if($amcode){
                    $data['material'][$v['fine']] = $amcode;
                }else{
                    //自己仓库没有该辅材,从大仓里面找一个
                    $basis_materials_list = Db::name('basis_materials')->where(['fine'=>$v['fine']])->order('in_warehouse','asc')->order('id','asc')->select();
                    $basis_materials = [];
                    foreach($basis_materials_list as $k1=>$v1){
                        if(strlen($v1['price']) > 0 && strlen($v1['in_price']) > 0 && $v1['pack'] && $v1['phr'] && $v1['source']){
                            $basis_materials = $basis_materials_list[$k1];
                            break;
                        }
                    }
                    if(empty($basis_materials)){
                        throw new \think\Exception('基础辅材库，辅材分类：'.$v['fine'].'未定义基础价格信息', 10006);
                    }
                    $add_f_materials = [];
                    $add_f_materials['p_amcode'] = $basis_materials['amcode'];
                    $add_f_materials['fine'] = $basis_materials['fine'];
                    // $add_f_materials['amcode'] = $basis_materials['amcode'];
                    $add_f_materials['fid'] = $data['fid'];
                    $add_f_materials['brank'] = $basis_materials['brank'];
                    $add_f_materials['place'] = $basis_materials['place'];
                    $add_f_materials['img'] = $basis_materials['img'];
                    $add_f_materials['price'] = $basis_materials['price'];
                    $add_f_materials['in_price'] = $basis_materials['in_price'];
                    $add_f_materials['pack'] = $basis_materials['pack'];
                    $add_f_materials['phr'] = $basis_materials['phr'];
                    $add_f_materials['source'] = $basis_materials['source'];
                    $add_f_materials['auto_add'] = 1;
                    $res = Db::name('f_materials')->insertGetId($add_f_materials);
                    Db::name('f_materials')->where(['id'=>$res])->update(['amcode'=>$basis_materials['amcode'].'_'.$res]);
                    $this->update_fwarehouse($basis_materials['amcode'].'_'.$res);
                    $data['material'][$v['fine']] = $basis_materials['amcode'].'_'.$res;
                }
            }
            $data['material'] = json_encode($data['material']);
        }else{
            $data['material'] = '';
        }
        $data['auto_add'] = 1;
        $fp_id = Db::name('f_project')->insertGetId($data);
        Db::name('f_project')->where(['id'=>$fp_id])->update(['item_number'=>$basis_project['item_number'].'_'.$fp_id]);
        $this->update_fproject($basis_project['item_number'].'_'.$fp_id);

    }

    //获取报价基础库所需辅材
    public function get_b_project_fine(){
        $fine = Db::name('basis_project')->where(['item_number'=>input('item_number')])->value('fine');
        if(!$fine){
            $this->success('success','',[]);
        }
        $data = json_decode($fine,true);
        if($fine){
            $this->success('success','',$data);
        }else{
            $this->success('success','',[]);
        }
    }

    public function edit_public_project(){
        $datas = [];
        $datas['item_number'] = input('item_number');
        $datas['type_word_id'] = input('type_word_id');
        $datas['name'] = input('name');
        $datas['unit'] = input('unit');
        $datas['content'] = input('content');
        $find = input('find');
        $funit = input('funit');
        if ($find && count($find) != count(array_unique($find))) {   
            $this->error('细类不得重复');
        }
        if(count($find) != count($funit)){
            $this->error('辅材参数错误');
        }
        if($find){
            $materials = [];
            foreach($find as $k=>$v){
                $info = [];
                $info['fine'] = $v;
                $info['funit'] = $funit[$k];
                $materials[] = $info;
            }
            $datas['fine'] = json_encode($materials);
        }else{
            $datas['fine'] = '{}';
        }
        Db::startTrans();
        try {
            $basis_project = Db::name('basis_project')->where(['item_number'=>$datas['item_number']])->find();
            $res = Db::name('basis_project')->where(['item_number'=>$datas['item_number']])->update($datas);
            if($basis_project['fine'] != $datas['fine']){
                $pfine = json_decode($basis_project['fine'],true);
                $pfine = array_column($pfine, 'fine');
                $dfine = json_decode($datas['fine'],true);
                $dfine = array_column($dfine, 'fine');
                sort($pfine);
                sort($dfine);
                if($pfine == $dfine){
                    $f_project = Db::name('f_project')->where(['p_item_number'=>$datas['item_number']])->select();
                    if($f_project){
                        foreach($f_project as $k=>$v){
                            $this->update_fproject($v['item_number']);
                        }
                    }
                }else{
                    //辅材修改了 需要分公司重新设置该报价
                    $rs = Db::name('f_project')->where(['p_item_number'=>$basis_project['item_number']])->update(['status'=>2]);
                    Db::name('offerquota')->where('item_number','like',$basis_project['item_number'].'_%')->delete();
                }
            }else{
                $f_project = Db::name('f_project')->where(['p_item_number'=>$datas['item_number']])->select();
                if($f_project){
                    foreach($f_project as $k=>$v){
                        $this->update_fproject($v['item_number']);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
       
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    public function del_public_project(){
        $item_number = input('item_number');
        Db::startTrans();
        try {
            Db::name('basis_project')->where(['item_number'=>$item_number])->delete();
            Db::name('f_project')->where(['p_item_number'=>$item_number])->update(['status'=>3]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('删除成功');
    }

    //获取单个基础辅材库
    public function get_public_warehouse(){
        $amcode = input('amcode');
        $info = Db::name('basis_materials')->where(['amcode'=>$amcode])->find();
        if(!$info){
            $this->error('参数错误');
        }
        $this->success('success','',$info);
    }

    //获取单个基础报价项目库
    public function get_public_project(){
        $item_number = input('item_number');
        $info = Db::name('basis_project')->where(['item_number'=>$item_number])->find();
        if($info){
            $info['type_word'] = Db::name('basis_type_work')->where(['id'=>$info['type_word_id']])->value('name');
        }
        if(!$info){
            $this->error('参数错误');
        }
        $this->success('success','',$info);
    }

    //=========================================================分公司

    //分公司添加页面 
    public function pwarehouse(){
        $where = [];
        if(input('samcode')){
            $where[] = ['amcode','like','%'.input('samcode').'%'];
        }
        if(input('type_of_work')){
            $where[] = ['type_of_work','like','%'.input('type_of_work').'%'];
        }
        if(input('fine')){
            $where[] = ['fine','like','%'.input('fine').'%'];
        }
        if(input('name')){
            $where[] = ['name','like','%'.input('name').'%'];
        }
        if(input('typeof')){
            $where[] = ['type_of_work','like','%'.input('typeof').'%'];
        }
        $neq=Db::table('fdz_basis_materials')->field('type_of_work')->group('type_of_work')->select();
        $res = Db::name('basis_materials')->where($where)->order('id','asc')->paginate(20,false,['query'=>request()->param()]);
        $p_amcode = array_column($res->items(), 'amcode');
        $p_amcode = array_column(Db::name('f_materials')->where(['p_amcode'=>$p_amcode,'fid'=>$this->_userinfo['companyid']])->field('p_amcode')->select(),'p_amcode');
        // var_dump($p_amcode);die;
        $this->assign('amcode',$p_amcode);
        $this->assign('data',$res);
        $this->assign('typeof',$neq);
        return $this->fetch();
    }

     //分公司添加页面 
    public function pproject(){
        $where = [];
        if(input('item_number')){
            $where[] = ['item_number','like','%'.input('item_number').'%'];
        }
        if(input('type_word_id')){
            $where[] = ['type_word_id','=',input('type_word_id')];
        }
        if(input('name')){
            $where[] = ['name','like','%'.input('name').'%'];
        }
        $res = Db::name('basis_project')->where($where)->order('id','asc')->paginate(20,false,['query'=>request()->param()])->each(function($item, $key){
            $fine = $item['fine'];
            $fine_info['has'] = [];
            $fine_info['nohas'] = [];
            if(!$fine || $fine == '{}'){
                $item['fine_info'] = $fine_info;
                return $item;
            }
            $fine = json_decode($fine);
            $fine = array_column($fine,'fine');
            $m_fine = Db::name('f_materials')->where(['fine'=>$fine,'fid'=>$this->_userinfo['companyid']])->group('fine')->select();
            $m_fine = array_column($m_fine,'fine');
            
            foreach($fine as $k=>$v){
                if(in_array($v, $m_fine)){
                    $fine_info['has'][] = $v;
                }else{
                    $fine_info['nohas'][] = $v;
                }
            }
            $item['fine_info'] = $fine_info;
            return $item;
        });
        //获取所有辅材细类
        $fines = Db::name('basis_materials')->field('fine,unit')->group('fine')->select();
        $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(),null,'id');

        //判断是否已添加
        $item_number = array_column($res->items(), 'item_number');
        $item_number = array_column(Db::name('f_project')->where(['p_item_number'=>$item_number,'fid'=>$this->_userinfo['companyid']])->field('p_item_number')->select(),'p_item_number');
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(),null,'id');
        $this->assign('item_number',$item_number);
        $this->assign('data',$res);
        $this->assign('fines',$fines);
        $this->assign('frame',$frame);
        $this->assign('type_work',$type_work);
        $this->assign('admininfo',$this->_userinfo);
        return $this->fetch();
    }

    //公司添加的仓库列表
    public function fwarehouse_list(){
        $where = [];
        $condition = [];
        if(input('amcode')){
            $where[] = ['amcode','like','%'.input('amcode').'%'];
        }
        if(input('fine')){
            $where[] = ['fine','like','%'.input('fine').'%'];
        }
        if(input('auto_add') || input('auto_add') === '0'){
            $where[] = ['auto_add','=',input('auto_add')];
        }
        if($this->_userinfo['roleid'] != 1){
            $where[] = ['fid','=',$this->_userinfo['companyid']];
        }else{
            if(input('fid')){
                $where[] = ['fid','=',input('fid')];
            }
        }
       

        if(input('name')){
            $name = Db::name('basis_materials')->where('name','like','%'.input('name').'%')->field('amcode')->select();
            if($name){
                $condition['p_amcode'] = array_column($name, 'amcode');
            }else{
                $condition['p_amcode'] = 0;
            }
        }
          
        $data = Db::name('f_materials')->where($condition)->where($where)->order('id','asc')->paginate(20,false,['query'=>request()->param()]);
        $p_amcode = array_unique(array_column($data->items(), 'p_amcode'));
        $basis_materials = array_column(Db::name('basis_materials')->where(['amcode'=>$p_amcode])->select(),null, 'amcode');
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(),null,'id');
        $user=$this->_userinfo;
        $this->assign('user',$user['roleid']);
        $this->assign('frame',$frame);
        $this->assign('admininfo',$this->_userinfo);
        $this->assign('basis_materials',$basis_materials);
        $this->assign('data',$data);
        return $this->fetch();
    }

    //分公司 添加辅材页面(忘记还有没有用了)
    public function add_fwarehouse(){
        $amcode = input('amcode');
        $info = Db::name('basis_materials')->where(['amcode'=>$amcode])->find();
        if(!$info){
            $this->error('参数错误');
        }
        $frame = Db::name('frame')->where('levelid',3)->field('id,name')->select();
        $this->assign('info',$info);
        $this->assign('frame',$frame);
        $this->assign('admininfo',$this->_userinfo);
        return $this->fetch();
    }
    //分公司 添加辅材操作
    public function add_fwarehouse_operation(){
        $datas = input();
        $datas['fid'] = $this->_userinfo['companyid'];
        $info = Db::name('basis_materials')->where(['amcode'=>$datas['p_amcode']])->find();
        if(!$info){
            $this->error('参数错误');
        }
        if($info['img']){
            $datas['img'] = $info['img'];
        }
        $datas['fine'] = $info['fine'];
        // var_dump($datas);die;

        Db::startTrans();
        try {
            $res = Db::name('f_materials')->insertGetId($datas);
            Db::name('f_materials')->where(['id'=>$res])->update(['amcode'=>$info['amcode'].'_'.$res]);
            $this->update_fwarehouse($info['amcode'].'_'.$res);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('添加成功','fwarehouse_list');
    }

    //分公司 修改辅材操作
    public function edit_fwarehouse_operation(){
        $datas = input();
        $datas['auto_add'] = 0;
        Db::startTrans();
        try {
            $res = Db::name('f_materials')->where(['amcode'=>$datas['amcode']])->update($datas);
            $this->update_fwarehouse($datas['amcode']);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('修改成功');
    }

    //分公司 删除辅材操作
    public function del_fwarehouse_operation(){
        $amcode = input('amcode');
        $info = Db::name('f_materials')->where(['amcode'=>$amcode])->find();
        Db::startTrans();
        try {
            // Db::name('f_project')->where('material','like','%'.$amcode.'%')->where(['fid'=>$info['fid']])->update(['status'=>2]);
            $this->update_fwarehouse($amcode,1);
            $res = Db::name('f_materials')->where(['amcode'=>$amcode])->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    //公司添加的报价项目
    public function fproject_list(){
        $where = [];
        $condition = [];
        // $where[] = ['status', 'IN', [1,2]];
        if(input('sitem_number')){
            $where[] = ['item_number','like','%'.input('sitem_number').'%'];
        } 
        if(input('sp_item_number')){
            $where[] = ['p_item_number','like','%'.input('sp_item_number').'%'];
        } 
        if($this->_userinfo['roleid'] != 1){
            $where[] = ['fid','=',$this->_userinfo['companyid']];
        }else{
            if(input('fid')){
                $where[] = ['fid','=',input('fid')];
            }
        }
        if(input('auto_add') || input('auto_add') === '0'){
            $where[] = ['auto_add','=',input('auto_add')];
        }
        if(input('sname')){
            $name = Db::name('basis_project')->where('name','like','%'.input('sname').'%')->field('item_number')->select();
            if($name){
                $condition['p_item_number'] = array_column($name, 'item_number');
            }else{
                $condition['p_item_number'] = 0;
            }
        }
        $data = Db::name('f_project')->where($condition)->where($where)->order('status','desc')->order('id','asc')->paginate(20,false,['query'=>request()->param()]);
        $p_item_number = array_unique(array_column($data->items(), 'p_item_number'));
        $basis_project = array_column(Db::name('basis_project')->where(['item_number'=>$p_item_number])->select(),null, 'item_number');
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(),null,'id');
        $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(),null,'id');
        $this->assign('admininfo',$this->_userinfo);
        $this->assign('type_work',$type_work);
        $this->assign('frame',$frame);
        $this->assign('basis_project',$basis_project);
        $this->assign('data',$data);
        return $this->fetch();
    }

    //分公司 添加辅材页面(改ajax添加了 暂时不用了)
    public function add_fproject(){
        $item_number = input('item_number');
        $info = Db::name('basis_project')->where(['item_number'=>$item_number])->find();
        if(!$info){
            $this->error('参数错误');
        }
        $frame = Db::name('frame')->where('levelid',3)->field('id,name')->select();
        //获取所需辅材细类
        if($info['fine']){
            $fine = json_decode($info['fine'],true);
            if(!$fine || !is_array($fine)){
                $this->error('该项目辅材配置有误，请联系管理员处理');
            }
            $fine = array_column($fine, 'fine');
            $basis_materials = Db::name('f_materials')->where(['fine'=>$fine])->select();
            $fmaterials = [];
            foreach($basis_materials as $k=>$v){
                $fmaterials[$v['fine']][] = $v;
            }
            if(count($fine) != count($fmaterials)){
                $this->error('该项目辅材不全，请及时补充');
            }
        }else{
            $fmaterials = [];
        }
        $this->assign('info',$info);
        $this->assign('frame',$frame);
        $this->assign('fmaterials',$fmaterials);
        $this->assign('admininfo',$this->_userinfo);
        return $this->fetch();
    }

    //分公司 添加项目操作
    public function add_fproject_operation(){
        $datas = input();
        $datas['fid'] = $this->_userinfo['companyid'];
        $info = Db::name('basis_project')->where(['item_number'=>$datas['p_item_number']])->find();
        if(!$info){
            $this->error('参数错误');
        }
        $datas['cost_value'] = $datas['quota'] + $datas['craft_show'];
        if (isset($datas['material'])) {
            $datas['material'] = json_encode($datas['material']);
        }else{
            $datas['material'] = '';
        }

        Db::startTrans();
        try {
            $res = Db::name('f_project')->insertGetId($datas);
            Db::name('f_project')->where(['id'=>$res])->update(['item_number'=>$info['item_number'].'_'.$res]);
            $this->update_fproject($info['item_number'].'_'.$res);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('添加成功','fproject_list');
    }

    //分公司在修改是否特价项目 或 是否纯人工项目
    public function edit_fproject_check(){
        $type = input('type');
        $id = input('id');
        $is_check = input('is_check');
        $f_project = Db::name('f_project')->where(['id'=>$id])->find();
        if(!$f_project){
            $this->error('参数错误');
        }
        if($is_check == 'true'){
            $is_check = 1;
        }else{
            $is_check = 0;
        }
        if($f_project[$type] === $is_check){
            $this->success('修改成功');
        }else{
            Db::startTrans();
            try {
                $res = Db::name('f_project')->where(['id'=>$id])->update([$type=>$is_check]);
                if($f_project['status'] == 1){
                    $this->update_fproject($f_project['item_number']);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('修改成功');
        }
        
    }

    //分公司 编辑项目操作
    public function edit_fproject_operation(){
        $datas = input();
        $datas['auto_add'] = 0;
        // $datas['fid'] = $this->_userinfo['companyid'];
        $info = Db::name('basis_project')->where(['item_number'=>$datas['p_item_number']])->find();

        $f_project = Db::name('f_project')->where(['id'=>$datas['id']])->find();
        if(!$info || !$f_project){
            $this->error('参数错误');
        }
        $datas['cost_value'] = $datas['quota'] + $datas['craft_show'];
        if (isset($datas['material'])) {
            $datas['material'] = json_encode($datas['material']);
        }else{
            $datas['material'] = '';
        }
        $datas['status'] = 1;
        Db::startTrans();
        try {
            $res = Db::name('f_project')->where(['id'=>$datas['id']])->update($datas);
            $this->update_fproject($f_project['item_number']);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('修改成功','fproject_list');
    }

    //删除分公司报价
    public function del_fproject(){
        $id = input('id');
        $f_project = Db::name('f_project')->where(['id'=>$id])->find();
        if(!$f_project){
            $this->error('参数错误');
        }
        try {
            $this->update_fproject($f_project['item_number'],1);
            $res = Db::name('f_project')->where(['id'=>$id])->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('删除成功');
    }

    public function create_datas(){
        $admininfo = $this->_userinfo;
        if(!input('fid')){
            $this->error('参数错误');
        }
        $fid = input('fid');
        $time = time();
        Db::startTrans();
        try {
            // 先清空原来的
            Db::name('materials')->where(['frameid'=>$fid])->delete();
            Db::name('offerquota')->where(['frameid'=>$fid])->delete();

            $materials = $this->create_materials($fid);
            Db::name('materials')->insertAll($materials);

            foreach($materials as $k=>$v){
                $materials[$k]['time'] = $time;
            }
            Db::name('materials_fb')->insertAll($materials);

            $projects = $this->create_project($fid);
            Db::name('offerquota')->insertAll($projects);
            foreach($projects as $k=>$v){
                $projects[$k]['time'] = $time;
            }
            Db::name('offerquota_fb')->insertAll($projects);
           
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('更新成功');
    }

    //生成 最终仓库数据 并保存
    public function create_materials($fid){
        $admininfo = $this->_userinfo;
        $materials = Db::name('f_materials')->where(['fid'=>$fid])->select();
        $p_amcode = array_unique(array_column($materials, 'p_amcode'));
        $basis_materials = array_column(Db::name('basis_materials')->where(['amcode'=>$p_amcode])->select(),null ,'amcode');
        $datas = [];
        foreach($materials as $k=>$v){
            if(!isset($basis_materials[$v['p_amcode']])){
                // $this->error('辅材编码'.$v['amcode'].'的基础库不存在');
                throw new \think\Exception('辅材编码'.$v['amcode'].'的基础库不存在', 10006);
            }
            $info = [];
            $info['frameid'] = $v['fid'];
            $info['userid'] = $admininfo['userid'];
            $info['amcode'] = $v['amcode'];
            $info['fine'] = $basis_materials[$v['p_amcode']]['classify'];
            $info['brand'] = $v['brank'];
            $info['place'] = $v['place'];
            $info['category'] = $basis_materials[$v['p_amcode']]['type_of_work'];
            $info['name'] = $basis_materials[$v['p_amcode']]['name'];
            //基础库还没有图片
            // if($v['img']){
            //     $info['img'] = $v['img'];
            // }else{
            //     $info['img'] = $basis_materials[$v['p_amcode']]['img'];
            // }
            $info['img'] = $v['img'];
            // $info['norms'] = $v['xxx'];
            $info['units'] = $v['phr'];
            $info['phr'] = $v['pack'].$basis_materials[$v['p_amcode']]['unit'].'/'.$v['phr'];
            $info['price'] = $v['price'];
            $info['in_price'] = $v['in_price'];
            $info['remarks'] = $v['source'];
            $info['coefficient'] = $basis_materials[$v['p_amcode']]['coefficient'];
            $info['important'] = $basis_materials[$v['p_amcode']]['important'];
            $datas[] = $info;
        }
        return $datas;
    }

    //生成 最终项目数据 并保存
    public function create_project($fid){
        $admininfo = $this->_userinfo;
        $project = Db::name('f_project')->where(['fid'=>$fid,'status'=>1])->select();
        $p_item_number = array_unique(array_column($project, 'p_item_number'));
        $basis_project = array_column(Db::name('basis_project')->where(['item_number'=>$p_item_number])->select(),null ,'item_number');
        $basis_type_work = array_column(Db::name('basis_type_work')->select(), 'name','id') ;
        $datas = [];
        foreach($project as $k=>$v){
            if(!isset($basis_project[$v['p_item_number']])){
                // $this->error('项目编号'.$v['item_number'].'找不到公共基础项目');
                throw new \think\Exception('项目编号'.$v['item_number'].'找不到公共基础项目', 10006);
            }
            $info = [];
            //计算辅材基数
            if($v['material']){
                $fine = json_decode($basis_project[$v['p_item_number']]['fine'],true);
                $fine = array_column($fine, 'funit','fine');//公式

                $material = json_decode($v['material'],true);
                $datas_material = [];
                foreach($material as $k1=>$v1){
                    // $fine[$k1] 需要的数量
                    $pack = Db::name('f_materials')->where(['amcode'=>$v1])->value('pack');//包装数量
                    if(!$pack){
                        // $this->error('项目编号'.$v['item_number'].'中,辅材编号'.$v1.'不存在');
                        throw new \think\Exception('项目编号'.$v['item_number'].'中,辅材编号'.$v1.'不存在', 10006);
                    }
                    //下面这个格式是按照之前的格式的 [对应辅材id,基数]
                    // v1 的id不对
                    $num = round($fine[$k1]/$pack,2);
                    if($num <= 0){
                        $num = 0.001;
                    }
                    $datas_material[] = [$v1,round($num,2)];
                }
                $info['content'] = json_encode($datas_material);
            }else{
                $info['content'] = '';
            }
            $info['frameid'] = $v['fid'];
            $info['userid'] = $admininfo['userid'];
            $info['item_number'] = $v['item_number'];
            $info['type_of_work'] = $basis_type_work[$basis_project[$v['p_item_number']]['type_word_id']];

            if($v['remark']){
                $info['project'] = $basis_project[$v['p_item_number']]['name'].'（'.$v['remark'].'）';
            }else{
                $info['project'] = $basis_project[$v['p_item_number']]['name'];
            }
            
            $info['company'] = $basis_project[$v['p_item_number']]['unit'];
            $info['cost_value'] = $v['cost_value'];
            $info['quota'] = $v['quota'];
            $info['craft_show'] = $v['craft_show'];
            $info['labor_cost'] = $v['labor_cost'];
            $info['material'] = $basis_project[$v['p_item_number']]['content'];
            $datas[] = $info;
        }
        return $datas;
    }

    //获取公司项目使用了什么辅材
    public function get_project_material(){
        $item_number = input('item_number');
        $material = Db::name('f_project')->where(['item_number'=>$item_number])->value('material');
        if(!$material){
            $this->error('无辅材信息');
        }
        $material = json_decode($material,true);
        if($material){
            $material = array_values($material);
            $material = Db::name('f_materials')->where(['amcode'=>$material])->select();
            $p_amcode = array_column($material, 'p_amcode');
            $p_amcode = Db::name('basis_materials')->field('id,amcode,name,unit')->where(['amcode'=>$p_amcode])->select();
            $p_amcode = array_column($p_amcode,null, 'amcode');
            foreach($material as $k=>$v){
                if(!isset($p_amcode[$v['p_amcode']])){
                    $this->error('辅材信息有误');
                }
                $material[$k]['name'] = $p_amcode[$v['p_amcode']]['name'];
            }
            $this->success('success','',$material);
        }else{
            $this->error('辅材信息有误');
        }
        //获取项目所需辅材

    }

    //根据公共基础项目库 获取需要的细类
    public function get_fine(){
        $where = [];
        if(input('item_number')){
            $where['item_number'] = input('item_number');
        }
        if(input('bp_id')){
            // $where['id'] = input('bp_id');
        }
        if(!$where){
            $this->error('参数错误'); 
        }
        $fine = Db::name('basis_project')->where($where)->value('fine');
        if(!$fine || $fine == '{}' || $fine == '[]'){
            $this->success('none');
        }
        $fine = json_decode($fine,true);
        if(!$fine || !is_array($fine)){
            $this->error('该项目辅材配置有误，请联系管理员处理');
        }
        $fine = array_column($fine, 'fine');
        $basis_materials = Db::name('f_materials')->where(['fine'=>$fine,'fid'=>$this->_userinfo['companyid']])->select();
        $datas = [];
        foreach($basis_materials as $k=>$v){
            $basis_material = Db::name('basis_materials')->where(['amcode'=>$v['p_amcode']])->find();
            if(!$basis_material){
                $this->error('基础库'.$v['p_amcode'].'不存在');
            }
            $v['name'] = $basis_material['name'];
            $v['unit'] = $basis_material['unit'];
            $datas[$v['fine']][] = $v;
        }
        if(count($fine) != count($datas)){
            $diff = array_diff($fine, array_keys($datas));
            $diff = implode('、',$diff);
            $this->error('辅材分类：'.$diff.'缺失，请及时补充');
        }
        $this->success('success','',$datas);
    }

    //导入基础辅材数据
    public function excel_public_warehouse(){
        require'../extend/PHPExcel/PHPExcel.php';
        $file = request()->file('file');
        if($file){
            $info = $file->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
            if (!$info) {
                $this->error('上传文件格式不正确');
            }else{
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                // 判断哪种类型
                if($suffix=="xlsx"){
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }

            }
            //处理表格数据
            //载入excel文件
            $excel = $reader->load("$filePath",$encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            $data = []; //数组形式获取表格数据 
            for ($i = 2; $i <= $row_num; $i ++) {
                $data[$i]['amcode']  = trim($sheet->getCell("A".$i)->getValue());
                $data[$i]['type_of_work']  = trim($sheet->getCell("B".$i)->getValue());
                $data[$i]['classify']  = trim($sheet->getCell("C".$i)->getValue());
                $data[$i]['fine']  = trim($sheet->getCell("D".$i)->getValue());
                $data[$i]['brank']  = trim($sheet->getCell("E".$i)->getValue()); 
                $data[$i]['place']  = trim($sheet->getCell("F".$i)->getValue()); 
                $data[$i]['name']  = trim($sheet->getCell("G".$i)->getValue()); 
                $data[$i]['unit']  = trim($sheet->getCell("H".$i)->getValue()); 
                $data[$i]['coefficient']  = trim($sheet->getCell("I".$i)->getValue()); 
                $data[$i]['important']  = trim($sheet->getCell("J".$i)->getValue()); 
                if(empty($data[$i]['amcode']) || empty($data[$i]['type_of_work']) || empty($data[$i]['classify']) || empty($data[$i]['fine']) || empty($data[$i]['name']) || empty($data[$i]['unit'])){
                    $this->error('第'.$i.'行数据不能为空');
                }
                if((empty($data[$i]['coefficient']) && $data[$i]['coefficient'] != '0') || (empty($data[$i]['important']) && $data[$i]['important'] != '0')){
                    $this->error('第'.$i.'行数据不能为空');
                }
            }
            //判断是否有重复编码
            $amcodes = array_column($data, 'amcode');
            if(count($amcodes) != count(array_unique($amcodes))){
                $this->error('编码重复');
            }

            $basis_materials = Db::name('basis_materials')->select();
            $data_list = array_column($data, null ,'amcode');
            $del_amcode = [];//删除的
            // $edit_amcode = [];//修改单位的
            //判断项目是否发生改变
            foreach($basis_materials as $k=>$v){
                if(isset($data_list[$v['amcode']])){
                    if($v['unit'] != $data_list[$v['amcode']]['unit']){
                        $this->error('编号'.$v['amcode'].'的单位与之前不一致');
                    }
                }else{
                    $del_amcode[] = $v['amcode'];//已删除的项目
                }
            }
            
            

            //将数据保存到数据库
            if ($data) {
            //把获取到的二维数组遍历进数据库
                Db::startTrans();
                try {
                    $this->save_basis_materials_img(1);
                    Db::name('basis_materials')->delete(true);

                    foreach ($data as $key => $value) {
                        //判断是否存在
                        $is_has = Db::name('basis_materials')->where(['fine'=>$value['fine']])->find();
                        if($is_has && $is_has['unit'] != $value['unit']){
                            // throw new \think\Exception('编号 - '.$value['amcode'].' 的分类与其他分类单位不一致', 10006);
                            throw new \think\Exception('编号 - '.$value['amcode'].' 的分类与其他分类单位不一致，应为'.$is_has['unit'], 10006);
                        }
                        Db::name('basis_materials')->insert($value);
                       
                    }
                    //修改了单位/删除了辅材  (就是对报价有影响的)
                    foreach($del_amcode as $k=>$v){
                        Db::name('f_project')->where('material','like','%'.$v.'%')->update(['status'=>2]);
                    }
                    $this->update_basis_materials_img(1);
                    Db::commit();
                }catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success('导入成功');
            }else{
                $this->error('获取导入文件数据失败');
            }
        }else{
            $this->error('请选择上传文件');
        }
    }

    //导入基础报价数据
    public function excel_public_project(){
        require'../extend/PHPExcel/PHPExcel.php';
        $file = request()->file('file');
        if($file){
            $info = $file->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
            if (!$info) {
                $this->error('上传文件格式不正确');
            }else{
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                // 判断哪种类型
                if($suffix=="xlsx"){
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }

            }
            //处理表格数据
            //载入excel文件
            $excel = $reader->load("$filePath",$encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            //获取所有工种
            $basis_type_work = array_column(Db::name('basis_type_work')->select(),null, 'name');
            $data = []; //数组形式获取表格数据 
            //获取所有辅材分类
            $basis_materials_fine = array_column(Db::name('basis_materials')->group('fine')->field('fine')->select(), 'fine');
            $arrletter = array('F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS');//辅材基数字母
            for ($i = 2; $i <= $row_num; $i ++) {
                $data[$i]['item_number']  = trim($sheet->getCell("A".$i)->getValue());

                $type_word = trim($sheet->getCell("B".$i)->getValue());
                if(isset($basis_type_work[$type_word])){
                    $data[$i]['type_word_id']  = $basis_type_work[$type_word]['id'];
                }else{
                    $this->error('第'.$i.'行，工种'.$type_word.' 不存在');
                }

                $data[$i]['name']  = trim($sheet->getCell("C".$i)->getValue());
                $data[$i]['unit']  = trim($sheet->getCell("D".$i)->getValue());
                $data[$i]['content']  = trim($sheet->getCell("E".$i)->getValue()); 

                //辅材基数对应转json数组开始
                $fine = '';
                $j = 0;
                foreach ($arrletter as $key => $value) {
                  if($j%2==0){
                    if($j === 0){
                        if($sheet->getCell($arrletter[$key].$i)->getValue()){
                            if(!$sheet->getCell($arrletter[$key+1].$i)->getValue()){
                                $this->error('第'.$i.'行,项目编号'.$data[$i]['item_number'].'所需的辅材分类数量不能为空', 10006);
                            }
                            $fine .= trim($sheet->getCell($arrletter[$key].$i)->getValue()).'-'.trim($sheet->getCell($arrletter[$key+1].$i)->getValue());
                        }
                    }else{
                        if($sheet->getCell($arrletter[$key].$i)->getValue()){
                            if(!$sheet->getCell($arrletter[$key+1].$i)->getValue()){
                                $this->error('第'.$i.'行,项目编号'.$data[$i]['item_number'].'所需的辅材分类数量不能为空', 10006);
                            }
                            $fine .= ','.trim($sheet->getCell($arrletter[$key].$i)->getValue()).'-'.trim($sheet->getCell($arrletter[$key+1].$i)->getValue());
                        }
                    }
                  }
                  $j++;
                }

                // $fine = trim($sheet->getCell("F".$i)->getValue()); 
                try {
                    $data_fine = [];
                    if($fine){
                        $fine = str_replace("，",",",$fine);
                        $fine = explode(',', $fine);
                        foreach($fine as $k=>$v){
                            $info = explode('-', $v);
                            if(!in_array($info[0], $basis_materials_fine)){
                                // $this->error('第'.$i.'行,项目编号'.$data[$i]['item_number'].'所需的辅材分类:'.$info[0].'不存在');
                                throw new \think\Exception('第'.$i.'行,项目编号'.$data[$i]['item_number'].'所需的辅材分类:'.$info[0].'不存在', 10006);
                            }
                            $data_fine[$k]['fine'] = $info[0];
                            $data_fine[$k]['funit'] = $info[1];
                        }
                    }
                }catch (\Exception $e) {
                    $this->error('第'.$i.'行，项目用料有误.  '.$e->getMessage());
                }
                $data[$i]['fine'] = json_encode($data_fine);

                
                if(empty($data[$i]['item_number']) || empty($data[$i]['type_word_id']) || empty($data[$i]['name']) || empty($data[$i]['unit']) || empty($data[$i]['content']) || empty($data[$i]['fine'])){
                    $this->error('第'.$i.'行数据不能为空');
                }
            }
            //判断是否有重复编码
            $data_list = array_column($data, null ,'item_number');
            $item_numbers = array_keys($data_list);
            if(count($item_numbers) != count(array_unique($item_numbers))){
                $this->error('编码重复');
            }
            $del_item_number = [];//删除的
            $edit_item_number = [];//修改用料的
            $basis_project = Db::name('basis_project')->select();

            //判断项目是否发生改变
            foreach($basis_project as $k=>$v){
                if(isset($data_list[$v['item_number']])){
                    if($v['fine'] != $data_list[$v['item_number']]['fine']){
                        $edit_item_number[] = $v['item_number'];//修改用料的项目
                    }
                }else{
                    $del_item_number[] = $v['item_number'];//已删除的项目
                }
            }
            //将数据保存到数据库
            if ($data) {
            //把获取到的二维数组遍历进数据库
                Db::startTrans();
                try {
                    Db::name('basis_project')->delete(true);
                    Db::name('basis_project')->insertAll($data);
                    Db::name('f_project')->where(['p_item_number'=>$edit_item_number])->update(['status'=>2]);
                    Db::name('f_project')->where(['p_item_number'=>$del_item_number])->update(['status'=>3]);
                    Db::commit();
                }catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success('导入成功');
            }else{
                $this->error('获取导入文件数据失败');
            }
        }else{
            $this->error('请选择上传文件');
        }
    }


    //没有的辅材分公司申请提交
    public function apply_new_material_index(){
        // $this->assign('data',$res);
        $where = [];
        if($this->_userinfo['userid'] == 1){
            if(input('fid')){
                $where['fid'] = input('fid');
            }
        }else{
            $where['fid'] = $this->_userinfo['companyid'];
        }
        if(input('status')){
            $where['status'] = input('status');
        }
        $wheres=[];
        if (!empty(input('material'))) {
            $wheres[] = ['name','like',"%".input('material')."%"];
        }
        $datas = Db::name('apply_material')->where($where)->where($wheres)->order('status','asc')->order('id','desc')->paginate(20,false,['query'=>request()->param()]);

        //判断是否已添加
        $amcode = array_column($datas->items(), 'p_amcode');
        $condintion = [];
        $condintion['p_amcode'] = $amcode;
        if(isset($where['fid'])){
            $condintion['fid'] = $where['fid'];
        }
        $p_amcode = array_column(Db::name('f_materials')->where($condintion)->field('p_amcode')->select(),'p_amcode');


        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(),null,'id');
        $this->assign('admininfo',$this->_userinfo);
        $this->assign('frame',$frame);
        $this->assign('p_amcode',$p_amcode);
        $this->assign('datas',$datas);
        return $this->fetch();
    }

    //ajax申请辅材
    public function apply_new_material(){
        $name = input('name');
        $brank = input('brank');
        $place = input('place');
        $unit = input('unit');
        $price = input('price');
        $in_price = input('in_price');
        $pack = input('pack');
        $phr = input('phr');
        $source = input('source');
        $warehouse_id = input('warehouse_id');
        array_shift($name);
        array_shift($brank);
        array_shift($place);
        array_shift($unit);
        array_shift($price);
        array_shift($in_price);
        array_shift($pack);
        array_shift($phr);
        array_shift($source);
        array_shift($warehouse_id);
        if(empty($name) || empty($brank) || empty($place) || empty($unit) || empty($price) || empty($in_price) || empty($pack) || empty($phr) || empty($source)){
            $this->error('添加辅材信息不能为空');
        }
        // if(count($name) != count(array_unique($name))){
        //     // $this->error('名字重复');
        // }
        $insert_datas = [];
        foreach($name as $k=>$v){
            if(!(empty($v) && empty($unit[$k]) && empty($price[$k]) && empty($in_price[$k]) && empty($pack[$k]) && empty($phr[$k]) && empty($source[$k]))){
                if(empty($v) || empty($unit[$k]) ||  empty($pack[$k]) || empty($phr[$k]) || empty($source[$k])){
                    $this->error('第'.($k+1).'行数据不能为空');
                }
                $price[$k] = trim($price[$k]);
                $in_price[$k] = trim($in_price[$k]);
                if(!is_numeric($price[$k]) || !is_numeric($in_price[$k])){
                    $this->error('第'.($k+1).'行价格输入有误');
                }
                if(strlen($price[$k]) == 0 || strlen($in_price[$k]) == 0){
                    $this->error('第'.($k+1).'行数据不能为空');
                }
                $insert_datas[$k]['fid'] = $this->_userinfo['companyid'];
                $insert_datas[$k]['name'] = $v;
                $insert_datas[$k]['brank'] = $brank[$k]?$brank[$k]:'';
                $insert_datas[$k]['place'] = $place[$k]?$place[$k]:'';
                $insert_datas[$k]['unit'] = $unit[$k];
                $insert_datas[$k]['price'] = $price[$k];
                $insert_datas[$k]['in_price'] = $in_price[$k];
                $insert_datas[$k]['pack'] = $pack[$k];
                $insert_datas[$k]['phr'] = $phr[$k];
                $insert_datas[$k]['source'] = $source[$k];
                $insert_datas[$k]['warehouse_id'] = $warehouse_id[$k];
            }
        }
        if(empty($insert_datas)){
            $this->error('申请数据为空');
        }
        $res = Db::name('apply_material')->insertAll($insert_datas);
        if($res){
            $this->success('申请成功，请等待回复');
        }else{
            $this->error('申请失败');
        }
    }

    //ajax删除申请辅材
    public function del_new_material(){
        $id = input('id');
        $has = Db::name('apply_material')->where(['id'=>$id,'fid'=>$this->_userinfo['companyid']])->find();
        if($has){
            $del = Db::name('apply_material')->where(['id'=>$id,'fid'=>$this->_userinfo['companyid']])->delete();
        }else{
            $del = false;
        }
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    //ajax删除申请辅材
    public function del_new_project(){
        $id = input('id');
        $has = Db::name('apply_project')->where(['id'=>$id,'fid'=>$this->_userinfo['companyid']])->find();
        if($has){
            $del = Db::name('apply_project')->where(['id'=>$id,'fid'=>$this->_userinfo['companyid']])->delete();
        }else{
            $del = false;
        }
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    //没有的 项目 分公司申请提交
    public function apply_new_project_index(){
        // $this->assign('data',$res);
        $where = [];
        if($this->_userinfo['userid'] == 1){
            if(input('fid')){
                $where['fid'] = input('fid');
            }
        }else{
            $where['fid'] = $this->_userinfo['companyid'];
        }
        if(input('status')){
            $where['status'] = input('status');
        }
        $wheres=[];
        if (!empty(input('material'))) {
            $wheres[] = ['name','like',"%".input('material')."%"];
        }
        $datas = Db::name('apply_project')->where($where)->where($wheres)->order('status','asc')->order('id','desc')->paginate(20,false,['query'=>request()->param()])->each(function($item, $key){
            if($item['status'] == 1){
                return $item;
            }
            $basis_project = Db::name('basis_project')->where(['item_number'=>$item['p_item_number']])->find();
            $fine = $basis_project['fine'];
            $fine_info['has'] = [];
            $fine_info['nohas'] = [];
            if(!$fine || $fine == '{}'){
                $item['fine_info'] = $fine_info;
                return $item;
            }
            $fine = json_decode($fine);
            $fine = array_column($fine,'fine');
            $m_fine = Db::name('f_materials')->where(['fine'=>$fine,'fid'=>$item['fid']])->group('fine')->select();
            $m_fine = array_column($m_fine,'fine');
            
            foreach($fine as $k=>$v){
                if(in_array($v, $m_fine)){
                    $fine_info['has'][] = $v;
                }else{
                    $fine_info['nohas'][] = $v;
                }
            }
            $item['fine_info'] = $fine_info;
            return $item;
        });
        //判断是否已添加
        $item_number = array_column($datas->items(), 'p_item_number');
        $condintion = [];
        $condintion['p_item_number'] = $item_number;
        if(isset($where['fid'])){
            $condintion['fid'] = $where['fid'];
        }
        $p_item_number = array_column(Db::name('f_project')->where($condintion)->field('p_item_number')->select(),'p_item_number');

        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(),null,'id');
        $type_work = array_column(Db::name('basis_type_work')->field('id,name')->select(),null,'id');//获取所有辅材细类
        $fines = Db::name('basis_materials')->field('fine,unit')->group('fine')->select();
        $this->assign('admininfo',$this->_userinfo);
        $this->assign('frame',$frame);
        $this->assign('type_work',$type_work);
        $this->assign('fines',$fines);
        $this->assign('p_item_number',$p_item_number);
        $this->assign('datas',$datas);
        return $this->fetch();
    }

    //ajax申请项目
    public function apply_new_project(){
        $name = input('name');
        $content = input('content');
        $unit = input('unit');
        $material = input('material');
        $quota = input('quota');
        $craft_show = input('craft_show');
        $labor_cost = input('labor_cost');
        array_shift($name);
        array_shift($content);
        array_shift($unit);
        array_shift($material);
        array_shift($quota);
        array_shift($craft_show);
        array_shift($labor_cost);
        if(empty($name) || empty($content) || empty($unit) || empty($material) || empty($quota) || empty($craft_show) || empty($labor_cost)){
            $this->error('参数有误');
        }
        $insert_datas = [];
        foreach($name as $k=>$v){
            if (!(empty($v) && empty($content[$k]) && empty($unit[$k]) && empty($material[$k]) && empty($quota[$k]) && empty($craft_show[$k]) && empty($labor_cost[$k]))) {
                if (empty($v) || empty($content[$k]) || empty($unit[$k])) {
                    $this->error('第'.($k+1).'行数据不能为空');
                }

                $quota[$k] = trim($quota[$k]);
                $craft_show[$k] = trim($craft_show[$k]);
                $labor_cost[$k] = trim($labor_cost[$k]);
                if(strlen($quota[$k]) == 0 || strlen($craft_show[$k]) == 0 || strlen($labor_cost[$k]) == 0){
                    $this->error('第'.($k+1).'行数据不能为空');
                }
                if(!is_numeric($quota[$k]) || !is_numeric($craft_show[$k]) || !is_numeric($labor_cost[$k])){
                    $this->error('第'.($k+1).'行价格输入有误');
                }
                $insert_datas[$k]['fid'] = $this->_userinfo['companyid'];
                $insert_datas[$k]['name'] = $v;
                $insert_datas[$k]['content'] = $content[$k];
                $insert_datas[$k]['unit'] = $unit[$k];
                $insert_datas[$k]['material'] = $material[$k];
                $insert_datas[$k]['quota'] = $quota[$k];
                $insert_datas[$k]['craft_show'] = $craft_show[$k];
                $insert_datas[$k]['labor_cost'] = $labor_cost[$k];
            }
        }
        if(empty($insert_datas)){
            $this->error('申请数据为空');
        }
        $res = Db::name('apply_project')->insertAll($insert_datas);
        if($res){
            $this->success('申请成功，请等待回复');
        }else{
            $this->error('申请失败');
        }
    }

    //绑定辅材
    public function bind_material(){
        $id = input('id');
        $amcode = input('amcode');
        $basis_materials = Db::name('basis_materials')->where(['amcode'=>$amcode])->find();
        if(!$basis_materials){
            $this->error('未找到绑定的辅材');
        }
        $apply_material = Db::name('apply_material')->where(['id'=>$id])->find();

        Db::startTrans();
        try{
            $res = Db::name('apply_material')->where(['id'=>$id])->update(['audittime'=>time(),'p_amcode'=>$amcode,'status'=>2]);
            //判断仓库是否已添加该辅材
            $is_has = Db::name('f_materials')->where(['p_amcode'=>$amcode,'fid'=>$apply_material['fid']])->find();
            if(!$is_has){
                if(strlen($apply_material['price']) == 0 && strlen($basis_materials['price']) == 0){
                    throw new \think\Exception('该基础辅材库未定义出库价', 10006);
                }
                if(strlen($apply_material['in_price']) == 0 && strlen($basis_materials['in_price']) == 0){
                    throw new \think\Exception('该基础辅材库未定义进库价', 10006);
                }
                if(empty($apply_material['pack']) && empty($basis_materials['pack'])){
                     throw new \think\Exception('该基础辅材库未定义包装数量', 10006);
                }
                if(empty($apply_material['phr']) && empty($basis_materials['phr'])){
                     throw new \think\Exception('该基础辅材库未定义出库单位', 10006);
                }
                if(empty($apply_material['source']) && empty($basis_materials['source'])){
                     throw new \think\Exception('该基础辅材库未定义来源', 10006);
                }
                $f_materials = [];
                $f_materials['p_amcode'] = $basis_materials['amcode'];
                $f_materials['fine'] = $basis_materials['fine'];
                $f_materials['fid'] = $apply_material['fid'];
                $f_materials['brank'] = $apply_material['brank'];
                $f_materials['place'] = $apply_material['place'];
                $f_materials['img'] = $basis_materials['img'];
                $f_materials['price'] = $apply_material['price']?$apply_material['price']:$basis_materials['price'];
                $f_materials['in_price'] = $apply_material['in_price']?$apply_material['in_price']:$basis_materials['in_price'];
                $f_materials['pack'] = $apply_material['pack']?$apply_material['pack']:$basis_materials['pack'];
                $f_materials['phr'] = $apply_material['phr']?$apply_material['phr']:$basis_materials['phr'];
                $f_materials['source'] = $basis_materials['source'];
                $f_materials['warehouse_id'] = $apply_material['warehouse_id']?$apply_material['warehouse_id']:$basis_materials['warehouse_id'];
                $f_materials['auto_add'] = 1;
                $res = Db::name('f_materials')->insertGetId($f_materials);
                Db::name('f_materials')->where(['id'=>$res])->update(['amcode'=>$basis_materials['amcode'].'_'.$res]);
                $this->update_fwarehouse($basis_materials['amcode'].'_'.$res);
            }
            Db::commit();
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('绑定成功');
    }

    //绑定项目
    public function bind_project(){
        $id = input('id');
        $item_number = input('item_number');
        $basis_project = Db::name('basis_project')->where(['item_number'=>$item_number])->find();
        if(!$basis_project){
            $this->error('未找到绑定的报价项目');
        }
        $fid = Db::name('apply_project')->where(['id'=>$id])->value('fid');

        Db::startTrans();
        try{
            $res = Db::name('apply_project')->where(['id'=>$id])->update(['audittime'=>time(),'p_item_number'=>$item_number,'status'=>2]);
            //查看绑定的项目,自己仓库是否已存在, 不存在则自动匹配一个添加上去
            $is_has = Db::name('f_project')->where(['p_item_number'=>$item_number,'fid'=>$fid])->find();
            if(!$is_has){
                $this->add_fwarehouse_by_apply($id);
            }
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('绑定成功');
    }

    public function img(Request $request){
        $data =$request->post();
        if($_FILES['image']['error'] !=4) {
            $file = request()->file('image');
            if($file){
                $info = $file->validate(['size'=>1048576])->move( './uploads/images');
                if($info){
                    // 成功上传后 获取上传信息
                    $images = $info->getSaveName();
                    $images = str_replace('\\', '/', $images);
                    $res=  Db::name('f_materials')->where('id', $data['id'])->data(['img' => $images])->update();
                    $this->success('上传成功');
                }else{
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
        }else{
            $this->error('图片未上传');
        }

    }
//辅材上传图片

    public function uploading(Request $request){
        $data =$request->post();
//        dump($data);die;
        if($_FILES['image']['error'] !=4) {
            $file = request()->file('image');
            if($file){
                $info = $file->validate(['size'=>1048576])->move( './uploads/images');
                if($info){
                    // 成功上传后 获取上传信息
                    $images = $info->getSaveName();
                    $images = str_replace('\\', '/', $images);
                    $res=  Db::name('basis_materials')->where('id', $data['id'])->data(['img' => $images])->update();
                    $this->success('上传成功');
                }else{
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
        }else{
            $this->error('图片未上传');
        }

    }

    public function export(){
        //1.从数据库中取出数据
        $data = Db::table('fdz_basis_project')->select();
        $type_word_ids = array_unique(array_column($data,'type_word_id'));
        $basis_type_work = Db::table('fdz_basis_type_work')->where(['id'=>$type_word_ids])->select();
        $basis_type_work = array_column($basis_type_work,null,'id');
        foreach ($data as $k=>$v){
            $data[$k]['word'] = $basis_type_work[$v['type_word_id']]['name'];
        }
//        dump($data);die;
        //2.加载PHPExcle类库
        require '../extend/PHPExcel/PHPExcel.php';
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '编码')
            ->setCellValue('B1', '工种类别')
            ->setCellValue('C1', '项目名称')
            ->setCellValue('D1', '单位')
            ->setCellValue('E1', ' 施工工艺与说明')
            ->setCellValue('F1', ' 用料1')
            ->setCellValue('G1', ' 数量1')
            ->setCellValue('H1', ' 用料2')
            ->setCellValue('I1', ' 数量2')
            ->setCellValue('J1', ' 用料3')
            ->setCellValue('K1', ' 数量3')
            ->setCellValue('L1', ' 用料4')
            ->setCellValue('M1', ' 数量4')
            ->setCellValue('N1', ' 用料5')
            ->setCellValue('O1', ' 数量5')
            ->setCellValue('P1', ' 用料6')
            ->setCellValue('Q1', ' 数量6')
            ->setCellValue('R1', ' 用料7')
            ->setCellValue('S1', ' 数量7')
            ->setCellValue('T1', ' 用料8')
            ->setCellValue('U1', ' 数量8')
            ->setCellValue('V1', ' 用料9')
            ->setCellValue('W1', ' 数量9')
            ->setCellValue('X1', ' 用料10')
            ->setCellValue('Y1', ' 数量10')
            ->setCellValue('Z1', ' 用料11')
            ->setCellValue('AA1', ' 数量11')
            ->setCellValue('AB1', ' 用料12')
            ->setCellValue('AC1', ' 数量12')
            ->setCellValue('AD1', ' 用料13')
            ->setCellValue('AE1', ' 数量13')
            ->setCellValue('AF1', ' 用料14')
            ->setCellValue('AG1', ' 数量14')
            ->setCellValue('AH1', ' 数量15')
            ->setCellValue('AI1', ' 数量15');
        //设置F列水平居中
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置单元格宽度
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(30);
        $arrletter = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS');//辅材基数字母
        //6.循环刚取出来的数组，将数据逐一添加到excel表格。

        $con = Db::view('basis_project', 'type_word_id')
            ->view('basis_type_work', 'name', 'basis_project.type_word_id=basis_type_work.id')
            ->select();
//        dump($con);die;
        foreach ($data as $k => $v) {
            $data[$k]['js'] = json_decode($v['fine'],true);
        }
//        dump($data);die;
//        dump($data);die;
        for($i=0;$i<count($data);$i++){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$data[$i]['item_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$data[$i]['word']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$data[$i]['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$data[$i]['unit']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$data[$i]['content']);
            if(is_array($data[$i]['js'])){
                $j = 0;
                foreach($data[$i]['js'] as $k=>$v){
                    $objPHPExcel->getActiveSheet()->setCellValue($arrletter[$j].($i+2),$v['fine']);
                    $j++;
                    $objPHPExcel->getActiveSheet()->setCellValue($arrletter[$j].($i+2),$v['funit']);
                    $j++;
                }
            }


        }
//        var_dump($data);die;

        //7.设置保存的Excel表格名称
        $filename = '报价信息'.date('ymd',time()).'.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('学生信息');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
    }

    //导出基础库报价 临时的
    public function export1(){
        //1.从数据库中取出数据
        $data = Db::table('fdz_basis_project')->select();
        $type_word_ids = array_unique(array_column($data,'type_word_id'));
        $basis_type_work = Db::table('fdz_basis_type_work')->where(['id'=>$type_word_ids])->select();
        $basis_type_work = array_column($basis_type_work,null,'id');
        foreach ($data as $k=>$v){
            $data[$k]['word'] = $basis_type_work[$v['type_word_id']]['name'];
        }
//        dump($data);die;
        //2.加载PHPExcle类库
        require '../extend/PHPExcel/PHPExcel.php';
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '编码')
            ->setCellValue('B1', '工种类别')
            ->setCellValue('C1', '项目名称')
            ->setCellValue('D1', '单位')
            ->setCellValue('E1', ' 施工工艺与说明')
            ->setCellValue('F1', ' 用料1');
        //设置F列水平居中
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置单元格宽度
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(30);
        $arrletter = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS');//辅材基数字母
        //6.循环刚取出来的数组，将数据逐一添加到excel表格。

        $con = Db::view('basis_project', 'type_word_id')
            ->view('basis_type_work', 'name', 'basis_project.type_word_id=basis_type_work.id')
            ->select();
//        dump($con);die;
        // foreach ($data as $k => $v) {
        //     $data[$k]['js'] = json_decode($v['fine'],true);
        // }
//        dump($data);die;
//        dump($data);die;
        for($i=0;$i<count($data);$i++){
            $fine = $data[$i]['fine'];
            $str = '';
            if($fine && !empty($fine)){
                $fine = json_decode($data[$i]['fine'],true);
                foreach($fine as $k1=>$v1){
                    foreach($v1 as $k2=>$v2){
                        if($k2 == 'funit'){
                            $str .= ' - ' . $v2 . '，';
                        }else{
                            $str .= $v2;
                        }
                    }
                }
            }
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$data[$i]['item_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$data[$i]['word']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$data[$i]['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$data[$i]['unit']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$data[$i]['content']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($i+2),$str);
        }
        //7.设置保存的Excel表格名称
        $filename = '报价信息'.date('ymd',time()).'.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('学生信息');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
    }

    //导出申请的报价 临时的
    public function export2(){
        //1.从数据库中取出数据
        $data = Db::name('apply_project')->where(['status'=>1])->order('fid','asc')->select();
        $frame = array_column(Db::name('frame')->where('levelid',3)->field('id,name')->select(), null,'id');
//        dump($data);die;
        //2.加载PHPExcle类库
        require '../extend/PHPExcel/PHPExcel.php';
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '项目名称')
            ->setCellValue('B1', '单位')
            ->setCellValue('C1', '施工工艺')
            ->setCellValue('D1', '所需辅材')
            ->setCellValue('E1', '分公司');
        //设置F列水平居中
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置单元格宽度
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(30);
        $arrletter = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS');//辅材基数字母
        //6.循环刚取出来的数组，将数据逐一添加到excel表格。
        for($i=0;$i<count($data);$i++){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$data[$i]['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$data[$i]['unit']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$data[$i]['content']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$data[$i]['material']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$frame[$data[$i]['fid']]['name']);
        }
        //7.设置保存的Excel表格名称
        $filename = '报价信息'.date('ymd',time()).'.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('学生信息');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
    }


    public function excel()
    {

        $data = Db::table('fdz_basis_materials')->select();
        //2.加载PHPExcle类库
        require '../extend/PHPExcel/PHPExcel.php';
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        // Add title
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '辅材编码')
            ->setCellValue('B1', '工种类别')
            ->setCellValue('C1', '辅材细类')
            ->setCellValue('D1', '分类')
            ->setCellValue('E1', ' 品牌')
            ->setCellValue('F1', '产地')
            ->setCellValue('G1', '辅材名称')
            ->setCellValue('H1', '最小单位')
            ->setCellValue('I1', '系数')
            ->setCellValue('J1', '是否重要(0:不是,1:是)');
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('基础辅材报表');

        $i = 2;
        foreach ($data as $rs) {
            // Add data
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $i, $rs['amcode'])
                ->setCellValue('B' . $i, $rs['type_of_work'])
                ->setCellValue('C' . $i, $rs['classify'])
                ->setCellValue('D' . $i, $rs['fine'])
                ->setCellValue('E' . $i, $rs['brank'])
                ->setCellValue('F' . $i, $rs['place'])
                ->setCellValue('G' . $i, $rs['name'])
                ->setCellValue('H' . $i, $rs['unit'])
                ->setCellValue('I' . $i, $rs['coefficient'])
                ->setCellValue('J' . $i, $rs['important']);
            $i++;
        }

        //7.设置保存的Excel表格名称
        $filename = '辅材信息'.date('ymd',time()).'.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('学生信息');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;

    }

  


    //更新导入图片
    public function update_img(){
        $file = "./uploads/update_img";//存储图片的文件
        $target_file = "./uploads/images/".date('Ymd');//储存目标文件夹
        $temp = scandir($file);
        if (file_exists($target_file) == false){
            if (!mkdir($target_file, 0755, true)) {
                $this->error('创建文件夹'.$target_file.'失败');
            }
        }
        
        foreach($temp as $k=>$v){
            if($k <= 1){
                continue;
            }
            if(@copy($file.'/'.$v,$target_file.'/'.$v)){
                $amcode = explode('.', $v)[0];
                Db::name('basis_materials')->where(['amcode'=>$amcode])->update(['img'=>date('Ymd').'/'.$v]);
            }
        }
        $this->success('success');
    }

    //另建一个表 储存basis_materials的图片
    //把basis_materials的图片存到basis_materials_other
    public function save_basis_materials_img($type="1"){
        $basis_materials = Db::name('basis_materials')->order('id','asc')->select();
        foreach($basis_materials as $k=>$v){
            if(!$v['img']){
                continue;
            }
            $basis_materials_other_info = Db::name('basis_materials_other')->where(['amcode'=>$v['amcode']])->find();
            if($basis_materials_other_info){
                if($v['img'] != $basis_materials_other_info['img']){
                    Db::name('basis_materials_other')->where(['amcode'=>$v['amcode']])->update(['img'=>$v['img']]);
                }
            }else{
                Db::name('basis_materials_other')->insert(['amcode'=>$v['amcode'],'img'=>$v['img']]);
            }
        }
        if($type){
            return 1;
        }
        $this->success('成功');
    }
    //吧basis_materials_other的图片存到basis_materials
    public function update_basis_materials_img($type="1"){
        $basis_materials_other = Db::name('basis_materials_other')->select();
        foreach($basis_materials_other as $k=>$v){
            if(!$v['img']){
                continue;
            }
            $basis_materials = Db::name('basis_materials')->where(['amcode'=>$v['amcode']])->find();
            if($basis_materials){
                if($v['img'] != $basis_materials['img']){
                    Db::name('basis_materials')->where(['amcode'=>$v['amcode']])->update(['img'=>$v['img']]);
                }
            }
        }
        if($type){
            return 1;
        }
        $this->success('成功');
    }


    public function excel_f_warehouse(){
        require'../extend/PHPExcel/PHPExcel.php';
        $f_project = Db::name('f_project')->where(['fid'=>$this->_userinfo['companyid']])->find();
        if($f_project){
            $this->error('新增定额数据后，此功能将会无法使用');
        }
        $file = request()->file('file');
        if($file){
            $info = $file->validate(['ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
            if (!$info) {
                $this->error('上传文件格式不正确');
            }else{
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                // 判断哪种类型
                if($suffix=="xlsx"){
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }
            }
            //处理表格数据
            //载入excel文件
            $excel = $reader->load("$filePath",$encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            $data = []; //数组形式获取表格数据 
            for ($i = 2; $i <= $row_num; $i ++) {
                $info = [];
                $info['p_amcode']  = trim($sheet->getCell("A".$i)->getValue());
                
                
                $info['fid']  = $this->_userinfo['companyid'];
                $info['brank']  = trim($sheet->getCell("C".$i)->getValue());
                $info['place']  = trim($sheet->getCell("D".$i)->getValue()); 
                $info['in_price']  = trim($sheet->getCell("E".$i)->getValue()); 
                $info['price']  = trim($sheet->getCell("F".$i)->getValue()); 
                $info['pack']  = trim($sheet->getCell("G".$i)->getValue()); 
                $info['phr']  = trim($sheet->getCell("I".$i)->getValue()); 
                $info['source']  = trim($sheet->getCell("J".$i)->getValue()); 
                $info['status']  = 1;
                $info['warehouse_id']  = trim($sheet->getCell("K".$i)->getValue());


                if( empty($info['in_price']) && empty($info['price']) && empty($info['pack']) && empty($info['phr']) && empty($info['source'])){
                    //没有填资料 默认不上传
                    continue;
                }
                if( !is_numeric($info['pack']) || $info['pack'] <= 0){
                    //有添数据,但是没填完整
                    $this->error('第'. ($i) .'行，包装数量必须为数字');
                }
                if( empty($info['pack']) || empty($info['phr']) || empty($info['source']) ){
                    //有添数据,但是没填完整
                    $this->error('第'. ($i) .'行，数据不能留空');
                }

                if(empty($info['brank']) || empty($info['place'])){
                    $this->error('第'. ($i) .'行，数据不能为空');
                }

                if(!is_numeric($info['in_price']) || !is_numeric($info['price']) || $info['in_price'] < 0 || $info['price'] < 0 ){
                    $this->error('第'. ($i) .'行，价格格式错误');
                }

                if($info['source'] != '公司仓库' && $info['source'] != '公司定点' && $info['source'] != '监理自购'){
                    $this->error('第'. ($i) .'行，材料来源必须为 公司仓库 或 公司定点 或 监理自购');
                }

                $basis = Db::name('basis_materials')->where(['amcode'=>$info['p_amcode']])->find();
                if(!$basis){
                    $this->error('第'. ($i) .'行，编码'.$info['p_amcode'].'不存在');
                }
                $info['fine']  = $basis['fine'];
                $info['img']  = $basis['img'];
                $data[] = $info;
            }

            //将数据保存到数据库
            if ($data) {
            //把获取到的二维数组遍历进数据库
                Db::startTrans();
                try{
                    Db::name('f_materials')->where(['fid'=>$this->_userinfo['companyid']])->delete();
                    foreach($data as $k=>$v){
                        $id = Db::name('f_materials')->insertGetId($v);
                        Db::name('f_materials')->where(['id'=>$id])->update(['amcode'=>$v['p_amcode'].'_'.$id]);
                    }
                    Db::commit();
                }catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success('导入成功');
            }else{
                $this->error('获取导入文件数据失败');
            }
        }else{
            $this->error('请选择上传文件');
        }
    }

    //新增 删除 修改 分公司辅材 及时更新到线上    以后加到model里面
    public function update_fwarehouse($amcode,$is_del=0){
        // return true;
        $f_materials = Db::name('f_materials')->where(['amcode'=>$amcode])->find();
        $f_project = Db::name('f_project')->where('material','like','%"'.$amcode.'"%')->select();
        if(!$f_materials){
            throw new \think\Exception('分公司辅材库有误', 10006);
        }
        if($is_del == 1){
            if($f_project){
                $item_numbers = implode(',', array_column($f_project, 'item_number'));
                throw new \think\Exception('项目编号：'.$item_numbers.'已使用该辅材，请修改对应项目后再删除', 10006);
            }
            Db::name('materials')->where(['amcode'=>$amcode])->delete();
            return true;
        }
        $basis_materials = Db::name('basis_materials')->where(['amcode'=>$f_materials['p_amcode']])->find();
        if(!$basis_materials){
            throw new \think\Exception('辅材基础库有误', 10006);
        }
        $info = [];
        $info['frameid'] = $f_materials['fid'];
        $info['userid'] = $this->_userinfo['userid'];
        $info['amcode'] = $f_materials['amcode'];
        $info['fine'] = $basis_materials['classify'];
        $info['brand'] = $f_materials['brank'];
        $info['place'] = $f_materials['place'];
        $info['category'] = $basis_materials['type_of_work'];
        $info['name'] = $basis_materials['name'];
        $info['img'] = $f_materials['img'];
        $info['units'] = $f_materials['phr'];
        $info['phr'] = $f_materials['pack'].$basis_materials['unit'].'/'.$f_materials['phr'];
        $info['price'] = $f_materials['price'];
        $info['in_price'] = $f_materials['in_price'];
        $info['remarks'] = $f_materials['source'];
        $info['coefficient'] = $basis_materials['coefficient'];
        $info['important'] = $basis_materials['important'];
        $info['auto_add'] = $f_materials['auto_add'];
        $materials = Db::name('materials')->where(['amcode'=>$info['amcode']])->find();
        if($f_project){
            foreach($f_project as $k=>$v){
                $this->update_fproject($v['item_number']);
            }
        }
        if($materials){
            //已有了 就修改
            Db::name('materials')->where(['amcode'=>$info['amcode']])->update($info);
        }else{
            Db::name('materials')->insert($info);
        }
    }

    //新增 删除 修改 分公司报价 及时更新到线上    以后加到model里面
    public function update_fproject($item_number,$is_del=0){
        $f_project = Db::name('f_project')->where(['item_number'=>$item_number])->find();
        if(!$f_project){
            throw new \think\Exception('分公司项目库库有误', 10006);
        }
        if($is_del == 1){
            Db::name('offerquota')->where(['item_number'=>$item_number])->delete();
            return true;
        }
        $basis_project = Db::name('basis_project')->where(['item_number'=>$f_project['p_item_number']])->find();
        if(!$basis_project){
            throw new \think\Exception('项目编码'.$f_project['p_item_number'].'不存在', 10006);
        }
        $basis_type_work = array_column(Db::name('basis_type_work')->select(), 'name','id') ;
        $info = [];
        //计算辅材基数
        if($f_project['material']){
            $fine = json_decode($basis_project['fine'],true);
            $fine = array_column($fine, 'funit','fine');//公式

            $material = json_decode($f_project['material'],true);
            $datas_material = [];
            foreach($material as $k1=>$v1){
                // $fine[$k1] 需要的数量
                $pack = Db::name('f_materials')->where(['amcode'=>$v1])->value('pack');//包装数量
                if(!$pack){
                    // $this->error('项目编号'.$f_project['item_number'].'中,辅材编号'.$v1.'不存在');
                    throw new \think\Exception('项目编号'.$f_project['item_number'].'中,辅材编号'.$v1.'不存在', 10006);
                }
                if(!is_numeric($pack) || $pack == 0){
                    // $this->error('项目编号'.$f_project['item_number'].'中,辅材编号'.$v1.'不存在');
                    throw new \think\Exception('项目编号'.$f_project['item_number'].'中,辅材编号'.$v1.'包装数量错误', 10006);
                }

                //下面这个格式是按照之前的格式的 [对应辅材id,基数]
                // v1 的id不对
                $num = round($fine[$k1]/$pack,2);
                if($num <= 0){
                    $num = 0.001;
                }
                $datas_material[] = [$v1,round($num,2)];
            }
            $info['content'] = json_encode($datas_material);
        }else{
            $info['content'] = '';
        }
        $info['frameid'] = $f_project['fid'];
        $info['userid'] = $this->_userinfo['companyid'];
        $info['item_number'] = $f_project['item_number'];
        $info['type_of_work'] = $basis_type_work[$basis_project['type_word_id']];

        if(!empty(trim($f_project['remark']))){
            $info['project'] = $f_project['remark'];
        }else{
            $info['project'] = $basis_project['name'];
        }
        if(!empty(trim($f_project['content']))){
            $info['material'] = $f_project['content'];
        }else{
            $info['material'] = $basis_project['content'];
        }
        
        $info['company'] = $basis_project['unit'];
        $info['cost_value'] = $f_project['cost_value'];
        $info['quota'] = $f_project['quota'];
        $info['craft_show'] = $f_project['craft_show'];
        $info['labor_cost'] = $f_project['labor_cost'];
        // $info['material'] = $basis_project['content'];
        $info['is_special'] = $f_project['is_special'];
        $info['is_artificial'] = $f_project['is_artificial'];
        $offerquota = Db::name('offerquota')->where(['item_number'=>$info['item_number']])->find();
        if($offerquota){
            //已有了 就修改
            Db::name('offerquota')->where(['item_number'=>$info['item_number']])->update($info);
        }else{
            Db::name('offerquota')->insert($info);
        }
    }

    public function auxiliaryderive(Request $request)
    {

        $da=$request->get();
        if(empty($da['id'])){
            $data = Db::name('f_materials')->select();
            $frame='全部数据';
        }else{
            $frame=Db::table('fdz_frame')->where('id',$da['id'])->value('name');
            $data = Db::name('f_materials')->where('fid',$da['id'])->select();
        }
        foreach ($data as $k=>$v)
        {
            $data[$k]['type_of_work']=Db::table('fdz_basis_materials')->where('amcode',$v['p_amcode'])->value('type_of_work');
            $data[$k]['classify']=Db::table('fdz_basis_materials')->where('amcode',$v['p_amcode'])->value('classify');
            $data[$k]['name']=Db::table('fdz_basis_materials')->where('amcode',$v['p_amcode'])->value('name');
            $data[$k]['unit']=Db::table('fdz_basis_materials')->where('amcode',$v['p_amcode'])->value('unit');
        }
        //2.加载PHPExcle类库
        require '../extend/PHPExcel/PHPExcel.php';
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        // Add title
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '编码')
            ->setCellValue('B1', '工种类别')
            ->setCellValue('C1', '辅材细类 ')
            ->setCellValue('D1', '分类')
            ->setCellValue('E1', '辅材名称')
            ->setCellValue('F1', '品牌')
            ->setCellValue('G1', '产地')
            ->setCellValue('H1', '入库价')
            ->setCellValue('I1', '出库价')
            ->setCellValue('J1', '包装数量')
            ->setCellValue('K1', '计量单位')
            ->setCellValue('L1', '出库单位')
            ->setCellValue('M1', '来源');
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('基础辅材报表');
                $i = 2;
        foreach ($data as $rs) {
            // Add data
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $i, $rs['amcode'])
                ->setCellValue('B' . $i, $rs['type_of_work'])
                ->setCellValue('C' . $i, $rs['classify'])
                ->setCellValue('D' . $i, $rs['fine'])
                ->setCellValue('E' . $i, $rs['name'])
                ->setCellValue('F' . $i, $rs['brank'])
                ->setCellValue('G' . $i, $rs['place'])
                ->setCellValue('H' . $i, $rs['in_price'])
                ->setCellValue('I' . $i, $rs['price'])
                ->setCellValue('J' . $i, $rs['pack'])
                ->setCellValue('k' . $i, $rs['unit'])
                ->setCellValue('L' . $i, $rs['phr'])
                ->setCellValue('M' . $i, $rs['source']);
            $i++;
        }
        //7.设置保存的Excel表格名称
        $filename =$frame. '辅材信息'.date('ymd',time()).'.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('学生信息');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
    }

    //逆转换 辅材
    public function set_fm(){
        echo '功能禁用';
        die;
        if(input('fid')){
            $fid = input('fid');
        }else{
            $fid = 153;//白云
        }
        $materials = Db::name('materials')->where(['frameid'=>$fid])->select();
        $basis_materials = Db::name('basis_materials')->select();
        $basis_materials = array_column($basis_materials ,null, 'name');
        $datas = [];
        foreach($materials as $k=>$v){
            if(isset($basis_materials[$v['name']])){
                $info = [];
                $info['p_amcode'] = $basis_materials[$v['name']]['amcode'];
                $info['fine'] = $basis_materials[$v['name']]['fine'];
                $info['amcode'] = '';
                $info['fid'] = $fid;
                $info['brank'] = $v['brand'];
                $info['place'] = $v['place'];
                $info['price'] = $v['price'];
                $phr = explode('/', $v['phr']);
                
                if(preg_match('/(\d+)(\D{0,})/',$phr[0],$arr)){
                    $info['pack'] = $arr[1];
                    if(isset($phr[1])){
                        $info['phr'] = $phr[1];
                    }else{
                        $info['phr'] = $basis_materials[$v['name']]['unit'];
                    }
                   
                }else{
                    continue;
                }
               
                
                $info['source'] = $v['remarks'];
                $datas[] = $info;
            }
        }
        Db::startTrans();
        try {
            foreach($datas as $k=>$v){
                $id = Db::name('f_materials')->insertGetId($v);
                $amcode = $v['p_amcode'] . '_' . $id;
                Db::name('f_materials')->where(['id'=>$id])->update(['amcode'=>$amcode]);
            } 
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    //逆转换 项目
    public function set_fp(){
        echo '功能禁用';
        die;
        if(input('fid')){
            $fid = input('fid');
        }else{
            $fid = 153;//白云
        }
        $offerquota = Db::name('offerquota')->where(['frameid'=>$fid])->select();
        $basis_project = Db::name('basis_project')->select();
        $basis_project = array_column($basis_project ,null, 'item_number');
        $datas = [];
        foreach($offerquota as $k=>$v){
            $item_number = explode('_', $v['item_number'])[0];
            if(isset($basis_project[$item_number])){
                $info = [];
                $info['p_item_number'] = $item_number;
                $info['item_number'] = '';
                // $info['remark'] = '';
                $info['fid'] = $fid;
                $info['cost_value'] = $v['cost_value'];
                $info['quota'] = $v['quota'];
                $info['craft_show'] = $v['craft_show'];
                $info['labor_cost'] = $v['labor_cost'];
                //组合辅材
                $content = json_decode($v['content'],true);
                $material = [];//所需的辅材
                foreach($content as $k1=>$v1){
                    if(!$v1[0] || !$v1[1]){
                        unset($content[$k1]);
                    }else{
                        $basis_materials = Db::name('basis_materials')->where(['name'=>$v1[0]])->order('id','desc')->find();
                        $f_materials = Db::name('f_materials')->where(['fid'=>$fid,'p_amcode'=>$basis_materials['amcode']])->find();
                        if(!$f_materials){
                            continue 2;
                        }
                        $material[$basis_materials['fine']] = $f_materials['amcode'];
                    }
                }
                $fine = array_keys($material);
                $basis_project_fine = json_decode($basis_project[$item_number]['fine'],true);
                if(count($basis_project_fine) != count($fine)){
                    continue;
                }
                if(!empty($basis_project_fine)){
                    foreach($basis_project_fine as $k1=>$v1){
                        if(!in_array($v1['fine'],$fine)){
                            continue 2;
                        }
                    }
                }
                $material = json_encode($material);
                if($material != '[]'){
                    $info['material'] = $material;
                }else{
                    $info['material'] = '';
                }
                $datas[] = $info;
            }
        }
        Db::startTrans();
        try {
            foreach($datas as $k=>$v){
                $id = Db::name('f_project')->insertGetId($v);
                $item_number = $v['p_item_number'] . '_' . $id;
                Db::name('f_project')->where(['id'=>$id])->update(['item_number'=>$item_number]);
            } 
            Db::commit();
            echo 'ok';
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

   

    // 导入修改辅材
    public function excel_edit_public_warehouse(){
        echo '功能禁用';
        die;
        //下面的只针对5个字段 不能再用了
        require'../extend/PHPExcel/PHPExcel.php';
        $file = request()->file('file');
        if($file){
            $info = $file->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
            if (!$info) {
                $this->error('上传文件格式不正确');
            }else{
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                // 判断哪种类型
                if($suffix=="xlsx"){
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }

            }
            //处理表格数据
            //载入excel文件
            $excel = $reader->load("$filePath",$encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            Db::startTrans();
            try {
                for ($i = 2; $i <= $row_num; $i ++) {
                    $update = [];
                    $amcode  = trim($sheet->getCell("A".$i)->getValue());//系统编号
                    if(trim($sheet->getCell("B".$i)->getValue()) != ''){
                        $update['warehouse_id']  = trim($sheet->getCell("B".$i)->getValue());//仓库编码
                    }
                    if(trim($sheet->getCell("F".$i)->getValue()) != ''){
                        $update['brank']  = trim($sheet->getCell("F".$i)->getValue());//品牌
                    }
                    if(trim($sheet->getCell("G".$i)->getValue()) != ''){
                        $update['place']  = trim($sheet->getCell("G".$i)->getValue());//产地
                    }
                    if(trim($sheet->getCell("J".$i)->getValue()) != ''){
                        $update['in_price']  = trim($sheet->getCell("J".$i)->getValue());//进库价
                        $update['price']  = trim($sheet->getCell("J".$i)->getValue());//出库价
                    }
                    if(trim($sheet->getCell("K".$i)->getValue()) != ''){
                        $update['pack']  = trim($sheet->getCell("K".$i)->getValue());//包装数量
                    }
                    if(trim($sheet->getCell("M".$i)->getValue()) != ''){
                        $update['phr']  = trim($sheet->getCell("M".$i)->getValue());//包装规格
                    }
                    if(trim($sheet->getCell("N".$i)->getValue()) != ''){
                        $update['source']  = trim($sheet->getCell("N".$i)->getValue());//来源
                    }
                    if(trim($sheet->getCell("O".$i)->getValue()) != ''){
                        $update['in_warehouse']  = trim($sheet->getCell("O".$i)->getValue())==1?1:2;//是否公司仓库
                    }
                    if(empty($update)){
                        continue;
                    }
                    echo $amcode."<br />";
                    $res = Db::name('basis_materials')->where(['amcode'=>$amcode])->update($update);
                    if($res){
                        $f_materials_list = DB::name('f_materials')->where(['p_amcode'=>$amcode,'auto_add'=>1])->select();
                        unset($update['in_warehouse']);
                        DB::name('f_materials')->where(['p_amcode'=>$amcode,'auto_add'=>1])->update($update);
                        foreach($f_materials_list as $k=>$v){
                            $this->update_fwarehouse($v['amcode']);
                        }
                    }
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                echo $e->getMessage();
            }
            echo 'ok';
        }
    }

    //管理员使用  批量导入申请的报价
    public function excel_appley_project(){
        require'../extend/PHPExcel/PHPExcel.php';
        $fid = input('fid');
        $file = request()->file('file');
        if($file){
            $info = $file->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
            if (!$info) {
                $this->error('上传文件格式不正确');
            }else{
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                // 判断哪种类型
                if($suffix=="xlsx"){
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }
            }
            //处理表格数据
            //载入excel文件
            $excel = $reader->load("$filePath",$encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            $data = []; //数组形式获取表格数据 
            for ($i = 2; $i <= $row_num; $i ++) {
                $info = [];
                $info['name']  = trim($sheet->getCell("A".$i)->getValue());
                $info['quota']  = trim($sheet->getCell("B".$i)->getValue());
                $info['craft_show']  = trim($sheet->getCell("C".$i)->getValue());
                $info['labor_cost']  = trim($sheet->getCell("D".$i)->getValue());
                $info['unit']  = trim($sheet->getCell("E".$i)->getValue()); 
                $info['content']  = trim($sheet->getCell("F".$i)->getValue()); 
                $info['material']  = trim($sheet->getCell("G".$i)->getValue()); 
                $info['fid']  = $fid; 
                if(empty($info['name']) && empty($info['quota']) && empty($info['craft_show']) && empty($info['labor_cost']) && empty($info['unit']) && empty($info['content']) && empty($info['material'])){
                    continue;
                }
                if(empty($info['name'])){
                    $this->error('第'.$i.'行名称不能为空');
                }
                if(strlen($info['quota']) == 0 || !is_numeric($info['quota']) || $info['quota'] < 0){
                    $this->error('第'.$i.'行辅材单价输入有误');
                }
                if(strlen($info['craft_show']) == 0 || !is_numeric($info['craft_show']) || $info['craft_show'] < 0){
                    $this->error('第'.$i.'行人工单价输入有误');
                }
                if(strlen($info['labor_cost']) == 0 || !is_numeric($info['labor_cost']) || $info['labor_cost'] < 0){
                    $this->error('第'.$i.'行人工成本输入有误');
                }
                if(empty($info['unit'])){
                    $this->error('第'.$i.'行单位不能为空');
                }
                if(empty($info['content'])){
                    $this->error('第'.$i.'行施工工艺与材料说明不能为空');
                }
                $data[] = $info;
            }
            
            
            if(!empty($data)){
                $res = Db::name('apply_project')->insertAll($data);
                 if ($res) {
                    $this->success('导入成功');
                }else{
                    $this->error('导入失败');
                }
            }else{
                $this->error('导入数据为空');
            }
        }else{
            $this->error('请选择上传文件');
        }
    }

    //辅材批量调价
    public function pricing_f_price(){
        $type  = input('type');
        $field  = input('field'); 
        $num  = input('num'); 
        if(empty($type)){
            $this->error('非法操作');
        }
        if(strlen($num) == 0){
            $this->error('上升额度不能为空');
        }
        if($this->_userinfo['roleid'] == 1){
            $this->error('管理员禁止调价');
        }
        if (empty($field)) {
            $this->error('请选择字段');
        }
        if ($type == 1) {
            $table = 'f_materials';
            if($field != 'in_price' && $field != 'price'){
                $this->error('非法操作');
            }
        }else if($type == 2){
            $table = 'f_project';
            if($field != 'quota' && $field != 'craft_show' && $field != 'labor_cost'){
                $this->error('非法操作');
            }
        }else{
            $this->error('非法操作');
        }
        if($num === '0'){
            $this->success('价格未发生变动');
        }
        if(!is_numeric($num) || $num < 0 || $num > 100){
            $this->error('上升额度必须为0~100之间');
        }
        $list = Db::name($table)->where(['fid'=>$this->_userinfo['companyid']])->select();
        Db::startTrans();
        try {
            foreach($list as $k=>$v){
                if(!is_numeric($v[$field])){
                    if($type == 1){
                        throw new \think\Exception('辅材编码：'.$v['amcode'].'对应字段有误，请检查后再提交', 10006);
                    }else{
                        throw new \think\Exception('项目编码：'.$v['item_number'].'对应字段有误，请检查后再提交', 10006);
                    }
                }
                $price = round(($v[$field] * $num /100) + $v[$field],2);
                $update = [];
                $update[$field] = $price;
                if($table == 'f_project'){
                    //综合价
                    if($field == 'quota' || $field == 'craft_show'){
                        $update['cost_value'] = $v['cost_value'] - $v[$field] + $price;
                    }
                }
                Db::name($table)->where(['id'=>$v['id']])->update($update);
                if($type == 1){
                    $this->update_fwarehouse($v['amcode']);
                }else{
                    if($v['status'] == 1){
                        $this->update_fproject($v['item_number']);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('调价成功');
    }
}