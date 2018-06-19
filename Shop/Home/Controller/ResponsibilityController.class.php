<?php
/**
 * 新品控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller; 
use Think\Controller;
class ResponsibilityController extends FrontendController{
	var $_mod=null;  
    function __construct(){
        parent::__construct();
        $this->ResponsibilityController();
    }
    
    function ResponsibilityController(){
        //$this->_goods_mod = D('Article'); 
    }
     
    function index(){   
      $this->display('./responsibility');
    }  
} 
?>