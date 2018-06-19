<?php
/**
 * Webapp我的专辑
 * @author Abiao
 * @copyright 2018
 */
namespace Webapp\Controller; 

use Think\Controller;
use Home\Controller\MemberController;
class MyalbumController extends MemberController{
	var $_myalbum_mod=null; 
    function __construct(){
        parent::__construct();
        $this->MyalbumController();
    }
    
    function MyalbumController(){
        $this->_myalbum_mod=D('myalbum'); 
    }
 
    /** 我的专辑首页 */
    function index(){ 
        #$user_id=$this->visitor->get('user_id');
        $list = $this->_myalbum_mod->alias('my')->join('__ALBUM__ a on a.id = my.album_id')
                ->field('a.*,my.code')
                ->where('user_id='.$this->user_id)->select();  
        $this->assign('list',$list);
        $this->display('./myalbum.index');
    }
     
    
 }
    
    
    ?>