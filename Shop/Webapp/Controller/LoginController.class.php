<?php
/**
 * Webapp登录控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Controller;
class LoginController extends FrontendController{
 
    /** 用户登录 */
    function index(){ 
        $this->display('./index.login');
    } 
    
    /** 用户注册 */
    function register(){
    	$this->display('./register');
    }
    
    
} 

?>