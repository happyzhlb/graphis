<?php
/**
 * 产品分类控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class GcategoryController extends BackendController{
    var $_gcategory_mod = null;
    var $_upload_conifg;
    var $_uploads;
    function __construct(){
        parent::__construct();
        $this->GcategoryController();
    }
    function GcategoryController(){
        $this->_gcategory_mod = D('Gcategory');
        $this->_upload_conifg = array(
            'maxSize' => '204800',
            'exts' => 'gif,jpg,jpeg,png',
            'subName' => '',
            'savePath' => 'gcategory/'
        );
    }
    
    /** 分类列表 */
    function index(){
        $list = $this->_gcategory_mod->get_category(0,true);
        $this->assign('gcategory',$list);
        $this->display('./gcategory.index');
    }

    /** 添加分类 */
    function add(){
        if(!IS_POST){
            $list = $this->_gcategory_mod->get_category(0,true);
            $this->assign('gcategory',$list);
            $this->display('./gcategory.form');
        }else{
            $date = array(
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
                'cate_desc' => trim(I('cate_desc'))
            );
            //处理分类图片
            if($_FILES['cate_image']['size'] > 0){
                $upload = new \Think\Upload($this->_upload_conifg);
                if(!$file = $upload->upload($_FILES)){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['cate_image']['savepath'].$file['cate_image']['savename'];
                if(is_file($filename)){ //文件上传成功，生成缩略图
                    $image = new \Think\Image();
                    $image->open($filename);
                    $image->thumb(200,200,2)->save($filename); //
                }else{
                    $this->error('分类图片上传失败');
                    return;
                }
                $date['cate_image'] = $filename;
            }
            if(!$this->_gcategory_mod->create($date)){
                @unlink($filename);
                $this->error($this->_gcategory_mod->getError());
                return;
            }else{
                if(!$this->_gcategory_mod->add($date)){
                    @unlink($filename);
                    $this->error('分类添加失败');
                    return;
                }
            }
            //清空缓存
            S('gcategory',null);
            $this->success('分类添加成功',U('/Admin/Gcategory'));   
        }
    }
    
    /** 编辑分类 */
    function edit(){
        $id = I('id','','intval');
        $cate = $this->_gcategory_mod->get_category($id);
        if(!$cate){
            $this->error('分类不存在');
            return;
        }
        if(!IS_POST){
            $list = $this->_gcategory_mod->get_category(0,true);
            $this->assign('gcategory',$list);
            $this->assign('cateinfo',$cate);
            $this->display('./gcategory.edit');
        }else{
            $date = array(
                'cate_id' => I('id','','intval'),
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
                'cate_desc' => trim(I('cate_desc'))
            );
            //处理分类图片
            if($_FILES['cate_image']['size'] > 0){
                $upload = new \Think\Upload($this->_upload_conifg);
                if(!$file = $upload->upload($_FILES)){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['cate_image']['savepath'].$file['cate_image']['savename'];
                if(is_file($filename)){ //文件上传成功，生成缩略图
                    $image = new \Think\Image();
                    $image->open($filename);
                    $image->thumb(200,200,2)->save($filename); //
                }else{
                    $this->error('分类图片上传失败');
                    return;
                }
                $date['cate_image'] = $filename;
            }
            if(!$this->_gcategory_mod->create($date)){
                @unlink($filename);
                $this->error($this->_gcategory_mod->getError());
                return;
            }else{
                if(!$this->_gcategory_mod->save($date)){
                    @unlink($filename);
                    $this->error('分类编辑失败');
                    return;
                }
            }
            @unlink($cate['cate_image']);
            //清空缓存
            S('gcategory',null);
            $this->success('分类编辑成功',U('/Admin/Gcategory'));
        }   
    }
    
    /** 删除分类 */
    function drop(){
        $id = I('id','','intval');
        if(!$id){
            $this->error('id为空，删除失败');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['cate_id'] = array('in',$id);
        }else{
            $where['cate_id'] = $id;
        }
        $cate = $this->_gcategory_mod->where($where)->select();
        if(empty($cate)){
            $this->error('分类不存在,删除失败');
            return;
        }
        $this->_gcategory_mod->startTrans();
        foreach($cate as $key => $value){
            //删除所有子分类
            $this->_uploads[] = $value['cate_image'];
            if(!$this->_drop_gcategory($value['cate_id'])){
                $this->_gcategory_mod->rollback();
                $this->error('子分类删除失败');
                return;
            }
        }
        if(!$this->_gcategory_mod->where($where)->delete()){
            $this->_gcategory_mod->rollback();
            $this->error('分类删除失败');
            return;
        }
        $this->_gcategory_mod->commit();
        //清空缓存
        S('gcategory',null);
        //清除删除的图片
        if(!empty($this->uploads)){
            foreach($this->_uploads as $key => $val){
                @unlink($val);
            }
        }
        $this->success('分类删除成功',U('/Admin/Gcategory'));
    }
    
    /** 删除分类及分类下的所有子分类 */
    private function _drop_gcategory($id){
        $where['parent_id'] = $id;
        $cate_list = $this->_gcategory_mod->where($where)->select();
        if(empty($cate_list)) //没有子分类，直接返回TREU
            return true;
        foreach($cate_list as $key => $list){
            $this->_uploads[] = $list['cate_image'];
            $this->_gcategory_mod->delete($list['cate_id']);
            if(!$this->_drop_gcategory($list['cate_id'])) //字分类删除失败
                return false;
        }
        return true;
    }
    
}


?>