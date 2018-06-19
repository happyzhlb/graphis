<?php
/**
 * 阿里百川交易跟踪控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Admin\Controller;
use Think\Controller;
class TradetailController extends BackendController{
    var $_tradetail_mod = null;
    var $appkey = null;
    var $secretKey = null; 
    function __construct(){
        parent::__construct();
        $this->TradetailController();
        $this->appkey = '23301057';
        $this->secretKey = '35fe38606fd21dd8d6e06299bb1351c1'; 
    }
    function TradetailController(){
        $this->_tradetail_mod = D('Tradetail');
		$topic = array(
        	'taobao_tae_BaichuanTradeCreated' => '7-创建订单消息(下单未付款)',
        	'taobao_tae_BaichuanTradeSuccess' => '6-交易成功消息(确认收货后)',
        	'taobao_tae_BaichuanTradeRefundCreated' => '0-买家点击退款按钮后促发 ',
        	'taobao_tae_BaichuanTradeRefundSuccess' => '0-退款成功',
        	'taobao_tae_BaichuanTradePaidDone' => '2-付款成功(下单已付款)',
        	'taobao_tae_BaichuanTradeClosed' => '4或8-交易关闭(包括退款后交易关闭和创建订单后交易关闭)',
        ); 
        $this->assign('topic',$topic);
    }
    
    /** 交易跟踪列表 */
    function index(){ 
    	$shop_title = I('shop_title','','trim');
        if(!empty($shop_title)){
            $where['shop_title'] = array('like','%'.$shop_title.'%');
        }
        $order_id = I('order_id','','trim');
        if(!empty($order_id)){
            $where['order_id'] = array('like','%'.$order_id.'%');
        } 
        $topic = I('topic','','trim');
        if(!empty($topic)){
            $where['topic'] =  $topic ;
        } 
        //$where['_query'] = 'status=1&score=100&_logic=or';
        
//        $_acategory_mod = D('Blogcate');
//        $list = $_acategory_mod->get_category(0,true);
        
        $count = $this->_tradetail_mod 
                 ->where($where)->count();
        $page = new \Think\Page($count,20);
        $list = $this->_tradetail_mod->field('ttid,topic,msgid,pub_app_key,pub_time,buyer_id,paid_fee,shop_title,create_order_time,order_id,order_status,seller_nick')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                //->order('create_order_time desc')
                ->order('pub_time desc')
                ->select();    
        $this->assign('_list',$list);
        $this->assign('page',$page->show());
        $this->display('./tradetail.index');
    }
    
    /** 查看订单  */
    function view(){
    	$mod =  M('tradetail');
    	$ttid=I('id');
    	$list = $mod->where(array('id'=>$ttid))->find(); 
    	$this->assign('auction_infos',json_decode($list['auction_infos'],true)); 
    	$where = array(
    		'order_id'=>$list['order_id']
    	);
    	$orderlist = $mod -> where($where)->order('create_order_time desc')->select();
    	 
    	$this->assign('orderlist',$orderlist);
    	$this->assign('list',$list);
    	$this->display('./tradetail.view');
    }
    
    /** 获取交易跟踪  */
    function getTradeTail(){ 
    	date_default_timezone_set('Asia/Shanghai');  
	    include "./baichuansdk/TopSdk.php"; 
		include("./baichuansdk/top/request/TmcMessagesConsumeRequest.php");
		include("./baichuansdk/top/request/TmcMessagesConfirmRequest.php");
		
		//实例化TopClient类 
		$c=new \TopClient; 
		$c->appkey = $this->appkey; 
		$c->secretKey = $this->secretKey; 
		$c->gatewayUrl="http://gw.api.taobao.com/router/rest"; 
		$c->format="json";
		 
		//实例化具体API对应的Request类		 
		$req=new \TmcMessagesConsumeRequest;		 
		$req->setQuantity(10);		 
		$resp=$c->execute($req);		 
		//var_dump($resp); 
		$data = null;
		
		//确认消息 
		$req2=new \TmcMessagesConfirmRequest;  
		$messages_ids = '';
		M()->startTrans();
		foreach ($resp->messages->tmc_message as $key => $val){ 
			$data['topic'] = $val->topic;
			$data['pub_time'] = $val->pub_time;
			$data['pub_app_key'] = $val->pub_app_key;
			$data['msgid'] = $val->id;  
			$content = $val->content;
			$content = json_decode($content,true);
			$data = array_merge($data,$content); 
			$data['auction_infos'] = json_encode($data['auction_infos']);
			$res = $this->_tradetail_mod->add($data); 
			if($res){ //数据插入成功,加入待确认ids
				$messages_ids.= $val->id.","; 
			}
		}   
		echo M()->getLastsql(); 
		M()->commit();
		
		//进行消息确认
		$messages_ids=substr($messages_ids,0,strlen($messages_ids)-1);
		$res2 = $req2->setSMessageIds($messages_ids); 
		dump($res2); 
    }
    
    
    
    /** 添加交易跟踪 */
    function add(){
        if(!IS_POST){
            $_acategory_mod = D('Tradetailcate');
            $list = $_acategory_mod->get_category(0,true);
            $this->assign('acategory',$list);
            $this->display('./tradetail.form');
        }else{
            $data = array(
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'content' => I('content'),
            	'img' => I('img'),
            	'cutline'=> I('cutline','','trim'),
            	'clicks' => I('clicks',0,'intval'),
                'cate_id' => I('cate_id','','intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                'ctime' => gmtime()
            );
            if(!$this->_tradetail_mod->create($data)){
                $this->error($this->_tradetail_mod->getError());
                return;
            }
            $this->_tradetail_mod->add();
            $this->success('交易跟踪添加成功',U('/Admin/Tradetail'));
        }
    }
    
    /** 编辑交易跟踪 */
    function edit(){
        $bid = I('id','','intval');
        $tradetail = $this->_tradetail_mod->find($bid);
        if(!$tradetail){
            $this->error('交易跟踪不存在');
            return;
        }
        if(!IS_POST){
            $_acategory_mod = D('Tradetailcate');
            $list = $_acategory_mod->get_category(0,true);
            $this->assign('acategory',$list);
            $this->assign('tradetail',$tradetail);
            $this->display('./tradetail.edit');
        }else{
            $data = array(
                'bid' => $bid,
                'title' => I('title','','trim'),
            	'link_url' => I('link_url','','trim'),
                'cate_id' => I('cate_id','','intval'),
                'content' => I('content'),
            	'img' => I('img'),
            	'cutline'=> I('cutline','','trim'),
            	'clicks' => I('clicks',0,'intval'),
                'if_show' => I('if_show','','intval'),
            	'author' => I('author','','trim'),
            	'sort_order' => I('sort_order','','intval'),
                //'ctime' => gmtime()
            );
            $crt=$this->_tradetail_mod->create($data); 
            if(!$crt){
                $this->error($this->_tradetail_mod->getError());
                return;
            }
            $res=$this->_tradetail_mod->save($data);
            if(false!==$res)
            	$this->success('交易跟踪编辑成功',U('/Admin/Tradetail'));
            else 
            	$this->error('交易跟踪编辑失败.');
        }
    }
    
    /** 异步修改交易跟踪显示状态 */
    function ajax_edit_status(){
        $bid = I('id','','intval');
        $tradetail = $this->_tradetail_mod->field('bid,if_show')->find($bid);
        if(!$tradetail){
            $this->error('交易跟踪不存在');
            return;
        }
        if($tradetail['if_show']){
            $tradetail['if_show'] = 0;
        }else{
            $tradetail['if_show'] = 1;
        }
        if(!$this->_tradetail_mod->save($tradetail)){
            $this->error('修改交易跟踪显示状态失败');
            return;
        }
        $this->success('修改交易跟踪显示状态成功',U('/Admin/Tradetail'));
    }
    
    /** 删除交易跟踪 */
    function drop(){
        $bid = I('id','','trim');
        if(!$bid){
            $this->error('传入的ID为空，删除失败.');
            return;
        }
        if(strpos($bid,','))
            $bid = explode(',',$bid);
        if(is_array($bid)){
            $where['bid'] = array('in',$bid);
        }else{
            $where['bid'] = $bid;
        }
        if(!$this->_tradetail_mod->where($where)->delete()){
            $this->error('交易跟踪删除失败.');
            return;
        }
        $this->success('交易跟踪删除成功.',U('/Admin/Tradetail'));
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='tradetail';
	    if(I('savePath')){
	        $savePath=trim(I('savePath'),'/').'/';
	    }
	    $savePath=trim($savePath,'/').'/';
	    
        $upconfig = array( //图片上传设置
            'maxSize' => 1024*1024, //最大支持上传1M的图片
            'exts' => 'pdf,txt,jpg,jpeg,gif,png',  //图片支持类型
            'subName' => '',
            'savePath' => $savePath,
        	'subName'  => array('date','Ymd')
        ); 
    	if(!IS_POST){
    		$this->assign('upconfig',$upconfig);
    		$this->display('./upload');
    	}else{ 
    		if(empty($_FILES['photo']['size'])){
    			$this->error('请选择上传文件.');
    		}
	        $upfile['file'] = $_FILES['photo'];


	        $upload = new \Think\Upload($upconfig);
	        if(!$file = $upload->upload($upfile)){ 
	            $this->error($upload->getError());
	            return;
	        }
	        $url= C('site_url').$upload->__get('rootPath').$savePath.date('Ymd').'/'.$file['file']['savename'] ;
	        
	        $data=array(
	        	'url'=>$url,
	        	'size'=> ceil($_FILES['photo']['size']/1024).'k',
	        	'name'=> $file['file']['savename'],
	        	'filepath' => trim($url,C('site_url')),
	        );
	        
	        $this->success($data);
	        //$this->ajaxReturn($url,1);
    	}
    }    
    
}


?>