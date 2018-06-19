<?php
/**
 * 场景主题控制器
 * @author Abiao
 * @copyright 2017
 */
 namespace Admin\Controller;
 use Think\Controller;
class ScenecategoryController extends BackendController{
    var $_scenecategory_mod = null;
    function __construct(){
        parent::__construct();
        $this->ScenecategoryController();
    }
    function ScenecategoryController(){
        $this->_scenecategory_mod = D('Scenecategory');
    }
    
    /** 场景主题 */
    function index(){ 
    	//C('SHOW_PAGE_TRACE',True); 
        //$list = $this->_scenecategory_mod->get_category(0,true);
		//缓存场景类别
        $scenecategory=S('scenecategory');
		if(!$scenecategory){
			$scenecategory = $this->_scenecategory_mod->get_category(0,true);  #dump($scenecategory);
			S('scenecategory',$scenecategory);
		}  
        $this->assign('scenecategory',$scenecategory); 
        $this->display('./scenecategory.index');
    }

    /** 场景主题选择 */
    function sel_ad(){ 
    	//C('SHOW_PAGE_TRACE',True); 
        //$list = $this->_scenecategory_mod->get_category(0,true);
		//缓存场景类别
        $scenecategory=S('scenecategory');
		if(!$scenecategory){
			$scenecategory = $this->_scenecategory_mod->get_category(0,true);
			S('scenecategory',$scenecategory);
		}
        $this->assign('scenecategory',$scenecategory); 
        if(isset($_REQUEST['single'])){
        	$this->display('./scenecategory.sel.ad');
        }else{
        	$this->display('./scenecategory.sel');
        }
        
    }

    /** 场景主题选择 */
    function sel_cate(){ 
    	//C('SHOW_PAGE_TRACE',True); 
        //$list = $this->_scenecategory_mod->get_category(0,true);
		//缓存场景类别
        $scenecategory=S('scenecategory');   
		if(!$scenecategory){
			$scenecategory = $this->_scenecategory_mod->get_category(0,true);
			S('scenecategory',$scenecategory);
		}
        $this->assign('scenecategory',$scenecategory); 
        if(!isset($_REQUEST['multi'])){
        	$this->display('./scenecategory.selcate');
        } 
        
    }
    
    
    /** 获取子分类 */
    function ajaxGetCategory(){     
    	$cate_id=I('cate_id');
    	$list=$this->_scenecategory_mod->get_category($cate_id,true);
    	if($list[0]['children']){
    		echo "<option value='' class='option'>==选择二级类别==</option>";
	    	foreach ($list[0]['children'] as $key => $val){
	    		echo "<option value=".$val['cate_id']." class='option'>".$val['cate_name']."</option>";
	    	}
    	}else{
    		echo 'false';
    	}
    }
    
    
    /** 添加分类 */
    function add(){
        if(!IS_POST){
	        //关联场景
	        $scene_ids = $this->getSceneStr($cate_id);
	        $this->assign('scene_ids',$scene_ids);
	        
        	$scenecategory=S('scenecategory');
        	if(empty($scenecategory)){
            	$scenecategory = $this->_scenecategory_mod->get_category(0,true);
        	}
            $this->assign('scenecategory',$scenecategory);
            
            //孕期标签
        	$gestation = M('gestation')->field('gestation_id,gestation_name,gestation_days')->select();
        	$this->assign('gestation',$gestation); 
        	
            $this->display('./scenecategory.form');
        }else{
            $data = array(
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show',1),
            	'is_recommend' => I('is_recommend',0),
            	'type' => I('type'),
            	'cate_desc'=>I('cate_desc','','trim'),
            	'is_hold' => I('is_hold',0),
                'sort_order' => I('sort_order','','intval'),
            	'gestation_ids' => join(',',I('gestation_ids')),
            );
           
           // 上传图片 
           $upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      'scene/cate_photo/'; // 设置附件上传目录    // 上传文件           
           if($_FILES['photo']['size']>0){ 
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$data['photo']= $upload->__get('rootPath').$info['photo']['savepath'].$info['photo']['savename'] ;
				 } 
				 $flag=1;
        	} 
           // 上传品牌图片
           if($_FILES['b_photo']['size']>0){  
				if($flag!=1) $info   =   $upload->upload();   
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$data['b_photo']= $upload->__get('rootPath').$info['b_photo']['savepath'].$info['b_photo']['savename'] ;
				 } 
        	} 
        	
