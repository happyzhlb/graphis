<?php
/**
 * 广告控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class AdController extends BackendController{
    var $_adplace_mod = null;
    var $_ad_mod = null;
    function __construct(){
        parent::__construct();
        $this->AdController();
    }
    function AdController(){
        $this->_adplace_mod = D('Adplace');
        $this->_ad_mod = D('Ad');
    }
    
    /** 广告列表 */
    function index(){
        if($_GET['keyword'])
            $where['ad.title'] = array('like','%'.trim(I('keyword')).'%'); 
		$pid = I('pid','','intval');
        if($_GET['pid'])
            $where['ad.pid'] = $pid; 
        $block=I('block');
        if(!empty($block)){
        	$where['adp.block']=$block;
        }
        $count = $this->_ad_mod->join('as ad left join __ADPLACE__ as adp on ad.pid = adp.pid ')
        		->field('ad.*,adp.pname,adp.block')->where($where)->count();
        $page = new \Think\Page($count,20);
        $page->parameter=array('block'=>$block,'pid'=>$pid,'keywords'=>$keywords);
        $ads = $this->_ad_mod->join('as ad left join __ADPLACE__ as adp on ad.pid = adp.pid ')
        		->field('ad.*,adp.pname,adp.block')
		        ->where($where)
		        ->order('ad.sort_order,ad.ad_id DESC')
		        ->limit($page->firstRow.','.$page->listRows)
        ->select();
        #echo M()->getLastsql();
        $adplace = $this->_adplace_mod->order('pid DESC')->select();
        $this->assign('adplace',$adplace); 
        $this->assign('ads',$ads);
        
        $this->assign('block',$this->block()); 
        //广告类型
        $this->assign('adType',$this->_ad_mod->getAdType());
        $this->assign('page',$page->show());
        $this->display('./ad.index');
    }
    
    /** 添加广告 */
    function add(){
        if(!IS_POST){
            //读取广告位
            $adplace = $this->_adplace_mod->order('pid DESC')->select();
            $this->assign('adplace',$adplace);
            $this->assign('adType',$this->_ad_mod->getAdType());
            $this->display('./ad.form');
        }else{
            $data = array(
                'pid' => I('pid','','intval'),
                'title' => trim(I('title')),
                'url' => trim(I('url')),
                'img' => '',
            	'type'=> I('type'),
            	'referId' => trim(I('referId')),
                'status' => I('status','','intval'),
                'clicks' => 0,
            	'sort_order' => I('sort_order','','intval')
            );
            $checkArticle = M('article')->find($data['referId']);
            if(!$checkArticle){
            //	$this->error('文章ID不存在.');
            }
            //处理图片
            if($_FILES['img']['size'] > 0){ //有上传图片
                //根据广告位设置的with*height生成缩略图
                $adplace = $this->_adplace_mod->find($data['pid']);
                if(!$adplace){
                    $this->error('广告位不存在或已经被删除，广告添加失败');
                    return;
                }
                $upload = new \Think\Upload(array(
                    'maxSize' => '512000', //最大支持上传500K的图片
                    'exts' => 'jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'ads/'
                ));
                //上传图片
                $file = $upload->upload($_FILES);
                if(!$file){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['img']['savepath'].$file['img']['savename'];
                
                //生成缩略图
                $image = new \Think\Image();
                $image->open($filename);
                $image_size = $image->size(); 
                if($image_size[0] != $adplace['adwith'] || $image_size[1] != $adplace['adheight']){
                    $image->thumb($adplace['adwith'],$adplace['adheight'],2)->save($filename);    
                }  
                //生成缩略图153x74
                $pathinfo =  pathinfo($filename);  
                $thumbfile = rtrim($pathinfo['dirname'].'/'.$pathinfo['basename'],'.'.$pathinfo['extension']).'(153x74).'.$pathinfo['extension'];
                $image->thumb(153,74,2)->save($thumbfile);
                $data['thumb'] = $thumbfile;
                $data['img'] = $filename;
            }
            if(!$this->_ad_mod->create($data)){
                @unlink($filename);
                $this->error($this->_ad_mod->getError());
                return;
            }
            $this->_ad_mod->add();
            $this->success('广告添加成功',U('index'));
        }
    }
    
    /** 编辑广告 */
    function edit(){
        $ad_id = I('id','','intval');
        $ad = $this->_ad_mod->find($ad_id);   #dump($ad);
        if(!$ad){
            $this->error('广告不存在或已经被删除');
            return;
        }
        if(!IS_POST){
            //读取广告位
            $adplace = $this->_adplace_mod->order('pid DESC')->select();
            $this->assign('adplace',$adplace);
            $this->assign('ad',$ad);
            $this->assign('adType',$this->_ad_mod->getAdType());
            $this->display('./ad.edit');
        }else{
            $data = array(
                'ad_id' => $ad_id,
                'pid' => I('pid','','intval'),
                'title' => trim(I('title')),
                'url' => trim(I('url')),
            	'type'=> I('type'),
            	'referId' => trim(I('referId')),
                'status' => I('status','','intval'),
            	'sort_order' => I('sort_order','','intval')
            );
           $checkArticle = M('article')->find($data['referId']);
            if(!$checkArticle){
            //	$this->error('文章ID不存在.');
            }
            //是否有上传图片
            if($_FILES['img']['size'] > 0){
                //根据广告位设置的with*height生成缩略图
                $adplace = $this->_adplace_mod->find($data['pid']);
                if(!$adplace){
                    $this->error('广告位不存在或已经被删除，广告添加失败');
                    return;
                }
                $upload = new \Think\Upload(array(
                    'maxSize' => '512000', //最大支持上传500K的图片
                    'exts' => 'jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'ads/'
                ));
                //上传图片
                $file = $upload->upload($_FILES);
                if(!$file){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['img']['savepath'].$file['img']['savename'];
                #dump($file); exit;
                //生成缩略图
                $image = new \Think\Image();
                $image->open($filename);
                $image_size = $image->size(); 
                if($image_size[0] != $adplace['adwith'] || $image_size[1] != $adplace['adheight']){
                    $image->thumb($adplace['adwith'],$adplace['adheight'],2)->save($filename);    
                }  
                //生成缩略图153x74
                $pathinfo =  pathinfo($filename);  
                $thumbfile = rtrim($pathinfo['dirname'].'/'.$pathinfo['basename'],'.'.$pathinfo['extension']).'(153x74).'.$pathinfo['extension'];
                $image->thumb(153,74,2)->save($thumbfile);
                $data['thumb'] = $thumbfile;
                $data['img'] = $filename;
            }
            if(!$this->_ad_mod->create($data)){
                @unlink($filename);
                $this->error($this->_ad_mod->getError());
                return;
            }
            $this->_ad_mod->save();
            if($filename){
                @unlink($ad['img']);   
            }
            $this->success('广告编辑成功',U('index'));
        }
    }
    
    /** 删除广告 */
    function drop(){
        $ad_id = trim(I('id'));
        if(!$ad_id){
            $this->error('传入的ID有误，不能删除');
            return;
        }
        if(strpos($ad_id,','))
            $ad_id = explode(',',$ad_id);
        if(is_array($ad_id)){
            $where['ad_id'] = array('in',$ad_id);
        }else{
            $where['ad_id'] = $ad_id;
        }
        
        $ads = $this->_ad_mod->where($where)->select();
        if(!$ads){
            $this->error('广告不存在或已经被删除');
            return;
        }
        if(!$this->_ad_mod->where($where)->delete()){
            $this->error('广告删除失败');
            return;
        }
        //删除图片
        foreach($ads as $key => $vo){
            @unlink($vo['img']);
        }
        $this->success('广告删除成功',U('index'));
    }
    
    /** 编辑广告开启状态 */
    function editstatus(){
        $ad_id = I('id','','intval');
        $ad = $this->_ad_mod->field('ad_id,status')->find($ad_id);
        if(!$ad){
            $this->error('广告不存在或被删除');
            return;
        }
        if($ad['status']){
            $ad['status'] = 0;
        }else{
            $ad['status'] = 1;
        }
        if(!$this->_ad_mod->save($ad)){
            $this->error('广告状态编辑失败');
            return;
        }
        $this->success('状态编辑成功.',U('index'));
    }
    
    /** ajax保存排序 */
    function ajaxSortOrder(){
    	$_mod=$this->_ad_mod;
    	$ad_id=I('ad_id');  
    	if(empty($ad_id)) $this->error('ID传值错误.');
    	$where=array(
    		'ad_id'=>$ad_id 
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
}


?>