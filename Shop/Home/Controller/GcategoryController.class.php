<?php
/**
 * 分类显示器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class GcategoryController extends FrontendController{
    var $_gcategory_mod = null;
    function __construct(){
        parent::__construct();
        $this->GcategoryController();
    }
    function GcategoryController(){
        $this->_gcategory_mod = D('Admin/Gcategory');
    }
    /** 全部分类 */
    function index(){
        //浏览记录
        $history = viewed_items();
        $this->assign('history',$history); 
        $this->display('./gcategory.index');
    }
    
    function error404(){ 
    	$this->display('./404');
    }
    
}


?>