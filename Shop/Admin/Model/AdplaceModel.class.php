<?php
/**
 * 广告位模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class AdplaceModel extends Model{
    protected $_validate = array(
        array('pname','require','广告位名称不能为空',0),
        array('pname','','广告位已经存在',0,'unique'),
        array('adwith','require','广告位宽度不能为空',0),
        array('adheight','require','广告位高度不能为空',0),
        array('ad_num','require','广告位显示数量不能为空',0)
    );
}


?>