<?php
/**
 * APPJSON数据接口 
 * @author Abiao
 * @copyright 2017
 */
namespace Home\Controller;
use Think\Page;

use Think\Controller;
use phpDocumentor\Reflection\Types\String_;
class AppjsonController extends FrontendController{
	var $cdnUrl=null;
	var $appid = null;
	var $secret = null;
	var $sh_account = null;
	var $mch_id = null;
	var $key = null;
	
    function __construct(){
        parent::__construct();
        $this->AppjsonController(); 
    } 
    
    function AppjsonController(){ 
        //APIv1流量统计
        $this->apiv1_traffic(); 
        $this->cdnUrl = 'https://static.graphis.com';
        $this->secret = 'f5683171de3c42314c585839bf49fe7d';
        $this->appid = 'wx2b8ec378bf9b09ac';
        
        $this->sh_account = '1494792202@1494792202';  //商户账号 
        $this->mch_id = '1494792202';  //商户ID
        $this->key = 'ZJZhijian828Zhijian828Zhijian828'; //微信支付API秘钥
    } 
    
    /** 打赏订单-微信小程序、公众号预支付
     *  /appjson/wxPrePay?urlencode=false&store_id=1&order_sn=1735754682&openid=odzWh01_jxzmzui_mddj4v4cD4kI
     *  strtolower($_SERVER['SERVER_NAME']) 兼容测试环境的支付
     * */
    function wxPrePay(){ 
        
        $order_sn=I('order_sn');
        if(empty($order_sn)) $this->toJson('订单号(order_sn)不能为空.');
        
        $order = M('reward_order')->where(array('order_sn'=>$order_sn))->find();
        if(empty($order)) $this->toJson('订单号不存在.');
        
//         $brand_id = $order['brand_id'];
//         //切换品牌
//         $this->change_brand($brand_id);
        
        $pay_code = trim(I('pay_code'));
        $pay_code='Wepay';
        $paymentclass = new \Think\Payment();
        $payment_info = $paymentclass->chekc_enabled_payment($pay_code);
        if($payment_info === false){
            $this->toJson("{$pay_code} 该支付方式未被安装.");
            return;
        }
        //实例化支付类
        $payment = $paymentclass->set_payment($pay_code);
        //$payment->set_config(unserialize($payment_info['pay_config']));
    
        $openid = I('openid','','trim');
        if(empty($openid)) $this->error('微信用户的openid不能为空.');
    
        $pay_config = array( //value为默认值.
            'sh_account'=> $this->sh_account,  //商户账号
            'appid' => $this->appid,
            'mch_id' => $this->mch_id,  //商户ID
            'notify_url' => 'http://'.strtolower($_SERVER['SERVER_NAME']).'/appjson/order_notify_url',  //https:
            'key' => $this->key,
            'url' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
            'trade_type' => 'JSAPI',  //小程序,公众号用JSAPI
            'openid' => $openid  //用户唯一标识 例如：o8aT50NEd2TOooxV12kawceqCNrI
        );
        $payment->set_config($pay_config);
        
    
        $total_fee=($order['total_amount']>0)?$order['total_amount']*100:$order['total_amount']*100;
        if(empty($total_fee)) $this->toJson('支付金额必须是大于0的整数(单位：分).');
                 
        $data=array(
            'attach'=> $order_sn,   //附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'body'=>'订单号：'.$order_sn,
            'out_trade_no'=> Date('YmdHis').rand(0000, 9999),  //支付流水号   //$orderdata['order_sn']
            'spbill_create_ip'=>get_client_ip(),
            'total_fee'=>$total_fee,   //支付总金额
        );
        $xml=$payment->payment($data);
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xmlarr=xmlToArray($dom->documentElement);
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
                //session('[start]'); 小程序浏览器不支持session？
                //session('prepay_id',$return['prepay_id']);
                errorLog(' 订单号：'.$order_sn.',预支付prepay_id:'.$return['prepay_id'],'prepay_id');
                //更新预支付ID
                M('reward_order')->where(array('order_sn'=>$order_sn))->save(array('prepay_id'=>$return['prepay_id']));
                $this->toJson('微信预支付订单请求成功.',1,$return);
                #echo '<a target="_blank" href="'.$return['mweb_url'].'">微信预支付订单请求成功.</a>';
            }else{
                $this->toJson('微信预支付订单请求失败.',0,$xmlarr);  //如果是商户订单号重复，一定要保证金额和单号一致
            }
        }else{
            $this->toJson('微信预支付订单请求失败.',0,$xmlarr);
        }
    
    }
    
    
    
    /** 餐品订单-微信h5预支付
     *  /appjson/wxH5Pay?urlencode=false&store_id=1&order_sn=1735754682&openid=o0ORn5BO3wros2UDsnxSWI8O4QFM
     *  http://www.graphis.club/appjson/wxH5Pay?urlencode=false&store_id=1&order_sn=1735754682&openid=odzWh01_jxzmzui_mddj4v4cD4kI
     *  strtolower($_SERVER['SERVER_NAME']) 兼容测试环境的支付
     * */
    function wxH5Pay(){
    
        $order_sn=I('order_sn');
        if(empty($order_sn)) $this->toJson('订单号(order_sn)不能为空.');
    
        $order = M('reward_order')->where(array('order_sn'=>$order_sn))->find();
        if(empty($order)) $this->toJson('订单号不存在.');
    
        //         $brand_id = $order['brand_id'];
        //         //切换品牌
        //         $this->change_brand($brand_id);
    
        $pay_code = trim(I('pay_code'));
        $pay_code='Wepay';
        $paymentclass = new \Think\Payment();
        $payment_info = $paymentclass->chekc_enabled_payment($pay_code);
        if($payment_info === false){
            $this->toJson("{$pay_code} 该支付方式未被安装.");
            return;
        }
        //实例化支付类
        $payment = $paymentclass->set_payment($pay_code);
        //$payment->set_config(unserialize($payment_info['pay_config']));
    
        $openid = I('openid','','trim');
        //if(empty($openid)) $this->error('微信用户的openid不能为空.');
    
        $pay_config = array( //value为默认值.
            'sh_account'=> $this->sh_account,  //商户账号
            'appid' => $this->appid,
            'mch_id' => $this->mch_id,  //商户ID
            'notify_url' => 'http://www.graphis.ltd/appjson/order_notify_url',    //strtolower($_SERVER['SERVER_NAME'])
            'key' => $this->key,
            'url' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
            'trade_type' => 'MWEB',  //小程序用JSAPI必须,MWEB(H5)，APP可选
            'openid' => $openid,  //用户唯一标识openid 例如：odzWh01_jxzmzui_mddj4v4cD4kI
        );
        $payment->set_config($pay_config);
    
    
        $total_fee=($order['total_amount']>0)?$order['total_amount']*100:$order['total_amount']*100;
        if(empty($total_fee)) $this->toJson('支付金额必须是大于0的整数(单位：分).');
         
        $data=array(
            'attach'=> $order_sn,   //附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'body'=>'打赏订单号：'.$order_sn,
            'out_trade_no'=> Date('YmdHis').rand(0000, 9999),  //支付流水号   //$orderdata['order_sn']
            'spbill_create_ip'=>get_client_ip(),
            'total_fee'=>$total_fee,   //支付总金额
            'scene_info' => '{"h5_info": {"type":"Wap","wap_url": "http://www.graphis.ltd","wap_name": "graphis"}} '
        );
        #$data['spbill_create_ip'] = '101.71.38.25'; 
        $xml=$payment->wxH5pay($data);   #var_dump($xml);exit;
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xmlarr=xmlToArray($dom->documentElement);
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
                    'trade_type'=>current($xmlarr['trade_type'][0]),
                    'mweb_url'=>current($xmlarr['mweb_url'][0]),  //h5支付独有
                );
                //session('[start]'); 小程序浏览器不支持session？
                //session('prepay_id',$return['prepay_id']);
                errorLog(' 订单号：'.$order_sn.',预支付prepay_id:'.$return['prepay_id'],'prepay_id');
                //更新预支付ID
                M('reward_order')->where(array('order_sn'=>$order_sn))->save(array('prepay_id'=>$return['prepay_id']));
                $this->toJson('微信预支付订单请求成功..',1,$return);
                #echo '<a target="_blank" href="'.$return['mweb_url'].'">微信预支付订单请求成功.</a>';
                #echo '<script>self.location="'.$return['mweb_url'].'"</script>';
            }else{
                $this->toJson('微信预支付订单请求失败.',0,$xmlarr);  //如果是商户订单号重复，一定要保证金额和单号一致
            }
        }else{
            $this->toJson('微信预支付订单请求失败.',0,$xmlarr);
        }
    
    }
    
    
    /**
     * 餐品订单-微信支付结果接收通知
     * 库存处理，余额处理，日志记录，推广者佣金处理
     * */
    function order_notify_url(){
        /*
         $postStr ='
         <xml>
         <appid><![CDATA[wxba2d4edf8088567b]]></appid>
         <attach><![CDATA[充值订单-小程序微信支付]]></attach>
         <bank_type><![CDATA[CFT]]></bank_type>
         <cash_fee><![CDATA[10]]></cash_fee>
         <fee_type><![CDATA[CNY]]></fee_type>
         <is_subscribe><![CDATA[N]]></is_subscribe>
         <mch_id><![CDATA[1488135302]]></mch_id>
         <nonce_str><![CDATA[2573]]></nonce_str>
         <openid><![CDATA[o8aT50NEd2TOooxV12kawceqCNrI]]></openid>
         <out_trade_no><![CDATA[1724796572]]></out_trade_no>
         <result_code><![CDATA[SUCCESS]]></result_code>
         <return_code><![CDATA[SUCCESS]]></return_code>
         <sign><![CDATA[CA9513CC863CE731BEB1F3DB7D6E9857]]></sign>
         <time_end><![CDATA[20170905172709]]></time_end>
         <total_fee>10</total_fee>
         <trade_type><![CDATA[JSAPI]]></trade_type>
         <transaction_id><![CDATA[4006332001201709050376637009]]></transaction_id>
         </xml>';
    
         //微信服务商return:
         $postStr ='<xml><appid><![CDATA[wx73a3c60c7eb2e975]]></appid>
         <attach><![CDATA[1804080140026]]></attach>
         <bank_type><![CDATA[CFT]]></bank_type>
         <cash_fee><![CDATA[1]]></cash_fee>
         <fee_type><![CDATA[CNY]]></fee_type>
         <is_subscribe><![CDATA[Y]]></is_subscribe>
         <mch_id><![CDATA[1500629801]]></mch_id>
         <nonce_str><![CDATA[3173]]></nonce_str>
         <openid><![CDATA[ogoL40mRMcZNUNgfAFb1PEAvSKSA]]></openid>
         <out_trade_no><![CDATA[1804080140026]]></out_trade_no>
         <result_code><![CDATA[SUCCESS]]></result_code>
         <return_code><![CDATA[SUCCESS]]></return_code>
         <sign><![CDATA[863AD52D536D38368AB5ADBD2C2A9CD1]]></sign>
         <sub_appid><![CDATA[wxfda89a25b2a1a8ab]]></sub_appid>
         <sub_is_subscribe><![CDATA[N]]></sub_is_subscribe>
         <sub_mch_id><![CDATA[1501466281]]></sub_mch_id>
         <sub_openid><![CDATA[oWZQe0R0qNUTZhfTLMshDdfWuMfg]]></sub_openid>
         <time_end><![CDATA[20180408164803]]></time_end>
         <total_fee>1</total_fee>
         <trade_type><![CDATA[JSAPI]]></trade_type>
         <transaction_id><![CDATA[4200000052201804084561753654]]></transaction_id>
         </xml>';
         */
        //接收微信支付通知信封
        $postStr=file_get_contents('php://input');
        if(empty($postStr)) $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if(empty($postStr)) $postStr = '';
         
        $return_code=I('return_code');
        $return_msg=I('return_msg');
         
        //日志文件
        $logfile='./Shop/Paylog/wepayNotify'.date('Ymd').'.log';
        error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$postStr.PHP_EOL.PHP_EOL, 3, $logfile);
         
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) {
            $msg='parse xml error';
            error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$msg.PHP_EOL.PHP_EOL, 3, $logfile);
            die($msg);
        }
        if ($postObj->return_code != 'SUCCESS') {
            error_log(' return_code:'.$postObj->return_code.PHP_EOL.PHP_EOL, 3, $logfile);
            die($postObj->return_msg);
        }
        if ($postObj->result_code != 'SUCCESS') {
            error_log(' result_code:'.$postObj->result_code.PHP_EOL.PHP_EOL, 3, $logfile);
            die($postObj->err_code);
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
    
        #$brand_id = getNameById('brand_id', 'order', 'order_sn', $arr['attach']); //out_trade_no
        //切换品牌
        #$this->change_brand($brand_id);
    
        $config = array(
            'mch_id' => $this->mch_id,
            'appid' => $this->appid,
            'key' => $this->key,
        );
    
        $order_sn = $postObj->attach;
        if (self::getSign($arr, $config['key']) == $postObj->sign) {  //签名验证成功
            $order_mod = D('rewardOrder');
            $order=$order_mod->where(array('order_sn'=>$order_sn))->find();  //$postObj->out_trade_no
//             $user_id = $order['user_id'];
//             $user = M('user')->where(array('user_id'=>$user_id))->field('user_name')->find();
    
//             //判断是否待支付状态,如果不是，拒绝继续执行业务逻辑
//             if(!in_array($order['order_status'], array('11','15'))){  //15兼容服务商支付
//                 error_log(PHP_EOL.'order_status error. order_status:'.$order['order_status'],3,$logfile);
//                 return ;
//             } 

            D()->startTrans();
                 
            $str = $order['order_sn'].$order['ip'].$order['album_id'];
            //修改订单状态-20
            $order_data=array(
                'order_status'=>20,
                'pay_code'=>'Wepay',
                'pay_name'=>'微信支付',
                'pay_time'=>gmtime(), 
                'pay_info'=>serialize($arr),                
                'command_code' => strtoupper(short_md5($str)),
                'notify_ip' => get_client_ip(),
                'notify_time' => date('Y-m-d H:i:s'),
                'notify_agent' => $_SERVER['HTTP_USER_AGENT'],
                'platform_trade_no'=> $arr['out_trade_no']
            );
            $map=array(
                'order_sn'=>$order_sn,
            ); 
            $res = $order_mod->where($map)->save($order_data);
            if($res==false){
                M()->rollback();
                fwlog('订单通知保存失败错误','alipayWapNotice');
                $this->toJson('订单通知保存失败错误.');
            }  
            
            $album = M('album')->where('id='.$order['album_id'])->find();
            //更新累计打赏金额
            $dt = array(
                'total_reward_times'=>$album['total_reward_times']+1,
                'total_reward_fee'=>$album['total_reward_fee']+$order['total_amount']
            );
            M('album')->where(array('id'=>$order['album_id']))->save($dt);
              
            
            D()->commit();
              
    
            //通知微信信封格式
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    
            error_log(date('Y-m-d H:i:s').' {Paid SUCCESS.}'.PHP_EOL.PHP_EOL, 3, $logfile);
     
             
        }else{ //签名验证失败
            error_log(date('Y-m-d H:i:s').' {Sign Failed.}'.PHP_EOL.'=================================='.PHP_EOL.$postStr.PHP_EOL.PHP_EOL, 3, $logfile);
            echo ' {Sign Failed.}';
        }
    }
    
    


    /** 微信支付验签
     * 例如：
     * appid：  wxd930ea5d5a258f4f
     * mch_id：  10000100
     * device_info： 1000
     * Body：  test
     * nonce_str： ibuaiVcKdpRxkhJA
     * 第一步：对参数按照 key=value 的格式，并按照参数名 ASCII 字典序排序如下：
     * stringA="appid=wxd930ea5d5a258f4f&body=test&device_info=1000&mch_i
     * d=10000100&nonce_str=ibuaiVcKdpRxkhJA";
     * 第二步：拼接支付密钥：
     * stringSignTemp="stringA&key=192006250b4c09247ec02edce69f6a2d"
     * sign=MD5(stringSignTemp).toUpperCase()="9A0A8659F005D6984697E2CA0A9CF3B7"
     */
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
    
    
}