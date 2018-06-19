<?php
/**
 * 博客控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Page;

use Think\Controller;
class BlogController extends FrontendController{
	var $_mod=null; 
	var $_blogcate_mod=null; 
    function __construct(){
        parent::__construct();
        $this->BlogController();
    }
    
    function BlogController(){
        $this->_mod=D('Blog');  
        $this->_blogcate_mod=D('Admin/Blogcate');
        $_public['blogcate'] = $this->_blogcate_mod->get_category(0,true);  
        $_public['right_list']=$this->_mod->limit(5)->order('bid desc')->select();
        $_public['archives']=$this->archives();
        $this->assign($_public); 
    }
    
    /** 首页 */
    function index(){  
       $where['b.if_show']='1';
       if(I('cate_id','','trim')){
       		$where['b.cate_id']=I('cate_id',0,'intval'); 
       }
       //标题搜索
       $title=I('title','','trim');
       $title=str_ireplace(array(' ','+','-'), '%', $title);
       if(I('title','','trim')){
       		$where['b.title']=array('like','%'.$title.'%'); 
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
       
       
       $totalRows=$this->_mod->join('as b join __BLOGCATE__ as bc on b.cate_id=bc.cate_id')->where($where)->count(); 
       // echo M()->getLastsql();
       $page=New Page($totalRows,10);
       $blog_list=$this->_mod->join('as b join __BLOGCATE__ as bc on b.cate_id=bc.cate_id')->where($where)->limit($page->firstRow.','.$page->listRows)->order('bid desc')->select(); 
       $this->assign('blog_list',$blog_list);  
       $this->assign('page',$page->show());
       $this->display('./blog_list'); 
    }    
    /** 博客h5详情页 */
    function detail(){       	
    	$bid=I('id',0,'intval'); 
    	$blog=$this->_mod->where('bid='.$bid)->find(); 
    	$blog['cate_name']=getNameById('cate_name','blogcate','cate_id',$blog['cate_id']);
    	$this->assign('blog',$blog);
    	$seo=array(
    		'name'=>$blog['title'].' - '.$blog['cate_name'],
    		'keywords'=>$blog['cate_name'], 
    	); 
    	$prev_list=$this->_mod->where('bid>'.$bid)->order('bid asc')->find();
    	$next_list=$this->_mod->where('bid<'.$bid)->order('bid desc')->find();
    	$this->assign('prev_list',$prev_list);
    	$this->assign('next_list',$next_list);
    	
    	$this->display('./blog.detail');
   } 
   //最近月份博文
   function archives(){ 
   		$return='';
        for($i=0;$i<=5;$i++){
            $str= "-".$i." month"; 
            $time=strtotime($str);
            $arr=array(
               'from'=>date('Y-m-01',$time),
               'to'=>date('Y-m-t',$time),
            ); 
            $return.='<li><a href="'.U('/blog/index',$arr).'">'.date('F Y',$time).'</a></li>';
        } 
        return $return;
   }
}


?>