<?php
/**
 * 能不能控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class CanController extends BackendController{
    var $_can_mod = null;
    function __construct(){
        parent::__construct();
        $this->CanController();
    }
    function CanController(){
        $this->_can_mod = D('Can');
    }
    
    /** 能不能列表 */
    function index(){
    	$_cancategory_mod = D('Cancategory');
        if(isset($_POST['keywords']) && $_POST['keywords']){
            $where['a.title'] = array('like','%'.I('keywords','','trim').'%');
        } 
        if(isset($_POST['cate_id']) && $_POST['cate_id']){
            $cate_id=I('cate_id'); 
            $cate_ids='';
            $_cancategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        }
        
        $list = $_cancategory_mod->get_category(0,true);
        $this->assign('cancategory',$list);
        $count = $this->_can_mod
                 ->join(' as a LEFT JOIN __CANCATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_can_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __CANCATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); 
        $this->assign('can',$list);
        $this->assign('page',$page->show());
        $this->display('./can.index');
    }
    
    /** 添加能不能 */
    function add(){
        if(!IS_POST){
            $_cancategory_mod = D('Cancategory');
            $list = $_cancategory_mod->get_category(0,true);
            $this->assign('cancategory',$list);
            $this->display('./can.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
            	'cutline' => I('cutline'),
                'content' => I('content'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime(),
                'yq'=>I('yq',0),
            	'yzq'=>I('yzq',0),
            	'brq'=>I('brq',0),
            	'yeq'=>I('yeq',0),
            );
			// 上传图片
           if($_FILES['photo']['size']>0){
				$upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      './can/cate_photo/'; // 设置附件上传目录    // 上传文件    
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$data['photo']=$info['photo']['savepath'].$info['photo']['savename'] ;
				 	if($data['cate_id']==0){
				 		$data['photo_small']=getThumb($info,'70x70');
				 	}else{
				 		$data['photo_small']=getThumb($info,'149x60');
				 	}
				 } 
        	}  
            if(!$this->_can_mod->create($data)){
                $this->error($this->_can_mod->getError());
                return;
            }
            $this->_can_mod->add();
            $this->success('能不能添加成功',U('/Admin/Can'));
        }
    }
    
    /** 编辑能不能 */
    function edit(){
        $can_id = I('id','','intval');
        $can = $this->_can_mod->find($can_id);
        if(!$can){
            $this->error('能不能不存在');
            return;
        }
        if(!IS_POST){
            $_cancategory_mod = D('Cancategory');
            $list = $_cancategory_mod->get_category(0,true);
            $this->assign('cancategory',$list);
            $this->assign('can',$can);
            $this->display('./can.edit');
        }else{
            $data = array(
                'can_id' => $can_id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','intval'),
            	'cutline' => I('cutline'),
                'content' => I('content'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            	'yq'=>I('yq',0),
            	'yzq'=>I('yzq',0),
            	'brq'=>I('brq',0),
            	'yeq'=>I('yeq',0),
            );
			// 上传图片
           if($_FILES['photo']['size']>0){
				$upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      './can/cate_photo/'; // 设置附件上传目录    // 上传文件    
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$data['photo']=$info['photo']['savepath'].$info['photo']['savename'] ;
				 	if($data['cate_id']==0){
				 		$data['photo_small']=getThumb($info,'70x70');
				 	}else{
				 		$data['photo_small']=getThumb($info,'149x60');
				 	}
				 }  
        	}    //149*60
            if(!$this->_can_mod->create($data)){
                $this->error($this->_can_mod->getError());
                return;
            }
            $this->_can_mod->save();
            $this->success('能不能编辑成功',U('/Admin/Can'));
        }
    }
    
    
    /** 异步修改能不能显示状态 */
    function ajax_edit_status(){
        $can_id = I('id','','intval');
        $can = $this->_can_mod->field('can_id,if_show')->find($can_id);
        if(!$can){
            $this->error('Can不存在');
            return;
        }
        if($can['if_show']){
            $can['if_show'] = 0;
        }else{
            $can['if_show'] = 1;
        }
        if(!$this->_can_mod->save($can)){
            $this->error('修改能不能显示状态失败');
            return;
        }
        $this->success('修改能不能显示状态成功',U('/Admin/Can'));
    }
    
    /** 删除能不能 */
    function drop(){
        $can_id = I('id','','trim');
        if(!$can_id){
            $this->error('传入的ID为空，删除失败');
            return;
        }
        if(strpos($can_id,','))
            $can_id = explode(',',$can_id);
        if(is_array($can_id)){
            $where['can_id'] = array('in',$can_id);
        }else{
            $where['can_id'] = $can_id;
        }
        if(!$this->_can_mod->where($where)->delete()){
            $this->error('能不能删除失败');
            return;
        }
        $this->success('能不能删除成',U('/Admin/Can'));
    }
}


?>