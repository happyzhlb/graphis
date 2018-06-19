<?php
/**
 * 地区模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class RegionModel extends Model{
    protected $_validate = array(
        array('region_name','require','地区名称不能为空',0),
        array('region_name','','地区名称已经存在',0,'unique'),
        array('region_code','require','地区代码不能为空',0),
        array('region_code','','地区代码已经存在',0,'unique'),
    );
    
    /** */
    function _get_region($region_id = 0, $is_all = false){
        if($is_all){
            $where['parent_id'] = $region_id;
            return $this->where($where)->select();
        }else{
            $where['region_id'] = $region_id;
            return $this->where($where)->find();
        }
    }
}


?>