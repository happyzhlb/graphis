<?php
/**
 * 产品列表页
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class ListController extends FrontendController{
    var $_goods_mod = null;
    var $_gcategory_mod = null;
    function __construct(){
        parent::__construct();
        $this->ListController();
    }
    function ListController(){
        $this->_goods_mod = D('Admin/Goods');
        $this->_gcategory_mod = D('Admin/Gcategory');
    }
    
    /** 产品列表 */
    function index(){
        $cate_id = I('cate_id','','intval');
        if($cate_id){ 
            $cate_ids = array();
            $this->_gcategory_mod->_get_children_cate_id($cate_ids,$cate_id);
            $catewhere['gd.cate_id'] = array('in',$cate_ids);
            $gcate = $this->_gcategory_mod->find($cate_id);
            $ncate_ids = $cate_ids;
            unset($cate_ids[0]);
            if(!empty($cate_ids)){
                $map['gc.cate_id'] = array('in',$cate_ids);
                $cates = M('GcategoryGoods')->field('count(*) as count,c.cate_id,c.cate_name')
                         ->join(' as gc INNER JOIN __GCATEGORY__ as c ON c.cate_id=gc.cate_id')
                         ->where($map)
                         ->group('gc.cate_id')
                         ->select();
                $this->assign('gcates',$cates);
            }
            
        }
        $keyword = trim(I('keyword'));
        //支持空格、加号等模糊查询
        $keyword=str_ireplace(array(' ','+','-'), '%', $keyword); 
        if($keyword){
            $where['g.goods_name'] = array('like','%'.$keyword.'%');
        }
        $brand_id = I('bid','','intval');
        if($brand_id){
            $where['g.bid'] = $brand_id;
        }
        $where['g.is_on_sale']=1;
        $sort = trim(I('sort'));
        if(!$sort) $sort = 'g.goods_id';
        $order = trim(I('order'));
        if(!$order) $order = 'DESC';
   if($cate_id){      
        $count = $this->_goods_mod
                 ->join(' as g INNER JOIN (select distinct goods.goods_id from __GOODS__ as goods LEFT JOIN  __GCATEGORY_GOODS__ as gd ON goods.goods_id= gd.goods_id where gd.cate_id in('.implode(',',$ncate_ids).')) as gg ON gg.goods_id=g.goods_id')
                 ->where($where)
                 ->count();
   }else{
        $count = $this->_goods_mod->where($where)->count(); 
   }
        $showpages = C('list_num')? C('list_num') : 10;         
        $page = new \Think\Page($count,$showpages);
   if($cate_id){   
        $goods = $this->_goods_mod->distinct(true)->field('g.*,gs.price')
                 ->join(' as g INNER JOIN (select distinct goods.goods_id from __GOODS__ as goods LEFT JOIN  __GCATEGORY_GOODS__ as gd ON goods.goods_id= gd.goods_id where gd.cate_id in('.implode(',',$ncate_ids).')) as gg ON gg.goods_id=g.goods_id')
                 ->join(' LEFT JOIN (select goods_id,min(price) as price from __GOODS_SPECS__ group by goods_id) gs ON g.goods_id=gs.goods_id')
                 ->where($where)->order($sort.' '.$order)->limit($page->firstRow.','.$page->listRows)
                 ->select();
         //统计品牌信息
        unset($where['g.bid']);
        $brands = $this->_goods_mod->distinct(true)->field('count(g.bid) as count,b.bid,b.bname')
                 ->join(' as g LEFT JOIN __BRAND__ as b ON g.bid=b.bid')
                 ->join(' INNER JOIN (select distinct goods.goods_id from __GOODS__ as goods LEFT JOIN  __GCATEGORY_GOODS__ as gd ON goods.goods_id= gd.goods_id where gd.cate_id in('.implode(',',$ncate_ids).')) as gg ON gg.goods_id=g.goods_id')
                 ->where($where)
                 ->group('g.bid')->select();                
                 
   }else{
        $goods = $this->_goods_mod->distinct(true)->field('g.*,gs.price')
                 ->join(' as g LEFT JOIN (select goods_id,min(price) as price from __GOODS_SPECS__ group by goods_id) gs ON g.goods_id=gs.goods_id')
                 ->where($where)->order($sort.' '.$order)->limit($page->firstRow.','.$page->listRows)
                 ->select(); 
         //统计品牌信息
        unset($where['g.bid']);
        $brands = $this->_goods_mod->field('count(g.bid) as count,b.bid,b.bname')
                 ->join(' as g LEFT JOIN __BRAND__ as b ON g.bid=b.bid')
                 ->where($where)
                 ->group('g.bid')->select();    
   } 
   
        if($goods != false){
            foreach($goods as $key => $val){
                $goods[$key]['is_collect'] = $this->check_collect_goods($val['goods_id']);
            }
        }
        //浏览记录
        $history = viewed_items();
        $this->assign('history',$history);
        $this->assign('sort',$sort);
        $this->assign('order',$order);
        $this->assign('parameter',$_GET);
        $this->assign('brands',$brands);
        $this->assign('gcate',$gcate);
        $this->assign('page',$page->show());
        $this->assign('goods',$goods);
        $this->display('./list.index');         
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
}


?>