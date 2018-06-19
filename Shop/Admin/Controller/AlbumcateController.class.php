<?php
/**
 * 专辑分类控制器
 * @author Abiao
 * @copyright 2017
 */
 namespace Admin\Controller;
 use Think\Controller;
class AlbumcateController extends BackendController{
    var $_albumcate_mod = null;
    function __construct(){
        parent::__construct();
        $this->AlbumcateController();
    }
    function AlbumcateController(){
        $this->_albumcate_mod = D('Albumcate');
    }
    
    /** 专辑分类列表 */
    function index(){
        
        //缓存文章类别
        $albumcate=S('albumcate');
        if(!$albumcate){
            $albumcate = $this->_albumcate_mod->get_category(0,true);
            S('albumcate',$albumcate);
        }
        
        //$list = $this->_albumcate_mod->get_category(0,true);
        
        $this->assign('albumcate',$albumcate);
        $this->display('./albumcate.index');
    }
    
    /** 添加分类 */
    function add(){
        if(!IS_POST){
            $list = $this->_albumcate_mod->get_category(0,true);
            $this->assign('albumcate',$list);
            $this->display('./albumcate.form');
        }else{
            $data = array(
                'cate_name' => I('cate_name','','trim'),
                'cate_label' => I('cate_label','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
            );
            
            // 上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     1024*1024*1 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath  =      'album/cate_photo/'; // 设置附件上传目录    // 上传文件
            if($_FILES['photo']['size']>0){
                $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    M()->rollback();
                    $this->error($upload->getError());
                }else{
                    $data['photo'] = $upload->__get('rootPath').$info['photo']['savepath'].$info['photo']['savename'] ;
                }
            }
            
            
            if(!$this->_albumcate_mod->create($data)){
                $this->error($this->_albumcate_mod->getError());
                return;
            }else{
                if(!$this->_albumcate_mod->add($data)){
                    $this->error('分类添加失败');
                    return;
                }
            }
            //清空缓存
            S('albumcate',null);
            $this->success('分类添加成功',U('/Admin/Albumcate'));   
        }
    }
    
    /** 编辑分类 */
    function edit(){
        $id = I('id','','intval');
        $cate = $this->_albumcate_mod->get_category($id);
        if(!$cate){
            $this->error('分类不存在');
            return;
        }
        if(!IS_POST){
            $list = $this->_albumcate_mod->get_category(0,true);
            $this->assign('albumcate',$list);
            $this->assign('cateinfo',$cate);
            $this->display('./albumcate.edit');
        }else{
            $data = array(
                'cate_id' => I('id','','intval'),
                'cate_name' => I('cate_name','','trim'),
                'cate_label' => I('cate_label','','trim'), 
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'), 
                'sort_order' => I('sort_order','','intval'),
            );
            
            if($data['parent_id'] == $id){
                $this->error('所属分类 操作失败：不能自己作为自己的父类别.');
            }
            
            // 上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     1024*1024*1 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath  =      'album/cate_photo/'; // 设置附件上传目录    // 上传文件
            if($_FILES['photo']['size']>0){
                $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    M()->rollback();
                    $this->error($upload->getError());
                }else{
                    $data['photo'] = $upload->__get('rootPath').$info['photo']['savepath'].$info['photo']['savename'] ;
                } 
            } 
            
            if(!$this->_albumcate_mod->create($data)){
                $this->error($this->_albumcate_mod->getError());
                return;
            }else{
                if(false === $this->_albumcate_mod->save($data)){
                    $this->error('分类编辑失败');
                    return;
                }
            }
            //清空缓存
            S('albumcate',null);
            $this->success('分类编辑成功',U('/Admin/Albumcate'));
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
        $cate = $this->_albumcate_mod->where($where)->select();  
        if(empty($cate)){
            $this->error('分类不存在,删除失败');
            return;
        }
        $this->_albumcate_mod->startTrans();
        foreach($cate as $key => $value){
            //删除所有子分类
            if(!$this->_drop_gcategory($value['cate_id'])){
                $this->_albumcate_mod->rollback();
                $this->error('子分类删除失败');
                return;
            }
        }
        if(!$this->_albumcate_mod->where($where)->delete()){
            $this->_albumcate_mod->rollback();
            $this->error('分类删除失败');
            return;
        }
        $this->_albumcate_mod->commit();
        //清空缓存
        S('albumcate',null);
        $this->success('分类删除成功',U('/Admin/Albumcate'));
    }
    
    /** 删除分类及分类下的所有子分类 */
    private function _drop_gcategory($id){
        $where['parent_id'] = $id;
        $cate_list = $this->_albumcate_mod->where($where)->select();
        if(empty($cate_list)) //没有子分类，直接返回TREU
            return true;
        foreach($cate_list as $key => $list){
            $this->_albumcate_mod->delete($list['cate_id']);
            if(!$this->_drop_gcategory($list['cate_id'])) //字分类删除失败
                return false;
        }
        return true;
    }
    
    /** ajax保存排序 */
    function ajaxSortOrder(){
        $_mod=$this->_albumcate_mod;
        $cate_id=I('cate_id');
        if(empty($cate_id)) $this->error('ID传值错误.');
        $where=array(
            'cate_id'=>$cate_id
        );
        $sort_order=I('sort_order',0);
        $data=array('sort_order'=>$sort_order);
        $find=$_mod->where($where)->find(); 
        if($find){
            $res=$_mod->where($where)->save($data);
            //清空缓存
            S('albumcate',null);
        }else{
            $this->success('保存失败.'.$find);
        }
        $this->success('保存成功.'.$res);
    }
    

    /** 分类选择 */
    function sel(){
        //C('SHOW_PAGE_TRACE',True);
        //$list = $this->_albumcate_mod->get_category(0,true);
        //缓存文章类别
        $albumcate=S('albumcate');
        if(!$albumcate){
            $albumcate = $this->_albumcate_mod->get_category(0,true);
            S('albumcate',$albumcate);
        }
        $this->assign('albumcate',$albumcate);
        if(!isset($_REQUEST['multi'])){
            $this->display('./albumcate.sel');
        }
    
    }
    
    /** 分类选择 */
    function selcate(){
        //C('SHOW_PAGE_TRACE',True);
        //$list = $this->_albumcate_mod->get_category(0,true);
        //缓存文章类别
        $albumcate=S('albumcate'); 
        if(!$albumcate){
            $albumcate = $this->_albumcate_mod->get_category(0,true);  
            S('albumcate',$albumcate);
        }
        $this->assign('albumcate',$albumcate);
        if(!isset($_REQUEST['multi'])){
            $this->display('./albumcate.selcate');
        }else{ 
            $this->display('./albumcate.selcate.multi'); 
        }
        
    }
    
    /** 异步获取文章列表 */
    function ajax_get_cate(){
        $cate_ids = I('cate_ids','','trim');
        $where['cate_id']=array('IN',$cate_ids);
        $list=$this->_albumcate_mod->where($where)->Field('cate_id,cate_name,cate_label,photo')->page(1,100)->select();
        $list_str='';
        foreach ($list as $key => $val){
            $list[$key]['relateCatename']= $this->_albumcate_mod->getRelateCatename($val['cate_id']); 
            $list_str.= '<li>'.$list[$key]['relateCatename'].' <a href="'.U('edit',array('id'=>$val['cate_id'])).'" target="_blank">查看</a></li> ';
        }
        echo $list_str;
        //$this->success($list);
    }
    
}




?>