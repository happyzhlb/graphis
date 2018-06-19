<?php
/**
 * 邮件发送队列控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class EmailsendlistController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->EmailsendlistController();
    }
    function EmailsendlistController(){
        $this->_mod = D('email_sendlist');
    }
    
    function _before_index(){ 
    	$email_send_status=C('email_send_status'); 
    	$this->assign('email_send_status',$email_send_status);
    }
    
    /** 邮件发送队列列表 */
    function index(){ 
        if($_GET['email']){
            $where['email'] = array('like','%'.trim(I('email')).'%');
        }
        if($_GET['send_content']){
            $where['send_content'] = array('exp','like "%'.trim(I('send_content')).'%"');
        }   
        if($_REQUEST['send_status']!=''){
            $where['send_status'] = array('eq',I('send_status'));
        }  
        $count = $this->_mod->where($where)->count();  
        $page = new \Think\Page($count,20);
        $list = $this->_mod->where($where)->order('send_id DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./emailsendlist.index');
    }
    
    /** 新增邮件发送队列 */
    function add(){
        if(!IS_POST){ 
            $this->display('./emailsendlist.form');
        }else{
            $data = array( 
                'email' => trim(I('email')),
                'send_content' => I('send_content'),
                'send_status' => I('send_status')
            ); $m=M(); 
            $_validate = array(
        		array('email','require','Email地址不能为空',1),
        		array('email','email','Email地址格式不正确',1), 
        		array('send_content','require','发送内容不能为空',1), 
    		); 
            $this->_mod->setProperty('_validate',$_validate);
            if(!$this->_mod->create($data)){
                $this->error($this->_mod->getError());
                return;
            }
             $string = new \Org\Util\String();
             $data['code'] = $string->randString(6);
             $data['password'] = md5(md5($data['password']).$data['code']);
             if(!$this->_mod->add($data)){
                $this->error('邮件添加失败');
                return;
             }
             $this->success('邮件添加成功',U('index'));
        }
    }
    
    /** 编辑邮件发送队列 */
    function edit(){
        $item_id = I('id','','intval'); 
        $list = $this->_mod->find($item_id);
        if(!$list){
            $this->error('邮件发送队列不存在');
            return;
        }
        if(!IS_POST){     
            $this->assign('list',$list);
            $this->display('./emailsendlist.edit');
        }else{
            $data = array(
                'send_id' => $item_id,
                'email' => trim(I('email')),
                'send_content' => I('send_content'),
                'send_status' => I('send_status')
            ); 
            $rules=array(
            	array('email','require','Email地址不能为空！'),
            	array('email','email','Email格式不对！'),
            	array('send_content','require','发送内容不能为空！'),
            );
            if(!$this->_mod->validate($rules)->create($data)){
                $this->error($this->_mod->getError());
                return;
            } 
            $this->_mod->save($data);
            $this->success('邮件编辑成功',U('index'));
        }
    }
    
    /** 编辑邮件发送队列状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('id,send_status')->find($id);
        if(!$list){
            $this->error('邮件发送队列不存在!');
            return;
        }
        if($list['send_status']=='1'){
            $list['send_status'] = 0;
        }else{
            $list['send_status'] = 1;
        }
        if(!$this->_mod->save($list)){
            $this->error('邮件发送队列状态编辑失败');
            return;
        }
        $this->success('邮件发送队列状态编辑成功',U('/Admin/Emailsendlist'));
    }
    
    /** 删除邮件发送队列 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['send_id'] = array('in',$item_id);
        }else{
            $where['send_id'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('邮件发送队列不存在或已经删除');
            return;
        }
        $res=$this->_mod->where($where)->delete();   
        if(FALSE===$res){
            $this->error('邮件发送队列删除失败');
            return;
        }
        $this->success('邮件发送队列删除成功',U('index'));
    }  
}


?>