<?php
/**
 * 订单明细模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class OrderGoodsModel extends Model{
    protected $_validate = array(
        array('refund_sn','require','退款单号不能为空',0),
        array('refund_sn','','退款单已经存在',0,'unique'),
        array('refund_price','require','退款金额不能为空',0),
        array('refund_num','require','退款数量不能为空',0),
        array('refund_num','number','退款数量必须为数字',0),
        array('refund_reason','require','退款原因不能为空',0),
        array('refund_shipping_name','require','Logistics company can not be empty.',0),
        array('refund_invoice_no','require','Tracking number can not be empty.',0)
    );   
}


?>