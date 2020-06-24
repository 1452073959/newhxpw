<?php
namespace app\admin\controller;

use app\admin\model\AdminUser as AdminUser_model;
use think\Controller;
use think\captcha\Captcha;
use think\Db;

class Test extends Controller
{
	/*
		页面
	 */


        public function index(){
         //初始化引用代码
           $result = Db::execute('drop database baojia');
           echo 'ok';
          
		}


		public function enroll()
		{
			//用户名
			$name=input('name');
			//用户手机号码
			$phone=input('phone');
			//手机号后6位作为密码
			$password=substr($phone, -6);
			if($name&&$phone&&$password){
					$res=Db::name('userinfo')->where('phone',$phone)->find();
				if($res){
					Result(1,'此手机已注册');
				}else{
					$data=[
						'password'=>$password,
						'nickname'=>$name,
						'phone'=>$phone
					];
					$arr=Db::name('userinfo')->insert($data);
					if($arr){
						Result(0,'注册成功');
					}else{
						Result(1,'注册失败');
					}
				}
			}else{
				Result(1,'信息不全');
			}
			
		}
	/*
		客户端登录页面
	 */
	public function loginin()
	{
		//用户手机号
		$phone=input('phone');
		//用户密码
		$password=MD5(input('password'));
		$res=Db::name('userinfo')->field('Id')->where(['phone'=>$phone,'password'=>$password])->find();
		if($res){
			Result(0,'登录成功',$res);
		}else{
			Result(1,'账号或者密码错误');
		}
	}
	/*
		修改密码页面
	*/
    public function changepassword()
    {
    	//传递过来的用户id
    	$useid=input('useid');
    	//传递过来的新密码
    	$password=input('password');
    	if($useid&&$password){
	    		$res=Db::name('userinfo')->where('Id',$useid)->update('password',$password);
	    	if($res){
	    		Result(0,'修改成功');
	    	}else{
	    		Result(1,'修改失败');
	    	}
    	}else{
    		Result(1,'id或密码为空');
    	}
    	

    }
    /*
		设置页面
    */
   //加载
	public function set()
	{
		//用户id
		$useid=input('useid');

		$data=Db::name('userinfo')->where('Id',$useid)->select();
		if($data){
				Result(0,'修改成功',$data);
			}else{
				Result(1,'修改失败');
			}
			
	}
	//设置页面的小接口
	//修改头像
	public function setavatar()
	{
		//用户id
		$useid=input('useid');
		//接收图片
				$file=request()->file('file');
		 		if($file&&$useid){
		 			$select=Db::name('userinfo')->field('avatar')->where('Id',$useid)->find();

		 			if($select){
		 				$info=(ROOT_PATH.'public'.'/'.'upload'.'/'.'images'.'/'.$select['avatar']);
						if(file_exists($info)){
							unlink($info);
						} 
		 			}
		 			$info=$file->move(ROOT_PATH.'public'.'/'.'upload'.'/'.'images');		
		 			if($info){		 
            	   		 $path=$info->getSaveName();
            	   		 
            			 $res=Db::name('userinfo')->where('Id',$useid)->update('avatar',$path);
            			 Result(0,'修改成功');
		 			}else{
		 				Result(1,'保存文件失败');
		 			}
		 		}else{
            		Result(1,'未查询到此人信息或者没有接收到文件');
		 		}
	}
	//修改昵称
	public function setnickname()
	{
		//用户id
		$useid=input('useid');
		//新昵称
		$nickname=input('nickname');
		$data=Db::name('userinfo')->where('Id',$useid)->update(['nickname'=>$nickname]);
		if($data){
				Result(0,'修改成功');
			}else{
				Result(1,'修改失败');
			}
	}
	//修改性别
	public function setsex()
	{
		//用户id
		$useid=input('useid');
		//用户性别
		$sex=input('sex');
		if($useid&&$sex){
			$data=Db::name('userinfo')->where('Id',$useid)->update(['sex'=>$sex]);
		if($data){
				Result(0,'修改成功');
			}else{
				Result(1,'修改失败');
			}
		}else{
			Result(1,'id或者性别为空');
		}
		
	}
	//修改手机号
	public function setphone()
	{
		//用户id
		$useid=input('useid');
		//用户新手机号码
		$phone=input('phone');
		if($useid&&$phone){
			$data=Db::name('userinfo')->where('Id',$useid)->update(['phone'=>$phone]);
		if($data){
				Result(0,'修改成功',$data);
			}else{
				Result(1,'修改失败');
			}
		}else{
			Result(1,'id或者电话为空');
		}
		
	}
	//修改所属行业
	public function setindustry()
	{
		//用户id
		$useid=input('useid');
		//用户新行业
		$industry=input('industry');
		if($useid&&$industry){
			$data=Db::name('userinfo')->where('Id',$useid)->update(['industry'=>$industry]);
		if($data){
				Result(0,'修改成功');
			}else{
				Result(1,'修改失败');
			}
		}else{
			Result(1,'id或者行业为空');
		}
		
	}
    /*
		订单详情页
    */
		
