<?php
/**
 * SEO节点控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class SeoController extends BackendController{
    var $_seo_mod = null;
    function __construct(){
        parent::__construct();
        $this->SeoController();
    }
    function SeoController(){
        $this->_seo_mod = D('Seo');
    }
    
    /** SEO节点列表 */
    function index(){ 
        if(isset($_GET['title']) && $_GET['title']){
            $where['title'] = array('like','%'.I('title','','trim').'%');
        }    
        $count = $this->_seo_mod 
                 ->where($where)->count();
        $page = new \Think\Page($count,15);
        $list = $this->_seo_mod->where($where)->limit($page->firstRow.','.$page->listRows)
                ->order('id DESC')->select(); 
        $this->assign('list',$list);
        $this->assign('page',$page->show());
        $this->display('./seo.index');
    }
    
    /** 添加SEO节点 */
    function add(){
        if(!IS_POST){
            $_acategory_mod = D('Seocate'); 
            $this->display('./seo.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'keywords' => I('keywords','','trim'),
                'description' => I('description','','trim'),
            	'request_uri' => I('request_uri','/','trim'),
            	'action' => I('action','','trim'),
                'controller' => I('controller','','trim'),
                'status' => I('status','','intval'),  
            );
            if(!$this->_seo_mod->create($data)){
                $this->error($this->_seo_mod->getError());
                return;
            }
            $res=$this->_seo_mod->add();
            if(false!==$res){
            	$this->success('SEO节点添加成功',U('/Admin/Seo'));
            }else{
            	$this->error('SEO节点添加失败',U('/Admin/Seo'));
            }
        }
    }
    
    /** 编辑SEO节点 */
    function edit(){
        $id = I('id','','intval');
        $seo = $this->_seo_mod->find($id);
        if(!$seo){
            $this->error('SEO节点不存在');
            return;
        }
        if(!IS_POST){ 
            $this->assign('seo',$seo);
            $this->display('./seo.edit');
        }else{
            $data = array(
                'id' => $id,
                'title' => I('title','','trim'),
            	'keywords' => I('keywords','','trim'),
                'description' => I('description','','trim'),
            	'request_uri' => I('request_uri','/','trim'),
            	'action' => I('action','','trim'),
                'controller' => I('controller','','trim'),
                'status' => I('status','','intval'),              	
            );
            $crt=$this->_seo_mod->create($data); 
            if(!$crt){
                $this->error($this->_seo_mod->getError());
                return;
            }
            $res=$this->_seo_mod->save($data);
            if(false!==$res)
            	$this->success('SEO节点编辑成功',U('/Admin/Seo'));
            else 
            	$this->error('SEO节点编辑失败.');
        }
    }
    
    /** 异步修改SEO节点显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $seo = $this->_seo_mod->field('id,status')->find($id);
        if(!$seo){
            $this->error('SEO节点不存在');
            return;
        }
        if($seo['status']){
            $seo['status'] = 0;
        }else{
            $seo['status'] = 1;
        }
        if(!$res=$this->_seo_mod->save($seo)){
            $this->error('修改SEO节点启用状态失败');
            return;
        } 
        $this->success('修改SEO节点启用状态成功',U('index'));
    }
    
    /** 删除SEO节点 */
    function drop(){
        $id = I('id','','trim');
        if(!$id){
            $this->error('传入的ID为空，删除失败');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['id'] = array('in',$id);
        }else{
            $where['id'] = $id;
        }
        if(!$this->_seo_mod->where($where)->delete()){
            $this->error('SEO节点删除失败');
            return;
        }
        $this->success('SEO节点删除成',U('/Admin/Seo'));
    }
}


?>