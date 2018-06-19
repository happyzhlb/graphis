<?php
/**
 * 帖子分类控制器
 * @author Abiao
 * @copyright 2016
 */
 namespace Admin\Controller;
 use Think\Controller;
class BbscategoryController extends BackendController{
    var $_bbscategory_mod = null;
    function __construct(){
        parent::__construct();
        $this->BbscategoryController();
    }
    function BbscategoryController(){
        $this->_bbscategory_mod = D('Bbscategory');
    }
    
    /** 帖子分类列表 */
    function index(){
        $list = $this->_bbscategory_mod->get_category(0,true);
        $this->assign('bbscategory',$list);
        $this->display('./bbscategory.index');
    }
    
    /** 添加分类 */
    function add(){
        if(!IS_POST){
            $list = $this->_bbscategory_mod->get_category(0,true);
            $this->assign('bbscategory',$list);
            $this->display('./bbscategory.form');
        }else{
            $data = array(
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
            );
            
           // 上传图片
           if($_FILES['photo']['size']>0){
				$upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      './bbs/cate_photo/'; // 设置附件上传目录    // 上传文件    
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$data['photo']=$info['photo']['savepath'].$info['photo']['savename'] ;
				 } 
        	} 
        	
            if(!$this->_bbscategory_mod->create($data)){
                $this->error($this->_bbscategory_mod->getError());
                return;
            }else{
                if(!$this->_bbscategory_mod->add($data)){
                    $this->error('分类添加失败');
                    return;
                }
            }
            //清空缓存
            S('bbscategory',null);
            $this->success('分类添加成功',U('/Admin/Bbscategory'));   
        }
    }
    
    /** 编辑分类 */
    function edit(){
        $id = I('id','','intval');
        $cate = $this->_bbscategory_mod->get_category($id);
        if(!$cate){
            $this->error('分类不存在');
            return;
        }
        if(!IS_POST){
            $list = $this->_bbscategory_mod->get_category(0,true);
            $this->assign('bbscategory',$list);
            $this->assign('cateinfo',$cate);
            $this->display('./bbscategory.edit');
        }else{ 
            $data = array(
                'cate_id' => I('id','','intval'),
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
            );
            
           // 上传图片
           if($_FILES['photo']['size']>0){
				$upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      './bbs/cate_photo/'; // 设置附件上传目录    // 上传文件    
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$data['photo']=$info['photo']['savepath'].$info['photo']['savename'] ;
				 } 
        	} 
            if($data['cate_id']==$data['parent_id']) $this->error('不能把自己作为父类.');
            if(!$this->_bbscategory_mod->create($data)){
                $this->error($this->_bbscategory_mod->getError());
                return;
            }else{
                if(!$this->_bbscategory_mod->save($data)){
                    $this->error('分类编辑失败');
                    return;
                }
            }
            //清空缓存
            S('bbscategory',null);
            $this->success('分类编辑成功',U('/Admin/Bbscategory'));
        }   
    }
    
     /** 删除分类 */
    function drop(){
        $id = I('id','','trim');
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
        $cate = $this->_bbscategory_mod->where($where)->select();
        if(empty($cate)){
            $this->error('分类不存在,删除失败');
            return;
        }
        $this->_bbscategory_mod->startTrans();
        foreach($cate as $key => $value){
            //删除所有子分类
            if(!$this->_drop_gcategory($value['cate_id'])){
                $this->_bbscategory_mod->rollback();
                $this->error('子分类删除失败');
                return;
            }
        }
        if(!$this->_bbscategory_mod->where($where)->delete()){
            $this->_bbscategory_mod->rollback();
            $this->error('分类删除失败');
            return;
        }
        $this->_bbscategory_mod->commit();
        //清空缓存
        S('bbscategory',null);
        $this->success('分类删除成功',U('/Admin/Bbscategory'));
    }
    
    /** 删除分类及分类下的所有子分类 */
    private function _drop_gcategory($id){
        $where['parent_id'] = $id;
        $cate_list = $this->_bbscategory_mod->where($where)->select();
        if(empty($cate_list)) //没有子分类，直接返回TREU
            return true;
        foreach($cate_list as $key => $list){
            $this->_bbscategory_mod->delete($list['cate_id']);
            if(!$this->_drop_gcategory($list['cate_id'])) //字分类删除失败
                return false;
        }
        return true;
    }
    
	public function upload(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/'; // 设置附件上传目录    // 上传文件    
		$info   =   $upload->upload();    
		 if(!$info) {// 上传错误提示错误信息       
		 	 $this->error($upload->getError());    
		 }else{// 上传成功       
		 	 $this->success('上传成功！');    
		 } 
	}
    
}


?>