<?php
/**
 * 会员模型
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Model;
use Think\Model;
class WikiuserModel extends Model{
    protected $_validate = array(
        array('user_name','require','用户名不能为空',0),
        array('user_name','','用户名已经被人注册.',0,'unique'),
//        array('nick_name','require','昵称不能为空',0),
        array('password','require','用户密码不能为空',0),
//       array('repassword','password','两次输入的密码不一致',0,'confirm'),
//        array('email','require','Email地址不能为空',0),
//        array('email','email','Email地址格式不正确',0),
//        array('email','','Email地址已经存在',0,'unique')
    );
    
    /** 检测用户名是否唯一 */
    function check_user_name($user_name, $user_id = 0){
        $where['user_name'] = $user_name;
        if($user_id)
            $where['user_id'] = array('neq',$user_id);
        $user = $this->where($where)->select();
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
        $user = $this->where($where)->select();
        if(!$user){
            return false;
        }else{
            return true;
        }        
    }
}


?>