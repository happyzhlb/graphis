<?php
/**
 * 地区模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class AuthRuleModel extends Model{
    protected $_validate = array(
        array('title','require','权限规则标题不能为空',0),
        array('name','','权限规则节点名称已经存在',1,'unique'),
        array('status','require','状态不能为空',0), 
    ); 
}


?>