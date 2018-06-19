<?php
/**
 * 产品属性类别控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class MygoodsoptiontypeController extends BackendController{
    var $_option_mod = null;
    var $_option_type_mod = null;
    function __construct(){
        parent::__construct();
        $this->MygoodsoptiontypeController();
    }
    function MygoodsoptiontypeController(){
    	$this->_option_mod = D('MygoodsOption');
        $this->_option_type_mod = D('MygoodsOptionType'); 
    }
    
    /** 属性类别列表 */
    function index(){
        if($_GET['keyword'])
            $where['title'] = array('like','%'.trim(I('keyword')).'%'); 
        if($_GET['pid'])
            $where['pid'] = I('pid','','intval'); 
        $count = $this->_option_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_option_type_mod->where($where)->order('type_id DESC')->limit($page->firstRow.','.$page->listRows)->select(); 
        #echo M()->getLastsql();
        $this->assign('list',$list); 
        $this->assign('page',$page->show());
        $this->display('./mygoodsoptiontype.index');
    }
    
    /** 添加属性类别 */
    function add(){
        if(!IS_POST){
            //读取属性类别位 
            $this->display('./mygoodsoptiontype.form');
        }else{
            $data = array( 
                'type_name' => trim(I('type_name')),
                'sort_order' => trim(I('sort_order')),  
            );
            if(!$this->_option_type_mod->create($data)){ 
                $this->error($this->_option_type_mod->getError());
                return;
            }
            $this->_option_type_mod->add();
            $this->success('属性类别添加成功',U('index'));
        }
    }
    
    /** 编辑属性类别 */
    function edit(){
        $type_id = I('id','','intval');
        $list = $this->_option_type_mod->find($type_id);  
        if(!$list){
            $this->error('属性类别不存在或已经被删除');
            return;
        }
        if(!IS_POST){ 
        	$this->assign('list',$list);
        	$options=$this->_option_mod->field('option_id,name')->select(); #dump($options);
        	$this->assign('options',$options);
            $this->display('./mygoodsoptiontype.edit');
        }else{
        	$option_ids=implode($_POST['option_ids'],",");
            $data = array( 
                'type_name' => trim(I('type_name')),
                'sort_order' => trim(I('sort_order')),  
            	'option_ids' => trim($option_ids,',')
            );
            if(!$this->_option_type_mod->create($data)){ 
                $this->error($this->_option_type_mod->getError());
                return;
            } 
            $res=$this->_option_type_mod->where(array('type_id'=>$type_id))->save($data); 
            if($res!==false){
            	$this->success('属性类别编辑成功.',U('index'));
            }else{ 
            	$this->error('属性类别编辑失败.');
            }
        }
    }
    
    /** 删除属性类别 */
    function drop(){
        $type_id = trim(I('id'));
        if(!$type_id){
            $this->error('传入的ID有误，不能删除');
            return;
        }
        if(strpos($type_id,','))
            $type_id = explode(',',$type_id);
        if(is_array($type_id)){
            $where['type_id'] = array('in',$type_id);
        }else{
            $where['type_id'] = $type_id;
        }
        
        $ads = $this->_option_type_mod->where($where)->select();
        if(!$ads){
            $this->error('属性类别不存在或已经被删除');
            return;
        }
        if(!$this->_option_type_mod->where($where)->delete()){
            $this->error('属性类别删除失败');
            return;
        }
        //删除图片
        foreach($ads as $key => $vo){
            @unlink($vo['img']);
        }
        $this->success('属性类别删除成功',U('index'));
    }
    
    /** 编辑属性类别开启状态 */
    function editstatus(){
        $type_id = I('id','','intval');
        $ad = $this->_option_type_mod->field('type_id,status')->find($type_id);
        if(!$ad){
            $this->error('属性类别不存在或被删除');
            return;
        }
        if($ad['status']){
            $ad['status'] = 0;
        }else{
            $ad['status'] = 1;
        }
        if(!$this->_option_mod->save($ad)){
            $this->error('属性类别状态编辑失败');
            return;
        }
        $this->success('属性类别状态编辑成',U('index'));
    }
}


?>