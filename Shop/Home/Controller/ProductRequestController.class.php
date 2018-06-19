<?php
/**
 * 前台用户需求控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class ProductRequestController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null; 
    function __construct(){
        parent::__construct();
        $this->ProductRequestController();
    }
    
    function ProductRequestController(){
        $this->_mod=D('ProductsRequest');  
        $this->_user_mod=D('user'); 
    }
    
    /** 首页 */
    function index(){ 
       if(session('?user_id')){  
       	$user=$this->_user_mod->field('company_name,contacts,email,phone')->where('user_id='.session('user_id'))->find();          
       	$this->assign('user',$user);  
       }
       $this->display('./Product_request_already_a_member'); 
    }    
    
    function add(){   
    	if(!IS_POST){ 
       		redirect(U('./ProductRequest'));
    	}else{ 
    		$data=array(
    			'pro_name'=>I('pro_name','','trim'),
    			'cas_number'=>I('cas_number','','trim'),
    			'standard'=>I('standard','','trim'),
    			'package'=>I('package','','trim'),
    			'quantity'=>I('quantity','','trim'),
    			'quantity_unit'=>I('quantity_unit','','trim'),
    			'cycle'=>I('cycle','','trim'),
    			'cycle_unit'=>I('cycle_unit','','trim'),
    			'expected_price'=>I('expected_price','','trim'),
    			'description'=>I('description','','trim'), 
    			'ip'=>$_SERVER[REMOTE_ADDR],
    			'add_time'=>time(),
    			'company'=>I('company','','trim'),
    			'contactor'=>I('contactor','','trim'),
    			'phone'=>I('phone','','trim'),
    			'email'=>I('email','','trim')
    		);
    		if(session('?user_id')){
    			$data['user_id'] = session('user_id');
    		}
    		//转为克,报表统计
    		if($data['quantity_unit']=='t'){
    			$unit=1000*1000;
    		}elseif($data['quantity_unit']=='kg'){
    			$unit=1000;
    		}else{
    			$unit=1;
    		}
   			$data['quantity_g']=$data['quantity']*$unit;
    		//转为日周期,报表统计
    		if($data['cycle_unit']=='month'){
    			$unit=30;
    		}elseif($data['cycle_unit']=='week'){
    			$unit=7;
    		}else{
    			$unit=1;
    		}
   			$data['cycle_day']=$data['cycle']*$unit;   			
   			
    		//验证
    		$rules = array( 
    			array('company','require','Company Name is required.',1), 
    			array('contactor','require','Contact Name is required.',1), 
    			array('phone','require','Phone is required.',1),  
    			array('email','email','Email invalidate.',1), 
    			array('pro_name','require','Product Name is required.',1), 
    			array('cas_number','require','Cas Number is required.',1), 
        		array('standard','require','Standard is required.',1),  
        		array('package','require','Package is required.',1), 
        		array('quantity','require','Quantity is required.',1),
        		array('quantity','currency','Quantity must to be digits.',1), 
        		array('quantity_unit','require','Quantity unit is required.',1), 
        		array('cycle','number','cycle must to be digits.',1), 
   			);    
   			if(!$this->_mod->validate($rules)->create($data)){
				$this->error($this->_mod->getError());
   			}else{ 
   				if(false!==$this->_mod->add()){ 
   					sendEmailByTemplate('product_request_submit',$data['email'],$data);
   					$this->success('OK',session('?user_id')?U('./ProductsRequest/index'):U('./ProductRequest'),1);
   				}else{
   					$this->error('Failed');
   				} 
   			}
    	}
    }     
}


?>