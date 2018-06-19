<?php
/**
 * 文章控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class ArticleController extends BackendController{
    var $_article_mod = null;
    function __construct(){
        parent::__construct();
        $this->ArticleController();
    }
    function ArticleController(){
        $this->_article_mod = D('Article');
    }
    
    /** 文章列表 */
    function index(){
        
       
    	//C('SHOW_PAGE_TRACE',True); 
    	$_acategory_mod = D('Acategory');
    	$keywords=I('keywords','','trim');  
    	
    	//dump($_acategory_mod->getRelateCatename(1054));
    	
        if(!empty($keywords)){
        	$keywords=urldecode(urldecode($keywords));   //苹果电脑火狐浏览器，需要urlencode解密两次？
            $where['_string'] = '(a.article_id like "%'.$keywords.'%") or (a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") ';
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
            $_acategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        
        
		//缓存文章类别
        $acategory=S('acategory');
		if(!$acategory){
			$acategory = $_acategory_mod->get_category(0,true);
			S('acategory',$acategory);
		}
        $this->assign('acategory',$acategory);
		 
        $acategory1=$_acategory_mod->get_category(0);
        $this->assign('acategory1',$acategory1);
        
        $keywords=str_replace('%', ' ', $keywords);
        $keywords=str_replace('+', ' ', $keywords);
        $count = $this->_article_mod
                 ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,20);
		$page->parameter=array( 
			'keywords'=>urlencode($keywords),
			'cate_id'=>$cate_id
			);  
		if(empty($cate_id)){
	        $list = $this->_article_mod->field('a.*,c.cate_name')
	                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
	                ->where($where)
	                ->limit($page->firstRow.','.$page->listRows)
	                ->order('ctime DESC')->select();
		}else{
	        $list = $this->_article_mod->field('a.*,c.cate_name')
	                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
	                ->where($where)
	                ->limit($page->firstRow.','.$page->listRows)
	                ->order('sort_order DESC')->select();
		} 
		
		foreach ($list as $key => $val){
		    $list[$key]['relateCateName'] = $_acategory_mod->getRelateCateName($val['cate_id']);
		}
		 
		
        $this->assign('article',$list);
        $this->assign('keywords',$keywords);
        $this->assign('page',$page->show());  
        $this->display('./article.index');
    }
    
    /** 搜索提示框 */
    function searchAuto(){
            $keywords=I('keywords','','trim');
            $where['_string'] = '(a.article_id like "%'.$keywords.'%") or (a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") ';
            if(strstr($keywords,' ')){
	            $keywords=str_replace(' ', '%', $keywords);
	            $subkeywords=explode('%', $keywords);
	            $subkeywords=array_reverse($subkeywords);
	            $subkeywords=join('%',$subkeywords);
	            $where['_string'] = '(a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") or (concat(a.title,c.cate_name) like "%'.$subkeywords.'%") or (concat(c.cate_name,a.title) like "%'.$subkeywords.'%") ';
            }
        $page = new \Think\Page($count,20);
        $list = $this->_article_mod->field('a.*,c.cate_name')
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
            $where['_string'] = '(a.article_id like "%'.$keywords.'%") or (a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") ';
            if(strstr($keywords,' ')){
	            $keywords=str_replace(' ', '%', $keywords);
	            $subkeywords=explode('%', $keywords);
	            $subkeywords=array_reverse($subkeywords);
	            $subkeywords=join('%',$subkeywords);
	            $where['_string'] = '(a.title like "%'.$keywords.'%") or ( c.cate_name like "%'.$keywords.'%") or (concat(a.title,c.cate_name) like "%'.$subkeywords.'%") or (concat(c.cate_name,a.title) like "%'.$subkeywords.'%") ';
            }
        $page = new \Think\Page($count,20);
        $list = $this->_article_mod->field('a.*,c.cate_name')
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
    
    /** 关联文章列表 */
    function sel(){  
    	C('SHOW_PAGE_TRACE',0);
    	$_acategory_mod = D('Acategory');
    	$ids=I('ids','','trim');
    	$this->assign('ids',','.$ids.',');
    	$keywords=$_REQUEST['keywords']= trim(I('keywords','','urldecode'));
    	$keywords=str_replace(' ', '%', $keywords);
    	
        if(is_numeric($keywords)){
        	$where['a.article_id']=array('like',"%".$keywords.'%');
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
            $_acategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
		
        //缓存文章类别
        $acategory=S('acategory');
		if(!$acategory){
			$acategory = $_acategory_mod->get_category(0,true);
			S('acategory',$acategory);
		}
        $this->assign('acategory',$acategory);
		
        $acategory1=$_acategory_mod->get_category(0);
        $this->assign('acategory1',$acategory1);
        
        $count = $this->_article_mod
                 ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,5);
        $page->parameter=array('cate_id'=>$cate_id,'keywords'=>$keywords);
        $list = $this->_article_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); #echo M()->getLastsql();
        
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_acategory_mod->getRelateCateName($val['cate_id']);
            }
        }
        
        $this->assign('article',$list);
        $this->assign('page',$page->show());
        if($_REQUEST['type']=='singleId'){
        	$this->display('./article.sel.singleId');
        }else{
        	$this->display('./article.sel');
        } 
    }
    
    /** 广告位选择文章 */
    function sel_ad(){  
    	C('SHOW_PAGE_TRACE',0);
    	$_acategory_mod = D('Acategory');
    	$ids=I('ids','','trim');
    	$this->assign('ids',','.$ids.',');
    	$keywords=$_REQUEST['keywords']= trim(I('keywords','','urldecode'));
    	$keywords=str_replace(' ', '%', $keywords);
    	
        if(is_numeric($keywords)){
        	$where['a.article_id']=array('like',"%".$keywords.'%');
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
            $_acategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
		
        //缓存文章类别
        $acategory=S('acategory');
		if(!$acategory){
			$acategory = $_acategory_mod->get_category(0,true);
			S('acategory',$acategory);
		}
        $this->assign('acategory',$acategory);
		
        $acategory1=$_acategory_mod->get_category(0);
        $this->assign('acategory1',$acategory1);
        
        $count = $this->_article_mod
                 ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,5);
        $page->parameter=array('cate_id'=>$cate_id,'keywords'=>$keywords,'single'=>$_GET['single']);
        $list = $this->_article_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ACATEGORY__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); 
        
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_acategory_mod->getRelateCateName($val['cate_id']);
            }
        }
        
        $this->assign('article',$list);
        $this->assign('page',$page->show());
        if(isset($_REQUEST['single']) && $_REQUEST['single']==1 ){
        	$this->display('./article.sel.single');
        }else{
        	$this->display('./article.sel.ad');
        }
        
    }
        
    function ajaxSetOrder(){
    	$_mod=$this->_article_mod;
    	$article_id=I('article_id');  
    	if(empty($article_id)) $this->error('ID传值错误.');
    	$where=array(
    		'article_id'=>$article_id 
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
    
    /** 添加文章 */
    function add(){
        if(!IS_POST){
            $_acategory_mod = D('Acategory');
			
			//缓存文章类别
			$acategory=S('acategory');
			if(!$acategory){
				$acategory = $_acategory_mod->get_category(0,true);
				S('acategory',$acategory);
			}
			$this->assign('acategory',$acategory);
			
            $this->display('./article.form');
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
            	//'photo' => getFirstImg(I('content',null,'htmlspecialchars_decode')), //内容页主图
            	//'photo0' => getFirstImg(I('content0',null,'htmlspecialchars_decode')), //列表页主图 
            	'collect_num' => I('collect_num',0),
            	'view_num' => I('view_num',0),
                'ctime' => gmtime()
            ); 

            // 上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath  =      'article/photo/'; // 设置附件上传目录    // 上传文件
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
            $ag_mod=M('article_goods');
            $goods_str=trim(I('goods_str'),'');
            $goods_arr=explode(',', $goods_str); #dump($goods_arr); exit;
            $goods_arr=array_unique($goods_arr);
            if(empty($data['cate_id'])){
            	$this->error('专题类别不能为空.');
            }
            M()->startTrans(); 
            $article_id=$this->_article_mod->add($data);
            foreach ($goods_arr as $key => $val){
            	if(empty($val)) continue;
            	$res=$ag_mod->add(array('goods_id'=>$val,'article_id'=>$article_id)); 
            	if(!res){
            		$ag_mod->rollback();
            		$this->error('商品关联失败.');
            	}
            } 
            
            M()->commit();
            
            
            $this->success('文章添加成功',U('/Admin/Article'));
        }
    }
    
    /** 编辑文章 */
    function edit(){
        $article_id = I('id','','intval');
        $article = $this->_article_mod->find($article_id);
        if(!$article){
            $this->error('文章不存在');
            return;
        }
        if(!IS_POST){
            $_acategory_mod = D('Acategory');
			
			//缓存文章类别
			$acategory=S('acategory');
			if(!$acategory){
				$acategory = $_acategory_mod->get_category(0,true);
				S('acategory',$acategory);
			}
			$this->assign('acategory',$acategory);			
			//$article['cate_name']=getNameById('cate_name', 'acategory', 'cate_id', $article['cate_id']); 
			$article['relateCateName']= $_acategory_mod->getRelateCateName($article['cate_id']);
            $this->assign('article',$article); 
            $this->assign('goods_str',$this->getGoodsStr($article_id));
            $this->display('./article.edit');
        }else{
            
            $data = array(
                'article_id' => $article_id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id',''),
            	'cutline' => I('cutline','','trim'),
                'content' => I('content','','trim'),
            	'content0' => I('content0'),
                'if_show' => I('if_show','','intval'),
            	'is_index' => I('is_index'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
            	//'photo' => getFirstImg(I('content',null,'htmlspecialchars_decode')), //内容页主图
            	//'photo0' => getFirstImg(I('content0',null,'htmlspecialchars_decode')), //列表页主图 
            	'collect_num' => I('collect_num',0),
            	'view_num' => I('view_num',0),
            );  
  
            // 上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath  =      'article/photo/'; // 设置附件上传目录    // 上传文件
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
            
            if(!$this->_article_mod->create($data)){
                $this->error($this->_article_mod->getError());
                return;
            } 
            if(empty($data['cate_id'])){
            	$this->error('专题类别不能为空.');
            }
			M()->startTrans();            
            $this->_article_mod->save($data);
            M()->commit();
            $this->success('文章编辑成功');
        }
    }
    
    /** 关联商品处理--暂时不用 */
    function doGoodsstr($article_id,$goods_str=''){
            $ag_mod=M('article_goods');
            if(empty($goods_str)) $goods_str=trim(I('goods_str'),'');
            $goods_arr=explode(',', $goods_str);
            $goods_arr=array_unique($goods_arr); 
            $ag_mod->where('article_id='.$article_id)->delete();
            foreach ($goods_arr as $key => $val){
            	if(empty($val)) continue;
            	$res=$ag_mod->add(array('goods_id'=>$val,'article_id'=>$article_id)); 
            	if(!res){
            		$ag_mod->rollback();
            		$this->error('商品关联失败.');
            	}
            }
    }
    
    /** 读取文章对应的关联商品 */
    function getGoodsStr($article_id){
    	if(empty($article_id)){
    		return false;
    	}
    	$where=array(
    		'article_id' => $article_id
    	);
    	$list=M('article_goods')->where($where)->field('goods_id,orderNum')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['goods_id'] ;
    	}
    	$arr = join(',', $arr ); 
    	return $arr;
    }
    
    /** 异步修改文章显示状态 */
    function ajax_edit_status(){
        $article_id = I('id','','intval');
        $article = $this->_article_mod->field('article_id,if_show')->find($article_id);
        if(!$article){
            $this->error('文章不存在');
            return;
        }
        if($article['if_show']){
            $article['if_show'] = 0;
        }else{
            $article['if_show'] = 1;
        }
        if(!$this->_article_mod->save($article)){
            $this->error('修改文章显示状态失败');
            return;
        }
        $this->success('修改文章显示状态成功',U('/Admin/Article'));
    }
    
    /** 异步修改文章显示状态 */
    function ajax_edit_isindex(){
        $article_id = I('id','','intval');
        $article = $this->_article_mod->field('article_id,if_show,is_index')->find($article_id);
        if(!$article){
            $this->error('文章不存在');
            return;
        }
        if($article['is_index']){
            $article['is_index'] = 0;
        }else{
            $article['is_index'] = 1;
        }
        if(!$this->_article_mod->save($article)){
            $this->error('修改文章首页推荐失败');
            return;
        }
        $this->success('修改文章首页推荐成功',U('/Admin/Article'));
    }
    
    /** 异步获取文章列表 */
    function ajax_get_article(){
    	$article_ids = I('article_ids','','trim');
    	$where['article_id']=array('IN',$article_ids);
    	$list=$this->_article_mod->where($where)->Field('article_id,title,cate_id,photo')->page(1,100)->select();
    	$list_str='';
    	foreach ($list as $key => $val){
    		$list_str.= '<li>'.$val['article_id'].'-'.$val['title'].' <a href="'.U('edit',array('id'=>$val['article_id'])).'" target="_blank">查看</a></li> ';
    	}
    	echo $list_str;
    	//$this->success($list);
    }
    
    
    /** ajax保存排序 */ 
    function ajaxSortOrder(){
    	$_mod=$this->_article_mod;
    	$article_id=I('article_id');  
    	if(empty($article_id)) $this->error('ID传值错误.');
    	$where=array(
    		'article_id'=>$article_id 
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
    
    
    
    /** 删除文章 */
    function drop(){
        $article_id = I('id','','trim');
        if(!$article_id){
            $this->error('传入的ID为空，删除失败.');
            return;
        }
        if(strpos($article_id,','))
            $article_id = explode(',',$article_id);
        if(is_array($article_id)){
            $where['article_id'] = array('in',$article_id);
        }else{
            $where['article_id'] = $article_id;
        }
        if(!$this->_article_mod->where($where)->delete()){
            $this->error('文章删除失败.');
            return;
        }
		$map=array('article_id'=>$article_id);
        M('article_goods')->where($map)->delete();
        $this->success('文章删除成功.',U('/Admin/Article'));
    }
}


?>