<?php
namespace app\applet\controller;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
 // //同源策略 跨域请求 头设置
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
use think\Db;
use think\Controller;
 
class Base extends Controller{
    //初始化验证模块
    
    /**
     * API数据返回，支持跨域方法
     */
    public function json( $code, $msg = '',$data = [],$url='' ){
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => $_SERVER['REQUEST_TIME'],
            'data' => $data,
        ];
        echo json_encode($result,JSON_UNESCAPED_UNICODE);//中文不转译
        exit(0);
    }

    // 设置登录秘钥
    protected function setToken($id,$day=7){
        $time_out = time() + 86400*$day;
        $token = sha1(md5($id.$time_out).rand(1,999).'&*hxpw#$');
        $res = [
            'userid'     => $id,
            'timeout'    => $time_out,
            'token'      => $token,
        ];
        Db::name('admin')->update($res);
        return $token;
    }

    //获取图片完整路径 并判断图片是否存在 不存在找到替换
    public function getImgSrc($src,$path="uploads/images/",$http='http://'){
        if(file_exists($path.$src) && $src != '/' && !empty($src)){
            $src = str_replace('\\','/',$src);
            return $http.$_SERVER['HTTP_HOST'].'/'.$path.$src;
        }else{
            return $http.$_SERVER['HTTP_HOST']."/static/imgs/logo1.png";
        }
    }
    
}