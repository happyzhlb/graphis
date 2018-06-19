<?php
/**
 * 食谱菜谱模型
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Model;
use Think\Model;
class RecipeModel extends Model{
    protected $_validate = array(
        array('title','require','百科标题不能为空',0),
        array('content','require','百科内容不能为空',0)
    );
     
    //获取指定分类下的百科列表
    function getRecipeList2222($CateId,$limit=20,$if_show = 0){
        if(!$CateId) return false;
        $where['cate_id'] = $CateId;
        if($if_show)
            $where['if_show'] = 1;
        $list = $this->field('id,title')->where($where)->order('id desc')->limit($limit)->select(); 
        return $list; 
    }
}


?>