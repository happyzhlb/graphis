<?php
/**
 * 产品详情控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Home\Controller;
use Think\Controller;
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
        #$this->_goods_gallery_mod = D('Admin/GoodsGallery');
        #$this->_goods_specs_mod = D('Admin/GoodsSpecs');
        #$this->_comments_mod = D('Comments');
        import('Vendor.Tae.TbGoodsApi');
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
        //读取产品活动
        $this->assign('promotion',$this->get_promotion($goods_id));
        $this->assign($detail);
        $seo=array(
        	'name'=>empty($detail['goods']['seo_title'])?$detail['goods']['goods_name']:$detail['goods']['seo_title'],
        	'keywords'=>$detail['goods']['seo_keywords'],
        	'description'=>$detail['goods']['seo_descriptions'],
        ); 
        $this->_config_seo($seo);
        $this->display('./goods.index');
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
                $user_name = $result = substr($val['user_name'], 0, 1);
                $user_name .= str_repeat('*', 4);
                $user_name .= substr($val['user_name'], strlen($val['user_name'])-1);
                $val['user_name'] = $user_name;
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
    
    /** 添加收藏 */
    function add_collect(){
        $goods_id = I('id','','intval');
        if(!$this->visitor->has_login){ //用户没有登陆，跳转到登陆页面
            $this->error('nologin',U('/Index/login_register'));
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
    
    /** 读取商品的促销活动 */
    function get_promotion($goods_id){
        $_promotion_mod = D('Admin/Promotion');
        $nowtime = gmtime();
        $map['_string'] = "(products = 'all') or (products like '%{$goods_id}%')";
        $map['from_time'] = array('ELT', $nowtime);
        $map['to_time'] = array('EGT', $nowtime);
        $map['status'] = 1;
        $map['pro_type'] = 'discount'; //优先读取折扣信息
        $list = $_promotion_mod->field('min(rate) as rate,title,remark')->where($map)->find();
        if(!$list['rate']){ //折扣优惠不存在，则读取积分活动
            $map['pro_type'] = 'integral';
            $list = $_promotion_mod->field('max(rate) as rate,title,remark')->where($map)->find();
        }
        return $list;
    }    
    
	/**
	 * 判断淘宝产品是否下架-根据淘宝ID能获取到产品的OpenIid，
	 * 能获取到:返回OpenIid--正常上架状态，
	 * 不能获取到的：返回false--不存在或者下架状态.
	 * */
	public function getOpenIid($id){ 		
		$tbGoods = new \TbGoodsApi(); 
		$openIid=$tbGoods->getOpenIid($id);
		return $openIid;
	}
	
	/**下架检测
	 * **/
	public function check_xj(){ 
		set_time_limit(600);
		$xjlog_mod= M('xj_log');
		$where=array(
			'is_on_sale'=>1
		);
		//$tbGoods = new \TbGoodsApi(); 
		//echo $openIid=$tbGoods->getOpenIid($val['outer_id']);
//			if ($openIid==48) {
//				echo "<meta http-equiv='refresh' content='3600'>";
//				echo 'API Refused.'; 
//				exit;
//			}		
		$list=$this->_goods_mod->where($where)->page(1,5)->order('check_time asc')->select();
		foreach ($list as $key => $val){
			$this->_goods_mod->where(array('outer_id'=>$val['outer_id']))->save(array('check_time'=>date('Y-m-d H:i:s')));  			
			if($this->is_xj($val['outer_id'])){
				$data=array('is_on_sale'=>0);
				$res=$this->_goods_mod->where('outer_id="'.$val['outer_id'].'"')->save($data);  #dump($res);
				$logdata=array(
					'outer_id'=>$val['outer_id'],
					'reason'=>'系统检测，自动下架.',
					'operator'=>'check_xj',
					'ctime'=>date('Y-m-d H:i:s')
				);
				$xjlog_mod->add($logdata); 
				echo $logdata['reason'];
			}else{
				echo 'On Sale OK.';
			} 
			echo $val['outer_id'].'<br/>';
		}
		echo "<meta charset='utf-8' /><meta http-equiv='refresh' content='5'>";
	}
	
	//执行下架(没用)
	public function do_xj($outer_id){
			if(empty($outer_id)) return FALSE;
			//$this->_goods_mod->where(array('outer_id'=>$outer_id))->save(array('check_time'=>date('Y-m-d H:i:s')));
			
			
				$data=array('is_on_sale'=>0);
				$res=$this->_goods_mod->where('outer_id="'.$outer_id.'"')->save($data);  
				$logdata=array(
					'outer_id'=>$val['outer_id'],
					'reason'=>'系统检测，自动下架.',
					'operator'=>'system',
					'ctime'=>date('Y-m-d H:i:s')
				);
				$xjlog_mod->add($logdata); 
			
	}  
	
	//判断是否下架,
	//		$url1= "https://item.taobao.com/item.htm?id=530678610014"; //淘宝已经下架 
	//		$url2= "https://item.taobao.com/item.htm?id=528264890830"; // 淘宝存在
	//		$url3= "https://item.taobao.com/item.htm?id=5282648908306"; //淘宝不存在
	//		$url4= "https://detail.tmall.com/item.htm?id=528735462292"; // 天猫存在
	//		$url5= "https://detail.tmall.com/item.htm?id=528735462292111"; // 天猫不存在
	//		$url6= "https://detail.tmall.com/item.htm?mt=&mt=&id=22690796931";  //天猫下架
	public function is_xj($outer_id){   
		$url = "https://item.taobao.com/item.htm?id=".$outer_id;
		$taobaoHtml=file_get_contents($url); 		dump($taobaoHtml);
		$tmall_error = strstr($taobaoHtml, '<div class="errorDetail">');
		//dump($tmall_error);
		$taobao_error =  strstr($taobaoHtml, '<div id="error-notice">');
		//dump($taobao_error);		
		$taobao_xj =  strstr($taobaoHtml, 'J_TOffSale tb-skin tb-off-sale');  //<strong>此宝贝已下架</strong>
		//dump($taobao_xj);		
		$tmall_xj =  strstr($taobaoHtml, 'sold-out-tit');
		//dump($tmall_xj);		

		if($tmall_error||$taobao_error||$taobao_xj||$tmall_xj){
			return TRUE;
		}else{
			return false;
		}
	}
	
	//判断是否上架,
	public function is_onsale($outer_id){   
		$url = "https://item.taobao.com/item.htm?id=".$outer_id;
		$taobaoHtml=file_get_contents($url); 		//dump($taobaoHtml);
		$tmall_onsale = strstr($taobaoHtml, '<dl class="tm-delivery-panel" id="J_RSPostageCont">');  //<dl class="tm-delivery-panel" id="J_RSPostageCont"><dt class="tb-metatit">运费</dt>
		$taobao_onsale =  strstr($taobaoHtml, 'tb-name tb-property-type');  //<span class="tb-name tb-property-type">配送</span> 
		if($tmall_onsale||$taobao_onsale){
			return TRUE;
		}else{
			return false;
		}
	}

	/**上架检测（下架区）
	 * **/
	public function check_onsale(){ 
		set_time_limit(600);
		$xjlog_mod= M('xj_log');
		$where=array(
			'is_on_sale'=>0
		); 
		$list=$this->_goods_mod->where($where)->page(1,5)->order('check_time asc')->select();
		foreach ($list as $key => $val){
			$this->_goods_mod->where(array('outer_id'=>$val['outer_id']))->save(array('check_time'=>date('Y-m-d H:i:s')));  			
			if($this->is_onsale($val['outer_id'])){
				$data=array('is_on_sale'=>1);
				$res=$this->_goods_mod->where('outer_id="'.$val['outer_id'].'"')->save($data);  dump($res);
				$logdata=array(
					'outer_id'=>$val['outer_id'],
					'reason'=>'下架区检测,自动上架.',
					'operator'=>'check_onsale',
					'ctime'=>date('Y-m-d H:i:s')
				);
				$xjlog_mod->add($logdata); 
				echo $logdata['reason'];
			}else{
				echo 'Not On Sale. ';
			} 
			echo $val['outer_id'].'<br/>';
		}
		echo "<meta charset='utf-8' /><meta http-equiv='refresh' content='5'>";
	}
	
	/**
     * 根据地址抓取淘宝页面html代码
     * @param type $url 地址   ?cookie 过期？
     * @return boolean
     */
    public function getTaoBaoHtml($url) {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init();
        // 设置 url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "User-Agent: {Mozilla/5.0 (Windows NT 6.1; WOW64; rv:26.0) Gecko/20100101 Firefox/26.0}",
            "Accept: {text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8}",
            "Accept-Language: {zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3}",
            "Cookie:{cq=ccp%3D1; cna=a7suCzOmSTECAXgg9iCf4AtX; t=671b2069c7e8ac444da66d664a397a5f; tracknick=%5Cu4F0D%5Cu6653%5Cu8F8901; _tb_token_=nDiU1vCuzFd0; cookie2=c54709ffbe04a5ccb80283c34d6b00fa; pnm_cku822=128WsMPac%2FFS4KgNn%2BYfhzduo4U2NC0zh9cAS4%3D%7CWUCLjKhqr873bOIFQcMecSw%3D%7CWMEKRlV%2B3D9a6XWaidNWNQOSWXwaXugvQHzhxALh%7CX0YLbX78NUR2b2DHoxnIqZENQqR35TBZbfQ5vooI0b6GHZA3U1kr%7CXkdILogCr878ZK9I%2B%2FE3QjAD3lFJJaAZRA%3D%3D%7CXUeMwMR2s%2BTUQk8IPP5TNgWfUjQwonccMCxihTa0fRYgtjgfa4j6%7CXMYK7F8liOvH3hMUpzXkiaU%2FJw%3D%3D}",
        ));
        // 页面内容我们并不需要
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        // 只需返回HTTP header
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // 返回结果，而不是输出它
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        ob_start();
        curl_exec($ch);
        $html = ob_get_contents();
        ob_end_clean();
        curl_close($ch);
        return $html;
    }
	
	/** 获取价格 http://127.0.0.1:81/goods/getPrice/outer_id/41944383443 */
	public function getTbPrice($outer_id){ 		
		$tbGoods = new \TbGoodsApi(); 
		$p=$tbGoods->getPrice($outer_id); 
		return $p;
	} 
	
	
	//判断是否上架,
	public function check_price(){
		$where = array('is_on_sale'=>1);
		$list=$this->_goods_mod->where($where)->field('outer_id')->page(1,2)->order('check_price_time asc')->select();
		$tbGoods = new \TbGoodsApi(); 
		foreach ($list as $key => $val){
			 $outer_id=$val['outer_id'];
			 if(empty($outer_id)) exit('outer id 缺少.');
			$price=$this->_goods_mod->where(array('outer_id'=>$outer_id))->getField('price');
			$tb_price=$tbGoods->getPrice($outer_id); ;
			
			var_dump($price);
			var_dump($tb_price);
			if($price==$tb_price){
				$data=array( 
					'is_price_diff'=>0,
					'check_price_time' => date('Y-m-d H:i:s',time())
				);
				$res=$this->_goods_mod->where(array('outer_id'=>$outer_id))->save($data);
				echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />价格ok,无需处理.'; 
			}else{
				$data=array(
					'is_price_diff'=>1,
					'price'=>$tb_price,
					'check_price_time' => date('Y-m-d H:i:s',time())
				);
				$res=$this->_goods_mod->where(array('outer_id'=>$outer_id))->save($data);
				var_dump($res); 
				//echo M()->getLastsql();
			}
		}
	}
}
    
?>