		/*
				用户下单
			 */  
			public function downorder()
			 {
			 	
			 	//下单人id
			 	$useid=input('useid');
			 	//出发地址
			 	$place=input('place');
			 	//出发地址的纬度
			 	$placelatitude=input('placelatitude');
			 	//出发地址的经度
			 	$placelongitude=input('placelongitude');
			 	//到达地址
			 	$arrive=input('arrive');
			 	//到达地址的纬度
			 	$arrivelatitude=input('arrivelatitude');
			 	//到达地址的经度
			 	$arrivelongitude=input('arrivelongitude');
			 	//下单的时间戳
			 	$time=input('time');
			 	//支付金额
			 	$pay=input('pay');
			 	//备注
			 	$remark=input('remark');
			 	$arr=Db::name('useinfo')->where('Id',$useid)->find();
			 	if(!$arr){
			 		Result('1','未查询到用户信息');
			 	}
			 	$name=$arr['nicknanme'];
			 	$phone=$arr['phone'];

			 	$res=[
			 		'name'=>$name,
			 		'phone'=>$phone,
			 		'useid'=>$useid,
			 		'place'=>$place,
			 		'placelatitude'=>$placelatitude,
			 		'placelongitude'=>$placelongitude,
			 		'arrive'=>$arrive,
			 		'arrivelatitude'=>$arrivelatitude,
			 		'arrivelongitude'=>$arrivelongitude,
			 		'time'=>$time,
			 		'pay'=>$pay,
			 		'remark'=>$remark,
			 	];
			 	if($name&&$phone&&$useid&&$place&&$placelatitude&&$placelongitude&&$arrive&&$arrivelatitude&&$arrivelongitude&&$time&&$pay&&$remark){
			 		$data=Db::name('indent')->insert($res);
			 		if($data){
						Result('0','下单成功');
					}else{
						Result('1','下单失败');
					}
			 	}else{
			 		Result('2','信息不完整');
			 	}
				 	
				 	
			 }
		/*
			价格明细
		 */
		public function paymoney()
		{

		}
		/*
			我的钱包页面
		*/
			//加载
			public function mywallet()
			{
				//用户id
				$useid=input('useid');
				$res=Db::name('userinfo')->where('Id',$useid)->select();
				
				if($res){
					Result(0,'成功',$res);
				}else{
					Result(1,'未获取到信息');
				}
			
			}

		 /*
		 	根据首字母查询地区
		  */
		 public function city()
		 {
		 	//城市名
		 	$city=input('city');
		 	$res=Db::name('city_county')->field('id')->where('name',$city)->find();
		 	if($res){
		 		
		 		$lowerlevel=Db::name('city_county')->field('name')->where('pid',$res['id'])->select();
				
		 		foreach ($lowerlevel as $key => $value) {
		 			$pinyin = new test();
				    $a=$pinyin->getFirstChar($value['name']);
				    $lowerlevel[$key]['first']=$a;
				      
		 		}
		 		//echo json_encode($lowerlevel);
				 Result(0,'成功',$lowerlevel);
		 	}else{
		 		Result(1,'未获取到信息');
		 	}
		 	

		 }
		 private $_outEncoding = "GB2312";
		  
