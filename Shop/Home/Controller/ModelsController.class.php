<?php
/**
 * 模特控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Home\Controller;
use Think\Page;

use Think\Controller;
class ModelsController extends FrontendController{
	var $_mod=null; 
	var $_modelscate_mod=null; 
    function __construct(){
        parent::__construct();
        $this->ModelsController();
    }
    
    function ModelsController(){
        $this->_mod=D('Models');  
        $this->_modelscate_mod=D('Admin/Modelscate');
        //$_public['modelscate'] = $this->_modelscate_mod->get_category(0,true);  
        $_public['weekstar']= $this->_mod->where(array('if_show' => 1,'is_top'=>1))->limit('0,3')->order('view_num desc,id desc')->select();
 
        $this->assign($_public); 
    }
    
    /** 首页 */
    function index(){ 
       $where['if_show']='1';
       $cate_id = I('cate_id','','trim');
       if($cate_id){
       		$where['_string']= 'find_in_set("'.$cate_id.'",cate_id)'; 
       }
       //标题搜索
       $title=I('title','','trim');
       $title=str_ireplace(array(' ','+','-'), '%', $title);
       if(I('title','','trim')){
       		$where['title']=array('like','%'.$title.'%'); 
       }  
       //内容搜索
       $content=I('content','','trim');
       $content=str_ireplace(array(' ','+','-'), '%', $content);
       if(I('content','','trim')){
       		$where['content']=array('like','%'.$content.'%'); 
       }  
	   //日期过滤
	   $from=I('from','','strtotime'); 
       $to=I('to','','trim');
       if($from && $to){
       	  $strfrom=array('egt',$from); 
       	  $to=$to.' 23:59:59';
          $to=strtotime($to);
       	  $strto=array('elt',$to);
       	  $where['ctime']=array($strfrom,$strto);
       }
       
       $list = $this->_modelscate_mod->get_category(0,true);
       $this->assign('modelscate',$list);
       
       $totalRows=$this->_mod->where($where)->count(); 
       #  echo M()->getLastsql();
       $page=New Page($totalRows,16);
       $models_list=$this->_mod->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select(); 
       foreach ($models_list as $key => $val){
       	$models_list[$key]['album_num'] = M('album')->where(array('models_id'=>$val['id']))->count();
       	 #echo M()->getLastsql();
       }
       
       $this->assign('models_list',$models_list);  
       $this->assign('page',$page->show());
       $this->display('index/models.index'); 
    }    
    
    /** 模特的专辑列表 */
    function albumlist(){
    	$models_id=I('id',0,'intval');
    	if(empty($models_id)){
    		$this->error('模特ID不能为空。');
    	}
    	
       $where = array(
       		'if_show'=> 1,
       		'models_id'=>$models_id,	
       	);
        
       //标题搜索
       $title=I('title','','trim');
       $title=str_ireplace(array(' ','+','-'), '%', $title);
       if(I('title','','trim')){
       		$where['title']=array('like','%'.$title.'%'); 
       }  
       //内容搜索
       $content=I('content','','trim');
       $content=str_ireplace(array(' ','+','-'), '%', $content);
       if(I('content','','trim')){
       		$where['content']=array('like','%'.$content.'%'); 
       }  
       
       $totalRows= M('album')->where($where)->count();  
       $page=New Page($totalRows,16);
       
       $album_list= M('album')->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select(); 
 	    
       M('models')->where(array('id'=>$models_id))->setInc('view_num');
       $models = M('models')->where(array('id'=>$models_id))->find();
       $this->assign('models',$models);
        
       $this->assign('album_list',$album_list);  
    	$this->assign('page',$page->show());
    	$this->display('index/models.albumlist');
    }
    
    
    /** 模特h5详情页 */
    function detail(){       	
    	$id=I('id',0,'intval'); 
    	$models=$this->_mod->where('id='.$id)->find(); 
    	$models['cate_name']=getNameById('cate_name','modelscate','cate_id',$models['cate_id']);
    	$this->assign('models',$models);
    	$seo=array(
    		'name'=>$models['title'].' - '.$models['cate_name'],
    		'keywords'=>$models['cate_name'], 
    	); 
    	$prev_list=$this->_mod->where('id>'.$id)->order('id asc')->find();
    	$next_list=$this->_mod->where('id<'.$id)->order('id desc')->find();
    	$this->assign('prev_list',$prev_list);
    	$this->assign('next_list',$next_list);
    	
    	$this->display('./models.detail');
   }  
}


?>