<?php
/**
 * 订单报表控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class OrderreportController extends BackendController{
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->OrderController();
    }
    function OrderController(){
        $this->_order_mod = D('Order');
        $this->_order_goods_mod = D('OrderGoods');
        $this->_order_log_mod = D('OrderLog');
    }
    
    function _before_index(){
    	//C('SHOW_PAGE_TRACE',true);
    	//C('SHOW_DB_TIMES',true); 
    	$order_status = array(
    		'40' => '已完成订单', 
            '20' => '含已付款待发货订单',
            '30' => '含已发货未确认收货订单',            
        ); 
        $refund_status = array(
            '0' => '无退款',
        	'11' => '退款中',
            '33' => '退款完成'
        );
        $this->assign('order_status',$order_status);
        $this->assign('refund_status',$refund_status);
    }
    /** 订单报表 */
    function index(){
      if(!IS_POST){
    	$this->display('./report.index');
      } 
    } 
    
    //按商品统计
    function goods(){ 
    	$this->_before_index();
    	$pre=C('DB_PREFIX');
    	$sqlcmd="SELECT b.goods_id,b.goods_name,b.present_price,b.weight,b.quantity  
    	 FROM {$pre}order a INNER JOIN {$pre}order_goods b ON a.order_id=b.order_id where 1"; 
		$sqlcmd.=$this->where();
        $list=M()->query("SELECT goods_id,goods_name,SUM(weight) weight,sum(present_price) present_price,sum(quantity) quantity from($sqlcmd) as aa GROUP BY goods_id");
        $this->assign('list',$list);  
        $this->display('./report.goods.index'); 
    }

	//按用户统计
    function user(){ 
    	$this->_before_index();
    	$pre=C('DB_PREFIX');
    	$sqlcmd="SELECT a.user_id,b.email,count(*) cnt,sum(goods_amount) goods_amount,sum(shipping_fee) shipping_fee,sum(integral_fee) integral_fee,sum(discount_fee) discount_fee,sum(totle_fee) totle_fee from {$pre}order a ,{$pre}user b  where a.user_id=b.user_id"; 
        $sqlcmd.=$this->where().' GROUP BY user_id'; 
        $list=M()->query( $sqlcmd );
    	$this->assign('list',$list); 
        $this->display('./report.user.index'); 
    }

	//按区域统计
    function region(){ 
    	$this->_before_index();
    	$pre=C('DB_PREFIX');
    	$sqlcmd="SELECT b.state,c.region_name,count(*) cnt,sum(goods_amount) goods_amount,sum(shipping_fee) shipping_fee,sum(integral_fee) integral_fee,sum(discount_fee) discount_fee,sum(totle_fee) totle_fee from {$pre}order a ,{$pre}user b,{$pre}region c where a.user_id=b.user_id and b.state=c.region_id"; 
        $sqlcmd.=$this->where().' GROUP BY b.state'; 
        $list=M()->query( $sqlcmd );
    	$this->assign('list',$list); 
        $this->display('./report.region.index'); 
    }   
    
	//按支付方式统计
    function paymethod(){ 
    	$this->_before_index();
    	$pre=C('DB_PREFIX');
    	$sqlcmd="SELECT a.pay_code,count(*) cnt,sum(goods_amount) goods_amount,sum(shipping_fee) shipping_fee,sum(integral_fee) integral_fee,sum(discount_fee) discount_fee,sum(totle_fee) totle_fee from {$pre}order a where 1"; 
        $sqlcmd.=$this->where().' GROUP BY a.pay_code'; 
        $list=M()->query( $sqlcmd );   
    	$this->assign('list',$list); 
        $this->display('./report.paymethod.index'); 
    } 
    
    //订单条件过滤
    function where(){
    	$sqlcmd='';
    	if(I('from_time','','trim')){
            $sqlcmd.= ' and a.add_time >='.strtotime(trim(I('from_time')));
        } 
        if(I('to_time','','trim')){
            $sqlcmd.= ' and a.add_time <='.strtotime(trim(I('to_time')).' 23:59:59');
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