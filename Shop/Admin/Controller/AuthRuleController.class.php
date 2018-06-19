<?php
/**
 * 权限节点控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class AuthRuleController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->AuthRuleController();
    }
    function AuthRuleController(){
        $this->_mod = D('AuthRule');
    }
    
    /** 权限节点列表 */
    function index(){ 
        if(isset($_GET['keywords']) && $_GET['keywords']){
            $where['title'] = array('like','%'.I('keywords','','trim').'%');
        } 
        $count = $this->_mod 
                 ->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_mod->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('id DESC')->select();  
        $this->assign('auth_rule',$list);
        $this->assign('page',$page->show());
        $this->display('./auth_rule.index');
    }
    
    /** 添加权限节点 */
    function add(){
        if(!IS_POST){ 
            $this->display('./auth_rule.form');
        }else{
            $data = array( 
                'title' => I('title','','trim'),
            	'status' => I('status','','intval'),
            	'name' => I('name','','trim'),
              	'condition' => I('condition','','trim'),
            );
            if(!$this->_mod->create($data)){
                $this->error($this->_mod->getError());
                return;
            }
            if($this->_mod->add()){
            	$this->success('权限节点添加成功',U('/Admin/AuthRule'));
            }else{ 
            	$this->error('权限节点添加失败',U('/Admin/AuthRule'));
            }
            
        }
    }
    
    /** 编辑权限节点 */
    function edit(){
        $id = I('id','','intval');
        $auth_rule = $this->_mod->find($id);
        if(!$auth_rule){
            $this->error('权限节点不存在');
            return;
        }
        if(!IS_POST){ 
            $this->assign('auth_rule',$auth_rule);
            $this->display('./auth_rule.edit');
        }else{
            $data = array(
                'id' => $id,
                'title' => I('title','','trim'),
            	'status' => I('status','','intval'),
            	'name' => I('name','','trim'),
              	'condition' => I('condition','','trim'),
            );
            $crt=$this->_mod->create($data); 
            if(!$crt){
                $this->error($this->_mod->getError());
                return;
            }
            $res=$this->_mod->save($data);
            if(false!==$res)
            	$this->success('权限节点编辑成功',U('/Admin/AuthRule'));
            else 
            	$this->error('权限节点编辑失败.');
        }
    }
    
    /** 异步修改权限节点显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $auth_rule = $this->_mod->field('id,status')->find($id);
        if(!$auth_rule){
            $this->error('权限节点不存在');
            return;
        }
        if($auth_rule['status']){
            $auth_rule['status'] = 0;
        }else{
            $auth_rule['status'] = 1;
        }
        if(!$this->_mod->save($auth_rule)){
            $this->error('修改权限节点显示状态失败');
            return;
        }
        $this->success('修改权限节点显示状态成功',U('/Admin/AuthRule'));
    }
    
    /** 删除权限节点 */
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
            $this->error('权限节点删除失败.');
            return;
        }
        $this->success('权限节点删除成功.',U('/Admin/AuthRule'));
    }
}


?>