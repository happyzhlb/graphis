<?php
/**
 * 文章模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class ArticleModel extends Model{
    protected $_validate = array(
        array('title','require','文章标题不能为空',0),
        array('cate_id','empty','文章所属专题不能为空',1,'function'),
        //array('content','require','文章内容不能为空',0),
    );
    
    //获取指定分类下的文章列表
    function getArticleList($CateId,$limit=20,$if_show = 0){
        if(!$CateId) return false;
        $where['cate_id'] = $CateId;
        if($if_show)
            $where['if_show'] = 1;
        $list = $this->field('article_id,title,cate_id,ctime,link_url')->where($where)->order('sort_order desc,article_id desc')->limit($limit)->select(); 
        return $list; 
    }
}


?>