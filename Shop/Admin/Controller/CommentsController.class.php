<?php
/**
 * 评论控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class CommentsController extends BackendController{
    var $_mod = null;
    function __construct(){
        parent::__construct();
        $this->CommentsController();
    }
    function CommentsController(){
        $this->_mod = D('Comments');
    }
    
    /** 评论列表 */
    function index(){
        if($_GET['user_name']){
            $where['c.user_name'] = array('like','%'.trim(I('user_name')).'%');
        }
        if($_GET['content']){
            $where['c.content'] = array('exp','like "%'.trim(I('content')).'%"');
        }   
        if($_REQUEST['status']!=''){
            $where['c.status'] = array('eq',intval(I('status')));
        }  
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_mod->join(' as c left join __USER__ as u on c.user_id=u.user_id')->field('c.*,u.email')->where($where)->order('c.comment_id DESC')->limit($page->firstRow.','.$page->listRows)->select();
         
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./comments.index');
    }
  
    
    /** 编辑评论状态 */
    function editstatus(){
        $id = I('id','','intval');
        $list = $this->_mod->field('comment_id,goods_id,status')->find($id);
        if(!$list){
            $this->error('评论不存在!');
            return;
        }
        if($list['status']=='1'){
            $list['status'] = 0;
        }else{
            $list['status'] = 1;
        }
        $this->_mod->startTrans();
        if(!$this->_mod->save($list)){
            $this->error('评论状态编辑失败');
            return;
        }else{  
          	$estars=$this->avg_stars($list['goods_id']);
          	if($estars === false){
        		$this->_mod->rollback();
        		$this->error('评论状态编辑失败,星级读取失败!');
           		return;
        	}
        	$ecount=$this->count_comment($list['goods_id']);
        	if($ecount === false){
        		$this->_mod->rollback();
        		$this->error('评论状态编辑失败,评论读取失败!');
           		return;
        	}        	
        	$data=array(
        			'estars' => $estars?$estars:5,
        			'ecount' => $ecount,
        	);  
        	$res=M('goods')->where('goods_id='.$list['goods_id'])->setField($data);    
        		if($res!==false){
        			$this->_mod->commit();
        		}else{
        			$this->_mod->rollback();
        			$this->error('评论状态编辑失败,事务处理失败！');
           			return;
        		} 
        }
        $this->success('评论状态编辑成功'.$data['estars'],U('/Admin/Comments'));
    }
    
    //计算综合评论星级
    function avg_stars($goods_id){
    	if(empty($goods_id)){
    		return false;
    	}
    	$stars=$this->_mod->where('goods_id='.$goods_id.' and status=1')->avg('comment_stars');
    	return round($stars);
    }
    //计算评论数量
    function count_comment($goods_id){
    	if(empty($goods_id)){
    		return false;
    	}
    	$res=$this->_mod->where('goods_id='.$goods_id.' and status=1')->count();
    	return $res;
    }    
    
    /** 删除评论 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        //查询商品ID
        if(is_array($item_id)){
            $where['comment_id'] = array('in',$item_id);
        }else{
            $where['comment_id'] = $item_id;
        }
        $goods_list_id = $this->_mod->distinct(true)->field('goods_id')->where($where)->select();
        D('')->startTrans();
        $list = $this->_mod->where($where)->select();
        if(!$list){
            D('')->rollback();
            $this->error('评论不存在或已经删除');
            return;
        }
        if(!$this->_mod->where($where)->delete()){
            D('')->rollback();
            $this->error('评论删除失败');
            return;
        }
        foreach($goods_list_id as $gk => $goods){
       	    $estars = $this->avg_stars($goods['goods_id']);
          	if($estars === false){
        		D('')->rollback();
        		$this->error('评论删除失败,更新星级数据失败!');
           		return;
        	}
        	$ecount=$this->count_comment($goods['goods_id']);
        	if($ecount === false){
        		D('')->rollback();
        		$this->error('评论删除失败,更新评论数据失败!');
           		return;
        	}        	
        	$data=array(
        			'goods_id' => $goods['goods_id'],
                    'estars' => $estars?$estars:5,
        			'ecount' => $ecount,
        	);
            if(!M('Goods')->save($data)){
                D('')->rollback();
                $this->error('评论删除失败，更新评论统计数据失败');
            }
            
        }
        D('')->commit();
        $this->success('评论删除成功',U('/Admin/Comments'));
    }  
}


?>