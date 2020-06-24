<?php
namespace app\admin\model;

use think\Model;

class PickingMaterial extends Model
{

    protected $table = 'fdz_picking_material';


    public function user()
    {
        return $this->hasOne(AdminUser::class,'userid','adminid');
    }


    public function user1()
    {
        return $this->hasOne(AdminUser::class,'userid','auditid');
    }

    public function client()
    {
        return $this->hasOne(Userlist::class,'id','userid');
    }

    public function userlist()
    {
        return $this->belongsTo(Userlist::class,'userid','id');
    }

    public function jianli()
    {
        return $this->belongsTo(AdminUser::class,'adminid','userid');
    }

}