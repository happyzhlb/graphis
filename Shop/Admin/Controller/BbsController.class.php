<?php
/**
 * BBS控制器
 * @author Abiao
 * @copyright 2015
 */
namespace Admin\Controller;
use Think\Controller;
class BbsController extends BackendController{
    var $_bbs_mod = null;
    function __construct(){
        parent::__construct();
        $this->BbsController();
    }
    function BbsController(){
        $this->_bbs_mod = D('Bbs');
    }
    
    /** BBS列表 */
    function index(){
    	$_bbscategory_mod = D('Bbscategory');
        if(isset($_REQUEST['keywords']) && $_REQUEST['keywords']){
            $where['a.title'] = array('like','%'.I('keywords','','trim').'%');
        }
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
            $cate_id=I('cate_id'); 
            $cate_ids='';
            $_bbscategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_bbscategory_mod->get_category(0,true);
        $this->assign('bbscategory',$list);
        $count = $this->_bbs_mod
                 ->join(' as a LEFT JOIN __BBSCATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $list = $this->_bbs_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __BBSCATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); 
        $this->assign('bbs',$list);
        $this->assign('page',$page->show());
        $this->display('./bbs.index');
    }
    
    /** 添加BBS */
    function add(){
        if(!IS_POST){
            $_bbscategory_mod = D('Bbscategory');
            $list = $_bbscategory_mod->get_category(0,true);
            $this->assign('bbscategory',$list);
            $this->display('./bbs.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'content' => I('content'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            	'clicks' => I('clicks','','intval'),
                'ctime' => gmtime()
            );
            if(!$this->_bbs_mod->create($data)){
                $this->error($this->_bbs_mod->getError());
                return;
            }
            $this->_bbs_mod->add();
            $this->success('BBS添加成功',U('/Admin/Bbs'));
        }
    }
    
    /** 编辑BBS */
    function edit(){
        $bbs_id = I('id','','intval');
        $bbs = $this->_bbs_mod->find($bbs_id);
        if(!$bbs){
            $this->error('BBS不存在');
            return;
        }
        if(!IS_POST){
            $_bbscategory_mod = D('Bbscategory');
            $list = $_bbscategory_mod->get_category(0,true);
            $this->assign('bbscategory',$list);
            $this->assign('bbs',$bbs);
            $this->display('./bbs.edit');
        }else{
            $data = array(
                'bbs_id' => $bbs_id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'content' => I('content'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            	'clicks' => I('clicks','','intval'),
            );
            if(!$this->_bbs_mod->create($data)){
                $this->error($this->_bbs_mod->getError());
                return;
            }
            $this->_bbs_mod->save();
            $this->success('BBS编辑成功',U('/Admin/Bbs'));
        }
    }
    
    /** 异步修改BBS显示状态 */
    function ajax_edit_status(){
        $bbs_id = I('id','','intval');
        $bbs = $this->_bbs_mod->field('bbs_id,if_show')->find($bbs_id);
        if(!$bbs){
            $this->error('BBS不存在');
            return;
        }
        if($bbs['if_show']){
            $bbs['if_show'] = 0;
        }else{
            $bbs['if_show'] = 1;
        }
        if(!$this->_bbs_mod->save($bbs)){
            $this->error('修改BBS显示状态失败');
            return;
        }
        $this->success('修改BBS显示状态成功',U('/Admin/Bbs'));
    }
    
    /** 删除BBS */
    function drop(){
        $bbs_id = I('id','','trim');
        if(!$bbs_id){
            $this->error('传入的ID为空，删除失败');
            return;
        }
        if(strpos($bbs_id,','))
            $bbs_id = explode(',',$bbs_id);
        if(is_array($bbs_id)){
            $where['bbs_id'] = array('in',$bbs_id);
        }else{
            $where['bbs_id'] = $bbs_id;
        }
        if(!$this->_bbs_mod->where($where)->delete()){
            $this->error('BBS删除失败');
            return;
        }
        $this->success('BBS删除成',U('/Admin/Bbs'));
    }
}


?>