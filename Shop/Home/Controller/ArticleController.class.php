<?php
/**
 * 文章控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class ArticleController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null; 
	var $_cate_mod=null;
    function __construct(){
        parent::__construct();
        $this->ArticleController();
    }
    
    function ArticleController(){
        $this->_mod=D('Admin/Article');   
        $this->_cate_mod=D('Admin/Acategory');
    }
    
    /** 读取文章列表 */
    function index(){ 
    	$this->detail();
    }
    
    function detail(){    
    	$cate_id=I('cate_id','','intval'); 
    	$cate=$this->_cate_mod->find($cate_id);
    	if(!$cate){
    		$this->error('类别ID错误.');
    	}
    	$this->_config_seo(array_merge($this->_seo,array('name'=>$cate['cate_name']))); 
    	$this->assign('top_name',$cate['cate_name']);
    	//文章列表
    	$where['cate_id']=$cate_id;
        $where['if_show'] = 1; 
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,100);
    	$list=$this->_mod->where($where)->field('article_id,cate_id,title')
    		->limit($page->firstRow.','.$page->listRows)->select();
    	$this->assign('_list',$list);
    	$article_id=I('article_id','','intval');
    	if($article_id){
    		$article=$this->_mod->where('article_id='.$article_id)->find();   
    	}else{
    		$article=$this->_mod->where('cate_id='.$cate_id)->order('article_id')->find(); 
    	}  
    	//echo M()->getLastsql();
    	$this->assign('curr_id',$article['article_id']);
    	$this->assign('article',$article); 
    	$this->display('./article.detail'); 
    }  
    
	/** 知识 等多级类目 排版显示 */
    function knowledge(){
    	$cate_id=I('cate_id','3','intval'); 
    	if(!$cate_id){
    		$this->error('大类别ID错误.');
    	}
    	$cate=$this->_cate_mod->get_category($cate_id,true);   
    	$sub_id=I('sub_id');
    	if(empty($sub_id)){
    		$sub_id=$cate[0]['children'][0]['cate_id'];
    	} 
    	//二级类目
    	$subcate=$this->_cate_mod->get_category($sub_id,true);   
    	foreach ($subcate[0]['children'] as $key =>$val){ 
    		$subcate[0]['children'][$key]['article']=$this->_mod->getArticleList($val['cate_id'],100,1);
    	}   
    	
    	$this->assign('_cate',$cate);
    	$this->assign('sub_id',$sub_id);
    	$this->assign('subcate',$subcate);
    	$this->display('./article.knowledge');
    }
    
    /** 知识详情页 */
    function show(){
        $cate_id=I('cate_id','','intval'); 
    	$cate=$this->_cate_mod->find($cate_id);
    	if(!$cate){
    		$this->error('类别ID错误.');
    	}
    	$this->_config_seo(array_merge($this->_seo,array('name'=>$cate['cate_name']))); 
    	$this->assign('top_name',$cate['cate_name']);
    	//文章列表
    	$where['cate_id']=$cate_id;
        $where['if_show'] = 1;  
    	$where['article_id']=I('article_id','','intval');
    	$article=$this->_mod->where($where)->find();     
    	//echo M()->getLastsql();
    	$this->assign('curr_id',$article['article_id']);
    	$this->assign('article',$article);  
    	$this->display('./article.knowledge.show');  
    }
    
    function newslist(){
    	$cate_id=I('cate_id','','intval'); 
    	$cate=$this->_cate_mod->find($cate_id);
    	if(!$cate){
    		$this->error('类别ID错误.');
    	}
    	$this->_config_seo(array_merge($this->_seo,array('name'=>$cate['cate_name']))); 
    	$this->assign('top_name',$cate['cate_name']);
    	//文章列表
    	$where['cate_id']=$cate_id;
        $where['if_show'] = 1; 
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,100);
    	$list=$this->_mod->where($where)->field('article_id,cate_id,title')
    		->limit($page->firstRow.','.$page->listRows)->select(); 
    	$this->assign('_list',$list);
    	$this->display('./article.list'); 
    }

    function newslist2(){
    	$cate_id=I('cate_id','','intval'); 
    	$cate=$this->_cate_mod->find($cate_id);
    	if(!$cate){
    		$this->error('类别ID错误.');
    	}
    	$this->_config_seo(array_merge($this->_seo,array('name'=>$cate['cate_name']))); 
    	$this->assign('top_name',$cate['cate_name']);
    	//文章列表
    	$where['cate_id']=$cate_id;
        $where['if_show'] = 1; 
        $count = $this->_mod->where($where)->count();
        $page = new \Think\Page($count,100);
    	$list=$this->_mod->where($where)->field('article_id,cate_id,title')
    		->limit($page->firstRow.','.$page->listRows)->select(); 
    	$this->assign('_list',$list);
    	$this->display('./article.list2'); 
    }  
    
    function jigou(){
    	$this->display('./article.jigou'); 
    }
}


?>