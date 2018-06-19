<?php
/**
 * 程序默认控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Controller; 
use Vendor\Paypal; 
use Org\Util\Date; 
use Org\Util\Net; 
use Think\Controller;
use Org\Freight; 
use Common\Common\Basevisitor; 
/*
require VENDOR_PATH  . 'Paypal/bootstrap.php';
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\CreditCard;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Transaction;
*/

class TestController extends Controller {
	
	//判断是否下架-根据淘宝ID能获取到产品的OpenIid，能获取到的正常上架状态，不能获取到的，不存在或者下架.
	public function getOpenIid($id){
		$tbGoods = new \Admin\Controller\TbGoodsController();
		$openIid=$tbGoods->getOpenIid($id);
		return $openIid;
	}
	
	
	public function ip(){
		$ip = get_client_ip();  //如果要支持IP定位功能，需要使用扩展类库Org\Net\IpLocation，并且要配合IP地址库文件一起使用，例如：
		#dump($ip);
		$Ip = new \Org\Net\IpLocation('qqwry.dat'); // 实例化类 参数表示IP地址库文件
		$area = $Ip->getlocation($_SERVER[REMOTE_ADDR]); 
		$area1 = $Ip->getlocation('203.34.5.66'); // 获取某个IP地址所在的位置
		$area2 = $Ip->getlocation('115.238.34.2');
		$area3 = $Ip->getlocation('23.229.224.106');
		dump($Ip);
			dump($area);
		dump($area1);	
		dump($area2);
		dump($area3);
			C('SHOW_PAGE_TRACE',1);
	}
    public function index(){ 
    	 echo CONTROLLER_NAME.'#';
    	 echo ACTION_NAME.'#';
    	 echo MODULE_NAME.'#';
    	
    	
    	
    	echo $subject='sdfsdfsabcdfsdfsdfs<br>';
    	$search=array('a','b');
    	$replace=array('c','e','d');
    	#$res=str_ireplace($search, $replace, $subject); 
    	//$res=str_ireplace(array('f'=>1),'',$subject);
    	dump($res);
    	exit();
    	$yrc=new \Org\Freight\Yrc(Date('Ymd'),220.1,array('DestZipCode'=>38017,'DestStateCode'=>'TN','DestCityName'=>'COLLIERVILLE'));
    	#$yrc->LineItemNmfcClass1=65;   //25kg:65,1t:125 
    	//COLLIERVILLE|TN|38017|USA|
    	$price=$yrc->getRateQuote();
    	var_dump($yrc->_msg);
    	var_dump( $price );
    	//var_dump($yrc->BusId);
    	#var_dump( $yrc->Yrc() );
    	exit('yrcyrc');
    	
	/*	
    	$ch = curl_init();
	$timeout = 50;
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$file_contents = curl_exec($ch);
	curl_close($ch);
	$data=$file_contents;
	exit($data);
	*/
    	
		$xml = simplexml_load_string($data);
		var_dump($xml['DateTime']);
	
		var_dump($xml);
		/*
	    	$dom = new \DOMDocument();
			$res=$dom->loadXML($data); 
			var_dump($res);
			print $dom->saveXML();
		*/
	    }
	    
	    public function tracking(){
	    	$yrc=new \Org\Freight\Yrc_tracking('1234567890,1234567891');
	    	$res=$yrc->tracking();
	    	#var_dump($yrc->_obj);
	    	var_dump($yrc->_msg);
	    	var_dump($res);
	    }
	
	    
	 function tt(){ #exit('ddd');
	 	$obj=New TestController();
	 	$c=get_class_methods($obj);
	 	dump($c);
	  $r = new \ReflectionClass($obj); 
	  dump($r->getMethods());
	
	
	 }  
    
	 function paypal(){  
	   $pp=new \Vendor\Paypal\Paypal();  
	   $cardInfo=array(
	   		'setType'=>'visa',
	   		'setNumber'=>'4417119669820331',
	   		'setExpireMonth'=>'12',
	   		'setExpireYear'=>'2019',
	   		'setCvv2'=>'012',
	   		'setFirstName'=>'Joe',
	   		'setLastName'=>'Shopper'
	   );  
	   $res=$pp->payment(0.01,$cardInfo);
	   echo $pp->_msg.'<pre>';
	   var_dump($res);
	   var_dump($pp->_url);
	   var_dump($pp->_data);
	   exit(); 
	   #require_once '/ThinkPHP/Library/Vendor/Paypal/Payment.php';
	   
	}
	
	
	
	
	##########
}

 