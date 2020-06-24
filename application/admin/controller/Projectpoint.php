<?php

// +----------------------------------------------------------------------
// | 报价管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;

use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;



class Projectpoint extends Adminbase
{

    public function index()
    {
       $userinfo = $this->_userinfo; 
       $da['userid'] = $userinfo['userid'];
      if($this->request->isPost()){
          $nr = input();
          if($nr['search']) {
            $ress = Db::name('frame')->where(array('name'=>$nr['search'],'levelid'=>3))->find();
            if ($ress) {
              $re = Db::name('class')->where(array('frameid'=>$ress['id']))->find();
              if ($re) {
                $this->assign('str',$re);     
                return $this->fetch();
              }else{
                return $this->error('暂无该公司的工程定点',url("Projectpoint/index"));
              }              
            }else{
              return $this->error('查询不到该公司',url("Projectpoint/index"));
            }
          }else{
           return $this->error('搜索内容为空',url("Projectpoint/index"));
          }
          // dump($nr);
      }else{
        // dump(input());
        $frameid = input('frameid');
        $re = Db::name('class')->where(array('frameid'=>$frameid?$frameid:152))->find();
        $this->assign('str',$re);     
        return $this->fetch();
      } 
}
//套房、别墅、复式提取表格信息返回对应区间利率
private function GetTable($zfc,$vals=""){
        preg_match_all('/<td(.*?)>(.*?)<\/td>/s', $zfc, $matches);  
        $data = $matches[2];
        // dump($data);
        foreach ($data as $k => $v) {
          if ($v == '<br/>') {
            unset($data[$k]);
          }
        }
          $data = array_values($data);
          foreach ($data as $kk => $vv) {
            if (checkNum($kk) === TRUE) {
              $data2[$kk] = $data[$kk];
            }else{
              $data1[$kk] = $this->jiequ($data[$kk]);
            }
          }  
          $xinzu = array_combine($data1,$data2);
          asort($xinzu);
          if ($vals) {
            reset($xinzu);
            $shou = key($xinzu);
            end($xinzu);
            $end = key($xinzu);
            echo $vals.'<br/>';
            if ($shou>$vals) {
              echo reset($xinzu);
            }else if($vals>=$end){
              echo end($xinzu);  
            }else{
              foreach ($xinzu as $ks => $vs) {
                if(strpos($ks,'-') !== false) {
                    $arrk = explode('-',$ks);
                   if($vals>=$arrk[0] && $vals<$arrk[1]) {
                     echo $vs;
                   }
                }
              }
            }
          }
          dump($xinzu);
}
//套房
// private function GetTable_s($zfc){
 
//         preg_match_all('/<span(.*?)>(.*?)<\/span>/s', $zfc, $matches);  
//          $data = $matches[2];
//          // dump($data);
//          $new = array();
//          for($i=0; $i<count($data); $i++){
//             if($i > 12){
//               $new[$i] = $data[$i];
//             }
//           }
//           $keys = array();
//           $vals = array();
//           $new = array_values($new);
//            for($i=0; $i<count($new); $i++){
//             if(checkNum($i) === TRUE){
//               $keys[$i] = $new[$i];
//                // echo $new[$i].'<br />';
//             }else{
//               $vals[$i] = $this->huoqu($new[$i]);
//                // echo $this->huoqu($new[$i]).'<br />';
//             }
//           }
//           // dump($keys);
//           // dump($vals);
//           $xinzu = array_combine($vals,$keys);
//           ksort($xinzu);
//           dump($xinzu); 
// }
//别墅、复式
// private function GetTable_v($zfc){
//         preg_match_all('/<span(.*?)>(.*?)<\/span>/s', $zfc, $matches);  
//         $data = $matches[2];
//         // dump($data);
//         foreach ($data as $k => $v) {
//           if ($k>14) {
//             $new[$k] = $v;
//           }
//         }
//           $new = array_values($new);
//           foreach ($new as $kk => $vv) {
//             if (checkNum($kk) === TRUE) {
//               $newarr2[$kk] = $new[$kk];
//             }else{
//               $newarr1[$kk] = $this->jiequ($new[$kk]);
//             }
//           }  
//           $xinzu = array_combine($newarr1,$newarr2);
//           ksort($xinzu);
//           dump($xinzu);
// }

private function huoqu($strsss){
  return  preg_replace('/\D/s', '', $strsss) ? preg_replace('/\D/s', '', $strsss) : 0;
}

private function jiequ($strsss){
   $jiequ = explode('万',$strsss);
  return $jiequ[0];
}


    // 编辑后台菜单
    public function edit()
    {
        if ($this->request->isPost()) {
             $dats = input();
            foreach($dats as $k => $v) {
              $dats[$k] = htmlspecialchars_decode($v);
            } 
            // dump($dats);
            // exit;
           $re = Db::name('class')->where('frameid',$dats['frameid'])->update($dats);
          if ($re) {
           $this->success('操作成功！', url('Projectpoint/index',['frameid'=>$dats['frameid']]));
          }
        }

    }

   
// 导入excel表
    public function ImportExcel(Request $request){
          
           if ($_FILES['excel']['error'] == 4) {
             $this->error('没有文件被上传');die;
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
                  // dump($col_num);
               if ($col_num != 'I') {
                   $this->error('文件数据字段不匹配，请重新选择');die;
                } 
                for ($i = 2; $i <= $row_num; $i ++) {
                    $data[$i]['item_number']  = $sheet->getCell("A".$i)->getValue();
                    $data[$i]['typeid']  = $sheet->getCell("B".$i)->getValue();
                    $data[$i]['type_of_work']  = $sheet->getCell("C".$i)->getValue();
                    $data[$i]['project']  = $sheet->getCell("D".$i)->getValue();
                    $data[$i]['company']  = $sheet->getCell("E".$i)->getValue();
                    $data[$i]['cost_value']  = $sheet->getCell("F".$i)->getValue();
                    $data[$i]['quota']  = $sheet->getCell("G".$i)->getValue();
                    $data[$i]['craft_show']  = $sheet->getCell("H".$i)->getValue();
                    $data[$i]['material']  = $sheet->getCell("I".$i)->getValue();
                    $data[$i]['userid']  = $userInfo['userid'];  
                    $data[$i]['addtime']  = time();  
                }

                //将数据保存到数据库
                if ($data) {
                   //把获取到的二维数组遍历进数据库
                   foreach ($data as $key => $value) {
                       $res = Db::name('offerlist')->insert($value);
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
