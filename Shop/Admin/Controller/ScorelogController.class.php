<?php
/**
 * 积分日志控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class ScorelogController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->ScoreLogController();
    }
    function ScoreLogController(){
        $this->_mod = D('ScoreLog');
    }
    
    function _before_index(){
    	$score_log_type=C('score_log_type'); 
    	$this->assign('score_log_type',$score_log_type);
    }
    
    /** 积分日志列表 */
    function index(){ 
        if($_GET['email']){
            $where['email'] = array('like','%'.trim(I('email')).'%');
        }
        if($_GET['desc']){
            $where['desc'] = array('exp','like "%'.trim(I('desc')).'%"');
        }   
        if($_REQUEST['type']!=''){
            $where['type'] = array('eq',I('type'));
        }  
        $count = $this->_mod->where($where)->count();  
        $page = new \Think\Page($count,20);
        $list = $this->_mod->join(' as a left join __USER__ as u on a.user_id=u.user_id')->field('a.*,u.email')->where($where)->order('a.log_id DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$list); 
        $this->assign('page',$page->show());
        $this->display('./scoreLog.index');
    }
  
    
    /** 编辑积分日志状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('id,is_check')->find($id);
        if(!$list){
            $this->error('积分日志不存在!');
            return;
        }
        if($list['is_check']=='1'){
            $list['is_check'] = 0;
        }else{
            $list['is_check'] = 1;
        }
        if(!$this->_mod->save($list)){
            $this->error('积分日志状态编辑失败');
            return;
        }
        $this->success('积分日志状态编辑成功',U('/Admin/Scorelog'));
    }
    
    /** 删除积分日志 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['log_id'] = array('in',$item_id);
        }else{
            $where['log_id'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('积分日志不存在或已经删除');
            return;
        }
        $res=$this->_mod->where($where)->delete();   
        if(FALSE===$res){
            $this->error('积分日志删除失败');
            return;
        }
        $this->success('积分日志删除成功',U('index'));
    }  
}


?>