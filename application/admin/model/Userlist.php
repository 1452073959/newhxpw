<?php
namespace app\admin\model;

use think\Model;

class Userlist extends Model
{

    public function profile()
    {
        return $this->hasOne(Offerlist::class,'id','oid');
    }
        //监理
    public function user()
    {
        return $this->belongsTo(AdminUser::class,'jid','userid');
    }
    //工程经理
    public function gcjl()
    {
        return $this->belongsTo(AdminUser::class,'gcmanager_id','userid');
    }
    //质检
    public function zj()
    {
        return $this->belongsTo(AdminUser::class,'check_id','userid');
    }
    public function picking()
    {
        return $this->hasMany(PickingMaterial::class,'userid','id');
    }

    public function  sale()
    {
        return  $this->hasMany(Material::class,'userid','id');
    }

}