<?php
/**
 * 用户评论日志控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class CommentsController extends MemberController{
	var $_mod=null; 
    function __construct(){
        parent::__construct();
        $this->CommentsController();
    }
    
    function CommentsController(){
        $this->_mod=D('comments');  
    }
    
    /** 首页 */
    function index(){ 
       $user_id=$this->visitor->get('user_id');  
       $where['user_id']=$user_id;
       if(I('comment_stars','','trim')){
       		$where['comment_stars']=I('comment_stars','','intval');
       } 
       $count = $this->_mod->join(' c join __GOODS__ g on c.goods_id=g.goods_id')->where($where)->count();  
       $page = new \Think\Page($count,10); 
       $list=$this->_mod->join(' c join __GOODS__ g on c.goods_id=g.goods_id')->field('c.*,g.goods_name,g.goods_code,g.goods_thumb,g.market_price')->where($where)->limit($page->firstRow.','.$page->listRows)->select();  
 
       $this->assign('page',$page->show());
       $this->assign('list',$list); 
       $this->display('./My_reviews');
    }   
}


?>