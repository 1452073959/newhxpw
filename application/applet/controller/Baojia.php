<?php

namespace app\applet\controller;

use app\admin\model\Userlist;
use app\applet\model\Userappler;
use think\Controller;
use think\db\Where;
use think\Request;
use Db;
use app\applet\model\Jiezhi;
use app\admin\model\OrderProject;

class Baojia extends UserBase
{
//提交借支订单
    public function index(Request $request)
    {
        //接收
        $data = $request->post();
        $user = $this->admininfo;
        if (Db::table('fdz_userlist')->where('id', $data['uid'])->value('status') > 6) {
            $this->json('33', '该订单已结算');
        }
        $money = Db::table('fdz_financial')->where('userid', $data['uid'])->select();
        $borrower = Db::table('fdz_financial')->where('userid', $data['uid'])->find();
        $borrower = Db::table('fdz_cost_tmp')->where('f_id', $borrower['fid'])->value('borrower');
        $ys = 0;
        foreach ($money as $k => $v) {
            $ys += $v['money'];
        }
//     已收款
        //可接比率
        //已借
        $jiezhi = Jiezhi::where('uid', $data['uid'])->where('status', '<', 4)->where('type', 1)->select();
        $yj = 0;
        foreach ($jiezhi as $k2 => $v2) {
            $yj += $v2['money'];
        }
        $n['ys'] = $ys;
        $n['yj'] = $yj;
        $n['borrower'] = $borrower;
        //可借金额工程款*%
        $n['borrower'] = round($n['ys'] * $n['borrower'] * 0.01, 2);
        //减去已借
        $n['kj'] = round($n['borrower'] - $n['yj'], 2);

        // type==1监理借支==2代工人借支小程序未更新$data['type']==1||
        if (($data['type'] == 1)) {
            $data = [
                'money' => $data['money'],
                'shroff' => $data['shroff'],
                'so' => $data['so'],
                'uid' => $data['uid'],
                'jid' => $data['jid'],
                'frameid' => $data['frameid'],
                'status' => 1,
                'type' => 1,
                'create_time' => date('Y-m-d H:i:s', time())
            ];
            if ($data['money'] <= $n['borrower']) {
                $data['status']=2;
            }
            $data1 = [
                'fid' => $data['frameid'],
                'shroff' => $data['shroff'],
                'type' => 1,
            ];
            if(empty(Db::table('fdz_worker')->where('shroff',$data['shroff'])->select()))
            {
                Db::table('fdz_worker')->data($data1)->insert();
            }
            $res = Db::table('fdz_jiezhi')->data($data)->insert();
            if ($res) {
                return json(['code' => 1, 'msg' => '成功', 'data' => $data]);
            } else {
                return json(['code' => 2, 'msg' => '失败']);
            }

        }
//        ----------------------------
        else {
            $data = [
                'money' => $data['money'],
                'shroff' => $data['shroff'],
                'so' => $data['so'],
                'uid' => $data['uid'],
                'jid' => $data['jid'],
                'frameid' => $data['frameid'],
                'status' => 1,
                'type' => $data['type'],
                'work' => $data['work'],
                'workername' => $data['workername'],
                'create_time' => date('Y-m-d H:i:s', time())
            ];

            $data1 = [
                'fid' => $data['frameid'],
                'username' => $data['workername'],
                'shroff' => $data['shroff'],
                'type' => 2,
            ];
            $res = Db::table('fdz_jiezhi')->data($data)->insert();
            if(empty(Db::table('fdz_worker')->where('username',$data['workername'])->select()))
            {
                 Db::table('fdz_worker')->data($data1)->insert();
            }else{
                Db::table('fdz_worker')->where('username', $data['workername'])->update(['shroff' => $data['shroff']]);
            }

            if ($res) {
                return json(['code' => 1, 'msg' => '成功', 'data' => $data]);
            } else {
                return json(['code' => 2, 'msg' => '失败']);
            }
        }
    }

