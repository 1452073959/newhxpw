<?php
// +----------------------------------------------------------------------
// | 后台登录页
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\AdminUser as AdminUser_model;
use think\Controller;
use think\captcha\Captcha;
use think\Db;
class Login extends Controller
{
    //登录判断
    public function index()
    {
       $AdminUser_model = new AdminUser_model;

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (empty($data['phone'])) {
                if ($AdminUser_model->login($data['username'], $data['password'])) {
                    $this->login();
                } else {
                    $info['status'] = 0;
                    $info['info']   = $AdminUser_model->getError();
                    $this->error($AdminUser_model->getError());
                    echo json_encode($info);die;
                }
            }
        } else {
            if ($AdminUser_model->isLogin() && input('type') != 1) {
                $this->redirect('admin/index/index');
            }
            return $this->fetch();
        }
          
    }
    public function login()
    {
        $data = Session('admin_user_auth');
        // dump($data);exit;
        if ($data['sid'] != 0) {
            $row = Db::name('shop')->where('id',$data['sid'])->find();
            if($row['status'] == 1){
                $info['status'] = 1;
                $info['url']    = url('admin/Index/index');
                $this->redirect('admin/index/index');
                echo json_encode($info);die;
            }else{
                session(null);
                cookie("forward", NULL);
                $info['status'] = 0;
                $info['info']   = '您账号被禁用，请联系总管理';
                echo json_encode($info);die;
            }
        }else{
            $info['status'] = 1;
            $info['url']    = url('admin/Index/index');
             $this->redirect('admin/index/index');
            echo json_encode($info);die;
        }
    }

    public function tuiguang()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $re   = Db::name('channel_promotion')->where($data)->find();
            if($re){
                $info = array('status'=>1,'url'=>'/admin/marketing/share_count.html?id='.$re['id'].'&status=1');
            }else{
                $info = array('status'=>2,'info'=>'账号或密码错误');
            }
            header('Content-Type:application/json; charset=utf-8');
            echo json_encode($info);
        }else{
            return $this->fetch();
        }
    }

    // 获取手机验证码
    public function Phone_sms()
    {
        $phone = input('phone');
        if (!empty($phone)) {
            $pyid = Db::name('admin')->field('pyid')->where(['phone' =>$phone])->find();
            if (empty($pyid)) {
                $info['status'] = 2;
                $info['info']   = '查无此人';
                echo json_encode($info);die;
            }
            // 查询数据价格
            $rule_money = Db::name('consumption_rule')->field('id,money,name')->where(['code' => 'reg_sms'])->find();
            if (empty($rule_money)) {
                $info['status'] = 3;
                $info['info']   = 'type参数错误';
                echo json_encode($info);die;
            }
            // 超管
            if ($pyid['pyid'] == '0') {
                $code = rand(100000,999999);
                $msg  = '【名网信用】您的短信验证码为'.$code.'，若非本人操作请忽略。';
                $res  = blueSendSms($phone,$msg);
                if ($res['code'] == '0') {
                    // 添加日志
                    $log1['pyid']        = $pyid['pyid'];
                    $log1['time']        = time();
                    $log1['name']        = $rule_money['name'];
                    $log1['money']       = $rule_money['money'];
                    $log1['type']        = $rule_money['id'];
                    $log1['description'] = $phone;
                    Db::name('shop_consumption_log')->insert($log1);
                    // 添加日志
                    // 短信发送记录
                    $log2['pyid']   = $pyid['pyid'];
                    $log2['mobile'] = $phone;
                    $log2['content']= $msg;
                    $log2['code']   = 'OK';
                    $log2['status'] = 1;
                    $log2['time']   = time();
                    Db::name('sms_log')->insert($log2);

                    $info['status'] = 1;
                    $info['info']   = $code;
                    session('code',$code);
                    echo json_encode($info);die;
                }else{
                    $info['status'] = 4;
                    $info['info']   = '短信验证码发送失败';
                    echo json_encode($info);die;
                }

            }else{
                //查询商户余额
                $shop = Db::name('product_type')->field('money,sign_name,sms10_status,sms10_id,sign_name')->where(['id' => $pyid['pyid']])->find();
                if (empty($shop) || $shop['money']-$rule_money['money'] < 0) {
                    $info['status'] = 3;
                    $info['info']   = '余额不足无法进行发送短信';
                    echo json_encode($info);die;
                }
                if (!empty($shop['sms10_status']) && !empty($shop['sms10_id'])) {
                    $code = rand(100000,999999);
                    $TemplateCode = Db::name('sms_template')->where(['id' =>$shop['sms10_id']])->find();
                    $msg  = '【'.$shop['sign_name'].'】'.str_replace('xxx',$code,$TemplateCode['templatecontent']);
                    $res  = blueSendSms($phone,$msg);
                    if ($res['code'] == '0') {
                        // 添加日志
                        Db::name('product_type')->where(['id' => $pyid['pyid']])->update(['money' =>$shop['money']-$rule_money['money']]);
                        $log1['pyid']        = $pyid['pyid'];
                        $log1['time']        = time();
                        $log1['name']        = $rule_money['name'];
                        $log1['money']       = $rule_money['money'];
                        $log1['type']        = $rule_money['id'];
                        $log1['description'] = $phone;
                        Db::name('shop_consumption_log')->insert($log1);
                        // 添加日志
                        // 短信发送记录
                        $log2['pyid']   = $pyid['pyid'];
                        $log2['mobile'] = $phone;
                        $log2['content']= $msg;
                        $log2['code']   = 'OK';
                        $log2['status'] = 1;
                        $log2['time']   = time();
                        Db::name('sms_log')->insert($log2);

                        $info['status'] = 1;
                        $info['info']   = $code;
                        session('code',$code);
                        echo json_encode($info);die;
                    }else{
                        $info['status'] = 4;
                        $info['info']   = '短信验证码发送失败';
                        echo json_encode($info);die;
                    }
                }else{
                    $info['status'] = 3;
                    $info['info']   = '未开启短信验证码';
                    echo json_encode($info);die;
                }
            }
        }
    }
    //手动退出登录
    public function logout()
    {
        $AdminUser_model = new AdminUser_model;
        if ($AdminUser_model->logout()) {
            // 手动登出时，清空forward
            cookie("forward", NULL);
            $this->redirect('admin/login/index');
        }
    }

    /**
     * 获取验证码
     */
    public function getVerify()
    {
        $captcha = [];
        //设置长度
        $codelen = $this->request->param('length', 4);
        if ($codelen) {
            if ($codelen > 8 || $codelen < 2) {
                $codelen = 4;
            }
            $captcha['length'] = $codelen;
        }
        //设置验证码字体大小
        $fontsize = $this->request->param('font_size', 15);
        if ($fontsize) {
            $captcha['fontSize'] = $fontsize;
        }
        //设置验证码图片宽度
        $width = $this->request->param('imageW', 40);
        if ($width) {
            $captcha['imageW'] = $width;
        }
        //设置验证码图片高度
        $height = $this->request->param('imageH', 110);
        if ($height) {
            $captcha['imageH'] = $height;
        }
        //设置背景颜色
        /*$background = $this->request->param('background');
        if ($background) {
        $checkcode->background = $background;
        }
        //设置字体颜色
        $fontcolor = $this->request->param('font_color');
        if ($fontcolor) {
        $checkcode->fontcolor = $fontcolor;
        }*/
        $captcha = new Captcha($captcha);
        return $captcha->entry();
    }
    // 短信验证码
    public function userSendSms()
    {
        $data = [
            'pyid'      => input('pyid'),
            'phone'     => input('phone'),
        ];
        if (empty($data['pyid'])) {
            $info['status'] = 0;
            $info['info']   = '产品类型不能为空';
            echo json_encode($info);die;
        }
        if (empty($data['phone'])) {
            $info['status'] = 0;
            $info['info']   = '手机号不能为空';
            echo json_encode($info);die;
        }
        $shop = Db::name('product_type')->field('start_status,sign_name')->where(['id' => $data['pyid']])->find();
        if (empty($shop) || $shop['start_status'] != 1) {
            $info['status'] = 0;
            $info['info']   = '该产品还未启用或者不存在';
            echo json_encode($info);die;
        }
        // 查询数据价格
        $rule_money = Db::name('consumption_rule')->field('id,money,name')->where(['code' => 'reg_sms'])->find();
        if (empty($rule_money)) {
            $info['status'] = 0;
            $info['info']   = 'type参数错误';
            echo json_encode($info);die;
        }
        // 查询商户余额
        $shop_money = Db::name('product_type')->field('money')->where(['id' => $data['pyid']])->find()['money'];
        if (empty($shop_money) || $shop_money-$rule_money['money'] < 0) {
            $info['status'] = 0;
            $info['info']   = '余额不足';
            echo json_encode($info);die;
        }
        $code = rand(100000,999999);
        $msg  = '【'.$shop['sign_name'].'】'.'您的短信验证码为'.$code.'，若非本人操作请忽略。';
        $res  = blueSendSms($data['phone'],$msg);
        if ($res['code'] == '0') {
            // 添加日志
            Db::name('product_type')->where(['id' => $data['pyid']])->update(['money' =>$shop_money-$rule_money['money']]);
            $log1['pyid']        = $data['pyid'];
            $log1['time']        = time();
            $log1['name']        = $rule_money['name'];
            $log1['money']       = $rule_money['money'];
            $log1['type']        = $rule_money['id'];
            $log1['description'] = $data['phone'];
            Db::name('shop_consumption_log')->insert($log1);
            // 添加日志
            // 短信发送记录
            $log2['pyid']   = $data['pyid'];
            $log2['mobile'] = $data['phone'];
            $log2['content']= $msg;
            $log2['code']   = 'OK';
            $log2['status'] = 1;
            $log2['time']   = time();
            Db::name('sms_log')->insert($log2);
            $info['status'] = 1;
            $info['info']   = $code;
            echo json_encode($info);die;
        }else{
            $info['status'] = 0;
            $info['info']   = '验证码发送失败';
            echo json_encode($info);die;
        }
    }
    // 渠道推广链接
    public function register()
    {   
        $add  = input('post.');
        // 注册
        if (!empty($add['phone']) && !empty($add['password']) && !empty($add['pyid']) && !empty($add['code'])) {
            if (Db::name('user')->where(['phone' => $add['phone'],'pyid' => $add['pyid']])->find()) {
                $info['status'] = 0;
                $info['info']   = '该手机号已注册';
                echo json_encode($info);die;
            }
            $user['pyid']     = $add['pyid'];
            $user['phone']    = $add['phone'];
            $user['status']   = 1;
            $user['reg_type'] = $add['code'];
            $user['add_time'] = time();
            $password         = encrypt_password($add['password']);
            $user['encrypt']  = $password['encrypt'];
            $user['password'] = $password['password'];
            $re = Db::name('user')->insert($user);
            if (!$re) {
                $info['status'] = 0;
                $info['info']   = '注册失败';
                echo json_encode($info);die;
            }else{
                $info['status'] = 1;
                $info['info']   = '注册成功';
                echo json_encode($info);die;
            }
        }else{
            $id   = input('pyid');
            $code = input('code');
            if (empty($id) || empty($code)) {
                $this->error('参数错误');
            }
            $shop = Db::name('product_type')->field('merchant_logo logo,name,id')->where(['id' =>$id])->find();
            if (empty($shop)) {
                $this->error('不存在该商户');
            }
            $data = Db::name('channel_promotion')->where(['code' =>$code])->find();
            if (empty($data)) {
                $this->error('不存在该推广人员');
            }
            $app  = Db::name('app_template')->where(['id' =>$data['template_id']])->find();
            if (empty($app)) {
                $this->error('不存在该模板');
            }
            $this->assign('shop',$shop);
            $this->assign('app',$app);
            $this->assign('code',$data['id']);
            return $this->fetch();
        }
    }
}
