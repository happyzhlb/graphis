<?php
/**
 * 产品规格属性控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class MygoodsoptionController extends BackendController{
    var $_option_mod = null;
    var $_option_type_mod = null;
    function __construct(){
        parent::__construct();
        $this->MygoodsoptionController();
    }
    function MygoodsoptionController(){
    	$this->_option_mod = D('MygoodsOption');
        $this->_option_type_mod = D('MygoodsOptionType'); 
    }
    
    /** 规格属性列表 */
    function index(){
        if($_GET['keyword'])
            $where['title'] = array('like','%'.trim(I('keyword')).'%'); 
        if($_GET['pid'])
            $where['pid'] = I('pid','','intval'); 
        $count = $this->_option_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_option_mod->where($where)->order('option_id DESC')->limit($page->firstRow.','.$page->listRows)->select(); 
        $this->assign('list',$list); 
        $this->assign('page',$page->show());
        $this->display('./mygoodsoption.index');
    }
    
    /** 添加规格属性 */
    function add(){
        if(!IS_POST){
        	$option_type=$this->_option_type_mod->select(); 
        	$this->assign('option_type',$option_type);
            $this->display('./mygoodsoption.form');
        }else{
            $data = array( 
                'name' => trim(I('name')),
                'value' => trim(I('value')), 
            	'type_id'=> I('type_id'),
            	'update_time' => date('Y-m-d H:i:s',time()), 
            );  
            if(!$this->_option_mod->create($data)){ 
                $this->error($this->_option_mod->getError());
                return;
            }
            $this->_option_mod->add();
            $this->success('规格属性添加成功',U('index'));
        }
    }
    
    /** 编辑规格属性 */
    function edit(){
        $option_id = I('id','','intval');
        $list = $this->_option_mod->find($option_id);
        if(!$list){
            $this->error('规格属性不存在或已经被删除');
            return;
        }
        if(!IS_POST){ 
        	$this->assign('list',$list);
        	$option_type=$this->_option_type_mod->select(); 
        	$this->assign('option_type',$option_type);
            $this->display('./mygoodsoption.edit');
        }else{
            $data = array( 
                'name' => trim(I('name')),
                'value' => trim(I('value')), 
            	'type_id'=> I('type_id'),
            	'update_time' => date('Y-m-d H:i:s',time()), 
            );
            if(!$this->_option_mod->create($data)){ 
                $this->error($this->_option_mod->getError());
                return;
            }  
            $res=$this->_option_mod->where(array('option_id'=>$option_id))->save($data); 
            if($res){
            	$this->success('规格属性编辑成功.',U('index'));
            }else{ 
            	$this->error('规格属性编辑失败.');
            }
        }
    }
    
    /** 删除规格属性 */
    function drop(){
        $option_id = trim(I('id'));
        if(!$option_id){
            $this->error('传入的ID有误，不能删除');
            return;
        }
        if(strpos($option_id,','))
            $option_id = explode(',',$option_id);
        if(is_array($option_id)){
            $where['option_id'] = array('in',$option_id);
        }else{
            $where['option_id'] = $option_id;
        }
        
        $ads = $this->_option_mod->where($where)->select();
        if(!$ads){
            $this->error('规格属性不存在或已经被删除');
            return;
        }
        if(!$this->_option_mod->where($where)->delete()){
            $this->error('规格属性删除失败');
            return;
        }
        //删除图片
        foreach($ads as $key => $vo){
            @unlink($vo['img']);
        }
        $this->success('规格属性删除成功',U('index'));
    }
    
    /** 编辑规格属性开启状态 */
    function editstatus(){
        $option_id = I('id','','intval');
        $ad = $this->_option_mod->field('option_id,status')->find($option_id);
        if(!$ad){
            $this->error('规格属性不存在或被删除');
            return;
        }
        if($ad['status']){
            $ad['status'] = 0;
        }else{
            $ad['status'] = 1;
        }
        if(!$this->_option_mod->save($ad)){
            $this->error('规格属性状态编辑失败');
            return;
        }
        $this->success('规格属性状态编辑成',U('index'));
    }
}


?>