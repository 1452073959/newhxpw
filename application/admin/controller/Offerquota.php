<?php

// +----------------------------------------------------------------------
// | 报价管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;

//调用导入类
use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;



class Offerquota extends Adminbase
{
    // protected function initialize()
    // {
    //     parent::initialize();
    //     $this->Menu = new Menu_Model;
    // }

    //
    public function index()
    {
        $userinfo = $this->_userinfo; 
        $where = [];
        // $da['userid'] = $userinfo['userid'];
        if (!empty(input('item_number'))) {
            $where[] = ['item_number','like',"%".input('item_number')."%"];
        }
        if (!empty(input('frame'))) {
            $where[] = ['frameid','like',"%".input('frame')."%"];
        }

        if (!empty(input('type_of_work'))) {
            $where[] = ['type_of_work','like',"%".input('type_of_work')."%"];
        }

        $res = Db::name('Offerquota')->where($where)->paginate(20,false,['query'=>request()->param()]);
        $frame = Db::name('frame')->field('id,name')->where('levelid',3)->select();
        $type_of_work=Db::name('offerquota')->group('type_of_work')->select();

        $this->assign('data',$res);
        $this->assign('type_of_work',$type_of_work);
        $this->assign('frame',$frame);
        return $this->fetch();
    }

   // 弹窗修改信息
    public function ajaxedits(){
         $datas = input();
         if($datas){
            $data['type_of_work'] = $datas['type_of_work'];
            $data['project'] = $datas['project'];
            $data['company'] = $datas['company'];
            $data['cost_value'] = $datas['cost_value'];
            $data['quota'] = $datas['quota'];
            $data['craft_show'] = $datas['craft_show'];
            $data['material'] = $datas['material'];
            $res = Db::name('Offerquota')->where('id',$datas['id'])->update($data);
            if($res) {
               Result(0,'更新成功',$data);
            }else{
               Result(1,'更新失败'); 
            }
         }else{
            Result(1,'获取数据失败');
         }
         // dump($datas);

    }
    //批量修改字段数据
    public function batchedit()
    {
        $datas = input();
         if($datas){
            $batch = $datas['batchname'];//字段名字
            $data[$batch] = $datas['value'];//字段内容
            $arr = $datas['idarray'];
            //利用 explode 函数分割字符串到数组 
            $arr = explode(',',$arr);
             //把获取到的二维数组遍历进数据库
             foreach ($arr as $key => $value) {
                 $res = Db::name('Offerquota')->where('id',$value)->update($data);
             }
            Result(0,'字段更新成功',$data);

            // $res = Db::name('Offerquota')->where('id',$datas['id'])->update($data);
            // if($res) {
            //    Result(0,'更新成功',$data);
            // }else{
            //    Result(1,'更新失败'); 
            // }
         }else{
            Result(1,'获取数据失败');
         }
    }

    // 报价导出数据接口
    public function baojia()
    {    
          // $res = Db::name('sertype')->alias('s')->field('s.*,u.img_url as img_url')->join('upload_img u','s.logo = u.id')->order('s.sort desc')->select();
         // $res = Db::name('Offerquota')->alias('a')->field('a.*,b.name as bname')->join('offer_type b','a.typeid = b.id')->select();
         $userinfo = $this->_userinfo; 
         $da['userid'] = $userinfo['userid'];
         $res = Db::name('Offerquota')->where($da)->select();
       
         $newdata = array();
         //过滤无用字段
         foreach ($res as $k => $v) {
             unset($v['addtime']);
             $newdata[$k] = $v;

         }
           // dump($news);
         if($newdata){
              TreeResult(0,'ok',$newdata,count($newdata));
          }else{
              TreeResult(1,'获取失败');
          }
    }


