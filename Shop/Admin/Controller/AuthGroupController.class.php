<?php
/**
 * 角色控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class AuthGroupController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->AuthGroupController();
    }
    function AuthGroupController(){
        $this->_mod = D('AuthGroup');
    }
    
    /** 角色列表 */
    function index(){ 
        if(isset($_GET['keywords']) && $_GET['keywords']){
            $where['title'] = array('like','%'.I('keywords','','trim').'%');
        } 
        $count = $this->_mod 
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $list = $this->_mod->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('id DESC')->select();  
        foreach ($list as $key => $val){
        	$ls=$this->getAuthRule($val['rules']); 
        	$list[$key]['rules']=$ls;
        }
        $this->assign('auth_group',$list);
        $this->assign('page',$page->show());
        $this->display('./auth_group.index');
    }
    
    /** 添加角色 */
    function add(){
        if(!IS_POST){ 
        	$this->getAuthRule();
            $this->display('./auth_group.form');
        }else{
            $data = array( 
                'title' => I('title','','trim'),
            	'status' => I('status','','intval'),
            	'remark' => I('remark','','trim'),
            	'rules' => I('rules','','trim'),  
            );
            if(!$this->_mod->create($data)){
                $this->error($this->_mod->getError());
                return;
            }
            $this->_mod->add();
            $this->success('角色添加成功',U('/Admin/AuthGroup'));
        }
    }
    
    /** 编辑角色 */
    function edit(){
        $id = I('id','','intval');
        $auth_group = $this->_mod->find($id);
        if(!$auth_group){
            $this->error('角色不存在');
            return;
        }
        if(!IS_POST){ 
            $this->assign('auth_group',$auth_group);
            $this->getAuthRule();
            $this->display('./auth_group.edit');
        }else{
        	$_POST['rules']=join(',', $_POST['rules']);
            $data = array(
                'id' => $id,
                'title' => I('title','','trim'),
            	'status' => I('status','','intval'),
            	'remark' => I('remark','','trim'),
            	'rules' => I('rules','','trim'),  
            );
            $crt=$this->_mod->create($data); 
            if(!$crt){
                $this->error($this->_mod->getError());
                return;
            }
            $res=$this->_mod->save($data);
            if(false!==$res)
            	$this->success('角色编辑成功',U('/Admin/AuthGroup'));
            else 
            	$this->error('角色编辑失败.');
        }
    }
    
    /** 异步修改角色显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $auth_group = $this->_mod->field('id,status')->find($id);
        if(!$auth_group){
            $this->error('角色不存在');
            return;
        }
        if($auth_group['status']){
            $auth_group['status'] = 0;
        }else{
            $auth_group['status'] = 1;
        }
        if(!$this->_mod->save($auth_group)){
            $this->error('修改角色显示状态失败');
            return;
        }
        $this->success('修改角色显示状态成功',U('/Admin/AuthGroup'));
    }
    
    /** 删除角色 */
    function drop(){
        $id = I('id','','trim');
        if(!$id){
            $this->error('传入的ID为空，删除失败.');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['id'] = array('in',$id);
        }else{
            $where['id'] = $id;
        }
        if(!$this->_mod->where($where)->delete()){
            $this->error('角色删除失败.');
            return;
        }
        $this->success('角色删除成功.',U('/Admin/AuthGroup'));
    }
    
    
    //获取权限列表
    protected function getAuthRule($ids=0){
    	if($ids===0){
	    	$list=M('AuthRule')->where('status=1')->order('name')->select();
	    	$this->assign('auth_rule',$list);
    	}else{ 
    		$map['id']=array('in',$ids);
    		$list=M('AuthRule')->where($map)->select();
    	}
    	return $list;
    }
}


?>