<?php
/**
 * 孕助理用户控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class WikiuserController extends BackendController{
    var $_user_mod = null;
    function __construct(){
        parent::__construct();
        $this->WikiuserController();
    }
    function WikiuserController(){
        $this->_user_mod = D('Wikiuser');
    }
    
    /** 会员列表 */
    function index(){
        if($_GET['user_name']){
            $user_name = trim(I('user_name'));
            $user_name = explode(' ',$user_name); 
            $where['user_name'] = array('like','%'.current($user_name).'%');
        }
        if($_GET['nick_name']){
            $where['nick_name'] = array('like','%'.I('nick_name').'%');
        }
        if($_GET['state']!=''){
        	$where['state']=trim(I('state'));
        }
        if($_GET['user_type'] != ''){
            $where['user_type'] = I('user_type','','intval');
        }
        $_region_mod=D('region');
        $regions=$_region_mod->_get_region(1,TRUE); 
        $this->assign('regions',$regions);
        $count = $this->_user_mod->where($where)->count();
        $page = new \Think\Page($count,15);
        $user = $this->_user_mod->where($where)->order('ctime DESC')->limit($page->firstRow.','.$page->listRows)->select(); 
        $this->assign('wikiuser',$user); 
        $this->assign('page',$page->show());
        $this->assign('total',$page->totalRows);
        $this->display('./wikiuser.index');
    }
    
    /** 新增会员 */
    function add(){
        if(!IS_POST){
            $company_size = array(
                'below 50 employees',
                '50-100 employees',
                '100-500 employees',
                'above 500 employees'
            );
            $_region_mod = D('Region'); 
            $regions =$_region_mod->_get_region(1,true);
            $this->assign('regions',$regions);
            $this->assign('company_size',$company_size);
            $this->display('./wikiuser.form');
        }else{
            $data = array(
                'first_name' => trim(I('first_name')),
                'last_name' => trim(I('last_name')),
                'password' => I('password'),
                'repassword' => I('repassword'),
                'email' => trim(I('email')),
                'user_type' => I('user_type','','intval'),
                'company_name' => trim(I('company_name')),
                'company_size' => I('company_size'),
                'state' => I('state'),
                'address' => trim(I('address')),
                'contacts' => trim(I('contacts')),
                'product_request' => I('product_request'),
                'status' => I('status'),
                'last_time' => gmtime(),
                'ctime' => gmtime()
            );
            $phone = I('phone');
            if($phone[0] && $phone[1] && $phone[2]){
                $data['phone'] = $phone[0] .'-'.$phone[1].'-'.$phone[2];
                if($phone[3]){
                    $data['phone'] .= ' Ext'.$phone[3];
                }
            }
            if(!$this->_user_mod->create($data)){
                $this->error($this->_user_mod->getError());
                return;
            }
             $string = new \Org\Util\String();
             $data['code'] = $string->randString(6);
             $data['password'] = md5(md5($data['password']).$data['code']);
             if(!$this->_user_mod->add($data)){
                $this->error('会员添加失败');
                return;
             }
             $this->success('会员添加成功',U('/Admin/Wikiuser'));
        }
    }
    
    /** 编辑会员 */
    function edit(){
        $user_id = I('id','','intval');
        $user = $this->_user_mod->find($user_id); 
        if(!$user){
            $this->error('会员不存在');
            return;
        }
        if(!IS_POST){
            if($user['phone']){
                if(strpos($user['phone'],'Ext')){
                    $ext = explode('Ext',$user['phone']);
                    $user['ext'] = $ext[1];
                    $user['phone'] = explode('-',$ext[0]);
                }else{
                    $user['phone'] = explode('-',$user['phone']);
                }
            }
            $company_size = array(
                'below 50 employees',
                '50-100 employees',
                '100-500 employees',
                'above 500 employees'
            );
            $_region_mod = D('Region'); 
            $regions =$_region_mod->_get_region(1,true);
            $this->assign('regions',$regions);
            $this->assign('company_size',$company_size);
            $this->assign('user',$user);
            $this->display('./wikiuser.edit');
        }else{
            $data = array(
                'user_id' => $user_id,
                'user_name' => trim(I('user_name')),
                'nick_name' => trim(I('nick_name')),
                'user_type' => I('user_type','','intval'),
                'company_name' => trim(I('company_name')),
                'company_size' => I('company_size'),
                'state' => I('state'),
                'address' => trim(I('address')),
            	'city' => trim(I('city')),
                'contacts' => trim(I('contacts')),
                'memo' => I('memo'),
                'status' => I('status'),
                'last_time' => gmtime(),
                'period' => I('period')
            );
            
            if($data['period']==''){
                $this->error('孕期阶段不能为空.');
            }
            
            $phone = I('phone');
            if($phone[0] && $phone[1] && $phone[2]){
                $data['phone'] = $phone[0] .'-'.$phone[1].'-'.$phone[2];
                if($phone[3]){
                    $data['phone'] .= ' Ext'.$phone[3];
                }
            }
            if($_POST['password']){
                $data['password'] = I('password');
                $data['repassword'] = I('repassword');
            }
            if(!$this->_user_mod->create($data)){
                $this->error($this->_user_mod->getError());
                return;
            }
            if($data['password']){
                $string = new \Org\Util\String();
                $data['code'] = $string->randString(6);
                $data['password'] = md5(md5($data['password']).$data['code']);
            }
            $this->_user_mod->save($data); #echo M()->getLastsql(); exit();
            $this->success('会员编辑成功',U('/Admin/Wikiuser'));
        }
    }
    
    /** 编辑会员状态 */
    function editstatus(){
        $user_id = I('id','','intval');
        $user = $this->_user_mod->field('user_id,status')->find($user_id);
        if(!$user){
            $this->error('会员不存在');
            return;
        }
        if($user['status']){
            $user['status'] = 0;
        }else{
            $user['status'] = 1;
        }
        if(!$this->_user_mod->save($user)){
            $this->error('会员状态编辑失败');
            return;
        }
        $this->success('会员状态编辑成功',U('/Admin/Wikiuser'));
    }
    
    /** 删除会员 */
    function drop(){
        $user_id = trim(I('id'));
        if(!$user_id){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($user_id,','))
            $user_id = explode(',',$user_id);
        if(is_array($user_id)){
            $where['user_id'] = array('in',$user_id);
        }else{
            $where['user_id'] = $user_id;
        }
        $user = $this->_user_mod->where($where)->select();
        if(!$user){
            $this->error('会员不存在或已经删除');
            return;
        }
        foreach ($user as $key => $val){
	        $order_num=M('order')->where('user_id='.$val['user_id'])->count();
	        if($order_num>0){
	        	$this->error($val['email'].',会员已经产生订单，不允许删除(可以锁定会员)','',200);
	        }
        }
        M('user_address')->where($where)->delete();
        M('products_request')->where($where)->delete();
        M('score_log')->where($where)->delete(); 
        M('comments')->where($where)->delete();  
        if(!$this->_user_mod->where($where)->delete()){
            $this->error('会员删除失败');
            return;
        }
        $this->success('会员删除成功',U('/Admin/Wikiuser'));
    }
            
    /** 检测用户名是否已经存在 */
    function check_user_name(){
        $user_name = I('user_name','','trim');
        $user_id = isset($_POST['user_id'])? I('user_id','','intval') : 0;
        $result = $this->_user_mod->check_user_name($user_name, $user_id);
        echo $result? 'false': 'true';
    }
    
    /** 检测email是否已经存在 */
    function check_email(){
        $email = I('email','','trim');
        $user_id = isset($_POST['user_id'])? I('user_id','','intval') : 0;
        $result = $this->_user_mod->check_email($email, $user_id);
        echo $result? 'false': 'true';
    }

    /** 促销活动选择会员 */
    function sel_user(){
        if($_REQUEST['first_name']){
            $where['first_name'] = array('like','%'.trim(I('first_name')).'%');
        }
        if($_REQUEST['email']){
            $where['email'] = array('like','%'.trim(I('email')).'%');
        }
        if($_REQUEST['state']!=''){
        	$where['state']=trim(I('state'));
        }
        $_region_mod=D('region');
        $regions=$_region_mod->_get_region(1,TRUE); 
        $this->assign('regions',$regions);
        $count = $this->_user_mod->where($where)->count(); 
        $page = new \Think\Page($count,10);
        $user = $this->_user_mod->where($where)->order('ctime DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('user',$user); 
        $this->assign('page',$page->show());
        $this->assign('total',$page->totalRows);
        $this->display('./wikiuser.sel');
    }
}


?>