<?php

// +----------------------------------------------------------------------
// | 后台欢迎页
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\Notice;
use app\admin\model\Personnel;
use app\common\controller\Adminbase;
use think\Db;
use think\Request;
use app\admin\model\Department;
class Main extends Adminbase
{
    //欢迎首页
    public function index()
    {
        if (Session('admin_user_auth')['sid'] != '0') {
            $map1[] = $maps[] = ['sid','eq',Session('admin_user_auth')['sid']];
            $where['id']     = Session('admin_user_auth')['sid'];
        }else{
          $maps   = '';
          $where  = '';
        }
        // $username['username'] =  $_SESSION['username'];
        // $logininfo   =     
        $re     = Session('admin_user_auth');


        $getcid = Db::name('admin')->where(array('userid'=>$re['uid']))->find();
        $huoid = array('name'=>getcid($getcid['companyid']));
        $rerole = Db::name('auth_group')->field('title,description')->where(array('id'=>$re['roleid']))->find();      
        $rerole = array_merge($huoid,$rerole);
        $py     = Db::name('shop')->field('name,money,phone')->where($where)->find();
        $gonggao= Db::name('notice_list')->order('time desc')->find();
        $gonggao['time']=date('Y-m-d H:i:s',$gonggao['time']);
        $countuser=count(Db::name('userinfo')->select());
        $countdrive=count(Db::name('driver')->select());
        $countclick='';
        // $this->assign('logininfo',$logininfo);
        $this->assign('countuser',$countuser);
        $this->assign('countdrive',$countdrive);
        $this->assign('py',$py);
        $this->assign('re',$re);
        $this->assign('rerole',$rerole);
        $this->assign('gonggao',$gonggao);
        $this->assign('userInfo', $this->_userinfo);
        $this->assign('sys_info', $this->get_sys_info());

//         dump($re);die;
        
          // 统计表数据
        $count_sj = count(Db::name('driver')->select());
        $count_djl = 59;
        $count_yh = 17;
        $countall = array($count_djl,$count_sj,$count_yh);

        $notice=Db::table('fdz_notice')->order('create_time','desc')->select();
        // dump($countall); 
        $this->assign('countall', $countall);
        $this->assign('notice', $notice);
        $this->assign('session_info', $_SESSION);
        return $this->fetch();
    }

