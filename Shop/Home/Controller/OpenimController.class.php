<?php
/**
 * 淘宝Open Im API接口控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
class OpenimController extends FrontendController{
    var $appkey = null; 
    var $secret = null;
    function __construct(){
        parent::__construct();
        $this->OpenimController();
    }
    function OpenimController(){ 
        $this->apiv2_traffic();
        include_once "./tbk_api/TopSdk.php";
		date_default_timezone_set('Asia/Shanghai'); 
		$this->appkey ='23301057';
		$this->secret	= '35fe38606fd21dd8d6e06299bb1351c1';
    }
     
    /** 批量获取用户信息-输入用户名 */  //http://localhost:81/Openim/getUsersInfo?urlencode=0&user_name=abiao,abiao2,abiao3
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
		$resp = ((array)$resp);
		if(empty($resp)){
			$this->toJson('用户名不存在：'.$userids);
		}
		$this->toJson('获取用户信息成功.',1,current($resp)); 
    }
     
    /** 添加用户*/  // /Openim/addUser?urlencode=0&user_name=abiao7&password=asdfasdf
    function addUser(){  
    	$userid=I('user_name','','strtolower');
    	$password=I('password','','trim');
    	if(empty($userid)){
    	 	$this->tojson('用户名不能为空.');
    	}
    	if(empty($password)){
    	 	$this->tojson('密码不能为空.');
    	}
		$c = new \TopClient;
		$c->appkey = $this->appkey;
		$c->secretKey = $this->secret;
		$req = new \OpenimUsersAddRequest;
		$userinfos = new \Userinfos;
		$userinfos->nick=I("nick_name");
		$userinfos->icon_url=I("u_pic");
		$userinfos->email=I("email");
		$userinfos->mobile=I("mobile");
		$userinfos->taobaoid=I("taobaoid");
		$userinfos->userid=$userid;
		$userinfos->password=$password;
		$userinfos->remark=I("remark");
		$userinfos->extra=I($extra,"{}");  //扩张json
		$userinfos->career=I("career");
		$userinfos->vip="{}";   //vip（Json），最大长度512字节
		$userinfos->address=I("address");
		$userinfos->name=I("true_name");
		$userinfos->age=I("age",null);
		$userinfos->gender=I("gender");    //性别。M: 男。 F：女
		$userinfos->wechat=I("wechat");
		$userinfos->qq=I("qq");
		$userinfos->weibo=I("weibo");
		$req->setUserinfos(json_encode($userinfos));
		$resp = $c->execute($req);   
		$resp = ((array)$resp);  
		if(isset($resp['uid_succ'])){ 
			M('user')->where(array('user_name'=>$userid))->save(array('openImUid'=> $userid,'openImPwd'=>$password));
			$this->toJson('添加用户成功.',1,$resp['uid_succ']);
		}else{
			if($resp['fail_msg']->string=='data exist'){
				M('user')->where(array('user_name'=>$userid))->save(array('openImUid'=> $userid,'openImPwd'=>$password));
				//echo M()->getLastsql();
			}
			$this->toJson('添加用户失败.',0,$resp); 
		} 
    }

    /** 删除用户*/  // /Openim/deleteUser?urlencode=0&user_name=abiao7
    function deleteUser(){  
    	$userid=I('user_name'); 
    	if(empty($userid)){
    	 	$this->tojson('用户名不能为空.');
    	} 
		$c = new \TopClient;
		$c->appkey = $this->appkey;
		$c->secretKey = $this->secret;
		$req = new \OpenimUsersDeleteRequest;
		$req->setUserids("$userid"); 
		$resp = $c->execute($req);   
		$resp = ((array)$resp); 
		if(isset($resp['result'])){
			$this->toJson('删除用户成功.',1,$resp);
		}else{
			$this->toJson('删除用户失败.',0,$resp); 
		} 
    }
    
    
}


?>