<?php
/**
 * Webapp立即购买控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Controller;
class BuyController extends FrontendController{
    var $_user_address_mod = null;
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    var $_promotion_mod = null;
    var $_goods_specs_mod = null;
    function __construct(){
        parent::__construct();
        $this->BuyController();
    }
    function BuyController(){
        $this->_user_address_mod = D('UserAddress');
        $this->_order_mod = D('Admin/Order');
        $this->_order_goods_mod = D('Admin/OrderGoods');
        $this->_order_log_mod = D('Admin/OrderLog');
        $this->_promotion_mod = D('Admin/Promotion');
        $this->_goods_specs_mod = D('Admin/GoodsSpecs');
    }
    
    /** 立即购买 */
    function index(){
        $spec_ids = trim(I('spec_ids')); 
        $spec_quantity = trim(I('spec_quantity'));
        if(empty($spec_ids)){
        	$this->error('You have not selected goods.');
        }
        if(empty($spec_quantity)){
        	$this->error('Goods quantity missing.');
        }
        //获取参数
        $param=$this->get_param();
        //获取登录用户地址
        if($this->visitor->has_login){ 
        	$this->get_user_address();  
        	$has_login=true;
        }else{
        	$has_login=false;
        }
        //$user_id = $this->visitor->get('user_id');
        $order['carts'] = $this->get_buy_goods($spec_ids, $spec_quantity);
        if(!$order['carts']){
            $this->error('Please add the item to cart before checking out.');
            return;
        } 
        $this->assign('order',$order);
        
        //获取当前运输方式
        $this->get_shipping_method();
        //获取运费？
        $this->assign('has_login',$has_login); 
        $this->assign('referer',U('Buy/index',array_merge($param,array('spec_ids'=>$spec_ids,'spec_quantity'=>$spec_quantity))));
        $this->display('./buy.index');
        
    }
 
    function _before_index(){ 
    	$score=getNameById('score', 'user', 'user_id', session('user_id')); 
    	$this->assign('score',$score);
    }
    
    /**用户未登录:执行注册，保存账单地址和运输地址,跳转订单BUY确认页面 */
    function unlogin_address(){
    	$param=$this->get_param();
    	if(!IS_POST){  
    		if($this->visitor->has_login){
	    		$spec_ids = trim(I('spec_ids'));
		        $spec_quantity = trim(I('spec_quantity'));
		        if(empty($spec_ids)){
		        	$this->error('You have not selected goods.');
		        }
		        if(empty($spec_quantity)){
		        	$this->error('Goods quantity missing.');
		        } 
    			$this->redirect('UserAddress/index',$param);
    		} 
    		//$this->assign('referer',$_SERVER['HTTP_REFERER']);
    		$this->assign('referer',U('Buy/index',$param));
    		$this->assign('regions',$this->get_region());
    		$this->display('./unlogin_address');
    	}else{  
	        //注册用户
	        $user_data = array(
	            'first_name' => trim(I('first_name')),
	            'last_name' => trim(I('last_name')),
	        	'user_type' => trim(I('user_type')),
	        	'company_name' => trim(I('company_name')),
	            'email' => trim(I('email')),
	            'password' => I('password'),
	            'repassword' => I('repassword'),
	        ); 
	        $_user_mod = D('Admin/User');
	        D('')->startTrans();
	        if(!$_user_mod->create($user_data)){
	            $this->error($_user_mod->getError());
	            return;           
	        }
	        $string = new \Org\Util\String();
	        $user_data['code'] = $string->randString(6);
	        $user_data['password'] = md5(md5($user_data['password']).$user_data['code']);
	        $user_data['ctime'] = gmtime();
	        $user_data['status'] = 1;
	        if(!$user_id = $_user_mod->add($user_data)){
	            D('')->rollback();
	            $this->error('System error, please refresh and try again.');
	            return;
	        }
	         //发送用户注册邮件 
	        sendEmailByTemplate('register_success',$user_data['email'],array(
	        	'password'=>I('password'),
	        	'first_name'=>I('first_name'),
	        	'last_name'=>I('last_name'),
	        )); 
	        
	        //保存账单地址和收货地址
	        $address_data = array(
	            'user_id' => $user_id,
	            'first_name' => $user_data['first_name'],
	            'last_name' => $user_data['last_name'],
	        	'company_name' => trim(I('company_name')),
	            'country' => 1,
	            'state' => I('state','','intval'),
	            'city' => trim(I('city')),
	            'address' => trim(I('address')),
	            'zipcode' => trim(I('zipcode')),
	            'telephone' => trim(I('telephone')),
	            'mobile' => trim(I('mobile')),
	            'email' => trim(I('email')), 
	            'is_default' => 1,
	        	'type' => I('type')
	        );
	        $type=I('type');
	        $billing_address_data=array_merge($address_data,array('type'=>'billing'));
	        $shipping_address_data=array_merge($address_data,array('type'=>'shipping'));
	        if($type== 'billing' || $type=='both' ){
	        	//保存收货地址
		        if(!$this->_user_address_mod->create($billing_address_data)){
		            D('')->rollback();
		            $this->error('An error occurred while saving the billing address:'.$this->_user_address_mod->getError());
		            return;
		        }
		        $this->_user_address_mod->add($billing_address_data); 
	        }
	        
	        if($type == 'shipping' || $type=='both' ){
	        	//保存账单地址
		        if(!$this->_user_address_mod->create($shipping_address_data)){
		            D('')->rollback();
		            $this->error('An error occurred while saving the billing address:'.$this->_user_address_mod->getError());
		            return;
		        }
		        $this->_user_address_mod->add($shipping_address_data); 
	        }  
	        
	        
	        //执行登陆操作
	        $this->do_login($user_id);
	        D('')->commit();
	        //地址保存成功，跳转订单确认页面
	        $this->success(U('Buy/index',$param)); 
    	} 
    }

    
    /** 提交订单 */
    function inlogin(){  
        if(!$this->visitor->has_login){//未登陆用户不允许访问
            redirect(U('Login/login')); 
        }
        $spec_ids = trim(I('spec_ids'));
        $spec_quantity = trim(I('spec_quantity'));
        $shipping_method = trim(I('shipping_method','','trim'));
        if(!$spec_ids || !$spec_quantity){
            $this->error('Incorrect parameters, please refresh and try again2.');
            return;
        }
        if(empty($shipping_method)) $this->error('Incorrect shipping method.');
        $order['carts'] = $this->get_buy_goods($spec_ids, $spec_quantity);
        if(!$order['carts']){
            $this->error('Please add the item to cart before checking out.');
            return;
        }
        $user_id = $this->visitor->get('user_id');
        //前端页面
        if(IS_POST){ 
            $shipping_address_id = I('shipping_address_id','','intval');
            $billing_address_id = I('billing_address_id','','intval');
            if(!$billing_address_id){
                $this->error('Incorrect parameters, please refresh and try again1.');
                return;   
            }
            $where = array(
                'user_id' => $user_id,
                'address_id' => array('in',array($shipping_address_id,$billing_address_id))
            );
            $address = $this->_user_address_mod->where($where)->select();
            if(!$address){
                $this->error('You have not set a shipping address yet.');
                return;
            }
            foreach($address as $key => $val){
                if($val['type'] == 'shipping'){
                    $new_address['shipping_address'] = $val;   
                }else{
                    $new_address['billing_address'] = $val;
                }
            }
            
            //计算运费
            if($shipping_method == 'shipping'){
                $orderController = new \Home\Controller\OrderController();
                $shipping_info = $orderController->calculation_shipping_fee($new_address['shipping_address']['address_id'],$order['carts']);
            }else{
                $shipping = C('SHIPPING');
                $shipping_info = array(
                    'delivery' => $shipping['CPU'],
                    'shipping_fee' => 0
                );
            }
            D('')->startTrans();
            //计算积分抵扣
            $integral = I('score','','intval');
            if($integral){
                $integral_fee = $integral / 100;
            }else{
                $integral_fee = 0;
            }
            //处理订单单头
            $order_data = array(
                'order_sn' => _gen_order_sn(),
                'user_id' => $user_id,
                'order_status' => 11,
                'refund_status' => 0,
                'bconsignee' => $new_address['billing_address']['first_name']. ' ' .$new_address['billing_address']['last_name'],
                'bcountry' => $new_address['billing_address']['country'],
                'bstate' => $new_address['billing_address']['state'],
                'bcity' => $new_address['billing_address']['city'],
                'baddress' => $new_address['billing_address']['address'],
                'bzipcode' => $new_address['billing_address']['zipcode'],
                'btelephone' => $new_address['billing_address']['telephone'],
                'bmobile' => $new_address['billing_address']['mobile'],
                'shipping_code' => $shipping_info['delivery']['shipping_code'],
                'shipping_name' => $shipping_info['delivery']['shipping_name'],
                'shipping_fee' => $shipping_info['shipping_fee'],
                'goods_amount' => $order['carts']['subtotle'],
                'integral_fee' => $integral_fee,
                'discount_fee' => 0,
                'refund_fee' => 0,
                'totle_fee' => $order['carts']['subtotle'] + $shipping_info['shipping_fee'] - $integral_fee,
                'add_time' => gmtime(),
            );
            
            // PICK UP无需收货地址 ,处理是否选择了inside服务,
            //if($shipping_method == 'shipping'){
                $order_data['consignee'] = $new_address['shipping_address']['first_name']. ' ' .$new_address['shipping_address']['last_name'];
                $order_data['country'] = $new_address['shipping_address']['country'];
                $order_data['state'] = $new_address['shipping_address']['state'];
                $order_data['city'] = $new_address['shipping_address']['city'];
                $order_data['address'] = $new_address['shipping_address']['address'];
                $order_data['zipcode'] = $new_address['shipping_address']['zipcode'];
                $order_data['telephone'] = $new_address['shipping_address']['telephone'];
                $order_data['mobile'] = $new_address['shipping_address']['mobile'];
                if(isset($_POST['want_inside_delivery']) && $_POST['want_inside_delivery']){
                    $order_data['shipping_fee'] += $shipping_info['inside_delivery'];
                    $order_data['totle_fee'] += $shipping_info['inside_delivery'];
                    $order_data['inside_fee'] = $shipping_info['inside_delivery'];
                }
            //}
            
            //计算折扣优惠
            if(isset($_POST['promotion_discount_id'])){
                $pro_id = I('promotion_discount_id','','intval');
                $promotion = $this->_promotion_mod->find($pro_id);
                if($promotion != false){
                    $order_data['discount_fee'] = round(($order['carts']['subtotle'] * ((100 - $promotion['rate']) / 100)),2);
                    $order_data['discount_pro_id'] = $pro_id;
                    $order_data['totle_fee'] -= $order_data['discount_fee'];
                }
            }
            
            //计算可以获得的积分
            if(isset($_POST['promotion_integral_id'])){
                $pro_id = I('promotion_integral_id','','intval');
                $promotion = $this->_promotion_mod->find($pro_id);
                if($promotion != false){
                    $order_data['points'] = round($order_data['totle_fee']) * $promotion['rate'];
                    $order_data['integral_pro_id'] = $pro_id;
                }
            }else{
                $order_data['points'] = round($order_data['totle_fee']);
            }
            
            if(!$this->_order_mod->create($order_data)){
                D('')->rollback();
                $this->error($this->_order_mod->getError());
                return;
            }
            $order_id = $this->_order_mod->add();
            //处理订单明细
            foreach($order['carts']['carts'] as $key => $cart){
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
            
            //积分抵扣，记录积分使用
            if($integral_fee){
                //积分处理
                $score_data = array(
                    'user_id' => $user_id,
                    'type' => '-',
                    'score' => $integral,
                    'desc' => 'Points redeemed in the order.[order_sn:'.$order_data['order_sn'].']',
                    'ctime' => gmtime()
                );
                $_score_log_mod = D('ScoreLog');
                if(!$_score_log_mod->add($score_data)){
                    D('')->rollback();
                    $this->error('Saving points record failed.');
                    return;
                }
                
                //减去用户的积分余额
                $update_user_data = array(
                    'user_id' => $user_id,
                    'score' => array('exp','score-'.$integral)
                );
                if(!M('User')->save($update_user_data)){
                    D('')->rollback();
                    $this->error('Updating points record failed.');
                    return;
                }
            }
            
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'buyer|'.$this->visitor->get('user_name'),
                'order_id' => $order_id,
                'from_status' => $order_data['order_status'],
                'to_status' => $order_data['order_status'],
                'note' => '用户新增订单',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->error('Saving order operation records failed.');
                return;
            }
            D('')->commit();
            $order_data['order_id']=$order_id;
            $this->assign('order_data',$order_data);
            $this->display('./order_success');
            
//            //前往支付页面
//            redirect(U('Myorders/pay',array('id'=>$order_id)));
//            //$this->success('Order placed successfully.',U('Index/index'));
        }      
    }

        
    /** 未登陆订单确认    --未使用 */
    function unlogin(){
        $spec_ids = trim(I('spec_ids'));
        $spec_quantity = trim(I('spec_quantity'));
        $shipping_method = trim(I('shipping_method','shipping'));
        //注册用户
        $user_data = array(
            'first_name' => trim(I('billing_first_name')),
            'last_name' => trim(I('billing_last_name')),
        	'user_type' => trim(I('user_type')),
        	'company_name' => trim(I('billing_company_name')),
            'email' => trim(I('billing_email')),
            'password' => I('newpassword'),
            'repassword' => I('repassword'),
        );
        $_user_mod = D('Admin/User');
        D('')->startTrans();
        if(!$_user_mod->create($user_data)){
            $this->error($_user_mod->getError());
            return;           
        }
        $string = new \Org\Util\String();
        $user_data['code'] = $string->randString(6);
        $user_data['password'] = md5(md5($user_data['password']).$user_data['code']);
        $user_data['ctime'] = gmtime();
        $user_data['status'] = 1;
        if(!$user_id = $_user_mod->add($user_data)){
            D('')->rollback();
            $this->error('System error, please refresh and try again.');
            return;
        }
         //发送用户注册邮件 
        sendEmailByTemplate('register_success',$user_data['email'],array(
        	'password'=>I('newpassword'),
        	'first_name'=>I('billing_first_name'),
        	'last_name'=>I('billing_last_name'),
        )); 
        
        //保存账单地址和收货地址
        $billing_data = array(
            'user_id' => $user_id,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
        	'company_name' => trim(I('billing_company_name')),
            'country' => 1,
            'state' => I('billing_state','','intval'),
            'city' => trim(I('billing_city')),
            'address' => trim(I('billing_address')),
            'zipcode' => trim(I('billing_zipcode')),
            'telephone' => trim(I('billing_telephone')),
            'mobile' => trim(I('billing_mobile')),
            'email' => trim(I('billing_email')),
            'type' => 'billing',
            'is_default' => 1,
        );
        
        //保存账单地址
        if(!$this->_user_address_mod->create($billing_data)){
            D('')->rollback();
            $this->error('An error occurred while saving the billing address:'.$this->_user_address_mod->getError());
            return;
        }
        $this->_user_address_mod->add($billing_data);
        
        //判断是否需要保存收货地址
        if($shipping_method == 'shipping'){
            //保存收货地址
            $shipping_type = I('shipping_type','','intval');
            if(!$shipping_type){
                $shipping_data = $billing_data;
                $shipping_data['type'] = 'shipping';
            }else{
                $shipping_data = array(
                    'user_id' => $user_id,
                    'first_name' => trim(I('shipping_first_name')),
                    'last_name' => trim(I('shipping_last_name')),
                	'company_name' => trim(I('shipping_company_name')),
                    'country' => 1,
                    'state' => I('shipping_state','','intval'),
                    'city' => trim(I('shipping_city')),
                    'address' => trim(I('shipping_address_1')),
                    'zipcode' => trim(I('shipping_zipcode')),
                    'telephone' => trim(I('shipping_telephone')),
                    'mobile' => trim(I('shipping_mobile')),
                    'email' => trim(I('billing_email')),
                    'type' => 'shipping',
                    'is_default' => 1,
                );
            }
            //保存收货地址
            if(!$this->_user_address_mod->create($shipping_data)){
                D('')->rollback();
                $this->error('An error occurred while saving the shipping address:'.$this->_user_address_mod->getError());
                return;
            }
            $this->_user_address_mod->add($shipping_data);
        }
        //执行登陆操作
        $this->do_login($user_id);
        D('')->commit();
        //地址保存成功，跳转订单确认页面
        redirect(U('Buy/index',array('spec_ids'=>$spec_ids,'spec_quantity'=>$spec_quantity,'shipping_method'=>$shipping_method)));
    }
        
    
	/** 获取当前运输方式*/
    function get_shipping_method(){
    	$shipping=C('SHIPPING');
    	$delivery=I('delivery','','trim'); 
    	if($delivery!=''){ 
    		$shipping_method=$shipping[$delivery];
    	}else{ //默认,根据克重判断
    		$shipping_method=$shipping['R02'];
    		/***/
    	}  
    	$this->assign('shipping_method',$shipping_method);
    	return $res;
    }
    
    /** 获取地址用户 */
    function get_user_address(){
    	$where['user_id']=$this->visitor->get('user_id');
    	if(empty($where['user_id'])) return;  	
    	//shipping address
    	$where['type']='shipping';
        if(I('shipping_address_id','')) $where['address_id']=I('shipping_address_id');  
    	$shipping_address=$this->_user_address_mod->where($where)->order('is_default desc')->find();
    	$this->assign('shipping_address',$shipping_address);    	 
    	    	
    	//billing address
    	$where['type']='billing';
    	unset($where['address_id']);
        if(I('billing_address_id','')) $where['address_id']=I('billing_address_id'); 
    	$billing_address=$this->_user_address_mod->where($where)->order('is_default desc')->find();
    	$this->assign('billing_address',$billing_address); 
    	#echo M()->getLastsql();

    }
    
    /** 获取state */
    function get_region($region_id=1){
        $_region_mod = D('Admin/Region');
        $regions = $_region_mod->_get_region($region_id,true);
        return $regions;
    }
    
    /** 获取商品信息 */
    function get_buy_goods($spce_ids, $spce_quantity){
        $spce_ids = explode(',',$spce_ids);
        $spce_quantity = explode(',',$spce_quantity);
        $where['spec_id'] = array('in',$spce_ids);
        $goods = $this->_goods_specs_mod->field('gs.*,g.goods_name,g.goods_thumb as default_image')
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
    
    /** 异步计算运费 */
    function ajax_shipping_fee(){
        $address_id = I('id','','intval');
        $spec_ids = trim(I('spec_ids'));
        $spec_quanttiy = trim(I('spec_quantity'));
        if(!$spec_ids || !$spec_quanttiy){
            $this->error('Incorrect parameters, please refresh and try again 2.');
            return;
        }
        $carts = $this->get_buy_goods($spec_ids, $spec_quanttiy);
        if(!$carts){
            $this->error('Please add the item to cart before checking out.');
            return;
        }
        $orderController = New \Home\Controller\OrderController();
        $shipping_info = $orderController->calculation_shipping_fee($address_id,$carts);
        $this->success($shipping_info);
    }

    
    /** 运输方式选择页 */
    function delivery(){
    	$shipping=C('SHIPPING'); 
    	unset($shipping['CPU']); 
    	$this->assign('shipping',$shipping);
    	$this->get_param();
    	$this->display('./Delivery'); 
    }
    
}


?>