<?php
/**
 * 产品详情控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Page;

use Think\Controllrt;
class GoodsController extends FrontendController{
    var $_goods_mod = null;
    var $_goods_gallery_mod = null;
    var $_goods_specs_mod = null;
    var $_comments_mod = null;
    function __construct(){
        parent::__construct();
        $this->GoodsController();
    }
    function GoodsController(){
        $this->_goods_mod = D('Admin/Goods');
        $this->_goods_gallery_mod = D('Admin/GoodsGallery');
        $this->_goods_specs_mod = D('Admin/GoodsSpecs');
        $this->_comments_mod = D('Comments');
    }
    
    /** 产品详细页 */
    function index(){
        $goods_id = I('id','','intval');
        $detail['goods'] = $this->_goods_mod->find($goods_id);
        if(!$detail['goods']){
            $this->error('The product does not exist, or has been deleted.');
            return;
        }
        if(!$detail['goods']['is_on_sale']){
            $this->error('The product is temporarily unavailable.');
            return;
        }
        $referer=U('index','id='.$goods_id);
    	$this->assign('referer',$referer);
        
        //发货地址
        $_region_mod = D('Admin/Region');
        $state = current($_region_mod->field('region_name')->where("region_id='".C('state')."'")->find());
        $detail['shipping_address'] = C('city').' '.$state;
        
        //判断是否已经收藏
        $detail['is_collect'] = $this->check_collect_goods($goods_id);
        
        //产品相册
        $detail['gallery'] = $this->_goods_gallery_mod->where("goods_id='{$goods_id}'")
                             ->order('sort_order ASC')->select();
        //产品规格
        $detail['specs'] = $this->_goods_specs_mod->where("goods_id='{$goods_id}'")->select();
        //处理产品规格的最大购买数量
        foreach($detail['specs'] as $sk => $spec){
            if(!$spec['limit_buy']) $detail['specs'][$sk]['limit_buy'] = $spec['sku'];
        }
        //获取关联产品
        $detail['related_products'] = $this->get_related_products();
        //获取产品coa
        $detail['goods_coas'] = $this->get_goods_coas($goods_id);
        //产品评论数量
        $detail['comments_count'] = $this->_comments_mod->where("goods_id={$goods_id} AND status=1")->count();
        //浏览记录
        $history = viewed_items();
        $this->assign('history',$history);
        //加入浏览记录
        $this->cookie_history($goods_id);
        $this->assign($detail);
        $seo=array(
        	'name'=>empty($detail['goods']['seo_title'])?$detail['goods']['goods_name']:$detail['goods']['seo_title'],
        	'keywords'=>$detail['goods']['seo_keywords'],
        	'description'=>$detail['goods']['seo_descriptions'],
        ); 
        $this->_config_seo($seo);   #dump($detail);
        $this->display('./Product_Details');
    }
    
    /** 产品详细页 */
    function information(){
        $goods_id = I('id','','intval');
        $detail['goods'] = $this->_goods_mod->find($goods_id);
        //获取产品coa
        $detail['goods_coas'] = $this->get_goods_coas($goods_id);
        if(!$detail['goods']){
            $this->error('The product does not exist, or has been deleted.');
            return;
        }
        if(!$detail['goods']['is_on_sale']){
            $this->error('The product is temporarily unavailable.');
            return;
        }
        $this->assign($detail); 
        $this->display('./Product_Information');
    }
    
    /** 获取关联产品 */
    function get_related_products(){
        $count = $this->_goods_mod->count();
        $firstRow = mt_rand(0,$count-3);
        $goods = $this->_goods_mod->limit($firstRow.',2')->select();
        return $goods;
    }
    
    /** 获取产品COA*/
    function get_goods_coas($goods_id){
        $_goods_coa_mod = D('Admin/GoodsCoa');
        $coas = $_goods_coa_mod->where("goods_id={$goods_id}")->select();
        return $coas;
    }
    
    /** 评论列表页*/
    function reviews(){
    	$where['goods_id']=I('goods_id');
    	$where['status']=1;
    	$totalRows=$this->_comments_mod->where($where)->count();
    	$listRows=10;
    	$page=New Page($totalRows, $listRows); 
    	$comments=$this->_comments_mod->where($where)->limit($page->firstRow,$page->listRows)->select(); 
    	$this->assign('comments',$comments);  
    	$this->assign('page',$page->show());
    	$totalpages=ceil($totalRows/$listRows);
    	$this->assign('totalpages',$totalpages);
    	$this->display('./reviews');
    }
     
    
    /** 异步读取产品评价 */
    function ajax_comments(){
        $goods_id = I('id','','intval');
        if(!$goods_id){
            $this->error('Incorrect parameters, please refresh and try again. 5');
            return;
        }
        $count = $this->_comments_mod->where("goods_id={$goods_id} AND status=1")->count();
        $page = new \Think\Page($count,5,array('id'=>$goods_id));
        $comments = $this->_comments_mod->where("goods_id={$goods_id} AND status=1")
                    ->limit($page->firstRow.','.$page->listRows)->select();
        $html = '';
        if(!$comments){
            $html .= '<div class="noComments">Be the first to submit a review on this product!</div>';
        }else{
            foreach($comments as $key => $val){
                $html .= '<div class="every">';
                $html .= '<p>'.$val['content'].'</p>';
                $html .= '<div class="star star'.(int)$val['comment_stars'].'"></div>';
                $html .= '<p class="whoInfo">By<span>'.$val['user_name'].'</span>'.date('Y-m-d H:i:s',$val['comment_time']).'</p>';
                $html .= '</div>';
            }
            $html .= '<div class="detailPage">'. $page->show() .'</div>';
        }
        $this->success($html);
    }
    
    
    /** 添加产品评论 */
    function add_comment(){
    	if(!IS_POST){
    		$this->display('./Write_Review');
    	}else{
	        $goods_id = I('id','','intval');
	        $goods = $this->_goods_mod->find($goods_id);
	        if(!$goods){
	            $this->error('Incorrect parameters, please refresh and try again. add_comment');
	            return;
	        }
	        $data = array(
	            'user_id' => 0,
	            'user_name' => trim(I('user_name')),
	            'email' => trim(I('email')),
	            'comment_stars' => I('score',5,'intval'),
	            'content' => trim(I('content')),
	            'goods_id' => $goods_id,
	            'comment_time' => gmtime(),
	            'status' => 0
	        );
	        if($this->visitor->has_login)
	            $data['user_id'] = $this->visitor->get('user_id');
	        if(!$this->_comments_mod->create($data)){
	            $this->error($this->_comments_mod->getError());
	            return;
	        }
	        $this->_comments_mod->add();
	        $this->success('Your review is submitted successfully, and will display after being moderated.');
    	}
    }
    
    /** 添加收藏 */
    function add_collect(){
        $goods_id = I('id','','intval');
        if(!$this->visitor->has_login){ //用户没有登陆，跳转到登陆页面
            $this->error('Please login first.',U('/Index/login_register'));
            return;
        }
        $goods = $this->_goods_mod->find($goods_id);
        if(!$goods){
            $this->error('Incorrect parameters, please refresh and try again. add_collect');
            return;
        }
        //检测是否已经收藏过了
        $_collect_mod = M('Collect');
        $where['user_id'] = $this->visitor->get('user_id');
        $where['goods_id'] = $goods_id;
        $collects = $_collect_mod->where($where)->find();
        if($collects != false){
            //$this->error('You have added this item already.');
            //return;
            $_collect_mod->delete($collects['cid']);
            $this->success('Remove from wish list successfully!');
            return;
        }
        $data = array(
            'goods_id' => $goods_id,
            'user_id' => $this->visitor->get('user_id'),
            'ctime' => gmtime()
        );
        if(!$_collect_mod->add($data)){
            $this->error('Add to wish list fail！');
            return;
        }
        $this->success('Add to wish list successfully!');
    }
    
    /** 检查商品是否已经被收藏 */
    function check_collect_goods($goods_id){
        if(!$this->visitor->has_login)  return false;
        $where['user_id'] = $this->visitor->get('user_id');
        $where['goods_id'] = $goods_id;
        $collect = M('Collect')->where($where)->find();
        if(!$collect) return false;
        return true;
    }
    
    /** 加入浏览记录 */
    function cookie_history($goods_id, $express = 604800){
        $history = array(gmtime() => $goods_id);
        if(!cookie('history_goods')){
            cookie('history_goods',$history,$express);
        }else{
            $new_history = array_merge(cookie('history_goods'),$history);
            uksort($new_history, "my_sort");//按照浏览时间排序
            $history = array_unique($new_history);
            if (count($history) > 10){
                $history = array_slice($history,0,10);
            }
            cookie('history_goods',$history,$express);
        } 
    }
    
    /** 浏览记录按照时间排序 */
    function my_sort($a, $b){
        $a = substr($a,1);
        $b = substr($b,1);
        if ($a == $b) return 0;
        return ($a > $b) ? -1 : 1;
      }
    }


?>