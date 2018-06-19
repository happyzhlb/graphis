<?php
/**
 * 接受仓库的订单通知控制器
 * @author jiwaini00000
 * @copyright 2014
 */
 namespace Home\Controller;
 use Think\Controller;
 use Think\Log;
 class AcceptnoticeController extends FrontendController{
    var $_order_mod = null;
    var $_order_goods_mod = null;
    var $_order_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->AcceptnoticeController();
    }
    function AcceptnoticeController(){
        $this->_order_mod = D('Admin/Order');
        $this->_order_goods_mod = D('Admin/OrderGoods');
        $this->_order_log_mod = D('Admin/OrderLog');
    }
    function index(){
        $client_ip = get_client_ip();
        $limit_ip = C('notice_limit_ip');
        $limit_ip = explode(',',$limit_ip);
        if(!in_array($client_ip,$limit_ip)){
            $this->result_error('Your IP is not in our white list.');
            return;
        }
        
        //记录通知发送的日志信息
        //$xml = file_get_contents(DATA_PATH .'notice_xml/Sample XML Shipment Notice.xml');
        $xml = file_get_contents('php://input');
        Log::record($xml,'INFO',true);
        Log::save();
        
        //需要确认传过来的文件变量名称
       /* $upload = new \Think\Upload(
            array(
                'rootPath' => './Shop/Runtime/Data/',
                'maxSize' => '5242880',
                'exts' => 'xml',
                'subName' => '',
                'savePath' => 'notice_xml/'
            )
        );
        
        if(!$file = $upload->upload($_FILES)){
            $this->result_error($upload->getError());
            return;
        }*/
        $result = simplexml_load_string($xml);
        $result = $this->object_array($result);
        $order_sn = $result['ShipmentOrders']['Order']['CustomerPONumber'];
        $shipping_code = $result['ShipmentOrders']['Order']['ShipmentInfo']['Carrier'];
        $invoice_no = $result['ShipmentOrders']['Order']['ShipmentInfo']['BillOfLading'];
        $shipping_time = strtotime($result['ShipmentOrders']['Order']['ShipmentInfo']['DateOfShipment']);
        $shiping_info = C('SHIPPING');
        if(empty($shiping_info[$shipping_code])){
            $this->result_error('Distribution mode does not exist.');
            return;
        }
        $where['order_sn'] = $order_sn;
        $order = $this->_order_mod->where($where)->find();
        if(!$order){
            $this->result_error('Order does not exist.');
            return;
        }
        //检测订单状态是否允许发货操作
        if($order['order_status'] == 20 && !$order['refund_status']){
            $order_data = array(
                'order_id' => $order['order_id'],
                'order_status' => 30,
                'shipping_code' => $shiping_info[$shipping_code]['shipping_code'],
                'shipping_name' => $shiping_info[$shipping_code]['shipping_name'],
                'shipping_time' => $shipping_time,
                'invoice_no' => $invoice_no, 
            );
            Log::record(var_export($order_data,true),'INFO',true);
            //判断是否自提订单如果自提，状态直接已经完成订单
            if($shipping_code == 'CPU'){
                $order_data['order_status'] = 40;
                $order_data['finish_time'] = $shipping_time;
            }
            D('')->startTrans();
            if(!$this->_order_mod->create($order_data)){
                D('')->rollback();
                $this->result_error($this->_order_mod->getError());
                return;
            }
            $this->_order_mod->save();
            //修改订单明细状态
            if(!$this->_order_goods_mod->where("order_id='{$order['order_id']}'")->setField('order_status',$order_data['order_status'])){
                $this->result_error('System Error.');
                return;
            }
            //记录订单操作日志
            //记录订单操作日志
            $log_data = array(
                'log_user' => 'admin|admin',
                'order_id' => $order['order_id'],
                'from_status' => $order['order_status'],
                'to_status' => $order_data['order_status'],
                'note' => '仓库发货处理',
                'log_time' => gmtime()
            );
            if(!$this->_order_log_mod->add($log_data)){
                D('')->rollback();
                $this->result_error('Saving order operation records failed.');
                return;
            }
            D('')->commit();
            $this->result_success('Success');
        }else{
            $this->result_error('Order status does not allow delivery.');
        }
    }
    
    
    //转换对象数组到数组
    function object_array($array) {  
        if(is_object($array)) {  
            $array = (array)$array;  
         }
         if(is_array($array)){  
             foreach($array as $key=>$value){  
                 $array[$key] = $this->object_array($value);  
             }  
         }  
         return $array;  
    }  
    
    //错误提示   
    function result_error($message){
        $result = array(
            'result' => array(
                'status' => 'fail',
                'message' => $message
            ),
        );
        Log::record($message,'INFO',true);
        $xml = $this->build_xml($result);
        header('Content-Type: text/xml');
        echo $xml;
    }
    
    //正确提示
    function result_success($message){
        $result = array(
            'result' => array(
                'status' => 'success',
                'message' => $message
            ),
        );
        $xml = $this->build_xml($result);
        header('Content-Type: text/xml');
        echo $xml;
    }
    
    function build_xml($info,&$resultxml = ''){
        if(!$resultxml)
            $resultxml = '<?xml version="1.0" encoding="utf-8"?>';
        if(is_array($info)){
            foreach($info as $key => $val){
                $resultxml .= '<'.$key .'>';
                if(!is_array($val)){
                    $resultxml .= $val;
                }else{
                    $this->build_xml($val,$resultxml);
                }
                $resultxml .= '</'.$key .'>';
            }
        }
        return $resultxml;  
     }
 }



?>