<?php
/**
 * 网站评论控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class TestimonialsController extends FrontendController{
    var $_testimonials_mod = null;
    function __construct(){
        parent::__construct();
        $this->TestimonialsController();
    }
    function TestimonialsController(){
        $this->_testimonials_mod = D('Admin/Testimonials');
    }
    /** 读取评论 */
    function index(){
        $where['status'] = 1; 
        $count = $this->_testimonials_mod->where($where)->count();
        $page = new \Think\Page($count,10);
        $tms = $this->_testimonials_mod->where($where)->order('ctime DESC')
               ->limit($page->firstRow.','.$page->listRows)
               ->select();
        $this->assign('tms',$tms);
        $this->assign('page',$page->show());
        //浏览记录
        $history = viewed_items();
        $this->assign('history',$history);
        $this->assign('count',$count);
        $this->assign('listRows',$page->listRows);
        $this->display('./testimonials.index');
    }
    
    /** 添加评论 */
    function add(){
        if(!IS_POST){
            //浏览记录
            $history = viewed_items();
            $this->assign('history',$history);
            C('TOKEN_ON',true);
            $this->display('./testimonials.form');
        }else{
            $data = array(
                'title' => trim(I('title')),
                'user_id' => 0,
                'name' => trim(I('name')),
                'email' => trim(I('email')),
                'location' => trim(I('location')),
                'content' => trim(I('content')),
                'ctime' => gmtime(),
                'status' => 0,
                '__hash__' => I('__hash__')
            );
            if(!$this->_testimonials_mod->create($data)){
                $this->error($this->_testimonials_mod->getError());
                return;
            }
            if($this->visitor->has_login)
                $data['user_id'] = $this->visitor->get('user_id');
            $this->_testimonials_mod->add();
            $this->success('Testimonial submitted and awaiting moderation.',U('/Testimonials'));
        }
    }
} 


?>