<?php
/**
 * 博客分类控制器
 * @author Abiao
 * @copyright 2014
 */
 namespace Admin\Controller;
 use Think\Controller;
class BlogcateController extends BackendController{
    var $_blogcate_mod = null;
    function __construct(){
        parent::__construct();
        $this->BlogcateController();
    }
    function BlogcateController(){
        $this->_blogcate_mod = D('Blogcate');
    }
    
    /** 博客分类列表 */
    function index(){
        $list = $this->_blogcate_mod->get_category(0,true);
        $this->assign('blogcate',$list);
        $this->display('./blogcate.index');
    }
    
    /** 添加分类 */
    function add(){
        if(!IS_POST){
            $list = $this->_blogcate_mod->get_category(0,true);
            $this->assign('blogcate',$list);
            $this->display('./blogcate.form');
        }else{
            $data = array(
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
            );
            if(!$this->_blogcate_mod->create($data)){
                $this->error($this->_blogcate_mod->getError());
                return;
            }else{
                if(!$this->_blogcate_mod->add($data)){
                    $this->error('分类添加失败');
                    return;
                }
            }
            //清空缓存
            S('blogcate',null);
            $this->success('分类添加成功',U('/Admin/Blogcate'));   
        }
    }
    
    /** 编辑分类 */
    function edit(){
        $id = I('id','','intval');
        $cate = $this->_blogcate_mod->get_category($id);
        if(!$cate){
            $this->error('分类不存在');
            return;
        }
        if(!IS_POST){
            $list = $this->_blogcate_mod->get_category(0,true);
            $this->assign('blogcate',$list);
            $this->assign('cateinfo',$cate);
            $this->display('./blogcate.edit');
        }else{
            $data = array(
                'cate_id' => I('id','','intval'),
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
            );
            
            if($data['parent_id'] == $id){
                $this->error('所属分类 操作失败：不能自己作为自己的父类别.');
            }
            
            if(false === $this->_blogcate_mod->create($data)){
                $this->error($this->_blogcate_mod->getError());
                return;
            }else{
                if(!$this->_blogcate_mod->save($data)){
                    $this->error('分类编辑失败');
                    return;
                }
            }
            //清空缓存
            S('blogcate',null);
            $this->success('分类编辑成功',U('/Admin/Blogcate'));
        }   
    }
    
     /** 删除分类 */
    function drop(){
        $id = I('id','');
        if(!$id){
            $this->error('id为空，删除失败');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['cate_id'] = array('in',$id);
        }else{
            $where['cate_id'] = (int)$id;
        }
        $cate = $this->_blogcate_mod->where($where)->select();
        if(empty($cate)){
            $this->error('分类不存在,删除失败');
            return;
        }
        $this->_blogcate_mod->startTrans();
        foreach($cate as $key => $value){
            //删除所有子分类
            if(!$this->_drop_gcategory($value['cate_id'])){
                $this->_blogcate_mod->rollback();
                $this->error('子分类删除失败');
                return;
            }
        }
        if(!$this->_blogcate_mod->where($where)->delete()){
            $this->_blogcate_mod->rollback();
            $this->error('分类删除失败');
            return;
        }
        $this->_blogcate_mod->commit();
        //清空缓存
        S('blogcate',null);
        $this->success('分类删除成功',U('/Admin/Blogcate'));
    }
    
    /** 删除分类及分类下的所有子分类 */
    private function _drop_gcategory($id){
        $where['parent_id'] = $id;
        $cate_list = $this->_blogcate_mod->where($where)->select();
        if(empty($cate_list)) //没有子分类，直接返回TREU
            return true;
        foreach($cate_list as $key => $list){
            $this->_blogcate_mod->delete($list['cate_id']);
            if(!$this->_drop_gcategory($list['cate_id'])) //字分类删除失败
                return false;
        }
        return true;
    }
}


?>