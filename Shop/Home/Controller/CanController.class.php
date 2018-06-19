<?php
/**
 * 发现控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
class CanController extends FrontendController{
	var $_mod=null; 
	var $_cate_mod=null; 
    function __construct(){
        parent::__construct();
        $this->CanController();
    }
    
    function CanController(){
        $this->_mod=D('Admin/Can');  
        $this->_cate_mod=D('Admin/Cancategory'); 
        $this->assign('top_name','能不能');
    }
    
    /** 首页 */
    function index(){  
       $id=I('id',1,'intval');
       $cate=$this->_cate_mod->get_category($id,1,1);  
       $this->assign('_cate',$cate=current($cate));
       $this->assign('top_name',$cate['cate_name']);
       if($id==2){
       		$this->assign('action','dolist');
       		$this->display('./can.do'); 
       }else{
       		$this->assign('action','eatlist');
       		$this->display('./can.eat'); 
       }
    }    

    /** 能不能吃 */
    function eatlist(){
       $cate_id=I('cate_id',0,'intval');
       $this->assign('top_name','能不能吃');
       if(empty($cate_id)){$this->error('类别ID错误.');}
       $list=$this->_mod->where('cate_id='.$cate_id)->limit('100')->order('can_id desc')->select();
       $this->assign('_list',$list);  
       $this->display('./can.eatlist');  
    }
    
   /** 能不能做 */
    function dolist(){
       $cate_id=I('cate_id',0,'intval');
       $this->assign('top_name','能不能做');
       if(empty($cate_id)){$this->error('类别ID错误.');}
       $list=$this->_mod->where('cate_id='.$cate_id)->limit('100')->order('can_id desc')->select();
       $this->assign('_list',$list);  
       $this->display('./can.dolist');  
    }   
    
    function eat_detail(){
    	$can_id=I('can_id',0,'intval');
    	$this->assign('top_name','能不能吃');
       	if(empty($can_id)){$this->error('ID错误.');}
        $list=$this->_mod->where('can_id='.$can_id)->find();
        $this->assign('list',$list);
    	$this->display('./can.eatdetail');
    }	
    
    function do_detail(){
    	$can_id=I('can_id',0,'intval');
    	$this->assign('top_name','能不能做');
       	if(empty($can_id)){$this->error('ID错误.');}
        $list=$this->_mod->where('can_id='.$can_id)->find();
        $this->assign('list',$list);
    	$this->display('./can.dodetail');
    }

}


?>