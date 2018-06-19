<?php
/**
 * 产品控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Admin\Controller;
use Think\Controller;
class JdgoodsController extends BackendController{
    var $_goods_mod = null; 
    var $upload_config = array(); //图片上传配置
    var $thumb_size = array();
    var $uploads = array(); //已经上传的文件
    var $_article_goods_mod=null;
    var $_brand_mod = null;
    function __construct(){
        parent::__construct();
        $this->JdgoodsController();
    }
    function JdgoodsController(){
        $this->_goods_mod = D('JdGoods'); 
        $this->_brand_mod = D('Brand');  
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
            $map['goodsName'] = array('like','%'.$keywords.'%');  
            $map['shopId'] = array('like','%'.$keywords.'%');  
            $map['_logic'] = 'or'; 
            $where['_complex'] = $map; 
        }
        
        $brand_name = I('brand_name','','trim');  
        if(!empty($brand_name)){
            $where['brand_name'] = array('like','%'.$brand_name.'%');   
        }  
        
        //京东产品ID
        $skuId=I('skuId','','trim');
        if($skuId){
            $where['skuId'] = array('eq',$skuId);
        } 
        
        //下架区
        $is_on_sale=I('is_on_sale','','trim');
        if($is_on_sale=='0'){
            $where['is_on_sale'] = 0;
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
 			}elseif($isSel=='zeroTkRate'){
 				$where['tk_rate']=0;
 			}elseif($isSel=='zeroTkRateOnSale'){
 				$where['tk_rate']=0;
 				$where['is_on_sale']=1;
 			}elseif($isSel=='zeroTkRateNotOnSale'){
 				$where['tk_rate']=0;
 				$where['is_on_sale']=0;
 			}elseif($isSel=='OnSale'){ 
 				$where['is_on_sale']=1;
 			}elseif($isSel=='NotOnSale'){ 
 				$where['is_on_sale']=0;
 			}
 		}	
 			
	        $count = $this->_goods_mod    
	        	->where($where)
	        	->count();	        	
	        $page = new \Think\Page($count,20);
	        $page->parameter=array( 
				'keywords'=>($keywords),
				'skuId'=>$skuId,
	        	'isSel' => I('isSel'), 
	            'is_on_sale'=>I('is_on_sale'),
				); 	  
	        
	        $goods = $this->_goods_mod
	        ->where($where)
	        ->order('ctime desc')
	        ->limit($page->firstRow.','.$page->listRows)
	        ->select();
	         
 		
        $this->assign('goods',$goods);
        $this->assign('page',$page->show());
        $this->assign('totalRecord',$count);
        $this->display('./jdgoods.index');
    }
    
    
    /** 添加产品 */
    function add(){
        if(!IS_POST){
            //$gcategory = $this->_get_gcategory(); //获取产品分类 
            $this->display('./jdgoods.form');
        }else{ 
           $brand_id = I('brand_id',0,'intval');
           $skuId = I('skuId','','trim');
           $data = array( 
                'goodsName' => I('goodsName','','trim'),
                'skuId' => $skuId,
                'open_iid' => I('open_iid','','trim'),
                'commisionRatioPc' => I('commisionRatioPc','','trim'),
            	'commisionRatioWl' => I('commisionRatioWl','','trim'),
           		'unitPrice' => I('unitPrice','','trim'), 
                'wlUnitPrice' => I('wlUnitPrice','','trim'), 
            	'materialUrl' => I('materialUrl','','trim'),
            	'skuId' => I('skuId','','trim'),
            	'imgUrl' => I('imgUrl','','trim'),
            	'imgUrl2' => I('imgUrl2','','trim'),
            	'imgUrl3' => I('imgUrl3','','trim'),
            	'imgUrl4' => I('imgUrl4','','trim'),
            	'imgUrl5' => I('imgUrl5','','trim'),
           		'shopId' => I('shopId','','trim'), 
                'ctime' => gmtime() , 
            );  

            
           if(empty($data['unitPrice'])){
               $this->error('PC端佣金不正确.');
           }
           if(empty($data['wlUnitPrice'])){
               $this->error('移动端价格不正确.');
           }
            
            //勾选交换主图
            $default_img=I('default_img','','trim');
            for($i=2;$i<=5;$i++){
            	if($default_img==$i){
            		$temp=$data['imgUrl'];
            		$data['imgUrl']=$data['imgUrl'.$i];
            		$data['imgUrl'.$i]=$temp;
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
            
            
//             //添加商品订阅
//             $goods = new TbGoodsController();
//             $res = $goods->subscribe($skuId);
//             if ($res->result->result_list->result_meta->code =='0' || $res->result->result_list->result_meta->msg =='成功'){
//                 $data['is_subscribe'] = 1;
//             }else{
//                 D('')->rollback();
//                 $this->error('百川商品消息失败.');
//             }             
            
            //提交
            if(!$goods_id=$this->_goods_mod->add($data)){
                D('')->rollback();
                $this->clear_images();
                $this->error('产品基本信息添加失败');
                return;
            }
  
            //关联文章处理
            $articleStr = I('article_ids','','trim');
            $this->article_goods($articleStr, $goods_id);
  			//$data['article_ids'] = $articleStr;  
  			
            //关联场景处理
            $sceneStr = I('scene_ids','','trim');
            $this->scene_goods($sceneStr, $goods_id);
            //$data['scene_ids'] = $sceneStr;   

            
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
            $this->display('./jdgoods.edit');
        }else{ 
           $brand_id = I('brand_id',0,'intval');

           $goods_desc = I('goods_desc');
           if(empty($goods_desc)){
           		$this->error('产品描述不能为空.');
           }
            $skuId = I('skuId','','trim');
            $data = array(
                'goods_id' => $goods_id,
                'goodsName' => I('goodsName','','trim'),
                'skuId' => $skuId, 
                'commisionRatioPc' => I('commisionRatioPc','','trim'),
            	'commisionRatioWl' => I('commisionRatioWl','','trim'),
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
            	'imgUrl' => I('imgUrl','','trim'),
            	'imgUrl2' => I('imgUrl2','','trim'),
            	'imgUrl3' => I('imgUrl3','','trim'),
            	'imgUrl4' => I('imgUrl4','','trim'),
            	'imgUrl5' => I('imgUrl5','','trim'),
            	'shop_name' => I('shop_name','','trim'),
           		'seller_type' => I('seller_type','','trim'),
           		'seller_nick' => I('seller_nick','','trim'),
                'last_update' => gmtime(), 
            	'brand_id' => $brand_id, 
            );  
            
           if(empty($data['commisionRatioWl'])){
               $this->error('折后价不正确.');
           } 
           
           if(empty($skuId)){
               $this->error('淘宝ID不能为空.');
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
            		$temp=$data['imgUrl'];
            		$data['imgUrl']=$data['imgUrl'.$i];
            		$data['imgUrl'.$i]=$temp;
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
            
            
            //添加商品订阅
            $goods = new TbGoodsController();
            $res = $goods->subscribe($skuId);
            if ($res->result->result_list->result_meta->code =='0' || $res->result->result_list->result_meta->msg =='成功'){
                $data['is_subscribe'] = 1;
            }else{
                D('')->rollback();
                $this->error('百川商品消息失败.');
            }
            
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
    
    /** 采集京东商品 */
    function getGoodsInfo(){ 
        $jdsdk = new \Org\Jd\JdSdk();
        $skuId=I('skuId');
        echo $jdsdk->getUnionGoods($skuId)->getpromotioninfo_result;
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
            $brands = $_brand_mod->where("if_show=1")->order('sort_order ASC,brand_id DESC')->select();
            if(!empty($brands)) S('brands',$brands,0);    
        }
        return $brands;
    }
     
    
    /** 产品发布失败，清空所有上传的图片 */
    function clear_images(){
        if(!empty($this->uploads)){
            foreach($this->uploads as $key => $file){
                @unlink($file);
            }
        }
    } 
    

    /** 搜索提示框 */
    function searchAuto(){
        $keywords=I('keywords','','trim');
        $where['_string'] = '(a.goodsName like "%'.$keywords.'%") or ( a.goods_desc like "%'.$keywords.'%") ';
        if(strstr($keywords,' ')){
            $keywords=str_replace(' ', '%', $keywords);
            $subkeywords=explode('%', $keywords);
            $subkeywords=array_reverse($subkeywords);
            $subkeywords=join('%',$subkeywords);
            $where['_string'] = '(a.goodsName like "%'.$keywords.'%") or ( a.goods_desc like "%'.$keywords.'%") or (concat(a.goodsName,a.goods_desc) like "%'.$subkeywords.'%") or (concat(a.goods_desc,a.goodsName) like "%'.$subkeywords.'%") ';
        }
        $page = new \Think\Page($count,20);
        $list = $this->_goods_mod->alias('a')->field('a.goodsName')
        ->where($where)
        ->limit($page->firstRow.','.$page->listRows)
        ->order('goods_id DESC')->select();
        if(empty($list)){
            echo '0';
            exit;
        }
        echo '<ul>';
        foreach ($list as $key => $val){
            echo '<li><a href="'.U("index",array('keywords'=>($val['goodsName']))).'">'.$val['goodsName'].'</a></li>';
        }
        echo '<li><a href="javascript:;" onclick="$(this).parent().parent().parent().fadeOut(100)">关闭</a></li>';
        echo '</ul>';
    }
    
    
   
    /** 嵌入框内选择商品 */
    function sel_goods_ad(){ 
        $where = array();
        //产品名称
        $goodsName=I('goodsName','','trim');
        $goodsName=urldecode($goodsName);
        if($_GET['goodsName']){
            $where['goodsName'] = array('like','%'.$goodsName.'%'); 
        }
        //淘宝ID
        $skuId=I('skuId','','trim');
        if($_GET['skuId']){
            $where['skuId'] = array('like','%'.$skuId.'%');
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
        	'goodsName'=>$goodsName,
        	'skuId'=>$skuId,
        	'article_id'=>$article_id,
        	'action' => 'edit'
        );
        $goods = $this->_goods_mod->where($where)->order('goods_id DESC')
                 ->limit($page->firstRow.','.$page->listRows)
                 ->select();
        $article_goods_mod=$this->_article_goods_mod;
        foreach ($goods as $key => $val){
        	$map=array('article_id'=>$article_id,goods_id=>$val['goods_id']);
        	$goods[$key]['ordernum']=$article_goods_mod->where($map)->getField('ordernum'); 
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
    	$ordernum=I('ordernum',0);
    	$data=array('ordernum'=>$ordernum);
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
    	$ordernum=I('ordernum',0);
    	$data=array('ordernum'=>$ordernum);
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
        if($_GET['goodsName']){
            $where['goodsName'] = array('like','%'.I('goodsName','','trim').'%'); 
        }
        //产品牌号
        if($_GET['skuId']){
            $where['skuId'] = array('like','%'.I('skuId','','trim').'%');
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
         
        $gcategory = $this->_get_gcategory(); //获取产品分类
//         $brands = $this->_get_brand(); //获取产品品牌
//         $this->assign('brands',$brands);
        $this->assign('gcategory',$gcategory);
        $this->assign('goods',$goods);
        $this->assign('page',$page->show());
        $this->display('./goods.ordersel');
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
    	$skuId=I('skuId');
    	if(empty($skuId)) return 0;
    	$where=array('skuId'=>$skuId);
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