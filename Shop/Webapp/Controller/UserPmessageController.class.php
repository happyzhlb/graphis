<?php
/**
 * Webapp用户私信控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Controller;
class UserPmessageController extends MemberController{
	var $_mod=null; 
    function __construct(){
        parent::__construct();
        $this->UserPmessageController();
    }
    
    function UserPmessageController(){
        $this->_mod=D('UserPmessage');  
    }
    
    /** 首页 */
    function index(){ 
       $user_id=$this->visitor->get('user_id');  
       $where['to_user']=$user_id; 
       $count = $this->_mod->where($where)->count();  
       $this->assign('count',$count);
       $page = new \Think\Page($count,15); 
       $list=$this->_mod->where($where)->limit($page->firstRow.','.$page->listRows)->order('send_time desc')->select();
       $this->assign('page',$page->show());
       $this->assign('list',$list); 
       $this->display('./Private_Messages');
    }  

    function detail(){
       $mid=I('mid','','intval');
       if(empty($mid)){
       	   $this->error('Message Id Error.');
       }
       $where['to_user']=session('user_id');
       $where['mid']=$mid;
       $list=$this->_mod->where($where)->find();
       if(empty($list)){
       	 	$this->error("Source records don't exist.");
       }
       $this->_mod->where($where)->save(array('is_new'=>0));
       $this->assign('list',$list);
       $user_id = $this->visitor->get('user_id');
       $prev_message=$this->_mod->where('to_user='.$user_id.' and mid>'.$mid)->order('mid asc')->find();
       $next_message=$this->_mod->where('to_user='.$user_id.' and mid<'.$mid)->order('mid desc')->find();
       $this->assign('prev_message',$prev_message);
       $this->assign('next_message',$next_message);
       
       $this->display('./Messages_detail');
    }
    
    function delete(){ 
        if(empty($_REQUEST['mid'])){
    		$this->error("You have not choosed any record.");
    	}
    	//ajax请求，支持用逗号分隔ID
    	if(IS_AJAX){
    		$_POST['mid']=explode(',',$_POST['mid']);
    	}
    	//批量删除
    	if(is_array($_POST['mid'])){
    		foreach ($_POST['mid'] as $key => $val){
    			$this->_mod->where('mid='.(int)$val.' and to_user='.session('user_id'))->delete();
    		}
    		$this->success('All records selected is removed successfully.',U('index'));
    	} 
    	
    	//单个删除
    	$mid=I('mid','','intval');   
    	if(empty($mid)){
    		$this->error('Message Id Error.');
    	}
    	$this->_mod->where('mid='.$mid.' and to_user='.session('user_id'))->delete();
    	$this->success('This record is removed successfully.',U('index'));
    } 
}


?>