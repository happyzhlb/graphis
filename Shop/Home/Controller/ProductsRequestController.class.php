<?php
/**
 * 用户需求控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class ProductsRequestController extends MemberController{
	var $_mod=null; 
    function __construct(){
        parent::__construct();
        $this->ProductsRequestController();
    }
    
    function ProductsRequestController(){
        $this->_mod=D('ProductsRequest'); 
    }
    
    /** 首页 */
    function index(){ 
       $user_id=$this->visitor->get('user_id');  
       $where['user_id']=$user_id; 
       $count = $this->_mod->where($where)->count();  
       $page = new \Think\Page($count,15); 
       $list=$this->_mod->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();  
       $this->assign('page',$page->show());
       $this->assign('list',$list);  
       $this->display('./Product_request');
    }   
    
    function detail(){
       $id=I('id','','trim');
       if(empty($id)){
       	   $this->error('Id Error.');
       }
       $where['user_id']=session('user_id');
       $where['id']=$id;
       $list=$this->_mod->where($where)->find();
       if(empty($list)){
       	 	$this->error("This record don't exist.");
       } 
       $this->assign('list',$list);
       $this->display('./Product_request_preview');
    }
    
    function edit(){ 
    	$id=I('id','',trim);   
       	if(empty($id)){
    		$this->error('Id Error.');
       	}	
        $where['user_id']=session('user_id');
        $where['id']=$id;
    	if(!IS_POST){ 
       		$list=$this->_mod->where($where)->find();
       		if(empty($list)){
       	 		$this->error("This record don't exist.");
       		} 
       		$this->assign('list',$list); 
       		$this->display('./Product_request_edit'); 
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
    		);
    		
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
   				if(false!==$this->_mod->where($where)->save()){ 
   					$this->success('OK',U('detail?id='.$id),1);
   				}else{
   					$this->error('Failed');
   				} 
   			}
    	}
    }     
    
    function delete(){ 
    	if(empty($_REQUEST['id'])){
    		$this->error("You have not choosed any record.");
    	}
        //ajax请求，支持用逗号分隔ID
    	if(IS_AJAX){
    		$_POST['id']=explode(',',$_POST['id']);
    	}
    	//批量删除
    	if(is_array($_POST['id'])){
    		foreach ($_POST['id'] as $key => $val){
    			$this->_mod->where('id='.(int)$val.' and user_id='.session('user_id'))->delete();
    		}
    		$this->success('All records selected is removed successfully.',U('index'));
    	}
    	//单个删除
    	$id=I('id','','intval');  
    	$this->_mod->where('id='.(int)$id.' and user_id='.session('user_id'))->delete();
    	$this->success('This record is removed successfully.',U('index'));
    } 
}


?>