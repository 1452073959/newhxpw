<?php
namespace app\admin\controller;

use app\admin\model\AdminUser as AdminUser_model;
use think\Controller;
use think\captcha\Captcha;
use think\Db;
use think\Session;
class Indextop extends Controller
{
	public function tt()
	{
		$this->assign('name','1');
		$this->assign('nian','2');
		return view();
	}
	public function users()
	{

		$res = Db::name('driver')->select(); 
		 $urls = ROOT_URL.'public/uploads';
             $this->assign('urls',$urls);   
		$this->assign('data',$res);    
		return view();
	}
}