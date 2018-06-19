<?php
/**
 * APP Json V2控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Page;

use Think\Controller;
class Appjsonv2Controller extends FrontendController{ 
	var $_json=null;
    function __construct(){
        parent::__construct();
        $this->Appjsonv2Controller();
    }
    
    function Appjsonv2Controller(){ 
        
        //APIv2流量统计
        $this->apiv2_traffic();
        
        //if(!$res){
       	//	error_log(PHP_EOL.time().PHP_EOL.$res.M()->getLastsql().PHP_EOL, 3, "./API_REQUEST.log");
        //} 
    }
    
    /** 广告列表  
     * 	返回所有数据
     * */
    function ad(){ 		
        //打包多个广告位,传入pids用逗号“,”分隔. 如pids=1,62
        $pids = I('pids','','trim'); 
        if(empty($pids)){
        	$this->toJson('广告位pids,不能为空(多个用逗号","隔开).');
        }
    	$where=array(
    		'status'=>1, 
    		'pid'=> array('in',trim($_REQUEST['pids'],','))
    	);  
    	
    	$pageindex=I('pageindex','','intval');
    	if(empty($pageindex)) $pageindex=1;  
    	$pagesize=I('pagesize',10,'intval');
    	if($pagesize>100) $pagesize=100;
    	$ad_mod = M('ad');
    	$TotalRecords = $ad_mod->where($where)->count();   
    	$PageCount=ceil($TotalRecords/$pagesize); 
        
        $ads = $ad_mod->field('ad_id,pid,title,clicks,img,type,referId') 
        	->where($where)
        	->order('sort_order asc,ad_id DESC')
        	->page($pageindex.','.$pagesize)
        	->select(); 
        
        foreach ($ads as $key => $val){
            if(empty($val['referId'])){
                $ads[$key]['referId'] = $val['referid'];
            }
        }
        
        $data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$ads);
        $this->toJson('请求成功.',1,$data);        	
    }   

    
    
    /** 广告列表  
     * 	返回所有数据
     * */
    function ad2(){ 		
        //打包多个广告位,传入pids用逗号“,”分隔. 如pids=1,62
        $toppid = I('toppid','','trim'); 
        $middlepid = I('middlepid','','trim'); 
        $scrollpid = I('scrollpid','','trim'); 
        $bottompid = I('bottompid','','trim'); 
        if(empty($toppid)&&empty($middlepid)&&empty($scrollpid)&&empty($bottompid)){
        	$this->toJson('广告位toppid,middlepid,scrollpid,bottompid至少一个不能为空.');
        } 
        $ad_mod = M('ad');
        if($toppid){         	
	    	$where=array(
	    		'status'=>1, 
	    		'pid'=> $toppid
	    	);   
	    	$pageindex=I('pageindex','','intval');
	    	if(empty($pageindex)) $pageindex=1;  
	    	$pagesize=I('pagesize',10,'intval');
	    	
	    	$TotalRecords = $ad_mod->where($where)->count();   
	    	$PageCount=ceil($TotalRecords/$pagesize); 
	        
	        $ads['toppid'] = $ad_mod->field('ad_id,pid,title,clicks,img,type,referId') 
	        	->where($where)
	        	->order('sort_order asc,ad_id DESC')
	        	->page($pageindex.','.$pagesize)
	        	->select();    
	        foreach ($ads['toppid'] as $key => $val){
	            if(empty($val['referId'])){
	               $ads['toppid'][$key]['referId'] = $val['referid'];
	            }
	        } 
        } 
        if($middlepid){         	
	    	$where=array(
	    		'status'=>1, 
	    		'pid'=> $middlepid
	    	);   
	    	$pageindex=I('pageindex','','intval');
	    	if(empty($pageindex)) $pageindex=1;  
	    	$pagesize=I('pagesize',10,'intval');
	    	
	    	$TotalRecords = $ad_mod->where($where)->count();   
	    	$PageCount=ceil($TotalRecords/$pagesize); 
	        
	        $ads['middlepid'] = $ad_mod->field('ad_id,pid,title,clicks,img,type,referId') 
	        	->where($where)
	        	->order('sort_order asc,ad_id DESC')
	        	->page($pageindex.','.$pagesize)
	        	->select();    
	        foreach ($ads['middlepid'] as $key => $val){
	            if(empty($val['referId'])){
	                $ads['middlepid'][$key]['referId'] = $val['referid'];
	            }
	        }
        }  
        if($scrollpid){         	
	    	$where=array(
	    		'status'=>1, 
	    		'pid'=> $scrollpid
	    	);   
	    	$pageindex=I('pageindex','','intval');
	    	if(empty($pageindex)) $pageindex=1;  
	    	$pagesize=I('pagesize',10,'intval');
	    	
	    	$TotalRecords = $ad_mod->where($where)->count();   
	    	$PageCount=ceil($TotalRecords/$pagesize); 
	        
	        $ads['scrollpid'] = $ad_mod->field('ad_id,pid,title,clicks,img,type,referId') 
	        	->where($where)
	        	->order('sort_order asc,ad_id DESC')
	        	->page($pageindex.','.$pagesize)
	        	->select();   
            foreach ($ads['scrollpid'] as $key => $val){
	            if(empty($val['referId'])){
	                $ads['scrollpid'][$key]['referId'] = $val['referid'];
	            }
	        }
        } 
        if($bottompid){         	
	    	$where=array(
	    		'status'=>1, 
	    		'pid'=> $bottompid
	    	);   
	    	$pageindex=I('pageindex','','intval');
	    	if(empty($pageindex)) $pageindex=1;  
	    	$pagesize=I('pagesize',10,'intval');
	    	
	    	$TotalRecords = $ad_mod->where($where)->count();   
	    	$PageCount=ceil($TotalRecords/$pagesize); 
	        
	        $ads['bottompid'] = $ad_mod->field('ad_id,pid,title,clicks,img,type,referId') 
	        	->where($where)
	        	->order('sort_order asc,ad_id DESC')
	        	->page($pageindex.','.$pagesize)
	        	->select();    
	        foreach ($ads['bottompid'] as $key => $val){
	            if(empty($val['referId'])){
	                $ads['bottompid'][$key]['referId'] = $val['referid'];
	            }
	        }
        } 
        
	    $data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'topdata'=>$ads['toppid'],'middledata'=>$ads['middlepid'],'scrolldata'=>$ads['scrollpid'],'bottomdata'=>$ads['bottompid']);      
        
        $this->toJson('请求成功.',1,$data);        	
    }    
    
    
    /** 文章类别  */
    function acategory(){       
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
    	   	
    	//type:0-文章，1-商品， 2-品牌，3-广告
    	$type=I('type',0,'trim');
    	if($type==2){
    		$where['type']=$type;
    	}else{
    		$where['type']=array('in','0,1,3');
    	}
    	
    	
    	$pageindex=I('pageindex','','intval');
    	if(empty($pageindex)) $pageindex=1;  
    	$pagesize=I('pagesize',10,'intval');
    	
    	$TotalRecords = M('acategory')->where($where)->count();   
    	$PageCount=ceil($TotalRecords/$pagesize); 
    	
    	$list=M('acategory')->alias('acate')->where($where)
    		->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc,(SELECT count(*) from '.C('DB_PREFIX').'article where cate_id = acate.cate_id) as article_num,gestation_ids')
    		->order('sort_order desc')
    		->page($pageindex.','.$pagesize)
    		->select();  
    		 

    	foreach ($list as $key  => $val){
    		if($val['article_num']=='1'){
    			$article_id = M('article')->where(array('cate_id'=>$val['cate_id'],'if_show'=>1))->getField('article_id'); 
    			$list[$key]['article_id'] = $article_id;
    		}else{
    			$list[$key]['article_id'] = ''; 
    		}
    		$list[$key]['gestation_name'] = idsToGestation($val['gestation_ids']); 
    	} 
    	$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    } 
    
    /** 文章\品牌类别  */
    function getCate(){      
    	$cate_ids=I('cate_ids',0,'trim');
    	if(empty($cate_ids)){
    		$this->toJson('cate_ids不能为空.');
    	} 
    	$where=array(
    		'if_show'=>1,
    		'cate_id'=>array('in',trim($cate_ids,','))
    	);
    	
    	$pageindex=I('pageindex','','intval');
    	if(empty($pageindex)) $pageindex=1;  
    	$pagesize=I('pagesize',10,'intval');
    	$acate_mod=M('acategory');
    	$TotalRecords = $acate_mod->where($where)->count();   
    	$PageCount=ceil($TotalRecords/$pagesize); 
    	
    	$list=$acate_mod->alias('acate')->where($where)
    		->Field('cate_id,cate_name,photo,b_photo,parent_id,type,cate_desc,(SELECT count(*) from '.C('DB_PREFIX').'article where cate_id = acate.cate_id) as article_num,gestation_ids')
    		->order('sort_order desc')
    		->page($pageindex.','.$pagesize)
    		->select();   
    	foreach ($list as $key => $val){
    	    if($val['article_num']=='1'){
    	        $article_id = M('article')->where(array('cate_id'=>$val['cate_id'],'if_show'=>1))->getField('article_id');
    	        $list[$key]['article_id'] = $article_id;
    	    }else{
    	        $list[$key]['article_id'] = '';
    	    }
    		$list[$key]['gestation_name'] = idsToGestation($val['gestation_ids']);
    	}
    	$count=count($list); 
    	$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    } 
    
    
    /** 根据小类cate_id获取文章列表  */
    function article(){     
    	$where =array(
    		'if_show'=>1, 
    	);   
    	if($cate_id = I('cate_id',0,'intval')){
			$where['cate_id']=$cate_id; 
			$cate=M('acategory')->field('cate_name,photo,b_photo,cate_desc')->where(array('cate_id'=>$cate_id,'if_show'=>1))->find();
		}    
    	if($article_id = I('article_id',0,'intval')){
		#	$where['article_id']=$article_id; 
		}  
		if($article_ids = I('article_ids',0,'trim')){ 
		#	$where['article_id']=array('in',trim($article_ids,',')); 
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
    	if($pagesize>100) $pagesize=100;
    	$article_mod = M('article');
        $count = $article_mod->where($where)->count();  
        $PageCount=ceil($count/$pagesize); 
    	$list=$article_mod->where($where)
    		->Field('article_id,cate_id,title,photo0,photo,cutline,collect_num,view_num,ctime,is_top,is_index')
			->order('sort_order desc,ctime desc') 
			->page($pageindex.','.$pagesize)
    		->select();
    		    
    	//传入用户user_id时，检测是否已经收藏
		$list = $this->check_collect($list, 'article_id', 'article');
		
    	$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$count,'cate_id'=>$cate_id,'cate_name'=>$cate['cate_name'],'cate_photo'=>$cate['photo'],'cate_b_photo'=>$cate['b_photo'],'cate_desc'=>$cate['cate_desc'],'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    }   
    
    /** 文章集合  */
    function getArticle(){   
    	$article_ids = I('article_ids',0,'trim');
    	if(empty($article_ids)){
    		$this->toJson('article_ids不能为空.');
    	} 
    	$where =array(
    		'if_show'=>1, 
    		'article_id'=>array('in',trim($article_ids,','))
    	);     
    	$pageindex=I('pageindex',1,'intval');
    	$pagesize=I('pagesize','','intval'); 
    	
		//列表展示数量
    	if(empty($pagesize)){
    		$pagesize=10;
    	} 
    	if($pagesize>100) $pagesize=100;
    	$article_mod = M('article');
        $count = $article_mod->where($where)->count();  
        $PageCount=ceil($count/$pagesize); 
    	$list=$article_mod->where($where)
    		->Field('article_id,cate_id,title,photo0,photo,cutline,collect_num,view_num,ctime,is_top,is_index')
			->order('sort_order desc,ctime desc') 
			->page($pageindex.','.$pagesize)
    		->select();
    	$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$count,'data'=>$list);
    	$this->toJson('请求成功.',1,$data);
    } 
    
    
    /** 获取商品列表 (文章详情一起) */
    function getGoods($debug=FALSE){ 
    	$where=array(
    		'is_on_sale'=>1
    	); 
    	$pageindex=I('pageindex',0,'intval');
    	if(empty($pageindex)) $pageindex=1; 

    	$article_mod = M('article');
  	
		//文章
		$article_id=I('article_id',0); 
		
    	if(empty($article_id)){ 
    			$this->toJson('aritcle_id不能为空.'); 
    	}
    	
			$where['ag.article_id']=$article_id; 
			$article=$article_mod->field('title,cutline,photo,cate_id,content')->where(array('article_id'=>$article_id,'if_show'=>1))->find(); 
			if(empty($article)) $this->toJson('aritcle_id not exists.');
			//传入用户id时，检测是否已经收藏
	    	$user_id=I('user_id',0,'intval');
	    	if(!empty($user_id)){
	    		$collect_mod = M('collect'); 
    			if($find=$collect_mod->where(array('type'=>'article','user_id'=>$user_id,'key_id'=>$article_id))->find()){
    				$article['is_collected']=1;
    				$article['cid'] = $find['cid'];
    			}else{
    				$article['is_collected']=0;
    				$article['cid']=null;
    			}   
	    	}  
		 
    	$model=M('goods'); 
    	
    	$TotalRecords=$model->join(' as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
	    	->Field('g.goods_id')
	    	->count();
			
    	$listrows=I('pagesize',10,'intval');
    	if($listrows>100) $listrows=100;
    	$PageCount=ceil($TotalRecords/$listrows);
    	$list=$model->join(' as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
    		->page($pageindex.','.$listrows)
	    	->Field('g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale')
	    	->order('ag.orderNum asc,ag.goods_id desc') 
	    	->select(); 

	    //传入用户user_id时，检测是否已经收藏
		$list = $this->check_collect($list, 'goods_id', 'goods');
		
	    $listsql=$model->getLastsql();		   	
    	$article_goods_mod=M('article_goods');
		foreach($list as $key => $val){
			$list[$key]['goods_desc']=strip_tags(htmlspecialchars_decode($val['goods_desc']));
			//$list[$key]['article_id']=$article_goods_mod->where('goods_id='.$val['goods_id'])->limit('1')->getField('article_id'); 
		}
		$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'article_id'=>$article_id,'article_title'=>$article['title'],'article_photo'=>$article['photo'],
			'article_cutline'=>$article['cutline'],
			'article_content'=>$article['content'],
			'cate_id'=>$article['cate_id'],
			'is_collected'=>$article['is_collected'],
			'cid'=>$article['cid'],
			'Goods'=>$list);
    	if($debug){
    		if(isset($_REQUEST['show_sql'])) echo $listsql;
    		return $data;
    	}else{
    		$this->toJson('获取商品成功.',1,$data);
    	}
    }
    
    /** 获取商品列表 */
    function getGoodsHtml($debug=FALSE){ 
    	$where=array(
    		'is_on_sale'=>1
    	); 
    	$pageindex=I('pageindex',0,'intval');
    	if(empty($pageindex)) $pageindex=1; 
		$article_mod = M('article'); 
  	
		//文章
		$article_id=I('article_id',0); 
    	if(!empty($article_id)){ 
			$where['ag.article_id']=$article_id; 
			$article=$article_mod->field('title,cutline,photo,cate_id,content')->where(array('article_id'=>$article_id,'if_show'=>1))->find(); 
			if(empty($article)) $this->toJson('aritcle_id not exists.');
		}else{
			$this->toJson('aritcle_id不能为空.');
		}   
		 
    	$model= M('goods'); 
    	
    	$TotalRecords=$model->join(' as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
	    	->Field('g.goods_id')
	    	->count();
			
    	$listrows=I('pagesize',10,'intval');
    	if($listrows>100) $listrows=100;
    	$PageCount=ceil($TotalRecords/$listrows);
    	$list=$model->join(' as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
    		->page($pageindex.','.$listrows)
	    	->Field('g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale')
	    	->order('ag.orderNum asc,ag.goods_id desc') 
	    	->select(); 
	     $listsql=$model->getLastsql();		   	
    	$article_goods_mod=M('article_goods');
		foreach($list as $key => $val){
			$list[$key]['goods_desc']=htmlspecialchars_decode($val['goods_desc']);
			//$list[$key]['article_id']=$article_goods_mod->where('goods_id='.$val['goods_id'])->limit('1')->getField('article_id'); 
		}
		$data=array(
			'PageIndex'=>$pageindex,
			'PageCount'=>$PageCount,
			'TotalRecords'=>$TotalRecords,
			'article_id'=>$article_id,
			'article_title'=>$article['title'],
			'article_photo'=>$article['photo'],
			'article_cutline'=>$article['cutline'],
			'article_content'=>base64_encode($article['content']),
			'cate_id'=>$article['cate_id'],
			'Goods'=>$list
		);
    	if($debug){
    		if(isset($_REQUEST['show_sql'])) echo $listsql;
    		return $data;
    	}else{
    		$this->toJson('获取商品成功.',1,$data);
    	}
    }
    
    
    /** 获取商品列表 */
    function getGoodsSet($debug=FALSE){
    	$where=array(
    		'is_on_sale'=>1
    	); 
    	$pageindex=I('pageindex',0,'intval');
    	if(empty($pageindex)) $pageindex=1;  
    	
    	$goods_ids=I('goods_ids',0,'trim'); 
    	if(!empty($goods_ids)){     		
    		$where['g.goods_id']=array('in',trim($goods_ids,',')); 
    	}else{
    		$this->toJson('goods_ids不能为空');
    	} 
    	
    	$model=M('goods'); 
    	
    	$TotalRecords=$model->alias('g')->where($where)
	    	->Field('g.goods_id')
	    	->count();
			
    	$listrows=I('pagesize',10,'intval');
    	if($listrows>100) $listrows=100;
    	$PageCount=ceil($TotalRecords/$listrows);
    	$list=$model->alias('g')
    		->where($where)
    		->page($pageindex.','.$listrows)
	    	->Field('g.goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale')
	    	->order('g.goods_id desc') 
	    	->select(); 
	    $listsql=$model->getLastsql();		   	
    	$article_goods_mod=M('article_goods');
    	
    	    	 
	   	//传入用户user_id时，检测是否已经收藏
		$list = $this->check_collect($list, 'goods_id', 'goods');
    	
		foreach($list as $key => $val){
			$list[$key]['goods_desc']=strip_tags(htmlspecialchars_decode($val['goods_desc']));
			$list[$key]['article_id']=$article_goods_mod->where('goods_id='.$val['goods_id'])->limit('1')->getField('article_id'); 
		}
		$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'Goods'=>$list);
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
    	$list=M('goods')->where($where)->Field('goods_id,outer_id,goods_name,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,goods_desc,market_price,price,wapImg,sales,is_on_sale,click_count,add_time')->find();
    	if(!$list) $this->toJson('该商品不存在或者已经下架.',0,$goods_id);
		if($list['goods_desc']){
			$list['goods_desc']=strip_tags(htmlspecialchars_decode($list['goods_desc'])); 
		}
		$wapImg = explode(',',$list['wapimg']);
		$count=count($wapImg); 
		$list['wapImg']=array();
		for($i=0;$i<$count;$i++){
			$n= floor($i/5);
			$list['wapImg'][$n][] = $wapImg[$i];
		}
    	$this->toJson('获取商品成功.',1,$list);
    } 
    
    /** 获取商品介绍 html */
    function getGoodsDescHtml(){  
    	$goods_id=I('goods_id');
    	if(empty($goods_id)){ $this->toJson('商品ID错误.'); }
    	$where=array(
    		'goods_id'=>$goods_id,
    	);     
    	$list=M('goods')->where($where)->Field('goods_id,outer_id,goods_name,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,goods_desc,market_price,price,wapImg,sales,is_on_sale,click_count,add_time')->find();
    	if(!$list) $this->toJson('该商品不存在或者已经下架.',0,$goods_id);
		if($list['goods_desc']){ 
			$list['goods_desc']= htmlspecialchars_decode($list['goods_desc']);
		}
		$wapImg = explode(',',$list['wapimg']);
		$count=count($wapImg); 
		$list['wapImg']=array();
		for($i=0;$i<$count;$i++){
			$n= floor($i/5);
			$list['wapImg'][$n][] = $wapImg[$i];
		}
		//传入用户id时，检测是否已经收藏
		$list = $this->check_collect($list, $goods_id, 'goods'); 
		
    	$this->toJson('获取商品成功.',1,$list);
    } 
        
    /** 获取商品详情图片WapImg  */
    function getGoodsWapImg(){  
    	$goods_id=I('goods_id');
    	if(empty($goods_id)){ $this->toJson('商品ID错误.'); }
    	$where=array(
    		'goods_id'=>$goods_id,
    	);     
    	$list=M('goods')->where($where)->Field('wapimg')->find();
    	if(!$list) $this->toJson('该商品不存在或者已经下架.',0,$goods_id);
		 
		$wapImg = explode(',',$list['wapimg']); 
		 

		$pagedata=array();
		$TotalRecords=count($wapImg); 
		$pageindex=I('pageindex',1,'intval');  
    	$pagesize=I('pagesize',10,'intval');
    	$PageCount=ceil($TotalRecords/$pagesize);
    	
		foreach ($wapImg as $key => $val){
			$pagedata[$key]['img'] = $val;
		}
		
		$start=($pageindex-1)*$pagesize;
		$pagedata = array_slice($pagedata,$start,$pagesize);
		
		$data=array(
			'PageIndex'=>$pageindex,
			'PageCount'=>$PageCount,
			'TotalRecords'=>$TotalRecords,
			'pagedata' => $pagedata
		);
		
    	$this->toJson('获取商品成功.',1,$data);
    }     
    
    
    /** 获取用户的收藏列表 */
    function getCollect(){ 
    	$c_mod=D('collect');
    	$user_id=I('user_id');
    	if(empty($user_id)) $this->toJson('用户ID缺少.');
    	$token=I('token','','trim');
    	if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}  
    	$where=array(
    		'user_id'=>$user_id
    	);
    	$cid=I('cid',0,'intval');
    	if(!empty($cid)){
    		$where['cid']=$cid;
    	} 
        	
    	//收藏类型：article,bbs,blog,goods,brand
    	$type = I('type','','trim');  
    	if(!empty($type)){
    		$allow_type=array('article','bbs','blog','goods','brand');
    		if(!in_array($type, $allow_type)){
    			$this->toJson('type类型不对,只允许：'.join(',',$allow_type));
    		}
    		$where['type'] = $type;
    	}
    	
    	$pageindex=I('pageindex','','intval');
    	if(empty($pageindex)) $pageindex=1; 
    	$TotalRecords=$c_mod->where($where)->count();
    	$pagesize=I('pagesize',10,'intval');
    	$PageCount=ceil($TotalRecords/$pagesize);
    	$list=$c_mod->where($where)->page($pageindex.','.$pagesize)->order('cid desc')->select();
    	$article_mod=M('article');
    	$goods_mod=M('goods');
		$article_goods_mod=M('article_goods');
    	foreach ($list as $key => $val){
    		if($val['type']=='article'){
    			$list[$key]['data'] = $article_mod->where('article_id='.$val['key_id'])->field('article_id,cate_id,title,photo0,photo,cutline,collect_num,view_num,video,ctime,is_top,is_index')->find();
    		}elseif($val['type']=='goods'){
    			$list[$key]['data'] = $goods_mod->where('goods_id='.$val['key_id'])->field('goods_id,outer_id,goods_name,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,goods_desc,market_price,price,sales,is_on_sale,click_count,add_time')->find();	
				$list[$key]['data']['article_id']=$article_goods_mod->where('goods_id='.$val['key_id'])->limit('1')->getField('article_id');
			}
    	}
    	$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'collect'=>$list);
    	$this->toJson('获取收藏成功.',1,$data);
    }
    
	
	/** 博客列表 */
	function bloglist(){
		$blog_mod = D('Blog');
		$blogcate_mod = D('Admin/Blogcate');
    	$where =array(
    		'if_show'=>1, 
    	);  
//     	$cate_id=I('cate_id','','intval');
//     	if($cate_id){
//     		$where['cate_id'] = $cate_id;
//     	} 
    	if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
    	    $cate_id = I('cate_id','','intval');
    	    $cate_ids='';
    	    $blogcate_mod->_get_children_cate_id($cate_ids,$cate_id);
    	    $cate_ids=join(',',$cate_ids);
    	    $where['cate_id']=array('exp','in('.$cate_ids.')');
    	}
    	
		$pageindex=I('pageindex',1,'intval'); 
    	$TotalRecords=$blog_mod->where($where)->count();
    	$pagesize=I('pagesize',10,'intval');
    	$PageCount=ceil($TotalRecords/$pagesize);
		
        $count = $blog_mod->where($where)->count(); 
        C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
        $page = new \Think\Page($count,$pagesize); 
    	$list=$blog_mod->where($where)
    		->Field('bid,cate_id,title,clicks,img,cutline,author,ctime')
    		->limit($page->firstRow.','.$page->listRows) 
    		->order('ctime desc')
    		->select();
    	foreach ($list as $key =>$val){
    		$list[$key]['cate_name']=$blogcate_mod->getFieldBy($val['cate_id'],'cate_name');
    	} 
    	
	   	//传入用户user_id时，检测是否已经收藏
		$list = $this->check_collect($list, 'bid', 'blog');  
    	
		$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
    	$this->toJson('请求成功.',1,$data); 
	}
	
	/** 博客类别 */
	function blogcate(){ 
		$blogcate_mod = D('Blogcate');
    	$where =array(
    		'if_show'=>1, 
    	);  
    	$cate_id=I('cate_id','','intval');
    	if($cate_id){
    		$where['cate_id'] = $cate_id;
    	} 
    	$list=$blogcate_mod->where($where)  
    		->order('sort_order desc,cate_id desc')
    		->select(); 
    	$this->toJson('请求成功.',1,$list); 
	}
	
	/** 博客详情 */
	function blogcenter(){ 
		$bid=I('bid',0,'intval');
		if(empty($bid)) $this->toJson('博客bid参数缺少.');
		$list = M('Blog')->where(array('bid'=>$bid))->field('bid,cate_id,title,clicks,author,content,ctime')->find();
		$this->toJson('请求成功.',1,$list);
	}
		
	
	/** 获取最新api版本*/
	function appversion(){
	    $where=array(
	        'is_check'=>1
	    );
	    $type=I('type','');
	    if($type=='android'){
	        $where['type']='android';
	    }elseif($type=='iphone'){
	        $where['type']='iphone';
	    }elseif($type=='others'){
	        $where['type']='others';
	    }
	    $app_info=M('appversion')->where($where)->order('version desc')->find();
	    if($app_info){
	        $this->toJson('获取api成功.',1,$app_info);
	    }else{ 
	        $this->toJson('未发布或者获取失败.');
	    }
	}
	
	
	/** 品牌列表 */
	function brandlist(){
		$brand_mod = M('brand');
		$where = array(
			'if_show'=>1
		);
		$cate_id = I('cate_id',0,'intval');
		if(!empty($cate_id)){
			$where['cate_id'] = $cate_id;
		}
		
		$pageindex=I('pageindex',1,'intval');  
    	$pagesize=I('pagesize',10,'intval');
    	if($pagesize>100) $pagesize=100;
    	
    	$TotalRecords = $brand_mod->where($where)->count();   
    	$PageCount=ceil($TotalRecords/$pagesize); 
    	
		$list= $brand_mod->where($where)
			->field('brand_id,brand_name,blogo,cate_id,views,collect_num,catchline,letter')		
			->page($pageindex.','.$pagesize)
			->order('sort_order asc,brand_id DESC')
			->select();  
		$brandCate=getBrandCate();
		foreach ($list as $key => $val){
			$list[$key]['cate_name'] = $brandCate[$val['cate_id']];
		}
		
		$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list); 
		$this->toJson('获取品牌成功.',1,$data); 
	}
	
	
	/** 品牌馆 (读取所有大类下的品牌列表) */	
	function acatebrand(){
		$acate_brand_mod = D('acate_brand'); //dump($acate_brand_mod->getBrandJson(1031)); exit;
		$acategory_mod = M('acategory');   //dump(S('acategory'));
		$where = array('parent_id'=>0,'if_show'=>1);
		
		$pageindex=I('pageindex',1,'intval');  
    	$pagesize=I('pagesize',10,'intval');
    	if($pagesize>100) $pagesize=100;
    	
    	$TotalRecords = $acategory_mod->where($where)->count();   
    	$PageCount=ceil($TotalRecords/$pagesize); 
    	
		
		$acate = $acategory_mod->where($where)
			->field('cate_id,cate_name')
			->page($pageindex.','.$pagesize)
			->select();
			
		$data = array();
		foreach ($acate as $key => $val){
			$data[$key]['cate_id'] = $val['cate_id'];
			$data[$key]['cate_name'] = $val['cate_name'];
			$data[$key]['brand'] =  $acate_brand_mod->getBrandJson($val['cate_id'],false);	
		} 
		
		$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$data); 
		$this->toJson('获取品牌成功.',1,$data); 
	}
	
	
	/** 品牌详情 */
	function branddetail(){
		$brand_mod =  M('brand');
		$brand_id = I('brand_id',0,'intval');
		if(empty($brand_id)){
			$this->toJson('品牌ID错误.');
		} 
		$brand = $brand_mod->where(array('brand_id'=>$brand_id))->find();
		if(empty($brand)){
			$this->toJson('品牌不存在.');
		}  
		$this->toJson('获取品牌成功.',1,$brand);
		
	} 
	
	
    /** 获取品牌对应商品  */
    function getBrandGoods(){  
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
		
		//品牌
		$brand_id=I('brand_id',0); 
    	if(!empty($brand_id)){ 
			$where['brand_id']=$brand_id; 
		}else{
			$this->toJson('品牌brand_id不能为空.');
		}    
		
//    	$goods_ids=I('goods_ids',0,'trim'); 
//    	if(!empty($goods_ids)){     		
//    		$where['g.goods_id']=array('in',trim($goods_ids,',')); 
//    		$this->getGoodsSet();  //商品优化查询
//    		exit;
//    	}elseif(empty($article_id)){
//    		$this->toJson('article_id不能为空.');
//    	}	

    	
    	$model=M('goods'); 
    	//$TotalRecords=$model->where($where)->count();
    	$TotalRecords=$model #->join(' as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
	    	->Field('goods_id')
	    	->count();
			
    	$pagesize=I('pagesize',10,'intval');
    	if($pagesize>1000) $pagesize=1000;
    	$PageCount=ceil($TotalRecords/$pagesize);
    	$list=$model #->join(' as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
    		->where($where)
    		->page($pageindex.','.$pagesize)
	    	->Field('goods_id,goods_name,goods_code,goods_img,goods_img2,goods_img3,goods_img4,goods_img5,cate_id,market_price,price,click_count,add_time,goods_desc,is_new,is_hot,is_promote,sales,is_on_sale,brand_id,article_ids')
	    	->order('sort_order desc,goods_id desc') 
	    	->select(); 
	    //echo $listsql=$model->getLastsql();		   	
//    	$article_goods_mod=M('article_goods');
		foreach($list as $key => $val){
			$list[$key]['goods_img']=dealImg($val['goods_img']);
			$list[$key]['goods_desc']=strip_tags(htmlspecialchars_decode($val['goods_desc']));
			//$list[$key]['article_id']=$article_goods_mod->where('goods_id='.$val['goods_id'])->limit('1')->getField('article_id');
			//echo M()->getLastsql();
		}

		$data=array('TotalRecords'=>$TotalRecords,'PageCount'=>$PageCount,'PageIndex'=>$pageindex,'Goods'=>$list);
    	  
    	$this->toJson('获取品牌商品成功.',1,$data); 
    }
	
	
	/** 专题评论 */
	function articleComments(){ 
		$mod = M('article_comments');
    	$where =array(
    		'status'=>1,  
    	);  
    	$article_id=I('article_id','','intval');
    	if($article_id){
    		$where['article_id'] = $article_id;
    	}else{
    		$this->toJson('article_id专题ID不能为空.');
    	} 
    	 
    	
    	$pageindex=I('pageindex','','intval');
    	if(empty($pageindex)) $pageindex=1;  
    	$pagesize=I('pagesize',10,'intval');
    	if($pagesize>100) $pagesize=100; 
    	$TotalRecords = $mod->where($where)->count();   
    	$PageCount=ceil($TotalRecords/$pagesize); 
    	
    	$list=$mod->where($where)  
    		->Field('comment_id,user_id,nick_name,status,article_id,ctime,approve_num,content,type,to_userid,to_nickname')  
    		->where($where)
    		->page($pageindex.','.$pagesize)
    		->order('ctime desc')
    		->select(); 
    	
    	
    	foreach ($list as $key => $val){
    		$user_ids[] = $val['user_id'];
    		//$list[$key]['user_icon'] = getNameById('u_pic', 'user', 'user_id', $val['user_id']);
    	} 
    	
    	$users =M('user')->field('user_id,u_pic')->where( array('user_id'=>array('in',$user_ids)) )->select(); 
    	 
    	$user_icon = array();
    	foreach ($users as $key => $val){ 
    		$user_icon[$val['user_id']] = $val['u_pic'];
    	} 
    	//
    	foreach ($list as $key => $val){
    		$list[$key]['user_icon'] = dealImg($user_icon[$val['user_id']]);
    	}
    	
    	$data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
    	$this->toJson('请求成功.',1,$data); 
	}
	
 
	
	/** 发布专题评论 */
	function articleCommentsAdd(){ 
		$mod = M('article_comments');
    	$user_id=I('user_id');
    	if(empty($user_id)) $this->toJson('用户ID缺少.');
    	$token=I('token','','trim');
    	if(!$this->checkUserToken($user_id, $token)){
    		$this->toJson('UserToken验证失败.');
    	}   
    	
    	$article_id =I('article_id',0,'intval');
    	if(empty($article_id)){
    		$this->toJson('专题ID不能为空.');
    	}
    	$article_mod=M('article');
    	if(!$article_mod->find($article_id)) $this->toJson('专题不存在.');
    	
    	$content=I('content');
    	if(empty($content)){
    		$this->toJson('评论内容不能为空.');
    	}
    	
    	$data =array(
    		'status'=>1, 
    		'user_id'=>$user_id,
    		'article_id' => $article_id,
    		'ctime' => gmtime(),
    		'ip' => get_client_ip(),
    		'content' => $content,
    		'to_userid' => I('to_userid',0,'intval'),
    		'type' =>  I('type','content','trim')
    	);   
    	if($data['type']=='reply'){
    		if(empty($data['to_userid'])){
    			$this->toJson('请选择回复对象(to_userid).');
    		}
    		$data['to_nickname'] =  getNameById('nick_name', 'user', 'user_id', $data['to_userid']);
    	}
    	
    	
    	$user = M('user')->field('user_name,nick_name,status')->where(array('user_id'=>$user_id))->find(); 
     	if(!user){
    		$this->toJson('用户不存在.');
    	}elseif(!$user['status']){
    		$this->toJson('用户已经被禁止发言.'.$user['status']);
    	}
    	$data['nick_name'] = $user['nick_name'] ? $user['nick_name'] : $user['user_name']; 
    	
    	if($mod->add($data)){ 
    		$this->toJson('评论成功.',1,$data); 
    	}else{
    		$this->toJson('评论失败.'); 
    	}
	}    
	
	/** 发布专题评论 */
	function articleCommentsDelete(){
	    $mod = M('article_comments');
	    $user_id=I('user_id');
	    if(empty($user_id)) $this->toJson('用户ID缺少.');
	    $token=I('token','','trim');
	    if(!$this->checkUserToken($user_id, $token)){
	        $this->toJson('UserToken验证失败.');
	    }
	     
	    $comment_id =I('comment_id',0,'intval');
	    if(empty($comment_id)){
	        $this->toJson('评论comment_id不能为空.');
	    }
	    
	    $this->toJson('评论 删除成功.',1,$mod->where(array('comment_id'=>$comment_id,'user_id'=>$user_id))->delete());
	}
	
	
	/** 点赞  */
	function articleCommentsApprove(){
		$mod = M('article_comments');
	    $user_id=I('user_id');
	    if(empty($user_id)) $this->toJson('只有登录用户才能点赞.');
	    $token=I('token','','trim');
	    if(!$this->checkUserToken($user_id, $token)){
	        $this->toJson('UserToken验证失败.');
	    }
	    $comment_id =I('comment_id',0,'intval');
	    if(empty($comment_id)){
	        $this->toJson('评论comment_id不能为空.');
	    }

	    $where = array('comment_id'=>$comment_id); 
	    
	    $map = array(
	        'key_id'=>$comment_id,
	        'type'=>'article_comments',
	        'user_id' => $user_id
	    );
	    $find = M('approve_log')->where($map)->find(); # echo M()->getLastsql();
	    if($find){
	        $this->toJson('你已经点赞过了.');
	    }
	    
	    $approve_num = $mod->where($where)->getField('approve_num');
	     
	    if($approve_num===null) $this->toJson('该评论不存在或已经被删除.');
	    
	    if($approve_num<1) $approve_num = 0;
	    
	    $data = array(
	        'approve_num' => $approve_num+1 
	    );
	    
	    $res = $mod->where($where)->save($data); 
	    if($res){
	        M('approve_log')->add(array(
	            'user_id' => $user_id,
	            'type' => 'article_comments',
	            'key_id' => $comment_id,
	            'ctime' => gmtime(),
	            'ip' => $_SERVER['REMOTE_ADDR'],
	            'http_user_agent' => $_SERVER['HTTP_USER_AGENT']
	        ));
	    }
	    
	    $this->toJson('点赞成功.',1,$data['approve_num']);
	}
	
	/** 取消点赞  */
	function articleCommentsNotApprove(){
	    $mod = M('article_comments');
	    $user_id=I('user_id');
	    if(empty($user_id)) $this->toJson('只有登录用户才能点赞.');
	    $token=I('token','','trim');
	    if(!$this->checkUserToken($user_id, $token)){
	        $this->toJson('UserToken验证失败.');
	    }
	    $comment_id =I('comment_id',0,'intval');
	    if(empty($comment_id)){
	        $this->toJson('评论comment_id不能为空.');
	    }
	    
	    $where = array('comment_id'=>$comment_id);
	       
	    $approve_num = $mod->where($where)->getField('approve_num');
	    
	    if($approve_num===null) $this->toJson('该评论不存在或已经被删除.');
	    
	    if($approve_num<1) $approve_num = 1;
	     
	    $data = array(
	        'approve_num' => $approve_num-1
	    );
	     
	    $map = array(
	        'key_id'=>$comment_id,
	        'type'=>'article_comments',
	        'user_id' => $user_id
	    );
	    
	    if( M('approve_log')->where($map)->delete() ){
	       $mod->where($where)->save($data);
	       $this->toJson('取消点赞成功.',1,$data['approve_num']);
	    }else{
	        $this->toJson('已经取消点赞过了.');
	    }
	    
	    
	     
	}
	
	/** 举报 */
	function tipoff(){ 
		$mod = M('tipoff');
    	$user_id=I('user_id',0); 
    	$key_id =I('key_id',0,'intval');
    	if(empty($key_id)){
    		$this->toJson('评论主键key_id不能为空.');
    	}  
    	$content=I('content','','trim');
    	if(empty($content)){
    		$this->toJson('举报内容不能为空.');
    	}
    	
    	$data =array( 
    		'user_id'=>$user_id,
    		'key_id' => $key_id,
    		'ctime' => gmtime(),
    		'ip' => get_client_ip(),
    		'content' => $content, 
    		'type' =>  I('type','article_comments','trim')
    	);    
    	if($user_id){
    		$user = M('user')->field('user_name,nick_name,status')->where(array('user_id'=>$user_id))->find(); 
	     	if(!user){
	    		$this->toJson('用户不存在.');
	    	} 
    	}
    	if($tipoff_id=$mod->add($data)){  
    		$data['tipoff_id'] = $tipoff_id;
    		$this->toJson('举报成功.',1,$data); 
    	}else{
    		$this->toJson('举报失败.'); 
    	}
	}    
	
	
	
	/**   
	 *  错误上传：手机版本号，App版本号，错误内容，所在页面 
	 * */
	function sendAppError(){	
		 	
		$type = I('type','','trim');
		if(empty($type)) $this->toJson('type手机类型不能为空.');
		
		$version = I('version','','trim');
		if(empty($version)) $this->toJson('version手机版本号不能为空.');
		
		$error_desc = I('error_desc','','trim');
		if(empty($error_desc)) $this->toJson('error_desc错误描述不能为空.');
		
		$api = I('api','','trim');
		if(empty($api)) $this->toJson('页面请求的API不能为空.');
		
		$data=array(
			'type' => $type,
			'version' =>$version,
			'error_desc' => $error_desc,
			'api' => $api,
			'ctime' =>gmtime()
		);
		$res=M('apperror')->add($data);
		if($res){
			$this->toJson('APP错误上传成功.',1,$res);
		}else{ 
			$this->toJson('APP错误上传失败.');
		}
		
	}
	
	
	/** 更新用户信息(昵称必填) */
	function user_update(){
	    $where['token']=I('token','','trim');
	    $where['user_id']=I('user_id','','trim');
	    if(empty($where['user_id'])){
	        $this->toJson('用户user_id不能为空.');
	    }
	    if(!$this->checkUserToken($where['user_id'], $where['token'])){
	        $this->toJson('用户令牌验证失败.');
	    }
	    $data['nick_name']=I('nick_name','','trim');
	    $data['phone']=I('phone','','trim');
	    $data['age']=I('age','','trim');
	    $data['sex']=I('sex','','trim');
	    $data['introduction']=I('introduction','','trim');
	    $data['address']=I('address','','trim');
	    $data['qq']=I('qq','','trim');
	    $data['memo']=I('memo','','trim');
	    $data['city']=I('city','','trim');
	    
	    if(empty($data['nick_name'])){
	        $this->toJson('用户昵称不能为空.');
	    } 
	    
	    if(isIllegalStr($data['nick_name'])){
	        $this->toJson('用户昵称不能包含非法字符.');
	    } 
	    
// 	    //孕期阶段 (单独API修改孕期阶段)
// 	    $period = I('period','','trim');
// 	    $data['period']= $period;
// 	    if(empty($period)){
// 	        $this->toJson('孕期阶段不能为空.');
// 	    } 
	    
	    $res=M('user')->where($where)->save($data);
	    if($res!==FALSE){
	        $this->toJson('用户更新成功.',1);
	    }else{
	        $this->toJson('请求失败.');
	    }
	}
	
	
	/** 更新用户孕期  */
	function user_period_update(){
	    $token=I('token','','trim');
	    $user_id = $where['user_id'] =I('user_id','','trim');
	    if(empty($user_id)){
	        $this->toJson('用户user_id不能为空.');
	    }
	    if(!$this->checkUserToken($user_id, $token)){
	        $this->toJson('用户令牌验证失败.');
	    }
	    
	    $period=I('period','','intval');  //孕期阶段：1-备孕；2-怀孕；3-产后；
	    
	    $mensesStartDate=I('mensesStartDate','','trim');  //末次月经开始日期	    
	    $mensesDays=I('mensesDays','','trim');  //经期 （一般7天） 
	    $menstrualCycle =I('menstrualCycle','','trim'); //月经周期 （一般30天） 
	    
	    $expectedDate=I('expectedDate','','trim');  //预产期 9周的第二个星期
	    
	    $babyBirthday=I('babyBirthday','','trim');  //孩子生日
	    $babySex=I('babySex','','trim');  //孩子性别
	     
	    if(empty($period)){
	        $this->toJson('请选择孕期阶段.');
	    } 
	    if(!array_key_exists($period,getPeriod())){
	        $this->toJson('你选择的孕期阶段不正确.');
	    } 
	     
	    if($period==1 ){
	        if(empty($mensesStartDate)){
	            $this->toJson('末次月经开始日期(mensesStartDate)不能为空.');
	        }    
	        if(!strtotime($mensesStartDate)){
	            $this->toJson('末次月经开始日期必须是日期格式.');
	        }
	        if(empty($mensesDays)){
	            $this->toJson('经期(mensesDays)不能为空.');
	        } 	
	        if(empty($menstrualCycle)){
	            $this->toJson('月经周期(menstrualCycle)不能为空.');
	        } 	  
	        $userdata = array(
	            'mensesStartDate' => $mensesStartDate,
	            'mensesDays' => $mensesDays,
	            'menstrualCycle' => $menstrualCycle,
	        );
	    }elseif($period==2){ 
	        if(empty($expectedDate)){
	            $this->toJson('预产期(expectedDate)不能为空.');
	        }
	        if(!strtotime($expectedDate)){
	            $this->toJson('预产期(expectedDate)日期格式不能正确.');
	        }	       
	        $userdata = array(
	            'expectedDate' => $expectedDate
	        );
	    }elseif($period==3){ 
	        if(empty($babyBirthday)){
	            $this->toJson('宝宝生日(babyBirthday)不能为空.');
	        }
	        if(!strtotime($babyBirthday)){
	            $this->toJson('宝宝生日(babyBirthday)格式不能正确.');
	        }		        
	        if(empty($babySex)){
	            $this->toJson('宝宝性别(babySex)不能为空.');
	        }
	        if(isIllegalStr($babySex)){
	            $this->toJson('宝宝性别(babySex)不能包含非法字符.');
	        }
	        $userdata = array(
	            'babyBirthday' => $babyBirthday,
	            'babySex' => $babySex,
	        );
	    } 
	     
	    $data = array_merge($userdata,array(
	        'user_id' => $user_id,
	        'period' => $period,
	    )); 
	    
	    $res=M('user')->where($where)->save($data); 
	    if($res!==FALSE){
	        $this->toJson('用户更新成功.',1);
	    }else{
	        $this->toJson('请求失败.');
	    }
	}
	
	
	
	
	
	/** 孕期进展(参考妈妈网数据)  */
	function gestaste(){
	    $day = I('day',1,'trim');
	    if($day>7) $day = 7;
	    $week = I('week','','trim');
	    if(empty($week)){
	        $this->toJson('周不能为空.');
	    }
	    $where = array(
	        'week'=>$week, 
	        'day'=>$day,
	    );
	    $list = M('gestate')->where($where)->find();
	    if(empty($list)){ 
	        $this->toJson('孕期进展请求失败.');
	    }
	    $list['nutrition_des'] = strip_tags($list['nutrition_des'],'<br>');
	    $list['educate_json'] = json_decode($list['educate_json'],true);
	    $list['baby_json'] = json_decode($list['baby_json'],true);
	    
	    $this->toJson('孕期进展请求成功.',1,$list);
	    
	}
	
	
	
	/** 用户分享统计  */
	function share(){
	    $user_id = $where['user_id'] =I('user_id','','trim'); 
	    
	    if(!$user_id){ 	   
    	    $token=I('token','','trim');
    	    if(!$this->checkUserToken($user_id, $token)){
    	    //    $this->toJson('用户令牌验证失败.');
    	    }
	    }
	     
	    $type=I('type','','trim');  //分享类型：article,blog,goods,bbs,wiki；
	    if(empty($type)){
	        $this->toJson('分享类型不能为空.');
	    }
	    if(!in_array($type, array('article','blog','goods','bbs','wiki','app','recipe'))){
	        $this->toJson('分享类型type只能是article,blog,goods,bbs,wiki,app');
	    }
	    $keyid = I('keyid','','intval');
	    if(empty($keyid)){
	        $this->toJson('分享类型表对应的keyid不能为空.');
	    }
	    $channel = I('channel','','trim');
	    if(empty($channel)){
	        $this->toJson('分享渠道不能为空.');
	    }
	    
	    $status = I('status',0,'intval');
	    if(empty($status)){
	        $this->toJson('状态不能为空.');
	    }
	    
	    $data = array(
	        'channel' => $channel,
	        'type' => $type,
	        'keyid' => $keyid,
	        'user_id' => $user_id,
	        'ip' => get_client_ip(),	        
	        'http_user_agent' => $_SERVER['HTTP_USER_AGENT'],
	        'ctime' => gmtime(),
	        'status' => $status
	    ); 
	    
	    if( M('share')->add($data) ){
	        $this->toJson('提交成功.',1);	  
	    }else{
	        $this->toJson('提交失败.');
	    }
          
	}
	
	
	
	/** 孕妈百科 列表 */
	function wikilist(){
	    $wiki_mod = M('Wiki');
	    $wikicate_mod = D('Admin/Wikicate');
	    $where =array(
	        'if_show'=>1,
	    ); 	    
	
	    if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
	        $cate_id = I('cate_id','','intval');
	        $cate_ids='';
	        $wikicate_mod->_get_children_cate_id($cate_ids,$cate_id);
	        $cate_ids=join(',',$cate_ids);
	        $where['cate_id']=array('exp','in('.$cate_ids.')');
	    }
	     
	    $pageindex=I('pageindex',1,'intval');
	    $TotalRecords=$wiki_mod->where($where)->count();
	    $pagesize=I('pagesize',10,'intval'); $pagesize>100?$pagesize=100:'';
	    $PageCount=ceil($TotalRecords/$pagesize);
	    #dump($pageindex);

	    
	    $count = $wiki_mod->where($where)->count();
	    C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
	    $page = new \Think\Page($count,$pagesize);  
	     
	    //首页随机显示
	    if($pageindex==1){ 
	       $page->firstRow = (round(rand(1, $PageCount))-1)*$pagesize;
	    }
	    
	    $list=$wiki_mod->where($where)
	    ->Field('id,cate_id,title,photo,cutline,cate_label,view_num,ctime')
	    ->limit($page->firstRow.','.$page->listRows)
	    ->order('ctime desc')
	    ->select();   
	    
	     
	    foreach ($list as $key =>$val){
	        $list[$key]['cate_name']=$wikicate_mod->getFieldBy($val['cate_id'],'cate_name');
	        $list[$key]['h5']= U('h5/wikidetail',array('id'=>$val['id']),'html',true);
	    }
	
	    //传入用户user_id时，检测是否已经收藏
	    $list = $this->check_collect($list, 'id', 'wiki');
	
	    $data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
	    $this->toJson('请求成功.',1,$data);
	}

	
	/** 百科随机列表  - 随机显示，每次都是最新的，无需分页  */
	function wiki_randomize(){
	    $wiki_mod = M('Wiki');
	    $wikicate_mod = D('Admin/Wikicate');
	    $where =array(
	        'if_show'=>1,
	        'navigation'=>array('exp','not like "%食材%"')
	    ); 	    
        //随机id
        $db_prefix = C('DB_PREFIX');
        $randid = M()->query(" SELECT (ROUND(   RAND() * ( (SELECT MAX(Id) FROM `{$db_prefix}wiki`)-(SELECT MIN(Id) FROM {$db_prefix}wiki))   )  + (SELECT MIN(Id) FROM {$db_prefix}wiki) ) as randid ");
        $randid = current(current($randid));
        $where['id'] = array('gt',$randid); 

        #echo M()->getLastsql();
	    $pageindex=I('pageindex',1,'intval');
	    $TotalRecords=$wiki_mod->where($where)->count();
	    $pagesize=I('pagesize',10,'intval'); $pagesize>100?$pagesize=100:'';
	    $PageCount=ceil($TotalRecords/$pagesize);
	
	    $count = $wiki_mod->where($where)->count();
	    C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
	    //$page = new \Think\Page($count,$pagesize);
	    $list=$wiki_mod->where($where)
	    ->Field('id,cate_id,title,photo,cutline,cate_label,view_num,ctime')
	    //->limit($page->firstRow.','.$page->listRows)
	    ->limit(0,$pagesize)  
	    ->order('id asc')
	    ->select();  
	    # echo M()->getLastsql();
	    foreach ($list as $key =>$val){
	        $list[$key]['cate_name']=$wikicate_mod->getFieldBy($val['cate_id'],'cate_name');
	        $list[$key]['h5']= U('h5/wikidetail',array('id'=>$val['id']),'html',true);
	        //$list[$key]['navigation']= strip_tags($val['navigation']);
	    }
	
	    //传入用户user_id时，检测是否已经收藏
	    $list = $this->check_collect($list, 'id', 'wiki');
	
	    $data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
	    $this->toJson('请求成功.',1,$data);
	}
	
	/** 百科类别 */
	function wikicate(){
	    $wikicate_mod = D('Admin/Wikicate');
	    $where =array(
	        'if_show'=>1,
	    );
	    $cate_id=I('cate_id','','intval');
	    if($cate_id){
	        $where['cate_id'] = $cate_id;
	    }
	    // 	    $list=$wikicate_mod->where($where)
	    // 	    ->order('sort_order desc,cate_id desc')
	    // 	    ->select();
	    $list=$wikicate_mod->get_category($cate_id,true);
// 	    foreach ($list as $key => $val){
// 	        $list[$key]['photo'] = dealImg($list[$key]['photo']);
// 	    } 
	    $this->toJson('请求成功.',1,$list);
	}
	
	/** 百科详情 */
	function wikidetail(){
	    $id=I('id',0,'intval');
	    if(empty($id)) $this->toJson('百科id参数缺少.'); 
	    $wiki_mod  = M('Wiki');
	    $list = $wiki_mod->where(array('id'=>$id,'if_show'=>1))->field('id,cate_id,title,photo,cutline,view_num,content,ctime')->find();
	    if(empty($list)){
	        $this->toJson('该记录不存在或者被禁止显示。');
	    }
	    $list['h5']= U('h5/wikidetail',array('id'=>$id),'html',true);
	    
	    $list['prev_id']=$wiki_mod->where('id>'.$id)->order('id asc')->getField('id');
	    $list['next_id']=$wiki_mod->where('id<'.$id)->order('id desc')->getField('id'); 
	    
	    
	    $this->toJson('请求成功.',1,$list);
	}	

	/** 更新用户信息(昵称必填) */
	function wikiuser_update(){
	    $where['token']=I('token','','trim');
	    $where['user_id']=I('user_id','','trim');
	    if(empty($where['user_id'])){
	        $this->toJson('用户user_id不能为空.');
	    }
	    if(!$this->wikiCheckUserToken($where['user_id'], $where['token'])){
	        $this->toJson('用户令牌验证失败.');
	    }
	    $data['nick_name']=I('nick_name','','trim');
	    $data['phone']=I('phone','','trim');
	    $data['age']=I('age','','trim');
	    $data['sex']=I('sex','','trim');
	    $data['introduction']=I('introduction','','trim');
	    $data['address']=I('address','','trim');
	    $data['qq']=I('qq','','trim');
	    $data['memo']=I('memo','','trim');
	    $data['city']=I('city','','trim');
	     
	    if(empty($data['nick_name'])){
	        $this->toJson('用户昵称不能为空.');
	    }
	     
	    if(isIllegalStr($data['nick_name'])){
	        $this->toJson('用户昵称不能包含非法字符.');
	    } 
	    
	    $res=M('wikiuser')->where($where)->save($data);
	    if($res!==FALSE){
	        $this->toJson('用户更新成功.',1);
	    }else{
	        $this->toJson('请求失败.');
	    }
	}
	
	
	/** 更新用户孕期  */
	function wikiuser_period_update(){
	    $token=I('token','','trim');
	    $user_id = $where['user_id'] =I('user_id','','trim');
	    if(empty($user_id)){
	        $this->toJson('用户user_id不能为空.');
	    }
	    if(!$this->wikiCheckUserToken($user_id, $token)){
	        $this->toJson('用户令牌验证失败.');
	    }
	     
	    $period=I('period','','intval');  //孕期阶段：1-备孕；2-怀孕；3-产后；
	     
	    $mensesStartDate=I('mensesStartDate','','trim');  //末次月经开始日期
	    $mensesDays=I('mensesDays','','trim');  //经期 （一般7天）
	    $menstrualCycle =I('menstrualCycle','','trim'); //月经周期 （一般30天）
	     
	    $expectedDate=I('expectedDate','','trim');  //预产期 9周的第二个星期
	     
	    $babyBirthday=I('babyBirthday','','trim');  //孩子生日
	    $babySex=I('babySex','','trim');  //孩子性别
	
	    if(empty($period)){
	        $this->toJson('请选择孕期阶段.');
	    }
	    if(!array_key_exists($period,getPeriod())){
	        $this->toJson('你选择的孕期阶段不正确.');
	    }
	
	    if($period==1 ){
	        if(empty($mensesStartDate)){
	            $this->toJson('末次月经开始日期(mensesStartDate)不能为空.');
	        }
	        if(!strtotime($mensesStartDate)){
	            $this->toJson('末次月经开始日期必须是日期格式.');
	        }
	        if(empty($mensesDays)){
	            $this->toJson('经期(mensesDays)不能为空.');
	        }
	        if(empty($menstrualCycle)){
	            $this->toJson('月经周期(menstrualCycle)不能为空.');
	        }
	        $userdata = array(
	            'mensesStartDate' => $mensesStartDate,
	            'mensesDays' => $mensesDays,
	            'menstrualCycle' => $menstrualCycle,
	        );
	    }elseif($period==2){
	        if(empty($expectedDate)){
	            $this->toJson('预产期(expectedDate)不能为空.');
	        }
	        if(!strtotime($expectedDate)){
	            $this->toJson('预产期(expectedDate)日期格式不能正确.');
	        }
	        $userdata = array(
	            'expectedDate' => $expectedDate
	        );
	    }elseif($period==3){
	        if(empty($babyBirthday)){
	            $this->toJson('宝宝生日(babyBirthday)不能为空.');
	        }
	        if(!strtotime($babyBirthday)){
	            $this->toJson('宝宝生日(babyBirthday)格式不能正确.');
	        }
	        if(empty($babySex)){
	            $this->toJson('宝宝性别(babySex)不能为空.');
	        }
	        if(isIllegalStr($babySex)){
	            $this->toJson('宝宝性别(babySex)不能包含非法字符.');
	        }
	        $userdata = array(
	            'babyBirthday' => $babyBirthday,
	            'babySex' => $babySex,
	        );
	    }
	
	    $data = array_merge($userdata,array(
	        'user_id' => $user_id,
	        'period' => $period,
	    ));
	     
	    $res=M('wikiuser')->where($where)->save($data);
	    if($res!==FALSE){
	        $this->toJson('用户更新成功.',1);
	    }else{
	        $this->toJson('请求失败.');
	    }
	}
	
	 
	//孕助理会员注册方法
	function wikiuser_reg(){ //执行注册
	    $data = array(
	        'user_name' => trim(I('user_name')),
	        'email' => trim(I('email')),
	        'password' => I('password'),
	        'nick_name' => I('nick_name'),
	        'phone' => I('phone'),
	        'age' => I('age'),
	        'sex' => I('sex'),
	        'memo' => I('memo'),
	        'u_pic' => I('u_pic'),
	        'source' => I('source'),
	        'identification' => I('identification'),
	        'DeviceToken' => I('DeviceToken'),
	        'user_token' => I('DeviceToken'),
	        'deviceType' => I('deviceType',''),
	        'period' => I('period',0),
	        'last_login_ip' => get_client_ip()
	    );
	    if(empty($data['source'])){
	        $this->toJson('第三方用户来源(source)不能为空.');   //第三方用户来源(qq／微信/sina等)
	    }
	    if(empty($data['identification'])){
	        $this->toJson('第三方账户唯一标识(identification)不能为空.'); //第三方用户唯一标示(qq号／微信号/sina账号等)
	    }
	    if(empty($data['DeviceToken'])){
	        $this->toJson('手机硬件标识(DeviceToken)不能为空.');
	    } 
	    
	    $find = M('wikiuser')->where(array('source'=>$data['source'],'identification'=>$data['identification']))->find();
	    if($find){
	        $this->toJson('来源：'.$data['source'].',第三方账户唯一标识(identification):'.$data['identification'].',该用户已经存在:'.$find['user_name'].'-'.$find['nick_name']);
	    }
	    
	    $_user_mod = D('Admin/Wikiuser');
	    if(!$_user_mod->create($data)){
	        $this->toJson($_user_mod->getError());
	        return;
	    }
	    $string = new \Org\Util\String();
	    $data['code'] = $string->randString(6);
	    $data['password'] = md5(md5($data['password']).$data['code']);
	    $data['ctime'] = gmtime();
	    $data['status'] = 1;
	    if(!$user_id = $_user_mod->add($data)){
	        $this->toJson('System error, please refresh and try again.');
	        return;
	    }
	    //执行登陆操作
	    $this->do_login($user_id);
	    $data=M('wikiuser')->where('user_id='.$user_id)->find();
	    $this->toJson('恭喜, 注册成功.',1,$data);
	}

	/** 用户登录 */
	function wikiuser_login(){
	    $DeviceToken = trim(I('DeviceToken'));
	    $user_name = trim(I('user_name'));
	    $password = I('password');
	    $_user_mod = D('Admin/Wikiuser');
	    $where['user_name']=$user_name;
	    $where['email']=$user_name;
	    $where['_logic']='or';
	    //if(!$DeviceToken){
	    //    $this->toJson('设备硬件标识DeviceToken不能为空.');
	    //    return;
	    //}
	    //if(strlen($DeviceToken)<8){
	    //	$this->toJson('设备硬件标识DeviceToken不能正确.');
	    //}
	    if(empty($user_name)) $this->toJson('账号不能为空.');
	    $user_info = $_user_mod->field('user_id,password,code,status')->where($where)->find();
	    if($_GET['debug']==true){
	        echo M()->getLastsql();
	        dump($user_info);
	    }
	    if(!$user_info){
	        $this->toJson('账号不存在.');
	        return;
	    }
	    //比对密码是否正确
	    if($user_info['password'] != MD5(MD5($password).$user_info['code'])){
	        $this->toJson('密码不正确.');
	        return;
	    }
	    //判断用户状态
	    if(!$user_info['status']){
	        $this->toJson('用户已经被锁定，请联系客服.');
	        return;
	    }
	    //执行本地登陆
	    $this->do_login($user_info['user_id'],$DeviceToken);
	    //来源地址处理
	    $referer = I('referer');
	    if(!$referer)
	        $referer = U('/Member');
	    //redirect($referer);
	    $data=$_user_mod->where('user_id='.$user_info['user_id'])->find();
	    if($_GET['debug']==true){
	        echo M()->getLastsql();
	        dump($user_info);
	    }
	    $this->toJson('登录成功.',1,$data);
	}
	
	/** 获取孕助理用户信息 */
	function wikiuserinfo(){
	    $uid=I('user_id');
	    if(empty($uid)){
	       $this->toJson('要获取的用户user_id不能为空.');
	    }
	    $loginuid=I('logined_userid');
	    if(empty($loginuid)){
	        $this->toJson('当前登录的用户logined_userid不能为空.');
	    }
	    $token=I('token','','trim');
	    if(!$this->wikiCheckUserToken($loginuid, $token)){
	        $this->toJson('UserToken验证失败.');
	    }
	    $data=M('wikiuser')->where('user_id='.$uid)->find();
	    $this->toJson('请求成功.',1,$data);
	}
	
	
	
	/**验证第三方账户是否已经注册*/
	function wiki_thirdverify(){
	    $identification = I('identification','','trim');
	    $source=I('source','','trim');
	    if(empty($source)){
	        $this->toJson('第三方账户来源source不能为空.');
	    }
	    if(empty($identification)){
	        $this->toJson('第三方账户唯一标识(identification)不能为空.');   //qq号,新浪账号等.
	    }
	    $where['source']= $source;
	    $where['identification']= $identification;
	     
	    $data=M('wikiuser')->where($where)->find();
	    if($data){
	        $dt = array('user_id'=>$data['user_id'],'user_name'=>$data['user_name'],'nick_name'=>$data['nick_name'],'password'=>$data['password']);
	        $this->toJson('已经被注册.',1,$dt);
	    }else{
	        $this->toJson('未注册.');
	    }
	} 
	
	/**更新用户头像*/
	function wikiuserpic_update(){
	    $where['token']=I('token','','trim');
	    $user_id=$where['user_id']=I('user_id','','trim');
	    if(empty($user_id)){
	        $this->toJson('缺少用户user_id');
	    }
	    if(!$this->wikiCheckUserToken($user_id, $where['token'])){
	        $this->toJson('用户令牌(token)验证失败.');
	    }
	    //header('Content-type:text/html;charset=utf-8');
	    //读取图片文件，转换成base64编码格式
// 	    $image_file = 'https://www.ymg280.com/Uploads/user/1/face.png';
// 	    $image_info = getimagesize($image_file);
// 	    $filecontent= file_get_contents($image_file);  var_dump($filecontent);
// 	    //  保存base64字符串为图片
// 	    $base64_image_content = (base64_encode($filecontent));
	
	    //获取传输的图片base64加密后的数据
	    $base64_image_content = I('base64_image_content','','trim');
	    if(empty($base64_image_content)){
	        $this->toJson('图片传输失败.');
	    }
	    $ext= strtolower(I('ext','png','trim'));
	    if(!in_array($ext, array('jpg','jpeg','gif','png'))){
	        $this->toJson('头像图片的后缀不正确:'.$ext);
	    }
	
	    $imgpath = 'Uploads/wikiuser/'.$user_id;
	    //如果不存在自动创建用户目录,默认0777或者继承父目录 （文件夹有执行的权限[7]才能创建文件）
	    @mkdir($imgpath);
	
	    //如果文件存在，修改文件权限为0777 --暂不用
	    $new_file = $imgpath."/face.{$ext}";
	    //$r = chmod($new_file,0777);
	    //dump($r);
	
	    if(file_put_contents($new_file, base64_decode($base64_image_content))){
	        //echo '新文件保存成功：', $new_file;
	        $u_pic=$new_file;
	    }else{
	        $this->toJson('头像保存失败');
	    }
	     
	    $data['u_pic']= dealImg($u_pic);
	    $res=M('wikiuser')->where($where)->save($data);
	    if($res!==FALSE){
	        $this->toJson($data['u_pic'],1);
	    }else{
	        $this->toJson('请求失败.');
	    }
	}
	
	

	//执行本地登陆
	protected function wikiuser_do_login($user_id,$DeviceToken=NULL){
	    $_user_mod = D('Admin/Wikiuser');
	    $user_info = $_user_mod->field('user_id,user_name,nick_name,u_pic,email,logins,last_login_ip,last_login_time,score,user_token')
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
	protected function wikiuser_do_register(){ //执行注册
	    $data = array(
	        'user_name' => trim(I('user_name')),
	        'email' => trim(I('email')),
	        'password' => I('newpassword'),
	        'repassword' => I('repassword'),
	        'is_subscription' => I('is_subscription',0,'intval'),
	    );
	    $referer = I('referer');
	    if(!$referer)
	        $referer = U('/Member/index');
	    $_user_mod = D('Admin/Wikiuser');
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
	    $this->wiki_do_login($user_id);
	     
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

	// 验证 用户 token
	protected function wikiCheckUserToken($user_id,$user_token){
	    $where=array('user_id'=>$user_id);
	    $user_token = addslashes($user_token);
	    $where['_string'] = "user_token='{$user_token}' or DeviceToken='{$user_token}'";
	    $find=M('Wikiuser')->where($where)->find();
	    return $find;
	}
	
	
	


	/** 孕妈百科 列表 */
	function recipelist(){
	    $recipe_mod = M('Recipe');
	    $recipecate_mod = D('Admin/Recipecate');
	    $where =array(
	        'if_show'=>1,
	    );
	
	    if(isset($_REQUEST['cate_id']) && $_REQUEST['cate_id']){
	        $cate_id = I('cate_id','','intval');
	        $cate_ids='';
	        $recipecate_mod->_get_children_cate_id($cate_ids,$cate_id);
	        $cate_ids=join(',',$cate_ids);
	        $where['cate_id']=array('exp','in('.$cate_ids.')');
	    }
	
	    $pageindex=I('pageindex',1,'intval');
	    $TotalRecords=$recipe_mod->where($where)->count();
	    $pagesize=I('pagesize',10,'intval'); $pagesize>100?$pagesize=100:'';
	    $PageCount=ceil($TotalRecords/$pagesize);
	
	    $count = $recipe_mod->where($where)->count();
	    C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
	    $page = new \Think\Page($count,$pagesize);
	    $list=$recipe_mod->where($where)
	    ->Field('id,cate_id,title,photo,cutline,labels,view_num,ctime,suitable_person,taboo_person,cautious_person,cooking_pic,content')
	    ->limit($page->firstRow.','.$page->listRows)
	    ->order('ctime desc')
	    ->select();
	
	    foreach ($list as $key =>$val){
	        $list[$key]['cate_name']=$recipecate_mod->getFieldBy($val['cate_id'],'cate_name');
	        $list[$key]['h5']= U('h5/recipedetail',array('id'=>$val['id']),'html',true);
	    }
	
	    //传入用户user_id时，检测是否已经收藏
	    $list = $this->check_collect($list, 'id', 'recipe');
	
	    $data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
	    $this->toJson('请求成功.',1,$data);
	}
	
	
	/** 百科随机列表  - 随机显示，每次都是最新的，无需分页  */
	function recipe_randomize(){
	    $recipe_mod = M('Recipe');
	    $recipecate_mod = D('Admin/Recipecate');
	    $where =array(
	        'if_show'=>1,
	    );
	
	    $db_prefix = C('DB_PREFIX');
	    $randid = M()->query(" SELECT (ROUND(   RAND() * ( (SELECT MAX(Id) FROM `{$db_prefix}recipe`)-(SELECT MIN(Id) FROM {$db_prefix}recipe))   )  + (SELECT MIN(Id) FROM {$db_prefix}recipe) ) as randid ");
	    $randid = current(current($randid));
	    $where['id'] = array('gt',$randid);
	 
	    $pageindex=I('pageindex',1,'intval');
	    $TotalRecords=$recipe_mod->where($where)->count();
	    $pagesize=I('pagesize',10,'intval'); $pagesize>100?$pagesize=100:'';
	    $PageCount=ceil($TotalRecords/$pagesize);
	
	    $count = $recipe_mod->where($where)->count();
	    C('VAR_PAGE','pageindex');  //设置分页参数，默认为p
	    //$page = new \Think\Page($count,$pagesize);
	    $list=$recipe_mod->where($where)
	    ->Field('id,cate_id,title,photo,cutline,labels,view_num,ctime,suitable_person,taboo_person,cautious_person,cooking_pic,content')
	    //->limit($page->firstRow.','.$page->listRows)
	    ->limit(0,$pagesize)
	    ->order('id asc')
	    ->select();
        
	    foreach ($list as $key =>$val){
	        $list[$key]['cate_name']=$recipecate_mod->getFieldBy($val['cate_id'],'cate_name');
	        $list[$key]['h5']= U('h5/recipedetail',array('id'=>$val['id']),'html',true);
	    }
	
	    //传入用户user_id时，检测是否已经收藏
	    $list = $this->check_collect($list, 'id', 'recipe');
	
	    $data=array('PageIndex'=>$pageindex,'PageCount'=>$PageCount,'TotalRecords'=>$TotalRecords,'data'=>$list);
	    $this->toJson('请求成功.',1,$data);
	}
	
	/** 百科类别 */
	function recipecate(){
	    $recipecate_mod = D('Admin/Recipecate');
	    $where =array(
	        'if_show'=>1,
	    );
	    $cate_id=I('cate_id','','intval');
	    if($cate_id){
	        $where['cate_id'] = $cate_id;
	    }
	    // 	    $list=$recipecate_mod->where($where)
	    // 	    ->order('sort_order desc,cate_id desc')
	    // 	    ->select();
	    $list=$recipecate_mod->get_category($cate_id,true);
	    // 	    foreach ($list as $key => $val){
	    // 	        $list[$key]['photo'] = dealImg($list[$key]['photo']);
	    // 	    }
	    $this->toJson('请求成功.',1,$list);
	}
	
	/** 百科详情 */
	function recipedetail(){
	    $id=I('id',0,'intval');
	    if(empty($id)) $this->toJson('百科id参数缺少.');
	    $list = M('Recipe')->where(array('id'=>$id))->field('id,cate_id,title,cutline,view_num,content,ctime')->find();
	    $this->toJson('请求成功.',1,$list);
	}

	/** 孕期百科app是否弹窗  */
	function wiki_tips_show(){
	    $list = M('config')->field('title,content,wiki_tips_show,wiki_tips_url')->find(1);
	    $this->toJson('请求成功.',1,$list);
	}
	
}





?>