<?php
/**
 * 定时任务控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Controller;
//use Admin\Controller\AdminController;
//use Admin\Controller\TradetailController;
use Think\Controller;
class CronController extends FrontendController{
//    var $_order_mod = null;
//    var $_order_goods_mod = null;
//    var $_order_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->CronController();
    }
    function CronController(){
//        $this->_order_mod = M('Order');
//        $this->_order_goods_mod = M('OrderGoods');
//        $this->_order_log_mod = M('OrderLog');
        $this->appkey = '23301057';
        $this->secretKey = '35fe38606fd21dd8d6e06299bb1351c1'; 
    }
    function index(){
        $this->fwlog('=========cron start=========');
        //自动取消订单
        //$this->auto_cancel_order();
        //自动确认收货
        //$this->auto_confirm_order();
        
        //淘客佣金更新
        $this->getTkrate(); 
        
        //获取取交易跟踪
        $this->getTradeTail();
        
        $this->fwlog('=========cron end===========');
    }
    
    /** 淘客佣金检测，更新佣金比例， 佣金为0时自动下架(暂不用) */
    function getTkrate($n=1){
    	$tbgoods = new \Admin\Controller\TbGoodsController();
    	#var_dump($tbgoods->getOpenIid($id)); 
    	$goods_mod = M('goods');
    	for($i=1; $i<=$n; $i++){
	    	$goods = $outer_id = $goods_mod->where(array('is_on_sale'=>1,'tk_rate'=>0))->field('goods_id,outer_id')->order('rand()')->find();
	    	$findsql = $goods_mod->getLastsql();
	    	if($goods['outer_id'] && $goods['goods_id']){
	    		$tkRate = $tbgoods->getOpenIid($goods['outer_id']);
	    		$tk_rate = $tkRate['tk_rate'];
	    		if($tk_rate>0){
    	    		$data=array('tk_rate'=>$tk_rate,'last_update'=>gmtime());
    	    		//if($tk_rate==0) $data['is_on_sale'] = 0 ;
    	    		$goods_mod->where(array('goods_id'=>$goods['goods_id']))->save($data); 
	    		}
	    		if($_GET['debug']){
	    			dump($goods);
	    			dump($tkRate);
	    			echo $findsql;
	    			echo M()->getLastsql();
	    		}
	    	}
    	}
    }
    
    
    
    /** 获取交易跟踪  */
    function getTradeTail(){ 
    	date_default_timezone_set('Asia/Shanghai');  
	    require_once "./baichuansdk/TopSdk.php"; 
		require_once("./baichuansdk/top/request/TmcMessagesConsumeRequest.php");
		require_once("./baichuansdk/top/request/TmcMessagesConfirmRequest.php");
		
		//实例化TopClient类 
		$c=new \TopClient; 
		$c->appkey = $this->appkey; 
		$c->secretKey = $this->secretKey; 
		$c->gatewayUrl="http://gw.api.taobao.com/router/rest"; 
		$c->format="json";
		 
		//实例化具体API对应的Request类		 
		$req=new \TmcMessagesConsumeRequest;		 
		$req->setQuantity(15);		 
		$resp=$c->execute($req);		 

		$data = null; 
		//确认消息 
		$req2=new \TmcMessagesConfirmRequest;  
		$messages_ids = '';
		M()->startTrans();
		
		$tradetail_mod = M('tradetail');
		foreach ($resp->messages->tmc_message as $key => $val){ 
			$data['topic'] = $val->topic;
			$data['pub_time'] = $val->pub_time;
			$data['pub_app_key'] = $val->pub_app_key;
			$data['msgid'] = $val->id;  
			$content = $val->content;
			$content = json_decode($content,true);
			$data = array_merge($data,$content); 
			$data['auction_infos'] = json_encode($data['auction_infos']);
			$res = $tradetail_mod->add($data); 
			if($res){ //数据插入成功,加入待确认ids
				$messages_ids.= $val->id.","; 
			}else{
				$tradetail_mod->rollback();
				$this->fwlog('CronGetTradeTail Failed && rollback.'.gmtime());
				exit;
			}
		}    
		M()->commit();
		
		//进行消息确认
		$messages_ids=substr($messages_ids,0,strlen($messages_ids)-1);
		$req2->setSMessageIds($messages_ids); 
		$resp2=$c->execute($req2);
		
		if($messages_ids){
			$this->fwlog('CronGetTradeTail success:'.$messages_ids);
			dump($messages_ids);
			dump($resp2);
		}else{
			echo 'no messages.';
		}
		
    	if($_GET['debug']){
			var_dump($resp); 
		}
		
    }
    
    
    
    /** 自动取消超过限制的订单 */
    private function auto_cancel_order(){
        $this->fwlog('function|auto_cancel_order');
        $allow_time = C('auto_cancel_order')? C('auto_cancel_order'):3600;
        $max_time = gmtime() - $allow_time;
        $where['add_time'] = array('lt',$max_time);
        $where['order_status'] = 11; //待付款
        $order = $this->_order_mod->field('order_id,order_status,refund_status')->where($where)->limit(30)->select(); //每次修改30调记录
        if(!$order){
            $this->fwlog('NO ORDER.');
        }else{
            //删除
            foreach($order as $ok => $val){
                $this->_order_mod->where("order_id='{$val['order_id']}'")->setField('order_status',0);
                $this->_order_goods_mod->where("order_id='{$val['order_id']}'")->setField('order_status',0);
                //记录订单操作日志
                $log_data = array(
                    'log_user' => 'buyer|admin',
                    'order_id' => $val['order_id'],
                    'from_status' => $val['order_status'],
                    'to_status' => 0,
                    'from_refund_statu' => $val['refund_status'],
                    'to_refund_status' => $val['refund_status'],
                    'note' => '系统自动取消订单',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    $this->fwlog('Saving order operation records failed.');
                    return;
                }
                cancel_order($val['order_id']); //库存处理和返还积分
            }
            $this->fwlog('success');
        }
    }
    
    /** 订单自动确认收货 */
    private function auto_confirm_order(){
        $this->fwlog('function|auto_confirm_order');
        $allow_time = C('auto_confirm_delivery')? C('auto_confirm_delivery'):7;
        $max_time = gmtime() - $allow_time * 24 * 3600;
        $where['add_time'] = array('lt',$max_time);
        $where['order_status'] = 30; //待付款
        $order = $this->_order_mod->field('order_id,order_status,refund_status')->where($where)->limit(30)->select(); //每次修改30调记录
        if(!$order){
            $this->fwlog('NO ORDER.');
        }else{
            //删除
            foreach($order as $ok => $val){
                $this->_order_mod->where("order_id='{$val['order_id']}'")->setField('order_status',40);
                $this->_order_goods_mod->where("order_id='{$val['order_id']}'")->setField('order_status',40);
                //记录订单操作日志
                $log_data = array(
                    'log_user' => 'buyer|admin',
                    'order_id' => $val['order_id'],
                    'from_status' => $val['order_status'],
                    'to_status' => 40,
                    'from_refund_statu' => $val['refund_status'],
                    'to_refund_status' => $val['refund_status'],
                    'note' => '系统自动确认收货',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    $this->fwlog('Saving order operation records failed.');
                    return;
                }
            }
            $this->fwlog('success');
        }
    }
    
    private function fwlog($msg){
        $fp = @fopen(TEMP_PATH.'cron'.date('y-m-d',gmtime()).'.log','a+');
        @fwrite($fp,'['.date('Y-m-d H:i:s',gmtime()).']:');
        @fwrite($fp,$msg."\r\n");
        @fclose($fp);
    }
}
?>