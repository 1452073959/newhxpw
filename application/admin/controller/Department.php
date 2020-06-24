<?php

// +----------------------------------------------------------------------
// | 组织架构管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use think\Paginator;
use think\Request;

use PHPExcel;
use PHPExcel_IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Department extends Adminbase
{
    public $show_page = 15;

    //部门列表
    public function index()
    {
        $admininfo = $this->_userinfo;
        if ($admininfo['roleid'] == 1) {
            $frame = Db::name('frame')->where(['levelid' => 3])->order('id', 'asc')->select();
            $this->assign('frame', $frame);
        }
        $this->assign([
            'admininfo' => $admininfo
        ]);
        return $this->fetch();
    }

    //获取部门列表 - json格式 供treetable使用
    public function treetype()
    {
        $admininfo = $this->_userinfo;
        $where = [];//部门筛选
        if ($admininfo['roleid'] == 1 && empty(input('fid'))) {
            TreeResult(0, 'ok', [], 0);
            die;
        }
        if ($admininfo['roleid'] == 1) {
            $admininfo['companyid'] = input('fid') ? input('fid') : 0;
        }
        $where['fid'] = $admininfo['companyid'];
        $frame = Db::name('frame')->where(['id' => $admininfo['companyid']])->order('id', 'asc')->select();
        $frame[0]['id'] = 0;
        $frame[0]['pid'] = -1;
        $res = Db::name('department')->where($where)->select();
        $res = array_merge($frame, $res);
        if ($res) {
            TreeResult(0, 'ok', $res, count($res));
        } else {
            TreeResult(1, '获取失败');
        }
    }

    public function add_job()
    {
        if (input('name') && input('fid')) {

        } else {
            $this->error('参数错误');
        }
    }

    //添加部门
    public function adds()
    {
        $admininfo = $this->_userinfo;
        if ($admininfo['roleid'] == 1) {
            $this->error('最高管理员不能添加/修改部门');
        }
        $input = input();
        if ($input) {
            $data['name'] = $input['name'];
            $data['remark'] = $input['remark'];
            // $data['sort'] = 0;//暂时没用
            if (isset($input['id'])) {
                //编辑
                $id = $input['id'];
                $res = Db::name('department')->where(['id' => $id])->update($data);
            } else {
                //新增
                $data['fid'] = $admininfo['companyid'];
                $data['addtime'] = time();
                $data['pid'] = $input['pid'];
                if ($data['pid'] == '0') {
                    $data['info_pid'] = '0-';
                } else {
                    $info_pid = Db::name('department')->where(['fid' => $admininfo['companyid'], 'id' => $data['pid']])->value('info_pid');
                    $data['info_pid'] = $info_pid . $data['pid'] . '-';
                    //拼接所有pid
                }

                $res = Db::name('department')->insert($data);
            }
            if ($res) {
                $this->success('添加/修改成功');
            } else {
                $this->error('添加/修改失败');
            }
        } else {
            $this->error('参数错误');
        }
    }

    //删除部门
    public function del()
    {
        if (input('id')) {
            $haschild = Db::name('department')->where(['pid' => input('id')])->count();
            if ($haschild) {
                $this->error('存在子部门，禁止删除');
            }
            $hasperson = Db::name('personnel')->where(['did' => input('id')])->count();
            if ($hasperson) {
                $this->error('部门下存在员工，禁止删除');
            }
            $res = Db::name('department')->where(['id' => input('id')])->delete();
            if ($res) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('参数错误');
        }
    }


    //------------------------下面人员管理
    //人员列表
    public function personnel_index()
    {
        $admininfo = $this->_userinfo;
        if ($admininfo['roleid'] == 1) {
            $frame = Db::name('frame')->where(['levelid' => 3])->order('id', 'asc')->select();
            $this->assign('frame', $frame);
        }
        $where = [];
        $where['fid'] = $admininfo['companyid'];
        // $datas = Db::name('personnel')->where($where)->select();
        $this->assign([
            // 'datas'=>$datas,
            'admininfo' => $admininfo
        ]);
        return $this->fetch();
    }

    //添加人员
    public function add_personnel(Request $request)
    {
        if (input('did')) {
            $admininfo = $this->_userinfo;
            if ($admininfo['roleid'] == 1) {
                $this->error('最高管理员不能添加/修改部门');
            }
            //预防以后最高管理员要添加用户
            // if($admininfo['roleid'] == 1){
            //     $admininfo['companyid'] = input('fid');
            // }
            $data = input();
            $data['addtime'] = time();
            $data['fid'] = $admininfo['companyid'];
            if($_FILES['card']['error'] !=4) {
                $file = $request->file('card');
                $info = $file->move('./uploads/card/images');
                if ($info) {
                    // 成功上传后 获取上传信息
                    $images = $info->getSaveName();
                    $images = '\uploads\card\images/' . $images;
                    $images = str_replace('\\', '/', $images);
                    $data['card'] = $images;
                } else {
                    // 上传失败获取错误信息
                $this->error($file->getError());
//                    $data['card'] = "";
                }
            }else{
                $data['card'] = "";
            }


            $res = Db::name('personnel')->insert($data);
            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->error('参数错误');
        }
    }

    //编辑人员
    public function edit_personnel()
    {
        if (input('id')) {
            $admininfo = $this->_userinfo;
            if ($admininfo['roleid'] == 1) {
                $this->error('最高管理员不能添加/修改部门');
            }
            $data = input();
            if($_FILES['card']['error'] !=4) {
                $file = request()->file('card');
                $info = $file->move('./uploads/card/images');
                if ($info) {
                    // 成功上传后 获取上传信息
                    $images = $info->getSaveName();
                    $images = '\uploads\card\images/' . $images;
                    $images = str_replace('\\', '/', $images);
                    $data['card'] = $images;
                } else {
                    // 上传失败获取错误信息
                $this->error($file->getError());
                }
            }

            $res = Db::name('personnel')->where(['id' => input('id')])->update($data);
            if ($data['status'] == 2) {
                if (Db::name('admin')->where(['pid' => input('id'), 'status' => 1])->count()) {
                    Db::name('admin')->where(['pid' => input('id'), 'status' => 1])->update(['status' => 0]);
                }
            }
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $this->error('参数错误');
        }
    }

    //获取人员列表  - json格式 供treetable使用
    public function get_personnel()
    {
        if (input('did') || input('did') == '0') {
            $admininfo = $this->_userinfo;
            $where = [];
            if ($admininfo['roleid'] == 1 && empty(input('fid'))) {
                TreeResult(0, 'ok', [], 0);
                die;
            }
            if ($admininfo['roleid'] == 1) {
                $admininfo['companyid'] = input('fid') ? input('fid') : 0;
            }
            $where[] = ['fid', '=', $admininfo['companyid']];
            if (input('did') != '0') {
                $where[] = ['info_pid', 'like', '%-' . input('did') . '-%'];
            }
            //
            $department = array_column(Db::name('department')->where($where)->whereOr(['id' => input('did')])->select(), null, 'id');
            // var_dump($department);die;
            $department_ids = array_column($department, 'id');
            // $department_ids[] = input('did');//所有部门id
            $datas = Db::name('personnel')->where(['did' => $department_ids])->page(input('page'))->limit(input('limit'))->order('status','asc')->select();
            $count = Db::name('personnel')->where(['did' => $department_ids])->count();
            $job = [1 => '设计师', 2 => '报价师', 3 => '商务经理', 4 => '工程监理', 5 => '其他',6=>'仓管',7=>'质检',
                8=>'工程经理',9=>'财务',10=>'出纳',11=>'人事',12=>'总经理',
                13=>'总设计师',14=>'业务员',15=>'施工图',
                16=>'设计总监',17=>'设计组长',18=>'效果图',19=>'市场经理',20=>'业务员',21=>'网销',22=>'行政',23=>'前台',24=>'客服'
            ];
            foreach ($datas as $k => $v) {
                $datas[$k]['sex'] = $v['sex'] == 1 ? '男' : '女';
                $datas[$k]['job'] = $job[$v['job']];
                $datas[$k]['status'] = $v['status'] == 1 ? '在职' : '离职';
                $datas[$k]['department'] = $department[$v['did']]['name'];
                $datas[$k]['addtime'] = date('Y-m-d', $v['addtime']);
            }
            TreeResult(0, 'ok', $datas, $count);
            // $this->success('success','',$datas);
        } else {
            TreeResult(0, 'ok', [], 0);
        }
    }

    public function bind()
    {
        $admininfo = $this->_userinfo;
        $personnel = Db::name('personnel')->where(['status' => 1, 'fid' => $admininfo['companyid']])->select();
        $this->assign("roles", model('admin/AuthGroup')->getGroups());
        $this->assign([
            'quoter_name' => $personnel,
        ]);
        return $this->fetch();
    }

    public function bindadd(Request $request)
    {

        $admininfo = $this->_userinfo;
        $data = $request->post();
        $staff = Db::name('personnel')->where(['status' => 1, 'fid' => $admininfo['companyid'], 'id' => $data['quoter_id']])->find();
//        dump($staff);die;
        $n = [
            'companyid' => $staff['fid'],
            'username' => $data['username'],
            'password' => md5($data['password']),
            'roleid' => $data['roleid'],
            'name' => $staff['name'],
            'phone' => $staff['phone'],
            'email' => $staff['email'],
            'status' => 0,
            'addid' => $admininfo['userid']
        ];
        $res = Db::table('fdz_admin')->data($n)->insert();
        if ($res) {
            $this->success("添加管理员成功！超级管理员审核成功后可以登陆", url('admin/manager/index'));
        } else {

            $this->error('添加失败！');
        }
    }
}