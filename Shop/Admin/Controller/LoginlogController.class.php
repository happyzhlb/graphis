<?php
/**
 * 登陆日志控制器
 * @author jiwaini00000
 * @copyright 2014
 */
 namespace Admin\Controller;
 use Think\Controller;
class LoginlogController extends BackendController{
    var $_login_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->LoginlogController();
    }
    
    function LoginlogController(){
        $this->_login_log_mod = D('LoginLog');
    }
    
    /** 日志列表 */
    function index(){
        if(isset($_GET['user_name']) && $_GET['user_name']){ //根据用户名查询
            $where['user_name'] = I('user_name','','trim');
        }
        //用户分类查询
        if($_GET['type']){
            $where['type'] = I('type');
        }
        $count = $this->_login_log_mod->where($where)->count();
        $page = new \Think\Page($count,10);
        $logs = $this->_login_log_mod->where($where)->limit($page->firstRow.','.$page->listRows)
                ->order('log_id DESC')->select();
        $this->assign('logs',$logs);
        $this->assign('page',$page->show());
        $this->display('/loginlog.index');
    }
    
    /** 删除日志 */
    function drop(){
        $id = I('id','','trim');
        if(!$id){
            $this->error('id为空，删除失败');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['log_id'] = array('in',$id);
        }else{
            $where['log_id'] = $id;
        }
        if(!$this->_login_log_mod->where($where)->delete()){
            $this->error('日志删除失败');
            return;
        }
        $this->success('日志删除成功',U('/Admin/Loginlog'));
    }
}


?>