    public function audit(Request $request)
    {
        //获取未审核的订单
        $data = $request->get();
        $user = Userappler::with('jiezhi')->all();
        $audit = Jiezhi::with(['offer', 'user'])->where('sid', 0)->where('frameid', $data['freamid'])->order('create_time', 'desc')->select()->toArray();

        if ($this->admininfo['roleid'] != 1 && $this->admininfo['roleid'] != 17) {
            foreach ($audit as $key => $value) {
                $audit[$key]['create_time'] = date('Y-m-d H:i', strtotime($value['create_time']));
                if ($value['offer']['gcmanager_id'] != $this->admininfo['userid']) {
                    unset($audit[$key]);
                }
            }
        }
        foreach ($audit as $k => $v) {
            if ($v['type'] == 1) {
                $audit[$k]['ys'] = 0;
                foreach ($money = Db::table('fdz_financial')->where('userid', $v['uid'])->select() as $k1 => $v1) {
                    $audit[$k]['ys'] += $v1['money'];
                    $audit[$k]['borrower'] = Db::table('fdz_financial')->where('userid', $v['uid'])->find();
                    $audit[$k]['borrower'] = Db::table('fdz_cost_tmp')->where('f_id', $audit[$k]['borrower']['fid'])->value('borrower');
                    $audit[$k]['borrower'] = round($audit[$k]['ys'] * $audit[$k]['borrower'] * 0.01, 2);
                }

                $audit[$k]['yj'] = 0;
                foreach ($jiezhi = Jiezhi::where('uid', $v['uid'])->where('status', '<', 4)->where('type', 1)->select() as $k2 => $v2) {
                    $audit[$k]['yj'] = $audit[$k]['yj'] + $v2['money'];
                }
                $audit[$k]['kj'] = round($audit[$k]['borrower'] - $audit[$k]['yj'], 2);
            } else {
                $audit[$k]['ys'] = 0;
                foreach (Db::table('fdz_order_project')->where('o_id', Userlist::where('id', $v['uid'])->value('oid'))->field(['labor_cost', 'num'])->select() as $k1 => $v1) {
                    $audit[$k]['ys'] += $v1['labor_cost'] * $v1['num'];
                    $audit[$k]['borrower'] = round($audit[$k]['ys'] * 0.5, 2);
                }
                //已借
                $audit[$k]['yj'] = 0;
                foreach (Jiezhi::where('uid', $v['uid'])->where('status', '<', 4)->where('type', 2)->select() as $k2 => $v2) {
                    $audit[$k]['yj'] += $v2['money'];
                }
                //剩余可借
                $audit[$k]['kj'] = round($audit[$k]['borrower'] - $audit[$k]['yj'], 2);
            }
        }
        if ($audit) {
            return json(['code' => 1, 'msg' => '成功', 'data' => $audit]);
        } else {
            return json(['code' => 2, 'msg' => '失败']);
        }

    }


    //审核订单
    public function update(Request $request)
    {
        $data = $request->post();
        $user = Jiezhi::where('id', $data['id'])->find();
        $user->sid = $data['sid'];
        $user->status = $data['status'];
        if ($data['status'] == 4) {
            $user->cause = $data['cause'];
        }
        $user->gcjltime = date('y-m-d H:i:s', time());
        $res = $user->save();
        if ($res) {
            $this->json(1, 'success', $user);
        }

    }

    public function user(Request $request)
    {
        $data = $request->get();
//        $history=Jiezhi::where('jid',$id)->select();
        $user = Userlist::where('id', $data['uid'])->find();
        $this->json(1, 'success', $user);
    }

    public function history(Request $request)
    {
        $data = $request->get();
        $history = Jiezhi::with(['offer', 'cw', 'gcjl'])->where('uid', $data['uid'])->order('create_time', 'desc')->select();
        $this->json(1, 'success', $history);
    }

    public function money(Request $request)
    {
        $data = $request->get();
        $money = Db::table('fdz_financial')->where('userid', $data['uid'])->select();
        $borrower = Db::table('fdz_financial')->where('userid', $data['uid'])->find();
        $borrower = Db::table('fdz_cost_tmp')->where('f_id', $borrower['fid'])->value('borrower');
        $ys = 0;
        foreach ($money as $k => $v) {
            $ys += $v['money'];
        }
//     已收款
        //可接比率
        //已借
        $jiezhi = Jiezhi::where('uid', $data['uid'])->where('status', '<', 4)->where('type', 1)->select();
        $yj = 0;
        foreach ($jiezhi as $k2 => $v2) {
            $yj += $v2['money'];
        }
        $n['ys'] = $ys;
        $n['yj'] = $yj;
        $n['borrower'] = $borrower;
        $n['borrower'] = round($n['ys'] * $n['borrower'] * 0.01, 2);
//        $n['kj'] = $n['borrower'] - $n['yj'];
        $n['kj'] = round($n['borrower'] - $n['yj'], 2);

        $this->json(1, 'success', $n);
    }


    //工人
    public function getworker(Request $request)
    {
        $data = $request->get();
        $worker = Db::table('fdz_admin_worker')->where('jid', $data['id'])->find();
        if (empty($worker)) {
            $data['water'] = 0;
            $data['electricity'] = 0;
            $data['timber'] = 0;
            $data['tile'] = 0;
            $data['grey'] = 0;
            $data['painter'] = 0;
            $data['rests'] = 0;
        } else {
            $data = $worker;
        }
        $this->json(1, 'success', $data);
    }

