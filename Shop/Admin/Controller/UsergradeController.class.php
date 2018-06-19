<?php
/**
 * 用户等级管理控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class UsergradeController extends BackendController{
    var $_usergrade_mod = null;
    var $upload_config = array(); //图片上传配置
    var $thumb_w=225;
    var $thumb_h=80;
    function __construct(){
        parent::__construct();
        $this->UsergradeController();
    }
    function UsergradeController(){
        $this->_usergrade_mod = D('UserGrade');
        $this->upload_config = array( //图片上传设置
            'maxSize' => '400000',
            'exts' => 'gif,jpg,jpeg,png',
            'subName' => '',
            'savePath' => 'Usergrade/'
        );
    }
    
    /** 用户等级列表 */
    function index(){
        if(isset($_GET['grade_name']) && $_GET['grade_name']){
            $where['grade_name'] = array('like','%'.I('grade_name','','trim').'%');
        }
        $count = $this->_usergrade_mod->where($where)->count();
        $page = $page = new \Think\Page($count,20);
        $usergrades = $this->_usergrade_mod->where($where)->order('level_id ASC')->limit($page->firstRow.','.$page->listRows)
                  ->select();  
        $this->assign('usergrades',$usergrades); 
        $this->assign('page',$page->show());
        $this->display('./usergrade.index');   
    }
    
    /** 添加用户等级 */
    function add(){
        if(!IS_POST){
            $this->display('./usergrade.form');
        }else{
            $data = array(
                'grade_name' => I('grade_name','','trim'),
                'logo' => $_FIname['logo']['name'],
            	'score' => I('score','','trim'),
                'level_id' => I('level_id'), 
            );
            if(!$this->_usergrade_mod->create($data)){
                $this->error($this->_usergrade_mod->getError());
                return;
            }
            if($_FILES['logo']['name']){
	            $upload = new \Think\Upload($this->upload_config);
	            if(!$file = $upload->upload($_FILES)){
	                $this->error($upload->getError());
	                return;
	            }
	            $filename = $upload->__get('rootPath').$file['logo']['savepath'].$file['logo']['savename'];
	            if(is_file($filename)){ //文件上传成功，生成缩略图
	                $image = new \Think\Image();
	                $image->open($filename);
	                $image->thumb($this->thumb_w,$this->thumb_h,2)->save($filename); //
	            }else{
	                $this->error('用户等级LOGO上传失败');
	                return;
	            }
	            $data['logo'] = $filename;
	         }    
            if(!$this->_usergrade_mod->add($data)){
                $this->error('用户等级添加失败');
                return;
            } 
            S('Usergrades',null); //清空缓存
            $this->success('用户等级添加成功',U('/Admin/Usergrade'));
        }
    }
    
    /** 异步修改用户等级是否显示 */
    function ajax_edit(){
        $grade_id = I('id','','intval');
        $Usergrade = $this->_usergrade_mod->field('grade_id,level_id')->find($grade_id);
        if(!$Usergrade){
            $this->error('用户等级不存在','',IS_AJAX);
            return;
        }
        $data = array(
            'grade_id'=>$grade_id,
            'level_id' => $Usergrade['level_id']? 0 : 1
        );
        if(!$this->_usergrade_mod->create($data)){
            $this->error($this->_usergrade_mod->getError(),'',IS_AJAX);
            return;
        }
        if(!$this->_usergrade_mod->save($data)){
            $this->error('状态修改失败','',IS_AJAX);
            return;
        }
        S('Usergrades',null);
        $this->success('状态修改成功',U('/Admin/Usergrade'),IS_AJAX);
    }
    
    /** 删除用户等级 */
    function drop(){
        $grade_id = I('id','','trim');
        if(!$grade_id){
            $this->error('ID为空，删除失败');
            return;
        }
        if(strpos($grade_id,','))
            $grade_id = explode(',',$grade_id);
        if(is_array($grade_id)){
            $where['grade_id'] = array('in',$grade_id);
        }else{
            $where['grade_id'] = intval($grade_id);
        }
        //处理图片
        $Usergrades = $this->_usergrade_mod->field('grade_id,logo')->where($where)->select();
        if(!empty($Usergrades)){
            foreach($Usergrades as $key => $value){
                unlink($value['logo']);
            }
        } 
        if(!$this->_usergrade_mod->where($where)->delete()){
            $this->error('用户等级删除失败');
            return;
        }
        S('Usergrades',null);
        $this->success('用户等级删除成功',U('/Admin/Usergrade'));
    }
    
    /** 编辑商品 */
    function edit(){
        $grade_id = I('id','','intval');
        $usergrade = $this->_usergrade_mod->find($grade_id);
        if(empty($usergrade)){
            $this->error('用户等级不存在');
            return;
        }
        if(!IS_POST){
            $this->assign('usergrade',$usergrade);
            $this->display('./usergrade.edit');
        }else{
            $data = array(
                'grade_id' => $grade_id,
                'grade_name' => I('grade_name'),
            	'score' => I('score','','trim'),
                'level_id' => I('level_id'), 
            );
            if(!$this->_usergrade_mod->create($data)){
                $this->error($this->_usergrade_mod->getError());
                return;
            }
            if($_FILES['logo']['name']){ //重新上传用户等级logo
                $upload = new \Think\Upload($this->upload_config);
                if(!$file = $upload->upload($_FILES)){
                    $this->error($upload->getError());
                    return;
                }
                $filename = $upload->__get('rootPath').$file['logo']['savepath'].$file['logo']['savename'];
                if(file_exists_case($filename)){ //文件上传成功，生成缩略图
                    $image = new \Think\Image();
                    $image->open($filename);
                    $image->thumb($this->thumb_w,$this->thumb_h,2)->save($filename); //
                }else{
                    $this->error('用户等级LOGO上传失败');
                    return;
                }
                $data['logo'] = $filename;
            }
            if(false===$this->_usergrade_mod->save($data)){ 
                $this->error('编辑用户等级保存失败');
                return;
            }
            if(isset($data['logo']))
                //删除原来老的图片
                unlink($Usergrade['logo']);
            S('Usergrades',null);
            $this->success('用户等级编辑成功',U('/Admin/Usergrade'));
        }
    }
}


?>