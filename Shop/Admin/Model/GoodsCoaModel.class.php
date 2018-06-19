<?php
/**
 * 产品批次COA模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Model;
use Think\Model;
class GoodsCoaModel extends Model{
    protected $_validate = array(
        array('goods_id','require','产品ID不能为空',0),
        array('batch','require','COA批次不能为空',0),
        array('file','require','COA文件必须上传',0)
    );
}


?>