    public function setworker(Request $request)
    {
        $data = $request->post();
        $fid = Db::table('fdz_admin')->where('userid', $data['id'])->value('companyid');
        $worker = Db::table('fdz_admin_worker')->where('jid', $data['id'])->find();
        $da = [
            'jid' => $data['id'],
            'fid' => $fid,
            'water' => $data['water'],
            'electricity' => $data['electricity'],
            'timber' => $data['timber'],
            'tile' => $data['tile'],
            'grey' => $data['grey'],
            'painter' => $data['painter'],
            'rests' => $data['rests'],
        ];
        if (empty($worker)) {
            $inserworker = Db::table('fdz_admin_worker')->strict(false)->insert($da);
            if ($inserworker) {
                $this->json(1, 'success', '添加成功');
            }
        } else {
            $inserworker = Db::table('fdz_admin_worker')->where('jid', $data['id'])->update($da);
            if ($inserworker) {
                $this->json(1, 'success', '更新成功');
            } else {
                $this->json(1, 'success', '更新失败');
            }
        }

    }


    //修改密码
    public function password()
    {
        $user = $this->admininfo;
        $user['password'] = Db::table('fdz_admin')->where('userid', $user['userid'])->value('password');
        $password = md5(input('password'));
        if (input('password') == '') {
            $this->json(1, '密码不能为空');
        }
        if ($user['password'] == $password) {
            $this->json(1, '修改失败,与原密码相同');
        }
        $res = Db::table('fdz_admin')->where('userid', $user['userid'])->update(['password' => $password]);
        if ($res) {
            $this->json(0, '修改成功,下次请用新密码登陆');
        } else {
            $this->json(2, '修改失败');
        }
    }

    //代工人借支工种
    public function work()
    {
        $work = Userlist::where('id', input('uid'))->value('oid');
        $work = Db::table('fdz_order_project')->where('o_id', $work)->field(['labor_cost', 'num', 'type_of_work'])->select();
        $jiezhi = Jiezhi::where('uid', input('uid'))->where('status', '<', 4)->where('type', 2)->select();
        $arr4 = [];//拼装数组
        foreach ($jiezhi as $k => $v) {
            if (!isset($arr4[$v['work']])) {
                $arr4[$v['work']] = 0;
            }
            $arr4[$v['work']] += $v['money'];
        }

        $arr = [];//拼装数组
        $total = 0;
        foreach ($work as $k => $v) {
            if (!isset($arr[$v['type_of_work']])) {
                $arr[$v['type_of_work']] = 0;
            }
            $arr[$v['type_of_work']] += $v['labor_cost'] * $v['num'] * 0.5;
            $total += $v['labor_cost'] * $v['num'];
        }
//        dump($arr);
//        dump($arr4);
//        $arr=array_keys($arr);
//        $arr4=array_keys($arr4);
//        $arr = array_intersect($arr, $arr4);
//        dump($arr);
//        $result = array();
        foreach ($arr as $k => $v) {
            foreach ($arr4 as $key =>$val){
                if($k==$key){
                    $arr[$k] = $arr[$k] - $arr4[$key];
                }
            }

        }
        $arr1 = array_keys($arr);
        $arr2 = array_values($arr);
        $arr3 = array_chunk($arr1, 1);
        foreach ($arr3 as $k1 => $v1) {
            $arr3[$k1]['money'] = $arr2[$k1];
            $arr3[$k1]['gz'] = $arr1;
        }

        $this->json(2, '获取成功', $arr3);
    }

    //
    public function workmoney()
    {
        $workmoney = Userlist::where('id', input('uid'))->value('oid');
        $workmoney = Db::table('fdz_order_project')->where('o_id', $workmoney)->field(['labor_cost', 'num', 'type_of_work'])->select();
        //工程人工成本
        $ys = 0;
        foreach ($workmoney as $k => $v) {
            $ys += $v['labor_cost'] * $v['num'];
        }
        //已借
        $jiezhi = Jiezhi::where('uid', input('uid'))->where('status', '<', 4)->where('type', 2)->select();
        $yj = 0;
        foreach ($jiezhi as $k2 => $v2) {
            $yj += $v2['money'];
        }
        $n['yj'] = $yj;
        //人工成本
        $n['ys'] = $ys;
        //剩余可借
        $n['kj'] = $n['ys'] * 0.5 - $yj;
        //能借
        $n['borrower'] = $n['ys'] * 0.5;
        $this->json(1, 'success', $n);
    }

    public function worker()
    {
        $user = $this->admininfo;
        $data=input();
        if($data['type']==2){
            $worker = Db::table('fdz_worker')->where('fid',$user['companyid'])->where('type',2)->select();
            $this->json(1, 'success', $worker);
        }else{
            $worker = Db::table('fdz_worker')->where('fid',$user['companyid'])->where('type',1)->select();
            foreach ($worker as $k=>$v)
            {
                $worker[$k]['username']=  $worker[$k]['shroff'];
            }
            $this->json(1, 'success', $worker);
        }

    }
}
