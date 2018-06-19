<?php
/**
 * 订单单头模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class OrderModel extends Model{
    protected $_validate = array(
        array('goods_amount','require','商品总价不能为空',0),
        array('goods_amount','currency','商品总价格式不正确',0),
        array('shipping_fee','require','运费不能为空',0),
        array('shipping_fee','currency','运费价格格式不正确',0),
        array('integral_fee','require','积分抵扣不能为空',0),
        array('integral_fee','currency','积分抵扣价格格式不正确',0),
        array('discount_fee','require','折扣优惠不能为空',0),
        array('discount_fee','currency','折扣优惠价格格式不正确',0),
        array('totle_fee','require','实收总金额不能为空',0),
        array('totle_fee','currency','实收总金额格式不正确',0),
        //array('shipping_code','require','配送方式code不能为空',0),
        //array('shipping_name','require','配送方式的名称不能为空',0),
        array('invoice_no','require','发货单号不能为空',0)
    );
    
    /** 返利佣金 */
    function deal_rebate($order_id){
        if(empty($order_id)){
            //M()->rollback();
            errorLog('订单ID不能为空.','dealRebate'.M()->getLastInsID());
            return false;
        }
        $order = $this->find($order_id);
        if(empty($order)){
            //M()->rollback();
            errorLog('订单不存在.','dealRebate');
            return false;
        }
        
        if($order['inviter_rebate_done']==1){
            //M()->rollback();
            errorLog('邀请者已经返利完成，请不要重复处理.','dealRebate');
            return false;
        }
        if(!$this->where(array('order_id'=>$order_id))->save(array('inviter_rebate_done'=>1))){
            //M()->rollback();
            errorLog('返利完成标志更新失败.'.M()->getLastSql(),'dealRebate');
            return false;
        }
        
        $user_mod = M('user');
        $user = $user_mod->where(array('user_id'=>$order['user_id']))->find();
    
        $rebate_rate = M('store')->where(array('store_id'=>$this->store_id))->getField('rebate_rate');
        if(empty($rebate_rate)) return false;
    
        
    
        //判断是否有邀请者、佣金
        if(!empty($user['from_code'])){
            $inviter_user = $user_mod->where(array('code'=>$user['from_code']))->find();
            if(!empty($inviter_user)) {
                $inviter_userid = $inviter_user['user_id'];
                //返利金额
                $rebate_money = round($order['totle_fee']*(int)$rebate_rate/100,2);
                $user_ext_data = array(
                    'rebate_total_amount'=>array('exp','rebate_total_amount+'.$rebate_money),
                    'rebate_order_num'=>array('exp','rebate_order_num+1'),
                );
                if(!M('user_ext')->where(array('user_id'=>$inviter_userid))->save($user_ext_data)){
                    //M()->rollback();
                    errorLog('邀请者累计返利更新失败.','dealRebate');
                    return false;
                }
                $data = array(
                    'user_id' => $inviter_userid,
                    'invitee_userid' => $order['user_id'],
                    'invitee_nickname' => $user['nick_name'],
                    'order_id' => $order_id,
                    'order_sn' => $order['order_sn'],
                    'order_amount' => $order['totle_fee'],
                    'rebate_money' => $rebate_money,
                    'is_translate' => 1,  //立即转佣金
                    'is_return' =>0,  //无退款
                    'ctime' => gmtime()
                );
                if(!$rebate_id = M('rebate_log')->add($data)){
                    //M()->rollback();
                    errorLog('邀请者明细佣金处理失败.','dealRebate');
                    return false;
                }
    
                //邀请者增加 用户余额
                if(false===$user_mod->where(array('user_id'=>$inviter_userid))->setInc('balance',$data['rebate_money'])){
                    //M()->rollback();
                    errorLog('邀请者用户余额处理失败.','dealRebate');
                    return false;
                }
                //邀请者资金明细增加
                $finance_data = array(
                    'user_name' => $inviter_user['user_name'],
                    'user_id' => $inviter_userid,
                    'type' => '+',
                    'money' => $data['rebate_money'],
                    'action_type' => 'rebate_log',
                    'key_id' => $rebate_id,
                    'memo' => '邀请用户下单返利',
                    'ctime' => gmtime(),
                    'ip' => get_client_ip(),
                    'admin_id' => 0
                );
                if(!M('finance')->add($finance_data)){
                    //M()->rollback();
                    errorLog('邀请者资金明细增加失败.','dealRebate');
                    return false;
                }
                //站内信通知-邀请者返利返利到账
                $message = '被邀请用户：'.$user['nick_name'].'订单编号：'.$order['order_sn'].',订单实付金额：'.format_price($order['totle_fee']).'，推广用户订单消费返现比例：'.$rebate_rate.'%，返现金额:'.format_price($rebate_money).',已经打入你的账户余额中,请注意查收.';
                if(!sendPmessage('推广者返现到账通知',$message,$inviter_userid,0,'inviter',$order_id)){
                    //$this->rollback();
                    return false;
                }
                
                
                //返利到账短信通知 
                $sms_account_mod = M('sms_account');
                $store_id = $order['store_id'];
                $store = M('store')->where(array('store_id'=>$store_id))->find();
                
                $find = M('sms_account')->where(array('brand_id'=>$order['brand_id']))->find();
                if( !$find ){
                    errorLog('返现短信通知失败,短信账户未开通.','orderRebateSmsError');
                    //return '短信账户未开通.';
                }elseif($find['remnant_num']<1){
                    errorLog('返现短信通知失败,短信账户可用余额不足:'.$store['store_name'],'sendRebateNoticeError');
                    //return '短信账户可用余额不足:'.$store['store_name'];
                }elseif(empty($store['is_rebate_notice'])){
                    errorLog('店铺未设置返现短信通知:'.$store['store_name'],'sendRebateNoticeError');
                }else{
                    $mobiles = explode(',',$inviter_user['mobile']);
                    //短信数量
                    $send_num = count($mobiles);
                    //更新短信账户余额
                    $sdata = array(
                        'remnant_num' => array('exp','remnant_num - '.(int)$send_num ),
                        'used_num' => array('exp','used_num + '.(int)$send_num ),
                    );
                    M()->startTrans();
                    if(!$sms_account_mod->where(array('brand_id'=>$store['brand_id']))->save($sdata)){
                        M()->rollback();
                        errorLog('短信账户余额更新失败.'.M()->getLastSql(),'sendRebateNoticeError');
                        //return '短信账户更新失败.';
                    }
                    
                    $smsdata['storename'] = $store['store_name'].'【返现通知】';
                    $smsdata['sendtime'] = todate($data['ctime']);
                    $smsdata['sendtime'] .= PHP_EOL.'被邀请会员名：'.$user['nick_name'];
                    $smsdata['sendtime'] .= PHP_EOL.'会员订单号：'.$order['order_sn'];
                    $smsdata['sendtime'] .= PHP_EOL.'订单金额：￥'.$order['totle_fee'];
                    $smsdata['sendtime'] .= PHP_EOL.'店铺返现比例：'.$store['rebate_rate'].'%';
                    $smsdata['sendtime'] .= PHP_EOL.'返现金额：￥'.$order['inviter_rebate_fee'];
                    
                    //批量发送短信
                    $return = sendNoticeSms($inviter_user['mobile'],$smsdata);
                    //$ids = array();
                    foreach ($mobiles as $mobile){
                        //短信发送日志记录
                        $dt = array(
                            'store_id' => $store_id,
                            'brand_id' => $store['brand_id'],
                            'ctime' => gmtime(),
                            'status' => 0,
                            'content' => json_encode($smsdata),
                            'type' => 'rebate_notice', //枚举类型：daily_sms-日结短信；comment_notice-差评通知；rebate_notice-返现到账通知；captcha-验证码
                            'mobile' => $mobile,
                            'end_time' => '00:00:00'
                        );
                        $remark = '';
                        if($return === true){
                            $dt['status'] = 1 ;
                        }else{
                            $dt['status'] = 0 ;
                            $dt['remark'] = $remark = 'API方法错误：'.$return->Message;
                        }
                
                        $id = M('send_sms')->add($dt);
                        if(!$id){
                            M()->rollback();
                            errorLog('返现短信通知，发送日志增加失败.'.M()->getLastSql(),'sendRebateNoticeError');
                            //return '返现短信通知，发送日志增加失败.'.M()->getLastSql();
                        }
                        //$ids[] = $id;
                    } //end foreach
                    M()->commit();
                } //end else
                
                
                
            } //inviter_user 
        } //from_code 
        
        return true;
    }
    
    
    


    /** 微信小程序支付-申请退款
     *  成功：return true; 失败：return 错误说明;
     *  /appjson/wxpayMiniRefund?urlencode=false&store_id=1&order_sn=1723850921&openid=o8aT50NEd2TOooxV12kawceqCNrI
     * <xml>
     *      <return_code><![CDATA[SUCCESS]]></return_code>
     <return_msg><![CDATA[OK]]></return_msg>
     <appid><![CDATA[wxba2d4edf8088567b]]></appid>
     <mch_id><![CDATA[1488135302]]></mch_id>
     <nonce_str><![CDATA[BkDqYeDVQYYTDwk2]]></nonce_str>
     <sign><![CDATA[5BCFBA1E396E56FFD2633666B8427CEC]]></sign>
     <result_code><![CDATA[FAIL]]></result_code>
     <err_code><![CDATA[REFUND_FEE_INVALID]]></err_code>
     <err_code_des><![CDATA[累计退款金额大于支付金额]]></err_code_des>
     </xml>
     */
    function wxpayMiniRefund($order_sn,$refund_fee){
         
        if(empty($order_sn)) return ('退款原订单号(order_sn)不能为空.');
    
        $order = $this->where(array('order_sn'=>$order_sn))->find();
        if(empty($order)) return('退款原订单号不存在.');
    
        $refund_fee=I('refund_fee');
        if(empty($refund_fee)) return('退款金额(refund_fee)必须是大于0的整数(单位：分).');
         
        $total_fee= $order['wepay_fee']*100;
        if(empty($total_fee)) return('支付金额必须是大于0的整数(单位：分).');
    
        if($total_fee<($refund_fee+$order['refund_fee']*100)) return('累计退款金额超过了订单的金额.'.$order['wepay_fee']);
    
        $pay_info=(unserialize($order['pay_info']));
    
        $pay_code='Wepay';
        $paymentclass = new \Think\Payment();
        $payment_info = $paymentclass->chekc_enabled_payment($pay_code);
        if($payment_info === false){
            return("{$pay_code} 该支付方式未被安装.");
            return;
        }
        //实例化支付类
        $payment = $paymentclass->set_payment($pay_code);
        
        //$pay_config = unserialize($payment_info['pay_config']); //微信APP支付 
        $pay_config = array( 
            'sh_account'=> '1488135302@1488135302',  //商户账号
            'appid' => 'wxba2d4edf8088567b', //APPID
            'mch_id' => '1488135302',  //商户ID 
            'key' => 'YunmagouYunmagouYunmagouYunmagou', //token
            'url' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
            'trade_type' => 'JSAPI',  //小程序用JSAPI 
        );
        $payment->set_config($pay_config); //加载配置文件
     
        $data=array(
            'attach'=> $order_sn,   //附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'body'=>'餐品订单号：'.$order_sn,
            'out_trade_no'=> $pay_info['out_trade_no'],  //支付流水号   //$orderdata['order_sn']
            'spbill_create_ip'=>get_client_ip(),
            'total_fee'=>$total_fee,   //支付总金额
            'refund_fee'=>$refund_fee,
        );
        $xml=$payment->refund($data);  #var_dump($payment->_msg);var_dump($payment->_data); var_dump($xml);
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xmlarr=xmlToArray($dom->documentElement);   #var_dump($xmlarr); exit;
        $res=current($xmlarr['return_code'][0]);
        if($res=='SUCCESS'){
            if(current($xmlarr['result_code'][0])=='SUCCESS'){
                $return=array(
                    'result_code'=>current($xmlarr['result_code'][0]),
                    'return_msg'=>current($xmlarr['return_msg'][0]),
                    'appid'=>current($xmlarr['appid'][0]),
                    'mch_id'=>current($xmlarr['mch_id'][0]),
                    'nonce_str'=>current($xmlarr['nonce_str'][0]),
                    'sign'=>current($xmlarr['sign'][0]),
                    'result_code'=>current($xmlarr['result_code'][0]),
                    'prepay_id'=>current($xmlarr['prepay_id'][0]),
                    'trade_type'=>current($xmlarr['trade_type'][0])
                );
    
                errorLog('申请退款成功，订单号：'.$order_sn.':'.$refund_fee,'wxpayMiniRefundSUCCESS');
                
                //记录订单操作日志
                $log_data = array(
                    'log_user' => 'system|system',
                    'order_id' => $order['order_id'],
                    'from_status' => $order['order_status'],
                    'to_status' => $order['order_status'],
                    'from_refund_status' => $order['refund_status'],
                    'to_refund_status' => 1,
                    'note' => '微信小程序支付退款成功.'.$_SERVER['REMOTE_ADDR'],
                    'log_time' => gmtime()
                );
                if(!D('order_log')->add($log_data)){
                    //D('')->rollback();
                    return('订单操作日志保存失败');
                }
                
                //更新订单状态
                $this->where(array('order_sn'=>$order_sn))->save(array('refund_status'=>1,'refund_fee'=>array('exp','refund_fee+'.$refund_fee)));
                 
                return true;
                //return('微信小程序支-付申请退款订单请求成功.',1,$return);
            }else{
                return('微信小程序支-付申请退款请求失败:'.current(current($xmlarr['return_msg'])).current(current($xmlarr['err_code_des'])));
            }
        }else{
            return('微信小程序支-付申请退款请求失败:'.current(current($xmlarr['return_msg'])).current(current($xmlarr['err_code_des'])));
        } 
    }
     
    
    
}


?>