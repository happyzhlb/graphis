<?php
/**
 * 广告位控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class AdplaceController extends BackendController{
    var $_adplace_mod = null;
    var $_ad_mod = null;
    function __construct(){
        parent::__construct();
        $this->AdplaceController();
    }
    
    function AdplaceController(){
        $this->_adplace_mod = D('Adplace');
        $this->_ad_mod = D('Ad'); 
    }
    
    /** 广告位列表 */
    function index(){ 
        if($_GET['keyword']){
            $where['pname'] = array('like','%'.trim(I('keyword')).'%');
        }
        $block=I('block');
        if(!empty($block)){
        	$where['block']=$block;
        }
        $count = $this->_adplace_mod->where($where)->count();
        $page = new \Think\Page($count,20);
        $adplace = $this->_adplace_mod->where($where)->order('pid DESC')
                   ->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('block',$this->block()); 
        $this->assign('adplace',$adplace);
        $this->assign('page',$page->show());
        $this->display('./adplace.index');    
    }
    
    /** 添加广告位 */
    function add(){
        if(!IS_POST){
            $file = getfile(APP_PATH.'Adview/');
            $this->assign('file',$file);
            $this->assign('block',$this->block());
            $this->display('./adplace.form');
        }else{
            $data = array(
                'pname' => trim(I('pname')),
                'adwith' => I('adwith','','doubleval'),
                'adheight' => I('adheight','','doubleval'),
                'ad_num' => I('ad_num','','intval'),
                'tpl' => I('tpl'),
            	'block' => I('block','','intval')
            );
            if(!$this->_adplace_mod->create($data)){
                $this->error($this->_adplace_mod->getError());
                return;
            }
            $this->_adplace_mod->add();
            $this->success('广告位添加成功',U('/Admin/Adplace'));      
        }
    }
    
    /** 编辑广告位 */
    function edit(){
        $pid = I('id','','intval');
        $adplace = $this->_adplace_mod->find($pid);
        if(!$adplace){
            $this->error('广告位不存在，或已经被删除');
            return;
        }
        if(!IS_POST){
            $file = getfile(APP_PATH.'Adview/');
            $this->assign('file',$file);
            $this->assign('adplace',$adplace);
            $this->assign('block',$this->block()); 
            $this->display('./adplace.edit');
        }else{
            $data = array(
                'pid' => $pid,
                'pname' => trim(I('pname')),
                'adwith' => I('adwith','','doubleval'),
                'adheight' => I('adheight','','doubleval'),
                'ad_num' => I('ad_num','','intval'),
                'tpl' => I('tpl'),
            	'block' => I('block','','intval'),
            );
            if(!$this->_adplace_mod->create($data)){
                $this->error($this->_adplace_mod->getError());
                return;
            }
            $this->_adplace_mod->save();
            $this->success('广告位编辑成功',U('/Admin/Adplace'));
        }
        
    }
    
    /** 删除广告位 */
    function drop(){
        $pid = trim(I('id'));
        if(!$pid){
            $this->error('传入的ID有误，不能删除');
            return;
        }
        if(strpos($pid,','))
            $pid = explode(',',$pid);
        if(is_array($pid)){
            $where['pid'] = array('in',$pid); 
        }else{
            $where['pid'] = $pid;
        }
        
        $adplace = $this->_adplace_mod->where($where)->select();
        if(!$adplace){
            $this->error('广告位不存在或者已经被删除');
            return;
        }
        D('')->startTrans();
        if(!$this->_adplace_mod->where($where)->delete()){
            D('')->rollback();
            $this->error('删除广告位失败');
            return;
        }
        //删除广告位以及该广告位下的所有广告
        $ad = $this->_ad_mod->where($where)->select();
        if($ad != false){
            if(!$this->_ad_mod->where($where)->delete()){
                D('')->rollback();
                $this->error('删除广告位下的广告失败');
                return;
            }
        }
        D('')->commit();
        $this->success('广告位删除成功',U('/Admin/Adplace'));
    }

    
}


?>