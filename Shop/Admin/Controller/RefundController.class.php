<?php
/**
 * 退款控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class RefundController extends BackendController{
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    var $_refund_mod = null;
    function __construct(){
        parent::__construct();
        $this->RefundController();
    }
    function RefundController(){
        $this->_order_mod = D('Order');
        $this->_order_goods_mod = D('OrderGoods');
        //$this->_order_log_mod = D('OrderLog');
        $this->_refund_mod = D('Refund');
    }
    
    //申请退款
    function apply(){
        $rec_id = I('id','','intval');
        $order_goods = $this->_order_goods_mod
                       ->field('og.*,o.order_sn,o.user_id,o.pay_time,o.goods_amount,o.shipping_fee,o.integral_fee,o.discount_fee,o.refund_fee,o.totle_fee,o.add_time')
                       ->join(' as og LEFT JOIN __ORDER__ as o ON og.order_id = o.order_id')
                       ->where("og.rec_id={$rec_id}")
                       ->find();
        //var_dump($this->_order_goods_mod->getLastSql());exit;
        if(!$order_goods){
            $this->error('订单明细不存在，或已被删除');
            return;
        }
        //检测退款状态
        if($order_goods['refund_stauts']){
            $this->error('退款已经申请，不能重复申请');
            return;
        }
        
        //检测订单状态是否允许退款
        if(!in_array($order_goods['order_status'],array(20,30,40))){
            $this->error('不是已付款订单不能退款');
            return;
        }
        
        //判断是否超过允许退款时间
        $nowtime = gmtime();
        $allow_max_time = $order_goods['pay_time'] + C('allow_refund_days') * 60*60*24;
        if($nowtime > $allow_max_time){
            $this->error(sprintf('从付款时间起%s天以内的订单可以申请退款，您已经超过时间限制，无法退款',C('allow_refund_days')));
            return;
        }
        //计算最多多少金额
        $order_goods['can_refund_price'] = round(($order_goods['totle_fee'] - $order_goods['shipping_fee']) / $order_goods['goods_amount'] * $order_goods['goods_totle'],2);
        if(!IS_POST){
            $this->assign('refund_reason',get_refund_reason());
            $this->assign('order',$order_goods);
            $this->display('./refund.apply');
        }else{
            $data =  array(
                'rec_id' => $rec_id,
                'refund_sn' => $order_goods['refund_sn']?$order_goods['refund_sn']:_gen_refund_sn(),
                'refund_type' => I('refund_type','','intval'),
                'refund_status' => 11,
                'refund_price' => number_format(I('refund_price','','doubleval'), 2, '.', ''),
                'refund_num' => I('refund_num',0,'intval'),
                'refund_time' => gmtime(),
                'refund_reason' => trim(I('refund_reason')),
                'refund_note' => trim(I('refund_note'))
            );
            //判断退款金额和退款数量
            if($data['refund_price'] > $order_goods['can_refund_price']){
                $this->error('退款金额超出允许范围');
                return;
            }
            if($data['refund_num'] > $order_goods['quantity']){
                $this->error('退货数量超过允许范围');
                return;
            }
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($data)){
                D('')->rollback();
                $this->error($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
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
            $_user_mod = D('User');
            $user_name = current($_user_mod->field('user_name')->find($order_goods['user_id']));
            $refund = array(
                'refund_sn' => $data['refund_sn'],
                'refund_user' => 'buyer|'.$user_name,
                'refund_status' => $data['refund_status'],
                'refund_time' => $data['refund_time'],
                'refund_data' => serialize(array(
                    'refund_type' => $data['refund_type'],
                    'refund_price' => $data['refund_price'],
            		'refund_num' => $data['refund_num'],
                    'refund_reason' => $data['refund_reason'],
                    'refund_note' => $data['refund_note'],
                    'refund_img' => C('site_url').$refund_img, 
                ))
            );
            if(!$this->_refund_mod->create($refund)){
                D('')->rollback();
                @unlink($refund_img);
                $this->error($this->_refund_mod->getError());
                return;
            }
            $this->_refund_mod->add();
            //修改订单退款状态
            $order_info = array(
                'order_id' => $order_goods['order_id'],
                'refund_status' => 1,
                //'refund_fee' => $order_goods['refund_fee'] + $data['refund_price'],
            );
            $this->_order_mod->save($order_info);
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|'.$this->visitor->get('user_name'),
                'order_id' => $order_goods['order_id'],
                'from_status' => $order_goods['order_status'],
                'to_status' => $order_goods['order_status'],
                'from_refund_statu' => $order_goods['refund_status'],
                'to_refund_status' => $order_info['refund_status'],
                'note' => '管理员创建退款单',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                @unlink($refund_img);
                $this->error('订单操作日志保存失败');
                return;
            }
            D('')->commit();
            $this->success('退款单创建成功',U('view',array('id'=>$order_goods['rec_id'])));
        }
    }
    
    /** 退款明细 */
    function view(){
        $rec_id = I('id','','intva');
        $rec_id = I('id','','intval');
        $order_goods = $this->_order_goods_mod
                       ->field('og.*,o.order_sn,o.user_id,o.pay_time,o.goods_amount,o.shipping_fee,o.integral_fee,o.discount_fee,o.refund_fee,o.totle_fee,o.add_time,o.outer_order_sn,o.pay_info,o.pay_code')
                       ->join(' as og LEFT JOIN __ORDER__ as o ON og.order_id = o.order_id')
                       ->where("og.rec_id={$rec_id}")
                       ->find();
        	#echo M()->getLastsql();
        if(!$order_goods){
            $this->error('订单明细不存在，或已被删除');
            return;
        }
        if(!IS_POST){
            //读取退货地址
            $_region_mod = D('Region');
            $region_map['region_id'] = array('in',array(C('country'),C('state')));
            $region = $_region_mod->field('region_name')->where($region_map)
                      ->order('region_id ASC')->select();
            $address['country'] = $region[0]['region_name'];
            $address['state'] = $region[1]['region_name'];
            $address['city'] = C('city');
            $address['address'] = C('address');
            $address['zipcode'] = C('zipcode');
            $address['linkman'] = C('linkman');
            $address['telephone'] = C('telephone');
            $this->assign('address',$address);
            $refunds=$this->_refund_mod->_get_refunds($order_goods['refund_sn']); // dump($refunds);
	#var_dump($order_goods);
            $this->assign('refunds',$refunds);
            $this->assign('order',$order_goods);
            $this->display('./refund.view');
        }else{
            //判断退款单状态是否为已申请
            if($order_goods['refund_status'] != 11){
                $this->error('退款单状态不正确，处理失败');
                return;
            }
            $refund_status = I('refund_status',0,'intval');
            $data = array(
                'rec_id' => $rec_id,
                'refund_status' => $refund_status,
                'refund_time' => gmtime() 
            );
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($data)){
                D('')->rollback();
                $this->error($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            
            //保存协商记录
            if($_FILES['refund_img']['size'] > 0){ //上传了退款凭证
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
            $refund = array(
                'refund_sn' => $order_goods['refund_sn'],
                'refund_user' => 'admin|'.$this->visitor->get('user_name'),
                'refund_status' => $refund_status,
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_address' => trim(I('refund_address')),
                    'refund_note' => trim(I('refund_note')),
                    'refund_img' => C('site_url').$refund_img
                ))
            );
            if(!$this->_refund_mod->create($refund)){
                D('')->rollback();
                @unlink($refund_img);
                $this->error($this->_refund_mod->getError());
                return;
            }
            $this->_refund_mod->add();
            //拒绝退款，处理订单头的退款单状态
            if($refund_status == 50){ //拒绝退款
                //检测订单下面是否还有其他处于退款中的订单明细
                $where = array();
                $where['order_id'] = $order_goods['order_id'];
                $where['refund_status'] = array('neq',0);
                $where['rec_id'] = array('neq',$rec_id);
                $other_order_goods = $this->_order_goods_mod->where($where)->select();
                if(!$other_order_goods){ //不存在则修改订单头的退款状态
                    $order_data = array(
                        'order_id' => $order_goods['order_id'],
                        'refund_status' => 0,
                    );
                    if(!$this->_order_mod->save($order_data)){
                        D('')->rollback();
                        $this->error('退款处理失败');
                        return;
                    }
                    //保存订单操作记录
                    //记录订单操作日志
                    $log_data = array(
                        'log_user' => 'admin|'.$this->visitor->get('user_name'),
                        'order_id' => $order_goods['order_id'],
                        'from_status' => $order_goods['order_status'],
                        'to_status' => $order_goods['order_status'],
                        'from_refund_statu' => $order_goods['refund_status'],
                        'to_refund_status' => $order_data['refund_status'],
                        'note' => '管理员拒绝退款',
                        'log_time' => gmtime()
                    );
                    if(!$this->_order_log_mod->add($log_data)){
                        D('')->rollback();
                        $this->error('订单操作日志保存失败');
                        return;
                    }
                }
            }elseif($refund_status == 20){ //无退货同意退款调用支付的退款接口
                //var_dump($order_goods);
                $paymentclass = new \Think\Payment();
                $payment_info = $paymentclass->chekc_enabled_payment($order_goods['pay_code']);
                if($payment_info === false){
                    $this->error('The payment does not exist.');
                    return;
                }
                //实例化支付类
                $payment = $paymentclass->set_payment($order_goods['pay_code']);
                $payment->set_config(unserialize($payment_info['pay_config']));
                $order_goods['pay_info'] = unserialize($order_goods['pay_info']);
                $result = $payment->credit($order_goods['outer_order_sn'],$order_goods['refund_price'],$order_goods['pay_info']['card_no']);
                if($result === false){
                    D('')->rollback();
                    $this->error($payment->getError());
                    return;
                }else{ //调用退款接口成功
                    $refund_data = array(
                        'rec_id' => $rec_id,
                        'refund_status' => 33,
                        'refund_time' => gmtime()
                    );
                    if(!$this->_order_goods_mod->create($refund_data)){
                        D('')->rollback();
                        $this->error($this->_order_goods_mod->getError());
                        return;
                    }
                    $this->_order_goods_mod->save();
                    //保存协商记录
                    $refund = array(
                        'refund_sn' => $order_goods['refund_sn'],
                        'refund_user' => 'admin|'.$this->visitor->get('user_name'),
                        'refund_status' => 33,
                        'refund_time' => $refund_data['refund_time'],
                        'refund_data' => serialize(array(
                            'refund_note' => '退款成功.',
                        ))
                    );
                    if(!$this->_refund_mod->create($refund)){
                        D('')->rollback();
                        @unlink($refund_img);
                        $this->error($this->_refund_mod->getError());
                        return;
                    }
                    $this->_refund_mod->add();
                    //检测订单明细是否还有存在其他的明细，明细状态
                    $where = array();
                    $where['order_id'] = $order_goods['order_id'];
                    $where['rec_id'] = array('neq',$rec_id);
                    $order_refund_status = 1; //订单单头状态为退款中状态
                    $other_order_goods = $this->_order_goods_mod->where($where)->select();
                    if(!$other_order_goods){ //没有其他明细
                        $order_refund_status = 2;  
                    }else{
                        $where['refund_status'] = array('not in','0,33');
                        $other_order_goods = $this->_order_goods_mod->where($where)->select();
                        if(!$other_order_goods){ //没有其他退款中的订单
                            $where['refund_status'] = 0;
                            $other_order_goods = $this->_order_goods_mod->where($where)->select();
                            if(!$other_order_goods){
                                $order_refund_status = 2;
                            }else{
                                $order_refund_status = 0;
                            }
                        }else{
                            $order_refund_status = 1;
                        }
                    }
                    if($order_refund_status !== 1){ //订单单头退款装填改变
                        $order_data = array(
                            'order_id' => $order_goods['order_id'],
                            'refund_status' => $order_refund_status,
                        );
                        if(!$this->_order_mod->save($order_data)){
                            D('')->rollback();
                            $this->error('退款处理失败');
                            return;
                        }
                        //保存订单操作记录
                        //记录订单操作日志
                        $log_data = array(
                            'log_user' => 'admin|'.$this->visitor->get('user_name'),
                            'order_id' => $order_goods['order_id'],
                            'from_status' => $order_goods['order_status'],
                            'to_status' => $order_goods['order_status'],
                            'from_refund_statu' => $order_goods['refund_status'],
                            'to_refund_status' => $order_data['refund_status'],
                            'note' => '管理员同意退款',
                            'log_time' => gmtime()
                        );
                        if(!$this->_order_log_mod->add($log_data)){
                            D('')->rollback();
                            $this->error('订单操作日志保存失败');
                            return;
                        }
                    }
                }
            }
            D('')->commit();
            $this->success('退款处理成功',U('view',array('id'=>$rec_id)));
        }
    }
    
    /** 确认收到退货 */
    function confirm(){
        $rec_id = I('id','','intval');
        $order_goods = $this->_order_goods_mod->find($rec_id);
        if(!$order_goods){
            $this->error('订单明细不存在，或已被删除');
            return;
        }
        $data = array(
            'rec_id' => $rec_id,
            'refund_status' => 30,
            'refund_time' => gmtime()
        );
        D('')->startTrans();
        if(!$this->_order_goods_mod->create($data)){
            D('')->rollback();
            $this->error($this->_order_goods_mod->getError());
            return;
        }
        $this->_order_goods_mod->save();
        //保存退款
        $refund = array(
            'refund_sn' => $order_goods['refund_sn'],
            'refund_user' => 'admin|'.$this->visitor->get('user_name'),
            'refund_status' => $data['refund_status'],
            'refund_time' => $data['refund_time'],
            'refund_data' => serialize(array(
                'refund_note' => trim(I('refund_note'))
            ))
        );
        if(!$this->_refund_mod->create($refund)){
            D('')->rollback();
            $this->error($this->_refund_mod->getError());
            return;
        }
        $this->_refund_mod->add();
        //调用支付的退款接口
        $order = $this->_order_mod->find($order_goods['order_id']);
        $paymentclass = new \Think\Payment();
        $payment_info = $paymentclass->chekc_enabled_payment($order['pay_code']);
        if($payment_info === false){
            $this->error('The payment does not exist.');
            return;
        }
        //实例化支付类
        $payment = $paymentclass->set_payment($order['pay_code']);
        $payment->set_config(unserialize($payment_info['pay_config']));
        $order['pay_info'] = unserialize($order['pay_info']);
        $result = $payment->credit($order['outer_order_sn'],$order_goods['refund_price'],$order['pay_info']['card_no']);
        if($result === false){
            D('')->rollback();
            $this->error($payment->getError());
            return;
        }else{ //调用退款接口成功
            $refund_data = array(
                'rec_id' => $rec_id,
                'refund_status' => 33,
                'refund_time' => gmtime()
            );
            if(!$this->_order_goods_mod->create($refund_data)){
                D('')->rollback();
                $this->error($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            //保存协商记录
            $refund = array(
                'refund_sn' => $order_goods['refund_sn'],
                'refund_user' => 'admin|'.$this->visitor->get('user_name'),
                'refund_status' => 33,
                'refund_time' => $refund_data['refund_time'],
                'refund_data' => serialize(array(
                    'refund_note' => 'Refund has been returned.',
                ))
            );
            if(!$this->_refund_mod->create($refund)){
                D('')->rollback();
                @unlink($refund_img);
                $this->error($this->_refund_mod->getError());
                return;
            }
            $this->_refund_mod->add();
            //检测订单明细是否还有存在其他的明细，明细状态
            $where = array();
            $where['order_id'] = $order_goods['order_id'];
            $where['rec_id'] = array('neq',$rec_id);
            $order_refund_status = 1; //订单单头状态为退款中状态
            $other_order_goods = $this->_order_goods_mod->where($where)->select();
            if(!$other_order_goods){ //没有其他明细
                $order_refund_status = 2;  
            }else{
                $where['refund_status'] = array('not in','0,33');
                $other_order_goods = $this->_order_goods_mod->where($where)->select();
                if(!$other_order_goods){ //没有其他退款中的订单
                    $where['refund_status'] = 0;
                    $other_order_goods = $this->_order_goods_mod->where($where)->select();
                    if(!$other_order_goods){
                        $order_refund_status = 2;
                    }else{
                        $order_refund_status = 0;
                    }
                }else{
                    $order_refund_status = 1;
                }
            }
            if($order_refund_status !== 1){ //订单单头退款装填改变
                $order_data = array(
                    'order_id' => $order_goods['order_id'],
                    'refund_status' => $order_refund_status,
                );
                if(!$this->_order_mod->save($order_data)){
                    D('')->rollback();
                    $this->error('退款处理失败');
                    return;
                }
                //保存订单操作记录
                //记录订单操作日志
                $log_data = array(
                    'log_user' => 'admin|'.$this->visitor->get('user_name'),
                    'order_id' => $order_goods['order_id'],
                    'from_status' => $order['order_status'],
                    'to_status' => $order['order_status'],
                    'from_refund_statu' => $order['refund_status'],
                    'to_refund_status' => $order_data['refund_status'],
                    'note' => '管理员同意退款',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    D('')->rollback();
                    $this->error('订单操作日志保存失败');
                    return;
                }
            }
        }
        D('')->commit();
        $this->success('确认收货退货成功',U('view',array('id'=>$rec_id)));
    }
}


?>