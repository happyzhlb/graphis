<?php
/**
 * 模特控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Home\Controller;
use Think\Page;

use Think\Controller;
class PayController extends FrontendController{
	var $_mod=null;  
    function __construct(){
        parent::__construct();
        $this->PayController();
    }
    
    function PayController(){
        $this->_mod=D('Album');   
    }
    
    /** 支付首页 */
    function index(){   
        
       $this->display('index/pay'); 
    }     
   
    /** 付款成功页面  */
    function pay_success(){
        $order_mod = M('reward_order');
        $order_sn = I('order_sn');
        $where = array('order_sn'=>$order_sn); //ip
        if (!$find=$order_mod->where($where)->find()){
            $this->error("订单号:{$order_sn}未找到对应记录.");
        }
        if($find['order_status']!=20){
            $this->error("订单号:{$order_sn}未支付成功.");
        }
        $this->assign('command_code',$find['command_code']);
        $page_url = U('album/reward',array('id'=>$find['album_id'],'t'=>microtime(true)));
        $this->assign('page_url',$page_url);
        $this->display('index/pay_success');
    }
    
}


?>