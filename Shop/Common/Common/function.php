<?php
/**
 * 公用方法类
 * @author jiwaini00000
 * @copyright 2014
 */
 
/** 时间方法 */
function gmtime(){
    return time();
}

/** 格式化时间戳 */
function todate($date,$format='Y-m-d H:i:s'){
    if(!$date) return '--';
    return date($format,$date);
}

/** 日期差几天
 *  @param beginDate:2018-01-26 endDatee:2018-01-26
 *  @return int days
 * */
function dateDiff($beginDate,$endDate){
    $diff = date_diff(date_create($beginDate),date_create($endDate))->format('%R%a');
    return (int)$diff;
}

function formatTime($the_time){
//   $now_time = date("Y-m-d H:i:s",time()+8*60*60);
//   $now_time = strtotime($now_time);
    $show_time = date('Y-m-d H:i',$the_time);
   $dur = time() - $the_time; #dump($dur);dump($the_time);
   if($dur > 30*24*3600){
    	return $show_time;
   }else{
	    if($dur < 60) 
	        return $dur.'秒前'; 
	    if($dur < 3600) 
	      	return floor($dur/60).'分钟前';
	    if($dur < 86400) 
	        return floor($dur/3600).'小时前'; 
		if($dur < 3*86400) //3天内
			return floor($dur/86400).'天前'; 
		if($dur <= 30*24*3600) //30天内
			return floor($dur/86400).'天前'; 
   }
 }

/** Php接收Post数据并写入日志  */
function phpInput($logfile='./Shop/Runtime/phpInput.log'){
	//接收回调通知
	$postStr=file_get_contents('php://input');
	if(empty($postStr)) $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
	if(empty($postStr)) $postStr = '';
	 
	//日志文件
	//$logfile='./Shop/Runtime/MiLogin'.date('Ymd').'.log';
	error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$postStr.PHP_EOL.PHP_EOL, 3, $logfile);
	 
	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
	if ($postObj === false) {
		$msg='parse xml error';
		error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$msg.PHP_EOL.PHP_EOL, 3, $logfile);
	}
}


/** 获取文件扩展名的n中方法 */
function getFileExt($filepath,$method='1'){
	if($method=='1'){
		return pathinfo($filepath, PATHINFO_EXTENSION);
	}elseif($method=='2'){
		$info = pathinfo($filepath);
		return $info['extension'];
	}elseif($method=='3'){
		return substr($filepath, strrpos($filepath, '.')+1);
	}elseif($method=='4'){
		return end(explode('.', $filepath));
	}elseif($method=='5'){
		return substr(strrchr($filepath, '.'), 1);
	}
}

/** 获取文件路径(去除扩展名) */
function getFilePathNoExt($filepath){
	return substr($filepath, 0,strrpos($filepath, '.'));
}

/** 生成缩略图 test.jpg => test(100x100).jpg */
function makeThumb($filepath,$sizesting='100x100'){
	$filepath = ltrim($filepath,'/');
	if(!is_file($filepath)) return false;
	$size=explode('x', strtolower($sizesting));
	if(!is_array($size)) return ;
	if(empty($filepath)) return ;
	$image = new \Think\Image();
	$image->open($filepath);
	$thumb_url= getFilePathNoExt($filepath).'('.$size[0].'x'.$size[1].').'.getFileExt($filepath);
	$image->thumb($size[0], $size[1],\Think\Image::IMAGE_THUMB_CENTER)->save($thumb_url);
	$thumb_url=ltrim($thumb_url,'.');
	return $thumb_url;
}

/** 获取缩略图,若不存在就生成缩略图  test.jpg => test(100x100).jpg  */
function getThumb($filepath,$size='100x100'){
	$filepath = ltrim($filepath,'/');
	if(!is_file($filepath)) return $filepath;
	$thumb_url = getFilePathNoExt($filepath).'('.$size.').'.getFileExt($filepath);
	if(!is_file($thumb_url)){
		$thumb_url = makeThumb($filepath,$size);
	}
	$thumb_url = '/'.ltrim($thumb_url,'/');
	return $thumb_url;
}


/** 文本中的获取第一张图片 */
function getFirstImg($body){
	//$body='asdfasd<img src="http\:\/\/erp.chicnova.com/media/thumb/product/thumb-6-14387652117834.jpg" width="60">sdfff<img src="http://erp.chicnova.com/media/thumb/product/thumb-6-14387652117834.jpg" width="60">sdfff';
	$body = stripslashes($body);
	$img_array = array();
	preg_match("/(src|SRC)=[\"|\'| ]{0,}(((http|https)\:\/\/|\/)(.*)\.(gif|jpg|jpeg|bmp|png))/isU",$body,$img_array);
	return ($img_array[2]);
}

/** 文本中的获取所有图片
 *  src="http:// src="https:// src="/   .(gif|jpg|jpeg|bmp|png)
 *  */
