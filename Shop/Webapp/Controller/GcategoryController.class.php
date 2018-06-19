<?php
/**
 * 分类显示器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Controller;
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
        //读取产品分类
        $cates = S('index_goods_category');
        if(empty($cates)){    
        	$_gcategory_mod = D('Admin/Gcategory');        
            $cates = $_gcategory_mod->get_category(0,true,1); 
            S('index_goods_category',$cates);   
        } 
        $_public['cates'] = $cates;
        $this->assign($_public);
        $this->display('./Product_Category');
    }
    
    function error404(){ 
    	$this->display('./404');
    }
    
}


?>