     //添加
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();  
            if(!$data['frameid']) {
                $this->error('请选择分公司');  
            }   
            $userinfo = $this->_userinfo; 
            $data['userid'] = $userinfo['userid'];
            //辣鸡外包写的 不加这个报错
            $data['content'] = '[[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null],[null,null]]';
            // dump($data);exit;
            if (Db::name('Offerquota')->insert($data)) {
                $this->success("添加成功！", url("Offerquota/index"));
            } else {
                $this->error('添加失败！');
            }
        }else{
             $res = Db::name('frame')->where('levelid',3)->field('id,name')->select();
              $this->assign("data", $res);
            return $this->fetch();
        }
    }

    //生成编号
    private function GetNumber($val="BJ_"){
        $shijian = date('YmdHis',time());
        return ($val.genRandomString(2).$shijian);
    }


    //编辑后台菜单
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
             // dump($data);exit; 
            if(Db::name('Offerquota')->where('id', $data['id'])->update($data)){
                $this->success("编辑成功！", url("Offerquota/index"));
            }else{
                $this->error('编辑失败了！');
            }
        } else {
            $request = request();
            $id = $request->param('id');
            $rs = Db::name('Offerquota')->where(["id" => $id])->find();    
            $res = Db::name('offer_type')->field('id,name')->select();;

            $this->assign("ones", $res); 
            $this->assign("data", $rs);
            return $this->fetch();
        }

    }

    //列表单字段修改
    public function singlefield_edit()
    {
        if ($this->request->isPost()) {
            $receive = $this->request->param();
            $data[$receive['field']] = $receive['value'];
            if(Db::name('Offerquota')->where('id', $receive['id'])->update($data)){
                 Result(0,'单字段编辑成功'); 
            }else{
                Result(1,'编辑失败了！'); 
            }
        } else {
           Result(1,'获取字段信息失败'); 
        }

    }
     //选择公司
    public function chashuju()
    {
        if ($this->request->isPost()) {
            $receive = $this->request->param();
            // $search = $receive[''];
            $search = $receive['_thisval'];
            $redata = Db::name('frame')->field('id,name')->where('levelid',3)->where('name','like',"%".$search."%")->select();
            if($redata){
                 Result(0,'查询成功',$redata); 
            }else{
                Result(1,'查不到该数据！'); 
            }
        } else {
           Result(1,'获取信息失败'); 
        }

    }

// 导入excel表
    public function ImportExcel(Request $request){
          
             $da = $this->request->param();
           if ($_FILES['excel']['error'] == 4) {
             $this->error('没有文件被上传', url("Offerquota/index"));die;
           }
           if ($da['frameid'] == '') {
             $this->error('请选择导入的公司', url("Offerquota/index"));die;
           }

           $userInfo = $this->_userinfo;
           if(!$userInfo) {
                $this->error('无法获取当前操作人员');die;
            }
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
               if ($col_num != 'AW') {
                   $this->error('文件数据字段不匹配，请重新选择'.$col_num);die;
                } 
                // if ($col_num == 'AW') {
                //    $this->error('输出AW正确');die;
                // } 
                $arrletter = array('J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW');//辅材基数字母
                // echo 666;exit;
                for ($i = 3; $i <= $row_num; $i++) {
                    $data[$i]['item_number']  = $sheet->getCell("A".$i)->getValue() ?: '';              
                    $data[$i]['project']  = $sheet->getCell("B".$i)->getValue() ?: '';
                    $data[$i]['type_of_work']  = $sheet->getCell("C".$i)->getValue() ?: '';
                    $data[$i]['company']  = $sheet->getCell("D".$i)->getValue() ?: '';
                    $data[$i]['quota']  = $sheet->getCell("E".$i)->getValue() ?: 0;
                    $data[$i]['craft_show']  = $sheet->getCell("F".$i)->getValue() ?: 0;
                    $data[$i]['cost_value']  = $sheet->getCell("G".$i)->getValue() ?: 0;
                    $data[$i]['labor_cost']  = $sheet->getCell("H".$i)->getValue() ?: 0;           
                    $data[$i]['material']  = $sheet->getCell("I".$i)->getValue() ?: '';
                    //辅材基数对应转json数组开始
                    $list = [];
                    $j = 0;
                    foreach ($arrletter as $key => $value) {
                      if($j%2==0){
                        $list[] = array($sheet->getCell($arrletter[$key].$i)->getValue(),$sheet->getCell($arrletter[$key+1].$i)->getValue());
                      }
                      $j++;
                    }
                    $data[$i]['content']  = str_replace("\\/", "/", json_encode($list,JSON_UNESCAPED_UNICODE));
                    //end
                    $data[$i]['userid']  = $userInfo['userid'];  
                    $data[$i]['frameid']  = $da['frameid'];  
                }

                //将数据保存到数据库
                if ($data) {
                   //把获取到的二维数组遍历进数据库
                    Db::startTrans();
                    try {
                        foreach ($data as $key => $value) {
                            $is_has = Db::name('Offerquota')->where(['item_number'=>$value['item_number'],'frameid'=>$value['frameid']])->find();
                            if($is_has){
                                Db::name('Offerquota')->where(['item_number'=>$value['item_number'],'frameid'=>$value['frameid']])->update($value);
                            }else{
                                $res = Db::name('Offerquota')->insert($value);
                            }
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
   //删除
    public function delete()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param(); 
            if(!$data['id']) {
                 Result(1,'数据不存在');  
            }
           if (Db::name('Offerquota')->where(array('id'=>$data['id']))->delete()) {
                Result(0,'删除成功');  
            } else {
                Result(1,'删除失败');  
            }
        }  
    }

    //批量删除
    public function deletes()
    {
        if ($this->request->isPost()) {
            $ids = input('ids');
            Db::name('Offerquota')->where('id','in',$ids)->delete();
            Result(0,'删除成功');
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
