<?php


namespace app\applet\model;
use think\Model;

class Userappler extends Model
{
    protected $pk = 'userid';
    protected $table = 'fdz_admin';

    public function jiezhi()
    {
        return $this->hasMany('Jiezhi','jid','userid');
    }
}