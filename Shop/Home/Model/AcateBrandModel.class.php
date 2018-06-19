<?php
/**
 * 分类关联品牌模型
 * @author Abiao
 * @copyright 2017
 */
namespace Home\Model;
use Think\Model;
class AcateBrandModel extends Model{ 

    /** 读取类别对应的关联的品牌 ID(数组,字符串，json) */
    function getBrandStr($cate_id,$toJson=FALSE){
    	if(empty($cate_id)){
    		return false;
    	}
    	$where=array(
    		'cate_id' => $cate_id
    	);
    	$list=$this->where($where)->field('brand_id')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['brand_id'] ;
    	} 
    	$return = join(',', $arr );  
    	return $return;
    }
    
    /** 读取类别对应的关联的品牌 ID(数组,字符串，json) */
    function getBrandJson($cate_id,$toJson=true){
    	if(empty($cate_id)){
    		return false;
    	}
    	$where=array(
    		'cate_id' => $cate_id
    	);
    	$list=$this->where($where)->field('brand_id')->select();  
    	$arr= array(); 
    	foreach ($list as $key =>$val){
    		$arr[$key] =  $val['brand_id'] ;
    	} 
    	$return = M('brand')->field('brand_id,brand_name,blogo')->where(array('brand_id' => array('in',$arr)))->select();  
    	foreach ($return as $key => $val){
    		$return[$key]['blogo'] = dealImg($val['blogo']);
    	}
    	if($toJson) $return = json_encode($return); 
    	return $return;
    }
    
    
    
    
}


?>