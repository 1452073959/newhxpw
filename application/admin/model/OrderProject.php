<?php
namespace app\admin\model;

use think\Model;

class OrderProject extends Model
{
    //订单用料表

    public function offerlist()
    {
        return $this->belongsTo(Offerlist::class,'o_id','id');
    }


}