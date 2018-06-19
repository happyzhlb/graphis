<?php
/**
 * 产品规格选项值控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class MygoodsoptionvalueController extends BackendController{
    var $_option_mod = null;
    var $_option_value_mod = null;
    function __construct(){
        parent::__construct();
        $this->MygoodsoptionvalueController();
    }
    function MygoodsoptionvalueController(){
    	$this->_option_mod = D('MygoodsOption');
        $this->_option_value_mod = D('MygoodsOptionValue'); 
    }
    
    /** 规格选项值列表 */
    function index(){
        if($_GET['keyword'])
            $where['value_name'] = array('like','%'.trim(I('value_name')).'%'); 
        if($_GET['option_id'])
            $where['option_id'] = I('option_id','','intval'); 
        $count = $this->_option_value_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_option_value_mod->where($where)->order('option_value_id DESC')->limit($page->firstRow.','.$page->listRows)->select(); 
        $this->assign('list',$list); 
        $this->assign('page',$page->show());
        $this->display('./mygoodsoptionvalue.index');
    }
    
    /** 添加规格选项值 */
    function add(){
        if(!IS_POST){
            //读取规格选项值位 
        	$options=$this->_option_mod->select(); 
        	$this->assign('options',$options);
            $this->display('./mygoodsoptionvalue.form');
        }else{
            $data = array( 
                'option_id' => trim(I('option_id')),
                'value_name' => trim(I('value_name')), 
            	'sort_order'=> I('sort_order'), 
            );
            if(!$this->_option_value_mod->create($data)){ 
                $this->error($this->_option_value_mod->getError());
                return;
            }
            $this->_option_value_mod->add();
            $this->success('规格选项值添加成功',U('index'));
        }
    }
    
    /** 编辑规格选项值 */
    function edit(){
        $id = I('id','','intval');
        $list = $this->_option_value_mod->find($id);
        if(!$list){
            $this->error('规格选项值不存在或已经被删除');
            return;
        }
        if(!IS_POST){ 
        	$options=$this->_option_mod->select(); 
        	$this->assign('options',$options);
        	$this->assign('list',$list);
            $this->display('./mygoodsoptionvalue.edit');
        }else{
            $data = array( 
                'option_id' => trim(I('option_id')),
                'value_name' => trim(I('value_name')), 
            	'sort_order'=> I('sort_order'), 
            );
            if(!$this->_option_value_mod->create($data)){ 
                $this->error($this->_option_value_mod->getError());
                return;
            }  
            $res=$this->_option_value_mod->where(array('option_value_id'=>$id))->save($data); 
            if($res){
            	$this->success('规格选项值编辑成功.',U('index'));
            }else{ 
            	$this->error('规格选项值编辑失败.');
            }
        }
    }
    
    /** 删除规格选项值 */
    function drop(){
        $option_id = trim(I('id'));
        if(!$option_id){
            $this->error('传入的ID有误，不能删除');
            return;
        }
        if(strpos($option_id,','))
            $option_id = explode(',',$option_id);
        if(is_array($option_id)){
            $where['option_value_id'] = array('in',$option_id);
        }else{
            $where['option_value_id'] = $option_id;
        }
        
        $ads = $this->_option_value_mod->where($where)->select();
        if(!$ads){
            $this->error('规格选项值不存在或已经被删除');
            return;
        }
        if(!$this->_option_value_mod->where($where)->delete()){
            $this->error('规格选项值删除失败');
            return;
        }
        //删除图片
        foreach($ads as $key => $vo){
            @unlink($vo['img']);
        }
        $this->success('规格选项值删除成功',U('index'));
    }
    
    /** 编辑规格选项值开启状态 */
    function editstatus(){
        $option_id = I('id','','intval');
        $ad = $this->_option_value_mod->field('option_id,status')->find($option_id);
        if(!$ad){
            $this->error('规格选项值不存在或被删除');
            return;
        }
        if($ad['status']){
            $ad['status'] = 0;
        }else{
            $ad['status'] = 1;
        }
        if(!$this->_option_value_mod->save($ad)){
            $this->error('规格选项值状态编辑失败');
            return;
        }
        $this->success('规格选项值状态编辑成',U('index'));
    }
}


?>