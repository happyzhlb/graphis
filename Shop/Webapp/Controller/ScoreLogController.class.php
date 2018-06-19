<?php
/**
 * 用户积分日志控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Controller;
class ScoreLogController extends MemberController{
	var $_mod=null; 
    function __construct(){
        parent::__construct();
        $this->UserAddressController();
    }
    
    function UserAddressController(){
        $this->_mod=D('scoreLog');  
    }
    
    /** 首页 */
    function index(){   
       $this->display('./Reward_points');
    }   
    
    function available_points(){
     	$this->display('./Available_Points');
    }
    
    function points_history(){
       $user_id=$this->visitor->get('user_id');  
       
       $where['user_id']=$user_id;
       if(I('type','','trim')){
       		$where['type']=I('type','','trim');
       } 
       $count = $this->_mod->where($where)->count();  
       $page = new \Think\Page($count,10); 
       $list=$this->_mod->where($where)->limit($page->firstRow.','.$page->listRows)->select();  
       $this->assign('page',$page->show());
       $this->assign('list',$list);
       
    	$this->display('./Points_History');
    }    
    
}


?>