<?php
/**
 * 产品规格模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class GoodsSpecsModel extends Model{
    protected $_validate = array(
        array('goods_id','require','规格中的产品ID不能为空',0),
        array('spec_page','require','规格名称不能为空',0),
        array('spec_batch','require','规格批次不能为空',0),
        array('sku','require','规格的库存不能为空',0),
        array('sku','number','库存必须为数字',0),
        array('unit','require','单位不能为空',0),
        array('weight','require','规格重量不能为空',0),
        array('price','require','规格的价格不能为空',0),
        array('price','currency','规格价格格式不正确',0)
    );
}


?>