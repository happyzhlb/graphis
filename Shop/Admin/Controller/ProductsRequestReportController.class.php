<?php
/**
 * 产品需求报表控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class ProductsRequestReportController extends BackendController{
    var $_mod = null; 
    function __construct(){
        parent::__construct();
        $this->ProductsRequestReportController();
    }
    function ProductsRequestReportController(){ 
        $this->_order_mod = D('productsRequest'); 
    }
     
    /** 订单报表 */
    function index(){
      if(!IS_POST){
    	$this->display('./report_products_request.index');
      } 
    } 
    
    //按商品统计
    function goods(){  
    	$pre=C('DB_PREFIX');
    	$sqlcmd="SELECT cas_number,sum(quantity_g) sum_quantity_g,AVG(quantity_g/cycle_day) avg_quantity_g,count(user_id) as cnt FROM `{$pre}products_request` GROUP BY cas_number "; 
		//$sqlcmd.=$this->where();
        $list=M()->query($sqlcmd);
    	//dump($list);
        $this->assign('list',$list);  
        $this->display('./report_products_request.goods'); 
    }

	//按用户统计
    function user(){  
    	$pre=C('DB_PREFIX');
    	$sqlcmd="SELECT a.user_id,b.email,count(*) cnt,sum(goods_amount) goods_amount,sum(shipping_fee) shipping_fee,sum(integral_fee) integral_fee,sum(discount_fee) discount_fee,sum(totle_fee) totle_fee from {$pre}order a ,{$pre}user b  where a.user_id=b.user_id"; 
        $sqlcmd.=$this->where().' GROUP BY user_id'; 
        $list=M()->query( $sqlcmd ); 
    	$this->assign('list',$list); 
        $this->display('./report_products_request.user'); 
    } 
    
    //订单条件过滤
    function where(){
    	$sqlcmd='';
    	if(I('from_time','','trim')){
            $sqlcmd.= ' and a.finish_time >='.strtotime(trim(I('from_time')));
        } 
        if(I('to_time','','trim')){
            $sqlcmd.= ' and a.finish_time <='.strtotime(trim(I('to_time')));
        } 
        if(I('order_status','','trim')){
            $sqlcmd.= ' and a.order_status ='.trim(I('order_status'));
        } 
        $refund_status=I('refund_status','','trim');
        if($refund_status=='0' || $refund_status=='33'){
            $sqlcmd.= ' and a.refund_status ='.trim(I('refund_status'));
        }elseif($refund_status>0 and $refund_status<33){
            $sqlcmd.= ' and (a.refund_status > 0 and a.refund_status < 33)';
        }	
    	return $sqlcmd;
    }
}


?>