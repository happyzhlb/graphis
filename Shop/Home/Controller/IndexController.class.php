<?php
/**
 * 程序默认控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller; 
class IndexController extends FrontendController { 
	var $_user_mod=null; 
	var $_albumcate_mod=null;
	var $_models_mod=null;
    function __construct(){
        parent::__construct();
        $this->IndexController();
    }
    
    function IndexController(){   
    	$_public['random_url'] = $this->random_url;
        $this->_mod=D('album');  
        $this->_albumcate_mod=D('Admin/albumcate');
        $this->_models_mod=D('models');  
        $_public['albumcate'] = $this->_albumcate_mod->get_category(0,true);
        $this->assign($_public); 
    }
	
    public function index(){ 
    	
        $this->oauth(); 
        
    	$ads = M('ad')->where(array('status'=>1))->limit('0,6')->order('ad_id asc')->select();
    	
    	$this->assign('ads',$ads);
    	
    	if(I('debug')=='true') var_dump(session_id());
    	
    	$where=array(
    			'if_show' => 1,
    			
    	);
    	$albumlist = $this->_mod->where($where)->limit('0,8')->order('is_recommend desc,total_reward_times desc,ctime desc')->select();
    	$this->assign('albumlist',$albumlist);
    	
    	//模特数量
    	$where=array(
    			'if_show' => 1,
    			'is_recommend'=>1
    	);
    	$modelslist = $this->_models_mod->where($where)->limit('0,4')->order('id desc')->select();
    	foreach ($modelslist as $key => $val){
    		$modelslist[$key]['album_num'] = $this->_mod->where(array('models_id'=>$val['id']))->count();
    	}
    	$this->assign('modelslist',$modelslist);
    	
    	$where=array(
    			'if_show' => 1,
    			'is_top'=>1
    	);
    	$weekstar = $this->_models_mod->where($where)->limit('0,3')->order('view_num desc,id desc')->select();
    	$this->assign('weekstar',$weekstar); 
    	
        $this->display('index/index');  
    }
    
 	/** 关于我们  */
    function vipreg(){ 
    	$this->display('index/vipreg');
    }
    
    /** 关于我们  */
    function singlepage(){  
    	$page_id = I('id',1,'intval');
    	if(empty($page_id)){
    		$this->error('参数错误.');
    	}
    	$page= M('singlepage')->find($page_id);
    	$page['content'] = htmlspecialchars_decode($page['content']);
    	$this->assign('page',$page);
    	$this->display('index/singlepage');
    }
    
    function test($ip=''){
    	$Ip = new \Org\Net\IpLocation('qqwry.dat');
    	$area = $Ip->getlocation($ip);
    	$province=iconv('gbk','utf-8',$area['country']);
    	$prov_name=mb_substr($province,0,2,'utf-8');
    	
    	 dump($area);
    	 
    	$res = curl_post('http://ip.cn/'.$ip,0);
    	preg_match('/所在地理位置：\<code\>(.*)\<\/code\>\<\/p\>/i', $res,$arr);
    	is_array($arr)?$arr[1]:'';
    	exit;
    	
		#$json = '{"r":true,"i":[{"ID":"705438","url":"http://image.meituzz.com/image/36063_1491403271000_75dvsl4ujid7vv8orpss"},{"ID":"705439","url":"http://image.meituzz.com/image/36063_1491403271000_smiha8z0q5j2et9jrrhx"},{"ID":"705440","url":"http://image.meituzz.com/image/36063_1491403271000_at4yn8oow3l2sxbiy7r7"},{"ID":"705441","url":"http://image.meituzz.com/image/36063_1491403272000_7k275kmfl0jf7jxth3wj"},{"ID":"705442","url":"http://image.meituzz.com/image/36063_1491403272000_00tvra66rlv8ynqga0p5"},{"ID":"705443","url":"http://image.meituzz.com/image/36063_1491403272000_1recs35bm2eaibncgcf6"},{"ID":"705444","url":"http://image.meituzz.com/image/36063_1491403272000_lgg2ke1elilpm15sivb9"},{"ID":"705445","url":"http://image.meituzz.com/image/36063_1491403272000_9ie3m8i003uflypprc3w"},{"ID":"705446","url":"http://image.meituzz.com/image/36063_1491403273000_98jpe7inlj03geomdld3"},{"ID":"705448","url":"http://image.meituzz.com/image/36063_1491403273000_mdmdufn66cw2e2nni1j6"}]}';
		#echo $json;
    	$album_id = 31;
		$list = M('picture')->field('id as ID,photo as url')->where(array('album_id'=>$album_id))->select();
		$data = array(
			'r'=>true,
			'i'=>$list
		);
		$this->ajaxReturn($data);
	}
    
    /** 微信预支付 */
    function wePrepay(){ 	
    	
    	
    	/** 签名字符串顺序：    	
			1、参数名ASCII码从小到大排序（字典序）；
			2、如果参数的值为空不参与签名；
			3、参数名区分大小写；
			4、验证调用返回或微信主动通知签名时，传送的sign参数不参与签名，将生成的签名与该sign值作校验。
			5、微信接口可能增加字段，验证签名时必须支持增加的扩展字段
    	*/
    	$stringA="appid=wx43f4bd20cce3d5dc&attach=test&body=APPtest&mch_id=1326744401&nonce_str=1add1a30ac87aa2db72f57a2375d8fec&notify_url=http://www.ymg280.com/mygoods/notify_url&out_trade_no=1615659990&spbill_create_ip=112.16.90.165&total_fee=1&trade_type=APP"; 
     	$stringSignTemp=$stringA."&key=20167161832YunmagouYunmagouYunma";  
    	$sign=strtoupper(md5($stringSignTemp));
    	$data="<xml>
	<appid>wx43f4bd20cce3d5dc</appid>
	<attach>test</attach>
	<body>APPtest</body>
	<mch_id>1326744401</mch_id>
	<nonce_str>1add1a30ac87aa2db72f57a2375d8fec</nonce_str>
	<notify_url>http://www.ymg280.com/mygoods/notify_url</notify_url>
	<out_trade_no>1615659990</out_trade_no>
	<spbill_create_ip>112.16.90.165</spbill_create_ip>
	<total_fee>1</total_fee>
	<trade_type>APP</trade_type>
	<sign>{$sign}</sign>
	</xml>";
    	dump($data);
    	$url="https://api.mch.weixin.qq.com/pay/unifiedorder";
    	$res=curl_post($url,$data); 
    	
    	dump($res); 
    }
    
    /** 支付宝同步通知--充值订单 
     *   gmt_create=2018-05-26+18%3A40%3A55&charset=UTF-8&seller_email=zhijian%40eqb.com.cn&subject=%E5%9B%BE%E7%89%87%E6%89%93%E8%B5%8F&sign=g3IE6GVLpP57DNT8aPya8fM3j08w3%2BS0V0N%2FMSaysC%2BXWXEfgqVLYecQmP%2BYJxWgA%2FMdiwNjbobttJ9BXhSz2UoUiFvLUyAKPDJszAVXMc0r%2FxZrwOMZGYNduL68WtNybFhcscx68AfnJYBttUVZb9AWBesA4ryZDKyzytgMqfwuMfeNjBfz1kS89CcNpCPaYN4%2FmKirXXZEJXFw8thwquFGgKdCefLbk0cppKH%2FsgnQHJIVU2IjjggNnr224vpDpjxlz84yJcMJcAJlKMKF%2Frkh8N4YRlK78LfqKqTW6rPiWwxBuBIpo%2BUnfLbwNxNkiUasseWQSdpKZWjcFqLvkA%3D%3D&body=%E4%B8%93%E8%BE%91%E5%9B%BE%E7%89%87%E6%89%93%E8%B5%8F%E8%AE%A2%E5%8D%95%E5%8F%B7%3A1814541060++ip%3A116.233.182.11&buyer_id=2088622162340611&invoice_amount=19.90&notify_id=d8a5c15f2968fe4f20c4acabbb2f41ckpi&fund_bill_list=%5B%7B%22amount%22%3A%2219.90%22%2C%22fundChannel%22%3A%22ALIPAYACCOUNT%22%7D%5D&notify_type=trade_status_sync&trade_status=TRADE_SUCCESS&receipt_amount=19.90&buyer_pay_amount=19.90&app_id=2017122401132635&sign_type=RSA2&seller_id=2088921191252712&gmt_payment=2018-05-26+18%3A40%3A57&notify_time=2018-05-27+19%3A04%3A35&version=1.0&out_trade_no=1814541060&total_amount=19.90&trade_no=2018052621001004610275285431&auth_app_id=2017122401132635&buyer_logon_id=139****2635&point_amount=0.00
     
     * 
     * */
    public function alipayPageReturn(){  
          phpInput('./Shop/Runtime/alipay_return_input'.date('Ymd',gmtime()).'.log'); 
     	  $order_sn = I('out_trade_no');
     	  if (empty($order_sn)) $this->error('订单号(out_trade_no)不能为空.');  
        
        /* *
         * 功能：支付宝页面跳转同步通知页面
         * 版本：2.0
         * 修改日期：2017-05-01
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
        
         *************************页面功能说明*************************
         * 该页面可在本机电脑测试
         * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
         */
 		require_once("./Core/Library/Vendor/alipay/alipayTradePagePay/config.php");
    	require_once './Core/Library/Vendor/alipay/alipayTradePagePay/pagepay/service/AlipayTradeService.php';
    	
        
        
        $arr=$_GET;
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($_GET);
        
        /* 实际验证过程建议商户添加以下校验。
         1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
         2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
         3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
         4、验证app_id是否为该商户本身。
         */
        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码
        
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
        
            //商户订单号
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);
        
            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);
        
            //echo "验证成功<br />支付宝交易号：".$trade_no;

            $where = array('order_sn'=>$order_sn);
            if (!$find=M('recharge')->where($where)->find()) $this->error("订单号:{$order_sn}未找到对应记录.");
            
            if($find['order_status']=='20'){
                $this->error('该订单已经处理过了，无需重复处理.',U('Member/index'));
            }
            
            M()->startTrans();
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            $data= array(
                'order_status' => 20,
                'pay_code' => 'alipay',
                'pay_name' => '支付宝',
                'total_amount' => I('total_amount'),
                'outer_order_sn'=>I('trade_no'),
                'pay_time'=> I('timestamp'),
                'pay_json' => json_encode($_GET),
                'notify_ip' => get_client_ip(),
                'notify_time' => gmtime()
            );
            $res = M('recharge')->where($where)->save($data);
            if($res==false){
                M()->rollback();
                fwlog('订单通知保存失败错误','alipay_notify');
                $this->error('订单通知保存失败错误.');
            }
            $updateScore = M('user')->where(array('user_id'=>$find['user_id']))->setInc('balance',$data['total_amount']);
            if(!$updateScore){ 
                M()->rollback();
                fwlog('用户充值失败','alipay_return');
                $this->error('用户充值失败.','',6000);
            }
          
           $money = I('total_amount');
           $user = M('user')->where(array('user_id'=>$find['user_id']))->find();
            //订单充值，资金明细增加
            $finance_mod = M('finance');
            $finance_data = array(
                'user_name' => $user['user_name'],
                'user_id' => $user['user_id'],
                'brand_id' => 0,
                'store_id' => 0,
                'type' => '+',
                'money' => $money,
                'balance' => $user['balance'] + $money,
                'action_type' => 'recharge',
                'key_id' => $find['order_id'],
                'memo' => '用户充值，充值订单号：'.$out_trade_no,
                'ctime' => gmtime(),
                'ip' => get_client_ip(),
                'admin_id' => 0
            );
            if(!$finance_mod->add($finance_data)){
                M()->rollback();
                $this->toJsonLog('资金明细增加失败.');
            } 
            
            if(in_array($money, array(0.1,99,499,888,1599))){
                //vip天数
                $vipDays = array(
                    '99' => 30,
                    '499' => 183,
                    '888' => 365,
                    '1599' => 1599,
                    '1888' => 36500, 
                );
                if(empty($user['vip_end_date'])) $user['vip_end_date'] = date('Y-m-d',gmtime());
                $end_time = strtotime($user['vip_end_date'])+3600*24*$vipDays[(int)$money];
                $data = array(
                    'balance' => $user['balance'] - $money,
                    'is_vip' => 1,
                    'vip_end_date' => date('Y-m-d',$end_time)
                ); 
                if(!M('user')->where(array('user_id'=>$find['user_id']))->save($data)){
                    M()->rollback();
                    $this->toJsonLog('购买VIP,用户表扣款失败.');
                }
                
                //购买VIP扣款，资金明细增加
                $finance_mod = M('finance');
                $finance_data = array(
                    'user_name' => $user['user_name'],
                    'user_id' => $user['user_id'],
                    'brand_id' => 0,
                    'store_id' => 0,
                    'type' => '-',
                    'money' => $money,
                    'action_type' => 'recharge',
                    'key_id' => $find['order_id'],
                    'memo' => '购买VIP扣款，充值订单号：'.$out_trade_no,
                    'ctime' => gmtime(),
                    'ip' => get_client_ip(),
                    'admin_id' => 0
                );
                if(!$finance_mod->add($finance_data)){
                    M()->rollback();
                    $this->toJsonLog('vip扣款，资金明细增加失败.');
                } 
                
            }
            
            M()->commit();
            $this->success('充值成功.',U('Member/index'));
        }else {
            //验证失败
            $this->error("支付接口验证失败.",U('Member/index'));
        } 
    }
    
    /** 支付宝异步通知 
      * gmt_create=2018-05-27+19%3A03%3A56&charset=UTF-8&gmt_payment=2018-05-27+19%3A04%3A01&notify_time=2018-05-27+19%3A04%3A02&subject=GRAPHIS%E7%BD%91%E7%AB%99-%E4%BC%9A%E5%91%98%E5%85%85%E5%80%BC&sign=iTGdP5bMM6jFa5jEUq9jdVlxpBxGs0pCPwBmcbz%2FmnLyjYHdtjIdPwgEdVzaE8rlVk51RebFHOZAeSEMshOlyNkyNEcThv%2B5zw4QOYF3KF0Is1Qyz6gkJUo55BPrLyX9D2KN0nnUrovWGdzOG5mYUy%2Bk8BwJu7tI687I0yDwRPFaUeAeS7Mvd8p%2FGvcxUqmV8WBXjRPj2Hd8JPZxSyq0uutniS1sm1%2BNQ0Qkc%2FN3i5bwyf8T2LsxSdSfUGeE2LV9d3Ay1wbbbEgqR6JZHiTKXqBsmkHI%2FoqC75FGo8%2B1h%2FKRknZ20Qj4oiDLUx77q2IVDZAlQmAnKmfKu7QuY5xevQ%3D%3D&buyer_id=2088002292783861&body=abiao&invoice_amount=0.10&version=1.0&notify_id=7593ee3f4a77cfa23f7fb20912dbd8emmy&fund_bill_list=%5B%7B%22amount%22%3A%220.10%22%2C%22fundChannel%22%3A%22ALIPAYACCOUNT%22%7D%5D&notify_type=trade_status_sync&out_trade_no=1814607072&total_amount=0.10&trade_status=TRADE_SUCCESS&trade_no=2018052721001004860290578282&auth_app_id=2017122401132635&receipt_amount=0.10&point_amount=0.00&app_id=2017122401132635&buyer_pay_amount=0.10&sign_type=RSA2&seller_id=2088921191252712
      * 
      **/
    public function alipayPageNotify(){ 
        
        phpInput('./Shop/Runtime/alipay_notify_input'.date('Ymd',gmtime()).'.log');
        
         /*************************页面功能说明*************************
         * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
         * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
         * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
         */
        
        require_once("./Core/Library/Vendor/alipay/alipayTradePagePay/config.php");
    	require_once './Core/Library/Vendor/alipay/alipayTradePagePay/pagepay/service/AlipayTradeService.php';
    	
        
        $arr=$_POST;
        $alipaySevice = new \AlipayTradeService($config); 
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        
        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if($result) {//验证成功
        	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        	//请在这里加上商户的业务逻辑程序代
        
        	
        	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
        	
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
        	
        	//商户订单号 
        	$out_trade_no = $_POST['out_trade_no'];
        
        	//支付宝交易号 
        	$trade_no = $_POST['trade_no'];
        
        	//交易状态
        	$trade_status = $_POST['trade_status'];
        
        
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
        
        		//判断该笔订单是否在商户网站中已经做过处理
        			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
        			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
        			//如果有做过处理，不执行商户的业务程序
        				
        		//注意：
        		//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知  
                
            }elseif ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                
        		//判断该笔订单是否在商户网站中已经做过处理
    			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
    			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
    			//如果有做过处理，不执行商户的业务程序			
        		//注意：
        		//付款完成后，支付宝系统发送该交易状态通知
        		
                
                $where = array('order_sn'=>$out_trade_no);
                if (!$find=M('recharge')->where($where)->find()) $this->error("订单号:{$out_trade_no}未找到对应记录.");
                
                if($find['order_status']=='20'){
                     exit("success");
                }
                
                //事务开始
                M()->startTrans();
 
                $data= array(
                    'order_status' => 20,
                    'pay_code' => 'alipay',
                    'pay_name' => '支付宝',
                    'total_amount' => I('total_amount'),
                    'outer_order_sn'=>I('trade_no'),
                    'pay_time'=> I('timestamp'),
                    'pay_json' => json_encode($_GET),
                    'notify_ip' => get_client_ip(),
                    'notify_time' => gmtime()
                );
                $res = M('recharge')->where($where)->save($data);
                if($res==false){
                    M()->rollback();
                    fwlog('订单通知保存失败错误','alipay_notify');
                    $this->error('订单通知保存失败错误.');
                }
                $updateScore = M('user')->where(array('user_id'=>$find['user_id']))->setInc('balance',$data['total_amount']);
                if(!$updateScore){
                    M()->rollback();
                    fwlog('用户充值失败','alipay_return');
                    $this->error('用户充值失败.','',6000);
                }
                
                $money = I('total_amount');
                $user = M('user')->where(array('user_id'=>$find['user_id']))->find();
                //订单充值，资金明细增加
                $finance_mod = M('finance');
                $finance_data = array(
                    'user_name' => $user['user_name'],
                    'user_id' => $user['user_id'],
                    'brand_id' => 0,
                    'store_id' => 0,
                    'type' => '+',
                    'money' => $money,
                    'action_type' => 'recharge',
                    'key_id' => $find['order_id'],
                    'memo' => '用户充值，充值订单号：'.$out_trade_no,
                    'ctime' => gmtime(),
                    'ip' => get_client_ip(),
                    'admin_id' => 0
                );
                if(!$finance_mod->add($finance_data)){
                    M()->rollback();
                    $this->toJsonLog('资金明细增加失败.');
                }
                
                if(in_array($money, array(0.1,99,499,888,1599))){
                    //vip天数
                    $vipDays = array(
                        '99' => 30,
                        '499' => 183,
                        '888' => 365,
                        '1599' => 1599,
                        '1888' => 36500, 
                    );
                    if(empty($user['vip_end_date'])) $user['vip_end_date'] = date('Y-m-d',gmtime());
                    $end_time = strtotime($user['vip_end_date'])+3600*24*$vipDays[(int)$money];
                    $data = array(
                        'balance' => $user['balance'] - $money,
                        'is_vip' => 1,
                        'vip_end_date' => date('Y-m-d',$end_time)
                    );
                    if(!M('user')->where(array('user_id'=>$find['user_id']))->save($data)){
                        M()->rollback();
                        $this->toJsonLog('购买VIP,用户表扣款失败.');
                    }
                
                    //购买VIP扣款，资金明细增加
                    $finance_mod = M('finance');
                    $finance_data = array(
                        'user_name' => $user['user_name'],
                        'user_id' => $user['user_id'],
                        'brand_id' => 0,
                        'store_id' => 0,
                        'type' => '-',
                        'money' => $money,
                        'balance' => $user['balance']-$money,
                        'action_type' => 'recharge',
                        'key_id' => $find['order_id'],
                        'memo' => '购买VIP扣款，充值订单号：'.$out_trade_no,
                        'ctime' => gmtime(),
                        'ip' => get_client_ip(),
                        'admin_id' => 0
                    );
                    if(!$finance_mod->add($finance_data)){
                        M()->rollback();
                        $this->toJsonLog('vip扣款，资金明细增加失败.');
                    }
                
                }
                
                M()->commit();
                exit("success");
                //$this->success('充值成功.',U('Member/index'));
                
            }
        	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        	echo "success";	//请不要修改或删除
        }else {
            //验证失败
            echo "fail"; 
        }  
    }
     
    
    /** 用户登录 */
    function user_login(){
		$this->assign('top_name','用户登录');
		$this->display('./user.login');
    }   
    
    /** 用户注册 */
	function register(){
		$this->assign('top_name','用户注册');
		$this->display('./user.register');
	}
    
	/** 字符串拼写倒叙，保持之前字母的大小写位置 */
	function convert($input="Many people spell MySQL incorrectly"){   //
    		$words=trim($input);
    		$words=explode(' ',$words);
    		foreach($words as $key=>$word){ 
    			$len=strlen($word); 
    			for($i=$len;$i>0;$i--){  
	    			if(preg_match('/^[A-Z]+$/', $word[$len-$i])){
	    				echo strtoupper($word[$i-1]);
	    			}else{
	    				echo strtolower($word[$i-1]);
	    			}
    			}
    			echo ' '; 
    		} 
    }
	 
    
    

    /**  (打赏订单)支付宝同步通知 
     *  /index/alipayWapReturn?total_amount=0.03&timestamp=2017-12-17+16%3A54%3A23&sign=YmUGT1hIrdlWVd21QfYevHms6PjhvdZsPKPuSPr3XBpQ0Uyp%2FNFErU%2B2w8IWGrz81wJXI2Yg1%2FP4BVsHQfxb5LdCb40pE3uUIgt%2FuFLhsnr2YQ1poqw1%2Fk16EJN%2FJSPX5j%2BOR3ppOQ5TogFfZWTmyVdVQSbd1A%2B0CA%2Be0iIJudrse1A8TJ3eHp0IgYwME4pt2JysD65%2BNrx61Xs1DpwXku6eBrXqG3DsOblenpX%2B%2F27hz9Gl8paaNHiIv%2BxvyvNA5DWdVg24uAilN27Dn%2B%2BMDElZpH%2BCJeeyDy6f5SCaUVVI%2Bkvc9WpxXIdoitJ0wrtgphRfvwileOZFZc6OJ8L0CQ%3D%3D&trade_no=2017121721001004860292728717&sign_type=RSA2&auth_app_id=2017060807448034&charset=UTF-8&seller_id=2088721199060537&method=alipay.trade.wap.pay.return&app_id=2017060807448034&out_trade_no=1735073978&version=1.0
         /index/alipayWapReturn?out_trade_no=1735764011
     * */
    public function alipayWapReturn(){ 
    	phpInput('./Shop/Runtime/alipayWapReturn'.date('Ymd',gmtime()).'.log');
    	$order_sn = I('out_trade_no');
    	if (empty($order_sn)) $this->error('订单号(out_trade_no)不能为空.');
    
    	/* *
    	 * 功能：支付宝页面跳转同步通知页面
    	* 版本：2.0
    	* 修改日期：2017-05-01
    	* 说明：
    	* 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
    
    	*************************页面功能说明*************************
    	* 该页面可在本机电脑测试
    	* 可放入HTML等美化页面的代码、商户业务逻辑程序代码
    	*/
    
    	require_once("./Core/Library/Vendor/alipay/alipayTradeWapPay/config.php");
    	require_once './Core/Library/Vendor/alipay/alipayTradeWapPay/wappay/service/AlipayTradeService.php';
    	  
    	$arr=$_GET;
    	$alipaySevice = new \AlipayTradeService($config);
    	$result = $alipaySevice->check($_GET);
    
    	/* 实际验证过程建议商户添加以下校验。
    	 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
    	2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
    	3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
    	4、验证app_id是否为该商户本身。
    	*/
    	if($result) {//验证成功
    		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    		//请在这里加上商户的业务逻辑程序代码
    
    		//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    		//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
    
    		//商户订单号
    		$out_trade_no = htmlspecialchars($_GET['out_trade_no']);
    
    		//支付宝交易号
    		$trade_no = htmlspecialchars($_GET['trade_no']);
    
    		
    	    //开始事务
    	    M()->startTrans();     			
    		$order_mod = M('reward_order');
    		$where = array('order_sn'=>$order_sn);
    		if (!$find=$order_mod->where($where)->find()) $this->error("订单号:{$order_sn}未找到对应记录."); 
            if(empty($find['command_code'])){ //口令码
                $str = $find['order_sn'].$find['ip'].$find['album_id'];
                $command_code = strtoupper(short_md5($str));
                if(!$order_mod->where(array('ro_id'=>$find['ro_id']))->save(array('command_code'=>$command_code))){
                    M()->rollback();
                    $this->error('口令码更新失败.');
                }
                $find['command_code'] = $command_code;
                M()->commit();
                M()->startTrans();
            }
            
    		if($find['order_status']>=20){
    		    $this->assign('command_code',$find['command_code']);
    		    $page_url = U('album/reward',array('id'=>$find['album_id'],'t'=>microtime(true)));
    		    $this->assign('page_url',$page_url);
    			$this->display('index/pay_success');
    			//$this->error('充值订单已经付款成功.',U('album/reward',array('id'=>$find['album_id'],'t'=>microtime(true))));
    			return;
    		}
    
    			$data= array(
    					'order_status' => 20,
    					'pay_code' => 'alipay',
    					'pay_name' => '支付宝',
    					'total_amount' => I('total_amount'),
    					'platform_trade_no'=>I('trade_no'), //支付宝交易号
    					'pay_time'=> I('timestamp'),
    					'pay_info' => json_encode($_REQUEST),
    					'notify_ip' => get_client_ip(),
    					'notify_time' => date('Y-m-d H:i:s'),
    					'notify_agent' => $_SERVER['HTTP_USER_AGENT'],
    			);
    			$res = $order_mod->where($where)->save($data);
    			if($res==false){
    				M()->rollback();
    				fwlog('订单通知保存失败错误','alipayWapNotice');
    				$this->error('订单通知保存失败错误.');
    			}
    			 
    			$album = M('album')->where('id='.$find['album_id'])->find();
    			//更新累计打赏金额
    			$dt = array(
    					'total_reward_times'=>$album['total_reward_times']+1,
    					'total_reward_fee'=>$album['total_reward_fee']+I('total_amount')
    					);
    			M('album')->where(array('id'=>$find['album_id']))->save($dt);
    			
    			M()->commit();
    			#$this->success('充值成功.',U('Store/index'));
    			
    			if (!$find=$order_mod->where($where)->find()) $this->error("订单号:{$order_sn}未找到对应记录.");
    			
    			if($find['order_status']>11){
    				exit("success");
    			}
    	}
    	else {
    		//验证失败
    		$this->error("支付接口验证失败.",U('Store/index'));
    	}
    }
    
    /** (打赏订单)支付宝异步通知 */
    public function alipayWapNotify(){
    
    	phpInput('./Shop/Runtime/alipayWapNotify'.date('Ymd',gmtime()).'.log');
    
    	require_once("./Core/Library/Vendor/alipay/alipayTradeWapPay/config.php");
    	require_once './Core/Library/Vendor/alipay/alipayTradeWapPay/wappay/service/AlipayTradeService.php';
    	  
    	$arr=$_POST;
    	$alipaySevice = new \AlipayTradeService($config);
    	$alipaySevice->writeLog(var_export($_POST,true));
    	$result = $alipaySevice->check($arr);
    	
    	/* 实际验证过程建议商户添加以下校验。
    	 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
    	2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
    	3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
    	4、验证app_id是否为该商户本身。
    	*/
    	if($result) {//验证成功
    		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    		//请在这里加上商户的业务逻辑程序代
    	
    	
    		//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    	
    		//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
    	
    		//商户订单号
    	
    		$order_sn = $_POST['out_trade_no'];
    	
    		//支付宝交易号
    	
    		$trade_no = $_POST['trade_no'];
    	
    		//交易状态
    		$trade_status = $_POST['trade_status'];
    	
    	
    		if($_POST['trade_status'] == 'TRADE_FINISHED') {
    	
    			//判断该笔订单是否在商户网站中已经做过处理
    			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
    			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
    			//如果有做过处理，不执行商户的业务程序
    	
    			//注意：
    			//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
    		}
    		else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
    			//判断该笔订单是否在商户网站中已经做过处理
    			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
    			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
    			//如果有做过处理，不执行商户的业务程序
    			//注意：
    			//付款完成后，支付宝系统发送该交易状态通知
    			
    			$order_mod = M('reward_order');
    			$where = array('order_sn'=>$order_sn);
    			if (!$find=$order_mod->where($where)->find()) $this->error("订单号:{$order_sn}未找到对应记录.");
    			
    			if($find['order_status']<>11){
    				fwlog('订单:'.$order_sn.' 重复通知.','alipayWapNotice');
    				$this->error('该订单已经处理过了，无需重复处理.',U('Store/index'));
    			}
    			
    			M()->startTrans(); 
    			$data= array(
    					'order_status' => 20,
    					'pay_code' => 'alipay',
    					'pay_name' => '支付宝',
    					'total_amount' => I('total_amount'),
    					'platform_trade_no'=>I('trade_no'), //支付宝交易号
    					'pay_time'=> gmtime(),
    					'pay_info' => json_encode($_REQUEST),
    					'notify_ip' => get_client_ip(),
    					'notify_time' => date('Y-m-d H:i:s'),
    					'notify_agent' => $_SERVER['HTTP_USER_AGENT'],
    			);
    			$res = $order_mod->where($where)->save($data);
    			if($res==false){
    				M()->rollback();
    				fwlog('订单通知保存失败错误','alipayWapNotice');
    				$this->error('订单通知保存失败错误.');
    			}
    			 
    			$album = M('album')->where('id='.$find['album_id'])->find();
    			//更新累计打赏金额
    			$dt = array(
    					'total_reward_times'=>$album['total_reward_times']+1,
    					'total_reward_fee'=>$album['total_reward_fee']+I('total_amount')
    					);
    			M('album')->where(array('id'=>$find['album_id']))->save($dt);
    			 
    			
    			M()->commit();
    			#$this->success('充值成功.',U('Store/index'));
    			
    			if (!$find=$order_mod->where($where)->find()) $this->error("订单号:{$order_sn}未找到对应记录.");
    			
    			if($find['order_status']>11){
    				exit("success");
    			}
    			
    			
    			
    		}
    		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
    	
    		echo "success";		//请不要修改或删除
    	
    	}else {
    		errorLog('打赏订单异步通知验证失败。','alipayWapNotify');
    		//验证失败
    		echo "fail";	//请不要修改或删除
    	
    	}
    	  
    }
    
    
    /** 微信扫码付返回通知
     * @date 2017年12月5日
     * @author Abiao
     * <xml><appid><![CDATA[wxba2d4edf8088567b]]></appid>
     <bank_type><![CDATA[CFT]]></bank_type>
     <cash_fee><![CDATA[1]]></cash_fee>
     <fee_type><![CDATA[CNY]]></fee_type>
     <is_subscribe><![CDATA[N]]></is_subscribe>
     <mch_id><![CDATA[1488135302]]></mch_id>
     <nonce_str><![CDATA[6f1ba76cae77e0a3a0c04633d0f356b8]]></nonce_str>
     <openid><![CDATA[o8aT50Mj2KL0DkhuFbW9KBV6WV5M]]></openid>
     <out_trade_no><![CDATA[1733840189]]></out_trade_no>
     <result_code><![CDATA[SUCCESS]]></result_code>
     <return_code><![CDATA[SUCCESS]]></return_code>
     <sign><![CDATA[A45C073B35F830EE1DB37C917CDACFD8]]></sign>
     <time_end><![CDATA[20171205155309]]></time_end>
     <total_fee>1</total_fee>
     <trade_type><![CDATA[NATIVE]]></trade_type>
     <transaction_id><![CDATA[4200000038201712059243553948]]></transaction_id>
     </xml>
     */
    function wxqrpay_return(){
    	//微信支付通知日志
    	phpInput('./Shop/Runtime/phpInputWxqrpay.log');
    	include_once VENDOR_PATH.'JuenengPay/src/autoload.php';
    	$config = include_once './Core/Library/Vendor/JuenengPay/example/config/wx-qr.php';
    	$pay = \Jueneng\Pay::getInstance('weixinpay.qr');
    	$pay->setConfig($config);
    	 
    
    	$params = \Jueneng\WeixinPay\Helper::getNotifyRequestParams();
    	#$params = $xml;
    	$result = $pay->verifyReturn($params);
    	if($result){
    		$order_sn = $result['out_trade_no'];
    		//处理订单业务逻辑
    		$find = M('sms_order')->where(array('order_sn'=>$order_sn))->find();
    		if(!$find){
    			errorLog('订单号不存在','wxqrpayReturnErrorLog');
    			$this->error('订单号不存在。','',1000);
    		}
    		if($find['order_status']<>11){
    			errorLog('订单已经支付过，无需重复通知','wxqrpayReturnErrorLog');
    			$this->error('订单已经支付过，无需重复通知','',1000);
    		}
    
    		$order_mod = M('sms_order');
    		$where = array('order_sn'=>$order_sn);
    		M()->startTrans();
    		$data= array(
    				'order_status' => 20,
    				'pay_code' => 'wxpay',
    				'pay_name' => '微信支付',
    				'total_amount' => $result['cash_fee']/100,
    				'platform_trade_no'=> $result["transaction_id"], //微信交易号
    				'pay_time'=> gmtime() ,
    				'pay_info' => json_encode($result),
    				'notify_ip' => get_client_ip(),
    				'notify_time' => date('Y-m-d H:i:s'),
    				'notify_agent' => $_SERVER['HTTP_USER_AGENT'],
    		);
    		$res = $order_mod->where($where)->save($data);
    		if($res==false){
    			M()->rollback();
    			fwlog('订单通知保存失败错误','wxqrpayReturnErrorLog');
    			$this->error('订单通知保存失败错误.','',1000);
    		}
    		 
    		$data = array(
    				'total_charge_amount' => array('exp','total_charge_amount+'.$find['total_amount'].',remnant_num=remnant_num+'.$find['sms_quantity'])
    		);
    		if(!M('sms_account')->where(array('brand_id'=>$find['brand_id']))->save($data)){
    			M()->rollback();
    			fwlog('短信订单充值失败'.M()->getLastSql() ,'wxqrpayReturnErrorLog');
    			$this->error('短信订单充值失败.','',1000);
    		}
    		M()->commit();
    		#$this->success('充值成功.',U('Store/index'));
    		 
    		if (!$find=$order_mod->where($where)->find()){
    			errorLog("微信支付-订单号:{$order_sn}未找到对应记录.",wxqrpayReturnErrorLog);
    			$this->error("订单号:{$order_sn}未找到对应记录.");
    		}
    		 
    		if($find['order_status']>11){
    			//通知微信信封格式
    			echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    		}
    		 
    	}else{
    		echo '微信扫码付签名验证失败.';
    	}
    }
    
    /** 微信授权 
     *  参考： https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&id=open1419316505&token=&lang=zh_CN
     *  请求：https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx2b8ec378bf9b09ac&redirect_uri=http%3A%2F%2Fwww.graphis.club&response_type=code&scope=snsapi_base&state=abiao243test&connect_redirect=1#wechat_redirect
     *  返回：http://www.graphis.club/?code=081YIxfS0JM4Z82rsqgS0zKvfS0YIxfl&state=/album/reward/id/90.html
     *  
     *  curl https://api.weixin.qq.com/sns/oauth2/access_token -d "appid=wx2b8ec378bf9b09ac&secret=f5683171de3c42314c585839bf49fe7d&code=0018g6Ac1U5sYu0OCLzc1tSPzc18g6A2&grant_type=authorization_code"
{"access_token":"9_x-jP9FnvBOje20VVrWKSvK-8iuz6-s__LoJX90RL6ex0AApNfcqu4Q4gUvxs3ityWVNt4JOgyyJ41ZbybroPJw","expires_in":7200,"refresh_token":"9_REK9CzjtyreUtQOKegU-Ijc3G4QqeJYdhAb2yvS2_3abIB_IBN02dkmr4Cr5FXPGU4Hv1cDZE4m81TRfspCbvg","openid":"odzWh01_jxzmzui_mddj4v4cD4kI","scope":"snsapi_base"}
     * */
    function oauth(){
        $code = I('code','','trim');
        $state = I('state','','trim');
        
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $json = curl_post($url,"appid=wx2b8ec378bf9b09ac&secret=f5683171de3c42314c585839bf49fe7d&code={$code}&grant_type=authorization_code");
        $json = json_decode($json,true);
        $openid = $json['openid'];
        
        if(!empty($code) && !empty($state))
        redirect('http://a'.rand(100000, 999999).'.graphis.club'.$state.'&openid='.$openid);
    }
    
    /** 微信支付 */
    function pay(){
        $this->display('index/pay');
    }    
    
    /** 支付成功 */
    function pay_success(){
        $this->display('index/pay_success');
    }
    
    /** 获取订单状态 */
    function AjaxGetOrderStatus($order_sn){
        $order_status = M('reward_order')->where(array('order_sn'=>$order_sn))->getField('order_status');
        if($order_status>=20){
            $this->success('已支付');
        }else{
            $this->error('未支付');
        }
    }
    
}