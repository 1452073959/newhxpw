<?php


namespace app\applet\model;

use think\Model;

class Salesrecord extends Model
{
    protected $pk = 'id';
    protected $table = 'fdz_sales_record';

    public function show()
    {
        return $this->hasMany(Sales::class,'tid','id');
    }
}