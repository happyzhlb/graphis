<?php
/**
 * 地区控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Admin\Controller;
class RegionController extends BackendController{
    var $_region_mod = null;
    function __construct(){
        parent::__construct();
        $this->RegionController();
    }
    function RegionController(){
        $this->_region_mod = D('Region');
    }
    
    /** 地区列表 */
    function index(){
        $region = $this->_region_mod->_get_region(0,true);
        //检测是否下级的地区
        if(is_array($region)){
            foreach($region as $key => $value){
                $region[$key]['has_child'] = $this->_region_mod->where(array('parent_id'=>$value['region_id']))->select();
            }
        }
        $this->assign('region',$region);
        $this->display('./region.index');   
    }
    
    /** 添加地区 */
    function add(){
        if(!IS_POST){
            $region = $this->_region_mod->_get_region(0,true);
            $this->assign('region',$region);
            $this->display('./region.form');
        }else{
            $data = array(
                'region_name' => I('region_name','','trim'),
                'parent_id' => I('parent_id','','intval')
            );
            if(!$data['parent_id']){
                $data['region_type'] = 0;
            }else{
                $region_type  = current($this->_region_mod->field('region_type')->find($data['parent_id']));
                $data['region_type'] = $region_type + 1;
            }
            if(!$this->_region_mod->create($data)){
                $this->error($this->_region_mod->getError());
                return;
            }
            $this->_region_mod->add();
            $this->success('地区添加成功',U('/Admin/Region'));
        }
    }
    
    /** 编辑地区 */
    function edit(){
        $region_id = I('id','','intval');
        $region_info = $this->_region_mod->find($region_id);
        if(!$region_info){
            $this->error('地区不存在');
            return;
        }
        if(!IS_POST){
            $region = $this->_region_mod->_get_region(0,true);
            $this->assign('region',$region);
            $this->assign('region_info',$region_info);
            $this->display('./region.edit');
        }else{
            $data = array(
                'region_id' => I('id','','intval'),
                'region_code' => I('region_code','','trim'),
            	'region_name' => I('region_name','','trim'),
                'parent_id' => I('parent_id','','intval'),
            );
            if(!$data['parent_id']){
                $data['region_type'] = 0;
            }else{
                $region_type  = current($this->_region_mod->field('region_type')->find($data['parent_id']));
                $data['region_type'] = $region_type + 1;
            }
            if(!$this->_region_mod->create($data)){
                $this->error($this->_region_mod->getError());
                return;
            }
            $this->_region_mod->save();
            $this->success('地区编辑成功',U('/Admin/Region'));
        }
    }
    
    /** 删除地区 */
    function drop(){
        $region_id = I('id','','trim');
        if(!$region_id){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($region_id,','))
            $region_id = explode(',',$region_id);
        if(is_array($region_id)){
            $where['region_id'] = array('in',$region_id);
        }else{
            $where['region_id'] = $region_id;
        }
        $region = $this->_region_mod->where($where)->select();
        if(!$region){
            $this->error('删除的地区不存在');
            return;
        }
        $this->_region_mod->startTrans();
        foreach($region as $key => $value){
            if(!$this->_drop_region($value['region_id'])){
                $this->_region_mod->rollback();
                $this->error('删除下级地区失败');
                return;
            }
        }
        if(!$this->_region_mod->where($where)->delete()){
            $this->_region_mod->rollback();
            $this->error('地区删除失败');
            return;
        }
        $this->_region_mod->commit();
        $this->success('地区删除成功',U('/Admin/Region'));
    }
    
    /** 删除地区及所有子地区 */
    private function _drop_region($id){
        $where['parent_id'] = $id;
        $region = $this->_region_mod->where($where)->select();
        if(empty($region)) //没有子分类，直接返回TREU
            return true;
        foreach($region as $key => $list){
            $this->_region_mod->delete($list['region_id']);
            if(!$this->_drop_region($list['region_id'])) //字分类删除失败
                return false;
        }
        return true;
    }
}


?>