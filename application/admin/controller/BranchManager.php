<?php


namespace app\admin\controller;


use app\common\controller\Adminbase;
use think\Db;
class BranchManager extends Adminbase
{

    public function index()
    {
        $login = $this->_userinfo;
        $admin=Db::table('fdz_admin')->where(['companyid'=>$login['companyid']])->where('roleid','neq',1)->select();
        foreach ($admin as $k=>$v){
            $admin[$k]['auth']=Db::table('fdz_auth_group')->where('id', $v['roleid'])->value('title');
        }
        $this->assign('admin',$admin);
        return $this->fetch();
    }

    public function log()
    {
        $log= Db::view('zlogs')
            ->view('admin', 'userid,username', 'admin.userid=zlogs.operator')->order('operate_time','desc')
            ->select();
        foreach ($log as $k=>$v){
            $log[$k]['cname']=Db::table('fdz_admin')->where('userid', $v['cname'])->value('username');
        }
        $this->assign('log',$log);
        return $this->fetch();
    }

    public function view()
    {
        $login = $this->_userinfo;
        $user=Db::table('fdz_userlist')->where('frameid',$login['companyid'])-> field(['id','address'])->select();
        foreach ($user as $k=>$v){
            $user[$k]['url']='http://'.$_SERVER['HTTP_HOST'].'/admin/qrcodes/add?id='.$v['id'];
        }
        require'../extend/phpqrcode/phpqrcode.php';
        foreach ($user as $k1=>$v1){
            $name=   parse_url($v1['url'],PHP_URL_QUERY) ;
            $name= md5(substr_replace($name,'',0,3));
            header('Content-Type: image/png');
            $qrcode = new \QRcode();
            $level = 'L';// 容错级别：L、M、Q、H
            $size = 4;
            $data=$v1['url'];
            $name='./uploads/qrcode/'.$name.'.png';
            Db::table('fdz_userlist')->where('id',$v1['id']) ->update(['qrcode' => $name]);
            $qrcode->png($data, $name, $level, $size);
        }

        return redirect('/admin/artificial/gcfx_first');
    }

    //刷新二维码


    //签到信息
    public function sign()
    {
        $userinfo = $this->_userinfo;
        $where=[];
        if(input('name')){
            $where[] = ['customer_name','LIKE','%'.input('name').'%'];
        }
        if(input('address')){
            $where[] = ['address','LIKE','%'.input('address').'%'];
        }
        $data=Db::table('fdz_userlist')->where($where)->where('frameid',$userinfo['companyid'])->select();

        foreach ($data as $k=>$v)
        {
            $data[$k]['user']=Db::table('fdz_register')->where('uid',$v['id'])->select();
        }
        foreach ($data as $k1=>$v1)
        {
            if(count($v1['user'])==0){
                unset($data[$k1]);
            }else{
                $data[$k1]['count']=count($v1['user']);
            }
        }
        $this->assign('data',$data);
        return $this->fetch();
    }
    //
    public function man()
    {

        $data=input();
        if(!empty($_GET['date'])){
            $data=Db::table('fdz_register')->where('uid',$data['id'])->whereBetweenTime('create_time',$data['date'])->select();
        }else{
            $data=Db::table('fdz_register')->where('uid',$data['id'])->select();
        }

        foreach ($data as $k=>$v)
        {
            $data[$k]['type']=Db::name('basis_type_work')->where('id',$v['type'])->value('name');
        }
        $this->assign('data',$data);
        return $this->fetch();
    }
    //
    public function signtwo()
    {
        $where=[];
        if(input('name')){
            $where[] = ['name','LIKE','%'.input('name').'%'];
        }
        $data=Db::table('fdz_register')->where($where)->select();

        foreach ($data as $k=>$v)
        {
            $data[$k]['user']=Db::table('fdz_userlist')->where('id',$v['uid'])->find();
            $data[$k]['type']=Db::name('basis_type_work')->where('id',$v['type'])->value('name');
        }
        //本公司
        $userinfo = $this->_userinfo;
        foreach ($data as $k1=>$v1)
        {
            if($v1['user']['frameid']!=$userinfo['companyid'])
            {
                unset($data[$k1]);
            }
        }
//        dump($data);
        $this->assign('data',$data);
        return $this->fetch();
    }
}
