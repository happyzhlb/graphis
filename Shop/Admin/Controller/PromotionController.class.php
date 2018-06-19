<?php
/**
 * 促销活动控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class PromotionController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->PromotionController();
    }
    function PromotionController(){
        $this->_mod = D('Promotion');
    }
    
    /** 促销活动列表 */
    function index(){
        if($_GET['title']){
            $where['title'] = array('like','%'.trim(I('title')).'%');
        }
        if($_GET['pro_type']){
            $where['pro_type'] = array('exp','="'.trim(I('pro_type')).'"');
        }   
        if($_REQUEST['status']!=''){
            $where['status'] = array('eq',intval(I('status')));
        }  
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_mod->where($where)->order('pro_id desc')->limit($page->firstRow.','.$page->listRows)->select();
        #echo M()->getLastsql();
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./promotion.index');
    }
  
    
    /** 编辑优惠活动状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('pro_id,status')->find($id);
        if(!$list){
            $this->error('评价不存在!');
            return;
        }
        if($list['status']=='1'){
            $list['status'] = 0;
        }else{
            $list['status'] = 1;
        } 
        if(!$this->_mod->save($list)){
            $this->error('评价状态编辑失败');
            return;
        } 
        $this->success('优惠活动状态编辑成功'.$data['estars'],U('/Admin/Comments'));
    }  
    
    function add(){  
    	if(!IS_POST){
   		     $this->display('./promotion.add'); 
    	}else{ 
    		$_POST['from_time']=strtotime($_POST['from_time']);
    		$_POST['to_time']=strtotime($_POST['to_time'].' 23:59:59'); 
    		$_POST['ctime']=strtotime($_POST['ctime']);
    		$_POST['products']=trim($_POST['products'],',');
    		$_POST['for_user']=trim($_POST['for_user'],',');
    		$rules = array(     
    			array('title','require','优惠活动标题必须！',1), 
    			array('pro_type',array('integral','discount'),'优惠类型值的范围不正确！',1,'in'), 
    			array('rate','number','优惠比例必须是数值格式',1),
    			array('from_time','number','起始时间格式不正确',1),  
    			array('to_time','number','结束时间格式不正确',1), 
    			array('condition_type',array('price','weight'),'优惠条件类型范围不正确',1,'in'), 
    			array('conditions','currency','优惠条件必须是数值格式',1), 
    			array('products','require','商品必须选择',1), 
    			array('for_user','require','用户必须选择',1), 
    			array('ctime','number','创建时间格式不正确',1), 
    		); 
    		$res=$this->_mod->validate($rules)->create(); 
    		if(!$res){
    			$this->error($this->_mod->getError());
    			return ;
    		} 
    		$res=$this->_mod->add();  
    		if(FALSE!==$res){
    			$this->success('优惠活动增加成功！',U('index'));
    		}else{
    			$this->error('优惠活动增加失败！'.M()->getLastsql());
    		} 
    	}
    }      
    function edit(){
        $id = I('id','','intval'); 
        if(empty($id)) $this->error('ID错误！');
        $where['pro_id']=$id; 
        $list=$this->_mod->where($where)->find();
    	$this->assign('list',$list);
    	if(!IS_POST){
   		     $this->display('./promotion.edit'); 
    	}else{ 
    		$_POST['from_time']=strtotime($_POST['from_time']);
    		$_POST['to_time']=strtotime($_POST['to_time'].' 23:59:59'); 
    		$_POST['ctime']=strtotime($_POST['ctime']);
    		$_POST['products']=trim($_POST['products'],',');
    		$_POST['for_user']=trim($_POST['for_user'],',');
    		$rules = array(     
    			array('title','require','优惠活动标题必须！',1), 
    			array('pro_type',array('integral','discount'),'优惠类型值的范围不正确！',1,'in'), 
    			array('rate','number','优惠比例必须是数值格式',1),
    			array('condition_type',array('price','weight'),'优惠条件类型范围不正确',1,'in'), 
    			array('conditions','currency','优惠条件必须是数值格式',1),
    			array('from_time','number','起始时间格式不正确',1),  
    			array('to_time','number','结束时间格式不正确',1),  
    			array('products','require','商品必须选择',1), 
    			array('for_user','require','用户必须选择',1), 
    			array('ctime','number','创建时间格式不正确',1), 
    		); 
    		$res=$this->_mod->validate($rules)->create(); 
    		if(!$res){
    			$this->error($this->_mod->getError());
    			return ;
    		} 
    		$res=$this->_mod->where($where)->save();  
    		if(FALSE!==$res){
    			$this->success('优惠活动修改成功！',U('index'));
    		}else{
    			$this->error('优惠活动修改失败！');
    		} 
    	}
    }    
        
    /** 删除优惠活动 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['pro_id'] = array('in',$item_id);
        }else{
            $where['pro_id'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('优惠活动不存在或已经删除');
            return;
        }
        if(!$this->_mod->where($where)->delete()){
            $this->error('优惠活动删除失败');
            return;
        }
        $this->success('优惠活动删除成功',U('index'));
    }  
    
    //商品ID转列表
    function get_goods(){
    	$goods_id=I('goods_id');
    	$where['goods_id']=array('in',$goods_id);
    	$list=M('goods')->field('goods_id,goods_name,goods_code')->where($where)->select();
    	foreach ($list as $key => $val){
    		echo '<li class="pro_li" id="g'.$val['goods_id'].'"><span class="proName">'.$val['goods_code'].'</span> <a href="javascript:fn_remove('.$val['goods_id'].')" class="peoDelete" title="Delete">X</a></li> ';
    	} 
    }
    //用户ID转列表
    function get_user(){
    	$user_id=I('user_id');
    	$where['user_id']=array('in',$user_id);
    	$list=M('user')->field('user_id,email')->where($where)->select();
    	foreach ($list as $key => $val){
    		echo '<li class="pro_li" id="u'.$val['user_id'].'"><span class="proName">'.$val['email'].'</span> <a href="javascript:fn_remove_user('.$val['user_id'].')" class="peoDelete" title="Delete">X</a></li> ';
    	} 
    }    
    
}


?>