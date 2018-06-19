<?php
/**
 * 场景分类模型
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Model;
use Think\Model;
class ScenecategoryModel extends Model{
    protected $_validate = array(
        array('cate_name','require','分类名不能为空',0),
        //array('cate_name','','分类已存在',0,'unique'),
        array('parent_id','number','父类ID必须为数字',0),
        array('if_show','number','是否显示必须为数字',0),
        array('sort_order','number','排序必须为数字',0)
    ); 
    
    //获取分类及下级所有子分类  ;  $have_children 为false时返回当前类
    function get_category($cate_id = 0, $have_children = false, $if_show = 0){
        if(!$cate_id){ //0 获取所有父级主分类
            $where['parent_id'] = 0;
        }else{
            $where['cate_id'] = $cate_id;
        }
        if($if_show)
            $where['if_show'] = 1;
        $list = $this->where($where)->order('sort_order desc,cate_id ASC')->select(); 
        if(empty($list)) return false;
        if($have_children){//获取搜有子类
            foreach($list as $key => $cate){
                $list[$key]['children'] = $this->get_children_category($cate['cate_id'],$if_show);    
            }
            return $list;
        }else{ //返回当前类
            if(isset($where['cate_id'])){ 
                return current($list);  //返回当前cate_id类
            }else{
                return $list;   //返回当前cate_id类
            }
        }
        
    }
    //获取指定分类下的子分类
    function get_children_category($parent_id, $if_show = 0){
        if(!$parent_id) return false;
        $where['parent_id'] = $parent_id;
        if($if_show)
            $where['if_show'] = 1;
        $list = $this->where($where)->order('sort_order desc,cate_id ASC')->select();
        if(empty($list)){
        	return false;
        }else{
	        foreach($list as $key => $cate){
	            $list[$key]['children'] = $this->get_children_category($cate['cate_id'],$if_show);
	        }
        }
        return $list;
            
    }

    //获取分类下所有子分类的ID
    function _get_children_cate_id(&$cate_ids, $cate_id){
        if(empty($cate_ids)){
            $cate_ids[] = $cate_id;
            $this->_get_children_cate_id($cate_ids, $cate_id); 
        }else{
            $where['parent_id'] = $cate_id;
            $list = $this->field('cate_id')->where($where)->select();
            if(is_array($list)){
                foreach($list as $key => $value){
                    $cate_ids[] = $value['cate_id'];
                    $this->_get_children_cate_id($cate_ids,$value['cate_id']);
                }
            }
        }   
    }
}
?>