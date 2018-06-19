<?php
/**
 * 订单操作日志模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class OrderLogModel extends Model{
    
    /** 根据订单ID获取所有订单的操作日志 */
    function _get_logs($order_id){
        $ordder_logs = $this->where("order_id = {$order_id}")
                       ->order('log_time ASC')->select();
        return $ordder_logs;
    }    
}


?>