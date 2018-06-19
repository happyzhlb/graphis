<?php
/**
 * 邮件模型
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class EmailsendlistModel extends Model{
    protected $_validate = array(
        array('email','require','Email地址不能为空',1),
        array('email','email','Email地址格式不正确',1),
        array('email','','Email地址已经存在',0,'unique')
    ); 
}


?>