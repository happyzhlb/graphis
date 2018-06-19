<?php
/**
 * 促销模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class PromotionModel extends Model{
    
    function getpromotion($user_id){
        $nowtime = gmtime();
        $map['_string'] = "(for_user = 'all') or (for_user like '%{$user_id}%')";
        $map['from_time'] = array('ELT', $nowtime);
        $map['to_time'] = array('EGT', $nowtime);
        $map['status'] = 1;
        $list = $this->where($map)->order('pro_id DESC')->select();
        return $list;
    }
}


?>