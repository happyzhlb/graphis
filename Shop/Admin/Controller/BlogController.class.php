<?php
/**
 * 博客控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class BlogController extends BackendController{
    var $_blog_mod = null;
    function __construct(){
        parent::__construct();
        $this->BlogController();
    }
    function BlogController(){
        $this->_blog_mod = D('Blog');
    }
    
    /** 博文列表 */
    function index(){ 
        if(isset($_GET['keywords']) && $_GET['keywords']){
            $where['a.title'] = array('like','%'.I('keywords','','trim').'%');
        }
        
//         if(isset($_GET['cate_id']) && $_GET['cate_id']){
//             $where['a.cate_id'] = I('cate_id','','intval');
//         }
        $_acategory_mod = D('Blogcate');
        
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_acategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_acategory_mod->get_category(0,true);
        $this->assign('acategory',$list);
        $count = $this->_blog_mod
                 ->join(' as a LEFT JOIN __BLOGCATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array(
            'keywords'=> $_GET['keywords'],
            #'p'=>$_GET['p'],
            'cate_id' => I('cate_id'),
            'URL_MODEL' => 0
        );
        $list = $this->_blog_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __BLOGCATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select();
        $this->assign('blog',$list);
        $this->assign('page',$page->show());
        $this->display('./blog.index');
    }
    
    /** 添加博文 */
    function add(){
        if(!IS_POST){
            $_acategory_mod = D('Blogcate');
            $list = $_acategory_mod->get_category(0,true);
            $this->assign('acategory',$list);
            $this->display('./blog.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'content' => I('content'),
            	'img' => I('img'),
            	'cutline'=> I('cutline','','trim'),
            	'clicks' => I('clicks',0,'intval'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime()
            );
            if(!$this->_blog_mod->create($data)){
                $this->error($this->_blog_mod->getError());
                return;
            }
            $this->_blog_mod->add();
            $this->success('博文添加成功',U('index'));
        }
    }
    
    /** 编辑博文 */
    function edit(){
        $bid = I('id','','intval');
        $blog = $this->_blog_mod->find($bid);
        if(!$blog){
            $this->error('博文不存在');
            return;
        }
        if(!IS_POST){
            $_acategory_mod = D('Blogcate');
            $list = $_acategory_mod->get_category(0,true);
            $this->assign('acategory',$list);
            $this->assign('blog',$blog);
            $this->display('./blog.edit');
        }else{
            $data = array(
                'bid' => $bid,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'content' => I('content'),
            	'img' => I('img'),
            	'cutline'=> I('cutline','','trim'),
            	'clicks' => I('clicks',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                //'ctime' => gmtime()
            );
            $crt=$this->_blog_mod->create($data); 
            if(!$crt){
                $this->error($this->_blog_mod->getError());
                return;
            }
            $res=$this->_blog_mod->save($data);
            if(false!==$res)
            	$this->success('博文编辑成功',U('index'));
            else 
            	$this->error('博文编辑失败.');
        }
    }
    
    /** 异步修改博文显示状态 */
    function ajax_edit_status(){
        $bid = I('id','','intval');
        $blog = $this->_blog_mod->field('bid,if_show')->find($bid);
        if(!$blog){
            $this->error('博文不存在');
            return;
        }
        if($blog['if_show']){
            $blog['if_show'] = 0;
        }else{
            $blog['if_show'] = 1;
        }
        if(!$this->_blog_mod->save($blog)){
            $this->error('修改博文显示状态失败');
            return;
        }
        $this->success('修改博文显示状态成功',U('/Admin/Blog'));
    }
    
    /** 删除博文 */
    function drop(){
        $bid = I('id','','trim');
        if(!$bid){
            $this->error('传入的ID为空，删除失败.');
            return;
        }
        if(strpos($bid,','))
            $bid = explode(',',$bid);
        if(is_array($bid)){
            $where['bid'] = array('in',$bid);
        }else{
            $where['bid'] = $bid;
        }
        if(!$this->_blog_mod->where($where)->delete()){
            $this->error('博文删除失败.');
            return;
        }
        $this->success('博文删除成功.',U('/Admin/Blog'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='blog';
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
    
}


?>