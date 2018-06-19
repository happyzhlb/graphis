<?php
/**
 * 订单控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class OrderController extends BackendController{
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    var $_refund_mod = null;
    var $_refund_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->OrderController();
    }
    function OrderController(){
        $this->_order_mod = D('Order');
        $this->_order_goods_mod = D('OrderGoods');
        $this->_order_log_mod = D('OrderLog');
        $this->_refund_mod = D('Refund');
        //$this->_refund_log_mod = D('RefundLog');
    }
    
    /** 订单列表 */
    function index(){
        if($_GET['user_name']){
            $user_name = trim(I('user_name'));
            $user_name = explode(' ',$user_name);
            if($user_name[0]) 
                $where['u.first_name'] = array('like','%'.$user_name[0].'%');
            if($user_name[1])
                $where['u.last_name'] = array('like','%'.$user_name[1].'%');
            //$where['u.user_name'] = array('like','%'.trim(I('user_name')).'%');
        }
        if($_GET['order_sn']){
            $where['order_sn'] = trim(I('order_sn'));
        }
        if($_GET['from_time']){
            $where['o.add_time'][] = array('gt',strtotime(I('from_time')));
        }
        if($_GET['to_time']){
            $where['o.add_time'][] = array('lt', strtotime(I('to_time').'23:59:59'));
        } 
        if(isset($_GET['user_type'])){
            $type = I('user_type');
            switch($type){
                case '0':
                    $where['u.user_type'] = array('eq',0);
                    break;
                case '1':
                    $where['u.user_type'] = 1;
                    break;
                default:
                    break;
            }
        }
        if($_GET['order_status']){
            $where['o.order_status'] = I('order_status','','intval');
        }
        if($_GET['refund_status']){
            $where['o.refund_status'] = I('refund_status','','intval');
        }
        if($_GET['order_type']){
            $where['o.order_type'] = trim(I('order_type')); 
        }
        if($_GET['shipping_method']){
            if($_GET['shipping_method'] == 'shipping')
                $where['o.shipping_code'] = array('neq','CPU');
            elseif($_GET['shipping_method'] == 'pickup')
                $where['o.shipping_code'] = 'CPU';
        }
        $where['is_delete'] = 0;
        $count = $this->_order_mod->where($where)
                 ->join('as o LEFT JOIN __USER__ as u ON o.user_id=u.user_id')
                 ->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('theme', '%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $orders = $this->_order_mod->field('o.*,u.user_name,u.email')->where($where)
                  ->join('as o LEFT JOIN __USER__ as u ON o.user_id=u.user_id')
                  ->order('o.add_time DESC')
                  ->limit($page->firstRow.','.$page->listRows)->select();
                  //echo M()->getLastsql();
        if(is_array($orders)){
            foreach($orders as $key => $vo){
                $order_ids[] = $vo['order_id'];
                if($vo['order_type'] == 'online'){
                    $orders[$key]['order_type'] = '线上订单';
                }else{
                    $orders[$key]['order_type'] = '线下订单';
                }
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
        $order_status = array(
            '0' => '已取消',
            '11' => '待付款',
            '20' => '待发货',
            '30' => '已发货',
            '40' => '已完成'
        );
        $refund_status = array(
            '1' => '退款中',
            '2' => '退款完成'
        );
        $order_type = array(
            'online' => '线上订单',
            'unline' => '线下订单'
        );
        $shipping_method = array(
            'shipping' => '普通发货',
            'pickup' => '自提'
        );
        $this->assign('order_type',$order_type);
        $this->assign('shipping_method',$shipping_method);
        $this->assign('order_status',$order_status);
        $this->assign('refund_status',$refund_status);
        $this->assign('orders',$orders);
        $this->assign('page',$page->show());
        $this->display('./order.index');
    }
    
    
    /** 订单回收站 */
    function recycle(){
        if($_GET['user_name']){
            $where['u.user_name'] = array('like','%'.trim(I('user_name')).'%');
        }
        if($_GET['order_sn']){
            $where['order_sn'] = trim(I('order_sn'));
        }
        if($_GET['from_time']){
            $where['o.add_time'][] = array('gt',strtotime(I('from_time')));
        }
        if($_GET['to_time']){
            $where['o.add_time'][] = array('lt', strtotime(I('to_time').'23:59:59'));
        }
        if(isset($_GET['user_type'])){
            $type = I('user_type');
            switch($type){
                case '0':
                    $where['u.user_type'] = array('eq',0);
                    break;
                case '1':
                    $where['u.user_type'] = 1;
                    break;
                default:
                    break;
            }
        }
        if($_GET['order_status']){
            $where['o.order_status'] = I('order_status','','intval');
        }
        if($_GET['refund_status']){
            $where['o.refund_status'] = I('refund_status','','intval');
        }
        
        $where['is_delete'] = array('gt',0); //已经被用户删除的订单
        
        $count = $this->_order_mod->where($where)
                 ->join('as o LEFT JOIN __USER__ as u ON o.user_id=u.user_id')
                 ->count();
        $page = new \Think\Page($count,10);
        $page->setConfig('theme', '%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $orders = $this->_order_mod->field('o.*,u.first_name,u.last_name,u.email')->where($where)
                  ->join('as o LEFT JOIN __USER__ as u ON o.user_id=u.user_id')
                  ->order('o.add_time DESC')
                  ->limit($page->firstRow.','.$page->listRows)->select();
        if(is_array($orders)){
            foreach($orders as $key => $vo){
                $order_ids[] = $vo['order_id'];
                if($vo['order_type'] == 'online'){
                    $orders[$key]['order_type'] = '线上订单';
                }else{
                    $orders[$key]['order_type'] = '线下订单';
                }
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
        $order_status = array(
            '0' => '已取消',
            '11' => '待付款',
            '20' => '待发货',
            '30' => '已发货',
            '40' => '已完成'
        );
        $refund_status = array(
            '1' => '退款中',
            '2' => '退款完成'
        );
        $this->assign('order_status',$order_status);
        $this->assign('refund_status',$refund_status);
        $this->assign('orders',$orders);
        $this->assign('page',$page->show());
        $this->display('./order.recycle');
    }
    
    
    /** 还原订单 */
    function restore(){
        $order_id = I('id','','intval');
        $order = $this->_order_mod->find($order_id);
        if(!$order){
            $this->error('订单不存在，或已被删除');
            return;
        }
        //还原已经删除的订单
        $edit_data = array(
            'order_id' => $order_id,
            'is_delete' => 0
        );
        if(!$this->_order_mod->save($edit_data)){
            $this->error('订单还原失败');
            return;
        }
        $this->success('订单还原成功');
    }
    
    /** 订单明细 */
    function view(){
        $order_id = I('id','','intval');
        $data['order'] = $this->_order_mod->field('o.*,u.user_name,u.email')
                         ->join('as o LEFT JOIN __USER__ as u ON o.user_id=u.user_id')
                         ->where(array('o.order_id'=>$order_id))
                         ->find();
        if(!$data['order']){
            $this->error('订单不存在或已被删除');
            return;
        }
        
        if($data['order']['order_type'] == 'online'){
            $data['order']['order_type'] = '线上商城';
        }else{
            $data['order']['order_type'] = 'APP';
        }
        //收货地址
        $_region_mod = M('Region');
        $where['region_id'] = array('in',array($data['order']['country'],$data['order']['state']));
        $region = $_region_mod->field('region_id,region_name')->where($where)->order('region_id desc')->select();
        foreach($region as $key => $value){
            //$data['order']['address'] .=  ' '. $value['region_name'];
        }
        $data['ordergoods'] = $this->_order_goods_mod->where(array('order_id'=>$order_id))->select();
        if(!$data['ordergoods']){
            $this->error('订单明细不存在，或已经被删除');
            return;   
        }
        //订单操作
        $data['handle'] = $this->order_can_handle($data['order']['order_status'], $data['order']['refund_status']);
        $data['logs'] = $this->_order_log_mod->_get_logs($order_id);
        $this->assign($data);
        $this->display('./order.view');
    }
    
    /** 订单状态操作 */
    function orderstatus(){
        $order_id = I('id','','intval');
        $order = $this->_order_mod->find($order_id);
        if(!$order){
            $this->error('订单不存在，或者已经被删除');
            return;
        }
        D('')->startTrans();
        /* 付款 */
        if(isset($_POST['pay'])){
            if($order['order_status'] == '11' && !$order['refund_status']){
                $data = array(
                    'order_id' => $order_id,
                    'order_status' => 20,
                    'pay_time' => gmtime()
                );
                $this->_order_mod->save($data);
                $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
                $send_email_data = array(
	                'order_sn' => $order['order_sn'],
	                'invoice_no' => $order['invoice_no'],
                	'totle_fee' => $order['totle_fee'],
	                'pay_time' => $data['pay_time']
            	);
            	//发送邮件通知发货
            	$user_email = current(M('User')->field('email')->where("user_id='{$order['user_id']}'")->find());
            	sendEmailByTemplate('payment_success', $user_email, $send_email_data);
            }
        }elseif(isset($_POST['unpay'])){ /* 待付款 */
            if($order['order_status'] == '20' && !$order['refund_status']){
                $data = array(
                    'order_id' => $order_id,
                    'order_status' => 11,
                    'pay_time' => 0
                );
                $this->_order_mod->save($data);
                $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
            }   
        }elseif(isset($_POST['cancel'])){ /* 取消 */
            if($order['order_status'] == '11' && !$order['refund_status']){
                $data = array(
                    'order_id' => $order_id,
                    'order_status' => 0,
                    'cancel_time' => gmtime()
                );
                $this->_order_mod->save($data);
                $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
                //取消订单处理库存和积分
                cancel_order($order_id);
            }   
        }elseif(isset($_POST['shipping'])){ /* 发货 */
            if($order['order_status'] == '20' && !$order['refund_status']){
                if($order['shipping_code'] == 'CPU'){ //判断是否自提订单
                    $nowtime = gmtime();
                    $data = array(
                        'order_id' => $order_id,
                        'order_status' => 40,
                        'shipping_time' => $nowtime,
                        'finish_time' => $nowtime
                    );
                    $this->_order_mod->save($data);
                    $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
                }else{
                    redirect(U('/Admin/Order/shipping',array('id'=>$order_id)));   
                }
            }else{
                $this->error('只有待发货订单才能进行发货操作');
                return;
            }
        }elseif(isset($_POST['unship'])){ /* 待发货 */
           if($order['order_status'] == '30' && !$order['refund_status']){
                $data = array(
                    'order_id' => $order_id,
                    'order_status' => 20,
                    'shipping_code' => '',
                    'shipping_name' => '',
                    'invoice_no' => '',
                    'shipping_time' => 0
                );
                $this->_order_mod->save($data);
                $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
            } 
        }elseif(isset($_POST['finish'])){ /* 完成 */
           if($order['order_status'] == '30' && !$order['refund_status']){
                $data = array(
                    'order_id' => $order_id,
                    'order_status' => 40,
                    'finish_time' => gmtime()
                );
                $this->_order_mod->save($data);
                $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
            } 
        }elseif(isset($_POST['shipped'])){ /* 已经发货 */
           if($order['order_status'] == '40' && !$order['refund_status']){
                $data = array(
                    'order_id' => $order_id,
                    'order_status' => 30,
                    'finish_time' => 0
                );
                $this->_order_mod->save($data);
                $this->_order_goods_mod->where("order_id={$order_id}")->save($data);
            } 
        }
        //记录订单操作日志
        $log_data = array(
            'log_user' => 'admin|'.$this->visitor->get('user_name'),
            'order_id' => $order_id,
            'from_status' => $order['order_status'],
            'to_status' => $data['order_status'],
            'note' => I('note'),
            'log_time' => gmtime()
        );
        if(!$this->_order_log_mod->add($log_data)){
            D('')->rollback();
            $this->error('订单操作日志保存失败');
            return;
        }
        D('')->commit();
        $this->success('订单操作成功',U('/Admin/Order/view',array('id'=>$order_id)));
    }
    
    /** 发货 */
    function shipping(){
        $order_id = I('id','','intval');
        $order = $this->_order_mod->find($order_id);
        if(!$order){
            $this->error('订单不存在，或者已被删除');
            return;
        }
        //获取配送方式
        $shipping = C('SHIPPING');
        if(!$shipping){
            $this->error('没有设置配送方式，不能发货');
            return;
        }
        if(!IS_POST){
            $this->assign('shipping',$shipping);
            $this->assign('order',$order);
            $this->display('./order.shipping');
        }else{
            $data = array(
                'order_id' => $order_id,
                'shipping_code' => I('shipping_code'),
                'shipping_name' => $shipping[I('shipping_code')]['shipping_name'],
                'order_status' => 30, //发货
                'invoice_no' => trim(I('invoice_no')),
                'shipping_time' => gmtime()
            );
            D('')->startTrans();
            if(!$this->_order_mod->create($data)){
                D('')->rollback();
                $this->error($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            $this->_order_goods_mod->where("order_id={$order_id}")->save(array('order_status'=>30));
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|'.$this->visitor->get('user_name'),
                'order_id' => $order_id,
                'from_status' => $order['order_status'],
                'to_status' => $data['order_status'],
                'note' => '发货处理',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('订单操作日志保存失败');
                return;
            }
            D('')->commit();
            
            //发送邮件通知发货
            $user_email = current(M('User')->field('email')->where("user_id='{$order['user_id']}'")->find());
            $send_email_data = array(
                'order_sn' => $order['order_sn'],
                'invoice_no' => $data['invoice_no'],
                'shipping_name' => $data['shipping_name']
            );
            sendEmailByTemplate('order_shipped', $user_email, $send_email_data);
            $this->success('发货成功',U('/Admin/Order/view',array('id'=>$order_id)));
        }
    }
    
    
    /** 调整费用 */
    function adjust_fee(){
        $order_id = I('id','','intval');
        $order = $this->_order_mod->field('order_id,order_status,refund_status,goods_amount,shipping_fee,integral_fee,discount_fee')
                 ->find($order_id);
        if(!$order){
            $this->error('订单不存在，或已被删除');
            return;
        }
        if($order['order_status'] != 11 || $order['refund_status']){
            $this->error('不是待付款订单不能调整费用');
            return;
        }
        if(!IS_POST){
            $this->assign('order',$order);
            $this->display('./order.adjust_fee');
        }else{
            $order['goods_amout'] = I('goods_amout','0','doubleval');
            $order['shipping_fee'] = I('shipping_fee','0','doubleval');
            $order['integral_fee'] = I('integral_fee','0','doubleval');
            $order['discount_fee'] = I('discount_fee','0','doubleval');
            $order['totle_fee'] = $order['goods_amount'] + $order['shipping_fee'] - $order['integral_fee'] - $order['discount_fee'];
            D('')->startTrans();
            if(!$this->_order_mod->create($order)){
                D('')->rollback();
                $this->error($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|'.$this->visitor->get('user_name'),
                'order_id' => $order_id,
                'from_status' => $order['order_status'],
                'to_status' => $order['order_status'],
                'note' => '费用调整',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('订单操作日志保存失败');
                return;
            }
            D('')->commit();
            $this->success('费用调整成功',U('/Admin/Order/view',array('id'=>$order_id)));
        }
    }
    
    /** 编辑配送方式 */
    function send_way(){
        $order_id = I('id','','intval');
        $order = $this->_order_mod->field('order_id,order_status,shipping_code,shipping_name,invoice_no')->find($order_id);
        if(!$order){
            $this->error('订单不存在，或者已被删除');
            return;
        }
        //获取配送方式
        $shipping = C('SHIPPING');
        if(!$shipping){
            $this->error('没有设置配送方式');
            return;
        }
        if(!IS_POST){
            $this->assign('order',$order);
            $this->assign('shipping',$shipping);
            $this->display('./order.send_way');
        }else{
            $shipping_info = trim(I('shipping_code'));
            $shipping_info = explode('|',$shipping_info);
            $order['shipping_code'] = $shipping_info[0];
            $order['shipping_name'] = $shipping_info[1];
            $order['invoice_no'] = trim(I('invoice_no'));
            D('')->startTrans();
            if(!$this->_order_mod->create($order)){
                D('')->rollback();
                $this->error($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|'.$this->visitor->get('user_name'),
                'order_id' => $order_id,
                'from_status' => $order['order_status'],
                'to_status' => $order['order_status'],
                'note' => '编辑配送信息',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('订单操作日志保存失败');
                return;
            }
            D('')->commit();
            $this->success('编辑配送信息成功',U('/Admin/Order/view',array('id'=>$order_id)));
        }
    }
    
    /** 编辑付款方式 */
    function payment(){
        $order_id = I('id','','intval');
        $order = $this->_order_mod->field('order_id,order_status,pay_code,pay_name')->find($order_id);
        if(!$order){
            $this->error('订单不存在，或者已被删除');
            return;
        }
        if(!IS_POST){
            $_payment_mod = D('Payment');
            $payments = $_payment_mod->get_enabled_payments();
            $this->assign('payments',$payments);
            $this->assign('order',$order);
            $this->display('./order.payment');
        }else{
            $payment = trim(I('payment'));
            $payment = explode('|',$payment);
            $order['pay_code'] = $payment[0];
            $order['pay_name'] = $payment[1];
            D('')->startTrans();
            $this->_order_mod->save($order);
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|'.$this->visitor->get('user_name'),
                'order_id' => $order_id,
                'from_status' => $order['order_status'],
                'to_status' => $order['order_status'],
                'note' => '编辑支付方式',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('订单操作日志保存失败');
                return;
            }
            D('')->commit();
            $this->success('订单支付方式编辑成功',U('/Admin/Order/view',array('id'=>$order_id)));
        }
    }
    
    /** 删除订单，订单进入回收站 */
    function delete(){
        $order_id = trim(I('id'));
        if(!$order_id){
            $this->error('传入的参数有误，无法删除');
            return;
        }
        if(strpos($order_id,','))
            $order_id = explode(',',$order_id);
        if(is_array($order_id)){
            $where['order_id'] = array('in',$order_id);
        }else{
            $where['order_id'] = $order_id;
        }
        $where['order_status'] = 0;
        $orders = $this->_order_mod->field('order_id')->where($where)->select();
        if(!$orders){
            $this->error('多选的订单状态不能删除');
            return;
        }
        unset($where);
        $order_id = array();
        foreach($orders as $key => $order){
            $order_id[] = $order['order_id'];
        }
        $where['order_id'] = array('in',$order_id);
        if(!$this->_order_mod->where($where)->save(array('is_delete'=>1))){
            $this->error('订单删除失败');
            return;
        }
        $this->success('订单删除成功',U('/Admin/Order'));
    }
    
    /** 删除订单 */
    function drop(){
        $order_id = trim(I('id'));
        if(!$order_id){
            $this->error('传入的参数有误，无法删除');
            return;
        }
        if(strpos($order_id,','))
            $order_id = explode(',',$order_id);
        if(is_array($order_id)){
            $where['order_id'] = array('in',$order_id);
        }else{
            $where['order_id'] = $order_id;
        }
        $where['order_status'] = 0;
        $orders = $this->_order_mod->field('order_id')->where($where)->select();
        if(!$orders){
            $this->error('多选的订单状态不能删除');
            return;
        }
        unset($where);
        $order_id = array();
        foreach($orders as $key => $order){
            $order_id[] = $order['order_id'];
        }
        if(count($order_id) > 0){
            $where['order_id'] = array('in',$order_id);
            D('')->startTrans();
            if(!$this->_order_mod->where($where)->delete()){
                D('')->rollback();
                $this->error('删除订单头失败');
                return;
            }
            //删除订单明细
            if(!$this->_order_goods_mod->where($where)->delete()){
                D('')->rollback();
                $this->error('订单明细删除失败');
                return;
            }
            //删除订单日志
            $log_count = $this->_order_log_mod->where($where)->count();
            if($log_count > 0){
                if(!$this->_order_log_mod->where($where)->delete()){
                    D('')->rollback();
                    $this->error('订单操作日志删除失败');
                    return;
                }
            }
            D('')->commit();
            $this->success('订单删除成功',U('/Admin/Order/recycle'));
        }else{
            $this->error('订单不存在，或者已被删除');
            return;
        }
    }
    
    
    /** 添加订单 */
    function add(){
        if(!IS_POST){
            $this->assign('regions',$this->get_region());
            $this->display('./order.add');
        }else{
            $spec_ids = I('spec_ids');
            $spec_quantity = I('quantity');
            if(!$spec_ids || !$spec_quantity){
                $this->error('请选择购买的产品信息');
                return;
            }
            $carts = $this->get_buy_goods(implode(',',$spec_ids), implode(',',$spec_quantity));
            if(!$carts){
                $this->error('请选择购买的产品信息');
                return;
            }
            //设置收货地址
            $shipping_address = array(
                'country' => 1, //默认美国
                'state' => I('state','','intval'),
                'company' => trim(I('company')),
                'city' => trim(I('city')),
                'address' => trim(I('address')),
                'zipcode' => trim(I('zipcode')),
                'first_name' => trim(I('consignee')),
                'telephone' => trim(I('telephone')),
                'mobile' => trim(I('mobile'))
            );
            if(!$_POST['billing_type']){ //新地址
                $billing_address = array(
                    'country' => 1, //默认美国
                    'state' => I('bstate','','intval'),
                    'company' => trim(I('bcompany')),
                    'city' => trim(I('bcity')),
                    'address' => trim(I('baddress')),
                    'zipcode' => trim(I('bzipcode')),
                    'first_name' => trim(I('bconsignee')),
                    'telephone' => trim(I('btelephone')),
                    'mobile' => trim(I('bmobile'))
                );   
            }else{
                $billing_address = $shipping_address;
            }
            
            //计算运费
            $shipping_info = $this->calculation_shipping_fee($shipping_address,$carts);
            
            //保存订单信息
            $order_data = array(
                'order_sn' => _gen_order_sn(),
                'user_id' => 0,
                'order_status' => 11,
                'refund_status' => 0,
                'order_type' => 'unline',
                'consignee' => $shipping_address['first_name'],
                'country' => $shipping_address['country'],
                'state' => $shipping_address['state'],
                'city' => $shipping_address['city'],
                'address' => $shipping_address['address'],
                'zipcode' => $shipping_address['zipcode'],
                'telephone' => $shipping_address['telephone'],
                'mobile' => $shipping_address['mobile'],
                'bconsignee' => $billing_address['first_name'],
                'bcountry' => $billing_address['country'],
                'bstate' => $billing_address['state'],
                'bcity' => $billing_address['city'],
                'baddress' => $billing_address['address'],
                'bzipcode' => $billing_address['zipcode'],
                'btelephone' => $billing_address['telephone'],
                'bmobile' => $billing_address['mobile'],
                'shipping_code' => $shipping_info['delivery']['shipping_code'],
                'shipping_name' => $shipping_info['delivery']['shipping_name'],
                'shipping_fee' => $shipping_info['shipping_fee'],
                'goods_amount' => I('goods_amount','','floatval'),
                'integral_fee' => 0,
                'discount_fee' => 0,
                'refund_fee' => 0,
                'totle_fee' => I('goods_amount','','floatval') + $shipping_info['shipping_fee'],
                'add_time' => gmtime(),
            );
            D('')->startTrans();
            if(!$this->_order_mod->create($order_data)){
                D('')->rollback();
                $this->error($this->_order_mod->getError());
                return;
            }
            $order_id = $this->_order_mod->add();
            //处理订单明细
            foreach($carts['carts'] as $ck => $cart){
                $order_goods_data = array(
                    'order_id' => $order_id,
                    'goods_id' => $cart['goods_id'],
                    'goods_name' => $cart['goods_name'],
                    'default_image' => $cart['default_image'],
                    'original_price' => $cart['price'],
                    'present_price' => $cart['price'],
                    'goods_attr' => $cart['spec_attr'],
                    'spec_id' => $cart['spec_id'],
                    'is_sample' => $cart['is_sample'],
                    'quantity' => $cart['quantity'],
                    'weight' => $cart['weight'],
                    'goods_totle' => $cart['totle'],
                    'order_status' => 11
                );
                if(!$this->_order_goods_mod->create($order_goods_data)){
                    D('')->rollback();
                    $this->error($this->_order_goods_mod->getError());
                    return;
                }
                $this->_order_goods_mod->add();
                
                //库存处理
                M('Goods')->where("goods_id='{$cart['goods_id']}'")->setField('goods_num',array('exp','goods_num-'.$cart['quantity']));
                M('GoodsSpecs')->where("spec_id='{$cart['spec_id']}'")->setField('sku',array('exp','sku-'.$cart['quantity']));
            }
            
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|'.$this->visitor->get('user_name'),
                'order_id' => $order_id,
                'from_status' => $order_data['order_status'],
                'to_status' => $order_data['order_status'],
                'note' => '管理新增线下订单',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('Saving order operation records failed.');
                return;
            }
            D('')->commit();
            $this->success('订单添加成功');
        }
    }
    
    
    /** 异步计算运费 */
    function ajax_shipping_fee(){
        $spec_ids = trim(I('spec_ids'));
        $spec_quanttiy = trim(I('quantity'));
        if(!$spec_ids || !$spec_quanttiy){
            $this->error('没有选择购买信息，或者输入购买信息错误');
            return;
        }
        $carts = $this->get_buy_goods($spec_ids, $spec_quanttiy);
        if(!$carts){
            $this->error('Please add the item to cart before checking out.');
            return;
        }
        //处理收货地址
        $address = array(
            'country' => 1, //默认美国
            'state' => I('state','','intval'),
            'company' => trim(I('company')),
            'city' => trim(I('city')),
            'address' => trim(I('address')),
            'zipcode' => trim(I('zipcode')),
            'first_name' => trim(I('consignee')),
            'telephone' => trim(I('telephone')),
        );
        $shipping_info = $this->calculation_shipping_fee($address,$carts);
        $this->success($shipping_info);
    }
    
    /** 获取商品信息 */
    function get_buy_goods($spce_ids, $spce_quantity){
        $spce_ids = explode(',',$spce_ids);
        $spce_quantity = explode(',',$spce_quantity);
        $where['spec_id'] = array('in',$spce_ids);
        $goods = M('GoodsSpecs')->field('gs.*,g.goods_name,g.goods_thumb as default_image')
                 ->join(' as gs INNER JOIN __GOODS__ as g ON gs.goods_id=g.goods_id')
                 ->where($where)->select();
        if(!$goods) return false;
        $ngoods = array();
        $ngoods['subtotle'] = 0;
        $ngoods['subweight'] = 0;
        foreach($spce_ids as $key => $spec_id){
            foreach($goods as $gk => $good){
                if($good['spec_id'] == $spec_id){
                    $quantity = $spce_quantity[$key];
                    if($quantity > $good['sku']){ //超出库存数量
                        $this->error('The '.$good['goods_name'].' lack of stock.');
                        return;
                    }
                    if($good['limit_buy'] && $quantity > $good['limit_buy']){
                        $quantity = $good['limit_buy'];
                    }
                    $good['quantity'] = $quantity;
                    $good['spec_attr'] = 'Batch:'.$good['spec_batch'].' Package:'.$good['spec_page'];
                    $ngoods['subtotle'] += $good['totle'] = (int)$good['price'] * $quantity;
                    $ngoods['subweight'] += (int)$good['weight'] * $quantity;
                    $ngoods['carts'][] = $good;
                }
            }
        }
        return $ngoods;
    }
    
    
    //计算运费
    function calculation_shipping_fee($address, $carts = array()){
        $delivery = '';
        $rateinfo = array();
        //读取发货地址
        $_region_mod = D('Admin/Region');
        $region = $_region_mod->field('region_name,region_code')
                         ->where(array('region_id'=>array('in',array(C('country'),C('state')))))
                         ->select();
        //获取发货地址
        $rateinfo['shipping_address'] = array(
            'country' => $region[0]['region_code'],
            'state' => $region[1]['region_code'],
            'city' => C('city'),
            'address' => C('address'),
            'zip' => C('zipcode'),
            'personname' => C('linkman'),
            'phonenumber' => C('telephone')
        );
        
        //设置收货地址
        $region = $_region_mod->field('region_name,region_code')
                         ->where(array('region_id'=>array('in',array($address['country'],$address['state']))))
                         ->select();
        $rateinfo['recipient'] = array(
            'country' => $region[0]['region_code'],
            'state' => $region[1]['region_code'],
            'city' => $address['city'],
            'address' => $address['address'],
            'zip' => $address['zipcode'],
            'personname' => $address['first_name'].' '.$address['last_name'],
            'phonenumber' => $address['telephone'],
        );
        //获取包装规格
        $pkgsize = get_package_size();
        $total_weight = 0; //单位LB
        //获取货物信息
        foreach($carts['carts'] as $key => $cart){
            $pk = $cart['weight'];
            $rateinfo['packageLineItem'][] = array(
                'SequenceNumber' => $cart['quantity'],
                'Weight' => $pkgsize[$pk]['weight'],
                'Length' => $pkgsize[$pk]['length'],
                'Width' => $pkgsize[$pk]['width'],
                'Height' => $pkgsize[$pk]['height'],
                'Packaging' => $pkgsize[$pk]['Packaging']
            );
            $total_weight += $cart['quantity'] * $pkgsize[$pk]['weight'];
        };
        //根据重量选择物流方式
        $shipping_info = C('SHIPPING');
        if($total_weight <= 150){
            $delivery = $shipping_info['R02'];
            vendor('FedEx.RateWebServiceClient');
            $fedex = new \Fedex($rateinfo);
            $result = $fedex->execute();
            if(!$result){
                $this->error($fedex->getError());
                return;
            }else{
                return array(
                    'delivery' => $delivery,
                    'shipping_fee' => $result
                );
            }
        }else{ 
            $delivery = $shipping_info['FXNL'];
            vendor('FedEx.FreightRateWebServiceClient');
            $fedex = new \Freight($rateinfo);
            $result = $fedex->execute();
            if(!$result){
                $this->error($fedex->getError());
                return;
            }else{
                return array(
                    'delivery' => $delivery,
                    'shipping_fee' => $result
                );
            }
            /*$delivery = 'YRC';
            $tolb = $carts['subweight'] / 453.59;
            $tolb = round($tolb);
            $dest = array( //收货地址
                'DestZipCode' => $rateinfo['recipient']['zip'],
                'DestCityName' => $rateinfo['recipient']['city'],
                'DestStateCode' => $rateinfo['recipient']['state']
            );
            $orig = array( //发货地址
                'OrigZipCode' => $rateinfo['shipping_address']['zip'],
                'OrigCityName' => $rateinfo['shipping_address']['city'],
                'OrigStateCode' => $rateinfo['shipping_address']['state']
            );
            $yrc=new \Org\Freight\Yrc(Date('Ymd'), $tolb, $dest, $orig);
            $price=$yrc->getRateQuote();
            if($price === false){
                $this->error($yrc->_msg);
                return;
            }
            return array(
                'delivery' => $delivery,
                'shipping_fee' => $price / 100
            );*/
        }
    }
    
    /** 导出订单 */
    function export(){
        $order_id = trim(I('id'));
        if(!$order_id){
            $this->error('参数有误，请刷新后再试');
            return;
        }
        if(strpos($order_id,','))
            $order_id = explode(',',$order_id);
        if(is_array($order_id)){
            $where['order_id'] = array('in',$order_id);
        }else{
            $where['order_id'] = $order_id;
        }
        $where['order_status'] = 20;
        $where['refund_status'] = 0;
        $orders = $this->_order_mod->where($where)->select();
        if(!$orders){
            $this->error('只有已付款待发货的订单可以导出，选中的订单中都不符合！');
            return;
        }
        $result = array();
        $_region_mod = D('Region');
        foreach($orders as $o => $order){
            //设置发货地址
            if($order['shipping_code'] == 'CPU'){
                $region = $_region_mod->field('region_name,region_code')->where(array('region_id' => array('in',array(C('country'),C('state')))))->order('region_id ASC')->select();
                $neworder['ShipTo'] = array(
                    'CompanyName' => 'A.N. Deringer, Inc.',
                    'Attention' => C('linkman'),
                    'Address1' => C('address'),
                    'Address2 ' => '',
                    'City' => C('city'),
                    'StateProvince' => $region[1]['region_code'],
                    'ZipPostalCode' => C('zipcode'),
                    'Country' => $region[0]['region_code'],
                    'Email' => '',
                    'Phone' => C('telephone'),
                    'Phone2 ' => ''
                );
            }else{
                $region = $_region_mod->field('region_name,region_code')->where(array('region_id' => array('in',array($order['country'],$order['state']))))->order('region_id ASC')->select();
                $neworder['ShipTo'] = array(
                    'CompanyName' => '',
                    'Attention' => $order['consignee'],
                    'Address1' => $order['address'],
                    'Address2 ' => '',
                    'City' => $order['city'],
                    'StateProvince' => $region[1]['region_code'],
                    'ZipPostalCode' => $order['zipcode'],
                    'Country' => $region[0]['region_code'],
                    'Email' => $order['email'],
                    'Phone' => $order['telephone'],
                    'Phone2 ' => $order['mobile']
                );
            }
            //设置账单地址
            $region = $_region_mod->field('region_name,region_code')->where(array('region_id' => array('in',array($order['bcountry'],$order['bstate']))))->order('region_id ASC')->select();
            $neworder['BillTo'] = array(
                'CompanyName' => '',
                'Attention' => $order['bconsignee'],
                'Address1' => $order['baddress'],
                'Address2 ' => '',
                'City' => $order['bcity'],
                'StateProvince' => $region[1]['region_code'],
                'ZipPostalCode' => $order['bzipcode'],
                'Country' => $region[0]['region_code'],
                'Email' => $order['bemail'],
                'Phone' => $order['btelephone'],
                'Phone2 ' => $order['bmobile']
            );
            $neworder['Storer'] = 'OKCHEM0001';
            $neworder['StorerName'] = 'OkChem';
            $neworder['ExternOrderKey'] = $order['order_sn'];
            $neworder['OrderDate'] = Date('m/d/Y',$order['add_time']);
            $neworder['ShipDate'] = Date('m/d/Y',$order['add_time']);
            $neworder['DeliveryDate'] = '';
            $neworder['ReferenceNo'] = $order['order_sn'];
            $neworder['DeliveryInfo'] = array(
                'COD' => '',
                'PayType' => '1',
                'ThirdPartyCollectAcctNo' => '',
                'Carrier' => array(
                    'CarrierCode' => $order['shipping_code'],
                    'CarrierName' => '',
                    'CarrierService' => ($order['shipping_code'] == 'CPU')?'':$order['shipping_name'], 
                ),
                'ResidentialDelivery' => 'N',
                
            );
            $neworder['NotesComments'] = '';
            if($order['inside_fee'] > 0)
                $neworder['NotesComments'] = 'Inside Delivery';
            if($order['shipping_code'] == 'CPU')
                $neworder['NotesComments'] = $order['shipping_name'];
            $neworder['WarehouseCode'] = 'CHA';
            $neworder['Gift'] = array(
                'IsGift' => '',
                'GiftWrap' => '',
                'GiftCard' => '',
                'GiftMessage' => '',
            );
            $neworder['PLD1'] = '';
            $neworder['PLD2'] = '';
            $neworder['PLD3'] = '';
            $neworder['PLD4'] = '';
            //设置订单明细
            $order_goods = $this->_order_goods_mod->field('og.*,gs.spec_item')
                           ->join(' as og INNER JOIN __GOODS_SPECS__ as gs ON og.spec_id=gs.spec_id')
                           ->where("og.order_id='{$order['order_id']}'")->select();
            if($order_goods != false){
                foreach($order_goods as $gk => $goods){
                    $item = array(
                        'LineNo' => $gk + 1,
                        'ProductCode' => $goods['spec_item'],
                        'ProductDesc' => $goods['goods_name'] . ' ' . $goods['goods_attr'],
                        'Companyin' => '',
                        'QTY' => $goods['quantity'],
                        'UnitPrice' => $goods['present_price'],
                        'PO' => '',
                        'LineNotesComments' => '',
                        'LineGift' => array(
                            'IsLineGift' => '',
                            'LineGiftWrap' => '',
                            'LineGiftCard' => '',
                            'LineGiftMessage' => ''
                        )
                    );
                    $neworder['LineItems'][]['LineItem'] = $item;                   
                }
            }
            $result['Orders'][]['Order'] = $neworder;
        }
        //header('Content-Type: text/xml');
        $xml = $this->build_xml($result);
        
        //写入本地文件
        $savepath = DATA_PATH.'order_xml/';
        if(!is_dir($savepath)){ //目录不存在创建目录
            if(!mkdir($savepath, 0777, true)){
                $this->error("目录 {$savepath} 创建失败！");
                return false;
            }
            
        }
        $savename = 'order_'.gmtime() .'.xml';
        $filename = $savepath . $savename;
        //var_dump($filename);exit;
        $fp = @fopen($filename,"w");
        if(!$fp){
            $this->error('文件创建失败');
        }
        @fwrite($fp,$xml);
        @fclose($fp);
        //header('Content-type: application/xml');
        //header('Content-Disposition: attachment; filename="'.$savename.'"');
        //readfile($filename);
        
        
        //上传xml文件至仓库服务器
        //$url  = 'http://www.okchem.com/Acceptnotice/index.html'; 
        $url = 'http://securetest.anderinger.com:8083/server?basicauth=OkChem:1121&request=send';
        //$rootpath = dirname(dirname(dirname(dirname(__FILE__))));
        $rootpath = '';
        $fields['file'] = '@'.$rootpath.$filename;
        //$fields['basicauth'] = 'OkChem:1121';
        //$fields['request'] = 'send';
        $fields['directory'] = 'Intermediate/';
        //var_dump($postdata);exit;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_POST, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
        
        ob_start();
        curl_exec($ch);
        $result = ob_get_contents();
        ob_end_clean();
        curl_close($ch);
        echo($result);
    }
    
    function build_xml($info,&$resultxml = ''){
        if(!$resultxml)
            $resultxml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if(is_array($info)){
            foreach($info as $key => $val){
                if(is_numeric($key)){
                    $this->build_xml($val,$resultxml);
                    continue;
                }
                $resultxml .= "<".$key;
                if(!$val){
                    $resultxml .= "/>\n";
                    continue;
                }else{
                    if(is_array($val)){
                        $resultxml .= ">\n";
                        $this->build_xml($val,$resultxml);
                    }else{
                        $resultxml .= ">".$val;
                    }
                }
                $resultxml .= "</".$key .">\n";
            }
        }
        return $resultxml;  
     }
     
     
     /** 获取state */
    function get_region($region_id=1){
        $_region_mod = D('Region');
        $regions = $_region_mod->_get_region($region_id,true);
        return $regions;
    } 
    
}


?>