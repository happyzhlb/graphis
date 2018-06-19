<?php
/**
 * 支付方式控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class PaymentController extends BackendController{
    var $_payment_mod = null;
    function __construct(){
        parent::__construct();
        $this->PaymentController();
    }
    function PaymentController(){
        $this->_payment_mod = D('Payment');
    }
    
    /** 支付方式列表 */
    function index(){
        $pay_list = $this->_payment_mod->select();
        $payment = new \Think\Payment();
        $payments = $payment->get_payments(); 
        if(is_array($pay_list)){
            foreach($pay_list as $key => $value){
                $payments[$value['pay_code']]['pay_name'] = $value['pay_name'];
                $payments[$value['pay_code']]['pay_desc'] = $value['pay_desc'];
                $payments[$value['pay_code']]['sort_order'] = $value['sort_order']; 
                if($value['enabled']){
                    $payments[$value['pay_code']]['install'] = 1;   
                }
            }
        }  
        array_sort($payments, 'sort_order');
        $this->assign('payments',$payments);
        $this->display('./payment.index');   
    }
    
    /** 安装支付方式 */
    function install(){
        $code =  trim(I('code'));
        $payment_info = $this->_payment_mod->getByPay_code($code);
        if($payment_info['enabled']){
            $this->error('支付方式已经安装，不能重复安装');
            return;
        }
        $payment = new \Think\Payment($code);
        $modules = $payment->_get_module($payment_info);
        if(!IS_POST){
            $this->assign('payment',$modules);
            $this->display('./payment.form');
        }else{
            $data = array(
                'pay_code' => $code,
                'pay_name' => trim(I('pay_name')),
                'pay_desc' => I('pay_desc'),
                'pay_config' => serialize(I('pay_config')),
                'sort_order' => I('sort_order','','intval'),
                'enabled' => 1,
                'is_online' => $modules['is_online']
            );
            if(!$payment_info){ //支付方式已经存在
                if(!$this->_payment_mod->add($data)){
                    $this->error('支付方式安装失败');
                    return;
                }   
            }else{
                $data['pay_id'] = $payment_info['pay_id'];
                if(!$this->_payment_mod->save($data)){
                    $this->error('支付方式安装失败');
                    return;
                }
            }
            $this->success('支付方式安装成功',U('/Admin/Payment'));
        }
    }
    
    /** 卸载支付方式 */
    function uninstall(){
        $code = trim(I('code'));
        $payment_info = $this->_payment_mod->field('pay_id,enabled')->getByPay_code($code);
        if(!$payment_info){
            $this->error('支付方式不存在，或者已经被删除');
            return;
        }
        $payment_info['enabled'] = 0;
        if(!$this->_payment_mod->save($payment_info)){
            $this->error('支付方式卸载失败');
            return;
        }
        $this->success('支付方式卸载成功',U('/Admin/Payment'));
    }
    
    /** 编辑支付方式 */
    function edit(){
        $code = trim(I('code'));
        $payment_info = $this->_payment_mod->getByPay_code($code);
        if(!$payment_info['enabled']){
            $this->error('支付方式还没有安装，不能进行编辑');
            return;
        }
        $payment = new \Think\Payment($code);
        $modules = $payment->_get_module($payment_info);
        if(!IS_POST){
            $this->assign('payment',$modules);
            $this->display('./payment.edit');
        }else{
            $data = array(
                'pay_id' => $payment_info['pay_id'],
                'pay_code' => $code,
                'pay_name' => trim(I('pay_name')),
                'pay_desc' => I('pay_desc'),
                'pay_config' => serialize(I('pay_config')),
                'sort_order' => I('sort_order','','intval'),
            );
            $this->_payment_mod->save($data);
            $this->success('支付方式编辑成功',U('/Admin/Payment'));
            
        }
    }
}


?>