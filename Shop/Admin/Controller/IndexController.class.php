<?php
/**
 * 程序默认控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class IndexController extends BackendController {
    public function index(){ 
	   	$this->display('/index');
    }
}