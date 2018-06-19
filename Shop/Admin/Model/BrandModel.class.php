<?php
/**
 * 品牌模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class BrandModel extends Model{
    protected $_validate = array(
        array('bname','require','品牌名不能为空',0),
        array('bname','','品牌已存在',0,'unique'),
        array('blogo','require','品牌LOGO不能为空',0),
        array('if_show','number','是否显示必须为数字',0),
        array('sort_order','number','排序必须为数字',0)
    );
    
    //获取品牌列表
    function getBrandList($cate_id=null){
    	if(!empty($cate_id)){
    		$where = array('cate_id'=>$cate_id);
    	}
    	return $this->where($where)->field('brand_id,brand_name')->order('brand_name asc')->select();
    }
    
    
}


?>