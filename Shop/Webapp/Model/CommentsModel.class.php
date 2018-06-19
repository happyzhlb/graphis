<?php
/**
 * 产品评论模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Model;
use Think\Model;
class CommentsModel extends Model{
    protected $_validate = array(
        array('user_id','require','User ID is required.',0),
        array('user_name','require','User name is required.',0),
        array('email','require','Email address is required.',0),
        array('email','email','Email address is not valid.',0),
        array('comment_stars','require','Rate is required.',0),
        array('content','require','Review comment is required.',0)
    );
    
}


?>