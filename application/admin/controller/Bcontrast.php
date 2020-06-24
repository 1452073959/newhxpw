<?php
/**
 * Created by PhpStorm.
 * User: 覃宇彬
 * Date: 2019/6/17
 * Time: 10:40
 */

namespace app\admin\controller;


use app\common\controller\Adminbase;
use think\Db;
class Bcontrast extends Adminbase
{
    public function index()
    {
         $userinfo = $this->_userinfo;
         if($userinfo['roleid'] != 1){
           $da['frameid'] = $userinfo['companyid'];
         }else{
           $da = '';
         }


         // echo $frameid;
         // dump($userinfo);
        if($this->request->isPost()){
            $search = input('search'); 
          if($search){
            $res = Db::name('userlist')->where($da)->where('customer_name','like',"%".$search."%")->order('id','desc')->select(); 
            $this->assign('data',$res); 
           }else{
             $this->error('请输入搜索内容', url("Bcontrast/index"));
           } 

        }else{
         
            $res = Db::name('userlist')->where($da)->order('id','desc')->select();        
             // dump($res);
             // exit;
            $this->assign('data',$res);

           
        }

         return $this->fetch();
    }
	//自选报表对比列表
	public function singlelist(){
         $id = $this->request->param('id/d');
         // echo $id;
         if ($id) {
          //单个客户列表
           $res = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address,u.manager_name as manager_name')->join('userlist u','o.customerid = u.id')->where('customerid',$id)->select();
         }

         $newres = [];
         foreach ($res as $key => $value) {
           $cons = json_decode($value['content'],true); 

           $newres[$key]['content'] = $cons;
           $newres[$key]['status'] = $value['status'];
           $newres[$key]['customer_name'] = $value['customer_name'];
           $gongzhong = '';
           if($cons) {
               foreach (json_decode($value['content'],true) as $kk => $vv) {
                    $gongzhong .= $vv['type_of_work'].',';
               }
               $newres[$key]['gongzhong'] = $gongzhong;
           }
           
       

         }


         // dump($newres);
         // exit;

      
         $this->assign('data',$res);
		
		return $this->fetch();
	}
    