function getAllImg($body){
	$body = stripslashes($body);
	$img_array = array();
	preg_match_all("/(src|SRC)=[\"|\'| ]{0,}(((http|https)\:\/\/|\/)(.*)\.(gif|jpg|jpeg|bmp|png))/isU",$body,$img_array);
	return ($img_array[2]);
}
 
 

/**
 Function: 获取远程图片并把它保存到本地
 确定您有把文件写入本地服务器的权限
 变量说明:
 $url 是远程图片的完整URL地址，不能为空。
 $filename 是可选变量: 如果为空，本地文件名将基于时间和日期
 自动生成.
 */
function GrabImage($url,$filename="") {
	if($url==""):return false;endif;
	 
	if($filename=="") {
		//             $ext=strrchr($url,".");
		//             if($ext!=".gif" && $ext!=".jpg"):return false;endif;
		//             $filename=date("dMYHis").$ext;
		$file=strrchr($url,"/");
		$ext=strrchr($file,".");
		if($ext!=".gif" && $ext!=".jpg"):return false;endif;
		$filename = trim($file,'/');
	}
	 
	ob_start();
	readfile($url);
	$img = ob_get_contents();
	ob_end_clean();
	$size = strlen($img);
	 
	$fp2=@fopen($filename, "a");
	fwrite($fp2,$img);
	fclose($fp2);
	 
	return $filename;
}
 
 
/*
 *功能：php多种方式完美实现下载远程图片保存到本地
*参数：文件url,保存文件名称，使用的下载方式
*当保存文件名称为空时则使用远程文件原来的名称
*/
function getImage($url,$filename='',$type=0){
	if($url==''){return false;}
	if($filename==''){
		$ext=strrchr($url,'.');
		if($ext!='.gif' && $ext!='.jpg'){return false;}
		$filename=time().$ext;
	}
	//文件保存路径
	if($type){
		$ch=curl_init();
		$timeout=5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$img=curl_exec($ch);
		curl_close($ch);
	}else{
		ob_start();
		readfile($url);
		$img=ob_get_contents();
		ob_end_clean();
	}
	$size=strlen($img);
	//文件大小
	$fp2=@fopen($filename,'a');
	fwrite($fp2,$img);
	fclose($fp2);
	return $filename;
}
 
   
   
/** Curl POST 2016*/   
   function curl_post($url,$data=null,$is_post=1,$isGetHttpCode=FALSE){ 
         $curl = curl_init(); 
         curl_setopt($curl,CURLOPT_URL,$url);//获取内容url 
       	 curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); //兼容https请求  对认证证书来源的检查
		 curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); //兼容https请求 // 从证书中检查SSL加密算法是否存在
