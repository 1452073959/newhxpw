<?php

// +----------------------------------------------------------------------
// | 登录验证
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class Contract extends Validate
{

    //定义验证规则
    protected $rule = [
        'client' => 'require',
        'number' => 'require',
        'theme' => 'require',
        'sign_time' => 'require',
        'we_rep' => 'require',

    ];
    //定义验证提示
    protected $message = [
        'client.require'  => '关联客户不能为空！',
        'number.require'   => '合同编号不能为空！',
        'theme.require'  => '合同主题不能为空',
        'sign_time.require'   => '签订时间不能为空！',
        'we_rep.require'  => '我方代表不能为空',


    ];


    //定义验证场景
    protected $scene = [
        'adds' => ['client', 'number', 'theme', 'sign_time', 'we_rep'],

    ];

}
