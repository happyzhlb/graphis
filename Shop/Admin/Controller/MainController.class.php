<?php
/**
 * main控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class MainController extends BackendController{ 
	var $_user_mod=null;
	var $_order_mod=null;
	function __construct(){  
		parent::__construct();
		$this->_user_mod= D('user');
        $this->_order_mod=D('reward_order');
	}
    function index(){
        
    	$public['total_users']=$this->_user_mod->count();
    	$public['total_orders']=$this->_order_mod->where('order_status=20')->count();
    	$public['total_sales']=$this->_order_mod->where('order_status=20')->sum('total_amount');
    	$public['total_reward_count']=$this->_order_mod->where('order_status=20')->count();
    	//新用户数量（7日内）
    	$new_where='unix_timestamp(now())-ctime< 7*24*60*60';
    	$public['new_users']=$this->_user_mod->where($new_where)->count();
    	//新订单数量（7日内）
    	#$new_where='unix_timestamp(now())-add_time< 7*24*60*60 and is_delete=0';
    	$new_where='date(FROM_UNIXTIME(ctime)) = "'.date('Y-m-d').'" and order_status=20';
    	$public['new_orders']=$this->_order_mod->where($new_where)->count(); 
    	//最新销售7日内
    	$public['new_sales']=(float)$this->_order_mod->where($new_where)->sum('total_amount'); 
    	
    	$yesterday_where='date(FROM_UNIXTIME(ctime)) = "'.date('Y-m-d',strtotime("-1 days")).'" and order_status=20';
    	$public['yesterday_orders']=$this->_order_mod->where($yesterday_where)->count();
    	//昨日打赏额
    	$public['yesterday_sales']=(float)$this->_order_mod->where($yesterday_where)->sum('total_amount');
    	
    	//今日浏览量
    	$today_where = array('_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())" );
    	$public['today_view_num']=(int)M('accesslog')->where($today_where)->sum('view_num');
    	$public['today_uv']=(int)M('accesslog')->where($today_where)->count('view_num');
    	
    	$d = intval(date('d')); //本月第n天
    	$month_where=' CONCAT(year(FROM_UNIXTIME(ctime)),"-",month(FROM_UNIXTIME(ctime)))  = "'.date('Y-n').'" and order_status=20';
    	//本月打赏次数
    	$public['month_orders']=$this->_order_mod->where($month_where)->count(); 
    	//本月打赏金额
    	$public['month_sales']=(float)$this->_order_mod->where($month_where)->sum('total_amount');
    	//累计浏览量
    	$public['view_num']=(int)M('album')->sum('view_num'); 
    	//累计打赏次
    	$public['reward_times']=(int)M('album')->sum('total_reward_times');
    	//打赏浏览比率
    	$public['reward_rate']= round($public['reward_times']/$public['view_num'],4);
    	
    	//本月平均打赏次数
    	$public['month_orders_avg']= round($public['month_orders']/$d,2);
    	//本月平均打赏金额
    	$public['month_sales_avg']= round($public['month_sales']/$d,2);
    	
    	$month_where=' CONCAT(year(FROM_UNIXTIME(ctime)),"-",month(FROM_UNIXTIME(ctime)))  = "'.date('Y-n').'" and order_status=20';
    	//本月打赏模特
    	$public['month_models_num']=$this->_order_mod->where($month_where)->count('DISTINCT models_id');
    	//本月打赏专辑
    	$public['month_album_num']=$this->_order_mod->where($month_where)->count('DISTINCT album_id');
    	 
    	//安卓用户打赏次数
    	$android_where=' user_agent like \'%android%\' and order_status=20'; 
    	$public['android_orders']=$this->_order_mod->where($android_where)->count();
    	//苹果用户打赏次数
    	$android_where=' user_agent like \'%iphone%\' and order_status=20';
    	$public['iphone_orders']=$this->_order_mod->where($android_where)->count();
    	//PC用户打赏次数
    	$android_where=' user_agent like \'%windows%\' and order_status=20';
    	$public['pc_orders']=$this->_order_mod->where($android_where)->count(); 
    	//Ipad用户打赏次数
		$android_where=' user_agent like \'%ipad%\' and order_status=20';
		$public['ipad_orders']=$this->_order_mod->where($android_where)->count();
		//Macintosh用户打赏次数
		$android_where=' user_agent like \'%macintosh%\' and order_status=20';
		$public['macintosh_orders']=$this->_order_mod->where($android_where)->count();
		 
		
		$public['others_orders'] = $public['total_orders'] - $public['android_orders'] - $public['pc_orders'] - $public['iphone_orders'] - $public['ipad_orders'] - $public['macintosh_orders'];
		 
		$public['models_num']=M('models')->where(array('if_show'=>1))->count();
		$public['album_num']=M('album')->where(array('if_show'=>1))->count();
		
		
		//支付宝
		$alipay_where=' pay_code="alipay" and order_status=20';
		$public['alipay_num']=$this->_order_mod->where($alipay_where)->count();
		$public['alipay_amount']=$this->_order_mod->where($alipay_where)->sum('total_amount');
		
		//微信
		$wepay_where=' pay_code="Wepay" and order_status=20';
		$public['wepay_num']=$this->_order_mod->where($wepay_where)->count();
		$public['wepay_amount']=$this->_order_mod->where($wepay_where)->sum('total_amount');
		
		//VIP充值
		$vip_where='order_status=20';
		$public['vip_num']=M('recharge')->where($vip_where)->count();
		$public['vip_amount']=M('recharge')->where($vip_where)->sum('total_amount');
		
    	//api统计
    	#$public['config'] = M('traffic')->where('to_days(NOW()) - to_days(create_date) = 0')->find();
    	//api分享下载统计
    	#$public['download'] = M('appdownload')->where('to_days(NOW()) - to_days(create_date) = 0')->find();    	
    	//echo M()->getLastsql();
    	
		//今日人气排行
		$topViewNumAlbum = M('accesslog')->field("album_id,SUM(view_num) pv,count(view_num) uv")->where($today_where)->limit(0,5)->group('album_id')->order('pv desc')->select();
		foreach ($topViewNumAlbum as $key => $val){
		    $where = array('order_status'=>20,'album_id'=>$val['album_id']);
		    $topViewNumAlbum[$key]['total_amount'] = (float)$this->_order_mod->where(array_merge($where,$today_where))->sum('total_amount');
		}
		$public['topViewNumAlbum'] = $topViewNumAlbum;
		
		//今日财富排行
		$public['topRewardFeeAlbum'] = $this->_order_mod->field("album_id,SUM(total_amount) total_reward_fee,count(total_amount) total_reward_times")->where(array_merge(array('order_status'=>20),$today_where))->limit(0,5)->group('album_id')->order('total_reward_fee desc')->select();
 
    	$this->assign($public); 
        $this->display('/main');
    }
    //统计图表
    function main_chart(){
    	vendor('jpgraph.jpgraph');
    	vendor('jpgraph.jpgraph_bar');
    	vendor('jpgraph.jpgraph_line'); 
    	$theme = isset($_GET['theme']) ? $_GET['theme'] : null;
		$data = array (
		  0 => array (0 => 179, 1 => -25, 2 => -7, 3 => 85, 4 => -26, 5 => -32, ),
		  1 => array (0 => 76, 1 => 51, 2 => 86, 3 => 12, 4 => -7, 5 => 94, ),
		  2 => array (0 => 49, 1 => 38, 2 => 7, 3 => -40, 4 => 9, 5 => -7, ),
		  3 => array ( 0 => 69, 1 => 96, 2 => 49, 3 => 7, 4 => 92, 5 => -38, ),
		  4 => array ( 0 => 68, 1 => 16, 2 => 82, 3 => -49, 4 => 50, 5 => 7, ),
		  5 => array ( 0 => -37, 1 => 28, 2 => 32, 3 => 6, 4 => 13, 5 => 57, ),
		  6 => array ( 0 => 24, 1 => -11, 2 => 7, 3 => 10, 4 => 51, 5 => 51, ),
		  7 => array ( 0 => 3, 1 => -1, 2 => -12, 3 => 61, 4 => 10, 5 => 47, ),
		  8 => array ( 0 => -47, 1 => -21, 2 => 43, 3 => 53, 4 => 36, 5 => 34, ),
		);
		
		// Create the graph. These two calls are always required
		$graph = new \Graph(450,300);    
		
		$graph->SetScale("textlin");
		if ($theme) {
		  $graph->SetTheme(new $theme());
		}
		$theme_class = new \RoseTheme;
		$graph->SetTheme($theme_class);
		
		$plot = array();
		// Create the bar plots
		for ($i = 0; $i < 4; $i++) {
		  $plot[$i] = new \BarPlot($data[$i]);
		  $plot[$i]->SetLegend('plot'.($i+1));
		}
		//$acc1 = new AccBarPlot(array($plot[0], $plot[1]));
		//$acc1->value->Show();
		$gbplot = new \GroupBarPlot(array($plot[0], $plot[1] , $plot[2] ));
		
		for ($i = 4; $i < 8; $i++) {
		  $plot[$i] = new \LinePlot($data[$i]);
		  $plot[$i]->SetLegend('plot'.$i);
		  $plot[$i]->value->Show();
		}
		
		$graph->Add($gbplot);
		#$graph->Add($plot[4]);
		
		$title = "用户订单报表";    //mb_detect_encoding($title)
		$title = mb_convert_encoding($title,'gbk','UTF-8');
		#$title =  iconv("UTF-8", "GBK", $title);  
		$graph->title->Set($title);
		$graph->title->setfont(FF_SIMSUN,FS_BOLD,12); 
		$graph->SetMargin(60,40,10,40); 
		$graph->subtitle->Set('(www.okchem.com)');
		#$graph->subtitle->Set('('.date("Y-m-d",time()).')'); 
		
		$graph->xaxis->title->Set("X-title");
		$graph->yaxis->title->Set("Y-title");
		
		#dump($graph);
		// Display the graph
		$graph->Stroke();

    }
    
}

?>