    //phpinfo信息 按需显示在前台
    public function get_sys_info()
    {
        //$sys_info['os'] = PHP_OS; //操作系统
        $sys_info['ip'] = GetHostByName($_SERVER['SERVER_NAME']); //服务器IP
        $sys_info['web_server'] = $_SERVER['SERVER_SOFTWARE']; //服务器环境
        $sys_info['phpv'] = phpversion(); //php版本
        $sys_info['fileupload'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknown'; //文件上传限制
        //$sys_info['memory_limit'] = ini_get('memory_limit'); //最大占用内存
        //$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false; //最大执行时间
        //$sys_info['zlib'] = function_exists('gzclose') ? 'YES' : 'NO'; //Zlib支持
        //$sys_info['safe_mode'] = (boolean) ini_get('safe_mode') ? 'YES' : 'NO'; //安全模式
        //$sys_info['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        //$sys_info['curl'] = function_exists('curl_init') ? 'YES' : 'NO'; //Curl支持
        //$sys_info['max_ex_time'] = @ini_get("max_execution_time") . 's';
        $sys_info['domain'] = $_SERVER['HTTP_HOST']; //域名
        //$sys_info['remaining_space'] = round((disk_free_space(".") / (1024 * 1024)), 2) . 'M'; //剩余空间
        //$sys_info['user_ip'] = $_SERVER['REMOTE_ADDR']; //用户IP地址
        $sys_info['beijing_time'] = gmdate("Y年n月j日 H:i:s", time() + 8 * 3600); //北京时间
        $sys_info['time'] = date("Y年n月j日 H:i:s"); //服务器时间
        //$sys_info['web_directory'] = $_SERVER["DOCUMENT_ROOT"]; //网站目录
        $mysqlinfo = Db::query("SELECT VERSION() as version");
        $sys_info['mysql_version'] = $mysqlinfo[0]['version'];
        if (function_exists("gd_info")) {
            //GD库版本
            $gd = gd_info();
            $sys_info['gdinfo'] = $gd['GD Version'];
        } else {
            $sys_info['gdinfo'] = "未知";
        }
        return $sys_info;
    }

    //新增用户曲线图
    public function zhuceliang()
    {
        $j         = date("t"); //获取当前月天数
        $monthlist = array();
        for($i = 0; $i < $j; $i++){
            $monthlist[] = date('m-d',strtotime("-".$i." day"));
        }
        sort($monthlist);
        if (Session('admin_user_auth')['pyid'] != '0') {
          $map = "AND pyid = ".Session('admin_user_auth')['pyid'];
        }else{
          $map = '';
        }
        $semRes = Db::query("select FROM_UNIXTIME(add_time,'%m-%d') days,count(id) count from yzn_user WHERE FROM_UNIXTIME(add_time,'%Y-%m') {$map} group by days");

        $ySemData = array();
        if(!empty($semRes))
        {
            foreach ($monthlist as $k=>$v)
            {
                foreach ($semRes as $kk=>$vv)
                {

                    if($v == $vv['days'])
                    {
                        $ySemData[$k] = $vv['count'];
                        break;
                    }else{
                        $ySemData[$k] = 0;
                        continue;
                    }
                }
            }
        }else{
            foreach ($monthlist as $k=>$v)
            {
                     $ySemData[$k] = 0;
            }
        }
        $dataString = array_values($ySemData);
        $countlist = $dataString;
        // $month = $monthlist;
        // dump($monthlist);
        $data = array(
                'countlist'=>$countlist,
                'monthlist'=>$monthlist
            );
        echo json_encode($data);
    }

    //回款曲线图
    public function repayment()
    {
        $j         = date("t"); //获取当前月天数
        $monthlist = array();
        for($i = 0; $i < $j; $i++){
            $monthlist[] = date('m-d',strtotime("-".$i." day"));
        }
        sort($monthlist);
        if (Session('admin_user_auth')['pyid'] != '0') {
          $map = "AND pyid = ".Session('admin_user_auth')['pyid'];
        }else{
          $map = '';
        }
        $semRes   = Db::query("select FROM_UNIXTIME(time,'%m-%d') days,sum(repayment_money) money from yzn_loan_log WHERE FROM_UNIXTIME(time,'%Y-%m') AND type = 2 {$map} group by days");
        $ySemData = array();
        $money    = 0;
        if(!empty($semRes))
        {
            foreach ($monthlist as $k=>$v)
            {

                foreach ($semRes as $kk=>$vv)
                {
                if($v == $vv['days'])
                    {

                        $ySemData[$k] = floatval($vv['money']);
                        break;
                    }else{
                        $ySemData[$k] = 0;
                        continue;
                    }
                }
            }
        }else{
            foreach ($monthlist as $k=>$v)
            {
                $ySemData[$k] = 0;
            }
        }
        $dataString = array_values($ySemData);
        $countlist = $dataString;
        // $month = $monthlist;
        // dump($monthlist);
        $data = array(
                'countlist'=>$countlist,
                'monthlist'=>$monthlist
            );
        echo json_encode($data);
    }

    public function query()
    {
        $userinfo = $this->_userinfo;
        $query=Db::table('fdz_memo')->where('aid',$userinfo['userid'])->order('time', 'desc')->select();

        return json(['code'=>0,'msg'=>'请求成功','data'=>$query]);
    }


    public function memo(Request $request,$chooseData,$type)
    {

        $userinfo = $this->_userinfo;
        if($type=='post'){
            $chooseData['aid']=$userinfo['userid'];
            Db::table('fdz_memo')->data($chooseData)->insert();
            return json(['code'=>1,'msg'=>'添加成功','data'=>$chooseData,'type'=>$type]);
        }elseif ($type=='delete')
        {
            Db::table('fdz_memo')->where('aid',$userinfo['userid'])->where('time', $chooseData['time'])->delete();
            return json(['code'=>1,'msg'=>'删除成功','data'=>$chooseData,'type'=>$type]);
        }else{
            Db::table('fdz_memo')->where('aid',$userinfo['userid'])->where('time', $chooseData['time'])->data($chooseData)->update();
            return json(['code'=>1,'msg'=>'修改成功','data'=>$chooseData,'type'=>$type]);
        }
    }


    public function notice(Request $request)
    {
        if (!request()->isGet()){
            $data=$request->post();
            $res= new Notice;
            $res->title     = $data['title'];
            $res->content    = $data['content'];
            $res->save();
              if($res){
                  $this->success('发布成功,可关闭该窗口刷新查看');
              }else{
                  $this->error('发布失败');
              }

        }


        return $this->fetch();
    }

    public function show(Request $request )
    {
        $data=$request->get();
        $res= Db::table('fdz_notice')->where('id',$data['id'])->find();
        $this->assign('notice',$res);
        return $this->fetch();
    }

    public function noticelist()
    {
        $data=Db::table('fdz_notice')->order('create_time','desc')->select();
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function noticedelte(Request $request)
    {
        $data=$request->get();
        $res=Db::table('fdz_notice')->where('id',$data['id'])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function noticeedit(Request $request)
    {
        if(!empty($request->post())){
            $data=$request->post();
            $res= Notice::get($data['id']);
            $res->title     = $data['title'];
            $res->content    = $data['content'];
            $res->save();
            if($res){
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }

        if(!empty($request->get())){
            $id=$request->get();
            $notice = Notice::where('id',$id['id'])->find();
            $this->assign('notice',$notice);
            return $this->fetch();
        }

    }


    public function  calendar()
    {
        $userinfo = $this->_userinfo;
        $arr=Department::getCates($pid=0,$userinfo['companyid']);
        foreach ( $arr as &$v ) {
            $v->title = $v->name;
            if (!empty($v['children'])){
                foreach ($v['children'] as $k1 => $v1) {
                  if(!empty($v1['ou'])){
                      $v1->children=$v1['ou'];
                      foreach ($v1['ou'] as $k2=>$v2){
                          $v2->title=$v2->name.$v2->phone;
                      }
                  }
                    $v1->title=$v1->name;
                }
            }else {
                $v->children = $v['ou'];
                if(!empty($v['ou'])){
                    $v->children=$v['ou'];
                    foreach ($v['ou'] as $k3=>$v3){
                        $v3->title=$v3->name.$v3->phone;
                    }
                }
            }
        }
        return json($arr);

    }



}