//       curl_setopt($curl,CURLOPT_HEADER,1);//获取http头信息 
//       curl_setopt($curl,CURLOPT_NOBODY,1);//不返回html的body信息 
//       curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);//返回数据流，不直接输出 
         
	    //curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    //curl_setopt($curl, CURLOPT_REFERER, "http://www.tmall.com/");
	    curl_setopt($curl, CURLOPT_HEADER, 0);

	    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.0)");
	    //$cookie_file = 'D:\www\kmtrade\temp\cookie.txt';
	    //curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file); // 存放Cookie信息的文件名称
	    //curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file); // 读取上面所储存的Cookie信息

	    curl_setopt($curl,CURLOPT_TIMEOUT,300); //超时时长，单位秒 
         curl_setopt($curl,CURLOPT_POST, $is_post );   //设置post方式 1-post,0-get
         curl_setopt ($curl, CURLOPT_POSTFIELDS, $data );  //data 为post数据，字符串形式 xml数据或者url参数形式
	 	// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //设置curl_exec()不直接输出而是返回 数据
		$header = array ( 
			"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8",
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			#"Accept-Encoding: gzip, deflate",
			"Accept-Language: zh-cn,zh;q=0.5",
			"Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7",
			"Keep-Alive: 115",
			"Connection: Keep-Alive",
			//"Referer: 'http://www.taobao.com/'",
		);
	 	 curl_setopt ($curl, CURLOPT_HTTPHEADER,$header);
	 	 $rtn= curl_exec($curl);   
         if($isGetHttpCode){
	 	 	$rtn1= curl_getinfo($curl); //CURLINFO_HTTP_CODE
	 	 	$rtn=array($rtn,$rtn1);
         }
         curl_close($curl);
         return  $rtn;
     }
   
   /**
   * curl get
   *
   * @param string $url
   * @param array $options
   * @return mixed
   */
  	function curlGet($url = '', $options = array())
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if (!empty($options)) {
      curl_setopt_array($ch, $options);
    }
    //https请求 不验证证书和host
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
   /**
   * curl get
   *
   * @param string $url
   * @param array $options
   * @return mixed
   */
  	function curlPost($url = '', $postData = '', $options = array())
  {
    if (is_array($postData)) {
      $postData = http_build_query($postData);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
    if (!empty($options)) {
      curl_setopt_array($ch, $options);
    }
    //https请求 不验证证书和host
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
  
  
  /**@json_encode后保持中文编码函数 
   * @author Abiao 2016
   **/
  function my_json_encode($bizdata){
		foreach ( $bizdata as $key => $value ) {  
        	$bizdata[$key] = urlencode ( $value );  
    	}   
		return urldecode(json_encode($bizdata));
  }
  
  /**@对数字对应的值进行urlencode   rawurlencode() 把空格变成%20 而不是+
   * @author Abiao 2016
   **/
  function my_urlencode($bizdata){
		foreach ( $bizdata as $key => $value ) {  
        	$bizdata[$key] = rawurlencode ( $value );  
    	}   
		return $bizdata;
  }  
  
  
function getCateType($typeid){
      if($typeid=='0'){ 
      	return '专题';
      }elseif($typeid=='1'){ 
      	return '<font color="green">商品</font>';
      }elseif($typeid=='2'){
      	return '<font color="#099">品牌</font>';
      } elseif($typeid=='3'){
      	return '<font color="#900">广告</font>';
      }
}
   
/** 转化成重量 */
function format_weight($weight, $punit){
    switch($punit){
        case 'g':
            return $weight;
            break;
        case 'kg':
            return $weight * 1000;
            break;
        case 't':
            return $weight * 1000 * 1000;
            break;
        default:
            break;
    }
}

/** 显示用户级别 */
function getGrade($level_id){ 
    return M('user_grade')->where('level_id='.$level_id)->getField('grade_name');
}

/** 显示重量 */
function show_weight($weight){
    if($weight > 1000000){
        $weight = $weight / (1000 * 1000).'t';
    }elseif($weight > 1000){
        $weight = $weight / 1000 .'kg';
    }else{
        $weight .= 'g';
    }
    return $weight;
}

/** 格式化价格 */
function format_price($price, $currency='￥%s'){
    $price = number_format($price, 2, '.', '');
    return sprintf($currency, $price);
}

/** 订单状态 */
function  order_status($order_status,$refund_status,$shipping_method=''){
    if(MODULE_NAME == 'Home' || MODULE_NAME == 'Webapp'){
        switch($refund_status){
            case '1':
                return 'Refund in Process';
                break;
            case '2':
                return 'Refund Completed';
                break;
            default:
                break;
        }
        //订单状态
        switch($order_status){
            case '0':
                return 'Closed';
                break;
            case '11':
                return 'Await Payment';
                break;
            case '20':
                if($shipping_method == 'CPU'){
                    return 'Await Pick Up';
                }
                return 'Await Shipment';
                break;
            case '30':
                return 'Await Confirmation';
                break;
            case '40':
                return 'Completed';
                break;
            default:
                return '--';
                break;
        }
    }else{
        switch($refund_status){
            case '1':
                return '退款中';
                break;
            case '2':
                return '关闭交易';
                break;
            default:
                break;
        }
        //订单状态
        switch($order_status){
            case '0':
                return '已取消';
                break;
            case '11':
                return '待付款';
                break;
            case '20':
                return '待发货';
                break;
            case '30':
                return '已发货';
                break;
            case '40':
                return '已完成';
                break;
            default:
                return '--';
                break;
        }
    }
}

/** 退款操作控制 */
function order_refund_status($refund_status, $order_status, $rec_id){
    if(MODULE_NAME == 'Admin'){
        if(in_array($order_status,array('20','30')) && !$refund_status)
            return '<a href="'.U('/Admin/Refund/apply',array('id'=>$rec_id)).'" target="_self">退款/退货</a>';
        elseif($order_status == '40' && !$refund_status)
            return '<a href="'.U('/Admin/Refund/apply',array('id'=>$rec_id)).'" target="_self">申请售后</a>';
        elseif($refund_status)
            return '<a href="'.U('/Admin/Refund/view',array('id'=>$rec_id)).'" target="_self">查看退款</a>';
    }elseif(MODULE_NAME == 'Home'){
        if(in_array($order_status,array('20','30','40'))){
            switch($refund_status){
                case '0':
                    //if($order_status == '40'){
                    //    return '<a href="'.U('/Refund/choice',array('id'=>$rec_id)).'" target="_blank">Request Customer Service</a>';
                    //}else{
                    return '<a href="'.U('/Refund/choice',array('id'=>$rec_id)).'" target="_blank">Refund/Return</a>';
                    //}
                    break;
                default:
                    return '<a href="'.U('/Refund/view',array('id'=>$rec_id)).'" target="_blank">Refund Details</a>'; 
                    break;
            }
        }
    }elseif(MODULE_NAME == 'Webapp'){
        if(in_array($order_status,array('20','30','40'))){
            switch($refund_status){
                case '0':
                    //if($order_status == '40'){
                    //    return '<a href="'.U('/Refund/choice',array('id'=>$rec_id)).'" target="_blank">Request Customer Service</a>';
                    //}else{
                    return '<a href="'.U('Refund/apply',array('id'=>$rec_id)).'" target="_blank">Apply Refund</a>';
                    //}
                    break;
                default:
                    return '<a href="'.U('Refund/view',array('id'=>$rec_id)).'" target="_blank">Refund Details</a>'; 
                    break;
            }
        }
    }
    
}

/** 退款单状态 */
function refund_status($refund_status){
    if(MODULE_NAME == 'Admin'){
        switch($refund_status){
            case '11':
                return '已申请退款';
                break;
            case '20':
                return '同意退款';
                break;
            case '21':
                return '同意退款，请退货';
                break;
            case '22':
                return '已退货';
                break;
            case '30':
                return '已收货';
                break;
            case '33':
                return '已退款';
                break;
            case '40':
                return '取消退款';
                break;
            case '50':
                return '拒绝退款';
                break;
            default:
                return '--';
                break;
            
        }
    }elseif(MODULE_NAME == 'Home' || MODULE_NAME == 'Webapp'){
        switch($refund_status){
            case '11':
                return 'Refund Applied';
                break;
            case '20':
                return 'Refund Approved';
                break;
            case '21':
                return 'Product to Be Returned';
                break;
            case '22':
                return 'Product Returned';
                break;
            case '30':
                return 'Returned Product Received';
                break;
            case '33':
                return 'Refunded';
                break;
            case '40':
                return 'Refund Cancelled';
                break;
            case '50':
                return 'Refund Rejected';
                break;
            default:
                return '--';
                break;
            
        } 
    }
}

/** 返回状态图标 */
function return_ico($status, $field = '', $goods_id=0){
    if($status){
        return '<i class="icon_correct" field="'.$field.'" goods_id="'.$goods_id.'" enpty="ajax_edit" style="cursor:pointer"></i>';
    }else{
        return '<i class="icon_error" field="'.$field.'" goods_id="'.$goods_id.'" enpty="ajax_edit" style="cursor:pointer"></i>';
    }
}


/** 计算价格区间 */
function get_range_price($goods_id,$yuan=''){
    $_goods_spec_mod = M('GoodsSpecs');
    $where['goods_id'] = $goods_id;
    $min_price = $_goods_spec_mod->where($where)->min('price');
    $max_price = $_goods_spec_mod->where($where)->max('price');
    if($min_price == $max_price){
        return $yuan.$max_price;
    }
    return $yuan.$min_price.'~'.$yuan.$max_price;
}

/** 计算价格区间new */
function _get_range_price($goods_id,$yuan=''){
    $_goods_spec_mod = M('GoodsSpecs');
    $where['goods_id'] = $goods_id;
    $min_price = $_goods_spec_mod->where($where)->min('price');
    $max_price = $_goods_spec_mod->where($where)->max('price');
    if($min_price == $max_price){
        return $yuan.$max_price;
    }
    return 'Price from<strong>'.$yuan.$min_price.'</strong>to<strong>'.$yuan.$max_price.'</strong>';
}


/** 写入登录日志 */
function login_log($user_id, $user_name,$type='admin', $message){
    $_log_mod = M('LoginLog');
    $data = array(
        'user_id' => $user_id,
        'user_name' => $user_name,
        'log_ip' => get_client_ip(),
        'log_time' => gmtime(),
        'type' => $type,
        'message' => $message
    );
    $_log_mod->add($data);
}

 function getNameById($theName,$theTable,$theId,$keyId){ 
  	$m=M($theTable);
   	$result=$m->where($theId."=".intval($keyId))->getField($theName);  
    return $result;
 }

 //★★★★☆ 评价星级后台显示；
 function show_stars($num){
 	$num=floatval($num);
 	switch ($num){
 		case $num<=1:
 			$num_icon='★☆☆☆☆';
 			break; 
 		case $num<=2:
 			$num_icon='★★☆☆☆';
 			break; 
 		case $num<=3:
 			$num_icon='★★★☆☆';
 			break; 
 		case $num<=4:
 			$num_icon='★★★★☆';
 			break; 			
 		default:
 			$num_icon='★★★★★';
 			break;
 	}  
 	return $num_icon;
 }
 //是否默认状态
 function is_default($is_default){ 
 	return $is_default?'default':'-';
 }
 
 //是否微信浏览器
 function isWeixin(){ 
 	if ( strpos($_SERVER['HTTP_USER_AGENT'], 
 			'MicroMessenger') !== false ) { 
 			return true; 
 	} 
 	return false; 
 }
 
 //判断是否iPhone手机访问
 function isIphone(){
     $clientkeywords = array('iphone');
     if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
         return true;
     }
     return false;
 }
 
 //判断是否手机访问
 function isMobile()
 {
     // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
     if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
     {
         return true;
     }
     // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
     if (isset ($_SERVER['HTTP_VIA']))
     {
         // 找不到为flase,否则为true
         return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
     }
     // 脑残法，判断手机发送的客户端标志,兼容性有待提高
     if (isset ($_SERVER['HTTP_USER_AGENT']))
     {
         $clientkeywords = array ('nokia',
             'sony',
             'ericsson',
             'mot',
             'samsung',
             'htc',
             'sgh',
             'lg',
             'sharp',
             'sie-',
             'philips',
             'panasonic',
             'alcatel',
             'lenovo',
             'iphone',
             'ipod',
             'blackberry',
             'meizu',
             'android',
             'netfront',
             'symbian',
             'ucweb',
             'windowsce',
             'palm',
             'operamini',
             'operamobi',
             'openwave',
             'nexusone',
             'cldc',
             'midp',
             'wap',
             'mobile'
         );
         // 从HTTP_USER_AGENT中查找手机浏览器的关键字
         if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
         {
             return true;
         }
     }
     // 协议法，因为有可能不准确，放到最后判断
     if (isset ($_SERVER['HTTP_ACCEPT']))
     {
         // 如果只支持wml并且不支持html那一定是移动设备
         // 如果支持wml和html但是wml在html之前则是移动设备
         if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
         {
             return true;
         }
     }
     return false;
 }
 
 //是否全中文，是-返回false，否-返回false
 function isChineseStr($str) {
     if (preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$str)) {
         return true;
     } else {
         return false;
     }
 }
 
 //是否非法字符，是-返回非法字符(数组)，否-返回false
 function isIllegalStr($str){
     if (preg_match_all("/[^\x{4e00}-\x{9fa5}a-z0-9_-]/ui",$str,$getarr)) {
         return $getarr;
     } else {
         return false;
     }
 }
 
 /** 将xml信封转成数组 
  * */
 function xmlToArray($node) {
     $array = false;
 
     if ($node->hasAttributes()) {
         foreach ($node->attributes as $attr) {
             $array[$attr->nodeName] = $attr->nodeValue;
         }
     }
 
     if ($node->hasChildNodes()) {
         if ($node->childNodes->length == 1) {
             $array[$node->firstChild->nodeName] = xmlToArray($node->firstChild);
         } else {
             foreach ($node->childNodes as $childNode) {
                 if ($childNode->nodeType != XML_TEXT_NODE) {
                     $array[$childNode->nodeName][] = xmlToArray($childNode);
                 }
             }
         }
     } else {
         return $node->nodeValue;
     }
     return $array;
 }
 
 
 function from_user($user_id){ 
 	$result=getNameById('email','user','user_id',$user_id);
 	return $result?$result:'System';
 }  
 
 //促销活动类型
 function pro_type($pro_type){ 
 	$return='';
 	$pro_type=trim($pro_type);
 	if($pro_type=='integral'){
 		$return='送积分';
 	}elseif($pro_type=='discount'){
 		$return='折扣优惠';
 	}
 	return $return ;
 }
 
  //商品标题处理,默认：列表页逗号','用换行代替
 function deal_title($subject,$replace='<br/>'){ 
 	return  str_replace(',', $replace, $subject);
 }

  
 /*邮件模板变量替换后发送邮件 */
 function sendEmailByTemplate($tm_no,$email,$data=array(), $model = false, $attachment_file = ''){ 
 	 if(empty($tm_no)||empty($email)) return;
     $tpl=M('template')->where("tm_no='".$tm_no."'")->find();
     if($tpl){
          $user = M('user')->where('email="'.$email.'"')->find(); 
	      $password=$data['password']?$data['password']:'';
	      $first_name=$data['first_name']?$data['first_name']:$user['first_name'];
	      $last_name=$data['last_name']?$data['last_name']:$user['last_name'];
	      $site_url=$data['site_url']?$data['site_url']:'http://'.C('site_url');
	      $site_name=$data['site_name']?$data['site_name']:'http://'.C('site_name');
	      $login_url=$data['login_url']?$data['login_url']:$site_url.U('/Index/login_register'); 
	      $get_pwd_url=$data['get_pwd_url']?$data['get_pwd_url']:'';
	      
	      $search=array('{$email}','{$password}','{$first_name}','{$last_name}','{$login_url}','{$site_url}','{$site_name}','{$get_pwd_url}');
	      $replace=array($email,$password,$first_name,$last_name,$login_url,$site_url,$site_name,$get_pwd_url);
	      //传入数组自动转成模板标签{$key}格式
	      if(!empty($data)){
	      	 foreach ($data as $key => $val){
	      	 	$search[]='{$'.$key.'}';
	      	 	$replace[]=$val;
	      	 } 	
	      } 
	      
	      //邮件标题替换
	      $title=$tpl['tm_subject'];
	      $title=str_ireplace($search, $replace, $title);  
	      
	      //邮件内容替换
	      $content=str_ireplace($search, $replace, htmlspecialchars_decode($tpl['tm_content']));          	
          sendemail($email,$title,$content, $model, $attachment_file);
      } 
 }
            
