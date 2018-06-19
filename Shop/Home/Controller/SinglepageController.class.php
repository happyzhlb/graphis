<?php
/**
 * 单页控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller; 
use Think\Controller;
class SinglepageController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null;  
    function __construct(){
        parent::__construct();
        $this->SinglepageController(); 
    }
    
    function SinglepageController(){
        $this->_mod=D('singlepage');    
    }
    
    /** 首页 */
    function index(){     
    	$page_id=I('page_id',0,'intval');
    	$list=$this->_mod->where('page_id='.$page_id)->find(); 
    	$list['content'] = htmlspecialchars_decode($list['content']);
    	$this->assign('list',$list);
        $this->display('./singlepage.index');
    }   

    function height(){
    	$this->assign('top_name','宝宝身高预测');
    	if(IS_POST){
    		$this->display('./singlepage.height_res');
    	}else{
    		$this->display('./singlepage.height');
    	}
    	
    }

    function weight(){
    	$this->assign('top_name','胎儿发育评测'); 
        if(IS_POST){
    		$this->display('./singlepage.weight_res');
    	}else{
    		$this->display('./singlepage.weight');
    	}
    }

    function table(){
    	$this->assign('top_name','清宫表'); 
    	
        if(IS_POST){
    		$this->display('./singlepage.table_res');
    	}else{
    		$this->display('./singlepage.table');
    	}
    }       
}


?>