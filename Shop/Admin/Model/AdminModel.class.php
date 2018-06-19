<?php
/**
 * 管理员模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class AdminModel extends Model{
    protected $_validate = array(
        array('user_name','require','用户名不能为空',0),
        array('user_name','','用户名已经存在',0,'unique'),
        array('psw','require','密码不能为空',0),
        array('repsw','psw','两次输入的密码不一致',0,'confirm') 
    );
}


?>