<?php
// +----------------------------------------------------------------------
// | 前台token验证api接口
// +----------------------------------------------------------------------
namespace app\home\controller;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
 // //同源策略 跨域请求 头设置
header('content-type:text/html;charset=utf8 ');
header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
use think\helper\Hash;
use think\Session;
use think\Controller;
use think\Db;
class Token extends Controller
{
    //下面是生成token方法代码

    public static function settoken()

    {

        $str = md5(uniqid(md5(microtime(true)),true));  //生成一个不会重复的字符串

        $str = sha1($str);  //加密

        return $str;

    }

     public static function checktokens($token, $table)

    {

        $res = Db::table($table)->where('token',$token)->find();

        if (!empty($res))

        {

            if (time() - $res['time_out'] > 0)

            {

                return 4;  //token长时间未使用而过期，需重新登陆

            }

            $new_time_out = time() + 604800;//604800是七天

            if (Db::table($table)->where('token',$token)->update(array('time_out' => $new_time_out)))

            {

                return 1;  //token验证成功，time_out刷新成功，可以获取接口信息

            }

        }

        return 2;  //token错误验证失败

    }
}