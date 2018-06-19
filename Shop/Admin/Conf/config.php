<?php
/**
 * 管理后台配置文件
 * @author jiwaini00000
 * @copyright 2014
 */
return array(
	'template' =>array(
		array(
			'tm_no' =>'register_success',
			'text' =>'注册成功自动回复邮件',
			'params' => array(
				'{$email}' => '登录邮箱',
				'{$password}' => '密码',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name', 
				'{$login_url}'=> '登录网址', 
				'{$site_name}'=> '网站名称', 
				'{$site_url}'=> '本站网址', 
			),
		),
		array(
			'tm_no' =>'get_password',
			'text' =>'找回密码自动回复邮件',
			'params' => array(
				'{$email}' => '登录邮箱',
				'{$get_pwd_url}' => '找回密码URL链接', 
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name', 
				'{$login_url}'=> '登录网址', 
				'{$site_name}'=> '网站名称', 
				'{$site_url}'=> '本站网址',  
			),
		),
		array(
			'tm_no' =>'subscription',
			'text' =>'邮件订阅自动回复邮件',
			'params' => array(
				'{$email}' => '登录邮箱',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name', 
				'{$login_url}'=> '登录网址', 
				'{$site_name}'=> '网站名称', 
				'{$site_url}'=> '本站网址', 
			),
		),
		array(
			'tm_no' =>'product_request_submit',
			'text' =>'提交产品需求自动回复邮件',
			'params' => array(
				'{$email}' => '登录邮箱',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name',  
				'{$site_name}'=> '网站名称', 
				'{$site_url}'=> '本站网址', 
				'{$pro_name}'=> 'Product Name',
				'{$cas_number}'=>'Cas Number', 
				'{$standard}'=>'Standard',
				'{$package}'=>'Package',
				'{$quantity}'=>'Quantity',
				'{$expected_price}'=>'Expected Price',
				'{$description}'=>'Other Details',		
			),
		),
		array(
			'tm_no' =>'payment_success',
			'text' =>'普通订单付款成功自动回复邮件',
			'params' => array( 
				'{$email}' => '登录邮箱',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name',  
				'{$site_url}'=> '本站网址', 
				'{$order_sn}' => '订单号',
				'{$pay_time}'=> '付款时间', 
				'{$totle_fee}'=> '付款金额',
			),
		),
        array(
			'tm_no' =>'pickup_payment_success',
			'text' =>'自提订单付款成功自动回复邮件',
			'params' => array( 
				'{$email}' => '登录邮箱',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name',  
				'{$site_url}'=> '本站网址', 
				'{$order_sn}' => '订单号',
				'{$pay_time}'=> '付款时间', 
				'{$totle_fee}'=> '付款金额',
			),
		),
		array(
			'tm_no' =>'order_shipped',
			'text' =>'发货完成自动回复邮件',
			'params' => array(
				'{$email}' => '登录邮箱',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name', 
				'{$site_url}'=> '本站网址', 
				'{$order_sn}' => '订单号',
				'{$invoice_no}' => '运单号',
                '{$shipping_name}' => '物流公司名称',
			),
		),	
		array(
			'tm_no' =>'order_completed',
			'text' =>'确认收货自动回复邮件附订单号，提醒填写评价，提醒付款日期后60天内可申请退货',
			'params' => array(
				'{$email}' => '登录邮箱',
				'{$first_name}'=> 'First Name',
				'{$last_name}'=> 'Last Name', 
				'{$order_sn}' => '订单号',
			),
		),				
	),
	
    'AUTH_CONFIG'=>array(
            'AUTH_ON' => true, //认证开关
            'AUTH_TYPE' => 1, // 认证方式，1为时时认证；2为登录认证。
            'AUTH_GROUP' => 'rj_auth_group', //用户组数据表名
            'AUTH_GROUP_ACCESS' => 'rj_auth_group_access', //用户组明细表
            'AUTH_RULE' => 'rj_auth_rule', //权限规则表
            'AUTH_USER' => 'rj_admin'//用户信息表
    ),
	
	
	//错误跳转对应的模板文件
	'TMPL_ACTION_ERROR' => './success',
	//成功跳转对应的模板文件
	'TMPL_ACTION_SUCCESS' => './success',
	//其它异常对应的模板（模板文件路径）
	//'TMPL_EXCEPTION_FILE' => './Shop/Admin/View/exception.html', 
	
);


?>