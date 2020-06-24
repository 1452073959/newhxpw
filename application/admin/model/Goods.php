<?php
// +----------------------------------------------------------------------
// | 后台产品管理
// +----------------------------------------------------------------------
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Session;
class Goods extends Model
{
    public $page_info;

    //添加产品
    public function goods_add($data)
    {
        if (empty($data)) {
            $this->error = '没有数据！';
            return false;
        }
        if (array_key_exists('ln_id', $data)) {
            $data['ln_id'] = implode(',',$data['ln_id']);
        }
        $id = $this->allowField(true)->insertGetId($data);
        if ($id) {
            return $id;
        }
        $this->error = '入库失败！';
        return false;
    }

    //添加图片
    public function goods_pic($data)
    {
        if (empty($data)) {
            $this->error = '没有数据！';
            return false;
        }
        $id = Db::name('goods_pic')->strict(false)->insertGetId($data);
        if ($id) {
            return $id;
        }
        $this->error = '入库失败！';
        return false;
    }

    //获取产品列表
    public function goods_list($where, $page = 5, $order = 'id desc', $limit = '0',$map=[])
    {
        if ($page) {
            if(!empty($map)){
                $sname = Db::name('shop')->field('id')->where($map)->find();
                $where['sid'] = $sname['id'];
            }
            $list = $this->where($where)->order($order)->paginate($page,false,['query' => request()->param()])->each(function($item, $key){
                $ln    = Db::name('product')->where('id','in',$item['ln_id'])->column('product_name','id'); //获取接口分类
                $la    = Db::name('language')->field('language_name')->where('id',$item['la_id'])->find(); //获取语言分类
                $tr    = Db::name('trade')->field('trade_name')->where('id',$item['tr_id'])->find(); //获取行业分类
                $class = implode($ln, '/');
                $pic   = Db::name('goods_pic')->field('pic')->where('id',$item['p_id'])->find();//获取图片
                $sname = Db::name('shop')->field('name')->where('id',$item['sid'])->find();     //获取商户名称
                $item['pic']   = $pic['pic'];
                $item['ln_id'] = $class;
                $item['la_id'] = $la['language_name'];
                $item['tr_id'] = $tr['trade_name'];
                $item['sname'] = $sname['name'];
            });
            $this->page_info = $list;
            return $list->items();
        } else {
            $list = $this->limit($limit)->group($group)->order($order)->select();
        }
        
    }

    //违规下架
    public function goods_lockup($where,$idArray)
    {
        if (!empty($where)) {
            foreach($idArray as $row){
                if ($this->where('id',$row)->update(['shelf_status'=> 2])) {
                    $true = true;
                }
            }
            if ($true) {
                return true;
            }
            
        }
    }
    // 技术架构列表
    public function language_list($num=10)
    {
        $data = Db::name('language')->paginate($num)->toArray();
        if (!empty($data)) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['time'] = date('Y-m-d H:i:s',$v['time']);
                $data['data'][$k]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_userinfo" lay-event="edit" title="编辑" data-id="'.$v['id'].'"></button><button class="layui-btn layui-btn-xs check_delete" lay-event="del" title="删除" data-id="'.$v['id'].'"></button></div></div>';
            }
            return $data;
        }
        return false;
    }
    // 技术架构新增、修改
    public function language_save($data)
    {
        $data['time'] = time();
        if (!empty($data['id'])) {
            Db::name('language')->update($data);
            return true;
        }else{
            Db::name('language')->insert($data);
            return true;
        }
        return false;
    }
    // 技术架构删除
    public function language_del($id)
    {
        if (!empty($id)) {
            Db::name('language')->delete($id);
            return true;
        }
        return false;
    }
    // 产品分类列表
    public function type_list($num=10)
    {
        $data = Db::name('trade')->paginate($num)->toArray();
        if (!empty($data)) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['time'] = date('Y-m-d H:i:s',$v['time']);
                $data['data'][$k]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_userinfo" lay-event="edit" title="编辑" data-id="'.$v['id'].'"></button><button class="layui-btn layui-btn-xs check_delete" lay-event="del" title="删除" data-id="'.$v['id'].'"></button></div></div>';
            }
            return $data;
        }
        return false;
    }
    // 产品分类新增、修改
    public function type_save($data)
    {
        $data['time'] = time();
        if (!empty($data['id'])) {
            Db::name('trade')->update($data);
            return true;
        }else{
            Db::name('trade')->insert($data);
            return true;
        }
        return false;
    }
    // 产品分类删除
    public function type_del($id)
    {
        if (!empty($id)) {
            Db::name('trade')->delete($id);
            return true;
        }
        return false;
    }
    // 应用类型列表
    public function style_list($num=10)
    {
        $data = Db::name('product')->paginate($num)->toArray();
        if (!empty($data)) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['time'] = date('Y-m-d H:i:s',$v['time']);
                $data['data'][$k]['caozuo'] = '<div class="layui-table-cell"><div class="layui-btn-group"><button class="layui-btn layui-btn-xs check_userinfo" lay-event="edit" title="编辑" data-id="'.$v['id'].'"></button><button class="layui-btn layui-btn-xs check_delete" lay-event="del" title="删除" data-id="'.$v['id'].'"></button></div></div>';
            }
            return $data;
        }
        return false;
    }
    // 应用类型新增、修改
    public function style_save($data)
    {
        $data['time'] = time();
        if (!empty($data['id'])) {
            Db::name('product')->update($data);
            return true;
        }else{
            Db::name('product')->insert($data);
            return true;
        }
        return false;
    }
    // 应用类型删除
    public function style_del($id)
    {
        if (!empty($id)) {
            Db::name('product')->delete($id);
            return true;
        }
        return false;
    }
}
