<?php
/**
 * 购物车控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Controller;
use Think\Controller;
class CartController extends FrontendController{
    var $_cart_mod = null;
    var $_goods_mod = null;
    var $_goods_specs_mod = null;
    function __construct(){
        parent::__construct();
        $this->CartController();
    }
    function CartController(){
        $this->_cart_mod = D('Cart');
        $this->_goods_mod = D('Admin/Goods');
        $this->_goods_specs_mod = D('Admin/GoodsSpecs');
    }
    
    /** 购物列表 */
    function index(){
        if($this->visitor->has_login){ //已经登陆
            $where['c.user_id'] = $this->visitor->get('user_id');
        }else{
            $where['c.user_id'] = 0;
            $where['c.session_id'] = $this->_ssid;
        }
        $carts = $this->_cart_mod->field('c.*,sp.sku,sp.limit_buy')
                 ->join(' as c LEFT JOIN __GOODS_SPECS__ as sp ON c.spec_id = sp.spec_id')
                 ->where($where)->select();
        if($carts != false){
            foreach($carts as $key => $vo){
                $carts[$key]['totle'] = $vo['price'] * $vo['quantity'];
                if(!$vo['limit_buy']) $carts[$key]['limit_buy'] = $vo['sku'];
            }
        }
        $this->assign('carts',$carts);
        $this->display('./cart.index');
    }
    
    /** 更新购物车信息 */
    function update(){
        $cart_id = I('id','','intval');
        $quantity = I('quantity','','intval');
        $where['c.cart_id'] = $cart_id;
        if($this->visitor->has_login){ //已经登陆
            $where['c.user_id'] = $this->visitor->get('user_id');
        }else{
            $where['c.user_id'] = 0;
            $where['c.session_id'] = $this->_ssid;
        }
        $cart = $this->_cart_mod->field('c.*,sp.sku,sp.limit_buy')
                ->join(' as c LEFT JOIN __GOODS_SPECS__ as sp ON c.spec_id = sp.spec_id')
                ->where($where)->find();
        if(!$cart){
            $this->error('Cart information is invalid, please refresh and try again.');
            return;
        }
        if(!$quantity)
            $quantity = 1;
        if($quantity > $cart['sku']){
            $this->error('Maximum stock amount exceeded');
            return;
        }
        if($cart['limit_buy'] && $quantity > $cart['limit_buy'])
            $quantity = $cart['limit_buy'];
        //更新购物车数量
        $data = array(
            'cart_id' => $cart_id,
            'quantity' => $quantity
        );
        if(!$this->_cart_mod->create($data)){
            $this->errorr($this->_cart_mod->getError());
            return;
        }
        $this->_cart_mod->save();
        $data['price'] = format_price($cart['price']);
        $data['totle'] = format_price($cart['price'] * $quantity);
        $this->success($data,U('/Cart'));
    }
    
    /** 删除购物车项 */
    function delete(){
        $cart_id = trim(I('id'));
        if(!$cart_id){
            $this->error('Incorrect parameters, please refresh and try again 3.');
            return;
        }
        if(strpos($cart_id,','))
            $cart_id = explode(',',$cart_id);
        if(is_array($cart_id)){
            $where['cart_id'] = array('in',$cart_id);
        }else{
            $where['cart_id'] = $cart_id;
        }
        if($this->visitor->has_login){ //已经登陆
            $where['user_id'] = $this->visitor->get('user_id');
        }else{
            $where['user_id'] = 0;
            $where['session_id'] = $this->_ssid;
        }
        $carts = $this->_cart_mod->where($where)->select();
        if(!$carts){
            $this->error('Items in cart do not exist, or have been deleted.');
            return;
        }
        if(!$this->_cart_mod->where($where)->delete()){
            $this->error();
            return;
        }
        $this->success($cart_id,U('/Cart'));
    }
    
    /** 加入购物车 */
    function add(){
        $spec_ids = trim(I('id'));
        $quantity = trim(I('quantity'));
        if(!$spec_ids || !$quantity){
            $this->error('Incorrect parameters, please refresh and try again 4.');
            return;
        }
        //判断用户是否登陆
        if($this->visitor->has_login){ //已经登陆
            $where['user_id'] = $this->visitor->get('user_id');
        }else{
            $where['user_id'] = 0;
            $where['session_id'] = $this->_ssid;
        }
        $spec_ids = explode(',',$spec_ids);
        $quantity = explode(',',$quantity);
        $cart_ids = array();
        $additem = 0; 
        foreach($spec_ids as $key => $sid){
            $where['spec_id'] = $sid;
            $cart = $this->_cart_mod->where($where)->find();
            if(!$cart){ //不存在则新增购物车
                $goods_info = $this->_goods_mod->field('g.*,gs.spec_id,gs.sku,gs.limit_buy,gs.price,gs.spec_page,gs.spec_batch,gs.weight,gs.is_sample')
                              ->join(' as g LEFT JOIN __GOODS_SPECS__ as gs ON g.goods_id=gs.goods_id')
                              ->where("gs.spec_id={$sid}")
                              ->find();
                if($quantity[$key] > $goods_info['sku']){ //判断是否超出库存
                    $this->error('Lack of stock.');
                    return;
                }
                if($goods_info['limit_buy'] && $quantity[$key] > $goods_info['limit_buy']){ //是否设置了最大库存数量
                    $quantity[$key] = $goods_info['limit_buy'];
                }
                $cart_data = array(
                    'session_id' => $this->_ssid,
                    'user_id' => 0,
                    'goods_id' => $goods_info['goods_id'],
                    'goods_name' => $goods_info['goods_name'],
                    'default_image' => $goods_info['goods_thumb'],
                    'spec_id' => $goods_info['spec_id'],
                    'spec_attr' => 'Batch:'.$goods_info['spec_batch'].' Package:'.$goods_info['spec_page'],
                    'quantity' => $quantity[$key],
                    'price' => $goods_info['price'],
                    'weight' => $goods_info['weight'],
                    'is_sample' => $goods_info['is_sample']
                );
                if($this->visitor->has_login){
                    $cart_data['user_id'] = $this->visitor->get('user_id');
                }
                if(!$this->_cart_mod->create($cart_data)){
                    $this->error($this->_cart_mod->getError());
                    return;
                }
                $cart_ids[] = $this->_cart_mod->add();
                $additem++;
            }else{ //购物车中已经存在，则更新数量
                $cart_data = array(
                    'cart_id' => $cart['cart_id'],
                    'quantity' => $cart['quantity'] + $quantity[$key]
                );
                //检测库存和是否设置最大库存数量
                $spec = $this->_goods_specs_mod->find($sid);
                if($cart_data['quantity'] > $spec['sku']){ //判断库存数量是否超出
                    $this->error('Lack of stock.');
                    return;
                }
                if($spec['limit_buy'] && $cart_data['quantity'] > $spec['limit_buy']){ //设置了最大购买数量，并且超出最购买数量
                    $cart_data['quantity'] = $spec['limit_buy'];
                }
                if(!$this->_cart_mod->create($cart_data)){
                    $this->error($this->_cart_mod->getError());
                    return;
                }
                $this->_cart_mod->save();
                $cart_ids[] = $cart['cart_id'];
            }
        }
        $this->success(array('cart_ids'=>implode(',',$cart_ids),'additem'=>$additem));
    }
}


?>