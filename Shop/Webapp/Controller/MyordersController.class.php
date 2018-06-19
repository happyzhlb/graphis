<?php
/**
 * Webapp我的订单控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Controller;
class MyordersController extends MemberController{
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->MyordersController();
    }
    function MyordersController(){
        $this->_order_mod = D('Admin/Order');
        $this->_order_goods_mod = D('Admin/OrderGoods');
        $this->_order_log_mod = D('Admin/OrderLog');
    }
    
    function _before_index(){
    	$user_id=$this->visitor->get('user_id');
    	//AwaitPayment
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,
    		'order_status'=>11,
    	);
    	$count['AwaitPayment']=$this->_order_mod->where($where)->count();
    	
    	//AwaitShipment
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,
    		'order_status'=>20,
    		'refund_status'=>0,
    	);    	
    	$count['AwaitShipment']=$this->_order_mod->where($where)->count();
    	
    	//AwaitPickUp
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,
    		'order_status'=>20,
    		'refund_status'=>0,
    		'shipping_code'=>"CPU"
    	);     	
    	$count['AwaitPickUp']=$this->_order_mod->where($where)->count();
    	
    	//AwaitConfirmation
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,
    		'order_status'=>30,
    		'refund_status'=>0, 
    	);     	
    	$count['AwaitConfirmation']=$this->_order_mod->where($where)->count();
    	
     	//ToBeReviewed
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,
    		'order_status'=>40,
    		'refund_status'=>0, 
    		'comment_time' =>0,
    	);   	
    	$count['ToBeReviewed']=$this->_order_mod->where($where)->count();
    	
     	//InRefundProcess
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0, 
    		'refund_status'=>1,  
    	);   
    	$count['InRefundProcess']=$this->_order_mod->where($where)->count();
    	
     	//BeyondThreeMonths
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,  
    		'add_time' => array('exp','<(unix_timestamp(now())-60*60*24*90)'),
    	);
    	$count['BeyondThreeMonths']=$this->_order_mod->where($where)->count();

    	//WithinThreeMonths
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0,  
    		'add_time' => array('exp','>=(unix_timestamp(now())-60*60*24*90)'),
    	);    	
    	$count['WithinThreeMonths']=$this->_order_mod->where($where)->count();
		
    	//ALL
    	$where=array(
    		'user_id' =>$user_id,
    		'is_delete'=>0, 
    	);
    	$count['ALL']=$this->_order_mod->where($where)->count();
    	
    	$this->assign($count);
    }
    
    //我的订单列表
    function index(){
        if($_GET['order_sn']){
            $where['order_sn'] = trim(I('order_sn'));
        }
        if($_GET['from_time']){
            $where['add_time'][] = array('gt',strtotime(I('from_time')));
        }
        if($_GET['to_time']){
            $where['add_time'][] = array('lt', strtotime(I('to_time').'23:59:59'));
        }
        if(isset($_GET['reviewed']) && !$_GET['reviewed']){
            $where['comment_time'] = array('eq','');
        }elseif(isset($_GET['reviewed']) && $_GET['reviewed']){
            $where['comment_time'] = array('neq','');
        }
        if($_GET['order_status']){
            $_GET['order_status'] = str_replace('+','',$_GET['order_status']);
            switch($_GET['order_status']){
                case 'AwaitPayment':
                    $where['order_status'] = 11;
                    break;
                case 'AwaitShipment':
                    $where['order_status'] = 20;
                    $where['refund_status'] = 0;
                    break; 
                case 'AwaitPickUp':
                    $where['order_status'] = 20;
                    $where['refund_status'] = 0;
                    $where['shipping_code'] ='CPU';
                    break;
                case 'AwaitConfirmation':					//Shipped
                    $where['order_status'] = 30;
                    $where['refund_status'] = 0;
                    break;
                case 'ToBeReviewed':
                    $where['order_status'] = 40;
                    $where['comment_time'] = 0;
                    $where['refund_status'] = 0;
                    break; 
                case 'InRefundProcess':
                    $where['refund_status'] = 1;
                    break;  
                case 'WithinThreeMonths':
                    $where['add_time'] = array('exp',' >(unix_timestamp(now())-60*60*24*90)');
                    break;   
                case 'BeyondThreeMonths':
                    $where['add_time'] = 1;
                    break;                               
                case 'Completed':
                    $where['order_status'] = 40;
                    $where['refund_status'] = 0;
                    break;
                case 'Cancelled':
                    $where['order_status'] = 0;
                    break; 
                case 'Closed':
                    $where['refund_status'] =2;
                    break;
                case 'Reviewed':
                    $where['order_status'] = 40;
                    $where['comment_time'] = array('GT',0);
                    $where['refund_status'] = 0;
                    break; 
                default:
                    break;
            }
        }
        $where['user_id'] = $this->visitor->get('user_id');
        $where['is_delete'] = 0;
        //$where['shipping_code'] = array('neq','CPU');
        //I('shipping_code')=='CPU'?$where['shipping_code']='CPU':'';
        $count = $this->_order_mod->where($where)->count();
        $listRows=10;
        $p =  new \Think\Page($count,10);
        $orders = $this->_order_mod->where($where)
                  ->order('add_time DESC')
                  ->limit($p->firstRow.','.$p->listRows)->select();  
        $totalpages=ceil($count/$listRows);
        $this->assign('totalpages',$totalpages);
        
        if($orders != false){
            foreach($orders as $key => $vo){
                $order_ids[] = $vo['order_id'];
            }
            //获取订单明细
            $map['order_id'] = array('in',$order_ids);
            $ordergoods = $this->_order_goods_mod->where($map)->select();
            foreach($orders as $key => $vo){
                if(is_array($ordergoods)){
                    foreach($ordergoods as $gk => $gvo){
                        if($gvo['order_id'] == $vo['order_id']){
                            $orders[$key]['ordergoods'][] = $gvo;
                        }
                    }
                }
                $orders[$key]['handles'] = $this->order_can_handle($vo['order_status'],$vo['refund_status']);
            }
        }
        //$query_string = $_SERVER['QUERY_STRING'];
        //if($query_string)
        //    $query_string = preg_replace('/(&|\?)order_status=[^&]+/', '', $query_string);
        $this->assign('orders',$orders);
        //$this->assign('query_string',$query_string);
        $this->assign('page',$p->show());
        $this->display('./order');
    }
    
    //我的订单列表
    function pickup(){
        if($_GET['order_sn']){
            $where['order_sn'] = trim(I('order_sn'));
        }
        if($_GET['from_time']){
            $where['add_time'][] = array('gt',strtotime(I('from_time')));
        }
        if($_GET['to_time']){
            $where['add_time'][] = array('lt', strtotime(I('to_time').'23:59:59'));
        }
        if(isset($_GET['reviewed']) && !$_GET['reviewed']){
            $where['comment_time'] = array('eq','');
        }elseif(isset($_GET['reviewed']) && $_GET['reviewed']){
            $where['comment_time'] = array('neq','');
        }
        if($_GET['order_status']){
            $_GET['order_status'] = str_replace('+','',$_GET['order_status']);
            switch($_GET['order_status']){
                case 'AwaitPayment':
                    $where['order_status'] = 11;
                    break;
                case 'AwaitShipment':
                    $where['order_status'] = 20;
                    $where['refund_status'] = 0;
                    break;
                case 'AwaitConfirmation':
                    $where['order_status'] = 30;
                    $where['refund_status'] = 0;
                    break;
                case 'Completed':
                    $where['order_status'] = 40;
                    $where['refund_status'] = 0;
                    break;
                case 'Cancelled':
                    $where['order_status'] = 0;
                    break;
                case 'InRefundProcess':
                    $where['refund_status'] = 1;
                    break;
                case 'Closed':
                    $where['refund_status'] =2;
                    break;
                case 'Reviewed':
                    $where['order_status'] = 40;
                    $where['comment_time'] = array('GT',0);
                    $where['refund_status'] = 0;
                    break;
                case 'ToBeReviewed':
                    $where['order_status'] = 40;
                    $where['comment_time'] = 0;
                    $where['refund_status'] = 0;
                    break;
                default:
                    break;
            }
        }
        $where['user_id'] = $this->visitor->get('user_id');
        $where['is_delete'] = 0;
        $where['shipping_code'] = 'CPU';
        $count = $this->_order_mod->where($where)->count();
        $p =  new \Think\Page($count,10);
        $orders = $this->_order_mod->where($where)
                  ->order('add_time DESC')
                  ->limit($p->firstRow.','.$p->listRows)->select();
        if($orders != false){
            foreach($orders as $key => $vo){
                $order_ids[] = $vo['order_id'];
            }
            //获取订单明细
            $map['order_id'] = array('in',$order_ids);
            $ordergoods = $this->_order_goods_mod->where($map)->select();
            foreach($orders as $key => $vo){
                if(is_array($ordergoods)){
                    foreach($ordergoods as $gk => $gvo){
                        if($gvo['order_id'] == $vo['order_id']){
                            $orders[$key]['ordergoods'][] = $gvo;
                        }
                    }
                }
                $orders[$key]['handles'] = $this->order_can_handle($vo['order_status'],$vo['refund_status']);
            }
        }
        //$query_string = $_SERVER['QUERY_STRING'];
        //if($query_string)
        //    $query_string = preg_replace('/(&|\?)order_status=[^&]+/', '', $query_string);
        $this->assign('orders',$orders);
        //$this->assign('query_string',$query_string);
        $this->assign('page',$p->show());
        $this->display('./myorders.pickup');
    }
    
    /** 订单明细 */
    function view(){
        $order_id = I('id','','intval');
        $where['user_id'] = $this->visitor->get('user_id');
        $where['order_id'] = $order_id;
        $where['is_delete'] = 0;
        $order_info = $this->_order_mod->where($where)->find();
        if(!$order_info){
            $this->error('Order does not exist, or has been deleted.');
            return;
        }
        
        $order_info['use_integral'] = $order_info['integral_fee'] * 100;
        
        //如果已经发货订单，计算自动确认收货时间
        if($order_info['order_status'] == 30){
            $order_info['auto_confirm_delivery'] = $order_info['shipping_time'] + C('auto_confirm_delivery') * 24 * 3600 - gmtime();
            vendor('FedEx.TrackWebServiceClient');
            //$order_info['invoice_no'] = '9612804318693278999297';
            $fedex = new \FedExTrack($order_info['invoice_no']);
            $result = $fedex->trackinfo();
            if($result === false){
                $this->error($fedex->getError());
                return;
            }
            $track = array();
            $result = (array)$result;
            //通知结果状态
            $track['Notification'] = (array)$result['Notification'];
            if($track['Notification']['Severity'] == 'SUCCESS'){
                $track['Events'] = $this->object_to_array($result['Events']); 
            }
            $this->assign('track',$track);
        }
        
        
        //收货地址
        $order_info['address'] .= $order_info['city'];
        $_region_mod = M('Region');
        //$where['region_id'] = array('in',array($order_info['country'],$order_info['state']));
        $where['region_id'] = $order_info['state'];
        $region = $_region_mod->field('region_id,region_name')->where($where)->order('region_id desc')->select();
        foreach($region as $key => $value){
            $order_info['address'] .=  ','. $value['region_name'];
        }
        
        //订单明细
        $order_info['ordergoods'] = $this->_order_goods_mod->where(array('order_id'=>$order_id))->select();
        if(!$order_info['ordergoods']){
            $this->error('Order details do not exist, or have been deleted.');
            return;   
        }
        //可执行的操作
        $order_info['handle'] = $this->order_can_handle($order_info['order_status'], $order_info['refund_status']);
        $this->assign('order',$order_info);
        $this->display('./order_detail');
    }
    
    /** 取消订单 */
    function cancel(){
        $order_id = I('id','','intval');
        $where['user_id'] = $this->visitor->get('user_id');
        $where['order_id'] = $order_id;
        $where['is_delete'] = 0;
        $order_info = $this->_order_mod->where($where)->find();
        if(!$order_info){
            $this->error('Order does not exist, or has been deleted.');
            return;
        }
        //判断订单状态是否可以取消
        if($order_info['order_status'] !== '11' || $order_info['refund_status']){
            $this->error('Cancelling order not allowed under this order status.');
            return;
        }
        $order_data = array(
            'order_id' => $order_id,
            'order_status' => 0,
            'cancel_time' => gmtime()
        );
        D('')->startTrans();
        if(!$this->_order_mod->create($order_data)){
            D('')->rollback();
            $this->error($this->_order_mod->getError());
            return;
        }
        $this->_order_mod->save();
        //修改订单明细的
        $this->_order_goods_mod->where("order_id={$order_id}")->save(array('order_status'=>$order_data['order_status']));
        
        //取消订单处理库存和积分
        cancel_order($order_id);
        
        //保存订单操作日志
        $log_data = array(
            'log_user' => 'buyer|'.$this->visitor->get('user_name'),
            'order_id' => $order_info['order_id'],
            'from_status' => $order_info['order_status'],
            'to_status' => $order_data['order_status'],
            'from_refund_statu' => $order_info['refund_status'],
            'to_refund_status' => $order_info['refund_status'],
            'note' => '用户取消退款',
            'log_time' => gmtime()
        );
        if(!$this->_order_log_mod->add($log_data)){
            D('')->rollback();
            $this->error('Saving order operation records failed.');
            return;
        }
        D('')->commit();
        $this->success('Order cancelled successfully.',U('Myorders/index'));
    }
    
    /** 用户删除订单 */
    function delete(){
        $order_id = I('id','','intval');
        $where['order_id'] = $order_id;
        $where['user_id'] = $this->visitor->get('user_id');
        $order_info = $this->_order_mod->where($where)->find();
        if(!$order_info){
            $this->error('Order does not exist, or has been deleted.');
            return;
        }
        //判断订单是否已经删除
        if($order_info['is_delete']){
            $this->error('The order has been deleted.');
            return;
        }
        
        //判断订单状态是否允许删除
        if(!$order_info['order_status']){
            $order_data = array(
                'order_id' => $order_id,
                'is_delete' => 1,
            );
            if(!$this->_order_mod->create($order_data)){
                $this->error($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            $this->success('Order deleted successfully.',U('Myorders/index'));
        }else{
            $this->error('Order can not be deleted.');
            return;
        }
    }
    
    
    /** 前往支付页面 */
    function _go_to_pay($order_id){
        $where = array(
            'user_id' => $this->visitor->get('user_id'),
            'order_id' => $order_id
        );
        $order = $this->_order_mod->where($where)->find();
        $this->_config_seo(array(
            'Order Paymennt'
        ));
        //获取支付方式
        $paymentclass = new \Think\Payment();
        $payments = $paymentclass->get_enabled_payments();
        if(!$payments){
            $this->error('Payment system is not installed.');
            return;
        }
        $this->assign('payments',$payments);
        $this->assign('order',$order);
        $this->display('./order.pay');
    }
    
    /** 立即付款 */
    function pay(){
        $order_id = I('id','','intval');
        if(!$order_id){
            $this->error('Incorrect parameters, please refresh and try again.');
            return;
        }
        if(!IS_POST){
            $this->_go_to_pay($order_id);
        }else{
            //$this->error('Online payment is not open.');
            //var_dump($_POST);
            $where = array(
                'user_id' => $this->visitor->get('user_id'),
                'order_id' => $order_id
            );
            $order = $this->_order_mod->where($where)->find();
            if(!$order){
                $this->error('Order does not exist, or has been deleted.');
                return;
            }
            //判断订单状态
            if(in_array($order['order_status'],array(20,30,40))){
                $this->error('The order has been successful payment.');
                return;
            }
            $pay_code = trim(I('pay_code'));
            $paymentclass = new \Think\Payment();
            $payment_info = $paymentclass->chekc_enabled_payment($pay_code);
            if($payment_info === false){
                $this->error('The payment does not exist.');
                return;
            }
            //实例化支付类
            $payment = $paymentclass->set_payment($pay_code);
            $payment->set_config(unserialize($payment_info['pay_config']));
            $_region_mod = M('Region');
            $where = array(
                'region_id' => array('in',array($order['bcountry'],$order['bstate']))
            );
            $regions = $_region_mod->field('region_code')->where($where)->order('region_id ASC')->select();
            $order['bcountry'] = $regions[0]['region_code'];
            $order['bstate'] = $regions[1]['region_code'];
            $order['bemail'] = $this->visitor->get('email');
            $card_nam = trim(I('card_name'));
            $card_nam = explode(' ',$card_nam);
            $card_info = array(
                'card_no' => trim(I('card_no')),
                'exp_date' => I('exp_month','','intval').I('exp_year','','intval'),
                'first_name' => $card_nam[0],
                'last_name' => $card_nam[1],
                'ccid' => I('ccid','','intval')
            );
            $result = $payment->get_pay_form($order,$card_info);
            if($result === false){
                $this->error($payment->getError());
                return;
            }
            if($result['order_status'] == 20 && $order['order_status'] == 11){
                $order_data = array(
                    'order_id' => $order_id,
                    'order_status' => $result['order_status'],
                    'pay_code' => $payment_info['pay_code'],
                    'pay_name' => $payment_info['pay_name'],
                    'pay_time' => gmtime(),
                    'outer_order_sn' => $result['outer_order_sn']
                );
                D('')->startTrans();
                //修改订单单头状态
                if(!$this->_order_mod->create($order_data)){
                    D('')->rollback();
                    $this->error($this->_order_mod->getError());
                    return;
                }
                if(!$this->_order_mod->save()){
                    D('')->rollback();
                    $this->error('Order processing failure.');
                    return;
                }
                //修改订单明细状态
                if(!$this->_order_goods_mod->where("order_id='{$order_id}'")->save(array('order_status'=>$order_data['order_status']))){
                    D('')->rollback('Order detail treatment failure.');
                    $this->error();
                    return;                    
                }
                //记录订单操作日志
                $log_data = array(
                    'log_user' => 'buyer|'.$this->visitor->get('user_name'),
                    'order_id' => $order['order_id'],
                    'from_status' => $order['order_status'],
                    'to_status' => $order_data['order_status'],
                    'from_refund_statu' => $order['refund_status'],
                    'to_refund_status' => $order['refund_status'],
                    'note' => '用户支付订单',
                    'log_time' => $order_data['pay_time']
                );
                if(!$this->_order_log_mod->add($log_data)){
                    D('')->rollback();
                    $this->error('Saving order operation records failed.');
                    return;
                }
                D('')->commit();
                if(IS_AJAX){
                	$this->success(U('Myorders/view',array('id'=>$order_id)));
                }else{
                	$this->success('The success of payment.',U('Myorders/view',array('id'=>$order_id)));
                }
            }
        }
    }
    
    /** 用户确认收货 */
    function confirm(){
        $order_id = I('id','','intval');
        $where['order_id'] = $order_id;
        $where['user_id'] = $this->visitor->get('user_id');
        $order_info = $this->_order_mod->where($where)->find();
        if(!$order_info){
            $this->error('Order does not exist, or has been deleted.');
            return;
        }
        //判断订单是否允许确认收货
        if($order_info['order_status'] != 30 || $order_info['refund_status']){
            $this->error('Order can not be confirmed in current status.');
            return;
        }
        $order_data = array(
            'order_id' => $order_id,
            'order_status' => 40,
            'finish_time' => gmtime()
        );
        D('')->startTrans();
        if(!$this->_order_mod->create($order_data)){
            D('')->rollback();
            $this->error($this->_order_mod->getError());
            return;
        }
        $this->_order_mod->save();
        //修改订单明细的
        $this->_order_goods_mod->where("order_id='{$order_id}'")->save(array('order_status'=>$order_data['order_status']));
        //保存订单操作日志
        $log_data = array(
            'log_user' => 'buyer|'.$this->visitor->get('user_name'),
            'order_id' => $order_info['order_id'],
            'from_status' => $order_info['order_status'],
            'to_status' => $order_data['order_status'],
            'from_refund_statu' => $order_info['refund_status'],
            'to_refund_status' => $order_info['refund_status'],
            'note' => '用户确认收货',
            'log_time' => gmtime()
        );
        if(!$this->_order_log_mod->add($log_data)){
            D('')->rollback();
            $this->error('Saving order operation records failed.');
            return;
        }
        D('')->commit();
        sendEmailByTemplate('order_completed', $this->visitor->get('email'), array('order_sn'=>$order_info['order_sn']));
        $this->success('Order confirmed successfully.',U('Myorders/index'));
    }
    
    
    /** 订单评价 */
    function review(){
        $order_id = I('id','','intval');
        if(!$order_id){
            $this->error('Incorrect parameters, please refresh and try again.');
            return;
        }
        $order = $this->_order_mod->find($order_id);
        if(!$order){
            $this->error('The order do not exist, or have been deleted.');
            return;
        }
        //判断订单状态和评价时间
        if($order['order_status'] != 40 || $order['refund_status']>0 || $order['comment_time']>0){
            $this->error('Do not allow the evaluation.');
            return;
        }
        //查询订单明细
        $order_goods = $this->_order_goods_mod->where("order_id='{$order_id}'")->select();
        if(!$order_goods){
            $this->error('Order details do not exist, or have been deleted.');
            return;
        }
        if(!IS_POST){
            $this->assign('order',$order);
            $this->assign('order_goods',$order_goods);
            $this->display('./myorders.reviews');
        }else{
            $rec_id = I('rec_id','','intval');
            $content = I('content','','trim');
            $score = I('score','','intval');
            D('')->startTrans(); //开启事务
            $_comments_mod = D('Home/Comments');
            $nowtime = gmtime();
            foreach($rec_id as $key =>$rid){
                $data = array(
                    'user_id' => $this->visitor->get('user_id'),
                    'user_name' => $this->visitor->get('user_name'),
                    'email' => $this->visitor->get('email'),
                    'comment_stars' => $score[$key],
                    'content' => $content[$key],
                    'rec_id' => $rec_id[$key],
                    'comment_time' => $nowtime,
                    'ip' => get_client_ip(),
                    'status' => 1 //全部审核通过 
                );
                foreach($order_goods as $ok => $goods){
                    if($goods['rec_id'] == $rec_id[$key]){
                        $data['goods_id'] = $goods['goods_id'];
                    }
                }
                if(!$_comments_mod->create($data)){
                    D('')->rollback();
                    $this->error($_comments_mod->getError());
                    return;
                }
                $_comments_mod->add();
            }
            
            //更新订单的评价时间
            $order_data = array(
                'order_id' => $order_id,
                'comment_time' => $nowtime
            );
            if(!$this->_order_mod->save($order_data)){
                D('')->rollback();
                $this->error('Evaluation of failure.');
                return;
            }
            D('')->commit();
            $this->success('Evaluation of success',U('Myorders/index'));
        }
        
    }
    
    /** 订单回收站 */
    function recycle(){
        $where['user_id'] = $this->visitor->get('user_id');
        $where['is_delete'] = 1;
        $count = $this->_order_mod->where($where)->count();
        $p =  new \Think\Page($count,10);
        $orders = $this->_order_mod->where($where)
                  ->order('add_time DESC')
                  ->limit($p->firstRow.','.$p->listRows)->select();
        if($orders != false){
            foreach($orders as $key => $vo){
                $order_ids[] = $vo['order_id'];
            }
            //获取订单明细
            $map['order_id'] = array('in',$order_ids);
            $ordergoods = $this->_order_goods_mod->where($map)->select();
            foreach($orders as $key => $vo){
                if(is_array($ordergoods)){
                    foreach($ordergoods as $gk => $gvo){
                        if($gvo['order_id'] == $vo['order_id']){
                            $orders[$key]['ordergoods'][] = $gvo;
                        }
                    }
                }
            }
        }
        $query_string = $_SERVER['QUERY_STRING'];
        if($query_string)
            $query_string = preg_replace('/(&|\?)order_status=[^&]+/', '', $query_string);
        $this->assign('orders',$orders);
        $this->assign('query_string',$query_string);
        $this->assign('page',$p->show());
        $this->display('./myorders.recycle');
    }
    
    /** 回收站还原 */
    function restore(){
        $order_id = I('id','','intval');
        if(!$order_id){
            $this->error('Incorrect parameters, please refresh and try again.');
            return;
        }
        $order = $this->_order_mod->find($order_id);
        if(!$order){
            $this->error('Order details do not exist, or have been deleted.');
            return;
        }
        $data = array(
            'order_id' => $order_id,
            'is_delete' => 0,
        );
        if(!$this->_order_mod->create($data)){
            $this->error($this->_order_mod->getError());
            return;
        }
        $this->_order_mod->save();
        $this->success('Reduction of success.',U('Myorders/recycle'));
        
    }
    
    /** 回收站删除 */
    function drop(){
        $order_id = I('id','','intval');
        if(!$order_id){
            $this->error('Incorrect parameters, please refresh and try again.');
            return;
        }
        $order = $this->_order_mod->find($order_id);
        if(!$order){
            $this->error('Order details do not exist, or have been deleted.');
            return;
        }
        $data = array(
            'order_id' => $order_id,
            'is_delete' => 2,
        );
        if(!$this->_order_mod->create($data)){
            $this->error($this->_order_mod->getError());
            return;
        }
        $this->_order_mod->save();
        $this->success('Successfully deleted.',U('.Myorders/recycle'));
    }
    
    /** 转对象为数组 */
    private function object_to_array($obj){
        $_arr = is_object($obj)? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val){
            $val = (is_array($val)) || is_object($val) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;

        }
        return $arr;
    }
    
}


?>