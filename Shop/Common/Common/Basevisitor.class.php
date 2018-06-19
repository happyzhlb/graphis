<?php
/**
 * 访问控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Common\Common;
class Basevisitor{
    var $has_login = false;
    var $info = null;
    var $_info_key = '';
    var $value = '';
    var $model_name = 'User';
    function __construct(){
        $this->Basevisitor();
    }
    
    function Basevisitor(){   
        if(!session_id()){
             session('[start]');
        }
        session(array('name'=>'session_id','expire'=>3600*24,'prefix'=>$this->_info_key)); //session默认有效时间是15分钟
        if(session('?user_id')){
           $this->info = $_SESSION[$this->_info_key];
           $this->has_login = true; 
        }else{
            $this->info = array(
                'user_id' => 0,
                'user_name' => '游客'
            );
            $this->has_login = false;
        }
    }
    
    /** 分派身份 */
    function assign($user_info){
        foreach($user_info as $key => $value){
            session($key,$value);
        }
    }
    
    /** 获取当前登录用户的详细信息 */
    function get_detail()
    {
        /* 未登录，则无详细信息 */
        if (!$this->has_login)
        {
            return array();
        }

        /* 取出详细信息 */
        static $detail = null;

        if ($detail === null)
        {
            $detail = $this->_get_detail();
        }

        return $detail;
    }
    
    /** 获取用户详细信息 */
    function _get_detail()
    {
        $model_member = m($this->model_name);

        /* 获取当前用户的详细信息 */
        $member_info =$model_member->find(session('user_id'));
        return $member_info; 
        /*
        $member_info = $model_member->findAll(array(
            'conditions'    => "member.user_id = '{$this->info['user_id']}'",
            'join'          => 'has_store',                 //关联查找看看是否有店铺
            'fields'        => 'user_id, email, password, user_name, logins, code, company_name, store_id, state, sgrade , feed_config',
            'include'       => array(                       //找出所有该用户管理的店铺
                'manage_store'  =>  array(
                    'fields'    =>  'user_priv.privs, store.store_name',
                ),
            ),
        ));
        $detail = current($member_info);
		
        /* 如果拥有店铺，则默认管理的店铺为自己的店铺，否则需要用户自行指定 * /
        if ($detail['store_id'] && $detail['state'] != STORE_APPLYING) // 排除申请中的店铺
        {
            $detail['manage_store'] = $detail['has_store'] = $detail['store_id'];
        }
        return $detail;
		*/       
    }
    
    /** 获取当前用户的指定信息 */
    function get($key = null)
    {
        $info = null;

        if (empty($key))
        {
            /* 未指定key，则返回当前用户的所有信息：基础信息＋详细信息 */
            $info = array_merge((array)$this->info, (array)$this->get_detail());
        }
        else
        {
            /* 指定了key，则返回指定的信息 */
            if (isset($this->info[$key]))
            {
                /* 优先查找基础数据 */
                $info = $this->info[$key];
            }
            else
            {
                /* 若基础数据中没有，则查询详细数据 */
                $detail = $this->get_detail();
                $info = isset($detail[$key]) ? $detail[$key] : null;
            }
        }

        return $info;
    }

    /** 登出 */
    function logout()
    {
        session(null);
    }
    
}


?>