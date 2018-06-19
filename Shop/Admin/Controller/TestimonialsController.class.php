<?php
/**
 * 网站评价控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class TestimonialsController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->CommentsController();
    }
    function CommentsController(){
        $this->_mod = D('Testimonials');
    }
    
    /** 网站评价列表 */
    function index(){
        if($_GET['email']){
            $where['email'] = array('like','%'.trim(I('email')).'%');
        }
        if($_GET['content']){
            $where['content'] = array('exp','like "%'.trim(I('content')).'%"');
        }   
        if($_REQUEST['status']!=''){
            $where['status'] = array('eq',intval(I('status')));
        }  
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_mod->where($where)->order('tm_id desc')->limit($page->firstRow.','.$page->listRows)->select();
       # echo M()->getLastsql();
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./testimonials.index');
    }
  
    
    /** 编辑网站评价状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('tm_id,status')->find($id);
        if(!$list){
            $this->error('评价不存在!');
            return;
        }
        if($list['status']=='1'){
            $list['status'] = 0;
        }else{
            $list['status'] = 1;
        } 
        if(!$this->_mod->save($list)){
            $this->error('评价状态编辑失败');
            return;
        } 
        $this->success('网站评价状态编辑成功'.$data['estars'],U('/Admin/Comments'));
    }
   
    
    /** 删除网站评价 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['tm_id'] = array('in',$item_id);
        }else{
            $where['tm_id'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('网站评价不存在或已经删除');
            return;
        }
        if(!$this->_mod->where($where)->delete()){
            $this->error('网站评价删除失败');
            return;
        }
        $this->success('网站评价删除成功',U('index'));
    }  
}


?>