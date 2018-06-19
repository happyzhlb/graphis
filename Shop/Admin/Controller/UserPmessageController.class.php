<?php
/**
 * 私信控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class UserPmessageController extends BackendController{
    var $_mod = null;
    var $_user_mod = null;
    function __construct(){
        parent::__construct();
        $this->UserPmessageController();
    }
    function UserPmessageController(){
        $this->_mod = D('UserPmessage');
        $this->_user_mod =D('user');
    }
    
    function _before_index(){ 
    	$email_is_new=C('email_is_new'); 
    	$this->assign('email_is_new',$email_is_new);
    }
    
    /** 用户私信列表 */
    function index(){ 
        if($_GET['email']){
            $where['b.email'] = array('like','%'.trim(I('email')).'%');
        }
        if($_GET['title']){
            $where['a.title'] = array('exp','like "%'.trim(I('title')).'%"');
        }   
        if($_REQUEST['is_new']!=''){
            $where['a.is_new'] = array('eq',I('is_new'));
        }  
        $count = $this->_mod->where($where)->count();  
        $page = new \Think\Page($count,20);
        $list = $this->_mod->join(' as a left join __USER__ as b on a.to_user=b.user_id')->where($where)->order('mid DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./user_pmessage.index');
    }
    
    /** 新增用户私信 */
    function add(){
        if(!IS_POST){ 
            $this->display('./user_pmessage.form');
        }else{
            $data = array( 
            	'email' => trim(I('email')),
                'title' => trim(I('title')),
                'content' => I('content'),
                'is_new' => I('is_new'),
            	'send_time' => time(),
            );   
            $_validate = array(
        		array('email','require','Email地址不能为空',1),
        		array('title','require','私信标题不能为空',1), 
        		array('content','require','发送内容不能为空',1), 
    		); 
            $this->_mod->setProperty('_validate',$_validate); 
            if(!$this->_mod->create($data)){
                $this->error($this->_mod->getError());
                return;
            }     
            
            $succ_num=0;
	        $err_num=0;
            if(strtolower($data['email'])=='all'){ 
            	$user=$this->_user_mod->field('user_id,email')->select(); 
            	foreach ($user as $val){ 
            		$data['to_user']=$val['user_id'];
	            	$res=$this->_mod->add($data);  
	            	if($res){
	            		$succ_num++;
	            	}else{
	            		$err_num++;
	            		$err_arr[]=$val;
	            	}
            	}
            }else{
	            $email=explode(',',$data['email']); 
	            foreach ($email as $key => $val){
	            	$to_user= $this->_user_mod->where('email="'.trim($val).'"')->getField('user_id'); 
	            	if($to_user){
	            		$data['to_user']=$to_user;
	            		$res=$this->_mod->add($data); 
	            	}
	            	if($res){
	            		$succ_num++;
	            	}else{
	            		$err_num++;
	            		$err_arr[]=$val;
	            	}
	            }  
            }
            $err_arr=join(',',$err_arr); 
            $message='用户私信发送成功'.$succ_num.'条.'.($err_num?'发送失败'.$err_num.'条('.$err_arr.')':'');
            $this->success($message,U('index'),10);
        }
    }
    
    /** 编辑用户私信 */
    function edit(){
        $item_id = I('id','','intval'); 
        $list = $this->_mod->find($item_id);
        if(!$list){
            $this->error('用户私信不存在');
            return;
        }
        if(!IS_POST){     
            $this->assign('list',$list);
            $this->display('./user_pmessage.edit');
        }else{
            $data = array(
                'mid' => $item_id,
                'email' => trim(I('email')),
                'content' => I('content'),
                'is_new' => I('is_new')
            ); 
            $rules=array(
            	array('email','require','Email地址不能为空！'),
            	array('email','email','Email格式不对！'),
            	array('content','require','发送内容不能为空！'),
            );
            if(!$this->_mod->validate($rules)->create($data)){
                $this->error($this->_mod->getError());
                return;
            } 
            $this->_mod->save($data);
            $this->success('用户私信编辑成功',U('index'));
        }
    }
    
    /** 编辑用户私信状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('mid,is_new')->find($id);
        if(!$list){
            $this->error('用户私信不存在!');
            return;
        }
        if($list['is_new']=='1'){
            $list['is_new'] = 0;
        }else{
            $list['is_new'] = 1;
        }
        if(false===$this->_mod->save($list)){
            $this->error('用户私信状态编辑失败');
            return;
        }
        $this->success('用户私信状态编辑成功',U('/Admin/UserPmessage'));
    }
    
    /** 删除用户私信 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['mid'] = array('in',$item_id);
        }else{
            $where['mid'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('用户私信不存在或已经删除');
            return;
        }
        $res=$this->_mod->where($where)->delete();   
        if(FALSE===$res){
            $this->error('用户私信删除失败');
            return;
        }
        $this->success('用户私信删除成功',U('index'));
    }  
}


?>