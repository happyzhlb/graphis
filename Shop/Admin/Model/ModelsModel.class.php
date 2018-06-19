<?php
/**
 * 模特模型
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Model;
use Think\Model;
class ModelsModel extends Model{
    protected $_validate = array(
        array('title','require','模特标题不能为空',0),
        //array('content','require','模特内容不能为空',0)
    ); 
}


?>