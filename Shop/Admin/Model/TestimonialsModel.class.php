<?php
/**
 * 网站评论控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class TestimonialsModel extends Model{
    protected $_validate = array(
        array('title','require','Title is required.',0),
        array('name','require','Name is required.',0),
        array('email','require','Email address is required.',0),
        array('email','email','Email address should be valid.',0),
        array('location','require','Location is required.',0),
        array('content','require','Content is required.',0)
    );   
}


?>