<?php
$config = require_once('setting.php');

$baseconfig = array(
	//'配置项'=>'配置值'
    'DB_TYPE'               => 'mysqli', #可选mysql,pdo //数据库类型 MySql Pdo:'DB_DSN' => 'mysql://root:123456@localhost:3306/thinkphp'
	
   'DB_HOST'               =>  'localhost', // 服务器地址
   'DB_NAME'               =>  'graphis',          // 数据库名
   'DB_USER'               =>  'root',      // 用户名
   'DB_PWD'                =>  '456852456852',          // 密码
	
//     'DB_HOST'               =>  'bdm280513641.my3w.com', // 服务器地址
//     'DB_NAME'               =>  'bdm280513641_db',          // 数据库名
//     'DB_USER'               =>  'bdm280513641',      // 用户名
//     'DB_PWD'                =>  '456852456852',          // 

    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'rj_',    // 数据库表前缀
    
    'URL_MODEL'             =>  2,
    
    'MODULE_ALLOW_LIST'    =>    array('Home','Admin','Webapp'),
    'DEFAULT_MODULE' => 'Home',
    

	'APP_SUB_DOMAIN_DEPLOY'   =>    1, // 开启子域名配置
	'APP_SUB_DOMAIN_RULES'    =>    array(
       'admin'  => 'Admin',  	//域名指向Admin模块  
	   'm.graphis.club:90'   => 'Webapp',  // m.okchem.com域名指向Test模块
	   'm.graphis.club'   => 'Webapp',
	),
	'URL_404_REDIRECT' => './404.html',
    //session设置
    'SESSION_AUTO_START' => false,
    'SESSION_TYPE' => 'DB',
    'SESSION_EXPIRE' =>3600,
	
    'SHIPPING' => array(
      'yunda' => array(
            'shipping_code' => 'yunda',
            'shipping_name' => '韵达快递'
        ),
       'zhongtong' =>  array(
            'shipping_code' => 'zhopngtong',
            'shipping_name' => '中通快递'
        ),
        'yuantong' => array(
            'shipping_code' => 'yuantong',
            'shipping_name' => '圆通快递',
        ),
		'shunfeng' =>  array(
            'shipping_code' => 'shunfeng',
            'shipping_name' => '顺丰快递'
        ),
        'ems' => array(
            'shipping_code' => 'ems',
            'shipping_name' => '邮政EMS',
        )
    ),  
	
	//积分类型
	'score_log_type'=>array(
		'+'=>'加积分',
		'-'=>'减积分',
    	'++'=>'冻结的积分' 
	),
	//邮件发送队列状态
	'email_send_status'=>array(
		'0'=>'等待发送',
		'1'=>'已发送' 
	),
    
    'TOKEN_ON'=>false,  // 是否开启令牌验证
    'TOKEN_NAME'=>'__hash__',    // 令牌验证的表单隐藏字段名称
    'TOKEN_TYPE'=>'md5',  //令牌哈希验证规则 默认为MD5
    'TOKEN_RESET'=>true,  //令牌验证出错后是否重置令牌 默认为true	
	
);

if($_SERVER['SERVER_NAME']=='localhost'){  //HTTP_HOST
	$baseconfig['DB_HOST']    =  'localhost'; // 服务器地址
	$baseconfig['DB_NAME']	  =  'graphis';          // 数据库名
	$baseconfig['DB_USER'] 	  =   'root';     // 用户名
	$baseconfig['DB_PWD']     =   '123456';          // 密码
} 

return array_merge($config, $baseconfig) ;
