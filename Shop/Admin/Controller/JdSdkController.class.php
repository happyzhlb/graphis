<?php
/**
 * 京东API商品接口控制器(暂时不用,已经转移到ORG/Jd目录)
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class JdSdkController extends BackendController{
    var $appkey = null; 
    var $secret = null;
    function __construct(){
        parent::__construct();
        $this->JdSdkController();
    }
    function JdSdkController(){   
        require_once 'jdsdk/JdSdk.php'; 
		date_default_timezone_set('Asia/Shanghai'); 

		$this->appkey ='B41EF74F0B75201EF927366CDB1A0E01';  //happyzhlb
		$this->secret	= '7c9387b706734119bdc00a2ceeb66999'; 
		$this->accessToken = 'f315376f-37d5-4a3b-ba4e-fb4a689f4a0c';
		$this->serverUrl = 'https://api.jd.com/routerjson';
    }
     
    
    /** 京东获取推广商品(测试)  */
    function getUnionGoods($itemid="12485168576"){
        
        $c = new \JdClient();    
        $c->appKey = $this->appkey;    
        $c->appSecret = $this->secret;    
        $c->accessToken = $this->accessToken;    
        $c->serverUrl = $this->serverUrl;
    
        $req = new \ServicePromotionGoodsInfoRequest();
    
        $req->setSkuIds( $itemid );
    
        $resp = $c->execute($req, $c->accessToken);
    
        if($_GET['debug'])dump($resp);
        return $resp->getpromotioninfo_result;
    }
    
}


?>