<?php
/**
 * Mygoods 控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Page;

use Think\Controller;
class MygoodsController extends FrontendController{  
	var $_acate_mod=null;
	var $_myarticle_mod=null; 
	var $_mygoods_mod=null;
	var $_myagoods_mod=null;	
	var $_user_address_mod = null;
	var $_cart_mod=null;
	var $_order_mod=null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    var $_promotion_mod = null;
    var $_goods_specs_mod = null;
    var $_goods_option_value_mod = null;
	var $_json=null;
    function __construct(){
        parent::__construct();
        $this->MygoodsController();
    }
    
    function MygoodsController(){ 
    	$this->_acate_mod=D('acategory'); 
        $this->_myarticle_mod=D('myarticle');          
        $this->_mygoods_mod = D('mygoods');
        $this->_myagoods_mod = M('myarticle_mygoods');        
        $this->_user_address_mod = D('UserAddress');
        $this->_cart_mod = D('cart');        
        $this->_order_mod = D('Admin/Order');
        $this->_order_goods_mod = D('Admin/OrderGoods');
        $this->_order_log_mod = D('Admin/OrderLog');
        $this->_promotion_mod = D('Admin/Promotion');
        $this->_goods_specs_mod = D('MygoodsSpecs');
        $this->_goods_option_value_mod = D('Mygoods_option_value'); 
    }
     
    
    /** 获取商品列表 */
    function getGoods($debug=FALSE){  
    	$where=array(
    		'is_on_sale'=>1
    	); 
    	$pageindex=I('pageindex',0,'intval');
    	if(empty($pageindex)) $pageindex=1; 
		
		//类别
    	$cate_id=I('cate_id',0); 
     	if(!empty($cate_id)){ 
			$where['cate_id']=$cate_id; 
		}   
  	
		//文章
		$article_id=I('article_id',0); 
    	if(!empty($article_id)){ 
			$where['article_id']=$article_id; 
		}   
		
    	$goods_ids=I('goods_ids',0,'trim'); 
    	if(!empty($goods_ids)){     		
    		$where['g.goods_id']=array('in',trim($goods_ids,',')); 
    	}      	 	
    	$model=$this->_mygoods_mod; 
    	//$TotalRecords=$model->where($where)->count();
    	$TotalRecords=$model->join(' as g join __MYARTICLE_MYGOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
	    	->Field('g.goods_id')
	    	->count();
			
    	$listrows=I('pagesize',10,'intval');
    	if($listrows>1000) $listrows=1000;
    	$PageCount=ceil($TotalRecords/$listrows);
    	$list=$model->join(' as g join __MYARTICLE_MYGOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
    		->page($pageindex.','.$listrows)
	    	->Field('g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale')
	    	->order('ag.orderNum asc,g.add_time desc')
	    	//->limit($page->firstRow.','.$page->listRows) 
	    	->select(); 
	    $listsql=$model->getLastsql();		   	
    	$myarticle_mygoods_mod=M('myarticle_mygoods');
		foreach($list as $key => $val){
			$list[$key]['goods_desc']=strip_tags(htmlspecialchars_decode($val['goods_desc']));
			$list[$key]['article_id']=$myarticle_mygoods_mod->where('goods_id='.$val['goods_id'])->limit('1')->getField('article_id');
			#echo M()->getLastsql();
		}
		$data=array('TotalRecords'=>$TotalRecords,'PageCount'=>$PageCount,'PageIndex'=>$pageindex,'Goods'=>$list);
    	if($debug){
    		if(isset($_REQUEST['show_sql'])) echo $listsql;
    		return $data;
    	}else{
    		$this->toJson('获取商品成功.',1,$data);
    	}
    }
    
    /** 获取商品介绍*/
    function getGoodsDesc(){  
    	$goods_id=I('goods_id');
    	if(empty($goods_id)){ $this->toJson('商品ID错误.'); }
    	$where=array(
    		'goods_id'=>$goods_id,
    	);  
    	$model=M('goods');   
    	$list=$model->where($where)->Field('goods_id,outer_id,goods_name,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,goods_desc,market_price,price,wapImg,sales,is_on_sale,click_count,add_time')->find();
    	if($list['goods_desc'])
		$list['goods_desc']=strip_tags(htmlspecialchars_decode($list['goods_desc']));
		
    	$this->toJson('获取商品成功.',1,$list);
    } 
     
    
    /** 文章列表  */
    function article(){     
    	$where =array(
    		'if_show'=>1, 
    	);   
    	if($cate_id = I('cate_id',0,'intval')){
			$where['cate_id']=$cate_id; 
		}    
    	if($article_id = I('article_id',0,'intval')){
			$where['article_id']=$article_id; 
		}  
		if($article_ids = I('article_ids',0,'trim')){ 
			$where['article_id']=array('in',trim($article_ids,',')); 
		}  
    	$pageindex=I('pageindex',1,'intval');
    	$pagesize=I('pagesize','','intval');
		//顶部轮播
		if(I('is_top','')){
			$where['is_top']=1; 
		}
		//首页显示
		if(I('is_index','')){
			$where['is_index']=1; 
		} 
		//列表展示数量
    	if(empty($pagesize)){
    		$pagesize=10;
    	} 
    	if($pagesize>1000) $pagesize=1000;
    	
        $count = $this->_myarticle_mod->where($where)->count();  
        $PageCount=ceil($count/$pagesize);
        C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
        $page = new \Think\Page($count,$pagesize); 
    	$list=$this->_myarticle_mod->where($where)
    		->Field('article_id,cate_id,title,photo0,photo,cutline,collect_num,view_num,video,ctime,is_top,is_index')
			->order('sort_order desc,ctime desc')
    		->limit($page->firstRow.','.$page->listRows) 
    		->select();
    	$data=array('TotalRecords'=>$count,'PageCount'=>$PageCount,'PageIndex'=>$pageindex,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    }   
     
    /** 搜索结果  */
    function searchResult(){     
    	$where =array(
    		'a.if_show'=>1, 
    	);   
    	$keywords=I('keywords','','trim');
    	if(empty($keywords)){
    		$this->toJson('请输入关键词.');
    	} 
		$keywords=str_replace(array(' ',"'",'"'),'%',$keywords);
    	$sqllike="a.title like '%{$keywords}%' or a.cutline like '%{$keywords}%' or g.goods_name like '%{$keywords}%' or g.goods_desc like '%{$keywords}%'";
    	if($cate_id = I('a.cate_id',0,'intval')){
			$where['a.cate_id']=$cate_id; 
		}    
    	if($article_id = I('article_id',0,'intval')){
			$where['a.article_id']=$article_id; 
		}  
    	$pageindex=I('pageindex',1,'intval');
    	$pagesize=I('pagesize','','intval'); 
		//首页显示
		if(I('is_index','')){
			$where['a.is_index']=1; 
		} 
    	if(empty($pagesize)){
    		$pagesize=10;
    	} 
    	
        $count = M()->query('SELECT COUNT(DISTINCT a.article_id) AS tp_count FROM rj_article a 
				 left join '.C('DB_PREFIX').'myarticle_mygoods as ag on a.article_id=ag.article_id
				 INNER JOIN rj_goods as g on ag.goods_id = g.goods_id 
				 WHERE ( a.if_show = 1 ) AND ('.$sqllike.')'); 
        $count = current($count[0]); 
        $PageCount=ceil($count/$pagesize);
        C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
        $page = new \Think\Page($count,$pagesize); 
    	$list=M('article')->alias('a')->distinct(true)
			->join('left join __MYARTICLE_MYGOODS__ as ag on a.article_id=ag.article_id')
    		->join('__GOODS__ as g on ag.goods_id = g.goods_id')
    		->where($where)->where($sqllike)
    		->Field('a.article_id,a.cate_id,a.title,a.photo0,a.photo,a.cutline,a.collect_num,a.view_num,a.video,a.ctime,a.is_top,a.is_index')
			->order('a.sort_order desc,a.ctime desc')
    		->limit($page->firstRow.','.$page->listRows) 
    		->select();   
    	$data=array('TotalRecords'=>$count,'PageCount'=>$PageCount,'PageIndex'=>$pageindex,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    }   
    
    /** 文章类别  */
    function acategory(){     
    	$model=D('Admin/Acategory');
    	//$list=$model->get_category(0,true); 
    	$where=array(
    		'if_show'=>1,
    		'parent_id'=>0
    	);
    	if($cate_id=I('cate_id',0,'intval')){
    		$where['parent_id']=$cate_id;
    	}
        if($cate_ids=I('cate_ids',0,'trim')){
    		$where['cate_id']=array('in',trim($cate_ids,','));
    	}
    	if($is_recommend=I('is_recommend',0,'intval')){
    		$where['is_recommend']=$is_recommend;
    		$where['parent_id']=array('gt','0');
    	}    	
    	$list=M('acategory')->where($where)->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc')->order('sort_order desc')->select();   
    	$count=count($list); 
    	$data=array('TotalRecords'=>$count,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    }    
	
    /** 文章所有小类别  */
    function getSubCate(){     
    	$model=D('Admin/Acategory');
    	//$list=$model->get_category(0,true); 
    	$where=array(
    		'if_show'=>1,
    		'parent_id'=>array('gt','0')
    	);
    	if($cate_id=I('cate_id',0,'intval')){
    		$where['parent_id']=$cate_id;
    	}
    	if($is_recommend=I('is_recommend',0,'intval')){
    		$where['is_recommend']=$is_recommend;
    		$where['parent_id']=array('gt','0');
    	}    	
    	$list=M('acategory')->where($where)->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc')->order('sort_order desc')->select();  
    	$count=count($list); 
    	$data=array('TotalRecords'=>$count,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    }  
    
    /** 广告列表  
     * 	返回所有数据
     * */
    function ad($debug=FALSE){ 
    	$model=D('Admin/Ad');
    	$where=array(
    		'status'=>1, 
    		//'pid'=>1
    	);    	
        if($_REQUEST['pid']) $where['pid'] = I('pid','','intval');
        
        //打包多个广告位,传入pids用逗号“,”分隔. 如pids=1,62
        if($_REQUEST['pids']){
            $where['pid'] = array('in',trim($_REQUEST['pids'],','));
        }
        $count = $this->_ad_mod->where($where)->count();
        $page = new \Think\Page($count,1000);
        $ads = $this->_ad_mod->field('ad_id,pid,title,clicks,img,type,referId') 
        	->where($where)
        	->order('ad_id DESC')
        	->limit($page->firstRow.','.$page->listRows)->select();
     	$listsql=M()->getLastsql();
		$ads=$this->getAds($ads);
        $data=array('TotalRecords'=>$count,'data'=>$ads);
        if($debug){
        	if(isset($_REQUEST['show_sql'])) echo $listsql;
    		return $data;
        }else{
        	$this->toJson('请求成功.',1,$data);
        } 
    }        
    
    /** 对referId 进行数据查询  */
    protected function getAds($ads){
        foreach ($ads as $key => $val){
        	$ads[$key]['img']=C('site_url').$val['img']; 
        	if($val['type']=='0'){ //品牌列表页	     		
	     		$list=$this->_acate_mod->where(array('parent_id'=>$val['referId'],'if_show'=>1))
	     			->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc')->select();
	     		$ads[$key]['referData']=$list;
	     		$ads[$key]['referName']=$this->_acate_mod->getFieldByCate_id($val['referId'],'cate_name');
        	}elseif($val['type']=='1'){ //分类列表页
	     		$list=$this->_acate_mod->where(array('parent_id'=>$val['referId'],'if_show'=>1))
	     			->Field('cate_id,cate_name,photo,b_photo,parent_id,type')->select();
	     		$ads[$key]['referData']=$list;
	     		$ads[$key]['referName']=$this->_acate_mod->getFieldByCate_id($val['referId'],'cate_name');
        	}elseif($val['type']=='2'){ //商品列表页 (指定文章的类别ID)
        		//"SELECT g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,ac.cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale,ag.article_id FROM `rj_goods` g JOIN `rj_myarticle_mygoods` ag on g.goods_id=ag.goods_id JOIN rj_article a on ag.article_id=a.article_id join rj_acategory ac on ac.cate_id=a.cate_id";
	     		$list=$this->_goods_mod
	     			->join(' as g join __MYARTICLE_MYGOODS__ as ag on g.goods_id=ag.goods_id')
	     			->join('__ARTICLE__ as a on ag.article_id=a.article_id')
		    		->join('__ACATEGORY__ ac on ac.cate_id=a.cate_id')
	     			->where(array('ac.cate_id'=>$val['referId']))
		    		->page('1,100')
			    	->Field('g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,ac.cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale,ag.article_id')
			    	->order('ag.orderNum asc,g.add_time desc')
			    	->select();
			    	//echo M()->getLastsql();
	     		$ads[$key]['referData']=$list;
	     		$ads[$key]['referName']=$this->_acate_mod->getFieldByCate_id($val['referId'],'cate_name');
        	}elseif($val['type']=='3'){ //文章列表页
	     		$article=$this->_article_mod->where(array('cate_id'=>$val['referId']))
	     			->Field('article_id,cate_id,title,photo0,photo,cutline,collect_num,view_num,video,ctime,is_top,is_index')
	     			->page('1,100')
	     			->order('sort_order desc,ctime desc')
	     			->select();
	     		$ads[$key]['referData']=$article;
	     		$ads[$key]['referName']=$this->_acate_mod->getFieldByCate_id($val['referId'],'cate_name');
        	}elseif($val['type']=='4'){ //文章的导购页（文章内容页）
	     		$article=$this->_article_mod->where(array('article_id'=>$val['referId']))
	     			->Field('article_id,cate_id,title,photo,cutline')->select();
	     		$ads[$key]['referData']=$article;
        	}elseif($val['type']=='5'){ //活动页（H5呈现）  
	     		$ads[$key]['referData']=null;
        	}elseif($val['type']=='6'){ //商品集合  
	     		$list=$this->_goods_mod->join(' as g join __MYARTICLE_MYGOODS__ as ag on g.goods_id=ag.goods_id')
		    		->where(array('g.goods_id'=>array('in',$val['referId'])))
		    		->page('1,100')
			    	->Field('g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale')  //ag.article_id
			    	->order('ag.orderNum asc,g.add_time desc')
			    	->distinct(TRUE)
			    	->select();  
	     		foreach ($list as $k => $v){
	     			$list[$k]['article_id']=$this->_myagoods_mod->where('goods_id='.$v['goods_id'])->getField('article_id');
	     		}
	     		$ads[$key]['referData']=$list;
        	}elseif($val['type']=='7'){ //文章集合 
	     		$article=$this->_article_mod->where(array('article_id'=>array('in',$val['referId'])))
	     			->Field('article_id,cate_id,title,photo0,photo,cutline,collect_num,view_num,video,ctime,is_top,is_index')->select();
	     		$ads[$key]['referData']=$article;
        	}elseif($val['type']=='8'){ //类别集合 
	     		$list=$this->_acate_mod->where(array('cate_id'=>array('in',$val['referId'])))
	     			->Field('cate_id,cate_name,photo,b_photo,parent_id,type')->select();
	     		$ads[$key]['referData']=$list;
        	}elseif($val['type']=='9'){ //品牌集合 
	     		$list=$this->_acate_mod->where(array('cate_id'=>array('in',$val['referId'])))
	     			->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc')->select();
	     		$ads[$key]['referData']=$list;
        	}elseif($val['type']=='11'){ //品牌详情页 
	     		$list=$this->_acate_mod->where(array('cate_id'=>array('in',$val['referId'])))
	     			->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc')->select();
	     		$ads[$key]['referData']=$list;
        	}
     	}
     	return $ads;
    } 
     
    
    /***
     * 添加购物车  
     * /mygoods/addToCart?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy&goods_id=123
     * */
    function addToCart(){
    	$_cart_mod = $this->_cart_mod;
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
    	$goods_id=I('goods_id');
    	if(empty($goods_id)) $this->toJson('goods_id不能为空.');
    	 
    	$spec_id=I('spec_id');
    	if(empty($spec_id)) $this->toJson('规格ID(spec_id)不能为空.');
    	
    	$wh=array(
    		'is_on_sale'=>1,
    		'goods_id'=>$goods_id,
    	);
    	$goods_info = $this->_mygoods_mod->where($wh)->find(); 
		if(!$goods_info){
			$this->toJson('商品不存在或已下架.');
		}
		
		$map=array(
			'user_id' => $user_id,
            'goods_id' => $goods_id
		);
		$cart=$this->_cart_mod->where($map)->find();
		if($cart){
			$this->_cart_mod->where($map)->setInc('quantity',1); 
			$cart_id = $cart['cart_id'];
		}else{
	    	$cart_data = array(
	               	'session_id' => $token,
	               	'user_id' => $user_id,
	               	'goods_id' => $goods_id,
	               	'goods_name' => $goods_info['goods_name'],
	               	'default_image' => $goods_info['goods_img'],
	    			'price' => $goods_info['price'], 
	    			'quantity'=>1
	       	);
	       	if(!$this->_cart_mod->create($cart_data)){
	             $this->toJson($this->_cart_mod->getError()); 
	        } 
	        $cart_id=$this->_cart_mod->add(); 
		}		
		$data=$this->_cart_mod->getByCart_id($cart_id);
		$this->toJson('添加到购物车成功..',1,$data); 
    }
    
    /** 传入商品ID，获取商品规格 */
	function getSpecs(){
    	$goods_id=I('goods_id');
    	if(empty($goods_id)) $this->toJson('goods_id不能为空.');
   		$where=array(
   			'goods_id' => $goods_id
   		);
   		$list=$this->_goods_specs_mod->where($where)->select();
   		foreach ($list as $key =>$val){
   			$map=array(
   				'option_value_id'=> array('in',$val['option_value_ids'])
   			);
   			$options = $this->_goods_option_value_mod->where($map)->field('value_name')->select();  
   			foreach ($options as $opt){
   				$list[$key]['option'].= " ".current($opt);
   			}  
   		}
   		
	   	$this->toJson('获取商品规格成功.',1, $list);
	 }
    
    /***
     * 修改购物车数量  
     * /mygoods/editCart?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy&cart_id=217&quantity=3
     * */
    function editCart(){
    	$_cart_mod = $this->_cart_mod;
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
    	$cart_id=I('cart_id');
    	if(empty($cart_id)) $this->toJson('cart_id购物车ID不能为空.');
		
		$map=array(
			'user_id' => $user_id,
            'cart_id' => $cart_id
		);
		$cart=$this->_cart_mod->where($map)->find(); 
		if($cart){ 
	    	$quantity=I('quantity');
	    	if(empty($quantity)) $this->toJson('购物车商品数量quantity必需是大于0的整数.');  	
			$dt=array('quantity'=>$quantity);
			$this->_cart_mod->where($map)->save($dt); 
			$returndata=array('quantity'=>$quantity);
			$this->toJson('修改购物车成功.',1,$returndata); 
		}else{
			$this->toJson('购物车商品不存在.');
		}	  
    }
    
	/***
     * 查看购物车  
     * /mygoods/viewCart?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy
     * */
    function viewCart(){
    	$_cart_mod = $this->_cart_mod;
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	} 
		
		$map=array(
			'user_id' => $user_id, 
		);
		$cart=$this->_cart_mod->where($map)->field('cart_id,user_id,goods_id,goods_name,price,quantity,default_image')->select(); 
		$cartlist=$cart;
		foreach ($cart as $key => $val ){ 
			$cart[$key]['market_price']=$this->_mygoods_mod->getFieldByGoods_id($val['goods_id'],'market_price');
		}
		if($cart){
			$this->toJson('查看购物车成功..',1,$cart);
		}else{ 
			$this->toJson('购物车没有商品.',1);
		} 
    }
    
	/***
     * 删除购物车  
     * /mygoods/viewCart?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy&cart_ids=3,2,1
     * */
    function deleteCart(){
    	$_cart_mod = $this->_cart_mod;
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	} 
		$cart_ids=I('cart_ids','','trim');
		$cart_ids=trim($cart_ids,',');
		$cart_arr=explode(',', $cart_ids); 
		foreach ($cart_arr as $key => $val){
			$map=array(
				'user_id' => $user_id,
				'cart_id'=>$val
			);  
			$res=$this->_cart_mod->where($map)->delete();  
		} 
		$this->toJson('删除购物车成功..',1,$res); 
    } 
    
    
    /**
     * 自营商品下单
     * /mygoods/submitOrder?urlencode=false&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy&cart_ids=263&shipping_address_id=50
     * */
    function submitOrder(){ 
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
    	$cart_ids=I('cart_ids');
    	if(empty($cart_ids)) $this->toJson('缺少cart_ids,请在购物车中提交下单.');
    	$cart_id = explode(',',$cart_ids);
        $order['carts'] = $this->get_cart_goods($cart_id,$user_id);
         

         if(!$order['carts']){
            $this->toJson('Failed:提交失败,购物车商品不存在.');
            return;
         }
        
        
            $shipping_address_id = I('shipping_address_id','','intval');
            //$billing_address_id = I('billing_address_id','','intval');
            if(!$shipping_address_id){
                $this->toJson('未选中收货地址.');
                return;   
            }
            $where = array(
                'user_id' => $user_id,
                'address_id' => $shipping_address_id
            );
            $address = $this->_user_address_mod->where($where)->find();
            if(!$address){
                $this->toJson('未匹配到你的收货地址，请先添加收货地址.');
                return;
            } 
             
            
            $shipping = C('SHIPPING');
            $shipping_info = array(
                'delivery' => array('shipping_code'=>'DEFAULT','shipping_name'=>'默认方式'),
                'shipping_fee' => 0
            ); 
            
            D('')->startTrans();
            //计算积分抵扣
            $score = I('score','','intval');
            if($score){
                $integral_fee = $score / 100;
            }else{
                $integral_fee = 0;
            }
            //处理订单单头
            $order_data = array(
                'order_sn' => _gen_order_sn(),
                'user_id' => $user_id,
                'order_status' => 11,
                'refund_status' => 0,
            	'order_type'=>'APP', 
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
            
            
                $order_data['consignee'] = $address['true_name'];
                $order_data['country'] = $address['country'];
                $order_data['state'] = $address['state'];
                $order_data['city'] = $address['city'];
                $order_data['address'] = $address['address'];
                $order_data['zipcode'] = $address['zipcode'];
                $order_data['telephone'] = $address['telephone'];
                $order_data['mobile'] = $address['mobile']; 
            
            //计算折扣优惠
            if(I('promotion_discount_id')){
                $pro_id = I('promotion_discount_id','','intval');
                $promotion = $this->_promotion_mod->find($pro_id);
                if($promotion != false){
                    $order_data['discount_fee'] = round(($order['carts']['subtotle'] * ((100 - $promotion['rate']) / 100)),2);
                    $order_data['discount_pro_id'] = $pro_id;
                    $order_data['totle_fee'] -= $order_data['discount_fee'];
                }
            }
            
            //计算可以获得的积分
            if(I('promotion_integral_id')){
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
                $this->toJson($this->_order_mod->getError());
                return;
            }
            $order_id = $this->_order_mod->add();
			if(!$order_id){
				$this->toJson('订单提交失败,请检查数据是否完整.');
			}
			$order_data['order_id']=$order_id;
			#echo M()->getLastsql();
            
            //处理订单明细
            foreach($order['carts']['carts'] as $key => $cart){
                $order_goods_data = array(
                    'order_id' => $order_id,
                    'goods_id' => $cart['goods_id'],
                    'goods_name' => $cart['goods_name'],
                    'default_image' => $cart['default_image'],
                    'original_price' => $cart['price'],
                    'present_price' => $cart['price'],
                    //'goods_attr' => $cart['spec_attr'],
                    //'spec_id' => $cart['spec_id'],
                    //'is_sample' => $cart['is_sample'],
                    'quantity' => $cart['quantity'],
                    //'weight' => $cart['weight'],
                    'goods_totle' => $cart['totle'],
                    'order_status' => 11
                );
                if(!$this->_order_goods_mod->create($order_goods_data)){
                    D('')->rollback();
                    $this->toJson($this->_order_goods_mod->getError());
                    return;
                }
                $this->_order_goods_mod->add();
                
                //库存处理
                //$this->_mygoods_mod->where("goods_id='{$cart['goods_id']}'")->setField('goods_num',array('exp','goods_num-'.$cart['quantity']));
                //M('GoodsSpecs')->where("spec_id='{$cart['spec_id']}'")->setField('sku',array('exp','sku-'.$cart['quantity']));
            }
            
            //积分抵扣，记录积分使用
            if($integral_fee){
                //积分处理
                $score_data = array(
                    'user_id' => $user_id,
                    'type' => '-',
                    'score' => $score,
                    'desc' => 'Points redeemed in the order.[order_sn:'.$order_data['order_sn'].']',
                    'ctime' => gmtime()
                );
                $_score_log_mod = D('ScoreLog');
                if(!$_score_log_mod->add($score_data)){
                    D('')->rollback();
                    $this->toJson('Saving points record failed.');
                    return;
                }
                
                //减去用户的积分余额
                $update_user_data = array(
                    'user_id' => $user_id,
                    'score' => array('exp','score-'.$integral)
                );
                if(!M('User')->save($update_user_data)){
                    D('')->rollback();
                    $this->toJson('Updating points record failed.');
                    return;
                }
            }
            
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'buyer|'.M('user')->getFieldByUser_id($user_id,'user_name'),
                'order_id' => $order_id,
                'from_status' => $order_data['order_status'],
                'to_status' => $order_data['order_status'],
                'note' => '用户新增订单',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->toJson('Saving order operation records failed.');
                return;
            }
            //下单成功，清空购物车
            //$this->_cart_mod->where(array('cart_id' => array('in',$cart_id)))->delete();
            D('')->commit();
            
            $this->toJson('下单成功.',1,$order_data);
            
            //前往支付页面
            //redirect(U('/Myorders/pay',array('id'=>$order_id)));
            //$this->success('Order placed successfully.',U('/Index'));
    	
    	
    }
    
	/***
     * 订单列表
     * /mygoods/orderList?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy
     * */
    function orderList(){
    	$_cart_mod = $this->_cart_mod;
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}   
    	
		$map=array(
			'user_id' => $user_id, 
			'is_delete'=>0
		);
		$order_id=I('order_id');
		if(!empty($order_id)) {
			$map['order_id']=$order_id;
		}
		$pageindex=I('pageindex',1,'intval');
    	$pagesize=I('pagesize','','intval'); 
		//首页显示
		if(I('is_index','')){
			$where['a.is_index']=1; 
		} 
    	if(empty($pagesize)){
    		$pagesize=10;
    	} 
    	
        $count = $this->_order_mod->where($map)->count();  
        $PageCount=ceil($count/$pagesize);
        C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
        $page = new \Think\Page($count,$pagesize);  
		
		$list=$this->_order_mod->where($map)
			->field('order_id,user_id,order_sn,order_status,refund_status,order_type,consignee,city,address,zipcode,telephone,mobile,shipping_code,shipping_name,goods_amount,shipping_fee,integral_fee,discount_fee,refund_fee,totle_fee,points,add_time,pay_code,pay_name,pay_time,pay_info,shipping_time,finish_time,comment_time,cancel_time,is_delete')
			->limit($page->firstRow.','.$page->listRows)
			->order('order_id desc')
			->select();
		foreach ($list as $key => $val)	{
			$list[$key]['order_goods']=$this->_order_goods_mod->where('order_id='.$val['order_id'])->select();
		}
		if($list){
			$this->toJson('获取订单列表成功..',1,$list);
		}else{ 
			$this->toJson('没有订单.',0);
		} 
    }
	
    /**
     * 	根据订单号查订单明细
     * **/
    function order_goods($order_id){
    	$order_id=I('order_id');
    	if(empty($order_id)) $this->toJson('order_id不能为空.');
    	$data=$this->_order_goods_mod->where('order_id='.$order_id)->select();
    	$this->toJson('获取订单明细成功.',1,$data);
    }
    
   
	/***
     * 取消订单
     * /mygoods/cancelOrder?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy
     * */
    function cancelOrder(){
    	$_cart_mod = $this->_cart_mod;
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}   
    	$order_id=I('order_id');
    	if(empty($order_id)) $this->toJson('订单order_id不能为空.');
		$map=array(
			'user_id' => $user_id, 
			'is_delete'=>0,
			'order_id'=>$order_id
		); 
		$data=array(
			'order_status'=>0,
			'cancel_time'=>gmtime()
		);
		$list=$this->_order_mod->where($map)
			->save($data); 
		if($list){
			$this->toJson('订单取消成功..',1,$list);
		}else{ 
			$this->toJson('订单取消失败.',0);
		} 
    }    
    
    
    /** 获取购物车产品信息 */
   protected function get_cart_goods($cart_id, $user_id=0){
        $list = array();
        $_err = ''; 
        $where['cart_id'] = array('in',$cart_id);
        $where['user_id'] = $user_id;
        $list['carts'] = $this->_cart_mod
                         ->where($where)->select();
        if(!$list['carts']){
            return false;
        }else{
            $list['subtotle'] = 0;
            //$list['subweight'] = 0;
            foreach($list['carts'] as $key => $val){ 
                $list['subtotle'] += $list['carts'][$key]['totle'] = $val['price'] * $val['quantity']; 
                //$list['subweight'] += (int)$val['weight'] * $val['quantity'];
            } 
            $list['subtotle']=round($list['subtotle'],2);
            return $list;
        }
    }
    
    
    /** 微信预支付
     * 签名字符串顺序：    	
			1、参数名ASCII码从小到大排序（字典序）；
			2、如果参数的值为空不参与签名；
			3、参数名区分大小写；
			4、验证调用返回或微信主动通知签名时，传送的sign参数不参与签名，将生成的签名与该sign值作校验。
			5、微信接口可能增加字段，验证签名时必须支持增加的扩展字段 
     *  */
    function wepay(){ 	 
    	$pay_code = trim(I('pay_code'));
    	$pay_code='Wepay';
        $paymentclass = new \Think\Payment();
       	$payment_info = $paymentclass->chekc_enabled_payment($pay_code);
    	if($payment_info === false){
                $this->toJson("{$pay_code} 该支付方式未被安装.");
                return;
        } 
    	//实例化支付类
        $payment = $paymentclass->set_payment($pay_code);
        $payment->set_config(unserialize($payment_info['pay_config'])); 
        
        $order_no=I('order_no');
        if(empty($order_no)) $this->toJson('订单号(order_no)不能为空.');  
        $total_fee=I('total_fee');
        if(empty($total_fee)) $this->toJson('总金额(total_fee)必须是大于0的整数(单位：分).');  
    	$data=array( 
    		'attach'=>'APP微信支付',   //附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
    		'body'=>'订单支付'.I('order_no'),  
    		'out_trade_no'=>$order_no, //Date('YmdHis').rand(00, 99),  //支付流水号
    		'spbill_create_ip'=>$_SERVER['REMOTE_ADDR'],
    		'total_fee'=>$total_fee,   //支付总金额
    	); 
    	$xml=$payment->payment($data); 
    	$dom = new \DOMDocument();
		$dom->loadXML($xml); 
		$xmlarr=$this->getArray($dom->documentElement); 
		$res=current($xmlarr['return_code'][0]); 
		if($res=='SUCCESS'){    
			if(current($xmlarr['result_code'][0])=='SUCCESS'){	  
				$return=array(
					'result_code'=>current($xmlarr['result_code'][0]),
					'return_msg'=>current($xmlarr['return_msg'][0]),
					'appid'=>current($xmlarr['appid'][0]),
					'mch_id'=>current($xmlarr['mch_id'][0]),
					'nonce_str'=>current($xmlarr['nonce_str'][0]),
					'sign'=>current($xmlarr['sign'][0]),
					'result_code'=>current($xmlarr['result_code'][0]),
					'prepay_id'=>current($xmlarr['prepay_id'][0]),
					'trade_type'=>current($xmlarr['trade_type'][0])
				);
    			$this->toJson('微信预支付订单请求成功.',1,$return);
			}else{
				$this->toJson('微信预支付订单请求失败.',0,$xmlarr);  //如果是商户订单号重复，一定要保证金额和单号一致
			}
		}else{
			$this->toJson('微信预支付订单请求失败.',0,$xmlarr);
		}
    }
    


	/**  转化xml成数组*/
	protected function getArray($node) {
	  $array = false;
	
	  if ($node->hasAttributes()) {
	    foreach ($node->attributes as $attr) {
	      $array[$attr->nodeName] = $attr->nodeValue;
	    }
	  }
	
	  if ($node->hasChildNodes()) {
	    if ($node->childNodes->length == 1) {
	      $array[$node->firstChild->nodeName] = $this->getArray($node->firstChild);
	    } else {
	      foreach ($node->childNodes as $childNode) {
	      if ($childNode->nodeType != XML_TEXT_NODE) {
	        $array[$childNode->nodeName][] = $this->getArray($childNode);
	      }
	    }
	  }
	  } else {
	    return $node->nodeValue;
	  }
	  return $array;
	}
     
    /**
     * 微信支付结果通用通知 
     * */
   function notify_url(){ 
	    $config = array(
	      	'mch_id' => '1326744401',
	      	'appid' => 'wx43f4bd20cce3d5dc',
	      	'key' => '20167161832YunmagouYunmagouYunma', 
	    ); 
	    /* 	    
	    $postStr ='
	    <xml><appid><![CDATA[wx43f4bd20cce3d5dc]]></appid>
		<attach><![CDATA[APP微信支付]]></attach>
		<bank_type><![CDATA[CFT]]></bank_type>
		<cash_fee><![CDATA[168]]></cash_fee>
		<fee_type><![CDATA[CNY]]></fee_type>
		<is_subscribe><![CDATA[N]]></is_subscribe>
		<mch_id><![CDATA[1326744401]]></mch_id>
		<nonce_str><![CDATA[8705]]></nonce_str>
		<openid><![CDATA[oEGKIuBQhUYq_TEmbCENr2iC1ydc]]></openid>
		<out_trade_no><![CDATA[1621529145]]></out_trade_no>
		<result_code><![CDATA[SUCCESS]]></result_code>
		<return_code><![CDATA[SUCCESS]]></return_code>
		<sign><![CDATA[E4447C5C1CF59F82925A73C2718F63C8]]></sign>
		<time_end><![CDATA[20160803213443]]></time_end>
		<total_fee>168</total_fee>
		<trade_type><![CDATA[APP]]></trade_type>
		<transaction_id><![CDATA[4006332001201608030499116502]]></transaction_id>
		</xml>
		';
	    */
	    //接收微信支付通知信封
	    $postStr=file_get_contents('php://input'); 
	    if(empty($postStr)) $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; 
	    if(empty($postStr)) $postStr = ''; 
	    
	    $return_code=I('return_code');
	    $return_msg=I('return_msg');
	    
	    //日志文件
	    $logfile='./Shop/Paylog/wepayNotify'.date('Ymd').'.log';
	    error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$postStr.PHP_EOL.PHP_EOL, 3, $logfile);
	    
	    $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA); 
	    if ($postObj === false) {
	      $msg='parse xml error';
	      error_log(date('Y-m-d H:i:s').PHP_EOL.'-----------------------------'.PHP_EOL.$msg.PHP_EOL.PHP_EOL, 3, $logfile);
	      die($msg);
	    }  
	    if ($postObj->return_code != 'SUCCESS') {
	      error_log(' return_code:'.$postObj->return_code.PHP_EOL.PHP_EOL, 3, $logfile);
	      die($postObj->return_msg);
	    } 
	    if ($postObj->result_code != 'SUCCESS') {
	      error_log(' result_code:'.$postObj->result_code.PHP_EOL.PHP_EOL, 3, $logfile);
	      die($postObj->err_code);
	    }
	    $arr = (array)$postObj;   
	    unset($arr['sign']);  
	    //dump(self::getSign($arr, $config['key']));
	    if (self::getSign($arr, $config['key']) == $postObj->sign) {  //签名验证成功	  
		  $order=$this->_order_mod->where(array('order_sn'=>$postObj->out_trade_no))->find();
		  //判断是否待支付状态,如果不是，拒绝继续执行业务逻辑
	      if($order['order_status']!='11'){ 
	      	error_log(PHP_EOL.'order_status error.',3,$logfile); 
	      	return ;
	      }
		  D()->startTrans();	
	      $data=array(
	      	'order_status'=>20,
	      	'pay_code'=>'Wepay',
	      	'pay_name'=>'微信支付',
	      	'pay_time'=>gmtime(),
	      	'pay_info'=>serialize($arr)
	      );
	      $map=array(
	      	'order_sn'=>$postObj->out_trade_no,
	      );
	      
	      $list=$this->_order_mod->where($map)->save($data);
	      
			
	        //记录订单操作日志
            $log_data = array(
                'log_user' => 'system|system',
                'order_id' => $order['order_id'],
                'from_status' => $order['order_status'],
                'to_status' => $data['order_status'],
                'note' => '微信通知支付成功.'.$_SERVER['REMOTE_ADDR'],
                'log_time' => gmtime()
            );
            if(!D('order_log')->add($log_data)){
                D('')->rollback();
                $this->error('订单操作日志保存失败');
                return;
            }
	      D()->commit();
	      
	      //通知微信信封格式
	      echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
	      error_log(date('Y-m-d H:i:s').' {Paid SUCCESS.}'.PHP_EOL.PHP_EOL, 3, $logfile);
	      
	    }else{ //签名验证失败
	    	 error_log(date('Y-m-d H:i:s').' {Sign Failed.}'.PHP_EOL.'=================================='.PHP_EOL.$postStr.PHP_EOL.PHP_EOL, 3, $logfile);
	    	 echo ' {Sign Failed.}';
	    } 
   } 

  /** 微信支付验签
   * 例如：
   * appid：  wxd930ea5d5a258f4f
   * mch_id：  10000100
   * device_info： 1000
   * Body：  test
   * nonce_str： ibuaiVcKdpRxkhJA
   * 第一步：对参数按照 key=value 的格式，并按照参数名 ASCII 字典序排序如下：
   * stringA="appid=wxd930ea5d5a258f4f&body=test&device_info=1000&mch_i
   * d=10000100&nonce_str=ibuaiVcKdpRxkhJA";
   * 第二步：拼接支付密钥：
   * stringSignTemp="stringA&key=192006250b4c09247ec02edce69f6a2d"
   * sign=MD5(stringSignTemp).toUpperCase()="9A0A8659F005D6984697E2CA0A9CF3B7"
   */
  public static function getSign($params, $key)
  {
    ksort($params, SORT_STRING);
    $unSignParaString = self::formatQueryParaMap($params, false);
    $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
    return $signStr;
  }

  protected static function formatQueryParaMap($paraMap, $urlEncode = false)
  {
    $buff = "";
    ksort($paraMap);
    foreach ($paraMap as $k => $v) {
      if (null != $v && "null" != $v) {
        if ($urlEncode) {
          $v = urlencode($v);
        }
        $buff .= $k . "=" . $v . "&";
      }
    }
    $reqPar = '';
    if (strlen($buff) > 0) {
      $reqPar = substr($buff, 0, strlen($buff) - 1);
    }
    return $reqPar;
  }
  
  
 /*** 微信支付API订单状态查询  **/
   function orderquery(){ 
       	$pay_code='Wepay';
        $paymentclass = new \Think\Payment();
       	$payment_info = $paymentclass->chekc_enabled_payment($pay_code);
    	if($payment_info === false){
                $this->toJson("{$pay_code} 该支付方式未被安装.");
                return;
        }
        
		//实例化支付类
        $payment = $paymentclass->set_payment($pay_code);
        $payment->set_config(unserialize($payment_info['pay_config'])); 
        
        //测试单号：1621635681 	
        $order_no=I('order_no');
        if(empty($order_no)) $this->toJson('订单号(order_no)不能为空.');  
		$data=array( 
    		//'transaction_id'=>'4006332001201608040525425305',  //优先
    		'out_trade_no'=>$order_no,
    	);
    	$xml=$payment->orderquery($data); 
    	if(!$xml){ $this->toJson('unknown error.'); }
    	$dom = new \DOMDocument();
		$dom->loadXML($xml); 
		$xmlarr=$this->getArray($dom->documentElement); 
		$return_code=current($xmlarr['return_code'][0]); 
		$trade_state=current($xmlarr['trade_state'][0]); 
		if($return_code && $trade_state){
			$this->toJson('订单支付成功',1,$xmlarr);
		}else{
			$this->toJson('订单支付失败',0,$xmlarr);
		}
   }
   
   
  
  /**  查询订单支付状态-(微信、支付宝 都调用这个方法查询支付是否成功) */
  function order_pay_status(){
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}  
  		$order_sn=I('order_sn','','trim');
  		if(empty($order_sn)){
  			$this->toJson('订单号order_sn不能为空.');
  		}
  		$where=array(
  			'order_sn'=>$order_sn
  		);
  		$order=$this->_order_mod->where($where)->find();
  		if($order){
  			
  			if($order['order_status']>=20){
  				$order_data=array(
					'order_id'=>$order['order_id'],
  					'order_sn'=>$order['order_sn'],
  					'order_status'=>$order['order_status'],
  					'refund_status'=>$order['refund_status'],
  					'pay_code'=>$order['pay_code'],
  					'pay_name'=>$order['pay_name'],
  					'pay_time'=>$order['pay_time'],
  					'pay_info'=>unserialize($order['pay_info'])
  				);
  				$this->toJson('订单支付成功',1,$order_data);
  			}else{
  				$order_data=array(
					'order_id'=>$order['order_id'],
  					'order_sn'=>$order['order_sn'],
  					'order_status'=>$order['order_status'],
  					'refund_status'=>$order['refund_status'] 
  				);
  				$this->toJson('订单未支付',0,$order_data);
  			}
  		}else{
  			$this->toJson('订单号不存在.');
  		}
  }
  
  //确认收货
  function confirmOrder(){
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}  
  		$order_id=I('order_id');
  		if(empty($order_id)) $this->toJson('订单order_id不能为空.'); 
  		
  		$where['order_id'] = $order_id;
        $where['user_id'] = $user_id;
        $order_info = $this->_order_mod->where($where)->find();
        if(!$order_info){
            $this->toJson('该订单不存在或者你没权限操作该订单.');
            return;
        }
        
        //判断订单是否允许确认收货
        if($order_info['order_status'] != 30 || $order_info['refund_status']){
            $this->toJson('当前订单状态不允许确认收货(只有待收货状态的订单才能确认收货).');
            return;
        }
        $order_data = array(
            'order_id' => $order_id,
            'order_status' => 40,
            'finish_time' => gmtime()
        );
        
        D('')->startTrans();
        if(!$this->_order_mod->create($order_data)){
            D('')->rollback();
            $this->toJson($this->_order_mod->getError());
            return;
        }
        $this->_order_mod->save();
        //修改订单明细的
        $this->_order_goods_mod->where("order_id='{$order_id}'")->save(array('order_status'=>$order_data['order_status']));
        //保存订单操作日志
        $log_data = array(
            'log_user' => 'buyer|'.getNameById('user_name', 'user', 'user_id', $user_id),
            'order_id' => $order_info['order_id'],
            'from_status' => $order_info['order_status'],
            'to_status' => $order_data['order_status'],
            'from_refund_status' => $order_info['refund_status'],
            'to_refund_status' => $order_info['refund_status'],
            'note' => '用户确认收货',
            'log_time' => gmtime()
        );
        if(!$this->_order_log_mod->add($log_data)){
            D('')->rollback();
            $this->toJson('修改订单状态失败.');
            return;
        }
        D('')->commit();
        //sendEmailByTemplate('order_completed', $this->visitor->get('email'), array('order_sn'=>$order_info['order_sn']));
        $this->toJson('确认收货成功.',1);
 
  }
  
  
    /** 申请退款 */
    function apply_refund(){
    	$refund_mod=D('Admin/Refund');
    	
    	$user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
    	
        $rec_id = I('rec_id','','intval');
        if(empty($rec_id)) $this->toJson('订单商品明细表rec_id不能为空.');
        $order_detail = $this->_get_order_detail($user_id,$rec_id);
        
        //判断订单状态是否允许退款
        if(in_array($order_detail['order_status'],array('0','11')) || $order_detail['refund_status']){
            $this->toJson('当前订单状态不允许退款.');
            return;
        }
         
        //判断是否在允许的时间内
        $nowtime = gmtime();
        $allow_max_time = $order_detail['pay_time'] + C('allow_refund_days') * 60*60*24;
        if($nowtime > $allow_max_time){
            $this->toJson(sprintf('超过 %s 天订单不允许退款.',C('allow_refund_days')));
            return;
        }
        $order_detail['refund_totle'] = round(($order_detail['totle_fee'] - $order_detail['shipping_fee']) / $order_detail['goods_amount'] * $order_detail['goods_totle'],2);
 
            if(!$order_detail['refund_sn']) $order_detail['refund_sn'] = _gen_refund_sn();
            $order_goods_data = array(
                'rec_id' => $rec_id,
                'refund_sn' => $order_detail['refund_sn'],
                'refund_type' => I('refund_type',0,'intval'),
                'refund_status' => 11,
                'refund_price' => I('refund_price',0,'doubleval'),
                'refund_num' => I('refund_num',0,'intval'),
                'refund_time' => gmtime(),
                'refund_reason' => trim(I('refund_reason')),
                'refund_note' => trim(I('refund_note'))
            );
            //退款金额是否符合限制
            if($order_goods_data['refund_price'] <= 0 or $order_goods_data['refund_price'] > $order_detail['refund_totle']){
                $this->toJson(sprintf('退款金额必须在 0 到 %s 之间.',$order_detail['refund_totle']));
                return;   
            }
            
            //退款数量是否符合限制
            if($order_goods_data['refund_num'] <0 or $order_goods_data['refund_num'] > $order_detail['quantity']){
                $this->toJson(sprintf('退款数量必须在 0 到 %s 之间.',$order_detail['refund_num']));
                return;
            }
            
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($order_goods_data)){
                D('')->rollback();
                $this->toJson($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save(); 
            
            //判断订单的单头的退款状态
            if(!$order_detail['o_refund_status']){ //更改订单表头的退款单状态
                $order_data = array(
                    'order_id' => $order_detail['order_id'],
                    'refund_status' => 1
                );
                if(!$this->_order_mod->create($order_data)){
                    D('')->rollback();
                    $this->toJson($this->_order_mod->getError());
                    return;
                }
                $this->_order_mod->save();
                $log_data = array(
                    'log_user' => 'buyer|'.getNameById('user_name', 'user', 'user_id', $user_id),
                    'order_id' => $order_detail['order_id'],
                    'from_status' => $order_detail['order_status'],
                    'to_status' => $order_detail['order_status'],
                    'from_refund_statu' => $order_detail['refund_status'],
                    'to_refund_status' => $order_data['refund_status'],
                    'note' => '用户修改了退款信息.',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    D('')->rollback();
                    $this->toJson('订单状态保存失败.');
                    return;
                }
            } 
            //保存退款凭证 , 上传凭证
            $refund_img = array();
            
			//获取传输的图片01 base64加密后的数据
			$base64_refund_img01 = I('refund_img01','','trim');
			if(!empty($base64_refund_img01)){
				//$this->toJson('图片传输失败.');
				$ext= strtolower(I('ext','png','trim')); 
				if(!in_array($ext, array('jpg','jpeg','gif','png'))){
					D('')->rollback();
					$this->toJson('头像图片的后缀不正确:'.$ext);
				}
				$imgpath = './Uploads/user/'.$user_id; 
				$new_file = $imgpath."/refund".substr(date('Y',time()),2).date('md',time()).rand(1000,9999).'.'.$ext;
				$res=mkdir($imgpath,777,true); 
				if(file_put_contents($new_file, base64_decode($base64_refund_img01))){
				   $refund_img01=$new_file;
				}else{
					D('')->rollback();
					$this->toJson('图片1保存失败');
				} 
		    	$refund_img[]= C('site_url').$refund_img01;  
			}
			
            //获取传输的图片02 base64加密后的数据
			$base64_refund_img02 = I('refund_img02','','trim');
			if(!empty($base64_refund_img02)){
				//$this->toJson('图片传输失败.');
				$ext= strtolower(I('ext','png','trim')); 
				if(!in_array($ext, array('jpg','jpeg','gif','png'))){
					D('')->rollback();
					$this->toJson('头像图片的后缀不正确:'.$ext);
				}
				$imgpath = './Uploads/user/'.$user_id; 
				$new_file = $imgpath."/refund".substr(date('Y',time()),2).date('md',time()).rand(1000,9999).'.'.$ext;
				mkdir($imgpath,777,true);
				if(file_put_contents($new_file, base64_decode($base64_refund_img02))){
				   $refund_img02=$new_file;
				}else{
					D('')->rollback();
					$this->toJson('图片2保存失败');
				} 
		    	$refund_img[]= C('site_url').$refund_img02;  
			}
			
           	//获取传输的图片03 base64加密后的数据
			$base64_refund_img03 = I('refund_img03','','trim');
			if(!empty($base64_refund_img03)){
				//$this->toJson('图片传输失败.');
				$ext= strtolower(I('ext','png','trim')); 
				if(!in_array($ext, array('jpg','jpeg','gif','png'))){
					D('')->rollback();
					$this->toJson('头像图片的后缀不正确:'.$ext);
				}
				$imgpath = './Uploads/user/'.$user_id; 
				$new_file = $imgpath."/refund".substr(date('Y',time()),2).date('md',time()).rand(1000,9999).'.'.$ext;
				mkdir($imgpath,777,true);
				if(file_put_contents($new_file, base64_decode($base64_refund_img03))){
				   $refund_img03=$new_file;
				}else{
					D('')->rollback();
					$this->toJson('图片3保存失败');
				} 
		    	$refund_img[]= C('site_url').$refund_img03;  
			}
			
            //获取传输的图片04 base64加密后的数据
			$base64_refund_img04 = I('refund_img04','','trim');
			if(!empty($base64_refund_img04)){
				//$this->toJson('图片传输失败.');
				$ext= strtolower(I('ext','png','trim')); 
				if(!in_array($ext, array('jpg','jpeg','gif','png'))){
					D('')->rollback();
					$this->toJson('头像图片的后缀不正确:'.$ext);
				}
				$imgpath = './Uploads/user/'.$user_id; 
				$new_file = $imgpath."/refund".substr(date('Y',time()),2).date('md',time()).rand(1000,9999).'.'.$ext;
				mkdir($imgpath,777,true);
				if(file_put_contents($new_file, base64_decode($base64_refund_img04))){
				   $refund_img04=$new_file;
				}else{
					D('')->rollback();
					$this->toJson('图片4保存失败');
				} 
		    	$refund_img[]= C('site_url').$refund_img04;  
			}
			
           //获取传输的图片05 base64加密后的数据
			$base64_refund_img05 = I('refund_img05','','trim');
			if(!empty($base64_refund_img05)){
				//$this->toJson('图片传输失败.');
				$ext= strtolower(I('ext','png','trim')); 
				if(!in_array($ext, array('jpg','jpeg','gif','png'))){
					D('')->rollback();
					$this->toJson('头像图片的后缀不正确:'.$ext);
				}
				$imgpath = './Uploads/user/'.$user_id; 
				$new_file = $imgpath."/refund".substr(date('Y',time()),2).date('md',time()).rand(1000,9999).'.'.$ext;
				mkdir($imgpath,777,true);
				if(file_put_contents($new_file, base64_decode($base64_refund_img05))){
				   $refund_img05=$new_file;
				}else{
					D('')->rollback();
					$this->toJson('图片5保存失败');
				} 
		    	$refund_img[]= C('site_url').$refund_img05;  
			} 
                            
            //保存退款协商记录
            $refund = array(
                'refund_sn' => $order_detail['refund_sn'],
                'refund_user' => 'buyer|'.getNameById('user_name', 'user', 'user_id', $user_id),
                'refund_status' => $order_goods_data['refund_status'],
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_type' => $order_goods_data['refund_type'],
                    'refund_price' => $order_goods_data['refund_price'],
                    'refund_reason' => $order_goods_data['refund_reason'],
                    'refund_note' => $order_goods_data['refund_note'],
                    'refund_img' => $refund_img,
                ))
            );
            if(!$refund_mod->create($refund)){
                @unlink($refund_img);
                D('')->rollback();
                $this->toJson($refund_mod->getError());
                return;
            }
            $res=$refund_mod->add();
            D('')->commit();
            $this->toJson('退款申请提交成功.',1,array('refund_id'=>$res));
 
    } 
    
    
    
    /** 修改退款信息 */
    function refund_edit(){    	
    	$refund_mod=D('Admin/Refund');
        $user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	} 
    	$user_name=getNameById('user_name', 'user', 'user_id', $user_id);
        $rec_id = I('rec_id','','intval');
        if(empty($rec_id)) $this->toJson('订单商品明细表rec_id不能为空.');
         
        $order_detail = $this->_get_order_detail($user_id,$rec_id);
        
        //检测订单状态是否允许修改
        if(!in_array($order_detail['refund_status'],array('11','40','50'))){
            $this->toJson('当前状态不允许修改退款信息.');
            return;
        }
        
        $order_detail['refund_totle'] = round(($order_detail['totle_fee'] - $order_detail['shipping_fee']) / $order_detail['goods_amount'] * $order_detail['goods_totle'],2);
 
            $order_goods_data = array(
                'rec_id' => $rec_id,
                'refund_type' => I('refund_type',0,'intval'),
                'refund_status' => 11,
                'refund_price' => I('refund_price',0,'doubleval'),
                'refund_num' => I('refund_num',0,'intval'),
                'refund_time' => gmtime(),
                'refund_reason' => trim(I('refund_reason')),
                'refund_note' => trim(I('refund_note'))
            );
            //退款金额是否符合限制
            if($order_goods_data['refund_price'] <= 0 or $order_goods_data['refund_price'] > $order_detail['refund_totle']){
                $this->toJson(sprintf('退款金额只能在 0 与 %s 之间.',$order_detail['refund_totle']));
                return;   
            } 
            //退款数量是否符合限制
            if($order_goods_data['refund_num'] <0 or $order_goods_data['refund_num'] > $order_detail['quantity']){
                $this->toJson(sprintf('退款数量只能在 0 and %s 之间.',$order_detail['quantity']));
                return;
            }
            
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($order_goods_data)){
                D('')->rollback();
                $this->toJson($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            //判断订单的单头的退款状态
            if(!$order_detail['o_refund_status']){ //更改订单单头的退款单状态
                $order_data = array(
                    'order_id' => $order_detail['order_id'],
                    'refund_status' => 1
                );
                if(!$this->_order_mod->create($order_data)){
                    D('')->rollback();
                    $this->toJson($this->_order_mod->getError());
                    return;
                }
                $this->_order_mod->save();
                $log_data = array(
                    'log_user' => 'buyer|'.$user_name,
                    'order_id' => $order_detail['order_id'],
                    'from_status' => $order_detail['order_status'],
                    'to_status' => $order_detail['order_status'],
                    'from_refund_statu' => $order_detail['refund_status'],
                    'to_refund_status' => $order_data['refund_status'],
                    'note' => '用户修改了退款信息.',
                    'log_time' => gmtime()
                );
                if(!$this->_order_log_mod->add($log_data)){
                    D('')->rollback();
                    $this->toJson('保存订单操作失败.');
                    return;
                }
            }
            
            //保存退款凭证
            //上传凭证
            $refund_img = '';
            if($_FILES['refund_img']['size'] > 0){
                $upload = new \Think\Upload(array( //图片上传设置
                    'maxSize' => 5*1024*1024, //最大支持上传5M的图片
                    'exts' => 'gif,jpg,jpeg,png,bmp',  //图片支持类型
                    'savePath' => 'refund/'
                ));
                if(!$file = $upload->upload($_FILES)){
                    D('')->rollback();
                    $this->toJson($upload->getError());
                    return;
                }
                $refund_img = $upload->__get('rootPath').$file['refund_img']['savepath'].$file['refund_img']['savename'];
            }
                            
            //保存退款协商记录
            $refund = array(
                'refund_sn' => $order_detail['refund_sn'],
                'refund_user' => 'buyer|'.$user_name,
                'refund_status' => $order_goods_data['refund_status'],
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_type' => $order_goods_data['refund_type'],
                    'refund_price' => $order_goods_data['refund_price'],
                    'refund_reason' => $order_goods_data['refund_reason'],
                    'refund_note' => $order_goods_data['refund_note'],
                    //'refund_img' => C('site_url').$refund_img,
                ))
            );
            if($refund_img){
            	$refund['refund_data']['refund_img']=C('site_url').$refund_img;
            }
            if(!$refund_mod->create($refund)){
                @unlink($refund_img);
                D('')->rollback();
                $this->toJson($refund_mod->getError());
                return;
            }
            $refund_mod->add();
            D('')->commit();
            $this->toJson('退款信息修改成功.',1,$refund);
 
    }
    
    /** 买家退回货物 */
    function refund_send(){
    	$refund_mod=D('Admin/Refund');
        $user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	} 
    	$user_name=getNameById('user_name', 'user', 'user_id', $user_id);
    	
        $rec_id = I('rec_id','','intval');
        if(empty($rec_id)) $this->toJson('订单商品明细表rec_id不能为空.');
         
        $order_detail = $this->_get_order_detail($user_id,$rec_id);
        
        $refund_shipping_name = trim(I('refund_shipping_name'));
        if(empty($refund_shipping_name)) $this->toJson('物流名称 refund_shipping_name 不能为空.');
        $refund_invoice_no = trim(I('refund_invoice_no'));
        if(empty($refund_invoice_no)) $this->toJson('物流单号 refund_invoice_no 不能为空.');
 
            $order_goods_data = array(
                'rec_id' => $rec_id,
                'refund_status' => 22,
                'refund_shipping_name' => $refund_shipping_name,
                'refund_invoice_no' => $refund_invoice_no
            );
            D('')->startTrans();
            if(!$this->_order_goods_mod->create($order_goods_data)){
                D('')->rollback();
                $this->toJson($this->_order_goods_mod->getError());
                return;
            }
            $this->_order_goods_mod->save();
            
            //记录退款协商记录
            $refund = array(
                'refund_sn' => $order_detail['refund_sn'],
                'refund_user' => 'buyer|'.$user_name,
                'refund_status' => $order_goods_data['refund_status'],
                'refund_time' => gmtime(),
                'refund_data' => serialize(array(
                    'refund_shipping_name' => $order_goods_data['refund_shipping_name'],
                    'refund_invoice_no' => $order_goods_data['refund_invoice_no'],
                ))
            );
            if(!$refund_mod->create($refund)){
                D('')->rollback();
                $this->toJson($refund_mod->getError());
                return;
            }
            $refund_mod->add();
            D('')->commit();
            $this->toJson('退货提交成功.',1,$refund);
 
    }
    

     
    /** 取消退款 */
    function cancel_refund(){
    	$refund_mod=D('Admin/Refund');
        $user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
    	
        $rec_id = I('rec_id','','intval');
        if(empty($rec_id)) $this->toJson('订单商品明细表rec_id不能为空.');
        $order_detail = $this->_get_order_detail($user_id,$rec_id);
        //检测订单状态
        if($order_detail['refund_status'] !== '11'){
            $this->error('退款状态正在处理中，不能取消.');
            return;
        }
        
        //取消退款
        $order_goods_data = array(
            'rec_id' => $rec_id,
            'refund_status' => 40,
            'refund_time' => gmtime()
        );
        D('')->startTrans();
        if(!$this->_order_goods_mod->create($order_goods_data)){
            $this->toJson($this->_order_goods_mod->getError());
            return;
        }
        $this->_order_goods_mod->save();
        
        //检测订单下是否还有退款中的明细
        $where['order_id'] = $order_detail['order_id'];
        $where['refund_status'] = array('not in',array(0,40));
        $where['rec_id'] = array('neq',$rec_id);
        $other_order_goods = $this->_order_goods_mod->where($where)->select();
        if(!$other_order_goods){
            //修改单头状态
            $order_data = array(
                'order_id' => $order_detail['order_id'],
                'refund_status' => 0
            );
            if(!$this->_order_mod->create($order_data)){
                D('')->rollback();
                $this->toJson($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'buyer|'.$user_id,
                'order_id' => $order_detail['order_id'],
                'from_status' => $order_detail['order_status'],
                'to_status' => $order_detail['order_status'],
                'from_refund_statu' => $order_detail['refund_status'],
                'to_refund_status' => $order_data['refund_status'],
                'note' => '用户取消退款',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->toJson('保存订单日志失败.');
                return;
            }
        }
        
        //记录退款明细
        $refund = array(
            'refund_sn' => $order_detail['refund_sn'],
            'refund_user' => 'buyer|'.$user_id,
            'refund_status' => $order_goods_data['refund_status'],
            'refund_time' => gmtime(),
            'refund_data' => serialize(array(
                'refund_note' => '用户取消退款申请.'
            ))
        );
        if(!$refund_mod->create($refund)){
            D('')->rollback();
            $this->toJson($refund_mod->getError());
            return;
        }
        $refund_mod->add();
        D('')->commit();
        $this->toJson('退款取消成功.',1);
    }
    
	/** 查看退款明细 */
    function view_refund(){
    	$refund_mod=D('Admin/Refund');
        $user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
        $rec_id = I('rec_id','','intval');
        if(empty($rec_id)) {
        	 $this->toJson('订单商品明细表rec_id不能为空.'); 
        }
        $order = $this->_get_order_detail($user_id,$rec_id); 
        $order['refund_totle'] = round(($order['totle_fee'] - $order['shipping_fee']) / $order['goods_amount'] * $order['goods_totle'],2);
        $this->toJson('查看退款明细成功',1,$refund_mod->_get_refunds($order['refund_sn']));  
    }
    
    
   protected function _get_order_detail($user_id,$rec_id){ 
        $where['og.rec_id'] = $rec_id;
        $where['o.user_id'] = $user_id;
        $order = $this->_order_goods_mod->field('og.*,o.order_sn,o.user_id,o.pay_time,o.goods_amount,o.shipping_fee,o.integral_fee,o.discount_fee,o.refund_fee,o.totle_fee,o.add_time,o.refund_status as o_refund_status')
                 ->join(' as og LEFT JOIN __ORDER__ as o ON og.order_id=o.order_id')
                 ->where($where)->find();  
        if(!$order){
            $this->toJson('Order does not exist, or has been deleted.');
            return;
        }
        return $order;
    }
    
    
	/** 商品评价 
	 * /mygoods/comment_add?urlencode=0&user_id=1&token=oLZsBVztzBgrdHotdufnagQrQzUsbZhy&goods_id=&rec_id=&comment_stars=&content=
	 * */
    function comment_add(){
    	$comment_mod=D('Comments');
        $user_id=I('user_id',0,'intval'); 
    	if(empty($user_id)) $this->toJson('用户Id不能为空.'); 
        $token=I('token','','trim');   
        if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}
        $rec_id = I('rec_id','','intval');
        if(empty($rec_id)) $this->toJson('订单明细表主键rec_id不能为空.');
        $goods_id = I('goods_id','','intval'); 
        if(empty($goods_id)) $this->toJson('订单明细表goods_id不能为空.');
        $comment_stars=I('comment_stars','5','intval');
        $content=I('content','','trim');
        if(empty($content)) $this->toJson('评价内容不能为空.');
        
        
        $find=$comment_mod->where(array('user_id'=>$user_id,'rec_id'=>$rec_id,'goods_id'=>$goods_id))->find();
        if($find){
        	$this->toJson('你已经评价过该商品，请不要重复提交.');
        }
        
        //查看用户对于的订单记录
        $where['og.rec_id'] = $rec_id;
        $where['o.user_id'] = $user_id;
        $order = $this->_order_goods_mod->field('og.*,o.order_sn,o.user_id,o.pay_time,o.goods_amount,o.shipping_fee,o.integral_fee,o.discount_fee,o.refund_fee,o.totle_fee,o.add_time,o.refund_status as o_refund_status')
                 ->join(' as og LEFT JOIN __ORDER__ as o ON og.order_id=o.order_id')
                 ->where($where)->find();         
        if(!$order){
            $this->toJson('订单不存在或者你无权评价该订单的商品.');
            return;
        }
        if($order['order_status']!=40 || $order['refund_status']!=0){
        	$this->toJson('未完成订单或者退款订单无权评价.');
        }
        
        $user=M('user')->where(array('user_id'=>$user_id))->field('user_name,email')->find();
        if(empty($user)) $this->toJson('用户不存在.');
        
        $data=array(
        	'user_id'=>$user_id,
        	'goods_id'=>$goods_id,
        	'rec_id'=>$rec_id,
        	'comment_stars'=>$comment_stars,
        	'content'=>$content, 
        	'ip'=>$_SERVER['REMOTE_ADDR'],
        	'comment_time'=>gmtime(),
        	'status'=>1,
        	'user_name'=>$user['user_name'],
        	'email'=>$user['email']
        );
        $user=M('user')->where(array('user_id'=>$user_id))->field('user_name,email')->find();
		   
        $res=$comment_mod->add($data);
        if($res){
        	$data['comment_id']=$res;
        	$this->toJson('商品评价成功',1,$data);  
        }else{
        	$this->toJson('评价失败.');
        }
    } 
    
    
