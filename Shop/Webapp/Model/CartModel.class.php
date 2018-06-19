<?php
/**
 * 购物车模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Model;
use Think\Model;
class CartModel extends Model{
    protected $_validate = array(
        array('quantity','require','Quantity is required.',0),
        array('quantity','number','Quantity must be numbers.',0)
    );
}


?>