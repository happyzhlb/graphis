<?php
/**
 * 模特控制器
 * @author Abiao
 * @copyright 2018
 */
namespace Webapp\Controller;
use Think\Page;

use Think\Controller;
class AlbumController extends FrontendController{
	var $_mod=null; 
	var $_albumcate_mod=null; 
	var $_albumcate_album_mod=null; 
    function __construct(){
        parent::__construct();
        $this->AlbumController();
    }
    
    function AlbumController(){
        $this->_mod=D('Album');  
        $this->_albumcate_mod=D('Admin/Albumcate');
        $this->_albumcate_album_mod=D('AlbumcateAlbum');
        $_public['albumcate'] = $this->_albumcate_mod->get_category(0,true); 
        $this->_models_mod = M('models');
        $_public['weekstar']= $this->_models_mod->where(array('if_show' => 1,'is_top'=>1))->limit('0,3')->order('view_num desc,id desc')->select();
        $this->assign($_public); 
    }
    
    /** 首页 */
    function index(){  
       $where['if_show']='1'; 
       $cate_id = I('cate_id','','trim');
       if($cate_id){

       		$album_ids = $this->_albumcate_album_mod->getAlbumidsByCateid($cate_id);
       		//$where['cate_id']=$cate_id; 
       		$where['id']=array('in',$album_ids);; 
       }
       //标题搜索
       $title=I('title','','trim');
       $title=str_ireplace(array(' ','+','-'), '%', $title);
       if(I('title','','trim')){
       		$where['b.title']=array('like','%'.$title.'%'); 
       }  
       
       //排序
       $orderdate = I('orderdate','desc');
       $orderhot = I('orderhot','desc');
       
       $param = array(
       		'cate_id'=>I('cate_id')
       );
       if($orderdate=='desc'){
       	 $param['orderdate'] = 'asc' ; 
       }
       if($orderhot=='desc'){
       	 $param['orderhot'] = 'asc' ; 
       }
       $order= "view_num {$orderhot},ctime {$orderdate}";
 	   $this->assign('param',$param);
       
       
       $totalRows=$this->_mod->where($where)->count();  
       $page=New Page($totalRows,16);
       $album_list=$this->_mod->where($where)->limit($page->firstRow.','.$page->listRows)
       ->order($order)
       ->select(); 
       # var_dump($album_list);
       
       $this->assign('album_list',$album_list);  
       $this->assign('page',$page->show());
       $this->display('./album.index'); 
    }    
    /** 详情页 */
    function detail(){       	
    	$album_id=I('id',0,'intval'); 
    	if(empty($album_id)){
    		$this->error('专辑id错误.');
    	}
    	$album=$this->_mod->where('id='.$album_id)->find(); 
    	$album['cate_name']=getNameById('cate_name','albumcate','cate_id',$album['cate_id']);
    	$this->assign('album',$album);
    	$seo=array(
    		'name'=>$album['title'].' - '.$album['cate_name'],
    		'keywords'=>$album['cate_name'], 
    	); 
//     	$prev_list=$this->_mod->where('id>'.$id)->order('id asc')->find();
//     	$next_list=$this->_mod->where('id<'.$id)->order('id desc')->find();
//     	$this->assign('prev_list',$prev_list);
//     	$this->assign('next_list',$next_list);
		
    	$models = M('models')->where(array('id'=>$album['models_id']))->find();
    	$this->assign('models',$models);
    	
    	
    	$total = M('picture')->where(array('album_id'=>$album_id,'if_show'=>1))->count();
    	
    	$pics = M('picture')->where(array('album_id'=>$album_id,'if_show'=>1))->limit(0,5)->select();
    	
    	$this->assign('total',$total); 
    	$this->assign('pics',$pics);
    	
    	if(I('debug')){ 
    		$this->display('index/album.detail1');
    	}else{
    		$this->display('index/album.detail');
    	}
   }  
   