        public function singleone(){
            $id = input('id');
            //获取对比的两条ID
            $id = explode(',',$id); 

            $newres1 = [];
            $res1 = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address,u.manager_name as manager_name')->join('userlist u','o.customerid = u.id')->where('o.id',$id[0])->find();
            $cons1 = json_decode($res1['content'],true);  
            // dump($cons1);

            $res2 = Db::name('offerlist')->alias('o')->field('o.*,u.customer_name as customer_name,u.quoter_name as quoter_name,u.designer_name as designer_name,u.address as address,u.manager_name as manager_name')->join('userlist u','o.customerid = u.id')->where('o.id',$id[1])->find();
            $cons2 = json_decode($res2['content'],true);  
            // dump($cons2);
            $newlist = array();
            if (!$cons1 && !$cons2) {
              $newlist = 0;
            }else if ($cons1 && !$cons2) {
                 foreach ($cons1 as $key => $value) {
                   $newlist[$key]['item_number'] = $value['item_number'];
                   $newlist[$key]['project'] = $value['project'];
                   $newlist[$key]['type_of_work'] = $value['type_of_work'];
                   $newlist[$key]['kongjian'] = $value['kongjian'];
                   $newlist[$key]['company'] = $value['company'];
                   $newlist[$key]['gclA'] = $value['gcl'];
                   $newlist[$key]['quotaA'] = $value['quota'];
                   $newlist[$key]['quotaallA'] = $value['quotaall'];
                   $newlist[$key]['craft_showA'] = $value['craft_show'];
                   $newlist[$key]['craft_showallA'] = $value['craft_showall'];
                   $newlist[$key]['gclB'] = 0;
                   $newlist[$key]['quotaB'] = 0;
                   $newlist[$key]['quotaallB'] = 0;
                   $newlist[$key]['craft_showB'] = 0;
                   $newlist[$key]['craft_showallB'] = 0;
                   $newlist[$key]['gclC'] = $newlist[$key]['gclB']-$newlist[$key]['gclA'];
                   $newlist[$key]['quotaC'] = $newlist[$key]['quotaB']-$newlist[$key]['quotaA'];
                   $newlist[$key]['quotaallC'] = $newlist[$key]['quotaallB']-$newlist[$key]['quotaallA'];
                   $newlist[$key]['craft_showC'] = $newlist[$key]['craft_showB']-$newlist[$key]['craft_showA'];
                   $newlist[$key]['craft_showallC'] = $newlist[$key]['craft_showallB']-$newlist[$key]['craft_showallA'];
                 }
              
            }else if ($cons2 && !$cons1) {
                 foreach ($cons2 as $key => $value) {
                   $newlist[$key]['item_number'] = $value['item_number'];
                   $newlist[$key]['project'] = $value['project'];
                   $newlist[$key]['type_of_work'] = $value['type_of_work'];
                   $newlist[$key]['kongjian'] = $value['kongjian'];
                   $newlist[$key]['company'] = $value['company'];
                   $newlist[$key]['gclA'] = 0;
                   $newlist[$key]['quotaA'] = 0;
                   $newlist[$key]['quotaallA'] = 0;
                   $newlist[$key]['craft_showA'] = 0;
                   $newlist[$key]['craft_showallA'] = 0;
                   $newlist[$key]['gclB'] = $value['gcl'];
                   $newlist[$key]['quotaB'] = $value['quota'];
                   $newlist[$key]['quotaallB'] = $value['quotaall'];
                   $newlist[$key]['craft_showB'] = $value['craft_show'];
                   $newlist[$key]['craft_showallB'] = $value['craft_showall'];
                   $newlist[$key]['gclC'] = $newlist[$key]['gclB']-$newlist[$key]['gclA'];
                   $newlist[$key]['quotaC'] = $newlist[$key]['quotaB']-$newlist[$key]['quotaA'];
                   $newlist[$key]['quotaallC'] = $newlist[$key]['quotaallB']-$newlist[$key]['quotaallA'];
                   $newlist[$key]['craft_showC'] = $newlist[$key]['craft_showB']-$newlist[$key]['craft_showA'];
                   $newlist[$key]['craft_showallC'] = $newlist[$key]['craft_showallB']-$newlist[$key]['craft_showallA'];
                 }
            }else{
             //相同的合并 
              foreach ($cons1 as $key => $value) {
                  foreach ($cons2 as $kkk => $vvv) {
                    if (($value['item_number'] == $vvv['item_number'])&&($value['type_of_work'] == $vvv['type_of_work'])&&($value['kongjian'] == $vvv['kongjian'])) {
                      $newlist[$key]['item_number'] = $value['item_number'];
                      $newlist[$key]['project'] = $value['project'];
                      $newlist[$key]['type_of_work'] = $value['type_of_work'];
                      $newlist[$key]['kongjian'] = $value['kongjian'];
                      $newlist[$key]['company'] = $value['company'];
                      $newlist[$key]['gclA'] = $value['gcl'];
                      $newlist[$key]['quotaA'] = $value['quota'];
                      $newlist[$key]['quotaallA'] = $value['quotaall'];
                      $newlist[$key]['craft_showA'] = $value['craft_show'];
                      $newlist[$key]['craft_showallA'] = $value['craft_showall'];
                      $newlist[$key]['gclB'] = $vvv['gcl'];
                      $newlist[$key]['quotaB'] = $vvv['quota'];
                      $newlist[$key]['quotaallB'] = $vvv['quotaall'];
                      $newlist[$key]['craft_showB'] = $vvv['craft_show'];
                      $newlist[$key]['craft_showallB'] = $vvv['craft_showall'];
                      $newlist[$key]['gclC'] = $newlist[$key]['gclB']-$newlist[$key]['gclA'];
                      $newlist[$key]['quotaC'] = $newlist[$key]['quotaB']-$newlist[$key]['quotaA'];
                      $newlist[$key]['quotaallC'] = $newlist[$key]['quotaallB']-$newlist[$key]['quotaallA'];
                      $newlist[$key]['craft_showC'] = $newlist[$key]['craft_showB']-$newlist[$key]['craft_showA'];
                      $newlist[$key]['craft_showallC'] = $newlist[$key]['craft_showallB']-$newlist[$key]['craft_showallA'];
                      unset($cons1[$key]);
                      unset($cons2[$kkk]);                     
                    }
                    
                  }
              }
             // 剩下的报表1 
                  $newlistA = [];            
                  // dump($cons1);
                  foreach ($cons1 as $key => $value) {
                   $newlistA[$key]['item_number'] = $value['item_number'];
                   $newlistA[$key]['project'] = $value['project'];
                   $newlistA[$key]['type_of_work'] = $value['type_of_work'];
                   $newlistA[$key]['kongjian'] = $value['kongjian'];
                   $newlistA[$key]['company'] = $value['company'];
                   $newlistA[$key]['gclA'] = $value['gcl'];
                   $newlistA[$key]['quotaA'] = $value['quota'];
                   $newlistA[$key]['quotaallA'] = $value['quotaall'];
                   $newlistA[$key]['craft_showA'] = $value['craft_show'];
                   $newlistA[$key]['craft_showallA'] = $value['craft_showall'];
                   $newlistA[$key]['gclB'] = 0;
                   $newlistA[$key]['quotaB'] = 0;
                   $newlistA[$key]['quotaallB'] = 0;
                   $newlistA[$key]['craft_showB'] = 0;
                   $newlistA[$key]['craft_showallB'] = 0;
                   $newlistA[$key]['gclC'] = $newlistA[$key]['gclB']-$newlistA[$key]['gclA'];
                   $newlistA[$key]['quotaC'] = $newlistA[$key]['quotaB']-$newlistA[$key]['quotaA'];
                   $newlistA[$key]['quotaallC'] = $newlistA[$key]['quotaallB']-$newlistA[$key]['quotaallA'];
                   $newlistA[$key]['craft_showC'] = $newlistA[$key]['craft_showB']-$newlistA[$key]['craft_showA'];
                   $newlistA[$key]['craft_showallC'] = $newlistA[$key]['craft_showallB']-$newlistA[$key]['craft_showallA'];
                 }
            // 剩下的报表2
                  // dump($cons2);
                 $newlistB = [];  
                  foreach ($cons2 as $key => $value) {
                   $newlistB[$key]['item_number'] = $value['item_number'];
                   $newlistB[$key]['project'] = $value['project'];
                   $newlistB[$key]['type_of_work'] = $value['type_of_work'];
                   $newlistB[$key]['kongjian'] = $value['kongjian'];
                   $newlistB[$key]['company'] = $value['company'];
                   $newlistB[$key]['gclA'] = 0;
                   $newlistB[$key]['quotaA'] = 0;
                   $newlistB[$key]['quotaallA'] = 0;
                   $newlistB[$key]['craft_showA'] = 0;
                   $newlistB[$key]['craft_showallA'] = 0;
                   $newlistB[$key]['gclB'] = $value['gcl'];
                   $newlistB[$key]['quotaB'] = $value['quota'];
                   $newlistB[$key]['quotaallB'] = $value['quotaall'];
                   $newlistB[$key]['craft_showB'] = $value['craft_show'];
                   $newlistB[$key]['craft_showallB'] = $value['craft_showall'];
                   $newlistB[$key]['gclC'] = $newlistB[$key]['gclB']-$newlistB[$key]['gclA'];
                   $newlistB[$key]['quotaC'] = $newlistB[$key]['quotaB']-$newlistB[$key]['quotaA'];
                   $newlistB[$key]['quotaallC'] = $newlistB[$key]['quotaallB']-$newlistB[$key]['quotaallA'];
                   $newlistB[$key]['craft_showC'] = $newlistB[$key]['craft_showB']-$newlistB[$key]['craft_showA'];
                   $newlistB[$key]['craft_showallC'] = $newlistB[$key]['craft_showallB']-$newlistB[$key]['craft_showallA'];
                 }
               //组合并  
               $newlist = array_merge($newlist,$newlistA,$newlistB);
            }




           
            // dump($newlist);
            // echo 666;
           $this->assign('newlist',$newlist);
           return $this->fetch();

        }



}