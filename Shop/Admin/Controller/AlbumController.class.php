<?php
/**
 * 专辑控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class AlbumController extends BackendController{
    var $_album_mod = null;
    function __construct(){
        parent::__construct();
        $this->AlbumController();
    }
    function AlbumController(){
        $this->_album_mod = D('Album');
    }
    
    /** 专辑列表 */
    function index(){  
        
        $_albumcate_mod = D('Albumcate');
            
        $keywords = I('keywords','','trim');   
        if(isset($_GET['keywords']) && $_GET['keywords']){
//             if(urldecode($keywords)){ echo 'ok';
//                 $keywords = urldecode($keywords);
//             }
            $where['a.title'] = array('like','%'.$keywords.'%');
        }
        
        if(isset($_GET['models_id']) && $_GET['models_id']){
            $where['a.models_id'] = I('models_id','','intval');
        }
        
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_albumcate_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_albumcate_mod->get_category(0,true);
        $this->assign('albumcate',$list);
        $count = $this->_album_mod
                 ->join(' as a LEFT JOIN __ALBUMCATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array( 
        	'keywords'=> $keywords, 
        	'p'=>$_GET['p'], 
            'cate_id' => I('cate_id'),
            'URL_MODEL' => 0
        ); 
        
        $list = $this->_album_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __ALBUMCATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('id DESC')->select(); 
        
        $accesslog_mod = M('accesslog'); 
        $reward_order_mod = M('reward_order'); 
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_albumcate_mod->getRelateCateName($val['cate_id']); 
                //今日浏览量
                $where = array('album_id'=>$val['id'],'_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())" );
                $today_view_num = (int)$accesslog_mod->where($where)->sum('view_num'); ;
                $list[$key]['today_view_num'] = $today_view_num ;
                //今日打赏量
                $where = array('album_id'=>$val['id'],'order_status'=>20,'_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())" );
                $today_reward_num = (int)$reward_order_mod->where($where)->count(); 
                $list[$key]['today_reward_num'] = $today_reward_num ;
            }
        }
        
        //累计打赏金额统计
        $total_reward_fee = M('reward_order')->where(array('order_status'=>20))->sum('total_amount');
        $this->assign('total_reward_fee',$total_reward_fee);
        //累计打赏次数统计
        $total_reward_times = M('reward_order')->where(array('order_status'=>20))->count();
        $this->assign('total_reward_times',$total_reward_times);

        //今日浏览量
        $where = array('_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())" );
        $today_view_num = (int)$accesslog_mod->where($where)->sum('view_num'); 
        $this->assign('today_view_num',$today_view_num);
        //今天打赏金额统计
        $today_reward_fee = $reward_order_mod->where(array('order_status'=>20,'_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())"))->sum('total_amount');
        $this->assign('today_reward_fee',$today_reward_fee);
        //今天打赏次数统计
        $today_reward_times = $reward_order_mod->where(array('order_status'=>20,'_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())"))->count();
        $this->assign('today_reward_times',$today_reward_times);
        
        $this->assign('models',M('models')->select());  
        $this->assign('album',$list);  
        $this->assign('page',$page->show()); 
        $this->display('./album.index');
    }
    
    /** 添加专辑 */
    function add(){
        if(!IS_POST){
            $_albumcate_mod = D('Albumcate');
            $list = $_albumcate_mod->get_category(0,true);
            $this->assign('albumcate',$list);
            
            //模特
            $modelslist = M('models')->where(array('if_show'=>1))->order('id desc')->select();
            $this->assign('modelslist',$modelslist); 
            
            $this->display('./album.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'models_id' => I('models_id','','trim'),
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
                'city' => I('city','','trim'),
                'pubdate' => I('pubdate','','trim'),
                'coin' => I('coin','','trim'),
            	'reward_fee' => I('reward_fee','','trim'),
            	'pay_index' => I('pay_index','','intval'),
            	'is_recommend' => I('is_recommend'),
            );
            
            $data['models_title'] =  getNameById('title', 'models', 'id', $data['models_id']);
             
            if(!$this->_album_mod->create($data)){
                $this->error($this->_album_mod->getError());
                return;
            }
            
            $id = $this->_album_mod->add(); 
            if(empty($id)){
                M()->rollBack();
                $this->error('新增失败.');
            }
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_albumcate_album($cate_ids, $id);
            M()->commit();
            
            $this->success('专辑添加成功',U('index'));
        }
    }
    
    /** 编辑专辑 */
    function edit(){
        $id = I('id','','intval');
        $album = $this->_album_mod->find($id);
        
        if(!$album){
            $this->error('专辑不存在');
            return;
        }
        
        
        if(!IS_POST){
            $_albumcate_mod = D('Albumcate');
            $list = $_albumcate_mod->get_category(0,true); 
            $this->assign('albumcate',$list);
            
            //模特
            $modelslist = M('models')->where(array('if_show'=>1))->order('id desc')->select();
            $this->assign('modelslist',$modelslist); 
            
            $cate = $_albumcate_mod->get_category( $album['cate_id']);  
            $cate_labels = explode(',', $cate['cate_label']);
            
            $album['cate_labels']=$cate_labels;
            
            //读取关联分类 
            $cate_ids=$this->getAlbumCateids($id); 
            $this->assign('cate_ids',$cate_ids); 
            
            $this->assign('album',$album);
            $this->display('./album.edit');
            
        }else{
            $data = array(
                'id' => $id,
             	'models_id' => I('models_id','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'title' => I('title'),
            	'content' => I('content'),            	
            	'photo' => I('photo'),
            	'cutline'=> I('cutline','','trim'),
            	'view_num' => I('view_num',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime(),
                'labels' => I('labels','','trim'),
                'city' => I('city','','trim'),
                'pubdate' => I('pubdate','','trim'),
                'coin' => I('coin','','trim'),
            	'reward_fee' => I('reward_fee','','trim'),
            	'pay_index' => I('pay_index','','intval'),	
            	'is_recommend' => I('is_recommend'),
            );
            
            $data['models_title'] =  getNameById('title', 'models', 'id', $data['models_id']);
             
           
            $crt=$this->_album_mod->create($data); 
            if(!$crt){
                $this->error($this->_album_mod->getError());
                return;
            }
            
            M()->startTrans();
            
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_albumcate_album($cate_ids, $id);
             
            $res=$this->_album_mod->save($data); 
            M()->commit();
            
            if(false!==$res)
            	$this->success('专辑编辑成功',U('index'));
            else 
            	$this->error('专辑编辑失败.');
        }
    }
    
    /** 异步修改专辑显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $album = $this->_album_mod->field('id,if_show')->find($id);
        if(!$album){
            $this->error('专辑不存在');
            return;
        }
        if($album['if_show']){
            $album['if_show'] = 0;
        }else{
            $album['if_show'] = 1;
        }
        if(!$this->_album_mod->save($album)){
            $this->error('修改专辑显示状态失败');
            return;
        }
        $this->success('修改专辑显示状态成功',U('index'));
    }
    
    /** 异步修改专辑推荐状态 */
    function ajax_edit_recommend(){
    	$id = I('id','','intval');
    	$album = $this->_album_mod->field('id,is_recommend')->find($id);
    	if(!$album){
    		$this->error('专辑不存在');
    		return;
    	}
    	if($album['is_recommend']){
    		$album['is_recommend'] = 0;
    	}else{
    		$album['is_recommend'] = 1;
    	}
    	if(!$this->_album_mod->save($album)){
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
        if(!$this->_album_mod->where($where)->delete()){
            $this->error('专辑删除失败.');
            return;
        }
        $this->success('专辑删除成功.',U('index'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='album';
	    if(I('savePath')){
	        $savePath=trim(I('savePath'),'/').'/';
	    }
	    $savePath=trim($savePath,'/').'/';
	    
        $upconfig = array( //图片上传设置
            'maxSize' => 1024*1024*10, //最大支持上传1M的图片
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
    
    /** 打赏记录 */
    function reward_detail(){
    	$id = I('id');
    	$album = M('album')->where(array('id'=>$id))->find();
    	$this->assign('album',$album);
    	$where = array('order_status'=>20,'album_id'=>$id);
    	$record = M('reward_order')->where($where)
    		->order('ctime desc')
    		->select(); 
    	$this->assign('record',$record);
    	$this->display('./album.reward_detail');
    	
    }
    
    
    /** 读取菜谱对应的关联类别，返回逗号分隔的ids字符串 */
    protected function getAlbumCateids($album_id){
        if(empty($album_id)){
            return false;
        }
        $where=array(
            'album_id' => $album_id
        );
        $list=M('albumcate_album')->where($where)->field('cate_id')->select();
        $arr= array();
        foreach ($list as $key =>$val){
            $arr[$key] =  $val['cate_id'] ;
        }
        $arr = join(',', $arr );
        return $arr;
    } 
    

    /** 处理菜谱类别对应的关联菜谱 */
    protected function deal_albumcate_album($cateStr,$album_id){   #dump($cateStr); dump($album_id); 
        if(empty($cateStr) or empty($album_id)) return ;
        $_mod=M('albumcate_album');
        $ids=explode(',',$cateStr);
        //$_mod->where(array('album_id'=>$album_id))->delete();
        
        foreach ($ids as $key => $val){
            $where=array(
                'cate_id'=>$val,
                'album_id' => $album_id,
            );
            $find=$_mod->where($where)->find();  
            if(!$find){
                $res=$_mod->add(array('cate_id'=>$val,'album_id'=>$album_id));    #echo M()->getLastsql(); exit;
                if(!res){
                    M()->rollback();
                    $this->error('菜谱关联失败.');
                }
            }
        }
        $wheredel=array(
            'album_id'=>$album_id,
            'cate_id'=>array('not in',$cateStr)
        );
        $_mod->where($wheredel)->delete();
    } 
    
}


?>