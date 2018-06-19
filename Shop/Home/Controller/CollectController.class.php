<?php
/**
 * 用户收藏控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class CollectController extends MemberController{
	var $_mod=null; 
    function __construct(){
        parent::__construct();
        $this->CollectController();
    }
    
    function CollectController(){
        $this->_mod=D('Collect');  
    }
    
    /** 首页 */
    function index(){ 
       $user_id=$this->visitor->get('user_id');  
       $where['user_id']=$user_id; 
       $count = $this->_mod->where($where)->count();  
       $page = new \Think\Page($count,10); 
       $list=$this->_mod->where($where)->limit($page->firstRow.','.$page->listRows)->select();   
       $this->assign('page',$page->show());
       $this->assign('list',$list); 
       $this->display('./My_wishlist');
    }  

    function delete(){ 
        if(empty($_REQUEST['cid'])){
    		$this->error("You have not choosed any record.");
    	}
    	//ajax请求，支持用逗号分隔ID
    	if(IS_AJAX){
    		$_POST['cid']=explode(',',$_POST['cid']);
    	}
    	
    	//批量删除
    	if(is_array($_POST['cid'])){
    		$cid_str=join(',',$_POST['cid']);
    		$this->_mod->where('cid in ('.trim($cid_str,',').') and user_id='.session('user_id'))->delete(); 
    		//$this->redirect(U('index','',''));
    		$this->success('All records selected is removed successfully.'.M()->getLastsql(),U('index'));
    	}
    	
    	//单个删除
    	$id=I('cid','',trim);   
    	if(empty($id)){
    		$this->error('Id Error.');
    	}
    	$this->_mod->where('cid='.(int)$id.' and user_id='.session('user_id'))->delete(); 
    	$this->redirect(U('index','',''));
    }    
    
}


?>