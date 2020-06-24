<?php

// +----------------------------------------------------------------------
// | 主材 软装 智能 订单管理
// +----------------------------------------------------------------------
namespace app\admin\controller; 

use app\common\controller\Adminbase;
use think\Db;
use think\Request;

use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FurnitureOrther extends Adminbase{
    public $show_page = 15;
    //主材 软装 智能 订单报价
    //选择客户
    public function userlist(){
        $where = [];
        if(input('customer_name')){
            $where[] = ['customer_name','LIKE','%'.input('customer_name').'%'];
        }
        if(input('quoter_name')){
            $where[] = ['quoter_name','LIKE','%'.input('quoter_name').'%'];
        }
        if(input('designer_name')){
            $where[] = ['designer_name','LIKE','%'.input('designer_name').'%'];
        }
        if(input('address')){
            $where[] = ['address','LIKE','%'.input('address').'%'];
        }
        if(input('manager_name')){
            $where[] = ['manager_name','LIKE','%'.input('manager_name').'%'];
        }
        $userinfo = $this->_userinfo; 
        $da = [];
        if($userinfo['userid'] != 1 && $userinfo['roleid'] != 10){
            $da['userid'] = $userinfo['userid'];
        }
        if($userinfo['roleid'] == 10){
            $da['frameid'] = $userinfo['companyid'];
        }
        $re = Db::name('userlist')->where($where)->where($da)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);
       
        $this->assign('data',$re);
        return $this->fetch();
    }
    public function order_list(){
        $admininfo = $this->_userinfo; 
        $type = [2=>'主材',3=>'智能、家电',4=>'软装'];
        $user_id = input('customer_id');
        $where = [];
         if($admininfo['roleid'] != 1 && $admininfo['roleid'] != 10){
            $where['adminid'] = $admininfo['userid'];
        }
        if($admininfo['roleid'] != 1){
            $where['frameid'] = $admininfo['companyid'];
        }
        if(input('type') && isset($type[input('type')])){
            $where['type'] = input('type');
        }else{
            $this->error('参数错误');
        }
        $where['userid'] = $user_id;
        $order_list = Db::name('order_furniture')->where($where)->select();
        $userinfo = Db::name('userlist')->where(['id'=>$user_id])->find();
        $this->assign([
            'data'=>$order_list,
            'userinfo'=>$userinfo,
            'type'=>$type
        ]);
        return $this->fetch();
    }

    //订单明细
    public function order_info(){
        $admininfo = $this->_userinfo; 
        $o_id = input('o_id');
        $order_info = Db::name('order_furniture')->where(['id'=>$o_id])->find();
        if($admininfo['roleid'] != 1 && $admininfo['roleid'] != 10 && $admininfo['userid'] != $order_info['adminid']){
            $this->error('无权查看此订单');
        }
        if($admininfo['roleid'] != 1 && $admininfo['companyid'] != $order_info['frameid']){
            $this->error('无权查看此订单');
        }
        $userinfo = Db::name('userlist')->where(['id'=>$order_info['userid']])->find();
        $project_list = Db::name('furniture_project')->where(['o_id'=>$o_id])->select();
        $data = [];//按分类重组数组
        foreach($project_list as $k=>$v){
            $data[$v['classify']][] = $v;
        }
        $this->assign([
            'data'=>$data,
            'order_info'=>$order_info,
            'userinfo'=>$userinfo,
        ]);
        return $this->fetch();
    } 

    //添加主材页面
    public function add_order(){
        if(!input('customer_id') || !input('type')){
            $this->error('非法操作');
        }
        $type = [2=>'主材',3=>'智能、家电',4=>'软装'];
        $userinfo = $this->_userinfo;
        $customer_info = Db::name('userlist')->where(['id'=>input('customer_id')])->find();
        $where = [];
        $where['type_name'] = $type[input('type')];//类型
        $where['frameid'] = $userinfo['companyid'];//公司
        $classify = Db::name('furniture')->where($where)->group('classify')->select();
        $this->assign([
            'customer_info'=>$customer_info,
            'classify'=>$classify,
            'type'=>input('type'),
            'type_val'=>$type
        
        ]);
        return $this->fetch();
    }

    //添加操作
    public function add_order_operation(){
        if(!input('customerid') || !input('data') || !input('framename') || !input('type_name')){
            $this->error('参数错误');
        }
        $userinfo = $this->_userinfo;
        $time = time();
        $data = array();
        $data['type'] = input('type_name');
        $data['userid'] = input('customerid');
        $data['frameid'] = $userinfo['companyid'];//存公司id到报表
        $data['adminid'] = $userinfo['userid'];//存公司id到报表
        $data['total_price'] = 0;//总价
        $data['unit'] = input('framename');//单位
        $data['addtime'] = $time;
        if(input('remark')){
            $data['remark'] = input('remark');
        }
        $order_project = [];
        foreach (input('data') as $k1 => $v) {
            $item = Db::name('furniture')->where('serial_number',$k1)->where('frameid',$userinfo['companyid'])->find();//获取定额数据
            if(!$item){
                $this->error('项目有误','',$k1);
            }
            if(!is_numeric($v['num']) || !is_numeric($v['price'])){
                $this->error($item['name'].' 数量/价格有误');
            }
            $project = [];
            $project['oa_id'] = 0;
            $project['serial_number'] = $k1;
            $project['num'] = $v['num'];
            $project['type_name'] = $item['type_name'];
            $project['classify'] = $item['classify'];
            $project['name'] = $item['name'];
            $project['loss'] = $item['loss'];
            $project['unit'] = $item['unit'];
            $project['price'] = $v['price'];
            $project['content'] = $item['content'];
            $project['addtime'] = $time;
            $order_project[] = $project; 
            $data['total_price'] += $v['num']*$v['price'];
        }
        Db::startTrans();
        try{
            $re = Db::name('order_furniture')->insertGetId($data);
            foreach($order_project as $k=>$v){
                $order_project[$k]['o_id'] = $re;
            }
            $order_project_res = Db::name('furniture_project')->insertAll($order_project);
            
            // 提交事务
            Db::commit();    
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('失败');
        }
        if($re!==false && $order_project_res){
            $this->success('保存订单成功',url('admin/furniture_orther/order_info',array('o_id'=>$re)));
            // $this->success('保存订单成功');
        }else{
            $this->error('失败');
        }
    }

    // 主材管理页面
    public function goods_list(){
        $userinfo = $this->_userinfo; 
        $where = [];
        if(input('frameid')){
            $where[] = ['frameid','=',input('frameid')];
        }
        if(input('search')){
            $where[] = ['name','like','%'.input('search').'%'];
        }
        $furniture = Db::name('furniture')->where($where)->order('id','desc')->paginate($this->show_page,false,['query'=>request()->param()]);
        $frame = Db::name('frame')->field('id,name')->order('id','desc')->where('levelid',3)->select();
        $this->assign('data',$furniture);    
        $this->assign('frame',$frame);    
        return $this->fetch();
    }

    //获取主材信息
    public function ajax_get_project(){
        $type = [2=>'主材',3=>'智能、家电',4=>'软装'];
        if(!input('type') || !input('classify') || !isset($type[input('type')])){
            $this->error('error');
        }
        $userinfo = $this->_userinfo;
        $where = [];
        $where['type_name'] = $type[input('type')];
        $where['classify'] = input('classify');
        $furniture = Db::name('furniture')->where($where)->order('id','desc')->select();
        if($furniture){
            $this->success('success','',$furniture);
        }else{
            $this->error('error');
        }
    }

    //导入主材
    public function ImportExcel(Request $request){
       if ($_FILES['excel']['error'] == 4) {
         $this->error('没有文件被上传');die;
       }
       if(!input('frameid')) {
         $this->error('请选择导入的公司');die;
       }
       $userInfo = $this->_userinfo;
       if(!$userInfo) {
            $this->error('无法获取当前操作人员');die;
        }
       require'../extend/PHPExcel/PHPExcel.php';
       $file = $request->file();
       if($file){
           foreach ($file as $files) {
             $info = $files->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
           }
           if (!$info) {
               $this->error('上传文件格式不正确');
           }else{
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
              // dump($col_num);
            if($col_num != 'H') {
               $this->error('文件数据字段不匹配，请重新选择'.$col_num);die;
            } 
            $time = time();
            for ($i = 2; $i <= $row_num; $i ++) {
                $data[$i]['serial_number']  = $sheet->getCell("A".$i)->getValue();
                $data[$i]['type_name']  = $sheet->getCell("B".$i)->getValue() ?: '';
                $data[$i]['classify']  = $sheet->getCell("C".$i)->getValue() ?: '';
                $data[$i]['name']  = $sheet->getCell("D".$i)->getValue() ?: 0;
                $data[$i]['loss']  = $sheet->getCell("E".$i)->getValue() ?: 0;
                $data[$i]['unit']  = $sheet->getCell("F".$i)->getValue() ?: '';
                $data[$i]['price']  = $sheet->getCell("G".$i)->getValue() ?: 0;
                $data[$i]['content']  = $sheet->getCell("H".$i)->getValue() ?: '';
                // $data[$i]['userid']  = $userInfo['userid'];
                $data[$i]['addtime']  = $time;
                $data[$i]['frameid']  = input('frameid');
                $data[$i]['adminid']  = $userInfo['userid'];
            }

            //将数据保存到数据库
            if ($data) {
               Db::startTrans();
               try {
                    foreach ($data as $key => $value) {
                        $is_has = Db::name('furniture')->where(['serial_number'=>$value['serial_number'],'frameid'=>$value['frameid']])->find();
                        if($is_has){
                            Db::name('furniture')->where(['serial_number'=>$value['serial_number'],'frameid'=>$value['frameid']])->update($value);
                        }else{
                            Db::name('furniture')->insert($value);
                        }
                    }
                   Db::commit();
                }catch (\Exception $e) {
                    Db::rollback();
                    $this->error('获取导入文件数据失败');
                }
               
               $this->success('导入成功');
            }else{
              $this->error('获取导入文件数据失败');
            }

       }else{
          $this->error('请选择上传文件');
       }
    }
	
}