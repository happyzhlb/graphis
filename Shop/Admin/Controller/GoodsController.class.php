<?php
/**
 * 产品控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class GoodsController extends BackendController{
    var $_goods_mod = null;
    var $_goods_gallery_mod = null;
    var $_goods_specs_mod = null;
    var $_brand_mod = null; 
    var $_gcategory_goods = null;
    var $upload_config = array(); //图片上传配置
    var $thumb_size = array();
    var $uploads = array(); //已经上传的文件
    var $_article_goods_mod=null;
    function __construct(){
        parent::__construct();
        $this->GoodsController();
    }
    function GoodsController(){
        $this->_goods_mod = D('Goods');
        $this->_goods_gallery_mod = D('GoodsGallery');
        $this->_goods_specs_mod = D('GoodsSpecs');
        $this->_brand_mod = D('Brand'); 
        $this->_gcategory_goods = M('GcategoryGoods');
        $this->_article_goods_mod = M('article_goods');
        $this->upload_config = array( //图片上传设置
            'maxSize' => 1024*1024, //最大支持上传1M的图片
            'exts' => 'jpg,jpeg,png,bmp',  //图片支持类型
            'savePath' => 'goods/'
        );
        $this->thumb_size = array(
            'middle_with' => 300,
            'middle_height' => 300,
            'small_with' => 140,
            'small_height' => 140
        );
    }
    
    /** 产品列表 */
    function index(){  
        $where = array();
        //产品名称
        $keywords = trim(I('keywords','','urldecode'));
		$keywords=str_replace('+','%', $keywords);
		$keywords=str_replace(' ','%', $keywords);
        $this->assign('keywords',str_replace('+', ' ', str_replace('%',' ',$keywords)));
		$order='goods_id desc';
		
		//品牌 
		$this->assign('brand',$this->_brand_mod->getBrandList());
		 
        if(!empty($keywords)){
            $map['goods_name'] = array('like','%'.$keywords.'%');  
            $map['shop_name'] = array('like','%'.$keywords.'%'); 
            $map['seller_nick'] = array('like','%'.$keywords.'%'); 
            $map['_logic'] = 'or'; 
            $where['_complex'] = $map; 
        }
        
        $brand_name = I('brand_name','','trim');  
        if(!empty($brand_name)){
            $where['brand_name'] = array('like','%'.$brand_name.'%');   
        }  
        
        //淘宝产品ID
        $outer_id=I('outer_id','','trim');
        if($outer_id){
            $where['outer_id'] = array('eq',$outer_id);
        }
        
        //下架区
        $is_on_sale=I('is_on_sale','','trim');
        if($is_on_sale=='0'){
            $where['is_on_sale'] = 0;
        }
        //价格待修改区
        $pending_modify=I('pending_modify','','trim');
        if($pending_modify=='1'){
            $where['is_price_diff'] = 1;
			$order='check_price_time desc';
        }
        
        //搜索未被关联商品
        $isSel = I('isSel','','trim');
 		if($isSel<>'all'){
 			if($isSel=='noRelatedArticle'){
 				$where['article_ids']=array('EXP',"is NULL or article_ids=''");
 			}elseif($isSel=='hasRelatedArticle'){
 				$where['article_ids']=array('neq',""); 
 			}elseif($isSel=='noRelatedBrand'){
 				$where['brand_id']=array('EXP',"is NULL or brand_id='' or brand_name=''"); 
 			}elseif($isSel=='hasRelatedBrand'){
 				$where['brand_id']=array('neq',"");
 			}
 			
 			
	        $count = $this->_goods_mod    
	        	->where($where)
	        	->count();	        	
	        $page = new \Think\Page($count,20);
	        $page->parameter=array( 
				'keywords'=>($keywords),
				'outer_id'=>$outer_id,
	        	'isSel' => I('isSel')
				); 	        
	        $goods = $this->_goods_mod->field('goods_id,goods_name,brand_json,tk_rate,goods_img,market_price,price,click_count,is_on_sale,shop_name,seller_type,seller_nick,goods_img,outer_id')
	        	->where($where)
	        	->order('goods_id desc')
	        	->limit($page->firstRow.','.$page->listRows) 
	        	->select();
 		}elseif($outer_id && empty($keywords) ){ // 显示单个商品关联列表
	        $count = $this->_goods_mod->where($where)->count();   
	        $page = new \Think\Page($count,20);
	        $page->parameter=array( 
				'keywords'=>($keywords),
				'outer_id'=>$outer_id
				);  
	        $goods = $this->_goods_mod->join('as g join __ARTICLE_GOODS__ as ag on g.goods_id=ag.goods_id')
	        		->join('__ARTICLE__ a on a.article_id=ag.article_id') 
	        		->where($where)->order('g.goods_id desc')
	        		->field('g.goods_id,goods_name,brand_json,tk_rate,goods_img,market_price,price,click_count,is_on_sale,shop_name,seller_type,seller_nick,goods_img,outer_id,a.article_id,a.title')
	                ->limit($page->firstRow.','.$page->listRows)
	                ->select();         
 		}else{ // 商品列表
	        $count = $this->_goods_mod->where($where)->count();   
	        $page = new \Think\Page($count,20);
	        $page->parameter=array( 
				'keywords'=>($keywords),
				'outer_id'=>$outer_id,
	        	'pending_modify'=>$pending_modify,
	        	'is_on_sale'=>$is_on_sale
				);  
	        $goods = $this->_goods_mod->field('goods_id,goods_name,brand_json,tk_rate,goods_img,market_price,price,click_count,is_on_sale,shop_name,seller_type,seller_nick,goods_img,outer_id')->where($where)->order($order)
	                 ->limit($page->firstRow.','.$page->listRows)
	                 ->order('goods_id desc')
	                 ->select();	                 
 		}       
 		#echo M()->getLastsql();
        $this->assign('goods',$goods);
        $this->assign('page',$page->show());
        $this->assign('totalRecord',$count);
        $this->display('./goods.index');
    }
    
    
    /** 搜索提示框 */
    function searchAuto(){
            $keywords=I('keywords','','trim');
            $where['_string'] = '(a.goods_name like "%'.$keywords.'%") or ( a.goods_desc like "%'.$keywords.'%") ';
            if(strstr($keywords,' ')){
	            $keywords=str_replace(' ', '%', $keywords);
	            $subkeywords=explode('%', $keywords);
	            $subkeywords=array_reverse($subkeywords);
	            $subkeywords=join('%',$subkeywords);
	            $where['_string'] = '(a.goods_name like "%'.$keywords.'%") or ( a.goods_desc like "%'.$keywords.'%") or (concat(a.goods_name,a.goods_desc) like "%'.$subkeywords.'%") or (concat(a.goods_desc,a.goods_name) like "%'.$subkeywords.'%") ';
            }
        $page = new \Think\Page($count,20);
        $list = $this->_goods_mod->alias('a')->field('a.goods_name')
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('goods_id DESC')->select();
        if(empty($list)){
        	echo '0';
        	exit;
        }
        echo '<ul>';       
        foreach ($list as $key => $val){ 
	    	echo '<li><a href="'.U("index",array('keywords'=>($val['goods_name']))).'">'.$val['goods_name'].'</a></li>'; 
        }
        echo '<li><a href="javascript:;" onclick="$(this).parent().parent().parent().fadeOut(100)">关闭</a></li>';
    	echo '</ul>';	
    }
    
    /** 添加产品 */
    function add(){
        if(!IS_POST){
            //$gcategory = $this->_get_gcategory(); //获取产品分类 
            $this->display('./goods.form');
        }else{ 
           $brand_id = I('brand_id',0,'intval');

           $data = array( 
                'goods_name' => I('goods_name','','trim'),
                'outer_id' => I('outer_id','','trim'),
                'bid' => I('bid','','intval'),
                'market_price' => I('market_price','','trim'),
            	'price' => I('price','','trim'),
           		'tk_rate' => I('tk_rate','','trim'),
                'goods_sub_desc' => trim(I('goods_sub_desc')),
                'goods_desc' => I('goods_desc'),
                'is_on_sale' => I('is_on_sale','','intval'),
                'is_new' => I('is_new','','intval'),
                'is_hot' => I('is_hot','','intval'),
                'is_promote' => I('is_promote','','intval'), 
                'sort_order' => I('sort_order','','trim'),
            	'click_count' => I('click_count','','trim'),
            	'article_id' => I('article_id','','trim'),
            	'wapDesc' => I('wapDesc','','trim'),
            	'wapImg' => I('wapImg','','trim'),
            	'goods_img' => I('goods_img','','trim'),
            	'goods_img2' => I('goods_img2','','trim'),
            	'goods_img3' => I('goods_img3','','trim'),
            	'goods_img4' => I('goods_img4','','trim'),
            	'goods_img5' => I('goods_img5','','trim'),
           		'shop_name' => I('shop_name','','trim'),
           		'seller_type' => I('seller_type','','trim'),
           		'seller_nick' => I('seller_nick','','trim'),
                'add_time' => gmtime() ,
           		'brand_id' => $brand_id
            );  

           if(empty($data['price'])){
               $this->error('折后价不正确.');
           }
           
           if(empty($brand_id)){
           //		$this->error('请选择品牌.');
           }else{
	            $brand = M('brand')->field('brand_id,brand_name,blogo,cate_id')->where(array('brand_id'=>$brand_id))->find();
           	 	if(!$brand){
	            	$this->error('品牌不存在.');
	            }else{ //更新关联品牌json
	            	$data['brand_json'] = json_encode($brand);
	            	$data['brand_name'] = $brand['brand_name']; 
	            }
           }
           
            //勾选交换主图
            $default_img=I('default_img','','trim');
            for($i=2;$i<=5;$i++){
            	if($default_img==$i){
            		$temp=$data['goods_img'];
            		$data['goods_img']=$data['goods_img'.$i];
            		$data['goods_img'.$i]=$temp;
            	}
            } 
            
            //详情页优先选编辑框图片
            if(!empty($data['editImg'])){
            	$imgstr=getAllImg($data['editImg']);  
            	$imgstr = implode(',', $imgstr);
	            $data['wapImg'] = $imgstr;
            }else{
	            //如果编辑框没有，把WapDesc转化成wapImg
	            $xml = simplexml_load_string($data['wapDesc']); 
	            if($xml){
		            $imgstr=(array)$xml;  
		            $imgstr = implode(',', $imgstr['img']);
		            $data['wapImg'] = $imgstr;
	            }else{ 
	            	$imgstr=getAllImg($data['wapDesc']);  
	            	$imgstr = implode(',', $imgstr);
		            $data['wapImg'] = $imgstr;
	            }  
            }
            
            
//            //把WapDesc转化成wapImg
//            $xml = simplexml_load_string($data['wapDesc']); 
//            if($xml){
//	            $imgstr=(array)$xml;  
//	            $imgstr = implode(',', $imgstr['img']);
//	            $data['wapImg'] = $imgstr;
//            }else{ 
//            	$imgstr=getAllImg($data['wapDesc']);
//            	$imgstr = implode(',', $imgstr);
//	            $data['wapImg'] = $imgstr;
//            } 
            
            D('')->startTrans();
            if(!$this->_goods_mod->create($data)){
                D('')->rollback();
                $this->clear_images();
                $this->error($this->_goods_mod->getError());
                return;
            }
            if(!$goods_id=$this->_goods_mod->add($data)){
                D('')->rollback();
                $this->clear_images();
                $this->error('产品基本信息添加失败');
                return;
            }
  
            //关联文章处理
            $articleStr = I('article_ids','','trim');
            $this->article_goods($articleStr, $goods_id);
  			$data['article_ids'] = $articleStr;  
  			
            //关联场景处理
            $sceneStr = I('scene_ids','','trim');
            $this->scene_goods($sceneStr, $goods_id);
            $data['scene_ids'] = $sceneStr;   
            
            
            D('')->commit();
            
            $this->success('产品发布成功',U('add')); 
            
        }
    }
    
    /** 编辑产品 */
    function edit(){
        $goods_id = I('id','','intval');
        $goods_info = $this->_goods_mod->find($goods_id);
        if(!$goods_info){
            $this->error('产品不存在');
            return;
        }
        
        $editImg = '';
        $imgarr = explode(',',$goods_info['wapImg']);
        foreach ($imgarr as $val){
            $editImg.= "<img src='{$val}' width='500' /><br>";
        } 
        
        if(!IS_POST){              
            $this->assign('goods',$goods_info);  
            $this->assign('article_ids',$this->getArticleStr($goods_id));
            $this->assign('scene_ids',$this->getSceneStr($goods_id));
            $this->assign('editImg',$editImg);
            $this->display('./goods.edit');
        }else{ 
           $brand_id = I('brand_id',0,'intval');

           $goods_desc = I('goods_desc');
           if(empty($goods_desc)){
           		$this->error('产品描述不能为空.');
           }
            $data = array(
                'goods_id' => $goods_id,
                'goods_name' => I('goods_name','','trim'),
                'outer_id' => I('outer_id','','trim'),
                'bid' => I('bid','','intval'),
                'market_price' => I('market_price','','trim'),
            	'price' => I('price','','trim'),
            	'tk_rate' => I('tk_rate','','trim'),
                'goods_sub_desc' => trim(I('goods_sub_desc')),
                'goods_desc' => $goods_desc,
                'is_on_sale' => I('is_on_sale','','intval'),
                'is_new' => I('is_new','','intval'),
                'is_hot' => I('is_hot','','intval'),
                'is_promote' => I('is_promote','','intval'), 
                'sort_order' => I('sort_order','','trim'),
            	'click_count' => I('click_count','','trim'), 
            	'wapDesc' => I('wapDesc','','trim'),
            	'editImg' => I('editImg','','trim'),
            	'goods_img' => I('goods_img','','trim'),
            	'goods_img2' => I('goods_img2','','trim'),
            	'goods_img3' => I('goods_img3','','trim'),
            	'goods_img4' => I('goods_img4','','trim'),
            	'goods_img5' => I('goods_img5','','trim'),
            	'shop_name' => I('shop_name','','trim'),
           		'seller_type' => I('seller_type','','trim'),
           		'seller_nick' => I('seller_nick','','trim'),
                'last_update' => gmtime(), 
            	'brand_id' => $brand_id, 
            );  
            
           if(empty($data['price'])){
               $this->error('折后价不正确.');
           } 
            
            
           if(empty($brand_id)){
           //	$this->error('请选择品牌.');
           }else{
	            $brand = M('brand')->field('brand_id,brand_name,blogo,cate_id')->where(array('brand_id'=>$brand_id))->find();
	            if(!$brand){
	            	$this->error('品牌不存在.');
	            }else{ //更新关联品牌json
	            	$data['brand_json'] = json_encode($brand);
	            	$data['brand_name'] = $brand['brand_name']; 
	            }
           }
           
            //勾选交换主图
            $default_img=I('default_img','','trim');
            for($i=2;$i<=5;$i++){
            	if($default_img==$i){
            		$temp=$data['goods_img'];
            		$data['goods_img']=$data['goods_img'.$i];
            		$data['goods_img'.$i]=$temp;
            	}
            } 
           
            //详情页优先选编辑框图片
            if(!empty($data['editImg'])){
            	$imgstr=getAllImg($data['editImg']);  
            	$imgstr = implode(',', $imgstr);
	            $data['wapImg'] = $imgstr;
            }else{
	            //如果编辑框没有，把WapDesc转化成wapImg
	            $xml = simplexml_load_string($data['wapDesc']); 
	            if($xml){
		            $imgstr=(array)$xml;  
		            $imgstr = implode(',', $imgstr['img']);
		            $data['wapImg'] = $imgstr;
	            }else{ 
	            	$imgstr=getAllImg($data['wapDesc']);  
	            	$imgstr = implode(',', $imgstr);
		            $data['wapImg'] = $imgstr;
	            }  
            } 
            
            D('')->startTrans();
            
            //关联文章处理
            $articleStr = I('article_ids','','trim');
            $this->article_goods($articleStr, $goods_id);
            $data['article_ids'] = $articleStr;
            
            //关联场景处理
            $sceneStr = I('scene_ids','','trim');
            $this->scene_goods($sceneStr, $goods_id);
            $data['scene_ids'] = $sceneStr;            
            
            
            if(!$this->_goods_mod->create($data)){
                D('')->rollback();
                $this->clear_images();
                $this->error($this->_goods_mod->getError());
                return;
            }  
            if(!$this->_goods_mod->save($data)){
                D('')->rollback();
                $this->clear_images();
                $this->error('产品基本信息修改失败');
                return;
            } 

  
            D('')->commit();
            
            $referer=I('referer',U('index'));
            
            $this->success('产品编辑成功',$referer);  //U('index')
        }
    }
    
    //把逗号分隔的图片字符串转成<img />标记
    protected function strToImg($wapImg,$width=500){
    	$editImg='';
        $imgarr = explode(',',$wapImg);
        foreach ($imgarr as $val){
            $editImg.= "<img src='{$val}' width='{$width}' /><br>";
        } 
        return $editImg;
    }
    
    public function ajaxWapImg(){
    	$wapDesc = I('wapDesc','','htmlspecialchars_decode'); //var_dump($wapDesc);
        //如果编辑框没有，把WapDesc转化成wapImg
        $xml = simplexml_load_string($wapDesc); //var_dump($xml);
        if($xml){
	            $imgstr=(array)$xml;  
	            $imgstr = implode(',', $imgstr['img']); 
        }else{ 
            	$imgstr=getAllImg($wapDesc);    
            	$imgstr = implode(',', $imgstr); 
        }   
    	echo $this->strToImg($imgstr);
    }
    
    
    /** 删除产品 */
    function drop(){
        $goods_id = I('id','','trim');
        if(!$goods_id){
            $this->error('参数有误，请刷新后重试');
            return;
        }
        if(strpos($goods_id,','))
            $goods_id = explode(',',$goods_id);
        if(is_array($goods_id)){
            $where['goods_id'] = array('in',$goods_id);
        }else{
            $where['goods_id'] = $goods_id;
        }
        
        //检测产品是否存在
        $goods = $this->_goods_mod->where($where)->select();
        if(!$goods){
            $this->error('产品不存在');
            return;
        }
        D('')->startTrans();
        //删除产品基本信息 
        if(!$this->_goods_mod->where($where)->delete()){
            D('')->rollback();
            $this->error('产品基本信息删除失败');
            return;
        }
        //删除产品跟文章的关联 
        if(false===M('article_goods')->where($where)->delete()){
            D('')->rollback();
            $this->error('产品跟文章的关联删除失败.');
            return;
        }        
        D('')->commit();
        $this->clear_images();
        $this->success('产品删除成功',U('/Admin/Goods'));
        
    }
    
    /** 异步删除规格属性 */
    function ajax_drop_specs(){
        $spec_id = I('id','','intval');
        $specs = $this->_goods_specs_mod->find($spec_id);
        if(!$specs){
            $this->error('规格不存在');
            return;
        }
        D('')->startTrans();
        if(!$this->_goods_specs_mod->delete($spec_id)){
            D('')->rollback();
            $this->error('规格删除失败');
            return;
        }
        //删除所有购物车中，该规格的数据
        $_cart_mod = D('Home/Cart');
        $map['spec_id'] = $spec_id;
        $carts = $_cart_mod->where($map)->select();
        if($carts != false){ //购物车不位空，则删除
            if(!$_cart_mod->where($map)->delete()){
                D('')->rollback();
                $this->error('同步购物车数据失败');
                return;
            }
        }
        D('')->commit();
        $this->success('规格删除成功',U('/Admin/Goods'));
    }
    
    /** 异步删除阶梯价格 */
    function ajax_drop_price(){
        $price_id = I('id','','intval');
        $price = $this->_goods_price_mod->find($price_id);
        if(!$price){
            $this->error('阶梯价格不存在');
            return;
        }
        if(!$this->_goods_price_mod->delete($price_id)){
            $this->error('阶梯价格删除失败');
            return;
        }
        $this->success('阶梯价格删除成功',U('/Admin/Goods'));
    }
    
    /** 异步删除COA文档 */
    function ajax_drop_coa(){
        $cd = I('id','','intval');
        $coa = $this->_goods_coa_mod->find($cid);
        if(!$coa){
            $this->error('COA记录不存在');
            return;
        }
        if(!$this->_goods_coa_mod->delete($cid)){
            $this->error('COA删除失败');
            return;
        }
        @unlink($coa['file']);
        $this->success('COA删除成功',U('/Admin/Goods'));
    }    
    
    /** 异步修改产品属性 */
    function ajax_request(){
        $goods_id = I('goods_id',0,'intval');
        $field = I('field','','trim');
        $where['goods_id'] = $goods_id;
        $attr = $this->_goods_mod->field($field)->where($where)->find();
        if(!$attr){
            $this->error('产品不存在');
            return;
        }
        $attr = current($attr);
        $nattr = $attr? 0:1;
        if(!$this->_goods_mod->where($where)->save(array($field=>$nattr))){
            $this->error('产品属性修改失败');
            return;
        }
        $this->success('产品属性修改成功',U('/Admin/Goods'));
    }
    
    /** 读取产品分类 */
    function _get_gcategory(){
        $gcategory = S('gcategory');
        if(!$gcategory){
            $_gcategory_mod = D('Gcategory');
            $gcategory = $_gcategory_mod->get_category(0,true,1);
            if(!empty($gcategory)) S('gcategory',$gcategory,0);
        }
        return $gcategory;
    }
    
    /** 读取品牌 */
    function _get_brand(){
        $brands = S('brands');
        if(!$brands){
            $_brand_mod = M('Brand');
            $brands = $_brand_mod->where("if_show=1")->order('sort_order ASC,bid DESC')->select();
            if(!empty($brands)) S('brands',$brands,0);    
        }
        return $brands;
    }
    
    /** 上传MSDS文档 */
    function _upload_msds($file){
        $upfile['msds'] = $file;
        $upconfig = array( //图片上传设置
            'maxSize' => 5 * 1024 * 1024, //最大支持上传500K的图片
            'exts' => 'pdf',  //图片支持类型
            'subName' => '',
            'savePath' => 'msds/'
        );
        $upload = new \Think\Upload($upconfig);
        if(!$file = $upload->upload($upfile)){
            $this->error($upload->getError());
            return;
        }
        return $upload->__get('rootPath').$file['msds']['savepath'].$file['msds']['savename'];
    }
    
    
    /** 产品发布失败，清空所有上传的图片 */
    function clear_images(){
        if(!empty($this->uploads)){
            foreach($this->uploads as $key => $file){
                @unlink($file);
            }
        }
    }
    
    /** 异步删除相册 */   
    function ajax_drop_gallery(){
        $gid = I('id','','intval');
        $gallery = $this->_goods_gallery_mod->find($gid);
        if(!$gallery){
            $this->error('相册图片不存在');
            return;
        }
        $this->uploads[] = $gallery['img_url'];
        $this->uploads[] = $gallery['img_thumb'];
        $this->uploads[] = $gallery['img_original'];
        if(!$this->_goods_gallery_mod->delete($gid)){
            $this->error();
            return;
        }
        $this->clear_images();
        $this->success('相册图片删除成功',U('/Admin/Goods'));
    }
    
    /** 导出excel */
    function export(){
        $goods_id = trim(I('id'));
        if(!$goods_id){
            $this->error('没有选择要导出的记录');
            return;
        }
        if(strpos($goods_id,','))
            $goods_id = explode(',',$goods_id);
        if(is_array($goods_id)){
            $where['g.goods_id'] = array('in',$goods_id);
        }else{
            $where['g.goods_id'] = $goods_id;
        }
        $goods_specs = $this->_goods_specs_mod->field('gs.*,g.goods_name,g.outer_id')
                       ->join(" as gs LEFT JOIN __GOODS__ as g ON g.goods_id = gs.goods_id")
                       ->where($where)->select();
        if(!$goods_specs){
            $this->error('你要导出的产品不存在，或者已经被删除了');
            return;
        }
        vendor('Phpexcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $filePath = 'Deringer Item Download Template.xls';
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($filePath)){						
	       $PHPReader = new \PHPExcel_Reader_Excel5();	
	       if(!$PHPReader->canRead($filePath)){						
		      echo '未发现Excel文件！';
		      return;
	       }
        }
        //不需要读取整个Excel文件而获取所有工作表数组的函数，感觉这个函数很有用，找了半天才找到
        //$sheetNames  = $PHPReader->listWorksheetNames($filePath);
 
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);

 
        //获取工作表的数目
        $sheetCount = $PHPExcel->getSheetCount();
 
        //选择第一个工作表
        $currentSheet = $PHPExcel->getSheet(0);
 
        //取得一共有多少列
        $allColumn = $currentSheet->getHighestColumn();
 
        //取得一共有多少行
        $allRow = $currentSheet->getHighestRow();
        $excel_title = array();
        //循环读取数据，默认编码是utf8，这里转换成gbk输出
        for($currentColumn = 'A';$currentColumn != $allColumn; $currentColumn++){
            $address = $currentColumn.'2';
            $excel_title[$currentColumn] = $currentSheet->getCell($address)->getValue();
        }
        
        $objPHPExcel->getProperties()->setCreator("Da")
                    ->setLastModifiedBy("Da")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX,generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");
            
         $objPHPExcel->setActiveSheetIndex(0);        
         $objPHPExcel->getActiveSheet(0)->setTitle('products');//标题   
         $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);//单元格宽度
         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('Arial');//设置字体
         $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);//设置字体大小
         foreach($excel_title as $key => $value){ //设置第一行
            $objPHPExcel->getActiveSheet(0)->setCellValue($key.'1',$value);   
         }
         
         //第二行开始内容填充
         foreach($goods_specs as $key => $spec){
            if(!$spec['spec_item']){
                $data['spec_item'] = $spec['spec_item'] = $spec['outer_id'].$spec['spec_id'];
                $data['spec_id'] = $spec['spec_id'];
                $this->_goods_specs_mod->save($data);          
            }
            $i = $key + 2;
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'. ($key + 2),'company code'); 
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($key + 2),$spec['spec_item']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($key + 2),$spec['goods_name'].'[batch:'.$spec['spec_batch'].'][pkg:'.$spec['spec_page'].']');
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($key + 2),'Y');
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($key + 2),'N');
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($key + 2),'N');
         }
         header('Content-Type: application/vnd.ms-excel');
         header('Content-Disposition: attachment;filename="okchem_products_'.date("YmdHis").'.xls"');
         header('Cache-Control: max-age=0');
         ob_clean();//关键
         flush();//关键
         $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
         $objWriter->save('php://output');
         
        //$objCalc = PHPExcel_Calculation::getInstance();
        //print_r($objCalc->listFunctionNames());
        //foreach($goods_specs as $key => $spec){
            
        //}
        //var_dump($goods_specs);
    }
    
    
   
    /** 嵌入框内选择商品 */
    function sel_goods(){ 
        $where = array();
        //产品名称
        $goods_name=I('goods_name','','trim');
        $goods_name=urldecode($goods_name);
        if($_GET['goods_name']){
            $where['goods_name'] = array('like','%'.$goods_name.'%'); 
        }
        //淘宝ID
        $outer_id=I('outer_id','','trim');
        if($_GET['outer_id']){
            $where['outer_id'] = array('like','%'.$outer_id.'%');
        }
        //是否勾选
        $isChecked = I('isChecked','all');
        
        //产品分类
        if($_GET['cate_id']){
            $_gcategory_mod = D('Gcategory');
            $cate_id = array();
            $_gcategory_mod->_get_children_cate_id($cate_id,intval($_GET['cate_id']));
            $_gcategory_goods_mod = M('GcategoryGoods');
            $map['cate_id'] = array('in',$cate_id);
            $gid = $_gcategory_goods_mod->distinct(true)->field('goods_id')->where($map)->select();
            $goods_id = array();
            if(is_array($gid)){
                foreach($gid as $key => $value){
                    $goods_id[] =$value['goods_id'];
                }
                $where['g.goods_id'] = array('in',$goods_id);
            }
        }
        
        //过滤已勾选的goods_ids
        $article_id=$_GET['article_id']; 
        if(!empty($article_id)){  
        	$where['ag.article_id']=$article_id;
        	$w=array('article_id'=>$article_id);
        	$goods_ids='';
            $goods_ids=M('article_goods')->where($w)->field('goods_id')->select();
            
            foreach ($goods_ids as $key =>$val){
            	$sel_goods[]=$val['goods_id'];
            }
            $goods_ids=join(',', $sel_goods);  
        }
        
        if($isChecked=='yes'){
        	$where['g.goods_id']=array('in',$goods_ids);
        }elseif($isChecked=='no' && !empty($goods_ids)){
        	$where['g.goods_id']=array('not in',$goods_ids);
        } 
        
        
        #$count = $this->_goods_mod->alias('g')->where($where)->count();  
        $count = $this->_goods_mod->distinct(true)->join('as g join __ARTICLE_GOODS__ ag on ag.goods_id=g.goods_id')->field('g.*,ag.article_id,ag.orderNum')->where($where)->count(); 
        #echo M()->getLastsql();
        $page = new \Think\Page($count,8);
        $page->parameter=array(
        	'isChecked'=>$isChecked,
        	'goods_name'=>$goods_name,
        	'outer_id'=>$outer_id,
        	'article_id'=>$article_id,
        	'action' => 'edit'
        );
        $goods = $this->_goods_mod->distinct(true)->join('as g join __ARTICLE_GOODS__ ag on ag.goods_id=g.goods_id')->field('g.*,ag.article_id,ag.orderNum')->where($where)->order('goods_id DESC')
                 ->limit($page->firstRow.','.$page->listRows)
                 ->order('ag.orderNum asc')
                 ->select();  #echo M()->getLastsql();
//        $article_goods_mod=$this->_article_goods_mod;
//        foreach ($goods as $key => $val){
//        	$map=array('article_id'=>$article_id,goods_id=>$val['goods_id']);
//        	$goods[$key]['orderNum']=$article_goods_mod->where($map)->getField('orderNum'); 
//        } 
        $gcategory = $this->_get_gcategory(); //获取产品分类
        $brands = $this->_get_brand(); //获取产品品牌
        $this->assign('brands',$brands);
        $this->assign('gcategory',$gcategory);
        $this->assign('goods',$goods);
        $this->assign('page',$page->show());
        if($_REQUEST['action']=='edit'){
        	$this->display('./goods.sel.edit');
        }else{
        	$this->display('./goods.sel');
        }
    }

    
   
    /** 嵌入框内选择商品 */
    function sel_goods_scene(){ 
        $where = array();
        //产品名称
        $goods_name=I('goods_name','','trim');
        $goods_name=urldecode($goods_name);
        if($_GET['goods_name']){
            $where['goods_name'] = array('like','%'.$goods_name.'%'); 
        }
        //淘宝ID
        $outer_id=I('outer_id','','trim');
        if($_GET['outer_id']){
            $where['outer_id'] = array('like','%'.$outer_id.'%');
        }
        //是否勾选
        $isChecked = I('isChecked','all');
        
        //产品分类
        if($_GET['cate_id']){
            $_gcategory_mod = D('Gcategory');
            $cate_id = array();
            $_gcategory_mod->_get_children_cate_id($cate_id,intval($_GET['cate_id']));
            $_gcategory_goods_mod = M('GcategoryGoods');
            $map['cate_id'] = array('in',$cate_id);
            $gid = $_gcategory_goods_mod->distinct(true)->field('goods_id')->where($map)->select();
            $goods_id = array();
            if(is_array($gid)){
                foreach($gid as $key => $value){
                    $goods_id[] =$value['goods_id'];
                }
                $where['g.goods_id'] = array('in',$goods_id);
            }
        }
        
        //过滤已勾选的goods_ids
        $scene_id=$_GET['scene_id']; 
        if(!empty($scene_id)){  
        	$where['ag.scene_id']=$scene_id;
        	$w=array('scene_id'=>$scene_id);
        	$goods_ids='';
            $goods_ids=M('scene_goods')->where($w)->field('goods_id')->select();
            
            foreach ($goods_ids as $key =>$val){
            	$sel_goods[]=$val['goods_id'];
            }
            $goods_ids=join(',', $sel_goods);  
        }
        
        if($isChecked=='yes'){
        	$where['g.goods_id']=array('in',$goods_ids);
        }elseif($isChecked=='no' && !empty($goods_ids)){
        	$where['g.goods_id']=array('not in',$goods_ids);
        } 
        
        
        #$count = $this->_goods_mod->alias('g')->where($where)->count();  
        $count = $this->_goods_mod->distinct(true)->join('as g join __SCENE_GOODS__ ag on ag.goods_id=g.goods_id')->field('g.*,ag.scene_id,ag.orderNum')->where($where)->count(); 
         
        $page = new \Think\Page($count,8);
        $page->parameter=array(
        	'isChecked'=>$isChecked,
        	'goods_name'=>$goods_name,
        	'outer_id'=>$outer_id,
        	'scene_id'=>$scene_id,
        	'action' => 'edit'
        );
        $goods = $this->_goods_mod->distinct(true)->join('as g join __SCENE_GOODS__ ag on ag.goods_id=g.goods_id')->field('g.*,ag.scene_id,ag.orderNum')->where($where)->order('goods_id DESC')
                 ->limit($page->firstRow.','.$page->listRows)
                 ->order('ag.orderNum asc')
                 ->select();  #echo M()->getLastsql();
//        $article_goods_mod=$this->_article_goods_mod;
//        foreach ($goods as $key => $val){
//        	$map=array('scene_id'=>$scene_id,goods_id=>$val['goods_id']);
//        	$goods[$key]['orderNum']=$article_goods_mod->where($map)->getField('orderNum'); 
//        } 
        $gcategory = $this->_get_gcategory(); //获取产品分类
        $brands = $this->_get_brand(); //获取产品品牌
        $this->assign('brands',$brands);
        $this->assign('gcategory',$gcategory);
        $this->assign('goods',$goods);
        $this->assign('page',$page->show());
        if($_REQUEST['action']=='edit'){
        	$this->display('./goods.sel.scene.edit');
        }else{
        	$this->display('./goods.sel.scene');
        }
    }
    
   
    /** 嵌入框内选择商品 */
    function sel_goods_ad(){ 
        $where = array();
        //产品名称
        $goods_name=I('goods_name','','trim');
        $goods_name=urldecode($goods_name);
        if($_GET['goods_name']){
            $where['goods_name'] = array('like','%'.$goods_name.'%'); 
        }
        //淘宝ID
        $outer_id=I('outer_id','','trim');
        if($_GET['outer_id']){
            $where['outer_id'] = array('like','%'.$outer_id.'%');
        }
        //是否勾选
        $isChecked = I('isChecked','all');
        
        //产品分类
        if($_GET['cate_id']){
            $_gcategory_mod = D('Gcategory');
            $cate_id = array();
            $_gcategory_mod->_get_children_cate_id($cate_id,intval($_GET['cate_id']));
            $_gcategory_goods_mod = M('GcategoryGoods');
            $map['cate_id'] = array('in',$cate_id);
            $gid = $_gcategory_goods_mod->distinct(true)->field('goods_id')->where($map)->select();
            $goods_id = array();
            if(is_array($gid)){
                foreach($gid as $key => $value){
                    $goods_id[] =$value['goods_id'];
                }
                $where['goods_id'] = array('in',$goods_id);
            }
        }
        
        //过滤已勾选的goods_ids
        $article_id=$_GET['article_id']; 
        if(!empty($article_id)){  
        	$w=array('article_id'=>$article_id);
        	$goods_ids='';
            $goods_ids=M('article_goods')->where($w)->field('goods_id')->select();
            
            foreach ($goods_ids as $key =>$val){
            	$sel_goods[]=$val['goods_id'];
            }
            $goods_ids=join(',', $sel_goods);  
        }
        
        if($isChecked=='yes'){
        	$where['goods_id']=array('in',$goods_ids);
        }elseif($isChecked=='no' && !empty($goods_ids)){
        	$where['goods_id']=array('not in',$goods_ids);
        } 
        
        
        $count = $this->_goods_mod->where($where)->count();  
        $page = new \Think\Page($count,8);
        $page->parameter=array(
        	'isChecked'=>$isChecked,
        	'goods_name'=>$goods_name,
        	'outer_id'=>$outer_id,
        	'article_id'=>$article_id,
        	'action' => 'edit'
        );
        $goods = $this->_goods_mod->where($where)->order('goods_id DESC')
                 ->limit($page->firstRow.','.$page->listRows)
                 ->select();
        $article_goods_mod=$this->_article_goods_mod;
        foreach ($goods as $key => $val){
        	$map=array('article_id'=>$article_id,goods_id=>$val['goods_id']);
        	$goods[$key]['orderNum']=$article_goods_mod->where($map)->getField('orderNum'); 
        } 
        $gcategory = $this->_get_gcategory(); //获取产品分类
        $brands = $this->_get_brand(); //获取产品品牌
        $this->assign('brands',$brands);
        $this->assign('gcategory',$gcategory);
        $this->assign('goods',$goods);
        $this->assign('page',$page->show()); 
        $this->display('./goods.sel.ad'); 
    }
    
    
    /** 文章/商品排序 */
    function ajaxOrderNum(){
    	$_mod=M('article_goods');
    	$article_id=I('article_id'); 
    	$goods_id=I('goods_id'); 
    	if(empty($article_id) or empty($goods_id)) $this->error('ID传值错误.');
    	$where=array(
    		'article_id'=>$article_id,
    		'goods_id'=>$goods_id 
    	);
    	$orderNum=I('orderNum',0);
    	$data=array('orderNum'=>$orderNum);
    	$find=$_mod->where($where)->find();
    	if($find){
    		$res=$_mod->where($where)->save($data); 
    	}else{
    		$res=$_mod->add(array_merge($where,$data)); 
    	}
    	$this->success('保存成功.'.$res);
    }
    
     
    /** 场景/商品排序 */
    function ajaxSceneOrderNum(){
    	$_mod=M('scene_goods');
    	$scene_id=I('scene_id'); 
    	$goods_id=I('goods_id'); 
    	if(empty($scene_id) or empty($goods_id)) $this->error('ID传值错误.');
    	$where=array(
    		'scene_id'=>$scene_id,
    		'goods_id'=>$goods_id 
    	);
    	$orderNum=I('orderNum',0);
    	$data=array('orderNum'=>$orderNum);
    	$find=$_mod->where($where)->find();
    	if($find){
    		$res=$_mod->where($where)->save($data); 
    	}else{
    		$res=$_mod->add(array_merge($where,$data)); 
    	}
    	$this->success('保存成功.'.$res);
    }   
    
    /** 后台新增订单商品选择器 */
    function order_goods_sel(){
       $where = array();
        //产品名称
        if($_GET['goods_name']){
            $where['goods_name'] = array('like','%'.I('goods_name','','trim').'%'); 
        }
        //产品牌号
        if($_GET['outer_id']){
            $where['outer_id'] = array('like','%'.I('outer_id','','trim').'%');
        }
        
        //产品分类
        if($_GET['cate_id']){
            $_gcategory_mod = D('Gcategory');
            $cate_id = array();
            $_gcategory_mod->_get_children_cate_id($cate_id,intval($_GET['cate_id']));
            $_gcategory_goods_mod = M('GcategoryGoods');
            $map['cate_id'] = array('in',$cate_id);
            $gid = $_gcategory_goods_mod->distinct(true)->field('goods_id')->where($map)->select();
            $goods_id = array();
            if(is_array($gid)){
                foreach($gid as $key => $value){
                    $goods_id[] =$value['goods_id'];
                }
                $where['goods_id'] = array('in',$goods_id);
            }
        }
        
        //产品品牌
        if($_GET['bid']){
            $where['bid'] = I('bid','','intval');
        }
        $count = $this->_goods_mod->where($where)->count();  #echo $this->_goods_mod->getLastsql();
        $page = new \Think\Page($count,10);
        $goods = $this->_goods_mod->where($where)->order('goods_id DESC')
                 ->limit($page->firstRow.','.$page->listRows)
                 ->select();
        //获取产品的规格信息
        if($goods != false){
            foreach($goods as $gk => $gv){
                $goods[$gk]['specs'] = M('GoodsSpecs')->where("goods_id='{$gv['goods_id']}'")->select();
            }
        }
        $gcategory = $this->_get_gcategory(); //获取产品分类
        $brands = $this->_get_brand(); //获取产品品牌
        $this->assign('brands',$brands);
        $this->assign('gcategory',$gcategory);
        $this->assign('goods',$goods);
        $this->assign('page',$page->show());
        $this->display('./goods.ordersel');
    }
    
    /** 异步获取规格信息 */
    function ajax_get_goods(){
        $specs_id = trim(I('id'));
        if(!$specs_id){
            $this->error('参数错误，请刷新后重试！');
            return false;
        }
        $where['spec_id'] = array('in',explode(',',$specs_id));
        $specs = $this->_goods_specs_mod->field('gs.*,g.goods_name,g.goods_thumb')
                 ->join(' as gs INNER JOIN __GOODS__ as g ON g.goods_id=gs.goods_id')
                 ->where($where)->select();
        if(!$specs){
            $this->error('产品不存在！');
            return false;
        }
        foreach($specs as $sk => $spec){
            $specs[$sk]['price'] = format_price($spec['price']);
        }
        $this->success($specs);
    }
    
    /** 淘宝API测试 */
    function getGoodsInfo(){ 
    	require_cache('./tbk_api/tbkapi_test1.php'); 
    }
    
    /** 上传文件 */
    function upload(){
    	$savePath='photo';
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
    
    /** 删除上传文件 by abiao  */
    function delFiles(){
    	$file=I('file'); 
    	if(!strstr($file, 'Uploads/')){
    		$this->error('文件路径不对.');
    	}
    	@$res=unlink($file);
    	if($res){
    	  	$this->success('删除成功.');
    	}else{
    		$this->success('删除失败.');
    	}
    }
    
    /** 读取商品对应的关联文章 */
    protected function getArticleStr($goods_id){
    	if(empty($goods_id)){
    		return false;
    	}
    	$where=array(
    		'goods_id' => $goods_id
    	);
    	$list=M('article_goods')->where($where)->field('article_id')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['article_id'] ;
    	}
    	$arr = join(',', $arr );
    	return $arr;
    }
    
    /** 处理商品对应的关联文章 */
    protected function article_goods($articleStr,$goods_id){ 
    	if(empty($articleStr) or empty($goods_id)) return ;
    	$_mod=M('article_goods');
    	$ids=explode(',',$articleStr); 
    	//$_mod->where(array('goods_id'=>$goods_id))->delete();
    	foreach ($ids as $key => $val){
    		$where=array(
    			'article_id'=>$val,
    			'goods_id' => $goods_id,
    		);
    		$find=$_mod->where($where)->find();
    		if(!$find){
    			$res=$_mod->add(array('article_id'=>$val,'goods_id'=>$goods_id));
    			if(!res){
    				D()->rollback();
    				$this->error('文章关联失败.');
    			}
    		}
    	}
    	$wheredel=array(
	    	'goods_id'=>$goods_id,
	    	'article_id'=>array('not in',$articleStr)
    	);
    	$_mod->where($wheredel)->delete(); 
    }


    /** 读取商品对应的关联文章 */
    protected function getSceneStr($goods_id){
    	if(empty($goods_id)){
    		return false;
    	}
    	$where=array(
    		'goods_id' => $goods_id
    	);
    	$list=M('scene_goods')->where($where)->field('scene_id')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['scene_id'] ;
    	}
    	$arr = join(',', $arr );
    	return $arr;
    }
    
    /** 处理商品对应的关联场景 */
    protected function scene_goods($sceneStr,$goods_id){ 
    	$_mod=M('scene_goods');
    	if(empty($sceneStr)) {
    		if(empty($goods_id)) return ;
    	 	//清除关联
    		$_mod->where(array('goods_id'=>$goods_id))->delete(); 
    		return;
    	}
    	
    	//添加关联商品
    	$ids=explode(',',$sceneStr);  
    	foreach ($ids as $key => $val){
    		$where=array(
    			'scene_id'=>$val,
    			'goods_id' => $goods_id,
    		);
    		$find=$_mod->where($where)->find();
    		if(!$find){
    			$res=$_mod->add(array('scene_id'=>$val,'goods_id'=>$goods_id));
    			if(!res){
    				D()->rollback();
    				$this->error('文章关联失败.');
    			}
    		}
    	}
    	//删除未关联商品
    	$wheredel=array(
	    	'goods_id'=>$goods_id,
	    	'scene_id'=>array('not in',$sceneStr)
    	);
    	$_mod->where($wheredel)->delete(); 
    }    
    
    /** 处理商品是否已经存在 */
    function is_exists(){
    	$outer_id=I('outer_id');
    	if(empty($outer_id)) return 0;
    	$where=array('outer_id'=>$outer_id);
    	if($goods=M('goods')->where($where)->find()){
    		$this->success($goods['goods_id']);
    	}else{
    		$this->error('该淘宝ID未被添加.');;
    	}
    } 
    
    /** 商品批量更新品牌 */
    function batUpdateBrand(){
    	$goods_ids = I('goods_ids');
    	$goods_ids = explode(',', $goods_ids);
    	$brand_id = I('brand_id',0,'intval');
    	if(empty($goods_ids)){
    		$this->error('请选择要修改的商品.');
    	}
    	if(empty($brand_id)){
    		$this->error('请选择品牌.');
    	}    	
    	
    	M()->startTrans();
    	foreach ($goods_ids as  $goods_id){
    		if(empty($goods_id)){
    			M()->rollback();
    			$this->error('商品goods_id错误:'.$goods_id);
    		} 
    		$brand = $this->_brand_mod->field('brand_id,brand_name,blogo,cate_id')
    			->where(array('brand_id'=>$brand_id))
    			->find();
    		if(empty($brand)){
    			M()->rollback();
    			$this->error('该品牌不存在.');
    		}
    		$this->_goods_mod->where(array('goods_id'=>$goods_id))->save(array('brand_id'=>$brand_id,'brand_name'=>$brand['brand_name'],'brand_json'=>json_encode($brand)));
    	} 
    	M()->commit();
    	$this->success('品牌批量处理成功.');
    }
    
    
    /** 商品批量更新专题 */
    function batUpdateArticle(){
    	$goods_ids = I('goods_ids');
    	$goods_ids = explode(',', $goods_ids);
    	$article_id = I('article_id',0,'intval');
    	if(empty($goods_ids)){
    		$this->error('请选择要修改的商品.');
    	}
    	if(empty($article_id)){
    		$this->error('请选择专题.');
    	}    	
    	$article_mod = M('article');
    	M()->startTrans();
    	foreach ($goods_ids as  $goods_id){
    		if(empty($goods_id)){
    			$article_mod->rollback();
    			$this->error('商品goods_id错误:'.$goods_id);
    		} 
    		$article = $article_mod->field('article_id')
    			->where(array('article_id'=>$article_id))
    			->find();
    		if(empty($article)){
    			$article_mod->rollback();
    			$this->error('该专题不存在.');
    		} 
    		
    		//添加到article_goods表
    		$find = $this->_article_goods_mod->where(array('article_id'=>$article_id,'goods_id'=>$goods_id))->find();
    		if(!$find){
    			if(!$this->_article_goods_mod->add(array('article_id'=>$article_id,'goods_id'=>$goods_id))){
    				$article_mod->rollback();
    				$this->error('专题关联商品添加失败.');
    			}
    		}
    		
    		//更新商品表对应的article_ids 
    		$article_ids = $this->_goods_mod->where(array('goods_id'=>$goods_id))->getField('article_ids');  
    		if(!empty($article_ids)){
    			$ids_arr = explode(',',$article_ids);
    			$ids_arr = array_merge($ids_arr,array($article_id)); 
    			$ids_arr = array_unique($ids_arr);
    			$article_ids = implode(',', $ids_arr);
    			
    		}else{
    			$article_ids = $article_id ;
    		}  	 
	    	$res =$this->_goods_mod
		    		->where(array('goods_id'=>$goods_id))
		    		->save(array('article_ids'=>$article_ids));    	
		    if(FALSE===$res){
		    	$article_mod->rollback();
		    	$this->error('专题批量处理失败.');
		    }	
    	}  
    	//提交事务
    	M()->commit();
    	$this->success('专题批量处理成功.');
    }
        
}


?>