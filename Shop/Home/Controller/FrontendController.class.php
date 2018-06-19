<?php
/**
 * 前台 主控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
use Org\Util\Net; 
use Common\Common\Basevisitor;
class FrontendController extends Controller{
    var $_ssid = null; //session_id
    var $visitor = null; 
    var $random_url =null; 
    function __construct(){ 
        ini_set("session.cookie_httponly", 1); //360安全评测建议
        header('X-Frame-Options: deny');  //360安全评测建议,影响后台显示
        parent::__construct();
        $this->FrontendController();
    }
    
    function FrontendController(){

    	$this->random_url = "http://a".rand(111,999).'b'.rand(111,999).".graphis.club";
    	if(strtolower($_SERVER['HTTP_HOST']) == 'vip.graphis.club') $this->random_url ='http://vip.graphis.club';
    	$this->assign('random_url',$this->random_url); //其它页面无效？
    	
        //检测网站是否关闭
        if(!C('is_close_shop')){
            echo(C('shop_close_reason'));
            exit;
        } 
        //[屏蔽国内IP访问程序]
        if(C('close_china_intview')){ //开启屏蔽IP访问
            //检测session是否存在
            if(!session('?you_can_intview')){
                $exception_ips = C('exception_intview_ip');
                $exception_ips = explode(',',$exception_ips);
                $client_ip = get_client_ip();  //echo $client_ip='115.238.34.2';
                if(!in_array($client_ip,$exception_ips)){
                   /*
				    $ch = curl_init();
            		curl_setopt($ch, CURLOPT_URL, "http://ip.taobao.com/service/getIpInfo.php?ip=".$client_ip);
            		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            		curl_setopt($ch, CURLOPT_HEADER, 0);
            		$response = curl_exec($ch);
            		curl_close($ch);
            		$res = json_decode($response);
                    if($res->data->country_id == 'CN'){
                        session('you_can_intview', false);        
                        die('Access denied');
                    }
					*/
					$Ip = new \Org\Net\IpLocation('qqwry.dat');
					$area = $Ip->getlocation($client_ip);
					$province=iconv('gbk','utf-8',$area['country']);
					$prov_name=mb_substr($province,0,2,'utf-8'); 
 					$prov_str='天津,上海,天津,重庆,河北,河南,云南,辽宁,黑龙江,湖南,安徽,山东,新疆,江苏,浙江,江西,湖北,广西,甘肃,山西,内蒙,陕西,吉林,福建,贵州,广东,青海,西藏,四川,宁夏,海南,台湾,香港,澳门';
					if(strstr($prov_str,$prov_name)){
						session('you_can_intview', false);        
                        die('The site is under construction.');
					}
                }
                session('you_can_intview', true);
            }else{
                if(!session('you_can_intview')){
                    die('Access denied');
                }
            }
        }
        
        $this->visitor = new visitor();
        $this->_ssid = session_id();
        $this->_init();
        $has_to_login = array('Member','Myorders','Record','Refund','Comments','Collect','ScoreLog','ProductsRequest','UserPmessage');  
        if(in_array(CONTROLLER_NAME,$has_to_login) &&ACTION_NAME!='do_register' && !$this->visitor->has_login){
            redirect(U('/Index/index'));
        }
    }
    
    function _init(){ 
    	$public = array();
    	if(strtolower(__CONTROLLER__) == '/home/album'){
    		$public['active_album'] = 'nav_center_active';	 
    	}
    	if(strtolower(__CONTROLLER__) == '/home/models'){
    		$public['active_models'] = 'nav_center_active';	 
    	}  
    	if(strtolower(__ACTION__) == '/home/index/index'){
    		$public['active_index'] = 'nav_center_active';	 
    	} 
    	if(strtolower(__ACTION__) == '/home/index/vipreg'){
    		$public['active_vipreg'] = 'nav_center_active';	 
    	}  
    	$this->assign($public);
    }
     
     // 验证 客户端 token
    protected function checkAppToken($apptoken){
        // 引入 function.php 中定义的检测 apptoken 的函数
        if(checkingAppToken($apptoken)){
            return true;
        }else{
            $data['code'] = '404';
            $data['msg'] = 'apptoken无效';
            $data['data'] = null;
            $this -> response($data, 'json');
            exit();
        }
    }
    
    // 验证 用户 token
    protected function checkUserToken($user_id,$user_token){
    	$where=array('user_id'=>$user_id);
    	$user_token = addslashes($user_token);
    	$where['_string'] = "user_token='{$user_token}' or DeviceToken='{$user_token}'";
    	$find=M('user')->where($where)->find(); 
        return $find;
    }

    /**输出Json格式
     * $code:1-请求成功；0-请求失败；
     * */
    function toJson($msg,$code=0,$data=array(),$urlencode=true){
    	$this->_json=array('msg'=>$msg,'code'=>$code,'data'=>$data);
    	if(I('urlencode')=='0'||I('urlencode')=='false'){
    		$urlencode=FALSE;
    	}
    	$result = $urlencode?urlencode(json_encode($this->_json)):json_encode($this->_json);
    	echo $result;   
    	exit;
    } 
    
    /**输出Json格式
     * $code:1-请求成功；0-请求失败；
     * */
    function showJson($msg,$code=0,$data=array(),$urlencode=true){
    	$this->_json=array('msg'=>$msg,'code'=>$code,'data'=>$data);
    	if(I('urlencode')=='0'||I('urlencode')=='false'){
    		$urlencode=FALSE;
    	}
    	$result = $urlencode?urlencode(json_encode($this->_json)):json_encode($this->_json);
    	echo $result; 
    }   
    
    /** 调试接口
	 *	如：/appjson/debug/m/ad?urlencode=false
	 */ 
    function debug($m){ 
    	$data=$this->$m(TRUE); 
    	C('SHOW_PAGE_TRACE',true);
    	$this->showJson('请求成功.',1,$data); 	 
    }
      
    
    //会员登陆
    function login_register(){
        $referer = $_SERVER['HTTP_REFERER'];
        $info = parse_url($referer);
        //非本站连接，来源地址重置的到首页
        if($info['host'] != C('site_url')){
            $referer = 'http://'.C('site_url');
        }
        if($info['path'] == $_SERVER['REDIRECT_URL']){
            $referer = 'http://'.C('site_url');
        }elseif(strpos($info['path'],'Forgotpassword')){
            $referer = 'http://'.C('site_url');
        }
        $this->_config_seo(array('name'=>'Sign in/Register'));
        $this->assign('referer',$referer);
        $this->display('./login_register');  
    }
    
    //用户登陆
    function login(){
        //$email = trim(I('email'));
        $user_name = trim(I('user_name'));
        $password = I('password');
        $_user_mod = D('Admin/User');
        //$user_info = $_user_mod->field('user_id,password,code,status')->getByEmail($email);
        $user_info = $_user_mod->field('user_id,password,code,status')->getByUser_name($user_name);
        if(!$user_info){
            $this->error('账号不存在.');
            return;
        }
        //比对密码是否正确
        if($user_info['password'] != MD5(MD5($password).$user_info['code'])){
            $this->error('密码不正确.');
            return;
        }
        //判断用户状态
        if(!$user_info['status']){
            $this->error('用户已经被锁定，请联系客服.');
            return;
        }
        //执行本地登陆
        $this->do_login($user_info['user_id']);
        //来源地址处理
        $referer = I('referer');
        if(!$referer)
            $referer = U('/Member');
        //redirect($referer);
        $this->success('登录成功.',$referer,true);
    }
    
    //执行本地登陆
    function do_login($user_id,$DeviceToken=NULL){
        $_user_mod = D('Admin/User');
        $user_info = $_user_mod->field('user_id,user_name,nick_name,is_vip,vip_end_date,u_pic,email,grade_id,logins,last_login_ip,last_login_time,score,user_token')
                     ->find($user_id);
        //分派身份
        $user_info['user_name'] = $user_info['user_name'];
        $this->visitor->assign($user_info);
               
        //更新用户登陆信息
        $edit_info = array(
            'user_id' => $user_id,
            'logins' => array('exp','logins+1'),
            'last_login_ip' => get_client_ip(),
            'last_login_time' => gmtime(),  //登时时间以时间戳为准
        	'last_login_date' => date('Y-m-d H:i:s'), 
        	'user_token' =>session_id(),
        	'deviceType' => I('deviceType','','trim')  //设备类型-android5，iphone7,windows10 等
        ); 
        
          
        
        //每次登录重新授权访问令牌
        $string = new \Org\Util\String();
        $edit_info['user_token'] = $string->randString(32);
        if($DeviceToken){
        	$edit_info['DeviceToken'] = $DeviceToken;
        }
        //每天登录积分
        $last_login_date=date('Y-m-d',$user_info['last_login_time']);
        $this_date=date('Y-m-d'); 
        if($last_login_date!=$this_date){
        	$edit_info['score']=array('exp','score+3');
        }
        
        $_user_mod->save($edit_info);
        
        //更新购物车中的数据
        $_cart_mod = D('Cart');
        $where['user_id'] = $user_id;
        $where['session_id'] = $this->_ssid;
        $where['_logic'] = 'OR';
        $_cart_mod->where($where)->save(array('user_id' => $user_id));

        //去掉购物车重复项
        $cart_goods = $_cart_mod->field('count(spec_id) as spec_count,sum(quantity) as spec_quantity,cart_id,spec_id')
                      ->where("user_id=".$user_id)
                      ->group('spec_id')->select();
        if($cart_goods != false){
            foreach($cart_goods as $key => $cart){
                if(intval($cart['spec_count']) > 1){//删除重复项
                    $_cart_mod->where(array(
                        'user_id' => $user_id,
                        'spec_id' => $cart['spec_id'],
                        'cart_id' => array('neq',$cart['cart_id'])
                    ))->delete();
                }
                //更新单项的数量
                $_cart_mod->where(array(
                    'user_id' => $user_id,
                    'cart_id' => $cart['cart_id']
                ))->save(array('quantity' => $cart['spec_quantity']));
            }
        }       
    }
    
    //会员注册方法
    function do_register(){ //执行注册
        $data = array(
            'user_name' => trim(I('user_name')),  
            'email' => trim(I('email')),
            'password' => I('password'),
            'repassword' => I('repassword'),
        	'nick_name' => trim(I('nick_name')),
            'is_subscription' => I('is_subscription',0,'intval'),
        	'last_time' => gmtime()
        );
        $referer = I('referer');
        if(!$referer)
            $referer = U('/Member/index');
        $_user_mod = D('Admin/User');
        if(!$_user_mod->create($data)){
            $this->error($_user_mod->getError());
            return;           
        }
        $string = new \Org\Util\String();
        $data['code'] = $string->randString(6);
        $data['password'] = md5(md5($data['password']).$data['code']);
        $data['ctime'] = gmtime();
        $data['status'] = 1; 
        if(!$user_id = $_user_mod->add($data)){
            $this->error('System error, please refresh and try again.');
            return;
        }
        //执行登陆操作
        $this->do_login($user_id);
       
//        //发送邮件处理 
//        sendEmailByTemplate('register_success',$data['email'],array(
//        	'password'=>I('newpassword'),
//        	'first_name'=>I('first_name'),
//        	'last_name'=>I('last_name'),
//        ));
//        if($data['is_subscription']){ //如果订阅，发送订阅成功邮件
//            sendEmailByTemplate('subscription',$data['email']);
//        }
        if(IS_AJAX){
        	$this->success($referer);  //'Congratulations, you have successfully registered.'
        }else{
        	$this->success('恭喜, 注册成功.',$referer);
        } 
    }
    
    /** 退出登陆 */
    function logout(){
        $this->visitor->logout();
        $url=U('/Index');
        if(I('referer','','trim')){
        	$url=I('referer','','trim');
        } 
        redirect($url);
    }
    
