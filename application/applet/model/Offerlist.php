<?php


namespace app\applet\model;


use think\Model;

class Offerlist extends Model
{
    protected $pk = 'id';
    protected $table = 'fdz_userlist';

    public function jiezhi()
    {
        return $this->hasMany('Jiezhi','uid','id');
    }
}