<?php
/**
 * 专辑控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class PictureController extends BackendController{
    var $_picture_mod = null;
    function __construct(){
        parent::__construct();
        $this->PictureController();
    }
    function PictureController(){
        $this->_picture_mod = D('Picture');
    }
    
    /** 专辑列表 */
    function index(){  
        
        $_picturecate_mod = D('Picturecate');
            
        $keywords = I('keywords','','trim');   
        if(isset($_GET['keywords']) && $_GET['keywords']){
//             if(urldecode($keywords)){ echo 'ok';
//                 $keywords = urldecode($keywords);
//             }
            $where['a.title'] = array('like','%'.$keywords.'%');
        }
        if(isset($_GET['album_id']) && $_GET['album_id']){
            $where['a.album_id'] = I('album_id','','intval');
        }
        
//         if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
//             $cate_id = I('cate_id','','intval');
//             $cate_ids='';
//             $_picturecate_mod->_get_children_cate_id($cate_ids,$cate_id);
//             $cate_ids=join(',',$cate_ids);
//             $where['a.cate_id']=array('exp','in('.$cate_ids.')');
//         } 
//         $list = $_picturecate_mod->get_category(0,true);  dump($list);

        $album = M('album')->select();
        $this->assign('album',$album);
        $count = $this->_picture_mod
                 ->join(' as a LEFT JOIN __ALBUMCATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array( 
        	'keywords'=> $keywords, 
        	'p'=>$_GET['p'], 
            'album_id' => I('album_id'),
            'URL_MODEL' => 0
        ); 
        
        $list = $this->_picture_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ALBUMCATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); 
        
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_picturecate_mod->getRelateCateName($val['cate_id']);
            }
        }
        
        $this->assign('picture',$list);  
        $this->assign('page',$page->show()); 
        $this->display('./picture.index');
    }
    
    /** 添加专辑 */
    function add(){
        if(!IS_POST){  
            //专辑
            $albums = M('album')->where(array('if_show'=>1))->order('id desc')->select();
            $this->assign('albums',$albums);  
            
            $this->display('./picture.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'album_id' => I('album_id','','intval'),
                'cate_id' => I('cate_id','','intval'), 
            	'photo' => I('photo'),
            	'cutline'=> I('cutline','','trim'),
            	'view_num' => I('view_num',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','10','intval'),
                'ctime' => gmtime(),
                'labels' => I('labels','','trim'),  
            	'is_recommend' => I('is_recommend'),
            );
            if(!$this->_picture_mod->create($data)){
                $this->error($this->_picture_mod->getError());
                return;
            }
            
            if(empty($data['album_id'])){ $this->error('请选择专辑。'); }
            
            
           # dump($data); 
            
            #exit;
            $n = 0;
            foreach ($data['photo'] as $photo){ 
            	if(empty($photo) || $photo =='/Uploads/800/') continue;
            	$data['title'] = I('title').++$n; 
            	$data['photo'] = $photo;
            	$id = $this->_picture_mod->add($data);  
            
	            if(empty($id)){
	                M()->rollBack();
	                $this->error('新增失败.');
	            }
            }
//             //处理关联分类
//             $cate_ids = I('cate_id','','trim');
//             if(empty($cate_ids)){ $this->error('请选择分类。'); }
//             $this->deal_picturecate_picture($cate_ids, $id);
            M()->commit();
            
            $this->success('专辑添加成功',U('index'));
        }
    }
    
    /** 编辑专辑 */
    function edit(){
        $id = I('id','','intval');
        $picture = $this->_picture_mod->find($id);
        
        if(!$picture){
            $this->error('专辑不存在');
            return;
        }
        
        
        if(!IS_POST){  
            //专辑
            $albums = M('album')->where(array('if_show'=>1))->order('id desc')->select();
            $this->assign('albums',$albums);  
            
            $this->assign('picture',$picture);
            $this->display('./picture.edit');
            
        }else{
            $data = array(
                'id' => $id,
                'title' => I('title','','trim'),
            	'album_id' => I('album_id','','intval'),
                'cate_id' => I('cate_id','','intval'), 
            	'photo' => I('photo','','trim'),
            	'cutline'=> I('cutline','','trim'),
            	'view_num' => I('view_num',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime(),
                'labels' => I('labels','','trim'),  
            	'is_recommend' => I('is_recommend'),
            );
           
            $crt=$this->_picture_mod->create($data); 
            if(!$crt){
                $this->error($this->_picture_mod->getError());
                return;
            }
             
            if(empty($data['album_id'])){ $this->error('请选择专辑。'); }
            
            M()->startTrans(); 
             
            $res=$this->_picture_mod->save($data); 
            M()->commit();
            
            if(false!==$res)
            	$this->success('专辑编辑成功',U('index',array('album_id'=>$data['album_id'])));
            else 
            	$this->error('专辑编辑失败.');
        }
    }
    
    /** 异步修改专辑显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $picture = $this->_picture_mod->field('id,if_show')->find($id);
        if(!$picture){
            $this->error('专辑不存在');
            return;
        }
        if($picture['if_show']){
            $picture['if_show'] = 0;
        }else{
            $picture['if_show'] = 1;
        }
        if(!$this->_picture_mod->save($picture)){
            $this->error('修改专辑显示状态失败');
            return;
        }
        $this->success('修改专辑显示状态成功',U('index'));
    }
    
    /** 删除专辑 */
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
        if(!$this->_picture_mod->where($where)->delete()){
            $this->error('专辑删除失败.');
            return;
        }
        $this->success('专辑删除成功.',U('index'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='picture';
	    if(I('savePath')){
	        $savePath=trim(I('savePath'),'/').'/';
	    }
	    $savePath=trim($savePath,'/').'/';
	    
        $upconfig = array( //图片上传设置
            'maxSize' => 1024*1024*15, //最大支持上传1M的图片
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
	        $url= $upload->__get('rootPath').$savePath.date('Ymd').'/'.$file['file']['savename'] ;
	        
	        //生成缩略图160x240
	        $image = new \Think\Image();
	        $image->open($url);
	        $pathinfo =  pathinfo($url);
	        $thumbfile = rtrim($pathinfo['dirname'].'/'.$pathinfo['basename'],'.'.$pathinfo['extension']).'(160x240).'.$pathinfo['extension'];
	        $image->thumb(160,240,2)->save($thumbfile);
	        $data['thumb'] = $thumbfile;
	        
	        $data=array(
	        	'url'=>trim($url,'.'),
	        	'size'=> ceil($_FILES['photo']['size']/1024).'k',
	        	'name'=> $file['file']['savename'],
	        	'filepath' => trim($url,C('site_url')),
	        );
	        
	        $this->success($data);
	        //$this->ajaxReturn($url,1);
    	}
    }    
    
    /** 读取图片对应的关联类别，返回逗号分隔的ids字符串 */
    protected function getPictureCateids($picture_id){
        if(empty($picture_id)){
            return false;
        }
        $where=array(
            'picture_id' => $picture_id
        );
        $list=M('picturecate_picture')->where($where)->field('cate_id')->select();
        $arr= array();
        foreach ($list as $key =>$val){
            $arr[$key] =  $val['cate_id'] ;
        }
        $arr = join(',', $arr );
        return $arr;
    } 
    

    /** 处理图片类别对应的关联图片 */
    protected function deal_picturecate_picture($cateStr,$picture_id){   #dump($cateStr); dump($picture_id); 
        if(empty($cateStr) or empty($picture_id)) return ;
        $_mod=M('picturecate_picture');
        $ids=explode(',',$cateStr);
        //$_mod->where(array('picture_id'=>$picture_id))->delete();
        
        foreach ($ids as $key => $val){
            $where=array(
                'cate_id'=>$val,
                'picture_id' => $picture_id,
            );
            $find=$_mod->where($where)->find();  
            if(!$find){
                $res=$_mod->add(array('cate_id'=>$val,'picture_id'=>$picture_id));    #echo M()->getLastsql(); exit;
                if(!res){
                    M()->rollback();
                    $this->error('图片关联失败.');
                }
            }
        }
        $wheredel=array(
            'picture_id'=>$picture_id,
            'cate_id'=>array('not in',$cateStr)
        );
        $_mod->where($wheredel)->delete();
    } 
    
    

    /** ajax保存排序 */
    function ajaxSortOrder(){
    	$_mod=$this->_picture_mod;
    	$id=I('id');
    	if(empty($id)) $this->error('ID传值错误.');
    	$where=array(
    			'id'=>$id
    	);
    	$sort_order=I('sort_order',0);
    	$data=array('sort_order'=>$sort_order);
    	$find=$_mod->where($where)->find();
    	if($find){
    		$res=$_mod->where($where)->save($data);
    		//清空缓存
    		S('albumcate',null);
    	}else{
    		$this->success('保存失败.'.$find);
    	}
    	$this->success('保存成功.'.$res);
    }
    
}


?>