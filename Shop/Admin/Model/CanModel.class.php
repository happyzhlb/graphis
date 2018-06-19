<?php
/**
 * Can模型
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Model;
use Think\Model;
class CanModel extends Model{
    protected $_validate = array(
        array('title','require','Can标题不能为空',0),
        array('content','require','Can内容不能为空',0)
    );
    
    //获取指定分类下的Can列表
    function getCanList($CateId,$limit=20,$if_show = 0){
        if(!$CateId) return false;
        $where['cate_id'] = $CateId;
        if($if_show)
            $where['if_show'] = 1;
        $list = $this->field('can_id,title,cate_id,ctime,link_url')->where($where)->order('sort_order desc,can_id desc')->limit($limit)->select(); 
        return $list; 
    }
}


?>