/** 邮件发送  sendemail('test@qq.com,16932555@qq.com', 'test-title', 'test-msg');   
 * @param bool $model 邮件发送模式，false表示立即发送邮件，true表示加入邮件发送队列
 *  返回boolean true 或 false 
 */
function sendemail($to='',$subject='',$message='', $model = false, $attachment_file = ''){
    if(!$model){ //立即发送邮件
        $smtpserver       = C('mail_smtp');
    	$smtpserverport   = C('smtp_port');; 
    	$smtpusermail     = C('smtp_user'); 
    	$smtpuser         = C('smtp_user');
    	$smtppass         = C('smtp_pwd');
    	//$user_name 		  = C('mail_nickname');
    	//$mailtype='HTML'; 
    	import("Org.Util.Smtp");
    	//$smtp = new Smtp($smtpserver, $smtpserverport, true, $smtpuser, $smtppass); 
    	////$smtp->debug = false; 
    	//$user_name=mb_convert_encoding($user_name, "GBK", "UTF-8"); 
    	//$subject=mb_convert_encoding($subject, "GBK", "UTF-8"); 
    	//$message=mb_convert_encoding($message, "GBK", "UTF-8"); 
        $smtp = new smtp($smtpserver,$smtpserverport,$smtpuser,$smtppass, true);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
        if($attachment_file){
            $smtp->addAttachment($attachment_file);   
        }
        return $smtp->sendmail($to, $smtpusermail, $subject, $message,'','');
    	//return $smtp->sendmail($to, $smtpusermail,$user_name, $subject,$message, $mailtype);
    }else{ //加入邮件发动队列
        $_email_sendlist_mod = M('EmailSendlist');
        $data = array(
            'email' => $to,
            'send_subject' => $subject,
            'send_content' => $message,
            'error' => 0,
            'last_send' => gmtime(),
        );
        if(!$_email_sendlist_mod->add($data)){
            return false;
        }
        return true;
    }
}
 