            if(!$this->_scenecategory_mod->create($data)){
                $this->error($this->_scenecategory_mod->getError());
                return;
            }else{
                if(!$this->_scenecategory_mod->add($data)){
                    $this->error('分类添加失败');
                    return;
                }
            }
            //清空缓存
            S('scenecategory',null);
            $this->success('分类添加成功',U('/Admin/Scenecategory'));   
        }
    }
    
    /** 编辑分类 */
    function edit(){
        $cate_id = I('id','','intval');
        if(empty($cate_id)){
        	$this->error('cate_id错误.');
       	}
        $cate = $this->_scenecategory_mod->get_category($cate_id);
        $cate['photo']=str_replace(C('site_url'), '/', $cate['photo']);
        $cate['b_photo']=str_replace(C('site_url'), '/', $cate['b_photo']);
        if(!$cate){
            $this->error('分类不存在');
            return;
        }

        	
        if(!IS_POST){
        	$scenecategory=S('scenecategory');
        	if(empty($scenecategory)){
            	$scenecategory = $this->_scenecategory_mod->get_category(0,true);
        	}
        
	        //关联场景
	        $scene_ids = $this->getSceneStr($cate_id); 
	        $this->assign('scene_ids',$scene_ids);
               	
            //孕期标签
        	$gestation = M('gestation')->field('gestation_id,gestation_name,gestation_days')->select();
        	$this->assign('gestation',$gestation); 
        	
            $this->assign('scenecategory',$scenecategory);
            $this->assign('cateinfo',$cate);
            $this->display('./scenecategory.edit');
        }else{
        	 
            $data = array(
                'cate_id' => $cate_id,
                'cate_name' => I('cate_name','','trim'),
                'parent_id' => I('parent_id'),
                'if_show' => I('if_show'),
            	'type' => I('type'),
            	'cate_desc'=>I('cate_desc','','trim'),
                'is_hold' => I('is_hold'),
            	'is_recommend' => I('is_recommend'),
                'sort_order' => I('sort_order','','intval'),
            	'gestation_ids' => join(',',I('gestation_ids')),
            ); 
            
			$acate_brand_mod = $this->_scenecategory_mod;
           // 上传图片 
           $upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      'scene/cate_photo/'; // 设置附件上传目录    // 上传文件           
           if($_FILES['photo']['size']>0){ 
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	$acate_brand_mod->rollback();
				 	$this->error($upload->getError());    
				 }else{     
				 	$data['photo']=C('site_url').$upload->__get('rootPath').$info['photo']['savepath'].$info['photo']['savename'] ;
				 } 
				 $flag=1;
        	} 
           // 上传品牌图片
           if($_FILES['b_photo']['size']>0){  
				if($flag!=1) $info   =   $upload->upload();   
				 if(!$info) {// 上传错误提示错误信息       
				 	$acate_brand_mod->rollback();
				 	$this->error($upload->getError());    
				 }else{     
				 	$data['b_photo']=C('site_url').$upload->__get('rootPath').$info['b_photo']['savepath'].$info['b_photo']['savename'] ;
				 } 
        	} 
            if($data['cate_id']==$data['parent_id']) $this->error('不能把自己作为父类.');
            if(!$this->_scenecategory_mod->create($data)){
            	$acate_brand_mod->rollback();
                $this->error($this->_scenecategory_mod->getError());
                return;
            }else{
                if(FALSE===$this->_scenecategory_mod->save($data)){
                	$acate_brand_mod->rollback();
                    $this->error('分类编辑失败'.M()->getLastsql());
                    return;
                }
            }
            M()->commit();
            
            //清空缓存
            S('scenecategory',null);
            $this->success('分类编辑成功',U('/Admin/Scenecategory'));
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
        $where['is_hold']=0;
        $cate = $this->_scenecategory_mod->where($where)->select();
        if(empty($cate)){
            $this->error('分类不存在或者Hold状态,删除失败');
            return;
        }
        $this->_scenecategory_mod->startTrans();
        foreach($cate as $key => $value){
            //删除所有子分类
            if(!$this->_drop_gcategory($value['cate_id'])){
                $this->_scenecategory_mod->rollback();
                $this->error('子分类删除失败');
                return;
            }
        }
        if(!$this->_scenecategory_mod->where($where)->delete()){
            $this->_scenecategory_mod->rollback();
            $this->error('分类删除失败');
            return;
        }
        $this->_scenecategory_mod->commit();
        
        //清空缓存
        S('scenecategory',null);
        
        $this->success('分类删除成功',U('index'));
    }
    
    /** 删除分类及分类下的所有子分类 */
    private function _drop_gcategory($id){
    	if(empty($id)) return false;
        $where['parent_id'] = $id;
        $cate_list = $this->_scenecategory_mod->where($where)->select();
        if(empty($cate_list)) //没有子分类，直接返回TREU
            return true;
        $scenecategory_scene_mod=M('category_scene');
        foreach($cate_list as $key => $list){
            $this->_scenecategory_mod->delete($list['cate_id']);
            //$scenecategory_scene_mod->delete($list['cate_id']);  //暂时没用场景类别一对多关系
            if(!$this->_drop_gcategory($list['cate_id'])) //字分类删除失败
                return false;
        }
        //清空缓存
        S('scenecategory',null);
        return true;
    }
    
    /** ajax保存排序 */
    function ajaxSortOrder(){
    	$_mod=$this->_scenecategory_mod;
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
        	S('scenecategory',null);
    	}else{
    		 $this->success('保存失败.'.$find);
    	} 
    	$this->success('保存成功.'.$res);
    } 
    
    /** 读取类别对应的关联的品牌 IDs(字符串) */
    function getBrandStr($cate_id){
    	if(empty($cate_id)){
    		return false;
    	}
    	$where=array(
    		'cate_id' => $cate_id
    	);
    	$list=M('acate_brand')->where($where)->field('brand_id')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['brand_id'] ;
    	} 
    	$return = join(',', $arr );  
    	return $return;
    }
    
    /** 读取类别对应的关联的品牌 (json) */
    function getBrandJson($cate_id){
    	if(empty($cate_id)){
    		return false;
    	}
    	$where=array(
    		'cate_id' => $cate_id
    	);
    	$list=M('acate_brand')->where($where)->field('brand_id')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['brand_id'] ;
    	} 
    	$return = M('brand')->field('brand_id,brand_name,blogo')->where(array('brand_id' => array('in',$arr)))->select();  
    	$return = json_encode($return); 
    	return $return;
    }

    /** 读取类别对应的关联的场景 IDs(字符串) */
    function getSceneStr($cate_id){
    	if(empty($cate_id)){
    		return false;
    	}
    	$where=array(
    		'cate_id' => $cate_id
    	);
    	$list=M('scene')->where($where)->field('scene_id')->select(); 
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['scene_id'] ;
    	} 
    	$return = join(',', $arr );  
    	return $return;
    }    
}


?>