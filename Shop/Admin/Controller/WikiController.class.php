<?php
/**
 * 百科控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class WikiController extends BackendController{
    var $_wiki_mod = null;
    function __construct(){
        parent::__construct();
        $this->WikiController();
    }
    function WikiController(){
        $this->_wiki_mod = D('Wiki');
    }
    
    /** 百科列表 */
    function index(){  
        
        $_wikicate_mod = D('Wikicate');
            
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
            $_wikicate_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_wikicate_mod->get_category(0,true);
        $this->assign('wikicate',$list);
        $count = $this->_wiki_mod
                 ->join(' as a LEFT JOIN __WIKICATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array( 
        	'keywords'=> $keywords, 
        	'p'=>$_GET['p'], 
            'cate_id' => I('cate_id'),
            'URL_MODEL' => 0
        );
       # $page->parameter = 'keywords='.urlencode($keywords); base64_decode($keywords)?$keywords:base64_encode
        
        $list = $this->_wiki_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __WIKICATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select();

        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_wikicate_mod->getRelateCateName($val['cate_id']);
            }
        }
        
        $this->assign('wiki',$list); 
        #C('URL_MODEL',0);
        $this->assign('page',$page->show());
        #C('URL_MODEL',1);
        $this->display('./wiki.index');
    }
    
    /** 添加百科 */
    function add(){
        if(!IS_POST){
            $_wikicate_mod = D('Wikicate');
            $list = $_wikicate_mod->get_category(0,true);
            $this->assign('wikicate',$list);
            $this->display('./wiki.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'content' => I('content'),
            	'photo' => I('photo'),
            	'cutline'=> I('cutline','','trim'),
            	'view_num' => I('view_num',0,'intval'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime(),
                'cate_label' => I('cate_label','','trim')
            );
            if(!$this->_wiki_mod->create($data)){
                $this->error($this->_wiki_mod->getError());
                return;
            }
            $this->_wiki_mod->add();
            $this->success('百科添加成功',U('index'));
        }
    }
    
    /** 编辑百科 */
    function edit(){
        $id = I('id','','intval');
        $wiki = $this->_wiki_mod->find($id);
        
        if(!$wiki){
            $this->error('百科不存在');
            return;
        }
        
        
        if(!IS_POST){
            $_wikicate_mod = D('Wikicate');
            $list = $_wikicate_mod->get_category(0,true); 
            $this->assign('wikicate',$list);
            
            $cate = $_wikicate_mod->get_category( $wiki['cate_id']);  
            $cate_labels = explode(',', $cate['cate_label']);
            
            $wiki['cate_labels']=$cate_labels;
            
            $this->assign('wiki',$wiki);
            $this->display('./wiki.edit');
            
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
                'cate_label' => join(',',$_POST['cate_label'])
            );
            $crt=$this->_wiki_mod->create($data); 
            if(!$crt){
                $this->error($this->_wiki_mod->getError());
                return;
            }
            $res=$this->_wiki_mod->save($data);
            if(false!==$res)
            	$this->success('百科编辑成功',U('index'));
            else 
            	$this->error('百科编辑失败.');
        }
    }
    
    /** 异步修改百科显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $wiki = $this->_wiki_mod->field('id,if_show')->find($id);
        if(!$wiki){
            $this->error('百科不存在');
            return;
        }
        if($wiki['if_show']){
            $wiki['if_show'] = 0;
        }else{
            $wiki['if_show'] = 1;
        }
        if(!$this->_wiki_mod->save($wiki)){
            $this->error('修改百科显示状态失败');
            return;
        }
        $this->success('修改百科显示状态成功',U('/Admin/Wiki'));
    }
    
    /** 删除百科 */
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
        if(!$this->_wiki_mod->where($where)->delete()){
            $this->error('百科删除失败.');
            return;
        }
        $this->success('百科删除成功.',U('/Admin/Wiki'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='wiki';
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
    
    

    /** 商品批量更新专题 */
    function batUpdateWiki(){
        $wiki_ids = I('wiki_ids');
        $wiki_ids = explode(',', $wiki_ids);
        $cate_id = I('cate_id',0,'intval');
        if(empty($wiki_ids)){
            $this->error('请选择要修改的商品.');
        }
        if(empty($cate_id)){
            $this->error('请选择专题.');
        }
        $cate_mod = M('wikicate');
        $wiki_mod = M('wiki');
        M()->startTrans();
        foreach ($wiki_ids as  $wiki_id){
            if(empty($wiki_id)){
                $cate_mod->rollback();
                $this->error('百科wiki_id错误:'.$wiki_id);
            }
            $cate = $cate_mod->field('cate_id')
            ->where(array('cate_id'=>$cate_id))
            ->find();
            if(empty($cate)){
                $cate_mod->rollback();
                $this->error('该分类不存在.');
            }
    
            $data =array('cate_id'=>$cate_id,'cate_label' => I('bat_cate_label','','trim'));
            //添加到cate_wiki表
            $res = $wiki_mod->where(array('id'=>$wiki_id))->save($data);  #echo M()->getLastsql(); exit();
            if(false === $res){ 
                    $cate_mod->rollback();
                    $this->error('关联分类失败.'); 
            }  
        }  
        
       
        //提交事务
        M()->commit();
        $this->success('专题批量处理成功.');
    }
    
    
}


?>