<?php
/**
 * 孕期进展控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class GestateController extends BackendController{
    var $_gestate_mod = null;
    function __construct(){
        parent::__construct();
        $this->GestateController();
    }
    function GestateController(){
        $this->_gestate_mod = D('Gestate');
    }
    
    /** 孕期进展列表 */
    function index(){  
        
        $_gestatecate_mod = D('Gestcate');
            
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
            $_gestatecate_mod->_get_children_cate_id($cate_ids,$cate_id);
            $cate_ids=join(',',$cate_ids);
            $where['a.cate_id']=array('exp','in('.$cate_ids.')');
        } 
        $list = $_gestatecate_mod->get_category(0,true);
        $this->assign('gestatecate',$list);
        $count = $this->_gestate_mod
                 ->join(' as a LEFT JOIN __GESTCATE__ as c ON a.cate_id=c.cate_id')
                 ->where($where)->count();
        $page = new \Think\Page($count,10);
        $page->parameter=array( 
        	'keywords'=> $keywords, 
        	'p'=>$_GET['p'], 
            'URL_MODEL' => 0
        ); 
        
        $list = $this->_gestate_mod->field('a.*,c.cate_name')
                ->join(' as a LEFT JOIN __GESTCATE__ as c ON a.cate_id=c.cate_id')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('id DESC')->select();  
        if(!empty($list)){
            foreach ($list as $key => $val){
                $list[$key]['relateCateName']= $_gestatecate_mod->getRelateCateName($val['cate_id']);
                $list[$key]['sourceurl']= "http://www.mama.cn/qrqm/{$val['week']}/?day={$val['day']}";
            }
        }
        
        $this->assign('gestate',$list);  
        $this->assign('page',$page->show()); 
        $this->display('./gestate.index');
    }
    
    /** 添加孕期进展 */
    function add(){
        if(!IS_POST){
            $_gestatecate_mod = D('Gestcate');
            $list = $_gestatecate_mod->get_category(0,true);
            $this->assign('gestatecate',$list);
            $this->display('./gestate.form');
        }else{
            $data = array(
                'baby_age_show' => I('baby_age_show','','trim'),
                'pic' => I('pic'),
            	'tips_title' => I('tips_title','','trim'), 
                'tips_title2' => I('tips_title2'),
                'tips_des' => I('tips_des','','trim'),
                'tips_des2' => I('tips_des2','','trim'), 
            	'educate_des'=> I('educate_des','','trim'),
            	'educate_json' => I('educate_json','','trim'),
                'baby_json' => I('baby_json','','trim'),
            	'expect_baby_day' => I('expect_baby_day','','intval'),
                'ctime' => gmtime(), 
                'nutrition_pic' => I('nutrition_pic','','trim'),
                'nutrition_des' => I('nutrition_des','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
                'author' => I('author','','trim'),
            );
            if(!$this->_gestate_mod->create($data)){
                $this->error($this->_gestate_mod->getError());
                return;
            }
            
            $id = $this->_gestate_mod->add(); 
            if(empty($id)){
                M()->rollBack();
                $this->error('新增失败.');
            }
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_gestatecate_gestate($cate_ids, $id);
            M()->commit();
            
            $this->success('孕期进展添加成功',U('index'));
        }
    }
    
    /** 编辑孕期进展 */
    function edit(){
        $id = I('id','','intval');
        $gestate = $this->_gestate_mod->find($id);
        
        if(!$gestate){
            $this->error('孕期进展不存在');
            return;
        }
        
        
        if(!IS_POST){
            $_gestatecate_mod = D('Gestcate');
            $list = $_gestatecate_mod->get_category(0,true); 
            $this->assign('gestatecate',$list);
            
            $cate = $_gestatecate_mod->get_category( $gestate['cate_id']); 
            $cate_labels = explode(',', $cate['cate_label']);
            
            $gestate['cate_labels']=$cate_labels;
            
            //读取关联分类 
            $cate_ids=$this->getGestateCateids($id); 
            $this->assign('cate_ids',$cate_ids); 
            
            $this->assign('gestate',$gestate);
            $this->display('./gestate.edit');
            
        }else{
            $data = array(
                'id' => $id,
                'baby_age_show' => I('baby_age_show','','trim'),
                'pic' => I('pic'),
            	'tips_title' => I('tips_title','','trim'), 
                'tips_title2' => I('tips_title2'),
                'tips_des' => I('tips_des','','trim'),
                'tips_des2' => I('tips_des2','','trim'), 
            	'educate_des'=> I('educate_des','','trim'),
            	'educate_json' => I('educate_json','','trim'),
                'baby_json' => I('baby_json','','trim'),
            	'expect_baby_day' => I('expect_baby_day','','intval'),
                'ctime' => gmtime(), 
                'nutrition_pic' => I('nutrition_pic','','trim'),
                'nutrition_des' => I('nutrition_des','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
                'author' => I('author','','trim'),
            );
            
            if($data['educate_json'] && !json_decode($data['educate_json'],true)){
                $this->error('educate_json错误.');
            }
            if($data['baby_json'] && !json_decode($data['baby_json'],true)){
                $this->error('baby_json错误.');
            }
            
            $crt=$this->_gestate_mod->create($data); 
            if(!$crt){
                $this->error($this->_gestate_mod->getError());
                return;
            }
            
            M()->startTrans();
            
            //处理关联分类
            $cate_ids = I('cate_id','','trim');
            if(empty($cate_ids)){ $this->error('请选择分类。'); }
            $this->deal_gestatecate_gestate($cate_ids, $id);
             
            $res=$this->_gestate_mod->save($data);
            M()->commit();
            
            if(false!==$res)
            	$this->success('孕期进展编辑成功',U('index'));
            else 
            	$this->error('孕期进展编辑失败.');
        }
    }
    
    /** 异步修改孕期进展显示状态 */
    function ajax_edit_status(){
        $id = I('id','','intval');
        $gestate = $this->_gestate_mod->field('id,if_show')->find($id);
        if(!$gestate){
            $this->error('孕期进展不存在');
            return;
        }
        if($gestate['if_show']){
            $gestate['if_show'] = 0;
        }else{
            $gestate['if_show'] = 1;
        }
        if(!$this->_gestate_mod->save($gestate)){
            $this->error('修改孕期进展显示状态失败');
            return;
        }
        $this->success('修改孕期进展显示状态成功',U('index'));
    }
    
    /** 删除孕期进展 */
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
        if(!$this->_gestate_mod->where($where)->delete()){
            $this->error('孕期进展删除失败.');
            return;
        }
        $this->success('孕期进展删除成功.',U('index'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='gestate';
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
    protected function getGestateCateids($gestate_id){
        if(empty($gestate_id)){
            return false;
        }
        $where=array(
            'gestate_id' => $gestate_id
        );
        $list=M('gestatecate_gestate')->where($where)->field('cate_id')->select();
        $arr= array();
        foreach ($list as $key =>$val){
            $arr[$key] =  $val['cate_id'] ;
        }
        $arr = join(',', $arr );
        return $arr;
    } 
    

    /** 处理菜谱类别对应的关联菜谱 */
    protected function deal_gestatecate_gestate($cateStr,$gestate_id){   #dump($cateStr); dump($gestate_id); 
        if(empty($cateStr) or empty($gestate_id)) return ;
        $_mod=M('gestatecate_gestate');
        $ids=explode(',',$cateStr);
        //$_mod->where(array('gestate_id'=>$gestate_id))->delete();
        
        foreach ($ids as $key => $val){
            $where=array(
                'cate_id'=>$val,
                'gestate_id' => $gestate_id,
            );
            $find=$_mod->where($where)->find();  
            if(!$find){
                $res=$_mod->add(array('cate_id'=>$val,'gestate_id'=>$gestate_id));    #echo M()->getLastsql(); exit;
                if(!res){
                    M()->rollback();
                    $this->error('菜谱关联失败.');
                }
            }
        }
        $wheredel=array(
            'gestate_id'=>$gestate_id,
            'cate_id'=>array('not in',$cateStr)
        );
        $_mod->where($wheredel)->delete();
    } 
    
}


?>