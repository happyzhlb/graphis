<?php
/**
 * 模特控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class ModelsController extends BackendController{
    var $_models_mod = null;
    function __construct(){
        parent::__construct();
        $this->ModelsController();
    }
    function ModelsController(){
        $this->_models_mod = D('Models');
    }
    
    /** 模特列表 */
    function index(){  
        
        $_modelscate_mod = D('Modelscate');
            
        $keywords = I('keywords','','trim');   
        if(isset($_GET['keywords']) && $_GET['keywords']){ 
            $where['a.title'] = array('like','%'.$keywords.'%');
        }
//         if(isset($_GET['cate_id']) && $_GET['cate_id']){
//             $where['a.cate_id'] = I('cate_id','','intval');
//         }
        
        if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
            $cate_id = I('cate_id','','intval');
            $cate_ids='';
            $_modelscate_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_modelscate_mod->get_category(0,true);
        $this->assign('modelscate',$list);
        $count = $this->_models_mod
                 ->join(' as a LEFT JOIN __MODELSCATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array( 
        	'keywords'=> $keywords, 
        	'p'=>$_GET['p'], 
            'cate_id' => I('cate_id'),
            'URL_MODEL' => 0
        ); 
        
        $list = $this->_models_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __MODELSCATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('ctime DESC')->select(); 
        
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_modelscate_mod->getRelateCateName($val['cate_id']);
            }
        }
        
        $this->assign('models',$list);
        $this->assign('page',$page->show()); 
        $this->display('./models.index');
    }
    
    /** 添加模特 */
    function add(){
        if(!IS_POST){
            $_modelscate_mod = D('Modelscate');
            $list = $_modelscate_mod->get_category(0,true);
            $this->assign('modelscate',$list); 
            
            $this->display('./models.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','trim'),
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
                'career' => I('career','','trim'),
                'constellation' => I('constellation','','trim'),
            	'is_recommend' => I('is_recommend','','trim'),
            	'is_top' => I('is_top','','trim'),
            );
            
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){
            	M()->rollBack();
            	$this->error('请选择分类。');
            }
            
           # $data['cate_id'] = 
            
            if(!$this->_models_mod->create($data)){
                $this->error($this->_models_mod->getError());
                return;
            }
            
            $id = $this->_models_mod->add(); 
            if(empty($id)){
                M()->rollBack();
                $this->error('新增失败.');
            }
            
            $this->deal_modelscate_models($cate_ids, $id);
            
            M()->commit();
            
            $this->success('模特添加成功',U('index'));
        }
    }
    
    /** 编辑模特 */
    function edit(){
        $id = I('id','','intval');
        $models = $this->_models_mod->find($id);
        
        if(!$models){
            $this->error('模特不存在');
            return;
        }
        
        
        if(!IS_POST){
            $_modelscate_mod = D('Modelscate');
            $list = $_modelscate_mod->get_category(0,true); 
            $this->assign('modelscate',$list); 
            
            $cate = $_modelscate_mod->get_category( $models['cate_id']);  
            $cate_labels = explode(',', $cate['cate_label']);
            
            $models['cate_labels']=$cate_labels;
            
            //读取关联分类 
            $cate_ids=$this->getModelsCateids($id); 
            $this->assign('cate_ids',$cate_ids); 
            
            $this->assign('models',$models);
            $this->display('./models.edit');
            
        }else{
            $data = array(
                'id' => $id,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','trim'),
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
                'career' => I('career','','trim'),
                'constellation' => I('constellation','','trim'),
            	'is_recommend' => I('is_recommend','','trim'),
            	'is_top' => I('is_top','','trim')
            );
           
            $crt=$this->_models_mod->create($data); 
            if(!$crt){
                $this->error($this->_models_mod->getError());
                return;
            }
            
            M()->startTrans();
            
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_modelscate_models($cate_ids, $id);
             
            $res=$this->_models_mod->save($data); 
            M()->commit();
            
            if(false!==$res)
            	$this->success('模特编辑成功',U('index'));
            else 
            	$this->error('模特编辑失败.');
        }
    }
    
    /** 异步修改模特显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $models = $this->_models_mod->field('id,if_show')->find($id);
        if(!$models){
            $this->error('模特不存在');
            return;
        }
        if($models['if_show']){
            $models['if_show'] = 0;
        }else{
            $models['if_show'] = 1;
        }
        if(!$this->_models_mod->save($models)){
            $this->error('修改模特显示状态失败');
            return;
        }
        $this->success('修改模特显示状态成功',U('index'));
    }
    

    /** 异步修改专辑推荐状态 */
    function ajax_edit_recommend(){
    	$id = I('id','','intval');
    	$models = $this->_models_mod->field('id,is_recommend')->find($id);
    	if(!$models){
    		$this->error('专辑不存在');
    		return;
    	}
    	if($models['is_recommend']){
    		$models['is_recommend'] = 0;
    	}else{
    		$models['is_recommend'] = 1;
    	}
    	if(!$this->_models_mod->save($models)){
    		$this->error('修改模特推荐状态失败');
    		return;
    	}
    	$this->success('修改模特推荐状态成功',U('index'));
    }
    
    /** 删除模特 */
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
        if(!$this->_models_mod->where($where)->delete()){
            $this->error('模特删除失败.');
            return;
        }
        $this->success('模特删除成功.',U('index'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='models';
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
    
    /** 读取菜谱对应的关联类别，返回逗号分隔的ids字符串 */
    protected function getModelsCateids($models_id){
        if(empty($models_id)){
            return false;
        }
        $where=array(
            'models_id' => $models_id
        );
        $list=M('modelscate_models')->where($where)->field('cate_id')->select();
        $arr= array();
        foreach ($list as $key =>$val){
            $arr[$key] =  $val['cate_id'] ;
        }
        $arr = join(',', $arr );
        return $arr;
    } 
    

    /** 处理菜谱类别对应的关联菜谱 */
    protected function deal_modelscate_models($cateStr,$models_id){   #dump($cateStr); dump($models_id); 
        if(empty($cateStr) or empty($models_id)) return ;
        $_mod=M('modelscate_models');
        $ids=explode(',',$cateStr);
        //$_mod->where(array('models_id'=>$models_id))->delete();
        
        foreach ($ids as $key => $val){
            $where=array(
                'cate_id'=>$val,
                'models_id' => $models_id,
            );
            $find=$_mod->where($where)->find();  
            if(!$find){
                $res=$_mod->add(array('cate_id'=>$val,'models_id'=>$models_id));    #echo M()->getLastsql(); exit;
                if(!res){
                    M()->rollback();
                    $this->error('菜谱关联失败.');
                }
            }
        }
        $wheredel=array(
            'models_id'=>$models_id,
            'cate_id'=>array('not in',$cateStr)
        );
        $_mod->where($wheredel)->delete();
    } 
    
}


?>