/** 生成二维码图片 
 *      $value = 'http://www.learnphp.cn'; //二维码内容  
 *      $errorCorrectionLevel = 'L';//容错级别  
 *      $matrixPointSize = 6;//生成图片大小 
 *      $filename = './test.jpg';
 *  */
function _gen_qrcode($value="http://www.kezhilian.com",$filename=FALSE,$matrixPointSize=12,$errorCorrectionLevel = 'L'){
    import("Org.Util.QRtools");  
    \QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize);
}


/** 读取目录下文件列表 */
function getfile($dir){
    $fileArray[]=NULL;
    if (false != ($handle = opendir ( $dir ))) {
        $i=0;
        while ( false !== ($file = readdir ( $handle )) ) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".."&&strpos($file,".")) {
                $fileArray[$i]= $file;
                if($i==100){
                    break;
                }
                $i++;
            }
        }
        //关闭句柄
        closedir ( $handle );
    }
    return $fileArray;
}

/** 二维数组排序 */
function array_sort($arr,$keys,$type='asc'){ 
	$keysvalue = $new_array = array();
	foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	}
	if($type == 'asc'){
		asort($keysvalue);
	}else{
		arsort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k=>$v){
		$new_array[$k] = $arr[$k];
	}
	return $new_array; 
}

