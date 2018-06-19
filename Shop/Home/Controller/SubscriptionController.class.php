<?php
/**
 * 订阅控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class SubscriptionController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null; 
    function __construct(){
        parent::__construct();
        $this->SubscriptionController();
    }
    
    function SubscriptionController(){
        $this->_mod=D('emailList');  
        $this->_user_mod = D('User'); 
    }
    
    /** 首页 */
    function index(){  
       $this->redirect(U('./Index')); 
    }    
    
    function add(){  
    	$data=array(
    		'email'=>I('email','','trim'),
    		'ip' => $_SERVER['REMOTE_ADDR'],
    		'ctime'=> time()
    	);

    	$rules=array(
    		array('email','email','Email invalidate',1),
    		array('email','','The e-mail address have been subscibed.',1,'unique'), 
    	); 
    	if(!$this->_mod->validate($rules)->create($data)){ 
    			$this->error($this->_mod->getError(),'/');
    	}else{ 
    		$res=$this->_mod->add($data);
    		if(false!==res){ 
				//发送邮件处理 
	            sendEmailByTemplate('subscription', I('email','','trim'));             
    			$this->success('Subscribe our emails success.','/');
    		}
    	}    
    }     
}


?>