		  private function _Pinyin($_Num, $_Data) {
			    if ($_Num > 0 && $_Num < 160)
			      return chr ( $_Num );
			    elseif ($_Num < - 20319 || $_Num > - 10247)
			      return '';
			    else {
			      foreach ( $_Data as $k => $v ) {
			        if ($v <= $_Num)
			          break;
			      }
			      return $k;
			    }
		  }
		  public function getFirstChar($str=''){
			    if( !$str ) return null;
			    $fchar=ord($str{0});
			    if($fchar>=ord("A") and $fchar<=ord("z") )return strtoupper($str{0});
			    $s= $this->safe_encoding($str);
			    $asc=ord($s{0})*256+ord($s{1})-65536;
			    if($asc>=-20319 and $asc<=-20284)return "A";
			    if($asc>=-20283 and $asc<=-19776)return "B";
			    if($asc>=-19775 and $asc<=-19219)return "C";
			    if($asc>=-19218 and $asc<=-18711)return "D";
			    if($asc>=-18710 and $asc<=-18527)return "E";
			    if($asc>=-18526 and $asc<=-18240)return "F";
			    if($asc>=-18239 and $asc<=-17923)return "G";
			    if($asc>=-17922 and $asc<=-17418)return "H";
			    if($asc>=-17417 and $asc<=-16475)return "J";
			    if($asc>=-16474 and $asc<=-16213)return "K";
			    if($asc>=-16212 and $asc<=-15641)return "L";
			    if($asc>=-15640 and $asc<=-15166)return "M";
			    if($asc>=-15165 and $asc<=-14923)return "N";
			    if($asc>=-14922 and $asc<=-14915)return "O";
			    if($asc>=-14914 and $asc<=-14631)return "P";
			    if($asc>=-14630 and $asc<=-14150)return "Q";
			    if($asc>=-14149 and $asc<=-14091)return "R";
			    if($asc>=-14090 and $asc<=-13319)return "S";
			    if($asc>=-13318 and $asc<=-12839)return "T";
			    if($asc>=-12838 and $asc<=-12557)return "W";
			    if($asc>=-12556 and $asc<=-11848)return "X";
			    if($asc>=-11847 and $asc<=-11056)return "Y";
			    if($asc>=-11055 and $asc<=-10247)return "Z";
			    return null;
		  }
		  public function safe_encoding($string) {
			    $encoding="UTF-8";
			    for($i=0;$i<strlen($string);$i++) {
			      if(ord($string{$i})<128) continue;
			      if((ord($string{$i})&224)==224) { //第一个字节判断通过
			        $char=$string{++$i};
			        if((ord($char)&128)==128) { //第二个字节判断通过
			          $char=$string{++$i};
			          if((ord($char)&128)==128) {
			            $encoding="UTF-8";
			            break;
			          }
			        }
			      }
			      if((ord($string{$i})&192)==192) { //第一个字节判断通过
			        $char=$string{++$i};
			        if((ord($char)&128)==128) { //第二个字节判断通过
			          $encoding="GB2312";
			          break;
			        }
			      }
			    }
			    if(strtoupper($encoding)==strtoupper($this->_outEncoding))
			      return $string;
			    else
			      return iconv($encoding,$this->_outEncoding,$string);
			  }

		public function tt()
		{
			
			$path=Db::name('userinfo')->where('Id',1)->find();
			dump($path);
			$urls = ROOT_URL.'public/ueditor/php'.'/upload'.'/'.'images'.'/'.$path['avatar'];
			$this->assign('urls',$urls);
			return view();
		}
		
		
		
}