//    function order_can_handle($order_status, $refund_status){
//        $you_can = array();
//        if(!$refund_status){
//            switch($order_status){
//                case '11':
//                    $you_can[] = array(
//                        'handle' => 'pay',
//                        'text' => 'Pay Now',
//                        'css' => 'yellowBtn'
//                    );
//                    $you_can[] = array(
//                        'handle' => 'cancel',
//                        'text' => 'Cancel',
//                        'css' => 'grayBtn'
//                    );
//                    break;
//                case '30':
//                    $you_can[] = array(
//                        'handle' => 'confirm',
//                        'text' => 'Confirm',
//                        'css' => 'yellowBtn'
//                    );
//                    break;
//                case '0':
//                    $you_can[] = array(
//                        'handle' => 'delete',
//                        'text' => 'Delete',
//                        'css' => 'orderDelete'
//                        
//                    );
//                    break;
//                default: 
//                    break;
//            }
//        }
//        /*
//        $you_can[] = array(
//            'handle' => 'view',
//            'text' => 'Order details',
//            'css' => 'yellowBtn'
//        );
//        */
//        return $you_can;
//    }
    
    /** 检测email是否已经存在 */
    function check_email(){
        $email = I('email','','trim');
        $user_id = isset($_POST['user_id'])? I('user_id','','intval') : 0;
        $_user_mod = D('Admin/User');
        $result = $_user_mod->check_email($email, $user_id);
        echo $result? 'false': 'true';
    }
    
    /** 用户登录检测email是否已经存在 */
    function ajax_check_email(){
        $email = I('email','','trim');
        $user_id = isset($_POST['user_id'])? I('user_id','','intval') : 0;
        $_user_mod = D('Admin/User');
        $result = $_user_mod->check_email($email, $user_id);
        echo $result? 'true': 'false';
    }    
    
    /** 用户登录检测是否已经存在 */
    function ajax_login(){ 
        $email = trim(I('email'));
        $password = I('password');
        $_user_mod = D('Admin/User');
        $user_info = $_user_mod->field('user_id,password,code,status')->getByEmail($email);
        if(!$user_info){
            $this->error('The account does not exist.');
            return;
        }
        //比对密码是否正确
        if($user_info['password'] != MD5(MD5($password).$user_info['code'])){
        	$data['status']='0';
        	$data['info']='The password is incorrect.';
            $this->ajaxReturn($data);
            return;
        }
        //判断用户状态
        if(!$user_info['status']){
           $this->error('The account has been locked, please contact customer service.');
            return;
        }
        //执行本地登陆
        $this->do_login($user_info['user_id']);
        //来源地址处理
        $referer = I('referer');
        if(!$referer)
            $referer = U('Member/index'); 
        $this->success($referer);
        return ;
    }
    
    /** 地区异步操作 */
    function ajax_get_region(){
        $region_id = I('id','','intval');
        $_region_mod = D('Admin/Region');
        $region = $_region_mod->_get_region($region_id,true);
        $this->success($region,'',true);
    }
    
    /** 站点地图 */
    function sitemap(){
        $this->_config_seo(array(
            'name' => 'Site Map'
        ));
        $this->display('./sitemap');
    }
    
    /** 生成电子账单 */
    function build_bill($order_id){
        $_order_mod = M('Order');
        $_order_goods_mod = M('OrderGoods');
        $order = $_order_mod->find($order_id);
        if(!$order) return false;
        $_user_mod = M('User');
        $user = $_user_mod->find($order['user_id']);
        $order['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        //获取账单和收货地址
        //账单地址
        $_region_mod = M('Region');
        $map['region_id'] = array('in',array($order['bcountry'],$order['bstate']));
        $bill_regions = $_region_mod->field('region_code')->where($map)->order('region_id ASC')->select();
        $order['bcountry'] = $bill_regions[0]['region_code'];
        $order['bstate'] = $bill_regions[1]['region_code'];
        if($order['shipping_code'] != 'CPU'){
            $map['region_id'] = array('in',array($order['country'],$order['state']));
            $ship_regions = $_region_mod->field('region_code')->where($map)->order('region_id ASC')->select();
            $order['country'] = $ship_regions[0]['region_code'];
            $order['state'] = $ship_regions[1]['region_code'];
        }
        //获取订单明细
        $order['goods'] = $_order_goods_mod->field("og.*,gs.spec_item")
                          ->join(' as og INNER JOIN __GOODS_SPECS__ as gs ON og.spec_id=gs.spec_id')
                          ->where("og.order_id='{$order['order_id']}'")->select();
        if($order['goods'] !== false){
            foreach($order['goods'] as $gk => $goods){
                $order['goods'][$gk]['subtotal'] = $goods['present_price'] * $goods['quantity'];
            }
        }
        $this->assign('order',$order);
        $this->buildHtml($order['order_sn'].'.html',TEMP_PATH.'/bill_html/','./bill.index');
    }
    
    
	/** 传入用户id时，检测是否已经收藏 ($list 为goods、article、blog、brand,wiki等二维数组格式) */
	protected function check_collect($list,$key_id,$type){
    	$user_id=I('user_id',0,'intval');
    	if(!empty($user_id)){
    		$collect_mod = M('collect'); 
    		if(is_array(current($list))){ //二维数组
	    		foreach ($list as $key => $val){
	    			if($find=$collect_mod->where(array('type'=>$type,'user_id'=>$user_id,'key_id'=>$val[$key_id]))->find()){
	    				$list[$key]['is_collected']=1;
	    				$list[$key]['cid'] = $find['cid'];
	    			}else{
	    				$list[$key]['is_collected']=0;
	    				$list[$key]['cid'] = null;
	    			} 	 
	    		}
    		}else{ //一维数组
    				if($find=$collect_mod->where(array('type'=>$type,'user_id'=>$user_id,'key_id'=>$key_id))->find()){
	    				$list['is_collected']=1;
	    				$list['cid'] = $find['cid'];
	    			}else{
	    				$list['is_collected']=0;
	    				$list['cid'] = null;
	    			} 
    		}
    	} 
    	return $list;
	}
	
	//APIv1流量统计
	protected function apiv1_traffic(){	 
    	$traffic_mod = M('traffic');
    	$find = $traffic_mod->where('create_date = date(now())')->find();
    	if(!$find){ //新增当天初始数据
    	    $yesterday = $traffic_mod->where('TO_DAYS(NOW()) - TO_DAYS(create_date)=1')->find();
    	     
    	    $traffic_mod->add(array(
    	        'total_api_v1' => (int)$yesterday['total_api_v1']+1,
    	        'total_api_v2' => (int)$yesterday['total_api_v2'],
    	        'today_api_v1' => 1,
    	        'today_api_v2' => 0,
    	        'yesterday_api_v1' => (int)$yesterday['today_api_v1'],     	        
    	        'yesterday_api_v2' => (int)$yesterday['today_api_v2'],
    	        'create_date' => todate(gmtime(),'Y-m-d')
    	    ));
    	}else{
    	    $data =array(
    	        'total_api_v1' => $find['total_api_v1']+1,
    	        'today_api_v1' => $find['today_api_v1']+1
    	    );
    	    $traffic_mod->where('create_date = date(now())')->save($data);
    	}
	}
	
	//APIv2流量统计
	protected function apiv2_traffic(){  
    	$traffic_mod = M('traffic');
    	$find = $traffic_mod->where('create_date = date(now())')->find();
    	if(!$find){ //新增当天初始数据
    	    $yesterday = $traffic_mod->where('TO_DAYS(NOW()) - TO_DAYS(create_date)=1')->find(); 
    	    $traffic_mod->add(array(
    	        'total_api_v1' => (int)$yesterday['total_api_v1'],
    	        'total_api_v2' => (int)$yesterday['total_api_v2']+1,
    	        'today_api_v1' => 0,
    	        'today_api_v2' => 1,
    	        'yesterday_api_v1' => (int)$yesterday['today_api_v1'],    	        
    	        'yesterday_api_v2' => (int)$yesterday['today_api_v2'],
    	        'create_date' => todate(gmtime(),'Y-m-d')
    	    ));
    	}else{
    	    $data =array(
    	        'total_api_v2' => $find['total_api_v2']+1,
    	        'today_api_v2' => $find['today_api_v2']+1
    	    );
    	    $traffic_mod->where('create_date = date(now())')->save($data);
    	}
	}
	
	
	//APP分享下载统计
	protected function appdownloadcount($type='ymg280'){
	    $appdownload_mod = M('appdownload');
	    $find = $appdownload_mod->where('create_date = date(now()) and type="'.$type.'"')->find();
	    if(!$find){ //新增当天初始数据
	        $yesterday = $appdownload_mod->where('TO_DAYS(NOW()) - TO_DAYS(create_date)=1 and type="'.$type.'"')->find();
	        $appdownload_mod->add(array(
	            'total_count' => (int)$yesterday['total_count']+1,
	            'today_count' => 1,
	            'yesterday_count' => (int)$yesterday['today_count'],
	            'create_date' => todate(gmtime(),'Y-m-d'),
	            'type' => $type 
	        ));
	    }else{
	        $data =array(
	            'total_count' => $find['total_count']+1,
	            'today_count' => $find['today_count']+1
	        );
	        $appdownload_mod->where('create_date = date(now()) and type="'.$type.'"')->save($data);
	    }
	}	
    
}

class visitor extends Basevisitor{
    var $_info_key = 'user_info';
    
}
?>