/** 退款原因 */
function get_refund_reason(){
    return array(
        '无理由退款',
        '商品质量问题',
        '商品投递错误',
        '收到货数量不对',
    	'其它问题',
    );
}


/** 静态文件图片url处理  
 *  加上或者去掉base_url 
 * */
function dealImg($imgurl,$base_url = true ){  
	$static_url = C('static_url');
	$site_url = C('site_url');
	if(empty($imgurl)){
		$imgsrc = $static_url.'Public/images/nopic2.jpg';
	}elseif(strstr($imgurl, $static_url)){
		$imgsrc = $imgurl;
	}elseif(strstr($imgurl, 'http://') || strstr($imgurl, 'https://')){
		$imgsrc = $imgurl;
	}else{ 
		$imgsrc = $static_url.$imgurl;
	}   
	
	$imgsrc = str_ireplace($site_url,$static_url, $imgsrc);
	
	//图片路径是否带base_url
	if(!$base_url) { 
		$imgsrc = str_ireplace($static_url, '/', $imgsrc);
	} 
	return $imgsrc;
}
 

/** 数组转玩兔CDN
 * */
function dealImgCdn(&$list,$field='photo',$cdnurl='http://ymg280.image.alimmdn.com/'){
    if (!is_array($list)) return $list;
    if(isset($list[$field])){
        $list[$field]= $cdnurl.$list[$field];
        return $list;
    }
    foreach ($list as $key => $val){
        if(empty($val[$field])) continue;
        if((!strstr($val[$field],'https://'))&&(!strstr($val[$field],'http://')))
            $list[$key][$field]= $cdnurl.$val[$field];
    }
    return $list;
}

/** 单个图片转玩兔CDN
 * */
