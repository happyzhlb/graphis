<?php
/**
 * 产品信息模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class GoodsModel extends Model{
    protected $_validate = array(
        array('goods_name','require','产品名称不能为空',0),
        //array('goods_name','','产品名称已经存在',0,'unique'),
        array('outer_id','require','淘宝产品ID不能为空',0),
        //array('outer_id','','淘宝产品ID已经存在',0,'unique'),
        array('goods_img','require','产品主图不能为空',0), 
        array('market_price','require','市场价不能为空',0),
        //array('goods_desc','require','产品描述不能为空',0), 
    );
}


?>