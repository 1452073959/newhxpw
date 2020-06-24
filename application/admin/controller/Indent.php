<?php

// +----------------------------------------------------------------------
// | 组织架构管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;
use app\admin\model\Department;
use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Indent extends Adminbase
{
    // protected function initialize()
    // {
    //     parent::initialize();
    //     $this->Menu = new Menu_Model;
    // }

    //

    public function calendar()
    {
        $admininfo = $this->_userinfo;
        if($admininfo['roleid']!=1){
            $role=Db::table('fdz_auth_group')->where('id',$admininfo['roleid'])->find();
            $role=explode(',',$role['auth']);
            $list=Db::table('fdz_zz')->where('id','in',$role)->select();
        }else{
            $list=Db::table('fdz_zz')->select();
        }
        $tree = [];
        if (is_array($list)) {
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data['id']] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                $parentId = $data['pid'];
                $list[$key]['spread'] =true;
                unset($list[$key]['content']);
                if (0 == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent['children'][] =& $list[$key];
//                        $parent['disabled'] =true;
                    }

                }
            }

        }
        return json($tree);
    }


    //删除
    public function del(Request $request)
    {
        $data=$request->post();
        $res=Db::table('fdz_zz')->where('pid',$data['id'])->select();
        if(!empty($res)){
            return json(['code'=>1,'msg'=>'有子项目未删除,请先删除子项目']);
        }else{
            $res=Db::table('fdz_zz')->where('id',$data['id'])->delete();
            if($res){
                return json(['code'=>1,'msg'=>'删除成功']);
            }else{
                return json(['code'=>2,'msg'=>'删除失败']);
            }
        }

    }
    //更新
    public function update(Request $request)
    {
        $data=$request->post();
        $a1=Db::table('fdz_auth_group')->select();
        foreach ($a1 as $k1=>$v1){
            $m[]=$v1['id'];
        }
        if( !isset($data['role']))
        {
            $data['role']=[];
        }
        $me=array_diff($m,$data['role']);
        $role2=Db::table('fdz_auth_group')->where('id','in',$me)->select();
        foreach ($role2 as $k2=>$v2)
        {
            if(in_array($data['id'],explode(',',$v2['auth']))) {
                $role2[$k2]['auth']=explode(',',$v2['auth']);
                foreach ($role2[$k2]['auth'] as $k3=>$v3){
                   if($v3==$data['id']){
                     unset($role2[$k2]['auth'][$k3]);
                   }
                }
                $role2[$k2]['auth'] = implode(',',$role2[$k2]['auth']);
            }
          Db::table('fdz_auth_group')->where('id',$v2['id'])->update(['auth'=>trim($role2[$k2]['auth'])]);
        }

        $role=Db::table('fdz_auth_group')->where('id','in',$data['role'])->select();
        foreach ($role as $k=>$v)
        {
            if( !in_array($data['id'],explode(',',$v['auth']))) {
                $role[$k]['auth'] = $v['auth'].','.$data['id'];
            }
            Db::table('fdz_auth_group')->where('id',$v['id'])->update(['auth'=>trim($role[$k]['auth'])]);
        }

        $res=Db::table('fdz_zz')->where('id',$data['id'])->update(['title'=>$data['title'],'content'=>htmlspecialchars_decode($data['content'])]);
        if($res){
            return json(['code'=>1,'msg'=>'修改成功','data'=>$data['title']]);
        }else{
            return json(['code'=>2,'msg'=>'修改成功','data'=>$data['title']]);
        }
    }
    //添加
    public function create()
    {
        $tree = new \util\Tree();
        $pid = $this->request->param('id/d', '');
        $result = Db::table('fdz_zz')->order(array( 'id' => 'DESC'))->select();
            foreach ($result as $k=>$v)
            {
                $result[$k]['parentid']=$v['pid'];
            }

        $array = array();
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $pid ? 'selected' : '';
            $array[] = $r;
        }

        $str = "<option value='\$id' \>\$spacer \$title</option>";
        $tree->init($array);
        $select_categorys = $tree->get_tree(0, $str);
        $data= Db::table('fdz_auth_group')->select();
        $this->assign('data',$data);
        $this->assign('select_categorys',$select_categorys);

        return $this->fetch();
    }
    //



    //权限及添加
    public function createpost()
    {
        $data=input();
        $data['content']='这里输入内容';
        $res=Db::table('fdz_zz')->insert($data);
        if($res){
            return json(['code=>1','msg'=>'添加成功','data'=>$res]);
        }else{
            return json(['code=>1','msg'=>'添加失败','data'=>$res]);
        }


    }

    public function select()
    {
        $key= input();
       $data= Db::table('fdz_auth_group')->select();
        foreach ($data as $k=>$v)
        {
            $data[$k]['auth']=explode(',',$v['auth']);
            $data[$k]['key']=$key['key'];
        }
//        dump($data);die;
        return json(['code=>1','msg'=>'切换成功','data'=>$data]);
    }
    //增加修改页面
    public function quality()
    {
        $data= Db::table('fdz_auth_group')->select();
        if(empty($key['key'])){
        $key['key']=1;
        }
        foreach ($data as $k=>$v)
        {
            $data[$k]['key']=$key['key'];
        }
        $this->assign('data',$data);
         return $this->fetch();
    }
    //显示
    public function document()
    {
        $topsearch=Db::table('fdz_zz')->where('topsearch',1)->select();
        $this->assign('topsearch',$topsearch);
        return $this->fetch();
    }
    //搜索
    public function search()
    {
        $data=input();
        $topsearch=Db::table('fdz_zz')->where('topsearch',1)->select();
        $neq=Db::table('fdz_zz')->where('title',$data['search'])->find();
        $net= Db::table('fdz_auth_group')->select();
        foreach ($net as $k=>$v)
        {
            $net[$k]['key']=$neq['id'];
        }
//        dump($neq);
        $this->assign('topsearch',$topsearch);
        $this->assign('neq',$neq);
        $this->assign('net',$net);
        return $this->fetch('indent/document');

    }
    //单个文档内容
    public function one()
    {
        $data=input();
        $a=$data['id'];
        $res=Db::table('fdz_zz')->where('id',$data['id'])->find();
        $data= Db::table('fdz_auth_group')->select();
        foreach ($data as $k=>$v)
        {
            $data[$k]['auth']=explode(',',$v['auth']);
            $data[$k]['key']=$a;
        }
//        dump($data);
        $str = '';
        $str .= "<div class='layui-input-block' id='le'>";
        foreach ($data as $key => $value) {
            $str .= '
             <input type="checkbox" name="role" title=" '.$value['title'].'"  value="'.$value['id'].'" '.(in_array($value['key'],$value['auth'])?'checked':'').' >
                        ';
        }
        $str .= " </div>";
        return json(['res'=>$res,'str'=>$str]);

    }
    //热搜
    public function topsearch()
    {
        $top=input('id');
        $topsearch=Db::table('fdz_zz')->where('id',$top)->value('topsearch');
        if($topsearch==0){
            $search1=Db::table('fdz_zz')->where('id',$top)->update(['topsearch'=>'1']);
            if($search1){
                return json(['code'=>1,'msg'=>'设置热搜成功']);
            }else{
                return json(['code'=>2,'msg'=>'设置热搜失败']);
            }
        }else{
            $search2=Db::table('fdz_zz')->where('id',$top)->update(['topsearch'=>'0']);
            if($search2){
                return json(['code'=>1,'msg'=>'取消热搜成功']);
            }else{
                return json(['code'=>2,'msg'=>'取消热搜失败']);
            }
        }


    }

    public function notice()
    {
        return $this->fetch();
    }
    public function index(){
    
        $res = Db::name('frame')->field('id,name')->where(['levelid'=>2,'status'=>0])->select();
        //获取省份
        $provinces = Db::name('provinces')->order('id','asc')->select();
      
        // dump($res);
        $this->assign('data',$res);    
        $this->assign('provinces',$provinces);    
        return $this->fetch();
    }

    // 获取组织架构接口
    public function TreeType(){
      $res = Db::name('frame')->select();
      $provinceid = array_column(Db::name('provinces')->select(),null,'provinceid'); 
      $cityid = array_column(Db::name('cities')->select(),null,'cityid'); 
      $areaid = array_column(Db::name('areas')->select(),null,'areaid'); 
      foreach($res as $k=>$v){
        if($v['provinceid']){
            $res[$k]['province'] = $provinceid[$v['provinceid']]['province'];
        }
        if($v['cityid']){
            $res[$k]['city'] = $cityid[$v['cityid']]['city'];
        }
        if($v['areaid']){
            $res[$k]['area'] = $areaid[$v['areaid']]['area'];
        }
        
        
      }
      // dump($res);
      if($res){
          TreeResult(0,'ok',$res,count($res));
      }else{
          TreeResult(1,'获取失败');
      }

    }

       // 添加组织架构信息
    public function adds(){
         $datas = input();
         if($datas){
            $data['name'] = $datas['name'];
            $data['other'] = $datas['other'];
            $data['pid'] = $datas['pid'];
            $data['levelid'] = $datas['levelid']+1;
            $res = Db::name('frame')->insert($data);
            if ($res) {
               Result(0,'添加成功');
            }else{
               Result(1,'添加失败'); 
            }
         }else{
            Result(1,'获取pid失败');
         }
    }

    // 删除组织架构信息
    public function dels(){
         $datas = input();
         if($datas){
           $ziji = Db::name('frame')->where('pid',$datas['id'])->find();
           if ($ziji) {
               Result(1,'请先删除下面的子类');die;
           }
            $res = Db::name('frame')->where('id',$datas['id'])->delete();
            // $res = 1;
            if ($res) {
               Result(0,'删除成功');
            }else{
               Result(1,'删除失败'); 
            }
         }else{
            Result(1,'信息不完整');
         }
    }

    // 禁用启用
    public function editstatu(){
         $datas = input();
         if($datas){
           $ziji = Db::name('frame')->where('pid',$datas['id'])->find();
           if ($ziji) {
               Result(1,'此项父级不能执行此操作');die;
           }
            if ($datas['status'] == 0) {
               $data['status'] = 1;
               $res = Db::name('frame')->where('id',$datas['id'])->update($data);
               $admin = Db::name('admin')->where('companyid',$datas['id'])->select();
               if($admin){
                 foreach ($admin as $key => $value) {
                  $upsta = Db::name('admin')->where('userid',$value['userid'])->update(['status'=>0]);
                 }
                 Result(0,'禁用成功');
               }else{
                 Result(0,'操作成功，但该公司没有操作员');
               }

               
            }elseif($datas['status'] == 1){
               // $data['status'] = 0;
               // $res = Db::name('frame')->where('id',$datas['id'])->update($data);
               Result(1,'已经禁用');
            }else{
               Result(1,'操作失败'); 
            }
         }else{
            Result(1,'信息不完整');
         }
         // dump($datas);

    }

      // 修改组织架构信息
    public function edits(){
         $datas = input();
         if($datas){
            if (isset($datas['frameid'])) {
              if (is_numeric($datas['frameid'])) {
                 $data['pid'] = $datas['frameid'];
              }
            }            
            $data['name'] = $datas['name'];
            $data['other'] = $datas['other'];
            $data['provinceid'] = $datas['province'];
            $data['cityid'] = $datas['cities'];
            $data['areaid'] = $datas['areas'];
            $res = Db::name('frame')->where('id',$datas['id'])->update($data);
            if($res) {
               Result(0,'更新成功');
            }else{
               Result(1,'更新失败'); 
            }
         }else{
            Result(1,'获取数据失败');
         }
         // dump($datas);

    }


 

    //添加
    // public function add()
    // {
    //     if ($this->request->isPost()) {
    //         $data = $this->request->param();         

    //         if(empty($data['name'])) {
    //             $this->error('车辆不能为空');             
    //         }elseif($data['sort']<0) {
    //             $this->error('排序数字不合法');        
    //         }
 
    //         if (Db::name('vehicle_type')->insert($data)) {
    //             $this->success("添加成功！", url("Carlist/index"));
    //         } else {
    //             $error = Db::name('vehicle_type')->getError();
    //             $this->error($error ? $error : '添加失败！');
    //         }
    //     } else {

    //         return $this->fetch();
    //     }
    // }

    //编辑后台菜单
    public function edit()
    {
        if ($this->request->isPost()) {
            // $data = $this->request->param();
              
            // $eui['name'] = $data['name'];
            // $eui['sort'] = $data['sort'];
            // $eui['remarks'] = $data['remarks'];
            // $eui['load'] = $data['load'];
            // $eui['lwh'] = $data['lwh'];
            // $eui['volume'] = $data['volume'];
            // $eui['start_money'] = $data['start_money'];
            // $eui['extra_mileage'] = $data['extra_mileage'];
   

            // if (Db::name('vehicle_type')->where('vehicle_type_id', $data['id'])->update($eui)) {
            //     $this->success("编辑成功！", url("Carlist/index"));
            // } else {
            //     $error = Db::name('vehicle_type')->getError();
            //     $this->error($error ? $error : '编辑失败！');
            // }
        } else {
            $request = request();
            $id = $request->param('id');
            $rs = Db::name('indent')->where(["Id" => $id])->find();
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
    //         $this->success("删除成功", url("Carlist/index"));
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
    // 导入excel表
    public function ImportExcel(Request $request){
          
       require'../extend/PHPExcel/PHPExcel.php';
       $file = $request->file();
       // dump($file);
       if($file){
           foreach ($file as $files) {
            // dump($files);
             $info = $files->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public/'. 'excel');
           }
           if (!$info) {
              // Result(1,'上传文件格式不正确'); 
               $this->error('上传文件格式不正确');
           }else{
              // Result(0,'上传成功'); 
              //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public/'. 'excel/'.$fileName;
                //获取文件后缀
                $suffix = $info->getExtension();

                //记录上传文件日志(先不做了)
                  // $log['filepath'] = $filePath;
                  // $log['addtime'] = time();
                  // $rval = Db::name('excelfile')->insert($log);


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
           if ($col_num != 'E') {
               $this->error('文件数据字段不匹配，请重新选择');die;
            } 
            for ($i = 2; $i <= $row_num; $i ++) {
                $data[$i]['name']  = $sheet->getCell("A".$i)->getValue();
                $data[$i]['other']  = $sheet->getCell("B".$i)->getValue();
                $data[$i]['levelid']  = $sheet->getCell("C".$i)->getValue();
                $data[$i]['pid']  = $sheet->getCell("D".$i)->getValue();
                $data[$i]['status']  = $sheet->getCell("E".$i)->getValue();
            }

            //将数据保存到数据库
            if ($data) {
               //把获取到的二维数组遍历进数据库
               foreach ($data as $key => $value) {
                   $res = Db::name('frame')->insert($value);
               }
               $this->success('导入成功');
            }else{
              $this->error('获取导入文件数据失败');
            }

       }else{
          $this->error('请选择上传文件');
       }
    }

    //分公司自行配置基础信息页面
    public function set_tmp(){
        // $this->assign("data", $rs);
        $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$this->_userinfo['companyid']])->find();
        if(!$cost_tmp){
            $cost_tmp = [
                // 'taxes'=>0,
                'supervisor'=>0,
                'design'=>0,
                'repeat'=>0,
                'business'=>0,
                'order_tfoot'=>'',
                'take_rate1'=>0,
                'take_rate2'=>0,
                'take_rate3'=>0,
                'take_rate4'=>0,
                'pick_rate'=>0,
                'order_check'=>'',
                'borrower'=>'3',
                'type'=>'1',
            ];//返回空数据
        }
        // if($cost_tmp['order_check']){
        //     $cost_tmp['order_check'] = json_decode($cost_tmp['order_check'],true);
        //     foreach ($cost_tmp['order_check'] as $k => $v) {
        //         if(empty($v[1])){
        //             $cost_tmp['order_check'][$k] = $v[0];
        //         }else{
        //             $cost_tmp['order_check'][$k] = implode('-', $v);
        //         }
        //     }
        //     $cost_tmp['order_check'] = implode("\n", $cost_tmp['order_check']);
        // }
        $discount = Db::name('discount')->where(['fid'=>$this->_userinfo['companyid'],'is_del'=>0])->select();
        $this->assign("discount", $discount);
        $this->assign("data", $cost_tmp);
        $this->assign("f_id", $this->_userinfo['companyid']);
        return $this->fetch();
    }

    public function get_tmp(){
        $f_id = input('f_id');
        $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$f_id])->find();
        if(!$cost_tmp){
            $cost_tmp = [
                            // 'tubemoney'=>0,
                            // 'carry'=>0,
                            // 'clean'=>0,
                            // 'accident'=>0,
                            // 'remote'=>0,
                            // 'old_house'=>0,
                            // 'taxes'=>0,
                            'supervisor'=>0,
                            'design'=>0,
                            'repeat'=>0,
                            'business'=>0,
                            'order_tfoot'=>'',
                            'take_rate1'=>0,
                            'take_rate2'=>0,
                            'take_rate3'=>0,
                            'take_rate4'=>0,
                            'pick_rate'=>0,
                            'order_check'=>'',
                        ];//返回空数据
        }
        // if($cost_tmp['order_check']){
        //     $cost_tmp['order_check'] = json_decode($cost_tmp['order_check'],true);
        //     foreach ($cost_tmp['order_check'] as $k => $v) {
        //         if(empty($v[1])){
        //             $cost_tmp['order_check'][$k] = $v[0];
        //         }else{
        //             $cost_tmp['order_check'][$k] = implode('-', $v);
        //         }
        //     }
        //     $cost_tmp['order_check'] = implode("\n", $cost_tmp['order_check']);
        // }
        Result(0,'',$cost_tmp);
    }
                // if(in_array($v[0], $title)){
                //     Result(1,'流程名称不能重复');
                // }
                // $title[] = $v[0];
    public function edit_tmp(){
        $f_id = input('f_id');
        $datas['supervisor'] = input('supervisor');
        $datas['design'] = input('design');
        $datas['repeat'] = input('repeat');
        $datas['business'] = input('business');
        $datas['order_tfoot'] = input('order_tfoot');
        $datas['take_rate1'] = input('take_rate1');
        $datas['take_rate2'] = input('take_rate2');
        $datas['take_rate3'] = input('take_rate3');
        $datas['take_rate4'] = input('take_rate4');
        $datas['pick_rate'] = input('pick_rate');
        // $datas['order_check'] = input('order_check');
        $datas['borrower'] = input('borrower');
        $datas['type'] = input('type');

        // $order_check = explode("\n", input('order_check'));
        // $title = [];
        // foreach($order_check as $k=>$v){
        //     $info = explode('-', $v);
        //     if(mb_strlen($info[0]) > 3){
        //         Result(1,'流程名称不能超过3个字');
        //     }
        //     if(in_array($info[0], $title)){
        //         Result(1,'流程名称不能重复');
        //     }

        //     $title[] = $info[0];
        //     if(!isset($info[1])){
        //         $info[1] = '';//没有说明
        //     }
        //     $info = array_slice($info,0,2);
        //     $order_check[$k] = $info;
        // }
        // $datas['order_check'] = json_encode($order_check);
        
        if($datas['take_rate1'] + $datas['take_rate2'] + $datas['take_rate3'] + $datas['take_rate4'] != 100){
            Result(1,'收款比率合计必须为100');
        }
        $cost_tmp = Db::name('cost_tmp')->where(['f_id'=>$f_id])->find();
        if($cost_tmp){
            //修改
            $res = Db::name('cost_tmp')->where(['f_id'=>$f_id])->update($datas);
        }else{
            //添加
            $datas['f_id'] = $f_id;
            $res = Db::name('cost_tmp')->insert($datas);

        }
        if($res){
            Result(0,'修改成功');
        }else{
            Result(1,'修改失败');
        }
    }

















}
