<?php
/**
 * 产品价格模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class GoodsPriceModel extends Model{
    protected $_validate = array(
        array('goods_id','require','产品价格中产品ID不能为空',0),
        array('price','require','价格不能为空',0),
        array('price','currency','价格格式不正确',0)
    );
}


?>