<?php
/**
 * 积分模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Model;
use Think\Model;
class AlbumcateAlbumModel extends Model{
    
   /** 读取专辑对应的关联类别，返回逗号分隔的ids字符串 */
   function getAlbumCateids($album_id){
   	if(empty($album_id)){
   		return false;
   	}
   	$where=array(
   			'album_id' => $album_id
   	);
   	$list=M('albumcate_album')->where($where)->field('cate_id')->select();
   	$arr= array();
   	foreach ($list as $key =>$val){
   		$arr[$key] =  $val['cate_id'] ;
   	}
   	$arr = join(',', $arr );
   	return $arr;
   }
   
   
   /** 读取专辑对应的关联类别，返回逗号分隔的ids字符串 */
   function getAlbumidsByCateid($cate_id){
   	if(empty($cate_id)){
   		return false;
   	}
   	$where=array(
   			'cate_id' => $cate_id
   	);
   	$list=M('albumcate_album')->where($where)->field('album_id')->select();
   	$arr= array();
   	foreach ($list as $key =>$val){
   		$arr[$key] =  $val['album_id'] ;
   	}
   	$arr = join(',', $arr );
   	return $arr;
   }
	
	
	
	
}


?>