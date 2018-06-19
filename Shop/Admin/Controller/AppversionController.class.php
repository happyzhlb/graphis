<?php
/**
 * App版本控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class AppversionController extends BackendController{
    var $_adplace_mod = null;
    var $_app_mod = null;
    function __construct(){
        parent::__construct();
        $this->AppversionController();
    }
    function AppversionController(){ 
        $this->_app_mod = D('Appversion');
    }
    
    /** App版本列表 */
    function index(){
        $keywords=trim(I('keyword',''));
        if($keywords){
            $where['desc'] = array('like','%'.trim(I('keyword')).'%');  
        }
		$id = I('id','','intval');
        if($_GET['id'])
            $where['id'] = $id;  
        $count = $this->_app_mod->field('*')->where($where)->count();
        $page = new \Think\Page($count,20);
        $page->parameter=array('id'=>$id,'keywords'=>$keywords);
        $list = $this->_app_mod->where($where)
		        ->order('version DESC')
		        ->limit($page->firstRow.','.$page->listRows)
        ->select(); 
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./appversion.index');
    }
    
    /** 添加App版本 */
    function add(){
        if(!IS_POST){  
            $this->display('./appversion.form');
        }else{
            $version=trim(I('version','0','floatval'));
            if(empty($version)){
                $this->error('App版本号不正确，只能为数字格式.');
            }
            $data = array( 
                'version' => trim(I('version')),
                'url' => trim(I('url')),
            	'type'=> I('type'),
            	'desc' => trim(I('desc')),
                'is_check' => I('is_check','','intval'),
                'ctime' => time()
            ); 
            //是否有上传图片
            if($_FILES['img']['size'] > 0){ 
                $upload = new \Think\Upload(array(
                    'maxSize' => '512000', //最大支持上传500K的图片
                    'exts' => 'jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'appversion/'
                ));
                //上传图片
                $file = $upload->upload($_FILES);
                if(!$file){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['img']['savepath'].$file['img']['savename'];
                 
                $data['img'] = $filename;
            }
            if(!$this->_app_mod->create($data)){
                @unlink($filename);
                $this->error($this->_app_mod->getError());
                return;
            }
            $this->_app_mod->add();
            $this->success('App版本添加成功',U('index'));
        }
    }
    
    /** 编辑App版本 */
    function edit(){
        $id = I('id','','intval');
        $list = $this->_app_mod->find($id);
        if(!$list){
            $this->error('App版本不存在或已经被删除');
            return;
        }
        if(!IS_POST){ 
            $this->assign('list',$list); 
            $this->display('./appversion.edit');
        }else{
            $version=trim(I('version','0','floatval'));
            if(empty($version)){
                $this->error('App版本号不正确，只能为数字格式.');
            }
            $data = array(
                'id' => $id, 
                'version' => $version,
                'url' => trim(I('url')),
            	'type'=> I('type'),
            	'desc' => trim(I('desc')),
                'is_check' => I('is_check','','intval')
            ); 
            //是否有上传图片
            if($_FILES['img']['size'] > 0){ 
                $upload = new \Think\Upload(array(
                    'maxSize' => '512000', //最大支持上传500K的图片
                    'exts' => 'jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'appversion/'
                ));
                //上传图片
                $file = $upload->upload($_FILES);
                if(!$file){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['img']['savepath'].$file['img']['savename'];
                 
                $data['img'] = $filename;
            }
            if(!$this->_app_mod->create($data)){
                @unlink($filename);
                $this->error($this->_app_mod->getError());
                return;
            }
            $this->_app_mod->save();
            if($filename){
                @unlink($list['img']);   
            }
            $this->success('App版本编辑成功',U('index'));
        }
    }
    
    /** 删除App版本 */
    function drop(){
        $id = trim(I('id'));  
        if(!$id){
            $this->error('传入的ID有误，不能删除');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['id'] = array('in',$id);
        }else{
            $where['id'] = $id;
        }
        
        $ads = $this->_app_mod->where($where)->select();
        if(!$ads){
            $this->error('App版本不存在或已经被删除');
            return;
        }
        if(!$this->_app_mod->where($where)->delete()){
            $this->error('App版本删除失败');
            return;
        }
        //删除图片
        foreach($ads as $key => $vo){
            @unlink($vo['img']);
        }
        $this->success('App版本删除成功',U('index'));
    }
    
    /** 编辑App版本开启状态 */
    function editstatus(){
        $id = I('id','','intval');
        $ad = $this->_app_mod->field('id,is_check')->find($id);
        if(!$ad){
            $this->error('App版本不存在或被删除');
            return;
        }
        if($ad['is_check']){
            $ad['is_check'] = 0;
        }else{
            $ad['is_check'] = 1;
        }
        if(!$this->_app_mod->save($ad)){
            $this->error('App版本状态编辑失败');
            return;
        }
        $this->success('App版本状态编辑成功.',U('index'));
    }
    
    /** ajax保存排序 */
    function ajaxSortOrder(){
    	$_mod=$this->_app_mod;
    	$id=I('id');  
    	if(empty($id)) $this->error('ID传值错误.');
    	$where=array(
    		'id'=>$id 
    	);
    	$sort_order=I('sort_order',0);
    	$data=array('sort_order'=>$sort_order);
    	$find=$_mod->where($where)->find();
    	if($find){
    		$res=$_mod->where($where)->save($data); 
    	}else{
    		 $this->success('保存失败.'.$find);
    	} 
    	$this->success('保存成功.'.$res);
    }
}


?>