   /** 读取专辑对应的关联类别，返回逗号分隔的ids字符串 */
   protected function getAlbumCateids($album_id){
   	if(empty($album_id)){
   		return false;
   	}
   	$where=array(
   			'album_id' => $album_id
   	);
   	$list=M('albumcate_album')->where($where)->field('cate_id')->select();
   	$arr= array();
   	foreach ($list as $key =>$val){
   		$arr[$key] =  $val['cate_id'] ;
   	}
   	$arr = join(',', $arr );
   	return $arr;
   }
   
   
   /** 读取专辑对应的关联类别，返回逗号分隔的ids字符串 */
   protected function getAlbumidsByCateid($cate_id){
   	if(empty($cate_id)){
   		return false;
   	}
   	$where=array(
   			'cate_id' => $cate_id
   	);
   	$list=M('albumcate_album')->where($where)->field('album_id')->select();
   	$arr= array();
   	foreach ($list as $key =>$val){
   		$arr[$key] =  $val['album_id'] ;
   	}
   	$arr = join(',', $arr );
   	return $arr;
   }
   
   /** 专辑图片打赏   */
   function reward(){
   	  $album_id = I('id','','intval'); 
   	  if(empty($album_id)) $this->error('相册专辑ID不能为空.');
   	  $where = array('album_id'=>$album_id);
   	  $album_mod = M('album'); 
   	  
   	  if(!IS_POST){
   	  	 $user_agent = $_SERVER['HTTP_USER_AGENT'];
   	  	 if(empty($user_agent)) exit('Forbiden');
   	  	 $ip = get_client_ip();
   	  	 $cookie_album_id = cookie('cookie_'.$album_id.'_'.$ip.'_'.date('Ymd'));
   	  	 if(empty($cookie_album_id)){
			  $ua_data = array(
			  	'album_id' => $album_id,
			  	'models_id' => (int)M('album')->where('id='.$album_id)->getField('models_id'),
			    'user_id' => 0,
			  	'type' => 'reward',
			  	'ip' => get_client_ip(),
			  	'user_agent' => $user_agent,
			  	'ctime' => gmtime(),
			  	'view_num' => 1
			  ); 
			  M('accesslog')->add($ua_data);
			  cookie('cookie_'.$album_id.'_'.$ip.'_'.date('Ymd'),$album_id);
   	  	 }else{
   	  	 	  $wh = array(
   	  	 	  		'album_id'=>$album_id ,
   	  	 	  		'ip' => $ip,
   	  	 	  		'_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())" 
   	  	 	  	);
   	  	 	  M('accesslog')->where($wh)->setInc('view_num'); 
   	  	 }
   	  	 $openid = I('openid','','trim'); 
   	  	 if(isWeixin() && empty($openid)){ 
   	  	     $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx2b8ec378bf9b09ac&redirect_uri=http%3A%2F%2Fwww.graphis.club&response_type=code&scope=snsapi_base&state=/album/reward/id/'.$album_id.'.html?code='.I('code').'&connect_redirect=1#wechat_redirect';
   	  	    #echo $url; exit;
   	  	     redirect($url); 
   	  	 }
   	  	 
   	  	 
		  $this->assign('album_id',$album_id);
	   	  $find  = $album_mod->where(array('id'=>$album_id))->field('title,reward_fee,pay_index')->find();
	   	  $this->assign('title',$find['title']);
	   	  $this->assign('pay_index',$find['pay_index']?$find['pay_index']:3); 
	   	  $album_mod->where(array('id'=>$album_id))->setInc('view_num');
	   	  $isPay = 0;  //未打赏成功
	   	  $wh = array(
	   	  			'order_status' => array('egt',20),
	   	  			'ip' => get_client_ip(),
	   	  			'album_id' => $album_id,
	   	  			'pay_time' => array('egt',gmtime()-3600*24*7)
	   	  		);
	   	  $ro = M('reward_order')->where($wh)->find();  
	   	  if($ro){
	   	  	$isPay = 1;  //已经打赏成功
	   	  }
	   	  
	   	  //调试或者设置打赏金额为0，免打赏
	   	  if(I('debug')=='true' || $find['reward_fee'] == 0) { $isPay = 1;  }
	   	  
	   	  //我的专辑免打赏
	   	  if(!$isPay){
	   	      session('[start]');
	   	      $user_id = session('user_id');
	   	      if($user_id){
        	   	  $wh = array(
        	   	      'album_id' => $album_id,
        	   	      'user_id' => $user_id
        	   	  );
        	   	  $ro = M('myalbum')->where($wh)->find();
        	   	  if($ro){
        	   	      $isPay = 1;  //已经打赏成功
        	   	  }
	   	      }
	   	  }
	   	  $this->assign('isPay',$isPay); 
	   	  $this->assign('reward_fee',$find['reward_fee']?$find['reward_fee']*100:0);
	   	  $count = M('picture')->field('id as ID,photo as url')->where($where)->count();
	   	  $this->assign('count',$count);
	   	   
	   	  $recommand_album = M('ad')->where(array('pid=2'))->order('sort_order asc')->limit(2)->select();
	   	  $img_td = '';
	   	  $img_td .= '<tr>';
	   	  foreach ($recommand_album as $key => $val){
	   	  	if($key>1 && ($key%2==0)) $img_td .= '</tr><tr>';
	   	  	$img_td .= '<td><a target="_blank" href="'.$val['url'].'"><img width="90%" src="'.getThumb($val['img'],'360x480').'" /><br><span style="color:#f2f2f2;">'.mb_substr($val['title'],0,10,'utf8').'</span></a></td>';
	   	  	#if(($key+1)%2==0) $img_td .= '</tr>';
	   	  }
	   	  $img_td .= '</tr>';
	   	  $this->assign('img_td',$img_td);
	   	  
	   	  $this->display('./album.reward');
   	  }else{  //打赏图片列表
	   	  #$json = '{"r":true,"i":[{"ID":"705438","url":"http://image.meituzz.com/image/36063_1491403271000_75dvsl4ujid7vv8orpss"},{"ID":"705439","url":"http://image.meituzz.com/image/36063_1491403271000_smiha8z0q5j2et9jrrhx"},{"ID":"705440","url":"http://image.meituzz.com/image/36063_1491403271000_at4yn8oow3l2sxbiy7r7"},{"ID":"705441","url":"http://image.meituzz.com/image/36063_1491403272000_7k275kmfl0jf7jxth3wj"},{"ID":"705442","url":"http://image.meituzz.com/image/36063_1491403272000_00tvra66rlv8ynqga0p5"},{"ID":"705443","url":"http://image.meituzz.com/image/36063_1491403272000_1recs35bm2eaibncgcf6"},{"ID":"705444","url":"http://image.meituzz.com/image/36063_1491403272000_lgg2ke1elilpm15sivb9"},{"ID":"705445","url":"http://image.meituzz.com/image/36063_1491403272000_9ie3m8i003uflypprc3w"},{"ID":"705446","url":"http://image.meituzz.com/image/36063_1491403273000_98jpe7inlj03geomdld3"},{"ID":"705448","url":"http://image.meituzz.com/image/36063_1491403273000_mdmdufn66cw2e2nni1j6"}]}';
	   	  #echo $json;
	   	  
	   	  $list = M('picture')->field('id as ID,photo as url')->where($where)->order('sort_order asc,id asc')->select();
	   	  #echo M()->getLastSql();
	   	  foreach ($list as $key => $val){
	   	  	//$list[$key]['url'] = getThumb($val['url'],'600x900'); 
	   	  }
	   	  $data = array(
	   	  	'r'=>true,
	   	  	'i'=>$list
	   	  );  
	   	  $this->ajaxReturn($data);
   	  }
   }
   
