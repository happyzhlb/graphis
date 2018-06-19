<?php
/**
 * 退款控制器
 * @author jiwaini00000
 * @copyright 2014
 */
 namespace Home\Controller;
 use Think\Controller;
 class RefundController extends MemberController{
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    var $_refund_mod = null;
    function __construct(){
        parent::__construct();
        $this->RefundController();
    }
    function RefundController(){
        $this->_order_mod = D('Admin/Order');
        $this->_order_goods_mod = D('Admin/OrderGoods');
        $this->_order_log_mod = D('Admin/OrderLog');
        $this->_refund_mod = D('Admin/Refund');
    }
    
    /** 退款明细 */
    function view(){
        $rec_id = I('id','','intval');
        $order = $this->_get_order_detail($rec_id);
        $order['refund_totle'] = round(($order['totle_fee'] - $order['shipping_fee']) / $order['goods_amount'] * $order['goods_totle'],2);
        $this->assign('refunds',$this->_refund_mod->_get_refunds($order['refund_sn']));
        $this->assign('order',$order);
        $this->display('./refund.view');
        
    }
    
    /** 选择退款类型 */
    function choice(){
        $rec_id = I('id','','intval');
        $order_detail = $this->_get_order_detail($rec_id);
        //判断订单状态是否允许退款
        if(in_array($order_detail['order_status'],array('0','11')) || $order_detail['refund_status']){
            $this->error('Refund not allowed under this order status.');
            return;
        }
        
        //判断是否在允许的时间内
        $nowtime = gmtime();
        $allow_max_time = $order_detail['pay_time'] + C('allow_refund_days') * 60*60*24;
        if($nowtime > $allow_max_time){
            $this->error(sprintf('Refund application is permitted only in %s days from the transaction date.',C('allow_refund_days')));
            return;
        }
        
        $order_detail['refund_totle'] = round(($order_detail['totle_fee'] - $order_detail['shipping_fee']) / $order_detail['goods_amount'] * $order_detail['goods_totle'],2);
        $this->assign('order',$order_detail);
        $this->display('./refund.choice');
    }
    
    /** 申请退款 */
    function apply(){
        $rec_id = I('id','','intval');
        $order_detail = $this->_get_order_detail($rec_id);
        //判断订单状态是否允许退款
        if(in_array($order_detail['order_status'],array('0','11')) || $order_detail['refund_status']){
            $this->error('Refund not allowed under this order status.');
            return;
        }
        
        //判断是否在允许的时间内
        $nowtime = gmtime();
        $allow_max_time = $order_detail['pay_time'] + C('allow_refund_days') * 60*60*24;
        if($nowtime > $allow_max_time){
            $this->error(sprintf('Refund application is permitted only in %s days from the transaction date.',C('allow_refund_days')));
            return;
        }
        $order_detail['refund_totle'] = round(($order_detail['totle_fee'] - $order_detail['shipping_fee']) / $order_detail['goods_amount'] * $order_detail['goods_totle'],2);
        if(!IS_POST){
            $refund_type = trim(I('type'));
            if(!$refund_type){
                redirect(U('/Refund/choice',array('id'=>$rec_id)));
                return;
            }
            if($refund_type == 'return'){
                $order_detail['refund_type'] = 1;
            }else{
                $order_detail['refund_type'] = 0;
            }
            $this->assign('refund_reason',get_refund_reason());
            $this->assign('order',$order_detail);
            $this->display('./refund.apply');
        }else{
            if(!$order_detail['refund_sn']) $order_detail['refund_sn'] = _gen_refund_sn();
            $order_goods_data = array(
                'rec_id' => $rec_id,
                'refund_sn' => $order_detail['refund_sn'],
                'refund_type' => I('refund_type',0,'intval'),
                'refund_status' => 11,
                'refund_price' => I('refund_price',0,'doubleval'),
                'refund_num' => I('refund_num',0,'intval'),
                'refund_time' => gmtime(),
                'refund_reason' => trim(I('refund_reason')),
                'refund_note' => trim(I('refund_note'))
            );
            //退款金额是否符合限制
            if($order_goods_data['refund_price'] <= 0 or $order_goods_data['refund_price'] > $order_detail['refund_totle']){
                $this->error(sprintf('Please type a number between 0 and %s in refund amount.',$order_detail['refund_totle']));
                return;   
            }
            
            //退款数量是否符合限制
            if($order_goods_data['refund_num'] <0 or $order_goods_data['refund_num'] > $order_detail['quantity']){
                $this->error(sprintf('Please type a number between 0 and %s in return amount.',$order_detail['refund_num']));
                return;
            }
            
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($order_goods_data)){
                D('')->rollback();
                $this->error($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            //判断订单的单头的退款状态
            if(!$order_detail['o_refund_status']){ //更改订单单头的退款单状态
                $order_data = array(
                    'order_id' => $order_detail['order_id'],
                    'refund_status' => 1
                );
                if(!$this->_order_mod->create($order_data)){
                    D('')->rollback();
                    $this->error($this->_order_mod->getError());
                    return;
                }
                $this->_order_mod->save();
                $log_data = array(
                    'log_user' => 'buyer|'.$this->visitor->get('user_name'),
                    'order_id' => $order_detail['order_id'],
                    'from_status' => $order_detail['order_status'],
                    'to_status' => $order_detail['order_status'],
                    'from_refund_statu' => $order_detail['refund_status'],
                    'to_refund_status' => $order_data['refund_status'],
                    'note' => 'Refund information edited by customer.',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    D('')->rollback();
                    $this->error('Saving order operation records failed.');
                    return;
                }
            }
            
            //保存退款凭证
            //上传凭证
            $refund_img = '';
            if($_FILES['refund_img']['size'] > 0){
                $upload = new \Think\Upload(array( //图片上传设置
                    'maxSize' => 5*1024*1024, //最大支持上传5M的图片
                    'exts' => 'gif,jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'refund/'
                ));
                if(!$file = $upload->upload($_FILES)){
                    D('')->rollback();
                    $this->error($upload->getError());
                    return;
                }
                $refund_img = $upload->__get('rootPath').$file['refund_img']['savepath'].$file['refund_img']['savename'];
            }
                            
            //保存退款协商记录
            $refund = array(
                'refund_sn' => $order_detail['refund_sn'],
                'refund_user' => 'buyer|'.$this->visitor->get('user_name'),
                'refund_status' => $order_goods_data['refund_status'],
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_type' => $order_goods_data['refund_type'],
                    'refund_price' => $order_goods_data['refund_price'],
                    'refund_reason' => $order_goods_data['refund_reason'],
                    'refund_note' => $order_goods_data['refund_note'],
                    'refund_img' => $refund_img,
                ))
            );
            if(!$this->_refund_mod->create($refund)){
                @unlink($refund_img);
                D('')->rollback();
                $this->error($this->_refund_mod->getError());
                return;
            }
            $this->_refund_mod->add();
            D('')->commit();
            $this->success('Refund applied successfully.',U('/Refund/view',array('id'=>$rec_id)));
        }
    }
    
    /** 取消退款 */
    function cancel(){
        $rec_id = I('id','','intval');
        $order_detail = $this->_get_order_detail($rec_id);
        //检测订单状态
        if($order_detail['refund_status'] !== '11'){
            $this->error('Being processed, refund can not be cancelled.');
            return;
        }
        //取消退款
        $order_goods_data = array(
            'rec_id' => $rec_id,
            'refund_status' => 40,
            'refund_time' => gmtime()
        );
        D('')->startTrans();
        if(!$this->_order_goods_mod->create($order_goods_data)){
            $this->error($this->_order_goods_mod->getError());
            return;
        }
        $this->_order_goods_mod->save();
        
        //检测订单下是否还有退款中的明细
        $where['order_id'] = $order_detail['order_id'];
        $where['refund_status'] = array('not in',array(0,40));
        $where['rec_id'] = array('neq',$rec_id);
        $other_order_goods = $this->_order_goods_mod->where($where)->select();
        if(!$other_order_goods){
            //修改单头状态
            $order_data = array(
                'order_id' => $order_detail['order_id'],
                'refund_status' => 0
            );
            if(!$this->_order_mod->create($order_data)){
                D('')->rollback();
                $this->error($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'buyer|'.$this->visitor->get('user_name'),
                'order_id' => $order_detail['order_id'],
                'from_status' => $order_detail['order_status'],
                'to_status' => $order_detail['order_status'],
                'from_refund_statu' => $order_detail['refund_status'],
                'to_refund_status' => $order_data['refund_status'],
                'note' => '用户取消退款',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('Saving order operation records failed.');
                return;
            }
        }
        
        //记录退款明细
        $refund = array(
            'refund_sn' => $order_detail['refund_sn'],
            'refund_user' => 'buyer|'.$this->visitor->get('user_name'),
            'refund_status' => $order_goods_data['refund_status'],
            'refund_time' => gmtime(),
            'refund_data' => serialize(array(
                'refund_note' => 'Refund application cancelled.'
            ))
        );
        if(!$this->_refund_mod->create($refund)){
            D('')->rollback();
            $this->error($this->_refund_mod->getError());
            return;
        }
        $this->_refund_mod->add();
        D('')->commit();
        $this->success('Refund application cancelled successfully.',U('/Refund/view',array('id'=>$rec_id)));
    }
    
    /** 修改退款信息 */
    function edit(){
        $rec_id = I('id','','intval');
        $order_detail = $this->_get_order_detail($rec_id);
        //检测订单状态是否允许修改
        if(!in_array($order_detail['refund_status'],array('11','40','50'))){
            $this->error('Being processed, refund can not be edited.');
            return;
        }
        $order_detail['refund_totle'] = round(($order_detail['totle_fee'] - $order_detail['shipping_fee']) / $order_detail['goods_amount'] * $order_detail['goods_totle'],2);
        if(!IS_POST){
            $this->assign('refund_reason',get_refund_reason());
            $this->assign('refunds',$this->_refund_mod->_get_refunds($order_detail['refund_sn']));
            $this->assign('order',$order_detail);
            $this->display('./refund.edit');
        }else{
            $order_goods_data = array(
                'rec_id' => $rec_id,
                'refund_type' => I('refund_type',0,'intval'),
                'refund_status' => 11,
                'refund_price' => I('refund_price',0,'doubleval'),
                'refund_num' => I('refund_num',0,'intval'),
                'refund_time' => gmtime(),
                'refund_reason' => trim(I('refund_reason')),
                'refund_note' => trim(I('refund_note'))
            );
            //退款金额是否符合限制
            if($order_goods_data['refund_price'] <= 0 or $order_goods_data['refund_price'] > $order_detail['refund_totle']){
                $this->error(sprintf('Please type a number between 0 and %s in refund amount.',$order_detail['refund_totle']));
                return;   
            }
            
            //退款数量是否符合限制
            if($order_goods_data['refund_num'] <0 or $order_goods_data['refund_num'] > $order_detail['quantity']){
                $this->error(sprintf('Please type a number between 0 and %s in return amount.',$order_detail['refund_num']));
                return;
            }
            
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($order_goods_data)){
                D('')->rollback();
                $this->error($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            //判断订单的单头的退款状态
            if(!$order_detail['o_refund_status']){ //更改订单单头的退款单状态
                $order_data = array(
                    'order_id' => $order_detail['order_id'],
                    'refund_status' => 1
                );
                if(!$this->_order_mod->create($order_data)){
                    D('')->rollback();
                    $this->error($this->_order_mod->getError());
                    return;
                }
                $this->_order_mod->save();
                $log_data = array(
                    'log_user' => 'buyer|'.$this->visitor->get('user_name'),
                    'order_id' => $order_detail['order_id'],
                    'from_status' => $order_detail['order_status'],
                    'to_status' => $order_detail['order_status'],
                    'from_refund_statu' => $order_detail['refund_status'],
                    'to_refund_status' => $order_data['refund_status'],
                    'note' => 'Refund information edited by customer.',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    D('')->rollback();
                    $this->error('Saving order operation records failed.');
                    return;
                }
            }
            
            //保存退款凭证
            //上传凭证
            $refund_img = '';
            if($_FILES['refund_img']['size'] > 0){
                $upload = new \Think\Upload(array( //图片上传设置
                    'maxSize' => 5*1024*1024, //最大支持上传5M的图片
                    'exts' => 'gif,jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'refund/'
                ));
                if(!$file = $upload->upload($_FILES)){
                    D('')->rollback();
                    $this->error($upload->getError());
                    return;
                }
                $refund_img = $upload->__get('rootPath').$file['refund_img']['savepath'].$file['refund_img']['savename'];
            }
                            
            //保存退款协商记录
            $refund = array(
                'refund_sn' => $order_detail['refund_sn'],
                'refund_user' => 'buyer|'.$this->visitor->get('user_name'),
                'refund_status' => $order_goods_data['refund_status'],
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_type' => $order_goods_data['refund_type'],
                    'refund_price' => $order_goods_data['refund_price'],
                    'refund_reason' => $order_goods_data['refund_reason'],
                    'refund_note' => $order_goods_data['refund_note'],
                    'refund_img' => $refund_img,
                ))
            );
            if(!$this->_refund_mod->create($refund)){
                @unlink($refund_img);
                D('')->rollback();
                $this->error($this->_refund_mod->getError());
                return;
            }
            $this->_refund_mod->add();
            D('')->commit();
            $this->success('Refund information edited successfully.',U('/Refund/view',array('id'=>$rec_id)));
            
        }
    }
    
    /** 退回货物 */
    function send(){
        $rec_id = I('id','','intval');
        $order_detail = $this->_get_order_detail($rec_id);
        if(!IS_POST){
            $this->assign('refunds',$this->_refund_mod->_get_refunds($order_detail['refund_sn']));
            $this->assign('order',$order_detail);
            $this->display('./refund.send');
        }else{
            $order_goods_data = array(
                'rec_id' => $rec_id,
                'refund_status' => 22,
                'refund_shipping_name' => trim(I('refund_shipping_name')),
                'refund_invoice_no' => trim(I('refund_invoice_no'))
            );
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($order_goods_data)){
                D('')->rollback();
                $this->error($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            
            //记录退款协商记录
            $refund = array(
                'refund_sn' => $order_detail['refund_sn'],
                'refund_user' => 'buyer|'.$this->visitor->get('user_name'),
                'refund_status' => $order_goods_data['refund_status'],
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_shipping_name' => $order_goods_data['refund_shipping_name'],
                    'refund_invoice_no' => $order_goods_data['refund_invoice_no'],
                ))
            );
            if(!$this->_refund_mod->create($refund)){
                D('')->rollback();
                $this->error($this->_refund_mod->getError());
                return;
            }
            $this->_refund_mod->add();
            D('')->commit();
            $this->success('Product returned successfully.',U('/Refund/view',array('id'=>$rec_id)));
        }
        
    }
    
    function _get_order_detail($rec_id){
        $where['og.rec_id'] = $rec_id;
        $where['o.user_id'] = $this->visitor->get('user_id');
        $order = $this->_order_goods_mod->field('og.*,o.order_sn,o.user_id,o.pay_time,o.goods_amount,o.shipping_fee,o.integral_fee,o.discount_fee,o.refund_fee,o.totle_fee,o.add_time,o.refund_status as o_refund_status')
                 ->join(' as og LEFT JOIN __ORDER__ as o ON og.order_id=o.order_id')
                 ->where($where)->find();
        if(!$order){
            $this->error('Order does not exist, or has been deleted.');
            return;
        }
        return $order;
    }
 }



?>