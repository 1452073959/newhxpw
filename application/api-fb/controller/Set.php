<?php
namespace app\api\controller;
use app\api\controller\Base;
use think\Request;
use think\Db;
 
 
class Set extends Base
{
    // 首页
    public function notify()
    {   
        if (!empty($_POST)) {
            foreach ($_POST as $k => $v) {
                file_put_contents(ROOT_PATH. '/App_nd.txt', $k.':'.$v.PHP_EOL,FILE_APPEND);
            }
        }
        $re = $this->verifyNotify();
        if ($re == false) {
            return false;
        }else{
            if (empty($_POST['extension'])) {
                $data = [
                    'outer_trade_no'    => input('outer_trade_no'),
                    'withdrawal_status' => input('withdrawal_status'),
                ];
            }else{
                $data = [
                    'outer_trade_no'    => $_POST['extension']['outer_trade_no'],
                    'withdrawal_status' => $_POST['extension']['withdrawal_status'],
                ];
            }
            if (empty($data['outer_trade_no']) || empty($data['withdrawal_status'])) {
                return false;
            }
            if ($data['withdrawal_status'] == 'WITHDRAWAL_SUCCESS') {
                $this->orderOk($data['outer_trade_no']);
            }else{
                $res['status'] = 1;
                $res['id']     = $data['outer_trade_no'];
                Db::name('loan_application')->update($res);
            }
            echo 'success';
        }

    }
    // 微信扫码支付
    public function wx_notify()
    {
        $mchid  = '1518944531';          //微信支付商户号 PartnerID 通过微信支付商户资料审核后邮件发送
        $appid  = 'wx75968440bfc5ee89';  //公众号APPID 通过微信支付商户资料审核后邮件发送
        $apiKey = '8934e7d15453e97507ef794cf7b0519d';   //https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
        $wxPay  = new \util\wx\Pay($mchid,$appid,$apiKey);
        $data = $wxPay->notify();
        if (empty($data)) {
           return false;
        }
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                file_put_contents(ROOT_PATH. '/App_wx.txt', $k.':'.$v.PHP_EOL,FILE_APPEND);
            }
        }
        if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
            Db::name('wx_recharge')->where(['id' => $data['out_trade_no']])->update(['status' => 1]);
            $recharge = Db::name('wx_recharge')->field('money,pyid')->where(['id' => $data['out_trade_no']])->find();
            $money    = Db::name('product_type')->field('money')->where(['id' => $recharge['pyid']])->find();
            Db::name('product_type')->where(['id' => $recharge['pyid']])->update(['money' => $money['money']+$recharge['money']]);
            Db::name('wx_recharge')->where(['id' => $data['out_trade_no']])->update(['status' => 1]);
        }
    }
    // 交易成功改变状态
    public function orderOk($id)
    {
        if (empty($id)) {
            return false;
        }
        $data  = Db::name('loan_application')->where(['id' => $id])->find();
        if (!empty($data['status']) && $data['status'] != 2) {
            $res['id']             = $id;
            $res['operation_time'] = time();
            $res['lend_time']      = time();
            $res['agreed_time']    = strtotime("+".$data['apply_days']."day",time());
            $res['status']         = 2;
            Db::name('loan_application')->update($res);
            // 增加借款记录
            $log['pyid']           = $data['pyid'];
            $log['oid']            = $id;
            $log['uid']            = $data['uid'];
            $log['name']           = $data['name'];
            $log['mobile']         = Db::name('user')->field('phone')->where(['id' => $data['uid']])->find()['phone'];
            $log['get_money']      = $data['get_money'];
            $log['time']           = time();
            $log['type']           = 1;
            $log['pay_name']       = '畅捷';
            Db::name('loan_log')->insert($log);
            // 借款成功通知
            // 查询数据价格
            $rule_money = Db::name('consumption_rule')->field('id,money,name')->where(['code' => 'loan_sms'])->find();
            if (empty($rule_money)) {
                return false;
            }
            if ($data['pyid'] != '0') {
                // 查询商户余额
                $shop = Db::name('product_type')->field('money,sign_name,sms3_status,sms3_id')->where(['id' => $data['pyid']])->find();
                if (empty($shop) || $shop['money']-$rule_money['money'] < 0) {
                    return false;
                }
                if (!empty($shop['sms3_status']) && !empty($shop['sms3_id'])) {
                    $TemplateCode = Db::name('sms_template')->where(['id' =>$shop['sms3_id']])->find();
                    $msg  = '【'.$shop['sign_name'].'】'.str_replace('xxx',$data['name'],$TemplateCode['templatecontent']);
                    $res  = blueSendSms($data['phone'],$msg);
                    if ($res['code'] == '0') {
                        // 添加日志
                        Db::name('product_type')->where(['id' => $data['pyid']])->update(['money' =>$shop['money']-$rule_money['money']]);
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
                        $log2['content']= '【'.$shop['sign_name'].'】'.str_replace('xxx',$data['name'],$TemplateCode['templatecontent']);
                        $log2['code']   = 'OK';
                        $log2['status'] = 1;
                        $log2['time']   = time();
                        Db::name('sms_log')->insert($log2);
                    }
                }
            }
        }
    }
    // 验签
    public function verifyNotify()
    {
        if (empty($_POST)) {
            return false;
        }else{
            if (empty($_POST['outer_trade_no'])) {
                return false;
            }
            $pyid = Db::name('loan_application')->field('pyid')->where(['id' => $_POST['outer_trade_no']])->find();
            if (empty($pyid['pyid'])) {
                return false;
            }
            $path = Db::name('product_type')->field('public')->where(['id' => $pyid['pyid']])->find();
            $re   = rsaVerify($_POST,$_POST['sign'],ROOT_PATH.$path['public']);
            if ($re) {
                return true;
            }else{
                return false;
            }
        }
    }
    // 到期提醒(定时任务)
    public function expireRemind()
    {
        $time =  file_get_contents(ROOT_PATH.'/timed_task.txt');
    }
    // 定时任务
    // public function timed_task()
    // {
    //     $time =  file_get_contents(ROOT_PATH.'/Data/timed_task.txt');
    //     if ($time != date('Y-m-d')) {
    //         // 订单自动收货
    //         $cache = M("Shop_order")->where(['status' => 3])->field('id,fahuokdnum')->select();
    //         $auto  = M("Set")->field('is_auto_qs,auto_qs_day')->find();
    //         if ($auto['is_auto_qs'] == 1 && !empty($auto['auto_qs_day'])) {
    //             $day = $auto['auto_qs_day'];
    //             foreach ($cache as $k => $v) {
    //                 if ($v['kd_type'] == 1) {
    //                     $data = unserialize($v['kdcontent']);
    //                 }else{
    //                     $data = wl($v['fahuokdnum']);
    //                 }
    //                 if ($data['status'] == 200 && $data['state'] == 3) {
    //                     $kdtime = $data['data'][0]['time'];
    //                     $otime  = strtotime("$kdtime +$day day");
    //                     if (time() > $otime) {//当前时间大于最晚收货时间执行
    //                         $this->order_ok($v['id']);
    //                     }
    //                 }
                    
    //             }
    //         }
    //         file_put_contents(ROOT_PATH.'/Data/timed_task.txt',date('Y-m-d'));
    //     }
    // }
    // 用户验证
    public function test()
    { 
        die;
        $url='http://www.fengkong.com/api/index/interest';
        $data = [
                'id'=>47,
                'money'=>1000,
                'day'=>5,
                'timestamp'   =>time()
        ];
        $sign = '';
        ksort($data);
        foreach ($data as $key => $value) {
            $sign .= '&' . strval($key) . '=' . strval($value);
        }
        $data['sign']=md5(substr($sign, 1) . $this->cert);
        echo fkd_curl($url,$data);
        // $data=json_decode(fkd_curl($url,$data),true);
        // dump($data);
    }
}