/** 商品评价列表
	 * /mygoods/comment_list?goods_id=
	 * */
    function comment_list(){
    	$comment_mod=D('Comments'); 
        $goods_id = I('goods_id','','intval'); 
        if(empty($goods_id)) $this->toJson('订单明细表goods_id不能为空.');
        
		$pageindex=I('pageindex',1,'intval');
    	$pagesize=I('pagesize','','intval');  
    	
    	if(empty($pagesize)){
    		$pagesize=10;
    	} 
    	
        $count = $this->_order_mod->where($map)->count();  
        $PageCount=ceil($count/$pagesize);
        C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
        //$page = new \Think\Page($count,$pagesize);  
        
        
        $list=$comment_mod->where(array('goods_id'=>$goods_id,'status'=>1))
        	->page($pageindex,$pagesize)
        	->order('comment_id desc')
        	->select(); 
         
        
        $this->toJson('获取商品评价列表成功',1,$list);  
        
    } 
    
    
       
   
   /***************************  支付宝支付通知 *********************************/
   function alipay_gateway(){
   	 
   }   
   /**  支付宝支付通知 */
   function alipay_notify_url(){
   		/*
		array(15) {
		  ["total_amount"] => string(4) "1.00"
		  ["buyer_id"] => string(16) "2088102564535894"
		  ["trade_no"] => string(28) "2016092321001004890223379040"
		  ["notify_time"] => string(19) "2016-09-23 21:21:17"
		  ["subject"] => string(15) "孕妈购订单"
		  ["sign_type"] => string(3) "RSA"
		  ["notify_type"] => string(17) "trade_status_sync"
		  ["out_trade_no"] => string(10) "1626614517"
		  ["trade_status"] => string(13) "TRADE_SUCCESS"
		  ["gmt_payment"] => string(19) "2016-09-23 21:21:17"
		  ["sign"] => string(172) "oad1oOjpxMd4ss6I9M5glryh8D8to/0k2GaVP1SvHGuPyRiB8I4qfUnlBW4Yp+q6eSmimO22O/HlLe8pVdC3LoILeW9lXAW6mOSq23hVhdYf+eNBjILwAZRUeXlUD3r5M471xTOTI+FffMviaKj02+SgNbB1DVs4V6h45+RV5m8="
		  ["gmt_create"] => string(19) "2016-09-23 21:21:16"
		  ["app_id"] => string(16) "2016091401906517"
		  ["seller_id"] => string(16) "2088801161162954"
		  ["notify_id"] => string(34) "6cb3424b8494be2b02ccebc2070a7e4mva"
		} 
		 $postStr="total_amount=1.00&buyer_id=2088102564535894&trade_no=2016092321001004890223379040&notify_time=2016-09-23+21%3A21%3A17&subject=%E5%AD%95%E5%A6%88%E8%B4%AD%E8%AE%A2%E5%8D%95&sign_type=RSA&notify_type=trade_status_sync&out_trade_no=1626614517&trade_status=TRADE_SUCCESS&gmt_payment=2016-09-23+21%3A21%3A17&sign=oad1oOjpxMd4ss6I9M5glryh8D8to%2F0k2GaVP1SvHGuPyRiB8I4qfUnlBW4Yp%2Bq6eSmimO22O%2FHlLe8pVdC3LoILeW9lXAW6mOSq23hVhdYf%2BeNBjILwAZRUeXlUD3r5M471xTOTI%2BFffMviaKj02%2BSgNbB1DVs4V6h45%2BRV5m8%3D&gmt_create=2016-09-23+21%3A21%3A16&app_id=2016091401906517&seller_id=2088801161162954&notify_id=6cb3424b8494be2b02ccebc2070a7e4mva";
 		*/
   	 	//接收支付宝支付通知信封 REMOTE_ADDR IP:110.75.152.3
	    $postStr=file_get_contents('php://input'); 
	    if(empty($postStr)) $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; 
	    if(empty($postStr)) $postStr = ''; 
	    
	    $trade_status=I('trade_status'); 
	    
	    //日志文件
	    $logfile='./Shop/Paylog/alipayNotify'.date('Ymd').'.log';
	    error_log(date('Y-m-d H:i:s').PHP_EOL.$_SERVER['REMOTE_ADDR'].PHP_EOL.'-----------------------------'.PHP_EOL.$postStr.PHP_EOL.PHP_EOL, 3, $logfile);
	    
	    //把请求字符串解析成数组
	    parse_str($postStr,$arr); 
	    #dump($arr);
	    
	    /**
	     * 验证签名是否成功,待续...
	    **/
	    
	    if($trade_status=='TRADE_SUCCESS'){
		      //订单处理开始
			  $order=$this->_order_mod->where(array('order_sn'=>$arr['out_trade_no']))->find();
			  //判断订单状态是否是待支付状态 ,如果不是,拒绝继续执行业务逻辑
			  if($order['order_status']!='11'){ error_log(PHP_EOL.'order_status error.',3,$logfile); }
			  D()->startTrans();	
		      $data=array(
		      	'order_status'=>20,
		      	'pay_code'=>'Alipay',
		      	'pay_name'=>'支付宝',
		      	'pay_time'=>gmtime(),
		      	'pay_info'=>serialize($arr)
		      );
		      $map=array(
		      	'order_sn'=>$arr['out_trade_no'],
		      );
		      
		     $list=$this->_order_mod->where($map)->save($data);
		      
				
		        //记录订单操作日志
	            $log_data = array(
	                'log_user' => 'system|system',
	                'order_id' => $order['order_id'],
	                'from_status' => $order['order_status'],
	                'to_status' => $data['order_status'],
	                'note' => '支付宝通知支付成功. '.$_SERVER['REMOTE_ADDR'],
	                'log_time' => gmtime()
	            );
	            if(!D('order_log')->add($log_data)){
	                D('')->rollback();
	                $this->error('订单操作日志保存失败');
	                return;
	            }
		      D()->commit();
		      //订单处理结束打印 success标志
			  echo 'success';
		      error_log(PHP_EOL.'Paid success.',3, $logfile);
	    } 
	    //$where=array('order_sn'=>$arr['out_trade_no'],'order_status'=>11);
	    //$data=array();
	    //$this->_order_mod->where($where)->save($data);
   }
   
 
   
  /***************************  支付宝App支付请求参数 ********************************
   * APP接口不支持CURL请求，只允许在SDK中发起支付请求
   * 支付宝网关 curl_post('https://openapi.alipay.com/gateway.do',$curl_param);
   */
   function alipay_order_sign(){ 	 
   		$order_sn = I('order_sn','','trim');
   		if(empty($order_sn)) $this->toJson('订单号order_sn不能为空.');
   		$where=array('order_sn'=>$order_sn,'is_delete'=>0);
   		$order=$this->_order_mod->where($where)->find();
   		if(!$order){
   			$this->toJson('订单号不存在.');
   		}
		$bizdata=array(
			'subject'=>'孕妈购订单',
			//'out_trade_no'=>uniqid(),
			'out_trade_no'=>$order_sn,
			'total_amount'=>$order['totle_fee'],
			'product_code'=>'QUICK_MSECURITY_PAY'
		); 
		//json_encode后保持中文编码
		$json=my_json_encode($bizdata); 
		$data=array(
			'app_id'=>'2016091401906517',
			'method'=>'alipay.trade.app.pay',
			'charset'=>'utf-8',
			'sign_type'=>'RSA',
			'timestamp'=>date('Y-m-d H:i:s',time()),
			//'timestamp' => '2016-09-17 22:06:58',
			'version'=>'1.0',
			'notify_url'=>'https://www.ymg280.com/mygoods/alipay_notify_url',
			'biz_content'=>$json
		);  
		ksort($data);  
		
		//把参数组合成请求字符串
		$param='';
		foreach ($data as $key => $val){
			$param.='&'.$key."=".$val;
		}
		$param=trim($param,'&'); 
		
		//获取私钥KEY
		$private_key = file_get_contents('./alipaysdk/rsa_private_key.pem');
		 
		$pkeyid = openssl_pkey_get_private($private_key);  
		
		//dump($pkeyid);
		
		$wait_sign_data = $param;  
		$res=openssl_sign($wait_sign_data,$signedMsg,$pkeyid,OPENSSL_ALGO_SHA1); 
    	if($res) { 
    		//对签名进行base64_encode编码
    		$signedMsg=base64_encode($signedMsg); 
    		if($result=$this->abVerify($wait_sign_data, $signedMsg)){
	    		$return_data['param']=$wait_sign_data;
	    		$return_data['sign']=$signedMsg;
	    		$this->toJson('签名验证成功,App支付请求成功.',1,$return_data);    			
    		}else{
    			$this->toJson('签名验证不通过,请检查 rsa_private_key.pem 和 rsa_public_key.pem 是否匹配.');
    		}              
         }else{
         	$this->toJson('sign failed.');
         } 
   }
   
  /***************************  支付宝App支付请求参数 ********************************
   * APP接口不支持CURL请求，只允许在SDK中发起支付请求
   * 支付宝网关 curl_post('https://openapi.alipay.com/gateway.do',$curl_param);
   */
   function alipay_order_sign2(){ 	 
   		$order_sn = I('order_sn','','trim');
   		if(empty($order_sn)) $this->toJson('订单号order_sn不能为空.');
   		$where=array('order_sn'=>$order_sn,'is_delete'=>0);
   		$order=$this->_order_mod->where($where)->find();
   		if(!$order){
   			$this->toJson('订单号不存在.');
   		}
		$bizdata=array(
			'subject'=>'孕妈购订单',
			//'out_trade_no'=>uniqid(),
			'out_trade_no'=>$order_sn,
			'total_amount'=>$order['totle_fee'],
			'product_code'=>'QUICK_MSECURITY_PAY'
		); 
		//json_encode后保持中文编码
		$json=my_json_encode($bizdata); 
		$data=array(
			'app_id'=>'2016091401906517',
			'method'=>'alipay.trade.app.pay',
			'charset'=>'utf-8',
			'sign_type'=>'RSA',
			'timestamp'=>date('Y-m-d H:i:s',time()),
			//'timestamp' => '2016-09-17 22:06:58',
			'version'=>'1.0',
			'notify_url'=>'https://www.ymg280.com/mygoods/alipay_notify_url',
			'biz_content'=>$json
		);  
		ksort($data); 
		//dump($data);   
		
		//把参数组合成请求字符串
		$param='';
		foreach ($data as $key => $val){
			$param.='&'.$key."=".$val;
		}
		$param=trim($param,'&');
		#echo(htmlspecialchars($param));
		
		//获取私钥KEY
		$private_key = file_get_contents('./alipaysdk/rsa_private_key.pem');
		 
		$pkeyid = openssl_pkey_get_private($private_key);  
		
		//dump($pkeyid);
		
		$wait_sign_data = $param;  
		//encode $param
		$param='';
		foreach ($data as $key => $val){
			$param.='&'.$key."=".rawurlencode($val);
		}		
		$param=trim($param,'&');
		$res=openssl_sign($wait_sign_data,$signedMsg,$pkeyid,OPENSSL_ALGO_SHA1); 
    	if($res) { 
    		//对签名进行base64_encode编码
    		$signedMsg=base64_encode($signedMsg); 
    		if($result=$this->abVerify($wait_sign_data, $signedMsg)){
	    		$return_data['param']=$param;
	    		$return_data['sign']=rawurlencode($signedMsg);
	    		$this->toJson('签名验证成功,App支付请求成功.',1,$return_data);    			
    		}else{
    			$this->toJson('签名验证不通过,请检查 rsa_private_key.pem 和 rsa_public_key.pem 是否匹配.');
    		}              
         }else{
         	$this->toJson('sign failed.');
         } 
   }
   
    //签名测试
   function test2(){
   		$signature=$this->abSign('abc2');	
   		dump($signature);
   		$result=$this->abVerify('abc2', $signature);
   		dump($result);
   }
   
	//sha1withRSA 生成签名：
	public function abSign($data){
		$key = openssl_pkey_get_private(file_get_contents("./alipaysdk/rsa_private_key.pem"));  
		openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA1);
		$sign = base64_encode($sign);
		return $sign;
	}
	
	//sha1withRSA 验证签名：
	public function abVerify($data, $sign){
		$sign = base64_decode($sign);
		$key = openssl_pkey_get_public(file_get_contents("./alipaysdk/rsa_public_key.pem")); 
		$result = openssl_verify($data, $sign, $key, OPENSSL_ALGO_SHA1) === 1;
		return $result;
	}
    
    
    
}





?>