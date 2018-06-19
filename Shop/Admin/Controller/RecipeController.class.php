<?php
/**
 * 食谱菜谱控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class RecipeController extends BackendController{
    var $_recipe_mod = null;
    function __construct(){
        parent::__construct();
        $this->RecipeController();
    }
    function RecipeController(){
        $this->_recipe_mod = D('Recipe');
    }
    
    /** 食谱菜谱列表 */
    function index(){  
        
        $_recipecate_mod = D('Recipecate');
            
        $keywords = I('keywords','','trim');   
        if(isset($_GET['keywords']) && $_GET['keywords']){
//             if(urldecode($keywords)){ echo 'ok';
//                 $keywords = urldecode($keywords);
//             }
            $where['a.title'] = array('like','%'.$keywords.'%');
        }
//         if(isset($_GET['cate_id']) && $_GET['cate_id']){
//             $where['a.cate_id'] = I('cate_id','','intval');
//         }
        
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_recipecate_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_recipecate_mod->get_category(0,true);
        $this->assign('recipecate',$list);
        $count = $this->_recipe_mod
                 ->join(' as a LEFT JOIN __RECIPECATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array( 
        	'keywords'=> $keywords, 
        	'p'=>$_GET['p'], 
            'cate_id' => I('cate_id'),
            'URL_MODEL' => 0
        ); 
        
        $list = $this->_recipe_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __RECIPECATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); 
        
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_recipecate_mod->getRelateCateName($val['cate_id']);
            }
        }
        
        $this->assign('recipe',$list);  
        $this->assign('page',$page->show()); 
        $this->display('./recipe.index');
    }
    
    /** 添加食谱菜谱 */
    function add(){
        if(!IS_POST){
            $_recipecate_mod = D('Recipecate');
            $list = $_recipecate_mod->get_category(0,true);
            $this->assign('recipecate',$list);
            
            //孕期群体
            $gestcate = M('gestcate')->where(array('if_show'=>1,'parent_id'=>array('exp','>0')))->order('parent_id asc')->select();
            $this->assign('gestcate',$gestcate);
            
            $this->display('./recipe.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'content' => I('content'),
            	'photo' => I('photo'),
            	'cutline'=> I('cutline','','trim'),
            	'view_num' => I('view_num',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime(),
                'labels' => I('labels','','trim'),
                'suitable_person_ids' => I('suitable_person_ids','','trim'),
                'taboo_person_ids' => I('taboo_person_ids','','trim'),
                'cautious_person_ids' => I('cautious_person_ids','','trim'),
            );
            if(!$this->_recipe_mod->create($data)){
                $this->error($this->_recipe_mod->getError());
                return;
            }
            
            $id = $this->_recipe_mod->add(); 
            if(empty($id)){
                M()->rollBack();
                $this->error('新增失败.');
            }
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_recipecate_recipe($cate_ids, $id);
            M()->commit();
            
            $this->success('食谱菜谱添加成功',U('index'));
        }
    }
    
    /** 编辑食谱菜谱 */
    function edit(){
        $id = I('id','','intval');
        $recipe = $this->_recipe_mod->find($id);
        
        if(!$recipe){
            $this->error('食谱菜谱不存在');
            return;
        }
        
        
        if(!IS_POST){
            $_recipecate_mod = D('Recipecate');
            $list = $_recipecate_mod->get_category(0,true); 
            $this->assign('recipecate',$list);
            
            //孕期群体
            $gestcate = M('gestcate')->where(array('if_show'=>1,'parent_id'=>array('exp','>0')))->order('parent_id asc')->select();
            $this->assign('gestcate',$gestcate); 
            
            $cate = $_recipecate_mod->get_category( $recipe['cate_id']);  
            $cate_labels = explode(',', $cate['cate_label']);
            
            $recipe['cate_labels']=$cate_labels;
            
            //读取关联分类 
            $cate_ids=$this->getRecipeCateids($id); 
            $this->assign('cate_ids',$cate_ids); 
            
            $this->assign('recipe',$recipe);
            $this->display('./recipe.edit');
            
        }else{
            $data = array(
                'id' => $id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'content' => I('content'),
            	'photo' => I('photo'),
            	'cutline'=> I('cutline','','trim'),
            	'view_num' => I('view_num',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime(),
                'labels' => I('labels','','trim'),
                'suitable_person_ids' => I('suitable_person_ids','','trim'),
                'taboo_person_ids' => I('taboo_person_ids','','trim'),
                'cautious_person_ids' => I('cautious_person_ids','','trim'),
            );
           
            $crt=$this->_recipe_mod->create($data); 
            if(!$crt){
                $this->error($this->_recipe_mod->getError());
                return;
            }
            
            M()->startTrans();
            
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_recipecate_recipe($cate_ids, $id);
             
            $res=$this->_recipe_mod->save($data); 
            M()->commit();
            
            if(false!==$res)
            	$this->success('食谱菜谱编辑成功',U('index'));
            else 
            	$this->error('食谱菜谱编辑失败.');
        }
    }
    
    /** 异步修改食谱菜谱显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $recipe = $this->_recipe_mod->field('id,if_show')->find($id);
        if(!$recipe){
            $this->error('食谱菜谱不存在');
            return;
        }
        if($recipe['if_show']){
            $recipe['if_show'] = 0;
        }else{
            $recipe['if_show'] = 1;
        }
        if(!$this->_recipe_mod->save($recipe)){
            $this->error('修改食谱菜谱显示状态失败');
            return;
        }
        $this->success('修改食谱菜谱显示状态成功',U('index'));
    }
    
    /** 删除食谱菜谱 */
    function drop(){
        $id = I('id','','trim');
        if(!$id){
            $this->error('传入的ID为空，删除失败.');
            return;
        }
        if(strpos($id,','))
            $id = explode(',',$id);
        if(is_array($id)){
            $where['id'] = array('in',$id);
        }else{
            $where['id'] = $id;
        }
        if(!$this->_recipe_mod->where($where)->delete()){
            $this->error('食谱菜谱删除失败.');
            return;
        }
        $this->success('食谱菜谱删除成功.',U('index'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='recipe';
	    if(I('savePath')){
	        $savePath=trim(I('savePath'),'/').'/';
	    }
	    $savePath=trim($savePath,'/').'/';
	    
        $upconfig = array( //图片上传设置
            'maxSize' => 1024*1024, //最大支持上传1M的图片
            'exts' => 'pdf,txt,jpg,jpeg,gif,png',  //图片支持类型
            'subName' => '',
            'savePath' => $savePath,
        	'subName'  => array('date','Ymd')
        ); 
    	if(!IS_POST){
    		$this->assign('upconfig',$upconfig);
    		$this->display('./upload');
    	}else{ 
    		if(empty($_FILES['photo']['size'])){
    			$this->error('请选择上传文件.');
    		}
	        $upfile['file'] = $_FILES['photo'];


	        $upload = new \Think\Upload($upconfig);
	        if(!$file = $upload->upload($upfile)){ 
	            $this->error($upload->getError());
	            return;
	        }
	        $url= C('site_url').$upload->__get('rootPath').$savePath.date('Ymd').'/'.$file['file']['savename'] ;
	        
	        $data=array(
	        	'url'=>$url,
	        	'size'=> ceil($_FILES['photo']['size']/1024).'k',
	        	'name'=> $file['file']['savename'],
	        	'filepath' => trim($url,C('site_url')),
	        );
	        
	        $this->success($data);
	        //$this->ajaxReturn($url,1);
    	}
    }    
    
    /** 读取菜谱对应的关联类别，返回逗号分隔的ids字符串 */
    protected function getRecipeCateids($recipe_id){
        if(empty($recipe_id)){
            return false;
        }
        $where=array(
            'recipe_id' => $recipe_id
        );
        $list=M('recipecate_recipe')->where($where)->field('cate_id')->select();
        $arr= array();
        foreach ($list as $key =>$val){
            $arr[$key] =  $val['cate_id'] ;
        }
        $arr = join(',', $arr );
        return $arr;
    } 
    

    /** 处理菜谱类别对应的关联菜谱 */
    protected function deal_recipecate_recipe($cateStr,$recipe_id){   #dump($cateStr); dump($recipe_id); 
        if(empty($cateStr) or empty($recipe_id)) return ;
        $_mod=M('recipecate_recipe');
        $ids=explode(',',$cateStr);
        //$_mod->where(array('recipe_id'=>$recipe_id))->delete();
        
        foreach ($ids as $key => $val){
            $where=array(
                'cate_id'=>$val,
                'recipe_id' => $recipe_id,
            );
            $find=$_mod->where($where)->find();  
            if(!$find){
                $res=$_mod->add(array('cate_id'=>$val,'recipe_id'=>$recipe_id));    #echo M()->getLastsql(); exit;
                if(!res){
                    M()->rollback();
                    $this->error('菜谱关联失败.');
                }
            }
        }
        $wheredel=array(
            'recipe_id'=>$recipe_id,
            'cate_id'=>array('not in',$cateStr)
        );
        $_mod->where($wheredel)->delete();
    } 
    
}


?>