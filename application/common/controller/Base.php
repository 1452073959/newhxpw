<?php


// +----------------------------------------------------------------------
// | 公共控制模块
// +----------------------------------------------------------------------
namespace app\common\controller;

use think\Controller;
use think\Db;
use util\sms\SignatureHelper;
class Base extends Controller
{
    public static $Cache = array(); //全局配置缓存

    //初始化
    protected function initialize()
    {
        $this->initSite();
    }

    //初始化站点配置信息
    protected function initSite()
    {
        $Config = cache("Config"); //获取所有配置名称和值
        self::$Cache['Config'] = $Config; //后端调用
        $this->assign("Config", $Config); //前端调用
        // 权限更新
        $auth = Session('admin_user_auth');
        if (!empty($auth)) {
            $rules = '';
            $custom_rules = '';
            if ($auth['roleid'] != 1) {
                $is_rule = Db::name('admin')->field('is_rule,rule')->where(['userid' =>$auth['uid']])->find();
                if ($is_rule['is_rule'] == 1) {
                    $json = json_decode($is_rule['rule'],true);
                    foreach ($json as $k => $v) {
                        if (!empty($v)) {
                            $rules = $v.','.$rules;
                        }
                    }
                    $custom_rules = $rules;
                    $rules = Db::name('auth_rule')->where('id','in',trim($rules,','))->column('title','id');
                }else{
                    $rules = Db::name('AuthGroup')->where([['status', 'neq', 0],['id', 'eq', $auth['roleid']]])->value('rules');
                    $rules = Db::name('auth_rule')->where('id','in',$rules)->column('title');
                }
            }
            $auth['rules'] = $rules;
            $auth['custom_rules'] = $custom_rules;
            Session('admin_user_auth', $auth);
            Session('admin_user_auth_sign', data_auth_sign($auth));
        }
    }

    //空操作
    public function _empty()
    {
        $this->error('该页面不存在！');
    }
    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @return void
     */
    protected function ajaxReturn($data,$type='') {
        if(empty($type)) $type  =   'json';
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET[config('VAR_JSONP_HANDLER')]) ? $_GET[config('VAR_JSONP_HANDLER')] : config('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);            
        }
    }
    // 短信通知
    public function noticeSendSms($mobile,$name,$SignName,$TemplateCode)
    {
        $KeyId  = config('accessKeyId');
        $Secret = config('accessKeySecret');
        if (empty($mobile) || empty($name) || empty($KeyId) || empty($Secret) || empty($SignName) || empty($TemplateCode)) {
            return false;
        }
        $data = [
            'PhoneNumbers'  => $mobile,
            'SignName'      => $SignName,//短信签名
            'TemplateCode'  => $TemplateCode,
            'TemplateParam' => '{"name":"'.$name.'"}',
            'RegionId'      => 'cn-hangzhou',
            'Action'        => 'SendSms',
            'Version'       => '2017-05-25',
        ];
        $helper  = new \util\sms\SignatureHelper();
        $content = $helper->request($KeyId,$Secret,"dysmsapi.aliyuncs.com",$data,false);
        return $content;
    }
}
