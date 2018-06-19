<?php
/**
 * 发现控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
class FoundController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null; 
    function __construct(){
        parent::__construct();
        $this->FoundController();
    }
    
    function FoundController(){
        $this->_mod=D('found');  
        $this->_user_mod=D('user'); 
        $this->assign('top_name','发现');
    }
    
    /** 首页 */
    function index(){ 
       if(session('?user_id')){  
       	$user=$this->_user_mod->field('user_name,nick_name,email,phone')->where('user_id='.session('user_id'))->find();          
       	$this->assign('user',$user);  
       }
       $this->display('./found'); 
    }    
     
}


?>