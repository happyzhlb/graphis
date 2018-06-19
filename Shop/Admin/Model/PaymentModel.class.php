<?php
/**
 * 支付方式模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class PaymentModel extends Model{
    
    
    /** 支付方式白名单 */
    function get_enabled_payments(){
        $where['enabled'] = 1;
        $payments = $this->where($where)->select();
        return $payments;
    }
}


?>