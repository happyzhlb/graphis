<?php
/**
 * 单页控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class SinglepageController extends BackendController{
    var $_singlepage_mod = null;
    var $_page_type=null;
    function __construct(){
        parent::__construct();
        $this->SinglepageController();
    }
    function SinglepageController(){
        $this->_singlepage_mod = D('Singlepage');
        $this->_page_type=array(
        	'1'=>'介绍页面',
        	'2'=>'内容页面',
        	'3'=>'其它页面'
        );
    }
    
    /** 单页列表 */
    function index(){
        if(isset($_GET['keywords']) && $_GET['keywords']){
            $where['a.title'] = array('like','%'.I('keywords','','trim').'%');
        }
        if(isset($_GET['type_id']) && $_GET['type_id']){
            $where['a.type_id'] = I('type_id','','intval');
        } 
        $this->assign('_category',$this->_page_type); 
        $count = $this->_singlepage_mod
                 ->alias(' as a')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $list = $this->_singlepage_mod->field('a.*')
                ->alias(' as a')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); //var_dump($list); echo M()->getLastsql();
        $this->assign('_list',$list);
        $this->assign('page',$page->show());
        $this->display('./singlepage.index');
    }
    
    /** 添加单页 */
    function add(){
        if(!IS_POST){
            $this->assign('_category',$this->_page_type); 
            $this->display('./singlepage.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'content' => I('content'),
                'type_id' => I('type_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime()
            );
            if(!$this->_singlepage_mod->create($data)){
                $this->error($this->_singlepage_mod->getError());
                return;
            }
            $this->_singlepage_mod->add();
            $this->success('单页添加成功',U('/Admin/Singlepage'));
        }
    }
    
    /** 编辑单页 */
    function edit(){
        $page_id = I('id','','intval');
        $singlepage = $this->_singlepage_mod->find($page_id);
        if(!$singlepage){
            $this->error('单页不存在');
            return;
        }
        if(!IS_POST){ 
            $this->assign('_category',$this->_page_type);
            $this->assign('singlepage',$singlepage);
            $this->display('./singlepage.edit');
        }else{
            $data = array(
                'page_id' => $page_id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'type_id' => I('type_id','','intval'),
                'content' => I('content'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            );
            if(!$this->_singlepage_mod->create($data)){
                $this->error($this->_singlepage_mod->getError());
                return;
            }
            $this->_singlepage_mod->save();
            $this->success('单页编辑成功',U('/Admin/Singlepage'));
        }
    }
    
    /** 异步修改单页显示状态 */
    function ajax_edit_status(){
        $page_id = I('id','','intval');
        $singlepage = $this->_singlepage_mod->field('page_id,if_show')->find($page_id);
        if(!$singlepage){
            $this->error('单页不存在');
            return;
        }
        if($singlepage['if_show']){
            $singlepage['if_show'] = 0;
        }else{
            $singlepage['if_show'] = 1;
        }
        if(!$this->_singlepage_mod->save($singlepage)){
            $this->error('修改单页显示状态失败');
            return;
        }
        $this->success('修改单页显示状态成功',U('/Admin/Singlepage'));
    }
    
    /** 删除单页 */
    function drop(){
        $page_id = I('id','','trim');
        if(!$page_id){
            $this->error('传入的ID为空，删除失败');
            return;
        }
        if(strpos($page_id,','))
            $page_id = explode(',',$page_id);
        if(is_array($page_id)){
            $where['page_id'] = array('in',$page_id);
        }else{
            $where['page_id'] = $page_id;
        }
        if(!$this->_singlepage_mod->where($where)->delete()){
            $this->error('单页删除失败');
            return;
        }
        $this->success('单页删除成',U('/Admin/Singlepage'));
    }
}


?>