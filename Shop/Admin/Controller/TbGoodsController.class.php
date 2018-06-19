<?php
/**
 * 淘宝API产品接口控制器(与CronController统一改用 阿里百川SDK baichuansdk)
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class TbGoodsController extends BackendController{
    var $appkey = null; 
    var $secret = null;
    function __construct(){
        parent::__construct();
        $this->TbGoodsController();
    }
    function TbGoodsController(){ 
        //require_cache('./tbk_api/tbkapi_test1.php'); 
        require "./baichuansdk/TopSdk.php";   //与CronController统一改用 阿里百川SDK baichuansdk 
		date_default_timezone_set('Asia/Shanghai'); 
		$this->appkey ='23301057';
		$this->secret	= '35fe38606fd21dd8d6e06299bb1351c1';
    }
     
    /** 根据商品ID查询商品详细信息，包括手机端详情页信息 */
    function getGoodsInfo(){  
    	$id=I('id');
    	if(empty($id)){
    	 	$this->error('淘宝Id错误.');
    	}
    	$openIid=$this->getOpenIid($id);   
		$c = new \TopClient;
		$c->appkey = $this->appkey;
		$c->secretKey = $this->secret;
		$req = new \TaeItemDetailGetRequest;
		$req->setBuyerIp("127.0.0.1");
		$req->setFields("itemInfo,priceInfo,skuInfo,stockInfo,rateInfo,descInfo,sellerInfo,mobileDescInfo,deliveryInfo,storeInfo,itemBuyInfo,couponInfo");
		$req->setOpenIid($openIid['open_iid']);
		//$req->setId("AAECZPIpACRa9kACPku0klL0");
		$resp = $c->execute($req, $sessionKey); //getGoodsList 
		$resp->tk_rate = $openIid['tk_rate'];
    	$this->ajaxReturn($resp);
    }
    
   /** 根据商品ID列表查询商品信息列表,可以获得Open_iid(不区分淘宝接口和天猫接口)
 	* http://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7629065.0.0.n89trr&apiId=23731 //TAE API  taobao.tae.items.list (商品列表服务)
 	* http://open.taobao.com/doc2/apiDetail.htm?apiId=23731&scopeId=11681  //高级电商能力 taobao.tae.items.list (商品列表服务)
 	*  包含产品的列表基本信息，但不包含图片和产品内容介绍( TaeItemDetailGetRequest包含 )
 	*/
    function getGoodsList($ids){  
		$c = new \TopClient;
		$c->appkey = $this->appkey;
		$c->secretKey = $this->secret;
		$req = new \TaeItemsListRequest;   //taobao.tae.items.list
		$req->setFields("title,nick,pic_url,location,cid,price,post_fee,promoted_service,ju,shop_name");
		//$req->setids("522674257218,527900985116,41944383443,22254815665,39932284103");  //AAHTZPIpACRa9kACPkiHwp_4,AAEEZPIpACRa9kACPjh6AA6Z,AAG9ZPIpACRa9kACPkjE6IKc,AAFiZPIpACRa9kACPki36uxi
		$req->setNumIids($ids);  //AAECZPIpACRa9kACPku0klL0
		$resp = $c->execute($req);   #var_dump($resp->items->x_item->tk_rate); 
    	$this->ajaxReturn($resp);
    }

 	/** 根据商品ID获取OpenIid 和 淘客佣金 tk_rate(方法同上!)*/
    function getOpenIid($id){  
		$c = new \TopClient;
		$c->appkey = $this->appkey;
		$c->secretKey = $this->secret;
		$req = new \TaeItemsListRequest;
		$req->setFields("title,nick,pic_url,location,cid,price,post_fee,promoted_service,ju,shop_name");
		$req->setNumIids($id);  //AAECZPIpACRa9kACPku0klL0
		$resp = $c->execute($req); 
		$open_iid = current($resp->items->x_item->open_iid);
		$tk_rate = current($resp->items->x_item->tk_rate);
		return array('open_iid'=>$open_iid,'tk_rate'=>$tk_rate);
    	//$this->ajaxReturn($resp);
    }
    
    /** Ajax方式根据商品ID获取OpenIid 和 淘客佣金 tk_rate(方法同上!)*/
    function ajaxGetTkrate($id){
    	echo json_encode($this->getOpenIid($id));
    }
    

    /** 批量获取用户信息-输入用户名 */
    function getUsersInfo(){  
    	$userids=I('user_name');
    	if(empty($userids)){
    	 	$this->tojson('用户名不能为空.');
    	}
		$c = new \TopClient;
		$c->appkey = $this->appkey;
		$c->secretKey = $this->secret;
		$req = new \OpenimUsersGetRequest;
		$req->setUserids($userids);   
		$resp = $c->execute($req);
    	$this->ajaxReturn($resp);
    }
    
}


?>