<?php
/**
 * 管理员控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class AdminController extends BackendController{
    var $_admin_mod = null;
    function __construct(){
        parent::__construct();
        $this->AdminController();
    }
    function AdminController(){
        $this->_admin_mod = D('Admin');
    }
    /** 管理员列表 */
    function index(){
        if($_GET['user_name']){
            $where['user_name'] = I('user_name','','trim');
        }
        $count = $this->_admin_mod->where($where)->count();
        $page = new \Think\Page($count,10);
        $admins = $this->_admin_mod->where($where)->limit($page->firstRow.','.$page->listRows)
                  ->order('user_id DESC')->select();
        //角色
		$groups=M('auth_group');
        foreach ($admins as $key => $val){ 
        	$arr=$groups->join(' as a join __AUTH_GROUP_ACCESS__ b on a.id=b.group_id')->field('a.id,a.title')->where('a.status=1 and b.uid='.$val['user_id'])->select();
        	$ids=array(); 
        	$g=array();
        	foreach ($arr as $k => $v){ 
        		if(!in_array($v['title'], $g)){
        			$g[]= $v['title']; 
        			$ids[]= $v['id'];
        		} 
            }   
            $group_name=join(',', $g);
            $group_id=join(',', $ids);
        	$admins[$key]['group_name']=$group_name;
        	$admins[$key]['group_id']=$group_id;
        }  
        
        $this->assign('admins',$admins);
        $this->assign('page',$page->show());
        $this->display('/admin.index');
    }
    
    /** 新增管理员 */
    function add(){
        if(!IS_POST){
        	$this->get_auth_group();
            $this->display('/admin.form');
        }else{
            $data = array(
                'user_name' => I('user_name','','trim'),
                'psw' => I('password','','trim'),
                'repsw' => I('repassword','','trim'),
            ); 
            if(!$this->_admin_mod->create($data)){
                $this->error($this->_admin_mod->getError());
                return;
            }else{ //验证成功
                $string = new \Org\Util\String();
                $data['code'] = $string->randString(6);
                $data['psw'] = md5(md5($data['psw']).$data['code']);
                $data['ctime'] = gmtime();
                if(!$uid=$this->_admin_mod->add($data)){
                    $this->error('管理员添加失败');
                    return;
                }
                //添加用户角色
                foreach ($_POST['role'] as $key => $val){
                	$data=array('uid'=>$uid,'group_id'=>$val);
                	M('auth_group_access')->add($data);
                }
                $this->success('管理员添加成功',U('/Admin/Admin'));
                
            }   
        }
    }
    
    /** 编辑管理员 */
    function edit(){
    	$uid=I('id',0,'intval');
    	if(empty($uid)){
    		$this->error('ID错误');
    	}
        if(!IS_POST){
        	$user=$this->_admin_mod->find($uid);
        	$groups=M('auth_group_access')->where('uid='.$uid)->select(); 
        	foreach ($groups as $key => $val){
        		$user['groups'][]=$val['group_id'];
        	} 
        	$this->assign('user',$user);
        	$this->get_auth_group();
            $this->display('/admin.edit');
        }else{
            $data = array(
            	'user_id'=>$uid,
                'user_name' => I('user_name','','trim'), 
            ); 
            if(!$this->_admin_mod->create($data)){
                $this->error($this->_admin_mod->getError());
                return;
            }else{ //验证成功 
                if(false===$this->_admin_mod->save($data)){
                    $this->error('管理员编辑失败');
                    return;
                }
                M('auth_group_access')->where('uid='.$uid)->delete();
                //添加用户角色
                foreach ($_POST['role'] as $key => $val){
                	$data=array('uid'=>$uid,'group_id'=>$val);
                	M('auth_group_access')->add($data);
                }
                $this->success('管理员编辑成功',U('/Admin/Admin'));
                
            }   
        }
    }    
    
    /** 删除管理员 */
    function drop(){
        $user_id = I('id','','trim');
        if(!$user_id){
            $this->error('传入的id为空，删除失败');
            return;
        }
        if(strpos($user_id,','))
            $user_id = explode(',',$user_id);
        if(is_array($user_id)){
            $where['user_id'] = array('in',$user_id);
        }else{
            $where['user_id'] = $user_id;
        }
        $admin_info = $this->_admin_mod->where($where)->select();
        if(empty($admin_info)){
            $this->error('管理员不存在，无法删除');
            return;
        }
        foreach($admin_info as $key => $info){
            if($info['is_system']){
                $this->error($info['user_name'].'为系统保留用户，不允许删除');
                return;
            }
        }
        if(!$this->_admin_mod->where($where)->delete()){
            $this->error('管理员删除失败');
            return;
        }
        $this->success('管理员删除成功',U('/Admin/Admin'));
    }
    
    /** 修改密码 */
    function editpsw(){
        $user_id = I('id','','intval');
        $admin_info = $this->_admin_mod->find($user_id);
        if(empty($admin_info)){
            $this->error('管理员不存在');
            return;
        }
        if(!IS_POST){
            $this->assign('id',$user_id);
            $this->display('/admin.editpsw');    
        }else{
            $data = array(
                'psw' => I('password','','trim'),
                'repsw' => I('repassword', '', 'trim') 
            );
            if(!$this->_admin_mod->create($data)){
                $this->error($this->_admin_mod->getError());
                return;
            }else{
                $string = new \Org\Util\String();
                $data['code'] = $string->randString(6);
                $data['psw'] = md5(md5($data['psw']).$data['code']);
                $data['user_id'] = $user_id;
                if(!$this->_admin_mod->save($data)){
                    $this->error('修改密码失败');
                    return;
                }
                $this->success('修改密码成功',U('/Admin/Admin'));
            }  
        }
    }
    
    /** 修改管理员状态 */
    function editstatus(){
        $user_id = I('id','','intval');
        $admin_info = $this->_admin_mod->field('user_id,status')->find($user_id);
        if(empty($admin_info)){
            $this->error('管理员不存在','',IS_AJAX);
            return;
        }
        if($admin_info['status']){
            $admin_info['status'] = 0;
        }else{
            $admin_info['status'] = 1;
        }
        if(!$this->_admin_mod->save($admin_info)){
            $this->error('状态修改失败','',IS_AJAX);
            return;
        }
        $this->success('状态修改成功',U('/Admin/Admin'),IS_AJAX);
    }
    
    
    protected function get_auth_group(){
    	$group=M('auth_group')->select();
    	$this->assign('group',$group);
    }
}


?>