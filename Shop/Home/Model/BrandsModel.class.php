<?php
/**
 * 前台品牌模型
 * @author Abiao
 * @copyright 2014
 */
namespace Home\Model;
use Think\Model;
class BrandsModel extends Model{ 

	/** 读取品牌下面的所有商品 */
    function getGoodsByBrand( $brand_id,$limit=0,$order='goods_id desc'){ 
        if($brand_id)
            $where['bid'] = array('eq',$brand_id);
        	$list = M('goods')->field('goods_id,goods_name,goods_code,goods_img,bid,market_price,estars,ecount')->limit($limit)->order($order)->where($where)->select(); 
        if(!$list){
            return false;
        }else{ 
        	foreach ($list as $key => $val){
        		$list[$key]['range_price'] = get_range_price($val['goods_id'],'$');
        	} 
            return $list;
        }        
    } 

    	/** 读取品牌下面的所有MSDS下载列表 */
    function getMsdsByBrand( $brand_id,$limit=0,$order='goods_id desc'){ 
        if($brand_id)
            $where['bid'] = array('eq',$brand_id);
        	$list = M('goods')->field('goods_id,goods_name,msds')->limit($limit)->order($order)->where($where)->select(); 
        if(!$list){
            return false;
        }else{  
            return $list;
        }        
    } 
    
}


?>