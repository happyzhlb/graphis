<?php
/**
 * 会员中心模型
 * @author Abiao
 * @copyright 2014
 */
namespace Webapp\Model;
use Think\Model;
class MemberModel extends Model{ 

	/** 检测用户名是否唯一 */
    function check_user_name($user_name, $user_id = 0){
        $where['user_name'] = $user_name;
        if($user_id)
            $where['user_id'] = array('neq',$user_id);
        $user = M('user')->where($where)->select();
        if(!$user){
            return false;
        }else{
            return true;
        }        
    }
    
    /** 检测email是否唯一 */
    function check_email($email, $user_id = 0){
        $where['email'] = $email;
        if($user_id)
            $where['user_id'] = array('neq',$user_id);
        $user = M('user')->where($where)->select();
        if(!$user){
            return false;
        }else{
            return true;
        }        
    }	
    
}


?>