function dealImgCdnUrl($imgurl,$cdnurl='http://ymg280.image.alimmdn.com/'){
    if(empty($imgurl)) return;
    if(stristr($imgurl, 'https://')||stristr($imgurl, 'http://')){
        $list = $imgurl;
    }else{
        $list = $cdnurl.trim($imgurl,'.');
    }
    return $list;
}

/** 把逗号分隔的图片字符串转成<img />标记 */
function strToImg($wapImg,$width=500){
    $editImg='';
    $imgarr = explode(',',$wapImg);
    foreach ($imgarr as $val){
        $editImg.= "<img src='{$val}' width='{$width}' /><br>";
    }
    return $editImg;
}

/** 文件日志记录 */
function fwlog($msg,$topic='cron'){
    $fp = @fopen(TEMP_PATH.$topic.date('y-m-d',gmtime()).'.log','a+');
    @fwrite($fp,'['.date('Y-m-d H:i:s',gmtime()).']:');
    @fwrite($fp,$msg."\r\n");
    @fclose($fp);
} 

/** 记录日志文件 */
function errorLog($errStr='',$field='printer'){
	$logfile='./Shop/Runtime/'.$field.'_log'.date('Ymd').'.log';
	error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$errStr.PHP_EOL.PHP_EOL, 3, $logfile);

}

/** 生成退款单编号 */
function _gen_refund_sn(){
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);
    $timestamp = gmtime();
    $y = date('y', $timestamp);
    $z = date('z', $timestamp);
    $refund_sn = $y . str_pad($z, 3, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $_refund_mod  = M('Refund');
    $refunds = $_refund_mod->where(array('refund_sn'=>$refund_sn))->find();
    if (!$refunds){
        /* 否则就使用这个订单号 */
        return $refund_sn;
    }

    /* 如果有重复的，则重新生成 */
    return _gen_refund_sn();
}


/** 生成订单编号 */
function _gen_order_sn($table='order',$field='order_sn'){
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);
    $timestamp = gmtime();
    $y = date('y', $timestamp);
    $z = date('z', $timestamp);
    $order_sn = $y . str_pad($z, 3, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $_order_mod  = M($table);
    $orders = $_order_mod->where(array($field=>$order_sn))->find();
    if (!$orders){
        /* 否则就使用这个订单号 */
        return $order_sn;
    }

    /* 如果有重复的，则重新生成 */
    return _gen_order_sn();
}

/** 广告调用代码 */
function Ad($id=0){
    if(intval($id)){
        $ad = S("ad_".$id);
        if(empty($ad)||1){
            $ad = '';
            $adinfo = M('adplace')->find($id);
            if(!$adinfo){
                return '广告位不存在'.$id;
            }else{
                $view = new \Think\View();
                $template = APP_PATH."Adview/".($adinfo['tpl']?$adinfo['tpl']:'index.html');
                if(!is_file($template)){return '模板路径错误';}
                $to = APP_PATH.'Runtime/Cache/'.MODULE_NAME.'/'.md5($template).'.php';
                $adlist = M('ad')->where(array('pid'=>$adinfo['pid'],'status'=>1))->order('ad_id desc')
                          ->limit($adinfo['ad_num'])->select();
                if($adlist != false){
                    $view->assign('adlist',$adlist);
                    $view->display($template);
                    if(!is_file($to)){return '配置错误';}
                    ob_start();
                    include $to;
                    $ad = ob_get_contents();
                    ob_clean();
                    //生成缓存
                    S("ad_".$id,$ad);
                }
            }
        }else{
            return $ad;   
        }
    }else{
        return '系统错误';
    }
}


/** 产品浏览记录 */
function viewed_items(){
    $goods_id = cookie('history_goods');
    $where['goods_id'] = array('in',$goods_id);
    $_goods_mod = M('Goods');
    $list = $_goods_mod->where($where)->limit(10)->select();
    if(!$list){
        return false;
    }else{
        foreach($goods_id as $key => $id){
            foreach($list as $lk => $goods){
                if($id == $goods['goods_id']){
                    $newlist[$key] = $goods;
                }
            }
        }
        return $newlist;   
    }
}

