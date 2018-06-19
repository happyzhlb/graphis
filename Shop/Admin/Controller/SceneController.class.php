<?php
/**
 * 场景控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class SceneController extends BackendController{
    var $_scene_mod = null;
    function __construct(){
        parent::__construct();
        $this->SceneController();
    }
    function SceneController(){
        $this->_scene_mod = D('Scene');
    }
    
    /** 场景列表 */
    function index(){
    	//C('SHOW_PAGE_TRACE',True); 
    	$_scenecategory_mod = D('Scenecategory');
    	$keywords=I('keywords','','trim');  
    	
        if(!empty($keywords)){
        	$keywords=urldecode(urldecode($keywords));   //苹果电脑火狐浏览器，需要urlencode解密两次？
            $where['_string'] = '(a.scene_id like "%'.$keywords.'%") or (a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") ';
            if(strstr($keywords,' ')){
	            $keywords=str_replace(' ', '%', $keywords);
				$keywords=str_replace('+', '%', $keywords);
	            $subkeywords=explode('%', $keywords);
	            $subkeywords=array_reverse($subkeywords);
	            $subkeywords=join('%',$subkeywords);
	            $where['_string'] = '(a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") or (concat(a.title,c.cate_name) like "%'.$subkeywords.'%") or (concat(c.cate_name,a.title) like "%'.$subkeywords.'%") ';
            }
        }
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_scenecategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        }
		
		//缓存场景类别
        $scenecategory=S('scenecategory');
		if(!$scenecategory){
			$scenecategory = $_scenecategory_mod->get_category(0,true);
			S('scenecategory',$scenecategory);
		}
        $this->assign('scenecategory',$scenecategory);
		
        
        $scenecategory1=$_scenecategory_mod->get_category(0);
        $this->assign('scenecategory1',$scenecategory1);
        
        $keywords=str_replace('%', ' ', $keywords);
        $keywords=str_replace('+', ' ', $keywords);
        $count = $this->_scene_mod
                 ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,20);
		$page->parameter=array( 
			'keywords'=>urlencode($keywords),
			'cate_id'=>$cate_id
			);  
		if(empty($cate_id)){
	        $list = $this->_scene_mod->field('a.*,c.cate_name')
	                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
	                ->where($where)
	                ->limit($page->firstRow.','.$page->listRows)
	                ->order('ctime DESC')->select();
		}else{
	        $list = $this->_scene_mod->field('a.*,c.cate_name')
	                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
	                ->where($where)
	                ->limit($page->firstRow.','.$page->listRows)
	                ->order('sort_order DESC')->select();
		} 
        $this->assign('scene',$list);
        $this->assign('keywords',$keywords);
        $this->assign('page',$page->show());  
        $this->display('./scene.index');
    }
    
    /** 搜索提示框 */
    function searchAuto(){
            $keywords=I('keywords','','trim');
            $where['_string'] = '(a.scene_id like "%'.$keywords.'%") or (a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") ';
            if(strstr($keywords,' ')){
	            $keywords=str_replace(' ', '%', $keywords);
	            $subkeywords=explode('%', $keywords);
	            $subkeywords=array_reverse($subkeywords);
	            $subkeywords=join('%',$subkeywords);
	            $where['_string'] = '(a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") or (concat(a.title,c.cate_name) like "%'.$subkeywords.'%") or (concat(c.cate_name,a.title) like "%'.$subkeywords.'%") ';
            }
        $page = new \Think\Page($count,20);
        $list = $this->_scene_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select();
        if(empty($list)){
        	echo '0';
        	exit;
        }
        echo '<ul>';       
        foreach ($list as $key => $val){ 
	    	echo '<li><a href="'.U("index",array('keywords'=>$val['title'])).'">'.$val['title'].'</a></li>'; 
        }
        echo '<li><a href="javascript:;" onclick="$(this).parent().parent().parent().fadeOut(100)">关闭</a></li>';
    	echo '</ul>';	
    }
    
    /** 搜索提示框 */
    function searchAutoSel(){
            $keywords=I('keywords','','trim');
            $where['_string'] = '(a.scene_id like "%'.$keywords.'%") or (a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") ';
            if(strstr($keywords,' ')){
	            $keywords=str_replace(' ', '%', $keywords);
	            $subkeywords=explode('%', $keywords);
	            $subkeywords=array_reverse($subkeywords);
	            $subkeywords=join('%',$subkeywords);
	            $where['_string'] = '(a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") or (concat(a.title,c.cate_name) like "%'.$subkeywords.'%") or (concat(c.cate_name,a.title) like "%'.$subkeywords.'%") ';
            }
        $page = new \Think\Page($count,20);
        $list = $this->_scene_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select();
        if(empty($list)){
        	echo '0';
        	exit;
        }
        echo '<ul>';       
        foreach ($list as $key => $val){ 
	    	echo '<li><a href="'.U("sel",array('keywords'=>$val['title'])).'">'.$val['title'].'</a></li>'; 
        }
        echo '<li><a href="javascript:;" onclick="$(this).parent().parent().parent().fadeOut(100)">关闭</a></li>';
    	echo '</ul>';	
    }
    
    /** 关联场景列表 */
    function sel(){  
    	C('SHOW_PAGE_TRACE',0);
    	$_scenecategory_mod = D('Scenecategory');
    	$ids=I('ids','','trim');
    	$this->assign('ids',','.$ids.',');
    	$keywords=$_REQUEST['keywords']= trim(I('keywords','','urldecode'));
    	$keywords=str_replace(' ', '%', $keywords);
    	
        if(is_numeric($keywords)){
        	$where['a.scene_id']=array('like',"%".$keywords.'%');
        }
        if(isset($_REQUEST['keywords']) && $_REQUEST['keywords']){
            $where['a.title'] = array('exp','like "%'.$keywords.'%" or c.cate_name like "%'.$keywords.'%"');
        	if(strstr($keywords,'%')){
        		$temp=explode('%', $keywords);
        		$where['a.title'] = array("exp","like '%".$keywords."%' or (c.cate_name like '%".$temp[0]."%' and a.title like '%".$temp[1]."%') or (c.cate_name like '%".$temp[1]."%' and a.title like '%".$temp[0]."%')");
        	}
        }

        $where['_logic'] = 'or';
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
        	$where['_logic'] = 'and';
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_scenecategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
		
        //缓存场景类别
        $scenecategory=S('scenecategory');
		if(!$scenecategory){
			$scenecategory = $_scenecategory_mod->get_category(0,true);
			S('scenecategory',$scenecategory);
		}
        $this->assign('scenecategory',$scenecategory);
		
        $scenecategory1=$_scenecategory_mod->get_category(0);
        $this->assign('scenecategory1',$scenecategory1);
        
        $count = $this->_scene_mod
                 ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,5);
        $page->parameter=array('cate_id'=>$cate_id,'keywords'=>$keywords);
        $list = $this->_scene_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); #echo M()->getLastsql();
        $this->assign('_list',$list);
        $this->assign('page',$page->show());
        if($_REQUEST['type']=='singleId'){
        	$this->display('./scene.sel.singleId');
        }else{
        	$this->display('./scene.sel');
        } 
    }
    
    /** 广告位选择场景 */
    function sel_ad(){  
    	C('SHOW_PAGE_TRACE',0);
    	$_scenecategory_mod = D('Scenecategory');
    	$ids=I('ids','','trim');
    	$this->assign('ids',','.$ids.',');
    	$keywords=$_REQUEST['keywords']= trim(I('keywords','','urldecode'));
    	$keywords=str_replace(' ', '%', $keywords);
    	
        if(is_numeric($keywords)){
        	$where['a.scene_id']=array('like',"%".$keywords.'%');
        }
        if(isset($_REQUEST['keywords']) && $_REQUEST['keywords']){
            $where['a.title'] = array('exp','like "%'.$keywords.'%" or c.cate_name like "%'.$keywords.'%"');
        	if(strstr($keywords,'%')){
        		$temp=explode('%', $keywords);
        		$where['a.title'] = array("exp","like '%".$keywords."%' or (c.cate_name like '%".$temp[0]."%' and a.title like '%".$temp[1]."%') or (c.cate_name like '%".$temp[1]."%' and a.title like '%".$temp[0]."%')");
        	}
        }
		
        $where['_logic'] = 'or';
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
        	$where['_logic'] = 'and';
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_scenecategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
		
        //缓存场景类别
        $scenecategory=S('scenecategory');
		if(!$scenecategory){
			$scenecategory = $_scenecategory_mod->get_category(0,true);
			S('scenecategory',$scenecategory);
		}
        $this->assign('scenecategory',$scenecategory);
		
        $scenecategory1=$_scenecategory_mod->get_category(0);
        $this->assign('scenecategory1',$scenecategory1);
        
        $count = $this->_scene_mod
                 ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,5);
        $page->parameter=array('cate_id'=>$cate_id,'keywords'=>$keywords,'single'=>$_GET['single']);
        $list = $this->_scene_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); #echo M()->getLastsql();
        $this->assign('scene',$list);
        $this->assign('page',$page->show());
        if(isset($_REQUEST['single']) && $_REQUEST['single']==1 ){
        	$this->display('./scene.sel.single');
        }else{
        	$this->display('./scene.sel.ad');
        }
        
    }
        
    function ajaxSetOrder(){
    	$_mod=$this->_scene_mod;
    	$scene_id=I('scene_id');  
    	if(empty($scene_id)) $this->error('ID传值错误.');
    	$where=array(
    		'scene_id'=>$scene_id 
    	);
    	$set_order=I('set_order',0);
    	$data=array('set_order'=>$set_order);
    	$find=$_mod->where($where)->find();
    	if($find){
    		$res=$_mod->where($where)->save($data); 
    	}else{
    		 $this->success('保存失败.'.$find);
    	} 
    	$this->success('保存成功.'.$res);
    }
    
    /** 添加场景 */
    function add(){
        if(!IS_POST){
            $_scenecategory_mod = D('Scenecategory');
			
			//缓存场景类别
			$scenecategory=S('scenecategory');
			if(!$scenecategory){
				$scenecategory = $_scenecategory_mod->get_category(0,true);
				S('scenecategory',$scenecategory);
			}
			$this->assign('scenecategory',$scenecategory);
			
            $this->display('./scene.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
            	'cutline' => I('cutline'),
                'content' => I('content'),
            	'content0' => I('content0'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'is_index' => I('is_index',0),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            	'photo' => getFirstImg(I('content',null,'htmlspecialchars_decode')), //内容页主图
            	//'photo0' => getFirstImg(I('content0',null,'htmlspecialchars_decode')), //列表页主图 
            	'collect_num' => I('collect_num',0),
            	'view_num' => I('view_num',0),
                'ctime' => gmtime()
            ); 

            // 上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath  =      'scene/photo/'; // 设置附件上传目录    // 上传文件
            if($_FILES['photo']['size']>0){
                $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{
                    $data['photo']=C('site_url').$upload->__get('rootPath').$info['photo']['savepath'].$info['photo']['savename'] ;
                }
                $flag=1;
            }
            // 上传图片
            if($_FILES['photo0']['size']>0){
                if($flag!=1) $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{
                    $data['photo0']=C('site_url').$upload->__get('rootPath').$info['photo0']['savepath'].$info['photo0']['savename'] ;
                }
            }
            
            //关联商品处理
            $ag_mod=M('scene_goods');
            $goods_str=trim(I('goods_str'),'');
            $goods_arr=explode(',', $goods_str); #dump($goods_arr); exit;
            $goods_arr=array_unique($goods_arr);
            if(empty($data['cate_id'])){
            	$this->error('场景类别不能为空.');
            }
            M()->startTrans(); 
            $scene_id=$this->_scene_mod->add($data);
            foreach ($goods_arr as $key => $val){
            	if(empty($val)) continue;
            	$res=$ag_mod->add(array('goods_id'=>$val,'scene_id'=>$scene_id)); 
            	if(!res){
            		$ag_mod->rollback();
            		$this->error('商品关联失败.');
            	}
            } 
            
            M()->commit();
            
            
            $this->success('场景添加成功',U('/Admin/Scene'));
        }
    }
    
    /** 编辑场景 */
    function edit(){
        $scene_id = I('id','','intval');
        $scene = $this->_scene_mod->find($scene_id);
        if(!$scene){
            $this->error('场景不存在');
            return;
        }
        if(!IS_POST){
            $_scenecategory_mod = D('Scenecategory');
			
			//缓存场景类别
			$scenecategory=S('scenecategory');
			if(!$scenecategory){
				$scenecategory = $_scenecategory_mod->get_category(0,true);
				S('scenecategory',$scenecategory);
			}
			$this->assign('scenecategory',$scenecategory);			
			$scene['cate_name']=getNameById('cate_name', 'scenecategory', 'cate_id', $scene['cate_id']); 
            $this->assign('scene',$scene); 
            $this->assign('goods_str',$this->getGoodsStr($scene_id));
            $this->display('./scene.edit');
        }else{
            
            $data = array(
                'scene_id' => $scene_id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id',''),
            	'cutline' => I('cutline'),
                'content' => I('content'),
            	'content0' => I('content0'),
                'if_show' => I('if_show','','intval'),
            	'is_index' => I('is_index'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            	'photo' => getFirstImg(I('content',null,'htmlspecialchars_decode')), //内容页主图
            	//'photo0' => getFirstImg(I('content0',null,'htmlspecialchars_decode')), //列表页主图 
            	'collect_num' => I('collect_num',0),
            	'view_num' => I('view_num',0),
            );  
 
            // 上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath  =      'scene/photo/'; // 设置附件上传目录    // 上传文件
            if($_FILES['photo']['size']>0){
                $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{
                    $data['photo']=C('site_url').$upload->__get('rootPath').$info['photo']['savepath'].$info['photo']['savename'] ;
                }
                $flag=1;
            }
            // 上传图片
            if($_FILES['photo0']['size']>0){
                if($flag!=1) $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{
                    $data['photo0']=C('site_url').$upload->__get('rootPath').$info['photo0']['savepath'].$info['photo0']['savename'] ;
                }
            }
            
            //var_dump($data); exit;
            
            if(!$this->_scene_mod->create($data)){
                $this->error($this->_scene_mod->getError());
                return;
            } 
            if(empty($data['cate_id'])){
            	$this->error('场景类别不能为空.');
            }
			M()->startTrans();            
            $this->_scene_mod->save($data);
            M()->commit();
            $this->success('场景编辑成功');
        }
    }
    
    /** 关联商品处理--暂时不用 */
    function doGoodsstr($scene_id,$goods_str=''){
            $ag_mod=M('scene_goods');
            if(empty($goods_str)) $goods_str=trim(I('goods_str'),'');
            $goods_arr=explode(',', $goods_str);
            $goods_arr=array_unique($goods_arr); 
            $ag_mod->where('scene_id='.$scene_id)->delete();
            foreach ($goods_arr as $key => $val){
            	if(empty($val)) continue;
            	$res=$ag_mod->add(array('goods_id'=>$val,'scene_id'=>$scene_id)); 
            	if(!res){
            		$ag_mod->rollback();
            		$this->error('商品关联失败.');
            	}
            }
    }
    
    /** 读取场景对应的关联商品 */
    function getGoodsStr($scene_id){
    	if(empty($scene_id)){
    		return false;
    	}
    	$where=array(
    		'scene_id' => $scene_id
    	);
    	$list=M('scene_goods')->where($where)->field('goods_id,orderNum')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['goods_id'] ;
    	}
    	$arr = join(',', $arr ); 
    	return $arr;
    }
    
    /** 异步修改场景显示状态 */
    function ajax_edit_status(){
        $scene_id = I('id','','intval');
        $scene = $this->_scene_mod->field('scene_id,if_show')->find($scene_id);
        if(!$scene){
            $this->error('场景不存在');
            return;
        }
        if($scene['if_show']){
            $scene['if_show'] = 0;
        }else{
            $scene['if_show'] = 1;
        }
        if(!$this->_scene_mod->save($scene)){
            $this->error('修改场景显示状态失败');
            return;
        }
        $this->success('修改场景显示状态成功',U('/Admin/Scene'));
    }
    
    /** 异步修改场景显示状态 */
    function ajax_edit_isindex(){
        $scene_id = I('id','','intval');
        $scene = $this->_scene_mod->field('scene_id,if_show,is_index')->find($scene_id);
        if(!$scene){
            $this->error('场景不存在');
            return;
        }
        if($scene['is_index']){
            $scene['is_index'] = 0;
        }else{
            $scene['is_index'] = 1;
        }
        if(!$this->_scene_mod->save($scene)){
            $this->error('修改场景首页推荐失败');
            return;
        }
        $this->success('修改场景首页推荐成功',U('/Admin/Scene'));
    }
    
    /** 异步获取场景列表 */
    function ajax_get_scene(){
    	$scene_ids = I('scene_ids','','trim');
    	$where['scene_id']=array('IN',$scene_ids);
    	$list=$this->_scene_mod->where($where)->Field('scene_id,title,cate_id,photo')->page(1,100)->select();
    	//echo M()->getLastsql();
    	$list_str='';
    	foreach ($list as $key => $val){
    		$list_str.= '<li>'.$val['scene_id'].'-'.$val['title'].' <a href="'.U('edit',array('id'=>$val['scene_id'])).'" target="_blank">查看</a></li> ';
    	}
    	echo $list_str;
    	//$this->success($list);
    }
    
    
    /** ajax保存排序 */ 
    function ajaxSortOrder(){
    	$_mod=$this->_scene_mod;
    	$scene_id=I('scene_id');  
    	if(empty($scene_id)) $this->error('ID传值错误.');
    	$where=array(
    		'scene_id'=>$scene_id 
    	);
    	$sort_order=I('sort_order',0);
    	$data=array('sort_order'=>$sort_order);
    	$find=$_mod->where($where)->find();
    	if($find){
    		$res=$_mod->where($where)->save($data); 
    	}else{
    		 $this->success('保存失败.'.$find);
    	} 
    	$this->success('保存成功.'.$res);
    } 
    
    
    
    /** 删除场景 */
    function drop(){
        $scene_id = I('id','','trim');
        if(!$scene_id){
            $this->error('传入的ID为空，删除失败.');
            return;
        }
        if(strpos($scene_id,','))
            $scene_id = explode(',',$scene_id);
        if(is_array($scene_id)){
            $where['scene_id'] = array('in',$scene_id);
        }else{
            $where['scene_id'] = $scene_id;
        }
        if(!$this->_scene_mod->where($where)->delete()){
            $this->error('场景删除失败.');
            return;
        }
		$map=array('scene_id'=>$scene_id);
        M('scene_goods')->where($map)->delete();
        $this->success('场景删除成功.',U('/Admin/Scene'));
    }
}


?>