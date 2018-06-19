<?php
/**
 * 用户地址控制器(收货地址/账单地址)
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class UserAddressController extends MemberController{
	var $_mod=null;
	var $_user_mod=null; 
    function __construct(){
        parent::__construct();
        $this->UserAddressController();
    }
    
    function UserAddressController(){
        $this->_mod=D('userAddress');
        $this->_region_mod=D('Admin/Region');  
    }
    
    /** 用户地址首页 */
    function index(){ 
       $this->redirect(U('add','','')); 
       $where['user_id']=$this->visitor->get('user_id'); 
       $list=$this->_mod->where($where)->select();  
       $this->assign('list',$list);
       $this->display('./Address_book');
    }  
        
    function add(){
    	$this->savedlist();
        $user_id = $this->visitor->get('user_id');
    	if(!IS_POST){  
    		$where['user_id']=$user_id;  
       		$_region_mod=D('Admin/region');
       		$regions=$_region_mod->_get_region(1,TRUE);  
       		$this->assign('company_name',$this->visitor->get('company_name'));
       		$this->assign('regions',$regions);
    		$this->display('./Address_book_add_new');
    	}else{
            $data = array(
                'user_id' => $user_id,
                'first_name' => trim(I('first_name')),
                'last_name' => trim(I('last_name')),
                'country' => 1, //默认美国
                'state' => I('state'),
                'city' => trim(I('city')),
                'address' => trim(I('address')),
                'zipcode' => trim(I('zipcode')),
                'telephone' => trim(I('telephone')),
                'mobile' => trim(I('mobile')),
                'email' => $this->visitor->get('email'),
            	'company_name' => trim(I('company_name')),
                'type' => I('type'), 
            );
    		if($_POST['is_default']=='on'){
    			$data['is_default']=1;
    		}else{
    			$data['is_default']=0;
    		}
    		if($data['type']!='both'){
	   			if(!$this->_mod->create($data)){
					$this->error($this->_mod->getError());
	                return;
	   			}else{
	                $address_id=$this->_mod->add();
	                if($data['is_default']){
	                    $where['address_id'] = array('neq' ,$address_id);
	                    $where['user_id'] = $user_id;
	                    $where['type'] = $data['type'];
	                    $this->_mod->where($where)->setField('is_default',0);
	                }
	                $this->success('OK',U('index'));
	   			}
    		}else{
    			//账单地址
    			$data['type']='billing';
    			if(!$rs=$this->_mod->create($data)){
					$this->error($this->_mod->getError());
	                return;
	   			}else{  
	                $address_id=$this->_mod->add();
	                if($data['is_default']){
	                    $where['address_id'] = array('neq' ,$address_id);
	                    $where['user_id'] = $user_id;
	                    $where['type'] = $data['type'];
	                    $this->_mod->where($where)->setField('is_default',0);
	                }
	                //$this->success('OK',U('index'));
	   			}  
    			//收货地址
    			$data['type']='shipping';
    			if(!$this->_mod->create($data)){
					$this->error($this->_mod->getError());
	                return;
	   			}else{
	                $address_id=$this->_mod->add();
	                if($data['is_default']){
	                    $where['address_id'] = array('neq' ,$address_id);
	                    $where['user_id'] = $user_id;
	                    $where['type'] = $data['type'];
	                    $this->_mod->where($where)->setField('is_default',0);
	                }
	                $this->success('OK',U('index'));
	   			}	   			
    		}
    	}
    } 
    
    function edit(){ 
    	$this->savedlist();
    	$address_id=I('address_id','','intval');   
    	if(!$address_id){
    		$this->error('Address_Id Error.');
    	}
        $user_id = $this->visitor->get('user_id');
    	if(!IS_POST){  
    		$where['user_id']=$user_id;
    		$where['address_id']=$address_id;
    		$list=M('userAddress')->where($where)->find();
    		$list['part']=explode('-', $list['telephone']);
    		$this->assign('list',$list);
       		$_region_mod=D('Admin/region');
       		$regions=$_region_mod->_get_region(1,TRUE);   
       		$this->assign('regions',$regions);
    		$this->display('./Address_book_add_new');
    	}else{ 
    		$data = array(
                'address_id' => $address_id,
                'user_id' => $user_id,
                'first_name' => trim(I('first_name')),
                'last_name' => trim(I('last_name')),
                'country' => 1, //默认美国
                'state' => I('state'),
                'city' => trim(I('city')),
                'address' => trim(I('address')),
                'zipcode' => trim(I('zipcode')),
                'telephone' => trim(I('telephone')),
                'mobile' => trim(I('mobile')),
                'email' => $this->visitor->get('email'),
    			'company_name' => trim(I('company_name')),
                'type' => I('type'), 
            );
    		if($_POST['is_default']=='on'){
    			$data['is_default']=1;
    		}else{
    			$data['is_default']=0;
    		}
   			if(!$this->_mod->create($data)){
				$this->error($this->_mod->getError());
                return;
   			}else{
   				$where['user_id']=$user_id;
   				$where['address_id']=$address_id;
                $res=$this->_mod->where($where)->save(); 
              	if(FALSE!==$res){
	                if($data['is_default']){
	                    $where['address_id'] = array('neq' ,$address_id); 
	                    $where['user_id']=$user_id;
	                    $where['type'] = $data['type'];
	                    $this->_mod->where($where)->setField('is_default',0); 
	                }
                	$this->success('OK',U('index'));
              	}else{
   					$this->error('Failed');
   			  	}  
   			}
    	}
    }  
	//已保存地址列表
    function savedlist(){
       $where['user_id']=$this->visitor->get('user_id'); 
       //Shipping
       $where['type']='shipping';
       $shipping=$this->_mod->where($where)->select();  
       $this->assign('shipping',$shipping);
       //Billing
       $where['type']='billing';
       $billing=$this->_mod->where($where)->select();  
       $this->assign('billing',$billing);      
    }
    
    function delete(){ 
    	$address_id=I('address_id','',trim);   
    	if(empty($address_id)){
    		$this->error('Address_Id Error.');
    	}
    	$this->_mod->where('address_id='.$address_id.' and user_id='.$this->visitor->get('user_id'))->delete();
    	$this->redirect(U('UserAddress/index','',''));
    }
    
    /** ajax添加我的地址 */
    function ajax_add(){
        $_region_mod=D('Admin/region');
  		$regions=$_region_mod->_get_region(1,TRUE);   
  		$this->assign('region',$regions);
        $this->display('./myaddress.form');
    }
    
    /** ajax 编辑地址 */
    function ajax_edit(){
        $address_id = I('id','','intval');
        $where['address_id'] = $address_id;
        $where['user_id'] = $this->visitor->get('user_id');
        $address = $this->_mod->where($where)->find($address);
        if(!$address){
            $this->error('The address you edit does not exist.');
            return;
        }
        $_region_mod=D('Admin/region');
  		$regions=$_region_mod->_get_region(1,TRUE);   
  		$this->assign('region',$regions);
        $this->assign('address',$address);
        $this->display('./myaddress.edit');
    }
    
    /** 设为默认地址 */
    function setdefault(){
        $address_id = I('id','','intval');
        $where['address_id'] = $address_id;
        $where['user_id'] = $this->visitor->get('user_id');
        $address = $this->_mod->where($where)->find($address_id);
        if(!$address){
            $this->error('The address you edit does not exist.');
            return;
        }
        if(!$address['is_default']){
            $where['type'] = $address['type'];
            $this->_mod->where($where)->setField('is_default',1);
            $where['address_id'] = array('neq',$address_id);
            $this->_mod->where($where)->setField('is_default',0);   
        }
        $this->success('ok');
    } 
}


?>