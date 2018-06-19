<?php
/**
 * 图片模型
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Model;
use Think\Model;
class PictureModel extends Model{
    protected $_validate = array(
        array('title','require','图片标题不能为空',0),
        //array('content','require','图片内容不能为空',0)
    ); 
}


?>