   /** 生成打赏订单  */
   function rewardOrder(){
       	header("Content-type: text/html; charset=utf-8");
       	$albumID = I('albumID');       	
       	$album = M('album')->where(array('id'=>$albumID))->find();        	
        $total_amount = $album['reward_fee'];
        if(empty($total_amount)){
            $this->error('充值金额错误.');
        }
        if(1>$total_amount){
        #    $this->error('充值金额不能小于1.');
        } 
        $order_sn = _gen_order_sn('reward_order','order_sn');
        $data = array(
            'order_sn' => $order_sn,
            'memo' => '专辑图片打赏订单号:'.$order_sn.'  ip:'. $_SERVER['REMOTE_ADDR'],
            'total_amount' => $total_amount,
            'order_status' => 11,
            'ctime' => gmtime(),
            'operator_id' => session('operator.operator_id'), 
            'ip' => get_client_ip(),
        	'user_agent' => $_SERVER[HTTP_USER_AGENT],
            'album_id' => $albumID,
        	'models_id' => $album['models_id']
        );
        $ro_id = M('reward_order')->add($data);
        if(!$ro_id){
            $this->error('打赏订单生成失败．');
        } 
   	
   	#dump(isWeixin()); 
     if(!isWeixin()){ //浏览器
	        include './Core/Library/Vendor/alipay/alipayTradeWapPay/include.php'; //引入支付宝类库 
	        #require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'service/AlipayTradeService.php';
	        #require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'buildermodel/AlipayTradeWapPayContentBuilder.php';
	        #require dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'./../config.php';
	        
	        $_POST['WIDout_trade_no'] = $order_sn;
	        $_POST['WIDsubject'] = '图片打赏';
	        $_POST['WIDtotal_amount'] = $total_amount;
	        $_POST['WIDbody'] = $data['memo'];
	        
	        if (!empty($_POST['WIDout_trade_no'])&& trim($_POST['WIDout_trade_no'])!=""){
	        	//商户订单号，商户网站订单系统中唯一订单号，必填
	        	$out_trade_no = $_POST['WIDout_trade_no'];
	        
	        	//订单名称，必填
	        	$subject = $_POST['WIDsubject'];
	        
	        	//付款金额，必填
	        	$total_amount = $_POST['WIDtotal_amount'];
	        
	        	//商品描述，可空
	        	$body = $_POST['WIDbody'];
	        
	        	//超时时间
	        	$timeout_express="1m";
	        
	        	$payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
	        	$payRequestBuilder->setBody($body);
	        	$payRequestBuilder->setSubject($subject);
	        	$payRequestBuilder->setOutTradeNo($out_trade_no);
	        	$payRequestBuilder->setTotalAmount($total_amount);
	        	$payRequestBuilder->setTimeExpress($timeout_express);
	        
	        	$payResponse = new \AlipayTradeService($config);
	        	$result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
	        
	        	return ;
	        }
  	  //}elseif(1){
  	      
  	  }else{ //微信  


  	      //echo "<script>alert('请点击右上角“...”用safari、firefox等浏览器中打开.');</script>";  exit;
  	      
  	      $openid = I('openid','','trim'); #var_dump($openid);
  	      if(empty($openid)) $this->error('微信用户的openid不能为空..');
  	      $url = 'http://'.strtolower($_SERVER['SERVER_NAME']).':'.$_SERVER['SERVER_PORT']; #echo $url; exit;
  	      $res = curlGet("{$url}/appjson/wxPrePay?urlencode=false&order_sn={$order_sn}&openid={$openid}");
  	      $return = json_decode($res,true);
  	      
  	      $return['timeStamp'] = gmtime();
  	      $return['paySignStr'] = "appId=wx2b8ec378bf9b09ac&nonceStr=".$return['data']['nonce_str']."&package=prepay_id=".$return['data']['prepay_id']."&signType=MD5&timeStamp=".$return['timeStamp']."";
  	      $paySignStr = $return['paySignStr']."&key=ZJZhijian828Zhijian828Zhijian828";
  	      $return['paySign'] = md5($paySignStr);
  	      
  	      #var_dump($return);
  	      if($return['code']==1){
  	          //echo  redirect("http://www.graphis.club/pay_success.html"); exit;
  	        echo '<script>  self.location="http://www.graphis.club/pay.html?'.$return["paySignStr"].'&paySign='.$return["paySign"].'&albumID='.$albumID.'&order_sn='.$order_sn.'";</script>';
  	        /* 
  	         echo '
  	              <script>  self.location="http://www.graphis.club/pay.html?'.$return["paySignStr"].'&paySign='.$return["paySign"].'&albumID='.$albumID.'";
      	              function onBridgeReady(){    
                        	   var a = new Date().getTime(); 
                        		   a+="";  
                        	   var timeStamp = a.substring(0,10); 
                        	   
                        	   var paySignStr = "appId=wx2b8ec378bf9b09ac&nonceStr=NMjyKEsuSW84PwSU&package=prepay_id=wx0121310849732155a36d1afe1921393794&signType=MD5&timeStamp=1525181046&key=ZJZhijian828Zhijian828Zhijian828";
                        	   var paySign = "";
                        	   WeixinJSBridge.invoke(
                        		   "getBrandWCPayRequest", {
                        			   "appId":"wx2b8ec378bf9b09ac",     //公众号名称，由商户传入      
                        			   "nonceStr":"tkw35dCeLpArZXz5", //随机串     
                        			   "package":"prepay_id=wx0222302617576976774fee970727355100",     
                        			   "signType":"MD5",         //微信签名方式：     			   
                        			   "timeStamp":"1525271426" ,//timeStamp, //"1525169795",         //时间戳，自1970年以来的秒数    
                        			   "paySign":"749b9f45bc867a57ba15cc33cce3e61a" //微信签名 
                        		   },
                        		   function(res){     
                        			   if(res.err_msg == "get_brand_wcpay_request:ok" ) {}     // 使用以上方式判断前端返回,微信团队郑重提示：res.err_msg将在用户支付成功后返回    ok，但并不保证它绝对可靠。 
                        		   }
                        	   );  
                    	}
                    	
                    	if (typeof WeixinJSBridge == "undefined"){
                    	   if( document.addEventListener ){
                    		   document.addEventListener("WeixinJSBridgeReady", onBridgeReady, false);
                    	   }else if (document.attachEvent){
                    		   document.attachEvent("WeixinJSBridgeReady", onBridgeReady); 
                    		   document.attachEvent("onWeixinJSBridgeReady", onBridgeReady);
                    	   }
                    	}else{
                    	   onBridgeReady();
                    	}  
  	              </script>
  	              ';*/
  	          //$this->success($return);
  	      }else{ 
  	          $this->error('预支付请求失败');
  	      }
  	      
  	      
  	      
  	      
  	      
  	      exit;
  	       
  	      
  	      
		   	echo '
		   	<form id="alipayment" style="display:none" name="alipayment" action="/Core/Library/Vendor/alipay/alipayTradeWapPay/wappay/pay.php?WIDout_trade_no={$order_sn}&WIDsubject=图片打赏&WIDtotal_amount='.$album['reward_fee'].'&WIDbody=graphis图片打赏" method="post" target="_self">
		   	<div id="body" style="clear:left">
		   	<dl class="content">
		   	<dt>商户订单号
		   	：</dt>
		   	<dd>
		   	<input id="WIDout_trade_no" value="'.$order_sn.'" name="WIDout_trade_no">
		   	</dd>
		   	<hr class="one_line">
		   	<dt>订单名称1
		   	：</dt>
		   	<dd>
		   	<input id="WIDsubject" value="图片打赏" name="WIDsubject">
		   	</dd>
		   	<hr class="one_line">
		   	<dt>付款金额
		   	：</dt>
		   	<dd>
		   	<input id="WIDtotal_amount" value="'.$album['reward_fee'].'" name="WIDtotal_amount">
		   	</dd>
		   	<hr class="one_line">
		   	<dt>商品描述：</dt>
		   	<dd>
		   	<input id="WIDbody" value="图片打赏'.$album['reward_fee'].'元" name="WIDbody">
		   	</dd>
		   	<hr class="one_line">
		   	<dt></dt>
		   	<dd id="btn-dd">
		   	<span class="new-btn-login-sp">
		   	<button class="new-btn-login" type="submit" style="text-align:center;">确 认OK</button>
		   	</span>
		   	<span class="note-help">如果您点击“确认OK”按钮，即表示您同意该次的执行操作。</span>
		   	</dd>
		   	</dl>
		   	</div>
		   	</form>
		   	'; 
		   	echo "<script>$('#alipayment').submit();</script>";  //微信支持js但是屏蔽了支付宝跳转
   		}
   }
   
   /** 用户举报  */
   function report(){
   		$data = array(
   			'type' => 'album',
   			'key_id' => I('albumID'),
   			'content' => I('reason'),
   			'ctime' => gmtime(),
   			'ip' => get_client_ip(),
   		);
   		if(M('tipoff')->add($data)){
   			$this->success('举报成功。');
   		}
   }
   
}


?>