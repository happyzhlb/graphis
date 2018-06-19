<?php
/**
 * 产品相册模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class GoodsGalleryModel extends Model{
    protected $_validate = array(
        array('goods_id','require','相册中产品ID不能为空',0),
        array('img_url','require','相册中图不能为空',0),
        array('img_thumb','require','相册缩略图不能为空',0),
        array('img_original','require','相册中图不能为空',0),
    );
}


?>