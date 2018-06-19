<?php
/**
 * 产品需求控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class RequestController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->RequestController();
    }
    function RequestController(){
        $this->_mod = D('ProductsRequest');
    }
    
    /** 产品需求列表 */
    function index(){
        if($_GET['pro_name']){
            $where['pro_name'] = array('like','%'.trim(I('pro_name')).'%');
        }
        if($_GET['cas_number']){
        	if(I('eq')=='1'){
        		$where['cas_number'] = array('eq',trim(I('cas_number')));
        	}else{
        		$where['cas_number'] = array('exp','like "%'.trim(I('cas_number')).'%"');
        	} 
        }
        if($_GET['contactor']){
            $where['contactor'] = array('like','%'.trim(I('contactor')).'%');
        }
        if($_GET['email']){
            $where['email'] = array('like','%'.trim(I('email')).'%');
        }
        
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,10);
        $list = $this->_mod->where($where)->order('add_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$list);
        $this->assign('page',$page->show());   
        $this->display('./products_request.index');
    }
  
    
    /** 编辑产品需求状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('id,is_check')->find($id);
        if(!$list){
            $this->error('产品需求不存在!');
            return;
        }
        if($list['is_check']){
            $list['is_check'] = 0;
        }else{
            $list['is_check'] = 1;
        }
        if(!$this->_mod->save($list)){
            $this->error('产品需求状态编辑失败');
            return;
        }
        $this->success('产品需求状态编辑成功',U('/Admin/Request'));
    }
    
    /** 删除产品需求 */
    function drop(){
        $item_id = trim(I('id'));
        if(!$item_id){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['id'] = array('in',$item_id);
        }else{
            $where['id'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('产品需求不存在或已经删除');
            return;
        }
        if(!$this->_mod->where($where)->delete()){
            $this->error('产品需求删除失败');
            return;
        }
        $this->success('产品需求删除成功',U('/Admin/Request'));
    }  
}


?>