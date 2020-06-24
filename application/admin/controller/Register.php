<?php

// +----------------------------------------------------------------------
// | 司机注册
// +----------------------------------------------------------------------
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Register extends Controller
{

    public function index()
    {
        # code...
    }

   //跑腿类型
    public function runtype()
    {
     $res = Db::name('sertype')->alias('s')->field('s.*,u.img_url as img_url')->join('upload_img u','s.logo = u.id')->order('s.sort desc')->select();

       $new = array();
       foreach ($res as $k => $v) {
          $new[$k] = $v;
          $new[$k]['img_url'] = 'http://39.106.181.129/fh_paotui/public/public/uploads/'.$v['img_url'];
       }
       $new = array_merge($new,$new,$new,$new,$new);
       // $new1 = count($new);
       // echo $new1;
       //  foreach ($new as $ke => $vl) {
       //   $new1[$k] = $vl;
       //  }

       // dump(array_chunk($new,10));
       // $news = array_chunk($new,10);
       // dump($news);
        Result(0,'跑腿类型查询成功', $new);
      
    }
    
    // 个人信息注册提交
    public function maninfo(){
            // $data['city'] = '广州';
            // $data['surname'] = '宋';
            // $data['truename'] = '天明';
            // $data['idcard'] = '452626566565625499';
            // $data['emergency_contact'] = '妈妈';
            // $data['emergency_contact_phone'] = '15646564798';

         
            
        $info =input('post.');
        if($info){          
            $data['city'] = input('post.city');
            $data['surname'] = input('post.surname');
            $data['truename'] = input('post.truename');
            $data['idcard'] = input('post.idcard');
            $data['emergency_contact'] = input('post.emergency_contact');
            $data['emergency_contact_phone'] = input('post.emergency_contact_phone');
            $data['addtime'] = time();
            $re = Db::name('driver')->insert($data);
            if ($re) {
               Result(0,'个人信息插入成功');
            }else{
               Result(1,'个人信息插入失败');
            }
        }else{
            Result(1,'post请求失败');
        }

    }
   // 车辆信息提交
     public function carinfo(){
        

         $info = input('post.');
         $id = input('post.id');
        if($info){          
            $data['vehicle_type_id'] = input('post.vehicle_type_id');
            $data['mobile'] = input('post.mobile');
            $data['nickname'] = input('post.nickname');
            $data['license_plate'] = input('post.license_plate');
            $data['driver_license_number'] = input('post.driver_license_number');
            $data['password'] = md5(substr($data['mobile'], -6));
            $res = Db::name('driver')->where('id', $id )->find();        
            if ($res['mobile'] != $data['mobile']) {
              if ($re = Db::name('driver')->where('id', $id)->update($data)) {
                  Result(0,'车辆更新成功');
              }else{
                   Result(1,'车辆更新失败');
              }
            }else{
              Result(1,'手机号已注册');
            }
            
        }else{
            Result(1,'车辆接收出错');
        }
     }

          // 上传身份证图片方法
      public function upload(){
            // 获取表单上传文件 例如上传了001.jpg
         $driver_id = input('id');
        if($driver_id && $_FILES){ 
             $nameval = key($_FILES);//传过来的name值
             //传过来的id
             $file = request()->file($nameval);
             // 移动到框架应用根目录/public/uploads/ 目录下

             //有图片删除图片
               $datas = Db::name('driver')->where('id', $driver_id )->find();
               $lujing = ROOT_PATH . 'public/'. 'peopleinfo/';
               if($datas[$nameval]) {
                  if(file_exists($lujing.$datas[$nameval])){
                    unlink($lujing.$datas[$nameval]);
                   // Result(0,'删除原头像成功');
                 }
               }
               $info = $file->move(ROOT_PATH . 'public/'. 'peopleinfo');//默认路径

            if($info){
                // 成功上传后 记录到数据库
                // dump($info);exit;
                $urlimg = $info->getSaveName();
                $res = Db::name('driver')->where('id', $driver_id)->update([$nameval => $urlimg]);
                if($res) {
                    Result(0,'上传成功');
                }else{
                    Result(1,'上传失败');
                }
                // echo $info->getExtension().'<br />';// 输出 后缀名
                // // 输出 文件夹\文件名
                // echo $info->getSaveName().'<br />';
                // // 输出 文件名
                // echo $info->getFilename(); 
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }

           }else{
              Result(1,'上传信息不完整');
           } 


        }

      // 提交的时候判断图片是否已经上传
       public function submit(){
           $driver_id = input('id');//传过来的id
           $datas = Db::name('driver')->where('id', $driver_id )->find();
           if ($driver_id &&  $datas) {
          
             if(!$datas['card_zm']){
                Result(1,'正面照不能为空');
             }elseif(!$datas['card_fm']) {
                 Result(1,'反面照不能为空');
             }elseif(!$datas['card_cx']) {
                 Result(1,'手持照不能为空');
             }elseif(!$datas['car_zm']) {
                 Result(1,'车辆正面不能为空');
             }elseif(!$datas['car_fm']) {
                 Result(1,'车辆反面不能为空');
             }elseif(!$datas['car_cm']) {
                 Result(1,'侧面不能为空');
             }elseif(!$datas['dri_lic_zm']) {
                 Result(1,'驾驶证面照不能为空');
             }elseif(!$datas['dri_lic_fm']) {
                 Result(1,'驾驶证反面照不能为空');
             }else{
                  Result(0,'图片已全部完整');
             }

           }else{
             Result(1,'获取不到id或没有这条id记录');
           }

        }


         // 司机头像更新
      public function uploadhead(){
            // 获取表单上传文件 例如上传了001.jpg
            
        $driver_id = 1;
        if($driver_id && $_FILES){  
             // $driver_id = 1;//传过来的id
              $nameval = key($_FILES);
              
             $file = request()->file($nameval);
             //删除图片
               $datas = Db::name('driver')->where('id', $driver_id )->find();
               $lujing = ROOT_PATH . 'public/'. 'uploads/';
               if($datas[$nameval]) {
                 if(file_exists($lujing.$datas[$nameval])){
                    unlink($lujing.$datas[$nameval]);
                   // Result(0,'删除原头像成功');
                 }
                   
               }
               $info = $file->move(ROOT_PATH . 'public/'. 'uploads');//默认路径

            if($info){
                // 成功上传后 记录到数据库
                // dump($info);exit;
                $urlimg = $info->getSaveName();
                $res = Db::name('driver')->where('id', $driver_id)->update([$nameval => $urlimg]);
                if($res){
                    Result(0,'上传成功');
                }else{
                    Result(1,'上传失败');
                }
                // echo $info->getExtension().'<br />';// 输出 后缀名
                // // 输出 文件夹\文件名
                // echo $info->getSaveName().'<br />';
                // // 输出 文件名
                // echo $info->getFilename(); 
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
           }else{
              Result(1,'上传信息不完整');
           } 


        }
        // 视图
          public function ceshi(){
            // 模板变量赋值
            // echo ROOT_URL;
             $urls = ROOT_URL.'public/peopleinfo';

             $this->assign('urls',$urls);

              // $captcha = new Captcha();
              // return $captcha->entry();    

            return view('ceshi');
         }

          // 
          public function imgs(){
           
            return view('imgs');
         }

     // 司机登陆
          public function driver_login(){

               $driver_id = input('id') ;//传过来的id
              $info =input('post.');
            if($info){          
                $data['mobile'] = input('post.mobile');
                $post_word = md5(input('post.password'));//post来的密码
                $re = Db::name('driver')->where('id', $driver_id )->find();              
                  if($re) {
                    $pass = $re['password'];
                   // 匹配密码                    
                    if($post_word == $pass){
                        //匹配成功
                        Result(0,'登陆成功');
                    }else{
                        Result(1,'密码错误');
                    }
                    
                }else{
                    Result(1,'手机号未注册');
                }
                
              
            }else{
                 Result(1,'请求报错');
            }
           
       }


         // 修改密码
          public function exit_password(){
                $mobile= input('post.mobile');//post来的手机号码             
                 $rep = Db::name('driver')->field('id,mobile,password')->where('mobile', $mobile )->find();
                 // dump($rep);
                if($rep) {
                    // 短信验证码验证手机号
                       // 待处理
                      
                      // 成功之后改修密码
                      $new_password = input('post.newpassword');//post过来的新密码
                      $firm_password = input('post.repassword');;//post过来的确认密码
                      if ($new_password == $firm_password) {
                           $npaw = md5($new_password);
                           $re = Db::name('driver')->where('mobile', $mobile)->update(['password' => $npaw]);
                           // echo '修改密码成功';
                           Result(0,'修改密码成功');
                      }else{
                        // echo '确认密码不匹配';
                        Result(1,'确认密码不匹配');
                      }
                            

                }else{
                    Result(1,'手机号不正确');
                }
                      

           
       }

      // 修改手机号
          public function exit_phone(){
                $mobile= input('post.mobile');//post来的手机号码              
                 $rep = Db::name('driver')->field('id,mobile')->where('mobile', $mobile )->find();
                 // dump($rep);
                 if($rep){

                       // 验证码验证手机号phone
                       // 待处理
                      



                      // 验证成功
                      // echo 'ok';
                       // $new_phone=  $phone;//post来的手机号码   
                      $re = Db::name('driver')->where('mobile', $mobile)->update(['mobile' => $mobile]);
                       if($re){
                            Result(0,'手机号修改成功');
                        }else{
                            Result(1,'手机号修改失败');
                        } 
                 }else{
                     Result(1,'手机号不正确');
                     // $this->exitJsonResult (1, "手机号不正确" );
                 }
         }

         // 个人中心
          public function drivers(){
            $driver_id= input('post.id');//post来的司机id
                // 头像信息
            $maninfo = Db::name('driver')->field('id,headerimg,mobile,surname,truename,vehicle_type_id')->where('id', $driver_id)->find();
            if ($maninfo) {
               dump($maninfo);
               Result(0,'个人信息',$maninfo);
            }else{
               Result(1,'信息获取失败');
            }
   
 


            //  完成订单 收入 顾客


         }

          // 订单列表
          public function order(){
                // 订单列表按最新时间排序
            $order = Db::name('indent')->order('time', 'desc')->select();
            dump($order);
            Result(0,'订单信息',$order);

         }
          // 订单详情
          public function order_details(){
            $oid = input('post.Id');
                // 订单列表按最新时间排序
            $details = Db::name('indent')->where('Id',$oid)->find();
            // dump($order);
            Result(0,'订单详情',$details);

         }
          // 司机接单订单
          public function meet_order(){
            $oid = input('post.Id');
            $dri_id = input('post.id');
            if($oid){
                $status = 1;
                $re = Db::name('indent')->where('Id', $oid)->update(['status' => $status]);
                if ($re) {
                    Result(0,'接单成功');
                    //生成接单日志
                    $data['ioid'] = $oid;
                    $data['dri_id'] = $dri_id;
                    $data['status'] = 1;
                    $data['addtime'] = time(); 
                    $res = Db::name('meetorder_log')->insert($data);
                    if($res) {
                      Result(0,'接单日志成功');
                    }else{
                       Result(1,'接单日志失败');
                    }

                }else{
                    Result(1,'接单失败，请重试');
                }
            }
            
            
         }

          // 回程单
          public function back_order(){

                      echo 6666666666;
         }


      public function fasong(){
           $data['username'] = 'wowssso';
           $data['password'] = 125853;
           // $data['name'] = 'name';
           $data["password_confirm"] = "16111";
           $data['roleid'] = 'roleid';
           $data['phone'] = '5165165161651';
           // $data['email'] = 'email@qq.com';
           // $data['status'] = '1';
           $res = Db::name('admin')->insert($data);
           if ($res) {
             Result(0,'成功');
           }else{
             Result(1,'失败');
           }
       }






}
