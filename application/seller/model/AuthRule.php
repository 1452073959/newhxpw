<?php


namespace app\seller\Model;

use think\Model;

/**
 * 权限规则模型
 */
class AuthRule extends Model
{
    const RULE_URL = 1;
    const RULE_MAIN = 2; //主菜单

    protected $table = "__SELLER_AUTH_RULE__";
}