/** 取消订单处理库存和积分 */
function cancel_order($order_id){
    if(!$order_id) return false;
    if(MODULE_NAME == 'Home'){
        $_order_mod = D('Admin/Order');
        $_order_goods_mod = D('Admin/OrderGoods');
        $_user_mod = D('Admin/User');
        $_score_log_mod = D('ScoreLog');
        $_goods_mod = D('Admin/Goods');
        $_goods_specs_mod = D('Admin/GoodsSpecs');   
    }else{
        $_order_mod = D('Order');
        $_order_goods_mod = D('OrderGoods');
        $_user_mod = D('User');
        $_score_log_mod = D('Home/ScoreLog');
        $_goods_mod = D('Goods');
        $_goods_specs_mod = D('GoodsSpecs');
    }
    $order = $_order_mod->find($order_id);
    //处理库存
    $order_goods = $_order_goods_mod->where("order_id='{$order_id}'")->select();
    if($order_goods != false){
        foreach($order_goods as $og => $goods){
            //修改产品SKU库存
            $_goods_specs_mod->where("spec_id='{$goods['spec_id']}'")->save(array(
                'sku' => array('exp','sku+'.$goods['quantity'])
            ));
            //修改产品总的库存
            $_goods_mod->where("goods_id='{$goods['goods_id']}'")->save(array(
                'goods_num' => array('exp','goods_num+'.$goods['quantity'])
            ));
        }
    }
    
    //处理积分返还用户积分
    if($order['integral_fee'] > 0){
        $score = $order['integral_fee'] * 100;
        $_user_mod->where("user_id='{$order['user_id']}'")->save(array(
            'score' => array('exp','score+'.$score)
        ));
        //增加积分操作日志
        $score_data = array(
            'user_id' => $order['user_id'],
            'type' => '+',
            'score' => $score,
            'desc' => 'Points returned due to No.'.$order['order_sn'].' order cancellation.',
            'ctime' => gmtime()
        );
        $_score_log_mod->add($score_data);
    }
}

/** 计算inside delivery费用  */
function js_inside_fee($total_weight){
    $inside_fee = 0;
    if($total_weight > 150 && $total_weight <= 1048){
        $inside_fee = 108.53;
    }elseif($total_weight > 1048){
        $inside_fee = round((0.1036 * $total_weight), 2);
    }
    return $inside_fee;
}

/**
 * 生成账单静态页面
 * @param int $order_id 订单ID
 * return sting
*/
function build_bill($order_id){
    
    //获取订单明细
    
}


/** 格式化百分比 */
function toRate($number,$len=2){ 
	return empty($number)?'-':sprintf("%.{$len}f",$number/100).'%';
   	//return round($number/100,$len).'%';
}

/** 品牌jsonToBrand */
function getJsonBrand($brandJson,$field='brand_name'){ 
	$brand = json_decode($brandJson,true); 
	return $brand[$field];
}

/** 品牌类别 */
function getBrandCate($cate_id=null,$brandcate = array(
		'1'=>'中国',
		'2'=>'美国',
		'3'=>'日本',
		'4'=>'韩国',
		'5'=>'澳大利亚',
		'6'=>'英国',
		'7'=>'法国',
		'8'=>'德国',
		'9'=>'新西兰',
		'10'=>'意大利',
		'11'=>'荷兰',
		'12'=>'西班牙',
		'13'=>'加拿大',
		'14'=>'俄罗斯',
		'15'=>'丹麦',
		'16'=>'泰国',
		'17'=>'台湾',
		'18'=>'香港'
	)){ 
	
	if(array_key_exists($cate_id, $brandcate)){
		$return = $brandcate[$cate_id];
	}else{
		$return = ($cate_id===null)?$brandcate:'-'; 
	} 
	return $return;
}

/** 用户孕期阶段 */
function getPeriod($period_id=null,$period = array(
		'1'=>'备孕',
		'2'=>'怀孕',
		'3'=>'产后', 
	)){  
	if(array_key_exists($period_id, $period)){
		$return = $period[$period_id];
	}else{
		$return = ($period_id===null)?$period:'-'; 
	} 
	return $return;
}

/** 孕期阶段转名称 ids='2,3,5'   */
function idsToGestation($ids=null,$gestation = array(
		'1'=>'备孕',
		'2'=>'孕早期',
		'3'=>'孕中期',
		'4'=>'孕晚期',
		'5'=>'产后月子',
		'6'=>'新生儿',
		'7'=>'宝宝', 
	)){ 
	if(empty($ids)) return '';  
	
	$return = '';
	if(!is_array($ids)){  //转数组
		$ids = explode(',', $ids);
	} 
	foreach ($ids as $id){
		if(array_key_exists($id, $gestation)){
			$return[] = $gestation[$id];
		} 
	}	
	$return = join(',', $return);

	return $return;
}


/** 品牌类别 */
function brandCate($cate_id){
	return  ($cate_id==0)?'':(($cate_id==1)?'国产':'进口');
}

/** 获取IP所在地区  */
function ipToArea($ip='',$type='ip'){
	if($type=='local'){
		$Ip = new \Org\Net\IpLocation('qqwry.dat');
		$area = $Ip->getlocation($ip);
		$province=iconv('gbk','utf-8',$area['country']);
		$prov_name=mb_substr($province,0,2,'utf-8');
		 
		return ($area);
	}else{
		$res = curl_post('http://ip.cn/'.$ip,0);
		preg_match('/所在地理位置：\<code\>(.*)\<\/code\>\<\/p\>/i', $res,$arr);
		return is_array($arr)?$arr[1]:'';
	}
}


/**
 * 返回16位md5值
 *
 * @param string $str 字符串
 * @return string $str 返回16位的字符串
 */
function short_md5($str) {
    return substr(md5($str), 8, 16);
}

?>