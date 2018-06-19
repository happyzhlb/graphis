<?php
/**
 * 协商记录模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class RefundModel extends Model{
    protected $_validate = array(
        array('refund_sn','require','The No. of refund request in communication record can not be blank.',0),
        array('refund_user','require','The operator of communication record can not be blank.',0),
        array('refund_status','require','The status of refund request in communication record can not be blank.',0),
    );
    
    //读取退款单下的退款协商明细
    function _get_refunds($refund_sn){  #dump($refund_sn);
        $where['refund_sn'] = $refund_sn;
        $refunds = $this->where($where)->order('refund_id ASC')->select();
        if(is_array($refunds)){
            foreach($refunds as $key => $value){
                $refunds[$key]['refund_data'] = unserialize($value['refund_data']);
                $refund_user = explode('|',$value['refund_user']);
                $refunds[$key]['role'] = $refund_user[0];
                $refunds[$key]['user'] = $refund_user[1];
            }
        }
        return $refunds;
    }
}


?>