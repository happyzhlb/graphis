<?php
/**
 * 广告模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class AdModel extends Model{
    protected $_validate = array(
        array('title','require','广告名称不能为空',0),
        array('pid','require','广告位置不能为空',0),
        //array('url','require','广告链接不能为空',0),
        //array('url','url','广告链接格式不正确'), 
        array('img','require','广告图片不能为空',0),
    );
    
    /** 获取广告类型列表 */
    function getAdType(){
    	return array(
    		'0'=>'品牌列表页',
    		'1'=>'分类列表页',
    		'2'=>'商品列表页',
    		'3'=>'文章列表页',
    		'4'=>'文章的导购页（文章内容页）',
    		'5'=>'活动页（H5呈现）',
			'6'=>'商品集合',
			'7'=>'文章集合',
			'8'=>'类别集合',
    		'9'=>'品牌集合',
			'10'=>'商品详情页',
			'11'=>'品牌详情页',
			'12'=>'自营文章列表页',
			'13'=>'自营商品列表页', 
			'14'=>'自营商品详情页',
    	);
    }
}


?>