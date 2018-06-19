<?php
/**
 * 专辑模型
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Model;
use Think\Model;
class AlbumModel extends Model{
    protected $_validate = array(
        array('title','require','专辑标题不能为空',0),
    	array('models_id','require','请选择模特',0),	
        //array('content','require','专辑内容不能为空',0)
    ); 
}


?>