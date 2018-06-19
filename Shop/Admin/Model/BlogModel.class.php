<?php
/**
 * 博客模型
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class BlogModel extends Model{
    protected $_validate = array(
        array('title','require','博客标题不能为空',0),
        array('content','require','博客内容不能为空',0)
    );
    
    //获取指定分类下的博客列表
    function getBlogList($CateId,$limit=20,$if_show = 0){
        if(!$CateId) return false;
        $where['cate_id'] = $CateId;
        if($if_show)
            $where['if_show'] = 1;
        $list = $this->field('bid,title,ctime')->where($where)->order('bid desc')->limit($limit)->select(); 
        return $list; 
    }
}


?>